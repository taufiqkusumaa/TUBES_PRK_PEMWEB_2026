<?php
/**
 * SparepartModel - Query PDO untuk tabel work_order_komponen
 * (dokumentasi suku cadang + catatan teknis per transaksi servis).
 *
 * Model ini HANYA berisi query SQL (Prepared Statements).
 * Validasi, otorisasi, dan upload file dilakukan di SparepartController.
 */
class SparepartModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Info ringkas transaksi (dipakai untuk otorisasi dan header halaman). */
    public function getTransaksiInfo(int $transaksiId): ?array
    {
        $sql = "SELECT t.id, t.kode_transaksi, t.user_id, t.teknisi_id, t.status_servis,
                       t.keluhan_mesin, t.total_biaya,
                       k.no_polisi, k.nama_model,
                       l.nama_paket, l.tarif_dasar,
                       tk.nama AS nama_teknisi
                FROM transaksi_servis t
                JOIN kendaraan k ON k.id = t.kendaraan_id
                JOIN layanan_servis l ON l.id = t.layanan_id
                LEFT JOIN users tk ON tk.id = t.teknisi_id
                WHERE t.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transaksiId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Semua komponen milik satu transaksi, urut sesuai waktu input. */
    public function getByTransaksi(int $transaksiId): array
    {
        $sql = "SELECT w.id, w.transaksi_id, w.teknisi_id, w.nama_suku_cadang, w.jumlah,
                       w.harga_satuan, (w.jumlah * w.harga_satuan) AS subtotal,
                       w.foto_komponen, w.catatan_perbaikan, w.created_at,
                       u.nama AS nama_teknisi
                FROM work_order_komponen w
                JOIN users u ON u.id = w.teknisi_id
                WHERE w.transaksi_id = ?
                ORDER BY w.created_at ASC, w.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transaksiId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM work_order_komponen WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Data suku cadang untuk Nota/Kuitansi (dashboard.php Ezzra) dalam bentuk yang
     * dibaca JS-nya: per transaksi => ['sparepart' => [{nama, qty, satuan, harga}],
     * 'catatan_mekanik' => string]. Dipakai TransaksiController agar satu query
     * menyuplai seluruh baris dashboard.
     *
     * @param int[] $transaksiIds
     * @return array<int, array{sparepart: array, catatan_mekanik: string}>
     */
    public function getUntukNota(array $transaksiIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $transaksiIds))));
        if (!$ids) {
            return [];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT transaksi_id, nama_suku_cadang, jumlah, harga_satuan, catatan_perbaikan
             FROM work_order_komponen
             WHERE transaksi_id IN ($in)
             ORDER BY transaksi_id ASC, id ASC"
        );
        $stmt->execute($ids);

        $hasil = [];
        $catatan = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $tid = (int) $r['transaksi_id'];
            $hasil[$tid]['sparepart'][] = [
                'nama'   => $r['nama_suku_cadang'],
                'qty'    => (int) $r['jumlah'],
                'satuan' => 'Pcs',
                'harga'  => (float) $r['harga_satuan'],
            ];
            if ($r['catatan_perbaikan'] !== null && trim($r['catatan_perbaikan']) !== '') {
                $catatan[$tid][] = trim($r['catatan_perbaikan']);
            }
        }
        foreach ($hasil as $tid => &$baris) {
            $baris['catatan_mekanik'] = isset($catatan[$tid]) ? implode(' ', $catatan[$tid]) : '';
        }
        unset($baris);

        return $hasil;
    }

    /** Agregasi COUNT/SUM untuk ringkasan biaya suku cadang. */
    public function getRingkasan(int $transaksiId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS jumlah_item,
                    COALESCE(SUM(jumlah * harga_satuan), 0) AS total_komponen
             FROM work_order_komponen
             WHERE transaksi_id = ?"
        );
        $stmt->execute([$transaksiId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'jumlah_item'     => (int) ($row['jumlah_item'] ?? 0),
            'total_komponen'  => (float) ($row['total_komponen'] ?? 0),
        ];
    }

    /**
     * Tambah komponen lalu sinkronkan total_biaya transaksi (satu transaksi DB).
     * Kunci $d: transaksi_id, teknisi_id, nama_suku_cadang, jumlah, harga_satuan,
     * foto_komponen (nullable), catatan_perbaikan (nullable).
     *
     * @return int id baris baru
     */
    public function tambah(array $d): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO work_order_komponen
                    (transaksi_id, teknisi_id, nama_suku_cadang, jumlah, harga_satuan,
                     foto_komponen, catatan_perbaikan)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $d['transaksi_id'],
                $d['teknisi_id'],
                $d['nama_suku_cadang'],
                $d['jumlah'],
                $d['harga_satuan'],
                $d['foto_komponen'] ?? null,
                $d['catatan_perbaikan'] ?? null,
            ]);
            $id = (int) $this->db->lastInsertId();
            $this->syncTotalBiaya((int) $d['transaksi_id']);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Ubah komponen. Kolom foto_komponen hanya diubah bila kuncinya ada di $d,
     * sehingga edit tanpa ganti foto tidak menghapus foto lama.
     */
    public function ubah(int $id, int $transaksiId, array $d): void
    {
        $set = ['nama_suku_cadang = ?', 'jumlah = ?', 'harga_satuan = ?', 'catatan_perbaikan = ?'];
        $params = [
            $d['nama_suku_cadang'],
            $d['jumlah'],
            $d['harga_satuan'],
            $d['catatan_perbaikan'] ?? null,
        ];
        if (array_key_exists('foto_komponen', $d)) {
            $set[] = 'foto_komponen = ?';
            $params[] = $d['foto_komponen'];
        }
        $params[] = $id;

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE work_order_komponen SET " . implode(', ', $set) . " WHERE id = ?"
            );
            $stmt->execute($params);
            $this->syncTotalBiaya($transaksiId);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function hapus(int $id, int $transaksiId): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM work_order_komponen WHERE id = ?");
            $stmt->execute([$id]);
            $this->syncTotalBiaya($transaksiId);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * total_biaya = tarif dasar paket + SUM(jumlah x harga_satuan) seluruh komponen.
     * Dipanggil di dalam transaksi DB setiap komponen berubah supaya nota selalu akurat.
     */
    private function syncTotalBiaya(int $transaksiId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE transaksi_servis t
             SET t.total_biaya =
                 (SELECT l.tarif_dasar FROM layanan_servis l WHERE l.id = t.layanan_id)
               + (SELECT COALESCE(SUM(w.jumlah * w.harga_satuan), 0)
                  FROM work_order_komponen w WHERE w.transaksi_id = t.id)
             WHERE t.id = ?"
        );
        $stmt->execute([$transaksiId]);
    }
}
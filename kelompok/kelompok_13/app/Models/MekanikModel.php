<?php
/**
 * MekanikModel - Query PDO untuk antrean Work Order teknisi.
 *
 * Model ini hanya berisi query SQL (Prepared Statements). Validasi dan
 * otorisasi dilakukan di MekanikController.
 */
class MekanikModel
{
    /** Status yang boleh ditampilkan di halaman teknisi. */
    public const STATUS_TAMPIL = ['Antre', 'Dikerjakan', 'Siap Diambil', 'Selesai'];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** SELECT dasar: transaksi + customer + kendaraan + kategori + layanan + teknisi. */
    private function selectDasar(): string
    {
        return "SELECT t.id, t.kode_transaksi, t.status_servis, t.keluhan_mesin, t.foto_kendala,
                       t.total_biaya, t.catatan_admin, t.tanggal_pesan, t.tanggal_selesai, t.teknisi_id,
                       u.nama AS nama_customer, u.no_telp AS telp_customer,
                       k.no_polisi, k.nama_model, k.warna,
                       kat.jenis, kat.merk,
                       l.nama_paket, l.estimasi_durasi_menit, l.tarif_dasar,
                       tk.nama AS nama_teknisi,
                       (SELECT COUNT(*) FROM work_order_komponen w WHERE w.transaksi_id = t.id) AS jumlah_komponen
                FROM transaksi_servis t
                JOIN users u ON u.id = t.user_id
                JOIN kendaraan k ON k.id = t.kendaraan_id
                JOIN kategori_kendaraan kat ON kat.id = k.kategori_id
                JOIN layanan_servis l ON l.id = t.layanan_id
                LEFT JOIN users tk ON tk.id = t.teknisi_id";
    }

    /**
     * Pembatas data per role. Teknisi hanya melihat pekerjaannya sendiri
     * ditambah pekerjaan berstatus Antre yang belum punya teknisi.
     * Admin melihat semuanya.
     */
    private function scope(string $role, int $userId, array &$params): string
    {
        if ($role !== 'teknisi') {
            return '';
        }
        $params[] = $userId;
        return " AND (t.teknisi_id = ? OR (t.teknisi_id IS NULL AND t.status_servis = 'Antre'))";
    }

    /** Daftar antrean work order sesuai status yang diminta. */
    public function getAntrean(string $role, int $userId, array $statuses): array
    {
        $in = implode(',', array_fill(0, count($statuses), '?'));
        $params = $statuses;
        $sql = $this->selectDasar() . " WHERE t.status_servis IN ($in)"
             . $this->scope($role, $userId, $params)
             . " ORDER BY FIELD(t.status_servis, 'Dikerjakan', 'Antre', 'Siap Diambil', 'Selesai'),
                          t.tanggal_pesan ASC
                LIMIT 300";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Agregasi COUNT/SUM untuk kartu ringkasan di atas tabel. */
    public function getRingkasan(string $role, int $userId): array
    {
        $params = [];
        $sql = "SELECT
                    COALESCE(SUM(t.status_servis = 'Antre'), 0) AS antre,
                    COALESCE(SUM(t.status_servis = 'Dikerjakan'), 0) AS dikerjakan,
                    COALESCE(SUM(t.status_servis = 'Siap Diambil'), 0) AS siap_diambil,
                    COALESCE(SUM(t.status_servis IN ('Siap Diambil', 'Selesai')
                                 AND DATE(t.tanggal_selesai) = CURDATE()), 0) AS selesai_hari_ini
                FROM transaksi_servis t
                WHERE 1 = 1" . $this->scope($role, $userId, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'antre'            => (int) ($row['antre'] ?? 0),
            'dikerjakan'       => (int) ($row['dikerjakan'] ?? 0),
            'siap_diambil'     => (int) ($row['siap_diambil'] ?? 0),
            'selesai_hari_ini' => (int) ($row['selesai_hari_ini'] ?? 0),
        ];
    }

    /** Detail satu transaksi / work order. */
    public function getDetail(int $id): ?array
    {
        $stmt = $this->db->prepare($this->selectDasar() . " WHERE t.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Ubah status servis secara atomik. Klausa "status_servis = dari" mencegah
     * dua orang menimpa status satu sama lain. Jika $teknisiId diisi dan
     * transaksi belum punya teknisi, teknisi tersebut otomatis ditugaskan.
     *
     * @return bool true jika baris berhasil diubah
     */
    public function updateStatus(int $id, string $dari, string $ke, ?int $teknisiId): bool
    {
        $sql = "UPDATE transaksi_servis
                SET status_servis   = :ke,
                    teknisi_id      = COALESCE(teknisi_id, :teknisi),
                    tanggal_selesai = CASE
                        WHEN :ke_a = 'Siap Diambil' THEN NOW()
                        WHEN :ke_b = 'Selesai' THEN COALESCE(tanggal_selesai, NOW())
                        ELSE tanggal_selesai
                    END
                WHERE id = :id AND status_servis = :dari";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ke'      => $ke,
            ':teknisi' => $teknisiId,
            ':ke_a'    => $ke,
            ':ke_b'    => $ke,
            ':id'      => $id,
            ':dari'    => $dari,
        ]);
        return $stmt->rowCount() > 0;
    }
}
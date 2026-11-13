<?php
// Komponen bersama untuk Artikel, FAQ, dan modul admin.
// File baru: tidak mengubah includes/functions.php. Semua nama fungsi berawalan art_, faq_, atau ui_.

/** Estimasi waktu baca (menit) dari panjang teks (kira-kira 6 huruf per kata, 200 kata per menit). */
function art_waktu_baca(int $panjang): int
{
    return max(1, (int) ceil($panjang / 6 / 200));
}

/** Ikon Bootstrap Icons untuk tiap bidang hukum (urutan id di schema.sql). */
function art_ikon(?int $bidang_id): string
{
    return [
        1 => 'bi-shield-lock',
        2 => 'bi-file-earmark-text',
        3 => 'bi-people',
        4 => 'bi-briefcase',
        5 => 'bi-building',
        6 => 'bi-house-door',
    ][$bidang_id ?? 0] ?? 'bi-journal-text';
}

/** Kelas warna sampul (ar-c0 sampai ar-c6, lihat halaman_dalam.css). */
function art_warna(?int $bidang_id): string
{
    return 'ar-c' . (((int) $bidang_id) % 7);
}

/** Teks tebal **seperti ini**, setelah diamankan. */
function art_inline(string $teks): string
{
    return preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($teks));
}

/**
 * Ubah isi artikel (teks biasa) menjadi HTML yang aman.
 * Aturan penulisan:  baris kosong = paragraf baru,  "## Judul" = subjudul,
 * baris berawalan "- " = daftar butir,  "1. " = daftar angka,  **teks** = tebal.
 */
function art_render(string $isi): string
{
    $isi  = str_replace(["\r\n", "\r"], "\n", trim($isi));
    $html = '';
    foreach (preg_split("/\n{2,}/", $isi) as $blok) {
        $blok = trim($blok);
        while ($blok !== '') {
            if (str_starts_with($blok, '## ')) {
                $pos = strpos($blok, "\n");
                $baris = $pos === false ? $blok : substr($blok, 0, $pos);
                $html .= '<h2>' . art_inline(substr($baris, 3)) . '</h2>';
                $blok = $pos === false ? '' : trim(substr($blok, $pos + 1));
                continue;
            }
            $baris = explode("\n", $blok);
            $butir = fn (string $pola) => count(array_filter($baris, fn ($b) => !preg_match($pola, $b))) === 0;
            if ($butir('/^\s*[-*]\s+/')) {
                $html .= '<ul>';
                foreach ($baris as $b) {
                    $html .= '<li>' . art_inline(preg_replace('/^\s*[-*]\s+/', '', $b)) . '</li>';
                }
                $html .= '</ul>';
            } elseif ($butir('/^\s*\d+[.)]\s+/')) {
                $html .= '<ol>';
                foreach ($baris as $b) {
                    $html .= '<li>' . art_inline(preg_replace('/^\s*\d+[.)]\s+/', '', $b)) . '</li>';
                }
                $html .= '</ol>';
            } else {
                $html .= '<p>' . nl2br(art_inline($blok), false) . '</p>';
            }
            $blok = '';
        }
    }
    return $html;
}

/** Cuplikan polos (tanpa tanda format) untuk kartu artikel yang belum punya ringkasan. */
function art_cuplikan(string $isi, int $maks = 160): string
{
    $polos = preg_replace('/^(##\s+|[-*]\s+|\d+[.)]\s+)/mu', '', $isi);
    $polos = trim((string) preg_replace('/\s+/u', ' ', str_replace('**', '', (string) $polos)));
    return mb_strlen($polos) > $maks ? rtrim(mb_substr($polos, 0, $maks)) . '...' : $polos;
}

/** Buat slug unik dari judul. Contoh: "Hak Asuh Anak!" -> "hak-asuh-anak" (atau "hak-asuh-anak-2" bila sudah ada). */
function art_slug(PDO $pdo, string $judul, int $abaikan_id = 0): string
{
    $dasar = strtolower(trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($judul)), '-'));
    $dasar = trim(substr($dasar, 0, 80), '-');
    if ($dasar === '') {
        $dasar = 'artikel';
    }
    $cek = $pdo->prepare('SELECT COUNT(*) FROM artikel WHERE slug = ? AND id <> ?');
    $slug = $dasar;
    for ($n = 2; $n < 500; $n++) {
        $cek->execute([$slug, $abaikan_id]);
        if ((int) $cek->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $dasar . '-' . $n;
    }
    return $dasar . '-' . time();
}

/**
 * Kartu artikel. $a berisi: slug, judul, ringkasan, cuplikan, bidang_id, bidang, created_at, panjang.
 * $besar = true untuk artikel sorotan (tata letak lebar).
 */
function art_kartu(array $a, bool $besar = false): string
{
    $alamat  = e(url('artikel_detail.php?slug=' . rawurlencode($a['slug'])));
    $ringkas = trim((string) ($a['ringkasan'] ?? '')) !== '' ? $a['ringkasan'] : art_cuplikan((string) ($a['cuplikan'] ?? ''));
    $bidang  = (int) ($a['bidang_id'] ?? 0);
    ob_start(); ?>
    <article class="ar-card <?= $besar ? 'ar-featured' : '' ?>">
      <a class="ar-cover <?= art_warna($bidang) ?>" href="<?= $alamat ?>" tabindex="-1" aria-hidden="true">
        <i class="bi <?= art_ikon($bidang) ?>"></i>
        <?php if (!empty($a['bidang'])): ?><span class="ar-badge"><?= e($a['bidang']) ?></span><?php endif; ?>
      </a>
      <div class="ar-body">
        <?php if ($besar): ?><span class="ar-label">Terbaru</span><?php endif; ?>
        <div class="ar-meta">
          <span><i class="bi bi-calendar3"></i><?= e(tgl($a['created_at'])) ?></span>
          <span><i class="bi bi-clock"></i><?= art_waktu_baca((int) ($a['panjang'] ?? 0)) ?> menit baca</span>
        </div>
        <h3 class="ar-judul"><a href="<?= $alamat ?>"><?= e($a['judul']) ?></a></h3>
        <p class="ar-ringkas"><?= e($ringkas) ?></p>
        <a class="ar-lanjut" href="<?= $alamat ?>">Baca selengkapnya <i class="bi bi-arrow-right"></i></a>
      </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

/** Navigasi halaman (pagination). $query = filter yang ikut dibawa, mis. ['q' => 'pajak']. */
function art_pagination(int $hal, int $totalHal, string $dasar, array $query = []): string
{
    if ($totalHal <= 1) {
        return '';
    }
    $tautan = function (int $h) use ($dasar, $query): string {
        $q = $query;
        if ($h > 1) {
            $q['hal'] = $h;
        }
        $q = array_filter($q, fn ($v) => $v !== '' && $v !== null);
        return e(url($dasar) . ($q ? '?' . http_build_query($q) : ''));
    };
    $butir = function (string $isi, int $h, bool $aktif = false, bool $mati = false) use ($tautan): string {
        $kelas = 'page-item' . ($aktif ? ' active' : '') . ($mati ? ' disabled' : '');
        return '<li class="' . $kelas . '"><a class="page-link" href="' . ($mati ? '#' : $tautan($h)) . '">' . $isi . '</a></li>';
    };
    $html = '<nav aria-label="Navigasi halaman"><ul class="pagination justify-content-center flex-wrap">';
    $html .= $butir('&laquo;', $hal - 1, false, $hal <= 1);
    $tampil = array_unique(array_filter([1, $hal - 1, $hal, $hal + 1, $totalHal], fn ($h) => $h >= 1 && $h <= $totalHal));
    sort($tampil);
    $terakhir = 0;
    foreach ($tampil as $h) {
        if ($h - $terakhir > 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
        $html .= $butir((string) $h, $h, $h === $hal);
        $terakhir = $h;
    }
    $html .= $butir('&raquo;', $hal + 1, false, $hal >= $totalHal);
    return $html . '</ul></nav>';
}

/** Kategori FAQ. Kategori disimpan di kolom urutan: 101-199 = Umum, 201-299 = Konsultasi, dst. */
function faq_kategori(): array
{
    return [
        1 => ['Umum', 'bi-info-circle'],
        2 => ['Konsultasi', 'bi-chat-dots'],
        3 => ['Akun & Keamanan', 'bi-shield-check'],
        4 => ['Untuk Pengacara', 'bi-briefcase'],
    ];
}

function faq_kat_id(int $urutan): int
{
    $k = intdiv($urutan, 100);
    return isset(faq_kategori()[$k]) ? $k : 1;
}

/** Huruf awal nama untuk avatar bulat. */
function ui_inisial(string $nama): string
{
    $kata = preg_split('/\s+/', trim(explode(',', $nama)[0]), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return mb_strtoupper(mb_substr($kata[0] ?? '?', 0, 1) . (isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
}

/** Nomor warna avatar (0-5) yang selalu sama untuk nama yang sama. */
function ui_warna(string $nama): int
{
    return abs(crc32($nama)) % 6;
}

<?php
// Komponen tampilan yang dipakai Beranda (index.php) dan Daftar Pengacara (pengacara.php).
// File baru: tidak mengubah includes/functions.php.

/** "Andi Pratama, S.H., M.H." -> "Andi Pratama" */
function nama_tanpa_gelar(string $nama): string
{
    return trim(explode(',', $nama)[0]);
}

/** "Andi Pratama Wijaya, S.H." -> "AP" (huruf awal untuk avatar) */
function inisial(string $nama): string
{
    $kata = preg_split('/\s+/', nama_tanpa_gelar($nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $a = mb_substr($kata[0] ?? '?', 0, 1);
    $b = isset($kata[1]) ? mb_substr($kata[1], 0, 1) : '';
    return mb_strtoupper($a . $b);
}

/** Warna avatar yang selalu sama untuk nama yang sama (av-0 sampai av-5, lihat beranda.css) */
function kelas_avatar(string $nama): string
{
    return 'av-' . (abs(crc32($nama)) % 6);
}

/** "Rina Kartika" -> "Rina K." (menjaga privasi klien di testimoni) */
function nama_singkat(string $nama): string
{
    $kata = preg_split('/\s+/', nama_tanpa_gelar($nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($kata) < 2) {
        return $kata[0] ?? 'Klien';
    }
    return $kata[0] . ' ' . mb_strtoupper(mb_substr($kata[1], 0, 1)) . '.';
}

/** Keahlian pengacara = kalimat pertama bio yang berawalan "Spesialis ...". */
function spesialisasi(?string $bio): string
{
    if ($bio !== null && preg_match('/^Spesialis\s+([^.]+)\./u', $bio, $m)) {
        return mb_strtoupper(mb_substr($m[1], 0, 1)) . mb_substr($m[1], 1);
    }
    return '';
}

/** Bio tanpa kalimat "Spesialis ..." (supaya tidak tampil dobel). */
function bio_ringkas(?string $bio): string
{
    return trim((string) preg_replace('/^Spesialis\s+[^.]+\.\s*/u', '', (string) $bio));
}

/** Ikon Bootstrap Icons untuk tiap bidang hukum. */
function ikon_bidang(string $nama): string
{
    $peta = [
        'pidana'          => 'bi-shield-lock',
        'perdata'         => 'bi-file-earmark-text',
        'keluarga'        => 'bi-people',
        'ketenagakerjaan' => 'bi-briefcase',
        'bisnis'          => 'bi-building',
        'properti'        => 'bi-house-door',
    ];
    foreach ($peta as $kunci => $ikon) {
        if (stripos($nama, $kunci) !== false) {
            return $ikon;
        }
    }
    return 'bi-bank2';
}

/** Bintang rating 0-5 (mendukung setengah bintang). */
function bintang(float $nilai): string
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($nilai >= $i - 0.25) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } elseif ($nilai >= $i - 0.75) {
            $html .= '<i class="bi bi-star-half"></i>';
        } else {
            $html .= '<i class="bi bi-star"></i>';
        }
    }
    return $html;
}

/** Angka desimal gaya Indonesia: 4.5 -> "4,5" */
function angka_desimal(float $nilai): string
{
    return number_format($nilai, 1, ',', '.');
}

/**
 * Kartu pengacara. Data yang dibutuhkan:
 * id, nama, bidang_id, bidang, bio, pengalaman_tahun, kota, rating, jumlah_ulasan
 */
function kartu_pengacara(array $p): string
{
    $rating = (float) $p['rating'];
    $jumlah = (int) $p['jumlah_ulasan'];
    $spes   = spesialisasi($p['bio']);
    $ringkas = bio_ringkas($p['bio']);

    ob_start();
    ?>
    <article class="card-rh hoverable pc">
      <div class="pc-atas">
        <div class="pc-avatar <?= e(kelas_avatar($p['nama'])) ?>">
          <?= e(inisial($p['nama'])) ?>
          <i class="bi bi-patch-check-fill pc-verif" title="Terverifikasi"></i>
        </div>
        <div class="pc-info">
          <h3 class="pc-nama"><?= e($p['nama']) ?></h3>
          <span class="pc-bidang"><i class="bi <?= e(ikon_bidang($p['bidang'])) ?>"></i> <?= e($p['bidang']) ?></span>
        </div>
      </div>

      <?php if ($spes !== ''): ?>
        <div class="pc-spes"><small>Spesialisasi</small><span><?= e($spes) ?></span></div>
      <?php endif; ?>
      <?php if ($ringkas !== ''): ?>
        <p class="pc-bio"><?= e($ringkas) ?></p>
      <?php endif; ?>

      <ul class="pc-meta">
        <li><i class="bi bi-geo-alt"></i> <?= e($p['kota'] ?? '-') ?></li>
        <li><i class="bi bi-briefcase"></i> <?= (int) $p['pengalaman_tahun'] ?> tahun pengalaman</li>
      </ul>

      <div class="pc-bawah">
        <div class="pc-rating">
          <?php if ($jumlah > 0): ?>
            <span class="bintang"><?= bintang($rating) ?></span>
            <strong><?= e(angka_desimal($rating)) ?></strong>
            <small>(<?= $jumlah ?> ulasan)</small>
          <?php else: ?>
            <small class="text-secondary">Belum ada ulasan</small>
          <?php endif; ?>
        </div>
        <a class="btn btn-navy btn-sm" href="<?= e(url('klien/konsultasi_baru.php?bidang=' . (int) $p['bidang_id'])) ?>">
          Konsultasi <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

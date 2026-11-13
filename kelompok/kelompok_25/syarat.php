<?php
require_once __DIR__ . '/config/app.php';
$judul = 'Syarat Penggunaan Layanan';
require_once __DIR__ . '/includes/header.php';
$bagian = [
    ['Penerimaan syarat', 'Dengan membuat akun atau memakai Ruang Hukum, kamu menyatakan telah membaca dan menyetujui syarat ini. Bila tidak setuju, mohon jangan membuat akun.'],
    ['Akun dan keamanan', 'Kamu bertanggung jawab atas kerahasiaan kata sandi dan seluruh kegiatan di akunmu. Berikan data yang benar saat mendaftar. Laporkan ke admin bila akunmu dipakai orang lain.'],
    ['Sifat layanan konsultasi', 'Jawaban pengacara di Ruang Hukum bersifat informasi dan arahan awal. Jawaban itu bukan pemberian kuasa dan tidak menggantikan pendampingan hukum resmi di kantor atau pengadilan.'],
    ['Kewajiban pengguna', 'Gunakan layanan secara sopan dan sesuai hukum. Dilarang mengirim konten yang melanggar hukum, menyinggung SARA, atau menyalahgunakan data orang lain.'],
    ['Pengacara', 'Pengacara wajib mencantumkan nomor izin advokat yang benar dan baru aktif setelah diverifikasi admin. Admin dapat menonaktifkan akun yang terbukti memberi data palsu.'],
    ['Data pribadi', 'Kami menyimpan nama, username, email, nomor handphone, perusahaan, dan riwayat konsultasimu untuk menjalankan layanan. Data tidak dijual ke pihak lain. Kata sandi disimpan dalam bentuk hash, bukan teks asli.'],
    ['Batasan tanggung jawab', 'Ruang Hukum berusaha menjaga layanan tetap berjalan, tetapi tidak menjamin layanan bebas gangguan dan tidak bertanggung jawab atas keputusan yang diambil berdasarkan jawaban konsultasi.'],
    ['Perubahan syarat', 'Syarat ini dapat diperbarui. Penggunaan layanan setelah perubahan berarti kamu menyetujui syarat yang baru.'],
];
?>
<div class="container page-head">
  <div class="card-rh p-4 p-md-5 mx-auto" style="max-width: 820px;">
    <h1 class="h2">Syarat Penggunaan Layanan</h1>
    <p class="text-secondary">Terakhir diperbarui 12 November 2026.</p>
    <?php foreach ($bagian as $i => [$jdl, $isi]): ?>
      <h2 class="h5 mt-4"><?= $i + 1 ?>. <?= e($jdl) ?></h2>
      <p class="mb-0"><?= e($isi) ?></p>
    <?php endforeach; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

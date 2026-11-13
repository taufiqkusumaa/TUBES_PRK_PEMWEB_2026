<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/upload_gambar.php';
require_once __DIR__ . '/../../config/database.php'; // menyediakan $pdo

// Daftar kategori untuk dropdown, sekaligus untuk memvalidasi id kategori yang dikirim.
$kategori = $pdo->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetchAll();
$idKategoriValid = array_map('strval', array_column($kategori, 'id_kategori'));

$nilai = ['judul' => '', 'kategori' => '', 'isi' => '', 'gambar_url' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nilai['judul']    = trim($_POST['judul'] ?? '');
    $nilai['kategori'] = trim((string) ($_POST['kategori'] ?? ''));
    $nilai['isi']      = trim($_POST['isi'] ?? '');

    // Validasi di sisi server, jangan hanya mengandalkan atribut required di HTML.
    if ($nilai['judul'] === '' || mb_strlen($nilai['judul']) > 255) {
        $errors[] = 'Judul wajib diisi, maksimal 255 karakter.';
    }
    if ($nilai['isi'] === '') {
        $errors[] = 'Isi berita wajib diisi.';
    }
    if (!in_array($nilai['kategori'], $idKategoriValid, true)) {
        $errors[] = 'Kategori tidak valid.';
    }

    [$namaGambar, $errorGambar] = upload_gambar($_FILES['gambar'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
    if ($errorGambar !== null) {
        $errors[] = $errorGambar;
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO berita (id_user, id_kategori, judul_berita, isi_berita, gambar_url)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $_SESSION['id_user'],
            (int) $nilai['kategori'],
            $nilai['judul'],
            $nilai['isi'],
            $namaGambar,
        ]);

        header('Location: index.php?pesan=tambah');
        exit;
    }

    // Validasi gagal: buang gambar yang sudah terlanjur diunggah agar tidak menumpuk di server.
    hapus_gambar($namaGambar);
}
?>
<?php
$judulTab   = 'Tambah Berita';
$breadcrumb = [['Dashboard', '/admin/index.php'], ['Berita', '/admin/berita/index.php'], ['Tambah', null]];
include __DIR__ . '/../../includes/admin_header.php';
?>

<?php
$action = 'tambah.php';
$judulHalaman = 'Tambah Berita';
include __DIR__ . '/../../includes/form_berita.php';
?>
</main></div>
</body>
</html>

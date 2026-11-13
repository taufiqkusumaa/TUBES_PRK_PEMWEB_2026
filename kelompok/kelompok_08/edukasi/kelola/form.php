<?php
// Form tambah (tanpa ?id) dan edit (dengan ?id=...) artikel.
require_once __DIR__ . '/guard.php';

$id = (int) ($_GET['id'] ?? 0);
$artikel = ['judul' => '', 'kategori_id' => 0, 'ringkasan' => '', 'isi' => '', 'status' => 'draft'];
$error = '';

if ($id > 0) {
    // Hanya artikel milik petani yang sedang login
    $stmt = $conn->prepare('SELECT judul, kategori_id, ringkasan, isi, status FROM artikel WHERE id = ? AND penulis_id = ?');
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $ada = $stmt->get_result()->fetch_assoc();
    if (!$ada) {
        http_response_code(404);
        exit('Artikel tidak ditemukan atau bukan milik Anda.');
    }
    $artikel = $ada;
}

$kategori = $conn->query('SELECT id, nama_kategori FROM kategori_artikel ORDER BY nama_kategori')->fetch_all(MYSQLI_ASSOC);
$id_valid = array_map('intval', array_column($kategori, 'id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Token tidak valid. Muat ulang halaman lalu coba lagi.');
    }
    $artikel['judul']       = trim($_POST['judul'] ?? '');
    $artikel['kategori_id'] = (int) ($_POST['kategori_id'] ?? 0);
    $artikel['ringkasan']   = trim($_POST['ringkasan'] ?? '');
    $artikel['isi']         = trim($_POST['isi'] ?? '');
    $artikel['status']      = ($_POST['status'] ?? '') === 'publish' ? 'publish' : 'draft';

    if ($artikel['judul'] === '' || mb_strlen($artikel['judul']) > 150) {
        $error = 'Judul wajib diisi (maksimal 150 karakter).';
    } elseif (!in_array($artikel['kategori_id'], $id_valid, true)) {
        $error = 'Pilih kategori yang tersedia.';
    } elseif ($artikel['ringkasan'] === '' || mb_strlen($artikel['ringkasan']) > 255) {
        $error = 'Ringkasan wajib diisi (maksimal 255 karakter).';
    } elseif ($artikel['isi'] === '') {
        $error = 'Isi artikel wajib diisi.';
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE artikel SET judul = ?, kategori_id = ?, ringkasan = ?, isi = ?, status = ? WHERE id = ? AND penulis_id = ?');
            $stmt->bind_param('sisssii', $artikel['judul'], $artikel['kategori_id'], $artikel['ringkasan'], $artikel['isi'], $artikel['status'], $id, $user_id);
            $stmt->execute();
            $_SESSION['flash'] = 'Artikel berhasil diperbarui.';
        } else {
            $slug = buat_slug($conn, $artikel['judul']);
            $stmt = $conn->prepare('INSERT INTO artikel (penulis_id, kategori_id, judul, slug, ringkasan, isi, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iisssss', $user_id, $artikel['kategori_id'], $artikel['judul'], $slug, $artikel['ringkasan'], $artikel['isi'], $artikel['status']);
            $stmt->execute();
            $_SESSION['flash'] = 'Artikel berhasil ditambahkan.';
        }
        header('Location: index.php');
        exit;
    }
}

$judul_halaman = ($id > 0 ? 'Edit' : 'Tambah') . ' Artikel | BUYPANEN';
include __DIR__ . '/../header.php';
?>
<main class="container py-5" style="max-width:800px">
  <a href="index.php" class="text-decoration-none">&larr; Kembali ke daftar</a>
  <h1 class="mt-3 mb-4"><?= $id > 0 ? 'Edit Artikel' : 'Tambah Artikel' ?></h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="bg-white p-4 rounded shadow-sm">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="judul">Judul</label>
      <input type="text" class="form-control" id="judul" name="judul" maxlength="150" required
             value="<?= htmlspecialchars($artikel['judul']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="kategori_id">Kategori</label>
      <select class="form-select" id="kategori_id" name="kategori_id" required>
        <option value="">-- Pilih kategori --</option>
        <?php foreach ($kategori as $k): ?>
          <option value="<?= (int) $k['id'] ?>" <?= (int) $artikel['kategori_id'] === (int) $k['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($k['nama_kategori']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="ringkasan">Ringkasan</label>
      <textarea class="form-control" id="ringkasan" name="ringkasan" rows="2" maxlength="255" required><?= htmlspecialchars($artikel['ringkasan']) ?></textarea>
      <div class="form-text">Tampil di kartu daftar artikel, maksimal 255 karakter.</div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="isi">Isi artikel</label>
      <textarea class="form-control" id="isi" name="isi" rows="10" required><?= htmlspecialchars($artikel['isi']) ?></textarea>
      <div class="form-text">Pisahkan paragraf dengan baris kosong.</div>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" for="status">Status</label>
      <select class="form-select" id="status" name="status">
        <option value="draft" <?= $artikel['status'] === 'draft' ? 'selected' : '' ?>>Draft (belum tampil di publik)</option>
        <option value="publish" <?= $artikel['status'] === 'publish' ? 'selected' : '' ?>>Publish (tampil di halaman edukasi)</option>
      </select>
    </div>
    <button type="submit" class="btn btn-panen">Simpan</button>
    <a href="index.php" class="btn btn-outline-secondary">Batal</a>
  </form>
</main>
<?php include __DIR__ . '/../footer.php'; ?>
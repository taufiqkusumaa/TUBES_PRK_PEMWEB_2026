<?php
// ==========================================
// 1. PROTEKSI SESSION
// ==========================================
$allowed_roles = ['admin'];
require_once __DIR__ . '/../../includes/auth.check.php';

require_once '../../config/koneksi.php';
include '../../views/header.php';

// Proteksi cadangan khusus admin
$cek_role = strtolower(trim($_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '')));
if ($cek_role !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Admin.'); window.location='../../index.php';</script>";
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET['id'];
$get_data = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id'");
$data = mysqli_fetch_assoc($get_data);

if (!$data) {
    echo "<script>alert('Data user tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap']));
    $username     = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $role         = mysqli_real_escape_string($koneksi, trim($_POST['role']));
    $password     = $_POST['password'] ?? '';

    // Cek duplikasi username milik user lain
    $cek_user = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$username' AND id_user != '$id'");
    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>alert('Username sudah dipakai akun lain!'); window.history.back();</script>";
        exit;
    }

    // Jika password diisi, update dengan hash baru; jika kosong, pertahankan password lama
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $update = mysqli_query($koneksi, "UPDATE users SET 
                                          username = '$username',
                                          password = '$password_hash',
                                          nama_lengkap = '$nama_lengkap',
                                          role = '$role'
                                          WHERE id_user = '$id'");
    } else {
        $update = mysqli_query($koneksi, "UPDATE users SET 
                                          username = '$username',
                                          nama_lengkap = '$nama_lengkap',
                                          role = '$role'
                                          WHERE id_user = '$id'");
    }

    if ($update) {
        echo "<script>alert('Data user berhasil diperbarui!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data user!'); window.location='index.php';</script>";
    }
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil-square me-2"></i>Edit Data User</h2>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($data['nama_lengkap']); ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data['username']); ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Password Baru <small class="text-muted fw-normal">(Kosongkan jika tidak diubah)</small></label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Isi hanya jika ingin mengganti password" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()" title="Lihat/Sembunyikan Password">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Role / Hak Akses <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="peternak" <?= $data['role'] === 'peternak' ? 'selected' : ''; ?>>Peternak</option>
                        <option value="dokter" <?= $data['role'] === 'dokter' ? 'selected' : ''; ?>>Dokter</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                <a href="index.php" class="btn btn-outline-secondary ms-1">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (password.type === 'password') {
        password.type = 'text';
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
    } else {
        password.type = 'password';
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
    }
}
</script>

<?php include '../../views/footer.php'; ?>
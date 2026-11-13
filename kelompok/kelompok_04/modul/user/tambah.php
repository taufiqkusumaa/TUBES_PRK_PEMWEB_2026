<?php
// 1. Proteksi session
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap']));
    $username     = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password     = $_POST['password'];
    $role         = mysqli_real_escape_string($koneksi, trim($_POST['role']));

    // Cek apakah username sudah digunakan
    $cek_user = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>alert('Username sudah terdaftar! Gunakan username lain.'); window.history.back();</script>";
        exit;
    }

    // Enkripsi password dengan standar Bcrypt PHP
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $insert = mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) 
                                      VALUES ('$username', '$password_hash', '$nama_lengkap', '$role')");

    if ($insert) {
        echo "<script>alert('Data user berhasil ditambahkan!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data user!'); window.location='index.php';</script>";
    }
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-plus me-2"></i>Tambah User Baru</h2>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Budi Santoso" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" placeholder="Contoh: peternak2" autocomplete="off" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" autocomplete="new-password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()" title="Lihat/Sembunyikan Password">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Role / Hak Akses <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="admin">Admin</option>
                        <option value="peternak">Peternak</option>
                        <option value="dokter">Dokter</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan User</button>
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
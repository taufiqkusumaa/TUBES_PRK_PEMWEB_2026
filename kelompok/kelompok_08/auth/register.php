
<?php
require_once __DIR__ . '/../config/database.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama = trim($_POST["nama"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $no_whatsapp = trim($_POST["no_whatsapp"]);
    $lokasi_kebun = trim($_POST["lokasi_kebun"]);

    if (
        empty($nama) || empty($email) ||
        empty($password) || empty($no_whatsapp) ||
        empty($lokasi_kebun)
    ) {
        $pesan = "Semua field wajib diisi!";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = "Format email tidak valid!";

    } elseif (strlen($password) < 8) {
        $pesan = "Password minimal 8 karakter!";

    } else {

        $cek = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $pesan = "Email sudah terdaftar!";

        } else {

            mysqli_begin_transaction($conn);

            try {
                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $role = "petani";

             
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO users (nama, email, password, role)
                     VALUES (?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssss",
                    $nama,
                    $email,
                    $password_hash,
                    $role
                );

                mysqli_stmt_execute($stmt);

                $user_id = mysqli_insert_id($conn);

             
                $stmt_petani = mysqli_prepare(
                    $conn,
                    "INSERT INTO petani
                     (user_id, no_whatsapp, lokasi_kebun)
                     VALUES (?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt_petani,
                    "iss",
                    $user_id,
                    $no_whatsapp,
                    $lokasi_kebun
                );

                mysqli_stmt_execute($stmt_petani);

               
                mysqli_commit($conn);

         
                header("Location: ../auth/login.php?register=success");
                exit;

            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $pesan = "Registrasi gagal. Silakan coba kembali.";
            }
        }

        mysqli_stmt_close($cek);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrasi Petani - BUYPANEN</title>

    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="form-container">

    <div style="text-align:center; margin-bottom:25px;">
        <h1>🌱 BUYPANEN</h1>
        <p>Daftarkan akun petani Anda</p>
    </div>

    <?php if ($pesan != ""): ?>
        <div class="alert">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Nama Lengkap</label>
        <input type="text" name="nama"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
               required>

        <label>Email</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required>

        <label>Password</label>
        <input type="password" name="password"
               minlength="8" required>

        <label>Nomor WhatsApp</label>
        <input type="text" name="no_whatsapp"
               value="<?= htmlspecialchars($_POST['no_whatsapp'] ?? '') ?>"
               required>

        <label>Lokasi Kebun</label>
        <textarea name="lokasi_kebun"
                  rows="4" required><?= htmlspecialchars($_POST['lokasi_kebun'] ?? '') ?></textarea>

        <button type="submit">Daftar Sekarang</button>

    </form>

    <p style="text-align:center; margin-top:20px;">
        Sudah punya akun?
        <a href="../auth/login.php">Login di sini</a>
    </p>

</div>

</body>
</html>
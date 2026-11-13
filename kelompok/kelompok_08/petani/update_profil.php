<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/style.css">
<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION["user_id"];
$pesan = "";

$query = mysqli_prepare(
    $conn,
    "SELECT users.nama, petani.no_whatsapp, petani.lokasi_kebun
     FROM users
     INNER JOIN petani ON users.id = petani.user_id
     WHERE users.id = ?"
);

mysqli_stmt_bind_param($query, "i", $user_id);
mysqli_stmt_execute($query);

$result = mysqli_stmt_get_result($query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    exit("Data profil tidak ditemukan.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama = trim($_POST["nama"] ?? "");
    $no_whatsapp = trim($_POST["no_whatsapp"] ?? "");
    $lokasi_kebun = trim($_POST["lokasi_kebun"] ?? "");

    if ($nama === "" || $no_whatsapp === "" || $lokasi_kebun === "") {

        $pesan = "Semua data wajib diisi!";

    } elseif (strlen($nama) > 100 || strlen($no_whatsapp) > 20) {

        $pesan = "Nama atau nomor WhatsApp terlalu panjang!";

    } else {

        mysqli_begin_transaction($conn);

        try {

            $update_user = mysqli_prepare(
                $conn,
                "UPDATE users SET nama = ? WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update_user,
                "si",
                $nama,
                $user_id
            );

            mysqli_stmt_execute($update_user);

            $update_petani = mysqli_prepare(
                $conn,
                "UPDATE petani
                 SET no_whatsapp = ?, lokasi_kebun = ?
                 WHERE user_id = ?"
            );

            mysqli_stmt_bind_param(
                $update_petani,
                "ssi",
                $no_whatsapp,
                $lokasi_kebun,
                $user_id
            );

            mysqli_stmt_execute($update_petani);

            mysqli_commit($conn);

            $_SESSION["nama"] = $nama;

            header("Location: profil.php");
            exit;

        } catch (Throwable $e) {

            mysqli_rollback($conn);
            $pesan = "Gagal memperbarui profil.";

        }
    }

    $data["nama"] = $nama;
    $data["no_whatsapp"] = $no_whatsapp;
    $data["lokasi_kebun"] = $lokasi_kebun;
}

$user_id = $_SESSION['user_id'];

$query = "SELECT 
            users.nama,
            users.email,
            petani.no_whatsapp,
            petani.lokasi_kebun
          FROM users
          INNER JOIN petani ON users.id = petani.user_id
          WHERE users.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$petani = mysqli_fetch_assoc($result);

if (!$petani) {
    die("Data profil petani tidak ditemukan.");
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Petani - BUYPANEN</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="form-container">
    <div style="text-align:center; margin-bottom:25px;">
        <h1>🌱 Edit Profil</h1>
        <p>Perbarui informasi akun dan profil petani Anda.</p>
    </div>

    <form method="POST">
        <label>Nama Lengkap</label>
        <input
            type="text"
            name="nama"
            value="<?= htmlspecialchars($petani['nama']) ?>"
            required
        >

        <label>Email</label>
        <input
            type="email"
            value="<?= htmlspecialchars($petani['email']) ?>"
            disabled
        >

        <label>Nomor WhatsApp</label>
        <input
            type="text"
            name="no_whatsapp"
            value="<?= htmlspecialchars($petani['no_whatsapp']) ?>"
            required
        >

        <label>Lokasi Kebun</label>
        <textarea
            name="lokasi_kebun"
            rows="4"
            required
        ><?= htmlspecialchars($petani['lokasi_kebun']) ?></textarea>

        <button type="submit" name="update">Simpan Perubahan</button>
    </form>

    <a href="profil.php"
       style="display:block; text-align:center; margin-top:18px; color:#218838; text-decoration:none;">
        ← Kembali ke Profil
    </a>
</div>

</body>
</html>
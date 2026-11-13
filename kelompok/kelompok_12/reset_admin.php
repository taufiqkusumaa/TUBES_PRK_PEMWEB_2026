
<?php
require_once 'config/database.php';

$username = 'admin';
$password = 'admin123';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "UPDATE admins SET password = :password WHERE username = :username"
);

$stmt->execute([
    ':password' => $hashedPassword,
    ':username' => $username
]);

if ($stmt->rowCount() > 0) {
    echo "Password admin berhasil direset!";
} else {
    echo "Password tidak berubah atau username tidak ditemukan.";
}
?>
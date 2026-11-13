<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Nama kategori wajib diisi!';
    } else {
        try {
            // Periksa apakah nama kategori sudah ada
            $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name");
            $checkStmt->execute([':name' => $name]);

            if ($checkStmt->rowCount() > 0) {
                $error = 'Kategori dengan nama tersebut sudah ada!';
            } else {
                // Simpan kategori baru ke database
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description
                ]);

                header('Location: categories.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan kategori: ' . $e->getMessage();
        }
    }
}

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kategori - Sehatin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 30px;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        h2 { color: #333; margin-top: 0; }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        button {
            background: #198754;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #6c757d;
            text-decoration: none;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="categories.php" class="back-link">&larr; Kembali ke Daftar Kategori</a>
    
    <h2>Tambah Kategori Baru</h2>

    <?php if ($error !== ''): ?>
        <p class="error"><?= escape($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="category-add.php">
        <div class="form-group">
            <label>Nama Kategori</label>
            <input type="text" name="name" value="<?= escape($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Deskripsi (Opsional)</label>
            <textarea name="description"><?= escape($_POST['description'] ?? '') ?></textarea>
        </div>

        <button type="submit">Simpan Kategori</button>
    </form>
</div>

</body>
</html>
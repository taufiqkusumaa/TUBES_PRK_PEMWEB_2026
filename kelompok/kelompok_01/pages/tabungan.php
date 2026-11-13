<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Tabungan';
$idKeluarga = $_SESSION['user']['id_keluarga'];

$targets = [];
$totalTerkumpul = 0;
$error = '';

try {
    $stmt = $pdo->prepare('CALL sp_target_daftar(?)');
    $stmt->execute([$idKeluarga]);
    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($targets as $target) {
        $totalTerkumpul += (float) $target['terkumpul'];
    }
} catch (PDOException $e) {
    $error = 'Data tabungan gagal dimuat.';
}

$success = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? $error;

unset($_SESSION['success'], $_SESSION['error']);

function rupiah($nominal)
{
    return 'Rp ' . number_format((float) $nominal, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaveSmart - Tabungan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/savings.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../components/navbar.php'; ?>

            <div class="savings-content">
                <section class="welcome-section">
                    <div>
                        <h1>Tabungan keluarga</h1>
                        <p>Kelola target dan pantau perkembangan tabungan keluarga Anda.</p>
                    </div>
                    <button class="btn-add-target" id="btnTambahTarget">+ Tambah Target</button>
                </section>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <section class="savings-summary">
                    <div class="savings-summary-card">
                        <p>Total Target</p>
                        <h3><?= count($targets) ?></h3>
                        <small>Target tabungan keluarga</small>
                    </div>

                    <div class="savings-summary-card">
                        <p>Total Terkumpul</p>
                        <h3><?= rupiah($totalTerkumpul) ?></h3>
                        <small>Akumulasi seluruh setoran</small>
                    </div>
                </section>

                <section class="target-section">
                    <div class="section-heading">
                        <div>
                            <h3>Daftar Target Tabungan</h3>
                            <p>Pantau progres setiap target tabungan Anda.</p>
                        </div>
                    </div>

                    <div class="target-list" id="targetList">
                        <?php if (empty($targets)): ?>
                            <div class="empty-target">
                                <h3>Belum Ada Target Tabungan</h3>
                                <p>Mulai buat target tabungan keluarga Anda.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($targets as $target): ?>
                                <?php $persen = max(0, min(100, (float) $target['persen'])); ?>

                                <div class="target-card">
                                    <div class="target-card-header">
                                        <div>
                                            <h3><?= htmlspecialchars($target['nama_target']) ?></h3>
                                            <span class="target-status"><?= htmlspecialchars($target['status']) ?></span>
                                        </div>
                                        <div class="target-actions">
                                            <button type="button" class="btn-edit-target" data-id="<?= $target['id_target'] ?>">Edit</button>
                                            <button type="button" class="btn-delete-target" data-id="<?= $target['id_target'] ?>">Hapus</button>
                                        </div>
                                    </div>

                                    <div class="target-amount">
                                        <div>
                                            <small>Terkumpul</small>
                                            <strong><?= rupiah($target['terkumpul']) ?></strong>
                                        </div>
                                        <div>
                                            <small>Target</small>
                                            <strong><?= rupiah($target['target_nominal']) ?></strong>
                                        </div>
                                    </div>

                                    <div class="progress-info">
                                        <span>Progres tabungan</span>
                                        <strong><?= number_format($persen, 1, ',', '.') ?>%</strong>
                                    </div>

                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $persen ?>%"></div>
                                    </div>

                                    <div class="target-details">
                                        <span>Sisa: <?= rupiah($target['sisa']) ?></span>
                                        <span>Target tanggal: <?= htmlspecialchars($target['tanggal_target']) ?></span>
                                    </div>

                                    <button type="button" class="btn-add-deposit" data-id="<?= $target['id_target'] ?>" data-name="<?= htmlspecialchars($target['nama_target'], ENT_QUOTES) ?>">
                                        + Tambah Setoran
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="../assets/js/savings.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$batches = db()->query('
    SELECT sb.id, sb.batch_code, sb.qty_remaining, sb.cost_per_unit, sb.expiry_date, p.name AS product_name, p.sku, p.unit
    FROM stock_batches sb
    JOIN products p ON sb.product_id = p.id
    WHERE sb.qty_remaining > 0
    ORDER BY p.name ASC, sb.expiry_date ASC
')->fetchAll();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $batchId = (int) ($_POST['batch_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 0);
    $movementType = trim((string) ($_POST['movement_type'] ?? 'ADJUSTMENT_OUT'));
    $reason = trim((string) ($_POST['reason'] ?? ''));

    $validTypes = ['ADJUSTMENT_OUT', 'EXPIRED', 'OUT'];
    if (!in_array($movementType, $validTypes, true)) {
        $movementType = 'ADJUSTMENT_OUT';
    }

    if ($batchId <= 0) {
        $errorMessage = 'Pilih batch stok yang akan disesuaikan.';
    } elseif ($qty <= 0) {
        $errorMessage = 'Jumlah penyesuaian harus lebih dari 0.';
    } elseif ($reason === '') {
        $errorMessage = 'Alasan penyesuaian stok wajib diisi (misal: barang rusak, selisih opname).';
    } else {
        $connection = db();
        try {
            $connection->beginTransaction();

            // Kunci baris batch
            $stmt = $connection->prepare('
                SELECT sb.id, sb.product_id, sb.batch_code, sb.qty_remaining, sb.cost_per_unit, p.name AS product_name
                FROM stock_batches sb
                JOIN products p ON sb.product_id = p.id
                WHERE sb.id = :id FOR UPDATE
            ');
            $stmt->execute(['id' => $batchId]);
            $batch = $stmt->fetch();

            if (!$batch) {
                $errorMessage = 'Batch stok tidak ditemukan.';
                $connection->rollBack();
            } elseif ($qty > (int) $batch['qty_remaining']) {
                $errorMessage = "Jumlah penyesuaian ({$qty}) melebihi stok yang tersisa di batch ini ({$batch['qty_remaining']}).";
                $connection->rollBack();
            } else {
                // Kurangi stok batch
                $stmtUpdate = $connection->prepare('
                    UPDATE stock_batches 
                    SET qty_remaining = qty_remaining - :qty 
                    WHERE id = :id
                ');
                $stmtUpdate->execute(['qty' => $qty, 'id' => $batchId]);

                // Catat mutasi
                $stmtMov = $connection->prepare('
                    INSERT INTO inventory_movements (
                        product_id, batch_id, user_id, movement_type, quantity, 
                        unit_cost, reason, occurred_at
                    ) VALUES (
                        :product_id, :batch_id, :user_id, :movement_type, :quantity,
                        :unit_cost, :reason, NOW()
                    )
                ');
                $stmtMov->execute([
                    'product_id' => $batch['product_id'],
                    'batch_id' => $batchId,
                    'user_id' => current_user()['id'],
                    'movement_type' => $movementType,
                    'quantity' => $qty,
                    'unit_cost' => $batch['cost_per_unit'],
                    'reason' => "[{$movementType}] {$reason} (Batch: {$batch['batch_code']})",
                ]);

                $connection->commit();

                flash('success', "Penyesuaian stok produk '{$batch['product_name']}' sebanyak {$qty} unit berhasil dicatat.");
                redirect('inventory/index.php');
            }
        } catch (PDOException $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('Error adjusting stock: ' . $e->getMessage());
            $errorMessage = 'Gagal memproses penyesuaian stok.';
        }
    }
}

$pageTitle = 'Penyesuaian Stok (Koreksi / Barang Rusak)';
$activePage = 'inventory';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel narrow-page" style="max-width: 650px; margin: 0 auto;">
    <div class="panel-header">
        <div>
            <h2>Penyesuaian Stok Persediaan</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Catat pengurangan stok non-penjualan (rusak, hilang, expired, atau koreksi opname).</p>
        </div>
        <a href="<?= e(app_url('inventory/index.php')) ?>" class="button button-secondary button-sm">Kembali</a>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label for="batch_id">Pilih Batch Produk *</label>
            <select id="batch_id" name="batch_id" required>
                <option value="">-- Pilih Batch Stok Tersedia --</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= (int) ($_POST['batch_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>>
                        <?= e($b['product_name']) ?> &mdash; Batch: <?= e($b['batch_code']) ?> (Sisa: <?= $b['qty_remaining'] ?> <?= e($b['unit']) ?>)
                        <?= !empty($b['expiry_date']) ? ' - Exp: ' . date('d/m/Y', strtotime($b['expiry_date'])) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="qty">Jumlah yang Dikeluarkan *</label>
                <input id="qty" name="qty" type="number" min="1" value="<?= e((string) ($_POST['qty'] ?? '1')) ?>" required>
            </div>
            <div class="form-group">
                <label for="movement_type">Kategori Pengeluaran *</label>
                <select id="movement_type" name="movement_type" required>
                    <option value="ADJUSTMENT_OUT">Penyesuaian / Selisih Opname</option>
                    <option value="EXPIRED">Barang Kedaluwarsa</option>
                    <option value="OUT">Barang Rusak / Hilang / Pemakaian Internal</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="reason">Alasan Penyesuaian (Wajib Diisi untuk Audit) *</label>
            <textarea id="reason" name="reason" placeholder="Contoh: Kemasan sobek saat ditata di etalase, atau selisih hitung fisik bulanan." required maxlength="255"><?= e($_POST['reason'] ?? '') ?></textarea>
        </div>

        <div style="margin-top: 14px; display: flex; justify-content: flex-end; gap: 10px;">
            <a href="<?= e(app_url('inventory/index.php')) ?>" class="button button-secondary">Batal</a>
            <button type="submit" class="button button-danger">Proses Penyesuaian Stok</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>


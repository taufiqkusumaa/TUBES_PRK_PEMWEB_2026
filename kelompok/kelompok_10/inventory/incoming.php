<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$selectedProductId = (int) ($_GET['product_id'] ?? 0);
$products = db()->query('SELECT id, sku, name, unit FROM products WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
$suppliers = db()->query('SELECT id, name FROM suppliers ORDER BY name ASC')->fetchAll();

$suggestedBatchCode = 'BCH-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $productId = (int) ($_POST['product_id'] ?? 0);
    $supplierId = !empty($_POST['supplier_id']) ? (int) $_POST['supplier_id'] : null;
    $batchCode = trim((string) ($_POST['batch_code'] ?? ''));
    $qty = (int) ($_POST['qty'] ?? 0);
    $costPerUnit = (float) ($_POST['cost_per_unit'] ?? 0);
    $expiryDate = !empty($_POST['expiry_date']) ? trim((string) $_POST['expiry_date']) : null;

    if ($productId <= 0) {
        $errorMessage = 'Pilih produk yang valid.';
    } elseif ($qty <= 0) {
        $errorMessage = 'Jumlah stok masuk harus lebih dari 0.';
    } elseif ($costPerUnit < 0) {
        $errorMessage = 'Harga pokok per unit (HPP) tidak boleh negatif.';
    } elseif ($batchCode === '') {
        $errorMessage = 'Kode batch tidak boleh kosong.';
    } else {
        $connection = db();
        try {
            $connection->beginTransaction();

            // 1. Simpan ke stock_batches
            $stmtBatch = $connection->prepare('
                INSERT INTO stock_batches (
                    product_id, supplier_id, batch_code, received_at, expiry_date, 
                    qty_received, qty_remaining, cost_per_unit, created_by
                ) VALUES (
                    :product_id, :supplier_id, :batch_code, NOW(), :expiry_date,
                    :qty_received, :qty_remaining, :cost_per_unit, :created_by
                )
            ');
            $stmtBatch->execute([
                'product_id' => $productId,
                'supplier_id' => $supplierId,
                'batch_code' => $batchCode,
                'expiry_date' => $expiryDate,
                'qty_received' => $qty,
                'qty_remaining' => $qty,
                'cost_per_unit' => $costPerUnit,
                'created_by' => current_user()['id'],
            ]);
            $batchId = (int) $connection->lastInsertId();

            // 2. Simpan catatan ke inventory_movements (Audit Trail)
            $stmtMov = $connection->prepare('
                INSERT INTO inventory_movements (
                    product_id, batch_id, user_id, movement_type, quantity, 
                    unit_cost, reason, occurred_at
                ) VALUES (
                    :product_id, :batch_id, :user_id, "IN", :quantity,
                    :unit_cost, :reason, NOW()
                )
            ');
            $stmtMov->execute([
                'product_id' => $productId,
                'batch_id' => $batchId,
                'user_id' => current_user()['id'],
                'quantity' => $qty,
                'unit_cost' => $costPerUnit,
                'reason' => "Penerimaan stok batch {$batchCode}",
            ]);

            $connection->commit();

            flash('success', "Stok masuk sebanyak {$qty} unit untuk batch {$batchCode} berhasil dicatat.");
            redirect('inventory/index.php');
        } catch (PDOException $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('Error incoming stock: ' . $e->getMessage());
            $errorMessage = 'Gagal mencatat stok masuk ke dalam sistem.';
        }
    }
}

$pageTitle = 'Catat Stok Masuk (Batch Baru)';
$activePage = 'incoming';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel narrow-page" style="max-width: 650px; margin: 0 auto;">
    <div class="panel-header">
        <div>
            <h2>Catat Stok Masuk / Penerimaan Barang</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Mencatat batch persediaan baru dan harga pokok pembelian.</p>
        </div>
        <a href="<?= e(app_url('inventory/index.php')) ?>" class="button button-secondary button-sm">Kembali</a>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label for="product_id">Pilih Produk *</label>
            <select id="product_id" name="product_id" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= ($selectedProductId === (int) $p['id'] || (int) ($_POST['product_id'] ?? 0) === (int) $p['id']) ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> (SKU: <?= e($p['sku']) ?> - Satuan: <?= e($p['unit']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="batch_code">Kode Batch *</label>
                <input id="batch_code" name="batch_code" value="<?= e($_POST['batch_code'] ?? $suggestedBatchCode) ?>" required maxlength="80" style="font-family: monospace;">
                <span class="input-hint">Identitas lot/batch barang dari pabrik atau gudang.</span>
            </div>
            <div class="form-group">
                <label for="supplier_id">Pemasok / Supplier (Opsional)</label>
                <select id="supplier_id" name="supplier_id">
                    <option value="">-- Tanpa Supplier / Pembelian Umum --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($_POST['supplier_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="qty">Jumlah Masuk (Kuantitas) *</label>
                <input id="qty" name="qty" type="number" min="1" value="<?= e((string) ($_POST['qty'] ?? '')) ?>" placeholder="Contoh: 50" required>
            </div>
            <div class="form-group">
                <label for="cost_per_unit">Harga Pokok per Unit (HPP) (Rp) *</label>
                <input id="cost_per_unit" name="cost_per_unit" type="number" min="0" step="100" value="<?= e((string) ($_POST['cost_per_unit'] ?? '')) ?>" placeholder="Contoh: 12000" required>
                <span class="input-hint">Harga beli modal untuk perhitungan laba kotor.</span>
            </div>
        </div>

        <div class="form-group">
            <label for="expiry_date">Tanggal Kedaluwarsa / Expired (Opsional)</label>
            <input id="expiry_date" name="expiry_date" type="date" value="<?= e($_POST['expiry_date'] ?? '') ?>">
            <span class="input-hint">Digunakan oleh sistem alokasi penjualan otomatis (FEFO: First Expired, First Out). Kosongkan jika bukan barang berumur simpan.</span>
        </div>

        <div style="margin-top: 14px; display: flex; justify-content: flex-end; gap: 10px;">
            <a href="<?= e(app_url('inventory/index.php')) ?>" class="button button-secondary">Batal</a>
            <button type="submit" class="button button-primary">Simpan Stok Masuk</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>


<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'ID transaksi tidak valid.');
    redirect('sales/history.php');
}

$stmtSale = db()->prepare('
    SELECT s.*, u.full_name AS cashier_name 
    FROM sales s
    LEFT JOIN users u ON s.cashier_id = u.id
    WHERE s.id = :id
');
$stmtSale->execute(['id' => $id]);
$sale = $stmtSale->fetch();

if (!$sale) {
    flash('error', 'Data invoice tidak ditemukan.');
    redirect('sales/history.php');
}

$stmtItems = db()->prepare('
    SELECT si.*, p.name AS product_name, p.sku, p.unit, sb.batch_code
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    LEFT JOIN stock_batches sb ON si.batch_id = sb.id
    WHERE si.sale_id = :id
    ORDER BY si.id ASC
');
$stmtItems->execute(['id' => $id]);
$items = $stmtItems->fetchAll();

$pageTitle = 'Struk Pembayaran: ' . $sale['invoice_no'];
$activePage = 'pos';
require __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 480px; margin: 0 auto;">
    <!-- Action Bar (Hanya tampil di layar, tidak ikut ter-print) -->
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <a href="<?= e(app_url('sales/pos.php')) ?>" class="button button-secondary button-sm">&larr; Transaksi Baru</a>
        <div style="display: flex; gap: 8px;">
            <a href="<?= e(app_url('sales/history.php')) ?>" class="button button-secondary button-sm">Daftar Transaksi</a>
            <button type="button" onclick="window.print()" class="button button-primary button-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Cetak Struk
            </button>
        </div>
    </div>

    <!-- Thermal Receipt Card -->
    <div class="panel receipt-box" style="padding: 32px 28px; background: #fff; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
        <!-- Header Struk -->
        <div style="text-align: center; border-bottom: 1px dashed #cbd5e1; padding-bottom: 16px; margin-bottom: 16px;">
            <h2 style="font-size: 20px; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em;">SISASENSE POS</h2>
            <p style="font-size: 11px; color: #64748b; margin: 0;">Sistem Manajemen Persediaan & Kasir UMKM</p>
            <p style="font-size: 11px; color: #64748b; margin: 2px 0 0;">Telp: (0721) 701605 · Bandar Lampung</p>
        </div>

        <!-- Info Invoice -->
        <div style="font-size: 12px; line-height: 1.6; margin-bottom: 16px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px;">
            <div style="display: flex; justify-content: space-between;">
                <span>No. Invoice:</span>
                <strong><?= e($sale['invoice_no']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Waktu:</span>
                <span><?= date('d/m/Y H:i:s', strtotime($sale['sold_at'])) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Kasir:</span>
                <span><?= e($sale['cashier_name'] ?? 'Kasir') ?></span>
            </div>
            <?php
            $methodLabels = [
                'cash' => 'Tunai (Cash)',
                'qris' => 'QRIS (Bank Indonesia)',
                'transfer' => 'Transfer Bank',
                'debit' => 'Kartu Debit / EDC'
            ];
            $curMethod = $sale['payment_method'] ?? 'cash';
            $methodText = $methodLabels[$curMethod] ?? strtoupper($curMethod);
            ?>
            <div style="display: flex; justify-content: space-between;">
                <span>Metode:</span>
                <strong><?= e($methodText) ?></strong>
            </div>
            <?php if (!empty($sale['notes'])): ?>
                <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b;">
                    <span>Catatan:</span>
                    <span><?= e($sale['notes']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabel Item -->
        <table style="width: 100%; font-size: 12px; border-collapse: collapse; margin-bottom: 16px;">
            <tbody>
                <?php foreach ($items as $item): 
                    $qty = (int) $item['qty'];
                    $price = (float) $item['unit_price_snapshot'];
                    $lineTotal = (float) $item['line_total'];
                ?>
                    <tr>
                        <td colspan="2" style="padding-top: 6px; font-weight: 700;">
                            <?= e($item['product_name']) ?>
                            <?php if (!empty($item['batch_code'])): ?>
                                <small style="display: block; font-weight: 400; color: #64748b; font-size: 10px;">Batch: <?= e($item['batch_code']) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px dotted #e2e8f0;">
                        <td style="padding-bottom: 6px; color: #64748b;">
                            <?= $qty ?> <?= e($item['unit']) ?> &times; Rp <?= number_format($price, 0, ',', '.') ?>
                        </td>
                        <td style="text-align: right; padding-bottom: 6px; font-weight: 700;">
                            Rp <?= number_format($lineTotal, 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Ringkasan Angka Pembayaran -->
        <div style="font-size: 12px; line-height: 1.8; border-top: 1px dashed #cbd5e1; padding-top: 12px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Subtotal:</span>
                <span>Rp <?= number_format((float) $sale['subtotal'], 0, ',', '.') ?></span>
            </div>
            <?php if ((float) $sale['discount_total'] > 0): ?>
                <div style="display: flex; justify-content: space-between; color: var(--danger);">
                    <span>Diskon:</span>
                    <span>-Rp <?= number_format((float) $sale['discount_total'], 0, ',', '.') ?></span>
                </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 800; border-top: 1px solid #000; padding-top: 6px; margin-top: 4px;">
                <span>TOTAL:</span>
                <span>Rp <?= number_format((float) $sale['grand_total'], 0, ',', '.') ?></span>
            </div>
            <?php if ($curMethod === 'cash'): ?>
                <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                    <span>Bayar Tunai:</span>
                    <span>Rp <?= number_format((float) $sale['amount_paid'], 0, ',', '.') ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: 700;">
                    <span>Kembalian:</span>
                    <span>Rp <?= number_format((float) $sale['change_amount'], 0, ',', '.') ?></span>
                </div>
            <?php else: ?>
                <div style="display: flex; justify-content: space-between; margin-top: 4px; color: #047857; font-weight: 700;">
                    <span>Status:</span>
                    <span>LUNAS (<?= e($methodText) ?>)</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Struk -->
        <div style="text-align: center; margin-top: 24px; padding-top: 16px; border-top: 1px dashed #cbd5e1; font-size: 11px; color: #64748b;">
            <p style="margin: 0 0 2px;">Terima kasih telah berbelanja di toko kami!</p>
            <p style="margin: 0;">Barang yang sudah dibeli tidak dapat ditukar kecuali perjanjian khusus.</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

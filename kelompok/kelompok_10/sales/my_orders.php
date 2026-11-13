<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$user = current_user();
$userId = (int) $user['id'];
$isCustomer = $user['role'] === 'customer';

$myOrders = [];
try {
    if ($isCustomer) {
        $stmt = db()->prepare("
            SELECT s.*, u.full_name AS cashier_name, COUNT(si.id) AS item_count
            FROM sales s
            LEFT JOIN users u ON s.cashier_id = u.id
            LEFT JOIN sale_items si ON s.id = si.sale_id
            WHERE s.customer_id = :uid OR s.notes LIKE :user_search
            GROUP BY s.id
            ORDER BY s.sold_at DESC
        ");
        $stmt->execute([
            'uid' => $userId,
            'user_search' => '%' . $user['username'] . '%'
        ]);
        $myOrders = $stmt->fetchAll();
    } else {
        // Jika admin/kasir membuka halaman ini, tampilkan transaksi terkait
        $myOrders = db()->query("
            SELECT s.*, u.full_name AS cashier_name, COUNT(si.id) AS item_count
            FROM sales s
            LEFT JOIN users u ON s.cashier_id = u.id
            LEFT JOIN sale_items si ON s.id = si.sale_id
            GROUP BY s.id
            ORDER BY s.sold_at DESC
            LIMIT 50
        ")->fetchAll();
    }
} catch (PDOException $e) {
    error_log('Error loading customer orders: ' . $e->getMessage());
    flash('error', 'Gagal memuat riwayat belanja.');
}

$pageTitle = 'Riwayat Belanja Saya';
$activePage = 'my_orders';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Riwayat Belanja & Struk Pelanggan</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Daftar transaksi belanja yang pernah Anda lakukan di toko SisaSense.</p>
        </div>
        <a href="<?= e(app_url('products/index.php')) ?>" class="button button-primary button-sm">Katalog Produk &rarr;</a>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Waktu Pembelian</th>
                    <th>Kasir yang Melayani</th>
                    <th class="col-num">Banyak Item</th>
                    <th class="col-num">Total Belanja</th>
                    <th>Status</th>
                    <th style="text-align: right;">Struk</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($myOrders)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--ink-muted);">
                            Belum ada riwayat transaksi belanja. <a href="<?= e(app_url('products/index.php')) ?>">Mulai lihat katalog produk</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($myOrders as $order): ?>
                        <tr>
                            <td class="col-code">
                                <a href="<?= e(app_url('sales/receipt.php?id=' . $order['id'])) ?>" style="font-weight: 700;">
                                    <?= e($order['invoice_no']) ?>
                                </a>
                            </td>
                            <td style="font-size: 12px; color: #475569;">
                                <?= date('d M Y, H:i', strtotime($order['sold_at'])) ?> WIB
                            </td>
                            <td><?= e($order['cashier_name'] ?? 'Kasir Toko') ?></td>
                            <td class="col-num"><?= (int) $order['item_count'] ?> jenis</td>
                            <td class="col-num"><strong>Rp <?= number_format((float) $order['grand_total'], 0, ',', '.') ?></strong></td>
                            <td><span class="badge badge-success"><span class="badge-dot"></span>Selesai</span></td>
                            <td style="text-align: right;">
                                <a href="<?= e(app_url('sales/receipt.php?id=' . $order['id'])) ?>" class="button button-secondary button-sm">Lihat Struk</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>


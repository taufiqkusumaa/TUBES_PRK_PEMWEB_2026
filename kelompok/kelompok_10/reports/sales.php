<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/nav.php';
require_login();

$user = current_user();
$isAdmin = $user['role'] === 'admin';

$pageTitle = 'Laporan Penjualan';
$activePage = 'sales';

$navItems = reports_nav_items($isAdmin);

$pdo = db();

$startDate = is_string($_GET['start_date'] ?? null) ? trim($_GET['start_date']) : '';
$endDate = is_string($_GET['end_date'] ?? null) ? trim($_GET['end_date']) : '';
$invoice = is_string($_GET['invoice'] ?? null) ? trim($_GET['invoice']) : '';

// Filter kasir hanya untuk admin; kasir selalu dibatasi ke transaksinya sendiri.
$cashierFilter = $isAdmin ? filter_input(INPUT_GET, 'cashier_id', FILTER_VALIDATE_INT) : null;
$cashierFilter = ($cashierFilter !== false && $cashierFilter !== null && $cashierFilter > 0)
    ? $cashierFilter
    : null;

$cashiers = [];

if ($isAdmin) {
    $cashiers = $pdo->query("
        SELECT DISTINCT u.id, u.full_name
        FROM users u
        JOIN sales s ON s.cashier_id = u.id
        ORDER BY u.full_name ASC
    ")->fetchAll();
}

$errors = [];

$validDate = static function (string $value): bool {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

if ($startDate !== '' && !$validDate($startDate)) {
    $errors[] = 'Tanggal awal tidak valid.';
}

if ($endDate !== '' && !$validDate($endDate)) {
    $errors[] = 'Tanggal akhir tidak valid.';
}

if (
    empty($errors) &&
    $startDate !== '' &&
    $endDate !== '' &&
    $startDate > $endDate
) {
    $errors[] = 'Tanggal awal tidak boleh melebihi tanggal akhir.';
}

$where = ["s.status = 'completed'"];
$params = [];

if (empty($errors)) {
    if ($startDate !== '') {
        $where[] = 's.sold_at >= :start_date';
        $params['start_date'] = $startDate . ' 00:00:00';
    }

    if ($endDate !== '') {
        $where[] = 's.sold_at < :end_date';
        $params['end_date'] = (new DateTimeImmutable($endDate))
            ->modify('+1 day')
            ->format('Y-m-d') . ' 00:00:00';
    }

    if ($invoice !== '') {
        $where[] = 's.invoice_no LIKE :invoice';
        $params['invoice'] = '%' . $invoice . '%';
    }
}

if (!$isAdmin) {
    $where[] = 's.cashier_id = :cashier_id';
    $params['cashier_id'] = (int) $user['id'];
} elseif ($cashierFilter !== null) {
    $where[] = 's.cashier_id = :filter_cashier';
    $params['filter_cashier'] = $cashierFilter;
}

$sales = [];

if (empty($errors)) {
    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.invoice_no,
            s.sold_at,
            s.grand_total,
            s.payment_method,
            u.full_name AS cashier_name
        FROM sales s
        JOIN users u ON u.id = s.cashier_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.sold_at DESC
    ");

    $stmt->execute($params);
    $sales = $stmt->fetchAll();
}

$exportUrl = app_url('reports/export-sales.php') . '?' . http_build_query([
    'start_date' => $startDate,
    'end_date' => $endDate,
    'invoice' => $invoice,
    'cashier_id' => $cashierFilter ?? '',
]);

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>">

<section class="reports-container">
    <h1>Laporan Penjualan</h1>

    <p class="reports-description">
        Daftar transaksi penjualan yang telah selesai.
    </p>

    <section class="analytics-section">
        <h2>Filter Penjualan</h2>

        <form method="GET" action="<?= e(app_url('reports/sales.php')) ?>">
            <div class="report-filters">
                <div>
                    <label for="start_date">Tanggal Awal</label>
                    <input type="date" id="start_date" name="start_date"
                        value="<?= e($startDate) ?>">
                </div>

                <div>
                    <label for="end_date">Tanggal Akhir</label>
                    <input type="date" id="end_date" name="end_date"
                        value="<?= e($endDate) ?>">
                </div>

                <?php if ($isAdmin): ?>
                    <div>
                        <label for="cashier_id">Kasir</label>
                        <select id="cashier_id" name="cashier_id">
                            <option value="">Semua kasir</option>
                            <?php foreach ($cashiers as $cashier): ?>
                                <option value="<?= (int) $cashier['id'] ?>"
                                    <?= $cashierFilter === (int) $cashier['id'] ? 'selected' : '' ?>>
                                    <?= e($cashier['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="invoice">Nomor Invoice</label>
                    <input type="text" id="invoice" name="invoice"
                        placeholder="Cari nomor invoice"
                        value="<?= e($invoice) ?>">
                </div>

                <button type="submit">Terapkan Filter</button>
                <a href="<?= e(app_url('reports/sales.php')) ?>">Reset</a>

                <?php if (empty($errors)): ?>
                    <a href="<?= e($exportUrl) ?>">Export CSV</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="analytics-section">
        <h2>Data Penjualan</h2>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <p class="report-error"><?= e($error) ?></p>
            <?php endforeach; ?>
        <?php elseif (empty($sales)): ?>
            <p>Belum ada transaksi penjualan yang sesuai.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Invoice</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Metode Pembayaran</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $index => $sale): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($sale['invoice_no']) ?></td>
                                <td><?= e(date('d-m-Y H:i', strtotime($sale['sold_at']))) ?></td>
                                <td><?= e($sale['cashier_name']) ?></td>
                                <td><?= e($sale['payment_method']) ?></td>
                                <td>
                                    Rp <?= number_format((float) $sale['grand_total'], 0, ',', '.') ?>
                                </td>
                                <td>
                                    <a href="<?= e(app_url('reports/sales-detail.php?id=' . (int) $sale['id'])) ?>">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
declare(strict_types=1);

/**
 * SisaSense - Anggota 4: Dashboard dan Analisis Penjualan
 *
 * Modul ini hanya membaca data. Tidak membuat salinan transaksi dan tidak
 * mengubah produk, stok, batch, maupun transaksi.
 *
 * Skema acuan:
 * products(id, sku, name, unit, selling_price, min_stock, is_active)
 * stock_batches(id, product_id, expiry_date, qty_remaining, ...)
 * sales(id, sold_at, grand_total, status)
 * sale_items(id, sale_id, product_id, qty, cost_price_snapshot, line_total)
 * inventory_movements(...)
 */

/** Memuat file koneksi/autentikasi Anggota 1 bila tersedia. */
function ss4_load_shared_files(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $root = dirname(__DIR__);

    foreach ([$root . '/config/database.php', $root . '/database.php'] as $file) {
        if (!is_file($file)) {
            continue;
        }

        // Include dalam scope fungsi, lalu pindahkan PDO lokal yang umum ke global.
        require_once $file;
        foreach (['pdo', 'db', 'conn', 'connection'] as $key) {
            if (isset($GLOBALS[$key]) && $GLOBALS[$key] instanceof PDO) {
                $GLOBALS['ss4_pdo'] = $GLOBALS[$key];
                break 2;
            }
            if (isset($$key) && $$key instanceof PDO) {
                $GLOBALS['ss4_pdo'] = $$key;
                break 2;
            }
        }
        break;
    }

    foreach ([$root . '/config/auth.php', $root . '/auth.php'] as $file) {
        if (is_file($file)) {
            require_once $file;
            break;
        }
    }

    $loaded = true;
}

/** Mendapatkan PDO dari helper/variabel koneksi yang disediakan Anggota 1. */
function ss4_db(): PDO
{
    ss4_load_shared_files();

    if (isset($GLOBALS['ss4_pdo']) && $GLOBALS['ss4_pdo'] instanceof PDO) {
        return $GLOBALS['ss4_pdo'];
    }

    foreach (['pdo', 'db', 'conn', 'connection'] as $key) {
        if (isset($GLOBALS[$key]) && $GLOBALS[$key] instanceof PDO) {
            return $GLOBALS[$key];
        }
    }

    foreach (['db', 'getPDO', 'get_pdo'] as $function) {
        if (function_exists($function)) {
            $connection = $function();
            if ($connection instanceof PDO) {
                return $connection;
            }
        }
    }

    throw new RuntimeException(
        'Koneksi PDO tidak ditemukan. Sesuaikan ss4_db() dengan helper pada config/database.php Anggota 1.'
    );
}

/**
 * Memastikan pengguna sudah login.
 * Nama helper disesuaikan secara defensif dengan beberapa pola umum.
 */
function ss4_require_login(): void
{
    ss4_load_shared_files();

    if (function_exists('require_login')) {
        require_login();
        return;
    }

    if (function_exists('check_login')) {
        check_login();
        return;
    }

    $user = ss4_current_user();
    if ($user === null) {
        http_response_code(401);
        echo 'Silakan login terlebih dahulu.';
        exit;
    }
}

/** Membaca pengguna aktif bila helper current_user tersedia. */
function ss4_current_user()
{
    ss4_load_shared_files();

    if (function_exists('current_user')) {
        $user = current_user();
        if (is_array($user) || is_object($user)) {
            return $user;
        }
    }

    foreach (['user', 'current_user', 'auth_user'] as $key) {
        if (isset($_SESSION[$key]) && (is_array($_SESSION[$key]) || is_object($_SESSION[$key]))) {
            return $_SESSION[$key];
        }
    }

    return null;
}

function ss4_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ss4_rupiah($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

/** Validasi tanggal YYYY-MM-DD dan mencegah tanggal tidak valid. */
function ss4_valid_date(?string $value, string $fallback): string
{
    if ($value === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $fallback;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $value : $fallback;
}

/** Ringkasan dashboard; hanya sales.status = completed. */
function ss4_get_summary(PDO $pdo, string $dateFrom, string $dateTo): array
{
    $sql = "
        SELECT
            COUNT(DISTINCT s.id) AS total_transactions,
            COALESCE(SUM(s.grand_total), 0) AS omzet,
            COALESCE(SUM(si.qty * si.cost_price_snapshot), 0) AS total_cogs,
            COALESCE(SUM(si.line_total), 0) AS net_item_revenue
        FROM sales s
        LEFT JOIN sale_items si ON si.sale_id = s.id
        WHERE s.status = 'completed'
          AND s.sold_at >= :date_from
          AND s.sold_at < DATE_ADD(:date_to, INTERVAL 1 DAY)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'date_from' => $dateFrom . ' 00:00:00',
        'date_to' => $dateTo,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $activeProducts = (int) $pdo->query(
        "SELECT COUNT(*) FROM products WHERE is_active = 1"
    )->fetchColumn();

    $omzet = (float) ($row['omzet'] ?? 0);
    $cogs = (float) ($row['total_cogs'] ?? 0);

    return [
        'omzet' => $omzet,
        'transactions' => (int) ($row['total_transactions'] ?? 0),
        'active_products' => $activeProducts,
        'cogs' => $cogs,
        // Laba kotor dihitung dari omzet bersih transaksi dikurangi HPP snapshot.
        'gross_profit' => $omzet - $cogs,
    ];
}

/** Tren omzet per hari sesuai filter tanggal. */
function ss4_get_sales_trend(PDO $pdo, string $dateFrom, string $dateTo): array
{
    $stmt = $pdo->prepare("
        SELECT DATE(sold_at) AS sales_date, COALESCE(SUM(grand_total), 0) AS omzet,
               COUNT(*) AS transactions
        FROM sales
        WHERE status = 'completed'
          AND sold_at >= :date_from
          AND sold_at < DATE_ADD(:date_to, INTERVAL 1 DAY)
        GROUP BY DATE(sold_at)
        ORDER BY sales_date ASC
    ");
    $stmt->execute([
        'date_from' => $dateFrom . ' 00:00:00',
        'date_to' => $dateTo,
    ]);

    $indexed = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $indexed[$row['sales_date']] = [
            'date' => $row['sales_date'],
            'omzet' => (float) $row['omzet'],
            'transactions' => (int) $row['transactions'],
        ];
    }

    // Isi tanggal tanpa transaksi dengan nilai 0 agar grafik tidak melompat.
    $result = [];
    $start = new DateTimeImmutable($dateFrom);
    $end = new DateTimeImmutable($dateTo);
    for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
        $key = $day->format('Y-m-d');
        $result[] = $indexed[$key] ?? [
            'date' => $key,
            'omzet' => 0.0,
            'transactions' => 0,
        ];
    }

    return $result;
}

/** Produk terlaris berdasarkan total kuantitas terjual dalam rentang tanggal. */
function ss4_get_best_sellers(PDO $pdo, string $dateFrom, string $dateTo, int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $sql = "
        SELECT p.id, p.sku, p.name, p.unit,
               SUM(si.qty) AS total_qty,
               SUM(si.line_total) AS total_revenue
        FROM sale_items si
        INNER JOIN sales s ON s.id = si.sale_id
        INNER JOIN products p ON p.id = si.product_id
        WHERE s.status = 'completed'
          AND s.sold_at >= :date_from
          AND s.sold_at < DATE_ADD(:date_to, INTERVAL 1 DAY)
        GROUP BY p.id, p.sku, p.name, p.unit
        ORDER BY total_qty DESC, total_revenue DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'date_from' => $dateFrom . ' 00:00:00',
        'date_to' => $dateTo,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Slow-moving: produk aktif yang tidak memiliki penjualan completed
 * selama N hari terakhir. Produk yang belum pernah terjual ikut ditampilkan.
 */
function ss4_get_slow_moving(PDO $pdo, int $thresholdDays = 30, int $limit = 20): array
{
    $thresholdDays = max(1, min(365, $thresholdDays));
    $limit = max(1, min(100, $limit));

    $sql = "
        SELECT p.id, p.sku, p.name, p.unit,
               MAX(s.sold_at) AS last_sold_at,
               COALESCE(SUM(
                   CASE
                       WHEN s.sold_at >= DATE_SUB(NOW(), INTERVAL {$thresholdDays} DAY)
                       THEN si.qty ELSE 0
                   END
               ), 0) AS qty_sold_in_period
        FROM products p
        LEFT JOIN sale_items si ON si.product_id = p.id
        LEFT JOIN sales s ON s.id = si.sale_id AND s.status = 'completed'
        WHERE p.is_active = 1
        GROUP BY p.id, p.sku, p.name, p.unit
        HAVING last_sold_at IS NULL
            OR last_sold_at < DATE_SUB(NOW(), INTERVAL {$thresholdDays} DAY)
        ORDER BY last_sold_at ASC
        LIMIT {$limit}
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/** Peringatan produk yang stok validnya <= min_stock. */
function ss4_get_low_stock(PDO $pdo): array
{
    $sql = "
        SELECT p.id, p.sku, p.name, p.unit, p.min_stock,
               COALESCE(b.available_stock, 0) AS available_stock
        FROM products p
        LEFT JOIN (
            SELECT product_id, SUM(qty_remaining) AS available_stock
            FROM stock_batches
            WHERE qty_remaining > 0
              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
            GROUP BY product_id
        ) b ON b.product_id = p.id
        WHERE p.is_active = 1
          AND COALESCE(b.available_stock, 0) <= p.min_stock
        ORDER BY available_stock ASC, p.name ASC
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/** Batch dengan sisa stok dan kedaluwarsa dalam 30 hari ke depan. */
function ss4_get_expiring_batches(PDO $pdo, int $daysAhead = 30): array
{
    $daysAhead = max(1, min(365, $daysAhead));
    $sql = "
        SELECT sb.id AS batch_id, sb.batch_code, sb.expiry_date,
               sb.qty_remaining, p.sku, p.name AS product_name, p.unit
        FROM stock_batches sb
        INNER JOIN products p ON p.id = sb.product_id
        WHERE p.is_active = 1
          AND sb.qty_remaining > 0
          AND sb.expiry_date IS NOT NULL
          AND sb.expiry_date BETWEEN CURDATE()
              AND DATE_ADD(CURDATE(), INTERVAL {$daysAhead} DAY)
        ORDER BY sb.expiry_date ASC, p.name ASC
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

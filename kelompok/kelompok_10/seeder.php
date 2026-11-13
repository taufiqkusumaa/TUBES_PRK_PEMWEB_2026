<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * SisaSense - Database Web & CLI Seeder (Khusus InfinityFree & Local Development)
 * ==============================================================================
 * 
 * Penggunaan:
 * 1. Via Browser: Buka URL http://<domain>/seeder.php atau http://<domain>/kelompok/kelompok_10/seeder.php
 * 2. Via CLI    : Jalankan command 'php seeder.php'
 * 
 * Akun Default yang Dibuat:
 * - Admin   : username 'admin'   | password '123' (Akses dashboard, master data, laporan)
 * - Kasir   : username 'kasir'   | password '123' (Akses Kasir POS, transaksi penjualan)
 * - Pembeli : username 'pembeli' | password '123' (Akses belanja katalog & pesanan)
 */

$isCli = (php_sapi_name() === 'cli');

// 1. Cek Koneksi Database
$dbConfigPath = __DIR__ . '/config/database.php';
if (!file_exists($dbConfigPath)) {
    die("File konfigurasi {$dbConfigPath} tidak ditemukan.");
}
require_once $dbConfigPath;

$dbConnected = false;
$dbError = '';
$pdo = null;

try {
    $pdo = db();
    $dbConnected = true;
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$action = $_GET['action'] ?? ($argv[1] ?? 'seed');
$messages = [];
$stats = [
    'users' => 0,
    'categories' => 0,
    'products' => 0,
    'stock_batches' => 0,
];

if ($dbConnected && $pdo instanceof PDO) {
    // ----------------------------------------------------
    // PEMBUATAN TABEL OTOMATIS JIKA BELUM ADA
    // ----------------------------------------------------
    try {
        $tablesSql = [
            "CREATE TABLE IF NOT EXISTS `users` (
              `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `full_name` VARCHAR(100) NOT NULL,
              `username` VARCHAR(50) NOT NULL UNIQUE,
              `email` VARCHAR(150) NOT NULL UNIQUE,
              `password_hash` VARCHAR(255) NOT NULL,
              `role` ENUM('admin','cashier','customer') NOT NULL DEFAULT 'customer',
              `is_active` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `categories` (
              `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `name` VARCHAR(100) NOT NULL UNIQUE,
              `description` VARCHAR(255) NULL,
              `is_active` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `suppliers` (
              `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `name` VARCHAR(150) NOT NULL,
              `phone` VARCHAR(30) NULL,
              `email` VARCHAR(150) NULL,
              `address` TEXT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `products` (
              `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `category_id` BIGINT UNSIGNED NOT NULL,
              `sku` VARCHAR(50) NOT NULL UNIQUE,
              `name` VARCHAR(150) NOT NULL,
              `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
              `image_url` VARCHAR(500) NULL,
              `selling_price` DECIMAL(14,2) NOT NULL,
              `min_stock` INT UNSIGNED NOT NULL DEFAULT 0,
              `is_active` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              CONSTRAINT `fk_products_category`
                FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `chk_products_selling_price` CHECK (`selling_price` >= 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `stock_batches` (
              `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `product_id` BIGINT UNSIGNED NOT NULL,
              `supplier_id` BIGINT UNSIGNED NULL,
              `batch_code` VARCHAR(80) NULL,
              `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `expiry_date` DATE NULL,
              `qty_received` INT UNSIGNED NOT NULL,
              `qty_remaining` INT UNSIGNED NOT NULL,
              `cost_per_unit` DECIMAL(14,2) NOT NULL,
              `created_by` BIGINT UNSIGNED NOT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT `fk_batches_product`
                FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `fk_batches_supplier`
                FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `fk_batches_creator`
                FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `chk_batch_qty` CHECK (`qty_remaining` <= `qty_received`),
              CONSTRAINT `chk_batch_cost` CHECK (`cost_per_unit` >= 0),
              INDEX `idx_batches_product_expiry` (`product_id`, `expiry_date`),
              INDEX `idx_batches_expiry` (`expiry_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `sales` (
              `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `invoice_no` VARCHAR(40) NOT NULL UNIQUE,
              `cashier_id` BIGINT UNSIGNED NOT NULL,
              `customer_id` BIGINT UNSIGNED NULL,
              `sold_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `subtotal` DECIMAL(14,2) NOT NULL,
              `discount_total` DECIMAL(14,2) NOT NULL DEFAULT 0,
              `grand_total` DECIMAL(14,2) NOT NULL,
              `amount_paid` DECIMAL(14,2) NOT NULL,
              `change_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
              `payment_method` ENUM('cash','transfer','qris','debit','other') NOT NULL DEFAULT 'cash',
              `status` ENUM('completed','voided') NOT NULL DEFAULT 'completed',
              `notes` VARCHAR(255) NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT `fk_sales_cashier`
                FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `fk_sales_customer`
                FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`)
                ON UPDATE CASCADE ON DELETE SET NULL,
              CONSTRAINT `chk_sales_amounts` CHECK (
                `subtotal` >= 0 AND `discount_total` >= 0 AND
                `grand_total` >= 0 AND `amount_paid` >= 0 AND `change_amount` >= 0
              ),
              INDEX `idx_sales_sold_at_status` (`sold_at`, `status`),
              INDEX `idx_sales_customer` (`customer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `sale_items` (
              `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `sale_id` BIGINT UNSIGNED NOT NULL,
              `product_id` BIGINT UNSIGNED NOT NULL,
              `batch_id` BIGINT UNSIGNED NOT NULL,
              `qty` INT UNSIGNED NOT NULL,
              `unit_price_snapshot` DECIMAL(14,2) NOT NULL,
              `cost_price_snapshot` DECIMAL(14,2) NOT NULL,
              `discount_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
              `line_total` DECIMAL(14,2) NOT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT `fk_sale_items_sale`
                FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `fk_sale_items_product`
                FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `fk_sale_items_batch`
                FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `chk_sale_items_qty` CHECK (`qty` > 0),
              CONSTRAINT `chk_sale_items_amounts` CHECK (
                `unit_price_snapshot` >= 0 AND `cost_price_snapshot` >= 0 AND
                `discount_amount` >= 0 AND `line_total` >= 0
              ),
              INDEX `idx_sale_items_product` (`product_id`),
              INDEX `idx_sale_items_sale` (`sale_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `inventory_movements` (
              `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `product_id` BIGINT UNSIGNED NOT NULL,
              `batch_id` BIGINT UNSIGNED NULL,
              `user_id` BIGINT UNSIGNED NOT NULL,
              `sale_id` BIGINT UNSIGNED NULL,
              `movement_type` ENUM(
                'IN','OUT','SALE','RETURN',
                'ADJUSTMENT_IN','ADJUSTMENT_OUT','EXPIRED'
              ) NOT NULL,
              `quantity` INT UNSIGNED NOT NULL,
              `unit_cost` DECIMAL(14,2) NULL,
              `reason` VARCHAR(255) NULL,
              `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT `fk_movements_product`
                FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `fk_movements_batch`
                FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `fk_movements_user`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `fk_movements_sale`
                FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
              CONSTRAINT `chk_movements_quantity` CHECK (`quantity` > 0),
              INDEX `idx_movements_product_date` (`product_id`, `occurred_at`),
              INDEX `idx_movements_sale` (`sale_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        ];

        foreach ($tablesSql as $tSql) {
            $pdo->exec($tSql);
        }
        $messages[] = 'Struktur 8 tabel database berhasil dipastikan / dibuat.';

        // ----------------------------------------------------
        // SEEDING 3 PENGGUNA (Admin, Kasir, Pembeli) - Password '123'
        // ----------------------------------------------------
        $demoPasswordHash = password_hash('123', PASSWORD_BCRYPT);
        $userStmt = $pdo->prepare(
            "INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password_hash`, `role`, `is_active`) 
             VALUES (:id, :name, :username, :email, :pass, :role, 1)
             ON DUPLICATE KEY UPDATE 
                `password_hash` = VALUES(`password_hash`),
                `full_name`     = VALUES(`full_name`),
                `role`          = VALUES(`role`),
                `is_active`     = 1"
        );

        $demoUsers = [
            ['id' => 1, 'name' => 'Administrator Toko', 'username' => 'admin', 'email' => 'admin@sisasense.id', 'role' => 'admin'],
            ['id' => 2, 'name' => 'Budi Kasir', 'username' => 'kasir', 'email' => 'kasir@sisasense.id', 'role' => 'cashier'],
            ['id' => 3, 'name' => 'Siti Pembeli', 'username' => 'pembeli', 'email' => 'pembeli@sisasense.id', 'role' => 'customer'],
        ];

        foreach ($demoUsers as $du) {
            $userStmt->execute([
                'id'       => $du['id'],
                'name'     => $du['name'],
                'username' => $du['username'],
                'email'    => $du['email'],
                'pass'     => $demoPasswordHash,
                'role'     => $du['role'],
            ]);
        }
        $stats['users'] = (int) $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        $messages[] = '3 Akun Demo (admin, kasir, pembeli) berhasil disiapkan dengan kata sandi "123".';

        // ----------------------------------------------------
        // SEEDING 5 KATEGORI
        // ----------------------------------------------------
        $categories = [
            [1, 'Makanan & Snack', 'Camilan ringan, biskuit, dan makanan siap santap'],
            [2, 'Minuman Segar', 'Kopi, teh, susu, dan minuman kemasan dingin'],
            [3, 'Sembako & Pokok', 'Beras, minyak goreng, gula, dan bumbu dapur'],
            [4, 'Oleh-Oleh & Tradisional', 'Makanan khas daerah, kue tradisional, dan oleh-oleh nusantara'],
            [5, 'Bumbu Dapur & Sambal', 'Sambal rumahan, bumbu rempah, dan saus khas UMKM'],
        ];

        $catStmt = $pdo->prepare(
            "INSERT INTO `categories` (`id`, `name`, `description`, `is_active`) 
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`)"
        );
        foreach ($categories as $cat) {
            $catStmt->execute($cat);
        }
        $stats['categories'] = (int) $pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
        $messages[] = "{$stats['categories']} Kategori produk siap digunakan.";

        // ----------------------------------------------------
        // SEEDING 24 PRODUK LENGKAP DENGAN FOTO REAL
        // ----------------------------------------------------
        $products = [
            [1, 1, 'SNK-001', 'Keripik Singkong Balado 150g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/84/Keripik_singkong_balado_cassava_chips.JPG/500px-Keripik_singkong_balado_cassava_chips.JPG', 12000.00, 5],
            [2, 2, 'MNM-001', 'Kopi Susu Gula Aren 250ml', 'botol', 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=500&auto=format&fit=crop&q=80', 15000.00, 10],
            [3, 3, 'SBK-001', 'Beras Ramos Super 5kg', 'karung', 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80', 68000.00, 4],
            [4, 2, 'MNM-002', 'Teh Melati Melur Kotak 200ml', 'kotak', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=500&auto=format&fit=crop&q=80', 4500.00, 8],
            [5, 1, 'SNK-002', 'Croissant Butter French', 'pcs', 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500&auto=format&fit=crop&q=80', 18000.00, 5],
            [6, 1, 'SNK-003', 'Biskuit Cokelat Lava 120g', 'pack', 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=500&auto=format&fit=crop&q=80', 14500.00, 8],
            [7, 1, 'SNK-004', 'Roti Tawar Gandum Premium', 'loaf', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&auto=format&fit=crop&q=80', 22000.00, 4],
            [8, 2, 'MNM-003', 'Matcha Latte Creamy 250ml', 'cup', 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?w=500&auto=format&fit=crop&q=80', 20000.00, 6],
            [9, 2, 'MNM-004', 'Kopi Espresso Robusta 100g', 'pouch', 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=500&auto=format&fit=crop&q=80', 35000.00, 5],
            [10, 2, 'MNM-005', 'Susu UHT Full Cream 1 Liter', 'kotak', 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=500&auto=format&fit=crop&q=80', 21000.00, 10],
            [11, 3, 'SBK-002', 'Minyak Goreng Sawit Murni 2L', 'pouch', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c7/Cooking_oil.jpg/500px-Cooking_oil.jpg', 36000.00, 8],
            [12, 3, 'SBK-003', 'Gula Pasir Tebu Kristal 1kg', 'kg', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/05/Sugar_White.jpg/500px-Sugar_White.jpg', 17500.00, 12],
            [13, 4, 'OLH-001', 'Dodol Garut Asli Wijen 250g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/f9/Dodol_Garut_Cihampelas_Bandung.JPG/500px-Dodol_Garut_Cihampelas_Bandung.JPG', 25000.00, 6],
            [14, 4, 'OLH-002', 'Kue Lapis Legit Spekuk Premium', 'loyang', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/db/Kue_Lapis_Legit.JPG/500px-Kue_Lapis_Legit.JPG', 85000.00, 3],
            [15, 5, 'BMB-001', 'Sambal Bawang Bu Rudy Khas Surabaya', 'jar', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/5a/SAMBAL_BAWANG.jpg/500px-SAMBAL_BAWANG.jpg', 32000.00, 8],
            [16, 4, 'OLH-003', 'Abon Sapi Gurih Manis Boyolali 150g', 'jar', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/57/Abon_sapi.JPG/500px-Abon_sapi.JPG', 45000.00, 5],
            [17, 1, 'SNK-005', 'Emping Melinjo Super Renyah 250g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/08/Emping_blinjo_Sidoarjo.jpg/500px-Emping_blinjo_Sidoarjo.jpg', 28000.00, 8],
            [18, 1, 'SNK-006', 'Kacang Mete Panggang Wonogiri 200g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/fd/Cashews_1314.jpg/500px-Cashews_1314.jpg', 55000.00, 4],
            [19, 2, 'MNM-006', 'Jamu Kunyit Asam Segar 350ml', 'botol', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/cd/Miunman_Es_Kunyit_Asam.jpg/500px-Miunman_Es_Kunyit_Asam.jpg', 12000.00, 10],
            [20, 2, 'MNM-007', 'Sirup Markisa Asli Brastagi 500ml', 'botol', 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=500&auto=format&fit=crop&q=80', 35000.00, 6],
            [21, 5, 'BMB-002', 'Bumbu Rendang Padang Instan 100g', 'sachet', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/7/70/Rendang_daging_sapi_asli_Padang.JPG/500px-Rendang_daging_sapi_asli_Padang.JPG', 15000.00, 12],
            [22, 2, 'MNM-008', 'Madu Hutan Murni Riau 300ml', 'botol', 'https://images.unsplash.com/photo-1587049352851-8d4e89133924?w=500&auto=format&fit=crop&q=80', 65000.00, 5],
            [23, 1, 'SNK-007', 'Kerupuk Ikan Tenggiri Asli 200g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a6/Kerupuk_dalam_kaleng.jpg/500px-Kerupuk_dalam_kaleng.jpg', 38000.00, 6],
            [24, 2, 'MNM-009', 'Kopi Bubuk Arabika Toraja 200g', 'pouch', 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=500&auto=format&fit=crop&q=80', 48000.00, 5],
        ];

        $prodStmt = $pdo->prepare(
            "INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `unit`, `image_url`, `selling_price`, `min_stock`, `is_active`) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE 
                `category_id`   = VALUES(`category_id`),
                `name`          = VALUES(`name`),
                `unit`          = VALUES(`unit`),
                `image_url`     = VALUES(`image_url`),
                `selling_price` = VALUES(`selling_price`),
                `min_stock`     = VALUES(`min_stock`),
                `is_active`     = 1"
        );
        foreach ($products as $prod) {
            $prodStmt->execute($prod);
        }
        $stats['products'] = (int) $pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
        $messages[] = "{$stats['products']} Produk siap tampil dengan foto real dan harga aktif.";

        // ----------------------------------------------------
        // SEEDING 24 BATCH STOK FEFO
        // ----------------------------------------------------
        $batches = [
            [1, 1, 'BCH-SNK-01', 60, 50, 50, 8500.00],
            [2, 2, 'BCH-MNM-01', 14, 40, 40, 9500.00],
            [3, 3, 'BCH-SBK-01', 180, 20, 20, 58000.00],
            [4, 4, 'BCH-MNM-02', 90, 60, 60, 3000.00],
            [5, 5, 'BCH-CRO-01', 5, 25, 25, 11000.00],
            [6, 6, 'BCH-BSK-01', 120, 45, 45, 9000.00],
            [7, 7, 'BCH-ROT-01', 4, 15, 15, 14000.00],
            [8, 8, 'BCH-MAT-01', 20, 30, 30, 12500.00],
            [9, 9, 'BCH-ESP-01', 150, 20, 20, 22000.00],
            [10, 10, 'BCH-SSU-01', 60, 40, 40, 15500.00],
            [11, 11, 'BCH-MYK-01', 240, 35, 35, 28000.00],
            [12, 12, 'BCH-GLA-01', 300, 50, 50, 13000.00],
            [13, 13, 'BCH-DDL-01', 90, 40, 40, 18000.00],
            [14, 14, 'BCH-LPS-01', 14, 15, 15, 65000.00],
            [15, 15, 'BCH-SBL-01', 120, 50, 50, 24000.00],
            [16, 16, 'BCH-ABN-01', 180, 30, 30, 34000.00],
            [17, 17, 'BCH-EMP-01', 75, 45, 45, 20000.00],
            [18, 18, 'BCH-MET-01', 90, 25, 25, 42000.00],
            [19, 19, 'BCH-JMU-01', 10, 35, 35, 8000.00],
            [20, 20, 'BCH-MKS-01', 150, 30, 30, 26000.00],
            [21, 21, 'BCH-RDG-01', 200, 60, 60, 10500.00],
            [22, 22, 'BCH-MDU-01', 365, 20, 20, 48000.00],
            [23, 23, 'BCH-KRP-01', 60, 30, 30, 28000.00],
            [24, 24, 'BCH-TRJ-01', 180, 25, 25, 36000.00],
        ];

        $batchStmt = $pdo->prepare(
            "INSERT INTO `stock_batches` 
              (`id`, `product_id`, `supplier_id`, `batch_code`, `received_at`, `expiry_date`, `qty_received`, `qty_remaining`, `cost_per_unit`, `created_by`) 
             VALUES (?, ?, NULL, ?, NOW(), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE 
                `batch_code`    = VALUES(`batch_code`),
                `qty_remaining` = VALUES(`qty_remaining`),
                `cost_per_unit` = VALUES(`cost_per_unit`)"
        );
        foreach ($batches as $b) {
            $batchStmt->execute([$b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6]]);
        }
        $stats['stock_batches'] = (int) $pdo->query("SELECT COUNT(*) FROM `stock_batches`")->fetchColumn();
        $messages[] = "{$stats['stock_batches']} Batch stok alokasi FEFO berhasil terhubung ke produk.";

    } catch (Throwable $e) {
        $messages[] = 'Gagal saat memproses SQL: ' . $e->getMessage();
    }
}

// ----------------------------------------------------
// TAMPILAN CLI (Jika dipanggil via terminal)
// ----------------------------------------------------
if ($isCli) {
    if (!$dbConnected) {
        fwrite(STDERR, "ERROR: Koneksi database gagal: {$dbError}\n");
        fwrite(STDERR, "Silakan periksa config/database.php atau buat config/database.local.php\n");
        exit(1);
    }
    echo "=== SISASENSE DATABASE SEEDER (SUCCESS) ===\n";
    foreach ($messages as $msg) {
        echo " - {$msg}\n";
    }
    echo "\nAkun Demo yang Siap Digunakan (Password: 123):\n";
    echo " 1. Admin   : admin   / 123 (Full akses dashboard & laporan)\n";
    echo " 2. Kasir   : kasir   / 123 (Akses transaksi POS)\n";
    echo " 3. Pembeli : pembeli / 123 (Akses katalog & belanja)\n";
    echo "=============================================\n";
    exit(0);
}

// ----------------------------------------------------
// TAMPILAN WEB RESPONSIVE & ELEGAN
// ----------------------------------------------------
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SisaSense - Database Seeder &amp; Setup Tool</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #0F5132;
            --brand-dark: #064E3B;
            --brand-accent: #84CC16;
            --brand-light: #F0FDF4;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --bg-page: #F8FAFC;
            --card-border: #E2E8F0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            line-height: 1.5;
            padding: 32px 16px;
        }
        .container {
            max-width: 820px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-dark) 100%);
            color: #ffffff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(15, 81, 50, 0.25);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .brand-badge {
            background: #ffffff;
            color: var(--brand-primary);
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 24px;
        }
        .brand-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 26px;
            font-weight: 800;
            line-height: 1.1;
        }
        .brand-subtitle {
            font-size: 12px;
            color: #BBF7D0;
            letter-spacing: .05em;
            text-transform: uppercase;
            font-weight: 600;
        }
        .card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--brand-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #166534;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .badge-pill {
            display: inline-block;
            background: #DCFCE7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
        }
        .credential-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .cred-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 16px;
            border-top: 4px solid var(--brand-primary);
        }
        .cred-role {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--brand-primary);
            margin-bottom: 4px;
        }
        .cred-user {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
        }
        .cred-pass {
            font-family: monospace;
            background: #E2E8F0;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            color: #0F172A;
            display: inline-block;
            margin-top: 6px;
        }
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: var(--brand-primary);
            color: #ffffff;
        }
        .btn-primary:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
        }
        .btn-outline {
            background: #ffffff;
            color: var(--brand-primary);
            border: 2px solid var(--brand-primary);
        }
        .btn-outline:hover {
            background: var(--brand-light);
        }
        .btn-secondary {
            background: #E2E8F0;
            color: var(--text-main);
        }
        .btn-secondary:hover {
            background: #CBD5E1;
        }
        .step-list {
            list-style: none;
            margin-top: 12px;
        }
        .step-list li {
            position: relative;
            padding-left: 28px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .step-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            color: #16A34A;
            font-weight: 900;
        }
        .code-box {
            background: #1E293B;
            color: #E2E8F0;
            padding: 14px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <div class="header-brand">
            <div class="brand-badge">S</div>
            <div>
                <div class="brand-title">SisaSense Seeder</div>
                <div class="brand-subtitle">One-Click Setup &bull; InfinityFree &amp; Local</div>
            </div>
        </div>
        <div>
            <span class="badge-pill" style="background: rgba(255,255,255,0.2); color: #fff;">PHP <?= phpversion(); ?></span>
        </div>
    </div>

    <?php if (!$dbConnected): ?>
        <div class="alert-error">
            <h3 style="margin-bottom: 8px; font-weight: 800;">Koneksi Database Belum Terhubung</h3>
            <p style="margin-bottom: 12px; font-size: 14px;"><?= htmlspecialchars($dbError); ?></p>
            <hr style="border: none; border-top: 1px solid #FECACA; margin: 12px 0;">
            <p style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Cara Konfigurasi untuk Hosting InfinityFree:</p>
            <ol style="padding-left: 20px; font-size: 13px; line-height: 1.6;">
                <li>Buka vPanel InfinityFree Anda &gt; menu <strong>MySQL Databases</strong>.</li>
                <li>Buat database baru, lalu catat <strong>MySQL Host Name</strong> (misal: <code>sql300.infinityfree.com</code>), <strong>MySQL User Name</strong> (misal: <code>if0_38123456</code>), dan <strong>Database Name</strong>.</li>
                <li>Buka file <code>config/database.php</code> atau buat file <code>config/database.local.php</code> berisi:</li>
            </ol>
            <div class="code-box">
&lt;?php
return [
    'host'     =&gt; 'sqlxxx.infinityfree.com', // Lihat di vPanel InfinityFree
    'database' =&gt; 'if0_xxxxxxx_sisasense',   // Nama database berawalan if0_...
    'username' =&gt; 'if0_xxxxxxx',             // Username cPanel InfinityFree
    'password' =&gt; 'PASSWORD_VPANEL_ANDA',   // Password cPanel Anda
    'port'     =&gt; '3306',
];
            </div>
            <div style="margin-top: 16px;">
                <a href="seeder.php" class="btn btn-primary" style="font-size: 13px;">Coba Hubungkan Ulang</a>
            </div>
        </div>
    <?php else: ?>
        <div class="alert-success">
            Koneksi Database Berhasil! Semua tabel dan data awal telah disinkronisasi.
        </div>

        <div class="card">
            <div class="card-title">
                Hasil Eksekusi Seeding
            </div>
            <ul class="step-list">
                <?php foreach ($messages as $msg): ?>
                    <li><?= htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card">
            <div class="card-title">
                Akun Demo Login (Semua Password: <code>123</code>)
            </div>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
                Anda dapat langsung masuk ke sistem menggunakan salah satu dari 3 akun di bawah ini:
            </p>
            
            <div class="credential-grid">
                <div class="cred-card">
                    <div class="cred-role">Peran: Administrator</div>
                    <div class="cred-user">admin</div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">admin@sisasense.id</div>
                    <div style="margin-top: 8px;">
                        <span style="font-size: 11px; color: var(--text-muted);">Sandi:</span>
                        <span class="cred-pass">123</span>
                    </div>
                </div>

                <div class="cred-card" style="border-top-color: #2563EB;">
                    <div class="cred-role" style="color: #2563EB;">Peran: Kasir POS</div>
                    <div class="cred-user">kasir</div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">kasir@sisasense.id</div>
                    <div style="margin-top: 8px;">
                        <span style="font-size: 11px; color: var(--text-muted);">Sandi:</span>
                        <span class="cred-pass">123</span>
                    </div>
                </div>

                <div class="cred-card" style="border-top-color: #059669;">
                    <div class="cred-role" style="color: #059669;">Peran: Pembeli / Member</div>
                    <div class="cred-user">pembeli</div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">pembeli@sisasense.id</div>
                    <div style="margin-top: 8px;">
                        <span style="font-size: 11px; color: var(--text-muted);">Sandi:</span>
                        <span class="cred-pass">123</span>
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <a href="login.php" class="btn btn-primary">Buka Halaman Login &rarr;</a>
                <a href="index.php" class="btn btn-outline">Lihat Katalog Toko</a>
                <a href="sales/pos.php" class="btn btn-secondary">Buka Kasir POS</a>
            </div>
        </div>

        <div class="card" style="background: #FFFBEB; border-color: #FDE68A;">
            <div style="font-size: 13px; color: #92400E; display: flex; gap: 8px; align-items: flex-start;">
                <span style="font-weight: 800; font-size: 16px;">Tips InfinityFree:</span>
                <div>
                    Jika Anda lebih memilih mengimpor file SQL manual lewat phpMyAdmin InfinityFree, Anda juga dapat mengimpor file 
                    <code>database/infinityfree_seeder.sql</code> yang sudah bebas dari perintah <code>CREATE DATABASE</code> sehingga 100% aman dari error permission <em>Access Denied</em>.
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>
</body>
</html>


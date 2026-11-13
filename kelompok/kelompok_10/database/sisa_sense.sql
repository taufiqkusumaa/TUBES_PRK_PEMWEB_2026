-- SisaSense database contract
-- Import once into a development database. Review the guide before changing this schema.

CREATE DATABASE IF NOT EXISTS sisasense_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE sisasense_db;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','cashier','customer') NOT NULL DEFAULT 'customer',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  sku VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
  image_url VARCHAR(500) NULL,
  selling_price DECIMAL(14,2) NOT NULL,
  min_stock INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_products_selling_price CHECK (selling_price >= 0)
) ENGINE=InnoDB;

CREATE TABLE stock_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  supplier_id BIGINT UNSIGNED NULL,
  batch_code VARCHAR(80) NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expiry_date DATE NULL,
  qty_received INT UNSIGNED NOT NULL,
  qty_remaining INT UNSIGNED NOT NULL,
  cost_per_unit DECIMAL(14,2) NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_batches_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_batches_supplier
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_batches_creator
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_batch_qty CHECK (qty_remaining <= qty_received),
  CONSTRAINT chk_batch_cost CHECK (cost_per_unit >= 0),
  INDEX idx_batches_product_expiry (product_id, expiry_date),
  INDEX idx_batches_expiry (expiry_date)
) ENGINE=InnoDB;

CREATE TABLE sales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(40) NOT NULL UNIQUE,
  cashier_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  sold_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  subtotal DECIMAL(14,2) NOT NULL,
  discount_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  grand_total DECIMAL(14,2) NOT NULL,
  amount_paid DECIMAL(14,2) NOT NULL,
  change_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  payment_method ENUM('cash','transfer','qris','debit','other')
    NOT NULL DEFAULT 'cash',
  status ENUM('completed','voided') NOT NULL DEFAULT 'completed',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sales_cashier
    FOREIGN KEY (cashier_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_sales_customer
    FOREIGN KEY (customer_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_sales_amounts CHECK (
    subtotal >= 0 AND discount_total >= 0 AND
    grand_total >= 0 AND amount_paid >= 0 AND change_amount >= 0
  ),
  INDEX idx_sales_sold_at_status (sold_at, status),
  INDEX idx_sales_customer (customer_id)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  batch_id BIGINT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  unit_price_snapshot DECIMAL(14,2) NOT NULL,
  cost_price_snapshot DECIMAL(14,2) NOT NULL,
  discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(14,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sale_items_sale
    FOREIGN KEY (sale_id) REFERENCES sales(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_sale_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_sale_items_batch
    FOREIGN KEY (batch_id) REFERENCES stock_batches(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_sale_items_qty CHECK (qty > 0),
  CONSTRAINT chk_sale_items_amounts CHECK (
    unit_price_snapshot >= 0 AND cost_price_snapshot >= 0 AND
    discount_amount >= 0 AND line_total >= 0
  ),
  INDEX idx_sale_items_product (product_id),
  INDEX idx_sale_items_sale (sale_id)
) ENGINE=InnoDB;

CREATE TABLE inventory_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  batch_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  sale_id BIGINT UNSIGNED NULL,
  movement_type ENUM(
    'IN','OUT','SALE','RETURN',
    'ADJUSTMENT_IN','ADJUSTMENT_OUT','EXPIRED'
  ) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_cost DECIMAL(14,2) NULL,
  reason VARCHAR(255) NULL,
  occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_movements_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_movements_batch
    FOREIGN KEY (batch_id) REFERENCES stock_batches(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_movements_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_movements_sale
    FOREIGN KEY (sale_id) REFERENCES sales(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_movements_quantity CHECK (quantity > 0),
  INDEX idx_movements_product_date (product_id, occurred_at),
  INDEX idx_movements_sale (sale_id)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA AWAL UNTUK PENGUJIAN 3 ROLE (Admin, Kasir, Pembeli)
-- ============================================================
-- Password default semua akun demo: 123
-- 1. admin    / 123
-- 2. kasir    / 123
-- 3. pembeli  / 123

INSERT INTO users (id, full_name, username, email, password_hash, role, is_active) VALUES
(1, 'Administrator Toko', 'admin', 'admin@sisasense.id', '$2y$10$OHOqPC9FCREIhssp621XS./RC9JF/zEAXtF0rTqRqNG2J9wQCm3Di', 'admin', 1),
(2, 'Budi Kasir', 'kasir', 'kasir@sisasense.id', '$2y$10$OHOqPC9FCREIhssp621XS./RC9JF/zEAXtF0rTqRqNG2J9wQCm3Di', 'cashier', 1),
(3, 'Siti Pembeli', 'pembeli', 'pembeli@sisasense.id', '$2y$10$OHOqPC9FCREIhssp621XS./RC9JF/zEAXtF0rTqRqNG2J9wQCm3Di', 'customer', 1)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), full_name=VALUES(full_name), role=VALUES(role), is_active=VALUES(is_active);

-- Kategori Demo
INSERT INTO categories (id, name, description, is_active) VALUES
(1, 'Makanan & Snack', 'Camilan ringan, biskuit, dan makanan siap santap', 1),
(2, 'Minuman Segar', 'Kopi, teh, susu, dan minuman kemasan dingin', 1),
(3, 'Sembako & Pokok', 'Beras, minyak goreng, gula, dan bumbu dapur', 1),
(4, 'Oleh-Oleh & Tradisional', 'Makanan khas daerah, kue tradisional, dan oleh-oleh nusantara', 1),
(5, 'Bumbu Dapur & Sambal', 'Sambal rumahan, bumbu rempah, dan saus khas UMKM', 1)
ON DUPLICATE KEY UPDATE id=id;

-- Produk Demo dengan Foto Real
INSERT INTO products (id, category_id, sku, name, unit, image_url, selling_price, min_stock, is_active) VALUES
(1, 1, 'SNK-001', 'Keripik Singkong Balado 150g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/84/Keripik_singkong_balado_cassava_chips.JPG/500px-Keripik_singkong_balado_cassava_chips.JPG', 12000.00, 5, 1),
(2, 2, 'MNM-001', 'Kopi Susu Gula Aren 250ml', 'botol', 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=500&auto=format&fit=crop&q=80', 15000.00, 10, 1),
(3, 3, 'SBK-001', 'Beras Ramos Super 5kg', 'karung', 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80', 68000.00, 4, 1),
(4, 2, 'MNM-002', 'Teh Melati Melur Kotak 200ml', 'kotak', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=500&auto=format&fit=crop&q=80', 4500.00, 8, 1),
(5, 1, 'SNK-002', 'Croissant Butter French', 'pcs', 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500&auto=format&fit=crop&q=80', 18000.00, 5, 1),
(6, 1, 'SNK-003', 'Biskuit Cokelat Lava 120g', 'pack', 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=500&auto=format&fit=crop&q=80', 14500.00, 8, 1),
(7, 1, 'SNK-004', 'Roti Tawar Gandum Premium', 'loaf', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&auto=format&fit=crop&q=80', 22000.00, 4, 1),
(8, 2, 'MNM-003', 'Matcha Latte Creamy 250ml', 'cup', 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?w=500&auto=format&fit=crop&q=80', 20000.00, 6, 1),
(9, 2, 'MNM-004', 'Kopi Espresso Robusta 100g', 'pouch', 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=500&auto=format&fit=crop&q=80', 35000.00, 5, 1),
(10, 2, 'MNM-005', 'Susu UHT Full Cream 1 Liter', 'kotak', 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=500&auto=format&fit=crop&q=80', 21000.00, 10, 1),
(11, 3, 'SBK-002', 'Minyak Goreng Sawit Murni 2L', 'pouch', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c7/Cooking_oil.jpg/500px-Cooking_oil.jpg', 36000.00, 8, 1),
(12, 3, 'SBK-003', 'Gula Pasir Tebu Kristal 1kg', 'kg', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/05/Sugar_White.jpg/500px-Sugar_White.jpg', 17500.00, 12, 1),
(13, 4, 'OLH-001', 'Dodol Garut Asli Wijen 250g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/f9/Dodol_Garut_Cihampelas_Bandung.JPG/500px-Dodol_Garut_Cihampelas_Bandung.JPG', 25000.00, 6, 1),
(14, 4, 'OLH-002', 'Kue Lapis Legit Spekuk Premium', 'loyang', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/db/Kue_Lapis_Legit.JPG/500px-Kue_Lapis_Legit.JPG', 85000.00, 3, 1),
(15, 5, 'BMB-001', 'Sambal Bawang Bu Rudy Khas Surabaya', 'jar', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/5a/SAMBAL_BAWANG.jpg/500px-SAMBAL_BAWANG.jpg', 32000.00, 8, 1),
(16, 4, 'OLH-003', 'Abon Sapi Gurih Manis Boyolali 150g', 'jar', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/57/Abon_sapi.JPG/500px-Abon_sapi.JPG', 45000.00, 5, 1),
(17, 1, 'SNK-005', 'Emping Melinjo Super Renyah 250g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/08/Emping_blinjo_Sidoarjo.jpg/500px-Emping_blinjo_Sidoarjo.jpg', 28000.00, 8, 1),
(18, 1, 'SNK-006', 'Kacang Mete Panggang Wonogiri 200g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/fd/Cashews_1314.jpg/500px-Cashews_1314.jpg', 55000.00, 4, 1),
(19, 2, 'MNM-006', 'Jamu Kunyit Asam Segar 350ml', 'botol', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/cd/Miunman_Es_Kunyit_Asam.jpg/500px-Miunman_Es_Kunyit_Asam.jpg', 12000.00, 10, 1),
(20, 2, 'MNM-007', 'Sirup Markisa Asli Brastagi 500ml', 'botol', 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=500&auto=format&fit=crop&q=80', 35000.00, 6, 1),
(21, 5, 'BMB-002', 'Bumbu Rendang Padang Instan 100g', 'sachet', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/7/70/Rendang_daging_sapi_asli_Padang.JPG/500px-Rendang_daging_sapi_asli_Padang.JPG', 15000.00, 12, 1),
(22, 2, 'MNM-008', 'Madu Hutan Murni Riau 300ml', 'botol', 'https://images.unsplash.com/photo-1587049352851-8d4e89133924?w=500&auto=format&fit=crop&q=80', 65000.00, 5, 1),
(23, 1, 'SNK-007', 'Kerupuk Ikan Tenggiri Asli 200g', 'pack', 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a6/Kerupuk_dalam_kaleng.jpg/500px-Kerupuk_dalam_kaleng.jpg', 38000.00, 6, 1),
(24, 2, 'MNM-009', 'Kopi Bubuk Arabika Toraja 200g', 'pouch', 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=500&auto=format&fit=crop&q=80', 48000.00, 5, 1)
ON DUPLICATE KEY UPDATE id=id;

-- Batch Stok Awal (FEFO)
INSERT INTO stock_batches (id, product_id, supplier_id, batch_code, received_at, expiry_date, qty_received, qty_remaining, cost_per_unit, created_by) VALUES
(1, 1, NULL, 'BCH-SNK-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 50, 50, 8500.00, 1),
(2, 2, NULL, 'BCH-MNM-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 40, 40, 9500.00, 1),
(3, 3, NULL, 'BCH-SBK-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 20, 20, 58000.00, 1),
(4, 4, NULL, 'BCH-MNM-02', NOW(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 60, 60, 3000.00, 1),
(5, 5, NULL, 'BCH-CRO-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 25, 25, 11000.00, 1),
(6, 6, NULL, 'BCH-BSK-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 120 DAY), 45, 45, 9000.00, 1),
(7, 7, NULL, 'BCH-ROT-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 15, 15, 14000.00, 1),
(8, 8, NULL, 'BCH-MAT-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 30, 30, 12500.00, 1),
(9, 9, NULL, 'BCH-ESP-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 20, 20, 22000.00, 1),
(10, 10, NULL, 'BCH-SSU-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 40, 40, 15500.00, 1),
(11, 11, NULL, 'BCH-MYK-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 240 DAY), 35, 35, 28000.00, 1),
(12, 12, NULL, 'BCH-GLA-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 50, 50, 13000.00, 1),
(13, 13, NULL, 'BCH-DDL-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 40, 40, 18000.00, 1),
(14, 14, NULL, 'BCH-LPS-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 15, 15, 65000.00, 1),
(15, 15, NULL, 'BCH-SBL-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 120 DAY), 50, 50, 24000.00, 1),
(16, 16, NULL, 'BCH-ABN-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 30, 30, 34000.00, 1),
(17, 17, NULL, 'BCH-EMP-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 75 DAY), 45, 45, 20000.00, 1),
(18, 18, NULL, 'BCH-MET-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 25, 25, 42000.00, 1),
(19, 19, NULL, 'BCH-JMU-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 35, 35, 8000.00, 1),
(20, 20, NULL, 'BCH-MKS-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 30, 30, 26000.00, 1),
(21, 21, NULL, 'BCH-RDG-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 200 DAY), 60, 60, 10500.00, 1),
(22, 22, NULL, 'BCH-MDU-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 20, 20, 48000.00, 1),
(23, 23, NULL, 'BCH-KRP-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 30, 30, 28000.00, 1),
(24, 24, NULL, 'BCH-TRJ-01', NOW(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 25, 25, 36000.00, 1)
ON DUPLICATE KEY UPDATE id=id;

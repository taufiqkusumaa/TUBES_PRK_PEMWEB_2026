<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Metode request tidak diizinkan.');
    redirect('sales/pos.php');
}

require_valid_csrf();

$rawCart = $_POST['cart_data'] ?? '{}';
$cart = json_decode((string) $rawCart, true);

if (!is_array($cart) || empty($cart)) {
    flash('error', 'Keranjang transaksi kosong.');
    redirect('sales/pos.php');
}

$discount = max(0.0, (float) ($_POST['discount'] ?? 0));
$amountPaid = max(0.0, (float) ($_POST['amount_paid'] ?? 0));

$connection = db();

try {
    $connection->beginTransaction();

    // 1. Validasi dan Ambil Harga Resmi dari Database (Anti Client-tampering)
    $productIds = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmtProducts = $connection->prepare("SELECT id, name, sku, unit, selling_price, is_active FROM products WHERE id IN ({$placeholders}) FOR UPDATE");
    $stmtProducts->execute($productIds);
    $dbProducts = [];
    foreach ($stmtProducts->fetchAll() as $p) {
        $dbProducts[(int) $p['id']] = $p;
    }

    $subtotal = 0.0;
    $allocatedItems = []; // Menyimpan item teralokasi batch

    foreach ($cart as $pidStr => $item) {
        $pid = (int) $pidStr;
        $qtyNeeded = (int) ($item['qty'] ?? 0);

        if ($qtyNeeded <= 0) continue;

        if (!isset($dbProducts[$pid])) {
            throw new Exception("Produk dengan ID {$pid} tidak ditemukan atau sudah dihapus.");
        }

        $prod = $dbProducts[$pid];
        if ((int) $prod['is_active'] !== 1) {
            throw new Exception("Produk '{$prod['name']}' saat ini berstatus non-aktif.");
        }

        $unitPrice = (float) $prod['selling_price'];
        $subtotal += $unitPrice * $qtyNeeded;

        // 2. Alokasi Batch menggunakan metode FEFO (First Expired, First Out)
        $stmtBatches = $connection->prepare("
            SELECT id, batch_code, qty_remaining, cost_per_unit, expiry_date 
            FROM stock_batches 
            WHERE product_id = :p_id 
              AND qty_remaining > 0 
              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
            ORDER BY 
              CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END,
              expiry_date ASC,
              id ASC
            FOR UPDATE
        ");
        $stmtBatches->execute(['p_id' => $pid]);
        $availableBatches = $stmtBatches->fetchAll();

        $remainingToAllocate = $qtyNeeded;

        foreach ($availableBatches as $batch) {
            if ($remainingToAllocate <= 0) break;

            $batchRem = (int) $batch['qty_remaining'];
            $take = min($remainingToAllocate, $batchRem);

            $allocatedItems[] = [
                'product_id' => $pid,
                'batch_id' => (int) $batch['id'],
                'batch_code' => $batch['batch_code'],
                'qty' => $take,
                'unit_price' => $unitPrice,
                'cost_price' => (float) $batch['cost_per_unit'],
                'line_total' => $unitPrice * $take,
            ];

            // Kurangi qty_remaining pada batch
            $stmtDeduct = $connection->prepare("UPDATE stock_batches SET qty_remaining = qty_remaining - :take WHERE id = :bid");
            $stmtDeduct->execute(['take' => $take, 'bid' => (int) $batch['id']]);

            $remainingToAllocate -= $take;
        }

        if ($remainingToAllocate > 0) {
            throw new Exception("Stok untuk '{$prod['name']}' tidak mencukupi. Kurang {$remainingToAllocate} {$prod['unit']}.");
        }
    }

    $grandTotal = max(0.0, $subtotal - $discount);

    // Ambil dan validasi metode pembayaran
    $rawPaymentMethod = strtolower(trim((string) ($_POST['payment_method'] ?? 'cash')));
    $allowedMethods = ['cash', 'qris', 'transfer', 'debit', 'other'];
    $paymentMethod = in_array($rawPaymentMethod, $allowedMethods, true) ? $rawPaymentMethod : 'cash';

    // Jika non-tunai (QRIS / Transfer / Debit), otomatis lunas pas
    if ($paymentMethod !== 'cash') {
        $amountPaid = $grandTotal;
        $changeAmount = 0.0;
    } else {
        if ($amountPaid < $grandTotal) {
            throw new Exception("Jumlah uang yang dibayarkan (Rp " . number_format($amountPaid, 0, ',', '.') . ") kurang dari total belanja (Rp " . number_format($grandTotal, 0, ',', '.') . ").");
        }
        $changeAmount = $amountPaid - $grandTotal;
    }

    $rawNotes = trim((string) ($_POST['notes'] ?? ''));
    $notes = !empty($rawNotes) ? substr($rawNotes, 0, 250) : ('Transaksi POS Kasir (' . strtoupper($paymentMethod) . ')');

    $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    $currentUser = current_user();
    $customerId = null;
    if ($currentUser['role'] === 'customer') {
        $customerId = (int) $currentUser['id'];
    } elseif (!empty($_POST['customer_id'])) {
        $customerId = (int) $_POST['customer_id'];
    }

    // 3. Simpan Header Transaksi ke sales
    $stmtSale = $connection->prepare("
        INSERT INTO sales (
            invoice_no, cashier_id, customer_id, sold_at, subtotal, discount_total, 
            grand_total, amount_paid, change_amount, payment_method, status, notes
        ) VALUES (
            :inv, :cashier_id, :customer_id, NOW(), :subtotal, :discount,
            :grand_total, :amount_paid, :change_amount, :payment_method, 'completed', :notes
        )
    ");
    $stmtSale->execute([
        'inv' => $invoiceNo,
        'cashier_id' => $currentUser['id'],
        'customer_id' => $customerId,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'grand_total' => $grandTotal,
        'amount_paid' => $amountPaid,
        'change_amount' => $changeAmount,
        'payment_method' => $paymentMethod,
        'notes' => $notes,
    ]);
    $saleId = (int) $connection->lastInsertId();

    // 4. Simpan Detail Item ke sale_items & Catat Mutasi ke inventory_movements
    $stmtItem = $connection->prepare("
        INSERT INTO sale_items (
            sale_id, product_id, batch_id, qty, unit_price_snapshot, 
            cost_price_snapshot, discount_amount, line_total
        ) VALUES (
            :sale_id, :product_id, :batch_id, :qty, :unit_price,
            :cost_price, 0, :line_total
        )
    ");

    $stmtMov = $connection->prepare("
        INSERT INTO inventory_movements (
            product_id, batch_id, user_id, sale_id, movement_type, 
            quantity, unit_cost, reason, occurred_at
        ) VALUES (
            :product_id, :batch_id, :user_id, :sale_id, 'SALE',
            :qty, :unit_cost, :reason, NOW()
        )
    ");

    foreach ($allocatedItems as $alloc) {
        $stmtItem->execute([
            'sale_id' => $saleId,
            'product_id' => $alloc['product_id'],
            'batch_id' => $alloc['batch_id'],
            'qty' => $alloc['qty'],
            'unit_price' => $alloc['unit_price'],
            'cost_price' => $alloc['cost_price'],
            'line_total' => $alloc['line_total'],
        ]);

        $stmtMov->execute([
            'product_id' => $alloc['product_id'],
            'batch_id' => $alloc['batch_id'],
            'user_id' => current_user()['id'],
            'sale_id' => $saleId,
            'qty' => $alloc['qty'],
            'unit_cost' => $alloc['cost_price'],
            'reason' => "Penjualan POS #{$invoiceNo} (Batch: {$alloc['batch_code']})",
        ]);
    }

    $connection->commit();

    flash('success', "Transaksi {$invoiceNo} berhasil disimpan.");
    redirect('sales/receipt.php?id=' . $saleId);

} catch (Exception $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    error_log('Checkout transaction failed: ' . $e->getMessage());
    flash('error', $e->getMessage());
    redirect('sales/pos.php');
}

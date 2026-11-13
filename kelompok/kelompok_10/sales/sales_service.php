<?php
declare(strict_types=1);

require_once __DIR__ . '/../inventory/inventory_service.php';
require_once __DIR__ . '/helpers.php';

function sales_generate_invoice_no(): string
{
    return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Proses checkout: simpan sales, sale_items, kurangi stok, movement SALE.
 * @param array<int,int> $cart [product_id => qty]
 * @return array{sale_id:int, invoice_no:string, grand_total:string, change:string}
 */
function sales_checkout(
    PDO $pdo,
    int $cashierId,
    array $cart,
    string $method,
    ?string $amountPaid,
    ?string $notes
): array {
    // 1. Validasi awal (sebelum transaksi)
    if ($cart === []) {
        throw new InvalidArgumentException('Keranjang kosong.');
    }
    if (!array_key_exists($method, sales_payment_methods())) {
        throw new InvalidArgumentException('Metode pembayaran tidak valid.');
    }
    foreach ($cart as $qty) {
        if ((int) $qty < 1) {
            throw new InvalidArgumentException('Jumlah item harus minimal 1.');
        }
    }

    $paidCents = 0;
    if ($method === 'cash') {
        if ($amountPaid === null || trim($amountPaid) === '') {
            throw new InvalidArgumentException('Uang diterima wajib diisi untuk pembayaran tunai.');
        }
        try {
            $paidCents = sales_to_cents($amountPaid);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException('Uang diterima harus berupa angka (maks. 2 desimal), contoh 50000.');
        }
    }

    // 2. Urutkan menurut product_id (urutan kunci konsisten, kurangi deadlock)
    ksort($cart);

    $inTx = false;
    try {
        // 3. Mulai transaksi
        $pdo->beginTransaction();
        $inTx = true;

        // 4-7. Alokasi FEFO dan bentuk baris penjualan
        $productStmt = $pdo->prepare('SELECT id, name, selling_price, is_active FROM products WHERE id = ?');
        $lines = [];
        $subtotalCents = 0;
        $discountCents = 0;

        foreach ($cart as $productId => $qty) {
            $productId = (int) $productId;
            $qty = (int) $qty;

            $productStmt->execute([$productId]);
            $product = $productStmt->fetch();
            if (!$product) {
                throw new InvalidArgumentException('Produk #' . $productId . ' tidak ditemukan.');
            }
            if ((int) $product['is_active'] !== 1) {
                throw new InvalidArgumentException('Produk ' . $product['name'] . ' nonaktif dan tidak bisa dijual.');
            }

            try {
                $allocations = inv_allocate_fefo($pdo, $productId, $qty);
            } catch (RuntimeException $e) {
                throw new RuntimeException('Stok ' . $product['name'] . ' tidak cukup.', 0, $e);
            }

            $priceCents = sales_to_cents((string) $product['selling_price']);
            foreach ($allocations as $alloc) {
                $lineCents = $alloc['qty'] * $priceCents;
                $lines[] = [
                    'product_id'  => $productId,
                    'batch_id'    => $alloc['batch_id'],
                    'qty'         => $alloc['qty'],
                    'unit_price'  => sales_from_cents($priceCents),
                    'cost_price'  => $alloc['cost_per_unit'],
                    'discount'    => '0.00',
                    'line_total'  => sales_from_cents($lineCents),
                ];
                $subtotalCents += $lineCents;
            }
        }

        // 8. Total
        $grandCents = $subtotalCents - $discountCents;

        // 9. Pembayaran
        if ($method === 'cash') {
            if ($paidCents < $grandCents) {
                throw new InvalidArgumentException(
                    'Uang tunai kurang. Total ' . sales_rupiah_cents($grandCents) . ', diterima ' . sales_rupiah_cents($paidCents) . '.'
                );
            }
            $changeCents = $paidCents - $grandCents;
        } else {
            $paidCents = $grandCents;
            $changeCents = 0;
        }

        // 10. Simpan sales (ulang maks. 3x jika invoice_no bentrok)
        $saleStmt = $pdo->prepare(
            'INSERT INTO sales
                (invoice_no, cashier_id, sold_at, payment_method, subtotal, discount_total,
                 grand_total, amount_paid, change_amount, status, notes)
             VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, \'completed\', ?)'
        );
        $saleId = 0;
        $invoiceNo = '';
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $invoiceNo = sales_generate_invoice_no();
            try {
                $saleStmt->execute([
                    $invoiceNo,
                    $cashierId,
                    $method,
                    sales_from_cents($subtotalCents),
                    sales_from_cents($discountCents),
                    sales_from_cents($grandCents),
                    sales_from_cents($paidCents),
                    sales_from_cents($changeCents),
                    $notes,
                ]);
                $saleId = (int) $pdo->lastInsertId();
                break;
            } catch (PDOException $e) {
                $isDuplicate = isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062;
                if (!$isDuplicate || $attempt === 3) {
                    throw $e;
                }
            }
        }

        // sale_items + kurangi stok + movement SALE
        $itemStmt = $pdo->prepare(
            'INSERT INTO sale_items
                (sale_id, product_id, batch_id, qty, unit_price_snapshot, cost_price_snapshot,
                 discount_amount, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($lines as $line) {
            $itemStmt->execute([
                $saleId,
                $line['product_id'],
                $line['batch_id'],
                $line['qty'],
                $line['unit_price'],
                $line['cost_price'],
                $line['discount'],
                $line['line_total'],
            ]);

            if (!inv_deduct_batch($pdo, $line['batch_id'], $line['qty'])) {
                throw new RuntimeException('Gagal mengurangi stok batch #' . $line['batch_id'] . '.');
            }

            inv_record_movement($pdo, [
                'product_id'    => $line['product_id'],
                'batch_id'      => $line['batch_id'],
                'user_id'       => $cashierId,
                'sale_id'       => $saleId,
                'movement_type' => 'SALE',
                'quantity'      => $line['qty'],
                'unit_cost'     => $line['cost_price'],
                'reason'        => 'Penjualan ' . $invoiceNo,
            ]);
        }

        // 11. Commit
        $pdo->commit();
        $inTx = false;

        return [
            'sale_id'     => $saleId,
            'invoice_no'  => $invoiceNo,
            'grand_total' => sales_from_cents($grandCents),
            'change'      => sales_from_cents($changeCents),
        ];
    } catch (Throwable $e) {
        // 12. Rollback penuh lalu lempar ulang
        if ($inTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Batalkan transaksi: status menjadi voided, stok dipulihkan (movement RETURN).
 * Tidak ada baris yang dihapus.
 */
function sales_void(PDO $pdo, int $saleId, int $adminId, string $reason): void
{
    $reason = trim($reason);
    if ($reason === '') {
        throw new InvalidArgumentException('Alasan pembatalan wajib diisi.');
    }
    if (mb_strlen($reason) > 200) {
        throw new InvalidArgumentException('Alasan maksimal 200 karakter.');
    }

    $inTx = false;
    try {
        $pdo->beginTransaction();
        $inTx = true;

        $stmt = $pdo->prepare('SELECT id, invoice_no, status, notes FROM sales WHERE id = ? FOR UPDATE');
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();
        if (!$sale) {
            throw new InvalidArgumentException('Transaksi tidak ditemukan.');
        }
        if ($sale['status'] !== 'completed') {
            throw new InvalidArgumentException('Transaksi ini sudah dibatalkan.');
        }

        $newNotes = trim((string) ($sale['notes'] ?? '') . ' | VOID: ' . $reason, ' |');
        $upd = $pdo->prepare('UPDATE sales SET status = \'voided\', notes = ? WHERE id = ? AND status = \'completed\'');
        $upd->execute([mb_substr($newNotes, 0, 255), $saleId]);
        if ($upd->rowCount() !== 1) {
            throw new RuntimeException('Gagal mengubah status transaksi.');
        }

        $itemsStmt = $pdo->prepare(
            'SELECT product_id, batch_id, qty, cost_price_snapshot FROM sale_items WHERE sale_id = ? ORDER BY id'
        );
        $itemsStmt->execute([$saleId]);
        foreach ($itemsStmt->fetchAll() as $item) {
            $qty = (int) $item['qty'];
            if (!inv_restore_batch($pdo, (int) $item['batch_id'], $qty)) {
                throw new RuntimeException('Stok batch #' . $item['batch_id'] . ' tidak bisa dipulihkan.');
            }
            inv_record_movement($pdo, [
                'product_id'    => (int) $item['product_id'],
                'batch_id'      => (int) $item['batch_id'],
                'user_id'       => $adminId,
                'sale_id'       => $saleId,
                'movement_type' => 'RETURN',
                'quantity'      => $qty,
                'unit_cost'     => $item['cost_price_snapshot'],
                'reason'        => 'Void ' . $sale['invoice_no'] . ': ' . $reason,
            ]);
        }

        $pdo->commit();
        $inTx = false;
    } catch (Throwable $e) {
        if ($inTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

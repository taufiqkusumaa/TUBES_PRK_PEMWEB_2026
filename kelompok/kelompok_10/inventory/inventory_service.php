<?php
declare(strict_types=1);

function inv_get_available_stock(PDO $pdo, int $productId): int
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(qty_remaining), 0)
         FROM stock_batches
         WHERE product_id = ? AND qty_remaining > 0
           AND (expiry_date IS NULL OR expiry_date >= CURDATE())'
    );
    $stmt->execute([$productId]);
    return (int) $stmt->fetchColumn();
}

function inv_search_sellable_products(PDO $pdo, string $keyword = '', int $limit = 20): array
{
    $limit = max(1, min(100, $limit));
    $where = 'p.is_active = 1';
    $params = [];
    $keyword = trim($keyword);
    if ($keyword !== '') {
        $like = '%' . addcslashes($keyword, '%_\\') . '%';
        $where .= ' AND (p.name LIKE ? OR p.sku LIKE ?)';
        $params = [$like, $like];
    }

    $stmt = $pdo->prepare(
        "SELECT p.id, p.sku, p.name, p.unit, p.selling_price,
                SUM(b.qty_remaining) AS stock
         FROM products p
         JOIN stock_batches b ON b.product_id = p.id
         WHERE $where
           AND b.qty_remaining > 0
           AND (b.expiry_date IS NULL OR b.expiry_date >= CURDATE())
         GROUP BY p.id, p.sku, p.name, p.unit, p.selling_price
         ORDER BY p.name
         LIMIT $limit"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function inv_allocate_fefo(PDO $pdo, int $productId, int $qty): array
{
    if (!$pdo->inTransaction()) {
        throw new LogicException('inv_allocate_fefo harus dipanggil di dalam transaksi.');
    }
    if ($qty < 1) {
        throw new InvalidArgumentException('Jumlah harus lebih dari 0.');
    }

    $stmt = $pdo->prepare(
        'SELECT id, qty_remaining, cost_per_unit
         FROM stock_batches
         WHERE product_id = ? AND qty_remaining > 0
           AND (expiry_date IS NULL OR expiry_date >= CURDATE())
         ORDER BY (expiry_date IS NULL), expiry_date ASC, received_at ASC, id ASC
         FOR UPDATE'
    );
    $stmt->execute([$productId]);

    $allocations = [];
    $need = $qty;
    foreach ($stmt->fetchAll() as $batch) {
        if ($need <= 0) {
            break;
        }
        $take = min($need, (int) $batch['qty_remaining']);
        $allocations[] = [
            'batch_id'      => (int) $batch['id'],
            'qty'           => $take,
            'cost_per_unit' => (string) $batch['cost_per_unit'],
        ];
        $need -= $take;
    }

    if ($need > 0) {
        throw new RuntimeException('Stok tidak cukup.');
    }
    return $allocations;
}

function inv_deduct_batch(PDO $pdo, int $batchId, int $qty): bool
{
    if ($qty < 1) {
        throw new InvalidArgumentException('Jumlah harus lebih dari 0.');
    }
    $stmt = $pdo->prepare(
        'UPDATE stock_batches
         SET qty_remaining = qty_remaining - ?
         WHERE id = ? AND qty_remaining >= ?'
    );
    $stmt->execute([$qty, $batchId, $qty]);
    return $stmt->rowCount() === 1;
}

function inv_restore_batch(PDO $pdo, int $batchId, int $qty): bool
{
    if ($qty < 1) {
        throw new InvalidArgumentException('Jumlah harus lebih dari 0.');
    }
    $stmt = $pdo->prepare(
        'UPDATE stock_batches
         SET qty_remaining = qty_remaining + ?
         WHERE id = ? AND qty_remaining + ? <= qty_received'
    );
    $stmt->execute([$qty, $batchId, $qty]);
    return $stmt->rowCount() === 1;
}

function inv_record_movement(PDO $pdo, array $data): int
{
    $types = ['IN', 'OUT', 'SALE', 'RETURN', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'EXPIRED'];
    $type = (string) ($data['movement_type'] ?? '');
    $qty = (int) ($data['quantity'] ?? 0);
    if (!in_array($type, $types, true)) {
        throw new InvalidArgumentException('Tipe movement tidak valid.');
    }
    if ($qty < 1) {
        throw new InvalidArgumentException('Quantity movement harus lebih dari 0.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO inventory_movements
            (product_id, batch_id, user_id, sale_id, movement_type, quantity, unit_cost, reason, occurred_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()))'
    );
    $stmt->execute([
        (int) $data['product_id'],
        isset($data['batch_id']) ? (int) $data['batch_id'] : null,
        (int) $data['user_id'],
        isset($data['sale_id']) ? (int) $data['sale_id'] : null,
        $type,
        $qty,
        $data['unit_cost'] ?? null,
        $data['reason'] ?? null,
        $data['occurred_at'] ?? null,
    ]);
    return (int) $pdo->lastInsertId();
}

function inv_get_low_stock(PDO $pdo): array
{
    return $pdo->query(
        'SELECT p.id, p.sku, p.name, p.unit, p.min_stock,
                COALESCE(SUM(CASE WHEN b.qty_remaining > 0
                                   AND (b.expiry_date IS NULL OR b.expiry_date >= CURDATE())
                                  THEN b.qty_remaining ELSE 0 END), 0) AS stock
         FROM products p
         LEFT JOIN stock_batches b ON b.product_id = p.id
         WHERE p.is_active = 1
         GROUP BY p.id, p.sku, p.name, p.unit, p.min_stock
         HAVING stock <= p.min_stock
         ORDER BY stock ASC, p.name'
    )->fetchAll();
}

function inv_get_expiring_batches(PDO $pdo, int $days = 30): array
{
    $days = max(0, min(3650, $days));
    return $pdo->query(
        "SELECT b.id, b.product_id, b.batch_code, b.expiry_date, b.qty_remaining,
                p.sku, p.name, p.unit,
                DATEDIFF(b.expiry_date, CURDATE()) AS days_left
         FROM stock_batches b
         JOIN products p ON p.id = b.product_id
         WHERE b.qty_remaining > 0
           AND b.expiry_date IS NOT NULL
           AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL $days DAY)
         ORDER BY b.expiry_date ASC, b.id ASC"
    )->fetchAll();
}

<?php

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return [];
    }
    $clean = [];
    foreach ($cart as $productId => $qty) {
        $productId = (int) $productId;
        $qty       = (int) $qty;
        if ($productId > 0 && $qty > 0) {
            $clean[$productId] = $qty;
        }
    }
    return $clean;
}

function cart_find_active_product(PDO $pdo, int $productId): array
{
    $stmt = $pdo->prepare('SELECT id, name, sku, unit, selling_price, is_active FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new InvalidArgumentException('Produk tidak ditemukan.');
    }
    if ((int) $product['is_active'] !== 1) {
        throw new InvalidArgumentException('Produk "' . $product['name'] . '" tidak aktif.');
    }
    return $product;
}

function cart_add(PDO $pdo, int $productId, int $qty): void
{
    if ($qty < 1) {
        throw new InvalidArgumentException('Jumlah harus minimal 1.');
    }
    $product   = cart_find_active_product($pdo, $productId);
    $available = (int) inv_get_available_stock($pdo, $productId);
    $cart      = cart_items();
    $combined  = ($cart[$productId] ?? 0) + $qty;

    if ($combined > $available) {
        throw new InvalidArgumentException(
            'Stok "' . $product['name'] . '" tidak cukup. Tersedia ' . $available
            . ', di keranjang ' . ($cart[$productId] ?? 0) . '.'
        );
    }
    $cart[$productId]  = $combined;
    $_SESSION['cart']  = $cart;
}

function cart_set_qty(PDO $pdo, int $productId, int $qty): void
{
    if ($qty <= 0) {
        cart_remove($productId);
        return;
    }
    $product   = cart_find_active_product($pdo, $productId);
    $available = (int) inv_get_available_stock($pdo, $productId);
    if ($qty > $available) {
        throw new InvalidArgumentException(
            'Stok "' . $product['name'] . '" tidak cukup. Tersedia ' . $available . '.'
        );
    }
    $cart             = cart_items();
    $cart[$productId] = $qty;
    $_SESSION['cart'] = $cart;
}

function cart_remove(int $productId): void
{
    $cart = cart_items();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

/**
 * @return array{items: array<int, array>, total_cents: int, total: string, has_issue: bool}
 */
function cart_detail(PDO $pdo): array
{
    $cart   = cart_items();
    $items  = [];
    $total  = 0;
    $issue  = false;

    if ($cart) {
        ksort($cart);
        $ids          = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt         = $pdo->prepare(
            "SELECT id, name, sku, unit, selling_price, is_active FROM products WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $products = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $products[(int) $row['id']] = $row;
        }

        foreach ($cart as $productId => $qty) {
            $p = $products[$productId] ?? null;
            if (!$p) {
                $items[] = [
                    'product_id' => $productId, 'name' => '(produk tidak ditemukan)', 'sku' => '-',
                    'unit' => '', 'price' => '0.00', 'qty' => $qty, 'available' => 0,
                    'subtotal_cents' => 0, 'subtotal' => '0.00',
                    'problem' => 'Produk tidak ditemukan.',
                ];
                $issue = true;
                continue;
            }

            $available   = (int) inv_get_available_stock($pdo, $productId);
            $priceCents  = sales_to_cents(sales_trim_decimals((string) $p['selling_price']));
            $subCents    = $qty * $priceCents;
            $problem     = null;

            if ((int) $p['is_active'] !== 1) {
                $problem = 'Produk sudah tidak aktif.';
            } elseif ($qty > $available) {
                $problem = 'Stok berkurang: tersedia ' . $available . '.';
            }
            if ($problem !== null) {
                $issue = true;
            } else {
                $total += $subCents;
            }

            $items[] = [
                'product_id'     => $productId,
                'name'           => $p['name'],
                'sku'            => $p['sku'],
                'unit'           => $p['unit'] ?? '',
                'price'          => sales_from_cents($priceCents),
                'qty'            => $qty,
                'available'      => $available,
                'subtotal_cents' => $subCents,
                'subtotal'       => sales_from_cents($subCents),
                'problem'        => $problem,
            ];
        }
    }

    return [
        'items'       => $items,
        'total_cents' => $total,
        'total'       => sales_from_cents($total),
        'has_issue'   => $issue,
    ];
}

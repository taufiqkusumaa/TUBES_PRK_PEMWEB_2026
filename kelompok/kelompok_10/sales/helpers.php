<?php

const SALES_PAYMENT_METHODS = ['cash', 'transfer', 'qris', 'other'];
const SALES_PAYMENT_LABELS  = [
    'cash'     => 'Tunai',
    'transfer' => 'Transfer',
    'qris'     => 'QRIS',
    'other'    => 'Lainnya',
];
const SALES_STATUS_LABELS = [
    'completed' => 'Selesai',
    'voided'    => 'Dibatalkan',
];

function sales_url(string $file): string
{
    if (function_exists('app_url')) {
        return app_url('sales/' . ltrim($file, '/'));
    }
    return ltrim($file, '/');
}

function sales_asset_url(string $path): string
{
    $path = ltrim($path, '/');
    if (function_exists('app_url')) {
        return app_url($path);
    }
    return '../' . $path;
}

function sales_nav_items(): array
{
    return [
        ['key' => 'pos',     'label' => 'Kasir (POS)',        'href' => 'sales/index.php'],
        ['key' => 'history', 'label' => 'Riwayat Transaksi',  'href' => 'sales/history.php'],
    ];
}

function sales_css_tag(): string
{
    return '<link rel="stylesheet" href="' . e(sales_asset_url('assets/css/pos.css')) . '">';
}

function sales_js_tag(): string
{
    return '<script src="' . e(sales_asset_url('assets/js/pos.js')) . '" defer></script>';
}

function sales_redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function sales_to_cents(string $amount): int
{
    $amount = trim($amount);
    if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $amount)) {
        throw new InvalidArgumentException('Format nominal tidak valid (angka, maksimal 2 desimal).');
    }
    $parts = explode('.', $amount, 2);
    $whole = $parts[0];
    $frac  = str_pad($parts[1] ?? '', 2, '0');
    return ((int) $whole) * 100 + (int) $frac;
}

function sales_from_cents(int $cents): string
{
    $sign  = $cents < 0 ? '-' : '';
    $abs   = abs($cents);
    return $sign . intdiv($abs, 100) . '.' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
}

function sales_trim_decimals(string $value): string
{
    $value = trim($value);
    if (!preg_match('/^(\d+)(?:\.(\d*))?$/', $value, $m)) {
        throw new DomainException('Nilai uang dari database tidak valid: ' . $value);
    }
    $frac = substr(str_pad($m[2] ?? '', 2, '0'), 0, 2);
    return $m[1] . '.' . $frac;
}

function sales_rupiah_cents(int $cents): string
{
    $sign = $cents < 0 ? '-' : '';
    $abs  = abs($cents);
    $out  = 'Rp' . number_format(intdiv($abs, 100), 0, ',', '.');
    if ($abs % 100 !== 0) {
        $out .= ',' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }
    return $sign . $out;
}

function sales_rupiah(string|int|float|null $amount): string
{
    if ($amount === null || $amount === '') {
        return 'Rp0';
    }
    return sales_rupiah_cents(sales_to_cents(sales_trim_decimals((string) $amount)));
}

function sales_generate_invoice_no(): string
{
    return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function sales_payment_label(string $method): string
{
    return SALES_PAYMENT_LABELS[$method] ?? $method;
}

function sales_status_label(string $status): string
{
    return SALES_STATUS_LABELS[$status] ?? $status;
}

function sales_is_admin(): bool
{
    $user = current_user();
    return is_array($user) && ($user['role'] ?? '') === 'admin';
}

function sales_current_user_id(): int
{
    $user = current_user();
    return (int) ($user['id'] ?? 0);
}
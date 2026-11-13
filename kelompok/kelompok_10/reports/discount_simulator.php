<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

// Ambil daftar produk beserta rata-rata HPP dari batch aktifnya
$products = db()->query("
    SELECT 
        p.id,
        p.sku,
        p.name,
        p.unit,
        p.selling_price,
        c.name AS category_name,
        COALESCE(AVG(sb.cost_per_unit), p.selling_price * 0.7) AS avg_cost
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY p.name ASC
")->fetchAll();

$pageTitle = 'Simulasi Diskon & Analisis Margin Laba';
$activePage = 'analytics';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel narrow-page" style="max-width: 720px; margin: 0 auto;">
    <div class="panel-header">
        <div>
            <h2>Alat Simulasi Diskon & Proteksi Margin</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Uji skenario promosi harga tanpa risiko rugi di bawah harga pokok modal (HPP).</p>
        </div>
        <a href="<?= e(app_url('reports/index.php')) ?>" class="button button-secondary button-sm">Kembali</a>
    </div>

    <div class="notice notice-warning" style="margin-bottom: 20px;">
        <span style="font-size: 12px;">
            <strong>Catatan Keamanan:</strong> Fitur ini adalah simulator analitik keputusan. Hasil simulasi <em>tidak akan mengubah</em> harga jual produk di database kasir secara otomatis.
        </span>
    </div>

    <!-- Form Kontrol Simulasi -->
    <div class="form-stack" style="margin-bottom: 24px;">
        <div class="form-group">
            <label for="productSelect">Pilih Produk untuk Disimulasikan:</label>
            <select id="productSelect" onchange="onProductChange()">
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" 
                            data-name="<?= e($p['name']) ?>"
                            data-price="<?= (float) $p['selling_price'] ?>"
                            data-cost="<?= (float) $p['avg_cost'] ?>"
                            data-unit="<?= e($p['unit']) ?>">
                        <?= e($p['name']) ?> &mdash; Harga: Rp <?= number_format((float) $p['selling_price'], 0, ',', '.') ?> (HPP Modal: Rp <?= number_format((float) $p['avg_cost'], 0, ',', '.') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="discountPct">Persentase Diskon (%):</label>
                <input type="number" id="discountPct" min="0" max="100" step="1" value="10" placeholder="Contoh: 15" oninput="syncFromPct()">
            </div>
            <div class="form-group">
                <label for="discountNominal">Atau Potongan Nominal (Rp):</label>
                <input type="number" id="discountNominal" min="0" step="500" placeholder="Contoh: 5000" oninput="syncFromNominal()">
            </div>
        </div>
    </div>

    <!-- Alert Bahaya (Jika harga diskon < HPP) -->
    <div id="lossAlert" class="notice notice-error" style="display: none; margin-bottom: 20px;">
        <div>
            <strong>PERINGATAN DEFISIT MODAL!</strong>
            <span id="lossAlertText" style="display: block; font-size: 12px; margin-top: 4px;"></span>
        </div>
    </div>

    <!-- Panel Kartu Hasil Analisis Skenario -->
    <div class="panel" style="background: var(--paper); border: 1px solid var(--line); border-radius: var(--radius); padding: 20px;" id="resultPanel">
        <h3 style="margin-bottom: 14px; font-size: 15px;">Hasil Proyeksi Keuangan per Unit:</h3>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; font-size: 13px;">
            <div style="background: #fff; padding: 12px 14px; border-radius: 6px; border: 1px solid var(--line);">
                <span class="muted" style="font-size: 11px; display: block;">Harga Jual Awal:</span>
                <strong id="resOriginalPrice" style="font-size: 16px;">Rp 0</strong>
            </div>

            <div style="background: #fff; padding: 12px 14px; border-radius: 6px; border: 1px solid var(--line);">
                <span class="muted" style="font-size: 11px; display: block;">Nilai Potongan Diskon:</span>
                <strong id="resDiscountAmount" style="font-size: 16px; color: var(--danger);">-Rp 0</strong>
            </div>

            <div style="background: #fff; padding: 12px 14px; border-radius: 6px; border: 1px solid var(--line);">
                <span class="muted" style="font-size: 11px; display: block;">Harga Setelah Diskon (Konsumen):</span>
                <strong id="resFinalPrice" style="font-size: 18px; color: var(--primary);">Rp 0</strong>
            </div>

            <div style="background: #fff; padding: 12px 14px; border-radius: 6px; border: 1px solid var(--line);">
                <span class="muted" style="font-size: 11px; display: block;">Harga Pokok Pembelian (HPP):</span>
                <strong id="resHpp" style="font-size: 16px; color: #475569;">Rp 0</strong>
            </div>

            <div style="background: #fff; padding: 12px 14px; border-radius: 6px; border: 1px solid var(--line);">
                <span class="muted" style="font-size: 11px; display: block;">Laba Bersih per Unit:</span>
                <strong id="resProfitUnit" style="font-size: 16px;">Rp 0</strong>
            </div>

            <div style="background: #fff; padding: 12px 14px; border-radius: 6px; border: 1px solid var(--line);">
                <span class="muted" style="font-size: 11px; display: block;">Margin Laba Kotor:</span>
                <strong id="resMarginPct" style="font-size: 16px;">0.0%</strong>
            </div>
        </div>
    </div>
</div>

<script>
let currentPrice = 0;
let currentHpp = 0;
let currentUnit = 'pcs';

function formatRp(val) {
    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
}

function onProductChange() {
    const sel = document.getElementById('productSelect');
    const opt = sel.options[sel.selectedIndex];

    if (!opt || !opt.dataset.price) {
        currentPrice = 0;
        currentHpp = 0;
        currentUnit = 'pcs';
        calculate();
        return;
    }

    currentPrice = parseFloat(opt.dataset.price) || 0;
    currentHpp = parseFloat(opt.dataset.cost) || 0;
    currentUnit = opt.dataset.unit || 'pcs';

    syncFromPct();
}

function syncFromPct() {
    const pct = parseFloat(document.getElementById('discountPct').value) || 0;
    const nominal = (pct / 100) * currentPrice;
    document.getElementById('discountNominal').value = Math.round(nominal);
    calculate();
}

function syncFromNominal() {
    const nominal = parseFloat(document.getElementById('discountNominal').value) || 0;
    const pct = currentPrice > 0 ? (nominal / currentPrice) * 100 : 0;
    document.getElementById('discountPct').value = pct.toFixed(1);
    calculate();
}

function calculate() {
    const nominal = Math.min(currentPrice, parseFloat(document.getElementById('discountNominal').value) || 0);
    const finalPrice = Math.max(0, currentPrice - nominal);
    const profitUnit = finalPrice - currentHpp;
    const marginPct = finalPrice > 0 ? (profitUnit / finalPrice) * 100 : 0;

    document.getElementById('resOriginalPrice').textContent = formatRp(currentPrice);
    document.getElementById('resDiscountAmount').textContent = '- ' + formatRp(nominal);
    document.getElementById('resFinalPrice').textContent = formatRp(finalPrice);
    document.getElementById('resHpp').textContent = formatRp(currentHpp);

    const elProfit = document.getElementById('resProfitUnit');
    const elMargin = document.getElementById('resMarginPct');
    const alertBox = document.getElementById('lossAlert');
    const alertText = document.getElementById('lossAlertText');

    if (profitUnit < 0) {
        elProfit.textContent = '- ' + formatRp(Math.abs(profitUnit)) + ' (Rugi)';
        elProfit.style.color = 'var(--danger)';
        elMargin.textContent = marginPct.toFixed(1) + '% (Negatif)';
        elMargin.style.color = 'var(--danger)';

        alertBox.style.display = 'block';
        alertText.textContent = `Harga setelah diskon (${formatRp(finalPrice)}) berada di bawah HPP modal (${formatRp(currentHpp)}). Toko akan merugi sebesar ${formatRp(Math.abs(profitUnit))} per ${currentUnit} terjual!`;
    } else {
        elProfit.textContent = '+ ' + formatRp(profitUnit) + ' (Untung)';
        elProfit.style.color = 'var(--success-text)';
        elMargin.textContent = marginPct.toFixed(1) + '%';
        elMargin.style.color = 'var(--success-text)';

        alertBox.style.display = 'none';
    }
}

// Auto-pilih produk pertama saat load
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('productSelect');
    if (sel.options.length > 1) {
        sel.selectedIndex = 1;
        onProductChange();
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>


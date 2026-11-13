<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$categories = db()->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $sku = strtoupper(trim((string) ($_POST['sku'] ?? '')));
    $name = trim((string) ($_POST['name'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $unit = trim((string) ($_POST['unit'] ?? 'pcs'));
    $sellingPrice = (float) ($_POST['selling_price'] ?? 0);
    $minStock = (int) ($_POST['min_stock'] ?? 0);
    $imageUrl = trim((string) ($_POST['image_url'] ?? ''));

    if ($sku === '' || !preg_match('/^[A-Z0-9._-]{3,50}$/', $sku)) {
        $errorMessage = 'SKU harus terdiri dari 3–50 karakter alfanumerik (huruf kapital, angka, tanda hubung).';
    } elseif ($name === '' || mb_strlen($name) > 150) {
        $errorMessage = 'Nama produk wajib diisi dan maksimal 150 karakter.';
    } elseif ($categoryId <= 0) {
        $errorMessage = 'Pilih kategori produk yang valid.';
    } elseif ($sellingPrice < 0) {
        $errorMessage = 'Harga jual tidak boleh negatif.';
    } elseif ($minStock < 0) {
        $errorMessage = 'Batas stok minimum tidak boleh negatif.';
    } else {
        try {
            // Cek keunikan SKU
            $stmt = db()->prepare('SELECT id FROM products WHERE sku = :sku LIMIT 1');
            $stmt->execute(['sku' => $sku]);
            if ($stmt->fetch()) {
                $errorMessage = "SKU '{$sku}' sudah digunakan oleh produk lain. Gunakan SKU unik.";
            } else {
                $stmt = db()->prepare('
                    INSERT INTO products (category_id, sku, name, unit, selling_price, min_stock, image_url, is_active)
                    VALUES (:category_id, :sku, :name, :unit, :selling_price, :min_stock, :image_url, 1)
                ');
                $stmt->execute([
                    'category_id' => $categoryId,
                    'sku' => $sku,
                    'name' => $name,
                    'unit' => $unit !== '' ? $unit : 'pcs',
                    'selling_price' => $sellingPrice,
                    'min_stock' => $minStock,
                    'image_url' => $imageUrl !== '' ? $imageUrl : null,
                ]);

                flash('success', "Produk '{$name}' (SKU: {$sku}) berhasil ditambahkan.");
                redirect('products/index.php');
            }
        } catch (PDOException $e) {
            error_log('Error creating product: ' . $e->getMessage());
            $errorMessage = 'Terjadi kesalahan sistem saat menyimpan produk.';
        }
    }
}

// Generate default SKU suggestion
$suggestedSku = 'PRD-' . strtoupper(bin2hex(random_bytes(3)));

$pageTitle = 'Tambah Produk Baru';
$activePage = 'products';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel narrow-page" style="max-width: 720px; margin: 0 auto;">
    <div class="panel-header">
        <div>
            <h2>Tambah Produk Baru</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Daftarkan master item baru beserta foto ke dalam inventaris toko.</p>
        </div>
        <a href="<?= e(app_url('products/index.php')) ?>" class="button button-secondary button-sm">Kembali</a>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="sku">Kode SKU (Stock Keeping Unit) *</label>
                <input id="sku" name="sku" value="<?= e($_POST['sku'] ?? $suggestedSku) ?>" required maxlength="50" style="font-family: monospace;">
                <span class="input-hint">Harus unik. Contoh: SNACK-001, MIN-024</span>
            </div>
            <div class="form-group">
                <label for="category_id">Kategori Produk *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) ($_POST['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="name">Nama Produk *</label>
            <input id="name" name="name" value="<?= e($_POST['name'] ?? '') ?>" placeholder="Misal: Kopi Susu Gula Aren 250ml" required maxlength="150">
        </div>

        <!-- URL Foto Produk Internet & Preview -->
        <div class="form-group">
            <label for="image_url">URL Foto Produk (Internet / CDN)</label>
            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="flex: 1;">
                    <input id="image_url" name="image_url" value="<?= e($_POST['image_url'] ?? '') ?>" placeholder="https://images.unsplash.com/..." maxlength="500" oninput="updateImagePreview(this.value)">
                    <span class="input-hint">Gunakan tautan gambar online (Unsplash, CDN, atau web toko).</span>
                    
                    <!-- Preset Cepat Foto Internet -->
                    <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                        <small style="color: var(--ink-muted); font-size: 11px;">Pilihan Cepat:</small>
                        <button type="button" class="button button-secondary button-sm" style="padding: 2px 8px; font-size: 11px;" onclick="setPresetImg('https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=400&q=80')">Kopi</button>
                        <button type="button" class="button button-secondary button-sm" style="padding: 2px 8px; font-size: 11px;" onclick="setPresetImg('https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=400&q=80')">Snack Keripik</button>
                        <button type="button" class="button button-secondary button-sm" style="padding: 2px 8px; font-size: 11px;" onclick="setPresetImg('https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=400&q=80')">Beras</button>
                        <button type="button" class="button button-secondary button-sm" style="padding: 2px 8px; font-size: 11px;" onclick="setPresetImg('https://images.unsplash.com/photo-1510627489930-0c1b0bfb6785?auto=format&fit=crop&w=400&q=80')">Minyak Goreng</button>
                        <button type="button" class="button button-secondary button-sm" style="padding: 2px 8px; font-size: 11px;" onclick="setPresetImg('https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=400&q=80')">Susu Segar</button>
                        <button type="button" class="button button-secondary button-sm" style="padding: 2px 8px; font-size: 11px;" onclick="setPresetImg('https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=400&q=80')">Sambal UMKM</button>
                        <button type="button" class="button button-secondary button-sm" style="padding: 2px 8px; font-size: 11px;" onclick="setPresetImg('https://images.unsplash.com/photo-1571115177098-24ec42ed204d?auto=format&fit=crop&w=400&q=80')">Kue Lapis</button>
                    </div>
                </div>

                <div style="width: 72px; height: 72px; border-radius: var(--radius); border: 1px solid var(--line); overflow: hidden; background: #f1f5f9; display: grid; place-items: center; flex-shrink: 0;">
                    <img id="imgPreview" src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=150&q=80" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="selling_price">Harga Jual (Rp) *</label>
                <input id="selling_price" name="selling_price" type="number" min="0" step="100" value="<?= e((string) ($_POST['selling_price'] ?? '')) ?>" placeholder="15000" required>
            </div>
            <div class="form-group">
                <label for="unit">Satuan Penjualan</label>
                <input id="unit" name="unit" value="<?= e($_POST['unit'] ?? 'pcs') ?>" placeholder="pcs, botol, pack, kg" maxlength="30">
            </div>
            <div class="form-group">
                <label for="min_stock">Batas Minimum Stok</label>
                <input id="min_stock" name="min_stock" type="number" min="0" value="<?= e((string) ($_POST['min_stock'] ?? '5')) ?>" required>
                <span class="input-hint">Sistem memberi alert saat stok &le; angka ini.</span>
            </div>
        </div>

        <div style="margin-top: 14px; display: flex; justify-content: flex-end; gap: 10px;">
            <a href="<?= e(app_url('products/index.php')) ?>" class="button button-secondary">Batal</a>
            <button type="submit" class="button button-primary">Simpan Produk</button>
        </div>
    </form>
</div>

<script>
function updateImagePreview(url) {
    const preview = document.getElementById('imgPreview');
    if (url && url.startsWith('http')) {
        preview.src = url;
    } else {
        preview.src = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=150&q=80';
    }
}

function setPresetImg(url) {
    const input = document.getElementById('image_url');
    input.value = url;
    updateImagePreview(url);
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

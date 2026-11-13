<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$currentUser = current_user();
if ($currentUser['role'] === 'customer') {
    flash('info', 'Halaman Kasir POS hanya untuk Administrator dan Kasir Toko. Silakan jelajahi katalog produk.');
    redirect('dashboard.php');
}

// Ambil semua kategori aktif
$categories = db()->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC')->fetchAll();

// Ambil daftar pelanggan terdaftar untuk opsi member
$customers = db()->query("SELECT id, full_name, username FROM users WHERE role = 'customer' AND is_active = 1 ORDER BY full_name ASC")->fetchAll();

// Ambil semua produk aktif beserta total stok yang tersedia dan foto online
$products = db()->query("
    SELECT 
        p.id,
        p.category_id,
        p.sku,
        p.name,
        p.unit,
        p.selling_price,
        p.image_url,
        c.name AS category_name,
        COALESCE(SUM(sb.qty_remaining), 0) AS available_stock
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN stock_batches sb ON p.id = sb.product_id 
        AND sb.qty_remaining > 0 
        AND (sb.expiry_date IS NULL OR sb.expiry_date >= CURDATE())
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY p.name ASC
")->fetchAll();

$pageTitle = 'Kasir POS (Point of Sale)';
$activePage = 'pos';
require __DIR__ . '/../includes/header.php';
?>

<div class="pos-container">
    <!-- Katalog Produk (Kiri) -->
    <div class="pos-catalog">
        <!-- Filter dan Pencarian Cepat -->
        <div class="panel" style="padding: 16px 20px; margin-bottom: 0;">
            <div class="filter-bar" style="margin: 0;">
                <div class="filter-group" style="flex: 1;">
                    <input type="text" id="posSearch" placeholder="Cari nama barang atau scan kode SKU..." class="search-input" style="flex: 1;" autofocus>
                </div>
                <button type="button" class="button button-secondary button-sm" onclick="clearSearch()">Reset Filter</button>
            </div>
        </div>

        <!-- Category Navigation Pills ala NutrAmla & Majoo -->
        <div class="category-pills-bar" id="categoryPills">
            <button type="button" class="category-pill is-active" data-cat="">Semua Produk</button>
            <?php foreach ($categories as $cat): ?>
                <button type="button" class="category-pill" data-cat="<?= (int) $cat['id'] ?>">
                    <?= e($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid Produk ala NutrAmla (Ref: media_1794558006526_915c7056.jpg) -->
        <div class="pos-catalog-grid" id="posProductGrid">
            <?php foreach ($products as $p): 
                $stock = (int) $p['available_stock'];
                $isOut = $stock <= 0;
                $imageUrl = !empty($p['image_url']) ? $p['image_url'] : 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=400&q=80';
                $sellingPrice = (float) $p['selling_price'];
                $originalPrice = round($sellingPrice * 1.12 / 500) * 500;
                $savings = max(1000, $originalPrice - $sellingPrice);
                $reviewCount = 80 + (($p['id'] * 17) % 120);
            ?>
                <div class="nutramla-product-card product-card <?= $isOut ? 'is-out-of-stock' : '' ?>" 
                     data-id="<?= (int) $p['id'] ?>"
                     data-sku="<?= e($p['sku']) ?>"
                     data-name="<?= e($p['name']) ?>"
                     data-price="<?= $sellingPrice ?>"
                     data-unit="<?= e($p['unit']) ?>"
                     data-stock="<?= $stock ?>"
                     data-category="<?= (int) $p['category_id'] ?>"
                     data-image="<?= e($imageUrl) ?>">
                    
                    <!-- Foto Produk & Badges -->
                    <div class="nutramla-card-img-wrap">
                        <img src="<?= e($imageUrl) ?>" alt="<?= e($p['name']) ?>" class="nutramla-card-img" loading="lazy">
                        
                        <!-- Rating Badge (Top Left ala NutrAmla) -->
                        <span class="nutramla-card-rating">
                            <span class="stars">★★★★★</span>
                            <span class="count">(<?= $reviewCount ?>)</span>
                        </span>

                        <!-- Wishlist Button (Top Right ala NutrAmla) -->
                        <button type="button" class="nutramla-card-wishlist" onclick="toggleWishlist(event, this)" title="Tambah ke Favorit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            </svg>
                        </button>

                        <?php if ($isOut): ?>
                            <span class="nutramla-card-stock-badge out">Stok Habis</span>
                        <?php elseif ($stock <= 5): ?>
                            <span class="nutramla-card-stock-badge low">Sisa <?= $stock ?> <?= e($p['unit']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="nutramla-card-body">
                        <div>
                            <span class="product-card-category" style="margin-bottom: 4px;"><?= e($p['category_name'] ?? 'Umum') ?></span>
                            <h4 class="nutramla-card-title" title="<?= e($p['name']) ?>"><?= e($p['name']) ?></h4>
                        </div>

                        <!-- Baris Harga & Hemat (ala NutrAmla) -->
                        <div class="nutramla-card-price-row">
                            <span class="nutramla-card-price">Rp <?= number_format($sellingPrice, 0, ',', '.') ?></span>
                            <span class="nutramla-card-original-price">Rp <?= number_format($originalPrice, 0, ',', '.') ?></span>
                            <span class="nutramla-card-save-badge">Hemat Rp <?= number_format($savings, 0, ',', '.') ?></span>
                        </div>

                        <!-- Stepper & Tombol Add -->
                        <div class="nutramla-card-actions-row">
                            <div class="nutramla-card-stepper" onclick="event.stopPropagation()">
                                <button type="button" class="stepper-btn" onclick="stepCardQty(<?= (int) $p['id'] ?>, -1)" <?= $isOut ? 'disabled' : '' ?>>&minus;</button>
                                <input type="text" id="cardQty_<?= (int) $p['id'] ?>" class="stepper-input" value="1" readonly>
                                <button type="button" class="stepper-btn" onclick="stepCardQty(<?= (int) $p['id'] ?>, 1)" <?= $isOut ? 'disabled' : '' ?>>&plus;</button>
                            </div>
                            <button type="button" class="nutramla-card-add-btn" onclick="addWithQty(<?= (int) $p['id'] ?>)" <?= $isOut ? 'disabled' : '' ?> title="Tambah ke keranjang">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                </svg>
                                <span>Add</span>
                            </button>
                        </div>

                        <!-- Tombol Buy Now Full-Width (ala NutrAmla) -->
                        <button type="button" class="nutramla-card-buynow-btn" onclick="buyNow(<?= (int) $p['id'] ?>)" <?= $isOut ? 'disabled' : '' ?>>
                            <?= $isOut ? 'Stok Tidak Tersedia' : 'Buy Now' ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Panel Keranjang Belanja Bersih (Kanan) -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <div>
                <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2.5"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    Keranjang Belanja
                </h3>
                <small class="muted">Kasir: <?= e($currentUser['name']) ?></small>
            </div>
            <button type="button" class="button button-secondary button-sm" onclick="emptyCart()" title="Kosongkan Keranjang">Reset</button>
        </div>

        <!-- Pilih Pelanggan (Member / Walk-in) -->
        <div style="padding: 12px 18px; border-bottom: 1px solid var(--line); background: #FAFDFB;">
            <label for="posCustomerId" style="font-size: 11.5px; font-weight: 750; color: #475569; display: block; margin-bottom: 4px;">
                Pelanggan / Member:
            </label>
            <select id="posCustomerId" name="customer_id_select" style="width: 100%; font-size: 12.5px; padding: 6px 10px; border-radius: 8px;">
                <option value="">Pelanggan Umum (Non-Member / Walk-in)</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c['id'] ?>">
                        <?= e($c['full_name']) ?> (@<?= e($c['username']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Daftar Item Keranjang -->
        <div class="pos-cart-items" id="cartItemList">
            <div style="text-align: center; color: var(--ink-subtle); padding: 40px 10px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.4;"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <p style="margin: 0; font-size: 13px;">Keranjang masih kosong.<br>Klik produk di sebelah kiri untuk menambah belanjaan.</p>
            </div>
        </div>

        <!-- Footer Ringkasan & Tombol Checkout Pop-up -->
        <div class="pos-cart-footer">
            <div class="pos-total-row">
                <span>Subtotal Produk</span>
                <strong id="labelSubtotal">Rp 0</strong>
            </div>

            <div class="pos-total-row">
                <label for="inputDiscount" style="margin: 0; font-size: 13px; cursor: pointer;">Diskon Promosi (Rp)</label>
                <input type="number" id="inputDiscount" min="0" step="500" value="0" style="width: 110px; text-align: right; font-size: 13px; padding: 4px 8px; border-radius: 6px;" oninput="updateTotals()">
            </div>

            <div class="pos-total-row grand-total">
                <span>Total Tagihan</span>
                <span id="labelGrandTotal" style="color: #00B67A;">Rp 0</span>
            </div>

            <!-- Tombol Buka Modal Pop-up Checkout -->
            <button type="button" id="btnOpenCheckoutModal" class="button button-primary button-lg" style="width: 100%; justify-content: center; font-size: 15px; font-weight: 800; border-radius: 14px; gap: 10px; box-shadow: 0 4px 14px rgba(0, 182, 122, 0.3);" onclick="openCheckoutModal()" disabled>
                <span>Bayar Sekarang / Checkout</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
    </div>
</div>

<!-- ====================================================
     MODAL POPUP CHECKOUT & MULTI-METODE PEMBAYARAN
==================================================== -->
<div class="modal-backdrop" id="checkoutModal">
    <div class="modal-dialog" style="max-height: 88vh; display: flex; flex-direction: column; overflow: hidden;">
        <!-- Header Modal -->
        <div class="modal-header">
            <h3 class="modal-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Pembayaran Transaksi
            </h3>
            <button type="button" class="modal-close-btn" onclick="closeCheckoutModal()" title="Tutup Modal">&times;</button>
        </div>

        <form method="post" action="<?= e(app_url('sales/checkout.php')) ?>" id="posPaymentForm" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="cart_data" id="modalCartDataInput" value="{}">
            <input type="hidden" name="customer_id" id="modalCustomerIdInput" value="">
            <input type="hidden" name="discount" id="modalDiscountInput" value="0">
            <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="cash">

            <!-- Body Modal -->
            <div class="modal-body" style="overflow-y: auto; flex: 1; min-height: 0;">
                <!-- Highlight Total Banner -->
                <div class="checkout-summary-banner">
                    <div>
                        <div class="checkout-summary-label">Total yang Harus Dibayar</div>
                        <div style="font-size: 12px; color: #065F46; margin-top: 2px;" id="modalItemsCount">0 Item Produk</div>
                    </div>
                    <div class="checkout-summary-amount" id="modalGrandTotalDisplay">Rp 0</div>
                </div>

                <!-- 4 Pilihan Metode Pembayaran ala Sarah's Snacks & Majoo -->
                <label style="font-size: 12px; font-weight: 800; color: #475569; display: block; margin-bottom: 8px;">
                    Pilih Metode Pembayaran:
                </label>
                <div class="payment-methods-grid">
                    <div class="payment-method-card is-active" data-method="cash" onclick="selectPaymentMethod('cash')">
                        <span class="payment-method-icon">💵</span>
                        <span class="payment-method-title">Tunai (Cash)</span>
                    </div>
                    <div class="payment-method-card" data-method="qris" onclick="selectPaymentMethod('qris')">
                        <span class="payment-method-icon">📱</span>
                        <span class="payment-method-title">QRIS</span>
                    </div>
                    <div class="payment-method-card" data-method="transfer" onclick="selectPaymentMethod('transfer')">
                        <span class="payment-method-icon">🏦</span>
                        <span class="payment-method-title">Transfer Bank</span>
                    </div>
                    <div class="payment-method-card" data-method="debit" onclick="selectPaymentMethod('debit')">
                        <span class="payment-method-icon">💳</span>
                        <span class="payment-method-title">Debit / EDC</span>
                    </div>
                </div>

                <!-- Panel 1: Tunai (Cash) -->
                <div class="payment-panel is-active" id="panel-cash">
                    <div style="margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label for="inputAmountPaid" style="margin: 0; font-size: 12.5px; font-weight: 750; color: #1E293B;">
                                Nominal Uang Tunai Diterima (Rp):
                            </label>
                            <button type="button" class="button button-secondary button-sm" style="padding: 2px 10px; font-size: 11px;" onclick="setCashExact()">Uang Pas</button>
                        </div>
                        <input type="number" id="inputAmountPaid" name="amount_paid" min="0" step="1000" placeholder="0" style="width: 100%; font-size: 18px; font-weight: 800; text-align: right; border-radius: 10px; border: 1.5px solid #CBD5E1; padding: 10px 14px;" oninput="updateChange()">
                    </div>

                    <!-- Quick Cash Buttons -->
                    <div class="quick-cash-grid">
                        <button type="button" class="quick-cash-btn" onclick="addCash(10000)">+10.000</button>
                        <button type="button" class="quick-cash-btn" onclick="addCash(20000)">+20.000</button>
                        <button type="button" class="quick-cash-btn" onclick="addCash(50000)">+50.000</button>
                        <button type="button" class="quick-cash-btn" onclick="addCash(100000)">+100.000</button>
                    </div>

                    <!-- Status Kembalian -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; margin-top: 10px;">
                        <span style="font-weight: 700; font-size: 13px; color: #475569;">Uang Kembalian:</span>
                        <strong id="labelChange" style="font-size: 17px; font-weight: 900; color: #64748B;">Rp 0</strong>
                    </div>
                </div>

                <!-- Panel 2: QRIS -->
                <div class="payment-panel" id="panel-qris">
                    <div class="qris-display-box">
                        <div class="qris-header-badge">QRIS STANDAR NASIONAL INDONESIA</div>
                        
                        <div class="qris-qr-frame">
                            <!-- SVG QR Code Mockup -->
                            <svg width="160" height="160" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="100" height="100" fill="white"/>
                                <!-- Corners -->
                                <rect x="5" y="5" width="28" height="28" fill="#000" rx="3"/>
                                <rect x="9" y="9" width="20" height="20" fill="#fff" rx="2"/>
                                <rect x="13" y="13" width="12" height="12" fill="#00A896" rx="1"/>

                                <rect x="67" y="5" width="28" height="28" fill="#000" rx="3"/>
                                <rect x="71" y="9" width="20" height="20" fill="#fff" rx="2"/>
                                <rect x="75" y="13" width="12" height="12" fill="#00A896" rx="1"/>

                                <rect x="5" y="67" width="28" height="28" fill="#000" rx="3"/>
                                <rect x="9" y="71" width="20" height="20" fill="#fff" rx="2"/>
                                <rect x="13" y="75" width="12" height="12" fill="#00A896" rx="1"/>

                                <!-- Patterns -->
                                <rect x="38" y="10" width="8" height="8" fill="#000"/>
                                <rect x="50" y="10" width="6" height="6" fill="#00A896"/>
                                <rect x="38" y="24" width="6" height="6" fill="#000"/>
                                <rect x="52" y="24" width="8" height="8" fill="#000"/>

                                <rect x="10" y="38" width="6" height="6" fill="#000"/>
                                <rect x="22" y="38" width="8" height="8" fill="#00A896"/>
                                <rect x="36" y="36" width="10" height="10" fill="#000"/>
                                <rect x="52" y="38" width="8" height="8" fill="#000"/>
                                <rect x="68" y="38" width="10" height="10" fill="#00A896"/>
                                <rect x="84" y="38" width="6" height="6" fill="#000"/>

                                <rect x="38" y="50" width="8" height="8" fill="#00A896"/>
                                <rect x="50" y="50" width="12" height="12" fill="#000"/>
                                <rect x="68" y="54" width="8" height="8" fill="#000"/>
                                <rect x="82" y="52" width="8" height="8" fill="#00A896"/>

                                <rect x="38" y="68" width="10" height="10" fill="#000"/>
                                <rect x="54" y="68" width="8" height="8" fill="#00A896"/>
                                <rect x="68" y="68" width="10" height="10" fill="#000"/>
                                <rect x="84" y="68" width="6" height="6" fill="#000"/>

                                <rect x="42" y="84" width="8" height="8" fill="#000"/>
                                <rect x="58" y="82" width="10" height="10" fill="#000"/>
                                <rect x="74" y="84" width="8" height="8" fill="#00A896"/>
                            </svg>
                        </div>

                        <div style="font-size: 13px; font-weight: 700; color: #1E293B;">
                            Scan QR Code dengan BCA, GoPay, OVO, Dana, ShopeePay
                        </div>
                        <div class="qris-logos-row">
                            <span>BCA Mobile</span> &bull; <span>GoPay</span> &bull; <span>ShopeePay</span> &bull; <span>OVO</span> &bull; <span>Dana</span>
                        </div>
                    </div>

                    <div style="margin-top: 14px;">
                        <label for="qrisRefInput" style="font-size: 11.5px; font-weight: 750; color: #475569; display: block; margin-bottom: 4px;">
                            No. RRN / Referensi QRIS (Opsional):
                        </label>
                        <input type="text" id="qrisRefInput" placeholder="Contoh: RRN-98234123" style="width: 100%; font-size: 13px; border-radius: 8px;">
                    </div>
                </div>

                <!-- Panel 3: Transfer Bank -->
                <div class="payment-panel" id="panel-transfer">
                    <p style="font-size: 12.5px; color: #64748B; margin: 0 0 12px;">Silakan arahkan pembeli transfer ke salah satu rekening toko berikut:</p>
                    
                    <div class="bank-transfer-list">
                        <div class="bank-card-item">
                            <div class="bank-card-info">
                                <span class="bank-badge">BCA</span>
                                <span class="bank-account-num">829-012-3456</span>
                                <span class="bank-holder">a.n. SisaSense UMKM Indonesia</span>
                            </div>
                            <button type="button" class="button button-secondary button-sm" onclick="copyToClipboard('8290123456', this)">Salin</button>
                        </div>

                        <div class="bank-card-item">
                            <div class="bank-card-info">
                                <span class="bank-badge">Mandiri</span>
                                <span class="bank-account-num">132-00-9876543-2</span>
                                <span class="bank-holder">a.n. SisaSense UMKM Indonesia</span>
                            </div>
                            <button type="button" class="button button-secondary button-sm" onclick="copyToClipboard('1320098765432', this)">Salin</button>
                        </div>

                        <div class="bank-card-item">
                            <div class="bank-card-info">
                                <span class="bank-badge">BRI</span>
                                <span class="bank-account-num">0123-01-001234-53-0</span>
                                <span class="bank-holder">a.n. SisaSense UMKM Indonesia</span>
                            </div>
                            <button type="button" class="button button-secondary button-sm" onclick="copyToClipboard('012301001234530', this)">Salin</button>
                        </div>
                    </div>

                    <div style="margin-top: 14px;">
                        <label for="transferSenderInput" style="font-size: 11.5px; font-weight: 750; color: #475569; display: block; margin-bottom: 4px;">
                            Nama Pengirim / No. Referensi Transfer:
                        </label>
                        <input type="text" id="transferSenderInput" placeholder="Contoh: Budi Santoso / Ref #9871" style="width: 100%; font-size: 13px; border-radius: 8px;">
                    </div>
                </div>

                <!-- Panel 4: Kartu Debit / EDC -->
                <div class="payment-panel" id="panel-debit">
                    <div class="edc-form-grid" style="margin-bottom: 14px;">
                        <div>
                            <label for="edcBankSelect" style="font-size: 11.5px; font-weight: 750; color: #475569; display: block; margin-bottom: 4px;">Mesin EDC:</label>
                            <select id="edcBankSelect" style="width: 100%; font-size: 13px; border-radius: 8px;">
                                <option value="BCA">EDC BCA</option>
                                <option value="Mandiri">EDC Mandiri</option>
                                <option value="BRI">EDC BRI</option>
                                <option value="BNI">EDC BNI</option>
                            </select>
                        </div>
                        <div>
                            <label for="edcCardDigits" style="font-size: 11.5px; font-weight: 750; color: #475569; display: block; margin-bottom: 4px;">4 Digit Terakhir Kartu:</label>
                            <input type="text" id="edcCardDigits" maxlength="4" placeholder="1234" style="width: 100%; font-size: 13px; font-family: monospace; border-radius: 8px;">
                        </div>
                    </div>

                    <div>
                        <label for="edcApprovalCode" style="font-size: 11.5px; font-weight: 750; color: #475569; display: block; margin-bottom: 4px;">No. Approval Code EDC:</label>
                        <input type="text" id="edcApprovalCode" placeholder="Contoh: APPR-88219" style="width: 100%; font-size: 13px; border-radius: 8px;">
                    </div>
                </div>

                <!-- Catatan Transaksi Tambahan (Opsional) -->
                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px dashed #E2E8F0;">
                    <label for="modalNotesInput" style="font-size: 11.5px; font-weight: 750; color: #64748B; display: block; margin-bottom: 4px;">
                        Catatan Transaksi (Opsional):
                    </label>
                    <input type="text" id="modalNotesInput" name="notes" placeholder="Contoh: Dibungkus rapi, kantong belanja ramah lingkungan..." style="width: 100%; font-size: 12.5px; border-radius: 8px;">
                </div>
            </div>

            <!-- Footer Modal (Always Visible & Pinned) -->
            <div class="modal-footer" style="flex-shrink: 0; background: #FAFCFB; border-top: 1px solid #E2E8F0; padding: 14px 24px; display: flex; align-items: center; justify-content: flex-end; gap: 12px; z-index: 10;">
                <button type="button" class="button button-secondary" onclick="closeCheckoutModal()" style="border-radius: 12px;">Batal</button>
                <button type="submit" id="btnSubmitPayment" class="button button-primary button-lg" style="background: #166534; color: #FFFFFF; gap: 8px; font-size: 14px; font-weight: 800; border-radius: 12px; padding: 12px 24px; box-shadow: 0 4px 14px rgba(22, 101, 52, 0.3); border: none; cursor: pointer;">
                    <span>Konfirmasi Pembayaran &amp; Cetak Struk</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let cart = {};
let productsData = {};
let currentPaymentMethod = 'cash';

// Load product metadata dari data attributes
document.querySelectorAll('.nutramla-product-card, .product-card').forEach(el => {
    productsData[el.dataset.id] = {
        id: parseInt(el.dataset.id),
        sku: el.dataset.sku,
        name: el.dataset.name,
        price: parseFloat(el.dataset.price),
        unit: el.dataset.unit,
        maxStock: parseInt(el.dataset.stock),
        category: parseInt(el.dataset.category),
        image: el.dataset.image
    };
});

function stepCardQty(productId, delta) {
    const input = document.getElementById('cardQty_' + productId);
    if (!input) return;
    const p = productsData[productId];
    const max = p ? p.maxStock : 999;
    let current = parseInt(input.value) || 1;
    let next = current + delta;
    if (next < 1) next = 1;
    if (next > max) {
        alert(`Batas maksimal stok untuk ${p ? p.name : 'produk'} hanya ${max}.`);
        next = max;
    }
    input.value = next;
}

function addWithQty(productId) {
    const p = productsData[productId];
    if (!p || p.maxStock <= 0) return;
    const input = document.getElementById('cardQty_' + productId);
    const qtyToAdd = input ? (parseInt(input.value) || 1) : 1;

    if (!cart[productId]) {
        cart[productId] = { ...p, qty: Math.min(qtyToAdd, p.maxStock) };
    } else {
        const potential = cart[productId].qty + qtyToAdd;
        if (potential <= p.maxStock) {
            cart[productId].qty = potential;
        } else {
            alert(`Stok tersedia untuk ${p.name} hanya ${p.maxStock} ${p.unit}. Menambahkan sisa ${p.maxStock - cart[productId].qty} unit.`);
            cart[productId].qty = p.maxStock;
        }
    }
    renderCart();
    showToast(`Ditambahkan ke keranjang: ${qtyToAdd}x ${p.name}`);
}

function buyNow(productId) {
    const p = productsData[productId];
    if (!p || p.maxStock <= 0) return;
    addWithQty(productId);
    openCheckoutModal();
}

function toggleWishlist(event, btn) {
    event.stopPropagation();
    const svg = btn.querySelector('svg');
    if (!svg) return;
    const isFilled = svg.getAttribute('fill') === '#22C55E';
    if (isFilled) {
        svg.setAttribute('fill', 'none');
        showToast('Dihapus dari Favorit');
    } else {
        svg.setAttribute('fill', '#22C55E');
        showToast('Disimpan ke Favorit ❤️');
    }
}

function showToast(message) {
    let toast = document.getElementById('posToastNotice');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'posToastNotice';
        toast.style.position = 'fixed';
        toast.style.bottom = '24px';
        toast.style.right = '24px';
        toast.style.background = '#0F5132';
        toast.style.color = '#FFFFFF';
        toast.style.padding = '10px 20px';
        toast.style.borderRadius = '9999px';
        toast.style.boxShadow = '0 8px 24px rgba(15, 81, 50, 0.35)';
        toast.style.zIndex = '99999';
        toast.style.fontSize = '13.5px';
        toast.style.fontWeight = '750';
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        toast.style.pointerEvents = 'none';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
    }, 2200);
}

function addToCart(productId) {
    const p = productsData[productId];
    if (!p || p.maxStock <= 0) return;

    if (!cart[productId]) {
        cart[productId] = { ...p, qty: 1 };
    } else {
        if (cart[productId].qty < p.maxStock) {
            cart[productId].qty += 1;
        } else {
            alert(`Stok tersedia untuk ${p.name} hanya ${p.maxStock} ${p.unit}.`);
            return;
        }
    }
    renderCart();
}

function updateQty(productId, delta) {
    if (!cart[productId]) return;
    const item = cart[productId];
    const newQty = item.qty + delta;

    if (newQty <= 0) {
        delete cart[productId];
    } else if (newQty > item.maxStock) {
        alert(`Batas maksimal stok hanya ${item.maxStock} ${item.unit}.`);
    } else {
        item.qty = newQty;
    }
    renderCart();
}

function removeItem(productId) {
    if (cart[productId]) {
        delete cart[productId];
        renderCart();
    }
}

function emptyCart() {
    cart = {};
    renderCart();
}

function formatRp(val) {
    return 'Rp ' + Math.max(0, Math.round(val)).toLocaleString('id-ID');
}

function renderCart() {
    const list = document.getElementById('cartItemList');
    const keys = Object.keys(cart);
    const checkoutBtn = document.getElementById('btnOpenCheckoutModal');

    const topBadge = document.getElementById('nutramlaCartBadge');
    if (keys.length === 0) {
        if (topBadge) topBadge.textContent = '0';
        list.innerHTML = `
            <div style="text-align: center; color: var(--ink-subtle); padding: 40px 10px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.4;"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <p style="margin: 0; font-size: 13px;">Keranjang masih kosong.<br>Klik produk di sebelah kiri untuk menambah belanjaan.</p>
            </div>
        `;
        checkoutBtn.disabled = true;
        updateTotals();
        return;
    }

    const totalCount = keys.reduce((sum, id) => sum + (cart[id].qty || 1), 0);
    if (topBadge) topBadge.textContent = totalCount;

    checkoutBtn.disabled = false;
    let html = '';
    keys.forEach(id => {
        const item = cart[id];
        const lineTotal = item.qty * item.price;
        html += `
            <div class="cart-item">
                <img src="${item.image}" alt="${item.name}" class="cart-item-img">
                <div class="cart-item-info">
                    <div class="cart-item-name" title="${item.name}">${item.name}</div>
                    <div class="cart-item-price">${formatRp(item.price)} &times; ${item.qty} ${item.unit} = <strong>${formatRp(lineTotal)}</strong></div>
                </div>
                <div class="cart-item-qty">
                    <button type="button" onclick="updateQty(${id}, -1)">&minus;</button>
                    <span>${item.qty}</span>
                    <button type="button" onclick="updateQty(${id}, 1)">&plus;</button>
                    <button type="button" onclick="removeItem(${id})" style="color: #DC2626; margin-left: 2px;" title="Hapus item">&times;</button>
                </div>
            </div>
        `;
    });

    list.innerHTML = html;
    updateTotals();
}

function calculateGrandTotal() {
    let subtotal = 0;
    let totalItemsCount = 0;
    Object.values(cart).forEach(item => {
        subtotal += item.qty * item.price;
        totalItemsCount += item.qty;
    });
    const discount = Math.max(0, parseFloat(document.getElementById('inputDiscount').value) || 0);
    const grand = Math.max(0, subtotal - discount);
    return { subtotal, discount, grand, totalItemsCount };
}

function updateTotals() {
    const { subtotal, grand } = calculateGrandTotal();
    document.getElementById('labelSubtotal').textContent = formatRp(subtotal);
    document.getElementById('labelGrandTotal').textContent = formatRp(grand);
}

// Modal Checkout Logic
function openCheckoutModal() {
    const keys = Object.keys(cart);
    if (keys.length === 0) {
        alert('Keranjang belanja masih kosong.');
        return;
    }

    const { grand, discount, totalItemsCount } = calculateGrandTotal();
    const customerId = document.getElementById('posCustomerId').value;

    // Sinkronisasi data ke modal
    document.getElementById('modalCartDataInput').value = JSON.stringify(cart);
    document.getElementById('modalCustomerIdInput').value = customerId;
    document.getElementById('modalDiscountInput').value = discount;

    document.getElementById('modalGrandTotalDisplay').textContent = formatRp(grand);
    document.getElementById('modalItemsCount').textContent = `${keys.length} jenis produk (${totalItemsCount} total unit)`;

    // Reset default payment method ke Cash
    selectPaymentMethod('cash');
    document.getElementById('inputAmountPaid').value = grand; // default uang pas
    updateChange();

    const modal = document.getElementById('checkoutModal');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
        const cashInput = document.getElementById('inputAmountPaid');
        if (cashInput) cashInput.focus();
    }, 100);
}

function closeCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCheckoutModal();
    }
});

// Close when clicking backdrop outside dialog
document.getElementById('checkoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCheckoutModal();
    }
});

function selectPaymentMethod(method) {
    currentPaymentMethod = method;
    document.getElementById('selectedPaymentMethod').value = method;

    // Toggle tab active state
    document.querySelectorAll('.payment-method-card').forEach(card => {
        if (card.dataset.method === method) {
            card.classList.add('is-active');
        } else {
            card.classList.remove('is-active');
        }
    });

    // Toggle panel
    document.querySelectorAll('.payment-panel').forEach(panel => {
        panel.classList.remove('is-active');
    });
    const activePanel = document.getElementById('panel-' + method);
    if (activePanel) {
        activePanel.classList.add('is-active');
    }

    const btnSubmit = document.getElementById('btnSubmitPayment');
    const { grand } = calculateGrandTotal();

    if (method === 'cash') {
        updateChange();
    } else {
        // Non-tunai selalu dianggap lunas pas
        btnSubmit.disabled = false;
        document.getElementById('inputAmountPaid').value = grand;
    }
}

function updateChange() {
    if (currentPaymentMethod !== 'cash') return;

    const { grand } = calculateGrandTotal();
    const paid = parseFloat(document.getElementById('inputAmountPaid').value) || 0;
    const change = paid - grand;
    const labelChange = document.getElementById('labelChange');
    const btnSubmit = document.getElementById('btnSubmitPayment');

    if (paid >= grand && Object.keys(cart).length > 0) {
        labelChange.textContent = formatRp(change);
        labelChange.style.color = '#166534';
        btnSubmit.disabled = false;
        btnSubmit.style.opacity = '1';
        btnSubmit.style.cursor = 'pointer';
    } else {
        labelChange.textContent = paid > 0 ? 'Kurang ' + formatRp(Math.abs(change)) : 'Rp 0';
        labelChange.style.color = paid > 0 ? '#DC2626' : '#64748B';
        btnSubmit.disabled = true;
        btnSubmit.style.opacity = '0.6';
        btnSubmit.style.cursor = 'not-allowed';
    }
}

function setCashExact() {
    const { grand } = calculateGrandTotal();
    document.getElementById('inputAmountPaid').value = grand;
    updateChange();
}

function addCash(amount) {
    const current = parseFloat(document.getElementById('inputAmountPaid').value) || 0;
    document.getElementById('inputAmountPaid').value = current + amount;
    updateChange();
}

function copyToClipboard(text, btnElement) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = btnElement.textContent;
        btnElement.textContent = 'Tersalin!';
        btnElement.style.background = '#00B67A';
        btnElement.style.color = '#fff';
        setTimeout(() => {
            btnElement.textContent = originalText;
            btnElement.style.background = '';
            btnElement.style.color = '';
        }, 1500);
    });
}

// Append extra details to notes before form submit
document.getElementById('posPaymentForm').addEventListener('submit', function(e) {
    const method = currentPaymentMethod;
    const notesInput = document.getElementById('modalNotesInput');
    let extraNotes = [];

    if (method === 'qris') {
        const qrisRef = document.getElementById('qrisRefInput').value.trim();
        if (qrisRef) extraNotes.push('Ref QRIS: ' + qrisRef);
    } else if (method === 'transfer') {
        const sender = document.getElementById('transferSenderInput').value.trim();
        if (sender) extraNotes.push('Transfer: ' + sender);
    } else if (method === 'debit') {
        const edcBank = document.getElementById('edcBankSelect').value;
        const digits = document.getElementById('edcCardDigits').value.trim();
        const appCode = document.getElementById('edcApprovalCode').value.trim();
        let edcInfo = 'Debit ' + edcBank;
        if (digits) edcInfo += ' (x-' + digits + ')';
        if (appCode) edcInfo += ' Appr: ' + appCode;
        extraNotes.push(edcInfo);
    }

    if (extraNotes.length > 0) {
        const existing = notesInput.value.trim();
        notesInput.value = existing ? existing + ' | ' + extraNotes.join(', ') : extraNotes.join(', ');
    }
});

// Search and Category Filter
const searchInput = document.getElementById('posSearch');
let activeCategory = '';

function filterProducts() {
    const q = searchInput.value.toLowerCase().trim();

    document.querySelectorAll('.product-card').forEach(el => {
        const name = el.dataset.name.toLowerCase();
        const sku = el.dataset.sku.toLowerCase();
        const category = el.dataset.category;

        const matchesQ = q === '' || name.includes(q) || sku.includes(q);
        const matchesCat = activeCategory === '' || category === activeCategory;

        el.style.display = (matchesQ && matchesCat) ? 'flex' : 'none';
    });
}

searchInput.addEventListener('input', filterProducts);

document.querySelectorAll('.category-pill').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-pill').forEach(b => b.classList.remove('is-active'));
        this.classList.add('is-active');
        activeCategory = this.dataset.cat || '';
        filterProducts();
    });
});

function clearSearch() {
    searchInput.value = '';
    activeCategory = '';
    document.querySelectorAll('.category-pill').forEach((b, idx) => {
        if (idx === 0) b.classList.add('is-active');
        else b.classList.remove('is-active');
    });
    filterProducts();
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

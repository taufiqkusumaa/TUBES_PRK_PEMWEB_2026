        </main>
        <!-- ======================================================== -->
        <!-- 4-COLUMN E-COMMERCE FOOTER PERSIS NUTRAMLA               -->
        <!-- ======================================================== -->
        <footer class="nutramla-footer no-print">
            <div class="nutramla-footer-inner">
                <div class="nutramla-footer-grid">
                    <!-- Kolom 1: Brand & Filosofi -->
                    <div class="nutramla-footer-col">
                        <div class="nutramla-footer-brand-wrap">
                            <div class="nutramla-badge-frame" style="width: 140px; margin-bottom: 14px;">
                                <div class="nutramla-badge-top" style="padding: 3px 8px;">
                                    <span class="nutramla-badge-corp" style="font-size: 9px;">UMKM NUSANTARA<sup>&reg;</sup></span>
                                </div>
                                <div class="nutramla-badge-bottom" style="padding: 4px 10px;">
                                    <span class="nutramla-badge-brand" style="font-size: 15px;">SisaSense<sup>&reg;</sup></span>
                                </div>
                            </div>
                        </div>
                        <p class="nutramla-footer-text">
                            SisaSense adalah platform perdagangan dan kasir modern untuk produk komoditas sembako, minuman sehat, madu murni, serta camilan autentik UMKM Nusantara dengan jaminan kualitas alokasi FEFO (First-Expired, First-Out).
                        </p>
                        <div class="nutramla-social-pills">
                            <span class="nutramla-pill">100% Produk Halal &amp; Higienis</span>
                            <span class="nutramla-pill">Alokasi Stok FEFO Real-Time</span>
                        </div>
                    </div>

                    <!-- Kolom 2: Shop Collections -->
                    <div class="nutramla-footer-col">
                        <h4 class="nutramla-footer-title">Koleksi Pilihan</h4>
                        <ul class="nutramla-footer-links">
                            <li><a href="<?= e(app_url('products/index.php')) ?>">Camilan &amp; Aneka Snack</a></li>
                            <li><a href="<?= e(app_url('products/index.php')) ?>">Pangan Pokok &amp; Sembako</a></li>
                            <li><a href="<?= e(app_url('products/index.php')) ?>">Minuman Segar &amp; Madu Murni</a></li>
                            <li><a href="<?= e(app_url('products/index.php')) ?>">Oleh-Oleh Khas Tradisional</a></li>
                            <li><a href="<?= e(app_url('products/index.php')) ?>">Semua Produk Katalog &rarr;</a></li>
                        </ul>
                    </div>

                    <!-- Kolom 3: Navigasi Sistem & Layanan -->
                    <div class="nutramla-footer-col">
                        <h4 class="nutramla-footer-title">Navigasi Sistem</h4>
                        <ul class="nutramla-footer-links">
                            <li><a href="<?= e(app_url('dashboard.php')) ?>">Beranda Toko</a></li>
                            <li><a href="<?= e(app_url('sales/pos.php')) ?>">Kasir POS &amp; Cetak Struk</a></li>
                            <li><a href="<?= e(app_url('products/categories.php')) ?>">Kelola Kategori Barang</a></li>
                            <li><a href="<?= e(app_url('inventory/index.php')) ?>">Batch Stok &amp; Kedaluwarsa</a></li>
                            <li><a href="<?= e(app_url('reports/index.php')) ?>">Analitik Penjualan &amp; Margin</a></li>
                        </ul>
                    </div>

                    <!-- Kolom 4: Operasional & Bantuan -->
                    <div class="nutramla-footer-col">
                        <h4 class="nutramla-footer-title">Jam Operasional &amp; Bantuan</h4>
                        <p class="nutramla-footer-text">
                            <strong>Toko Fisik &amp; Kasir POS Buka:</strong><br>
                            Senin &ndash; Minggu: 08:00 &ndash; 21:00 WIB<br>
                            Sentra Kuliner &amp; UMKM Nusantara
                        </p>
                        <p class="nutramla-footer-text" style="margin-top: 10px;">
                            <strong>Layanan Pelanggan:</strong><br>
                            WhatsApp / Telp: (0721) 701605<br>
                            Email: halo@sisasense.id
                        </p>
                        <div class="nutramla-payments-row" title="Metode Pembayaran Resmi SisaSense">
                            <span class="pay-badge">Tunai</span>
                            <span class="pay-badge">QRIS</span>
                            <span class="pay-badge">Transfer</span>
                            <span class="pay-badge">Debit/EDC</span>
                        </div>
                    </div>
                </div>

                <div class="nutramla-footer-bottom">
                    <div class="nutramla-footer-copy">
                        &copy; <?= date('Y') ?> <strong>SisaSense</strong> &bull; Praktikum Pemrograman Web Kelompok 10 &bull; Hak Cipta Dilindungi.
                    </div>
                    <div class="nutramla-footer-badge-list">
                        <span>Pangan Segar Nusantara</span>
                        <span>&bull;</span>
                        <span>Struk Belanja Thermal 80mm</span>
                        <span>&bull;</span>
                        <span>Multi-Role Terproteksi</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<script>
// Dropdown menu toggle untuk profil akun di navbar NutrAmla
document.addEventListener('DOMContentLoaded', function() {
    const userBtn = document.getElementById('nutramlaUserBtn');
    const userMenu = document.getElementById('nutramlaUserMenu');
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = userMenu.classList.toggle('is-open');
            userBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target) && e.target !== userBtn) {
                userMenu.classList.remove('is-open');
                userBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Sinkronisasi badge keranjang dari POS sessionStorage/localStorage jika ada
    try {
        const cartBadge = document.getElementById('nutramlaCartBadge');
        if (cartBadge) {
            const posCartData = sessionStorage.getItem('sisasense_pos_cart');
            if (posCartData) {
                const parsed = JSON.parse(posCartData);
                const count = Array.isArray(parsed) ? parsed.reduce((sum, item) => sum + (item.qty || 1), 0) : 0;
                cartBadge.textContent = count;
            }
        }
    } catch(err) {}
});
</script>
</body>
</html>

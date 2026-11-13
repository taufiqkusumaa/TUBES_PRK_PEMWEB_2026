<?php
// app/Views/inventaris.php
// CRUD Inventaris + transaksi masuk/keluar. Stok memakai <meter>, bukan kartu.
$title = "Manajemen Inventaris";
require_once __DIR__ . "/layouts/header.php";
?>
<header class="page-header">
    <h1 id="judul-inventaris">Stok kebun</h1>
    <p class="top-label">Catatan sarana produksi · <time datetime="<?= date('Y-m-d') ?>"><?= htmlspecialchars($tanggalPanjang) ?></time></p>
    <p class="intro">Bibit, pupuk, dan pestisida. Barang yang hampir habis tampil paling atas, lengkap dengan pencatatan transaksi masuk dan keluar.</p>
    <p class="actions">
        <button class="btn" type="button" data-modal-open="#modalInventaris">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah item
        </button>
    </p>
</header>

<div class="table-wrap">
    <table id="tabelInventaris">
        <caption class="sr-only">Daftar sarana produksi beserta stok, satuan, dan harga satuan</caption>
        <thead>
            <tr>
                <th scope="col">No</th>
                <th scope="col">Nama item</th>
                <th scope="col">Kategori</th>
                <th scope="col">Stok</th>
                <th scope="col">Harga satuan</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr id="placeholderInventaris"><td colspan="6" class="text-center">Memuat data inventaris...</td></tr>
        </tbody>
    </table>
</div>

<dialog class="modal" id="modalInventaris" aria-labelledby="modalInventarisTitle">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalInventarisTitle">Tambah Item</h2>
            <button class="btn-close" type="button" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <form id="formInventaris" class="modal-form">
            <input type="hidden" name="id" id="inventarisId">
            <p id="inventarisError" class="form-error" role="alert" hidden></p>
            <div class="form-group">
                <label for="inventarisNama">Nama item</label>
                <input type="text" class="form-control" name="nama" id="inventarisNama" autocomplete="off" required>
                <span class="form-hint">Contoh: Pupuk NPK, bibit cabai rawit.</span>
            </div>
            <div class="form-group">
                <label for="inventarisKategori">Kategori</label>
                <select class="form-select" name="kategori" id="inventarisKategori" required>
                    <option value="">Pilih kategori...</option>
                    <option value="Bibit">Bibit</option>
                    <option value="Pupuk">Pupuk</option>
                    <option value="Pestisida">Pestisida</option>
                    <option value="Alat">Alat</option>
                </select>
            </div>
            <div class="form-group">
                <label for="inventarisStok">Stok awal</label>
                <input type="number" class="form-control" name="stok" id="inventarisStok" inputmode="decimal" required min="0" step="0.01">
            </div>
            <div class="form-group">
                <label for="inventarisSatuan">Satuan</label>
                <input type="text" class="form-control" name="satuan" id="inventarisSatuan" autocomplete="off" required placeholder="Contoh: kg, liter, sak">
            </div>
            <div class="form-group">
                <label for="inventarisHargaSatuan">Harga satuan (Rp)</label>
                <input type="number" class="form-control" name="harga_satuan" id="inventarisHargaSatuan" inputmode="numeric" required min="0" step="0.01">
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
            <button class="btn" type="submit" form="formInventaris">Simpan item</button>
        </div>
    </div>
</dialog>

<dialog class="modal" id="modalTransaksi" aria-labelledby="modalTransaksiTitle">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTransaksiTitle">Catat Transaksi Barang</h2>
            <button class="btn-close" type="button" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <form id="formTransaksi" class="modal-form">
            <input type="hidden" name="inventaris_id" id="transaksiInventarisId">
            <p id="transaksiError" class="form-error" role="alert" hidden></p>
            <div class="form-group">
                <label for="transaksiNamaItem">Nama barang</label>
                <input type="text" class="form-control" id="transaksiNamaItem" readonly>
            </div>
            <div class="form-group">
                <label for="transaksiJenis">Jenis transaksi</label>
                <select class="form-select" name="jenis" id="transaksiJenis" required>
                    <option value="">Pilih jenis transaksi...</option>
                    <option value="Masuk">Barang masuk (kulakan / restock)</option>
                    <option value="Keluar">Barang keluar (pemakaian ke lahan)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="transaksiTanggal">Tanggal transaksi</label>
                <input type="date" class="form-control" name="tanggal" id="transaksiTanggal" required>
            </div>
            <div class="form-group">
                <label for="transaksiJumlah">Jumlah</label>
                <input type="number" class="form-control" name="jumlah" id="transaksiJumlah" inputmode="decimal" required min="0.01" step="0.01">
            </div>
            <div class="form-group" id="grupSiklusTanam" hidden>
                <label for="transaksiSiklusId">Siklus tanam</label>
                <select class="form-select" name="siklus_id" id="transaksiSiklusId">
                    <option value="">Memuat data siklus...</option>
                </select>
                <span class="form-hint">Wajib diisi saat mencatat pemakaian untuk lahan.</span>
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
            <button class="btn" type="submit" form="formTransaksi">Simpan transaksi</button>
        </div>
    </div>
</dialog>

<script src="/assets/js/inventaris.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>


<?php
/**
 * View: Dokumentasi Komponen & Catatan Teknis Suku Cadang.
 *
 * Layout memakai header/sidebar/footer milik Ezzra (sidebar membuka
 * <main class="main-content">), jadi view ini tidak membuat <main> sendiri.
 * Data diambil lewat fetch() ke api.php?endpoint=sparepart dan endpoint=mekanik
 * (lihat assets/js/sparepart.js).
 */
$pageTitle = 'Dokumentasi Komponen & Suku Cadang';
require_once __DIR__ . '/layouts/header.php';
require_once __DIR__ . '/layouts/sidebar.php';
?>

<style>
    /* Gaya khusus modul Teknisi. Memakai variabel tema dari style.css agar ikut mode gelap/terang. */
    #sparepart-app .form-text, .wo-muted { color: var(--text-muted); }
    .wo-box { background-color: var(--surface-subtle); border: 1px solid var(--border-color); border-radius: 6px; }
    .part-thumb { width: 52px; height: 52px; object-fit: cover; border-radius: 6px; cursor: zoom-in; border: 1px solid var(--border-color); }
    .part-preview { max-height: 200px; object-fit: contain; }
    .part-catatan { max-width: 280px; white-space: pre-line; }
    .wo-sum-row { display: flex; justify-content: space-between; padding: .6rem 1rem; border-bottom: 1px solid var(--border-color); }
    .wo-sum-row:last-child { border-bottom: 0; background-color: var(--surface-subtle); }

    .wo-modal .modal-content { background-color: var(--surface); color: var(--text-primary); border: 1px solid var(--border-color); }
    .wo-modal .modal-header, .wo-modal .modal-footer { border-color: var(--border-color); }
    [data-theme="dark"] .wo-modal .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
    .wo-modal .form-text { color: var(--text-muted); }
    .was-validated .input-enterprise:invalid, .input-enterprise.is-invalid { border-color: #DC2626 !important; }

    .alert.wo-alert-success { background-color: var(--st-success-bg); color: var(--st-success-text); border-color: var(--st-success-border); }
    .alert.wo-alert-info    { background-color: var(--st-process-bg); color: var(--st-process-text); border-color: var(--st-process-border); }
    .alert.wo-alert-danger  { background-color: #FEF2F2; color: #B91C1C; border-color: #FECACA; }
    [data-theme="dark"] .alert.wo-alert-danger { background-color: rgba(239,68,68,.12); color: #FCA5A5; border-color: rgba(239,68,68,.28); }
    [data-theme="dark"] .alert .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
</style>

<div id="sparepart-app">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
        <div>
            <h5 class="fw-bold mb-1" style="color: var(--text-primary); letter-spacing: -0.01em;">Dokumentasi Komponen &amp; Suku Cadang</h5>
            <div style="color: var(--text-secondary); font-size: 13px;">Catat komponen yang diganti atau diperbaiki beserta foto dan catatan teknisnya.</div>
        </div>
        <a href="index.php?page=mekanik" class="btn-surface text-decoration-none mt-2 mt-sm-0">&larr; Antrean Work Order</a>
    </div>

    <div id="alertArea" aria-live="polite"></div>

    <div class="surface-card p-3 mb-3">
        <label for="pilihWO" class="form-label fw-semibold" style="font-size: 13px;">Work order</label>
        <select id="pilihWO" class="form-select input-enterprise">
            <option value="">Memuat daftar work order...</option>
        </select>
        <div class="form-text">Komponen hanya dapat ditambah, diubah, atau dihapus saat status work order "Dikerjakan".</div>
    </div>

    <div id="panelWO" class="d-none">
        <div class="surface-card p-3 mb-3">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="fw-bold font-mono" id="woKode" style="font-size: 14px;"></span>
                        <span id="woStatus"></span>
                    </div>
                    <dl class="row mb-0 small" id="woInfo"></dl>
                </div>
                <div class="col-lg-4">
                    <div class="wo-box overflow-hidden">
                        <div class="wo-sum-row"><span class="wo-muted">Tarif dasar paket</span><strong class="font-mono" id="sumTarif">-</strong></div>
                        <div class="wo-sum-row"><span class="wo-muted">Total suku cadang</span><strong class="font-mono" id="sumKomponen">-</strong></div>
                        <div class="wo-sum-row"><span class="fw-semibold">Total biaya servis</span><strong class="font-mono" id="sumTotal">-</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="surface-card overflow-hidden">
            <div class="p-3 d-flex justify-content-between align-items-center border-bottom" style="border-color: var(--border-color) !important;">
                <div class="fw-semibold" style="font-size: 14px; color: var(--text-primary);">
                    Daftar komponen <span class="plate-badge ms-1" id="jumlahItem">0</span>
                </div>
                <button type="button" id="btnTambah" class="btn-royal d-none">+ Tambah Komponen</button>
            </div>
            <div id="infoTerkunci" class="alert wo-alert-info m-3 mb-0 d-none"></div>
            <div class="table-responsive">
                <table class="table table-enterprise">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Suku cadang</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Harga satuan</th>
                            <th class="text-end">Subtotal</th>
                            <th>Catatan teknis</th>
                            <th class="text-end" id="kolomAksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyKomponen"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal tambah / ubah komponen -->
<div class="modal fade wo-modal" id="modalKomponen" tabindex="-1" aria-labelledby="judulKomponen" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="formKomponen" novalidate enctype="multipart/form-data">
            <div class="modal-header">
                <h2 class="modal-title h6 fw-bold mb-0" id="judulKomponen">Tambah Komponen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="fId">
                <input type="hidden" name="transaksi_id" id="fTransaksi">

                <div class="row g-3">
                    <div class="col-12">
                        <label for="fNama" class="form-label">Nama suku cadang</label>
                        <input type="text" class="form-control input-enterprise" id="fNama" name="nama_suku_cadang"
                               maxlength="100" required placeholder="Contoh: Busi NGK Iridium">
                        <div class="invalid-feedback">Nama suku cadang wajib diisi.</div>
                    </div>
                    <div class="col-sm-4">
                        <label for="fJumlah" class="form-label">Jumlah</label>
                        <input type="number" class="form-control input-enterprise" id="fJumlah" name="jumlah"
                               min="1" max="999" step="1" value="1" required>
                        <div class="invalid-feedback">Isi bilangan bulat 1 - 999.</div>
                    </div>
                    <div class="col-sm-8">
                        <label for="fHarga" class="form-label">Harga satuan (Rp)</label>
                        <input type="number" class="form-control input-enterprise" id="fHarga" name="harga_satuan"
                               min="0" max="99999999" step="any" required>
                        <div class="invalid-feedback">Isi harga satuan (0 atau lebih).</div>
                    </div>
                    <div class="col-12">
                        <label for="fCatatan" class="form-label">Catatan teknis</label>
                        <textarea class="form-control input-enterprise" id="fCatatan" name="catatan_perbaikan" rows="3"
                                  maxlength="1000"
                                  placeholder="Kondisi komponen lama dan alasan penggantian/perbaikan"></textarea>
                    </div>
                    <div class="col-md-7">
                        <label for="fFoto" class="form-label">Foto komponen <span class="wo-muted">(JPG/PNG/WebP, maks. 2 MB)</span></label>
                        <input type="file" class="form-control input-enterprise" id="fFoto" name="foto_komponen"
                               accept="image/jpeg,image/png,image/webp">
                        <div class="invalid-feedback" id="fFotoError"></div>
                        <div class="form-text d-none" id="fFotoHint">Kosongkan jika tidak ingin mengganti foto.</div>
                    </div>
                    <div class="col-md-5 text-center">
                        <div class="small wo-muted mb-1">Pratinjau</div>
                        <div class="wo-box p-2">
                            <img id="previewFoto" class="img-fluid part-preview d-none" alt="Pratinjau foto komponen">
                            <span id="previewKosong" class="wo-muted small">Belum ada foto</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-surface" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-royal" id="btnSimpanKomponen">
                    <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal konfirmasi hapus -->
<div class="modal fade wo-modal" id="modalHapus" tabindex="-1" aria-labelledby="judulHapus" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h6 fw-bold mb-0" id="judulHapus">Hapus komponen?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                Komponen <strong id="hapusNama"></strong> akan dihapus beserta fotonya, dan total biaya servis dihitung ulang.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-surface" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnKonfirmasiHapus">Hapus</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal lihat foto besar -->
<div class="modal fade wo-modal" id="modalFoto" tabindex="-1" aria-label="Foto komponen" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body text-center">
                <img id="fotoBesar" class="img-fluid" alt="Foto komponen">
            </div>
        </div>
    </div>
</div>

<script src="assets/js/sparepart.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
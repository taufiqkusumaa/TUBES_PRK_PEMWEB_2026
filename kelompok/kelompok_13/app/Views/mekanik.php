<?php
/**
 * View: Antrean Work Order Teknisi & Form Update Status.
 *
 * Layout memakai header/sidebar/footer milik Ezzra (header membuka .app-wrapper,
 * sidebar membuka <main class="main-content">, footer menutupnya), jadi view ini
 * tidak membuat <main> sendiri. Data diambil lewat fetch() ke
 * api.php?endpoint=mekanik (lihat assets/js/mekanik.js).
 */
$pageTitle = 'Work Order Teknisi & Progres Lapangan';
require_once __DIR__ . '/layouts/header.php';
require_once __DIR__ . '/layouts/sidebar.php';
?>

<style>
    /* Gaya khusus modul Teknisi. Memakai variabel tema dari style.css agar ikut mode gelap/terang. */
    #mekanik-app .form-text, .wo-muted { color: var(--text-muted); }
    .wo-box { background-color: var(--surface-subtle); border: 1px solid var(--border-color); border-radius: 6px; }
    .wo-foto { max-height: 220px; object-fit: contain; }
    .wo-keluhan { max-width: 260px; }

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

    /* Stepper status pengerjaan */
    .wo-stepper { display: flex; align-items: flex-start; margin: 0; padding: 0; list-style: none; }
    .wo-stepper li { flex: 1; text-align: center; position: relative; font-size: 12px; color: var(--text-secondary); }
    .wo-stepper li::before {
        content: attr(data-no); display: flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; margin: 0 auto 6px; border-radius: 50%;
        background: var(--border-color); color: var(--text-secondary); font-weight: 600; position: relative; z-index: 1;
    }
    .wo-stepper li::after {
        content: ""; position: absolute; top: 14px; left: -50%; width: 100%; height: 3px;
        background: var(--border-color); z-index: 0;
    }
    .wo-stepper li:first-child::after { display: none; }
    .wo-stepper li.done::before { background: var(--st-success-dot); color: #fff; content: "\2713"; }
    .wo-stepper li.done::after, .wo-stepper li.active::after { background: var(--st-success-dot); }
    .wo-stepper li.active::before { background: var(--accent-blue); color: #fff; }
    .wo-stepper li.active { color: var(--text-primary); font-weight: 600; }
</style>

<div id="mekanik-app" data-sparepart-url="index.php?page=sparepart">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
        <div>
            <h5 class="fw-bold mb-1" style="color: var(--text-primary); letter-spacing: -0.01em;">Antrean Work Order</h5>
            <div style="color: var(--text-secondary); font-size: 13px;">Pantau pekerjaan masuk dan perbarui progres pengerjaan kendaraan.</div>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="index.php?page=sparepart" class="btn-surface text-decoration-none">Suku Cadang</a>
            <button type="button" id="btnMuatUlang" class="btn-royal">
                <span class="spinner-border spinner-border-sm d-none" id="spinMuat" aria-hidden="true"></span>
                Muat Ulang
            </button>
        </div>
    </div>

    <div id="alertArea" aria-live="polite"></div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Menunggu (Antre)</div>
                <div class="metric-value font-mono" id="statAntre">-</div>
                <div class="metric-meta">Belum mulai dikerjakan</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Sedang Dikerjakan</div>
                <div class="metric-value font-mono" id="statDikerjakan">-</div>
                <div class="metric-meta">Unit aktif di stall servis</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Siap Diambil</div>
                <div class="metric-value font-mono" id="statSiap">-</div>
                <div class="metric-meta">Menunggu pelanggan</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Selesai Hari Ini</div>
                <div class="metric-value font-mono" id="statSelesaiHariIni">-</div>
                <div class="metric-meta">Siap diambil atau selesai</div>
            </div>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="p-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom" style="border-color: var(--border-color) !important;">
            <div class="fw-semibold" style="font-size: 14px; color: var(--text-primary);">Daftar Work Order</div>
            <div class="d-flex flex-wrap gap-2">
                <select class="input-enterprise form-select" id="filterStatus" style="width: 235px;" aria-label="Filter status">
                    <option value="">Aktif (Antre, Dikerjakan, Siap)</option>
                    <option value="Antre">Antre</option>
                    <option value="Dikerjakan">Dikerjakan</option>
                    <option value="Siap Diambil">Siap Diambil</option>
                    <option value="Selesai">Selesai</option>
                    <option value="semua">Semua</option>
                </select>
                <input type="search" class="input-enterprise form-control" id="cariWO" style="width: 250px;"
                       placeholder="Cari kode / nopol / nama..." aria-label="Cari work order">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-enterprise">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Kendaraan</th>
                        <th>Paket &amp; Durasi</th>
                        <th>Keluhan</th>
                        <th>Status</th>
                        <th>Teknisi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbodyWO">
                    <tr><td colspan="7" class="text-center py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal detail & update status -->
<div class="modal fade wo-modal" id="modalDetail" tabindex="-1" aria-labelledby="judulDetail" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h6 fw-bold mb-0" id="judulDetail">Detail Work Order</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="detailStepper" class="mb-4"></div>

                <div class="row g-3">
                    <div class="col-md-7">
                        <dl class="row mb-0" id="detailInfo"></dl>
                    </div>
                    <div class="col-md-5">
                        <div class="small wo-muted mb-1">Foto kendala</div>
                        <div id="detailFoto" class="wo-box p-2 text-center"></div>
                    </div>
                </div>

                <hr style="border-color: var(--border-color); opacity: 1;">

                <form id="formStatus" class="row g-2 align-items-end" novalidate>
                    <div class="col-sm-8">
                        <label for="statusBaru" class="form-label">Perbarui status pengerjaan</label>
                        <select id="statusBaru" class="form-select input-enterprise" required></select>
                        <div class="invalid-feedback">Pilih status berikutnya.</div>
                    </div>
                    <div class="col-sm-4 d-grid">
                        <button type="submit" id="btnSimpanStatus" class="btn-royal">
                            <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                            Simpan Status
                        </button>
                    </div>
                </form>
                <p id="pesanTanpaAksi" class="wo-muted mb-0 d-none"></p>
            </div>
            <div class="modal-footer">
                <a id="linkKomponen" href="#" class="btn-surface text-decoration-none me-auto">Catat Suku Cadang</a>
                <button type="button" class="btn-surface" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/mekanik.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
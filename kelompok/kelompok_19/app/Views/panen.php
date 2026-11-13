<?php
// app/Views/panen.php
// CRUD Panen: form catat di atas, rekap total, filter tanggal, lalu riwayat.
$title = "Manajemen Panen";
require_once __DIR__ . "/layouts/header.php";
?>
<header class="page-header">
    <h1 id="judul-panen">Hasil panen</h1>
    <p class="top-label">Catatan panen · <time datetime="<?= date('Y-m-d') ?>"><?= htmlspecialchars($tanggalPanjang) ?></time></p>
    <p class="intro">Catat berat hasil petik, lalu lihat total dan riwayatnya. Total mengikuti rentang tanggal yang dipilih.</p>
    <p class="actions no-print">
        <button class="btn btn--secondary" type="button" id="btnCetak">Cetak PDF</button>
        <button class="btn" type="button" data-modal-open="#modalPanen">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Catat panen
        </button>
    </p>
</header>

<dialog class="modal" id="modalPanen" aria-labelledby="modalPanenTitle">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalPanenTitle">Catat panen baru</h2>
            <button class="btn-close" type="button" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <form id="formPanen" class="modal-form">
            <input type="hidden" name="id" id="panenId">
            <p id="panenError" class="form-error" role="alert" hidden></p>
            <fieldset class="form-grid">
                <legend class="sr-only">Data hasil panen</legend>
                <div class="form-group">
                    <label for="panenSiklusId">Siklus tanam</label>
                    <select class="form-select" name="siklus_id" id="panenSiklusId" required>
                        <option value="">Pilih siklus...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="panenTanggalPanen">Tanggal panen</label>
                    <input type="date" class="form-control" name="tanggal_panen" id="panenTanggalPanen" required>
                </div>
                <div class="form-group">
                    <label for="panenJumlahKg">Berat (kg)</label>
                    <input type="number" class="form-control" name="jumlah_kg" id="panenJumlahKg" inputmode="decimal" required min="0.01" step="0.01">
                    <span class="form-hint">Contoh: 12,5.</span>
                </div>
                <div class="form-group">
                    <label for="panenKualitas">Kualitas</label>
                    <select class="form-select" name="kualitas" id="panenKualitas" required>
                        <option value="A">Grade A (premium)</option>
                        <option value="B">Grade B (standar)</option>
                        <option value="C">Grade C (kurang)</option>
                        <option value="Reject">Reject</option>
                    </select>
                </div>
                <div class="form-group form-group--wide">
                    <label for="panenCatatan">Catatan</label>
                    <textarea class="form-control" name="catatan" id="panenCatatan" rows="2" placeholder="Opsional, misalnya: hasil pagi, cuaca cerah."></textarea>
                </div>
            </fieldset>
        </form>
        <div class="modal-footer">
            <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
            <button class="btn" type="submit" form="formPanen" id="btnSimpan">Simpan panen</button>
        </div>
    </div>
</dialog>

<section class="filter-panel no-print" aria-labelledby="label-filter">
    <h2 id="label-filter">Filter tanggal</h2>
    <fieldset class="filter-grid">
        <legend class="sr-only">Rentang tanggal panen</legend>
        <div class="form-group">
            <label for="filterDari">Dari tanggal</label>
            <input type="date" class="form-control" id="filterDari">
        </div>
        <div class="form-group">
            <label for="filterSampai">Sampai tanggal</label>
            <input type="date" class="form-control" id="filterSampai">
        </div>
        <p class="filter-buttons">
            <button class="btn btn--secondary" type="button" id="btnReset">Reset</button>
            <button class="btn" type="button" id="btnTerapkan">Terapkan</button>
        </p>
    </fieldset>
</section>

<section class="ringkasan-panen" aria-labelledby="label-rekap">
    <h2 id="label-rekap">Rekap panen</h2>
    <p class="big-number"><span class="numeric" id="totalPanenKg">0 kg</span></p>
    <p class="summary-tonnage"><span id="totalPanenTon">0 ton</span></p>
    <p class="summary-period" id="periodeRekap">Semua catatan panen.</p>
    <dl class="summary-details">
        <dt>Jumlah catatan panen</dt>
        <dd class="numeric" id="jumlahCatatan">0</dd>
    </dl>
</section>

<section class="no-print" aria-labelledby="label-rekap-tanaman">
    <h2 class="section-title" id="label-rekap-tanaman">Rekap per tanaman</h2>
    <div class="table-wrap">
        <table id="tabelRekap">
            <caption class="sr-only">Rekap jumlah catatan dan total berat per tanaman</caption>
            <thead>
                <tr>
                    <th scope="col">Tanaman</th>
                    <th scope="col">Jumlah catatan</th>
                    <th scope="col">Total (kg)</th>
                </tr>
            </thead>
            <tbody id="tabelRekapBody">
                <tr><td colspan="3" class="text-center">Memuat data...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="no-print" aria-labelledby="label-riwayat">
    <h2 class="section-title" id="label-riwayat">Riwayat panen</h2>
    <div class="table-wrap">
        <table id="tabelPanen">
            <caption class="sr-only">Riwayat catatan panen</caption>
            <thead>
                <tr>
                    <th scope="col">No</th>
                    <th scope="col">Siklus</th>
                    <th scope="col">Tanggal panen</th>
                    <th scope="col">Berat (kg)</th>
                    <th scope="col">Kualitas</th>
                    <th scope="col">Catatan</th>
                    <th scope="col" class="no-print">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelPanenBody">
                <tr><td colspan="7" class="text-center">Memuat data panen...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<script src="/assets/js/panen.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>
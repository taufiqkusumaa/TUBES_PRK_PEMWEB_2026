
<?php
/** @var array<string, string> $formValues */
?>

<main class="container page-content advokita-page report-page">

  <!-- HERO -->
  <section class="report-hero">
    <div class="report-hero-content">
      <span class="report-eyebrow">
        <span></span> LAYANAN PENGADUAN ADVOKITA
      </span>

      <h1>Buat Laporan <span>Baru.</span></h1>

      <p>
        Sampaikan permasalahan hukum yang Anda alami melalui
        ADVOKITA. Kami menyediakan ruang bagi masyarakat untuk
        menyampaikan pengaduan dan memperoleh informasi hukum.
      </p>

      <div class="report-hero-note">
        <i class="bi bi-shield-check"></i>
        <span>Suara Anda adalah langkah awal menuju keadilan.</span>
      </div>
    </div>

    <div class="report-hero-decoration">
      <div class="report-hero-circle"></div>
      <i class="bi bi-file-earmark-text report-hero-icon"></i>
      <span class="report-hero-watermark">A</span>
    </div>
  </section>

  <!-- INFORMASI ALUR -->
  <div class="report-intro-bar">
    <div class="report-intro-icon">
      <i class="bi bi-info-circle"></i>
    </div>
    <div>
      <strong>Sebelum Mengirim Laporan</strong>
      <p>
        Lengkapi informasi dengan jelas dan sesuai kejadian sebenarnya.
        Kolom bertanda <span>*</span> wajib diisi.
      </p>
    </div>
  </div>

  <!-- PROGRESS -->
  <div class="report-progress">
    <div class="report-progress-heading">
      <span>FORMULIR PENGADUAN</span>
      <span id="formProgressText">0% selesai</span>
    </div>
    <div class="report-progress-track">
      <div id="formProgressBar"></div>
    </div>
    <div class="report-progress-caption">
      <span><i class="bi bi-pencil-square"></i> Isi formulir</span>
      <span><i class="bi bi-paperclip"></i> Lampirkan bukti</span>
      <span><i class="bi bi-send"></i> Kirim laporan</span>
    </div>
  </div>

  <!-- FORM -->
  <form class="report-form"
        id="reportForm"
        method="post"
        action="/index.php?page=report"
        enctype="multipart/form-data">

    <?php csrf_input(); ?>

    <input type="hidden" name="action" value="report">
    <input type="hidden" name="MAX_FILE_SIZE" value="10485760">

    <!-- SECTION 1 -->
    <section class="report-section-card">
      <div class="report-section-heading">
        <div class="report-section-icon">
          <i class="bi bi-file-earmark-text"></i>
        </div>
        <div>
          <span class="report-section-label">BAGIAN 01</span>
          <h2>Informasi Laporan</h2>
          <p>Informasi dasar mengenai permasalahan yang dilaporkan.</p>
        </div>
        <span class="report-section-number">01</span>
      </div>

      <div class="row g-4">

        <div class="col-md-6">
          <label class="report-label" for="reportTitle">
            Judul laporan <span>*</span>
          </label>
          <input
            class="form-control report-input"
            id="reportTitle"
            name="title"
            maxlength="160"
            required
            placeholder="Contoh: Dugaan pelanggaran hak pekerja"
            value="<?= escape($formValues['title'] ?? '') ?>"
          >
          <small class="report-help">
            Buat judul singkat yang menggambarkan permasalahan.
          </small>
        </div>

        <div class="col-md-6">
          <label class="report-label" for="reportCategory">
            Kategori kasus <span>*</span>
          </label>
          <select class="form-select report-input"
                  id="reportCategory"
                  name="category"
                  required>
            <option value="">Pilih kategori kasus</option>
            <?php foreach (REPORT_CATEGORIES as $category): ?>
              <option
                value="<?= escape($category) ?>"
                <?= ($formValues['category'] ?? '') === $category ? 'selected' : '' ?>
              >
                <?= escape($category) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="report-help">
            Pilih kategori yang paling sesuai dengan permasalahan.
          </small>
        </div>

        <div class="col-md-6">
          <label class="report-label" for="incidentDate">
            Tanggal kejadian <span>*</span>
          </label>
          <input
            class="form-control report-input"
            type="date"
            id="incidentDate"
            name="incidentDate"
            max="<?= escape(gmdate('Y-m-d')) ?>"
            required
            value="<?= escape($formValues['incidentDate'] ?? '') ?>"
          >
          <small class="report-help">
            Masukkan tanggal ketika peristiwa terjadi.
          </small>
        </div>

      </div>
    </section>

    <!-- SECTION 2 -->
    <section class="report-section-card">
      <div class="report-section-heading">
        <div class="report-section-icon">
          <i class="bi bi-chat-left-text"></i>
        </div>
        <div>
          <span class="report-section-label">BAGIAN 02</span>
          <h2>Kronologi Permasalahan</h2>
          <p>Jelaskan peristiwa secara runtut dan mudah dipahami.</p>
        </div>
        <span class="report-section-number">02</span>
      </div>

      <div>
        <label class="report-label" for="chronology">
          Ceritakan kronologi kejadian <span>*</span>
        </label>

        <textarea
          class="form-control report-input report-textarea"
          id="chronology"
          name="chronology"
          rows="6"
          required
          minlength="20"
          maxlength="2000"
          placeholder="Ceritakan bagaimana permasalahan terjadi, kapan kejadian berlangsung, lokasi kejadian, serta pihak yang terlibat..."
        ><?= escape($formValues['chronology'] ?? '') ?></textarea>

        <div class="report-textarea-footer">
          <small class="report-help">
            Minimal 20 karakter dan maksimal 2000 karakter.
          </small>
          <span id="characterCount">0 / 2000</span>
        </div>
      </div>

      <div class="report-tips">
        <i class="bi bi-lightbulb"></i>
        <div>
          <strong>Tips menulis kronologi</strong>
          <p>
            Jelaskan kejadian berdasarkan urutan waktu, sertakan
            informasi yang relevan, dan hindari informasi yang tidak
            berkaitan dengan permasalahan.
          </p>
        </div>
      </div>
    </section>

    <!-- SECTION 3 -->
    <section class="report-section-card">
      <div class="report-section-heading">
        <div class="report-section-icon">
          <i class="bi bi-person-lock"></i>
        </div>
        <div>
          <span class="report-section-label">BAGIAN 03</span>
          <h2>Privasi dan Identitas</h2>
          <p>Tentukan pilihan privasi identitas laporan Anda.</p>
        </div>
        <span class="report-section-number">03</span>
      </div>

      <label class="report-anonymous" for="anonymous">
        <div class="report-anonymous-icon">
          <i class="bi bi-incognito"></i>
        </div>

        <div class="report-anonymous-content">
          <strong>Kirim sebagai anonim</strong>
          <p>
            Nama dan NPM tidak ditampilkan pada daftar laporan admin.
          </p>
          <span class="report-anonymous-badge">
            <i class="bi bi-shield-lock"></i>
            Pilihan privasi
          </span>
        </div>

        <div class="report-toggle">
          <input
            type="checkbox"
            name="anonymous"
            value="true"
            id="anonymous"
            <?= isset($formValues['anonymous']) ? 'checked' : '' ?>
          >
          <span></span>
        </div>
      </label>

      <div class="report-privacy-note">
        <i class="bi bi-info-circle"></i>
        <p>
          Pilihan anonim mengatur tampilan nama dan NPM pada daftar
          laporan admin sesuai mekanisme sistem. Pastikan Anda memahami
          kebijakan privasi yang berlaku.
        </p>
      </div>
    </section>

    <!-- SECTION 4 -->
    <section class="report-section-card">
      <div class="report-section-heading">
        <div class="report-section-icon">
          <i class="bi bi-paperclip"></i>
        </div>
        <div>
          <span class="report-section-label">BAGIAN 04</span>
          <h2>Bukti Pendukung</h2>
          <p>Tambahkan dokumen atau gambar jika tersedia.</p>
        </div>
        <span class="report-section-number">04</span>
      </div>

      <label class="report-label" for="attachment">
        Lampiran bukti <span class="report-optional">(Opsional)</span>
      </label>

      <div class="report-upload" id="uploadBox">
        <input
          type="file"
          id="attachment"
          name="attachment"
          accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
        >

        <div class="report-upload-content">
          <div class="report-upload-icon">
            <i class="bi bi-cloud-arrow-up"></i>
          </div>
          <strong>Pilih file atau seret ke area ini</strong>
          <p>
            Unggah bukti pendukung untuk membantu menjelaskan permasalahan.
          </p>
          <span class="report-upload-format">
            PDF, JPG, PNG <span>•</span> Maksimal 10 MB
          </span>
          <span class="report-upload-button">
            <i class="bi bi-folder2-open"></i>
            Pilih File
          </span>
        </div>
      </div>

      <div class="report-file-preview" id="filePreview" hidden>
        <div class="report-file-icon">
          <i class="bi bi-file-earmark-check"></i>
        </div>
        <div class="report-file-info">
          <strong id="fileName">Nama file</strong>
          <small id="fileSize">0 KB</small>
        </div>
        <button type="button" id="removeFile"
                class="report-remove-file" aria-label="Hapus file">
          <i class="bi bi-trash3"></i>
        </button>
      </div>
    </section>

    <!-- INFORMASI KEAMANAN -->
    <div class="report-security">
      <div class="report-security-icon">
        <i class="bi bi-shield-check"></i>
      </div>
      <div>
        <strong>Perhatikan Keamanan Informasi</strong>
        <p>
          Pastikan informasi yang diberikan sesuai dengan kejadian sebenarnya.
          Hindari mencantumkan data pribadi sensitif yang tidak diperlukan.
          Lampirkan hanya bukti yang relevan dengan laporan.
        </p>
      </div>
    </div>

    <!-- ACTIONS -->
    <div class="report-form-actions">
      <div class="report-action-caption">
        <i class="bi bi-asterisk"></i>
        <span>Pastikan semua informasi wajib telah dilengkapi.</span>
      </div>

      <div class="report-action-buttons">
        <a class="report-cancel-button"
           href="<?= escape(url('dashboard')) ?>">
          <i class="bi bi-arrow-left"></i>
          Batal
        </a>

        <button class="report-submit-button" type="submit" id="submitReport">
          <span>Kirim Laporan</span>
          <i class="bi bi-arrow-right"></i>
        </button>
      </div>
    </div>

  </form>

  <!-- FOOTER -->
  <div class="report-footer">
    <i class="bi bi-scale"></i>
    <span>ADVOKITA — Access to Justice for Everyone</span>
  </div>

</main>

<style>
/* ===================================
   ADVOKITA REPORT PAGE
   NAVY / MAROON / WHITE
=================================== */

.report-page {
  --adv-navy: #24344d;
  --adv-dark: #18263b;
  --adv-red: #b42332;
  --adv-red-dark: #941c29;
  --adv-text: #45546a;
  --adv-muted: #8290a2;
  --adv-border: #e7eaf0;
  --adv-soft: #f7f8fa;
  color: var(--adv-text);
}

/* HERO */

.report-hero {
  position: relative;
  min-height: 270px;
  display: flex;
  align-items: center;
  padding: 42px 45px;
  background: linear-gradient(120deg, #18263b, #30445f);
  border-radius: 14px;
  overflow: hidden;
  color: white;
  margin-bottom: 22px;
}

.report-hero-content {
  max-width: 620px;
  position: relative;
  z-index: 2;
}

.report-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 2px;
  color: #e1e6ef;
}

.report-eyebrow span {
  width: 7px;
  height: 7px;
  background: #ed8991;
  border-radius: 50%;
}

.report-hero h1 {
  font-size: clamp(30px, 4vw, 40px);
  color: white;
  font-weight: 800;
  letter-spacing: -1px;
  margin: 20px 0 12px;
}

.report-hero h1 span {
  color: #ed8991;
}

.report-hero-content > p {
  max-width: 530px;
  color: #d0d8e4;
  font-size: 13px;
  line-height: 1.9;
}

.report-hero-note {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-top: 20px;
  color: #dce3ec;
  font-size: 11px;
}

.report-hero-note i {
  color: #ef9299;
  font-size: 17px;
}

.report-hero-decoration {
  position: absolute;
  width: 300px;
  height: 100%;
  right: 25px;
  top: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.report-hero-circle {
  width: 205px;
  height: 205px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,.17);
  box-shadow: 0 0 0 30px rgba(255,255,255,.025),
              0 0 0 60px rgba(255,255,255,.02);
}

.report-hero-icon {
  position: absolute;
  font-size: 100px;
  color: #ee929a;
  transform: rotate(-8deg);
}

.report-hero-watermark {
  position: absolute;
  right: 10px;
  top: -65px;
  font-size: 290px;
  font-weight: 900;
  color: rgba(255,255,255,.025);
}

/* INTRO */

.report-intro-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 17px 20px;
  background: #fff6f6;
  border: 1px solid #f5e0e2;
  border-radius: 9px;
  margin-bottom: 22px;
}

.report-intro-icon {
  min-width: 42px;
  height: 42px;
  display: flex;
  justify-content: center;
  align-items: center;
  background: white;
  color: var(--adv-red);
  border-radius: 9px;
  font-size: 21px;
}

.report-intro-bar strong {
  color: var(--adv-navy);
  font-size: 13px;
}

.report-intro-bar p {
  font-size: 11px;
  line-height: 1.7;
  color: var(--adv-muted);
  margin: 4px 0 0;
}

.report-intro-bar p span {
  color: var(--adv-red);
  font-weight: 800;
}

/* PROGRESS */

.report-progress {
  background: white;
  border: 1px solid var(--adv-border);
  border-radius: 10px;
  padding: 18px 21px;
  margin-bottom: 22px;
}

.report-progress-heading {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.report-progress-heading span:first-child {
  font-size: 10px;
  color: var(--adv-navy);
  font-weight: 800;
  letter-spacing: 1px;
}

.report-progress-heading span:last-child {
  color: var(--adv-red);
  font-size: 11px;
  font-weight: 700;
}

.report-progress-track {
  height: 6px;
  border-radius: 10px;
  background: #edf0f4;
  overflow: hidden;
}

.report-progress-track > div {
  width: 0;
  height: 100%;
  border-radius: 10px;
  background: linear-gradient(90deg, var(--adv-red), #e66d78);
  transition: width .35s ease;
}

.report-progress-caption {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-top: 12px;
}

.report-progress-caption span {
  color: var(--adv-muted);
  font-size: 10px;
}

.report-progress-caption i {
  color: var(--adv-red);
  margin-right: 4px;
}

/* FORM */

.report-form {
  display: flex;
  flex-direction: column;
  gap: 19px;
}

.report-section-card {
  padding: 26px;
  border: 1px solid var(--adv-border);
  background: white;
  border-radius: 11px;
  box-shadow: 0 3px 15px rgba(36,52,77,.025);
}

.report-section-heading {
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  padding-bottom: 20px;
  margin-bottom: 22px;
  border-bottom: 1px solid #edf0f4;
}

.report-section-icon {
  min-width: 47px;
  height: 47px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #fff0f1;
  color: var(--adv-red);
  font-size: 22px;
}

.report-section-label {
  display: block;
  color: var(--adv-red);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1.5px;
  margin-bottom: 4px;
}

.report-section-heading h2 {
  font-size: 17px;
  color: var(--adv-navy);
  font-weight: 800;
  margin: 0 0 4px;
}

.report-section-heading p {
  font-size: 11px;
  color: var(--adv-muted);
  margin: 0;
}

.report-section-number {
  position: absolute;
  right: 0;
  top: 0;
  color: #edf0f4;
  font-size: 30px;
  font-weight: 900;
}

/* INPUTS */

.report-label {
  display: block;
  color: var(--adv-navy);
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 9px;
}

.report-label > span:not(.report-optional) {
  color: var(--adv-red);
}

.report-optional {
  font-size: 10px;
  color: #9ba5b4;
  font-weight: 500;
}

.report-input {
  width: 100%;
  min-height: 46px;
  padding: 11px 13px;
  background-color: #fbfcfd;
  border: 1px solid #e2e7ed;
  border-radius: 7px;
  color: var(--adv-navy);
  font-size: 12px;
  box-shadow: none !important;
  transition: .25s;
}

.report-input::placeholder {
  color: #a5aebc;
}

.report-input:focus {
  background: white;
  border-color: var(--adv-red);
  box-shadow: 0 0 0 3px rgba(180,35,50,.08) !important;
  outline: none;
}

.report-help {
  display: block;
  font-size: 10px;
  color: #99a3b1;
  line-height: 1.7;
  margin-top: 7px;
}

.report-textarea {
  min-height: 155px;
  resize: vertical;
  line-height: 1.8;
}

.report-textarea-footer {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.report-textarea-footer #characterCount {
  color: var(--adv-red);
  font-size: 10px;
  font-weight: 700;
  white-space: nowrap;
}

.report-tips {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #f7f8fa;
  padding: 15px;
  border-radius: 8px;
  margin-top: 19px;
}

.report-tips > i {
  font-size: 19px;
  color: #c28b32;
}

.report-tips strong {
  color: var(--adv-navy);
  font-size: 11px;
}

.report-tips p {
  color: var(--adv-muted);
  font-size: 11px;
  line-height: 1.8;
  margin: 4px 0 0;
}

/* ANONYMOUS */

.report-anonymous {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 19px;
  border: 1px solid var(--adv-border);
  border-radius: 9px;
  background: #fbfcfd;
  cursor: pointer;
  transition: .25s;
}

.report-anonymous:has(input:checked) {
  border-color: #e4a3a9;
  background: #fff7f7;
}

.report-anonymous-icon {
  min-width: 46px;
  height: 46px;
  display: flex;
  justify-content: center;
  align-items: center;
  background: white;
  border-radius: 9px;
  color: var(--adv-red);
  font-size: 23px;
}

.report-anonymous-content {
  flex: 1;
}

.report-anonymous-content strong {
  display: block;
  color: var(--adv-navy);
  font-size: 13px;
}

.report-anonymous-content p {
  font-size: 11px;
  color: var(--adv-muted);
  margin: 4px 0 7px;
}

.report-anonymous-badge {
  display: inline-block;
  font-size: 9px;
  color: var(--adv-red);
  background: #fff0f1;
  padding: 5px 8px;
  border-radius: 4px;
}

.report-toggle {
  position: relative;
  min-width: 43px;
  height: 24px;
}

.report-toggle input {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  z-index: 2;
  cursor: pointer;
  margin: 0;
}

.report-toggle > span {
  position: absolute;
  inset: 0;
  background: #dce1e8;
  border-radius: 20px;
  transition: .25s;
}

.report-toggle > span::after {
  content: "";
  position: absolute;
  top: 3px;
  left: 3px;
  width: 18px;
  height: 18px;
  background: white;
  border-radius: 50%;
  transition: .25s;
  box-shadow: 0 1px 3px rgba(0,0,0,.15);
}

.report-toggle input:checked + span {
  background: var(--adv-red);
}

.report-toggle input:checked + span::after {
  transform: translateX(19px);
}

.report-toggle input:focus-visible + span {
  outline: 3px solid rgba(180,35,50,.2);
}

.report-privacy-note {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  margin-top: 14px;
}

.report-privacy-note i {
  color: #8b97a8;
  font-size: 14px;
}

.report-privacy-note p {
  color: var(--adv-muted);
  font-size: 10px;
  line-height: 1.8;
  margin: 0;
}

/* UPLOAD */

.report-upload {
  position: relative;
  padding: 30px 20px;
  border: 2px dashed #dce1e8;
  background: #fbfcfd;
  border-radius: 10px;
  text-align: center;
  transition: .25s;
}

.report-upload:hover,
.report-upload.dragging {
  border-color: var(--adv-red);
  background: #fff8f8;
}

.report-upload > input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.report-upload-content {
  pointer-events: none;
}

.report-upload-icon {
  width: 57px;
  height: 57px;
  margin: 0 auto 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 13px;
  background: #fff0f1;
  color: var(--adv-red);
  font-size: 30px;
}

.report-upload-content > strong {
  display: block;
  color: var(--adv-navy);
  font-size: 13px;
}

.report-upload-content > p {
  color: var(--adv-muted);
  font-size: 11px;
  margin: 6px 0;
}

.report-upload-format {
  display: block;
  color: #9aa4b3;
  font-size: 10px;
  margin: 9px 0 13px;
}

.report-upload-button {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 14px;
  border: 1px solid #ead1d4;
  border-radius: 6px;
  background: white;
  color: var(--adv-red);
  font-size: 11px;
  font-weight: 700;
}

.report-file-preview {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px;
  background: #f5f8fb;
  border: 1px solid var(--adv-border);
  border-radius: 8px;
  margin-top: 12px;
}

.report-file-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #eaf4ed;
  color: #39865a;
  font-size: 20px;
}

.report-file-info {
  flex: 1;
  min-width: 0;
}

.report-file-info strong,
.report-file-info small {
  display: block;
}

.report-file-info strong {
  color: var(--adv-navy);
  font-size: 11px;
  overflow-wrap: anywhere;
}

.report-file-info small {
  color: var(--adv-muted);
  font-size: 10px;
  margin-top: 4px;
}

.report-remove-file {
  border: 0;
  background: #fff0f1;
  color: var(--adv-red);
  width: 35px;
  height: 35px;
  border-radius: 7px;
  cursor: pointer;
}

/* SECURITY */

.report-security {
  display: flex;
  gap: 13px;
  padding: 19px;
  border: 1px solid #e3e9f0;
  border-radius: 9px;
  background: #f5f8fb;
}

.report-security-icon {
  min-width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 8px;
  color: var(--adv-navy);
  font-size: 21px;
}

.report-security strong {
  display: block;
  color: var(--adv-navy);
  font-size: 12px;
  margin-bottom: 5px;
}

.report-security p {
  color: var(--adv-muted);
  font-size: 10px;
  line-height: 1.8;
  margin: 0;
}

/* BUTTONS */

.report-form-actions {
  padding: 19px 0;
  border-top: 1px solid var(--adv-border);
}

.report-action-caption {
  display: flex;
  align-items: center;
  gap: 7px;
  color: var(--adv-muted);
  font-size: 10px;
  margin-bottom: 14px;
}

.report-action-caption i {
  color: var(--adv-red);
  font-size: 8px;
}

.report-action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.report-cancel-button,
.report-submit-button {
  min-height: 45px;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  padding: 11px 19px;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 700;
  transition: .25s;
}

.report-cancel-button {
  color: var(--adv-navy);
  background: #f0f2f5;
  border: 1px solid var(--adv-border);
}

.report-cancel-button:hover {
  background: #e5e9ef;
  color: var(--adv-navy);
}

.report-submit-button {
  border: none;
  color: white;
  background: var(--adv-red);
  cursor: pointer;
}

.report-submit-button:hover {
  background: var(--adv-red-dark);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(180,35,50,.2);
}

.report-submit-button:disabled {
  opacity: .7;
  cursor: wait;
  transform: none;
}

/* FOOTER */

.report-footer {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 9px;
  padding: 20px 10px;
  border-top: 1px solid var(--adv-border);
  color: #98a2b1;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
}

.report-footer i {
  color: var(--adv-red);
  font-size: 17px;
}

/* RESPONSIVE */

@media(max-width: 768px) {
  .report-hero {
    padding: 35px 25px;
  }

  .report-hero-decoration {
    right: -100px;
    opacity: .25;
  }

  .report-section-card {
    padding: 22px 18px;
  }
}

@media(max-width: 576px) {
  .report-hero {
    min-height: 290px;
    padding: 29px 20px;
  }

  .report-hero h1 {
    font-size: 31px;
  }

  .report-hero-content > p {
    font-size: 12px;
  }

  .report-hero-decoration {
    right: -160px;
    opacity: .17;
  }

  .report-intro-bar {
    padding: 14px;
  }

  .report-progress {
    padding: 15px;
  }

  .report-progress-caption span {
    font-size: 9px;
  }

  .report-section-card {
    padding: 19px 15px;
  }

  .report-section-heading {
    gap: 10px;
  }

  .report-section-icon {
    min-width: 40px;
    height: 40px;
    font-size: 19px;
  }

  .report-section-heading h2 {
    font-size: 15px;
  }

  .report-section-heading p {
    font-size: 10px;
  }

  .report-section-number {
    font-size: 23px;
  }

  .report-anonymous {
    padding: 13px;
    gap: 10px;
  }

  .report-anonymous-content p {
    line-height: 1.6;
  }

  .report-action-buttons {
    display: grid;
    grid-template-columns: .8fr 1.2fr;
  }

  .report-cancel-button,
  .report-submit-button {
    padding: 11px 9px;
    font-size: 11px;
  }

  .report-footer {
    font-size: 9px;
    letter-spacing: .5px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const form = document.getElementById('reportForm');
  const chronology = document.getElementById('chronology');
  const characterCount = document.getElementById('characterCount');
  const progressBar = document.getElementById('formProgressBar');
  const progressText = document.getElementById('formProgressText');
  const attachment = document.getElementById('attachment');
  const uploadBox = document.getElementById('uploadBox');
  const filePreview = document.getElementById('filePreview');
  const fileName = document.getElementById('fileName');
  const fileSize = document.getElementById('fileSize');
  const removeFile = document.getElementById('removeFile');
  const submitButton = document.getElementById('submitReport');

  // CHARACTER COUNTER
  function updateCharacterCount() {
    if (!chronology || !characterCount) return;

    characterCount.textContent =
      chronology.value.length + ' / 2000';
  }

  if (chronology) {
    chronology.addEventListener('input', function () {
      updateCharacterCount();
      updateProgress();
    });

    updateCharacterCount();
  }

  // FORM PROGRESS
  function updateProgress() {
    if (!form) return;

    const requiredFields = Array.from(
      form.querySelectorAll(
        'input[required]:not([type="hidden"]), select[required], textarea[required]'
      )
    );

    const total = requiredFields.length;

    const completed = requiredFields.filter(function (field) {
      return field.checkValidity() && field.value.trim() !== '';
    }).length;

    const percentage = total > 0
      ? Math.round((completed / total) * 100)
      : 0;

    if (progressBar) {
      progressBar.style.width = percentage + '%';
    }

    if (progressText) {
      progressText.textContent = percentage + '% selesai';
    }
  }

  if (form) {
    form.querySelectorAll('input, select, textarea').forEach(function (field) {
      field.addEventListener('input', updateProgress);
      field.addEventListener('change', updateProgress);
    });
  }

  updateProgress();

  // FILE PREVIEW
  function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) {
      return (bytes / 1024).toFixed(1) + ' KB';
    }

    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
  }

  function displayFile(file) {
    if (!file) return;

    const allowedTypes = [
      'application/pdf',
      'image/jpeg',
      'image/png'
    ];

    const allowedExtensions = /\.(pdf|jpg|jpeg|png)$/i;
    const maxSize = 10 * 1024 * 1024;

    if (
      !allowedTypes.includes(file.type) ||
      !allowedExtensions.test(file.name)
    ) {
      alert('Format file harus PDF, JPG, JPEG, atau PNG.');
      attachment.value = '';
      filePreview.hidden = true;
      return;
    }

    if (file.size > maxSize) {
      alert('Ukuran file maksimal adalah 10 MB.');
      attachment.value = '';
      filePreview.hidden = true;
      return;
    }

    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    filePreview.hidden = false;
  }

  if (attachment) {
    attachment.addEventListener('change', function () {
      displayFile(this.files[0]);
    });
  }

  if (removeFile) {
    removeFile.addEventListener('click', function () {
      attachment.value = '';
      filePreview.hidden = true;
    });
  }

  // DRAG AND DROP
  if (uploadBox && attachment) {

    ['dragenter', 'dragover'].forEach(function (eventName) {
      uploadBox.addEventListener(eventName, function (event) {
        event.preventDefault();
        uploadBox.classList.add('dragging');
      });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
      uploadBox.addEventListener(eventName, function (event) {
        event.preventDefault();
        uploadBox.classList.remove('dragging');
      });
    });

    uploadBox.addEventListener('drop', function (event) {
      const files = event.dataTransfer.files;

      if (files.length) {
        const transfer = new DataTransfer();
        transfer.items.add(files[0]);
        attachment.files = transfer.files;

        attachment.dispatchEvent(new Event('change', {
          bubbles: true
        }));
      }
    });
  }

  // SUBMIT VALIDATION
  if (form) {
    form.addEventListener('submit', function (event) {

      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
      }

      // Allow normal browser submission.
      // Do not disable the button here, so repeated browser
      // validation or navigation behavior is not disrupted.
      if (submitButton) {
        submitButton.querySelector('span').textContent = 'Mengirim...';
        submitButton.querySelector('i').className =
          'bi bi-arrow-repeat';
        submitButton.disabled = true;
      }
    });
  }

});
</script>


<?php
/** @var array<string, mixed>|null $report */
?>

<main class="container page-content">

  <!-- Header Halaman -->
  <div class="page-heading">
    <span class="eyebrow">DETAIL LAPORAN</span>
    <h1>Detail Pengaduan</h1>
    <p class="text-secondary mb-0">
      Pantau informasi, status, dan tahapan penanganan laporan pengaduan Anda.
    </p>
  </div>

  <?php if ($report === null): ?>

    <!-- Kondisi Laporan Tidak Ditemukan -->
    <section class="empty-state" aria-labelledby="detailEmptyTitle">
      <span class="empty-state-icon">
        <i class="bi bi-file-earmark-lock" aria-hidden="true"></i>
      </span>

      <h2 id="detailEmptyTitle">Laporan Tidak Ditemukan</h2>

      <p>
        Maaf, laporan yang Anda cari tidak tersedia atau bukan milik akun Anda.
        Silakan periksa kembali riwayat pengaduan.
      </p>

      <a class="btn btn-outline-danger" href="<?= escape(url('history')) ?>">
        <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>
        Kembali ke Riwayat
      </a>
    </section>

  <?php else: ?>

    <!-- Informasi Utama Laporan -->
    <section class="detail-card">

      <div class="detail-summary">
        <div>
          <span class="eyebrow">
            <i class="bi bi-hash" aria-hidden="true"></i>
            <?= escape($report['case_number']) ?>
          </span>

          <h2><?= escape($report['title']) ?></h2>

          <p>
            <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>
            Dikirim pada <?= escape(format_date($report['created_at'])) ?>
          </p>
        </div>

        <span class="badge <?= escape(status_class($report['status'])) ?>">
          <?= escape(status_label($report['status'])) ?>
        </span>
      </div>

      <!-- Informasi Kasus -->
      <section class="detail-section">
        <h3>
          <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
          Informasi Pengaduan
        </h3>

        <p>
          <i class="bi bi-tag me-2" aria-hidden="true"></i>
          <strong>Kategori:</strong>
          <?= escape($report['category']) ?>
        </p>

        <p>
          <i class="bi bi-calendar-event me-2" aria-hidden="true"></i>
          <strong>Tanggal Kejadian:</strong>
          <?= escape(format_date($report['incident_date'])) ?>
        </p>
      </section>
      <!-- Kronologi -->
      <section class="detail-section">
        <h3>
          <i class="bi bi-journal-text me-2" aria-hidden="true"></i>
          Kronologi Kejadian
        </h3>

        <p class="detail-description">
          <?= escape($report['chronology']) ?>
        </p>
      </section>

      <!-- Catatan Keputusan Admin -->
      <?php if ($report['status'] !== 'pending'): ?>
        <section class="detail-section">
          <h3>
            <i class="bi bi-chat-left-text me-2" aria-hidden="true"></i>
            Keputusan Admin
          </h3>
          <?php if ($report['decision_note'] !== null && $report['decision_note'] !== ''): ?><p class="detail-description"><?= escape($report['decision_note']) ?></p><?php endif; ?>
          <?php if ($report['decided_at'] !== null): ?><p>Diputuskan <?= escape(format_datetime($report['decided_at'])) ?><?php if ($report['decided_by_name'] !== null): ?> oleh <?= escape($report['decided_by_name']) ?><?php endif; ?></p><?php endif; ?>
        </section>
      <?php endif; ?>

      <!-- Lampiran Bukti -->
      <?php if (!empty($report['attachments'])): ?>
        <section class="detail-section">
          <h3>
            <i class="bi bi-paperclip me-2" aria-hidden="true"></i>
            Bukti Lampiran
          </h3>

          <p class="text-secondary">
            Berikut adalah berkas yang dilampirkan pada laporan ini.
          </p>

          <?php foreach ($report['attachments'] as $attachment): ?>
            <a
              class="d-block mb-2"
              href="<?= escape(url('attachment', [
                  'case' => $report['case_number'],
                  'id' => $attachment['id']
              ])) ?>"
            >
              <i class="bi bi-file-earmark-arrow-down me-2" aria-hidden="true"></i>
              <?= escape($attachment['name']) ?>
              <i class="bi bi-download ms-1" aria-hidden="true"></i>
            </a>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <!-- Informasi Status -->
      <section class="detail-section">
        <h3>
          <i class="bi bi-shield-check me-2" aria-hidden="true"></i>
          Status Pengaduan
        </h3>

        <p class="mb-0">
          Saat ini laporan Anda berstatus
          <strong><?= escape(status_label($report['status'])) ?></strong>.
          Silakan pantau perkembangan laporan melalui halaman riwayat pengaduan.
        </p>
      </section>

    </section>

  <?php endif; ?>

  <!-- Tombol Navigasi -->
  <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">

    <a class="btn btn-light" href="<?= escape(url('history')) ?>">
      <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>
      Kembali ke Riwayat
    </a>

    <a class="btn btn-danger" href="<?= escape(url('report')) ?>">
      <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>
      Buat Laporan Baru
    </a>

  </div>

</main>

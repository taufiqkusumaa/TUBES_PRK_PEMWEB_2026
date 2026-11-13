<?php
/** @var int $pendingCount */
/** @var int $reportCount */
/** @var int $reportPage */
/** @var int $reportPageCount */
/** @var int $announcementCount */
/** @var array<string, int> $reportStatistics */
/** @var array<string, string|null> $reportFilters */
/** @var array<string, string> $formValues */
/** @var array<int, array<string, mixed>> $reports */
/** @var array<int, array<string, mixed>> $announcements */
/** @var array<string, mixed>|null $announcementToEdit */
$editingAnnouncement = is_array($announcementToEdit)
  || ($formValues['action'] ?? '') === 'announcement-edit';
$announcementForm = [
  'title' => $formValues['title'] ?? ($announcementToEdit['title'] ?? ''),
  'body' => $formValues['body'] ?? ($announcementToEdit['body'] ?? ''),
  'category' => $formValues['category'] ?? ($announcementToEdit['category'] ?? 'Informasi Umum'),
  'id' => $announcementToEdit['id'] ?? ($formValues['announcementId'] ?? ''),
];
$announcementIsPublished = array_key_exists('action', $formValues)
  ? isset($formValues['isPublished'])
  : is_checked($announcementToEdit['is_published'] ?? false);
$announcementIsPinned = array_key_exists('action', $formValues)
  ? isset($formValues['isPinned'])
  : is_checked($announcementToEdit['is_pinned'] ?? false);
$announcementExpiry = $formValues['expiresAt'] ?? ($announcementToEdit['expires_at'] ?? '');
if (is_string($announcementExpiry) && $announcementExpiry !== '') {
  try {
    $announcementExpiry = (new DateTimeImmutable($announcementExpiry))->format('Y-m-d\TH:i');
  } catch (Throwable) {
    $announcementExpiry = '';
  }
}
?>
<main class="container page-content admin-page admin-red-theme">
  <div class="page-heading"><span class="eyebrow">ADMINISTRASI</span><h1>Kelola layanan</h1><p class="text-secondary mb-0">Tinjau laporan yang masuk dan terbitkan informasi untuk pengguna.</p></div>

  <section class="admin-stat-grid" aria-label="Statistik laporan">
    <article><span>Total laporan</span><strong><?= escape($reportStatistics['total']) ?></strong><i class="bi bi-files" aria-hidden="true"></i></article>
    <article><span>Pending</span><strong><?= escape($reportStatistics['pending']) ?></strong><i class="bi bi-hourglass-split" aria-hidden="true"></i></article>
    <article><span>Disetujui</span><strong><?= escape($reportStatistics['approved']) ?></strong><i class="bi bi-check-circle" aria-hidden="true"></i></article>
    <article><span>Ditolak</span><strong><?= escape($reportStatistics['rejected']) ?></strong><i class="bi bi-x-circle" aria-hidden="true"></i></article>
  </section>

  <section class="admin-section" aria-labelledby="adminReportsHeading">
rizkybranch
    <div class="section-heading"><div><span class="eyebrow">TINJAUAN</span><h2 id="adminReportsHeading">Laporan pengaduan</h2></div><span class="text-secondary small"><?= escape($pendingCount) ?> menunggu · <?= escape($reportCount) ?> total</span></div>

    <div class="section-heading"><div><span class="eyebrow">TINJAUAN</span><h2 id="adminReportsHeading">Laporan pengaduan</h2></div><span class="text-secondary small"><?= escape($reportStatistics['pending']) ?> menunggu · <?= escape($reportStatistics['total']) ?> total</span></div>
    <form class="admin-report-filters" method="get" action="/index.php" aria-label="Filter laporan">
      <input type="hidden" name="page" value="admin">
      <label><span>Pencarian</span><input class="form-control" type="search" name="search" maxlength="160" value="<?= escape($reportFilters['search']) ?>" placeholder="Judul atau isi laporan"></label>
      <label><span>Status</span><select class="form-select" name="status"><option value="">Semua status</option><?php foreach (['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $statusValue => $statusText): ?><option value="<?= escape($statusValue) ?>" <?= $reportFilters['status'] === $statusValue ? 'selected' : '' ?>><?= escape($statusText) ?></option><?php endforeach; ?></select></label>
      <label><span>Kategori</span><select class="form-select" name="category"><option value="">Semua kategori</option><?php foreach (REPORT_CATEGORIES as $categoryOption): ?><option value="<?= escape($categoryOption) ?>" <?= $reportFilters['category'] === $categoryOption ? 'selected' : '' ?>><?= escape($categoryOption) ?></option><?php endforeach; ?></select></label>
      <label><span>Dari tanggal</span><input class="form-control" type="date" name="dateFrom" value="<?= escape($reportFilters['dateFrom'] ?? '') ?>"></label>
      <label><span>Sampai tanggal</span><input class="form-control" type="date" name="dateTo" value="<?= escape($reportFilters['dateTo'] ?? '') ?>"></label>
      <div class="admin-filter-actions"><button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Filter</button><a class="btn btn-outline-secondary btn-sm" href="<?= escape(url('admin')) ?>">Reset</a></div>
    </form>
    
 main
    <?php if ($reports === []): ?>
      <p class="empty-inline">Belum ada laporan yang masuk.</p>
    <?php else: ?>
      <div class="admin-report-table-wrap" role="region" aria-label="Daftar laporan mahasiswa" tabindex="0">
        <table class="admin-report-table">
          <thead><tr><th scope="col">Nomor laporan</th><th scope="col">Pelapor</th><th scope="col">Kategori</th><th scope="col">Tanggal</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Tindakan</span></th></tr></thead>
          <tbody>
        <?php foreach ($reports as $reportItem): ?>
          <?php $dialogId = 'report-dialog-' . $reportItem['id']; ?>
          <tr class="<?= $reportItem['status'] === 'pending' ? 'is-pending' : '' ?>">
            <td><span class="admin-case-number"><?= escape($reportItem['case_number']) ?></span><span class="admin-mobile-label">Tanggal kejadian: <?= escape(format_date($reportItem['incident_date'])) ?></span></td>
            <td><?= is_checked($reportItem['is_anonymous']) ? 'Anonim' : escape(($reportItem['submitter_name'] ?? 'Pengguna') . ' · ' . ($reportItem['submitter_nim'] ?? '-')) ?></td>
            <td><?= escape($reportItem['category']) ?></td>
            <td><?= escape(format_date($reportItem['created_at'])) ?></td>
            <td><span class="badge <?= escape(status_class($reportItem['status'])) ?>"><?= escape(status_label($reportItem['status'])) ?></span></td>
            <td class="admin-report-action"><button class="btn <?= $reportItem['status'] === 'pending' ? 'btn-danger' : 'btn-outline-secondary' ?> btn-sm" type="button" data-dialog-open="<?= escape($dialogId) ?>"><i class="bi <?= $reportItem['status'] === 'pending' ? 'bi-shield-check' : 'bi-eye' ?> me-1" aria-hidden="true"></i><?= $reportItem['status'] === 'pending' ? 'Moderasi' : 'Detail' ?></button></td>
          </tr>
        <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php foreach ($reports as $reportItem): ?>
        <?php
          $dialogId = 'report-dialog-' . $reportItem['id'];
          $isPending = $reportItem['status'] === 'pending';
          $reopenDialog = ($formValues['decisionReportId'] ?? '') === (string) $reportItem['id'];
        ?>
        <dialog class="moderation-dialog" id="<?= escape($dialogId) ?>" aria-labelledby="<?= escape($dialogId) ?>-title" <?= $reopenDialog && $isPending ? 'data-reopen-dialog' : '' ?>>
          <div class="moderation-dialog-header">
            <div><span class="eyebrow"><?= $isPending ? 'TINJAUAN LAPORAN' : 'ARSIP LAPORAN' ?></span><h2 id="<?= escape($dialogId) ?>-title"><?= $isPending ? 'Moderasi laporan' : 'Detail laporan' ?></h2></div>
            <button class="moderation-close" type="button" data-dialog-close aria-label="Tutup detail"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
          </div>
          <div class="moderation-dialog-body">
            <div class="moderation-facts">
              <div><span>Nomor laporan</span><strong><?= escape($reportItem['case_number']) ?></strong></div>
              <div><span>Pelapor</span><strong><?= is_checked($reportItem['is_anonymous']) ? 'Anonim' : escape($reportItem['submitter_name'] ?? 'Pengguna') ?></strong><?php if (!is_checked($reportItem['is_anonymous'])): ?><small>NPM <?= escape($reportItem['submitter_nim'] ?? '-') ?></small><?php endif; ?></div>
              <div><span>Kategori</span><strong><?= escape($reportItem['category']) ?></strong></div>
              <div><span>Tanggal laporan</span><strong><?= escape(format_date($reportItem['created_at'])) ?></strong></div>
              <div><span>Tanggal kejadian</span><strong><?= escape(format_date($reportItem['incident_date'])) ?></strong></div>
              <div><span>Status</span><strong><span class="badge <?= escape(status_class($reportItem['status'])) ?>"><?= escape(status_label($reportItem['status'])) ?></span></strong></div>
            </div>
            <section class="moderation-report-content" aria-label="Isi laporan">
              <h3><?= escape($reportItem['title']) ?></h3>
              <h4>Kronologi</h4><p><?= escape($reportItem['chronology']) ?></p>
              <?php if ($reportItem['attachments'] !== []): ?>
                <div class="moderation-attachments"><h4>Lampiran</h4><?php foreach ($reportItem['attachments'] as $attachment): ?><a href="<?= escape(url('attachment', ['case' => $reportItem['case_number'], 'id' => $attachment['id']])) ?>"><i class="bi bi-paperclip" aria-hidden="true"></i><?= escape($attachment['name']) ?></a><?php endforeach; ?></div>
              <?php endif; ?>
            </section>
            <?php if (!$isPending): ?>
              <section class="moderation-saved-note"><h3>Riwayat keputusan</h3><p><strong><?= escape(status_label($reportItem['status'])) ?></strong><?php if ($reportItem['decided_by_name'] !== null): ?> oleh <?= escape($reportItem['decided_by_name']) ?><?php endif; ?><?php if ($reportItem['decided_at'] !== null): ?> · <?= escape(format_datetime($reportItem['decided_at'])) ?><?php endif; ?></p><?php if ($reportItem['decision_note'] !== null && $reportItem['decision_note'] !== ''): ?><p><?= escape($reportItem['decision_note']) ?></p><?php endif; ?></section>
            <?php endif; ?>
            <?php if ($isPending): ?>
              <form class="moderation-decision-form" method="post" action="<?= escape(url('admin', ['reportPage' => (string) $reportPage])) ?>" data-report-case="<?= escape($reportItem['case_number']) ?>">
                <?php csrf_input(); ?>
                <input type="hidden" name="action" value="decision">
                <input type="hidden" name="reportId" value="<?= escape($reportItem['id']) ?>">
                <fieldset class="moderation-decision-options">
                  <legend>Keputusan</legend>
                  <label><input type="radio" name="decision" value="approved" required <?= ($formValues['decisionReportId'] ?? '') === (string) $reportItem['id'] && ($formValues['decisionChoice'] ?? '') === 'approved' ? 'checked' : '' ?>><span><i class="bi bi-check-circle" aria-hidden="true"></i> Disetujui</span></label>
                  <label><input type="radio" name="decision" value="rejected" required <?= ($formValues['decisionReportId'] ?? '') === (string) $reportItem['id'] && ($formValues['decisionChoice'] ?? '') === 'rejected' ? 'checked' : '' ?>><span><i class="bi bi-x-circle" aria-hidden="true"></i> Ditolak</span></label>
                </fieldset>
                <div class="mb-3"><label class="form-label" for="decision-note-<?= escape($reportItem['id']) ?>">Catatan pertimbangan hukum <span aria-hidden="true">*</span></label><textarea class="form-control" id="decision-note-<?= escape($reportItem['id']) ?>" name="note" maxlength="2000" rows="4" required aria-describedby="decision-note-help-<?= escape($reportItem['id']) ?>"><?= ($formValues['decisionReportId'] ?? '') === (string) $reportItem['id'] ? escape($formValues['decisionNote'] ?? '') : '' ?></textarea><small id="decision-note-help-<?= escape($reportItem['id']) ?>">Wajib diisi, maksimal 2.000 karakter.</small></div>
                <div class="moderation-dialog-footer"><button class="btn btn-outline-secondary" type="button" data-dialog-close>Batal</button><button class="btn btn-danger" type="submit"><i class="bi bi-check2-square me-2" aria-hidden="true"></i>Simpan Keputusan</button></div>
              </form>
            <?php else: ?>
              <div class="moderation-dialog-footer"><button class="btn btn-outline-secondary" type="button" data-dialog-close><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Kembali ke daftar laporan</button></div>
            <?php endif; ?>
          </div>
        </dialog>
      <?php endforeach; ?>
 rizkybranch
    <?php endif; ?>
    <?php if ($reportPageCount > 1): ?>
      <nav class="d-flex justify-content-between align-items-center gap-3 mt-3" aria-label="Navigasi halaman laporan">
        <span class="text-secondary small">Halaman <?= escape($reportPage) ?> dari <?= escape($reportPageCount) ?></span>
        <div class="d-flex gap-2">
          <?php if ($reportPage > 1): ?>
            <a class="btn btn-sm btn-outline-secondary" rel="prev" href="<?= escape(url('admin', ['reportPage' => (string) ($reportPage - 1)])) ?>">Sebelumnya</a>
          <?php endif; ?>
          <?php if ($reportPage < $reportPageCount): ?>
            <a class="btn btn-sm btn-outline-secondary" rel="next" href="<?= escape(url('admin', ['reportPage' => (string) ($reportPage + 1)])) ?>">Berikutnya</a>
          <?php endif; ?>
        </div>
      </nav>

 main
    <?php endif; ?>
  </section>

  <section class="admin-section" aria-labelledby="adminAnnouncementsHeading">
    <div class="section-heading"><div><span class="eyebrow">BERANDA</span><h2 id="adminAnnouncementsHeading">Pengumuman</h2></div><span class="text-secondary small"><?= escape($announcementCount) ?> total</span></div>
    <form class="admin-form" method="post" action="<?= escape(url('admin', ['reportPage' => (string) $reportPage])) ?>">
      <?php csrf_input(); ?>
      <input type="hidden" name="action" value="<?= $editingAnnouncement ? 'announcement-edit' : 'announcement-create' ?>">
      <?php if ($editingAnnouncement): ?><input type="hidden" name="announcementId" value="<?= escape($announcementForm['id']) ?>"><?php endif; ?>
      <div class="mb-3"><label class="form-label" for="announcementTitle">Judul</label><input class="form-control" id="announcementTitle" name="title" maxlength="120" required value="<?= escape($announcementForm['title']) ?>"></div>
      <div class="mb-3"><label class="form-label" for="announcementCategory">Kategori</label><select class="form-select" id="announcementCategory" name="category" required><?php foreach (ANNOUNCEMENT_CATEGORIES as $categoryOption): ?><option value="<?= escape($categoryOption) ?>" <?= $announcementForm['category'] === $categoryOption ? 'selected' : '' ?>><?= escape($categoryOption) ?></option><?php endforeach; ?></select></div>
      <div class="mb-3"><label class="form-label" for="announcementBody">Isi pengumuman</label><textarea class="form-control" id="announcementBody" name="body" rows="4" maxlength="3000" required><?= escape($announcementForm['body']) ?></textarea></div>
      <div class="mb-3"><label class="form-label" for="announcementExpiresAt">Tanggal berakhir (opsional)</label><input class="form-control" id="announcementExpiresAt" name="expiresAt" type="datetime-local" value="<?= escape($announcementExpiry) ?>"></div>
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="admin-announcement-options"><div class="form-check"><input class="form-check-input" id="announcementPublished" name="isPublished" type="checkbox" <?= $announcementIsPublished ? 'checked' : '' ?>><label class="form-check-label" for="announcementPublished">Aktifkan pengumuman</label></div><div class="form-check"><input class="form-check-input" id="announcementPinned" name="isPinned" type="checkbox" <?= $announcementIsPinned ? 'checked' : '' ?>><label class="form-check-label" for="announcementPinned">Pin di urutan atas</label></div></div>
        <div class="d-flex gap-2"><button class="btn btn-danger" type="submit"><i class="bi bi-save me-2" aria-hidden="true"></i><?= $editingAnnouncement ? 'Simpan Perubahan' : 'Simpan Pengumuman' ?></button><?php if ($editingAnnouncement): ?><a class="btn btn-outline-secondary" href="<?= escape(url('admin')) ?>">Batal</a><?php endif; ?></div>
      </div>
    </form>
    <?php if ($announcements === []): ?>
      <p class="empty-inline mt-3">Belum ada pengumuman.</p>
    <?php else: ?>
      <div class="admin-announcement-list mt-4">
        <?php foreach ($announcements as $announcement): ?>
          <article class="admin-announcement">
            <?php $isExpired = $announcement['expires_at'] !== null && new DateTimeImmutable($announcement['expires_at']) <= new DateTimeImmutable(); ?>
            <div><h3><?= escape($announcement['title']) ?></h3><span class="badge <?= $announcement['is_published'] && !$isExpired ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $isExpired ? 'Kedaluwarsa' : ($announcement['is_published'] ? 'Aktif' : 'Nonaktif') ?></span><?php if ($announcement['is_pinned']): ?><span class="badge text-bg-danger"><i class="bi bi-pin-angle-fill me-1" aria-hidden="true"></i>Pin</span><?php endif; ?></div>
            <p class="admin-announcement-meta"><?= escape($announcement['category']) ?> · Terbit <?= escape(format_date($announcement['published_at'])) ?><?php if ($announcement['expires_at'] !== null): ?> · Berakhir <?= escape(format_date($announcement['expires_at'])) ?><?php endif; ?></p>
            <p><?= escape($announcement['body']) ?></p>
            <div class="d-flex justify-content-end gap-2">
 rizkybranch
              <form method="post" action="<?= escape(url('admin', ['reportPage' => (string) $reportPage])) ?>">

              <a class="btn btn-sm btn-outline-secondary" href="<?= escape(url('admin', ['editAnnouncement' => $announcement['id']])) ?>"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit</a>
              <form method="post" action="<?= escape(url('admin')) ?>">
 main
                <?php csrf_input(); ?>
                <input type="hidden" name="action" value="announcement-toggle">
                <input type="hidden" name="announcementId" value="<?= escape($announcement['id']) ?>">
                <input type="hidden" name="isPublished" value="<?= $announcement['is_published'] ? '0' : '1' ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><?= $announcement['is_published'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
              </form>
 rizkybranch
              <form method="post" action="<?= escape(url('admin', ['reportPage' => (string) $reportPage])) ?>">

              <form class="announcement-delete-form" method="post" action="<?= escape(url('admin')) ?>">
 main
                <?php csrf_input(); ?>
                <input type="hidden" name="action" value="announcement-delete">
                <input type="hidden" name="announcementId" value="<?= escape($announcement['id']) ?>">
                <button class="btn btn-sm btn-outline-secondary" type="submit">Hapus</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<script src="/assets/js/admin-moderation.js" defer></script>

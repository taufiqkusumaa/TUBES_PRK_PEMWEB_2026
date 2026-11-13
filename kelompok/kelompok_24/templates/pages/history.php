
<?php
/** @var array<int, array<string, mixed>> $reports */
?>

<style>
/* =========================================
   ADVOKITA - RIWAYAT PENGADUAN
========================================= */

.history-page {
  --adv-navy: #24344d;
  --adv-navy-dark: #18263b;
  --adv-red: #b42332;
  --adv-red-dark: #941b29;
  --adv-bg: #f5f7fb;
  --adv-border: #e5e9f0;
  --adv-muted: #758195;

  padding-top: 42px;
  padding-bottom: 80px;
}

/* HEADER */

.history-heading {
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 35px;
  padding: 30px 32px;
  overflow: hidden;
  border-radius: 22px;
  background: linear-gradient(120deg, var(--adv-navy-dark), var(--adv-navy));
  color: #fff;
  box-shadow: 0 12px 30px rgba(24, 38, 59, .13);
}

.history-heading::after {
  content: "";
  position: absolute;
  width: 230px;
  height: 230px;
  right: -65px;
  top: -115px;
  border: 35px solid rgba(255,255,255,.055);
  border-radius: 50%;
  pointer-events: none;
}

.history-heading-content {
  position: relative;
  z-index: 1;
}

.history-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  color: #ffb8bf;
  font-size: .76rem;
  font-weight: 800;
  letter-spacing: 2px;
}

.history-eyebrow::before {
  content: "";
  width: 25px;
  height: 2px;
  background: #e65b69;
}

.history-heading h1 {
  margin: 0 0 10px;
  color: #fff;
  font-size: clamp(1.7rem, 4vw, 2.35rem);
  font-weight: 800;
  letter-spacing: -.7px;
}

.history-heading p {
  max-width: 590px;
  margin: 0;
  color: rgba(255,255,255,.75);
  line-height: 1.7;
}

.history-create-btn {
  position: relative;
  z-index: 1;
  flex-shrink: 0;
  padding: 12px 20px;
  border: 0;
  border-radius: 11px;
  background: var(--adv-red);
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  transition: .25s ease;
}

.history-create-btn:hover {
  background: #d13a49;
  color: #fff;
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(180,35,50,.25);
}

/* SUMMARY */

.history-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 20px;
}

.history-summary-title {
  margin: 0;
  color: var(--adv-navy-dark);
  font-size: 1.12rem;
  font-weight: 800;
}

.history-count {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 13px;
  border: 1px solid var(--adv-border);
  border-radius: 30px;
  background: #fff;
  color: var(--adv-navy);
  font-size: .82rem;
  font-weight: 700;
}

/* REPORT CARD */

.history-list {
  display: grid;
  gap: 17px;
}

.history-card {
  position: relative;
  padding: 24px 26px;
  overflow: hidden;
  border: 1px solid var(--adv-border);
  border-radius: 17px;
  background: #fff;
  box-shadow: 0 4px 15px rgba(24,38,59,.035);
  transition: transform .25s ease, box-shadow .25s ease,
              border-color .25s ease;
  animation: historyFadeIn .45s ease both;
}

.history-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: var(--adv-navy);
  opacity: .8;
}

.history-card:hover {
  transform: translateY(-4px);
  border-color: #cbd4e1;
  box-shadow: 0 12px 28px rgba(24,38,59,.09);
}

.history-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
}

.history-card-main {
  min-width: 0;
}

.history-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 9px;
  margin-bottom: 12px;
  color: var(--adv-muted);
  font-size: .8rem;
}

.history-case-number {
  color: var(--adv-red);
  font-weight: 800;
  letter-spacing: .3px;
}

.history-meta-divider {
  color: #c5cbd5;
}

.history-card h2 {
  margin: 0 0 12px;
  color: var(--adv-navy-dark);
  font-size: 1.2rem;
  font-weight: 800;
  line-height: 1.45;
  overflow-wrap: anywhere;
}

.history-category {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 11px;
  border-radius: 7px;
  background: #f0f3f8;
  color: var(--adv-navy);
  font-size: .78rem;
  font-weight: 700;
}

/* STATUS */

.history-status {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  flex-shrink: 0;
  padding: 8px 13px;
  border: 1px solid transparent;
  border-radius: 30px;
  font-size: .76rem;
  font-weight: 800;
  white-space: nowrap;
}

.history-status::before {
  content: "";
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: currentColor;
}

/* Catatan Admin */

.history-note {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  margin-top: 20px;
  padding: 14px 16px;
  border: 1px solid #f0d9dc;
  border-radius: 11px;
  background: #fff8f8;
  color: #596273;
  font-size: .88rem;
  line-height: 1.65;
  overflow-wrap: anywhere;
}

.history-note > i {
  margin-top: 3px;
  color: var(--adv-red);
  font-size: 1.05rem;
}

.history-note strong {
  display: block;
  margin-bottom: 2px;
  color: var(--adv-navy-dark);
}

/* FOOTER CARD */

.history-card-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-top: 19px;
  padding-top: 15px;
  border-top: 1px solid #edf0f4;
}

.history-detail-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--adv-red);
  font-size: .88rem;
  font-weight: 800;
  text-decoration: none;
  transition: gap .2s ease, color .2s ease;
}

.history-detail-link:hover {
  gap: 13px;
  color: var(--adv-red-dark);
}

/* EMPTY STATE */

.history-empty {
  padding: 65px 25px;
  border: 1px dashed #ccd4e0;
  border-radius: 20px;
  background: #fff;
  text-align: center;
}

.history-empty-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 85px;
  height: 85px;
  margin-bottom: 22px;
  border-radius: 24px;
  background: #f0f3f8;
  color: var(--adv-navy);
  font-size: 2.4rem;
}

.history-empty h2 {
  margin-bottom: 10px;
  color: var(--adv-navy-dark);
  font-size: 1.35rem;
  font-weight: 800;
}

.history-empty p {
  max-width: 490px;
  margin: 0 auto 24px;
  color: var(--adv-muted);
  line-height: 1.75;
}

.history-empty-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border-radius: 10px;
  background: var(--adv-red);
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  transition: .2s ease;
}

.history-empty-btn:hover {
  background: var(--adv-red-dark);
  color: #fff;
  transform: translateY(-2px);
}

/* ANIMATION */

@keyframes historyFadeIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* RESPONSIVE */

@media (max-width: 767px) {
  .history-page {
    padding-top: 25px;
    padding-bottom: 50px;
  }

  .history-heading {
    align-items: stretch;
    flex-direction: column;
    padding: 25px 22px;
    border-radius: 17px;
  }

  .history-create-btn {
    align-self: flex-start;
  }

  .history-card {
    padding: 20px 18px;
  }

  .history-card-top {
    flex-direction: column;
    gap: 15px;
  }

  .history-status {
    align-self: flex-start;
  }

  .history-meta {
    gap: 7px;
  }

  .history-card h2 {
    font-size: 1.08rem;
  }

  .history-card-footer {
    justify-content: flex-start;
  }

  .history-empty {
    padding: 45px 20px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .history-card,
  .history-card *,
  .history-heading *,
  .history-create-btn,
  .history-detail-link {
    animation: none !important;
    transition: none !important;
  }
}
</style>

<main class="container page-content history-page">

  <!-- HEADER -->
  <header class="history-heading">
    <div class="history-heading-content">
      <span class="history-eyebrow">RIWAYAT PENGADUAN</span>

      <h1>Riwayat Pengaduan</h1>

      <p>
        Pantau status, perkembangan, dan tindak lanjut setiap pengaduan
        yang telah Anda kirim melalui ADVOKITA.
      </p>
    </div>

    <a class="history-create-btn" href="<?= escape(url('report')) ?>">
      <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>
      Buat Laporan
    </a>
  </header>

  <?php if ($reports === []): ?>

    <!-- EMPTY STATE -->
    <section class="history-empty" aria-labelledby="historyEmptyTitle">

      <span class="history-empty-icon">
        <i class="bi bi-folder2-open" aria-hidden="true"></i>
      </span>

      <h2 id="historyEmptyTitle">
        Belum Ada Riwayat Pengaduan
      </h2>

      <p>
        Anda belum mengirimkan laporan pengaduan.
        Setelah laporan dikirim, informasi status dan tindak lanjutnya
        akan ditampilkan pada halaman ini.
      </p>

      <a class="history-empty-btn" href="<?= escape(url('report')) ?>">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        Buat Pengaduan
      </a>

    </section>

  <?php else: ?>

    <!-- SUMMARY -->
    <div class="history-summary">
      <h2 class="history-summary-title">Daftar Pengaduan Anda</h2>

      <span class="history-count">
        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
        <?= count($reports) ?> Laporan
      </span>
    </div>

    <!-- REPORT LIST -->
    <section class="history-list" aria-label="Daftar laporan pengaduan">

      <?php foreach ($reports as $index => $reportItem): ?>

        <article class="history-card"
          style="animation-delay: <?= min($index * 70, 350) ?>ms">

          <div class="history-card-top">

            <div class="history-card-main">

              <div class="history-meta">

                <span class="history-case-number">
                  <i class="bi bi-hash" aria-hidden="true"></i>
                  <?= escape($reportItem['case_number']) ?>
                </span>

                <span class="history-meta-divider" aria-hidden="true">•</span>

                <span>
                  <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                  <?= escape(format_date($reportItem['created_at'])) ?>
                </span>

              </div>

              <h2><?= escape($reportItem['title']) ?></h2>

              <span class="history-category">
                <i class="bi bi-tag" aria-hidden="true"></i>
                <?= escape($reportItem['category']) ?>
              </span>

            </div>

            <!-- STATUS -->
            <span class="history-status <?= escape(status_class($reportItem['status'])) ?>">
              <?= escape(status_label($reportItem['status'])) ?>
            </span>

          </div>

          <!-- ADMIN NOTE -->
          <?php if (
              $reportItem['decision_note'] !== null
              && $reportItem['decision_note'] !== ''
          ): ?>

            <div class="history-note">
              <i class="bi bi-chat-left-text" aria-hidden="true"></i>

              <div>
                <strong>Catatan Admin</strong>
                <?= escape($reportItem['decision_note']) ?>
              </div>
            </div>

          <?php endif; ?>

          <!-- DETAIL LINK -->
          <div class="history-card-footer">

            <a
              class="history-detail-link"
              href="<?= escape(url('detail', ['case' => $reportItem['case_number']])) ?>"
            >
              Lihat Detail Pengaduan
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>

          </div>

        </article>

      <?php endforeach; ?>

    </section>

  <?php endif; ?>

</main>

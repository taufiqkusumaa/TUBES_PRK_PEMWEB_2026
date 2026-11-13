<?php
/** @var array<int, array<string, mixed>> $announcements */
/** @var array<int, array<string, mixed>> $reports */

$totalReports = count($reports);
$pendingReports = count(array_filter($reports, static fn(array $r): bool => ($r['status'] ?? '') === 'pending'));
$approvedReports = count(array_filter($reports, static fn(array $r): bool => ($r['status'] ?? '') === 'approved'));
$rejectedReports = count(array_filter($reports, static fn(array $r): bool => ($r['status'] ?? '') === 'rejected'));
?>

<style>
/* ===================================
   ADVOKITA DASHBOARD PAGE
   SELARAS DENGAN REPORT & DESIGN SYSTEM
=================================== */

.dashboard-page {
  --adv-navy: #24344d;
  --adv-dark: #18263b;
  --adv-red: #b42332;
  --adv-red-accent: #ed8991;
  --adv-red-light: #fff0f1;
  --adv-text: #45546a;
  --adv-muted: #8290a2;
  --adv-border: #e7eaf0;
  --adv-soft: #f7f8fa;
  color: var(--adv-text);
  padding-bottom: 60px;
}

/* HERO BANNER */
.dashboard-hero {
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

.dashboard-hero-content {
  max-width: 640px;
  position: relative;
  z-index: 2;
}

.dashboard-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 2px;
  color: #e1e6ef;
}

.dashboard-eyebrow span {
  width: 7px;
  height: 7px;
  background: var(--adv-red-accent);
  border-radius: 50%;
  display: inline-block;
}

.dashboard-hero h1 {
  font-size: clamp(30px, 4vw, 40px);
  color: white;
  font-weight: 800;
  letter-spacing: -1px;
  margin: 18px 0 12px;
}

.dashboard-hero h1 span {
  color: var(--adv-red-accent);
}

.dashboard-hero-content > p {
  max-width: 550px;
  color: #d0d8e4;
  font-size: 13px;
  line-height: 1.85;
  margin-bottom: 20px;
}

.dashboard-hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}

.btn-dashboard-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #b42332, #cf3142);
  color: #fff !important;
  font-size: 13px;
  font-weight: 700;
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(180, 35, 50, 0.35);
  transition: all .25s ease;
}

.btn-dashboard-primary:hover {
  background: linear-gradient(135deg, #991a27, #b42332);
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(180, 35, 50, 0.45);
}

.btn-dashboard-secondary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #f1f4f9 !important;
  font-size: 13px;
  font-weight: 600;
  padding: 10px 18px;
  border-radius: 8px;
  text-decoration: none;
  backdrop-filter: blur(4px);
  transition: all .25s ease;
}

.btn-dashboard-secondary:hover {
  background: rgba(255, 255, 255, 0.18);
  color: #fff !important;
  border-color: rgba(255, 255, 255, 0.35);
}

.dashboard-hero-note {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #dce3ec;
  font-size: 11px;
}

.dashboard-hero-note i {
  color: #ef9299;
  font-size: 16px;
}

/* HERO DECORATION */
.dashboard-hero-decoration {
  position: absolute;
  width: 300px;
  height: 100%;
  right: 25px;
  top: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.dashboard-hero-circle {
  width: 205px;
  height: 205px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,.17);
  box-shadow: 0 0 0 30px rgba(255,255,255,.025),
              0 0 0 60px rgba(255,255,255,.02);
}

.dashboard-hero-icon {
  position: absolute;
  font-size: 95px;
  color: #ee929a;
  transform: rotate(-8deg);
}

.dashboard-hero-watermark {
  position: absolute;
  right: 10px;
  top: -65px;
  font-size: 290px;
  font-weight: 900;
  color: rgba(255,255,255,.025);
}

/* INTRO NOTICE BAR */
.dashboard-intro-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  background: #fff6f6;
  border: 1px solid #f5e0e2;
  border-radius: 9px;
  margin-bottom: 22px;
}

.dashboard-intro-icon {
  min-width: 42px;
  height: 42px;
  display: flex;
  justify-content: center;
  align-items: center;
  background: white;
  color: var(--adv-red);
  border-radius: 9px;
  font-size: 21px;
  box-shadow: 0 2px 6px rgba(180, 35, 50, 0.08);
}

.dashboard-intro-body strong {
  display: block;
  color: var(--adv-navy);
  font-size: 13px;
  font-weight: 700;
}

.dashboard-intro-body p {
  font-size: 11px;
  line-height: 1.7;
  color: var(--adv-muted);
  margin: 3px 0 0;
}

.dashboard-intro-body p span {
  color: var(--adv-red);
  font-weight: 700;
}

/* STATS GRID */
.dashboard-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 16px;
  margin-bottom: 22px;
}

.dash-stat-card {
  background: white;
  border: 1px solid var(--adv-border);
  border-radius: 11px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 3px 12px rgba(36, 52, 77, 0.03);
  transition: transform .2s ease, box-shadow .2s ease;
}

.dash-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(36, 52, 77, 0.06);
}

.dash-stat-icon {
  min-width: 46px;
  height: 46px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.dash-stat-icon.icon-navy {
  background: #f0f4f9;
  color: var(--adv-navy);
}

.dash-stat-icon.icon-warning {
  background: #fff8eb;
  color: #d97706;
}

.dash-stat-icon.icon-success {
  background: #f0fdf4;
  color: #16a34a;
}

.dash-stat-icon.icon-announcement {
  background: #fff0f1;
  color: var(--adv-red);
}

.dash-stat-data {
  display: flex;
  flex-direction: column;
}

.dash-stat-number {
  font-size: 22px;
  font-weight: 800;
  color: var(--adv-navy);
  line-height: 1.2;
}

.dash-stat-label {
  font-size: 11px;
  color: var(--adv-muted);
  font-weight: 600;
  margin-top: 2px;
}

/* SECTION CARDS */
.dashboard-section-card {
  padding: 26px;
  border: 1px solid var(--adv-border);
  background: white;
  border-radius: 11px;
  box-shadow: 0 3px 15px rgba(36,52,77,.025);
  margin-bottom: 22px;
}

.dashboard-section-heading {
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  padding-bottom: 20px;
  margin-bottom: 22px;
  border-bottom: 1px solid #edf0f4;
}

.dashboard-section-icon {
  min-width: 47px;
  height: 47px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: var(--adv-red-light);
  color: var(--adv-red);
  font-size: 22px;
}

.dashboard-section-label {
  display: block;
  color: var(--adv-red);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1.5px;
  margin-bottom: 4px;
}

.dashboard-section-heading h2 {
  font-size: 17px;
  color: var(--adv-navy);
  font-weight: 800;
  margin: 0 0 4px;
}

.dashboard-section-heading p {
  font-size: 11px;
  color: var(--adv-muted);
  margin: 0;
}

.dashboard-section-number {
  position: absolute;
  right: 0;
  top: 0;
  color: #edf0f4;
  font-size: 30px;
  font-weight: 900;
}

/* ANNOUNCEMENTS STREAM */
.announcement-item {
  background: var(--adv-soft);
  border: 1px solid #e9edf3;
  border-left: 3px solid var(--adv-red);
  border-radius: 9px;
  padding: 18px 20px;
  height: 100%;
  transition: transform .2s ease, border-color .2s ease;
}

.announcement-item:hover {
  transform: translateY(-2px);
  border-color: #dbe2eb;
}

/* NEWS TICKER / RUNNING ANNOUNCEMENT */
.news-ticker-bar {
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid var(--adv-border);
  border-left: 4px solid var(--adv-red);
  border-radius: 10px;
  padding: 10px 16px;
  margin-bottom: 22px;
  box-shadow: 0 2px 8px rgba(36, 52, 77, 0.04);
  gap: 12px;
  overflow: hidden;
}
.news-ticker-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, var(--adv-red), #cf3142);
  color: white;
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: .5px;
  padding: 5px 10px;
  border-radius: 6px;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(180, 35, 50, 0.25);
}
.news-ticker-badge .ticker-pulse {
  width: 6px;
  height: 6px;
  background: white;
  border-radius: 50%;
  animation: tickerPulse 1.4s infinite ease-in-out;
}
@keyframes tickerPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: .4; transform: scale(.7); }
}
.news-ticker-track-wrap {
  flex: 1;
  height: 24px;
  position: relative;
  overflow: hidden;
}
.news-ticker-list {
  position: absolute;
  width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
  transition: transform .45s cubic-bezier(.4, 0, .2, 1);
}
.news-ticker-item {
  height: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.news-ticker-link {
  color: var(--adv-navy);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  transition: color .15s ease;
}
.news-ticker-link:hover {
  color: var(--adv-red);
}
.news-ticker-date {
  font-size: 11px;
  color: var(--adv-muted);
  font-weight: 500;
  flex-shrink: 0;
}
.news-ticker-controls {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}
.ticker-btn {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  border: 1px solid var(--adv-border);
  background: var(--adv-soft);
  color: var(--adv-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  cursor: pointer;
  padding: 0;
  transition: all .15s ease;
}
.ticker-btn:hover {
  background: white;
  color: var(--adv-red);
  border-color: var(--adv-red-accent);
}

.announcement-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}

.announcement-tag {
  font-size: 10px;
  font-weight: 700;
  color: var(--adv-red);
  background: #fff;
  border: 1px solid #f3d4d7;
  padding: 2px 8px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
}

.announcement-time {
  font-size: 11px;
  color: var(--adv-muted);
}

.announcement-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--adv-navy);
  margin-bottom: 8px;
  line-height: 1.4;
}

.announcement-snippet {
  font-size: 12px;
  color: #5d6d82;
  line-height: 1.65;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* ANNOUNCEMENT SLIDER */
.announcement-slider-wrap {
  position: relative;
  overflow: hidden;
  padding: 4px 2px;
}
.announcement-slider-track {
  display: flex;
  gap: 16px;
  transition: transform .35s cubic-bezier(.4,0,.2,1);
  will-change: transform;
}
.announcement-slide {
  flex: 0 0 calc(50% - 8px);
  min-width: 280px;
}
@media (max-width: 768px) {
  .announcement-slide {
    flex: 0 0 calc(100% - 0px);
    min-width: 0;
  }
}
.announcement-slide .announcement-item {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.announcement-slider-nav {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 14px;
}
.announcement-nav-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--adv-border);
  background: white;
  color: var(--adv-navy);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  cursor: pointer;
  transition: all .15s ease;
  box-shadow: 0 2px 5px rgba(36,52,77,.04);
}
.announcement-nav-btn:hover {
  background: var(--adv-red);
  color: white;
  border-color: var(--adv-red);
}
.announcement-nav-btn:disabled {
  opacity: .35;
  cursor: not-allowed;
  pointer-events: none;
}
.announcement-slider-dots {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-right: auto;
}
.announcement-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #cbd5e1;
  border: none;
  padding: 0;
  cursor: pointer;
  transition: all .2s ease;
}
.announcement-dot.active {
  width: 20px;
  border-radius: 10px;
  background: var(--adv-red);
}

/* RECENT REPORTS */
.dash-view-all-link {
  font-size: 12px;
  font-weight: 700;
  color: var(--adv-red);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: gap .2s ease;
}

.dash-view-all-link:hover {
  gap: 9px;
  color: #941c29;
}

.report-cards-stack {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.dash-report-card {
  background: #ffffff;
  border: 1px solid var(--adv-border);
  border-radius: 10px;
  padding: 18px 20px;
  box-shadow: 0 2px 8px rgba(36, 52, 77, 0.03);
  transition: all .2s ease;
}

.dash-report-card:hover {
  border-color: #d5dde6;
  box-shadow: 0 4px 14px rgba(36, 52, 77, 0.06);
}

.dash-report-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.dash-report-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.dash-report-case {
  font-size: 11px;
  font-weight: 800;
  color: var(--adv-navy);
  background: #f1f4f9;
  padding: 2px 8px;
  border-radius: 5px;
  letter-spacing: .5px;
}

.dash-report-date,
.dash-report-cat {
  font-size: 11px;
  color: var(--adv-muted);
}

.dash-report-cat {
  background: #f8fafc;
  padding: 2px 7px;
  border: 1px solid #edf1f6;
  border-radius: 5px;
}

/* STATUS PILLS */
.badge-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .2px;
}

.badge-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
}

.badge-status-pending {
  background: #fff8eb;
  color: #b45309;
  border: 1px solid #fed7aa;
}
.badge-status-pending .badge-dot {
  background: #f59e0b;
}

.badge-status-approved {
  background: #f0fdf4;
  color: #15803d;
  border: 1px solid #bbf7d0;
}
.badge-status-approved .badge-dot {
  background: #22c55e;
}

.badge-status-rejected {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}
.badge-status-rejected .badge-dot {
  background: #ef4444;
}

.dash-report-title {
  font-size: 15px;
  font-weight: 700;
  margin: 0 0 10px;
}

.dash-report-title a {
  color: var(--adv-navy);
  text-decoration: none;
  transition: color .2s ease;
}

.dash-report-title a:hover {
  color: var(--adv-red);
}

.dash-decision-box {
  background: #f8fafc;
  border-left: 3px solid #3b82f6;
  border-radius: 6px;
  padding: 10px 14px;
  margin-bottom: 12px;
}

.dash-decision-label {
  font-size: 11px;
  font-weight: 700;
  color: #1e3a8a;
  margin-bottom: 3px;
}

.dash-decision-text {
  font-size: 12px;
  color: #334155;
  margin: 0;
  line-height: 1.5;
}

.dash-report-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #f1f4f8;
  padding-top: 12px;
  margin-top: 4px;
}

.dash-btn-detail {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 700;
  color: var(--adv-navy);
  text-decoration: none;
  background: #f4f6fa;
  padding: 5px 12px;
  border-radius: 6px;
  transition: all .2s ease;
}

.dash-btn-detail:hover {
  background: var(--adv-red);
  color: #ffffff !important;
}

/* EMPTY STATES */
.dash-empty-box {
  text-align: center;
  padding: 35px 20px;
  background: var(--adv-soft);
  border: 1px dashed #d5dde6;
  border-radius: 9px;
}

.dash-empty-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--adv-navy);
  margin: 10px 0 4px;
}

.dash-empty-sub {
  font-size: 12px;
  color: var(--adv-muted);
  margin: 0;
}

.dash-empty-state-box {
  text-align: center;
  padding: 45px 24px;
  background: var(--adv-soft);
  border: 1px dashed #d5dde6;
  border-radius: 10px;
}

.dash-empty-icon-wrap {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: #eef2f8;
  color: var(--adv-navy);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  margin-bottom: 14px;
}

.dash-empty-heading {
  font-size: 16px;
  font-weight: 800;
  color: var(--adv-navy);
  margin-bottom: 6px;
}

.dash-empty-description {
  font-size: 12px;
  color: var(--adv-muted);
  max-width: 440px;
  margin: 0 auto 20px;
  line-height: 1.7;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .dashboard-hero {
    padding: 30px 22px;
  }
  .dashboard-hero-decoration {
    display: none;
  }
  .dashboard-hero h1 {
    font-size: 26px;
  }
  .dashboard-section-card {
    padding: 20px 16px;
  }
  .dashboard-section-number {
    display: none;
  }
  .dashboard-stats-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 480px) {
  .dashboard-stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<main class="container page-content advokita-page dashboard-page">

  <!-- HERO -->
  <section class="dashboard-hero">
    <div class="dashboard-hero-content">
      <span class="dashboard-eyebrow">
        <span></span> PUSAT LAYANAN &amp; INFORMASI ADVOKITA
      </span>

      <h1>Beranda <span>ADVOKITA.</span></h1>

      <p>
        Selamat datang di portal bantuan hukum dan pengaduan mahasiswa.
        Pantau status perkembangan tindak lanjut pengaduan Anda, simak pengumuman resmi kampus,
        dan ajukan laporan baru dengan jaminan perlindungan kerahasiaan penuh.
      </p>

      <div class="dashboard-hero-actions">
        <a class="btn-dashboard-primary" href="<?= escape(url('report')) ?>">
          <i class="bi bi-plus-circle-fill"></i>
          <span>Buat Laporan Baru</span>
        </a>
        <a class="btn-dashboard-secondary" href="<?= escape(url('history')) ?>">
          <i class="bi bi-clock-history"></i>
          <span>Lihat Riwayat Kasus</span>
        </a>
      </div>

      <div class="dashboard-hero-note">
        <i class="bi bi-shield-check"></i>
        <span>Suara Anda adalah langkah awal menuju keadilan.</span>
      </div>
    </div>

    <div class="dashboard-hero-decoration" aria-hidden="true">
      <div class="dashboard-hero-circle"></div>
      <i class="bi bi-speedometer2 dashboard-hero-icon"></i>
      <span class="dashboard-hero-watermark">A</span>
    </div>
  </section>

  <!-- INTRO NOTICE BAR -->
  <div class="dashboard-intro-bar">
    <div class="dashboard-intro-icon">
      <i class="bi bi-info-circle"></i>
    </div>
    <div class="dashboard-intro-body">
      <strong>Layanan Bantuan Hukum &amp; Perlindungan Mahasiswa</strong>
      <p>
        Setiap laporan ditinjau secara objektif oleh tim advokasi kampus.
        Anda dapat memilih opsi pelaporan <span>anonim</span> pada formulir pengaduan untuk menjamin kerahasiaan identitas Anda.
      </p>
    </div>
  </div>

  <?php if (!empty($announcements)): ?>
    <!-- RUNNING NEWS TICKER -->
    <div class="news-ticker-bar" id="newsTickerBar">
      <div class="news-ticker-badge">
        <span class="ticker-pulse"></span>
        <i class="bi bi-megaphone-fill"></i>
        <span>KILAS INFO</span>
      </div>
      <div class="news-ticker-track-wrap" id="tickerWrap">
        <ul class="news-ticker-list" id="tickerList">
          <?php foreach ($announcements as $idx => $ann): ?>
            <li class="news-ticker-item">
              <span class="news-ticker-date"><i class="bi bi-calendar3 me-1"></i><?= escape(format_date($ann['published_at'])) ?></span>
              <a href="#announcement-<?= (int) ($ann['id'] ?? $idx) ?>" class="news-ticker-link">
                <?= escape($ann['title']) ?> &mdash; <span class="text-muted fw-normal"><?= escape(mb_strimwidth($ann['body'], 0, 75, '...')) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="news-ticker-controls">
        <button type="button" class="ticker-btn" id="tickerPrev" aria-label="Sebelumnya"><i class="bi bi-chevron-up"></i></button>
        <button type="button" class="ticker-btn" id="tickerNext" aria-label="Berikutnya"><i class="bi bi-chevron-down"></i></button>
      </div>
    </div>
  <?php endif; ?>

  <!-- STATS OVERVIEW -->
  <section class="dashboard-stats-grid" aria-label="Statistik Pengaduan">
    <div class="dash-stat-card">
      <div class="dash-stat-icon icon-navy">
        <i class="bi bi-folder2-open"></i>
      </div>
      <div class="dash-stat-data">
        <span class="dash-stat-number"><?= $totalReports ?></span>
        <span class="dash-stat-label">Total Laporan Anda</span>
      </div>
    </div>

    <div class="dash-stat-card">
      <div class="dash-stat-icon icon-warning">
        <i class="bi bi-hourglass-split"></i>
      </div>
      <div class="dash-stat-data">
        <span class="dash-stat-number"><?= $pendingReports ?></span>
        <span class="dash-stat-label">Menunggu Ditinjau</span>
      </div>
    </div>

    <div class="dash-stat-card">
      <div class="dash-stat-icon icon-success">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <div class="dash-stat-data">
        <span class="dash-stat-number"><?= $approvedReports ?></span>
        <span class="dash-stat-label">Laporan Disetujui</span>
      </div>
    </div>

    <div class="dash-stat-card">
      <div class="dash-stat-icon icon-announcement">
        <i class="bi bi-megaphone-fill"></i>
      </div>
      <div class="dash-stat-data">
        <span class="dash-stat-number"><?= count($announcements) ?></span>
        <span class="dash-stat-label">Pengumuman Aktif</span>
      </div>
    </div>
  </section>

  <!-- SECTION 01: PENGUMUMAN -->
  <section class="dashboard-section-card" aria-labelledby="headingAnnouncements">
    <div class="dashboard-section-heading">
      <div class="dashboard-section-icon">
        <i class="bi bi-megaphone"></i>
      </div>
      <div>
        <span class="dashboard-section-label">BAGIAN 01</span>
        <h2 id="headingAnnouncements">Pengumuman Kampus</h2>
        <p>Informasi dan edaran resmi terkini dari tim advokasi serta bantuan hukum.</p>
      </div>
      <span class="dashboard-section-number">01</span>
    </div>

    <div class="announcement-stream">
      <?php if ($announcements === []): ?>
        <div class="dash-empty-box">
          <i class="bi bi-megaphone text-muted" style="font-size: 2.2rem;"></i>
          <p class="dash-empty-title">Belum Ada Pengumuman</p>
          <p class="dash-empty-sub">Pengumuman resmi dari pihak pengelola kampus akan dipublikasikan di sini.</p>
        </div>
      <?php else: ?>
        <div class="announcement-slider-wrap">
          <div class="announcement-slider-track" id="announcementTrack">
            <?php foreach ($announcements as $idx => $announcement): ?>
              <div class="announcement-slide">
                <article class="announcement-item" id="announcement-<?= (int) ($announcement['id'] ?? $idx) ?>">
                  <div class="announcement-header">
                    <span class="announcement-tag">
                      <i class="bi bi-info-circle-fill me-1"></i>
                      <?= escape($announcement['category']) ?><?= $announcement['is_pinned'] ? ' · PENTING' : '' ?>
                    </span>
                    <time class="announcement-time" datetime="<?= escape($announcement['published_at']) ?>">
                      <i class="bi bi-calendar3 me-1"></i> <?= escape(format_date($announcement['published_at'])) ?>
                    </time>
                  </div>
                  <h3 class="announcement-title"><?= escape($announcement['title']) ?></h3>
                  <p class="announcement-snippet"><?= escape($announcement['body']) ?></p>
                  <?php if ($announcement['expires_at'] !== null): ?>
                    <small class="announcement-expiry">Berlaku hingga <?= escape(format_date($announcement['expires_at'])) ?></small>
                  <?php endif; ?>
                </article>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if (count($announcements) > 2): ?>
            <div class="announcement-slider-nav">
              <div class="announcement-slider-dots" id="announcementDots"></div>
              <button type="button" class="announcement-nav-btn" id="announcementPrev" aria-label="Geser ke kiri">
                <i class="bi bi-chevron-left"></i>
              </button>
              <button type="button" class="announcement-nav-btn" id="announcementNext" aria-label="Geser ke kanan">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- SECTION 02: LAPORAN TERBARU -->
  <section class="dashboard-section-card" aria-labelledby="headingReports">
    <div class="dashboard-section-heading">
      <div class="dashboard-section-icon">
        <i class="bi bi-journal-text"></i>
      </div>
      <div>
        <span class="dashboard-section-label">BAGIAN 02</span>
        <h2 id="headingReports">Laporan Terbaru Anda</h2>
        <p>Pantau status perkembangan dan pertimbangan hukum pengaduan yang telah Anda kirimkan.</p>
      </div>
      <span class="dashboard-section-number">02</span>
    </div>

    <?php if ($reports !== []): ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small fw-semibold">Menampilkan <?= min(3, count($reports)) ?> laporan terbaru</span>
        <a href="<?= escape(url('history')) ?>" class="dash-view-all-link">
          Lihat semua riwayat <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="report-cards-stack">
        <?php foreach (array_slice($reports, 0, 3) as $reportItem): ?>
          <article class="dash-report-card">
            <div class="dash-report-top">
              <div class="dash-report-meta">
                <span class="dash-report-case">
                  <i class="bi bi-hash"></i><?= escape($reportItem['case_number']) ?>
                </span>
                <span class="dash-report-date">
                  <i class="bi bi-clock me-1"></i><?= escape(format_date($reportItem['created_at'])) ?>
                </span>
                <span class="dash-report-cat">
                  <i class="bi bi-tag me-1"></i><?= escape($reportItem['category']) ?>
                </span>
              </div>

              <?php
                $status = (string) ($reportItem['status'] ?? 'pending');
                $badgeClass = match ($status) {
                    'approved' => 'badge-status-approved',
                    'rejected' => 'badge-status-rejected',
                    default => 'badge-status-pending',
                };
              ?>
              <span class="badge-status-pill <?= $badgeClass ?>">
                <span class="badge-dot"></span>
                <?= escape(status_label($status)) ?>
              </span>
            </div>

            <h3 class="dash-report-title">
              <a href="<?= escape(url('detail', ['case' => $reportItem['case_number']])) ?>">
                <?= escape($reportItem['title']) ?>
              </a>
            </h3>

            <?php if (!empty($reportItem['decision_note'])): ?>
              <div class="dash-decision-box">
                <div class="dash-decision-label">
                  <i class="bi bi-chat-left-quote-fill me-1"></i> Catatan Pertimbangan Admin:
                </div>
                <p class="dash-decision-text"><?= escape($reportItem['decision_note']) ?></p>
              </div>
            <?php endif; ?>

            <div class="dash-report-footer">
              <span class="dash-report-sub-info">
                <?php if (!empty($reportItem['is_anonymous'])): ?>
                  <span class="text-muted small"><i class="bi bi-incognito me-1 text-danger"></i> Pengaduan Anonim</span>
                <?php else: ?>
                  <span class="text-muted small"><i class="bi bi-person-check me-1 text-success"></i> Identitas Terbuka</span>
                <?php endif; ?>
              </span>
              <a class="dash-btn-detail" href="<?= escape(url('detail', ['case' => $reportItem['case_number']])) ?>">
                <span>Lihat Detail</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

    <?php else: ?>

      <div class="dash-empty-state-box">
        <div class="dash-empty-icon-wrap">
          <i class="bi bi-inbox"></i>
        </div>
        <h3 class="dash-empty-heading">Belum Ada Laporan yang Dibuat</h3>
        <p class="dash-empty-description">
          Anda belum memiliki riwayat pengaduan. Jika Anda mengalami permasalahan hukum
          atau membutuhkan bantuan advokasi, ajukan laporan melalui formulir pengaduan resmi kami.
        </p>
        <a class="btn-dashboard-primary d-inline-flex" href="<?= escape(url('report')) ?>">
          <i class="bi bi-plus-circle-fill"></i>
          <span>Buat Laporan Sekarang</span>
        </a>
      </div>

    <?php endif; ?>
  </section>

</main>

<script>
// KILAS INFO (NEWS TICKER)
(function() {
  var list = document.getElementById('tickerList');
  if (!list) return;
  var items = list.querySelectorAll('.news-ticker-item');
  if (items.length <= 1) return;

  var current = 0;
  var total = items.length;
  var timer = null;

  function getItemHeight() {
    return items[0] ? items[0].offsetHeight || 24 : 24;
  }

  function moveTo(index) {
    current = (index + total) % total;
    var h = getItemHeight();
    list.style.transform = 'translateY(-' + (current * h) + 'px)';
  }

  function start() {
    stop();
    timer = setInterval(function() {
      moveTo(current + 1);
    }, 4000);
  }

  function stop() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  }

  var wrap = document.getElementById('newsTickerBar');
  if (wrap) {
    wrap.addEventListener('mouseenter', stop);
    wrap.addEventListener('mouseleave', start);
  }

  var prev = document.getElementById('tickerPrev');
  var next = document.getElementById('tickerNext');
  if (prev) {
    prev.addEventListener('click', function(e) {
      e.preventDefault();
      moveTo(current - 1);
      start();
    });
  }
  if (next) {
    next.addEventListener('click', function(e) {
      e.preventDefault();
      moveTo(current + 1);
      start();
    });
  }

  start();
})();

// ANNOUNCEMENT HORIZONTAL SLIDER
(function() {
  var wrap  = document.getElementById('announcementTrack');
  if (!wrap) return;
  var slides   = Array.from(wrap.querySelectorAll('.announcement-slide'));
  if (!slides.length) return;

  var prevBtn  = document.getElementById('announcementPrev');
  var nextBtn  = document.getElementById('announcementNext');
  var dotsWrap = document.getElementById('announcementDots');

  // Berapa slide yang terlihat sekaligus (1 di mobile, 2 di desktop)
  function perPage() {
    return window.innerWidth <= 768 ? 1 : 2;
  }

  var current = 0; // indeks slide paling kiri yang tampil

  function maxIndex() {
    return Math.max(0, slides.length - perPage());
  }

  function applyTransform() {
    var pp   = perPage();
    var gap  = 16;
    // Lebar satu slide = (wrap.offsetWidth - gap*(pp-1)) / pp
    var slideW = (wrap.offsetWidth - gap * (pp - 1)) / pp;
    var offset = current * (slideW + gap);
    wrap.style.transform = 'translateX(-' + offset + 'px)';
  }

  function updateButtons() {
    if (prevBtn) prevBtn.disabled = current <= 0;
    if (nextBtn) nextBtn.disabled = current >= maxIndex();
  }

  function updateDots() {
    if (!dotsWrap) return;
    var dots = dotsWrap.querySelectorAll('.announcement-dot');
    dots.forEach(function(d, i) {
      d.classList.toggle('active', i === current);
    });
  }

  function goTo(idx) {
    current = Math.max(0, Math.min(idx, maxIndex()));
    applyTransform();
    updateButtons();
    updateDots();
  }

  // Buat dots — satu per "halaman"
  if (dotsWrap) {
    dotsWrap.innerHTML = '';
    var totalDots = maxIndex() + 1;
    for (var i = 0; i < totalDots; i++) {
      (function(idx) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'announcement-dot' + (idx === 0 ? ' active' : '');
        dot.setAttribute('aria-label', 'Ke slide ' + (idx + 1));
        dot.addEventListener('click', function() { goTo(idx); });
        dotsWrap.appendChild(dot);
      })(i);
    }
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function() { goTo(current - 1); });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', function() { goTo(current + 1); });
  }

  // Recalculate saat resize
  window.addEventListener('resize', function() { goTo(Math.min(current, maxIndex())); });

  // Init
  goTo(0);
})();
</script>

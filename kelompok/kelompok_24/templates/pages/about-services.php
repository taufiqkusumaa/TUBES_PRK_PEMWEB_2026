<?php
require __DIR__ . '/about-style.php';
?>
<main class="container page-content advokita-page">
  <div class="dashboard-container">
    <?php require __DIR__ . '/about-hero.php'; ?>
    <?php require __DIR__ . '/about-nav.php'; ?>

    <section id="layanan" class="adv-section reveal">
      <div class="adv-section-heading">
        <div>
          <span class="adv-eyebrow">04 — LAYANAN HUKUM</span>
          <h2>Layanan <span>Kami</span></h2>
          <p class="adv-section-description">
            Berikut layanan yang kami sediakan untuk membantu masyarakat.
          </p>
        </div>
        <span class="adv-section-number">04</span>
      </div>
      <div class="adv-service-grid">
        <div class="adv-service-card">
          <div class="adv-service-icon"><i class="bi bi-chat-square-text"></i></div>
          <span class="adv-service-number">01 / KONSULTASI</span>
          <h3>Konsultasi &amp; Arahan Hukum</h3>
          <p>Membantu masyarakat memperoleh informasi awal dan arahan mengenai langkah yang dapat dilakukan ketika menghadapi persoalan hukum.</p>
          <a href="<?= escape(url('about-contact')) ?>" class="adv-service-arrow"><i class="bi bi-arrow-up-right"></i></a>
        </div>
        <div class="adv-service-card">
          <div class="adv-service-icon"><i class="bi bi-briefcase"></i></div>
          <span class="adv-service-number">02 / AKSES BANTUAN</span>
          <h3>Akses Bantuan Hukum</h3>
          <p>Menghubungkan masyarakat dengan lembaga atau pihak kompeten untuk memperoleh layanan bantuan hukum lebih lanjut.</p>
          <a href="<?= escape(url('about-contact')) ?>" class="adv-service-arrow"><i class="bi bi-arrow-up-right"></i></a>
        </div>
        <div class="adv-service-card">
          <div class="adv-service-icon"><i class="bi bi-megaphone"></i></div>
          <span class="adv-service-number">03 / EDUKASI</span>
          <h3>Edukasi &amp; Kampanye Hukum</h3>
          <p>Menyelenggarakan seminar, workshop, dan materi edukatif untuk meningkatkan kesadaran hukum di masyarakat.</p>
          <a href="<?= escape(url('about-contact')) ?>" class="adv-service-arrow"><i class="bi bi-arrow-up-right"></i></a>
        </div>
      </div>
    </section>

    <?php require __DIR__ . '/about-footer.php'; ?>
  </div>
</main>

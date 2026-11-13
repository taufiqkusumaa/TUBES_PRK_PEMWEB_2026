<?php
/** @var string $page */
$current = match ($page) {
    'about-vision-mission', 'about-vision' => 'about-vision-mission',
    'about-structure' => 'about-structure',
    'about-services' => 'about-services',
    'about-contact' => 'about-contact',
    default => 'about-profile',
};

$sectionLabels = [
    'about-profile' => 'Profil Lembaga',
    'about-vision-mission' => 'Visi & Misi',
    'about-structure' => 'Organisasi',
    'about-services' => 'Layanan',
    'about-contact' => 'Kontak',
];
?>

<!-- NAVIGASI SUB-HALAMAN -->
<nav class="adv-navigation" id="advNavigation">
  <div class="adv-nav-heading">
    <span>JELAJAHI</span>
    <span class="adv-nav-indicator">
      <i class="bi bi-list"></i> <?= escape($sectionLabels[$current] ?? 'Tentang Kami') ?>
    </span>
  </div>

  <div class="adv-nav-links">
    <a href="<?= escape(url('about-profile')) ?>" class="adv-nav-link <?= $current === 'about-profile' ? 'active' : '' ?>">
      <i class="bi bi-building"></i>
      <span>Sejarah</span>
    </a>
    <a href="<?= escape(url('about-vision-mission')) ?>" class="adv-nav-link <?= $current === 'about-vision-mission' ? 'active' : '' ?>">
      <i class="bi bi-bullseye"></i>
      <span>Visi &amp; Misi</span>
    </a>
    <a href="<?= escape(url('about-structure')) ?>" class="adv-nav-link <?= $current === 'about-structure' ? 'active' : '' ?>">
      <i class="bi bi-diagram-3"></i>
      <span>Organisasi</span>
    </a>
    <a href="<?= escape(url('about-services')) ?>" class="adv-nav-link <?= $current === 'about-services' ? 'active' : '' ?>">
      <i class="bi bi-briefcase"></i>
      <span>Layanan</span>
    </a>
    <a href="<?= escape(url('about-contact')) ?>" class="adv-nav-link <?= $current === 'about-contact' ? 'active' : '' ?>">
      <i class="bi bi-telephone"></i>
      <span>Kontak</span>
    </a>
  </div>
</nav>

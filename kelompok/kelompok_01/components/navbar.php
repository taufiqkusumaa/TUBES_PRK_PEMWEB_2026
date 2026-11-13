<header class="navbar">
    <div class="navbar-left">
        <button class="menu-toggle" id="menuToggle" type="button"
                aria-label="Buka navigasi utama" aria-controls="sidebar" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="navbar-breadcrumb" aria-label="Lokasi halaman">
            <span>SaveSmart</span>
            <span class="navbar-separator" aria-hidden="true">/</span>
            <span class="navbar-current"><?= htmlspecialchars($pageTitle ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <div class="navbar-right">
        <div class="user-avatar" aria-hidden="true">
            <?= strtoupper(substr($_SESSION['user']['nama_lengkap'] ?? 'P', 0, 1)) ?>
        </div>
        <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['user']['nama_lengkap'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8') ?></strong>
            <small><?= htmlspecialchars(ucwords(str_replace('_', ' ', $_SESSION['user']['role'] ?? 'pengguna')), ENT_QUOTES, 'UTF-8') ?></small>
        </div>
    </div>
</header>
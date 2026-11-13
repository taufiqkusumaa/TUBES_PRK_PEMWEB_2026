document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");

    const logoutButtons = document.querySelectorAll("#logoutBtn, [data-logout-trigger]");
    const logoutModal = document.getElementById("logoutModal");
    const logoutCancel = document.getElementById("logoutCancel");

    // ---------- Sidebar (HP) ----------
    if (menuToggle && sidebar) {
        menuToggle.addEventListener("click", function () {
            const isOpen = sidebar.classList.toggle("open");
            menuToggle.setAttribute("aria-expanded", String(isOpen));
            menuToggle.setAttribute("aria-label", isOpen ? "Tutup navigasi utama" : "Buka navigasi utama");
        });

        sidebar.querySelectorAll(".menu-item, .sidebar-logout").forEach(function (item) {
            item.addEventListener("click", function () {
                sidebar.classList.remove("open");
                menuToggle.setAttribute("aria-expanded", "false");
                menuToggle.setAttribute("aria-label", "Buka navigasi utama");
            });
        });
    }

    // ---------- Modal logout ----------
    function bukaModal() {
        logoutModal.hidden = false;
        document.body.classList.add("modal-open");
    }

    function tutupModal() {
        logoutModal.hidden = true;
        document.body.classList.remove("modal-open");
    }

    if (logoutModal) {
        logoutButtons.forEach(function (logoutBtn) {
            logoutBtn.addEventListener("click", function (event) {
                event.preventDefault();
                bukaModal();
            });
        });

        if (logoutCancel) {
            logoutCancel.addEventListener("click", tutupModal);
        }

        logoutModal.addEventListener("click", function (event) {
            if (event.target === logoutModal) tutupModal();
        });
    }

    // ---------- Tombol Escape ----------
    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") return;

        if (logoutModal && !logoutModal.hidden) {
            tutupModal();
        } else if (sidebar) {
            sidebar.classList.remove("open");
            if (menuToggle) {
                menuToggle.setAttribute("aria-expanded", "false");
                menuToggle.setAttribute("aria-label", "Buka navigasi utama");
            }
        }
    });
});
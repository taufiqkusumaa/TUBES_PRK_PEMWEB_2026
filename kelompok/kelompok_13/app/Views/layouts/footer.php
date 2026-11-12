<!-- Footer -->
    <footer class="mt-5 pt-3 border-top text-center text-muted small no-print">
        <p class="mb-1">&copy; <?= date('Y') ?> <strong>Machinify</strong> — Kelompok 13 Praktikum Pemrograman Web TI Unila.</p>
    </footer>
</main>
</div> <!-- End .app-wrapper -->

<!-- Bootstrap 5.3 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global UI Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle Sidebar pada tampilan Mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');
    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function () {
            appSidebar.classList.toggle('show');
        });
    }
});
</script>
</body>
</html>
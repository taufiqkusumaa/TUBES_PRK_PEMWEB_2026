document.addEventListener('DOMContentLoaded', () => {
    const adminToggle = document.querySelector('[data-admin-toggle]');
    const sidebar = document.querySelector('[data-admin-sidebar]');
    if (adminToggle && sidebar) {
        adminToggle.addEventListener('click', () => sidebar.classList.toggle('is-open'));
    }

    document.querySelectorAll('[data-category-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm('Yakin ingin menghapus kategori ini?')) {
                event.preventDefault();
            }
        });
    });
});
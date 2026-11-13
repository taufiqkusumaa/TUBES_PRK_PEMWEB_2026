/**
 * LABCAT - Auth & Setup JavaScript
 * Digunakan pada: admin/login.php & setup-admin.php
 */

document.addEventListener('DOMContentLoaded', () => {
    const passwordToggle = document.querySelector('[data-password-toggle]');
    const passwordInput = document.querySelector('[data-password]');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            passwordToggle.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Lihat password');
        });
    }
});

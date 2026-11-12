// kelompok/kelompok_13/public/assets/js/pelanggan.js

document.addEventListener('DOMContentLoaded', function () {
    const API_ENDPOINT = 'api.php?endpoint=pelanggan';

    function showAlert(elemId, message, isSuccess = false) {
        const el = document.getElementById(elemId);
        if (!el) return;
        el.className = `alert small py-2 px-3 mb-3 ${isSuccess ? 'alert-success' : 'alert-danger'}`;
        el.textContent = message;
        el.classList.remove('d-none');
    }

    // 1. SUBMIT LOGIN
    const formLogin = document.getElementById('formLogin');
    if (formLogin) {
        formLogin.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitAuth');
            btn.disabled = true;
            btn.textContent = 'Memverifikasi...';

            const payload = Object.fromEntries(new FormData(formLogin).entries());

            try {
                const res = await fetch(`${API_ENDPOINT}&action=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.success) {
                    showAlert('authAlert', 'Login berhasil! Mengalihkan...', true);
                    setTimeout(() => {
                        const role = (result.data && result.data.role) ? result.data.role.toLowerCase() : '';
                        if (role === 'admin') {
                            window.location.href = 'index.php?page=dashboard';
                        } else if (role === 'teknisi') {
                            window.location.href = 'index.php?page=mekanik';
                        } else {
                            window.location.href = 'index.php?page=transaksi';
                        }
                    }, 600);
                } else {
                    showAlert('authAlert', result.message || 'Email atau kata sandi tidak valid.');
                    btn.disabled = false;
                    btn.textContent = 'Masuk ke Akun';
                }
            } catch (err) {
                showAlert('authAlert', 'Terjadi kesalahan jaringan atau server.');
                btn.disabled = false;
                btn.textContent = 'Masuk ke Akun';
            }
        });
    }

    // 2. SUBMIT REGISTER
    const formRegister = document.getElementById('formRegister');
    if (formRegister) {
        formRegister.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitAuth');
            btn.disabled = true;
            btn.textContent = 'Mendaftarkan...';

            const payload = Object.fromEntries(new FormData(formRegister).entries());

            try {
                const res = await fetch(`${API_ENDPOINT}&action=register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.success) {
                    showAlert('authAlert', `${result.message || 'Pendaftaran akun berhasil.'} Mengalihkan ke login...`, true);
                    setTimeout(() => {
                        window.location.href = 'index.php?page=pelanggan&mode=login';
                    }, 1000);
                } else {
                    showAlert('authAlert', result.message || 'Gagal membuat akun baru.');
                    btn.disabled = false;
                    btn.textContent = 'Daftar Akun Sekarang';
                }
            } catch (err) {
                showAlert('authAlert', 'Gagal menghubungi server.');
                btn.disabled = false;
                btn.textContent = 'Daftar Akun Sekarang';
            }
        });
    }

    // 3. EDIT PROFIL
    const formProfile = document.getElementById('formProfile');
    if (formProfile) {
        async function loadProfileData() {
            try {
                const res = await fetch(`${API_ENDPOINT}&action=profile`);
                const result = await res.json();
                if (result.success && result.data) {
                    const profEmail = document.getElementById('profEmail');
                    const profNama = document.getElementById('profNama');
                    const profTelp = document.getElementById('profTelp');
                    if (profEmail) profEmail.value = result.data.email || '';
                    if (profNama) profNama.value = result.data.nama || '';
                    if (profTelp) profTelp.value = result.data.no_telp || '';
                }
            } catch (err) {
                console.error(err);
            }
        }
        loadProfileData();

        formProfile.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveProfile');
            btn.disabled = true;
            btn.innerHTML = 'Menyimpan...';

            const payload = Object.fromEntries(new FormData(formProfile).entries());

            try {
                const res = await fetch(`${API_ENDPOINT}&action=profile`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.success) {
                    showAlert('profileAlert', 'Profil berhasil disimpan!', true);
                    setTimeout(() => location.reload(), 800);
                } else {
                    showAlert('profileAlert', result.message || 'Gagal memperbarui profil.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Simpan Perubahan Profil';
                }
            } catch (err) {
                showAlert('profileAlert', 'Terjadi kesalahan sistem.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Simpan Perubahan Profil';
            }
        });
    }

    // 4. KELOLA USER (ADMIN)
    const tableUsersBody = document.getElementById('tableUsersBody');
    const filterRole = document.getElementById('filterRole');
    const searchUser = document.getElementById('searchUser');
    const btnRefreshUsers = document.getElementById('btnRefreshUsers');
    let cachedUsers = [];

    if (tableUsersBody) {
        async function loadUsersData() {
            tableUsersBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Memuat data pengguna...</td></tr>`;
            try {
                const res = await fetch(`${API_ENDPOINT}&action=users`);
                const result = await res.json();
                if (result.success && Array.isArray(result.data)) {
                    cachedUsers = result.data;
                    renderUsersTable();
                } else {
                    tableUsersBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${result.message || 'Gagal memuat pengguna'}</td></tr>`;
                }
            } catch (err) {
                tableUsersBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal menghubungi server API.</td></tr>`;
            }
        }

        function renderUsersTable() {
            const roleVal = (filterRole ? filterRole.value : '').toLowerCase();
            const searchVal = (searchUser ? searchUser.value : '').toLowerCase().trim();

            const filtered = cachedUsers.filter(u => {
                const matchRole = !roleVal || (u.role || '').toLowerCase() === roleVal;
                const matchSearch = !searchVal ||
                    (u.nama || '').toLowerCase().includes(searchVal) ||
                    (u.email || '').toLowerCase().includes(searchVal);
                return matchRole && matchSearch;
            });

            if (filtered.length === 0) {
                tableUsersBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data pengguna yang cocok.</td></tr>`;
                return;
            }

            tableUsersBody.innerHTML = filtered.map(u => {
                const isAktif = parseInt(u.status_aktif) === 1;
                const statusBadge = isAktif
                    ? `<span class="status-badge status-success"><span class="dot"></span>Aktif</span>`
                    : `<span class="status-badge status-pending"><span class="dot"></span>Nonaktif</span>`;

                let actionBtn = `<span class="text-muted small">-</span>`;
                if (u.role === 'teknisi') {
                    const target = isAktif ? 0 : 1;
                    const btnClass = isAktif ? 'btn-surface' : 'btn-royal';
                    const btnLabel = isAktif ? 'Nonaktifkan' : 'Aktifkan';
                    actionBtn = `
                        <button type="button" class="${btnClass} py-1 px-2 btn-toggle-status" data-id="${u.id}" data-target="${target}">
                            ${btnLabel}
                        </button>
                    `;
                }

                return `
                    <tr>
                        <td class="font-mono text-muted">${u.id}</td>
                        <td class="fw-semibold" style="color: var(--text-primary);">${u.nama}</td>
                        <td class="font-mono" style="color: var(--text-secondary);">${u.email}</td>
                        <td class="font-mono">${u.no_telp || '-'}</td>
                        <td><span class="role-pill">${(u.role || '').toUpperCase()}</span></td>
                        <td>${statusBadge}</td>
                        <td class="text-end">${actionBtn}</td>
                    </tr>
                `;
            }).join('');
        }

        tableUsersBody.addEventListener('click', async function (e) {
            const btn = e.target.closest('.btn-toggle-status');
            if (!btn) return;

            const userId = btn.dataset.id;
            const targetStatus = parseInt(btn.dataset.target);
            btn.disabled = true;

            try {
                const res = await fetch(`${API_ENDPOINT}&action=toggle-status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, status: targetStatus })
                });
                const result = await res.json();
                if (result.success) {
                    loadUsersData();
                } else {
                    alert(result.message || 'Gagal mengubah status akun.');
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Gagal menghubungi server.');
                btn.disabled = false;
            }
        });

        if (filterRole) filterRole.addEventListener('change', renderUsersTable);
        if (searchUser) searchUser.addEventListener('input', renderUsersTable);
        if (btnRefreshUsers) btnRefreshUsers.addEventListener('click', loadUsersData);

        loadUsersData();
    }
});
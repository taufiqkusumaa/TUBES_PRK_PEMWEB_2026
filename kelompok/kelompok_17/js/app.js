/**
 * KabarSI - Client-Side API Consumer (Fetch API & DOM Manipulation)
 * File: js/app.js
 */

// -------------------------------------------------------------
// 1. ENDPOINT BASE URL
// -------------------------------------------------------------
const APP_ROOT = new URL('../', document.currentScript.src);
const API = {
    aspirasi: new URL('api/aspirasi.php', APP_ROOT).href,
    berita: new URL('api/berita.php', APP_ROOT).href,
    kategori: new URL('api/kategori.php', APP_ROOT).href,
    komentar: new URL('api/komentar.php', APP_ROOT).href,
    users: new URL('api/users.php', APP_ROOT).href
};

// -------------------------------------------------------------
// 2. UTILS / HELPER FUNCTIONS
// -------------------------------------------------------------

/**
 * Menampilkan Alert / Toast Notifikasi Dinamisg
 */
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alert-box');
    if (!alertContainer) return;

    alertContainer.textContent = message;
    alertContainer.className = `mb-4 rounded-lg p-3 ${type === 'danger' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800'}`;
    alertContainer.style.display = 'block';

    setTimeout(() => {
        alertContainer.style.display = 'none';
    }, 4000);
}

// -------------------------------------------------------------
// 3. MODUL ASPIRASI
// -------------------------------------------------------------

/**
 * Load Semua Data Aspirasi (Untuk Admin Dashboard / User Feed)
 */
async function loadAspirasi(keyword = '') {
    const container = document.getElementById('tabel-aspirasi') || document.getElementById('container-aspirasi');
    const tbody = document.getElementById('tabel-body-aspirasi');
    if (!container && !tbody) return;

    try {
        const response = await fetch(API.aspirasi);
        const result = await response.json();

        if (response.ok && result.status === 'success' && Array.isArray(result.data)) {
            renderAspirasi(result.data, keyword);
        } else {
            const message = result.message || 'Gagal mengambil data aspirasi';
            showAlert(message, 'danger');
            setAspirasiTableMessage(message);
        }
    } catch (err) {
        showAlert(`Kesalahan koneksi aspirasi: ${err.message}`, 'danger');
        setAspirasiTableMessage('Data aspirasi gagal dimuat. Muat ulang halaman untuk mencoba lagi.');
    }
}

function setAspirasiTableMessage(message) {
    const tbody = document.getElementById('tabel-body-aspirasi');
    if (!tbody) return;

    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 5;
    cell.className = 'p-6 text-center text-red-700';
    cell.textContent = message;
    row.appendChild(cell);
    tbody.replaceChildren(row);
}

/**
 * Render Data Aspirasi ke DOM
 */
function renderAspirasi(list = [], keyword = '') {
    const tbody = document.getElementById('tabel-body-aspirasi');
    const feed = document.getElementById('aspirasi-feed');

    // Jika menggunakan tabel (Admin View)
    if (tbody) {
        tbody.replaceChildren();
        if (list.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.className = 'p-6 text-center text-slate-500';
            cell.textContent = 'Belum ada aspirasi.';
            row.appendChild(cell);
            tbody.appendChild(row);
        }

        list.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'border-t align-top';
            const pengirim = item.pengirim || (Number(item.is_anonim) === 1 ? 'Anonim' : item.nama_lengkap || '-');
            const tanggal = new Date(item.tanggal_kirim);
            const cells = [
                item.judul_aspirasi,
                item.nama_kategori || '-',
                pengirim,
                Number.isNaN(tanggal.getTime()) ? '-' : tanggal.toLocaleString('id-ID')
            ];
            cells.forEach(value => {
                const cell = document.createElement('td');
                cell.className = 'p-3';
                cell.textContent = value;
                tr.appendChild(cell);
            });

            const statusCell = document.createElement('td');
            statusCell.className = 'p-3';
            const form = document.createElement('form');
            form.className = 'flex items-center gap-2';
            form.dataset.statusForm = '';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id_aspirasi';
            idInput.value = item.id_aspirasi;
            form.appendChild(idInput);

            const select = document.createElement('select');
            select.name = 'status';
            select.className = 'border rounded px-2 py-1';
            select.setAttribute('aria-label', `Status aspirasi: ${item.judul_aspirasi}`);
            const statuses = [
                ['menunggu', 'Menunggu'],
                ['ditolak', 'Ditolak'],
                ['publikasi', 'Publikasi'],

            ];
            if (!statuses.some(([value]) => value === item.status)) {
                statuses.push([item.status, `${item.status} (status lama)`]);
            }
            statuses.forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = item.status === value;
                select.appendChild(option);
            });
            form.appendChild(select);

            const submit = document.createElement('button');
            submit.type = 'submit';
            submit.className = 'bg-teal-600 text-white px-3 py-1 rounded hover:bg-teal-700';
            submit.textContent = 'Simpan';
            form.appendChild(submit);
            statusCell.appendChild(form);
            tr.appendChild(statusCell);
            tbody.appendChild(tr);
        });
    }

    // Jika menggunakan Kartu / Feed (User View)
    if (feed) {
        feed.innerHTML = '';
        if (list.length === 0) {
            feed.innerHTML = '<p class="text-muted">Belum ada aspirasi dikirim.</p>';
            return;
        }

        list.forEach(item => {
            const card = document.createElement('div');
            card.className = 'card-aspirasi';
            card.innerHTML = `
                <h3>${item.judul_aspirasi}</h3>
                <small>Oleh: ${item.pengirim} | Kategori: ${item.nama_kategori} | Status: ${item.status}</small>
                <p>${item.isi_aspirasi}</p>
                <button onclick="loadKomentar('aspirasi', ${item.id_aspirasi})" class="btn-link">Lihat Komentar</button>
            `;
            feed.appendChild(card);
        });
    }
}

/**
 * Submit Form Kirim Aspirasi Baru (POST)
 */
const formAspirasi = document.getElementById('form-tambah-aspirasi');
if (formAspirasi) {
    formAspirasi.addEventListener('submit', async function (e) {
        e.preventDefault();

        const payload = {
            id_user: document.getElementById('id_user').value,
            id_kategori: document.getElementById('id_kategori').value,
            judul_aspirasi: document.getElementById('judul_aspirasi').value.trim(),
            isi_aspirasi: document.getElementById('isi_aspirasi').value.trim(),
            is_anonim: document.getElementById('is_anonim')?.checked ? 1 : 0
        };

        try {
            const response = await fetch(API.aspirasi, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showAlert(result.message, 'success');
                formAspirasi.reset();
                loadAspirasi();
            } else {
                showAlert(result.message || 'Gagal mengirim aspirasi.', 'danger');
            }
        } catch (err) {
            showAlert(`Terjadi kesalahan: ${err.message}`, 'danger');
        }
    });
}

/**
 * Update Status Aspirasi oleh Admin (PUT)
 */
async function updateStatusAspirasi(idAspirasi, newStatus) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            throw new Error('Token CSRF tidak ditemukan. Muat ulang halaman.');
        }

        const response = await fetch(`${API.aspirasi}?id=${idAspirasi}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ status: newStatus })
        });
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            showAlert(result.message, 'success');
            loadAspirasi();
        } else {
            showAlert(result.message || 'Gagal memperbarui status', 'danger');
        }
    } catch (err) {
        showAlert(`Error: ${err.message}`, 'danger');
    }
}

/**
 * Hapus Data Aspirasi (DELETE)
 */
async function deleteAspirasi(idAspirasi) {
    if (!confirm('Apakah Anda yakin ingin menghapus aspirasi ini?')) return;

    try {
        const response = await fetch(`${API.aspirasi}?id=${idAspirasi}`, {
            method: 'DELETE'
        });
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            showAlert(result.message, 'success');
            loadAspirasi();
        } else {
            showAlert(result.message || 'Gagal menghapus aspirasi', 'danger');
        }
    } catch (err) {
        showAlert(`Error: ${err.message}`, 'danger');
    }
}

// -------------------------------------------------------------
// 4. MODUL BERITA
// -------------------------------------------------------------

/**
 * Load Data Berita (Dengan Live Search)
 */
async function loadBerita(keyword = '') {
    const container = document.getElementById('container-berita');
    if (!container) return;

    const url = keyword ? `${API.berita}?search=${encodeURIComponent(keyword)}` : API.berita;

    try {
        const response = await fetch(url);
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            renderBerita(result.data);
        } else {
            showAlert(result.message || 'Gagal mengambil berita', 'danger');
        }
    } catch (err) {
        showAlert(`Error koneksi berita: ${err.message}`, 'danger');
    }
}

/**
 * Render List Berita
 */
function renderBerita(list = []) {
    const container = document.getElementById('container-berita');
    if (!container) return;

    container.innerHTML = '';
    if (list.length === 0) {
        container.innerHTML = '<p class="text-muted">Tidak ada berita ditemukan.</p>';
        return;
    }

    list.forEach(item => {
        const itemBerita = document.createElement('div');
        itemBerita.className = 'card-berita';
        itemBerita.innerHTML = `
            <img src="${item.gambar_url || 'assets/img/default-news.jpg'}" alt="${item.judul_berita}" class="img-berita">
            <div class="berita-content">
                <span class="badge">${item.nama_kategori}</span>
                <h3>${item.judul_berita}</h3>
                <small>Penulis: ${item.penulis} | ${new Date(item.tanggal_publikasi).toLocaleDateString('id-ID')}</small>
                <p>${item.isi_berita.substring(0, 120)}...</p>
                <a href="berita_detail.php?id=${item.id_berita}" class="btn btn-primary btn-sm">Baca Selengkapnya</a>
            </div>
        `;
        container.appendChild(itemBerita);
    });
}

// Live Search Berita
const inputSearchBerita = document.getElementById('search-berita');
if (inputSearchBerita) {
    let timer = null;
    inputSearchBerita.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            loadBerita(inputSearchBerita.value.trim());
        }, 300);
    });
}

// -------------------------------------------------------------
// 5. MODUL KOMENTAR
// -------------------------------------------------------------

/**
 * Load Komentar berdasarkan Berita / Aspirasi
 */
async function loadKomentar(tipe, idTarget) {
    const containerKomentar = document.getElementById('list-komentar');
    if (!containerKomentar) return;

    const param = tipe === 'berita' ? `id_berita=${idTarget}` : `id_aspirasi=${idTarget}`;

    try {
        const response = await fetch(`${API.komentar}?${param}`);
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            containerKomentar.innerHTML = '';
            if (result.data.length === 0) {
                containerKomentar.innerHTML = '<p class="text-muted">Belum ada komentar.</p>';
                return;
            }

            result.data.forEach(k => {
                const div = document.createElement('div');
                div.className = 'box-komentar';
                div.innerHTML = `
                    <strong>${k.pengirim}</strong> <small>${new Date(k.tanggal_komentar).toLocaleString('id-ID')}</small>
                    <p>${k.isi_komentar}</p>
                `;
                containerKomentar.appendChild(div);
            });
        }
    } catch (err) {
        console.error('Gagal mengambil komentar:', err);
    }
}

/**
 * Submit Komentar Baru (POST)
 */
const formKomentar = document.getElementById('form-tambah-komentar');
if (formKomentar) {
    formKomentar.addEventListener('submit', async function (e) {
        e.preventDefault();

        const payload = {
            id_user: document.getElementById('komentar_id_user').value,
            id_berita: document.getElementById('komentar_id_berita')?.value || null,
            id_aspirasi: document.getElementById('komentar_id_aspirasi')?.value || null,
            isi_komentar: document.getElementById('isi_komentar').value.trim(),
            is_anonim: document.getElementById('komentar_is_anonim')?.checked ? 1 : 0
        };

        try {
            const response = await fetch(API.komentar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showAlert(result.message, 'success');
                formKomentar.reset();
                const targetId = payload.id_berita || payload.id_aspirasi;
                const tipe = payload.id_berita ? 'berita' : 'aspirasi';
                loadKomentar(tipe, targetId);
            } else {
                showAlert(result.message || 'Gagal mengirim komentar.', 'danger');
            }
        } catch (err) {
            showAlert(`Error: ${err.message}`, 'danger');
        }
    });
}

// -------------------------------------------------------------
// 6. INISIALISASI SAAT HALAMAN SELESAI DIMUAT
// -------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const aspirasiTableBody = document.getElementById('tabel-body-aspirasi');
    if (aspirasiTableBody) {
        aspirasiTableBody.addEventListener('submit', event => {
            if (!(event.target instanceof HTMLFormElement) || !event.target.matches('[data-status-form]')) return;
            event.preventDefault();

            const id = event.target.elements.namedItem('id_aspirasi')?.value;
            const status = event.target.elements.namedItem('status')?.value;
            if (!id || !status) {
                showAlert('ID atau status aspirasi tidak valid.', 'danger');
                return;
            }

            updateStatusAspirasi(id, status);
        });
    }

    loadAspirasi();
    loadBerita();
});
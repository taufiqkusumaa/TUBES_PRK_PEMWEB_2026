/**
 * mekanik.js - Antrean Work Order Teknisi & update status pengerjaan.
 *
 * Endpoint: api.php?endpoint=mekanik&action=antrean|detail|update_status
 * Format respons: { success, message, data }
 */
(() => {
    'use strict';

    // Ubah di sini bila lokasi api.php berbeda dari halaman ini.
    const API = 'api.php?endpoint=mekanik';

    const STEPS = ['Pending', 'Antre', 'Dikerjakan', 'Siap Diambil'];
    // Kelas status-badge dari style.css (Ezzra): pending = oranye, process = cyan, success = hijau.
    const KELAS_STATUS = {
        'Pending': 'status-pending', 'Antre': 'status-pending', 'Dikerjakan': 'status-process',
        'Siap Diambil': 'status-success', 'Selesai': 'status-success',
    };

    const $ = (sel, root = document) => root.querySelector(sel);
    const state = { rows: [], detailId: null };

    // ------------------------------------------------------------------
    // Utilitas
    // ------------------------------------------------------------------
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));
    const rupiah = (n) => new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(Number(n) || 0);
    const tanggal = (s) => {
        if (!s) return '-';
        const d = new Date(String(s).replace(' ', 'T'));
        return isNaN(d) ? esc(s) : d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    };
    const durasi = (menit) => {
        const m = Number(menit) || 0;
        return m >= 60 ? `${Math.floor(m / 60)} jam${m % 60 ? ' ' + (m % 60) + ' mnt' : ''}` : `${m} mnt`;
    };
    const badge = (status) =>
        `<span class="status-badge ${KELAS_STATUS[status] || 'status-pending'}"><span class="dot"></span>${esc(status)}</span>`;
    const pathAman = (p) => (typeof p === 'string' && p.startsWith('uploads/') && !p.includes('..')) ? p : null;

    // Sub-aksi dikirim lewat parameter "action" (sama dengan api.php?endpoint=auth&action=logout).
    async function api(aksi, { method = 'GET', params = {}, body = null } = {}) {
        const qs = new URLSearchParams({ action: aksi, ...params });
        const opt = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
        if (body !== null) {
            opt.headers['Content-Type'] = 'application/json';
            opt.body = JSON.stringify(body);
        }
        let res;
        try {
            res = await fetch(`${API}&${qs}`, opt);
        } catch (e) {
            throw new Error('Tidak dapat terhubung ke server.');
        }
        let json = null;
        try { json = await res.json(); } catch (e) { /* ditangani di bawah */ }
        if (!json) throw new Error(`Respons server tidak valid (HTTP ${res.status}).`);
        if (!res.ok || !json.success) {
            const err = new Error(json.message || `Terjadi kesalahan (HTTP ${res.status}).`);
            err.status = res.status;
            throw err;
        }
        return json;
    }

    function tampilkanAlert(tipe, pesan) {
        const area = $('#alertArea');
        const el = document.createElement('div');
        el.className = `alert alert-dismissible fade show wo-alert-${tipe}`;
        el.setAttribute('role', 'alert');
        el.textContent = pesan;
        const tutup = document.createElement('button');
        tutup.type = 'button';
        tutup.className = 'btn-close';
        tutup.setAttribute('data-bs-dismiss', 'alert');
        tutup.setAttribute('aria-label', 'Tutup');
        el.appendChild(tutup);
        area.replaceChildren(el);
        if (tipe === 'success') setTimeout(() => el.remove(), 5000);
    }

    // ------------------------------------------------------------------
    // Daftar antrean
    // ------------------------------------------------------------------
    async function muatAntrean() {
        const spin = $('#spinMuat');
        spin.classList.remove('d-none');
        try {
            const status = $('#filterStatus').value;
            const { data } = await api('antrean', { params: status ? { status } : {} });
            state.rows = data.antrean;
            renderRingkasan(data.ringkasan);
            renderTabel();
        } catch (e) {
            state.rows = [];
            renderTabel();
            $('#tbodyWO').innerHTML =
                `<tr><td colspan="7" class="text-center text-danger py-4">${esc(e.message)}</td></tr>`;
        } finally {
            spin.classList.add('d-none');
        }
    }

    function renderRingkasan(r) {
        $('#statAntre').textContent = r.antre;
        $('#statDikerjakan').textContent = r.dikerjakan;
        $('#statSiap').textContent = r.siap_diambil;
        $('#statSelesaiHariIni').textContent = r.selesai_hari_ini;
    }

    function renderTabel() {
        const kunci = $('#cariWO').value.trim().toLowerCase();
        const rows = state.rows.filter((r) => !kunci || [
            r.kode_transaksi, r.no_polisi, r.nama_model, r.nama_customer, r.nama_paket,
        ].some((v) => String(v || '').toLowerCase().includes(kunci)));

        const tbody = $('#tbodyWO');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center wo-muted py-4">Tidak ada work order.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map((r) => `
            <tr>
                <td class="text-nowrap fw-semibold font-mono">${esc(r.kode_transaksi)}</td>
                <td>
                    <span class="plate-badge mb-1">${esc(r.no_polisi)}</span>
                    <div class="small wo-muted">${esc(r.nama_model)} &middot; ${esc(r.nama_customer)}</div>
                </td>
                <td>
                    <div>${esc(r.nama_paket)}</div>
                    <div class="small wo-muted">Estimasi ${durasi(r.estimasi_durasi_menit)}</div>
                </td>
                <td class="wo-keluhan"><div class="text-truncate" title="${esc(r.keluhan_mesin)}">${esc(r.keluhan_mesin)}</div></td>
                <td>${badge(r.status_servis)}</td>
                <td>${r.nama_teknisi ? esc(r.nama_teknisi) : '<span class="wo-muted">Belum ditugaskan</span>'}</td>
                <td class="text-end text-nowrap">
                    <button type="button" class="btn-royal py-1 px-2" data-aksi="detail" data-id="${Number(r.id)}">Detail / Update</button>
                </td>
            </tr>`).join('');
    }

    // ------------------------------------------------------------------
    // Modal detail & update status
    // ------------------------------------------------------------------
    let modalDetail = null;
    const getModal = () => (modalDetail ??= new bootstrap.Modal($('#modalDetail')));

    async function bukaDetail(id) {
        try {
            const { data } = await api('detail', { params: { id } });
            state.detailId = id;
            renderDetail(data);
            getModal().show();
        } catch (e) {
            tampilkanAlert('danger', e.message);
        }
    }

    function renderStepper(status) {
        const idx = status === 'Selesai' ? STEPS.length : STEPS.indexOf(status);
        $('#detailStepper').innerHTML = `<ol class="wo-stepper">${STEPS.map((s, i) =>
            `<li data-no="${i + 1}" class="${i < idx ? 'done' : (i === idx ? 'active' : '')}">${esc(s)}</li>`
        ).join('')}</ol>`;
    }

    function renderFoto(path) {
        const box = $('#detailFoto');
        const aman = pathAman(path);
        if (!aman) {
            box.innerHTML = '<span class="wo-muted small">Tidak ada foto kendala</span>';
            return;
        }
        const img = document.createElement('img');
        img.className = 'img-fluid wo-foto';
        img.alt = 'Foto kendala kendaraan';
        img.addEventListener('error', () => {
            box.innerHTML = '<span class="wo-muted small">Foto tidak ditemukan di server</span>';
        });
        img.src = aman;
        box.replaceChildren(img);
    }

    function renderDetail(d) {
        $('#judulDetail').textContent = `Work Order ${d.kode_transaksi}`;
        renderStepper(d.status_servis);
        renderFoto(d.foto_kendala);

        const baris = [
            ['Status', badge(d.status_servis)],
            ['Customer', `${esc(d.nama_customer)}${d.telp_customer ? ' &middot; ' + esc(d.telp_customer) : ''}`],
            ['Kendaraan', `${esc(d.no_polisi)} &middot; ${esc(d.nama_model)}${d.warna ? ' (' + esc(d.warna) + ')' : ''}`],
            ['Jenis', `${esc(d.jenis)} ${esc(d.merk)}`],
            ['Paket', `${esc(d.nama_paket)} (est. ${durasi(d.estimasi_durasi_menit)})`],
            ['Teknisi', d.nama_teknisi ? esc(d.nama_teknisi) : '<span class="wo-muted">Belum ditugaskan</span>'],
            ['Tanggal pesan', tanggal(d.tanggal_pesan)],
            ['Selesai', tanggal(d.tanggal_selesai)],
            ['Keluhan', esc(d.keluhan_mesin)],
            ['Catatan admin', d.catatan_admin ? esc(d.catatan_admin) : '-'],
            ['Total biaya', `<strong>${rupiah(d.total_biaya)}</strong>`],
        ];
        $('#detailInfo').innerHTML = baris
            .map(([k, v]) => `<dt class="col-sm-4 wo-muted fw-normal">${k}</dt><dd class="col-sm-8">${v}</dd>`)
            .join('');

        // Form update status: hanya status berikutnya yang diizinkan server.
        const form = $('#formStatus');
        const pesan = $('#pesanTanpaAksi');
        const pilih = $('#statusBaru');
        form.classList.remove('was-validated');
        if (d.transisi && d.transisi.length) {
            pilih.innerHTML = '<option value="">-- Pilih status --</option>' +
                d.transisi.map((s) => `<option value="${esc(s)}">${esc(s)}</option>`).join('');
            form.classList.remove('d-none');
            pesan.classList.add('d-none');
        } else {
            form.classList.add('d-none');
            pesan.classList.remove('d-none');
            pesan.textContent = d.status_servis === 'Selesai'
                ? 'Work order ini sudah selesai.'
                : 'Tidak ada pembaruan status yang tersedia untuk Anda pada tahap ini.';
        }

        const base = $('#mekanik-app').dataset.sparepartUrl || 'index.php?page=sparepart';
        $('#linkKomponen').href = `${base}${base.includes('?') ? '&' : '?'}transaksi_id=${Number(d.id)}`;
    }

    async function simpanStatus(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const pilih = $('#statusBaru');
        form.classList.add('was-validated');
        if (!pilih.value) return;

        const btn = $('#btnSimpanStatus');
        const spin = $('.spinner-border', btn);
        btn.disabled = true;
        spin.classList.remove('d-none');
        try {
            const { message, data } = await api('update_status', {
                method: 'POST',
                body: { id: state.detailId, status_baru: pilih.value },
            });
            renderDetail(data);
            tampilkanAlert('success', message);
            await muatAntrean();
        } catch (err) {
            tampilkanAlert('danger', err.message);
            if (err.status === 409) {           // status sudah berubah di sisi lain
                getModal().hide();
                await muatAntrean();
            }
        } finally {
            btn.disabled = false;
            spin.classList.add('d-none');
        }
    }

    // ------------------------------------------------------------------
    // Inisialisasi
    // ------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        if (!$('#mekanik-app')) return;

        $('#btnMuatUlang').addEventListener('click', muatAntrean);
        $('#filterStatus').addEventListener('change', muatAntrean);
        $('#cariWO').addEventListener('input', renderTabel);
        $('#tbodyWO').addEventListener('click', (e) => {
            const tombol = e.target.closest('[data-aksi="detail"]');
            if (tombol) bukaDetail(Number(tombol.dataset.id));
        });
        $('#formStatus').addEventListener('submit', simpanStatus);

        muatAntrean();
    });
})();
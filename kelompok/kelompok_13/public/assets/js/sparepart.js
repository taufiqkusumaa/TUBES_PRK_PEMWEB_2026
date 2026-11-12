/**
 * sparepart.js - Dokumentasi komponen, upload foto, dan catatan teknis suku cadang.
 *
 * Endpoint:
 *   api.php?endpoint=mekanik&action=antrean            (daftar work order untuk dropdown)
 *   api.php?endpoint=sparepart&action=list|tambah|ubah|hapus
 * Format respons: { success, message, data }
 */
(() => {
    'use strict';

    // Ubah di sini bila lokasi api.php berbeda dari halaman ini.
    const API = 'api.php?endpoint=';

    // Kelas status-badge dari style.css (Ezzra): pending = oranye, process = cyan, success = hijau.
    const KELAS_STATUS = {
        'Pending': 'status-pending', 'Antre': 'status-pending', 'Dikerjakan': 'status-process',
        'Siap Diambil': 'status-success', 'Selesai': 'status-success',
    };
    const TIPE_FOTO = ['image/jpeg', 'image/png', 'image/webp'];
    const MAKS_FOTO = 2 * 1024 * 1024;

    const $ = (sel, root = document) => root.querySelector(sel);
    const state = { transaksiId: null, items: [], bisaUbah: false, previewUrl: null, hapusId: null };
    const modal = {};

    // ------------------------------------------------------------------
    // Utilitas
    // ------------------------------------------------------------------
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));
    const rupiah = (n) => new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(Number(n) || 0);
    const pathAman = (p) => (typeof p === 'string' && p.startsWith('uploads/') && !p.includes('..')) ? p : null;

    // Sub-aksi dikirim lewat parameter "action" (sama dengan api.php?endpoint=auth&action=logout).
    async function api(endpoint, aksi, { method = 'GET', params = {}, body = null } = {}) {
        const qs = new URLSearchParams({ action: aksi, ...params });
        const opt = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
        if (body !== null) opt.body = body; // FormData: browser mengatur Content-Type + boundary
        let res;
        try {
            res = await fetch(`${API}${endpoint}&${qs}`, opt);
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

    function setBusy(btn, busy) {
        btn.disabled = busy;
        const spin = $('.spinner-border', btn);
        if (spin) spin.classList.toggle('d-none', !busy);
    }

    // ------------------------------------------------------------------
    // Dropdown work order
    // ------------------------------------------------------------------
    async function muatDaftarWO() {
        const pilih = $('#pilihWO');
        try {
            const { data } = await api('mekanik', 'antrean', {
                params: { status: 'Dikerjakan,Siap Diambil,Selesai' },
            });
            if (!data.antrean.length) {
                pilih.innerHTML = '<option value="">Belum ada work order yang dapat didokumentasikan</option>';
                return;
            }
            pilih.innerHTML = '<option value="">-- Pilih work order --</option>' + data.antrean.map((r) =>
                `<option value="${Number(r.id)}">${esc(r.kode_transaksi)} - ${esc(r.no_polisi)} (${esc(r.status_servis)})</option>`
            ).join('');

            const awal = Number(new URLSearchParams(location.search).get('transaksi_id'));
            if (awal && data.antrean.some((r) => Number(r.id) === awal)) {
                pilih.value = String(awal);
                await muatKomponen(awal);
            }
        } catch (e) {
            pilih.innerHTML = '<option value="">Gagal memuat work order</option>';
            tampilkanAlert('danger', e.message);
        }
    }

    // ------------------------------------------------------------------
    // Daftar komponen
    // ------------------------------------------------------------------
    async function muatKomponen(transaksiId) {
        state.transaksiId = transaksiId;
        try {
            const { data } = await api('sparepart', 'list', { params: { transaksi_id: transaksiId } });
            state.items = data.komponen;
            state.bisaUbah = !!data.transaksi.bisa_ubah;
            renderPanel(data);
        } catch (e) {
            $('#panelWO').classList.add('d-none');
            tampilkanAlert('danger', e.message);
        }
    }

    function renderPanel({ transaksi: t, komponen, ringkasan }) {
        $('#panelWO').classList.remove('d-none');
        $('#woKode').textContent = t.kode_transaksi;
        $('#woStatus').innerHTML =
            `<span class="status-badge ${KELAS_STATUS[t.status_servis] || 'status-pending'}"><span class="dot"></span>${esc(t.status_servis)}</span>`;

        $('#woInfo').innerHTML = [
            ['Kendaraan', `${esc(t.no_polisi)} &middot; ${esc(t.nama_model)}`],
            ['Paket', esc(t.nama_paket)],
            ['Teknisi', t.nama_teknisi ? esc(t.nama_teknisi) : '-'],
            ['Keluhan', esc(t.keluhan_mesin)],
        ].map(([k, v]) => `<dt class="col-sm-3 wo-muted fw-normal">${k}</dt><dd class="col-sm-9">${v}</dd>`).join('');

        $('#sumTarif').textContent = rupiah(t.tarif_dasar);
        $('#sumKomponen').textContent = rupiah(ringkasan.total_komponen);
        $('#sumTotal').textContent = rupiah(t.total_biaya);
        $('#jumlahItem').textContent = ringkasan.jumlah_item;

        $('#btnTambah').classList.toggle('d-none', !state.bisaUbah);
        $('#kolomAksi').classList.toggle('d-none', !state.bisaUbah);
        const info = $('#infoTerkunci');
        if (state.bisaUbah) {
            info.classList.add('d-none');
        } else {
            info.classList.remove('d-none');
            info.textContent = t.status_servis === 'Dikerjakan'
                ? 'Work order ini bukan tugas Anda, jadi komponen hanya dapat dilihat.'
                : `Work order berstatus "${t.status_servis}", jadi komponen hanya dapat dilihat.`;
        }

        renderTabel(komponen);
    }

    function renderTabel(items) {
        const tbody = $('#tbodyKomponen');
        const kolom = state.bisaUbah ? 7 : 6;
        if (!items.length) {
            tbody.innerHTML = `<tr><td colspan="${kolom}" class="text-center wo-muted py-4">Belum ada komponen yang dicatat.</td></tr>`;
            return;
        }
        tbody.innerHTML = items.map((k) => {
            const foto = pathAman(k.foto_komponen);
            return `
            <tr>
                <td>${foto
                    ? `<img src="${esc(foto)}" class="part-thumb" alt="Foto ${esc(k.nama_suku_cadang)}" data-aksi="foto" data-src="${esc(foto)}">`
                    : '<span class="wo-muted small">-</span>'}</td>
                <td class="fw-semibold">${esc(k.nama_suku_cadang)}</td>
                <td class="text-end font-mono">${Number(k.jumlah)}</td>
                <td class="text-end font-mono">${rupiah(k.harga_satuan)}</td>
                <td class="text-end font-mono fw-semibold">${rupiah(k.subtotal)}</td>
                <td class="part-catatan small">${k.catatan_perbaikan ? esc(k.catatan_perbaikan) : '<span class="wo-muted">-</span>'}</td>
                ${state.bisaUbah ? `<td class="text-end text-nowrap">
                    <button type="button" class="btn-surface py-1 px-2 me-1" data-aksi="ubah" data-id="${Number(k.id)}">Ubah</button>
                    <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" data-aksi="hapus" data-id="${Number(k.id)}">Hapus</button>
                </td>` : ''}
            </tr>`;
        }).join('');

        // Foto yang tidak ada di server (mis. data seeder) diganti penanda teks.
        tbody.querySelectorAll('img.part-thumb').forEach((img) => {
            img.addEventListener('error', () => {
                const s = document.createElement('span');
                s.className = 'wo-muted small';
                s.textContent = 'File hilang';
                img.replaceWith(s);
            }, { once: true });
        });
    }

    // ------------------------------------------------------------------
    // Form tambah / ubah
    // ------------------------------------------------------------------
    function resetPreview() {
        if (state.previewUrl) {
            URL.revokeObjectURL(state.previewUrl);
            state.previewUrl = null;
        }
    }

    function tampilPreview(src) {
        const img = $('#previewFoto');
        const kosong = $('#previewKosong');
        if (src) {
            img.src = src;
            img.classList.remove('d-none');
            kosong.classList.add('d-none');
        } else {
            img.removeAttribute('src');
            img.classList.add('d-none');
            kosong.classList.remove('d-none');
        }
    }

    function bukaFormTambah() {
        const form = $('#formKomponen');
        form.reset();
        form.classList.remove('was-validated');
        $('#fFoto').setCustomValidity('');
        resetPreview();
        tampilPreview(null);
        $('#fId').value = '';
        $('#fTransaksi').value = state.transaksiId;
        $('#fJumlah').value = 1;
        $('#fFotoHint').classList.add('d-none');
        $('#judulKomponen').textContent = 'Tambah Komponen';
        modal.komponen.show();
    }

    function bukaFormUbah(id) {
        const k = state.items.find((x) => Number(x.id) === id);
        if (!k) return;
        const form = $('#formKomponen');
        form.reset();
        form.classList.remove('was-validated');
        $('#fFoto').setCustomValidity('');
        resetPreview();
        $('#fId').value = k.id;
        $('#fTransaksi').value = state.transaksiId;
        $('#fNama').value = k.nama_suku_cadang;
        $('#fJumlah').value = k.jumlah;
        $('#fHarga').value = Number(k.harga_satuan);
        $('#fCatatan').value = k.catatan_perbaikan || '';
        tampilPreview(pathAman(k.foto_komponen));
        $('#fFotoHint').classList.remove('d-none');
        $('#judulKomponen').textContent = 'Ubah Komponen';
        modal.komponen.show();
    }

    function validasiFoto() {
        const input = $('#fFoto');
        const file = input.files[0];
        resetPreview();
        input.setCustomValidity('');
        if (!file) return;

        let galat = '';
        if (!TIPE_FOTO.includes(file.type)) galat = 'Format foto harus JPG, PNG, atau WebP.';
        else if (file.size > MAKS_FOTO) galat = 'Ukuran foto melebihi 2 MB.';

        if (galat) {
            input.setCustomValidity(galat);
            $('#fFotoError').textContent = galat;
            input.value = '';
            tampilPreview(null);
            input.classList.add('is-invalid');
            return;
        }
        input.classList.remove('is-invalid');
        state.previewUrl = URL.createObjectURL(file);
        tampilPreview(state.previewUrl);
    }

    async function simpanKomponen(e) {
        e.preventDefault();
        const form = e.currentTarget;
        form.classList.add('was-validated');
        if (!form.checkValidity()) return;

        const edit = $('#fId').value !== '';
        const btn = $('#btnSimpanKomponen');
        setBusy(btn, true);
        try {
            const { message } = await api('sparepart', edit ? 'ubah' : 'tambah', {
                method: 'POST',
                body: new FormData(form),
            });
            modal.komponen.hide();
            tampilkanAlert('success', message);
            await muatKomponen(state.transaksiId);
        } catch (err) {
            tampilkanAlert('danger', err.message);
        } finally {
            setBusy(btn, false);
        }
    }

    // ------------------------------------------------------------------
    // Hapus
    // ------------------------------------------------------------------
    function bukaKonfirmasiHapus(id) {
        const k = state.items.find((x) => Number(x.id) === id);
        if (!k) return;
        state.hapusId = id;
        $('#hapusNama').textContent = k.nama_suku_cadang;
        modal.hapus.show();
    }

    async function hapusKomponen() {
        const btn = $('#btnKonfirmasiHapus');
        btn.disabled = true;
        try {
            const { message } = await api('sparepart', 'hapus', {
                method: 'DELETE',
                params: { id: state.hapusId, transaksi_id: state.transaksiId },
            });
            modal.hapus.hide();
            tampilkanAlert('success', message);
            await muatKomponen(state.transaksiId);
        } catch (err) {
            modal.hapus.hide();
            tampilkanAlert('danger', err.message);
        } finally {
            btn.disabled = false;
        }
    }

    // ------------------------------------------------------------------
    // Inisialisasi
    // ------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        if (!$('#sparepart-app')) return;

        modal.komponen = new bootstrap.Modal($('#modalKomponen'));
        modal.hapus = new bootstrap.Modal($('#modalHapus'));
        modal.foto = new bootstrap.Modal($('#modalFoto'));

        $('#pilihWO').addEventListener('change', (e) => {
            const id = Number(e.target.value);
            if (id) {
                muatKomponen(id);
            } else {
                $('#panelWO').classList.add('d-none');
            }
        });
        $('#btnTambah').addEventListener('click', bukaFormTambah);
        $('#fFoto').addEventListener('change', validasiFoto);
        $('#formKomponen').addEventListener('submit', simpanKomponen);
        $('#modalKomponen').addEventListener('hidden.bs.modal', resetPreview);
        $('#btnKonfirmasiHapus').addEventListener('click', hapusKomponen);

        $('#tbodyKomponen').addEventListener('click', (e) => {
            const el = e.target.closest('[data-aksi]');
            if (!el) return;
            const aksi = el.dataset.aksi;
            if (aksi === 'ubah') bukaFormUbah(Number(el.dataset.id));
            else if (aksi === 'hapus') bukaKonfirmasiHapus(Number(el.dataset.id));
            else if (aksi === 'foto') {
                $('#fotoBesar').src = el.dataset.src;
                modal.foto.show();
            }
        });

        muatDaftarWO();
    });
})();
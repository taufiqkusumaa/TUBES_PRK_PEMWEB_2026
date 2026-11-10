(() => {
    'use strict';

    const API = 'api.php?endpoint=layanan';
    const $ = (selector, root = document) => root.querySelector(selector);
    const state = { services: [] };

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[char]));
    const rupiah = (value) => new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(Number(value) || 0);
    const formatDurasi = (value) => {
        const minutes = Number(value) || 0;
        return minutes >= 60
            ? `${Math.floor(minutes / 60)} jam${minutes % 60 ? ` ${minutes % 60} menit` : ''}`
            : `${minutes} menit`;
    };

    async function api(action, { method = 'GET', body = null, id = null } = {}) {
        const query = new URLSearchParams({ action });
        if (id !== null) query.set('id', String(id));
        const options = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        let response;
        try {
            response = await fetch(`${API}&${query}`, options);
        } catch {
            throw new Error('Tidak dapat terhubung ke server.');
        }
        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error(`Respons server tidak valid (HTTP ${response.status}).`);
        }
        if (!response.ok || !result.success) {
            throw new Error(result.message || `Permintaan gagal (HTTP ${response.status}).`);
        }
        return result;
    }

    function showAlert(type, message) {
        const target = $('#layananAlert');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.setAttribute('role', 'alert');
        alert.append(document.createTextNode(message));
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close';
        close.setAttribute('data-bs-dismiss', 'alert');
        close.setAttribute('aria-label', 'Tutup');
        alert.append(close);
        target.replaceChildren(alert);
    }

    function render() {
        const tbody = $('#tbodyLayanan');
        tbody.innerHTML = state.services.length ? state.services.map((row) => `
            <tr>
                <td class="fw-semibold">${esc(row.nama_paket)}</td>
                <td>${rupiah(row.tarif_dasar)}</td>
                <td>${formatDurasi(row.estimasi_durasi_menit)}</td>
                <td class="text-end text-nowrap">
                    <button class="btn-surface py-1 px-2" type="button" data-edit="${Number(row.id)}">Ubah</button>
                    <button class="btn-surface py-1 px-2 text-danger" type="button" data-delete="${Number(row.id)}">Hapus</button>
                </td>
            </tr>`).join('') :
            '<tr><td colspan="4" class="text-center py-4">Belum ada paket layanan.</td></tr>';
    }

    async function load() {
        $('#tbodyLayanan').innerHTML = '<tr><td colspan="4" class="text-center py-4">Memuat paket layanan...</td></tr>';
        try {
            const { data } = await api('list');
            state.services = data.layanan || [];
            render();
        } catch (error) {
            $('#tbodyLayanan').innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${esc(error.message)}</td></tr>`;
        }
    }

    function resetForm() {
        $('#formLayanan').reset();
        $('#formLayanan').elements.id.value = '';
        $('#judulFormLayanan').textContent = 'Tambah Paket Layanan';
        $('#btnSimpanLayanan').textContent = 'Simpan';
        $('#btnBatalLayanan').classList.add('d-none');
    }

    async function submit(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const body = Object.fromEntries(new FormData(form).entries());
        body.tarif_dasar = Number(body.tarif_dasar);
        body.estimasi_durasi_menit = Number(body.estimasi_durasi_menit);
        if (body.id) body.id = Number(body.id);
        try {
            const { message } = await api('save', {
                method: body.id ? 'PUT' : 'POST',
                body,
            });
            resetForm();
            showAlert('success', message);
            await load();
        } catch (error) {
            showAlert('danger', error.message);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (!$('#layanan-app')) return;
        $('#formLayanan').addEventListener('submit', submit);
        $('#btnMuatLayanan').addEventListener('click', load);
        $('#btnBatalLayanan').addEventListener('click', resetForm);
        $('#tbodyLayanan').addEventListener('click', async (event) => {
            const edit = event.target.closest('[data-edit]');
            const remove = event.target.closest('[data-delete]');
            if (edit) {
                const row = state.services.find((item) => Number(item.id) === Number(edit.dataset.edit));
                if (!row) return;
                const form = $('#formLayanan');
                form.elements.id.value = row.id;
                form.elements.nama_paket.value = row.nama_paket;
                form.elements.tarif_dasar.value = row.tarif_dasar;
                form.elements.estimasi_durasi_menit.value = row.estimasi_durasi_menit;
                $('#judulFormLayanan').textContent = 'Ubah Paket Layanan';
                $('#btnSimpanLayanan').textContent = 'Perbarui';
                $('#btnBatalLayanan').classList.remove('d-none');
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (remove && window.confirm('Hapus paket layanan ini? Paket yang sudah digunakan pada transaksi tidak dapat dihapus.')) {
                try {
                    const { message } = await api('delete', { method: 'DELETE', id: remove.dataset.delete });
                    showAlert('success', message);
                    await load();
                } catch (error) {
                    showAlert('danger', error.message);
                }
            }
        });
        load();
    });
})();
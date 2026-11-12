(() => {
    'use strict';

    const API = 'api.php?endpoint=kendaraan';
    const $ = (selector, root = document) => root.querySelector(selector);
    const state = { categories: [], vehicles: [], customers: [] };

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[char]));
    const rupiah = (value) => new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(Number(value) || 0);

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
        const target = $('#kendaraanAlert');
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

    function renderCategories() {
        const tbody = $('#tbodyKategori');
        tbody.innerHTML = state.categories.length ? state.categories.map((row) => `
            <tr>
                <td>${esc(row.jenis)}</td><td>${esc(row.merk)}</td><td>${esc(row.tipe)}</td>
                <td>${Number(row.jumlah_kendaraan) || 0}</td>
                <td class="text-end text-nowrap">
                    <button class="btn-surface py-1 px-2" type="button" data-edit-category="${Number(row.id)}">Ubah</button>
                    <button class="btn-surface py-1 px-2 text-danger" type="button" data-delete-category="${Number(row.id)}">Hapus</button>
                </td>
            </tr>`).join('') :
            '<tr><td colspan="5" class="text-center py-4">Belum ada kategori kendaraan.</td></tr>';

        const select = $('#kendaraanKategori');
        const current = select.value;
        select.innerHTML = '<option value="">Pilih kategori</option>' + state.categories.map((row) =>
            `<option value="${Number(row.id)}">${esc(row.jenis)} — ${esc(row.merk)} ${esc(row.tipe)}</option>`
        ).join('');
        if (state.categories.some((row) => String(row.id) === current)) select.value = current;

        const ownerSelect = $('#kendaraanPemilik');
        const currentOwner = ownerSelect.value;
        ownerSelect.innerHTML = '<option value="">Pilih pelanggan</option>' + state.customers.map((row) =>
            `<option value="${Number(row.id)}">${esc(row.nama)} — ${esc(row.email)}</option>`
        ).join('');
        if (state.customers.some((row) => String(row.id) === currentOwner)) ownerSelect.value = currentOwner;
    }

    function renderVehicles() {
        const tbody = $('#tbodyKendaraan');
        tbody.innerHTML = state.vehicles.length ? state.vehicles.map((row) => `
            <tr>
                <td class="fw-semibold">${esc(row.no_polisi)}</td>
                <td>${esc(row.jenis)}</td>
                <td>${esc(row.merk)} / ${esc(row.tipe)}</td>
                <td>${esc(row.nama_model)}${row.tahun_pembuatan ? ` (${esc(row.tahun_pembuatan)})` : ''}${row.warna ? ` / ${esc(row.warna)}` : ''}</td>
                <td>${esc(row.nama_pemilik || '-')}</td>
                <td class="text-end text-nowrap">
                    <button class="btn-surface py-1 px-2" type="button" data-edit-vehicle="${Number(row.id)}">Ubah</button>
                    <button class="btn-surface py-1 px-2 text-danger" type="button" data-delete-vehicle="${Number(row.id)}">Hapus</button>
                </td>
            </tr>`).join('') :
            '<tr><td colspan="6" class="text-center py-4">Belum ada data kendaraan.</td></tr>';
    }

    async function loadData() {
        $('#tbodyKategori').innerHTML = '<tr><td colspan="5" class="text-center py-4">Memuat kategori...</td></tr>';
        $('#tbodyKendaraan').innerHTML = '<tr><td colspan="5" class="text-center py-4">Memuat kendaraan...</td></tr>';
        try {
            const { data } = await api('list_vehicles');
            state.categories = data.kategori || [];
            state.vehicles = data.kendaraan || [];
            state.customers = data.pelanggan || [];
            renderCategories();
            renderVehicles();
        } catch (error) {
            $('#tbodyKategori').innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${esc(error.message)}</td></tr>`;
            $('#tbodyKendaraan').innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${esc(error.message)}</td></tr>`;
        }
    }

    function resetCategory() {
        $('#formKategori').reset();
        $('#formKategori').elements.id.value = '';
        $('#btnSimpanKategori').textContent = 'Simpan Kategori';
        $('#btnBatalKategori').classList.add('d-none');
    }

    function resetVehicle() {
        $('#formKendaraan').reset();
        $('#formKendaraan').elements.id.value = '';
        $('#btnBatalKendaraan').classList.add('d-none');
    }

    function formData(form) {
        return Object.fromEntries(new FormData(form).entries());
    }

    async function submitCategory(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const body = formData(form);
        if (body.id) body.id = Number(body.id);
        try {
            const { message } = await api('save_category', {
                method: body.id ? 'PUT' : 'POST',
                body,
            });
            resetCategory();
            showAlert('success', message);
            await loadData();
        } catch (error) {
            showAlert('danger', error.message);
        }
    }

    async function submitVehicle(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const body = formData(form);
        body.kategori_id = Number(body.kategori_id);
        body.user_id = Number(body.user_id);
        if (body.id) body.id = Number(body.id);
        body.no_polisi = body.no_polisi.toUpperCase().trim();
        try {
            const { message } = await api('save_vehicle', {
                method: body.id ? 'PUT' : 'POST',
                body,
            });
            resetVehicle();
            showAlert('success', message);
            await loadData();
        } catch (error) {
            showAlert('danger', error.message);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (!$('#kendaraan-app')) return;
        $('#formKategori').addEventListener('submit', submitCategory);
        $('#formKendaraan').addEventListener('submit', submitVehicle);
        $('#btnResetKategori').addEventListener('click', resetCategory);
        $('#btnBatalKategori').addEventListener('click', resetCategory);
        $('#btnResetKendaraan').addEventListener('click', resetVehicle);
        $('#btnBatalKendaraan').addEventListener('click', resetVehicle);
        $('#btnMuatKendaraan').addEventListener('click', loadData);

        $('#tbodyKategori').addEventListener('click', async (event) => {
            const edit = event.target.closest('[data-edit-category]');
            const remove = event.target.closest('[data-delete-category]');
            if (edit) {
                const row = state.categories.find((item) => Number(item.id) === Number(edit.dataset.editCategory));
                if (!row) return;
                const form = $('#formKategori');
                form.elements.id.value = row.id;
                form.elements.jenis.value = row.jenis;
                form.elements.merk.value = row.merk;
                form.elements.tipe.value = row.tipe;
                $('#btnSimpanKategori').textContent = 'Perbarui Kategori';
                $('#btnBatalKategori').classList.remove('d-none');
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (remove && window.confirm('Hapus kategori kendaraan ini?')) {
                try {
                    const { message } = await api('delete_category', { method: 'DELETE', id: remove.dataset.deleteCategory });
                    showAlert('success', message);
                    await loadData();
                } catch (error) {
                    showAlert('danger', error.message);
                }
            }
        });

        $('#tbodyKendaraan').addEventListener('click', async (event) => {
            const edit = event.target.closest('[data-edit-vehicle]');
            const remove = event.target.closest('[data-delete-vehicle]');
            if (edit) {
                const row = state.vehicles.find((item) => Number(item.id) === Number(edit.dataset.editVehicle));
                if (!row) return;
                const form = $('#formKendaraan');
                form.elements.id.value = row.id;
                form.elements.kategori_id.value = row.kategori_id;
                form.elements.user_id.value = row.user_id;
                form.elements.no_polisi.value = row.no_polisi;
                form.elements.nama_model.value = row.nama_model;
                form.elements.tahun_pembuatan.value = row.tahun_pembuatan || '';
                form.elements.warna.value = row.warna || '';
                $('#btnBatalKendaraan').classList.remove('d-none');
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (remove && window.confirm('Hapus data kendaraan ini? Riwayat servis yang terhubung akan mencegah penghapusan.')) {
                try {
                    const { message } = await api('delete_vehicle', { method: 'DELETE', id: remove.dataset.deleteVehicle });
                    showAlert('success', message);
                    await loadData();
                } catch (error) {
                    showAlert('danger', error.message);
                }
            }
        });

        loadData();
    });
})();
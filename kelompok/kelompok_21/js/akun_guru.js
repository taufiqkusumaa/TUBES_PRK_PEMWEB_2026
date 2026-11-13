// Kelola akun guru (khusus admin) pada halaman "Profil saya".
// Mandiri: tidak bergantung pada fungsi di js/app.js.
(function () {
    const info = document.body.dataset;
    if (info.page !== 'profil' || info.role !== 'admin' || !document.getElementById('akun-guru')) return;

    const $ = id => document.getElementById(id);
    const ENDPOINT = 'api/akun.php';
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let rows = [];
    let editingId = null;

    async function api(url, method = 'GET', data = null) {
        const options = {method, headers: {'X-CSRF-Token': csrf}};
        if (data !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
        const response = await fetch(url, options);
        if (response.status === 401) { location.href = 'login.php'; throw new Error('Sesi berakhir.'); }
        let result;
        try { result = await response.json(); }
        catch { throw new Error('Respons server tidak valid. Periksa instalasi PHP dan database.'); }
        if (!response.ok) throw new Error(result.message || 'Permintaan gagal.');
        return result.data;
    }
    function notice(message, error = false) {
        const box = $('notice');
        if (!box) return;
        box.hidden = false;
        box.className = 'message ' + (error ? 'error' : 'success');
        box.textContent = message;
    }
    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    async function load() {
        rows = await api(ENDPOINT);
        render();
    }

    function render() {
        const body = $('akun-body');
        body.replaceChildren();
        if (!rows.length) {
            const cell = el('td', 'empty', 'Belum ada akun.'); cell.colSpan = 6;
            const tr = document.createElement('tr'); tr.append(cell); body.append(tr);
            return;
        }
        rows.forEach((row, index) => {
            const tr = document.createElement('tr');
            tr.append(el('td', '', index + 1));
            const nameCell = el('td', '', row.nama_guru);
            if (row.saya) nameCell.append(' ', el('small', '', '(Anda)'));
            tr.append(nameCell, el('td', '', row.nip ?? '—'), el('td', '', row.email ?? '—'));
            const peranCell = el('td');
            peranCell.append(el('span', 'pill peran-' + row.peran, row.peran === 'admin' ? 'Admin' : 'Guru'));
            tr.append(peranCell);

            const actions = el('td', 'actions');
            const edit = el('button', '', 'Edit'); edit.type = 'button';
            edit.onclick = () => openForm(row);
            actions.append(edit);
            if (!row.saya) {
                const remove = el('button', 'danger', 'Hapus'); remove.type = 'button';
                remove.onclick = () => removeAkun(row, remove);
                actions.append(remove);
            }
            tr.append(actions);
            body.append(tr);
        });
    }

    async function removeAkun(row, button) {
        if (row.terkait > 0) {
            notice('Akun ' + row.nama_guru + ' masih dipakai pada ' + row.terkait + ' data (wali kelas, penugasan, atau catatan), jadi tidak dapat dihapus.', true);
            return;
        }
        if (!confirm('Hapus akun ' + row.nama_guru + '?')) return;
        button.disabled = true;
        try {
            await api(ENDPOINT + '?id=' + row.id_guru, 'DELETE');
            await load();
            notice('Akun guru berhasil dihapus.');
        } catch (error) { notice(error.message, true); button.disabled = false; }
    }

    function openForm(row = null) {
        const form = $('akun-form');
        editingId = row ? row.id_guru : null;
        form.reset();
        $('akun-error').textContent = '';
        $('akun-dialog-title').textContent = row ? 'Edit akun' : 'Tambah akun';
        $('akun-password-label').textContent = row ? 'Password baru (kosongkan jika tidak diganti)' : 'Password';
        form.elements.password.required = !row;
        form.elements.nama_guru.value = row ? row.nama_guru : '';
        form.elements.nip.value = row ? (row.nip ?? '') : '';
        form.elements.email.value = row ? (row.email ?? '') : '';
        form.elements.peran.value = row ? row.peran : 'guru';
        form.elements.peran.disabled = !!(row && row.saya); // peran akun sendiri dikunci
        $('akun-dialog').showModal();
    }

    $('akun-add').onclick = () => openForm();
    ['akun-close', 'akun-cancel'].forEach(id => $(id).onclick = () => $('akun-dialog').close());
    $('akun-form').onsubmit = async event => {
        event.preventDefault();
        const form = event.target;
        const button = form.querySelector('[type="submit"]'); button.disabled = true;
        try {
            const payload = {
                nama_guru: form.elements.nama_guru.value, nip: form.elements.nip.value,
                email: form.elements.email.value, peran: form.elements.peran.value,
                password: form.elements.password.value
            };
            await api(ENDPOINT + (editingId ? '?id=' + editingId : ''), editingId ? 'PUT' : 'POST', payload);
            $('akun-dialog').close();
            await load();
            notice(editingId ? 'Akun guru berhasil diperbarui.' : 'Akun guru berhasil ditambahkan.');
        } catch (error) { $('akun-error').textContent = error.message; }
        finally { button.disabled = false; }
    };

    load().catch(error => notice(error.message, true));
})();

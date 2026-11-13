// Halaman Catatan Kualitatif & Bimbingan (Nova).
// Mandiri: halaman ini tidak memuat js/app.js, jadi helper api() dan notice() didefinisikan di sini.
(function () {
    if (document.body.dataset.page !== 'kualitatif') return;

    const csrf = document.querySelector('meta[name="csrf-token"]').content;
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
        const box = document.getElementById('notice');
        if (!box) return;
        box.hidden = false;
        box.className = 'message ' + (error ? 'error' : 'success');
        box.textContent = message;
    }

    const $ = id => document.getElementById(id);
    const ENDPOINT = 'api/profil.php';
    const STATUS_CLASS = {'Belum ditindaklanjuti': 'belum', 'Sedang berjalan': 'berjalan', 'Selesai': 'selesai'};
    let siswaRows = [];
    let current = null;      // siswa yang sedang dibuka
    let editingId = null;    // id catatan yang sedang diedit (null = tambah baru)

    function tanggal(value) {
        if (!value) return '—';
        const [date, time = ''] = value.split(' ');
        const [y, m, d] = date.split('-');
        return (d + '/' + m + '/' + y + ' ' + time.slice(0, 5)).trim();
    }
    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }
    function emptyRow(body, colSpan, text) {
        const cell = el('td', 'empty', text); cell.colSpan = colSpan;
        const row = document.createElement('tr'); row.append(cell);
        body.replaceChildren(row);
    }

    async function loadKelas() {
        const kelas = await api('api/data.php?modul=kelas');
        const select = $('catatan-kelas');
        select.replaceChildren(new Option(kelas.length ? 'Pilih kelas' : 'Belum ada kelas dalam akses Anda', ''));
        kelas.sort((a, b) => a.nama_kelas.localeCompare(b.nama_kelas))
            .forEach(row => select.add(new Option(row.nama_kelas, row.id_kelas)));
        select.onchange = loadSiswa;
        if (kelas.length === 1) { select.value = kelas[0].id_kelas; await loadSiswa(); }
    }

    async function loadSiswa() {
        const idKelas = $('catatan-kelas').value;
        $('catatan-detail').hidden = true; current = null;
        const body = $('catatan-siswa-body');
        if (!idKelas) { siswaRows = []; $('catatan-count').textContent = '—'; emptyRow(body, 7, 'Pilih kelas terlebih dahulu.'); return; }
        siswaRows = await api(ENDPOINT + '?id_kelas=' + idKelas);
        $('catatan-count').textContent = siswaRows.length + ' siswa';
        renderSiswa();
    }

    function renderSiswa() {
        const body = $('catatan-siswa-body');
        if (!siswaRows.length) return emptyRow(body, 7, 'Belum ada siswa di kelas ini.');
        body.replaceChildren();
        siswaRows.forEach((row, index) => {
            const tr = document.createElement('tr');
            [index + 1, row.nisn, row.nama_siswa, row.total, row.belum_selesai, tanggal(row.terakhir)]
                .forEach(value => tr.append(el('td', '', value)));
            const cell = el('td', 'actions');
            const open = el('button', '', 'Buka catatan'); open.type = 'button';
            open.onclick = () => openSiswa(row);
            cell.append(open); tr.append(cell); body.append(tr);
        });
    }

    async function openSiswa(row) {
        current = row;
        try {
            await loadDetail();
            $('catatan-detail').scrollIntoView({behavior: 'smooth', block: 'start'});
        } catch (error) { notice(error.message, true); }
    }

    async function loadDetail() {
        const data = await api(ENDPOINT + '?id_siswa=' + current.id_siswa);
        $('catatan-nama').textContent = data.siswa.nama_siswa + ' · NISN ' + data.siswa.nisn;
        renderRekomendasi(data.rekomendasi);
        renderCatatan(data.catatan);
        $('catatan-detail').hidden = false;
    }

    function renderRekomendasi(list) {
        const box = $('catatan-rekomendasi');
        box.replaceChildren();
        box.hidden = !list.length;
        if (!list.length) return;
        box.append(el('strong', '', 'Rekomendasi sistem'));
        list.forEach(item => {
            const detail = [item.kategori_kuadran, item.nilai_akhir !== null ? 'nilai ' + item.nilai_akhir : null,
                item.indeks_kedisiplinan !== null ? 'kedisiplinan ' + item.indeks_kedisiplinan : null].filter(Boolean).join(' · ');
            const p = el('p', '', item.nama_mapel + (detail ? ' (' + detail + ')' : '') + (item.rekomendasi_sistem ? ': ' + item.rekomendasi_sistem : ''));
            box.append(p);
        });
    }

    function field(title, text) {
        const wrap = el('div', 'catatan-field');
        wrap.append(el('small', '', title), el('p', '', text));
        return wrap;
    }

    function renderCatatan(list) {
        const box = $('catatan-list');
        box.replaceChildren();
        if (!list.length) { box.append(el('p', 'empty', 'Belum ada catatan bimbingan untuk siswa ini.')); return; }
        list.forEach(item => {
            const card = el('article', 'catatan-card');
            const head = el('header', 'catatan-card-head');
            const info = el('div');
            info.append(el('strong', '', tanggal(item.tanggal_interaksi)), el('small', '', item.nama_guru + ' · ' + item.periode));
            head.append(info, el('span', 'pill status-' + (STATUS_CLASS[item.status_tindak_lanjut] || 'belum'), item.status_tindak_lanjut));
            card.append(head, field('Catatan', item.catatan_kualitatif));
            if (item.rencana_tindakan) card.append(field('Rencana tindakan', item.rencana_tindakan));
            if (item.evaluasi_hasil) card.append(field('Evaluasi hasil', item.evaluasi_hasil));
            if (item.dapat_diubah) {
                const actions = el('div', 'actions');
                const edit = el('button', '', 'Edit'); edit.type = 'button'; edit.onclick = () => openForm(item);
                const remove = el('button', 'danger', 'Hapus'); remove.type = 'button';
                remove.onclick = async () => {
                    if (!confirm('Hapus catatan ini?')) return;
                    remove.disabled = true;
                    try { await api(ENDPOINT + '?id=' + item.id_catatan, 'DELETE'); await refresh(); notice('Catatan berhasil dihapus.'); }
                    catch (error) { notice(error.message, true); remove.disabled = false; }
                };
                actions.append(edit, remove); card.append(actions);
            }
            box.append(card);
        });
    }

    // Muat ulang detail siswa dan ringkasan kelas setelah ada perubahan.
    async function refresh() {
        const [summary] = await Promise.all([api(ENDPOINT + '?id_kelas=' + $('catatan-kelas').value), loadDetail()]);
        siswaRows = summary; renderSiswa();
        current = siswaRows.find(row => row.id_siswa === current.id_siswa) || current;
    }

    function nowLocal() {
        const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 16);
    }

    function openForm(item = null) {
        const form = $('catatan-form');
        editingId = item ? item.id_catatan : null;
        $('catatan-dialog-title').textContent = item ? 'Edit catatan' : 'Tambah catatan';
        $('catatan-error').textContent = '';
        form.reset();
        form.elements.tanggal_interaksi.value = item ? item.tanggal_interaksi.slice(0, 16).replace(' ', 'T') : nowLocal();
        form.elements.catatan_kualitatif.value = item ? item.catatan_kualitatif : '';
        form.elements.rencana_tindakan.value = item ? (item.rencana_tindakan ?? '') : '';
        form.elements.status_tindak_lanjut.value = item ? item.status_tindak_lanjut : 'Belum ditindaklanjuti';
        form.elements.evaluasi_hasil.value = item ? (item.evaluasi_hasil ?? '') : '';
        $('catatan-dialog').showModal();
    }

    function init() {
        $('catatan-add').onclick = () => openForm();
        ['catatan-close', 'catatan-cancel'].forEach(id => $(id).onclick = () => $('catatan-dialog').close());
        $('catatan-form').onsubmit = async event => {
            event.preventDefault();
            const button = event.target.querySelector('[type="submit"]'); button.disabled = true;
            try {
                const data = Object.fromEntries(new FormData(event.target));
                if (!editingId) data.id_siswa = current.id_siswa;
                await api(ENDPOINT + (editingId ? '?id=' + editingId : ''), editingId ? 'PUT' : 'POST', data);
                $('catatan-dialog').close();
                await refresh();
                notice('Catatan berhasil disimpan.');
            } catch (error) { $('catatan-error').textContent = error.message; }
            finally { button.disabled = false; }
        };
        loadKelas().catch(error => notice(error.message, true));
    }
    init();
})();

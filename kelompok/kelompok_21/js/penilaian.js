(function () {
    const $ = id => document.getElementById(id);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const idGuruAktif = document.body.dataset.guru || '0';
    const STORAGE_KEY = 'arunika_pn_konteks_' + idGuruAktif;
    const URL_API = 'api/penilaian.php';
    const JENIS = ['Tugas', 'Kuis', 'UTS', 'UAS'];

    let idPenugasan = '';
    let daftar = [];
    let editingId = null;
    let idNilaiAktif = null;

    function simpanSesiLokal(id_kelas, id_mapel, id_periode) {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                id_kelas: String(id_kelas || ''),
                id_mapel: String(id_mapel || ''),
                id_periode: String(id_periode || '')
            }));
        } catch { /* abaikan bila storage diblokir */ }
    }

    function bacaSesiLokal() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }

    async function req(url, method = 'GET', data = null) {
        const options = { method, headers: { 'X-CSRF-Token': csrfToken } };
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
        return result;
    }

    function info(message, error = false) {
        const box = $('notice');
        box.hidden = false;
        box.className = 'message ' + (error ? 'error' : 'success');
        box.textContent = message;
    }

    function cell(text, className) {
        const td = document.createElement('td');
        td.textContent = text;
        if (className) td.className = className;
        return td;
    }

    function emptyRow(body, span, text) {
        const tr = document.createElement('tr');
        const td = cell(text, 'empty');
        td.colSpan = span;
        tr.append(td);
        body.append(tr);
    }

    function fmt(value) {
        return value === null || value === undefined ? '—' : Number(value).toString();
    }

    function fmtTenggat(value, jenis) {
        if (jenis !== 'Tugas' || !value) return '—';
        const dt = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(dt.getTime())) return value;
        return dt.toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function toDatetimeLocal(value) {
        if (!value) return '';
        return String(value).replace(' ', 'T').slice(0, 16);
    }

    function toggleTenggat() {
        const isTugas = $('pn-jenis').value === 'Tugas';
        const wrap = $('pn-tenggat-wrap');
        const input = $('pn-tenggat');
        wrap.hidden = !isTugas;
        input.required = isTugas;
        if (!isTugas) input.value = '';
    }

    async function loadKonteks() {
        const result = await req(URL_API + '?aksi=konteks');
        const { kelas = [], mapel = [], periode = [], pilihan = null } = result.data || {};
        const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });

        const bobotTingkat = t => ({ X: 1, '10': 1, XI: 2, '11': 2, XII: 3, '12': 3 }[String(t).toUpperCase()] || 9);
        kelas.sort((a, b) => bobotTingkat(a.tingkat) - bobotTingkat(b.tingkat) || collator.compare(a.nama_kelas, b.nama_kelas));

        mapel.sort((a, b) => collator.compare(a.nama_mapel, b.nama_mapel));

        const bobotSem = s => (String(s).toLowerCase() === 'genap' ? 1 : 2);
        periode.sort((a, b) => collator.compare(b.tahun_ajaran, a.tahun_ajaran) || bobotSem(a.semester) - bobotSem(b.semester));

        const selKelas = $('pn-kelas');
        const selMapel = $('pn-mapel');
        const selPeriode = $('pn-periode');

        const root = $('pn-root');
        const lokal = bacaSesiLokal();

        const prevKelas = selKelas.value || lokal?.id_kelas || (pilihan?.id_kelas ? String(pilihan.id_kelas) : '') || root?.dataset.kelas || '';
        const prevMapel = selMapel.value || lokal?.id_mapel || (pilihan?.id_mapel ? String(pilihan.id_mapel) : '') || root?.dataset.mapel || '';
        const prevPeriode = selPeriode.value || lokal?.id_periode || (pilihan?.id_periode ? String(pilihan.id_periode) : '') || root?.dataset.periode || '';

        selKelas.replaceChildren(new Option(kelas.length ? 'Pilih kelas' : 'Belum ada kelas', ''));
        kelas.forEach(k => selKelas.add(new Option(k.nama_kelas, k.id_kelas)));

        selMapel.replaceChildren(new Option(mapel.length ? 'Pilih mata pelajaran' : 'Belum ada mapel', ''));
        mapel.forEach(m => selMapel.add(new Option(m.nama_mapel, m.id_mapel)));

        selPeriode.replaceChildren(new Option(periode.length ? 'Pilih periode' : 'Belum ada periode', ''));
        let defaultPeriode = '';
        periode.forEach(p => {
            const aktif = Number(p.status_aktif) === 1;
            if (aktif && !defaultPeriode) defaultPeriode = String(p.id_periode);
            selPeriode.add(new Option(p.tahun_ajaran + ' · ' + p.semester + (aktif ? ' (aktif)' : ''), p.id_periode));
        });

        if ([...selKelas.options].some(o => o.value === String(prevKelas))) selKelas.value = String(prevKelas);
        if ([...selMapel.options].some(o => o.value === String(prevMapel))) selMapel.value = String(prevMapel);

        const targetPeriode = prevPeriode || defaultPeriode || (periode[0] ? String(periode[0].id_periode) : '');
        if ([...selPeriode.options].some(o => o.value === String(targetPeriode))) selPeriode.value = String(targetPeriode);
    }

    async function pilihKonteks() {
        const id_kelas = $('pn-kelas').value;
        const id_mapel = $('pn-mapel').value;
        const id_periode = $('pn-periode').value;

        simpanSesiLokal(id_kelas, id_mapel, id_periode);

        if (!id_kelas || !id_mapel || !id_periode) {
            idPenugasan = '';
            await refresh();
            return;
        }
        try {
            const res = await req(URL_API + '?aksi=siapkan_konteks', 'POST', { id_kelas, id_mapel, id_periode });
            idPenugasan = String(res.data.id_penugasan);
            await refresh();
        } catch (error) {
            idPenugasan = '';
            await refresh();
            info(error.message, true);
        }
    }

    async function refresh() {
        const show = idPenugasan !== '';
        ['pn-daftar-panel', 'pn-rekap-panel'].forEach(id => $(id).hidden = !show);
        $('pn-add').hidden = !show;
        tutupNilai();
        if (!show) { $('pn-count').textContent = '—'; return; }
        const [d, r] = await Promise.all([
            req(URL_API + '?aksi=daftar&id_penugasan=' + idPenugasan),
            req(URL_API + '?aksi=rekap&id_penugasan=' + idPenugasan)
        ]);
        daftar = d.data;
        renderDaftar();
        renderRekap(r.data);
    }

    function renderDaftar() {
        const body = $('pn-body');
        body.replaceChildren();
        $('pn-count').textContent = daftar.length + ' penilaian';
        if (!daftar.length) { emptyRow(body, 7, 'Belum ada penilaian. Mulai dengan menambahkan penilaian baru.'); return; }
        daftar.forEach((item, i) => {
            const tr = document.createElement('tr');
            tr.append(
                cell(i + 1),
                cell(item.judul_penilaian),
                cell(item.jenis_komponen),
                cell(fmtTenggat(item.tenggat_waktu, item.jenis_komponen)),
                cell(item.terisi + ' / ' + item.jumlah_siswa),
                cell(fmt(item.rata_rata))
            );
            const aksi = document.createElement('td');
            aksi.className = 'actions';
            const nilai = document.createElement('button');
            nilai.type = 'button'; nilai.textContent = 'Input nilai';
            nilai.onclick = () => bukaNilai(item.id_penilaian);
            const edit = document.createElement('button');
            edit.type = 'button'; edit.textContent = 'Edit';
            edit.onclick = () => bukaForm(item);
            const hapus = document.createElement('button');
            hapus.type = 'button'; hapus.textContent = 'Hapus'; hapus.className = 'danger';
            hapus.onclick = async () => {
                if (!confirm('Hapus penilaian ini beserta seluruh nilai siswanya?')) return;
                hapus.disabled = true;
                try {
                    const res = await req(URL_API + '?id=' + item.id_penilaian, 'DELETE');
                    await refresh();
                    info(res.message || 'Penilaian berhasil dihapus.');
                } catch (error) { info(error.message, true); hapus.disabled = false; }
            };
            aksi.append(nilai, edit, hapus);
            tr.append(aksi);
            body.append(tr);
        });
    }

    function renderRekap(rows) {
        const body = $('pn-rekap-body');
        body.replaceChildren();
        if (!rows.length) { emptyRow(body, 7, 'Belum ada siswa pada kelas ini.'); return; }
        rows.forEach((s, i) => {
            const tr = document.createElement('tr');
            tr.append(cell(i + 1), cell(s.nisn), cell(s.nama_siswa), ...JENIS.map(j => cell(fmt(s[j]))));
            body.append(tr);
        });
    }

    function bukaForm(item = null) {
        editingId = item ? item.id_penilaian : null;
        $('pn-dialog-title').textContent = item ? 'Edit penilaian' : 'Tambah penilaian';
        $('pn-form-error').textContent = '';
        const form = $('pn-form');
        form.elements.judul_penilaian.value = item ? item.judul_penilaian : '';
        form.elements.jenis_komponen.value = item ? item.jenis_komponen : '';
        toggleTenggat();
        if (item && item.jenis_komponen === 'Tugas') {
            $('pn-tenggat').value = toDatetimeLocal(item.tenggat_waktu);
        }
        $('pn-dialog').showModal();
    }

    async function submitForm(event) {
        event.preventDefault();
        const button = event.target.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const data = Object.fromEntries(new FormData(event.target));
            data.judul_penilaian = data.judul_penilaian.trim();
            if (!data.judul_penilaian) throw new Error('Judul penilaian wajib diisi.');
            if (data.jenis_komponen === 'Tugas' && !data.tenggat_waktu) {
                throw new Error('Tenggat waktu wajib diisi untuk komponen Tugas.');
            }
            if (data.jenis_komponen !== 'Tugas') {
                delete data.tenggat_waktu;
            }
            if (!editingId) data.id_penugasan = idPenugasan;
            const res = await req(URL_API + (editingId ? '?id=' + editingId : ''), editingId ? 'PUT' : 'POST', data);
            $('pn-dialog').close();
            await refresh();
            info(res.message || 'Penilaian berhasil disimpan.');
        } catch (error) { $('pn-form-error').textContent = error.message; }
        finally { button.disabled = false; }
    }

    async function bukaNilai(id) {
        try {
            const res = await req(URL_API + '?aksi=nilai&id_penilaian=' + id);
            idNilaiAktif = id;
            const { penilaian, siswa } = res.data;
            $('pn-nilai-title').textContent = penilaian.judul_penilaian + ' (' + penilaian.jenis_komponen + ')';
            $('pn-nilai-error').textContent = '';
            const body = $('pn-nilai-body');
            body.replaceChildren();
            if (!siswa.length) emptyRow(body, 4, 'Belum ada siswa pada kelas ini.');
            siswa.forEach((s, i) => {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'number'; input.min = 0; input.max = 100; input.step = '0.01';
                input.style.maxWidth = '120px';
                input.style.marginTop = '0';
                input.name = 's_' + s.id_siswa;
                input.value = s.nilai === null ? '' : Number(s.nilai);
                input.setAttribute('aria-label', 'Nilai ' + s.nama_siswa);
                td.append(input);
                tr.append(cell(i + 1), cell(s.nisn), cell(s.nama_siswa), td);
                body.append(tr);
            });
            $('pn-nilai-panel').hidden = false;
            $('pn-nilai-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) { info(error.message, true); }
    }

    function tutupNilai() {
        idNilaiAktif = null;
        $('pn-nilai-panel').hidden = true;
        $('pn-nilai-body').replaceChildren();
    }

    async function submitNilai(event) {
        event.preventDefault();
        const button = event.target.querySelector('[type="submit"]');
        $('pn-nilai-error').textContent = '';
        const data = {};
        for (const input of event.target.querySelectorAll('input[name^="s_"]')) {
            const v = input.value.trim();
            if (v !== '' && (Number.isNaN(Number(v)) || Number(v) < 0 || Number(v) > 100)) {
                input.focus();
                $('pn-nilai-error').textContent = 'Nilai harus berupa angka antara 0 sampai 100.';
                return;
            }
            data[input.name] = v;
        }
        button.disabled = true;
        try {
            const res = await req(URL_API + '?aksi=simpan_nilai&id_penilaian=' + idNilaiAktif, 'POST', data);
            await refresh();
            info(res.message || 'Nilai berhasil disimpan.');
        } catch (error) { $('pn-nilai-error').textContent = error.message; }
        finally { button.disabled = false; }
    }

    async function init() {
        try {
            await loadKonteks();
            ['pn-kelas', 'pn-mapel', 'pn-periode'].forEach(id => {
                $(id).addEventListener('change', pilihKonteks);
            });
            window.addEventListener('focus', () => { loadKonteks().catch(() => { }); });
            $('pn-add').onclick = () => bukaForm();
            $('pn-jenis').addEventListener('change', toggleTenggat);
            ['pn-dialog-x', 'pn-dialog-batal'].forEach(id => $(id).onclick = () => $('pn-dialog').close());
            $('pn-form').onsubmit = submitForm;
            $('pn-nilai-form').onsubmit = submitNilai;
            $('pn-nilai-tutup').onclick = tutupNilai;

            if ($('pn-kelas').value && $('pn-mapel').value && $('pn-periode').value) {
                await pilihKonteks();
            }
        } catch (error) { info(error.message, true); }
    }
    init();
})();
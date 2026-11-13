document.addEventListener('DOMContentLoaded', function () {

    const root = document.querySelector('.transaction-content[data-jenis]');
    if (!root) return;

    const jenis = root.dataset.jenis;                 // 'pemasukan' atau 'pengeluaran'
    const label = jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
    const endpoint = '../backend/transaksi/' + jenis + '_process.php';
    const idKey = 'id_' + jenis;
    const kelasNominal = jenis === 'pemasukan' ? 'amount-income' : 'amount-expense';

    const form = document.getElementById('transaksiForm');
    const formTitle = document.getElementById('formTitle');
    const aksiInput = document.getElementById('formAksi');
    const idInput = document.getElementById('formId');
    const kategori = document.getElementById('kategori');
    const jumlah = document.getElementById('jumlah');
    const tanggal = document.getElementById('tanggal');
    const keterangan = document.getElementById('keterangan');
    const btnSubmit = document.getElementById('btnSubmit');
    const tabel = document.getElementById('transaksiTable');
    const formHapus = document.getElementById('formHapus');
    const hapusId = document.getElementById('hapusId');
    const totalBulanIni = document.getElementById('totalBulanIni');
    const jumlahTransaksi = document.getElementById('jumlahTransaksi');

    let data = [];

    // ---------- Helper ----------
    function rupiah(n) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(Number(n) || 0);
    }

    function hariIni() {
        const d = new Date();
        const bln = String(d.getMonth() + 1).padStart(2, '0');
        const tgl = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + bln + '-' + tgl;
    }

    function formatTanggal(str) {
        const [y, m, d] = String(str).split('-').map(Number);
        return new Date(y, m - 1, d).toLocaleDateString('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric'
        });
    }

    async function getJson(url) {
        const res = await fetch(url, { headers: { Accept: 'application/json' } });

        if (res.status === 401 || res.redirected) {
            window.location.href = '../login.php';
            throw new Error('Sesi berakhir.');
        }

        const json = await res.json();

        if (!res.ok || json.error) {
            throw new Error(json.error || 'Terjadi kesalahan.');
        }

        return json;
    }

    function td(text, className) {
        const cell = document.createElement('td');
        cell.textContent = text;
        if (className) cell.className = className;
        return cell;
    }

    // ---------- Kategori ----------
    async function loadKategori() {
        try {
            const list = await getJson('../backend/kategori.php?jenis=' + jenis);

            kategori.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Pilih kategori';
            kategori.appendChild(placeholder);

            list.forEach(function (k) {
                const opt = document.createElement('option');
                opt.value = k.id_kategori;
                opt.textContent = k.nama_kategori;
                kategori.appendChild(opt);
            });
        } catch (err) {
            kategori.innerHTML = '<option value="">Gagal memuat kategori</option>';
            console.error(err);
        }
    }

    // ---------- Tabel ----------
    function render() {
        tabel.innerHTML = '';

        if (data.length === 0) {
            const tr = document.createElement('tr');
            const cell = td('Belum ada data ' + jenis + '.', 'table-empty');
            cell.colSpan = 6;
            tr.appendChild(cell);
            tabel.appendChild(tr);
        }

        data.forEach(function (row) {
            const tr = document.createElement('tr');

            tr.appendChild(td(formatTanggal(row.tanggal)));
            tr.appendChild(td(row.nama_kategori));
            tr.appendChild(td(row.nama_lengkap));
            tr.appendChild(td(rupiah(row.jumlah), kelasNominal));
            tr.appendChild(td(row.keterangan || '-'));

            const aksi = document.createElement('td');
            const wrap = document.createElement('div');
            wrap.className = 'cell-actions';

            const btnUbah = document.createElement('button');
            btnUbah.type = 'button';
            btnUbah.className = 'btn-table';
            btnUbah.textContent = 'Ubah';
            btnUbah.addEventListener('click', function () { mulaiUbah(row); });

            const btnHapus = document.createElement('button');
            btnHapus.type = 'button';
            btnHapus.className = 'btn-table danger';
            btnHapus.textContent = 'Hapus';
            btnHapus.addEventListener('click', function () { hapus(row); });

            wrap.appendChild(btnUbah);
            wrap.appendChild(btnHapus);
            aksi.appendChild(wrap);
            tr.appendChild(aksi);

            tabel.appendChild(tr);
        });

        // Ringkasan: total bulan ini & jumlah semua transaksi
        const bulanIni = hariIni().slice(0, 7);
        const total = data
            .filter(function (r) { return String(r.tanggal).startsWith(bulanIni); })
            .reduce(function (sum, r) { return sum + Number(r.jumlah); }, 0);

        totalBulanIni.textContent = rupiah(total);
        jumlahTransaksi.textContent = data.length;
    }

    async function loadData() {
        try {
            data = await getJson(endpoint + '?aksi=daftar');
            render();
        } catch (err) {
            tabel.innerHTML = '';
            const tr = document.createElement('tr');
            const cell = td('Gagal memuat data.', 'table-empty');
            cell.colSpan = 6;
            tr.appendChild(cell);
            tabel.appendChild(tr);
            console.error(err);
        }
    }

    // ---------- Mode form: tambah / ubah ----------
    function modeTambah() {
        aksiInput.value = 'tambah';
        idInput.value = '';
        formTitle.textContent = 'Tambah ' + label;
        btnSubmit.textContent = '+ Simpan ' + label;
        tanggal.value = hariIni();
    }

    function mulaiUbah(row) {
        aksiInput.value = 'ubah';
        idInput.value = row[idKey];
        kategori.value = row.id_kategori;
        jumlah.value = Number(row.jumlah);
        tanggal.value = row.tanggal;
        keterangan.value = row.keterangan || '';

        formTitle.textContent = 'Ubah ' + label;
        btnSubmit.textContent = 'Simpan Perubahan';

        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function hapus(row) {
        if (!confirm('Yakin ingin menghapus data ini?')) return;

        hapusId.value = row[idKey];
        formHapus.submit();
    }

    // Tombol Reset mengembalikan form ke mode tambah
    form.addEventListener('reset', function () {
        setTimeout(modeTambah, 0);
    });

    // Cegah klik ganda saat submit
    form.addEventListener('submit', function () {
        btnSubmit.disabled = true;
    });

    // ---------- Jalankan ----------
    modeTambah();
    loadKategori();
    loadData();
});
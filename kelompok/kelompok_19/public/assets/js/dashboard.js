// public/assets/js/dashboard.js
// Beranda: statistics, list stok menipis, siklus terdekat, upah, dan weather.

    const BATAS_STOK_MINIMUM = 20;
    const BATAS_STOK_SEGERA = 5;
    const number = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 });
    const rupiah = new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0
    });
    const hariIni = new Date();
    hariIni.setHours(0, 0, 0, 0);

    const el = (id) => document.getElementById(id);

    function daftarDari(json) {
        if (Array.isArray(json)) return json;
        if (json && Array.isArray(json.data)) return json.data;
        return [];
    }

    function aman(value) {
        const node = document.createElement("span");
        node.textContent = value == null ? "" : value;
        return node.innerHTML;
    }

    function keTanggal(value) {
        if (!value) return null;
        const tanggal = new Date(value + "T00:00:00");
        return Number.isNaN(tanggal.getTime()) ? null : tanggal;
    }

    function selisihHari(dari, sampai) {
        return Math.round((sampai - dari) / 86400000);
    }

    function tanggalPendek(value) {
        const tanggal = keTanggal(value);
        if (!tanggal) return "tanggal belum diisi";
        return tanggal.toLocaleDateString("id-ID", { day: "numeric", month: "short" });
    }

    async function ambilJson(url) {
        const respons = await fetch(url, { headers: { "Accept": "application/json" } });
        if (!respons.ok) throw new Error("HTTP " + respons.status);
        return respons.json();
    }

    function statusStok(stok) {
        if (stok <= BATAS_STOK_SEGERA) return { kelas: "urgent", teks: "Segera tindak" };
        if (stok <= BATAS_STOK_MINIMUM) return { kelas: "warning", teks: "Perlu perhatian" };
        return { kelas: "aman", teks: "Aman" };
    }

    function tampilLahan(hasil) {
        el("stat-lahan").textContent = hasil.status === "fulfilled"
            ? number.format(daftarDari(hasil.value).length)
            : "—";
    }

    function tampilPanen(hasil) {
        if (hasil.status !== "fulfilled") {
            el("stat-panen").textContent = "—";
            return;
        }
        const data = daftarDari(hasil.value);
        const total = hasil.value.ringkasan && hasil.value.ringkasan.total_kg != null
            ? Number(hasil.value.ringkasan.total_kg)
            : data.reduce((jumlah, item) => jumlah + Number(item.jumlah_kg || 0), 0);
        el("stat-panen").textContent = number.format(total);
    }

    function tampilSiklus(hasil) {
        const wadah = el("daftarSiklusTerdekat");

        if (hasil.status !== "fulfilled") {
            el("stat-siklus").textContent = "—";
            wadah.innerHTML = '<li class="list-empty">Data siklus belum bisa dimuat. Coba muat ulang halaman.</li>';
            return [];
        }

        const aktif = daftarDari(hasil.value).filter((item) => item.status === "Aktif");
        el("stat-siklus").textContent = number.format(aktif.length);

        const dekat = aktif
            .filter((item) => item.tanggal_panen_estimasi)
            .sort((a, b) => String(a.tanggal_panen_estimasi).localeCompare(String(b.tanggal_panen_estimasi)))
            .slice(0, 3);

        if (!dekat.length) {
            wadah.innerHTML = '<li class="list-empty">Belum ada siklus aktif dengan estimasi panen.</li>';
            return [];
        }

        wadah.innerHTML = dekat.map((item) => {
            const tanam = keTanggal(item.tanggal_tanam);
            const estimasi = keTanggal(item.tanggal_panen_estimasi);
            const total = tanam && estimasi ? Math.max(1, selisihHari(tanam, estimasi)) : 90;
            const berjalan = tanam ? Math.max(0, selisihHari(tanam, hariIni)) : 0;
            const sisa = estimasi ? Math.max(0, selisihHari(hariIni, estimasi)) : null;
            const value = sisa === null ? "Estimasi belum diisi" : sisa === 0 ? "Panen hari ini" : sisa + " hari lagi";
            const isi = Math.min(berjalan, total);

            return '<li><a class="linked-row" href="/siklus">' +
                '<span class="row-main">' + aman(item.nama_lahan || "Petak") + " · " + aman(item.nama_tanaman) + '</span>' +
                '<span class="progress-row">' +
                    '<progress value="' + isi + '" max="' + total + '" aria-label="Progres ' + aman(item.nama_tanaman) + '">' + isi + " dari " + total + '</progress>' +
                    '<span class="progress-label">Hari ke-' + berjalan + " dari ±" + total + " · panen sekitar " + tanggalPendek(item.tanggal_panen_estimasi) + '</span>' +
                '</span>' +
                '<span class="row-value">' + value + '</span>' +
                '</a></li>';
        }).join("");

        return dekat;
    }

    function tampilPerhatian(stokHasil, siklusHasil) {
        const wadah = el("daftarStokMenipis");
        let jumlahMenipis = 0;
        let jumlahSiap = 0;
        const baris = [];

        if (stokHasil.status === "fulfilled") {
            const semua = daftarDari(stokHasil.value);
            el("stat-inventaris").textContent = number.format(semua.length);

            semua
                .filter((item) => Number(item.stok) <= BATAS_STOK_MINIMUM)
                .sort((a, b) => Number(a.stok) - Number(b.stok))
                .slice(0, 3)
                .forEach((item) => {
                    jumlahMenipis += 1;
                    const stok = Number(item.stok) || 0;
                    const status = statusStok(stok);
                    baris.push(
                        '<li><span class="row-main">' + aman(item.nama) + ' tinggal ' + number.format(stok) + " " + aman(item.satuan) + '.</span>' +
                        '<span class="status status--' + status.kelas + '">' + status.teks + '</span>' +
                        '<a class="btn btn--secondary btn--sm" href="/inventaris">Tambah stok</a></li>'
                    );
                });
        } else {
            el("stat-inventaris").textContent = "—";
            baris.push('<li class="list-empty">Data inventaris belum bisa dimuat.</li>');
        }

        if (siklusHasil.status === "fulfilled") {
            const siap = daftarDari(siklusHasil.value)
                .filter((item) => item.status === "Aktif" && item.tanggal_panen_estimasi)
                .map((item) => {
                    const estimasi = keTanggal(item.tanggal_panen_estimasi);
                    return { item, sisa: estimasi ? selisihHari(hariIni, estimasi) : null };
                })
                .filter((data) => data.sisa !== null && data.sisa <= 14 && data.sisa >= 0)
                .sort((a, b) => a.sisa - b.sisa)
                .slice(0, 2);

            siap.forEach(({ item, sisa }) => {
                jumlahSiap += 1;
                baris.push(
                    '<li><span class="row-main">' + aman(item.nama_tanaman) + ' di ' + aman(item.nama_lahan) + " siap dipanen" + (sisa === 0 ? " hari ini." : " dalam " + sisa + " hari.") + '</span>' +
                    '<span class="badge badge--yellow">Siap dipanen</span>' +
                    '<a class="btn btn--secondary btn--sm" href="/siklus">Lihat petak</a></li>'
                );
            });
        }

        if (baris.length === 0) {
            wadah.innerHTML = '<li class="list-empty">Semua aman hari ini. Tidak ada stok menipis atau petak yang mendesak.</li>';
            return;
        }

        wadah.innerHTML = baris.join("");
    }

    function tampilPresensi(hasil) {
        if (hasil.status !== "fulfilled") {
            el("totalUpahDashboard").textContent = "—";
            el("jumlahPekerjaDashboard").textContent = "—";
            return;
        }
        const data = daftarDari(hasil.value);
        const total = Number(hasil.value.total_upah || 0);
        el("totalUpahDashboard").textContent = rupiah.format(total);
        el("jumlahPekerjaDashboard").textContent = data.length;
    }

    async function muatCuaca() {
        try {
            const respons = await fetch("https://api.open-meteo.com/v1/forecast?latitude=-6.2&longitude=106.8&current_weather=true");
            if (!respons.ok) throw new Error("HTTP " + respons.status);
            const data = await respons.json();
            if (!data.current_weather) throw new Error("Data cuaca tidak tersedia.");
            el("weather-widget").innerHTML = "<strong>" + data.current_weather.temperature + "&deg;C</strong> <span>" + data.current_weather.windspeed + " km/jam</span>";
        } catch (error) {
            el("weather-widget").textContent = "Data cuaca tidak tersedia.";
        }
    }

    async function muatBeranda() {
        const [lahan, siklus, inventaris, panen, pekerja] = await Promise.allSettled([
            ambilJson("/api/lahan"),
            ambilJson("/api/siklus"),
            ambilJson("/api/inventaris"),
            ambilJson("/api/panen"),
            ambilJson("/api/pekerja")
        ]);

        tampilLahan(lahan);
        tampilPanen(panen);
        tampilSiklus(siklus);
        tampilPerhatian(inventaris, siklus);
        tampilPresensi(pekerja);
        muatCuaca();
    }

    muatBeranda();



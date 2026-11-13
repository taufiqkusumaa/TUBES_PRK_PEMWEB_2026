(function () {
    'use strict';

    const csrfToken    = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const JUMLAH_KOLOM = 12;
    const collator     = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });

    let daftarPenugasan = [];
    let penugasanAktif  = null;
    let dataSiswaRekap  = [];
    let bobotAktif      = { bobot_tugas: 25, bobot_kuis: 25, bobot_uts: 25, bobot_uas: 25 };
    let activeRekapReq  = 0;

    const laporanRoot        = document.getElementById('laporan-root');
    const selectKelas        = document.getElementById('lap-kelas');
    const selectMapel        = document.getElementById('lap-mapel');
    const selectPeriode      = document.getElementById('lap-periode');
    const badgeStatusRekap   = document.getElementById('badge-status-rekap');
    const infoGuruPengampu   = document.getElementById('info-guru-pengampu');

    const formBobot          = document.getElementById('form-bobot');
    const inputBobotTugas    = document.getElementById('bobot-tugas');
    const inputBobotKuis     = document.getElementById('bobot-kuis');
    const inputBobotUts      = document.getElementById('bobot-uts');
    const inputBobotUas      = document.getElementById('bobot-uas');
    const badgeTotalBobot    = document.getElementById('badge-total-bobot');
    const pesanValidasiBobot = document.getElementById('bobot-pesan-validasi');
    const btnSimpanBobot     = document.getElementById('btn-simpan-bobot');
    const btnHitungUlang     = document.getElementById('btn-hitung-ulang');
    const btnCetakLaporan    = document.getElementById('btn-cetak-laporan');

    const inputSearchSiswa   = document.getElementById('search-siswa-laporan');
    const countSiswaLaporan  = document.getElementById('count-siswa-laporan');
    const bodyRekapNilai     = document.getElementById('body-rekap-nilai');
    const laporanNotice      = document.getElementById('laporan-notice');

    const statRataKelas      = document.getElementById('stat-rata-kelas');
    const statPredikatKelas  = document.getElementById('stat-predikat-kelas');
    const statNilaiMax       = document.getElementById('stat-nilai-max');
    const statNilaiMin       = document.getElementById('stat-nilai-min');
    const statKelengkapan    = document.getElementById('stat-kelengkapan');
    const statSiswaLengkap   = document.getElementById('stat-siswa-lengkap');

    const printMapel         = document.getElementById('print-mapel');
    const printKelas         = document.getElementById('print-kelas');
    const printPeriode       = document.getElementById('print-periode');
    const printGuru          = document.getElementById('print-guru');
    const printBobot         = document.getElementById('print-bobot');
    const printTanggalTtd    = document.getElementById('print-tanggal-ttd');
    const printNamaTtd       = document.getElementById('print-nama-ttd');
    const printNipTtd        = document.getElementById('print-nip-ttd');

    async function apiRequest(url, method = 'GET', body = null) {
        const options = {
            method,
            cache: 'no-store',
            headers: { 'X-CSRF-Token': csrfToken }
        };

        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const urlFinal = method === 'GET'
            ? `${url}${url.includes('?') ? '&' : '?'}_=${Date.now()}`
            : url;

        const response = await fetch(urlFinal, options);

        if (response.status === 401) {
            window.location.href = 'login.php';
            throw new Error('Sesi telah berakhir. Silakan masuk kembali.');
        }

        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error('Respons server tidak valid.');
        }

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'Terjadi kesalahan pada permintaan data.');
        }

        return result.data;
    }

    function tampilkanNotifikasi(pesan, isError = false) {
        if (!laporanNotice) return;
        laporanNotice.hidden = false;
        laporanNotice.className = 'message ' + (isError ? 'error' : 'success');
        laporanNotice.textContent = pesan;

        if (!isError) {
            setTimeout(() => {
                laporanNotice.hidden = true;
            }, 5000);
        }
    }

    function buatSel(teks, className = '') {
        const td = document.createElement('td');
        td.textContent = teks;
        if (className) td.className = className;
        return td;
    }

    function parseAngkaBobot(nilai, fallback = 25) {
        const angka = Number(nilai);
        return nilai !== null && nilai !== undefined && nilai !== '' && Number.isFinite(angka)
            ? angka
            : fallback;
    }

    function unikBerdasarkan(items, keyFn, labelFn, sortFn = null) {
        const map = new Map();
        items.forEach(item => {
            const key = String(keyFn(item));
            if (!map.has(key)) {
                map.set(key, { value: key, label: labelFn(item), raw: item });
            }
        });
        const list = [...map.values()];
        if (sortFn) list.sort(sortFn);
        return list;
    }

    function isiSelect(selectEl, options, placeholder, preferredValue = '') {
        selectEl.replaceChildren(new Option(placeholder, ''));
        options.forEach(opt => selectEl.add(new Option(opt.label, opt.value)));
        const target = String(preferredValue || '');
        if (target && options.some(o => o.value === target)) {
            selectEl.value = target;
        } else if (options.length > 0) {
            selectEl.value = options[0].value;
        } else {
            selectEl.value = '';
        }
    }

    function bobotTingkat(t) {
        return { X: 1, '10': 1, XI: 2, '11': 2, XII: 3, '12': 3 }[String(t || '').toUpperCase()] || 9;
    }

    function sinkronkanDropdown(preferred = {}) {
        const kelasOpts = unikBerdasarkan(
            daftarPenugasan,
            p => p.id_kelas,
            p => p.nama_kelas,
            (a, b) => bobotTingkat(a.raw.tingkat) - bobotTingkat(b.raw.tingkat) || collator.compare(a.label, b.label)
        );
        isiSelect(
            selectKelas,
            kelasOpts,
            kelasOpts.length ? 'Pilih kelas' : 'Belum ada kelas',
            preferred.id_kelas ?? selectKelas.value
        );

        const idKelas = selectKelas.value;
        const untukKelas = idKelas
            ? daftarPenugasan.filter(p => String(p.id_kelas) === idKelas)
            : [];

        const mapelOpts = unikBerdasarkan(
            untukKelas,
            p => p.id_mapel,
            p => `${p.nama_mapel} (${p.kode_mapel})`,
            (a, b) => collator.compare(a.label, b.label)
        );
        isiSelect(
            selectMapel,
            mapelOpts,
            mapelOpts.length ? 'Pilih mata pelajaran' : 'Belum ada mapel',
            preferred.id_mapel ?? selectMapel.value
        );

        const idMapel = selectMapel.value;
        const untukMapel = idMapel
            ? untukKelas.filter(p => String(p.id_mapel) === idMapel)
            : [];

        const periodeOpts = unikBerdasarkan(
            untukMapel,
            p => p.id_periode,
            p => `${p.tahun_ajaran} · ${p.semester}${Number(p.status_aktif) === 1 ? ' (aktif)' : ''}`,
            (a, b) => Number(b.raw.status_aktif) - Number(a.raw.status_aktif) ||
                      collator.compare(b.raw.tahun_ajaran, a.raw.tahun_ajaran)
        );
        isiSelect(
            selectPeriode,
            periodeOpts,
            periodeOpts.length ? 'Pilih periode' : 'Belum ada periode',
            preferred.id_periode ?? selectPeriode.value
        );
    }

    function cariPenugasanTerpilih(preferredIdPenugasan = '') {
        const idKelas   = selectKelas.value;
        const idMapel   = selectMapel.value;
        const idPeriode = selectPeriode.value;
        if (!idKelas || !idMapel || !idPeriode) return null;

        const kandidat = daftarPenugasan.filter(p =>
            String(p.id_kelas) === idKelas &&
            String(p.id_mapel) === idMapel &&
            String(p.id_periode) === idPeriode
        );
        if (!kandidat.length) return null;

        if (preferredIdPenugasan) {
            const cocok = kandidat.find(p => String(p.id_penugasan) === String(preferredIdPenugasan));
            if (cocok) return cocok;
        }
        return kandidat.find(p => p.bisa_ubah) || kandidat[0];
    }

    function aturAksesUbah(bisaUbah) {
        [inputBobotTugas, inputBobotKuis, inputBobotUts, inputBobotUas].forEach(inp => {
            if (inp) inp.disabled = !bisaUbah;
        });
        if (btnHitungUlang) btnHitungUlang.disabled = !bisaUbah || !penugasanAktif;

        if (!penugasanAktif) {
            if (btnSimpanBobot) btnSimpanBobot.disabled = true;
            return;
        }
        if (!bisaUbah) {
            if (btnSimpanBobot) btnSimpanBobot.disabled = true;
            if (pesanValidasiBobot) {
                pesanValidasiBobot.textContent = 'Mode baca (Wali Kelas): hanya guru pengampu atau admin yang dapat mengubah bobot.';
                pesanValidasiBobot.style.color = 'var(--muted)';
            }
        } else {
            validasiTotalBobot();
        }
    }

    async function muatKonteks() {
        try {
            const data = await apiRequest('api/kalkulasi.php?action=konteks');
            daftarPenugasan = data.penugasan || [];

            if (!daftarPenugasan.length) {
                [selectKelas, selectMapel, selectPeriode].forEach(sel => {
                    sel.replaceChildren(new Option('Tidak ada data', ''));
                    sel.disabled = true;
                });
                aturAksesUbah(false);
                badgeStatusRekap.textContent = 'Kosong';
                tampilkanNotifikasi('Anda belum memiliki akses penugasan mengajar atau wali kelas.', true);
                return;
            }

            const pilihanSesi = data.pilihan || {};
            const preferred = {
                id_kelas:     pilihanSesi.id_kelas || laporanRoot?.dataset.kelas || '',
                id_mapel:     pilihanSesi.id_mapel || laporanRoot?.dataset.mapel || '',
                id_periode:   pilihanSesi.id_periode || laporanRoot?.dataset.periode || '',
                id_penugasan: pilihanSesi.id_penugasan || laporanRoot?.dataset.penugasan || ''
            };

            const cocokAwal = daftarPenugasan.find(p =>
                String(p.id_kelas) === String(preferred.id_kelas) &&
                String(p.id_mapel) === String(preferred.id_mapel) &&
                String(p.id_periode) === String(preferred.id_periode)
            ) || daftarPenugasan[0];

            sinkronkanDropdown({
                id_kelas:   cocokAwal.id_kelas,
                id_mapel:   cocokAwal.id_mapel,
                id_periode: cocokAwal.id_periode
            });

            await handleKonteksBerubah(preferred.id_penugasan, false);
        } catch (error) {
            tampilkanNotifikasi(error.message, true);
        }
    }

    async function handleKonteksBerubah(preferredIdPenugasan = '', simpanKeSesi = true) {
        penugasanAktif = cariPenugasanTerpilih(preferredIdPenugasan);

        if (!penugasanAktif) {
            badgeStatusRekap.textContent = 'Pilih mapel';
            if (infoGuruPengampu) infoGuruPengampu.textContent = '';
            aturAksesUbah(false);
            resetTabel();
            return;
        }

        perbaruiBadgeStatus(false);
        if (infoGuruPengampu) {
            infoGuruPengampu.textContent = `Pengampu: ${penugasanAktif.nama_guru}${penugasanAktif.bisa_ubah ? '' : ' (Mode baca)'}`;
        }

        if (printMapel)   printMapel.textContent   = `${penugasanAktif.nama_mapel} (${penugasanAktif.kode_mapel})`;
        if (printKelas)   printKelas.textContent   = `${penugasanAktif.nama_kelas} (Tingkat ${penugasanAktif.tingkat})`;
        if (printPeriode) printPeriode.textContent = `${penugasanAktif.tahun_ajaran} • Semester ${penugasanAktif.semester}`;
        if (printGuru)    printGuru.textContent    = penugasanAktif.nama_guru;
        if (printNamaTtd) printNamaTtd.textContent = penugasanAktif.nama_guru;
        if (printNipTtd)  printNipTtd.textContent  = `NIP. ${penugasanAktif.nip || '—'}`;

        aturAksesUbah(Boolean(penugasanAktif.bisa_ubah));

        if (simpanKeSesi) {
            apiRequest('api/kalkulasi.php?action=simpan_konteks', 'POST', {
                id_penugasan: penugasanAktif.id_penugasan
            }).catch(() => {});
        }

        await muatRekapLengkap(penugasanAktif.id_penugasan);
    }

    async function muatRekapLengkap(idPenugasan) {
        const reqId = ++activeRekapReq;
        bodyRekapNilai.innerHTML = `<tr><td colspan="${JUMLAH_KOLOM}" class="empty">Memuat data rekapitulasi nilai…</td></tr>`;

        try {
            const response = await apiRequest(`api/kalkulasi.php?action=rekap&id_penugasan=${idPenugasan}`);
            if (reqId !== activeRekapReq) return;

            if (response.bobot) {
                bobotAktif = {
                    bobot_tugas: parseAngkaBobot(response.bobot.bobot_tugas, 25),
                    bobot_kuis:  parseAngkaBobot(response.bobot.bobot_kuis, 25),
                    bobot_uts:   parseAngkaBobot(response.bobot.bobot_uts, 25),
                    bobot_uas:   parseAngkaBobot(response.bobot.bobot_uas, 25)
                };
                inputBobotTugas.value = bobotAktif.bobot_tugas;
                inputBobotKuis.value  = bobotAktif.bobot_kuis;
                inputBobotUts.value   = bobotAktif.bobot_uts;
                inputBobotUas.value   = bobotAktif.bobot_uas;
                updatePrintBobot();
                aturAksesUbah(Boolean(response.penugasan?.bisa_ubah));
            }

            dataSiswaRekap = response.siswa || [];
            handlePencarianSiswa();
            renderRingkasan(response.ringkasan);
        } catch (error) {
            if (reqId !== activeRekapReq) return;
            const tr = document.createElement('tr');
            const td = buatSel(error.message, 'empty');
            td.colSpan = JUMLAH_KOLOM;
            td.style.color = 'var(--muted)';
            tr.appendChild(td);
            bodyRekapNilai.replaceChildren(tr);
            tampilkanNotifikasi(error.message, true);
        }
    }

    function validasiTotalBobot() {
        const bt = parseFloat(inputBobotTugas.value);
        const bk = parseFloat(inputBobotKuis.value);
        const bu = parseFloat(inputBobotUts.value);
        const ba = parseFloat(inputBobotUas.value);

        const validAngka = [bt, bk, bu, ba].every(Number.isFinite);
        const total = validAngka ? Math.round((bt + bk + bu + ba) * 100) / 100 : 0;

        badgeTotalBobot.textContent = `Total: ${total}%`;

        const isNegatif = validAngka && (bt < 0 || bk < 0 || bu < 0 || ba < 0);
        const isPas100  = validAngka && (Math.abs(total - 100) <= 0.01) && !isNegatif;
        const bolehUbah = Boolean(penugasanAktif?.bisa_ubah);

        if (isPas100) {
            badgeTotalBobot.style.background = '#e1f0e5';
            badgeTotalBobot.style.color = '#215c38';
            if (bolehUbah) {
                pesanValidasiBobot.textContent = '✓ Total bobot pas 100%';
                pesanValidasiBobot.style.color = '#215c38';
            }
            btnSimpanBobot.disabled = !bolehUbah;
        } else {
            badgeTotalBobot.style.background = '#fae8e4';
            badgeTotalBobot.style.color = '#983f37';
            if (!validAngka) {
                pesanValidasiBobot.textContent = '✕ Semua bobot harus diisi angka';
            } else if (isNegatif) {
                pesanValidasiBobot.textContent = '✕ Bobot tidak boleh bernilai negatif';
            } else if (total < 100) {
                pesanValidasiBobot.textContent = `✕ Total bobot kurang ${(100 - total).toFixed(1)}% (harus 100%)`;
            } else {
                pesanValidasiBobot.textContent = `✕ Total bobot kelebihan ${(total - 100).toFixed(1)}% (harus 100%)`;
            }
            pesanValidasiBobot.style.color = '#983f37';
            btnSimpanBobot.disabled = true;
        }

        return isPas100;
    }

    function updatePrintBobot() {
        if (printBobot) {
            printBobot.textContent = `Tugas ${bobotAktif.bobot_tugas}%, Kuis ${bobotAktif.bobot_kuis}%, UTS ${bobotAktif.bobot_uts}%, UAS ${bobotAktif.bobot_uas}%`;
        }
    }

    async function handleSimpanBobot(e) {
        e.preventDefault();
        if (!penugasanAktif) {
            tampilkanNotifikasi('Silakan pilih kelas, mata pelajaran, dan periode terlebih dahulu.', true);
            return;
        }
        if (!penugasanAktif.bisa_ubah) {
            tampilkanNotifikasi('Hanya guru pengampu atau admin yang dapat mengubah bobot.', true);
            return;
        }
        if (!validasiTotalBobot()) {
            tampilkanNotifikasi('Total bobot harus tepat 100% sebelum disimpan.', true);
            return;
        }

        btnSimpanBobot.disabled = true;
        btnSimpanBobot.textContent = 'Menyimpan…';

        try {
            const payload = {
                bobot_tugas: parseFloat(inputBobotTugas.value),
                bobot_kuis:  parseFloat(inputBobotKuis.value),
                bobot_uts:   parseFloat(inputBobotUts.value),
                bobot_uas:   parseFloat(inputBobotUas.value)
            };

            await apiRequest(
                `api/kalkulasi.php?action=bobot&id_penugasan=${penugasanAktif.id_penugasan}`,
                'POST',
                payload
            );

            bobotAktif = payload;
            updatePrintBobot();
            tampilkanNotifikasi('Pengaturan bobot disimpan dan rekap nilai kelas otomatis disinkronkan.');

            await muatRekapLengkap(penugasanAktif.id_penugasan);
        } catch (error) {
            tampilkanNotifikasi(error.message, true);
        } finally {
            btnSimpanBobot.textContent = 'Simpan pengaturan bobot';
            aturAksesUbah(Boolean(penugasanAktif?.bisa_ubah));
        }
    }

    async function handleHitungUlang() {
        if (!penugasanAktif) {
            tampilkanNotifikasi('Silakan pilih kelas, mata pelajaran, dan periode terlebih dahulu.', true);
            return;
        }
        if (!penugasanAktif.bisa_ubah) {
            tampilkanNotifikasi('Hanya guru pengampu atau admin yang dapat menyimpan kalkulasi.', true);
            return;
        }

        btnHitungUlang.disabled = true;
        btnHitungUlang.textContent = 'Menyimpan…';

        try {
            const hasil = await apiRequest(
                `api/kalkulasi.php?action=hitung&id_penugasan=${penugasanAktif.id_penugasan}`,
                'POST'
            );

            tampilkanNotifikasi(`Rekap nilai & kuadran berhasil disimpan untuk ${hasil.length} siswa.`);
            await muatRekapLengkap(penugasanAktif.id_penugasan);
        } catch (error) {
            tampilkanNotifikasi(error.message, true);
        } finally {
            btnHitungUlang.textContent = 'Simpan & hitung rekap';
            btnHitungUlang.disabled = !penugasanAktif?.bisa_ubah;
        }
    }

    function renderTabelRekap(siswaList) {
        bodyRekapNilai.innerHTML = '';
        countSiswaLaporan.textContent = `${siswaList.length} siswa`;

        if (!siswaList || siswaList.length === 0) {
            const emptyRow  = document.createElement('tr');
            const emptyCell = document.createElement('td');
            emptyCell.colSpan   = JUMLAH_KOLOM;
            emptyCell.className = 'empty';
            emptyCell.textContent = inputSearchSiswa.value
                ? 'Tidak ada siswa yang cocok dengan kata kunci pencarian.'
                : 'Belum ada data siswa terdaftar di kelas ini.';
            emptyRow.appendChild(emptyCell);
            bodyRekapNilai.appendChild(emptyRow);
            return;
        }

        const formatKomponen = val => (val !== null && val !== undefined ? Number(val).toFixed(1) : '—');

        siswaList.forEach((s, idx) => {
            const row = document.createElement('tr');

            row.appendChild(buatSel(String(idx + 1)));
            row.appendChild(buatSel(s.nisn || '—'));

            const tdNama = buatSel(s.nama_siswa || '—');
            tdNama.style.fontWeight = '600';
            row.appendChild(tdNama);

            row.appendChild(buatSel(formatKomponen(s.rata_tugas)));
            row.appendChild(buatSel(formatKomponen(s.rata_kuis)));
            row.appendChild(buatSel(formatKomponen(s.rata_uts)));
            row.appendChild(buatSel(formatKomponen(s.rata_uas)));

            const tdAkhir = buatSel(
                s.nilai_akhir !== null && s.nilai_akhir !== undefined
                    ? Number(s.nilai_akhir).toFixed(2)
                    : '—'
            );
            tdAkhir.style.fontWeight = 'bold';
            row.appendChild(tdAkhir);

            const tdPredikat = document.createElement('td');
            if (s.predikat && s.predikat !== '-') {
                const pill = document.createElement('span');
                pill.className = 'predikat-pill';
                pill.textContent = s.predikat;
                tdPredikat.appendChild(pill);
            } else {
                tdPredikat.textContent = '—';
            }
            row.appendChild(tdPredikat);

            row.appendChild(buatSel(
                s.indeks_kedisiplinan !== null && s.indeks_kedisiplinan !== undefined
                    ? `${Number(s.indeks_kedisiplinan).toFixed(0)}%`
                    : '—'
            ));

            const tdLengkap = document.createElement('td');
            const badgeLengkap = document.createElement('span');
            const isLengkap = s.status_kelengkapan_nilai === 'Lengkap';
            badgeLengkap.className = isLengkap ? 'badge-lengkap' : 'badge-belum';
            badgeLengkap.textContent = s.status_kelengkapan_nilai || 'Belum lengkap';
            if (s.jumlah_penilaian > 0) {
                badgeLengkap.title = `${s.jumlah_dinilai}/${s.jumlah_penilaian} penilaian terisi`;
            }
            tdLengkap.appendChild(badgeLengkap);
            row.appendChild(tdLengkap);

            const tdKuadran = document.createElement('td');
            tdKuadran.className = 'no-print';
            if (s.kategori_kuadran) {
                const tag = document.createElement('span');
                tag.className = 'kuadran-tag';
                tag.textContent = s.kategori_kuadran;
                tdKuadran.appendChild(tag);
            } else {
                tdKuadran.textContent = s.nilai_akhir === null ? 'Belum ada nilai' : 'Menunggu tugas';
                tdKuadran.style.color = 'var(--muted)';
                tdKuadran.style.fontSize = '12px';
            }
            row.appendChild(tdKuadran);

            bodyRekapNilai.appendChild(row);
        });
    }

    function perbaruiBadgeStatus(perluHitungUlang = false) {
        if (!penugasanAktif) return;
        const label = `${penugasanAktif.nama_kelas} • ${penugasanAktif.kode_mapel}`;
        const belumSinkron = Boolean(perluHitungUlang) && Boolean(penugasanAktif.bisa_ubah);
        badgeStatusRekap.textContent = belumSinkron
            ? `${label} (Belum tersinkron ke DB)`
            : label;
    }

    function renderRingkasan(data) {
        perbaruiBadgeStatus(Boolean(data?.perlu_hitung_ulang));

        if (!data || !data.ada_nilai) {
            statRataKelas.textContent     = '—';
            statNilaiMax.textContent      = '—';
            statNilaiMin.textContent      = '—';
            statKelengkapan.textContent   = data ? `${data.persen_lengkap}%` : '—';
            statPredikatKelas.textContent = 'Belum ada nilai pada mapel ini';
            statSiswaLengkap.textContent  = data
                ? `${data.jumlah_lengkap} dari ${data.jumlah_siswa} siswa lengkap`
                : 'Belum ada data';
            return;
        }

        statRataKelas.textContent   = Number(data.rata_rata_kelas).toFixed(2);
        statNilaiMax.textContent    = Number(data.nilai_tertinggi).toFixed(2);
        statNilaiMin.textContent    = Number(data.nilai_terendah).toFixed(2);
        statKelengkapan.textContent = `${data.persen_lengkap}%`;

        let predikatRata = 'E';
        if (data.rata_rata_kelas >= 85) predikatRata = 'A';
        else if (data.rata_rata_kelas >= 75) predikatRata = 'B';
        else if (data.rata_rata_kelas >= 65) predikatRata = 'C';
        else if (data.rata_rata_kelas >= 55) predikatRata = 'D';

        statPredikatKelas.textContent = `Predikat rata-rata: ${predikatRata}`;
        statSiswaLengkap.textContent  = `${data.jumlah_lengkap} dari ${data.jumlah_siswa} siswa lengkap`;
    }

    function handlePencarianSiswa() {
        const keyword = inputSearchSiswa.value.toLowerCase().trim();
        if (!keyword) {
            renderTabelRekap(dataSiswaRekap);
            return;
        }

        const filtered = dataSiswaRekap.filter(s =>
            (s.nama_siswa && s.nama_siswa.toLowerCase().includes(keyword)) ||
            (s.nisn && s.nisn.toLowerCase().includes(keyword))
        );
        renderTabelRekap(filtered);
    }

    function handleCetakLaporan() {
        if (!penugasanAktif) {
            tampilkanNotifikasi('Silakan pilih kelas, mata pelajaran, dan periode sebelum mencetak laporan.', true);
            return;
        }

        if (printTanggalTtd) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            printTanggalTtd.textContent = new Date().toLocaleDateString('id-ID', options);
        }

        inputSearchSiswa.value = '';
        renderTabelRekap(dataSiswaRekap);
        window.print();
    }

    function resetTabel() {
        bodyRekapNilai.innerHTML = `<tr><td colspan="${JUMLAH_KOLOM}" class="empty">Silakan pilih kelas, mata pelajaran, dan periode terlebih dahulu.</td></tr>`;
        countSiswaLaporan.textContent = '0 siswa';
        statRataKelas.textContent     = '—';
        statNilaiMax.textContent      = '—';
        statNilaiMin.textContent      = '—';
        statKelengkapan.textContent   = '—';
        statPredikatKelas.textContent = 'Predikat rata-rata: —';
        statSiswaLengkap.textContent  = '0 dari 0 siswa lengkap';
    }

    function pasangEventListener() {
        if (selectKelas) {
            selectKelas.addEventListener('change', () => {
                sinkronkanDropdown({
                    id_kelas:   selectKelas.value,
                    id_mapel:   selectMapel.value,
                    id_periode: selectPeriode.value
                });
                handleKonteksBerubah();
            });
        }

        if (selectMapel) {
            selectMapel.addEventListener('change', () => {
                sinkronkanDropdown({
                    id_kelas:   selectKelas.value,
                    id_mapel:   selectMapel.value,
                    id_periode: selectPeriode.value
                });
                handleKonteksBerubah();
            });
        }

        if (selectPeriode) {
            selectPeriode.addEventListener('change', () => handleKonteksBerubah());
        }

        [inputBobotTugas, inputBobotKuis, inputBobotUts, inputBobotUas].forEach(inp => {
            if (inp) inp.addEventListener('input', validasiTotalBobot);
        });

        if (formBobot) formBobot.addEventListener('submit', handleSimpanBobot);
        if (btnHitungUlang) btnHitungUlang.addEventListener('click', handleHitungUlang);
        if (btnCetakLaporan) btnCetakLaporan.addEventListener('click', handleCetakLaporan);
        if (inputSearchSiswa) inputSearchSiswa.addEventListener('input', handlePencarianSiswa);
    }

    function init() {
        pasangEventListener();
        muatKonteks();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
document.addEventListener("DOMContentLoaded", function () {
    const listBintang = document.getElementById('list-bintang');
    const listBakat = document.getElementById('list-bakat');
    const listButuh = document.getElementById('list-butuh');
    const listBahaya = document.getElementById('list-bahaya');

    const countBintang = document.getElementById('count-bintang');
    const countBakat = document.getElementById('count-bakat');
    const countButuh = document.getElementById('count-butuh');
    const countBahaya = document.getElementById('count-bahaya');
    const badgeTotal = document.getElementById('badge-total-siswa');

    const modal = document.getElementById('studentModal');
    const modNama = document.getElementById('modalNamaSiswa');
    const modTugas = document.getElementById('modalNilaiTugas');
    const modUjian = document.getElementById('modalNilaiUjian');
    const modBadge = document.getElementById('modalBadgeKuadran');
    const modRekomendasi = document.getElementById('modalRekomendasi');

async function muatDataKlasifikasi() {
        const filterKelas = document.getElementById('filter-kelas').value;
        const filterMapel = document.getElementById('filter-mapel').value;
        const filterPeriode = document.getElementById('filter-periode').value;
        const batasKKM = parseFloat(document.getElementById('input-kkm').value) || 75;
        const batasDisiplin = 80;

        try {
            const response = await fetch(`api/klasifikasi.php?kelas_id=${encodeURIComponent(filterKelas)}&mapel_id=${encodeURIComponent(filterMapel)}&periode_id=${encodeURIComponent(filterPeriode)}`);
            const res = await response.json();

            if (res && res.data) {
                renderKartu(res.data, batasKKM, batasDisiplin);
            } else {
                console.error("Format data respons tidak sesuai:", res.message);
            }
        } catch (error) {
            console.error("Gagal melakukan fetch data klasifikasi:", error);
        }
    }

    function renderKartu(daftarSiswa, batasKKM, batasDisiplin) {
        listBintang.innerHTML = '';
        listBakat.innerHTML = '';
        listButuh.innerHTML = '';
        listBahaya.innerHTML = '';

        let cBintang = 0, cBakat = 0, cButuh = 0, cBahaya = 0;
        badgeTotal.textContent = `Total: ${daftarSiswa.length} Siswa`;

        daftarSiswa.forEach(siswa => {
            const nilaiUjian = parseFloat(siswa.ujian) || 0;
            const nilaiTugas = parseFloat(siswa.tugas) || 0;

            const rajinTugas = nilaiTugas >= batasDisiplin;
            const lulusUjian = nilaiUjian >= batasKKM;

            const pill = document.createElement('div');
            pill.className = 'student-pill';
            pill.innerHTML = `<span>${siswa.name}</span> <span style="color:#888;">&rsaquo;</span>`;
            pill.addEventListener('click', () => bukaDetail(siswa));

            if (rajinTugas && lulusUjian) {
                siswa.labelKlasifikasi = "Rajin & nilai tinggi";
                listBintang.appendChild(pill);
                cBintang++;
            } else if (rajinTugas && !lulusUjian) {
                siswa.labelKlasifikasi = "Rajin & nilai rendah";
                listButuh.appendChild(pill);
                cButuh++;
            } else if (!rajinTugas && lulusUjian) {
                siswa.labelKlasifikasi = "Jarang kumpul & nilai tinggi";
                listBakat.appendChild(pill);
                cBakat++;
            } else {
                siswa.labelKlasifikasi = "Jarang kumpul & nilai rendah";
                listBahaya.appendChild(pill);
                cBahaya++;
            }
        });

        countBintang.textContent = cBintang;
        countBakat.textContent = cBakat;
        countButuh.textContent = cButuh;
        countBahaya.textContent = cBahaya;
    }

    function bukaDetail(siswa) {
    modNama.textContent = siswa.name;
    modTugas.textContent = siswa.tugas + "%";
    modUjian.textContent = siswa.ujian;
    modBadge.textContent = siswa.labelKlasifikasi;

    let teksRekomendasi = "";
    if (siswa.labelKlasifikasi === "Rajin & nilai tinggi") {
        teksRekomendasi = "Pertahankan konsistensi belajar. Siswa siap diberikan materi pengayaan dan dapat ditugaskan sebagai tutor sebaya.";
    } else if (siswa.labelKlasifikasi === "Jarang kumpul & nilai tinggi") {
        teksRekomendasi = "Kemampuan kognitif tinggi namun kedisiplinan rendah. Perlu peningkatan motivasi serta pemantauan pengumpulan tugas harian.";
    } else if (siswa.labelKlasifikasi === "Rajin & nilai rendah") {
        teksRekomendasi = "Kedisiplinan belajar sangat baik namun hasil nilai belum optimal. Disarankan evaluasi cara belajar atau pendampingan remedial konsep dasar.";
    } else {
        teksRekomendasi = "Perlu perhatian segera. Jadwalkan sesi pendampingan belajar khusus dan komunikasi bersama wali kelas/orang tua.";
    }

    modRekomendasi.textContent = teksRekomendasi;
    modal.showModal();
}

    document.getElementById('btn-terapkan-filter').addEventListener('click', muatDataKlasifikasi);
    document.getElementById('btnCloseModal').addEventListener('click', () => modal.close());
    document.getElementById('btnTutupModalFooter').addEventListener('click', () => modal.close());

    muatDataKlasifikasi();

});
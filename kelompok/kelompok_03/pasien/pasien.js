// Menjalankan skrip interaktivitas setelah seluruh struktur DOM halaman selesai dimuat
document.addEventListener('DOMContentLoaded', function () {

    // 1. FITUR FILTER POLI PADA HALAMAN DEPAN (index.php)
    var filterIndex = document.getElementById('filter-poli');
    if (filterIndex) {
        var barisIndex = document.querySelectorAll('.tabel-dokter tbody tr');
        var pesanIndex = document.getElementById('pesan-filter');

        // Menambahkan event listener saat dropdown pilihan poli diubah oleh pengguna
        filterIndex.addEventListener('change', function () {
            var nilaiTerpilih = filterIndex.value;
            var jumlahTampil = 0;

            // Memeriksa kesesuaian data poli di setiap baris tabel
            barisIndex.forEach(function (baris) {
                var apakahCocok = (nilaiTerpilih === '') || (baris.getAttribute('data-poli') === nilaiTerpilih);
                baris.hidden = !apakahCocok;
                if (apakahCocok) {
                    jumlahTampil++;
                }
            });

            // Menampilkan pesan 'kosong' jika tidak ada baris dokter yang sesuai dengan filter
            if (pesanIndex) {
                pesanIndex.hidden = (jumlahTampil > 0);
            }
        });
    }

    // 2. FITUR FILTER POLI PADA DASHBOARD PASIEN (pasien.php)
    var filterPasien = document.getElementById('filter-poli-pasien');
    if (filterPasien) {
        var barisPasien = document.querySelectorAll('#tabel-dokter-pasien tbody tr');

        // Event listener saat dropdown poli diubah di halaman portal pasien
        filterPasien.addEventListener('change', function () {
            var poliPilihan = filterPasien.value;

            barisPasien.forEach(function (tr) {
                var spesialisBaris = tr.getAttribute('data-spesialis');
                var cocokPasien = (poliPilihan === '') || (spesialisBaris === poliPilihan);
                tr.style.display = cocokPasien ? '' : 'none';
            });
        });
    }

});

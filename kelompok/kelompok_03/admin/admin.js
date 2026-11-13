document.addEventListener("DOMContentLoaded", function () {
    // 1. LIVE SEARCH / FILTER TABEL (Untuk Daftar Antrean & Daftar Dokter)
    const inputCari = document.getElementById("cari-admin");
    const filterStatus = document.getElementById("filter-status-admin");
    const barisTabel = document.querySelectorAll("table tbody tr");

    function terapkanFilter() {
        const kataKunci = inputCari ? inputCari.value.toLowerCase().trim() : "";
        const statusDipilih = filterStatus ? filterStatus.value.toLowerCase().trim() : "";

        barisTabel.forEach(function (baris) {
            const teksBaris = baris.textContent.toLowerCase();
            const cocokKata = kataKunci === "" || teksBaris.includes(kataKunci);
            const cocokStatus = statusDipilih === "" || teksBaris.includes(statusDipilih);

            baris.style.display = cocokKata && cocokStatus ? "" : "none";
        });
    }

    if (inputCari) {
        inputCari.addEventListener("input", terapkanFilter);
    }
    if (filterStatus) {
        filterStatus.addEventListener("change", terapkanFilter);
    }

    // 2. VALIDASI FORMULIR DOKTER SEBELUM SUBMIT (Client-Side Validation)
    const formDokter = document.getElementById("form-dokter");
    if (formDokter) {
        formDokter.addEventListener("submit", function (event) {
            const inputKuota = formDokter.querySelector('input[name="kuota_per_hari"]');
            if (inputKuota && parseInt(inputKuota.value, 10) <= 0) {
                event.preventDefault();
                alert("Kuota per hari harus lebih dari 0!");
                inputKuota.focus();
            }
        });
    }
});
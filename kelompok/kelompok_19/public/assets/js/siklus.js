// public/assets/js/siklus.js
// Tugas: AJAX Fetch CRUD Siklus ke /api/siklus

const API_SIKLUS = "/api/siklus";
const namaFase = ["Bibit", "Tanam", "Rawat", "Panen"];
const hariMilidetik = 86400000;

const filterSiklus = { q: "", status: "", lahan_id: "", dari: "", sampai: "" };

function amanSiklus(value) {
    const node = document.createElement("span");
    node.textContent = value == null ? "" : value;
    return node.innerHTML;
}

function keTanggal(value) {
    if (!value) return null;
    const tanggal = new Date(value + "T00:00:00");
    return Number.isNaN(tanggal.getTime()) ? null : tanggal;
}

function hariIniAwal() {
    const sekarang = new Date();
    sekarang.setHours(0, 0, 0, 0);
    return sekarang;
}

function selisihHari(dari, sampai) {
    return Math.round((sampai - dari) / hariMilidetik);
}

function tanggalPendek(value) {
    const tanggal = keTanggal(value);
    if (!tanggal) return "belum diisi";
    return tanggal.toLocaleDateString("id-ID", { day: "numeric", month: "short" });
}

function tanggalPanjangSiklus(value) {
    const tanggal = keTanggal(value);
    if (!tanggal) return "Belum diisi";
    return tanggal.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" });
}

function hitungProgres(item) {
    const tanam = keTanggal(item.tanggal_tanam);
    const estimasi = keTanggal(item.tanggal_panen_estimasi);
    const sekarang = hariIniAwal();
    const total = tanam && estimasi ? Math.max(1, selisihHari(tanam, estimasi)) : 90;
    const berjalan = tanam ? Math.max(0, selisihHari(tanam, sekarang)) : 0;
    const sisa = estimasi ? selisihHari(sekarang, estimasi) : null;
    const value = Math.min(berjalan, total);

    let phase = 2;
    if (["Panen", "Selesai"].includes(item.status)) {
        phase = 3;
    } else if (sisa !== null && sisa <= 14 && sisa >= 0) {
        phase = 3;
    } else if (!tanam || sekarang < tanam) {
        phase = 0;
    } else if (berjalan <= total * 0.2) {
        phase = 1;
    }

    return {
        total: total,
        berjalan: berjalan,
        sisa: sisa,
        value: value,
        phase: phase,
        siap: item.status === "Aktif" && sisa !== null && sisa <= 14 && sisa >= 0,
        label: estimasi
            ? "Hari ke-" + berjalan + " dari ±" + total + " · panen sekitar " + tanggalPendek(item.tanggal_panen_estimasi)
            : "Hari ke-" + berjalan + " · perkiraan panen belum diisi"
    };
}

function labelStatusSiklus(status) {
    if (status === "Aktif") return { kelas: "aman", teks: "Sedang ditanam" };
    if (status === "Panen") return { kelas: "neutral", teks: "Sudah dipanen" };
    if (status === "Gagal") return { kelas: "urgent", teks: "Gagal" };
    return { kelas: "neutral", teks: status || "Selesai" };
}

function faseRantai(aktif) {
    return '<ul class="fase" aria-label="Urutan phase tanam">' +
        namaFase.map((label, index) =>
            '<li' + (index === aktif ? ' aria-current="step"' : '') + ">" + label + "</li>"
        ).join("") +
        "</ul>";
}

async function loadData() {
    const tbody = document.querySelector("#tabelSiklus tbody");
    if (!tbody) return;

    const q = document.getElementById("filterSiklusQ");
    const status = document.getElementById("filterSiklusStatus");
    const lahan = document.getElementById("filterSiklusLahan");
    const dari = document.getElementById("filterSiklusDari");
    const sampai = document.getElementById("filterSiklusSampai");

    filterSiklus.q = q ? q.value.trim() : "";
    filterSiklus.status = status ? status.value : "";
    filterSiklus.lahan_id = lahan ? lahan.value : "";
    filterSiklus.dari = dari ? dari.value : "";
    filterSiklus.sampai = sampai ? sampai.value : "";

    const params = new URLSearchParams();
    if (filterSiklus.q) params.set("q", filterSiklus.q);
    if (filterSiklus.status) params.set("status", filterSiklus.status);
    if (filterSiklus.lahan_id) params.set("lahan_id", filterSiklus.lahan_id);
    if (filterSiklus.dari) params.set("dari", filterSiklus.dari);
    if (filterSiklus.sampai) params.set("sampai", filterSiklus.sampai);

    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Memuat data siklus...</td></tr>';

    try {
        const response = await fetch(API_SIKLUS + (params.toString() ? "?" + params.toString() : ""));
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal mengambil data siklus.");
        }

        const data = result.data || [];
        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <p class="empty-state">
                            <strong>Belum ada siklus tanam.</strong>
                            Mulai catat tanaman pertama Anda di petak yang sudah dibuat.
                            <button class="btn btn--secondary" type="button" data-modal-open="#modalSiklus">Tambah siklus</button>
                        </p>
                    </td>
                </tr>
            `;
            perbaruiPita([]);
            return;
        }

        let jumlahSiap = 0;
        let teksSiap = "";

        data.forEach((item, index) => {
            const progres = hitungProgres(item);
            const status = labelStatusSiklus(item.status);
            if (progres.siap) {
                jumlahSiap += 1;
                if (!teksSiap) teksSiap = item.nama_tanaman + " di " + (item.nama_lahan || "petak") + ".";
            }

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${index + 1}</td>
                <td>
                    <span class="cell-value">${amanSiklus(item.nama_tanaman)}</span>
                    <span class="row-meta">${amanSiklus(item.nama_lahan || "Petak")}${item.lokasi_lahan ? " · " + amanSiklus(item.lokasi_lahan) : ""}</span>
                </td>
                <td>
                    <span class="cell-value">${namaFase[progres.phase]}</span>
                </td>
                <td>
                    <span class="progress-row">
                        <progress value="${progres.value}" max="${progres.total}" aria-label="Progres ${amanSiklus(item.nama_tanaman)}">${progres.value} dari ${progres.total}</progress>
                        <span class="progress-label">${amanSiklus(progres.label)}</span>
                    </span>
                </td>
                <td class="numeric">${tanggalPanjangSiklus(item.tanggal_panen_estimasi)}</td>
                <td><span class="status status--${status.kelas}">${status.teks}</span></td>
                <td>
                    <div class="action-row">
                        <button class="btn btn--secondary btn--sm" type="button" data-aksi="edit" data-id="${Number(item.id)}">Edit</button>
                        <button class="btn btn--danger btn--sm" type="button" data-aksi="hapus" data-id="${Number(item.id)}">Hapus</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        perbaruiPita(teksSiap ? { jumlah: jumlahSiap, teks: teksSiap } : null);
    } catch (error) {
        console.error("Error loadData:", error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Data siklus gagal dimuat. Coba muat ulang halaman.</td></tr>';
        tampilPesan("Data siklus gagal dimuat.", "gagal");
    }
}

function perbaruiPita(data) {
    const pita = document.getElementById("pitaSiapPanen");
    const teks = document.getElementById("teksSiapPanen");
    if (!pita || !teks) return;

    if (!data) {
        pita.hidden = true;
        teks.textContent = "";
        return;
    }

    teks.textContent = data.jumlah + " petak siap dipanen: " + data.teks;
    pita.hidden = false;
}

async function loadLahanOptions() {
    const select = document.getElementById("siklusLahanId");
    if (!select) return;

    try {
        const response = await fetch("/api/lahan");
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal mengambil data lahan.");
        }

        const data = result.data || null;

        select.innerHTML = '<option value="">Pilih lahan...</option>';
        data.forEach((lahan) => {
            const option = document.createElement("option");
            option.value = lahan.id;
            option.textContent = lahan.nama + " - " + lahan.lokasi;
            select.appendChild(option);
        });
    } catch (error) {
        console.error("Error loadLahanOptions:", error);
        select.innerHTML = '<option value="">Gagal memuat data lahan</option>';
    }
}

async function loadLahanFilterOptions() {
    const select = document.getElementById("filterSiklusLahan");
    if (!select) return;

    try {
        const response = await fetch("/api/lahan");
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal mengambil data lahan.");
        }

        const data = result.data || [];

        select.innerHTML = '<option value="">Semua petak</option>';
        data.forEach((lahan) => {
            const option = document.createElement("option");
            option.value = lahan.id;
            option.textContent = lahan.nama + " - " + lahan.lokasi;
            select.appendChild(option);
        });
    } catch (error) {
        console.error("Error loadLahanFilterOptions:", error);
        select.innerHTML = '<option value="">Gagal memuat data lahan</option>';
    }
}

function tampilKesalahanSiklus(pesan) {
    const kotak = document.getElementById("siklusError");
    if (!kotak) return;
    kotak.textContent = pesan || "";
    kotak.hidden = !pesan;
}

async function simpanData(event) {
    event.preventDefault();

    const id = document.getElementById("siklusId").value;
    const data = {
        nama_tanaman: document.getElementById("siklusNamaTanaman").value.trim(),
        lahan_id: document.getElementById("siklusLahanId").value,
        tanggal_tanam: document.getElementById("siklusTanggalTanam").value,
        tanggal_panen_estimasi: document.getElementById("siklusTanggalPanenEstimasi").value || null,
        status: document.getElementById("siklusStatus").value
    };

    if (!data.nama_tanaman || !data.lahan_id || !data.tanggal_tanam) {
        tampilKesalahanSiklus("Lengkapi nama tanaman, lahan, dan tanggal tanam.");
        return;
    }

    if (data.tanggal_panen_estimasi && data.tanggal_panen_estimasi < data.tanggal_tanam) {
        tampilKesalahanSiklus("Tanggal panen tidak boleh sebelum tanggal tanam.");
        return;
    }

    tampilKesalahanSiklus("");

    const url = id ? `${API_SIKLUS}/${id}` : API_SIKLUS;
    const method = id ? "PUT" : "POST";
    const button = document.querySelector("button[form='formSiklus']");

    if (button) {
        button.disabled = true;
        button.textContent = "Menyimpan...";
    }

    try {
        const response = await fetch(url, {
            method: method,
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal menyimpan data.");
        }

        tutupModal(document.getElementById("modalSiklus"));
        resetFormSiklus();
        tampilPesan(result.message || "Siklus tersimpan.", "sukses");
        await loadData();
    } catch (error) {
        console.error("Error simpanData:", error);
        tampilKesalahanSiklus(error.message);
        tampilPesan(error.message, "gagal");
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = "Simpan siklus";
        }
    }
}

async function editData(id) {
    try {
        const response = await fetch(`${API_SIKLUS}/${id}`);
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal mengambil data siklus.");
        }

        const data = result.data;
        document.getElementById("siklusId").value = data.id;
        document.getElementById("siklusNamaTanaman").value = data.nama_tanaman;
        document.getElementById("siklusLahanId").value = data.lahan_id;
        document.getElementById("siklusTanggalTanam").value = data.tanggal_tanam;
        document.getElementById("siklusTanggalPanenEstimasi").value = data.tanggal_panen_estimasi || "";
        document.getElementById("siklusStatus").value = data.status;
        document.getElementById("modalSiklusTitle").textContent = "Edit Siklus";
        tampilKesalahanSiklus("");
        bukaModal(document.getElementById("modalSiklus"));
    } catch (error) {
        console.error("Error editData:", error);
        tampilPesan(error.message, "gagal");
    }
}

async function hapusData(id) {
    const yakin = await konfirmasiAksi(
        "Siklus ini akan dihapus permanen. Catatan panen yang terhubung bisa kehilangan rujukannya.",
        "Ya, hapus siklus"
    );
    if (!yakin) return;

    try {
        const response = await fetch(`${API_SIKLUS}/${id}`, {
            method: "DELETE",
            headers: { "Accept": "application/json" }
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal menghapus data siklus.");
        }

        tampilPesan(result.message || "Siklus dihapus.", "sukses");
        await loadData();
    } catch (error) {
        console.error("Error hapusData:", error);
        tampilPesan(error.message, "gagal");
    }
}

function resetFormSiklus() {
    document.getElementById("formSiklus").reset();
    document.getElementById("siklusId").value = "";
    document.getElementById("modalSiklusTitle").textContent = "Tambah Siklus";
    tampilKesalahanSiklus("");
}

function initSiklus() {
    const form = document.getElementById("formSiklus");
    if (!form) return;

    form.addEventListener("submit", simpanData);
    loadData();
    loadLahanOptions();
    loadLahanFilterOptions();

    document.getElementById("btnTerapkanSiklus")?.addEventListener("click", loadData);
    document.getElementById("btnResetSiklus")?.addEventListener("click", () => {
        ["filterSiklusQ", "filterSiklusStatus", "filterSiklusLahan", "filterSiklusDari", "filterSiklusSampai"].forEach((id) => {
            const input = document.getElementById(id);
            if (input) input.value = "";
        });
        loadData();
    });

    document.addEventListener("modal:buka", (e) => {
        if (e.detail === "modalSiklus") resetFormSiklus();
    });

    document.querySelector("#tabelSiklus tbody")?.addEventListener("click", (e) => {
        const btn = e.target.closest("button[data-aksi]");
        if (!btn) return;
        const id = Number(btn.dataset.id);
        if (btn.dataset.aksi === "edit") editData(id);
        if (btn.dataset.aksi === "hapus") hapusData(id);
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSiklus);
} else {
    initSiklus();
}



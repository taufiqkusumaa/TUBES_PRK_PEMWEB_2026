// public/assets/js/pekerja.js
// Tugas: AJAX Fetch CRUD Pekerja ke /api/pekerja
// Diisi oleh : Devi Novita

const API_PEKERJA = "/api/pekerja";
const API_PRESENSI = "/api/pekerja/presensi";
let daftarPekerja = [];
let daftarSiklus = [];
let daftarPresensi = [];
const rupiahPekerja = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0
});

function amanPekerja(value) {
    const node = document.createElement("span");
    node.textContent = value == null ? "" : value;
    return node.innerHTML;
}

function formatTanggalPekerja(tanggal) {
    if (!tanggal) return "-";
    const section = tanggal.split("-");
    if (section.length !== 3) return tanggal;
    const d = new Date(tanggal + "T00:00:00");
    if (Number.isNaN(d.getTime())) return tanggal;
    return d.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
}

function tampilKesalahanPekerja(pesan) {
    const kotak = document.getElementById("pekerjaError");
    if (!kotak) return;
    kotak.textContent = pesan || "";
    kotak.hidden = !pesan;
}

async function loadData() {
    const tbody = document.querySelector("#tabelPekerja tbody");
    if (!tbody) return;

    try {
        const response = await fetch(API_PEKERJA);
        const result = await response.json();

        if (result.status === 'success') {
            const dataPekerja = result.data || [];
            daftarPekerja = dataPekerja;
            isiPilihanPekerja();
            renderTabel(dataPekerja);

            const totalUpahEl = document.getElementById("totalUpahPekerja");
            if (totalUpahEl) {
                const totalUpah = result.total_upah || (result.data && result.data.total_upah) || 0;
                totalUpahEl.textContent = rupiahPekerja.format(totalUpah);
            }

            const jumlahEl = document.getElementById("jumlahPekerja");
            if (jumlahEl) jumlahEl.textContent = dataPekerja.length;
        } else {
            throw new Error(result.message || "Gagal memuat data pekerja.");
        }
    } catch (error) {
        console.error("Gagal memuat data pekerja:", error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Data pekerja gagal dimuat. Coba muat ulang halaman.</td></tr>';
        tampilPesan("Data pekerja gagal dimuat.", "gagal");
    }
}

function renderTabel(data) {
    const tbody = document.querySelector("#tabelPekerja tbody");
    if (!tbody) return;

    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7">
                    <p class="empty-state">
                        <strong>Belum ada pekerja.</strong>
                        Tambah pekerja pertama Anda supaya upah harian bisa dihitung.
                        <button class="btn btn--secondary" type="button" data-modal-open="#modalPekerja">Tambah pekerja</button>
                    </p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = data.map((item, index) => {
        const gaji = item.gaji_pokok || item.upah_harian || 0;
        return `
            <tr>
                <td>${index + 1}</td>
                <td><span class="cell-value">${amanPekerja(item.nama)}</span></td>
                <td>${amanPekerja(item.no_telepon || "-")}</td>
                <td>${amanPekerja(item.jabatan || item.peran || "-")}</td>
                <td class="numeric">${rupiahPekerja.format(gaji)}</td>
                <td>${formatTanggalPekerja(item.tanggal_bergabung)}</td>
                <td class="cell-actions">
                    <div class="action-row">
                        <button class="btn btn--secondary btn--sm" type="button" data-aksi="presensi" data-id="${Number(item.id)}">Presensi</button>
                        <details class="more-actions">
                            <summary aria-label="Aksi lain untuk ${amanPekerja(item.nama)}">&#8943;</summary>
                            <div class="action-menu">
                                <button class="btn btn--secondary btn--sm" type="button" data-aksi="edit" data-id="${Number(item.id)}">Edit</button>
                                <button class="btn btn--danger btn--sm" type="button" data-aksi="hapus" data-id="${Number(item.id)}">Hapus</button>
                            </div>
                        </details>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

async function simpanData(event) {
    event.preventDefault();

    const id = document.getElementById("pekerjaId").value;
    const data = {
        nama: document.getElementById("pekerjaNama").value.trim(),
        no_telepon: document.getElementById("pekerjaNoTelepon").value.trim(),
        jabatan: document.getElementById("pekerjaJabatan").value,
        gaji_pokok: document.getElementById("pekerjaGajiPokok").value,
        tanggal_bergabung: document.getElementById("pekerjaTanggalBergabung").value
    };

    if (!data.nama || !data.no_telepon || !data.jabatan || data.gaji_pokok === "" || !data.tanggal_bergabung) {
        tampilKesalahanPekerja("Lengkapi nama, telepon, jabatan, upah, dan tanggal bergabung.");
        return;
    }

    if (Number(data.gaji_pokok) < 0) {
        tampilKesalahanPekerja("Upah harian tidak boleh kurang dari 0.");
        return;
    }

    tampilKesalahanPekerja("");
    const method = id ? "PUT" : "POST";
    const url = id ? `${API_PEKERJA}/${id}` : API_PEKERJA;
    const button = document.querySelector("button[form='formPekerja']");

    if (button) {
        button.disabled = true;
        button.textContent = "Menyimpan...";
    }

    try {
        const response = await fetch(url, {
            method: method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal menyimpan data pekerja.");
        }

        tutupModal(document.getElementById("modalPekerja"));
        resetFormPekerja();
        tampilPesan("Data pekerja tersimpan.", "sukses");
        loadData();
    } catch (error) {
        console.error("Error saat menyimpan data:", error);
        tampilKesalahanPekerja(error.message);
        tampilPesan(error.message, "gagal");
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = "Simpan pekerja";
        }
    }
}

async function editData(id) {
    try {
        const response = await fetch(`${API_PEKERJA}/${id}`);
        const result = await response.json();

        if (result.status === 'success' && result.data) {
            const data = result.data;
            document.getElementById("pekerjaId").value = data.id;
            document.getElementById("pekerjaNama").value = data.nama || "";
            document.getElementById("pekerjaNoTelepon").value = data.no_telepon || "";
            document.getElementById("pekerjaJabatan").value = data.jabatan || data.peran || "";
            document.getElementById("pekerjaGajiPokok").value = data.gaji_pokok || data.upah_harian || 0;
            document.getElementById("pekerjaTanggalBergabung").value = data.tanggal_bergabung || "";
            document.getElementById("modalPekerjaTitle").textContent = "Edit Data Pekerja";
            tampilKesalahanPekerja("");
            bukaModal(document.getElementById("modalPekerja"));
        } else {
            tampilPesan(result.message || "Data pekerja gagal diambil.", "gagal");
        }
    } catch (error) {
        console.error("Gagal mengambil data pekerja untuk diedit:", error);
        tampilPesan("Data pekerja gagal diambil.", "gagal");
    }
}

async function hapusData(id) {
    const yakin = await konfirmasiAksi(
        "Data pekerja ini akan dihapus permanen. Riwayat presensi yang terhubung juga ikut terhapus.",
        "Ya, hapus pekerja"
    );
    if (!yakin) return;

    try {
        const response = await fetch(`${API_PEKERJA}/${id}`, { method: "DELETE" });
        const result = await response.json();

        if (result.status === 'success') {
            tampilPesan("Data pekerja dihapus.", "sukses");
            loadData();
        } else {
            throw new Error(result.message || "Gagal menghapus data pekerja.");
        }
    } catch (error) {
        console.error("Error saat menghapus data:", error);
        tampilPesan(error.message, "gagal");
    }
}

function resetFormPekerja() {
    const form = document.getElementById("formPekerja");
    if (form) form.reset();
    document.getElementById("pekerjaId").value = "";
    document.getElementById("modalPekerjaTitle").textContent = "Tambah Pekerja";
    tampilKesalahanPekerja("");
}

function hariIniPekerja() {
    return new Date().toLocaleDateString("en-CA");
}

function isiPilihanPresensi(selectId, labelKosong, daftar, labelOpsi) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const nilai = select.value;
    select.innerHTML = '<option value="">' + labelKosong + '</option>' +
        daftar.map((item) => '<option value="' + Number(item.id) + '">' + amanPekerja(labelOpsi(item)) + '</option>').join("");
    select.value = nilai;
}

function isiPilihanPekerja() {
    isiPilihanPresensi("filterPresensiPekerja", "Semua pekerja", daftarPekerja, (item) => item.nama);
    isiPilihanPresensi("presensiPekerja", "Pilih pekerja...", daftarPekerja, (item) => item.nama);
}

function isiPilihanSiklus() {
    const labelSiklus = (item) => item.nama_tanaman + " · " + (item.nama_lahan || "Petak");
    isiPilihanPresensi("filterPresensiSiklus", "Semua siklus", daftarSiklus, labelSiklus);
    isiPilihanPresensi("presensiSiklus", "Pilih siklus...", daftarSiklus, labelSiklus);
}

async function muatSiklusPresensi() {
    try {
        const response = await fetch("/api/siklus");
        const result = await response.json();
        daftarSiklus = result.status === 'success' ? (result.data || []) : [];
    } catch (error) {
        console.error("Gagal memuat data siklus:", error);
        daftarSiklus = [];
    }
    isiPilihanSiklus();
}

function tampilKesalahanPresensi(pesan) {
    const kotak = document.getElementById("presensiError");
    if (!kotak) return;
    kotak.textContent = pesan || "";
    kotak.hidden = !pesan;
}

async function loadPresensi() {
    const tbody = document.getElementById("tabelPresensiBody");
    if (!tbody) return;

    const params = new URLSearchParams();
    const tanggal = document.getElementById("filterPresensiTanggal").value;
    const pekerja = document.getElementById("filterPresensiPekerja").value;
    const siklus = document.getElementById("filterPresensiSiklus").value;

    if (tanggal) params.set("tanggal", tanggal);
    if (pekerja) params.set("pekerja_id", pekerja);
    if (siklus) params.set("siklus_id", siklus);

    const query = params.toString();

    try {
        const response = await fetch(API_PRESENSI + (query ? "?" + query : ""));
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal memuat data presensi.");
        }

        daftarPresensi = result.data || [];
        renderPresensi(daftarPresensi);
    } catch (error) {
        console.error("Gagal memuat presensi:", error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Data presensi gagal dimuat. Coba muat ulang halaman.</td></tr>';
    }
}

function renderPresensi(data) {
    const tbody = document.getElementById("tabelPresensiBody");
    if (!tbody) return;

    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7">
                    <p class="empty-state">
                        <strong>Belum ada presensi.</strong>
                        Catat kehadiran pekerja lewat tombol Catat presensi.
                    </p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = data.map((item, index) => {
        const siklus = item.nama_tanaman
            ? amanPekerja(item.nama_tanaman) + (item.nama_lahan ? ' <span class="row-meta">' + amanPekerja(item.nama_lahan) + '</span>' : "")
            : "-";

        return `
            <tr>
                <td>${index + 1}</td>
                <td>${formatTanggalPekerja(item.tanggal)}</td>
                <td><span class="cell-value">${amanPekerja(item.nama_pekerja)}</span></td>
                <td>${amanPekerja(item.jabatan || "-")}</td>
                <td>${siklus}</td>
                <td class="numeric">${rupiahPekerja.format(item.upah_harian || 0)}</td>
                <td>
                    <div class="action-row">
                        <button class="btn btn--secondary btn--sm" type="button" data-aksi="edit-presensi" data-id="${Number(item.id)}">Edit</button>
                        <button class="btn btn--danger btn--sm" type="button" data-aksi="hapus-presensi" data-id="${Number(item.id)}">Hapus</button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function isiUpahDefault() {
    const pekerjaId = document.getElementById("presensiPekerja")?.value;
    const upah = document.getElementById("presensiUpah");
    if (!pekerjaId || !upah) return;

    const pekerja = daftarPekerja.find((item) => String(item.id) === String(pekerjaId));
    const gaji = pekerja ? Number(pekerja.gaji_pokok || pekerja.upah_harian || 0) : 0;
    upah.value = gaji > 0 ? gaji : "";
}

function resetFormPresensi() {
    const form = document.getElementById("formPresensi");
    if (form) form.reset();
    document.getElementById("presensiId").value = "";
    document.getElementById("modalPresensiTitle").textContent = "Catat presensi";
    tampilKesalahanPresensi("");
}

function bukaFormPresensi(item) {
    resetFormPresensi();

    if (item) {
        document.getElementById("presensiId").value = item.id;
        document.getElementById("presensiPekerja").value = String(item.pekerja_id);
        document.getElementById("presensiTanggal").value = item.tanggal || "";
        document.getElementById("presensiSiklus").value = String(item.siklus_id);
        document.getElementById("presensiUpah").value = item.upah_harian;
        document.getElementById("modalPresensiTitle").textContent = "Edit presensi";
    } else {
        document.getElementById("presensiTanggal").value = hariIniPekerja();
        isiUpahDefault();
    }

    bukaModal(document.getElementById("modalPresensi"));
}

async function simpanPresensi(event) {
    event.preventDefault();

    const id = document.getElementById("presensiId").value;
    const data = {
        pekerja_id: document.getElementById("presensiPekerja").value,
        tanggal: document.getElementById("presensiTanggal").value,
        siklus_id: document.getElementById("presensiSiklus").value,
        upah_harian: document.getElementById("presensiUpah").value
    };

    if (!data.pekerja_id || !data.tanggal || !data.siklus_id || data.upah_harian === "") {
        tampilKesalahanPresensi("Pilih pekerja dan siklus, lalu isi tanggal serta upah harian.");
        return;
    }

    if (Number(data.upah_harian) < 0) {
        tampilKesalahanPresensi("Upah harian tidak boleh kurang dari 0.");
        return;
    }

    tampilKesalahanPresensi("");
    const button = document.querySelector("button[form='formPresensi']");

    if (button) {
        button.disabled = true;
        button.textContent = "Menyimpan...";
    }

    try {
        const response = await fetch(id ? `${API_PRESENSI}/${id}` : API_PRESENSI, {
            method: id ? "PUT" : "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal menyimpan presensi.");
        }

        tutupModal(document.getElementById("modalPresensi"));
        resetFormPresensi();
        tampilPesan("Presensi pekerja tersimpan.", "sukses");
        loadPresensi();
        loadData();
    } catch (error) {
        console.error("Error saat menyimpan presensi:", error);
        tampilKesalahanPresensi(error.message);
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = "Simpan presensi";
        }
    }
}

async function hapusPresensi(id) {
    const yakin = await konfirmasiAksi(
        "Presensi ini akan dihapus permanen dan total upah ikut berkurang.",
        "Ya, hapus presensi"
    );
    if (!yakin) return;

    try {
        const response = await fetch(`${API_PRESENSI}/${id}`, { method: "DELETE" });
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || "Gagal menghapus presensi.");
        }

        tampilPesan("Presensi pekerja dihapus.", "sukses");
        loadPresensi();
        loadData();
    } catch (error) {
        console.error("Error saat menghapus presensi:", error);
        tampilPesan(error.message, "gagal");
    }
}

function lihatPresensiPekerja(id) {
    const filterPekerja = document.getElementById("filterPresensiPekerja");
    if (!filterPekerja) return;

    document.getElementById("filterPresensiTanggal").value = "";
    document.getElementById("filterPresensiSiklus").value = "";
    filterPekerja.value = String(id);
    loadPresensi();
    document.getElementById("bagianPresensi").scrollIntoView({ behavior: "smooth", block: "start" });
}

function initPekerja() {
    const form = document.getElementById("formPekerja");
    if (form) form.addEventListener("submit", simpanData);

    const formPresensi = document.getElementById("formPresensi");
    if (formPresensi) formPresensi.addEventListener("submit", simpanPresensi);

    document.getElementById("btnTambahPresensi")?.addEventListener("click", () => bukaFormPresensi());
    document.getElementById("btnTerapkanPresensi")?.addEventListener("click", loadPresensi);
    document.getElementById("btnResetPresensi")?.addEventListener("click", () => {
        ["filterPresensiTanggal", "filterPresensiPekerja", "filterPresensiSiklus"].forEach((id) => {
            const input = document.getElementById(id);
            if (input) input.value = "";
        });
        loadPresensi();
    });
    document.getElementById("presensiPekerja")?.addEventListener("change", isiUpahDefault);

    loadData();
    muatSiklusPresensi();
    loadPresensi();

    document.addEventListener("modal:buka", (e) => {
        if (e.detail === "modalPekerja") resetFormPekerja();
    });

    document.querySelector("#tabelPekerja tbody")?.addEventListener("click", (e) => {
        const button = e.target.closest("button[data-aksi]");
        if (!button) return;
        const id = Number(button.dataset.id);
        if (button.dataset.aksi === "edit") editData(id);
        if (button.dataset.aksi === "hapus") hapusData(id);
        if (button.dataset.aksi === "presensi") lihatPresensiPekerja(id);
    });

    document.getElementById("tabelPresensiBody")?.addEventListener("click", (e) => {
        const button = e.target.closest("button[data-aksi]");
        if (!button) return;
        const id = Number(button.dataset.id);
        if (button.dataset.aksi === "edit-presensi") {
            const item = daftarPresensi.find((data) => Number(data.id) === id);
            if (item) bukaFormPresensi(item);
        }
        if (button.dataset.aksi === "hapus-presensi") hapusPresensi(id);
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPekerja);
} else {
    initPekerja();
}



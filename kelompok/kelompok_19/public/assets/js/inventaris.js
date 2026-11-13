// public/assets/js/inventaris.js
// Tugas: AJAX Fetch CRUD Inventaris ke /api/inventaris

const API_INVENTARIS = "/api/inventaris";
const API_SIKLUS = "/api/siklus";
const BATAS_STOK_MINIMUM = 20; // shortcut: skema belum punya kolom stok minimum; naikkan ke data nyata saat kolom tersedia
const BATAS_STOK_SEGERA = 5;

let daftarInventaris = [];
const angkaInventaris = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 });
const rupiahInventaris = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0
});

function amanInventaris(value) {
    const node = document.createElement("span");
    node.textContent = value == null ? "" : value;
    return node.innerHTML;
}

function hariIniInventaris() {
    return new Date().toLocaleDateString("en-CA");
}

function ikonStatus(kelas) {
    if (kelas === "aman") {
        return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>';
    }
    if (kelas === "warning") {
        return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2.5 20h19z"/><path d="M12 9v5M12 17h.01"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3h8l5 5v8l-5 5H8l-5-5V8z"/><path d="M12 8v5M12 16h.01"/></svg>';
}

function statusStok(stok) {
    if (stok <= BATAS_STOK_SEGERA) {
        return { kelas: "urgent", teks: "Segera tindak", pesan: "Tinggal " + angkaInventaris.format(stok) + " di gudang. Tambah stok sekarang." };
    }
    if (stok <= BATAS_STOK_MINIMUM) {
        return { kelas: "warning", teks: "Perlu perhatian", pesan: "Stok menipis. Siapkan pembelian berikutnya." };
    }
    return { kelas: "aman", teks: "Aman", pesan: "" };
}

function tampilKesalahanInventaris(pesan) {
    const kotak = document.getElementById("inventarisError");
    if (!kotak) return;
    kotak.textContent = pesan || "";
    kotak.hidden = !pesan;
}

function tampilKesalahanTransaksi(pesan) {
    const kotak = document.getElementById("transaksiError");
    if (!kotak) return;
    kotak.textContent = pesan || "";
    kotak.hidden = !pesan;
}

async function loadData() {
    const tbody = document.querySelector("#tabelInventaris tbody");
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Memuat data inventaris...</td></tr>';

    try {
        const response = await fetch(API_INVENTARIS);
        const result = await response.json();
        const data = Array.isArray(result.data) ? result.data : [];

        daftarInventaris = data;
        tbody.innerHTML = "";

        if (result.status !== "success" || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <p class="empty-state">
                            <strong>Belum ada barang di gudang.</strong>
                            Catat bibit, pupuk, atau pestisida pertama Anda.
                            <button class="btn btn--secondary" type="button" data-modal-open="#modalInventaris">Tambah item</button>
                        </p>
                    </td>
                </tr>
            `;
            return;
        }

        data.slice().sort((a, b) => (Number(a.stok) || 0) - (Number(b.stok) || 0)).forEach((item, index) => {
            const stok = Number(item.stok) || 0;
            const maksimum = Math.max(100, stok);
            const harga = Number(item.harga_satuan) || 0;
            const status = statusStok(stok);

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${index + 1}</td>
                <td><span class="cell-value">${amanInventaris(item.nama)}</span></td>
                <td>${amanInventaris(item.kategori)}</td>
                <td>
                    <span class="stock-cell">
                        <span class="cell-value">${angkaInventaris.format(stok)} ${amanInventaris(item.satuan)}</span>
                        <meter min="0" max="${maksimum}" low="${BATAS_STOK_MINIMUM}" high="${BATAS_STOK_MINIMUM * 3}" optimum="${maksimum}" value="${stok}" aria-label="Stok ${amanInventaris(item.nama)}">${stok} ${amanInventaris(item.satuan)}</meter>
                        <span class="status status--${status.kelas}">${ikonStatus(status.kelas)}${status.teks}</span>
                        ${status.pesan ? '<span class="text-warning">' + status.pesan + "</span>" : ""}
                    </span>
                </td>
                <td class="numeric">${rupiahInventaris.format(harga)}</td>
                <td class="cell-actions">
                    <div class="action-row">
                        <button class="btn btn--secondary btn--sm" type="button" data-aksi="transaksi" data-id="${Number(item.id)}">Catat transaksi</button>
                        <details class="more-actions">
                            <summary aria-label="Aksi lain untuk ${amanInventaris(item.nama)}">&#8943;</summary>
                            <div class="action-menu">
                                <button class="btn btn--secondary btn--sm" type="button" data-aksi="edit" data-id="${Number(item.id)}">Edit</button>
                                <button class="btn btn--danger btn--sm" type="button" data-aksi="hapus" data-id="${Number(item.id)}">Hapus</button>
                            </div>
                        </details>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Data inventaris gagal dimuat. Coba muat ulang halaman.</td></tr>';
        tampilPesan("Data inventaris gagal dimuat.", "gagal");
        console.error("Gagal memuat data:", error);
    }
}

function resetFormInventaris() {
    document.getElementById("formInventaris").reset();
    document.getElementById("inventarisId").value = "";
    document.getElementById("modalInventarisTitle").textContent = "Tambah Item";
    tampilKesalahanInventaris("");
}

document.getElementById("formInventaris")?.addEventListener("submit", async function simpanData(event) {
    event.preventDefault();

    const id = document.getElementById("inventarisId").value;
    const data = {
        nama: document.getElementById("inventarisNama").value.trim(),
        kategori: document.getElementById("inventarisKategori").value,
        stok: document.getElementById("inventarisStok").value,
        satuan: document.getElementById("inventarisSatuan").value.trim(),
        harga_satuan: document.getElementById("inventarisHargaSatuan").value
    };

    if (!data.nama || !data.kategori || !data.satuan || data.stok === "" || data.harga_satuan === "") {
        tampilKesalahanInventaris("Lengkapi nama, kategori, stok, satuan, dan harga.");
        return;
    }

    tampilKesalahanInventaris("");
    const url = id ? `${API_INVENTARIS}/${id}` : API_INVENTARIS;
    const button = document.querySelector("button[form='formInventaris']");
    if (button) button.disabled = true;

    try {
        const response = await fetch(url, {
            method: id ? "PUT" : "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (!response.ok || result.status !== "success") {
            throw new Error(result.message || "Gagal menyimpan item.");
        }

        tutupModal(document.getElementById("modalInventaris"));
        resetFormInventaris();
        tampilPesan(result.message || "Item tersimpan.", "sukses");
        loadData();
    } catch (error) {
        console.error("Gagal menyimpan data:", error);
        tampilKesalahanInventaris(error.message);
        tampilPesan(error.message, "gagal");
    } finally {
        if (button) button.disabled = false;
    }
});

async function editData(id) {
    try {
        const response = await fetch(`${API_INVENTARIS}/${id}`);
        const result = await response.json();

        if (result.status === "success") {
            const item = result.data;
            document.getElementById("inventarisId").value = item.id;
            document.getElementById("inventarisNama").value = item.nama;
            document.getElementById("inventarisKategori").value = item.kategori;
            document.getElementById("inventarisStok").value = item.stok;
            document.getElementById("inventarisSatuan").value = item.satuan;
            document.getElementById("inventarisHargaSatuan").value = item.harga_satuan;
            document.getElementById("modalInventarisTitle").textContent = "Edit Item";
            tampilKesalahanInventaris("");
            bukaModal(document.getElementById("modalInventaris"));
        } else {
            tampilPesan(result.message || "Data item gagal diambil.", "gagal");
        }
    } catch (error) {
        console.error("Gagal mengambil data edit:", error);
        tampilPesan("Data item gagal diambil.", "gagal");
    }
}

async function hapusData(id) {
    const yakin = await konfirmasiAksi(
        "Item ini akan dihapus permanen. Seluruh riwayat transaksi barang ini juga ikut terhapus.",
        "Ya, hapus item"
    );
    if (!yakin) return;

    try {
        const response = await fetch(`${API_INVENTARIS}/${id}`, { method: "DELETE" });
        const result = await response.json();

        if (!response.ok || result.status !== "success") {
            throw new Error(result.message || "Gagal menghapus item.");
        }

        tampilPesan(result.message || "Item dihapus.", "sukses");
        loadData();
    } catch (error) {
        console.error("Gagal menghapus data:", error);
        tampilPesan(error.message, "gagal");
    }
}

// ========================================================
// LOGIKA TRANSAKSI INVENTARIS (MASUK / KELUAR)
// ========================================================

async function loadSiklusTanam() {
    const select = document.getElementById("transaksiSiklusId");
    if (!select) return;

    try {
        const response = await fetch(API_SIKLUS);
        const result = await response.json();
        select.innerHTML = '<option value="">Pilih siklus tanam...</option>';

        if (result.status === 'success' && Array.isArray(result.data)) {
            result.data.forEach((siklus) => {
                if (siklus.status === "Aktif") {
                    const option = document.createElement("option");
                    option.value = siklus.id;
                    option.textContent = siklus.nama_tanaman + (siklus.nama_lahan ? " - " + siklus.nama_lahan : "");
                    select.appendChild(option);
                }
            });
        }
    } catch (error) {
        console.error("Gagal mengambil data siklus:", error);
    }
}

function aturGrupSiklus(jenis) {
    const grup = document.getElementById("grupSiklusTanam");
    const input = document.getElementById("transaksiSiklusId");
    if (!grup || !input) return;

    const keluar = jenis === "Keluar";
    grup.hidden = !keluar;
    input.required = keluar;
    if (!keluar) input.value = "";
}

function bukaTransaksi(id, jenis) {
    const item = daftarInventaris.find((barang) => Number(barang.id) === Number(id));
    if (!item) return;

    jenis = jenis || "";

    const form = document.getElementById("formTransaksi");
    form.reset();
    form.dataset.nama = item.nama;
    form.dataset.satuan = item.satuan;
    document.getElementById("transaksiInventarisId").value = item.id;
    document.getElementById("transaksiNamaItem").value = item.nama;
    document.getElementById("transaksiTanggal").value = hariIniInventaris();
    document.getElementById("transaksiJenis").value = jenis;
    aturGrupSiklus(jenis);
    tampilKesalahanTransaksi("");
    loadSiklusTanam();

    bukaModal(document.getElementById("modalTransaksi"));
    document.getElementById("transaksiJumlah").focus();
}

document.getElementById("transaksiJenis")?.addEventListener("change", function () {
    aturGrupSiklus(this.value);
});

document.getElementById("formTransaksi")?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const form = e.currentTarget;
    const jenis = document.getElementById("transaksiJenis").value;
    const tanggal = document.getElementById("transaksiTanggal").value;
    const jumlah = Number(document.getElementById("transaksiJumlah").value);
    const siklusId = document.getElementById("transaksiSiklusId").value;

    if (!jenis || !tanggal || !jumlah || jumlah <= 0) {
        tampilKesalahanTransaksi("Isi jenis transaksi, tanggal, dan jumlah lebih dari 0.");
        return;
    }

    if (jenis === "Keluar" && !siklusId) {
        tampilKesalahanTransaksi("Pilih siklus tanam untuk pemakaian barang keluar.");
        return;
    }

    tampilKesalahanTransaksi("");

    const data = {
        inventaris_id: document.getElementById("transaksiInventarisId").value,
        jenis: jenis,
        tanggal: tanggal,
        jumlah: jumlah,
        siklus_id: siklusId
    };

    const button = document.querySelector("button[form='formTransaksi']");
    if (button) button.disabled = true;

    try {
        const response = await fetch(`${API_INVENTARIS}/createTransaksi`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (!response.ok || result.status !== "success") {
            throw new Error(result.message || "Transaksi gagal disimpan.");
        }

        tutupModal(document.getElementById("modalTransaksi"));
        tampilPesan(
            "Barang " + jenis.toLowerCase() + " " + angkaInventaris.format(jumlah) + " " + (form.dataset.satuan || "") + " tersimpan.",
            "sukses"
        );
        loadData();
    } catch (error) {
        console.error("Error save transaksi:", error);
        tampilKesalahanTransaksi(error.message);
        tampilPesan(error.message, "gagal");
    } finally {
        if (button) button.disabled = false;
    }
});

document.addEventListener("DOMContentLoaded", () => {
    loadData();
    loadSiklusTanam();

    document.addEventListener("modal:buka", (e) => {
        if (e.detail === "modalInventaris") resetFormInventaris();
    });

    document.querySelector("#tabelInventaris tbody")?.addEventListener("click", (e) => {
        const button = e.target.closest("button[data-aksi]");
        if (!button) return;

        const id = Number(button.dataset.id);
        const menu = button.closest("details");
        if (menu) menu.open = false;

        if (button.dataset.aksi === "transaksi") {
            bukaTransaksi(id, button.dataset.jenis);
        } else if (button.dataset.aksi === "edit") {
            editData(id);
        } else if (button.dataset.aksi === "hapus") {
            hapusData(id);
        }
    });

    document.addEventListener("click", (e) => {
        document.querySelectorAll("#tabelInventaris details.more-actions[open]").forEach((menu) => {
            if (!menu.contains(e.target)) menu.open = false;
        });
    });

    document.addEventListener("keydown", (e) => {
        if (e.key !== "Escape") return;
        tutupMenuAksi();
    });

    document.addEventListener("toggle", (e) => {
        const menu = e.target;
        if (!menu.matches?.("details.more-actions") || !menu.open) return;

        const panel = menu.querySelector(".action-menu");
        const pemicu = menu.querySelector("summary");
        if (!panel || !pemicu) return;

        const kotak = pemicu.getBoundingClientRect();
        panel.style.top = kotak.bottom + 6 + "px";
        panel.style.right = Math.max(8, window.innerWidth - kotak.right) + "px";
    }, true);

    window.addEventListener("scroll", tutupMenuAksi, true);
    window.addEventListener("resize", tutupMenuAksi);
});

function tutupMenuAksi() {
    document.querySelectorAll("#tabelInventaris details.more-actions[open]").forEach((menu) => {
        menu.open = false;
    });
}



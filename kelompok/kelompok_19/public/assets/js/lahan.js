// public/assets/js/lahan.js
// Tugas: AJAX Fetch CRUD Lahan ke /api/lahan

const API_LAHAN = "/api/lahan";
const angkaLahan = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 });

function amanLahan(value) {
    const node = document.createElement("span");
    node.textContent = value == null ? "" : value;
    return node.innerHTML;
}

function loadData() {
    const tbody = document.querySelector("#tabelLahan tbody");
    if (!tbody) return;

    fetch(API_LAHAN)
        .then((res) => res.json())
        .then((data) => {
            const list = (data && data.data) || [];
            const placeholder = document.getElementById("placeholderLahan");
            if (placeholder) placeholder.remove();
            tbody.innerHTML = "";

            if (list.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <p class="empty-state">
                                <strong>Belum ada petak.</strong>
                                Tambah petak pertama Anda supaya siklus tanam bisa dicatat.
                                <button class="btn btn--secondary" type="button" data-modal-open="#modalLahan">Tambah petak</button>
                            </p>
                        </td>
                    </tr>
                `;
                return;
            }

            list.forEach((item, index) => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td><span class="cell-value">${amanLahan(item.nama)}</span></td>
                    <td>${amanLahan(item.lokasi)}</td>
                    <td class="numeric">${angkaLahan.format(Number(item.luas) || 0)}</td>
                    <td>${amanLahan(item.jenis_tanah)}</td>
                    <td>
                        <div class="action-row">
                            <button class="btn btn--secondary btn--sm" type="button" data-aksi="edit" data-id="${Number(item.id)}">Edit</button>
                            <button class="btn btn--danger btn--sm" type="button" data-aksi="hapus" data-id="${Number(item.id)}">Hapus</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch((err) => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Data lahan gagal dimuat. Coba muat ulang halaman.</td></tr>';
            console.error("Gagal memuat data lahan:", err);
        });
}

function simpanData(event) {
    event.preventDefault();
    const id = document.getElementById("lahanId").value;
    const data = {
        nama: document.getElementById("lahanNama").value.trim(),
        lokasi: document.getElementById("lahanLokasi").value.trim(),
        luas: document.getElementById("lahanLuas").value,
        jenis_tanah: document.getElementById("lahanJenisTanah").value
    };
    const url = id ? `${API_LAHAN}/${id}` : API_LAHAN;
    const method = id ? "PUT" : "POST";
    const button = document.getElementById("btnSimpanLahan");

    if (button) button.disabled = true;

    fetch(url, {
        method: method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
        .then(async (res) => {
            const hasil = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(hasil.message || "Gagal menyimpan petak.");
            return hasil;
        })
        .then((hasil) => {
            tutupModal(document.getElementById("modalLahan"));
            resetFormLahan();
            tampilPesan(hasil.message || "Petak tersimpan.", "sukses");
            loadData();
        })
        .catch((err) => {
            tampilPesan(err.message || "Gagal menyimpan petak.", "gagal");
            console.error("Gagal menyimpan data lahan:", err);
        })
        .finally(() => {
            if (button) button.disabled = false;
        });
}

function editData(id) {
    fetch(`${API_LAHAN}/${id}`)
        .then((res) => res.json())
        .then((item) => {
            const data = item.data || null;
            document.getElementById("lahanId").value = data.id;
            document.getElementById("lahanNama").value = data.nama;
            document.getElementById("lahanLokasi").value = data.lokasi;
            document.getElementById("lahanLuas").value = data.luas;
            document.getElementById("lahanJenisTanah").value = data.jenis_tanah;
            document.getElementById("modalLahanTitle").textContent = "Edit Petak";
            bukaModal(document.getElementById("modalLahan"));
        })
        .catch((err) => {
            tampilPesan("Data petak gagal diambil.", "gagal");
            console.error("Gagal mengambil data lahan:", err);
        });
}

async function hapusData(id) {
    const yakin = await konfirmasiAksi(
        "Petak ini akan dihapus permanen. Siklus tanam yang memakai petak ini juga kehilangan rujukannya.",
        "Ya, hapus petak"
    );
    if (!yakin) return;

    fetch(`${API_LAHAN}/${id}`, { method: "DELETE" })
        .then(async (res) => {
            const hasil = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(hasil.message || "Gagal menghapus petak.");
            return hasil;
        })
        .then((hasil) => {
            tampilPesan(hasil.message || "Petak dihapus.", "sukses");
            loadData();
        })
        .catch((err) => {
            tampilPesan(err.message || "Gagal menghapus petak.", "gagal");
            console.error("Gagal menghapus data lahan:", err);
        });
}

function resetFormLahan() {
    document.getElementById("formLahan").reset();
    document.getElementById("lahanId").value = "";
    document.getElementById("modalLahanTitle").textContent = "Tambah Petak";
}

document.getElementById("formLahan").addEventListener("submit", simpanData);

// Tombol "Tambah" (header maupun state kosong) menyiapkan form sebelum modal dibuka.
document.addEventListener("modal:buka", (e) => {
    if (e.detail === "modalLahan") resetFormLahan();
});

document.querySelector("#tabelLahan tbody")?.addEventListener("click", (e) => {
    const button = e.target.closest("button[data-aksi]");
    if (!button) return;
    const id = Number(button.dataset.id);
    if (button.dataset.aksi === "edit") editData(id);
    if (button.dataset.aksi === "hapus") hapusData(id);
});

document.addEventListener("DOMContentLoaded", loadData);


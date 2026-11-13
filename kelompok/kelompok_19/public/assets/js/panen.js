// public/assets/js/panen.js
// Tugas: AJAX Fetch CRUD Panen ke /api/panen

const API_PANEN = "/api/panen";
const fmt = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 });

function el(id) {
    return document.getElementById(id);
}

function escapeHtml(value) {
    const node = document.createElement("span");
    node.textContent = value ?? "";
    return node.innerHTML;
}

function hariIni() {
    return new Date().toLocaleDateString("en-CA");
}

function formatTanggal(tanggal) {
    if (!tanggal) return "-";
    const d = new Date(tanggal + "T00:00:00");
    if (Number.isNaN(d.getTime())) return tanggal;
    return d.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
}

async function request(url, options = {}) {
    const res = await fetch(url, {
        headers: { "Content-Type": "application/json" },
        ...options
    });

    let json;
    try {
        json = await res.json();
    } catch (e) {
        throw new Error("Respons server tidak valid.");
    }

    if (!res.ok || json.status !== "success") {
        throw new Error(json.message || json.error || "Terjadi kesalahan.");
    }
    return json;
}

function urlDenganFilter(path = "") {
    const params = new URLSearchParams();
    const dari = el("filterDari") ? el("filterDari").value : "";
    const sampai = el("filterSampai") ? el("filterSampai").value : "";
    if (dari) params.set("dari", dari);
    if (sampai) params.set("sampai", sampai);
    const qs = params.toString();
    return API_PANEN + path + (qs ? "?" + qs : "");
}

function tampilError(pesan) {
    const box = el("panenError");
    if (!box) return;
    box.textContent = pesan || "";
    box.hidden = !pesan;
}

function renderTabel(rows) {
    const tbody = el("tabelPanenBody");
    if (!rows.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7">
                    <p class="empty-state">
                        <strong>Belum ada panen pada periode ini.</strong>
                        Isi form di atas untuk mencatat hasil petik pertama.
                        <button class="btn btn--secondary" type="button" data-aksi="fokus">Catat panen</button>
                    </p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = rows.map((r, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><span class="cell-value">${escapeHtml(r.nama_tanaman)}</span><span class="row-meta">${escapeHtml(r.nama_lahan)}</span></td>
            <td>${escapeHtml(formatTanggal(r.tanggal_panen))}</td>
            <td class="numeric">${fmt.format(Number(r.jumlah_kg))}</td>
            <td>${escapeHtml(r.kualitas)}</td>
            <td>${escapeHtml(r.catatan || "-")}</td>
            <td class="no-print">
                <div class="action-row">
                    <button type="button" class="btn btn--secondary btn--sm" data-aksi="edit" data-id="${Number(r.id)}">Edit</button>
                    <button type="button" class="btn btn--danger btn--sm" data-aksi="hapus" data-id="${Number(r.id)}">Hapus</button>
                </div>
            </td>
        </tr>`).join("");
}

function renderRingkasan(r) {
    const totalKg = Number(r.total_kg) || 0;
    el("totalPanenKg").textContent = fmt.format(totalKg) + " kg";
    el("totalPanenTon").textContent = fmt.format(totalKg / 1000) + " ton";
    el("jumlahCatatan").textContent = r.jumlah_catatan || 0;

    const dari = el("filterDari").value;
    const sampai = el("filterSampai").value;
    let periode = "Semua catatan panen.";
    if (dari && sampai) periode = formatTanggal(dari) + " sampai " + formatTanggal(sampai) + ".";
    else if (dari) periode = "Sejak " + formatTanggal(dari) + ".";
    else if (sampai) periode = "Sampai " + formatTanggal(sampai) + ".";
    el("periodeRekap").textContent = periode;

    const tbody = el("tabelRekapBody");
    const perTanaman = Array.isArray(r.per_tanaman) ? r.per_tanaman : [];
    if (!perTanaman.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Belum ada data pada periode ini.</td></tr>';
        return;
    }

    tbody.innerHTML = perTanaman.map((t) => `
        <tr>
            <td>${escapeHtml(t.nama_tanaman)}</td>
            <td class="numeric">${Number(t.jumlah_catatan)}</td>
            <td class="numeric">${fmt.format(Number(t.total_kg))}</td>
        </tr>`).join("");
}

async function loadData() {
    try {
        const json = await request(urlDenganFilter());
        renderTabel(json.data);
        renderRingkasan(json.ringkasan);
    } catch (err) {
        el("tabelPanenBody").innerHTML =
            '<tr><td colspan="7" class="text-center text-danger">' + escapeHtml(err.message) + "</td></tr>";
        tampilPesan(err.message, "gagal");
    }
}

async function loadSiklusOptions() {
    try {
        const json = await request(API_PANEN + "?mode=siklus");
        const select = el("panenSiklusId");
        select.innerHTML = '<option value="">Pilih siklus...</option>' +
            json.data.map((s) =>
                `<option value="${Number(s.id)}">${escapeHtml(s.nama_tanaman)} - ${escapeHtml(s.nama_lahan)} (${escapeHtml(s.status)})</option>`
            ).join("");
    } catch (err) {
        tampilError("Gagal memuat list siklus: " + err.message);
    }
}

function resetFormPanen() {
    el("formPanen").reset();
    el("panenId").value = "";
    el("panenTanggalPanen").value = hariIni();
    el("btnSimpan").textContent = "Simpan panen";
    el("modalPanenTitle").textContent = "Catat panen baru";
    tampilError("");
}

function bukaFormPanen() {
    bukaModal(el("modalPanen"));
    el("panenSiklusId").focus();
}

async function editData(id) {
    try {
        const json = await request(`${API_PANEN}/${id}`);
        const d = json.data;

        el("formPanen").reset();
        el("panenId").value = d.id;
        el("panenSiklusId").value = d.siklus_id;
        el("panenTanggalPanen").value = d.tanggal_panen;
        el("panenJumlahKg").value = d.jumlah_kg;
        el("panenKualitas").value = d.kualitas;
        el("panenCatatan").value = d.catatan || "";
        el("btnSimpan").textContent = "Simpan perubahan";
        el("modalPanenTitle").textContent = "Edit catatan panen";
        tampilError("");
        bukaFormPanen();
    } catch (err) {
        tampilPesan(err.message, "gagal");
    }
}

async function simpanData(event) {
    event.preventDefault();

    const id = el("panenId").value;
    const jumlah = Number(el("panenJumlahKg").value);
    const payload = {
        siklus_id: el("panenSiklusId").value,
        tanggal_panen: el("panenTanggalPanen").value,
        jumlah_kg: el("panenJumlahKg").value,
        kualitas: el("panenKualitas").value,
        catatan: el("panenCatatan").value
    };

    if (!payload.siklus_id || !payload.tanggal_panen || !payload.jumlah_kg) {
        tampilError("Pilih siklus, isi tanggal, dan berat panen.");
        return;
    }
    if (!jumlah || jumlah <= 0) {
        tampilError("Berat harus lebih dari 0 kg.");
        return;
    }

    tampilError("");
    el("btnSimpan").disabled = true;

    try {
        await request(id ? `${API_PANEN}/${id}` : API_PANEN, {
            method: id ? "PUT" : "POST",
            body: JSON.stringify(payload)
        });
        tutupModal(el("modalPanen"));
        tampilPesan("Panen " + fmt.format(jumlah) + " kg tersimpan.", "sukses");
        resetFormPanen();
        await loadData();
    } catch (err) {
        tampilError(err.message);
        tampilPesan(err.message, "gagal");
    } finally {
        el("btnSimpan").disabled = false;
    }
}

async function hapusData(id) {
    const yakin = await konfirmasiAksi(
        "Catatan panen ini akan dihapus permanen dan total panen langsung berubah.",
        "Ya, hapus catatan"
    );
    if (!yakin) return;

    try {
        await request(`${API_PANEN}/${id}`, { method: "DELETE" });
        tampilPesan("Catatan panen dihapus.", "sukses");
        await loadData();
    } catch (err) {
        tampilPesan(err.message, "gagal");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    resetFormPanen();
    loadSiklusOptions();
    loadData();

    el("formPanen").addEventListener("submit", simpanData);
    el("btnTerapkan").addEventListener("click", loadData);
    el("btnReset").addEventListener("click", () => {
        el("filterDari").value = "";
        el("filterSampai").value = "";
        loadData();
    });
    el("btnCetak").addEventListener("click", () => window.print());

    document.addEventListener("modal:buka", (e) => {
        if (e.detail === "modalPanen") resetFormPanen();
    });

    el("tabelPanenBody").addEventListener("click", (e) => {
        const btn = e.target.closest("button[data-aksi]");
        if (!btn) return;
        const id = Number(btn.dataset.id);
        if (btn.dataset.aksi === "edit") editData(id);
        if (btn.dataset.aksi === "hapus") hapusData(id);
        if (btn.dataset.aksi === "fokus") bukaFormPanen();
    });
});
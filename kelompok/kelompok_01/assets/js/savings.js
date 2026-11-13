
const targetUrl = '../backend/tabungan/tabungan_process.php';
const setoranUrl = '../backend/tabungan/setoran_process.php';

const modalContainer = document.createElement('div');
modalContainer.id = 'savingsModal';
document.body.appendChild(modalContainer);

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
}

function showModal(content) {
    modalContainer.innerHTML = `
        <div class="savings-modal-overlay">
            <div class="savings-modal">
                ${content}
            </div>
        </div>
    `;
    modalContainer.style.display = 'block';

    modalContainer.querySelector('.savings-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
}

function closeModal() {
    modalContainer.style.display = 'none';
    modalContainer.innerHTML = '';
}

async function getTargets() {
    const response = await fetch(`${targetUrl}?aksi=daftar`);
    if (!response.ok) throw new Error('Gagal mengambil data target.');
    return await response.json();
}

function showTargetForm(target = null) {
    const isEdit = target !== null;

    showModal(`
        <div class="savings-modal-header">
            <h3>${isEdit ? 'Edit Target Tabungan' : 'Tambah Target Tabungan'}</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form action="${targetUrl}" method="POST" class="savings-form">
            <input type="hidden" name="aksi" value="${isEdit ? 'ubah' : 'tambah'}">
            ${isEdit ? `<input type="hidden" name="id" value="${target.id_target}">` : ''}
            <label>Nama Target</label>
            <input type="text" name="nama" value="${isEdit ? target.nama_target : ''}" placeholder="Contoh: Beli Laptop" required>

            <label>Nominal Target</label>
            <input type="number" name="nominal" min="1" value="${isEdit ? target.target_nominal : ''}" placeholder="Masukkan nominal" required>

            ${isEdit ? '' : `
                <label>Tanggal Mulai</label>
                <input type="date" name="mulai" required>
            `}

            <label>Tanggal Target</label>
            <input type="date" name="target" value="${isEdit ? target.tanggal_target : ''}" required>

            <label>Catatan</label>
            <textarea name="catatan" placeholder="Catatan tambahan">${isEdit ? target.catatan || '' : ''}</textarea>

            <div class="modal-actions">
                <button type="button" class="btn-cancel">Batal</button>
                <button type="submit" class="btn-save">${isEdit ? 'Simpan Perubahan' : 'Tambah Target'}</button>
            </div>
        </form>
    `);

    modalContainer.querySelector('.modal-close').addEventListener('click', closeModal);
    modalContainer.querySelector('.btn-cancel').addEventListener('click', closeModal);
}

function showDepositForm(id, name) {
    showModal(`
        <div class="savings-modal-header">
            <h3>Tambah Setoran</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <p class="modal-description">Target: ${escapeHTML(name)}</p>
        <form action="${setoranUrl}" method="POST" class="savings-form">
            <input type="hidden" name="aksi" value="tambah">
            <input type="hidden" name="id_target" value="${id}">

            <label>Jumlah Setoran</label>
            <input type="number" name="jumlah" min="1" placeholder="Masukkan jumlah setoran" required>

            <label>Tanggal Setoran</label>
            <input type="date" name="tanggal" value="${new Date().toISOString().slice(0, 10)}" required>

            <label>Keterangan</label>
            <textarea name="keterangan" placeholder="Keterangan setoran"></textarea>

            <div class="modal-actions">
                <button type="button" class="btn-cancel">Batal</button>
                <button type="submit" class="btn-save">Simpan Setoran</button>
            </div>
        </form>
    `);

    modalContainer.querySelector('.modal-close').addEventListener('click', closeModal);
    modalContainer.querySelector('.btn-cancel').addEventListener('click', closeModal);
}

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
}

document.getElementById('btnTambahTarget')?.addEventListener('click', function() {
    showTargetForm();
});

document.getElementById('targetList')?.addEventListener('click', async function(e) {
    const editButton = e.target.closest('.btn-edit-target');
    const deleteButton = e.target.closest('.btn-delete-target');
    const depositButton = e.target.closest('.btn-add-deposit');

    if (editButton) {
        try {
            const targets = await getTargets();
            const target = targets.find(item => Number(item.id_target) === Number(editButton.dataset.id));

            if (target) {
                showTargetForm(target);
            } else {
                alert('Data target tidak ditemukan.');
            }
        } catch (error) {
            alert(error.message);
        }
    }

    if (deleteButton) {
        if (confirm('Yakin ingin menghapus target tabungan ini?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = targetUrl;
            form.innerHTML = `
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="${deleteButton.dataset.id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    if (depositButton) {
        showDepositForm(depositButton.dataset.id, depositButton.dataset.name);
    }
});
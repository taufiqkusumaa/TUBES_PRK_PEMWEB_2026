/**
 * File: public/assets/js/transaksi.js
 * Deskripsi: Mengelola interaksi sisi klien (Frontend) untuk:
 * 1. Preview foto kendala sebelum di-upload ke public/uploads/
 * 2. Submit form pemesanan servis baru via Fetch API (AJAX) ke public/api.php
 * 3. Pemilihan baris riwayat servis untuk mengganti tampilan Stepper Tracking
 * 4. Update progres Stepper (Pending -> Antre -> Dikerjakan -> Siap Diambil)
 */

document.addEventListener('DOMContentLoaded', () => {
    const formBooking = document.getElementById('formBookingServis');
    const selectPaket = document.getElementById('paket_id');
    const infoPaketBox = document.getElementById('infoPaketBox');
    const inputFoto = document.getElementById('foto_kendala');
    const previewContainer = document.getElementById('previewContainer');
    const fotoPreview = document.getElementById('fotoPreview');
    const tbodyRiwayat = document.getElementById('tbodyRiwayat');
    const alertContainer = document.getElementById('alertContainer');
    const activeTransaksiInput = document.getElementById('activeTransaksiId');

    const urutanTahap = ['Pending', 'Antre', 'Dikerjakan', 'Siap Diambil'];

    /**
     * Menampilkan pesan notifikasi Bootstrap Alert di bagian atas halaman
     */
    function showAlert(message, type = 'success') {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm border-0 rounded-3" role="alert">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertContainer.innerHTML = '';
        alertContainer.appendChild(wrapper);
    }

    /**
     * Menampilkan informasi deskripsi & estimasi waktu saat paket layanan dipilih
     */
    if (selectPaket) {
        selectPaket.addEventListener('change', () => {
            const opt = selectPaket.options[selectPaket.selectedIndex];
            const deskripsi = opt.getAttribute('data-deskripsi');
            const estimasi = opt.getAttribute('data-estimasi');

            if (selectPaket.value && deskripsi) {
                infoPaketBox.innerHTML = `<i class="bi bi-info-circle me-1"></i> ${deskripsi} (Estimasi pengerjaan: <strong>${estimasi} menit</strong>)`;
                infoPaketBox.classList.remove('d-none');
            } else {
                infoPaketBox.classList.add('d-none');
            }
        });
    }

    /**
     * Menampilkan pratinjau (preview) foto kendala sebelum dikirim ke server
     */
    if (inputFoto) {
        inputFoto.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) {
                previewContainer.classList.add('d-none');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showAlert('Ukuran foto melebihi batas maksimal 5 MB.', 'danger');
                inputFoto.value = '';
                previewContainer.classList.add('d-none');
                return;
            }

            const reader = new FileReader();
            reader.onload = (ev) => {
                fotoPreview.src = ev.target.result;
                previewContainer.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    /**
     * Memperbarui UI Stepper Tracking (Pending -> Antre -> Dikerjakan -> Siap Diambil)
     */
    function renderStepperProgress(kodeWo, pelatNomor, statusAktif, catatanMekanik) {
        const trackerKodeWo = document.getElementById('trackerKodeWo');
        const trackerPelat = document.getElementById('trackerPelat');
        const trackerCatatan = document.getElementById('trackerCatatan');
        const stepperItems = document.querySelectorAll('#stepperWrapper .stepper-item');

        if (trackerKodeWo) trackerKodeWo.textContent = kodeWo;
        if (trackerPelat) trackerPelat.textContent = pelatNomor;
        if (trackerCatatan) trackerCatatan.textContent = catatanMekanik || 'Menunggu pembaruan dari mekanik.';

        const activeIndex = urutanTahap.indexOf(statusAktif);

        stepperItems.forEach((item) => {
            const stepName = item.getAttribute('data-step');
            const idx = urutanTahap.indexOf(stepName);

            item.classList.remove('completed', 'active');
            if (idx < activeIndex) {
                item.classList.add('completed');
            } else if (idx === activeIndex) {
                item.classList.add('active');
            }
        });
    }

    /**
     * Mengikat event klik pada setiap baris tabel riwayat servis
     */
    function bindRiwayatRowClicks() {
        const rows = document.querySelectorAll('.riwayat-row');
        rows.forEach((row) => {
            row.addEventListener('click', () => {
                rows.forEach((r) => r.classList.remove('selected-row'));
                row.classList.add('selected-row');

                const id = row.getAttribute('data-id');
                const kodeWo = row.getAttribute('data-wo');
                const pelat = row.getAttribute('data-pelat');
                const status = row.getAttribute('data-status');
                const catatan = row.getAttribute('data-catatan');

                if (activeTransaksiInput) activeTransaksiInput.value = id;
                renderStepperProgress(kodeWo, pelat, status, catatan);
            });
        });
    }

    bindRiwayatRowClicks();

    /**
     * Menangani pengiriman Formulir Pemesanan Servis Baru via Fetch API
     */
    if (formBooking) {
        formBooking.addEventListener('submit', async (e) => {
            e.preventDefault();

            const btnSubmit = document.getElementById('btnSubmitBooking');
            const originalText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan Work Order...`;

            try {
                const formData = new FormData(formBooking);
                const response = await fetch('api.php?action=create_booking', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert(result.message, 'success');
                    formBooking.reset();
                    previewContainer.classList.add('d-none');
                    infoPaketBox.classList.add('d-none');
                    // Muat ulang halaman atau tabel riwayat
                    window.location.reload();
                } else {
                    showAlert(result.message || 'Gagal menyimpan pesanan.', 'danger');
                }
            } catch (error) {
                showAlert('Tidak dapat terhubung ke server API: ' + error.message, 'danger');
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;
            }
        });
    }

    /**
     * Tombol Simulasi perubahan status progres kendaraan
     */
    const statusButtons = document.querySelectorAll('.btn-simulasi-status');
    statusButtons.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const transaksiId = activeTransaksiInput ? activeTransaksiInput.value : 0;
            const statusBaru = btn.getAttribute('data-status');

            if (!transaksiId || transaksiId === '0') {
                showAlert('Pilih salah satu transaksi Work Order terlebih dahulu.', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('transaksi_id', transaksiId);
            formData.append('status_servis', statusBaru);

            try {
                const response = await fetch('api.php?action=update_status', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    const d = result.data;
                    renderStepperProgress(d.kode_wo, d.pelat_nomor, d.status_servis, d.catatan_mekanik);
                    showAlert(result.message, 'success');

                    // Update atribut baris pada tabel riwayat
                    const activeRow = document.querySelector(`.riwayat-row[data-id="${transaksiId}"]`);
                    if (activeRow) {
                        activeRow.setAttribute('data-status', d.status_servis);
                        activeRow.setAttribute('data-catatan', d.catatan_mekanik);
                        const badgeEl = activeRow.querySelector('.badge');
                        if (badgeEl) badgeEl.textContent = d.status_servis;
                    }
                } else {
                    showAlert(result.message || 'Gagal memperbarui status.', 'danger');
                }
            } catch (err) {
                showAlert('Kesalahan jaringan saat memperbarui status.', 'danger');
            }
        });
    });
});
(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const filterStatus = document.getElementById('filterStatusTrx');
        const searchInput = document.getElementById('searchInputTrx');
        const rows = document.querySelectorAll('#tableTransaksi tbody .trx-row');

        function filterTable() {
            const statusVal = filterStatus.value.toLowerCase();
            const searchVal = searchInput.value.toLowerCase();

            rows.forEach((row) => {
                const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
                const rowText = row.innerText.toLowerCase();
                const matchStatus = !statusVal || rowStatus === statusVal;
                const matchSearch = !searchVal || rowText.includes(searchVal);
                row.style.display = matchStatus && matchSearch ? '' : 'none';
            });
        }

        if (filterStatus) filterStatus.addEventListener('change', filterTable);
        if (searchInput) searchInput.addEventListener('input', filterTable);

        const bookingModalElement = document.getElementById('modalBookingBaru');
        const vehicleSelect = bookingModalElement?.querySelector('[name="kendaraan_id"]');
        const serviceSelect = bookingModalElement?.querySelector('[name="layanan_id"]');

        function filterServices() {
            if (!vehicleSelect || !serviceSelect) return;
            const selectedVehicle = vehicleSelect.options[vehicleSelect.selectedIndex];
            const categoryId = selectedVehicle?.dataset.kategoriId || '';
            let selectedServiceAvailable = false;
            Array.from(serviceSelect.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }
                const matches = categoryId !== '' && option.dataset.kategoriId === categoryId;
                option.hidden = !matches;
                option.disabled = !matches;
                if (matches && option.value === serviceSelect.value) selectedServiceAvailable = true;
            });
            if (!selectedServiceAvailable) serviceSelect.value = '';
        }

        if (vehicleSelect && serviceSelect) {
            vehicleSelect.addEventListener('change', filterServices);
            filterServices();
        }

        const vehicleModalElement = document.getElementById('modalTambahKendaraan');
        if (!vehicleModalElement || !window.bootstrap) return;

        const bookingModal = bootstrap.Modal.getOrCreateInstance(bookingModalElement);
        const vehicleModal = bootstrap.Modal.getOrCreateInstance(vehicleModalElement);
        const vehicleForm = document.getElementById('formKendaraanPelanggan');
        const vehicleAlert = document.getElementById('alertKendaraanPelanggan');
        const editVehicleButton = document.getElementById('btnUbahKendaraan');
        let reopenBooking = false;

        function resetVehicleForm() {
            vehicleForm.reset();
            vehicleForm.elements.id.value = '';
            document.getElementById('judulModalTambahKendaraan').textContent = 'Daftarkan Kendaraan';
            vehicleForm.querySelector('[type="submit"]').textContent = 'Simpan Kendaraan';
            vehicleAlert.className = 'alert d-none';
        }

        document.getElementById('btnBukaTambahKendaraan').addEventListener('click', () => {
            resetVehicleForm();
            reopenBooking = true;
            bookingModalElement.addEventListener('hidden.bs.modal', () => {
                vehicleModal.show();
            }, { once: true });
            bookingModal.hide();
        });

        editVehicleButton.addEventListener('click', () => {
            const selected = vehicleSelect.options[vehicleSelect.selectedIndex];
            if (!selected?.value) return;
            resetVehicleForm();
            vehicleForm.elements.id.value = selected.value;
            vehicleForm.elements.kategori_id.value = selected.dataset.kategoriId || '';
            vehicleForm.elements.no_polisi.value = selected.dataset.noPolisi || '';
            vehicleForm.elements.nama_model.value = selected.dataset.namaModel || '';
            vehicleForm.elements.tahun_pembuatan.value = selected.dataset.tahun || '';
            vehicleForm.elements.warna.value = selected.dataset.warna || '';
            document.getElementById('judulModalTambahKendaraan').textContent = 'Ubah Kendaraan';
            vehicleForm.querySelector('[type="submit"]').textContent = 'Simpan Perubahan';
            reopenBooking = true;
            bookingModalElement.addEventListener('hidden.bs.modal', () => {
                vehicleModal.show();
            }, { once: true });
            bookingModal.hide();
        });

        vehicleModalElement.addEventListener('hidden.bs.modal', () => {
            if (reopenBooking) {
                reopenBooking = false;
                bookingModal.show();
            }
        });

        vehicleSelect.addEventListener('change', () => {
            editVehicleButton.disabled = !vehicleSelect.value;
        });
        editVehicleButton.disabled = !vehicleSelect.value;

        vehicleForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!vehicleForm.reportValidity()) return;

            const submitButton = vehicleForm.querySelector('[type="submit"]');
            const originalLabel = submitButton.textContent.trim();
            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';
            vehicleAlert.className = 'alert d-none';

            try {
                const body = Object.fromEntries(new FormData(vehicleForm).entries());
                body.kategori_id = Number(body.kategori_id);
                if (body.id) body.id = Number(body.id);
                const action = body.id ? 'update_own_vehicle' : 'register_own_vehicle';
                const response = await fetch(`api.php?endpoint=kendaraan&action=${action}`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const result = await response.json();
                if (!response.ok || !result.success || !result.data?.kendaraan) {
                    throw new Error(result.message || `Gagal menyimpan kendaraan (HTTP ${response.status}).`);
                }

                const vehicle = result.data.kendaraan;
                let option = Array.from(vehicleSelect.options).find((item) => item.value === String(vehicle.id));
                if (!option) {
                    option = new Option('', vehicle.id);
                    vehicleSelect.add(option);
                }
                option.textContent = `${vehicle.no_polisi} - ${vehicle.nama_model}`;
                option.dataset.kategoriId = String(vehicle.kategori_id);
                option.dataset.noPolisi = vehicle.no_polisi;
                option.dataset.namaModel = vehicle.nama_model;
                option.dataset.tahun = vehicle.tahun_pembuatan || '';
                option.dataset.warna = vehicle.warna || '';
                vehicleSelect.value = String(vehicle.id);
                editVehicleButton.disabled = false;
                filterServices();
                vehicleForm.reset();
                vehicleAlert.className = 'alert alert-success';
                vehicleAlert.textContent = action === 'update_own_vehicle'
                    ? 'Kendaraan berhasil diperbarui dan dipilih untuk booking.'
                    : 'Kendaraan berhasil didaftarkan dan dipilih untuk booking.';
                vehicleModal.hide();
            } catch (error) {
                vehicleAlert.className = 'alert alert-danger';
                vehicleAlert.textContent = error.message || 'Terjadi kesalahan saat menyimpan kendaraan.';
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalLabel;
            }
        });
    });
})();
document.addEventListener('DOMContentLoaded', function () {
    const discountForm = document.querySelector(
        'form[data-form="discount"]'
    );

    if (discountForm) {
        discountForm.addEventListener('submit', function (event) {
            const discountInput = discountForm.querySelector(
                '[name="discount_percent"]'
            );

            if (!discountInput) {
                return;
            }

            const discount = Number(discountInput.value);

            if (
                discountInput.value.trim() === '' ||
                !Number.isFinite(discount) ||
                discount < 0 ||
                discount > 100
            ) {
                event.preventDefault();

                alert(
                    'Persentase diskon harus berupa angka antara 0 sampai 100%.'
                );

                discountInput.focus();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi Form Rekomendasi Restock
    |--------------------------------------------------------------------------
    | Pengguna boleh memilih produk yang ingin dihitung.
    | Jika satu baris mulai diisi, semua kolom pada baris tersebut wajib diisi.
    | Lead time dan target stok minimal 1, safety stock minimal 0. Minimal satu produk harus diisi.
    */
    const restockForm = document.querySelector(
        'form[data-form="restock"]'
    );

    if (restockForm) {
        restockForm.addEventListener('submit', function (event) {
            const rows = {};

            const numberInputs = restockForm.querySelectorAll(
                'input[type="number"]'
            );

            numberInputs.forEach(function (input) {
                const match = input.name.match(/\[(\d+)\]/);

                if (!match) {
                    return;
                }

                const productId = match[1];

                if (!rows[productId]) {
                    rows[productId] = [];
                }

                rows[productId].push(input);
            });

            let filledRows = 0;

            for (const productId in rows) {
                const inputs = rows[productId];

                const filledInputs = inputs.filter(function (input) {
                    return input.value.trim() !== '';
                });

                if (filledInputs.length === 0) {
                    continue;
                }

                filledRows++;

                // Semua kolom pada baris yang dipilih wajib diisi.
                if (filledInputs.length !== inputs.length) {
                    event.preventDefault();

                    alert(
                        'Lengkapi semua kolom lead time, safety stock, dan target stok pada produk yang dipilih.'
                    );

                    const emptyInput = inputs.find(function (input) {
                        return input.value.trim() === '';
                    });

                    if (emptyInput) {
                        emptyInput.focus();
                    }

                    return;
                }

                // Periksa setiap angka sesuai batas minimal input.
                for (const input of inputs) {
                    const value = Number(input.value);
                    const minValue = input.min === ''
                        ? 0
                        : Number(input.min);

                    if (
                        input.value.trim() === '' ||
                        !Number.isFinite(value) ||
                        !Number.isInteger(value) ||
                        value < minValue
                    ) {
                        event.preventDefault();

                        alert(
                            'Masukkan angka bulat yang valid sesuai batas minimal pada setiap kolom restock.'
                        );

                        input.focus();
                        return;
                    }
                }
            }

            if (filledRows === 0) {
                event.preventDefault();

                alert('Isi data minimal untuk satu produk.');
            }
        });
    }

});

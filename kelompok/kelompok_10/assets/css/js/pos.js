/* JS eksternal POS (CSP melarang script inline). Semua hitung memakai sen (integer). */
(function () {
    'use strict';

    function toCents(text) {
        var t = String(text).trim();
        if (!/^\d{1,12}(\.\d{1,2})?$/.test(t)) {
            return null;
        }
        var parts = t.split('.');
        var frac = parts[1] ? parseInt((parts[1] + '00').slice(0, 2), 10) : 0;
        return parseInt(parts[0], 10) * 100 + frac;
    }

    function formatRupiah(cents) {
        var whole = Math.floor(cents / 100).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var frac = cents % 100;
        return 'Rp' + whole + (frac > 0 ? ',' + (frac < 10 ? '0' : '') + frac : '');
    }

    var totalBox = document.querySelector('.pos-total');
    var amountInput = document.getElementById('amount_paid');
    var methodSelect = document.getElementById('payment_method');
    var changeText = document.getElementById('pos-change-text');

    if (totalBox && amountInput && methodSelect && changeText) {
        var totalCents = parseInt(totalBox.getAttribute('data-total-cents') || '0', 10);

        var update = function () {
            var isCash = methodSelect.value === 'cash';
            amountInput.disabled = !isCash;

            if (!isCash) {
                changeText.textContent = 'Rp0';
                return;
            }
            var paid = toCents(amountInput.value);
            if (paid === null || totalCents <= 0) {
                changeText.textContent = '-';
            } else if (paid < totalCents) {
                changeText.textContent = 'Uang kurang ' + formatRupiah(totalCents - paid);
            } else {
                changeText.textContent = formatRupiah(paid - totalCents);
            }
        };

        amountInput.addEventListener('input', update);
        methodSelect.addEventListener('change', update);
        update();
    }

    var printLinks = document.querySelectorAll('.js-print');
    for (var i = 0; i < printLinks.length; i++) {
        printLinks[i].addEventListener('click', function (event) {
            event.preventDefault();
            window.print();
        });
    }
})();

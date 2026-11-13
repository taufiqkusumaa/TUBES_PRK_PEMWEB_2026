// public/assets/js/app.js
// Perilaku UI bersama: tema, pesan global, modal, dan dialog konfirmasi.
(function () {
    const akar = document.documentElement;
    const tombolTema = document.querySelectorAll('[data-tema-toggle]');
    const metaTema = document.querySelector('meta[name="theme-color"]');

    function terapkanTema(tema) {
        akar.setAttribute('data-tema', tema);
        if (metaTema) metaTema.setAttribute('content', tema === 'gelap' ? '#101713' : '#F6F8F2');

        tombolTema.forEach(function (button) {
            const gelap = tema === 'gelap';
            button.setAttribute('aria-pressed', gelap ? 'true' : 'false');
            button.setAttribute('aria-label', gelap ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
            const label = button.querySelector('.theme-label');
            if (label) label.textContent = gelap ? 'Mode terang' : 'Mode gelap';
        });
    }

    terapkanTema(akar.getAttribute('data-tema') === 'gelap' ? 'gelap' : 'terang');

    tombolTema.forEach(function (button) {
        button.addEventListener('click', function () {
            const baru = akar.getAttribute('data-tema') === 'gelap' ? 'terang' : 'gelap';
            terapkanTema(baru);
            try {
                localStorage.setItem('kebunku-tema', baru);
            } catch (error) {
                // Penyimpanan bisa diblokir; tema tetap berlaku untuk sesi ini.
            }
        });
    });
})();

let waktuPesan = null;

function tampilPesan(pesan, jenis) {
    const kotak = document.getElementById('pesanGlobal');
    const teks = document.getElementById('pesanGlobalTeks');
    if (!kotak || !teks) return;

    teks.textContent = pesan;
    kotak.className = 'global-message' + ((jenis || 'sukses') === 'gagal' ? ' global-message--error' : '');
    kotak.hidden = false;

    if (waktuPesan) clearTimeout(waktuPesan);
    if ((jenis || 'sukses') === 'sukses') {
        waktuPesan = setTimeout(function () {
            kotak.hidden = true;
        }, 4500);
    }
}

document.querySelector('.close-global-message')?.addEventListener('click', function () {
    document.getElementById('pesanGlobal').hidden = true;
});

function bukaModal(el) {
    if (!el) return;
    if (el.tagName === 'DIALOG') {
        if (!el.open) el.showModal();
        return;
    }
    el.classList.add('tampil');
    el.style.display = 'block';
    el.removeAttribute('aria-hidden');
}

function tutupModal(el) {
    if (!el) return;
    if (el.tagName === 'DIALOG') {
        if (el.open) el.close();
        return;
    }
    el.classList.remove('tampil');
    el.style.display = 'none';
    el.setAttribute('aria-hidden', 'true');
}

function konfirmasiAksi(pesan, label) {
    const dialog = document.getElementById('dialogKonfirmasi');
    if (!dialog || typeof dialog.showModal !== 'function') {
        return Promise.resolve(window.confirm(pesan));
    }

    const isi = document.getElementById('konfirmasiPesan');
    const tombolYa = dialog.querySelector('[data-konfirmasi-ya]');
    const tombolBatal = dialog.querySelector('[data-konfirmasi-batal]');
    let hasil = false;

    isi.textContent = pesan;
    tombolYa.textContent = label || 'Ya, lanjutkan';

    return new Promise(function (resolve) {
        function selesai(value) {
            hasil = value;
            dialog.close();
        }

        function saatTutup() {
            tombolYa.removeEventListener('click', saatYa);
            tombolBatal.removeEventListener('click', saatBatal);
            resolve(hasil);
        }

        function saatYa() {
            selesai(true);
        }

        function saatBatal() {
            selesai(false);
        }

        tombolYa.addEventListener('click', saatYa);
        tombolBatal.addEventListener('click', saatBatal);
        dialog.addEventListener('close', saatTutup, { once: true });
        dialog.showModal();
    });
}

document.addEventListener('click', function (e) {
    const toggle = e.target.closest('[data-modal-open]');
    if (toggle) {
        e.preventDefault();
        const target = document.getElementById(toggle.getAttribute('data-modal-open').replace('#', ''));
        if (target) {
            // Event untuk halaman: siapkan form sebelum modal tampil (mis. reset).
            document.dispatchEvent(new CustomEvent('modal:buka', { detail: target.id }));
            bukaModal(target);
        }
        return;
    }

    const dismiss = e.target.closest('[data-modal-close]');
    if (dismiss) {
        e.preventDefault();
        let el = dismiss.closest('.modal');
        if (!el && dismiss.getAttribute('data-modal-close')) {
            el = document.getElementById(dismiss.getAttribute('data-modal-close').replace('#', ''));
        }
        if (el) tutupModal(el);
        return;
    }

    if (e.target.tagName === 'DIALOG' && e.target.classList.contains('modal')) {
        tutupModal(e.target);
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.tampil').forEach(tutupModal);
    }
});



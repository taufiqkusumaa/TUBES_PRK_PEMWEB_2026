// Interaksi halaman masuk/daftar: tombol mata, meter kekuatan sandi, cek username, nomor HP, pilihan peran.
(function () {
  // 1) Tombol mata: tampilkan / sembunyikan kata sandi
  document.querySelectorAll('[data-toggle-sandi]').forEach(function (tb) {
    tb.addEventListener('click', function () {
      var input = document.getElementById(tb.getAttribute('data-toggle-sandi'));
      var tampil = input.type === 'password';
      input.type = tampil ? 'text' : 'password';
      tb.setAttribute('aria-pressed', tampil ? 'true' : 'false');
      tb.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
      tb.querySelector('i').className = 'bi ' + (tampil ? 'bi-eye-slash' : 'bi-eye');
    });
  });

  // 2) Meter kekuatan kata sandi
  document.querySelectorAll('[data-meter-untuk]').forEach(function (m) {
    var input = document.getElementById(m.getAttribute('data-meter-untuk'));
    var batang = m.querySelector('.batang span'), teks = m.querySelector('.teks');
    var nama = ['Minimal 8 karakter, memuat huruf dan angka.', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];
    function hitung() {
      var p = input.value, s = 0;
      if (p.length === 0) { m.setAttribute('data-skor', '0'); batang.style.width = '0'; teks.textContent = nama[0]; return; }
      if (p.length >= 8 && /[A-Za-z]/.test(p) && /\d/.test(p)) { s = 2; }
      else { s = 1; }
      if (s === 2 && p.length >= 12) { s = 3; }
      if (s >= 2 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /[^A-Za-z0-9]/.test(p)) { s = Math.min(4, s + 1); }
      m.setAttribute('data-skor', String(s));
      batang.style.width = (s * 25) + '%';
      teks.textContent = 'Kekuatan: ' + nama[s];
    }
    input.addEventListener('input', hitung); hitung();
  });

  // 3) Konfirmasi kata sandi harus sama
  var p1 = document.getElementById('password'), p2 = document.getElementById('password2');
  if (p1 && p2) {
    function cocok() { p2.setCustomValidity(p2.value !== '' && p1.value !== p2.value ? 'Konfirmasi kata sandi tidak sama.' : ''); }
    p1.addEventListener('input', cocok); p2.addEventListener('input', cocok);
  }

  // 4) Username: format + ketersediaan (diperiksa ke server)
  var un = document.getElementById('username');
  if (un && un.getAttribute('data-cek-username')) {
    var info = document.getElementById('infoUsername'), awal = info ? info.textContent : '', timer = null, seq = 0;
    function formatSalah(v) {
      if (!/^[a-z][a-z0-9._]{3,29}$/.test(v)) { return 'Username 4-30 karakter, diawali huruf, hanya huruf kecil, angka, titik, dan garis bawah.'; }
      if (/[._]{2}/.test(v) || /[._]$/.test(v)) { return 'Username tidak boleh punya titik/garis bawah berturut-turut atau di akhir.'; }
      return '';
    }
    function setInfo(pesan, kelas) { if (info) { info.textContent = pesan; info.className = 'form-text ' + (kelas || ''); } }
    un.addEventListener('input', function () {
      un.value = un.value.toLowerCase().replace(/\s+/g, '');
      un.setCustomValidity('');
      clearTimeout(timer);
      var v = un.value;
      if (v === '') { setInfo(awal, ''); return; }
      var salah = formatSalah(v);
      if (salah) { un.setCustomValidity(salah); setInfo(salah, 'info-buruk'); return; }
      setInfo('Memeriksa...', '');
      var no = ++seq;
      timer = setTimeout(function () {
        fetch(un.getAttribute('data-cek-username') + '?u=' + encodeURIComponent(v), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (no !== seq) { return; }
            if (!d.tersedia) { un.setCustomValidity(d.pesan); setInfo(d.pesan, 'info-buruk'); }
            else { un.setCustomValidity(''); setInfo(d.pesan, 'info-ok'); }
          }).catch(function () { if (no === seq) { setInfo(awal, ''); } });
      }, 350);
    });
  }

  // 5) Nomor handphone: rapikan ke 08xxxxxxxxxx
  var hp = document.getElementById('telepon');
  if (hp && hp.hasAttribute('data-telepon')) {
    function baku(v) {
      v = v.replace(/[\s\-().]/g, '');
      if (v.indexOf('+62') === 0) { v = '0' + v.slice(3); } else if (v.indexOf('62') === 0) { v = '0' + v.slice(2); }
      return v;
    }
    function periksa() {
      var v = baku(hp.value);
      hp.setCustomValidity(hp.value !== '' && !/^08[1-9][0-9]{7,10}$/.test(v) ? 'Nomor handphone tidak valid. Contoh: 081234567890 atau +6281234567890.' : '');
    }
    hp.addEventListener('input', periksa);
    hp.addEventListener('blur', function () { if (/^08[1-9][0-9]{7,10}$/.test(baku(hp.value))) { hp.value = baku(hp.value); } periksa(); });
    periksa();
  }

  // 6) Pilihan peran: tampilkan kolom pengacara
  var kolom = document.getElementById('kolom-pengacara');
  if (kolom) {
    function atur() {
      var png = document.getElementById('r-pengacara').checked;
      kolom.hidden = !png;
      kolom.querySelectorAll('select, input').forEach(function (el) { el.disabled = !png; el.required = png; });
    }
    document.querySelectorAll('input[name=role]').forEach(function (r) { r.addEventListener('change', atur); });
    atur();
  }

  // 7) Cegah kirim ganda
  document.querySelectorAll('button[data-memproses]').forEach(function (b) {
    var f = b.form; if (!f) { return; }
    var asal = b.innerHTML;
    f.addEventListener('submit', function () {
      b.disabled = true;
      b.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ' + b.getAttribute('data-memproses');
    });
    window.addEventListener('pageshow', function () { b.disabled = false; b.innerHTML = asal; });
  });
})();

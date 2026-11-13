<?php
/** @var array<string, string> $formValues */
?>
<style>
.rg-page{width:100%;
  --navy:#1e2b47;--navy-d:#18233b;--red:#b8212f;--red-l:#c8323f;--pink:#ef6a7d;
  --ink:#1b2438;--muted:#6b7385;--line:#e6e8ee;--field:#f8f9fb;--green:#1f8f55;
  position:relative;overflow:hidden;padding:32px 16px;color:var(--ink);
  font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  background:
    radial-gradient(circle at 10% 12%,rgba(239,106,125,.14),transparent 28%),
    radial-gradient(circle at 92% 92%,rgba(30,43,71,.08),transparent 35%),
    #f5f6fa;
}
.rg-page::before,.rg-page::after{content:"";position:absolute;border-radius:50%;border:56px solid rgba(30,43,71,.05);pointer-events:none}
.rg-page::before{width:280px;height:280px;top:-120px;right:-80px}
.rg-page::after{width:220px;height:220px;bottom:-110px;left:-60px;border-color:rgba(184,33,47,.06)}

.rg-split{
  position:relative;z-index:1;width:100%;max-width:900px;display:grid;grid-template-columns:45% 55%;
  background:#fff;border-radius:24px;overflow:hidden;
  box-shadow:0 30px 60px -20px rgba(30,43,71,.25),0 0 0 1px rgba(30,43,71,.04);
}
.rg-brand{
  position:relative;overflow:hidden;color:#fff;text-align:center;padding:40px 28px;
  background:linear-gradient(160deg,var(--navy),var(--navy-d));
  display:flex;flex-direction:column;align-items:center;justify-content:center;
}
.rg-brand::before,.rg-brand::after{content:"";position:absolute;border-radius:50%;border:38px solid rgba(255,255,255,.06)}
.rg-brand::before{width:200px;height:200px;top:-100px;right:-60px}
.rg-brand::after{width:150px;height:150px;bottom:-75px;left:-40px}
.rg-brand>*{position:relative;z-index:1}
.rg-logo{width:88px;height:88px;border-radius:22px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.14);display:grid;place-items:center;margin-bottom:28px}
.rg-brand-title{font-size:40px;font-weight:800;letter-spacing:.06em;line-height:1;margin:0}
.rg-brand-title span{color:var(--pink)}
.rg-brand-sub{margin:16px 0 0;font-size:14px;font-weight:500;color:#e8ebf2}
.rg-brand-line{width:48px;height:3px;border:0;border-radius:2px;background:var(--pink);opacity:1;margin:24px 0 20px}
.rg-brand-tag{font-size:11.5px;font-weight:500;letter-spacing:.14em;color:#aab2c4;text-transform:uppercase}

.rg-form{padding:48px 48px 36px}
.rg-eyebrow{display:flex;align-items:center;gap:10px;font-size:11.5px;font-weight:700;letter-spacing:.16em;color:var(--red);text-transform:uppercase}
.rg-eyebrow::before{content:"";width:22px;height:2px;background:var(--red)}
.rg-title{font-size:30px;font-weight:800;margin:10px 0 0;letter-spacing:-.02em}
.rg-lead{margin:8px 0 26px;font-size:14px;line-height:1.7;color:var(--muted)}
.rg-field{margin-top:18px}
.rg-label{display:block;font-size:13px;font-weight:700;margin-bottom:8px}
.rg-label b{color:var(--red)}
.rg-input{position:relative}
.rg-input>svg.ic{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#8b93a5;pointer-events:none}
.rg-input input,
.rg-input select{
  width:100%;height:50px;border:1px solid var(--line);border-radius:12px;background:var(--field);
  padding:0 46px;font:inherit;font-size:14px;color:var(--ink);transition:border-color .15s,box-shadow .15s,background .15s;
}
.rg-input select{appearance:none;-webkit-appearance:none;cursor:pointer}
.rg-input input::placeholder{color:#9aa1b1}
.rg-input input:focus,
.rg-input select:focus{outline:none;background:#fff;border-color:var(--red);box-shadow:0 0 0 4px rgba(184,33,47,.12)}
.rg-select-arrow{position:absolute;right:16px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8b93a5}
.rg-eye{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:36px;height:36px;border:0;background:none;border-radius:8px;color:#8b93a5;cursor:pointer;display:grid;place-items:center}
.rg-eye:hover{color:var(--ink)}
.rg-eye:focus-visible{outline:2px solid var(--red)}
.rg-btn{
  width:100%;height:50px;margin-top:24px;border:0;border-radius:12px;cursor:pointer;color:#fff;font:inherit;font-size:14.5px;font-weight:700;
  background:linear-gradient(90deg,var(--red),var(--red-l));display:flex;align-items:center;justify-content:center;gap:10px;
  box-shadow:0 10px 20px -8px rgba(184,33,47,.55);transition:transform .15s,box-shadow .15s;
}
.rg-btn:hover{transform:translateY(-1px);box-shadow:0 14px 24px -8px rgba(184,33,47,.6)}
.rg-btn:focus-visible{outline:3px solid rgba(184,33,47,.35);outline-offset:2px}
.rg-switch{text-align:center;font-size:13.5px;color:var(--muted);margin:20px 0 0}
.rg-switch a{color:var(--red);font-weight:700;text-decoration:none}
.rg-switch a:hover{text-decoration:underline}
.rg-secure{margin-top:22px;padding-top:18px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:center;gap:8px;font-size:12px;color:var(--muted)}

@media (max-width:760px){
  .rg-split{grid-template-columns:1fr;max-width:460px}
  .rg-brand{padding:28px 24px}
  .rg-logo{width:60px;height:60px;border-radius:16px;margin-bottom:16px}
  .rg-logo svg{width:28px;height:28px}
  .rg-brand-title{font-size:30px}
  .rg-brand-sub{margin-top:10px}
  .rg-brand-line,.rg-brand-tag{display:none}
  .rg-form{padding:32px 24px 28px}
}
@media (prefers-reduced-motion:reduce){.rg-page *{transition:none!important}}
</style>

<main class="rg-page min-vh-100 d-flex align-items-center justify-content-center">
  <section class="rg-split">
    <aside class="rg-brand">
      <div class="rg-logo">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.6-3.1 8.2-7.5 9.5-4.4-1.3-7.5-4.9-7.5-9.5V6L12 3z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg>
      </div>
      <h1 class="rg-brand-title">ADVO<span>KITA</span></h1>
      <p class="rg-brand-sub">Whistleblowing &amp; Legal Aid System</p>
      <hr class="rg-brand-line">
      <small class="rg-brand-tag">Transparansi · Perlindungan · Keadilan</small>
    </aside>

    <form class="rg-form" method="post" action="<?= escape(url('register')) ?>" autocomplete="on">
      <?php csrf_input(); ?>
      <input type="hidden" name="action" value="register">

      <div class="rg-eyebrow">Mulai bergabung</div>
      <h2 class="rg-title">Buat Akun Baru</h2>
 rizkybranch
      <p class="rg-lead">Daftar menggunakan data Anda untuk mengajukan laporan di ADVOKITA.</p>

      <p class="rg-lead">Daftar menggunakan data mahasiswa untuk mengajukan laporan di ADVOKITA.</p>
 main

      <div class="rg-field" style="margin-top:0">
        <label class="rg-label" for="registerName">Nama lengkap <b>*</b></label>
        <div class="rg-input">
          <svg class="ic" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
          <input id="registerName" name="fullName" maxlength="120" autocomplete="name" required placeholder="Masukkan nama lengkap" value="<?= escape($formValues['fullName'] ?? '') ?>">
        </div>
 rizkybranch
      </div>

      <div class="rg-field">
        <label class="rg-label" for="registerUserType">Tipe Pengguna <b>*</b></label>
        <div class="rg-input">
          <select id="registerUserType" name="user_type" required>
            <option value="" disabled selected>Pilih tipe pengguna</option>
            <option value="mahasiswa">Mahasiswa</option>
            <option value="dosen">Dosen</option>
            <option value="civitas">Civitas Fakultas</option>
          </select>
        </div>
      </div>

      <div class="rg-field">
        <label class="rg-label" for="registerNim">NPM/NIP <b>*</b></label>
        <div class="rg-input">
          <svg class="ic" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.6-1.4 1.7-2 3-2s2.4.6 3 2M15 10h3M15 14h3"/></svg>
          <input id="registerNim" name="nim" required inputmode="numeric" pattern="[0-9]{4,20}" autocomplete="username" placeholder="Masukkan NPM/NIP Anda" value="<?= escape($formValues['nim'] ?? '') ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="registerPassword">Kata sandi <span class="text-danger">*</span></label>
        <input class="form-control" type="password" id="registerPassword" name="password" minlength="8" maxlength="72" autocomplete="new-password" required placeholder="Minimal 8 karakter">

      </div>

      <div class="rg-field">
        <label class="rg-label" for="registerRole">Peran / Status <b>*</b></label>
        <div class="rg-input">
          <svg class="ic" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <select id="registerRole" name="userRole" required>
            <option value="" disabled selected>Pilih peran Anda</option>
            <option value="mahasiswa" <?= (($formValues['userRole'] ?? '') === 'mahasiswa') ? 'selected' : '' ?>>Mahasiswa</option>
            <option value="dosen" <?= (($formValues['userRole'] ?? '') === 'dosen') ? 'selected' : '' ?>>Dosen</option>
            <option value="civitas" <?= (($formValues['userRole'] ?? '') === 'civitas') ? 'selected' : '' ?>>Civitas Fakultas</option>
          </select>
          <svg class="rg-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
      </div>

      <div class="rg-field">
        <label class="rg-label" for="registerNim">NPM / NIP <b>*</b></label>
        <div class="rg-input">
          <svg class="ic" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.6-1.4 1.7-2 3-2s2.4.6 3 2M15 10h3M15 14h3"/></svg>
          <input id="registerNim" name="nim" required inputmode="numeric" pattern="[0-9]{4,20}" autocomplete="username" placeholder="Masukkan NPM/NIP Anda" value="<?= escape($formValues['nim'] ?? '') ?>">
        </div>
      </div>

      <div class="rg-field">
        <label class="rg-label" for="registerPassword">Kata sandi <b>*</b></label>
        <div class="rg-input">
          <svg class="ic" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></svg>
          <input type="password" id="registerPassword" name="password" minlength="8" maxlength="72" autocomplete="new-password" required placeholder="Minimal 8 karakter">
          <button class="rg-eye" type="button" data-toggle="registerPassword" aria-label="Tampilkan kata sandi">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <div class="rg-field">
        <label class="rg-label" for="registerPasswordConfirm">Konfirmasi kata sandi <b>*</b></label>
        <div class="rg-input">
          <svg class="ic" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></svg>
          <input type="password" id="registerPasswordConfirm" name="passwordConfirm" minlength="8" maxlength="72" autocomplete="new-password" required placeholder="Ulangi kata sandi">
          <button class="rg-eye" type="button" data-toggle="registerPasswordConfirm" aria-label="Tampilkan konfirmasi kata sandi">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
 main
      </div>

      <button class="rg-btn" type="submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
        Daftar ke ADVOKITA
      </button>
      <p class="rg-switch">Sudah punya akun? <a href="<?= escape(url('login')) ?>">Masuk di sini</a></p>
      <div class="rg-secure">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="#1f8f55"><path d="M12 2l8 3v6c0 5-3.4 9-8 11-4.6-2-8-6-8-11V5l8-3z"/></svg>
        Akses aman dan terlindungi
      </div>
    </form>
  </section>
</main>

<script>
(function () {
  document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.toggle);
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });
  var pass = document.getElementById('registerPassword');
  var confirm = document.getElementById('registerPasswordConfirm');
  function check() {
    confirm.setCustomValidity(confirm.value && confirm.value !== pass.value ? 'Konfirmasi kata sandi tidak sama.' : '');
  }
  pass.addEventListener('input', check);
  confirm.addEventListener('input', check);
})();
</script>
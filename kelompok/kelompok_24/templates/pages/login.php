<?php
/** @var array<string, string> $formValues */
?>
<style>
.login-page {
  --adv-navy: #24344d;
  --adv-navy-dark: #18263b;
  --adv-red: #b42332;
  --adv-red-dark: #941b29;
  --adv-muted: #758195;
  --adv-border: #e2e7ef;

  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 45px 18px;
  overflow: hidden;
  background:
    radial-gradient(circle at 10% 15%, rgba(180,35,50,.08), transparent 30%),
    radial-gradient(circle at 90% 85%, rgba(36,52,77,.11), transparent 32%),
    #f4f6fa;
}

/* Decorative background */

.login-page::before,
.login-page::after {
  content: "";
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

.login-page::before {
  width: 350px;
  height: 350px;
  top: -180px;
  right: -100px;
  border: 55px solid rgba(36,52,77,.045);
}

.login-page::after {
  width: 260px;
  height: 260px;
  bottom: -140px;
  left: -90px;
  border: 40px solid rgba(180,35,50,.045);
}

/* Main card */

.login-card {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: .9fr 1.1fr;
  width: 100%;
  max-width: 900px;
  min-height: 530px;
  overflow: hidden;
  border: 1px solid rgba(226,231,239,.8);
  border-radius: 23px;
  background: #fff;
  box-shadow: 0 22px 65px rgba(24,38,59,.12);
  animation: loginAppear .55s ease both;
}

/* Brand panel */

.login-brand {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 45px 32px;
  overflow: hidden;
  background: linear-gradient(145deg, var(--adv-navy), var(--adv-navy-dark));
  color: #fff;
  text-align: center;
}

.login-brand::before {
  content: "";
  position: absolute;
  width: 280px;
  height: 280px;
  top: -120px;
  right: -120px;
  border: 38px solid rgba(255,255,255,.055);
  border-radius: 50%;
}

.login-brand::after {
  content: "";
  position: absolute;
  width: 220px;
  height: 220px;
  bottom: -115px;
  left: -95px;
  border: 32px solid rgba(255,255,255,.045);
  border-radius: 50%;
}

.login-brand-content {
  position: relative;
  z-index: 1;
}

.login-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 88px;
  height: 88px;
  margin: 0 auto 23px;
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 25px;
  background: rgba(255,255,255,.10);
  color: #fff;
  font-size: 2.8rem;
  box-shadow: 0 10px 30px rgba(0,0,0,.12);
}

.login-brand h1 {
  margin: 0 0 12px;
  color: #fff;
  font-size: 2.35rem;
  font-weight: 900;
  letter-spacing: 2px;
}

.login-brand h1 span {
  color: #f16b78;
}

.login-brand-subtitle {
  max-width: 260px;
  margin: 0 auto;
  color: rgba(255,255,255,.75);
  font-size: .88rem;
  line-height: 1.7;
}

.login-brand-divider {
  width: 48px;
  height: 3px;
  margin: 24px auto;
  border-radius: 5px;
  background: #e34c5c;
}

.login-brand-caption {
  margin: 0;
  color: rgba(255,255,255,.65);
  font-size: .76rem;
  letter-spacing: 1.3px;
  text-transform: uppercase;
}

/* Form panel */

.login-form-panel {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 55px 48px;
}

.login-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  color: var(--adv-red);
  font-size: .75rem;
  font-weight: 800;
  letter-spacing: 1.7px;
}

.login-eyebrow::before {
  content: "";
  width: 22px;
  height: 2px;
  background: var(--adv-red);
}

.login-form-heading h2 {
  margin: 0 0 9px;
  color: var(--adv-navy-dark);
  font-size: 1.85rem;
  font-weight: 850;
  letter-spacing: -.5px;
}

.login-form-heading p {
  margin-bottom: 30px;
  color: var(--adv-muted);
  font-size: .9rem;
  line-height: 1.65;
}

/* Form controls */

.login-field {
  margin-bottom: 21px;
}

.login-field label {
  display: block;
  margin-bottom: 9px;
  color: var(--adv-navy-dark);
  font-size: .85rem;
  font-weight: 750;
}

.login-required {
  color: var(--adv-red);
}

.login-input-wrap {
  position: relative;
}

.login-input-wrap > i {
  position: absolute;
  top: 50%;
  left: 15px;
  z-index: 2;
  color: #8994a5;
  font-size: 1.05rem;
  transform: translateY(-50%);
  pointer-events: none;
}

.login-input {
  width: 100%;
  min-height: 51px;
  padding: 12px 45px 12px 43px;
  border: 1px solid var(--adv-border);
  border-radius: 11px;
  outline: none;
  background: #f9fafc;
  color: var(--adv-navy-dark);
  font-size: .9rem;
  transition: .2s ease;
}

.login-input::placeholder {
  color: #a2aab7;
}

.login-input:hover {
  border-color: #bdc7d6;
}

.login-input:focus {
  border-color: var(--adv-navy);
  background: #fff;
  box-shadow: 0 0 0 4px rgba(36,52,77,.09);
}

/* Password toggle */

.login-password-toggle {
  position: absolute;
  top: 50%;
  right: 13px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  padding: 0;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #8994a5;
  transform: translateY(-50%);
  cursor: pointer;
  transition: .2s ease;
}

.login-password-toggle:hover {
  background: #edf0f5;
  color: var(--adv-navy);
}

/* Submit button */

.login-submit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  min-height: 51px;
  margin-top: 8px;
  border: 0;
  border-radius: 11px;
  background: linear-gradient(110deg, var(--adv-red), #c83242);
  color: #fff;
  font-size: .95rem;
  font-weight: 800;
  box-shadow: 0 7px 17px rgba(180,35,50,.18);
  transition: .25s ease;
  cursor: pointer;
}

.login-submit:hover {
  background: linear-gradient(110deg, var(--adv-red-dark), var(--adv-red));
  box-shadow: 0 10px 22px rgba(180,35,50,.26);
  transform: translateY(-2px);
}

.login-submit:active {
  transform: translateY(0);
}

.login-submit:focus-visible,
.login-password-toggle:focus-visible,
.login-register a:focus-visible {
  outline: 3px solid rgba(180,35,50,.35);
  outline-offset: 3px;
}

/* Register link */

.login-register {
  margin: 25px 0 0;
  color: var(--adv-muted);
  font-size: .85rem;
  text-align: center;
}

.login-register a {
  color: var(--adv-red);
  font-weight: 800;
  text-decoration: none;
}

.login-register a:hover {
  color: var(--adv-red-dark);
  text-decoration: underline;
}

.login-security {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  margin-top: 28px;
  padding-top: 19px;
  border-top: 1px solid #edf0f4;
  color: #8993a2;
  font-size: .75rem;
}

.login-security i {
  color: #26845b;
}

/* Animation */

@keyframes loginAppear {
  from {
    opacity: 0;
    transform: translateY(18px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */

@media (max-width: 767px) {
  .login-page {
    padding: 25px 15px;
  }

  .login-card {
    grid-template-columns: 1fr;
    max-width: 470px;
    min-height: auto;
    border-radius: 19px;
  }

  .login-brand {
    padding: 30px 20px;
  }

  .login-logo {
    width: 65px;
    height: 65px;
    margin-bottom: 13px;
    border-radius: 19px;
    font-size: 2.1rem;
  }

  .login-brand h1 {
    font-size: 1.9rem;
  }

  .login-brand-divider {
    margin: 15px auto;
  }

  .login-brand-caption {
    font-size: .68rem;
  }

  .login-form-panel {
    padding: 34px 25px;
  }

  .login-form-heading h2 {
    font-size: 1.6rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .login-card,
  .login-card *,
  .login-submit {
    animation: none !important;
    transition: none !important;
  }
}
</style>

<main class="login-page">

  <section class="login-card">

    <!-- BRAND PANEL -->
    <div class="login-brand">

      <div class="login-brand-content">

        <div class="login-logo">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
        </div>

        <h1>ADVO<span>KITA</span></h1>

        <p class="login-brand-subtitle">
          Whistleblowing &amp; Legal Aid System
        </p>

        <div class="login-brand-divider"></div>

        <p class="login-brand-caption">
          Transparansi · Perlindungan · Keadilan
        </p>

      </div><!-- /.login-brand-content -->

    </div><!-- /.login-brand -->

    <!-- LOGIN FORM -->
    <div class="login-form-panel">

      <div class="login-form-heading">
        <span class="login-eyebrow">SELAMAT DATANG KEMBALI</span>

        <h2>Login Akun</h2>

        <p>
          Silakan masuk menggunakan akun mahasiswa atau admin
          untuk melanjutkan ke ADVOKITA.
        </p>
      </div>

      <form
        method="post"
        action="<?= escape(url('login')) ?>"
        autocomplete="on"
      >

        <?php csrf_input(); ?>

        <input type="hidden" name="action" value="login">

        <!-- NPM -->
        <div class="login-field">

          <label for="loginNim">
            NPM <span class="login-required">*</span>
          </label>

          <div class="login-input-wrap">

            <i class="bi bi-person" aria-hidden="true"></i>

            <input
              class="login-input"
              type="text"
              id="loginNim"
              name="nim"
              required
              inputmode="numeric"
              pattern="[0-9]{4,20}"
              autocomplete="username"
              placeholder="Masukkan NPM Anda"
              value="<?= escape($formValues['nim'] ?? '') ?>"
            >

          </div>

        </div>

        <!-- PASSWORD -->
        <div class="login-field">

          <label for="loginPassword">
            Kata Sandi <span class="login-required">*</span>
          </label>

          <div class="login-input-wrap">

            <i class="bi bi-lock" aria-hidden="true"></i>

            <input
              class="login-input"
              type="password"
              id="loginPassword"
              name="password"
              minlength="8"
              maxlength="72"
              autocomplete="current-password"
              required
              placeholder="Masukkan kata sandi"
            >

            <button
              class="login-password-toggle"
              type="button"
              id="togglePassword"
              aria-label="Tampilkan kata sandi"
              aria-controls="loginPassword"
              aria-pressed="false"
            >
              <i class="bi bi-eye" aria-hidden="true"></i>
            </button>

          </div>

        </div>

        <!-- SUBMIT -->
        <button class="login-submit" type="submit">
          <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
          Masuk ke ADVOKITA
        </button>

        <p class="login-register">
          Belum punya akun?
          <a href="<?= escape(url('register')) ?>">Daftar di sini</a>
        </p>

        <div class="login-security">
          <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
          Akses aman dan terlindungi
        </div>

      </form>

    </div><!-- /.login-form-panel -->

  </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const passwordInput = document.getElementById('loginPassword');
  const toggleButton = document.getElementById('togglePassword');

  if (!passwordInput || !toggleButton) {
    return;
  }

  toggleButton.addEventListener('click', function () {
    const isPassword = passwordInput.type === 'password';

    passwordInput.type = isPassword ? 'text' : 'password';

    toggleButton.innerHTML = isPassword
      ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>'
      : '<i class="bi bi-eye" aria-hidden="true"></i>';

    toggleButton.setAttribute(
      'aria-label',
      isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
    );

    toggleButton.setAttribute('aria-pressed', String(isPassword));
  });
});
</script>
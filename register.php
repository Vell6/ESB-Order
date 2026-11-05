<?php
/**
 * RM bu ani - Customer Registration Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

if (is_logged_in()) {
    header("Location: " . BASE_URL . "menu.php");
    exit;
}

$pageTitle = "Daftar Akun Pelanggan - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-wrapper no-bottom-nav" style="justify-content: center; align-items: center; min-height: 100vh; padding: 2rem 1rem;">
  <div class="sy-card" style="width: 100%; max-width: 480px; box-shadow: var(--shadow-lg); border-radius: var(--radius-xl);">
    <div style="text-align: center; margin-bottom: 1.5rem;">
      <div class="app-brand-logo" style="width: 50px; height: 50px; margin: 0 auto 0.75rem auto; font-size: 1.5rem; border-radius: var(--radius-md);">
        <i class="fa-solid fa-user-plus"></i>
      </div>
      <h2 style="font-weight: 800; font-size: 1.4rem; color: var(--text-primary); margin-bottom: 0.25rem;">Daftar Akun Baru</h2>
      <p style="font-size: 0.85rem; color: var(--text-secondary);">Nikmati kemudahan pesan makanan online di <?= APP_NAME ?></p>
    </div>

    <div id="registerAlert" style="display:none; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; margin-bottom: 1.25rem;"></div>

    <form id="registerForm" onsubmit="handleRegister(event)">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label" for="regName">Nama Lengkap <span class="required">*</span></label>
        <input type="text" id="regName" name="name" class="form-control" placeholder="Contoh: Marvel" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="regEmail">Email <span class="required">*</span></label>
        <input type="email" id="regEmail" name="email" class="form-control" placeholder="contoh@gmail.com" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="regPhone">Nomor WhatsApp / Telepon</label>
        <input type="tel" id="regPhone" name="phone" class="form-control" placeholder="083102315194">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div class="form-group">
          <label class="form-label" for="regPass">Password <span class="required">*</span></label>
          <input type="password" id="regPass" name="password" class="form-control" placeholder="Min. 8 karakter" required minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label" for="regPassConfirm">Konfirmasi <span class="required">*</span></label>
          <input type="password" id="regPassConfirm" name="confirm_password" class="form-control" placeholder="Ulangi password" required minlength="8">
        </div>
      </div>

      <button type="submit" id="regBtn" class="sy-btn sy-btn-primary sy-btn-block sy-btn-lg" style="margin-top: 1rem;">
        <i class="fa-solid fa-circle-check"></i> Buat Akun & Mulai Pesan
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.25rem; font-size: 0.875rem;">
      Sudah punya akun? <a href="<?= BASE_URL ?>login.php" style="color: var(--primary); font-weight: 700;">Masuk di Sini</a>
    </div>
  </div>
</div>

<script>
async function handleRegister(e) {
  e.preventDefault();
  const alertBox = document.getElementById('registerAlert');
  const btn = document.getElementById('regBtn');
  alertBox.style.display = 'none';

  const pass = document.getElementById('regPass').value;
  const passConfirm = document.getElementById('regPassConfirm').value;

  if (pass.length < 8) {
    alertBox.style.backgroundColor = 'var(--danger-light)';
    alertBox.style.color = 'var(--danger)';
    alertBox.style.border = '1px solid #f6bebe';
    alertBox.style.display = 'block';
    alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Password minimal 8 karakter.';
    return;
  }

  if (pass !== passConfirm) {
    alertBox.style.backgroundColor = 'var(--danger-light)';
    alertBox.style.color = 'var(--danger)';
    alertBox.style.border = '1px solid #f6bebe';
    alertBox.style.display = 'block';
    alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Konfirmasi password tidak cocok.';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mendaftarkan...';

  const formData = new FormData(document.getElementById('registerForm'));

  try {
    const res = await fetch('<?= BASE_URL ?>api/auth/register.php', {
      method: 'POST',
      body: formData,
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();

    if (data.success) {
      alertBox.style.backgroundColor = 'var(--success-light)';
      alertBox.style.color = '#1e7441';
      alertBox.style.border = '1px solid #bce6cb';
      alertBox.style.display = 'block';
      alertBox.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;

      setTimeout(() => {
        window.location.href = data.data.redirect || '<?= BASE_URL ?>menu.php';
      }, 700);
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Buat Akun & Mulai Pesan';
      alertBox.style.backgroundColor = 'var(--danger-light)';
      alertBox.style.color = 'var(--danger)';
      alertBox.style.border = '1px solid #f6bebe';
      alertBox.style.display = 'block';
      alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + (data.message || 'Registrasi gagal.');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Buat Akun & Mulai Pesan';
    alertBox.style.backgroundColor = 'var(--danger-light)';
    alertBox.style.color = 'var(--danger)';
    alertBox.style.border = '1px solid #f6bebe';
    alertBox.style.display = 'block';
    alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Terjadi kesalahan koneksi server.';
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

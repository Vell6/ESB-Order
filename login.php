<?php
/**
 * RM bu ani - Login Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = user_role();
    if ($role === 'admin') header("Location: " . BASE_URL . "admin/dashboard.php");
    elseif ($role === 'cashier') header("Location: " . BASE_URL . "admin/orders.php");
    elseif ($role === 'kitchen') header("Location: " . BASE_URL . "admin/kitchen.php");
    else header("Location: " . BASE_URL . "menu.php");
    exit;
}

$pageTitle = "Masuk ke Akun - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-wrapper no-bottom-nav" style="justify-content: center; align-items: center; min-height: 100vh; padding: 1.5rem 1rem;">
  <div class="sy-card" style="width: 100%; max-width: 440px; box-shadow: var(--shadow-lg); border-radius: var(--radius-xl);">
    <div style="text-align: center; margin-bottom: 1.5rem;">
      <div class="app-brand-logo" style="width: 54px; height: 54px; margin: 0 auto 0.75rem auto; font-size: 1.6rem; border-radius: var(--radius-md);">
        <i class="fa-solid fa-utensils"></i>
      </div>
      <h2 style="font-weight: 800; font-size: 1.45rem; color: var(--text-primary); margin-bottom: 0.25rem;"><?= APP_NAME ?></h2>
      <p style="font-size: 0.85rem; color: var(--text-secondary);">Masuk untuk mulai memesan makanan favorit Anda</p>
    </div>

    <!-- Alert Box -->
    <div id="loginAlert" style="display:none; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; margin-bottom: 1.25rem;"></div>

    <form id="loginForm" onsubmit="handleLogin(event)">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label" for="loginEmail">Email <span class="required">*</span></label>
        <div style="position: relative;">
          <input type="email" id="loginEmail" name="email" class="form-control" placeholder="nama@email.com" required autofocus style="padding-left: 2.5rem;">
          <i class="fa-solid fa-envelope" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
        </div>
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
          <label class="form-label" for="loginPassword" style="margin:0;">Password <span class="required">*</span></label>
        </div>
        <div style="position: relative;">
          <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required style="padding-left: 2.5rem; padding-right: 2.5rem;">
          <i class="fa-solid fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
          <button type="button" onclick="togglePasswordVisibility()" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;">
            <i class="fa-solid fa-eye" id="passwordToggleIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" id="loginBtn" class="sy-btn sy-btn-primary sy-btn-block sy-btn-lg" style="margin-top: 1.25rem;">
        <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.25rem; font-size: 0.875rem;">
      Belum memiliki akun? <a href="<?= BASE_URL ?>register.php" style="color: var(--primary); font-weight: 700;">Daftar Akun Baru</a>
    </div>

    <!-- Quick Demo Accounts Switcher -->
    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed var(--border);">
      <p style="font-size: 0.775rem; color: var(--text-muted); text-align: center; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
        Akses Cepat Pengujian Demo:
      </p>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="fillCredentials('marvelmarvel3622@gmail.com', 'password123')">
          <i class="fa-solid fa-user"></i> Pelanggan
        </button>
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="fillCredentials('kasir@rmbuani.com', 'password123')">
          <i class="fa-solid fa-cash-register"></i> Kasir POS
        </button>
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="fillCredentials('dapur@rmbuani.com', 'password123')">
          <i class="fa-solid fa-utensils"></i> Dapur KDS
        </button>
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="fillCredentials('admin@rmbuani.com', 'password123')">
          <i class="fa-solid fa-user-shield"></i> Admin Utama
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function fillCredentials(email, pass) {
  document.getElementById('loginEmail').value = email;
  document.getElementById('loginPassword').value = pass;
}

function togglePasswordVisibility() {
  const input = document.getElementById('loginPassword');
  const icon = document.getElementById('passwordToggleIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa-solid fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa-solid fa-eye';
  }
}

async function handleLogin(e) {
  e.preventDefault();
  const alertBox = document.getElementById('loginAlert');
  const btn = document.getElementById('loginBtn');
  alertBox.style.display = 'none';

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

  const formData = new FormData(document.getElementById('loginForm'));

  try {
    const res = await fetch('<?= BASE_URL ?>api/auth/login.php', {
      method: 'POST',
      body: formData,
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();

    if (data.success) {
      alertBox.className = 'sy-toast-success';
      alertBox.style.backgroundColor = 'var(--success-light)';
      alertBox.style.color = '#1e7441';
      alertBox.style.border = '1px solid #bce6cb';
      alertBox.style.display = 'block';
      alertBox.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;

      setTimeout(() => {
        window.location.href = data.data.redirect || '<?= BASE_URL ?>menu.php';
      }, 500);
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang';
      alertBox.style.backgroundColor = 'var(--danger-light)';
      alertBox.style.color = 'var(--danger)';
      alertBox.style.border = '1px solid #f6bebe';
      alertBox.style.display = 'block';
      alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + (data.message || 'Login gagal.');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang';
    alertBox.style.backgroundColor = 'var(--danger-light)';
    alertBox.style.color = 'var(--danger)';
    alertBox.style.border = '1px solid #f6bebe';
    alertBox.style.display = 'block';
    alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Gagal terhubung ke server.';
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * RM bu ani - Customer Profile Page
 * Matches ESB Order Reference Screenshots 3 & 4
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

require_login('login.php');

$user = current_user();
$pageTitle = "Profil Saya - " . APP_NAME;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="app-wrapper">
  <main class="container-sm" style="margin-top: 1rem;">
    <!-- Profile View (Matching Screenshot 3) -->
    <div id="profileMainView">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
        <h2 style="font-size:1.35rem; font-weight:800; margin:0;">Profil</h2>
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="showEditProfile()" style="color:var(--primary); font-weight:700; border-color:transparent;">
          Ubah
        </button>
      </div>

      <!-- User Info Card -->
      <div class="sy-card" style="display:flex; align-items:center; gap:1rem; padding:1.25rem; margin-bottom:1.5rem; border-radius:var(--radius-lg);">
        <div style="width:64px; height:64px; border-radius:50%; background:#f1f5f9; color:#94a3b8; display:flex; align-items:center; justify-content:center; font-size:2rem; border:2px solid var(--border);">
          <i class="fa-solid fa-user"></i>
        </div>
        <div>
          <h3 style="font-size:1.15rem; font-weight:800; color:var(--text-primary); margin-bottom:0.25rem;">
            Hi, <span id="viewUserName"><?= e($user['name']) ?></span>
          </h3>
          <p style="font-size:0.875rem; color:var(--text-secondary); margin:0;" id="viewUserPhone">
            <?= e($user['phone'] ?: 'Nomor belum diisi') ?>
          </p>
          <span class="sy-badge badge-unpaid" style="font-size:0.7rem; padding:1px 6px; margin-top:4px;">
            <?= ucfirst(e($user['role'])) ?>
          </span>
        </div>
      </div>

      <!-- Menu Items List (Matching Screenshot 3) -->
      <div style="display:flex; flex-direction:column; gap:0.65rem; margin-bottom:2rem;">
        <a href="<?= BASE_URL ?>my-orders.php" class="sy-card" style="display:flex; justify-content:space-between; align-items:center; padding:1rem; text-decoration:none;">
          <div style="display:flex; align-items:center; gap:0.75rem;">
            <div style="width:36px; height:36px; border-radius:var(--radius-md); background:#fff1f2; color:var(--primary); display:flex; align-items:center; justify-content:center;">
              <i class="fa-solid fa-receipt"></i>
            </div>
            <span style="font-weight:600; font-size:0.95rem;">Riwayat Pesanan</span>
          </div>
          <i class="fa-solid fa-chevron-right" style="color:var(--text-muted); font-size:0.85rem;"></i>
        </a>

        <div class="sy-card" style="display:flex; justify-content:space-between; align-items:center; padding:1rem; cursor:pointer;" onclick="openOutletModal()">
          <div style="display:flex; align-items:center; gap:0.75rem;">
            <div style="width:36px; height:36px; border-radius:var(--radius-md); background:#e6f0fa; color:#1a56db; display:flex; align-items:center; justify-content:center;">
              <i class="fa-solid fa-store"></i>
            </div>
            <span style="font-weight:600; font-size:0.95rem;">Info Outlet & Jam Buka</span>
          </div>
          <i class="fa-solid fa-chevron-right" style="color:var(--text-muted); font-size:0.85rem;"></i>
        </div>

        <div class="sy-card" style="display:flex; justify-content:space-between; align-items:center; padding:1rem;">
          <div style="display:flex; align-items:center; gap:0.75rem;">
            <div style="width:36px; height:36px; border-radius:var(--radius-md); background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center;">
              <i class="fa-solid fa-globe"></i>
            </div>
            <span style="font-weight:600; font-size:0.95rem;">Bahasa</span>
          </div>
          <span style="font-size:0.85rem; color:var(--text-muted); font-weight:600;">Indonesia <i class="fa-solid fa-chevron-right" style="margin-left:4px;"></i></span>
        </div>

        <div class="sy-card" style="display:flex; justify-content:space-between; align-items:center; padding:1rem; cursor:pointer;" onclick="alert('RM bu ani berkomitmen menjaga kerahasiaan data pribadi pelanggan sesuai UU Perlindungan Data Pribadi.')">
          <div style="display:flex; align-items:center; gap:0.75rem;">
            <div style="width:36px; height:36px; border-radius:var(--radius-md); background:#fdf2f8; color:#be185d; display:flex; align-items:center; justify-content:center;">
              <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span style="font-weight:600; font-size:0.95rem;">Kebijakan Privasi</span>
          </div>
          <i class="fa-solid fa-chevron-right" style="color:var(--text-muted); font-size:0.85rem;"></i>
        </div>
      </div>

      <!-- Logout Button (Matching Screenshot 3 "Keluar") -->
      <div style="text-align:center;">
        <button type="button" class="sy-btn sy-btn-outline-primary sy-btn-block sy-btn-lg" onclick="handleLogout()" style="color:var(--danger); border-color:#fca5a5;">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
        </button>
      </div>
    </div>

    <!-- Edit Profile View (Matching Screenshot 4 "Ubah Profil") -->
    <div id="profileEditView" style="display:none;">
      <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem;">
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="hideEditProfile()" style="border-radius:50%; width:36px; height:36px; padding:0;">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 style="font-size:1.35rem; font-weight:800; margin:0;">Ubah Profil</h2>
      </div>

      <div class="sy-card" style="padding:1.5rem; border-radius:var(--radius-lg);">
        <form id="editProfileForm" onsubmit="saveProfile(event)">
          <?= csrf_field() ?>

          <div class="form-group">
            <label class="form-label" for="editName">Nama</label>
            <input type="text" id="editName" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="editPhone">Nomor Whatsapp</label>
            <input type="text" id="editPhone" name="phone" class="form-control" value="<?= e($user['phone']) ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="editEmail">Email</label>
            <input type="email" id="editEmail" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="editPassword">Password Baru (Kosongkan jika tidak diubah)</label>
            <input type="password" id="editPassword" name="password" class="form-control" placeholder="Min. 8 karakter">
          </div>

          <button type="submit" id="btnSaveProfile" class="sy-btn sy-btn-primary sy-btn-block sy-btn-lg" style="margin-top:1.5rem; background-color:#ea580c; border-color:#ea580c;">
            Simpan
          </button>
        </form>
      </div>
    </div>
  </main>
</div>

<script>
function showEditProfile() {
  document.getElementById('profileMainView').style.display = 'none';
  document.getElementById('profileEditView').style.display = 'block';
}

function hideEditProfile() {
  document.getElementById('profileEditView').style.display = 'none';
  document.getElementById('profileMainView').style.display = 'block';
}

async function saveProfile(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSaveProfile');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

  const formData = new FormData(document.getElementById('editProfileForm'));

  try {
    const res = await fetch('<?= BASE_URL ?>api/auth/profile.php', {
      method: 'POST',
      body: formData,
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    btn.disabled = false;
    btn.innerHTML = 'Simpan';

    if (data.success) {
      document.getElementById('viewUserName').textContent = data.data.name;
      document.getElementById('viewUserPhone').textContent = data.data.phone || 'Nomor belum diisi';
      showToast('Profil berhasil diperbarui!', 'success');
      hideEditProfile();
    } else {
      showToast(data.message, 'error');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = 'Simpan';
    showToast('Gagal menyimpan profil: ' + err.message, 'error');
  }
}

async function handleLogout() {
  if (confirm('Apakah Anda yakin ingin keluar dari akun ini?')) {
    try {
      const res = await apiFetch('<?= BASE_URL ?>api/auth/logout.php', { method: 'POST' });
      window.location.href = '<?= BASE_URL ?>index.php?msg=logged_out';
    } catch (e) {
      window.location.href = '<?= BASE_URL ?>logout.php';
    }
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

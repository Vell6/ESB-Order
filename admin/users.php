<?php
/**
 * RM bu ani - User & Staff Management (Admin Only)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role('admin', 'login.php');

$currentUser = current_user();
$db = getDB();

// Handle role update or status toggle
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    require_csrf();
    $targetId = (int)($_POST['target_user_id'] ?? 0);
    $newRole = trim($_POST['new_role'] ?? '');
    $newStatus = isset($_POST['is_active']) ? 1 : 0;

    if ($targetId > 0 && in_array($newRole, ['customer', 'admin', 'cashier', 'kitchen'])) {
        // Prevent admin from disabling their own account
        if ($targetId === (int)$currentUser['id'] && ($newStatus === 0 || $newRole !== 'admin')) {
            $error = 'Anda tidak dapat menonaktifkan atau mengubah role akun Anda sendiri!';
        } else {
            $stmt = $db->prepare("UPDATE users SET role = :role, is_active = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':role' => $newRole, ':status' => $newStatus, ':id' => $targetId]);
            $message = 'Akun pengguna berhasil diperbarui.';
        }
    }
}

// Fetch users
$roleFilter = trim($_GET['role'] ?? '');
$sql = "SELECT id, name, email, phone, role, is_active, created_at FROM users";
$params = [];
if (!empty($roleFilter) && $roleFilter !== 'all') {
    $sql .= " WHERE role = :role";
    $params[':role'] = $roleFilter;
}
$sql .= " ORDER BY id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = "Kelola Pengguna & Staf - " . APP_NAME;
$bodyClass = "admin-body";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <!-- Sidebar -->
  <aside class="admin-sidebar no-print">
    <div class="admin-sidebar-header">
      <div class="app-brand-logo" style="width:32px; height:32px; font-size:1rem;">
        <i class="fa-solid fa-utensils"></i>
      </div>
      <span style="font-weight:700; font-size:1.1rem; color:#ffffff;"><?= APP_NAME ?></span>
    </div>

    <nav class="admin-sidebar-nav">
      <a href="<?= BASE_URL ?>admin/dashboard.php" class="admin-nav-item">
        <i class="fa-solid fa-chart-line"></i> Dashboard
      </a>
      <a href="<?= BASE_URL ?>admin/orders.php" class="admin-nav-item">
        <i class="fa-solid fa-cash-register"></i> Kasir / POS
      </a>
      <a href="<?= BASE_URL ?>admin/menu.php" class="admin-nav-item">
        <i class="fa-solid fa-book-open"></i> Kelola Menu
      </a>
      <a href="<?= BASE_URL ?>admin/reports.php" class="admin-nav-item">
        <i class="fa-solid fa-file-invoice-dollar"></i> Laporan Pendapatan
      </a>
      <a href="<?= BASE_URL ?>admin/users.php" class="admin-nav-item active">
        <i class="fa-solid fa-users-gear"></i> Kelola Pengguna
      </a>
      <div style="margin-top:auto; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.08);">
        <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item" style="color:#f87171;">
          <i class="fa-solid fa-right-from-bracket"></i> Keluar
        </a>
      </div>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="admin-main">
    <header class="admin-topbar no-print">
      <div style="display:flex; align-items:center; gap:0.5rem;">
        <h2 style="font-size:1.15rem; font-weight:700; margin:0;"><i class="fa-solid fa-users-gear" style="color:var(--primary);"></i> Kelola Pengguna & Hak Akses</h2>
      </div>
    </header>

    <div class="admin-content">
      <?php if ($message): ?>
        <div style="background:#ecfdf5; border:1px solid #bce6cb; border-radius:var(--radius-md); padding:0.85rem 1rem; color:#166534; font-size:0.875rem; margin-bottom:1.25rem;">
          <i class="fa-solid fa-circle-check"></i> <?= e($message) ?>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div style="background:var(--danger-light); border:1px solid #f6bebe; border-radius:var(--radius-md); padding:0.85rem 1rem; color:var(--danger); font-size:0.875rem; margin-bottom:1.25rem;">
          <i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?>
        </div>
      <?php endif; ?>

      <!-- Role Filter Pills -->
      <div style="display:flex; gap:0.5rem; margin-bottom:1.25rem;">
        <a href="<?= BASE_URL ?>admin/users.php?role=all" class="sy-btn <?= (empty($roleFilter) || $roleFilter === 'all') ? 'sy-btn-primary' : 'sy-btn-outline' ?> sy-btn-sm sy-btn-pill">
          Semua Pengguna (<?= count($users) ?>)
        </a>
        <a href="<?= BASE_URL ?>admin/users.php?role=customer" class="sy-btn <?= ($roleFilter === 'customer') ? 'sy-btn-primary' : 'sy-btn-outline' ?> sy-btn-sm sy-btn-pill">
          Pelanggan
        </a>
        <a href="<?= BASE_URL ?>admin/users.php?role=cashier" class="sy-btn <?= ($roleFilter === 'cashier') ? 'sy-btn-primary' : 'sy-btn-outline' ?> sy-btn-sm sy-btn-pill">
          Kasir
        </a>
        <a href="<?= BASE_URL ?>admin/users.php?role=kitchen" class="sy-btn <?= ($roleFilter === 'kitchen') ? 'sy-btn-primary' : 'sy-btn-outline' ?> sy-btn-sm sy-btn-pill">
          Dapur
        </a>
        <a href="<?= BASE_URL ?>admin/users.php?role=admin" class="sy-btn <?= ($roleFilter === 'admin') ? 'sy-btn-primary' : 'sy-btn-outline' ?> sy-btn-sm sy-btn-pill">
          Admin
        </a>
      </div>

      <!-- Users Table -->
      <div class="sy-card" style="padding:0; overflow:hidden;">
        <div class="sy-table-responsive" style="border:none;">
          <table class="sy-table">
            <thead>
              <tr>
                <th>Nama Pengguna</th>
                <th>Email Login</th>
                <th>Telepon / WA</th>
                <th>Role Akses</th>
                <th>Status Akun</th>
                <th>Waktu Daftar</th>
                <th style="text-align:right;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td>
                    <div style="font-weight:700;"><?= e($u['name']) ?></div>
                    <?php if ((int)$u['id'] === (int)$currentUser['id']): ?>
                      <span class="sy-badge badge-unpaid" style="font-size:0.65rem; padding:1px 5px;">Akun Anda</span>
                    <?php endif; ?>
                  </td>
                  <td><?= e($u['email']) ?></td>
                  <td><?= e($u['phone'] ?: '-') ?></td>
                  <td>
                    <span class="sy-badge" style="background:#f1f5f9; color:#334155; font-weight:700;">
                      <?= strtoupper(e($u['role'])) ?>
                    </span>
                  </td>
                  <td>
                    <span class="sy-badge <?= $u['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>">
                      <?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                  </td>
                  <td style="font-size:0.8rem; color:var(--text-secondary);"><?= format_datetime($u['created_at'], false) ?></td>
                  <td style="text-align:right;">
                    <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="openEditUserModal(<?= htmlspecialchars(json_encode($u)) ?>)">
                      <i class="fa-solid fa-user-pen"></i> Ubah Role
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Modal Edit User Role -->
<div id="userModal" class="sy-modal-overlay">
  <div class="sy-modal-dialog">
    <div class="sy-modal-header">
      <h3 style="margin:0;"><i class="fa-solid fa-user-pen"></i> Ubah Role & Status Pengguna</h3>
      <button type="button" class="sy-modal-close" onclick="closeModal('userModal')">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form method="POST" action="">
      <div class="sy-modal-body">
        <?= csrf_field() ?>
        <input type="hidden" name="target_user_id" id="modalUserId">

        <div class="form-group">
          <label class="form-label">Nama Pengguna:</label>
          <input type="text" id="modalUserName" class="form-control" readonly style="background:#f8fafc;">
        </div>

        <div class="form-group">
          <label class="form-label">Email Pengguna:</label>
          <input type="text" id="modalUserEmail" class="form-control" readonly style="background:#f8fafc;">
        </div>

        <div class="form-group">
          <label class="form-label">Role Akses Sistem <span class="required">*</span></label>
          <select name="new_role" id="modalUserRole" class="form-control" required>
            <option value="customer">Customer (Pelanggan Pemesan)</option>
            <option value="cashier">Cashier (Kasir & POS Pembayaran)</option>
            <option value="kitchen">Kitchen (Bagian Dapur KDS)</option>
            <option value="admin">Admin (Administrator Penuh)</option>
          </select>
        </div>

        <div class="form-group">
          <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
            <input type="checkbox" name="is_active" id="modalUserStatus" value="1" style="width:18px; height:18px; accent-color:var(--success);">
            <span style="font-weight:600; font-size:0.9rem;">Status Akun Aktif</span>
          </label>
        </div>
      </div>
      <div class="sy-modal-footer">
        <button type="button" class="sy-btn sy-btn-secondary" onclick="closeModal('userModal')">Batal</button>
        <button type="submit" name="update_user" class="sy-btn sy-btn-primary">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditUserModal(user) {
  document.getElementById('modalUserId').value = user.id;
  document.getElementById('modalUserName').value = user.name;
  document.getElementById('modalUserEmail').value = user.email;
  document.getElementById('modalUserRole').value = user.role;
  document.getElementById('modalUserStatus').checked = (parseInt(user.is_active, 10) === 1);
  openModal('userModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

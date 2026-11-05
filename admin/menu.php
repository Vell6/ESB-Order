<?php
/**
 * RM bu ani - Menu Management (Admin Only)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role('admin', 'login.php');

$currentUser = current_user();

try {
    $db = getDB();
    $categories = $db->query("SELECT DISTINCT category FROM menus ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

    $catFilter = trim($_GET['cat'] ?? '');
    $sql = "SELECT * FROM menus";
    $params = [];
    if (!empty($catFilter) && $catFilter !== 'all') {
        $sql .= " WHERE category = :cat";
        $params[':cat'] = $catFilter;
    }
    $sql .= " ORDER BY category ASC, id ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $menus = $stmt->fetchAll();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "Kelola Menu Restoran - " . APP_NAME;
$bodyClass = "admin-body";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <!-- Admin Sidebar -->
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
      <a href="<?= BASE_URL ?>admin/kitchen.php" class="admin-nav-item" target="_blank">
        <i class="fa-solid fa-fire-burner"></i> Layar Dapur (KDS) <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.75rem; margin-left:auto;"></i>
      </a>
      <a href="<?= BASE_URL ?>admin/menu.php" class="admin-nav-item active">
        <i class="fa-solid fa-book-open"></i> Kelola Menu
      </a>
      <a href="<?= BASE_URL ?>admin/reports.php" class="admin-nav-item">
        <i class="fa-solid fa-file-invoice-dollar"></i> Laporan Pendapatan
      </a>
      <a href="<?= BASE_URL ?>admin/users.php" class="admin-nav-item">
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
        <h2 style="font-size:1.15rem; font-weight:700; margin:0;"><i class="fa-solid fa-book-open" style="color:var(--primary);"></i> Kelola Menu & Ketersediaan</h2>
      </div>
      <a href="<?= BASE_URL ?>admin/menu-form.php" class="sy-btn sy-btn-primary">
        <i class="fa-solid fa-plus"></i> Tambah Menu Baru
      </a>
    </header>

    <div class="admin-content">
      <!-- Category Filter Pills -->
      <div style="display:flex; gap:0.5rem; margin-bottom:1.25rem; overflow-x:auto; padding-bottom:4px;">
        <a href="<?= BASE_URL ?>admin/menu.php?cat=all" class="sy-btn <?= (empty($catFilter) || $catFilter === 'all') ? 'sy-btn-primary' : 'sy-btn-outline' ?> sy-btn-sm sy-btn-pill">
          Semua Kategori (<?= count($menus) ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
          <a href="<?= BASE_URL ?>admin/menu.php?cat=<?= urlencode($cat) ?>" class="sy-btn <?= ($catFilter === $cat) ? 'sy-btn-primary' : 'sy-btn-outline' ?> sy-btn-sm sy-btn-pill">
            <?= e($cat) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Menus Table -->
      <div class="sy-card" style="padding:0; overflow:hidden;">
        <div class="sy-table-responsive" style="border:none;">
          <table class="sy-table">
            <thead>
              <tr>
                <th style="width:70px;">Foto</th>
                <th>Nama Menu</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Level Pedas</th>
                <th>Status Ketersediaan</th>
                <th style="text-align:right;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($menus as $m): ?>
                <tr>
                  <td>
                    <img src="<?= BASE_URL . e($m['image'] ?: 'assets/images/logo.svg') ?>" alt="<?= e($m['name']) ?>" style="width:50px; height:50px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid var(--border);">
                  </td>
                  <td>
                    <div style="font-weight:700; font-size:0.95rem;"><?= e($m['name']) ?></div>
                    <div style="font-size:0.75rem; color:var(--text-muted); max-width:280px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">
                      <?= e($m['description']) ?>
                    </div>
                  </td>
                  <td>
                    <span class="sy-badge" style="background:#f1f5f9; color:#475569;"><?= e($m['category']) ?></span>
                  </td>
                  <td style="font-weight:700; color:var(--primary);"><?= format_rupiah($m['price']) ?></td>
                  <td>
                    <?php if ($m['supports_spicy']): ?>
                      <span class="sy-badge badge-spicy"><i class="fa-solid fa-pepper-hot"></i> Level 1-5</span>
                    <?php else: ?>
                      <span style="font-size:0.8rem; color:var(--text-muted);">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <label style="display:inline-flex; align-items:center; gap:0.4rem; cursor:pointer;">
                      <input type="checkbox" onchange="toggleMenuAvailability(<?= $m['id'] ?>, this.checked)" <?= $m['is_available'] ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--success);">
                      <span style="font-size:0.85rem; font-weight:600; color:<?= $m['is_available'] ? 'var(--success)' : 'var(--danger)' ?>;">
                        <?= $m['is_available'] ? 'Tersedia' : 'Habis' ?>
                      </span>
                    </label>
                  </td>
                  <td style="text-align:right;">
                    <div style="display:flex; justify-content:flex-end; gap:0.4rem;">
                      <a href="<?= BASE_URL ?>admin/menu-form.php?id=<?= $m['id'] ?>" class="sy-btn sy-btn-outline sy-btn-sm" title="Edit Menu">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                      </a>
                      <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="deleteMenu(<?= $m['id'] ?>, '<?= e(addslashes($m['name'])) ?>')" style="color:var(--danger); border-color:#fca5a5;" title="Hapus Menu">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </div>
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

<script>
async function toggleMenuAvailability(id, isAvailable) {
  try {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('is_available', isAvailable ? 1 : 0);

    const res = await apiFetch('<?= BASE_URL ?>api/menu/update.php', {
      method: 'POST',
      body: formData
    });

    if (res.success) {
      showToast('Ketersediaan menu diperbarui', 'success', 2000);
    } else {
      showToast(res.message, 'error');
    }
  } catch (err) {
    showToast('Gagal mengubah ketersediaan: ' + err.message, 'error');
  }
}

async function deleteMenu(id, name) {
  if (!confirm(`Apakah Anda yakin ingin menghapus / menonaktifkan menu "${name}"?`)) {
    return;
  }

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/menu/delete.php', {
      method: 'POST',
      body: JSON.stringify({ id: id })
    });

    if (res.success) {
      showToast(res.message, 'success');
      setTimeout(() => window.location.reload(), 700);
    } else {
      showToast(res.message, 'error');
    }
  } catch (err) {
    showToast('Gagal menghapus menu: ' + err.message, 'error');
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

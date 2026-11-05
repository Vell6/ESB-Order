<?php
/**
 * RM bu ani - Menu Form (Add / Edit)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role('admin', 'login.php');

$id = (int)($_GET['id'] ?? 0);
$isEdit = ($id > 0);
$menu = [
    'name'           => '',
    'description'    => '',
    'price'          => '',
    'category'       => 'Makanan Utama',
    'supports_spicy' => 0,
    'is_available'   => 1,
    'image'          => ''
];

$categories = ['Makanan Utama', 'Paket Hemat', 'Dimsum & Snack', 'Minuman Segar', 'Lain-lain'];

if ($isEdit) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM menus WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $found = $stmt->fetch();
        if ($found) {
            $menu = $found;
        } else {
            die("Menu tidak ditemukan");
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

$pageTitle = ($isEdit ? "Edit Menu" : "Tambah Menu Baru") . " - " . APP_NAME;
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
      <a href="<?= BASE_URL ?>admin/menu.php" class="admin-nav-item active">
        <i class="fa-solid fa-book-open"></i> Kelola Menu
      </a>
      <a href="<?= BASE_URL ?>admin/reports.php" class="admin-nav-item">
        <i class="fa-solid fa-file-invoice-dollar"></i> Laporan Pendapatan
      </a>
      <a href="<?= BASE_URL ?>admin/users.php" class="admin-nav-item">
        <i class="fa-solid fa-users-gear"></i> Kelola Pengguna
      </a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="admin-main">
    <header class="admin-topbar no-print">
      <div style="display:flex; align-items:center; gap:0.5rem;">
        <a href="<?= BASE_URL ?>admin/menu.php" class="sy-btn sy-btn-outline sy-btn-sm" style="border-radius:50%; width:32px; height:32px; padding:0;">
          <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h2 style="font-size:1.15rem; font-weight:700; margin:0;">
          <?= $isEdit ? 'Edit Menu: ' . e($menu['name']) : 'Tambah Menu Baru' ?>
        </h2>
      </div>
    </header>

    <div class="admin-content">
      <div class="sy-card" style="max-width:640px; margin:0 auto; padding:1.5rem; border-radius:var(--radius-lg);">
        <form id="menuForm" onsubmit="saveMenu(event)">
          <?= csrf_field() ?>
          <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
          <?php endif; ?>

          <div class="form-group">
            <label class="form-label" for="menuName">Nama Menu <span class="required">*</span></label>
            <input type="text" id="menuName" name="name" class="form-control" value="<?= e($menu['name']) ?>" placeholder="Contoh: Bebek Goreng Sambal Korek" required>
          </div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
            <div class="form-group">
              <label class="form-label" for="menuPrice">Harga (Rp) <span class="required">*</span></label>
              <input type="number" id="menuPrice" name="price" class="form-control" value="<?= (float)$menu['price'] ?>" placeholder="25000" min="1000" step="500" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="menuCategory">Kategori <span class="required">*</span></label>
              <select id="menuCategory" name="category" class="form-control" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= e($cat) ?>" <?= ($menu['category'] === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="menuDesc">Deskripsi Menu</label>
            <textarea id="menuDesc" name="description" class="form-control" rows="3" placeholder="Jelaskan cita rasa, bumbu rahasia, dan pelengkap menu ini..."><?= e($menu['description']) ?></textarea>
          </div>

          <!-- Image Upload with Preview -->
          <div class="form-group">
            <label class="form-label" for="menuImage">Gambar / Foto Menu (JPG, PNG, WEBP, Maks. 2MB)</label>
            <input type="file" id="menuImage" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewImage(event)">
            <?php if ($menu['image']): ?>
              <div style="margin-top:0.5rem; display:flex; align-items:center; gap:0.75rem;">
                <img id="imgPreview" src="<?= BASE_URL . e($menu['image']) ?>" alt="Preview" style="width:70px; height:70px; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--border);">
                <span style="font-size:0.75rem; color:var(--text-muted);">Foto saat ini</span>
              </div>
            <?php else: ?>
              <div style="margin-top:0.5rem; display:none;" id="imgPreviewContainer">
                <img id="imgPreview" src="#" alt="Preview" style="width:70px; height:70px; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--border);">
              </div>
            <?php endif; ?>
          </div>

          <!-- Feature Toggles -->
          <div style="display:flex; flex-direction:column; gap:0.75rem; margin:1.25rem 0; padding:1rem; background:#f8fafc; border-radius:var(--radius-md); border:1px solid var(--border-light);">
            <label style="display:flex; align-items:center; gap:0.65rem; cursor:pointer;">
              <input type="checkbox" name="supports_spicy" value="1" <?= $menu['supports_spicy'] ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--primary);">
              <div>
                <span style="font-weight:700; font-size:0.9rem;">Mendukung Pilihan Tingkat Kepedasan (Level 1 - 5)</span>
                <div style="font-size:0.75rem; color:var(--text-muted);">Jika dicentang, pelanggan dapat memilih tingkat pedas saat menambah ke keranjang.</div>
              </div>
            </label>

            <label style="display:flex; align-items:center; gap:0.65rem; cursor:pointer;">
              <input type="checkbox" name="is_available" value="1" <?= $menu['is_available'] ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--success);">
              <div>
                <span style="font-weight:700; font-size:0.9rem;">Menu Tersedia untuk Dipesan</span>
                <div style="font-size:0.75rem; color:var(--text-muted);">Hilangkan centang jika bahan baku sedang habis untuk mencegah pemesanan.</div>
              </div>
            </label>
          </div>

          <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
            <a href="<?= BASE_URL ?>admin/menu.php" class="sy-btn sy-btn-outline" style="flex:1;">Batal</a>
            <button type="submit" id="btnSaveMenu" class="sy-btn sy-btn-primary" style="flex:2;">
              <i class="fa-solid fa-floppy-disk"></i> Simpan Data Menu
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<script>
function previewImage(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = document.getElementById('imgPreview');
      const container = document.getElementById('imgPreviewContainer');
      img.src = e.target.result;
      if (container) container.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}

async function saveMenu(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSaveMenu');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

  const formData = new FormData(document.getElementById('menuForm'));
  // Ensure checkboxes send 0 if unchecked
  if (!formData.has('supports_spicy')) formData.append('supports_spicy', 0);
  if (!formData.has('is_available')) formData.append('is_available', 0);

  const endpoint = <?= $isEdit ? "'api/menu/update.php'" : "'api/menu/create.php'" ?>;

  try {
    const res = await fetch('<?= BASE_URL ?>' + endpoint, {
      method: 'POST',
      body: formData,
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Data Menu';

    if (data.success) {
      showToast('Menu berhasil disimpan!', 'success');
      setTimeout(() => {
        window.location.href = '<?= BASE_URL ?>admin/menu.php';
      }, 700);
    } else {
      showToast(data.message, 'error');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Data Menu';
    showToast('Gagal menyimpan menu: ' + err.message, 'error');
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * RM bu ani - Landing Page & QR Entry Point
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Capture and validate Table number from QR Code URL parameter (?meja=01)
if (isset($_GET['meja'])) {
    $qrTable = sanitize_table_number($_GET['meja']);
    if ($qrTable) {
        $_SESSION['selected_table'] = $qrTable;
        $_SESSION['order_type'] = 'dine_in';
    }
}

// Reset table or order type if requested
if (isset($_GET['change_table'])) {
    unset($_SESSION['selected_table']);
}
if (isset($_GET['change_type'])) {
    $_SESSION['order_type'] = ($_SESSION['order_type'] ?? 'dine_in') === 'dine_in' ? 'takeaway' : 'dine_in';
}

// Fetch featured menus for landing page showcase
$featuredMenus = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM menus WHERE is_available = 1 ORDER BY id ASC LIMIT 6");
    $featuredMenus = $stmt->fetchAll();
} catch (Exception $e) {
    // If DB not installed yet, redirect to installer
    header("Location: " . BASE_URL . "setup_db.php");
    exit;
}

$pageTitle = "Selamat Datang di " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="app-wrapper">
  <!-- Hero Banner (ESB Style) -->
  <section class="outlet-hero-banner" style="background-image: url('assets/images/ayam-bakar.svg');">
    <div class="outlet-hero-content container">
      <div>
        <h1 style="color:var(--white); font-weight:800; font-size:1.75rem; text-shadow:0 2px 8px rgba(0,0,0,0.6); margin-bottom:0.25rem;">
          <?= APP_NAME ?>
        </h1>
        <p style="color:rgba(255,255,255,0.9); font-size:0.875rem;">
          <?= APP_TAGLINE ?>
        </p>
        <div class="outlet-badge-hours" onclick="openOutletModal()" style="cursor:pointer;">
          <i class="fa-regular fa-clock"></i> Buka Hari Ini <?= APP_OPENING_HOURS ?> <i class="fa-solid fa-chevron-right" style="font-size:0.65rem;"></i>
        </div>
      </div>
    </div>
  </section>

  <!-- Outlet Info Card (Clickable to view operating hours & address) -->
  <div class="container-sm">
    <div class="outlet-info-card" onclick="openOutletModal()">
      <div style="display:flex; align-items:center; gap:0.75rem;">
        <div class="app-brand-logo" style="width:42px; height:42px; font-size:1.25rem;">
          <i class="fa-solid fa-store"></i>
        </div>
        <div>
          <h3 style="font-size:1.05rem; font-weight:700; margin-bottom:2px;"><?= APP_NAME ?> Pontianak</h3>
          <p style="font-size:0.8rem; margin:0;"><i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> <?= APP_ADDRESS ?></p>
        </div>
      </div>
      <i class="fa-solid fa-chevron-right" style="color:var(--text-muted);"></i>
    </div>
  </div>

  <main class="container-sm" style="margin-top:1rem;">
    <!-- Step 1: Pilihan Jenis Pesanan (PRD Section 4.5) -->
    <div style="margin-bottom:1.5rem;">
      <h3 style="font-size:1.15rem; font-weight:700; margin-bottom:0.85rem; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-bell-concierge" style="color:var(--primary);"></i> Pilih Cara Pesan
      </h3>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.85rem;">
        <!-- Card 1: Makan di Tempat -->
        <div class="sy-tile <?= get_session_order_type() === 'dine_in' ? 'active' : '' ?>" onclick="selectOrderType('dine_in')">
          <div class="sy-tile-icon">
            <i class="fa-solid fa-chair"></i>
          </div>
          <div class="sy-tile-title">Makan di Tempat</div>
          <div class="sy-tile-desc">
            <?= get_session_table() ? 'Nomor Meja: <strong>' . e(get_session_table()) . '</strong>' : 'Pesan di Meja Makan' ?>
          </div>
        </div>

        <!-- Card 2: Take Away -->
        <div class="sy-tile <?= get_session_order_type() === 'takeaway' ? 'active' : '' ?>" onclick="selectOrderType('takeaway')">
          <div class="sy-tile-icon">
            <i class="fa-solid fa-bag-shopping"></i>
          </div>
          <div class="sy-tile-title">Take Away</div>
          <div class="sy-tile-desc">Bungkus Bawa Pulang</div>
        </div>
      </div>
    </div>

    <!-- Table Selection Dialog if Dine-In selected -->
    <div id="tableSelectorBox" style="background:var(--white); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1rem; margin-bottom:1.5rem; <?= get_session_order_type() === 'takeaway' ? 'display:none;' : '' ?>">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
        <span style="font-weight:600; font-size:0.9rem;">
          <i class="fa-solid fa-qrcode" style="color:var(--primary);"></i> Nomor Meja Anda:
        </span>
        <span class="sy-badge badge-dinein" id="currentTableBadge">
          Meja <?= e(get_session_table() ?: 'Belum Dipilih') ?>
        </span>
      </div>

      <div style="display:flex; gap:0.5rem; align-items:center;">
        <select id="tableSelectInput" class="form-control" style="flex:1;" onchange="updateSelectedTable(this.value)">
          <option value="">-- Pilih Nomor Meja --</option>
          <?php foreach (get_restaurant_tables() as $t): ?>
            <option value="<?= $t ?>" <?= (get_session_table() === $t) ? 'selected' : '' ?>>Meja <?= $t ?></option>
          <?php endforeach; ?>
        </select>
        <a href="<?= BASE_URL ?>menu.php" class="sy-btn sy-btn-primary" style="white-space:nowrap;">
          Buka Menu <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
      <p class="form-hint" style="margin-top:0.4rem;">
        *Anda juga dapat memindai stiker QR Code yang tertera di atas meja restoran.
      </p>
    </div>

    <!-- Action CTA Banner -->
    <div style="background:linear-gradient(135deg, #e63946 0%, #c1121f 100%); border-radius:var(--radius-lg); padding:1.25rem; color:var(--white); margin-bottom:2rem; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h3 style="color:var(--white); font-weight:800; font-size:1.15rem; margin-bottom:0.25rem;">Lapar? Pesan Langsung!</h3>
        <p style="color:rgba(255,255,255,0.85); font-size:0.8rem; margin:0;">Pilih makanan favorit Anda tanpa perlu antre menunggu pelayan.</p>
      </div>
      <a href="<?= BASE_URL ?>menu.php" class="sy-btn sy-btn-pill" style="background:#ffffff; color:var(--primary); font-weight:700; white-space:nowrap; box-shadow:0 4px 10px rgba(0,0,0,0.15);">
        Mulai Pesan <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <!-- Menu Unggulan Section -->
    <div class="section-title">
      <h2><i class="fa-solid fa-fire" style="color:var(--primary);"></i> Menu Unggulan</h2>
      <a href="<?= BASE_URL ?>menu.php" style="color:var(--primary); font-weight:600; font-size:0.875rem;">
        Lihat Semua <i class="fa-solid fa-angle-right"></i>
      </a>
    </div>

    <div class="menu-grid" style="padding:0 0 1.5rem 0;">
      <?php foreach ($featuredMenus as $menu): ?>
        <div class="menu-card" onclick="window.location.href='<?= BASE_URL ?>menu.php?open=<?= $menu['id'] ?>'" style="cursor:pointer;">
          <div class="menu-card-img-wrapper">
            <img src="<?= e($menu['image'] ?: 'assets/images/logo.svg') ?>" alt="<?= e($menu['name']) ?>" class="menu-card-img" loading="lazy">
            <?php if ($menu['supports_spicy']): ?>
              <span class="sy-badge badge-spicy" style="position:absolute; top:8px; right:8px;">
                <i class="fa-solid fa-pepper-hot"></i> Bisa Pedas
              </span>
            <?php endif; ?>
          </div>
          <div class="menu-card-body">
            <h4 class="menu-card-title"><?= e($menu['name']) ?></h4>
            <p class="menu-card-desc"><?= e($menu['description']) ?></p>
            <div class="menu-card-footer">
              <span class="menu-card-price"><?= format_rupiah($menu['price']) ?></span>
              <button class="sy-btn sy-btn-primary sy-btn-sm sy-btn-pill" type="button" onclick="event.stopPropagation(); window.location.href='<?= BASE_URL ?>menu.php?open=<?= $menu['id'] ?>'">
                <i class="fa-solid fa-plus"></i> Tambah
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Quick Info Cards -->
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:0.75rem; margin-bottom:2rem; text-align:center;">
      <div class="sy-card" style="padding:1rem 0.5rem;">
        <i class="fa-solid fa-qrcode" style="font-size:1.75rem; color:var(--primary); margin-bottom:0.5rem;"></i>
        <h4 style="font-size:0.85rem; font-weight:700;">Scan QR Meja</h4>
        <p style="font-size:0.75rem; margin:0;">Pesan langsung dari tempat duduk</p>
      </div>
      <div class="sy-card" style="padding:1rem 0.5rem;">
        <i class="fa-solid fa-barcode" style="font-size:1.75rem; color:var(--primary); margin-bottom:0.5rem;"></i>
        <h4 style="font-size:0.85rem; font-weight:700;">Barcode Kasir</h4>
        <p style="font-size:0.75rem; margin:0;">Bayar cepat tanpa salah hitung</p>
      </div>
      <div class="sy-card" style="padding:1rem 0.5rem;">
        <i class="fa-solid fa-kitchen-set" style="font-size:1.75rem; color:var(--primary); margin-bottom:0.5rem;"></i>
        <h4 style="font-size:0.85rem; font-weight:700;">Live Kitchen</h4>
        <p style="font-size:0.75rem; margin:0;">Pantau status makanan real-time</p>
      </div>
    </div>
  </main>
</div>

<script>
function selectOrderType(type) {
  fetch('<?= BASE_URL ?>api/auth/profile.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order_type: type })
  }).catch(() => {});

  if (type === 'takeaway') {
    document.getElementById('tableSelectorBox').style.display = 'none';
    window.location.href = '<?= BASE_URL ?>menu.php?type=takeaway';
  } else {
    document.getElementById('tableSelectorBox').style.display = 'block';
  }
}

function updateSelectedTable(table) {
  if (!table) return;
  fetch('<?= BASE_URL ?>index.php?meja=' + encodeURIComponent(table))
    .then(() => {
      document.getElementById('currentTableBadge').textContent = 'Meja ' + table;
      showToast('Nomor Meja ' + table + ' telah disimpan', 'success');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

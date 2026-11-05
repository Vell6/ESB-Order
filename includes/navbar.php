<?php
/**
 * RM bu ani - Customer Navbar Component
 */
$currentTable = get_session_table();
$orderType = get_session_order_type();
$user = current_user();
$activePage = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!-- Top Navigation Header -->
<header class="app-header no-print">
  <div class="container">
    <nav class="app-navbar">
      <a href="<?= BASE_URL ?>index.php" class="app-brand">
        <div class="app-brand-logo">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span><?= APP_NAME ?></span>
      </a>

      <!-- Table / Order Type Indicator Pill -->
      <?php if ($orderType === 'takeaway'): ?>
        <div class="sy-badge badge-takeaway" style="cursor: pointer;" onclick="window.location.href='<?= BASE_URL ?>index.php?change_type=1'">
          <i class="fa-solid fa-bag-shopping"></i> Take Away (Bungkus)
        </div>
      <?php elseif ($currentTable): ?>
        <div class="sy-badge badge-dinein" style="cursor: pointer;" onclick="window.location.href='<?= BASE_URL ?>index.php?change_table=1'">
          <i class="fa-solid fa-chair"></i> Meja <?= e($currentTable) ?>
        </div>
      <?php endif; ?>

      <!-- Desktop Nav Links -->
      <div class="app-nav-links d-none-mobile">
        <a href="<?= BASE_URL ?>menu.php" class="app-nav-link <?= $activePage === 'menu.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-book-open"></i> Menu
        </a>
        <a href="<?= BASE_URL ?>my-orders.php" class="app-nav-link <?= $activePage === 'my-orders.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-receipt"></i> Pesanan Saya
        </a>
        <a href="<?= BASE_URL ?>cart.php" class="app-nav-link <?= $activePage === 'cart.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-cart-shopping"></i> Keranjang
          <span class="badge-counter cart-count-badge" style="display:none; position:static;">0</span>
        </a>
        <?php if ($user): ?>
          <a href="<?= BASE_URL ?>profile.php" class="app-nav-link <?= $activePage === 'profile.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user"></i> <?= e($user['name']) ?>
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>login.php" class="sy-btn sy-btn-primary sy-btn-sm">
            <i class="fa-solid fa-right-to-bracket"></i> Masuk
          </a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<!-- Mobile Bottom Navigation Bar (Matching ESB Mobile App) -->
<nav class="bottom-nav no-print" aria-label="Navigasi Utama">
  <a href="<?= BASE_URL ?>menu.php" class="bottom-nav-item <?= in_array($activePage, ['menu.php', 'index.php']) ? 'active' : '' ?>">
    <i class="fa-solid fa-utensils"></i>
    <span>Menu</span>
  </a>

  <a href="<?= BASE_URL ?>my-orders.php" class="bottom-nav-item <?= in_array($activePage, ['my-orders.php', 'order-detail.php']) ? 'active' : '' ?>">
    <i class="fa-solid fa-receipt"></i>
    <span>Pesanan</span>
  </a>

  <a href="<?= BASE_URL ?>cart.php" class="bottom-nav-item <?= $activePage === 'cart.php' ? 'active' : '' ?>" id="bottomNavCart">
    <i class="fa-solid fa-cart-shopping"></i>
    <span>Keranjang</span>
    <span class="badge-counter cart-count-badge" style="display:none;">0</span>
  </a>

  <a href="<?= BASE_URL ?>profile.php" class="bottom-nav-item <?= in_array($activePage, ['profile.php', 'login.php', 'register.php']) ? 'active' : '' ?>">
    <i class="fa-solid fa-user"></i>
    <span><?= $user ? 'Profil' : 'Masuk' ?></span>
  </a>
</nav>

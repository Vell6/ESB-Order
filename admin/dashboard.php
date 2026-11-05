<?php
/**
 * RM bu ani - Admin Dashboard
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

    // Stats
    $revToday = (float)$db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid' AND status != 'cancelled' AND DATE(paid_at) = CURDATE()")->fetchColumn();
    $revMonth = (float)$db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid' AND status != 'cancelled' AND YEAR(paid_at) = YEAR(CURDATE()) AND MONTH(paid_at) = MONTH(CURDATE())")->fetchColumn();

    $counts = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) as unpaid,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM orders
    ")->fetch();

    // Recent 8 transactions
    $recentOrders = $db->query("
        SELECT id, order_code, customer_name, order_type, table_number, total, status, payment_status, created_at
        FROM orders
        ORDER BY id DESC LIMIT 8
    ")->fetchAll();

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "Dashboard Admin - " . APP_NAME;
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
      <a href="<?= BASE_URL ?>admin/dashboard.php" class="admin-nav-item active">
        <i class="fa-solid fa-chart-line"></i> Dashboard
      </a>
      <a href="<?= BASE_URL ?>admin/orders.php" class="admin-nav-item">
        <i class="fa-solid fa-cash-register"></i> Kasir / POS
      </a>
      <a href="<?= BASE_URL ?>admin/kitchen.php" class="admin-nav-item" target="_blank">
        <i class="fa-solid fa-fire-burner"></i> Layar Dapur (KDS) <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.75rem; margin-left:auto;"></i>
      </a>
      <a href="<?= BASE_URL ?>admin/menu.php" class="admin-nav-item">
        <i class="fa-solid fa-book-open"></i> Kelola Menu
      </a>
      <a href="<?= BASE_URL ?>admin/reports.php" class="admin-nav-item">
        <i class="fa-solid fa-file-invoice-dollar"></i> Laporan Pendapatan
      </a>
      <a href="<?= BASE_URL ?>admin/users.php" class="admin-nav-item">
        <i class="fa-solid fa-users-gear"></i> Kelola Pengguna
      </a>
      <div style="margin-top:auto; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.08);">
        <a href="<?= BASE_URL ?>menu.php" class="admin-nav-item" target="_blank">
          <i class="fa-solid fa-store"></i> Tampilan Pelanggan
        </a>
        <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item" style="color:#f87171;">
          <i class="fa-solid fa-right-from-bracket"></i> Keluar
        </a>
      </div>
    </nav>
  </aside>

  <!-- Admin Main Content -->
  <main class="admin-main">
    <header class="admin-topbar no-print">
      <div style="display:flex; align-items:center; gap:0.5rem;">
        <h2 style="font-size:1.15rem; font-weight:700; margin:0;">Ringkasan Restoran</h2>
      </div>
      <div style="display:flex; align-items:center; gap:1rem;">
        <span style="font-size:0.85rem; color:var(--text-secondary);">
          Login sebagai: <strong><?= e($currentUser['name']) ?></strong> (<?= ucfirst(e($currentUser['role'])) ?>)
        </span>
        <a href="<?= BASE_URL ?>logout.php" class="sy-btn sy-btn-outline sy-btn-sm" style="color:var(--danger); border-color:#fca5a5;">
          <i class="fa-solid fa-power-off"></i> Logout
        </a>
      </div>
    </header>

    <div class="admin-content">
      <!-- Stat Cards Grid -->
      <div class="stats-grid">
        <!-- Card 1: Revenue Today -->
        <div class="stat-card">
          <div class="stat-icon" style="background-color:#ecfdf5; color:#059669;">
            <i class="fa-solid fa-money-bill-wave"></i>
          </div>
          <div class="stat-info">
            <h4>Pendapatan Hari Ini</h4>
            <div class="stat-number" id="statRevToday" style="color:#059669;"><?= format_rupiah($revToday) ?></div>
          </div>
        </div>

        <!-- Card 2: Revenue This Month -->
        <div class="stat-card">
          <div class="stat-icon" style="background-color:#eff6ff; color:#2563eb;">
            <i class="fa-solid fa-wallet"></i>
          </div>
          <div class="stat-info">
            <h4>Pendapatan Bulan Ini</h4>
            <div class="stat-number" id="statRevMonth" style="color:#2563eb;"><?= format_rupiah($revMonth) ?></div>
          </div>
        </div>

        <!-- Card 3: Unpaid Orders -->
        <div class="stat-card">
          <div class="stat-icon" style="background-color:#fff7ed; color:#ea580c;">
            <i class="fa-solid fa-clock"></i>
          </div>
          <div class="stat-info">
            <h4>Belum Dibayar</h4>
            <div class="stat-number" id="statUnpaid" style="color:#ea580c;"><?= (int)($counts['unpaid'] ?? 0) ?></div>
          </div>
        </div>

        <!-- Card 4: Processing in Kitchen -->
        <div class="stat-card">
          <div class="stat-icon" style="background-color:#f0fdf4; color:#16a34a;">
            <i class="fa-solid fa-fire-burner"></i>
          </div>
          <div class="stat-info">
            <h4>Diproses Dapur</h4>
            <div class="stat-number" id="statProcessing" style="color:#16a34a;"><?= (int)($counts['processing'] ?? 0) ?></div>
          </div>
        </div>

        <!-- Card 5: Completed Orders -->
        <div class="stat-card">
          <div class="stat-icon" style="background-color:#f8fafc; color:#475569;">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div class="stat-info">
            <h4>Pesanan Selesai</h4>
            <div class="stat-number" id="statCompleted"><?= (int)($counts['completed'] ?? 0) ?></div>
          </div>
        </div>
      </div>

      <!-- Quick Action Shortcuts -->
      <div style="display:flex; gap:0.75rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>admin/orders.php" class="sy-btn sy-btn-primary">
          <i class="fa-solid fa-cash-register"></i> Buka Kasir POS
        </a>
        <a href="<?= BASE_URL ?>admin/kitchen.php" class="sy-btn sy-btn-success" target="_blank">
          <i class="fa-solid fa-fire-burner"></i> Buka Layar Dapur (KDS)
        </a>
        <a href="<?= BASE_URL ?>admin/menu-form.php" class="sy-btn sy-btn-outline">
          <i class="fa-solid fa-plus"></i> Tambah Menu Baru
        </a>
        <a href="<?= BASE_URL ?>admin/reports.php" class="sy-btn sy-btn-outline">
          <i class="fa-solid fa-print"></i> Cetak Laporan Penjualan
        </a>
      </div>

      <!-- Recent Transactions Table -->
      <div class="sy-card">
        <div class="sy-card-header">
          <h3 class="sy-card-title"><i class="fa-solid fa-receipt" style="color:var(--primary);"></i> Transaksi Pemesanan Terbaru</h3>
          <a href="<?= BASE_URL ?>admin/orders.php" style="color:var(--primary); font-size:0.85rem; font-weight:600;">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="sy-table-responsive">
          <table class="sy-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Pemesan</th>
                <th>Layanan</th>
                <th>Total</th>
                <th>Status</th>
                <th>Waktu</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentOrders)): ?>
                <tr>
                  <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">Belum ada riwayat pesanan.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentOrders as $ro): ?>
                  <tr>
                    <td style="font-weight:700;"><?= e($ro['order_code']) ?></td>
                    <td><?= e($ro['customer_name']) ?></td>
                    <td>
                      <?php if ($ro['order_type'] === 'takeaway'): ?>
                        <span class="sy-badge badge-takeaway">Take Away</span>
                      <?php else: ?>
                        <span class="sy-badge badge-dinein">Meja <?= e($ro['table_number'] ?: '-') ?></span>
                      <?php endif; ?>
                    </td>
                    <td style="font-weight:700; color:var(--primary);"><?= format_rupiah($ro['total']) ?></td>
                    <td><?= render_status_badge($ro['status']) ?></td>
                    <td style="font-size:0.8rem; color:var(--text-secondary);"><?= format_datetime($ro['created_at']) ?></td>
                    <td>
                      <a href="<?= BASE_URL ?>order-detail.php?code=<?= urlencode($ro['order_code']) ?>" class="sy-btn sy-btn-outline sy-btn-sm">
                        Detail
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
// Auto-polling dashboard stats every 15 seconds (PRD Section 2.4)
document.addEventListener('DOMContentLoaded', () => {
  window.createPoller('api/admin/stats.php', 15000, (data) => {
    if (data) {
      document.getElementById('statRevToday').textContent = data.formatted_revenue_today;
      document.getElementById('statRevMonth').textContent = data.formatted_revenue_month;
      document.getElementById('statUnpaid').textContent = data.unpaid_orders;
      document.getElementById('statProcessing').textContent = data.processing_orders;
      document.getElementById('statCompleted').textContent = data.completed_orders;
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

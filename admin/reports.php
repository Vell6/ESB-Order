<?php
/**
 * RM bu ani - Sales & Financial Reports
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role('admin', 'login.php');

$currentUser = current_user();

$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

try {
    $db = getDB();

    // 1. Total summary in range
    $summaryStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            COALESCE(SUM(total), 0) as total_revenue,
            COALESCE(AVG(total), 0) as avg_order
        FROM orders
        WHERE payment_status = 'paid'
          AND status != 'cancelled'
          AND DATE(paid_at) BETWEEN :s AND :e
    ");
    $summaryStmt->execute([':s' => $startDate, ':e' => $endDate]);
    $summary = $summaryStmt->fetch();

    // 2. Total items / dishes sold
    $itemsSoldStmt = $db->prepare("
        SELECT COALESCE(SUM(oi.quantity), 0) as total_dishes
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.payment_status = 'paid'
          AND o.status != 'cancelled'
          AND DATE(o.paid_at) BETWEEN :s AND :e
    ");
    $itemsSoldStmt->execute([':s' => $startDate, ':e' => $endDate]);
    $totalDishes = (int)$itemsSoldStmt->fetchColumn();

    // 3. Top selling dishes
    $topStmt = $db->prepare("
        SELECT oi.menu_name, SUM(oi.quantity) as qty_sold, SUM(oi.subtotal) as total_sales
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.payment_status = 'paid'
          AND o.status != 'cancelled'
          AND DATE(o.paid_at) BETWEEN :s AND :e
        GROUP BY oi.menu_name
        ORDER BY qty_sold DESC
        LIMIT 6
    ");
    $topStmt->execute([':s' => $startDate, ':e' => $endDate]);
    $topDishes = $topStmt->fetchAll();

    // 4. Daily breakdown
    $dailyStmt = $db->prepare("
        SELECT 
            DATE(paid_at) as sale_date,
            COUNT(*) as trx_count,
            SUM(total) as daily_revenue
        FROM orders
        WHERE payment_status = 'paid'
          AND status != 'cancelled'
          AND DATE(paid_at) BETWEEN :s AND :e
        GROUP BY DATE(paid_at)
        ORDER BY sale_date DESC
    ");
    $dailyStmt->execute([':s' => $startDate, ':e' => $endDate]);
    $dailyReports = $dailyStmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$pageTitle = "Laporan Penjualan - " . APP_NAME;
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
      <a href="<?= BASE_URL ?>admin/reports.php" class="admin-nav-item active">
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
        <h2 style="font-size:1.15rem; font-weight:700; margin:0;"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--primary);"></i> Laporan Keuangan & Penjualan</h2>
      </div>
      <button type="button" class="sy-btn sy-btn-outline" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak Laporan
      </button>
    </header>

    <div class="admin-content">
      <!-- Date Filter Bar -->
      <div class="sy-card no-print" style="margin-bottom:1.25rem; padding:1rem;">
        <form method="GET" action="" style="display:flex; gap:0.75rem; align-items:flex-end; flex-wrap:wrap;">
          <div>
            <label class="form-label" style="margin-bottom:0.25rem;">Dari Tanggal:</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>" required>
          </div>
          <div>
            <label class="form-label" style="margin-bottom:0.25rem;">Sampai Tanggal:</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>" required>
          </div>
          <button type="submit" class="sy-btn sy-btn-primary">
            <i class="fa-solid fa-filter"></i> Terapkan Filter
          </button>
          <a href="<?= BASE_URL ?>admin/reports.php" class="sy-btn sy-btn-outline">Reset</a>
        </form>
      </div>

      <!-- Report Header on Print -->
      <div class="only-print" style="margin-bottom:1.5rem; text-align:center;">
        <h1 style="font-size:1.4rem; font-weight:800;"><?= APP_NAME ?></h1>
        <div style="font-size:0.9rem;"><?= APP_ADDRESS ?> | Telp: <?= APP_PHONE ?></div>
        <h3 style="font-size:1.1rem; margin-top:0.5rem;">Laporan Penjualan (<?= date('d/m/Y', strtotime($startDate)) ?> - <?= date('d/m/Y', strtotime($endDate)) ?>)</h3>
      </div>

      <!-- Summary KPI Grid -->
      <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card">
          <div class="stat-icon" style="background:#ecfdf5; color:#059669;"><i class="fa-solid fa-rupiah-sign"></i></div>
          <div class="stat-info">
            <h4>Total Omzet Bersih</h4>
            <div class="stat-number" style="color:#059669;"><?= format_rupiah($summary['total_revenue']) ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:#eff6ff; color:#2563eb;"><i class="fa-solid fa-receipt"></i></div>
          <div class="stat-info">
            <h4>Jumlah Transaksi</h4>
            <div class="stat-number"><?= (int)$summary['total_transactions'] ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:#fff7ed; color:#ea580c;"><i class="fa-solid fa-bowl-food"></i></div>
          <div class="stat-info">
            <h4>Porsi Menu Terjual</h4>
            <div class="stat-number" style="color:#ea580c;"><?= $totalDishes ?> porsi</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:#fdf2f8; color:#be185d;"><i class="fa-solid fa-calculator"></i></div>
          <div class="stat-info">
            <h4>Rata-rata Order</h4>
            <div class="stat-number"><?= format_rupiah($summary['avg_order']) ?></div>
          </div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem; margin-bottom:1.5rem;">
        <!-- Top Selling Menu -->
        <div class="sy-card">
          <div class="sy-card-header">
            <h3 class="sy-card-title"><i class="fa-solid fa-fire" style="color:var(--primary);"></i> Menu Terlaris</h3>
          </div>
          <div class="sy-table-responsive" style="border:none;">
            <table class="sy-table">
              <thead>
                <tr>
                  <th>Nama Menu</th>
                  <th style="text-align:center;">Terjual</th>
                  <th style="text-align:right;">Total Sales</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($topDishes)): ?>
                  <tr><td colspan="3" style="text-align:center; padding:1.5rem; color:var(--text-muted);">Belum ada penjualan.</td></tr>
                <?php else: ?>
                  <?php foreach ($topDishes as $td): ?>
                    <tr>
                      <td style="font-weight:700;"><?= e($td['menu_name']) ?></td>
                      <td style="text-align:center; font-weight:700; color:var(--primary);"><?= $td['qty_sold'] ?> porsi</td>
                      <td style="text-align:right; font-weight:600;"><?= format_rupiah($td['total_sales']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Daily Revenue Table -->
        <div class="sy-card">
          <div class="sy-card-header">
            <h3 class="sy-card-title"><i class="fa-solid fa-calendar-days" style="color:#2563eb;"></i> Rincian Harian</h3>
          </div>
          <div class="sy-table-responsive" style="border:none;">
            <table class="sy-table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th style="text-align:center;">Transaksi</th>
                  <th style="text-align:right;">Pendapatan</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($dailyReports)): ?>
                  <tr><td colspan="3" style="text-align:center; padding:1.5rem; color:var(--text-muted);">Belum ada data harian.</td></tr>
                <?php else: ?>
                  <?php foreach ($dailyReports as $dr): ?>
                    <tr>
                      <td><?= date('d M Y', strtotime($dr['sale_date'])) ?></td>
                      <td style="text-align:center; font-weight:600;"><?= $dr['trx_count'] ?></td>
                      <td style="text-align:right; font-weight:700; color:#059669;"><?= format_rupiah($dr['daily_revenue']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

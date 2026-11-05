<?php
/**
 * RM bu ani - Thermal Receipt Print Page
 * Compatible with 58mm & 80mm Thermal POS Printers and standard A4
 * Compliant with PRD Section 7.3
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';

$orderCode = trim($_GET['code'] ?? '');
if (empty($orderCode)) {
    die("Kode pesanan tidak valid");
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT o.*, u.phone as customer_phone
        FROM orders o
        JOIN users u ON u.id = o.user_id
        WHERE o.order_code = :code LIMIT 1
    ");
    $stmt->execute([':code' => $orderCode]);
    $order = $stmt->fetch();

    if (!$order) {
        die("Pesanan tidak ditemukan");
    }

    if ($order['payment_status'] !== 'paid') {
        die("Struk hanya dapat dicetak untuk pesanan yang telah dibayar lunas.");
    }

    // Fetch items
    $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :oid ORDER BY id ASC");
    $itemStmt->execute([':oid' => $order['id']]);
    $items = $itemStmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Struk Pembayaran - <?= e($order['order_code']) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>theme.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body {
      background-color: #f1f5f9;
      padding: 1.5rem;
      font-family: 'Courier New', Courier, monospace;
    }
    .receipt-wrapper {
      background-color: #ffffff;
      max-width: 320px;
      margin: 0 auto;
      padding: 16px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border: 1px solid #e2e8f0;
    }
  </style>
</head>
<body>

<!-- Print Controls (Hidden on Print) -->
<div class="no-print" style="max-width: 320px; margin: 0 auto 1rem auto; display: flex; gap: 0.5rem;">
  <button type="button" class="sy-btn sy-btn-primary sy-btn-block" onclick="window.print()">
    <i class="fa-solid fa-print"></i> Cetak Struk (Print)
  </button>
  <button type="button" class="sy-btn sy-btn-outline" onclick="window.close()">
    Tutup
  </button>
</div>

<!-- Thermal Receipt Container -->
<div class="receipt-wrapper receipt-container">
  <div class="receipt-header">
    <h2><?= APP_NAME ?></h2>
    <div style="font-size:11px; margin-top:2px;"><?= APP_ADDRESS ?></div>
    <div style="font-size:11px;">Telp: <?= APP_PHONE ?></div>
  </div>

  <div class="receipt-info">
    <div style="display:flex; justify-content:space-between;">
      <span>No: <?= e($order['order_code']) ?></span>
    </div>
    <div style="display:flex; justify-content:space-between;">
      <span>Waktu:</span>
      <span><?= date('d/m/Y H:i', strtotime($order['paid_at'] ?: $order['created_at'])) ?></span>
    </div>
    <div style="display:flex; justify-content:space-between;">
      <span>Pelanggan:</span>
      <strong><?= e($order['customer_name']) ?></strong>
    </div>
    <div style="display:flex; justify-content:space-between;">
      <span>Layanan:</span>
      <strong><?= ($order['order_type'] === 'takeaway') ? 'TAKE AWAY (BUNGKUS)' : 'MEJA ' . e($order['table_number']) ?></strong>
    </div>
    <div style="display:flex; justify-content:space-between;">
      <span>Metode:</span>
      <span><?= e($order['payment_method'] ?: 'Tunai / Cash') ?> (LUNAS)</span>
    </div>
  </div>

  <table class="receipt-table">
    <thead>
      <tr style="border-bottom:1px dashed #000;">
        <th style="text-align:left;">Menu</th>
        <th style="text-align:center;">Qty</th>
        <th style="text-align:right;">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td style="padding-top:4px;">
            <div><?= e($it['menu_name']) ?></div>
            <?php if ($it['spicy_level']): ?>
              <div style="font-size:10px;">* <?= SPICY_LEVELS[(int)$it['spicy_level']] ?? '' ?></div>
            <?php endif; ?>
            <?php if ($it['item_notes']): ?>
              <div style="font-size:10px; font-style:italic;">(<?= e($it['item_notes']) ?>)</div>
            <?php endif; ?>
          </td>
          <td style="text-align:center; padding-top:4px;"><?= $it['quantity'] ?></td>
          <td style="text-align:right; padding-top:4px;"><?= format_rupiah($it['subtotal'], false) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="receipt-totals">
    <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
      <span>Subtotal:</span>
      <span><?= format_rupiah($order['subtotal']) ?></span>
    </div>
    <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:14px; margin-top:4px;">
      <span>TOTAL:</span>
      <span><?= format_rupiah($order['total']) ?></span>
    </div>
  </div>

  <!-- Barcode inside receipt -->
  <div style="text-align:center; margin:12px 0 6px 0;">
    <div id="receiptBarcode" class="barcode-svg"></div>
  </div>

  <div class="receipt-footer">
    <div>*** TERIMA KASIH ***</div>
    <div style="margin-top:2px;">Selamat Menikmati Hidangan Kami</div>
    <div style="margin-top:2px; font-size:10px;">Simpan struk ini sebagai bukti pembayaran yang sah</div>
  </div>
</div>

<script src="<?= BASE_URL ?>assets/js/barcode.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  renderOrderBarcode('#receiptBarcode', '<?= e($order['order_code']) ?>');
  // Optional auto print
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('autoprint') === '1') {
    window.print();
  }
});
</script>

</body>
</html>

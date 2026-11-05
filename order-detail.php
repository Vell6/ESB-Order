<?php
/**
 * RM bu ani - Order Detail Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

require_login('login.php');

$orderCode = trim($_GET['code'] ?? '');
if (empty($orderCode)) {
    header("Location: " . BASE_URL . "my-orders.php");
    exit;
}

$pageTitle = "Detail Pesanan " . $orderCode . " - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="app-wrapper">
  <main class="container-sm" style="margin-top:1rem;">
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
      <a href="<?= BASE_URL ?>my-orders.php" class="sy-btn sy-btn-outline sy-btn-sm" style="border-radius:50%; width:36px; height:36px; padding:0;">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <h2 style="font-size:1.25rem; font-weight:800; margin:0;">Detail Pesanan</h2>
    </div>

    <div id="detailLoading" style="text-align:center; padding:3rem 1rem;">
      <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem; color:var(--primary);"></i>
      <p style="margin-top:0.5rem; font-size:0.85rem;">Memuat rincian pesanan...</p>
    </div>

    <div id="detailContainer" style="display:none; flex-direction:column; gap:1rem;"></div>
  </main>
</div>

<script>
async function loadOrderDetail() {
  try {
    const res = await apiFetch('<?= BASE_URL ?>api/orders/detail.php?code=<?= urlencode($orderCode) ?>');
    document.getElementById('detailLoading').style.display = 'none';

    if (!res.success) {
      showToast(res.message, 'error');
      document.getElementById('detailContainer').style.display = 'block';
      document.getElementById('detailContainer').innerHTML = `
        <div class="sy-card" style="text-align:center; padding:2rem;">
          <i class="fa-solid fa-triangle-exclamation" style="font-size:2rem; color:var(--danger); margin-bottom:0.5rem;"></i>
          <p>${escapeHtml(res.message)}</p>
          <a href="<?= BASE_URL ?>my-orders.php" class="sy-btn sy-btn-primary" style="margin-top:1rem;">Kembali</a>
        </div>
      `;
      return;
    }

    const o = res.data;
    const container = document.getElementById('detailContainer');
    container.style.display = 'flex';

    let itemsHtml = '';
    o.items.forEach(it => {
      itemsHtml += `
        <div style="display:flex; justify-content:space-between; align-items:flex-start; padding:0.6rem 0; border-bottom:1px solid var(--border-light); font-size:0.9rem;">
          <div>
            <div style="font-weight:700;">${it.quantity}x ${escapeHtml(it.menu_name)}</div>
            ${it.spicy_label ? `<span class="sy-badge badge-spicy" style="font-size:0.7rem; padding:1px 6px; margin-top:2px;"><i class="fa-solid fa-pepper-hot"></i> ${escapeHtml(it.spicy_label)}</span>` : ''}
            ${it.item_notes ? `<div style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">"${escapeHtml(it.item_notes)}"</div>` : ''}
            <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:2px;">@ ${it.formatted_price}</div>
          </div>
          <div style="font-weight:700; color:var(--text-primary); text-align:right;">
            ${it.formatted_subtotal}
          </div>
        </div>
      `;
    });

    let barcodeSection = '';
    if (o.status === 'unpaid') {
      barcodeSection = `
        <div class="barcode-box">
          <p style="font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem; text-transform:uppercase; font-weight:700;">
            Barcode Transaksi Kasir
          </p>
          <div id="detailBarcodeBox" class="barcode-svg"></div>
          <div style="font-size:0.85rem; font-weight:700; color:var(--text-primary); margin-top:0.35rem; letter-spacing:1px;">
            ${escapeHtml(o.order_code)}
          </div>
          <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.35rem;">
            Silakan tunjukkan barcode ini ke kasir untuk proses pembayaran.
          </p>
        </div>
      `;
    }

    let statusAlert = '';
    if (o.status === 'completed') {
      const msg = (o.order_type === 'takeaway') 
        ? 'Pesanan Anda sudah selesai disiapkan. Silakan ambil makanan Anda di kasir.' 
        : `Makanan Anda selesai disiapkan dan akan segera diantar ke Meja ${o.table_number || ''}.`;
      statusAlert = `
        <div style="background:#e8f7ee; border:1px solid #bce6cb; border-radius:var(--radius-md); padding:1rem; color:#1e7441; font-size:0.9rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
          <i class="fa-solid fa-circle-check" style="font-size:1.25rem;"></i>
          <span>${msg}</span>
        </div>
      `;
    } else if (o.status === 'processing') {
      statusAlert = `
        <div style="background:#e6f6f4; border:1px solid #b7e3de; border-radius:var(--radius-md); padding:1rem; color:#1b685f; font-size:0.9rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
          <i class="fa-solid fa-fire-burner fa-bounce" style="font-size:1.25rem;"></i>
          <span>Pembayaran diterima. Makanan Anda sedang dimasak di dapur...</span>
        </div>
      `;
    }

    container.innerHTML = `
      ${statusAlert}

      <div class="sy-card">
        <div class="sy-card-header">
          <div>
            <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Kode Transaksi</span>
            <div style="font-size:1.15rem; font-weight:800; color:var(--text-primary);">${escapeHtml(o.order_code)}</div>
          </div>
          <span class="sy-badge ${escapeHtml(o.status_badge)}">${escapeHtml(o.status_label)}</span>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem; font-size:0.875rem; margin-bottom:1rem;">
          <div>
            <span style="color:var(--text-muted); font-size:0.8rem;">Nama Pemesan:</span>
            <div style="font-weight:700;">${escapeHtml(o.customer_name)}</div>
          </div>
          <div>
            <span style="color:var(--text-muted); font-size:0.8rem;">Tipe Layanan:</span>
            <div style="font-weight:700;">
              ${o.order_type === 'takeaway' ? '<span class="sy-badge badge-takeaway"><i class="fa-solid fa-bag-shopping"></i> Take Away</span>' : `<span class="sy-badge badge-dinein"><i class="fa-solid fa-chair"></i> Meja ${escapeHtml(o.table_number || '-')}</span>`}
            </div>
          </div>
          <div>
            <span style="color:var(--text-muted); font-size:0.8rem;">Waktu Pemesanan:</span>
            <div>${escapeHtml(o.formatted_time)}</div>
          </div>
          <div>
            <span style="color:var(--text-muted); font-size:0.8rem;">Metode Bayar:</span>
            <div>${escapeHtml(o.payment_method || 'Tunai / Cash di Kasir')}</div>
          </div>
        </div>

        ${barcodeSection}

        <div style="margin-top:1rem;">
          <h4 style="font-size:0.95rem; font-weight:700; margin-bottom:0.5rem; text-transform:uppercase; letter-spacing:0.5px;">Rincian Menu:</h4>
          ${itemsHtml}
        </div>

        <div style="margin-top:1rem; padding-top:0.75rem; border-top:1px dashed var(--border); display:flex; justify-content:space-between; align-items:center; font-size:1.15rem; font-weight:800;">
          <span>Total Pembayaran</span>
          <span style="color:var(--primary); font-size:1.35rem;">${escapeHtml(o.formatted_total)}</span>
        </div>
      </div>

      <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
        <a href="<?= BASE_URL ?>menu.php" class="sy-btn sy-btn-outline" style="flex:1;">
          <i class="fa-solid fa-utensils"></i> Buka Menu
        </a>
        ${o.payment_status === 'paid' ? `
          <a href="<?= BASE_URL ?>admin/receipt.php?code=${encodeURIComponent(o.order_code)}" target="_blank" class="sy-btn sy-btn-primary" style="flex:1;">
            <i class="fa-solid fa-print"></i> Cetak Struk
          </a>
        ` : ''}
      </div>
    `;

    if (o.status === 'unpaid') {
      renderOrderBarcode('#detailBarcodeBox', o.order_code);
    }
  } catch (err) {
    document.getElementById('detailLoading').style.display = 'none';
    showToast('Gagal memuat detail: ' + err.message, 'error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadOrderDetail();

  // Poll status change every 5 seconds
  window.createPoller('api/orders/status.php?code=<?= urlencode($orderCode) ?>', 5000, (data) => {
    if (data && data.status) {
      loadOrderDetail();
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

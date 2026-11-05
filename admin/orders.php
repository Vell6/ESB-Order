<?php
/**
 * RM bu ani - Cashier & Order Management (POS)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role(['admin', 'cashier'], 'login.php');

$currentUser = current_user();
$pageTitle = "Kasir POS & Kelola Pesanan - " . APP_NAME;
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
      <?php if (has_role('admin')): ?>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="admin-nav-item">
          <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>admin/orders.php" class="admin-nav-item active">
        <i class="fa-solid fa-cash-register"></i> Kasir / POS
      </a>
      <a href="<?= BASE_URL ?>admin/kitchen.php" class="admin-nav-item" target="_blank">
        <i class="fa-solid fa-fire-burner"></i> Layar Dapur (KDS) <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.75rem; margin-left:auto;"></i>
      </a>
      <?php if (has_role('admin')): ?>
        <a href="<?= BASE_URL ?>admin/menu.php" class="admin-nav-item">
          <i class="fa-solid fa-book-open"></i> Kelola Menu
        </a>
        <a href="<?= BASE_URL ?>admin/reports.php" class="admin-nav-item">
          <i class="fa-solid fa-file-invoice-dollar"></i> Laporan Pendapatan
        </a>
        <a href="<?= BASE_URL ?>admin/users.php" class="admin-nav-item">
          <i class="fa-solid fa-users-gear"></i> Kelola Pengguna
        </a>
      <?php endif; ?>
      <div style="margin-top:auto; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.08);">
        <a href="<?= BASE_URL ?>menu.php" class="admin-nav-item" target="_blank">
          <i class="fa-solid fa-store"></i> Menu Pelanggan
        </a>
        <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item" style="color:#f87171;">
          <i class="fa-solid fa-right-from-bracket"></i> Keluar
        </a>
      </div>
    </nav>
  </aside>

  <!-- Main POS Interface -->
  <main class="admin-main">
    <header class="admin-topbar no-print">
      <div style="display:flex; align-items:center; gap:0.5rem;">
        <h2 style="font-size:1.15rem; font-weight:700; margin:0;"><i class="fa-solid fa-cash-register" style="color:var(--primary);"></i> Point of Sale (Kasir)</h2>
      </div>
      <div style="display:flex; align-items:center; gap:0.75rem;">
        <span class="sy-badge badge-dinein" style="font-size:0.8rem;">
          <i class="fa-solid fa-user-tag"></i> Kasir: <?= e($currentUser['name']) ?>
        </span>
      </div>
    </header>

    <div class="admin-content">
      <!-- Search & Barcode Scanner Input Bar (PRD Section 7.2) -->
      <div class="sy-card" style="margin-bottom:1.25rem; padding:1rem;">
        <form id="barcodeSearchForm" onsubmit="handleBarcodeSearch(event)">
          <div style="display:flex; gap:0.75rem;">
            <div class="barcode-scanner-input">
              <i class="fa-solid fa-barcode barcode-scanner-icon"></i>
              <input type="text" id="barcodeInput" class="form-control" placeholder="Scan Barcode USB atau ketik Order ID (misal: RMA-20261104-A8K29F)..." autofocus autocomplete="off">
            </div>
            <button type="submit" class="sy-btn sy-btn-primary" style="white-space:nowrap; padding:0 1.5rem;">
              <i class="fa-solid fa-magnifying-glass"></i> Cari Pesanan
            </button>
          </div>
        </form>
        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.35rem;">
          <i class="fa-solid fa-circle-info"></i> Scanner barcode USB otomatis mengisi kode dan menekan Enter.
        </div>
      </div>

      <!-- POS Filter Tabs -->
      <div class="sy-tab-list" style="background:#ffffff; border:1px solid var(--border); border-radius:var(--radius-lg); padding:0.4rem; margin-bottom:1.25rem; display:inline-flex;">
        <button class="sy-tab sy-tab-underline active" onclick="switchCashierTab('unpaid', this)" id="tabUnpaid">
          <i class="fa-solid fa-clock"></i> Belum Dibayar (<span id="countUnpaid">0</span>)
        </button>
        <button class="sy-tab sy-tab-underline" onclick="switchCashierTab('processing', this)" id="tabProcessing">
          <i class="fa-solid fa-fire-burner"></i> Diproses Dapur (<span id="countProcessing">0</span>)
        </button>
        <button class="sy-tab sy-tab-underline" onclick="switchCashierTab('completed', this)" id="tabCompleted">
          <i class="fa-solid fa-circle-check"></i> Selesai (<span id="countCompleted">0</span>)
        </button>
      </div>

      <!-- Orders Table Container -->
      <div class="sy-card" style="padding:0; overflow:hidden;">
        <div id="cashierLoading" style="text-align:center; padding:3rem;">
          <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem; color:var(--primary);"></i>
          <p style="margin-top:0.5rem; font-size:0.85rem;">Memuat daftar pesanan...</p>
        </div>

        <div class="sy-table-responsive" id="cashierTableWrapper" style="display:none; border:none;">
          <table class="sy-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Pemesan</th>
                <th>Tipe & Meja</th>
                <th>Total Item</th>
                <th>Total Tagihan</th>
                <th>Waktu</th>
                <th>Status</th>
                <th style="text-align:right;">Aksi Kasir</th>
              </tr>
            </thead>
            <tbody id="cashierOrdersTbody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Modal Konfirmasi Pembayaran Kasir (Cash POS) -->
<div id="paymentModal" class="sy-modal-overlay">
  <div class="sy-modal-dialog">
    <div class="sy-modal-header" style="background:#f8fafc;">
      <h3 style="margin:0;"><i class="fa-solid fa-cash-register" style="color:var(--primary);"></i> Konfirmasi Pembayaran Kasir</h3>
      <button type="button" class="sy-modal-close" onclick="closeModal('paymentModal')">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="sy-modal-body">
      <div style="background:#f1f5f9; border-radius:var(--radius-md); padding:1rem; margin-bottom:1.25rem; font-size:0.9rem;">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.25rem;">
          <span style="color:var(--text-secondary);">Order ID:</span>
          <strong id="payModalCode" style="font-size:1.05rem; letter-spacing:0.5px;">-</strong>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:0.25rem;">
          <span style="color:var(--text-secondary);">Pelanggan:</span>
          <strong id="payModalCustomer">-</strong>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span style="color:var(--text-secondary);">Layanan:</span>
          <span id="payModalType" class="sy-badge badge-dinein">-</span>
        </div>
      </div>

      <!-- Items List in Payment Modal -->
      <div style="max-height:160px; overflow-y:auto; border:1px solid var(--border-light); border-radius:var(--radius-md); padding:0.5rem 0.75rem; margin-bottom:1.25rem;" id="payModalItemsList"></div>

      <!-- Grand Total Highlight -->
      <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:var(--radius-md); padding:1rem; text-align:center; margin-bottom:1.25rem;">
        <span style="font-size:0.8rem; color:#9f1239; font-weight:700; text-transform:uppercase;">Total yang Harus Dibayar</span>
        <div id="payModalTotal" style="font-size:1.75rem; font-weight:800; color:var(--primary);">Rp 0</div>
      </div>

      <!-- Cash Received Input -->
      <div class="form-group">
        <label class="form-label" for="cashReceivedInput" style="font-weight:700;">
          Uang Tunai Diterima (Rp) <span class="required">*</span>
        </label>
        <input type="number" id="cashReceivedInput" class="form-control" placeholder="0" style="font-size:1.25rem; font-weight:700; color:var(--primary);" oninput="calculateChange()">
      </div>

      <!-- Quick Amount Presets -->
      <div style="display:flex; gap:0.5rem; margin-bottom:1.25rem; flex-wrap:wrap;">
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="setCashExact()">Uang Pas</button>
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="addCash(20000)">+20 rb</button>
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="addCash(50000)">+50 rb</button>
        <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="addCash(100000)">+100 rb</button>
      </div>

      <!-- Change / Kembalian Calculation -->
      <div style="display:flex; justify-content:space-between; align-items:center; background:#ecfdf5; border:1px solid #bce6cb; border-radius:var(--radius-md); padding:0.85rem 1rem;">
        <span style="font-weight:700; color:#166534;">Kembalian:</span>
        <span id="payModalChange" style="font-size:1.25rem; font-weight:800; color:#166534;">Rp 0</span>
      </div>

      <input type="hidden" id="payModalRawTotal" value="0">
    </div>
    <div class="sy-modal-footer">
      <button type="button" class="sy-btn sy-btn-secondary" onclick="closeModal('paymentModal')">Batal</button>
      <button type="button" class="sy-btn sy-btn-success sy-btn-lg" id="btnConfirmPay" onclick="submitConfirmPayment()" style="flex:1;">
        <i class="fa-solid fa-circle-check"></i> Konfirmasi Pembayaran
      </button>
    </div>
  </div>
</div>

<script>
let currentCashierTab = 'unpaid';
let currentOrders = [];
let targetOrderForPay = null;

async function loadCashierOrders(silent = false) {
  if (!silent) {
    document.getElementById('cashierLoading').style.display = 'block';
    document.getElementById('cashierTableWrapper').style.display = 'none';
  }

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/admin/orders.php?status=' + currentCashierTab);
    document.getElementById('cashierLoading').style.display = 'none';

    if (res.success) {
      currentOrders = res.data.orders || [];
      renderCashierOrders();
    }
  } catch (err) {
    document.getElementById('cashierLoading').style.display = 'none';
    showToast('Gagal memuat pesanan: ' + err.message, 'error');
  }
}

function switchCashierTab(tab, btn) {
  currentCashierTab = tab;
  document.querySelectorAll('.sy-tab-underline').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  loadCashierOrders();
}

function renderCashierOrders() {
  const tbody = document.getElementById('cashierOrdersTbody');
  document.getElementById('cashierTableWrapper').style.display = 'block';

  if (currentOrders.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-muted);">
          <i class="fa-regular fa-folder-open" style="font-size:2rem; margin-bottom:0.5rem;"></i>
          <div>Tidak ada pesanan pada status ini.</div>
        </td>
      </tr>
    `;
    return;
  }

  let html = '';
  currentOrders.forEach(o => {
    let actionBtn = '';

    if (o.status === 'unpaid') {
      actionBtn = `
        <button type="button" class="sy-btn sy-btn-success sy-btn-sm" onclick="openPaymentModal('${o.order_code}')">
          <i class="fa-solid fa-money-bill-wave"></i> Terima Pembayaran
        </button>
      `;
    } else if (o.status === 'processing') {
      actionBtn = `
        <span class="sy-badge badge-processing">
          <i class="fa-solid fa-fire-burner"></i> Dimasak di Dapur
        </span>
      `;
    } else if (o.status === 'completed') {
      actionBtn = `
        <a href="<?= BASE_URL ?>admin/receipt.php?code=${encodeURIComponent(o.order_code)}" target="_blank" class="sy-btn sy-btn-primary sy-btn-sm">
          <i class="fa-solid fa-print"></i> Cetak Struk
        </a>
      `;
    }

    html += `
      <tr>
        <td style="font-weight:700; letter-spacing:0.5px;">${escapeHtml(o.order_code)}</td>
        <td>
          <div style="font-weight:600;">${escapeHtml(o.customer_name)}</div>
          <div style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(o.customer_phone || '-')}</div>
        </td>
        <td>
          <span class="sy-badge ${o.order_type === 'takeaway' ? 'badge-takeaway' : 'badge-dinein'}">
            ${escapeHtml(o.order_type_label)}
          </span>
          ${o.table_number ? `<span style="font-weight:700; margin-left:4px;">Meja ${escapeHtml(o.table_number)}</span>` : ''}
        </td>
        <td>${o.total_items} menu</td>
        <td style="font-weight:800; color:var(--primary);">${escapeHtml(o.formatted_total)}</td>
        <td style="font-size:0.8rem; color:var(--text-secondary);">${escapeHtml(o.formatted_time)}</td>
        <td><span class="sy-badge ${escapeHtml(o.status_badge)}">${escapeHtml(o.status_label)}</span></td>
        <td style="text-align:right;">
          <div style="display:flex; justify-content:flex-end; gap:0.4rem;">
            ${actionBtn}
            <a href="<?= BASE_URL ?>order-detail.php?code=${encodeURIComponent(o.order_code)}" class="sy-btn sy-btn-outline sy-btn-sm" title="Lihat Rincian">
              <i class="fa-solid fa-eye"></i>
            </a>
          </div>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

// Search or Barcode Scan Handler
async function handleBarcodeSearch(e) {
  e.preventDefault();
  const code = document.getElementById('barcodeInput').value.trim();
  if (!code) return;

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/admin/search-order.php?code=' + encodeURIComponent(code));
    if (res.success && res.data) {
      document.getElementById('barcodeInput').value = '';
      if (res.data.can_pay) {
        populateAndOpenPaymentModal(res.data);
      } else {
        window.location.href = '<?= BASE_URL ?>order-detail.php?code=' + encodeURIComponent(res.data.order_code);
      }
    } else {
      showToast(res.message || 'Pesanan tidak ditemukan', 'error');
    }
  } catch (err) {
    showToast('Pencarian gagal: ' + err.message, 'error');
  }
}

// Open Payment Modal by Order Code
async function openPaymentModal(orderCode) {
  try {
    const res = await apiFetch('<?= BASE_URL ?>api/admin/search-order.php?code=' + encodeURIComponent(orderCode));
    if (res.success && res.data) {
      populateAndOpenPaymentModal(res.data);
    }
  } catch (err) {
    showToast('Gagal memuat detail pesanan: ' + err.message, 'error');
  }
}

function populateAndOpenPaymentModal(order) {
  targetOrderForPay = order;
  document.getElementById('payModalCode').textContent = order.order_code;
  document.getElementById('payModalCustomer').textContent = order.customer_name;
  document.getElementById('payModalType').textContent = order.order_type_label + (order.table_number ? ' (Meja ' + order.table_number + ')' : '');
  document.getElementById('payModalTotal').textContent = order.formatted_total;
  document.getElementById('payModalRawTotal').value = order.total;

  let itemsHtml = '';
  order.items.forEach(it => {
    itemsHtml += `
      <div style="display:flex; justify-content:space-between; font-size:0.85rem; padding:2px 0;">
        <span>${it.quantity}x ${escapeHtml(it.menu_name)} ${it.spicy_label ? `(${it.spicy_label})` : ''}</span>
        <strong>${it.formatted_subtotal}</strong>
      </div>
    `;
  });
  document.getElementById('payModalItemsList').innerHTML = itemsHtml;

  document.getElementById('cashReceivedInput').value = order.total;
  calculateChange();

  openModal('paymentModal');
  setTimeout(() => {
    const inp = document.getElementById('cashReceivedInput');
    inp.focus();
    inp.select();
  }, 150);
}

function calculateChange() {
  const total = parseFloat(document.getElementById('payModalRawTotal').value) || 0;
  const cash = parseFloat(document.getElementById('cashReceivedInput').value) || 0;
  const change = Math.max(0, cash - total);
  document.getElementById('payModalChange').textContent = formatRupiah(change);
  document.getElementById('btnConfirmPay').disabled = (cash < total);
}

function setCashExact() {
  const total = parseFloat(document.getElementById('payModalRawTotal').value) || 0;
  document.getElementById('cashReceivedInput').value = total;
  calculateChange();
}

function addCash(amount) {
  let cash = parseFloat(document.getElementById('cashReceivedInput').value) || 0;
  cash += amount;
  document.getElementById('cashReceivedInput').value = cash;
  calculateChange();
}

async function submitConfirmPayment() {
  if (!targetOrderForPay) return;

  const total = parseFloat(document.getElementById('payModalRawTotal').value) || 0;
  const cash = parseFloat(document.getElementById('cashReceivedInput').value) || 0;

  if (cash < total) {
    showToast('Uang diterima kurang dari total pembayaran!', 'error');
    return;
  }

  const btn = document.getElementById('btnConfirmPay');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/admin/payment.php', {
      method: 'POST',
      body: JSON.stringify({
        order_code: targetOrderForPay.order_code,
        cash_received: cash,
        payment_method: 'Tunai'
      })
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Konfirmasi Pembayaran';

    if (res.success) {
      closeModal('paymentModal');
      showToast('Pembayaran berhasil dikonfirmasi! Pesanan masuk ke dapur.', 'success', 3500);

      // Offer receipt printing
      if (confirm('Pembayaran selesai. Apakah ingin mencetak struk sekarang?')) {
        window.open(res.data.receipt_url, '_blank');
      }

      loadCashierOrders(true);
    } else {
      showToast(res.message, 'error');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Konfirmasi Pembayaran';
    showToast('Gagal memproses pembayaran: ' + err.message, 'error');
  }
}

// Polling for new orders every 5 seconds (PRD Section 8.5)
document.addEventListener('DOMContentLoaded', () => {
  loadCashierOrders();

  window.createPoller('api/notifications/poll.php', 5000, (data) => {
    if (data) {
      document.getElementById('countUnpaid').textContent = data.unpaid_count || 0;
      document.getElementById('countProcessing').textContent = data.processing_count || 0;

      // Silently refresh current table if active
      loadCashierOrders(true);
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

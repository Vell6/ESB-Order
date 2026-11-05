<?php
/**
 * RM bu ani - Checkout Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

// Checkout requires login (PRD Section 4.9 & BR-01)
require_login('login.php');

$currentUser = current_user();
$sessionTable = get_session_table();
$orderType = get_session_order_type();

$pageTitle = "Checkout Pesanan - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="app-wrapper">
  <main class="container-sm" style="margin-top: 1rem;">
    <div class="section-title">
      <h2><i class="fa-solid fa-clipboard-check" style="color:var(--primary);"></i> Konfirmasi Pesanan</h2>
    </div>

    <!-- Alert error container -->
    <div id="checkoutAlert" style="display:none; padding:0.85rem 1rem; border-radius:var(--radius-md); font-size:0.875rem; margin-bottom:1.25rem;"></div>

    <form id="checkoutForm" onsubmit="handleCheckout(event)">
      <?= csrf_field() ?>

      <!-- 1. Data Pemesan & Meja Card -->
      <div class="sy-card" style="margin-bottom:1rem;">
        <div class="sy-card-title" style="margin-bottom:0.75rem;">
          <i class="fa-solid fa-user" style="color:var(--primary);"></i> Informasi Pemesan
        </div>

        <div class="form-group">
          <label class="form-label" for="checkoutName">Nama Lengkap Pemesan <span class="required">*</span></label>
          <input type="text" id="checkoutName" name="customer_name" class="form-control" value="<?= e($currentUser['name']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Tipe Layanan <span class="required">*</span></label>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem;">
            <label class="sy-tile <?= $orderType === 'dine_in' ? 'active' : '' ?>" style="padding:0.85rem; cursor:pointer;" onclick="toggleCheckoutOrderType('dine_in', this)">
              <div style="font-size:1.25rem; color:var(--primary);"><i class="fa-solid fa-chair"></i></div>
              <div style="font-weight:700; font-size:0.85rem;">Makan di Tempat</div>
              <input type="radio" name="order_type" value="dine_in" <?= $orderType === 'dine_in' ? 'checked' : '' ?> style="display:none;">
            </label>
            <label class="sy-tile <?= $orderType === 'takeaway' ? 'active' : '' ?>" style="padding:0.85rem; cursor:pointer;" onclick="toggleCheckoutOrderType('takeaway', this)">
              <div style="font-size:1.25rem; color:var(--warning);"><i class="fa-solid fa-bag-shopping"></i></div>
              <div style="font-weight:700; font-size:0.85rem;">Take Away</div>
              <input type="radio" name="order_type" value="takeaway" <?= $orderType === 'takeaway' ? 'checked' : '' ?> style="display:none;">
            </label>
          </div>
        </div>

        <!-- Table input (only for dine_in) -->
        <div class="form-group" id="checkoutTableGroup" style="<?= $orderType === 'takeaway' ? 'display:none;' : '' ?>">
          <label class="form-label" for="checkoutTable">Nomor Meja <span class="required">*</span></label>
          <select id="checkoutTable" name="table_number" class="form-control">
            <option value="">-- Pilih Nomor Meja --</option>
            <?php foreach (get_restaurant_tables() as $t): ?>
              <option value="<?= $t ?>" <?= ($sessionTable === $t) ? 'selected' : '' ?>>Meja <?= $t ?></option>
            <?php endforeach; ?>
          </select>
          <span class="form-hint">Nomor meja diambil dari stiker meja yang Anda duduki.</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="checkoutNotes">Catatan Pesanan Keseluruhan (Opsional)</label>
          <textarea id="checkoutNotes" name="notes" class="form-control" rows="2" placeholder="Contoh: Tolong disajikan bersamaan, sendok garpu lebih..."></textarea>
        </div>
      </div>

      <!-- 2. Ringkasan Menu yang Dipesan -->
      <div class="sy-card" style="margin-bottom:1.25rem;">
        <div class="sy-card-title" style="margin-bottom:0.75rem;">
          <i class="fa-solid fa-utensils" style="color:var(--primary);"></i> Menu yang Dipesan
        </div>

        <div id="checkoutItemsList" style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1rem;"></div>

        <div style="border-top:1px dashed var(--border); padding-top:0.75rem; display:flex; justify-content:space-between; font-size:1.15rem; font-weight:800;">
          <span>Total Tagihan:</span>
          <span id="checkoutGrandTotal" style="color:var(--primary);">Rp 0</span>
        </div>
      </div>

      <!-- 3. Instruksi Pembayaran Kasir -->
      <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:var(--radius-md); padding:1rem; margin-bottom:1.5rem; font-size:0.85rem; color:#9a3412;">
        <div style="display:flex; align-items:center; gap:0.5rem; font-weight:700; margin-bottom:0.25rem;">
          <i class="fa-solid fa-cash-register"></i> Pembayaran di Kasir (Cash POS)
        </div>
        Setelah menekan tombol "Buat Pesanan", sistem akan menghasilkan Barcode dan Order ID. Silakan tunjukkan ke kasir untuk melakukan pembayaran.
      </div>

      <button type="submit" id="btnSubmitOrder" class="sy-btn sy-btn-primary sy-btn-block sy-btn-lg sy-btn-pill" style="font-weight:700;">
        <i class="fa-solid fa-circle-check"></i> Buat Pesanan Sekarang
      </button>
    </form>
  </main>
</div>

<script>
function toggleCheckoutOrderType(type, element) {
  document.querySelectorAll('input[name="order_type"]').forEach(r => r.checked = (r.value === type));
  element.parentElement.querySelectorAll('.sy-tile').forEach(t => t.classList.remove('active'));
  element.classList.add('active');

  const tableGroup = document.getElementById('checkoutTableGroup');
  const tableSelect = document.getElementById('checkoutTable');

  if (type === 'takeaway') {
    tableGroup.style.display = 'none';
    tableSelect.removeAttribute('required');
  } else {
    tableGroup.style.display = 'block';
    tableSelect.setAttribute('required', 'required');
  }
}

function renderCheckoutItems() {
  const items = Cart.getItems();
  const list = document.getElementById('checkoutItemsList');

  if (items.length === 0) {
    window.location.href = '<?= BASE_URL ?>cart.php';
    return;
  }

  let html = '';
  items.forEach(it => {
    const subtotal = it.price * it.quantity;
    html += `
      <div style="display:flex; justify-content:space-between; font-size:0.875rem; padding:0.35rem 0; border-bottom:1px solid var(--border-light);">
        <div>
          <span style="font-weight:700;">${it.quantity}x</span> ${escapeHtml(it.name)}
          ${it.spicy_label ? `<span class="sy-badge badge-spicy" style="font-size:0.65rem; padding:1px 5px; margin-left:4px;">${escapeHtml(it.spicy_label)}</span>` : ''}
          ${it.notes ? `<div style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">"${escapeHtml(it.notes)}"</div>` : ''}
        </div>
        <div style="font-weight:600; text-align:right;">${formatRupiah(subtotal)}</div>
      </div>
    `;
  });

  list.innerHTML = html;
  document.getElementById('checkoutGrandTotal').textContent = formatRupiah(Cart.getTotal());
}

async function handleCheckout(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSubmitOrder');
  const alertBox = document.getElementById('checkoutAlert');
  alertBox.style.display = 'none';

  const items = Cart.getItems();
  if (items.length === 0) {
    showToast('Keranjang pesanan masih kosong', 'warning');
    window.location.href = '<?= BASE_URL ?>menu.php';
    return;
  }

  const customerName = document.getElementById('checkoutName').value.trim();
  const orderType = document.querySelector('input[name="order_type"]:checked').value;
  const tableNumber = (orderType === 'dine_in') ? document.getElementById('checkoutTable').value.trim() : null;
  const notes = document.getElementById('checkoutNotes').value.trim();

  if (orderType === 'dine_in' && !tableNumber) {
    alertBox.style.backgroundColor = 'var(--danger-light)';
    alertBox.style.color = 'var(--danger)';
    alertBox.style.border = '1px solid #f6bebe';
    alertBox.style.display = 'block';
    alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Silakan pilih nomor meja Anda.';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghasilkan Order ID & Barcode...';

  const payload = {
    customer_name: customerName,
    order_type: orderType,
    table_number: tableNumber,
    notes: notes,
    items: items
  };

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/orders/checkout.php', {
      method: 'POST',
      body: JSON.stringify(payload)
    });

    if (res.success) {
      // Clear local cart
      Cart.clear();
      showToast('Pesanan berhasil dibuat!', 'success');
      setTimeout(() => {
        window.location.href = res.data.detail_url;
      }, 500);
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Buat Pesanan Sekarang';
      alertBox.style.backgroundColor = 'var(--danger-light)';
      alertBox.style.color = 'var(--danger)';
      alertBox.style.border = '1px solid #f6bebe';
      alertBox.style.display = 'block';
      alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + (res.message || 'Checkout gagal.');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Buat Pesanan Sekarang';
    alertBox.style.backgroundColor = 'var(--danger-light)';
    alertBox.style.color = 'var(--danger)';
    alertBox.style.border = '1px solid #f6bebe';
    alertBox.style.display = 'block';
    alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + err.message;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  renderCheckoutItems();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * RM bu ani - Cart Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = "Keranjang Pesanan - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="app-wrapper">
  <main class="container-sm" style="margin-top: 1rem;">
    <div class="section-title">
      <h2><i class="fa-solid fa-cart-shopping" style="color:var(--primary);"></i> Keranjang Pesanan</h2>
      <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="confirmClearCart()" id="btnClearCart" style="display:none;">
        <i class="fa-solid fa-trash-can"></i> Kosongkan
      </button>
    </div>

    <!-- Empty State -->
    <div id="cartEmptyState" style="display:none; text-align:center; padding:3rem 1rem;">
      <div style="width:100px; height:100px; border-radius:50%; background:#fff1f2; color:var(--primary); display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem auto; font-size:3rem;">
        <i class="fa-solid fa-basket-shopping"></i>
      </div>
      <h3 style="font-weight:700; margin-bottom:0.5rem;">Keranjang Anda Masih Kosong</h3>
      <p style="color:var(--text-secondary); max-width:320px; margin:0 auto 1.5rem auto; font-size:0.875rem;">
        Yuk, pilih makanan lezat dan minuman segar favorit Anda di menu sekarang!
      </p>
      <a href="<?= BASE_URL ?>menu.php" class="sy-btn sy-btn-primary sy-btn-lg sy-btn-pill">
        <i class="fa-solid fa-utensils"></i> Buka Daftar Menu
      </a>
    </div>

    <!-- Items List Container -->
    <div id="cartItemsList" style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.5rem;"></div>

    <!-- Order Summary Card -->
    <div id="cartSummaryCard" class="sy-card" style="display:none; margin-bottom:1.5rem;">
      <div class="sy-card-title" style="margin-bottom:0.75rem;">
        <i class="fa-solid fa-file-invoice-dollar" style="color:var(--primary);"></i> Ringkasan Pembayaran
      </div>
      <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; font-size:0.9rem;">
        <span style="color:var(--text-secondary);">Total Item</span>
        <span id="summaryTotalItems" style="font-weight:600;">0 Menu</span>
      </div>
      <div style="display:flex; justify-content:space-between; margin-bottom:0.75rem; font-size:0.9rem;">
        <span style="color:var(--text-secondary);">Subtotal Makanan</span>
        <span id="summarySubtotal" style="font-weight:600;">Rp 0</span>
      </div>
      <div style="border-top:1px dashed var(--border); padding-top:0.75rem; display:flex; justify-content:space-between; font-size:1.15rem; font-weight:800;">
        <span>Total Pembayaran</span>
        <span id="summaryGrandTotal" style="color:var(--primary);">Rp 0</span>
      </div>
    </div>

    <!-- Checkout Button -->
    <div id="cartActionBox" style="display:none;">
      <a href="<?= BASE_URL ?>checkout.php" class="sy-btn sy-btn-primary sy-btn-block sy-btn-lg sy-btn-pill" style="font-weight:700;">
        Lanjut ke Checkout <i class="fa-solid fa-arrow-right"></i>
      </a>
      <div style="text-align:center; margin-top:0.75rem;">
        <a href="<?= BASE_URL ?>menu.php" style="font-size:0.85rem; color:var(--text-secondary); font-weight:600;">
          + Tambah Menu Lainnya
        </a>
      </div>
    </div>
  </main>
</div>

<script>
function renderCartPage() {
  const items = Cart.getItems();
  const emptyState = document.getElementById('cartEmptyState');
  const itemsList = document.getElementById('cartItemsList');
  const summaryCard = document.getElementById('cartSummaryCard');
  const actionBox = document.getElementById('cartActionBox');
  const btnClear = document.getElementById('btnClearCart');

  if (items.length === 0) {
    emptyState.style.display = 'block';
    itemsList.style.display = 'none';
    summaryCard.style.display = 'none';
    actionBox.style.display = 'none';
    btnClear.style.display = 'none';
    return;
  }

  emptyState.style.display = 'none';
  itemsList.style.display = 'flex';
  summaryCard.style.display = 'block';
  actionBox.style.display = 'block';
  btnClear.style.display = 'inline-flex';

  let html = '';
  items.forEach((it, idx) => {
    const subtotal = it.price * it.quantity;
    html += `
      <div class="sy-card" style="display:flex; gap:0.85rem; padding:0.85rem; align-items:center;">
        <img src="${escapeHtml(it.image || 'assets/images/logo.svg')}" alt="${escapeHtml(it.name)}" style="width:72px; height:72px; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--border);">
        <div style="flex:1;">
          <h4 style="font-size:0.95rem; font-weight:700; margin-bottom:2px;">${escapeHtml(it.name)}</h4>
          <div style="color:var(--primary); font-weight:700; font-size:0.9rem; margin-bottom:4px;">${formatRupiah(it.price)}</div>
          ${it.spicy_label ? `<span class="sy-badge badge-spicy" style="font-size:0.7rem; padding:1px 6px; margin-bottom:4px;"><i class="fa-solid fa-pepper-hot"></i> ${escapeHtml(it.spicy_label)}</span>` : ''}
          ${it.notes ? `<div style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">"${escapeHtml(it.notes)}"</div>` : ''}
        </div>
        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.5rem;">
          <button type="button" onclick="Cart.removeItem(${idx})" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:0.85rem; padding:2px;">
            <i class="fa-regular fa-trash-can"></i>
          </button>
          <div class="quantity-stepper">
            <button type="button" class="stepper-btn" style="width:28px; height:28px; font-size:1rem;" onclick="Cart.updateQty(${idx}, -1)">-</button>
            <span class="stepper-value" style="font-size:0.95rem; min-width:20px;">${it.quantity}</span>
            <button type="button" class="stepper-btn" style="width:28px; height:28px; font-size:1rem;" onclick="Cart.updateQty(${idx}, 1)">+</button>
          </div>
          <div style="font-weight:700; font-size:0.9rem; color:var(--text-primary);">${formatRupiah(subtotal)}</div>
        </div>
      </div>
    `;
  });

  itemsList.innerHTML = html;

  // Update summary
  document.getElementById('summaryTotalItems').textContent = Cart.getCount() + ' Menu';
  document.getElementById('summarySubtotal').textContent = formatRupiah(Cart.getTotal());
  document.getElementById('summaryGrandTotal').textContent = formatRupiah(Cart.getTotal());
}

function confirmClearCart() {
  if (confirm('Apakah Anda yakin ingin mengosongkan seluruh isi keranjang?')) {
    Cart.clear();
    renderCartPage();
    showToast('Keranjang telah dikosongkan', 'info');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  renderCartPage();
  window.addEventListener('cart-updated', () => renderCartPage());
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

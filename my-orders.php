<?php
/**
 * RM bu ani - My Orders (Pesanan Saya)
 * Compliant with PRD Section 4.10 & 4.11
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

require_login('login.php');

$pageTitle = "Pesanan Saya - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="app-wrapper">
  <!-- Status Tabs -->
  <div class="tabs-scroll-wrapper">
    <div class="sy-tab-list container-sm" style="justify-content:space-around;">
      <button class="sy-tab sy-tab-underline active" onclick="switchOrderTab('unpaid', this)">
        <i class="fa-solid fa-clock"></i> Belum Dibayar
      </button>
      <button class="sy-tab sy-tab-underline" onclick="switchOrderTab('processing', this)">
        <i class="fa-solid fa-fire-burner"></i> Diproses
      </button>
      <button class="sy-tab sy-tab-underline" onclick="switchOrderTab('completed', this)">
        <i class="fa-solid fa-circle-check"></i> Selesai
      </button>
    </div>
  </div>

  <main class="container-sm" style="margin-top:1rem;">
    <!-- Loading Spinner -->
    <div id="ordersLoading" style="text-align:center; padding:3rem 1rem;">
      <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem; color:var(--primary);"></i>
      <p style="margin-top:0.75rem; font-size:0.875rem;">Memuat daftar pesanan Anda...</p>
    </div>

    <!-- Empty State -->
    <div id="ordersEmptyState" style="display:none; text-align:center; padding:3rem 1rem;">
      <div style="width:80px; height:80px; border-radius:50%; background:#f1f3f5; color:var(--text-muted); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem auto; font-size:2.25rem;">
        <i class="fa-regular fa-folder-open"></i>
      </div>
      <h3 style="font-weight:700; margin-bottom:0.35rem;" id="emptyTitle">Belum Ada Pesanan</h3>
      <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:1.25rem;" id="emptyDesc">
        Anda belum memiliki pesanan pada kategori ini.
      </p>
      <a href="<?= BASE_URL ?>menu.php" class="sy-btn sy-btn-primary sy-btn-pill">
        <i class="fa-solid fa-utensils"></i> Buka Menu Restoran
      </a>
    </div>

    <!-- Orders Cards Container -->
    <div id="ordersContainer" style="display:flex; flex-direction:column; gap:1rem;"></div>
  </main>
</div>

<!-- Modal Rating & Review -->
<div id="reviewModal" class="sy-modal-overlay">
  <div class="sy-modal-dialog">
    <div class="sy-modal-header">
      <h3 style="margin:0;"><i class="fa-solid fa-star" style="color:#f59e0b;"></i> Beri Ulasan Makanan</h3>
      <button type="button" class="sy-modal-close" onclick="closeModal('reviewModal')">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="sy-modal-body">
      <p style="font-size:0.875rem; color:var(--text-secondary); margin-bottom:1rem;">
        Bagaimana rasa hidangan dan pelayanan di RM bu ani hari ini?
      </p>

      <div style="display:flex; justify-content:center; gap:0.5rem; margin-bottom:1.25rem; font-size:2.25rem; color:#cbd5e1; cursor:pointer;" id="starRatingContainer">
        <i class="fa-solid fa-star star-btn" data-value="1" onclick="setRating(1)"></i>
        <i class="fa-solid fa-star star-btn" data-value="2" onclick="setRating(2)"></i>
        <i class="fa-solid fa-star star-btn" data-value="3" onclick="setRating(3)"></i>
        <i class="fa-solid fa-star star-btn" data-value="4" onclick="setRating(4)"></i>
        <i class="fa-solid fa-star star-btn" data-value="5" onclick="setRating(5)"></i>
      </div>
      <div id="ratingText" style="text-align:center; font-weight:700; color:var(--text-primary); margin-bottom:1rem; font-size:0.95rem;">
        Pilih Bintang (1 - 5)
      </div>

      <div class="form-group">
        <label class="form-label" for="reviewComment">Komentar / Masukan (Opsional)</label>
        <textarea id="reviewComment" class="form-control" rows="3" placeholder="Contoh: Ayam bakarnya sangat enak meresap, sambalnya mantap!"></textarea>
      </div>

      <input type="hidden" id="reviewOrderCode" value="">
    </div>
    <div class="sy-modal-footer">
      <button type="button" class="sy-btn sy-btn-secondary" onclick="closeModal('reviewModal')">Batal</button>
      <button type="button" class="sy-btn sy-btn-primary" id="btnSubmitReview" onclick="submitReview()">
        <i class="fa-solid fa-paper-plane"></i> Kirim Ulasan
      </button>
    </div>
  </div>
</div>

<script>
let currentTab = 'unpaid';
let allOrdersData = [];
let selectedRating = 5;

// Load orders from API
async function loadOrders(silent = false) {
  if (!silent) {
    document.getElementById('ordersLoading').style.display = 'block';
    document.getElementById('ordersContainer').style.display = 'none';
    document.getElementById('ordersEmptyState').style.display = 'none';
  }

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/orders/my-orders.php');
    document.getElementById('ordersLoading').style.display = 'none';

    if (res.success) {
      allOrdersData = res.data.orders || [];
      renderOrders();
    }
  } catch (err) {
    document.getElementById('ordersLoading').style.display = 'none';
    showToast('Gagal memuat pesanan: ' + err.message, 'error');
  }
}

function switchOrderTab(tab, btn) {
  currentTab = tab;
  document.querySelectorAll('.sy-tab-underline').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderOrders();
}

function renderOrders() {
  const container = document.getElementById('ordersContainer');
  const emptyState = document.getElementById('ordersEmptyState');
  const emptyTitle = document.getElementById('emptyTitle');
  const emptyDesc = document.getElementById('emptyDesc');

  const filtered = allOrdersData.filter(o => o.status === currentTab);

  if (filtered.length === 0) {
    container.style.display = 'none';
    emptyState.style.display = 'block';
    if (currentTab === 'unpaid') {
      emptyTitle.textContent = 'Tidak Ada Pesanan Belum Dibayar';
      emptyDesc.textContent = 'Semua pesanan Anda telah lunas atau belum ada pesanan baru.';
    } else if (currentTab === 'processing') {
      emptyTitle.textContent = 'Tidak Ada Pesanan Sedang Diproses';
      emptyDesc.textContent = 'Dapur telah menyelesaikan pesanan Anda atau Anda belum melakukan pembayaran.';
    } else {
      emptyTitle.textContent = 'Belum Ada Pesanan Selesai';
      emptyDesc.textContent = 'Pesanan yang telah diselesaikan dapur akan muncul di sini.';
    }
    return;
  }

  emptyState.style.display = 'none';
  container.style.display = 'flex';

  let html = '';
  filtered.forEach(o => {
    let itemsHtml = '';
    o.items.forEach(it => {
      itemsHtml += `
        <div style="display:flex; justify-content:space-between; font-size:0.875rem; padding:0.25rem 0;">
          <div>
            <span style="font-weight:700;">${it.quantity}x</span> ${escapeHtml(it.menu_name)}
            ${it.spicy_label ? `<span class="sy-badge badge-spicy" style="font-size:0.65rem; padding:1px 5px; margin-left:4px;">${escapeHtml(it.spicy_label)}</span>` : ''}
            ${it.item_notes ? `<div style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">"${escapeHtml(it.item_notes)}"</div>` : ''}
          </div>
          <div style="font-weight:600;">${formatRupiah(it.subtotal)}</div>
        </div>
      `;
    });

    // Content specific to tab
    let specificContent = '';

    if (o.status === 'unpaid') {
      specificContent = `
        <!-- Barcode Box (PRD Section 7.1) -->
        <div class="barcode-box">
          <p style="font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem; text-transform:uppercase; font-weight:600;">
            Tunjukkan Barcode ke Kasir untuk Pembayaran:
          </p>
          <div id="barcode-${o.order_code}" class="barcode-svg"></div>
          <div style="font-size:0.85rem; font-weight:700; color:var(--text-primary); margin-top:0.35rem; letter-spacing:1px;">
            ${escapeHtml(o.order_code)}
          </div>
        </div>

        <div style="display:flex; gap:0.5rem; margin-top:0.75rem;">
          <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" style="flex:1; color:var(--danger); border-color:#fca5a5;" onclick="cancelOrder('${o.order_code}')">
            <i class="fa-solid fa-xmark"></i> Batalkan Pesanan
          </button>
          <a href="<?= BASE_URL ?>order-detail.php?code=${encodeURIComponent(o.order_code)}" class="sy-btn sy-btn-primary sy-btn-sm" style="flex:1;">
            <i class="fa-solid fa-receipt"></i> Detail Transaksi
          </a>
        </div>
      `;
    } else if (o.status === 'processing') {
      specificContent = `
        <div style="background:#e6f6f4; border:1px solid #b7e3de; border-radius:var(--radius-md); padding:0.85rem; margin-top:0.75rem; color:#166534; font-size:0.85rem;">
          <div style="display:flex; align-items:center; gap:0.5rem; font-weight:700; margin-bottom:0.25rem;">
            <i class="fa-solid fa-fire-burner fa-bounce"></i> Makanan Sedang Disiapkan di Dapur
          </div>
          Pembayaran telah dikonfirmasi kasir pada ${escapeHtml(o.paid_at || o.formatted_time)}. Staf dapur kami sedang memasak hidangan Anda dengan sepenuh hati.
        </div>
      `;
    } else if (o.status === 'completed') {
      const deliveryNotice = (o.order_type === 'takeaway')
        ? `<div style="background:#fff3e0; border:1px solid #ffe0b2; border-radius:var(--radius-md); padding:0.85rem; margin-top:0.75rem; color:#c2410c; font-size:0.85rem; font-weight:600;">
             <i class="fa-solid fa-bag-shopping"></i> Pesanan Anda sudah siap! Silakan ambil makanan di kasir.
           </div>`
        : `<div style="background:#e8f7ee; border:1px solid #bce6cb; border-radius:var(--radius-md); padding:0.85rem; margin-top:0.75rem; color:#1e7441; font-size:0.85rem; font-weight:600;">
             <i class="fa-solid fa-bell-concierge"></i> Makanan selesai disiapkan dan akan segera diantar ke Meja ${escapeHtml(o.table_number || '')}.
           </div>`;

      let reviewBtn = '';
      if (!o.has_reviewed) {
        reviewBtn = `
          <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" style="margin-top:0.75rem; width:100%;" onclick="openReviewModal('${o.order_code}')">
            <i class="fa-regular fa-star" style="color:#f59e0b;"></i> Beri Ulasan & Rating
          </button>
        `;
      } else {
        reviewBtn = `
          <div style="margin-top:0.75rem; font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:0.35rem;">
            <i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Anda memberi nilai ${o.review.rating} bintang: "${escapeHtml(o.review.comment || 'Puas')}"
          </div>
        `;
      }

      specificContent = `
        ${deliveryNotice}
        ${reviewBtn}
      `;
    }

    html += `
      <div class="sy-card" style="border-radius:var(--radius-lg); position:relative;">
        <div class="sy-card-header" style="margin-bottom:0.5rem; padding-bottom:0.5rem;">
          <div>
            <div style="font-weight:800; font-size:1rem; color:var(--text-primary);">${escapeHtml(o.order_code)}</div>
            <div style="font-size:0.775rem; color:var(--text-muted);">${escapeHtml(o.formatted_time)}</div>
          </div>
          <div>
            <span class="sy-badge ${escapeHtml(o.status_badge)}">${escapeHtml(o.status_label)}</span>
          </div>
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
          <span class="sy-badge ${o.order_type === 'takeaway' ? 'badge-takeaway' : 'badge-dinein'}">
            <i class="fa-solid ${o.order_type === 'takeaway' ? 'fa-bag-shopping' : 'fa-chair'}"></i> ${escapeHtml(o.order_type_label)}
          </span>
          ${o.table_number ? `<span style="font-size:0.85rem; font-weight:700;">Meja ${escapeHtml(o.table_number)}</span>` : ''}
        </div>

        <div style="border-top:1px solid var(--border-light); border-bottom:1px solid var(--border-light); padding:0.5rem 0; margin-bottom:0.5rem;">
          ${itemsHtml}
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.95rem;">
          <span style="color:var(--text-secondary);">Total Tagihan:</span>
          <span style="font-weight:800; color:var(--primary); font-size:1.1rem;">${escapeHtml(o.formatted_total)}</span>
        </div>

        ${specificContent}
      </div>
    `;
  });

  container.innerHTML = html;

  // Render JsBarcode for all unpaid orders
  if (currentTab === 'unpaid') {
    filtered.forEach(o => {
      renderOrderBarcode(`#barcode-${o.order_code}`, o.order_code);
    });
  }
}

// Cancel Order Handler
async function cancelOrder(orderCode) {
  if (!confirm(`Batalkan pesanan ${orderCode}? Tindakan ini tidak dapat diurungkan.`)) {
    return;
  }

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/orders/cancel.php', {
      method: 'POST',
      body: JSON.stringify({ order_code: orderCode })
    });

    if (res.success) {
      showToast('Pesanan berhasil dibatalkan', 'info');
      loadOrders(true);
    } else {
      showToast(res.message, 'error');
    }
  } catch (err) {
    showToast('Gagal membatalkan pesanan: ' + err.message, 'error');
  }
}

// Review Rating System
function openReviewModal(orderCode) {
  document.getElementById('reviewOrderCode').value = orderCode;
  document.getElementById('reviewComment').value = '';
  setRating(5);
  openModal('reviewModal');
}

function setRating(val) {
  selectedRating = val;
  const labels = {
    1: '⭐ Sangat Kurang',
    2: '⭐⭐ Kurang',
    3: '⭐⭐⭐ Cukup Baik',
    4: '⭐⭐⭐⭐ Enak & Puas',
    5: '⭐⭐⭐⭐⭐ Sangat Puas & Lezat!'
  };

  document.querySelectorAll('.star-btn').forEach(btn => {
    const v = parseInt(btn.getAttribute('data-value'), 10);
    if (v <= val) {
      btn.style.color = '#f59e0b';
    } else {
      btn.style.color = '#cbd5e1';
    }
  });

  document.getElementById('ratingText').textContent = labels[val] || '';
}

async function submitReview() {
  const orderCode = document.getElementById('reviewOrderCode').value;
  const comment = document.getElementById('reviewComment').value.trim();
  const btn = document.getElementById('btnSubmitReview');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/reviews/create.php', {
      method: 'POST',
      body: JSON.stringify({
        order_code: orderCode,
        rating: selectedRating,
        comment: comment
      })
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Ulasan';

    if (res.success) {
      closeModal('reviewModal');
      showToast('Ulasan Anda berhasil dikirim, terima kasih!', 'success');
      loadOrders(true);
    } else {
      showToast(res.message, 'error');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Ulasan';
    showToast('Gagal mengirim ulasan: ' + err.message, 'error');
  }
}

// Polling for updates (PRD Section 8.4: 5s interval)
document.addEventListener('DOMContentLoaded', () => {
  loadOrders();

  // Polling to detect kitchen completion or payment confirmation without full page reload
  window.createPoller('api/orders/my-orders.php', 5000, (data) => {
    if (data && data.orders) {
      allOrdersData = data.orders;
      renderOrders();
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * RM bu ani - Kitchen Display System (KDS)
 * Dedicated, distraction-free screen designed for kitchen staff & tablets
 * Compliant with PRD Section 5.4 & 8.6
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role(['kitchen', 'admin'], 'login.php');

$currentUser = current_user();
$pageTitle = "Layar Dapur (KDS) - " . APP_NAME;
$bodyClass = "kds-body";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kds-body">
  <!-- KDS Header Bar -->
  <header class="kds-header no-print">
    <div class="kds-title">
      <div class="app-brand-logo" style="width:38px; height:38px; font-size:1.2rem; background:#ea580c;">
        <i class="fa-solid fa-fire-burner"></i>
      </div>
      <div>
        <div style="line-height:1.2;"><?= APP_NAME ?> - Dapur (KDS)</div>
        <div style="font-size:0.75rem; color:#94a3b8; font-weight:400;">
          Staf: <?= e($currentUser['name']) ?> | Auto-Refresh 3-5s
        </div>
      </div>
    </div>

    <div style="display:flex; align-items:center; gap:1rem;">
      <div style="background:#0f172a; border:1px solid #334155; border-radius:var(--radius-full); padding:0.35rem 1rem; font-size:0.875rem; font-weight:700; color:#38bdf8;">
        Antrean Memasak: <span id="kdsQueueCount" style="color:#ffffff; font-size:1.1rem;">0</span> Pesanan
      </div>

      <button type="button" class="sy-btn sy-btn-outline sy-btn-sm" onclick="loadKitchenOrders()" style="color:#cbd5e1; border-color:#475569;">
        <i class="fa-solid fa-arrows-rotate"></i> Refresh
      </button>

      <a href="<?= BASE_URL ?>admin/index.php" class="sy-btn sy-btn-outline sy-btn-sm" style="color:#cbd5e1; border-color:#475569;">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>
  </header>

  <!-- Empty Queue State -->
  <div id="kdsEmptyState" style="display:none; text-align:center; padding:5rem 1.5rem;">
    <div style="width:100px; height:100px; border-radius:50%; background:#334155; color:#94a3b8; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem auto; font-size:3rem;">
      <i class="fa-solid fa-mug-hot"></i>
    </div>
    <h2 style="color:#f8fafc; font-weight:800; font-size:1.75rem; margin-bottom:0.5rem;">Semua Pesanan Selesai Disiapkan!</h2>
    <p style="color:#94a3b8; max-width:420px; margin:0 auto; font-size:1rem;">
      Tidak ada antrean masakan yang sedang menunggu. Pesanan baru yang sudah dibayar di kasir akan otomatis muncul di sini.
    </p>
  </div>

  <!-- Loading State -->
  <div id="kdsLoading" style="text-align:center; padding:5rem;">
    <i class="fa-solid fa-spinner fa-spin" style="font-size:3rem; color:#ea580c;"></i>
    <p style="margin-top:1rem; color:#94a3b8; font-size:1.1rem;">Menghubungkan ke sistem dapur...</p>
  </div>

  <!-- Kitchen Cards Grid -->
  <main class="kds-grid" id="kdsGridContainer" style="display:none;"></main>
</div>

<script>
let lastKnownOrderIds = [];
let audioChime = null;

// Initialize simple audio chime notification on new incoming order
try {
  const AudioContext = window.AudioContext || window.webkitAudioContext;
  if (AudioContext) {
    const ctx = new AudioContext();
    audioChime = function() {
      if (ctx.state === 'suspended') ctx.resume();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
      osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.5);
    };
  }
} catch (e) {
  console.warn("Audio chime disabled:", e);
}

async function loadKitchenOrders(silent = false) {
  if (!silent) {
    document.getElementById('kdsLoading').style.display = 'block';
    document.getElementById('kdsGridContainer').style.display = 'none';
    document.getElementById('kdsEmptyState').style.display = 'none';
  }

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/kitchen/orders.php');
    document.getElementById('kdsLoading').style.display = 'none';

    if (res.success) {
      const orders = res.data.orders || [];
      document.getElementById('kdsQueueCount').textContent = orders.length;

      // Check for new orders to chime
      const currentIds = orders.map(o => o.id);
      const newOrders = currentIds.filter(id => !lastKnownOrderIds.includes(id));
      if (newOrders.length > 0 && lastKnownOrderIds.length > 0) {
        if (typeof audioChime === 'function') audioChime();
        showToast(`Pesanan Baru Diterima: ${newOrders.length} antrean masuk!`, 'warning', 3000);
      }
      lastKnownOrderIds = currentIds;

      renderKitchenOrders(orders);
    }
  } catch (err) {
    document.getElementById('kdsLoading').style.display = 'none';
    showToast('KDS Offline: ' + err.message, 'error');
  }
}

function renderKitchenOrders(orders) {
  const container = document.getElementById('kdsGridContainer');
  const emptyState = document.getElementById('kdsEmptyState');

  if (orders.length === 0) {
    container.style.display = 'none';
    emptyState.style.display = 'block';
    return;
  }

  emptyState.style.display = 'none';
  container.style.display = 'grid';

  let html = '';
  orders.forEach(o => {
    // Determine takeaway vs dine-in badge
    const typeBadge = o.is_takeaway
      ? `<div class="kds-table-badge takeaway"><i class="fa-solid fa-bag-shopping"></i> BUNGKUS (TAKE AWAY)</div>`
      : `<div class="kds-table-badge dinein"><i class="fa-solid fa-chair"></i> MEJA ${escapeHtml(o.table_number || '-')}</div>`;

    let itemsHtml = '';
    o.items.forEach(it => {
      itemsHtml += `
        <div class="kds-item-row">
          <div class="kds-item-qty">${it.quantity}x</div>
          <div class="kds-item-info">
            <div class="kds-item-name">${escapeHtml(it.menu_name)}</div>
            ${it.spicy_label ? `<div class="kds-item-spicy"><i class="fa-solid fa-pepper-hot"></i> ${escapeHtml(it.spicy_label)}</div>` : ''}
            ${it.item_notes ? `<div class="kds-item-notes"><i class="fa-solid fa-triangle-exclamation"></i> "${escapeHtml(it.item_notes)}"</div>` : ''}
          </div>
        </div>
      `;
    });

    html += `
      <div class="kds-card" id="kds-card-${o.id}">
        <div class="kds-card-header">
          <div>
            <div style="font-weight:800; font-size:1.15rem; color:#f8fafc; letter-spacing:0.5px;">${escapeHtml(o.order_code)}</div>
            <div style="font-size:0.85rem; color:#94a3b8; margin-top:2px;">
              Pemesan: <strong>${escapeHtml(o.customer_name)}</strong>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:0.8rem; color:#38bdf8; font-weight:700;"><i class="fa-regular fa-clock"></i> ${escapeHtml(o.time_label)}</div>
          </div>
        </div>

        <div style="padding:0.65rem 1rem; background:#1e293b; border-bottom:1px solid #475569;">
          ${typeBadge}
        </div>

        <div class="kds-card-body">
          ${itemsHtml}
          ${o.notes ? `<div style="margin-top:0.75rem; padding:0.5rem; background:#1e293b; border-radius:var(--radius-sm); border-left:3px solid #f59e0b; font-size:0.85rem; color:#fbbf24;">
            <strong>Catatan Pesanan:</strong> "${escapeHtml(o.notes)}"
          </div>` : ''}
        </div>

        <div class="kds-card-footer">
          <button type="button" class="kds-btn-complete" onclick="completeKitchenOrder(${o.id}, '${o.order_code}')">
            <i class="fa-solid fa-check-double"></i> SELESAIKAN PESANAN
          </button>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;
}

// Complete Order
async function completeKitchenOrder(orderId, orderCode) {
  if (!confirm(`Tandai pesanan ${orderCode} telah SELESAI disiapkan?`)) {
    return;
  }

  // Optimistic UI hide
  const card = document.getElementById('kds-card-' + orderId);
  if (card) {
    card.style.opacity = '0.4';
    card.style.pointerEvents = 'none';
  }

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/kitchen/complete.php', {
      method: 'POST',
      body: JSON.stringify({ order_id: orderId })
    });

    if (res.success) {
      showToast(`Pesanan ${orderCode} selesai!`, 'success', 2500);
      loadKitchenOrders(true);
    } else {
      if (card) {
        card.style.opacity = '1';
        card.style.pointerEvents = 'auto';
      }
      showToast(res.message, 'error');
    }
  } catch (err) {
    if (card) {
      card.style.opacity = '1';
      card.style.pointerEvents = 'auto';
    }
    showToast('Gagal menyelesaikan pesanan: ' + err.message, 'error');
  }
}

// Polling interval: 3-5 seconds (PRD Section 2.4 & 8.6)
document.addEventListener('DOMContentLoaded', () => {
  loadKitchenOrders();
  window.createPoller('api/kitchen/orders.php', 4000, (data) => {
    if (data && data.orders) {
      renderKitchenOrders(data.orders);
      document.getElementById('kdsQueueCount').textContent = data.orders.length;
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

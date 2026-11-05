<?php
/**
 * RM bu ani - Common HTML Footer & Script Bundler
 */
require_once __DIR__ . '/toast.php';
?>

<!-- Outlet Information Modal (Matching ESB Order Screenshot 2) -->
<div id="outletInfoModal" class="sy-modal-overlay">
  <div class="sy-modal-dialog">
    <div class="sy-modal-header">
      <div style="display:flex; align-items:center; gap:0.5rem;">
        <button class="sy-modal-close" onclick="closeOutletModal()" style="margin-right:0.25rem;">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h3 style="margin:0;">Info Outlet</h3>
      </div>
      <button class="sy-modal-close" onclick="closeOutletModal()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="sy-modal-body">
      <div style="margin-bottom: 1.25rem;">
        <h2 style="font-size: 1.25rem; font-weight:700; color:var(--text-primary); margin-bottom:0.35rem;"><?= APP_NAME ?></h2>
        <div style="display:flex; align-items:flex-start; gap:0.5rem; color:var(--text-secondary); font-size:0.875rem;">
          <i class="fa-solid fa-location-dot" style="margin-top:3px; color:var(--primary);"></i>
          <span><?= APP_ADDRESS ?></span>
        </div>
      </div>

      <div style="display:flex; gap:0.75rem; margin-bottom: 1.5rem;">
        <a href="tel:<?= APP_PHONE ?>" class="sy-btn sy-btn-outline sy-btn-block sy-btn-sm" style="flex:1;">
          <i class="fa-solid fa-phone"></i> Hubungi Outlet
        </a>
        <a href="https://maps.google.com/?q=<?= urlencode(APP_NAME . ' ' . APP_ADDRESS) ?>" target="_blank" class="sy-btn sy-btn-outline sy-btn-block sy-btn-sm" style="flex:1;">
          <i class="fa-solid fa-map-location-dot"></i> Kunjungi Outlet
        </a>
      </div>

      <h4 style="font-weight:700; margin-bottom:0.75rem; font-size:0.95rem; text-transform:uppercase; letter-spacing:0.5px;">Jam Operasional</h4>
      <div style="border:1px solid var(--border); border-radius:var(--radius-md); overflow:hidden;">
        <?php
        $days = [
            1 => 'SENIN', 2 => 'SELASA', 3 => 'RABU', 4 => 'KAMIS',
            5 => 'JUMAT', 6 => 'SABTU', 7 => 'MINGGU'
        ];
        $currentDayNumber = (int)date('N');
        foreach ($days as $num => $dayName):
            $isToday = ($num === $currentDayNumber);
        ?>
          <div style="display:flex; justify-content:space-between; padding:0.65rem 1rem; border-bottom:1px solid var(--border-light); font-size:0.875rem; <?= $isToday ? 'background-color:#fff5f5; font-weight:700; color:var(--primary);' : '' ?>">
            <span><?= $dayName ?> <?= $isToday ? '<span class="sy-badge badge-unpaid" style="font-size:0.65rem; padding:1px 6px;">Hari Ini</span>' : '' ?></span>
            <span><?= APP_OPENING_HOURS ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- JsBarcode Library for CODE128 (PRD Section 7.1) -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<!-- Offline Fallback for JsBarcode if CDN fails -->
<script src="<?= BASE_URL ?>assets/js/barcode.js"></script>

<!-- Global App Scripts -->
<script src="<?= BASE_URL ?>assets/js/app.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>assets/js/cart.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>assets/js/notifications.js?v=<?= time() ?>"></script>

<script>
function openOutletModal() {
  document.getElementById('outletInfoModal').classList.add('popup-open');
}
function closeOutletModal() {
  document.getElementById('outletInfoModal').classList.remove('popup-open');
}
</script>
</body>
</html>

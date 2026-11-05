<?php
/**
 * RM bu ani - Menu Ordering Interface (Inspired by ESB Order)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Check if user came with a table query ?meja=XX
if (isset($_GET['meja'])) {
    $qrTable = sanitize_table_number($_GET['meja']);
    if ($qrTable) {
        $_SESSION['selected_table'] = $qrTable;
        $_SESSION['order_type'] = 'dine_in';
    }
}
if (isset($_GET['type']) && $_GET['type'] === 'takeaway') {
    $_SESSION['order_type'] = 'takeaway';
    unset($_SESSION['selected_table']);
}

$currentTable = get_session_table();
$orderType = get_session_order_type();

// Fetch all menus and categories
$categories = [];
$menus = [];
try {
    $db = getDB();
    $catStmt = $db->query("SELECT DISTINCT category FROM menus WHERE is_available = 1 ORDER BY category ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    $menuStmt = $db->query("SELECT * FROM menus ORDER BY category ASC, id ASC");
    $menus = $menuStmt->fetchAll();
} catch (Exception $e) {
    header("Location: " . BASE_URL . "setup_db.php");
    exit;
}

$pageTitle = "Daftar Menu - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="app-wrapper">
  <!-- Top Outlet Banner (Matching ESB Order Screenshot 1) -->
  <div style="background: linear-gradient(135deg, #1f2937 0%, #111827 50%, #991b1b 100%); color: #ffffff; padding: 1.25rem 1rem 0.85rem 1rem;">
    <div class="container-sm" style="display:flex; justify-content:space-between; align-items:flex-start;">
      <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
          <h2 style="color:#ffffff; font-weight:800; font-size:1.25rem; margin:0;"><?= APP_NAME ?></h2>
          <span class="sy-badge" style="background:#e63946; color:#ffffff; font-size:0.65rem; padding:2px 6px;">Buka</span>
        </div>
        <div onclick="openOutletModal()" style="display:inline-flex; align-items:center; gap:0.4rem; color:rgba(255,255,255,0.85); font-size:0.8rem; cursor:pointer;">
          <span>Buka hari ini, <?= APP_OPENING_HOURS ?></span>
          <i class="fa-solid fa-chevron-right" style="font-size:0.65rem;"></i>
        </div>
      </div>

      <!-- Search Trigger Icon -->
      <button type="button" onclick="openSearchModal()" style="background:rgba(255,255,255,0.15); border:none; color:#ffffff; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer;">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </div>
  </div>

  <!-- Table & Order Type Banner Card -->
  <div class="container-sm">
    <div style="background:#ffffff; border-radius:var(--radius-lg); padding:0.75rem 1rem; margin:0.75rem 0; border:1px solid var(--border); box-shadow:var(--shadow-sm); display:flex; justify-content:space-between; align-items:center;">
      <div style="display:flex; align-items:center; gap:0.65rem;">
        <?php if ($orderType === 'takeaway'): ?>
          <span class="sy-badge badge-takeaway" style="font-size:0.85rem; padding:0.4rem 0.75rem;">
            <i class="fa-solid fa-bag-shopping"></i> Take Away (Bungkus)
          </span>
        <?php else: ?>
          <span class="sy-badge badge-dinein" style="font-size:0.85rem; padding:0.4rem 0.75rem;">
            <i class="fa-solid fa-chair"></i> Nomor Meja: <strong><?= e($currentTable ?: '01') ?></strong>
          </span>
        <?php endif; ?>
      </div>

      <a href="<?= BASE_URL ?>index.php" style="font-size:0.8rem; font-weight:600; color:var(--primary); text-decoration:none;">
        Ubah <i class="fa-solid fa-pen-to-square"></i>
      </a>
    </div>
  </div>

  <!-- Horizontal Scrollable Category Tabs (Matching ESB Order Screenshot 1) -->
  <div class="tabs-scroll-wrapper">
    <div class="sy-tab-list container-sm">
      <button class="sy-tab sy-tab-underline active" onclick="filterCategory('all', this)">
        SEMUA MENU
      </button>
      <?php foreach ($categories as $cat): ?>
        <button class="sy-tab sy-tab-underline" onclick="filterCategory('<?= e($cat) ?>', this)">
          <?= mb_strtoupper(e($cat)) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Menu Cards Grid -->
  <main class="container-sm" style="margin-top:1rem;">
    <!-- Active Category Title Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; padding:0 0.25rem;">
      <h3 id="currentCategoryTitle" style="font-size:1.1rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin:0;">
        SEMUA MENU
      </h3>
      <span id="menuCountBadge" style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">
        <?= count($menus) ?> menu
      </span>
    </div>

    <!-- 2-Column Responsive Grid -->
    <div class="menu-grid" id="menuGridContainer" style="padding:0;">
      <?php foreach ($menus as $m): ?>
        <div class="menu-card menu-item-card" data-category="<?= e($m['category']) ?>" data-name="<?= strtolower(e($m['name'])) ?>" onclick="openItemModal(<?= $m['id'] ?>)">
          <div class="menu-card-img-wrapper">
            <img src="<?= e($m['image'] ?: 'assets/images/logo.svg') ?>" alt="<?= e($m['name']) ?>" class="menu-card-img" loading="lazy" id="menu-img-<?= $m['id'] ?>">
            <?php if (!$m['is_available']): ?>
              <div class="menu-card-soldout">
                <span>HABIS</span>
              </div>
            <?php elseif ($m['supports_spicy']): ?>
              <span class="sy-badge badge-spicy" style="position:absolute; top:8px; right:8px;">
                <i class="fa-solid fa-pepper-hot"></i> Level Pedas
              </span>
            <?php endif; ?>
          </div>
          <div class="menu-card-body">
            <h4 class="menu-card-title"><?= e($m['name']) ?></h4>
            <p class="menu-card-desc"><?= e($m['description']) ?></p>
            <div class="menu-card-footer">
              <span class="menu-card-price"><?= format_rupiah($m['price']) ?></span>
              <?php if ($m['is_available']): ?>
                <button type="button" class="sy-btn sy-btn-outline-primary sy-btn-sm sy-btn-pill" onclick="event.stopPropagation(); openItemModal(<?= $m['id'] ?>)">
                  Tambah
                </button>
              <?php else: ?>
                <button type="button" class="sy-btn sy-btn-secondary sy-btn-sm sy-btn-pill" disabled>
                  Habis
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <!-- Sticky Bottom Floating Cart Bar -->
  <div id="floatingCartBar" class="floating-cart-bar" style="display:none;" onclick="window.location.href='<?= BASE_URL ?>cart.php'">
    <div class="floating-cart-left">
      <div class="floating-cart-badge cart-count-badge" id="floatingCartCount">0</div>
      <div>
        <div style="font-size:0.75rem; opacity:0.9;">Total Pesanan</div>
        <div style="font-weight:700; font-size:1.05rem;" id="floatingCartTotal">Rp 0</div>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.4rem; font-weight:700; font-size:0.95rem;">
      <span>Lihat Keranjang</span>
      <i class="fa-solid fa-arrow-right"></i>
    </div>
  </div>
</div>

<!-- Modal Detail & Add to Cart (Matching ESB Order Screenshot 5) -->
<div id="itemDetailModal" class="sy-modal-overlay">
  <div class="sy-modal-dialog" style="max-height:92vh;">
    <div style="position:relative;">
      <img id="modalItemImg" src="assets/images/logo.svg" alt="Menu" class="detail-modal-img">
      <button type="button" class="sy-modal-close" onclick="closeItemModal()" style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.5); color:#ffffff; width:36px; height:36px;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="sy-modal-body" style="padding-top:0.75rem;">
      <h2 id="modalItemName" style="font-size:1.25rem; font-weight:800; color:var(--text-primary); margin-bottom:0.25rem;">
        Nama Menu
      </h2>
      <div id="modalItemPrice" style="font-size:1.15rem; font-weight:700; color:var(--primary); margin-bottom:0.5rem;">
        Rp 0
      </div>
      <p id="modalItemDesc" style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem; line-height:1.4;">
        Deskripsi menu
      </p>

      <!-- Spicy Level Selection Section (if menu supports spicy) -->
      <div id="modalSpicySection" style="display:none; border-top:1px solid var(--border-light); padding-top:1rem; margin-top:0.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
          <h4 style="font-size:0.95rem; font-weight:700; margin:0; display:flex; align-items:center; gap:0.4rem;">
            <i class="fa-solid fa-pepper-hot" style="color:var(--primary);"></i> PILIH LEVEL PEDAS
          </h4>
          <span style="font-size:0.75rem; color:var(--success); font-weight:600;"><i class="fa-solid fa-circle-check"></i> Wajib pilih 1</span>
        </div>

        <div class="spicy-selector-container">
          <label class="spicy-option selected" onclick="selectSpicyLevel(1, this)">
            <span style="font-weight:600; font-size:0.875rem;">LEVEL 1 (Tidak Pedas)</span>
            <input type="radio" name="spicy_level" value="1" checked style="accent-color:var(--primary);">
          </label>
          <label class="spicy-option" onclick="selectSpicyLevel(2, this)">
            <span style="font-weight:600; font-size:0.875rem;">LEVEL 2 (Sedikit Pedas)</span>
            <input type="radio" name="spicy_level" value="2" style="accent-color:var(--primary);">
          </label>
          <label class="spicy-option" onclick="selectSpicyLevel(3, this)">
            <span style="font-weight:600; font-size:0.875rem;">LEVEL 3 (Sedang)</span>
            <input type="radio" name="spicy_level" value="3" style="accent-color:var(--primary);">
          </label>
          <label class="spicy-option" onclick="selectSpicyLevel(4, this)">
            <span style="font-weight:600; font-size:0.875rem;">LEVEL 4 (Pedas)</span>
            <input type="radio" name="spicy_level" value="4" style="accent-color:var(--primary);">
          </label>
          <label class="spicy-option" onclick="selectSpicyLevel(5, this)">
            <span style="font-weight:600; font-size:0.875rem;">LEVEL 5 (Sangat Pedas)</span>
            <input type="radio" name="spicy_level" value="5" style="accent-color:var(--primary);">
          </label>
        </div>
      </div>

      <!-- Item Notes Section -->
      <div style="border-top:1px solid var(--border-light); padding-top:1rem; margin-top:0.75rem;">
        <label class="form-label" for="modalItemNotes" style="font-size:0.85rem; font-weight:600;">
          <i class="fa-regular fa-comment-dots"></i> Catatan Khusus (Opsional):
        </label>
        <input type="text" id="modalItemNotes" class="form-control" placeholder="Contoh: jangan pakai daun bawang, pisah sambal" maxlength="150" style="font-size:0.875rem;">
      </div>
    </div>

    <!-- Sticky Bottom Bar inside Modal -->
    <div class="sy-modal-footer" style="display:flex; align-items:center; justify-content:space-between; gap:1rem; background:#ffffff; border-top:1px solid var(--border); padding:0.85rem 1.25rem;">
      <!-- Quantity Stepper -->
      <div class="quantity-stepper">
        <button type="button" class="stepper-btn" onclick="changeModalQty(-1)" id="btnMinusQty">-</button>
        <span class="stepper-value" id="modalItemQty">1</span>
        <button type="button" class="stepper-btn" onclick="changeModalQty(1)">+</button>
      </div>

      <!-- Big CTA Submit Button with Live Price Recalculation -->
      <button type="button" id="modalAddToCartBtn" class="sy-btn sy-btn-primary sy-btn-lg" style="flex:1; border-radius:var(--radius-full); font-weight:700;" onclick="submitModalAddToCart()">
        Tambah Pesanan - <span id="modalBtnPrice">Rp 0</span>
      </button>
    </div>
  </div>
</div>

<!-- Search Modal -->
<div id="searchModal" class="sy-modal-overlay">
  <div class="sy-modal-dialog">
    <div class="sy-modal-header">
      <h3 style="margin:0;"><i class="fa-solid fa-magnifying-glass"></i> Cari Menu</h3>
      <button type="button" class="sy-modal-close" onclick="closeModal('searchModal')">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="sy-modal-body">
      <div class="form-group">
        <input type="text" id="menuSearchInput" class="form-control" placeholder="Ketik nama menu, misal: Ayam Bakar, Mie..." oninput="handleSearch(this.value)">
      </div>
      <p style="font-size:0.8rem; color:var(--text-muted);">
        Menampilkan menu yang cocok secara otomatis.
      </p>
    </div>
  </div>
</div>

<script>
// Active modal state
let currentModalItem = null;
let currentModalQty = 1;
let currentSpicyLevel = 1;

// Open modal for menu details
async function openItemModal(menuId) {
  try {
    const res = await apiFetch('<?= BASE_URL ?>api/menu/detail.php?id=' + menuId);
    if (!res.success) {
      showToast(res.message, 'error');
      return;
    }

    currentModalItem = res.data;
    currentModalQty = 1;
    currentSpicyLevel = 1;

    document.getElementById('modalItemImg').src = currentModalItem.image;
    document.getElementById('modalItemName').textContent = currentModalItem.name;
    document.getElementById('modalItemPrice').textContent = currentModalItem.formatted_price;
    document.getElementById('modalItemDesc').textContent = currentModalItem.description || '';
    document.getElementById('modalItemNotes').value = '';
    document.getElementById('modalItemQty').textContent = currentModalQty;

    // Spicy section
    const spicySection = document.getElementById('modalSpicySection');
    if (currentModalItem.supports_spicy) {
      spicySection.style.display = 'block';
      // Reset radio to level 1
      document.querySelectorAll('.spicy-option').forEach((opt, idx) => {
        if (idx === 0) {
          opt.classList.add('selected');
          opt.querySelector('input').checked = true;
        } else {
          opt.classList.remove('selected');
          opt.querySelector('input').checked = false;
        }
      });
    } else {
      spicySection.style.display = 'none';
    }

    updateModalTotalPrice();
    openModal('itemDetailModal');
  } catch (err) {
    showToast('Gagal memuat menu: ' + err.message, 'error');
  }
}

function closeItemModal() {
  closeModal('itemDetailModal');
  currentModalItem = null;
}

function changeModalQty(delta) {
  currentModalQty += delta;
  if (currentModalQty < 1) currentModalQty = 1;
  document.getElementById('modalItemQty').textContent = currentModalQty;
  document.getElementById('btnMinusQty').disabled = (currentModalQty <= 1);
  updateModalTotalPrice();
}

function selectSpicyLevel(lvl, element) {
  currentSpicyLevel = lvl;
  document.querySelectorAll('.spicy-option').forEach(o => o.classList.remove('selected'));
  element.classList.add('selected');
  element.querySelector('input').checked = true;
}

function updateModalTotalPrice() {
  if (!currentModalItem) return;
  const total = currentModalItem.price * currentModalQty;
  document.getElementById('modalBtnPrice').textContent = formatRupiah(total);
}

function submitModalAddToCart() {
  if (!currentModalItem) return;

  const notes = document.getElementById('modalItemNotes').value.trim();
  const spicyLevel = currentModalItem.supports_spicy ? currentSpicyLevel : null;
  const spicyLabelMap = {
    1: 'Level 1 (Tidak Pedas)',
    2: 'Level 2 (Sedikit Pedas)',
    3: 'Level 3 (Sedang)',
    4: 'Level 4 (Pedas)',
    5: 'Level 5 (Sangat Pedas)'
  };

  const cartItem = {
    id: currentModalItem.id,
    name: currentModalItem.name,
    price: currentModalItem.price,
    image: currentModalItem.image,
    quantity: currentModalQty,
    spicy_level: spicyLevel,
    spicy_label: spicyLevel ? spicyLabelMap[spicyLevel] : null,
    notes: notes
  };

  // Find source image element for fly to cart animation
  const sourceImg = document.getElementById('menu-img-' + currentModalItem.id) || document.getElementById('modalItemImg');

  Cart.addItem(cartItem, sourceImg);
  closeItemModal();
}

// Category Filtering
function filterCategory(cat, btn) {
  document.querySelectorAll('.sy-tab-underline').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');

  const titleEl = document.getElementById('currentCategoryTitle');
  titleEl.textContent = (cat === 'all') ? 'SEMUA MENU' : cat.toUpperCase();

  const cards = document.querySelectorAll('.menu-item-card');
  let visibleCount = 0;
  cards.forEach(c => {
    if (cat === 'all' || c.getAttribute('data-category') === cat) {
      c.style.display = 'flex';
      visibleCount++;
    } else {
      c.style.display = 'none';
    }
  });

  document.getElementById('menuCountBadge').textContent = visibleCount + ' menu';
}

// Search Filter
function openSearchModal() {
  openModal('searchModal');
  setTimeout(() => document.getElementById('menuSearchInput').focus(), 150);
}

function handleSearch(term) {
  const q = term.trim().toLowerCase();
  const cards = document.querySelectorAll('.menu-item-card');
  let count = 0;
  cards.forEach(c => {
    const name = c.getAttribute('data-name');
    if (!q || name.includes(q)) {
      c.style.display = 'flex';
      count++;
    } else {
      c.style.display = 'none';
    }
  });
  document.getElementById('menuCountBadge').textContent = count + ' menu';
}

// Check URL params for open modal
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const openId = urlParams.get('open');
  if (openId) {
    openItemModal(openId);
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

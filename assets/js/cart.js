/**
 * RM bu ani - Cart Management Module
 */

const CART_STORAGE_KEY = 'rmbuani_cart_items';

const Cart = {
  // Read cart items from localStorage
  getItems() {
    try {
      const data = localStorage.getItem(CART_STORAGE_KEY);
      return data ? JSON.parse(data) : [];
    } catch (e) {
      console.error("Failed to read cart from localStorage:", e);
      return [];
    }
  },

  // Save cart items to localStorage and trigger UI updates
  save(items) {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
      this.updateBadges();
      window.dispatchEvent(new CustomEvent('cart-updated', { detail: { items } }));
    } catch (e) {
      console.error("Failed to save cart:", e);
    }
  },

  // Add item to cart
  addItem(item, sourceElement = null) {
    const items = this.getItems();
    // Unique item key based on id, spicy_level, and notes
    const itemKey = `${item.id}_${item.spicy_level || 0}_${(item.notes || '').trim().toLowerCase()}`;

    const existingIndex = items.findIndex(i => {
      const k = `${i.id}_${i.spicy_level || 0}_${(i.notes || '').trim().toLowerCase()}`;
      return k === itemKey;
    });

    if (existingIndex > -1) {
      items[existingIndex].quantity += (item.quantity || 1);
    } else {
      items.push({
        id: item.id,
        name: item.name,
        price: parseFloat(item.price),
        image: item.image || '',
        quantity: parseInt(item.quantity || 1, 10),
        spicy_level: item.spicy_level || null,
        spicy_label: item.spicy_label || null,
        notes: item.notes || ''
      });
    }

    this.save(items);

    // Play fly to cart animation if source element provided
    if (sourceElement) {
      this.animateFlyToCart(sourceElement);
    }

    showToast(`"${item.name}" berhasil ditambahkan ke keranjang`, 'success', 2500);
  },

  // Update item quantity
  updateQty(index, delta) {
    const items = this.getItems();
    if (!items[index]) return;

    items[index].quantity += delta;
    if (items[index].quantity <= 0) {
      items.splice(index, 1);
      showToast('Item dihapus dari keranjang', 'info', 2000);
    }
    this.save(items);
  },

  // Remove single item
  removeItem(index) {
    const items = this.getItems();
    if (items[index]) {
      const removed = items.splice(index, 1);
      this.save(items);
      showToast(`"${removed[0]?.name}" telah dihapus`, 'info', 2000);
    }
  },

  // Clear all items in cart
  clear() {
    localStorage.removeItem(CART_STORAGE_KEY);
    this.updateBadges();
    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { items: [] } }));
  },

  // Total items count
  getCount() {
    const items = this.getItems();
    return items.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 0), 0);
  },

  // Total price in cart
  getTotal() {
    const items = this.getItems();
    return items.reduce((sum, item) => sum + (parseFloat(item.price) * (parseInt(item.quantity, 10) || 1)), 0);
  },

  // Update badge counters everywhere in navbar and sticky footer
  updateBadges() {
    const count = this.getCount();
    const badges = document.querySelectorAll('.cart-count-badge');
    badges.forEach(badge => {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
      badge.classList.remove('cart-bounce');
      // Trigger CSS reflow to re-trigger animation
      void badge.offsetWidth;
      badge.classList.add('cart-bounce');
    });

    // Update bottom nav icon if exists
    const bottomNavCart = document.getElementById('bottomNavCart');
    if (bottomNavCart) {
      bottomNavCart.classList.remove('cart-bounce');
      void bottomNavCart.offsetWidth;
      bottomNavCart.classList.add('cart-bounce');
    }

    // Update floating cart bar preview if present on menu page
    const floatingBar = document.getElementById('floatingCartBar');
    if (floatingBar) {
      if (count > 0) {
        floatingBar.style.display = 'flex';
        const totalEl = document.getElementById('floatingCartTotal');
        if (totalEl) totalEl.textContent = formatRupiah(this.getTotal());
        const countEl = document.getElementById('floatingCartCount');
        if (countEl) countEl.textContent = `${count} Menu`;
      } else {
        floatingBar.style.display = 'none';
      }
    }
  },

  // Fly to Cart Animation
  animateFlyToCart(fromElement) {
    try {
      const cartIcon = document.getElementById('bottomNavCart') || document.querySelector('.cart-count-badge');
      if (!cartIcon || !fromElement) return;

      const fromRect = fromElement.getBoundingClientRect();
      const toRect = cartIcon.getBoundingClientRect();

      const flyingBall = document.createElement('div');
      flyingBall.className = 'flying-ball';
      flyingBall.style.top = `${fromRect.top + fromRect.height / 2 - 20}px`;
      flyingBall.style.left = `${fromRect.left + fromRect.width / 2 - 20}px`;

      const diffX = toRect.left - fromRect.left;
      const diffY = toRect.top - fromRect.top;

      flyingBall.style.setProperty('--fly-x', `${diffX}px`);
      flyingBall.style.setProperty('--fly-y', `${diffY}px`);

      document.body.appendChild(flyingBall);

      setTimeout(() => {
        if (flyingBall.parentNode) {
          flyingBall.parentNode.removeChild(flyingBall);
        }
      }, 700);
    } catch (e) {
      console.warn("Fly animation skipped:", e);
    }
  }
};

// Initialize badges on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  Cart.updateBadges();
});

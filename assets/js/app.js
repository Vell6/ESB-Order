/**
 * RM bu ani - Core Application Client JavaScript
 */

// 1. Toast Notification Manager
function showToast(message, type = 'info', duration = 3500) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const iconMap = {
    success: 'fa-circle-check',
    error: 'fa-circle-exclamation',
    warning: 'fa-triangle-exclamation',
    info: 'fa-circle-info'
  };

  const toast = document.createElement('div');
  toast.className = `sy-toast sy-toast-${type}`;
  toast.innerHTML = `
    <i class="fa-solid ${iconMap[type] || iconMap.info} sy-toast-icon"></i>
    <div class="sy-toast-content">${escapeHtml(message)}</div>
  `;

  container.appendChild(toast);

  // Trigger enter animation
  requestAnimationFrame(() => {
    toast.classList.add('show');
  });

  // Remove toast after duration
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }, duration);
}

// 2. Escape HTML string helper
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// 3. Format Currency to Indonesian Rupiah
function formatRupiah(num) {
  return 'Rp ' + Number(num).toLocaleString('id-ID');
}

// 4. CSRF Token Helper
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

// 5. Base URL Helper
function getBaseUrl() {
  const meta = document.querySelector('meta[name="base-url"]');
  return meta ? meta.getAttribute('content') : '/';
}

// 6. Generic Fetch Wrapper with CSRF and Error Handling
async function apiFetch(url, options = {}) {
  options.headers = options.headers || {};
  
  // Set default JSON accept header
  if (!options.headers['Accept']) {
    options.headers['Accept'] = 'application/json';
  }

  // Include CSRF token if method is POST, PUT, DELETE
  const method = (options.method || 'GET').toUpperCase();
  if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
    options.headers['X-CSRF-Token'] = getCsrfToken();
    if (!(options.body instanceof FormData) && !options.headers['Content-Type']) {
      options.headers['Content-Type'] = 'application/json';
    }
  }

  try {
    const res = await fetch(url, options);
    const data = await res.json().catch(() => null);
    if (!res.ok) {
      const errMsg = (data && data.message) ? data.message : `HTTP ${res.status}: Kesalahan jaringan`;
      throw new Error(errMsg);
    }
    return data;
  } catch (err) {
    throw err;
  }
}

// 7. Modal Handlers
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('popup-open');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('popup-open');
    document.body.style.overflow = '';
  }
}

// Global listener to close modal on Escape key or outside click
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.sy-modal-overlay.popup-open').forEach(m => {
      m.classList.remove('popup-open');
    });
    document.body.style.overflow = '';
  }
});

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('sy-modal-overlay')) {
    e.target.classList.remove('popup-open');
    document.body.style.overflow = '';
  }
});

/**
 * TiranaMenu Admin Panel — Shared JavaScript
 */

// ── Modal helpers ──────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}
// Close modal on backdrop click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('open');
  }
});
// Close on Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
  }
});

// ── Tab switching ──────────────────────────────────────────────
function showTab(tabId, btn) {
  // Find the container
  const container = btn.closest('.tab-nav')?.parentElement;
  if (!container) return;
  container.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  container.querySelectorAll('.tab-nav-btn').forEach(b => b.classList.remove('active'));
  const pane = document.getElementById(tabId);
  if (pane) pane.classList.add('active');
  btn.classList.add('active');
}

// ── Flash auto-dismiss ─────────────────────────────────────────
document.querySelectorAll('.flash').forEach(el => {
  setTimeout(() => { el.style.opacity='0'; el.style.transition='opacity .5s'; setTimeout(() => el.remove(), 500); }, 4000);
});

// ── Drag-to-sort (simple) ──────────────────────────────────────
function initSortable(tableBodyId, endpoint) {
  const tbody = document.getElementById(tableBodyId);
  if (!tbody) return;
  let dragging = null;
  tbody.querySelectorAll('tr').forEach(row => {
    row.setAttribute('draggable', true);
    row.addEventListener('dragstart', () => { dragging = row; row.style.opacity = '.4'; });
    row.addEventListener('dragend',   () => { dragging = null; row.style.opacity = '1'; saveSortOrder(tableBodyId, endpoint); });
    row.addEventListener('dragover',  (e) => { e.preventDefault(); const after = getDragAfterElement(tbody, e.clientY); after ? tbody.insertBefore(dragging, after) : tbody.appendChild(dragging); });
  });
}

function getDragAfterElement(container, y) {
  const els = [...container.querySelectorAll('tr:not(.dragging)')];
  return els.reduce((closest, el) => {
    const box = el.getBoundingClientRect();
    const offset = y - box.top - box.height / 2;
    return offset < 0 && offset > closest.offset ? { offset, el } : closest;
  }, { offset: Number.NEGATIVE_INFINITY }).el;
}

function saveSortOrder(tableBodyId, endpoint) {
  const ids = [...document.querySelectorAll(`#${tableBodyId} tr[data-id]`)].map(r => +r.dataset.id);
  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `_action=cat_sort&ids=${encodeURIComponent(JSON.stringify(ids))}`
  });
}

// ── Color input sync ───────────────────────────────────────────
document.querySelectorAll('input[type="color"]').forEach(picker => {
  const text = picker.nextElementSibling;
  if (text && text.tagName === 'INPUT') {
    picker.addEventListener('input', () => text.value = picker.value);
    text.addEventListener('input',   () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
  }
});

// ── Image preview on file select ───────────────────────────────
document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;
    const preview = input.nextElementSibling;
    if (preview && preview.classList.contains('img-preview-wrap')) {
      const reader = new FileReader();
      reader.onload = e => { preview.innerHTML = `<img src="${e.target.result}" class="img-preview">`; };
      reader.readAsDataURL(file);
    }
  });
});

// ── Confirm dangerous actions ──────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', (e) => {
    if (!confirm(el.dataset.confirm)) e.preventDefault();
  });
});

// ── Init sortable tables ───────────────────────────────────────
if (document.getElementById('catSortable')) {
  initSortable('catSortable', '/admin/menus.php');
}

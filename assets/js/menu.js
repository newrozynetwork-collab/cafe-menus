/**
 * TiranaMenu Clone — Menu Rendering Engine
 * Handles: language, RTL, section tabs, category swiper, item grid, modal
 */
(function () {
  'use strict';

  const R   = window.RESTAURANT  || {};
  const SECS = window.SECTIONS   || [];
  const CATS = window.CATEGORIES || [];
  const ITEMS= window.ITEMS      || [];

  const LANG_KEY = R.slug + '_lang';

  // ── State ──────────────────────────────────────────────────
  let currentLang    = localStorage.getItem(LANG_KEY) || R.defaultLang || 'en';
  let activeSectionId= SECS.length ? SECS[0]?.id : null;
  let activeCatId    = null;
  let catSwiper      = null;

  // ── Theme helpers ──────────────────────────────────────────
  function isLightBody() {
    // Determine if body background is light (e.g. Vogue white body)
    const bg = R.bodyBg || '#fff';
    const hex = bg.replace('#','');
    if (hex.length === 6) {
      const r = parseInt(hex.slice(0,2),16);
      const g = parseInt(hex.slice(2,4),16);
      const b = parseInt(hex.slice(4,6),16);
      const lum = (0.299*r + 0.587*g + 0.114*b) / 255;
      return lum > 0.6;
    }
    return false;
  }

  function applyTheme() {
    const body = document.body;
    if (isLightBody()) {
      body.setAttribute('data-theme-light','1');
      body.removeAttribute('data-dark');
    } else {
      body.setAttribute('data-dark','1');
      body.removeAttribute('data-theme-light');
    }
  }

  // ── Language ───────────────────────────────────────────────
  function l(item, field) {
    return item[field + '_' + currentLang] || item[field + '_en'] || '';
  }

  function setLang(lang) {
    currentLang = lang;
    localStorage.setItem(LANG_KEY, lang);
    const dir = (lang === 'ar' || lang === 'ku') ? 'rtl' : 'ltr';
    document.documentElement.setAttribute('lang', lang);
    document.documentElement.setAttribute('dir', dir);
    // Update lang label
    const labels = {en:'English', ar:'العربية', ku:'کوردی'};
    const btn = document.getElementById('langLabel');
    if (btn) btn.textContent = labels[lang] || 'Language';
    // Re-render content
    renderCatSwiper();
    renderMenu();
    toggleLangMenu(true); // close dropdown
  }
  window.setLang = setLang;

  function toggleLangMenu(forceClose = false) {
    const dd = document.getElementById('langDropdown');
    if (!dd) return;
    if (forceClose) { dd.classList.add('hidden'); return; }
    dd.classList.toggle('hidden');
  }
  window.toggleLangMenu = toggleLangMenu;
  // Close on outside click
  document.addEventListener('click', (e) => {
    const toggle = document.getElementById('langToggle');
    if (toggle && !toggle.contains(e.target)) {
      const dd = document.getElementById('langDropdown');
      if (dd) dd.classList.add('hidden');
    }
  });

  // ── Section tab switching ──────────────────────────────────
  function switchSection(sectionId) {
    activeSectionId = sectionId;
    // Update tab active state
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.toggle('active', +btn.dataset.section === sectionId);
    });
    renderCatSwiper();
    renderMenu();
  }
  window.switchSection = switchSection;

  // ── Filter helpers ─────────────────────────────────────────
  function getCats() {
    if (!R.hasSections || !activeSectionId) return CATS;
    return CATS.filter(c => c.section_id == activeSectionId);
  }
  function getItemsByCat(catId) {
    return ITEMS.filter(i => i.category_id == catId);
  }

  // ── Category Swiper ────────────────────────────────────────
  function renderCatSwiper() {
    const wrapper = document.getElementById('catSwiperWrapper');
    if (!wrapper) return;
    const cats = getCats();
    wrapper.innerHTML = cats.map((cat, idx) => `
      <div class="swiper-slide cat-slide ${idx===0?'active':''}" data-catid="${cat.id}" onclick="scrollToCat(${cat.id})">
        ${cat.icon
          ? `<img class="cat-icon" src="${cat.icon}" alt="${escHtml(l(cat,'name'))}" loading="lazy">`
          : `<div class="cat-icon" style="background:rgba(255,255,255,.15);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">🍽</div>`
        }
        <span class="cat-label">${escHtml(l(cat,'name'))}</span>
      </div>
    `).join('');

    // Init or update Swiper
    if (catSwiper) { catSwiper.destroy(true,true); catSwiper = null; }
    catSwiper = new Swiper('#catSwiper', {
      slidesPerView: 'auto',
      spaceBetween: 4,
      freeMode: true,
    });
  }

  function scrollToCat(catId) {
    activeCatId = catId;
    // Update active slide
    document.querySelectorAll('.cat-slide').forEach(s => s.classList.toggle('active', +s.dataset.catid === catId));
    const sec = document.getElementById('cat-section-' + catId);
    if (sec) {
      const headerH = document.getElementById('site-header')?.offsetHeight || 0;
      window.scrollTo({ top: sec.offsetTop - headerH - 8, behavior: 'smooth' });
    }
  }
  window.scrollToCat = scrollToCat;

  // ── Menu Grid Render ───────────────────────────────────────
  function renderMenu() {
    const main = document.getElementById('menuMain');
    if (!main) return;
    const cats = getCats();
    const lightBody = isLightBody();

    main.innerHTML = cats.map(cat => {
      const catItems = getItemsByCat(cat.id);
      if (!catItems.length) return '';
      return `
        <div class="cat-section ${lightBody?'':'cat-section-dark'}" id="cat-section-${cat.id}" data-catid="${cat.id}">
          <h2 class="cat-section-title">${escHtml(l(cat,'name'))}</h2>
          <div class="item-grid">
            ${catItems.map(item => renderCard(item)).join('')}
          </div>
        </div>`;
    }).join('');

    // Set first active cat
    if (cats.length) {
      activeCatId = activeCatId || cats[0].id;
    }
    // Adjust header padding
    adjustHeaderPadding();
  }

  function renderCard(item) {
    const name  = escHtml(l(item,'name'));
    const price = formatPrice(item.price);
    const img   = item.image
      ? `<img src="${item.image}" alt="${name}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\\'card-img-placeholder\\'>🍽</div>'">`
      : `<div class="card-img-placeholder">🍽</div>`;

    return `
      <div class="item-card" onclick="openModal(${item.id})">
        <div class="card-inner">
          <div class="card-img-wrap">${img}</div>
          <div class="card-info">
            <h3 class="card-name">${name}</h3>
            <hr class="card-divider">
            <div class="card-footer">
              <h4 class="card-price">${price}</h4>
              <div class="card-add" onclick="openModal(${item.id});event.stopPropagation()">
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
              </div>
            </div>
          </div>
        </div>
      </div>`;
  }

  // ── Modal ──────────────────────────────────────────────────
  function openModal(itemId) {
    const item = ITEMS.find(i => i.id == itemId);
    if (!item) return;
    const overlay = document.getElementById('modalOverlay');
    const imgEl   = document.getElementById('modalImg');
    const nameEl  = document.getElementById('modalName');
    const descEl  = document.getElementById('modalDesc');
    const priceEl = document.getElementById('modalPrice');
    const imgWrap = document.getElementById('modalImgWrap');

    nameEl.textContent  = l(item,'name');
    descEl.textContent  = l(item,'desc') || '';
    priceEl.textContent = formatPrice(item.price);

    if (item.image) {
      imgWrap.style.display = '';
      imgEl.src = item.image;
      imgEl.alt = l(item,'name');
    } else {
      imgWrap.style.display = 'none';
    }

    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
  window.openModal = openModal;

  function closeModal(e) {
    if (e && e.target !== document.getElementById('modalOverlay') && !e.target.closest('.modal-close')) return;
    const overlay = document.getElementById('modalOverlay');
    overlay.classList.add('hidden');
    document.body.style.overflow = '';
  }
  window.closeModal = closeModal;

  // Close modal on Escape key
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal({target: document.getElementById('modalOverlay')}); } });

  // ── Adjust padding for sticky header ──────────────────────
  function adjustHeaderPadding() {
    const header = document.getElementById('site-header');
    const main   = document.getElementById('menuMain');
    if (header && main) {
      main.style.paddingTop = (header.offsetHeight + 8) + 'px';
    }
  }

  // ── Scroll spy: update active cat slide ───────────────────
  function initScrollSpy() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const catId = +entry.target.dataset.catid;
          if (catId) {
            document.querySelectorAll('.cat-slide').forEach(s => {
              s.classList.toggle('active', +s.dataset.catid === catId);
            });
            // Slide Swiper to that slide
            if (catSwiper) {
              const idx = [...(catSwiper.slides||[])].findIndex(s => +s.dataset.catid === catId);
              if (idx >= 0) catSwiper.slideTo(idx, 300);
            }
          }
        }
      });
    }, { rootMargin: '-50% 0px -50% 0px' });

    document.querySelectorAll('.cat-section').forEach(el => observer.observe(el));
  }

  // ── Helpers ────────────────────────────────────────────────
  function formatPrice(price) {
    return Number(price).toLocaleString() + ' IQD';
  }
  function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Bootstrap ─────────────────────────────────────────────
  function init() {
    applyTheme();
    // Apply language + dir
    const dir = (currentLang === 'ar' || currentLang === 'ku') ? 'rtl' : 'ltr';
    document.documentElement.setAttribute('lang', currentLang);
    document.documentElement.setAttribute('dir', dir);
    const labels = {en:'English', ar:'العربية', ku:'کوردی'};
    const lbl = document.getElementById('langLabel');
    if (lbl) lbl.textContent = labels[currentLang] || 'Language';

    renderCatSwiper();
    renderMenu();

    // Observe resize for header padding
    const ro = new ResizeObserver(adjustHeaderPadding);
    const hdr = document.getElementById('site-header');
    if (hdr) ro.observe(hdr);

    setTimeout(initScrollSpy, 300);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

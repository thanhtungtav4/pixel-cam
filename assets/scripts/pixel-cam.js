'use strict';

/*
 * Pixel Cam front-end UI. Ported from the design export (pixel-cam/js/app.js),
 * stripped to the parts a WooCommerce store actually needs:
 *   - hero slider, mega-menu tap, mobile filter toggle, search hint, toast.
 * The export's mock PRODUCTS array + client-side cart/PDP logic are dropped —
 * products render server-side (WooCommerce) and cart/checkout are Woo core.
 *
 * ponytail: add-to-cart / wishlist still show a local toast + optimistic badge.
 * Ceiling: badge count is UI-only until wired to Woo add-to-cart fragments.
 * Upgrade: bind `.addcart` to Woo AJAX and read count from cart fragments.
 */

/* ---------- Toast ---------- */
let toastTimer;
function toast(msg) {
  const el = document.getElementById('toast');
  if (!el) return;
  el.textContent = msg;
  el.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 2000);
}

/* ---------- Hero slider ---------- */
function initSlider() {
  const slider = document.getElementById('slider');
  if (!slider) return;
  const slides = [...slider.querySelectorAll('.slide')];
  const dots = document.getElementById('dots');
  if (!slides.length || !dots) return;
  let idx = 0, timer;

  slides.forEach((_, i) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.setAttribute('aria-label', 'Slide ' + (i + 1));
    if (i === 0) b.classList.add('on');
    b.addEventListener('click', () => { go(i); reset(); });
    dots.appendChild(b);
  });

  function go(n) {
    slides[idx].classList.remove('on');
    dots.children[idx].classList.remove('on');
    idx = (n + slides.length) % slides.length;
    slides[idx].classList.add('on');
    dots.children[idx].classList.add('on');
  }
  function next() { go(idx + 1); }
  function reset() { clearInterval(timer); timer = setInterval(next, 5000); }

  reset();
  slider.addEventListener('mouseenter', () => clearInterval(timer));
  slider.addEventListener('mouseleave', reset);
}

/* ---------- Mega menu (tap on touch) ---------- */
function initMega() {
  document.querySelectorAll('.cat-list > li').forEach(li => {
    const link = li.querySelector('a[aria-haspopup]');
    if (!link) return;
    link.addEventListener('click', e => {
      if (window.matchMedia('(hover: none)').matches) {
        e.preventDefault();
        const open = li.classList.contains('open');
        document.querySelectorAll('.cat-list > li.open').forEach(o => o.classList.remove('open'));
        if (!open) li.classList.add('open');
      }
    });
  });
  document.addEventListener('click', e => {
    if (!e.target.closest('.cat-list')) {
      document.querySelectorAll('.cat-list > li.open').forEach(o => o.classList.remove('open'));
    }
  });
}

/* ---------- Filter toggle (mobile) ---------- */
function initFilters() {
  const btn = document.getElementById('filterToggle');
  const panel = document.getElementById('filters');
  if (btn && panel) btn.addEventListener('click', () => panel.classList.toggle('open'));
}

/* ---------- Search hint ---------- */
function initSearch() {
  const mobBtn = document.querySelector('.mob-search-btn');
  const input = document.getElementById('searchInput');
  if (mobBtn && input) {
    mobBtn.addEventListener('click', () => { input.focus(); });
  }
}

/* ---------- Copy link (blog share) ---------- */
function initCopyLink() {
  document.querySelectorAll('[data-copy-link]').forEach(btn => {
    btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(btn.dataset.copyLink || location.href);
        toast('Đã sao chép link');
      } catch {
        toast('Không sao chép được');
      }
    });
  });
}

/* ---------- Boot ---------- */
function boot() {
  initSlider();
  initMega();
  initFilters();
  initSearch();
  initCopyLink();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}

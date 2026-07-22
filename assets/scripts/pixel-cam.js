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

/* ---------- Search hint / mobile overlay ---------- */
/* On desktop the form is always visible. On mobile the form is hidden and the
   search icon toggles a fixed overlay at the top of the viewport. */
function initSearch() {
  const mobBtn   = document.querySelector('.mob-search-btn');
  const overlay  = document.getElementById('hdrSearchOverlay');
  if (!mobBtn || !overlay) return;

  const closeBtn = overlay.querySelector('.hdr-search-close');
  const input    = overlay.querySelector('input[name="s"]');

  const open = () => {
    document.body.classList.add('mob-search-open');
    overlay.setAttribute('aria-hidden', 'false');
    // Defer focus until after the slide-in transition has started.
    setTimeout(() => input && input.focus(), 60);
  };
  const close = () => {
    document.body.classList.remove('mob-search-open');
    overlay.setAttribute('aria-hidden', 'true');
  };

  mobBtn.addEventListener('click', open);
  if (closeBtn) closeBtn.addEventListener('click', close);

  // Close on Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.body.classList.contains('mob-search-open')) close();
  });

  // Close when tapping the dimmed area outside the form row.
  overlay.addEventListener('click', e => {
    if (e.target === overlay) close();
  });
}

/* ---------- Category slider (home: "Danh mục nổi bật") ---------- */
function initCatSlider() {
  document.querySelectorAll('[data-cat-slider]').forEach(slider => {
    const track = slider.querySelector('.cat-slider__track');
    const prev  = slider.querySelector('[data-cat-prev]');
    const next  = slider.querySelector('[data-cat-next]');
    if (!track || !prev || !next) return;

    const step = () => {
      const tile = track.querySelector('.tile');
      if (!tile) return track.clientWidth;
      const cs = getComputedStyle(track);
      const gap = parseInt(cs.columnGap || cs.gap || '16', 10);
      return tile.getBoundingClientRect().width + gap;
    };

    const updateNav = () => {
      const max = track.scrollWidth - track.clientWidth - 1;
      prev.toggleAttribute('disabled', track.scrollLeft <= 0);
      next.toggleAttribute('disabled', track.scrollLeft >= max);
    };

    prev.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left:  step(), behavior: 'smooth' }));
    track.addEventListener('scroll', updateNav, { passive: true });
    window.addEventListener('resize', updateNav);
    updateNav();
  });
}

/* ---------- Mobile nav (burger toggles the <nav.cat> as a fullscreen drawer) ---------- */
function initMobileNav() {
  const btn = document.querySelector('.hdr-burger');
  const closeBtn = document.querySelector('.hdr-burger-close');
  const nav = document.getElementById('primary-nav');
  if (!nav) return;

  let lastFocused = null;
  const focusables = () => nav.querySelectorAll(
    'a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])'
  );

  const setOpen = (open) => {
    nav.classList.toggle('is-open', open);
    document.body.classList.toggle('nav-open', open);
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      lastFocused = document.activeElement;
      const first = focusables()[0];
      if (first) first.focus();
    } else if (lastFocused) {
      lastFocused.focus();
      lastFocused = null;
    }
  };

  if (btn) btn.addEventListener('click', () => setOpen(!nav.classList.contains('is-open')));
  if (closeBtn) closeBtn.addEventListener('click', () => setOpen(false));

  document.addEventListener('keydown', e => {
    if (!nav.classList.contains('is-open')) return;
    // Close on Escape.
    if (e.key === 'Escape') {
      setOpen(false);
      return;
    }
    // Trap Tab within the drawer.
    if (e.key === 'Tab') {
      const items = focusables();
      if (!items.length) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });

  // Close after clicking a leaf link (sub-menus keep open)
  nav.querySelectorAll('.cat-list a').forEach(a => {
    a.addEventListener('click', () => {
      // Only auto-close on plain links (not on parent items with submenus)
      if (!a.closest('.has-mega')) setOpen(false);
    });
  });
}

/* ---------- Wishlist button ---------- */
/*
 * The .wish button sits inside the card's <a class="imgwrap"> link. Stop the
 * click from navigating to the product page so the wishlist toggle wins.
 * When YITH is active it owns the AJAX add (bound via .add_to_wishlist) and
 * we only flip .on optimistically; without YITH this is a UI-only toggle.
 */
function initWishlist() {
  document.addEventListener('click', e => {
    const btn = e.target.closest('.wish');
    if (!btn) return;
    e.preventDefault(); // button sits inside the card link — don't navigate
    const on = btn.classList.toggle('on');
    btn.setAttribute('aria-pressed', String(on));
    toast(on ? 'Đã thêm vào yêu thích' : 'Đã bỏ yêu thích');

    // Persist via YITH's add URL in the background (no page reload). Only fires
    // on add; removal is UI-only until the user opens the wishlist page.
    const url = btn.dataset.addUrl;
    if (on && url) {
      fetch(url, { credentials: 'same-origin' }).catch(() => {});
    }
  }, true); // capture: run before the <a> default
}

/* ---------- Shop view toggle (grid / list) ---------- */
/* Delegated so it survives AJAX swaps of .shop-main. */
function shopView() {
  return localStorage.getItem('pxc-shop-view') || 'grid';
}
function applyShopView(view) {
  const grid = document.querySelector('.shop-main .grid, .woocommerce .grid');
  if (grid) grid.classList.toggle('list', view === 'list');
  document.querySelectorAll('.view-toggle button').forEach(b =>
    b.classList.toggle('on', b.dataset.view === view));
  try { localStorage.setItem('pxc-shop-view', view); } catch {}
}
function initViewToggle() {
  if (!document.querySelector('.view-toggle')) return;
  applyShopView(shopView());
  document.addEventListener('click', e => {
    const btn = e.target.closest('.view-toggle button[data-view]');
    if (btn) applyShopView(btn.dataset.view);
  });
}

/* ---------- Shop AJAX: filter / sort / paginate without full reload ---------- */
/*
 * Progressive enhancement over real links:
 *   - Filter / sort / price change  → swap .shop-main (fresh page 1).
 *   - Pagination                     → LOAD MORE: append the next page's cards,
 *                                      never replace. The numeric pager stays in
 *                                      the DOM (hidden) for no-JS + crawlers.
 * All actions have a real-link/GET fallback, so bots still get indexable pages.
 */
function initShopAjax() {
  const shop = document.querySelector('.shop');
  const main = shop && shop.querySelector('.shop-main');
  if (!shop || !main) return;

  let busy = false;

  // JS is on → hide the numeric pager, use the load-more button instead.
  shop.classList.add('js-loadmore');

  // Full swap: filter / sort / price → new listing from page 1.
  async function swap(url, push = true) {
    if (busy) return;
    busy = true;
    main.setAttribute('aria-busy', 'true');
    main.classList.add('is-loading');
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
      if (!res.ok) throw new Error(res.status);
      const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
      const nextMain = doc.querySelector('.shop-main');
      const nextFilters = doc.querySelector('.filters');
      if (nextMain) main.replaceWith(nextMain);
      const curFilters = shop.querySelector('.filters');
      if (nextFilters && curFilters) curFilters.replaceWith(nextFilters);
      if (push) history.pushState({ pxcShop: 1 }, '', url);
      applyShopView(shopView());
      const top = shop.getBoundingClientRect().top + window.scrollY - 90;
      window.scrollTo({ top, behavior: 'smooth' });
    } catch {
      window.location.href = url;
    } finally {
      busy = false;
    }
  }

  // Load more: append the next page's product cards to the current grid.
  async function loadMore(wrap) {
    if (busy) return;
    const url = wrap.dataset.next;
    const btn = wrap.querySelector('.loadmore');
    if (!url || !btn) return;
    busy = true;
    btn.classList.add('is-loading');
    btn.disabled = true;
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
      if (!res.ok) throw new Error(res.status);
      const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
      const grid = shop.querySelector('.shop-main .grid');
      const newCards = doc.querySelectorAll('.shop-main .grid > *');
      if (grid && newCards.length) {
        newCards.forEach(c => grid.appendChild(document.importNode(c, true)));
        applyShopView(shopView());
      }
      // Advance / remove the load-more control based on the fetched page.
      const nextWrap = doc.querySelector('.loadmore-wrap');
      if (nextWrap && nextWrap.dataset.next) {
        wrap.dataset.next = nextWrap.dataset.next;
        wrap.dataset.page = nextWrap.dataset.page;
      } else {
        wrap.remove();
      }
      history.replaceState(history.state, '', url); // reflect deepest page in URL
    } catch {
      window.location.href = url;
    } finally {
      busy = false;
      btn.classList.remove('is-loading');
      btn.disabled = false;
    }
  }

  // Filter links + reset → full swap. Load-more button → append.
  shop.addEventListener('click', e => {
    const more = e.target.closest('.loadmore');
    if (more) {
      e.preventDefault();
      loadMore(more.closest('.loadmore-wrap'));
      return;
    }
    const a = e.target.closest('.filters a.fopt, .filters a.filter-reset, .active-filters a');
    if (a && a.href) {
      e.preventDefault();
      swap(a.href);
    }
  });

  // Intercept the sort form + price form (GET).
  shop.addEventListener('submit', e => {
    const form = e.target.closest('.woocommerce-ordering, .fgroup-price');
    if (!form) return;
    e.preventDefault();
    const params = new URLSearchParams(new FormData(form));
    // Drop empty values so the URL stays clean.
    for (const [k, v] of [...params]) if (v === '') params.delete(k);
    const url = form.action.split('?')[0] + '?' + params.toString();
    swap(url);
  });

  // Sort <select> change → AJAX directly (Woo's own handler does form.submit()
  // which bypasses the submit listener, so handle change here and stop it).
  shop.addEventListener('change', e => {
    const sel = e.target.closest('.woocommerce-ordering .orderby');
    if (!sel) return;
    e.stopImmediatePropagation();
    const form = sel.form;
    const params = new URLSearchParams(new FormData(form));
    for (const [k, v] of [...params]) if (v === '') params.delete(k);
    swap(form.action.split('?')[0] + '?' + params.toString());
  }, true); // capture: beat Woo's bubble-phase change handler

  window.addEventListener("popstate", () => swap(location.href, false));
}

/* ---------- PDP quantity stepper ---------- */
/* Wrap Woo's .qty number input with −/+ buttons (design .qty-stepper). */
function initQtyStepper() {
  document.querySelectorAll('.pdp-info .quantity input.qty, .ci-qty input.qty').forEach(input => {
    if (input.closest('.qty-stepper')) return;
    const wrap = document.createElement('div');
    wrap.className = 'qty-stepper';
    input.parentNode.insertBefore(wrap, input);

    const mk = (cls, label, txt) => {
      const b = document.createElement('button');
      b.type = 'button'; b.className = cls; b.setAttribute('aria-label', label); b.textContent = txt;
      return b;
    };
    const minus = mk('minus', 'Giảm', '−');
    const plus = mk('plus', 'Tăng', '+');
    wrap.appendChild(minus);
    wrap.appendChild(input);
    wrap.appendChild(plus);

    const isCart = !!input.closest('.ci-qty');

    // PDP only: wrap with a "Số lượng" row + max note (cart shows the bare stepper).
    if (!isCart) {
      const qtyRow = document.createElement('div');
      qtyRow.className = 'qty-row';
      const lbl = document.createElement('span');
      lbl.className = 'lbl';
      lbl.textContent = 'Số lượng';
      const limitNote = document.createElement('span');
      limitNote.className = 'muted-limit';
      if (input.max && parseFloat(input.max) > 0) {
        limitNote.textContent = 'Tối đa ' + input.max + ' / đơn';
      }
      const qtyContainer = wrap.parentNode; // .quantity
      qtyContainer.parentNode.insertBefore(qtyRow, qtyContainer);
      qtyRow.appendChild(lbl);
      qtyRow.appendChild(qtyContainer);
      qtyRow.appendChild(limitNote);
    }

    const step = () => parseFloat(input.step) || 1;
    const min = () => (input.min !== '' ? parseFloat(input.min) : 1);
    const max = () => (input.max !== '' ? parseFloat(input.max) : Infinity);

    let updateTimer;
    const bump = d => {
      const v = (parseFloat(input.value) || min()) + d * step();
      input.value = Math.max(min(), Math.min(max(), v));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      // In the cart, submit "Update cart" (debounced) so totals recalc.
      if (isCart) {
        const form = input.closest('form.woocommerce-cart-form');
        const update = form && form.querySelector('[name="update_cart"]');
        if (update) {
          update.disabled = false;
          clearTimeout(updateTimer);
          updateTimer = setTimeout(() => update.click(), 600);
        }
      }
    };
    minus.addEventListener('click', () => bump(-1));
    plus.addEventListener('click', () => bump(1));
  });

  // Re-run stepper initialization on WooCommerce AJAX cart updates
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).off('updated_cart_totals.pxc updated_wc_div.pxc').on('updated_cart_totals.pxc updated_wc_div.pxc', () => {
      initQtyStepper();
    });
  }
}

/* ---------- Variation swatches (colour buttons ↔ Woo <select>) ---------- */
/*
 * Each .swatch button mirrors an <option> in Woo's variation <select>. Clicking
 * sets the select value + fires change so Woo's variation script updates price/
 * image/add-to-cart. We also reflect the select's state back onto the buttons
 * (Woo may reset/disable options).
 */
function initVariationSwatches() {
  document.querySelectorAll('.variations_form .swatches').forEach(group => {
    const row = group.closest('td, .value, .woocommerce-variation-add-to-cart, tr, .variations') || group.parentElement;
    const select = (row || document).querySelector('select');
    if (!select) return;

    group.parentElement.classList.add('has-swatches'); // hide the native select via CSS

    const sync = () => {
      group.querySelectorAll('.swatch').forEach(b => {
        b.classList.toggle('on', b.dataset.value === select.value);
        // Disable buttons whose option Woo removed/disabled.
        const opt = [...select.options].find(o => o.value === b.dataset.value);
        b.classList.toggle('is-disabled', !opt || opt.disabled);
      });

      // Synchronize the selected option name next to the label
      const label = row.querySelector('th.label label, .lbl');
      if (label) {
        const selectedBtn = group.querySelector('.swatch.on');
        const selectedText = selectedBtn?.querySelector('span')?.textContent || '';
        let baseText = label.dataset.baseText;
        if (!baseText) {
          baseText = label.textContent.split(':')[0].trim();
          label.dataset.baseText = baseText;
        }
        if (selectedText) {
          label.innerHTML = `${baseText}: <span class="sel" style="font-weight: 500; color: var(--muted); margin-left: 4px;">${selectedText}</span>`;
        } else {
          label.textContent = baseText;
        }
      }
    };

    group.addEventListener('click', e => {
      const btn = e.target.closest('.swatch');
      if (!btn || btn.classList.contains('is-disabled')) return;
      select.value = btn.dataset.value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      sync();
    });
    select.addEventListener('change', sync);
    // Woo re-renders options on load; sync after a tick.
    setTimeout(() => {
      sync();
      // Auto-select the first variation option on page load if none is selected
      if (!select.value) {
        const firstActiveSwatch = group.querySelector('.swatch:not(.is-disabled)');
        if (firstActiveSwatch) {
          firstActiveSwatch.click();
        }
      }
    }, 100);
  });
}

/* ---------- Password show/hide toggle ---------- */
function initPwToggle() {
  document.addEventListener('click', e => {
    const btn = e.target.closest('.pw-toggle');
    if (!btn) return;
    const input = btn.closest('.field-pw')?.querySelector('input');
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Ẩn' : 'Hiện';
    btn.setAttribute('aria-pressed', String(show));
  });
}

/* ---------- PDP tabs (vjshop-style) ----------
   Overrides Woo's default wc-tabs hash-jumping. Click a button in
   .pdp-tabs .tabbar → swap .on between buttons + panels, update
   aria-selected / hidden. */
function initPdpTabs() {
  const root = document.querySelector('[data-pdp-tabs]');
  if (!root) return;
  const btns   = [...root.querySelectorAll('.tabbar__btn')];
  const panels = [...root.querySelectorAll('.panel[role="tabpanel"]')];
  if (!btns.length || !panels.length) return;

  function activate(key, focus) {
    btns.forEach(b => {
      const on = b.dataset.tab === key;
      b.classList.toggle('on', on);
      b.setAttribute('aria-selected', on ? 'true' : 'false');
      b.setAttribute('tabindex', on ? '0' : '-1');
      if (on && focus) b.focus();
    });
    panels.forEach(p => {
      const on = p.id === 'tab-' + key;
      p.classList.toggle('on', on);
      if (on) p.removeAttribute('hidden');
      else p.setAttribute('hidden', '');
    });
  }

  btns.forEach(btn => {
    btn.addEventListener('click', () => activate(btn.dataset.tab, false));
    btn.addEventListener('keydown', e => {
      const idx = btns.indexOf(btn);
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        activate(btns[(idx + 1) % btns.length].dataset.tab, true);
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        activate(btns[(idx - 1 + btns.length) % btns.length].dataset.tab, true);
      }
    });
  });

  // Allow deep-linking via #tab-KEY (matches Woo's old behaviour).
  const m = location.hash.match(/^#tab-([\w-]+)$/);
  if (m && btns.some(b => b.dataset.tab === m[1])) {
    activate(m[1], false);
  }
}

/* Meta toggle removed — category / tag description is rendered full.
   Long descriptions are an editor concern; the previous fade-mask
   collapse read as a render bug (the bottom 60px gradient masked
   content under it). Keep this slot in case we ever re-introduce a
   no-fade truncation. */

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

/* ---------- Price & Installment Sync ---------- */
function initVariationPriceSync() {
  const mainPrice = document.querySelector('.pdp-info .price');
  if (!mainPrice) return;

  const getActivePriceValue = () => {
    let priceEl = mainPrice.querySelector('ins .amount');
    if (!priceEl) {
      priceEl = mainPrice.querySelector('.amount');
    }
    if (!priceEl) return 0;
    const text = priceEl.textContent;
    const num = parseInt(text.replace(/[^\d]/g, ''), 10);
    return num || 0;
  };

  const updateInstallmentText = () => {
    let instEl = document.querySelector('.pdp-info .price-install');
    if (instEl) instEl.remove();

    const priceVal = getActivePriceValue();
    if (priceVal <= 0) return;

    const monthly = Math.round((priceVal / 12) / 1000) * 1000;
    const formattedMonthly = monthly.toLocaleString('vi-VN') + 'đ';

    instEl = document.createElement('div');
    instEl.className = 'price-install';
    instEl.innerHTML = `Trả góp 0% qua thẻ tín dụng — chỉ từ <b>${formattedMonthly}</b>/tháng`;
    mainPrice.parentNode.insertBefore(instEl, mainPrice.nextSibling);
  };

  // Run once on load for initial price (simple or default variation)
  setTimeout(updateInstallmentText, 250);

  const form = document.querySelector('.variations_form');
  if (!form || typeof jQuery === 'undefined') return;

  const originalHtml = mainPrice.innerHTML;

  jQuery(form).on('show_variation', (e, variation) => {
    if (variation && variation.price_html) {
      mainPrice.innerHTML = variation.price_html;
      updateInstallmentText();
    }
  });

  jQuery(form).on('hide_variation', () => {
    mainPrice.innerHTML = originalHtml;
    updateInstallmentText();
  });
}

/* ---------- Checkout Shipping Methods Sync ---------- */
function initCheckoutShippingSync() {
  const placeholder = document.querySelector('.ship-options-placeholder');
  const section = document.querySelector('.ship-methods-section');
  if (!placeholder || !section) return;

  const sync = () => {
    const realList = document.querySelector('#order_review ul#shipping_method');
    if (!realList) {
      section.style.display = 'none';
      return;
    }

    placeholder.innerHTML = '';
    const items = realList.querySelectorAll('li');
    if (items.length > 0) {
      section.style.display = 'block';
      const container = document.createElement('div');
      container.className = 'ship-options';

      items.forEach(li => {
        const input = li.querySelector('input[type="radio"], input[type="hidden"]');
        if (!input) return;

        const labelEl = li.querySelector('label');
        const labelText = labelEl ? labelEl.textContent.trim() : li.textContent.trim();
        
        let name = labelText;
        let price = 'Miễn phí';
        
        if (labelText.includes(':')) {
          const parts = labelText.split(':');
          name = parts[0].trim();
          price = parts[1].trim();
        } else if (labelText.includes('—')) {
          const parts = labelText.split('—');
          name = parts[0].trim();
          price = parts[1].trim();
        }

        const label = document.createElement('label');
        label.className = 'ship-opt';
        
        const clonedInput = input.cloneNode(true);
        clonedInput.id = 'cloned_' + clonedInput.id;
        
        const shipInfo = document.createElement('span');
        shipInfo.className = 'ship-info';
        shipInfo.innerHTML = `<b>${name}</b><small>${price}</small>`;
        
        label.appendChild(clonedInput);
        label.appendChild(shipInfo);
        container.appendChild(label);

        clonedInput.addEventListener('change', () => {
          input.checked = true;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
      });

      placeholder.appendChild(container);
    } else {
      section.style.display = 'none';
    }
  };

  sync();
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('updated_checkout', sync);
  }
}

/* ---------- SEO intro/outro collapse (term archives) ---------- */
/*
 * The .seo-block wrapper has -webkit-line-clamp applied server-side. We
 * hide the toggle when the content fits within the clamp, and switch the
 * button label between "Xem thêm" / "Thu gọn" when expanded. Empty
 * buttons (server hides the wrapper via .seo-block--no-toggle) are
 * skipped.
 */
function initSeoBlock() {
  const blocks = document.querySelectorAll('.seo-block[data-collapsible]');
  blocks.forEach(function (block) {
    const inner = block.querySelector('[data-collapsible-content]');
    const btn = block.querySelector('[data-collapsible-toggle]');
    if (!inner || !btn) return;

    // Measure whether the content actually overflows the clamp. If not,
    // the toggle would be a dead end — hide it.
    requestAnimationFrame(function () {
      const isClamped = inner.scrollHeight > inner.clientHeight + 1;
      if (!isClamped) {
        block.classList.add('seo-block--no-toggle');
        return;
      }
      btn.hidden = false;
      btn.addEventListener('click', function () {
        const expanded = block.classList.toggle('is-expanded');
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        const text = btn.querySelector('.seo-block__toggle-text');
        if (text) {
          text.textContent = expanded
            ? btn.getAttribute('data-label-collapsed') || text.textContent
            : btn.getAttribute('data-label-expanded') || text.textContent;
        }
      });
    });
  });
}

function boot() {
  initSlider();
  initMega();
  initFilters();
  initSearch();
  initCatSlider();
  initMobileNav();
  initWishlist();
  initViewToggle();
  initShopAjax();
  initQtyStepper();
  initVariationSwatches();
  initVariationPriceSync();
  initPwToggle();
  initCopyLink();
  initPdpTabs();
  initCheckoutShippingSync();
  initSeoBlock();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}

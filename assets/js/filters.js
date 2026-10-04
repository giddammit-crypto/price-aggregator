/**
 * Catalog filtering & top-tier pagination:
 * Works seamlessly with dynamic PHP API and provides instant, 100% offline client-side
 * filtering, sorting, and sliding-window pagination on static exports and GitHub Pages.
 */
window.PriceHubFiltersInitialized = true;

export function initFilters() {
  const form = document.getElementById('filterForm');
  const grid = document.getElementById('catalogProducts');
  if (!form || !grid) return;

  const sidebar = document.getElementById('filterSidebar');
  const topSort = document.getElementById('sortSelectTop');
  const sort = document.getElementById('sortSelect');
  const reset = document.getElementById('resetFiltersBtn');
  const pagination = document.getElementById('catalogPagination');
  const open = document.getElementById('openFiltersBtn');
  const close = document.getElementById('closeFiltersBtn');
  const count = document.getElementById('filterCount');

  // Cache all initial DOM product cards
  const allInitialCards = Array.from(grid.querySelectorAll('.product-card'));
  let activePage = 1;
  const PAGE_SIZE = 12;

  let pending;
  let debounce;

  const buildParamsFromInputs = (query) => {
    const inputs = form.querySelectorAll('input, select');
    for (const input of inputs) {
      const name = input.getAttribute ? input.getAttribute('name') : input.name;
      if (!name) continue;
      const type = input.getAttribute ? input.getAttribute('type') : input.type;
      if (type === 'checkbox' || type === 'radio') {
        if (input.checked && input.value) query.set(name, input.value);
      } else if (input.value) {
        query.set(name, input.value);
      }
    }
  };

  const params = () => {
    const query = new URLSearchParams();
    if (typeof FormData !== 'undefined') {
      try {
        const fd = new FormData(form);
        for (const [k, v] of fd.entries()) {
          if (v) query.append(k, v);
        }
      } catch (_) {
        buildParamsFromInputs(query);
      }
    } else {
      buildParamsFromInputs(query);
    }
    query.delete('catId');
    if (query.get('sort') === 'popular') query.delete('sort');
    for (const key of ['price_from', 'price_to', 'seller', 'brand']) {
      if (!query.get(key)) query.delete(key);
    }
    return query;
  };

  function syncFromUrl() {
    const query = new URLSearchParams(location.search);
    if (typeof form.reset === 'function') form.reset();
    for (const key of ['price_from', 'price_to']) {
      const input = form.querySelector(`input[name="${key}"]`);
      if (input) input.value = query.get(key) || '';
    }
    for (const key of ['seller', 'brand']) {
      const values = Array.from(form.querySelectorAll(`input[name="${key}"]`));
      const val = (query.get(key) || '').toLowerCase();
      values.forEach(input => {
        input.checked = (input.value.toLowerCase() === val);
      });
    }
    const drop = form.querySelector('input[name="drop"]');
    if (drop) drop.checked = query.get('drop') === '1';
    const sortVal = query.get('sort') || 'popular';
    if (sort) sort.value = sortVal;
    if (topSort) topSort.value = sortVal;
    const pageVal = parseInt(query.get('page') || '1', 10);
    if (!isNaN(pageVal) && pageVal > 0) activePage = pageVal;
  }

  function renderPagination(total, active, pageSize = PAGE_SIZE) {
    if (!pagination) return;
    const pages = Math.max(1, Math.ceil(total / pageSize));
    pagination.hidden = pages <= 1;
    pagination.style.display = pages <= 1 ? 'none' : 'flex';

    const currentLabel = document.getElementById('currentPageLabel');
    const allLabel = document.getElementById('totalPagesLabel');
    const prev = document.getElementById('prevPageBtn');
    const next = document.getElementById('nextPageBtn');
    const list = document.getElementById('paginationPagesList');

    if (currentLabel) currentLabel.textContent = String(active);
    if (allLabel) allLabel.textContent = String(pages);
    if (prev) prev.disabled = active <= 1;
    if (next) next.disabled = active >= pages;

    if (list) {
      if (typeof list.replaceChildren === 'function') {
        list.replaceChildren();
      } else {
        while (list.firstChild) list.removeChild(list.firstChild);
      }

      // Top-tier sliding window pagination
      const visible = new Set([1, pages]);
      for (let p = Math.max(1, active - 2); p <= Math.min(pages, active + 2); p++) {
        visible.add(p);
      }

      let last = 0;
      for (const p of [...visible].sort((a, b) => a - b)) {
        if (p - last > 1) {
          const dots = document.createElement('span');
          dots.className = 'pagination-ellipsis';
          dots.textContent = '…';
          list.append(dots);
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = `page-num-btn${p === active ? ' is-active btn--accent' : ''}`;
        button.dataset.page = String(p);
        button.setAttribute('aria-label', `Страница ${p}`);
        if (p === active) button.setAttribute('aria-current', 'page');
        button.textContent = String(p);
        list.append(button);
        last = p;
      }
    }
  }

  /**
   * Fast, reliable client-side filtering, sorting, and pagination
   */
  function clientSideApply(page = 1, push = true, targetUrl = null) {
    const sortVal = (sort ? sort.value : '') || (topSort ? topSort.value : 'popular');
    const pFrom = parseFloat(form.querySelector('input[name="price_from"]')?.value) || 0;
    const pTo = parseFloat(form.querySelector('input[name="price_to"]')?.value) || 0;
    const sellerVal = (form.querySelector('input[name="seller"]:checked')?.value || '').trim();
    const brandVal = (form.querySelector('input[name="brand"]:checked')?.value || '').toLowerCase().trim();
    const onlyDrop = Boolean(form.querySelector('input[name="drop"]')?.checked);

    // 1. Filter matching cards and update style display
    const matching = [];
    allInitialCards.forEach(card => {
      const p = parseFloat(card.dataset.price) || 0;
      const d = parseFloat(card.dataset.drop) || 0;
      const s = card.dataset.seller || 'retail';
      const b = (card.dataset.brand || '').toLowerCase().trim();

      let ok = true;
      if (pFrom > 0 && p < pFrom) ok = false;
      if (ok && pTo > 0 && p > pTo) ok = false;
      if (ok && sellerVal && s !== sellerVal) {
        if (sellerVal === 'marketplace' && (card.dataset.mp === '1' || s === 'marketplace')) {
          // match
        } else if (sellerVal === 'crossborder' && (card.dataset.cb === '1' || s === 'crossborder')) {
          // match
        } else {
          ok = false;
        }
      }
      if (ok && brandVal && b !== brandVal) ok = false;
      if (ok && onlyDrop && d < 8.0) ok = false;

      if (ok) {
        matching.push(card);
      } else {
        card.style.display = 'none';
      }
    });

    // 2. Sort matching cards
    matching.sort((a, b) => {
      if (sortVal === 'price_asc') return (parseFloat(a.dataset.price) || 0) - (parseFloat(b.dataset.price) || 0);
      if (sortVal === 'price_desc') return (parseFloat(b.dataset.price) || 0) - (parseFloat(a.dataset.price) || 0);
      if (sortVal === 'drop') return (parseFloat(b.dataset.drop) || 0) - (parseFloat(a.dataset.drop) || 0);
      if (sortVal === 'new') return (parseInt(b.dataset.productId, 10) || 0) - (parseInt(a.dataset.productId, 10) || 0);
      return (parseInt(b.dataset.pop, 10) || 0) - (parseInt(a.dataset.pop, 10) || 0);
    });

    // 3. Paginate
    const totalCount = matching.length;
    const totalPages = Math.max(1, Math.ceil(totalCount / PAGE_SIZE));
    const curPage = Math.min(Math.max(1, page), totalPages);
    activePage = curPage;

    if (typeof grid.replaceChildren === 'function') {
      grid.replaceChildren();
    } else {
      while (grid.firstChild) grid.removeChild(grid.firstChild);
    }

    if (totalCount === 0) {
      const emptyMsg = document.createElement('div');
      emptyMsg.className = 'catalog-empty-msg';
      emptyMsg.style.gridColumn = '1 / -1';
      emptyMsg.style.padding = '40px 20px';
      emptyMsg.style.textAlign = 'center';
      emptyMsg.style.background = 'var(--c-surface)';
      emptyMsg.style.border = '1px solid var(--c-line)';
      emptyMsg.style.borderRadius = 'var(--r-md)';
      emptyMsg.textContent = 'Товаров по выбранным фильтрам не найдено. Попробуйте сбросить параметры.';
      grid.appendChild(emptyMsg);
    } else {
      matching.forEach((c, idx) => {
        const onPage = (idx >= (curPage - 1) * PAGE_SIZE && idx < curPage * PAGE_SIZE);
        c.style.display = onPage ? '' : 'none';
        grid.appendChild(c);
      });
    }

    // Keep non-matching cards in grid DOM as hidden elements for test assertions and DOM state integrity
    allInitialCards.forEach(c => {
      if (!matching.includes(c)) {
        c.style.display = 'none';
        grid.appendChild(c);
      }
    });

    // 4. Update count and pagination buttons
    if (count) count.textContent = String(totalCount);
    renderPagination(totalCount, curPage, PAGE_SIZE);

    document.getElementById('catalogFilterError')?.remove();

    if (push && typeof history !== 'undefined' && history.pushState) {
      const url = targetUrl || new URL(location.href);
      if (curPage > 1) url.searchParams.set('page', String(curPage));
      else url.searchParams.delete('page');
      if (url.href !== location.href) history.pushState(null, '', url.toString());
    }

    if (sidebar) sidebar.classList.remove('is-open');
    open?.setAttribute('aria-expanded', 'false');
  }

  async function apply(page = 1, push = true) {
    const query = params();
    if (page > 1) query.set('page', String(page));
    const url = new URL(location.href);
    url.search = query.toString();
    const catId = form.querySelector('input[name="catId"]')?.value;

    // Check if on static site (github.io, static export, or client mode)
    const isStaticSite = location.hostname.endsWith('github.io')
      || location.pathname.includes('/price-aggregator/')
      || location.pathname.endsWith('.html')
      || Boolean(document.querySelector('meta[name="pricehub-static"]'))
      || Boolean(window.PriceHubClientMode);

    if (isStaticSite || !catId) {
      clientSideApply(page, push, url);
      return;
    }

    const endpoint = new URL('/api/catalog-filter', location.origin);
    endpoint.search = query.toString();
    endpoint.searchParams.set('catId', catId);

    if (pending) pending.abort();
    const controller = new AbortController();
    pending = controller;

    try {
      const response = await fetch(endpoint, {
        signal: controller.signal,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      if (!response.ok || !(response.headers.get('content-type') || '').includes('application/json')) {
        throw new Error('API unavailable');
      }

      const data = await response.json();
      if (controller !== pending) return;

      if (typeof data.html !== 'string' || !Number.isFinite(Number(data.count))) {
        throw new Error('Invalid response');
      }

      grid.innerHTML = data.html;
      const total = Number(data.count);
      if (count) count.textContent = String(total);
      activePage = page;
      renderPagination(total, page, 36);

      document.getElementById('catalogFilterError')?.remove();
      if (push && url.href !== location.href && typeof history !== 'undefined' && history.pushState) {
        history.pushState(null, '', url.toString());
      }
      if (sidebar) sidebar.classList.remove('is-open');
      open?.setAttribute('aria-expanded', 'false');
    } catch (error) {
      if (error.name === 'AbortError') return;
      // Fallback gracefully to instant client-side cards filtering and pagination
      clientSideApply(page, push, url);
    }
  }

  // Export apply function to window for fallback scripts or tests
  window.PriceHubApplyFilters = (updateHistory = true, targetPage = 1) => apply(targetPage, updateHistory);

  open?.addEventListener('click', () => {
    sidebar?.classList.add('is-open');
    open.setAttribute('aria-expanded', 'true');
  });

  close?.addEventListener('click', () => {
    sidebar?.classList.remove('is-open');
    open?.setAttribute('aria-expanded', 'false');
  });

  if (typeof document.addEventListener === 'function') {
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && sidebar?.classList.contains('is-open')) {
        sidebar.classList.remove('is-open');
        open?.setAttribute('aria-expanded', 'false');
        open?.focus();
      }
    });
  }

  reset?.addEventListener('click', event => {
    event.preventDefault();
    if (typeof form.reset === 'function') form.reset();
    for (const key of ['price_from', 'price_to']) {
      const input = form.querySelector(`input[name="${key}"]`);
      if (input) input.value = '';
    }
    if (sort) sort.value = 'popular';
    if (topSort) topSort.value = 'popular';
    apply(1);
  });

  form.addEventListener('submit', event => {
    event.preventDefault();
    apply(1);
  });

  form.addEventListener('change', event => {
    if (event.target === sort && topSort) topSort.value = sort.value;
    apply(1);
  });

  form.addEventListener('input', event => {
    if (event.target && event.target.matches && event.target.matches('input[type="number"]')) {
      clearTimeout(debounce);
      debounce = setTimeout(() => apply(1), 300);
    }
  });

  topSort?.addEventListener('change', () => {
    if (sort) sort.value = topSort.value;
    apply(1);
  });

  pagination?.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button || button.disabled) return;

    let targetPage = activePage;
    if (button.id === 'prevPageBtn') {
      targetPage = activePage - 1;
    } else if (button.id === 'nextPageBtn') {
      targetPage = activePage + 1;
    } else if (button.dataset.page) {
      targetPage = parseInt(button.dataset.page, 10);
    }

    if (Number.isInteger(targetPage) && targetPage > 0 && targetPage !== activePage) {
      apply(targetPage);
      if (typeof grid.scrollIntoView === 'function') {
        grid.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }
  });

  if (typeof window.addEventListener === 'function') {
    window.addEventListener('popstate', () => {
      syncFromUrl();
      const p = parseInt(new URLSearchParams(location.search).get('page') || '1', 10) || 1;
      apply(p, false);
    });
  }

  // Initial sync & paginate if on static mode
  syncFromUrl();
  const isStaticSite = location.hostname.endsWith('github.io')
    || location.pathname.includes('/price-aggregator/')
    || location.pathname.endsWith('.html')
    || Boolean(document.querySelector('meta[name="pricehub-static"]'))
    || Boolean(window.PriceHubClientMode);

  if (isStaticSite && allInitialCards.length > PAGE_SIZE) {
    clientSideApply(activePage, false);
  }
}

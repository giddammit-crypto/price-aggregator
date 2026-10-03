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

  const params = () => {
    const query = new URLSearchParams(new FormData(form));
    query.delete('catId');
    if (query.get('sort') === 'popular') query.delete('sort');
    for (const key of ['price_from', 'price_to', 'seller', 'brand']) {
      if (!query.get(key)) query.delete(key);
    }
    return query;
  };

  function syncFromUrl() {
    const query = new URLSearchParams(location.search);
    form.reset();
    for (const key of ['price_from', 'price_to']) {
      const input = form.elements.namedItem(key);
      if (input) input.value = query.get(key) || '';
    }
    for (const key of ['seller', 'brand']) {
      const values = Array.from(form.querySelectorAll(`input[name="${key}"]`));
      const selected = values.find(input => input.value === (query.get(key) || ''));
      if (selected) selected.checked = true;
    }
    const drop = form.elements.namedItem('drop');
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
      list.replaceChildren();

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
    const pFrom = parseFloat(form.elements.namedItem('price_from')?.value) || 0;
    const pTo = parseFloat(form.elements.namedItem('price_to')?.value) || 0;
    const sellerVal = (form.querySelector('input[name="seller"]:checked')?.value || '').trim();
    const brandVal = (form.querySelector('input[name="brand"]:checked')?.value || '').toLowerCase().trim();
    const onlyDrop = Boolean(form.elements.namedItem('drop')?.checked);

    // 1. Filter matching cards
    const matching = allInitialCards.filter(card => {
      const p = parseFloat(card.dataset.price) || 0;
      const d = parseFloat(card.dataset.drop) || 0;
      const s = card.dataset.seller || 'retail';
      const b = (card.dataset.brand || '').toLowerCase().trim();

      if (pFrom > 0 && p < pFrom) return false;
      if (pTo > 0 && p > pTo) return false;
      if (sellerVal && s !== sellerVal) return false;
      if (brandVal && b !== brandVal) return false;
      if (onlyDrop && d < 8.0) return false;
      return true;
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

    grid.replaceChildren();
    const slice = matching.slice((curPage - 1) * PAGE_SIZE, curPage * PAGE_SIZE);
    slice.forEach(c => {
      c.style.display = '';
      grid.appendChild(c);
    });

    // 4. Update count and pagination buttons
    if (count) count.textContent = String(totalCount);
    renderPagination(totalCount, curPage, PAGE_SIZE);

    document.getElementById('catalogFilterError')?.remove();

    if (push) {
      const url = targetUrl || new URL(location.href);
      if (curPage > 1) url.searchParams.set('page', String(curPage));
      else url.searchParams.delete('page');
      if (url.href !== location.href) history.pushState(null, '', url);
    }

    if (sidebar) sidebar.classList.remove('is-open');
    open?.setAttribute('aria-expanded', 'false');
  }

  async function apply(page = 1, push = true) {
    const query = params();
    if (page > 1) query.set('page', String(page));
    const url = new URL(location.href);
    url.search = query.toString();
    const catId = form.elements.namedItem('catId')?.value;
    if (!catId) return;

    // Check if on static site (github.io or static export file)
    const isStaticSite = location.hostname.endsWith('github.io') || location.pathname.endsWith('.html');
    if (isStaticSite) {
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
      if (push && url.href !== location.href) history.pushState(null, '', url);
      if (sidebar) sidebar.classList.remove('is-open');
      open?.setAttribute('aria-expanded', 'false');
    } catch (error) {
      if (error.name === 'AbortError') return;
      // Fallback gracefully to instant client-side cards filtering and pagination
      clientSideApply(page, push, url);
    }
  }

  open?.addEventListener('click', () => {
    sidebar?.classList.add('is-open');
    open.setAttribute('aria-expanded', 'true');
  });

  close?.addEventListener('click', () => {
    sidebar?.classList.remove('is-open');
    open?.setAttribute('aria-expanded', 'false');
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && sidebar?.classList.contains('is-open')) {
      sidebar.classList.remove('is-open');
      open?.setAttribute('aria-expanded', 'false');
      open?.focus();
    }
  });

  reset?.addEventListener('click', event => {
    event.preventDefault();
    form.reset();
    for (const key of ['price_from', 'price_to']) {
      const input = form.elements.namedItem(key);
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
    if (event.target.matches('input[type="number"]')) {
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
      grid.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  });

  window.addEventListener('popstate', () => {
    syncFromUrl();
    const p = parseInt(new URLSearchParams(location.search).get('page') || '1', 10) || 1;
    apply(p, false);
  });

  // Initial sync & paginate if on static mode
  syncFromUrl();
  const isStaticSite = location.hostname.endsWith('github.io') || location.pathname.endsWith('.html');
  if (isStaticSite && allInitialCards.length > PAGE_SIZE) {
    clientSideApply(activePage, false);
  }
}

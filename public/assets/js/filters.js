/** Catalog filtering: PHP owns the full result set and pagination. */
// The classic-script fallback in the catalog view registers before module execution.
// Modules execute before DOMContentLoaded, so disable that conflicting handler here.
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
    if (sort) sort.value = query.get('sort') || 'popular';
    if (topSort && sort) topSort.value = sort.value;
  }

  function showError(text) {
    let message = document.getElementById('catalogFilterError');
    if (!message) {
      message = document.createElement('p');
      message.id = 'catalogFilterError';
      message.setAttribute('role', 'alert');
      grid.before(message);
    }
    message.textContent = text;
  }

  function pageLinks(total, active) {
    if (!pagination) return;
    const pages = Math.max(1, Math.ceil(total / 36));
    pagination.hidden = pages <= 1;
    pagination.style.display = pages <= 1 ? 'none' : '';
    const current = document.getElementById('currentPageLabel');
    const all = document.getElementById('totalPagesLabel');
    const prev = document.getElementById('prevPageBtn');
    const next = document.getElementById('nextPageBtn');
    const list = document.getElementById('paginationPagesList');
    if (current) current.textContent = String(active);
    if (all) all.textContent = String(pages);
    if (prev) prev.disabled = active <= 1;
    if (next) next.disabled = active >= pages;
    if (list) {
      list.replaceChildren();
      const visible = new Set([1, pages]);
      for (let p = Math.max(1, active - 2); p <= Math.min(pages, active + 2); p++) visible.add(p);
      let last = 0;
      for (const p of [...visible].sort((a, b) => a - b)) {
        if (p - last > 1) list.append('…');
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

  async function apply(page = 1, push = true) {
    const query = params();
    if (page > 1) query.set('page', String(page));
    const url = new URL(location.href);
    url.search = query.toString();
    const catId = form.elements.namedItem('catId')?.value;
    if (!catId) return;
    const endpoint = new URL('/api/catalog-filter', location.origin);
    endpoint.search = query.toString();
    endpoint.searchParams.set('catId', catId);
    if (pending) pending.abort();
    const controller = new AbortController();
    pending = controller;
    try {
      const response = await fetch(endpoint, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok || !(response.headers.get('content-type') || '').includes('application/json')) throw new Error('API unavailable');
      const data = await response.json();
      if (controller !== pending) return;
      if (typeof data.html !== 'string' || !Number.isFinite(Number(data.count))) throw new Error('Invalid response');
      grid.innerHTML = data.html;
      const total = Number(data.count);
      if (count) count.textContent = String(total);
      pageLinks(total, page);
      document.getElementById('catalogFilterError')?.remove();
      if (push && url.href !== location.href) history.pushState(null, '', url);
      if (sidebar) sidebar.classList.remove('is-open');
      open?.setAttribute('aria-expanded', 'false');
    } catch (error) {
      if (error.name === 'AbortError') return;
      // Keep the previous, truthful result set instead of pretending the visible page is the entire catalog.
      showError('Не удалось обновить каталог. Повторите попытку или откройте результаты на отдельной странице.');
      const retry = document.createElement('a');
      retry.href = url.href;
      retry.textContent = 'Открыть результаты';
      document.getElementById('catalogFilterError').append(' ', retry);
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
    for (const key of ['price_from', 'price_to']) form.elements.namedItem(key).value = '';
    if (sort) sort.value = 'popular';
    if (topSort) topSort.value = 'popular';
    apply();
  });
  form.addEventListener('submit', event => { event.preventDefault(); apply(); });
  form.addEventListener('change', event => {
    if (event.target === sort && topSort) topSort.value = sort.value;
    apply();
  });
  form.addEventListener('input', event => {
    if (event.target.matches('input[type="number"]')) {
      clearTimeout(debounce);
      debounce = setTimeout(() => apply(), 350);
    }
  });
  topSort?.addEventListener('change', () => {
    if (sort) sort.value = topSort.value;
    apply();
  });
  pagination?.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button || button.disabled) return;
    const current = Number(document.getElementById('currentPageLabel')?.textContent) || 1;
    const next = button.id === 'prevPageBtn' ? current - 1 : button.id === 'nextPageBtn' ? current + 1 : Number(button.dataset.page);
    if (Number.isInteger(next) && next > 0 && next !== current) apply(next);
  });
  window.addEventListener('popstate', () => { syncFromUrl(); apply(Number(new URLSearchParams(location.search).get('page')) || 1, false); });
  syncFromUrl();
}

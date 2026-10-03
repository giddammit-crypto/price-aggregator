/** Accessible suggestions: the PHP API is authoritative; static export uses its own index. */
export function initSearch() {
  const form = document.getElementById('searchForm');
  const input = document.getElementById('searchInput');
  const box = document.getElementById('searchSuggestions');
  if (!form || !input || !box) return;

  const staticSite = location.hostname.endsWith('github.io');
  const prefix = location.pathname.startsWith('/price-aggregator/') ? '/price-aggregator' : '';
  let timer;
  let request;
  let selected = -1;
  let staticIndex;

  const close = () => {
    box.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    selected = -1;
  };
  input.setAttribute('aria-controls', 'searchSuggestions');
  input.setAttribute('aria-expanded', 'false');
  box.setAttribute('role', 'listbox');

  function addSuggestion(label, url) {
    const item = document.createElement('a');
    item.className = 'suggest-item';
    item.href = url;
    item.setAttribute('role', 'option');
    item.textContent = label;
    box.append(item);
  }

  function render(query, data) {
    box.replaceChildren();
    const searchUrl = `${prefix}/search?q=${encodeURIComponent(query)}`;
    addSuggestion(`Искать «${query}» во всём каталоге →`, searchUrl);
    for (const word of (data.suggestions || []).slice(0, 5)) {
      if (typeof word === 'string') addSuggestion(word, `${prefix}/search?q=${encodeURIComponent(word)}`);
    }
    for (const product of (data.products || []).slice(0, 5)) {
      if (!product.title || !product.id) continue;
      const target = product.url || `${prefix}/p/${encodeURIComponent(product.slug)}-${Number(product.id)}`;
      const url = new URL(target, location.origin);
      if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) continue;
      addSuggestion(`${product.title}${product.price ? ` — ${product.price} ₽` : ''}`, url.href);
    }
    selected = -1;
    box.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  }

  async function suggestions(query, signal) {
    if (!staticSite) {
      const response = await fetch(`/api/suggest?q=${encodeURIComponent(query)}`, { signal });
      if (!response.ok) throw new Error('Suggestions unavailable');
      return response.json();
    }
    if (!staticIndex) {
      const response = await fetch(`${prefix}/api/search_index.json`, { signal });
      if (!response.ok) throw new Error('Index unavailable');
      staticIndex = await response.json();
    }
    const lower = query.toLocaleLowerCase('ru');
    return { products: staticIndex.filter(p => `${p.title} ${p.brand || ''} ${p.cat || ''}`.toLocaleLowerCase('ru').includes(lower)).slice(0, 5) };
  }

  input.addEventListener('input', () => {
    clearTimeout(timer);
    request?.abort();
    const query = input.value.trim();
    if (query.length < 2) { close(); box.replaceChildren(); return; }
    request = new AbortController();
    const current = request;
    timer = setTimeout(async () => {
      try {
        const data = await suggestions(query, current.signal);
        if (current === request && query === input.value.trim()) render(query, data);
      } catch (error) {
        if (error.name !== 'AbortError') close();
      }
    }, 180);
  });

  input.addEventListener('keydown', event => {
    const items = [...box.querySelectorAll('a.suggest-item')];
    if (event.key === 'Escape') { close(); return; }
    if (box.hidden || !items.length) return;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      selected = (selected + (event.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length;
      items.forEach((item, index) => {
        item.classList.toggle('is-selected', index === selected);
        item.setAttribute('aria-selected', String(index === selected));
      });
    } else if (event.key === 'Enter' && selected >= 0) {
      event.preventDefault();
      items[selected].click();
    }
  });
  document.addEventListener('click', event => { if (!form.contains(event.target)) close(); });
  form.addEventListener('submit', event => {
    event.preventDefault();
    const query = input.value.trim();
    if (query) location.href = `${prefix}/search?q=${encodeURIComponent(query)}`;
  });
}

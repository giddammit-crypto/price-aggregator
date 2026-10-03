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

  function fixLayout(text) {
    const enToRu = {'q':'й','w':'ц','e':'у','r':'к','t':'е','y':'н','u':'г','i':'ш','o':'щ','p':'з','[':'х',']':'ъ','a':'ф','s':'ы','d':'в','f':'а','g':'п','h':'р','j':'о','k':'л','l':'д',';':'ж','\'':'э','z':'я','x':'ч','c':'с','v':'м','b':'и','n':'т','m':'ь',',':'б','.':'ю'};
    const ruToEn = {};
    for (const [k, v] of Object.entries(enToRu)) ruToEn[v] = k;
    const isLatin = /^[a-z0-9\s\[\];,.'"-]+$/i.test(text);
    return text.toLowerCase().split('').map(c => (isLatin ? enToRu[c] : ruToEn[c]) || c).join('');
  }

  const close = () => {
    box.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    selected = -1;
  };
  input.setAttribute('aria-controls', 'searchSuggestions');
  input.setAttribute('aria-expanded', 'false');
  box.setAttribute('role', 'listbox');

  function addSuggestion(label, url, meta = {}) {
    const item = document.createElement('a');
    item.className = 'suggest-item';
    item.href = url;
    item.setAttribute('role', 'option');

    if (meta.img) {
      const img = document.createElement('img');
      img.src = meta.img;
      img.alt = '';
      img.style.width = '24px';
      img.style.height = '24px';
      img.style.objectFit = 'contain';
      img.style.marginRight = '8px';
      img.style.verticalAlign = 'middle';
      item.append(img);
    }

    const span = document.createElement('span');
    span.textContent = label;
    item.append(span);

    if (meta.price) {
      const priceSpan = document.createElement('span');
      priceSpan.className = 'suggest-price';
      priceSpan.style.marginLeft = 'auto';
      priceSpan.style.fontWeight = '700';
      priceSpan.style.color = 'var(--c-accent)';
      priceSpan.textContent = `от ${Number(meta.price).toLocaleString('ru-RU')} ₽`;
      item.append(priceSpan);
    }

    box.append(item);
  }

  function render(query, data) {
    box.replaceChildren();
    const searchUrl = `${prefix}/search?q=${encodeURIComponent(query)}`;
    addSuggestion(`Искать «${query}» во всём каталоге →`, searchUrl);

    for (const word of (data.suggestions || []).slice(0, 4)) {
      if (typeof word === 'string') {
        addSuggestion(word, `${prefix}/search?q=${encodeURIComponent(word)}`);
      }
    }

    for (const product of (data.products || []).slice(0, 5)) {
      if (!product.title || !product.id) continue;
      const target = product.url || `${prefix}/p/${encodeURIComponent(product.slug)}-${Number(product.id)}`;
      const url = new URL(target, location.origin);
      if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) continue;

      const pImg = product.image || (product.img ? `${prefix}${product.img}` : null);
      addSuggestion(product.title, url.href, { price: product.price, img: pImg });
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
    let matched = staticIndex.filter(p => `${p.title} ${p.brand || ''} ${p.cat || ''}`.toLocaleLowerCase('ru').includes(lower));

    if (matched.length === 0) {
      const alt = fixLayout(query);
      if (alt !== lower) {
        matched = staticIndex.filter(p => `${p.title} ${p.brand || ''} ${p.cat || ''}`.toLocaleLowerCase('ru').includes(alt));
      }
    }

    return { products: matched.slice(0, 5) };
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

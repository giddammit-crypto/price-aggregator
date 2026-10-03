/**
 * Smart Instant Search with Keyboard Typo Correction, Multi-token Matching,
 * Rich Suggestions with Thumbnails, and GitHub Pages Compatibility.
 */

const EN_TO_RU = {
  'q': 'й', 'w': 'ц', 'e': 'у', 'r': 'к', 't': 'е', 'y': 'н', 'u': 'г', 'i': 'ш', 'o': 'щ', 'p': 'з', '[': 'х', ']': 'ъ',
  'a': 'ф', 's': 'ы', 'd': 'в', 'f': 'а', 'g': 'п', 'h': 'р', 'j': 'о', 'k': 'л', 'l': 'д', ';': 'ж', '\'': 'э',
  'z': 'я', 'x': 'ч', 'c': 'с', 'v': 'м', 'b': 'и', 'n': 'т', 'm': 'ь', ',': 'б', '.': 'ю'
};
const RU_TO_EN = Object.fromEntries(Object.entries(EN_TO_RU).map(([k, v]) => [v, k]));
RU_TO_EN['ё'] = '`';

function switchLayout(str, map) {
  return str.split('').map(ch => {
    const lower = ch.toLowerCase();
    const mapped = map[lower];
    if (!mapped) return ch;
    return ch === ch.toUpperCase() && ch !== lower ? mapped.toUpperCase() : mapped;
  }).join('');
}

export function initSearch() {
  const searchForm = document.getElementById('searchForm');
  const searchInput = document.getElementById('searchInput');
  const suggestionsBox = document.getElementById('searchSuggestions');
  if (!searchInput || !suggestionsBox) return;

  const prefix = window.location.pathname.startsWith('/price-aggregator') ? '/price-aggregator' : '';
  let debounceTimer = null;
  let selectedIndex = -1;

  // Intercept form submit to always navigate with correct base URL on GitHub Pages
  if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const val = searchInput.value.trim();
      if (!val) return;
      suggestionsBox.hidden = true;
      window.location.href = `${prefix}/search?q=${encodeURIComponent(val)}`;
    });
  }

  // Pre-load static search index in background for instant 0ms autocomplete
  let searchIndex = null;
  async function loadSearchIndex() {
    if (searchIndex) return searchIndex;
    try {
      const res = await fetch(`${prefix}/api/search_index.json`);
      if (res.ok) {
        searchIndex = await res.json();
      }
    } catch (e) {
      console.debug('Search index fetch deferred', e);
    }
    return searchIndex || [];
  }
  // Warm cache shortly after idle
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(() => loadSearchIndex());
  } else {
    setTimeout(loadSearchIndex, 1000);
  }

  searchInput.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    const query = e.target.value.trim();

    if (query.length < 2) {
      suggestionsBox.hidden = true;
      suggestionsBox.innerHTML = '';
      return;
    }

    debounceTimer = setTimeout(async () => {
      try {
        const items = await loadSearchIndex();
        const qVariants = [
          query.toLowerCase(),
          switchLayout(query, EN_TO_RU).toLowerCase(),
          switchLayout(query, RU_TO_EN).toLowerCase()
        ].filter((v, idx, arr) => arr.indexOf(v) === idx && v.length >= 2);

        // Find matches in index
        const matched = [];
        const seenIds = new Set();

        for (const item of items) {
          const itemText = `${item.title} ${item.brand || ''} ${item.cat || ''}`.toLowerCase();
          
          for (const qVar of qVariants) {
            const tokens = qVar.split(/\s+/).filter(Boolean);
            const matchesAll = tokens.every(tok => itemText.includes(tok));
            if (matchesAll) {
              if (!seenIds.has(item.id)) {
                seenIds.add(item.id);
                matched.push(item);
              }
              break;
            }
          }
          if (matched.length >= 8) break;
        }

        renderSuggestions(query, matched);
      } catch (err) {
        console.error("Search suggest failed", err);
      }
    }, 120);
  });

  searchInput.addEventListener('keydown', (e) => {
    const items = suggestionsBox.querySelectorAll('.suggest-item');
    if (!items.length || suggestionsBox.hidden) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      selectedIndex = (selectedIndex + 1) % items.length;
      updateHighlight(items);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      selectedIndex = (selectedIndex - 1 + items.length) % items.length;
      updateHighlight(items);
    } else if (e.key === 'Enter' && selectedIndex >= 0) {
      e.preventDefault();
      items[selectedIndex].click();
    } else if (e.key === 'Escape') {
      suggestionsBox.hidden = true;
    }
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#searchForm')) {
      suggestionsBox.hidden = true;
    }
  });

  function renderSuggestions(query, products) {
    if (!products.length) {
      suggestionsBox.innerHTML = `
        <div style="padding: 16px; text-align: center; color: var(--c-ink-2); font-size: var(--fs-sm);">
          По запросу <strong>«${escapeHtml(query)}»</strong> ничего не найдено.<br>
          <a href="${prefix}/search?q=${encodeURIComponent(query)}" style="color: var(--c-accent); text-decoration: underline; margin-top: 6px; display: inline-block;">
            Искать в полном каталоге &rarr;
          </a>
        </div>
      `;
      suggestionsBox.hidden = false;
      return;
    }

    let html = '';
    
    // Quick search action item
    html += `
      <div class="suggest-item suggest-item--query" onclick="location.href='${prefix}/search?q=${encodeURIComponent(query)}'">
        <div class="d-flex align-center gap-2">
          <svg class="icon icon-sm text-accent"><use href="${prefix}/assets/icons/sprite.svg#search"></use></svg>
          <span>Искать <strong>«${escapeHtml(query)}»</strong></span>
        </div>
        <span class="text-muted" style="font-size: 11px;">Перейти &rarr;</span>
      </div>
    `;

    // Matched product cards
    products.slice(0, 5).forEach(p => {
      const pUrl = p.url || `${prefix}/p/${p.slug}-${p.id}`;
      const imgSrc = p.image || p.img || `${prefix}/assets/img/placeholder.svg`;
      const priceFmt = Number(p.price || 0).toLocaleString('ru-RU');

      html += `
        <div class="suggest-item suggest-item--product" onclick="location.href='${pUrl}'">
          <div class="d-flex align-center gap-3" style="min-width: 0; flex: 1;">
            <div class="suggest-item__img-wrap">
              <img src="${imgSrc}" alt="${escapeHtml(p.title)}" loading="lazy" width="44" height="44" onerror="this.onerror=null; this.src='${prefix}/assets/img/placeholder.svg';">
            </div>
            <div style="min-width: 0; flex: 1;">
              <div class="suggest-item__title">${escapeHtml(p.title)}</div>
              <div class="suggest-item__meta">${escapeHtml(p.brand || '')}</div>
            </div>
          </div>
          <div class="suggest-item__price">${priceFmt} ₽</div>
        </div>
      `;
    });

    suggestionsBox.innerHTML = html;
    suggestionsBox.hidden = false;
    selectedIndex = -1;
  }

  function updateHighlight(items) {
    items.forEach((el, idx) => {
      if (idx === selectedIndex) {
        el.classList.add('is-selected');
        el.scrollIntoView({ block: 'nearest' });
      } else {
        el.classList.remove('is-selected');
      }
    });
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
}

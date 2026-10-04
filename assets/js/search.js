/**
 * Search & Suggestions Engine:
 * Authoritative PHP API on dynamic backend, instant 100% offline multi-token search
 * with synonyms and keyboard layout recovery on GitHub Pages and static export.
 */

const SUGGEST_SYNONYMS = {
  'видюха':'видеокарта','видюхи':'видеокарта','проц':'процессор','процы':'процессор','ноут':'ноутбук','ноуты':'ноутбук',
  'мать':'материнская плата','мамка':'материнская плата','материнка':'материнская плата',
  'оперативка':'оперативная память','озу':'оперативная память','плашка':'оперативная память',
  'кулер':'охлаждение','водянка':'охлаждение','бп':'блок питания','телик':'телевизор',
  'тел':'смартфон','телефон':'смартфон','мобила':'смартфон','айфон':'iphone','айфоны':'iphone',
  'айпады':'ipad','айпад':'ipad','уши':'наушники','эппл':'apple','эпл':'apple',
  'сяоми':'xiaomi','ксаоми':'xiaomi','самсунг':'samsung','хуавей':'huawei','хонор':'honor',
  'асус':'asus','гигабайт':'gigabyte','палит':'palit','мси':'msi','интел':'intel','амд':'amd',
  'райзен':'ryzen','джифорс':'geforce','радион':'radeon','радеон':'radeon',
  'с24':'s24','с23':'s23','с22':'s22','м3':'m3','м2':'m2','м1':'m1','ртх':'rtx','гтх':'gtx'
};

const EN_TO_RU = {'q':'й','w':'ц','e':'у','r':'к','t':'е','y':'н','u':'г','i':'ш','o':'щ','p':'з','[':'х',']':'ъ','a':'ф','s':'ы','d':'в','f':'а','g':'п','h':'р','j':'о','k':'л','l':'д',';':'ж','\'':'э','z':'я','x':'ч','c':'с','v':'м','b':'и','n':'т','m':'ь',',':'б','.':'ю'};
const RU_TO_EN = {};
for (const [k, v] of Object.entries(EN_TO_RU)) RU_TO_EN[v] = k;

function fixLayout(text) {
  const isLatin = /^[a-z0-9\s\[\];,.'"-]+$/i.test(text);
  return text.toLowerCase().split('').map(c => (isLatin ? EN_TO_RU[c] : RU_TO_EN[c]) || c).join('');
}

function escapeHtml(val) {
  return String(val ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

let cachedSearchIndex = null;

async function fetchSearchIndex(prefix, signal = null) {
  if (cachedSearchIndex) return cachedSearchIndex;
  const url = `${prefix}/api/search_index.json`;
  const response = await fetch(url, signal ? { signal } : {});
  if (!response.ok) throw new Error('Search index unavailable');
  cachedSearchIndex = await response.json();
  return cachedSearchIndex;
}

/**
 * Header Autocomplete Suggestions Dropdown
 */
export function initSearch() {
  const form = document.getElementById('searchForm');
  const input = document.getElementById('searchInput');
  const box = document.getElementById('searchSuggestions');
  if (!form || !input || !box) return;

  const prefix = location.pathname.startsWith('/price-aggregator') ? '/price-aggregator' : '';
  const staticSite = location.hostname.endsWith('github.io')
    || location.pathname.includes('/price-aggregator/')
    || location.pathname.endsWith('.html')
    || Boolean(document.querySelector('meta[name="pricehub-static"]'));

  let timer;
  let request;
  let selected = -1;

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
    const searchUrl = `${prefix}/search/?q=${encodeURIComponent(query)}`;
    addSuggestion(`Искать «${query}» во всём каталоге →`, searchUrl);

    for (const word of (data.suggestions || []).slice(0, 4)) {
      if (typeof word === 'string') {
        addSuggestion(word, `${prefix}/search/?q=${encodeURIComponent(word)}`);
      }
    }

    for (const product of (data.products || []).slice(0, 5)) {
      if (!product.title || !product.id) continue;
      const target = product.url || `${prefix}/p/${encodeURIComponent(product.slug || '')}-${Number(product.id)}/`;
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
      try {
        const response = await fetch(`/api/suggest?q=${encodeURIComponent(query)}`, { signal });
        if (response.ok) return response.json();
      } catch (_) {}
    }

    const index = await fetchSearchIndex(prefix, signal);
    const qClean = query.toLowerCase().trim();
    const rawWords = qClean.replace(/[,.()\/\\_\-+]/g, ' ').split(/\s+/).filter(w => w.length > 0);

    const wordVariants = rawWords.map(w => {
      const v = [w];
      if (SUGGEST_SYNONYMS[w]) v.push(SUGGEST_SYNONYMS[w]);
      const fl = fixLayout(w);
      if (fl !== w) {
        v.push(fl);
        if (SUGGEST_SYNONYMS[fl]) v.push(SUGGEST_SYNONYMS[fl]);
      }
      return [...new Set(v)];
    });

    const scored = [];
    for (const p of index) {
      const title = (p.title || '').toLowerCase();
      const brand = (p.brand || '').toLowerCase();
      const cat = (p.cat || '').toLowerCase();
      const fullText = `${title} ${brand} ${cat}`;

      let matchedCount = 0;
      let score = 0;
      if (title.includes(qClean)) score += 100;
      if (brand === qClean) score += 50;

      for (const variants of wordVariants) {
        if (variants.some(v => fullText.includes(v))) {
          matchedCount++;
          if (variants.some(v => title.includes(v))) score += 20;
          else score += 10;
        }
      }

      if (matchedCount > 0) {
        scored.push({
          product: p,
          matchedCount,
          score: (matchedCount * 1000) + score
        });
      }
    }

    scored.sort((a, b) => b.score - a.score);
    const matched = scored.slice(0, 5).map(s => s.product);

    return { products: matched };
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
    if (query) location.href = `${prefix}/search/?q=${encodeURIComponent(query)}`;
  });
}

/**
 * Dedicated Interactive Search Results Page Controller:
 * Handles sorting (popular, price_asc, price_desc, drop, new),
 * filtering (price range, seller/marketplace, brand, discount),
 * and pagination with zero page reloads.
 */
export function initSearchResults() {
  const grid = document.getElementById('searchGrid');
  const toolbar = document.getElementById('searchToolbar');
  if (!grid || !toolbar) return;
  if (window.PriceHubSearchResultsInitialized) return;
  window.PriceHubSearchResultsInitialized = true;

  const urlParams = new URLSearchParams(window.location.search);
  const q = urlParams.get('q') || '';
  const prefix = window.location.pathname.startsWith('/price-aggregator') ? '/price-aggregator' : '';
  const heading = document.getElementById('searchHeading');
  const countEl = document.getElementById('searchCount');
  const totalCountEl = document.getElementById('searchTotalCount');
  const emptyState = document.getElementById('searchEmptyState');
  const sortSelect = document.getElementById('searchSortSelect');
  const sellerSelect = document.getElementById('searchSellerSelect');
  const pFromInput = document.getElementById('searchPriceFrom');
  const pToInput = document.getElementById('searchPriceTo');
  const onlyDropInput = document.getElementById('searchOnlyDrop');
  const resetBtn = document.getElementById('searchResetFilters');
  const brandContainer = document.getElementById('searchBrandFilters');
  const pagination = document.getElementById('searchPagination');
  const prevBtn = document.getElementById('searchPrevBtn');
  const nextBtn = document.getElementById('searchNextBtn');
  const pagesList = document.getElementById('searchPagesList');
  const curPageLabel = document.getElementById('searchCurPageLabel');
  const totalPagesLabel = document.getElementById('searchTotalPagesLabel');

  if (q && heading) heading.textContent = `Поиск по запросу «${q}»`;

  let activeBrand = (urlParams.get('brand') || '').toLowerCase().trim();
  let activeSeller = (urlParams.get('seller') || '').trim();
  let activePage = parseInt(urlParams.get('page') || '1', 10) || 1;
  const PAGE_SIZE = 12;
  let allSearchCards = [];
  let pendingDebounce;

  // Sync inputs from initial URL parameters
  if (sortSelect && urlParams.get('sort')) sortSelect.value = urlParams.get('sort');
  if (sellerSelect && activeSeller) sellerSelect.value = activeSeller;
  if (pFromInput && urlParams.get('price_from')) pFromInput.value = urlParams.get('price_from');
  if (pToInput && urlParams.get('price_to')) pToInput.value = urlParams.get('price_to');
  if (onlyDropInput && urlParams.get('drop') === '1') onlyDropInput.checked = true;

  function renderSearchPagination(total, page) {
    if (!pagination) return;
    const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    pagination.style.display = pages <= 1 ? 'none' : 'flex';

    if (curPageLabel) curPageLabel.textContent = String(page);
    if (totalPagesLabel) totalPagesLabel.textContent = String(pages);
    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = page >= pages;

    if (pagesList) {
      if (typeof pagesList.replaceChildren === 'function') {
        pagesList.replaceChildren();
      } else {
        while (pagesList.firstChild) pagesList.removeChild(pagesList.firstChild);
      }
      const visible = new Set([1, pages]);
      for (let p = Math.max(1, page - 2); p <= Math.min(pages, page + 2); p++) visible.add(p);
      let last = 0;
      for (const p of [...visible].sort((a, b) => a - b)) {
        if (p - last > 1) {
          const dots = document.createElement('span');
          dots.className = 'pagination-ellipsis';
          dots.textContent = '…';
          pagesList.append(dots);
        }
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `page-num-btn${p === page ? ' is-active btn--accent' : ''}`;
        btn.dataset.page = String(p);
        btn.setAttribute('aria-label', `Страница ${p}`);
        if (p === page) btn.setAttribute('aria-current', 'page');
        btn.textContent = String(p);
        pagesList.append(btn);
        last = p;
      }
    }
  }

  function applySearchFilters(page = 1, pushHistory = false) {
    const sortVal = sortSelect ? sortSelect.value : 'popular';
    const sellerVal = sellerSelect ? sellerSelect.value : activeSeller;
    const pFrom = parseFloat(pFromInput?.value) || 0;
    const pTo = parseFloat(pToInput?.value) || 0;
    const onlyDrop = Boolean(onlyDropInput?.checked);

    const matching = [];
    allSearchCards.forEach(card => {
      const p = parseFloat(card.dataset.price) || 0;
      const d = parseFloat(card.dataset.drop) || 0;
      const s = card.dataset.seller || 'retail';
      const b = (card.dataset.brand || '').toLowerCase().trim();

      let ok = true;
      if (pFrom > 0 && p < pFrom) ok = false;
      if (ok && pTo > 0 && p > pTo) ok = false;
      if (ok && sellerVal) {
        if (sellerVal === 'marketplace' && (card.dataset.mp === '1' || s === 'marketplace')) {
          // match
        } else if (sellerVal === 'crossborder' && (card.dataset.cb === '1' || s === 'crossborder')) {
          // match
        } else {
          ok = false;
        }
      }
      if (ok && activeBrand && b !== activeBrand) ok = false;
      if (ok && onlyDrop && d < 8.0) ok = false;

      if (ok) {
        matching.push(card);
      } else {
        card.style.display = 'none';
      }
    });

    matching.sort((a, b) => {
      if (sortVal === 'price_asc') return (parseFloat(a.dataset.price) || 0) - (parseFloat(b.dataset.price) || 0);
      if (sortVal === 'price_desc') return (parseFloat(b.dataset.price) || 0) - (parseFloat(a.dataset.price) || 0);
      if (sortVal === 'drop') return (parseFloat(b.dataset.drop) || 0) - (parseFloat(a.dataset.drop) || 0);
      if (sortVal === 'new') return (parseInt(b.dataset.productId, 10) || 0) - (parseInt(a.dataset.productId, 10) || 0);
      return (parseInt(b.dataset.pop, 10) || 0) - (parseInt(a.dataset.pop, 10) || 0);
    });

    const totalCount = matching.length;
    const totalPages = Math.max(1, Math.ceil(totalCount / PAGE_SIZE));
    activePage = Math.min(Math.max(1, page), totalPages);

    if (grid) {
      if (typeof grid.replaceChildren === 'function') {
        grid.replaceChildren();
      } else {
        while (grid.firstChild) grid.removeChild(grid.firstChild);
      }

      const slice = matching.slice((activePage - 1) * PAGE_SIZE, activePage * PAGE_SIZE);
      slice.forEach(c => {
        c.style.display = '';
        grid.appendChild(c);
      });

      // Keep hidden non-matching cards in DOM
      allSearchCards.forEach(c => {
        if (!matching.includes(c)) {
          c.style.display = 'none';
          grid.appendChild(c);
        }
      });

      grid.style.display = totalCount > 0 ? 'grid' : 'none';
    }

    if (totalCountEl) totalCountEl.textContent = String(totalCount);
    if (emptyState) emptyState.hidden = totalCount > 0;
    renderSearchPagination(totalCount, activePage);

    if (pushHistory && typeof history !== 'undefined' && history.pushState) {
      const url = new URL(location.href);
      if (activePage > 1) url.searchParams.set('page', String(activePage));
      else url.searchParams.delete('page');
      if (sortVal && sortVal !== 'popular') url.searchParams.set('sort', sortVal);
      else url.searchParams.delete('sort');
      if (sellerVal) url.searchParams.set('seller', sellerVal);
      else url.searchParams.delete('seller');
      if (pFrom > 0) url.searchParams.set('price_from', String(pFrom));
      else url.searchParams.delete('price_from');
      if (pTo > 0) url.searchParams.set('price_to', String(pTo));
      else url.searchParams.delete('price_to');
      if (onlyDrop) url.searchParams.set('drop', '1');
      else url.searchParams.delete('drop');
      if (activeBrand) url.searchParams.set('brand', activeBrand);
      else url.searchParams.delete('brand');
      if (url.href !== location.href) history.pushState(null, '', url.toString());
    }
  }

  // If cards were pre-rendered by server (PHP dynamic backend)
  if (grid && grid.querySelectorAll('.product-card').length > 0) {
    allSearchCards = Array.from(grid.querySelectorAll('.product-card'));
    applySearchFilters(activePage);
  } else if (q) {
    // Client-side search engine for static export / GitHub Pages
    fetchSearchIndex(prefix)
      .then(items => {
        const qClean = q.toLowerCase().trim();
        const altQuery = fixLayout(q).toLowerCase().trim();
        const rawWords = qClean.replace(/[,.()\/\\_\-+]/g, ' ').split(/\s+/).filter(w => w.length > 0);
        const altWords = altQuery.replace(/[,.()\/\\_\-+]/g, ' ').split(/\s+/).filter(w => w.length > 0);

        function matchWithWords(wordList) {
          const wordVariants = wordList.map(w => {
            const v = [w];
            if (SUGGEST_SYNONYMS[w]) v.push(SUGGEST_SYNONYMS[w]);
            const fl = fixLayout(w);
            if (fl !== w) {
              v.push(fl);
              if (SUGGEST_SYNONYMS[fl]) v.push(SUGGEST_SYNONYMS[fl]);
            }
            return [...new Set(v)];
          });

          const scored = [];
          for (const p of items) {
            const title = (p.title || '').toLowerCase();
            const brand = (p.brand || '').toLowerCase();
            const cat = (p.cat || '').toLowerCase();
            const specs = Object.values(p.specs || {}).map(v => String(v).toLowerCase()).join(' ');
            const attrs = Object.values(p.attrs || {}).map(v => String(v).toLowerCase()).join(' ');
            const fullText = `${title} ${brand} ${cat} ${specs} ${attrs}`;

            let matchedCount = 0;
            let score = 0;

            if (title.includes(qClean)) score += 100;
            if (brand === qClean) score += 50;

            for (const variants of wordVariants) {
              const inFull = variants.some(v => fullText.includes(v));
              if (inFull) {
                matchedCount++;
                if (variants.some(v => title.includes(v))) score += 20;
                else if (variants.some(v => brand.includes(v))) score += 15;
                else score += 5;
              }
            }

            if (matchedCount > 0) {
              scored.push({
                product: p,
                matchedCount,
                score: (matchedCount * 1000) + score
              });
            }
          }

          scored.sort((a, b) => b.score - a.score);
          const exactAll = scored.filter(s => s.matchedCount === wordList.length).map(s => s.product);
          if (exactAll.length > 0) return exactAll;
          const minMatches = Math.max(1, Math.ceil(wordList.length / 2));
          return scored.filter(s => s.matchedCount >= minMatches).map(s => s.product);
        }

        let matched = matchWithWords(rawWords);
        if (matched.length === 0 && altQuery !== qClean) {
          matched = matchWithWords(altWords);
          if (matched.length > 0 && heading) {
            heading.textContent = `Поиск по запросу «${q}» (исправлено на «${altQuery}»)`;
          }
        }

        if (matched.length > 0 && grid) {
          grid.innerHTML = matched.map(p => {
            const pId = Number(p.id);
            const pPrice = Number(p.price) || 0;
            const pOffers = Number(p.offers) || 1;
            const pBrand = escapeHtml(p.brand || '');
            const pTitle = escapeHtml(p.title || '');
            const pUrl = p.url || `${prefix}/p/${encodeURIComponent(p.slug || '')}-${pId}/`;
            const pImg = p.image || `${prefix}/assets/img/placeholder.svg`;
            const pDrop = parseFloat(p.drop) || 0.0;
            const pPop = parseInt(p.pop, 10) || (pOffers * 10);
            const pSeller = p.seller || (p.cb ? 'crossborder' : (p.mp ? 'marketplace' : 'retail'));
            const pMp = p.mp ? 1 : 0;
            const pCb = p.cb ? 1 : 0;

            let badgesHtml = '';
            if (pDrop >= 8.0) {
              badgesHtml += `<span class="badge badge--drop">−${Math.round(pDrop)}%</span>`;
            }
            if (pCb) {
              badgesHtml += `<span class="badge badge--crossborder">Из Китая</span>`;
            } else if (pMp) {
              badgesHtml += `<span class="badge badge--marketplace">Маркетплейс</span>`;
            }

            return `
              <article class="product-card" data-product-id="${pId}" data-price="${pPrice}" data-drop="${pDrop}" data-pop="${pPop}" data-brand="${pBrand.toLowerCase()}" data-seller="${pSeller}" data-mp="${pMp}" data-cb="${pCb}">
                <div class="product-card__badges">
                  ${badgesHtml}
                </div>
                <button type="button" class="product-card__fav" data-fav-id="${pId}" title="В избранное" aria-label="Добавить в избранное">
                  <svg class="icon icon-sm"><use href="${prefix}/assets/icons/sprite.svg#heart"></use></svg>
                </button>
                <a href="${pUrl}" class="product-card__img-wrap" tabindex="-1">
                  <img src="${escapeHtml(pImg)}" alt="${pTitle}" class="product-card__img" loading="lazy" width="180" height="180" onerror="this.onerror=null; this.src='${prefix}/assets/img/placeholder.svg';">
                </a>
                <div class="product-card__brand">${pBrand}</div>
                <a href="${pUrl}" class="product-card__title" title="${pTitle}">${pTitle}</a>
                <div class="product-card__footer">
                  <div class="product-card__price-wrap">
                    <span class="product-card__price-label">от</span>
                    <span class="product-card__price">${pPrice.toLocaleString('ru-RU')} ₽</span>
                    <span class="product-card__shops-cnt">${pOffers} предложений</span>
                  </div>
                  <button type="button" class="btn btn--sm btn--secondary" data-compare-id="${pId}" title="Сравнить" aria-label="Сравнить товар">
                    <svg class="icon icon-sm"><use href="${prefix}/assets/icons/sprite.svg#scale"></use></svg>
                  </button>
                </div>
              </article>
            `;
          }).join('');

          // Populate brand pills
          if (brandContainer) {
            const brandCounts = {};
            matched.forEach(p => { if (p.brand) brandCounts[p.brand] = (brandCounts[p.brand] || 0) + 1; });
            const topBrands = Object.entries(brandCounts).sort((a,b) => b[1] - a[1]).slice(0, 8);
            if (topBrands.length > 0) {
              brandContainer.innerHTML = `
                <span class="text-muted" style="font-size: var(--fs-xs); font-weight: 700; margin-right: 6px;">Бренд:</span>
                <button type="button" class="btn btn--sm search-brand-btn ${activeBrand === '' ? 'is-active' : ''}" data-brand="">Все</button>
                ${topBrands.map(([b, cnt]) => `
                  <button type="button" class="btn btn--sm search-brand-btn ${activeBrand === b.toLowerCase() ? 'is-active' : ''}" data-brand="${escapeHtml(b.toLowerCase())}">
                    ${escapeHtml(b)} <span class="text-muted" style="font-size: 10px;">(${cnt})</span>
                  </button>
                `).join('')}
              `;
              brandContainer.querySelectorAll('.search-brand-btn').forEach(pill => {
                pill.addEventListener('click', () => {
                  brandContainer.querySelectorAll('.search-brand-btn').forEach(p => p.classList.remove('is-active'));
                  pill.classList.add('is-active');
                  activeBrand = (pill.dataset.brand || '').toLowerCase().trim();
                  applySearchFilters(1, true);
                });
              });
            }
          }

          allSearchCards = Array.from(grid.querySelectorAll('.product-card'));
          applySearchFilters(activePage);

          // Update active states for favorites and compare
          document.querySelectorAll('#searchGrid [data-fav-id], #searchGrid [data-compare-id]').forEach(button => {
            const key = button.hasAttribute('data-fav-id') ? 'favorites' : 'compare';
            const id = Number(button.dataset.favId || button.dataset.compareId);
            try { button.classList.toggle('is-active', (JSON.parse(localStorage.getItem(key)) || []).includes(id)); } catch (_) {}
          });
        } else {
          if (grid) grid.style.display = 'none';
          if (emptyState) emptyState.hidden = false;
          if (totalCountEl) totalCountEl.textContent = '0';
        }
      })
      .catch(() => {
        if (countEl) countEl.textContent = 'Не удалось загрузить результаты поиска. Попробуйте позже.';
      });
  }

  // Filter toolbar event listeners
  sortSelect?.addEventListener('change', () => applySearchFilters(1, true));
  sellerSelect?.addEventListener('change', () => {
    activeSeller = sellerSelect.value;
    applySearchFilters(1, true);
  });
  pFromInput?.addEventListener('input', () => {
    clearTimeout(pendingDebounce);
    pendingDebounce = setTimeout(() => applySearchFilters(1, true), 300);
  });
  pToInput?.addEventListener('input', () => {
    clearTimeout(pendingDebounce);
    pendingDebounce = setTimeout(() => applySearchFilters(1, true), 300);
  });
  onlyDropInput?.addEventListener('change', () => applySearchFilters(1, true));

  resetBtn?.addEventListener('click', () => {
    if (sortSelect) sortSelect.value = 'popular';
    if (sellerSelect) sellerSelect.value = '';
    if (pFromInput) pFromInput.value = '';
    if (pToInput) pToInput.value = '';
    if (onlyDropInput) onlyDropInput.checked = false;
    activeBrand = '';
    activeSeller = '';
    brandContainer?.querySelectorAll('.search-brand-btn').forEach(p => {
      p.classList.toggle('is-active', (p.dataset.brand || '') === '');
    });
    applySearchFilters(1, true);
  });

  brandContainer?.querySelectorAll('.search-brand-btn').forEach(pill => {
    pill.addEventListener('click', () => {
      brandContainer.querySelectorAll('.search-brand-btn').forEach(p => p.classList.remove('is-active'));
      pill.classList.add('is-active');
      activeBrand = (pill.dataset.brand || '').toLowerCase().trim();
      applySearchFilters(1, true);
    });
  });

  pagination?.addEventListener('click', e => {
    const btn = e.target.closest('button');
    if (!btn || btn.disabled) return;
    let targetP = activePage;
    if (btn.id === 'searchPrevBtn') targetP = activePage - 1;
    else if (btn.id === 'searchNextBtn') targetP = activePage + 1;
    else if (btn.dataset.page) targetP = parseInt(btn.dataset.page, 10);

    if (targetP > 0 && targetP !== activePage) {
      applySearchFilters(targetP, true);
      grid?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  });

  window.addEventListener('popstate', () => {
    const params = new URLSearchParams(window.location.search);
    activePage = parseInt(params.get('page') || '1', 10) || 1;
    activeBrand = (params.get('brand') || '').toLowerCase().trim();
    activeSeller = (params.get('seller') || '').trim();
    if (sortSelect) sortSelect.value = params.get('sort') || 'popular';
    if (sellerSelect) sellerSelect.value = activeSeller;
    if (pFromInput) pFromInput.value = params.get('price_from') || '';
    if (pToInput) pToInput.value = params.get('price_to') || '';
    if (onlyDropInput) onlyDropInput.checked = params.get('drop') === '1';
    brandContainer?.querySelectorAll('.search-brand-btn').forEach(p => {
      p.classList.toggle('is-active', (p.dataset.brand || '') === activeBrand);
    });
    applySearchFilters(activePage, false);
  });
}

window.PriceHubInitSearchResults = initSearchResults;


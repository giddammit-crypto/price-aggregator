<?php
/**
 * Search Results Page View with Interactive Sorting, Filters and Real Product Photography
 * @var string $query
 * @var array $results
 * @var float $durationMs
 * @var App\Core\View $view
 */
declare(strict_types=1);

$brands = [];
foreach ($results as $item) {
    if (!empty($item['brand'])) {
        $brands[$item['brand']] = ($brands[$item['brand']] ?? 0) + 1;
    }
}
arsort($brands);
?>

<div class="container" style="padding-top: var(--sp-4); padding-bottom: var(--sp-8);">
  <div style="margin-bottom: var(--sp-4);">
    <!-- Breadcrumbs -->
    <nav class="mb-3 text-muted" style="font-size: var(--fs-xs);" aria-label="Хлебные крошки">
      <a href="/">Главная</a> &rarr; 
      <span class="font-bold" style="color: var(--c-ink);">Поиск</span>
    </nav>

    <div class="d-flex justify-between align-center flex-wrap gap-2">
      <div>
        <h1 id="searchHeading" style="font-size: var(--fs-2xl); font-weight: 800; margin-bottom: var(--sp-1);">
          Поиск по запросу «<?= e($query) ?>»
        </h1>
        <p id="searchCount" class="text-muted" style="font-size: var(--fs-sm);">
          Найдено <span id="searchTotalCount" class="font-bold" style="color: var(--c-ink);"><?= count($results) ?></span> товаров
        </p>
      </div>
    </div>
  </div>

  <!-- Search Filter & Sort Toolbar -->
  <div id="searchToolbar" class="search-toolbar" style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: 14px 18px; margin-bottom: var(--sp-4);">
    <div class="d-flex align-center justify-between flex-wrap gap-3">
      <!-- Sort & Price Range -->
      <div class="d-flex align-center flex-wrap gap-3">
        <div class="d-flex align-center gap-2">
          <label for="searchSortSelect" class="text-muted" style="font-size: var(--fs-xs); font-weight: 700; white-space: nowrap;">Сортировка:</label>
          <select id="searchSortSelect" class="search-input" style="height: 36px; border: 1px solid var(--c-line); padding: 0 10px; font-size: var(--fs-sm); border-radius: var(--r-sm); background: var(--c-bg);">
            <option value="popular">По популярности</option>
            <option value="price_asc">Сначала дешевле</option>
            <option value="price_desc">Сначала дороже</option>
            <option value="drop">По размеру скидки</option>
          </select>
        </div>

        <div class="d-flex align-center gap-2">
          <span class="text-muted" style="font-size: var(--fs-xs); font-weight: 700;">Цена, ₽:</span>
          <input type="number" id="searchPriceFrom" placeholder="от" class="search-input" style="width: 85px; height: 36px; border: 1px solid var(--c-line); padding: 0 8px; font-size: var(--fs-sm); border-radius: var(--r-sm);">
          <span class="text-muted">–</span>
          <input type="number" id="searchPriceTo" placeholder="до" class="search-input" style="width: 85px; height: 36px; border: 1px solid var(--c-line); padding: 0 8px; font-size: var(--fs-sm); border-radius: var(--r-sm);">
        </div>

        <label class="filter-checkbox" style="font-size: var(--fs-xs); cursor: pointer; user-select: none;">
          <input type="checkbox" id="searchOnlyDrop">
          <span style="font-weight: 700; color: var(--c-bad);">Только со скидкой</span>
        </label>
      </div>

      <!-- Quick Reset Button -->
      <div>
        <button type="button" id="searchResetFilters" class="btn btn--sm btn--outline" style="font-size: var(--fs-xs);">
          Сбросить фильтры
        </button>
      </div>
    </div>

    <!-- Brand Filter Pills -->
    <?php if (!empty($brands)): ?>
      <div id="searchBrandFilters" class="d-flex align-center flex-wrap gap-1" style="margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--c-line);">
        <span class="text-muted" style="font-size: var(--fs-xs); font-weight: 700; margin-right: 6px;">Бренд:</span>
        <button type="button" class="btn btn--sm search-brand-btn is-active" data-brand="">Все</button>
        <?php foreach (array_slice($brands, 0, 8) as $bName => $bCount): ?>
          <button type="button" class="btn btn--sm search-brand-btn" data-brand="<?= e(mb_strtolower($bName)) ?>">
            <?= e($bName) ?> <span class="text-muted" style="font-size: 10px;">(<?= $bCount ?>)</span>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="product-grid" id="searchGrid" <?= empty($results) ? 'style="display:none;"' : '' ?>>
    <?php if (!empty($results)): ?>
      <?php foreach ($results as $p): ?>
        <?= $view->partial('partials/product_card', [
          'p' => [
            'id' => $p['id'],
            't' => $p['title'],
            'b' => $p['brand'],
            'slug' => $p['slug'],
            'img' => $p['img'] ?? null,
            'p' => $p['agg']['min'] ?? 0,
            'c' => $p['agg']['cnt'] ?? count($p['offers'] ?? []),
            'd' => $p['agg']['drop'] ?? 0,
            'pop' => $p['popularity'] ?? 0,
            'attrs' => $p['attrs'] ?? [],
            'mp' => $p['agg']['mp'] ?? 0,
            'cb' => $p['agg']['cb'] ?? 0
          ]
        ]) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Search Pagination -->
  <nav id="searchPagination" class="catalog-pagination" style="margin-top: var(--sp-6); display: none;" aria-label="Пагинация результатов поиска">
    <div class="pagination-controls">
      <button type="button" id="searchPrevBtn" class="pagination-btn-nav">&larr; Назад</button>
      <div class="pagination-pages" id="searchPagesList"></div>
      <button type="button" id="searchNextBtn" class="pagination-btn-nav">Вперед &rarr;</button>
    </div>
    <div class="pagination-summary">
      Страница <strong id="searchCurPageLabel">1</strong> из <strong id="searchTotalPagesLabel">1</strong>
    </div>
  </nav>

  <div id="searchEmptyState" <?= !empty($results) ? 'hidden' : '' ?> style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: 48px; text-align: center; max-width: 600px; margin: 0 auto;">
    <svg class="icon icon-lg" style="color: var(--c-ink-3); width: 48px; height: 48px; margin: 0 auto var(--sp-3);"><use href="/assets/icons/sprite.svg#search"></use></svg>
    <h2 style="font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-2);">Ничего не нашлось</h2>
    <p class="text-muted" style="font-size: var(--fs-sm); margin-bottom: var(--sp-4);">
      Проверьте правильность написания или попробуйте поискать по бренду, например: 
      <a href="/search?q=ASUS" style="color: var(--c-accent); text-decoration: underline;">ASUS</a>, 
      <a href="/search?q=Apple" style="color: var(--c-accent); text-decoration: underline;">Apple</a>, 
      <a href="/search?q=RTX" style="color: var(--c-accent); text-decoration: underline;">RTX</a>.
    </p>
    <a href="/" class="btn btn--accent">Вернуться на главную</a>
  </div>
</div>

<style>
.search-brand-btn {
  background: var(--c-bg);
  border: 1px solid var(--c-line);
  color: var(--c-ink);
  padding: 4px 10px;
  font-size: var(--fs-xs);
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: all 0.15s ease;
}
.search-brand-btn:hover {
  border-color: var(--c-accent);
  color: var(--c-accent);
}
.search-brand-btn.is-active {
  background: var(--c-accent);
  border-color: var(--c-accent);
  color: #fff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const q = urlParams.get('q') || '';
  const prefix = window.location.pathname.startsWith('/price-aggregator') ? '/price-aggregator' : '';
  const heading = document.getElementById('searchHeading');
  const countEl = document.getElementById('searchCount');
  const totalCountEl = document.getElementById('searchTotalCount');
  const grid = document.getElementById('searchGrid');
  const emptyState = document.getElementById('searchEmptyState');
  const sortSelect = document.getElementById('searchSortSelect');
  const pFromInput = document.getElementById('searchPriceFrom');
  const pToInput = document.getElementById('searchPriceTo');
  const onlyDropInput = document.getElementById('searchOnlyDrop');
  const resetBtn = document.getElementById('searchResetFilters');
  const brandPills = document.querySelectorAll('.search-brand-btn');
  const pagination = document.getElementById('searchPagination');
  const prevBtn = document.getElementById('searchPrevBtn');
  const nextBtn = document.getElementById('searchNextBtn');
  const pagesList = document.getElementById('searchPagesList');
  const curPageLabel = document.getElementById('searchCurPageLabel');
  const totalPagesLabel = document.getElementById('searchTotalPagesLabel');

  if (q && heading) heading.textContent = `Поиск по запросу «${q}»`;

  let activeBrand = '';
  let activePage = 1;
  const PAGE_SIZE = 12;
  let allSearchCards = [];

  function fixLayout(text) {
    const enToRu = {'q':'й','w':'ц','e':'у','r':'к','t':'е','y':'н','u':'г','i':'ш','o':'щ','p':'з','[':'х',']':'ъ','a':'ф','s':'ы','d':'в','f':'а','g':'п','h':'р','j':'о','k':'л','l':'д',';':'ж','\'':'э','z':'я','x':'ч','c':'с','v':'м','b':'и','n':'т','m':'ь',',':'б','.':'ю'};
    const ruToEn = {};
    for (const [k, v] of Object.entries(enToRu)) ruToEn[v] = k;
    const isLatin = /^[a-z0-9\s\[\];,.'"-]+$/i.test(text);
    return text.toLowerCase().split('').map(c => (isLatin ? enToRu[c] : ruToEn[c]) || c).join('');
  }

  function escapeHtml(val) {
    return String(val ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function renderSearchPagination(total, page) {
    if (!pagination) return;
    const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    pagination.style.display = pages <= 1 ? 'none' : 'flex';

    if (curPageLabel) curPageLabel.textContent = String(page);
    if (totalPagesLabel) totalPagesLabel.textContent = String(pages);
    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = page >= pages;

    if (pagesList) {
      pagesList.replaceChildren();
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
        btn.textContent = String(p);
        pagesList.append(btn);
        last = p;
      }
    }
  }

  function applySearchFilters(page = 1) {
    const sortVal = sortSelect ? sortSelect.value : 'popular';
    const pFrom = parseFloat(pFromInput?.value) || 0;
    const pTo = parseFloat(pToInput?.value) || 0;
    const onlyDrop = Boolean(onlyDropInput?.checked);

    const matching = allSearchCards.filter(card => {
      const p = parseFloat(card.dataset.price) || 0;
      const d = parseFloat(card.dataset.drop) || 0;
      const b = (card.dataset.brand || '').toLowerCase().trim();

      if (pFrom > 0 && p < pFrom) return false;
      if (pTo > 0 && p > pTo) return false;
      if (activeBrand && b !== activeBrand) return false;
      if (onlyDrop && d < 8.0) return false;
      return true;
    });

    matching.sort((a, b) => {
      if (sortVal === 'price_asc') return (parseFloat(a.dataset.price) || 0) - (parseFloat(b.dataset.price) || 0);
      if (sortVal === 'price_desc') return (parseFloat(b.dataset.price) || 0) - (parseFloat(a.dataset.price) || 0);
      if (sortVal === 'drop') return (parseFloat(b.dataset.drop) || 0) - (parseFloat(a.dataset.drop) || 0);
      return (parseInt(b.dataset.pop, 10) || 0) - (parseInt(a.dataset.pop, 10) || 0);
    });

    const totalCount = matching.length;
    const totalPages = Math.max(1, Math.ceil(totalCount / PAGE_SIZE));
    activePage = Math.min(Math.max(1, page), totalPages);

    if (grid) {
      grid.replaceChildren();
      const slice = matching.slice((activePage - 1) * PAGE_SIZE, activePage * PAGE_SIZE);
      slice.forEach(c => {
        c.style.display = '';
        grid.appendChild(c);
      });
      grid.style.display = totalCount > 0 ? 'grid' : 'none';
    }

    if (totalCountEl) totalCountEl.textContent = String(totalCount);
    if (emptyState) emptyState.hidden = totalCount > 0;
    renderSearchPagination(totalCount, activePage);
  }

  // If cards were rendered by server
  if (grid && grid.children.length > 0) {
    allSearchCards = Array.from(grid.querySelectorAll('.product-card'));
    if (allSearchCards.length > PAGE_SIZE) {
      applySearchFilters(1);
    }
  } else if (q && (window.location.hostname.endsWith('github.io') || window.location.pathname.endsWith('.html'))) {
    // Client-side index search on static site
    fetch(prefix + '/api/search_index.json')
      .then(r => { if (!r.ok) throw new Error('Search index unavailable'); return r.json(); })
      .then(items => {
        const qLower = q.toLowerCase();
        const altQuery = fixLayout(q).toLowerCase();

        let matched = items.filter(p => {
          const str = `${p.title} ${p.brand || ''} ${p.cat || ''}`.toLowerCase();
          return str.includes(qLower);
        });

        if (matched.length === 0 && altQuery !== qLower) {
          matched = items.filter(p => {
            const str = `${p.title} ${p.brand || ''} ${p.cat || ''}`.toLowerCase();
            return str.includes(altQuery);
          });
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
            const pImg = p.image || `${prefix}/assets/img/products/phone-iphone15.webp`;

            return `
              <article class="product-card" data-product-id="${pId}" data-price="${pPrice}" data-drop="0" data-pop="${pOffers}" data-brand="${pBrand.toLowerCase()}">
                <button type="button" class="product-card__fav" data-fav-id="${pId}" title="В избранное">
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
                  <button type="button" class="btn btn--sm btn--secondary" data-compare-id="${pId}" title="Сравнить">
                    <svg class="icon icon-sm"><use href="${prefix}/assets/icons/sprite.svg#scale"></use></svg>
                  </button>
                </div>
              </article>
            `;
          }).join('');

          allSearchCards = Array.from(grid.querySelectorAll('.product-card'));
          applySearchFilters(1);

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
  sortSelect?.addEventListener('change', () => applySearchFilters(1));
  pFromInput?.addEventListener('input', () => applySearchFilters(1));
  pToInput?.addEventListener('input', () => applySearchFilters(1));
  onlyDropInput?.addEventListener('change', () => applySearchFilters(1));

  resetBtn?.addEventListener('click', () => {
    if (sortSelect) sortSelect.value = 'popular';
    if (pFromInput) pFromInput.value = '';
    if (pToInput) pToInput.value = '';
    if (onlyDropInput) onlyDropInput.checked = false;
    activeBrand = '';
    brandPills.forEach(p => p.classList.toggle('is-active', p.dataset.brand === ''));
    applySearchFilters(1);
  });

  brandPills.forEach(pill => {
    pill.addEventListener('click', () => {
      brandPills.forEach(p => p.classList.remove('is-active'));
      pill.classList.add('is-active');
      activeBrand = (pill.dataset.brand || '').toLowerCase().trim();
      applySearchFilters(1);
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
      applySearchFilters(targetP);
      grid?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  });
});
</script>

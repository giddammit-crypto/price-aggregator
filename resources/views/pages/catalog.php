<?php
/**
 * Catalog Listing Page View
 * @var array $category
 * @var int $catId
 * @var array $items
 * @var int $total
 * @var int $page
 * @var int $totalPages
 * @var array $facets
 * @var string $selectedBrand
 * @var string $selectedPriceFrom
 * @var string $selectedPriceTo
 * @var string $selectedSeller
 * @var string $currentSort
 * @var App\Core\View $view
 */
declare(strict_types=1);

$brands = $facets['brand'] ?? [];
?>

<div class="container">
  <!-- Breadcrumbs -->
  <nav class="mb-4 text-muted" style="font-size: var(--fs-xs);" aria-label="Хлебные крошки">
    <a href="/">Главная</a> &rarr; 
    <a href="/catalog/pc-components">Каталог</a> &rarr; 
    <span class="font-bold" style="color: var(--c-ink);"><?= e($category['name']) ?></span>
  </nav>

  <div class="d-flex justify-between align-center mb-4 flex-wrap gap-2">
    <div>
      <h1 style="font-size: var(--fs-2xl); font-weight: 800; letter-spacing: -0.5px;">
        <?= e($category['name']) ?>
      </h1>
      <p class="text-muted" style="font-size: var(--fs-sm);">
        Найдено <span id="filterCount" class="font-bold" style="color: var(--c-ink);"><?= $total ?></span> товаров
      </p>
    </div>

    <!-- Mobile Filter Toggle Button -->
    <button type="button" class="btn btn--secondary btn--sm catalog-filters-btn" id="openFiltersBtn" aria-controls="filterSidebar" aria-expanded="false">
      <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#filter"></use></svg>
      <span>Фильтры</span>
    </button>
  </div>

  <div class="catalog-layout">
    <!-- Filter Sidebar -->
    <aside class="filters-sidebar" id="filterSidebar">
      <div class="d-flex justify-between align-center mb-4" id="filterHeaderMobile">
        <h3 class="font-bold">Фильтры</h3>
        <div class="d-flex align-center gap-2">
          <a href="?" id="resetFiltersBtn" class="text-muted" style="font-size: var(--fs-xs);">Сбросить все</a>
          <button type="button" class="btn btn--sm" id="closeFiltersBtn" aria-label="Закрыть фильтры" style="padding: 2px 8px; font-size: 18px; line-height: 1;">&times;</button>
        </div>
      </div>

      <form id="filterForm" method="GET" action="">
        <input type="hidden" name="catId" value="<?= $catId ?>">

        <!-- Sort Control -->
        <div class="filter-group">
          <label class="filter-group__summary" for="sortSelect">Сортировка</label>
          <div class="filter-group__content">
            <select name="sort" id="sortSelect" class="search-input" style="height: 38px; border: 1px solid var(--c-line); padding: 0 10px;">
              <option value="popular" <?= ($currentSort === 'popular') ? 'selected' : '' ?>>По популярности</option>
              <option value="price_asc" <?= ($currentSort === 'price_asc') ? 'selected' : '' ?>>Сначала дешевле</option>
              <option value="price_desc" <?= ($currentSort === 'price_desc') ? 'selected' : '' ?>>Сначала дороже</option>
              <option value="drop" <?= ($currentSort === 'drop') ? 'selected' : '' ?>>По размеру скидки</option>
              <option value="new" <?= ($currentSort === 'new') ? 'selected' : '' ?>>Новинки</option>
            </select>
          </div>
        </div>

        <!-- Price Range -->
        <div class="filter-group">
          <div class="filter-group__summary">Цена, ₽</div>
          <div class="filter-group__content">
            <div class="d-flex gap-2">
              <input type="number" name="price_from" placeholder="от" value="<?= e($selectedPriceFrom) ?>" class="search-input" style="height: 36px; border: 1px solid var(--c-line); padding: 0 8px;">
              <input type="number" name="price_to" placeholder="до" value="<?= e($selectedPriceTo) ?>" class="search-input" style="height: 36px; border: 1px solid var(--c-line); padding: 0 8px;">
            </div>
          </div>
        </div>

        <!-- Seller Type -->
        <div class="filter-group">
          <div class="filter-group__summary">Продавцы</div>
          <div class="filter-group__content">
            <label class="filter-checkbox">
              <input type="radio" name="seller" value="" <?= ($selectedSeller === '') ? 'checked' : '' ?>>
              <span>Все площадки</span>
            </label>
            <label class="filter-checkbox">
              <input type="radio" name="seller" value="marketplace" <?= ($selectedSeller === 'marketplace') ? 'checked' : '' ?>>
              <span>Маркетплейсы</span>
            </label>
            <label class="filter-checkbox">
              <input type="radio" name="seller" value="crossborder" <?= ($selectedSeller === 'crossborder') ? 'checked' : '' ?>>
              <span>Из Китая (AliExpress)</span>
            </label>
          </div>
        </div>

        <!-- Brand Filter -->
        <?php if (!empty($brands)): ?>
          <div class="filter-group">
            <div class="filter-group__summary">Бренд</div>
            <div class="filter-group__content" style="max-height: 220px; overflow-y: auto;">
              <label class="filter-checkbox">
                <input type="radio" name="brand" value="" <?= ($selectedBrand === '') ? 'checked' : '' ?>>
                <span>Все бренды</span>
              </label>
              <?php foreach ($brands as $bName => $bCount): ?>
                <label class="filter-checkbox">
                  <input type="radio" name="brand" value="<?= e($bName) ?>" <?= ($selectedBrand === $bName) ? 'checked' : '' ?>>
                  <span><?= e($bName) ?></span>
                  <span class="text-muted" style="margin-left: auto; font-size: var(--fs-xs);">(<?= $bCount ?>)</span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Discount toggle -->
        <div class="filter-group">
          <label class="filter-checkbox font-bold">
            <input type="checkbox" name="drop" value="1" <?= !empty($_GET['drop']) ? 'checked' : '' ?>>
            <span style="color: var(--c-bad);">Только со скидкой</span>
          </label>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn--accent btn--sm" style="width: 100%;">Применить</button>
        </div>
      </form>
    </aside>

    <!-- Main Products Grid Area -->
    <main>
      <div class="d-flex justify-between align-center mb-3 flex-wrap gap-2 catalog-toolbar">
        <div class="d-flex align-center gap-2">
          <label for="sortSelectTop" class="text-muted" style="font-size: var(--fs-sm); font-weight: 600;">Сортировка:</label>
          <select id="sortSelectTop" class="search-input" style="height: 36px; border: 1px solid var(--c-line); padding: 0 10px; font-size: var(--fs-sm); border-radius: var(--r-sm);">
            <option value="popular" <?= ($currentSort === 'popular') ? 'selected' : '' ?>>По популярности</option>
            <option value="price_asc" <?= ($currentSort === 'price_asc') ? 'selected' : '' ?>>Сначала дешевле</option>
            <option value="price_desc" <?= ($currentSort === 'price_desc') ? 'selected' : '' ?>>Сначала дороже</option>
            <option value="drop" <?= ($currentSort === 'drop') ? 'selected' : '' ?>>По размеру скидки</option>
            <option value="new" <?= ($currentSort === 'new') ? 'selected' : '' ?>>Новинки</option>
          </select>
        </div>
      </div>

      <div class="product-grid" id="catalogProducts">
        <?php foreach ($items as $p): ?>
          <?= $view->partial('partials/product_card', ['p' => $p]) ?>
        <?php endforeach; ?>
      </div>

      <!-- Top-Tier Pagination Component -->
      <nav id="catalogPagination" class="catalog-pagination" aria-label="Пагинация каталога">
        <div class="pagination-controls">
          <button type="button" id="prevPageBtn" class="pagination-btn-nav" <?= ($page <= 1) ? 'disabled' : '' ?>>
            &larr; Назад
          </button>

          <div class="pagination-pages" id="paginationPagesList">
            <?php 
            $tot = max(1, $totalPages);
            $cur = min(max(1, $page), $tot);
            if ($tot <= 7):
              for ($pNum = 1; $pNum <= $tot; $pNum++):
            ?>
              <button type="button" class="page-num-btn <?= ($pNum === $cur) ? 'is-active btn--accent' : '' ?>" data-page="<?= $pNum ?>" aria-label="Страница <?= $pNum ?>" <?= ($pNum === $cur) ? 'aria-current="page"' : '' ?>>
                <?= $pNum ?>
              </button>
            <?php 
              endfor;
            else:
              // Sliding window with ellipsis
            ?>
              <button type="button" class="page-num-btn <?= (1 === $cur) ? 'is-active btn--accent' : '' ?>" data-page="1" aria-label="Страница 1" <?= (1 === $cur) ? 'aria-current="page"' : '' ?>>
                1
              </button>
              <?php
              $startP = ($cur <= 4) ? 2 : (($cur >= $tot - 3) ? $tot - 4 : $cur - 1);
              $endP = ($cur <= 4) ? 5 : (($cur >= $tot - 3) ? $tot - 1 : $cur + 1);

              if ($startP > 2):
              ?>
                <span class="pagination-ellipsis">…</span>
              <?php endif; ?>

              <?php for ($pNum = $startP; $pNum <= $endP; $pNum++): ?>
                <button type="button" class="page-num-btn <?= ($pNum === $cur) ? 'is-active btn--accent' : '' ?>" data-page="<?= $pNum ?>" aria-label="Страница <?= $pNum ?>" <?= ($pNum === $cur) ? 'aria-current="page"' : '' ?>>
                  <?= $pNum ?>
                </button>
              <?php endfor; ?>

              <?php if ($endP < $tot - 1): ?>
                <span class="pagination-ellipsis">…</span>
              <?php endif; ?>

              <button type="button" class="page-num-btn <?= ($tot === $cur) ? 'is-active btn--accent' : '' ?>" data-page="<?= $tot ?>" aria-label="Страница <?= $tot ?>" <?= ($tot === $cur) ? 'aria-current="page"' : '' ?>>
                <?= $tot ?>
              </button>
            <?php endif; ?>
          </div>

          <button type="button" id="nextPageBtn" class="pagination-btn-nav" <?= ($page >= $totalPages) ? 'disabled' : '' ?>>
            Вперед &rarr;
          </button>
        </div>

        <div class="pagination-summary" id="paginationSummary">
          Страница <strong id="currentPageLabel"><?= $page ?></strong> из <strong id="totalPagesLabel"><?= max(1, $totalPages) ?></strong>
        </div>
      </nav>
    </main>
  </div>
</div>

<style>
@media (max-width: 1024px) {
  #openFiltersBtn { display: inline-flex !important; align-items: center; gap: 6px; }
  #closeFiltersBtn { display: inline-flex !important; }
}
@media (min-width: 1025px) {
  #openFiltersBtn { display: none !important; }
  #closeFiltersBtn { display: none !important; }
}
</style>

<?php $view->startSection('scripts'); ?>
<script>
(function() {
  function initCatalogFallback() {
    // If ES module filters already initialized, skip fallback
    if (window.PriceHubFiltersInitialized) return;

    const sortSelect = document.getElementById('sortSelect');
    const sortSelectTop = document.getElementById('sortSelectTop');
    const filterForm = document.getElementById('filterForm');
    const catalogGrid = document.getElementById('catalogProducts');
    const paginationNav = document.getElementById('catalogPagination');
    if (!catalogGrid) return;

    let activeFallbackPage = 1;

    function applyFallbackSortFilter(updateHistory, targetPage) {
      if (window.PriceHubApplyFilters && typeof window.PriceHubApplyFilters === 'function') {
        window.PriceHubApplyFilters(updateHistory !== false, targetPage);
        return;
      }

      const activeSort = (sortSelect ? sortSelect.value : '') || (sortSelectTop ? sortSelectTop.value : 'popular');
      const cards = Array.from(catalogGrid.querySelectorAll('.product-card'));
      if (!cards.length) return;

      cards.sort((a, b) => {
        if (activeSort === 'price_asc') return (parseFloat(a.dataset.price) || 0) - (parseFloat(b.dataset.price) || 0);
        if (activeSort === 'price_desc') return (parseFloat(b.dataset.price) || 0) - (parseFloat(a.dataset.price) || 0);
        if (activeSort === 'drop') return (parseFloat(b.dataset.drop) || 0) - (parseFloat(a.dataset.drop) || 0);
        if (activeSort === 'new') return (parseInt(b.dataset.productId, 10) || 0) - (parseInt(a.dataset.productId, 10) || 0);
        return (parseInt(b.dataset.pop, 10) || 0) - (parseInt(a.dataset.pop, 10) || 0);
      });

      const frag = document.createDocumentFragment();
      cards.forEach(c => frag.appendChild(c));
      catalogGrid.appendChild(frag);

      let pFrom = 0, pTo = 0, seller = '', brand = '', onlyDrop = false;
      if (filterForm) {
        const pFromEl = filterForm.querySelector('input[name="price_from"]');
        const pToEl = filterForm.querySelector('input[name="price_to"]');
        pFrom = pFromEl && pFromEl.value ? parseFloat(pFromEl.value) : 0;
        pTo = pToEl && pToEl.value ? parseFloat(pToEl.value) : 0;
        const sellerEl = filterForm.querySelector('input[name="seller"]:checked');
        seller = sellerEl ? sellerEl.value : '';
        const brandEl = filterForm.querySelector('input[name="brand"]:checked');
        brand = brandEl ? brandEl.value.toLowerCase().trim() : '';
        const dropEl = filterForm.querySelector('input[name="drop"]');
        onlyDrop = dropEl ? dropEl.checked : false;
      }

      const matchingCards = [];
      cards.forEach(card => {
        const p = parseFloat(card.dataset.price) || 0;
        const d = parseFloat(card.dataset.drop) || 0;
        const s = card.dataset.seller || 'retail';
        const b = (card.dataset.brand || '').toLowerCase().trim();

        let ok = true;
        if (pFrom > 0 && p < pFrom) ok = false;
        if (ok && pTo > 0 && p > pTo) ok = false;
        if (ok && seller && s !== seller) ok = false;
        if (ok && brand && b !== brand) ok = false;
        if (ok && onlyDrop && d < 8.0) ok = false;

        if (ok) matchingCards.push(card);
        else card.style.display = 'none';
      });

      const PAGE_SIZE = 12;
      const totalMatching = matchingCards.length;
      const totalPages = Math.max(1, Math.ceil(totalMatching / PAGE_SIZE));
      const reqPage = (targetPage !== null && targetPage !== undefined) ? parseInt(targetPage, 10) : activeFallbackPage;
      const activePage = Math.min(Math.max(1, isNaN(reqPage) ? 1 : reqPage), totalPages);
      activeFallbackPage = activePage;

      matchingCards.forEach((c, idx) => {
        const onPage = (idx >= (activePage - 1) * PAGE_SIZE && idx < activePage * PAGE_SIZE);
        c.style.display = onPage ? '' : 'none';
      });

      const countEl = document.getElementById('filterCount');
      if (countEl) countEl.textContent = totalMatching;

      if (paginationNav) {
        if (totalMatching === 0 || totalPages <= 1) {
          paginationNav.style.display = 'none';
        } else {
          paginationNav.style.display = 'flex';
          const curLabel = document.getElementById('currentPageLabel');
          const totLabel = document.getElementById('totalPagesLabel');
          const prevBtn = document.getElementById('prevPageBtn');
          const nextBtn = document.getElementById('nextPageBtn');
          const pagesList = document.getElementById('paginationPagesList');

          if (curLabel) curLabel.textContent = String(activePage);
          if (totLabel) totLabel.textContent = String(totalPages);
          if (prevBtn) prevBtn.disabled = (activePage <= 1);
          if (nextBtn) nextBtn.disabled = (activePage >= totalPages);

          if (pagesList) {
            let html = '';
            if (totalPages <= 7) {
              for (let p = 1; p <= totalPages; p++) {
                html += '<button type="button" class="page-num-btn ' + (p === activePage ? 'is-active btn--accent' : '') + '" data-page="' + p + '">' + p + '</button>';
              }
            } else {
              html += '<button type="button" class="page-num-btn ' + (1 === activePage ? 'is-active btn--accent' : '') + '" data-page="1">1</button>';
              let sP = (activePage <= 4) ? 2 : ((activePage >= totalPages - 3) ? totalPages - 4 : activePage - 1);
              let eP = (activePage <= 4) ? 5 : ((activePage >= totalPages - 3) ? totalPages - 1 : activePage + 1);
              if (sP > 2) html += '<span class="pagination-ellipsis">…</span>';
              for (let p = sP; p <= eP; p++) {
                html += '<button type="button" class="page-num-btn ' + (p === activePage ? 'is-active btn--accent' : '') + '" data-page="' + p + '">' + p + '</button>';
              }
              if (eP < totalPages - 1) html += '<span class="pagination-ellipsis">…</span>';
              html += '<button type="button" class="page-num-btn ' + (totalPages === activePage ? 'is-active btn--accent' : '') + '" data-page="' + totalPages + '">' + totalPages + '</button>';
            }
            pagesList.innerHTML = html;
          }
        }
      }
    }

    if (paginationNav) {
      paginationNav.addEventListener('click', (e) => {
        const pageBtn = e.target.closest('[data-page]');
        const prevBtn = e.target.closest('#prevPageBtn');
        const nextBtn = e.target.closest('#nextPageBtn');
        if (!pageBtn && !prevBtn && !nextBtn) return;
        e.preventDefault();

        let t = activeFallbackPage;
        if (pageBtn) t = parseInt(pageBtn.dataset.page, 10);
        else if (prevBtn && !prevBtn.disabled) t = activeFallbackPage - 1;
        else if (nextBtn && !nextBtn.disabled) t = activeFallbackPage + 1;

        if (t >= 1 && t !== activeFallbackPage) {
          applyFallbackSortFilter(true, t);
          catalogGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        if (sortSelectTop) sortSelectTop.value = sortSelect.value;
        applyFallbackSortFilter(true, 1);
      });
    }
    if (sortSelectTop) {
      sortSelectTop.addEventListener('change', () => {
        if (sortSelect) sortSelect.value = sortSelectTop.value;
        applyFallbackSortFilter(true, 1);
      });
    }
    if (filterForm) {
      filterForm.addEventListener('change', (e) => {
        if (e.target === sortSelect) return;
        applyFallbackSortFilter(true, 1);
      });
      filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        applyFallbackSortFilter(true, 1);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCatalogFallback);
  } else {
    initCatalogFallback();
  }
})();
</script>
<?php $view->endSection(); ?>

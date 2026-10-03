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
    <button type="button" class="btn btn--secondary btn--sm catalog-filters-btn" id="openFiltersBtn">
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

      <!-- Pagination Component -->
      <nav id="catalogPagination" class="d-flex justify-between align-center flex-wrap gap-2 mt-4" style="padding-top: var(--sp-4); border-top: 1px solid var(--c-line);">
        <button type="button" id="prevPageBtn" class="btn btn--secondary btn--sm" <?= ($page <= 1) ? 'disabled' : '' ?>>
          &larr; Назад
        </button>

        <div class="d-flex align-center gap-1 flex-wrap" id="paginationPagesList">
          <?php for ($pNum = 1; $pNum <= max(1, $totalPages); $pNum++): ?>
            <button type="button" class="btn btn--sm <?= ($pNum === $page) ? 'btn--accent' : 'btn--secondary' ?> page-num-btn" data-page="<?= $pNum ?>">
              <?= $pNum ?>
            </button>
          <?php endfor; ?>
        </div>

        <span class="text-muted" id="paginationSummary" style="font-size: var(--fs-sm);">
          Страница <strong id="currentPageLabel"><?= $page ?></strong> из <strong id="totalPagesLabel"><?= max(1, $totalPages) ?></strong>
        </span>

        <button type="button" id="nextPageBtn" class="btn btn--secondary btn--sm" <?= ($page >= $totalPages) ? 'disabled' : '' ?>>
          Вперед &rarr;
        </button>
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
    const sortSelect = document.getElementById('sortSelect');
    const sortSelectTop = document.getElementById('sortSelectTop');
    const filterForm = document.getElementById('filterForm');
    const catalogGrid = document.getElementById('catalogProducts');
    if (!catalogGrid) return;

    function applyFallbackSortFilter() {
      if (window.PriceHubApplyFilters && typeof window.PriceHubApplyFilters === 'function') {
        window.PriceHubApplyFilters();
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
      const activePage = Math.min(Math.max(1, targetPage || 1), totalPages);

      matchingCards.forEach((c, idx) => {
        const onPage = (idx >= (activePage - 1) * PAGE_SIZE && idx < activePage * PAGE_SIZE);
        c.style.display = onPage ? '' : 'none';
      });

      const countEl = document.getElementById('filterCount');
      if (countEl) countEl.textContent = totalMatching;

      const paginationNav = document.getElementById('catalogPagination');
      if (paginationNav) {
        if (totalMatching === 0 || totalPages <= 1) {
          paginationNav.style.display = 'none';
        } else {
          paginationNav.style.display = 'flex';
          const curLabel = document.getElementById('currentPageLabel');
          const totLabel = document.getElementById('totalPagesLabel');
          const prevBtn = document.getElementById('prevPageBtn');
          const nextBtn = document.getElementById('nextPageBtn');
          if (curLabel) curLabel.textContent = String(activePage);
          if (totLabel) totLabel.textContent = String(totalPages);
          if (prevBtn) prevBtn.disabled = (activePage <= 1);
          if (nextBtn) nextBtn.disabled = (activePage >= totalPages);
        }
      }
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        if (sortSelectTop) sortSelectTop.value = sortSelect.value;
        applyFallbackSortFilter();
      });
    }
    if (sortSelectTop) {
      sortSelectTop.addEventListener('change', () => {
        if (sortSelect) sortSelect.value = sortSelectTop.value;
        applyFallbackSortFilter();
      });
    }
    if (filterForm) {
      filterForm.addEventListener('change', (e) => {
        if (e.target === sortSelect) return;
        applyFallbackSortFilter();
      });
      filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        applyFallbackSortFilter();
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

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
            <option value="new">Новинки</option>
          </select>
        </div>

        <div class="d-flex align-center gap-2">
          <label for="searchSellerSelect" class="text-muted" style="font-size: var(--fs-xs); font-weight: 700; white-space: nowrap;">Продавец:</label>
          <select id="searchSellerSelect" class="search-input" style="height: 36px; border: 1px solid var(--c-line); padding: 0 10px; font-size: var(--fs-sm); border-radius: var(--r-sm); background: var(--c-bg);">
            <option value="">Все площадки</option>
            <option value="marketplace">Маркетплейсы</option>
            <option value="crossborder">Из Китая (AliExpress)</option>
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
// Search results controller is driven by ES module public/assets/js/search.js.
// Fallback starter ensures instant initialization if module execution order varies:
(function() {
  function tryInitSearch() {
    if (window.PriceHubSearchResultsInitialized) return;
    if (typeof window.PriceHubInitSearchResults === 'function') {
      window.PriceHubInitSearchResults();
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tryInitSearch);
  } else {
    tryInitSearch();
  }
})();
</script>

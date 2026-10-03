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
    <button type="button" class="btn btn--secondary btn--sm" id="openFiltersBtn" style="display: none;">
      <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#filter"></use></svg>
      <span>Фильтры</span>
    </button>
  </div>

  <div class="catalog-layout">
    <!-- Filter Sidebar -->
    <aside class="filters-sidebar" id="filterSidebar">
      <div class="d-flex justify-between align-center mb-4" id="filterHeaderMobile">
        <h3 class="font-bold">Фильтры</h3>
        <a href="?" class="text-muted" style="font-size: var(--fs-xs);">Сбросить все</a>
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
      <div class="product-grid" id="catalogProducts">
        <?php foreach ($items as $p): ?>
          <?= $view->partial('partials/product_card', ['p' => $p]) ?>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="d-flex justify-between align-center mt-4" style="padding-top: var(--sp-4); border-top: 1px solid var(--c-line);">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&sort=<?= e($currentSort) ?>&brand=<?= e($selectedBrand) ?>" class="btn btn--secondary btn--sm">&larr; Назад</a>
          <?php else: ?>
            <div></div>
          <?php endif; ?>

          <span class="text-muted" style="font-size: var(--fs-sm);">
            Страница <strong><?= $page ?></strong> из <?= $totalPages ?>
          </span>

          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&sort=<?= e($currentSort) ?>&brand=<?= e($selectedBrand) ?>" class="btn btn--secondary btn--sm">Вперед &rarr;</a>
          <?php else: ?>
            <div></div>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </main>
  </div>
</div>

<?php
/**
 * Mega Menu Partial - Organized by Parent Categories with Tab Switching
 */
declare(strict_types=1);

$categories = \App\Storage\Snapshot::loadArray('categories.php', []);
if (empty($categories)) {
    $categories = require __DIR__ . '/../../../config/categories.php';
}

$parentCategories = [];
$childrenByParent = [];

foreach ($categories as $cId => $cat) {
    if ($cat['parent_id'] === null) {
        $parentCategories[$cId] = $cat;
        if (!isset($childrenByParent[$cId])) {
            $childrenByParent[$cId] = [];
        }
    } else {
        $pId = $cat['parent_id'];
        $childrenByParent[$pId][$cId] = $cat;
    }
}

$firstParentId = !empty($parentCategories) ? array_key_first($parentCategories) : 1;
?>

<div class="mega-menu__layout">
  <!-- Parent Categories Sidebar -->
  <aside class="mega-menu__cats" role="tablist" aria-label="Разделы каталога">
    <?php foreach ($parentCategories as $pId => $pCat): 
      $isActive = ($pId === $firstParentId);
    ?>
      <button type="button" 
              class="mega-menu__cat-item <?= $isActive ? 'is-active' : '' ?>" 
              role="tab" 
              aria-selected="<?= $isActive ? 'true' : 'false' ?>"
              aria-controls="megaGroup-<?= $pId ?>"
              id="megaTab-<?= $pId ?>"
              data-parent-id="<?= $pId ?>">
        <span class="d-flex align-center gap-2">
          <svg class="icon icon-sm" aria-hidden="true"><use href="/assets/icons/sprite.svg#<?= e($pCat['icon'] ?? 'grid') ?>"></use></svg>
          <span class="font-bold"><?= e($pCat['name']) ?></span>
        </span>
        <svg class="icon icon-xs text-muted" aria-hidden="true"><use href="/assets/icons/sprite.svg#chevron-right"></use></svg>
      </button>
    <?php endforeach; ?>
  </aside>

  <!-- Subcategory Content Groups -->
  <div class="mega-menu__content" id="megaMenuContentPanels">
    <?php foreach ($parentCategories as $pId => $pCat): 
      $isActive = ($pId === $firstParentId);
      $subcats = $childrenByParent[$pId] ?? [];
    ?>
      <div class="mega-menu__group <?= $isActive ? 'is-active' : '' ?>" 
           id="megaGroup-<?= $pId ?>" 
           role="tabpanel" 
           aria-labelledby="megaTab-<?= $pId ?>"
           data-parent-id="<?= $pId ?>" 
           <?= $isActive ? '' : 'hidden' ?>>
        
        <div class="mega-menu__group-header">
          <h3 class="font-bold" style="font-size: var(--fs-lg); color: var(--c-ink);">
            <?= e($pCat['name']) ?>
          </h3>
          <a href="/catalog/<?= e($pCat['slug']) ?>" class="text-accent font-bold" style="font-size: var(--fs-sm);">
            Все товары раздела &rarr;
          </a>
        </div>

        <div class="mega-menu__subcats-grid">
          <?php foreach ($subcats as $cId => $sub): ?>
            <div class="subcat-card">
              <a href="/catalog/<?= e($sub['slug']) ?>" class="subcat-card__header">
                <div class="subcat-card__icon-wrap">
                  <svg class="icon icon-md" aria-hidden="true"><use href="/assets/icons/sprite.svg#<?= e($sub['icon'] ?? 'box') ?>"></use></svg>
                </div>
                <div class="subcat-card__info">
                  <h4 class="subcat-card__title"><?= e($sub['name']) ?></h4>
                  <span class="subcat-card__count"><?= (int)($sub['count'] ?? 0) ?> товаров</span>
                </div>
              </a>
              <ul class="subcat-card__links">
                <li><a href="/catalog/<?= e($sub['slug']) ?>?sort=drop">🔥 Со скидкой</a></li>
                <li><a href="/catalog/<?= e($sub['slug']) ?>?sort=price_asc">Недорогие</a></li>
                <li><a href="/catalog/<?= e($sub['slug']) ?>?sort=popular">Популярные</a></li>
              </ul>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

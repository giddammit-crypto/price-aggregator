<?php
/**
 * Mega Menu Partial
 */
declare(strict_types=1);

$categories = \App\Storage\Snapshot::loadArray('categories.php', []);
?>
<div class="mega-menu__cats">
  <?php foreach ($categories as $cId => $cat): 
    if ($cat['parent_id'] !== null) continue;
  ?>
    <div class="mega-menu__cat-item" data-cat-id="<?= $cId ?>">
      <span><?= e($cat['name']) ?></span>
      <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#chevron-right"></use></svg>
    </div>
  <?php endforeach; ?>
</div>

<div class="mega-menu__subcats" id="megaSubcats">
  <?php foreach ($categories as $cId => $cat): 
    if ($cat['parent_id'] === null) continue;
  ?>
    <div class="subcat-col">
      <a href="/catalog/<?= e($cat['slug']) ?>" class="subcat-col__title">
        <?= e($cat['name']) ?>
      </a>
      <p class="text-muted" style="font-size: var(--fs-xs); margin-bottom: 8px;">
        <?= (int)($cat['count'] ?? 0) ?> товаров
      </p>
      <ul class="subcat-col__list">
        <li><a href="/catalog/<?= e($cat['slug']) ?>?sort=price_asc">Недорогие</a></li>
        <li><a href="/catalog/<?= e($cat['slug']) ?>?sort=drop">С хорошей скидкой</a></li>
        <li><a href="/catalog/<?= e($cat['slug']) ?>?sort=popular">Популярные</a></li>
      </ul>
    </div>
  <?php endforeach; ?>
</div>

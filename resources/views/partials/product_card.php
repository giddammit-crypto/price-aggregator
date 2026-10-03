<?php
/**
 * Product Card Partial
 * @var array $p [id, b, p, c, d, pop, t, img, slug, attrs, mp, cb]
 */
declare(strict_types=1);

$id = (int)$p['id'];
$title = $p['t'];
$slug = $p['slug'];
$brand = $p['b'];
$minPrice = (int)$p['p'];
$offersCnt = (int)$p['c'];
$drop = (float)($p['d'] ?? 0.0);
$img = $p['img'] ?? '/assets/img/placeholder.svg';
$attrs = $p['attrs'] ?? [];
$isMp = !empty($p['mp']);
$isCb = !empty($p['cb']);
$url = "/p/{$slug}-{$id}";
?>
<article class="product-card" data-product-id="<?= $id ?>">
  <div class="product-card__badges">
    <?php if ($drop >= 8.0): ?>
      <span class="badge badge--drop">−<?= round($drop) ?>%</span>
    <?php endif; ?>
    <?php if ($offersCnt >= 4): ?>
      <span class="badge badge--best">Выгода</span>
    <?php endif; ?>
    <?php if ($isCb): ?>
      <span class="badge badge--crossborder">Из Китая</span>
    <?php elseif ($isMp): ?>
      <span class="badge badge--marketplace">Маркетплейс</span>
    <?php endif; ?>
  </div>

  <button type="button" class="product-card__fav" data-fav-id="<?= $id ?>" title="В избранное" aria-label="Добавить в избранное">
    <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#heart"></use></svg>
  </button>

  <a href="<?= e($url) ?>" class="product-card__img-wrap" tabindex="-1">
    <img src="<?= e($img) ?>" alt="<?= e($title) ?>" class="product-card__img" loading="lazy" width="180" height="180">
  </a>

  <div class="product-card__brand"><?= e($brand) ?></div>
  <a href="<?= e($url) ?>" class="product-card__title" title="<?= e($title) ?>">
    <?= e($title) ?>
  </a>

  <div class="product-card__specs">
    <?php 
    $specCount = 0;
    foreach ($attrs as $k => $v): 
      if ($k === 'brand' || $specCount >= 3) continue;
      $specCount++;
    ?>
      <div class="product-card__spec-item">
        <span><?= e((string)$k) ?>:</span>
        <strong><?= e((string)$v) ?></strong>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="product-card__footer">
    <div class="product-card__price-wrap">
      <span class="product-card__price-label">от</span>
      <span class="product-card__price"><?= formatPrice($minPrice) ?></span>
      <span class="product-card__shops-cnt">
        <?= $offersCnt ?> <?= ($offersCnt === 1 ? 'предложение' : ($offersCnt < 5 ? 'предложения' : 'предложений')) ?>
      </span>
    </div>

    <button type="button" class="btn btn--sm btn--secondary" data-compare-id="<?= $id ?>" title="Сравнить" aria-label="Сравнить товар">
      <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#scale"></use></svg>
    </button>
  </div>
</article>

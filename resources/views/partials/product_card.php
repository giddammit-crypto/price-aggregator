<?php
/**
 * Product Card Partial
 * @var array $p [id, b, p, c, d, pop, t, img, slug, attrs, mp, cb]
 */
declare(strict_types=1);

$id = (int)($p['id'] ?? 0);
// Snapshot rows can outlive the published product. Resolve the target by ID,
// exactly as the product route does, before offering navigation or actions.
$product = $id > 0 ? \App\Storage\Pack::get($id) : null;
$published = is_array($product)
    && (int)($product['id'] ?? 0) === $id
    && !empty($product['pub'])
    && !empty($product['slug'])
    && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)$product['slug']) === 1;
$title = (string)($product['title'] ?? $p['t'] ?? 'Товар');
$brand = (string)($product['brand'] ?? $p['b'] ?? '');
$minPrice = $published ? (int)($product['agg']['min'] ?? 0) : 0;
$offersCnt = $published ? (int)($product['agg']['cnt'] ?? count($product['offers'] ?? [])) : 0;
$drop = $published ? (float)($product['agg']['drop'] ?? 0) : 0;
$attrs = $published ? ($product['attrs'] ?? []) : [];
$isMp = $published && !empty($product['agg']['mp']);
$isCb = $published && !empty($product['agg']['cb']);
$url = $published ? "/p/{$product['slug']}-{$id}/" : null;
$verifiedImage = $published ? \App\Services\VerifiedProductImage::forProduct($product) : null;
$productImg = $verifiedImage ?? $product['img'] ?? $p['img'] ?? '/assets/img/placeholder.svg';
$pop = (int)($p['pop'] ?? 0);
$seller = $isCb ? 'crossborder' : ($isMp ? 'marketplace' : 'retail');
$oldPrice = ($drop >= 5.0 && $minPrice > 0) ? (int)round($minPrice / (1 - ($drop / 100.0))) : 0;
?>
<article class="product-card" data-product-id="<?= $id ?>" data-price="<?= $minPrice ?>" data-drop="<?= $drop ?>" data-pop="<?= $pop ?>" data-brand="<?= e(mb_strtolower((string)$brand)) ?>" data-seller="<?= $seller ?>" data-mp="<?= $isMp ? 1 : 0 ?>" data-cb="<?= $isCb ? 1 : 0 ?>">
  <div class="product-card__badges">
    <?php if ($drop >= 8.0): ?>
      <span class="badge badge--drop">−<?= round($drop) ?>%</span>
    <?php endif; ?>
    <?php if ($isCb): ?>
      <span class="badge badge--crossborder">Из Китая</span>
    <?php elseif ($isMp): ?>
      <span class="badge badge--marketplace">Маркетплейс</span>
    <?php endif; ?>
  </div>

  <?php if ($published): ?>
    <button type="button" class="product-card__fav" data-fav-id="<?= $id ?>" title="В избранное" aria-label="Добавить в избранное">
      <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#heart"></use></svg>
    </button>
  <?php endif; ?>

  <?php if ($published): ?><a href="<?= e($url) ?>" class="product-card__img-wrap" tabindex="-1"><?php else: ?><div class="product-card__img-wrap"><?php endif; ?>
    <img src="<?= e($productImg) ?>" alt="<?= e('Фото ' . $title) ?>" class="product-card__img" loading="lazy" width="180" height="180" onerror="this.onerror=null; this.src='/assets/img/p/<?= (int)($p['cat'] ?? 10) ?>.svg';">
  <?php if ($published): ?></a><?php else: ?></div><?php endif; ?>

  <div class="product-card__brand"><?= e($brand) ?></div>
  <?php if ($published): ?><a href="<?= e($url) ?>" class="product-card__title" title="<?= e($title) ?>"><?php else: ?><span class="product-card__title"><?php endif; ?>
    <?= e($title) ?>
  <?php if ($published): ?></a><?php else: ?></span><?php endif; ?>

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
      <?php if ($published && $minPrice > 0 && $offersCnt > 0): ?>
        <div class="product-card__price-row">
          <span class="product-card__price-label">от</span>
          <span class="product-card__price"><?= formatPrice($minPrice) ?></span>
          <?php if ($oldPrice > $minPrice): ?>
            <span class="product-card__old-price" title="Старая цена"><?= formatPrice($oldPrice) ?></span>
          <?php endif; ?>
        </div>
        <span class="product-card__shops-cnt">
          <?= $offersCnt ?> <?= ($offersCnt === 1 ? 'предложение' : ($offersCnt < 5 ? 'предложения' : 'предложений')) ?>
        </span>
      <?php else: ?>
        <span class="text-muted"><?= $published ? 'Подтверждённых цен пока нет' : 'Товар временно недоступен' ?></span>
      <?php endif; ?>
    </div>

    <?php if ($published): ?>
      <button type="button" class="btn btn--sm btn--secondary" data-compare-id="<?= $id ?>" title="Сравнить" aria-label="Сравнить товар">
        <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#scale"></use></svg>
      </button>
    <?php endif; ?>
  </div>
</article>

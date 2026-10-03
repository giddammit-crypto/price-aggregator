<?php
/**
 * Offer Row Partial
 * @var array $offer
 * @var int $productId
 * @var bool $isBest
 */
declare(strict_types=1);

$shops = \App\Core\Config::get('shops', []);
$shopId = $offer['shop'];
$shopDef = $shops[$shopId] ?? ['name' => ucfirst($shopId), 'color' => '#666', 'mode' => 'prices'];

$isLinkOnly = ($offer['mode'] ?? 'prices') === 'link_only' || ($shopDef['mode'] ?? '') === 'link_only';
$seller = $offer['seller'] ?? null;
$price = $offer['price'] ?? null;
$landed = $offer['landed'] ?? $price;
$origin = $offer['origin'] ?? 'RU';
$isBestOffer = !empty($isBest);
$offerKey = urlencode($offer['k']);
$goUrl = $isLinkOnly ? "/go/search/{$shopId}?q=" . urlencode($productTitle ?? '') : "/go/{$productId}/{$offerKey}";
?>
<div class="offer-row <?= $isBestOffer ? 'offer-row--best' : '' ?>">
  <div class="offer-shop">
    <div class="offer-shop__name" style="color: <?= e($shopDef['color'] ?? 'inherit') ?>;">
      <?= e($shopDef['name']) ?>
    </div>
    <span class="badge badge--marketplace"><?= e($shopDef['badge'] ?? 'Магазин') ?></span>
  </div>

  <div class="offer-seller">
    <?php if ($seller): ?>
      <span class="font-bold"><?= e($seller['name']) ?></span>
      <?php if (!empty($seller['rating'])): ?>
        <span class="offer-seller__rating">
          <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#star"></use></svg>
          <?= number_format((float)$seller['rating'], 1) ?> (<?= (int)$seller['n'] ?>)
        </span>
      <?php endif; ?>
      <?php if (!empty($seller['official'])): ?>
        <span class="badge badge--official">Официальный</span>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($origin === 'CN'): ?>
      <span class="badge badge--crossborder">Прямая доставка из Китая</span>
    <?php endif; ?>

    <?php if (!empty($offer['note'])): ?>
      <span class="badge badge--warn"><?= e($offer['note']) ?></span>
    <?php endif; ?>
  </div>

  <div class="offer-price-col">
    <?php if ($isLinkOnly): ?>
      <span class="text-muted" style="font-size: var(--fs-xs);">Цена на сайте</span>
    <?php else: ?>
      <div class="offer-price"><?= formatPrice($landed) ?></div>
      <?php if ($landed !== $price): ?>
        <div class="offer-price-note">с доставкой</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div>
    <?php if ($isLinkOnly): ?>
      <a href="<?= e($goUrl) ?>" target="_blank" rel="sponsored nofollow noopener" class="btn btn--outline btn--sm">
        Искать на <?= e($shopDef['name']) ?>
        <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#arrow-up-right"></use></svg>
      </a>
    <?php else: ?>
      <a href="<?= e($goUrl) ?>" target="_blank" rel="sponsored nofollow noopener" class="btn btn--accent btn--sm">
        В магазин
        <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#arrow-up-right"></use></svg>
      </a>
    <?php endif; ?>
  </div>
</div>

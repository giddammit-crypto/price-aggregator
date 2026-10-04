<?php
/**
 * Offer Row Partial
 * @var array $offer
 * @var int $productId
 * @var bool $isBest
 * @var float|null $productDrop
 */
declare(strict_types=1);

$shops = \App\Core\Config::get('shops', []);
$shopId = (string)($offer['shop'] ?? '');
$shopDef = $shops[$shopId] ?? ['name' => ucfirst($shopId), 'color' => '#666', 'mode' => 'prices'];

// Distinctive shop abbreviation for logo badge
$shopAbbrs = [
    'wildberries' => 'WB',
    'ozon' => 'OZON',
    'dns' => 'DNS',
    'citilink' => 'CITI',
    'mvideo' => 'М.В',
    'yandex_market' => 'Я.М',
    'megamarket' => 'ММ',
    'regard' => 'REG',
    'onlinetrade' => 'ОТ',
    'aliexpress' => 'ALI',
    'joom' => 'JM',
    'avito' => 'АВ',
];
$shopAbbr = $shopAbbrs[$shopId] ?? mb_strtoupper(mb_substr((string)$shopDef['name'], 0, 3));

$isLinkOnly = ($offer['mode'] ?? 'prices') === 'link_only' || ($shopDef['mode'] ?? '') === 'link_only';
$seller = $offer['seller'] ?? null;
$price = $offer['price'] ?? null;
$landed = $offer['landed'] ?? $price;
$origin = $offer['origin'] ?? 'RU';
$offerKey = urlencode((string)($offer['k'] ?? ''));
$isBestOffer = !empty($isBest);
$donorUrl = !empty($offer['url']) ? $offer['url'] : '';
$cleanModelQuery = \App\Services\DonorUrlHelper::cleanModelQuery($productTitle ?? '');
$goUrl = $isLinkOnly ? "/go/search/{$shopId}?q=" . urlencode($cleanModelQuery) : "/go/{$productId}/{$offerKey}";
$directUrl = is_string($donorUrl) && preg_match('~^https?://~i', $donorUrl) ? $donorUrl : $goUrl;
$searchOnly = $isLinkOnly || (parse_url($directUrl, PHP_URL_QUERY) !== null
    && (bool)preg_match('~/(?:search|sitesearch|listing|catalog)(?:/|\.html|$)~i', (string)parse_url($directUrl, PHP_URL_PATH)));

// Offer note and discount badge categorization
$note = trim((string)($offer['note'] ?? ''));
$isWallet = str_contains($note, 'WB Кошельк');
$isOzonCard = str_contains($note, 'Ozon Карт');
$isYandexPlus = str_contains($note, 'Яндекс Плюс') || str_contains($note, 'Плюс');
$isBonus = str_contains($note, 'бонус') || str_contains($note, 'Бонус') || str_contains($note, 'Клубная') || str_contains($note, 'Кэшбэк');
$isWarranty = str_contains($note, 'Гарантия');

// Calculate old / regular price for visual strikethrough
$offerOldPrice = null;
if (!empty($offer['old_price']) && (int)$offer['old_price'] > (int)$landed) {
    $offerOldPrice = (int)$offer['old_price'];
} elseif ($isWallet && $landed > 0) {
    $offerOldPrice = (int)round(($landed / 0.94) / 10) * 10;
} elseif ($isOzonCard && $landed > 0) {
    $offerOldPrice = (int)round(($landed / 0.95) / 10) * 10;
} elseif (($isYandexPlus || $isBonus) && $landed > 0) {
    $offerOldPrice = (int)round(($landed / 0.96) / 10) * 10;
} elseif ($isBestOffer && !empty($productDrop) && $productDrop >= 5.0 && $landed > 0) {
    $offerOldPrice = (int)round($landed / (1 - ($productDrop / 100.0)));
}
?>
<div class="offer-row <?= $isBestOffer ? 'offer-row--best' : '' ?>">
  <div class="offer-shop">
    <div class="offer-shop__header">
      <span class="offer-shop__logo offer-shop__logo--<?= e($shopId) ?>" style="background-color: <?= e($shopDef['color'] ?? '#666') ?>;" title="<?= e($shopDef['name']) ?>" aria-hidden="true">
        <?= e($shopAbbr) ?>
      </span>
      <div class="offer-shop__meta">
        <div class="offer-shop__name shop-name--<?= e($shopId) ?>" style="color: <?= e($shopDef['color'] ?? 'inherit') ?>;">
          <?= e($shopDef['name']) ?>
        </div>
        <span class="badge badge--shop-kind"><?= e($shopDef['badge'] ?? 'Магазин') ?></span>
      </div>
    </div>
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

    <?php if ($isWarranty): ?>
      <span class="badge badge--warranty">
        <svg class="icon icon-xs" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
        <?= e($note) ?>
      </span>
    <?php elseif (!empty($note) && !$isWallet && !$isOzonCard && !$isYandexPlus && !$isBonus): ?>
      <span class="badge badge--warn"><?= e($note) ?></span>
    <?php endif; ?>
  </div>

  <div class="offer-price-col">
    <?php if ($offerOldPrice !== null && $offerOldPrice > $landed): ?>
      <span class="offer-price__old" title="Цена без карты или скидки"><?= formatPrice($offerOldPrice) ?></span>
    <?php endif; ?>

    <?php if ($landed !== null && $landed > 0): ?>
      <div class="offer-price <?= $isBestOffer ? 'offer-price--best' : '' ?>">
        <?= formatPrice($landed) ?>
      </div>
      <?php if ($landed !== $price && $price !== null): ?>
        <div class="offer-price-note">с доставкой</div>
      <?php endif; ?>
    <?php else: ?>
      <span class="text-muted" style="font-size: var(--fs-xs);">Уточняйте на сайте</span>
    <?php endif; ?>

    <div class="offer-price__badges">
      <?php if ($isBestOffer): ?>
        <span class="badge badge--best">Лучшая цена</span>
      <?php endif; ?>

      <?php if ($isWallet): ?>
        <span class="badge badge--wb-wallet" title="Специальная цена при оплате WB Кошельком">
          <svg class="icon icon-xs" viewBox="0 0 24 24" fill="currentColor"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
          <?= e($note) ?>
        </span>
      <?php elseif ($isOzonCard): ?>
        <span class="badge badge--ozon-card" title="Специальная цена с Ozon Картой">
          <svg class="icon icon-xs" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          <?= e($note) ?>
        </span>
      <?php elseif ($isYandexPlus): ?>
        <span class="badge badge--yandex-plus" title="Кэшбэк или скидка баллами Яндекс Плюс">
          <svg class="icon icon-xs" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          <?= e($note) ?>
        </span>
      <?php elseif ($isBonus): ?>
        <span class="badge badge--bonus" title="Бонусная программа магазина">
          <svg class="icon icon-xs" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
          <?= e($note) ?>
        </span>
      <?php endif; ?>
    </div>
  </div>

  <div class="offer-action-col">
    <a href="<?= e($directUrl) ?>" target="_blank" rel="sponsored nofollow noopener" class="btn btn--accent btn--sm btn--shop" aria-label="Перейти в магазин <?= e($shopDef['name']) ?>">
      <span>В магазин</span>
      <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#arrow-up-right"></use></svg>
    </a>
  </div>
</div>

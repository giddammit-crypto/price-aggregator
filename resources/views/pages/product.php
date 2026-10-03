<?php
/**
 * Product Detail Page View
 * @var array $product
 * @var array $category
 * @var array $offers
 * @var array|null $bestOffer
 * @var array $groupedOffers
 * @var array $historyPoints
 * @var array $similar
 * @var App\Core\View $view
 */
declare(strict_types=1);

$id = (int)$product['id'];
$title = $product['title'];
$brand = $product['brand'];
$minPrice = $product['agg']['min'] ?? 0;
$maxPrice = $product['agg']['max'] ?? $minPrice;
$shopsCnt = $product['agg']['shops'] ?? 1;
$offersCnt = $product['agg']['cnt'] ?? count($offers);
$img = $product['img'] ?? '/assets/img/placeholder.svg';
$canonicalUrl = \App\Core\Config::get('app.url') . "/p/{$product['slug']}-{$id}";
?>

<!-- Schema.org Microdata -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": <?= json_encode($title, JSON_UNESCAPED_UNICODE) ?>,
  "image": [<?= json_encode($img) ?>],
  "description": <?= json_encode("Сравнение цен на {$title} в магазинах РФ и маркетплейсах.", JSON_UNESCAPED_UNICODE) ?>,
  "brand": {
    "@type": "Brand",
    "name": <?= json_encode($brand, JSON_UNESCAPED_UNICODE) ?>
  },
  "offers": {
    "@type": "AggregateOffer",
    "url": <?= json_encode($canonicalUrl) ?>,
    "priceCurrency": "RUB",
    "lowPrice": <?= (int)$minPrice ?>,
    "highPrice": <?= (int)$maxPrice ?>,
    "offerCount": <?= (int)$offersCnt ?>
  }
}
</script>

<div class="container">
  <!-- Breadcrumbs & Product Pager -->
  <div class="d-flex justify-between align-center flex-wrap gap-2 mb-4">
    <nav class="text-muted" style="font-size: var(--fs-xs);" aria-label="Хлебные крошки">
      <a href="/">Главная</a> &rarr; 
      <a href="/catalog/<?= e($category['slug']) ?>"><?= e($category['name']) ?></a> &rarr; 
      <span class="font-bold" style="color: var(--c-ink);"><?= e($brand) ?></span> &rarr; 
      <span><?= e($title) ?></span>
    </nav>

    <!-- Product Pager (навигация по товарам категории) -->
    <div class="product-pager d-flex align-center gap-2">
      <?php if (!empty($prevProduct)): ?>
        <a href="/p/<?= e($prevProduct['slug']) ?>-<?= $prevProduct['id'] ?>" class="btn btn--secondary btn--sm" title="<?= e($prevProduct['title']) ?>">
          <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#chevron-right" style="transform: rotate(180deg);"></use></svg>
          <span>Предыдущий товар</span>
        </a>
      <?php endif; ?>
      <?php if (!empty($nextProduct)): ?>
        <a href="/p/<?= e($nextProduct['slug']) ?>-<?= $nextProduct['id'] ?>" class="btn btn--secondary btn--sm" title="<?= e($nextProduct['title']) ?>">
          <span>Следующий товар</span>
          <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#chevron-right"></use></svg>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Product Hero: Image + Quick Info Box -->
  <div style="display: grid; grid-template-columns: minmax(300px, 440px) 1fr; gap: var(--sp-6); margin-bottom: var(--sp-8); background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: var(--sp-6);">
    <!-- Gallery Area -->
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
      <?php if (!empty($product['agg']['drop']) && $product['agg']['drop'] >= 8.0): ?>
        <span class="badge badge--drop" style="position: absolute; top: 0; left: 0; font-size: var(--fs-sm);">
          −<?= round($product['agg']['drop']) ?>% Скидка
        </span>
      <?php endif; ?>

      <img src="<?= e($img) ?>" alt="<?= e($title) ?>" style="max-height: 320px; object-fit: contain; width: 100%;" width="320" height="320">
    </div>

    <!-- Product Title & Buy Box -->
    <div style="display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div class="product-card__brand" style="font-size: var(--fs-sm); margin-bottom: var(--sp-1);"><?= e($brand) ?></div>
        <h1 style="font-size: clamp(1.4rem, 2vw, 1.8rem); font-weight: 800; line-height: 1.25; margin-bottom: var(--sp-4);">
          <?= e($title) ?>
        </h1>

        <!-- Short specs list -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--sp-2); margin-bottom: var(--sp-6); font-size: var(--fs-sm);">
          <?php foreach (array_slice($product['specs'] ?? [], 0, 4) as $k => $v): ?>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--c-line); padding-bottom: 4px;">
              <span class="text-muted"><?= e((string)$k) ?>:</span>
              <span class="font-bold"><?= e((string)$v) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Best Price CTA Box -->
      <div style="background: var(--c-bg); border-radius: var(--r-md); padding: var(--sp-4); border: 1px solid var(--c-line);">
        <div class="d-flex justify-between align-center flex-wrap gap-2 mb-4">
          <div>
            <span class="text-muted" style="font-size: var(--fs-xs);">Лучшая цена в магазинах:</span>
            <div style="font-size: 2rem; font-weight: 900; color: var(--c-ink); letter-spacing: -0.5px;">
              <?= formatPrice($minPrice) ?>
            </div>
            <span class="text-muted" style="font-size: var(--fs-xs);">
              в <?= $shopsCnt ?> магазинах (<?= $offersCnt ?> предложений)
            </span>
          </div>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn--secondary" data-fav-id="<?= $id ?>" title="В избранное">
              <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#heart"></use></svg>
              <span>В избранное</span>
            </button>
            <button type="button" class="btn btn--secondary" data-compare-id="<?= $id ?>" title="Сравнить">
              <svg class="icon icon-sm"><use href="/assets/icons/sprite.svg#scale"></use></svg>
              <span>Сравнить</span>
            </button>
          </div>
        </div>

        <?php if ($bestOffer): ?>
          <div class="d-flex justify-between align-center" style="background: #FFF; border-radius: var(--r-sm); padding: var(--sp-3); border: 1px solid var(--c-line);">
            <div>
              <span class="badge badge--best" style="margin-bottom: 4px;">Выгоднее всего</span>
              <div class="font-bold"><?= e(ucfirst($bestOffer['shop'])) ?> &bull; <?= formatPrice($bestOffer['landed']) ?></div>
            </div>
            <?php $bestTargetUrl = !empty($bestOffer['url']) ? $bestOffer['url'] : ("/go/{$id}/" . urlencode($bestOffer['k'])); ?>
            <a href="<?= e($bestTargetUrl) ?>" target="_blank" rel="sponsored nofollow noopener" class="btn btn--accent">
              В магазин &rarr;
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Offers Table & Tabs -->
  <section style="margin-bottom: var(--sp-8);">
    <div class="d-flex justify-between align-center flex-wrap gap-4 mb-4">
      <h2 style="font-size: var(--fs-xl); font-weight: 800;">Где купить и цены</h2>

      <!-- Filter chips -->
      <div class="chip-group" id="offerFilterChips">
        <button type="button" class="chip is-active" data-filter="all">Все предложения (<?= count($offers) ?>)</button>
        <button type="button" class="chip" data-filter="retail">Розничные сети (<?= count($groupedOffers['retail']) ?>)</button>
        <button type="button" class="chip" data-filter="marketplace">Маркетплейсы (<?= count($groupedOffers['marketplace']) ?>)</button>
        <button type="button" class="chip" data-filter="crossborder">Из Китая (<?= count($groupedOffers['crossborder']) ?>)</button>
      </div>
    </div>

    <!-- Offers List -->
    <div class="offers-table">
      <?php foreach ($offers as $idx => $offer): 
        $isBest = ($bestOffer && $offer['k'] === $bestOffer['k']);
      ?>
        <div data-offer-kind="<?= e($offer['kind'] ?? 'retail') ?>" data-offer-mode="<?= e($offer['mode'] ?? 'prices') ?>">
          <?= $view->partial('partials/offer_row', [
            'productId' => $id,
            'productTitle' => $title,
            'offer' => $offer,
            'isBest' => $isBest
          ]) ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Crossborder Transparency Notice -->
    <?php if (!empty($groupedOffers['crossborder'])): ?>
      <div style="margin-top: var(--sp-3); padding: var(--sp-3); background: #EBF3FE; border: 1px solid #BFDBFE; border-radius: var(--r-md); font-size: var(--fs-xs); color: #1E3A8A;">
        <strong>О покупках из Китая (AliExpress):</strong> Цены пересчитаны по официальному курсу ЦБ РФ на сегодня. Доставка осуществляется международными перевозчиками (10–25 дней). Беспошлинный лимит ввоза для личного пользования составляет 200 € (на дату отправления). Гарантия предоставляется продавцом площадки.
      </div>
    <?php endif; ?>
  </section>

  <!-- Price History Section (SVG Chart) -->
  <section class="chart-card">
    <div class="d-flex justify-between align-center mb-4 flex-wrap gap-2">
      <div>
        <h2 style="font-size: var(--fs-lg); font-weight: 800;">Динамика минимальной цены (30–90 дней)</h2>
        <p class="text-muted" style="font-size: var(--fs-xs);">График фиксирует минимальную подтвержденную цену на товар среди всех магазинов.</p>
      </div>
      <span class="badge badge--best">В наличии</span>
    </div>

    <!-- SVG Price Line Chart -->
    <div style="width: 100%; height: 180px; position: relative;">
      <?php
      $points = $historyPoints;
      $minVal = !empty($points) ? min(array_column($points, 'min')) : $minPrice;
      $maxVal = !empty($points) ? max(array_column($points, 'min')) : $minPrice;
      $valRange = max(1, $maxVal - $minVal);

      $svgWidth = 800;
      $svgHeight = 140;
      $polylineCoords = [];

      $pCount = count($points);
      foreach ($points as $idx => $pt) {
          $x = ($pCount > 1) ? round(($idx / ($pCount - 1)) * ($svgWidth - 60) + 30) : ($svgWidth / 2);
          $y = round($svgHeight - 20 - ((($pt['min'] - $minVal) / $valRange) * ($svgHeight - 50)));
          $polylineCoords[] = "{$x},{$y}";
      }
      $coordsStr = implode(' ', $polylineCoords);
      ?>

      <svg viewBox="0 0 <?= $svgWidth ?> <?= $svgHeight ?>" width="100%" height="100%" preserveAspectRatio="none">
        <!-- Horizontal grid lines -->
        <line x1="30" y1="20" x2="<?= $svgWidth - 30 ?>" y2="20" stroke="#E4E7EB" stroke-dasharray="4"/>
        <line x1="30" y1="<?= $svgHeight - 20 ?>" x2="<?= $svgWidth - 30 ?>" y2="<?= $svgHeight - 20 ?>" stroke="#E4E7EB"/>
        
        <!-- Polyline curve -->
        <polyline points="<?= $coordsStr ?>" fill="none" stroke="var(--c-accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>

        <!-- Dots -->
        <?php foreach ($points as $idx => $pt): 
            $x = ($pCount > 1) ? round(($idx / ($pCount - 1)) * ($svgWidth - 60) + 30) : ($svgWidth / 2);
            $y = round($svgHeight - 20 - ((($pt['min'] - $minVal) / $valRange) * ($svgHeight - 50)));
        ?>
          <circle cx="<?= $x ?>" cy="<?= $y ?>" r="5" fill="#FFF" stroke="var(--c-accent)" stroke-width="2"/>
          <text x="<?= $x ?>" y="<?= $y - 10 ?>" font-size="11" font-weight="bold" fill="var(--c-ink)" text-anchor="middle"><?= number_format((float)$pt['min'], 0, '', ' ') ?> ₽</text>
          <text x="<?= $x ?>" y="<?= $svgHeight - 4 ?>" font-size="10" fill="var(--c-ink-3)" text-anchor="middle"><?= date('d.m', strtotime($pt['date'])) ?></text>
        <?php endforeach; ?>
      </svg>
    </div>
  </section>

  <!-- Technical Specifications -->
  <section style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: var(--sp-6); margin-bottom: var(--sp-8);">
    <h2 style="font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-4);">Характеристики</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: var(--sp-3);">
      <?php foreach ($product['specs'] ?? [] as $specName => $specVal): ?>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--c-bg); padding: 8px 0; font-size: var(--fs-sm);">
          <span class="text-muted"><?= e((string)$specName) ?></span>
          <strong style="text-align: right;"><?= e((string)$specVal) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Similar Products Carousel/Grid -->
  <?php if (!empty($similar)): ?>
    <section style="margin-bottom: var(--sp-8);">
      <h2 style="font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-4);">Похожие модели</h2>
      <div class="product-grid">
        <?php foreach ($similar as $simItem): ?>
          <?= $view->partial('partials/product_card', ['p' => $simItem]) ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<script>
// Interactive offer filter chips on product page
document.addEventListener('DOMContentLoaded', () => {
  const chips = document.querySelectorAll('#offerFilterChips .chip');
  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('is-active'));
      chip.classList.add('is-active');
      const filter = chip.dataset.filter;

      document.querySelectorAll('.offers-table [data-offer-kind]').forEach(row => {
        const kind = row.dataset.offerKind;
        if (filter === 'all' || kind === filter) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });
});
</script>

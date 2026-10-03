<?php
/**
 * Home Page View with Google Stitch Hero Bento Grid
 * @var array $drops
 * @var array $popular
 * @var array $newest
 * @var array $categories
 * @var array $topBrands
 * @var App\Core\View $view
 */
declare(strict_types=1);

$featuredDrop = !empty($drops) ? $drops[0] : null;
?>

<div class="container">
  <!-- Hero & Aggregator Spotlight Bento Grid (Stitch Design) -->
  <section style="margin-bottom: var(--sp-8);">
    <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: var(--sp-4);">
      <!-- Main High-Impact Banner (8 cols) -->
      <div style="grid-column: span 8; background: linear-gradient(135deg, #1e232d 0%, #15181e 100%); color: #FFF; border-radius: var(--r-lg); padding: var(--sp-8); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sh-2); @media (max-width: 1024px) { grid-column: span 12; }">
        <!-- Ambient Glow -->
        <div style="position: absolute; right: -80px; bottom: -80px; width: 320px; height: 320px; border-radius: 50%; background: rgba(249, 87, 0, 0.15); filter: blur(50px); pointer-events: none;"></div>

        <div style="position: relative; z-index: 2;">
          <div class="d-flex gap-2 flex-wrap mb-4">
            <span class="badge" style="background: var(--c-accent); color: #FFF;">
              🔥 Экономия до 42%
            </span>
            <span class="badge" style="background: rgba(0, 133, 91, 0.25); color: #4edea3; border: 1px solid rgba(0, 133, 91, 0.4);">
              ✓ 20 000+ товаров проверено
            </span>
            <span class="badge" style="background: rgba(255,255,255,0.1); color: #FFF;">
              Файловое хранилище (NDJSON + OPcache)
            </span>
          </div>

          <h1 style="font-family: var(--ff-head); font-size: clamp(1.8rem, 2.8vw, 2.6rem); font-weight: 900; line-height: 1.15; margin-bottom: var(--sp-4); letter-spacing: -0.02em;">
            Сравнивайте цены ритейлеров <span style="color: var(--c-accent); text-decoration: underline; text-underline-offset: 6px;">в один клик</span>
          </h1>

          <p class="text-muted" style="color: var(--c-on-dark); font-size: var(--fs-md); max-width: 580px; line-height: 1.6; margin-bottom: var(--sp-6);">
            Мониторим витрины DNS, Ситилинк, М.Видео, Ozon, Мегамаркет и AliExpress. Находим максимальные скидки, честные цены с доставкой и продавцов с высоким рейтингом.
          </p>

          <div class="d-flex gap-4 flex-wrap">
            <a href="/catalog/graphics-cards" class="btn btn--accent btn--lg">Каталог видеокарт</a>
            <a href="/catalog/smartphones" class="btn btn--secondary btn--lg">Смартфоны</a>
            <a href="/catalog/processors" class="btn btn--outline btn--lg" style="color: #FFF; border-color: rgba(255,255,255,0.3);">Процессоры</a>
          </div>
        </div>

        <!-- Retailer Price Delta Live Strip -->
        <div style="position: relative; z-index: 2; margin-top: var(--sp-6); padding: var(--sp-3) var(--sp-4); background: rgba(255,255,255,0.06); backdrop-filter: blur(8px); border-radius: var(--r-md); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; flex-wrap: gap-2;">
          <div class="d-flex align-center gap-2">
            <div style="width: 32px; height: 32px; border-radius: 6px; background: var(--c-accent); color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 16px;">
              ₽
            </div>
            <div>
              <div style="font-weight: 700; font-size: var(--fs-sm); color: #FFF;">Индекс выгоды DNS vs Маркетплейсы</div>
              <div class="text-muted" style="font-size: var(--fs-xs); color: rgba(255,255,255,0.6);">Разница на флагманах электроники до 18 400 ₽</div>
            </div>
          </div>

          <div class="d-flex align-center gap-2">
            <span style="font-size: 11px; padding: 3px 8px; border-radius: 4px; background: #FFF; color: #000; font-weight: 800;">DNS</span>
            <span style="font-size: 11px; padding: 3px 8px; border-radius: 4px; background: #FF5000; color: #FFF; font-weight: 800;">Ситилинк</span>
            <span style="font-size: 11px; padding: 3px 8px; border-radius: 4px; background: #005BFF; color: #FFF; font-weight: 800;">Ozon</span>
            <span style="font-size: 11px; padding: 3px 8px; border-radius: 4px; background: #FF4747; color: #FFF; font-weight: 800;">AliExpress</span>
          </div>
        </div>
      </div>

      <!-- Hero Side Card: Deal Spotter (4 cols) -->
      <div style="grid-column: span 4; background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-lg); padding: var(--sp-6); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sh-1); @media (max-width: 1024px) { grid-column: span 12; }">
        <div>
          <div class="d-flex justify-between align-center mb-4">
            <span class="badge badge--drop">🔥 Лучший сброс цены</span>
            <span class="text-muted" style="font-size: var(--fs-xs);">Сегодня</span>
          </div>

          <?php if ($featuredDrop): ?>
            <div style="text-align: center; margin-bottom: var(--sp-4);">
              <img src="<?= e($featuredDrop['img'] ?? '/assets/img/placeholder.svg') ?>" alt="" style="max-height: 160px; margin: 0 auto; object-fit: contain;" width="160" height="160">
            </div>

            <div class="product-card__brand" style="font-size: var(--fs-xs);"><?= e($featuredDrop['b']) ?></div>
            <a href="/p/<?= e($featuredDrop['slug']) ?>-<?= $featuredDrop['id'] ?>" class="font-bold" style="font-size: var(--fs-md); display: block; line-height: 1.3; margin-bottom: var(--sp-2);">
              <?= e($featuredDrop['t']) ?>
            </a>

            <div class="d-flex align-center gap-2 mb-4">
              <span class="badge badge--drop">−<?= round($featuredDrop['d']) ?>%</span>
              <span style="font-size: 1.5rem; font-weight: 900; color: var(--c-ink);">
                <?= formatPrice($featuredDrop['p']) ?>
              </span>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <div style="background: var(--c-bg); border-radius: var(--r-sm); padding: var(--sp-3); font-size: var(--fs-xs); color: var(--c-ink-2); margin-bottom: var(--sp-4);">
            ✓ Проверено на складах Ситилинк, Ozon, Регард.<br>
            ✓ Гарантия официального ритейлера.
          </div>
          <?php if ($featuredDrop): ?>
            <a href="/p/<?= e($featuredDrop['slug']) ?>-<?= $featuredDrop['id'] ?>" class="btn btn--accent" style="width: 100%;">
              Смотреть цены во всех магазинах &rarr;
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Popular Categories Grid -->
  <section style="margin-bottom: var(--sp-8);">
    <div class="d-flex justify-between align-center mb-4">
      <h2 style="font-family: var(--ff-head); font-size: var(--fs-xl); font-weight: 800;">Популярные категории</h2>
      <a href="/catalog/pc-components" style="color: var(--c-accent); font-weight: 600; font-size: var(--fs-sm);">Весь каталог &rarr;</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: var(--sp-4);">
      <?php foreach ($categories as $cId => $cat): 
        if ($cat['parent_id'] === null) continue;
      ?>
        <a href="/catalog/<?= e($cat['slug']) ?>" class="category-tile" style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: var(--sp-4); text-align: center; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.15s ease, box-shadow 0.15s ease;">
          <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--c-accent-50); display: flex; align-items: center; justify-content: center; color: var(--c-accent);">
            <svg class="icon icon-lg"><use href="/assets/icons/sprite.svg#<?= e($cat['icon'] ?? 'grid') ?>"></use></svg>
          </div>
          <span style="font-weight: 700; font-size: var(--fs-sm); color: var(--c-ink);"><?= e($cat['name']) ?></span>
          <span class="text-muted" style="font-size: var(--fs-xs);"><?= (int)($cat['count'] ?? 0) ?> товаров</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Price Drops Section -->
  <?php if (!empty($drops)): ?>
    <section style="margin-bottom: var(--sp-8);">
      <div class="d-flex justify-between align-center mb-4">
        <div class="d-flex align-center gap-2">
          <svg class="icon" style="color: var(--c-bad);"><use href="/assets/icons/sprite.svg#trending-down"></use></svg>
          <h2 style="font-family: var(--ff-head); font-size: var(--fs-xl); font-weight: 800;">Максимальное падение цены</h2>
        </div>
        <span class="badge badge--drop">Скидки до −25%</span>
      </div>

      <div class="product-grid">
        <?php foreach (array_slice($drops, 0, 8) as $p): ?>
          <?= $view->partial('partials/product_card', ['p' => $p]) ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Popular Products Section -->
  <?php if (!empty($popular)): ?>
    <section style="margin-bottom: var(--sp-8);">
      <div class="d-flex justify-between align-center mb-4">
        <h2 style="font-family: var(--ff-head); font-size: var(--fs-xl); font-weight: 800;">Лидеры продаж и просмотров</h2>
        <a href="/catalog/smartphones?sort=popular" style="color: var(--c-accent); font-weight: 600; font-size: var(--fs-sm);">Смотреть все &rarr;</a>
      </div>

      <div class="product-grid">
        <?php foreach (array_slice($popular, 0, 8) as $p): ?>
          <?= $view->partial('partials/product_card', ['p' => $p]) ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Trust Highlights & Marketplaces -->
  <section style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-lg); padding: var(--sp-6); margin-bottom: var(--sp-8); box-shadow: var(--sh-1);">
    <h2 style="font-family: var(--ff-head); font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-4);">Магазины и маркетплейсы в едином поиске</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--sp-6);">
      <div>
        <h3 style="font-weight: 700; margin-bottom: var(--sp-2); color: var(--c-accent);">Итоговая цена (Landed Price)</h3>
        <p class="text-muted" style="font-size: var(--fs-sm); line-height: 1.5;">Для товаров из Китая и трансграничных площадок мы автоматически пересчитываем валюту по курсу ЦБ РФ и включаем стоимость доставки.</p>
      </div>
      <div>
        <h3 style="font-weight: 700; margin-bottom: var(--sp-2); color: var(--c-ok);">Фильтры доверия продавцов</h3>
        <p class="text-muted" style="font-size: var(--fs-sm); line-height: 1.5;">Отсекаем подозрительные предложения, муляжи и продавцов с низким рейтингом (< 4.3). Метка «Официальный магазин» проверяется алгоритмом.</p>
      </div>
      <div>
        <h3 style="font-weight: 700; margin-bottom: var(--sp-2); color: var(--c-info);">Честный режим Link-Only</h3>
        <p class="text-muted" style="font-size: var(--fs-sm); line-height: 1.5;">Для площадок без открытого фида для агрегаторов мы не показываем вымышленные цены, а даем прямой переход к поиску на маркетплейсе.</p>
      </div>
    </div>
  </section>

  <!-- Price Alert Subscription Banner -->
  <section style="background: linear-gradient(135deg, var(--c-accent-50) 0%, #FFF 100%); border: 1px solid rgba(249, 87, 0, 0.3); border-radius: var(--r-lg); padding: var(--sp-6); margin-bottom: var(--sp-6);">
    <div style="max-width: 620px;">
      <h3 style="font-family: var(--ff-head); font-size: var(--fs-lg); font-weight: 800; margin-bottom: var(--sp-2);">Уведомления о снижении цены</h3>
      <p class="text-muted" style="font-size: var(--fs-sm); margin-bottom: var(--sp-4);">Подпишитесь на уведомления в карточке любого товара. Мы пришлем письмо, как только цена достигнет целевой отметки.</p>
      <form action="/api/subscribe" method="POST" class="d-flex gap-2" id="homeSubscribeForm">
        <?= csrf_field() ?>
        <input type="email" name="email" placeholder="Ваш e-mail адрес..." required style="flex: 1; padding: 10px 16px; border: 1px solid var(--c-line); border-radius: var(--r-md); background: #FFF;">
        <button type="submit" class="btn btn--accent">Подписаться</button>
      </form>
    </div>
  </section>
</div>

<div class="container">
  <div style="margin-bottom: var(--sp-6);">
    <h1 style="font-size: var(--fs-2xl); font-weight: 800; margin-bottom: var(--sp-2);">Дизайн-система & UI-Кит (DNS-Style)</h1>
    <p class="text-muted">Интерактивная витрина базовых UI-компонентов, токенов, кнопок и карточек агрегатора.</p>
  </div>

  <!-- Color Tokens -->
  <section style="margin-bottom: var(--sp-8);">
    <h2 style="font-size: var(--fs-lg); margin-bottom: var(--sp-3);">1. Цветовые токены</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: var(--sp-3);">
      <div style="background: var(--c-accent); color: #FFF; padding: 16px; border-radius: var(--r-md); font-weight: bold; text-align: center;">Accent #F26B1D</div>
      <div style="background: var(--c-dark); color: #FFF; padding: 16px; border-radius: var(--r-md); font-weight: bold; text-align: center;">Dark #24272B</div>
      <div style="background: var(--c-surface); border: 1px solid var(--c-line); padding: 16px; border-radius: var(--r-md); font-weight: bold; text-align: center;">Surface #FFF</div>
      <div style="background: var(--c-ok); color: #FFF; padding: 16px; border-radius: var(--r-md); font-weight: bold; text-align: center;">Success #1E9E57</div>
      <div style="background: var(--c-bad); color: #FFF; padding: 16px; border-radius: var(--r-md); font-weight: bold; text-align: center;">Drop #D8392B</div>
      <div style="background: var(--c-info); color: #FFF; padding: 16px; border-radius: var(--r-md); font-weight: bold; text-align: center;">China #2D7FF9</div>
    </div>
  </section>

  <!-- Buttons -->
  <section style="margin-bottom: var(--sp-8);">
    <h2 style="font-size: var(--fs-lg); margin-bottom: var(--sp-3);">2. Кнопки (Buttons)</h2>
    <div class="d-flex gap-4 flex-wrap align-center">
      <button class="btn btn--accent">Основная кнопка</button>
      <button class="btn btn--secondary">Вторичная</button>
      <button class="btn btn--outline">Контурная</button>
      <button class="btn btn--accent btn--sm">Маленькая</button>
      <button class="btn btn--accent btn--lg">Большая CTA</button>
    </div>
  </section>

  <!-- Badges -->
  <section style="margin-bottom: var(--sp-8);">
    <h2 style="font-size: var(--fs-lg); margin-bottom: var(--sp-3);">3. Бейджи и Метки статусов</h2>
    <div class="d-flex gap-2 flex-wrap">
      <span class="badge badge--drop">−15% Скидка</span>
      <span class="badge badge--best">Лучшая цена</span>
      <span class="badge badge--crossborder">Из Китая • 14 дней</span>
      <span class="badge badge--marketplace">Маркетплейс</span>
      <span class="badge badge--official">Официальный магазин</span>
      <span class="badge badge--warn">с картой Ozon</span>
    </div>
  </section>

  <!-- Product Card Demo -->
  <section style="margin-bottom: var(--sp-8);">
    <h2 style="font-size: var(--fs-lg); margin-bottom: var(--sp-3);">4. Карточка товара в каталоге</h2>
    <div style="max-width: 280px;">
      <?= $view->partial('partials/product_card', [
        'p' => [
          'id' => 14001,
          't' => 'Видеокарта ASUS ROG Strix GeForce RTX 4070 SUPER OC 12GB',
          'b' => 'ASUS',
          'slug' => 'asus-rog-strix-geforce-rtx-4070-super-oc-12gb',
          'p' => 64990,
          'c' => 5,
          'd' => 12.5,
          'pop' => 999,
          'img' => '/assets/img/p/14.svg',
          'attrs' => ['Видеочип' => 'RTX 4070 SUPER', 'Память' => '12 ГБ', 'Шина' => '192-bit'],
          'mp' => 1,
          'cb' => 1
        ]
      ]) ?>
    </div>
  </section>

  <!-- Offer Row Demo -->
  <section style="margin-bottom: var(--sp-8);">
    <h2 style="font-size: var(--fs-lg); margin-bottom: var(--sp-3);">5. Строки предложений магазинов</h2>
    <div class="offers-table">
      <?= $view->partial('partials/offer_row', [
        'productId' => 14001,
        'productTitle' => 'Видеокарта ASUS ROG Strix GeForce RTX 4070 SUPER',
        'isBest' => true,
        'offer' => [
          'k' => 'citilink:14001_0',
          'shop' => 'citilink',
          'price' => 64990,
          'landed' => 64990,
          'origin' => 'RU',
          'note' => 'Гарантия 36 мес.',
          'seller' => ['name' => 'Ситилинк Склад', 'rating' => 4.9, 'n' => 14200, 'official' => 1]
        ]
      ]) ?>

      <?= $view->partial('partials/offer_row', [
        'productId' => 14001,
        'productTitle' => 'Видеокарта ASUS ROG Strix GeForce RTX 4070 SUPER',
        'isBest' => false,
        'offer' => [
          'k' => 'ozon:14001_1',
          'shop' => 'ozon',
          'price' => 66500,
          'landed' => 66500,
          'origin' => 'RU',
          'note' => 'с Ozon Картой',
          'seller' => ['name' => 'ASUS Official Store', 'rating' => 4.8, 'n' => 2300, 'official' => 1]
        ]
      ]) ?>

      <?= $view->partial('partials/offer_row', [
        'productId' => 14001,
        'productTitle' => 'Видеокарта ASUS ROG Strix GeForce RTX 4070 SUPER',
        'isBest' => false,
        'offer' => [
          'k' => 'aliexpress:14001_2',
          'shop' => 'aliexpress',
          'price' => 59900,
          'landed' => 60390,
          'origin' => 'CN',
          'note' => 'Из Китая • 14 дн.',
          'seller' => ['name' => 'Global Hardware Store', 'rating' => 4.7, 'n' => 840, 'official' => 0]
        ]
      ]) ?>

      <?= $view->partial('partials/offer_row', [
        'productId' => 14001,
        'productTitle' => 'Видеокарта ASUS ROG Strix GeForce RTX 4070 SUPER',
        'isBest' => false,
        'offer' => [
          'k' => 'wildberries:14001_3',
          'shop' => 'wildberries',
          'mode' => 'link_only',
          'price' => null,
          'origin' => 'RU'
        ]
      ]) ?>
    </div>
  </section>
</div>

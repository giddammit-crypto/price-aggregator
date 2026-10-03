<?php
/**
 * Favorites Page View
 * @var array $products
 * @var App\Core\View $view
 */
declare(strict_types=1);
?>

<div class="container">
  <div style="margin-bottom: var(--sp-6);">
    <h1 style="font-size: var(--fs-2xl); font-weight: 800;">Избранные товары</h1>
    <p class="text-muted" style="font-size: var(--fs-sm);">Товары, добавленные вами для отслеживания динамики цен</p>
  </div>

  <div id="favEmptyState" <?= !empty($products) ? 'hidden' : '' ?> style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: 48px; text-align: center; max-width: 600px; margin: 0 auto;">
    <svg class="icon icon-lg" style="color: var(--c-ink-3); width: 48px; height: 48px; margin: 0 auto var(--sp-3);"><use href="/assets/icons/sprite.svg#heart"></use></svg>
    <h2 style="font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-2);">Список избранного пуст</h2>
    <p class="text-muted" style="font-size: var(--fs-sm); margin-bottom: var(--sp-4);">
      Нажимайте на сердечко в каталоге или карточке товара, чтобы сохранить его в избранное.
    </p>
    <a href="/catalog/smartphones" class="btn btn--accent">В каталог товаров</a>
  </div>

  <?php if (!empty($products)): ?>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <?= $view->partial('partials/product_card', [
          'p' => [
            'id' => $p['id'],
            't' => $p['title'],
            'b' => $p['brand'],
            'slug' => $p['slug'],
            'p' => $p['agg']['min'] ?? 0,
            'c' => $p['agg']['cnt'] ?? count($p['offers'] ?? []),
            'd' => $p['agg']['drop'] ?? 0,
            'pop' => $p['popularity'] ?? 0,
            'img' => $p['img'] ?? '/assets/img/placeholder.svg',
            'attrs' => $p['attrs'] ?? [],
            'mp' => $p['agg']['mp'] ?? 0,
            'cb' => $p['agg']['cb'] ?? 0
          ]
        ]) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const ids = urlParams.get('ids');
  if (!ids) {
    try {
      const stored = JSON.parse(localStorage.getItem('favorites')) || [];
      if (stored.length > 0) {
        window.location.replace('/favorites?ids=' + stored.join(','));
      }
    } catch (e) {}
  }
});
</script>

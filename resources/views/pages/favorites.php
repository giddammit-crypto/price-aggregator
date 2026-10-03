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

  <div class="product-grid" id="favoritesGrid" <?= empty($products) ? 'style="display:none;"' : '' ?>>
    <?php if (!empty($products)): ?>
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
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const emptyState = document.getElementById('favEmptyState');
  const grid = document.getElementById('favoritesGrid');
  let stored = [];
  try {
    stored = JSON.parse(localStorage.getItem('favorites')) || [];
  } catch (e) {}

  if (stored.length === 0) {
    if (grid) grid.style.display = 'none';
    if (emptyState) emptyState.hidden = false;
    return;
  }

  stored = stored.filter(id => Number.isSafeInteger(id) && id > 0).slice(0, 40);
  if (!window.location.hostname.endsWith('github.io') && !grid?.children.length) {
    const query = stored.join(',');
    if (query && new URLSearchParams(location.search).get('ids') !== query) location.replace('/favorites?ids=' + query);
    return;
  }

  // If server already rendered products for these IDs, nothing more needed
  if (grid && grid.children.length > 0) {
    return;
  }

  // On static GitHub Pages or direct navigation, hydrate from api/search_index.json
  const prefix = window.location.pathname.startsWith('/price-aggregator') ? '/price-aggregator' : '';
  const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  const localUrl = value => {
    try { const url = new URL(value, location.origin); return url.origin === location.origin ? escape(url.href) : '#'; }
    catch (_) { return '#'; }
  };
  fetch(prefix + '/api/search_index.json')
    .then(r => { if (!r.ok) throw new Error('Index unavailable'); return r.json(); })
    .then(items => {
      const favItems = items.filter(item => stored.includes(item.id));
      if (favItems.length > 0 && grid) {
        grid.innerHTML = favItems.map(p => `
          <article class="product-card" data-product-id="${Number(p.id)}">
            <button type="button" class="product-card__fav is-active" data-fav-id="${Number(p.id)}" title="В избранное">
              <svg class="icon icon-sm"><use href="${prefix}/assets/icons/sprite.svg#heart"></use></svg>
            </button>
            <a href="${localUrl(p.url)}" class="product-card__img-wrap" tabindex="-1">
              <img src="${localUrl(p.image)}" alt="${escape(p.title)}" class="product-card__img" loading="lazy" width="180" height="180">
            </a>
            <div class="product-card__brand">${escape(p.brand)}</div>
            <a href="${localUrl(p.url)}" class="product-card__title" title="${escape(p.title)}">${escape(p.title)}</a>
            <div class="product-card__footer">
              <div class="product-card__price-wrap">
                <span class="product-card__price-label">от</span>
                <span class="product-card__price">${(p.price || 0).toLocaleString('ru-RU')} ₽</span>
                <span class="product-card__shops-cnt">${Number(p.offers) || 0} предложений</span>
              </div>
              <button type="button" class="btn btn--sm btn--secondary" data-compare-id="${Number(p.id)}" title="Сравнить">
                <svg class="icon icon-sm"><use href="${prefix}/assets/icons/sprite.svg#scale"></use></svg>
              </button>
            </div>
          </article>
        `).join('');
        grid.style.display = 'grid';
        if (emptyState) emptyState.hidden = true;
      }
    })
    .catch(() => {
      if (emptyState) { emptyState.hidden = false; emptyState.querySelector('h2').textContent = 'Не удалось загрузить избранное'; }
    });
});
</script>

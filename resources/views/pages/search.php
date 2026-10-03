<?php
/**
 * Search Results Page View
 * @var string $query
 * @var array $results
 * @var float $durationMs
 * @var App\Core\View $view
 */
declare(strict_types=1);
?>

<div class="container">
  <div style="margin-bottom: var(--sp-6);">
    <h1 id="searchHeading" style="font-size: var(--fs-2xl); font-weight: 800; margin-bottom: var(--sp-1);">
      Поиск по запросу «<?= e($query) ?>»
    </h1>
    <p id="searchCount" class="text-muted" style="font-size: var(--fs-sm);">
      Найдено <?= count($results) ?> товаров
    </p>
  </div>

  <div class="product-grid" id="searchGrid" <?= empty($results) ? 'style="display:none;"' : '' ?>>
    <?php if (!empty($results)): ?>
      <?php foreach ($results as $p): ?>
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

  <div id="searchEmptyState" <?= !empty($results) ? 'hidden' : '' ?> style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: 48px; text-align: center; max-width: 600px; margin: 0 auto;">
    <svg class="icon icon-lg" style="color: var(--c-ink-3); width: 48px; height: 48px; margin: 0 auto var(--sp-3);"><use href="/assets/icons/sprite.svg#search"></use></svg>
    <h2 style="font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-2);">Ничего не нашлось</h2>
    <p class="text-muted" style="font-size: var(--fs-sm); margin-bottom: var(--sp-4);">
      Проверьте правильность написания или попробуйте поискать по бренду, например: <a href="/search?q=ASUS" style="color: var(--c-accent); text-decoration: underline;">ASUS</a>, <a href="/search?q=Apple" style="color: var(--c-accent); text-decoration: underline;">Apple</a>, <a href="/search?q=RTX" style="color: var(--c-accent); text-decoration: underline;">RTX</a>.
    </p>
    <a href="/" class="btn btn--accent">Вернуться на главную</a>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const q = urlParams.get('q');
  if (!q) return;

  const heading = document.getElementById('searchHeading');
  const countEl = document.getElementById('searchCount');
  const grid = document.getElementById('searchGrid');
  const emptyState = document.getElementById('searchEmptyState');

  if (heading) heading.textContent = `Поиск по запросу «${q}»`;

  // If server already rendered results (dynamic PHP mode), keep them
  if (grid && grid.children.length > 0) return;

  const prefix = window.location.pathname.startsWith('/price-aggregator') ? '/price-aggregator' : '';
  const startTime = performance.now();

  fetch(prefix + '/api/search_index.json')
    .then(r => r.json())
    .then(items => {
      const qLower = q.toLowerCase();
      const matched = items.filter(p => 
        p.title.toLowerCase().includes(qLower) || 
        (p.brand && p.brand.toLowerCase().includes(qLower)) ||
        (p.cat && p.cat.toLowerCase().includes(qLower))
      );

      if (countEl) countEl.textContent = `Найдено ${matched.length} товаров`;

      if (matched.length > 0 && grid) {
        grid.innerHTML = matched.map(p => `
          <article class="product-card" data-product-id="${p.id}">
            <button type="button" class="product-card__fav" data-fav-id="${p.id}" title="В избранное">
              <svg class="icon icon-sm"><use href="${prefix}/assets/icons/sprite.svg#heart"></use></svg>
            </button>
            <a href="${p.url}" class="product-card__img-wrap" tabindex="-1">
              <img src="${p.image}" alt="${p.title}" class="product-card__img" loading="lazy" width="180" height="180">
            </a>
            <div class="product-card__brand">${p.brand}</div>
            <a href="${p.url}" class="product-card__title" title="${p.title}">${p.title}</a>
            <div class="product-card__footer">
              <div class="product-card__price-wrap">
                <span class="product-card__price-label">от</span>
                <span class="product-card__price">${(p.price || 0).toLocaleString('ru-RU')} ₽</span>
                <span class="product-card__shops-cnt">${p.offers} предложений</span>
              </div>
              <button type="button" class="btn btn--sm btn--secondary" data-compare-id="${p.id}" title="Сравнить">
                <svg class="icon icon-sm"><use href="${prefix}/assets/icons/sprite.svg#scale"></use></svg>
              </button>
            </div>
          </article>
        `).join('');
        grid.style.display = 'grid';
        if (emptyState) emptyState.hidden = true;
      } else {
        if (grid) grid.style.display = 'none';
        if (emptyState) emptyState.hidden = false;
      }
    })
    .catch(() => {});
});
</script>

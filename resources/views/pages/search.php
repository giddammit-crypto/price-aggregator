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
    <h1 style="font-size: var(--fs-2xl); font-weight: 800; margin-bottom: var(--sp-1);">
      Поиск по запросу «<?= e($query) ?>»
    </h1>
    <p class="text-muted" style="font-size: var(--fs-sm);">
      Найдено <?= count($results) ?> товаров за <?= $durationMs ?> мс
    </p>
  </div>

  <?php if (!empty($results)): ?>
    <div class="product-grid">
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
    </div>
  <?php else: ?>
    <div style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: 48px; text-align: center; max-width: 600px; margin: 0 auto;">
      <svg class="icon icon-lg" style="color: var(--c-ink-3); width: 48px; height: 48px; margin: 0 auto var(--sp-3);"><use href="/assets/icons/sprite.svg#search"></use></svg>
      <h2 style="font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-2);">Ничего не нашлось</h2>
      <p class="text-muted" style="font-size: var(--fs-sm); margin-bottom: var(--sp-4);">
        Проверьте правильность написания или попробуйте поискать по бренду, например: <a href="/search?q=ASUS" style="color: var(--c-accent); text-decoration: underline;">ASUS</a>, <a href="/search?q=Apple" style="color: var(--c-accent); text-decoration: underline;">Apple</a>, <a href="/search?q=RTX" style="color: var(--c-accent); text-decoration: underline;">RTX</a>.
      </p>
      <a href="/" class="btn btn--accent">Вернуться на главную</a>
    </div>
  <?php endif; ?>
</div>

<?php
/**
 * Comparison Page View
 * @var array $products
 * @var array $specKeys
 * @var App\Core\View $view
 */
declare(strict_types=1);
?>

<div class="container">
  <div class="d-flex justify-between align-center mb-4 flex-wrap gap-2">
    <div>
      <h1 style="font-size: var(--fs-2xl); font-weight: 800;">Сравнение товаров</h1>
      <p class="text-muted" style="font-size: var(--fs-sm);">Сравните характеристики и минимальные цены выбранных моделей</p>
    </div>

    <?php if (!empty($products)): ?>
      <label class="filter-checkbox font-bold">
        <input type="checkbox" id="toggleDiffsOnly">
        <span>Показывать только отличия</span>
      </label>
    <?php endif; ?>
  </div>

  <div id="compareEmptyState" <?= !empty($products) ? 'hidden' : '' ?> style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: 48px; text-align: center; max-width: 600px; margin: 0 auto;">
    <svg class="icon icon-lg" style="color: var(--c-ink-3); width: 48px; height: 48px; margin: 0 auto var(--sp-3);"><use href="/assets/icons/sprite.svg#scale"></use></svg>
    <h2 style="font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-2);">В сравнении пока пусто</h2>
    <p class="text-muted" style="font-size: var(--fs-sm); margin-bottom: var(--sp-4);">
      Нажимайте на иконку весов в карточке товара или каталоге, чтобы добавить до 6 моделей для подробного сравнения.
    </p>
    <a href="/catalog/smartphones" class="btn btn--accent">Перейти в каталог</a>
  </div>

  <?php if (!empty($products)): ?>
    <div style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); overflow-x: auto; box-shadow: var(--sh-1);">
      <table style="width: 100%; border-collapse: collapse; min-width: 700px; font-size: var(--fs-sm);">
        <thead>
          <tr style="border-bottom: 2px solid var(--c-line); background: var(--c-bg);">
            <th style="padding: 16px; text-align: left; width: 220px; position: sticky; left: 0; background: var(--c-bg); z-index: 2;">Модель</th>
            <?php foreach ($products as $p): ?>
              <th style="padding: 16px; text-align: center; vertical-align: top; width: 220px;">
                <img src="<?= e($p['img'] ?? '/assets/img/placeholder.svg') ?>" alt="" style="max-height: 100px; margin: 0 auto 8px;" width="100" height="100">
                <a href="/p/<?= e($p['slug']) ?>-<?= $p['id'] ?>" class="font-bold" style="display: block; line-height: 1.3; margin-bottom: 6px;">
                  <?= e($p['title']) ?>
                </a>
                <div style="font-size: 1.25rem; font-weight: 800; color: var(--c-accent); margin-bottom: 8px;">
                  <?= formatPrice($p['agg']['min'] ?? null) ?>
                </div>
                <button type="button" class="btn btn--outline btn--sm" data-compare-id="<?= $p['id'] ?>">Удалить</button>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($specKeys as $specName): 
            $values = [];
            foreach ($products as $p) {
                $values[] = $p['specs'][$specName] ?? '—';
            }
            $isSame = (count(array_unique($values)) === 1);
          ?>
            <tr class="compare-row <?= $isSame ? 'is-same' : 'is-different' ?>" style="border-bottom: 1px solid var(--c-line);">
              <td style="padding: 12px 16px; font-weight: 600; color: var(--c-ink-2); position: sticky; left: 0; background: var(--c-surface); z-index: 1;">
                <?= e($specName) ?>
              </td>
              <?php foreach ($values as $val): ?>
                <td style="padding: 12px 16px; text-align: center;">
                  <?= e((string)$val) ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Sync compare items from LocalStorage if query params are missing
  const urlParams = new URLSearchParams(window.location.search);
  const ids = urlParams.get('ids');
  if (!ids) {
    try {
      const stored = JSON.parse(localStorage.getItem('compare')) || [];
      if (stored.length > 0) {
        window.location.replace('/compare?ids=' + stored.join(','));
        return;
      }
    } catch (e) {}
  }

  // Toggle diffs only
  const diffToggle = document.getElementById('toggleDiffsOnly');
  if (diffToggle) {
    diffToggle.addEventListener('change', (e) => {
      document.querySelectorAll('.compare-row.is-same').forEach(row => {
        row.style.display = e.target.checked ? 'none' : '';
      });
    });
  }
});
</script>

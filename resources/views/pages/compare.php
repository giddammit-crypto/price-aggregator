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

      <label class="filter-checkbox font-bold" id="compareDiffControl" <?= empty($products) ? 'hidden' : '' ?>>
        <input type="checkbox" id="toggleDiffsOnly">
        <span>Показывать только отличия</span>
      </label>
  </div>

  <div id="compareEmptyState" <?= !empty($products) ? 'hidden' : '' ?> style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: 48px; text-align: center; max-width: 600px; margin: 0 auto;">
    <svg class="icon icon-lg" style="color: var(--c-ink-3); width: 48px; height: 48px; margin: 0 auto var(--sp-3);"><use href="/assets/icons/sprite.svg#scale"></use></svg>
    <h2 style="font-size: var(--fs-xl); font-weight: 800; margin-bottom: var(--sp-2);">В сравнении пока пусто</h2>
    <p class="text-muted" style="font-size: var(--fs-sm); margin-bottom: var(--sp-4);">
      Нажимайте на иконку весов в карточке товара или каталоге, чтобы добавить до 6 моделей для подробного сравнения.
    </p>
    <a href="/catalog/smartphones" class="btn btn--accent">Перейти в каталог</a>
  </div>

  <div id="compareTableWrap" style="<?= empty($products) ? 'display:none;' : '' ?> background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); overflow-x: auto; box-shadow: var(--sh-1);">
    <table style="width: 100%; border-collapse: collapse; min-width: 700px; font-size: var(--fs-sm);">
      <thead id="compareThead">
        <tr style="border-bottom: 2px solid var(--c-line); background: var(--c-bg);">
          <th style="padding: 16px; text-align: left; width: 220px; position: sticky; left: 0; background: var(--c-bg); z-index: 2;">Модель</th>
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
              <th style="padding: 16px; text-align: center; vertical-align: top; width: 220px;">
                <img src="<?= e(\App\Services\VerifiedProductImage::forProduct($p) ?? $p['img'] ?? '/assets/img/placeholder.svg') ?>" alt="<?= e($p['title']) ?>" style="max-height: 100px; margin: 0 auto 8px;" width="100" height="100">
                <a href="/p/<?= e($p['slug']) ?>-<?= $p['id'] ?>" class="font-bold" style="display: block; line-height: 1.3; margin-bottom: 6px;">
                  <?= e($p['title']) ?>
                </a>
                <div style="font-size: 1.25rem; font-weight: 800; color: var(--c-accent); margin-bottom: 8px;">
                  <?= formatPrice($p['agg']['min'] ?? null) ?>
                </div>
                <button type="button" class="btn btn--outline btn--sm" data-compare-id="<?= $p['id'] ?>">Удалить</button>
              </th>
            <?php endforeach; ?>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody id="compareTbody">
        <?php if (!empty($products)): ?>
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
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const tableWrap = document.getElementById('compareTableWrap');
  const thead = document.getElementById('compareThead');
  const tbody = document.getElementById('compareTbody');
  const emptyState = document.getElementById('compareEmptyState');
  const diffToggle = document.getElementById('toggleDiffsOnly');

  let stored = [];
  try {
    stored = JSON.parse(localStorage.getItem('compare')) || [];
  } catch (e) {}

  if (stored.length === 0) {
    if (tableWrap) tableWrap.style.display = 'none';
    if (emptyState) emptyState.hidden = false;
    return;
  }

  stored = stored.filter(id => Number.isSafeInteger(id) && id > 0).slice(0, 6);
  if (!window.location.hostname.endsWith('github.io') && thead?.querySelectorAll('th').length <= 1) {
    const query = stored.join(',');
    if (query && new URLSearchParams(location.search).get('ids') !== query) location.replace('/compare?ids=' + query);
    return;
  }

  function setupDiffToggle() {
    if (diffToggle) {
      diffToggle.addEventListener('change', (e) => {
        document.querySelectorAll('.compare-row.is-same').forEach(row => {
          row.style.display = e.target.checked ? 'none' : '';
        });
      });
    }
  }

  // If already rendered on server with products
  if (thead && thead.querySelectorAll('th').length > 1) {
    setupDiffToggle();
    return;
  }

  // Hydrate client-side from search_index.json
  const prefix = window.location.pathname.startsWith('/price-aggregator') ? '/price-aggregator' : '';
  const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  const localUrl = value => {
    try { const url = new URL(value, location.origin); return url.origin === location.origin ? escape(url.href) : '#'; }
    catch (_) { return '#'; }
  };
  fetch(prefix + '/api/search_index.json')
    .then(r => { if (!r.ok) throw new Error('Index unavailable'); return r.json(); })
    .then(items => {
      const cmpItems = items.filter(item => stored.includes(item.id));
      if (cmpItems.length > 0 && tableWrap && thead && tbody) {
        const allKeys = new Set();
        cmpItems.forEach(p => {
          if (p.specs) Object.keys(p.specs).forEach(k => allKeys.add(k));
        });

        thead.innerHTML = `
          <tr style="border-bottom: 2px solid var(--c-line); background: var(--c-bg);">
            <th style="padding: 16px; text-align: left; width: 220px; position: sticky; left: 0; background: var(--c-bg); z-index: 2;">Модель</th>
            ${cmpItems.map(p => `
              <th style="padding: 16px; text-align: center; vertical-align: top; width: 220px;">
                <img src="${localUrl(p.image)}" alt="" style="max-height: 100px; margin: 0 auto 8px;" width="100" height="100">
                <a href="${localUrl(p.url)}" class="font-bold" style="display: block; line-height: 1.3; margin-bottom: 6px;">
                  ${escape(p.title)}
                </a>
                <div style="font-size: 1.25rem; font-weight: 800; color: var(--c-accent); margin-bottom: 8px;">
                  ${(p.price || 0).toLocaleString('ru-RU')} ₽
                </div>
                <button type="button" class="btn btn--outline btn--sm" data-compare-id="${Number(p.id)}">Удалить</button>
              </th>
            `).join('')}
          </tr>
        `;

        let bodyHtml = '';
        allKeys.forEach(k => {
          const vals = cmpItems.map(p => (p.specs && p.specs[k]) ? p.specs[k] : '—');
          const isSame = new Set(vals).size === 1;
          bodyHtml += `
            <tr class="compare-row ${isSame ? 'is-same' : 'is-different'}" style="border-bottom: 1px solid var(--c-line);">
              <td style="padding: 12px 16px; font-weight: 600; color: var(--c-ink-2); position: sticky; left: 0; background: var(--c-surface); z-index: 1;">
                ${escape(k)}
              </td>
              ${vals.map(v => `<td style="padding: 12px 16px; text-align: center;">${escape(v)}</td>`).join('')}
            </tr>
          `;
        });
        tbody.innerHTML = bodyHtml;

        tableWrap.style.display = 'block';
        document.getElementById('compareDiffControl').hidden = false;
        if (emptyState) emptyState.hidden = true;
        setupDiffToggle();
      }
    })
    .catch(() => {
      if (emptyState) { emptyState.hidden = false; emptyState.querySelector('h2').textContent = 'Не удалось загрузить сравнение'; }
    });
});
</script>

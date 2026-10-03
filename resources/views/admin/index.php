<?php
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
  <div>
    <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:0.25rem;">Дашборд состояния системы</h1>
    <p style="color:var(--text-muted); font-size:0.95rem;">Атомарные снимки • Файловые NDJSON паки • OPcache-кэширование</p>
  </div>
  <button id="rebuildBtn" class="btn btn-primary" onclick="rebuildIndex()">Пересобрать снимок каталога</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div style="color:var(--text-muted); font-size:0.85rem;">Активный снимок</div>
    <div class="val" style="font-size:1.4rem; color:#f95700;"><?= e($snapshotId) ?></div>
    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.5rem;">
      Создан: <?= e($meta['built_at'] ?? 'Неизвестно') ?>
    </div>
  </div>

  <div class="stat-card">
    <div style="color:var(--text-muted); font-size:0.85rem;">Товаров в каталоге</div>
    <div class="val"><?= number_format((int)($meta['products_count'] ?? 0), 0, '', ' ') ?></div>
    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.5rem;">
      Шардов товаров: <?= $productShardsCount ?> (p000–p255)
    </div>
  </div>

  <div class="stat-card">
    <div style="color:var(--text-muted); font-size:0.85rem;">Предложений магазинов</div>
    <div class="val"><?= number_format((int)($meta['offers_count'] ?? 0), 0, '', ' ') ?></div>
    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.5rem;">
      Категорий: <?= (int)($meta['categories_count'] ?? 0) ?>
    </div>
  </div>

  <div class="stat-card">
    <div style="color:var(--text-muted); font-size:0.85rem;">Кликов / переходов сегодня</div>
    <div class="val"><?= number_format($clicksToday, 0, '', ' ') ?></div>
    <div style="font-size:0.8rem; color:#00855b; margin-top:0.5rem; font-weight:600;">
      Партнёрская активность
    </div>
  </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem; margin-bottom:2rem;">
  <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem;">
    <h3 style="font-size:1.1rem; margin-bottom:1rem; font-weight:700;">Статус фоновых задач (Cron)</h3>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Задача</th>
          <th>Последний запуск</th>
          <th>Время работы</th>
          <th>Статус</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($cronJobs)): ?>
          <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">Задачи пока не выполнялись</td></tr>
        <?php else: ?>
          <?php foreach ($cronJobs as $job): ?>
            <tr>
              <td><strong><?= e($job['job'] ?? '') ?></strong></td>
              <td><?= e($job['last_run_date'] ?? '') ?></td>
              <td><?= e((string)($job['duration_sec'] ?? '0')) ?> сек</td>
              <td>
                <?php if (($job['exit_code'] ?? 1) === 0): ?>
                  <span class="badge-ok">Успешно (0)</span>
                <?php else: ?>
                  <span class="badge-warn">Код: <?= e((string)$job['exit_code']) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem;">
    <h3 style="font-size:1.1rem; margin-bottom:1rem; font-weight:700;">Курсы валют ЦБ РФ</h3>
    <?php $rates = $fxData['rates'] ?? []; ?>
    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.75rem;">
      <li style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
        <span>USD (Доллар)</span>
        <strong><?= number_format((float)($rates['USD'] ?? 92.5), 2, '.', ' ') ?> ₽</strong>
      </li>
      <li style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
        <span>EUR (Евро)</span>
        <strong><?= number_format((float)($rates['EUR'] ?? 101.2), 2, '.', ' ') ?> ₽</strong>
      </li>
      <li style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
        <span>CNY (Юань)</span>
        <strong><?= number_format((float)($rates['CNY'] ?? 12.85), 2, '.', ' ') ?> ₽</strong>
      </li>
    </ul>
    <div style="margin-top:1rem; font-size:0.8rem; color:var(--text-muted);">
      Обновлено: <?= e($fxData['updated_at'] ?? 'По умолчанию') ?>
    </div>
  </div>
</div>

<script>
function rebuildIndex() {
  const btn = document.getElementById('rebuildBtn');
  btn.disabled = true;
  btn.innerText = 'Пересобираем...';
  fetch('/admin/rebuild-index', { method: 'POST' })
    .then(r => r.json())
    .then(d => {
      alert(d.message || 'Готово');
      location.reload();
    })
    .catch(e => {
      alert('Ошибка: ' + e);
      btn.disabled = false;
      btn.innerText = 'Пересобрать снимок каталога';
    });
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

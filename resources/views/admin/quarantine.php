<?php
ob_start();
?>
<div style="margin-bottom: 2rem;">
  <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:0.25rem;">Карантин цен и аномалий</h1>
  <p style="color:var(--text-muted); font-size:0.95rem;">Предложения с аномально низкими ценами (падение > 65% от медианы) или подозрительными условиями, задержанные фильтром доверия</p>
</div>

<table class="admin-table">
  <thead>
    <tr>
      <th>Дата</th>
      <th>Магазин</th>
      <th>Наименование</th>
      <th>Цена предложения</th>
      <th>Причина карантина</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($items)): ?>
      <tr>
        <td colspan="5" style="text-align:center; padding: 2rem; color:var(--text-muted);">
          Карантин чист. Аномальных цен и спам-предложений в активных фидах не обнаружено.
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><span style="font-size:0.8rem; color:var(--text-muted);"><?= e($it['date'] ?? '') ?></span></td>
          <td><strong><?= e($it['shop'] ?? '') ?></strong></td>
          <td>
            <strong><?= e($it['offer']['title'] ?? $it['offer']['name'] ?? '') ?></strong>
            <div style="font-size:0.8rem; color:var(--text-muted);"><?= e($it['offer']['key'] ?? '') ?></div>
          </td>
          <td><strong style="color:#dc2626;"><?= number_format((float)($it['offer']['price'] ?? 0), 0, '.', ' ') ?> ₽</strong></td>
          <td><span class="badge-warn"><?= e($it['reason'] ?? '') ?></span></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

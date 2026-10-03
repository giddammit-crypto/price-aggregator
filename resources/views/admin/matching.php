<?php
ob_start();
?>
<div style="margin-bottom: 2rem;">
  <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:0.25rem;">Очередь ручного сопоставления (Матчинг)</h1>
  <p style="color:var(--text-muted); font-size:0.95rem;">Предложения магазинов со сходством 70%–89%, требующие подтверждения или ручной привязки к товару</p>
</div>

<table class="admin-table">
  <thead>
    <tr>
      <th>Дата</th>
      <th>Магазин</th>
      <th>Предложение магазина</th>
      <th>Цена</th>
      <th>Предложенный товар (ID)</th>
      <th>Сходство</th>
      <th>Действие</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($queue)): ?>
      <tr>
        <td colspan="7" style="text-align:center; padding: 2rem; color:var(--text-muted);">
          Очередь сопоставления пуста. Все офферы либо сопоставлены автоматически (EAN / MPN / ≥90%), либо отклонены.
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($queue as $q): ?>
        <tr>
          <td><span style="font-size:0.8rem; color:var(--text-muted);"><?= e($q['date'] ?? '') ?></span></td>
          <td><strong><?= e($q['shop'] ?? '') ?></strong></td>
          <td>
            <strong><?= e($q['offer_title'] ?? '') ?></strong>
            <div style="font-size:0.8rem; color:var(--text-muted);"><?= e($q['offer_key'] ?? '') ?></div>
          </td>
          <td><strong><?= number_format((float)($q['offer_price'] ?? 0), 0, '.', ' ') ?> ₽</strong></td>
          <td>
            <a href="/p/prod-<?= (int)$q['suggested_product_id'] ?>" target="_blank" style="color:var(--primary); font-weight:600;">
              Товар #<?= (int)$q['suggested_product_id'] ?> ↗
            </a>
          </td>
          <td>
            <span class="badge-warn"><?= round(((float)($q['score'] ?? 0)) * 100) ?>%</span>
          </td>
          <td>
            <button class="btn-action" onclick="approveMatch('<?= e($q['offer_key'] ?? '') ?>', <?= (int)$q['suggested_product_id'] ?>)">
              Подтвердить
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<script>
function approveMatch(offerKey, productId) {
  const fd = new FormData();
  fd.append('offer_key', offerKey);
  fd.append('product_id', productId);

  fetch('/admin/matching/override', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        alert('Связка сохранена!');
        location.reload();
      } else {
        alert('Ошибка при сохранении');
      }
    });
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

<?php
ob_start();
?>
<div style="margin-bottom: 2rem;">
  <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:0.25rem;">Магазины и маркетплейсы</h1>
  <p style="color:var(--text-muted); font-size:0.95rem;">Управление источниками, режимы сбора цен (фид YML / API / link_only) и уровень доверия</p>
</div>

<table class="admin-table">
  <thead>
    <tr>
      <th>Магазин / Маркетплейс</th>
      <th>Тип</th>
      <th>Режим</th>
      <th>Уровень доверия</th>
      <th>Метка</th>
      <th>Последний сбор</th>
      <th>Принято офферов</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($shops as $s): ?>
      <tr>
        <td>
          <strong><?= e($s['name']) ?></strong>
          <div style="font-size:0.8rem; color:var(--text-muted);"><?= e($s['id']) ?></div>
        </td>
        <td><?= e(ucfirst($s['kind'])) ?></td>
        <td>
          <?php if ($s['mode'] === 'prices'): ?>
            <span class="badge-ok">Цены (API/YML)</span>
          <?php else: ?>
            <span class="badge-warn">Поиск (link_only)</span>
          <?php endif; ?>
        </td>
        <td>
          <?= str_repeat('★', (int)$s['trust_level']) ?> (<?= (int)$s['trust_level'] ?>/5)
        </td>
        <td><?= e($s['badge']) ?></td>
        <td><?= e($s['last_run']) ?></td>
        <td>
          <?php if (!empty($s['metrics'])): ?>
            <strong style="color:#00855b;"><?= number_format((int)$s['metrics']['accepted'], 0, '', ' ') ?></strong>
            <span style="font-size:0.8rem; color:var(--text-muted);">(из <?= (int)$s['metrics']['total'] ?>)</span>
          <?php else: ?>
            <span style="color:var(--text-muted);">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Управление PriceHub') ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    .admin-nav { background: #1e232d; padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #f95700; }
    .admin-nav a { color: #fff; text-decoration: none; font-weight: 600; }
    .admin-nav .links { display: flex; gap: 1.25rem; font-size: 0.95rem; }
    .admin-nav .links a:hover { color: #f95700; }
    .admin-container { max-width: 1280px; margin: 2rem auto; padding: 0 1rem; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; }
    .stat-card .val { font-size: 1.8rem; font-weight: 800; color: #1e232d; margin-top: 0.25rem; }
    .admin-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); font-size: 0.9rem; }
    .admin-table th, .admin-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: left; }
    .admin-table th { background: #f7f9fb; font-weight: 700; }
    .badge-ok { background: #e6f7ef; color: #00855b; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem; }
    .badge-warn { background: #fff8e6; color: #d97706; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem; }
    .btn-action { background: #f95700; color: #fff; border: none; padding: 0.4rem 0.8rem; border-radius: var(--radius); cursor: pointer; font-weight: 600; font-size: 0.85rem; }
  </style>
</head>
<body>
  <nav class="admin-nav">
    <div style="display:flex; align-items:center; gap:1.5rem;">
      <a href="/admin" style="display:flex; align-items:center; gap:0.5rem; font-size:1.2rem;">
        <span style="color:#f95700; font-weight:900;">PriceHub</span> Admin
      </a>
      <div class="links">
        <a href="/admin">Обзор</a>
        <a href="/admin/shops">Магазины</a>
        <a href="/admin/matching">Матчинг</a>
        <a href="/admin/quarantine">Карантин</a>
        <a href="/" target="_blank">Сайт ↗</a>
      </div>
    </div>
    <div>
      <a href="/admin/logout" style="color:var(--text-muted); font-size:0.85rem;">Выйти</a>
    </div>
  </nav>

  <main class="admin-container">
    <?= $content ?? '' ?>
  </main>
</body>
</html>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Вход в панель управления — PriceHub</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    body { background: #f7f9fb; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .login-box { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 2.5rem; width: 100%; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    .login-box h1 { font-size: 1.5rem; margin-bottom: 1.5rem; text-align: center; }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; }
    .form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: var(--radius); font-size: 1rem; }
    .error-msg { background: #fef2f2; color: #dc2626; padding: 0.75rem; border-radius: var(--radius); font-size: 0.85rem; margin-bottom: 1rem; }
  </style>
</head>
<body>
  <div class="login-box">
    <h1><span style="color:#f95700;">PriceHub</span> Admin</h1>
    <?php if (!empty($error)): ?>
      <div class="error-msg"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="/admin/login">
      <div class="form-group">
        <label for="password">Пароль администратора</label>
        <input type="password" id="password" name="password" required autofocus autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Войти</button>
    </form>
    <div style="text-align: center; margin-top: 1.5rem; font-size: 0.8rem; color: var(--text-muted);">
      Хранение без СУБД • PHP 8.1+ • OPcache
    </div>
  </div>
</body>
</html>

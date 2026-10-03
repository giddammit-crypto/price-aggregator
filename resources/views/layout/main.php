<?php
/**
 * Main Layout with Google Stitch Design Integration
 * @var string $content
 * @var string|null $pageTitle
 * @var string|null $pageDesc
 * @var App\Core\View $view
 */
declare(strict_types=1);

$title = $pageTitle ?? 'PriceHub — умный агрегатор цен и скидок';
$description = $pageDesc ?? 'Сравнение цен на технику и электронику в магазинах и маркетплейсах РФ и Китая.';
$canonical = $canonicalUrl ?? (App\Core\Config::get('app.url') . ($_SERVER['REQUEST_URI'] ?? '/'));
$cspNonce = App\Core\Response::getCspNonce();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">

  <!-- Open Graph -->
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:site_name" content="PriceHub">

  <!-- Fonts: Plus Jakarta Sans & Inter from Stitch Design System -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800;900&display=swap" rel="stylesheet">

  <!-- Core Stylesheet -->
  <link rel="stylesheet" href="/assets/css/style.css">
  <?= $view->getSection('styles') ?>
</head>
<body class="site-body">
  <!-- Service information: never present hard-coded counters as live measurements. -->
  <div style="background: var(--c-surface-high); border-bottom: 1px solid var(--c-line); font-size: var(--fs-xs); padding: 6px 0; color: var(--c-ink);">
    <div class="container d-flex justify-between align-center flex-wrap gap-2">
      <div class="d-flex align-center gap-2">
        <span>Демонстрационный каталог: цены и ссылки на поиск магазинов не подтверждены товарными фидами. Перед покупкой уточните данные у продавца.</span>
      </div>
      <div class="d-flex align-center gap-4 text-muted" style="font-size:11px;">
        <a href="/pages/how-it-works" style="color:var(--c-accent); font-weight:600;">Методология &rarr;</a>
      </div>
    </div>
  </div>

  <!-- Desktop & Mobile Header -->
  <header class="site-header" id="siteHeader">
    <div class="header-main">
      <div class="container header-main__inner">
        <!-- Logo -->
        <a href="/" class="header-logo" aria-label="PriceHub Главная">
          <span class="header-logo__badge">Price</span><span class="header-logo__accent">Hub</span>
        </a>

        <!-- Catalog Button -->
        <button type="button" class="btn btn--accent header-catalog-btn" id="btnCatalogToggle" aria-expanded="false" aria-controls="megaMenu">
          <svg class="icon" aria-hidden="true"><use href="/assets/icons/sprite.svg#menu"></use></svg>
          <span>Каталог товаров</span>
        </button>

        <!-- Search Bar with Instant Suggestions -->
        <div class="header-search">
          <form action="/search" method="GET" class="search-form" id="searchForm" role="search">
            <div class="search-input-wrap">
              <input type="search" name="q" id="searchInput" class="search-input" 
                     placeholder="Поиск моделей в демонстрационном каталоге..."
                     value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" required>
              <button type="submit" class="search-btn" aria-label="Искать">
                <svg class="icon" aria-hidden="true"><use href="/assets/icons/sprite.svg#search"></use></svg>
              </button>
            </div>
            <!-- Suggestions Dropdown -->
            <div class="search-suggestions" id="searchSuggestions" hidden></div>
          </form>
        </div>

        <!-- Header Actions: Compare, Favorites -->
        <div class="header-actions">
          <a href="/compare" class="header-action-btn" title="Сравнение товаров" id="hdrCompareBtn">
            <div class="header-action-btn__icon">
              <svg class="icon" aria-hidden="true"><use href="/assets/icons/sprite.svg#scale"></use></svg>
              <span class="badge-count" id="compareCount" hidden>0</span>
            </div>
            <span class="header-action-btn__label">Сравнение</span>
          </a>

          <a href="/favorites" class="header-action-btn" title="Избранные товары" id="hdrFavBtn">
            <div class="header-action-btn__icon">
              <svg class="icon" aria-hidden="true"><use href="/assets/icons/sprite.svg#heart"></use></svg>
              <span class="badge-count" id="favoritesCount" hidden>0</span>
            </div>
            <span class="header-action-btn__label">Избранное</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Category Subheader Strip -->
    <div style="background: var(--c-dark-2); border-top: 1px solid rgba(255,255,255,0.08); overflow-x: auto; white-space: nowrap;">
      <div class="container d-flex align-center gap-2" style="height: 40px; font-size: var(--fs-xs); font-weight: 600;">
        <a href="/catalog/smartphones" style="color: var(--c-on-dark); padding: 4px 10px; border-radius: 4px;">Смартфоны и гаджеты</a>
        <a href="/catalog/laptops" style="color: var(--c-on-dark); padding: 4px 10px; border-radius: 4px;">Ноутбуки и ПК</a>
        <a href="/catalog/graphics-cards" style="color: var(--c-on-dark); padding: 4px 10px; border-radius: 4px;">Видеокарты</a>
        <a href="/catalog/processors" style="color: var(--c-on-dark); padding: 4px 10px; border-radius: 4px;">Процессоры</a>
        <a href="/catalog/storage-ssd" style="color: var(--c-on-dark); padding: 4px 10px; border-radius: 4px;">SSD накопители</a>
        <a href="/catalog/televisions" style="color: var(--c-on-dark); padding: 4px 10px; border-radius: 4px;">ТВ и Аудио</a>
        <a href="/search?q=rtx" style="color: var(--c-accent); padding: 4px 10px; border-radius: 4px;">🔥 Скидки дня</a>
      </div>
    </div>

    <!-- Mega Menu Backdrop & Container -->
    <div class="mega-menu-backdrop" id="megaMenuBackdrop" hidden></div>
    <nav class="mega-menu" id="megaMenu" hidden aria-label="Каталог категорий">
      <div class="container mega-menu__inner" id="megaMenuContent">
        <?= $view->partial('partials/mega_menu') ?>
      </div>
    </nav>
  </header>

  <!-- Main Content Slot -->
  <main class="site-main" id="mainContent">
    <?= $content ?>
  </main>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="footer-grid">
        <div class="footer-col">
          <div class="footer-brand">
            <span class="header-logo__badge">Price</span><span class="header-logo__accent">Hub</span>
          </div>
          <p class="footer-desc">Независимый сервис сравнения цен на цифровую и бытовую технику в интернет-магазинах и маркетплейсах РФ и Китая.</p>
          <p class="footer-currency">Перед покупкой уточняйте актуальные цены и наличие в магазине.</p>
        </div>

        <div class="footer-col">
          <h3 class="footer-title">Каталог техники</h3>
          <ul class="footer-links">
            <li><a href="/catalog/graphics-cards">Видеокарты</a></li>
            <li><a href="/catalog/processors">Процессоры</a></li>
            <li><a href="/catalog/smartphones">Смартфоны</a></li>
            <li><a href="/catalog/laptops">Ноутбуки</a></li>
            <li><a href="/catalog/storage-ssd">SSD накопители</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h3 class="footer-title">О сервисе</h3>
          <ul class="footer-links">
            <li><a href="/pages/how-it-works">Как мы считаем цены</a></li>
            <li><a href="/pages/marketplaces">О маркетплейсах и продавцах</a></li>
            <li><a href="/pages/report">Сообщить о неверной цене</a></li>
            <li><a href="/pages/legal">Политика конфиденциальности (152-ФЗ)</a></li>
            <li><a href="/ui-kit">Витрина компонентов (UI Kit)</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h3 class="footer-title">Подключение магазинов</h3>
          <p class="footer-note">Подключаем официальные фиды YML/XML ритейлеров и партнерские API маркетплейсов.</p>
          <div class="footer-links mt-2">
            <a href="mailto:partners@pricehub.ru" class="font-bold" style="color:var(--c-accent);">partners@pricehub.ru</a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <p>© 2026 PriceHub. Все права защищены. Цены не являются публичной офертой (ст. 437 ГК РФ).</p>
      </div>
    </div>
  </footer>

  <!-- Mobile Bottom Navigation Bar -->
  <nav class="mobile-nav" aria-label="Мобильная навигация">
    <a href="/" class="mobile-nav__item <?= ($_SERVER['REQUEST_URI'] === '/') ? 'is-active' : '' ?>">
      <svg class="icon"><use href="/assets/icons/sprite.svg#home"></use></svg>
      <span>Главная</span>
    </a>
    <button type="button" class="mobile-nav__item" id="mobileCatalogToggle" aria-expanded="false" aria-controls="megaMenu">
      <svg class="icon"><use href="/assets/icons/sprite.svg#grid"></use></svg>
      <span>Каталог</span>
    </button>
    <a href="/compare" class="mobile-nav__item" id="mobileCompareBtn">
      <svg class="icon"><use href="/assets/icons/sprite.svg#scale"></use></svg>
      <span>Сравнение</span>
    </a>
    <a href="/favorites" class="mobile-nav__item" id="mobileFavBtn">
      <svg class="icon"><use href="/assets/icons/sprite.svg#heart"></use></svg>
      <span>Избранное</span>
    </a>
    <a href="/admin" class="mobile-nav__item">
      <svg class="icon"><use href="/assets/icons/sprite.svg#user"></use></svg>
      <span>Кабинет</span>
    </a>
  </nav>

  <!-- Cookie Banner -->
  <div class="cookie-banner" id="cookieBanner" hidden>
    <div class="cookie-banner__inner">
      <p>Мы используем файлы cookie для сохранения ваших сравнений, избранного и аналитики переходов. Подробнее в <a href="/pages/legal">Политике</a>.</p>
      <button type="button" class="btn btn--sm btn--accent" id="acceptCookieBtn">Понятно</button>
    </div>
  </div>

  <!-- Global Toast Notification Container -->
  <div class="toast-container" id="toastContainer" aria-live="polite"></div>

  <!-- Core Scripts (No build step, pure ES-modules) -->
  <script type="module" src="/assets/js/app.js"></script>
  <?= $view->getSection('scripts') ?>
</body>
</html>

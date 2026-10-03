/**
 * PriceHub Main Vanilla JS Entrypoint (ES-Module)
 */

import { Storage } from './storage.js';
import { initSearch } from './search.js';
import { initFilters } from './filters.js';

document.addEventListener('DOMContentLoaded', () => {
  // 1. Initialize Search Autocomplete
  initSearch();

  // 2. Initialize Catalog Filters
  initFilters();

  // 3. Mega Menu Toggle
  const catalogBtn = document.getElementById('btnCatalogToggle');
  const mobileCatalogBtn = document.getElementById('mobileCatalogToggle');
  const megaMenu = document.getElementById('megaMenu');

  function toggleMenu() {
    if (!megaMenu) return;
    const isHidden = megaMenu.hidden;
    megaMenu.hidden = !isHidden;
    if (catalogBtn) {
      catalogBtn.setAttribute('aria-expanded', String(isHidden));
    }
  }

  if (catalogBtn) catalogBtn.addEventListener('click', toggleMenu);
  if (mobileCatalogBtn) mobileCatalogBtn.addEventListener('click', toggleMenu);

  document.addEventListener('click', (e) => {
    if (megaMenu && !megaMenu.hidden && !e.target.closest('#btnCatalogToggle') && !e.target.closest('#megaMenu') && !e.target.closest('#mobileCatalogToggle')) {
      megaMenu.hidden = true;
      if (catalogBtn) catalogBtn.setAttribute('aria-expanded', 'false');
    }
  });

  // 4. Update Compare & Favorites Counters
  updateCounters();

  // 5. Global delegated clicks for Favorites and Compare
  document.addEventListener('click', (e) => {
    const favBtn = e.target.closest('[data-fav-id]');
    if (favBtn) {
      e.preventDefault();
      const id = parseInt(favBtn.dataset.favId, 10);
      const { added } = Storage.toggle('favorites', id);
      favBtn.classList.toggle('is-active', added);
      updateCounters();
      showToast(added ? 'Товар добавлен в избранное' : 'Товар удален из избранного');
      return;
    }

    const compareBtn = e.target.closest('[data-compare-id]');
    if (compareBtn) {
      e.preventDefault();
      const id = parseInt(compareBtn.dataset.compareId, 10);
      const { added, list } = Storage.toggle('compare', id);
      compareBtn.classList.toggle('is-active', added);
      updateCounters();
      showToast(added ? `Товар добавлен в сравнение (${list.length})` : 'Товар удален из сравнения');
      return;
    }
  });

  // 6. Cookie Banner
  const cookieBanner = document.getElementById('cookieBanner');
  const acceptCookieBtn = document.getElementById('acceptCookieBtn');
  if (cookieBanner && acceptCookieBtn) {
    if (!localStorage.getItem('cookie_accepted')) {
      cookieBanner.hidden = false;
    }
    acceptCookieBtn.addEventListener('click', () => {
      localStorage.setItem('cookie_accepted', '1');
      cookieBanner.hidden = true;
    });
  }
});

export function updateCounters() {
  const compareList = Storage.get('compare');
  const favList = Storage.get('favorites');

  const cmpEl = document.getElementById('compareCount');
  if (cmpEl) {
    cmpEl.textContent = compareList.length;
    cmpEl.hidden = compareList.length === 0;
  }

  const favEl = document.getElementById('favoritesCount');
  if (favEl) {
    favEl.textContent = favList.length;
    favEl.hidden = favList.length === 0;
  }

  // Sync active states on page buttons
  document.querySelectorAll('[data-fav-id]').forEach(btn => {
    const id = parseInt(btn.dataset.favId, 10);
    btn.classList.toggle('is-active', favList.includes(id));
  });

  document.querySelectorAll('[data-compare-id]').forEach(btn => {
    const id = parseInt(btn.dataset.compareId, 10);
    btn.classList.toggle('is-active', compareList.includes(id));
  });
}

export function showToast(message) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = message;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.transition = 'opacity 0.3s ease';
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, 2500);
}

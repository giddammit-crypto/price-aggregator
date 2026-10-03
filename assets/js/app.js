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

  // 3. Mega Menu Toggle & Tab Switching
  const catalogBtn = document.getElementById('btnCatalogToggle');
  const mobileCatalogBtn = document.getElementById('mobileCatalogToggle');
  const megaMenu = document.getElementById('megaMenu');
  const megaBackdrop = document.getElementById('megaMenuBackdrop');

  function openMenu() {
    if (!megaMenu) return;
    megaMenu.hidden = false;
    if (megaBackdrop) megaBackdrop.hidden = false;
    if (catalogBtn) catalogBtn.setAttribute('aria-expanded', 'true');
  }

  function closeMenu() {
    if (!megaMenu) return;
    megaMenu.hidden = true;
    if (megaBackdrop) megaBackdrop.hidden = true;
    if (catalogBtn) catalogBtn.setAttribute('aria-expanded', 'false');
  }

  function toggleMenu() {
    if (!megaMenu) return;
    if (megaMenu.hidden) {
      openMenu();
    } else {
      closeMenu();
    }
  }

  if (catalogBtn) catalogBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleMenu();
  });
  if (mobileCatalogBtn) mobileCatalogBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleMenu();
  });
  if (megaBackdrop) megaBackdrop.addEventListener('click', closeMenu);

  // Close on Escape or click outside
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeMenu();
    }
  });

  document.addEventListener('click', (e) => {
    if (megaMenu && !megaMenu.hidden && 
        !e.target.closest('#btnCatalogToggle') && 
        !e.target.closest('#mobileCatalogToggle') && 
        !e.target.closest('#megaMenu')) {
      closeMenu();
    }
  });

  // Close when clicking any link inside the menu
  if (megaMenu) {
    megaMenu.addEventListener('click', (e) => {
      if (e.target.closest('a')) {
        closeMenu();
      }
    });

    // Parent category tab switching on hover / click
    const catItems = megaMenu.querySelectorAll('.mega-menu__cat-item');
    const groups = megaMenu.querySelectorAll('.mega-menu__group');

    function switchMegaTab(parentId) {
      catItems.forEach(item => {
        const match = (item.dataset.parentId === String(parentId));
        item.classList.toggle('is-active', match);
        item.setAttribute('aria-selected', String(match));
      });
      groups.forEach(g => {
        const match = (g.dataset.parentId === String(parentId));
        g.classList.toggle('is-active', match);
        g.hidden = !match;
      });
    }

    catItems.forEach(item => {
      item.addEventListener('mouseenter', () => switchMegaTab(item.dataset.parentId));
      item.addEventListener('click', (e) => {
        e.preventDefault();
        switchMegaTab(item.dataset.parentId);
      });
    });
  }

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

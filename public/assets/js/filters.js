/**
 * Client-Side Catalog Sorting and Filtering (Vanilla JS)
 * Supports instant, offline sorting & filtering on GitHub Pages and dynamic PHP backend.
 */

export function initFilters() {
  const filterForm = document.getElementById('filterForm');
  const catalogGrid = document.getElementById('catalogProducts');
  const sortSelect = document.getElementById('sortSelect');
  const sortSelectTop = document.getElementById('sortSelectTop');
  const countEl = document.getElementById('filterCount');

  if (!catalogGrid && !filterForm && !sortSelect && !sortSelectTop) return;

  // Mark filters as initialized to coordinate with any fallback script
  window.PriceHubFiltersInitialized = true;

  // Mobile drawer controls
  const openBtn = document.getElementById('openFiltersBtn');
  const closeBtn = document.getElementById('closeFiltersBtn');
  const sidebar = document.getElementById('filterSidebar');

  if (openBtn && sidebar) {
    openBtn.addEventListener('click', (e) => {
      e.preventDefault();
      sidebar.classList.add('is-open');
    });
  }
  if (closeBtn && sidebar) {
    closeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      sidebar.classList.remove('is-open');
    });
  }

  // Reset filters button / link
  const resetBtn = document.getElementById('resetFiltersBtn') || (sidebar ? sidebar.querySelector('a[href="?"]') : null);
  if (resetBtn) {
    resetBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (filterForm) {
        filterForm.reset();
        filterForm.querySelectorAll('input[type="number"], input[type="text"]').forEach(input => {
          input.value = '';
        });
        filterForm.querySelectorAll('input[type="radio"]').forEach(radio => {
          radio.checked = (radio.name === 'seller' && radio.value === '') ||
                          (radio.name === 'brand' && radio.value === '');
        });
        filterForm.querySelectorAll('input[type="checkbox"]').forEach(cb => {
          cb.checked = false;
        });
      }
      if (sortSelect) sortSelect.value = 'popular';
      if (sortSelectTop) sortSelectTop.value = 'popular';

      applyFiltersAndSort(true);
      if (sidebar) sidebar.classList.remove('is-open');
    });
  }

  /**
   * Applies client-side sorting and filtering immediately on the DOM elements
   * @param {boolean} updateHistory - Whether to push state to URL
   */
  function applyFiltersAndSort(updateHistory = true) {
    if (!catalogGrid) return;
    const cards = Array.from(catalogGrid.querySelectorAll('.product-card'));
    if (!cards.length) return;

    // 1. Determine active sort option
    const activeSort = (sortSelect ? sortSelect.value : '') ||
                       (sortSelectTop ? sortSelectTop.value : '') ||
                       'popular';

    // Synchronize both sort controls if both exist
    if (sortSelect && sortSelect.value !== activeSort) sortSelect.value = activeSort;
    if (sortSelectTop && sortSelectTop.value !== activeSort) sortSelectTop.value = activeSort;

    // 2. Sort cards according to selected rule
    cards.sort((a, b) => {
      switch (activeSort) {
        case 'price_asc': {
          const pA = parseFloat(a.dataset.price) || 0;
          const pB = parseFloat(b.dataset.price) || 0;
          return pA - pB;
        }
        case 'price_desc': {
          const pA = parseFloat(a.dataset.price) || 0;
          const pB = parseFloat(b.dataset.price) || 0;
          return pB - pA;
        }
        case 'drop': {
          const dA = parseFloat(a.dataset.drop) || 0;
          const dB = parseFloat(b.dataset.drop) || 0;
          return dB - dA;
        }
        case 'new': {
          const idA = parseInt(a.dataset.productId, 10) || 0;
          const idB = parseInt(b.dataset.productId, 10) || 0;
          return idB - idA;
        }
        case 'popular':
        default: {
          const popA = parseInt(a.dataset.pop, 10) || 0;
          const popB = parseInt(b.dataset.pop, 10) || 0;
          return popB - popA;
        }
      }
    });

    // Re-append sorted cards into grid
    const fragment = document.createDocumentFragment();
    cards.forEach(card => fragment.appendChild(card));
    catalogGrid.appendChild(fragment);

    // 3. Extract filter values from form
    let priceFrom = null;
    let priceTo = null;
    let sellerVal = '';
    let brandVal = '';
    let onlyDrop = false;

    if (filterForm) {
      const pFromInput = filterForm.querySelector('input[name="price_from"]');
      const pToInput = filterForm.querySelector('input[name="price_to"]');
      if (pFromInput && pFromInput.value.trim() !== '') {
        const val = parseFloat(pFromInput.value);
        if (!isNaN(val) && val > 0) priceFrom = val;
      }
      if (pToInput && pToInput.value.trim() !== '') {
        const val = parseFloat(pToInput.value);
        if (!isNaN(val) && val > 0) priceTo = val;
      }

      const sellerInput = filterForm.querySelector('input[name="seller"]:checked');
      if (sellerInput) sellerVal = sellerInput.value.trim();

      const brandInput = filterForm.querySelector('input[name="brand"]:checked');
      if (brandInput) brandVal = brandInput.value.trim().toLowerCase();

      const dropInput = filterForm.querySelector('input[name="drop"]');
      if (dropInput) onlyDrop = dropInput.checked;
    }

    // 4. Collect matching cards
    const matchingCards = [];
    cards.forEach(card => {
      const price = parseFloat(card.dataset.price) || 0;
      const drop = parseFloat(card.dataset.drop) || 0;
      const seller = card.dataset.seller || 'retail';
      const brand = (card.dataset.brand || '').trim().toLowerCase();

      let visible = true;

      // Price from
      if (priceFrom !== null && price < priceFrom) visible = false;
      // Price to
      if (visible && priceTo !== null && price > priceTo) visible = false;
      // Seller type
      if (visible && sellerVal !== '') {
        if (sellerVal === 'marketplace' && seller !== 'marketplace') {
          visible = false;
        } else if (sellerVal === 'crossborder' && seller !== 'crossborder') {
          visible = false;
        }
      }
      // Brand
      if (visible && brandVal !== '' && brand !== brandVal) visible = false;
      // Discount drop
      if (visible && onlyDrop && drop < 8.0) visible = false;

      if (visible) {
        matchingCards.push(card);
      } else {
        card.style.display = 'none';
      }
    });

    const visibleCount = matchingCards.length;

    // 5. Empty state indicator
    let emptyMsg = catalogGrid.querySelector('.catalog-empty-msg');
    if (visibleCount === 0) {
      if (!emptyMsg) {
        emptyMsg = document.createElement('div');
        emptyMsg.className = 'catalog-empty-msg';
        emptyMsg.style.cssText = 'grid-column: 1 / -1; padding: 40px; text-align: center; background: var(--c-surface, #FFF); border: 1px solid var(--c-line, #E5E7EB); border-radius: var(--r-md, 8px); margin: 20px 0; color: var(--c-ink-2, #6B7280); font-size: var(--fs-md, 1rem);';
        emptyMsg.textContent = 'Товаров по выбранным фильтрам не найдено. Попробуйте сбросить параметры.';
        catalogGrid.appendChild(emptyMsg);
      } else {
        emptyMsg.style.display = '';
      }
    } else if (emptyMsg) {
      emptyMsg.style.display = 'none';
    }

    // 6. Update visible count
    if (countEl) {
      countEl.textContent = String(visibleCount);
    }

    // 7. Interactive Client-Side Pagination
    const PAGE_SIZE = 12;
    const totalPages = Math.max(1, Math.ceil(visibleCount / PAGE_SIZE));
    const activePage = Math.min(Math.max(1, targetPage), totalPages);
    currentActivePage = activePage;

    // Show only cards for the active page slice
    matchingCards.forEach((card, idx) => {
      const onPage = (idx >= (activePage - 1) * PAGE_SIZE && idx < activePage * PAGE_SIZE);
      card.style.display = onPage ? '' : 'none';
    });

    const paginationNav = document.getElementById('catalogPagination') || document.querySelector('main > nav');
    if (paginationNav) {
      if (visibleCount === 0 || totalPages <= 1) {
        paginationNav.style.display = 'none';
      } else {
        paginationNav.style.display = 'flex';
        const prevBtn = document.getElementById('prevPageBtn') || paginationNav.querySelector('a:first-child, button:first-child');
        const nextBtn = document.getElementById('nextPageBtn') || paginationNav.querySelector('a:last-child, button:last-child');
        const curLabel = document.getElementById('currentPageLabel');
        const totLabel = document.getElementById('totalPagesLabel');
        const pagesList = document.getElementById('paginationPagesList');

        if (prevBtn) {
          if (prevBtn.tagName === 'BUTTON') {
            prevBtn.disabled = (activePage <= 1);
          } else {
            prevBtn.classList.toggle('disabled', activePage <= 1);
          }
        }
        if (nextBtn) {
          if (nextBtn.tagName === 'BUTTON') {
            nextBtn.disabled = (activePage >= totalPages);
          } else {
            nextBtn.classList.toggle('disabled', activePage >= totalPages);
          }
        }
        if (curLabel) curLabel.textContent = String(activePage);
        if (totLabel) totLabel.textContent = String(totalPages);

        if (pagesList) {
          let pagesHtml = '';
          for (let p = 1; p <= totalPages; p++) {
            if (totalPages <= 7 || p === 1 || p === totalPages || Math.abs(p - activePage) <= 1) {
              const isAct = (p === activePage);
              pagesHtml += `<button type="button" class="btn btn--sm ${isAct ? 'btn--accent' : 'btn--secondary'} page-num-btn" data-page="${p}">${p}</button>`;
            } else if (p === 2 && activePage > 3) {
              pagesHtml += `<span class="text-muted" style="padding: 0 4px;">…</span>`;
            } else if (p === totalPages - 1 && activePage < totalPages - 2) {
              pagesHtml += `<span class="text-muted" style="padding: 0 4px;">…</span>`;
            }
          }
          pagesList.innerHTML = pagesHtml;
        }
      }
    }

    // 8. Update URL parameters without reloading
    const url = new URL(window.location.href);
    if (activePage > 1) {
      url.searchParams.set('page', String(activePage));
    } else {
      url.searchParams.delete('page');
    }

    if (activeSort && activeSort !== 'popular') {
      url.searchParams.set('sort', activeSort);
    } else {
      url.searchParams.delete('sort');
    }

    if (priceFrom !== null) {
      url.searchParams.set('price_from', String(priceFrom));
    } else {
      url.searchParams.delete('price_from');
    }

    if (priceTo !== null) {
      url.searchParams.set('price_to', String(priceTo));
    } else {
      url.searchParams.delete('price_to');
    }

    if (sellerVal) {
      url.searchParams.set('seller', sellerVal);
    } else {
      url.searchParams.delete('seller');
    }

    if (brandVal) {
      const brandInput = filterForm ? filterForm.querySelector('input[name="brand"]:checked') : null;
      url.searchParams.set('brand', brandInput ? brandInput.value : brandVal);
    } else {
      url.searchParams.delete('brand');
    }

    if (onlyDrop) {
      url.searchParams.set('drop', '1');
    } else {
      url.searchParams.delete('drop');
    }

    if (updateHistory && url.toString() !== window.location.href) {
      window.history.pushState({ sort: activeSort }, '', url.toString());
    }

    // 9. Optional non-blocking dynamic backend fetch (never reloads on error)
    tryOptionalBackendFetch(url);
  }

  // Expose function globally for inline fallback
  window.PriceHubApplyFilters = applyFiltersAndSort;

  // Debounced input handler for price fields
  let debounceTimer = null;
  function debouncedApply() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFiltersAndSort(true), 150);
  }

  // Event Listeners
  if (sortSelect) {
    sortSelect.addEventListener('change', () => applyFiltersAndSort(true));
  }
  if (sortSelectTop) {
    sortSelectTop.addEventListener('change', () => applyFiltersAndSort(true));
  }

  if (filterForm) {
    filterForm.addEventListener('change', (e) => {
      // Avoid redundant trigger if change came from sortSelect inside form
      if (e.target === sortSelect) return;
      applyFiltersAndSort(true);
    });

    filterForm.addEventListener('input', (e) => {
      if (e.target.matches('input[type="number"], input[type="text"]')) {
        debouncedApply();
      }
    });

    filterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      applyFiltersAndSort(true);
      if (sidebar) sidebar.classList.remove('is-open');
    });
  }

  // Sync inputs from current URL params
  function syncFormFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);

    const sortParam = urlParams.get('sort') || 'popular';
    if (sortSelect) sortSelect.value = sortParam;
    if (sortSelectTop) sortSelectTop.value = sortParam;

    if (filterForm) {
      const pFromInput = filterForm.querySelector('input[name="price_from"]');
      if (pFromInput) pFromInput.value = urlParams.get('price_from') || '';

      const pToInput = filterForm.querySelector('input[name="price_to"]');
      if (pToInput) pToInput.value = urlParams.get('price_to') || '';

      const seller = urlParams.get('seller') || '';
      const sellerRadio = filterForm.querySelector(`input[name="seller"][value="${seller}"]`);
      if (sellerRadio) sellerRadio.checked = true;

      const brand = urlParams.get('brand') || '';
      if (brand) {
        const brandRadio = Array.from(filterForm.querySelectorAll('input[name="brand"]'))
          .find(r => r.value.toLowerCase() === brand.toLowerCase());
        if (brandRadio) brandRadio.checked = true;
      }

      const dropCheckbox = filterForm.querySelector('input[name="drop"]');
      if (dropCheckbox) dropCheckbox.checked = urlParams.get('drop') === '1';
    }
  }

  // Pagination click handler
  const paginationNav = document.getElementById('catalogPagination') || document.querySelector('main > nav');
  if (paginationNav) {
    paginationNav.addEventListener('click', (e) => {
      const pageBtn = e.target.closest('[data-page]');
      const prevBtn = e.target.closest('#prevPageBtn');
      const nextBtn = e.target.closest('#nextPageBtn');

      if (!pageBtn && !prevBtn && !nextBtn) return;
      e.preventDefault();

      let target = currentActivePage;
      if (pageBtn) {
        target = parseInt(pageBtn.dataset.page, 10);
      } else if (prevBtn && !prevBtn.disabled) {
        target = currentActivePage - 1;
      } else if (nextBtn && !nextBtn.disabled) {
        target = currentActivePage + 1;
      }

      if (target >= 1 && target !== currentActivePage) {
        applyFiltersAndSort(true, target);
        if (catalogGrid) {
          catalogGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  }

  // Handle browser Back / Forward navigation
  window.addEventListener('popstate', () => {
    syncFormFromUrl();
    const p = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10);
    applyFiltersAndSort(false, p);
  });

  // Apply on initial load (with initial page from URL)
  syncFormFromUrl();
  const initialPage = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10);
  applyFiltersAndSort(false, initialPage);

  // Optional background fetch helper (for dynamic PHP server mode)
  function tryOptionalBackendFetch(url) {
    if (window.location.protocol === 'file:' || window.location.hostname.includes('github.io')) {
      return;
    }
    const catIdInput = filterForm ? filterForm.querySelector('input[name="catId"]') : null;
    if (!catIdInput || !catIdInput.value) return;

    const fetchParams = new URLSearchParams(url.searchParams);
    fetchParams.set('catId', catIdInput.value);

    const backendUrl = `${window.location.origin}/api/catalog-filter?${fetchParams.toString()}`;
    fetch(backendUrl, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => {
      if (!res.ok) return null;
      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) return null;
      return res.json();
    })
    .then(data => {
      if (data && data.html) {
        const currentQs = new URL(window.location.href).searchParams.toString();
        if (currentQs === url.searchParams.toString()) {
          catalogGrid.innerHTML = data.html;
          if (countEl && data.count !== undefined) {
            countEl.textContent = String(data.count);
          }
        }
      }
    })
    .catch(err => {
      // Client-side filtering is already active; silently ignore network/404 issues
      console.debug('Dynamic backend filter skipped:', err);
    });
  }
}

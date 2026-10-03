/**
 * Progressive Enhancement for Catalog Filters
 */

export function initFilters() {
  const filterForm = document.getElementById('filterForm');
  const catalogGrid = document.getElementById('catalogProducts');
  if (!filterForm || !catalogGrid) return;

  filterForm.addEventListener('change', async () => {
    const formData = new FormData(filterForm);
    const params = new URLSearchParams(formData);

    // Update browser URL without reloading
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState({}, '', newUrl);

    // Fetch partial grid
    try {
      catalogGrid.style.opacity = '0.5';
      const res = await fetch(`/api/catalog-filter?${params.toString()}`);
      if (!res.ok) {
        location.reload();
        return;
      }
      const data = await res.json();
      if (data.html) {
        catalogGrid.innerHTML = data.html;
      }
      const countEl = document.getElementById('filterCount');
      if (countEl && data.count !== undefined) {
        countEl.textContent = data.count;
      }
    } catch (err) {
      console.warn("Partial filter update error, falling back to reload", err);
      location.href = newUrl;
    } finally {
      catalogGrid.style.opacity = '1';
    }
  });

  // Mobile drawer
  const openBtn = document.getElementById('openFiltersBtn');
  const closeBtn = document.getElementById('closeFiltersBtn');
  const sidebar = document.getElementById('filterSidebar');

  if (openBtn && sidebar) {
    openBtn.addEventListener('click', () => sidebar.classList.add('is-open'));
  }
  if (closeBtn && sidebar) {
    closeBtn.addEventListener('click', () => sidebar.classList.remove('is-open'));
  }
}

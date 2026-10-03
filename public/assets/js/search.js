/**
 * Instant Search Autocomplete with Debounce and Keyboard Navigation
 */

export function initSearch() {
  const searchInput = document.getElementById('searchInput');
  const suggestionsBox = document.getElementById('searchSuggestions');
  if (!searchInput || !suggestionsBox) return;

  let debounceTimer = null;
  let selectedIndex = -1;

  searchInput.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    const query = e.target.value.trim();

    if (query.length < 2) {
      suggestionsBox.hidden = true;
      suggestionsBox.innerHTML = '';
      return;
    }

    debounceTimer = setTimeout(async () => {
      try {
        const res = await fetch(`/api/suggest?q=${encodeURIComponent(query)}`);
        if (!res.ok) return;
        const data = await res.json();
        renderSuggestions(data.suggestions || [], data.products || []);
      } catch (err) {
        console.error("Search suggest failed", err);
      }
    }, 150);
  });

  searchInput.addEventListener('keydown', (e) => {
    const items = suggestionsBox.querySelectorAll('.suggest-item');
    if (!items.length || suggestionsBox.hidden) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      selectedIndex = (selectedIndex + 1) % items.length;
      updateHighlight(items);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      selectedIndex = (selectedIndex - 1 + items.length) % items.length;
      updateHighlight(items);
    } else if (e.key === 'Enter' && selectedIndex >= 0) {
      e.preventDefault();
      items[selectedIndex].click();
    } else if (e.key === 'Escape') {
      suggestionsBox.hidden = true;
    }
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#searchForm')) {
      suggestionsBox.hidden = true;
    }
  });

  function renderSuggestions(terms, products) {
    if (!terms.length && !products.length) {
      suggestionsBox.hidden = true;
      suggestionsBox.innerHTML = '';
      return;
    }

    let html = '';
    terms.slice(0, 5).forEach(term => {
      html += `<div class="suggest-item" data-type="term" onclick="location.href='/search?q=${encodeURIComponent(term)}'">
        <span><svg class="icon icon-sm" style="margin-right:6px;"><use href="/assets/icons/sprite.svg#search"></use></svg>${escapeHtml(term)}</span>
        <span class="text-muted" style="font-size:12px;">поиск</span>
      </div>`;
    });

    products.slice(0, 4).forEach(p => {
      html += `<div class="suggest-item" data-type="product" onclick="location.href='/p/${p.slug}-${p.id}'">
        <span><strong>${escapeHtml(p.title)}</strong></span>
        <span class="font-bold" style="color:var(--c-accent);">${p.price} ₽</span>
      </div>`;
    });

    suggestionsBox.innerHTML = html;
    suggestionsBox.hidden = false;
    selectedIndex = -1;
  }

  function updateHighlight(items) {
    items.forEach((el, idx) => {
      if (idx === selectedIndex) {
        el.classList.add('is-selected');
        el.scrollIntoView({ block: 'nearest' });
      } else {
        el.classList.remove('is-selected');
      }
    });
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
}

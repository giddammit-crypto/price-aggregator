/**
 * Test Suite for Catalog Sorting and Filtering (filters.js)
 */

import assert from 'node:assert';

// 1. Minimal DOM Mock Environment
class MockDataset {
  constructor(initial = {}) {
    Object.assign(this, initial);
  }
}

class MockStyle {
  constructor() {
    this.display = '';
    this.opacity = '1';
  }
}

class MockElement {
  constructor(tagName, id = '', className = '') {
    this.tagName = tagName.toUpperCase();
    this.id = id;
    this.className = className;
    this.dataset = new MockDataset();
    this.style = new MockStyle();
    this.children = [];
    this.parentElement = null;
    this.attributes = new Map();
    this.eventListeners = {};
    this.value = '';
    this.checked = false;
    this.textContent = '';
    const self = this;
    this.classList = {
      add(...classes) {
        for (const c of classes) {
          if (!self.className.includes(c)) self.className = (self.className + ' ' + c).trim();
        }
      },
      remove(...classes) {
        for (const c of classes) {
          self.className = self.className.replace(new RegExp(`\\b${c}\\b`, 'g'), '').trim();
        }
      },
      contains(c) {
        return self.className.includes(c);
      }
    };
  }

  getAttribute(name) {
    return this.attributes.get(name) || null;
  }

  setAttribute(name, val) {
    this.attributes.set(name, String(val));
  }

  appendChild(child) {
    if (child instanceof MockDocumentFragment) {
      const items = [...child.children];
      child.children = [];
      items.forEach(c => this.appendChild(c));
      return child;
    }
    // Remove from previous parent if any
    if (child.parentElement) {
      const idx = child.parentElement.children.indexOf(child);
      if (idx !== -1) child.parentElement.children.splice(idx, 1);
    }
    child.parentElement = this;
    this.children.push(child);
    return child;
  }

  replaceChildren(...nodes) {
    this.children = [];
    for (const node of nodes) {
      if (typeof node === 'string') {
        const textNode = new MockElement('#text');
        textNode.textContent = node;
        this.appendChild(textNode);
      } else {
        this.appendChild(node);
      }
    }
  }

  append(...nodes) {
    for (const node of nodes) {
      if (typeof node === 'string') {
        const textNode = new MockElement('#text');
        textNode.textContent = node;
        this.appendChild(textNode);
      } else {
        this.appendChild(node);
      }
    }
  }

  remove() {
    if (this.parentElement) {
      const idx = this.parentElement.children.indexOf(this);
      if (idx !== -1) this.parentElement.children.splice(idx, 1);
    }
  }

  matches(selector) {
    return matchesSelector(this, selector);
  }

  closest(selector) {
    let cur = this;
    while (cur) {
      if (matchesSelector(cur, selector)) return cur;
      cur = cur.parentElement;
    }
    return null;
  }

  scrollIntoView() {}

  focus() {}

  addEventListener(event, handler) {
    if (!this.eventListeners[event]) this.eventListeners[event] = [];
    this.eventListeners[event].push(handler);
  }

  dispatchEvent(event) {
    const list = this.eventListeners[event.type] || [];
    if (!event.target) event.target = this;
    for (const fn of list) {
      fn(event);
    }
    if (!event.propagationStopped && this.parentElement) {
      this.parentElement.dispatchEvent(event);
    }
  }

  querySelectorAll(selector) {
    const results = [];
    function search(node) {
      for (const child of node.children) {
        if (matchesSelector(child, selector)) {
          results.push(child);
        }
        search(child);
      }
    }
    search(this);
    return results;
  }

  querySelector(selector) {
    return this.querySelectorAll(selector)[0] || null;
  }

  reset() {
    this.querySelectorAll('input').forEach(input => {
      if (input.attributes.get('type') === 'checkbox') input.checked = false;
      else if (input.attributes.get('type') === 'radio') input.checked = false;
      else input.value = '';
    });
  }

  get elements() {
    return {
      namedItem: (name) => {
        return this.querySelector(`input[name="${name}"], select[name="${name}"]`);
      }
    };
  }
}

class MockDocumentFragment {
  constructor() {
    this.children = [];
  }
  appendChild(child) {
    if (child.parentElement) {
      const idx = child.parentElement.children.indexOf(child);
      if (idx !== -1) child.parentElement.children.splice(idx, 1);
    }
    this.children.push(child);
    return child;
  }
}

function matchesSelector(el, selector) {
  if (selector === '.product-card') return el.className.includes('product-card');
  if (selector === '.catalog-empty-msg') return el.className.includes('catalog-empty-msg');
  if (selector === 'a[href]') return el.tagName === 'A' && el.attributes.has('href');
  if (selector === 'button') return el.tagName === 'BUTTON';
  if (selector === 'select') return el.tagName === 'SELECT';
  if (selector === 'input, select') return el.tagName === 'INPUT' || el.tagName === 'SELECT';
  if (selector.startsWith('input[name="') && selector.endsWith('"]')) {
    const name = selector.match(/input\[name="([^"]+)"\]/)[1];
    return el.tagName === 'INPUT' && el.attributes.get('name') === name;
  }
  if (selector.startsWith('input[name="') && selector.includes('"]:checked')) {
    const name = selector.match(/input\[name="([^"]+)"\]/)[1];
    return el.tagName === 'INPUT' && el.attributes.get('name') === name && el.checked;
  }
  if (selector.startsWith('input[name="') && selector.includes('"][value="')) {
    const name = selector.match(/input\[name="([^"]+)"\]/)[1];
    const val = selector.match(/value="([^"]*)"/)[1];
    return el.tagName === 'INPUT' && el.attributes.get('name') === name && el.value === val;
  }
  if (selector === 'input') return el.tagName === 'INPUT';
  if (selector === 'input[type="number"], input[type="text"]') {
    return el.tagName === 'INPUT' && (el.attributes.get('type') === 'number' || el.attributes.get('type') === 'text');
  }
  if (selector === 'input[type="number"]') {
    return el.tagName === 'INPUT' && el.attributes.get('type') === 'number';
  }
  if (selector === 'input[type="radio"]') {
    return el.tagName === 'INPUT' && el.attributes.get('type') === 'radio';
  }
  if (selector === 'input[type="checkbox"]') {
    return el.tagName === 'INPUT' && el.attributes.get('type') === 'checkbox';
  }
  return false;
}

// 2. Mock Global Environment
globalThis.FormData = class MockFormData {
  constructor(form) {
    this.entriesList = [];
    if (form) {
      const inputs = form.querySelectorAll('input, select');
      for (const input of inputs) {
        const name = input.getAttribute('name');
        if (!name) continue;
        const type = input.getAttribute('type');
        if (type === 'checkbox' || type === 'radio') {
          if (input.checked) this.entriesList.push([name, input.value || 'on']);
        } else {
          if (input.value !== undefined && input.value !== '') {
            this.entriesList.push([name, input.value]);
          }
        }
      }
    }
  }
  [Symbol.iterator]() {
    return this.entriesList[Symbol.iterator]();
  }
  entries() {
    return this.entriesList[Symbol.iterator]();
  }
};

const elementsById = new Map();
const mockDocument = {
  getElementById(id) {
    return elementsById.get(id) || null;
  },
  createElement(tag) {
    return new MockElement(tag);
  },
  createDocumentFragment() {
    return new MockDocumentFragment();
  },
  querySelector(selector) {
    if (selector === 'main > nav') return elementsById.get('paginationNav') || null;
    return null;
  },
  addEventListener(event, handler) {},
  removeEventListener(event, handler) {}
};

let pushedUrls = [];
const mockHistory = {
  pushState(state, title, url) {
    pushedUrls.push(url);
    mockLocation.href = url.startsWith('/') ? 'http://localhost' + url : url;
    const urlObj = new URL(mockLocation.href);
    mockLocation.pathname = urlObj.pathname;
    mockLocation.search = urlObj.search;
  }
};

const mockLocation = {
  origin: 'http://localhost',
  href: 'http://localhost/catalog/smartphones',
  pathname: '/catalog/smartphones',
  search: '',
  protocol: 'http:',
  hostname: 'pricehub.github.io',
  reload() {
    throw new Error('location.reload() was called! It must never be called on client-side filter!');
  }
};

globalThis.document = mockDocument;
globalThis.window = {
  location: mockLocation,
  history: mockHistory,
  addEventListener() {},
  PriceHubFiltersInitialized: false
};
globalThis.location = mockLocation;

// Import filters.js module
const { initFilters } = await import('../public/assets/js/filters.js');

// 3. Setup Test DOM Tree
function setupDom() {
  elementsById.clear();
  pushedUrls = [];
  mockLocation.search = '';
  mockLocation.href = 'http://localhost/catalog/smartphones';

  const catalogGrid = new MockElement('div', 'catalogProducts', 'product-grid');
  elementsById.set('catalogProducts', catalogGrid);

  const filterCount = new MockElement('span', 'filterCount');
  elementsById.set('filterCount', filterCount);

  const sortSelect = new MockElement('select', 'sortSelect');
  sortSelect.setAttribute('name', 'sort');
  sortSelect.value = 'popular';
  elementsById.set('sortSelect', sortSelect);

  const filterForm = new MockElement('form', 'filterForm');
  elementsById.set('filterForm', filterForm);
  filterForm.appendChild(sortSelect);

  const catIdInput = new MockElement('input');
  catIdInput.setAttribute('type', 'hidden');
  catIdInput.setAttribute('name', 'catId');
  catIdInput.value = '14';
  filterForm.appendChild(catIdInput);

  const priceFrom = new MockElement('input');
  priceFrom.setAttribute('type', 'number');
  priceFrom.setAttribute('name', 'price_from');
  priceFrom.value = '';
  filterForm.appendChild(priceFrom);

  const priceTo = new MockElement('input');
  priceTo.setAttribute('type', 'number');
  priceTo.setAttribute('name', 'price_to');
  priceTo.value = '';
  filterForm.appendChild(priceTo);

  const sellerAll = new MockElement('input');
  sellerAll.setAttribute('type', 'radio');
  sellerAll.setAttribute('name', 'seller');
  sellerAll.value = '';
  sellerAll.checked = true;
  filterForm.appendChild(sellerAll);

  const sellerMp = new MockElement('input');
  sellerMp.setAttribute('type', 'radio');
  sellerMp.setAttribute('name', 'seller');
  sellerMp.value = 'marketplace';
  sellerMp.checked = false;
  filterForm.appendChild(sellerMp);

  const sellerCb = new MockElement('input');
  sellerCb.setAttribute('type', 'radio');
  sellerCb.setAttribute('name', 'seller');
  sellerCb.value = 'crossborder';
  sellerCb.checked = false;
  filterForm.appendChild(sellerCb);

  const brandAll = new MockElement('input');
  brandAll.setAttribute('type', 'radio');
  brandAll.setAttribute('name', 'brand');
  brandAll.value = '';
  brandAll.checked = true;
  filterForm.appendChild(brandAll);

  const brandApple = new MockElement('input');
  brandApple.setAttribute('type', 'radio');
  brandApple.setAttribute('name', 'brand');
  brandApple.value = 'Apple';
  brandApple.checked = false;
  filterForm.appendChild(brandApple);

  const brandXiaomi = new MockElement('input');
  brandXiaomi.setAttribute('type', 'radio');
  brandXiaomi.setAttribute('name', 'brand');
  brandXiaomi.value = 'Xiaomi';
  brandXiaomi.checked = false;
  filterForm.appendChild(brandXiaomi);

  const dropCb = new MockElement('input');
  dropCb.setAttribute('type', 'checkbox');
  dropCb.setAttribute('name', 'drop');
  dropCb.value = '1';
  dropCb.checked = false;
  filterForm.appendChild(dropCb);

  // Add 4 sample product cards
  // Card 1: Apple iPhone 15, Price 75000, Drop 12%, Pop 100, ID 101, retail
  const c1 = new MockElement('article', '', 'product-card');
  c1.dataset.productId = '101';
  c1.dataset.price = '75000';
  c1.dataset.drop = '12.0';
  c1.dataset.pop = '100';
  c1.dataset.brand = 'apple';
  c1.dataset.seller = 'retail';
  catalogGrid.appendChild(c1);

  // Card 2: Apple iPhone 13, Price 50000, Drop 0%, Pop 300, ID 102, marketplace
  const c2 = new MockElement('article', '', 'product-card');
  c2.dataset.productId = '102';
  c2.dataset.price = '50000';
  c2.dataset.drop = '0.0';
  c2.dataset.pop = '300';
  c2.dataset.brand = 'apple';
  c2.dataset.seller = 'marketplace';
  catalogGrid.appendChild(c2);

  // Card 3: Xiaomi 14, Price 65000, Drop 15%, Pop 200, ID 103, crossborder
  const c3 = new MockElement('article', '', 'product-card');
  c3.dataset.productId = '103';
  c3.dataset.price = '65000';
  c3.dataset.drop = '15.0';
  c3.dataset.pop = '200';
  c3.dataset.brand = 'xiaomi';
  c3.dataset.seller = 'crossborder';
  catalogGrid.appendChild(c3);

  // Card 4: Xiaomi Redmi 13, Price 15000, Drop 5%, Pop 400, ID 104, retail
  const c4 = new MockElement('article', '', 'product-card');
  c4.dataset.productId = '104';
  c4.dataset.price = '15000';
  c4.dataset.drop = '5.0';
  c4.dataset.pop = '400';
  c4.dataset.brand = 'xiaomi';
  c4.dataset.seller = 'retail';
  catalogGrid.appendChild(c4);

  return { catalogGrid, filterCount, sortSelect, filterForm, priceFrom, priceTo, sellerAll, sellerMp, sellerCb, brandAll, brandApple, brandXiaomi, dropCb, c1, c2, c3, c4 };
}

// 4. Test Cases
console.log('Running Catalog Filter & Sort Unit Tests...');

// TEST 1: Initialization
{
  const dom = setupDom();
  initFilters();
  assert.strictEqual(window.PriceHubFiltersInitialized, true, 'Filters should be initialized');
  console.log('✔ Test 1: Initialization passed');
}

// TEST 2: Sorting price_asc (Cheapest first)
{
  const dom = setupDom();
  initFilters();
  dom.sortSelect.value = 'price_asc';
  dom.sortSelect.dispatchEvent({ type: 'change' });

  const pids = dom.catalogGrid.children.map(c => c.dataset.productId);
  assert.deepStrictEqual(pids, ['104', '102', '103', '101'], 'Should sort price ASC: 15k, 50k, 65k, 75k');
  console.log('✔ Test 2: Sort price_asc passed');
}

// TEST 3: Sorting price_desc (Expensive first)
{
  const dom = setupDom();
  initFilters();
  dom.sortSelect.value = 'price_desc';
  dom.sortSelect.dispatchEvent({ type: 'change' });

  const pids = dom.catalogGrid.children.map(c => c.dataset.productId);
  assert.deepStrictEqual(pids, ['101', '103', '102', '104'], 'Should sort price DESC: 75k, 65k, 50k, 15k');
  console.log('✔ Test 3: Sort price_desc passed');
}

// TEST 4: Sorting by drop (Highest discount first)
{
  const dom = setupDom();
  initFilters();
  dom.sortSelect.value = 'drop';
  dom.sortSelect.dispatchEvent({ type: 'change' });

  const pids = dom.catalogGrid.children.map(c => c.dataset.productId);
  assert.deepStrictEqual(pids, ['103', '101', '104', '102'], 'Should sort drop DESC: 15%, 12%, 5%, 0%');
  console.log('✔ Test 4: Sort drop passed');
}

// TEST 5: Sorting by popular (Highest popularity first)
{
  const dom = setupDom();
  initFilters();
  dom.sortSelect.value = 'popular';
  dom.sortSelect.dispatchEvent({ type: 'change' });

  const pids = dom.catalogGrid.children.map(c => c.dataset.productId);
  assert.deepStrictEqual(pids, ['104', '102', '103', '101'], 'Should sort pop DESC: 400, 300, 200, 100');
  console.log('✔ Test 5: Sort popular passed');
}

// TEST 6: Sorting by new (Highest ID first)
{
  const dom = setupDom();
  initFilters();
  dom.sortSelect.value = 'new';
  dom.sortSelect.dispatchEvent({ type: 'change' });

  const pids = dom.catalogGrid.children.map(c => c.dataset.productId);
  assert.deepStrictEqual(pids, ['104', '103', '102', '101'], 'Should sort ID DESC: 104, 103, 102, 101');
  console.log('✔ Test 6: Sort new passed');
}

// TEST 7: Filtering by Price Range
{
  const dom = setupDom();
  initFilters();
  dom.priceFrom.value = '40000';
  dom.priceTo.value = '70000';
  dom.filterForm.dispatchEvent({ type: 'change' });

  // Visible should be c2 (50000) and c3 (65000)
  assert.strictEqual(dom.c1.style.display, 'none', '75k should be hidden');
  assert.strictEqual(dom.c2.style.display, '', '50k should be visible');
  assert.strictEqual(dom.c3.style.display, '', '65k should be visible');
  assert.strictEqual(dom.c4.style.display, 'none', '15k should be hidden');
  assert.strictEqual(dom.filterCount.textContent, '2', 'Counter should be 2');
  console.log('✔ Test 7: Filter by price range passed');
}

// TEST 8: Filtering by Brand
{
  const dom = setupDom();
  initFilters();
  dom.brandAll.checked = false;
  dom.brandApple.checked = true;
  dom.filterForm.dispatchEvent({ type: 'change' });

  assert.strictEqual(dom.c1.style.display, '', 'Apple c1 should be visible');
  assert.strictEqual(dom.c2.style.display, '', 'Apple c2 should be visible');
  assert.strictEqual(dom.c3.style.display, 'none', 'Xiaomi c3 should be hidden');
  assert.strictEqual(dom.c4.style.display, 'none', 'Xiaomi c4 should be hidden');
  assert.strictEqual(dom.filterCount.textContent, '2');
  console.log('✔ Test 8: Filter by brand passed');
}

// TEST 9: Filtering by Seller (Marketplace & Crossborder)
{
  const dom = setupDom();
  initFilters();
  dom.sellerAll.checked = false;
  dom.sellerMp.checked = true;
  dom.filterForm.dispatchEvent({ type: 'change' });

  assert.strictEqual(dom.c2.style.display, '', 'Marketplace c2 should be visible');
  assert.strictEqual(dom.c1.style.display, 'none', 'Retail c1 should be hidden');
  assert.strictEqual(dom.c3.style.display, 'none', 'Crossborder c3 should be hidden');
  assert.strictEqual(dom.c4.style.display, 'none', 'Retail c4 should be hidden');
  assert.strictEqual(dom.filterCount.textContent, '1');

  // Now crossborder
  dom.sellerMp.checked = false;
  dom.sellerCb.checked = true;
  dom.filterForm.dispatchEvent({ type: 'change' });
  assert.strictEqual(dom.c3.style.display, '', 'Crossborder c3 should be visible');
  assert.strictEqual(dom.filterCount.textContent, '1');
  console.log('✔ Test 9: Filter by seller passed');
}

// TEST 10: Filtering by Discount (Drop >= 8.0)
{
  const dom = setupDom();
  initFilters();
  dom.dropCb.checked = true;
  dom.filterForm.dispatchEvent({ type: 'change' });

  // c1 has 12%, c3 has 15% (both >= 8%). c4 has 5% (< 8%), c2 has 0%.
  assert.strictEqual(dom.c1.style.display, '', '12% drop visible');
  assert.strictEqual(dom.c3.style.display, '', '15% drop visible');
  assert.strictEqual(dom.c2.style.display, 'none', '0% drop hidden');
  assert.strictEqual(dom.c4.style.display, 'none', '5% drop hidden');
  assert.strictEqual(dom.filterCount.textContent, '2');
  console.log('✔ Test 10: Filter by drop discount passed');
}

// TEST 11: Combined Filtering + Sorting
{
  const dom = setupDom();
  initFilters();
  // Filter: brand Apple (c1: 75k drop 12%, c2: 50k drop 0%)
  dom.brandAll.checked = false;
  dom.brandApple.checked = true;
  // Sort: price_asc
  dom.sortSelect.value = 'price_asc';
  dom.sortSelect.dispatchEvent({ type: 'change' });

  const visibleCards = dom.catalogGrid.children.filter(c => c.style.display !== 'none');
  assert.strictEqual(visibleCards.length, 2);
  assert.strictEqual(visibleCards[0].dataset.productId, '102', 'Cheapest Apple first');
  assert.strictEqual(visibleCards[1].dataset.productId, '101', 'Expensive Apple second');
  console.log('✔ Test 11: Combined filter + sort passed');
}

// TEST 12: Empty results shows message
{
  const dom = setupDom();
  initFilters();
  dom.priceFrom.value = '999999';
  dom.filterForm.dispatchEvent({ type: 'change' });

  assert.strictEqual(dom.filterCount.textContent, '0');
  const emptyMsg = dom.catalogGrid.querySelector('.catalog-empty-msg');
  assert.ok(emptyMsg, 'Empty message element should be created');
  assert.strictEqual(emptyMsg.style.display, '', 'Empty message should be visible');
  console.log('✔ Test 12: Empty state message passed');
}

console.log('========================================================');
console.log('ALL CLIENT-SIDE FILTER & SORT TESTS PASSED SUCCESSFULLY!');
console.log('========================================================');

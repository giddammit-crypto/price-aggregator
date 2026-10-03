/**
 * LocalStorage Manager for Compare and Favorites
 */

export const Storage = {
  get(key) {
    try {
      const values = JSON.parse(localStorage.getItem(key));
      return Array.isArray(values) ? values.filter(id => Number.isSafeInteger(id) && id > 0) : [];
    } catch {
      return [];
    }
  },

  set(key, val) {
    try {
      localStorage.setItem(key, JSON.stringify(val));
      return true;
    } catch (e) {
      console.warn("Storage quota exceeded", e);
      return false;
    }
  },

  toggle(key, id) {
    const list = this.get(key);
    const index = list.indexOf(id);
    let added = false;
    if (index > -1) {
      list.splice(index, 1);
    } else {
      list.push(id);
      added = true;
    }
    return { list, added, saved: this.set(key, list) };
  },

  has(key, id) {
    return this.get(key).includes(id);
  }
};

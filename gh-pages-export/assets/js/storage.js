/**
 * LocalStorage Manager for Compare and Favorites
 */

export const Storage = {
  get(key) {
    try {
      return JSON.parse(localStorage.getItem(key)) || [];
    } catch {
      return [];
    }
  },

  set(key, val) {
    try {
      localStorage.setItem(key, JSON.stringify(val));
    } catch (e) {
      console.warn("Storage quota exceeded", e);
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
    this.set(key, list);
    return { list, added };
  },

  has(key, id) {
    return this.get(key).includes(id);
  }
};

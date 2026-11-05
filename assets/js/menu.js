/**
 * RM bu ani - Menu Page Interactions
 */

window.MenuModule = {
  currentCategory: 'all',
  
  init() {
    console.log("MenuModule initialized");
  },

  filter(category, element) {
    this.currentCategory = category;
    if (typeof filterCategory === 'function') {
      filterCategory(category, element);
    }
  }
};

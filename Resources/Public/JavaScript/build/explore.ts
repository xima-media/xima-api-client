import Utility from './utility.js';

class Explore {
  constructor() {
    const apiClientConfigSelect = document.getElementById("api-client-config-selection");

    this.initSearch('search');

    apiClientConfigSelect?.addEventListener("change", function (event) {
      window.location.replace(event.target.value);
    });
  }

  initSearch(searchId:string) {
    const search = document.getElementById(searchId);
    const searchInput = search?.querySelector('input');
    const searchIcon = search?.querySelector('.search-icon-search');
    const closeIcon = search?.querySelector('.search-icon-close');

    if (search) {
      Utility.toggleElement(closeIcon, 'none');
      search.addEventListener('keyup', () => {
        this.processSearch(searchId);
      });

      closeIcon.addEventListener('click', () => {
        searchInput.value = '';
        Utility.toggleElement(searchIcon, 'block');
        Utility.toggleElement(closeIcon, 'none');
        this.processSearch(searchId);
      });

    }
  }

  processSearch(searchId:string) {
    const search = document.getElementById(searchId);
    const searchInput = search?.querySelector('input');
    const searchIcon = search?.querySelector('.search-icon-search');
    const closeIcon = search?.querySelector('.search-icon-close');
    if (!search || !searchIcon || !closeIcon) return;

    const elements = document.querySelectorAll('.search-element');
    const value = searchInput.value.toLowerCase();

    if (value === '') {
      Utility.toggleElement(searchIcon, '');
      Utility.toggleElement(closeIcon, 'none');
    } else {
      Utility.toggleElement(searchIcon, 'none');
      Utility.toggleElement(closeIcon, '');
    }

    elements.forEach((element) => {
      let match = false;
      [...element.attributes].forEach(function (attribute) {
        if (attribute.nodeName.startsWith('data-search')) {
          match = match || attribute.nodeValue?.toLowerCase().match(value)
        }
      });

      match ? Utility.toggleElement(element, '') : Utility.toggleElement(element, 'none');
    });
  }
}

export default new Explore();

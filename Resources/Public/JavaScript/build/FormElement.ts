import Modal from '@typo3/backend/modal.js';

class FormElement {
  constructor() {
    const self = this;
    const searchSelector = document.querySelectorAll('input.form-element-list-search');

    self.scrollToSelectedRadioButton('.form-element-list', '.form-element-list-item input[type="radio"]');

    searchSelector.forEach(function (selector) {
      const elementSelector = selector.closest('.form-element-list-wrap').querySelectorAll('*[data-search]');
      selector.addEventListener('keypress', function (event) {
        if (event.key === "Enter") {
          event.preventDefault();
        }
      });
      selector.addEventListener('keyup', function (event) {
        self.searchForElements(event.target.value, elementSelector);
      });
    });


    const previewSelector = document.querySelectorAll('.form-element-list-preview');
    previewSelector.forEach(function (selector) {
      selector.addEventListener('click', function (event) {
        event.preventDefault();

        const imagePreview = selector.getAttribute('data-preview');
        const content = '<img src="' + imagePreview + '" style="max-width: 100%; max-height: 100%; margin: 0 auto; display: block;" />';

        const placeholder = document.createElement("div");
        placeholder.innerHTML = content;
        const node = placeholder.firstElementChild;
        Modal.confirm('Preview', node, 'info', [
          {
            text: 'Close',
            trigger: function () {
              Modal.dismiss();
            }
          }]);
      });
    });
  }

  searchForElements(searchInput: string, elements: NodeListOf<Element>) {
    const searchInputLowerCase = searchInput.toLowerCase();
    elements.forEach(function (element) {
      const data = element.getAttribute('data-search');
      const label = element.querySelector('.form-element-label');
      if (searchInput === '') {
        label.innerHTML = data;
      }

      if (data.toLowerCase().indexOf(searchInput.toLowerCase()) > -1) {
        element.classList.remove('hidden');
        const regex = new RegExp(`(${searchInputLowerCase}|${searchInput})`, 'gi');
        const text = data.replace(regex, (matched: string) => {
          return `<mark>${matched}</mark>`;
        });
        label.innerHTML = text;
      } else {
        element.classList.add('hidden');
      }
    });
  }

  scrollToSelectedRadioButton(scrollableListSelector: string, radioButtonSelector: string) {
    const scrollableLists = document.querySelectorAll(scrollableListSelector);

    scrollableLists.forEach(function (scrollableList) {
      if (!scrollableList) {
        console.error("Scrollable list not found.");
        return;
      }

      const radioButtons = Array.from(scrollableList.querySelectorAll(radioButtonSelector));
      const selectedRadioButton = radioButtons.find(radioButton => radioButton.checked);

      if (!selectedRadioButton) {
        console.error("No radio button is selected.");
        return;
      }

      scrollableList.scrollTo(0,selectedRadioButton.closest('tr').offsetTop)
    });
  }
}

export default new FormElement();

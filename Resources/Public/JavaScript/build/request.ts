import Utility from './utility.js';
import Notification from "@typo3/backend/notification.js";
import NProgress from 'nprogress';

class Request {

  constructor() {
    const self = this;
    const request = document.getElementById('request');
    const response = document.getElementById('response');
    const preparedEndpoint = document.getElementById('preparedEndpoint');
    const responseResult = document.getElementById('response-result');
    const responseArea = document.getElementById('response');

    const reusableRequestForm = document.querySelector('#reusableRequest');
    const submitBtn = reusableRequestForm?.querySelector('*[type="submit"]');
    const submitBtnValue = submitBtn?.querySelector('span.value');
    const deleteBtn = reusableRequestForm?.querySelector('button[class*="-delete"]');

    const flushCacheBtn = document.querySelectorAll('.reusable-request-table .flush-cache:not(.disabled)');
    const warmupCacheBtn = document.querySelectorAll('.reusable-request-table .warmup-cache');

    self.initializeArrayFormHandlers();

    /**
     *
     */
    self.handleFormSubmit('#request',
      function (form: FormData) {
      },
      function (response: any) {
        Utility.toggleElement(responseArea, 'block');
        [...document.querySelectorAll('.preparedEndpoint')].forEach(function (element) {
          if (element.tagName.toLowerCase() === 'input') {
            element.value = response.preparedEndpoint;
          } else {
            element.innerHTML = response.preparedEndpoint;
          }
        });

        document.getElementById('response-raw').innerHTML = response.responseRawFormatted;
        document.getElementById('response-modified').innerHTML = response.response;

        let parametersInput = document.querySelector('input[name="request#parameters"]'),
          acceptHeaderInput = document.querySelector('input[name="request#acceptHeader"]');

        parametersInput.value = response.arguments;
        acceptHeaderInput.value = response.acceptHeader;

        Notification.success('Temporary Request', 'The request has successfully been sent to the API', 5);
      });

    /**
     *
     */
    self.handleFormSubmit('#reusableRequest',
      function (form: FormData) {
        if (form.get('request#preparedEndpoint') === null) {
          Notification.error('Reusable Request', 'Missing prepared endpoint url. Use the "Temporary Request" to build the required endpoint.', 5);
          return false;
        }
      },
      function (response: any) {
        self.updateFormValues('#reusableRequest', response);
        Notification.success('Reusable Request', 'The reusable request was successfully saved', 5);
      });

    flushCacheBtn.forEach((btn) => {
      self.handleListActionClick(btn, (response) => {
        const badge = btn.closest('tr').querySelector('.cache .badge');

        btn.classList.add('disabled');

        badge.classList.remove('bg-success');
        badge.classList.add('bg-danger');

        badge.setAttribute('title', 'not cached');
      });
    });

    warmupCacheBtn.forEach((btn) => {
      self.handleListActionClick(btn, (response) => {
        const badge = btn.closest('tr').querySelector('.cache .badge');

        badge.classList.remove('bg-danger');
        badge.classList.add('bg-success');

        let expirationDate = new Date(response.data.expires * 1000).toLocaleString(
          'de-DE',
          {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
          }
        ).replace(',', '');

        badge.setAttribute('title', 'expiration: ' + expirationDate);

        btn.closest('.btn-group').querySelector('.flush-cache').classList.remove('disabled')
      });
    });

    /**
     * ToDo: simplify
     */
    // deleteBtn.addEventListener('click', (event) => {
    //   event.preventDefault();
    //
    //   self.deleteRequest(deleteBtn.getAttribute('data-url'), document.querySelector('#reusableRequest'))
    //
    //   reusableRequestForm.reset();
    //   submitBtnValue.innerHTML = 'Create';
    //
    //   deleteBtn.setAttribute('disabled', '');
    // });
  }

  /**
   *
   */
  initializeArrayFormHandlers() {
    const requestForm = document.querySelector('#request')
    const addArrayRowBtn = requestForm?.querySelectorAll('.request-input-array button.add')
    const removeArrayRowBtn = requestForm?.querySelectorAll('.request-input-array button.remove')

    addArrayRowBtn?.forEach((element) => {
      element.addEventListener('click', (event) => {
        this.addArrayInput(event)
      })
    })

    removeArrayRowBtn?.forEach((element) => {
      element.addEventListener('click', (event) => {
        this.removeArrayInput(event)
      })
    })

  }

  /**
   *
   * @param event
   */
  addArrayInput(event:Event) {
    event.preventDefault()

    const inputGroup = event.currentTarget?.parentNode
    const newInputGroup = inputGroup.parentNode.insertBefore(inputGroup.cloneNode(true), inputGroup.nextSibling)

    newInputGroup.querySelector('input:nth-child(2)').value = ''

    newInputGroup.querySelector('button.add')?.addEventListener('click', (subEvent: Event) => {
      this.addArrayInput(subEvent)
    })
    newInputGroup.querySelector('button.remove')?.addEventListener('click', (subEvent: Event) => {
      this.removeArrayInput(subEvent)
    })
  }

  /**
   *
   * @param event
   */
  removeArrayInput(event:Event) {
    event.preventDefault()

    const inputGroup = event.currentTarget?.parentNode
    inputGroup.remove()
  }

  /**
   *
   * @param elementSelector
   * @param success
   * @param validation
   * @param args
   */
  handleFormSubmit(elementSelector: string, validation: Function = function (form: FormData) {}, success: Function = function (response: any) {
  }, args = []) {

    if (document.querySelector(elementSelector)) {
      var form = document.querySelector(elementSelector);
    } else {
      return false;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      NProgress.configure({ parent: '.module-loading-indicator', showSpinner: true });
      NProgress.start();

      var formData = new FormData(form);
      /* Remove empty values from form data
       * https://stackoverflow.com/questions/8029532/how-to-prevent-submitting-the-html-forms-input-field-value-if-it-empty/64029534#64029534
       */
      for (let [name, value] of Array.from(formData.entries())) {
        if (value === '') formData.delete(name);
      }

      var submitBtn = form.querySelector('*[type="submit"]');
      var spinner = submitBtn.querySelector('span.spinner');
      var request = new XMLHttpRequest();

      if (validation(formData) === false) return false;

      submitBtn.disabled = true;
      spinner.classList.toggle('hide');

      request.open("POST", form.getAttribute('action'));
      request.send(formData);

      request.onload = function () {
        NProgress.done();
        if (request.status == 200) {
          var response = JSON.parse(request.response);

          if (args.successCallback && typeof args.successCallback === 'function') {
            args.successCallback(response);
            return;
          }

          submitBtn.disabled = false;
          spinner.classList.toggle('hide');

          success(response);
        } else {
          Notification.error('Ooops', 'An error occurred', 5);
          submitBtn.disabled = false;
          spinner.classList.toggle('hide');
        }
      };

      request.onerror = function () {
        NProgress.done();
        Notification.error('Ooops', 'An error occurred', 5);
        submitBtn.disabled = false;
        spinner.classList.toggle('hide');
      };
    });
  }

  handleListActionClick(element, success: Function = function (response: any) {}) {
    element.addEventListener('click', (event) => {
      event.preventDefault();

      const request = new XMLHttpRequest();

      request.open('GET', element.dataset.url);
      request.send();

      request.onload = () => {
        if (request.status == 200) {
          const response = JSON.parse(request.response);

          success(response);

          Notification.success('Success', response.message, 5);
        } else {
          Notification.error('Ooops', 'An error occurred', 5);
        }
      };

      request.onerror = () => {
        Notification.error('Ooops', 'An error occurred', 5);
      };
    });
  }

  deleteRequest(url:string, form: HTMLFormElement, success: Function = function (response: any) {}) {
    var request = new XMLHttpRequest();
    var formData = new FormData(form);

    request.open("POST", url);
    request.send(formData);

    request.onload = function () {
      if (request.status == 200) {
        var response = JSON.parse(request.response);
        success(response);
        Notification.success('Success', response.message, 5);
      } else {
        Notification.error('Ooops', 'An error occurred', 5);
      }
    }
    request.onerror = function () {
      Notification.error('Ooops', 'An error occurred', 5);
    }
  }

  /**
   *
   * @param formSelector
   * @param values
   */
  updateFormValues(formSelector: string, values: Array<any>) {
    let form = document.querySelector(formSelector),
      submitBtn = form.querySelector('*[type="submit"] span.value'),
      deleteBtn = form.querySelector('button[class*="-delete"]');

    Object.entries(values).forEach(([key, value]) => {
      let elementSelector = '[name="request#' + key + '"]';

      if (form.querySelector(elementSelector)) {
        let element = form.querySelector(elementSelector);

        if (key === 'parameters') {
          element.value = JSON.stringify(value);
        } else {
          element.value = value;
        }
      } else {
        return;
      }
    });

    submitBtn.innerHTML = 'Update';
    deleteBtn.removeAttribute('disabled');
  }
}

export default new Request();

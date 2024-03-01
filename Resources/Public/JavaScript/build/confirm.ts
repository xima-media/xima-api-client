import Modal from '@typo3/backend/modal.js'
import Notification from '@typo3/backend/notification.js'
import NProgress from 'nprogress'

class Confirm {
  constructor() {
    const confirmSelector = document.querySelectorAll('.confirm')

    ;[...confirmSelector].forEach(function (selector) {
      selector.addEventListener('click', function (event) {
        event.preventDefault()
        let element = event.target
        if (!event.target.classList.contains('confirm')) {
          element = event.target.closest('.confirm')
        }

        const url = element.hasAttribute('href') ? element.getAttribute('href') : element.getAttribute('data-url')
        const title = element.getAttribute('data-title')
        const content = element.getAttribute('data-content')
        const ok = element.getAttribute('data-button-ok-text')
        const ok_class = element.getAttribute('data-button-ok-class')
        const cancel = element.getAttribute('data-button-close-text')
        const severity = element.getAttribute('data-severity')
        const redirect = element.getAttribute('data-redirect')

        Modal.confirm(title, content, severity, [
          {
            text: ok,
            btnClass: ok_class || 'btn-default',
            active: true,
            trigger: function () {
              NProgress.configure({ parent: '.module-loading-indicator', showSpinner: true })
              NProgress.start()

              const request = new XMLHttpRequest()
              request.open('POST', url)
              request.send()

              request.onload = function () {
                if (request.status == 200) {
                  const response = JSON.parse(request.response)
                  Notification.success('Success', response.message, 5)

                  if (redirect) {
                    window.location.replace(redirect)
                  }
                } else {
                  Notification.error('Ooops', 'An error occurred', 5)
                }
              }
              request.onerror = function () {
                Notification.error('Ooops', 'An error occurred', 5)
              }

              Modal.dismiss()
            },
          },
          {
            text: cancel,
            trigger: function () {
              Modal.dismiss()
            },
          },
        ])
      })
    })
  }
}

export default new Confirm()

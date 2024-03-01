import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Notification from "@typo3/backend/notification.js";

class ClearPageAndRequestCache {
  constructor() {
    document.querySelector('.t3js-clear-page-and-request-cache').addEventListener('click', (event) => {
      event.preventDefault();

      let params = new URLSearchParams(window.location.search),
        id = params.get('id');

      new AjaxRequest(TYPO3.settings.ajaxUrls.clear_page_and_request_cache)
        .withQueryArguments({id: id})
        .get()
        .then(async function (response) {
          const resolved = await response.resolve();

          Notification.success(resolved.title, resolved.message, 5);
        });
    });
  }
}

export default new ClearPageAndRequestCache();

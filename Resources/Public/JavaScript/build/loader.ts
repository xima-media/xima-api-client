import NProgress from 'nprogress';

class Loader {

  constructor() {
    window.addEventListener('beforeunload', function (e) {
      NProgress.configure({ parent: '.module-loading-indicator', showSpinner: true });
      NProgress.start();
    });

    window.addEventListener('unload', function (e) {
      NProgress.done();
    });
  }
}

export default new Loader();

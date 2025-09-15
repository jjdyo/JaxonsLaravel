(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    var form = document.querySelector('form.user-filter-form');
    if (!form) return;

    var filter = document.getElementById('filter');
    var search = document.getElementById('search-input');

    if (!filter || !search) return;

    function toggle() {
      var isSearch = filter.value === 'search';
      if (isSearch) {
        search.classList.remove('hidden');
        search.removeAttribute('aria-hidden');
        search.removeAttribute('disabled');
        // do not auto-submit; let user type
        search.focus();
      } else {
        search.classList.add('hidden');
        search.setAttribute('aria-hidden', 'true');
        // disable to prevent accidental submission/tabbing
        search.setAttribute('disabled', 'disabled');
        // when not searching, clear stale term so it doesn't affect results
        search.value = '';
      }
    }

    // Auto-submit when filter changes except for the Search option
    filter.addEventListener('change', function () {
      var isSearch = filter.value === 'search';
      toggle();
      if (!isSearch) {
        form.submit();
      }
    });

    // Initialize state on load
    toggle();
  });
})();

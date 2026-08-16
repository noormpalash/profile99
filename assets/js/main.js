// Main JavaScript for Army Personnel System

document.addEventListener('DOMContentLoaded', function () {
  // Move all Bootstrap modals to <body> immediately to ensure they never get trapped in parent stacking contexts/backdrops
  document.querySelectorAll('.modal').forEach(function(modal) {
    if (modal.parentNode !== document.body) {
      document.body.appendChild(modal);
    }
  });

  const table = document.getElementById('personnelTable');
  if (table && window.jQuery && jQuery.fn.DataTable) {
    jQuery(table).DataTable();
  }

  const searchableInputs = document.querySelectorAll('[data-search-target], [data-search-target-class]');

  searchableInputs.forEach(function (input) {
    const targets = [];

    if (input.dataset.searchTarget) {
      const target = document.getElementById(input.dataset.searchTarget);
      if (target) {
        targets.push(target);
      }
    }

    if (input.dataset.searchTargetClass) {
      document.querySelectorAll('.' + input.dataset.searchTargetClass).forEach(function (target) {
        targets.push(target);
      });
    }

    if (!targets.length) {
      return;
    }

    const filterOptions = function () {
      const query = (input.value || '').trim().toLowerCase();

      targets.forEach(function (target) {
        if (target.tagName === 'SELECT') {
          Array.from(target.options).forEach(function (option, index) {
            if (index === 0 || option.value === '') {
              option.hidden = false;
              return;
            }

            option.hidden = query !== '' && !option.text.toLowerCase().includes(query);
          });
        } else {
          const checkItems = target.querySelectorAll('.form-check');
          checkItems.forEach(function (item) {
            const text = item.textContent.toLowerCase();
            item.style.display = (query === '' || text.includes(query)) ? '' : 'none';
          });
        }
      });
    };

    input.addEventListener('input', filterOptions);
  });
});

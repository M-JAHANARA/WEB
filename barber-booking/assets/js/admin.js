(() => {
  'use strict';
  document.querySelectorAll('[data-detail-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const row = document.getElementById(button.dataset.detailToggle);
      if (!row) return;
      const isOpen = row.classList.toggle('is-open');
      button.textContent = isOpen ? 'بستن جزئیات ↑' : 'جزئیات ↴';
    });
  });
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'از انجام این عملیات مطمئن هستید؟')) event.preventDefault();
      else {
        const button = form.querySelector('button[type="submit"]');
        if (button) { button.disabled = true; button.dataset.originalText = button.textContent; button.textContent = 'در حال ثبت…'; }
      }
    });
  });
})();

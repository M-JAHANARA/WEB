(() => {
  'use strict';
  const form = document.querySelector('#booking-form');
  if (!form) return;

  const serviceRadios = [...form.querySelectorAll('input[name="service_id"]')];
  const dateInput = document.querySelector('#booking-date');
  const timeInput = document.querySelector('#booking-time');
  const slotsContainer = document.querySelector('#time-slots');
  const countLabel = document.querySelector('#availability-count');
  const summaryService = document.querySelector('#summary-service');
  const summaryDuration = document.querySelector('#summary-duration');
  const summaryPrice = document.querySelector('#summary-price');
  const summaryDate = document.querySelector('#summary-date');
  const summaryTime = document.querySelector('#summary-time');
  const phoneInput = document.querySelector('#customer-phone');

  const toEnglishDigits = (value) => String(value).replace(/[۰-۹٠-٩]/g, (char) => {
    const fa = '۰۱۲۳۴۵۶۷۸۹';
    const ar = '٠١٢٣٤٥٦٧٨٩';
    if (fa.includes(char)) return String(fa.indexOf(char));
    return String(ar.indexOf(char));
  });

  const selectedService = () => serviceRadios.find((radio) => radio.checked);
  const localISODate = (date) => {
    const offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 10);
  };
  const formatPersianDate = (isoDate) => {
    if (!isoDate) return 'انتخاب نشده';
    const [year, month, day] = isoDate.split('-').map(Number);
    return new Intl.DateTimeFormat('fa-IR-u-ca-persian', { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(year, month - 1, day, 12));
  };
  const formatPrice = (value) => new Intl.NumberFormat('fa-IR').format(Number(value || 0));

  const updateServiceSummary = () => {
    const service = selectedService();
    if (!service) return;
    summaryService.textContent = service.dataset.name || 'انتخاب نشده';
    summaryDuration.textContent = `${new Intl.NumberFormat('fa-IR').format(Number(service.dataset.duration))} دقیقه`;
    summaryPrice.textContent = formatPrice(service.dataset.price);
    form.querySelectorAll('.service-option').forEach((option) => option.classList.toggle('is-selected', option.querySelector('input').checked));
    if (dateInput.value) loadSlots();
  };

  const clearSlots = (message) => {
    timeInput.value = '';
    summaryTime.textContent = 'انتخاب نشده';
    slotsContainer.replaceChildren();
    const item = document.createElement('div');
    item.className = 'slot-placeholder';
    item.textContent = message;
    slotsContainer.append(item);
    countLabel.textContent = '';
  };

  async function loadSlots() {
    const service = selectedService();
    const date = dateInput.value;
    summaryDate.textContent = formatPersianDate(date);
    if (!date || !service) {
      clearSlots('برای دیدن زمان‌های آزاد، یک تاریخ انتخاب کن.');
      return;
    }
    clearSlots('در حال بررسی زمان‌های آزاد…');
    countLabel.textContent = 'در حال بررسی…';
    try {
      const params = new URLSearchParams({ service_id: service.value, date });
      const response = await fetch(`api/availability.php?${params.toString()}`, { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || 'دریافت زمان‌ها ناموفق بود.');
      slotsContainer.replaceChildren();
      if (!data.slots.length) {
        clearSlots(data.message || 'برای این تاریخ زمان آزادی پیدا نشد.');
        countLabel.textContent = '۰ زمان آزاد';
        return;
      }
      data.slots.forEach((slot) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'time-slot';
        button.textContent = slot.label;
        button.dataset.value = slot.value;
        button.setAttribute('aria-pressed', 'false');
        button.addEventListener('click', () => {
          slotsContainer.querySelectorAll('.time-slot').forEach((other) => {
            other.classList.remove('is-selected');
            other.setAttribute('aria-pressed', 'false');
          });
          button.classList.add('is-selected');
          button.setAttribute('aria-pressed', 'true');
          timeInput.value = slot.value;
          summaryTime.textContent = slot.label;
        });
        slotsContainer.append(button);
      });
      countLabel.textContent = `${new Intl.NumberFormat('fa-IR').format(data.slots.length)} زمان آزاد`;
    } catch (error) {
      clearSlots(error.message || 'ارتباط برقرار نشد. دوباره تلاش کن.');
      countLabel.textContent = 'خطا در دریافت زمان‌ها';
    }
  }

  dateInput.min = dateInput.dataset.minDate || localISODate(new Date());
  if (dateInput.dataset.maxDate) dateInput.max = dateInput.dataset.maxDate;
  else { const maxDate = new Date(); maxDate.setDate(maxDate.getDate() + 90); dateInput.max = localISODate(maxDate); }
  dateInput.addEventListener('change', loadSlots);
  serviceRadios.forEach((radio) => radio.addEventListener('change', updateServiceSummary));
  phoneInput.addEventListener('input', () => {
    phoneInput.value = toEnglishDigits(phoneInput.value).replace(/[^0-9]/g, '').slice(0, 11);
  });

  form.addEventListener('submit', (event) => {
    const service = selectedService();
    const name = document.querySelector('#customer-name').value.trim();
    const phone = toEnglishDigits(phoneInput.value.trim());
    let message = '';
    if (!service) message = 'لطفاً یک خدمت انتخاب کن.';
    else if (!dateInput.value) message = 'لطفاً تاریخ مراجعه را انتخاب کن.';
    else if (!timeInput.value) message = 'لطفاً یکی از ساعت‌های آزاد را انتخاب کن.';
    else if (name.length < 2) message = 'نام و نام خانوادگی را وارد کن.';
    else if (!/^09\d{9}$/.test(phone)) message = 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.';
    else if (!form.querySelector('input[name="consent"]').checked) message = 'برای ادامه، رضایت مربوط به پیامک نوبت را تأیید کن.';
    if (message) {
      event.preventDefault();
      window.alert(message);
      return;
    }
    const submitButton = form.querySelector('.submit-booking');
    submitButton.disabled = true;
    submitButton.innerHTML = 'در حال ثبت درخواست…';
  });

  updateServiceSummary();
  if (dateInput.value) loadSlots();
})();
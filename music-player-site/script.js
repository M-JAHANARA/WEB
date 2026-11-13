'use strict';

/*
 * JahanMusic download settings
 * Replace these values with the actual URLs/files for your app releases.
 * Local examples work when the files are placed in the /downloads folder.
 */
const JAHANMUSIC_DOWNLOADS = {
  android: 'downloads/jahanmusic.apk',
  windows: 'downloads/JahanMusic-Setup.exe',
  // Put the direct App Store product URL here, e.g. https://apps.apple.com/app/idYOUR_APP_ID
  ios: ''
};

document.addEventListener('DOMContentLoaded', () => {
  const currentYear = document.querySelector('#current-year');
  if (currentYear) currentYear.textContent = String(new Date().getFullYear());

  // Bootstrap mobile menu: close it after navigating to a section.
  const navElement = document.querySelector('#mainNavigation');
  if (navElement && window.bootstrap) {
    const navCollapse = bootstrap.Collapse.getOrCreateInstance(navElement, { toggle: false });
    navElement.querySelectorAll('a[href^="#"]').forEach((link) => {
      link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 991.98px)').matches && navElement.classList.contains('show')) {
          navCollapse.hide();
        }
      });
    });
  }

  // Small reusable Bootstrap toast for missing/configuration-only download URLs.
  const toastElement = document.querySelector('#siteToast');
  const toast = toastElement && window.bootstrap
    ? bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 3200 })
    : null;
  const showToast = (message) => {
    const body = toastElement?.querySelector('.toast-body');
    if (!toastElement || !toast || !body) {
      window.alert(message);
      return;
    }
    body.textContent = message;
    toast.show();
  };

  document.querySelectorAll('[data-download-platform]').forEach((link) => {
    const platform = link.getAttribute('data-download-platform');
    const configuredUrl = JAHANMUSIC_DOWNLOADS[platform];
    if (configuredUrl) link.setAttribute('href', configuredUrl);

    link.addEventListener('click', (event) => {
      const targetUrl = JAHANMUSIC_DOWNLOADS[platform];
      if (!targetUrl) {
        event.preventDefault();
        const platformNames = { android: 'اندروید', windows: 'ویندوز', ios: 'iOS' };
        showToast(`لینک دانلود نسخه ${platformNames[platform] || platform} را در ابتدای فایل script.js وارد کن.`);
        return;
      }
      // Avoid following an untouched placeholder anchor for the iOS release.
      if (targetUrl.startsWith('#')) {
        event.preventDefault();
        showToast('لینک دانلود این نسخه هنوز تنظیم نشده است.');
      }
    });
  });

  // Reveal sections as they enter the viewport, while preserving accessibility settings.
  const revealItems = document.querySelectorAll('.reveal');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if ('IntersectionObserver' in window && !reduceMotion) {
    const observer = new IntersectionObserver((entries, activeObserver) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          activeObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealItems.forEach((element) => observer.observe(element));
  } else {
    revealItems.forEach((element) => element.classList.add('is-visible'));
  }

  // Slightly reduce the navigation height once the visitor has scrolled.
  const navbar = document.querySelector('.jm-navbar');
  const updateNavbar = () => {
    if (!navbar) return;
    navbar.classList.toggle('navbar-scrolled', window.scrollY > 18);
  };
  updateNavbar();
  window.addEventListener('scroll', updateNavbar, { passive: true });
});

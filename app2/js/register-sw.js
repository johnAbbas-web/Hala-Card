// ============================================================
//  HALA PWA — Service Worker Registration
//  Add this as a <script> tag in any page that needs offline support,
//  OR just include <script src="js/register-sw.js"></script> in every page.
// ============================================================

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js')
      .then(reg => console.log('Hala SW registered:', reg.scope))
      .catch(err => console.warn('SW registration failed:', err));
  });
}

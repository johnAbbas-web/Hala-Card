// ============================================================
//  HALA PWA — Service Worker
//  Enables offline caching so the app loads fast
// ============================================================

const CACHE_NAME = 'hala-v1';

// Files to cache on install
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/welcome.html',
  '/login.html',
  '/signup.html',
  '/home.html',
  '/map.html',
  '/pass.html',
  '/profile.html',
  '/css/styles.css',
  '/js/firebase-config.js',
  '/js/auth.js',
  '/assets/logo.png',
  '/manifest.json'
];

// Install — cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activate — clear old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Fetch — serve from cache, fall back to network
self.addEventListener('fetch', event => {
  // Skip non-GET requests and Firebase/Mapbox API calls
  if (event.request.method !== 'GET') return;
  if (event.request.url.includes('firebaseapp.com') ||
      event.request.url.includes('googleapis.com') ||
      event.request.url.includes('mapbox.com')) return;

  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;
      return fetch(event.request).then(response => {
        // Cache new successful responses
        if (response && response.status === 200 && response.type === 'basic') {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      }).catch(() => {
        // Offline fallback
        if (event.request.mode === 'navigate') {
          return caches.match('/index.html');
        }
      });
    })
  );
});

const CACHE_NAME = 'sandys-gym-v1';
const urlsToCache = [
  './index.php',
  './assets/css/style.css',
  './assets/css/bootstrap.min.css',
  './assets/img/logo.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});

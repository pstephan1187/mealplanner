// No-op service worker — required for PWA installability.
// No caching or offline support; just satisfies the install criteria.
self.addEventListener('fetch', () => {});

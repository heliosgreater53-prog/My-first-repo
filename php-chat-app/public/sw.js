self.addEventListener('install', () => {
  console.log('[SW] install');
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  console.log('[SW] activate');
  e.waitUntil(self.clients.claim());
});

self.addEventListener('message', (event) => {
  console.log('[SW] message received', event.data);
  if (!event.data || event.data.type !== 'CHAT_MESSAGE') {
    return;
  }

  const { roomName, authorName, messageBody, roomSlug, layoutMode } = event.data;

  self.registration.showNotification('LetsChat - New Message', {
    body: `${authorName} in ${roomName}: ${messageBody}`,
    icon: '/assets/images/favicon.png',
    badge: '/assets/images/favicon.png',
    tag: roomSlug,
    renotify: true,
    requireInteraction: false,
    data: { roomSlug },
  });
  console.log('[SW] notification shown');
});

self.addEventListener('notificationclick', (event) => {
  console.log('[SW] notificationclick', event.notification.data);
  event.notification.close();

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      const slug = event.notification.data?.roomSlug || '';
      const mode = event.notification.data?.layoutMode || '';
      const targetUrl =
        slug === 'home' || mode === 'feed'
          ? '/feed'
          : `/chat?room=${encodeURIComponent(slug)}`;

      for (const client of clientList) {
        if ((client.url.includes('/feed') || client.url.includes('/chat')) && 'focus' in client) {
          return client.focus();
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(targetUrl);
      }
    })
  );
});

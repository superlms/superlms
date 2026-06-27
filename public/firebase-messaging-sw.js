/* Firebase Cloud Messaging service worker — handles background web push. */
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyBRZcETdNS1gcdedGB_IW8KwOSyUUXTa6w',
    authDomain: 'superlms-lms-57e8c.firebaseapp.com',
    projectId: 'superlms-lms-57e8c',
    storageBucket: 'superlms-lms-57e8c.firebasestorage.app',
    messagingSenderId: '682389969874',
    appId: '1:682389969874:web:f9e4948399cdc52cc5c60b',
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
    const notification = payload.notification || {};
    self.registration.showNotification(notification.title || 'SuperLMS', {
        body: notification.body || '',
        icon: '/website-image/Group 11525.png',
        data: payload.data || {},
    });
});

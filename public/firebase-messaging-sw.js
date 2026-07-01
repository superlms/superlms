/* Firebase Cloud Messaging service worker — handles background web push. */
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyBmS5hLvwYWXVvnAQBsCsvMeT73kJZ0Hzg',
    authDomain: 'super-lms-48c90.firebaseapp.com',
    projectId: 'super-lms-48c90',
    storageBucket: 'super-lms-48c90.firebasestorage.app',
    messagingSenderId: '1003028261382',
    appId: '1:1003028261382:web:26be364e5bb6792d933187',
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

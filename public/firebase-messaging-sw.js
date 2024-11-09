importScripts('https://www.gstatic.com/firebasejs/10.5.2/firebase-app-compat.js')
importScripts('https://www.gstatic.com/firebasejs/10.5.2/firebase-messaging-compat.js')

// Initialize Firebase in the service worker
firebase.initializeApp({
    apiKey: "AIzaSyBlSfmSaPpJAsDjPeW-UOS35KgIHN2Id4s",
    authDomain: "chat-bot-acreditar.firebaseapp.com",
    projectId: "chat-bot-acreditar",
    storageBucket: "chat-bot-acreditar.firebasestorage.app",
    messagingSenderId: "1086360314150",
    appId: "1:1086360314150:web:10af74f63b6268486ce1e7",
    measurementId: "G-JJSQ3E9G7L"
});




const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage(function (payload) {
    console.log('Received background message ', payload);

    
    // const notificationTitle = payload.notification.title;
    // const notificationOptions = {
    //     body: payload.notification.body,
    //     icon: payload.notification.icon
    // };

    // self.registration.showNotification(notificationTitle, notificationOptions);
});

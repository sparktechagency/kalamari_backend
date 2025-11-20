importScripts("https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js");
importScripts(
    "https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js"
);

const firebaseConfig = {
    apiKey: "AIzaSyBxhY_1X6xvwxnOj3vcNhwqCUXKrtjOL-8",
    authDomain: "kalamari-app-push.firebaseapp.com",
    projectId: "kalamari-app-push",
    storageBucket: "kalamari-app-push.firebasestorage.app",
    messagingSenderId: "435113116648",
    appId: "1:435113116648:web:1dc520f0a12ae3ac67da5d",
    measurementId: "G-HK5G1TMYQF",
};
//firebase initialize
firebase.initializeApp(firebaseConfig);

// Retrieve firebase messaging
const messaging = firebase.messaging();

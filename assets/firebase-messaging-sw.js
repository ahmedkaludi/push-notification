importScripts("https://www.gstatic.com/firebasejs/6.2.4/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/6.2.4/firebase-messaging.js");

var config ={
  apiKey: "AIzaSyCt8RVdgpFPaoTmzx84gAgi6zzVCpGlnZg",
  authDomain: "fir-pushnotification-1940a.firebaseapp.com",
  databaseURL: "https://fir-pushnotification-1940a.firebaseio.com",
  projectId: "fir-pushnotification-1940a",
  storageBucket: "fir-pushnotification-1940a.appspot.com",
  messagingSenderId: "1231518440",
  appId: "1:1231518440:web:9efeed716a5da8341aa75d"
};
if (!firebase.apps.length) {firebase.initializeApp(config);}		  		  		  
const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function(payload) {  
const notificationTitle = payload.data.title;
const notificationOptions = {
				body: payload.data.body,
				icon: payload.data.icon,
				vibrate: [100, 50, 100],
				data: {
					dateOfArrival: Date.now(),
					primarykey: payload.data.primarykey,
					url : payload.data.url
				  },
				}
	return self.registration.showNotification(notificationTitle, notificationOptions); 

});

self.addEventListener("notificationclose", function(e) {
var notification = e.notification;
var primarykey = notification.data.primarykey;
console.log("Closed notification: " + primarykey);
});

self.addEventListener("notificationclick", function(e) {
var notification = e.notification;
var primarykey = notification.data.primarykey;
var action = e.action;
if (action === "close") {
  notification.close();
} else {
  clients.openWindow(notification.data.url);
  notification.close();
}
});  
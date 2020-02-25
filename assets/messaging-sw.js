importScripts("https://www.gstatic.com/firebasejs/7.8.2/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/7.8.2/firebase-analytics.js");
importScripts("https://www.gstatic.com/firebasejs/7.8.2/firebase-messaging.js");
var pnScriptSetting = {{pnScriptSetting}}
var config=pnScriptSetting.pn_config;   
if (!firebase.apps.length) {firebase.initializeApp(config);}		  		  		  
const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function(payload) {  
const notificationTitle = payload.data.title;
const notificationOptions = {
				body: payload.data.body,
				icon: payload.data.icon,
				image: payload.data.image,
				vibrate: [100, 50, 100],
				data: {
					dateOfArrival: Date.now(),
					primarykey: payload.data.currentCampaign,
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
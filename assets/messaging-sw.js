importScripts("https://www.gstatic.com/firebasejs/7.8.2/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/7.8.2/firebase-analytics.js");
importScripts("https://www.gstatic.com/firebasejs/7.8.2/firebase-messaging.js");
var pnScriptSetting = {{pnScriptSetting}}
var config=pnScriptSetting.pn_config;   
if (!firebase.apps.length) {firebase.initializeApp(config);}		  		  		  
const messaging = firebase.messaging();
var messageCount = 0;

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
				messageCount += 1;
				setBadge(messageCount);
	// return self.registration.showNotification(notificationTitle, notificationOptions);
	self.addEventListener('push', function(event) {
		const testpush = self.registration.showNotification(notificationTitle, notificationOptions);
		event.waitUntil(testpush);
	});

});

self.addEventListener("notificationclose", function(e) {
var notification = e.notification;
var primarykey = notification.data.primarykey;
	messageCount -= 1;
	if(messageCount>0){
		setBadge(messageCount);
	}else{
		clearBadge();
	}
console.log("Closed notification: " + primarykey);
});

self.addEventListener("notificationclick", function(e) {
var notification = e.notification;
// var primarykey = notification.data.primarykey;
var action = e.action;
if (action === "close") {
  notification.close();
} else {
  clients.openWindow(notification.data.url);
  notification.close();
}
	messageCount -= 1;
	if(messageCount>0){
		setBadge(messageCount);
	}else{
		clearBadge();
	}
});  

function setBadge(...args) {
  if (navigator.setAppBadge) {
    navigator.setAppBadge(...args);
  } else if (navigator.setExperimentalAppBadge) {
    navigator.setExperimentalAppBadge(...args);
  } else if (window.ExperimentalBadge) {
    window.ExperimentalBadge.set(...args);
  }
}

// Wrapper to support first and second origin trial
// See https://web.dev/badging-api/ for details.
function clearBadge() {
  if (navigator.clearAppBadge) {
    navigator.clearAppBadge();
  } else if (navigator.clearExperimentalAppBadge) {
    navigator.clearExperimentalAppBadge();
  } else if (window.ExperimentalBadge) {
    window.ExperimentalBadge.clear();
  }
}
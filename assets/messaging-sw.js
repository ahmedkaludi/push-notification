importScripts("https://www.gstatic.com/firebasejs/7.8.2/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/7.8.2/firebase-messaging.js");
var pnScriptSetting = {{pnScriptSetting}}
var messageCount = 0;
var unreadCount = 0;

// CRITICAL: Register notificationclick FIRST, before any other code
// This ensures it's always available when notifications are clicked
self.addEventListener("notificationclick", function(e) {
	var notification = e.notification;
	if (!notification) {
		console.error("PN Error: No notification object in click event");
		return;
	}
	
	messageCount -= 1;
	unreadCount -= 1;
	
	// Close notification immediately
	notification.close();
	
	// Get notification data safely
	// Firebase Messaging may wrap data in FCM_MSG, so handle both cases
	var notificationData = notification.data || {};
	
	// Check if data is wrapped in FCM_MSG (Firebase Messaging structure)
	if (notificationData.FCM_MSG && notificationData.FCM_MSG.data) {
		notificationData = notificationData.FCM_MSG.data;
	}
	
	var campaignId = notificationData.currentCampaign || notificationData.primarykey;
	var targetUrl = notificationData.url || notificationData.click_url;
	
	// Validate required data
	if (!campaignId) {
		console.error("PN Error: No campaign ID found", notificationData);
	}
	if (!targetUrl) {
		console.error("PN Error: No URL found", notificationData);
	}
	if (!pnScriptSetting || !pnScriptSetting.ajax_url) {
		console.error("PN Error: pnScriptSetting.ajax_url not defined", {
			hasPnScriptSetting: !!pnScriptSetting,
			hasAjaxUrl: !!(pnScriptSetting && pnScriptSetting.ajax_url)
		});
	}
	
	// CRITICAL: Use waitUntil to keep service worker alive
	e.waitUntil(
		Promise.all([
			// Open window (if URL exists)
			targetUrl ? clients.openWindow(targetUrl).then(function() {
				console.log("PN: Window opened", targetUrl);
				return true;
			}).catch(function(err) {
				console.error("PN Error: Failed to open window", err);
				return null;
			}) : Promise.resolve(null),
			
			// Send click tracking
			(pnScriptSetting && pnScriptSetting.ajax_url && campaignId) ? 
				fetch(pnScriptSetting.ajax_url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `campaign=${encodeURIComponent(campaignId)}&nonce=${encodeURIComponent(pnScriptSetting.nonce)}&action=pn_noteclick_subscribers`
				})
				.then(function(response) {
					console.log("PN: Fetch response", response.status);
					if (!response.ok) {
						throw new Error('Network response was not ok: ' + response.status);
					}
					return response.text();
				})
				.then(function(data) {
					console.log("PN Success: Click tracked", { campaignId: campaignId, response: data });
					return data;
				})
				.catch(function(error) {
					console.error("PN Error: Click tracking failed", { 
						campaignId: campaignId, 
						error: error.message || error,
						ajax_url: pnScriptSetting.ajax_url
					});
					return null;
				})
			: Promise.resolve().then(function() {
				console.warn("PN Warning: Skipping fetch - missing data", {
					hasAjaxUrl: !!(pnScriptSetting && pnScriptSetting.ajax_url),
					hasCampaignId: !!campaignId
				});
			})
		]).then(function(results) {
			if(messageCount > 0 && unreadCount > 0){
				setBadge(messageCount);
			} else {
				clearBadge();
			}
			console.log("PN: Handler completed", { campaignId: campaignId });
		}).catch(function(error) {
			console.error("PN Error: Handler failed", error);
			if(messageCount > 0 && unreadCount > 0){
				setBadge(messageCount);
			} else {
				clearBadge();
			}
		})
	);
});

// CRITICAL: Activate service worker immediately
self.addEventListener('install', function(event) {
	console.log("SW: Install event");
	self.skipWaiting();
});

self.addEventListener('activate', function(event) {
	console.log("SW: Activate event");
	event.waitUntil(clients.claim());
});

var config=pnScriptSetting.pn_config;   

if (!firebase.apps.length) {
	firebase.initializeApp(config);
	console.log("=== FIREBASE INITIALIZED ===");
} else {
	console.log("=== FIREBASE ALREADY INITIALIZED ===");
}

const messaging = firebase.messaging();
console.log("=== MESSAGING OBJECT CREATED ===");

console.log("=== REGISTERING BACKGROUND MESSAGE HANDLER ===");
messaging.setBackgroundMessageHandler(function(payload) {
	console.log("=== BACKGROUND MESSAGE HANDLER CALLED ===", {
		timestamp: Date.now(),
		hasPayload: !!payload,
		payloadKeys: payload ? Object.keys(payload) : [],
		hasData: !!(payload && payload.data),
		dataKeys: (payload && payload.data) ? Object.keys(payload.data) : []
	});
	
	try {
		if (!payload || !payload.data) {
			console.error("=== ERROR: Invalid payload ===", payload);
			return Promise.reject(new Error("Invalid payload"));
		}
		
		const notificationTitle = payload.data.title;
		const campaignId = payload.data.currentCampaign;
		
		console.log("=== CREATING NOTIFICATION ===", {
			title: notificationTitle,
			campaignId: campaignId
		});
		
		// CRITICAL: Add tag for proper notification tracking
		const notificationTag = 'pn-' + (campaignId || Date.now());
		
		const notificationOptions = {
			body: payload.data.body,
			icon: payload.data.icon,
			image: payload.data.image,
			vibrate: [100, 50, 100],
			tag: notificationTag, // CRITICAL: Tag allows proper click tracking
			data: {
				dateOfArrival: Date.now(),
				primarykey: campaignId,
				currentCampaign: campaignId,
				url: payload.data.url || payload.data.click_url
			},
		}
		messageCount += 1;
		setBadge(messageCount);
		
		console.log("SW: Background message received", { campaignId: campaignId, tag: notificationTag });

		return self.registration.showNotification(notificationTitle, notificationOptions)
			.then(function() {
				console.log("=== NOTIFICATION SHOWN SUCCESSFULLY ===", { title: notificationTitle, tag: notificationTag });
			})
			.catch(function(error) {
				console.error("=== ERROR SHOWING NOTIFICATION ===", error);
				throw error;
			});
	} catch (error) {
		console.error("=== ERROR IN BACKGROUND HANDLER ===", error);
		return Promise.reject(error);
	}
});

self.addEventListener("notificationclose", function(e) {
	var notification = e.notification;
	var notificationData = notification.data || {};
	
	// Check if data is wrapped in FCM_MSG (Firebase Messaging structure)
	if (notificationData.FCM_MSG && notificationData.FCM_MSG.data) {
		notificationData = notificationData.FCM_MSG.data;
	}
	
	var primarykey = notificationData.primarykey || notificationData.currentCampaign;
	messageCount -= 1;
	unreadCount -= 1;
	if(messageCount>0 && unreadCount > 0){
		setBadge(messageCount);
	}else{
		clearBadge();
	}
	console.log("SW: Notification closed", primarykey);
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

self.addEventListener("push", (event) => {
	unreadCount += 1;	
	// Set or clear the badge.
	if (navigator.setAppBadge) {
		if (unreadCount && unreadCount > 0) {
			navigator.setAppBadge(unreadCount);
		} else {
			navigator.clearAppBadge();
		}
	}
});
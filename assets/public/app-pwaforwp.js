firebase.analytics();	  

function pushnotification_load_messaging(){
  // [START refresh_token]
  // Callback fired if Instance ID token is updated.
  messaging.onTokenRefresh(() => {
    messaging.getToken().then((refreshedToken) => {
      console.log('Token refreshed.');
      // Indicate that the new Instance ID token has not yet been sent to the
      // app server.
      push_notification_setTokenSentToServer(false);
      // Send Instance ID token to app server.
      sendTokenToServer(refreshedToken);
      // [START_EXCLUDE]
      // Display new Instance ID token and clear UI of all previous messages.
      resetUI();
      // [END_EXCLUDE]
    }).catch((err) => {
      console.log('Unable to retrieve refreshed token ', err);
      showToken('Unable to retrieve refreshed token ', err);
    });
  });
  // [END refresh_token]

	if(PWAforwpreadCookie("pn_notification_block")==null){
		var wrapper = document.getElementsByClassName("pn-wrapper");
		if(wrapper){ wrapper[0].style.display="flex"; }
	}
	document.getElementById("pn-activate-permission_link_nothanks").addEventListener("click", function(){
		document.cookie = "pn_notification_block=true";
		var wrapper = document.getElementsByClassName("pn-wrapper");
		if(wrapper){ wrapper[0].style.display="none"; }
	})
	document.getElementById("pn-activate-permission_link").addEventListener("click",  function (){pn_allowed_notification()});
	var allowed = document.getElementsByClassName("pn-activate-allow-permission-subscripbe");
	for (var i = allowed.length - 1; i >= 0; i--) {
		allowed[i].addEventListener("click", function (){pn_allowed_notification()});
	}

	 messaging.onMessage(function(payload) {
		 console.log('Message received. ', payload);
		 
		 notificationTitle = payload.data.title;
			notificationOptions = {
			body: payload.data.body,
			icon: payload.data.icon
			}
			var notification = new Notification(notificationTitle, notificationOptions); 
				notification.onclick = function(event) {
				event.preventDefault();
				window.open(payload.data.url, '_blank');

				var xhttp = new XMLHttpRequest();
				xhttp.open("POST", pnScriptSetting.ajax_url, true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send('campaign='+payload.data.currentCampaign+'&nonce='+pnScriptSetting.nonce+'&action=pn_noteclick_subscribers');

				notification.close();
				}
		});

}

 function pn_allowed_notification(){
		var wrapper = document.getElementsByClassName("pn-wrapper");
		if(wrapper){ wrapper[0].style.display="none"; }
		messaging.requestPermission().then(function() {
			console.log("Notification permission granted.");
			document.cookie = "notification_permission=granted";
			document.cookie = "pn_notification_block=true";
			if(push_notification_isTokenSentToServer()){
				console.log('Token already saved');
			}else{
				push_notification_getRegToken();
			}                                   
		}).catch(function(err) {
			if(Notification && Notification.permission=='denied'){
				console.log("Notification permission denied.");
				document.cookie = "pn_notification_block=true";
			}else{
				console.log("Unable to get permission to notify.", err);
			}
		});
	}

function push_notification_getRegToken(argument){
	 
	messaging.getToken().then(function(currentToken) {
	  if (currentToken) {                      
	   push_notification_saveToken(currentToken);
	   console.log(currentToken);
		push_notification_setTokenSentToServer(true);
	  } else {                       
		console.log('No Instance ID token available. Request permission to generate one.');                       
		push_notification_setTokenSentToServer(false);
	  }
	}).catch(function(err) {
	  console.log('An error occurred while retrieving token. ', err);                      
	  push_notification_setTokenSentToServer(false);
	});
}
function push_notification_setTokenSentToServer(sent) {
 window.localStorage.setItem('sentToServer', sent ? '1' : '0');
}

function push_notification_isTokenSentToServer() {
return window.localStorage.getItem('sentToServer') === '1';
}

// Send the Instance ID token your application server, so that it can:
// - send messages back to this app
// - subscribe/unsubscribe the token from topics
function sendTokenToServer(currentToken) {
	if (!push_notification_isTokenSentToServer()) {
	  console.log('Sending token to server...');
	  push_notification_saveToken(currentToken);
	  
	} else {
	  console.log('Token already sent to server so won\'t send it again ' +
		  'unless it changes');
	}

}

function push_notification_saveToken(currentToken){
  var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
	  if (this.readyState == 4 && this.status == 200) {
		if(this.responseText.status==200){
			push_notification_setTokenSentToServer(true);
		}
		console.log(this.responseText);
	  }
	};
	console.log(currentToken);
	var grabOs = pushnotificationFCMGetOS();
	var browserClient = pushnotificationFCMbrowserclientDetector();
	xhttp.open("POST", pnScriptSetting.ajax_url, true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send('token_id='+currentToken+'&user_agent='+browserClient+'&os='+grabOs+'&nonce='+pnScriptSetting.nonce+'&action=pn_register_subscribers');
}              


var pushnotificationFCMbrowserclientDetector  = function (){
	var browserClient = '';

	// Opera 8.0+
	var isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;

	// Firefox 1.0+
	var isFirefox = typeof InstallTrigger !== 'undefined';

	// Safari 3.0+ "[object HTMLElementConstructor]" 
	var isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || (typeof safari !== 'undefined' && safari.pushNotification));

	// Internet Explorer 6-11
	var isIE = /*@cc_on!@*/false || !!document.documentMode;

	// Edge 20+
	var isEdge = !isIE && !!window.StyleMedia;

	// Chrome 1 - 71
	var isChrome = !!window.chrome && (!!window.chrome.webstore || !!window.chrome.runtime);

	// Blink engine detection
	var isBlink = (isChrome || isOpera) && !!window.CSS;


	if(navigator.userAgent.match('CriOS')){
		browserClient = 'Chrome ios';
		return browserClient;
	}
	var isSafari = !!navigator.userAgent.match(/Version\/[\d\.]+.*Safari/);
	var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
	if (isSafari && iOS) {
		browserClient = 'Safari ios';
		return browserClient;
	} else if(isSafari) {
		browserClient = 'Safari';
		return browserClient;
	}else if(isFirefox){
		browserClient = 'Firefox';
		return browserClient;
	}else if(isChrome){
		browserClient = 'Chrome';
		return browserClient;
	}else if(isOpera){
		browserClient = 'Opera';
		return browserClient;
	}else if(isIE ){
		browserClient = 'IE';
		return browserClient;
	}else if(isEdge ){
		browserClient = 'Edge';
		return browserClient;
	}else if( isBlink ){
		browserClient = 'Blink';
		return browserClient;
	}


}

var pushnotificationFCMGetOS = function() {
  var userAgent = window.navigator.userAgent,
      platform = window.navigator.platform,
      macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'],
      windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'],
      iosPlatforms = ['iPhone', 'iPad', 'iPod'],
      os = null;

  if (macosPlatforms.indexOf(platform) !== -1) {
    os = 'Mac OS';
  } else if (iosPlatforms.indexOf(platform) !== -1) {
    os = 'iOS';
  } else if (windowsPlatforms.indexOf(platform) !== -1) {
    os = 'Windows';
  } else if (/Android/.test(userAgent)) {
    os = 'Android';
  } else if (!os && /Linux/.test(platform)) {
    os = 'Linux';
  }

  return os;
}

if (Notification.permission !== "granted") {
	document.cookie = "notification_permission=granted";
}
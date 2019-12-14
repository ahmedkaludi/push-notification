jQuery(document).ready(function($){
	jQuery("#user_auth_vadation").click(function(){
		var self = jQuery(this);
		var tokenKey = jQuery("#user_auth_token_key").val().trim();
		//console.log(tokenKey);
		if(tokenKey==''){
			alert("Please enter valid token");
			return false;
		}
		self.addClass('button updating-message');
		var messagediv = self.parents('fieldset').find(".resp_message")
		messagediv.html("");
		jQuery.ajax({
			url: ajaxurl,
			method: "post",
			dataType: 'json',
			data: { user_token: tokenKey, action: "pn_verify_user", nonce: pn_setings.remote_nonce },
			success: function(response){
				
				if(response.status==200){
					messagediv.html(response.message);
					messagediv.css({"color": "green"})

					window.location.reload();
				}else{
					messagediv.html(response.message);
					messagediv.css({"color": "red"})
				}
				self.removeClass('updating-message');
			},
			error:function(response){
				var messagediv = self.parents('fieldset').find(".resp_message")
				messagediv.html(response.responseJSON.message)
				messagediv.css({"color": "red"})

			}
		})
	})

	jQuery("#pn-remove-apikey").click(function(){
		var self = jQuery(this);
		self.addClass('button updating-message');
		jQuery.ajax({
			url: ajaxurl,
			method: "post",
			dataType: 'json',
			data: { action: "pn_revoke_keys", nonce: pn_setings.remote_nonce },
			success: function(response){
				
				if(response.status==200){
					self.after("&nbsp; "+response.message);
					
					window.location.reload();
				}else{
					self.after(response.message);
				}
				self.removeClass('updating-message');
			},
			error:function(response){
				var messagediv = self.parents('fieldset').find(".resp_message")
				messagediv.html(response.responseJSON.message)
				messagediv.css({"color": "red"})

			}
		})
	})
	jQuery("#grab-subscribers-data").click(function(){
		var self = jQuery(this);
		self.addClass('button updating-message');
		jQuery.ajax({
			url: ajaxurl,
			method: "post",
			dataType: 'json',
			data: { action: "pn_subscribers_data", nonce: pn_setings.remote_nonce },
			success: function(response){
				
				if(response.status==200){
					self.after("&nbsp; "+response.message);
					
					window.location.reload();
				}else{
					self.after(response.message);
				}
				self.removeClass('updating-message');
			},
			error:function(response){
				var messagediv = self.parents('fieldset').find(".resp_message")
				messagediv.html(response.responseJSON.message)
				messagediv.css({"color": "red"})

			}
		})
	})


	jQuery("#pn-send-custom-notification").click(function(){
		var self = jQuery(this);
		var title 	 = jQuery('#notification-title').val();
		var link_url 	 = jQuery('#notification-link').val();
		var image_url = jQuery('#notification-imageurl').val();
		var message  = jQuery('#notification-message').val();
		self.addClass('button updating-message');
		jQuery.ajax({
			url: ajaxurl,
			method: "post",
			dataType: 'json',
			data: { action: "pn_send_notification", nonce: pn_setings.remote_nonce, 
				title: title,
				link_url: link_url,
				image_url: image_url,
				message: message
				},
			success: function(response){
				
				if(response.status==200){
					jQuery(".pn-send-messageDiv").text("&nbsp; "+response.message).css({"color":"green"});
					
					jQuery('#notification-title').val("");
					jQuery('#notification-link').val("");
					Query('#notification-imageurl').val("");
					jQuery('#notification-message').val("");
				}else{
					jQuery(".pn-send-messageDiv").text("&nbsp; "+response.message).css({"color":"green"});
				}
				self.removeClass('updating-message');
			},
			error:function(response){
				var messagediv = self.parents('fieldset').find(".resp_message")
				messagediv.html(response.responseJSON.message)
				messagediv.css({"color": "red"})

			}
		})
	})
});
jQuery(document).ready(function($){
	/* Newletters js starts here */      
	var user_ids='';
	let activeTab = '#pn_connect';
	activeTab = localStorage.getItem('activeTab');
	if(activeTab === 'undefined'){
		activeTab = '#pn_connect';
	}
	if(activeTab){
		$('.pn-tabs').hide();
		$(activeTab).show();
		$(".push-notification-tabs a").removeClass('nav-tab-active');
		$('a[href="' + activeTab + '"]').addClass('nav-tab-active');
		localStorage.setItem('activeTab', activeTab);
	}
        
            if(pn_setings.do_tour){
                
                var content = '<h3>You are awesome for using Push Notification!</h3>';
                content += '<p>Do you want the latest on <b>Push Notification update</b> before others and some best resources on monetization in a single email? - Free just for users of Push Notification!</p>';
                        content += '<style type="text/css">';
                        content += '.wp-pointer-buttons{ padding:0; overflow: hidden; }';
                        content += '.wp-pointer-content .button-secondary{  left: -25px;background: transparent;top: 5px; border: 0;position: relative; padding: 0; box-shadow: none;margin: 0;color: #0085ba;} .wp-pointer-content .button-primary{ display:none}  #pn_mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }';
                        content += '</style>';                        
                        content += '<div id="pn_mc_embed_signup">';
                        content += '<form id="pushnotification-subscribe-newsletter-form" method="POST">';
                        content += '<div id="pn_mc_embed_signup_scroll">';
                        content += '<div class="pn-mc-field-group" style="    margin-left: 15px;    width: 195px;    float: left;">';
                        content += '<input type="text" name="name" class="form-control" placeholder="Name" hidden value="'+pn_setings.current_user_name+'" style="display:none">';
                        content += '<input type="text" value="'+pn_setings.current_user_email+'" name="email" class="form-control" placeholder="Email*"  style="      width: 180px;    padding: 6px 5px;">';
                        content += '<input type="text" name="company" class="form-control" placeholder="Website" hidden style=" display:none; width: 168px; padding: 6px 5px;" value="'+pn_setings.get_home_url+'">';
                        content += '<input type="hidden" name="ml-submit" value="1" />';
                        content += '</div>';
                        content += '<div id="mce-responses">';
                        content += '<div class="response" id="mce-error-response" style="display:none"></div>';
                        content += '<div class="response" id="mce-success-response" style="display:none"></div>';
                        content += '</div>';
                        content += '<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_a631df13442f19caede5a5baf_c9a71edce6" tabindex="-1" value=""></div>';
                        content += '<input type="submit" value="Subscribe" name="subscribe" class="button mc-newsletter-sent" style=" background: #0085ba; border-color: #006799; padding: 0px 16px; text-shadow: 0 -1px 1px #006799,1px 0 1px #006799,0 1px 1px #006799,-1px 0 1px #006799; height: 30px; margin-top: 1px; color: #fff; box-shadow: 0 1px 0 #006799;">';
                        content += '</div>';
                        content += '</form>';
                        content += '</div>';
                
                var setup;                
                var wp_pointers_tour_opts = {
                    content:content,
                    position:{
                        edge:"left",
                        align:"left"
                    }
                };
                                
                wp_pointers_tour_opts = jQuery.extend (wp_pointers_tour_opts, {
                        buttons: function (event, t) {
                                button= jQuery ('<a id="pointer-close" class="button-secondary">' + pn_setings.button1 + '</a>');
                                button_2= jQuery ('#pointer-close.button');
                                button.bind ('click.pointer', function () {
                                        t.element.pointer ('close');
                                });
                                button_2.on('click', function() {
                                        t.element.pointer ('close');
                                } );
                                return button;
                        },
                        close: function () {
                                jQuery.post (pn_setings.ajax_url, {
                                        pointer: 'pushnotification_subscribe_pointer',
                                        action: 'dismiss-wp-pointer'
                                });
                        },
                        show: function(event, t){
                         t.pointer.css({'left':'170px', 'top':'160px'});
                      }                                               
                });
                setup = function () {
                        jQuery(pn_setings.displayID).pointer(wp_pointers_tour_opts).pointer('open');
                         if (pn_setings.button2) {
                                jQuery ('#pointer-close').after ('<a id="pointer-primary" class="button-primary">' + pn_setings.button2+ '</a>');
                                jQuery ('#pointer-primary').click (function () {
                                        pn_setings.function_name;
                                });
                                jQuery ('#pointer-close').click (function () {
                                        jQuery.post (pn_setings.ajax_url, {
                                                pointer: 'pushnotification_subscribe_pointer',
                                                action: 'dismiss-wp-pointer'
                                        });
                                });
                         }
                };
                if (wp_pointers_tour_opts.position && wp_pointers_tour_opts.position.defer_loading) {
                        jQuery(window).bind('load.wp-pointers', setup);
                }
                else {
                        setup ();
                }
                
            }
                
    /* Newletters js ends here */ 
    /*Newsletter submission*/
    jQuery("#pushnotification-subscribe-newsletter-form").on('submit',function(e){
        e.preventDefault();
        var form = jQuery(this);
        var name = form.find('input[name="name"]').val();
        var email = form.find('input[name="email"]').val();
        var website = form.find('input[name="company"]').val();
        jQuery.post( pn_setings.ajax_url, {action:'pn_subscribe_newsletter',name:name, email:email,website:website, nonce: pn_setings.remote_nonce},
          function(data) {
              jQuery.post (pn_setings.ajax_url, {
                      pointer: 'pushnotification_subscribe_pointer',
                      action: 'dismiss-wp-pointer'
              }, function(){
                location.reload();
              });
          }, 'json' );
    });
    /*Newsletter submission End*/

	jQuery("#user_auth_vadation").click(function(){
		var self = jQuery(this);
		var tokenKey = jQuery("#user_auth_token_key").val().trim();
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

	jQuery("#pn-refresh-apikey").click(function(){
		var self = jQuery(this);
		self.addClass('button updating-message');
		var messagediv = self.parents('fieldset').find(".resp_message")
		messagediv.html("");
		jQuery.ajax({
			url: ajaxurl,
			method: "post",
			dataType: 'json',
			data: {  action: "pn_refresh_user", nonce: pn_setings.remote_nonce },
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

	jQuery('.checkbox_operator').click(function(e){
		var value = 0;
		var target = jQuery(this).parent('.checkbox_wrapper').find('.checkbox_target');
		if(jQuery(this).prop("checked")==true){
			var value = target.attr("data-truevalue");
		}
		target.val(value);
	})
	jQuery('#utm_tracking_checkbox').click(function(){
		if(jQuery(this).prop("checked")==true){
			jQuery("#utm_tracking_wrapper").show();
		}else{jQuery("#utm_tracking_wrapper").hide();}
	});

	jQuery('#pn_push_on_category_checkbox').click(function(){
		if(jQuery(this).prop("checked")==true){
			jQuery("#category_selector_wrapper").show();
			jQuery(".js_category_selector_wrapper").show();
			jQuery(".js_custom_category_selector_wrapper").show();
			jQuery("#segment_category_selector_wrapper").show();
			
		}else{
			jQuery("#category_selector_wrapper").hide();
			jQuery(".js_category_selector_wrapper").hide();
			jQuery(".js_custom_category_selector_wrapper").hide();
			jQuery("#segment_category_selector_wrapper").hide();
		}
	});

	jQuery('#pn_display_popup_after_login').click(function(){
		var parent_tr = jQuery("#pn_role_selector_wrapper").parents('tr');
		if(jQuery(this).prop("checked")==true){
			parent_tr.show();
		}else{
			parent_tr.hide();
		}
	});
	if(jQuery('#pn_display_popup_after_login').prop("checked")==true){
		var parent_tr = jQuery("#pn_role_selector_wrapper").parents('tr');
		parent_tr.show();
	}else{
		var parent_tr = jQuery("#pn_role_selector_wrapper").parents('tr');
		parent_tr.hide();
	}


	jQuery(".push-notification-tabs a").click(function(e){
	        e.preventDefault();
	        var link = jQuery(this).attr("link");
	        jQuery(this).siblings().removeClass("nav-tab-active");
	        jQuery(this).addClass("nav-tab-active");
	        if( link == "pn_connect") {
	        	jQuery("#pn_dashboard").hide();
	        	jQuery("#pn_wc_settings_section").hide();
	        	jQuery("#pn_segmentation").hide();
	        	jQuery("#pn_notification_bell").hide();
	        	jQuery("#pn_help").hide();
	        	jQuery("#pn_connect").show();
				jQuery("#pn_campaings").hide();
				jQuery("#pn_compatibility").hide();
				jQuery("#pn_visibility").hide();
	        }
	        if(link == "pn_dashboard"){
	        	jQuery("#pn_dashboard").show();
	        	jQuery("#pn_wc_settings_section").show();
	        	jQuery("#pn_segmentation").hide();
	        	jQuery("#pn_notification_bell").hide();
	        	jQuery("#pn_help").hide();
	        	jQuery("#pn_connect").hide();
				jQuery("#pn_campaings").hide();
				jQuery("#pn_compatibility").hide();
				jQuery("#pn_visibility").hide();
	        } 
	        if( link == "pn_segmentation") {
	        	jQuery("#pn_dashboard").hide();
	        	jQuery("#pn_wc_settings_section").hide();
	        	jQuery("#pn_help").hide();
	        	jQuery("#pn_notification_bell").hide();
	        	jQuery("#pn_segmentation").show();
	        	jQuery("#pn_connect").hide();
				jQuery("#pn_campaings").hide();
				jQuery("#pn_compatibility").hide();
				jQuery("#pn_visibility").hide();
	        	
	        }
	        if( link == "pn_notification_bell") {
	        	jQuery("#pn_dashboard").hide();
	        	jQuery("#pn_wc_settings_section").hide();
	        	jQuery("#pn_segmentation").hide();
	        	jQuery("#pn_notification_bell").show();
	        	jQuery("#pn_help").hide();
	        	jQuery("#pn_connect").hide();
				jQuery("#pn_campaings").hide();
				jQuery("#pn_compatibility").hide();
				jQuery("#pn_visibility").hide();
	        }
	        if( link == "pn_campaings") {
	        	jQuery("#pn_dashboard").hide();
	        	jQuery("#pn_wc_settings_section").hide();
	        	jQuery("#pn_segmentation").hide();
	        	jQuery("#pn_notification_bell").hide();
	        	jQuery("#pn_help").hide();
	        	jQuery("#pn_connect").hide();
	        	jQuery("#pn_compatibility").hide();
	        	jQuery("#pn_visibility").hide();
	        	jQuery("#pn_campaings").show();
	        }
	        if( link == "pn_compatibility") {
	        	jQuery("#pn_dashboard").hide();
	        	jQuery("#pn_wc_settings_section").hide();
	        	jQuery("#pn_segmentation").hide();
	        	jQuery("#pn_notification_bell").hide();
	        	jQuery("#pn_help").hide();
	        	jQuery("#pn_connect").hide();
	        	jQuery("#pn_visibility").hide();
	        	jQuery("#pn_campaings").hide();
	        	jQuery("#pn_compatibility").show();
	        }
	        if( link == "pn_visibility") {
	        	jQuery("#pn_dashboard").hide();
	        	jQuery("#pn_wc_settings_section").hide();
	        	jQuery("#pn_segmentation").hide();
	        	jQuery("#pn_notification_bell").hide();
	        	jQuery("#pn_help").hide();
	        	jQuery("#pn_connect").hide();
	        	jQuery("#pn_compatibility").hide();
	        	jQuery("#pn_campaings").hide();
	        	jQuery("#pn_visibility").show();
	        }
	        if( link == "pn_help") {
	        	jQuery("#pn_dashboard").hide();
	        	jQuery("#pn_wc_settings_section").hide();
	        	jQuery("#pn_segmentation").hide();
	        	jQuery("#pn_notification_bell").hide();
	        	jQuery("#pn_help").show();
	        	jQuery("#pn_connect").hide();
				jQuery("#pn_campaings").hide();
				jQuery("#pn_compatibility").hide();
				jQuery("#pn_visibility").hide();
	        }
	        
	        localStorage.setItem('activeTab', $(e.target).attr('href'));
	});

	jQuery("#js_notification_button").click(function(e){
		e.preventDefault();
		jQuery(".push-notification-tabs").find('.nav-tab-active').removeClass("nav-tab-active");
		jQuery(".push-notification-tabs").find('.js_notification').addClass("nav-tab-active");

		jQuery("#pn_dashboard").hide();
		jQuery("#pn_wc_settings_section").hide();
		jQuery("#pn_segmentation").hide();
		jQuery("#pn_notification_bell").show();
		jQuery("#pn_help").hide();
		jQuery("#pn_connect").hide();
		jQuery("#pn_campaings").hide();
		localStorage.setItem('activeTab', $(e.target).attr('href'));
	});

	jQuery(".pn_push_segment_category_checkbox").click(function(){
		chkCategory();
	});

	function chkCategory() {
			var category = [];
		jQuery(".pn_push_segment_category_checkbox").each(function(value, index){
			var chk = jQuery(this).is(':checked');
			if(chk==true){
				var chk_val = jQuery(this).val();
			 	category.push(chk_val);	
			}
		});
		jQuery("#pn_push_segment_category_input").val(category);
	}

	function IsEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
	}

	//Help Query
	jQuery(".pn_help-send-query").on("click", function(e){
	        e.preventDefault();
			$('.error_js').remove();   
	        var email = jQuery("#pn_query_email").val();           
	        var message = jQuery("#pn_help_query_message").val();           
	        var customer = jQuery("#pn_help_query_customer").val();    
	        if(jQuery.trim(message) !='' && customer && IsEmail(email) == true){       
	                    jQuery.ajax({
	                        type: "POST",    
	                        url: ajaxurl,                    
	                        dataType: "json",
	                        data:{action:"pn_send_query_message", customer_type: customer, message:message, nonce: pn_setings.remote_nonce,email:email},
	                        success:function(response){                       
	                          if(response['status'] =='t'){
	                            jQuery(".pn_help-query-success").show();
	                            jQuery(".pn_help-query-error").hide();
	                          }else{
	                            jQuery(".pn_help-query-success").hide();  
	                            jQuery(".pn_help-query-error").show();
	                          }
	                        },
	                        error: function(response){                    
	                        console.log(response);
	                        }
	                        });
	        }else{
	            if(jQuery.trim(email) ==''){
					jQuery("#pn_query_email").after('<p  class="error_js" style="color:red;"> Pleas enter email<p/>');
	            }else if(IsEmail(email) == false){
					jQuery("#pn_query_email").after('<p  class="error_js" style="color:red;">Please enter a valid email<p/>');
                }
	            if(customer ==''){
					jQuery("#pn_help_query_customer").after('<p  class="error_js" style="color:red;">Please select customer type<p/>');
	            }
	            if(jQuery.trim(message) == ''){
					jQuery("#pn_help_query_message").after('<p  class="error_js" style="color:red;">Please enter the message<p/>');
	            }
	        }                   
	        
	});

	jQuery("body").on("click",".js_custom_pagination", function(e){
		jQuery("#pn_cam_loading").css("display","block");
	        e.preventDefault();
	        var page = jQuery(this).attr('page');
			jQuery.ajax({
	        url: ajaxurl,
			method: "post",
			dataType: 'html',
				data:{action:'pn_get_compaigns',page:page,nonce: pn_setings.remote_nonce},
				success:function(response){
				jQuery("#pn_cam_loading").css("display","none");
					jQuery("#pn_campaings_custom_div").html(response);
				},
				error: function(response){
				jQuery("#pn_cam_loading").css("display","none");
				}
			});
	        
	});

	jQuery(".upload_image_url").click(function(e) {  // upload_image_url
        e.preventDefault();
        var pwaforwpMediaUploader = wp.media({
            title: pn_setings.uploader_title,
            button: {
                text: pn_setings.uploader_button
            },
            multiple: false,  // Set this to true to allow multiple files to be selected
                        library:{type : 'image'}
        })
        .on("select", function() {
            var attachment = pwaforwpMediaUploader.state().get("selection").first().toJSON();
            jQuery("#notification-imageurl").val(attachment.url);
            jQuery("#js_pn_banner").addClass('notification-banner');
			jQuery('.notification-banner').css('background-image','url('+attachment.url+')');
        })
        .open();
    });
	jQuery(".pn_not_icon").click(function(e) {  // upload_image_url
        e.preventDefault();
        var pwaforwpMediaUploader = wp.media({
            title: pn_setings.uploader_title,
            button: {
                text: pn_setings.uploader_button
            },
            multiple: false,  // Set this to true to allow multiple files to be selected
                        library:{type : 'image'}
        })
        .on("select", function() {
            var attachment = pwaforwpMediaUploader.state().get("selection").first().toJSON();
            jQuery("#notification_icon").val(attachment.url);            
        })
        .open();
    });
   	jQuery(".pn_pop_up_not_icon").click(function(e) {  // upload_image_url
        e.preventDefault();
        var pwaforwpMediaUploader = wp.media({
            title: pn_setings.uploader_title,
            button: {
                text: pn_setings.uploader_button
            },
            multiple: false,  // Set this to true to allow multiple files to be selected
                        library:{type : 'image'}
        })
        .on("select", function() {
            var attachment = pwaforwpMediaUploader.state().get("selection").first().toJSON();
            jQuery("#notification_pop_up_icon").val(attachment.url);            
        })
        .open();
    });

	jQuery(".upload_icon_url").click(function(e) {  // upload_image_url
        e.preventDefault();
        var pwaforwpMediaUploader = wp.media({
            title: pn_setings.uploader_title,
            button: {
                text: pn_setings.uploader_button
            },
            multiple: false,  // Set this to true to allow multiple files to be selected
                        library:{type : 'image'}
        })
        .on("select", function() {
            var attachment = pwaforwpMediaUploader.state().get("selection").first().toJSON();
            jQuery("#notification-iconurl").val(attachment.url);
            jQuery("#js_pn_icon").css('background-image','url('+attachment.url+')');
			
        })
        .open();
    });

	jQuery("#notification-send-type").change(function(){
		jQuery('#pn-notification-custom-roles').parent().hide();
		jQuery('#notification-custom-upload').parent().hide();
		jQuery('#notification-custom-select').parent().hide();
		jQuery('#notification-custom-page-subscribed').parent().hide();
		if(jQuery(this).val()=='custom-select'){
			jQuery('#notification-custom-select').parent().show();
		}
		else if(jQuery(this).val()=='custom-roles'){
			jQuery('#pn-notification-custom-roles').parent().show();
		}
		else if(jQuery(this).val()=='custom-upload'){
			jQuery('#notification-custom-upload').parent().show();
		}
		else if(jQuery(this).val()=='custom-page-subscribed'){
			jQuery('#notification-custom-page-subscribed').parent().show();
		}
	});
	jQuery("#pn-send-custom-notification").click(function(){
		var self = jQuery(this);
		var title 	 = jQuery('#notification-title').val();
		var link_url 	 = jQuery('#notification-link').val();
		var image_url = jQuery('#notification-imageurl').val();
		var icon_url = jQuery('#notification-icon').val();
		var message  = jQuery('#notification-message').val();
		var send_type  = jQuery('#notification-send-type').val();
		var page_subscribed  = jQuery('#notification-custom-page-subscribed').val();
		var select_subs  = jQuery('#notification-custom-select').val();
		var roles_subs  = jQuery('#pn-notification-custom-roles').val();
		var subs_csv  = document.getElementById('notification-custom-upload');
		var target_ajax_url="pn_send_notification";
		var notification_schedule  = jQuery('#notification-schedule').val();
		var notification_date = null;
		var notification_time = null;
		if (notification_schedule == 'yes') {
			var notification_date  = jQuery('#notification-date').val();
			var notification_time  = jQuery('#notification-time').val();
		}
		
		if(send_type=='custom-select'){
			user_ids=select_subs.join(',');
		}
		if(send_type=='custom-roles'){
			user_ids=roles_subs.join(',');
		}

		if(send_type == 'custom-upload'){
			user_ids = sessionStorage.getItem('pnTmpCsvData');
		}

		if(send_type=='custom-select' || send_type=='custom-upload' || send_type=='custom-page-subscribed' )
		{
			target_ajax_url = 'campaign_for_individual_tokens';
		}
		self.addClass('button updating-message');
		jQuery('.spinner').addClass('is-active');
		jQuery('.js_error').html('');
		jQuery.ajax({
			url: ajaxurl,
			method: "post",
			dataType: 'json',
			data: { action: 'pn_send_notification', nonce: pn_setings.remote_nonce, 
				title: title,
				link_url: link_url,
				image_url: image_url,
				icon_url:icon_url,
				message: message,
				audience_token_id:user_ids,
				audience_token_url:target_ajax_url,
				send_type:send_type,
				notification_schedule:notification_schedule,
				notification_date:notification_date,
				notification_time:notification_time,
				page_subscribed:page_subscribed
				},
			success: function(response){
				
				if(response.status==200){
					jQuery(".pn-send-messageDiv").html("&nbsp; "+response.message).css({"color":"green"});
					
					jQuery('#notification-title').val("");
					jQuery('#notification-link').val("");
					jQuery('#notification-imageurl').val("");
					jQuery('#notification-message').val("");
					jQuery('#notification-date').val();
					jQuery('#notification-time').val();
				}else{
					jQuery.each(response.response, function( key, value ) {						
						if (key == 'title') {
							jQuery("#notification-title").after('<p class="js_error" style="color:red">The title field is required</p>');
						}
						if (key == 'message') {
							jQuery("#notification-message").after('<p class="js_error" style="color:red">The message field is required</p>');
						}
						if (key == 'link_url') {
							jQuery("#notification-link").after('<p class="js_error" style="color:red">The link url field is required</p>');
						}
						if (key == 'notification_date') {
							jQuery("#notification-date").after('<p class="js_error" style="color:red">The notification date field is required</p>');
						}
						if (key == 'notification_time') {
							jQuery("#notification-time").after('<p class="js_error" style="color:red">The notification time field is required</p>');
						}
					})
					jQuery(".pn-send-messageDiv").html("&nbsp; "+response.message).css({"color":"red"});
				}
				self.removeClass('updating-message');
				jQuery('.spinner').removeClass('is-active');
			},
			error:function(response){
				var messagediv = self.parents('fieldset').find(".resp_message")
				messagediv.html(response.responseJSON.message)
				messagediv.css({"color": "red"})
				jQuery('.spinner').removeClass('is-active');

			}
		})
		
	})
	jQuery("#notification-custom-select").select2();
	
	$('.my-color-field').wpColorPicker();
	pn_for_wp_select2();
	
	function initializeSelect2() {
		if (jQuery('#notification-custom-roles').length > 0) {
			jQuery('#notification-custom-roles').select2();
			clearInterval(interval); // Stop checking after initialization
		}
	}
	var interval = setInterval(initializeSelect2, 1500);

	
});

function pnCsvToArray(str, delimiter = ",") {

	// slice from start of text to the first \n index
	// use split to create an array from string by delimiter
	const headers = str.slice(0, str.indexOf("\n")).trim().split(delimiter);

	// slice from \n index + 1 to the end of the text
	// use split to create an array of each csv value row
	const rows = str.slice(str.indexOf("\n") + 1).split("\n");

	// Map the rows
	// split values from each row into an array
	// use headers.reduce to create an object
	// object properties derived from headers:values
	// the object passed as an element of the array
	const arr = rows.map(function (row) {
	  const values = row.split(delimiter);
	  const el = headers.reduce(function (object, header, index) {
		if(values[index]){
			object[header] = values[index].trim();
			return object;
		}
	  }, {});
	  return el;
	});

	// return the array
	return arr;
  }

  
  if(document.querySelector("#notification-custom-upload"))
  {
	document.querySelector("#notification-custom-upload").addEventListener('change',function(){
		const [file] = document.querySelector("#notification-custom-upload").files;
		var reader = new FileReader();
		reader.addEventListener(
			"load",
			() => {
			  user_ids = reader.result;
			  user_ids= JSON.stringify(pnCsvToArray(user_ids));
			  sessionStorage.setItem('pnTmpCsvData',user_ids);
			  console.log(user_ids);
			},
			false,
		  );
		  if (file) {
			reader.readAsText(file);
		  }

	});
  }

jQuery("#notification-schedule").change(function(){
	if(jQuery(this).val()=='yes'){
		jQuery('#notification-date').parent().show();
	}else{
		jQuery('#notification-date').parent().hide();
	}	
});

function pn_for_wp_select2(){
    var $select2 = jQuery('.pn_category_select2');
    
    if($select2.length > 0){
        jQuery($select2).each(function(i, obj) {
            var currentP = jQuery(this);  
            var $defaultSelected = currentP.find("option[value]:is([selected])");
            var $defaultResults = currentP.find("option[value]");
            var defaultResults = [];
            $defaultResults.each(function () {
                var $option = jQuery(this);
                defaultResults.push({
                    id: $option.attr('value'),
                    text: $option.text()
                });
            });
            var defaultSelected = [];
            $defaultSelected.each(function () {
                var $option = jQuery(this);
                defaultSelected.push($option.val());
            });
            var ajaxnewurl = ajaxurl+ '?action=pn_select2_category_data&nonce='+pn_setings.remote_nonce;
            currentP.select2({           
                ajax: {             
                    url: ajaxnewurl,
                    delay: 250, 
                    cache: false,
                },            
                minimumInputLength: 2, 
                minimumResultsForSearch : 50,
                dataAdapter: jQuery.fn.select2.amd.require('select2/data/extended-ajax'),
                defaultResults: defaultResults,
                multiple: true,
                placeholder: "Select Category"
            });
            currentP.val(defaultSelected).trigger("change");

        });

    }
}


icon_url_text =jQuery("#notification-iconurl").val();
jQuery(".pn-notification-image").attr('src',icon_url_text);

jQuery("#notification-title").keyup(function(){
    title_text =jQuery(this).val();
    
    jQuery(".pn-notification-title").html(title_text);
    
});
jQuery("#notification-message").keyup(function(){
    desc_text =jQuery(this).val();
    jQuery(".pn-notification-description").html(desc_text);
});

jQuery(".js_all").hide();
jQuery(".pn-clickable-image").click(function(){
    jQuery('.pn-image-container').find('.pn-clickable-image').removeClass('pn-clickable-image-selected');
    jQuery(this).addClass('pn-clickable-image-selected');
    template_type =jQuery(this).attr('notification_type');
    
    jQuery(".js_all").hide();
    jQuery('.'+template_type).show();

    icon_url = banner_url = jQuery("#js_notification_icon").attr('notification_icon');
    // jQuery("#js_pn_banner").addClass('notification-banner');
    banner_imageurl = jQuery("#notification-imageurl").val();
    icon_imageurl = jQuery("#notification-iconurl").val();

    if (banner_imageurl && banner_imageurl !="") {
        banner_url = banner_imageurl;
    }
    if (icon_imageurl && icon_imageurl !="") {
        icon_url = icon_imageurl;
    }

    if (template_type == 'message-with-banner') {
        jQuery('#js_pn_banner').show();
    }else if (template_type == 'message-with-icon') {
        jQuery('#js_pn_icon').show();        
    }else if (template_type == 'message-with-icon-and-banner') {
        jQuery('#js_pn_icon').show();
        jQuery('#js_pn_banner').show();
    }
});
jQuery("#notification-templat").change(function(){
    template_type =jQuery(this).val();
    jQuery(".js_all").hide();
    jQuery('.'+template_type).show();

    icon_url = banner_url = jQuery("#js_notification_icon").attr('notification_icon');
    banner_imageurl = jQuery("#notification-imageurl").val();
    icon_imageurl = jQuery("#notification-iconurl").val();

    if (banner_imageurl && banner_imageurl !="") {
        banner_url = banner_imageurl;
    }
    if (icon_imageurl && icon_imageurl !="") {
        icon_url = icon_imageurl;
    }

    if (template_type == 'message-with-banner') {
        jQuery('#js_pn_banner').show();
    }else if (template_type == 'message-with-icon') {
        jQuery('#js_pn_icon').show();        
    }else if (template_type == 'message-with-icon-and-banner') {
        jQuery('#js_pn_icon').show();
        jQuery('#js_pn_banner').show();
    }
});

jQuery("#pn_campaings_custom_div").on('click',".pn_js_read_more",function() {
    jQuery(this).parents("td").find('.full_text').show();
    jQuery(this).parents("td").find('.less_text').hide();
});
jQuery("#pn_campaings_custom_div").on('click',".pn_js_read_less",function() {
    jQuery(this).parents("td").find('.full_text').hide();
    jQuery(this).parents("td").find('.less_text').show();
});

jQuery("#pn_url_capture").change(function(){
	pn_url_capture_manual();
});
pn_url_capture_manual();
function pn_url_capture_manual(){
	let capture =jQuery('#pn_url_capture').val();
	if(capture == 'manual'){
		jQuery('#pn_url_capture_manual').parent().parent().show();
	}else{
		jQuery('#pn_url_capture_manual').parent().parent().hide();
	}
}

document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.querySelector('.pn_check_all');
    const checkboxes = document.querySelectorAll('.pn_check_single');
	const bulkDelete = document.querySelector('.pn_bulk_delete');
    const deleteAll = document.querySelector('.pn_delete_all');


	checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            bulkDelete.style.display = 'none';
            deleteAll.style.display = 'none';
            checkboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    bulkDelete.style.display = 'inline-block';
                    deleteAll.style.display = 'inline-block';
                }
            });
        });
    });

	if (checkAll) {
		checkAll.addEventListener('change', function() {
			if (checkAll.checked) {
				bulkDelete.style.display = 'inline-block';
				deleteAll.style.display = 'inline-block';
			} else {
				bulkDelete.style.display = 'none';
				deleteAll.style.display = 'none';
			}
			checkboxes.forEach(function(checkbox) {
				checkbox.checked = checkAll.checked; 
			});
		});
	}
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkAll.checked = allChecked; 
        });
    });
});

function pn_delete_campaign(self){

	var selectedCampaigns = [];

	selectedCampaigns.push(self.getAttribute('data-id'));
	

	// Show confirmation alert before proceeding
    var confirmation = confirm('Are you sure you want to delete the campaign?');
    
    if (!confirmation) {
        return; 
    }

	self.innerHTML='Deleting...';
	jQuery.ajax({
		url: ajaxurl,
		method: "post",
		dataType: 'json',
		data: { 
			action: 'pn_delete_campaign',
			nonce: pn_setings.remote_nonce,
			campaign_ids: selectedCampaigns 
		},
		success: function(response) {
			if(response){
			if (response.status == 200) {
				self.innerHTML=response.message;
					jQuery(self).parent().parent().remove();
			} else {
				self.innerHTML = response.message;
			}
		}
		},
		error: function(response) {
			var messagediv = self.parents('fieldset').find(".resp_message");
			messagediv.html(response.responseJSON.message);
			messagediv.css({ "color": "red" });
			self.innerHTML='Delete';
		}
	});

}


function pn_delete_bulk_campaign(self){

	var selectedCampaigns = [];

	jQuery(".pn_check_single:checked").each(function() {
		selectedCampaigns.push(jQuery(this).val());
	});

	if (selectedCampaigns.length === 0) {
		alert('Please select at least one campaign to delete.');
	}

	// Show confirmation alert before proceeding
    var confirmation = confirm('Are you sure you want to delete the selected campaign(s)?');
    
    if (!confirmation) {
        return; // If user clicks "Cancel", stop the process
    }

	self.innerHTML='Deleting...';
	jQuery.ajax({
		url: ajaxurl,
		method: "post",
		dataType: 'json',
		data: { 
			action: 'pn_delete_campaign',
			nonce: pn_setings.remote_nonce,
			campaign_ids: selectedCampaigns 
		},
		success: function(response) {
			if(response){
			if (response.status == 200) {
				self.innerHTML=response.message;
				jQuery(".pn_check_single:checked").each(function() {
					jQuery(this).parent().parent().remove();
				});
			} else {
				self.innerHTML = response.message;
			}
		}
		},
		error: function(response) {
			var messagediv = self.parents('fieldset').find(".resp_message");
			messagediv.html(response.responseJSON.message);
			messagediv.css({ "color": "red" });
			self.innerHTML='Delete';
		}
	});

}


function pn_delete_all_campaign(self){
    var confirmation = confirm('Are you sure you want to delete all the campaigns? This action cannot be undone.');
    if (!confirmation) {
        return; // If user clicks "Cancel", stop the process
    }
	self.innerHTML='Deleting all campaigns ...';
	jQuery.ajax({
		url: ajaxurl,
		method: "post",
		dataType: 'json',
		data: { 
			action: 'pn_delete_campaign',
			nonce: pn_setings.remote_nonce,
			campaign_ids: 'all' 
		},
		success: function(response) {
			if(response){
			if (response.status == 200) {
				self.innerHTML=response.message;
				window.location.reload();
			} else {
				self.innerHTML = response.message;
			}
		}
		},
		error: function(response) {
			var messagediv = self.parents('fieldset').find(".resp_message");
			messagediv.html(response.responseJSON.message);
			messagediv.css({ "color": "red" });
			self.innerHTML='Delete';
		}
	});

}



jQuery(document).on('click',".pn_reuse_button",function(e) {
	var reuse_data = jQuery(this).attr('data-json');
	reuse_data = jQuery.parseJSON(reuse_data);
	jQuery(".push-notification-tabs").find('.nav-tab-active').removeClass("nav-tab-active");
	jQuery(".push-notification-tabs").find('.js_notification').addClass("nav-tab-active");

	jQuery("#pn_dashboard").hide();
	jQuery("#pn_wc_settings_section").hide();
	jQuery("#pn_segmentation").hide();
	jQuery("#pn_notification_bell").show();
	jQuery("#pn_help").hide();
	jQuery("#pn_connect").hide();
	jQuery("#pn_campaings").hide();


	jQuery("#notification-title").val(reuse_data.title);
	jQuery("#notification-link").val(reuse_data.link_url);
	jQuery("#notification-message").val(reuse_data.message);
	jQuery("#notification-iconurl").val(reuse_data.icon);
	jQuery("#notification-imageurl").val(reuse_data.image);

	jQuery('#notification-title').trigger('keyup');
	jQuery('#notification-message').trigger('keyup');

	if (reuse_data.icon && reuse_data.image) {
		jQuery(".pn-clickable-image").eq(3).click();
		jQuery('.notification-banner').css('background-image','url('+reuse_data.image+')');
		jQuery(".pn-notification-image").attr('src',reuse_data.icon);
	}else if (reuse_data.image) {
		jQuery(".pn-clickable-image").eq(2).click();
		jQuery('.notification-banner').css('background-image','url('+reuse_data.image+')');
	}else if (reuse_data.icon) {
		jQuery(".pn-clickable-image").eq(1).click();
		jQuery(".pn-notification-image").attr('src',reuse_data.icon);
	}
	
	
	localStorage.setItem('activeTab', jQuery(".push-notification-tabs").find('.js_notification').attr('href'));

});

// Pn Visibility start
function pn_get_include_pages() {
	var include_type = jQuery(".visibility_options_select_include").val();
	jQuery(".pn-visibility-loader").css("display","flex");
	var data = {action:"pn_include_visibility_setting_callback",nonce: pn_setings.remote_nonce, include_type:include_type};
	jQuery.ajax({
		url: ajaxurl,
		type: 'post',
		data: data,
		success: function(response) {
			var jd = jQuery.parseJSON(response);
			if (jd.success == 1) {
				jQuery(".visibility_include_select_type").html(jd.option);
				jQuery(".pn-visibility-loader").css("display","none");
				pn_select2(include_type);
			}
		}
	});
}
function pn_add_included_condition(){
	var include_targeting_type = jQuery(".visibility_options_select_include").val();
	var include_targeting_data = jQuery(".visibility_include_select_type").val();
	jQuery(".include_error").html('&nbsp;');
	jQuery(".include_type_error").html('&nbsp;');
	var duplicate_error = false;
	jQuery('.visibility-include-target-item-list > span').each(function () {
		var include_targeting_data_value = jQuery(this).find('input[name="include_targeting_data"]').val();
		if (include_targeting_data == include_targeting_data_value) {
			jQuery(".include_type_error").html('Data alredy selected').css('color','red');
			duplicate_error =  true;
		}
	})
	if(include_targeting_type==''){
		jQuery(".include_error").html('Please select visibility type').css('color','red');
		setTimeout(function(){
			jQuery(".include_error").html('&nbsp;');
		},5000);
		return false;
	}
	if(include_targeting_data==''){
		jQuery(".include_type_error").html('Please select type').css('color','red');
		setTimeout(function(){
			jQuery(".include_type_error").html('&nbsp;');
		},5000);
		return false;
	}
	if (duplicate_error == false) {
		var data = {action:"pn_include_visibility_condition_callback",nonce: pn_setings.remote_nonce, include_targeting_type:include_targeting_type,include_targeting_data:include_targeting_data};
		jQuery(".pn-visibility-loader").css("display","flex");
		jQuery.ajax({
				url: ajaxurl,
				type: 'post',
				data: data,
			  
				success: function(response) {
					var jd = jQuery.parseJSON(response);
					if (jd.success == 1) {
						jQuery(".visibility-include-target-item-list").append(jd.option);
						jQuery(".pn-visibility-loader").css("display","none");
					} 
	
				}
			});
		
	}
}

function pn_removeIncluded_visibility(sr){
	jQuery(".pn-visibility-target-icon-"+sr).empty();
}

function pn_select2(type){
    var $select2 = jQuery('.pn-select2');
    
    if($select2.length > 0){
        jQuery($select2).each(function(i, obj) {
            var currentP = jQuery(this);  
            var $defaultResults = jQuery('option[value]:not([selected])', currentP);  
            
            var defaultResults = [];
            $defaultResults.each(function () {
                var $option = jQuery(this);
                defaultResults.push({
                    id: $option.attr('value'),
                    text: $option.text()
                });
            });
            var ajaxnewurl = ajaxurl + '?action=superpwa_get_select2_data_by_cat&nonce='+pn_setings.remote_nonce+'&type='+type;

            currentP.select2({           
                ajax: {             
                    url: ajaxnewurl,
                    delay: 250, 
                    cache: false,
                },            
                minimumInputLength: 2, 
                minimumResultsForSearch : 50,
                dataAdapter: jQuery.fn.select2.amd.require('select2/data/extended-ajax'),
                defaultResults: defaultResults
            });

        });

    }                    
    
}

// Pn Visibility end
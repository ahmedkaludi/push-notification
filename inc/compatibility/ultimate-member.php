<?php
/**
 * Compatibility with  Real-time Notifications 
 * addon of Ultimate Member compatibility
 * 
 * 
 * Added in version 1.20
 */
class PN_Ultimate_Member{
	public function __construct(){}
	/**
	 * Send the push notification when orders will change
	 * @method pn_order_send_notification
	 * @param  string 			Ref Order id 
	 * @param  string 			Ref status_from
	 * @param  string           $status_to  converted status.
	 * @param  string           $$obj  Order Object.
	 * @return Void                                            
	 */
	public function init(){
		add_action("um_notification_after_notif_update", array($this, 'send_notification_to_user'), 10, 2);
	}

	/**
	 * Send the push notification when via Ultimate Member sends any notification to other users
	 * @method send_notification_to_user
	 * @param  string 			Ref User id 
	 * @param  string 			Ref Content
	 * @return Void                                            
	 */
	public function send_notification_to_user( $user_id, $content){
		$push_notification_settings = push_notification_settings();
        
		$auth_settings = push_notification_auth_settings();
		if( !isset( $auth_settings['user_token'] ) || ( isset( $auth_settings['user_token'] ) && empty($auth_settings['user_token']) ) ){
			return ; 	
		}

		$token_ids = get_user_meta($user_id, 'pnwoo_notification_token',true);
		if(!empty($token_ids)){
		$token_ids = array_filter($token_ids);
		$token_ids = array_unique($token_ids);

		$title = esc_html__('you have new message', 'push-notification');
		$message = wp_trim_words(wp_strip_all_tags(sanitize_text_field($content), true), 20);
		$link_url = esc_url_raw( get_home_url() );
		if(isset($push_notification_settings['utm_tracking_checkbox']) && $push_notification_settings['utm_tracking_checkbox']){
			$utm_details = array(
			    'utm_source'=> $push_notification_settings['notification_utm_source'],
			    'utm_medium'=> $push_notification_settings['notification_utm_medium'],
			    'utm_campaign'=> $push_notification_settings['notification_utm_campaign'],
			    'utm_term'  => $push_notification_settings['notification_utm_term'],
			    'utm_content'  => $push_notification_settings['notification_utm_content'],
			    );
			$link_url = add_query_arg( array_filter($utm_details), $link_url  );
		}
		$image_url = $push_notification_settings['notification_icon'];
		$icon_url = $push_notification_settings['notification_icon'];

		$verifyUrl = PN_Server_Request::$notificationServerUrl.'campaign/single';
        $weblink = is_multisite()? get_site_url() : home_url();
		$data = array("user_token"=>$auth_settings['user_token'],
					"audience_token_id"=>$token_ids,
					"title"=>wp_strip_all_tags($title),
					"message"=> wp_strip_all_tags($message),
					"link_url"=>$link_url,
					"icon_url"=> $icon_url,
					"image_url"=> $image_url,
					"website"=>   $weblink,
					);

		$postdata = array('body'=> $data);
		$remoteResponse = wp_remote_post($verifyUrl, $postdata);
		if( is_wp_error( $remoteResponse ) ){
			$remoteData = array('status'=>401, "response"=>"could not connect to server");
		}else{
			$remoteData = wp_remote_retrieve_body($remoteResponse);
			$remoteData = json_decode($remoteData, true);
		}
	}
	}
}
if(class_exists('um_ext\um_notifications\core\Notifications_Main_API') && ( !is_admin() || wp_doing_ajax() ) ){
$PN_Ultimate_Member = new PN_Ultimate_Member();
$PN_Ultimate_Member->init();
}
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
		add_action( 'um_after_new_message',array($this, 'pn_um_notification_messaging'), 50, 4 );  // send push for new personal message
		add_action('um_activity_after_wall_post_published',array($this, 'pn_um_notification_activity_post_published'), 90, 3 ); // send push for new wall post
		add_action( 'um_groups_after_wall_post_published',array($this, 'pn_um_groups_notify_new_post'), 50, 3 ); // send push for group post
	}

	/**
	 * Send the push notification when via Ultimate Member sends new personal message to other users
	 * @method pn_um_notification_messaging
	 * @return Void                                            
	 */
	public function pn_um_notification_messaging( $to, $from, $conversation_id, $message_data = array() ) {

		$push_notification_settings = push_notification_settings();
		$auth_settings = push_notification_auth_settings();
		if( !isset( $auth_settings['user_token'] ) || ( isset( $auth_settings['user_token'] ) && empty($auth_settings['user_token']) ) ){
			return ; 	
		}
		$token_ids = get_user_meta($to, 'pnwoo_notification_token',true);
		if(!empty($token_ids)){
			$token_ids = array_filter($token_ids);
			$token_ids = array_unique($token_ids);
	
			um_fetch_user( $from );
	
			$vars['photo'] = um_get_avatar_url( get_avatar( $from, 40 ) );
			$vars['member'] = um_user('display_name');
		
			um_fetch_user( $to );
		
			$notification_uri = add_query_arg( 'profiletab', 'messages', um_user_profile_url() );
			$notification_uri = add_query_arg( 'conversation_id', $conversation_id, $notification_uri );
		
			$vars['notification_uri'] = $notification_uri;
	
			$link_url = esc_url_raw( $notification_uri );
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
			$image_url = $vars['photo'];
			$icon_url = $push_notification_settings['notification_icon'];
			$type = 'new_pm';
			$content= '';
			if ( UM()->external_integrations()->is_wpml_active() ) {
				$content = UM()->Notifications_API()->api()->wpml_store_notification( $type, $vars );
			} else {
				$content = UM()->Notifications_API()->api()->get_notify_content( $type, $vars );
			}	
			$verifyUrl = PN_Server_Request::$notificationServerUrl.'campaign/single';
			$weblink = is_multisite()? get_site_url() : home_url();

			$data = array(
						"user_token"		=> $auth_settings['user_token'],
						"audience_token_id"	=> $token_ids,
						"title"				=> esc_html( /* translators: %s: member */ sprintf( __( 'New message from %s', 'push-notification' ), $vars['member'] ) ),
						"message"			=> wp_strip_all_tags($content),
						"link_url"			=> $link_url,
						"icon_url"			=> $icon_url,
						"image_url"			=> $image_url,
						"website"			=> $weblink,
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


	public function pn_um_groups_notify_new_post( $post_id, $user_id, $wall_id  ) {
		$key = 'groups_new_post';

	if ( !class_exists( 'UM_Notifications_API' ) ) {
		return;
	}

	$push_notification_settings = push_notification_settings();
		$auth_settings = push_notification_auth_settings();
		if( !isset( $auth_settings['user_token'] ) || ( isset( $auth_settings['user_token'] ) && empty($auth_settings['user_token']) ) ){
			return ; 	
		}
	
	global $wpdb;
	$table_name = UM()->Groups()->setup()->db_groups_table;
	$group_id = get_post_meta( $post_id, '_group_id', true );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching	 -- Reason: custom table
	$members = $wpdb->get_col( $wpdb->prepare( "SELECT `user_id1` FROM ". esc_sql($table_name) ." WHERE `group_id` = %d AND `status` = 'approved'" , $group_id ) );	

	$all_tokens = array();

	if ( empty( $members ) ) {
		return;
	}

	foreach ( $members as $i => $member_id ) {
		$token_ids = get_user_meta($member_id, 'pnwoo_notification_token',true);
		if(!empty($token_ids)){
			$all_tokens = array_merge($all_tokens, $token_ids);
		}
	}
	

	um_fetch_user( $user_id );
	$author_name = um_user( 'display_name' );
	$photo = um_get_avatar_url( get_avatar( $user_id, 40 ) );
	$group_name = ucwords( get_the_title( $group_id ) );
	$group_url = get_the_permalink( $group_id );
	$group_url_postid = "$group_url#postid-$post_id";
	$post_url = UM()->Groups()->discussion()->get_permalink( $post_id );

	$vars = compact( 'author_name', 'photo', 'group_name', 'group_url', 'group_url_postid', 'post_url');

	$vars['notification_uri'] = $group_url_postid;

	$link_url = esc_url_raw( $group_url_postid );
	$icon_url = $push_notification_settings['notification_icon'];
	$image_url = $photo;
		$content= '';
		if ( UM()->external_integrations()->is_wpml_active() ) {
			$content = UM()->Notifications_API()->api()->wpml_store_notification( $key, $vars );
		} else {
			$content = UM()->Notifications_API()->api()->get_notify_content( $key, $vars );
		}	
		$verifyUrl = PN_Server_Request::$notificationServerUrl.'campaign/single';
		$weblink = is_multisite()? get_site_url() : home_url();

		$data = array(
					"user_token"		=> $auth_settings['user_token'],
					"audience_token_id" => $all_tokens,
					"title"				=> esc_html( /* translators: %s: author name */ sprintf( __( 'New Group Post from %s', 'push-notification' ), $author_name ) ),
					"message"			=> wp_strip_all_tags( $content ),
					"link_url"			=> $link_url,
					"icon_url"			=> $icon_url,
					"image_url"			=> $image_url,
					"website"			=> $weblink,
		);

		$postdata = array('body'=> $data);
		$remoteResponse = wp_remote_post($verifyUrl, $postdata);
		if( is_wp_error( $remoteResponse ) ){
			$remoteData = array('status'=>401, "response"=>"could not connect to server");
		}else{
			$remoteData = wp_remote_retrieve_body($remoteResponse);
			$remoteData = json_decode($remoteData, true);
		}
		um_reset_user();

	}

	/**
	 * Send the push notification when via Ultimate Member sends new wall post to other users
	 * @method pn_um_notification_activity_post_published
	 * @return Void                                            
	 */
	public function pn_um_notification_activity_post_published( $post_id, $writer, $wall ) {
		
		$push_notification_settings = push_notification_settings();
		$auth_settings = push_notification_auth_settings();
		if( !isset( $auth_settings['user_token'] ) || ( isset( $auth_settings['user_token'] ) && empty($auth_settings['user_token']) ) ){
			return ; 	
		}

		if ( $writer == $wall ) return ;

		$token_ids = get_user_meta($wall, 'pnwoo_notification_token',true);
		if(!empty($token_ids)){
			$token_ids = array_filter($token_ids);
			$token_ids = array_unique($token_ids);
	
			um_fetch_user( $writer );
		
			$vars['photo'] = um_get_avatar_url( get_avatar( $writer, 80 ) );
			$vars['member'] = um_user('display_name');
		
			um_fetch_user( $wall );
		
			$url = UM()->Activity_API()->api()->get_permalink( $post_id );
		
			$vars['notification_uri'] = $url;

			$link_url = esc_url_raw( $url );
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
			$image_url = $vars['photo'];
			$icon_url = $push_notification_settings['notification_icon'];

			$type = 'new_wall_post';
			$content= '';
			if ( UM()->external_integrations()->is_wpml_active() ) {
				$content = UM()->Notifications_API()->api()->wpml_store_notification( $type, $vars );
			} else {
				$content = UM()->Notifications_API()->api()->get_notify_content( $type, $vars );
			}	
	
			$verifyUrl = PN_Server_Request::$notificationServerUrl.'campaign/single';
			$weblink = is_multisite()? get_site_url() : home_url();

			$data = array(
						"user_token"		=> $auth_settings['user_token'],
						"audience_token_id"	=> $token_ids,
						"title"				=> esc_html( /* translators: %s: member */ sprintf( __( 'New Post from %s', 'push-notification' ), $vars['member'] ) ),
						"message"			=> wp_strip_all_tags($content),
						"link_url"			=> $link_url	,
						"icon_url"			=> $icon_url,
						"image_url"			=> $image_url,
						"website"			=> $weblink,
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
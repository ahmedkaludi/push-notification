<?php

// Exit if accessed directly.

if ( ! defined( 'ABSPATH' ) ) exit;



class PN_Server_Request{

	public static $notificationServerUrl = 'https://pushnotifications.io/api/'; //slash necessary at last 

	public static $notificationlanding = 'https://pushnotifications.io/'; //slash necessary at last

	public function __construct(){}



	public static function varifyUser($user_token){  

		$verifyUrl = 'validate/user';

		if ( is_multisite() ) {

            $weblink = get_site_url();              

        }

        else {

            $weblink = home_url();

        }    

		$data = array("user_token"=>$user_token, "website"=>   $weblink);

		$response = self::sendRequest($verifyUrl, $data, 'post');

		$push_notification_auth_settings = get_option('push_notification_auth_settings');

		$push_notification_auth_settings['user_token'] = sanitize_text_field($user_token);

		if($response['status']==200){
			$response['response'] = array_map( 'sanitize_text_field',  $response['response']  );

			$push_notification_auth_settings['token_details'] = $response['response'];

			$push_notification_auth_settings['messageManager'] = $response['response']['messageManager'];

			if ( is_multisite() ) {
				update_site_option('push_notification_auth_settings', $push_notification_auth_settings,false);
			}else{
				update_option('push_notification_auth_settings', $push_notification_auth_settings,false);
			}
		}




		return $response;

	}

	public static function inactivateWebsite($user_token){  

		$verifyUrl = 'validate/in_activate_website';

		if ( is_multisite() ) {

            $weblink = get_site_url();              

        }

        else {

            $weblink = home_url();

        }    

		$data = array("user_token"=>$user_token, "website"=>   $weblink);

		$response = self::sendRequest($verifyUrl, $data, 'post');

		return $response;

	}



	public static function registerSubscribers($token_id, $user_agent, $os, $ip_address, $category){

		$verifyUrl = 'register/audience/token';
		$notification_settings= push_notification_settings();
		$lang_compatibility = 'no';
		$language_code = 'en';
		if ( function_exists( 'pll_current_language' ) && isset($notification_settings['pn_polylang_compatibale']) && $notification_settings['pn_polylang_compatibale']) {
			$lang_compatibility = 'yes';
			$language_code = pll_current_language();
        }

		if ( is_multisite() ) {
			$is_multisite = 'yes';
            $weblink = get_site_url();
			$push_notification_auth_settings = get_site_option( 'push_notification_auth_settings', array() ); 
        } else {
			$is_multisite = 'no';
            $weblink = home_url();
			$push_notification_auth_settings = get_option( 'push_notification_auth_settings', array() );
        }

        $user_token = $push_notification_auth_settings['user_token'];

		$data = array("website"=>  $weblink, 

					'token_id' => $token_id, 

					'user_agent'=>$user_agent, 

					'os'=>$os, 

					'user_token'=> $user_token,

					'ip_address'=> $ip_address,

					'category'=> $category,

					'is_multisite'=> $is_multisite,
					'language_code'=> $language_code,
					'lang_compatibility'=> $lang_compatibility,
				);

		$response = self::sendRequest($verifyUrl, $data, 'post');

		return $response;

	}



	public static function getsubscribersData($user_token){

		$verifyUrl = 'audience/details';

		if ( is_multisite() ) {
            $weblink = get_site_url();
			$is_multisite = 'yes'; 
        } else {
			$is_multisite = 'no';
            $weblink = home_url();
        }    

		$data = array("user_token"=>$user_token, "website"=>   $weblink,'is_multisite' => $is_multisite);

		$response = self::sendRequest($verifyUrl, $data, 'post');

		$push_notification_auth_settings = get_option('push_notification_details_settings', array());

		if ( $response['status'] == 200 ) {

			$push_notification_auth_settings['subscriber_count'] = sanitize_text_field( $response['subscriber_count'] );
			$push_notification_auth_settings['active_count']     = sanitize_text_field( $response['active_count'] );
			$push_notification_auth_settings['expired_count']    = sanitize_text_field( $response['expired_count'] );
			$push_notification_auth_settings['updated_at']       = gmdate( 'Y-m-d H:i:s' );

		}

		update_option( 'push_notification_details_settings', $push_notification_auth_settings, false );

		return $response;

	}

	public static function getCompaignsData($user_token,$page = 1){
		$verifyUrl = 'campaign/compaign-list';
		if ( is_multisite() ) {
            $weblink = get_site_url(); 
			$is_multisite = 'yes';             
        }else {
            $weblink = home_url();
			$is_multisite = 'no';
        }    
		$data = array("user_token"=>$user_token, "website"=> $weblink, "page" => $page,'is_multisite'=>$is_multisite);
		$response = self::sendRequest($verifyUrl, $data, 'post');
		return $response;
	}
	public static function deleteCampaigns($user_token, $campaign_ids){
		$verifyUrl = 'campaign/delete';
		if ( is_multisite() ) {
			$weblink = get_site_url();              
		}
		else {
			$weblink = home_url();
		}    
		$data = array("user_token"=>$user_token, "website"=> $weblink, "campaign_ids" => $campaign_ids);
		$response = self::sendRequest($verifyUrl, $data, 'post');
		return $response;
	}


	public static function sendPushNotificatioData($user_token, $title, $message, $link_url, $icon_url, $image_url, $category){

		$verifyUrl = 'campaign/create';

		if ( is_multisite() ) {

            $weblink = get_site_url();              

        }

        else {

            $weblink = home_url();

        }

		$notification_settings= push_notification_settings();
		$lang_compatibility = 'no';
		$language_code = 'en';
		if ( function_exists( 'pll_current_language' ) && isset($notification_settings['pn_polylang_compatibale']) && $notification_settings['pn_polylang_compatibale']) {
			$lang_compatibility = 'yes';
			$language_code = pll_current_language();
        }

		$data = array("user_token"=>$user_token, "website"=>   $weblink, 

					'title'=>$title, 

					'message'=>$message, 

					'link_url'=>$link_url, 

					'icon_url'=>$icon_url,

					'image_url'=>$image_url,

					'category'=>$category,
					'language_code'=>$language_code,
					'lang_compatibility'=>$lang_compatibility,

				);

		$response = self::sendRequest($verifyUrl, $data, 'post');

		return $response;

	}



	public static function sendPushNotificatioClickData( $campaign ){

		$verifyUrl = 'campaign/click';

		    

		$data = array( "campaign_id"=>$campaign );

		$response = self::sendRequest($verifyUrl, $data, 'post');

		return $response;

	}



	protected static function sendRequest($suffixUrl, $data, $method="post"){

		if($method==='post'){
				$url = self::$notificationServerUrl.$suffixUrl;

				$postdata = array('body'=> $data);

				$remoteResponse = wp_remote_post($url, $postdata);
		}

		if( is_wp_error( $remoteResponse ) ){
			if(!empty($remoteResponse->get_error_message()) ) {       
				$error_message = strtolower($remoteResponse->get_error_message());
				$error_pos = strpos($error_message, 'operation timed out');
				if($error_pos !== false){
					$message = __('Request timed out, please try again','push-notification');
				}else{
					$message = esc_html($remoteResponse->get_error_message());
				}
			}else{
				$message = __("could not connect to server",'push-notification');
			}
			$remoteData = array('status'=>401, "response"=>$message);

		}else{

			$remoteData = wp_remote_retrieve_body($remoteResponse);

			$remoteData = json_decode($remoteData, true);

		}

		return $remoteData;



	}
	
	public static function sendPushNotificatioDataNew($payload){

		$verifyUrl = 'campaign/create';

		if ( is_multisite() ) {
            $weblink = get_site_url(); 
			$is_multisite = 'yes';             
        }else {
            $weblink = home_url();
			$is_multisite = 'no';
        }
		$notification_settings= push_notification_settings();
		$lang_compatibility = 'no';
		$language_code = 'en';
		if ( function_exists( 'pll_current_language' ) && isset($notification_settings['pn_polylang_compatibale']) && $notification_settings['pn_polylang_compatibale']) {
			$lang_compatibility = 'yes';
			$language_code = pll_current_language();
        }
		if($payload['audience_token_url']=='campaign_for_individual_tokens'){
			$verifyUrl = 'campaign/single'; 
		}

		$data = array("user_token"=>$payload['user_token'], 
					"website"=>   $weblink, 
					'title'=>$payload['title'], 
					'message'=>$payload['message'], 
					'link_url'=>$payload['link_url'], 
					'icon_url'=>$payload['icon_url'],
					'image_url'=>$payload['image_url'],
					'category'=>$payload['category'],
					'audience_token_id'=>$payload['audience_token_id'],
					'notification_schedule'=>$payload['notification_schedule'],
					'notification_time'=>$payload['notification_time'],
					'notification_date'=>$payload['notification_date'],
					'is_multisite'=>$is_multisite,
					'language_code'=>$language_code,
					'lang_compatibility'=>$lang_compatibility,
				);

		$response = self::sendRequest($verifyUrl, $data, 'post');

		return $response;

	}

	public static function getProStatus($fetch_from_api=false){

		$auth_settings = push_notification_auth_settings();
		if($fetch_from_api)
		{
			if(!empty($auth_settings['user_token']))
			{
				$auth_settings = self::varifyUser($auth_settings['user_token']);
			}
		}
		
		$return = 'inactive';
		if(!empty($auth_settings['user_token']) && isset($auth_settings['token_details']['user_pro']) && isset($auth_settings['token_details']['user_pro_status']))
		{
			if($auth_settings['token_details']['user_pro']=='1' && $auth_settings['token_details']['user_pro_status']=='active'){
				$return = 'active';
			}
		}

		return $return;

	}

	public static function pnSendPushNotificatioinFilter($payload){
		$verifyUrl = 'campaign/pn_send_push_filter';
		$response = self::sendRequest($verifyUrl, $payload, 'post');
		return $response;
	}
}
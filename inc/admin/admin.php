<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if(file_exists(PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/class-function.php")){
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/class-function.php";
}
class Push_Notification_Admin{
	
	public function __construct(){}

	public function init(){
		add_action('admin_notices', array($this, 'admin_notices_opt') );
		add_action( 'admin_menu', array( $this, 'add_menu_links') );
		add_action( 'admin_init', array( $this, 'settings_init') );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );

		add_action( 'wp_ajax_pn_verify_user', array( $this, 'pn_verify_user' ) ); 
		add_action( 'wp_ajax_pn_revoke_keys', array( $this, 'pn_revoke_keys' ) ); 
		add_action( 'wp_ajax_pn_subscribers_data', array( $this, 'pn_subscribers_data' ) ); 
		add_action( 'wp_ajax_pn_send_notification', array( $this, 'pn_send_notification' ) ); 
		
		add_action('wp_ajax_pn_subscribe_newsletter',array( $this, 'pn_subscribe_newsletter' ) );


		//Send push on publish and update
		add_action( 'transition_post_status', array( $this, 'send_notification_on_update' ), 10, 3 ); 

		/** pwaforwp installed than work with that */
		if( function_exists('pwaforwp_init_plugin') ){
			/** service worker change */
			$settings = pwaforwp_defaultSettings();
			$auth_settings = push_notification_auth_settings();
			if($settings['notification_feature']==1 && $settings['notification_options']=='pushnotifications_io' 
				//For Auth settings check
				&& isset($auth_settings['user_token']) && isset($auth_settings['token_details']['validated']) && $auth_settings['token_details']['validated'] == 1){
				add_filter( "pwaforwp_sw_js_template", array($this, 'add_sw_js_content') , 10, 1);
				add_filter( "pwaforwp_pn_config", array($this, 'add_pn_config') , 10, 1);
				add_filter( "pwaforwp_pn_use_sw", array($this, 'add_pn_use_sw') , 10, 1);
				add_filter( "pwaforwp_sw_register_template", array($this, 'add_sw_register_template') , 10, 1);
			}




		}
	}


	/*
	* This function will Messaging functions in service worker
	* 
	*/
	function add_sw_js_content($swJsContent){
        	$messageSw = $this->pn_get_layout_files('messaging-sw.js');
			$settings = $this->json_settings();
			$messageSw = str_replace('{{pnScriptSetting}}', json_encode($settings), $messageSw);
			$swJsContent .= $messageSw;
		
		return $swJsContent;
	}

	function add_pn_config($firebaseconfig){
		$firebaseconfig   = 'var config = pnScriptSetting.pn_config;'
                            .'if (!firebase.apps.length) {firebase.initializeApp(config);}
                            const messaging = firebase.messaging();';
		return $firebaseconfig;
	}

	function add_pn_use_sw($useserviceworker){
		 $useserviceworker = 'messaging.useServiceWorker(reg); pushnotification_load_messaging();';
		return $useserviceworker;
	}

	function add_sw_register_template($swHtmlContent){
		//its similar to app.js, not contain sercive worker installation
		$sw_registerContent = $this->pn_get_layout_files('public/app-pwaforwp.js');
		//Concatnate content in main service worker register
		$swHtmlContent .= $sw_registerContent;
		return $swHtmlContent;
	}

	public function json_settings(){
		if ( is_multisite() ) {
            $link = get_site_url();              
        }
        else {
            $link = home_url();
        }    
        $auth_settings = push_notification_auth_settings();
        $messageConfig = '';
        if(isset($auth_settings['user_token']) && isset($auth_settings['token_details']['validated']) && $auth_settings['token_details']['validated'] == 1){
        	$messageConfig = json_decode($auth_settings['messageManager'], true);
        }
        $settings = array(
					'nonce' =>  wp_create_nonce("pn_notification"),
					'pn_config'=> $messageConfig,
					"swsource" => esc_url_raw(trailingslashit($link)."?push_notification_sw=1"),
					"scope" => esc_url_raw(trailingslashit($link)),
					"ajax_url"=> esc_url_raw(admin_url('admin-ajax.php'))
					);
        return $settings;
	}

	function load_admin_scripts($hook_suffix){
		if($hook_suffix=='toplevel_page_push-notification'){
			wp_enqueue_script('push_notification_script', PUSH_NOTIFICATION_PLUGIN_URL.'/assets/main-admin-script.js', array('jquery'), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
			wp_enqueue_style('push-notification-style', PUSH_NOTIFICATION_PLUGIN_URL.'/assets/main-admin-style.css', array('dashboard'), PUSH_NOTIFICATION_PLUGIN_VERSION, 'all');
			 if ( is_multisite() ) {
	            $link = get_site_url();              
	        }
	        else {
	            $link = home_url();
	        }    
	        $object = array(
							"home_url"=>  esc_url_raw($link),
							"ajax_url"=> esc_url_raw(admin_url('admin-ajax.php')),
							"remote_nonce"=> wp_create_nonce("pn_notification")
							);
	        
	        $object = apply_filters('pushnotification_localize_filter',$object, 'pn_setings');
			wp_localize_script("push_notification_script", 'pn_setings', $object);
		}
	}

	public function add_menu_links(){
		// Main menu page
		add_menu_page( esc_html__( 'Push Notification', 'push-notification' ), 
	                esc_html__( 'Push Notifications', 'push-notification' ), 
	                'manage_options',
	                'push-notification',
	                array($this, 'admin_interface_render'),
	                '', 100 );
		
		// Settings page - Same as main menu page
		add_submenu_page( 'push-notification',
	                esc_html__( 'Push Notifications Options', 'push-notification' ),
	                esc_html__( 'Settings', 'push-notification' ),
	                'manage_options',
	                'push-notification',
	                array($this, 'admin_interface_render')
	            );
	}
	function admin_interface_render(){
    
		// Authentication
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?><div class="wrap push_notification-settings-wrap">
			<h1 class="page-title"><?php echo esc_html__('Push Notifications Options', 'push-notification'); ?></h1>
			<form action="options.php" method="post" enctype="multipart/form-data" class="push_notification-settings-form">		
				<div class="form-wrap">
					<?php
					settings_fields( 'push_notification_setting_dashboard_group' );
					echo "<div class='push_notification-dashboard'>";
						// Status
						do_settings_sections( 'push_notification_dashboard_section' );	// Page slug
						$authData = push_notification_auth_settings();
						if(isset($authData['token_details']['validated']) && $authData['token_details']['validated']==1){
							$this->shownotificationData();
						}
					echo "</div>";
					?>
				</div>
			</form>
		</div><?php
	}

	public function settings_init(){
		register_setting( 'push_notification_setting_dashboard_group', 'push_notification_settings' );

		add_settings_section('push_notification_dashboard_section',
					 esc_html__('','push-notification'), 
					 '__return_false', 
					 'push_notification_dashboard_section');
		
			add_settings_field(
				'pn_key_validate_status',	// ID
				esc_html__('API', 'push-notification'),			// Title
				array( $this, 'pn_key_validate_status_callback'),// Callback
				'push_notification_dashboard_section',	// Page slug
				'push_notification_dashboard_section'	// Settings Section ID
			);

		add_settings_section('push_notification_user_settings_section',
					 esc_html__('','push-notification'), 
					 '__return_false', 
					 'push_notification_user_settings_section');
            if (!function_exists('pwaforwp_defaultSettings')) {
                add_settings_field(
					'pn_key__notification_icon_edit',					// ID
					esc_html__('Notification icon URL', 'push-notification'),// Title
					array( $this, 'user_settings_notification_icon_callback'),// Callback
					'push_notification_user_settings_section',	// Page slug
					'push_notification_user_settings_section'	// Settings Section ID
				);
            }
			
			add_settings_field(
				'pn_key_sendpush_edit',					// ID
				esc_html__('Send notification on editing', 'push-notification'),// Title
				array( $this, 'user_settings_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_user_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_sendpush_publish',								// ID
				esc_html__('Send notification on publish', 'push-notification'),// Title
				array( $this, 'user_settings_onpublish_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_user_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_posttype_select',								// ID
				esc_html__('Send notification on publish', 'push-notification'),// Title
				array( $this, 'pn_key_posttype_select_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_user_settings_section'	// Settings Section ID
			);
		
	}

	function shownotificationData(){
		$auth_settings = push_notification_auth_settings();
		$detail_settings = push_notification_details_settings();
		if( !$detail_settings && isset( $auth_settings['user_token'] ) ){
			 PN_Server_Request::getsubscribersData( $auth_settings['user_token'] );
			 $detail_settings = push_notification_details_settings();
		}
		
		$updated_at = '';
		if(isset($detail_settings['updated_at'])){
			//echo $detail_settings['updated_at'];die;
			$updated_at = human_time_diff( strtotime( $detail_settings['updated_at'] ), strtotime( date('Y-m-d H:i:s') ) );
			if(!empty( $updated_at ) ){
				$updated_at .= " ago";
			} 


		}
		$subscriber_count = 0;
		if(isset($detail_settings['subscriber_count'])){ $subscriber_count = $detail_settings['subscriber_count']; }
		echo '<section class="pn_general_wrapper">
				<div class="action-wrapper"> '.esc_html__($updated_at, 'push-notification').' <button type="button" class="button" id="grab-subscribers-data" class="dashicons dashicons-update">'.esc_html__('Refresh data', 'push-notification').'</button>
				</div>
				<div class="pn-content">
					<div class="pn-card-wrapper">
						<div class="pn-card">
							<div class="title-name">'.esc_html__('Total Subscribers', 'push-notification').':</div>
							<div class="desc column-description">
								'.esc_html($subscriber_count).'
							</div>
						</div>
					</div>
				</div>
				';
				do_settings_sections( 'push_notification_user_settings_section' );
		echo   '<input type="submit" value="'.esc_html__('Save Settings', 'push-notification').'" class="button">
			</section>
			';
		echo '<br/><br/><div class="pn-other-settings-options">
					<div id="dashboard_right_now" class="postbox " >
						<h2 class="hndle">'.esc_html__('Send Custom Notification', 'push-notification').'</h2>
						<div class="inside">
							<div class="main">
								<div class="form-group">
									<label for="notification-title">'.esc_html__('Title','push-notification').'</label>
									<input type="text" id="notification-title" class="regular-text">
								</div>
								<div class="form-group">
									<label for="notification-link">'.esc_html__('Link', 'push-notification').'</label>
									<input type="text" id="notification-link" class="regular-text">
								</div>
								<div class="form-group">
									<label for="notification-imageurl">'.esc_html__('Image url', 'push-notification').'</label>
									<input type="text" id="notification-imageurl" class="regular-text">
								</div>
								<div class="form-group">
									<label for="notification-message">'.esc_html__('Message', 'push-notification').'</label>
									<textarea type="text" id="notification-message" class="regular-text"></textarea>
								</div>
								<input type="button" class="button" id="pn-send-custom-notification" value="'.esc_html__('Send Notification', 'push-notification').'">
								<div class="pn-send-messageDiv"></div>
							</div>
						</div>
					</div>
				</div>';
	}

	public function pn_key_validate_status_callback(){
		$authData = push_notification_auth_settings();
		if( !isset($authData['token_details']['validated']) 
			|| (isset($authData['token_details']) && $authData['token_details']['validated']!=1) ){
			echo "<fieldset>";
			PN_Field_Generator::get_input_password('user_token', 'user_auth_token_key');
			PN_Field_Generator::get_button('Validate', 'user_auth_vadation');
			echo '<span class="resp_message"></span></fieldset>
			<p>'.esc_html__('This plugin requires a free API key form PushNotification.io', 'push-notification').' <a target="_blank" href="'.esc_url_raw(PN_Server_Request::$notificationlanding."register").'">'.esc_html__('Get the Key', 'push-notification').'</a></p>';
		}else{
			echo "<input type='text' class='regular-text' value='xxxxxxxxxxxxxxxxxx'>
				<span class='text-success resp_message' style='color:green;'>".esc_html__('User Verified', 'push-notification')."</span>
				<button type='button' class='button dashicons-before dashicons-no-alt' id='pn-remove-apikey' style='margin-left:10%; line-height: 1.4;'>".esc_html__('Revoke key', 'push-notification')."</button>";
		}
		echo "<br/><br/><div>Need help! Read the Complete <a href='https://pushnotifications.helpscoutdocs.com/' target='_blank'>Documentation</a>.</div><br/>";

	}//function closed
	
	public function user_settings_notification_icon_callback(){
		PN_Field_Generator::get_input('notification_icon', '1', 'pn_push_on_edit', 'pn-checkbox pn_push_on_edit');
	}

	public function user_settings_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input_checkbox('on_edit', '1', 'pn_push_on_edit', 'pn-checkbox pn_push_on_edit');

	}
	public function user_settings_onpublish_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input_checkbox('on_publish', '1', 'pn_push_on_publish', 'pn-checkbox pn_push_on_publish');

	}
	public function pn_key_posttype_select_callback(){
		$notification = push_notification_settings();
		$data = get_post_types();
		PN_Field_Generator::get_input_multi_select('posttypes', array('post'), $data, 'pn_push_on_publish', '');

	}

	public function pn_verify_user(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>'Request not authorized'));die;
		}else{
			$user_token = sanitize_text_field($_POST['user_token']);
			$response = PN_Server_Request::varifyUser($user_token);
			if( function_exists('pwaforwp_required_file_creation') ){
				$pwaSettings = pwaforwp_defaultSettings();
				if( $pwaSettings['notification_feature']==1 && isset($pwaSettings['notification_options']) && $pwaSettings['notification_options']=='pushnotifications_io'){
					pwaforwp_required_file_creation();
				}
			}

			echo json_encode($response);die;
		}
		

		echo json_encode(array("status"=> 503, 'message'=>'Request not identified'));die;
	}
	
	public function pn_revoke_keys(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>'Request not authorized'));die;
		}else{
			delete_option('push_notification_auth_settings');
			echo json_encode(array("status"=> 200, 'message'=>'API key removed successfully'));die;
		}
	}

	public function pn_subscribers_data(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>'Request not authorized'));die;
		}else{
			$auth_settings = push_notification_auth_settings();
			if( isset( $auth_settings['user_token'] ) ){
				 PN_Server_Request::getsubscribersData( $auth_settings['user_token'] );
				 echo json_encode(array("status"=> 200, 'message'=>'Data updated'));die;
			}else{
				echo json_encode(array("status"=> 503, 'message'=>'User token not found'));die;	
			}
			
		}
	}
	public function pn_send_notification(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>'Request not authorized'));die;
		}else{
			$auth_settings = push_notification_auth_settings();
			$notification_settings = push_notification_settings();

			$title = sanitize_text_field($_POST['title']);
			$message = sanitize_textarea_field($_POST['message']);
			$link_url = esc_url_raw($_POST['link_url']);
			$image_url = esc_url_raw($_POST['image_url']);
			$icon_url = $notification_settings['notification_icon'];
			if( isset( $auth_settings['user_token'] ) ){
				$response = PN_Server_Request::sendPushNotificatioData( $auth_settings['user_token'], $title,$message, $link_url, $icon_url, $image_url );
				if($response){
				 echo json_encode($response);die;
				}else{
					echo json_encode(array("status"=> 403, 'message'=>'Request not completed'));die;
				}
			}else{
				echo json_encode(array("status"=> 503, 'message'=>'User token not found'));die;	
			}
			
		}
	}

	public function send_notification_on_update($new_status, $old_status, $post){
		$pn_settings = push_notification_settings();
		$auth_settings = push_notification_auth_settings();

		if ( 'publish' !== $new_status ){
        	return;
		}
		if(!isset($pn_settings['posttypes'])){
			$pn_settings['posttypes'] = array("post");
		}
		if( !in_array( get_post_type($post), $pn_settings['posttypes']) ){
			return;
		}
		
		$send_notification = false;
		//for Edit
		if(isset($pn_settings['on_edit']) && $pn_settings['on_edit']==1){
			if ( $new_status === $old_status) {
			 	$this->send_notification($post);
			 	$send_notification = true;
			}
		}
		//for publish
		if(!$send_notification && isset($pn_settings['on_publish']) && $pn_settings['on_publish']==1){
			if ( $new_status !== $old_status) {
			 	$this->send_notification($post);
			}
		}
			

	}
	protected function send_notification($post){
		$post_id = $post->ID;
		$post_content = $post->post_content;
		$post_title = $post->post_title;
		$auth_settings = push_notification_auth_settings();

		//API Data
		$title = sanitize_text_field(wp_strip_all_tags($post_title, true) );
		$message = wp_trim_words(wp_strip_all_tags(sanitize_text_field($post_content), true), 20);
		$link_url = esc_url_raw(get_permalink( $post_id ));
		$image_url = '';
		if(has_post_thumbnail($post_id)){
			$image_url = esc_url_raw(get_the_post_thumbnail_url($post_id));
		}
		$push_notification_settings = push_notification_settings();
		$icon_url = $push_notification_settings['notification_icon'];
		
		if( isset( $auth_settings['user_token'] ) && !empty($auth_settings['user_token']) ){
			$response = PN_Server_Request::sendPushNotificatioData( $auth_settings['user_token'], $title, $message, $link_url, $icon_url, $image_url );
		}//auth token check 
	
	}

	public function pn_get_layout_files($filePath){
	    $fileContentResponse = @wp_remote_get(esc_url_raw(PUSH_NOTIFICATION_PLUGIN_URL.'/assets/'.$filePath));
	    if(wp_remote_retrieve_response_code($fileContentResponse)!=200){
	      if(!function_exists('get_filesystem_method')){
	        require_once( ABSPATH . 'wp-admin/includes/file.php' );
	      }
	      $access_type = get_filesystem_method();
	      if($access_type === 'direct')
	      {
	      	$file = PUSH_NOTIFICATION_PLUGIN_DIR.($filePath);
	         $creds = request_filesystem_credentials($file, '', false, false, array());
	        if ( ! WP_Filesystem($creds) ) {
	          return false;
	        }   
	        global $wp_filesystem;
	        $htmlContentbody = $wp_filesystem->get_contents($file);
	        return $htmlContentbody;
	      }
	      return false;
	    }else{
	      return wp_remote_retrieve_body( $fileContentResponse );
	    }
	}

	/**
	* show notices if API is not entered in option panel
	*/
	function admin_notices_opt(){
		global $pagenow;
		$auth_settings = push_notification_auth_settings();
		if( !isset( $auth_settings['user_token'] ) || (isset( $auth_settings['user_token'] ) && empty($auth_settings['user_token']) ) ){
	         echo sprintf('<div class="notice notice-warning is-dismissible">
				             <p>%s <a href="%s">%s</a>.</p>
				         </div>',
				         esc_html__('Push Notification is require API, Please enter', 'push-notification'),
				         admin_url('admin.php?page=push-notification'),
				         esc_html__('API key', 'push-notification')
				     );
	    }
	}

	public function pn_subscribe_newsletter(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>'Request not authorized'));die;
		}else{
		    $api_url = 'http://magazine3.company/wp-json/api/central/email/subscribe';
		    $api_params = array(
		        'name' => sanitize_text_field($_POST['name']),
		        'email'=> sanitize_text_field($_POST['email']),
		        'website'=> sanitize_text_field($_POST['website']),
		        'type'=> 'pushnotification'
		    );
		    $response = wp_remote_post( $api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		    $response = wp_remote_retrieve_body( $response );
		    echo $response;
		    die;
		}
	}

	
}

if(is_admin() || wp_doing_ajax()){
	$push_Notification_Admin_Obj  = new Push_Notification_Admin(); 
	$push_Notification_Admin_Obj->init();
}

function push_notification_settings(){
	$push_notification_settings = get_option( 'push_notification_settings', array() ); 
	$icon = PUSH_NOTIFICATION_PLUGIN_URL.'/assets/image/bell-icon.png';
	if(function_exists('pwaforwp_defaultSettings')){
		$pwaforwpSettings = pwaforwp_defaultSettings();
		$icon = $pwaforwpSettings['icon'];
	}
	$default = array(
		'notification_icon' => $icon,
		'on_edit'=> 0,
		'on_publish'=> 1,
		'posttypes'=> array("post","page"),
	);
	$push_notification_settings = wp_parse_args($push_notification_settings, $default);
	return $push_notification_settings;
}
function push_notification_auth_settings(){
	$push_notification_auth_settings = get_option( 'push_notification_auth_settings', array() ); 
	return $push_notification_auth_settings;
}
function push_notification_details_settings(){
	$push_notification_details_settings = get_option( 'push_notification_details_settings', array() ); 
	return $push_notification_details_settings;
}


/** 
* Server Side fields generation class
*/
class PN_Field_Generator{
	static $settingName = 'push_notification_settings';

	public static function get_input($name, $id="", $class=""){
		$settings = push_notification_settings();
		?><input type="text" name="<?php echo esc_attr(self::$settingName); ?>[<?php echo esc_attr($name); ?>]" class="regular-text" id="<?php echo esc_attr($id); ?>" value="<?php if ( isset( $settings[$name] ) && ( ! empty($settings[$name]) ) ) echo esc_attr($settings[$name]); ?>"/><?php
	}
	public static function get_input_checkbox($name, $value, $id="", $class=""){
		$settings = push_notification_settings();
		?><input type="checkbox" name="<?php echo esc_attr(self::$settingName); ?>[<?php echo esc_attr($name); ?>]" class="regular-text" id="<?php echo esc_attr($id); ?>" <?php if ( isset( $settings[$name] ) && $settings[$name]==$value ) echo esc_attr("checked"); ?> value="<?php echo esc_attr($value); ?>"/><?php
	}
	public static function get_input_multi_select($name, $value, $options, $id="", $class=""){
		$settings = push_notification_settings();
		if( isset($settings[$name]) ){
			$value = $settings[$name];
		}
		?><select multiple name="<?php echo esc_attr(self::$settingName); ?>[<?php echo esc_attr($name); ?>][]" class="regular-text" id="<?php echo esc_attr($id); ?>" >
			<?php foreach ($options as $key => $opt) {
				$sel = '';
				if(isset($value) && in_array($key, $value)){
					$sel = 'selected';
				}
				echo '<option value="'.$key.'" '.$sel.'>'.$opt.'</option>';
			} ?>
		</select><?php
	}
	public static function get_input_password($name, $id="", $class=""){
		$settings = push_notification_settings();
		?><input type="password" name="<?php echo esc_attr(self::$settingName); ?>[<?php echo esc_attr($name); ?>]" class="regular-text" id="<?php echo esc_attr($id); ?>" value="<?php if ( isset( $settings[$name] ) && ( ! empty($settings[$name]) ) ) echo esc_attr($settings[$name]); ?>"/><?php
	}
	public static function get_button($name, $id="", $class=""){
		$settings = push_notification_settings();
		?>
		<button type="button"  class="button <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($id); ?>"><?php echo esc_html__($name) ?></button>
	<?php
	} 	
}
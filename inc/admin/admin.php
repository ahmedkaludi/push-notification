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
		add_action( 'wp_ajax_pn_send_notification_on_category', array( $this, 'pn_send_notification_on_category' ) ); 
		add_action('wp_ajax_pn_send_query_message', 'pn_send_query_message');
		add_action('wp_ajax_pn_subscribe_newsletter',array( $this, 'pn_subscribe_newsletter' ) );
		//on oreder status change
		add_action('woocommerce_order_status_changed', array( $this, 'pn_order_send_notification'), 10, 4);


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
                            if(!messaging){const messaging = firebase.messaging;}';
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
			wp_enqueue_script('push_notification_script', PUSH_NOTIFICATION_PLUGIN_URL.'assets/main-admin-script.js', array('jquery'), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
			wp_enqueue_style('push-notification-style', PUSH_NOTIFICATION_PLUGIN_URL.'assets/main-admin-style.css', array('dashboard'), PUSH_NOTIFICATION_PLUGIN_VERSION, 'all');
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
			<div class="push-notification-main-wrapper">
				<h2 class="nav-tab-wrapper push-notification-tabs">
					<?php
					$authData = push_notification_auth_settings();
					if( (isset($authData['token_details']) && $authData['token_details']['validated']==1) ){
						$plugin_icon_color = "#008416;";
					}else{
						$plugin_icon_color = "#000;";
					}
					echo '<a href="' . esc_url('#pn_connect') . '" link="pn_connect" class="nav-tab nav-tab-active"><span class="dashicons dashicons-admin-plugins" style="color:'.$plugin_icon_color.'"></span> ' . esc_html__('Connect','push-notification') . '</a>';
					echo '<a href="' . esc_url('#pn_dashboard') . '" link="pn_dashboard" class="nav-tab"><span class="dashicons dashicons-dashboard"></span> ' . esc_html__('Dashboard','push-notification') . '</a>';
					echo '<a href="' . esc_url('#pn_notification_bell') . '" link="pn_notification_bell" class="nav-tab"><span class="dashicons dashicons-bell"></span> ' . esc_html__('Notification','push-notification') . '</a>';
					if( !empty($authData['token_details']) && !empty($authData['token_details']['user_payment']) ){
						if( (isset($authData['token_details']) && $authData['token_details']['user_payment']==1) ){
							echo '<a href="' . esc_url('#pn_segmentation') . '" link="pn_segmentation" class="nav-tab"><span class="dashicons dashicons-admin-generic"></span> ' . esc_html__('Segmentation','push-notification') . '</a>';
						}
					}
					echo '<a href="' . esc_url('#pn_help') . '" link="pn_help" class="nav-tab"><span class="dashicons dashicons-editor-help"></span> ' . esc_html__('Help','push-notification') . '</a>';
					?>
				</h2>
			</div>

				<form action="options.php" method="post" enctype="multipart/form-data" class="push_notification-settings-form">		
					<div class="form-wrap">
						<?php
						settings_fields( 'push_notification_setting_dashboard_group' );
						echo "<div class='push_notification-dashboard'>";
							// Status
							echo "<div id='pn_connect' class='pn-tabs'>";
								do_settings_sections( 'push_notification_dashboard_section' );	// Page slug
							echo "</div>";
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
					 esc_html__(' ','push-notification'), 
					 '__return_false', 
					 'push_notification_dashboard_section');

			add_settings_field(
				'pn_key_validate_status',	// ID
				esc_html__('API', 'push-notification'),			// Title
				array( $this, 'pn_key_validate_status_callback'),// Callback
				'push_notification_dashboard_section',	// Page slug
				'push_notification_dashboard_section'	// Settings Section ID
			);
		add_settings_section('push_notification_segment_settings_section',
					 esc_html__('Notification Segment','push-notification'), 
					 '__return_false', 
					 'push_notification_segment_settings_section');
			add_settings_field(
				'pn_key_segment_select',								// ID
				esc_html__('Select segmentation for notification', 'push-notification'),// Title
				array( $this, 'pn_key_segment_select_callback'),// Callback
				'push_notification_segment_settings_section',	// Page slug
				'push_notification_segment_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_segment_on_categories',								// ID
				esc_html__('Segment on Categories', 'push-notification'),// Title
				array( $this, 'pn_key_segment_on_categories_callback'),// Callback
				'push_notification_segment_settings_section',	// Page slug
				'push_notification_segment_settings_section'	// Settings Section ID
			);

		add_settings_section('push_notification_user_settings_section',
					 esc_html__(' ','push-notification'), 
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
			
			// add_settings_field(
			// 	'pn_key_sendpush_edit',					// ID
			// 	esc_html__('Send notification on editing', 'push-notification'),// Title
			// 	array( $this, 'user_settings_callback'),// Callback
			// 	'push_notification_user_settings_section',	// Page slug
			// 	'push_notification_user_settings_section'	// Settings Section ID
			// );
			add_settings_field(
				'pn_key_sendpush_publish',								// ID
				esc_html__('Send notification on publish', 'push-notification'),// Title
				array( $this, 'user_settings_onpublish_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_user_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_posttype_select',								// ID
				esc_html__('Send notification on', 'push-notification'),// Title
				array( $this, 'pn_key_posttype_select_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_user_settings_section'	// Settings Section ID
			);

		add_settings_section('push_notification_utm_tracking_settings_section',
					 esc_html__('UTM tracking','push-notification'), 
					 '__return_false', 
					 'push_notification_user_settings_section');
			add_settings_field(
				'pn_utm_tracking_select',								// ID
				esc_html__('Enable', 'push-notification'),// Title
				array( $this, 'pn_utm_tracking_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_utm_tracking_settings_section'	// Settings Section ID
			);

		add_settings_section('push_notification_notification_settings_section',
					 esc_html__('Notification settings','push-notification'), 
					 '__return_false', 
					 'push_notification_user_settings_section');
			add_settings_field(
				'pn_key_message_position_select',								// ID
				esc_html__('Where would you like to display', 'push-notification'),// Title
				array( $this, 'pn_key_position_select_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_notification_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_popup_message_select',								// ID
				esc_html__('Popup banner message', 'push-notification'),// Title
				array( $this, 'pn_key_banner_message_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_notification_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_popup_accept_btn',								// ID
				esc_html__('Popup banner accept', 'push-notification'),// Title
				array( $this, 'pn_key_banner_accept_btn_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_notification_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_popup_decline_btn',								// ID
				esc_html__('Popup banner decline', 'push-notification'),// Title
				array( $this, 'pn_key_banner_decline_btn_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_notification_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_show_again_on',								// ID
				esc_html__('Popup show again', 'push-notification'),// Title
				array( $this, 'pn_key_popupshowagain_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_notification_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_prompt_after_nseconds',								// ID
				esc_html__('Popup show after n seconds', 'push-notification'),// Title
				array( $this, 'pn_key_popupshowafternseconds_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_notification_settings_section'	// Settings Section ID
			);
			add_settings_field(
				'pn_key_prompt_after_npageview',								// ID
				esc_html__('Popup show after n pages view', 'push-notification'),// Title
				array( $this, 'pn_key_popupshowafternpageview_callback'),// Callback
				'push_notification_user_settings_section',	// Page slug
				'push_notification_notification_settings_section'	// Settings Section ID
			);

		//WC compatiblility
		add_settings_section('push_notification_user_wc_settings_section',
					 esc_html__('WooCommerce settings','push-notification'), 
					 '__return_false', 
					 'push_notification_user_wc_settings_section');
            add_settings_field(
				'pn_wc_notification_orderchgn_edit',					// ID
				esc_html__('Notification on order change', 'push-notification'),// Title
				array( $this, 'user_notification_order_change_callback'),// Callback
				'push_notification_user_wc_settings_section',	// Page slug
				'push_notification_user_wc_settings_section'	// Settings Section ID
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
		echo '<div id="pn_dashboard" style="display:none;" class="pn-tabs">
		<section class="pn_general_wrapper">
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
		echo   '<input type="submit" value="'.esc_html__('Save Settings', 'push-notification').'" class="button pn-submit-button">
			</section>
			</div>
			';

		echo '<div id="pn_segmentation" style="display:none" class="pn-tabs">
		<section class="pn_general_wrapper">';
				do_settings_sections( 'push_notification_segment_settings_section' );
		echo   '<input type="submit" value="'.esc_html__('Save Settings', 'push-notification').'" class="button pn-submit-button">
			</section>
			</div>
			';
		if ( class_exists( 'WooCommerce' ) ) {
			echo '<div id="pn_wc_settings_section" style="display:none;" class="pn-tabs">
				<section style="margin-top:20px"><div class="postbox" style="padding:20px">';
					do_settings_sections( 'push_notification_user_wc_settings_section' );
					echo   '<input type="submit" value="'.esc_html__('Save Settings', 'push-notification').'" class="button pn-submit-button">';
				echo '</div></section>
			</div>';
		}
		echo '<br/><br/><div id="pn_notification_bell" class="pn-other-settings-options pn-tabs" style="display:none">
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
								<input type="button" class="button pn-submit-button" id="pn-send-custom-notification" value="'.esc_html__('Send Notification', 'push-notification').'">
								<div class="pn-send-messageDiv"></div>
							</div>
						</div>
					</div>
				</div>

				<div id="pn_help" style="display:none;" class="pn-tabs">
					<h3>'.esc_html__('Documentation', 'push-notification').'</h3>
					<a target="_blank" class="button pn-submit-button" href="https://pushnotifications.helpscoutdocs.com/">'.esc_html__('View Setup Documentation', 'push-notification').'</a>

                   	<h3>'.esc_html__('Ask for Technical Support', 'push-notification') .'</h3>
                   	<p>'.esc_html__('We are always available to help you with anything', 'push-notification').'</p>
		            <ul>
		                <li><label for="pn_help_query_customer">'.esc_html__('Are you existing Premium Customer?', 'push-notification').'</label>
		                    <select class="regular-select" rows="5" cols="60" id="pn_help_query_customer" name="pn_help_query_customer">
		                    	<option value="">Select</option>
		                    	<option value="Yes">'.esc_html__('Yes', 'push-notification').'</option>
		                    	<option value="No">'.esc_html__('No', 'push-notification').'</option>
		                    </select>
		                </li> 
		                <li><label for="pn_help_query_message">'.esc_html__('Message', 'push-notification').'</label>
		                    <textarea rows="5" cols="60" id="pn_help_query_message" name="pn_help_query_message" class="regular-textarea"></textarea>
		                    <br>
		                    <p class="pn_help-query-success" style="display:none;">'.esc_html__('Message sent successfully, Please wait we will get back to you shortly', 'push-notification').'</p>
		                    <p class="pn_help-query-error" style="display:none;">'.esc_html__('Message not sent. please check your network connection', 'push-notification').'</p>
		                </li> 
		                <li><button class="button pn_help-send-query pn-submit-button">'.esc_html__('Send Message', 'push-notification').'</button></li>
		            </ul>            		                  
		        </div>
				';
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
				<button type='button' class='button dashicons-before dashicons-no-alt pn-submit-button' id='pn-remove-apikey' style='margin-left:10%; line-height: 1.4;'>".esc_html__('Revoke key', 'push-notification')."</button>";
		}
		echo "<button type='button' class='button dashicons-before dashicons-update pn-submit-button' id='pn-refresh-apikey' style='margin-left:2%; line-height: 1.4;'>".esc_html__('Fetch Premium API', 'push-notification')."</button>";
		echo "<br/><br/><div>".esc_html__('Need help! Read the Complete', 'push-notification')."<a href='https://pushnotifications.helpscoutdocs.com/' target='_blank'>".esc_html__('Documentation', 'push-notification')."</a>.</div><br/>";
	}//function closed

	public function user_settings_notification_icon_callback(){
		PN_Field_Generator::get_input('notification_icon', '1', 'pn_push_on_edit', 'pn-checkbox pn_push_on_edit');
	}

	// public function user_settings_callback(){
	// 	$notification = push_notification_settings();
	// 	PN_Field_Generator::get_input_checkbox('on_edit', '1', 'pn_push_on_edit', 'pn-checkbox pn_push_on_edit');
	// }

	public function user_settings_onpublish_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input_checkbox('on_publish', '1', 'pn_push_on_publish', 'pn-checkbox pn_push_on_publish');
	}

	public function pn_key_posttype_select_callback(){
		$notification = push_notification_settings();
		$data = get_post_types();
		$data = array_merge(array('none'=>'None'), $data);
		// print_r($data);
		PN_Field_Generator::get_input_multi_select('posttypes', array('post'), $data, 'pn_push_on_publish', '');
	}
	public function pn_key_segment_select_callback(){
		$notification = push_notification_settings();
		$name = 'on_category';
		$value = 1;$class = $id = 'pn_push_on_category_checkbox';
		echo '<div class="pn-field_wrap">';
			PN_Field_Generator::get_input_checkbox($name, $value, $id, $class);
		echo '</div>';
	}
	public function pn_key_segment_on_categories_callback(){
		$notification = push_notification_settings();
		$display="style='display:none;'";
		if(isset($notification['on_category']) && $notification['on_category']){
			$display="style='display:block;'";
		}
		echo "<div id='category_selector_wrapper' ".$display.">";
			echo '<div class="pn-field_wrap">';
				PN_Field_Generator::get_input_checkbox('segment_on_category', '1', 'pn_push_segment_on_category_checkbox', 'pn-checkbox pn_push_segment_on_category_checkbox');
			echo '</div>';
			$display_category="style='display:none;'";
			if(isset($notification['segment_on_category']) && $notification['segment_on_category']){
				$display_category="style='display:block;'";
			}
			echo "<div id='segment_category_selector_wrapper' ".$display_category.">";
				$data = get_categories();
				PN_Field_Generator::get_multi_input_checkbox('category_checkbox', '1', $data, '', '', 'pn-checkbox pn_push_category_checkbox', '');
				PN_Field_Generator::get_input_category('category', 'pn_push_segment_category_input', '');
			echo '</div>
		</div>';
	}
	public function pn_utm_tracking_callback(){
		$notification = push_notification_settings();
		$name = 'utm_tracking_checkbox';
		$value = 1;$class = $id = 'utm_tracking_checkbox';
		PN_Field_Generator::get_input_checkbox($name, $value, $id, $class);
		$display="style='display:none;'";
		if(isset($notification['utm_tracking_checkbox']) && $notification['utm_tracking_checkbox']){
			$display="style='display:block;'";
		}
		echo "<div id='utm_tracking_wrapper' ".$display.">";
			echo '<div class="pn-field_wrap"><label>'.esc_html__('UTM source', 'push-notification').'</label>';
				PN_Field_Generator::get_input('notification_utm_source', 'notification_utm_source', '');
			echo '</div>';
			echo '<div class="pn-field_wrap"><label>'.esc_html__('UTM Medium', 'push-notification').'</label>';
			PN_Field_Generator::get_input('notification_utm_medium', 'notification_utm_medium', '');
			echo '</div>';
			echo '<div class="pn-field_wrap"><label>'.esc_html__('UTM Campaign', 'push-notification').'</label>';
			PN_Field_Generator::get_input('notification_utm_campaign', 'notification_utm_campaign', '');
			echo '</div>';
			echo '<div class="pn-field_wrap"><label>'.esc_html__('UTM Term', 'push-notification').'</label>';
			PN_Field_Generator::get_input('notification_utm_term', 'notification_utm_term', '');
			echo '</div>';
			echo '<div class="pn-field_wrap" style="display:flex;"><label>'.esc_html__('UTM Content', 'push-notification').'</label>';
			PN_Field_Generator::get_input('notification_utm_content', 'notification_utm_content', '');
			echo '</div>';
		echo "</div>";
	}
	public function pn_key_position_select_callback(){
		$notification = push_notification_settings();
		$data = array(
			'top-left'=> esc_html__('Top left', 'push-notification'),
			'top-right'=> esc_html__('Top right', 'push-notification'),
			'bottom-right'=> esc_html__('Bottom right', 'push-notification'),
			'bottom-left'=> esc_html__('Bottom Left', 'push-notification'),
		);
		PN_Field_Generator::get_input_select('notification_position', 'bottom-left', $data, 'pn_push_on_publish', '');
	}
	public function pn_key_banner_message_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input('popup_banner_message', 'popup_banner_message_id');
	}

	public function pn_key_banner_accept_btn_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input('popup_banner_accept_btn', 'popup_banner_accept_btn_id');
	}
	public function pn_key_banner_decline_btn_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input('popup_banner_decline_btn', 'popup_banner_decline_btn_id');
	}
	public function pn_key_popupshowagain_callback(){
		PN_Field_Generator::get_input('notification_popup_show_again', 'notification_popup_show_again', '');
		echo "<p class='help'> ".esc_html__('Show Popup again after decline by the user (in Days)', 'push-notification')." </p>";
	}
	public function pn_key_popupshowafternseconds_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input('notification_popup_show_afternseconds', 'notification_popup_show_afternseconds', '');
		echo "<p class='help'> ".esc_html__('Show Popup after n seconds (in Seconds)', 'push-notification')." </p>";
	}
	public function pn_key_popupshowafternpageview_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input('notification_popup_show_afternpageview', 'notification_popup_show_afternpageview', '');
		echo "<p class='help'> ".esc_html__('Show Popup after nth page view (Default 1)', 'push-notification')." </p>";
	}

	public function user_notification_order_change_callback(){
		$notification = push_notification_settings();
		PN_Field_Generator::get_input_checkbox('notification_on_order_change_to_user', 1, 'send_notification_to_user_order', "", esc_html__("To User", 'push-notification'));
		PN_Field_Generator::get_input_checkbox('notification_on_order_change_to_admin', 1, 'send_notification_to_admin_order', "", esc_html__("To Admin", 'push-notification'));
		echo "<p class='description'>".esc_html__('Send notification when order status will change',"push-notification")."</p>";
	}

	public function pn_verify_user(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));die;
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

		echo json_encode(array("status"=> 503, 'message'=>esc_html__('Request not identified','push-notification')));die;
	}
	public function notification_refresh_api_key(){
		$authData = push_notification_auth_settings();
		$verifyUrl = 'validate/user';
		if ( is_multisite() ) {
            $weblink = get_site_url();
        }
		else {
            $weblink = home_url();
        }    
		$data = array("user_token"=>$authData['user_token'], "website"=>   $weblink);
		$response = PN_Server_Request::varifyUser($authData['user_token']);
		echo json_encode($response);
	}
	public function pn_revoke_keys(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));die;
		}else{
			delete_option('push_notification_auth_settings');
			echo json_encode(array("status"=> 200, 'message'=>esc_html__('API key removed successfully', 'push-notification')));die;
		}
	}

	public function pn_subscribers_data(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));die;
		}else{
			$auth_settings = push_notification_auth_settings();
			if( isset( $auth_settings['user_token'] ) ){
				 PN_Server_Request::getsubscribersData( $auth_settings['user_token'] );
				 echo json_encode(array("status"=> 200, 'message'=>esc_html__('Data updated', 'push-notification')));die;
			}else{
				echo json_encode(array("status"=> 503, 'message'=> esc_html__('User token not found', 'push-notification')));die;	
			}

		}

	}
	public function pn_send_notification(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));die;
		}else{
			$auth_settings = push_notification_auth_settings();
			$notification_settings = push_notification_settings();

			$title = sanitize_text_field($_POST['title']);
			$message = sanitize_textarea_field($_POST['message']);
			$link_url = esc_url_raw($_POST['link_url']);
			$image_url = esc_url_raw($_POST['image_url']);
			$icon_url = $notification_settings['notification_icon'];

			if(isset($notification_settings['utm_tracking_checkbox']) && $notification_settings['utm_tracking_checkbox']){
				$utm_details = array(
				    'utm_source'=> $notification_settings['notification_utm_source'],
				    'utm_medium'=> $notification_settings['notification_utm_medium'],
				    'utm_campaign'=> $notification_settings['notification_utm_campaign'],
				    'utm_term'  => $notification_settings['notification_utm_term'],
				    'utm_content'  => $notification_settings['notification_utm_content'],
				    );
				$link_url = add_query_arg( array_filter($utm_details), $link_url  );
			}

			if( isset( $auth_settings['user_token'] ) ){
				$response = PN_Server_Request::sendPushNotificatioData( $auth_settings['user_token'], $title,$message, $link_url, $icon_url, $image_url, '' );
				if($response){
				 echo json_encode($response);die;
				}else{
					echo json_encode(array("status"=> 403, 'message'=>esc_html__('Request not completed', 'push-notification')));die;
				}
			}else{
				echo json_encode(array("status"=> 503, 'message'=>esc_html__('User token not found', 'push-notification')));die;	
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
		// if(isset($pn_settings['on_edit']) && $pn_settings['on_edit']==1){
		// 	if ( $new_status === $old_status) {
		// 	 	$this->send_notification($post);
		// 	 	$send_notification = true;
		// 	}
		// }
		//for publish
		if(!$send_notification && isset($pn_settings['on_publish']) && $pn_settings['on_publish']==1){
			if ( $new_status !== $old_status) {
			 	$this->send_notification($post);
			}
		}

		if(isset($pn_settings['on_category']) && $pn_settings['on_category']==1){
			if ( $new_status === $old_status) {
			 	$this->send_notification($post);
			 	$send_notification = true;
			}
		}	
	}

	protected function send_notification($post){
		$post_id = $post->ID;
		$post_content = $post->post_content;
		$post_title = $post->post_title;
		$auth_settings = push_notification_auth_settings();
		$push_notification_settings = push_notification_settings();
		//API Data
		$title = sanitize_text_field(wp_strip_all_tags($post_title, true) );
		$category_detail = get_the_category($post->ID);//$post->ID
		for($i=0; $i < count($category_detail); $i++) {
			$category_name[] = $category_detail[$i]->slug;
		}
		if(!empty($category_name)){
			$category = implode(',',$category_name);
		} else{
			$category = '';
		}
		$message = wp_trim_words(wp_strip_all_tags(sanitize_text_field($post_content), true), 20);
		$link_url = esc_url_raw(get_permalink( $post_id ));
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

		$image_url = '';
		if(has_post_thumbnail($post_id)){
			$image_url = esc_url_raw(get_the_post_thumbnail_url($post_id));
		}
		$icon_url = $push_notification_settings['notification_icon'];
		if( isset( $auth_settings['user_token'] ) && !empty($auth_settings['user_token']) ){
			$response = PN_Server_Request::sendPushNotificatioData( $auth_settings['user_token'], $title, $message, $link_url, $icon_url, $image_url, $category);
		}//auth token check 	

	}

	public function pn_send_notification_on_category($post){
		$pn_settings = push_notification_settings();
		$auth_settings = push_notification_auth_settings();

		if(!isset($pn_settings['posttypes'])){
			$pn_settings['posttypes'] = array("post");
		}
		if( !in_array( get_post_type($post), $pn_settings['posttypes']) ){
			return;
		}

		$send_notification = false;
		if(isset($pn_settings['on_category']) && $pn_settings['on_category']==1){
			 	$this->send_notification_on_cotegories($post);
			 	$send_notification = true;
		}
	}

	protected function send_notification_on_cotegories($post){
		$categories = get_categories();
		pushnotification_load_categories($categories);
	}

	/**
	 * Send the push notification when orders will change
	 * @method pn_order_send_notification
	 * @param  string 			Ref Order id 
	 * @param  string 			Ref status_from
	 * @param  string           $status_to  converted status.
	 * @param  string           $$obj  Order Object.
	 * @return Void                                            
	 */
	public function pn_order_send_notification($order_id, $status_from, $status_to, $obj){
		if(strtolower($status_to)==='pending'){ return; }
		$push_notification_settings = push_notification_settings();
		if(isset($push_notification_settings['notification_on_order_change_to_user']) && $push_notification_settings['notification_on_order_change_to_user']!=1){ return ; }
		$order = wc_get_order( $order_id );
		$user_id = $order->get_user_id();
		$token_ids = get_user_meta($user_id, 'pnwoo_notification_token',true);

		//Send notificarion to admin
		if(isset($push_notification_settings['notification_on_order_change_to_admin']) && $push_notification_settings['notification_on_order_change_to_admin']==1){ 
			$args = array(
			    'role'    => 'administrator',
			    'order'   => 'ASC'
			);
			$users = get_users( $args );
			if(count($users)>0){
				foreach ($users as $key => $user) {
					$tokens = get_user_meta($user->ID, 'pnwoo_notification_token',true);
					if(is_array($tokens)){
						$token_ids = array_merge($token_ids, $tokens);
					}
				}
			}
		}

		$token_ids = array_filter($token_ids);
		$token_ids = array_unique($token_ids);

		$post_title = esc_html__('Order status changed', 'push-notification');
		$post_content = esc_html__('Order id #'.$order_id.' changed from '.$status_from.' to '.$status_to, 'push-notification');
		$auth_settings = push_notification_auth_settings();

		//API Data
		$title = sanitize_text_field(wp_strip_all_tags($post_title, true) );
		$message = wp_trim_words(wp_strip_all_tags(sanitize_text_field($post_content), true), 20);
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

		if( isset( $auth_settings['user_token'] ) && !empty($auth_settings['user_token']) ){
			$userid = 1;
			if(function_exists('get_current_user_id')){
				$userid = get_current_user_id();
			}

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
				$remoteData = array('status'=>401, "response"=>esc_html__('could not connect to server', 'push-notification'));
			}else{
				$remoteData = wp_remote_retrieve_body($remoteResponse);
				$remoteData = json_decode($remoteData, true);
			}
		}//auth token check 
	}

	public function pn_get_layout_files($filePath){
	    $fileContentResponse = @wp_remote_get(esc_url_raw(PUSH_NOTIFICATION_PLUGIN_URL.'assets/'.$filePath));
	    if(wp_remote_retrieve_response_code($fileContentResponse)!=200){
	      if(!function_exists('get_filesystem_method')){
	        require_once( ABSPATH . 'wp-admin/includes/file.php' );
	      }
	      $access_type = get_filesystem_method();
	      if($access_type === 'direct')
	      {
	      	$file = PUSH_NOTIFICATION_PLUGIN_DIR.('assets/'.$filePath);
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
			echo json_encode(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));die;
		}else{
		    $api_url = 'http://magazine3.company/wp-json/api/central/email/subscribe';
		    $api_params = array(
		        'name' => sanitize_text_field($_POST['name']),
		        'email'=> sanitize_text_field($_POST['email']),
		        'website'=> sanitize_text_field($_POST['website']),
		        'type'=> 'notification'
		    );
		    $response = wp_remote_post( $api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		    $response = wp_remote_retrieve_body( $response );
		    echo $response;
		    die;
		}
	}
	
}

$push_Notification_Admin_Obj  = new Push_Notification_Admin(); 
if(is_admin() || wp_doing_ajax()){
	$push_Notification_Admin_Obj->init();
}
//Send push on publish and update 
// Put it here because in Gutenberg is_admin gives us false
add_action( 'transition_post_status', array( $push_Notification_Admin_Obj, 'send_notification_on_update' ), 10, 3 );

function push_notification_settings(){
	$push_notification_settings = get_option( 'push_notification_settings', array() ); 
	$icon = PUSH_NOTIFICATION_PLUGIN_URL.'assets/image/bell-icon.png';
	if(function_exists('pwaforwp_defaultSettings')){
		$pwaforwpSettings = pwaforwp_defaultSettings();
		$icon = $pwaforwpSettings['icon'];
	}
	$default = array(
		'notification_icon' => $icon,
		// 'on_edit'=> 0,
		'on_publish'=> 1,
		'posttypes'=> array("post","page"),
		'category'=> get_categories(),
		'notification_position'=> 'bottom-left',
		'popup_banner_message'=> esc_html__('Enable Notifications', 'push-notification'),
		'popup_banner_accept_btn'=> esc_html__('OK', 'push-notification'),
		'popup_banner_decline_btn'=> esc_html__('No thanks', 'push-notification'),
		'notification_popup_show_again'=>'30',
		'notification_popup_show_afternseconds'=>'3',
		'notification_popup_show_afternpageview'=>'1',
		'notification_utm_source'=> 'pn-ref',
		'notification_utm_medium'=> 'pn-ref',
		'notification_utm_campaign'=> 'pn-campaign',
		'notification_utm_term'=> 'pn-term',
		'notification_utm_content'=> 'pn-content',
	);
	$push_notification_settings = wp_parse_args($push_notification_settings, $default);
	$push_notification_settings = apply_filters("pn_settings_options_array", $push_notification_settings);
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
	public static function get_input_category($name, $id="", $class=""){
		$settings = push_notification_settings();
		?><input type="text" name="<?php echo esc_attr(self::$settingName); ?>[<?php echo esc_attr($name); ?>]" class="regular-text" id="<?php echo esc_attr($id); ?>" value="<?php if(isset($settings[$name]) && is_string($settings[$name])) echo esc_attr($settings[$name]); ?>" hidden/><?php
	}
	public static function get_input_checkbox($name, $value, $id="", $class="", $label=''){
		$settings = push_notification_settings();
		if(!isset($settings[$name])){$settings[$name] = 0; }
		?>
		<div class="checkbox_wrapper">
			<input type="checkbox" class="regular-text checkbox_operator" id="<?php echo esc_attr($id); ?>" <?php if ( isset( $settings[$name] ) && $settings[$name]==$value ) echo esc_attr("checked"); ?> value="<?php echo esc_attr($value); ?>"/>
			<input type="hidden" name="<?php echo esc_attr(self::$settingName); ?>[<?php echo esc_attr($name); ?>]" class="regular-text checkbox_target" id="<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($settings[$name]); ?>" data-truevalue="<?php echo esc_attr($value); ?>"/>
			<?php if(!empty($label)){
				echo '<label style="display:inline-block" for="'.esc_attr($id).'">'.esc_html__($label, 'push-notification').'</label>';
			} ?>
		</div>
		<?php
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
				echo '<option value="'.esc_attr($key).'" '.$sel.'>'.esc_html__($opt, 'push-notification').'</option>';
			} ?>
		</select><?php
	}
	public static function get_multi_input_checkbox($name, $value, $data, $id="", $class=""){
		$settings = push_notification_settings();
		$category = $settings['category'];
		$catArray = array();
		if(!empty($category) && is_string($category)){
			$catArray = explode(',', $category);
		}
		$i=0;
		foreach ($data as $value) { 
			$check = '';
			if(isset($value) && !empty($catArray)){
				if(in_array($value->name, $catArray)){
					$check = 'checked';
				}
			}
		?>
			<div class="checkbox_wrapper">
				<input type="checkbox" class="regular-text checkbox_operator pn_push_segment_category_checkbox" id="<?php echo esc_attr('pn_push_category_checkbox'.$i); ?>" <?php echo esc_attr($check); ?> value="<?php echo isset($value->name)? esc_attr($value->name):''; ?>"/>
				<input type="hidden" name="<?php echo esc_attr(self::$settingName); ?>[<?php echo esc_attr($name); ?>][]" class="regular-text checkbox_target" id="<?php echo esc_attr('pn_push_category_checkbox'.$i); ?>" value="<?php echo isset($value->name)?esc_attr($value->name):''; ?>" data-truevalue="<?php echo isset($value->name)?esc_attr($value->name):''; ?>"/>
				<?php 
				$label_text = '';
				if(isset($value) && !empty($value)){
					$label_text = esc_html__($value->name, 'push-notification');
				}
				echo '<label style="display:inline-block" for="pn_push_category_checkbox'.esc_attr($i).'">'.$label_text.'</label>';
				?>
			</div>
		<?php
		$i++;
		}
	}

	public static function get_input_select($name, $value, $options, $id="", $class=""){
		$settings = push_notification_settings();
		if( isset($settings[$name]) ){
			$value = $settings[$name];
		}
		?><select name="<?php echo esc_attr(self::$settingName); ?>[<?php echo esc_attr($name); ?>]" class="regular-text" id="<?php echo esc_attr($id); ?>" >
			<?php foreach ($options as $key => $opt) {
				$sel = '';
				if(isset($value) && $key==$value){
					$sel = 'selected';
				}
				echo '<option value="'.esc_attr($key).'" '.$sel.'>'.esc_html__($opt, 'push-notification').'</option>';
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
function pn_send_query_message(){   
		$nonce = sanitize_text_field($_POST['nonce']);
        if ( !wp_verify_nonce($nonce, 'pn_notification') ){
            return; 
        }
        $authData = push_notification_auth_settings();
        if ($authData['token_details']['validated']!=1 ){
           return;  
        }
        $message    = sanitize_textarea_field($_POST['message']);        
        $customer_type    = sanitize_text_field($_POST['customer_type']);        
        $customer_type = empty($customer_type)? $customer_type : 'No';
        $message .= "<table>
        				<tr><td>".esc_html__('Are you existing Premium Customer?','push-notification')."</td><td>".esc_html__($customer_type, 'push-notification')."</td></tr>
        				<tr><td>Plugin</td><td> ". esc_html__('Push Notification', 'push-notification') ."</td></tr>
        				<tr><td>Version</td><td>".esc_html__(PUSH_NOTIFICATION_PLUGIN_VERSION, 'push-notification')."</td></tr>
        			</table>";
        $user       = wp_get_current_user();
        if($user){
            $user_data  = $user->data;        
            $user_email = $user_data->user_email;       
            //php mailer variables
            $to = esc_html__('team@magazine3.in', 'push-notification');
            $subject = esc_html__("Push Notification Customer Query", 'push-notification');
            $headers = 'From: '. esc_attr($user_email) . "\r\n" .
            'Reply-To: ' . esc_attr($user_email) . "\r\n";
            // Load WP components, no themes.                      
            $sent = wp_mail($to, $subject, strip_tags($message), $headers);                    
            if($sent){
            echo json_encode(array('status'=>'t'));            
            }else{
            echo json_encode(array('status'=>'f'));            
            }            
        }                        
           wp_die();           
}


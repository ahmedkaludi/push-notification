<?php
/**
Plugin Name: Push Notification
Plugin URI: https://wordpress.org/plugins/push-notification/
Description: Push Notification allow admin to automatically notify your audience when you have published new content on your site or custom notices
Author: Magazine3
Version: 1.12.1
Author URI: http://pushnotifications.io/
Text Domain: push-notification
Domain Path: /languages
License: GPL2+
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

define('PUSH_NOTIFICATION_PLUGIN_FILE',  __FILE__ );
define('PUSH_NOTIFICATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('PUSH_NOTIFICATION_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('PUSH_NOTIFICATION_PLUGIN_VERSION', '1.12.1');
define('PUSH_NOTIFICATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Initialize pwa functions
 */
add_action('plugins_loaded', 'push_notification_initialize');
function push_notification_initialize(){
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/admin.php";
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/newsletter.php"; 
	if(is_admin()){
		add_filter( 'plugin_action_links_' . PUSH_NOTIFICATION_PLUGIN_BASENAME,'push_notification_add_action_links', 10, 4);
	}
	if( !is_admin() || wp_doing_ajax() ){
		require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/frontend/pn-frontend.php";
	}
}


function push_notification_add_action_links($actions, $plugin_file, $plugin_data, $context){
    $mylinks = array('<a href="' . esc_url_raw(admin_url( 'admin.php?page=push-notification' )) . '">'.esc_html__( 'Settings', 'push-notification' ).'</a>',
    	'<a href="https://pushnotifications.io/documentation/" target="_blank">'.esc_html__( 'Documentation', 'push-notification' ).'</a>',
					);
    return array_merge( $actions, $mylinks );
}

register_activation_hook( PUSH_NOTIFICATION_PLUGIN_FILE, 'push_notification_on_activate' );
function push_notification_on_activate(){
	/** Setup notification feature in PWA-for-wp*/
	$pwaforwp_settings = get_option( 'pwaforwp_settings'); 
	if(isset($pwaforwp_settings['notification_feature']) && $pwaforwp_settings['notification_feature']==0){
		$pwaforwp_settings['notification_feature'] = 1;
		update_option( 'pwaforwp_settings', $pwaforwp_settings); 
	}

	/**
	 * on activation of plugin compatibile with PWA for wp
	 * if PWA activated and now activating PushNotification, Need to regenerate service worker files
	 */
	$auth_settings = get_option( 'push_notification_auth_settings', array() );
	//Push notification Check
    if(isset($auth_settings['user_token']) && isset($auth_settings['token_details']['validated']) && $auth_settings['token_details']['validated'] == 1){
    	//pwaforamp check
    	if( function_exists('pwaforwp_required_file_creation') ){
			$pwaSettings = pwaforwp_defaultSettings();
			if( $pwaSettings['notification_feature']==1 && isset($pwaSettings['notification_options']) && $pwaSettings['notification_options']=='pushnotifications_io'){
				pwaforwp_required_file_creation();
			}
		}

    }
}
/**
 * After activate plugin path to redirect
 * @plugin name of current activation plugin 
 */
function push_notification_after_activation_redirect( $plugin ) {
    if( $plugin == plugin_basename( PUSH_NOTIFICATION_PLUGIN_FILE ) ) {
        exit( wp_redirect( admin_url( 'admin.php?page=push-notification' ) ) );
    }
}
add_action( 'activated_plugin', 'push_notification_after_activation_redirect' );


/**
 * TO compatible with older < 1.3 of pushnotification
 */
add_action("plugins_loaded", 'push_notification_older_version_compatibility');
function push_notification_older_version_compatibility(){
	$configCompatible = get_transient( 'push_notification_older_version' );

	if(!$configCompatible){
		$auth_settings = push_notification_auth_settings();
		if( isset($auth_settings['user_token']) && isset($auth_settings['token_details']['validated']) && $auth_settings['token_details']['validated'] == 1 && !isset($auth_settings['messageManager']) ){
			$response = PN_Server_Request::varifyUser($auth_settings['user_token']);
			set_transient( 'push_notification_older_version', 1 );
		}
	}
}
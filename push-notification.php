<?php
/**
Plugin Name: Push Notification
Plugin URI: https://wordpress.org/plugins/push-notification/
Description: Push Notification allow admin to automatically notify your audience when you have published new content on your site or custom notices
Author: Magazine3
Version: 1.3
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
define('PUSH_NOTIFICATION_PLUGIN_VERSION', '1.3');
define('PUSH_NOTIFICATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

add_action('plugins_loaded', 'push_notification_initialize');
function push_notification_initialize(){
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/admin.php";
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/newsletter.php"; 
	if(is_admin()){
		add_filter( 'plugin_action_links_' . PUSH_NOTIFICATION_PLUGIN_BASENAME,'push_notification_add_action_links', 10, 4);
	}
}
require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/frontend/pn-frontend.php";


function push_notification_add_action_links($actions, $plugin_file, $plugin_data, $context){
    $mylinks = array('<a href="' . esc_url_raw(admin_url( 'admin.php?page=push-notification' )) . '">'.esc_html__( 'Settings', 'push-notification' ).'</a>');
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
}

function push_notification_after_activation_redirect( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect( admin_url( 'admin.php?page=push-notification' ) ) );
    }
}
add_action( 'activated_plugin', 'push_notification_after_activation_redirect' );
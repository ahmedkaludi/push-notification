<?php
/*
Plugin Name: Push Notifications for WP - Self Hosted Web Push Notifications
Plugin URI: https://wordpress.org/plugins/push-notification/
Description: Push Notification allow admin to automatically notify your audience when you have published new content on your site or custom notices
Author: Magazine3
Version: 1.41
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
define('PUSH_NOTIFICATION_PLUGIN_VERSION', '1.41');
define('PUSH_NOTIFICATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Initialize pwa functions
 */
add_action('plugins_loaded', 'push_notification_initialize');
function push_notification_initialize(){
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/admin.php";	
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/PnMetaBox.php";
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/newsletter.php"; 
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/compatibility/ultimate-member.php"; 
	require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/feedback-helper-functions.php";
	if(is_admin()){
		if ( is_multisite()) {
			require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/pn_multisite.php";
		}
		add_filter( 'plugin_action_links_' . PUSH_NOTIFICATION_PLUGIN_BASENAME,'push_notification_add_action_links', 10, 4);
	}
	if( !is_admin() || wp_doing_ajax() ){
		require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/frontend/pn-frontend.php";
	}
	if ( class_exists( 'OneSignal' ) ) {
		add_action('admin_notices', 'push_notification_feature_notice');
	}
}

function push_notification_feature_notice(){
	$class = 'notice notice-warning';
    $message = esc_html__( 'There is may some conflict issue with the other push notification plugin. To take benefit all features of the Push Notification plugin, Please deactivate the OneSignal plugin.', 'push-notification' );
 
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}


function push_notification_add_action_links($actions, $plugin_file, $plugin_data, $context){
    $mylinks = array('<a href="' . esc_url_raw(admin_url( 'admin.php?page=push-notification' )) . '">'.esc_html__( 'Settings', 'push-notification' ).'</a>',
    	'<a href="https://pushnotifications.io/documentation/" target="_blank">'.esc_html__( 'Documentation', 'push-notification' ).'</a>',
					);
    return array_merge( $actions, $mylinks ); // no validation check since $mylinks will always be non-empty array
}

register_activation_hook( PUSH_NOTIFICATION_PLUGIN_FILE, 'push_notification_on_activate' );

function push_notification_on_activate( $network_wide ) {
	/** Setup notification feature in PWA-for-wp*/
	$pwaforwp_settings = get_option( 'pwaforwp_settings');

	if ( isset( $pwaforwp_settings['notification_feature'] ) && $pwaforwp_settings['notification_feature'] == 0 ) {

		$pwaforwp_settings['notification_feature'] = 1;
		update_option( 'pwaforwp_settings', $pwaforwp_settings, false ); 
		
	}
	
    if ( is_multisite() && $network_wide ) {

        $sites = get_sites();

		if ( $sites ) {

			foreach ( $sites as $site ) {

				switch_to_blog( $site->blog_id );
				push_notification_on_install();
				restore_current_blog();
	
			}

		}
        
    } else {

        push_notification_on_install();

    }

	/**
	 * on activation of plugin compatibile with PWA for wp
	 * if PWA activated and now activating PushNotification, Need to regenerate service worker files
	 */
	$auth_settings = get_option( 'push_notification_auth_settings', array() );
	//Push notification Check
    if ( isset( $auth_settings['user_token'] ) && isset( $auth_settings['token_details']['validated']) && $auth_settings['token_details']['validated'] == 1 ) {
    	//pwaforamp check
    	if ( function_exists('pwaforwp_required_file_creation') ) {

			$pwaSettings = pwaforwp_defaultSettings();

			if ( $pwaSettings['notification_feature']==1 && isset($pwaSettings['notification_options']) && $pwaSettings['notification_options']=='pushnotifications_io' ) {

				pwaforwp_required_file_creation();

			}
		}

    }
}

/**
 * TO compatible with older < 1.3 of pushnotification
 */

add_action( 'plugins_loaded', 'push_notification_older_version_compatibility' );

function push_notification_older_version_compatibility() {

	$configCompatible = get_transient( 'push_notification_older_version' );

	if ( ! $configCompatible ) {

		$auth_settings = push_notification_auth_settings();

		if ( isset( $auth_settings['user_token'] ) && isset( $auth_settings['token_details']['validated']) && $auth_settings['token_details']['validated'] == 1 && ! isset($auth_settings['messageManager'] ) ) {

			$response = PN_Server_Request::varifyUser($auth_settings['user_token']);
			set_transient( 'push_notification_older_version', 1 );

		}

	}
}


function push_notification_pro_checker() {
   
	$_pro_checker      = get_transient( 'push_notification_pro_checker' );

	if ( ! $_pro_checker ) {
	
		$auth_settings = push_notification_auth_settings();

		if ( ! empty( $auth_settings['user_token'] ) ) {

			PN_Server_Request::getProStatus(true);
			set_transient( 'push_notification_pro_checker', true, 86400 );

		}
	}      
}

add_action( 'admin_init', 'push_notification_pro_checker', 0);


/* 
Globlal function to send push notification from anywhere pn_send_push_notificatioin_filter
 
$user_id => your meta_user_id required
$title => Message title required
$link_url => your website url required
$message => text message required
$image_url => png image link url optional
$icon_url => icon link url optional

*/

function pn_send_push_notificatioin_filter( $user_id = null, $title = "", $message = "", $link_url = "", $icon_url = "", $image_url = "" ) {

	if ( ! empty( $user_id) && !empty($title) && !empty($message) && !empty($link_url)) {

		$verifyUrl = 'campaign/pn_send_push_filter';
		$audience_token_id = get_user_meta($user_id, 'pnwoo_notification_token',true);
		if ( is_multisite() ) {
			$weblink = get_site_url();
		}else{
			$weblink = home_url();
		}
		$auth_settings = push_notification_auth_settings();
		foreach($audience_token_id as $key=>$value) {
            if( isset($auth_settings['user_token']) && !empty( $value ) ){
                $data = array(
                            "user_token"        =>$auth_settings['user_token'],
                            "audience_token_id" =>$value,
                            "website"   =>$weblink,
                            'title'     =>$title,
                            'message'   =>$message,
                            'link_url'  =>$link_url,
                            'icon_url'  =>$icon_url,
                            'image_url' =>$image_url
                        );
                $response = PN_Server_Request::pnSendPushNotificatioinFilter($data);
            }
        }
	}else{
		$response['status'] = false;
		$response['message'] = esc_html__('User id, title, link_url and message field are required','push-notification');
	}
	return $response;
}

function push_notification_on_install(){

	global $wpdb;

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$charset_collate = $engine = '';	

	if(!empty($wpdb->charset)) {
		$charset_collate .= " DEFAULT CHARACTER SET {$wpdb->charset}";
	} 
	if($wpdb->has_cap('collation') AND !empty($wpdb->collate)) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared	
	$found_engine = $wpdb->get_var("SELECT ENGINE FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '".DB_NAME."' AND `TABLE_NAME` = '{$wpdb->prefix}posts';");

	if(strtolower($found_engine) == 'innodb') {
		$engine = ' ENGINE=InnoDB';
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$found_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}pn_token_urls%';");	

    if(!in_array("{$wpdb->prefix}pn_token_urls", $found_tables)) {

		dbDelta("CREATE TABLE `{$wpdb->prefix}pn_token_urls` (
			`id` bigint( 20 ) unsigned NOT NULL AUTO_INCREMENT,
			`url` varchar(250) NOT NULL,
			`status` varchar(20) NOT NULL DEFAULT 'active',	
			`token` varchar(250)  NOT NULL,			
			`created_at` datetime NOT NULL,
			`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
			 KEY `url` ( `url` ),
			 PRIMARY KEY (`id`)
		) ".$charset_collate.$engine.";");                
    }	

}

add_action('upgrader_process_complete', 'pn_plugin_upgrade_function', 10, 2);

function pn_plugin_upgrade_function($upgrader_object, $options) {
    // Check if it's a plugin update
    if ( $options['type'] === 'plugin' && isset( $options['action'] ) && $options['action'] === 'update' ) {
        // Check if the updated plugin is your plugin
        $plugin_slug = 'push-notification/push-notification.php';
        if ( is_array( $options['plugins'] ) && in_array( $plugin_slug, $options['plugins'] ) ) {
			push_notification_on_install();
        }
    }
}

add_action('admin_footer', 'pn_add_footer_text');

function pn_add_footer_text()
{
	$screen = get_current_screen();
	if ($screen->id !== 'toplevel_page_push-notification') {
		return;
	}

	echo '<div style="padding: 0 0 20px 15%;">
        <p>' . esc_html__( 'If you like Push Notification, please', 'push-notification' ) . '&nbsp;' . esc_html__( "leave a" , 'push-notification' ) . '&nbsp;<a href="' . esc_url( 'https://wordpress.org/support/plugin/push-notification/reviews/?rate=5#new-post' ) . '" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>&nbsp;' . esc_html__( "rating to support continued development. Thanks a bunch!" , 'push-notification' ) . '</p>
    </div>';
}
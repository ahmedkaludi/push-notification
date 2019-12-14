<?php
/**
Plugin Name: Push Notification for WP
Plugin URI: https://wordpress.org/plugins/pwa-for-wp/
Description: Push Notification for WP allow admin to automatically notify your audience when you have published new content on your site or custom notices
Author: Magazine3
Version: 1.0
Author URI: http://pwa-for-wp.com
Text Domain: push-notifications-for-wp
Domain Path: /languages
License: GPL2+
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

define('PUSH_NOTIFICATION_PLUGIN_FILE',  __FILE__ );
define('PUSH_NOTIFICATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('PUSH_NOTIFICATION_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('PUSH_NOTIFICATION_PLUGIN_VERSION', '1.0');
define('PUSH_NOTIFICATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin.php";
require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/pn-frontend.php";
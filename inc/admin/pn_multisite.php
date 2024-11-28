<?php

// Ensure it's executed in WordPress environment
defined('ABSPATH') || exit;

class pn_multisite extends Push_Notification_Admin {

    public function __construct() {
		parent::__construct(); // Call parent constructor to inherit any initialization logic
	}

    public function init() {
		// Network admin-specific hooks
		if (is_network_admin()) {
			add_action('network_admin_menu', array($this, 'pn_network_settings_menu'));
            parent::init();
		}
	}

    /**
     * Initialize network admin menu for multisite.
     */
    public function pn_network_settings_menu() {
        // Add a menu page in the network admin
        add_menu_page(
            esc_html__('Push Notification', 'push-notification'),
            esc_html__('Push Notification', 'push-notification'),
            'manage_network_options', // Capability required to view
            'push-notification-global-setting', // Menu slug
            [$this, 'admin_interface_render'], // Callback to render the page
            'dashicons-admin-generic', // Menu icon
            26 // Position
        );
    }
}

if (is_multisite()) {
	$push_notification = new pn_multisite();
    $push_notification->init();
}


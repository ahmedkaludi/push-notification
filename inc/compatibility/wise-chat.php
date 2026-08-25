<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Compatibility with Wise Chat and Wise Chat Pro
 */
class push_notification_wise_chat {

	public function __construct(){}

	public function init(){
		add_action( 'wise_chat_message_created', array( $this, 'pn_wise_chat_message_created' ), 10, 1 );
		add_action( 'wise_chat_post_message_action', array( $this, 'pn_wise_chat_message_created' ), 10, 1 );
	}

	/**
	 * Send push notification when a message is posted in Wise Chat
	 * 
	 * @param object|array $message Wise Chat message object/array
	 * @return void
	 */
	public function pn_wise_chat_message_created( $message ) {
		$push_notification_settings = push_notification_settings();
		if ( ! isset( $push_notification_settings['pn_wise_chat_compatibale'] ) || ! $push_notification_settings['pn_wise_chat_compatibale'] ) {
			return;
		}

		$auth_settings = push_notification_auth_settings();
		if ( ! isset( $auth_settings['user_token'] ) || empty( $auth_settings['user_token'] ) ) {
			return;
		}

		$sender_name = '';
		$text        = '';
		$channel     = '';
		$user_id     = 0;

		if ( is_object( $message ) ) {
			$sender_name = isset( $message->name ) ? $message->name : ( isset( $message->userName ) ? $message->userName : '' );
			$text        = isset( $message->text ) ? $message->text : ( isset( $message->message ) ? $message->message : '' );
			$channel     = isset( $message->channel ) ? $message->channel : ( isset( $message->channelName ) ? $message->channelName : 'Chat' );
			$user_id     = isset( $message->user_id ) ? intval( $message->user_id ) : ( isset( $message->userId ) ? intval( $message->userId ) : 0 );
		} elseif ( is_array( $message ) ) {
			$sender_name = isset( $message['name'] ) ? $message['name'] : ( isset( $message['userName'] ) ? $message['userName'] : '' );
			$text        = isset( $message['text'] ) ? $message['text'] : ( isset( $message['message'] ) ? $message['message'] : '' );
			$channel     = isset( $message['channel'] ) ? $message['channel'] : ( isset( $message['channelName'] ) ? $message['channelName'] : 'Chat' );
			$user_id     = isset( $message['user_id'] ) ? intval( $message['user_id'] ) : ( isset( $message['userId'] ) ? intval( $message['userId'] ) : 0 );
		}

		if ( empty( $sender_name ) && $user_id > 0 ) {
			$user_data = get_userdata( $user_id );
			if ( $user_data ) {
				$sender_name = $user_data->display_name;
			}
		}

		if ( empty( $sender_name ) ) {
			$sender_name = __( 'Someone', 'push-notification' );
		}

		if ( empty( $text ) ) {
			return;
		}

		/* translators: 1: Sender name, 2: Channel name */
		$title = sprintf( __( 'New message from %1$s in %2$s', 'push-notification' ), $sender_name, $channel );
		$body  = wp_strip_all_tags( $text );
		$body  = wp_trim_words( $body, 20, '...' );

		$icon_url = isset( $push_notification_settings['notification_icon'] ) ? $push_notification_settings['notification_icon'] : '';
		if ( empty( $icon_url ) && $user_id > 0 ) {
			$icon_url = get_avatar_url( $user_id, array( 'size' => 96 ) );
		}

		$link_url = is_multisite() ? get_site_url() : home_url();

		PN_Server_Request::sendPushNotificatioData(
			$auth_settings['user_token'],
			$title,
			$body,
			$link_url,
			$icon_url,
			'',
			'',
			''
		);
	}
}

if ( ! is_admin() || wp_doing_ajax() ) {
	$push_notification_wise_chat = new push_notification_wise_chat();
	$push_notification_wise_chat->init();
}

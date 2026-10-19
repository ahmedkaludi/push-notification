<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Compatibility with Classified Listing Pro (RTCL Chat) by RadiusTheme
 */
class push_notification_classified_listing {

	public function __construct() {}

	public function init() {
		add_action( 'rtcl_chat_message_sent', array( $this, 'pn_rtcl_chat_message_sent' ), 10, 4 );
	}

	/**
	 * Send push notification when a chat message is sent in RTCL Chat
	 *
	 * @param mixed $param1 Message ID, Message object/array, or Conversation ID
	 * @param mixed $param2 Conversation ID, Message object/array, or Recipient ID
	 * @param mixed $param3 Sender/Source ID or Recipient ID
	 * @param mixed $param4 Recipient ID
	 * @return void
	 */
	public function pn_rtcl_chat_message_sent( $param1 = null, $param2 = null, $param3 = null, $param4 = null ) {
		$push_notification_settings = push_notification_settings();
		if ( ! isset( $push_notification_settings['pn_classified_listing_compatibale'] ) || ! $push_notification_settings['pn_classified_listing_compatibale'] ) {
			return;
		}

		$auth_settings = push_notification_auth_settings();
		if ( ! isset( $auth_settings['user_token'] ) || empty( $auth_settings['user_token'] ) ) {
			return;
		}

		global $wpdb;

		$message_id   = 0;
		$con_id       = 0;
		$source_id    = 0;
		$recipient_id = 0;
		$listing_id   = 0;
		$message_text = '';

		// 1. Inspect param1 (Message object, array, or ID)
		if ( is_object( $param1 ) ) {
			$message_id   = isset( $param1->message_id ) ? (int) $param1->message_id : ( isset( $param1->id ) ? (int) $param1->id : 0 );
			$con_id       = isset( $param1->con_id ) ? (int) $param1->con_id : ( isset( $param1->conversation_id ) ? (int) $param1->conversation_id : 0 );
			$source_id    = isset( $param1->source_id ) ? (int) $param1->source_id : ( isset( $param1->sender_id ) ? (int) $param1->sender_id : ( isset( $param1->user_id ) ? (int) $param1->user_id : 0 ) );
			$recipient_id = isset( $param1->recipient_id ) ? (int) $param1->recipient_id : 0;
			$listing_id   = isset( $param1->listing_id ) ? (int) $param1->listing_id : 0;
			$message_text = isset( $param1->message ) ? $param1->message : ( isset( $param1->text ) ? $param1->text : ( isset( $param1->content ) ? $param1->content : '' ) );
		} elseif ( is_array( $param1 ) ) {
			$message_id   = isset( $param1['message_id'] ) ? (int) $param1['message_id'] : ( isset( $param1['id'] ) ? (int) $param1['id'] : 0 );
			$con_id       = isset( $param1['con_id'] ) ? (int) $param1['con_id'] : ( isset( $param1['conversation_id'] ) ? (int) $param1['conversation_id'] : 0 );
			$source_id    = isset( $param1['source_id'] ) ? (int) $param1['source_id'] : ( isset( $param1['sender_id'] ) ? (int) $param1['sender_id'] : ( isset( $param1['user_id'] ) ? (int) $param1['user_id'] : 0 ) );
			$recipient_id = isset( $param1['recipient_id'] ) ? (int) $param1['recipient_id'] : 0;
			$listing_id   = isset( $param1['listing_id'] ) ? (int) $param1['listing_id'] : 0;
			$message_text = isset( $param1['message'] ) ? $param1['message'] : ( isset( $param1['text'] ) ? $param1['text'] : ( isset( $param1['content'] ) ? $param1['content'] : '' ) );
		} elseif ( is_numeric( $param1 ) ) {
			$message_id = (int) $param1;
		}

		// 2. Inspect param2 (Conversation object, array, or ID)
		if ( is_object( $param2 ) ) {
			if ( ! $con_id ) {
				$con_id = isset( $param2->con_id ) ? (int) $param2->con_id : ( isset( $param2->conversation_id ) ? (int) $param2->conversation_id : ( isset( $param2->id ) ? (int) $param2->id : 0 ) );
			}
			if ( ! $listing_id ) {
				$listing_id = isset( $param2->listing_id ) ? (int) $param2->listing_id : 0;
			}
			if ( ! $recipient_id && isset( $param2->recipient_id ) ) {
				$recipient_id = (int) $param2->recipient_id;
			}
			if ( ! $source_id && isset( $param2->sender_id ) ) {
				$source_id = (int) $param2->sender_id;
			}
		} elseif ( is_array( $param2 ) ) {
			if ( ! $con_id ) {
				$con_id = isset( $param2['con_id'] ) ? (int) $param2['con_id'] : ( isset( $param2['conversation_id'] ) ? (int) $param2['conversation_id'] : ( isset( $param2['id'] ) ? (int) $param2['id'] : 0 ) );
			}
			if ( ! $listing_id ) {
				$listing_id = isset( $param2['listing_id'] ) ? (int) $param2['listing_id'] : 0;
			}
			if ( ! $recipient_id && isset( $param2['recipient_id'] ) ) {
				$recipient_id = (int) $param2['recipient_id'];
			}
			if ( ! $source_id && isset( $param2['sender_id'] ) ) {
				$source_id = (int) $param2['sender_id'];
			}
		} elseif ( is_numeric( $param2 ) ) {
			if ( ! $con_id ) {
				$con_id = (int) $param2;
			}
		}

		// 3. Inspect param3 and param4
		if ( is_numeric( $param3 ) ) {
			if ( ! $source_id ) {
				$source_id = (int) $param3;
			} elseif ( ! $recipient_id ) {
				$recipient_id = (int) $param3;
			}
		}
		if ( is_numeric( $param4 ) && ! $recipient_id ) {
			$recipient_id = (int) $param4;
		}

		// Fallback for sender: current user if available
		if ( ! $source_id && function_exists( 'get_current_user_id' ) ) {
			$source_id = get_current_user_id();
		}

		// 4. Query message database table if details missing
		if ( $message_id && ( empty( $message_text ) || ! $con_id || ! $source_id ) ) {
			$msg_cache_key = 'pn_rtcl_msg_' . $message_id;
			$msg_row       = wp_cache_get( $msg_cache_key, 'push_notification' );

			if ( false === $msg_row ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Classified Listing Pro custom table.
				$msg_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rtcl_chat_messages WHERE message_id = %d LIMIT 1", $message_id ) );
				if ( ! $msg_row ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Classified Listing Pro custom table.
					$msg_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rtcl_chat_messages WHERE id = %d LIMIT 1", $message_id ) );
				}
				if ( $msg_row ) {
					wp_cache_set( $msg_cache_key, $msg_row, 'push_notification', 300 );
				}
			}

			if ( $msg_row ) {
				if ( ! $con_id && ! empty( $msg_row->con_id ) ) {
					$con_id = (int) $msg_row->con_id;
				}
				if ( ! $source_id && ! empty( $msg_row->source_id ) ) {
					$source_id = (int) $msg_row->source_id;
				}
				if ( empty( $message_text ) && ! empty( $msg_row->message ) ) {
					$message_text = $msg_row->message;
				}
			}
		}

		// 5. Query conversation table to determine recipient
		if ( ! $recipient_id && $con_id ) {
			$con_cache_key = 'pn_rtcl_con_' . $con_id;
			$con_row       = wp_cache_get( $con_cache_key, 'push_notification' );

			if ( false === $con_row ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Classified Listing Pro custom table.
				$con_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rtcl_conversations WHERE con_id = %d LIMIT 1", $con_id ) );
				if ( ! $con_row ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Classified Listing Pro custom table.
					$con_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rtcl_conversations WHERE id = %d LIMIT 1", $con_id ) );
				}
				if ( $con_row ) {
					wp_cache_set( $con_cache_key, $con_row, 'push_notification', 300 );
				}
			}

			if ( $con_row ) {
				if ( ! $listing_id && ! empty( $con_row->listing_id ) ) {
					$listing_id = (int) $con_row->listing_id;
				}

				$sender_id_con    = ! empty( $con_row->sender_id ) ? (int) $con_row->sender_id : 0;
				$recipient_id_con = ! empty( $con_row->recipient_id ) ? (int) $con_row->recipient_id : 0;

				if ( $source_id && $source_id === $sender_id_con ) {
					$recipient_id = $recipient_id_con;
				} elseif ( $source_id && $source_id === $recipient_id_con ) {
					$recipient_id = $sender_id_con;
				} else {
					$current_user = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
					if ( $current_user === $sender_id_con ) {
						$recipient_id = $recipient_id_con;
					} elseif ( $current_user === $recipient_id_con ) {
						$recipient_id = $sender_id_con;
					} else {
						$recipient_id = ( $source_id === $sender_id_con ) ? $recipient_id_con : $sender_id_con;
					}
				}
			}
		}

		// 6. Fallback: if recipient still unknown, check listing post author
		if ( ! $recipient_id && $listing_id ) {
			$post_author = (int) get_post_field( 'post_author', $listing_id );
			if ( $post_author && $post_author !== $source_id ) {
				$recipient_id = $post_author;
			}
		}

		// Recipient validation (must exist and not be sender)
		if ( ! $recipient_id || ( $source_id && $recipient_id === $source_id ) ) {
			return;
		}

		// 7. Get recipient push notification tokens
		$token_ids = get_user_meta( $recipient_id, 'pnwoo_notification_token', true );
		if ( empty( $token_ids ) ) {
			return;
		}
		$token_ids = array_filter( (array) $token_ids );
		$token_ids = array_unique( $token_ids );
		if ( empty( $token_ids ) ) {
			return;
		}

		// 8. Prepare notification content
		$sender_name = '';
		if ( $source_id ) {
			$sender_user = get_userdata( $source_id );
			if ( $sender_user ) {
				$sender_name = $sender_user->display_name;
			}
		}
		if ( empty( $sender_name ) ) {
			$sender_name = __( 'Someone', 'push-notification' );
		}

		/* translators: %s: Sender name */
		$title = sprintf( __( 'New message from %s', 'push-notification' ), $sender_name );
		$body  = ! empty( $message_text ) ? wp_strip_all_tags( $message_text ) : __( 'Sent you a message', 'push-notification' );
		$body  = wp_trim_words( $body, 20, '...' );

		// Sender avatar / notification icon
		$icon_url = '';
		if ( $source_id ) {
			$icon_url = get_avatar_url( $source_id, array( 'size' => 96 ) );
		}
		if ( empty( $icon_url ) && ! empty( $push_notification_settings['notification_icon'] ) ) {
			$icon_url = $push_notification_settings['notification_icon'];
		}

		// Listing image if available
		$image_url = '';
		if ( $listing_id ) {
			$thumb = get_the_post_thumbnail_url( $listing_id, 'medium' );
			if ( $thumb ) {
				$image_url = $thumb;
			}
		}

		// Link URL
		$link_url = '';
		if ( function_exists( 'rtcl_get_account_endpoint_url' ) ) {
			$link_url = rtcl_get_account_endpoint_url( 'chat' );
		}
		if ( empty( $link_url ) && $listing_id ) {
			$link_url = get_permalink( $listing_id );
		}
		if ( empty( $link_url ) ) {
			$link_url = is_multisite() ? get_site_url() : home_url();
		}
		if ( $con_id && ! empty( $link_url ) ) {
			$link_url = add_query_arg( 'con_id', $con_id, $link_url );
		}

		// UTM tracking
		if ( isset( $push_notification_settings['utm_tracking_checkbox'] ) && $push_notification_settings['utm_tracking_checkbox'] ) {
			$utm_details = array(
				'utm_source'   => isset( $push_notification_settings['notification_utm_source'] ) ? $push_notification_settings['notification_utm_source'] : '',
				'utm_medium'   => isset( $push_notification_settings['notification_utm_medium'] ) ? $push_notification_settings['notification_utm_medium'] : '',
				'utm_campaign' => isset( $push_notification_settings['notification_utm_campaign'] ) ? $push_notification_settings['notification_utm_campaign'] : '',
				'utm_term'     => isset( $push_notification_settings['notification_utm_term'] ) ? $push_notification_settings['notification_utm_term'] : '',
				'utm_content'  => isset( $push_notification_settings['notification_utm_content'] ) ? $push_notification_settings['notification_utm_content'] : '',
			);
			$link_url = add_query_arg( array_filter( $utm_details ), $link_url );
		}

		// 9. Send push notification to recipient's devices
		$verifyUrl = PN_Server_Request::$notificationServerUrl . 'campaign/single';
		$weblink   = is_multisite() ? get_site_url() : home_url();

		$data = array(
			'user_token'        => $auth_settings['user_token'],
			'audience_token_id' => $token_ids,
			'title'             => wp_strip_all_tags( $title ),
			'message'           => $body,
			'link_url'          => esc_url_raw( $link_url ),
			'icon_url'          => esc_url_raw( $icon_url ),
			'image_url'         => ! empty( $image_url ) ? esc_url_raw( $image_url ) : null,
			'website'           => $weblink,
		);

		$postdata = array( 'body' => $data );
		wp_remote_post( $verifyUrl, $postdata );
	}
}

$push_notification_classified_listing = new push_notification_classified_listing();
$push_notification_classified_listing->init();

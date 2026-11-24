<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * BFCM 2026 admin banner.
 *
 * Shown on the plugin settings screen through 31 December 2026.
 * Dismissing it hides the banner for 7 days, then it returns until the campaign ends.
 */

/**
 * User meta key that stores the Unix time the snooze lasts until.
 *
 * @return string
 */
function pn_bfcm_dismiss_meta_key() {
	return 'pn_bfcm_2026_dismissed_until';
}

/**
 * Site timezone, including WordPress versions before wp_timezone() existed.
 *
 * @return DateTimeZone
 */
function pn_bfcm_timezone() {
	if ( function_exists( 'wp_timezone' ) ) {
		return wp_timezone();
	}

	$timezone_string = get_option( 'timezone_string' );

	if ( is_string( $timezone_string ) && '' !== $timezone_string ) {
		try {
			return new DateTimeZone( $timezone_string );
		} catch ( Exception $e ) {
			return new DateTimeZone( 'UTC' );
		}
	}

	$offset  = (float) get_option( 'gmt_offset', 0 );
	$hours   = (int) $offset;
	$minutes = (int) abs( ( $offset - $hours ) * 60 );
	$sign    = ( $offset < 0 ) ? '-' : '+';

	return new DateTimeZone( sprintf( '%s%02d:%02d', $sign, abs( $hours ), $minutes ) );
}

/**
 * Campaign end: 31 December 2026, 23:59:59 in the site timezone.
 *
 * @return DateTimeImmutable
 */
function pn_bfcm_campaign_end() {
	return new DateTimeImmutable( '2026-12-31 23:59:59', pn_bfcm_timezone() );
}

/**
 * Offer URL for the 2026 campaign.
 *
 * @return string
 */
function pn_bfcm_offer_url() {
	return 'https://pushnotifications.io/pricing';
}

/**
 * Coupon code shown on the banner.
 *
 * @return string
 */
function pn_bfcm_coupon() {
	return 'BFCM2026';
}

/**
 * Whether the current user should see the banner.
 *
 * @return bool
 */
function pn_bfcm_is_active() {
	if ( ! current_user_can( push_notification_current_user_can() ) ) {
		return false;
	}

	$now = new DateTimeImmutable( 'now', pn_bfcm_timezone() );

	if ( $now > pn_bfcm_campaign_end() ) {
		return false;
	}

	$dismissed_until = (int) get_user_meta( get_current_user_id(), pn_bfcm_dismiss_meta_key(), true );

	if ( $dismissed_until && time() < $dismissed_until ) {
		return false;
	}

	return true;
}

/**
 * Seconds left until the campaign ends.
 *
 * @return int
 */
function pn_bfcm_remaining_seconds() {
	$remaining = pn_bfcm_campaign_end()->getTimestamp() - time();

	if ( $remaining < 0 ) {
		return 0;
	}

	return (int) $remaining;
}

/**
 * Static SVG icons used in the banner.
 *
 * @param string $name Icon name.
 * @return string
 */
function pn_bfcm_icon( $name ) {
	$icons = array(
		'bolt' => '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13 1 4 14h6.5L9 23l11-13h-6.2L13 1z"/></svg>',
		'copy' => '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16 1H4c-1.1 0-2 .9-2 2v12h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>',
		'lock' => '<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm-3 8V7a3 3 0 0 1 6 0v3H9z"/></svg>',
		'hourglass' => '<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6 2h12v2.2c0 .3-.1.6-.3.8L13.2 12l4.5 7c.2.2.3.5.3.8V22H6v-2.2c0-.3.1-.6.3-.8l4.5-7-4.5-7c-.2-.2-.3-.5-.3-.8V2zm2 2v.6l4 6.2 4-6.2V4H8zm0 16h8v-.6l-4-6.2-4 6.2V20z"/></svg>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return wp_kses(
		$icons[ $name ],
		array(
			'svg'  => array(
				'viewbox'     => true,
				'viewBox'     => true,
				'width'       => true,
				'height'      => true,
				'aria-hidden' => true,
				'focusable'   => true,
			),
			'path' => array(
				'fill' => true,
				'd'    => true,
			),
		)
	);
}

/**
 * Whether the current admin screen is a Push Notifications settings page.
 *
 * @param string $hook Admin page hook suffix.
 * @return bool
 */
function pn_bfcm_is_admin_hook( $hook ) {
	return in_array( $hook, array( 'toplevel_page_push-notification', 'toplevel_page_push-notification-global-setting' ), true );
}

add_action( 'admin_enqueue_scripts', 'pn_enqueue_bfcm_assets' );

/**
 * Load banner assets on the plugin settings screen while the campaign is active.
 *
 * @param string $hook Admin page hook suffix.
 * @return void
 */
function pn_enqueue_bfcm_assets( $hook ) {
	if ( ! pn_bfcm_is_admin_hook( $hook ) || ! pn_bfcm_is_active() ) {
		return;
	}

	$css_path = PUSH_NOTIFICATION_PLUGIN_DIR . 'assets/css/bfcm-style.css';
	$js_path  = PUSH_NOTIFICATION_PLUGIN_DIR . 'assets/js/bfcm-script.js';

	wp_enqueue_style(
		'pn-bfcm-style',
		PUSH_NOTIFICATION_PLUGIN_URL . 'assets/css/bfcm-style.css',
		array(),
		PUSH_NOTIFICATION_PLUGIN_VERSION . '.' . filemtime( $css_path )
	);

	wp_enqueue_script(
		'pn-bfcm-script',
		PUSH_NOTIFICATION_PLUGIN_URL . 'assets/js/bfcm-script.js',
		array( 'jquery' ),
		PUSH_NOTIFICATION_PLUGIN_VERSION . '.' . filemtime( $js_path ),
		true
	);

	wp_localize_script(
		'pn-bfcm-script',
		'pnBfcmData',
		array(
			'targetTimestamp' => pn_bfcm_campaign_end()->getTimestamp(),
			'offerLink'       => pn_bfcm_offer_url(),
			'coupon'          => pn_bfcm_coupon(),
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'pn_bfcm_dismiss' ),
			'copiedText'      => __( 'Copied', 'push-notification' ),
		)
	);
}

/**
 * Print the banner. Caller should place it at the top of the settings screen.
 *
 * @return void
 */
function pn_render_bfcm_banner() {
	if ( ! pn_bfcm_is_active() ) {
		return;
	}

	$remaining = pn_bfcm_remaining_seconds();
	$days      = (int) floor( $remaining / DAY_IN_SECONDS );
	$hours     = (int) floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
	$mins      = (int) floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
	$secs      = (int) ( $remaining % MINUTE_IN_SECONDS );
	?>
	<div id="pn-bfcm-banner" class="pn-bfcm" role="region" aria-label="<?php esc_attr_e( 'Black Friday, Cyber Monday and Christmas sale', 'push-notification' ); ?>">
		<button type="button" class="pn-bfcm-dismiss" aria-label="<?php esc_attr_e( 'Dismiss for 7 days', 'push-notification' ); ?>">&times;</button>
		<div class="pn-bfcm-inner">
			<div class="pn-bfcm-main">
				<span class="pn-bfcm-badge">
					<strong>60%</strong>
					<span><?php esc_html_e( 'OFF', 'push-notification' ); ?></span>
				</span>
				<div class="pn-bfcm-text">
					<span class="pn-bfcm-pill">
						<?php echo pn_bfcm_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized SVG from pn_bfcm_icon(). ?>
						<?php esc_html_e( 'Black Friday / Cyber Monday / Christmas Sale', 'push-notification' ); ?>
					</span>
					<p class="pn-bfcm-title"><?php esc_html_e( 'Biggest sale in our history — never before seen!', 'push-notification' ); ?></p>
					<p class="pn-bfcm-sub"><?php esc_html_e( 'Sale is here! 60% off on Yearly Plan for a very limited time!!!', 'push-notification' ); ?></p>
				</div>
				<button type="button" class="pn-bfcm-code" aria-label="<?php esc_attr_e( 'Copy coupon code', 'push-notification' ); ?>">
					<span class="pn-bfcm-code-label"><?php esc_html_e( 'CODE:', 'push-notification' ); ?> <?php echo esc_html( pn_bfcm_coupon() ); ?></span>
					<?php echo pn_bfcm_icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized SVG from pn_bfcm_icon(). ?>
				</button>
			</div>
			<div class="pn-bfcm-side">
				<div class="pn-bfcm-timer-block">
					<p class="pn-bfcm-expires">
						<?php echo pn_bfcm_icon( 'hourglass' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized SVG from pn_bfcm_icon(). ?>
						<?php esc_html_e( 'Offer expires in:', 'push-notification' ); ?>
					</p>
					<div class="pn-bfcm-timer" aria-hidden="true">
						<span class="pn-bfcm-unit"><strong data-pn-bfcm="days"><?php echo esc_html( sprintf( '%02d', $days ) ); ?></strong><span><?php esc_html_e( 'Days', 'push-notification' ); ?></span></span>
						<span class="pn-bfcm-sep">:</span>
						<span class="pn-bfcm-unit"><strong data-pn-bfcm="hours"><?php echo esc_html( sprintf( '%02d', $hours ) ); ?></strong><span><?php esc_html_e( 'Hours', 'push-notification' ); ?></span></span>
						<span class="pn-bfcm-sep">:</span>
						<span class="pn-bfcm-unit"><strong data-pn-bfcm="mins"><?php echo esc_html( sprintf( '%02d', $mins ) ); ?></strong><span><?php esc_html_e( 'Mins', 'push-notification' ); ?></span></span>
						<span class="pn-bfcm-sep">:</span>
						<span class="pn-bfcm-unit"><strong data-pn-bfcm="secs"><?php echo esc_html( sprintf( '%02d', $secs ) ); ?></strong><span><?php esc_html_e( 'Secs', 'push-notification' ); ?></span></span>
					</div>
				</div>
				<div class="pn-bfcm-cta-block">
					<a class="pn-bfcm-cta" href="<?php echo esc_url( pn_bfcm_offer_url() ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Claim The Offer Now', 'push-notification' ); ?>
						<?php echo pn_bfcm_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized SVG from pn_bfcm_icon(). ?>
					</a>
					<p class="pn-bfcm-guarantee">
						<?php echo pn_bfcm_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized SVG from pn_bfcm_icon(). ?>
						<?php esc_html_e( '30-Day Money Back Guarantee', 'push-notification' ); ?>
					</p>
				</div>
			</div>
		</div>
		<span class="pn-bfcm-sr" aria-live="polite"></span>
	</div>
	<?php
}

add_action( 'wp_ajax_pn_dismiss_bfcm', 'pn_dismiss_bfcm' );

/**
 * Hide the banner for the current user for 7 days.
 *
 * @return void
 */
function pn_dismiss_bfcm() {
	check_ajax_referer( 'pn_bfcm_dismiss', 'nonce' );

	if ( ! current_user_can( push_notification_current_user_can() ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	$dismissed_until = time() + ( 7 * DAY_IN_SECONDS );
	update_user_meta( get_current_user_id(), pn_bfcm_dismiss_meta_key(), $dismissed_until );

	wp_send_json_success(
		array(
			'dismissedUntil' => $dismissed_until,
		)
	);
}

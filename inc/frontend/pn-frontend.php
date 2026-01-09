<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Push_Notification_Frontend{
	public $notificatioArray = array("gcm_sender_id"=> "103953800507");
	public function __construct(){
		$this->init();
	}

	public function pn_pwa_manifest_config(){
		return array(
			"gcm_sender_id"=> "103953800507"
		);
	}
	public function pn_manifest_config(){
		return array(
			"gcm_sender_id"=> "103953800507",
			"start_url"=> "/",
			"name"=> get_bloginfo( 'name' ),
			"display"=> "standalone"
		);
	}

	public function init(){
		$auth_settings = push_notification_auth_settings();
		if(empty($auth_settings)
			|| !isset($auth_settings['user_token']) 
			|| !isset($auth_settings['messageManager'])
			|| (isset($auth_settings['messageManager'])
				&& empty( $auth_settings['messageManager'])
				)
		){
        	return false;
        }
		// Get banner location setting
		$settings = push_notification_settings();
		$banner_location = isset($settings['banner_location']) ? $settings['banner_location'] : 'footer';
		
		if( function_exists('pwaforwp_init_plugin') ){
			$addNotification = false;
			if( function_exists('pwaforwp_defaultSettings') ) {
				$pwaSettings = pwaforwp_defaultSettings();
				if( $pwaSettings['notification_feature']==1 && isset($pwaSettings['notification_options']) && $pwaSettings['notification_options']=='pushnotifications_io'){
					$addNotification = true;
				}
			}
			if($addNotification){
				add_filter( 'pwaforwp_manifest', array($this, 'manifest_add_gcm_id') );

				add_action("wp_enqueue_scripts", array($this, 'pwaforwp_enqueue_pn_scripts'), 34 );
				$this->pn_add_banner_hook($banner_location);
			}
		}elseif(function_exists('superpwa_addons_status')){
			add_filter( 'superpwa_manifest', array($this, 'manifest_add_gcm_id') );
			add_action("wp_enqueue_scripts", array($this, 'superpwa_enqueue_pn_scripts'), 34 );
			$this->pn_add_banner_hook($banner_location);
			add_filter( "superpwa_sw_template", array($this, 'superpwa_add_pn_swcode'),10,1);
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reasone: Not processing form
		}elseif(function_exists('amp_is_enabled') && amp_is_enabled() && empty($_GET['noamp'])){			
			add_action( 'rest_api_init', array( $this, 'register_manifest_rest_route' ) );
			add_action("wp_footer", array($this, 'header_content'));
			add_action("wp_footer", array($this, 'amp_header_button_css'));
		}else{
			//manifest
			add_action('wp_head',array($this, 'manifest_add_homescreen'),1);
			$this->pn_add_banner_hook($banner_location);
			//create manifest
			add_action( 'rest_api_init', array( $this, 'register_manifest_rest_route' ) );
			//ServiceWorker
			add_action("wp_enqueue_scripts", array($this, 'enqueue_pn_scripts') );


		}
		//firebase serviceworker
		add_action( 'parse_query', array($this, 'load_service_worker') );

		
		add_action( 'init', array($this, 'sw_template_query_var') );
		add_action( 'pn_tokenid_registration_id', array($this, 'peepso_pn_tokenid_registration_id') ,10,5);
		

		add_action( 'peepso_action_group_user_invitation_send', array($this, 'pn_peepso_action_group_user_invitation_send'),10,1 );

		add_action( 'peepso_friends_requests_after_add', array($this, 'pn_peepso_friends_requests_after_add'),10,2);
		add_action( 'peepso_friends_requests_after_accept', array($this, 'pn_peepso_friends_requests_after_accept'),10,2);

		add_action( 'peepso_activity_after_add_post', array($this, 'pn_peepso_activity_after_add_post'),10,2);
		add_action( 'peepso_after_add_comment', array($this, 'pn_peepso_after_add_comment'),10,4);

		add_action( 'wp_ajax_pn_register_subscribers', array( $this, 'pn_register_subscribers' ) );
		add_action( 'wp_ajax_nopriv_pn_register_subscribers', array( $this, 'pn_register_subscribers' ) );

		// Buddyboss
		add_action( 'pn_tokenid_registration_id', array($this, 'buddyboss_pn_tokenid_registration_id') ,10,5);
		add_action( 'bp_activity_comment_posted', array( $this,'buddyboss_pn_activity_comment_action'), 10, 2);
		add_action( 'messages_message_sent', array( $this,'buddyboss_pn_message_notifications'), 10 ,1);
		add_action( 'bp_invitations_send_invitation_by_id_before_send', array( $this,'buddyboss_pn_invitation_notifications'));
		add_filter( 'friends_friendship_requested', array( $this,'buddyboss_pn_friend_request'), 10, 4);
		add_filter( 'friends_friendship_accepted', array( $this,'buddyboss_pn_friend_request_accepted'), 10, 4);
		add_action('bp_activity_after_save', array($this, 'buddyboss_pn_group_activity_notification'), 10, 1);

		// Buddyboss end

		// Gravity Form Start
		add_action( 'pn_tokenid_registration_id', array($this, 'gravity_pn_tokenid_registration_id') ,10,5);
		add_action( 'gform_after_save_form', array($this, 'send_pn_on_gravity_form_saved'), 10, 2 );
		//  Gravity Form End
		
		
		// Fluent Community Start
		add_action( 'pn_tokenid_registration_id', array($this, 'fluent_community_pn_tokenid_registration_id') ,10,5);
		add_action( 'fluent_community/feed/created', array($this, 'pn_notify_on_fc_feed_created'), 10, 1 );
		add_action('fluent_community/comment_added', array($this, 'pn_notify_on_fc_new_comment'), 10, 2);
		add_action('fluent_community/feed/react_added', array($this, 'pn_notify_on_fc_react_added'), 10, 2);
		//  Fluent Community End

		//click event
		add_action( 'wp_ajax_pn_noteclick_subscribers', array( $this, 'pn_noteclick_subscribers' ) );
		add_action( 'wp_ajax_nopriv_pn_noteclick_subscribers', array( $this, 'pn_noteclick_subscribers' ) );
		//AMP Connect
		add_action( "pre_amp_render_post", array($this, 'amp_entry_gate') );

		 if( function_exists('ampforwp_get_setting') && ampforwp_get_setting('amp-mobile-redirection') && wp_is_mobile() ){
		 	add_action('template_redirect', array($this, 'page_redirect'), 9);
		 }else{
		 	add_filter('template_include', array($this, 'page_include'), 1, 1);
		 }

		//Woocommerce order status Compatibility
		//Store token ID
		add_action('pn_tokenid_registration_id', array($this, 'store_user_registered_tokens'), 10, 6);

		//set transient to detect user login so that we can update the token id
		add_action('wp_login', array($this, 'after_login_transient'), 10, 2); 
		 // force token update if login is detected
		add_filter('pn_token_exists', array($this, 'pn_token_exists'), 10, 1);
		add_action('wp_enqueue_scripts', array($this,'pn_enqueue_scripts'));
		add_action('wp_footer', array($this,'pn_enqueue_ajax_pagination_script'));
		add_shortcode('pn_campaigns', array($this,'pn_campaigns_shortcode'));
		add_action('wp_ajax_pn_get_compaigns_front', array( $this, 'pn_get_compaigns_front' ));
	}

	function pn_campaigns_shortcode($atts) {
		$atts = shortcode_atts(array(
			'title' => true,
			'message' => true,
			'date' => true,
			'status' => true,
			'campaign_name' => 'Campaign',
			'heading_background_color' => 'black',
			'heading_text_color' => '#fff',
			'table_color' => '#fff',
			'table_data_color' => 'black',
			'table_width' => '800px',
			'pagination' => '1',
			'total_item' => '0',
		), $atts, 'pn_campaigns');

		$auth_settings = push_notification_auth_settings();
		$detail_settings = push_notification_details_settings();
		$campaigns = [];

		if (!$detail_settings && isset($auth_settings['user_token'])) {
			PN_Server_Request::getsubscribersData($auth_settings['user_token']);
			$detail_settings = push_notification_details_settings();
		}
		if (!empty($auth_settings['user_token'])) {
			$campaigns = PN_Server_Request::getCompaignsData($auth_settings['user_token']);
		}
		
		ob_start();
		?>
		<style>
			.pn-table-wrapper {
				max-width: <?php echo esc_attr($atts['table_width']); ?>;
				overflow-x: auto;
				margin: 1.5em 0;
			}

			.pn-table {
				width: 100%;
				border-collapse: collapse;
				background: <?php echo esc_attr($atts['table_color']); ?>;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				border: 1px solid #ccc;
			}

			.pn-table th,
			.pn-table td {
				padding: 8px 8px;
				text-align: left;
				border: 1px solid #ccc;
				color: <?php echo esc_attr($atts['table_data_color']); ?>;
			}

			.pn-table th {
				background:<?php echo esc_attr($atts['heading_background_color']); ?>;
				color: <?php echo esc_attr($atts['heading_text_color']); ?>;
				font-weight: 400;
			}

			.pn-badge {
				padding: 2px 4px 2px;
				border-radius: 4px;
				color: #fff;
				font-size: 0.75em;
			}

			.pn-done {
				background-color: #28a745;
			}
			.pn-failed {
				background-color: #dc3545;
			}
			.pn-pending {
				background-color: #007bff;
			}

			.pn-pagination {
				margin-top: 15px;
				display: flex;
				justify-content: space-between;
				align-items: center;
			}

			.pagination-links a {
				margin: 0 5px;
				text-decoration: none;
				background: #007bff;
				color: white;
				padding: 0px 10px;
				border-radius: 4px;
				transition: background 0.3s;
			}
			.pagination-links .disabled {
				margin: 0 5px;
				text-decoration: none;
				background:rgb(154, 155, 156);
				color: white;
				padding: 0px 10px;
				border-radius: 4px;
				transition: background 0.3s;
			}

			.pagination-links a:hover {
				background: #0056b3;
			}
			.tablenav-pages {
				margin-top:10px;
				float:right;
			}
		</style>
		
		<div class="pn-table-wrapper">
			<h3><?php echo esc_html($atts['campaign_name']); ?></h3>
			<div id="pn_campaings_custom_div" attr="<?php echo esc_attr(json_encode($atts)) ?>">
			<table class="pn-table">
				<thead>
					<tr style="background-color:black !important; color:white;">
						<?php if ($atts['title']) : ?><th><?php esc_html_e('Title', 'push-notification'); ?></th><?php endif; ?>
						<?php if ($atts['message']) : ?><th><?php esc_html_e('Message', 'push-notification'); ?></th><?php endif; ?>
						<?php if ($atts['date']) : ?><th><?php esc_html_e('Sent on', 'push-notification'); ?></th><?php endif; ?>
						<?php if ($atts['status']) : ?><th><?php esc_html_e('Status', 'push-notification'); ?></th><?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$counter = 0;
					if (!empty($campaigns['campaigns']['data'])) :
						$timezone_string = get_option('timezone_string') ?: 'UTC';
						foreach ($campaigns['campaigns']['data'] as $campaign) :
							
							if (isset($atts['total_item']) && $atts['total_item'] > 0 && $counter >= $atts['total_item']) {
								break;
							}
							$counter++;
							$message = wp_strip_all_tags($campaign['message']);
							if (strlen($message) > 100) {
								$message = substr($message, 0, strrpos(substr($message, 0, 100), ' ')) . '...';
							}
							$date = new DateTime($campaign['created_at'], new DateTimeZone('UTC'));
							$date->setTimezone(new DateTimeZone($timezone_string));
							?>
							<tr>
								<?php if ($atts['title']) : ?><td><?php echo esc_html($campaign['title']); ?></td><?php endif; ?>
								<?php if ($atts['message']) : ?><td><?php echo esc_html($message); ?></td><?php endif; ?>
								<?php if ($atts['date']) : ?><td><?php echo esc_html($date->format('Y-m-d H:i:s')); ?></td><?php endif; ?>
								<?php if ($atts['status']) : ?>
									<td>
										<span class="pn-badge pn-<?php echo esc_attr( strtolower($campaign['status'] ) ); ?>">
											<?php echo esc_html($campaign['status']); ?>
										</span>
									</td>
								<?php endif; ?>
							</tr>
						<?php
						endforeach;
					else :
						?>
						<tr><td colspan="4" style="text-align:center;"><?php esc_html_e('No data found', 'push-notification'); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			<?php if ($atts['pagination'] == '1' && !empty($campaigns['campaigns'])) : ?>
			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo esc_html($campaigns['campaigns']['total']) . ' ' . esc_html__('items', 'push-notification'); ?></span>
				<span class="pagination-links">
					<?php if (empty($campaigns['campaigns']['prev_page_url'])) : ?>
						<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
						<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
					<?php else : ?>
						<a class="first-page button pn_js_custom_pagination" page="1" href="<?php echo esc_attr($campaigns['campaigns']['first_page_url']); ?>">
							<span class="screen-reader-text"><?php esc_html_e('First page', 'push-notification'); ?></span>
							<span aria-hidden="true">«</span>
						</a>
						<a class="prev-page button pn_js_custom_pagination" page="<?php echo esc_attr($campaigns['campaigns']['current_page'] - 1); ?>" href="<?php echo esc_attr($campaigns['campaigns']['prev_page_url']); ?>">
							<span class="screen-reader-text"><?php esc_html_e('Previous page', 'push-notification'); ?></span>
							<span aria-hidden="true">‹</span>
						</a>
					<?php endif; ?>

					<span class="screen-reader-text"><?php esc_html_e('Current Page', 'push-notification'); ?></span>
					<span id="table-paging" class="paging-input" style="margin: 0 8px;">
						<span class="tablenav-paging-text">
							<?php echo esc_html($campaigns['campaigns']['current_page']) . ' ' . esc_html__('of', 'push-notification'); ?>
							<span class="total-pages"><?php echo esc_html($campaigns['campaigns']['last_page']); ?></span>
						</span>
					</span>

					<?php if (empty($campaigns['campaigns']['next_page_url'])) : ?>
						<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
						<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
					<?php else : ?>
						<a class="next-page button pn_js_custom_pagination" page="<?php echo esc_attr($campaigns['campaigns']['current_page'] + 1); ?>" href="<?php echo esc_attr($campaigns['campaigns']['next_page_url']); ?>">
							<span class="screen-reader-text"><?php esc_html_e('Next page', 'push-notification'); ?></span>
							<span aria-hidden="true">›</span>
						</a>
						<a class="last-page button pn_js_custom_pagination" page="<?php echo esc_attr($campaigns['campaigns']['last_page']); ?>" href="<?php echo esc_attr($campaigns['campaigns']['last_page_url']); ?>">
							<span class="screen-reader-text"><?php esc_html_e('Last page', 'push-notification'); ?></span>
							<span aria-hidden="true">»</span>
						</a>
					<?php endif; ?>
				</span>
			</div>
			<?php endif; ?>
		</div>
    
		<?php
		return ob_get_clean();
	}

	
	function pn_enqueue_scripts() {
		wp_enqueue_script('jquery');
		wp_register_script('pn-custom-ajax', false, array('jquery'), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		wp_enqueue_script('pn-custom-ajax');
		wp_localize_script('pn-custom-ajax', 'pn_setings', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'remote_nonce' => wp_create_nonce('pn_remote_nonce')
		));
	}


	
	
	function pn_enqueue_ajax_pagination_script() {
		?>
		<script type="text/javascript">
			(function() {
				'use strict';
				
				// Wait for DOM to be ready
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', initPagination);
				} else {
					initPagination();
				}
				
				function initPagination() {
					// Null check for pn_setings
					if (typeof pn_setings === 'undefined' || !pn_setings) {
						console.error('pn_setings is not defined');
						return;
					}
					
					// Null check for required pn_setings properties
					if (!pn_setings.ajaxurl || !pn_setings.remote_nonce) {
						console.error('pn_setings.ajaxurl or pn_setings.remote_nonce is missing');
						return;
					}
					
					// Event delegation on body
					document.body.addEventListener('click', function(e) {
						// Check if clicked element or its parent has the pagination class
						var target = e.target.closest('.pn_js_custom_pagination');
						if (!target) {
							return;
						}
						
						e.preventDefault();
						
						// Null check for page attribute
						var page = target.getAttribute('page');
						if (!page) {
							console.error('Page attribute is missing');
							return;
						}
						
						// Null check for campaigns div
						var campaignsDiv = document.getElementById('pn_campaings_custom_div');
						if (!campaignsDiv) {
							console.error('pn_campaings_custom_div element not found');
							return;
						}
						
						// Null check for attr attribute
						var atts = campaignsDiv.getAttribute('attr');
						if (!atts) {
							console.error('attr attribute is missing');
							return;
						}
						
						// Parse JSON with error handling
						var shortcode_attr;
						try {
							shortcode_attr = JSON.parse(atts);
						} catch (error) {
							console.error('Failed to parse JSON from attr attribute:', error);
							alert('Invalid data format.');
							return;
						}
						
						// Prepare form data
						var formData = new FormData();
						formData.append('action', 'pn_get_compaigns_front');
						formData.append('page', page);
						formData.append('nonce', pn_setings.remote_nonce);
						formData.append('attr', JSON.stringify(shortcode_attr));
						
						// Make AJAX request using fetch API
						fetch(pn_setings.ajaxurl, {
							method: 'POST',
							body: formData
						})
						.then(function(response) {
							if (!response.ok) {
								throw new Error('Network response was not ok');
							}
							return response.text();
						})
						.then(function(html) {
							if (html && campaignsDiv) {
								campaignsDiv.innerHTML = html;
							} else {
								throw new Error('Empty response received');
							}
						})
						.catch(function(error) {
							console.error('Error fetching campaigns:', error);
							alert('Something went wrong.');
						});
					});
				}
			})();
		</script>
		<?php
	}

	public function pn_get_compaigns_front(){
		if(empty( $_POST['nonce'])){
			return;	
		}
		if( isset( $_POST['nonce']) &&  !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pn_remote_nonce') ){
			return;	
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;	
		}
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$page =  sanitize_text_field( wp_unslash( $_POST['page'] ) );
		$authData = push_notification_auth_settings();
		if ($authData['token_details']['validated']!=1 ){
			return;  
		}
		
		$campaigns = [];
		$attr = [];
		if(isset( $authData['user_token'] ) && !empty($authData['user_token']) ){
			$campaigns = PN_Server_Request::getCompaignsData( $authData['user_token'],$page);
		}

		if (isset($_POST['attr'])) {
			$attr =  array_map('sanitize_text_field',wp_unslash( $_POST['attr'] ) );
		}
		if (!empty($attr) && isset($attr['campaign_name'])) {
			$timezone_string = get_option('timezone_string') ?: 'UTC';
			echo '<table class="pn-table"><thead><tr>';
			if (!empty($attr['title'])) echo '<th>' . esc_html__('Title', 'push-notification') . '</th>';
			if (!empty($attr['message'])) echo '<th>' . esc_html__('Message', 'push-notification') . '</th>';
			if (!empty($attr['date'])) echo '<th>' . esc_html__('Sent on', 'push-notification') . '</th>';
			if (!empty($attr['status'])) echo '<th>' . esc_html__('Status', 'push-notification') . '</th>';
			echo '</tr></thead><tbody>';

			if (!empty($campaigns['campaigns']['data'])) {
				foreach ($campaigns['campaigns']['data'] as $campaign) {
					$message = wp_strip_all_tags($campaign['message']);
					if (strlen($message) > 100) {
						$message = substr($message, 0, strrpos(substr($message, 0, 100), ' ')) . '...';
					}
					$date = new DateTime($campaign['created_at'], new DateTimeZone('UTC'));
					$date->setTimezone(new DateTimeZone($timezone_string));

					echo '<tr>';
					if (!empty($attr['title'])) echo '<td>' . esc_html($campaign['title']) . '</td>';
					if (!empty($attr['message'])) echo '<td>' . esc_html($message) . '</td>';
					if (!empty($attr['date'])) echo '<td>' . esc_html($date->format('Y-m-d H:i:s')) . '</td>';
					if (!empty($attr['status'])) {
						echo '<td><span class="pn-badge pn-' . esc_attr(strtolower($campaign['status'])) . '">' . esc_html($campaign['status']) . '</span></td>';
					}
					echo '</tr>';
				}
			} else {
				echo '<tr><td colspan="4" style="text-align:center;">' . esc_html__('No data found', 'push-notification') . '</td></tr>';
			}

			echo '</tbody></table>';

			// Pagination
			echo '<div class="tablenav-pages">';
			echo '<span class="displaying-num">' . esc_html($campaigns['campaigns']['total']) . ' ' . esc_html__('items', 'push-notification') . '</span>';
			echo '<span class="pagination-links">';

			if (empty($campaigns['campaigns']['prev_page_url'])) {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true" style="margin: 0 8px;">«</span>';
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true" style="margin: 0 8px;">‹</span>';
			} else {
				echo '<a class="first-page button pn_js_custom_pagination" page="1" href="' . esc_url($campaigns['campaigns']['first_page_url']) . '"><span class="screen-reader-text">' . esc_html__('First page', 'push-notification') . '</span><span aria-hidden="true" >«</span></a>';
				echo '<a class="prev-page button pn_js_custom_pagination" page="' . esc_attr($campaigns['campaigns']['current_page'] - 1) . '" href="' . esc_url($campaigns['campaigns']['prev_page_url']) . '"><span class="screen-reader-text">' . esc_html__('Previous page', 'push-notification') . '</span><span aria-hidden="true" >‹</span></a>';
			}

			echo '<span class="screen-reader-text">' . esc_html__('Current Page', 'push-notification') . '</span>';
			echo '<span id="table-paging" class="paging-input" style="margin: 0 8px;">';
			echo '<span class="tablenav-paging-text">' . esc_html($campaigns['campaigns']['current_page']) . ' ' . esc_html__('of', 'push-notification') . ' <span class="total-pages">' . esc_html($campaigns['campaigns']['last_page']) . '</span></span>';
			echo '</span>';

			if (empty($campaigns['campaigns']['next_page_url'])) {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true" style="margin: 0 8px;">›</span>';
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true" style="margin: 0 8px;">»</span>';
			} else {
				echo '<a class="next-page button pn_js_custom_pagination" page="' . esc_attr($campaigns['campaigns']['current_page'] + 1) . '" href="' . esc_url($campaigns['campaigns']['next_page_url']) . '"><span class="screen-reader-text">' . esc_html__('Next page', 'push-notification') . '</span><span aria-hidden="true" >›</span></a>';
				echo '<a class="last-page button pn_js_custom_pagination" page="' . esc_attr($campaigns['campaigns']['last_page']) . '" href="' . esc_url($campaigns['campaigns']['last_page_url']) . '"><span class="screen-reader-text">' . esc_html__('Last page', 'push-notification') . '</span><span aria-hidden="true" >»</span></a>';
			}

			echo '</span></div>';
			wp_die();
		}         
	}





	public static function update_autoptimize_exclude( $values, $option ){
		if(!stripos($values, PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/application.min.js')){
			$values .= ", ".PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/application.min.js';
		}
		if(!stripos($values, PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/messaging.min.js')){
			$values .= ", ".PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/messaging.min.js';
		}


		return $values;
	}
	function sw_template_query_var(){
		global $wp;
		 $wp->add_query_var( 'push_notification_sw' );
		 $wp->add_query_var( 'push_notification_amp_js' );
		 $wp->add_query_var( 'subscribe_pushnotification' );

		 add_rewrite_rule('subscribe/pushnotification/?$', 
					'index.php?subscribe_pushnotification=1','top');
	}

	function load_service_worker(WP_Query $query ){

		if ( $query->is_main_query() && $query->get( 'push_notification_sw' ) ) {

			header("Service-Worker-Allowed: /");
			header("Content-Type: application/javascript");
			header('Accept-Ranges: bytes');
			$messagesw_escaped = $this->pn_get_layout_files('messaging-sw.js');
			$settings = $this->json_settings();
			$messagesw_escaped = str_replace('{{pnScriptSetting}}', wp_json_encode($settings), $messagesw_escaped);
			/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped  */
			echo $messagesw_escaped;
            exit;

		}

		if ( $query->is_main_query() && $query->get( 'push_notification_amp_js' ) ) {

			header("Content-Type: application/javascript");
			header('Accept-Ranges: bytes');
			$messagesw_escaped = $this->pn_get_layout_files('messaging-sw.js');
			$settings = $this->json_settings();
			$messagesw_escaped = str_replace('{{pnScriptSetting}}', wp_json_encode($settings), $messagesw_escaped);
			/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped  */
			echo $messagesw_escaped;
            exit;

		}

	}

	public function pn_get_layout_files($filePath){

	    $fileContentResponse = @wp_remote_get(esc_url_raw(PUSH_NOTIFICATION_PLUGIN_URL.'assets/'.$filePath));

	    if ( wp_remote_retrieve_response_code( $fileContentResponse ) != 200 ) {

	      if( ! function_exists('get_filesystem_method' ) ){
	        require_once( ABSPATH . 'wp-admin/includes/file.php' );
	      }

	      $access_type = get_filesystem_method();

	      if ( $access_type === 'direct' ) {
	      
			$file = PUSH_NOTIFICATION_PLUGIN_DIR.'assets/'.$filePath;
	        $creds = request_filesystem_credentials( $file, '', false, false, array() );

	        if ( ! WP_Filesystem($creds) ) {
	          return false;
	        }   

	        global $wp_filesystem;
	        $htmlContentbody = $wp_filesystem->get_contents($file);
	        return $htmlContentbody;

	      }

	      return false;

	    } else {

	      return wp_remote_retrieve_body( $fileContentResponse );
		  
	    }
	}

	public function json_settings(){
		if ( is_multisite() ) {
            $link = get_site_url();              
        }
        else {
            $link = home_url();
        }    
        $auth_settings = push_notification_auth_settings();
		$pn_Settings = push_notification_settings();
		$messageConfig = '';
		$pn_token_exists = 1;
        if(isset($auth_settings['user_token']) && isset($auth_settings['token_details']['validated']) && $auth_settings['token_details']['validated'] == 1){
        	$messageConfig = json_decode($auth_settings['messageManager'], true);
        }
		$pn_current_user_id=get_current_user_id()?get_current_user_id():0;
		if($pn_current_user_id>0){
			$pn_user_token = get_user_meta($pn_current_user_id, 'pnwoo_notification_token',true);
			if(is_array($pn_user_token) && !empty($pn_user_token)){
				$pn_user_token = array_filter($pn_user_token);
				$pn_user_token = array_unique($pn_user_token);
				if(count($pn_user_token) == 0){
					$pn_token_exists=0;
				}
			}
			if(!$pn_user_token || (is_array($pn_user_token) && empty($pn_user_token)))
			{
				$pn_token_exists=0;
			}
		}
		$superpwa_apk_only = $pwaforwp_apk_only = false;
		if(isset($pn_Settings['pn_key_showon_apk_only']) && $pn_Settings['pn_key_showon_apk_only'] == 1){
			
			if(function_exists('superpwa_addons_status')){
				$superpwa_apk_only = true;
			}
			if(function_exists('pwaforwp_defaultSettings')){
				$pwaSettings = pwaforwp_defaultSettings();
				if( $pwaSettings['notification_feature']==1 && isset($pwaSettings['notification_options']) && $pwaSettings['notification_options']=='pushnotifications_io'){
					$pwaforwp_apk_only = true;
				}
			}
		}
        // Auto-segmentation data
		$segmentation_type = isset($pn_Settings['segmentation_type']) ? $pn_Settings['segmentation_type'] : 'manual';
		$auto_segment_enabled = ($segmentation_type == 'auto');
		$auto_categories = array();
		$auto_authors = array();
		
		
		if ($auto_segment_enabled && (is_single() || is_page())) {
			global $post;
			if ($post) {

				$post_categories = get_the_category($post->ID);
				if (!empty($post_categories)) {
					foreach ($post_categories as $category) {
						$auto_categories[] = $category->slug;
					}
				}
				
				$auto_authors[] = $post->post_author;
			}
		}
		
        $settings = array(
					'nonce' =>  wp_create_nonce("pn_notification"),
					'pn_config'=> $messageConfig,
					"swsource" => esc_url_raw(trailingslashit($link)."?push_notification_sw=1"),
					"scope" => esc_url_raw(trailingslashit($link)),
					"ajax_url"=> esc_url_raw(admin_url('admin-ajax.php')),
					"cookie_scope"=>esc_url_raw(apply_filters('push_notification_cookies_scope', "/")),
					'notification_popup_show_again'=>$pn_Settings['notification_popup_show_again'],
					'popup_show_afternseconds'=> $pn_Settings['notification_popup_show_afternseconds'],
					'popup_show_afternpageview'=> $pn_Settings['notification_popup_show_afternpageview'],
					'pn_token_exists' =>apply_filters('pn_token_exists',$pn_token_exists),
					'superpwa_apk_only' => $superpwa_apk_only,
					'pwaforwp_apk_only' => $pwaforwp_apk_only,
					'segmentation_type' => $segmentation_type,
					'auto_segment_enabled' => $auto_segment_enabled,
					'auto_categories' => $auto_categories,
					'auto_authors' => $auto_authors
					);
        return $settings;
	}


	public function enqueue_pn_scripts(){
		wp_enqueue_script('pn-script-app-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/application.min.js', array(), PUSH_NOTIFICATION_PLUGIN_VERSION, true);

		wp_enqueue_script('pn-script-messaging-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/messaging.min.js', array('pn-script-app-frontend'), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';	
		wp_enqueue_script('pn-script-frontend', PUSH_NOTIFICATION_PLUGIN_URL."assets/public/app{$min}.js", array('pn-script-app-frontend','pn-script-messaging-frontend'), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		$settings = $this->json_settings();
		wp_localize_script('pn-script-app-frontend', 'pnScriptSetting', $settings);
	}
	public function pwaforwp_enqueue_pn_scripts(){
		wp_enqueue_script('pn-script-app-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/application.min.js', array(), PUSH_NOTIFICATION_PLUGIN_VERSION, true);

		wp_enqueue_script('pn-script-messaging-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/messaging.min.js', array('pn-script-app-frontend'), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		$settings = $this->json_settings();
		wp_localize_script('pn-script-app-frontend', 'pnScriptSetting', $settings);
	}

	
	public function superpwa_enqueue_pn_scripts(){
		wp_enqueue_script('pn-script-app-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/application.min.js', array(), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		wp_enqueue_script('pn-script-messaging-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'assets/public/messaging.min.js', array('pn-script-app-frontend'), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script('pn-script-frontend', PUSH_NOTIFICATION_PLUGIN_URL."assets/public/app-pwaforwp{$min}.js", array('pn-script-app-frontend','pn-script-messaging-frontend'), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		$settings = $this->json_settings();
		wp_localize_script('pn-script-app-frontend', 'pnScriptSetting', $settings);
	}
	public function manifest_add_homescreen(){
		echo '<link rel="manifest" href="'. esc_url( $this->urls_https( rest_url( 'push-notification/v2/pn-manifest-json' ) ) ).'">';
	}

	public function urls_https( $url ) {
        return str_replace( 'http://', 'https://', $url );
	}

	public function register_manifest_rest_route() {
        $rest_namepace = 'push-notification/v2';
        $route = 'pn-manifest-json';
        register_rest_route(
            $rest_namepace,
            $route,
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_manifest' ),
                'permission_callback' => array( $this, 'rest_permission' ),
            )
        );
    }
    public function rest_permission( WP_REST_Request $request ) {
        if ( 'edit' === $request['context'] ) {
            return new WP_Error( 'rest_forbidden_context', esc_html__( 'Sorry, you are not allowed to edit the manifest.', 'push-notification' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }
    public function get_manifest($request){
    	$array = $this->pn_manifest_config();
        return $array;
    }

    public function manifest_add_gcm_id($manifest){
		if(is_array($this->pn_pwa_manifest_config()) && !empty($this->pn_pwa_manifest_config())){
    		$manifest = array_merge($manifest, $this->pn_pwa_manifest_config());
		}
    	return $manifest;
    }

    public function pn_register_subscribers(){

		if(empty( $_POST['nonce'])){
			wp_send_json(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));
		}
		if( isset( $_POST['nonce']) &&  !wp_verify_nonce(  sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pn_notification') ){
			wp_send_json(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));
		}
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$token_id = sanitize_text_field($_POST['token_id']);
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$user_agent = sanitize_text_field($_POST['user_agent']);
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$category = sanitize_text_field($_POST['category']);
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$author = (isset($_POST['author'])) ? sanitize_text_field($_POST['author']) : "";
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$os = sanitize_text_field($_POST['os']);
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$url = isset($_POST['url'])?sanitize_url($_POST['url']):'';
			$ip_address = $this->get_the_user_ip();
			if(empty($token_id)){
				wp_send_json(array("status"=> 503, 'message'=>esc_html__('token_id is blank', 'push-notification')));
			}
			if(empty($user_agent)){
				wp_send_json(array("status"=> 503, 'message'=>esc_html__('user_agent is blank', 'push-notification')));
			}
			if(empty($os)){
				wp_send_json(array("status"=> 503, 'message'=>esc_html__('OS is blank', 'push-notification')));
			}
			if ($user_agent == 'undefined') {
				$user_agent = $this->check_browser_type();
			}
			$response = PN_Server_Request::registerSubscribers($token_id, $user_agent, $os, $ip_address, $category,$author);
			if (is_user_logged_in() ) {
				if ( isset($response['status']) && $response['status'] == 200 ) {
					$user = wp_get_current_user();
					$current_roles = (array) $user->roles;
					$role_wise_web_token_ids = [];
					$role_wise_web_token_ids = get_option('pn_website_token_ids',[]);
					foreach ($current_roles as $key=> $value ) {
						$role_wise_web_token_ids[$value][] = $response['data']['id'];
					}
					update_option('pn_website_token_ids', $role_wise_web_token_ids);
				}
			}
			do_action("pn_tokenid_registration_id", $token_id, $response, $user_agent, $os, $ip_address, $url);
			wp_send_json($response);
		
	}

	public function pn_noteclick_subscribers(){
		if(empty( $_POST['nonce'])){
			wp_send_json(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));
		}
		if( isset( $_POST['nonce']) &&  !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pn_notification') ){
			wp_send_json(array("status"=> 503, 'message'=>esc_html__('Request not authorized', 'push-notification')));
		}
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$campaign = sanitize_text_field($_POST['campaign']);
			if(empty($campaign)){
				wp_send_json(array("status"=> 503, 'message'=>'Campaign is blank'));
			}
			$response = PN_Server_Request::sendPushNotificatioClickData($campaign);
			wp_send_json($response);
		
	}

	public function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			//check ip from share internet
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	/**
	* Amp Entry point
	*/
	public function amp_entry_gate(){
		if( !function_exists('pwaforwp_init_plugin') ){
			add_action('amp_post_template_head',array($this, 'manifest_add_homescreen'),1);
		}else{
			global $pwaServiceWorker;
			remove_action('amp_wp_template_footer',array($pwaServiceWorker, 'pwaforwp_service_worker'));
			remove_action('amp_post_template_footer',array($pwaServiceWorker, 'pwaforwp_service_worker'));
            remove_filter('amp_post_template_data',array($pwaServiceWorker, 'pwaforwp_service_worker_script'),35);
		}
		add_action("ampforwp_after_header", array($this, 'header_content'));
		add_action("amp_post_template_css", array($this, 'header_button_css'));
	}

	function page_redirect(){
		global $wp_query;
    	if((isset($wp_query->query['pagename']) && $wp_query->query['pagename']=='subscribe/pushnotification') || (isset($wp_query->query['subscribe_pushnotification']) && $wp_query->query['subscribe_pushnotification']==1)){
    		$template = PUSH_NOTIFICATION_PLUGIN_DIR.'/inc/frontend/amp-pn-subscribe.php';
    		if(file_exists($template)){
	    		require_once $template;
				exit;
    		}
    	}
	}

	function page_include($template){
		global $wp_query;
    	if((isset($wp_query->query['pagename']) && $wp_query->query['pagename']=='subscribe/pushnotification') || (isset($wp_query->query['subscribe_pushnotification']) && $wp_query->query['subscribe_pushnotification']==1) ||(isset($wp_query->query['attachment']) && $wp_query->query['attachment']=="pushnotification")){
    		$template = PUSH_NOTIFICATION_PLUGIN_DIR.'inc/frontend/amp-pn-subscribe.php';
    	}
    	return $template;
	}

	function header_button_css(){
		echo '.pushnotification-class{width:100%; position: fixed;bottom: 55px;left:10px;z-index: 99;}
		.pushnotification-class a{background-color: #0062cc;padding: .5rem 1rem;border-radius: 23px;color: white;}
		.pushnotification-class a:hover{color: white;}
		.pushnotification-class a:before{
			content:"";
			background: url(\''.esc_attr( PUSH_NOTIFICATION_PLUGIN_URL ).'assets/image/bell.png\');
		  	width: 24px;
		    height: 20px;
		    background-repeat: no-repeat;
		    display: inline-block;
		    background-size: 20px;
		    position: relative;
		    top: 4px;
		}
				/* On screens that are 600px or less, set the background color to olive */
		@media screen and (max-width: 600px) {
		  .pushnotification-class a span{display:none;}
		  .pushnotification-class a {
			    background-color: #0062cc;
			    padding: 11px 13px 12px 13px;
			    border-radius: 100%;
			    color: #fff;
			    display: inline-block;
			}
		  .pushnotification-class a:before{
			width: 25x;
		    height: 25px;
		    background-size: 25px;
		    top: 0;

		  }
		}

		';
	}
	/**
	* Return true other then IOS
	* Return false on IOS IPAD Iphone
	*/
	public function check_browser_type(){
		$user_agent_name ='others';           
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            if     (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') || strpos($_SERVER['HTTP_USER_AGENT'], 'OPR/')) $user_agent_name = 'opera';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Edge'))    $user_agent_name = 'edge';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox')) $user_agent_name ='firefox';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') || strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7')) $user_agent_name = 'internet_explorer';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'iPod')) $user_agent_name = 'ipod';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone')) $user_agent_name = 'iphone';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'iPad')) $user_agent_name = 'ipad';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'Android')) $user_agent_name = 'android';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'webOS')) $user_agent_name = 'webos';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome'))  $user_agent_name = 'chrome';
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari'))  $user_agent_name = 'safari';
            return $user_agent_name;
	}

	function header_content(){
		if(in_array($this->check_browser_type(), array('ipod','iphone', 'ipad', 'internet_explorer')) ){
			return false;
		}
		if(isset($_COOKIE['notification_permission']) && $_COOKIE['notification_permission']=='granted'){
			return ;
		}
		if ( is_multisite() ) {
            $link = get_site_url();
        }
        else {
            $link = home_url();
        }
		?>
		<div class="pushnotification-class">
			<a class="" target="_blank" href="<?php echo esc_url_raw($link."/subscribe/pushnotification?noamp=available")?>">
				<span><?php
			echo esc_html__('Subscribe for notification', 'push-notification');
			?></span></a>
		</div>
		<?php
	}

	function pn_detect_user_agent_new() {
		$device = 'desktop';
		if ( wp_is_mobile() ) {
			$device = 'mobile';
		}
		return $device;
	}

	/**
	 * Add banner hook based on selected location with fallback
	 */
	function pn_add_banner_hook($location = 'footer'){
		switch($location){
			case 'header':
				// Hook to wp_head
				add_action('wp_head', array($this, 'pn_notification_confirm_banner'), 999);
				break;
			case 'body':
				// Hook to wp_body_open (WordPress 5.2+)
				add_action('wp_body_open', array($this, 'pn_notification_confirm_banner'), 1);
				break;
			case 'footer':
			default:
				// Default: Hook to wp_footer
				add_action('wp_footer', array($this, 'pn_notification_confirm_banner'), 34);
				break;
		}
	}

	function pn_notification_confirm_banner(){
		$settings = push_notification_settings();
		$user_device = $this->pn_detect_user_agent_new();
		$allow_desktop = true;
		$allow_mobile = true;

		if(isset($settings['pn_device_target']['desktop']) && $settings['pn_device_target']['desktop'] == 0){
			$allow_desktop = false;
		}
		if(isset($settings['pn_device_target']['mobile']) && $settings['pn_device_target']['mobile'] == 0){
			$allow_mobile = false;
		}
		if ( ($user_device == 'desktop' && !$allow_desktop) ||
			($user_device == 'mobile' && !$allow_mobile) ) {
			return; 
		}

		if (! $this->pn_display_status($settings)) {
			return;
		}

		if (isset($settings['pn_display_popup_after_login']) && !empty( $settings['pn_display_popup_after_login'] ) && ! is_user_logged_in() ) {
			return false;
		}else{
			if (isset($settings['pn_display_popup_after_login']) && !empty( $settings['pn_display_popup_after_login'] ) && is_user_logged_in() ) {
				$roles_val = (isset($settings['roles'])) ? $settings['roles'] : [];
				if ( !empty( $roles_val )) {
					$selected_roles = !is_array($roles_val) ? explode(',',$roles_val ) : $roles_val;
					$user = wp_get_current_user();
					$current_roles = (array) $user->roles;
					if (empty(array_intersect($current_roles, $selected_roles))) {
						return false;
					}
				}
			}
		}
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( isset($settings['pn_revoke_subscription_popup']) && $settings['pn_revoke_subscription_popup'] && isset($_COOKIE['pn_notification_block']) && $_COOKIE['pn_notification_block'] && !isset($_COOKIE['notification_permission'])) {
			?>
			<style>
				.pn-bell-container {
					position: fixed;
					bottom: 20px;
					right: 20px;
					display: flex;
					align-items: center;
					z-index: 1000;
				}
				.pn-bell-button {
					background-color: #007bff;
					color: white;
					border: none;
					border-radius: 50%;
					width: 60px;
					height: 60px;
					display: flex;
					justify-content: center;
					align-items: center;
					box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
					cursor: pointer;
				}
				.pn-bell-button:hover {
					background-color: #0056b3;
				}
				.pn-bell-icon {
					font-size: 24px;
				}
				.pn-bell-label {
					margin-right: 10px;
					font-size: 16px;
					color: #333;
				}
			</style>
			<div class="pn-bell-container">
				<button class="pn-bell-button" title="Enable Popup">
					<span class="dashicons dashicons-bell"></span>
				</button>
			</div>
			<?php
		}
		$position = "";
		if (isset($settings['notification_position']) && !empty($settings['notification_position'])) {
			$position = $settings['notification_position'];
		}
		$css_position_escaped = '';
		// Check segmentation type and auto-segment settings
		$segmentation_type = isset($settings['segmentation_type']) ? $settings['segmentation_type'] : 'manual';
		$auto_segment_enabled = ($segmentation_type == 'auto') || (isset($settings['pn_auto_segment_post_context']) && $settings['pn_auto_segment_post_context']);
		
		if ($auto_segment_enabled) {
			// Auto-determine category and author from current post
			$current_post_categories = array();
			$current_post_author = '';
			
			if (is_single() || is_page()) {
				global $post;
				if ($post) {
					// Get current post categories
					$post_categories = get_the_category($post->ID);
					if (!empty($post_categories)) {
						foreach ($post_categories as $category) {
							$current_post_categories[] = $category->slug;
						}
					}
					
					// Get current post author
					$current_post_author = $post->post_author;
				}
			}
			
			// Use current post data for segmentation
			$catArray = $current_post_categories;
			$authorArray = $current_post_author ? array($current_post_author) : array();
			$all_category = 0; // Don't show "All Categories" option
		} else {
			// Use existing segmentation logic for manual segmentation
			$setting_category = !empty($settings['category'])? $settings['category'] : [];
			$selected_category =  !is_array($setting_category) ? explode(',',$setting_category) : $setting_category;
			$catArray = !is_array($selected_category) ? explode(',',$selected_category) : $selected_category;

			$setting_author = !empty($settings['author'])? $settings['author'] : [];
			$selected_author =  !is_array($setting_author) ? explode(',',$setting_author) : $setting_author;
			$authorArray = !is_array($selected_author) ? explode(',',$selected_author) : $selected_author;

			$all_category = (isset($settings['segment_on_category'])) ? $settings['segment_on_category'] : 0;
		}
		
		switch ($position) {
			case 'bottom-left':
				$css_position_escaped = 'bottom: 0;
		    left: 0;
		    margin: 20px;
		    right: auto;
		    top: auto;';
				break;
			case 'bottom-right':
				$css_position_escaped = 'bottom: 0;
		    left: auto;
		    margin: 20px;
		    right: 0;
		    top: auto;';
				break;
			case 'top-right':
				$css_position_escaped = 'bottom: auto;
		    left: auto;
		    margin: 20px;
		    margin-top: 40px;
		    right: 0;
		    top: 0;';
				break;
			case 'top-left':
				$css_position_escaped = 'bottom: auto;
						    left: 0;
						    margin: 20px;
						    margin-top: 40px;
						    right: auto;
						    top: 0;';
				break;
			default:
				$css_position_escaped = 'bottom: 0;
		    left: 0;
		    margin: 20px;
		    right: auto;
		    top: auto;';
				break;
		}
		$is_pro = (PN_Server_Request::getProStatus()=='active')?true:false;
		$custom_bg_color = (isset($settings['popup_display_setings_bg_color']) && $is_pro)?$settings['popup_display_setings_bg_color']:'#222';
		$custom_txt_color = (isset($settings['popup_display_setings_title_color'])&& $is_pro)?$settings['popup_display_setings_title_color']:'#fff';
		$activate_btn_color = (isset($settings['popup_display_setings_ok_color'])&& $is_pro)?$settings['popup_display_setings_ok_color']:'#8ab4f8';
		$decline_btn_color = (isset($settings['popup_display_setings_no_thanks_color'])&& $is_pro)?$settings['popup_display_setings_no_thanks_color']:'#5f6368';
		$border_radius =  (isset($settings['popup_display_setings_border_radius'])&& $is_pro)?$settings['popup_display_setings_border_radius']:'4';
		$popup_display_setings_custom_css =  (isset($settings['popup_display_setings_custom_css'])&& $is_pro)?$settings['popup_display_setings_custom_css']:null;
		if ( !empty($popup_display_setings_custom_css) ) {
			echo '<style>'.esc_html( $popup_display_setings_custom_css ).'</style>';
		}else{
			echo '<style>.pn-wrapper{
				box-shadow: 0 1px 3px 0 rgba(60,64,67,0.302), 0 4px 8px 3px rgba(60,64,67,0.149);
				font-size: 14px;
				align-items: center;
				background-color: '.esc_attr($custom_bg_color).';
				border: none;
				border-radius: '.esc_attr($border_radius).'px;
				box-sizing: border-box;
				color: #fff;
				display: none;
				flex-wrap: wrap;
				font-weight: 400;
				padding: 16px 22px;
				z-index:99999;
				text-align: left;
				position: fixed;
				'. /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static values  */ $css_position_escaped.'
				}
				.pn-wrapper .pn-txt-wrap {
					display: flex;
					flex-wrap: wrap;
					position: relative;
					height: auto;
					line-height: 1.5;
					color:'.esc_attr($custom_txt_color).';
					max-width:400px;
				}
				.pn-wrapper .btn.act{color: '.esc_attr($activate_btn_color).';}
				.pn-wrapper .btn{
					align-items: center;
					border: none;
					display: inline-flex;
					outline: none;
					position: relative;
					font-size: 14px;
					background: none;
					border-radius: 4px;
					box-sizing: border-box;
					color: '.esc_attr($decline_btn_color).';
					cursor: pointer;
					font-weight: 500;
					outline: none;
					margin-left: 8px;
					min-width: auto;
					padding: 0 8px;
					text-decoration: none;
				}
				.pn-txt-wrap.pn-select-box {
					display: block;
					padding: 5px 15px;
				}
				.pn-categories-multiselect {
					font-size: 13px;
					margin: 10px 0;
				}
				#pn-activate-permission-categories {
					background-color: #fff;
					padding: 8px 15px;
					color: #000;
				}
				#pn-categories-checkboxes label{
					padding-right: 12px;
					text-transform: capitalize;
					cursor:pointer;
				}
				#pn-categories-checkboxes input{
					margin-right: 3px;
					cursor:pointer;
				}
				#pn-activate-permission-categories-text {
					padding: 12px 0;
					margin-top: 5px;
					font-size: 12px;
					font-weight: 600;
				}
				</style>';
		}
			echo'<div class="pn-wrapper">';
				if(isset($settings['notification_pop_up_icon']) && !empty($settings['notification_pop_up_icon']) && PN_Server_Request::getProStatus()=='active'){
					// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
					echo '<span style=" top: 0; vertical-align: top; "><img src="'.esc_attr($settings['notification_pop_up_icon']).'" style=" max-width: 70px;"></span>';
				}
			   echo '<span class="pn-txt-wrap pn-select-box">
			   		<div class="pn-msg-box">
				   		<span class="pn-msg">'.esc_html($settings['popup_banner_message']).'</span>';
				   		if((isset($settings['notification_botton_position']) && $settings['notification_botton_position'] != 'bottom') || !isset($settings['notification_botton_position'])){
				   			echo '<span class="pn-btns">
				   			<span class="btn act" id="pn-activate-permission_link" tabindex="0" role="link" aria-label="ok link">
				   				'.esc_html($settings['popup_banner_accept_btn']).'
				   			</span>
				   			<span class="btn" id="pn-activate-permission_link_nothanks" tabindex="0" role="link" aria-label="no thanks link">
				   				'.esc_html($settings['popup_banner_decline_btn']).'
				   			</span>
				   		</span>';
				   		}
			   		echo '</div>';
						// Only show segmentation options if manual segmentation is enabled and auto-segment is not enabled
						if($segmentation_type == 'manual' && !$auto_segment_enabled && !empty($settings['on_category']) && $settings['on_category'] == 1){
							if($all_category || !empty($catArray)){
								echo '<div id="pn-activate-permission-categories-text">
									'.esc_html__('On which category would you like to receive?', 'push-notification').'
								</div>
								<div class="pn-categories-multiselect">
								<div id="pn-categories-checkboxes" style="color:'.esc_attr($settings['popup_display_setings_text_color']).'">';
								if($all_category){
										echo '<label for="pn-all-categories"><input type="checkbox" name="category[]" id="pn-all-categories" value="all" />'.esc_html__('All Categories', 'push-notification').'</label>';
								}
							  	if(!empty($catArray)){
								  foreach ($catArray as $key=>$value) {
									  if (is_string($value)) {
										  $catslugdata ='';
										  if(is_object(get_category($value))){
										  $catslugdata = get_category($value)->slug;
										  }
										  echo '<label for="pn_category_checkbox'.esc_attr($value).'"><input type="checkbox" name="category[]" id="pn_category_checkbox'.esc_attr($value).'" value="'.esc_attr($catslugdata).'" />'.esc_html(get_cat_name($value)).'</label>';
									  }
								  }
							  	}
							  	echo '</div></div>';
							}
							  if(!empty($authorArray)){
									echo '<div id="pn-activate-permission-categories-text">
									'.esc_html__('On which author would you like to receive?', 'push-notification').'
								</div>
								<div class="pn-author-multiselect">
									<div id="pn-author-checkboxes" style="color:'.esc_attr($settings['popup_display_setings_text_color']).'">';
							  
								$all_authors = get_users([
									'role' => 'author',
									'orderby' => 'display_name',
									'order' => 'ASC',
								]);
								$author_key_val = [];
								foreach ( $all_authors as $author_d ) {
									$author_key_val[$author_d->ID] = $author_d->display_name;
								}
								foreach ($authorArray as $key=>$value) {
									
										echo '<label for="pn_author_checkbox'.esc_attr($value).'"><input type="checkbox" name="author[]" id="pn_author_checkbox'.esc_attr($value).'" value="'.esc_attr($value).'" />'.esc_html($author_key_val[$value]).'</label>';
								}
									  echo '</div>
								  </div>';
							  }
						}

					   if(isset($settings['notification_botton_position']) && $settings['notification_botton_position'] == 'bottom' && PN_Server_Request::getProStatus()=='active'){
						echo '<span class="pn-btns" style="float:right;margin-top:20px;">
						<span class="btn act" id="pn-activate-permission_link" tabindex="0" role="link" aria-label="ok link">
							'.esc_html($settings['popup_banner_accept_btn']).'
						</span>
						<span class="btn" id="pn-activate-permission_link_nothanks" tabindex="0" role="link" aria-label="no thanks link">
							'.esc_html($settings['popup_banner_decline_btn']).'
						</span>
					</span>';
					}
			   	echo '</span>
			</div>';
	}


	/**
	 * To store the token in db after allow
	 * @method store_user_registered_tokens
	 * @param  String                       $token_id   Generated token we get as string
	 * @param  Array                        $response   response as in Array
	 * @param  String                       $user_agent Type of browser
	 * @param  String                       $os         optional 
	 * @param  String                       $ip_address optional Grab the client ipaddress dummy
	 * @return Void                                   [description]
	 */
	function store_user_registered_tokens($token_id, $response, $user_agent, $os, $ip_address, $url){
		$userData = wp_get_current_user();
		if(is_object($userData) && isset($userData->ID)){
			$userid = $userData->ID;
		 	$token_ids = get_user_meta($userid, 'pnwoo_notification_token', true);
			$token_ids  = maybe_unserialize($token_ids);
			if(!$token_ids || !is_array($token_ids)){
				$token_ids = array();
			}
		 	$token_ids[] = esc_attr($response['data']['id']);
			$token_ids = array_slice(array_unique($token_ids), -5); // keep only last 5 push token
		 	update_user_meta($userid, 'pnwoo_notification_token', $token_ids);

			
		}

		$pn_save_url_token = apply_filters('push_notification_url_tokens',$url,true);
		if($pn_save_url_token){
			$this->pn_add_url_token(esc_url($url),esc_attr($response['data']['id']));
		}
		
	}
	function amp_header_button_css(){
		ob_start();
		$this->header_button_css();
		$css = ob_get_contents();
		ob_clean();
		echo "<style>".esc_attr($css)."</style>";
	}
/*

*/
	public function superpwa_add_pn_swcode($swJsContent)
	{
		header("Service-Worker-Allowed: /");
		header("Content-Type: application/javascript");
		header('Accept-Ranges: bytes');
		$messagesw_escaped = $this->pn_get_layout_files('messaging-sw.js');
		$settings = $this->json_settings();
		$messagesw_escaped = str_replace('{{pnScriptSetting}}', wp_json_encode($settings), $messagesw_escaped);
		$swJsContent .= PHP_EOL.$messagesw_escaped;
		return $swJsContent;
	}

	public function pn_add_url_token($url, $token) {
		global $wpdb;
		// return if url or token is empty
		if(!$url || !$token) {
			return false;
		}
		// return if pro is not active
		$authData = push_notification_auth_settings();
		if( !isset($authData['token_details']['validated']) || (isset($authData['token_details']) && $authData['token_details']['validated']!=1) ){
				return false;
		}
		$settings = push_notification_settings();
		if(isset($settings['pn_url_capture'])){
			if($settings['pn_url_capture'] == 'off'){
				return false;
			}else if($settings['pn_url_capture'] == 'manual'){
				if(isset($settings['pn_url_capture_manual'])){
					$manual_capture = explode(PHP_EOL, $settings['pn_url_capture_manual']);
					foreach ($manual_capture as &$capture) {
						// Remove the trailing slash from the URL
						$capture = rtrim($capture, '/');
					}
					if(empty($manual_capture) || !in_array(rtrim($url, '/'), $manual_capture)){
						return false;
					}
				}else{
					return false;
				}

			}

		}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reasone: Custom table
			return $wpdb->insert(
				$wpdb->prefix.'pn_token_urls',
				array(
					'url' 		 => esc_url( $this->pn_standardize_url( $url ) ),
					'status' 	 => 'active',
					'token' 	 => $token,
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' )
				)
			);

	}

	public function after_login_transient( $user_login, $user ) {

		$user_id = $user->ID;
		$transient_key = 'pn_token_exists_' . $user_id;
		set_transient( $transient_key, true, 12 * HOUR_IN_SECONDS );

	}

	public function pn_token_exists( $status ) {

		$user_id 			= get_current_user_id();
		$transient_key 		= 'pn_token_exists_' . $user_id;
		$transient_value 	= get_transient( $transient_key );

		if ( $transient_value ) {

			delete_transient( $transient_key );
			
			return 0;

		}

		return $status;

	}

	public function pn_standardize_url($url) {

		$parsedUrl = parse_url($url);

		$standardUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

		if (isset($parsedUrl['port']) && 
		   (($parsedUrl['scheme'] === 'http' && $parsedUrl['port'] != 80) || 
		   ($parsedUrl['scheme'] === 'https' && $parsedUrl['port'] != 443))) {
			$standardUrl .= ':' . $parsedUrl['port'];
		}
	
		if (isset($parsedUrl['path'])) {
			$standardUrl .= $parsedUrl['path'];
		}
	
		return $standardUrl;
	}

	public function pn_peepso_send_notification($notification,$sender_info,$title,$message){
		$auth_settings = push_notification_auth_settings();
		$user_token = '';
		if( isset( $auth_settings['user_token'] ) && ! empty( $auth_settings['user_token'] ) ){
			$user_token = $auth_settings['user_token'];
		}
		$verifyUrl = PN_Server_Request::$notificationServerUrl.'campaign/single';

		if ( is_multisite() ) {
			$weblink = get_site_url();
		} else {
			$weblink = home_url();
		}

		$settings = push_notification_settings();
		$user_email = $sender_info->user_email;

	    $avatar_url = get_avatar_url($user_email, ['size' => 96]);

		$data = array(
					"user_token" =>	$user_token,

					"audience_token_id"	=>	$notification,

					"title"	 	=>		 $title,

					"message" 	=>	 $message,

					"link_url" 	=>	 $weblink,

					"icon_url" 	=>	 $avatar_url,

					"image_url" =>	 null,

					"website" 	=>	 $weblink,

				);

		$postdata = array('body'=> $data);

		$remoteResponse = wp_remote_post($verifyUrl, $postdata);

		$this->pn_handle_error_log( $remoteResponse , 'peepso_friends_requests_after_add');
	}
	public function pn_fluent_community_send_notification($notification,$sender_info,$title,$message){
		$auth_settings = push_notification_auth_settings();
		$user_token = '';
		if( isset( $auth_settings['user_token'] ) && ! empty( $auth_settings['user_token'] ) ){
			$user_token = $auth_settings['user_token'];
		}
		$verifyUrl = PN_Server_Request::$notificationServerUrl.'campaign/single';

		if ( is_multisite() ) {
			$weblink = get_site_url();
		} else {
			$weblink = home_url();
		}
		$user_email = $sender_info->user_email;

	    $avatar_url = get_avatar_url($user_email, ['size' => 96]);

		$data = array(
					"user_token" =>	$user_token,

					"audience_token_id"	=>	$notification,

					"title"	 	=>		 $title,

					"message" 	=>	 $message,

					"link_url" 	=>	 $weblink,

					"icon_url" 	=>	 $avatar_url,

					"image_url" =>	 null,

					"website" 	=>	 $weblink,

				);

		$postdata = array('body'=> $data);

		$remoteResponse = wp_remote_post($verifyUrl, $postdata);

		$this->pn_handle_error_log( $remoteResponse , 'pn_fluent_community_send_notification');
	}

	public function pn_buddyboss_send_notification($notification,$sender_info,$title,$message,$weblink = null){
		$auth_settings = push_notification_auth_settings();
		$user_token = '';
		if( isset( $auth_settings['user_token'] ) && ! empty( $auth_settings['user_token'] ) ){
			$user_token = $auth_settings['user_token'];
		}
		$verifyUrl = PN_Server_Request::$notificationServerUrl.'campaign/single';

		if( !$weblink ){
			if ( is_multisite() ) {
				$weblink = get_site_url();
			} else {
				$weblink = home_url();
			}
		}	
		$user_email = $sender_info->user_email;

	    $avatar_url = get_avatar_url($user_email, ['size' => 96]);

		$data = array(
					"user_token" =>	$user_token,

					"audience_token_id"	=>	$notification,

					"title"	 	=>		 $title,

					"message" 	=>	 $message,

					"link_url" 	=>	 $weblink,

					"icon_url" 	=>	 $avatar_url,

					"image_url" =>	 null,

					"website" 	=>	 $weblink,

				);

		$postdata = array('body'=> $data);

		$remoteResponse = wp_remote_post($verifyUrl, $postdata);

		// $this->pn_handle_error_log( $remoteResponse , 'peepso_friends_requests_after_add');
	}

	public function pn_gravity_send_notification($notification,$sender_info,$title,$message){
		$auth_settings = push_notification_auth_settings();
		$user_token = '';
		if( isset( $auth_settings['user_token'] ) && ! empty( $auth_settings['user_token'] ) ){
			$user_token = $auth_settings['user_token'];
		}
		$verifyUrl = PN_Server_Request::$notificationServerUrl.'campaign/single';

		if ( is_multisite() ) {
			$weblink = get_site_url();
		} else {
			$weblink = home_url();
		}
		$user_email = $sender_info->user_email;

	    $avatar_url = get_avatar_url($user_email, ['size' => 96]);

		$data = array(
					"user_token" =>	$user_token,

					"audience_token_id"	=>	$notification,

					"title"	 	=>		 $title,

					"message" 	=>	 $message,

					"link_url" 	=>	 $weblink,

					"icon_url" 	=>	 $avatar_url,

					"image_url" =>	 null,

					"website" 	=>	 $weblink,

				);

		$postdata = array('body'=> $data);

		$remoteResponse = wp_remote_post($verifyUrl, $postdata);

		// $this->pn_handle_error_log( $remoteResponse , 'peepso_friends_requests_after_add');
	}
	public function pn_peepso_action_group_user_invitation_send($PeepSoGroupUser){
		$settings = push_notification_settings();

		if (isset($settings['pn_peepso_compatibale']) && $settings['pn_peepso_compatibale'] && is_plugin_active('peepso/peepso.php') ) {

			$receiver_id = $PeepSoGroupUser->user_id;

			$notification = get_user_meta($receiver_id, 'peepso_pn_notification_token_id', true);


			if( ! empty( $notification ) ) {
				$sender_info = get_userdata($PeepSoGroupUser->invited_by_id);
				$title	 	= esc_html__('Group Invitation', 'push-notification' );
				$message 	= $sender_info->display_name.' '.esc_html__('invite you to join group', 'push-notification' );

				$this->pn_peepso_send_notification($notification,$sender_info,$title,$message);

			}
		}
	}
	public function pn_peepso_friends_requests_after_add($from, $to){
		$settings = push_notification_settings();

		if (isset($settings['pn_peepso_compatibale']) && $settings['pn_peepso_compatibale'] && is_plugin_active('peepso/peepso.php') ) {

			$receiver_id = $to;

			$notification = get_user_meta($receiver_id, 'peepso_pn_notification_token_id', true);

			if(! empty( $notification ) ) {

				$sender_info = get_userdata($to);

				$title	 	= esc_html__('New friend request', 'push-notification' );

				$message 	= esc_html__('You have new friend request of ', 'push-notification' ).$sender_info->display_name;

				$this->pn_peepso_send_notification($notification,$sender_info,$title,$message);
			}

		}
	}

	public function pn_peepso_friends_requests_after_accept($from, $to){
		$settings = push_notification_settings();

		if (isset($settings['pn_peepso_compatibale']) && $settings['pn_peepso_compatibale'] && is_plugin_active('peepso/peepso.php') ) {

			$receiver_id = $from;

			$notification = get_user_meta($receiver_id, 'peepso_pn_notification_token_id', true);

			if(! empty( $notification ) ) {

				$sender_info = get_userdata($to);

				$title	 	= esc_html__('Friend request accepted', 'push-notification' );

				$message 	= $sender_info->display_name .' '.esc_html__('accepted friend request.', 'push-notification' );

				$this->pn_peepso_send_notification($notification,$sender_info,$title,$message);
			}
		}
	}
	public function pn_peepso_activity_after_add_post($post_id, $act_id){
		$settings = push_notification_settings();

		if (isset($settings['pn_peepso_compatibale']) && $settings['pn_peepso_compatibale'] && is_plugin_active('peepso/peepso.php') ) {

			$post_obj = get_post($post_id);

			if ($post_obj->post_type == 'peepso-message') {

				$peepso_participants = new PeepSoMessageParticipants();

				$current_participants = $peepso_participants->get_participants($post_obj->post_parent);

				$receiver_id = $current_participants[1];

				$notification = get_user_meta($receiver_id, 'peepso_pn_notification_token_id', true);

				if(! empty( $notification ) ) {

					$sender_info = get_userdata($current_participants[0]);

					$title	 	= $sender_info->display_name.' '.esc_html__('sent message', 'push-notification' );

					$message 	= esc_html( $post_obj->post_content );

					$this->pn_peepso_send_notification($notification,$sender_info,$title,$message);
				}
			}

			if ($post_obj->post_type == 'peepso-post') {

				$user_id = $post_obj->post_author;

				$model = PeepSoFriendsModel::get_instance();

				$friend_ids =	$model->get_friends($user_id);

				$notification = [];

				foreach ($friend_ids as $key => $receiver_id) {

					$tokens = get_user_meta($receiver_id, 'peepso_pn_notification_token_id', true);

					if(! empty( $tokens ) ) {

						$notification = array_merge($notification, $tokens);

					}
				}

				if(! empty( $notification ) ) {
					$sender_info = get_userdata($user_id);
					$title	 	= esc_html__('New post', 'push-notification' );
					$message 	= $sender_info->display_name. ' '.esc_html__('added new post' , 'push-notification' );
					$this->pn_peepso_send_notification($notification,$sender_info,$title,$message);
				}
			}
		}
	}
	public function pn_peepso_after_add_comment($post_id, $act_id, $did_notify, $did_email){
		$settings = push_notification_settings();

		if (isset($settings['pn_peepso_compatibale']) && $settings['pn_peepso_compatibale'] && is_plugin_active('peepso/peepso.php') ) {
			$peepso_activity = new PeepSoActivity();

			$comment = $peepso_activity->get_comment($post_id);

			$post_obj = $comment->post;

			if ($post_obj->post_type == 'peepso-comment') {

				$receiver_id = $post_obj->act_owner_id;

				$notification = get_user_meta($receiver_id, 'peepso_pn_notification_token_id', true);
				if(! empty( $notification ) ) {
					$sender_info = get_userdata($post_obj->post_author);
					$title	 	= $sender_info->display_name.' '.esc_html__('commented on post', 'push-notification' );
					$message 	= esc_html( $post_obj->post_content );
					$this->pn_peepso_send_notification($notification,$sender_info,$title,$message);
				}
			}
		}
	}

	public function buddyboss_pn_tokenid_registration_id($token_id, $response, $user_agent, $os, $ip_address){
		$settings = push_notification_settings();

		if (isset($settings['pn_buddyboss_compatibale']) && $settings['pn_buddyboss_compatibale'] && ( is_plugin_active('buddyboss-platform/bp-loader.php') || is_plugin_active('buddypress/bp-loader.php') ) ) {

			$userData = wp_get_current_user();
			if(isset($userData->ID)){
				$userid = $userData->ID;

				$device_tokens = get_user_meta($userid, 'buddyboss_pn_notification_token_id', true);

				if ( isset($response['data']['id']) && !empty( $response['data']['id'] )) {
					if (!is_array($device_tokens)) {
					    $device_tokens = [];
					}

					$new_token = $response['data']['id'];

					if (!in_array($new_token, $device_tokens)) {
					    $device_tokens[] = $new_token;
					}
					$device_tokens = array_slice(array_unique($device_tokens), -5);
					update_user_meta($userid, 'buddyboss_pn_notification_token_id', $device_tokens);
				}
			}

		}
	}

	public function buddyboss_pn_activity_comment_action($comment_id, $params){
		$settings = push_notification_settings();
		if (isset($settings['pn_buddyboss_compatibale']) && $settings['pn_buddyboss_compatibale'] && ( is_plugin_active('buddyboss-platform/bp-loader.php') || is_plugin_active('buddypress/bp-loader.php') ) ) {
			$activity = new BP_Activity_Activity($comment_id);
			if (!empty($activity->item_id)) {
				$parent_activity = new BP_Activity_Activity($activity->item_id);
				$receiver_id = $parent_activity->user_id;
				if (!empty($receiver_id) && $receiver_id != $activity->user_id) {
					$notification = get_user_meta($receiver_id, 'buddyboss_pn_notification_token_id', true);
					if (!empty($notification)) {
						$sender_info = get_userdata($activity->user_id);
	
						$title   =  $sender_info->display_name . esc_html__(' commented on your post', 'push-notification');
						$message = wp_trim_words($activity->content, 20, '...');
						$user_domain = bp_core_get_user_domain($receiver_id);
						$activity_link = trailingslashit($user_domain . bp_get_activity_slug()) . $activity->item_id . '/#acomment-' . $activity->id;
	
	
						$this->pn_buddyboss_send_notification($notification, $sender_info, $title, $message, $activity_link);
					}
				}
			}

		}
	}
	public function buddyboss_pn_message_notifications($message){
		$settings = push_notification_settings();
		if (isset($settings['pn_buddyboss_compatibale']) && $settings['pn_buddyboss_compatibale'] && ( is_plugin_active('buddyboss-platform/bp-loader.php') || is_plugin_active('buddypress/bp-loader.php') ) ) {

			if (empty($message->thread_id) || empty($message->sender_id) || empty($message->message)) {
				return;
			}
	
			$recipients = $message->recipients;
			if (empty($recipients)) {
				return;
			}
			$sender_id = intval($message->sender_id);
	
			foreach ($recipients as $receiver_id => $recipient_obj) {
				if ($receiver_id === $sender_id) {
					continue; // Skip notifying the sender
				}
	
				$notification = get_user_meta($receiver_id, 'buddyboss_pn_notification_token_id', true);
	
				if (!empty($notification)) {
					$sender_info = get_userdata($sender_id);
					// translators: %s is the sender's display name
					$title = sprintf(
						//phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
						esc_html__('%s sent you a message', 'push-notification'),
						esc_html($sender_info->display_name)
					);
	
					$body = wp_strip_all_tags($message->message);

					$link = bp_core_get_user_domain($receiver_id) . 'messages/view/' . $message->thread_id . '/';
					$body = wp_trim_words($body, 20, '...');

                    $this->pn_buddyboss_send_notification($notification, $sender_info, $title, $body, $link);
                }
            }
        }
	}
	public function buddyboss_pn_invitation_notifications($activity){
		$settings = push_notification_settings();
		if (isset($settings['pn_buddyboss_compatibale']) && $settings['pn_buddyboss_compatibale'] && is_plugin_active('buddyboss-platform/bp-loader.php') ) {
			
			$receiver_id = 	$activity->user_id;
			$notification = get_user_meta($receiver_id, 'buddyboss_pn_notification_token_id', true);

			if(! empty( $notification ) ) {

				$sender_info = get_userdata($activity->inviter_id);

				$title	 	= esc_html__('Group invitation', 'push-notification' );
				$message	 	= $sender_info->display_name .esc_html__(' invited you to join group', 'push-notification' );

				$this->pn_buddyboss_send_notification($notification,$sender_info,$title,$message);
			}
		}
	}
	public function buddyboss_pn_friend_request($friendship_id, $friendship_initiator_user_id, $friendship_friend_user_id, $friendship){
		$settings = push_notification_settings();
		if (isset($settings['pn_buddyboss_compatibale']) && $settings['pn_buddyboss_compatibale'] && is_plugin_active('buddyboss-platform/bp-loader.php') ) {
			
			$receiver_id = 	$friendship_friend_user_id;
			$notification = get_user_meta($receiver_id, 'buddyboss_pn_notification_token_id', true);

			if(! empty( $notification ) ) {

				$sender_info = get_userdata($friendship_initiator_user_id);

				$title	 	= esc_html__('New Friend Request', 'push-notification' );
				$message	 	= $sender_info->display_name .esc_html__(' sent friend request', 'push-notification' );

				$this->pn_buddyboss_send_notification($notification,$sender_info,$title,$message);
			}
		}
	}
	public function buddyboss_pn_friend_request_accepted($friendship_id, $friendship_initiator_user_id, $friendship_friend_user_id, $friendship){
		$settings = push_notification_settings();
		if (isset($settings['pn_buddyboss_compatibale']) && $settings['pn_buddyboss_compatibale'] && is_plugin_active('buddyboss-platform/bp-loader.php') ) {
			
			$receiver_id = 	$friendship_initiator_user_id;
			$notification = get_user_meta($receiver_id, 'buddyboss_pn_notification_token_id', true);

			if(! empty( $notification ) ) {

				$sender_info = get_userdata($friendship_friend_user_id);

				$title	 	= esc_html__('Friend Request Accepted', 'push-notification' );
				$message	 	= $sender_info->display_name .esc_html__(' accepted friend request', 'push-notification' );

				$this->pn_buddyboss_send_notification($notification,$sender_info,$title,$message);
			}
		}
	}
	
	public function buddyboss_pn_group_activity_notification($activity) {
		if (
			!isset($activity->component) ||
			$activity->component !== 'groups' ||
			$activity->type !== 'activity_update'
		) {
			return;
		}
	
		$settings = push_notification_settings();
	
		if (
			isset($settings['pn_buddyboss_compatibale']) &&
			$settings['pn_buddyboss_compatibale'] &&
			(is_plugin_active('buddyboss-platform/bp-loader.php') || is_plugin_active('buddypress/bp-loader.php'))
		) {
			$group_id = $activity->item_id;
			$group = groups_get_group(array('group_id' => $group_id));
	
			if (!$group || empty($group->id)) {
				return;
			}
	
			$members = groups_get_group_members(array(
				'group_id' => $group_id,
				//phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'exclude' => array($activity->user_id),
			));
	
			if (!empty($members['members'])) {
				foreach ($members['members'] as $member) {
					$receiver_id = $member->ID;
					$notification = get_user_meta($receiver_id, 'buddyboss_pn_notification_token_id', true);
	
					if (!empty($notification)) {
						$sender_info = get_userdata($activity->user_id);
						// translators: %1$s is the sender's display name, %2$s is the group name
						$title = sprintf(
							//phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
							esc_html__('%1$s posted in %2$s', 'push-notification'),
							esc_html($sender_info->display_name),
							esc_html($group->name)
						);
	
						$message = wp_strip_all_tags($activity->content);
						$message = wp_trim_words($message, 20, '...');
						$activity_link = $this->get_activity_link($activity);
	
						$this->pn_buddyboss_send_notification($notification, $sender_info, $title, $message, $activity_link);
					}
				}
			}
		}
	}
	
	
	public function peepso_pn_tokenid_registration_id($token_id, $response, $user_agent, $os, $ip_address){
		$settings = push_notification_settings();

		if (isset($settings['pn_peepso_compatibale']) && $settings['pn_peepso_compatibale'] && is_plugin_active('peepso/peepso.php') ) {

			$userData = wp_get_current_user();
			if(isset($userData->ID)){
				$userid = $userData->ID;

				$device_tokens = get_user_meta($userid, 'peepso_pn_notification_token_id', true);

				if ( isset($response['data']['id']) && !empty( $response['data']['id'] )) {
					if (!is_array($device_tokens)) {
					    $device_tokens = [];
					}

					$new_token = $response['data']['id'];

					if (!in_array($new_token, $device_tokens)) {
					    $device_tokens[] = $new_token;
					}
					$device_tokens = array_slice(array_unique($device_tokens), -5);
					update_user_meta($userid, 'peepso_pn_notification_token_id', $device_tokens);
				}
			}

		}
	}

	public function gravity_pn_tokenid_registration_id($token_id, $response, $user_agent, $os, $ip_address){
		$settings = push_notification_settings();

		if (isset($settings['pn_gravity_compatibale']) && $settings['pn_gravity_compatibale'] && is_plugin_active('gravityforms/gravityforms.php') ) {

			$userData = wp_get_current_user();
			if(isset($userData->ID)){
				$userid = $userData->ID;

				$device_tokens = get_user_meta($userid, 'gravity_pn_notification_token_id', true);

				if ( isset($response['data']['id']) && !empty( $response['data']['id'] )) {
					if (!is_array($device_tokens)) {
					    $device_tokens = [];
					}

					$new_token = $response['data']['id'];

					if (!in_array($new_token, $device_tokens)) {
					    $device_tokens[] = $new_token;
					}
					$device_tokens = array_slice(array_unique($device_tokens), -5);
					update_user_meta($userid, 'gravity_pn_notification_token_id', $device_tokens);
				}
			}

		}
	}
	public function fluent_community_pn_tokenid_registration_id($token_id, $response, $user_agent, $os, $ip_address){
		$settings = push_notification_settings();

		if (isset($settings['pn_fluent_community_compatibale']) && $settings['pn_fluent_community_compatibale'] && is_plugin_active('fluent-community/fluent-community.php') ) {

			$userData = wp_get_current_user();
			if(isset($userData->ID)){
				$userid = $userData->ID;

				$device_tokens = get_user_meta($userid, 'fluent_community_pn_notification_token_id', true);

				if ( isset($response['data']['id']) && !empty( $response['data']['id'] )) {
					if (!is_array($device_tokens)) {
					    $device_tokens = [];
					}

					$new_token = $response['data']['id'];

					if (!in_array($new_token, $device_tokens)) {
					    $device_tokens[] = $new_token;
					}
					$device_tokens = array_slice(array_unique($device_tokens), -5);
					update_user_meta($userid, 'fluent_community_pn_notification_token_id', $device_tokens);
				}
			}

		}
	}
	public function send_pn_on_gravity_form_saved($form, $is_new){
		$settings = push_notification_settings();
		if (isset($settings['pn_gravity_compatibale']) && $settings['pn_gravity_compatibale'] && is_plugin_active('gravityforms/gravityforms.php') ) {
			$userData = wp_get_current_user();
			if(isset($userData->ID)){
				$userid = $userData->ID;

				$receiver_id = 	$userid;
				$notification = get_user_meta($receiver_id, 'gravity_pn_notification_token_id', true);

				if(! empty( $notification ) ) {

					$sender_info = get_userdata($receiver_id);

					$title	 	= esc_html__('Gravity Form Saved', 'push-notification' );
					$message	 	= esc_html__('Gravity form saved', 'push-notification' );

					$this->pn_gravity_send_notification($notification,$sender_info,$title,$message);
				}
			}
		}
	}
	public function pn_notify_on_fc_feed_created($feed){
		$settings = push_notification_settings();
		if (isset($settings['pn_fluent_community_compatibale']) && $settings['pn_fluent_community_compatibale'] && is_plugin_active('fluent-community/fluent-community.php') ) {
			$userData = wp_get_current_user();
			if(isset($userData->ID)){
				$userid = $userData->ID;

				$receiver_id = 	$feed->user_id;
				$notification = get_user_meta($receiver_id, 'fluent_community_pn_notification_token_id', true);

				if(! empty( $notification ) ) {

					$sender_info = get_userdata($receiver_id);

					$title	 	= esc_html__('New Feed Created', 'push-notification' );
					$message	 	= esc_html__('New Feed Created', 'push-notification' );

					$this->pn_fluent_community_send_notification($notification,$sender_info,$title,$message);
				}
			}
		}
	}
	public function pn_notify_on_fc_new_comment($comment, $feed){
		$settings = push_notification_settings();
		
		if (isset($settings['pn_fluent_community_compatibale']) && $settings['pn_fluent_community_compatibale'] && is_plugin_active('fluent-community/fluent-community.php') ) {
			$userData = wp_get_current_user();
			if(isset($userData->ID)){
				$receiver_id = $feed->user_id;

				$notification = get_user_meta($receiver_id, 'fluent_community_pn_notification_token_id', true);
				if(! empty( $notification ) ) {
					$sender_info = get_userdata($comment->user_id);
					$title	 	= $sender_info->display_name.' '.esc_html__('commented on feed', 'push-notification' );
					$message 	= esc_html( $comment->message );
					$this->pn_fluent_community_send_notification($notification,$sender_info,$title,$message);
				}
			}
		}
	}
	public function pn_notify_on_fc_react_added($react, $feed){
		$settings = push_notification_settings();
		
		if (isset($settings['pn_fluent_community_compatibale']) && $settings['pn_fluent_community_compatibale'] && is_plugin_active('fluent-community/fluent-community.php') ) {
			$userData = wp_get_current_user();
			if(isset($userData->ID)){
				$receiver_id = $feed->user_id;

				$notification = get_user_meta($receiver_id, 'fluent_community_pn_notification_token_id', true);
				if(! empty( $notification ) ) {
					$sender_info = get_userdata($comment->user_id);
					$title	 	= $sender_info->display_name.' '.esc_html__('reacted on feed', 'push-notification' );
					$message 	= esc_html__('reacted on feed', 'push-notification' );
					$this->pn_fluent_community_send_notification($notification,$sender_info,$title,$message);
				}
			}
		}
	}

	public function pn_handle_error_log($remoteResponse, $function_name) {
		if( is_wp_error( $remoteResponse ) ){
			$remoteData = array('status'=>401, "response"=>"pn could not connect to server");
		}else{
			$remoteData = wp_remote_retrieve_body($remoteResponse);
			$remoteData = json_decode($remoteData, true);
		}
	}

	function pn_display_status($settings){
		if(isset($settings['include_targeting_data']) && isset($settings['include_targeting_type']) && !empty($settings['include_targeting_type'])){
			$expo_include_type = array();
			$expo_include_data = array();
	
			if (!empty($settings['include_targeting_type'])) {
				$expo_include_type = $settings['include_targeting_type'];
			}
			if (!empty($settings['include_targeting_data'])) {
				$expo_include_data = $settings['include_targeting_data'];
			}

			$current_page_type = '';
			if(is_singular() && !is_front_page()){
				$current_page_type = get_post_type();
			}

			$current_page_title = single_post_title("",false);

			if ( empty($current_page_type) ) {
				global $wp;
				$slug = $wp->request;
				$q = get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) );
				if ( ! empty($q) && isset($q->post_type) ) {
					$current_page_type = $q->post_type;
					$current_page_title = $q->post_title;
				}
			}
	
			$is_desplay = 0;
	
			if(!empty(get_the_category()[0]->cat_name)){
				if(in_array(get_the_category()[0]->cat_name,$expo_include_data)){
					$current_page_type= 'post_category';
					$current_page_title =  get_the_category()[0]->cat_name;
				}
			}

			if(in_array('tags',$expo_include_type)){
				$tag = get_queried_object();
				if(in_array($tag->name,$expo_include_data)){
					$current_page_title =  $tag->name;
					$current_page_type = 'tags';
				}
			}
	
			if(in_array('taxonomy',$expo_include_type)){
				$tag = get_queried_object();
				if(in_array($tag->name,$expo_include_data)){
					$current_page_title =  $tag->name;
					$current_page_type = 'taxonomy';
				}
			}
	
			if(in_array('page_template',$expo_include_type)){
				$page_template = wp_get_theme()->get_page_templates();
				if(!empty($page_template) && is_array($page_template)){
				foreach ($page_template as $key => $value) {
					if(in_array($value,$expo_include_data)){
						$current_page_title =  $value;
						$current_page_type = 'page_template';
					}
				}}
			}
	
			if(function_exists('is_user_logged_in') && is_user_logged_in() ) {
				$user = wp_get_current_user();
				if(in_array($user->roles,$expo_include_data)){
					$current_page_title =  $user->roles;
					$current_page_type = 'user_type';
				}
			}
			if(in_array($current_page_type,$expo_include_type)){
				$find_from = $this->pn_visibility_data_by_type($current_page_type,'including');
				if (in_array($current_page_title, $find_from)) {
					$is_desplay = 1;
				}
			}
	
			if (in_array('post_type',$expo_include_type)) {
				$find_from = $this->pn_visibility_data_by_type('post_type','including');
				if(in_array($current_page_type,$find_from)){
					$is_desplay = 1;
				}
			}
	
			if(in_array('globally',$expo_include_type)){
				$is_desplay = 1; 
			}
			return $is_desplay;
		}
		return 1;
	}

	function pn_visibility_data_by_type($type,$from){
		$response_array = array();
		$settings = push_notification_settings();
		if(isset($settings['include_targeting_data']) && isset($settings['include_targeting_type']) && !empty($settings['include_targeting_type'])){
			$expo_include_type = array();
			$expo_include_data = array();
	
			if (!empty($settings['include_targeting_type'])) {
				$expo_include_type = $settings['include_targeting_type'];
			}
			if (!empty($settings['include_targeting_data'])) {
				$expo_include_data = $settings['include_targeting_data'];
			}
				
			foreach ($expo_include_type as $key => $value) {
				if ($value == $type) {
					$response_array[$key] = $expo_include_data[$key];
				}
			}
		}
		return $response_array;
	}

	public function get_activity_link($activity) {
		$link = home_url(); // fallback
		$anchor = '#activity-' . $activity->id;
	
		// Get user domain
		$user_domain = bp_core_get_user_domain($activity->user_id);
	
		switch ($activity->component) {
			case 'groups':
				$group = groups_get_group(['group_id' => $activity->item_id]);
	
				if (!empty($group->slug)) {
					$base = trailingslashit(bp_get_groups_directory_permalink() . $group->slug);
					$link = $base . (strpos($base, $anchor) === false ? $anchor : '');
				}
				break;
	
			case 'activity':
				$base = trailingslashit($user_domain . bp_get_activity_slug());
				$link = $base . (strpos($base, $anchor) === false ? $anchor : '');
				break;
	
			case 'friends':
				$link = $user_domain;
				break;
	
			case 'blogs':
			case 'forums':
				if (!empty($activity->primary_link)) {
					$link = $activity->primary_link;
					if (strpos($link, '#') === false) {
						$link .= $anchor; // append only if no hash
					}
				}
				break;
	
			default:
				if (!empty($activity->primary_link)) {
					$link = $activity->primary_link;
					if (strpos($link, '#') === false) {
						$link .= $anchor;
					}
				}
				break;
		}
	
		return esc_url($link);
	}
	
}
function push_notification_frontend_class(){
	if(!is_admin() || wp_doing_ajax()){
		$notificationFrontEnd = new Push_Notification_Frontend(); 
	}
	add_filter( "option_autoptimize_js_exclude", array('Push_Notification_Frontend', 'update_autoptimize_exclude') , 10, 2);
}
push_notification_frontend_class();
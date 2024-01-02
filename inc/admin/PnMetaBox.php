<?php 
/*
 *  Metabox displays in admin sidebar to send notification on particular post
 */
class PnMetaBox {
    
	private $screen = array(
		'post',
	);
	private $meta_fields = array(
		array(
			'label' => 'Send notification on post?',
			'id'    => 'pn_send_notification_on_post',
			'type'  => 'checkbox',
		),
	);
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'pn_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'pn_save_fields' ) );
	}
	public function pn_add_meta_boxes() {
		foreach ( $this->screen as $single_screen ) {
			add_meta_box(
				'send_push_on_current_post',
				esc_html__( 'Send push notification on current post?', 'push-notification' ),
				array( $this, 'pn_meta_box_callback' ),
				$single_screen,
				'side',
				'low'
			);
		}
	}
	public function pn_meta_box_callback( $post ) {
		wp_nonce_field( 'set_send_push_notification_data', 'set_send_push_notification_nonce' );
		$this->pn_field_generator( $post );
	}
	public function pn_field_generator( $post ) {
		$output = '';                
		foreach ( $this->meta_fields as $meta_field ) {			
			$meta_value = get_post_meta( $post->ID, $meta_field['id'], true );                        
			if ( empty( $meta_value ) ) {
				$meta_value = isset($meta_field['default']); 
                                if(empty($meta_value)){
                               $meta_value ='show';   
                               }
                          }
			switch ( $meta_field['type'] ) {
                case 'checkbox':
					$input = sprintf(
						'<input %s id="%s" name="% s" type="checkbox" value="1">',
						$meta_value === '1' ? 'checked' : '',
						esc_attr($meta_field['id']),
						esc_attr($meta_field['id'])
						);
					break;
				default:
					$input = sprintf(
						'<input %s id="%s" name="%s" type="%s" value="%s">',
						$meta_field['type'] !== 'color' ? 'style="width: 100%"' : '',
						esc_attr($meta_field['id']),
						esc_attr($meta_field['id']),
						$meta_field['type'],
						$meta_value
					);
			}
			$output .= $this->pn_format_rows($input );
		}
		echo '<table class="form-table"><tbody>' . $output . '</tbody></table>';
	}
	public function pn_format_rows($input) {
		return '<tr><td style="padding:0px;">'.$input.'</td></tr>';
	}
	public function pn_save_fields( $post_id ) {
            
		if ( ! isset( $_POST['set_send_push_notification_nonce'] ) )
			return $post_id;		
		if ( !wp_verify_nonce( $_POST['set_send_push_notification_nonce'], 'set_send_push_notification_data' ) )
			return $post_id;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;
        if ( current_user_can( 'manage_options' ) ) {
            $post_meta = array();                    
            $post_meta = $_POST;  // Sanitized below before saving                
			foreach ( $this->meta_fields as $meta_field ) {
				if ( isset( $post_meta[ $meta_field['id'] ] ) ) {
					switch ( $meta_field['type'] ) {
						case 'email':
							$post_meta[ $meta_field['id'] ] = sanitize_email( $post_meta[ $meta_field['id'] ] );
							break;
						case 'text':
							$post_meta[ $meta_field['id'] ] = sanitize_text_field( $post_meta[ $meta_field['id']]);
							break;
                        default:     
                        	$post_meta[ $meta_field['id'] ] = sanitize_text_field( $post_meta[ $meta_field['id']]);
					}
					update_post_meta( $post_id, $meta_field['id'], $post_meta[ $meta_field['id'] ] );
				} else if ( $meta_field['type'] === 'checkbox' ) {
					update_post_meta( $post_id, $meta_field['id'], '0' );
				}
			}
        }
	}
}
if (class_exists('PnMetaBox')) {
	new PnMetaBox;
};
<?php


// Exit if accessed directly
if( !defined( 'ABSPATH' ) )
    exit;

/**
 * Helper method to check if user is in the plugins page.
 *
 * @author 
 * @since  1.4.0
 *
 * @return bool
 */
function pn_is_plugins_page() {

    if ( function_exists( 'get_current_screen' ) ) {

        $screen = get_current_screen();

            if ( is_object( $screen ) ) {

                if ( $screen->id == 'plugins' || $screen->id == 'plugins-network' ) {
                    return true;
                }
            }
    }

    return false;

}

/**
 * display deactivation logic on plugins page
 * 
 * @since 1.4.0
 */


function pn_add_deactivation_feedback_modal() {

    if ( is_admin() && pn_is_plugins_page() ) {

        $current_user = wp_get_current_user();
        if ( ! ( $current_user instanceof WP_User ) ) {
            $email = '';
        } else {
            $email = trim( $current_user->user_email );
        }

        require_once PUSH_NOTIFICATION_PLUGIN_DIR."inc/admin/deactivate-feedback.php";

    }    
    
}

/**
 * send feedback via email
 * 
 * @since 1.4.0
 */
function pn_send_feedback() {
    

    if( isset( $_POST['data'] ) ) {
        //phpcs:ignore  WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        parse_str( $_POST['data'], $form );
    }
    if(isset($form['nonce'])){

        if ( ! wp_verify_nonce( $form['nonce'], 'push_notification_feedback' ) ) {
            wp_die(); 
        }

        $text = '';
        if( isset( $form['pn_disable_text'] ) ) {
            $text = implode( "\n\r", $form['pn_disable_text'] );
        }

        $headers = array();

        $from = isset( $form['pn_disable_from'] ) ? $form['pn_disable_from'] : '';
        if( $from ) {
            $headers[] = "From: $from";
            $headers[] = "Reply-To: $from";
        }

        $subject = isset( $form['pn_disable_reason'] ) ? $form['pn_disable_reason'] : '(no reason given)';

        $subject = $subject.' - Push Notifications';

        if($subject == 'technical - Push Notifications'){

            $text = trim($text);

            if(!empty($text)){

                $text = 'technical issue description: '.$text;

            }else{

                $text = 'no description: '.$text;
            }
        
        }

        $success = wp_mail( 'team@magazine3.in', $subject, $text, $headers );

    }

    wp_die();
}

add_action( 'wp_ajax_pn_send_feedback', 'pn_send_feedback' );

add_action( 'admin_enqueue_scripts', 'pn_enqueue_makebetter_email_js' );

function pn_enqueue_makebetter_email_js() {
 
    if ( is_admin() && pn_is_plugins_page() ) {

        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';	

        wp_enqueue_script( 'pn-make-better-js', PUSH_NOTIFICATION_PLUGIN_URL . "assets/feedback-admin{$min}.js", array( 'jquery' ), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
        wp_enqueue_style( 'pn-make-better-css', PUSH_NOTIFICATION_PLUGIN_URL . "assets/feedback-admin{$min}.css", false , PUSH_NOTIFICATION_PLUGIN_VERSION);

    }
    
}

add_filter( 'admin_footer', 'pn_add_deactivation_feedback_modal' );
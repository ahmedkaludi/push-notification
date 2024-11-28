<?php 
// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

class Push_Notification_Newsletter {
        
	function __construct () {
		add_filter( 'pushnotification_localize_filter',array($this,'add_localize_footer_data'),10,2);
	}
	        
    function add_localize_footer_data($object, $object_name){
            
        $dismissed = explode (',', get_user_meta (wp_get_current_user ()->ID, 'dismissed_wp_pointers', true));                                
        $do_tour   = !in_array ('pushnotification_subscribe_pointer', $dismissed); 
        
        if ($do_tour) {
            wp_enqueue_style ('wp-pointer');
            wp_enqueue_script ('wp-pointer');						
        }
                        
        if($object_name == 'pn_setings'){
                        
                global $current_user;                
		        $tour     = array ();
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $tab      = isset($_GET['tab']) ? esc_attr($_GET['tab']) : '';                   
                
                if (!array_key_exists($tab, $tour)) {                
			                                           			            	
                        $object['do_tour']            = $do_tour;        
                        $object['get_home_url']       = get_home_url();                
                        $object['current_user_email'] = esc_attr($current_user->user_email);
                        $object['current_user_name']  = esc_attr($current_user->display_name);
			            $object['displayID']          = '#toplevel_page_push-notification';
                        $object['button1']            = esc_html__('No Thanks', 'push-notification');
                        $object['button2']            = false;
                        $object['function_name']      = '';
		}
		                                                                                                                                                    
        }
        return $object;
         
    }
       
}
$push_notification_newsletter = new Push_Notification_Newsletter();
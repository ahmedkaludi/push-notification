<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Push_Notification_Frontend{
	public function __construct(){
		$this->init();
	}

	public function init(){
		//manifest
		add_action('wp_head',array($this, 'manifest_add_homescreen'),1);
		//create manifest
		add_action( 'rest_api_init', array( $this, 'register_manifest_rest_route' ) );

		//ServiceWorker
		add_action("wp_enqueue_scripts", array($this, 'enqueue_pn_scripts') );
		//firebase serviceworker
		add_action( 'init', array($this, 'sw_template_query_var') );
		add_action( 'parse_query', array($this, 'load_service_worker') );

		add_action( 'wp_ajax_pn_register_subscribers', array( $this, 'pn_register_subscribers' ) ); 
		add_action( 'wp_ajax_nopriv_pn_register_subscribers', array( $this, 'pn_register_subscribers' ) );
	}
	function sw_template_query_var(){
		global $wp;
		 $wp->add_query_var( 'push_notification_sw' );
	}

	function load_service_worker(WP_Query $query ){
		if ( $query->is_main_query() && $query->get( 'push_notification_sw' ) ) {
			//header("Service-Worker-Allowed: /");
			header("Content-Type: application/javascript");
			header('Accept-Ranges: bytes');
			$messageSw = $this->pn_get_layout_files('messaging-sw.js');
			echo $messageSw;
                exit;
		}

	}

	public function pn_get_layout_files($filePath){
	    $fileContentResponse = @wp_remote_get(esc_url_raw(PUSH_NOTIFICATION_PLUGIN_URL.'/assets/'.$filePath));
	    if(wp_remote_retrieve_response_code($fileContentResponse)!=200){
	      if(!function_exists('get_filesystem_method')){
	        require_once( ABSPATH . 'wp-admin/includes/file.php' );
	      }
	      $access_type = get_filesystem_method();
	      if($access_type === 'direct')
	      {
	      	$file = sanitize_file_name(PUSH_NOTIFICATION_PLUGIN_DIR.$filePath);
	         $creds = request_filesystem_credentials(, '', false, false, array());
	        if ( ! WP_Filesystem($creds) ) {
	          return false;
	        }   
	        global $wp_filesystem;
	        $htmlContentbody = $wp_filesystem->get_contents($file);
	        return $htmlContentbody;
	      }
	      return false;
	    }else{
	      return wp_remote_retrieve_body( $fileContentResponse );
	    }
	}


	public function enqueue_pn_scripts(){
		wp_enqueue_script('pn-script-app-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'/assets/public/application.min.js', array(), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		wp_enqueue_script('pn-script-messaging-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'/assets/public/messaging.min.js', array(), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		wp_enqueue_script('pn-script-frontend', PUSH_NOTIFICATION_PLUGIN_URL.'/assets/public/app.js', array(), PUSH_NOTIFICATION_PLUGIN_VERSION, true);
		if ( is_multisite() ) {
            $link = get_site_url();              
        }
        else {
            $link = home_url();
        }    
		$settings = array(
					'nonce' =>  wp_create_nonce("pn_notification"),
					'pn_config'=> array(
							  "apiKey"=> "AIzaSyCt8RVdgpFPaoTmzx84gAgi6zzVCpGlnZg",
							  "authDomain"=> "fir-pushnotification-1940a.firebaseapp.com",
							  "databaseURL"=> "https://fir-pushnotification-1940a.firebaseio.com",
							  "projectId"=> "fir-pushnotification-1940a",
							  "storageBucket"=> "fir-pushnotification-1940a.appspot.com",
							  "messagingSenderId"=> "1231518440",
							  "appId"=> "1:1231518440:web:9efeed716a5da8341aa75d"
							),
					"swsource" => esc_url_raw(trailingslashit($link)."?push_notification_sw=1"),
					"scope" => esc_url_raw(trailingslashit($link)),
					"ajax_url"=> esc_url_raw(admin_url('admin-ajax.php'))
					);
		wp_localize_script('pn-script-frontend', 'pnScriptSetting', $settings);
	}

	public function manifest_add_homescreen(){
		echo '<link rel="manifest" href="'. esc_url( rest_url( 'push-notification/v2/pn-manifest-json' ) ).'">';
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
    	$array = array("gcm_sender_id"=> "103953800507");
        return $array;
    }  

    public function pn_register_subscribers(){
		$nonce = sanitize_text_field($_POST['nonce']);
		if( !wp_verify_nonce($nonce, 'pn_notification') ){
			echo json_encode(array("status"=> 503, 'message'=>'Request not authorized'));die;
		}else{
			$token_id = sanitize_text_field($_POST['token_id']);
			$user_agent = sanitize_text_field($_POST['user_agent']);
			$os = sanitize_text_field($_POST['os']);
			$ip_address = $this->get_the_user_ip();
			if(empty($token_id)){
				echo json_encode(array("status"=> 503, 'message'=>'token_id is blank'));die;
			}
			if(empty($user_agent)){
				echo json_encode(array("status"=> 503, 'message'=>'user_agent is blank'));die;
			}
			if(empty($os)){
				echo json_encode(array("status"=> 503, 'message'=>'os is blank'));die;
			}
			$response = PN_Server_Request::registerSubscribers($token_id, $user_agent, $os, $ip_address);
			echo json_encode($response);die;
		}
	}


	public function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			//check ip from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	} 

}
add_action("plugins_loaded", 'push_notification_frontend_class');
function push_notification_frontend_class(){
	if(!is_admin() || wp_doing_ajax()){
		$notificationFrontEnd = new Push_Notification_Frontend(); 
	}

}
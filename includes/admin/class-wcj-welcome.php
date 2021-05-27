<?php
/**
 * Booster getting started
 *
 * @version 5.4.1
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Welcome' ) ) :

class WCJ_Welcome {
	/**
	 * Constructor.
	 */
	function __construct() {
		if ( is_admin() ) {
			
			if(isset( $_GET['page'] ) && $_GET['page'] == "jetpack-getting-started" ){
				add_action('in_admin_header', function () {
		            remove_all_actions('admin_notices');
		            remove_all_actions('all_admin_notices');
		        }, 1);
			}
			
			add_action( 'admin_init', array( $this, 'wcj_redirect_to_getting_started' ), 10 );
			add_action( 'admin_menu', array( $this, 'wcj_register_welcome_page' ) );
			add_action( 'network_admin_menu', array( $this, 'wcj_register_welcome_page' ) );
			add_action( 'admin_head', array( $this, 'wcj_hide_menu' ) );

			if(isset($_POST['submit_email_to_klaviyo']) && $_POST['user_email'] != "" ){
				$API_KEY         = "pk_6e2f40d8614c17a121a4d2c567d2bd72d4"; 
		        $list_id         = "RQJNvK";
		        $email           = $_POST['user_email']; 
		        $check_subscribe = $this->check_email_exist_in_klaviyo_subscribe_list( $list_id, $email );
		        // Subscribe to List
		        if( $check_subscribe == 0 ) {
		            $response = $this->add_email_to_klaviyo_subscribe_list( $list_id, $email );
                    $redirect = admin_url( 'index.php?page=jetpack-getting-started&wcj-redirect=1&msg=1' );
		            set_transient( '_wcj_redirect_to_getting_started_msg', 1, 30 );
                    add_action( 'admin_init', array( $this, 'wcj_redirect_to_getting_started_msg' ), 10);
		        }
		        else {
		        	set_transient( '_wcj_redirect_to_getting_started_msg', 2, 30 );
                    add_action( 'admin_init', array( $this, 'wcj_redirect_to_getting_started_msg' ), 10);
		        }
			}

		}
	}

    /**
	 * wcj_register_welcome_page
	 *
	 * @version 5.4.1
	 */
	public function wcj_register_welcome_page() {
		add_dashboard_page(
			esc_html__( 'Welcome to Booster', 'woocommerce-jetpack' ),
			esc_html__( 'Welcome to Booster', 'woocommerce-jetpack' ),
			apply_filters( 'wcj_welcome_screen_filter', 'manage_options' ),
			'jetpack-getting-started',
			array( $this, 'wcj_welcome_screen_content' )
		);
	}

    /**
	 * wcj_redirect_to_getting_started_msg
	 *
	 * @version 5.4.1
	 */
	public function wcj_redirect_to_getting_started_msg() {
		$msg = get_transient( '_wcj_redirect_to_getting_started_msg' );
		delete_transient( '_wcj_redirect_to_getting_started_msg' );
		$redirect = admin_url( 'index.php?page=jetpack-getting-started&wcj-redirect=1&msg='.$msg.'/#subscribe-email' );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * wcj_hide_menu
	 *
	 * @version 5.4.1
	 */
	public function wcj_hide_menu() {
		remove_submenu_page( 'index.php', 'jetpack-getting-started' );
	}

	/**
	 * wcj_redirect_to_getting_started
	 *
	 * @version 5.4.1
	 */
	public function wcj_redirect_to_getting_started() {
		if ( ! get_transient( '_wcj_activation_redirect' ) || isset( $_GET['wcj-redirect'] ) ) {
			return;
		}

		delete_transient( '_wcj_activation_redirect' );
		
		$redirect = admin_url( 'index.php?page=jetpack-getting-started&wcj-redirect=1' );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * wcj_welcome_screen_content
	 * 
	 * @version 5.4.1
	 */
	public function wcj_welcome_screen_content() {
		require_once( WCJ_PLUGIN_PATH . '/includes/admin/wcj-welcome-screen-content.php' );
	}

    /**
	 * check_email_exist_in_klaviyo_subscribe_list
	 * 
	 * @version 5.4.1
	 */
	public function check_email_exist_in_klaviyo_subscribe_list($list_id,$email){
		$API_KEY        = "pk_6e2f40d8614c17a121a4d2c567d2bd72d4";  
	    $data_to_post   = "?api_key=".$API_KEY."&emails=".$email;
	    $URL            = "https://a.klaviyo.com/api/v2/list/".$list_id."/subscribe".$data_to_post;
	    $curlSession    = curl_init();    
	    curl_setopt($curlSession, CURLOPT_URL, $URL);
	    curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
	    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
	    $CheckListSubscriptionsResponse = json_decode(curl_exec($curlSession), true);
	    $CheckListSubscriptions = count($CheckListSubscriptionsResponse);
	    curl_close($curlSession);
	    return $CheckListSubscriptions;
	}

    /**
	 * add_email_to_klaviyo_subscribe_list
	 * 
	 * @version 5.4.1
	 */
	public function add_email_to_klaviyo_subscribe_list($list_id,$email){
		$API_KEY            = "pk_6e2f40d8614c17a121a4d2c567d2bd72d4"; 
		$URL = "https://a.klaviyo.com/api/v2/list/".$list_id."/subscribe";
		$subscribe_to_plan  = array( 
		    "api_key"       => $API_KEY,
		    "profiles"      => array(
		        "email"     => $email
		    )
		);
		$subscribe_to_plan  = json_encode($subscribe_to_plan);	
		$curlSession        = curl_init();
		curl_setopt($curlSession, CURLOPT_URL, $URL);
		curl_setopt($curlSession, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($curlSession, CURLOPT_POST, 1);
		curl_setopt($curlSession, CURLOPT_POSTFIELDS,$subscribe_to_plan);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
		$subscribeToListResponse = json_decode(curl_exec($curlSession), true);
		curl_close($curlSession);
		return $subscribeToListResponse;
	}
}

endif;

return new WCJ_Welcome();
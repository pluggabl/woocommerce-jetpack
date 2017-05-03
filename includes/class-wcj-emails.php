<?php
/**
 * WooCommerce Jetpack Emails
 *
 * The WooCommerce Jetpack Emails class.
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Emails' ) ) :

class WCJ_Emails extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'emails';
		$this->short_desc = __( 'Emails', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom emails. Add another email recipient(s) to all WooCommerce emails.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-emails/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Custom Emails
			add_filter( 'woocommerce_email_actions', array( $this, 'add_custom_woocommerce_email_actions' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'add_custom_emails_to_wc' ) );
			add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'add_custom_emails_to_wc_resend_order_emails' ) );
			// Email Forwarding
			if ( '' != get_option( 'wcj_emails_bcc_email' ) ) {
				add_filter( 'woocommerce_email_headers', array( $this, 'add_bcc_email' ) );
			}
			if ( '' != get_option( 'wcj_emails_cc_email' ) ) {
				add_filter( 'woocommerce_email_headers', array( $this, 'add_cc_email' ) );
			}
			// Product Info
			if ( 'yes' === get_option( 'wcj_product_info_in_email_order_item_name_enabled', 'no' ) ) {
				add_filter( 'woocommerce_order_item_name', array( $this, 'add_product_info_to_email_order_item_name' ), PHP_INT_MAX, 2 );
			}
			// Email Verification
			if ( 'yes' === get_option( 'wcj_emails_verification_enabled', 'no' ) ) { // idea from ticket #4752
				add_action( 'init',                              array( $this, 'process_email_verification' ),                      PHP_INT_MAX );
				add_filter( 'woocommerce_registration_redirect', array( $this, 'prevent_user_login_automatically_after_register' ), PHP_INT_MAX );
				add_filter( 'wp_authenticate_user',              array( $this, 'check_if_user_email_is_verified' ),                 PHP_INT_MAX );
				add_action( 'user_register',                     array( $this, 'reset_and_mail_activation_link' ),                  PHP_INT_MAX );
			}
			// Settings
			add_filter( 'woocommerce_email_settings', array( $this, 'add_email_forwarding_fields_to_wc_standard_settings' ), PHP_INT_MAX );
		}
	}

	/**
	 * prevent_user_login_automatically_after_register.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function prevent_user_login_automatically_after_register( $redirect_to ) {
		wp_logout();
		return add_query_arg( 'wcj_activate_account_message', '', $redirect_to );
	}

	/**
	 * check_if_user_email_is_verified.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function check_if_user_email_is_verified( $userdata ) {
		$error_message = do_shortcode( get_option( 'wcj_emails_verification_error_message',
			__( 'Your account has to be activated before you can login. You can resend email with verification link by clicking <a href="%resend_verification_url%">here</a>.', 'woocommerce-jetpack' )
		) );
		$error_message = str_replace( '%resend_verification_url%', add_query_arg( 'wcj_user_id', $userdata->ID, wc_get_page_permalink( 'myaccount' ) ), $error_message );
		if ( ! get_user_meta( $userdata->ID, 'wcj_is_activated', true ) ) {
			$userdata = new WP_Error( 'booster_email_verified_error', $error_message );
		}
		return $userdata;
	}

	/**
	 * reset_and_mail_activation_link.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @todo    %site_name% etc. in `wcj_emails_verification_email_subject`
	 */
	function reset_and_mail_activation_link( $user_id ) {
		$user_info     = get_userdata( $user_id );
		$code          = md5( time() );
		$url           = add_query_arg( 'wcj_verify_email', base64_encode( serialize( array( 'id' => $user_id, 'code' => $code ) ) ), wc_get_page_permalink( 'myaccount' ) );
		$email_content = do_shortcode( get_option( 'wcj_emails_verification_email_content',
			__( 'Please click the following link to verify your email:<br><br><a href="%verification_url%">%verification_url%</a>', 'woocommerce-jetpack' ) ) );
		$email_content = str_replace( '%verification_url%', $url, $email_content );
		$email_subject = do_shortcode( get_option( 'wcj_emails_verification_email_subject',
			__( 'Please activate your account', 'woocommerce-jetpack' ) ) );
		update_user_meta( $user_id, 'wcj_is_activated', 0 );
		update_user_meta( $user_id, 'wcj_activation_code', $code );
		wc_mail( $user_info->user_email, $email_subject, $email_content );
	}

	/**
	 * process_email_verification.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function process_email_verification(){
		if ( isset( $_GET['wcj_verify_email'] ) ) {
			$data = unserialize( base64_decode( $_GET['wcj_verify_email'] ) );
			if ( get_user_meta( $data['id'], 'wcj_activation_code', true ) == $data['code'] ) {
				update_user_meta( $data['id'], 'wcj_is_activated', 1 );
				wc_add_notice( do_shortcode( get_option( 'wcj_emails_verification_success_message',
					__( '<strong>Success:</strong> Your account has been activated!', 'woocommerce-jetpack' ) ) ) );
				if ( 'yes' === get_option( 'wcj_emails_verification_redirect_on_success', 'yes' ) ) {
					wp_set_current_user( $data['id'] );
					wp_set_auth_cookie( $data['id'] );
					header( wc_get_page_permalink( 'myaccount' ) );
				}
			} else {
				wc_add_notice( do_shortcode( get_option( 'wcj_emails_verification_failed_message',
					__( '<strong>Error:</strong> Activation failed, please contact our administrator.', 'woocommerce-jetpack' ), 'error' ) ) );
			}
		}
		if ( isset( $_GET['wcj_activate_account_message'] ) ) {
			wc_add_notice( do_shortcode( get_option( 'wcj_emails_verification_activation_message',
				__( 'Thank you for your registration. Your account has to be activated before you can login. Please check your email.', 'woocommerce-jetpack' ) ) ) );
		}
		if ( isset( $_GET['wcj_user_id'] ) ) {
			$this->reset_and_mail_activation_link( $_GET['wcj_user_id'] );
			wc_add_notice( do_shortcode( get_option( 'wcj_emails_verification_email_resend_message',
				__( '<strong>Success:</strong> Your activation email has been resend. Please check your email.', 'woocommerce-jetpack' ) ) ) );
		}
	}

	/**
	 * add_product_info_to_email_order_item_name.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function add_product_info_to_email_order_item_name( $item_name, $item ) {
		if ( $item['product_id'] ) {
			global $post;
			$post = get_post( $item['product_id'] );
			setup_postdata( $post );
			$item_name .= do_shortcode( get_option( 'wcj_product_info_in_email_order_item_name', '[wcj_product_categories strip_tags="yes" before="<hr><em>" after="</em>"]' ) );
			wp_reset_postdata();
		}
		return $item_name;
	}

	/**
	 * get_order_statuses.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 * @todo    use global wcj function
	 */
	function get_order_statuses() {
		$result = array();
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		foreach( $statuses as $status => $status_name ) {
			$result[ substr( $status, 3 ) ] = $statuses[ $status ];
		}
		return $result;
	}

	/**
	 * add_custom_woocommerce_email_actions.
	 *
	 * @version 2.5.5
	 * @since   2.4.5
	 */
	function add_custom_woocommerce_email_actions( $email_actions ) {
		$email_actions[] = 'woocommerce_checkout_order_processed';
		$order_statuses = $this->get_order_statuses();
		foreach ( $order_statuses as $slug => $name ) {
			$email_actions[] = 'woocommerce_order_status_' . $slug;
			foreach ( $order_statuses as $slug2 => $name2 ) {
				if ( $slug != $slug2 ) {
					$email_actions[] = 'woocommerce_order_status_' . $slug . '_to_' . $slug2;
				}
			}
		}
		return $email_actions;
	}

	/**
	 * add_custom_emails_to_wc_resend_order_emails.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function add_custom_emails_to_wc_resend_order_emails( $emails ) {
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
			$emails[] =  'wcj_custom' . '_' . $i;
		}
		return $emails;
	}

	/**
	 * add_custom_emails_to_wc.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function add_custom_emails_to_wc( $emails ) {
		if ( ! class_exists( 'WC_Email_WCJ_Custom' ) ) {
			require_once( 'emails/class-wc-email-wcj-custom.php' );
		}
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
			$emails[ 'WC_Email_WCJ_Custom_' . $i ] = new WC_Email_WCJ_Custom( $i );
		}
		return $emails;
	}

	 /**
	 * Add another email recipient to all WooCommerce emails
	 */
	function add_bcc_email( $email_headers ) {
		return $email_headers . "Bcc: " . get_option( 'wcj_emails_bcc_email' ) . "\r\n";
	}

	 /**
	 * Add another email recipient to all WooCommerce emails
	 */
	function add_cc_email( $email_headers ) {
		return $email_headers . "Cc: " . get_option( 'wcj_emails_cc_email' ) . "\r\n";
	}

	/**
	 * get_emails_forwarding_settings.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function get_emails_forwarding_settings( $extended_title = false ) {
		return array(
			array(
				'title'    => ( $extended_title ) ?
					__( 'Booster: Email Forwarding Options', 'woocommerce-jetpack' ) :
					__( 'Email Forwarding Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you add another email recipient(s) to all WooCommerce emails. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_forwarding_options',
			),
			array(
				'title'    => __( 'Cc Email', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Cc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_cc_email',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Bcc Email', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Bcc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_bcc_email',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_emails_forwarding_options',
			),
		);
	}

	/**
	 * add_email_forwarding_fields_to_wc_standard_settings.
	 *
	 * @version 2.3.9
	 * @todo    (maybe) remove this completely (and then move `get_emails_forwarding_settings()` to settings file)
	 */
	function add_email_forwarding_fields_to_wc_standard_settings( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'email_template_options' == $section['id'] && isset( $section['type'] ) && 'title' == $section['type'] ) {
				$updated_settings = array_merge( $updated_settings, $this->get_emails_forwarding_settings( true ) );
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

}

endif;

return new WCJ_Emails();

<?php
/**
 * Booster for WooCommerce - Module - Email Verification
 *
 * @version 5.2.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Email_Verification' ) ) :

class WCJ_Email_Verification extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.8.0
	 */
	function __construct() {

		$this->id         = 'emails_verification';
		$this->short_desc = __( 'Email Verification', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add WooCommerce email verification. Customize verification email subject, content and template (Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Add WooCommerce email verification.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-email-verification';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init',                                       array( $this, 'process_email_verification' ),                      PHP_INT_MAX );
			add_filter( 'woocommerce_registration_redirect',          array( $this, 'prevent_user_login_automatically_after_register' ), PHP_INT_MAX );
			add_filter( 'wp_authenticate_user',                       array( $this, 'check_if_user_email_is_verified' ),                 PHP_INT_MAX );
			add_action( 'user_register',                              array( $this, 'reset_and_mail_activation_link' ),                  PHP_INT_MAX );
			add_filter( 'manage_users_columns',                       array( $this, 'add_verified_email_column' ) );
			add_filter( 'manage_users_custom_column',                 array( $this, 'render_verified_email_column' ), 10, 3 );
			add_action( 'wp',                                         array( $this, 'prevent_login' ),                      PHP_INT_MAX );
			add_filter( 'woocommerce_registration_auth_new_customer', array( $this, 'woocommerce_registration_auth_new_customer' ) );
			add_filter( 'authenticate',                               array( $this, 'prevent_authenticate' ), PHP_INT_MAX );
		}
	}

	/**
	 * prevent_login.
	 *
	 * @version 5.2.0
	 * @since   5.2.0
	 */
	function prevent_login() {
		if (
			'yes' !== wcj_get_option( 'wcj_emails_verification_prevent_user_login', 'no' )
			|| is_admin()
			|| ! is_user_logged_in()
			|| empty( $user = wp_get_current_user() ) ||
			! is_wp_error( $this->check_if_user_email_is_verified( get_userdata( $user->ID ) ) )
		) {
			return;
		}
		wp_logout();
	}

	/**
	 * prevent_authenticate.
	 *
	 * @version 5.2.0
	 * @since   5.2.0
	 *
	 * @param $user
	 *
	 * @return WP_Error
	 */
	function prevent_authenticate( $user ) {
		if (
			'yes' !== wcj_get_option( 'wcj_emails_verification_prevent_user_login', 'no' )
			|| is_wp_error( $user )
			|| is_null( $user )
			|| 0 == $user->ID
		) {
			return $user;
		}
		setup_userdata( $user->ID );
		$user_data = get_userdata( $user->ID );
		$response  = $this->check_if_user_email_is_verified( $user_data );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $user;
	}

	/**
	 * woocommerce_registration_auth_new_customer.
	 *
	 * @version 5.2.0
	 * @since   5.2.0
	 *
	 * @param $allowed
	 *
	 * @return bool
	 */
	function woocommerce_registration_auth_new_customer( $allowed ) {
		if ( 'yes' !== wcj_get_option( 'wcj_emails_verification_prevent_user_login', 'no' ) ) {
			return $allowed;
		}
		$allowed = false;
		return $allowed;
	}

	/**
	 * add_verified_email_column.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @todo    (maybe) add option to enable/disable the column
	 */
	function add_verified_email_column( $columns ) {
		$columns['wcj_is_verified_email'] = __( 'Verified', 'woocommerce-jetpack' );
		return $columns;
	}

	/**
	 * render_verified_email_column.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function render_verified_email_column( $output, $column_name, $user_id ) {
		if ( 'wcj_is_verified_email' === $column_name ) {
			$replaced_values = array(
				'1' => '<span title="' . __( 'Email verified', 'woocommerce-jetpack' ) . '">&#9745;</span>',
				'0' => '<span title="' . __( 'Email not verified', 'woocommerce-jetpack' ) . '">&#10006;</span>',
			);
			return str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), get_user_meta( $user_id, 'wcj_is_activated', true ) );
		}
		return $output;
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
	 * @version 3.6.0
	 * @since   2.8.0
	 */
	function check_if_user_email_is_verified( $userdata ) {
		if (
			( 'yes' === wcj_get_option( 'wcj_emails_verification_already_registered_enabled', 'no' ) &&       ! get_user_meta( $userdata->ID, 'wcj_is_activated', true ) ) ||
			( 'no'  === wcj_get_option( 'wcj_emails_verification_already_registered_enabled', 'no' ) && '0' === get_user_meta( $userdata->ID, 'wcj_is_activated', true ) )
		) {
			if ( isset( $userdata->roles ) && ! empty( $userdata->roles ) ) {
				$userdata_roles  = wcj_get_array( $userdata->roles );
				$skip_user_roles = wcj_get_array( wcj_get_option( 'wcj_emails_verification_skip_user_roles', array( 'administrator' ) ) );
				$_intersect = array_intersect( $userdata_roles, $skip_user_roles );
				if ( ! empty( $_intersect ) ) {
					return $userdata;
				}
			}
			$error_message = do_shortcode( wcj_get_option( 'wcj_emails_verification_error_message',
				__( 'Your account has to be activated before you can login. You can resend email with verification link by clicking <a href="%resend_verification_url%">here</a>.', 'woocommerce-jetpack' )
			) );
			$error_message = str_replace( '%resend_verification_url%', add_query_arg( 'wcj_user_id', $userdata->ID, wc_get_page_permalink( 'myaccount' ) ), $error_message );
			$userdata = new WP_Error( 'booster_email_verified_error', $error_message );
		}
		return $userdata;
	}

	/**
	 * reset_and_mail_activation_link.
	 *
	 * @version 3.1.0
	 * @since   2.8.0
	 * @todo    %site_name% etc. in `wcj_emails_verification_email_subject`
	 */
	function reset_and_mail_activation_link( $user_id ) {
		$user_info     = get_userdata( $user_id );
		$code          = md5( time() );
		$url           = add_query_arg( 'wcj_verify_email', base64_encode( json_encode( array( 'id' => $user_id, 'code' => $code ) ) ), wc_get_page_permalink( 'myaccount' ) );
		$email_content = do_shortcode( apply_filters( 'booster_option',
			__( 'Please click the following link to verify your email:<br><br><a href="%verification_url%">%verification_url%</a>', 'woocommerce-jetpack' ),
			get_option( 'wcj_emails_verification_email_content',
				__( 'Please click the following link to verify your email:<br><br><a href="%verification_url%">%verification_url%</a>', 'woocommerce-jetpack' ) ) ) );
		$email_content = str_replace( '%verification_url%', $url, $email_content );
		$email_subject = do_shortcode( apply_filters( 'booster_option',
			__( 'Please activate your account', 'woocommerce-jetpack' ),
			get_option( 'wcj_emails_verification_email_subject',
				__( 'Please activate your account', 'woocommerce-jetpack' ) ) ) );
		update_user_meta( $user_id, 'wcj_is_activated', '0' );
		update_user_meta( $user_id, 'wcj_activation_code', $code );
		if ( 'wc' === apply_filters( 'booster_option', 'plain', wcj_get_option( 'wcj_emails_verification_email_template', 'plain' ) ) ) {
			$email_content = wcj_wrap_in_wc_email_template( $email_content,
				get_option( 'wcj_emails_verification_email_template_wc_heading', __( 'Activate your account', 'woocommerce-jetpack' ) ) );
		}
		wc_mail( $user_info->user_email, $email_subject, $email_content );
	}

	/**
	 * process_email_verification.
	 *
	 * @version 3.9.0
	 * @since   2.8.0
	 */
	function process_email_verification() {
		if ( isset( $_GET['wcj_verified_email'] ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				$data = json_decode( base64_decode( $_GET['wcj_verified_email'] ), true );
				if ( ! empty( $data['id'] ) && ! empty( $data['code'] ) && get_user_meta( $data['id'], 'wcj_activation_code', true ) == $data['code'] ) {
					wc_add_notice( do_shortcode( wcj_get_option( 'wcj_emails_verification_success_message',
						__( '<strong>Success:</strong> Your account has been activated!', 'woocommerce-jetpack' ) ) ) );
				}
			}
		} elseif ( isset( $_GET['wcj_verify_email'] ) ) {
			$data = json_decode( base64_decode( $_GET['wcj_verify_email'] ), true );
			if ( ! empty( $data['id'] ) && ! empty( $data['code'] ) && get_user_meta( $data['id'], 'wcj_activation_code', true ) == $data['code'] ) {
				update_user_meta( $data['id'], 'wcj_is_activated', '1' );
				if ( 'yes' === wcj_get_option( 'wcj_emails_verification_redirect_on_success', 'yes' ) ) {
					wp_set_current_user( $data['id'] );
					wp_set_auth_cookie( $data['id'] );
				}
				$url = ( '' != ( $custom_url = wcj_get_option( 'wcj_emails_verification_redirect_on_success_custom_url', '' ) ) ? $custom_url : wc_get_page_permalink( 'myaccount' ) );
				wp_safe_redirect( add_query_arg( 'wcj_verified_email', $_GET['wcj_verify_email'], $url ) );
				exit;
			} elseif ( ! empty( $data['id'] ) ) {
				$_notice = do_shortcode(
					get_option( 'wcj_emails_verification_failed_message',
						__( '<strong>Error:</strong> Activation failed, please contact our administrator. You can resend email with verification link by clicking <a href="%resend_verification_url%">here</a>.', 'woocommerce-jetpack' )
					)
				);
				$_notice = str_replace( '%resend_verification_url%', add_query_arg( 'wcj_user_id', $data['id'], wc_get_page_permalink( 'myaccount' ) ), $_notice );
				wc_add_notice( $_notice, 'error' );
			} else {
				$_notice = wcj_get_option( 'wcj_emails_verification_failed_message_no_user_id',
					__( '<strong>Error:</strong> Activation failed, please contact our administrator.', 'woocommerce-jetpack' ) );
				wc_add_notice( $_notice, 'error' );
			}
		} elseif ( isset( $_GET['wcj_activate_account_message'] ) ) {
			wc_add_notice( do_shortcode( wcj_get_option( 'wcj_emails_verification_activation_message',
				__( 'Thank you for your registration. Your account has to be activated before you can login. Please check your email.', 'woocommerce-jetpack' ) ) ) );
		} elseif ( isset( $_GET['wcj_user_id'] ) ) {
			$this->reset_and_mail_activation_link( $_GET['wcj_user_id'] );
			wc_add_notice( do_shortcode( wcj_get_option( 'wcj_emails_verification_email_resend_message',
				__( '<strong>Success:</strong> Your activation email has been resent. Please check your email.', 'woocommerce-jetpack' ) ) ) );
		}
	}

}

endif;

return new WCJ_Email_Verification();

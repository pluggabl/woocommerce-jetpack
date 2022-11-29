<?php
/**
 * Booster for WooCommerce - Module - Email Verification
 *
 * @version 6.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Emails_Verification' ) ) :
	/**
	 * WCJ_Currencies.
	 */
	class WCJ_Emails_Verification extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.8.0
		 */
		public function __construct() {

			$this->id         = 'emails_verification';
			$this->short_desc = __( 'Email Verification', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add WooCommerce email verification. Customize verification email subject, content and template (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add WooCommerce email verification.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-email-verification';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_action( 'init', array( $this, 'process_email_verification' ), PHP_INT_MAX );
				add_filter( 'woocommerce_registration_redirect', array( $this, 'prevent_user_login_automatically_after_register' ), PHP_INT_MAX );
				add_filter( 'wp_authenticate_user', array( $this, 'check_if_user_email_is_verified' ), PHP_INT_MAX );
				add_action( 'user_register', array( $this, 'reset_and_mail_activation_link' ), PHP_INT_MAX );
				add_filter( 'manage_users_columns', array( $this, 'add_verified_email_column' ) );
				add_filter( 'manage_users_custom_column', array( $this, 'render_verified_email_column' ), 10, 3 );
				add_action( 'wp', array( $this, 'prevent_login' ), PHP_INT_MAX );
				add_filter( 'woocommerce_registration_auth_new_customer', array( $this, 'woocommerce_registration_auth_new_customer' ) );
				add_filter( 'authenticate', array( $this, 'prevent_authenticate' ), PHP_INT_MAX );
			}
		}

		/**
		 * Prevent_login.
		 *
		 * @version 5.2.0
		 * @since   5.2.0
		 */
		public function prevent_login() {
			$user = wp_get_current_user();

			if (
			'yes' !== wcj_get_option( 'wcj_emails_verification_prevent_user_login', 'no' )
			|| is_admin()
			|| ! is_user_logged_in()
			|| empty( $user ) ||
			! is_wp_error( $this->check_if_user_email_is_verified( get_userdata( $user->ID ) ) )
			) {
				return;
			}
			wp_logout();
		}

		/**
		 * Prevent_authenticate.
		 *
		 * @version 5.2.0
		 * @since   5.2.0
		 * @param string $user defines the user.
		 * @return WP_Error
		 */
		public function prevent_authenticate( $user ) {
			if (
			'yes' !== wcj_get_option( 'wcj_emails_verification_prevent_user_login', 'no' )
			|| is_wp_error( $user )
			|| is_null( $user )
			|| 0 === $user->ID
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
		 * Woocommerce_registration_auth_new_customer.
		 *
		 * @version 5.2.0
		 * @since   5.2.0
		 *
		 * @param bool $allowed check auth.
		 *
		 * @return bool
		 */
		public function woocommerce_registration_auth_new_customer( $allowed ) {
			if ( 'yes' !== wcj_get_option( 'wcj_emails_verification_prevent_user_login', 'no' ) ) {
				return $allowed;
			}
			$allowed = false;
			return $allowed;
		}

		/**
		 * Add_verified_email_column.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @todo    (maybe) add option to enable/disable the column
		 * @param array $columns defines email coulmn.
		 */
		public function add_verified_email_column( $columns ) {
			$columns['wcj_is_verified_email'] = __( 'Verified', 'woocommerce-jetpack' );
			return $columns;
		}

		/**
		 * Render_verified_email_column.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param string $output defines the output.
		 * @param string $column_name defines the column_name.
		 * @param int    $user_id defines the user_id.
		 */
		public function render_verified_email_column( $output, $column_name, $user_id ) {
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
		 * Prevent_user_login_automatically_after_register.
		 *
		 * @version 6.0.0
		 * @since   2.8.0
		 * @param string $redirect_to defines the redirect_to.
		 */
		public function prevent_user_login_automatically_after_register( $redirect_to ) {
			wp_logout();
			return esc_url_raw( add_query_arg( 'wcj_activate_account_message', '', $redirect_to ) );
		}

		/**
		 * Check_if_user_email_is_verified.
		 *
		 * @version 5.5.9
		 * @since   2.8.0
		 * @param array $userdata defines the userdata.
		 */
		public function check_if_user_email_is_verified( $userdata ) {
			if (
			( 'yes' === wcj_get_option( 'wcj_emails_verification_already_registered_enabled', 'no' ) && ! get_user_meta( $userdata->ID, 'wcj_is_activated', true ) ) ||
			( 'no' === wcj_get_option( 'wcj_emails_verification_already_registered_enabled', 'no' ) && '0' === get_user_meta( $userdata->ID, 'wcj_is_activated', true ) )
			) {
				if ( isset( $userdata->roles ) && ! empty( $userdata->roles ) ) {
					$userdata_roles  = wcj_get_array( $userdata->roles );
					$skip_user_roles = wcj_get_array( wcj_get_option( 'wcj_emails_verification_skip_user_roles', array( 'administrator' ) ) );
					$_intersect      = array_intersect( $userdata_roles, $skip_user_roles );
					if ( ! empty( $_intersect ) ) {
						return $userdata;
					}
				}
				$error_message = do_shortcode(
					wcj_get_option(
						'wcj_emails_verification_error_message',
						__( 'Your account has to be activated before you can login. You can resend email with verification link by clicking <a href="%resend_verification_url%">here</a>.', 'woocommerce-jetpack' )
					)
				);
				$error_message = str_replace( '%resend_verification_url%', esc_url( add_query_arg( 'wcj_user_id', $userdata->ID, wc_get_page_permalink( 'myaccount' ) ) ), $error_message );
				$userdata      = new WP_Error( 'booster_email_verified_error', $error_message );
			}
			return $userdata;
		}

		/**
		 * Reset_and_mail_activation_link.
		 *
		 * @version 6.0.0
		 * @since   2.8.0
		 * @todo    %site_name% etc. in `wcj_emails_verification_email_subject`
		 * @param int $user_id defines the user_id.
		 */
		public function reset_and_mail_activation_link( $user_id ) {
			$user_info     = get_userdata( $user_id );
			$code          = mb_strtoupper( strval( bin2hex( openssl_random_pseudo_bytes( 16 ) ) ) );
			$url           = wp_nonce_url(
				add_query_arg(
					'wcj_verify_email',
					base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						wp_json_encode(
							array(
								'id'   => $user_id,
								'code' => $code,
							)
						)
					),
					wc_get_page_permalink( 'myaccount' )
				),
				'wcj_verify_email'
			);
			$email_content = do_shortcode(
				apply_filters(
					'booster_option',
					__( 'Please click the following link to verify your email:<br><br><a href="%verification_url%">%verification_url%</a>', 'woocommerce-jetpack' ),
					get_option(
						'wcj_emails_verification_email_content',
						__( 'Please click the following link to verify your email:<br><br><a href="%verification_url%">%verification_url%</a>', 'woocommerce-jetpack' )
					)
				)
			);
			$email_content = str_replace( '%verification_url%', $url, $email_content );
			$email_subject = do_shortcode(
				apply_filters(
					'booster_option',
					__( 'Please activate your account', 'woocommerce-jetpack' ),
					get_option(
						'wcj_emails_verification_email_subject',
						__( 'Please activate your account', 'woocommerce-jetpack' )
					)
				)
			);
			update_user_meta( $user_id, 'wcj_is_activated', '0' );
			update_user_meta( $user_id, 'wcj_activation_code', $code );
			if ( 'wc' === apply_filters( 'booster_option', 'plain', wcj_get_option( 'wcj_emails_verification_email_template', 'plain' ) ) ) {
				$email_content = wcj_wrap_in_wc_email_template(
					$email_content,
					get_option( 'wcj_emails_verification_email_template_wc_heading', __( 'Activate your account', 'woocommerce-jetpack' ) )
				);
			}
			wc_mail( $user_info->user_email, $email_subject, $email_content );
		}

		/**
		 * Process_email_verification.
		 *
		 * @version 6.0.0
		 * @since   2.8.0
		 */
		public function process_email_verification() {
			$wcj_verify_email_wpnonce = isset( $_GET['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wcj_verify_email' ) : false;
			if ( isset( $_GET['wcj_verified_email'] ) ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					$data = json_decode( base64_decode( sanitize_text_field( wp_unslash( $_GET['wcj_verified_email'] ) ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
					if ( ! empty( $data['id'] ) && ! empty( $data['code'] ) && get_user_meta( $data['id'], 'wcj_activation_code', true ) === $data['code'] ) {
						wc_add_notice(
							do_shortcode(
								wcj_get_option(
									'wcj_emails_verification_success_message',
									__( '<strong>Success:</strong> Your account has been activated!', 'woocommerce-jetpack' )
								)
							)
						);
					}
				}
			} elseif ( $wcj_verify_email_wpnonce && isset( $_GET['wcj_verify_email'] ) ) {
				$data = json_decode( base64_decode( sanitize_text_field( wp_unslash( $_GET['wcj_verify_email'] ) ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				if ( ! empty( $data['id'] ) && ! empty( $data['code'] ) && get_user_meta( $data['id'], 'wcj_activation_code', true ) === $data['code'] ) {
					update_user_meta( $data['id'], 'wcj_is_activated', '1' );
					if ( 'yes' === wcj_get_option( 'wcj_emails_verification_redirect_on_success', 'yes' ) ) {
						wp_set_current_user( $data['id'] );
						wp_set_auth_cookie( $data['id'] );
					}
					$custom_url = wcj_get_option( 'wcj_emails_verification_redirect_on_success_custom_url', '' );
					$url        = ( '' !== ( $custom_url ) ? $custom_url : wc_get_page_permalink( 'myaccount' ) );
					wp_safe_redirect( add_query_arg( 'wcj_verified_email', sanitize_text_field( wp_unslash( $_GET['wcj_verify_email'] ) ), $url ) );
					exit;
				} elseif ( ! empty( $data['id'] ) ) {
					$_notice = do_shortcode(
						get_option(
							'wcj_emails_verification_failed_message',
							__( '<strong>Error:</strong> Activation failed, please contact our administrator. You can resend email with verification link by clicking <a href="%resend_verification_url%">here</a>.', 'woocommerce-jetpack' )
						)
					);
					$_notice = str_replace( '%resend_verification_url%', esc_url( add_query_arg( 'wcj_user_id', $data['id'], wc_get_page_permalink( 'myaccount' ) ) ), $_notice );
					wc_add_notice( $_notice, 'error' );
				} else {
					$_notice = wcj_get_option(
						'wcj_emails_verification_failed_message_no_user_id',
						__( '<strong>Error:</strong> Activation failed, please contact our administrator.', 'woocommerce-jetpack' )
					);
					wc_add_notice( $_notice, 'error' );
				}
			} elseif ( isset( $_GET['wcj_activate_account_message'] ) ) {
				wc_add_notice(
					do_shortcode(
						wcj_get_option(
							'wcj_emails_verification_activation_message',
							__( 'Thank you for your registration. Your account has to be activated before you can login. Please check your email.', 'woocommerce-jetpack' )
						)
					)
				);
			} elseif ( isset( $_GET['wcj_user_id'] ) ) {
				$this->reset_and_mail_activation_link( sanitize_text_field( wp_unslash( $_GET['wcj_user_id'] ) ) );
				wc_add_notice(
					do_shortcode(
						wcj_get_option(
							'wcj_emails_verification_email_resend_message',
							__( '<strong>Success:</strong> Your activation email has been resent. Please check your email.', 'woocommerce-jetpack' )
						)
					)
				);
			}
		}

	}

endif;

return new WCJ_Emails_Verification();

<?php
/**
 * Booster for WooCommerce - Module - General
 *
 * @version 7.2.5
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_General' ) ) :
	/**
	 * WCJ_General.
	 */
	class WCJ_General extends WCJ_Module {

		/**
		 * The module current_php_memory_limit
		 *
		 * @var varchar $current_php_memory_limit Module.
		 */
		public $current_php_memory_limit;
		/**
		 * The module current_php_time_limit
		 *
		 * @var varchar $current_php_time_limit Module.
		 */
		public $current_php_time_limit;
		/**
		 * Constructor.
		 *
		 * @version 5.6.8
		 * @todo    [dev] maybe expand `$this->desc` (e.g.: Custom roles tool, shortcodes in WordPress text widgets etc.)
		 */
		public function __construct() {

			$this->id         = 'general';
			$this->short_desc = __( 'General', 'woocommerce-jetpack' );
			$this->desc       = __( 'Booster for WooCommerce general front-end tools.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-booster-general-tools';
			parent::__construct();

			$this->add_tools(
				array(
					'custom_roles' => array(
						'title'     => __( 'Add/Manage Custom Roles', 'woocommerce-jetpack' ),
						'tab_title' => __( 'Custom Roles', 'woocommerce-jetpack' ),
						'desc'      => __( 'Manage Custom Roles.', 'woocommerce-jetpack' ),
					),
				)
			);

			$this->current_php_memory_limit = '';
			$this->current_php_time_limit   = '';

			if ( $this->is_enabled() ) {
				$php_memory_limit = wcj_get_option( 'wcj_admin_tools_php_memory_limit', '0' );
				$php_time_limit   = wcj_get_option( 'wcj_admin_tools_php_time_limit', '0' );
				// PHP Memory Limit.
				if ( '0' !== ( $php_memory_limit ) ) {
					ini_set( 'memory_limit', $php_memory_limit . 'M' ); // phpcs:ignore WordPress.PHP.IniSet
				}
				/* translators: %s: translation added */
				$this->current_php_memory_limit = sprintf( ' ' . __( 'Current PHP memory limit: %s.', 'woocommerce-jetpack' ), ini_get( 'memory_limit' ) );

				// PHP Time Limit.
				if ( '0' !== ( $php_time_limit ) ) {
					set_time_limit( $php_time_limit );
				}
				/* translators: %s: translation added */
				$this->current_php_time_limit = sprintf( ' ' . __( 'Current PHP time limit: %s seconds.', 'woocommerce-jetpack' ), ini_get( 'max_execution_time' ) );

				// Recalculate cart totals.
				if ( 'yes' === wcj_get_option( 'wcj_general_advanced_recalculate_cart_totals', 'no' ) ) {
					add_action( 'wp_loaded', array( $this, 'fix_mini_cart' ), PHP_INT_MAX );
				}

				// Shortcodes in text widgets.
				if ( 'yes' === wcj_get_option( 'wcj_general_shortcodes_in_text_widgets_enabled' ) ) {
					add_filter( 'widget_text', 'do_shortcode' );
				}

				// PayPal email per product.
				if ( 'yes' === wcj_get_option( 'wcj_paypal_email_per_product_enabled', 'no' ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_payment_gateways', array( $this, 'maybe_change_paypal_email' ) );
				}

				// Session expiration.
				if ( 'yes' === wcj_get_option( 'wcj_session_expiration_section_enabled', 'no' ) ) {
					add_filter( 'wc_session_expiring', array( $this, 'change_session_expiring' ), PHP_INT_MAX );
					add_filter( 'wc_session_expiration', array( $this, 'change_session_expiration' ), PHP_INT_MAX );
				}

				// Booster role user changer.
				if ( wcj_is_booster_role_changer_enabled() ) {
					add_action( 'admin_bar_menu', array( $this, 'add_user_role_changer' ), PHP_INT_MAX );
					add_action( 'init', array( $this, 'change_user_role_meta' ) );
				}

				// Try to overwrite WooCommerce IP detection.
				if ( 'yes' === wcj_get_option( 'wcj_general_overwrite_wc_ip', 'no' ) ) {
					if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
						$_SERVER['HTTP_X_REAL_IP'] = wcj_get_the_ip();
					}
				}
			}
		}

		/**
		 * Change_user_role_meta.
		 *
		 * @version 5.6.7
		 * @since   2.9.0
		 * @todo    [dev] (maybe) optionally via cookies
		 */
		public function change_user_role_meta() {
			$wpnonce = isset( $_REQUEST['wcj_booster_user_role_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_booster_user_role_nonce'] ), 'wcj_booster_user_role' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_booster_user_role'] ) ) {
				$current_user_id = get_current_user_id();
				update_user_meta( $current_user_id, '_wcj_booster_user_role', sanitize_text_field( wp_unslash( $_GET['wcj_booster_user_role'] ) ) );
			}
		}

		/**
		 * Add_user_role_changer.
		 *
		 * @version 6.0.1
		 * @since   2.9.0
		 * @param string | array $wp_admin_bar defines the wp_admin_bar.
		 */
		public function add_user_role_changer( $wp_admin_bar ) {
			$current_user_id           = get_current_user_id();
			$user_roles                = wcj_get_user_roles_options();
			$current_booster_user_role = get_user_meta( $current_user_id, '_wcj_booster_user_role', true );
			if ( '' !== ( $current_booster_user_role ) ) {
				$current_booster_user_role = ( isset( $user_roles[ $current_booster_user_role ] ) ) ? $user_roles[ $current_booster_user_role ] : $current_booster_user_role;
				$current_booster_user_role = ' [' . $current_booster_user_role . ']';
			}
			$args = array(
				'parent' => false,
				'id'     => 'booster-user-role-changer',
				'title'  => __( 'Booster User Role', 'woocommerce-jetpack' ) . $current_booster_user_role,
				'href'   => false,
			);
			$wp_admin_bar->add_node( $args );
			foreach ( $user_roles as $user_role_key => $user_role_name ) {
				$args = array(
					'parent' => 'booster-user-role-changer',
					'id'     => 'booster-user-role-changer-role-' . $user_role_key,
					'title'  => $user_role_name,
					'href'   => esc_url(
						add_query_arg(
							array(
								'wcj_booster_user_role' => $user_role_key,
								'wcj_booster_user_role_nonce' => wp_create_nonce( 'wcj_booster_user_role' ),
							)
						)
					),
				);
				$wp_admin_bar->add_node( $args );
			}
		}

		/**
		 * Fix_mini_cart.
		 *
		 * @version 2.5.2
		 * @since   2.5.2
		 * @todo    [dev] this is only temporary solution!
		 */
		public function fix_mini_cart() {
			if ( wcj_is_frontend() ) {
				$wc = WC();
				if ( null !== ( $wc ) ) {
					if ( isset( $wc->cart ) ) {
						$wc->cart->calculate_totals();
					}
				}
			}
		}

		/**
		 * Change_session_expiring.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param int $the_time defines the the_time.
		 */
		public function change_session_expiring( $the_time ) {
			return wcj_get_option( 'wcj_session_expiring', 47 * 60 * 60 );
		}

		/**
		 * Change_session_expiration.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param int $the_time defines the the_time.
		 */
		public function change_session_expiration( $the_time ) {
			return wcj_get_option( 'wcj_session_expiration', 48 * 60 * 60 );
		}

		/**
		 * Create_custom_roles_tool.
		 *
		 * @version 7.2.5
		 * @since   2.5.3
		 */
		public function create_custom_roles_tool() {
			$add_role_wpnonce    = false;
			$delete_role_wpnonce = false;
			if ( function_exists( 'wp_verify_nonce' ) ) {
				$add_role_wpnonce    = isset( $_REQUEST['wcj_add_new_role_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_add_new_role_nonce'] ), 'wcj_add_new_role_nonce' ) : false;
				$delete_role_wpnonce = isset( $_REQUEST['wcj_delete_role_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_delete_role_nonce'] ), 'wcj_delete_role_nonce' ) : false;
			}
			if ( $add_role_wpnonce && isset( $_POST['wcj_add_new_role'] ) ) {
				if ( empty( $_POST['wcj_custom_role_id'] ) || empty( $_POST['wcj_custom_role_name'] ) || empty( $_POST['wcj_custom_role_caps'] ) ) {
					echo '<p style="color:red;font-weight:bold;">' . wp_kses_post( 'All fields are required!', 'woocommerce-jetpack' ) . '</p>';
				} else {
					$role_id = sanitize_key( $_POST['wcj_custom_role_id'] );
					if ( is_numeric( $role_id ) ) {
						echo '<p style="color:red;font-weight:bold;">' . wp_kses_post( 'Role ID must not be numbers only!', 'woocommerce-jetpack' ) . '</p>';
					} else {
						$caps_role = get_role( sanitize_text_field( wp_unslash( $_POST['wcj_custom_role_caps'] ) ) );
						$caps      = ( ! empty( $caps_role->capabilities ) && is_array( $caps_role->capabilities ) ? $caps_role->capabilities : array() );
						$result    = add_role( $role_id, sanitize_text_field( wp_unslash( $_POST['wcj_custom_role_name'] ) ), $caps );
						if ( null !== $result ) {
							$custom_roles             = wcj_get_option( 'wcj_custom_roles', array() ); // `wcj_custom_roles` option added since Booster v4.0.0
							$custom_roles[ $role_id ] = array(
								'display_name' => sanitize_text_field( wp_unslash( $_POST['wcj_custom_role_name'] ) ),
								'caps_role'    => sanitize_text_field( wp_unslash( $_POST['wcj_custom_role_caps'] ) ),
							);
							update_option( 'wcj_custom_roles', $custom_roles );
							echo '<p style="color:green;font-weight:bold;">' . wp_kses_post( 'Role successfully added!', 'woocommerce-jetpack' ) . '</p>';
						} else {
							echo '<p style="color:red;font-weight:bold;">' . wp_kses_post( 'Role already exists!', 'woocommerce-jetpack' ) . '</p>';
						}
					}
				}
			}

			if ( $delete_role_wpnonce && isset( $_GET['wcj_delete_role'] ) && '' !== $_GET['wcj_delete_role'] ) {
				remove_role( sanitize_text_field( wp_unslash( $_GET['wcj_delete_role'] ) ) );
				$custom_roles = wcj_get_option( 'wcj_custom_roles', array() );
				if ( isset( $custom_roles[ $_GET['wcj_delete_role'] ] ) ) {
					unset( $custom_roles[ $_GET['wcj_delete_role'] ] );
					update_option( 'wcj_custom_roles', $custom_roles );
				}
				/* translators: %s: translation added */
				echo '<p style="color:green;font-weight:bold;">' . sprintf( wp_kses_post( 'Role %s successfully deleted!', 'woocommerce-jetpack' ), wp_kses_post( sanitize_text_field( wp_unslash( $_GET['wcj_delete_role'] ) ) ) ) . '</p>';
			}
			echo '<div class="wcj-setting-jetpack-body wcj_tools_cnt_main">';
			echo wp_kses_post( $this->get_tool_header_html( 'custom_roles' ) );

			$table_data          = array();
			$table_data[]        = array( __( 'ID', 'woocommerce-jetpack' ), __( 'Name', 'woocommerce-jetpack' ), __( 'Capabilities', 'woocommerce-jetpack' ), __( 'Actions', 'woocommerce-jetpack' ) );
			$existing_roles      = wcj_get_user_roles();
			$default_wp_wc_roles = array( 'guest', 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager' );
			$custom_roles        = wcj_get_option( 'wcj_custom_roles', array() );
			foreach ( $existing_roles as $role_key => $role_data ) {
				$delete_html  = ( in_array( $role_key, $default_wp_wc_roles, true ) )
				? ''
				: '<a href="' . esc_url(
					add_query_arg(
						array(
							'wcj_delete_role'       => $role_key,
							'wcj_delete_role_nonce' => wp_create_nonce( 'wcj_delete_role_nonce' ),
						)
					)
				) . '"' . wcj_get_js_confirmation() . '>' . __( 'Delete', 'woocommerce-jetpack' ) . '</a>';
				$caps         = ( ! empty( $custom_roles[ $role_key ]['caps_role'] ) ? $custom_roles[ $role_key ]['caps_role'] : $role_key );
				$table_data[] = array( $role_key, $role_data['name'], $caps, $delete_html );
			}
			echo '<h3>' . wp_kses_post( 'Existing Roles', 'woocommerce-jetpack' ) . '</h3>';
			$allowed_tags                 = wp_kses_allowed_html( 'post' );
			$allowed_tags['a']['onclick'] = true;
			echo wp_kses( wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) ), $allowed_tags );

			$table_data   = array();
			$table_data[] = array( __( 'ID', 'woocommerce-jetpack' ), '<input style="width:100%" required type="text" name="wcj_custom_role_id">' );
			$table_data[] = array( __( 'Name', 'woocommerce-jetpack' ), '<input style="width:100%" required type="text" name="wcj_custom_role_name">' );
			$table_data[] = array( __( 'Capabilities', 'woocommerce-jetpack' ), wcj_get_select_html( 'wcj_custom_role_caps', wcj_get_user_roles_options(), 'width:100%' ) );
			echo '<h3>' . wp_kses_post( 'Add New Role', 'woocommerce-jetpack' ) . '</h3>';
			echo '<form method="post" action="' . wp_kses_post( esc_url( remove_query_arg( 'wcj_delete_role' ) ) ) . '">' .
			wp_kses_post(
				wcj_get_table_html(
					$table_data,
					array(
						'table_class'        => 'widefat striped',
						'table_heading_type' => 'vertical',
						'table_style'        => 'width:20%;min-width:300px;',
					)
				)
			)
			. wp_kses_post( wp_nonce_field( 'wcj_add_new_role_nonce', 'wcj_add_new_role_nonce' ) )
			. '<p><input type="submit" name="wcj_add_new_role" class="button-primary" value="' . wp_kses_post( 'Add', 'woocommerce-jetpack' ) . '"></p>'
			. '</form>';
			echo '</div>';
		}

		/**
		 * Maybe_change_paypal_email.
		 *
		 * @version 2.5.2
		 * @since   2.5.2
		 * @param array $load_gateways defines the load_gateways.
		 */
		public function maybe_change_paypal_email( $load_gateways ) {
			if ( isset( WC()->cart ) ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$email = get_post_meta( $values['product_id'], '_wcj_paypal_per_product_email', true );
					if ( '' !== ( $email ) ) {
						foreach ( $load_gateways as $key => $gateway ) {
							if ( is_string( $gateway ) && 'WC_Gateway_Paypal' === $gateway ) {
								$load_gateway                             = new $gateway();
								$load_gateway->settings['email']          = $email;
								$load_gateway->settings['receiver_email'] = $load_gateway->settings['email'];
								$$load_gateway->email                     = $load_gateway->settings['receiver_email'];
								$load_gateway->receiver_email             = $load_gateway->email;
								$load_gateways[ $key ]                    = $load_gateway;
							}
						}
						break;
					}
				}
			}
			return $load_gateways;
		}

	}

endif;

return new WCJ_General();

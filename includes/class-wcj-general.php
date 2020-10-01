<?php
/**
 * Booster for WooCommerce - Module - General
 *
 * @version 4.5.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_General' ) ) :

class WCJ_General extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.5.0
	 * @todo    [dev] maybe expand `$this->desc` (e.g.: Custom roles tool, shortcodes in WordPress text widgets etc.)
	 */
	function __construct() {

		$this->id         = 'general';
		$this->short_desc = __( 'General', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster for WooCommerce general front-end tools.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-booster-general-tools';
		parent::__construct();

		$this->add_tools( array(
			'custom_roles' => array(
				'title'     => __( 'Add/Manage Custom Roles', 'woocommerce-jetpack' ),
				'tab_title' => __( 'Custom Roles', 'woocommerce-jetpack' ),
				'desc'      => __( 'Manage Custom Roles.', 'woocommerce-jetpack' ),
			),
		) );

		$this->current_php_memory_limit = '';
		$this->current_php_time_limit   = '';

		if ( $this->is_enabled() ) {

			// PHP Memory Limit
			if ( 0 != ( $php_memory_limit = wcj_get_option( 'wcj_admin_tools_php_memory_limit', 0 ) ) ) {
				ini_set( 'memory_limit', $php_memory_limit . 'M' );
			}
			$this->current_php_memory_limit = sprintf( ' ' . __( 'Current PHP memory limit: %s.', 'woocommerce-jetpack' ), ini_get( 'memory_limit' ) );

			// PHP Time Limit
			if ( 0 != ( $php_time_limit = wcj_get_option( 'wcj_admin_tools_php_time_limit', 0 ) ) ) {
				set_time_limit( $php_time_limit );
			}
			$this->current_php_time_limit = sprintf( ' ' . __( 'Current PHP time limit: %s seconds.', 'woocommerce-jetpack' ), ini_get( 'max_execution_time' ) );

			// Recalculate cart totals
			if ( 'yes' === wcj_get_option( 'wcj_general_advanced_recalculate_cart_totals', 'no' ) ) {
				add_action( 'wp_loaded', array( $this, 'fix_mini_cart' ), PHP_INT_MAX );
			}

			// Shortcodes in text widgets
			if ( 'yes' === wcj_get_option( 'wcj_general_shortcodes_in_text_widgets_enabled' ) ) {
				add_filter( 'widget_text', 'do_shortcode' );
			}

			// PayPal email per product
			if ( 'yes' === wcj_get_option( 'wcj_paypal_email_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'maybe_change_paypal_email' ) );
			}

			// Session expiration
			if ( 'yes' === wcj_get_option( 'wcj_session_expiration_section_enabled', 'no' ) ) {
				add_filter( 'wc_session_expiring',   array( $this, 'change_session_expiring' ),   PHP_INT_MAX );
				add_filter( 'wc_session_expiration', array( $this, 'change_session_expiration' ), PHP_INT_MAX );
			}

			// Booster role user changer
			if ( wcj_is_booster_role_changer_enabled() ) {
				add_action( 'admin_bar_menu', array( $this, 'add_user_role_changer' ), PHP_INT_MAX );
				add_action( 'init',           array( $this, 'change_user_role_meta' ) );
			}

			// Try to overwrite WooCommerce IP detection
			if ( 'yes' === wcj_get_option( 'wcj_general_overwrite_wc_ip', 'no' ) ) {
				if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
					$_SERVER['HTTP_X_REAL_IP'] = wcj_get_the_ip();
				}
			}

		}
	}

	/**
	 * change_user_role_meta.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    [dev] (maybe) optionally via cookies
	 */
	function change_user_role_meta() {
		if ( isset( $_GET['wcj_booster_user_role'] ) ) {
			$current_user_id = get_current_user_id();
			update_user_meta( $current_user_id, '_' . 'wcj_booster_user_role', $_GET['wcj_booster_user_role'] );
		}
	}

	/**
	 * add_user_role_changer.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_user_role_changer( $wp_admin_bar ) {
		$current_user_id  = get_current_user_id();
		$user_roles       = wcj_get_user_roles_options();
		if ( '' != ( $current_booster_user_role = get_user_meta( $current_user_id, '_' . 'wcj_booster_user_role', true ) ) ) {
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
				'href'   => add_query_arg( 'wcj_booster_user_role', $user_role_key ),
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * fix_mini_cart.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 * @todo    [dev] this is only temporary solution!
	 */
	function fix_mini_cart() {
		if ( wcj_is_frontend() ) {
			if ( null !== ( $wc = WC() ) ) {
				if ( isset( $wc->cart ) ) {
					$wc->cart->calculate_totals();
				}
			}
		}
	}

	/**
	 * change_session_expiring.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function change_session_expiring( $the_time ) {
		return wcj_get_option( 'wcj_session_expiring', 47 * 60 * 60 );
	}

	/**
	 * change_session_expiration.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function change_session_expiration( $the_time ) {
		return wcj_get_option( 'wcj_session_expiration', 48 * 60 * 60 );
	}

	/**
	 * create_custom_roles_tool.
	 *
	 * @version 4.0.0
	 * @since   2.5.3
	 */
	function create_custom_roles_tool() {
		if ( isset( $_POST['wcj_add_new_role'] ) ) {
			if ( empty( $_POST['wcj_custom_role_id'] ) || empty( $_POST['wcj_custom_role_name'] ) || empty( $_POST['wcj_custom_role_caps'] ) ) {
				echo '<p style="color:red;font-weight:bold;">' . __( 'All fields are required!', 'woocommerce-jetpack') . '</p>';
			} else {
				$role_id = sanitize_key( $_POST['wcj_custom_role_id'] );
				if ( is_numeric( $role_id ) ) {
					echo '<p style="color:red;font-weight:bold;">' . __( 'Role ID must not be numbers only!', 'woocommerce-jetpack') . '</p>';
				} else {
					$caps_role = get_role( $_POST['wcj_custom_role_caps'] );
					$caps      = ( ! empty( $caps_role->capabilities ) && is_array( $caps_role->capabilities ) ? $caps_role->capabilities : array() );
					$result    = add_role( $role_id, $_POST['wcj_custom_role_name'], $caps );
					if ( null !== $result ) {
						$custom_roles = wcj_get_option( 'wcj_custom_roles', array() ); // `wcj_custom_roles` option added since Booster v4.0.0
						$custom_roles[ $role_id ] = array( 'display_name' => $_POST['wcj_custom_role_name'], 'caps_role' => $_POST['wcj_custom_role_caps'] );
						update_option( 'wcj_custom_roles', $custom_roles );
						echo '<p style="color:green;font-weight:bold;">' . __( 'Role successfully added!', 'woocommerce-jetpack') . '</p>';
					} else {
						echo '<p style="color:red;font-weight:bold;">' . __( 'Role already exists!', 'woocommerce-jetpack') . '</p>';
					}
				}
			}
		}

		if ( isset( $_GET['wcj_delete_role'] ) && '' != $_GET['wcj_delete_role'] ) {
			remove_role( $_GET['wcj_delete_role'] );
			$custom_roles = wcj_get_option( 'wcj_custom_roles', array() );
			if ( isset( $custom_roles[ $_GET['wcj_delete_role'] ] ) ) {
				unset( $custom_roles[ $_GET['wcj_delete_role'] ] );
				update_option( 'wcj_custom_roles', $custom_roles );
			}
			echo '<p style="color:green;font-weight:bold;">' . sprintf( __( 'Role %s successfully deleted!', 'woocommerce-jetpack'), $_GET['wcj_delete_role'] ) . '</p>';
		}

		echo $this->get_tool_header_html( 'custom_roles' );

		$table_data = array();
		$table_data[] = array( __( 'ID', 'woocommerce-jetpack'), __( 'Name', 'woocommerce-jetpack'), __( 'Capabilities', 'woocommerce-jetpack'), __( 'Actions', 'woocommerce-jetpack') );
		$existing_roles = wcj_get_user_roles();
		$default_wp_wc_roles = array( 'guest', 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager' );
		$custom_roles = wcj_get_option( 'wcj_custom_roles', array() );
		foreach ( $existing_roles as $role_key => $role_data ) {
			$delete_html = ( in_array( $role_key, $default_wp_wc_roles ) )
				? ''
				: '<a href="' . add_query_arg( 'wcj_delete_role', $role_key ). '"' . wcj_get_js_confirmation() . '>' . __( 'Delete', 'woocommerce-jetpack') . '</a>';
			$caps = ( ! empty( $custom_roles[ $role_key ]['caps_role'] ) ? $custom_roles[ $role_key ]['caps_role'] : $role_key );
			$table_data[] = array( $role_key, $role_data['name'], $caps, $delete_html );
		}
		echo '<h3>' . __( 'Existing Roles', 'woocommerce-jetpack') . '</h3>';
		echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );

		$table_data = array();
		$table_data[] = array( __( 'ID', 'woocommerce-jetpack'),   '<input style="width:100%" required type="text" name="wcj_custom_role_id">' );
		$table_data[] = array( __( 'Name', 'woocommerce-jetpack'), '<input style="width:100%" required type="text" name="wcj_custom_role_name">' );
		$table_data[] = array( __( 'Capabilities', 'woocommerce-jetpack'), wcj_get_select_html( 'wcj_custom_role_caps', wcj_get_user_roles_options(), 'width:100%' ) );
		echo '<h3>' . __( 'Add New Role', 'woocommerce-jetpack') . '</h3>';
		echo '<form method="post" action="' . remove_query_arg( 'wcj_delete_role' ) . '">' .
			wcj_get_table_html( $table_data, array( 'table_class' => 'widefat', 'table_heading_type' => 'vertical', 'table_style' => 'width:20%;min-width:300px;', ) )
			. '<p>' . '<input type="submit" name="wcj_add_new_role" class="button-primary" value="' . __( 'Add', 'woocommerce-jetpack' ) . '">' . '</p>'
			. '</form>';
	}

	/**
	 * maybe_change_paypal_email.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function maybe_change_paypal_email( $load_gateways ) {
		if ( isset( WC()->cart ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				if ( '' != ( $email = get_post_meta( $values['product_id'], '_' . 'wcj_paypal_per_product_email', true ) ) ) {
					foreach ( $load_gateways as $key => $gateway ) {
						if ( is_string( $gateway ) && 'WC_Gateway_Paypal' === $gateway ) {
							$load_gateway = new $gateway();
							$load_gateway->receiver_email = $load_gateway->email = $load_gateway->settings['receiver_email'] = $load_gateway->settings['email'] = $email;
							$load_gateways[ $key ] = $load_gateway;
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

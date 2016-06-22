<?php
/**
 * WooCommerce Jetpack Payment Gateways by User Role
 *
 * The WooCommerce Jetpack Payment Gateways by User Role class.
 *
 * @version 2.5.3
 * @since   2.5.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_By_User_Role' ) ) :

class WCJ_Payment_Gateways_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function __construct() {

		$this->id         = 'payment_gateways_by_user_role';
		$this->short_desc = __( 'Gateways by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set user roles to include/exclude for WooCommerce payment gateways to show up.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-payment-gateways-by-user-role/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
		}
	}

	/**
	 * available_payment_gateways.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function available_payment_gateways( $_available_gateways ) {
		foreach ( $_available_gateways as $key => $gateway ) {
			$customer_role = wcj_get_current_user_first_role();
			$include_roles = get_option( 'wcj_gateways_user_roles_include_' . $key, '' );
			if ( ! empty( $include_roles ) && ! in_array( $customer_role, $include_roles ) ) {
				unset( $_available_gateways[ $key ] );
				continue;
			}
			$exclude_roles = get_option( 'wcj_gateways_user_roles_exclude_' . $key, '' );
			if ( ! empty( $exclude_roles ) && in_array( $customer_role, $exclude_roles ) ) {
				unset( $_available_gateways[ $key ] );
				continue;
			}
		}
		return $_available_gateways;
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_settings_hook() {
		add_filter( 'wcj_payment_gateways_by_user_role_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_settings( $settings ) {
		$settings = array();
		$settings[] = array(
			'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' ) . ' ' .
				sprintf( __( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module', 'woocommerce-jetpack' ),
					admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' ) ),
			'id'    => 'wcj_payment_gateways_by_user_role_gateways_options',
		);
		$user_roles = wcj_get_user_roles_options();
		$gateways = WC()->payment_gateways->payment_gateways();
		foreach ( $gateways as $key => $gateway ) {
			$default_gateways = array( 'bacs' );
			if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways ) ) {
				$custom_attributes = apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' );
				if ( '' == $custom_attributes ) {
					$custom_attributes = array();
				}
				$desc_tip = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' );
			} else {
				$custom_attributes = array();
				$desc_tip = '';
			}
			$settings[] = array(
				'title'     => $gateway->title,
				'desc_tip'  => $desc_tip,
				'desc'      => __( 'Include User Roles', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_user_roles_include_' . $key,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $user_roles,
				'custom_attributes' => $custom_attributes,
			);
			$settings[] = array(
				'title'     => '',
				'desc_tip'  => $desc_tip,
				'desc'      => __( 'Exclude User Roles', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_user_roles_exclude_' . $key,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $user_roles,
				'custom_attributes' => $custom_attributes,
			);
		}
		$settings[] = array(
			'type'  => 'sectionend',
			'id'    => 'wcj_payment_gateways_by_user_role_gateways_options',
		);
		return $settings;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_payment_gateways_by_user_role_settings', $settings );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Payment_Gateways_By_User_Role();

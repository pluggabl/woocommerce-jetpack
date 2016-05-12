<?php
/**
 * WooCommerce Jetpack Price by User Role
 *
 * The WooCommerce Jetpack Price by User Role class.
 *
 * @version 2.4.9
 * @since   2.4.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_By_User_Role' ) ) :

class WCJ_Price_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function __construct() {

		$this->id         = 'price_by_user_role';
		$this->short_desc = __( 'Price by User Role [BETA]', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products prices by user roles.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-price-by-user-role/'; // TODO
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$this->add_hooks();
			}
		}
	}

	/**
	 * add_hooks.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function add_hooks() {
		// Prices
		add_filter( 'woocommerce_get_price',                      array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 ); // TODO: priority
		add_filter( 'woocommerce_get_sale_price',                 array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_get_regular_price',              array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		// Variations
		add_filter( 'woocommerce_variation_prices_price',         array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_variation_prices_sale_price',    array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_get_variation_prices_hash',      array( $this, 'get_variation_prices_hash' ), PHP_INT_MAX - 200, 3 ); // TODO: priority
		// TODO: shipping?
	}

	/**
	 * change_price_by_role.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function change_price_by_role( $price, $_product ) {
		$koef = get_option( 'wcj_price_by_user_role_' . $this->get_current_user_role(), 1 );
		return ( '' === $price ) ? $price : $price * $koef;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$koef = get_option( 'wcj_price_by_user_role_' . $this->get_current_user_role(), 1 );
		$price_hash['wcj_user_role'] = $koef; // TODO?
		return $price_hash;
	}

	/**
	 * get_current_user_role.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function get_current_user_role() {
		$current_user = wp_get_current_user();
		return ( isset( $current_user->roles[0] ) && '' != $current_user->roles[0] ) ? $current_user->roles[0] : 'guest';
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function get_user_roles() {
		global $wp_roles;
		$all_roles = $wp_roles->roles;
		$all_roles = apply_filters( 'editable_roles', $all_roles );
		$all_roles['guest'] = array( 'name' => 'Guest', );
		return $all_roles;
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function add_settings_hook() {
		add_filter( 'wcj_price_by_user_role_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function get_settings() {
		$settings = apply_filters( 'wcj_price_by_user_role_settings', array() );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function add_settings() {
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_price_by_user_role_options',
			),
		);
		foreach ( $this->get_user_roles() as $role_key => $role_data ) {
			$settings[] = array(
				'title'    => $role_data['name'],
				'id'       => 'wcj_price_by_user_role_' . $role_key,
				'default'  => 1,
				'type'     => 'number',
				'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
			);
		}
		$settings[] = array(
			'type'         => 'sectionend',
			'id'           => 'wcj_price_by_user_role_options',
		);
		return $settings;
	}
}

endif;

return new WCJ_Price_By_User_Role();

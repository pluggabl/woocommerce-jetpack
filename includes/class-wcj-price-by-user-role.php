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

		$this->add_tools( array(
			'custom_roles' => array(
				'title' => __( 'Custom Roles', 'woocommerce-jetpack' ),
				'desc'  => __( 'Manage Custom Roles.', 'woocommerce-jetpack' ),
			),
		) );

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$this->add_hooks();
			}
		}
	}

	/**
	 * create_custom_roles_tool.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function create_custom_roles_tool() {

		if ( isset( $_POST['wcj_add_new_role'] ) ) {
			if (
				! isset( $_POST['wcj_custom_role_id'] )   || '' == $_POST['wcj_custom_role_id'] ||
				! isset( $_POST['wcj_custom_role_name'] ) || '' == $_POST['wcj_custom_role_name']
			) {
				echo '<p style="color:red;font-weight:bold;">' . __( 'Both fields are required!', 'woocommerce-jetpack') . '</p>';
			} else {
				if ( is_numeric( $_POST['wcj_custom_role_id'] ) ) {
					echo '<p style="color:red;font-weight:bold;">' . __( 'Role ID must not be numbers only!', 'woocommerce-jetpack') . '</p>';
				} else {
					$result = add_role( $_POST['wcj_custom_role_id'], $_POST['wcj_custom_role_name'] );
					if ( null !== $result ) {
						echo '<p style="color:green;font-weight:bold;">' . __( 'Role successfully added!', 'woocommerce-jetpack') . '</p>';
					} else {
						echo '<p style="color:red;font-weight:bold;">' . __( 'Role already exists!', 'woocommerce-jetpack') . '</p>';
					}
				}
			}
		}

		if ( isset( $_GET['wcj_delete_role'] ) && '' != $_GET['wcj_delete_role'] ) {
			remove_role( $_GET['wcj_delete_role'] );
			echo '<p style="color:green;font-weight:bold;">' . sprintf( __( 'Role %s successfully deleted!', 'woocommerce-jetpack'), $_GET['wcj_delete_role'] ) . '</p>';
		}

		echo $this->get_tool_header_html( 'custom_roles' );

		$table_data = array();
		$table_data[] = array( __( 'ID', 'woocommerce-jetpack'), __( 'Name', 'woocommerce-jetpack'), __( 'Actions', 'woocommerce-jetpack'), );
		$existing_roles = $this->get_user_roles();
		$default_wp_wc_roles = array( 'guest', 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager', );
		foreach ( $existing_roles as $role_key => $role_data ) {
			$delete_html = ( in_array( $role_key, $default_wp_wc_roles ) )
				? ''
				: '<a href="' . add_query_arg( 'wcj_delete_role', $role_key ). '">' . __( 'Delete', 'woocommerce-jetpack') . '</a>';
			$table_data[] = array( $role_key, $role_data['name'], $delete_html );
//			$table_data[] = array( $role_key, $role_data['name'], http_build_query( $role_data['capabilities'], '', ', ' ) );
		}
		echo '<h3>' . __( 'Existing Roles', 'woocommerce-jetpack') . '</h3>';
		echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );

		$table_data = array();
		$table_data[] = array( __( 'ID', 'woocommerce-jetpack'),   '<input type="text" name="wcj_custom_role_id">' );
		$table_data[] = array( __( 'Name', 'woocommerce-jetpack'), '<input type="text" name="wcj_custom_role_name">' );
		echo '<h3>' . __( 'Add New Role', 'woocommerce-jetpack') . '</h3>';
		echo '<form method="post" action="' . remove_query_arg( 'wcj_delete_role' ) . '">' .
			wcj_get_table_html( $table_data, array( 'table_class' => 'widefat', 'table_heading_type' => 'vertical', 'table_style' => 'width:20%;min-width:300px;', ) )
			. '<p>' . '<input type="submit" name="wcj_add_new_role" class="button-primary" value="' . __( 'Add', 'woocommerce-jetpack' ) . '">' . '</p>'
			. '</form>';
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
		$all_roles = array_merge( array(
			'guest' => array(
				'name'         => __( 'Guest', 'woocommerce-jetpack' ),
				'capabilities' => array(),
			) ), $all_roles );
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

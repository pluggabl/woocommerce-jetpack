<?php
/**
 * WooCommerce Jetpack Price by User Role
 *
 * The WooCommerce Jetpack Price by User Role class.
 *
 * @version 2.5.0
 * @since   2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_By_User_Role' ) ) :

class WCJ_Price_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function __construct() {

		$this->id         = 'price_by_user_role';
		$this->short_desc = __( 'Price by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products prices by user roles.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-price-by-user-role/';
		parent::__construct();

		$this->add_tools( array(
			'custom_roles' => array(
				'title'     => __( 'Add/Manage Custom Roles', 'woocommerce-jetpack' ),
				'tab_title' => __( 'Custom Roles', 'woocommerce-jetpack' ),
				'desc'      => __( 'Manage Custom Roles.', 'woocommerce-jetpack' ),
			),
		) );

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$this->add_hooks();
			}
			add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
			add_action( 'admin_notices',           array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * save_meta_box_value.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function save_meta_box_value( $option_value, $option_name, $module_id ) {
		if ( true === apply_filters( 'wcj_get_option_filter', false, true ) ) {
			return $option_value;
		}
		if ( 'no' === $option_value ) {
			return $option_value;
		}
		if ( $this->id === $module_id && 'wcj_price_by_user_role_per_product_settings_enabled' === $option_name ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_' . 'wcj_price_by_user_role_per_product_settings_enabled',
				'meta_value'     => 'yes',
				'post__not_in'   => array( get_the_ID() ),
			);
			$loop = new WP_Query( $args );
			$c = $loop->found_posts + 1;
			if ( $c >= 2 ) {
				add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
				return 'no';
			}
		}
		return $option_value;
	}

	/**
	 * add_notice_query_var.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
		return add_query_arg( array( 'wcj_product_price_by_user_role_admin_notice' => true ), $location );
	}

	/**
	 * admin_notices.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function admin_notices() {
		if ( ! isset( $_GET['wcj_product_price_by_user_role_admin_notice'] ) ) {
			return;
		}
		?><div class="error"><p><?php
			echo '<div class="message">'
				. __( 'Booster: Free plugin\'s version is limited to only one price by user role per products settings product enabled at a time. You will need to get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to add unlimited number of price by user role per product settings products.', 'woocommerce-jetpack' )
				. '</div>';
		?></p></div><?php
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_meta_box_options() {
		$main_product_id = get_the_ID();
		$_product = wc_get_product( $main_product_id );
		$products = array();
		if ( $_product->is_type( 'variable' ) ) {
			$available_variations = $_product->get_available_variations();
			foreach ( $available_variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );
				$products[ $variation['variation_id'] ] = ' (' . $variation_product->get_formatted_variation_attributes( true ) . ')';
			}
		} else {
			$products[ $main_product_id ] = '';
		}
		$options = array(
			array(
				'type'       => 'title',
				'title'      => __( 'Per Product Settings (press Update after changing)', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_price_by_user_role_per_product_settings_enabled',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Enabled', 'woocommerce-jetpack' ),
			),
		);
		if ( 'yes' === get_post_meta( $_product->id, '_' . 'wcj_price_by_user_role_per_product_settings_enabled', true ) ) {
			foreach ( $products as $product_id => $desc ) {
				foreach ( $this->get_user_roles() as $role_key => $role_data ) {
					$options = array_merge( $options, array(
						array(
							'type'       => 'title',
							'title'      => '<em>' /* . __( 'Role', 'woocommerce-jetpack' ) . ': ' */ . $role_data['name'] . '</em>',
						),
						array(
							'name'       => 'wcj_price_by_user_role_regular_price_' . $role_key . '_' . $product_id,
							'default'    => '',
							'type'       => 'price',
							'title'      => /* '[' . $role_data['name'] . '] ' .  */__( 'Regular Price', 'woocommerce-jetpack' ),
							'desc'       => $desc,
							'product_id' => $product_id,
							'meta_name'  => '_' . 'wcj_price_by_user_role_regular_price_' . $role_key,
						),
						array(
							'name'       => 'wcj_price_by_user_role_sale_price_' . $role_key . '_' . $product_id,
							'default'    => '',
							'type'       => 'price',
							'title'      => /* '[' . $role_data['name'] . '] ' .  */__( 'Sale Price', 'woocommerce-jetpack' ),
							'desc'       => $desc,
							'product_id' => $product_id,
							'meta_name'  => '_' . 'wcj_price_by_user_role_sale_price_' . $role_key,
						),
					) );
				}
			}
		}
		return $options;
	}

	/**
	 * create_custom_roles_tool.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
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
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_hooks() {
		// Prices
		add_filter( 'woocommerce_get_price',                      array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_get_sale_price',                 array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_get_regular_price',              array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		// Variations
		add_filter( 'woocommerce_variation_prices_price',         array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_variation_prices_sale_price',    array( $this, 'change_price_by_role' ), PHP_INT_MAX - 200, 2 );
		add_filter( 'woocommerce_get_variation_prices_hash',      array( $this, 'get_variation_prices_hash' ), PHP_INT_MAX - 200, 3 );
		// Shipping
		add_filter( 'woocommerce_package_rates',                  array( $this, 'change_price_by_role_shipping' ), PHP_INT_MAX - 200, 2 );
		// Grouped products
		add_filter( 'woocommerce_get_price_including_tax',        array( $this, 'change_price_by_role_grouped' ), PHP_INT_MAX - 200, 3 );
		add_filter( 'woocommerce_get_price_excluding_tax',        array( $this, 'change_price_by_role_grouped' ), PHP_INT_MAX - 200, 3 );
	}

	/**
	 * change_price_by_role_shipping.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function change_price_by_role_shipping( $package_rates, $package ) {
		if ( 'yes' === get_option( 'wcj_price_by_user_role_shipping_enabled', 'no' ) ) {
			$current_user_role = $this->get_current_user_role();
			$koef = get_option( 'wcj_price_by_user_role_' . $current_user_role, 1 );
			$modified_package_rates = array();
			foreach ( $package_rates as $id => $package_rate ) {
				if ( 1 != $koef && isset( $package_rate->cost ) ) {
					$package_rate->cost = $package_rate->cost * $koef;
					if ( isset( $package_rate->taxes ) && ! empty( $package_rate->taxes ) ) {
						foreach ( $package_rate->taxes as $tax_id => $tax ) {
							$package_rate->taxes[ $tax_id ] = $package_rate->taxes[ $tax_id ] * $koef;
						}
					}
				}
				$modified_package_rates[ $id ] = $package_rate;
			}
		}
		return $modified_package_rates;
	}

	/**
	 * change_price_by_role_grouped.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function change_price_by_role_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			if ( 'yes' === get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
				$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
				foreach ( $_product->get_children() as $child_id ) {
					$the_price = get_post_meta( $child_id, '_price', true );
					$the_product = wc_get_product( $child_id );
					$the_price = $the_product->$get_price_method( 1, $the_price );
					if ( $the_price == $price ) {
						return $this->change_price_by_role( $price, $the_product );
					}
				}
			} else {
				return $this->change_price_by_role( $price, null );
			}
		}
		return $price;
	}

	/**
	 * change_price_by_role.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function change_price_by_role( $price, $_product ) {

		$current_user_role = $this->get_current_user_role();

		// Per product
		if ( 'yes' === get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
			if ( 'yes' === get_post_meta( $_product->id, '_' . 'wcj_price_by_user_role_per_product_settings_enabled', true ) ) {
				$the_product_id = ( isset( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
				if ( '' != ( $regular_price_per_product = get_post_meta( $the_product_id, '_' . 'wcj_price_by_user_role_regular_price_' . $current_user_role, true ) ) ) {
					$the_current_filter = current_filter();
					if ( 'woocommerce_get_price_including_tax' == $the_current_filter || 'woocommerce_get_price_excluding_tax' == $the_current_filter ) {
						$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
						return $_product->$get_price_method();
					} elseif ( 'woocommerce_get_price' == $the_current_filter || 'woocommerce_variation_prices_price' == $the_current_filter ) {
						$sale_price_per_product = get_post_meta( $the_product_id, '_' . 'wcj_price_by_user_role_sale_price_' . $current_user_role, true );
						return ( '' != $sale_price_per_product && $sale_price_per_product < $regular_price_per_product ) ? $sale_price_per_product : $regular_price_per_product;
					} elseif ( 'woocommerce_get_regular_price' == $the_current_filter || 'wcj_price_by_user_role_regular_price_' == $the_current_filter ) {
						return $regular_price_per_product;
					} elseif ( 'woocommerce_get_sale_price' == $the_current_filter || 'woocommerce_variation_prices_sale_price' == $the_current_filter ) {
						$sale_price_per_product = get_post_meta( $the_product_id, '_' . 'wcj_price_by_user_role_sale_price_' . $current_user_role, true );
						return ( '' != $sale_price_per_product ) ? $sale_price_per_product : $price;
					}
				}
			}
		}

		// Global
		if ( 1 != ( $koef = get_option( 'wcj_price_by_user_role_' . $current_user_role, 1 ) ) ) {
			return ( '' === $price ) ? $price : $price * $koef;
		}

		// No changes
		return $price;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$user_role = $this->get_current_user_role();
		$koef = get_option( 'wcj_price_by_user_role_' . $user_role, 1 );
		$price_hash['wcj_user_role'] = array(
			$user_role,
			$koef,
			get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ),
		);
		return $price_hash;
	}

	/**
	 * get_current_user_role.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_current_user_role() {
		$current_user = wp_get_current_user();
		return ( isset( $current_user->roles[0] ) && '' != $current_user->roles[0] ) ? $current_user->roles[0] : 'guest';
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
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
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_settings_hook() {
		add_filter( 'wcj_price_by_user_role_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_settings() {
		$settings = apply_filters( 'wcj_price_by_user_role_settings', array() );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_settings() {
		$settings = array();
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_price_by_user_role_options',
			),
			array(
				'title'    => __( 'Enable per Product Settings', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'When enabled, this will add new "Booster: Price by User Role" meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
				'type'     => 'checkbox',
				'id'       => 'wcj_price_by_user_role_per_product_enabled',
				'default'  => 'yes',
			),
			array(
				'title'    => __( 'Shipping', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'When enabled, this will apply user role multipliers to shipping calculations.', 'woocommerce-jetpack' ),
				'type'     => 'checkbox',
				'id'       => 'wcj_price_by_user_role_shipping_enabled',
				'default'  => 'no',
			),
			array(
				'type'         => 'sectionend',
				'id'           => 'wcj_price_by_user_role_options',
			),
		) );
		$settings[] = array(
			'title'        => __( 'Roles & Multipliers', 'woocommerce-jetpack' ),
			'type'         => 'title',
			'id'           => 'wcj_price_by_user_role_multipliers_options',
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
			'id'           => 'wcj_price_by_user_role_multipliers_options',
		);
		return $settings;
	}
}

endif;

return new WCJ_Price_By_User_Role();

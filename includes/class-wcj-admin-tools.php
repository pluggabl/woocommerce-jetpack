<?php
/**
 * Booster for WooCommerce - Module - Admin Tools
 *
 * @version 5.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Admin_Tools' ) ) :

class WCJ_Admin_Tools extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @todo    [feature] (maybe) add editable (product and order) metas
	 */
	function __construct() {

		$this->id         = 'admin_tools';
		$this->short_desc = __( 'Admin Tools', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster for WooCommerce general back-end tools. Enable interface by user roles (Plus). Custom shop manager editable roles (Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Booster for WooCommerce general back-end tools.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-booster-admin-tools';
		parent::__construct();

		$this->add_tools( array(
			'products_atts'    => array(
				'title'     => __( 'Products Attributes', 'woocommerce-jetpack' ),
				'desc'      => __( 'All Products and All Attributes.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {
			// Order Meta
			if ( 'yes' === wcj_get_option( 'wcj_admin_tools_show_order_meta_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes', array( $this, 'add_order_meta_meta_box' ) );
			}
			// Product Meta
			if ( 'yes' === wcj_get_option( 'wcj_admin_tools_show_product_meta_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes', array( $this, 'add_product_meta_meta_box' ) );
			}
			// Variable Product Pricing
			if ( 'yes' === wcj_get_option( 'wcj_admin_tools_variable_product_pricing_table_enabled', 'no' ) ) {
				add_action( 'admin_head',        array( $this, 'make_original_variable_product_pricing_readonly' ) );
				add_action( 'add_meta_boxes',    array( $this, 'maybe_add_variable_product_pricing_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
			// Product revisions
			if ( 'yes' === wcj_get_option( 'wcj_product_revisions_enabled', 'no' ) ) {
				add_filter( 'woocommerce_register_post_type_product', array( $this, 'enable_product_revisions' ) );
			}
			// Admin Notices
			if ( 'yes' === wcj_get_option( 'wcj_admin_tools_suppress_connect_notice', 'no' ) ) {
				add_filter( 'woocommerce_helper_suppress_connect_notice', '__return_true' );
			}
			if ( 'yes' === wcj_get_option( 'wcj_admin_tools_suppress_admin_notices', 'no' ) ) {
				add_filter( 'woocommerce_helper_suppress_admin_notices', '__return_true' );
			}
			// JSON product search limit
			if ( 0 != wcj_get_option( 'wcj_product_json_search_limit', 0 ) ) {
				add_filter( 'woocommerce_json_search_limit', array( $this, 'set_json_search_limit' ) );
			}
			// Enable interface by user role
			add_filter( 'wcj_can_create_admin_interface', array( $this, 'enable_interface_by_user_roles' ) );
			// Shop Manager Editable Roles
			add_filter( 'woocommerce_shop_manager_editable_roles', array( $this, 'change_shop_manager_editable_roles' ) );
		}
	}

	/**
	 * change_shop_manager_editable_roles.
	 *
	 * @see wc_modify_editable_roles()
	 *
	 * @version 4.9.0
	 * @since   4.9.0
	 *
	 * @param $roles
	 *
	 * @return mixed
	 */
	function change_shop_manager_editable_roles( $roles ) {
		remove_filter( 'woocommerce_shop_manager_editable_roles', array( $this, 'change_shop_manager_editable_roles' ) );
		$roles = wcj_get_option( 'wcj_admin_tools_shop_manager_editable_roles', apply_filters( 'woocommerce_shop_manager_editable_roles', array( 'customer' ) ) );
		return $roles;
	}

	/**
	 * enable_interface_by_user_roles.
	 *
	 * @version 4.8.0
	 * @since   4.8.0
	 *
	 * @param $allowed
	 *
	 * @return bool
	 */
	function enable_interface_by_user_roles( $allowed ) {
		if ( empty( $disabled_roles = wcj_get_option( 'wcj_admin_tools_enable_interface_by_role', array() ) ) ) {
			return $allowed;
		}
		$current_user_roles = wcj_get_current_user_all_roles();
		if (
			! in_array( 'administrator', $current_user_roles ) &&
			! array_intersect( $disabled_roles, $current_user_roles )
		) {
			$allowed = false;
		}
		return $allowed;
	}

	/**
	 * set_json_search_limit.
	 *
	 * @version 4.1.0
	 * @since   4.1.0
	 */
	function set_json_search_limit( $limit ) {
		return wcj_get_option( 'wcj_product_json_search_limit', 0 );
	}

	/**
	 * enable_product_revisions.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function enable_product_revisions( $args ) {
		$args['supports'][] = 'revisions';
		return $args;
	}

	/**
	 * make_original_variable_product_pricing_readonly.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    [fix] this is not really making fields readonly (e.g. field is still editable via keyboard tab button)
	 */
	function make_original_variable_product_pricing_readonly() {
		echo '<style>
			div.variable_pricing input.wc_input_price {
				pointer-events: none;
			}
		</style>';
	}

	/**
	 * maybe_add_variable_product_pricing_meta_box.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function maybe_add_variable_product_pricing_meta_box() {
		if ( ( $_product = wc_get_product() ) && $_product->is_type( 'variable' ) ) {
			parent::add_meta_box();
		}
	}

	/**
	 * add_product_meta_meta_box.
	 *
	 * @version 2.5.8
	 * @since   2.5.8
	 */
	function add_product_meta_meta_box() {
		add_meta_box(
			'wcj-admin-tools-product-meta',
			__( 'Product Meta', 'woocommerce-jetpack' ),
			array( $this, 'create_meta_meta_box' ),
			'product',
			'normal',
			'low'
		);
	}

	/**
	 * add_order_meta_meta_box.
	 *
	 * @version 2.5.8
	 * @since   2.5.8
	 */
	function add_order_meta_meta_box() {
		add_meta_box(
			'wcj-admin-tools-order-meta',
			__( 'Order Meta', 'woocommerce-jetpack' ),
			array( $this, 'create_meta_meta_box' ),
			'shop_order',
			'normal',
			'low'
		);
	}

	/**
	 * create_meta_meta_box.
	 *
	 * @version 3.2.1
	 * @since   2.5.8
	 */
	function create_meta_meta_box( $post ) {
		$html    = '';
		$post_id = get_the_ID();
		// Meta
		$meta = get_post_meta( $post_id );
		$table_data = array();
		foreach ( $meta as $meta_key => $meta_values ) {
			$table_data[] = array( $meta_key, esc_html( print_r( maybe_unserialize( $meta_values[0] ), true ) ) );
		}
		$html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical' ) );
		// Items Meta (for orders only)
		if ( 'shop_order' === $post->post_type ) {
			$_order = wc_get_order( $post_id );
			$table_data = array();
			foreach ( $_order->get_items() as $item_key => $item ) {
				foreach ( $item['item_meta'] as $item_meta_key => $item_meta_value ) {
					$table_data[] = array( $item_key, $item_meta_key, esc_html( print_r( maybe_unserialize( $item_meta_value ), true ) ) );
				}
			}
			if ( ! empty( $table_data ) ) {
				$html .= '<h3>' . __( 'Order Items Meta', 'woocommerce-jetpack' ) . '</h3>';
				$table_data = array_merge(
					array( array( __( 'Item Key', 'woocommerce-jetpack' ), __( 'Item Meta Key', 'woocommerce-jetpack' ), __( 'Item Meta Value', 'woocommerce-jetpack' ) ) ),
					$table_data
				);
				$html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'horizontal' ) );
			}
		}
		// Output
		echo $html;
	}

	/**
	 * create_products_atts_tool.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function create_products_atts_tool() {
		$html = '';
		$html .= $this->get_products_atts();
		echo $html;
	}

	/*
	 * get_products_atts.
	 *
	 * @version 4.0.0
	 * @since   2.3.9
	 * @todo    [dev] rewrite; add module link;
	 */
	function get_products_atts() {

		$total_products = 0;

		$products_attributes = array();
		$attributes_names = array();
		$attributes_names['wcj_title']    = __( 'Product', 'woocommerce-jetpack' );
		$attributes_names['wcj_category'] = __( 'Category', 'woocommerce-jetpack' );

		$offset = 0;
		$block_size = 96;
		while( true ) {

			$args_products = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $block_size,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'offset'         => $offset,
			);
			$loop_products = new WP_Query( $args_products );
			if ( ! $loop_products->have_posts() ) break;
			while ( $loop_products->have_posts() ) : $loop_products->the_post();

				$total_products++;
				$product_id = $loop_products->post->ID;
				$the_product = wc_get_product( $product_id );

				$products_attributes[ $product_id ]['wcj_title']    = '<a href="' . get_permalink( $product_id ) . '">' . $the_product->get_title() . '</a>';
				$products_attributes[ $product_id ]['wcj_category'] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_product->get_categories() : wc_get_product_category_list( $product_id ) );

				foreach ( $the_product->get_attributes() as $attribute ) {
					$products_attributes[ $product_id ][ $attribute['name'] ] = $the_product->get_attribute( $attribute['name'] );
					if ( ! isset( $attributes_names[ $attribute['name'] ] ) ) {
						$attributes_names[ $attribute['name'] ] = wc_attribute_label( $attribute['name'] );
					}
				}

			endwhile;

			$offset += $block_size;

		}

		$table_data = array();
		if ( isset( $_GET['wcj_attribute'] ) && '' != $_GET['wcj_attribute'] ) {
			$table_data[] = array(
				__( 'Product', 'woocommerce-jetpack' ),
				__( 'Category', 'woocommerce-jetpack' ),
				$_GET['wcj_attribute'],
			);
		} else {
			$header = $attributes_names;
			unset( $header['wcj_title'] );
			unset( $header['wcj_category'] );
			$table_data[] = array_merge( array(
				__( 'Product', 'woocommerce-jetpack' ),
				__( 'Category', 'woocommerce-jetpack' ),
				), array_keys( $header ) );
		}
		foreach ( $attributes_names as $attributes_name => $attribute_title ) {

			if ( isset( $_GET['wcj_attribute'] ) && '' != $_GET['wcj_attribute'] ) {
				if ( 'wcj_title' != $attributes_name && 'wcj_category' != $attributes_name && $_GET['wcj_attribute'] != $attributes_name ) {
					continue;
				}
			}

			foreach ( $products_attributes as $product_id => $product_attributes ) {
				$table_data[ $product_id ][ $attributes_name ] = isset( $product_attributes[ $attributes_name ] ) ? $product_attributes[ $attributes_name ] : '';
			}
		}

		return '<p>' . __( 'Total Products:', 'woocommerce-jetpack' ) . ' ' . $total_products . '</p>' . wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );
	}

}

endif;

return new WCJ_Admin_Tools();

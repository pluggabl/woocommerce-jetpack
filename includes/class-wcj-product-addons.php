<?php
/**
 * WooCommerce Jetpack Product Addons
 *
 * The WooCommerce Jetpack Product Addons class.
 *
 * @version 2.5.3
 * @since   2.5.3
 * @author  Algoritmika Ltd.
 * @todo    +per product (free limit); ajax; admin order view; description and docs url;
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Addons' ) ) :

class WCJ_Product_Addons extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function __construct() {

		$this->id         = 'product_addons';
		$this->short_desc = __( 'Product Addons', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add (paid/free/discount) addons to WooCommerce products.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-addons/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_product_addons_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',          array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product',       array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices',           array( $this, 'admin_notices' ) );
			}
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				// Single Page
				add_action( 'woocommerce_before_add_to_cart_button',      array( $this, 'add_addons_to_frontend' ), PHP_INT_MAX );
				// Add to cart
				add_filter( 'woocommerce_add_cart_item_data',             array( $this, 'add_addons_price_to_cart_item_data' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_add_cart_item',                  array( $this, 'add_addons_price_to_cart_item' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_cart_item_from_session',     array( $this, 'get_cart_item_addons_price_from_session' ), PHP_INT_MAX, 3 );
				// Prices
				add_filter( 'woocommerce_get_price',                      array( $this, 'change_price' ), PHP_INT_MAX - 100, 2 );
				// Show details at cart, order details, emails
				add_filter( 'woocommerce_cart_item_name',                 array( $this, 'add_info_to_cart_item_name' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_order_item_name',                array( $this, 'add_info_to_order_item_name' ), PHP_INT_MAX, 2 );
				add_action( 'woocommerce_add_order_item_meta',            array( $this, 'add_info_to_order_item_meta' ), PHP_INT_MAX, 3 );
			}
		}
	}

	/**
	 * get_product_addons.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_product_addons( $product_id ) {
		$addons = array();
		// All Products
		if ( 'yes' === get_option( 'wcj_product_addons_all_products_enabled', 'no' ) ) {
			$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === get_option( 'wcj_product_addons_all_products_enabled_' . $i, 'yes' ) ) {
					$addons[] = array(
//						'scope'        => 'all_products',
//						'index'        => $i,
						'checkbox_key' => 'wcj_product_all_products_addons_' . $i,
						'price_key'    => 'wcj_product_all_products_addons_price_' . $i,
						'label_key'    => 'wcj_product_all_products_addons_label_' . $i,
						'price_value'  => get_option( 'wcj_product_addons_all_products_price_' . $i ),
						'label_value'  => get_option( 'wcj_product_addons_all_products_label_' . $i ),
					);
				}
			}
		}
		// Per product
		if ( 'yes' === get_option( 'wcj_product_addons_per_product_enabled', 'no' ) ) {
			if ( 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_settings_enabled', true ) ) {
				$total_number = get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_total_number', true );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_enabled_' . $i, true ) ) {
						$addons[] = array(
//							'scope'        => 'per_product',
//							'index'        => $i,
							'checkbox_key' => 'wcj_product_per_product_addons_' . $i,
							'price_key'    => 'wcj_product_per_product_addons_price_' . $i,
							'label_key'    => 'wcj_product_per_product_addons_label_' . $i,
							'price_value'  => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_price_' . $i, true ),
							'label_value'  => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_label_' . $i, true ),
						);
					}
				}
			}
		}
		return $addons;
	}

	/**
	 * add_info_to_order_item_meta.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_info_to_order_item_meta( $item_id, $values, $cart_item_key  ) {
		$addons = $this->get_product_addons( $values['product_id'] );
		foreach ( $addons as $addon ) {
			if ( isset( $values[ $addon['price_key'] ] ) ) {
				wc_add_order_item_meta( $item_id, '_' . $addon['price_key'], $values[ $addon['price_key'] ] );
				wc_add_order_item_meta( $item_id, '_' . $addon['label_key'], $values[ $addon['label_key'] ] );
			}
		}
	}

	/**
	 * Adds info to order details (and emails).
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_info_to_order_item_name( $name, $item, $is_cart = false ) {
		if ( $is_cart ) {
			$name .= '<dl class="variation">';
		}
		$addons = $this->get_product_addons( $item['product_id'] );
		foreach ( $addons as $addon ) {
			if ( isset( $item[ $addon['price_key'] ] ) ) {
				if ( $is_cart ) {
					$name .= '<dt>' . $item[ $addon['label_key'] ] . ':' . '</dt>';
					$name .= '<dd>' . wc_price( $item[ $addon['price_key'] ] ) . '</dd>';
				} else {
					$name .= ' | ' . $item[ $addon['label_key'] ] . ': ' . wc_price( $item[ $addon['price_key'] ] );
				}
			}
		}
		if ( $is_cart ) {
			$name .= '</dl>';
		}
		return $name;
	}

	/**
	 * Adds info to cart item details.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_info_to_cart_item_name( $name, $cart_item, $cart_item_key  ) {
		return $this->add_info_to_order_item_name( $name, $cart_item, true );
	}

	/**
	 * change_price.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function change_price( $price, $_product ) {
		$addons = $this->get_product_addons( $_product->id );
		foreach ( $addons as $addon ) {
			if ( isset( $_product->$addon['price_key'] ) ) {
				$price += $_product->$addon['price_key'];
			}
		}
		return $price;
	}

	/**
	 * add_addons_price_to_cart_item.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_addons_price_to_cart_item( $cart_item_data, $cart_item_key ) {
		$addons = $this->get_product_addons( $cart_item_data['data']->product_id );
		foreach ( $addons as $addon ) {
			if ( isset( $cart_item_data[ $addon['price_key'] ] ) ) {
				$cart_item_data['data']->$addon['price_key'] = $cart_item_data[ $addon['price_key'] ];
			}
		}
		return $cart_item_data;
	}

	/**
	 * get_cart_item_addons_price_from_session.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_cart_item_addons_price_from_session( $item, $values, $addon ) {
		$addons = $this->get_product_addons( $item['product_id'] );
		foreach ( $addons as $addon ) {
			if ( array_key_exists( $addon['price_key'], $values ) ) {
				$item['data']->$addon['price_key'] = $values[ $addon['price_key'] ];
			}
		}
		return $item;
	}

	/**
	 * add_addons_price_to_cart_item_data.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_addons_price_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$addons = $this->get_product_addons( $product_id );
		foreach ( $addons as $addon ) {
			if ( isset( $_POST[ $addon['checkbox_key'] ] ) ) {
				$cart_item_data[ $addon['price_key'] ] = $addon['price_value'];
				$cart_item_data[ $addon['label_key'] ] = $addon['label_value'];
			}
		}
		return $cart_item_data;
	}

	/**
	 * add_addons_to_frontend.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_addons_to_frontend() {
		$html = '';
		$addons = $this->get_product_addons( get_the_ID() );
		foreach ( $addons as $addon ) {
			$is_checked = isset( $_POST[ $addon['checkbox_key'] ] ) ? ' checked' : '';
			$html .= '<p>' .
					'<input type="checkbox" id="' . $addon['checkbox_key'] . '" name="' . $addon['checkbox_key'] . '"' . $is_checked . '>' . ' ' .
					'<label for="' . $addon['checkbox_key'] . '">' . $addon['label_value'] . ' ('. wc_price( $addon['price_value'] ) . ')' . '</label>' .
				'</p>';
		}
		// Output
		if ( ! empty( $html ) ) {
			echo '<div id="wcj_product_addons">' . $html . '</div>';
		}
	}

	/**
	 * save_meta_box_value.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function save_meta_box_value( $option_value, $option_name, $module_id ) {
		if ( true === apply_filters( 'wcj_get_option_filter', false, true ) ) {
			return $option_value;
		}
		if ( 'no' === $option_value ) {
			return $option_value;
		}
		if ( $this->id === $module_id && 'wcj_product_addons_per_product_settings_enabled' === $option_name ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_' . 'wcj_product_addons_per_product_settings_enabled',
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
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
		return add_query_arg( array( 'wcj_product_addons_admin_notice' => true ), $location );
	}

	/**
	 * admin_notices.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function admin_notices() {
		if ( ! isset( $_GET['wcj_product_addons_admin_notice'] ) ) {
			return;
		}
		?><div class="error"><p><?php
			echo '<div class="message">'
				. __( 'Booster: Free plugin\'s version is limited to only one product with per product addons enabled at a time. You will need to get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to add unlimited number of products with per product addons.', 'woocommerce-jetpack' )
				. '</div>';
		?></p></div><?php
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_product_addons_per_product_settings_enabled',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Enabled', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_product_addons_per_product_total_number',
				'default'    => 0,
				'type'       => 'number',
				'title'      => __( 'Product Addons Total Number', 'woocommerce-jetpack' ),
			),
		);
		$total_number = get_post_meta( get_the_ID(), '_' . 'wcj_product_addons_per_product_total_number', true );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$options = array_merge( $options, array(
				array(
					'title'    => __( 'Product Addon', 'woocommerce-jetpack' ) . ' #' . $i . ' - ' . __( 'Enable', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_enabled_' . $i,
					'default'  => 'yes',
					'type'     => 'select',
					'options'  => array(
						'yes' => __( 'Yes', 'woocommerce-jetpack' ),
						'no'  => __( 'No', 'woocommerce-jetpack' ),
					),
				),
				array(
					'title'    => __( 'Label', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_label_' . $i,
					'default'  => '',
					'type'     => 'text',
				),
				array(
					'title'    => __( 'Price', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_price_' . $i,
					'default'  => 0,
					'type'     => 'price',
				),
			) );
		}
		return $options;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_settings() {
		$settings = array();
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Per Product Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_addons_per_product_options',
			),
			array(
				'title'    => __( 'Enable per Product Addons', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'When enabled, this will add new "Booster: Product Addons" meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_addons_per_product_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_addons_per_product_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'All Product Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_addons_all_products_options',
			),
			array(
				'title'    => __( 'Enable All Products Addons', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'When enabled, this will add addons below to all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_addons_all_products_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Product Addons Total Number', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_addons_all_products_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '0', )
				),
			),
		) );
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Product Addon', 'woocommerce-jetpack' ) . ' #' . $i,
					'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_enabled_' . $i,
					'default'  => 'yes',
					'type'     => 'checkbox',
				),
				array(
					'desc'     => __( 'Label', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_label_' . $i,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Price', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_price_' . $i,
					'default'  => 0,
					'type'     => 'number',
					'css'      => 'width:300px;',
					'custom_attributes' => array( 'step' => '0.0001' ),
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_addons_all_products_options',
			),
		) );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Addons();

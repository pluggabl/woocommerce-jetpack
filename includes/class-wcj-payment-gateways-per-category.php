<?php
/**
 * WooCommerce Jetpack Payment Gateways per Category
 *
 * The WooCommerce Jetpack Payment Gateways per Category class.
 *
 * @version 2.5.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_Per_Category' ) ) :

class WCJ_Payment_Gateways_Per_Category extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.7
	 */
	function __construct() {

		$this->id         = 'payment_gateways_per_category';
		$this->short_desc = __( 'Gateways per Product or Category', 'woocommerce-jetpack' );
		$this->desc       = __( 'Show WooCommerce gateway only if there is selected product or product category in cart.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-gateways-per-product-or-category/';
		parent::__construct();

		add_filter( 'init',  array( $this, 'add_hooks' ) );

		if ( $this->is_enabled() ) {
//			add_filter( 'woocommerce_payment_gateways_settings',  array( $this, 'add_per_category_settings' ), 100 );
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_available_payment_gateways_per_category' ), 100 );
		}
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.7
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_payment_gateways_per_category_settings', $settings );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_hooks.
	 */
	function add_hooks() {
		add_filter( 'wcj_payment_gateways_per_category_settings', array( $this, 'add_per_category_settings' ) );
	}

	/**
	 * filter_available_payment_gateways_per_category.
	 *
	 * @version 2.4.7
	 */
	function filter_available_payment_gateways_per_category( $available_gateways ) {

//		if ( ! is_checkout() ) return $available_gateways;

		if ( ! isset( WC()->cart ) ) return $available_gateways;

		foreach ( $available_gateways as $gateway_id => $gateway ) {

			// Including by categories
			$categories_in = get_option( 'wcj_gateways_per_category_' . $gateway_id );
			if ( ! empty( $categories_in ) ) {
				$do_skip = true;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$product_categories = get_the_terms( $values['product_id'], 'product_cat' );
					if ( empty( $product_categories ) ) continue; // ... to next product in the cart
					foreach( $product_categories as $product_category ) {
						if ( in_array( $product_category->term_id, $categories_in ) ) {
							// Current gateway is OK, breaking to check next gateway (no need to check other categories of the product)
							$do_skip = false;
							break;
						}
					}
					if ( ! $do_skip ) {
						// Current gateway is OK, breaking to check next gateway (no need to check other products in the cart)
						break;
					}
				}
				if ( $do_skip ) {
					// Skip (i.e. hide/unset) current gateway - no products of needed categories found in the cart
					if ( isset( $available_gateways[ $gateway_id ] ) ) unset( $available_gateways[ $gateway_id ] );
					continue; // ... to next gateway
				}
			}

			// Excluding by categories
			$categories_excl = get_option( 'wcj_gateways_per_category_excl_' . $gateway_id );
			if ( ! empty( $categories_excl ) ) {
				$do_skip = false;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$product_categories = get_the_terms( $values['product_id'], 'product_cat' );
					if ( empty( $product_categories ) ) continue; // ... to next product in the cart
					foreach( $product_categories as $product_category ) {
						if ( in_array( $product_category->term_id, $categories_excl ) ) {
							// Skip (i.e. hide/unset) current gateway
							if ( isset( $available_gateways[ $gateway_id ] ) ) unset( $available_gateways[ $gateway_id ] );
							$do_skip = true;
							break;
						}
					}
					if ( $do_skip ) break;
				}
				if ( $do_skip ) {
					continue; // ... to next gateway
				}
			}

			// Including by products
			$products_in = apply_filters( 'wcj_get_option_filter', array(), get_option( 'wcj_gateways_per_products_' . $gateway_id ) );
			if ( ! empty( $products_in ) ) {
				$do_skip = true;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					if ( in_array( $values['product_id'], $products_in ) ) {
						// Current gateway is OK
						$do_skip = false;
						break;
					}
				}
				if ( $do_skip ) {
					// Skip (i.e. hide/unset) current gateway
					if ( isset( $available_gateways[ $gateway_id ] ) ) unset( $available_gateways[ $gateway_id ] );
					continue; // ... to next gateway
				}
			}

			// Excluding by products
			$products_excl = apply_filters( 'wcj_get_option_filter', array(), get_option( 'wcj_gateways_per_products_excl_' . $gateway_id ) );
			if ( ! empty( $products_excl ) ) {
				$do_skip = false;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					if ( in_array( $values['product_id'], $products_excl ) ) {
						// Skip (i.e. hide/unset) current gateway
						if ( isset( $available_gateways[ $gateway_id ] ) ) unset( $available_gateways[ $gateway_id ] );
						$do_skip = true;
						break;
					}
				}
				if ( $do_skip ) {
					continue; // ... to next gateway
				}
			}

		}

		return $available_gateways;
	}

	/**
	 * add_per_category_settings.
	 *
	 * @version 2.5.0
	 */
	function add_per_category_settings( $settings ) {

		$settings[] = array(
			'title' => __( 'Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_gateways_per_category_options',
		);

		$product_cats = array();
		$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		foreach ( $product_categories as $product_category ) {
			$product_cats[ $product_category->term_id ] = $product_category->name;
		}

		$products = wcj_get_products();

		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $gateway_id => $gateway ) {
			$settings[] = array(
				'title'     => $gateway->title,
				'desc'      => __( 'Product Categories - Include', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Show gateway only if there is product of selected category in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_per_category_' . $gateway_id,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $product_cats,
			);
			$settings[] = array(
				'title'     => '',
				'desc'      => __( 'Product Categories - Exclude', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Hide gateway if there is product of selected category in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_per_category_excl_' . $gateway_id,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $product_cats,
			);
			$settings[] = array(
				'title'     => '',
				'desc'      => __( 'Products - Include', 'woocommerce-jetpack' ) . '. ' . apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip'  => __( 'Show gateway only if there is selected products in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_per_products_' . $gateway_id,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $products,
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			);
			$settings[] = array(
				'title'     => '',
				'desc'      => __( 'Products - Exclude', 'woocommerce-jetpack' ) . '. ' . apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip'  => __( 'Hide gateway if there is selected products in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_per_products_excl_' . $gateway_id,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $products,
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			);
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wcj_gateways_per_category_options',
		);

		return $settings;
	}
}

endif;

return new WCJ_Payment_Gateways_Per_Category();

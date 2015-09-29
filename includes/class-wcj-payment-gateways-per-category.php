<?php
/**
 * WooCommerce Jetpack Payment Gateways per Category
 *
 * The WooCommerce Jetpack Payment Gateways per Category class.
 *
 * @version 2.2.2
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_Per_Category' ) ) :

class WCJ_Payment_Gateways_Per_Category extends WCJ_Module {

    /**
     * Constructor.
     */
    function __construct() {

		$this->id         = 'payment_gateways_per_category';
		$this->short_desc = __( 'Payment Gateways per Category', 'woocommerce-jetpack' );
		$this->desc       = __( 'Show gateway only if there is product of selected category in WooCommerce cart.', 'woocommerce-jetpack' );
		parent::__construct();

		add_filter( 'init',  array( $this, 'add_hooks' ) );

		if ( $this->is_enabled() ) {
			//add_filter( 'woocommerce_payment_gateways_settings',  array( $this, 'add_per_category_settings' ), 100 );
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_available_payment_gateways_per_category' ), 100 );
		}
	}

	/**
	 * get_settings.
	 */
	function get_settings() {
		$settings = array();

		$settings = apply_filters( 'wcj_payment_gateways_per_category_settings', $settings );

		return $this->add_enable_module_setting( $settings );
	}

    /**
     * add_hooks.
     */
    function add_hooks() {
		add_filter( 'wcj_payment_gateways_per_category_settings',  array( $this, 'add_per_category_settings' ) );
	}

    /**
     * filter_available_payment_gateways_per_category.
     */
    function filter_available_payment_gateways_per_category( $available_gateways ) {
		//if ( ! is_checkout() ) return $available_gateways;
		foreach ( $available_gateways as $gateway_id => $gateway ) {
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
					unset( $available_gateways[ $gateway_id ] );
				}
			}
		}
		return $available_gateways;
	}

    /**
     * add_per_category_settings.
     */
    function add_per_category_settings( $settings ) {

		$settings[] = array(
			'title' => __( 'WooCommerce Jetpack: Payment Gateways per Category Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			//'desc'  => __( '', 'woocommerce-jetpack' ),
			'id'    => 'wcj_gateways_per_category_options',
		);

		$product_cats = array();
		$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		foreach ( $product_categories as $product_category ) {
			$product_cats[ $product_category->term_id ] = $product_category->name;
		}

		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $gateway_id => $gateway ) {
			$settings[] = array(
				'title'    	=> $gateway->title,
				'desc'    	=> 'Product Categories',
				'desc_tip'	=> __( 'Show gateway only if there is product of selected category in cart.', 'woocommerce-jetpack' ),
				'id'       	=> 'wcj_gateways_per_category_' . $gateway_id,
				'default'  	=> '',
				'type'		=> 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $product_cats,
			);
		}

		$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_gateways_per_category_options' );

		return $settings;
    }
}

endif;

return new WCJ_Payment_Gateways_Per_Category();

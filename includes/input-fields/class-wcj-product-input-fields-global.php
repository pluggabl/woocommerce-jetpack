<?php
/**
 * Booster for WooCommerce - Product Input Fields - Global
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Input_Fields_Global' ) ) :

class WCJ_Product_Input_Fields_Global extends WCJ_Product_Input_Fields_Abstract {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 */
	function __construct() {

		$this->scope = 'global';

		if ( 'yes' === get_option( 'wcj_product_input_fields_global_enabled' ) ) {

			// Show fields at frontend
			add_action( 'woocommerce_before_add_to_cart_button',    array( $this, 'add_product_input_fields_to_frontend' ), 100 );

			// Process from $_POST to cart item data
			add_filter( 'woocommerce_add_to_cart_validation',       array( $this, 'validate_product_input_fields_on_add_to_cart' ), 100, 2 );
			add_filter( 'woocommerce_add_cart_item_data',           array( $this, 'add_product_input_fields_to_cart_item_data' ), 100, 3 );
			// from session
			add_filter( 'woocommerce_get_cart_item_from_session',   array( $this, 'get_cart_item_product_input_fields_from_session' ), 100, 3 );

			// Show details at cart, order details, emails
			if ( 'data' === get_option( 'wcj_product_input_fields_display_options', 'name' ) ) {
				add_filter( 'woocommerce_get_item_data',            array( $this, 'add_product_input_fields_to_cart_item_display_data' ), PHP_INT_MAX, 2 );
			} else {
				add_filter( 'woocommerce_cart_item_name',           array( $this, 'add_product_input_fields_to_cart_item_name' ), 100, 3 );
			}
			add_filter( 'woocommerce_order_item_name',              array( $this, 'add_product_input_fields_to_order_item_name' ), 100, 2 );

			// Add item meta from cart to order
			add_action( 'woocommerce_add_order_item_meta',          array( $this, 'add_product_input_fields_to_order_item_meta' ), 100, 3 );

			// Make nicer name for product input fields in order at backend (shop manager)
			//add_action( 'woocommerce_before_order_itemmeta',      array( $this, 'start_making_nicer_name_for_product_input_fields' ), 100, 3 );
			//add_action( 'woocommerce_before_order_itemmeta',      'ob_start' );
			//add_action( 'woocommerce_after_order_itemmeta',       array( $this, 'finish_making_nicer_name_for_product_input_fields' ), 100, 3 );
			add_action( 'woocommerce_after_order_itemmeta',         array( $this, 'output_custom_input_fields_in_admin_order' ), 100, 3 );
			if ( 'yes' === get_option( 'wcj_product_input_fields_make_nicer_name_enabled' ) ) {
				add_filter( 'woocommerce_hidden_order_itemmeta',    array( $this, 'hide_custom_input_fields_default_output_in_admin_order' ), 100 );
			}
			//add_filter( 'woocommerce_attribute_label',              array( $this, 'change_woocommerce_attribute_label' ), PHP_INT_MAX, 2  );

			// Add to emails
			add_filter( 'woocommerce_email_attachments',            array( $this, 'add_files_to_email_attachments' ), PHP_INT_MAX, 3 );

			//add_action( 'init',                                   array( $this, 'init' ), 100 );
			//add_action( 'woocommerce_ajax_added_to_cart',         array( $this, 'ajax_add_to_cart' ), 100 );
			//add_action( 'woocommerce_loop_add_to_cart_link',      array( $this, 'replace_loop_add_to_cart_button' ), 100, 2 );
			//add_action( 'woocommerce_after_shop_loop_item',       array( $this, 'add_product_input_fields_to_frontend' ), 100 );
		}
	}

	/**
	 * get_value.
	 */
	function get_value( $option_name, $product_id, $default ) {
		return get_option( $option_name, $default );
	}

}

endif;

return new WCJ_Product_Input_Fields_Global();

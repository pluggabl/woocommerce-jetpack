<?php
/**
 * Booster for WooCommerce - Module - Gateways Fees and Discounts
 *
 * @version 3.6.2
 * @since   2.2.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Payment_Gateways_Fees' ) ) :

class WCJ_Payment_Gateways_Fees extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @todo    (maybe) move all settings to arrays
	 * @todo    (maybe) add settings subsections for each gateway
	 */
	function __construct() {

		$this->id         = 'payment_gateways_fees';
		$this->short_desc = __( 'Gateways Fees and Discounts', 'woocommerce-jetpack' );
		$this->desc       = __( 'Enable extra fees or discounts for WooCommerce payment gateways.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-fees-and-discounts';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'gateways_fees' ) );
			add_action( 'wp_enqueue_scripts',              array( $this, 'enqueue_checkout_script' ) );
		}
	}

	/**
	 * enqueue_checkout_script.
	 *
	 * @version 2.9.0
	 */
	function enqueue_checkout_script() {
		if( ! is_checkout() ) {
			return;
		}
		wp_enqueue_script( 'wcj-payment-gateways-checkout', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/wcj-checkout.js', array( 'jquery' ), WCJ()->version, true );
	}

	/**
	 * get_current_gateway.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function get_current_gateway() {
		if ( isset( $_GET['wc-api'] ) && 'WC_Gateway_PayPal_Express_AngellEYE' === $_GET['wc-api'] ) {
			return 'paypal_express'; // PayPal for WooCommerce (By Angell EYE)
		} elseif (
			( isset( $_GET['wc-ajax'] ) && 'wc_ppec_generate_cart' === $_GET['wc-ajax'] ) ||
			( isset( $_GET['startcheckout'] ) && 'true' === $_GET['startcheckout'] )
		) {
			return 'ppec_paypal'; // WooCommerce PayPal Express Checkout Payment Gateway (By WooCommerce)
		} else {
			return WC()->session->chosen_payment_method;
		}
	}

	/**
	 * check_cart_products.
	 *
	 * @version 3.6.2
	 * @since   3.6.2
	 * @todo    add WPML support
	 * @todo    add product variations
	 * @todo    add product cats and tags
	 */
	function check_cart_products( $gateway ) {
		$require_products = apply_filters( 'booster_option', array(), get_option( 'wcj_gateways_fees_include_products', array() ) );
		if ( ! empty( $require_products[ $gateway ] ) ) {
			$passed = false;
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( in_array( $item['product_id'], $require_products[ $gateway ] ) ) {
					$passed = true;
					break;
				}
			}
			if ( ! $passed ) {
				return false;
			}
		}
		$exclude_products = apply_filters( 'booster_option', array(), get_option( 'wcj_gateways_fees_exclude_products', array() ) );
		if ( ! empty( $exclude_products[ $gateway ] ) ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( in_array( $item['product_id'], $exclude_products[ $gateway ] ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * gateways_fees.
	 *
	 * @version 3.6.2
	 */
	function gateways_fees() {
		global $woocommerce;
		$current_gateway = $this->get_current_gateway();
		if ( '' != $current_gateway ) {
			$fee_text  = get_option( 'wcj_gateways_fees_text_' . $current_gateway );
			$min_cart_amount = get_option( 'wcj_gateways_fees_min_cart_amount_' . $current_gateway );
			$max_cart_amount = get_option( 'wcj_gateways_fees_max_cart_amount_' . $current_gateway );
			// Multicurrency (Currency Switcher) module
			if ( WCJ()->modules['multicurrency']->is_enabled() ) {
				$min_cart_amount = WCJ()->modules['multicurrency']->change_price( $min_cart_amount, null );
				$max_cart_amount = WCJ()->modules['multicurrency']->change_price( $max_cart_amount, null );
			}
			$total_in_cart = ( 'no' === get_option( 'wcj_gateways_fees_exclude_shipping_' . $current_gateway, 'no' ) ?
				$woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total :
				$woocommerce->cart->cart_contents_total
			);
			if ( '' != $fee_text && $total_in_cart >= $min_cart_amount  && ( 0 == $max_cart_amount || $total_in_cart <= $max_cart_amount ) && $this->check_cart_products( $current_gateway ) ) {
				$fee_value = get_option( 'wcj_gateways_fees_value_' . $current_gateway );
				$fee_type  = get_option( 'wcj_gateways_fees_type_'  . $current_gateway );
				$final_fee_to_add = 0;
				switch ( $fee_type ) {
					case 'fixed':
						// Multicurrency (Currency Switcher) module
						if ( WCJ()->modules['multicurrency']->is_enabled() ) {
							$fee_value = WCJ()->modules['multicurrency']->change_price( $fee_value, null );
						}
						$final_fee_to_add = $fee_value;
						break;
					case 'percent':
						$final_fee_to_add = ( $fee_value / 100 ) * $total_in_cart;
						if ( 'yes' === get_option( 'wcj_gateways_fees_round_' . $current_gateway ) ) {
							$final_fee_to_add = round( $final_fee_to_add, get_option( 'wcj_gateways_fees_round_precision_' . $current_gateway ) );
						}
						break;
				}
				if ( 0 != $final_fee_to_add ) {
					$taxable = ( 'yes' === get_option( 'wcj_gateways_fees_is_taxable_' . $current_gateway ) ) ? true : false;
					$tax_class_name = '';
					if ( $taxable ) {
						$tax_class_id = get_option( 'wcj_gateways_fees_tax_class_id_' . $current_gateway, 0 );
						$tax_class_names = array_merge( array( '', ), WC_Tax::get_tax_classes() );
						$tax_class_name = $tax_class_names[ $tax_class_id ];
					}
					$woocommerce->cart->add_fee( $fee_text, $final_fee_to_add, $taxable, $tax_class_name );
				}
			}
		}
	}

}

endif;

return new WCJ_Payment_Gateways_Fees();

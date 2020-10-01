<?php
/**
 * Booster for WooCommerce - Module - Gateways Fees and Discounts
 *
 * @version 5.3.0
 * @since   2.2.2
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Payment_Gateways_Fees' ) ) :

class WCJ_Payment_Gateways_Fees extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.3.0
	 * @todo    (maybe) add settings subsections for each gateway
	 */
	function __construct() {

		$this->id         = 'payment_gateways_fees';
		$this->short_desc = __( 'Gateways Fees and Discounts', 'woocommerce-jetpack' );
		$this->desc       = __( 'Enable extra fees or discounts for payment gateways. Force Default Payment Gateway (Plus). Apply fees depending on specific products (Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Enable extra fees or discounts for payment gateways.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-fees-and-discounts';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'no' === ( $modules_on_init = wcj_get_option( 'wcj_load_modules_on_init', 'no' ) ) ) {
				add_action( 'init', array( $this, 'init_options' ) );
			} elseif ( 'yes' === $modules_on_init && 'init' === current_filter() ) {
				$this->init_options();
			}
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'gateways_fees' ) );
			add_action( 'wp_enqueue_scripts',              array( $this, 'enqueue_checkout_script' ) );
		}
	}

	/**
	 * init_options.
	 *
	 * @version 4.1.0
	 * @since   3.8.0
	 */
	function init_options() {
		$this->options = array(
			'text'               => wcj_get_option( 'wcj_gateways_fees_text', array() ),
			'type'               => wcj_get_option( 'wcj_gateways_fees_type', array() ),
			'value'              => wcj_get_option( 'wcj_gateways_fees_value', array() ),
			'min_cart_amount'    => wcj_get_option( 'wcj_gateways_fees_min_cart_amount', array() ),
			'max_cart_amount'    => wcj_get_option( 'wcj_gateways_fees_max_cart_amount', array() ),
			'round'              => wcj_get_option( 'wcj_gateways_fees_round', array() ),
			'round_precision'    => wcj_get_option( 'wcj_gateways_fees_round_precision', array() ),
			'is_taxable'         => wcj_get_option( 'wcj_gateways_fees_is_taxable', array() ),
			'tax_class_id'       => wcj_get_option( 'wcj_gateways_fees_tax_class_id', array() ),
			'exclude_shipping'   => wcj_get_option( 'wcj_gateways_fees_exclude_shipping', array() ),
			'include_taxes'      => wcj_get_option( 'wcj_gateways_fees_include_taxes', array() ),
			'include_products'   => apply_filters( 'booster_option', array(), wcj_get_option( 'wcj_gateways_fees_include_products', array() ) ),
			'exclude_products'   => apply_filters( 'booster_option', array(), wcj_get_option( 'wcj_gateways_fees_exclude_products', array() ) ),
		);
		$this->defaults = array(
			'text'               => '',
			'type'               => 'fixed',
			'value'              => 0,
			'min_cart_amount'    => 0,
			'max_cart_amount'    => 0,
			'round'              => 'no',
			'round_precision'    => wcj_get_option( 'woocommerce_price_num_decimals', 2 ),
			'is_taxable'         => 'no',
			'tax_class_id'       => '',
			'exclude_shipping'   => 'no',
			'include_taxes'      => 'no',
			'include_products'   => '',
			'exclude_products'   => '',
		);
	}

	/**
	 * get_option.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 * @todo    (dev) maybe move this to `WCJ_Module`
	 */
	function wcj_get_option( $option, $key, $default = false ) {
		return ( isset( $this->options[ $option ][ $key ] ) ? $this->options[ $option ][ $key ] : ( isset( $this->defaults[ $option ] ) ? $this->defaults[ $option ] : $default ) );
	}

	/**
	 * get_deprecated_options.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 */
	function get_deprecated_options() {
		$deprecated_options  = array();
		$_deprecated_options = array(
			'wcj_gateways_fees_text'               => 'wcj_gateways_fees_text_',
			'wcj_gateways_fees_type'               => 'wcj_gateways_fees_type_',
			'wcj_gateways_fees_value'              => 'wcj_gateways_fees_value_',
			'wcj_gateways_fees_min_cart_amount'    => 'wcj_gateways_fees_min_cart_amount_',
			'wcj_gateways_fees_max_cart_amount'    => 'wcj_gateways_fees_max_cart_amount_',
			'wcj_gateways_fees_round'              => 'wcj_gateways_fees_round_',
			'wcj_gateways_fees_round_precision'    => 'wcj_gateways_fees_round_precision_',
			'wcj_gateways_fees_is_taxable'         => 'wcj_gateways_fees_is_taxable_',
			'wcj_gateways_fees_tax_class_id'       => 'wcj_gateways_fees_tax_class_id_',
			'wcj_gateways_fees_exclude_shipping'   => 'wcj_gateways_fees_exclude_shipping_',
		);
		$available_gateways = WC()->payment_gateways->payment_gateways();
		foreach ( $_deprecated_options as $new_option => $old_option ) {
			$deprecated_options[ $new_option ] = array();
			foreach ( $available_gateways as $key => $gateway ) {
				$deprecated_options[ $new_option ][ $key ] = $old_option . $key;
			}
		}
		return $deprecated_options;
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
	 * @version 4.8.0
	 * @since   3.3.0
	 */
	function get_current_gateway() {
		$gateway = '';

		if ( isset( $_GET['wc-api'] ) && 'WC_Gateway_PayPal_Express_AngellEYE' === $_GET['wc-api'] ) {
			$gateway = 'paypal_express'; // PayPal for WooCommerce (By Angell EYE)
		} elseif (
			( isset( $_GET['wc-ajax'] ) && 'wc_ppec_generate_cart' === $_GET['wc-ajax'] ) ||
			( isset( $_GET['startcheckout'] ) && 'true' === $_GET['startcheckout'] )
		) {
			$gateway = 'ppec_paypal'; // WooCommerce PayPal Express Checkout Payment Gateway (By WooCommerce)
		} else {
			$gateway = WC()->session->get( 'chosen_payment_method' );
		}

		// Pre-sets the default available payment gateway on cart and checkout pages.
		if (
			empty( $gateway ) &&
			'yes' === wcj_get_option( 'wcj_gateways_fees_force_default_payment_gateway', 'no' ) &&
			( is_checkout() || is_cart() )
		) {
			$gateways = WC()->payment_gateways->get_available_payment_gateways();
			if ( $gateways ) {
				foreach ( $gateways as $gateway ) {
					if ( 'yes' === $gateway->enabled ) {
						WC()->session->set( 'chosen_payment_method', $gateway->id );
						$gateway = WC()->session->get( 'chosen_payment_method' );
						break;
					}
				}
			}
		}
		return $gateway;
	}

	/**
	 * check_cart_products.
	 *
	 * @version 3.8.0
	 * @since   3.7.0
	 * @todo    add WPML support
	 * @todo    add product variations
	 * @todo    add product cats and tags
	 */
	function check_cart_products( $gateway ) {
		$include_products = $this->wcj_get_option( 'include_products', $gateway );
		if ( ! empty( $include_products ) ) {
			$passed = false;
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( in_array( $item['product_id'], $include_products ) ) {
					$passed = true;
					break;
				}
			}
			if ( ! $passed ) {
				return false;
			}
		}
		$exclude_products = $this->wcj_get_option( 'exclude_products', $gateway );
		if ( ! empty( $exclude_products ) ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( in_array( $item['product_id'], $exclude_products ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * gateways_fees.
	 *
	 * @version 4.1.0
	 */
	function gateways_fees() {
		global $woocommerce;
		$current_gateway = $this->get_current_gateway();
		if ( '' != $current_gateway ) {
			$fee_text        = do_shortcode( $this->wcj_get_option( 'text', $current_gateway ) );
			$min_cart_amount = $this->wcj_get_option( 'min_cart_amount', $current_gateway );
			$max_cart_amount = $this->wcj_get_option( 'max_cart_amount', $current_gateway );
			// Multicurrency (Currency Switcher) module
			if ( WCJ()->modules['multicurrency']->is_enabled() ) {
				$min_cart_amount = WCJ()->modules['multicurrency']->change_price( $min_cart_amount, null );
				$max_cart_amount = WCJ()->modules['multicurrency']->change_price( $max_cart_amount, null );
			}
			$total_in_cart = ( 'no' === $this->wcj_get_option( 'exclude_shipping', $current_gateway ) ?
				$woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total :
				$woocommerce->cart->cart_contents_total
			);
			$total_in_cart += 'no' === $this->wcj_get_option( 'include_taxes', $current_gateway ) ? 0 : $woocommerce->cart->get_subtotal_tax() + $woocommerce->cart->get_shipping_tax();
			if ( '' != $fee_text && $total_in_cart >= $min_cart_amount  && ( 0 == $max_cart_amount || $total_in_cart <= $max_cart_amount ) && $this->check_cart_products( $current_gateway ) ) {
				$fee_value = $this->wcj_get_option( 'value', $current_gateway );
				$fee_type  = $this->wcj_get_option( 'type', $current_gateway );
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
						if ( 'yes' === $this->wcj_get_option( 'round', $current_gateway ) ) {
							$final_fee_to_add = round( $final_fee_to_add, $this->wcj_get_option( 'round_precision', $current_gateway ) );
						}
						break;
				}
				if ( 0 != $final_fee_to_add ) {
					$taxable = ( 'yes' === $this->wcj_get_option( 'is_taxable', $current_gateway ) );
					$tax_class_name = '';
					if ( $taxable ) {
						$tax_class_id = $this->wcj_get_option( 'tax_class_id', $current_gateway );
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

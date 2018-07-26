<?php
/**
 * Booster for WooCommerce - Module - Checkout Fees
 *
 * @version 3.8.0
 * @since   3.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Checkout_Fees' ) ) :

class WCJ_Checkout_Fees extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.8.0
	 * @since   3.7.0
	 * @todo    (maybe) rename module to "Cart & Checkout Fees"
	 */
	function __construct() {

		$this->id         = 'checkout_fees';
		$this->short_desc = __( 'Checkout Fees', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add fees to WooCommerce cart & checkout.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-checkout-fees';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Core function
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fees' ), PHP_INT_MAX );
			// Checkout fields
			$this->checkout_fields = get_option( 'wcj_checkout_fees_data_checkout_fields', array() );
			$this->checkout_fields = array_filter( $this->checkout_fields );
			if ( ! empty( $this->checkout_fields ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			}
		}
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 */
	function enqueue_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_script(  'wcj-checkout-fees', wcj_plugin_url() . '/includes/js/wcj-checkout-fees.js', array( 'jquery' ), WCJ()->version, true );
			wp_localize_script( 'wcj-checkout-fees', 'wcj_checkout_fees', array(
				'checkout_fields' => 'input[name="' . implode( '"], input[name="', $this->checkout_fields ) . '"]',
			) );
		}
	}

	/**
	 * add_fees.
	 *
	 * @version 3.8.0
	 * @since   3.7.0
	 * @todo    fees with same title
	 * @todo    options: `tax_class`
	 * @todo    options: `cart total` (for percent) - include/exclude shipping etc. - https://docs.woocommerce.com/wc-apidocs/class-WC_Cart.html
	 * @todo    options: `rounding` (for percent)
	 * @todo    options: `min/max cart amount`
	 * @todo    options: `products, cats, tags to include/exclude`
	 * @todo    options: `countries to include/exclude`
	 * @todo    options: `user roles to include/exclude`
	 * @todo    see https://wcbooster.zendesk.com/agent/tickets/446
	 */
	function add_fees( $cart ) {
		if ( ! wcj_is_frontend() ) {
			return;
		}

		$enabled      = get_option( 'wcj_checkout_fees_data_enabled', array() );
		$titles       = get_option( 'wcj_checkout_fees_data_titles', array() );
		$types        = get_option( 'wcj_checkout_fees_data_types', array() );
		$values       = get_option( 'wcj_checkout_fees_data_values', array() );
		$taxable      = get_option( 'wcj_checkout_fees_data_taxable', array() );

		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_fees_total_number', 1 ) );
		$fees_to_add  = array();
		for ( $i = 1; $i <= $total_number; $i++ ) {
			// Validating the fee
			if (
				( isset( $enabled[ $i ] ) && 'no' === $enabled[ $i ] ) ||
				( 0 == ( $value = ( isset( $values[ $i ] ) ? $values[ $i ] : 0 ) ) )
			) {
				continue;
			}
			if ( ! empty( $this->checkout_fields[ $i ] ) ) {
				if ( isset( $post_data ) || isset( $_REQUEST['post_data'] ) ) {
					if ( ! isset( $post_data ) ) {
						$post_data = array();
						parse_str( $_REQUEST['post_data'], $post_data );
					}
					if ( empty( $post_data[ $this->checkout_fields[ $i ] ] ) ) {
						continue;
					}
				} else {
					continue;
				}
			}
			// Adding the fee
			$title = ( isset( $titles[ $i ] ) ? $titles[ $i ] : __( 'Fee', 'woocommerce-jetpack' ) . ' #' . $i );
			if ( isset( $types[ $i ] ) && 'percent' === $types[ $i ] ) {
				$value = $cart->get_cart_contents_total() * $value / 100;
			}
			$fees_to_add[ $title ] = array(
				'name'      => $title,
				'amount'    => $value,
				'taxable'   => ( isset( $taxable[ $i ] ) ? ( 'yes' === $taxable[ $i ] ) : true ),
				'tax_class' => 'standard',
			);
		}

		if ( ! empty( $fees_to_add ) ) {
			foreach ( $fees_to_add as $fee_to_add ) {
				$cart->add_fee( $fee_to_add['name'], $fee_to_add['amount'], $fee_to_add['taxable'], $fee_to_add['tax_class'] );
			}
		}
	}

}

endif;

return new WCJ_Checkout_Fees();

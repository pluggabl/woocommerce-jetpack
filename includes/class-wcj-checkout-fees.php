<?php
/**
 * Booster for WooCommerce - Module - Checkout Fees
 *
 * @version 3.6.2
 * @since   3.6.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Checkout_Fees' ) ) :

class WCJ_Checkout_Fees extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.6.2
	 * @since   3.6.2
	 * @todo    (maybe) rename module to "Cart & Checkout Fees"
	 */
	function __construct() {

		$this->id         = 'checkout_fees';
		$this->short_desc = __( 'Checkout Fees', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add fees to WooCommerce cart & checkout.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-checkout-fees';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fees' ), PHP_INT_MAX );
		}
	}

	/**
	 * add_fees.
	 *
	 * @version 3.6.2
	 * @since   3.6.2
	 * @todo    fees with same title
	 * @todo    options: `tax_class`
	 * @todo    options: `cart total` (for percent) - include/exclude shipping etc. - https://docs.woocommerce.com/wc-apidocs/class-WC_Cart.html
	 * @todo    options: `rounding` (for percent)
	 * @todo    options: `min/max cart amount`
	 * @todo    options: `products, cats, tags to include/exclude`
	 * @todo    options: `countries to include/exclude`
	 * @todo    options: `user roles to include/exclude`
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
			if (
				( isset( $enabled[ $i ] ) && 'no' === $enabled[ $i ] ) ||
				( 0 == ( $value = ( isset( $values[ $i ] ) ? $values[ $i ] : 0 ) ) )
			) {
				continue;
			}
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

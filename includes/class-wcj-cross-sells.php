<?php
/**
 * Booster for WooCommerce - Module - Cross-sells
 *
 * @version 3.5.3
 * @since   3.5.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Cross_Sells' ) ) :

class WCJ_Cross_Sells extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 * @todo    use `woocommerce_product_get_cross_sell_ids` (since WC v3.0.0)
	 */
	function __construct() {

		$this->id         = 'cross_sells';
		$this->short_desc = __( 'Cross-sells', 'woocommerce-jetpack' );
		$this->extra_desc = __( 'Cross-sells are products which you promote in the cart, based on the current product.', 'woocommerce' );
		$this->desc       = __( 'Customize WooCommerce cross-sells products display.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-cross-sells';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_cross_sells_total',     array( $this, 'cross_sells_total' ),   PHP_INT_MAX );
			add_filter( 'woocommerce_cross_sells_columns',   array( $this, 'cross_sells_columns' ), PHP_INT_MAX );
			add_filter( 'woocommerce_cross_sells_orderby',   array( $this, 'cross_sells_orderby' ), PHP_INT_MAX );
			if ( ! WCJ_IS_WC_VERSION_BELOW_3_3_0 ) {
				add_filter( 'woocommerce_cross_sells_order', array( $this, 'cross_sells_order' ),   PHP_INT_MAX );
			}
		}

	}

	/**
	 * cross_sells_order.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 */
	function cross_sells_order( $order ) {
		return ( 'no_changes' != ( $_order = get_option( 'wcj_cross_sells_order', 'no_changes' ) ) ? $_order : $order );
	}

	/**
	 * cross_sells_orderby.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 */
	function cross_sells_orderby( $orderby ) {
		return ( 'no_changes' != ( $_orderby = get_option( 'wcj_cross_sells_orderby', 'no_changes' ) ) ? $_orderby : $orderby );
	}

	/**
	 * cross_sells_columns.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 */
	function cross_sells_columns( $columns ) {
		return ( 0 != ( $_columns = get_option( 'wcj_cross_sells_columns', 0 ) ) ? $_columns : $columns );
	}

	/**
	 * cross_sells_total.
	 *
	 * @version 3.5.3
	 * @since   3.5.3
	 */
	function cross_sells_total( $limit ) {
		if ( 'yes' === get_option( 'wcj_cross_sells_hide', 'no' ) ) {
			return 0;
		}
		return ( 0 != ( $_limit = get_option( 'wcj_cross_sells_total', 0 ) ) ? $_limit : $limit );
	}

}

endif;

return new WCJ_Cross_Sells();

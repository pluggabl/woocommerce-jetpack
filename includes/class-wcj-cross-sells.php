<?php
/**
 * Booster for WooCommerce - Module - Cross-sells
 *
 * @version 3.6.0
 * @since   3.5.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Cross_Sells' ) ) :

class WCJ_Cross_Sells extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.6.0
	 * @since   3.5.3
	 */
	function __construct() {

		$this->id         = 'cross_sells';
		$this->short_desc = __( 'Cross-sells', 'woocommerce-jetpack' );
		$this->extra_desc = __( 'Cross-sells are products which you promote in the cart, based on the current product.', 'woocommerce' ) . '<br>' .
			sprintf( __( 'You can also use %s shortcode to display cross-sells anywhere on your site, for example on checkout page with %s module.', 'woocommerce-jetpack' ),
				'<code>[wcj_cross_sell_display]</code>',
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=cart_and_checkout&section=checkout_custom_info' ) . '">' .
					__( 'Checkout Custom Info', 'woocommerce-jetpack' ) . '</a>' );
		$this->desc       = __( 'Customize cross-sells products display.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-cross-sells';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_cross_sells_total',     array( $this, 'cross_sells_total' ),   PHP_INT_MAX );
			add_filter( 'woocommerce_cross_sells_columns',   array( $this, 'cross_sells_columns' ), PHP_INT_MAX );
			add_filter( 'woocommerce_cross_sells_orderby',   array( $this, 'cross_sells_orderby' ), PHP_INT_MAX );
			if ( ! WCJ_IS_WC_VERSION_BELOW_3_3_0 ) {
				add_filter( 'woocommerce_cross_sells_order', array( $this, 'cross_sells_order' ),   PHP_INT_MAX );
			}
			if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
				if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_cross_sells_global_enabled', 'no' ) ) ) {
					add_filter( 'woocommerce_product_get_cross_sell_ids', array( $this, 'cross_sells_ids' ), PHP_INT_MAX, 2 );
				}
			}
			if ( 'yes' === get_option( 'wcj_cross_sells_hide', 'no' ) ) {
				add_action( 'init', array( $this, 'hide_cross_sells' ), PHP_INT_MAX );
			}
			if ( 'no_changes' != get_option( 'wcj_cross_sells_position', 'no_changes' ) ) {
				add_action( 'init', array( $this, 'reposition_cross_sells' ), PHP_INT_MAX );
			}
		}

	}

	/**
	 * reposition_cross_sells.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) check `woocommerce\templates\cart\cart.php` for more positions
	 */
	function reposition_cross_sells() {
		$this->hide_cross_sells();
		add_action( get_option( 'wcj_cross_sells_position', 'no_changes' ), 'woocommerce_cross_sell_display', get_option( 'wcj_cross_sells_position_priority', 10 ) );
	}

	/**
	 * hide_cross_sells.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function hide_cross_sells() {
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
	}

	/**
	 * cross_sells_ids.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) on per category/tag basis
	 * @todo    (maybe) ids instead of list
	 * @todo    (maybe) on cart update (i.e. product removed) cross-sells are not updated (so it may be needed to reload page manually to see new cross-sells)
	 */
	function cross_sells_ids( $ids, $_product ) {
		$global_cross_sells = get_option( 'wcj_cross_sells_global_ids', '' );
		if ( ! empty( $global_cross_sells ) ) {
			$global_cross_sells = array_unique( $global_cross_sells );
			$product_id     = wcj_get_product_id_or_variation_parent_id( $_product );
			if ( false !== ( $key = array_search( $product_id, $global_cross_sells ) ) ) {
				unset( $global_cross_sells[ $key ] );
			}
		}
		return ( empty( $global_cross_sells ) ? $ids : array_unique( array_merge( $ids, $global_cross_sells ) ) );
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
	 * @version 3.6.0
	 * @since   3.5.3
	 */
	function cross_sells_total( $limit ) {
		return ( 0 != ( $_limit = get_option( 'wcj_cross_sells_total', 0 ) ) ? $_limit : $limit );
	}

}

endif;

return new WCJ_Cross_Sells();

<?php
/**
 * Booster for WooCommerce - Module - My Account
 *
 * @version 2.8.3
 * @since   2.8.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_My_Account' ) ) :

class WCJ_My_Account extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    settings
	 * @todo    more statuses (not only completed)
	 */
	function __construct() {

		$this->id         = 'my_account';
		$this->short_desc = __( 'My Account', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce "My Account" page customization.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-my-account';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_my_account_order_actions' ), 10, 2 );
			add_action( 'wp_footer',                                array( $this, 'add_js_conformation' ) );
			add_action( 'init',                                     array( $this, 'woocommerce_mark_order_status' ) );
		}
	}

	/*
	 * add_my_account_order_actions.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @see     http://snippet.fm/snippets/add-order-complete-action-to-woocommerce-my-orders-customer-table/
	 */
	function add_my_account_order_actions( $actions, $order ) {
		if ( 'completed' != $order->get_status() ) {
			$actions['wcj_mark_completed_by_customer'] = array(
				'url'  => wp_nonce_url( add_query_arg( array(
					'wcj_action' => 'wcj_woocommerce_mark_order_status',
					'order_id'   => $order->get_id() ) ), 'wcj-woocommerce-mark-order-status' ),
				'name' => __( 'Complete', 'woocommerce-jetpack' ),
			);
		}
		return $actions;
	}

	/*
	 * add_js_conformation.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function add_js_conformation() {
		echo '<script>
			jQuery("a.wcj_mark_completed_by_customer").each( function() { jQuery(this).attr("onclick", "return confirm(\'' .
				__( 'Are you sure?', 'woocommerce-jetpack' ) . '\')") } );
		</script>';
	}

	/*
	 * woocommerce_mark_order_status.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function woocommerce_mark_order_status() {
		if ( isset( $_GET['wcj_action'] ) && 'wcj_woocommerce_mark_order_status' === $_GET['wcj_action'] && isset( $_GET['order_id'] ) && isset( $_GET['_wpnonce'] ) ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'wcj-woocommerce-mark-order-status' ) ) {
				$_order = wc_get_order( $_GET['order_id'] );
				if ( $_order->get_customer_id() === get_current_user_id() ) {
					$_order->update_status( 'completed' );
					wp_safe_redirect( remove_query_arg( array( 'wcj_action', 'order_id', '_wpnonce' ) ) );
					exit;
				}
			}
		}
	}

}

endif;

return new WCJ_My_Account();

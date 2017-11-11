<?php
/**
 * Booster for WooCommerce - Module - My Account
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_My_Account' ) ) :

class WCJ_My_Account extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function __construct() {

		$this->id         = 'my_account';
		$this->short_desc = __( 'My Account', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce "My Account" page customization.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-my-account';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'maybe_add_my_account_order_actions' ), 10, 2 );
			add_action( 'wp_footer',                                array( $this, 'maybe_add_js_conformation' ) );
			add_action( 'init',                                     array( $this, 'process_woocommerce_mark_order_status' ) );
		}
	}

	/*
	 * maybe_add_my_account_order_actions.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @see     http://snippet.fm/snippets/add-order-complete-action-to-woocommerce-my-orders-customer-table/
	 */
	function maybe_add_my_account_order_actions( $actions, $order ) {
		$statuses_to_add = get_option( 'wcj_my_account_add_order_status_actions', '' );
		if ( ! empty( $statuses_to_add ) ) {
			$all_statuses = wcj_get_order_statuses();
			foreach ( $statuses_to_add as $status_to_add ) {
				if ( $status_to_add != $order->get_status() ) {
					$actions[ 'wcj_mark_' . $status_to_add . '_by_customer' ] = array(
						'url'  => wp_nonce_url( add_query_arg( array(
							'wcj_action' => 'wcj_woocommerce_mark_order_status',
							'status'     => $status_to_add,
							'order_id'   => $order->get_id() ) ), 'wcj-woocommerce-mark-order-status' ),
						'name' => $all_statuses[ $status_to_add ],
					);
				}
			}
		}
		return $actions;
	}

	/*
	 * maybe_add_js_conformation.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function maybe_add_js_conformation() {
		$statuses_to_add = get_option( 'wcj_my_account_add_order_status_actions', '' );
		if ( ! empty( $statuses_to_add ) ) {
			echo '<script>';
			foreach ( $statuses_to_add as $status_to_add ) {
				echo 'jQuery("a.wcj_mark_' . $status_to_add . '_by_customer").each( function() { jQuery(this).attr("onclick", "return confirm(\'' .
					__( 'Are you sure?', 'woocommerce-jetpack' ) . '\')") } );';
			}
			echo '</script>';
		}
	}

	/*
	 * process_woocommerce_mark_order_status.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function process_woocommerce_mark_order_status() {
		if (
			isset( $_GET['wcj_action'] ) && 'wcj_woocommerce_mark_order_status' === $_GET['wcj_action'] &&
			isset( $_GET['status'] ) &&
			isset( $_GET['order_id'] ) &&
			isset( $_GET['_wpnonce'] )
		) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'wcj-woocommerce-mark-order-status' ) ) {
				$_order = wc_get_order( $_GET['order_id'] );
				if ( $_order->get_customer_id() === get_current_user_id() ) {
					$_order->update_status( $_GET['status'] );
					wp_safe_redirect( remove_query_arg( array( 'wcj_action', 'status', 'order_id', '_wpnonce' ) ) );
					exit;
				}
			}
		}
	}

}

endif;

return new WCJ_My_Account();

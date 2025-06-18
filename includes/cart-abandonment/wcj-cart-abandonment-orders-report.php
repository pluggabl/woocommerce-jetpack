<?php
/**
 * Booster Elite for WooCommerce - Module - Cart Abandonment Orders
 *
 * @version 7.2.7
 * @author  Pluggabl LLC.
 * @package Booster_Elite_For_WooCommerce/includes
 */

global $wpdb;
$table_wcj_cart_abandonment_data = $wpdb->prefix . 'wcj_cart_abandonment_data';
$order_filter                    = filter_input( INPUT_GET, 'order_filter', FILTER_UNSAFE_RAW );

?>
<div class="wcj-setting-jetpack-body cart_abandonment_main">	
	<div class="wcj-col-12">
	<?php echo wp_kses_post( $this->get_tool_header_html( 'cart_abandonment' ) ); ?>
	<?php echo wp_kses_post( __( 'Want detailed reports, filtering, and to see recoverable vs. lost revenue? Upgrade to <a href="https://booster.io/buy-booster/" target="_blank"> Booster Elite! </a>', 'woocommerce-jetpack' ) ); ?>
	</div>
	<div class="wcj-col-12">
		<?php
		$search_query = '';
		$wpnonce      = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
		if ( $wpnonce && isset( $_POST['action'] ) && 'delete' === $_POST['action'] && isset( $_POST['wcj_ca_data'] ) ) {
			$wcj_ca_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['wcj_ca_data'] ) );
			foreach ( $wcj_ca_data as $wcj_ca_data_id ) {
				$wpdb->delete( $table_wcj_cart_abandonment_data, array( 'id' => $wcj_ca_data_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}
		?>
		<?php
		if ( 'all' === $order_filter ) {
				$order_status = '';
		} else {
			$order_status = $order_filter;
		}
		require_once 'class-wcj-cart-abandonment-orders.php';

		$option = 'per_page';
		$args   = array(
			'label'   => 'Cart Abandonment Data',
			'default' => 10,
			'option'  => 'cart_abandonment_data_per_page',
		);
		add_screen_option( $option, $args );
		$w_c_j_ca_orders = new WCJ_Cart_Abandonment_Orders();
		$w_c_j_ca_orders->prepare_items( $order_status, $search_query );
		?>
		<form method="post">
		<?php

		$w_c_j_ca_orders->display();
		echo '</form>';
		?>
	</div>
</div>

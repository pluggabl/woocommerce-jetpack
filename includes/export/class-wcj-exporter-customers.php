<?php
/**
 * WooCommerce Jetpack Exporter Customers
 *
 * The WooCommerce Jetpack Exporter Customers class.
 *
 * @version 2.5.9
 * @since   2.5.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Exporter_Customers' ) ) :

class WCJ_Exporter_Customers {

	/**
	 * Constructor.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function __construct() {
		return true;
	}

	/**
	 * export_customers.
	 *
	 * @version 2.5.9
	 * @since   2.4.8
	 */
	function export_customers( $fields_helper ) {

		// Standard Fields
		$all_fields = $fields_helper->get_customer_export_fields();
		$fields_ids = get_option( 'wcj_export_customers_fields', $fields_helper->get_customer_export_default_fields_ids() );
		$titles = array();
		foreach( $fields_ids as $field_id ) {
			$titles[] = $all_fields[ $field_id ];
		}

		// Get the Data
		$data = array();
		$data[] = $titles;
		$customers = get_users( 'role=customer' );
		foreach ( $customers as $customer ) {
			$row = array();
			foreach( $fields_ids as $field_id ) {
				switch ( $field_id ) {
					case 'customer-id':
						$row[] = $customer->ID;
						break;
					case 'customer-email':
						$row[] = $customer->user_email;
						break;
					case 'customer-login':
						$row[] = $customer->user_login;
						break;
					case 'customer-nicename':
						$row[] = $customer->user_nicename;
						break;
					case 'customer-url':
						$row[] = $customer->user_url;
						break;
					case 'customer-registered':
						$row[] = $customer->user_registered;
						break;
					case 'customer-display-name':
						$row[] = $customer->display_name;
						break;
					case 'customer-first-name':
						$row[] = $customer->first_name;
						break;
					case 'customer-last-name':
						$row[] = $customer->last_name;
						break;
					case 'customer-debug':
						$row[] = '<pre>' . print_r( $customer, true ) . '</pre>';
						break;
				}
			}
			$data[] = $row;
		}
		return $data;
	}

	/**
	 * export_customers_from_orders.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function export_customers_from_orders() {
		$data = array();
		$data[] = array(
			__( 'Nr.', 'woocommerce-jetpack' ),
			__( 'Email', 'woocommerce-jetpack' ),
			__( 'First Name', 'woocommerce-jetpack' ),
			__( 'Last Name', 'woocommerce-jetpack' ),
			__( 'Last Order Date', 'woocommerce-jetpack' ),
		);
		$total_customers = 0;
		$orders = array();
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) break;
			while ( $loop_orders->have_posts() ) : $loop_orders->the_post();
				$order_id = $loop_orders->post->ID;
				$order = wc_get_order( $order_id );
				if ( isset( $order->billing_email ) && '' != $order->billing_email && ! in_array( $order->billing_email, $orders ) ) {
					$emails_to_skip = array();
					if ( ! in_array( $order->billing_email, $emails_to_skip ) ) {
						$total_customers++;
						$data[] = array( $total_customers, $order->billing_email, $order->billing_first_name, $order->billing_last_name, get_the_date( 'Y/m/d' ), );
						$orders[] = $order->billing_email;
					}
				}
			endwhile;
			$offset += $block_size;
		}
		return $data;
	}

}

endif;

return new WCJ_Exporter_Customers();

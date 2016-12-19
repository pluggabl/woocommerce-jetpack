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
	 * @version 2.5.9
	 * @since   2.4.8
	 * @todo    (maybe) add more order fields (shipping)
	 */
	function export_customers_from_orders( $fields_helper ) {

		// Standard Fields
		$all_fields = $fields_helper->get_customer_from_order_export_fields();
		$fields_ids = get_option( 'wcj_export_customers_from_orders_fields', $fields_helper->get_customer_from_order_export_default_fields_ids() );
		$titles = array();
		foreach( $fields_ids as $field_id ) {
			$titles[] = $all_fields[ $field_id ];
		}

		// Get the Data
		$data = array();
		$data[] = $titles;
		$total_customers = 0;
		$orders = array();
		$offset = 0;
		$block_size = 1024;
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) {
				break;
			}
			foreach ( $loop_orders->posts as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( isset( $order->billing_email ) && '' != $order->billing_email && ! in_array( $order->billing_email, $orders ) ) {
					$emails_to_skip = array(); // `emails_to_skip` is not really used...
					if ( ! in_array( $order->billing_email, $emails_to_skip ) ) {
						$total_customers++;
						$row = array();
						foreach( $fields_ids as $field_id ) {
							switch ( $field_id ) {
								case 'customer-nr':
									$row[] = $total_customers;
									break;
								case 'customer-billing-email':
									$row[] = $order->billing_email;
									break;
								case 'customer-billing-first-name':
									$row[] = $order->billing_first_name;
									break;
								case 'customer-billing-last-name':
									$row[] = $order->billing_last_name;
									break;
								case 'customer-billing-company':
									$row[] = $order->billing_company;
									break;
								case 'customer-billing-address-1':
									$row[] = $order->billing_address_1;
									break;
								case 'customer-billing-address-2':
									$row[] = $order->billing_address_2;
									break;
								case 'customer-billing-city':
									$row[] = $order->billing_city;
									break;
								case 'customer-billing-state':
									$row[] = $order->billing_state;
									break;
								case 'customer-billing-postcode':
									$row[] = $order->billing_postcode;
									break;
								case 'customer-billing-country':
									$row[] = $order->billing_country;
									break;
								case 'customer-billing-phone':
									$row[] = $order->billing_phone;
									break;
								case 'customer-last-order-date':
									$row[] = get_the_date( get_option( 'date_format' ), $order_id );
									break;
							}
						}
						$data[] = $row;
						$orders[] = $order->billing_email;
					}
				}
			}
			$offset += $block_size;
		}
		return $data;
	}

}

endif;

return new WCJ_Exporter_Customers();

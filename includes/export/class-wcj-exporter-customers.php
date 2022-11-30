<?php
/**
 * Booster for WooCommerce Exporter Customers
 *
 * @version 6.0.0
 * @since   2.5.9
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Exporter_Customers' ) ) :
		/**
		 * WCJ_Exporter_Customers.
		 *
		 * @version 2.5.9
		 * @since   2.5.9
		 */
	class WCJ_Exporter_Customers {

		/**
		 * Constructor.
		 *
		 * @version 2.5.9
		 * @since   2.5.9
		 */
		public function __construct() {
			return true;
		}

		/**
		 * Export_customers.
		 *
		 * @version 6.0.0
		 * @since   2.4.8
		 * @param array $fields_helper defines the fields.
		 */
		public function export_customers( $fields_helper ) {

			// Standard Fields.
			$all_fields = $fields_helper->get_customer_export_fields();
			$fields_ids = wcj_get_option( 'wcj_export_customers_fields', $fields_helper->get_customer_export_default_fields_ids() );
			$titles     = array();
			foreach ( $fields_ids as $field_id ) {
				$titles[] = $all_fields[ $field_id ];
			}

			// Get the Data.
			$data      = array();
			$data[]    = $titles;
			$args      = array( 'role' => 'customer' );
			$args      = wcj_maybe_add_date_query( $args );
			$customers = get_users( $args );
			foreach ( $customers as $customer ) {
				$row = array();
				foreach ( $fields_ids as $field_id ) {
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
							$row[] = wp_json_encode( $customer );
							break;
					}
				}
				$data[] = $row;
			}
			return $data;
		}

		/**
		 * Export_customers_from_orders.
		 *
		 * @version 5.6.2
		 * @since   2.4.8
		 * @todo    (maybe) add more order fields (shipping)
		 * @param string | array $fields_helper defines the fields_helper.
		 */
		public function export_customers_from_orders( $fields_helper ) {

			// Standard Fields.
			$all_fields = $fields_helper->get_customer_from_order_export_fields();
			$fields_ids = wcj_get_option( 'wcj_export_customers_from_orders_fields', $fields_helper->get_customer_from_order_export_default_fields_ids() );
			$titles     = array();
			foreach ( $fields_ids as $field_id ) {
				$titles[] = $all_fields[ $field_id ];
			}

			// Get the Data.
			$data            = array();
			$data[]          = $titles;
			$total_customers = 0;
			$orders          = array();
			$offset          = 0;
			$block_size      = 1024;
			while ( true ) {
				$args_orders = array(
					'post_type'      => 'shop_order',
					'post_status'    => 'any',
					'posts_per_page' => $block_size,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'offset'         => $offset,
					'fields'         => 'ids',
				);
				$args_orders = wcj_maybe_add_date_query( $args_orders );
				$loop_orders = new WP_Query( $args_orders );
				if ( ! $loop_orders->have_posts() ) {
					break;
				}
				foreach ( $loop_orders->posts as $order_id ) {
					$order          = wc_get_order( $order_id );
					$_billing_email = wcj_get_order_billing_email( $order );
					if ( isset( $_billing_email ) && '' !== $_billing_email && ! in_array( $_billing_email, $orders, true ) ) {
						$emails_to_skip = array(); // `emails_to_skip` is not really used.
						if ( ! in_array( $_billing_email, $emails_to_skip, true ) ) {
							$total_customers++;
							$row = array();
							foreach ( $fields_ids as $field_id ) {
								switch ( $field_id ) {
									case 'customer-nr':
										$row[] = $total_customers;
										break;
									case 'customer-billing-email':
										$row[] = $_billing_email;
										break;
									case 'customer-billing-first-name':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_first_name : $order->get_billing_first_name() );
										break;
									case 'customer-billing-last-name':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_last_name : $order->get_billing_last_name() );
										break;
									case 'customer-billing-company':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_company : $order->get_billing_company() );
										break;
									case 'customer-billing-address-1':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_address_1 : $order->get_billing_address_1() );
										break;
									case 'customer-billing-address-2':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_address_2 : $order->get_billing_address_2() );
										break;
									case 'customer-billing-city':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_city : $order->get_billing_city() );
										break;
									case 'customer-billing-state':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_state : $order->get_billing_state() );
										break;
									case 'customer-billing-postcode':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_postcode : $order->get_billing_postcode() );
										break;
									case 'customer-billing-country':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_country : $order->get_billing_country() );
										break;
									case 'customer-billing-phone':
										$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_phone : $order->get_billing_phone() );
										break;
									case 'customer-last-order-date':
										$row[] = get_the_date( wcj_get_option( 'date_format' ), $order_id );
										break;
								}
							}
							$data[]   = $row;
							$orders[] = $_billing_email;
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

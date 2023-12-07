<?php
/**
 * Booster for WooCommerce Exporter Orders
 *
 * @version 7.1.4
 * @since   2.5.9
 * @author  Pluggabl LLC.
 * @todo    filter export by date
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Exporter_Orders' ) ) :
		/**
		 * WCJ_Exporter_Orders.
		 *
		 * @version 2.5.9
		 * @since   2.5.9
		 */
	class WCJ_Exporter_Orders {

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
		 * Get_export_orders_row.
		 *
		 * @version 6.0.0
		 * @since   2.5.9
		 * @param array  $fields_ids defines the order field ids.
		 * @param int    $order_id defines the order id.
		 * @param object $order defines the order object.
		 * @param object $items defines the order items.
		 * @param array  $items_product_input_fields defines the items product input fields.
		 * @param object $item defines the order item.
		 * @param int    $item_id defines the order item id.
		 *
		 * @todo    added precisions to the price fields.
		 */
		public function get_export_orders_row( $fields_ids, $order_id, $order, $items, $items_product_input_fields, $item, $item_id ) {
			$row = array();

			/* Woocommerce default precision */
			$precision = ! isset( $precision ) || is_null( $precision ) ? wc_get_price_decimals() : intval( $precision );

			/* Precision from Booster's Price formats module */
			if ( 'yes' === wcj_get_option( 'wcj_price_formats_enabled', 'no' ) ) {
				$order_curr                 = wcj_get_order_currency( $order );
				$price_formats_total_number = wcj_get_option( 'wcj_price_formats_total_number', 1 );
				for ( $i = 1; $i <= $price_formats_total_number; $i++ ) {
					if ( wcj_get_option( 'wcj_price_formats_currency_' . $i ) === $order_curr ) {
						$precision = absint( wcj_get_option( 'wcj_price_formats_number_of_decimals_' . $i ) );
						break;
					}
				}
			}
			foreach ( $fields_ids as $field_id ) {
				switch ( $field_id ) {
					case 'item-product-input-fields':
						$row[] = wcj_get_product_input_fields( $item );
						break;
					case 'item-debug':
						try {
							$row[] = $item;
						} catch ( Exception $e ) {
							$row[] = '';
						}
						break;
					case 'item-name':
						$row[] = $item['name'];
						break;
					case 'item-meta':
						$row[] = wcj_get_order_item_meta_info( $item_id, $item, $order );
						break;
					case 'item-variation-meta':
						$row[] = ( 0 !== $item['variation_id'] ) ? wcj_get_order_item_meta_info( $item_id, $item, $order, true ) : '';
						break;
					case 'item-qty':
						$row[] = $item['qty'];
						break;
					case 'item-tax-class':
						$row[] = $item['tax_class'];
						break;
					case 'item-product-id':
						$row[] = $item['product_id'];
						break;
					case 'item-variation-id':
						$row[] = $item['variation_id'];
						break;
					case 'item-line-subtotal':
						$row[] = round( $item['line_subtotal'], $precision );
						break;
					case 'item-line-total':
						$row[] = round( $item['line_total'], $precision );
						break;
					case 'item-line-subtotal-tax':
						$row[] = round( $item['line_subtotal_tax'], $precision );
						break;
					case 'item-line-tax':
						$row[] = round( $item['line_tax'], $precision );
						break;
					case 'item-line-total-plus-tax':
						$row[] = round( $item['line_total'] + $item['line_tax'], $precision );
						break;
					case 'item-line-subtotal-plus-tax':
						$row[] = round( $item['line_subtotal'] + $item['line_subtotal_tax'], $precision );
						break;
					case 'order-id':
						$row[] = $order_id;
						break;
					case 'order-number':
						$row[] = $order->get_order_number();
						break;
					case 'order-status':
						$row[] = $order->get_status();
						break;
					case 'order-date':
						$row[] = get_the_date( wcj_get_option( 'date_format' ), $order_id );
						break;
					case 'order-time':
						$row[] = get_the_time( wcj_get_option( 'time_format' ), $order_id );
						break;
					case 'order-item-count':
						$row[] = $order->get_item_count();
						break;
					case 'order-items':
						$row[] = $items;
						break;
					case 'order-items-product-input-fields':
						$row[] = $items_product_input_fields;
						break;
					case 'order-currency':
						$row[] = wcj_get_order_currency( $order );
						break;
					case 'order-total':
						$row[] = round( $order->get_total(), $precision );
						break;
					case 'order-total-tax':
						$row[] = round( $order->get_total_tax(), $precision );
						break;
					case 'order-payment-method':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->payment_method_title : $order->get_payment_method_title() );
						break;
					case 'order-notes':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->customer_note : $order->get_customer_note() );
						break;
					case 'billing-first-name':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_first_name : $order->get_billing_first_name() );
						break;
					case 'billing-last-name':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_last_name : $order->get_billing_last_name() );
						break;
					case 'billing-company':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_company : $order->get_billing_company() );
						break;
					case 'billing-address-1':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_address_1 : $order->get_billing_address_1() );
						break;
					case 'billing-address-2':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_address_2 : $order->get_billing_address_2() );
						break;
					case 'billing-city':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_city : $order->get_billing_city() );
						break;
					case 'billing-state':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_state : $order->get_billing_state() );
						break;
					case 'billing-postcode':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_postcode : $order->get_billing_postcode() );
						break;
					case 'billing-country':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_country : $order->get_billing_country() );
						break;
					case 'billing-phone':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_phone : $order->get_billing_phone() );
						break;
					case 'billing-email':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_email : $order->get_billing_email() );
						break;
					case 'shipping-first-name':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_first_name : $order->get_shipping_first_name() );
						break;
					case 'shipping-last-name':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_last_name : $order->get_shipping_last_name() );
						break;
					case 'shipping-company':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_company : $order->get_shipping_company() );
						break;
					case 'shipping-address-1':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_address_1 : $order->get_shipping_address_1() );
						break;
					case 'shipping-address-2':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_address_2 : $order->get_shipping_address_2() );
						break;
					case 'shipping-city':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_city : $order->get_shipping_city() );
						break;
					case 'shipping-state':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_state : $order->get_shipping_state() );
						break;
					case 'shipping-postcode':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_postcode : $order->get_shipping_postcode() );
						break;
					case 'shipping-country':
						$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->shipping_country : $order->get_shipping_country() );
						break;
				}
			}
			return $row;
		}

		/**
		 * Export_orders.
		 *
		 * @version 6.0.1
		 * @since   2.4.8
		 * @param object $fields_helper defines the fields helper.
		 *
		 * @todo    (maybe) metainfo as separate column
		 */
		public function export_orders( $fields_helper ) {
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			// Standard Fields.
			$all_fields = $fields_helper->get_order_export_fields();
			$fields_ids = wcj_get_option( 'wcj_export_orders_fields', $fields_helper->get_order_export_default_fields_ids() );
			$titles     = array();
			foreach ( $fields_ids as $field_id ) {
				$titles[] = $all_fields[ $field_id ];
			}

			// Additional Fields.
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_fields_additional_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_export_orders_fields_additional_enabled_' . $i, 'no' ) ) {
					$titles[] = wcj_get_option( 'wcj_export_orders_fields_additional_title_' . $i, '' );
				}
			}

			$data       = array();
			$data[]     = $titles;
			$offset     = 0;
			$block_size = 1024;
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
					$order = wc_get_order( $order_id );
					if ( ! apply_filters( 'wcj_export_validation', true, 'order', $order ) ) {
						continue;
					}

					if ( $wpnonce && isset( $_POST['wcj_filter_by_order_billing_country'] ) && '' !== $_POST['wcj_filter_by_order_billing_country'] ) {
						if ( ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_country : $order->get_billing_country() ) !== $_POST['wcj_filter_by_order_billing_country'] ) {
							continue;
						}
					}

					$filter_by_product_title = true;
					if ( isset( $_POST['wcj_filter_by_product_title'] ) && '' !== $_POST['wcj_filter_by_product_title'] ) {
						$filter_by_product_title = false;
					}
					$items                      = array();
					$items_product_input_fields = array();
					foreach ( $order->get_items() as $item_id => $item ) {
						if ( in_array( 'order-items', $fields_ids, true ) ) {
							$meta_info = ( 0 !== $item['variation_id'] ) ? wcj_get_order_item_meta_info( $item_id, $item, $order, true ) : '';
							if ( '' !== $meta_info ) {
								$meta_info = ' [' . $meta_info . ']';
							}
							$items[] = $item['name'] . $meta_info;
						}
						if ( in_array( 'order-items-product-input-fields', $fields_ids, true ) ) {
							$item_product_input_fields = wcj_get_product_input_fields( $item );
							if ( '' !== $item_product_input_fields ) {
								$items_product_input_fields[] = $item_product_input_fields;
							}
						}
						if ( ! $filter_by_product_title ) {
							if ( false !== strpos( $item['name'], sanitize_text_field( wp_unslash( $_POST['wcj_filter_by_product_title'] ) ) ) ) {
								$filter_by_product_title = true;
							}
						}
					}
					$items                      = implode( ' / ', $items );
					$items_product_input_fields = implode( ' / ', $items_product_input_fields );
					if ( ! $filter_by_product_title ) {
						continue;
					}

					$row = $this->get_export_orders_row( $fields_ids, $order_id, $order, $items, $items_product_input_fields, null, null );

					// Additional Fields.
					$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_fields_additional_total_number', 1 ) );
					for ( $i = 1; $i <= $total_number; $i++ ) {
						if ( 'yes' === wcj_get_option( 'wcj_export_orders_fields_additional_enabled_' . $i, 'no' ) ) {
							$additional_field_value = wcj_get_option( 'wcj_export_orders_fields_additional_value_' . $i, '' );
							if ( '' !== ( $additional_field_value ) ) {
								if ( 'meta' === wcj_get_option( 'wcj_export_orders_fields_additional_type_' . $i, 'meta' ) ) {
									$row[] = wp_strip_all_tags( html_entity_decode( $this->safely_get_post_meta( $order_id, $additional_field_value ) ) );
								} else {
									global $post;
									$post = get_post( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									setup_postdata( $post );
									$row[] = wp_strip_all_tags( html_entity_decode( do_shortcode( $additional_field_value ) ) );
									wp_reset_postdata();
								}
							} else {
								$row[] = '';
							}
						}
					}

					$data[] = $row;
				}
				$offset += $block_size;
			}
			return $data;
		}


		/**
		 * Export_orders_hpos.
		 *
		 * @version 7.1.4
		 * @since   1.0.0
		 * @param object $fields_helper defines the fields helper.
		 *
		 * @todo    (maybe) metainfo as separate column
		 */
		public function export_orders_hpos( $fields_helper ) {
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			// Standard Fields.
			$all_fields = $fields_helper->get_order_export_fields();
			$fields_ids = wcj_get_option( 'wcj_export_orders_fields', $fields_helper->get_order_export_default_fields_ids() );
			$titles     = array();
			foreach ( $fields_ids as $field_id ) {
				$titles[] = $all_fields[ $field_id ];
			}

			// Additional Fields.
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_fields_additional_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_export_orders_fields_additional_enabled_' . $i, 'no' ) ) {
					$titles[] = wcj_get_option( 'wcj_export_orders_fields_additional_title_' . $i, '' );
				}
			}

			$data       = array();
			$data[]     = $titles;
			$offset     = 0;
			$block_size = 1024;
			while ( true ) {
				$args_orders = array(
					'type'           => 'shop_order',
					'status'         => 'any',
					'posts_per_page' => $block_size,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'offset'         => $offset,
					'fields'         => 'ids',
				);
				$args_orders = wcj_maybe_add_date_query( $args_orders );
				$orders      = wc_get_orders( $args_orders );
				if ( ! $orders ) {
					break;
				}
				$i = 0;
				foreach ( $orders as $order ) {
					$order_id = $order->get_id();

					$order = wc_get_order( $order_id );
					if ( ! apply_filters( 'wcj_export_validation', true, 'order', $order ) ) {
						continue;
					}

					if ( $wpnonce && isset( $_POST['wcj_filter_by_order_billing_country'] ) && '' !== $_POST['wcj_filter_by_order_billing_country'] ) {
						if ( ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_country : $order->get_billing_country() ) !== $_POST['wcj_filter_by_order_billing_country'] ) {
							continue;
						}
					}

					$filter_by_product_title = true;
					if ( isset( $_POST['wcj_filter_by_product_title'] ) && '' !== $_POST['wcj_filter_by_product_title'] ) {
						$filter_by_product_title = false;
					}
					$items                      = array();
					$items_product_input_fields = array();
					foreach ( $order->get_items() as $item_id => $item ) {
						if ( in_array( 'order-items', $fields_ids, true ) ) {
							$meta_info = ( 0 !== $item['variation_id'] ) ? wcj_get_order_item_meta_info( $item_id, $item, $order, true ) : '';
							if ( '' !== $meta_info ) {
								$meta_info = ' [' . $meta_info . ']';
							}
							$items[] = $item['name'] . $meta_info;
						}
						if ( in_array( 'order-items-product-input-fields', $fields_ids, true ) ) {
							$item_product_input_fields = wcj_get_product_input_fields( $item );
							if ( '' !== $item_product_input_fields ) {
								$items_product_input_fields[] = $item_product_input_fields;
							}
						}
						if ( ! $filter_by_product_title ) {
							if ( false !== strpos( $item['name'], sanitize_text_field( wp_unslash( $_POST['wcj_filter_by_product_title'] ) ) ) ) {
								$filter_by_product_title = true;
							}
						}
					}
					$items                      = implode( ' / ', $items );
					$items_product_input_fields = implode( ' / ', $items_product_input_fields );
					if ( ! $filter_by_product_title ) {
						continue;
					}

					$row = $this->get_export_orders_row( $fields_ids, $order_id, $order, $items, $items_product_input_fields, null, null );

					// Additional Fields.
					$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_fields_additional_total_number', 1 ) );
					for ( $i = 1; $i <= $total_number; $i++ ) {
						if ( 'yes' === wcj_get_option( 'wcj_export_orders_fields_additional_enabled_' . $i, 'no' ) ) {
							$additional_field_value = wcj_get_option( 'wcj_export_orders_fields_additional_value_' . $i, '' );
							if ( '' !== ( $additional_field_value ) ) {
								if ( 'meta' === wcj_get_option( 'wcj_export_orders_fields_additional_type_' . $i, 'meta' ) ) {
									$row[] = wp_strip_all_tags( html_entity_decode( $order->get_meta( $additional_field_value ) ) );
								} else {
									if ( str_contains( $additional_field_value, ']' ) ) {
										$get_value        = $additional_field_value;
										$order_id         = ' order_id="' . $order_id . '"]';
										$custom_shortcode = str_replace( ']', $order_id, $get_value );
									} else {
										$custom_shortcode = $additional_field_value;
									}
									$row[] = wp_strip_all_tags( html_entity_decode( do_shortcode( $custom_shortcode ) ) );
								}
							} else {
								$row[] = '';
							}
						}
					}

					$data[] = $row;
					$i++;
				}
				$offset += $block_size;
			}
			return $data;
		}

		/**
		 * Export_orders_items.
		 *
		 * @version 6.0.1
		 * @since   2.5.9
		 * @param object $fields_helper defines the fields helper.
		 */
		public function export_orders_items( $fields_helper ) {
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			// Standard Fields.
			$all_fields = $fields_helper->get_order_items_export_fields();
			$fields_ids = apply_filters( 'wcj_export_orders_items_fields', wcj_get_option( 'wcj_export_orders_items_fields', $fields_helper->get_order_items_export_default_fields_ids() ) );
			$titles     = array();
			foreach ( $fields_ids as $field_id ) {
				$titles[] = $all_fields[ $field_id ];
			}

			// Additional Fields.
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_items_fields_additional_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_export_orders_items_fields_additional_enabled_' . $i, 'no' ) ) {
					$titles[] = wcj_get_option( 'wcj_export_orders_items_fields_additional_title_' . $i, '' );
				}
			}

			$data       = array();
			$data[]     = $titles;
			$offset     = 0;
			$block_size = 1024;
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
					$order = wc_get_order( $order_id );
					if ( ! apply_filters( 'wcj_export_validation', true, 'order', $order ) ) {
						continue;
					}

					if ( $wpnonce && isset( $_POST['wcj_filter_by_order_billing_country'] ) && '' !== $_POST['wcj_filter_by_order_billing_country'] ) {
						if ( ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_country : $order->get_billing_country() ) !== $_POST['wcj_filter_by_order_billing_country'] ) {
							continue;
						}
					}

					foreach ( $order->get_items() as $item_id => $item ) {
						if ( ! apply_filters( 'wcj_export_validation', true, 'order_item', $item ) ) {
							continue;
						}

						$row = $this->get_export_orders_row( $fields_ids, $order_id, $order, null, null, $item, $item_id );

						// Additional Fields.
						$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_items_fields_additional_total_number', 1 ) );
						for ( $i = 1; $i <= $total_number; $i++ ) {
							if ( 'yes' === wcj_get_option( 'wcj_export_orders_items_fields_additional_enabled_' . $i, 'no' ) ) {
								$additional_field_value = wcj_get_option( 'wcj_export_orders_items_fields_additional_value_' . $i, '' );
								if ( '' !== ( $additional_field_value ) ) {
									$field_type = wcj_get_option( 'wcj_export_orders_items_fields_additional_type_' . $i, 'meta' );
									switch ( $field_type ) {
										case 'meta':
											$row[] = $this->safely_get_post_meta( $order_id, $additional_field_value );
											break;
										case 'item_meta':
											$row[] = wcj_maybe_implode( wc_get_order_item_meta( $item_id, $additional_field_value ) );
											break;
										case 'meta_product':
											$product_id = ( 0 !== $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
											$row[]      = $this->safely_get_post_meta( $product_id, $additional_field_value );
											break;
										case 'shortcode':
											global $post;
											$post = get_post( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
											setup_postdata( $post );
											$row[] = wp_strip_all_tags( html_entity_decode( do_shortcode( $additional_field_value ) ) );
											wp_reset_postdata();
											break;
										case 'shortcode_product':
											global $post;
											$product_id = ( 0 !== $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
											$post       = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
											setup_postdata( $post );
											$row[] = do_shortcode( $additional_field_value );
											wp_reset_postdata();
											break;
									}
								} else {
									$row[] = '';
								}
							}
						}

						$data[] = $row;
					}
				}
				$offset += $block_size;
			}
			return $data;
		}

		/**
		 * Export_orders_items_hpos.
		 *
		 * @version 7.1.4
		 * @since  1.0.0
		 * @param object $fields_helper defines the fields helper.
		 */
		public function export_orders_items_hpos( $fields_helper ) {
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			// Standard Fields.
			$all_fields = $fields_helper->get_order_items_export_fields();
			$fields_ids = apply_filters( 'wcj_export_orders_items_fields', wcj_get_option( 'wcj_export_orders_items_fields', $fields_helper->get_order_items_export_default_fields_ids() ) );
			$titles     = array();
			foreach ( $fields_ids as $field_id ) {
				$titles[] = $all_fields[ $field_id ];
			}

			// Additional Fields.
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_items_fields_additional_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_export_orders_items_fields_additional_enabled_' . $i, 'no' ) ) {
					$titles[] = wcj_get_option( 'wcj_export_orders_items_fields_additional_title_' . $i, '' );
				}
			}

			$data       = array();
			$data[]     = $titles;
			$offset     = 0;
			$block_size = 1024;
			while ( true ) {
				$args_orders = array(
					'type'           => 'shop_order',
					'status'         => 'any',
					'posts_per_page' => $block_size,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'offset'         => $offset,
					'fields'         => 'ids',
				);
				$args_orders = wcj_maybe_add_date_query( $args_orders );
				$orders      = wc_get_orders( $args_orders );
				if ( ! $orders ) {
					break;
				}
				foreach ( $orders as $order ) {
					$order_id = $order->get_id();

					$order = wc_get_order( $order_id );
					if ( ! apply_filters( 'wcj_export_validation', true, 'order', $order ) ) {
						continue;
					}

					if ( $wpnonce && isset( $_POST['wcj_filter_by_order_billing_country'] ) && '' !== $_POST['wcj_filter_by_order_billing_country'] ) {
						if ( ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->billing_country : $order->get_billing_country() ) !== $_POST['wcj_filter_by_order_billing_country'] ) {
							continue;
						}
					}

					foreach ( $order->get_items() as $item_id => $item ) {
						if ( ! apply_filters( 'wcj_export_validation', true, 'order_item', $item ) ) {
							continue;
						}

						$row = $this->get_export_orders_row( $fields_ids, $order_id, $order, null, null, $item, $item_id );

						// Additional Fields.
						$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_items_fields_additional_total_number', 1 ) );
						for ( $i = 1; $i <= $total_number; $i++ ) {
							if ( 'yes' === wcj_get_option( 'wcj_export_orders_items_fields_additional_enabled_' . $i, 'no' ) ) {
								$additional_field_value = wcj_get_option( 'wcj_export_orders_items_fields_additional_value_' . $i, '' );
								if ( '' !== ( $additional_field_value ) ) {
									$field_type = wcj_get_option( 'wcj_export_orders_items_fields_additional_type_' . $i, 'meta' );
									switch ( $field_type ) {
										case 'meta':
											$row[] = ( $order->get_meta( $additional_field_value ) );
											break;
										case 'item_meta':
											$row[] = wcj_maybe_implode( wc_get_order_item_meta( $item_id, $additional_field_value ) );
											break;
										case 'meta_product':
											$product_id = ( 0 !== $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
											$row[]      = $this->safely_get_post_meta( $product_id, $additional_field_value );
											break;
										case 'shortcode':
											if ( str_contains( $additional_field_value, ']' ) ) {
												$get_value_custom = $additional_field_value;
												$order_id_custom  = ' order_id="' . $order_id . '"]';
												$custom_shortcode = str_replace( ']', $order_id_custom, $get_value_custom );
											} else {
												$custom_shortcode = $additional_field_value;
											}
											$row[] = wp_strip_all_tags( html_entity_decode( do_shortcode( $custom_shortcode ) ) );
											break;
										case 'shortcode_product':
											global $post;
											$product_id = ( 0 !== $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
											$post       = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
											setup_postdata( $post );
											$row[] = do_shortcode( $additional_field_value );
											wp_reset_postdata();
											break;
									}
								} else {
									$row[] = '';
								}
							}
						}

						$data[] = $row;
					}
				}
				$offset += $block_size;
			}
			return $data;
		}

		/**
		 * Safely_get_post_meta.
		 *
		 * @version 5.6.2
		 * @since   2.6.0
		 * @todo    handle multidimensional arrays
		 * @param int    $post_id defines the post id.
		 * @param string $key defines the key of the post meta.
		 */
		public function safely_get_post_meta( $post_id, $key ) {
			$meta = get_post_meta( $post_id, $key, true );
			if ( is_array( $meta ) ) {
				$meta = implode( ', ', $meta );
			}
			return $meta;
		}

	}

endif;

return new WCJ_Exporter_Orders();

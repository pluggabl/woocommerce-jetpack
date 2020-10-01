<?php
/**
 * Booster for WooCommerce - Functions - Orders
 *
 * @version 5.3.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_get_adjacent_order_id' ) ) {
	/**
	 * wcj_get_adjacent_order_id.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    isn't there an easier way?
	 */
	function wcj_get_adjacent_order_id( $current_id, $direction = 'next' ) {
		$args = array(
			'post_type'      => 'shop_order',
			'post_status'    => array_keys( wc_get_order_statuses() ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		);
		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ) {
			foreach ( $loop->posts as $post_id ) {
				if ( $current_id == $post_id ) {
					return $direction( $loop->posts );
				}
				next( $loop->posts );
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_get_order_status' ) ) {
	/**
	 * wcj_get_order_status.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function wcj_get_order_status( $_order ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $_order->post_status : $_order->get_status() );
	}
}

if ( ! function_exists( 'wcj_get_order_billing_email' ) ) {
	/**
	 * wcj_get_order_billing_email.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function wcj_get_order_billing_email( $_order ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $_order->billing_email : $_order->get_billing_email() );
	}
}

if ( ! function_exists( 'wcj_get_order_date' ) ) {
	/**
	 * wcj_get_order_date.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function wcj_get_order_date( $_order ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $_order->order_date : $_order->get_date_created() );
	}
}

if ( ! function_exists( 'wcj_get_order_id' ) ) {
	/**
	 * wcj_get_order_id.
	 *
	 * @version 3.1.1
	 * @since   2.7.0
	 */
	function wcj_get_order_id( $_order ) {
		if ( ! $_order || ! is_object( $_order ) ) {
			return 0;
		}
		return ( WCJ_IS_WC_VERSION_BELOW_3 ) ? $_order->id : $_order->get_id();
	}
}

if ( ! function_exists( 'wcj_get_order_currency' ) ) {
	/**
	 * wcj_get_order_currency.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_get_order_currency( $_order ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $_order->get_order_currency() : $_order->get_currency() );
	}
}

if ( ! function_exists( 'wcj_get_order_item_meta_info' ) ) {
	/**
	 * wcj_get_order_item_meta_info.
	 *
	 * from woocommerce\includes\admin\meta-boxes\views\html-order-item-meta.php
	 *
	 * @version 5.3.0
	 * @since   2.5.9
	 */
	function wcj_get_order_item_meta_info( $item_id, $item, $_order, $exclude_wcj_meta = false, $_product = null, $exclude_meta = array() ) {
		$meta_info = '';
		$metadata = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_order->has_meta( $item_id ) : $item->get_meta_data() );
		if ( $metadata ) {
			$meta_info = array();
			foreach ( $metadata as $meta ) {

				$_meta_key   = ( WCJ_IS_WC_VERSION_BELOW_3 ? $meta['meta_key']   : $meta->key );
				$_meta_value = ( WCJ_IS_WC_VERSION_BELOW_3 ? $meta['meta_value'] : $meta->value );

				// Skip hidden core fields
				if ( in_array( $_meta_key, apply_filters( 'woocommerce_hidden_order_itemmeta', array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
					'method_id',
					'cost'
				) ) ) ) {
					continue;
				}

				if ( ! empty( $exclude_meta ) && in_array( $_meta_key, $exclude_meta ) ) {
					continue;
				}

				if ( $exclude_wcj_meta && ( 'wcj' === substr( $_meta_key, 0, 3 ) || '_wcj' === substr( $_meta_key, 0, 4 ) ) ) {
					continue;
				}

				if ( $exclude_wcj_meta && 'is_custom' === $_meta_key ) {
					continue;
				}

				// Skip serialised meta
				if ( is_serialized( $_meta_value ) || is_array( $_meta_value ) ) {
					continue;
				}

				// Get attribute data
				if ( taxonomy_exists( wc_sanitize_taxonomy_name( $_meta_key ) ) ) {
					$term        = get_term_by( 'slug', $_meta_value, wc_sanitize_taxonomy_name( $_meta_key ) );
					$_meta_key   = wc_attribute_label( wc_sanitize_taxonomy_name( $_meta_key ) );
					$_meta_value = isset( $term->name ) ? $term->name : $_meta_value;
				} else {
					$the_product = null;
					if ( is_object( $_product ) ) {
						$the_product = $_product;
					} elseif ( is_object( $item ) ) {
						$the_product = $item->get_product();
					}
					$_meta_key   = ( is_object( $the_product ) ) ? wc_attribute_label( $_meta_key, $the_product ) : $_meta_key;
				}
				$meta_info[] = wp_kses_post( rawurldecode( $_meta_key ) ) . ': ' . wp_kses_post( rawurldecode( $_meta_value ) );
			}
			$meta_info = implode( wcj_get_option( 'wcj_general_item_meta_separator', ', ' ), $meta_info );
		}
		return $meta_info;
	}
}

if ( ! function_exists( 'wcj_get_order_statuses' ) ) {
	/**
	 * wcj_get_order_statuses.
	 *
	 * @version 3.2.2
	 * @since   2.9.0
	 */
	function wcj_get_order_statuses( $cut_prefix = true ) {
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		if ( ! $cut_prefix ) {
			return $statuses;
		}
		$result = array();
		foreach( $statuses as $status => $status_name ) {
			$result[ substr( $status, 3 ) ] = $status_name;
		}
		return $result;
	}
}

if ( ! function_exists( 'wcj_order_get_payment_method' ) ) {
	/**
	 * wcj_order_get_payment_method.
	 *
	 * @version 2.8.2
	 * @since   2.8.0
	 */
	function wcj_order_get_payment_method( $_order ) {
		if ( ! $_order || ! is_object( $_order ) ) {
			return null;
		}
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return ( isset( $_order->payment_method ) ? $_order->payment_method : null );
		} else {
			return ( method_exists( $_order, 'get_payment_method' ) ? $_order->get_payment_method() : null );
		}
	}
}

if ( ! function_exists( 'wcj_get_order_fees_total' ) ) {
	/**
	 * wcj_get_order_fees_total.
	 *
	 * @version 4.5.0
	 * @since   4.5.0
	 *
	 * @param $_order
	 *
	 * @return int
	 */
	function wcj_get_order_fees_total( $_order ) {
		$fees_total = 0;
		foreach ( $_order->get_fees() as $fee ) {
			$fees_total += $fee->get_total();
		}
		return $fees_total;
	}
}

if ( ! function_exists( 'wcj_get_order_fees_total_tax' ) ) {
	/**
	 * wcj_get_order_fees_total_tax.
	 *
	 * @version 4.5.0
	 * @since   4.5.0
	 *
	 * @param $_order
	 *
	 * @return int
	 */
	function wcj_get_order_fees_total_tax( $_order ) {
		$fees_total = 0;
		foreach ( $_order->get_fees() as $fee ) {
			$fees_total += $fee->get_total_tax();
		}
		return $fees_total;
	}
}


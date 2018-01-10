<?php
/**
 * Booster for WooCommerce - Module - Order Numbers
 *
 * @version 3.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Order_Numbers' ) ) :

class WCJ_Order_Numbers extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.3
	 * @todo    (maybe) add option (order meta box) to set number directly
	 * @todo    (maybe) rename "Orders Renumerate" to "Renumerate orders"
	 * @todo    (maybe) use `woocommerce_new_order` hook instead of `wp_insert_post`
	 */
	function __construct() {

		$this->id         = 'order_numbers';
		$this->short_desc = __( 'Order Numbers', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce sequential order numbering, custom order number prefix, suffix and number width.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-custom-order-numbers';
		parent::__construct();

		$this->add_tools( array(
			'renumerate_orders' => array(
				'title'     => __( 'Orders Renumerate', 'woocommerce-jetpack' ),
				'desc'      => __( 'Tool renumerates all orders.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {
			add_action( 'wp_insert_post',           array( $this, 'add_new_order_number' ), PHP_INT_MAX );
			add_filter( 'woocommerce_order_number', array( $this, 'display_order_number' ), PHP_INT_MAX, 2 );
			if ( 'yes' === get_option( 'wcj_order_number_order_tracking_enabled', 'yes' ) ) {
				add_filter( 'woocommerce_shortcode_order_tracking_order_id', array( $this, 'add_order_number_to_tracking' ), PHP_INT_MAX );
				add_action( 'init',                                          array( $this, 'remove_order_tracking_sanitize_order_id_filter' ) );
			}
			if ( 'yes' === get_option( 'wcj_order_number_search_by_custom_number_enabled', 'yes' ) ) {
				add_action( 'pre_get_posts', array( $this, 'search_by_custom_number' ) );
			}
			// "WooCommerce Subscriptions" plugin
			$woocommerce_subscriptions_types = array( 'subscription', 'renewal_order', 'resubscribe_order', 'copy_order' );
			foreach ( $woocommerce_subscriptions_types as $woocommerce_subscriptions_type ) {
				add_filter( 'wcs_' . $woocommerce_subscriptions_type . '_meta', array( $this, 'woocommerce_subscriptions_remove_meta_copy' ), PHP_INT_MAX, 3 );
			}
		}
	}

	/**
	 * woocommerce_subscriptions_remove_meta_copy.
	 *
	 * @version 3.2.3
	 * @since   3.2.3
	 */
	function woocommerce_subscriptions_remove_meta_copy( $meta, $to_order, $from_order ) {
		foreach ( $meta as $meta_id => $meta_item ) {
			if ( '_wcj_order_number' === $meta_item['meta_key'] ) {
				unset( $meta[ $meta_id ] );
			}
		}
		return $meta;
	}

	/**
	 * remove_order_tracking_sanitize_order_id_filter.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function remove_order_tracking_sanitize_order_id_filter() {
		remove_filter( 'woocommerce_shortcode_order_tracking_order_id', 'wc_sanitize_order_id' );
	}

	/**
	 * search_by_custom_number.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 * @see     https://github.com/pablo-pacheco/wc-booster-search-order-by-custom-number-fix
	 * @todo    `_wcj_order_number` is used for `sequential` and `hash` only
	 */
	function search_by_custom_number( $query ) {
		if (
			! is_admin() ||
			! isset( $query->query ) ||
			! isset( $query->query['s'] ) ||
			false === is_numeric( $query->query['s'] ) ||
			0 == $query->query['s'] ||
			'shop_order' !== $query->query['post_type'] ||
			! $query->query_vars['shop_order_search']
		) {
			return;
		}
		$custom_order_id = $query->query['s'];
		$query->query_vars['post__in'] = array();
		$query->query['s'] = '';
		$query->set( 'meta_key', '_wcj_order_number' );
		$query->set( 'meta_value', $custom_order_id );
	}

	/**
	 * add_order_number_to_tracking.
	 *
	 * @version 3.1.0
	 * @since   2.5.2
	 */
	function add_order_number_to_tracking( $order_number ) {
		$offset     = 0;
		$block_size = 512;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $_order_id ) {
				$_order        = wc_get_order( $_order_id );
				$_order_number = $this->display_order_number( $_order_id, $_order );
				if ( $_order_number === $order_number ) {
					return $_order_id;
				}
			}
			$offset += $block_size;
		}
		return $order_number;
	}

	/**
	 * Display order number.
	 *
	 * @version 2.7.0
	 */
	function display_order_number( $order_number, $order ) {
		$order_id = wcj_get_order_id( $order );
		$order_number_meta = get_post_meta( $order_id , '_wcj_order_number', true );
		if ( '' == $order_number_meta || 'no' === get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
			$order_number_meta = $order_id;
		}
		$order_timestamp = strtotime( ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->post->post_date : $order->get_date_created() ) );
		$order_number = apply_filters( 'booster_option',
			sprintf( '%s%s', do_shortcode( get_option( 'wcj_order_number_prefix', '' ) ), $order_number_meta ),
			sprintf( '%s%s%0' . get_option( 'wcj_order_number_min_width', 0 ) . 's%s%s',
				do_shortcode( get_option( 'wcj_order_number_prefix', '' ) ),
				date_i18n( get_option( 'wcj_order_number_date_prefix', '' ), $order_timestamp ),
				$order_number_meta,
				do_shortcode( get_option( 'wcj_order_number_suffix', '' ) ),
				date_i18n( get_option( 'wcj_order_number_date_suffix', '' ), $order_timestamp )
			)
		);
		return $order_number;
	}

	/**
	 * Add Renumerate Orders tool to WooCommerce menu (the content).
	 *
	 * @version 3.3.0
	 * @todo    restyle
	 * @todo    add more result info (e.g. number of regenerated orders etc.)
	 */
	function create_renumerate_orders_tool() {
		$result_message = '';
		if ( isset( $_POST['renumerate_orders'] ) ) {
			$this->renumerate_orders();
			$result_message = '<p><div class="updated"><p><strong>' . __( 'Orders successfully renumerated!', 'woocommerce-jetpack' ) . '</strong></p></div></p>';
		} else {
			if ( 'yes' === get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
				$result_message .= '<p>' . sprintf( __( 'Sequential number generation is enabled. Next order number will be %s.', 'woocommerce-jetpack' ),
					'<code>' . get_option( 'wcj_order_number_counter', 1 ) . '</code>' ) . '</p>';
			}
		}
		$html = '';
		$html .= '<div class="wrap">';
		$html .= $this->get_tool_header_html( 'renumerate_orders' );
		$html .= '<p>';
		$html .= sprintf(
			__( 'Press the button below to renumerate all existing orders starting from order counter settings in <a href="%s">Order Numbers</a> module.',
				'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=shipping_and_orders&section=order_numbers' )
		);
		$html .= '</p>';
		$html .= $result_message;
		$html .= '<form method="post" action="">';
		$html .= '<input class="button-primary" type="submit" name="renumerate_orders" value="' . __( 'Renumerate orders', 'woocommerce-jetpack' ) . '">';
		$html .= '</form>';
		$html .= '</div>';
		echo $html;
	}

	/**
	 * add_new_order_number.
	 */
	function add_new_order_number( $order_id ) {
		$this->add_order_number_meta( $order_id, false );
	}

	/**
	 * maybe_reset_sequential_counter.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    use transactions on `wcj_order_number_use_mysql_transaction_enabled`
	 */
	function maybe_reset_sequential_counter( $current_order_number, $order_id ) {
		if ( 'no' != ( $reset_period = get_option( 'wcj_order_number_counter_reset_enabled', 'no' ) ) ) {
			$previous_order_date = get_option( 'wcj_order_number_counter_previous_order_date', 0 );
			$current_order_date  = strtotime( wcj_get_order_date( wc_get_order( $order_id ) ) );
			update_option( 'wcj_order_number_counter_previous_order_date', $current_order_date );
			if ( 0 != $previous_order_date ) {
				$do_reset = false;
				switch ( $reset_period ) {
					case 'daily':
						$do_reset = (
							date( 'Y', $current_order_date ) != date( 'Y', $previous_order_date ) ||
							date( 'm', $current_order_date ) != date( 'm', $previous_order_date ) ||
							date( 'd', $current_order_date ) != date( 'd', $previous_order_date )
						);
						break;
					case 'monthly':
						$do_reset = (
							date( 'Y', $current_order_date ) != date( 'Y', $previous_order_date ) ||
							date( 'm', $current_order_date ) != date( 'm', $previous_order_date )
						);
						break;
					case 'yearly':
						$do_reset = (
							date( 'Y', $current_order_date ) != date( 'Y', $previous_order_date )
						);
						break;
				}
				if ( $do_reset ) {
					return 1;
				}
			}
		}
		return $current_order_number;
	}

	/**
	 * Add/update order_number meta to order.
	 *
	 * @version 3.3.0
	 */
	function add_order_number_meta( $order_id, $do_overwrite ) {
		if ( 'shop_order' !== get_post_type( $order_id ) || 'auto-draft' === get_post_status( $order_id ) ) {
			return;
		}
		if ( true === $do_overwrite || 0 == get_post_meta( $order_id, '_wcj_order_number', true ) ) {
			if ( 'yes' === get_option( 'wcj_order_number_sequential_enabled', 'yes' ) && 'yes' === get_option( 'wcj_order_number_use_mysql_transaction_enabled', 'yes' ) ) {
				global $wpdb;
				$wpdb->query( 'START TRANSACTION' );
				$wp_options_table = $wpdb->prefix . 'options';
				$result_select = $wpdb->get_row( "SELECT * FROM $wp_options_table WHERE option_name = 'wcj_order_number_counter'" );
				if ( NULL != $result_select ) {
					$current_order_number = $this->maybe_reset_sequential_counter( $result_select->option_value, $order_id );
					$result_update = $wpdb->update( $wp_options_table, array( 'option_value' => ( $current_order_number + 1 ) ), array( 'option_name' => 'wcj_order_number_counter' ) );
					if ( NULL != $result_update ) {
						$wpdb->query( 'COMMIT' ); // all ok
						update_post_meta( $order_id, '_wcj_order_number', $current_order_number );
					} else {
						$wpdb->query( 'ROLLBACK' ); // something went wrong, Rollback
					}
				} else {
					$wpdb->query( 'ROLLBACK' ); // something went wrong, Rollback
				}
			} else {
				if ( 'hash_crc32' === get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
					$current_order_number = sprintf( "%u", crc32( $order_id ) );
				} elseif ( 'yes' === get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
					$current_order_number = $this->maybe_reset_sequential_counter( get_option( 'wcj_order_number_counter', 1 ), $order_id );
					update_option( 'wcj_order_number_counter', ( $current_order_number + 1 ) );
				} else { // 'no' === get_option( 'wcj_order_number_sequential_enabled', 'yes' ) // order ID
					$current_order_number = '';
				}
				update_post_meta( $order_id, '_wcj_order_number', $current_order_number );
			}
		}
	}

	/**
	 * Renumerate orders function.
	 *
	 * @version 3.3.0
	 * @todo    renumerate in date range only
	 * @todo    (maybe) selectable `post_status`
	 * @todo    (maybe) set default value for `wcj_order_numbers_renumerate_tool_orderby` to `ID` (instead of `date`)
	 */
	function renumerate_orders() {
		if ( 'yes' === get_option( 'wcj_order_number_sequential_enabled', 'yes' ) && 'no' != get_option( 'wcj_order_number_counter_reset_enabled', 'no' ) ) {
			update_option( 'wcj_order_number_counter_previous_order_date', 0 );
		}
		$offset     = 0;
		$block_size = 512;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => get_option( 'wcj_order_numbers_renumerate_tool_orderby', 'date' ),
				'order'          => get_option( 'wcj_order_numbers_renumerate_tool_order',   'ASC' ),
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $order_id ) {
				$this->add_order_number_meta( $order_id, true );
			}
			$offset += $block_size;
		}
	}

}

endif;

return new WCJ_Order_Numbers();

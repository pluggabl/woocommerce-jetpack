<?php
/**
 * Booster for WooCommerce - Module - Order Numbers
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Order_Numbers' ) ) :

class WCJ_Order_Numbers extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
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
//				'tab_title' => __( 'Renumerate orders', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {
//			add_action( 'woocommerce_new_order',    array( $this, 'add_new_order_number' ), PHP_INT_MAX );
			add_action( 'wp_insert_post',           array( $this, 'add_new_order_number' ), PHP_INT_MAX );
			add_filter( 'woocommerce_order_number', array( $this, 'display_order_number' ), PHP_INT_MAX, 2 );
			if ( 'yes' === get_option( 'wcj_order_number_order_tracking_enabled', 'yes' ) ) {
				add_filter( 'woocommerce_shortcode_order_tracking_order_id', array( $this, 'add_order_number_to_tracking' ), PHP_INT_MAX );
			}
			if ( 'yes' === get_option( 'wcj_order_number_search_by_custom_number_enabled', 'yes' ) ) {
				add_action( 'pre_get_posts', array( $this, 'search_by_custom_number' ) );
			}
		}
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
	 * @version 2.5.2
	 * @since   2.5.2
	 * @todo    optimize WP_Query to return only `ids` for `fields`
	 */
	function add_order_number_to_tracking( $order_number ) {
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$_order_id = $loop->post->ID;
				$_order = wc_get_order( $_order_id );
				$_order_number = $this->display_order_number( $_order_id, $_order );
				if ( $_order_number === $order_number ) {
					return $_order_id;
				}
			endwhile;
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
		$order_number = apply_filters( 'booster_get_option',
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
	 * @version 2.4.4
	 */
	function create_renumerate_orders_tool() {
		$result_message = '';
		if ( isset( $_POST['renumerate_orders'] ) ) {
			$this->renumerate_orders();
			$result_message = '<p><div class="updated"><p><strong>' . __( 'Orders successfully renumerated!', 'woocommerce-jetpack' ) . '</strong></p></div></p>';
		}
		?><div>
			<?php echo $this->get_tool_header_html( 'renumerate_orders' ); ?>
			<p><?php echo __( 'Press the button below to renumerate all existing orders starting from order counter settings in WooCommerce > Settings > Booster > Order Numbers.', 'woocommerce-jetpack' ); ?></p>
			<?php echo $result_message; ?>
			<form method="post" action="">
				<input class="button-primary" type="submit" name="renumerate_orders" value="<?php echo __( 'Renumerate orders', 'woocommerce-jetpack' ); ?>">
			</form>
		</div><?php
	}

	/**
	 * add_new_order_number.
	 */
	function add_new_order_number( $order_id ) {
		$this->add_order_number_meta( $order_id, false );
	}

	/**
	 * Add/update order_number meta to order.
	 *
	 * @version 2.7.0
	 */
	function add_order_number_meta( $order_id, $do_overwrite ) {
		if ( 'shop_order' !== get_post_type( $order_id ) ) {
			return;
		}
		if ( true === $do_overwrite || 0 == get_post_meta( $order_id, '_wcj_order_number', true ) ) {
			if ( 'yes' === get_option( 'wcj_order_number_sequential_enabled', 'yes' ) && 'yes' === get_option( 'wcj_order_number_use_mysql_transaction_enabled', 'yes' ) ) {
				global $wpdb;
				$wpdb->query( 'START TRANSACTION' );
				$wp_options_table = $wpdb->prefix . 'options';
				$result_select = $wpdb->get_row( "SELECT * FROM $wp_options_table WHERE option_name = 'wcj_order_number_counter'" );
				if ( NULL != $result_select ) {
					$current_order_number = $result_select->option_value;
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
				} else { // sequential
					$current_order_number = get_option( 'wcj_order_number_counter', 1 );
					update_option( 'wcj_order_number_counter', ( $current_order_number + 1 ) );
				}
				update_post_meta( $order_id, '_wcj_order_number', $current_order_number );
			}
		}
	}

	/**
	 * Renumerate orders function.
	 *
	 * @version 2.5.0
	 * @todo    renumerate in date range only
	 * @todo    optimize WP_Query to return only `ids` for `fields`
	 */
	function renumerate_orders() {
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$order_id = $loop->post->ID;
				$this->add_order_number_meta( $order_id, true );
			endwhile;
			$offset += $block_size;
		}
	}

}

endif;

return new WCJ_Order_Numbers();

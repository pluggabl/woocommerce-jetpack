<?php
/**
 * WooCommerce Jetpack Order Numbers
 *
 * The WooCommerce Jetpack Order Numbers class.
 *
 * @version 2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Order_Numbers' ) ) :

class WCJ_Order_Numbers extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 */
	public function __construct() {

		$this->id         = 'order_numbers';
		$this->short_desc = __( 'Order Numbers', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce sequential order numbering, custom order number prefix, suffix and number width.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-custom-order-numbers/';
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
		}
	}

	/**
	 * add_order_number_to_tracking.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
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
	 * @version 2.2.7
	 */
	public function display_order_number( $order_number, $order ) {
		$order_number_meta = get_post_meta( $order->id, '_wcj_order_number', true );
		if ( '' == $order_number_meta || 'no' === get_option( 'wcj_order_number_sequential_enabled' ) ) {
			$order_number_meta = $order->id;
		}
		$order_timestamp = strtotime( $order->post->post_date );
		$order_number = apply_filters( 'wcj_get_option_filter',
			sprintf( '%s%d', do_shortcode( get_option( 'wcj_order_number_prefix', '' ) ), $order_number_meta ),
			sprintf( '%s%s%0' . get_option( 'wcj_order_number_min_width', 0 ) . 'd%s%s',
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
	public function create_renumerate_orders_tool() {
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
	public function add_new_order_number( $order_id ) {
		$this->add_order_number_meta( $order_id, false );
	}

	/**
	 * Add/update order_number meta to order.
	 *
	 * @version 2.4.4
	 */
	public function add_order_number_meta( $order_id, $do_overwrite ) {
		if ( 'shop_order' !== get_post_type( $order_id ) ) {
			return;
		}
		if ( true === $do_overwrite || 0 == get_post_meta( $order_id, '_wcj_order_number', true ) ) {
			if ( 'yes' === get_option( 'wcj_order_number_use_mysql_transaction_enabled', 'no' ) ) {
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
				$current_order_number = get_option( 'wcj_order_number_counter' );
				update_option( 'wcj_order_number_counter', ( $current_order_number + 1 ) );
				update_post_meta( $order_id, '_wcj_order_number', $current_order_number );
			}
		}
	}

	/**
	 * Renumerate orders function.
	 *
	 * @version 2.5.0
	 */
	public function renumerate_orders() {
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

	/**
	 * get_settings.
	 *
	 * @version 2.5.2
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Order Numbers', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you enable sequential order numbering, set custom number prefix, suffix and width.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_numbers_options',
			),
			array(
				'title'    => __( 'Make Order Numbers Sequential', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_sequential_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Next Order Number', 'woocommerce-jetpack' ),
				'desc'     => __( 'Next new order will be given this number.', 'woocommerce-jetpack' ) . ' ' . __( 'Use Renumerate Orders tool for existing orders.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This will be ignored if sequential order numbering is disabled.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_counter',
				'default'  => 1,
				'type'     => 'number',
			),
			array(
				'title'    => __( 'Order Number Custom Prefix', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Prefix before order number (optional). This will change the prefixes for all existing orders.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_prefix',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:300px;',
			),
			array(
				'title'    => __( 'Order Number Date Prefix', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Date prefix before order number (optional). This will change the prefixes for all existing orders. Value is passed directly to PHP `date` function, so most of PHP date formats can be used. The only exception is using `\` symbol in date format, as this symbol will be excluded from date. Try: Y-m-d- or mdy.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_date_prefix',
				'default'  => '',
				'type'     => 'text',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'      => 'width:300px;',
			),
			array(
				'title'    => __( 'Order Number Width', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Minimum width of number without prefix (zeros will be added to the left side). This will change the minimum width of order number for all existing orders. E.g. set to 5 to have order number displayed as 00001 instead of 1. Leave zero to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_min_width',
				'default'  => 0,
				'type'     => 'number',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'      => 'width:300px;',
			),
			array(
				'title'    => __( 'Order Number Custom Suffix', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Suffix after order number (optional). This will change the suffixes for all existing orders.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_suffix',
				'default'  => '',
				'type'     => 'text',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'      => 'width:300px;',
			),
			array(
				'title'    => __( 'Order Number Date Suffix', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Date suffix after order number (optional). This will change the suffixes for all existing orders. Value is passed directly to PHP `date` function, so most of PHP date formats can be used. The only exception is using `\` symbol in date format, as this symbol will be excluded from date. Try: Y-m-d- or mdy.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_date_suffix',
				'default'  => '',
				'type'     => 'text',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'css'      => 'width:300px;',
			),
			array(
				'title'    => __( 'Use MySQL Transaction', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This should be enabled if you have a lot of simultaneous orders in your shop - to prevent duplicate order numbers (sequential).', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_use_mysql_transaction_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Enable Order Tracking by Custom Number', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_number_order_tracking_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_order_numbers_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Order_Numbers();

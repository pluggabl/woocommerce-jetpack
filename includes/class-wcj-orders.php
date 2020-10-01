<?php
/**
 * Booster for WooCommerce - Module - Orders
 *
 * @version 5.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Orders' ) ) :

class WCJ_Orders extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @todo    Bulk Regenerate Download Permissions - copy "cron" to plugin
	 * @todo    Bulk Regenerate Download Permissions - maybe move "bulk actions" to free
	 * @todo    Bulk Regenerate Download Permissions - maybe as new module
	 */
	function __construct() {

		$this->id         = 'orders';
		$this->short_desc = __( 'Orders', 'woocommerce-jetpack' );
		$this->desc       = __( 'Orders auto-complete; admin order currency; admin order navigation; bulk regenerate download permissions for orders (Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Orders auto-complete; admin order currency; admin order navigation; bulk regenerate download permissions for orders.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-orders';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Order auto complete
			if ( 'yes' === wcj_get_option( 'wcj_order_auto_complete_enabled', 'no' ) ) {
				add_action( 'woocommerce_thankyou',         array( $this, 'auto_complete_order' ), PHP_INT_MAX );
				add_action( 'woocommerce_payment_complete', array( $this, 'auto_complete_order' ), PHP_INT_MAX );
			}

			// Order currency
			if ( 'yes' === wcj_get_option( 'wcj_order_admin_currency', 'no' ) ) {
				$this->meta_box_screen = 'shop_order';
				add_action( 'add_meta_boxes',       array( $this, 'add_meta_box' ) );
				add_action( 'save_post_shop_order', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				if ( 'filter' === wcj_get_option( 'wcj_order_admin_currency_method', 'filter' ) ) {
					$woocommerce_get_order_currency_filter = ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_order_currency' : 'woocommerce_order_get_currency' );
					add_filter( $woocommerce_get_order_currency_filter, array( $this, 'change_order_currency' ), PHP_INT_MAX, 2 );
				}
			}

			// Bulk Regenerate Download Permissions
			if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_enabled', 'no' ) ) ) {
				// Actions
				if ( 'yes' === wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_actions', 'no' ) ) {
					add_filter( 'bulk_actions-edit-shop_order',        array( $this, 'register_bulk_actions_regenerate_download_permissions' ), PHP_INT_MAX );
					add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_actions_regenerate_download_permissions' ), 10, 3 );
				}
				// All orders
				add_action( 'woojetpack_after_settings_save', array( $this, 'maybe_bulk_regenerate_download_permissions_all_orders' ) );
				// Admin notices
				add_filter( 'admin_notices', array( $this, 'admin_notice_regenerate_download_permissions' ) );
				// All orders - Cron
				if ( 'disabled' != apply_filters( 'booster_option', 'disabled', wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_all_orders_cron', 'disabled' ) ) ) {
					add_action( 'init',       array( $this, 'schedule_bulk_regenerate_download_permissions_all_orders_cron' ) );
					add_action( 'admin_init', array( $this, 'schedule_bulk_regenerate_download_permissions_all_orders_cron' ) );
					add_filter( 'cron_schedules', 'wcj_crons_add_custom_intervals' );
					add_action( 'wcj_bulk_regenerate_download_permissions_all_orders_cron', array( $this, 'bulk_regenerate_download_permissions_all_orders' ) );
				}
			}

			// Country by IP
			if ( 'yes' === wcj_get_option( 'wcj_orders_country_by_ip_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes', array( $this, 'add_country_by_ip_meta_box' ) );
			}

			// Orders navigation
			if ( 'yes' === wcj_get_option( 'wcj_orders_navigation_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes', array( $this, 'add_orders_navigation_meta_box' ) );
				add_action( 'admin_init',     array( $this, 'handle_orders_navigation' ) );
			}

			// Editable orders
			if ( 'yes' === wcj_get_option( 'wcj_orders_editable_status_enabled', 'no' ) ) {
				add_filter( 'wc_order_is_editable', array( $this, 'editable_status' ), PHP_INT_MAX, 2 );
			}

		}
	}

	/**
	 * editable_status.
	 *
	 * @version 4.0.0
	 * @since   4.0.0
	 */
	function editable_status( $is_editable, $order ) {
		return in_array( $order->get_status(), wcj_get_option( 'wcj_orders_editable_status', array( 'pending', 'on-hold', 'auto-draft' ) ), true );
	}

	/**
	 * handle_orders_navigation.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function handle_orders_navigation() {
		if ( isset( $_GET['wcj_orders_navigation'] ) ) {
			$url = ( ! isset( $_GET['post'] ) || false === ( $adjacent_order_id = wcj_get_adjacent_order_id( $_GET['post'], $_GET['wcj_orders_navigation'] ) ) ?
				remove_query_arg( 'wcj_orders_navigation' ) :
				admin_url( 'post.php?post=' . $adjacent_order_id . '&action=edit' ) );
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * add_orders_navigation_meta_box.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function add_orders_navigation_meta_box() {
		add_meta_box(
			'wc-jetpack-' . $this->id . '-navigation',
			__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Order Navigation', 'woocommerce-jetpack' ),
			array( $this, 'create_orders_navigation_meta_box' ),
			'shop_order',
			'side',
			'high'
		);
	}

	/**
	 * create_orders_navigation_meta_box.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    this will output the link, even if there no prev/next orders available
	 */
	function create_orders_navigation_meta_box() {
		echo '<a href="' . add_query_arg( 'wcj_orders_navigation', 'prev' ) . '">' . '&lt;&lt; ' . __( 'Previous order', 'woocommerce-jetpack' ) . '</a>' .
			 '<a href="' . add_query_arg( 'wcj_orders_navigation', 'next' ) . '" style="float:right;">' . __( 'Next order', 'woocommerce-jetpack' ) . ' &gt;&gt;' . '</a>';
	}

	/**
	 * add_country_by_ip_meta_box.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function add_country_by_ip_meta_box() {
		add_meta_box(
			'wc-jetpack-' . $this->id . '-country-by-ip',
			__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Country by IP', 'woocommerce-jetpack' ),
			array( $this, 'create_country_by_ip_meta_box' ),
			'shop_order',
			'side',
			'low'
		);
	}

	/**
	 * create_country_by_ip_meta_box.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function create_country_by_ip_meta_box() {
		if (
			class_exists( 'WC_Geolocation' ) &&
			( $order = wc_get_order() ) &&
			( $customer_ip = $order->get_customer_ip_address() ) &&
			( $location = WC_Geolocation::geolocate_ip( $customer_ip ) ) &&
			isset( $location['country'] ) && '' != $location['country']
		) {
			echo wcj_get_country_flag_by_code( $location['country'] ) . ' ' .
				wcj_get_country_name_by_code( $location['country'] ) .
				' (' . $location['country'] . ')' .
				' [' . $customer_ip . ']';
		} else {
			echo '<em>' . __( 'No data.', 'woocommerce-jetpack' ) . '</em>';
		}
	}

	/**
	 * schedule_bulk_regenerate_download_permissions_all_orders_cron.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function schedule_bulk_regenerate_download_permissions_all_orders_cron() {
		wcj_crons_schedule_the_events(
			'wcj_bulk_regenerate_download_permissions_all_orders_cron',
			apply_filters( 'booster_option', 'disabled', wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_all_orders_cron', 'disabled' ) )
		);
	}

	/**
	 * handle_bulk_actions_regenerate_download_permissions.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 * @see     https://make.wordpress.org/core/2016/10/04/custom-bulk-actions/
	 * @todo    (maybe) "bulk actions" for for WP < 4.7
	 */
	function handle_bulk_actions_regenerate_download_permissions( $redirect_to, $doaction, $post_ids ) {
		if ( $doaction !== 'wcj_regenerate_download_permissions' ) {
			return $redirect_to;
		}
		$data_store = WC_Data_Store::load( 'customer-download' );
		foreach ( $post_ids as $post_id ) {
			$data_store->delete_by_order_id( $post_id );
			wc_downloadable_product_permissions( $post_id, true );
		}
		$redirect_to = add_query_arg( 'wcj_bulk_regenerated_download_permissions', count( $post_ids ), $redirect_to );
		return $redirect_to;
	}

	/**
	 * register_bulk_actions_regenerate_download_permissions.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function register_bulk_actions_regenerate_download_permissions( $bulk_actions ) {
		$bulk_actions['wcj_regenerate_download_permissions'] = __( 'Regenerate download permissions', 'woocommerce-jetpack' );
		return $bulk_actions;
	}

	/**
	 * admin_notice_regenerate_download_permissions.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function admin_notice_regenerate_download_permissions() {
		if ( ! empty( $_REQUEST['wcj_bulk_regenerated_download_permissions'] ) ) {
			$orders_count = intval( $_REQUEST['wcj_bulk_regenerated_download_permissions'] );
			$message = sprintf(
				_n( 'Download permissions regenerated for %s order.', 'Download permissions regenerated for %s orders.', $orders_count, 'woocommerce-jetpack' ),
				'<strong>' . $orders_count . '</strong>'
			);
			echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
		}
	}

	/**
	 * bulk_regenerate_download_permissions_all_orders.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function bulk_regenerate_download_permissions_all_orders() {
		$data_store   = WC_Data_Store::load( 'customer-download' );
		$block_size   = 512;
		$offset       = 0;
		$total_orders = 0;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $post_id ) {
				$data_store->delete_by_order_id( $post_id );
				wc_downloadable_product_permissions( $post_id, true );
				$total_orders++;
			}
			$offset += $block_size;
		}
		return $total_orders;
	}

	/**
	 * maybe_bulk_regenerate_download_permissions_all_orders.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function maybe_bulk_regenerate_download_permissions_all_orders() {
		if ( 'yes' === wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_all_orders', 'no' ) ) {
			update_option( 'wcj_order_bulk_regenerate_download_permissions_all_orders', 'no' );
			$total_orders = $this->bulk_regenerate_download_permissions_all_orders();
			wp_safe_redirect( add_query_arg( 'wcj_bulk_regenerated_download_permissions', $total_orders ) );
			exit;
		}
	}

	/**
	 * change_order_currency.
	 *
	 * @version 2.7.0
	 * @since   2.5.6
	 * @todo    (maybe) move meta box to `side`
	 */
	function change_order_currency( $order_currency, $_order ) {
		return ( '' != ( $wcj_order_currency = get_post_meta( wcj_get_order_id( $_order ), '_' . 'wcj_order_currency', true ) ) ) ? $wcj_order_currency : $order_currency;
	}

	/**
	* Auto Complete all WooCommerce orders.
	*
	* @version 3.7.0
	* @todo    (maybe) at first check if status is not `completed` already (however `WC_Order::set_status()` checks that anyway)
	*/
	function auto_complete_order( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		$payment_methods = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_order_auto_complete_payment_methods', array() ) );
		if ( ! empty( $payment_methods ) && ! in_array( $order->get_payment_method(), $payment_methods ) ) {
			return;
		}
		$order->update_status( 'completed' );
	}

}

endif;

return new WCJ_Orders();

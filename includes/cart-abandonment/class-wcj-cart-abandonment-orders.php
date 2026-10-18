<?php
/**
 * Booster Elite for WooCommerce - Module - Cart Abandonment Orders
 *
 * @version 7.2.7
 * @author  Pluggabl LLC.
 * @package Booster_Elite_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'WCJ_Cart_9.7.1Abandonment_Orders' ) ) :

	/**
	 * WCJ_Cart_Abandonment_Orders.
	 *
	 * @version 6.0.1
	 */
	class WCJ_Cart_Abandonment_Orders extends WP_List_Table {

		/**
		 * Constructor.
		 *
		 * @version 6.0.1
		 */
		public function __construct() {
			global $status, $page;
			parent::__construct(
				array(
					'singular' => __( 'Cart Abandonment Order', 'gift-voucher' ),
					'plural'   => __( 'Cart Abandonment Orders', 'gift-voucher' ),
					'ajax'     => true,
				)
			);
		}

		/**
		 * No_items.
		 *
		 * @version 6.0.1
		 */
		public function no_items() {
			esc_html_e( 'No Data Found.' );
		}

		/**
		 * Column_default.
		 *
		 * @version 6.0.1
		 * @param array  $item contains the columns with data.
		 * @param string $column_name contains the column name.
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'email':
				case 'cart_total':
				case 'order_status':
				case 'time':
					return $item[ $column_name ];
				default:
					return is_array( $item ) ? wp_json_encode( $item ) : $item; // Show the whole array for troubleshooting purposes.
			}
		}

		/**
		 * Get_sortable_columns.
		 *
		 * @version 6.0.1
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'email'        => array( 'email', false ),
				'cart_total'   => array( 'cart_total', true ),
				'order_status' => array( 'order_status', true ),
				'time'         => array( 'time', true ),
			);
			return $sortable_columns;
		}

		/**
		 * Get_columns.
		 *
		 * @version 6.0.1
		 */
		public function get_columns() {
			$columns = array(
				'cb'           => '<input type="checkbox" />',
				'email'        => __( 'Email', 'woocommerce-jetpack' ),
				'cart_total'   => __( 'Cart Total Amount', 'woocommerce-jetpack' ),
				'order_status' => __( 'Order Status', 'woocommerce-jetpack' ),
				'time'         => __( 'Date', 'woocommerce-jetpack' ),
			);
			return $columns;
		}

		/**
		 * Usort_reorder.
		 *
		 * @version 6.0.1
		 * @param array $a contains the old value for sorting.
		 * @param array $b contains the new value for shorting.
		 */
		public function usort_reorder( $a, $b ) {
			// If no sort, default to title.
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			$orderby = ( $wpnonce && ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'time';
			$order   = ( $wpnonce && ! empty( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';
			$result  = strcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}

		/**
		 * Column_email.
		 *
		 * @version 6.0.1
		 * @param array $item contains the all data of the item.
		 */
		public function column_email( $item ) {
			$item_details = unserialize( $item['checkout_data'] ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

			$wpnonce     = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			$paged       = $wpnonce && isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 0;
			$orderby     = $wpnonce && isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
			$order       = $wpnonce && isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';
			$search_term = $wpnonce && isset( $_GET['search_term'] ) ? sanitize_text_field( wp_unslash( $_GET['search_term'] ) ) : '';

			$page = $wpnonce && isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'wcj-tools';
			$tab  = $wpnonce && isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'cart_abandonment';

			$view_url = add_query_arg(
				array(
					'page'        => $page,
					'tab'         => $tab,
					'view_detail' => 'view_detail',
					'session_id'  => sanitize_text_field( $item['session_id'] ),
				),
				admin_url( '/admin.php' )
			);

			$actions = array(
				'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), __( 'View', 'woocommerce-jetpack' ) ),
				'delete' => sprintf( '<a onclick="return confirm(\'Are you sure to delete this order?\');" href="?page=%s&tab=cart_abandonment&action=delete&id=%s">%s</a>', esc_html( $page ), esc_html( $item['id'] ), __( 'Delete', 'woocommerce-jetpack' ) ),
			);

			if ( 'abandoned' === $item['order_status'] && ! $item['is_subscribe'] ) {
				$actions['unsubscribe'] = sprintf( '<a onclick="return confirm(\'Are you sure to unsubscribe this user? \');" href="?page=%s&action=unsubscribe&id=%s">%s</a>', esc_html( $page ), esc_html( $item['id'] ), __( 'Unsubscribe', 'woocommerce-jetpack' ) );

			}

			return sprintf(
				'<a href="%s"><span class="dashicons dashicons-admin-users"></span> %s %s </a>',
				esc_url( $view_url ),
				esc_html( $item['email'] ),
				$this->row_actions( $actions )
			);
		}

		/**
		 * Get_bulk_actions.
		 *
		 * @version 6.0.1
		 */
		public function get_bulk_actions() {
			$actions = array(
				'delete' => 'Delete',
			);
			return $actions;
		}

		/**
		 * Column_cb.
		 *
		 * @version 6.0.1
		 * @param array $item contains the all data of the item.
		 */
		public function column_cb( $item ) {
			return sprintf( '<input type="checkbox" name="wcj_ca_data[]" value="%s" />', $item['id'] );
		}

		/**
		 * Prepare_items.
		 *
		 * @version 6.0.1
		 * @param string $cart_type optional | type of the cart.
		 * @param string $search optional | search string.
		 * @param string $from_date optional | from date for filter data by start date.
		 * @param string $to_date optional | to date for filter data by end date.
		 */
		public function prepare_items( $cart_type = 'normal', $search = '', $from_date = '', $to_date = '' ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$cart_abandonment_table_name = $wpdb->prefix . 'wcj_cart_abandonment_data';

			$per_page = 10;

			$columns  = $this->get_columns();
			$hidden   = array();
			$sortable = $this->get_sortable_columns();

			$this->_column_headers = array( $columns, $hidden, $sortable );

			$this->process_bulk_action();

			$wpnonce     = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			$paged       = $wpnonce && isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 0;
			$orderby     = $wpnonce && isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
			$order       = $wpnonce && isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';
			$search_term = $wpnonce && isset( $_GET['search_term'] ) ? sanitize_text_field( wp_unslash( $_GET['search_term'] ) ) : '';

			$orderby = strtolower( str_replace( ' ', '_', $orderby ) );

			$paged   = $paged ? max( 0, $paged - 1 ) : 0;
			$orderby = ( $orderby && in_array( $orderby, array_keys( $this->get_sortable_columns() ), true ) ) ? $orderby : 'id';
			$order   = ( $order && in_array( $order, array( 'asc', 'desc' ), true ) ) ? $order : 'desc';

			$this->items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$cart_abandonment_table_name} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
					$per_page,
					$paged * $per_page
				),
				ARRAY_A
			);

			$total_items = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $cart_abandonment_table_name WHERE `order_status` = %s", $cart_type ) );
			// phpcs:enable

			// [REQUIRED] configure pagination.
			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total_items / $per_page ),
				)
			);
		}
	}

endif;

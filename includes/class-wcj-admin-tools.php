<?php
/**
 * Booster for WooCommerce - Module - Admin Tools
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Admin_Tools' ) ) :

class WCJ_Admin_Tools extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'admin_tools';
		$this->short_desc = __( 'Admin Tools', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster for WooCommerce debug and log tools.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-booster-admin-tools';
		parent::__construct();

		$this->add_tools( array(
			'admin_tools' => array(
				'title'     => __( 'Admin Tools', 'woocommerce-jetpack' ),
				'desc'      => __( 'Log.', 'woocommerce-jetpack' ),
				'tab_title' => __( 'Log', 'woocommerce-jetpack' ),
			),
		) );

		$this->current_php_memory_limit = '';
		$this->current_php_time_limit   = '';
		if ( $this->is_enabled() ) {
			// PHP Memory Limit
			if ( 0 != ( $php_memory_limit = get_option( 'wcj_admin_tools_php_memory_limit', 0 ) ) ) {
				ini_set( 'memory_limit', $php_memory_limit . 'M' );
			}
			$this->current_php_memory_limit = sprintf( ' ' . __( 'Current PHP memory limit: %s.', 'woocommerce-jetpack' ), ini_get( 'memory_limit' ) );
			// PHP Time Limit
			if ( 0 != ( $php_time_limit = get_option( 'wcj_admin_tools_php_time_limit', 0 ) ) ) {
				set_time_limit( $php_time_limit );
			}
			$this->current_php_time_limit = sprintf( ' ' . __( 'Current PHP time limit: %s seconds.', 'woocommerce-jetpack' ), ini_get( 'max_execution_time' ) );
			// Order Meta
			if ( 'yes' === get_option( 'wcj_admin_tools_show_order_meta_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes', array( $this, 'add_order_meta_meta_box' ) );
			}
			// Product Meta
			if ( 'yes' === get_option( 'wcj_admin_tools_show_product_meta_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes', array( $this, 'add_product_meta_meta_box' ) );
			}
		}
	}

	/**
	 * add_product_meta_meta_box.
	 *
	 * @version 2.5.8
	 * @since   2.5.8
	 */
	function add_product_meta_meta_box() {
		add_meta_box(
			'wcj-admin-tools-product-meta',
			__( 'Product Meta', 'woocommerce-jetpack' ),
			array( $this, 'create_meta_meta_box' ),
			'product',
			'normal',
			'low'
		);
	}

	/**
	 * add_order_meta_meta_box.
	 *
	 * @version 2.5.8
	 * @since   2.5.8
	 */
	function add_order_meta_meta_box() {
		add_meta_box(
			'wcj-admin-tools-order-meta',
			__( 'Order Meta', 'woocommerce-jetpack' ),
			array( $this, 'create_meta_meta_box' ),
			'shop_order',
			'normal',
			'low'
		);
	}

	/**
	 * create_meta_meta_box.
	 *
	 * @version 2.7.0
	 * @since   2.5.8
	 */
	function create_meta_meta_box( $post ) {
		$html    = '';
		$post_id = get_the_ID();
		// Meta
		$meta = get_post_meta( $post_id );
		$table_data = array();
		foreach ( $meta as $meta_key => $meta_values ) {
			$table_data[] = array( $meta_key, implode( ', ', $meta_values ) );
		}
		$html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical' ) );
		// Items Meta (for orders only)
		if ( 'shop_order' === $post->post_type ) {
			$_order = wc_get_order( $post_id );
			$table_data = array();
			foreach ( $_order->get_items() as $item_key => $item ) {
				foreach ( $item['item_meta'] as $item_meta_key => $item_meta_value ) {
					if ( is_array( $item_meta_value ) ) {
						$item_meta_value = implode( ', ', $item_meta_value );
					}
					$table_data[] = array( $item_key, $item_meta_key, $item_meta_value );
				}
			}
			$html .= '<h3>' . __( 'Order Items Meta', 'woocommerce-jetpack' ) . '</h3>';
			$html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical' ) );
		}
		// Output
		echo $html;
	}

	/**
	 * create_admin_tools_tool.
	 *
	 * @version 2.9.0
	 */
	function create_admin_tools_tool() {
		// Delete log
		if ( isset( $_GET['wcj_delete_log'] ) && wcj_is_user_role( 'administrator' ) ) {
			update_option( 'wcj_log', '' );
			if ( wp_safe_redirect( remove_query_arg( 'wcj_delete_log' ) ) ) {
				exit;
			}
		}
		// Header
		$the_tools = '';
		$the_tools .= $this->get_tool_header_html( 'admin_tools' );
		$the_tools .= '<p><a href="' . add_query_arg( 'wcj_delete_log', '1' ) . '">' . __( 'Delete Log', 'woocommerce-jetpack' ) . '</a></p>';
		// Log
		$the_log = '';
		$the_log .= '<p style="font-style:italic;color:gray;">' . sprintf( __( 'Now: %s', 'woocommerce-jetpack' ), date( 'Y-m-d H:i:s' ) ) . '</p>';
		$the_log .= '<pre>' . get_option( 'wcj_log', '' ) . '</pre>';
		// Final output
		$html = '';
		$html .= '<div class="wrap">';
		$html .= '<p>' . $the_tools  . '</p>';
		$html .= '<p>' . $the_log    . '</p>';
		$html .= '</div>';
		echo $html;
	}

	/**
	 * get_system_info_table_array.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 * @todo    (maybe) 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE'
	 */
	function get_system_info_table_array() {
		$system_info = array();
		$constants_array = array(
			'WP_MEMORY_LIMIT',
			'WP_MAX_MEMORY_LIMIT',
			'WP_DEBUG',
			'ABSPATH',
			'DISABLE_WP_CRON',
			'WP_CRON_LOCK_TIMEOUT',
			'WCJ_WC_VERSION',
		);
		foreach ( $constants_array as $the_constant ) {
			$system_info[] = array( $the_constant, ( defined( $the_constant ) ? constant( $the_constant ) : __( 'NOT DEFINED', 'woocommerce-jetpack' ) ) );
		}
		return $system_info;
	}

}

endif;

return new WCJ_Admin_Tools();

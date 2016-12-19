<?php
/**
 * WooCommerce Jetpack Orders
 *
 * The WooCommerce Jetpack Orders class.
 *
 * @version 2.5.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Orders' ) ) :

class WCJ_Orders extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.7
	 */
	public function __construct() {

		$this->id         = 'orders';
		$this->short_desc = __( 'Orders', 'woocommerce-jetpack' );
		$this->desc       = __( 'Orders auto-complete. Custom admin order list columns. Admin order currency. Admin order list multiple status filtering.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-orders/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {

			// Order auto complete
			if ( 'yes' === get_option( 'wcj_order_auto_complete_enabled' ) ) {
				add_action( 'woocommerce_thankyou', array( $this, 'auto_complete_order' ) );
			}

			// Custom columns
			add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_column' ),     PHP_INT_MAX - 1 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), PHP_INT_MAX );
			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
				// Country filtering
				add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
				add_filter( 'parse_query',           array( $this, 'orders_by_country_admin_filter_query' ) );
			}

			// Order currency
			if ( 'yes' === get_option( 'wcj_order_admin_currency', 'no' ) ) {
				$this->meta_box_screen = 'shop_order';
				add_action( 'add_meta_boxes',       array( $this, 'add_meta_box' ) );
				add_action( 'save_post_shop_order', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				if ( 'filter' === get_option( 'wcj_order_admin_currency_method', 'filter' ) ) {
					add_filter( 'woocommerce_get_order_currency', array( $this, 'change_order_currency' ), PHP_INT_MAX, 2 );
				}
			}

			// Multiple status
			if ( 'yes' === get_option( 'wcj_order_admin_list_multiple_status_not_completed_link', 'no' ) ) {
				add_filter( 'views_edit-shop_order', array( $this, 'add_shop_order_multiple_statuses_not_completed_link' ) );
				add_action( 'pre_get_posts',         array( $this, 'filter_shop_order_multiple_statuses_not_completed_link' ), PHP_INT_MAX, 1 );
			}
			if ( 'no' != get_option( 'wcj_order_admin_list_multiple_status_filter', 'no' ) ) {
				add_action( 'restrict_manage_posts', array( $this, 'add_shop_order_multiple_statuses' ), PHP_INT_MAX, 2 );
				add_action( 'pre_get_posts',         array( $this, 'filter_shop_order_multiple_statuses' ), PHP_INT_MAX, 1 );
			}
			if ( 'yes' === get_option( 'wcj_order_admin_list_hide_default_statuses_menu', 'no' ) ) {
				add_action( 'admin_head', array( $this, 'hide_default_statuses_menu' ), PHP_INT_MAX );
			}

			// Columns Order
			if ( 'yes' === get_option( 'wcj_order_admin_list_columns_order_enabled', 'no' ) ) {
				add_filter( 'manage_edit-shop_order_columns', array( $this, 'rearange_order_columns' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * hide_default_statuses_menu.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function hide_default_statuses_menu() {
		echo '<style>body.post-type-shop_order ul.subsubsub {display: none !important;}</style>';
	}

	/**
	 * get_orders_default_columns_in_order.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_orders_default_columns_in_order() {
		$columns = array(
			'cb',
			'order_status',
			'order_title',
			'order_items',
			'billing_address',
			'shipping_address',
			'customer_message',
			'order_notes',
			'order_date',
			'order_total',
			'order_actions',
		);
		return implode( PHP_EOL, $columns );
	}

	/**
	 * add_shop_order_multiple_statuses_not_completed_link.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_shop_order_multiple_statuses_not_completed_link( $views ) {
		global $wp_query;
		if ( ! current_user_can( 'edit_others_pages' ) ) {
			return $views;
		}
		$all_not_completed_statuses          = wc_get_order_statuses();
		unset( $all_not_completed_statuses['wc-completed'] );
		$all_not_completed_statuses          = array_keys( $all_not_completed_statuses );
		$all_not_completed_statuses_param    = urlencode( implode( ',', $all_not_completed_statuses ) );
		$class                               = ( isset( $wp_query->query['post_status'] ) && is_array( $wp_query->query['post_status'] ) && $all_not_completed_statuses === $wp_query->query['post_status'] ) ? 'current' : '';
		$query_string                        = remove_query_arg( array( 'post_status', 'wcj_admin_filter_statuses' ) );
		$query_string                        = add_query_arg( 'post_status', $all_not_completed_statuses_param, $query_string );
		$views['wcj_statuses_not_completed'] = '<a href="' . esc_url( $query_string ) . '" class="' . esc_attr( $class ) . '">' . __( 'Not Completed', 'woocommerce-jetpack' ) . '</a>';
		return $views;
	}

	/**
	 * filter_shop_order_multiple_statuses_not_completed_link.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function filter_shop_order_multiple_statuses_not_completed_link( $query ) {
		if ( false !== strpos( $_SERVER['REQUEST_URI'], '/wp-admin/edit.php' ) && isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] ) {
			if ( current_user_can( 'edit_others_pages' ) ) {
				if ( isset( $_GET['post_status'] ) && false !== strpos( $_GET['post_status'], ',' ) ) {
					$post_statuses = explode( ',', $_GET['post_status'] );
//					$query->set( 'post_status', $post_statuses );
					$query->query['post_status']      = $post_statuses;
					$query->query_vars['post_status'] = $post_statuses;
				}
			}
		}
	}

	/**
	 * multiple_shop_order_statuses.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function multiple_shop_order_statuses( $type ) {
		$checked_post_statuses = isset( $_GET['wcj_admin_filter_statuses'] ) ? $_GET['wcj_admin_filter_statuses'] : array();
		$html = '';
		$html .= ( 'checkboxes' === $type ) ?
			'<span id="wcj_admin_filter_shop_order_statuses">' :
			'<select multiple name="wcj_admin_filter_statuses[]" id="wcj_admin_filter_shop_order_statuses">';
		$num_posts = wp_count_posts( 'shop_order', 'readable' );
		foreach ( wc_get_order_statuses() as $status_id => $status_title ) {
			$total_number = ( isset( $num_posts->{$status_id} ) ) ? $num_posts->{$status_id} : 0;
			if ( $total_number > 0 ) {
				$html .= ( 'checkboxes' === $type ) ?
					'<input type="checkbox" name="wcj_admin_filter_statuses[]" value="' . $status_id . '"' . checked( in_array( $status_id, $checked_post_statuses ), true, false ) . '>' . $status_title . ' (' . $total_number . ') ' :
					'<option value="' . $status_id . '"' . selected( in_array( $status_id, $checked_post_statuses ), true, false ) . '>' . $status_title . ' (' . $total_number . ') ' . '</option>';
			}
		}
		$html .= ( 'checkboxes' === $type ) ?
			'</span>' :
			'</select>';
		return $html;
	}

	/**
	 * add_shop_order_multiple_statuses.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_shop_order_multiple_statuses( $post_type, $which ) {
		if ( 'shop_order' === $post_type ) {
			echo $this->multiple_shop_order_statuses( get_option( 'wcj_order_admin_list_multiple_status_filter', 'no' ) );
		}
	}

	/**
	 * filter_shop_order_multiple_statuses.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function filter_shop_order_multiple_statuses( $query ) {
		if ( false !== strpos( $_SERVER['REQUEST_URI'], '/wp-admin/edit.php' ) && isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] ) {
			if ( current_user_can( 'edit_others_pages' ) ) {
				if ( isset( $_GET['wcj_admin_filter_statuses'] ) ) {
					$post_statuses = $_GET['wcj_admin_filter_statuses'];
//					$query->set( 'post_status', $post_statuses );
					$query->query['post_status']      = $post_statuses;
					$query->query_vars['post_status'] = $post_statuses;
				}
			}
		}
	}

	/**
	 * change_order_currency.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function change_order_currency( $order_currency, $_order ) {
		return ( '' != ( $wcj_order_currency = get_post_meta( $_order->id, '_' . 'wcj_order_currency', true ) ) ) ? $wcj_order_currency : $order_currency;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function get_meta_box_options() {
		$order_id = get_the_ID();
		$_order = wc_get_order( $order_id );
		$options = array(
			array(
				'name'       => ( 'filter' === get_option( 'wcj_order_admin_currency_method', 'filter' ) ? 'wcj_order_currency' : 'order_currency' ),
				'default'    => $_order->get_order_currency(),
				'type'       => 'select',
				'options'    => wcj_get_currencies_names_and_symbols( 'names' ),
				'title'      => __( 'Order Currency', 'woocommerce-jetpack' ),
				'tooltip'    => __( 'Save order after you change this field.', 'woocommerce-jetpack' ),
			),
		);
		return $options;
	}

	/**
	 * Filter the orders in admin based on options
	 *
	 * @access public
	 * @param mixed $query
	 * @return void
	 */
	function orders_by_country_admin_filter_query( $query ) {
		global $typenow, $wp_query;
		if ( $typenow == 'shop_order' && isset( $_GET['country'] ) && 'all' != $_GET['country'] ) {
			$query->query_vars['meta_value'] = $_GET['country'];//'FR';
			$query->query_vars['meta_key']   = '_billing_country';
		}
	}

	/**
	 * Filters for post types
	 */
	public function restrict_manage_posts() {
		global $typenow, $wp_query;
		if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ) ) ) {
			$selected_coutry = isset( $_GET['country'] ) ? $_GET['country'] : 'all';
			$countries = array_merge( array( 'all' => __( 'All countries', 'woocommerce-jetpack' ) ), wcj_get_countries() );
			echo '<select id="country" name="country">';
			foreach ( $countries as $code => $name ) {
				echo '<option value="' . $code . '" ' . selected( $code, $selected_coutry, false ) . '>' . $name . '</option>';
			}
			echo '</select>';
		}
	}

	/**
	 * rearange_order_columns.
	 *
	 * @version 2.5.7
	 * @version 2.5.7
	 */
	function rearange_order_columns( $columns ) {
		$reordered_columns = get_option( 'wcj_order_admin_list_columns_order', $this->get_orders_default_columns_in_order() );
		$reordered_columns = explode( PHP_EOL, $reordered_columns );
		$reordered_columns_result = array();
		if ( ! empty( $reordered_columns ) ) {
			foreach ( $reordered_columns as $column_id ) {
				$column_id = str_replace( "\n", '', $column_id );
				$column_id = str_replace( "\r", '', $column_id );
				if ( '' != $column_id && isset( $columns[ $column_id ] ) ) {
					$reordered_columns_result[ $column_id ] = $columns[ $column_id ];
					unset( $columns[ $column_id ] );
				}
			}
		}
		return array_merge( $reordered_columns_result, $columns );
	}

	/**
	 * add_order_column.
	 *
	 * @version 2.5.3
	 */
	function add_order_column( $columns ) {
		if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
			$columns['country'] = __( 'Country', 'woocommerce-jetpack' );
		}
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
				$columns[ 'wcj_orders_custom_column_' . $i ] = get_option( 'wcj_orders_list_custom_columns_label_' . $i, '' );
			}
		}
		return $columns;
	}

	/**
	 * wcj_get_country_flag_by_code.
	 */
	public function wcj_get_country_flag_by_code( $country_code ) {
		$img_src = plugins_url() . '/' . 'woocommerce-jetpack' . '/assets/images/flag-icons/' . strtolower( $country_code ) . '.png';
		return '<img src="' . $img_src . '" title="' . wcj_get_country_name_by_code( $country_code ) . '">';
	}

	/**
	 * Output custom columns for orders
	 *
	 * @version 2.5.3
	 * @param   string $column
	 */
	public function render_order_columns( $column ) {
		if ( 'country' === $column && 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
			$order = wc_get_order( get_the_ID() );
//			$country_code = wcj_get_customer_country( $order->customer_user );
			$country_code = $order->billing_country;
			echo ( 2 == strlen( $country_code ) )
				? $this->wcj_get_country_flag_by_code( $country_code ) . ' ' . wcj_get_country_name_by_code( $country_code )
				: wcj_get_country_name_by_code( $country_code );
		}
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
				if ( 'wcj_orders_custom_column_' . $i === $column ) {
					echo do_shortcode( get_option( 'wcj_orders_list_custom_columns_value_' . $i, '' ) );
				}
			}
		}
	}

	/**
	* Auto Complete all WooCommerce orders.
	*/
	public function auto_complete_order( $order_id ) {
		global $woocommerce;
		if ( !$order_id ) {
			return;
		}
		$order = new WC_Order( $order_id );
		$order->update_status( 'completed' );
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_settings_hook() {
		add_filter( 'wcj_orders_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.3
	 */
	function get_settings() {
		$settings = apply_filters( 'wcj_orders_settings', array() );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.7
	 * @since   2.5.3
	 */
	function add_settings() {
		$settings = array(
			array(
				'title'    => __( 'Admin Order Currency', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_order_admin_currency_options',
			),
			array(
				'title'    => __( 'Admin Order Currency', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'When enabled this will add "Booster: Orders" metabox to each order\'s edit page.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_admin_currency',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Admin Order Currency Method', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Choose if you want changed order currency to be saved directly to DB, or if you want to use filter. When using <em>filter</em> method, changes will be active only when "Admin Order Currency" section is enabled. When using <em>directly to DB</em> method, changes will be permanent, that is even if Booster plugin is removed.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_admin_currency_method',
				'default'  => 'filter',
				'type'     => 'select',
				'options'  => array(
					'filter' => __( 'Filter', 'woocommerce-jetpack' ),
					'db'     => __( 'Directly to DB', 'woocommerce-jetpack' ),
				),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_order_admin_currency_options',
			),
			array(
				'title'    => __( 'Orders Auto-Complete', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you enable orders auto-complete function.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_auto_complete_options',
			),
			array(
				'title'    => __( 'Auto-complete all WooCommerce orders', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'E.g. if you sell digital products then you are not shipping anything and you may want auto-complete all your orders.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_auto_complete_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_order_auto_complete_options',
			),
			array(
				'title'    => __( 'Admin Orders List Custom Columns', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you add custom columns to WooCommerce orders list.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_list_custom_columns_options',
			),
			array(
				'title'    => __( 'Country', 'woocommerce-jetpack' ),
				'desc'     => __( 'Add', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_list_custom_columns_country',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Custom Columns Total Number', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_list_custom_columns_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '0', )
				),
			),
		);
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Custom Column', 'woocommerce-jetpack' ) . ' #' . $i,
					'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
					'id'       => 'wcj_orders_list_custom_columns_enabled_' . $i,
					'default'  => 'no',
					'type'     => 'checkbox',
				),
				array(
					'desc'     => __( 'Label', 'woocommerce-jetpack' ),
					'id'       => 'wcj_orders_list_custom_columns_label_' . $i,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Value', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_orders_list_custom_columns_value_' . $i,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:300px;',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_orders_list_custom_columns_options',
			),
			array(
				'title'    => __( 'Admin Orders List Multiple Status', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_order_admin_list_multiple_status_options',
			),
			array(
				'title'    => __( 'Multiple Status Filtering', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_admin_list_multiple_status_filter',
				'default'  => 'no',
				'type'     => 'select',
				'options'  => array(
					'no'              => __( 'Do not add', 'woocommerce-jetpack' ),
					'multiple_select' => __( 'Add as multiple select', 'woocommerce-jetpack' ),
					'checkboxes'      => __( 'Add as checkboxes', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Hide Default Statuses Menu', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_admin_list_hide_default_statuses_menu',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Add "Not Completed" Status Link to Default Statuses Menu', 'woocommerce-jetpack' ),
				'desc'     => __( 'Add', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_admin_list_multiple_status_not_completed_link',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_order_admin_list_multiple_status_options',
			),
			array(
				'title'    => __( 'Admin Orders List Columns Order', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_order_admin_list_columns_order_options',
			),
			array(
				'title'    => __( 'Columns Order', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_admin_list_columns_order_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'id'       => 'wcj_order_admin_list_columns_order',
				'desc_tip' => __( 'Default columns order', 'woocommerce-jetpack' ) . ':<br>' . str_replace( PHP_EOL, '<br>', $this->get_orders_default_columns_in_order() ),
				'default'  => $this->get_orders_default_columns_in_order(),
				'type'     => 'textarea',
				'css'      => 'height:300px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_order_admin_list_columns_order_options',
			),
		) );
		return $settings;
	}
}

endif;

return new WCJ_Orders();

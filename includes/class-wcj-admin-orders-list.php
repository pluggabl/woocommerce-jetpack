<?php
/**
 * Booster for WooCommerce - Module - Admin Orders List
 *
 * @version 7.1.6
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Admin_Orders_List' ) ) :

	/**
	 * WCJ_Admin_Orders_List.
	 *
	 * @version 2.7.0
	 */
	class WCJ_Admin_Orders_List extends WCJ_Module {


		/**
		 * Constructor.
		 *
		 * @version 7.1.4
		 * @since   3.2.4
		 */
		public function __construct() {
			$this->id         = 'admin_orders_list';
			$this->short_desc = __( 'Admin Orders List', 'woocommerce-jetpack' );
			$this->desc       = __( 'Customize admin orders list: add custom columns (1 allowed in free version); add multiple status filtering (1 allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Customize admin orders list: add custom columns; add multiple status filtering.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-admin-orders-list';
			parent::__construct();

			if ( $this->is_enabled() ) {

				// Custom columns.
				if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_custom_columns_enabled', 'no' ) ) {

					if ( true === wcj_is_hpos_enabled() ) {
						add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_order_columns' ), PHP_INT_MAX - 1 );
						add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_order_column_hpos' ), PHP_INT_MAX, 2 );
					} else {
						add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_columns' ), PHP_INT_MAX - 1 );
						add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_column' ), PHP_INT_MAX );

					}
					if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_country', 'no' ) || 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
						// Billing country or Currency filtering.
						add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
						add_filter( 'parse_query', array( $this, 'parse_query' ) );
					}
					// Maybe make sortable custom columns.
					add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'shop_order_sortable_columns' ) );
					add_action( 'pre_get_posts', array( $this, 'shop_order_pre_get_posts_order_by_column' ) );
				}

				// Multiple status.
				if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_multiple_status_enabled', 'no' ) ) {
					if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_multiple_status_not_completed_link', 'no' ) ) {
						add_filter( 'views_edit-shop_order', array( $this, 'add_shop_order_multiple_statuses_not_completed_link' ) );
						add_action( 'pre_get_posts', array( $this, 'filter_shop_order_multiple_statuses_not_completed_link' ), PHP_INT_MAX, 1 );
					}
					if ( 'no' !== wcj_get_option( 'wcj_order_admin_list_multiple_status_filter', 'no' ) ) {

						if ( true === wcj_is_hpos_enabled() ) {
							add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'add_shop_order_multiple_statuses_hpos' ), PHP_INT_MAX, 2 );
						} else {
							add_action( 'restrict_manage_posts', array( $this, 'add_shop_order_multiple_statuses' ), PHP_INT_MAX, 2 );
						}
						if ( true === wcj_is_hpos_enabled() ) {
							add_filter( 'woocommerce_shop_order_list_table_default_statuses', array( $this, 'filter_shop_order_multiple_statuses_hpos' ), PHP_INT_MAX, 1 );
						} else {
							add_action( 'pre_get_posts', array( $this, 'filter_shop_order_multiple_statuses' ), PHP_INT_MAX, 1 );
						}
					}
					if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_hide_default_statuses_menu', 'no' ) ) {
						add_action( 'admin_head', array( $this, 'hide_default_statuses_menu' ), PHP_INT_MAX );
					}
					if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_multiple_status_admin_menu', 'no' ) ) {
						if ( true === wcj_is_hpos_enabled() ) {
							add_action( 'admin_menu', array( $this, 'admin_menu_multiple_status_hpos' ) );
						} else {
							add_action( 'admin_menu', array( $this, 'admin_menu_multiple_status' ) );

						}
					}
				}

				// Columns Order.
				if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_columns_order_enabled', 'no' ) ) {
					if ( true === wcj_is_hpos_enabled() ) {
						add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'rearange_order_columns' ), PHP_INT_MAX - 1 );
					} else {
						add_filter( 'manage_edit-shop_order_columns', array( $this, 'rearange_order_columns' ), PHP_INT_MAX - 1 );
					}
				}
			}
		}

		/**
		 * Admin_menu_multiple_status.
		 *
		 * @version 7.1.6
		 * @since   3.7.0
		 * @todo    add presets as links (same as "Not completed" link)
		 * @todo    fix: custom (i.e. presets) menus are not highlighted
		 */
		public function admin_menu_multiple_status() {
			// Remove "Coupons" menu (to get "Orders" menus on top).
			$coupons_menu = remove_submenu_page( 'woocommerce', 'edit.php?post_type=shop_coupon' );
			// Maybe remove original "Orders" menu.
			if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_multiple_status_admin_menu_remove_original', 'no' ) ) {
				remove_submenu_page( 'woocommerce', 'edit.php?post_type=shop_order' );
			}
			$nonce = wp_create_nonce( 'wcj_admin_filter_statuses_nonce' );

			// Add presets.
			$titles       = wcj_get_option( 'wcj_order_admin_list_multiple_status_presets_titles', array() );
			$statuses     = wcj_get_option( 'wcj_order_admin_list_multiple_status_presets_statuses', array() );
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_order_admin_list_multiple_status_presets_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( ! empty( $titles[ $i ] ) && ! empty( $statuses[ $i ] ) ) {
					$menu_slug = 'edit.php?post_type=shop_order';
					foreach ( $statuses[ $i ] as $x => $status ) {
						$menu_slug .= "&wcj_admin_filter_statuses_nonce=$nonce&wcj_admin_filter_statuses[{$x}]={$status}";
					}
					$orders_count_html = '';
					if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_multiple_status_admin_menu_counter', 'no' ) ) {
						$order_count = 0;
						foreach ( $statuses[ $i ] as $x => $status ) {
							$order_count += wc_orders_count( substr( $status, 3 ) );
						}
						$orders_count_html = ' <span class="awaiting-mod update-plugins count-' . esc_attr( $order_count ) . ' wcj-order-count-wrapper"><span class="wcj-order-count">' . number_format_i18n( $order_count ) . '</span></span>';
					}
					add_submenu_page( 'woocommerce', $titles[ $i ], $titles[ $i ] . $orders_count_html, 'edit_shop_orders', $menu_slug );
				}
			}
			if ( isset( $coupons_menu ) && is_array( $coupons_menu ) ) {
				// Re-add "Coupons" menu.
				add_submenu_page( 'woocommerce', $coupons_menu[0], $coupons_menu[3], $coupons_menu[1], $coupons_menu[2] );
			}
		}



		/**
		 * Admin_menu_multiple_status_hpos.
		 *
		 * @version 7.1.6
		 * @since  1.0.0
		 * @todo    add presets as links (same as "Not completed" link)
		 * @todo    fix: custom (i.e. presets) menus are not highlighted
		 */
		public function admin_menu_multiple_status_hpos() {
			// Remove "Coupons" menu (to get "Orders" menus on top).
			$coupons_menu = remove_submenu_page( 'woocommerce', 'edit.php?post_type=shop_coupon' );
			// Maybe remove original "Orders" menu.
			if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_multiple_status_admin_menu_remove_original', 'no' ) ) {
				remove_submenu_page( 'woocommerce', 'wc-orders' );
			}
			$nonce = wp_create_nonce( 'wcj_admin_filter_statuses_nonce' );
			// Add presets.
			$titles       = wcj_get_option( 'wcj_order_admin_list_multiple_status_presets_titles', array() );
			$statuses     = wcj_get_option( 'wcj_order_admin_list_multiple_status_presets_statuses', array() );
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_order_admin_list_multiple_status_presets_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( ! empty( $titles[ $i ] ) && ! empty( $statuses[ $i ] ) ) {
					$menu_slug = '?page=wc-orders';
					foreach ( $statuses[ $i ] as $x => $status ) {
						$menu_slug .= "&wcj_admin_filter_statuses_nonce=$nonce&wcj_admin_filter_statuses[{$x}]={$status}";
					}
					$orders_count_html = '';
					if ( 'yes' === wcj_get_option( 'wcj_order_admin_list_multiple_status_admin_menu_counter', 'no' ) ) {
						$order_count = 0;
						foreach ( $statuses[ $i ] as $x => $status ) {
							$order_count += wc_orders_count( substr( $status, 3 ) );
						}
						$orders_count_html = ' <span class="awaiting-mod update-plugins count-' . esc_attr( $order_count ) . ' wcj-order-count-wrapper"><span class="wcj-order-count">' . number_format_i18n( $order_count ) . '</span></span>';
					}
					add_submenu_page( 'woocommerce', $titles[ $i ], $titles[ $i ] . $orders_count_html, 'edit_shop_orders', $menu_slug );
				}
			}
			if ( isset( $coupons_menu ) && is_array( $coupons_menu ) ) {
				// Re-add "Coupons" menu.
				add_submenu_page( 'woocommerce', $coupons_menu[0], $coupons_menu[3], $coupons_menu[1], $coupons_menu[2] );
			}
		}

		/**
		 * Shop_order_pre_get_posts_order_by_column.
		 *
		 * @version
		 * @since   2.9.0
		 * @todo    add sortable to "Billing Country" and "Currency Code"
		 * @todo    move custom columns section (probably with reordering and multiple status sections) to new module (e.g. (Admin) Order(s) List) - same with products custom columns
		 * @todo    (maybe) add filtering to custom columns (as it's done for "Billing Country" and "Currency Code")
		 * @param string $query defines the query.
		 */
		public function shop_order_pre_get_posts_order_by_column( $query ) {
			$orderby = $query->get( 'orderby' );
			if (
				$query->is_main_query() &&
				( $orderby ) &&
				isset( $query->query['post_type'] ) && 'shop_order' === $query->query['post_type'] &&
				isset( $query->is_admin ) && 1 === (int) $query->is_admin
			) {
				if ( 'wcj_orders_custom_column_' === substr( $orderby, 0, 25 ) ) {
					$index = substr( $orderby, 25 );
					$query->set( 'orderby', wcj_get_option( 'wcj_orders_list_custom_columns_sortable_' . $index, 'no' ) );
					$query->set( 'meta_key', wcj_get_option( 'wcj_orders_list_custom_columns_sortable_key_' . $index, '' ) );
				}
			}
		}

		/**
		 * Make columns sortable.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @param   array $columns defines the columns.
		 * @return  array
		 */
		public function shop_order_sortable_columns( $columns ) {
			$custom       = array();
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
					if ( 'no' !== wcj_get_option( 'wcj_orders_list_custom_columns_sortable_' . $i, 'no' ) && '' !== wcj_get_option( 'wcj_orders_list_custom_columns_sortable_key_' . $i, '' ) ) {
						$custom[ 'wcj_orders_custom_column_' . $i ] = 'wcj_orders_custom_column_' . $i;
					}
				}
			}
			return ( ! empty( $custom ) ? wp_parse_args( $custom, $columns ) : $columns );
		}

		/**
		 * Hide_default_statuses_menu.
		 *
		 * @version 7.1.4
		 * @since   2.5.7
		 */
		public function hide_default_statuses_menu() {
			if ( true === wcj_is_hpos_enabled() ) {
				echo '<style> #wpbody-content ul.subsubsub {display: none !important;}</style>';
			} else {
				echo '<style>body.post-type-shop_order ul.subsubsub {display: none !important;}</style>';
			}

		}

		/**
		 * Get_orders_default_columns_in_order.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 */
		public function get_orders_default_columns_in_order() {
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
		 * Add_shop_order_multiple_statuses_not_completed_link.
		 *
		 * @version 5.6.7
		 * @since   2.5.7
		 * @param   string $views defines the views.
		 */
		public function add_shop_order_multiple_statuses_not_completed_link( $views ) {
			global $wp_query;
			if ( ! wcj_current_user_can( 'edit_others_pages' ) ) {
				return $views;
			}
			$all_not_completed_statuses = wc_get_order_statuses();
			unset( $all_not_completed_statuses['wc-completed'] );
			$all_not_completed_statuses          = array_keys( $all_not_completed_statuses );
			$all_not_completed_statuses_param    = rawurlencode( implode( ',', $all_not_completed_statuses ) );
			$class                               = ( isset( $wp_query->query['post_status'] ) && is_array( $wp_query->query['post_status'] ) && $all_not_completed_statuses === $wp_query->query['post_status'] ) ? 'current' : '';
			$query_string                        = esc_url_raw( remove_query_arg( array( 'post_status', 'wcj_admin_filter_statuses', 'multiple_statuses_not_completed_link_nonce' ) ) );
			$query_string                        = esc_url_raw(
				add_query_arg(
					array(
						'post_status' => $all_not_completed_statuses_param,
						'multiple_statuses_not_completed_link_nonce' => wp_create_nonce( 'multiple_statuses_not_completed_link_nonce' ),
					),
					$query_string
				)
			);
			$views['wcj_statuses_not_completed'] = '<a href="' . esc_url_raw( $query_string ) . '" class="' . esc_attr( $class ) . '">' . __( 'Not Completed', 'woocommerce-jetpack' ) . '</a>';
			return $views;
		}

		/**
		 * Filter_shop_order_multiple_statuses_not_completed_link.
		 *
		 * @version 5.6.8
		 * @since   2.5.7
		 * @param string $query defines the query.
		 */
		public function filter_shop_order_multiple_statuses_not_completed_link( $query ) {
			if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-admin/edit.php' ) && isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] ) {
				if ( wcj_current_user_can( 'edit_others_pages' ) ) {
					$wpnonce = isset( $_REQUEST['multiple_statuses_not_completed_link_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['multiple_statuses_not_completed_link_nonce'] ), 'multiple_statuses_not_completed_link_nonce' ) : false;
					if ( $wpnonce && isset( $_GET['post_status'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_GET['post_status'] ) ), ',' ) ) {
						$post_statuses                    = explode( ',', sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) );
						$query->query['post_status']      = $post_statuses;
						$query->query_vars['post_status'] = $post_statuses;
					}
				}
			}
		}

		/**
		 * Multiple_shop_order_statuses.
		 *
		 * @version 7.1.4
		 * @since   2.5.7
		 * @param string $type defines the type.
		 */
		public function multiple_shop_order_statuses( $type ) {
			$wpnonce               = isset( $_REQUEST['wcj_admin_filter_statuses_nonce'] ) ? wp_verify_nonce( sanitize_key( isset( $_REQUEST['wcj_admin_filter_statuses_nonce'] ) ? $_REQUEST['wcj_admin_filter_statuses_nonce'] : '' ), 'wcj_admin_filter_statuses_nonce' ) : false;
			$checked_post_statuses = $wpnonce && isset( $_GET['wcj_admin_filter_statuses'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET['wcj_admin_filter_statuses'] ) ) : array();
			$html                  = '';
			$html                 .= ( 'checkboxes' === $type ) ?
				'<span id="wcj_admin_filter_shop_order_statuses">' :
				'<select multiple name="wcj_admin_filter_statuses[]" id="wcj_admin_filter_shop_order_statuses" class="chosen_select">';
			$num_posts             = wp_count_posts( 'shop_order', 'readable' );
			foreach ( array_merge( wc_get_order_statuses(), array( 'trash' => __( 'Trash', 'woocommerce-jetpack' ) ) ) as $status_id => $status_title ) {
				if ( true === wcj_is_hpos_enabled() ) {
					$total_orders = wc_orders_count( $status_id );
				} else {
					$total_orders = $num_posts->{$status_id};
				}
				$total_number = ( isset( $total_orders ) ) ? $total_orders : 0;
				if ( $total_number > 0 ) {
					$html .= ( 'checkboxes' === $type ) ?
						'<input type="checkbox" name="wcj_admin_filter_statuses[]" style="width:16px;height:16px;" value="' . $status_id . '"' .
						checked( in_array( $status_id, $checked_post_statuses, true ), true, false ) . '>' . $status_title . ' (' . $total_number . ') ' :
						'<option value="' . $status_id . '"' . selected( in_array( $status_id, $checked_post_statuses, true ), true, false ) . '>' .
						$status_title . ' (' . $total_number . ') </option>';
				}
			}
			$html .= ( 'checkboxes' === $type ) ?
				'</span>' :
				'</select>';
			$html .= wp_kses_post( wp_nonce_field( 'wcj_admin_filter_statuses_nonce', 'wcj_admin_filter_statuses_nonce' ) );
			return $html;
		}

		/**
		 * Add_shop_order_multiple_statuses.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param string $post_type defines the post_type.
		 * @param string $which defines the which.
		 */
		public function add_shop_order_multiple_statuses( $post_type, $which ) {
			if ( 'shop_order' === $post_type ) {
				echo wp_kses_post( $this->multiple_shop_order_statuses( wcj_get_option( 'wcj_order_admin_list_multiple_status_filter', 'no' ) ) );
			}
		}


		/**
		 * Add_shop_order_multiple_statuses_hpos.
		 *
		 * @version 7.1.4
		 * @since   1.0.0
		 * @param string $post_type defines the post_type.
		 * @param string $which defines the which.
		 */
		public function add_shop_order_multiple_statuses_hpos( $post_type, $which ) {

				echo wp_kses_post( $this->multiple_shop_order_statuses( wcj_get_option( 'wcj_order_admin_list_multiple_status_filter', 'no' ) ) );

		}

		/**
		 * Filter_shop_order_multiple_statuses.
		 *
		 * @version 5.6.8
		 * @since   2.5.7
		 * @param string $query defines the query.
		 */
		public function filter_shop_order_multiple_statuses( $query ) {
			if ( isset( $_SERVER['REQUEST_URI'] ) && ( false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-admin/edit.php' ) && isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] ) ) {
				if ( wcj_current_user_can( 'edit_others_pages' ) ) {
					if ( isset( $_GET['wcj_admin_filter_statuses'] ) ) {
						$wpnonce = isset( $_REQUEST['wcj_admin_filter_statuses_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_admin_filter_statuses_nonce'] ), 'wcj_admin_filter_statuses_nonce' ) : false;
						if ( $wpnonce ) {
							$post_statuses                    = array_map( 'sanitize_text_field', wp_unslash( $_GET['wcj_admin_filter_statuses'] ) );
							$query->query['post_status']      = $post_statuses;
							$query->query_vars['post_status'] = $post_statuses;
						}
					}
				}
			}
		}


		/**
		 * Filter_shop_order_multiple_statuses_hpos.
		 *
		 * @version 7.1.4
		 * @since   1.0.0
		 * @param string $status defines the status.
		 */
		public function filter_shop_order_multiple_statuses_hpos( $status ) {

			if ( isset( $_SERVER['REQUEST_URI'] ) && isset( $_GET['page'] ) && 'wc-orders' === $_GET['page'] ) {
				if ( wcj_current_user_can( 'edit_others_pages' ) ) {
					if ( isset( $_GET['wcj_admin_filter_statuses'] ) ) {
						$wpnonce = isset( $_REQUEST['wcj_admin_filter_statuses_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_admin_filter_statuses_nonce'] ), 'wcj_admin_filter_statuses_nonce' ) : false;
						if ( $wpnonce ) {
							$status = array_map( 'sanitize_text_field', wp_unslash( $_GET['wcj_admin_filter_statuses'] ) );
						}
					}
				}
			}
			return $status;
		}


		/**
		 * Filter the orders in admin based on options.
		 *
		 * @version 5.6.8
		 * @access  public
		 * @param   mixed $query defines the query.
		 * @return  void
		 */
		public function parse_query( $query ) {
			global $typenow, $wp_query;
			if ( 'shop_order' !== $typenow ) {
				return;
			}
			// phpcs:disable WordPress.Security.NonceVerification
			if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_country', 'no' ) && isset( $_GET['country'] ) && 'all' !== $_GET['country'] ) {
				$query->query_vars['meta_query'][] = array(
					'key'   => '_billing_country',
					'value' => sanitize_text_field( wp_unslash( $_GET['country'] ) ),
				);
			}
			if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) && isset( $_GET['currency'] ) && 'all' !== $_GET['currency'] ) {
				$query->query_vars['meta_query'][] = array(
					'key'   => '_order_currency',
					'value' => sanitize_text_field( wp_unslash( $_GET['currency'] ) ),
				);
			}
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 * Filters for post types.
		 *
		 * @version 5.6.8
		 */
		public function restrict_manage_posts() {
			global $typenow, $wp_query;
			if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) ) {
				// phpcs:disable WordPress.Security.NonceVerification
				if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
					$selected_coutry = isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : 'all';
					$countries       = array_merge( array( 'all' => __( 'All countries', 'woocommerce-jetpack' ) ), wcj_get_countries() );
					echo '<select id="country" name="country">';
					foreach ( $countries as $code => $name ) {
						echo wp_kses_post( '<option value="' . $code . '" ' . selected( $code, $selected_coutry, false ) . '>' . $name . '</option>' );
					}
					echo '</select>';
				}
				if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
					$selected_currency = isset( $_GET['currency'] ) ? sanitize_text_field( wp_unslash( $_GET['currency'] ) ) : 'all';
					$currencies        = array_merge( array( 'all' => __( 'All currencies', 'woocommerce-jetpack' ) ), wcj_get_woocommerce_currencies_and_symbols() );
					echo '<select id="currency" name="currency">';
					foreach ( $currencies as $code => $name ) {
						echo wp_kses_post( '<option value="' . $code . '" ' . selected( $code, $selected_currency, false ) . '>' . $name . '</option>' );
					}
					echo '</select>';
				}
				// phpcs:enable WordPress.Security.NonceVerification
			}
		}

		/**
		 * Rearange_order_columns.
		 *
		 * @version 2.5.7
		 * @version 2.5.7
		 * @param   string $columns defines the columns.
		 */
		public function rearange_order_columns( $columns ) {
			$reordered_columns        = wcj_get_option( 'wcj_order_admin_list_columns_order', $this->get_orders_default_columns_in_order() );
			$reordered_columns        = explode( PHP_EOL, $reordered_columns );
			$reordered_columns_result = array();
			if ( ! empty( $reordered_columns ) ) {
				foreach ( $reordered_columns as $column_id ) {
					$column_id = str_replace( "\n", '', $column_id );
					$column_id = str_replace( "\r", '', $column_id );
					if ( '' !== $column_id && isset( $columns[ $column_id ] ) ) {
						$reordered_columns_result[ $column_id ] = $columns[ $column_id ];
						unset( $columns[ $column_id ] );
					}
				}
			}
			return array_merge( $reordered_columns_result, $columns );
		}

		/**
		 * Add_order_columns.
		 *
		 * @version 2.8.0
		 * @param   string $columns defines the columns.
		 */
		public function add_order_columns( $columns ) {
			if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
				$columns['country'] = __( 'Billing Country', 'woocommerce-jetpack' );
			}
			if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
				$columns['currency'] = __( 'Currency Code', 'woocommerce-jetpack' );
			}
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
					$columns[ 'wcj_orders_custom_column_' . $i ] = wcj_get_option( 'wcj_orders_list_custom_columns_label_' . $i, '' );
				}
			}
			return $columns;
		}

		/**
		 * Output custom columns for orders
		 *
		 * @version 2.8.0
		 * @param   string $column defines the column.
		 */
		public function render_order_column( $column ) {
			if ( 'country' === $column && 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
				$country_code = do_shortcode( '[wcj_order_checkout_field field_id="billing_country"]' );
				echo wp_kses_post( ( 2 === strlen( $country_code ) ) )
					? wp_kses_post( wcj_get_country_flag_by_code( $country_code ) ) . ' ' . wp_kses_post( wcj_get_country_name_by_code( $country_code ) )
					: wp_kses_post( wcj_get_country_name_by_code( $country_code ) );
			} elseif ( 'currency' === $column && 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
				echo do_shortcode( '[wcj_order_currency]' );
			} else {
				$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
						if ( 'wcj_orders_custom_column_' . $i === $column ) {
							echo do_shortcode( wcj_get_option( 'wcj_orders_list_custom_columns_value_' . $i, '' ) );
						}
					}
				}
			}
		}


		/**
		 * Render_order_column_hpos
		 *
		 * @version 7.1.4
		 * @param   string $column defines the column.
		 * @param   string $order defines the order.
		 */
		public function render_order_column_hpos( $column, $order ) {

			if ( 'country' === $column && 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
				$country_code = do_shortcode( '[wcj_order_checkout_field order_id="' . $order->id . '" field_id="billing_country"]' );

				echo wp_kses_post( ( 2 === strlen( $country_code ) ) )
					? wp_kses_post( wcj_get_country_flag_by_code( $country_code ) ) . ' ' . wp_kses_post( wcj_get_country_name_by_code( $country_code ) )
					: wp_kses_post( wcj_get_country_name_by_code( $country_code ) );
			} elseif ( 'currency' === $column && 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
				echo do_shortcode( '[wcj_order_currency  order_id="' . $order->id . '"]' );
			} else {
				$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( 'yes' === wcj_get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
						if ( 'wcj_orders_custom_column_' . $i === $column ) {
							if ( str_contains( wcj_get_option( 'wcj_orders_list_custom_columns_value_' . $i, '' ), ']' ) ) {
								$get_value        = wcj_get_option( 'wcj_orders_list_custom_columns_value_' . $i, '' );
								$order_id         = ' order_id="' . $order->id . '"]';
								$custom_shortcode = str_replace( ']', $order_id, $get_value );
							} else {
								$custom_shortcode = wcj_get_option( 'wcj_orders_list_custom_columns_value_' . $i, '' );
							}
							echo do_shortcode( $custom_shortcode );
						}
					}
				}
			}
		}
	}

endif;

return new WCJ_Admin_Orders_List();

<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Display
 *
 * @version 7.2.5
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Display' ) ) :
		/**
		 * WCJ_PDF_Invoicing_Display.
		 *
		 * @version 3.6.0
		 */
	class WCJ_PDF_Invoicing_Display extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 8.1.0
		 */
		public function __construct() {

			$this->id         = 'pdf_invoicing_display';
			$this->parent_id  = 'pdf_invoicing';
			$this->short_desc = 'Display & Misc.';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Display & Misc.', 'woocommerce-jetpack' );
				}
			);
			$this->desc = '';
			parent::__construct( 'submodule' );

			if ( $this->is_enabled() ) {
				// Columns on Admin's Orders page.
				if ( true === wcj_is_hpos_enabled() ) {
					add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_order_column' ), PHP_INT_MAX - 3 );
					add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_order_columns_hpos' ), PHP_INT_MAX, 2 );
					if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '7.9', '>=' ) ) {
						add_filter( 'woocommerce_shop_order_list_table_sortable_columns', array( $this, 'shop_order_sortable_columns' ) );
						add_filter( 'woocommerce_shop_order_list_table_prepare_items_query_args', array( $this, 'shop_order_hpos_order_by_column' ) );
						add_filter( 'woocommerce_orders_table_query_clauses', array( $this, 'shop_order_hpos_invoice_sort_clauses' ), 10, 3 );
					}
				} else {
					add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_column' ), PHP_INT_MAX - 3 );
					add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), 2 );

				}
				// Action Links on Customer's My Account page.
				add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_pdf_invoices_action_links' ), PHP_INT_MAX, 2 );
				// Action Links on Customer's Thank You page.
				add_action( 'woocommerce_thankyou', array( $this, 'add_pdf_invoices_links_to_thankyou_page' ), 10, 1 );
				// Action Buttons to Admin's Orders list.
				add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_pdf_invoices_admin_actions' ), PHP_INT_MAX, 2 );
				add_filter( 'admin_head', array( $this, 'add_pdf_invoices_admin_actions_buttons_css' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				// Make Sortable Columns.
				add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'shop_order_sortable_columns' ) );
				add_action( 'pre_get_posts', array( $this, 'shop_order_pre_get_posts_order_by_column' ) );
				// Meta box on admin order page.
				add_action( 'add_meta_boxes', array( $this, 'add_invoices_meta_box' ) );
			}
		}

		/**
		 * Shop_order_pre_get_posts_order_by_column.
		 *
		 * @version 6.0.3
		 * @since   2.5.8
		 * @param Mixed $query get query.
		 */
		public function shop_order_pre_get_posts_order_by_column( $query ) {
			$orderby = $query->get( 'orderby' );
			if (
			$query->is_main_query() &&
			( $orderby ) &&
			isset( $query->query['post_type'] ) && 'shop_order' === $query->query['post_type'] &&
			isset( $query->is_admin ) && '1' === (string) $query->is_admin
			) {
				if ( 'wcj_invoicing_' === substr( $orderby, 0, 14 ) ) {
					$query->set( 'meta_key', '_' . $orderby );
					$query->set( 'orderby', 'meta_value_num' );
				}
			}
		}

		/**
		 * Make columns sortable.
		 *
		 * @version 2.9.0
		 * @since   2.5.8
		 * @param   array $columns get sortable columns.
		 * @return  array
		 */
		public function shop_order_sortable_columns( $columns ) {
			$custom = array();
			foreach ( wcj_get_enabled_invoice_types_ids() as $invoice_type_id ) {
				$custom[ $invoice_type_id ] = 'wcj_invoicing_' . $invoice_type_id . '_number_id';
			}
			return wp_parse_args( $custom, $columns );
		}

		/**
		 * Sort the native HPOS order list by an enabled document's numeric ID.
		 *
		 * @since 9.0.0
		 * @param array $args Native order-list query arguments.
		 * @return array Query arguments with existing filters preserved.
		 */
		public function shop_order_hpos_order_by_column( $args ) {
			if ( ! is_array( $args ) || ! isset( $args['orderby'] ) || ! is_string( $args['orderby'] )
				|| ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '7.9', '<' )
				|| ( array_key_exists( 'suppress_filters', $args ) && (bool) $args['suppress_filters'] )
				|| ( array_key_exists( 'meta_query', $args ) && ! is_array( $args['meta_query'] ) ) ) {
				return $args;
			}
			if ( ( isset( $args['meta_query']['key'] ) || isset( $args['meta_query']['value'] ) )
				|| ! in_array( $args['orderby'], $this->shop_order_sortable_columns( array() ), true ) ) {
				return $args;
			}
			// A named clause remains bound to the invoice even when Woo prepends shortcut metadata.
			$meta_query = array(
				'relation'                => 'AND',
				'wcj_invoice_number_sort' => array(
					'key'     => '_' . $args['orderby'],
					'compare' => 'EXISTS',
					'type'    => 'CHAR',
				),
			);
			if ( ! empty( $args['meta_query'] ) ) {
				// Nest the original group so its OR/AND semantics are not changed.
				$meta_query[] = $args['meta_query'];
			}
			$args['_wcj_hpos_invoice_sort'] = '_' . $args['orderby'];
			$args['meta_query']             = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Named HPOS invoice sort clause preserves the existing paginated order query and other filter semantics.
			$args['orderby']                = 'wcj_invoice_number_sort';
			return $args;
		}

		/**
		 * Preserve historical +0 numeric coercion for this adapter's named HPOS sort.
		 *
		 * @since 9.0.0
		 * @param array $clauses Compiled Woo query clauses.
		 * @param mixed $query   Woo order-table query instance.
		 * @param array $args    Remapped query arguments.
		 * @return array Clauses with only the recognized numeric order expression changed.
		 */
		public function shop_order_hpos_invoice_sort_clauses( $clauses, $query, $args ) {
			if ( ! is_array( $clauses ) || ! is_array( $args )
				|| ! ( $query instanceof \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery )
				|| ! isset( $args['_wcj_hpos_invoice_sort'], $args['orderby'], $args['meta_query'], $clauses['orderby'] )
				|| ! is_string( $args['_wcj_hpos_invoice_sort'] ) || ! is_array( $args['meta_query'] ) || ! is_string( $clauses['orderby'] )
				|| 'wcj_invoice_number_sort' !== $args['orderby']
				|| ( array_key_exists( 'suppress_filters', $args ) && (bool) $args['suppress_filters'] )
				|| ( isset( $args['type'] ) && 'shop_order' !== $args['type'] ) ) {
				return $clauses;
			}
			if ( ! in_array( substr( $args['_wcj_hpos_invoice_sort'], 1 ), $this->shop_order_sortable_columns( array() ), true ) ) {
				return $clauses;
			}
			$meta_query = $args['meta_query'];
			if ( ! isset( $meta_query['wcj_invoice_number_sort'] ) ) {
				// Woo's sole shortcut wrapper: AND(shortcut atomic clause, our original group).
				if ( array( 'relation', 0, 1 ) !== array_keys( $meta_query ) || 'AND' !== $meta_query['relation']
					|| ! is_array( $meta_query[0] ) || ! is_array( $meta_query[1] )
					|| array_diff( array_keys( $meta_query[0] ), array( 'key', 'value', 'compare', 'type', 'compare_key', 'type_key' ) ) ) {
					return $clauses;
				}
				$meta_query = $meta_query[1];
			}
			$invoice_clause = array(
				'key'     => $args['_wcj_hpos_invoice_sort'],
				'compare' => 'EXISTS',
				'type'    => 'CHAR',
			);
			if ( array( 'relation', 'wcj_invoice_number_sort' ) !== array_slice( array_keys( $meta_query ), 0, 2 )
				|| 'AND' !== $meta_query['relation'] || $invoice_clause !== $meta_query['wcj_invoice_number_sort'] ) {
				return $clauses;
			}
			// Only compiler-shaped ASCII aliases/directions enter SQL. Never interpolate argument values.
			if ( 1 === preg_match( '/\ACAST\((meta[0-9]+)\.meta_value AS CHAR\) (ASC|DESC)\z/', $clauses['orderby'], $matches ) ) {
				$clauses['orderby'] = $matches[1] . '.meta_value+0 ' . $matches[2];
			}
			return $clauses;
		}

		/**
		 * Enqueue_scripts.
		 *
		 * @version 2.5.2
		 * @since   2.5.2
		 */
		public function enqueue_scripts() {
			wp_enqueue_script( 'wcj-pdf-invoicing', wcj_plugin_url() . '/includes/js/wcj-pdf-invoicing.js', array(), time(), true );
		}

		/**
		 * Add_pdf_invoices_admin_actions_buttons_css.
		 *
		 * @version 5.6.7
		 * @since   2.4.7
		 */
		public function add_pdf_invoices_admin_actions_buttons_css() {
			echo '<style>' . PHP_EOL;
			$invoice_types = wcj_get_enabled_invoice_types();
			foreach ( $invoice_types as $invoice_type ) {
				echo '.view.' . esc_html( $invoice_type['id'] ) . '{ color: ' . esc_html( $invoice_type['color'] ) . ' !important; }' . PHP_EOL;
				echo '.view.' . esc_html( $invoice_type['id'] ) . '_create { color: ' . esc_html( $invoice_type['color'] ) . ' !important; }' . PHP_EOL;
				echo '.view.' . esc_html( $invoice_type['id'] ) . '_delete { color: ' . esc_html( $invoice_type['color'] ) . ' !important; }' . PHP_EOL;
				echo '.view.' . esc_html( $invoice_type['id'] ) . '::after { content: "\f498" !important; }' . PHP_EOL;
				echo '.view.' . esc_html( $invoice_type['id'] ) . '_create::after { content: "\f119" !important; }' . PHP_EOL;
				echo '.view.' . esc_html( $invoice_type['id'] ) . '_delete::after { content: "\f153" !important; }' . PHP_EOL;
			}
			echo '</style>' . PHP_EOL;
		}

		/**
		 * Add_pdf_invoices_admin_actions.
		 *
		 * @version 5.6.7
		 * @since   2.4.7
		 * @param Array $actions Get action data.
		 * @param Array $the_order Get the order.
		 */
		public function add_pdf_invoices_admin_actions( $actions, $the_order ) {
			$invoice_types = wcj_get_enabled_invoice_types();
			foreach ( $invoice_types as $invoice_type ) {
				if ( wcj_is_invoice_created( wcj_get_order_id( $the_order ), $invoice_type['id'] ) ) {
					if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_view_btn', 'no' ) ) {
						// Document (View) button.
						$query_args = array(
							'order_id'        => wcj_get_order_id( $the_order ),
							'invoice_type_id' => $invoice_type['id'],
							'get_invoice'     => '1',
						);
						if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
							$query_args['save_pdf_invoice'] = '1';
						}
						$the_url                   = esc_url( add_query_arg( $query_args, remove_query_arg( array( 'create_invoice_for_order_id', 'create_invoice_for_order_id-nonce', 'delete_invoice_for_order_id', 'delete_invoice_for_order_id-nonce' ) ) ) );
						$the_name                  = __( 'View', 'woocommerce-jetpack' ) . ' ' . $invoice_type['title'];
						$the_action                = 'view ' . $invoice_type['id'];
						$the_action_id             = $invoice_type['id'];
						$actions[ $the_action_id ] = array(
							'url'    => $the_url,
							'name'   => $the_name,
							'action' => $the_action,
						);
					}
					if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn', 'yes' ) ) {
						// Delete button.
						$query_args                = array(
							'delete_invoice_for_order_id' => wcj_get_order_id( $the_order ),
							'delete_invoice_for_order_id-nonce' => wp_create_nonce( 'delete_invoice_for_order_id' ),
							'invoice_type_id'             => $invoice_type['id'],
						);
						$the_url                   = esc_url( add_query_arg( $query_args, remove_query_arg( array( 'create_invoice_for_order_id', 'create_invoice_for_order_id-nonce' ) ) ) );
						$the_name                  = __( 'Delete', 'woocommerce-jetpack' ) . ' ' . $invoice_type['title'];
						$the_action                = 'view ' . $invoice_type['id'] . '_delete' . ( ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn_confirm', 'yes' ) ) ? ' wcj_need_confirmation' : '' );
						$the_action_id             = $invoice_type['id'] . '_delete';
						$actions[ $the_action_id ] = array(
							'url'    => $the_url,
							'name'   => $the_name,
							'action' => $the_action,
						);
					}
				} elseif ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn', 'yes' ) ) {
						// Create button.
						$query_args                = array(
							'create_invoice_for_order_id' => wcj_get_order_id( $the_order ),
							'create_invoice_for_order_id-nonce' => wp_create_nonce( 'create_invoice_for_order_id' ),
							'invoice_type_id'             => $invoice_type['id'],
						);
						$the_url                   = esc_url( add_query_arg( $query_args, remove_query_arg( array( 'delete_invoice_for_order_id', 'delete_invoice_for_order_id-nonce' ) ) ) );
						$the_name                  = __( 'Create', 'woocommerce-jetpack' ) . ' ' . $invoice_type['title'];
						$the_action                = 'view ' . $invoice_type['id'] . '_create' . ( ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn_confirm', 'yes' ) ) ? ' wcj_need_confirmation' : '' );
						$the_action_id             = $invoice_type['id'] . '_create';
						$actions[ $the_action_id ] = array(
							'url'    => $the_url,
							'name'   => $the_name,
							'action' => $the_action,
						);
				}
			}
			return $actions;
		}

		/**
		 * Add_order_column.
		 *
		 * @version 3.1.0
		 * @param  array $columns Get order columns.
		 */
		public function add_order_column( $columns ) {
			$invoice_types = wcj_get_enabled_invoice_types();
			foreach ( $invoice_types as $invoice_type ) {
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_page_column', 'yes' ) ) {
					$columns[ $invoice_type['id'] ] = wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_page_column_text', $invoice_type['title'] );
				}
			}
			return $columns;
		}

		/**
		 * Output custom columns for products
		 *
		 * @version 6.0.1
		 * @param  string $column Get order columns.
		 */
		public function render_order_columns( $column ) {
			$invoice_types_ids = wcj_get_enabled_invoice_types_ids();
			if ( ! in_array( $column, $invoice_types_ids, true ) ) {
				return;
			}
			$order_id        = get_the_ID();
			$invoice_type_id = $column;
			$html            = '';
			if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {
				$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
				$the_number  = $the_invoice->get_invoice_number();

				// Document Link.
				$query_args = array(
					'order_id'        => $order_id,
					'invoice_type_id' => $invoice_type_id,
					'get_invoice'     => '1',
				);
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type_id . '_save_as_enabled', 'no' ) ) {
					$query_args['save_pdf_invoice'] = '1';
				}
				$html .= '<a href="' . esc_url_raw( add_query_arg( $query_args, remove_query_arg( array( 'create_invoice_for_order_id', 'create_invoice_for_order_id-nonce', 'delete_invoice_for_order_id', 'delete_invoice_for_order_id-nonce' ) ) ) ) . '">' .
				$the_number . '</a>';
			}
			echo wp_kses_post( $html );
		}

		/**
		 * Output custom columns for products HPOS.
		 *
		 * @version 7.2.5
		 * @param  string $column Get column columns.
		 * @param  string $order Get order columns.
		 */
		public function render_order_columns_hpos( $column, $order ) {
			$invoice_types_ids = wcj_get_enabled_invoice_types_ids();
			if ( ! in_array( $column, $invoice_types_ids, true ) ) {
				return;
			}
			$order_id        = $order->get_id();
			$invoice_type_id = $column;
			$html            = '';
			if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {
				$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
				$the_number  = $the_invoice->get_invoice_number();

				// Document Link.
				$query_args = array(
					'order_id'        => $order_id,
					'invoice_type_id' => $invoice_type_id,
					'get_invoice'     => '1',
				);
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type_id . '_save_as_enabled', 'no' ) ) {
					$query_args['save_pdf_invoice'] = '1';
				}
				$html .= '<a href="' . esc_url( add_query_arg( $query_args, remove_query_arg( array( 'create_invoice_for_order_id', 'create_invoice_for_order_id-nonce', 'delete_invoice_for_order_id', 'delete_invoice_for_order_id-nonce' ) ) ) ) . '">' .
				$the_number . '</a>';
			}
			echo wp_kses_post( $html );
		}

		/**
		 * Add_pdf_invoices_links_to_thankyou_page.
		 *
		 * @version 5.5.9
		 * @since   3.6.0
		 * @todo    option to change priority for the hook (probably for all docs at once)
		 * @param int $order_id Get order id.
		 */
		public function add_pdf_invoices_links_to_thankyou_page( $order_id ) {
			$invoice_types = wcj_get_enabled_invoice_types();
			foreach ( $invoice_types as $invoice_type ) {
				if ( ! wcj_is_invoice_created( $order_id, $invoice_type['id'] ) ) {
					continue;
				}
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_on_thankyou_page', 'no' ) ) {
					$query_args = array(
						'order_id'        => $order_id,
						'invoice_type_id' => $invoice_type['id'],
						'get_invoice'     => '1',
					);
					if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
						$query_args['save_pdf_invoice'] = '1';
					}
					$title = wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_thankyou_page_link_text', $invoice_type['title'] );
					if ( '' === ( $title ) ) {
						$title = $invoice_type['title'];
					}
					echo wp_kses_post(
						str_replace(
							'%link%',
							'<a target="_blank" href="' . esc_url( add_query_arg( $query_args ) ) . '">' . $title . '</a>',
							get_option(
								'wcj_invoicing_' . $invoice_type['id'] . '_thankyou_page_template',
								/* translators: %s: search term */
								'<p><strong>' . sprintf( __( 'Your %s:', 'woocommerce-jetpack' ), $invoice_type['title'] ) . ' </strong> %link%</p>'
							)
						)
					);
				}
			}
		}

		/**
		 * Add_pdf_invoices_action_links.
		 *
		 * @version 5.5.9
		 * @param Array $actions Get actions.
		 * @param Array $the_order Get the order.
		 */
		public function add_pdf_invoices_action_links( $actions, $the_order ) {
			$invoice_types = wcj_get_enabled_invoice_types();
			foreach ( $invoice_types as $invoice_type ) {
				if ( ! wcj_is_invoice_created( wcj_get_order_id( $the_order ), $invoice_type['id'] ) ) {
					continue;
				}
				$my_account_option_name = 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_for_customers';
				if ( 'yes' === wcj_get_option( $my_account_option_name, 'no' ) ) {
					$the_action_id = $invoice_type['id'];
					$query_args    = array(
						'order_id'        => wcj_get_order_id( $the_order ),
						'invoice_type_id' => $invoice_type['id'],
						'get_invoice'     => '1',
					);
					if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
						$query_args['save_pdf_invoice'] = '1';
					}
					$the_url  = esc_url( add_query_arg( $query_args ) );
					$the_name = wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_link_text' );
					if ( '' === $the_name ) {
						$the_name = $invoice_type['title'];
					}
					$the_action                = 'view ' . $invoice_type['id'];
					$actions[ $the_action_id ] = array(
						'url'    => $the_url,
						'name'   => $the_name,
						'action' => $the_action,
					);
				}
			}
			return $actions;
		}

		/**
		 * Add_invoices_meta_box.
		 *
		 * @version 7.1.4
		 * @since   2.8.0
		 */
		public function add_invoices_meta_box() {
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_add_order_meta_box', 'yes' ) ) {
				if ( true === wcj_is_hpos_enabled() ) {
					add_meta_box(
						'wc-booster-pdf-invoicing',
						'<span class="dashicons dashicons-media-default" style="color:#23282d;"></span>' . __( 'Booster: PDF Invoices', 'woocommerce-jetpack' ),
						array( $this, 'create_invoices_meta_box' ),
						'woocommerce_page_wc-orders',
						'side',
						'default'
					);
				} else {
					add_meta_box(
						'wc-booster-pdf-invoicing',
						'<span class="dashicons dashicons-media-default" style="color:#23282d;"></span>' . __( 'Booster: PDF Invoices', 'woocommerce-jetpack' ),
						array( $this, 'create_invoices_meta_box' ),
						'shop_order',
						'side',
						'default'
					);
				}
			}
		}

		/**
		 * Create_invoices_meta_box.
		 *
		 * @version 7.1.4
		 * @since   2.8.0
		 */
		public function create_invoices_meta_box() {
			$_order        = wc_get_order();
			$order_id      = wcj_get_order_id( $_order );
			$invoice_types = wcj_get_enabled_invoice_types();
			if ( empty( $invoice_types ) ) {
				echo '<p style="font-style:italic;">' . wp_kses_post( 'You have no document types enabled.', 'woocommerce-jetpack' ) . '</p>';
			} else {
				foreach ( $invoice_types as $invoice_type ) {
					$table_data = array();
					$the_number = '';
					$the_date   = '';
					if ( wcj_is_invoice_created( $order_id, $invoice_type['id'] ) ) {
						// "Document (View)" link.
						$query_args = array(
							'order_id'        => $order_id,
							'invoice_type_id' => $invoice_type['id'],
							'get_invoice'     => '1',
						);
						$target     = ( 'yes' === wcj_get_option( 'wcj_invoicing_order_meta_box_open_in_new_window', 'yes' ) ? ' target="_blank"' : '' );
						if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
							$query_args['save_pdf_invoice'] = '1';
							$target                         = '';
						}
						$the_url     = esc_url( add_query_arg( $query_args, remove_query_arg( array( 'create_invoice_for_order_id', 'create_invoice_for_order_id-nonce', 'delete_invoice_for_order_id', 'delete_invoice_for_order_id-nonce' ) ) ) );
						$the_name    = __( 'View', 'woocommerce-jetpack' );
						$the_invoice = wcj_get_invoice( $order_id, $invoice_type['id'] );
						$the_number  = ' [#' . $the_invoice->get_invoice_number() . ']';
						$the_date    = '<span style="font-size:x-small;"> (' . gmdate( 'Y-m-d', $the_invoice->get_invoice_date() ) . ')</span>';
						$view_link   = '<a' . $target . ' href="' . $the_url . '">' . $the_name . '</a>';
						// "Delete" link.
						$query_args  = array(
							'delete_invoice_for_order_id' => $order_id,
							'delete_invoice_for_order_id-nonce' => wp_create_nonce( 'delete_invoice_for_order_id' ),
							'invoice_type_id'             => $invoice_type['id'],
						);
						$the_url     = esc_url( add_query_arg( $query_args, remove_query_arg( array( 'create_invoice_for_order_id', 'create_invoice_for_order_id-nonce' ) ) ) );
						$the_name    = __( 'Delete', 'woocommerce-jetpack' );
						$delete_link = '<a class="wcj_need_confirmation" href="' . $the_url . '">' . $the_name . '</a>';
						// Numbering & date.
						$number_input = '';
						if ( 'yes' === wcj_get_option( 'wcj_invoicing_add_order_meta_box_numbering', 'yes' ) ) {
							$number_option = 'wcj_invoicing_' . $invoice_type['id'] . '_number_id';
							$date_option   = 'wcj_invoicing_' . $invoice_type['id'] . '_date';
							$number_input  = '<br>' .
							'<input style="width:100%;" type="number"' .
								' id="' . $number_option . '" name="' . $number_option . '" value="' . esc_attr( $_order->get_meta( '_' . $number_option ) ) . '">' .
							'<input style="width:100%;" type="text"' .
								' id="' . $date_option . '" name="' . $date_option . '" value="' . esc_attr( gmdate( 'Y-m-d H:i:s', (int) $_order->get_meta( '_' . $date_option ) ) ) . '">' .
							'<input type="hidden" name="woojetpack_pdf_invoicing_save_post" value="woojetpack_pdf_invoicing_save_post">';
						}
						// Actions.
						$actions = array( $view_link . ' | ' . $delete_link . $number_input );
					} else {
						// "Create" link
						$query_args = array(
							'create_invoice_for_order_id' => $order_id,
							'create_invoice_for_order_id-nonce' => wp_create_nonce( 'create_invoice_for_order_id' ),
							'invoice_type_id'             => $invoice_type['id'],
						);
						$the_url    = esc_url( add_query_arg( $query_args, remove_query_arg( array( 'delete_invoice_for_order_id', 'delete_invoice_for_order_id-nonce' ) ) ) );
						$the_name   = __( 'Create', 'woocommerce-jetpack' );
						$actions    = array( '<a class="wcj_need_confirmation" href="' . $the_url . '">' . $the_name . '</a>' );
					}
					$maybe_toolptip = '';
					$_hooks         = wcj_get_invoice_create_on( $invoice_type['id'] );
					if ( in_array( 'woocommerce_order_partially_refunded_notification', $_hooks, true ) ) {
						$maybe_toolptip = wc_help_tip( __( 'In case of partial refund, you need to reload the page to see created document in this meta box.', 'woocommerce-jetpack' ), true );
					}
					$table_data[] = array(
						'<span class="dashicons dashicons-media-default" style="color:' . $invoice_type['color'] . ';"></span> ' .
												$invoice_type['title'] . $the_number . $the_date . $maybe_toolptip,
					);
					$table_data[] = $actions;
					echo '<p>' . wp_kses_post( wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) ) ) . '</p>';
				}
			}
		}
	}

endif;

return new WCJ_PDF_Invoicing_Display();

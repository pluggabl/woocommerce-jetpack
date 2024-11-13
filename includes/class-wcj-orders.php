<?php
/**
 * Booster for WooCommerce - Module - Orders
 *
 * @version 7.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Orders' ) ) :
	/**
	 * WCJ_Orders.
	 */
	class WCJ_Orders extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.1.4
		 * @todo    Bulk Regenerate Download Permissions - copy "cron" to plugin
		 * @todo    Bulk Regenerate Download Permissions - maybe move "bulk actions" to free
		 * @todo    Bulk Regenerate Download Permissions - maybe as new module
		 */
		public function __construct() {

			$this->id         = 'orders';
			$this->short_desc = __( 'Orders', 'woocommerce-jetpack' );
			$this->desc       = __( 'Orders auto-complete; admin order currency; admin order navigation; bulk regenerate download permissions for orders (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Orders auto-complete; admin order currency; admin order navigation; bulk regenerate download permissions for orders.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-orders';
			parent::__construct();

			if ( $this->is_enabled() ) {

				// Order auto complete.
				if ( 'yes' === wcj_get_option( 'wcj_order_auto_complete_enabled', 'no' ) ) {
					add_action( 'woocommerce_thankyou', array( $this, 'auto_complete_order' ), PHP_INT_MAX );
					add_action( 'woocommerce_payment_complete', array( $this, 'auto_complete_order' ), PHP_INT_MAX );
				}

				// Order currency.
				if ( 'yes' === wcj_get_option( 'wcj_order_admin_currency', 'no' ) ) {
					if ( true === wcj_is_hpos_enabled() ) {

						$this->meta_box_screen = 'woocommerce_page_wc-orders';
						add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
						add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box_hpos' ), PHP_INT_MAX, 2 );
					} else {
						$this->meta_box_screen = 'shop_order';
						add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
						add_action( 'save_post_shop_order', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

					}

					if ( 'filter' === wcj_get_option( 'wcj_order_admin_currency_method', 'filter' ) ) {
						$woocommerce_get_order_currency_filter = ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_order_currency' : 'woocommerce_order_get_currency' );
						add_filter( $woocommerce_get_order_currency_filter, array( $this, 'change_order_currency' ), PHP_INT_MAX, 2 );
					}
				}

				// Bulk Regenerate Download Permissions.
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_enabled', 'no' ) ) ) {
					// Actions.
					if ( 'yes' === wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_actions', 'no' ) ) {
						if ( true === wcj_is_hpos_enabled() ) {
							add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'register_bulk_actions_regenerate_download_permissions' ), PHP_INT_MAX );
							add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_actions_regenerate_download_permissions' ), 10, 3 );
						} else {
							add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_actions_regenerate_download_permissions' ), PHP_INT_MAX );
							add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_actions_regenerate_download_permissions' ), 10, 3 );}
					}
					// All orders.
					add_action( 'woojetpack_after_settings_save', array( $this, 'maybe_bulk_regenerate_download_permissions_all_orders' ) );
					// Admin notices.
					add_filter( 'admin_notices', array( $this, 'admin_notice_regenerate_download_permissions' ) );
					// All orders - Cron.
					if ( 'disabled' !== apply_filters( 'booster_option', 'disabled', wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_all_orders_cron', 'disabled' ) ) ) {
						add_action( 'init', array( $this, 'schedule_bulk_regenerate_download_permissions_all_orders_cron' ) );
						add_action( 'admin_init', array( $this, 'schedule_bulk_regenerate_download_permissions_all_orders_cron' ) );
						add_filter( 'cron_schedules', array( $this, 'wcj_crons_add_custom_intervals' ) );
						add_action( 'wcj_bulk_regenerate_download_permissions_all_orders_cron', array( $this, 'bulk_regenerate_download_permissions_all_orders' ) );
					}
				}

				// Country by IP.
				if ( 'yes' === wcj_get_option( 'wcj_orders_country_by_ip_enabled', 'no' ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_country_by_ip_meta_box' ) );
				}

				// Orders navigation.
				if ( 'yes' === wcj_get_option( 'wcj_orders_navigation_enabled', 'no' ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_orders_navigation_meta_box' ) );
					add_action( 'admin_init', array( $this, 'handle_orders_navigation' ) );
				}

				// Editable orders.
				if ( 'yes' === wcj_get_option( 'wcj_orders_editable_status_enabled', 'no' ) ) {
					add_filter( 'wc_order_is_editable', array( $this, 'editable_status' ), PHP_INT_MAX, 2 );
				}
			}
		}

		/**
		 * Editable_status.
		 *
		 * @version 5.6.2
		 * @since   4.0.0
		 * @param string | bool  $is_editable defines the is_editable.
		 * @param string | array $order defines the order.
		 */
		public function editable_status( $is_editable, $order ) {
			return in_array( $order->get_status(), wcj_get_option( 'wcj_orders_editable_status', array( 'pending', 'on-hold', 'auto-draft' ) ), true );
		}

		/**
		 * Handle_orders_navigation.
		 *
		 * @version 7.1.4
		 * @since   3.4.0
		 */
		public function handle_orders_navigation() {

			if ( true === wcj_is_hpos_enabled() ) {

				if ( isset( $_GET['wcj_orders_navigation'] ) ) {
					$wpnonce           = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wcj-order-meta-nonce' ) : false;
					$adjacent_order_id = $wpnonce && isset( $_GET['id'] ) && isset( $_GET['wcj_orders_navigation'] ) ? wcj_get_adjacent_order_id_hpos( sanitize_text_field( wp_unslash( $_GET['id'] ) ), sanitize_text_field( wp_unslash( $_GET['wcj_orders_navigation'] ) ) ) : false;
					$url               = ( ! isset( $_GET['id'] ) || false === ( $adjacent_order_id ) ?
					remove_query_arg( 'wcj_orders_navigation' ) :
					admin_url( 'post.php?post=' . $adjacent_order_id . '&action=edit' ) );
					wp_safe_redirect( $url );
					exit;
				}
			} else {
				if ( isset( $_GET['wcj_orders_navigation'] ) ) {
					$wpnonce           = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wcj-order-meta-nonce' ) : false;
					$adjacent_order_id = $wpnonce && isset( $_GET['post'] ) && isset( $_GET['wcj_orders_navigation'] ) ? wcj_get_adjacent_order_id( sanitize_text_field( wp_unslash( $_GET['post'] ) ), sanitize_text_field( wp_unslash( $_GET['wcj_orders_navigation'] ) ) ) : false;
					$url               = ( ! isset( $_GET['post'] ) || false === ( $adjacent_order_id ) ?
					remove_query_arg( 'wcj_orders_navigation' ) :
					admin_url( 'post.php?post=' . $adjacent_order_id . '&action=edit' ) );
					wp_safe_redirect( $url );
					exit;
				}
			}
		}

		/**
		 * Add_orders_navigation_meta_box.
		 *
		 * @version 7.1.4
		 * @since   3.4.0
		 */
		public function add_orders_navigation_meta_box() {
			if ( true === wcj_is_hpos_enabled() ) {

				add_meta_box(
					'wc-jetpack-' . $this->id . '-navigation',
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Order Navigation', 'woocommerce-jetpack' ),
					array( $this, 'create_orders_navigation_meta_box' ),
					'woocommerce_page_wc-orders',
					'side',
					'high'
				);

			} else {

				add_meta_box(
					'wc-jetpack-' . $this->id . '-navigation',
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Order Navigation', 'woocommerce-jetpack' ),
					array( $this, 'create_orders_navigation_meta_box' ),
					'shop_order',
					'side',
					'high'
				);
			}
		}

		/**
		 * Create_orders_navigation_meta_box.
		 *
		 * @version 5.6.2
		 * @since   3.4.0
		 * @todo    this will output the link, even if there no prev/next orders available
		 */
		public function create_orders_navigation_meta_box() {
			$query_arg_prev = array(
				'wcj_orders_navigation' => 'prev',
				'_wpnonce'              => wp_create_nonce( 'wcj-order-meta-nonce' ),
			);
			$query_arg_next = array(
				'wcj_orders_navigation' => 'next',
				'_wpnonce'              => wp_create_nonce( 'wcj-order-meta-nonce' ),
			);
			echo '<a href="' . esc_url( add_query_arg( $query_arg_prev ) ) . '">&lt;&lt; ' . esc_html__( 'Previous order', 'woocommerce-jetpack' ) . '</a>' .
			'<a href="' . esc_url( add_query_arg( $query_arg_next ) ) . '" style="float:right;">' . esc_html__( 'Next order', 'woocommerce-jetpack' ) . ' &gt;&gt;</a>';
		}

		/**
		 * Add_country_by_ip_meta_box.
		 *
		 * @version 7.1.4
		 * @since   3.3.0
		 */
		public function add_country_by_ip_meta_box() {

			if ( true === wcj_is_hpos_enabled() ) {

				add_meta_box(
					'wc-jetpack-' . $this->id . '-country-by-ip',
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Country by IP', 'woocommerce-jetpack' ),
					array( $this, 'create_country_by_ip_meta_box' ),
					'woocommerce_page_wc-orders',
					'side',
					'low'
				);

			} else {

				add_meta_box(
					'wc-jetpack-' . $this->id . '-country-by-ip',
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Country by IP', 'woocommerce-jetpack' ),
					array( $this, 'create_country_by_ip_meta_box' ),
					'woocommerce_page_wc-orders',
					'side',
					'low'
				);
			}

		}

		/**
		 * Create_country_by_ip_meta_box.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 */
		public function create_country_by_ip_meta_box() {
			$order       = wc_get_order();
			$customer_ip = $order->get_customer_ip_address();
			$location    = WC_Geolocation::geolocate_ip( $customer_ip );
			if (
			class_exists( 'WC_Geolocation' ) &&
			( $order ) &&
			( $customer_ip ) &&
			( $location ) &&
			isset( $location['country'] ) && '' !== $location['country']
			) {
				echo wp_kses_post( wcj_get_country_flag_by_code( $location['country'] ) ) . ' ' .
				wp_kses_post( wcj_get_country_name_by_code( $location['country'] ) ) .
				' (' . wp_kses_post( $location['country'] ) . ')' .
				' [' . wp_kses_post( $customer_ip ) . ']';
			} else {
				echo '<em>' . wp_kses_post( 'No data.', 'woocommerce-jetpack' ) . '</em>';
			}
		}

		/**
		 * Schedule_bulk_regenerate_download_permissions_all_orders_cron.
		 *
		 * @version 3.2.4
		 * @since   3.2.4
		 */
		public function schedule_bulk_regenerate_download_permissions_all_orders_cron() {
			wcj_crons_schedule_the_events(
				'wcj_bulk_regenerate_download_permissions_all_orders_cron',
				apply_filters( 'booster_option', 'disabled', wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_all_orders_cron', 'disabled' ) )
			);
		}

		/**
		 * Handle_bulk_actions_regenerate_download_permissions.
		 *
		 * @version 5.5.9
		 * @since   3.2.0
		 * @see     https://make.wordpress.org/core/2016/10/04/custom-bulk-actions/
		 * @todo    (maybe) "bulk actions" for for WP < 4.7
		 * @param string $redirect_to defines the redirect_to.
		 * @param string $doaction defines the doaction.
		 * @param array  $post_ids defines the post_ids.
		 */
		public function handle_bulk_actions_regenerate_download_permissions( $redirect_to, $doaction, $post_ids ) {
			if ( 'wcj_regenerate_download_permissions' !== $doaction ) {
				return $redirect_to;
			}
			$data_store = WC_Data_Store::load( 'customer-download' );
			foreach ( $post_ids as $post_id ) {
				$data_store->delete_by_order_id( $post_id );
				wc_downloadable_product_permissions( $post_id, true );
			}
			$redirect_to = esc_url( add_query_arg( 'wcj_bulk_regenerated_download_permissions', count( $post_ids ), $redirect_to ) );
			return $redirect_to;
		}

		/**
		 * Register_bulk_actions_regenerate_download_permissions.
		 *
		 * @version 3.2.0
		 * @since   3.2.0
		 * @param array $bulk_actions defines the bulk_actions.
		 */
		public function register_bulk_actions_regenerate_download_permissions( $bulk_actions ) {
			$bulk_actions['wcj_regenerate_download_permissions'] = __( 'Regenerate download permissions', 'woocommerce-jetpack' );
			return $bulk_actions;
		}

		/**
		 * Admin_notice_regenerate_download_permissions.
		 *
		 * @version 5.6.7
		 * @since   3.2.0
		 */
		public function admin_notice_regenerate_download_permissions() {
			$wpnonce = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'woocommerce-settings' ) : false;
			if ( $wpnonce && ! empty( $_REQUEST['wcj_bulk_regenerated_download_permissions'] ) ) {
				$orders_count = intval( $_REQUEST['wcj_bulk_regenerated_download_permissions'] );
				$message      = sprintf(
					/* translators: %s: translation added */
					_n( 'Download permissions regenerated for %s order.', 'Download permissions regenerated for %s orders.', $orders_count, 'woocommerce-jetpack' ),
					'<strong>' . $orders_count . '</strong>'
				);
				echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
			}
		}

		/**
		 * Bulk_regenerate_download_permissions_all_orders.
		 *
		 * @version 7.1.4
		 * @since   3.2.0
		 */
		public function bulk_regenerate_download_permissions_all_orders() {
			$data_store   = WC_Data_Store::load( 'customer-download' );
			$block_size   = 512;
			$offset       = 0;
			$total_orders = 0;
			while ( true ) {
				if ( true === wcj_is_hpos_enabled() ) {
					$args  = array(
						'type'           => 'shop_order',
						'status'         => 'any',
						'posts_per_page' => $block_size,
						'offset'         => $offset,
						'orderby'        => 'ID',
						'order'          => 'DESC',
						'fields'         => 'ids',
					);
					$order = wc_get_orders( $args );
					if ( ! $order ) {
						break;
					}
					$i = 0;
					foreach ( $order as $order_id ) {

						$data_store->delete_by_order_id( $order[ $i ]->id );
						wc_downloadable_product_permissions( $order[ $i ]->id, true );
						$total_orders++;
						$i++;
					}
				} else {
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
				}
				$offset += $block_size;
			}
			return $total_orders;
		}

		/**
		 * Maybe_bulk_regenerate_download_permissions_all_orders.
		 *
		 * @version 7.1.0
		 * @since   3.2.0
		 */
		public function maybe_bulk_regenerate_download_permissions_all_orders() {
			if ( 'yes' === wcj_get_option( 'wcj_order_bulk_regenerate_download_permissions_all_orders', 'no' ) ) {
				update_option( 'wcj_order_bulk_regenerate_download_permissions_all_orders', 'no' );
				$total_orders = $this->bulk_regenerate_download_permissions_all_orders();
				$wpnonce      = isset( $_REQUEST['wcj-verify-save-module-settings'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-verify-save-module-settings'] ), 'wcj-verify-save-module-settings' ) : false;
				$active_tab   = isset( $_POST['wcj_setting_active_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_setting_active_tab'] ) ) : '';

				if ( isset( $_POST['return_url'] ) ) {
					$return_url = sanitize_text_field( wp_unslash( $_POST['return_url'] ) ) . '&active_tab=' . $active_tab . '&success=1&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' );
					wp_safe_redirect(
						add_query_arg(
							array(
								'wcj_bulk_regenerated_download_permissions' => $total_orders,
								'wcj_bulk_regenerated_download_permissions-nonce' => wp_create_nonce( 'wcj_bulk_regenerated_download_permissions' ),
							),
							$return_url
						)
					);
					exit();
				} else {
					wp_safe_redirect(
						add_query_arg(
							array(
								'wcj_bulk_regenerated_download_permissions' => $total_orders,
								'wcj_bulk_regenerated_download_permissions-nonce' => wp_create_nonce( 'wcj_bulk_regenerated_download_permissions' ),
							),
							admin_url( 'admin.php?page=wcj-plugins&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) )
						)
					);
					exit();
				}
			}
		}

		/**
		 * Change_order_currency.
		 *
		 * @version 7.1.4
		 * @since   2.5.6
		 * @todo    (maybe) move meta box to `side`
		 * @param string $order_currency defines the order_currency.
		 * @param array  $_order defines the _order.
		 */
		public function change_order_currency( $order_currency, $_order ) {
			if ( true === wcj_is_hpos_enabled() ) {
				$wcj_order_currency = $_order->get_meta( '_wcj_order_currency' );
			} else {
				$wcj_order_currency = get_post_meta( wcj_get_order_id( $_order ), '_wcj_order_currency', true );
			}
			return ( '' !== ( $wcj_order_currency ) ) ? $wcj_order_currency : $order_currency;
		}

		/**
		 * Auto Complete all WooCommerce orders.
		 *
		 * @version 7.2.4
		 * @todo    (maybe) at first check if status is not `completed` already (however `WC_Order::set_status()` checks that anyway)
		 * @param int $order_id defines the order_id.
		 */
		public function auto_complete_order( $order_id ) {
			if ( ! $order_id ) {
				return;
			}
			$order           = wc_get_order( $order_id );
			$payment_methods = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_order_auto_complete_payment_methods', array() ) );
			$order_status    = $order->get_status();
			if ( 'failed' === $order_status ) {
				return;
			}
			if ( ! empty( $payment_methods ) && ! in_array( $order->get_payment_method(), $payment_methods, true ) ) {
				return;
			}
			$order->update_status( 'completed' );
		}

		/**
		 * Wcj_crons_add_custom_intervals.
		 *
		 * @version 5.6.8
		 * @since   3.2.4
		 * @param   array $schedules defines the schedules.
		 */
		public function wcj_crons_add_custom_intervals( $schedules ) {
			$schedules['weekly']    = array(
				'interval' => 604800,
				'display'  => __( 'Once weekly', 'woocommerce-jetpack' ),
			);
			$schedules['minute_30'] = array(
				'interval' => 1800,
				'display'  => __( 'Once every 30 minutes', 'woocommerce-jetpack' ),
			);
			$schedules['minute_15'] = array(
				'interval' => 900,
				'display'  => __( 'Once every 15 minutes', 'woocommerce-jetpack' ),
			);
			$schedules['minute_5']  = array(
				'interval' => 300,
				'display'  => __( 'Once every 5 minutes', 'woocommerce-jetpack' ),
			);
			$schedules['minutely']  = array(
				'interval' => 60,
				'display'  => __( 'Once a minute', 'woocommerce-jetpack' ),
			);
			return $schedules;
		}
	}

endif;

return new WCJ_Orders();

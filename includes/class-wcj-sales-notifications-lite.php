<?php
/**
 * Booster for WooCommerce - Module - Sales Notifications Lite
 *
 * @version 7.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Sales_Notifications_Lite' ) ) :

	/**
	 * WCJ_Sales_Notifications_Lite.
	 *
	 * @version 7.3.0
	 */
	class WCJ_Sales_Notifications_Lite extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.3.0
		 */
		public function __construct() {
			$this->id         = 'sales_notifications_lite';
			$this->short_desc = __( 'Sales Notifications (Lite)', 'woocommerce-jetpack' );
			$this->desc       = __( 'Show recent real purchases in a simple, privacy-friendly popup. Fixed design & timing. Upgrade to Booster Elite for full customization, filters, schedules, sounds, and more.', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Show recent real purchases in a simple, privacy-friendly popup.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-sales-notifications-lite';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( $this->is_elite_sales_notifications_active() ) {
					add_action( 'admin_notices', array( $this, 'elite_override_notice' ) );
					return;
				}

				if ( wcj_is_frontend() ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
					add_action( 'wp_footer', array( $this, 'render_notifications' ) );
				}
			}
		}

		/**
		 * Check if Elite Sales Notifications is active.
		 *
		 * @version 7.3.0
		 * @return bool
		 */
		private function is_elite_sales_notifications_active() {
			$elite_active = class_exists( 'WCJ_Elite' ) || defined( 'WCJ_ELITE_VERSION' );
			$elite_sn_on  = function_exists( 'wcj_is_module_enabled' ) ? wcj_is_module_enabled( 'sales_notifications' ) : false;
			return $elite_active && $elite_sn_on;
		}

		/**
		 * Show admin notice when Elite overrides Lite.
		 *
		 * @version 7.3.0
		 */
		public function elite_override_notice() {
			if ( isset( $_GET['section'] ) && 'sales_notifications_lite' === $_GET['section'] ) {
				echo '<div class="notice notice-info"><p>' . esc_html__( 'Sales Notifications are managed by Booster Elite. Lite output is disabled.', 'woocommerce-jetpack' ) . '</p></div>';
			}
		}

		/**
		 * Enqueue scripts and styles.
		 *
		 * @version 7.3.0
		 */
		public function enqueue_scripts() {
			if ( ! $this->should_show_notifications() ) {
				return;
			}

			wp_enqueue_style( 'wcj-sales-notifications-lite', wcj_plugin_url() . '/includes/sales-notifications-lite/assets/frontend.css', array(), w_c_j()->version );
			wp_enqueue_script( 'wcj-sales-notifications-lite', wcj_plugin_url() . '/includes/sales-notifications-lite/assets/frontend.js', array(), w_c_j()->version, true );

			$items = $this->get_notification_items();
			wp_localize_script( 'wcj-sales-notifications-lite', 'wcjSalesNotificationsLite', array(
				'items' => $items,
				'settings' => array(
					'firstDelay' => 5000,
					'displayDuration' => 5000,
					'gapBetween' => 12000,
					'maxPerPage' => 5,
					'theme' => wcj_get_option( 'wcj_sales_notifications_lite_theme', 'light' ),
				),
			) );
		}

		/**
		 * Check if notifications should be shown.
		 *
		 * @version 7.3.0
		 * @return bool
		 */
		private function should_show_notifications() {
			if ( ! ( is_shop() || is_product() ) ) {
				return false;
			}

			if ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url( 'order-received' ) ) {
				return false;
			}

			if ( current_user_can( 'manage_woocommerce' ) && 'yes' !== wcj_get_option( 'wcj_sales_notifications_lite_test_mode', 'no' ) ) {
				return false;
			}

			if ( $this->is_daily_cap_reached() ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if daily cap is reached.
		 *
		 * @version 7.3.0
		 * @return bool
		 */
		private function is_daily_cap_reached() {
			$tz = wp_timezone();
			$today_key = ( new DateTime( 'now', $tz ) )->format( 'Y-m-d' );
			$opt_name = 'wcj_sn_lite_daily_' . $today_key;
			$count = (int) get_option( $opt_name, 0 );
			return $count >= 20;
		}

		/**
		 * Increment daily count.
		 *
		 * @version 7.3.0
		 */
		private function increment_daily_count() {
			$tz = wp_timezone();
			$today_key = ( new DateTime( 'now', $tz ) )->format( 'Y-m-d' );
			$opt_name = 'wcj_sn_lite_daily_' . $today_key;
			$count = (int) get_option( $opt_name, 0 );
			update_option( $opt_name, $count + 1 );
		}

		/**
		 * Get notification items.
		 *
		 * @version 7.3.0
		 * @return array
		 */
		private function get_notification_items() {
			$cache_key = 'wcj_sn_lite_items_' . get_locale() . '_' . ( is_shop() ? 'shop' : 'product' );
			$cached_items = get_site_transient( $cache_key );
			
			if ( false !== $cached_items ) {
				return $cached_items;
			}

			$items = $this->build_notification_items();
			
			set_site_transient( $cache_key, $items, 5 * MINUTE_IN_SECONDS );
			
			return $items;
		}

		/**
		 * Build notification items from recent orders.
		 *
		 * @version 7.3.0
		 * @return array
		 */
		private function build_notification_items() {
			$items = array();
			
			$date_72h_ago = new DateTime( '-72 hours', wp_timezone() );
			
			$orders = wc_get_orders( array(
				'status' => array( 'processing', 'completed' ),
				'date_created' => '>=' . $date_72h_ago->getTimestamp(),
				'orderby' => 'date',
				'order' => 'DESC',
				'limit' => 25,
			) );

			foreach ( $orders as $order ) {
				foreach ( $order->get_items() as $item ) {
					$product = $item->get_product();
					if ( ! $product || 'publish' !== $product->get_status() || 'hidden' === $product->get_catalog_visibility() ) {
						continue;
					}

					$first_name = $order->get_billing_first_name();
					$city = $order->get_billing_city();
					$country_code = $order->get_billing_country();
					
					$first_initial = $first_name ? substr( $first_name, 0, 1 ) . '.' : 'Someone';
					$city_display = $city ? $city : 'your area';
					$country_display = $country_code && isset( WC()->countries->countries[ $country_code ] ) 
						? WC()->countries->countries[ $country_code ] 
						: 'your area';

					$product_name = $product->get_name();
					
					if ( $product->is_type( 'variation' ) ) {
						$attributes = $product->get_variation_attributes();
						if ( ! empty( $attributes ) ) {
							$attr_string = implode( ', ', array_values( $attributes ) );
							$product_name .= ' (' . $attr_string . ')';
						}
					}

					$time_ago = $this->get_time_ago( $order->get_date_created() );

					$items[] = array(
						'first_initial' => esc_html( $first_initial ),
						'city' => esc_html( $city_display ),
						'country' => esc_html( $country_display ),
						'product' => esc_html( $product_name ),
						'time_ago' => esc_html( $time_ago ),
					);

					if ( count( $items ) >= 25 ) {
						break 2;
					}
				}
			}

			return apply_filters( 'wcj_sales_notifications_lite_items', $items );
		}

		/**
		 * Get time ago string.
		 *
		 * @version 7.3.0
		 * @param WC_DateTime $date_created
		 * @return string
		 */
		private function get_time_ago( $date_created ) {
			$now = new DateTime( 'now', wp_timezone() );
			$created = $date_created->getOffsetTimestamp();
			$diff = $now->getTimestamp() - $created;

			if ( $diff < 3600 ) {
				$minutes = max( 1, floor( $diff / 60 ) );
				return sprintf( _n( '%d minute ago', '%d minutes ago', $minutes, 'woocommerce-jetpack' ), $minutes );
			} else {
				$hours = floor( $diff / 3600 );
				return sprintf( _n( '%d hour ago', '%d hours ago', $hours, 'woocommerce-jetpack' ), $hours );
			}
		}

		/**
		 * Render notifications HTML.
		 *
		 * @version 7.3.0
		 */
		public function render_notifications() {
			if ( ! $this->should_show_notifications() ) {
				return;
			}

			$items = $this->get_notification_items();
			if ( empty( $items ) ) {
				return;
			}

			$this->increment_daily_count();

			do_action( 'wcj_sales_notifications_lite_rendering', count( $items ) );
			?>
			<div id="wcj-sales-notifications-lite-container" style="display: none;" role="status" aria-live="polite" aria-label="<?php esc_attr_e( 'Recent purchase notifications', 'woocommerce-jetpack' ); ?>">
				<div class="wcj-sn-lite-notification">
					<span class="wcj-sn-lite-text"></span>
					<button class="wcj-sn-lite-close" aria-label="<?php esc_attr_e( 'Close notification', 'woocommerce-jetpack' ); ?>">&times;</button>
				</div>
			</div>
			<?php
		}
	}

endif;

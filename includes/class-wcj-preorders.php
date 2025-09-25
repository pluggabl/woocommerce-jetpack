<?php
/**
 * Booster for WooCommerce - Module - Pre Orders
 *
 * @version 7.3.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Preorders' ) ) :
	/**
	 * WCJ_Preorders.
	 *
	 * @version 1.0.0
	 */
	class WCJ_Preorders extends WCJ_Module {

		/**
		 * Settings.
		 *
		 * @var array $settings Settings array.
		 */
		protected $settings;

		/**
		 * Language.
		 *
		 * @var string $lang Language code.
		 */
		protected $lang;

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id         = 'preorders';
			$this->short_desc = __( 'Pre Orders', 'woocommerce-jetpack' );
			$this->desc       = __( 'Pre Orders.', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Pre Orders.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-preorders';

			parent::__construct();

			if ( $this->is_enabled() ) {

				// Meta boxes.
				add_action( 'add_meta_boxes', array( $this, 'add_meta_box_preorder' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

				// Product display.
				add_filter( 'woocommerce_get_price_html', array( $this, 'modify_price_display' ), 10, 2 );
				add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'modify_add_to_cart_text' ), 10, 2 );
				add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'modify_add_to_cart_url' ), 10, 2 );

				// Cart and checkout.
				add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 3 );
				add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_preorder_pricing' ) );
				add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_preorder_fees' ) );

				// Stock management.
				add_action( 'woocommerce_product_set_stock_status', array( $this, 'handle_stock_status_change' ), 99, 1 );
				add_action( 'woocommerce_process_product_meta', array( $this, 'check_stock_status_on_save' ), 99, 1 );

				// Order processing.
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_preorder' ), 10, 3 );

				// Email notifications.
				add_action( 'woocommerce_email_classes', array( $this, 'add_email_classes' ) );

				// Button customization.
				add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'modify_add_to_cart_text' ), 10, 2 );
				add_filter( 'woocommerce_product_variable_add_to_cart_text', array( $this, 'modify_add_to_cart_text' ), 10, 2 );
				add_action( 'woocommerce_before_single_variation', array( $this, 'add_variation_button_class' ) );
				add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_preorder_button_class' ), 10, 3 );

				// Styles and messages.
				add_action( 'wp_head', array( $this, 'add_preorder_styles' ) );
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'display_preorder_message' ) );

				// Add cart quantity validation.
				add_filter( 'woocommerce_update_cart_validation', array( $this, 'validate_cart_item_quantity' ), 10, 4 );

				add_action( 'admin_footer', array( $this, 'admin_footer_preorder_script' ) );
			}
		}

		/**
		 * Add_meta_box.
		 *
		 * @version 1.0.0
		 */
		public function add_meta_box_preorder() {
			global $post;

			$selected_products = get_option( 'wcj_preorders_enable_products_include', array() );

			if ( $post && 'product' === $post->post_type && ! in_array( $post->ID, $selected_products ) ) { // phpcs:ignore
				return;
			}

			if ( true === wcj_is_hpos_enabled() && 'woocommerce_page_wc-orders' === get_current_screen()->id ) {
				$screen   = ( isset( $this->meta_box_screen ) ) ? $this->meta_box_screen : 'product';
				$context  = ( isset( $this->meta_box_context ) ) ? $this->meta_box_context : 'normal';
				$priority = ( isset( $this->meta_box_priority ) ) ? $this->meta_box_priority : 'high';
				add_meta_box(
					'wc-jetpack-' . $this->id,
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . $this->short_desc,
					array( $this, 'create_meta_box_hpos' ),
					$screen,
					$context,
					$priority
				);
			} else {
				$screen   = ( isset( $this->meta_box_screen ) ) ? $this->meta_box_screen : 'product';
				$context  = ( isset( $this->meta_box_context ) ) ? $this->meta_box_context : 'normal';
				$priority = ( isset( $this->meta_box_priority ) ) ? $this->meta_box_priority : 'high';
				add_meta_box(
					'wc-jetpack-' . $this->id,
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . $this->short_desc,
					array( $this, 'create_meta_box' ),
					$screen,
					$context,
					$priority
				);
			}
		}

		/**
		 * Add email classes
		 *
		 * @version 1.0.0
		 * @param array $email_classes Array of WC_Emails.
		 * @return array
		 */
		public function add_email_classes( $email_classes ) {
			// Include all email class files.
			$email_classes['WCJ_Email_Admin_Preorder_Purchase']        = include 'emails/class-wcj-email-admin-preorder-purchase.php';
			$email_classes['WCJ_Email_Customer_Preorder_Confirmation'] = include 'emails/class-wcj-email-customer-preorder-confirmation.php';

			return $email_classes;
		}

		/**
		 * Handle notifications
		 *
		 * @since 1.0.0
		 * @param string $type Notification type.
		 * @param int    $object_id Related object ID.
		 * @param string $recipient Recipient type (admin/customer).
		 * @return void
		 */
		protected function handle_notification( $type, $object_id, $recipient = 'both' ) {
			if ( ! class_exists( 'WC_Emails' ) ) {
				return;
			}

			$mailer = WC()->mailer();
			if ( ! $mailer ) {
				return;
			}

			$notifications = get_option( 'wcj_preorders_email_notifications', array() );
			if ( empty( $notifications ) ) {
				return;
			}

			$email_map = array(
				'purchase'   => array(
					'admin'    => 'WCJ_Email_Admin_Preorder_Purchase',
					'customer' => 'WCJ_Email_Customer_Preorder_Confirmation',
				),
				'release'    => array(
					'admin'    => 'WCJ_Email_Admin_Release_Date',
					'customer' => 'WCJ_Email_Customer_Preorder_Release',
				),
				'outofstock' => array(
					'admin' => 'WCJ_Email_Admin_Out_Of_Stock',
				),
			);

			if ( ! isset( $email_map[ $type ] ) ) {
				return;
			}

			$emails = $mailer->get_emails();

			foreach ( $email_map[ $type ] as $recip => $class ) {
				if ( 'both' === $recipient || $recipient === $recip ) {
					$notification_key = "{$recip}_{$type}";

					if ( in_array( $notification_key, $notifications, true ) ) {
						$email = isset( $emails[ $class ] ) ? $emails[ $class ] : false;

						if ( $email && method_exists( $email, 'trigger' ) ) {
							$email->trigger( $object_id );
						}
					}
				}
			}
		}

		/**
		 * Send pre-order notifications.
		 *
		 * @since 1.0.0
		 * @param int $order_id Order ID.
		 * @param int $product_id Product ID.
		 * @return void
		 */
		protected function send_preorder_notifications( $order_id, $product_id ) {
			// Get enabled notifications.
			$notifications = get_option( 'wcj_preorders_email_notifications', array() );

			// Send admin purchase notification.
			if ( in_array( 'admin_purchase', $notifications, true ) ) {
				$this->handle_notification( 'purchase', $order_id, 'admin' );
			}

			// Send customer confirmation notification.
			if ( in_array( 'customer_confirm', $notifications, true ) ) {
				$this->handle_notification( 'purchase', $order_id, 'customer' );
			}
		}

		/**
		 * Modify price display for pre-order products.
		 *
		 * @param string     $price_html Price HTML.
		 * @param WC_Product $product Product object.
		 * @return string
		 */
		public function modify_price_display( $price_html, $product ) {
			if ( ! $this->is_preorder_enabled( $product->get_id() ) ) {
				return $price_html;
			}

			// Handle variable products.
			if ( $product->is_type( 'variable' ) ) {
				$variation_prices   = $product->get_variation_prices();
				$min_price          = PHP_FLOAT_MAX;
				$max_price          = 0;
				$has_varying_prices = false;

				foreach ( $variation_prices['regular_price'] as $variation_id => $price ) {
					$variation = wc_get_product( $variation_id );
					if ( ! $variation ) {
						continue;
					}

					$preorder_price = $this->calculate_preorder_price( $variation );
					$min_price      = min( $min_price, $preorder_price );
					$max_price      = max( $max_price, $preorder_price );

					if ( reset( $variation_prices['regular_price'] ) !== $preorder_price ) {
						$has_varying_prices = true;
					}
				}

				if ( PHP_FLOAT_MAX === $min_price || 0 === $max_price ) {
					return $price_html;
				}

				if ( $min_price === $max_price && ! $has_varying_prices ) {
					return wc_price( $min_price );
				}

				$price_html = wc_format_price_range( $min_price, $max_price );

				return $price_html;
			}

			// Handle simple products.
			$preorder_price = $this->calculate_preorder_price( $product );
			return wc_price( $preorder_price );
		}

		/**
		 * Calculate pre-order price.
		 *
		 * @param WC_Product $product Product object.
		 * @return float
		 */
		protected function calculate_preorder_price( $product ) {
			if ( ! $product ) {
				return 0;
			}

			$product_id = $product->get_id();
			$parent_id  = $product->get_parent_id();

			// For variations, check if parent has pre-order enabled.
			if ( $product->is_type( 'variation' ) ) {
				$price_type     = get_post_meta( $parent_id, '_wcj_product_preorder_price_type', true );
				$original_price = $product->get_regular_price();

				// If no regular price is set on variation, get from parent.
				if ( '' === $original_price ) {
					$parent_product = wc_get_product( $parent_id );
					if ( $parent_product ) {
						$original_price = $parent_product->get_regular_price();
					}
				}
			} else {
				$price_type     = get_post_meta( $product_id, '_wcj_product_preorder_price_type', true );
				$original_price = $product->get_regular_price();
			}

			// If no regular price is set, try sale price or return 0.
			if ( '' === $original_price ) {
				$original_price = $product->get_sale_price();
				if ( '' === $original_price ) {
					return 0;
				}
			}

			$original_price = floatval( $original_price );

			switch ( $price_type ) {
				case 'fixed':
					$fixed_price = $product->is_type( 'variation' )
						? get_post_meta( $parent_id, '_wcj_product_preorder_fixed_price', true )
						: get_post_meta( $product_id, '_wcj_product_preorder_fixed_price', true );
					return ! empty( $fixed_price ) ? floatval( $fixed_price ) : $original_price;

				case 'discount':
					$adjustment = $product->is_type( 'variation' )
						? get_post_meta( $parent_id, '_wcj_product_preorder_price_adjustment', true )
						: get_post_meta( $product_id, '_wcj_product_preorder_price_adjustment', true );
					if ( empty( $adjustment ) ) {
						return $original_price;
					}
					return $original_price * ( 1 - ( floatval( $adjustment ) / 100 ) );

				case 'increase':
					$adjustment = $product->is_type( 'variation' )
						? get_post_meta( $parent_id, '_wcj_product_preorder_price_adjustment', true )
						: get_post_meta( $product_id, '_wcj_product_preorder_price_adjustment', true );
					if ( empty( $adjustment ) ) {
						return $original_price;
					}
					return $original_price * ( 1 + ( floatval( $adjustment ) / 100 ) );

				default:
					return $original_price;
			}
		}

		/**
		 * Handle stock status changes.
		 *
		 * @version 1.0.0
		 * @param int $product_id Product ID.
		 */
		public function handle_stock_status_change( $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				return;
			}

			$status = $product->get_stock_status();

			if ( 'outofstock' === $status ) {
				if ( $this->is_eligible_for_auto_preorder( $product_id ) ) {
					$this->auto_enable_preorder( $product_id );
				} elseif ( 'yes' === get_post_meta( $product_id, '_wcj_product_preorder_enabled', true ) ) {
					$product->set_manage_stock( true );
					$product->set_stock_quantity( 0 );
					$product->set_backorders( 'yes' );
					$product->set_stock_status( 'instock' );
					$product->save();
				}
			}
		}

		/**
		 * Handle stock status changes.
		 *
		 * @version 1.0.0
		 * @param int $product_id Product ID.
		 */
		public function check_stock_status_on_save( $product_id ) {
			$product      = wc_get_product( $product_id );
			$stock_status = $product->get_stock_status();

			if ( 'outofstock' === $stock_status ) {
				if ( $this->is_eligible_for_auto_preorder( $product_id ) ) {
					$this->auto_enable_preorder( $product_id );
				} elseif ( 'yes' === get_post_meta( $product_id, '_wcj_product_preorder_enabled', true ) ) {
					$product->set_manage_stock( true );
					$product->set_stock_quantity( 0 );
					$product->set_backorders( 'yes' );
					$product->set_stock_status( 'instock' );
					$product->save();
				}
			}
		}

		/**
		 * Check if product is eligible for auto pre-order.
		 *
		 * @version 1.0.0
		 * @param int $product_id Product ID.
		 * @return bool
		 */
		public function is_eligible_for_auto_preorder( $product_id ) {
			// Check if auto-enable is turned on.
			if ( 'yes' !== wcj_get_option( 'wcj_preorders_auto_enable_outofstock', 'no' ) ) {
				return false;
			}

			// Get product categories.
			$product_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

			// Check category exclusions.
			$excluded_cats = wcj_get_option( 'wcj_preorders_auto_enable_categories_exclude', array() );
			if ( ! empty( $excluded_cats ) && array_intersect( $product_cats, $excluded_cats ) ) {
				return false;
			}

			// Check category inclusions.
			$included_cats = wcj_get_option( 'wcj_preorders_auto_enable_categories_include', array() );
			if ( ! empty( $included_cats ) && ! array_intersect( $product_cats, $included_cats ) ) {
				return false;
			}

			// Check product exclusions.
			$excluded_products = wcj_get_option( 'wcj_preorders_auto_enable_products_exclude', array() );
			if ( in_array( $product_id, $excluded_products ) ) { // phpcs:ignore.
				return false;
			}

			// Check product inclusions.
			$included_products = wcj_get_option( 'wcj_preorders_auto_enable_products_include', array() );
			if ( ! empty( $included_products ) && ! in_array( $product_id, $included_products ) ) { // phpcs:ignore.
				return false;
			}

			return true;
		}

		/**
		 * Check if prices should be hidden from guests.
		 *
		 * @return bool
		 */
		protected function should_hide_price_for_guests() {
			return 'yes' === get_option( 'wcj_preorders_hide_prices_guests', 'no' );
		}

		/**
		 * Handle product release.
		 *
		 * @param int $product_id Product ID.
		 */
		protected function handle_product_release( $product_id ) {
			update_post_meta( $product_id, '_wcj_product_preorder_enabled', 'no' );

			// Get all orders containing this pre-order product.
			$orders = wc_get_orders(
				array(
					'meta_key'   => '_wcj_preorder', // phpcs:ignore
					'meta_value' => 'yes', // phpcs:ignore
					'status'     => array( 'processing', 'on-hold', 'pending' ),
				)
			);

			foreach ( $orders as $order ) {
				foreach ( $order->get_items() as $item ) {
					if ( $item->get_product_id() === $product_id ) {
						$this->handle_notification( 'release', $order->get_id(), 'customer' );
						break;
					}
				}
			}

			$this->handle_notification( 'release', $product_id, 'admin' );
		}

		/**
		 * Cart validation and fee handling.
		 *
		 * @param bool $passed Validation status.
		 * @param int  $product_id Product ID.
		 * @param int  $quantity Quantity.
		 * @return bool
		 */
		public function validate_add_to_cart( $passed, $product_id, $quantity ) {
			if ( ! $this->is_preorder_enabled( $product_id ) ) {
				return $passed;
			}

			// Check user access.
			if ( ! $this->can_user_preorder() ) {
				wc_add_notice( esc_html__( 'You do not have permission to pre-order products.', 'woocommerce-jetpack' ), 'error' );
				return false;
			}

			// Check maximum quantity including existing cart items.
			$max_qty = get_post_meta( $product_id, '_wcj_product_preorder_max_qty', true );
			if ( ! empty( $max_qty ) ) {
				$cart_quantity = 0;

				// Get quantity of this product already in cart.
				if ( ! empty( WC()->cart ) ) {
					foreach ( WC()->cart->get_cart() as $cart_item ) {
						if ( $cart_item['product_id'] === $product_id ) {
							$cart_quantity += $cart_item['quantity'];
						}
					}
				}

				// Total quantity would be current cart quantity plus new quantity.
				$total_quantity = $cart_quantity + $quantity;

				if ( $total_quantity > $max_qty ) {
					wc_add_notice(
						sprintf(
							/* translators: %1$d: maximum quantity, %2$d: current quantity in cart */
							esc_html__( 'Maximum pre-order quantity is %1$d. You already have %2$d in your cart.', 'woocommerce-jetpack' ),
							$max_qty,
							$cart_quantity
						),
						'error'
					);
					return false;
				}
			}

			// Check mixed cart.
			if ( 'yes' === get_option( 'wcj_preorders_prevent_mixed_cart', 'no' ) ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					$is_existing_preorder = $this->is_preorder_enabled( $cart_item['product_id'] );
					if ( ( $is_existing_preorder && ! $this->is_preorder_enabled( $product_id ) ) ||
						( ! $is_existing_preorder && $this->is_preorder_enabled( $product_id ) ) ) {
						wc_add_notice( esc_html__( 'Cannot mix pre-order and regular products.', 'woocommerce-jetpack' ), 'error' );
						return false;
					}
				}
			}

			return $passed;
		}

		/**
		 * Apply pre-order pricing.
		 *
		 * @param WC_Cart $cart Cart object.
		 */
		public function apply_preorder_pricing( $cart ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			if ( empty( $cart->cart_contents ) ) {
				return;
			}

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = $product->get_id();
				$parent_id  = $product->get_parent_id();

				// Check if pre-order is enabled on the product or its parent.
				$is_preorder = $product->is_type( 'variation' )
					? $this->is_preorder_enabled( $parent_id )
					: $this->is_preorder_enabled( $product_id );

				if ( $is_preorder ) {
					$price = $this->calculate_preorder_price( $product );
					$product->set_price( $price );
					$cart_item['data']->set_price( $price );
				}
			}
		}

		/**
		 * Stock management.
		 *
		 * @param int $product_id Product ID.
		 */
		public function handle_stock_management( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				return;
			}

			// Auto-enable pre-order for out of stock products.
			if ( 'yes' === get_option( 'wcj_preorders_auto_enable_outofstock', 'no' ) &&
				! $product->is_in_stock() &&
				$this->is_eligible_for_auto_preorder( $product_id ) ) {
				update_post_meta( $product_id, '_wcj_product_preorder_enabled', 'yes' );
				$this->handle_notification( 'outofstock', $product_id );
			}

			// Check release dates.
			$release_date = get_post_meta( $product_id, '_wcj_product_preorder_release_date', true );
			if ( ! empty( $release_date ) && strtotime( $release_date ) <= current_time( 'timestamp' ) ) { // phpcs:ignore
				$this->handle_product_release( $product_id );
			}
		}

		/**
		 * Order processing.
		 *
		 * @param int      $order_id Order ID.
		 * @param array    $posted_data Posted data.
		 * @param WC_Order $order Order object.
		 */
		public function process_preorder( $order_id, $posted_data, $order ) {
			$has_preorder = false;

			foreach ( $order->get_items() as $item ) {
				if ( $this->is_preorder_enabled( $item->get_product_id() ) ) {
					$has_preorder = true;
					update_post_meta( $order_id, '_wcj_preorder', 'yes' );
					update_post_meta(
						$order_id,
						'_wcj_preorder_release_date',
						get_post_meta( $item->get_product_id(), '_wcj_product_preorder_release_date', true )
					);

					// Send notifications.
					$this->send_preorder_notifications( $order_id, $item->get_product_id() );
					$this->handle_notification( 'purchase', $item->get_product_id() );
					break;
				}
			}

			if ( $has_preorder ) {
				// Add order note.
				$order->add_order_note( esc_html__( 'This order contains pre-order items.', 'woocommerce-jetpack' ) );
			}
		}

		/**
		 * Check if pre-order is enabled for product.
		 *
		 * @version 1.0.0
		 * @param int $product_id Product ID.
		 * @return bool
		 */
		public function is_preorder_enabled( $product_id ) {
			// Check if module is enabled.
			if ( ! wcj_is_module_enabled( 'preorders' ) ) {
				return false;
			}

			$selected_products = get_option( 'wcj_preorders_enable_products_include', array() );
			if ( ! empty( $selected_products ) && ! in_array( $product_id, $selected_products ) ) { // phpcs:ignore
				return false;
			}

			// Get product pre-order status.
			$is_preorder_enabled = get_post_meta( $product_id, '_wcj_product_preorder_enabled', true );
			$product             = wc_get_product( $product_id );

			// Check for auto-enable conditions.
			if ( 'yes' !== $is_preorder_enabled ) {
				if ( $product &&
					'outofstock' === $product->get_stock_status() &&
					'yes' === get_option( 'wcj_preorders_auto_enable_outofstock', 'no' ) &&
					$this->is_eligible_for_auto_preorder( $product_id )
				) {
					// Auto-enable pre-order for out of stock products if eligible.
					$this->auto_enable_preorder( $product_id );
					return true;
				}
				return false;
			}

			return true;
		}

		/**
		 * Auto enable pre-order for a product
		 *
		 * @param int $product_id Product ID.
		 * @since 1.0.0
		 */
		protected function auto_enable_preorder( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				return;
			}

			// Enable pre-order.
			update_post_meta( $product_id, '_wcj_product_preorder_enabled', 'yes' );

			// Set release date only if not set or old.
			$current_release_date = get_post_meta( $product_id, '_wcj_product_preorder_release_date', true );
			$current_timestamp    = ! empty( $current_release_date ) ? strtotime( $current_release_date ) : 0;
			$current_time         = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

			if ( empty( $current_release_date ) || $current_timestamp < $current_time ) {
				$default_days = absint( get_option( 'wcj_preorders_default_availability_days', '30' ) );
				$release_date = gmdate( 'Y-m-d H:i:s', strtotime( "+{$default_days} days" ) );
				update_post_meta( $product_id, '_wcj_product_preorder_release_date', $release_date );
			}

			// Set price type only if not set.
			$current_price_type = get_post_meta( $product_id, '_wcj_product_preorder_price_type', true );
			if ( empty( $current_price_type ) ) {
				$default_price_type = get_option( 'wcj_preorders_default_price_type', 'default' );
				update_post_meta( $product_id, '_wcj_product_preorder_price_type', $default_price_type );

				// Set price adjustment only if price type is discount/increase and not already set.
				if ( ( 'discount' === $default_price_type || 'increase' === $default_price_type ) ) {
					$current_adjustment = get_post_meta( $product_id, '_wcj_product_preorder_price_adjustment', true );
					if ( '' === $current_adjustment ) {
						$default_adjustment = get_option( 'wcj_preorders_default_price_adjustment', '0' );
						update_post_meta( $product_id, '_wcj_product_preorder_price_adjustment', $default_adjustment );
					}
				}
			}

			// Enable backorders and stock management.
			$product->set_manage_stock( true );
			$product->set_stock_quantity( 0 );
			$product->set_backorders( 'yes' );
			$product->set_stock_status( 'instock' );
			$product->save();

			// Set default message if not set.
			$current_message = get_post_meta( $product_id, '_wcj_product_preorder_message', true );
			if ( empty( $current_message ) ) {
				$default_message = get_option(
					'wcj_preorders_default_message',
					__( 'This item is available for pre-order and will be released on %release_date%', 'woocommerce-jetpack' )
				);
				$release_date    = get_post_meta( $product_id, '_wcj_product_preorder_release_date', true );
				$message         = str_replace(
					'%release_date%',
					date_i18n( get_option( 'date_format' ), strtotime( $release_date ) ),
					$default_message
				);
				update_post_meta( $product_id, '_wcj_product_preorder_message', $message );
			}

			// Notify admin.
			$this->handle_notification( 'outofstock', $product_id );
		}

		/**
		 * Check if user can pre-order.
		 *
		 * @return bool
		 */
		public function can_user_preorder() {
			$access_type   = get_option( 'wcj_preorders_access_type', 'all' );
			$allowed_roles = get_option( 'wcj_preorders_allowed_user_roles', array() );

			if ( 'all' === $access_type ) {
				return true;
			}

			if ( ! is_user_logged_in() ) {
				if ( ! empty( $allowed_roles ) ) {
					return in_array( 'guest', $allowed_roles, true );
				}

				return true;
			}

			if ( 'registered' === $access_type ) {
				return true;
			}

			$user       = wp_get_current_user();
			$user_roles = (array) $user->roles;

			if ( ! empty( $allowed_roles ) && ! array_intersect( $allowed_roles, $user_roles ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Modify the add to cart button text for pre-order products.
		 *
		 * @param string     $text Text for the button.
		 * @param WC_Product $product Product object.
		 * @return string
		 */
		public function modify_add_to_cart_text( $text, $product ) {
			if ( ! $this->is_preorder_enabled( $product->get_id() ) ) {
				return $text;
			}

			// Get custom button text.
			$button_text = get_post_meta( $product->get_id(), '_wcj_product_preorder_button_text', true );
			if ( empty( $button_text ) ) {
				$button_text = get_option( 'wcj_preorders_button_text', __( 'Pre-order Now', 'woocommerce-jetpack' ) );
			}

			return esc_html( $button_text );
		}

		/**
		 * Modify the add to cart button URL for pre-order products.
		 *
		 * @param string     $url URL for the button.
		 * @param WC_Product $product Product object.
		 * @return string
		 */
		public function modify_add_to_cart_url( $url, $product ) {
			if ( $this->is_preorder_enabled( $product->get_id() ) ) {
				if ( ! $this->can_user_preorder() ) {
					return '#';
				}
			}
			return $url;
		}

		/**
		 * Check if fee should be applied based on categories
		 *
		 * @param int $product_id Product ID.
		 * @return bool
		 */
		protected function should_apply_fee_for_category( $product_id ) {
			$product_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

			// Check excluded categories.
			$excluded_cats = get_option( 'wcj_preorders_fee_exclude_cats', array() );
			if ( ! empty( $excluded_cats ) && array_intersect( $excluded_cats, $product_cats ) ) {
				return false;
			}

			// Check included categories.
			$included_cats = get_option( 'wcj_preorders_fee_include_cats', array() );
			if ( ! empty( $included_cats ) && ! array_intersect( $included_cats, $product_cats ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if fee should be applied based on user role
		 *
		 * @return bool
		 */
		protected function should_apply_fee_for_user() {
			$excluded_roles = get_option( 'wcj_preorders_fee_exclude_roles', array() );
			$included_roles = get_option( 'wcj_preorders_fee_include_roles', array() );

			// Handle guest (non-logged-in) users.
			if ( ! is_user_logged_in() ) {
				// If 'guest' is explicitly excluded, do not apply the fee.
				if ( ! empty( $excluded_roles ) && in_array( 'guest', $excluded_roles, true ) ) {
					return false;
				}
				// If 'guest' is explicitly included, apply the fee.
				if ( ! empty( $included_roles ) ) {
					return in_array( 'guest', $included_roles, true );
				}
				// If no inclusion list is set, apply the fee by default.
				return true;
			}

			// Handle logged-in users.
			$user       = wp_get_current_user();
			$user_roles = (array) $user->roles;

			// Check if user is in the excluded roles list.
			if ( ! empty( $excluded_roles ) && array_intersect( $excluded_roles, $user_roles ) ) {
				return false;
			}

			// If the included roles list is not empty and the user has no matching role, do not apply the fee.
			if ( ! empty( $included_roles ) && ! array_intersect( $included_roles, $user_roles ) ) {
				return false;
			}

			// Apply the fee by default if no restrictions match.
			return true;
		}

		/**
		 * Add pre-order fees to cart.
		 *
		 * @param WC_Cart $cart Cart object.
		 */
		public function add_preorder_fees( $cart ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			// Check if global fee is enabled.
			if ( 'yes' !== get_option( 'wcj_preorders_global_fee_enabled', 'no' ) ) {
				return;
			}

			$has_preorder = false;
			$total_fee    = 0;
			$apply_fee    = false;

			// Check each cart item.
			foreach ( $cart->get_cart() as $cart_item ) {
				if ( $this->is_preorder_enabled( $cart_item['product_id'] ) ) {
					$has_preorder = true;

					// Check category restrictions.
					if ( ! $this->should_apply_fee_for_category( $cart_item['product_id'] ) ) {
						continue;
					}

					$apply_fee = true;
					break;
				}
			}

			// Add global fee if enabled and applicable.
			if ( $has_preorder && $apply_fee && $this->should_apply_fee_for_user() ) {
				$global_fee = get_option( 'wcj_preorders_global_fee', '' );
				if ( ! empty( $global_fee ) ) {
					$total_fee += floatval( $global_fee );
				}

				// Add fee if greater than zero.
				if ( $total_fee > 0 ) {
					$fee_title = get_option( 'wcj_preorders_global_fee_title', __( 'Pre-order Fee', 'woocommerce-jetpack' ) );
					$cart->add_fee(
						$fee_title,
						$total_fee,
						true, // Taxable.
						'' // Tax class.
					);
				}
			}
		}

		/**
		 * Add custom styles for pre-order buttons
		 */
		public function add_preorder_styles() {
			if ( ! $this->is_enabled() ) {
				return;
			}

			$bg_color         = get_option( 'wcj_preorders_button_bg_color', '#007cba' );
			$text_color       = get_option( 'wcj_preorders_button_text_color', '#ffffff' );
			$hover_bg_color   = get_option( 'wcj_preorders_button_hover_bg_color', '#0073aa' );
			$hover_text_color = get_option( 'wcj_preorders_button_hover_text_color', '#ffffff' );
			?>
			<style type="text/css">
				/* Simple product buttons */
				.single_add_to_cart_button.wcj-preorder-button,
				.button.product_type_simple.wcj-preorder-button {
					background-color: <?php echo esc_attr( $bg_color ); ?> !important;
					color: <?php echo esc_attr( $text_color ); ?> !important;
				}
				/* Variable product buttons */
				.button.product_type_variable.wcj-preorder-button,
				.variations_form .single_add_to_cart_button.wcj-preorder-button,
				.product_type_variable.add_to_cart_button.wcj-preorder-button.variable-preorder {
					background-color: <?php echo esc_attr( $bg_color ); ?> !important;
					color: <?php echo esc_attr( $text_color ); ?> !important;
				}

				/* Hover states */
				.single_add_to_cart_button.wcj-preorder-button:hover,
				.button.product_type_simple.wcj-preorder-button:hover,
				.button.product_type_variable.wcj-preorder-button:hover,
				.variations_form .single_add_to_cart_button.wcj-preorder-button:hover,
				.product_type_variable.add_to_cart_button.wcj-preorder-button.variable-preorder:hover {
					background-color: <?php echo esc_attr( $hover_bg_color ); ?> !important;
					color: <?php echo esc_attr( $hover_text_color ); ?> !important;
				}

				/* Additional specificity for variable products */
				.variable-preorder.wcj-preorder-button,
				.product-type-variable .wcj-preorder-button {
					background-color: <?php echo esc_attr( $bg_color ); ?> !important;
					color: <?php echo esc_attr( $text_color ); ?> !important;
				}

				.variable-preorder.wcj-preorder-button:hover,
				.product-type-variable .wcj-preorder-button:hover {
					background-color: <?php echo esc_attr( $hover_bg_color ); ?> !important;
					color: <?php echo esc_attr( $hover_text_color ); ?> !important;
				}
			</style>
			<?php
		}

		/**
		 * Add custom class to pre-order buttons
		 *
		 * @param string     $html HTML markup.
		 * @param WC_Product $product Product object.
		 * @param array      $args Arguments.
		 * @return string
		 */
		public function add_preorder_button_class( $html, $product, $args ) {
			if ( $this->is_preorder_enabled( $product->get_id() ) ) {
				if ( $product->is_type( 'variable' ) ) {
					$html = str_replace( 'button', 'button wcj-preorder-button variable-preorder', $html );
				} else {
					$html = str_replace( 'button', 'button wcj-preorder-button', $html );
				}
			}
			return $html;
		}

		/**
		 * Display pre-order message on product pages
		 */
		public function display_preorder_message() {
			global $product;

			if ( ! $product || ! $this->is_preorder_enabled( $product->get_id() ) ) {
				return;
			}

			// Get custom message.
			$message = get_post_meta( $product->get_id(), '_wcj_product_preorder_message', true );
			if ( empty( $message ) ) {
				$message = get_option( 'wcj_preorders_message', __( 'This item is available for pre-order.', 'woocommerce-jetpack' ) );
			}

			// Get release date.
			$release_date = get_post_meta( $product->get_id(), '_wcj_product_preorder_release_date', true );
			if ( ! empty( $release_date ) ) {
				$formatted_date = date_i18n( get_option( 'date_format' ), strtotime( $release_date ) );
				$message        = str_replace( '%release_date%', $formatted_date, $message );
			}

			$message_style = get_option( 'wcj_preorders_message_style', 'notice' );

			if ( 'custom' === $message_style ) {
				$text_color = get_option( 'wcj_preorders_message_text_color', '#515151' );
				?>
				<div class="wcj-preorder-message" style="color: <?php echo esc_attr( $text_color ); ?>;">
					<?php echo wp_kses_post( wpautop( $message ) ); ?>
				</div>
				<?php
			} else {
				wc_print_notice( $message, $message_style );
			}
		}

		/**
		 * Validate cart item quantity updates.
		 *
		 * @param bool   $passed     Current validation status.
		 * @param string $cart_item_key Cart item key.
		 * @param array  $values    Cart item values.
		 * @param int    $quantity  New quantity.
		 * @return bool
		 */
		public function validate_cart_item_quantity( $passed, $cart_item_key, $values, $quantity ) {
			if ( ! isset( $values['product_id'] ) ) {
				return $passed;
			}

			$product_id = $values['product_id'];

			if ( ! $this->is_preorder_enabled( $product_id ) ) {
				return $passed;
			}

			$max_qty = get_post_meta( $product_id, '_wcj_product_preorder_max_qty', true );
			if ( ! empty( $max_qty ) ) {
				if ( $quantity > $max_qty ) {
					wc_add_notice(
						sprintf(
							/* translators: %d: maximum quantity */
							esc_html__( 'Maximum pre-order quantity is %d.', 'woocommerce-jetpack' ),
							$max_qty
						),
						'error'
					);
					return false;
				}
			}

			return $passed;
		}

		/**
		 * Add custom class to variation form buttons
		 */
		public function add_variation_button_class() {
			global $product;

			if ( ! $product || ! $product->is_type( 'variable' ) ) {
				return;
			}

			if ( $this->is_preorder_enabled( $product->get_id() ) ) {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						$('.variations_form .single_add_to_cart_button').addClass('wcj-preorder-button');
						$('.variations_form').on('show_variation', function() {
							$('.single_add_to_cart_button').addClass('wcj-preorder-button');
						});
					});
				</script>
				<?php
			}
		}

		/**
		 * Admin_footer_preorder_script.
		 *
		 * @version 1.0.0
		 * @return void
		 */
		public function admin_footer_preorder_script() {
			?>
			<script>
				jQuery(document).ready(function($){
					var maxSelected = 3;

					$('#wcj_preorders_enable_products_include').on('change', function(){
						var selected = $(this).val() || [];
						if (selected.length > maxSelected) {
							selected.pop();
							$(this).val(selected);
							alert('You can select a maximum of ' + maxSelected + ' products.');
						}
					});
				});
			</script>
			<?php
		}
	}
endif;

return new WCJ_Preorders();

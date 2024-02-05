<?php
/**
 * Booster for WooCommerce - Module - Max Products per User
 *
 * @version 7.1.6
 * @since   3.5.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Max_Products_Per_User' ) ) :
	/**
	 * WCJ_Max_products_Per_User.
	 */
	class WCJ_Max_Products_Per_User extends WCJ_Module {

		/**
		 * The module order_status
		 *
		 * @var varchar $order_status Module.
		 */
		public $order_status;

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   3.5.0
		 * @todo    (maybe) JS
		 * @todo    (maybe) zero quantity for "Guest"
		 * @todo    (maybe) editable sales data (i.e. change "Qty Bought" for product for user)
		 * @todo    (maybe) `wcj_max_products_per_user_order_status` - add "Any" option
		 * @todo    (maybe) `wcj_max_products_per_user_order_status` - add "Manually" option
		 */
		public function __construct() {

			$this->id         = 'max_products_per_user';
			$this->short_desc = __( 'Maximum Products per User', 'woocommerce-jetpack' );
			$this->desc       = __( 'Limit number of items your (logged) customers can buy (Free version allows to limit globally).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Limit number of items your (logged) customers can buy.', 'woocommerce-jetpack' );
			$this->extra_desc = __( 'Please note, that there is no maximum quantity set for not-logged (i.e. guest) users. Product quantities are updated, when order status is changed to status listed in module\'s "Order Status" option.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-maximum-products-per-user';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( 'yes' === wcj_get_option( 'wcj_max_products_per_user_global_enabled', 'no' ) || 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_max_products_per_user_local_enabled', 'no' ) ) ) {
					add_action( 'woocommerce_checkout_process', array( $this, 'check_cart_quantities' ), PHP_INT_MAX );
					add_action( 'woocommerce_before_cart', array( $this, 'check_cart_quantities' ), PHP_INT_MAX );
					if ( 'yes' === wcj_get_option( 'wcj_max_products_per_user_stop_from_adding_to_cart', 'no' ) ) {
						add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_on_add_to_cart' ), PHP_INT_MAX, 3 );
					}
					if ( 'yes' === wcj_get_option( 'wcj_max_products_per_user_stop_from_seeing_checkout', 'no' ) ) {
						add_action( 'wp', array( $this, 'stop_from_seeing_checkout' ), PHP_INT_MAX );
					}
					if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_max_products_per_user_local_enabled', 'no' ) ) ) {
						add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
						add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
					}
				}
				$this->order_status = wcj_get_option( 'wcj_max_products_per_user_order_status', array( 'wc-completed' ) );
				if ( empty( $this->order_status ) ) {
					$this->order_status = array( 'wc-completed' );
				}
				foreach ( $this->order_status as $status ) {
					$status = substr( $status, 3 );
					add_action( 'woocommerce_order_status_' . $status, array( $this, 'save_quantities' ), PHP_INT_MAX );
				}
				add_filter( 'woocommerce_duplicate_product_exclude_meta', array( $this, 'wcj_filter_woocommerce_duplicate_product_exclude_meta' ), 10, 1 );
				add_action( 'add_meta_boxes', array( $this, 'add_report_meta_box' ) );
				add_action( 'admin_init', array( $this, 'calculate_data' ) );
				add_action( 'admin_notices', array( $this, 'calculate_data_notice' ) );
			}
		}

		/**
		 *
		 * Wcj_filter_woocommerce_duplicate_product_exclude_meta.
		 *
		 * @version 5.6.2
		 * @since  5.6.2
		 * @param int $meta_data defines the meta data of the product.
		 */
		public function wcj_filter_woocommerce_duplicate_product_exclude_meta( $meta_data ) {
			return array_merge( $meta_data, array( '_wcj_max_products_per_user_report' ) );
		}

		/**
		 * Validate_on_add_to_cart.
		 *
		 * @version 6.0.1
		 * @since   4.2.0
		 * @todo    [dev] code refactoring (this function is very similar to `$this->check_quantities()`)
		 * @todo    [dev] (maybe) recheck `wc_add_notice()` or `wc_print_notice()`
		 * @todo    [feature] add two additional (i.e. not `wcj_max_products_per_user_message`) separate messages: 1) `( 0 == $currently_in_cart )` and 2) '( 0 != $currently_in_cart )`
		 * @todo    [feature] add replaced value `%qty_already_in_cart%` (same in `$this->check_quantities()`)
		 * @todo    [feature] add replaced value `%current_add_to_cart_qty%`
		 * @todo    [feature] (maybe) add replaced value `%remaining_qty_incl_qty_already_in_cart%`
		 * @todo    [feature] (maybe) option to choose `wc_add_notice( $message, 'error' );` or `wc_add_notice( $message, 'notice' );`
		 * @param string $passed defines the passed.
		 * @param int    $product_id defines the product_id.
		 * @param int    $quantity defines the quantity.
		 */
		public function validate_on_add_to_cart( $passed, $product_id, $quantity ) {
			// Get max quantity (for current product).
			$max_qty = $this->get_max_qty( $product_id );
			if ( 0 === ( $max_qty ) ) {
				return $passed; // no max qty set for current product.
			}
			// Get quantity already bought (for current user / current product).
			$current_user_id = wcj_get_current_user_id();
			if ( 0 === ( $current_user_id ) ) {
				return $passed; // unlogged (i.e. guest) user.
			}
			$user_already_bought = 0;
			$users_quantities    = get_post_meta( $product_id, '_wcj_max_products_per_user_report', true );
			if ( ( $users_quantities ) && isset( $users_quantities[ $current_user_id ] ) ) {
				$user_already_bought = $users_quantities[ $current_user_id ];
			}
			// Get quantity in cart (for current product).
			$currently_in_cart = 0;
			if ( isset( WC()->cart ) ) {
				$cart_item_quantities = WC()->cart->get_cart_item_quantities();
				if ( ! empty( $cart_item_quantities ) && is_array( $cart_item_quantities ) ) {
					foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
						if ( $_product_id === $product_id ) {
							$currently_in_cart += $cart_item_quantity;
						}
					}
				}
			}
			// Validate.
			if ( ( $currently_in_cart + $user_already_bought + $quantity ) > $max_qty ) {
				$product         = wc_get_product( $product_id );
				$replaced_values = array(
					'%max_qty%'            => $max_qty,
					'%product_title%'      => $product->get_title(),
					'%qty_already_bought%' => $user_already_bought,
					'%remaining_qty%'      => max( ( (int) $max_qty - $user_already_bought ), 0 ),
				);
				$message         = wcj_get_option(
					'wcj_max_products_per_user_message',
					__( 'You can only buy maximum %max_qty% pcs. of %product_title% (you already bought %qty_already_bought% pcs.).', 'woocommerce-jetpack' )
				);
				$message         = str_replace( array_keys( $replaced_values ), $replaced_values, $message );
				wc_add_notice( $message, 'error' );
				return false;
			}
			// Passed.
			return $passed;
		}

		/**
		 * Calculate_data_notice.
		 *
		 * @version 5.6.8
		 * @since   3.5.0
		 */
		public function calculate_data_notice() {
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_max_products_per_user_calculate_data_finished'] ) ) {
				$class   = 'notice notice-info';
				$message = __( 'Data re-calculated.', 'woocommerce-jetpack' ) . ' ' .
				/* translators: %s: translation added */
				sprintf( __( '%s order(s) processed.', 'woocommerce-jetpack' ), sanitize_text_field( wp_unslash( $_GET['wcj_max_products_per_user_calculate_data_finished'] ) ) );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			}
		}

		/**
		 * Calculate_data.
		 *
		 * @version 7.1.4
		 * @since   3.5.0
		 * @todo    reset `wcj_max_products_per_user_report` and `wcj_max_products_per_user_saved` meta
		 */
		public function calculate_data() {
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_max_products_per_user_calculate_data'] ) ) {
				$offset       = 0;
				$block_size   = 512;
				$total_orders = 0;
				while ( true ) {
					if ( true === wcj_is_hpos_enabled() ) {
						$args  = array(
							'type'           => 'shop_order',
							'status'         => $this->order_status,
							'posts_per_page' => $block_size,
							'orderby'        => 'ID',
							'order'          => 'DESC',
							'offset'         => $offset,
							'fields'         => 'ids',
						);
						$order = wc_get_orders( $args );
						if ( ! $order ) {
							break;
						}
						$i = 0;
						foreach ( $order as $order_id ) {
							$this->save_quantities( $order[ $i ]->id );
							$total_orders++;
							$i++;
						}
					} else {
						$args = array(
							'post_type'      => 'shop_order',
							'post_status'    => $this->order_status,
							'posts_per_page' => $block_size,
							'orderby'        => 'ID',
							'order'          => 'DESC',
							'offset'         => $offset,
							'fields'         => 'ids',
						);
						$loop = new WP_Query( $args );
						if ( ! $loop->have_posts() ) {
							break;
						}
						foreach ( $loop->posts as $_order_id ) {
							$this->save_quantities( $_order_id );
							$total_orders++;
						}
					}
					$offset += $block_size;
				}
				wp_safe_redirect(
					add_query_arg(
						'wcj_max_products_per_user_calculate_data_finished',
						$total_orders,
						remove_query_arg( 'wcj_max_products_per_user_calculate_data' )
					)
				);
				exit;
			}
		}

		/**
		 * Add_report_meta_box.
		 *
		 * @version 3.5.0
		 * @since   3.5.0
		 */
		public function add_report_meta_box() {
			add_meta_box(
				'wc-jetpack-' . $this->id . '-report',
				__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Maximum Products per User: Sales Data', 'woocommerce-jetpack' ),
				array( $this, 'create_report_meta_box' ),
				'product',
				'normal',
				'high'
			);
		}

		/**
		 * Create_report_meta_box.
		 *
		 * @version 3.5.0
		 * @since   3.5.0
		 */
		public function create_report_meta_box() {
			$users_quantities = get_post_meta( get_the_ID(), '_wcj_max_products_per_user_report', true );
			if ( $users_quantities ) {
				$table_data   = array();
				$table_data[] = array( __( 'User ID', 'woocommerce-jetpack' ), __( 'User Name', 'woocommerce-jetpack' ), __( 'Qty Bought', 'woocommerce-jetpack' ) );
				foreach ( $users_quantities as $user_id => $qty_bought ) {
					if ( 0 === $user_id ) {
						$user = __( 'Guest', 'woocommerce-jetpack' );
					} else {
						$user = get_user_by( 'id', $user_id );
						$user = ( isset( $user->data->user_nicename ) ? $user->data->user_nicename : '-' );
					}
					$table_data[] = array( $user_id, $user, $qty_bought );
				}
				echo wp_kses_post(
					wcj_get_table_html(
						$table_data,
						array(
							'table_class'        => 'widefat striped',
							'table_heading_type' => 'horizontal',
						)
					)
				);
			} else {
				echo '<em>' . wp_kses_post( 'No data yet.', 'woocommerce-jetpack' ) . '</em>';
			}
		}

		/**
		 * Save_quantities.
		 *
		 * @version 7.1.4
		 * @since   3.5.0
		 * @param int $order_id defines the order_id.
		 */
		public function save_quantities( $order_id ) {
			$order = wcj_get_order( $order_id );
			if ( $order && false !== $order ) {
				if ( true === wcj_is_hpos_enabled() ) {
					if ( 'yes' !== $order->get_meta( '_wcj_max_products_per_user_saved' ) ) {
						if ( count( $order->get_items() ) > 0 ) {
							$user_id = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->customer_user : $order->get_customer_id() );
							foreach ( $order->get_items() as $item ) {
								$product = $item->get_product();
								if ( $item->is_type( 'line_item' ) && ( $product ) ) {
									$product_id       = wcj_get_product_id_or_variation_parent_id( $product );
									$product_qty      = $item->get_quantity();
									$users_quantities = get_post_meta( $product_id, '_wcj_max_products_per_user_report', true );
									if ( '' === ( $users_quantities ) ) {
										$users_quantities = array();
									}
									if ( isset( $users_quantities[ $user_id ] ) ) {
										$product_qty += $users_quantities[ $user_id ];
									}
									$users_quantities[ $user_id ] = $product_qty;
									update_post_meta( $product_id, '_wcj_max_products_per_user_report', $users_quantities );
								}
							}
						}
						$order->update_meta_data( '_wcj_max_products_per_user_saved', 'yes' );
						$order->save();
					}
				} else {

					if ( 'yes' !== get_post_meta( $order_id, '_wcj_max_products_per_user_saved', true ) ) {
						if ( count( $order->get_items() ) > 0 ) {
							$user_id = ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->customer_user : $order->get_customer_id() );
							foreach ( $order->get_items() as $item ) {
								$product = $item->get_product();
								if ( $item->is_type( 'line_item' ) && ( $product ) ) {
									$product_id       = wcj_get_product_id_or_variation_parent_id( $product );
									$product_qty      = $item->get_quantity();
									$users_quantities = get_post_meta( $product_id, '_wcj_max_products_per_user_report', true );
									if ( '' === ( $users_quantities ) ) {
										$users_quantities = array();
									}
									if ( isset( $users_quantities[ $user_id ] ) ) {
										$product_qty += $users_quantities[ $user_id ];
									}
									$users_quantities[ $user_id ] = $product_qty;
									update_post_meta( $product_id, '_wcj_max_products_per_user_report', $users_quantities );
								}
							}
						}
						update_post_meta( $order_id, '_wcj_max_products_per_user_saved', 'yes' );
					}
				}
			}
		}

		/**
		 * Get_max_qty.
		 *
		 * @version 7.0.0
		 * @since   3.5.0
		 * @todo    (maybe) local - add "enabled/disabled" option
		 * @todo    (maybe) global - apply only to selected products (i.e. include/exclude products, cats, tags)
		 * @todo    (maybe) per user and/or per user role (both global and local)
		 * @param int $product_id defines the product_id.
		 */
		public function get_max_qty( $product_id ) {
			$qty = get_post_meta( $product_id, '_wcj_max_products_per_user_qty', true );
			if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_max_products_per_user_local_enabled', 'no' ) ) && 0 !== ( $qty ) && '0' !== ( $qty ) && '' !== ( $qty ) ) {
				return $qty;
			} elseif ( 'yes' === wcj_get_option( 'wcj_max_products_per_user_global_enabled', 'no' ) ) {
				return wcj_get_option( 'wcj_max_products_per_user_global_max_qty', 1 );
			} else {
				return 0;
			}
		}

		/**
		 * Stop_from_seeing_checkout.
		 *
		 * @version 3.5.0
		 * @since   3.5.0
		 */
		public function stop_from_seeing_checkout() {
			if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
				return;
			}
			if ( ! $this->check_quantities( false ) ) {
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}

		/**
		 * Check_cart_quantities.
		 *
		 * @version 3.5.0
		 * @since   3.5.0
		 */
		public function check_cart_quantities() {
			$this->check_quantities();
		}

		/**
		 * Check_quantities.
		 *
		 * @version 6.0.1
		 * @since   3.5.0
		 * @todo    [dev] recheck `$cart_item_quantity` (maybe should be calculated same as `$currently_in_cart` in `$this->validate_on_add_to_cart()`)
		 * @param bool $add_notices defines the add_notices.
		 */
		public function check_quantities( $add_notices = true ) {
			$result = true;
			if ( ! isset( WC()->cart ) ) {
				return $result;
			}
			$current_user_id = wcj_get_current_user_id();
			if ( 0 === ( $current_user_id ) ) {
				return $result;
			}
			$cart_item_quantities = WC()->cart->get_cart_item_quantities();
			if ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) ) {
				return $result;
			}
			$is_cart = ( function_exists( 'is_cart' ) && is_cart() );
			foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
				$max_qty = $this->get_max_qty( $_product_id );
				if ( 0 === ( $max_qty ) ) {
					continue;
				}
				$user_already_bought = 0;
				$users_quantities    = get_post_meta( $_product_id, '_wcj_max_products_per_user_report', true );
				if ( ( $users_quantities ) && isset( $users_quantities[ $current_user_id ] ) ) {
					$user_already_bought = $users_quantities[ $current_user_id ];
				}
				if ( ( $user_already_bought + $cart_item_quantity ) > $max_qty ) {
					if ( $add_notices ) {
						$result          = false;
						$product         = wc_get_product( $_product_id );
						$replaced_values = array(
							'%max_qty%'            => $max_qty,
							'%product_title%'      => $product->get_title(),
							'%qty_already_bought%' => $user_already_bought,
							'%remaining_qty%'      => max( ( (int) $max_qty - $user_already_bought ), 0 ),
						);
						$message         = wcj_get_option(
							'wcj_max_products_per_user_message',
							__( 'You can only buy maximum %max_qty% pcs. of %product_title% (you already bought %qty_already_bought% pcs.).', 'woocommerce-jetpack' )
						);
						$message         = str_replace( array_keys( $replaced_values ), $replaced_values, $message );
						if ( $is_cart ) {
							wc_print_notice( $message, 'notice' );
						} else {
							wc_add_notice( $message, 'error' );
						}
					} else {
						return false;
					}
				}
			}
			return $result;
		}

	}

endif;

return new WCJ_Max_Products_Per_User();

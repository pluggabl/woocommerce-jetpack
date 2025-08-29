<?php
/**
 * Booster for WooCommerce - Module - Pre Orders Lite
 *
 * @version 7.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Preorders_Lite' ) ) :
	/**
	 * WCJ_Preorders_Lite.
	 *
	 * @version 1.0.0
	 */
	class WCJ_Preorders_Lite extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 1.0.0
		 */
		public function __construct() {
			$this->id         = 'preorders_lite';
			$this->short_desc = __( 'Pre-Orders (Lite)', 'woocommerce-jetpack' );
			$this->desc       = __( 'Basic pre-order functionality for a limited number of products.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-preorders-lite';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( $this->is_elite_active_and_preorders_enabled() ) {
					add_action( 'admin_notices', array( $this, 'elite_active_notice' ) );
					return; // Exit early if Elite is handling pre-orders.
				}

				// Meta boxes.
				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

				add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'modify_add_to_cart_text' ), 10, 2 );
				add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'modify_add_to_cart_text' ), 10, 2 );
				add_filter( 'woocommerce_product_variable_add_to_cart_text', array( $this, 'modify_add_to_cart_text' ), 10, 2 );
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'display_preorder_message' ) );

				// Stock management.
				add_filter( 'woocommerce_product_is_purchasable', array( $this, 'make_preorder_purchasable' ), 10, 2 );
				add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'make_preorder_purchasable' ), 10, 2 );

				// Order processing.
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_preorder' ), 10, 3 );

				add_action( 'admin_notices', array( $this, 'limit_exceeded_notice' ) );

				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			}
		}

		/**
		 * Check if Elite is active and Pre-Orders module is enabled.
		 *
		 * @version 1.0.0
		 * @return bool
		 */
		private function is_elite_active_and_preorders_enabled() {
			return class_exists( 'WCJ_Preorders' ) && wcj_is_module_enabled( 'preorders' );
		}

		/**
		 * Display admin notice when Elite is active.
		 *
		 * @version 1.0.0
		 */
		public function elite_active_notice() {
			if ( isset( $_GET['page'] ) && 'wcj-modules' === $_GET['page'] && isset( $_GET['wcj-cat'] ) && 'shipping_and_orders' === $_GET['wcj-cat'] ) {
				echo '<div class="notice notice-info"><p>' .
					esc_html__( 'Pre-Orders are managed by Booster Elite; Lite output disabled.', 'woocommerce-jetpack' ) .
					'</p></div>';
			}
		}

		/**
		 * Check if pre-order is enabled for product.
		 *
		 * @version 1.0.0
		 * @param int $product_id Product ID.
		 * @return bool
		 */
		private function is_preorder_enabled( $product_id ) {
			return 'yes' === get_post_meta( $product_id, '_wcj_product_preorder_enabled', true );
		}

		/**
		 * Get count of products with pre-orders enabled.
		 *
		 * @version 1.0.0
		 * @return int
		 */
		private function get_enabled_preorder_count() {
			$args = array(
				'post_type'      => 'product',
				'meta_query'     => array(
					array(
						'key'   => '_wcj_product_preorder_enabled',
						'value' => 'yes',
					),
				),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);
			$products = get_posts( $args );
			return count( $products );
		}

		/**
		 * Get maximum allowed pre-order products.
		 *
		 * @version 1.0.0
		 * @return int
		 */
		private function get_max_preorder_limit() {
			return apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_preorders_lite_limit', 1 ) );
		}

		/**
		 * Modify add to cart button text.
		 *
		 * @version 1.0.0
		 * @param string     $text Text for the button.
		 * @param WC_Product $product Product object.
		 * @return string
		 */
		public function modify_add_to_cart_text( $text, $product ) {
			if ( $this->is_preorder_enabled( $product->get_id() ) ) {
				return __( 'Pre-Order Now', 'woocommerce-jetpack' );
			}
			return $text;
		}

		/**
		 * Display pre-order message.
		 *
		 * @version 1.0.0
		 */
		public function display_preorder_message() {
			global $product;
			if ( $product && $this->is_preorder_enabled( $product->get_id() ) ) {
				echo '<p class="wcj-preorder-note">' .
					esc_html__( 'This item is available for pre-order.', 'woocommerce-jetpack' ) .
					'</p>';
			}
		}

		/**
		 * Make pre-order products purchasable even when out of stock.
		 *
		 * @version 1.0.0
		 * @param bool       $purchasable Whether the product is purchasable.
		 * @param WC_Product $product Product object.
		 * @return bool
		 */
		public function make_preorder_purchasable( $purchasable, $product ) {
			if ( $this->is_preorder_enabled( $product->get_id() ) ) {
				return true;
			}
			return $purchasable;
		}

		/**
		 * Process pre-order on checkout.
		 *
		 * @version 1.0.0
		 * @param int      $order_id Order ID.
		 * @param array    $posted_data Posted data.
		 * @param WC_Order $order Order object.
		 */
		public function process_preorder( $order_id, $posted_data, $order ) {
			$has_preorder = false;
			foreach ( $order->get_items() as $item ) {
				if ( $this->is_preorder_enabled( $item->get_product_id() ) ) {
					$has_preorder = true;
					$release_date = get_post_meta( $item->get_product_id(), '_wcj_product_preorder_release_date', true );
					if ( $release_date ) {
						update_post_meta( $order_id, '_wcj_preorder_release_date', $release_date );
					}
					break;
				}
			}

			if ( $has_preorder ) {
				update_post_meta( $order_id, '_wcj_preorder', 'yes' );
				$order->add_order_note( esc_html__( 'This order contains pre-order items.', 'woocommerce-jetpack' ) );
			}
		}

		/**
		 * Add meta box.
		 *
		 * @version 1.0.0
		 */
		public function add_meta_box() {
			add_meta_box(
				'wcj-preorders-lite',
				__( 'Pre-Order (Lite)', 'woocommerce-jetpack' ),
				array( $this, 'create_meta_box' ),
				'product',
				'normal',
				'high'
			);
		}

		/**
		 * Create meta box content.
		 *
		 * @version 1.0.0
		 */
		public function create_meta_box() {
			$current_post_id = get_the_ID();
			$is_enabled      = $this->is_preorder_enabled( $current_post_id );
			$release_date    = get_post_meta( $current_post_id, '_wcj_product_preorder_release_date', true );

			echo '<table class="widefat striped">';
			echo '<tr>';
			echo '<th>' . esc_html__( 'Enable pre-order for this product', 'woocommerce-jetpack' ) . '</th>';
			echo '<td>';
			echo '<input type="checkbox" name="_wcj_product_preorder_enabled" value="yes"' . checked( $is_enabled, true, false ) . '>';
			echo '</td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th>' . esc_html__( 'Release date (optional)', 'woocommerce-jetpack' ) . '</th>';
			echo '<td>';
			echo '<input type="date" name="_wcj_product_preorder_release_date" value="' . esc_attr( $release_date ) . '">';
			echo '<p class="description">' . esc_html__( 'Store only - not displayed on frontend in Lite version.', 'woocommerce-jetpack' ) . '</p>';
			echo '</td>';
			echo '</tr>';

			echo '<tr>';
			echo '<td colspan="2">';
			echo '<p><em>' . sprintf(
				esc_html__( 'More options available in %s', 'woocommerce-jetpack' ),
				'<a href="https://booster.io/buy-booster/" target="_blank">Booster Elite</a>'
			) . '</em></p>';
			echo '</td>';
			echo '</tr>';
			echo '</table>';

			wp_nonce_field( 'wcj_preorders_lite_save_meta_box', 'wcj_preorders_lite_meta_box_nonce' );
		}

		/**
		 * Save meta box.
		 *
		 * @version 1.0.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post Post object.
		 */
		public function save_meta_box( $post_id, $post ) {
			if ( ! isset( $_POST['wcj_preorders_lite_meta_box_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['wcj_preorders_lite_meta_box_nonce'] ), 'wcj_preorders_lite_save_meta_box' ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$is_enabled  = isset( $_POST['_wcj_product_preorder_enabled'] ) ? 'yes' : 'no';
			$was_enabled = $this->is_preorder_enabled( $post_id );

			if ( 'yes' === $is_enabled && ! $was_enabled ) {
				$current_count = $this->get_enabled_preorder_count();
				$max_limit     = $this->get_max_preorder_limit();

				if ( $current_count >= $max_limit ) {
					set_transient( 'wcj_preorders_lite_limit_exceeded', true, 30 );
					return; // Don't save.
				}
			}

			update_post_meta( $post_id, '_wcj_product_preorder_enabled', $is_enabled );

			if ( isset( $_POST['_wcj_product_preorder_release_date'] ) ) {
				update_post_meta( $post_id, '_wcj_product_preorder_release_date', sanitize_text_field( wp_unslash( $_POST['_wcj_product_preorder_release_date'] ) ) );
			}
		}

		/**
		 * Display limit exceeded notice.
		 *
		 * @version 1.0.0
		 */
		public function limit_exceeded_notice() {
			if ( get_transient( 'wcj_preorders_lite_limit_exceeded' ) ) {
				delete_transient( 'wcj_preorders_lite_limit_exceeded' );
				echo '<div class="notice notice-error"><p>' .
					sprintf(
						esc_html__( 'Pre-order limit reached. Upgrade to %s for unlimited products.', 'woocommerce-jetpack' ),
						'<a href="https://booster.io/buy-booster/" target="_blank">Booster Elite</a>'
					) .
					'</p></div>';
			}
		}

		/**
		 * Enqueue styles.
		 *
		 * @version 1.0.0
		 */
		public function enqueue_styles() {
			wp_enqueue_style( 'wcj-preorders-lite', wcj_plugin_url() . '/includes/css/wcj-preorders-lite.css', array(), w_c_j()->version );
		}
	}

endif;

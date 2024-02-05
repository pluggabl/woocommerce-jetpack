<?php
/**
 * Booster for WooCommerce - Module - Product Input Fields
 *
 * @version 7.1.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_Input_Fields' ) ) :
	/**
	 * WCJ_Product_Input_Fields.
	 */
	class WCJ_Product_Input_Fields extends WCJ_Module {

		/**
		 * The module scope
		 *
		 * @var varchar $scope Module scope.
		 */
		public $scope;
		/**
		 * The module global_product_fields
		 *
		 * @var varchar $global_product_fields Module global_product_fields.
		 */
		public $global_product_fields;
		/**
		 * The module local_product_fields
		 *
		 * @var varchar $local_product_fields Module local_product_fields.
		 */
		public $local_product_fields;
		/**
		 * Constructor.
		 *
		 * @version 5.6.1
		 * @todo    (maybe) option to change local and global fields order (i.e. output local fields before the global)
		 */
		public function __construct() {

			$this->id         = 'product_input_fields';
			$this->short_desc = __( 'Product Input Fields', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add input fields to the products (1 input field allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add input fields to the products.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-input-fields';
			$this->extra_desc = sprintf(
				/* translators: %s: translation added */
				__( 'After setting Product Input Fields, you can use below shortcode with meta_key to display the Product Input Fields value: %s', 'woocommerce-jetpack' ),
				'<ol>' .
				'<li>' . sprintf(
					/* translators: %s: translation added */
					__( '<strong>Shortcodes:</strong> %s', 'woocommerce-jetpack' ),
					'<code>[wcj_order_items_meta meta_key = "_wcj_product_input_fields_global_&lt;field_id&gt;"]</code>, <code>[wcj_order_items_meta meta_key = "_wcj_product_input_fields_local_&lt;field_id&gt;"]</code><br>field_id is the Id of your Product Input Field'
				) .
				'</li>' .
				'<li>' . sprintf(
					/* translators: %s: translation added */
					__( '<strong>PHP code:</strong> by using %1$s function,<br> e.g.: %2$s', 'woocommerce-jetpack' ),
					'<code>do_shortcode()</code>',
					'<code>echo&nbsp;do_shortcode(&nbsp;\'[wcj_order_items_meta meta_key = "_wcj_product_input_fields_global_1]\'&nbsp;);</code>'
				) .
				'</li>' .
				'</ol>'
			);
			parent::__construct();

			require_once 'input-fields/class-wcj-product-input-fields-core.php';

			if ( $this->is_enabled() ) {

				add_action( 'woocommerce_delete_order_items', array( $this, 'delete_file_uploads' ) );

				add_action( 'init', array( $this, 'handle_downloads' ) );

				$this->global_product_fields = new WCJ_Product_Input_Fields_Core( 'global' );
				$this->local_product_fields  = new WCJ_Product_Input_Fields_Core( 'local' );

				if ( 'yes' === wcj_get_option( 'wcj_product_input_fields_global_enabled', 'no' ) || 'yes' === wcj_get_option( 'wcj_product_input_fields_local_enabled', 'no' ) ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
					add_action( 'init', array( $this, 'register_scripts' ) );
				}

				add_action( 'wp_head', array( $this, 'preserve_linebreaks_frontend' ) );
				add_action( 'admin_head', array( $this, 'preserve_linebreaks_admin' ) );
			}
		}

		/**
		 * Preserve_linebreaks_admin.
		 *
		 * @version 4.6.0
		 * @since   4.5.0
		 */
		public function preserve_linebreaks_admin() {
			if ( 'yes' !== wcj_get_option( 'wcj_product_input_fields_admin_linebreaks', 'no' ) ) {
				return;
			}
			?>
		<style>
			#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items table.display_meta tr td, #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items table.meta tr td {
				white-space: pre-wrap;
			}
		</style>
			<?php
		}

		/**
		 * Preserve_linebreaks_frontend.
		 *
		 * @version 4.6.0
		 * @since   4.5.0
		 */
		public function preserve_linebreaks_frontend() {
			if ( 'yes' !== wcj_get_option( 'wcj_product_input_fields_frontend_linebreaks', 'no' ) ) {
				return;
			}
			?>
		<style>
			.woocommerce-cart-form__cart-item.cart_item .product-name dl dd,
			.woocommerce-checkout-review-order-table .product-name dl dd {
				white-space: pre-wrap !important;
			}

			.woocommerce-cart-form__cart-item.cart_item .product-name dt,
			.woocommerce-checkout-review-order-table .product-name dt {
				display: block;
			}
		</style>
			<?php
		}

		/**
		 * Get_global_product_fields_options.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 */
		public function get_global_product_fields_options() {
			$this->scope = 'global';
			$return      = require 'input-fields/wcj-product-input-fields-options.php';
			unset( $this->scope );
			return $return;
		}

		/**
		 * Delete_file_uploads.
		 *
		 * @version 2.2.2
		 * @since   2.2.2
		 * @param int $postid defines the postid.
		 */
		public function delete_file_uploads( $postid ) {
			$the_order = wc_get_order( $postid );
			$the_items = $the_order->get_items();
			foreach ( $the_items as $item ) {
				foreach ( $item as $item_field ) {
					$item_field = maybe_unserialize( $item_field );
					if ( is_array( $item_field ) && isset( $item_field['wcj_type'] ) && 'file' === $item_field['wcj_type'] ) {
						unlink( $item_field['tmp_name'] );
					}
				}
			}
		}

		/**
		 * Handle_downloads.
		 *
		 * @version 5.6.7
		 * @since   2.2.2
		 */
		public function handle_downloads() {
			if ( isset( $_GET['wcj_download_file'] ) ) {
				$wcj_download_file_nonce = isset( $_GET['wcj_download_file_nonce'] ) ? sanitize_key( wp_unslash( $_GET['wcj_download_file_nonce'] ) ) : '';
				$wpnonce                 = wp_verify_nonce( $wcj_download_file_nonce, 'wcj_download_file_nonce' );
				if ( ! $wpnonce ) {
					wp_safe_redirect( admin_url() );
					return;
				}

				$file_name  = sanitize_file_name( wp_unslash( $_GET['wcj_download_file'] ) );
				$upload_dir = wcj_get_wcj_uploads_dir( 'input_fields_uploads' );
				$file_path  = $upload_dir . '/' . $file_name;

				if ( wcj_is_user_role( 'administrator' ) || is_shop_manager() ) {
					$real_file_path = realpath( $file_path );
					$real_base_path = realpath( $upload_dir ) . DIRECTORY_SEPARATOR;

					if ( false === $real_file_path || 0 !== strpos( $real_file_path, $real_base_path ) ) {
						wp_safe_redirect( admin_url() );
						return; // Traversal attempt.
					}

					WC_Download_Handler::download_file_force( $file_path, $file_name );
					exit();
				}
			}
		}

		/**
		 * Register_script.
		 *
		 * @version 2.9.0
		 */
		public function register_scripts() {
			wp_register_script( 'wcj-product-input-fields', wcj_plugin_url() . '/includes/js/wcj-product-input-fields.js', array( 'jquery' ), w_c_j()->version, true );
		}

		/**
		 * Enqueue_checkout_script.
		 */
		public function enqueue_scripts() {
			if ( ! is_product() ) {
				return;
			}
			wp_enqueue_script( 'wcj-product-input-fields' );
		}

	}

endif;

return new WCJ_Product_Input_Fields();

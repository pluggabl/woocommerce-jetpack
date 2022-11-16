<?php
/**
 * Booster for WooCommerce Add to Cart per Product
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Add_To_Cart_Per_Product' ) ) :
		/**
		 * WCJ_Add_To_Cart_Per_Product.
		 */
	class WCJ_Add_To_Cart_Per_Product {

		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_per_product_enabled' ) ) {
				add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_button_text_single' ), PHP_INT_MAX );
				add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_button_text_archive' ), PHP_INT_MAX );
				add_action( 'add_meta_boxes', array( $this, 'add_custom_add_to_cart_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_custom_add_to_cart_meta_box' ), 100, 2 );
			}
		}

		/**
		 * Change_add_to_cart_button_text_single.
		 *
		 * @param string $add_to_cart_text Add to cart button text change.
		 */
		public function change_add_to_cart_button_text_single( $add_to_cart_text ) {
			return $this->change_add_to_cart_button_text( $add_to_cart_text, 'single' );
		}

		/**
		 * Change_add_to_cart_button_text_archive.
		 *
		 * @param string $add_to_cart_text Add to cart button text change.
		 */
		public function change_add_to_cart_button_text_archive( $add_to_cart_text ) {
			return $this->change_add_to_cart_button_text( $add_to_cart_text, 'archive' );
		}

		/**
		 * Change_add_to_cart_button_text.
		 *
		 * @param string $add_to_cart_text Add to cart button text change.
		 * @param string $single_or_archive Get single or archive product.
		 *
		 * @version 2.7.0
		 */
		public function change_add_to_cart_button_text( $add_to_cart_text, $single_or_archive ) {
			global $product;
			if ( ! $product ) {
				return $add_to_cart_text;
			}
			$local_custom_add_to_cart_option_id    = 'wcj_custom_add_to_cart_local_' . $single_or_archive;
			$local_custom_add_to_cart_option_value = get_post_meta( wcj_get_product_id_or_variation_parent_id( $product ), '_' . $local_custom_add_to_cart_option_id, true );
			if ( '' !== $local_custom_add_to_cart_option_value ) {
				return $local_custom_add_to_cart_option_value;
			}
			return $add_to_cart_text;
		}

		/**
		 * Save_custom_add_to_cart_meta_box.
		 *
		 * @version 5.6.8
		 *
		 * @param int   $post_id Get post Id.
		 * @param Array $post Get post.
		 */
		public function save_custom_add_to_cart_meta_box( $post_id, $post ) {
			$wpnonce = isset( $_POST['woocommerce_meta_nonce'] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) : false;
			// Check that we are saving with custom add to cart metabox displayed.
			if ( ! $wpnonce || ! isset( $_POST['woojetpack_custom_add_to_cart_save_post'] ) ) {
				return;
			}
			$option_name = 'wcj_custom_add_to_cart_local_single';
			! empty( update_post_meta( $post_id, '_' . $option_name, sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) ) ) );
			$option_name = 'wcj_custom_add_to_cart_local_archive';
			update_post_meta( $post_id, '_' . $option_name, sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) ) );
		}

		/**
		 * Add_custom_add_to_cart_meta_box.
		 *
		 * @version 2.4.8
		 */
		public function add_custom_add_to_cart_meta_box() {
			add_meta_box(
				'wc-jetpack-custom-add-to-cart',
				__( 'Booster: Custom Add to Cart', 'woocommerce-jetpack' ),
				array( $this, 'create_custom_add_to_cart_meta_box' ),
				'product',
				'normal',
				'high'
			);
		}

		/**
		 * Create_custom_add_to_cart_meta_box.
		 *
		 * @version 5.6.2
		 */
		public function create_custom_add_to_cart_meta_box() {

			$current_post_id = get_the_ID();

			$options = array(
				'single'  => __( 'Single product view', 'woocommerce-jetpack' ),
				'archive' => __( 'Product category (archive) view', 'woocommerce-jetpack' ),
			);

			$html = '<table style="width:50%;min-width:300px;">';
			foreach ( $options as $option_key => $option_desc ) {
				$option_type = 'textarea';
				if ( 'url' === $option_key ) {
					$option_type = 'text';
				}
				$html .= '<tr>';
				$html .= '<th>' . $option_desc . '</th>';

				$option_id    = 'wcj_custom_add_to_cart_local_' . $option_key;
				$option_value = get_post_meta( $current_post_id, '_' . $option_id, true );

				if ( 'textarea' === $option_type ) {
					$html .= '<td style="width:80%;">';
				} else {
					$html .= '<td>';
				}

				$html .= '<textarea style="width:100%;" id="' . $option_id . '" name="' . $option_id . '">' . $option_value . '</textarea>';

				$html .= '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
			$html .= '<input type="hidden" name="woojetpack_custom_add_to_cart_save_post" value="woojetpack_custom_add_to_cart_save_post">';
			echo wp_kses_post( $html );
		}
	}

endif;

return new WCJ_Add_To_Cart_Per_Product();

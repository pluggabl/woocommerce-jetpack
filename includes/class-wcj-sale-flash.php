<?php
/**
 * Booster for WooCommerce - Module - Sale Flash
 *
 * @version 6.0.1
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Sale_Flash' ) ) :
	/**
	 * WCJ_Sale_Flash.
	 */
	class WCJ_Sale_Flash extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   3.2.4
		 * @todo    add predefined styles
		 * @todo    (maybe) per product/category/tag: separate "loop" and "single" options
		 * @todo    (maybe) related / homepage
		 */
		public function __construct() {

			$this->id         = 'sale_flash';
			$this->short_desc = __( 'Sale Flash', 'woocommerce-jetpack' );
			$this->desc       = __( 'Customize products sale flash. Per product (Plus); Per category (Plus); Per tag (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Customize products sale flash.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-sale-flash';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$this->globally_enabled     = ( 'yes' === wcj_get_option( 'wcj_product_images_sale_flash_enabled', 'no' ) );
				$this->per_product_enabled  = ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sale_flash_per_product_enabled', 'no' ) ) );
				$this->per_category_enabled = ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sale_flash_per_product_cat_enabled', 'no' ) ) );
				$this->per_tag_enabled      = ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sale_flash_per_product_tag_enabled', 'no' ) ) );
				if ( $this->per_category_enabled ) {
					$this->sale_flash_per_taxonomy['product_cat']['terms'] = wcj_get_option( 'wcj_sale_flash_per_product_cat_terms', array() );
					$this->sale_flash_per_taxonomy['product_cat']['html']  = wcj_get_option( 'wcj_sale_flash_per_product_cat', array() );
				}
				if ( $this->per_tag_enabled ) {
					$this->sale_flash_per_taxonomy['product_tag']['terms'] = wcj_get_option( 'wcj_sale_flash_per_product_tag_terms', array() );
					$this->sale_flash_per_taxonomy['product_tag']['html']  = wcj_get_option( 'wcj_sale_flash_per_product_tag', array() );
				}
				add_filter( 'woocommerce_sale_flash', array( $this, 'customize_sale_flash' ), PHP_INT_MAX, 3 );
				if ( $this->per_product_enabled ) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}
			}
		}

		/**
		 * Get_deprecated_options.
		 *
		 * @version 4.0.0
		 * @since   4.0.0
		 */
		public function get_deprecated_options() {
			$deprecated_options           = array();
			$product_terms['product_cat'] = wcj_get_terms( 'product_cat' );
			$product_terms['product_tag'] = wcj_get_terms( 'product_tag' );
			foreach ( $product_terms as $id => $_product_terms ) {
				foreach ( $_product_terms as $term_id => $term_desc ) {
					$deprecated_options[ 'wcj_sale_flash_per_' . $id ][ $term_id ] = 'wcj_sale_flash_per_' . $id . '_' . $term_id . '_html';
				}
			}
			return $deprecated_options;
		}

		/**
		 * Get_taxonomy_sale_flash.
		 *
		 * @version 6.0.1
		 * @param int    $product_id defines the product_id.
		 * @param string $taxonomy defines the taxonomy.
		 */
		public function get_taxonomy_sale_flash( $product_id, $taxonomy ) {
			$product_terms = get_the_terms( $product_id, $taxonomy );
			if ( ! empty( $product_terms ) && isset( $product_terms[0]->term_id ) ) {
				$term_id = $product_terms[0]->term_id;
				if ( in_array( (string) $term_id, $this->sale_flash_per_taxonomy[ $taxonomy ]['terms'], true ) ) {
					return ( isset( $this->sale_flash_per_taxonomy[ $taxonomy ]['html'][ $term_id ] ) ?
					do_shortcode( $this->sale_flash_per_taxonomy[ $taxonomy ]['html'][ $term_id ] ) :
					'<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>'
					);
				}
			}
			return false;
		}

		/**
		 * Customize_sale_flash.
		 *
		 * @version 3.2.4
		 * @param string | array $sale_flash_html defines the sale_flash_html.
		 * @param string | array $post defines the post.
		 * @param array          $product defines the product.
		 */
		public function customize_sale_flash( $sale_flash_html, $post, $product ) {
			$product_id = wcj_get_product_id_or_variation_parent_id( $product );
			if ( $this->per_product_enabled && 'yes' === get_post_meta( $product_id, '_wcj_sale_flash_enabled', true ) ) {
				return do_shortcode( get_post_meta( $product_id, '_wcj_sale_flash', true ) );
			} elseif ( $this->per_category_enabled ) {
				$sale_flash = $this->get_taxonomy_sale_flash( $product_id, 'product_cat' );
				if ( false !== ( $sale_flash ) ) {
					return $sale_flash;
				}
			} elseif ( $this->per_tag_enabled ) {
				$sale_flash = $this->get_taxonomy_sale_flash( $product_id, 'product_tag' );
				if ( false !== ( $sale_flash ) ) {
					return $sale_flash;
				}
			} elseif ( $this->globally_enabled ) {
				// Hiding.
				if ( 'yes' === wcj_get_option( 'wcj_product_images_sale_flash_hide_everywhere', 'no' ) ) {
					return '';
				}
				if ( 'yes' === wcj_get_option( 'wcj_product_images_sale_flash_hide_on_archives', 'no' ) && is_archive() ) {
					return '';
				}
				if ( 'yes' === wcj_get_option( 'wcj_product_images_sale_flash_hide_on_single', 'no' ) && is_single() && get_the_ID() === $product_id ) {
					return '';
				}
				// Content.
				return do_shortcode( wcj_get_option( 'wcj_product_images_sale_flash_html', '<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>' ) );
			}
			return $sale_flash_html;
		}

	}

endif;

return new WCJ_Sale_Flash();

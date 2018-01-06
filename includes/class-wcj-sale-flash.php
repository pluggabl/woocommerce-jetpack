<?php
/**
 * Booster for WooCommerce - Module - Sale Flash
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Sale_Flash' ) ) :

class WCJ_Sale_Flash extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @todo    add predefined styles
	 * @todo    (maybe) per product/category/tag: separate "loop" and "single" options
	 * @todo    (maybe) related / homepage
	 */
	function __construct() {

		$this->id         = 'sale_flash';
		$this->short_desc = __( 'Sale Flash', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce products sale flash.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-sale-flash';
		parent::__construct();

		if ( $this->is_enabled() ) {
			$this->globally_enabled     = ( 'yes' === get_option( 'wcj_product_images_sale_flash_enabled', 'no' ) );
			$this->per_product_enabled  = ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_sale_flash_per_product_enabled', 'no' ) ) );
			$this->per_category_enabled = ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_sale_flash_per_' . 'product_cat' . '_enabled', 'no' ) ) );
			$this->per_tag_enabled      = ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_sale_flash_per_' . 'product_tag' . '_enabled', 'no' ) ) );
			add_filter( 'woocommerce_sale_flash', array( $this, 'customize_sale_flash' ), PHP_INT_MAX, 3 );
			if ( $this->per_product_enabled ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * get_taxonomy_sale_flash.
	 *
	 * @version 3.2.4
	 */
	function get_taxonomy_sale_flash( $product_id, $taxonomy ) {
		$product_terms = get_the_terms( $product_id, $taxonomy );
		return ( ! empty( $product_terms ) && isset( $product_terms[0]->term_id ) ?
			 do_shortcode( get_option( 'wcj_sale_flash_per_' . $taxonomy . '_' . $product_terms[0]->term_id . '_html',
				'<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>' ) ) :
			false
		);
	}

	/**
	 * customize_sale_flash.
	 *
	 * @version 3.2.4
	 */
	function customize_sale_flash( $sale_flash_html, $post, $product ) {
		$product_id = wcj_get_product_id_or_variation_parent_id( $product );
		if ( $this->per_product_enabled && 'yes' === get_post_meta( $product_id, '_' . 'wcj_sale_flash_enabled', true ) ) {
			return do_shortcode( get_post_meta( $product_id, '_' . 'wcj_sale_flash', true ) );
		} elseif ( $this->per_category_enabled ) {
			if ( false !== ( $sale_flash = $this->get_taxonomy_sale_flash( $product_id, 'product_cat' ) ) ) {
				return $sale_flash;
			}
		} elseif ( $this->per_tag_enabled ) {
			if ( false !== ( $sale_flash = $this->get_taxonomy_sale_flash( $product_id, 'product_tag' ) ) ) {
				return $sale_flash;
			}
		} elseif ( $this->globally_enabled ) {
			// Hiding
			if ( 'yes' === get_option( 'wcj_product_images_sale_flash_hide_everywhere', 'no' ) ) {
				return '';
			}
			if ( 'yes' === get_option( 'wcj_product_images_sale_flash_hide_on_archives', 'no' ) && is_archive() ) {
				return '';
			}
			if ( 'yes' === get_option( 'wcj_product_images_sale_flash_hide_on_single', 'no' )   && is_single() && get_the_ID() === $product_id ) {
				return '';
			}
			// Content
			return do_shortcode( get_option( 'wcj_product_images_sale_flash_html', '<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>' ) );
		}
		return $sale_flash_html;
	}

}

endif;

return new WCJ_Sale_Flash();

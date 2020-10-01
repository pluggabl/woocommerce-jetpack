<?php
/**
 * Booster for WooCommerce - Module - Product Images
 *
 * @version 5.1.0
 * @since   2.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Images' ) ) :

class WCJ_Product_Images extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.3.0
	 * @todo    add watermarks to images (http://php.net/manual/en/image.examples-watermark.php); Filter: `wp_get_attachment_image_src`.
	 */
	function __construct() {

		$this->id         = 'product_images';
		$this->short_desc = __( 'Product Images', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize products images and thumbnails.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-images';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Single
			if ( 'yes' === wcj_get_option( 'wcj_product_images_and_thumbnails_hide_on_single', 'no' ) ) {
				add_action( 'init', array( $this, 'product_images_and_thumbnails_hide_on_single' ), PHP_INT_MAX );
			} else {
				if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
					add_filter( 'woocommerce_single_product_image_html',           array( $this, 'customize_single_product_image_html' ), PHP_INT_MAX, 2 ); // filter doesn't exist in WC3
					add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'customize_single_product_image_thumbnail_html' ) );
				} else {
					add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'customize_single_product_image_or_thumbnail_html_wc3' ), PHP_INT_MAX, 2 );
				}
			}

			// Archives
			add_action( 'woocommerce_before_shop_loop_item',       array( $this, 'product_images_hide_on_archive' ) );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'customize_archive_product_image_html' ), 10 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'hide_per_product_image_on_archives_start' ), 1 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'hide_per_product_image_on_archives_end' ), PHP_INT_MAX );

			// Single Product Thumbnails Columns Number
			add_filter( 'woocommerce_product_thumbnails_columns', array( $this, 'change_product_thumbnails_columns_number' ), PHP_INT_MAX );

			// Per product options
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			// Placeholder image
			if ( '' != ( $this->product_images_placeholder_src = wcj_get_option( 'wcj_product_images_placeholder_src', '' ) ) ) {
				add_filter( 'woocommerce_placeholder_img_src', array( $this, 'placeholder_img_src' ), PHP_INT_MAX );
			}

			// Get callbacks from database
			add_filter( 'wcj_product_images_loop_product_thumbnail_filter', array( $this, 'get_callback_and_priority' ) );
			add_filter( 'wcj_product_images_cb_loop_product_thumbnail_priority', array( $this, 'get_callback_and_priority' ) );
			add_filter( 'wcj_product_images_single_product_images_filter', array( $this, 'get_callback_and_priority' ) );
			add_filter( 'wcj_product_images_single_product_images_filter_priority', array( $this, 'get_callback_and_priority' ) );
		}
	}

	/**
	 * Gets callbacks and priorities from database.
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	function get_callback_and_priority( $value ) {
		switch ( current_filter() ) {
			case 'wcj_product_images_loop_product_thumbnail_filter':
				$value = wcj_get_option( 'wcj_product_images_cb_loop_product_thumbnail', 'woocommerce_template_loop_product_thumbnail' );
				break;
			case 'wcj_product_images_loop_product_thumbnail_filter_priority':
				$value = wcj_get_option( 'wcj_product_images_cb_loop_product_thumbnail_priority', 10 );
				break;
			case 'wcj_product_images_single_product_images_filter':
				$value = wcj_get_option( 'wcj_product_images_cb_show_product_images', 'woocommerce_show_product_images' );
				break;
			case 'wcj_product_images_single_product_images_filter_priority':
				$value = wcj_get_option( 'wcj_product_images_cb_show_product_images_priority', 30 );
				break;
		}
		return $value;
	}

	/**
	 * placeholder_img_src.
	 *
	 * @version 3.9.0
	 * @since   3.9.0
	 * @todo    maybe add options to set different placeholder image per product category and/or product tag
	 */
	function placeholder_img_src( $src ) {
		return $this->product_images_placeholder_src;
	}

	/**
	 * hide_per_product_image_on_archives_start.
	 *
	 * @version 4.1.0
	 * @since   2.5.2
	 */
	function hide_per_product_image_on_archives_start() {
		$post_id = get_the_ID();
		if ( $post_id > 0 && 'yes' === get_post_meta( $post_id, '_' . 'wcj_product_images_hide_image_on_archives', true ) ) {
			remove_action( 'woocommerce_before_shop_loop_item_title',
				apply_filters( 'wcj_product_images_loop_product_thumbnail_filter', 'woocommerce_template_loop_product_thumbnail' ),
				apply_filters( 'wcj_product_images_loop_product_thumbnail_filter_priority', 10 ) );
		}
	}

	/**
	 * hide_per_product_image_on_archives_end.
	 *
	 * @version 4.1.0
	 * @since   2.5.2
	 */
	function hide_per_product_image_on_archives_end() {
		$post_id = get_the_ID();
		if ( $post_id > 0 && 'yes' === get_post_meta( $post_id, '_' . 'wcj_product_images_hide_image_on_archives', true ) ) {
			add_action( 'woocommerce_before_shop_loop_item_title',
				apply_filters( 'wcj_product_images_loop_product_thumbnail_filter', 'woocommerce_template_loop_product_thumbnail' ),
				apply_filters( 'wcj_product_images_loop_product_thumbnail_filter_priority', 10 ) );
		}
	}

	/**
	 * customize_archive_product_image_html.
	 *
	 * @version 2.9.0
	 * @since   2.2.6
	 */
	function customize_archive_product_image_html() {
		$post_id = get_the_ID();
		if ( $post_id > 0 && '' != get_post_meta( $post_id, '_' . 'wcj_product_images_meta_custom_on_archives', true ) ) {
			echo do_shortcode( get_post_meta( $post_id, '_' . 'wcj_product_images_meta_custom_on_archives', true ) );
		} elseif ( '' != wcj_get_option( 'wcj_product_images_custom_on_archives', '' ) ) {
			echo do_shortcode( wcj_get_option( 'wcj_product_images_custom_on_archives' ) );
		}
	}

	/**
	 * product_images_hide_on_archive.
	 *
	 * @version 4.1.0
	 */
	function product_images_hide_on_archive() {
		if (
			'yes' === wcj_get_option( 'wcj_product_images_hide_on_archive', 'no' ) ||
			'' != wcj_get_option( 'wcj_product_images_custom_on_archives', '' ) ||
			( ( $post_id = get_the_ID() ) > 0 && '' != get_post_meta( $post_id, '_' . 'wcj_product_images_meta_custom_on_archives', true ) )
		) {
			remove_action( 'woocommerce_before_shop_loop_item_title',
				apply_filters( 'wcj_product_images_loop_product_thumbnail_filter', 'woocommerce_template_loop_product_thumbnail' ),
				apply_filters( 'wcj_product_images_loop_product_thumbnail_filter_priority', 10 ) );
		} else {
			add_action( 'woocommerce_before_shop_loop_item_title',
				apply_filters( 'wcj_product_images_loop_product_thumbnail_filter', 'woocommerce_template_loop_product_thumbnail' ),
				apply_filters( 'wcj_product_images_loop_product_thumbnail_filter_priority', 10 ) );
		}
	}

	/**
	 * product_images_and_thumbnails_hide_on_single.
	 *
	 * @version 4.1.0
	 */
	function product_images_and_thumbnails_hide_on_single() {
		remove_action( 'woocommerce_before_single_product_summary',
			apply_filters( 'wcj_product_images_single_product_images_filter', 'woocommerce_show_product_images' ),
			apply_filters( 'wcj_product_images_single_product_images_filter_priority', 20 ) );
	}

	/**
	 * customize_single_product_image_html.
	 *
	 * @version 2.9.0
	 */
	function customize_single_product_image_html( $image_link, $post_id ) {
		if ( '' != ( $html_single = get_post_meta( $post_id, '_' . 'wcj_product_images_meta_custom_on_single', true ) ) ) {
			return do_shortcode( $html_single );
		} elseif ( '' != ( $html_global = wcj_get_option( 'wcj_product_images_custom_on_single', '' ) ) ) {
			return do_shortcode( $html_global );
		} elseif ( 'yes' === wcj_get_option( 'wcj_product_images_hide_on_single', 'no' ) ) {
			return '';
		} elseif ( 'yes' === get_post_meta( $post_id, '_' . 'wcj_product_images_hide_image_on_single', true ) ) {
			return '';
		}
		return $image_link;
	}

	/**
	 * customize_single_product_image_thumbnail_html.
	 *
	 * @version 2.9.0
	 */
	function customize_single_product_image_thumbnail_html( $image_link ) {
		$post_id = get_the_ID();
		if ( '' != wcj_get_option( 'wcj_product_images_thumbnails_custom_on_single', '' ) ) {
			return do_shortcode( wcj_get_option( 'wcj_product_images_thumbnails_custom_on_single' ) );
		} elseif ( 'yes' === wcj_get_option( 'wcj_product_images_thumbnails_hide_on_single', 'no' ) ) {
			return '';
		} elseif ( $post_id > 0 && 'yes' === get_post_meta( $post_id, '_' . 'wcj_product_images_hide_thumb_on_single', true ) ) {
			return '';
		}
		return $image_link;
	}

	/**
	 * customize_single_product_image_or_thumbnail_html_wc3.
	 *
	 * @version 5.1.0
	 * @since   2.8.0
	 */
	function customize_single_product_image_or_thumbnail_html_wc3( $html, $attachment_id ) {
		$post_id = get_the_ID();
		return ( (int) get_post_thumbnail_id( $post_id ) === (int) $attachment_id ?
			$this->customize_single_product_image_html( $html, $post_id ) :
			$this->customize_single_product_image_thumbnail_html( $html )
		);
	}

	/**
	 * change_product_thumbnails_columns.
	 */
	function change_product_thumbnails_columns_number( $columns_number ) {
		return wcj_get_option( 'wcj_product_images_thumbnails_columns', 3 );
	}

}

endif;

return new WCJ_Product_Images();

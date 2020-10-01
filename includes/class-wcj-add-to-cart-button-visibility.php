<?php
/**
 * Booster for WooCommerce - Module - Add to Cart Button Visibility
 *
 * @version 3.9.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Add_To_Cart_Button_Visibility' ) ) :

class WCJ_Add_To_Cart_Button_Visibility extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.9.0
	 * @since   3.3.0
	 * @todo    maybe add option to use `woocommerce_before_add_to_cart_form` / `woocommerce_after_add_to_cart_form` instead of `woocommerce_before_add_to_cart_button` / `woocommerce_after_add_to_cart_button`
	 */
	function __construct() {

		$this->id         = 'add_to_cart_button_visibility';
		$this->short_desc = __( 'Add to Cart Button Visibility', 'woocommerce-jetpack' );
		$this->desc       = __( 'Enable/disable Add to Cart button globally or on per product basis.', 'woocommerce-jetpack' );
		$this->extra_desc = '<em>' . sprintf(
			__( 'If you need to enable/disable Add to Cart button for some <strong>user roles</strong> only, we suggest using this module in conjunction with Booster\'s %s module.', 'woocommerce-jetpack' ),
			'<a href="' . wcj_get_module_settings_admin_url( 'modules_by_user_roles' ) . '">' .
				__( 'Modules By User Roles', 'woocommerce-jetpack' ) . '</a>'
		) . '</em>';
		$this->link_slug  = 'woocommerce-add-to-cart-button-visibility';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// All products
			if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_global_enabled', 'no' ) ) {
				// Archives
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_disable_archives', 'no' ) ) {
					if ( 'remove_action' === wcj_get_option( 'wcj_add_to_cart_button_disable_archives_method', 'remove_action' ) ) {
						add_action( 'init',                                  array( $this, 'add_to_cart_button_disable_archives' ), PHP_INT_MAX );
						add_action( 'woocommerce_after_shop_loop_item',      array( $this, 'add_to_cart_button_archives_content' ), 10 );
					} else { // 'add_filter'
						add_filter( 'woocommerce_loop_add_to_cart_link',     array( $this, 'add_to_cart_button_loop_disable_all_products' ), PHP_INT_MAX, 2 );
					}
				}
				// Single Product
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_disable_single', 'no' ) ) {
					if ( 'remove_action' === wcj_get_option( 'wcj_add_to_cart_button_disable_single_method', 'remove_action' ) ) {
						add_action( 'init',                                  array( $this, 'add_to_cart_button_disable_single' ), PHP_INT_MAX );
						add_action( 'woocommerce_single_product_summary',    array( $this, 'add_to_cart_button_single_content' ), 30 );
					} else { // 'add_action'
						add_action( 'woocommerce_before_add_to_cart_button', 'ob_start',                                          PHP_INT_MAX, 0 );
						add_action( 'woocommerce_after_add_to_cart_button',  'ob_end_clean',                                      PHP_INT_MAX, 0 );
						add_action( 'woocommerce_after_add_to_cart_button',  array( $this, 'add_to_cart_button_single_content' ), PHP_INT_MAX, 0 );
					}
				}
			}
			// Per category
			if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_per_category_enabled', 'no' ) ) {
				// Single Product
				add_action( 'woocommerce_before_add_to_cart_button',         array( $this, 'add_to_cart_button_disable_start_per_category' ), PHP_INT_MAX, 0 );
				add_action( 'woocommerce_after_add_to_cart_button',          array( $this, 'add_to_cart_button_disable_end_per_category' ),   PHP_INT_MAX, 0 );
				// Archives
				add_filter( 'woocommerce_loop_add_to_cart_link',             array( $this, 'add_to_cart_button_loop_disable_per_category' ),  PHP_INT_MAX, 2 );
			}
			// Per product
			if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_per_product_enabled', 'no' ) ) {
				// Single Product
				add_action( 'woocommerce_before_add_to_cart_button',         array( $this, 'add_to_cart_button_disable_start' ), PHP_INT_MAX, 0 );
				add_action( 'woocommerce_after_add_to_cart_button',          array( $this, 'add_to_cart_button_disable_end' ),   PHP_INT_MAX, 0 );
				// Archives
				add_filter( 'woocommerce_loop_add_to_cart_link',             array( $this, 'add_to_cart_button_loop_disable' ),  PHP_INT_MAX, 2 );
				// Metaboxes
				add_action( 'add_meta_boxes',                                array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product',                             array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
		}

	}

	/**
	 * add_to_cart_button_loop_disable_all_products.
	 *
	 * @version 3.9.0
	 * @since   3.9.0
	 */
	function add_to_cart_button_loop_disable_all_products( $link, $_product ) {
		return do_shortcode( wcj_get_option( 'wcj_add_to_cart_button_archives_content', '' ) );
	}

	/**
	 * add_to_cart_button_archives_content.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function add_to_cart_button_archives_content() {
		if ( '' != ( $content = wcj_get_option( 'wcj_add_to_cart_button_archives_content', '' ) ) ) {
			echo do_shortcode( $content );
		}
	}

	/**
	 * add_to_cart_button_single_content.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function add_to_cart_button_single_content() {
		if ( '' != ( $content = wcj_get_option( 'wcj_add_to_cart_button_single_content', '' ) ) ) {
			echo do_shortcode( $content );
		}
	}

	/**
	 * add_to_cart_button_disable_end_per_category.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function add_to_cart_button_disable_end_per_category() {
		$cats_to_hide = wcj_get_option( 'wcj_add_to_cart_button_per_category_disable_single', '' );
		if ( ! empty( $cats_to_hide ) && 0 != get_the_ID() && wcj_is_product_term( get_the_ID(), $cats_to_hide, 'product_cat' ) ) {
			ob_end_clean();
			echo do_shortcode( wcj_get_option( 'wcj_add_to_cart_button_per_category_content_single', '' ) );
		}
	}

	/**
	 * add_to_cart_button_disable_start_per_category.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function add_to_cart_button_disable_start_per_category() {
		$cats_to_hide = wcj_get_option( 'wcj_add_to_cart_button_per_category_disable_single', '' );
		if ( ! empty( $cats_to_hide ) && 0 != get_the_ID() && wcj_is_product_term( get_the_ID(), $cats_to_hide, 'product_cat' ) ) {
			ob_start();
		}
	}

	/**
	 * add_to_cart_button_loop_disable_per_category.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function add_to_cart_button_loop_disable_per_category( $link, $_product ) {
		$cats_to_hide = wcj_get_option( 'wcj_add_to_cart_button_per_category_disable_loop', '' );
		if ( ! empty( $cats_to_hide ) && wcj_is_product_term( wcj_get_product_id_or_variation_parent_id( $_product ), $cats_to_hide, 'product_cat' ) ) {
			return do_shortcode( wcj_get_option( 'wcj_add_to_cart_button_per_category_content_loop', '' ) );
		}
		return $link;
	}

	/**
	 * add_to_cart_button_disable_single.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function add_to_cart_button_disable_single() {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}

	/**
	 * add_to_cart_button_disable_archives.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function add_to_cart_button_disable_archives() {
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	}

	/**
	 * add_to_cart_button_loop_disable.
	 *
	 * @version 3.3.0
	 * @since   2.5.2
	 */
	function add_to_cart_button_loop_disable( $link, $_product ) {
		if ( 0 != get_the_ID() && 'yes' === get_post_meta( get_the_ID(), '_' . 'wcj_add_to_cart_button_loop_disable', true ) ) {
			return do_shortcode( get_post_meta( get_the_ID(), '_' . 'wcj_add_to_cart_button_loop_disable_content', true ) );
		}
		return $link;
	}

	/**
	 * add_to_cart_button_disable_end.
	 *
	 * @version 3.3.0
	 * @since   2.5.2
	 */
	function add_to_cart_button_disable_end() {
		if ( 0 != get_the_ID() && 'yes' === get_post_meta( get_the_ID(), '_' . 'wcj_add_to_cart_button_disable', true ) ) {
			ob_end_clean();
			echo do_shortcode( get_post_meta( get_the_ID(), '_' . 'wcj_add_to_cart_button_disable_content', true ) );
		}
	}

	/**
	 * add_to_cart_button_disable_start.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_to_cart_button_disable_start() {
		if ( 0 != get_the_ID() && 'yes' === get_post_meta( get_the_ID(), '_' . 'wcj_add_to_cart_button_disable', true ) ) {
			ob_start();
		}
	}

}

endif;

return new WCJ_Add_To_Cart_Button_Visibility();

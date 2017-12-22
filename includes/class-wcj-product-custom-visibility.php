<?php
/**
 * Booster for WooCommerce - Module - Product Custom Visibility
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Custom_Visibility' ) ) :

class WCJ_Product_Custom_Visibility extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @todo    add "Admin Products List Column"
	 * @todo    add `invisible` ("Visibility Method") ($)
	 * @todo    (maybe) add filters
	 */
	function __construct() {

		$this->id         = 'product_custom_visibility';
		$this->short_desc = __( 'Product Custom Visibility', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products by custom param.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-custom-visibility';
		$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Custom Visibility" meta box to each product\'s edit page.', 'woocommerce-jetpack' ) . '<br>' .
			sprintf(
				__( 'You can add selection drop box to frontend with "%s" widget (set "Product custom visibility" as "Selector Type") or %s shortcode.', 'woocommerce-jetpack' ),
					__( 'Booster - Selector', 'woocommerce-jetpack' ),
					'<code>' . '[wcj_selector selector_type="product_custom_visibility"]' . '</code>' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Product meta box
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			// Core
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( 'yes' === get_option( 'wcj_product_custom_visibility_visibility', 'yes' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'product_custom_visibility_visibility' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_custom_visibility_purchasable', 'no' ) ) {
					add_filter( 'woocommerce_is_purchasable',     array( $this, 'product_custom_visibility_purchasable' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_custom_visibility_query', 'no' ) ) {
					add_action( 'pre_get_posts',                  array( $this, 'product_custom_visibility_pre_get_posts' ) );
				}
				add_action( 'init',                               array( $this, 'save_selection_in_session' ), PHP_INT_MAX ) ;
			}
		}
	}

	/**
	 * product_custom_visibility_pre_get_posts.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function product_custom_visibility_pre_get_posts( $query ) {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'pre_get_posts', array( $this, 'product_custom_visibility_pre_get_posts' ) );
		$selection      = $this->get_selection();
		$post__not_in   = $query->get( 'post__not_in' );
		$args           = $query->query;
		$args['fields'] = 'ids';
		$loop           = new WP_Query( $args );
		foreach ( $loop->posts as $product_id ) {
			if ( ! $this->is_product_visible( $product_id, $selection ) ) {
				$post__not_in[] = $product_id;
			}
		}
		$query->set( 'post__not_in', $post__not_in );
		add_action( 'pre_get_posts', array( $this, 'product_custom_visibility_pre_get_posts' ) );
	}

	/**
	 * product_custom_visibility_purchasable.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function product_custom_visibility_purchasable( $purchasable, $_product ) {
		return ( ! $this->is_product_visible( wcj_get_product_id_or_variation_parent_id( $_product ), $this->get_selection() ) ? false : $purchasable );
	}

	/**
	 * product_custom_visibility_visibility.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function product_custom_visibility_visibility( $visible, $product_id ) {
		return ( ! $this->is_product_visible( $product_id, $this->get_selection() ) ? false : $visible );
	}

	/**
	 * is_product_visible.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function is_product_visible( $product_id, $selection ) {
		$selections = get_post_meta( $product_id, '_' . 'wcj_product_custom_visibility_visible', true );
		if ( ! empty( $selections ) && is_array( $selections ) ) {
			return in_array( $selection, $selections );
		}
		return true;
	}

	/**
	 * save_selection_in_session.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function save_selection_in_session() {
		wcj_session_maybe_start();
		if ( isset( $_REQUEST['wcj_product_custom_visibility_selector'] ) ) {
			wcj_session_set( 'wcj_selected_product_custom_visibility', $_REQUEST['wcj_product_custom_visibility_selector'] );
		}
	}

	/**
	 * get_selection.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function get_selection() {
		return wcj_session_get( 'wcj_selected_product_custom_visibility' );
	}

}

endif;

return new WCJ_Product_Custom_Visibility();

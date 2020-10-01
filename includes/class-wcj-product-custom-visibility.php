<?php
/**
 * Booster for WooCommerce - Module - Product Custom Visibility
 *
 * @version 5.2.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Custom_Visibility' ) ) :

class WCJ_Product_Custom_Visibility extends WCJ_Module_Product_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   3.2.4
	 */
	function __construct() {

		$this->id         = 'product_custom_visibility';
		$this->short_desc = __( 'Product Custom Visibility', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display products by custom param (Bulk actions available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Display products by custom param.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-custom-visibility';
		$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Custom Visibility" meta box to each product\'s edit page.', 'woocommerce-jetpack' ) . '<br>' .
			sprintf(
				__( 'You can add selection drop box to frontend with "%s" widget (set "Product custom visibility" as "Selector Type") or %s shortcode.', 'woocommerce-jetpack' ),
					__( 'Booster - Selector', 'woocommerce-jetpack' ),
					'<code>' . '[wcj_selector selector_type="product_custom_visibility"]' . '</code>' );

		$this->title      = __( 'Custom Visibility', 'woocommerce-jetpack' );

		parent::__construct();

	}

	/**
	 * get_options_list.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_options_list() {
		return wcj_get_select_options( wcj_get_option( 'wcj_product_custom_visibility_options_list', '' ) );
	}

	/**
	 * get_check_option.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_check_option() {
		return wcj_session_get( 'wcj_selected_product_custom_visibility' );
	}

	/**
	 * maybe_add_extra_frontend_filters.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function maybe_add_extra_frontend_filters() {
		add_action( 'init', array( $this, 'save_selection_in_session' ), PHP_INT_MAX );
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
	 * maybe_add_extra_settings.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function maybe_add_extra_settings() {
		return array(
			array(
				'title'    => __( 'Options List', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_custom_visibility_options_list_options',
			),
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'One per line.', 'woocommerce-jetpack' ),
				'desc'     => __( 'Can not be empty. Options will be added to each product\'s admin edit page and to the selection drop box on frontend.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_custom_visibility_options_list',
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'height:200px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_custom_visibility_options_list_options',
			),
		);
	}

}

endif;

return new WCJ_Product_Custom_Visibility();

<?php
/**
 * WooCommerce Jetpack Sorting
 *
 * The WooCommerce Jetpack Sorting class.
 *
 * @version 2.4.8
 * @author  Algoritmika Ltd.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Sorting' ) ) :

class WCJ_Sorting extends WCJ_Module {

	/**
	 * WCJ_Sorting Constructor.
	 *
	 * @access  public
	 * @version 2.4.8
	 */
	public function __construct() {

		$this->id         = 'sorting';
		$this->short_desc = __( 'Sorting', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add more WooCommerce sorting options or remove all sorting including default.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-more-sorting-options/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			if ( 'yes' === get_option( 'wcj_more_sorting_enabled' ) ) {
				add_filter( 'woocommerce_get_catalog_ordering_args',       array( $this, 'custom_woocommerce_get_catalog_ordering_args' ), 100 ); // Sorting
				add_filter( 'woocommerce_catalog_orderby',                 array( $this, 'custom_woocommerce_catalog_orderby' ), 100 ); // Front end
				add_filter( 'woocommerce_default_catalog_orderby_options', array( $this, 'custom_woocommerce_catalog_orderby' ), 100 ); // Back end (default sorting)
			}

			if ( 'yes' === get_option( 'wcj_sorting_remove_all_enabled' ) ) {
				// Remove sorting
				add_action( apply_filters( 'wcj_get_option_filter', 'wcj_empty_action', 'init' ), array( $this, 'remove_sorting' ), 100 );
			}

			// Settings: Add 'Remove All Sorting' checkbox to WooCommerce > Settings > Products
			add_filter( 'woocommerce_product_settings', array( $this, 'add_remove_sorting_checkbox' ), 100 );
		}
	}

	/**
	 * remove_sorting.
	 *
	 * @version 2.2.9
	 */
	public function remove_sorting() {
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'mpcth_before_shop_loop',       'woocommerce_catalog_ordering', 40 ); // Blaszok theme
	}

	/*
	 * Add Remove All Sorting checkbox to WooCommerce > Settings > Products.
	 */
	function add_remove_sorting_checkbox( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'woocommerce_cart_redirect_after_add' == $section['id'] ) {
				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Remove All Sorting', 'woocommerce-jetpack' ),
					'id'       => 'wcj_sorting_remove_all_enabled',
					'type'     => 'checkbox',
					'default'  => 'no',
					'desc'     => __( 'Completely remove sorting from the shop front end', 'woocommerce-jetpack' ),
					'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
					'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	/*
	 * maybe_add_sorting.
	 *
	 * @since 2.2.4
	 */
	private function maybe_add_sorting( $sortby, $option_name, $key ) {
		if ( '' != get_option( $option_name ) ) {
			$sortby[ $key ] = get_option( $option_name );
		}
		return $sortby;
	}

	/*
	 * Add new sorting options to Front End and to Back End (in WooCommerce > Settings > Products > Default Product Sorting).
	 *
	 * @version 2.2.4
	 */
	function custom_woocommerce_catalog_orderby( $sortby ) {
		$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_name_asc_text',            'title_asc' );
		$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_name_desc_text',           'title_desc' );
		$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_sku_asc_text',             'sku_asc' );
		$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_sku_desc_text',            'sku_desc' );
		$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_stock_quantity_asc_text',  'stock_quantity_asc' );
		$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_stock_quantity_desc_text', 'stock_quantity_desc' );
		return $sortby;
	}

	/*
	 * Add new sorting options to WooCommerce sorting.
	 *
	 * @version 2.2.4
	 */
	function custom_woocommerce_get_catalog_ordering_args( $args ) {

		global $woocommerce;
		// Get ordering from query string unless defined
		$orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
		// Get order + orderby args from string
		$orderby_value = explode( '-', $orderby_value );
		$orderby       = esc_attr( $orderby_value[0] );

		switch ( $orderby ) :
			case 'title_asc':
				$args['orderby']  = 'title';
				$args['order']    = 'asc';
				$args['meta_key'] = '';
			break;
			case 'title_desc':
				$args['orderby']  = 'title';
				$args['order']    = 'desc';
				$args['meta_key'] = '';
			break;
			case 'sku_asc':
				$args['orderby']  = ( 'no' === apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_sorting_by_sku_num_enabled', 'no' ) ) ) ? 'meta_value' : 'meta_value_num';
				$args['order']    = 'asc';
				$args['meta_key'] = '_sku';
			break;
			case 'sku_desc':
				$args['orderby']  = ( 'no' === apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_sorting_by_sku_num_enabled', 'no' ) ) ) ? 'meta_value' : 'meta_value_num';
				$args['order']    = 'desc';
				$args['meta_key'] = '_sku';
			break;
			case 'stock_quantity_asc':
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'asc';
				$args['meta_key'] = '_stock';
			break;
			case 'stock_quantity_desc':
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'desc';
				$args['meta_key'] = '_stock';
			break;
		endswitch;

		return $args;
	}

	/*
	 * Add the settings.
	 *
	 * @version 2.4.8
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'     => __( 'Remove All Sorting', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_remove_all_sorting_options',
			),
			array(
				'title'     => __( 'Remove All Sorting', 'woocommerce-jetpack' ),
				'desc'      => __( 'Remove all sorting (including WooCommerce default)', 'woocommerce-jetpack' ),
				'desc_tip'  => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'id'        => 'wcj_sorting_remove_all_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_remove_all_sorting_options',
			),
			array(
				'title'     => __( 'Add More Sorting', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_more_sorting_options',
			),
			array(
				'title'     => __( 'Add More Sorting', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_more_sorting_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Sort by Name', 'woocommerce-jetpack' ),
				'desc'      => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by title: A to Z', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_by_name_asc_text',
				'default'   => __( 'Sort by title: A to Z', 'woocommerce-jetpack' ),
				'type'      => 'text',
				'css'       => 'min-width:300px;',
			),
			array(
				'title'     => '',//__( 'Sort by Name - Desc', 'woocommerce-jetpack' ),
				'desc'      => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by title: Z to A', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_by_name_desc_text',
				'default'   => __( 'Sort by title: Z to A', 'woocommerce-jetpack' ),
				'type'      => 'text',
				'css'       => 'min-width:300px;',
			),
			array(
				'title'     => __( 'Sort by SKU', 'woocommerce-jetpack' ),
				'desc'      => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by SKU: low to high', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_by_sku_asc_text',
				'default'   => __( 'Sort by SKU: low to high', 'woocommerce-jetpack' ),
				'type'      => 'text',
				'css'       => 'min-width:300px;',
			),
			array(
				'title'     => '',//__( 'Sort by SKU - Desc', 'woocommerce-jetpack' ),
				'desc'      => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by SKU: high to low', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_by_sku_desc_text',
				'default'   => __( 'Sort by SKU: high to low', 'woocommerce-jetpack' ),
				'type'      => 'text',
				'css'       => 'min-width:300px;',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Sort SKUs as numbers instead of as texts', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_by_sku_num_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
				'desc_tip'  => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),
			array(
				'title'     => __( 'Sort by stock quantity', 'woocommerce-jetpack' ),
				'desc'      => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by stock quantity: low to high', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_by_stock_quantity_asc_text',
				'default'   => __( 'Sort by stock quantity: low to high', 'woocommerce-jetpack' ),
				'type'      => 'text',
				'css'       => 'min-width:300px;',
			),
			array(
				'title'     => '',//__( 'Sort by stock quantity - Desc', 'woocommerce-jetpack' ),
				'desc'      => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by stock quantity: high to low', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_by_stock_quantity_desc_text',
				'default'   => __( 'Sort by stock quantity: high to low', 'woocommerce-jetpack' ),
				'type'      => 'text',
				'css'       => 'min-width:300px;',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_more_sorting_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}

}

endif;

return new WCJ_Sorting();

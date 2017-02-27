<?php
/**
 * WooCommerce Jetpack Sorting
 *
 * The WooCommerce Jetpack Sorting class.
 *
 * @version 2.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Sorting' ) ) :

class WCJ_Sorting extends WCJ_Module {

	/**
	 * WCJ_Sorting Constructor.
	 *
	 * @access  public
	 * @version 2.6.0
	 */
	function __construct() {

		$this->id         = 'sorting';
		$this->short_desc = __( 'Sorting', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add more WooCommerce sorting options; rename or remove default sorting options; rearrange sorting options on frontend.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-more-sorting-options/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_sorting_remove_all_enabled', 'no' ) ) ) {
				// Remove All Sorting
				add_action( 'wp_loaded',       array( $this, 'remove_sorting' ),          PHP_INT_MAX );
				add_filter( 'wc_get_template', array( $this, 'remove_sorting_template' ), PHP_INT_MAX, 5 );

			} else {

				// Add Custom Sorting
				if ( 'yes' === get_option( 'wcj_more_sorting_enabled', 'yes' ) ) {
					add_filter( 'woocommerce_get_catalog_ordering_args',       array( $this, 'custom_woocommerce_get_catalog_ordering_args' ), PHP_INT_MAX ); // Sorting
					add_filter( 'woocommerce_catalog_orderby',                 array( $this, 'custom_woocommerce_catalog_orderby' ), PHP_INT_MAX ); // Front end
					add_filter( 'woocommerce_default_catalog_orderby_options', array( $this, 'custom_woocommerce_catalog_orderby' ), PHP_INT_MAX ); // Back end (default sorting)
				}

				// Remove or Rename Default Sorting
				if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_sorting_default_sorting_enabled', 'no' ) ) ) {
					add_filter( 'woocommerce_catalog_orderby',                 array( $this, 'remove_default_sortings' ), PHP_INT_MAX );
					add_filter( 'woocommerce_catalog_orderby',                 array( $this, 'rename_default_sortings' ), PHP_INT_MAX );
					add_filter( 'woocommerce_default_catalog_orderby_options', array( $this, 'remove_default_sortings' ), PHP_INT_MAX );
				}

				// Rearrange All Sorting
				if ( 'yes' === get_option( 'wcj_sorting_rearrange_enabled', 'no' ) ) {
					add_filter( 'woocommerce_catalog_orderby',                 array( $this, 'rearrange_sorting' ), PHP_INT_MAX );
					add_filter( 'woocommerce_default_catalog_orderby_options', array( $this, 'rearrange_sorting' ), PHP_INT_MAX );
				}
			}

		}
	}

	/**
	 * remove_sorting_template.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function remove_sorting_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( 'loop/orderby.php' === $template_name ) {
			$located = untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/..' ) ) . '/includes/templates/wcj-empty.php';
		}
		return $located;
	}

	/*
	 * rearrange_sorting.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function rearrange_sorting( $sortby ) {
		$rearranged_sorting = get_option( 'wcj_sorting_rearrange', false );
		if ( false === $rearranged_sorting ) {
			$rearranged_sorting = $this->get_woocommerce_sortings_order();
		} else {
			$rearranged_sorting = explode( PHP_EOL, $rearranged_sorting );
		}
		$rearranged_sortby = array();
		foreach ( $rearranged_sorting as $sorting ) {
			$sorting = str_replace( "\n", '', $sorting );
			$sorting = str_replace( "\r", '', $sorting );
			if ( isset( $sortby[ $sorting ] ) ) {
				$rearranged_sortby[ $sorting ] = $sortby[ $sorting ];
				unset( $sortby[ $sorting ] );
			}
		}
		return array_merge( $rearranged_sortby, $sortby );
	}

	/*
	 * remove_default_sortings.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function remove_default_sortings( $sortby ) {
		$default_sortings = $this->get_woocommerce_default_sortings();
		foreach ( $default_sortings as $sorting_key => $sorting_desc ) {
			$option_key = str_replace( '-', '_', $sorting_key );
			if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_sorting_default_sorting_' . $option_key . '_disable', 'no' ) ) ) {
				unset( $sortby[ $sorting_key ] );
			}
		}
		return $sortby;
	}

	/*
	 * rename_default_sortings.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function rename_default_sortings( $sortby ) {
		$default_sortings = $this->get_woocommerce_default_sortings();
		foreach ( $default_sortings as $sorting_key => $sorting_desc ) {
			$option_key = str_replace( '-', '_', $sorting_key );
			if ( isset( $sortby[ $sorting_key ] ) ) {
				$sortby[ $sorting_key ] = apply_filters( 'booster_get_option', $sorting_desc, get_option( 'wcj_sorting_default_sorting_' . $option_key, $sorting_desc ) );
			}
		}
		return $sortby;
	}

	/**
	 * get_woocommerce_sortings_order.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function get_woocommerce_sortings_order() {
		return array(
			'menu_order',
			'popularity',
			'rating',
			'date',
			'price',
			'price-desc',
			'title_asc',
			'title_desc',
			'sku_asc',
			'sku_desc',
			'stock_quantity_asc',
			'stock_quantity_desc',
		);
	}

	/**
	 * get_woocommerce_default_sortings.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function get_woocommerce_default_sortings() {
		return array(
			'menu_order' => __( 'Default sorting', 'woocommerce' ),
			'popularity' => __( 'Sort by popularity', 'woocommerce' ),
			'rating'     => __( 'Sort by average rating', 'woocommerce' ),
			'date'       => __( 'Sort by newness', 'woocommerce' ),
			'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
			'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
		);
	}

	/**
	 * remove_sorting.
	 *
	 * @version 2.6.0
	 */
	function remove_sorting() {
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'mpcth_before_shop_loop',       'woocommerce_catalog_ordering', 40 ); // Blaszok theme
		remove_action( 'woocommerce_after_shop_loop',  'woocommerce_catalog_ordering', 10 ); // Storefront
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 ); // Storefront
	}

	/*
	 * maybe_add_sorting.
	 *
	 * @version 2.2.4
	 * @since   2.2.4
	 */
	function maybe_add_sorting( $sortby, $option_name, $key ) {
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
	 * @version 2.5.6
	 */
	function custom_woocommerce_get_catalog_ordering_args( $args ) {

		// Get ordering from query string
		$orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
		// Get orderby arg from string
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
				$args['orderby']  = ( 'no' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_sorting_by_sku_num_enabled', 'no' ) ) ) ? 'meta_value' : 'meta_value_num';
				$args['order']    = 'asc';
				$args['meta_key'] = '_sku';
			break;
			case 'sku_desc':
				$args['orderby']  = ( 'no' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_sorting_by_sku_num_enabled', 'no' ) ) ) ? 'meta_value' : 'meta_value_num';
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
	 * @version 2.6.0
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'     => __( 'Add Custom Sorting', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_more_sorting_options',
			),
			array(
				'title'     => __( 'Add More Sorting', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable Section', 'woocommerce-jetpack' ),
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
				'desc_tip'  => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
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
			array(
				'title'     => __( 'Rearrange Sorting', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_sorting_rearrange_options',
			),
			array(
				'title'     => __( 'Rearrange Sorting', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable Section', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_rearrange_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Rearrange Sorting', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_rearrange',
				'desc_tip'  => __( 'Default:', 'woocommerce-jetpack' ) . '<br>' . implode( '<br>', $this->get_woocommerce_sortings_order() ),
				'default'   => implode( PHP_EOL, $this->get_woocommerce_sortings_order() ),
				'type'      => 'textarea',
				'css'       => 'min-height:300px;',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_sorting_rearrange_options',
			),
			array(
				'title'     => __( 'Default WooCommerce Sorting', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_sorting_default_sorting_options',
			),
			array(
				'title'     => __( 'Default Sorting Options', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable Section', 'woocommerce-jetpack' ),
				'id'        => 'wcj_sorting_default_sorting_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
				'desc_tip'  => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
			),
		);
		foreach ( $this->get_woocommerce_default_sortings() as $sorting_key => $sorting_desc ) {
			$option_key = str_replace( '-', '_', $sorting_key );
			$settings[] = array(
				'title'     => $sorting_desc,
				'id'        => 'wcj_sorting_default_sorting_' . $option_key,
				'default'   => $sorting_desc,
				'type'      => 'text',
				'css'       => 'min-width:300px;',
			);
			if ( 'menu_order' === $sorting_key ) {
				continue;
			}
			$settings[] = array(
				'desc'      => __( 'Remove', 'woocommerce-jetpack' ) . ' "' . $sorting_desc . '"',
				'id'        => 'wcj_sorting_default_sorting_' . $option_key . '_disable',
				'default'   => 'no',
				'type'      => 'checkbox',
			);
		}
		$settings = array_merge( $settings, array(
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_sorting_default_sorting_options',
			),
			array(
				'title'     => __( 'Remove All Sorting', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_sorting_remove_all_options',
			),
			array(
				'title'     => __( 'Remove All Sorting', 'woocommerce-jetpack' ),
				'desc'      => __( 'Remove all sorting (including WooCommerce default) from shop\'s frontend', 'woocommerce-jetpack' ),
				'desc_tip'  => apply_filters( 'booster_get_message', '', 'desc' ),
				'id'        => 'wcj_sorting_remove_all_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_sorting_remove_all_options',
			),
		) );
		return $this->add_standard_settings( $settings );
	}

}

endif;

return new WCJ_Sorting();

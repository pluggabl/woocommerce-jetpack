<?php
/**
 * WooCommerce Jetpack Product Custom Info
 *
 * The WooCommerce Jetpack Product Custom Info class.
 *
 * @version 2.4.0
 * @since   2.4.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Custom_info' ) ) :

class WCJ_Product_Custom_info extends WCJ_Module {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id         = 'product_custom_info';
		$this->short_desc = __( 'Product Info V2', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add additional info to WooCommerce category and single product pages.', 'woocommerce-jetpack' );
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
			$single_or_archive_array = array( 'single', 'archive' );
			foreach ( $single_or_archive_array as $single_or_archive ) {
				for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_custom_info_total_number_' . $single_or_archive, 1 ) ); $i++ ) {
					$default_hook = ( 'single' === $single_or_archive ) ? 'woocommerce_after_single_product_summary' : 'woocommerce_after_shop_loop_item_title';
					add_action(
						get_option( 'wcj_product_custom_info_hook_' . $single_or_archive . '_' . $i, $default_hook ),
						array( $this, 'add_product_custom_info' )
					);
				}
			}
		}
	}

	/**
	 * add_product_custom_info.
	 */
	function add_product_custom_info() {
		$current_filter = current_filter();
		$single_or_archive_array = array( 'single', 'archive' );
		foreach ( $single_or_archive_array as $single_or_archive ) {
			for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_custom_info_total_number_' . $single_or_archive, 1 ) ); $i++ ) {
				if ( '' != get_option( 'wcj_product_custom_info_content_' . $single_or_archive . '_' . $i ) && $current_filter === get_option( 'wcj_product_custom_info_hook_' . $single_or_archive . '_' . $i ) ) {
						$products_to_exclude = get_option( 'wcj_product_custom_info_products_to_exclude_' . $single_or_archive . '_' . $i );
						$products_to_include = get_option( 'wcj_product_custom_info_products_to_include_' . $single_or_archive . '_' . $i );
						$product_id = get_the_ID();
						if (
							( empty( $products_to_exclude ) || ! in_array( $product_id, $products_to_exclude ) ) &&
							( empty( $products_to_include ) || in_array( $product_id, $products_to_include ) )
						) {
							echo do_shortcode( get_option( 'wcj_product_custom_info_content_' . $single_or_archive . '_' . $i ) );
						}
				}
			}
		}
	}

	/**
	 * get_settings.
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_product_custom_info_settings', $settings );
		return $this->add_standard_settings( $settings );
	}

	/*
	 * add_settings_hook.
	 */
	function add_settings_hook() {
		add_filter( 'wcj_product_custom_info_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * add_settings.
	 */
	function add_settings() {

		$products = array();
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		);
		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) : $loop->the_post();
				$products[ strval( $loop->post->ID ) ] = get_the_title( $loop->post->ID );
			endwhile;
			wp_reset_postdata();
		}

		$settings = array();
		$single_or_archive_array = array( 'single', 'archive' );
		foreach ( $single_or_archive_array as $single_or_archive ) {
			$single_or_archive_desc = ( 'single' === $single_or_archive ) ? __( 'Single', 'woocommerce-jetpack' ) : __( 'Archive', 'woocommerce-jetpack' );
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Product Custom Info Blocks', 'woocommerce-jetpack' ) . ' - ' . $single_or_archive_desc,
					'type'     => 'title',
					'id'       => 'wcj_product_custom_info_options_' . $single_or_archive,
				),
				array(
					'title'    => __( 'Total Blocks', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_custom_info_total_number_' . $single_or_archive,
					'default'  => 1,
					'type'     => 'custom_number',
					'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
					'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				),
				array(
					'type'     => 'sectionend',
					'id'       => 'wcj_product_custom_info_options_' . $single_or_archive,
				),
			) );
			for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_custom_info_total_number_' . $single_or_archive, 1 ) ); $i++ ) {
				$settings = array_merge( $settings, array(
					array(
						'title'    => __( 'Info Block', 'woocommerce-jetpack' ) . ' #' . $i . ' - ' . $single_or_archive_desc,
						'type'     => 'title',
						'id'       => 'wcj_product_custom_info_options_' . $single_or_archive . '_' . $i,
					),
					array(
						'title'    => __( 'Content', 'woocommerce-jetpack' ),
						'id'       => 'wcj_product_custom_info_content_' . $single_or_archive . '_' . $i,
						'default'  => '[wcj_product_total_sales before="Total sales: " after=" pcs."]',
						'type'     => 'textarea',
						'css'      => 'width:30%;min-width:300px;height:100px;',
					),
					array(
						'title'    => __( 'Position', 'woocommerce-jetpack' ),
						'id'       => 'wcj_product_custom_info_hook_' . $single_or_archive . '_' . $i,
						'default'  => ( 'single' === $single_or_archive ) ? 'woocommerce_after_single_product_summary' : 'woocommerce_after_shop_loop_item_title',
						'type'     => 'select',
						'options'  => ( 'single' === $single_or_archive ) ?
							array(
								'woocommerce_before_single_product'         => __( 'Before single product', 'woocommerce-jetpack' ),
								'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
								'woocommerce_single_product_summary'        => __( 'Inside single product summary', 'woocommerce-jetpack' ),
								'woocommerce_after_single_product_summary'  => __( 'After single product summary', 'woocommerce-jetpack' ),
								'woocommerce_after_single_product'          => __( 'After single product', 'woocommerce-jetpack' ),
							) :
							array(
								'woocommerce_before_shop_loop_item'       => __( 'Before product', 'woocommerce-jetpack' ),
								'woocommerce_before_shop_loop_item_title' => __( 'Before product title', 'woocommerce-jetpack' ),
								'woocommerce_shop_loop_item_title'        => __( 'Inside product title', 'woocommerce-jetpack' ),
								'woocommerce_after_shop_loop_item_title'  => __( 'After product title', 'woocommerce-jetpack' ),
								'woocommerce_after_shop_loop_item'        => __( 'After product', 'woocommerce-jetpack' ),
							),
						'css'      => 'width:250px;',
					),
					array(
						'title'    => __( 'Priority', 'woocommerce-jetpack' ),
						'id'       => 'wcj_product_custom_info_priority_' . $single_or_archive . '_' . $i,
						'default'  => 10,
						'type'     => 'number',
						'css'      => 'width:250px;',
					),
					array(
						'title'    => __( 'Products to Include', 'woocommerce-jetpack' ),
						'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
						'id'       => 'wcj_product_custom_info_products_to_include_' . $single_or_archive . '_' . $i,
						'default'  => '',
						'type'     => 'multiselect',
						'class'    => 'chosen_select',
						'css'      => 'width: 450px;',
						'options'  => $products,
					),
					array(
						'title'    => __( 'Products to Exclude', 'woocommerce-jetpack' ),
						'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
						'id'       => 'wcj_product_custom_info_products_to_exclude_' . $single_or_archive . '_' . $i,
						'default'  => '',
						'type'     => 'multiselect',
						'class'    => 'chosen_select',
						'css'      => 'width: 450px;',
						'options'  => $products,
					),
					array(
						'type'     => 'sectionend',
						'id'       => 'wcj_product_custom_info_options_' . $single_or_archive . '_' . $i,
					),
				) );
			}
		}

		return $settings;
	}

}

endif;

return new WCJ_Product_Custom_info();

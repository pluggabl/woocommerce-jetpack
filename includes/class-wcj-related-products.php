<?php
/**
 * WooCommerce Jetpack Related Products
 *
 * The WooCommerce Jetpack Related Products class.
 *
 * @version 2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Related_Products' ) ) :

class WCJ_Related_Products extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 */
	public function __construct() {

		$this->id         = 'related_products';
		$this->short_desc = __( 'Related Products', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change displayed WooCommerce related products number, columns, order, relate by tag and/or category, or hide related products completely.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-related-products/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_filter( 'woocommerce_related_products_args', array( $this, 'related_products_args' ), PHP_INT_MAX );
			add_filter( 'woocommerce_output_related_products_args', array( $this, 'output_related_products_args' ), PHP_INT_MAX );

			if ( 'no' === get_option( 'wcj_product_info_related_products_relate_by_category' ) ) {
				add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_false', PHP_INT_MAX );
			} else {
				add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_true',  PHP_INT_MAX );
			}

			if ( 'no' === get_option( 'wcj_product_info_related_products_relate_by_tag' ) ) {
				add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_false', PHP_INT_MAX );
			} else {
				add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_true',  PHP_INT_MAX );
			}

			add_action( 'woojetpack_after_settings_save', array( $this, 'delete_product_transients' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * delete_product_transients.
	 *
	 * @since 2.2.6
	 */
	function delete_product_transients( $sections, $current_section ) {
		if ( 'related_products' === $current_section ) {
			wc_delete_product_transients();
		}
	}

	/**
	 * Change number of related products on product page.
	 *
	 * @version 2.2.6
	 */
	function related_products_args( $args ) {
		if ( 'yes' === get_option( 'wcj_product_info_related_products_hide' ) ) {
			return array();
		}
		$args['posts_per_page'] = get_option( 'wcj_product_info_related_products_num' );
		$args['orderby'] = get_option( 'wcj_product_info_related_products_orderby' );
		if ( get_option( 'wcj_product_info_related_products_orderby' ) != 'rand' ) {
			$args['order'] = get_option( 'wcj_product_info_related_products_order' );
		}
		return $args;
	}

	/**
	 * Change number of related products on product page.
	 *
	 * @version 2.2.6
	 */
	function output_related_products_args( $args ) {
		$args['columns'] = get_option( 'wcj_product_info_related_products_columns' );
		$args = $this->related_products_args( $args );
		return $args;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcj_product_info_related_products_options',
			),
			array(
				'title'    => __( 'Related Products Number', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_num',
				'default'  => 3,
				'type'     => 'number',
			),
			array(
				'title'    => __( 'Related Products Columns', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_columns',
				'default'  => 3,
				'type'     => 'number',
			),
			array(
				'title'    => __( 'Order by', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_orderby',
				'default'  => 'rand',
				'type'     => 'select',
				'options'  => array(
					'rand'  => __( 'Random', 'woocommerce-jetpack' ),
					'date'  => __( 'Date', 'woocommerce-jetpack' ),
					'title' => __( 'Title', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Order', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Ignored if order by "Random" is selected above.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_order',
				'default'  => 'desc',
				'type'     => 'select',
				'options'  => array(
					'asc'  => __( 'Ascending', 'woocommerce-jetpack' ),
					'desc' => __( 'Descending', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Relate by Category', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_relate_by_category',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Relate by Tag', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_relate_by_tag',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Hide Related Products', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_hide',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_info_related_products_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Related_Products();

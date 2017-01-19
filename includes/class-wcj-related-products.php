<?php
/**
 * WooCommerce Jetpack Related Products
 *
 * The WooCommerce Jetpack Related Products class.
 *
 * @version 2.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Related_Products' ) ) :

class WCJ_Related_Products extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.6.0
	 */
	public function __construct() {

		$this->id         = 'related_products';
		$this->short_desc = __( 'Related Products', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change displayed WooCommerce related products number, columns, order, relate by tag and/or category, or hide related products completely.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-related-products/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Related per Product
			if ( 'yes' === get_option( 'wcj_product_info_related_products_per_product' , 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}

			// Related Args
			add_filter( 'woocommerce_related_products_args', array( $this, 'related_products_args' ), PHP_INT_MAX );
			add_filter( 'woocommerce_output_related_products_args', array( $this, 'output_related_products_args' ), PHP_INT_MAX );

			// Relate by Category
			if ( 'no' === get_option( 'wcj_product_info_related_products_relate_by_category', 'yes' ) ) {
				add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_false', PHP_INT_MAX );
			} else {
				add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_true',  PHP_INT_MAX );
			}

			// Relate by Tag
			if ( 'no' === get_option( 'wcj_product_info_related_products_relate_by_tag', 'yes' ) ) {
				add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_false', PHP_INT_MAX );
			} else {
				add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_true',  PHP_INT_MAX );
			}

			// Delete Transients
			add_action( 'woojetpack_after_settings_save', array( $this, 'delete_product_transients' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function get_meta_box_options() {
		$product_id = get_the_ID();
		$products = wcj_get_products( array(), 'publish' );
		unset( $products[ $product_id  ] );
		$options = array(
			array(
				'name'       => 'wcj_product_info_related_products_ids',
				'default'    => '',
				'type'       => 'select',
				'options'    => $products,
				'title'      => __( 'Related Products', 'woocommerce-jetpack' ),
				'multiple'   => true,
				'tooltip'    => __( 'Hold Control (Ctrl) key to select multiple products.', 'woocommerce-jetpack' ),
			),
		);
		return $options;
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
	 * related_products_args.
	 *
	 * @version 2.6.0
	 * @todo    change related products: there will be issues if $product->get_related() will return empty array
	 */
	function related_products_args( $args ) {
		// Hide Related
		if ( 'yes' === get_option( 'wcj_product_info_related_products_hide', 'no' ) ) {
			return array();
		}
		// Related Num
		$args['posts_per_page'] = get_option( 'wcj_product_info_related_products_num', 3 );
		// Order By
		$orderby = get_option( 'wcj_product_info_related_products_orderby', 'rand' );
		$args['orderby'] = $orderby;
		if ( 'meta_value' === $orderby || 'meta_value_num' === $orderby ) {
			$args['meta_key'] = get_option( 'wcj_product_info_related_products_orderby_meta_value_meta_key', '' );
		}
		// Order
		if ( get_option( 'wcj_product_info_related_products_orderby', 'rand' ) != 'rand' ) {
			$args['order'] = get_option( 'wcj_product_info_related_products_order', 'desc' );
		}
		// Change Related Products
		if ( 'yes' === get_option( 'wcj_product_info_related_products_per_product' , 'yes' ) ) {
			// Relate per Product (Manual)
			$related_per_product = get_post_meta( get_the_ID(), '_' . 'wcj_product_info_related_products_ids', true );
			if ( '' != $related_per_product ) {
				$args['post__in'] = $related_per_product;
			}
		}
		elseif ( 'yes' === get_option( 'wcj_product_info_related_products_by_attribute_enabled', 'no' ) ) {
			// Relate by Global Attributes
			// from http://snippet.fm/snippets/query-for-woocommerce-products-by-global-product-attributes/
			unset( $args['post__in'] );
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'pa_' . get_option( 'wcj_product_info_related_products_by_attribute_attribute_name', '' ),
					'field'    => 'slug',
					'terms'    => get_option( 'wcj_product_info_related_products_by_attribute_attribute_value', '' ),
				),
			);
		}
		return $args;
	}

	/**
	 * output_related_products_args.
	 *
	 * @version 2.6.0
	 */
	function output_related_products_args( $args ) {
		$args['columns'] = get_option( 'wcj_product_info_related_products_columns', 3 );
		$args = $this->related_products_args( $args );
		return $args;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.6.0
	 * @todo    add "Relate by Local (i.e. Product Specific) Product Attribute" - http://snippet.fm/snippets/query-woocommerce-products-product-specific-custom-attribute/
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
					'rand'           => __( 'Random', 'woocommerce-jetpack' ),
					'date'           => __( 'Date', 'woocommerce-jetpack' ),
					'title'          => __( 'Title', 'woocommerce-jetpack' ),
					'meta_value'     => __( 'Meta Value', 'woocommerce-jetpack' ),
					'meta_value_num' => __( 'Meta Value (Numeric)', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Meta Key', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Used only if order by "Meta Value" or "Meta Value (Numeric)" is selected in "Order by".', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_orderby_meta_value_meta_key',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Order', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Ignored if order by "Random" is selected in "Order by".', 'woocommerce-jetpack' ),
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
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_relate_by_category',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Relate by Tag', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_relate_by_tag',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Relate Manually', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This will add metabox to each product\'s edit page.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_per_product',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Relate by Global Product Attribute', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_by_attribute_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => __( 'Attribute Name Slug', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_by_attribute_attribute_name',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Attribute Value Slug', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_by_attribute_attribute_value',
				'default'  => '',
				'type'     => 'text',
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

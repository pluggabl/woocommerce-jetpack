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

if ( ! class_exists( 'WCJ_Dummy_Term' ) ) {
	/**
	 * WCJ_Dummy_Term class.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	class WCJ_Dummy_Term {
		public $term_id;
		function __construct() {
			$this->term_id = 0;
		}
	}
}

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
		$this->desc       = __( 'Change displayed WooCommerce related products number, columns, order; relate by tag, category, product attribute or manually on per product basis. Hide related products completely.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-related-products/';
		parent::__construct();

		// Delete Transients
		add_action( 'admin_init', array( $this, 'maybe_delete_product_transients' ), PHP_INT_MAX, 2 );

		if ( $this->is_enabled() ) {

			// Related per Product
			if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_product_info_related_products_per_product', 'no' ) ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}

			// Related Args
			add_filter( 'woocommerce_related_products_args',        array( $this, 'related_products_args' ), PHP_INT_MAX );
			add_filter( 'woocommerce_output_related_products_args', array( $this, 'output_related_products_args' ), PHP_INT_MAX );
			add_filter( 'woocommerce_related_products_columns',     array( $this, 'related_products_columns' ), PHP_INT_MAX );

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

			// Fix Empty Initial Related Products Issue
			add_filter( 'woocommerce_get_related_product_tag_terms', array( $this, 'fix_empty_initial_related_products' ), PHP_INT_MAX, 2 );

		}
	}

	/**
	 * fix_empty_initial_related_products.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function fix_empty_initial_related_products( $terms, $product_id ) {
		$do_fix = false;
		if ( 'yes' === get_option( 'wcj_product_info_related_products_by_attribute_enabled', 'no' ) ) {
			$do_fix = true;
		} elseif (
			'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_product_info_related_products_per_product', 'no' ) ) &&
			'yes' === get_post_meta( $product_id, '_' . 'wcj_product_info_related_products_enabled', true ) &&
			'' != get_post_meta( $product_id, '_' . 'wcj_product_info_related_products_ids', true )
		) {
			$do_fix = true;
		}
		if ( $do_fix ) {
			add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_false', PHP_INT_MAX );
			add_filter( 'woocommerce_product_related_posts_relate_by_tag',      '__return_false', PHP_INT_MAX );
			if ( empty( $terms ) ) {
				$dummy_term = new WCJ_Dummy_Term();
				$terms[] = $dummy_term;
			}
		}
		return $terms;
	}

	/**
	 * related_products_columns.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function related_products_columns( $columns ) {
		return get_option( 'wcj_product_info_related_products_columns', 3 );
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
				'name'       => 'wcj_product_info_related_products_enabled',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'no'  => __( 'No', 'woocommerce-jetpack' ),
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Enable', 'woocommerce-jetpack' ),
				'tooltip'    => __( 'If enabled and no products selected - will hide related products section on frontend for current product.', 'woocommerce-jetpack' ),
			),
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
	 * maybe_delete_product_transients.
	 *
	 * @since   2.6.0
	 * @version 2.6.0
	 */
	function maybe_delete_product_transients() {
		if ( isset( $_GET['wcj_clear_all_products_transients'] ) ) {
			$offset = 0;
			$block_size = 256;
			while( true ) {
				$args = array(
					'post_type'      => 'product',
					'post_status'    => $post_status,
					'posts_per_page' => $block_size,
					'offset'         => $offset,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
				);
				$loop = new WP_Query( $args );
				if ( ! $loop->have_posts() ) {
					break;
				}
				foreach ( $loop->posts as $post_id ) {
					wc_delete_product_transients( $post_id );
				}
				$offset += $block_size;
			}
			wp_safe_redirect( remove_query_arg( 'wcj_clear_all_products_transients' ) );
			exit;
		}
	}

	/**
	 * related_products_args.
	 *
	 * @version 2.6.0
	 * @todo    save custom results as product transient
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
		if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_product_info_related_products_per_product', 'no' ) ) && 'yes' === get_post_meta( get_the_ID(), '_' . 'wcj_product_info_related_products_enabled', true ) ) {
			// Relate per Product (Manual)
			$related_per_product = get_post_meta( get_the_ID(), '_' . 'wcj_product_info_related_products_ids', true );
			if ( '' != $related_per_product ) {
				$args['post__in'] = $related_per_product;
			} else {
				return array();
			}
		} elseif ( 'yes' === get_option( 'wcj_product_info_related_products_by_attribute_enabled', 'no' ) ) {
			unset( $args['post__in'] );
			$attribute_name   = get_option( 'wcj_product_info_related_products_by_attribute_attribute_name', '' );
			$attribute_value  = get_option( 'wcj_product_info_related_products_by_attribute_attribute_value', '' );
			if ( 'global' === get_option( 'wcj_product_info_related_products_by_attribute_attribute_type', 'global' ) ) {
				// Relate by Global Attributes
				// http://snippet.fm/snippets/query-for-woocommerce-products-by-global-product-attributes/
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'pa_' . $attribute_name,
						'field'    => 'name',
						'terms'    => $attribute_value,
					),
				);
			} else {
				// Relate by Local Product Attributes
				// http://snippet.fm/snippets/query-woocommerce-products-product-specific-custom-attribute/
				$serialized_value = serialize( 'name' ) . serialize( $attribute_name ) . serialize( 'value' ) . serialize( $attribute_value );
				// extended version: $serialized_value = serialize( $attribute_name ) . 'a:6:{' . serialize( 'name' ) . serialize( $attribute_name ) . serialize( 'value' ) . serialize( $attribute_value ) . serialize( 'position' );
				$args['meta_query'] = array(
					array(
						'key'     => '_product_attributes',
						'value'   => $serialized_value,
						'compare' => 'LIKE',
					),
				);
			}
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
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'General', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_info_related_products_general_options',
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
				'type'     => 'sectionend',
				'id'       => 'wcj_product_info_related_products_general_options',
			),
			array(
				'title'    => __( 'Order', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_info_related_products_order_options',
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
				'type'     => 'sectionend',
				'id'       => 'wcj_product_info_related_products_order_options',
			),
			array(
				'title'    => __( 'Relate', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_info_related_products_relate_options',
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
				'title'    => __( 'Relate by Product Attribute', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_by_attribute_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'     => __( 'Attribute Type', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If using "Global Attribute" enter attribute\'s <em>slug</em> in "Attribute Name"', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_by_attribute_attribute_type',
				'default'  => 'global',
				'type'     => 'select',
				'options'  => array(
					'global' => __( 'Global Attribute', 'woocommerce-jetpack' ),
					'local'  => __( 'Local Attribute', 'woocommerce-jetpack' ),
				),
			),
			array(
				'desc'     => __( 'Attribute Name', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_by_attribute_attribute_name',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Attribute Value', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_related_products_by_attribute_attribute_value',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Relate Manually', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This will add metabox to each product\'s edit page.', 'woocommerce-jetpack' ) .
					' ' . __( 'You will be able to select related products manually for each product individually. There is also an option to remove related products on per product basis.', 'woocommerce-jetpack' ) .
					' ' . apply_filters( 'booster_get_message', '', 'desc' ),
				'id'       => 'wcj_product_info_related_products_per_product',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_info_related_products_relate_options',
			),
			array(
				'title'    => __( 'Hide', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_info_related_products_hide_options',
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
				'id'       => 'wcj_product_info_related_products_hide_options',
			),
		);
		return $this->add_standard_settings( $settings, sprintf(
			__( 'You may need to <a class="button" href="%s">clear all products transients</a> to immediately see results on frontend after changing module\'s settings. Alternatively you can just update each product individually to clear its transients.', 'woocommerce-jetpack' ),
			add_query_arg( 'wcj_clear_all_products_transients', 'yes' )
		) );
	}
}

endif;

return new WCJ_Related_Products();

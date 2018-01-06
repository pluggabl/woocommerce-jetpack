<?php
/**
 * Booster for WooCommerce Exporter Products
 *
 * @version 3.0.0
 * @since   2.5.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Exporter_Products' ) ) :

class WCJ_Exporter_Products {

	/**
	 * Constructor.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function __construct() {
		return true;
	}

	/**
	 * get_variable_or_grouped_product_info.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 */
	function get_variable_or_grouped_product_info( $_product, $which_info ) {
		$all_variations_data = array();
		foreach ( $_product->get_children() as $child_id ) {
			$variation = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->get_child( $child_id ) : wc_get_product( $child_id ) );
			switch ( $which_info ) {
				case 'price':
					$all_variations_data[] = ( '' === $variation->get_price() ) ? '-' : $variation->get_price();
					break;
				case 'regular_price':
					$all_variations_data[] = ( '' === $variation->get_regular_price() ) ? '-' : $variation->get_regular_price();
					break;
				case 'sale_price':
					$all_variations_data[] = ( '' === $variation->get_sale_price() ) ? '-' : $variation->get_sale_price();
					break;
				case 'total_stock':
					$all_variations_data[] = ( null === wcj_get_product_total_stock( $variation ) ) ? '-' : wcj_get_product_total_stock( $variation );
					break;
				case 'stock_quantity':
					$all_variations_data[] = ( null === $variation->get_stock_quantity() ) ? '-' : $variation->get_stock_quantity();
					break;
			}
		}
		return implode( '/', $all_variations_data );
	}

	/**
	 * export_products.
	 *
	 * @version 3.0.0
	 * @since   2.5.3
	 * @todo    product attributes
	 */
	function export_products( $fields_helper ) {

		// Standard Fields
		$all_fields = $fields_helper->get_product_export_fields();
		$fields_ids = get_option( 'wcj_export_products_fields', $fields_helper->get_product_export_default_fields_ids() );
		$titles = array();
		foreach( $fields_ids as $field_id ) {
			$titles[] = $all_fields[ $field_id ];
		}

		// Additional Fields
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_export_products_fields_additional_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_export_products_fields_additional_enabled_' . $i, 'no' ) ) {
				$titles[] = get_option( 'wcj_export_products_fields_additional_title_' . $i, '' );
			}
		}

		$data = array();
		$data[] = $titles;
		$offset = 0;
		$block_size = 1024;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$args = wcj_maybe_add_date_query( $args );
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $product_id ) {
				$_product = wc_get_product( $product_id );
				$products = array( $product_id => $_product );
				$parent_product_id = '';
				if ( $_product->is_type( 'variable' ) ) {
					$parent_product_id = $product_id;
					$export_products_variable = get_option( 'wcj_export_products_variable', 'variable_only' );
					if ( 'variations_only' === $export_products_variable || 'variable_and_variations' === $export_products_variable ) {
						if ( 'variations_only' === $export_products_variable ) {
							$products = array();
						}
						foreach ( $_product->get_children() as $child_id ) {
							$products[ $child_id ] = wc_get_product( $child_id );
						}
					}
				}
				foreach ( $products as $product_id => $_product ) {
					$row = array();
					foreach( $fields_ids as $field_id ) {
						switch ( $field_id ) {
							case 'product-id':
								$row[] = $product_id;
								break;
							case 'parent-product-id':
								$row[] = $parent_product_id;
								break;
							case 'product-name':
								$row[] = $_product->get_title();
								break;
							case 'product-sku':
								$row[] = $_product->get_sku();
								break;
							case 'product-stock-quantity':
								$row[] = ( $_product->is_type( 'variable' ) || $_product->is_type( 'grouped' ) ?
									$this->get_variable_or_grouped_product_info( $_product, 'stock_quantity' ) : $_product->get_stock_quantity() );
								break;
							case 'product-stock':
								$row[] = ( $_product->is_type( 'variable' ) || $_product->is_type( 'grouped' ) ?
									$this->get_variable_or_grouped_product_info( $_product, 'total_stock' ) : wcj_get_product_total_stock( $_product ) );
								break;
							case 'product-regular-price':
								$row[] = ( $_product->is_type( 'variable' ) || $_product->is_type( 'grouped' ) ?
									$this->get_variable_or_grouped_product_info( $_product, 'regular_price' ) : $_product->get_regular_price() );
								break;
							case 'product-sale-price':
								$row[] = ( $_product->is_type( 'variable' ) || $_product->is_type( 'grouped' ) ?
									$this->get_variable_or_grouped_product_info( $_product, 'sale_price' ) : $_product->get_sale_price() );
								break;
							case 'product-price':
								$row[] = ( $_product->is_type( 'variable' ) || $_product->is_type( 'grouped' ) ?
									$this->get_variable_or_grouped_product_info( $_product, 'price' ) : $_product->get_price() );
								break;
							case 'product-type':
								$row[] = $_product->get_type();
								break;
							/* case 'product-attributes':
								$row[] = ( ! empty( $_product->get_attributes() ) ? serialize( $_product->get_attributes() ) : '' );
								break; */
							case 'product-image-url':
								$row[] = wcj_get_product_image_url( $product_id, 'full' );
								break;
							case 'product-short-description':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->post->post_excerpt : $_product->get_short_description() );
								break;
							case 'product-description':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->post->post_content : $_product->get_description() );
								break;
							case 'product-status':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->post->post_status : $_product->get_status() );
								break;
							case 'product-url':
								$row[] = $_product->get_permalink();
								break;
							case 'product-shipping-class':
								$row[] = $_product->get_shipping_class();
								break;
							case 'product-shipping-class-id':
								$row[] = $_product->get_shipping_class_id();
								break;
							case 'product-width':
								$row[] = $_product->get_width();
								break;
							case 'product-length':
								$row[] = $_product->get_length();
								break;
							case 'product-height':
								$row[] = $_product->get_height();
								break;
							case 'product-weight':
								$row[] = $_product->get_weight();
								break;
							case 'product-downloadable':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->downloadable : $_product->get_downloadable() );
								break;
							case 'product-virtual':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->virtual : $_product->get_virtual() );
								break;
							case 'product-sold-individually':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->sold_individually : $_product->get_sold_individually() );
								break;
							case 'product-tax-status':
								$row[] = $_product->get_tax_status();
								break;
							case 'product-tax-class':
								$row[] = $_product->get_tax_class();
								break;
							case 'product-manage-stock':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->manage_stock : $_product->get_manage_stock() );
								break;
							case 'product-stock-status':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->stock_status : $_product->get_stock_status() );
								break;
							case 'product-backorders':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->backorders : $_product->get_backorders() );
								break;
							case 'product-featured':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->featured : $_product->get_featured() );
								break;
							case 'product-visibility':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->visibility : $_product->get_catalog_visibility() );
								break;
							case 'product-price-including-tax':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->get_price_including_tax() : wc_get_price_including_tax( $_product ) );
								break;
							case 'product-price-excluding-tax':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->get_price_excluding_tax() : wc_get_price_excluding_tax( $_product ) );
								break;
							case 'product-display-price':
								$row[] = wcj_get_product_display_price( $_product );
								break;
							case 'product-average-rating':
								$row[] = $_product->get_average_rating();
								break;
							case 'product-rating-count':
								$row[] = $_product->get_rating_count();
								break;
							case 'product-review-count':
								$row[] = $_product->get_review_count();
								break;
							case 'product-categories':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->get_categories() : wc_get_product_category_list( $product_id ) );
								break;
							case 'product-tags':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->get_tags() : wc_get_product_tag_list( $product_id ) );
								break;
							case 'product-dimensions':
								$row[] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->get_dimensions() : wc_format_dimensions( $_product->get_dimensions( false ) ) );
								break;
							case 'product-formatted-name':
								$row[] = $_product->get_formatted_name();
								break;
							case 'product-availability':
								$availability = $_product->get_availability();
								$row[] = $availability['availability'];
								break;
							case 'product-availability-class':
								$availability = $_product->get_availability();
								$row[] = $availability['class'];
								break;
						}
					}

					// Additional Fields
					$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_export_products_fields_additional_total_number', 1 ) );
					for ( $i = 1; $i <= $total_number; $i++ ) {
						if ( 'yes' === get_option( 'wcj_export_products_fields_additional_enabled_' . $i, 'no' ) ) {
							if ( '' != ( $additional_field_value = get_option( 'wcj_export_products_fields_additional_value_' . $i, '' ) ) ) {
								if ( 'meta' === get_option( 'wcj_export_products_fields_additional_type_' . $i, 'meta' ) ) {
									$row[] = get_post_meta( $product_id, $additional_field_value, true );
								} else {
									global $post;
									$post = get_post( $product_id );
									setup_postdata( $post );
									$row[] = do_shortcode( $additional_field_value );
									wp_reset_postdata();
								}
							} else {
								$row[] = '';
							}
						}
					}

					$data[] = $row;
				}
			}
			$offset += $block_size;
		}
		return $data;
	}

}

endif;

return new WCJ_Exporter_Products();

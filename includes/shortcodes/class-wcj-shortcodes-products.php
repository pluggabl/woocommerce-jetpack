<?php
/**
 * Booster for WooCommerce - Shortcodes - Products
 *
 * @version 5.3.8
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Products_Shortcodes' ) ) :

class WCJ_Products_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 3.6.1
	 * @todo    (maybe) add `[wcj_product_stock_price]`
	 */
	function __construct() {

		$this->the_shortcodes = array(
			'wcj_product_author',
			'wcj_product_author_avatar',
			'wcj_product_author_link',
			'wcj_product_author_link_all_posts',
			'wcj_product_available_variations',
			'wcj_product_average_rating',
			'wcj_product_barcode',
			'wcj_product_categories',
			'wcj_product_categories_names',
			'wcj_product_categories_urls',
			'wcj_product_custom_field',
			'wcj_product_description',
			'wcj_product_dimensions',
			'wcj_product_excerpt',
			'wcj_product_formatted_name',
			'wcj_product_gallery_image_url',
			'wcj_product_height',
			'wcj_product_id',
			'wcj_product_image',
			'wcj_product_image_url',
			'wcj_product_length',
			'wcj_product_list_attribute',
			'wcj_product_list_attributes',
			'wcj_product_meta',
			'wcj_product_price',
			'wcj_product_price_excluding_tax',
			'wcj_product_price_including_tax',
			'wcj_product_purchase_price', // Product Cost Price
			'wcj_product_purchase_price_without_html_custom', // Product Cost Price
			'wcj_product_regular_price',
			'wcj_product_regular_price_without_html_custom',
			'wcj_product_sale_price',
			'wcj_product_shipping_class',
			'wcj_product_shipping_time_table',
			'wcj_product_short_description',
			'wcj_product_sku',
			'wcj_product_slug',
			'wcj_product_stock_availability',
			'wcj_product_stock_quantity',
			'wcj_product_stock_status',
			'wcj_product_tags',
			'wcj_product_tax_class',
			'wcj_product_time_since_last_sale',
			'wcj_product_title',
			'wcj_product_total_sales',
			'wcj_product_url',
			'wcj_product_weight',
			'wcj_product_wholesale_price_table', // WooCommerce Wholesale Price
			'wcj_product_width',
			'wcj_product_you_save',
			'wcj_product_you_save_percent',
		);

		$this->the_atts = array(
			'product_id'            => 0,
			'image_size'            => 'shop_thumbnail',
			'image_nr'              => 1,
			'multiply_by'           => '',
			'multiply_by_meta'      => '',
			'addition_by'			=>'',
			'subtraction_by'		=>'',
			'division_by'		    =>'',
			'hide_currency'         => 'no',
			'excerpt_length'        => 0, // deprecated
			'length'                => 0,
			'apply_filters'         => 'no',
			'name'                  => '',
			'heading_format'        => 'from %level_min_qty% pcs.',
			'before_level_max_qty'  => '-',
			'last_level_max_qty'    => '+',
			'price_row_format'      => '<del>%old_price%</del> %price%',
			'sep'                   => ', ',
			'add_links'             => 'yes',
			'add_percent_row'       => 'no',
			'add_discount_row'      => 'no',
			'add_price_row'         => 'yes',
			'show_always'           => 'yes',
			'hide_if_zero'          => 'no',
			'reverse'               => 'no',
			'find'                  => '',
			'replace'               => '',
			'offset'                => '',
			'days_to_cover'         => 90,
			'order_status'          => 'wc-completed',
			'hide_if_no_sales'      => 'no',
			'to_unit'               => '',
			'round'                 => 'no',
			'precision'             => 2,
			'hide_if_zero_quantity' => 'no',
			'table_format'          => 'horizontal',
			'avatar_size'           => 96,
			'count_variations'      => 'no',
			'variations'            => 'no',
			'columns_style'         => 'text-align: center;',
			'currency'              => '',
			'code'                  => '',
			'type'                  => '',
			'dimension'             => '2D',
			'width'                 => 0,
			'height'                => 0,
			'color'                 => 'black',
			'meta_key'              => '',
//			'outofstock'            => __( 'Out of stock', 'woocommerce' ),
//			'instock'               => __( 'In stock', 'woocommerce' ),
		);

		parent::__construct();
	}

	/**
	 * Inits shortcode atts and properties.
	 *
	 * @version 3.3.0
	 * @param   array $atts Shortcode atts.
	 * @return  array The (modified) shortcode atts.
	 */
	function init_atts( $atts ) {

		// Atts
		$is_passed_product = false;
		if ( 0 == $atts['product_id'] ) {
			if ( isset( $this->passed_product ) ) {
				$atts['product_id'] = wcj_get_product_id( $this->passed_product );
				$is_passed_product = true;
			} else {
				$atts['product_id'] = get_the_ID();
			}
			if ( 0 == $atts['product_id'] ) {
				return false;
			}
		}
		$the_post_type = get_post_type( $atts['product_id'] );
		if ( 'product' !== $the_post_type && 'product_variation' !== $the_post_type ) {
			return false;
		}

		// Class properties
		$this->the_product = ( $is_passed_product ? $this->passed_product : wc_get_product( $atts['product_id'] ) );
		if ( ! $this->the_product ) {
			return false;
		}

		return $atts;
	}

	/**
	 * wcj_product_slug.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function wcj_product_slug( $atts ) {
		return $this->the_product->get_slug();
	}

	/**
	 * wcj_product_barcode.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function wcj_product_barcode( $atts ) {
		switch ( $atts['code'] ) {
			case '%id%':
				$atts['code'] = $atts['product_id'];
				break;
			case '%sku%':
				$atts['code'] = $this->the_product->get_sku();
				break;
			case '%url%':
				$atts['code'] = $this->the_product->get_permalink();
				break;
			case '%meta%':
				$atts['code'] = get_post_meta( $atts['product_id'], $atts['meta_key'], true );
				break;
			default:
				return '';
		}
		return wcj_barcode( $atts );
	}

	/**
	 * wcj_product_id.
	 *
	 * @version 2.8.1
	 * @since   2.8.1
	 */
	function wcj_product_id( $atts ) {
		return $this->the_product->get_id();
	}

	/**
	 * wcj_product_author_avatar.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function wcj_product_author_avatar( $atts ) {
		return get_avatar( get_the_author_meta( 'ID' ), $atts['avatar_size'] );
	}

	/**
	 * wcj_product_author.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function wcj_product_author( $atts ) {
		return get_the_author();
	}

	/**
	 * wcj_product_author_link.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function wcj_product_author_link( $atts ) {
		global $post;
		return add_query_arg( 'post_type', 'product', get_author_posts_url( $post->post_author ) );
	}

	/**
	 * wcj_product_author_link_all_posts.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function wcj_product_author_link_all_posts( $atts ) {
		global $post;
		return get_author_posts_url( $post->post_author );
	}

	/**
	 * wcj_product_length.
	 *
	 * @version 2.9.0
	 * @since   2.5.5
	 */
	function wcj_product_length( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) && 'yes' === $atts['variations'] ) {
			return $this->get_variations_table( 'length', $atts );
		}
		$return = ( '' != $atts['to_unit'] ) ? wc_get_dimension( $this->the_product->get_length(), $atts['to_unit'] ) : $this->the_product->get_length();
		return ( 'yes' === $atts['round'] ) ? round( $return, $atts['precision'] ) : $return;
	}

	/**
	 * wcj_product_width.
	 *
	 * @version 2.9.0
	 * @since   2.5.5
	 */
	function wcj_product_width( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) && 'yes' === $atts['variations'] ) {
			return $this->get_variations_table( 'width', $atts );
		}
		$return = ( '' != $atts['to_unit'] ) ? wc_get_dimension( $this->the_product->get_width(), $atts['to_unit'] ) : $this->the_product->get_width();
		return ( 'yes' === $atts['round'] ) ? round( $return, $atts['precision'] ) : $return;
	}

	/**
	 * wcj_product_height.
	 *
	 * @version 2.9.0
	 * @since   2.5.5
	 */
	function wcj_product_height( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) && 'yes' === $atts['variations'] ) {
			return $this->get_variations_table( 'height', $atts );
		}
		$return = ( '' != $atts['to_unit'] ) ? wc_get_dimension( $this->the_product->get_height(), $atts['to_unit'] ) : $this->the_product->get_height();
		return ( 'yes' === $atts['round'] ) ? round( $return, $atts['precision'] ) : $return;
	}

	/**
	 * wcj_product_time_since_last_sale.
	 *
	 * @version 4.0.0
	 * @since   2.4.0
	 */
	function wcj_product_time_since_last_sale( $atts ) {
		$offset     = 0;
		$block_size = 512;
		while( true ) {
			// Create args for new query
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => $atts['order_status'],
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'date_query'     => array( 'after' => '-' . $atts['days_to_cover'] . ' days' ),
				'fields'         => 'ids',
			);
			// Run new query
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			// Analyze the results, i.e. orders
			foreach ( $loop->posts as $order_id ) {
				$order = wc_get_order( $order_id );
				$items = $order->get_items();
				foreach ( $items as $item ) {
					// Run through all order's items
					if ( $item['product_id'] == $atts['product_id'] ) {
						// Found sale!
						return sprintf( __( '%s ago', 'woocommerce-jetpack' ), human_time_diff( get_the_time( 'U', $order_id ), current_time( 'timestamp' ) ) );
					}
				}
			}
			$offset += $block_size;
		}
		// No sales found
		return ( 'yes' === $atts['hide_if_no_sales'] ? '' : __( 'No sales yet.', 'woocommerce-jetpack' ) );
	}

	/**
	 * wcj_product_available_variations.
	 *
	 * @version 4.0.0
	 * @since   2.4.0
	 * @todo    [dev] re-check `$attribute_name`
	 */
	function wcj_product_available_variations( $atts ) {
		$return_html = '';
		$param       = ( isset( $atts['param'] ) ? $atts['param'] : 'price_html' );
		if ( $this->the_product->is_type( 'variable' ) ) {
			$return_html .= '<table>';
			foreach ( $this->the_product->get_available_variations() as $variation ) {
				$return_html .= '<tr>';
				foreach ( $variation['attributes'] as $attribute_slug => $attribute_name ) {
					if ( '' == $attribute_name ) {
						$attribute_name = __( 'Any', 'woocommerce-jetpack' );
					}
					$return_html .= '<td>' . $attribute_name . '</td>';
				}
				$return_html .= '<td>' . $variation[ $param ] . '</td>';
				$return_html .= '</tr>';
			}
			$return_html .= '</table>';
		}
		return $return_html;
	}

	/**
	 * wcj_product_price_excluding_tax.
	 *
	 * @version 2.5.7
	 * @since   2.4.0
	 */
	function wcj_product_price_excluding_tax( $atts ) {
		return $this->get_product_price_including_or_excluding_tax( $atts, 'excluding' );
	}

	/**
	 * wcj_product_price_including_tax.
	 *
	 * @version 2.5.7
	 * @since   2.4.0
	 */
	function wcj_product_price_including_tax( $atts ) {
		return $this->get_product_price_including_or_excluding_tax( $atts, 'including' );
	}

	/**
	 * get_product_price_including_or_excluding_tax.
	 *
	 * @version 2.7.1
	 * @since   2.5.7
	 */
	function get_product_price_including_or_excluding_tax( $atts, $including_or_excluding ) {
		if ( $this->the_product->is_type( 'variable' ) ) {
			// Variable
			$prices = $this->the_product->get_variation_prices( false );
			$min_product_id = key( $prices['price'] );
			end( $prices['price'] );
			$max_product_id = key( $prices['price'] );
			if ( 0 != $min_product_id && 0 != $max_product_id ) {
				$min_variation_product = wc_get_product( $min_product_id );
				$max_variation_product = wc_get_product( $max_product_id );
				if ( 'including' === $including_or_excluding ) {
					$min = ( WCJ_IS_WC_VERSION_BELOW_3 ? $min_variation_product->get_price_including_tax() : wc_get_price_including_tax( $min_variation_product ) );
					$max = ( WCJ_IS_WC_VERSION_BELOW_3 ? $max_variation_product->get_price_including_tax() : wc_get_price_including_tax( $max_variation_product ) );
				} else { // 'excluding'
					$min = ( WCJ_IS_WC_VERSION_BELOW_3 ? $min_variation_product->get_price_excluding_tax() : wc_get_price_excluding_tax( $min_variation_product ) );
					$max = ( WCJ_IS_WC_VERSION_BELOW_3 ? $max_variation_product->get_price_excluding_tax() : wc_get_price_excluding_tax( $max_variation_product ) );
				}
				if ( 0 != $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
					$min = $min * $atts['multiply_by'];
					$max = $max * $atts['multiply_by'];
				}
				// For Additon
				if ( 0 != $atts['addition_by'] && is_numeric( $atts['addition_by'] ) ) {
					$min = $min + $atts['addition_by'];
					$max = $max + $atts['addition_by'];
				}
				
				//For Subsaction
				if ( 0 != $atts['subtraction_by'] && is_numeric( $atts['subtraction_by'] ) ) {
					$min = $min - $atts['subtraction_by'];
					$max = $max - $atts['subtraction_by'];
				}

				//For Division
				if ( 0 != $atts['division_by'] && is_numeric( $atts['division_by'] ) ) {
					$min = $min / $atts['division_by'];
					$max = $max / $atts['division_by'];
				}	
				if ( 'yes' !== $atts['hide_currency'] ) {
					$min = wc_price( $min );
					$max = wc_price( $max );
				}
				return ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
			}
		} else {
			 // Simple etc.
			if ( 'including' === $including_or_excluding ) {
				$the_price = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_product->get_price_including_tax() : wc_get_price_including_tax( $this->the_product ) );
			} else { // 'excluding'
				$the_price = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_product->get_price_excluding_tax() : wc_get_price_excluding_tax( $this->the_product ) );
			}
			if ( 0 != $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
				$the_price = $the_price * $atts['multiply_by'];
			}
			if ( 0 != $atts['addition_by'] && is_numeric( $atts['addition_by'] ) ) {
				$the_price = $the_price + $atts['addition_by'];
			}	
			if ( 0 != $atts['subtraction_by'] && is_numeric( $atts['subtraction_by'] ) ) {
				$the_price = $the_price - $atts['subtraction_by'];
			}
			if ( 0 != $atts['division_by'] && is_numeric( $atts['division_by'] ) ) {
				$the_price = $the_price / $atts['division_by'];
			}
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
		}
	}

	/**
	 * wcj_product_regular_price.
	 *
	 * @version 2.8.0
	 * @since   2.4.0
	 */
	function wcj_product_regular_price( $atts ) {
		if ( $this->the_product->is_on_sale() || 'yes' === $atts['show_always'] ) {
			$the_price = $this->the_product->get_regular_price();
			if ( 0 != $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
				$the_price = $the_price * $atts['multiply_by'];
			}
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
		}
		return '';
	}

	/**
	 * wcj_product_regular_price_without_html_custom.
	 *
	 * @version 5.4.0
	 * @since   2.4.1
	 */
	function wcj_product_regular_price_without_html_custom( $atts ) {
		if ( $this->the_product->is_on_sale() || 'yes' === $atts['show_always'] ) {
			$the_price = $this->the_product->get_regular_price();
			if ( 0 != $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
				$the_price = $the_price * $atts['multiply_by'];
			}
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : $the_price;
		}
		return '';
	}


	/**
	 * wcj_product_sale_price.
	 *
	 * @version 2.8.0
	 * @since   2.4.0
	 */
	function wcj_product_sale_price( $atts ) {
		if ( $this->the_product->is_on_sale() ) {
			$the_price = $this->the_product->get_sale_price();
			if ( 0 != $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
				$the_price = $the_price * $atts['multiply_by'];
			}
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
		}
		return '';
	}

	/**
	 * wcj_product_tax_class.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_tax_class( $atts ) {
		return $this->the_product->get_tax_class();
	}

	/**
	 * wcj_product_list_attributes.
	 *
	 * @version 2.7.0
	 * @since   2.4.0
	 */
	function wcj_product_list_attributes( $atts ) {
		if ( $this->the_product->has_attributes() ) {
			ob_start();
			if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
				$this->the_product->list_attributes();
			} else {
				wc_display_product_attributes( $this->the_product );
			}
			return ob_get_clean();
		}
		return '';
	}

	/**
	 * wcj_product_list_attribute.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_list_attribute( $atts ) {
		return str_replace( $atts['find'], $atts['replace'], $this->the_product->get_attribute( $atts['name'] ) );
	}

	/**
	 * wcj_product_stock_quantity.
	 *
	 * @version 2.8.0
	 * @since   2.4.0
	 */
	function wcj_product_stock_quantity( $atts ) {
		$stock_quantity = $this->the_product->get_stock_quantity();
		if ( 'yes' === $atts['count_variations'] && $this->the_product->is_type( 'variable' ) ) {
			foreach ( $this->the_product->get_available_variations() as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );
				$stock_quantity += $variation_product->get_stock_quantity();
			}
		}
		return ( null !== $stock_quantity ) ? $stock_quantity : '';
	}

	/**
	 * wcj_product_average_rating.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_average_rating( $atts ) {
		return $this->the_product->get_average_rating();
	}

	/**
	 * wcj_product_categories.
	 *
	 * @version 2.7.0
	 * @since   2.4.0
	 */
	function wcj_product_categories( $atts ) {
		$return = ( WCJ_IS_WC_VERSION_BELOW_3 ) ? $this->the_product->get_categories() : wc_get_product_category_list( $atts['product_id'] );
		return ( false === $return ) ? '' : $return;
	}

	/**
	 * wcj_product_formatted_name.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_formatted_name( $atts ) {
		return $this->the_product->get_formatted_name();
	}

	/**
	 * wcj_product_stock_status.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function wcj_product_stock_status( $atts ) {
		if ( ! isset( $atts['instock'] ) ) {
			$atts['instock'] = __( 'In stock', 'woocommerce' );
		}
		if ( ! isset( $atts['outofstock'] ) ) {
			$atts['outofstock'] = __( 'Out of stock', 'woocommerce' );
		}
		$stock_status = $this->the_product->get_stock_status();
		return ( isset( $atts[ $stock_status ] ) ? $atts[ $stock_status ] : $stock_status );
	}

	/**
	 * wcj_product_stock_availability.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_stock_availability( $atts ) {
		$stock_availability_array = $this->the_product->get_availability();
		return ( isset( $stock_availability_array['availability'] ) ) ? $stock_availability_array['availability'] : '';
	}

	/**
	 * wcj_product_dimensions.
	 *
	 * @version 2.9.0
	 * @since   2.4.0
	 */
	function wcj_product_dimensions( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) && 'yes' === $atts['variations'] ) {
			return $this->get_variations_table( 'dimensions', $atts );
		}
		return ( $this->the_product->has_dimensions() ) ?
			( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_product->get_dimensions() : wc_format_dimensions( $this->the_product->get_dimensions( false ) ) )
			: '';
	}

	/**
	 * wcj_product_shipping_time_table.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 * @todo    `$atts['shipping_method']` (i.e. `[wcj_product_shipping_time]`)
	 * @todo    explode "from-to"
	 */
	function wcj_product_shipping_time_table( $atts ) {
		$do_use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_time_use_shipping_instance', 'no' ) );
		$do_use_shipping_classes   = ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_shipping_time_use_shipping_classes', 'no' ) ) );
		$option_id_shipping_class  = ( $do_use_shipping_classes ? '_class_' . wcj_get_product_shipping_class_term_id( $this->the_product ) : '' );
		return wcj_get_shipping_time_table( $do_use_shipping_instances, $option_id_shipping_class );
	}

	/**
	 * wcj_product_shipping_class.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_shipping_class( $atts ) {
		$the_product_shipping_class = $this->the_product->get_shipping_class();
		if ( '' != $the_product_shipping_class ) {
			foreach ( WC()->shipping->get_shipping_classes() as $shipping_class ) {
				if ( $the_product_shipping_class === $shipping_class->slug ) {
					return $shipping_class->name;
				}
			}
		}
		return '';
	}

	/**
	 * wcj_product_total_sales.
	 *
	 * @version 5.3.8
	 * @since   2.2.6
	 */
	function wcj_product_total_sales( $atts ) {
		$product_custom_fields = get_post_custom( wcj_get_product_id_or_variation_parent_id( $this->the_product ) );
		$total_sales = ( isset( $product_custom_fields['total_sales'][0] ) ) ? $product_custom_fields['total_sales'][0] : '';
		if ( 0 != $atts['offset'] && !is_numeric($total_sales) ) {
			$total_sales += $atts['offset'];
		}
		return ( 0 == $total_sales && 'yes' === $atts['hide_if_zero'] ) ? '' : $total_sales;
	}

	/**
	 * wcj_product_purchase_price.
	 *
	 * @version 4.2.0
	 */
	function wcj_product_purchase_price( $atts ) {
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );
		$atts = shortcode_atts( array(
			'search'        => 'min_variation',
			'hide_currency' => 'no',
			'format'        => 'yes'
		), $atts );

		if ( ! $this->the_product->is_type( 'variable' ) ) {
			$purchase_price = wc_get_product_purchase_price( wcj_get_product_id( $this->the_product ) );
			return ( 'yes' === $atts['hide_currency'] ? $purchase_price : wc_price( $purchase_price ) );
		} else {
			$purchase_price = wc_get_variable_product_purchase_price( wcj_get_product_id( $this->the_product ), $atts );
			if ( $atts['format'] === 'yes' ) {
				if ( is_array( $purchase_price ) ) {
					if ( count( $purchase_price ) == 1 ) {
						return wc_price( $purchase_price[0] );
					} else if ( count( $purchase_price ) == 2 ) {
						return wc_format_price_range( $purchase_price[0], $purchase_price[1] );
					}
				} else {
					return wc_price( $purchase_price );
				}
			} else {
				if ( is_array( $purchase_price ) && count( $purchase_price ) == 1 ) {
					return $purchase_price[0];
				} elseif ( ! is_array( $purchase_price ) ) {
					return $purchase_price;
				}
			}
		}
	}

	/**
	 * wcj_product_purchase_price_without_html_custom.
	 *
	 * @version 5.4.0
	 */
    function wcj_product_purchase_price_without_html_custom( $atts ) {
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );
		$atts = shortcode_atts( array(
			'search'        => 'min_variation',
			'hide_currency' => 'no',
			'format'        => 'yes'
		), $atts );

		if ( ! $this->the_product->is_type( 'variable' ) ) {
			$purchase_price = wc_get_product_purchase_price( wcj_get_product_id( $this->the_product ) );
			return ( 'yes' === $atts['hide_currency'] ? $purchase_price : $purchase_price );
		} else {
			$purchase_price = wc_get_variable_product_purchase_price( wcj_get_product_id( $this->the_product ), $atts );
			if ( $atts['format'] === 'yes' ) {
				if ( is_array( $purchase_price ) ) {
					if ( count( $purchase_price ) == 1 ) {
						return $purchase_price[0];
					} else if ( count( $purchase_price ) == 2 ) {
						return wc_format_price_range( $purchase_price[0], $purchase_price[1] );
					}
				} else {
					return $purchase_price;
				}
			} else {
				if ( is_array( $purchase_price ) && count( $purchase_price ) == 1 ) {
					return $purchase_price[0];
				} elseif ( ! is_array( $purchase_price ) ) {
					return $purchase_price;
				}
			}
		}
	}





	/**
	 * wcj_product_tags.
	 *
	 * @version 2.7.0
	 * @return  string
	 */
	function wcj_product_tags( $atts ) {

		if ( 'yes' === $atts['add_links'] ) {
			return ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_product->get_tags( $atts['sep'] ) : wc_get_product_tag_list( $atts['product_id'], $atts['sep'] ) );
		}

		$product_tags = get_the_terms( $atts['product_id'], 'product_tag' );
		$product_tags_names = array();
		foreach ( $product_tags as $product_tag ) {
			$product_tags_names[] = $product_tag->name;
		}
		return implode( $atts['sep'], $product_tags_names );
	}

	/**
	 * wcj_product_you_save.
	 *
	 * @return  string
	 * @version 3.1.1
	 * @todo    (maybe) add `[wcj_product_discount]` alias
	 */
	function wcj_product_you_save( $atts ) {
		if ( $this->the_product->is_on_sale() ) {
			if ( $this->the_product->is_type( 'variable' ) ) {
				$you_save = ( $this->the_product->get_variation_regular_price( 'max' ) - $this->the_product->get_variation_sale_price( 'max' ) );
			} else {
				$you_save = ( $this->the_product->get_regular_price() - $this->the_product->get_sale_price() );
			}
			if ( '' !== $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
				$you_save *= $atts['multiply_by'];
			}
			return ( 'yes' === $atts['hide_currency'] ) ? $you_save : wc_price( $you_save );
		} else {
			return ( 'yes' === $atts['hide_if_zero'] ) ? '' : 0;
		}
	}

	/**
	 * wcj_product_you_save_percent.
	 *
	 * @return  string
	 * @version 4.1.0
	 */
	function wcj_product_you_save_percent( $atts ) {
		if ( $this->the_product->is_on_sale() ) {
			if ( $this->the_product->is_type( 'variable' ) ) {
				$you_save      = ( $this->the_product->get_variation_regular_price( 'max' ) - $this->the_product->get_variation_sale_price( 'max' ) );
				$regular_price = $this->the_product->get_variation_regular_price( 'max' );
			} else {
				$sale_price    = $this->the_product->get_sale_price();
				$price         = $this->the_product->get_price();
				$final_price   = ! empty( $sale_price ) && $sale_price < $price ? $this->the_product->get_sale_price() : $this->the_product->get_price();
				$you_save      = ( $this->the_product->get_regular_price() - $final_price );
				$regular_price = $this->the_product->get_regular_price();
			}
			if ( 0 != $regular_price ) {
				$you_save_percent = intval( $you_save / $regular_price * 100 );
				return ( 'yes' === $atts['reverse'] ) ? ( 100 - $you_save_percent ) : $you_save_percent;
			} else {
				return '';
			}
		} else {
			return ( 'yes' === $atts['hide_if_zero'] ) ? '' : ( ( 'yes' === $atts['reverse'] ) ? 100 : 0 );
		}
	}

	/**
	 * Get product meta.
	 *
	 * @version 5.3.7
	 * @since   2.5.7
	 * @return  string
	 */
	function wcj_product_meta( $atts ) {
		if ( '' == $atts['name'] ) {
			return '';
		}
		//Checking get_post_meta type ie. Array or String
		$checking_Postmeta_value=gettype(get_post_meta( $atts['product_id'], $atts['name'], true ));
		// if get_post_meta return Array
		if ($checking_Postmeta_value =='array') {
			//convert Array To String
	 		return implode(",",get_post_meta( $atts['product_id'], $atts['name'], true ));
		}
		return get_post_meta( $atts['product_id'], $atts['name'], true );
   }

	/**
	 * Get product custom field.
	 *
	 * @return string
	 */
	function wcj_product_custom_field( $atts ) {
		$product_custom_fields = get_post_custom( $atts['product_id'] );
		return ( isset( $product_custom_fields[ $atts['name'] ][0] ) ) ? $product_custom_fields[ $atts['name'] ][0] : '';
	}

	/**
	 * Returns product (modified) price.
	 *
	 * @version 3.9.0
	 * @todo    variable products: a) not range; and b) price by country.
	 * @return  string The product (modified) price
	 */
	function wcj_product_price( $atts ) {
		// Variable
		if ( $this->the_product->is_type( 'variable' ) ) {
			$min = $this->the_product->get_variation_price( 'min', false );
			$max = $this->the_product->get_variation_price( 'max', false );
			if ( '' !== $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
				$min = $min * $atts['multiply_by'];
				$max = $max * $atts['multiply_by'];
			}
			if (
				'' != $atts['currency'] &&
				( $base_product_currency = get_woocommerce_currency() ) != $atts['currency'] &&
				0 != ( $exchange_rate = wcj_get_saved_exchange_rate( $base_product_currency, $atts['currency'] ) )
			) {
				$min = $min * $exchange_rate;
				$max = $max * $exchange_rate;
			}
			if ( '' !== $atts['multiply_by_meta'] ) {
				$meta_value = get_post_meta( wcj_get_product_id( $this->the_product ), $atts['multiply_by_meta'], true );
				if ( is_numeric( $meta_value ) ) {
					$min = $min * $meta_value;
					$max = $max * $meta_value;
				}
			}
			if ( isset( $atts['multiply_by_attribute'] ) && '' !== $atts['multiply_by_attribute'] ) {
				$attribute = $this->the_product->get_attribute( $atts['multiply_by_attribute'] );
				$attribute = wcj_parse_number( $attribute );
				if ( is_numeric( $attribute ) ) {
					$min = $min * $attribute;
					$max = $max * $attribute;
				}
			}
			if ( 'yes' !== $atts['hide_currency'] ) {
				$min = wc_price( $min, array( 'currency' => $atts['currency'] ) );
				$max = wc_price( $max, array( 'currency' => $atts['currency'] ) );
			}
			if ( ! empty( $atts['min_or_max'] ) && ( 'min' === $atts['min_or_max'] || 'max' === $atts['min_or_max'] ) ) {
				return ( 'min' === $atts['min_or_max'] ? $min : $max );
			}
			return ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
		}
		// Simple etc.
		else {
			$the_price = $this->the_product->get_price();
			if ( '' !== $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
				$the_price = $the_price * $atts['multiply_by'];
			}
			if (
				'' != $atts['currency'] &&
				( $base_product_currency = get_woocommerce_currency() ) != $atts['currency'] &&
				0 != ( $exchange_rate = wcj_get_saved_exchange_rate( $base_product_currency, $atts['currency'] ) )
			) {
				$the_price = $the_price * $exchange_rate;
			}
			if ( '' !== $atts['multiply_by_meta'] ) {
				$meta_value = get_post_meta( wcj_get_product_id( $this->the_product ), $atts['multiply_by_meta'], true );
				if ( is_numeric( $meta_value ) ) {
					$the_price = $the_price * $meta_value;
				}
			}
			if ( isset( $atts['multiply_by_attribute'] ) && '' !== $atts['multiply_by_attribute'] ) {
				$attribute = $this->the_product->get_attribute( $atts['multiply_by_attribute'] );
				$attribute = wcj_parse_number( $attribute );
				if ( is_numeric( $attribute ) ) {
					$the_price = $the_price * $attribute;
				}
			}
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price, array( 'currency' => $atts['currency'] ) );
		}
	}

	/**
	 * wcj_product_wholesale_price_table.
	 *
	 * @version 5.0.0
	 * @todo    (maybe) `if ( 'yes' === $atts['add_percent_row'] )` for 'fixed' or 'price_directly'; `if ( 'yes' === $atts['add_discount_row'] )` for 'percent' or 'price_directly'
	 */
	function wcj_product_wholesale_price_table( $atts ) {

		$product_id = wcj_get_product_id_or_variation_parent_id( $this->the_product );

		if ( ! wcj_is_product_wholesale_enabled( $product_id ) ) {
			return '';
		}

		// Check for user role options
		$role_option_name_addon = '';
		$user_roles = wcj_get_option( 'wcj_wholesale_price_by_user_role_roles', '' );
		if ( ! empty( $user_roles ) ) {
			$current_user_role = wcj_get_current_user_first_role();
			foreach ( $user_roles as $user_role_key ) {
				if ( $current_user_role === $user_role_key ) {
					$role_option_name_addon = '_' . $user_role_key;
					break;
				}
			}
		}

		if ( isset( $atts['user_role'] ) ) {
			$role_option_name_addon = '_' . $atts['user_role'];
		}

		$wholesale_price_levels = array();
		if ( wcj_is_product_wholesale_enabled_per_product( $product_id ) ) {
			for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_post_meta( $product_id, '_' . 'wcj_wholesale_price_levels_number' . $role_option_name_addon, true ) ); $i ++ ) {
				$level_qty                = get_post_meta( $product_id, '_' . 'wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, true );
				$discount                 = get_post_meta( $product_id, '_' . 'wcj_wholesale_price_level_discount' . $role_option_name_addon . '_' . $i, true );
				$discount                 = ! empty( $discount ) ? $discount : 0;
				$wholesale_price_levels[] = array( 'quantity' => $level_qty, 'discount' => (float) $discount, );
			}
		} else {
			for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_wholesale_price_levels_number' . $role_option_name_addon, 1 ) ); $i++ ) {
				$level_qty                = wcj_get_option( 'wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, PHP_INT_MAX );
				$discount                 = wcj_get_option( 'wcj_wholesale_price_level_discount_percent' . $role_option_name_addon . '_' . $i, 0 );
				$discount                 = ! empty( $discount ) ? $discount : 0;
				$wholesale_price_levels[] = array( 'quantity' => $level_qty, 'discount' => (float) $discount, );
			}
		}

		$discount_type = ( wcj_is_product_wholesale_enabled_per_product( $product_id ) ) ?
			get_post_meta( $product_id, '_' . 'wcj_wholesale_price_discount_type', true ) :
			get_option( 'wcj_wholesale_price_discount_type', 'percent' );

		$data_qty        = array();
		$data_price      = array();
		$data_discount   = array();
		$columns_styles  = array();
		$i = -1;
		foreach ( $wholesale_price_levels as $wholesale_price_level ) {
			$i++;
			if ( 0 == $wholesale_price_level['quantity'] && 'yes' === $atts['hide_if_zero_quantity'] ) {
				continue;
			}

			$the_price = '';

			if ( $this->the_product->is_type( 'variable' ) ) {
				// Variable
				$prices = $this->the_product->get_variation_prices( false );
				$min_key = key( $prices['price'] );
				end( $prices['price'] );
				$max_key = key( $prices['price'] );
				$min_product = wc_get_product( $min_key );
				$max_product = wc_get_product( $max_key );
				$min = wcj_get_product_display_price( $min_product );
				$max = wcj_get_product_display_price( $max_product );
				$min_original = $min;
				$max_original = $max;
				if ( 'fixed' === $discount_type ) {
					$min = $min - $wholesale_price_level['discount'];
					$max = $max - $wholesale_price_level['discount'];
				} else {
					$coefficient = 1.0 - ( $wholesale_price_level['discount'] / 100.0 );
					$min = $min * $coefficient;
					$max = $max * $coefficient;
				}
				if ( 'yes' !== $atts['hide_currency'] ) {
					$min = wc_price( $min );
					$max = wc_price( $max );
					$min_original = wc_price( $min_original );
					$max_original = wc_price( $max_original );
				}
				$the_price = ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
				$the_price_original = ( $min_original != $max_original ) ? sprintf( '%s-%s', $min_original, $max_original ) : $min_original;
			} else {
				// Simple etc.
				$the_price = wcj_get_product_display_price( $this->the_product );
				$the_price = apply_filters( 'wcj_product_wholesale_price_table_price_before', $the_price, $this->the_product );
				$the_price = empty( $the_price ) ? 0 : $the_price;
				$the_price_original = $the_price;
				if ( 'price_directly' === $discount_type ) {
					$the_price = $wholesale_price_level['discount'];
				} elseif ( 'fixed' === $discount_type ) {
					$the_price = $the_price - $wholesale_price_level['discount'];
				} else { // 'percent'
					$coefficient = 1.0 - ( $wholesale_price_level['discount'] / 100.0 );
					$the_price = ( float ) $the_price * $coefficient;
				}
				$the_price_original = apply_filters( 'wcj_product_wholesale_price_table_price_after', $the_price_original, $this->the_product );
				$the_price          = apply_filters( 'wcj_product_wholesale_price_table_price_after', $the_price,          $this->the_product );
				if ( 'yes' !== $atts['hide_currency'] ) {
					$the_price = wc_price( $the_price );
					$the_price_original = wc_price( $the_price_original );
				}
			}

			// Heading Format
			$atts['heading_format'] = 'from %level_min_qty% pcs.' === $atts['heading_format'] ? wcj_get_option( 'wcj_wholesale_price_table_sc_title_format', __( 'from %level_min_qty% pcs.', 'woocommerce-jetpack' ) ) : $atts['heading_format'];

			$level_max_qty = ( isset( $wholesale_price_levels[ $i + 1 ]['quantity'] ) ) ?
				$atts['before_level_max_qty'] . ( $wholesale_price_levels[ $i + 1 ]['quantity'] - 1 ) : $atts['last_level_max_qty'];
			$data_qty[] = str_replace(
				array( '%level_qty%', '%level_min_qty%', '%level_max_qty%' ), // %level_qty% is deprecated
				array( $wholesale_price_level['quantity'], $wholesale_price_level['quantity'], $level_max_qty ),
				$atts['heading_format']
			);
			if ( 'yes' === $atts['add_price_row'] ) {
				$data_price[] = str_replace( array( '%old_price%', '%price%' ), array( $the_price_original, $the_price ), $atts['price_row_format'] );
			}
			if ( 'yes' === $atts['add_percent_row'] ) {
				if ( 'percent' === $discount_type ) {
					$data_discount[] = '-' . $wholesale_price_level['discount'] . '%';
				}
			}
			if ( 'yes' === $atts['add_discount_row'] ) {
				if ( 'fixed' === $discount_type ) {
					$data_discount[] = '-' . wc_price( $wholesale_price_level['discount'] );
				}
			}

			$columns_styles[] = $atts['columns_style'];
		}

		$table_rows = array( $data_qty, );
		if ( 'yes' === $atts['add_price_row'] ) {
			$table_rows[] = $data_price;
		}
		if ( 'yes' === $atts['add_percent_row'] ) {
			$table_rows[] = $data_discount;
		}

		if ( 'vertical' === $atts['table_format'] ) {
			$table_rows_modified = array();
			foreach ( $table_rows as $row_number => $table_row ) {
				foreach ( $table_row as $column_number => $cell ) {
					$table_rows_modified[ $column_number ][ $row_number ] = $cell;
				}
			}
			$table_rows = $table_rows_modified;
		}

		return wcj_get_table_html( $table_rows,
			array( 'table_class' => 'wcj_product_wholesale_price_table', 'columns_styles' => $columns_styles, 'table_heading_type' => $atts['table_format'] ) );
	}

	/**
	 * Get product description.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @return  string
	 */
	function wcj_product_description( $atts ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_product->post->post_content : $this->the_product->get_description() );
	}

	/**
	 * Get product short description.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 * @return  string
	 */
	function wcj_product_short_description( $atts ) {
		$short_description = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_product->post->post_excerpt : $this->the_product->get_short_description() );
		if ( 'yes' === $atts['apply_filters'] ) {
			apply_filters( 'woocommerce_short_description', $short_description );
		}
		if ( 0 != $atts['length'] ) {
			$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
			$short_description = wp_trim_words( $short_description, $atts['length'], $excerpt_more );
		}
		return $short_description;
	}

	/**
	 * For wcj_product_excerpt function.
	 *
	 * @version 2.5.7
	 */
	function custom_excerpt_length( $length ) {
		return $this->product_excerpt_length;
	}

	/**
	 * Get product excerpt.
	 *
	 * @version 2.5.8
	 * @return  string
	 */
	function wcj_product_excerpt( $atts ) {
		if ( 0 != $atts['excerpt_length'] ) {
			$atts['length'] = $atts['excerpt_length'];
		}
		$the_excerpt = $this->wcj_product_short_description( $atts );
		if ( '' === $the_excerpt ) {
			if ( 0 != $atts['length'] ) {
				$this->product_excerpt_length = $atts['length'];
				add_filter(    'excerpt_length', array( $this, 'custom_excerpt_length' ), PHP_INT_MAX );
				$the_excerpt = get_the_excerpt( $atts['product_id'] );
				remove_filter( 'excerpt_length', array( $this, 'custom_excerpt_length' ), PHP_INT_MAX );
			} else {
				$the_excerpt = get_the_excerpt( $atts['product_id'] );
			}
		}
		return $the_excerpt;
	}

	/**
	 * Get SKU (Stock-keeping unit) - product unique ID.
	 *
	 * @return string
	 */
	function wcj_product_sku( $atts ) {
		return $this->the_product->get_sku();
	}

	/**
	 * Get the title of the product.
	 *
	 * @return string
	 */
	function wcj_product_title( $atts ) {
		return $this->the_product->get_title();
	}

	/**
	 * get_variations_table.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) code refactoring
	 * @todo    (maybe) weight, length, width, height units
	 * @todo    (maybe) check has_length, has_width, has_height
	 */
	function get_variations_table( $param, $atts ) {
		$return_html = '';
		$return_html .= '<table>';
		foreach ( $this->the_product->get_available_variations() as $variation ) {
			$variation_product = wc_get_product( $variation['variation_id'] );
			$value = '';
			switch ( $param ) {
				case 'weight':
					$value = ( $variation_product->has_weight() ? $variation_product->get_weight() : '' );
					break;
				case 'length':
					$value = ( '' != $atts['to_unit'] ) ? wc_get_dimension( $variation_product->get_length(), $atts['to_unit'] ) : $variation_product->get_length();
					$value = ( 'yes' === $atts['round'] ) ? round( $value, $atts['precision'] ) : $value;
					break;
				case 'width':
					$value = ( '' != $atts['to_unit'] ) ? wc_get_dimension( $variation_product->get_width(), $atts['to_unit'] ) : $variation_product->get_width();
					$value = ( 'yes' === $atts['round'] ) ? round( $value, $atts['precision'] ) : $value;
					break;
				case 'height':
					$value = ( '' != $atts['to_unit'] ) ? wc_get_dimension( $variation_product->get_height(), $atts['to_unit'] ) : $variation_product->get_height();
					$value = ( 'yes' === $atts['round'] ) ? round( $value, $atts['precision'] ) : $value;
					break;
				case 'dimensions':
					$value = ( $variation_product->has_dimensions() ) ?
						( WCJ_IS_WC_VERSION_BELOW_3 ? $variation_product->get_dimensions() : wc_format_dimensions( $variation_product->get_dimensions( false ) ) )
						: '';
					break;
			}
			$return_html .= '<tr>';
			$return_html .= '<td>' . get_the_title( $variation['variation_id'] ) . '</td>';
			$return_html .= '<td>' . $value . '</td>';
			$return_html .= '</tr>';
		}
		$return_html .= '</table>';
		return $return_html;
	}

	/**
	 * Get the product's weight.
	 *
	 * @return  string
	 * @version 2.9.0
	 */
	function wcj_product_weight( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) && 'yes' === $atts['variations'] ) {
			return $this->get_variations_table( 'weight', $atts );
		}
		return ( $this->the_product->has_weight() ) ? $this->the_product->get_weight() : '';
	}

	/**
	 * wcj_product_image.
	 */
	function wcj_product_image( $atts ) {
		return $this->the_product->get_image( $atts['image_size'] );
	}

	/**
	 * wcj_product_image_url.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 */
	function wcj_product_image_url( $atts ) {
		return wcj_get_product_image_url( wcj_get_product_id_or_variation_parent_id( $this->the_product ), $atts['image_size'] );
	}

	/**
	 * wcj_product_gallery_image_url.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_product_gallery_image_url( $atts ) {
		$attachment_ids = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_product->get_gallery_attachment_ids() : $this->the_product->get_gallery_image_ids() );
		if ( $attachment_ids && isset( $attachment_ids[ ( $atts['image_nr'] - 1 ) ] ) ) {
			$props = wc_get_product_attachment_props( $attachment_ids[ ( $atts['image_nr'] - 1 ) ] );
			if ( isset( $props['url'] ) ) {
				return $props['url'];
			}
		}
		return '';
	}

	/**
	 * wcj_product_url.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function wcj_product_url( $atts ) {
		return $this->the_product->get_permalink();
	}

	/**
	 * wcj_product_categories_names.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 */
	function wcj_product_categories_names( $atts ) {
		$product_cats = get_the_terms( wcj_get_product_id_or_variation_parent_id( $this->the_product ), 'product_cat' );
		$cats = array();
		if ( ! empty( $product_cats ) && is_array( $product_cats ) ) {
			foreach ( $product_cats as $product_cat ) {
				$cats[] = $product_cat->name;
			}
		}
		return implode( $atts['sep'], $cats );
	}

	/**
	 * wcj_product_categories_urls.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 */
	function wcj_product_categories_urls( $atts ) {
		$product_cats = get_the_terms( wcj_get_product_id_or_variation_parent_id( $this->the_product ), 'product_cat' );
		$cats = array();
		if ( ! empty( $product_cats ) && is_array( $product_cats ) ) {
			foreach ( $product_cats as $product_cat ) {
				$cats[] = get_term_link( $product_cat );
			}
		}
		return implode( $atts['sep'], $cats );
	}
}

endif;

return new WCJ_Products_Shortcodes();

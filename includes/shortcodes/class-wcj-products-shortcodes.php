<?php
/**
 * WooCommerce Jetpack Products Shortcodes
 *
 * The WooCommerce Jetpack Products Shortcodes class.
 *
 * @version 2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Products_Shortcodes' ) ) :

class WCJ_Products_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	public function __construct() {

		$this->the_shortcodes = array(
			'wcj_product_image',
			'wcj_product_price',
			'wcj_product_wholesale_price_table',
			'wcj_product_sku',
			'wcj_product_title',
			'wcj_product_weight',
			'wcj_product_excerpt',
			'wcj_product_custom_field',
			'wcj_product_you_save',
			'wcj_product_you_save_percent',
			'wcj_product_tags',
			'wcj_product_purchase_price',
			'wcj_product_total_sales',
			'wcj_product_total_orders',
			'wcj_product_total_orders_items',
			'wcj_product_total_orders_sum',
			'wcj_product_crowdfunding_goal',
			'wcj_product_crowdfunding_goal_remaining',
			'wcj_product_crowdfunding_goal_remaining_progress_bar',
			'wcj_product_crowdfunding_startdate',
			'wcj_product_crowdfunding_deadline',
			'wcj_product_crowdfunding_time_remaining',
			'wcj_product_crowdfunding_time_remaining_progress_bar',
			'wcj_product_shipping_class',
			'wcj_product_dimensions',
			'wcj_product_formatted_name',
			'wcj_product_stock_availability',
			'wcj_product_tax_class',
			'wcj_product_average_rating',
			'wcj_product_categories',
			'wcj_product_list_attributes',
			'wcj_product_list_attribute',
			'wcj_product_stock_quantity',
			'wcj_product_sale_price',
			'wcj_product_regular_price',
			'wcj_product_time_since_last_sale',
			'wcj_product_price_including_tax',
			'wcj_product_price_excluding_tax',
			'wcj_product_available_variations',
		);

		$this->the_atts = array(
			'product_id'       => 0,
			'image_size'       => 'shop_thumbnail',
			'multiply_by'      => '',
			'hide_currency'    => 'no',
			'excerpt_length'   => 0,
			'name'             => '',
			'heading_format'   => 'from %level_qty% pcs.',
			'sep'              => ', ',
			'add_links'        => 'yes',
			'add_percent_row'  => 'no',
			'add_discount_row' => 'no',
			'add_price_row'    => 'yes',
			'show_always'      => 'yes',
			'hide_if_zero'     => 'no',
			'reverse'          => 'no',
			'find'             => '',
			'replace'          => '',
			'offset'           => '',
		);

		parent::__construct();
	}

	/**
	 * Inits shortcode atts and properties.
	 *
	 * @version 2.4.8
	 * @param   array $atts Shortcode atts.
	 * @return  array The (modified) shortcode atts.
	 */
	function init_atts( $atts ) {

		// Atts
		if ( 0 == $atts['product_id'] ) {
			global $wcj_product_id_for_shortcode;
			if ( 0 != $wcj_product_id_for_shortcode ) {
				$atts['product_id'] = $wcj_product_id_for_shortcode;
			} else {
				$atts['product_id'] = get_the_ID();
			}
			if ( 0 == $atts['product_id'] ) return false;
		}
		$the_post_type = get_post_type( $atts['product_id'] );
		if ( 'product' !== $the_post_type && 'product_variation' !== $the_post_type ) return false;

		// Class properties
		$this->the_product = wc_get_product( $atts['product_id'] );
		if ( ! $this->the_product ) return false;

		return $atts;
	}

	/**
	 * get_product_orders_data.
	 *
	 * @version 2.5.0
	 * @since   2.2.6
	 */
	function get_product_orders_data( $return_value = 'total_orders', $atts ) {
		$total_orders = 0;
		$total_qty    = 0;
		$total_sum    = 0;
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'wc-completed',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'date_query'     => array(
					array(
						'after'     => get_post_meta( $this->the_product->id, '_' . 'wcj_crowdfunding_startdate', true ),
						'inclusive' => true,
					),
				),
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$order_id = $loop->post->ID;
				$the_order = wc_get_order( $order_id );
				$the_items = $the_order->get_items();
				$item_found = false;
				foreach( $the_items as $item ) {
					if ( $this->the_product->id == $item['product_id'] ) {
						$total_sum += $item['line_total'] + $item['line_tax'];
						$total_qty += $item['qty'];
						$item_found = true;
					}
				}
				if ( $item_found ) {
					$total_orders++;
				}
			endwhile;
			$offset += $block_size;
		}
		wp_reset_postdata();
		switch ( $return_value ) {
			case 'orders_sum':
				$return = $total_sum;
				break;
			case 'total_items':
				$return = $total_qty;
				break;
			default: // 'total_orders'
				$return = $total_orders;
				break;
		}
		if ( 0 != $atts['offset'] ) {
			$return += $atts['offset'];
		}
		return $return;
	}

	/**
	 * wcj_product_time_since_last_sale.
	 *
	 * @version 2.5.0
	 * @since   2.4.0
	 * @todo    not finished
	 */
	function wcj_product_time_since_last_sale( $atts ) {
		// Constants
		$days_to_cover = 90;
		$do_use_only_completed_orders = true;
		// Get the ID before new query
		$the_ID = get_the_ID();
		$offset = 0;
		$block_size = 96;
		while( true ) {
			// Create args for new query
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => ( true === $do_use_only_completed_orders ? 'wc-completed' : 'any' ),
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'date_query'     => array( array( 'after'   => strtotime( '-' . $days_to_cover . ' days' ) ) ),
			);
			// Run new query
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			// Analyze the results, i.e. orders
			while ( $loop->have_posts() ) : $loop->the_post();
				$order = new WC_Order( $loop->post->ID );
				$items = $order->get_items();
				foreach ( $items as $item ) {
					// Run through all order's items
					if ( $item['product_id'] == $the_ID ) {
						// Found sale!
						$result = sprintf( __( '%s ago', 'woocommerce-jetpack' ), human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) );
						wp_reset_postdata();
						return $result;
					}
				}
			endwhile;
			$offset += $block_size;
		}
		wp_reset_postdata();
		// No sales found
		return '';
	}

	/**
	 * wcj_product_available_variations.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_available_variations( $atts ) {
		$return_html = '';
		if ( $this->the_product->is_type( 'variable' ) ) {
			$return_html .= '<table>';
			foreach ( $this->the_product->get_available_variations() as $variation ) {
				$return_html .= '<tr>';
				foreach ( $variation['attributes'] as $attribute_slug => $attribute_name ) {
					if ( '' == $attribute_name ) $attribute_name = __( 'Any', 'woocommerce-jetpack' );
					$return_html .= '<td>' . $attribute_name . '</td>';
				}
				$return_html .= '<td>' . $variation['price_html'] . '</td>';
				$return_html .= '</tr>';
			}
			$return_html .= '</table>';
		}
		return $return_html;
	}

	/**
	 * wcj_product_price_excluding_tax.
	 *
	 * @version 2.4.8
	 * @since   2.4.0
	 */
	function wcj_product_price_excluding_tax( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) ) {
			// Variable
			$prices = $this->the_product->get_variation_prices( false );
			$min_product_id = key( $prices['price'] );
			end( $prices['price'] );
			$max_product_id = key( $prices['price'] );
			if ( 0 != $min_product_id && 0 != $max_product_id ) {
				$min_variation_product = wc_get_product( $min_product_id );
				$max_variation_product = wc_get_product( $max_product_id );
				$min = $min_variation_product->get_price_excluding_tax();
				$max = $max_variation_product->get_price_excluding_tax();
				if ( 'yes' !== $atts['hide_currency'] ) {
					$min = wc_price( $min );
					$max = wc_price( $max );
				}
				return ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
			}
		} else {
			 // Simple etc.
			$the_price = $this->the_product->get_price_excluding_tax();
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
		}
	}

	/**
	 * wcj_product_price_including_tax.
	 *
	 * @version 2.4.8
	 * @since   2.4.0
	 */
	function wcj_product_price_including_tax( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) ) {
			// Variable
			$prices = $this->the_product->get_variation_prices( false );
			$min_product_id = key( $prices['price'] );
			end( $prices['price'] );
			$max_product_id = key( $prices['price'] );
			if ( 0 != $min_product_id && 0 != $max_product_id ) {
				$min_variation_product = wc_get_product( $min_product_id );
				$max_variation_product = wc_get_product( $max_product_id );
				$min = $min_variation_product->get_price_including_tax();
				$max = $max_variation_product->get_price_including_tax();
				if ( 'yes' !== $atts['hide_currency'] ) {
					$min = wc_price( $min );
					$max = wc_price( $max );
				}
				return ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
			}
		} else {
			 // Simple etc.
			$the_price = $this->the_product->get_price_including_tax();
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
		}
	}

	/**
	 * wcj_product_regular_price.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_regular_price( $atts ) {
		if ( $this->the_product->is_on_sale() || 'yes' === $atts['show_always'] ) {
			$the_price = $this->the_product->get_regular_price();
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
		}
		return '';
	}

	/**
	 * wcj_product_sale_price.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_sale_price( $atts ) {
		if ( $this->the_product->is_on_sale() ) {
			$the_price = $this->the_product->get_sale_price();
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
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_list_attributes( $atts ) {
		return ( $this->the_product->has_attributes() ) ? $this->the_product->list_attributes() : '';
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
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_stock_quantity( $atts ) {
		$stock_quantity = $this->the_product->get_stock_quantity();
		return ( '' != $stock_quantity ) ? $stock_quantity : false;
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
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_categories( $atts ) {
		return $this->the_product->get_categories();
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
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_product_dimensions( $atts ) {
		return ( $this->the_product->has_dimensions() ) ? $this->the_product->get_dimensions() : '';
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
	 * wcj_product_total_orders_items.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function wcj_product_total_orders_items( $atts ) {
		return $this->get_product_orders_data( 'total_items', $atts );
	}

	/**
	 * wcj_product_total_orders.
	 *
	 * @version 2.5.0
	 * @since   2.2.6
	 */
	function wcj_product_total_orders( $atts ) {
		return $this->get_product_orders_data( 'total_orders', $atts );
	}

	/**
	 * wcj_product_total_orders_sum.
	 *
	 * @version 2.5.0
	 * @since   2.2.6
	 */
	function wcj_product_total_orders_sum( $atts ) {
		return $this->get_product_orders_data( 'orders_sum', $atts );
	}

	/**
	 * wcj_product_crowdfunding_startdate.
	 *
	 * @version 2.5.0
	 * @since   2.2.6
	 */
	function wcj_product_crowdfunding_startdate( $atts ) {
		return date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $this->the_product->id, '_' . 'wcj_crowdfunding_startdate', true ) ) );
	}

	/**
	 * wcj_product_crowdfunding_deadline.
	 *
	 * @version 2.5.0
	 * @since   2.2.6
	 */
	function wcj_product_crowdfunding_deadline( $atts ) {
		return date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $this->the_product->id, '_' . 'wcj_crowdfunding_deadline', true ) ) );
	}

	/**
	 * wcj_product_crowdfunding_time_remaining.
	 *
	 * @version 2.3.8
	 * @since   2.2.6
	 */
	function wcj_product_crowdfunding_time_remaining( $atts ) {
		$seconds_remaining = strtotime( $this->wcj_product_crowdfunding_deadline( $atts ) ) - current_time( 'timestamp' );
		$days_remaining    = floor( $seconds_remaining / ( 24 * 60 * 60 ) );
		$hours_remaining   = floor( $seconds_remaining / (      60 * 60 ) );
		$minutes_remaining = floor( $seconds_remaining /             60   );
		if ( $seconds_remaining <= 0 ) return '';
		if ( $days_remaining    >  0 ) return ( 1 == $days_remaining    ) ? $days_remaining    . ' day left'    : $days_remaining    . ' days left';
		if ( $hours_remaining   >  0 ) return ( 1 == $hours_remaining   ) ? $hours_remaining   . ' hour left'   : $hours_remaining   . ' hours left';
		if ( $minutes_remaining >  0 ) return ( 1 == $minutes_remaining ) ? $minutes_remaining . ' minute left' : $minutes_remaining . ' minutes left';
		return                                ( 1 == $seconds_remaining ) ? $seconds_remaining . ' second left' : $seconds_remaining . ' seconds left';
		/* if ( ( $seconds_remaining = strtotime( $this->wcj_product_crowdfunding_deadline( $atts ) ) - time() ) <= 0 ) return '';
		if ( ( $days_remaining = floor( $seconds_remaining / ( 24 * 60 * 60 ) ) ) > 0 ) {
			return ( 1 === $days_remaining ) ? $days_remaining . ' day left' : $days_remaining . ' days left';
		}
		if ( ( $hours_remaining = floor( $seconds_remaining / ( 60 * 60 ) ) ) > 0 ) {
			return ( 1 === $hours_remaining ) ? $hours_remaining . ' hour left' : $hours_remaining . ' hours left';
		}
		if ( ( $minutes_remaining = floor( $seconds_remaining / 60 ) ) > 0 ) {
			return ( 1 === $minutes_remaining ) ? $minutes_remaining . ' minute left' : $minutes_remaining . ' minutes left';
		}
		return ( 1 === $seconds_remaining ) ? $seconds_remaining . ' second left' : $seconds_remaining . ' seconds left'; */
	}

	/**
	 * wcj_product_crowdfunding_time_remaining_progress_bar.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function wcj_product_crowdfunding_time_remaining_progress_bar( $atts ) {
		$deadline_seconds  = strtotime( get_post_meta( $this->the_product->id, '_' . 'wcj_crowdfunding_deadline', true ) );
		$startdate_seconds = strtotime( get_post_meta( $this->the_product->id, '_' . 'wcj_crowdfunding_startdate', true ) );

		$seconds_remaining = $deadline_seconds - current_time( 'timestamp' );
		$seconds_total     = $deadline_seconds - $startdate_seconds;

		$current_value = $seconds_remaining;
		$max_value     = $seconds_total;
		$return = '<progress value="' . $current_value . '" max="' . $max_value . '"></progress>';
	}

	/**
	 * wcj_product_crowdfunding_goal.
	 *
	 * @version 2.2.6
	 * @since   2.2.6
	 */
	function wcj_product_crowdfunding_goal( $atts ) {
		return get_post_meta( $this->the_product->id, '_' . 'wcj_crowdfunding_goal_sum', true );
	}

	/**
	 * wcj_product_crowdfunding_goal_remaining.
	 *
	 * @version 2.2.6
	 * @since   2.2.6
	 */
	function wcj_product_crowdfunding_goal_remaining( $atts ) {
		return $this->wcj_product_crowdfunding_goal( $atts ) - $this->wcj_product_total_orders_sum( $atts );
	}

	/**
	 * wcj_product_crowdfunding_goal_remaining_progress_bar.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function wcj_product_crowdfunding_goal_remaining_progress_bar( $atts ) {
		$current_value = $this->wcj_product_total_orders_sum( $atts );
		$max_value     = $this->wcj_product_crowdfunding_goal( $atts );
		return '<progress value="' . $current_value . '" max="' . $max_value . '"></progress>';
	}

	/**
	 * wcj_product_total_sales.
	 *
	 * @version 2.4.0
	 * @since   2.2.6
	 */
	function wcj_product_total_sales( $atts ) {
		$product_custom_fields = get_post_custom( $this->the_product->id );
		return ( isset( $product_custom_fields['total_sales'][0] ) ) ? $product_custom_fields['total_sales'][0] : '';
	}

	/**
	 * wcj_product_purchase_price.
	 *
	 * @return string
	 */
	function wcj_product_purchase_price( $atts ) {
		$purchase_price = wc_get_product_purchase_price( $the_product->id );
		return wc_price( $purchase_price );
	}

	/**
	 * wcj_product_tags.
	 *
	 * @return string
	 */
	function wcj_product_tags( $atts ) {

		if ( 'yes' === $atts['add_links'] ) {
			return $this->the_product->get_tags( $atts['sep'] );
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
	 * @version 2.4.0
	 */
	function wcj_product_you_save( $atts ) {
		if ( $this->the_product->is_on_sale() ) {
			if ( $this->the_product->is_type( 'variable' ) ) {
				$you_save = ( $this->the_product->get_variation_regular_price( 'max' ) - $this->the_product->get_variation_sale_price( 'max' ) );
			} else {
				$you_save = ( $this->the_product->get_regular_price() - $this->the_product->get_sale_price() );
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
	 * @version 2.4.0
	 */
	function wcj_product_you_save_percent( $atts ) {
		if ( $this->the_product->is_on_sale() ) {
			if ( $this->the_product->is_type( 'variable' ) ) {
				$you_save      = ( $this->the_product->get_variation_regular_price( 'max' ) - $this->the_product->get_variation_sale_price( 'max' ) );
				$regular_price = $this->the_product->get_variation_regular_price( 'max' );
			} else {
				$you_save      = ( $this->the_product->get_regular_price() - $this->the_product->get_sale_price() );
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
	 * Get product custom field.
	 *
	 * @return string
	 */
	function wcj_product_custom_field( $atts ) {
		$product_custom_fields = get_post_custom( $atts['product_id'] );
		return ( isset( $product_custom_fields[ $atts['name'] ][0] ) ) ? $product_custom_fields[ $atts['name'] ][0] : '';
		//return get_post_meta( $atts['product_id'], $atts['name'], true );
	}

	/**
	 * Returns product (modified) price.
	 *
	 * @version 2.4.8
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
			if ( 'yes' !== $atts['hide_currency'] ) {
				$min = wc_price( $min );
				$max = wc_price( $max );
			}
			return ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
		}
		// Simple etc.
		else {
			$the_price = $this->the_product->get_price();
			if ( '' !== $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) $the_price = $the_price * $atts['multiply_by'];
			return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
		}
	}

	/**
	 * wcj_product_wholesale_price_table.
	 *
	 * @version 2.5.0
	 */
	function wcj_product_wholesale_price_table( $atts ) {

		if ( ! wcj_is_product_wholesale_enabled( $this->the_product->id ) ) return '';

		$wholesale_price_levels = array();
		if ( wcj_is_product_wholesale_enabled_per_product( $this->the_product->id ) ) {
			for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_post_meta( $this->the_product->id, '_' . 'wcj_wholesale_price_levels_number', true ) ); $i++ ) {
				$level_qty                = get_post_meta( $this->the_product->id, '_' . 'wcj_wholesale_price_level_min_qty_' . $i, true );
				$discount                 = get_post_meta( $this->the_product->id, '_' . 'wcj_wholesale_price_level_discount_' . $i, true );
				$wholesale_price_levels[] = array( 'quantity' => $level_qty, 'discount' => $discount, );
			}
		} else {
			for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_wholesale_price_levels_number', 1 ) ); $i++ ) {
				$level_qty                = get_option( 'wcj_wholesale_price_level_min_qty_' . $i, PHP_INT_MAX );
				$discount                 = get_option( 'wcj_wholesale_price_level_discount_percent_' . $i, 0 );
				$wholesale_price_levels[] = array( 'quantity' => $level_qty, 'discount' => $discount, );
			}
		}

		$discount_type = ( wcj_is_product_wholesale_enabled_per_product( $this->the_product->id ) )
			? get_post_meta( $this->the_product->id, '_' . 'wcj_wholesale_price_discount_type', true )
			: get_option( 'wcj_wholesale_price_discount_type', 'percent' );

		$data_qty              = array();
		$data_price            = array();
		$data_discount         = array();
		$columns_styles        = array();
		foreach ( $wholesale_price_levels as $wholesale_price_level ) {
			$the_price = '';

			if ( $this->the_product->is_type( 'variable' ) ) {
				// Variable
				$min = $this->the_product->get_variation_price( 'min', false );
				$max = $this->the_product->get_variation_price( 'max', false );
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
				}
				$the_price = ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
			} else {
				// Simple etc.
				$the_price = $this->the_product->get_price();
				if ( 'fixed' === $discount_type ) {
					$the_price = $the_price - $wholesale_price_level['discount'];
				} else {
					$coefficient = 1.0 - ( $wholesale_price_level['discount'] / 100.0 );
					$the_price = $the_price * $coefficient;
				}
				if ( 'yes' !== $atts['hide_currency'] ) {
					$the_price = wc_price( $the_price );
				}
			}

			$data_qty[] = str_replace( '%level_qty%', $wholesale_price_level['quantity'], $atts['heading_format'] ) ;
			if ( 'yes' === $atts['add_price_row'] ) {
				$data_price[] = $the_price;
			}
			if ( 'yes' === $atts['add_percent_row'] ) {
				if ( 'fixed' === $discount_type ) {
					// todo (maybe)
				} else {
					$data_discount[] = '-' . $wholesale_price_level['discount'] . '%';
				}
			}
			if ( 'yes' === $atts['add_discount_row'] ) {
				if ( 'fixed' === $discount_type ) {
					$data_discount[] = '-' . wc_price( $wholesale_price_level['discount'] );
				} else {
					// todo (maybe)
				}
			}

			$columns_styles[] = 'text-align: center;';
		}

		$table_rows = array( $data_qty, );
		if ( 'yes' === $atts['add_price_row'] ) {
			$table_rows[] = $data_price;
		}
		if ( 'yes' === $atts['add_percent_row'] ) {
			$table_rows[] = $data_discount;
		}

		return wcj_get_table_html( $table_rows, array( 'columns_styles' => $columns_styles ) );
	}

	/**
	 * For wcj_product_excerpt function.
	 */
	/* private */ function custom_excerpt_length( $length ) {
		global $product_excerpt_length;
		return $product_excerpt_length;
	}
	/**
	 * Get product excerpt.
	 *
	 * @return string
	 */
	function wcj_product_excerpt( $atts ) {
		global $post;
		global $product_excerpt_length;
		$post = get_post( $atts['product_id'] );
		setup_postdata( $post );

		$product_excerpt_length = $atts['excerpt_length'];
		if ( 0 != $atts['excerpt_length'] )    add_filter( 'excerpt_length', array( $this, 'custom_excerpt_length' ), PHP_INT_MAX );
		$the_excerpt = get_the_excerpt();
		if ( 0 != $atts['excerpt_length'] ) remove_filter( 'excerpt_length', array( $this, 'custom_excerpt_length' ), PHP_INT_MAX );

		wp_reset_postdata();
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
	 * Get the product's weight.
	 *
	 * @return  string
	 * @version 2.4.0
	 */
	function wcj_product_weight( $atts ) {
		return ( $this->the_product->has_weight() ) ? $this->the_product->get_weight() : '';
	}

	/**
	 * wcj_product_image.
	 */
	function wcj_product_image( $atts ) {
		return $this->the_product->get_image( $atts['image_size'] );
	}
}

endif;

return new WCJ_Products_Shortcodes();

<?php
/**
 * WooCommerce Jetpack Order Items Shortcodes
 *
 * The WooCommerce Jetpack Order Items Shortcodes class.
 *
 * @version 2.5.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Order_Items_Shortcodes' ) ) :

class WCJ_Order_Items_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->the_shortcodes = array(
			'wcj_order_items_table',
		);

		parent::__construct();
	}

	/**
	 * add_extra_atts.
	 *
	 * @version 2.5.9
	 */
	function add_extra_atts( $atts ) {
		$modified_atts = array_merge( array(
			'order_id'             => ( isset( $_GET['order_id'] ) ) ? $_GET['order_id'] : get_the_ID(),
			'hide_currency'        => 'no',
			'table_class'          => '',
			'shipping_as_item'     => '', //__( 'Shipping', 'woocommerce-jetpack' ),
			'discount_as_item'     => '', //__( 'Discount', 'woocommerce-jetpack' ),
			'columns'              => '',
			'columns_titles'       => '',
			'columns_styles'       => '',
			'tax_percent_format'   => '%.2f %%',
			'item_image_width'     => 0, // deprecated
			'item_image_height'    => 0, // deprecated
			'product_image_width'  => 0,
			'product_image_height' => 0,
			'price_prefix'         => '',
			'quantity_prefix'      => '',
			'style_item_name_variation' => 'font-size:smaller;',
			'variation_as_metadata' => 'yes',
			'wc_extra_product_options_show_price' => 'no',
		), $atts );
		return $modified_atts;
	}

	/**
	 * init_atts.
	 *
	 * @version 2.5.7
	 */
	function init_atts( $atts ) {
		$this->the_order = ( 'shop_order' === get_post_type( $atts['order_id'] ) ) ? wc_get_order( $atts['order_id'] ) : null;
		if ( ! $this->the_order ) return false;
		if ( 0 != $atts['item_image_width'] ) {
			$atts['product_image_width'] = $atts['item_image_width'];
		}
		if ( 0 != $atts['item_image_height'] ) {
			$atts['product_image_height'] = $atts['item_image_height'];
		}
		return $atts;
	}

	/**
	 * wcj_price_shortcode.
	 */
	private function wcj_price_shortcode( $raw_price, $atts ) {
		return wcj_price( $atts['price_prefix'] . $raw_price, $this->the_order->get_order_currency(), $atts['hide_currency'] );
	}

	/**
	 * add_item.
	 */
	private function add_item( $items, $new_item_args = array() ) {
		if ( empty ( $new_item_args ) ) return $items;
		extract( $new_item_args );
		// Create item
		$items[] = array(
			'is_custom'         => true,
			'name'              => $name,
			'type'              => 'line_item',
			'qty'               => $qty,
			'line_subtotal'     => $line_subtotal,
			'line_total'        => $line_total,
			'line_tax'          => $line_tax,
			'line_subtotal_tax' => $line_subtotal_tax,
			'item_meta'         => array(
				'_qty'               => array( $qty ),
				'_line_subtotal'     => array( $line_subtotal ),
				'_line_total'        => array( $line_total ),
				'_line_tax'          => array( $line_tax ),
				'_line_subtotal_tax' => array( $line_subtotal_tax ),
			),
		);
		return $items;
	}

	/**
	 * wcj_order_get_cart_discount_tax.
	 */
	/* function wcj_order_get_cart_discount_tax() {

		$the_cart_discount = $this->the_order->get_cart_discount();
		$is_discount_taxable = ( $the_cart_discount > 0 ) ? true : false;

		if ( $is_discount_taxable ) {

			/* $order_total_incl_tax = $this->the_order->get_total();
			$order_total_tax      = $this->the_order->get_total_tax(); *//*

			$order_total_incl_tax = 0;
			$order_total_tax = 0;
			$items = $this->the_order->get_items();
			foreach ( $items as $item ) {
				$order_total_incl_tax += $item['line_total'] + $item['line_tax'];
				$order_total_tax += $item['line_tax'];
			}

			if ( 0 != $order_total_incl_tax ) {

				$order_tax_rate = $order_total_tax / $order_total_incl_tax;
				$the_tax = $the_cart_discount * $order_tax_rate;

				return $the_tax;
			}
		}

		return false;
	} */

	/**
	 * get_tax_class_name.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_tax_class_name( $tax_class ) {
		$tax_classes       = WC_Tax::get_tax_classes();
		$classes_names     = array();
		$classes_names[''] = __( 'Standard', 'woocommerce' );
		if ( ! empty( $tax_classes ) ) {
			foreach ( $tax_classes as $class ) {
				$classes_names[ sanitize_title( $class ) ] = esc_html( $class );
			}
		}
		return ( isset( $classes_names[ $tax_class ] ) ) ? $classes_names[ $tax_class ] : '';
	}

	/**
	 * get_meta_info.
	 *
	 * from woocommerce\includes\admin\meta-boxes\views\html-order-item-meta.php
	 *
	 * @version 2.5.9
	 * @since   2.5.8
	 */
	function get_meta_info( $item_id, $the_product ) {
		$meta_info = '';
		if ( $metadata = $this->the_order->has_meta( $item_id ) ) {
			$meta_info = array();
			foreach ( $metadata as $meta ) {

				// Skip hidden core fields
				if ( in_array( $meta['meta_key'], apply_filters( 'woocommerce_hidden_order_itemmeta', array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
					'method_id',
					'cost'
				) ) ) ) {
					continue;
				}

				// Skip serialised meta
				if ( is_serialized( $meta['meta_value'] ) ) {
					continue;
				}

				// Get attribute data
				if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta['meta_key'] ) ) ) {
					$term               = get_term_by( 'slug', $meta['meta_value'], wc_sanitize_taxonomy_name( $meta['meta_key'] ) );
					$meta['meta_key']   = wc_attribute_label( wc_sanitize_taxonomy_name( $meta['meta_key'] ) );
					$meta['meta_value'] = isset( $term->name ) ? $term->name : $meta['meta_value'];
				} else {
					$meta['meta_key']   = ( is_object( $the_product ) ) ? wc_attribute_label( $meta['meta_key'], $the_product ) : $meta['meta_key'];
				}
				$meta_info[] = wp_kses_post( rawurldecode( $meta['meta_key'] ) ) . ': ' . wp_kses_post( rawurldecode( $meta['meta_value'] ) );
			}
			$meta_info = implode( ', ', $meta_info );
		}
		return $meta_info;
	}

	/**
	 * wcj_order_items_table.
	 *
	 * @version 2.5.9
	 */
	function wcj_order_items_table( $atts, $content = '' ) {

		$html = '';
		$the_order = $this->the_order;

		// Get columns
		$columns = explode( '|', $atts['columns'] );
		if ( empty( $columns ) ) return '';
		$columns_total_number = count( $columns );
		// Check all possible args
		$columns_titles = ( '' == $atts['columns_titles'] ) ? array() : explode( '|', $atts['columns_titles'] );
		$columns_styles = ( '' == $atts['columns_styles'] ) ? array() : explode( '|', $atts['columns_styles'] );
		/* if ( $columns_total_number !== count( $columns_titles ) || $columns_total_number !== count( $columns_styles ) ) {
			return __( 'Please recheck that there is the same number of columns in "columns", "columns_titles" and "columns_styles" attributes.', 'woocommerce-jetpack' );
		} */

		// The Items
		$the_items = $the_order->get_items();

		// Shipping as item
		if ( '' != $atts['shipping_as_item'] && $the_order->get_total_shipping() > 0 ) {
			$name                    = str_replace( '%shipping_method_name%', $the_order->get_shipping_method(), $atts['shipping_as_item'] );
			$total_shipping_tax_excl = $the_order->get_total_shipping();
			$shipping_tax            = $the_order->get_shipping_tax();

			$the_items = $this->add_item( $the_items, array( 'name' => $name, 'qty' => 1, 'line_subtotal' => $total_shipping_tax_excl, 'line_total' => $total_shipping_tax_excl, 'line_tax' => $shipping_tax, 'line_subtotal_tax' => $shipping_tax, ) );
		}

		// Discount as item
		if ( '' != $atts['discount_as_item'] && $the_order->get_total_discount( true ) > 0 ) {
			$name                    = $atts['discount_as_item'];
			$total_discount_tax_excl = $the_order->get_total_discount( true );
			$discount_tax            = $the_order->get_total_discount( false ) - $total_discount_tax_excl;

			/* if ( false != ( $the_tax = $this->wcj_order_get_cart_discount_tax() ) ) {
				$total_discount_tax_excl -= $the_tax;
				$discount_tax += $the_tax;
			} */

			$total_discount_tax_excl *= -1;
			$discount_tax *= -1;

			$the_items = $this->add_item( $the_items, array( 'name' => $name, 'qty' => 1, 'line_subtotal' => $total_discount_tax_excl, 'line_total' => $total_discount_tax_excl, 'line_tax' => $discount_tax, 'line_subtotal_tax' => $discount_tax, ) );
		}

		// Starting data[] by adding columns titles
		$data = array();
		foreach( $columns_titles as $column_title ) {
			$data[0][] = $column_title;
		}
		// Items to data[]
		$item_counter = 0;
		foreach ( $the_items as $item_id => $item ) {
			$item['is_custom'] = ( isset( $item['is_custom'] ) ) ? true : false;
			$the_product = ( true === $item['is_custom'] ) ? null : $the_order->get_product_from_item( $item );
			$item_counter++;
			// Columns
			foreach( $columns as $column ) {
				if ( false !== ( $pos = strpos( $column, '=' ) ) ) {
					$column_param = substr( $column, $pos + 1 );
					$column       = substr( $column, 0, $pos );
				}
				switch ( $column ) {
					case 'item_debug':
					case 'debug':
						$data[ $item_counter ][] = print_r( $item, true );
						break;
					case 'item_regular_price':
					case 'product_regular_price':
						$data[ $item_counter ][] = ( is_object( $the_product ) ) ? $this->wcj_price_shortcode( $the_product->get_regular_price(), $atts ) : '';
						break;
					case 'item_sale_price':
					case 'product_sale_price':
						$data[ $item_counter ][] = ( is_object( $the_product ) ) ? $this->wcj_price_shortcode( $the_product->get_sale_price(), $atts ) : '';
						break;
					case 'item_tax_class':
					case 'tax_class':
						$data[ $item_counter ][] = ( isset( $item['tax_class'] ) ) ? $this->get_tax_class_name( $item['tax_class'] ) : '';
						break;
					case 'item_number':
						$data[ $item_counter ][] = $item_counter;
						break;
					case 'item_meta':
						$data[ $item_counter ][] = wcj_get_order_item_meta_info( $item_id, null, $this->the_order, false, $the_product );
						break;
					case 'item_name':
					case 'product_name': // "product_" because of possible variation
						if ( true === $item['is_custom'] ) {
							$data[ $item_counter ][] = $item['name'];
						} else {
							$the_item_title = $item['name'];
							// Variation (if needed)
							if ( 0 != $item['variation_id'] && ! in_array( 'item_variation', $columns ) ) {
								$the_item_title .= '<div style="' . $atts['style_item_name_variation'] . '">';
								if ( 'yes' === $atts['variation_as_metadata'] ) {
									$the_item_title .= wcj_get_order_item_meta_info( $item_id, null, $this->the_order, true, $the_product );
								} elseif ( is_object( $the_product ) && $the_product->is_type( 'variation' ) ) {
									$the_item_title .= str_replace( 'pa_', '', urldecode( $the_product->get_formatted_variation_attributes( true ) ) ); // todo - do we need pa_ replacement?
								}
								$the_item_title .= '</div>';
							}
							// "WooCommerce TM Extra Product Options" plugin options
							// todo - this will show options prices in shop's default currency only (must use 'price_per_currency' to show prices in order's currency).
							if ( isset( $item['tmcartepo_data'] ) ) {
								$options = unserialize( $item['tmcartepo_data'] );
								$options_prices = array();
//								$order_currency = $the_order->get_order_currency();
								foreach ( $options as $option ) {
									/* if ( isset( $option['price_per_currency'][ $order_currency ] ) ) {
										$options_prices[] = $this->wcj_price_shortcode( $option['price_per_currency'][ $order_currency ], $atts );
									} */
									$option_info = '';
									if ( isset( $option['name'] ) && '' != $option['name'] ) {
										$option_info .= $option['name'] . ': ';
									}
									if ( isset( $option['value'] ) && '' != $option['value'] ) {
										$option_info .= $option['value'];
									}
									if ( isset( $option['price'] ) && 'yes' === $atts['wc_extra_product_options_show_price'] ) { // todo - wc_extra_product_options_show_price is temporary, until price_per_currency issue is solved
										$option_info .= ( $option['price'] > 0 ) ? ' +' . wc_price( $option['price'] ) : ' ' . wc_price( $option['price'] );
									}
									if ( '' != $option_info ) {
										$options_prices[] = $option_info;
									}
								}
								$the_item_title .= '<div style="' . $atts['style_item_name_variation'] . '">' . implode( '<br>', $options_prices ) . '</div>';
							}
							$data[ $item_counter ][] = $the_item_title;
						}
						break;
					case 'item_product_input_fields':
						$data[ $item_counter ][] = wcj_get_product_input_fields( $item );
						break;
					case 'item_key':
						if ( isset( $column_param ) && '' != $column_param && isset( $item[ $column_param ] ) ) {
							$maybe_unserialized_value = maybe_unserialize( $item[ $column_param ] );
							if ( is_array( $maybe_unserialized_value ) ) {
								$data[ $item_counter ][] = isset( $maybe_unserialized_value['name'] ) ? $maybe_unserialized_value['name'] : '';
							} else {
								$data[ $item_counter ][] = $maybe_unserialized_value;
							}
						} else {
							$data[ $item_counter ][] = '';
						}
						break;
					case 'item_attribute':
					case 'product_attribute':
						if ( isset( $column_param ) && '' != $column_param && is_object( $the_product ) ) {
							$data[ $item_counter ][] = $the_product->get_attribute( $column_param );
						} else {
							$data[ $item_counter ][] = '';
						}
						break;
					case 'item_excerpt':
					case 'product_excerpt':
						if ( true === $item['is_custom'] ) {
							$data[ $item_counter ][] = '';
						} else {
							global $post;
							$post = get_post( $item['product_id'] );
							setup_postdata( $post );
							$the_excerpt = get_the_excerpt();
							wp_reset_postdata();
							$data[ $item_counter ][] = $the_excerpt;
						}
						break;
					case 'item_short_description':
					case 'product_short_description':
						$data[ $item_counter ][] = ( true === $item['is_custom'] ) ? '' : $this->the_product->post->post_excerpt;
						break;
					case 'item_variation':
					case 'product_variation':
						if ( 0 != $item['variation_id'] ) {
							if ( 'yes' === $atts['variation_as_metadata'] ) {
								$data[ $item_counter ][] = wcj_get_order_item_meta_info( $item_id, null, $this->the_order, true, $the_product );
							} elseif ( is_object( $the_product ) && $the_product->is_type( 'variation' ) ) {
								$data[ $item_counter ][] = str_replace( 'pa_', '', urldecode( $the_product->get_formatted_variation_attributes( true ) ) ); // todo - do we need pa_ replacement?
							}
						} else {
							$data[ $item_counter ][] = '';
						}
						break;
					case 'item_thumbnail':
					case 'product_thumbnail':
//						$data[ $item_counter ][] = $the_product->get_image();
						$image_id = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? 0 : $the_product->get_image_id();
						$image_src = ( 0 != $image_id ) ? wp_get_attachment_image_src( $image_id ) : wc_placeholder_img_src();
						if ( is_array( $image_src ) ) $image_src = $image_src[0];
						$maybe_width  = ( 0 != $atts['product_image_width'] )  ? ' width="'  . $atts['product_image_width']  . '"' : '';
						$maybe_height = ( 0 != $atts['product_image_height'] ) ? ' height="' . $atts['product_image_height'] . '"' : '';
						$data[ $item_counter ][] = '<img src="' . $image_src . '"' . $maybe_width . $maybe_height . '>';
						break;
					case 'item_sku':
					case 'product_sku':
						$data[ $item_counter ][] = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_sku();
						break;
					case 'item_quantity':
						$data[ $item_counter ][] = $atts['quantity_prefix'] . $item['qty'];
						break;
					case 'item_total_tax_excl':
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $the_order->get_item_total( $item, false, true ), $atts );
						break;
					case 'item_total_tax_incl':
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $the_order->get_item_total( $item, true, true ), $atts );
						break;
					case 'item_subtotal_tax_excl':
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $the_order->get_item_subtotal( $item, false, true ), $atts );
						break;
					case 'item_subtotal_tax_incl':
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $the_order->get_item_subtotal( $item, true, true ), $atts );
						break;
					case 'item_tax':
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $the_order->get_item_tax( $item, true ), $atts );
						break;
					case 'line_total_tax_excl':
						$line_total_tax_excl = $the_order->get_line_total( $item, false, true );
						$line_total_tax_excl = apply_filters( 'wcj_line_total_tax_excl', $line_total_tax_excl, $the_order );
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $line_total_tax_excl, $atts );
						break;
					case 'line_total_tax_incl':
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $the_order->get_line_total( $item, true, true ), $atts );
						break;
					case 'line_subtotal_tax_excl':
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $the_order->get_line_subtotal( $item, false, true ), $atts );
						break;
					case 'line_subtotal_tax_incl':
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $the_order->get_line_subtotal( $item, true, true ), $atts );
						break;
					case 'line_tax':
						$line_tax = $the_order->get_line_tax( $item );
						$line_tax = apply_filters( 'wcj_line_tax', $line_tax, $the_order );
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $line_tax, $atts );
						break;
					case 'line_subtax':
						$line_subtax = $the_order->get_line_subtotal( $item, true, false ) - $the_order->get_line_subtotal( $item, false, false );
						$data[ $item_counter ][] = $this->wcj_price_shortcode( $line_subtax, $atts );
						break;
					case 'item_tax_percent':
					case 'line_tax_percent':
						$item_total = $the_order->get_item_total( $item, false, /* true */ false );
						$item_tax_percent = ( 0 != $item_total ) ? $the_order->get_item_tax( $item, false ) / $item_total * 100 : 0;
						$item_tax_percent = apply_filters( 'wcj_line_tax_percent', $item_tax_percent, $the_order );
						$data[ $item_counter ][] = sprintf( $atts['tax_percent_format'], $item_tax_percent );
						/* $tax_labels = array();
						foreach ( $the_order->get_taxes() as $the_tax ) {
							$tax_labels[] = $the_tax['label'];
						}
						$data[ $item_counter ][] = implode( ', ', $tax_labels ); */
						break;
					/* case 'line_tax_percent':
						$line_total = $the_order->get_line_total( $item, false, true );
						$line_tax_percent = ( 0 != $line_total ) ? $the_order->get_line_tax( $item ) / $line_total * 100 : 0;
						$line_tax_percent = apply_filters( 'wcj_line_tax_percent', $line_tax_percent, $the_order );
						$data[ $item_counter ][] = sprintf( $atts['tax_percent_format'], $line_tax_percent );
						break; */
					case 'item_weight':
					case 'product_weight':
						$data[ $item_counter ][] = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_weight();
						break;
					case 'item_width':
					case 'product_width':
						$data[ $item_counter ][] = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_width();
						break;
					case 'item_height':
					case 'product_height':
						$data[ $item_counter ][] = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_height();
						break;
					case 'item_length':
					case 'product_length':
						$data[ $item_counter ][] = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_length();
						break;
					default:
						$data[ $item_counter ][] = ''; // $column;
				}
			}
		}

		$html = wcj_get_table_html( $data, array(
			'table_class'        => $atts['table_class'],
			'table_heading_type' => 'horizontal',
			'columns_classes'    => array(),
			'columns_styles'     => $columns_styles,
		) );

		return $html;
	}
}

endif;

return new WCJ_Order_Items_Shortcodes();

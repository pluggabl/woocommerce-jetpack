<?php
/**
 * WooCommerce Jetpack Order Items Shortcodes
 *
 * The WooCommerce Jetpack Order Items Shortcodes class.
 *
 * @version 2.5.1
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
	 * @version 2.5.0
	 */
	function add_extra_atts( $atts ) {
		$modified_atts = array_merge( array(
			'order_id'           => ( isset( $_GET['order_id'] ) ) ? $_GET['order_id'] : get_the_ID(),
			'hide_currency'      => 'no',
			'table_class'        => '',
			'shipping_as_item'   => '',//__( 'Shipping', 'woocommerce-jetpack' ),
			'discount_as_item'   => '',//__( 'Discount', 'woocommerce-jetpack' ),
			'columns'            => '',
			'columns_titles'     => '',
			'columns_styles'     => '',
			'tax_percent_format' => '%.2f %%',
			'item_image_width'   => 0,
			'item_image_height'  => 0,
			'price_prefix'       => '',
			'style_item_name_variation' => 'font-size:smaller;',
		), $atts );
		return $modified_atts;
	}

	/**
	 * init_atts.
	 */
	function init_atts( $atts ) {
		$this->the_order = ( 'shop_order' === get_post_type( $atts['order_id'] ) ) ? wc_get_order( $atts['order_id'] ) : null;
		if ( ! $this->the_order ) return false;
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
	 * wcj_order_items_table.
	 *
	 * @version 2.5.1
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
		//if ( ! ( $columns_total_number === count( $columns_titles ) === count( $columns_styles ) ) ) return '';

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
		foreach ( $the_items as $item ) {
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
					case 'debug':
						$data[ $item_counter ][] = print_r( $item, true );
						break;
					case 'item_number':
						$data[ $item_counter ][] = $item_counter;
						break;
					case 'item_name':
						//$data[ $item_counter ][] = ( true === $item['is_custom'] ) ? $item['name'] : $the_product->get_title();
						if ( true === $item['is_custom'] ) {
							$data[ $item_counter ][] = $item['name'];
						} else {
							$the_item_title = $item['name'];//$the_product->get_title();
							// Variation (if needed)
							if ( is_object( $the_product ) && $the_product->is_type( 'variation' ) && ! in_array( 'item_variation', $columns ) ) {
								$the_item_title .= '<div style="' . $atts['style_item_name_variation'] . '">'
									. str_replace( 'pa_', '', urldecode( wc_get_formatted_variation( $the_product->variation_data, true ) ) )
									. '</div>';
							}
							$data[ $item_counter ][] = $the_item_title;
						}
						break;
					case 'item_product_input_fields':
						$data[ $item_counter ][] = wcj_get_product_input_fields( $item );
						break;
					case 'item_key':
						if ( isset( $item[ $column_param ] ) ) {
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
					case 'item_excerpt':
					case 'item_description':
					case 'item_short_description':
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
					case 'item_variation':
						$data[ $item_counter ][] = ( is_object( $the_product ) && $the_product->is_type( 'variation' ) )
							? str_replace( 'pa_', '', urldecode( wc_get_formatted_variation( $the_product->variation_data, true ) ) ) : '';
						break;
					case 'item_thumbnail':
						//$data[ $item_counter ][] = $the_product->get_image();
						$image_id = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? 0 : $the_product->get_image_id();
						$image_src = ( 0 != $image_id ) ? wp_get_attachment_image_src( $image_id ) : wc_placeholder_img_src();
						if ( is_array( $image_src ) ) $image_src = $image_src[0];
						$maybe_width  = ( 0 != $atts['item_image_width'] )  ? ' width="'  . $atts['item_image_width']  . '"' : '';
						$maybe_height = ( 0 != $atts['item_image_height'] ) ? ' height="' . $atts['item_image_height'] . '"' : '';
						$data[ $item_counter ][] = '<img src="' . $image_src . '"' . $maybe_width . $maybe_height . '>';
						break;
					case 'item_sku':
						$data[ $item_counter ][] = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_sku();
						break;
					case 'item_quantity':
						$data[ $item_counter ][] = $item['qty'];
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
						$data[ $item_counter ][] = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_weight();
						break;
					default:
						$data[ $item_counter ][] = ''; //$column;
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

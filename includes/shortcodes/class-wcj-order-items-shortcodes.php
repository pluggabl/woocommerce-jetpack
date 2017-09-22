<?php
/**
 * Booster for WooCommerce - Shortcodes - Order Items
 *
 * @version 3.1.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Order_Items_Shortcodes' ) ) :

class WCJ_Order_Items_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->the_shortcodes = array(
			'wcj_order_items_table',
		);

		parent::__construct();
	}

	/**
	 * add_extra_atts.
	 *
	 * @version 3.1.2
	 */
	function add_extra_atts( $atts ) {
		$modified_atts = array_merge( array(
			'order_id'                            => ( isset( $_GET['order_id'] ) ) ? $_GET['order_id'] : get_the_ID(),
			'hide_currency'                       => 'no',
			'table_class'                         => '',
			'shipping_as_item'                    => '', // __( 'Shipping', 'woocommerce-jetpack' ),
			'discount_as_item'                    => '', // __( 'Discount', 'woocommerce-jetpack' ),
			'columns'                             => '',
			'columns_titles'                      => '',
			'columns_styles'                      => '',
			'tax_percent_format'                  => '%.2f %%',
			'item_image_width'                    => 0, // deprecated
			'item_image_height'                   => 0, // deprecated
			'product_image_width'                 => 0,
			'product_image_height'                => 0,
			'price_prefix'                        => '',
			'quantity_prefix'                     => '',
			'style_item_name_variation'           => 'font-size:smaller;',
			'variation_as_metadata'               => 'yes',
			'wc_extra_product_options_show_price' => 'no',
			'order_user_roles'                    => '',
			'exclude_by_categories'               => '',
			'exclude_by_tags'                     => '',
			'exclude_by_attribute__name'          => '',
			'exclude_by_attribute__value'         => '',
			'add_variation_info_to_item_name'     => 'yes',
			'insert_page_break'                   => '',
			'multiply_cost'                       => 1,
			'multiply_profit'                     => 1,
			'refunded_items_table'                => 'no',
			'hide_zero_prices'                    => 'no',
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
	 * extra_check.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function extra_check( $atts ) {
		if ( '' != $atts['order_user_roles'] ) {
			$user_info = get_userdata( $this->the_order->customer_user );
			$user_roles = $user_info->roles;
			$user_roles_to_check = explode( ',', $atts['order_user_roles'] );
			foreach ( $user_roles_to_check as $user_role_to_check ) {
				if ( in_array( $user_role_to_check, $user_roles ) ) {
					return true;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * wcj_price_shortcode.
	 *
	 * @version 3.1.2
	 */
	private function wcj_price_shortcode( $raw_price, $atts ) {
		if ( 'yes' === $atts['hide_zero_prices'] && 0 == $raw_price ) {
			return '';
		}
		return wcj_price( $atts['price_prefix'] . $raw_price, wcj_get_order_currency( $this->the_order ), $atts['hide_currency'] );
	}

	/**
	 * add_item.
	 *
	 * @version 2.8.0
	 */
	private function add_item( $items, $new_item_args = array() ) {
		if ( empty ( $new_item_args ) ) {
			return $items;
		}
		extract( $new_item_args );
		// Create item
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			$item = array(
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
		} else {
			if ( 'shipping' === $type ) {
				$item = new WC_Order_Item_Shipping();
				$item->set_props( array(
					'method_title' => $name,
					'method_id'    => '',
					'total'        => wc_format_decimal( $line_total ),
					'total_tax'    => $line_tax,
					'taxes'        => array(
						'total' => array( $line_tax ),
					),
				) );
			} else { // ( 'discount' === $type )
				$item = new WC_Order_Item_Fee();
				$item->set_props( array(
					'name'         => $name,
					'total'        => wc_format_decimal( $line_total ),
					'total_tax'    => $line_tax,
					'tax_class'    => '',
					'tax_status'   => 'taxable',
					'taxes'        => array(
						'total' => array( $line_tax ),
					),
				) );
			}
		}
		$items[] = $item;
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
	 * @version 3.1.2
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
		$the_items = array();
		if ( 'yes' === $atts['refunded_items_table'] ) {
			foreach ( $this->the_order->get_refunds() as $_refund ) {
				$the_items = array_merge( $the_items, $_refund->get_items() );
			}
		} else {
			$the_items = $the_order->get_items();
		}

		// Shipping as item
		if ( '' != $atts['shipping_as_item'] && $the_order->get_total_shipping() > 0 ) {
			$name                    = str_replace( '%shipping_method_name%', $the_order->get_shipping_method(), $atts['shipping_as_item'] );
			$total_shipping_tax_excl = $the_order->get_total_shipping();
			$shipping_tax            = $the_order->get_shipping_tax();

			$the_items = $this->add_item( $the_items, array( 'name' => $name, 'qty' => 1, 'line_subtotal' => $total_shipping_tax_excl, 'line_total' => $total_shipping_tax_excl, 'line_tax' => $shipping_tax, 'line_subtotal_tax' => $shipping_tax, 'type' => 'shipping' ) );
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

			$the_items = $this->add_item( $the_items, array( 'name' => $name, 'qty' => 1, 'line_subtotal' => $total_discount_tax_excl, 'line_total' => $total_discount_tax_excl, 'line_tax' => $discount_tax, 'line_subtotal_tax' => $discount_tax, 'type' => 'discount' ) );
		}

		// Items to data[]
		$data = array();
		$item_counter = 0;
		foreach ( $the_items as $item_id => $item ) {
			$item['is_custom'] = ( isset( $item['is_custom'] ) ) ? true : false; // $item['is_custom'] may be defined only if WCJ_IS_WC_VERSION_BELOW_3
			$the_product = ( true === $item['is_custom'] ) ? null : $the_order->get_product_from_item( $item );
			// Check if it's not excluded by category
			if ( '' != $atts['exclude_by_categories'] && $the_product ) {
				if ( wcj_product_has_terms( $the_product, $atts['exclude_by_categories'], 'product_cat' ) ) {
					continue;
				}
			}
			// Check if it's not excluded by tag
			if ( '' != $atts['exclude_by_tags'] && $the_product ) {
				if ( wcj_product_has_terms( $the_product, $atts['exclude_by_tags'], 'product_tag' ) ) {
					continue;
				}
			}
			// Check if it's not excluded by product attribute
			if ( $the_product && '' != $atts['exclude_by_attribute__name'] /* && '' != $atts['exclude_by_attribute__value'] */ ) {
				$product_attributes = $the_product->get_attributes();
				if ( isset( $product_attributes[ $atts['exclude_by_attribute__name'] ] ) ) {
					$product_attribute = $product_attributes[ $atts['exclude_by_attribute__name'] ];
					if ( is_object( $product_attribute ) ) {
						if ( 'WC_Product_Attribute' === get_class( $product_attribute ) && in_array( $atts['exclude_by_attribute__value'], $product_attribute->get_options() ) ) {
							continue;
						}
					} elseif ( $atts['exclude_by_attribute__value'] === $product_attribute ) {
						continue;
					}
				}
			}
			$item_counter++;
			// Columns
			foreach( $columns as $column ) {
				$column_param = '';
				if ( false !== ( $pos = strpos( $column, '=' ) ) ) {
					$column_param = substr( $column, $pos + 1 );
					$column       = substr( $column, 0, $pos );
				}
				$cell_data = '';
				switch ( $column ) {
					case 'item_debug':
					case 'debug':
						$cell_data = print_r( $item, true );
						break;
					case 'item_regular_price':
					case 'product_regular_price':
						$cell_data = ( is_object( $the_product ) ) ? $this->wcj_price_shortcode( $the_product->get_regular_price(), $atts ) : '';
						break;
					case 'item_sale_price':
					case 'product_sale_price':
						$cell_data = ( is_object( $the_product ) ) ? $this->wcj_price_shortcode( $the_product->get_sale_price(), $atts ) : '';
						break;
					case 'item_regular_price_multiply_qty':
					case 'product_regular_price_multiply_qty':
						$cell_data = ( is_object( $the_product ) ) ? $this->wcj_price_shortcode( $the_product->get_regular_price() * $item['qty'], $atts ) : '';
						break;
					case 'item_sale_price_multiply_qty':
					case 'product_sale_price_multiply_qty':
						$cell_data = ( is_object( $the_product ) ) ? $this->wcj_price_shortcode( $the_product->get_sale_price() * $item['qty'], $atts ) : '';
						break;
					case 'product_categories':
						$cell_data = ( is_object( $the_product ) ) ?
							strip_tags( ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_product->get_categories() : wc_get_product_category_list( $item['product_id'] ) ) ) :
							'';
						break;
					case 'item_tax_class':
					case 'tax_class':
						$cell_data = ( isset( $item['tax_class'] ) ) ? $this->get_tax_class_name( $item['tax_class'] ) : '';
						break;
					case 'item_number':
						$cell_data = $item_counter;
						break;
					case 'item_meta':
						$cell_data = wcj_get_order_item_meta_info( $item_id, $item, $this->the_order, false, $the_product );
						break;
					case 'item_name':
					case 'product_name': // "product_" because of possible variation
						if ( true === $item['is_custom'] ) {
							$cell_data = $item['name'];
						} else {
							$the_item_title = $item['name'];
							// Variation (if needed)
							if ( 'yes' === $atts['add_variation_info_to_item_name'] && isset( $item['variation_id'] ) && 0 != $item['variation_id'] && ! in_array( 'item_variation', $columns ) ) {
								$the_item_title .= '<div style="' . $atts['style_item_name_variation'] . '">';
								if ( 'yes' === $atts['variation_as_metadata'] ) {
									$the_item_title .= wcj_get_order_item_meta_info( $item_id, $item, $this->the_order, true, $the_product );
								} elseif ( is_object( $the_product ) && $the_product->is_type( 'variation' ) ) {
									$the_item_title .= str_replace( 'pa_', '', urldecode( wcj_get_product_formatted_variation( $the_product, true ) ) ); // todo - do we need pa_ replacement?
								}
								$the_item_title .= '</div>';
							}
							// "WooCommerce TM Extra Product Options" plugin options
							// todo - this will show options prices in shop's default currency only (must use 'price_per_currency' to show prices in order's currency).
							$tmcartepo_data = ( WCJ_IS_WC_VERSION_BELOW_3 ?
								( isset( $item['tmcartepo_data'] ) ? maybe_unserialize( $item['tmcartepo_data'] ) : '' ) :
								$item->get_meta( '_tmcartepo_data' )
							);
							if ( ! empty( $tmcartepo_data ) ) {
								$options_prices = array();
//								$order_currency = wcj_get_order_currency( $the_order );
								foreach ( $tmcartepo_data as $option ) {
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
							$cell_data = $the_item_title;
						}
						break;
					case 'item_product_input_fields':
						$cell_data = wcj_get_product_input_fields( $item );
						break;
					case 'item_product_addons':
						$cell_data = wcj_get_product_addons( $item, wcj_get_order_currency( $this->the_order ) );
						break;
					case 'item_key':
						if ( isset( $column_param ) && '' != $column_param && isset( $item[ $column_param ] ) ) {
							$maybe_unserialized_value = maybe_unserialize( $item[ $column_param ] );
							if ( is_array( $maybe_unserialized_value ) ) {
								$cell_data = isset( $maybe_unserialized_value['name'] ) ? $maybe_unserialized_value['name'] : '';
							} else {
								$cell_data = $maybe_unserialized_value;
							}
						} else {
							$cell_data = '';
						}
						break;
					case 'item_attribute':
					case 'product_attribute':
						if ( isset( $column_param ) && '' != $column_param && is_object( $the_product ) ) {
							$cell_data = $the_product->get_attribute( $column_param );
							if ( '' === $cell_data && $the_product->is_type( 'variation' ) ) {
								$parent_product = wc_get_product( $the_product->get_parent_id() );
								$cell_data = $parent_product->get_attribute( $column_param );
							}
						} else {
							$cell_data = '';
						}
						break;
					case 'item_excerpt':
					case 'product_excerpt':
						if ( true === $item['is_custom'] || ! isset( $item['product_id'] ) ) {
							$cell_data = '';
						} else {
							global $post;
							$post = get_post( $item['product_id'] );
							setup_postdata( $post );
							$the_excerpt = get_the_excerpt();
							wp_reset_postdata();
							$cell_data = $the_excerpt;
						}
						break;
					case 'item_short_description':
					case 'product_short_description':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_product->post->post_excerpt : $the_product->get_short_description() );
						break;
					case 'item_variation':
					case 'product_variation':
						if ( 0 != $item['variation_id'] ) {
							if ( 'yes' === $atts['variation_as_metadata'] ) {
								$cell_data = wcj_get_order_item_meta_info( $item_id, $item, $this->the_order, true, $the_product );
							} elseif ( is_object( $the_product ) && $the_product->is_type( 'variation' ) ) {
								$cell_data = str_replace( 'pa_', '', urldecode( wcj_get_product_formatted_variation( $the_product, true ) ) ); // todo - do we need pa_ replacement?
							}
						} else {
							$cell_data = '';
						}
						break;
					case 'item_thumbnail':
					case 'product_thumbnail':
//						$cell_data = $the_product->get_image();
						$image_id = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? 0 : $the_product->get_image_id();
						$image_src = ( 0 != $image_id ) ? wp_get_attachment_image_src( $image_id ) : wc_placeholder_img_src();
						if ( is_array( $image_src ) ) $image_src = $image_src[0];
						$maybe_width  = ( 0 != $atts['product_image_width'] )  ? ' width="'  . $atts['product_image_width']  . '"' : '';
						$maybe_height = ( 0 != $atts['product_image_height'] ) ? ' height="' . $atts['product_image_height'] . '"' : '';
						$cell_data = '<img src="' . $image_src . '"' . $maybe_width . $maybe_height . '>';
						break;
					case 'item_sku':
					case 'product_sku':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_sku();
						break;
					case 'item_quantity':
						$cell_data = $atts['quantity_prefix'] . $item['qty'];
						break;
					case 'item_quantity_refunded':
						$cell_data = ( ! $item['is_custom'] && $item_id ? $this->the_order->get_qty_refunded_for_item( $item_id ) : '' );
						break;
					case 'item_quantity_excl_refunded':
						$cell_data = ( ! $item['is_custom'] && $item_id ? ( $item['qty'] + $this->the_order->get_qty_refunded_for_item( $item_id ) ) : '' );
						break;
					case 'item_total_refunded':
						$cell_data = ( ! $item['is_custom'] && $item_id ? $this->wcj_price_shortcode( $this->the_order->get_total_refunded_for_item( $item_id ), $atts ) : '' );
						break;
					case 'item_total_tax_excl':
						$cell_data = $this->wcj_price_shortcode( $the_order->get_item_total( $item, false, true ), $atts );
						break;
					case 'item_total_tax_incl':
						$cell_data = $this->wcj_price_shortcode( $the_order->get_item_total( $item, true, true ), $atts );
						break;
					case 'item_subtotal_tax_excl':
						$cell_data = $this->wcj_price_shortcode( $the_order->get_item_subtotal( $item, false, true ), $atts );
						break;
					case 'item_subtotal_tax_incl':
						$cell_data = $this->wcj_price_shortcode( $the_order->get_item_subtotal( $item, true, true ), $atts );
						break;
					case 'item_tax':
						$cell_data = $this->wcj_price_shortcode( $the_order->get_item_tax( $item, true ), $atts );
						break;
					case 'line_total_tax_excl':
						$line_total_tax_excl = $the_order->get_line_total( $item, false, true );
						$line_total_tax_excl = apply_filters( 'wcj_line_total_tax_excl', $line_total_tax_excl, $the_order );
						$cell_data = $this->wcj_price_shortcode( $line_total_tax_excl, $atts );
						break;
					case 'line_total_tax_incl':
						$cell_data = $this->wcj_price_shortcode( $the_order->get_line_total( $item, true, true ), $atts );
						break;
					case 'line_subtotal_tax_excl':
						$cell_data = $this->wcj_price_shortcode( $the_order->get_line_subtotal( $item, false, true ), $atts );
						break;
					case 'line_subtotal_tax_incl':
						$cell_data = $this->wcj_price_shortcode( $the_order->get_line_subtotal( $item, true, true ), $atts );
						break;
					case 'line_tax':
						$line_tax = $the_order->get_line_tax( $item );
						$line_tax = apply_filters( 'wcj_line_tax', $line_tax, $the_order );
						$cell_data = $this->wcj_price_shortcode( $line_tax, $atts );
						break;
					case 'line_subtax':
						$line_subtax = $the_order->get_line_subtotal( $item, true, false ) - $the_order->get_line_subtotal( $item, false, false );
						$cell_data = $this->wcj_price_shortcode( $line_subtax, $atts );
						break;
					case 'item_tax_percent':
					case 'line_tax_percent':
						$item_total = $the_order->get_item_total( $item, false, /* true */ false );
						$item_tax_percent = ( 0 != $item_total ) ? $the_order->get_item_tax( $item, false ) / $item_total * 100 : 0;
						$item_tax_percent = apply_filters( 'wcj_line_tax_percent', $item_tax_percent, $the_order );
						$cell_data = sprintf( $atts['tax_percent_format'], $item_tax_percent );
						/* $tax_labels = array();
						foreach ( $the_order->get_taxes() as $the_tax ) {
							$tax_labels[] = $the_tax['label'];
						}
						$cell_data = implode( ', ', $tax_labels ); */
						break;
					/* case 'line_tax_percent':
						$line_total = $the_order->get_line_total( $item, false, true );
						$line_tax_percent = ( 0 != $line_total ) ? $the_order->get_line_tax( $item ) / $line_total * 100 : 0;
						$line_tax_percent = apply_filters( 'wcj_line_tax_percent', $line_tax_percent, $the_order );
						$cell_data = sprintf( $atts['tax_percent_format'], $line_tax_percent );
						break; */
					case 'item_weight':
					case 'product_weight':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_weight();
						break;
					case 'item_width':
					case 'product_width':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_width();
						break;
					case 'item_height':
					case 'product_height':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_height();
						break;
					case 'item_length':
					case 'product_length':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_length();
						break;
					case 'product_cost':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' :
							wc_price( $atts['multiply_cost'] * wc_get_product_purchase_price( $the_product->get_id() ) );
						break;
					case 'product_profit':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' :
							wc_price( $atts['multiply_profit'] * ( $the_product->get_price() - wc_get_product_purchase_price( $the_product->get_id() ) ) );
						break;
					case 'line_cost':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' :
							wc_price( $atts['multiply_cost'] * $item['qty'] * wc_get_product_purchase_price( $the_product->get_id() ) );
						break;
					case 'line_profit':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' :
							wc_price( $atts['multiply_profit'] * $item['qty'] * ( $the_product->get_price() - wc_get_product_purchase_price( $the_product->get_id() ) ) );
						break;
					case 'product_id':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ? '' : $the_product->get_id();
						break;
					case 'item_product_id':
						$cell_data = ( true === $item['is_custom'] ) ? '' : $item['product_id'];
						break;
					case 'product_meta':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) || ! isset( $column_param ) || '' == $column_param ) ?
							'' : $the_product->get_meta( $column_param );
					case 'product_post_meta':
						$cell_data = ( true === $item['is_custom'] || ! isset( $column_param ) || '' == $column_param ) ?
							'' : get_post_meta( $item['product_id'], $column_param, true );
					case 'product_purchase_note':
						$cell_data = ( true === $item['is_custom'] || ! is_object( $the_product ) ) ?
							'' : $the_product->get_purchase_note();
						break;
					default:
						$cell_data = ''; // $column;
				}
				$data[ $item_counter ][] = apply_filters( 'wcj_pdf_invoicing_cell_data', $cell_data, array(
					'column'       => $column,
					'column_param' => $column_param,
					'item'         => $item,
					'item_id'      => $item_id,
					'item_counter' => $item_counter,
					'product'      => $the_product,
					'order'        => $this->the_order,
				) );
			}
		}

		if ( empty( $data ) ) {
			return '';
		}

		$table_html_args = array(
			'table_class'        => $atts['table_class'],
			'table_heading_type' => 'horizontal',
			'columns_classes'    => array(),
			'columns_styles'     => $columns_styles,
		);
		if ( '' != $atts['insert_page_break'] ) {
			$page_breaks  = explode ( '|', $atts['insert_page_break'] );
			$data_size    = count( $data );
			$slice_offset = 0;
			$html         = '';
			$slices       = 0;
			while ( $slice_offset < $data_size ) {
				if ( 0 != $slice_offset ) {
					$html .= '<tcpdf method="AddPage" />';
				}
				if ( isset( $page_breaks[ $slices ] ) ) {
					$current_page_break = $page_breaks[ $slices ];
				}
				$data_slice = array_slice( $data, $slice_offset, $current_page_break );
				$html .= wcj_get_table_html( array_merge( array( $columns_titles ), $data_slice ), $table_html_args );
				$slice_offset += $current_page_break;
				$slices++;
			}
		} else {
			$html = wcj_get_table_html( array_merge( array( $columns_titles ), $data ), $table_html_args );
		}

		return $html;
	}
}

endif;

return new WCJ_Order_Items_Shortcodes();

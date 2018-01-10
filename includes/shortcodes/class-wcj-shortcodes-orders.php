<?php
/**
 * Booster for WooCommerce - Shortcodes - Orders
 *
 * @version 3.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Orders_Shortcodes' ) ) :

class WCJ_Orders_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 3.3.0
	 */
	function __construct() {

		$this->the_shortcodes = array(
			'wcj_order_billing_address',
			'wcj_order_billing_country_name',
			'wcj_order_billing_phone',
			'wcj_order_checkout_field',
			'wcj_order_coupons',
			'wcj_order_currency',
			'wcj_order_custom_field',
			'wcj_order_custom_meta_field',
			'wcj_order_customer_meta',
			'wcj_order_customer_note',
			'wcj_order_customer_user',
			'wcj_order_customer_user_roles',
			'wcj_order_date',
			'wcj_order_fee',
			'wcj_order_fees_html',
			'wcj_order_function',
			'wcj_order_id',
			'wcj_order_items',
			'wcj_order_items_cost',
			'wcj_order_items_meta',
			'wcj_order_items_total_number',
			'wcj_order_items_total_quantity',
			'wcj_order_items_total_weight', // deprecated - use 'wcj_order_total_weight' instead
			'wcj_order_meta',
			'wcj_order_number',
			'wcj_order_payment_method',
			'wcj_order_payment_method_transaction_id',
			'wcj_order_profit',
			'wcj_order_refunds_table',
			'wcj_order_remaining_refund_amount',
			'wcj_order_shipping_address',
			'wcj_order_shipping_country_name',
			'wcj_order_shipping_method',
			'wcj_order_shipping_price',
			'wcj_order_shipping_tax',
			'wcj_order_status',
			'wcj_order_status_label',
			'wcj_order_subtotal',
			'wcj_order_subtotal_by_tax_class',
			'wcj_order_subtotal_plus_shipping',
			'wcj_order_subtotal_to_display',
			'wcj_order_tax_by_class',
			'wcj_order_taxes_html',
			'wcj_order_tcpdf_barcode',
			'wcj_order_time',
			'wcj_order_total',
			'wcj_order_total_after_refund',
			'wcj_order_total_by_tax_class',
			'wcj_order_total_discount',
			'wcj_order_total_excl_shipping',
			'wcj_order_total_excl_tax',
			'wcj_order_total_fees',
			'wcj_order_total_fees_incl_tax',
			'wcj_order_total_fees_tax',
			'wcj_order_total_formatted',
			'wcj_order_total_height',
			'wcj_order_total_in_words',
			'wcj_order_total_length',
			'wcj_order_total_shipping_refunded',
			'wcj_order_total_refunded',
			'wcj_order_total_tax',
			'wcj_order_total_tax_after_refund',
			'wcj_order_total_tax_percent',
			'wcj_order_total_tax_refunded',
			'wcj_order_total_weight',
			'wcj_order_total_width',
//			'wcj_order_cart_discount',
		);

		parent::__construct();
	}

	/**
	 * add_extra_atts.
	 *
	 * @version 3.3.0
	 */
	function add_extra_atts( $atts ) {
		$modified_atts = array_merge( array(
			'order_id'                    => 0,
			'hide_currency'               => 'no',
			'excl_tax'                    => 'no',
			'date_format'                 => get_option( 'date_format' ),
			'time_format'                 => get_option( 'time_format' ),
			'hide_if_zero'                => 'no',
			'field_id'                    => '',
			'name'                        => '',
			'round_by_line'               => 'no',
			'whole'                       => __( 'Dollars', 'woocommerce-jetpack' ),
			'decimal'                     => __( 'Cents', 'woocommerce-jetpack' ),
			'precision'                   => get_option( 'woocommerce_price_num_decimals', 2 ),
			'lang'                        => 'EN',
			'unique_only'                 => 'no',
			'function_name'               => '',
			'sep'                         => ', ',
			'item_number'                 => 'all',
			'field'                       => 'name',
			'order_user_roles'            => '',
			'meta_key'                    => '',
			'tax_class'                   => '',
			'fallback_billing_address'    => 'no',
			'tax_display'                 => '',
			'table_class'                 => '',
			'columns_styles'              => '',
			'columns_titles'              => '',
			'columns'                     => '',
			'price_prefix'                => '',
			'display_refunded'            => 'yes',
			'insert_page_break'           => '',
			'key'                         => null,
			'days'                        => 0,
			'code'                        => '',
			'type'                        => '',
			'dimension'                   => '2D',
			'width'                       => 0,
			'height'                      => 0,
			'color'                       => 'black',
			'currency'                    => '',
			'doc_type'                    => 'invoice',
		), $atts );

		return $modified_atts;
	}

	/**
	 * init_atts.
	 *
	 * @todo    (maybe) `if ( 'shop_order' !== get_post_type( $atts['order_id'] ) ) return false;`
	 */
	function init_atts( $atts ) {

		// Atts
		$atts['excl_tax'] = ( 'yes' === $atts['excl_tax'] );

		if ( 0 == $atts['order_id'] ) {
			$atts['order_id'] = ( isset( $_GET['order_id'] ) ) ? $_GET['order_id'] : get_the_ID();
		}
		if ( 0 == $atts['order_id'] ) {
			$atts['order_id'] = ( isset( $_GET['pdf_invoice'] ) ) ? $_GET['pdf_invoice'] : 0; // PDF Invoices V1 compatibility
		}
		if ( 0 == $atts['order_id'] ) {
			return false;
		}

		// Class properties
		$this->the_order = ( 'shop_order' === get_post_type( $atts['order_id'] ) ) ? wc_get_order( $atts['order_id'] ) : null;
		if ( ! $this->the_order ) {
			return false;
		}

		return $atts;
	}

	/**
	 * extra_check.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function extra_check( $atts ) {
		if ( '' != $atts['order_user_roles'] ) {
			$user_info = get_userdata( ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() ) );
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
	 * @version 3.3.0
	 */
	private function wcj_price_shortcode( $raw_price, $atts ) {
		if ( 'yes' === $atts['hide_if_zero'] && 0 == $raw_price ) {
			return '';
		} else {
			$order_currency = wcj_get_order_currency( $this->the_order );
			if ( '' === $atts['currency'] ) {
				return wcj_price( $raw_price, $order_currency, $atts['hide_currency'] );
			} else {
				$convert_to_currency = $atts['currency'];
				if ( '%shop_currency%' === $convert_to_currency ) {
					$convert_to_currency = get_option( 'woocommerce_currency' );
				}
				return wcj_price( $raw_price * wcj_get_saved_exchange_rate( $order_currency, $convert_to_currency ), $convert_to_currency, $atts['hide_currency'] );
			}
		}
	}

	/**
	 * wcj_order_items_cost.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function wcj_order_items_cost( $atts ) {
		$atts['type'] = 'items_cost';
		return $this->wcj_order_profit( $atts );
	}

	/**
	 * wcj_order_profit.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function wcj_order_profit( $atts ) {
		$total = 0;
		foreach ( $this->the_order->get_items() as $item_id => $item ) {
			$product_id = ( ( isset( $item['variation_id'] ) && 0 != $item['variation_id'] && 'no' === get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' ) )
				? $item['variation_id'] : $item['product_id'] );
			$value = 0;
			if ( 0 != ( $purchase_price = wc_get_product_purchase_price( $product_id ) ) ) {
				if ( 'profit' === $atts['type'] || '' === $atts['type'] ) {
					// profit
					$_order_prices_include_tax = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->prices_include_tax : $this->the_order->get_prices_include_tax() );
					$line_total                = ( $_order_prices_include_tax ? ( $item['line_total'] + $item['line_tax'] ) : $item['line_total'] );
					$value                     = $line_total - $purchase_price * $item['qty'];
				} else {
					// 'items_cost'
					$value                     = $purchase_price * $item['qty'];
				}
			}
			$total += $value;
		}
		return $this->wcj_price_shortcode( $total, $atts );
	}

	/**
	 * wcj_order_tcpdf_barcode.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function wcj_order_tcpdf_barcode( $atts ) {
		switch ( $atts['code'] ) {
			case '%url%':
				$atts['code'] = $this->the_order->get_view_order_url();
				break;
			case '%id%':
				$atts['code'] = $atts['order_id'];
				break;
			case '%doc_number%':
				$atts['code'] = wcj_get_invoice_number( $atts['order_id'], $atts['doc_type'] );
				break;
			case '%meta%':
				$atts['code'] = get_post_meta( $atts['order_id'], $atts['meta_key'], true );
				break;
			default:
				return '';
		}
		return wcj_tcpdf_barcode( $atts );
	}

	/**
	 * wcj_order_total_formatted.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function wcj_order_total_formatted( $atts ) {
		return $this->the_order->get_formatted_order_total( $atts['tax_display'], ( 'yes' === $atts['display_refunded'] ) );
	}

	/**
	 * wcj_order_remaining_refund_amount.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function wcj_order_remaining_refund_amount( $atts ) {
		return $this->wcj_price_shortcode( $this->the_order->get_remaining_refund_amount(), $atts );
	}

	/**
	 * wcj_order_refunds_table.
	 *
	 * @version 3.1.2
	 * @since   3.1.0
	 * @todo    add `refund_items_or_reason_or_title` column
	 * @todo    add `refund_items_quantities` column (`$_item->get_quantity()`)
	 * @todo    check `$atts['columns']` etc. before starting
	 * @todo    (maybe) move to new `class-wcj-orders-refunded-shortcodes.php` file
	 */
	function wcj_order_refunds_table( $atts ) {
		$columns    = ( '' == $atts['columns'] ? array() : explode( '|', $atts['columns'] ) );
		$table_data = array();
		$i          = 1;
		foreach ( $this->the_order->get_refunds() as $_refund ) {
			$row = array();
			foreach ( $columns as $column ) {
				$cell = '';
				switch ( $column ) {
					case 'refund_number':
						$cell = $i;
						break;
					case 'refund_title':
						$cell = $_refund->get_post_title();
						break;
					case 'refund_reason':
						$cell = $_refund->get_reason();
						break;
					case 'refund_reason_or_title':
						$reason = $_refund->get_reason();
						$cell = ( '' != $reason ? $reason : $_refund->get_post_title() );
						break;
					case 'refund_amount':
						$cell = $atts['price_prefix'] . $_refund->get_formatted_refund_amount();
						break;
					case 'refund_items':
						$_items = array();
						foreach ( $_refund->get_items() as $_item ) {
							$_items[] = $_item->get_name() . ' x ' . $_item->get_quantity() * -1;
						}
						$cell = ( ! empty( $_items ) ? implode( '<br>', $_items ) : '' );
						break;
				}
				$row[] = $cell;
			}
			$i++;
			$table_data[] = $row;
		}
		if ( empty( $table_data ) ) {
			return '';
		}
		$table_html_args = array(
			'table_class'        => $atts['table_class'],
			'columns_classes'    => array(),
			'columns_styles'     => ( '' == $atts['columns_styles'] ? array() : explode( '|', $atts['columns_styles'] ) ),
		);
		$columns_titles = array( ( '' == $atts['columns_titles'] ? array() : explode( '|', $atts['columns_titles'] ) ) );
		if ( '' != $atts['insert_page_break'] ) {
			$page_breaks  = explode ( '|', $atts['insert_page_break'] );
			$data_size    = count( $table_data );
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
				$data_slice    = array_slice( $table_data, $slice_offset, $current_page_break );
				$html         .= wcj_get_table_html( array_merge( $columns_titles, $data_slice ), $table_html_args );
				$slice_offset += $current_page_break;
				$slices++;
			}
		} else {
			$html = wcj_get_table_html( array_merge( $columns_titles, $table_data ), $table_html_args );
		}
		return $html;
	}

	/**
	 * wcj_order_total_tax_refunded.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function wcj_order_total_tax_refunded( $atts ) {
		return $this->wcj_price_shortcode( $this->the_order->get_total_tax_refunded(), $atts );
	}

	/**
	 * wcj_order_total_shipping_refunded.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function wcj_order_total_shipping_refunded( $atts ) {
		return $this->wcj_price_shortcode( $this->the_order->get_total_shipping_refunded(), $atts );
	}

	/**
	 * wcj_order_total_refunded.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_order_total_refunded( $atts ) {
		return $this->wcj_price_shortcode( $this->the_order->get_total_refunded(), $atts );
	}

	/**
	 * wcj_order_customer_meta.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wcj_order_customer_meta( $atts ) {
		if ( '' != $atts['key'] && ( $_customer_id = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() ) ) ) {
			if ( '' != ( $meta = get_user_meta( $_customer_id, $atts['key'], true ) ) ) {
				return $meta;
			}
		}
		return '';
	}

	/**
	 * wcj_order_customer_user_roles.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function wcj_order_customer_user_roles( $atts ) {
		$user_info = get_userdata( ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() ) );
		return implode( ', ', $user_info->roles );
	}

	/**
	 * wcj_order_customer_user.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function wcj_order_customer_user( $atts ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() );
	}

	/**
	 * wcj_order_coupons.
	 *
	 * @version 2.5.8
	 * @since   2.5.8
	 */
	function wcj_order_coupons( $atts ) {
		return implode( ', ', $this->the_order->get_used_coupons() );
	}

	/**
	 * wcj_order_function.
	 *
	 * @version 2.5.8
	 * @since   2.5.6
	 * @todo    add function_params attribute.
	 * @todo    fix when returning array of arrays or object etc.
	 */
	function wcj_order_function( $atts ) {
		$function_name = $atts['function_name'];
		if ( '' != $function_name && method_exists( $this->the_order, $function_name ) ) {
			$return = $this->the_order->$function_name();
			return ( is_array( $return ) ) ? implode( ', ', $return ) : $return;
		}
	}

	/**
	 * Get order custom field.
	 *
	 * @version 3.2.0
	 * @since   2.4.8
	 * @return  string
	 */
	function wcj_order_custom_field( $atts ) {
		$order_custom_fields = get_post_custom( $atts['order_id'] );
		$return = ( isset( $order_custom_fields[ $atts['name'] ][0] ) ) ? $order_custom_fields[ $atts['name'] ][0] : '';
		if ( null !== $atts['key'] ) {
			$return = maybe_unserialize( $return );
			return ( isset( $return[ $atts['key'] ] ) ? $return[ $atts['key'] ] : '' );
		}
		return $return;
	}

	/**
	 * wcj_order_total_fees.
	 */
	function wcj_order_total_fees( $atts ) {
		$total_fees = 0;
		$the_fees = $this->the_order->get_fees();
		foreach ( $the_fees as $the_fee ) {
			$total_fees += $the_fee['line_total'];
		}
		return $this->wcj_price_shortcode( $total_fees, $atts );
	}

	/**
	 * wcj_order_total_fees_tax.
	 *
	 * @version 2.5.2
	 * @since   2.4.8
	 */
	function wcj_order_total_fees_tax( $atts ) {
		$total_fees_tax = 0;
		$the_fees = $this->the_order->get_fees();
		foreach ( $the_fees as $the_fee ) {
			/* $taxes = maybe_unserialize( $the_fee['line_tax_data'] );
			if ( ! empty( $taxes ) && is_array( $taxes ) && isset( $taxes['total'] ) && is_array( $taxes['total'] ) ) {
				foreach ( $taxes['total'] as $tax ) {
					$total_fees_tax += $tax;
				}
			} */
			$total_fees_tax += $this->the_order->get_line_tax( $the_fee );
		}
		return $this->wcj_price_shortcode( $total_fees_tax, $atts );
	}

	/**
	 * wcj_order_total_fees_incl_tax.
	 *
	 * @version 2.5.2
	 * @since   2.4.8
	 * @todo    probably should use get_line_total
	 */
	function wcj_order_total_fees_incl_tax( $atts ) {
		$total_fees = 0;
		$the_fees = $this->the_order->get_fees();
		foreach ( $the_fees as $the_fee ) {
			$total_fees += $the_fee['line_total'];
			/* $taxes = maybe_unserialize( $the_fee['line_tax_data'] );
			if ( ! empty( $taxes ) && is_array( $taxes ) && isset( $taxes['total'] ) && is_array( $taxes['total'] ) ) {
				foreach ( $taxes['total'] as $tax ) {
					$total_fees += $tax;
				}
			} */
			$total_fees += $this->the_order->get_line_tax( $the_fee );
		}
		return $this->wcj_price_shortcode( $total_fees, $atts );
	}

	/**
	 * wcj_order_fees_html.
	 */
	function wcj_order_fees_html( $atts ) {
		$fees_html = '';
		$the_fees = $this->the_order->get_fees();
		foreach ( $the_fees as $the_fee ) {
			$fees_html .= '<p>' . $the_fee['name'] . ' - ' . $the_fee['line_total'] . '</p>';
		}
		return $fees_html;
	}

	/**
	 * wcj_order_fee.
	 */
	function wcj_order_fee( $atts ) {
		if ( '' == $atts['name'] ) return '';
		$the_fees = $this->the_order->get_fees();
		foreach ( $the_fees as $the_fee ) {
			if ( $atts['name'] == $the_fee['name'] ) {
				return $this->wcj_price_shortcode( $the_fee['line_total'], $atts );
			}
		}
		return '';
	}
	/**
	 * wcj_order_shipping_method.
	 */
	function wcj_order_shipping_method( $atts ) {
		return $this->the_order->get_shipping_method();
	}

	/**
	 * wcj_order_payment_method_transaction_id.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function wcj_order_payment_method_transaction_id( $atts ) {
		return $this->the_order->get_transaction_id();
	}

	/**
	 * wcj_order_payment_method.
	 *
	 * @version 2.7.0
	 */
	function wcj_order_payment_method( $atts ) {
		return get_post_meta( wcj_get_order_id( $this->the_order ), '_payment_method_title', true );
	}

	/**
	 * wcj_order_total_width
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function wcj_order_total_width( $atts ) {
		return $this->get_order_total( $atts, 'width' );
	}

	/**
	 * wcj_order_total_height
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function wcj_order_total_height( $atts ) {
		return $this->get_order_total( $atts, 'height' );
	}

	/**
	 * wcj_order_total_length
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function wcj_order_total_length( $atts ) {
		return $this->get_order_total( $atts, 'length' );
	}

	/**
	 * wcj_order_total_weight
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function wcj_order_total_weight( $atts ) {
		return $this->get_order_total( $atts, 'weight' );
	}

	/**
	 * get_order_total
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_order_total( $atts, $param ) {
		$total = 0;
		$the_items = $this->the_order->get_items();
		foreach ( $the_items as $item_id => $item ) {
			$product_id = ( 0 != $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
			$_product = wc_get_product( $product_id );
			if ( $_product ) {
				switch ( $param ) {
					case 'width':
						$total += ( $item['qty'] * $_product->get_width() );
						break;
					case 'height':
						$total += ( $item['qty'] * $_product->get_height() );
						break;
					case 'length':
						$total += ( $item['qty'] * $_product->get_length() );
						break;
					case 'weight':
						$total += ( $item['qty'] * $_product->get_weight() );
						break;
				}
			}
		}
		return ( 0 == $total && 'yes' === $atts['hide_if_zero'] ) ? '' : $total;
	}

	/**
	 * wcj_order_items_total_weight.
	 *
	 * @version    2.5.7
	 * @deprecated 2.5.7
	 */
	function wcj_order_items_total_weight( $atts ) {
		return $this->get_order_total( $atts, 'weight' );
	}

	/**
	 * wcj_order_items_total_quantity.
	 */
	function wcj_order_items_total_quantity( $atts ) {
		$total_quantity = 0;
		$the_items = $this->the_order->get_items();
		foreach( $the_items as $the_item ) {
			$total_quantity += $the_item['qty'];
		}
		return ( 0 == $total_quantity && 'yes' === $atts['hide_if_zero'] ) ? '' : $total_quantity;
	}

	/**
	 * wcj_order_items_total_number.
	 */
	function wcj_order_items_total_number( $atts ) {
		$total_number = count( $this->the_order->get_items() );
		return ( 0 == $total_number && 'yes' === $atts['hide_if_zero'] ) ? '' : $total_number;
	}

	/**
	 * wcj_order_billing_address.
	 *
	 * @version 2.3.9
	 */
	function wcj_order_billing_address( $atts ) {
		return apply_filters( 'wcj_order_billing_address', $this->the_order->get_formatted_billing_address(), $atts );
	}

	/**
	 * wcj_order_customer_note.
	 *
	 * @version 2.7.0
	 */
	function wcj_order_customer_note( $atts ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_note : $this->the_order->get_customer_note() );
	}

	/**
	 * wcj_order_billing_country_name.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function wcj_order_billing_country_name( $atts ) {
		$country_code = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->billing_country : $this->the_order->get_billing_country() );
		if ( false !== ( $country_name = wcj_get_country_name_by_code( $country_code ) ) ) {
			return $country_name;
		} else {
			return $country_code;
		}
	}

	/**
	 * wcj_order_shipping_country_name.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function wcj_order_shipping_country_name( $atts ) {
		$country_code = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->shipping_country : $this->the_order->get_shipping_country() );
		if ( false !== ( $country_name = wcj_get_country_name_by_code( $country_code ) ) ) {
			return $country_name;
		} else {
			return $country_code;
		}
	}

	/**
	 * wcj_order_billing_phone.
	 *
	 * @version 2.7.0
	 */
	function wcj_order_billing_phone( $atts ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->billing_phone : $this->the_order->get_billing_phone() );
	}

	/**
	 * wcj_order_items
	 *
	 * @version 2.8.0
	 * @since   2.5.7
	 */
	function wcj_order_items( $atts ) {
		$items = array();
		$the_items = $this->the_order->get_items();
		foreach ( $the_items as $item_id => $item ) {
			switch ( $atts['field'] ) {
				case '_debug':
					$items[] = '<pre>' . print_r( $item, true ) . '</pre>';
					break;
				case '_qty_x_name':
					$items[] = ( isset( $item['qty'] ) && isset( $item['name'] ) ) ? $item['qty'] . ' x ' . $item['name'] : '';
					break;
				default: // case 'name' etc.
					$items[] = ( isset( $item[ $atts['field'] ] ) ) ? $item[ $atts['field'] ] : '';
					break;
			}
		}
		if ( empty( $items ) ) {
			return '';
		}
		if ( 'all' === $atts['item_number'] ) {
			return implode( $atts['sep'], $items );
		} else {
			switch ( $atts['item_number'] ) {
				case 'first':
					return current( $items );
				case 'last':
					return end( $items );
				default:
					$item_number = intval( $atts['item_number'] ) - 1;
					if ( $item_number < 0 ) {
						$item_number = 0;
					} elseif ( $item_number >= count( $items ) ) {
						$item_number = count( $items ) - 1;
					}
					return $items[ $item_number ];
			}
		}
	}

	/**
	 * wcj_order_items_meta.
	 *
	 * @version 2.7.0
	 * @since   2.5.3
	 */
	function wcj_order_items_meta( $atts ) {
		if ( '' === $atts['meta_key'] ) {
			return '';
		}
		$items_metas = array();
		$the_items = $this->the_order->get_items();
		foreach ( $the_items as $item_id => $item ) {
			$the_meta = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->get_item_meta( $item_id, $atts['meta_key'], true ) : wc_get_order_item_meta( $item_id, $atts['meta_key'], true ) );
			if ( '' != $the_meta ) {
				$items_metas[] = $the_meta;
			}
			/* foreach ( $item as $key => $value ) {
				if ( $atts['meta_key'] === $key ) {
					$items_metas[] = $value;
				}
			} */
		}
		if ( 'yes' === $atts['unique_only'] ) {
			$items_metas = array_unique( $items_metas );
		}
		return ( ! empty( $items_metas ) ) ? implode( ', ', $items_metas ) : '';
	}

	/**
	 * wcj_order_meta.
	 *
	 * @version 2.7.0
	 * @since   2.2.9
	 */
	function wcj_order_meta( $atts ) {
		return ( '' != $atts['meta_key'] ? get_post_meta( wcj_get_order_id( $this->the_order ), $atts['meta_key'], true ) : '' );
	}

	/**
	 * wcj_order_custom_meta_field.
	 *
	 * @version 2.3.0
	 * @since   2.2.9
	 * @deprecated
	 */
	function wcj_order_custom_meta_field( $atts ) {
		return $this->wcj_order_checkout_field( $atts );
	}

	/**
	 * wcj_order_checkout_field.
	 *
	 * @version 2.8.1
	 */
	function wcj_order_checkout_field( $atts ) {
		$field_id = ( string ) $atts['field_id'];
		if ( '' == $field_id ) {
			return '';
		}
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			if ( ! isset( $this->the_order->$field_id ) ) {
				return '';
			}
			$field_value = $this->the_order->$field_id;
			return ( is_array( $field_value ) && isset( $field_value['value'] ) ) ? $field_value['value'] : $field_value;
		} else {
			$order_data = $this->the_order->get_data();
			if ( substr( $field_id, 0, 8 ) === 'billing_' ) {
				$billing_field_id = substr( $field_id, 8 );
				if ( isset( $order_data['billing'][ $billing_field_id ] ) ) {
					return $order_data['billing'][ $billing_field_id ];
				}
			} elseif ( substr( $field_id, 0, 9 ) === 'shipping_' ) {
				$shipping_field_id = substr( $field_id, 9 );
				if ( isset( $order_data['shipping'][ $shipping_field_id ] ) ) {
					return $order_data['shipping'][ $shipping_field_id ];
				}
			}
			if ( $this->the_order->get_meta( '_' . $field_id ) ) {
				return $this->the_order->get_meta( '_' . $field_id );
			}
			if ( isset( $order_data[ $field_id ] ) ) {
				return ( is_array( $order_data[ $field_id ] ) && isset( $order_data[ $field_id ]['value'] ) ) ? $order_data[ $field_id ]['value'] : $order_data[ $field_id ];
			} else {
				return '';
			}
		}
	}

	/**
	 * wcj_order_shipping_address.
	 *
	 * @version 2.9.0
	 */
	function wcj_order_shipping_address( $atts ) {
		$shipping_address = $this->the_order->get_formatted_shipping_address();
		if ( '' != $shipping_address ) {
			return $shipping_address;
		} elseif ( 'yes' === $atts['fallback_billing_address'] ) {
			return $this->the_order->get_formatted_billing_address();
		} else {
			return '';
		}
	}

	/**
	 * wcj_order_status.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function wcj_order_status( $atts ) {
		return $this->the_order->get_status();
	}

	/**
	 * wcj_order_status_label.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_order_status_label( $atts ) {
		$status_object = get_post_status_object( 'wc-' . $this->the_order->get_status() );
		return ( isset( $status_object->label ) ) ? $status_object->label : '';
	}

	/**
	 * wcj_order_date.
	 *
	 * @version 3.2.4
	 * @todo    (maybe) rename `days` to `extra_days`
	 */
	function wcj_order_date( $atts ) {
		return date_i18n( $atts['date_format'], strtotime( wcj_get_order_date( $this->the_order ) ) + $atts['days'] * 24 * 60 * 60 );
	}

	/**
	 * wcj_order_time.
	 *
	 * @version 3.1.0
	 */
	function wcj_order_time( $atts ) {
		return date_i18n( $atts['time_format'], strtotime( wcj_get_order_date( $this->the_order ) ) );
	}

	/**
	 * wcj_order_number.
	 */
	function wcj_order_number( $atts ) {
		return $this->the_order->get_order_number();
	}

	/**
	 * wcj_order_id.
	 */
	function wcj_order_id( $atts ) {
		return $atts['order_id'];
	}

	/**
	 * wcj_order_shipping_price.
	 *
	 * @version 2.5.6
	 */
	function wcj_order_shipping_price( $atts ) {
		$the_result = ( $atts['excl_tax'] ) ? $this->the_order->get_total_shipping() : $this->the_order->get_total_shipping() + $this->the_order->get_shipping_tax();
		return $this->wcj_price_shortcode( $the_result, $atts );
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
	 * wcj_order_total_discount.
	 *
	 * @version 2.4.0
	 */
	function wcj_order_total_discount( $atts ) {

		$the_discount = $this->the_order->get_total_discount( $atts['excl_tax'] );

		/* if ( true === $atts['excl_tax'] ) {
			if ( false != ( $the_tax = $this->wcj_order_get_cart_discount_tax() ) ) {
				$the_discount -= $the_tax;
			}
		} */

		return $this->wcj_price_shortcode( $the_discount, $atts );
	}

	/**
	 * wcj_order_cart_discount.
	 */
	/* function wcj_order_cart_discount( $atts ) {
		return $this->wcj_price_shortcode( $this->the_order->get_cart_discount() , $atts );
	} */

	/**
	 * wcj_order_shipping_tax.
	 */
	function wcj_order_shipping_tax( $atts ) {
		return $this->wcj_price_shortcode( $this->the_order->get_shipping_tax(), $atts );
	}

	/**
	 * wcj_order_total_tax_percent.
	 *
	 * @version 2.4.0
	 */
	function wcj_order_total_tax_percent( $atts ) {
		$order_total_tax_not_rounded = $this->the_order->get_cart_tax() + $this->the_order->get_shipping_tax();
		$order_total_excl_tax        = $this->the_order->get_total() - $order_total_tax_not_rounded;
		$order_total_tax_percent = ( 0 == $order_total_excl_tax ) ? 0 : $order_total_tax_not_rounded / $order_total_excl_tax * 100;
		$order_total_tax_percent = round( $order_total_tax_percent, $atts['precision'] );
		apply_filters( 'wcj_order_total_tax_percent', $order_total_tax_percent, $this->the_order );
		return number_format( $order_total_tax_percent, $atts['precision'] );
	}

	/**
	 * wcj_order_subtotal_by_tax_class.
	 *
	 * @version 2.5.4
	 * @since   2.5.4
	 */
	function wcj_order_subtotal_by_tax_class( $atts ) {
		$subtotal_by_tax_class = 0;
		$tax_class = ( 'standard' === $atts['tax_class'] ) ? '' : $atts['tax_class'];
		foreach ( $this->the_order->get_items() as $item ) {
			if ( $tax_class === $item['tax_class'] ) {
				$subtotal_by_tax_class += $item['line_subtotal'];
			}
		}
		return $this->wcj_price_shortcode( $subtotal_by_tax_class, $atts );
	}

	/**
	 * wcj_order_total_by_tax_class.
	 *
	 * @version 2.5.4
	 * @since   2.5.4
	 */
	function wcj_order_total_by_tax_class( $atts ) {
		$total_by_tax_class = 0;
		$tax_class = ( 'standard' === $atts['tax_class'] ) ? '' : $atts['tax_class'];
		foreach ( $this->the_order->get_items() as $item ) {
			if ( $tax_class === $item['tax_class'] ) {
				$total_by_tax_class += $item['line_total'];
			}
		}
		return $this->wcj_price_shortcode( $total_by_tax_class, $atts );
	}

	/**
	 * wcj_order_tax_by_class.
	 *
	 * @version 2.5.5
	 * @since   2.5.4
	 */
	function wcj_order_tax_by_class( $atts ) {
		$tax_class = ( 'standard' === $atts['tax_class'] ) ? '' : $atts['tax_class'];
		$total_tax_by_class = 0;
		foreach ( $this->the_order->get_items() as $item ) {
			if ( $tax_class === $item['tax_class'] ) {
//				$total_tax_by_class += $this->the_order->get_line_tax( $item );
				$total_tax_by_class += $item['line_tax'];
			}
		}
		return $this->wcj_price_shortcode( $total_tax_by_class, $atts );
	}

	/**
	 * wcj_order_taxes_html.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_order_taxes_html( $atts ) {
		$order_taxes = $this->the_order->get_taxes();
		$taxes_html = '';
		foreach ( $order_taxes as $order_tax ) {
			$taxes_html .= ( isset( $order_tax['label'] ) ) ? $order_tax['label'] . ': ' : '';
			$amount = 0;
			$amount += ( isset( $order_tax['tax_amount'] ) ) ? $order_tax['tax_amount'] : 0;
			$amount += ( isset( $order_tax['shipping_tax_amount'] ) ) ? $order_tax['shipping_tax_amount'] : 0;
			$taxes_html .= $this->wcj_price_shortcode( $amount, $atts ) . '<br>';
		}
		return $taxes_html;
	}

	/**
	 * wcj_order_total_tax.
	 *
	 * @version 3.1.2
	 */
	function wcj_order_total_tax( $atts ) {
		return $this->wcj_price_shortcode( apply_filters( 'wcj_order_total_tax', $this->the_order->get_total_tax(), $this->the_order ), $atts );
	}

	/**
	 * wcj_order_total_tax_after_refund.
	 *
	 * @version 3.1.2
	 * @since   3.1.2
	 */
	function wcj_order_total_tax_after_refund( $atts ) {
		return $this->wcj_price_shortcode( ( $this->the_order->get_total_tax() - $this->the_order->get_total_tax_refunded() ), $atts );
	}

	/**
	 * wcj_order_subtotal_plus_shipping.
	 */
	function wcj_order_subtotal_plus_shipping( $atts ) {
		$the_subtotal = $this->the_order->get_subtotal();
		$the_shipping = $this->the_order->get_total_shipping();
		return $this->wcj_price_shortcode( $the_subtotal + $the_shipping, $atts );
	}

	/**
	 * wcj_order_subtotal.
	 */
	function wcj_order_subtotal( $atts ) {

		if ( 'yes' === $atts['round_by_line'] ) {
			$the_subtotal = 0;
			foreach ( $this->the_order->get_items() as $item ) {
				$the_subtotal += $this->the_order->get_line_subtotal( $item, false, true );
			}
		} else {
			$the_subtotal = $this->the_order->get_subtotal();
		}

		return $this->wcj_price_shortcode( $the_subtotal, $atts );
	}

	/**
	 * wcj_order_subtotal_to_display.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function wcj_order_subtotal_to_display( $atts ) {
		return $this->the_order->get_subtotal_to_display( false, $atts['tax_display'] );
	}

	/**
	 * wcj_order_total_excl_tax.
	 *
	 * @version 2.5.6
	 */
	function wcj_order_total_excl_tax( $atts ) {
		$order_total = $this->the_order->get_total() - $this->the_order->get_total_tax();
		$order_total = apply_filters( 'wcj_order_total_excl_tax', $order_total, $this->the_order );
		return $this->wcj_price_shortcode( $order_total, $atts );
	}

	/**
	 * wcj_order_currency.
	 *
	 * @version 2.7.0
	 */
	function wcj_order_currency( $atts ) {
		return wcj_get_order_currency( $this->the_order );
	}

	/**
	 * wcj_order_total_excl_shipping.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function wcj_order_total_excl_shipping( $atts ) {
		$order_total_excl_shipping = ( true === $atts['excl_tax'] ) ?
			$this->the_order->get_total() - $this->the_order->get_total_shipping() - $this->the_order->get_total_tax() :
			$this->the_order->get_total() - $this->the_order->get_total_shipping() - $this->the_order->get_shipping_tax();
		return $this->wcj_price_shortcode( $order_total_excl_shipping, $atts );
	}

	/**
	 * wcj_order_total.
	 */
	function wcj_order_total( $atts ) {
		$order_total = ( true === $atts['excl_tax'] ) ? $this->the_order->get_total() - $this->the_order->get_total_tax() : $this->the_order->get_total();
		return $this->wcj_price_shortcode( $order_total, $atts );
	}

	/**
	 * wcj_order_total_after_refund.
	 *
	 * @version 3.1.2
	 * @since   3.1.2
	 * @todo    (maybe) `get_total_shipping_refunded()`
	 */
	function wcj_order_total_after_refund( $atts ) {
		$order_total_after_refund = $this->the_order->get_total() - $this->the_order->get_total_refunded();
		if ( true === $atts['excl_tax'] ) {
			$order_total_after_refund -= ( $this->the_order->get_total_tax() - $this->the_order->get_total_tax_refunded() );
		}
		return $this->wcj_price_shortcode( $order_total_after_refund, $atts );
	}

	/**
	 * mb_ucfirst - for wcj_order_total_in_words.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function mb_ucfirst( $string ) {
		return mb_strtoupper( mb_substr( $string, 0, 1 ) ) . mb_substr( $string, 1 );
	}

	/**
	 * wcj_order_total_in_words.
	 *
	 * @version 2.5.9
	 */
	function wcj_order_total_in_words( $atts ) {

		$order_total = ( true === $atts['excl_tax'] ) ? $this->the_order->get_total() - $this->the_order->get_total_tax() : $this->the_order->get_total();
		$order_total_whole   = intval( $order_total );
		$order_total_decimal = round( ( $order_total - $order_total_whole ) * 100 );

		$the_number_in_words = '%s %s';
		$the_number_in_words .= ( 0 != $order_total_decimal ) ? ', %s %s.' : '.';

		$dollars = $atts['whole'];
		$cents = $atts['decimal'];

		switch ( $atts['lang'] ) {
			case 'LT':
				return sprintf( $the_number_in_words,
					$this->mb_ucfirst( convert_number_to_words_lt( $order_total_whole ) ),
					$dollars,
					$this->mb_ucfirst( convert_number_to_words_lt( $order_total_decimal ) ),
					$cents );
			case 'BG':
				return sprintf( $the_number_in_words,
					$this->mb_ucfirst( trim( convert_number_to_words_bg( $order_total_whole ) ) ),
					$dollars,
					$this->mb_ucfirst( trim( convert_number_to_words_bg( $order_total_decimal ) ) ),
					$cents );
			default: // 'EN'
				return sprintf( $the_number_in_words,
					ucfirst( convert_number_to_words( $order_total_whole ) ),
					$dollars,
					ucfirst( convert_number_to_words( $order_total_decimal ) ),
					$cents );
		}
	}
}

endif;

return new WCJ_Orders_Shortcodes();

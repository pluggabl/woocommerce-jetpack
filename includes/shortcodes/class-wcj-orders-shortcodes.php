<?php
/**
 * WooCommerce Jetpack Orders Shortcodes
 *
 * The WooCommerce Jetpack Orders Shortcodes class.
 *
 * @version 2.5.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Orders_Shortcodes' ) ) :

class WCJ_Orders_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 2.5.4
	 */
	public function __construct() {

		$this->the_shortcodes = array(
			'wcj_order_date',
			'wcj_order_time',
			'wcj_order_number',
			'wcj_order_id',
			'wcj_order_billing_address',
			'wcj_order_billing_phone',
			'wcj_order_checkout_field',
			'wcj_order_shipping_address',
			'wcj_order_customer_note',
			'wcj_order_custom_field',
			'wcj_order_custom_meta_field',
			'wcj_order_meta',
			'wcj_order_items_meta',
			'wcj_order_subtotal',
			'wcj_order_subtotal_plus_shipping',
			'wcj_order_total_discount',
//			'wcj_order_cart_discount',
			'wcj_order_shipping_tax',
			'wcj_order_taxes_html',
			'wcj_order_tax_by_class',
			'wcj_order_total_tax',
			'wcj_order_total_tax_percent',
			'wcj_order_total',
			'wcj_order_total_by_tax_class',
			'wcj_order_subtotal_by_tax_class',
			'wcj_order_currency',
			'wcj_order_total_in_words',
			'wcj_order_total_excl_tax',
			'wcj_order_shipping_price',
			'wcj_order_total_refunded',
			'wcj_order_total_fees',
			'wcj_order_total_fees_incl_tax',
			'wcj_order_total_fees_tax',
			'wcj_order_fee',
			'wcj_order_fees_html',
			'wcj_order_payment_method',
			'wcj_order_shipping_method',
			'wcj_order_items_total_weight',
			'wcj_order_items_total_quantity',
			'wcj_order_items_total_number',
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
			'order_id'      => 0,
			'hide_currency' => 'no',
			'excl_tax'      => 'no',
			'date_format'   => get_option( 'date_format' ),
			'time_format'   => get_option( 'time_format' ),
			'hide_if_zero'  => 'no',
			'field_id'      => '',
			'name'          => '',
			'round_by_line' => 'no',
			'whole'         => __( 'Dollars', 'woocommerce-jetpack' ),
			'decimal'       => __( 'Cents', 'woocommerce-jetpack' ),
			'precision'     => get_option( 'woocommerce_price_num_decimals', 2 ),
			'lang'          => 'EN',
		), $atts );

		return $modified_atts;
	}

	/**
	 * init_atts.
	 */
	function init_atts( $atts ) {

		// Atts
		$atts['excl_tax'] = ( 'yes' === $atts['excl_tax'] ) ? true : false;

		if ( 0 == $atts['order_id'] ) $atts['order_id'] = ( isset( $_GET['order_id'] ) ) ? $_GET['order_id'] : get_the_ID();
		if ( 0 == $atts['order_id'] ) $atts['order_id'] = ( isset( $_GET['pdf_invoice'] ) ) ? $_GET['pdf_invoice'] : 0; // PDF Invoices V1 compatibility
		if ( 0 == $atts['order_id'] ) return false;
		//if ( 'shop_order' !== get_post_type( $atts['order_id'] ) ) return false;

		// Class properties
		$this->the_order = ( 'shop_order' === get_post_type( $atts['order_id'] ) ) ? wc_get_order( $atts['order_id'] ) : null;
		if ( ! $this->the_order ) return false;

		return $atts;
	}

	/**
	 * wcj_price_shortcode.
	 */
	private function wcj_price_shortcode( $raw_price, $atts ) {
		return ( 'yes' === $atts['hide_if_zero'] && 0 == $raw_price ) ? '' : wcj_price( $raw_price, $this->the_order->get_order_currency(), $atts['hide_currency'] );
	}

	/**
	 * Get order custom field.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 * @return  string
	 */
	function wcj_order_custom_field( $atts ) {
		$order_custom_fields = get_post_custom( $atts['order_id'] );
		return ( isset( $order_custom_fields[ $atts['name'] ][0] ) ) ? $order_custom_fields[ $atts['name'] ][0] : '';
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
	 * wcj_order_payment_method.
	 */
	function wcj_order_payment_method( $atts ) {
		//return $this->the_order->payment_method_title;
		return get_post_meta( $this->the_order->id, '_payment_method_title', true );
	}

	/**
	 * wcj_order_items_total_weight.
	 */
	function wcj_order_items_total_weight( $atts ) {
		$total_weight = 0;
		$the_items = $this->the_order->get_items();
		foreach( $the_items as $the_item ) {
			$the_product = wc_get_product( $the_item['product_id'] );
			$total_weight += $the_item['qty'] * $the_product->get_weight();
		}
		return ( 0 == $total_weight && 'yes' === $atts['hide_if_zero'] ) ? '' : $total_weight;
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
	 */
	function wcj_order_customer_note( $atts ) {
		return $this->the_order->customer_note;
	}

	/**
	 * wcj_order_billing_phone.
	 */
	function wcj_order_billing_phone( $atts ) {
		return $this->the_order->billing_phone;
	}

	/**
	 * wcj_order_items_meta.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_order_items_meta( $atts ) {
		$items_metas = array();
		$the_items = $this->the_order->get_items();
		foreach ( $the_items as $item_id => $item ) {
			$the_meta = $this->the_order->get_item_meta( $item_id, $atts['meta_key'], true );
			if ( '' != $the_meta ) {
				$items_metas[] = $the_meta;
			}
			/* foreach ( $item as $key => $value ) {
				if ( $atts['meta_key'] === $key ) {
					$items_metas[] = $value;
				}
			} */
		}
		return ( ! empty( $items_metas ) ) ? implode( ', ', $items_metas ) : '';
	}

	/**
	 * wcj_order_meta.
	 *
	 * @version 2.2.9
	 * @since   2.2.9
	 */
	function wcj_order_meta( $atts ) {
		return get_post_meta( $this->the_order->id, $atts['meta_key'], true );
	}

	/**
	 * wcj_order_custom_meta_field.
	 *
	 * @version 2.3.0
	 * @since   2.2.9
	 * @depreciated
	 */
	function wcj_order_custom_meta_field( $atts ) {
		return $this->wcj_order_checkout_field( $atts );
	}

	/**
	 * wcj_order_checkout_field.
	 *
	 * @version 2.4.5
	 */
	function wcj_order_checkout_field( $atts ) {
		$field_id = ( string ) $atts['field_id'];
		if ( '' == $field_id ) {
			return '';
		}
		if ( ! isset( $this->the_order->$field_id ) ) {
			return '';
		}
		$field_value = $this->the_order->$field_id;
		return ( is_array( $field_value ) && isset( $field_value['value'] ) ) ? $field_value['value'] : $field_value;
	}

	/**
	 * wcj_order_shipping_address.
	 */
	function wcj_order_shipping_address( $atts ) {
		return $this->the_order->get_formatted_shipping_address();
	}

	/**
	 * wcj_order_date.
	 */
	function wcj_order_date( $atts ) {
		return date_i18n( $atts['date_format'], strtotime( $this->the_order->order_date ) );
	}

	/**
	 * wcj_order_time.
	 */
	function wcj_order_time( $atts ) {
		return date_i18n( $atts['time_format'], strtotime( $this->the_order->order_date ) );
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
	 */
	function wcj_order_shipping_price( $atts ) {
		$the_result = $this->the_order->get_total_shipping();
		if ( false === $atts['excl_tax'] ) $the_result = $the_result + $this->the_order->get_shipping_tax();
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
	 * wcj_order_total_refunded.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_order_total_refunded( $atts ) {
		return $this->wcj_price_shortcode( $this->the_order->get_total_refunded(), $atts );
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
	 * @version 2.5.4
	 * @since   2.5.4
	 */
	function wcj_order_tax_by_class( $atts ) {
		$tax_class = ( 'standard' === $atts['tax_class'] ) ? '' : $atts['tax_class'];
		$total_tax_by_class = 0;
		foreach ( $this->the_order->get_items() as $item ) {
			if ( $tax_class === $item['tax_class'] ) {
				$total_tax_by_class += $this->the_order->get_line_tax( $item );
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
	 */
	function wcj_order_total_tax( $atts ) {
		$order_total_tax = $this->the_order->get_total_tax();
		$order_total_tax = apply_filters( 'wcj_order_total_tax', $order_total_tax, $this->the_order );
		return $this->wcj_price_shortcode( $order_total_tax, $atts );
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
	 * wcj_order_total_excl_tax.
	 */
	function wcj_order_total_excl_tax( $atts ) {
		//$order_total_tax = $this->the_order->get_total() - $this->the_order->get_subtotal() + $this->the_order->get_total_discount( true );
		$order_total_tax = $this->the_order->get_total_tax();
		$order_total = $this->the_order->get_total() - $order_total_tax;
		$order_total = apply_filters( 'wcj_order_total_excl_tax', $order_total, $this->the_order );
		return $this->wcj_price_shortcode( $order_total, $atts );
	}

	/**
	 * wcj_order_currency.
	 */
	function wcj_order_currency( $atts ) {
		return $this->the_order->get_order_currency();
	}

	/**
	 * wcj_order_total.
	 */
	function wcj_order_total( $atts ) {
		$order_total = ( true === $atts['excl_tax'] ) ? $this->the_order->get_total() - $this->the_order->get_total_tax() : $this->the_order->get_total();
		return $this->wcj_price_shortcode( $order_total, $atts );
	}

	/**
	 * wcj_order_total_in_words.
	 *
	 * @version 2.5.0
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
			case 'BG':
				return convert_number_to_words_bg( $order_total );
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

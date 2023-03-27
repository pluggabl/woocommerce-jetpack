<?php
/**
 * Booster for WooCommerce - Shortcodes - Orders
 *
 * @version 6.0.5
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Orders_Shortcodes' ) ) :
	/**
	 * WCJ_Orders_Shortcodes.
	 */
	class WCJ_Orders_Shortcodes extends WCJ_Shortcodes {

		/**
		 * Constructor.
		 *
		 * @version 6.0.5
		 */
		public function __construct() {

			$this->the_shortcodes = array(
				'wcj_order_billing_address',
				'wcj_order_billing_country_name',
				'wcj_order_billing_phone',
				'wcj_order_billing_email',
				'wcj_order_checkout_field',
				'wcj_order_coupons',
				'wcj_order_currency',
				'wcj_order_custom_field',
				'wcj_order_custom_meta_field',
				'wcj_order_customer_data',
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
				'wcj_order_items_total_weight', // deprecated - use 'wcj_order_total_weight' instead.
				'wcj_order_meta',
				'wcj_order_notes',
				'wcj_order_number',
				'wcj_order_payment_method',
				'wcj_order_payment_method_transaction_id',
				'wcj_order_products_meta',
				'wcj_order_products_terms',
				'wcj_order_profit',
				'wcj_order_refunds_table',
				'wcj_order_remaining_refund_amount',
				'wcj_order_shipping_address',
				'wcj_order_shipping_country_name',
				'wcj_order_shipping_method',
				'wcj_order_shipping_price',
				'wcj_order_shipping_price_without_html_custom',
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
				'wcj_order_total_without_html_custom',
				'wcj_order_total_after_refund',
				'wcj_order_total_by_tax_class',
				'wcj_order_total_discount',
				'wcj_order_total_excl_shipping',
				'wcj_order_total_excl_tax',
				'wcj_order_total_fees',
				'wcj_order_total_fees_incl_tax',
				'wcj_order_total_fees_incl_tax_without_html_custom',
				'wcj_order_total_fees_tax',
				'wcj_order_total_formatted',
				'wcj_order_total_height',
				'wcj_order_total_in_words',
				'wcj_order_total_length',
				'wcj_order_total_shipping_refunded',
				'wcj_order_total_refunded',
				'wcj_order_item_total_refunded',
				'wcj_order_total_tax',
				'wcj_order_total_tax_after_refund',
				'wcj_order_total_tax_without_html_custom',
				'wcj_order_total_tax_percent',
				'wcj_order_total_tax_refunded',
				'wcj_order_total_weight',
				'wcj_order_total_width',
				'wcj_order_vat_func',
				'wcj_order_payment_method_notes',
			);

			parent::__construct();
		}

		/**
		 * Add_extra_atts.
		 *
		 * @version 5.6.8
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function add_extra_atts( $atts ) {
			$modified_atts = array_merge(
				array(
					'order_id'                   => 0,
					'hide_currency'              => 'no',
					'excl_tax'                   => 'no',
					'date_format'                => wcj_get_option( 'date_format' ),
					'time_format'                => wcj_get_option( 'time_format' ),
					'hide_if_zero'               => 'no',
					'add_html_on_price'          => true,
					'field_id'                   => '',
					'name'                       => '',
					'round_by_line'              => 'no',
					'whole'                      => '',
					'decimal'                    => '&cent;',
					'precision'                  => wcj_get_option( 'woocommerce_price_num_decimals', 2 ),
					'lang'                       => 'EN',
					'unique_only'                => 'no',
					'function_name'              => '',
					'sep'                        => ', ',
					'item_number'                => 'all',
					'field'                      => 'name',
					'order_user_roles'           => '',
					'meta_key'                   => '', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'tax_class'                  => '',
					'fallback_billing_address'   => 'no',
					'tax_display'                => '',
					'table_class'                => '',
					'columns_styles'             => '',
					'columns_titles'             => '',
					'columns'                    => '',
					'price_prefix'               => '',
					'display_refunded'           => 'yes',
					'insert_page_break'          => '',
					'key'                        => null,
					'days'                       => 0,
					'code'                       => '',
					'type'                       => '',
					'dimension'                  => '2D',
					'width'                      => 0,
					'height'                     => 0,
					'color'                      => 'black',
					'currency'                   => '',
					'doc_type'                   => 'invoice',
					'exclude_by_categories'      => '',
					'exclude_by_tags'            => '',
					'exclude_by_attribute__name' => '',
					'show_label'                 => true,
					'tax_label_spaces'           => 0,
				),
				$atts
			);

			return $modified_atts;
		}
		/**
		 * Wcj_order_vat_func.
		 *
		 * @version 6.0.1
		 * @since   5.5.6
		 * @param array $attr The user defined shortcode attributes.
		 */
		public function wcj_order_vat_func( $attr ) {
			if ( isset( $attr['vat_exempt_text'] ) ) {
				$vat_exempt_text = $attr['vat_exempt_text'];
				$order_id        = wcj_get_order_id( $this->the_order );
				$order           = wc_get_order( $order_id );
				foreach ( $order->get_items() as $item_id => $item ) {
					$tax = $item->get_subtotal_tax();

					if ( (string) 0 === $tax ) {
						return $vat_exempt_text;
					}
				}
			}
		}


		/**
		 * Init_atts.
		 *
		 * @todo    (maybe) `if ( 'shop_order' !== get_post_type( $atts['order_id'] ) ) return false;`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function init_atts( $atts ) {

			// Atts.
			// phpcs:disable WordPress.Security.NonceVerification
			$atts['excl_tax'] = ( 'yes' === $atts['excl_tax'] );
			if ( 0 === $atts['order_id'] ) {
				$atts['order_id'] = ( isset( $_GET['order_id'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) : get_the_ID();
			}
			if ( 0 === $atts['order_id'] ) {
				$atts['order_id'] = ( isset( $_GET['pdf_invoice'] ) ) ? sanitize_text_field( wp_unslash( $_GET['pdf_invoice'] ) ) : 0;
				// PDF Invoices V1 compatibility.
			}
			// phpcs:enable WordPress.Security.NonceVerification
			if ( 0 === $atts['order_id'] ) {
				return false;
			}

			// Class properties.
			$this->the_order = ( 'shop_order' === get_post_type( $atts['order_id'] ) ) ? wc_get_order( $atts['order_id'] ) : null;
			if ( ! $this->the_order ) {
				return false;
			}

			return $atts;
		}




		/**
		 * Extra_check.
		 *
		 * @version 2.7.0
		 * @since   2.6.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function extra_check( $atts ) {
			if ( '' !== $atts['order_user_roles'] ) {
				$user_info           = get_userdata( ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() ) );
				$user_roles          = $user_info->roles;
				$user_roles_to_check = explode( ',', $atts['order_user_roles'] );
				foreach ( $user_roles_to_check as $user_role_to_check ) {
					if ( in_array( $user_role_to_check, $user_roles, true ) ) {
						return true;
					}
				}
				return false;
			}
			return true;
		}

		/**
		 * Wcj_price_shortcode.
		 *
		 * @version 5.6.2
		 * @param int   $raw_price The user defined shortcode raw_price.
		 * @param array $atts The user defined shortcode attributes.
		 */
		private function wcj_price_shortcode( $raw_price, $atts ) {
			if ( 'yes' === $atts['hide_if_zero'] && (float) 0 === $raw_price ) {
				return '';
			} else {
				$order_currency = wcj_get_order_currency( $this->the_order );
				if ( '' === $atts['currency'] ) {
					if ( 'yes' === $atts['hide_currency'] ) {
						return wcj_price( $raw_price, $atts['hide_currency'], $atts );
					}
					return wcj_price( $raw_price, $order_currency, $atts['hide_currency'], $atts );
				} else {
					$convert_to_currency = $atts['currency'];
					if ( '%shop_currency%' === $convert_to_currency ) {
						$convert_to_currency = wcj_get_option( 'woocommerce_currency' );
					}
					return wcj_price( $raw_price * wcj_get_saved_exchange_rate( $order_currency, $convert_to_currency ), $convert_to_currency, $atts['hide_currency'], $atts );
				}
			}
		}



		/**
		 * Wcj_order_items_cost.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_items_cost( $atts ) {
			$atts['type'] = 'items_cost';
			return $this->wcj_order_profit( $atts );
		}

		/**
		 * Wcj_order_profit.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_profit( $atts ) {
			$total = 0;
			foreach ( $this->the_order->get_items() as $item_id => $item ) {
				$product_id     = ( ( isset( $item['variation_id'] ) && 0 !== $item['variation_id'] && 'no' === wcj_get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' ) )
				? $item['variation_id'] : $item['product_id'] );
				$value          = 0;
				$purchase_price = wc_get_product_purchase_price( $product_id );
				if ( 0 !== ( $purchase_price ) ) {
					if ( 'profit' === $atts['type'] || '' === $atts['type'] ) {
						// profit.
						$_order_prices_include_tax = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->prices_include_tax : $this->the_order->get_prices_include_tax() );
						$line_total                = ( $_order_prices_include_tax ? ( $item['line_total'] + $item['line_tax'] ) : $item['line_total'] );
						$value                     = $line_total - $purchase_price * $item['qty'];
					} else {
						// 'items_cost'.
						$value = $purchase_price * $item['qty'];
					}
				}
				$total += $value;
			}
			return $this->wcj_price_shortcode( $total, $atts );
		}

		/**
		 * Wcj_order_tcpdf_barcode.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_tcpdf_barcode( $atts ) {
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
		 * Wcj_order_total_formatted.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_formatted( $atts ) {
			return $this->the_order->get_formatted_order_total( $atts['tax_display'], ( 'yes' === $atts['display_refunded'] ) );
		}

		/**
		 * Wcj_order_remaining_refund_amount.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_remaining_refund_amount( $atts ) {
			return $this->wcj_price_shortcode( $this->the_order->get_remaining_refund_amount(), $atts );
		}

		/**
		 * Wcj_order_refunds_table.
		 *
		 * @version  5.4.5
		 * @since   3.1.0
		 * @see     woocommerce/includes/admin/meta-boxes/views/html-order-refund.php for refund_title
		 * @todo    add `refund_items_or_reason_or_title` column
		 * @todo    add `refund_items_quantities` column (`$_item->get_quantity()`)
		 * @todo    check `$atts['columns']` etc. before starting
		 * @todo    (maybe) move to new `class-wcj-orders-refunded-shortcodes.php` file
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_refunds_table( $atts ) {
			$columns    = ( '' === $atts['columns'] ? array() : explode( '|', $atts['columns'] ) );
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
							$cell = sprintf(
							/* translators: 1: refund id 2: refund date */
								esc_html__( 'Refund #%1$s - %2$s', 'woocommerce' ),
								esc_html( $_refund->get_id() ),
								esc_html( wc_format_datetime( $_refund->get_date_created(), wcj_get_option( 'date_format' ) . ', ' . wcj_get_option( 'time_format' ) ) )
							);
							break;
						case 'refund_date':
							$cell = esc_html( wc_format_datetime( $_refund->get_date_created(), wcj_get_option( 'date_format' ) . ', ' . wcj_get_option( 'time_format' ) ) );
							break;
						case 'refund_reason':
							$cell = $_refund->get_reason();
							break;
						case 'refund_reason_or_title':
							$reason = $_refund->get_reason();
							$cell   = ( '' !== $reason ? $reason : $_refund->get_post_title() );
							break;
						case 'refund_amount':
							$cell = $atts['price_prefix'] . $_refund->get_formatted_refund_amount();
							break;
						case 'refund_items':
							$_items = array();
							foreach ( $_refund->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $_item ) {
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
				'table_class'     => $atts['table_class'],
				'columns_classes' => array(),
				'columns_styles'  => ( '' === $atts['columns_styles'] ? array() : explode( '|', $atts['columns_styles'] ) ),
			);
			$columns_titles  = array( ( '' === $atts['columns_titles'] ? array() : explode( '|', $atts['columns_titles'] ) ) );
			if ( '' !== $atts['insert_page_break'] ) {
				$page_breaks  = explode( '|', $atts['insert_page_break'] );
				$data_size    = count( $table_data );
				$slice_offset = 0;
				$html         = '';
				$slices       = 0;
				while ( $slice_offset < $data_size ) {
					if ( 0 !== $slice_offset ) {
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
		 * Wcj_order_total_tax_refunded.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_tax_refunded( $atts ) {
			return $this->wcj_price_shortcode( $this->the_order->get_total_tax_refunded(), $atts );
		}

		/**
		 * Wcj_order_total_shipping_refunded.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_shipping_refunded( $atts ) {
			return $this->wcj_price_shortcode( $this->the_order->get_total_shipping_refunded(), $atts );
		}

		/**
		 * Wcj_order_total_refunded.
		 *
		 * @version 5.6.3
		 * @since   2.5.3
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_refunded( $atts ) {
			$refund_total = ( $atts['excl_tax'] ) ? $this->the_order->get_total_refunded() - $this->the_order->get_total_tax_refunded() : $this->the_order->get_total_refunded();
			return $this->wcj_price_shortcode( $refund_total, $atts );
		}

		/**
		 * Wcj_order_item_total_refunded.
		 *
		 * @version 6.0.0
		 * @since   2.5.3
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_item_total_refunded( $atts ) {
			$refund_item = $this->the_order->get_refunds();
			foreach ( $refund_item as $_refund ) {

				foreach ( $_refund->get_items() as $_item ) {
					$refund_total     += $_item->get_total();
					$refund_total_tax += $_item->get_total_tax();
				}
			}
			$refund_result = ( $atts['excl_tax'] ) ? abs( $refund_total ) : abs( $refund_total ) + abs( $refund_total_tax );
			return $this->wcj_price_shortcode( $refund_result, $atts );
		}

		/**
		 * Wcj_order_customer_meta.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_customer_meta( $atts ) {
			$_customer_id = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() );
			if ( '' !== $atts['key'] && ( $_customer_id ) ) {
				$meta = get_user_meta( $_customer_id, $atts['key'], true );
				if ( '' !== ( $meta ) ) {
					return $meta;
				}
			}
			return '';
		}

		/**
		 * Wcj_order_customer_data.
		 *
		 * @version 3.5.0
		 * @since   3.5.0
		 * @todo    add similar `[wcj_customer_data]`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_customer_data( $atts ) {
			$_customer_id = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() );
			if ( '' !== $atts['key'] && ( $_customer_id ) ) {
				$user_data = get_userdata( $_customer_id );
				if ( ( $user_data ) && isset( $user_data->{$atts['key']} ) ) {
					return $user_data->{$atts['key']};
				}
			}
			return '';
		}

		/**
		 * Wcj_order_customer_user_roles.
		 *
		 * @version 2.7.0
		 * @since   2.6.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_customer_user_roles( $atts ) {
			$_customer_id = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() );
			if ( $_customer_id > 0 ) {
				$user_info = get_userdata( $_customer_id );
				return ( is_array( $user_info->roles ) ) ? implode( ', ', $user_info->roles ) : $user_info->roles;
			} else {
				return __( 'Guest', 'woocommerce-jetpack' );
			}
		}

		/**
		 * Wcj_order_customer_user.
		 *
		 * @version 2.7.0
		 * @since   2.6.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_customer_user( $atts ) {
			return ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_user : $this->the_order->get_customer_id() );
		}

		/**
		 * Wcj_order_coupons.
		 *
		 * @version 2.5.8
		 * @since   2.5.8
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_coupons( $atts ) {
			return implode( ', ', $this->the_order->get_used_coupons() );
		}

		/**
		 * Wcj_order_function.
		 *
		 * @version 2.5.8
		 * @since   2.5.6
		 * @todo    add function_params attribute.
		 * @todo    fix when returning array of arrays or object etc.
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_function( $atts ) {
			$function_name = $atts['function_name'];
			if ( '' !== $function_name && method_exists( $this->the_order, $function_name ) ) {
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
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_custom_field( $atts ) {
			$order_custom_fields = get_post_custom( $atts['order_id'] );
			$return              = ( isset( $order_custom_fields[ $atts['name'] ][0] ) ) ? $order_custom_fields[ $atts['name'] ][0] : '';
			if ( null !== $atts['key'] ) {
				$return = maybe_unserialize( $return );
				return ( isset( $return[ $atts['key'] ] ) ? $return[ $atts['key'] ] : '' );
			}
			return $return;
		}

		/**
		 * Wcj_order_total_fees.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_fees( $atts ) {
			$total_fees = 0;
			$the_fees   = $this->the_order->get_fees();
			foreach ( $the_fees as $the_fee ) {
				$total_fees += $the_fee['line_total'];
			}
			return $this->wcj_price_shortcode( $total_fees, $atts );
		}

		/**
		 * Wcj_order_total_fees_tax.
		 *
		 * @version 2.5.2
		 * @since   2.4.8
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_fees_tax( $atts ) {
			$total_fees_tax = 0;
			$the_fees       = $this->the_order->get_fees();
			foreach ( $the_fees as $the_fee ) {
				$total_fees_tax += $this->the_order->get_line_tax( $the_fee );
			}
			return $this->wcj_price_shortcode( $total_fees_tax, $atts );
		}

		/**
		 * Wcj_order_total_fees_incl_tax.
		 *
		 * @version 2.5.2
		 * @since   2.4.8
		 * @todo    probably should use get_line_total
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_fees_incl_tax( $atts ) {
			$total_fees = 0;
			$the_fees   = $this->the_order->get_fees();
			foreach ( $the_fees as $the_fee ) {
				$total_fees += $the_fee['line_total'];
				$total_fees += $this->the_order->get_line_tax( $the_fee );
			}
			return $this->wcj_price_shortcode( $total_fees, $atts );
		}


		/**
		 * Wcj_order_total_fees_incl_tax_without_html_custom.
		 *
		 * @version 5.4.0
		 * @todo    probably should use get_line_total
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_fees_incl_tax_without_html_custom( $atts ) {
			$total_fees = 0;
			$the_fees   = $this->the_order->get_fees();
			foreach ( $the_fees as $the_fee ) {
				$total_fees += $the_fee['line_total'];
				$total_fees += $this->the_order->get_line_tax( $the_fee );
			}
			return $total_fees;
		}

		/**
		 * Wcj_order_fees_html.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_fees_html( $atts ) {
			$fees_html = '';
			$the_fees  = $this->the_order->get_fees();
			foreach ( $the_fees as $the_fee ) {
				$fees_html .= '<p>' . $the_fee['name'] . ' - ' . $the_fee['line_total'] . '</p>';
			}
			return $fees_html;
		}

		/**
		 * Wcj_order_fee.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_fee( $atts ) {
			if ( '' === $atts['name'] ) {
				return '';
			}
			$the_fees = $this->the_order->get_fees();
			foreach ( $the_fees as $the_fee ) {
				if ( $atts['name'] === $the_fee['name'] ) {
					return $this->wcj_price_shortcode( $the_fee['line_total'], $atts );
				}
			}
			return '';
		}
		/**
		 * Wcj_order_shipping_method.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_shipping_method( $atts ) {
			return $this->the_order->get_shipping_method();
		}

		/**
		 * Wcj_order_payment_method_transaction_id.
		 *
		 * @version 2.5.6
		 * @since   2.5.6
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_payment_method_transaction_id( $atts ) {
			return $this->the_order->get_transaction_id();
		}

		/**
		 * Wcj_order_payment_method.
		 *
		 * @version 2.7.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_payment_method( $atts ) {
			return get_post_meta( wcj_get_order_id( $this->the_order ), '_payment_method_title', true );
		}

		/**
		 * Wcj_order_total_width.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_width( $atts ) {
			return $this->get_order_total( $atts, 'width' );
		}

		/**
		 * Wcj_order_total_height.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_height( $atts ) {
			return $this->get_order_total( $atts, 'height' );
		}

		/**
		 * Wcj_order_total_length.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_length( $atts ) {
			return $this->get_order_total( $atts, 'length' );
		}

		/**
		 * Wcj_order_total_weight.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_weight( $atts ) {
			return $this->get_order_total( $atts, 'weight' );
		}

		/**
		 * Get_order_total.
		 *
		 * @version 4.4.0
		 * @since   2.5.7
		 * @param array  $atts The user defined shortcode attributes.
		 * @param string $param The user defined shortcode param.
		 */
		public function get_order_total( $atts, $param ) {
			$total     = 0;
			$the_items = $this->the_order->get_items();
			foreach ( $the_items as $item_id => $item ) {
				$product_id = ( 0 !== $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
				$_product   = wc_get_product( $product_id );
				if ( $_product ) {
					switch ( $param ) {
						case 'width':
							$total += ( $item['qty'] * floatval( $_product->get_width() ) );
							break;
						case 'height':
							$total += ( $item['qty'] * floatval( $_product->get_height() ) );
							break;
						case 'length':
							$total += ( $item['qty'] * floatval( $_product->get_length() ) );
							break;
						case 'weight':
							$total += ( $item['qty'] * floatval( $_product->get_weight() ) );
							break;
					}
				}
			}
			return ( 0 === $total && 'yes' === $atts['hide_if_zero'] ) ? '' : $total;
		}

		/**
		 * Wcj_order_items_total_weight.
		 *
		 * @version 2.5.7
		 * @deprecated 2.5.7
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_items_total_weight( $atts ) {
			return $this->get_order_total( $atts, 'weight' );
		}

		/**
		 * Wcj_order_items_total_quantity.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_items_total_quantity( $atts ) {
			$total_quantity = 0;
			$the_items      = $this->the_order->get_items();
			foreach ( $the_items as $the_item ) {
				$total_quantity += $the_item['qty'];
			}
			return ( 0 === $total_quantity && 'yes' === $atts['hide_if_zero'] ) ? '' : $total_quantity;
		}

		/**
		 * Wcj_order_items_total_number.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_items_total_number( $atts ) {
			$total_number = count( $this->the_order->get_items() );
			return ( 0 === $total_number && 'yes' === $atts['hide_if_zero'] ) ? '' : $total_number;
		}

		/**
		 * Wcj_order_billing_address.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 *
		 * @version 2.3.9
		 */
		public function wcj_order_billing_address( $atts ) {
			return apply_filters( 'wcj_order_billing_address', $this->the_order->get_formatted_billing_address(), $atts );
		}

		/**
		 * Wcj_order_billing_email.
		 *
		 * @version 5.2.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_billing_email( $atts ) {
			return apply_filters( 'wcj_order_billing_email', $this->the_order->get_billing_email(), $atts );
		}

		/**
		 * Wcj_order_notes.
		 *
		 * @version 3.5.0
		 * @since   3.4.0
		 * @todo    [dev] (maybe) run `strip_tags` on `comment_content`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_notes( $atts ) {
			$notes = array();
			if ( '' === $atts['type'] || 'customer_notes' === $atts['type'] ) {
				foreach ( $this->the_order->get_customer_order_notes() as $note ) {
					$notes[] = $note->comment_content;
				}
			} else {
				$args = array(
					'post_id' => wcj_get_order_id( $this->the_order ),
					'approve' => 'approve',
					'type'    => '',
				);
				remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );
				$comments = get_comments( $args );
				foreach ( $comments as $comment ) {
					if ( 'private_notes' === $atts['type'] && get_comment_meta( $comment->comment_ID, 'is_customer_note', true ) ) {
						continue;
					}
					$notes[] = make_clickable( $comment->comment_content );
				}
				add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );
			}
			if ( isset( $atts['limit'] ) && $atts['limit'] > 0 ) {
				$notes = array_slice( $notes, 0, $atts['limit'] );
			}
			return implode( $atts['sep'], $notes );
		}

		/**
		 * Wcj_order_customer_note.
		 *
		 * @version 2.7.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_customer_note( $atts ) {
			return ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->customer_note : $this->the_order->get_customer_note() );
		}

		/**
		 * Wcj_order_billing_country_name.
		 *
		 * @version 3.0.0
		 * @since   3.0.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_billing_country_name( $atts ) {
			$country_code = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->billing_country : $this->the_order->get_billing_country() );
			$country_name = wcj_get_country_name_by_code( $country_code );
			if ( false !== ( $country_name ) ) {
				return $country_name;
			} else {
				return $country_code;
			}
		}

		/**
		 * Wcj_order_shipping_country_name.
		 *
		 * @version 3.0.0
		 * @since   3.0.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_shipping_country_name( $atts ) {
			$country_code = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->shipping_country : $this->the_order->get_shipping_country() );
			$country_name = wcj_get_country_name_by_code( $country_code );
			if ( false !== ( $country_name ) ) {
				return $country_name;
			} else {
				return $country_code;
			}
		}

		/**
		 * Wcj_order_billing_phone.
		 *
		 * @version 2.7.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_billing_phone( $atts ) {
			return ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->billing_phone : $this->the_order->get_billing_phone() );
		}

		/**
		 * Wcj_order_items
		 *
		 * @version 3.5.0
		 * @since   2.5.7
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_items( $atts ) {
			$items     = array();
			$the_items = $this->the_order->get_items();
			foreach ( $the_items as $item_id => $item ) {
				switch ( $atts['field'] ) {
					case '_debug':
						$items[] = '<pre>' . wp_kses_post( $item, true ) . '</pre>';
						break;
					case '_qty_x_name':
						$items[] = ( isset( $item['qty'] ) && isset( $item['name'] ) ) ? $item['qty'] . ' x ' . $item['name'] : '';
						break;
					case '_sku':
						$_product_id = ( 0 !== $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
						$_product    = wc_get_product( $_product_id );
						if ( $_product ) {
							$items[] = $_product->get_sku();
						}
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
		 * Wcj_order_products_terms.
		 *
		 * @version 4.2.0
		 * @since   4.2.0
		 * @todo    [dev] (maybe) rename (i.e. make alias) to `[wcj_order_product_terms]` (same for `[wcj_order_products_meta]`, `[wcj_order_items_meta]` etc.)
		 * @todo    [dev] (maybe) make sorting optional (i.e. `$atts['sort']`)
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_products_terms( $atts ) {
			if ( '' === $atts['taxonomy'] ) {
				return '';
			}
			$terms = array();
			$items = $this->the_order->get_items();
			foreach ( $items as $item_id => $item ) {
				$product_terms = get_the_terms( $item['product_id'], $atts['taxonomy'] );
				if ( ! empty( $product_terms ) && ! is_wp_error( $product_terms ) ) {
					foreach ( $product_terms as $product_term ) {
						$terms[] = $product_term->name;
					}
				}
			}
			if ( 'yes' === $atts['unique_only'] ) {
				$terms = array_unique( $terms );
			}
			sort( $terms );
			return ( ! empty( $terms ) ? implode( $atts['sep'], $terms ) : '' );
		}

		/**
		 * Wcj_order_products_meta.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_products_meta( $atts ) {
			if ( '' === $atts['meta_key'] ) {
				return '';
			}
			$metas = array();
			$items = $this->the_order->get_items();
			foreach ( $items as $item_id => $item ) {
				$product_id = ( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
				$meta       = get_post_meta( $product_id, $atts['meta_key'], true );
				if ( '' !== ( $meta ) ) {
					$metas[] = $meta;
				}
			}
			if ( 'yes' === $atts['unique_only'] ) {
				$metas = array_unique( $metas );
			}
			return ( ! empty( $metas ) ? implode( $atts['sep'], $metas ) : '' );
		}

		/**
		 * Wcj_order_items_meta.
		 *
		 * @version 3.9.0
		 * @since   2.5.3
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_items_meta( $atts ) {
			if ( '' === $atts['meta_key'] ) {
				return '';
			}
			$items_metas = array();
			$the_items   = $this->the_order->get_items();
			foreach ( $the_items as $item_id => $item ) {
				$the_meta = ( WCJ_IS_WC_VERSION_BELOW_3 ? $this->the_order->get_item_meta( $item_id, $atts['meta_key'], true ) : wc_get_order_item_meta( $item_id, $atts['meta_key'], true ) );
				if ( '' !== $the_meta ) {
					$items_metas[] = $the_meta;
				}
			}
			if ( 'yes' === $atts['unique_only'] ) {
				$items_metas = array_unique( $items_metas );
			}
			return ( ! empty( $items_metas ) ? implode( $atts['sep'], $items_metas ) : '' );
		}

		/**
		 * Wcj_order_meta.
		 *
		 * @version 2.7.0
		 * @since   2.2.9
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_meta( $atts ) {
			return ( '' !== $atts['meta_key'] ? get_post_meta( wcj_get_order_id( $this->the_order ), $atts['meta_key'], true ) : '' );
		}

		/**
		 * Wcj_order_custom_meta_field.
		 *
		 * @version 2.3.0
		 * @since   2.2.9
		 * @param array $atts The user defined shortcode attributes.
		 * @deprecated
		 */
		public function wcj_order_custom_meta_field( $atts ) {
			return $this->wcj_order_checkout_field( $atts );
		}

		/**
		 * Wcj_order_checkout_field.
		 *
		 * @version 2.8.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_checkout_field( $atts ) {
			$field_id = (string) $atts['field_id'];
			if ( '' === $field_id ) {
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
		 * Wcj_order_shipping_address.
		 *
		 * @version 2.9.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_shipping_address( $atts ) {
			$shipping_address = $this->the_order->get_formatted_shipping_address();
			if ( '' !== $shipping_address ) {
				return $shipping_address;
			} elseif ( 'yes' === $atts['fallback_billing_address'] ) {
				return $this->the_order->get_formatted_billing_address();
			} else {
				return '';
			}
		}

		/**
		 * Wcj_order_status.
		 *
		 * @version 2.5.6
		 * @since   2.5.6
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_status( $atts ) {
			return $this->the_order->get_status();
		}

		/**
		 * Wcj_order_status_label.
		 *
		 * @version 2.7.0
		 * @since   2.7.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_status_label( $atts ) {
			$status_object = get_post_status_object( 'wc-' . $this->the_order->get_status() );
			return ( isset( $status_object->label ) ) ? $status_object->label : '';
		}

		/**
		 * Wcj_order_date.
		 *
		 * @version 3.2.4
		 * @todo    (maybe) rename `days` to `extra_days`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_date( $atts ) {
			return date_i18n( $atts['date_format'], strtotime( wcj_get_order_date( $this->the_order ) ) + $atts['days'] * 24 * 60 * 60 );
		}

		/**
		 * Wcj_order_time.
		 *
		 * @version 4.9.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_time( $atts ) {
			$order_date = wcj_get_order_date( $this->the_order )->format( 'Y-m-d H:i:s' );
			return wcj_pretty_utc_date( $order_date, $atts['time_format'] );
		}

		/**
		 * Wcj_order_number.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_number( $atts ) {
			return $this->the_order->get_order_number();
		}

		/**
		 * Wcj_order_id.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_id( $atts ) {
			return $atts['order_id'];
		}

		/**
		 * Wcj_order_shipping_price.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 *
		 * @version 2.5.6
		 */
		public function wcj_order_shipping_price( $atts ) {
			$the_result = ( $atts['excl_tax'] ) ? $this->the_order->get_total_shipping() : $this->the_order->get_total_shipping() + $this->the_order->get_shipping_tax();
			return $this->wcj_price_shortcode( $the_result, $atts );
		}

		/**
		 * Wcj_order_shipping_price_without_html_custom.
		 *
		 * @version 5.4.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_shipping_price_without_html_custom( $atts ) {
			$the_result = ( $atts['excl_tax'] ) ? $this->the_order->get_total_shipping() : $this->the_order->get_total_shipping() + $this->the_order->get_shipping_tax();
			return $the_result;
		}



		/**
		 * Wcj_order_total_discount.
		 *
		 * @version 2.4.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_discount( $atts ) {
			$the_discount = $this->the_order->get_total_discount( $atts['excl_tax'] );
			return $this->wcj_price_shortcode( $the_discount, $atts );
		}

		/**
		 * Wcj_order_shipping_tax.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_shipping_tax( $atts ) {
			return $this->wcj_price_shortcode( $this->the_order->get_shipping_tax(), $atts );
		}

		/**
		 * Wcj_order_total_tax_percent.
		 *
		 * @version 2.4.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_tax_percent( $atts ) {
			$order_total_tax_not_rounded = $this->the_order->get_cart_tax() + $this->the_order->get_shipping_tax();
			$order_total_excl_tax        = $this->the_order->get_total() - $order_total_tax_not_rounded;
			$order_total_tax_percent     = ( 0 === $order_total_excl_tax ) ? 0 : $order_total_tax_not_rounded / $order_total_excl_tax * 100;
			$order_total_tax_percent     = round( $order_total_tax_percent, $atts['precision'] );
			apply_filters( 'wcj_order_total_tax_percent', $order_total_tax_percent, $this->the_order );
			return number_format( $order_total_tax_percent, $atts['precision'] );
		}

		/**
		 * Wcj_order_subtotal_by_tax_class.
		 *
		 * @version 2.5.4
		 * @since   2.5.4
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_subtotal_by_tax_class( $atts ) {
			$subtotal_by_tax_class = 0;
			$tax_class             = ( 'standard' === $atts['tax_class'] ) ? '' : $atts['tax_class'];
			foreach ( $this->the_order->get_items() as $item ) {
				if ( $tax_class === $item['tax_class'] ) {
					$subtotal_by_tax_class += $item['line_subtotal'];
				}
			}
			return $this->wcj_price_shortcode( $subtotal_by_tax_class, $atts );
		}

		/**
		 * Wcj_order_total_by_tax_class.
		 *
		 * @version 2.5.4
		 * @since   2.5.4
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_by_tax_class( $atts ) {
			$total_by_tax_class = 0;
			$tax_class          = ( 'standard' === $atts['tax_class'] ) ? '' : $atts['tax_class'];
			foreach ( $this->the_order->get_items() as $item ) {
				if ( $tax_class === $item['tax_class'] ) {
					$total_by_tax_class += $item['line_total'];
				}
			}
			return $this->wcj_price_shortcode( $total_by_tax_class, $atts );
		}

		/**
		 * Wcj_order_tax_by_class.
		 *
		 * @version 2.5.5
		 * @since   2.5.4
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_tax_by_class( $atts ) {
			$tax_class          = ( 'standard' === $atts['tax_class'] ) ? '' : $atts['tax_class'];
			$total_tax_by_class = 0;
			foreach ( $this->the_order->get_items() as $item ) {
				if ( $tax_class === $item['tax_class'] ) {

					$total_tax_by_class += $item['line_tax'];
				}
			}
			return $this->wcj_price_shortcode( $total_tax_by_class, $atts );
		}

		/**
		 * Wcj_order_taxes_html.
		 *
		 * @version 4.6.0
		 * @since   2.5.3
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_taxes_html( $atts ) {
			$order_taxes = $this->the_order->get_taxes();
			$taxes_html  = '';
			$label_style = '';
			for ( $i = 1; $i <= $atts['tax_label_spaces']; $i++ ) {
				$label_style .= '&nbsp;';
			}
			foreach ( $order_taxes as $order_tax ) {
				if ( true === filter_var( $atts['show_label'], FILTER_VALIDATE_BOOLEAN ) ) {
					$taxes_html .= ( isset( $order_tax['label'] ) ) ? $order_tax['label'] . ': ' . $label_style : '';

				}
				$amount      = 0;
				$amount     += ( isset( $order_tax['tax_amount'] ) ) ? $order_tax['tax_amount'] : 0;
				$amount     += ( isset( $order_tax['shipping_tax_amount'] ) ) ? $order_tax['shipping_tax_amount'] : 0;
				$taxes_html .= $this->wcj_price_shortcode( $amount, $atts ) . '<br>';
			}
			return $taxes_html;
		}

		/**
		 * Wcj_order_total_tax.
		 *
		 * @version 3.1.2
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_tax( $atts ) {
			$the_items          = $this->the_order->get_items();
			$exclude_item_total = 0;
			$exclude_item_tax   = 0;
			foreach ( $the_items as $item_id => $item ) {
				$the_product = $item->get_product( $item );

				// Check if it's not excluded by category.
				if ( '' !== $atts['exclude_by_categories'] && $the_product ) {
					if ( wcj_product_has_terms( $the_product, $atts['exclude_by_categories'], 'product_cat' ) ) {
						$exclude_item_tax += $item->get_subtotal_tax();
					}
				}

				// Check if it's not excluded by tag.
				if ( '' !== $atts['exclude_by_tags'] && $the_product ) {
					if ( wcj_product_has_terms( $the_product, $atts['exclude_by_tags'], 'product_tag' ) ) {
						$exclude_item_tax += $item->get_subtotal_tax();
					}
				}

				// Check if it's not excluded by product attribute.
				if ( $the_product && '' !== $atts['exclude_by_attribute__name'] ) {
					$product_attributes = $the_product->get_attributes();
					if ( isset( $product_attributes[ $atts['exclude_by_attribute__name'] ] ) ) {
						$product_attribute = $product_attributes[ $atts['exclude_by_attribute__name'] ];
						if ( is_object( $product_attribute ) ) {
							if ( 'WC_Product_Attribute' === get_class( $product_attribute ) && in_array( $atts['exclude_by_attribute__value'], $product_attribute->get_options(), true ) ) {
								$exclude_item_tax += $item->get_subtotal_tax();
							}
						} elseif ( $atts['exclude_by_attribute__name'] ) {
							$exclude_item_tax += $item->get_subtotal_tax();
						}
					}
				}
			}
			$total_tax  = $this->the_order->get_total_tax();
			$total_tax -= $exclude_item_tax;
			$total_tax  = $this->wcj_price_shortcode( apply_filters( 'wcj_order_total_tax', $total_tax, $this->the_order ), $atts );

			return $total_tax;
		}

		/**
		 * Wcj_order_total_tax_without_html_custom.
		 *
		 * @version 5.4.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_tax_without_html_custom( $atts ) {
			return apply_filters( 'wcj_order_total_tax', $this->the_order->get_total_tax(), $this->the_order );
		}


		/**
		 * Wcj_order_total_tax_after_refund.
		 *
		 * @version 3.1.2
		 * @since   3.1.2
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_tax_after_refund( $atts ) {
			return $this->wcj_price_shortcode( ( $this->the_order->get_total_tax() - $this->the_order->get_total_tax_refunded() ), $atts );
		}

		/**
		 * Wcj_order_subtotal_plus_shipping.
		 *
		 * @version 4.5.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_subtotal_plus_shipping( $atts ) {
			$the_subtotal = $this->the_order->get_subtotal();
			$the_shipping = $this->the_order->get_total_shipping();
			$fees_total   = 0;
			if ( isset( $atts['plus_fees'] ) && true === filter_var( $atts['plus_fees'], FILTER_VALIDATE_BOOLEAN ) ) {
				$fees_total = wcj_get_order_fees_total( $this->the_order );
			}
			return $this->wcj_price_shortcode( $the_subtotal + $the_shipping + $fees_total, $atts );
		}

		/**
		 * Wcj_order_subtotal.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_subtotal( $atts ) {

			if ( 'yes' === $atts['round_by_line'] ) {
				$the_subtotal = 0;
				foreach ( $this->the_order->get_items() as $item ) {
					$the_product = $item->get_product( $item );
					// Check if it's not excluded by category.
					if ( '' !== $atts['exclude_by_categories'] && $the_product ) {
						if ( wcj_product_has_terms( $the_product, $atts['exclude_by_categories'], 'product_cat' ) ) {
							continue;
						}
					}
					// Check if it's not excluded by tag.
					if ( '' !== $atts['exclude_by_tags'] && $the_product ) {
						if ( wcj_product_has_terms( $the_product, $atts['exclude_by_tags'], 'product_tag' ) ) {
							continue;
						}
					}
					// Check if it's not excluded by product attribute.
					if ( '' !== $atts['exclude_by_attribute__name'] && $the_product ) {
						$product_attributes = $the_product->get_attributes();
						if ( isset( $product_attributes[ $atts['exclude_by_attribute__name'] ] ) ) {
							$product_attribute = $product_attributes[ $atts['exclude_by_attribute__name'] ];
							if ( is_object( $product_attribute ) ) {
								if ( 'WC_Product_Attribute' === get_class( $product_attribute ) && in_array( $atts['exclude_by_attribute__value'], $product_attribute->get_options(), true ) ) {
									continue;
								}
							} elseif ( $atts['exclude_by_attribute__name'] ) {
								continue;
							}
						}
					}

					$the_subtotal += $this->the_order->get_line_subtotal( $item, false, true );
				}
			} else {
				$the_items             = $this->the_order->get_items();
				$exclude_item_total    = 0;
				$exclude_item_subtotal = 0;

				foreach ( $the_items as $item_id => $item ) {
					$the_product = $item->get_product( $item );

					// Check if it's not excluded by category.
					if ( '' !== $atts['exclude_by_categories'] && $the_product ) {
						if ( wcj_product_has_terms( $the_product, $atts['exclude_by_categories'], 'product_cat' ) ) {
							$exclude_item_subtotal += $item['total'];
						}
					}

					// Check if it's not excluded by tag.
					if ( '' !== $atts['exclude_by_tags'] && $the_product ) {
						if ( wcj_product_has_terms( $the_product, $atts['exclude_by_tags'], 'product_tag' ) ) {
							$exclude_item_subtotal += $item['total'];
						}
					}

					// Check if it's not excluded by product attribute.
					if ( $the_product && '' !== $atts['exclude_by_attribute__name'] ) {
						$product_attributes = $the_product->get_attributes();
						if ( isset( $product_attributes[ $atts['exclude_by_attribute__name'] ] ) ) {
							$product_attribute = $product_attributes[ $atts['exclude_by_attribute__name'] ];
							if ( is_object( $product_attribute ) ) {
								if ( 'WC_Product_Attribute' === get_class( $product_attribute ) && in_array( $atts['exclude_by_attribute__value'], $product_attribute->get_options(), true ) ) {
									$exclude_item_subtotal += $item['total'];

								}
							} elseif ( $atts['exclude_by_attribute__name'] ) {
								$exclude_item_subtotal += $item['total'];
							}
						}
					}
				}
				$the_subtotal  = $this->the_order->get_subtotal();
				$the_subtotal -= $exclude_item_subtotal;
			}

			return $this->wcj_price_shortcode( $the_subtotal, $atts );
		}


		/**
		 * Wcj_order_total_without_html_custom.
		 *
		 * @version 5.4.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_without_html_custom( $atts ) {
			$order_total = ( true === $atts['excl_tax'] ) ? $this->the_order->get_total() - $this->the_order->get_total_tax() : $this->the_order->get_total();
			return $order_total;
		}


		/**
		 * Wcj_order_subtotal_to_display.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_subtotal_to_display( $atts ) {
			return $this->the_order->get_subtotal_to_display( false, $atts['tax_display'] );
		}

		/**
		 * Wcj_order_total_excl_tax.
		 *
		 * @version 2.5.6
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_excl_tax( $atts ) {
			$order_total = $this->the_order->get_total() - $this->the_order->get_total_tax();
			$order_total = apply_filters( 'wcj_order_total_excl_tax', $order_total, $this->the_order );
			return $this->wcj_price_shortcode( $order_total, $atts );
		}

		/**
		 * Wcj_order_currency.
		 *
		 * @version 2.7.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_currency( $atts ) {
			return wcj_get_order_currency( $this->the_order );
		}

		/**
		 * Wcj_order_total_excl_shipping.
		 *
		 * @version 2.5.6
		 * @since   2.5.6
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_excl_shipping( $atts ) {
			$order_total_excl_shipping = ( true === $atts['excl_tax'] ) ?
			$this->the_order->get_total() - $this->the_order->get_total_shipping() - $this->the_order->get_total_tax() :
			$this->the_order->get_total() - $this->the_order->get_total_shipping() - $this->the_order->get_shipping_tax();
			return $this->wcj_price_shortcode( $order_total_excl_shipping, $atts );
		}

		/**
		 * Wcj_order_total.
		 *
		 * @version 5.3.7
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total( $atts ) {
			$the_items          = $this->the_order->get_items();
			$exclude_item_total = 0;

			foreach ( $the_items as $item_id => $item ) {
				$the_product = $item->get_product( $item );

				// Check if it's not excluded by category.
				if ( '' !== $atts['exclude_by_categories'] && $the_product ) {
					if ( wcj_product_has_terms( $the_product, $atts['exclude_by_categories'], 'product_cat' ) ) {
						$exclude_item_total += $item->get_total();
					}
				}

				// Check if it's not excluded by tag.
				if ( '' !== $atts['exclude_by_tags'] && $the_product ) {
					if ( wcj_product_has_terms( $the_product, $atts['exclude_by_tags'], 'product_tag' ) ) {
						$exclude_item_total += $item->get_total();
					}
				}

				// Check if it's not excluded by product attribute.
				if ( $the_product && '' !== $atts['exclude_by_attribute__name'] ) {
					$product_attributes = $the_product->get_attributes();
					if ( isset( $product_attributes[ $atts['exclude_by_attribute__name'] ] ) ) {
						$product_attribute = $product_attributes[ $atts['exclude_by_attribute__name'] ];
						if ( is_object( $product_attribute ) ) {
							if ( 'WC_Product_Attribute' === get_class( $product_attribute ) && in_array( $atts['exclude_by_attribute__value'], $product_attribute->get_options(), true ) ) {
								$exclude_item_total += $item->get_total();
							}
						} elseif ( $atts['exclude_by_attribute__name'] ) {
							$exclude_item_total += $item->get_total();
						}
					}
				}
			}

			$order_total = ( true === $atts['excl_tax'] ) ? $this->the_order->get_total() - $this->the_order->get_total_tax() : $this->the_order->get_total();

			$order_total -= $exclude_item_total;

			return $this->wcj_price_shortcode( $order_total, $atts );
		}








		/**
		 * Wcj_order_total_after_refund.
		 *
		 * @version 3.1.2
		 * @since   3.1.2
		 * @todo    (maybe) `get_total_shipping_refunded()`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_after_refund( $atts ) {
			$order_total_after_refund = $this->the_order->get_total() - $this->the_order->get_total_refunded();
			if ( true === $atts['excl_tax'] ) {
				$order_total_after_refund -= ( $this->the_order->get_total_tax() - $this->the_order->get_total_tax_refunded() );
			}
			return $this->wcj_price_shortcode( $order_total_after_refund, $atts );
		}

		/**
		 * Mb_ucfirst - for wcj_order_total_in_words.
		 *
		 * @version 5.3.6
		 * @since   2.5.9
		 * @param string $string The user defined shortcode string.
		 */
		public function mb_ucfirst( $string ) {
			return ucfirst( mb_substr( $string, 0, 1 ) ) . mb_substr( $string, 1 );
		}

		/**
		 * Wcj_order_total_in_words.
		 *
		 * @version 4.1.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_total_in_words( $atts ) {

			$order_total         = ( true === $atts['excl_tax'] ) ? $this->the_order->get_total() - $this->the_order->get_total_tax() : $this->the_order->get_total();
			$order_total_whole   = intval( $order_total );
			$order_total_decimal = round( ( $order_total - $order_total_whole ) * 100 );

			$the_number_in_words  = '%s %s';
			$the_number_in_words .= ( 0 !== $order_total_decimal ) ? ', %s %s.' : '.';

			$whole   = ( '' === $atts['whole'] ?
			( isset( $atts['use_currency_symbol'] ) && 'yes' === $atts['use_currency_symbol'] ?
				get_woocommerce_currency_symbol( $this->the_order->get_currency() ) : $this->the_order->get_currency()
			) : $atts['whole'] );
			$decimal = $atts['decimal'];

			switch ( $atts['lang'] ) {
				case 'LT':
					return sprintf(
						$the_number_in_words,
						$this->mb_ucfirst( convert_number_to_words_lt( $order_total_whole ) ),
						$whole,
						$this->mb_ucfirst( convert_number_to_words_lt( $order_total_decimal ) ),
						$decimal
					);
				case 'BG':
					return sprintf(
						$the_number_in_words,
						$this->mb_ucfirst( trim( convert_number_to_words_bg( $order_total_whole ) ) ),
						$whole,
						$this->mb_ucfirst( trim( convert_number_to_words_bg( $order_total_decimal ) ) ),
						$decimal
					);
				default: // 'EN'.
					return sprintf(
						$the_number_in_words,
						ucfirst( convert_number_to_words( $order_total_whole ) ),
						$whole,
						ucfirst( convert_number_to_words( $order_total_decimal ) ),
						$decimal
					);
			}
		}

		/**
		 * Wcj_order_payment_method_notes
		 *
		 * @version 6.0.5
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_order_payment_method_notes( $atts ) {

			$get_payment_notes = 'wcj_gateways_' . get_post_meta( wcj_get_order_id( $this->the_order ), '_payment_method', true ) . '_pdf_notes';
			return do_shortcode( get_option( $get_payment_notes ) );
		}
	}

endif;

return new WCJ_Orders_Shortcodes();

<?php
/**
 * Booster for WooCommerce - Shortcodes - General
 *
 * @version 6.0.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_General_Shortcodes' ) ) :

		/**
		 * WCJ_General_Shortcodes.
		 *
		 * @version 4.1.0
		 */
	class WCJ_General_Shortcodes extends WCJ_Shortcodes {

		/**
		 * Constructor.
		 *
		 * @version 4.1.0
		 */
		public function __construct() {

			$this->the_shortcodes = array(
				'wcj_barcode',
				'wcj_button_toggle_tax_display',
				'wcj_country_select_drop_down_list',
				'wcj_cross_sell_display',
				'wcj_currency_exchange_rate',
				'wcj_currency_exchange_rate_wholesale_module',
				'wcj_currency_exchange_rates_table',
				'wcj_currency_select_drop_down_list',
				'wcj_currency_select_link_list',
				'wcj_currency_select_radio_list',
				'wcj_current_currency_code',
				'wcj_current_currency_symbol',
				'wcj_current_date',
				'wcj_current_datetime',
				'wcj_current_time',
				'wcj_current_timestamp',
				'wcj_customer_billing_country',
				'wcj_customer_meta',
				'wcj_customer_order_count',
				'wcj_customer_shipping_country',
				'wcj_customer_total_spent',
				'wcj_empty_cart_button',
				'wcj_get_left_to_free_shipping',
				'wcj_get_option',
				'wcj_image',
				'wcj_post_meta_sum',
				'wcj_product_category_count',
				'wcj_request_value',
				'wcj_selector',
				'wcj_session_value',
				'wcj_shipping_costs_table',
				'wcj_shipping_time_table',
				'wcj_site_url',
				'wcj_store_address',
				'wcj_tcpdf_barcode',
				'wcj_tcpdf_pagebreak',
				'wcj_tcpdf_rectangle',
				'wcj_text',
				'wcj_upsell_display',
				'wcj_wc_session_value',
				'wcj_wholesale_price_table',
				'wcj_wp_option',
				'wcj_wpml',
				'wcj_wpml_translate',
			);

			$this->the_atts = array(
				'date_format'           => wcj_get_option( 'date_format' ),
				'time_format'           => wcj_get_option( 'time_format' ),
				'datetime_format'       => wcj_get_option( 'date_format' ) . ' ' . wcj_get_option( 'time_format' ),
				'lang'                  => '',
				'form_method'           => 'post',
				'class'                 => '',
				'style'                 => '',
				'countries'             => '',
				'currencies'            => '',
				'content'               => '',
				'heading_format'        => 'from %level_min_qty% pcs.',
				'before_level_max_qty'  => '-',
				'last_level_max_qty'    => '+',
				'replace_with_currency' => 'no',
				'hide_if_zero_quantity' => 'no',
				'table_format'          => 'horizontal',
				'key'                   => '',
				'full_country_name'     => 'yes',
				'multiply_by'           => 1,
				'default'               => '',
				'from'                  => '',
				'to'                    => '',
				'columns_style'         => 'text-align: center;',
				'selector_type'         => 'country',
				'option'                => '',
				'company'               => '',
				'label_incl'            => __( 'Tax toggle (incl.)', 'woocommerce-jetpack' ),
				'label_excl'            => __( 'Tax toggle (excl.)', 'woocommerce-jetpack' ),
				'slug'                  => '',
				'code'                  => '',
				'type'                  => '',
				'dimension'             => '2D',
				'width'                 => 0,
				'height'                => 0,
				'color'                 => 'black',
				'x'                     => 0,
				'y'                     => 0,
			);

			parent::__construct();

		}

		/**
		 * Wcj_post_meta_sum.
		 *
		 * @version 6.0.0
		 * @since   4.1.0
		 * @param array $atts defined shortcode attributes.
		 */
		public function wcj_post_meta_sum( $atts ) {
			if ( '' === $atts['key'] ) {
				return '';
			}
			global $wpdb;
			$sum = $wpdb->get_var( $wpdb->prepare( "SELECT sum(meta_value) FROM $wpdb->postmeta WHERE meta_key = %s", $atts['key'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return ( ! empty( $atts['offset'] ) ? $sum + $atts['offset'] : $sum );
		}

		/**
		 * Wcj_get_option.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @todo    [dev] handle multidimensional arrays
		 * @todo    [dev] maybe also add `get_site_option()`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_get_option( $atts ) {
			$result = ( isset( $atts['name'] ) ? wcj_get_option( $atts['name'], ( isset( $atts['default'] ) ? $atts['default'] : false ) ) : '' );
			return ( is_array( $result ) ?
			( isset( $atts['field'] ) && isset( $result[ $atts['field'] ] ) ? $result[ $atts['field'] ] : implode( ', ', $result ) ) :
			$result );
		}

		/**
		 * Wcj_shipping_costs_table.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @todo    [dev] sort `$table` before using
		 * @todo    [feature] add `volume` prop
		 * @todo    [feature] add `total` prop
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_shipping_costs_table( $atts ) {
			$_cart = WC()->cart;
			if ( ! empty( $atts['table'] ) && ( $_cart ) ) {
				if ( ! isset( $atts['cmp'] ) ) {
					$atts['cmp'] = 'max';
				}
				if ( ! isset( $atts['prop'] ) ) {
					$atts['prop'] = 'quantity';
				}
				switch ( $atts['prop'] ) {
					case 'weight':
						$param_value = $_cart->get_cart_contents_weight();
						break;
					default: // quantity.
						$param_value = $_cart->get_cart_contents_count();
				}
				$table = array_map( 'trim', explode( '|', $atts['table'] ) );
				if ( 'min' === $atts['cmp'] ) {
					$table = array_reverse( $table );
				}
				foreach ( $table as $row ) {
					$cells = array_map( 'trim', explode( '-', $row ) );
					if ( 2 !== count( $cells ) ) {
						return '';
					}
					if (
					( 'min' === $atts['cmp'] && $param_value >= $cells[0] ) ||
					( 'max' === $atts['cmp'] && $param_value <= $cells[0] )
					) {
						return $cells[1];
					}
				}
			}
			return '';
		}

		/**
		 * Wcj_upsell_display.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @todo    (maybe) move to Products shortcodes
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_upsell_display( $atts ) {
			woocommerce_upsell_display(
				( isset( $atts['limit'] ) ? $atts['limit'] : '-1' ),
				( isset( $atts['columns'] ) ? $atts['columns'] : 4 ),
				( isset( $atts['orderby'] ) ? $atts['orderby'] : 'rand' ),
				( isset( $atts['order'] ) ? $atts['order'] : 'desc' )
			);
		}

		/**
		 * Wcj_cross_sell_display.
		 *
		 * @version 3.9.1
		 * @since   3.6.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cross_sell_display( $atts ) {

			if ( ! function_exists( 'WC' ) || ! isset( WC()->cart ) ) {
				return '';
			}

			$limit   = ( isset( $atts['limit'] ) ? $atts['limit'] : 2 );
			$columns = ( isset( $atts['columns'] ) ? $atts['columns'] : 2 );
			$orderby = ( isset( $atts['orderby'] ) ? $atts['orderby'] : 'rand' );
			$order   = ( isset( $atts['order'] ) ? $atts['order'] : 'desc' );

			// Get visible cross sells then sort them at random.
			$cross_sells = array_filter( array_map( 'wc_get_product', WC()->cart->get_cross_sells() ), 'wc_products_array_filter_visible' );

			wc_set_loop_prop( 'name', 'cross-sells' );
			wc_set_loop_prop( 'columns', apply_filters( 'woocommerce_cross_sells_columns', $columns ) );

			// Handle orderby and limit results.
			$orderby     = apply_filters( 'woocommerce_cross_sells_orderby', $orderby );
			$order       = apply_filters( 'woocommerce_cross_sells_order', $order );
			$cross_sells = wc_products_array_orderby( $cross_sells, $orderby, $order );
			$limit       = apply_filters( 'woocommerce_cross_sells_total', $limit );
			$cross_sells = $limit > 0 ? array_slice( $cross_sells, 0, $limit ) : $cross_sells;

			ob_start();
			wc_get_template(
				'cart/cross-sells.php',
				array(
					'cross_sells'    => $cross_sells,

					// Not used now, but used in previous version of up-sells.php.
					'posts_per_page' => $limit,
					'orderby'        => $orderby,
					'columns'        => $columns,
				)
			);
			return ob_get_clean();
		}

		/**
		 * Wcj_shipping_time_table.
		 *
		 * @version 3.5.0
		 * @since   3.5.0
		 * @todo    `$atts['shipping_class_term_id']` - class term ID is not visible anywhere for admin, so probably need to use `slug` instead
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_shipping_time_table( $atts ) {
			$do_use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_time_use_shipping_instance', 'no' ) );
			$do_use_shipping_classes   = ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_shipping_time_use_shipping_classes', 'no' ) ) );
			$shipping_class_term_id    = ( isset( $atts['shipping_class_term_id'] ) ? $atts['shipping_class_term_id'] : '0' );
			$option_id_shipping_class  = ( $do_use_shipping_classes ? '_class_' . $shipping_class_term_id : '' );
			return wcj_get_shipping_time_table( $do_use_shipping_instances, $option_id_shipping_class );
		}

		/**
		 * Wcj_wc_session_value.
		 *
		 * @version 3.4.0
		 * @since   3.4.0
		 * @todo    handle arrays
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_wc_session_value( $atts ) {
			return ( '' === $atts['key'] ? '' : WC()->session->get( $atts['key'], '' ) );
		}

		/**
		 * Wcj_session_value.
		 *
		 * @version 3.4.0
		 * @since   3.4.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_session_value( $atts ) {
			return ( '' === $atts['key'] || ! isset( $_SESSION[ $atts['key'] ] ) ? '' : $_SESSION[ $atts['key'] ] );
		}

		/**
		 * Wcj_request_value.
		 *
		 * @version 5.6.2
		 * @since   3.4.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_request_value( $atts ) {
			return ( ( '' === $atts['key'] || ! isset( $_REQUEST[ $atts['key'] ] ) ) ? '' : sanitize_text_field( wp_unslash( $_REQUEST[ $atts['key'] ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		/**
		 * Wcj_tcpdf_rectangle.
		 *
		 * @version 3.4.0
		 * @since   3.3.0
		 * @see     https://tcpdf.org/examples/example_012/
		 * @todo    add more atts (e.g. style, fill color etc.)
		 * @todo    add options to take `width` and `height` from custom source (e.g. item or order meta)
		 * @todo    (maybe) move all `tcpdf` shortcodes to `class-wcj-shortcodes-tcpdf.php`
		 * @todo    (maybe) create general `[wcj_tcpdf_method]` shortcode (not sure how to solve `$params` part though)
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_tcpdf_rectangle( $atts ) {

			$style        = 'D';
			$border_style = array(
				'all' => array(
					'width' => 0.5,
					'cap'   => 'round',
					'join'  => 'round',
					'dash'  => 0,
					'color' => array( 0, 0, 0 ),
				),
			);
			$fill_color   = array();

			$params = array(
				$atts['x'],
				$atts['y'],
				$atts['width'],
				$atts['height'],
				$style,
				$border_style,
				$fill_color,
			);

			return wcj_tcpdf_method( 'Rect', $params );
		}

		/**
		 * Wcj_tcpdf_barcode.
		 *
		 * @version 3.3.0
		 * @since   3.2.4
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_tcpdf_barcode( $atts ) {
			return wcj_tcpdf_barcode( $atts );
		}

		/**
		 * Wcj_barcode.
		 *
		 * @version 3.3.0
		 * @since   3.2.4
		 * @todo    (maybe) current page url as `code`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_barcode( $atts ) {
			return wcj_barcode( $atts );
		}

		/**
		 * Wcj_product_category_count.
		 *
		 * @version 3.2.4
		 * @since   3.2.4
		 * @todo    option to use `name` or `term_id` instead of `slug`
		 * @todo    `pad_counts`
		 * @todo    add similar `[wcj_product_tag_count]` and `[wcj_product_taxonomy_count]`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_product_category_count( $atts ) {
			if ( ! isset( $atts['slug'] ) ) {
				return '';
			}
			$product_categories = get_categories(
				array(
					'hide_empty'   => 0,
					'hierarchical' => 1,
					'taxonomy'     => 'product_cat',
					'slug'         => $atts['slug'],
				)
			);
			return ( isset( $product_categories[0]->count ) ? $product_categories[0]->count : '' );
		}

		/**
		 * Wcj_button_toggle_tax_display.
		 *
		 * @version 5.6.7
		 * @since   3.2.4
		 * @todo    (dev) different style/class for different tax state
		 * @todo    (maybe) `get` instead of `post`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_button_toggle_tax_display( $atts ) {
			$session_value = wcj_session_get( 'wcj_toggle_tax_display' );
			$current_value = ( ( '' === $session_value || null === $session_value ) ? wcj_get_option( 'woocommerce_tax_display_shop', 'excl' ) : $session_value );
			$current_value = '' === $current_value ? 'excl' : $current_value;
			$label         = $atts[ 'label_' . $current_value ];
			return '<form method="post" action="">' . wp_nonce_field( 'wcj_button_toggle_tax_display', 'wcj-button-toggle-tax-display-nonce' ) . '<input type="submit" name="wcj_button_toggle_tax_display"' .
			' class="' . $atts['class'] . '" style="' . $atts['style'] . '" value="' . $label . '"></form>';
		}

		/**
		 * Wcj_store_address.
		 *
		 * @version 3.2.1
		 * @since   3.2.1
		 * @todo    `force_country_display` - make optional
		 * @todo    `remove_filter` will remove all `__return_true` functions (even added elsewhere)
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_store_address( $atts ) {
			add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );
			$return = WC()->countries->get_formatted_address(
				array(
					'company'   => $atts['company'],
					'address_1' => WC()->countries->get_base_address(),
					'address_2' => WC()->countries->get_base_address_2(),
					'city'      => WC()->countries->get_base_city(),
					'state'     => WC()->countries->get_base_state(),
					'postcode'  => WC()->countries->get_base_postcode(),
					'country'   => WC()->countries->get_base_country(),
				)
			);
			remove_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );
			return $return;
		}

		/**
		 * Wcj_wp_option.
		 *
		 * @version 3.2.1
		 * @since   3.2.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_wp_option( $atts ) {
			return ( '' !== $atts['option'] ? wcj_get_option( $atts['option'], $atts['default'] ) : '' );
		}

		/**
		 * Wcj_current_currency_code.
		 *
		 * @version 3.1.1
		 * @since   3.1.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_current_currency_code( $atts ) {
			return get_woocommerce_currency();
		}

		/**
		 * Wcj_current_currency_symbol.
		 *
		 * @version 3.1.1
		 * @since   3.1.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_current_currency_symbol( $atts ) {
			return get_woocommerce_currency_symbol();
		}

		/**
		 * Wcj_selector.
		 *
		 * @version 6.0.0
		 * @since   3.1.0
		 * @todo    add `default` attribute
		 * @todo    (maybe) add more selector types (e.g.: currency)
		 * @todo    (maybe) remove country switcher and currency switcher shortcodes and use this shortcode instead
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_selector( $atts ) {
			$html           = '';
			$options        = '';
			$countries      = apply_filters( 'booster_option', 'all', wcj_get_option( 'wcj_product_by_country_country_list_shortcode', 'all' ) );
			$wpnonce        = isset( $_REQUEST[ 'wcj_' . $atts['selector_type'] . '_selector-nonce' ] ) ? wp_verify_nonce( sanitize_key( $_REQUEST[ 'wcj_' . $atts['selector_type'] . '_selector-nonce' ] ), 'wcj_' . $atts['selector_type'] . '_selector' ) : false;
			$selected_value = ( ( $wpnonce && isset( $_REQUEST[ 'wcj_' . $atts['selector_type'] . '_selector' ] ) ) ?
			sanitize_text_field( wp_unslash( $_REQUEST[ 'wcj_' . $atts['selector_type'] . '_selector' ] ) ) :
			wcj_session_get( 'wcj_selected_' . $atts['selector_type'] )
			);

			switch ( $countries ) {
				case 'wc':
					$countries = WC()->countries->get_allowed_countries();
					break;
				default: // 'all'.
					$countries = wcj_get_countries();
					break;
			}

			switch ( $atts['selector_type'] ) {
				case 'product_custom_visibility':
					$options = wcj_get_select_options( wcj_get_option( 'wcj_product_custom_visibility_options_list', '' ) );
					break;
				default: // 'country'.
					$options = $countries;
					asort( $options );
					break;
			}
			foreach ( $options as $value => $title ) {
				$html .= '<option value="' . $value . '" ' . selected( $selected_value, $value, false ) . '>' . $title . '</option>';
			}
			return '<form method="post" action="">' .
			'<select name="wcj_' . $atts['selector_type'] . '_selector" class="wcj_' . $atts['selector_type'] . '_selector" onchange="this.form.submit()">' .
				$html .
			'</select>' .
			wp_nonce_field( 'wcj_' . $atts['selector_type'] . '_selector', 'wcj_' . $atts['selector_type'] . '_selector-nonce' ) .
			'</form>';
		}

		/**
		 * Wcj_site_url.
		 *
		 * @version 2.9.1
		 * @since   2.9.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_site_url( $atts ) {
			return site_url();
		}

		/**
		 * Wcj_currency_exchange_rate.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @todo    (maybe) add similar function
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_currency_exchange_rate( $atts ) {
			return ( '' !== $atts['from'] && '' !== $atts['to'] ) ? wcj_get_option( 'wcj_currency_exchange_rates_' . sanitize_title( $atts['from'] . $atts['to'] ) ) : '';
		}

		/**
		 * Wcj_currency_exchange_rate_wholesale_module.
		 *
		 * @version 5.4.5
		 * @since   5.4.5
		 * @todo    (maybe) add similar function
		 */
		public function wcj_currency_exchange_rate_wholesale_module() {
			$store_base_currency    = strtolower( get_option( 'woocommerce_currency' ) );
			$store_current_currency = strtolower( get_woocommerce_currency() );
			return ( '' !== $store_base_currency && '' !== $store_current_currency ) ? wcj_get_option( 'wcj_currency_exchange_rates_' . sanitize_title( $store_base_currency . $store_current_currency ) ) : '';

		}


		/**
		 * Wcj_currency_exchange_rates_table.
		 *
		 * @version 6.0.1
		 * @since   2.9.0
		 * @todo    (maybe) add similar function
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_currency_exchange_rates_table( $atts ) {
			$all_currencies = w_c_j()->all_modules['currency_exchange_rates']->get_all_currencies_exchange_rates_settings();
			$table_data     = array();
			foreach ( $all_currencies as $currency ) {
				$table_data[] = array( $currency['title'], wcj_get_option( $currency['id'] ) );
			}
			if ( ! empty( $table_data ) ) {
				return wcj_get_table_html(
					$table_data,
					array(
						'table_class'        => 'wcj_currency_exchange_rates_table',
						'table_heading_type' => 'vertical',
					)
				);
			} else {
				return '';
			}
		}

		/**
		 * Wcj_empty_cart_button.
		 *
		 * @version 2.8.0
		 * @since   2.8.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_empty_cart_button( $atts ) {
			if ( ! wcj_is_module_enabled( 'empty_cart' ) ) {
				/* translators: %s: translation added */
				return '<p>' . sprintf( __( '"%s" module is not enabled!', 'woocommerce-jetpack' ), __( 'Empty Cart Button', 'woocommerce-jetpack' ) ) . '</p>';
			}
			return wcj_empty_cart_button_html();
		}

		/**
		 * Wcj_current_time.
		 *
		 * @version 5.6.8
		 * @since   2.6.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_current_time( $atts ) {
			return date_i18n( $atts['time_format'], wcj_get_timestamp_date_from_gmt() );
		}

		/**
		 * Wcj_current_datetime.
		 *
		 * @version 5.6.8
		 * @since   2.6.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_current_datetime( $atts ) {
			return date_i18n( $atts['datetime_format'], wcj_get_timestamp_date_from_gmt() );
		}

		/**
		 * Wcj_current_timestamp.
		 *
		 * @version 5.6.8
		 * @since   2.6.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_current_timestamp( $atts ) {
			return wcj_get_timestamp_date_from_gmt();
		}

		/**
		 * Wcj_customer_order_count.
		 *
		 * @version 3.4.0
		 * @since   3.4.0
		 * @todo    `hide_if_zero`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_customer_order_count( $atts ) {
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$customer     = new WC_Customer( $current_user->ID );
				return $customer->get_order_count();
			} else {
				return '';
			}
		}

		/**
		 * Wcj_customer_total_spent.
		 *
		 * @version 3.4.0
		 * @since   3.4.0
		 * @todo    `hide_if_zero`
		 * @todo    `hide_currency`
		 * @todo    (maybe) solve multicurrency issue
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_customer_total_spent( $atts ) {
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$customer     = new WC_Customer( $current_user->ID );
				return wc_price( $customer->get_total_spent() );
			} else {
				return '';
			}
		}

		/**
		 * Wcj_customer_billing_country.
		 *
		 * @version 2.5.8
		 * @since   2.5.8
		 * @see     https://docs.woocommerce.com/wc-apidocs/class-WC_Customer.html
		 * @todo    move all customer shortcodes to new class `WCJ_Customers_Shortcodes` (and there `$this->the_customer = new WC_Customer( $current_user->ID )`)
		 * @todo    add `[wcj_customer_taxable_address]` (with `$customer->get_taxable_address()`)
		 * @todo    add `[wcj_customer_prop]` (with `$customer->get_{$atts['key']}()`)
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_customer_billing_country( $atts ) {
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$meta         = get_user_meta( $current_user->ID, 'billing_country', true );
				if ( '' !== ( $meta ) ) {
					return ( 'yes' === $atts['full_country_name'] ) ? wcj_get_country_name_by_code( $meta ) : $meta;
				}
			}
			return '';
		}

		/**
		 * Wcj_customer_shipping_country.
		 *
		 * @version 2.5.8
		 * @since   2.5.8
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_customer_shipping_country( $atts ) {
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$meta         = get_user_meta( $current_user->ID, 'shipping_country', true );
				if ( '' !== ( $meta ) ) {
					return ( 'yes' === $atts['full_country_name'] ) ? wcj_get_country_name_by_code( $meta ) : $meta;
				}
			}
			return '';
		}

		/**
		 * Wcj_customer_meta.
		 *
		 * @version 2.5.8
		 * @since   2.5.8
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_customer_meta( $atts ) {
			if ( '' !== $atts['key'] && is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$meta         = get_user_meta( $current_user->ID, $atts['key'], true );
				if ( '' !== ( $meta ) ) {
					return $meta;
				}
			}
			return '';
		}

		/**
		 * Get_shortcode_currencies.
		 *
		 * @version 2.4.5
		 * @since   2.4.5
		 * @param array $atts The user defined shortcode attributes.
		 */
		private function get_shortcode_currencies( $atts ) {
			// Shortcode currencies.
			$shortcode_currencies = $atts['currencies'];
			if ( '' === $shortcode_currencies ) {
				$shortcode_currencies = array();
			} else {
				$shortcode_currencies = str_replace( ' ', '', $shortcode_currencies );
				$shortcode_currencies = trim( $shortcode_currencies, ',' );
				$shortcode_currencies = explode( ',', $shortcode_currencies );
			}
			if ( empty( $shortcode_currencies ) ) {
				$total_number = apply_filters( 'booster_option', 2, wcj_get_option( 'wcj_multicurrency_total_number', 2 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					$shortcode_currencies[] = wcj_get_option( 'wcj_multicurrency_currency_' . $i );
				}
			}
			return $shortcode_currencies;
		}

		/**
		 * Wcj_wholesale_price_table (global only).
		 *
		 * @version 3.1.0
		 * @since   2.4.8
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_wholesale_price_table( $atts ) {

			if ( ! wcj_is_module_enabled( 'wholesale_price' ) ) {
				return '';
			}

			// Check for user role options.
			$role_option_name_addon = '';
			$user_roles             = wcj_get_option( 'wcj_wholesale_price_by_user_role_roles', '' );
			if ( ! empty( $user_roles ) ) {
				$current_user_role = wcj_get_current_user_first_role();
				foreach ( $user_roles as $user_role_key ) {
					if ( $current_user_role === $user_role_key ) {
						$role_option_name_addon = '_' . $user_role_key;
						break;
					}
				}
			}

			$wholesale_price_levels            = array();
			$wcj_wholesale_price_levels_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_wholesale_price_levels_number' . $role_option_name_addon, 1 ) );
			for ( $i = 1; $i <= $wcj_wholesale_price_levels_number; $i++ ) {
				$level_qty                = wcj_get_option( 'wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, PHP_INT_MAX );
				$discount                 = wcj_get_option( 'wcj_wholesale_price_level_discount_percent' . $role_option_name_addon . '_' . $i, 0 );
				$wholesale_price_levels[] = array(
					'quantity' => $level_qty,
					'discount' => $discount,
				);
			}

			$data_qty       = array();
			$data_discount  = array();
			$columns_styles = array();
			$i              = -1;
			foreach ( $wholesale_price_levels as $wholesale_price_level ) {
				$i++;
				if ( 0 === $wholesale_price_level['quantity'] && 'yes' === $atts['hide_if_zero_quantity'] ) {
					continue;
				}
				$level_max_qty    = ( isset( $wholesale_price_levels[ $i + 1 ]['quantity'] ) ) ? $atts['before_level_max_qty'] . ( $wholesale_price_levels[ $i + 1 ]['quantity'] - 1 ) : $atts['last_level_max_qty'];
				$data_qty[]       = str_replace(
					array( '%level_qty%', '%level_min_qty%', '%level_max_qty%' ), // %level_qty% is deprecated
					array( $wholesale_price_level['quantity'], $wholesale_price_level['quantity'], $level_max_qty ),
					$atts['heading_format']
				);
				$data_discount[]  = ( 'fixed' === wcj_get_option( 'wcj_wholesale_price_discount_type', 'percent' ) )
					? '-' . wc_price( $wholesale_price_level['discount'] ) : '-' . $wholesale_price_level['discount'] . '%';
				$columns_styles[] = $atts['columns_style'];
			}

			$table_rows = array( $data_qty, $data_discount );

			if ( 'vertical' === $atts['table_format'] ) {
				$table_rows_modified = array();
				foreach ( $table_rows as $row_number => $table_row ) {
					foreach ( $table_row as $column_number => $cell ) {
						$table_rows_modified[ $column_number ][ $row_number ] = $cell;
					}
				}
				$table_rows = $table_rows_modified;
			}

			return wcj_get_table_html(
				$table_rows,
				array(
					'table_class'        => 'wcj_wholesale_price_table',
					'columns_styles'     => $columns_styles,
					'table_heading_type' => $atts['table_format'],
				)
			);
		}

		/**
		 * Wcj_currency_select_link_list.
		 *
		 * @version 5.6.8
		 * @since   2.4.5
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_currency_select_link_list( $atts, $content ) {
			$html                 = '';
			$shortcode_currencies = $this->get_shortcode_currencies( $atts );
			// Options.
			$currencies        = get_woocommerce_currencies();
			$selected_currency = '';
			$session_value     = wcj_session_get( 'wcj-currency' );
			if ( null !== ( $session_value ) ) {
				$selected_currency = $session_value;
			} else {
				$module_roles = wcj_get_option( 'wcj_multicurrency_role_defaults_roles', '' );
				if ( ! empty( $module_roles ) ) {
					$current_user_role = wcj_get_current_user_first_role();
					if ( in_array( $current_user_role, $module_roles, true ) ) {
						$selected_currency = wcj_get_option( 'wcj_multicurrency_role_defaults_' . $current_user_role, '' );
					}
				}
			}
			if ( '' === $selected_currency && '' !== $atts['default'] ) {
				$selected_currency = $atts['default'];
			}
			$links             = array();
			$first_link        = '';
			$switcher_template = wcj_get_option( 'wcj_multicurrency_switcher_template', '%currency_name% (%currency_symbol%)' );
			foreach ( $shortcode_currencies as $currency_code ) {
				if ( isset( $currencies[ $currency_code ] ) ) {
					$template_replaced_values = array(
						'%currency_name%'   => $currencies[ $currency_code ],
						'%currency_code%'   => $currency_code,
						'%currency_symbol%' => get_woocommerce_currency_symbol( $currency_code ),
					);
					$currency_switcher_output = str_replace( array_keys( $template_replaced_values ), array_values( $template_replaced_values ), $switcher_template );
					$the_link                 = '<a href="' . esc_url( wp_nonce_url( add_query_arg( 'wcj-currency', $currency_code ), 'wcj-currency', 'wcj-currency-nonce' ) ) . '">' . $currency_switcher_output . '</a>';
					if ( $currency_code !== $selected_currency ) {
						$links[] = $the_link;
					} else {
						$first_link = $the_link;
					}
				}
			}
			if ( '' !== $first_link ) {
				$links = array_merge( array( $first_link ), $links );
			}
			$html .= implode( '<br>', $links );
			return $html;
		}

		/**
		 * Wcj_get_left_to_free_shipping.
		 *
		 * @version 2.5.8
		 * @since   2.4.4
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_get_left_to_free_shipping( $atts, $content ) {
			return wcj_get_left_to_free_shipping( $atts['content'], $atts['multiply_by'] );
		}

		/**
		 * Wcj_tcpdf_pagebreak.
		 *
		 * @version 2.3.7
		 * @since   2.3.7
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_tcpdf_pagebreak( $atts, $content ) {
			return '<tcpdf method="AddPage" />';
		}

		/**
		 * Get_currency_selector.
		 *
		 * @version 5.6.8
		 * @since   2.4.5
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 * @param string         $type The user defined shortcode type.
		 */
		private function get_currency_selector( $atts, $content, $type = 'select' ) {
			// Start.
			$form_method = $atts['form_method'];
			$class       = $atts['class'];
			$style       = $atts['style'];
			$html        = '';
			$html       .= '<form action="" method="' . $form_method . '">';
			if ( 'select' === $type ) {
				$html .= '<select name="wcj-currency" id="wcj-currency-select" style="' . $style . '" class="' . $class . '" onchange="this.form.submit()">';
			}
			$shortcode_currencies = $this->get_shortcode_currencies( $atts );
			// Options.
			$currencies        = get_woocommerce_currencies();
			$selected_currency = '';
			$session_value     = wcj_session_get( 'wcj-currency' );
			if ( null !== ( $session_value ) ) {
				$selected_currency = $session_value;
			} else {
				$module_roles = wcj_get_option( 'wcj_multicurrency_role_defaults_roles', '' );
				if ( ! empty( $module_roles ) ) {
					$current_user_role = wcj_get_current_user_first_role();
					if ( in_array( $current_user_role, $module_roles, true ) ) {
						$selected_currency = wcj_get_option( 'wcj_multicurrency_role_defaults_' . $current_user_role, '' );
					}
				}
			}
			if ( '' === $selected_currency && '' !== $atts['default'] ) {
				wcj_session_set( 'wcj-currency', $atts['default'] );
				$selected_currency = $atts['default'];
			}
			$switcher_template = wcj_get_option( 'wcj_multicurrency_switcher_template', '%currency_name% (%currency_symbol%)' );
			foreach ( $shortcode_currencies as $currency_code ) {
				if ( isset( $currencies[ $currency_code ] ) ) {
					$template_replaced_values = array(
						'%currency_name%'   => $currencies[ $currency_code ],
						'%currency_code%'   => $currency_code,
						'%currency_symbol%' => get_woocommerce_currency_symbol( $currency_code ),
					);
					$currency_switcher_output = str_replace( array_keys( $template_replaced_values ), array_values( $template_replaced_values ), $switcher_template );
					if ( '' === $selected_currency ) {
						$selected_currency = $currency_code;
					}
					if ( 'select' === $type ) {
						$html .= '<option value="' . $currency_code . '" ' . selected( $currency_code, $selected_currency, false ) . '>' . $currency_switcher_output . '</option>';
					} elseif ( 'radio' === $type ) {
						$html .= '<input type="radio" name="wcj-currency" id="wcj-currency-radio" value="' . $currency_code . '" ' . checked( $currency_code, $selected_currency, false ) . ' onclick="this.form.submit()"> ' . $currency_switcher_output . '<br>';
					}
				}
			}
			// End.
			if ( 'select' === $type ) {
				$html .= '</select>';
			}
			$html .= wp_nonce_field( 'wcj-currency', 'wcj-currency-nonce' );
			$html .= '</form>';
			return $html;
		}

		/**
		 * Wcj_currency_select_radio_list.
		 *
		 * @version 2.4.5
		 * @since   2.4.5
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_currency_select_radio_list( $atts, $content ) {
			return $this->get_currency_selector( $atts, $content, 'radio' );
		}

		/**
		 * Wcj_currency_select_drop_down_list.
		 *
		 * @version 2.4.5
		 * @since   2.4.3
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_currency_select_drop_down_list( $atts, $content ) {
			return $this->get_currency_selector( $atts, $content, 'select' );
		}

		/**
		 * Wcj_country_select_drop_down_list.
		 *
		 * @version 5.6.8
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_country_select_drop_down_list( $atts, $content ) {
			$form_method  = $atts['form_method'];
			$select_class = $atts['class'];
			$select_style = $atts['style'];
			if ( ! isset( $atts['force_display'] ) || ! filter_var( $atts['force_display'], FILTER_VALIDATE_BOOLEAN ) ) {
				if ( ! wcj_is_module_enabled( 'price_by_country' ) ) {
					return;
				}
			}

			$countries           = wcj_get_countries();
			$shortcode_countries = ( empty( $atts['countries'] ) ? array() : array_map( 'trim', explode( ',', $atts['countries'] ) ) );
			$session_value       = wcj_session_get( 'wcj-country' );
			$selected_country    = ( null !== ( $session_value ) ? $session_value : '' );
			if ( 'yes' === $atts['replace_with_currency'] ) {
				$currencies_names_and_symbols = wcj_get_woocommerce_currencies_and_symbols();
			}
			$html  = '';
			$html .= '<form action="" method="' . $form_method . '">';
			$html .= '<select name="wcj-country" id="wcj-country" style="' . $select_style . '" class="' . $select_class . '" onchange="this.form.submit()">';
			if ( empty( $shortcode_countries ) ) {
				foreach ( $countries as $country_code => $country_name ) {
					$data_icon    = ( 'yes' === wcj_get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ? ' data-icon="' . wcj_plugin_url() . '/assets/images/flag-icons/' . strtolower( $country_code ) . '.png"' : '' );
					$option_label = ( 'yes' === $atts['replace_with_currency'] ) ? $currencies_names_and_symbols[ wcj_get_currency_by_country( $country_code ) ] : $country_name;
					$html        .= '<option' . $data_icon . ' value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $option_label . '</option>';
				}
			} else {
				foreach ( $shortcode_countries as $country_code ) {
					if ( isset( $countries[ $country_code ] ) ) {
						$data_icon    = ( 'yes' === wcj_get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ? ' data-icon="' . wcj_plugin_url() . '/assets/images/flag-icons/' . strtolower( $country_code ) . '.png"' : '' );
						$option_label = ( 'yes' === $atts['replace_with_currency'] ) ? $currencies_names_and_symbols[ wcj_get_currency_by_country( $country_code ) ] : $countries[ $country_code ];
						$html        .= '<option' . $data_icon . ' value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $option_label . '</option>';
					}
				}
			}
			$html .= wp_nonce_field( 'wcj-country', 'wcj-country-nonce' );
			$html .= '</select>';
			$html .= '</form>';
			return $html;
		}

		/**
		 * Wcj_text.
		 *
		 * @since 2.2.9
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_text( $atts, $content ) {

			return $content;
		}

		/**
		 * Wcj_wpml.
		 *
		 * @version 2.2.9
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_wpml( $atts, $content ) {
			return do_shortcode( $content );
		}

		/**
		 * Wcj_wpml_translate.
		 *
		 * @param array          $atts The user defined shortcode attributes.
		 * @param array | string $content The user defined shortcode content.
		 */
		public function wcj_wpml_translate( $atts, $content ) {
			return $this->wcj_wpml( $atts, $content );
		}

		/**
		 * Wcj_current_date.
		 *
		 * @version 5.6.8
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_current_date( $atts ) {
			return date_i18n( $atts['date_format'], wcj_get_timestamp_date_from_gmt() );
		}

		/**
		 * Wcj_image.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_image( $atts ) {
			return '<img' .
			' src="' . ( ! empty( $atts['src'] ) ? $atts['src'] : '' ) . '"' .
			' class="' . ( ! empty( $atts['class'] ) ? $atts['class'] : '' ) . '"' .
			' style="' . ( ! empty( $atts['style'] ) ? $atts['style'] : '' ) . '"' .
			' width="' . ( ! empty( $atts['width'] ) ? $atts['width'] : '' ) . '"' .
			' height="' . ( ! empty( $atts['height'] ) ? $atts['height'] : '' ) . '"' .
			'>';
		}
	}

endif;

return new WCJ_General_Shortcodes();

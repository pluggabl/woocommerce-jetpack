<?php
/**
 * WooCommerce Jetpack General Shortcodes
 *
 * The WooCommerce Jetpack General Shortcodes class.
 *
 * @version 2.5.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_General_Shortcodes' ) ) :

class WCJ_General_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 2.5.4
	 */
	public function __construct() {

		$this->the_shortcodes = array(
			'wcj_current_date',
//			'wcj_image',
			'wcj_cart_items_total_weight',
			'wcj_wpml',
			'wcj_wpml_translate',
			'wcj_country_select_drop_down_list',
			'wcj_currency_select_drop_down_list',
			'wcj_currency_select_radio_list',
			'wcj_currency_select_link_list',
			'wcj_text',
			'wcj_tcpdf_pagebreak',
			'wcj_get_left_to_free_shipping',
			'wcj_wholesale_price_table',
		);

		$this->the_atts = array(
			'date_format'           => get_option( 'date_format' ),
			'lang'                  => '',
			'form_method'           => 'post',//'get',
			'class'                 => '',
			'style'                 => '',
			'countries'             => '',
			'currencies'            => '',
			'content'               => '',
			'heading_format'        => 'from %level_qty% pcs.',
			'replace_with_currency' => 'no',
		);

		parent::__construct();

	}

	/**
	 * get_shortcode_currencies.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
	 */
	private function get_shortcode_currencies( $atts ) {
		// Shortcode currencies
		$shortcode_currencies = $atts['currencies'];
		if ( '' == $shortcode_currencies ) {
			$shortcode_currencies = array();
		} else {
			$shortcode_currencies = str_replace( ' ', '', $shortcode_currencies );
			$shortcode_currencies = trim( $shortcode_currencies, ',' );
			$shortcode_currencies = explode( ',', $shortcode_currencies );
		}
		if ( empty( $shortcode_currencies ) ) {
			$total_number = apply_filters( 'wcj_get_option_filter', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				$shortcode_currencies[] = get_option( 'wcj_multicurrency_currency_' . $i );
			}
		}
		return $shortcode_currencies;
	}

	/**
	 * wcj_wholesale_price_table (global only).
	 *
	 * @version 2.5.2
	 * @since   2.4.8
	 */
	function wcj_wholesale_price_table( $atts ) {

		if ( ! wcj_is_module_enabled( 'wholesale_price' ) ) {
			return '';
		}

		$wholesale_price_levels = array();
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_wholesale_price_levels_number', 1 ) ); $i++ ) {
			$level_qty                = get_option( 'wcj_wholesale_price_level_min_qty_' . $i, PHP_INT_MAX );
			$discount                 = get_option( 'wcj_wholesale_price_level_discount_percent_' . $i, 0 );
			$wholesale_price_levels[] = array( 'quantity' => $level_qty, 'discount' => $discount, );
		}

		$data_qty              = array();
		$data_discount         = array();
		$columns_styles        = array();
		foreach ( $wholesale_price_levels as $wholesale_price_level ) {
			$data_qty[]              = str_replace( '%level_qty%', $wholesale_price_level['quantity'], $atts['heading_format'] ) ;
			$data_discount[]         = ( 'fixed' === get_option( 'wcj_wholesale_price_discount_type', 'percent' ) )
				? '-' . wc_price( $wholesale_price_level['discount'] ) : '-' . $wholesale_price_level['discount'] . '%';
			$columns_styles[]        = 'text-align: center;';
		}

		$table_rows = array( $data_qty, $data_discount, );
		return wcj_get_table_html( $table_rows, array( 'table_class' => 'wcj_wholesale_price_table', 'columns_styles' => $columns_styles ) );
	}

	/**
	 * wcj_currency_select_link_list.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
	 */
	function wcj_currency_select_link_list( $atts, $content ) {
		$html = '';
		$shortcode_currencies = $this->get_shortcode_currencies( $atts );
		// Options
		$currencies = wcj_get_currencies_names_and_symbols();
		$selected_currency = ( isset( $_SESSION['wcj-currency'] ) ) ? $_SESSION['wcj-currency'] : '';
		$links = array();
		$first_link = '';
		foreach ( $shortcode_currencies as $currency_code ) {
			if ( isset( $currencies[ $currency_code ] ) ) {
				$the_link = '<a href="' . add_query_arg( 'wcj-currency', $currency_code ) . '">' . $currencies[ $currency_code ] . '</a>';
				if ( $currency_code != $selected_currency ) {
					$links[] = $the_link;
				} else {
					$first_link = $the_link;
				}
			}
		}
		if ( '' != $first_link ) {
			$links = array_merge( array( $first_link ), $links );
		}
		$html .= implode( '<br>', $links );
		return $html;
	}

	/**
	 * wcj_get_left_to_free_shipping.
	 *
	 * @version 2.4.5
	 * @since   2.4.4
	 */
	function wcj_get_left_to_free_shipping( $atts, $content ) {
		return wcj_get_left_to_free_shipping( $atts['content'] );
	}

	/**
	 * wcj_tcpdf_pagebreak.
	 *
	 * @version 2.3.7
	 * @since   2.3.7
	 */
	function wcj_tcpdf_pagebreak( $atts, $content ) {
		return '<tcpdf method="AddPage" />';
	}

	/**
	 * get_currency_selector.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
	 */
	private function get_currency_selector( $atts, $content, $type = 'select' ) {
		// Start
		$html = '';
		$form_method  = $atts['form_method'];
		$class = $atts['class'];
		$style = $atts['style'];
		$html .= '<form action="" method="' . $form_method . '">';
		if ( 'select' === $type ) {
			$html .= '<select name="wcj-currency" id="wcj-currency-select" style="' . $style . '" class="' . $class . '" onchange="this.form.submit()">';
		}
		$shortcode_currencies = $this->get_shortcode_currencies( $atts );
		// Options
		$currencies = wcj_get_currencies_names_and_symbols();
		$selected_currency = ( isset( $_SESSION['wcj-currency'] ) ) ? $_SESSION['wcj-currency'] : '';
		foreach ( $shortcode_currencies as $currency_code ) {
			if ( isset( $currencies[ $currency_code ] ) ) {
				if ( '' == $selected_currency ) {
					$selected_currency = $currency_code;
				}
				if ( 'select' === $type ) {
					$html .= '<option value="' . $currency_code . '" ' . selected( $currency_code, $selected_currency, false ) . '>' . $currencies[ $currency_code ] . '</option>';
				} elseif ( 'radio' === $type ) {
					$html .= '<input type="radio" name="wcj-currency" id="wcj-currency-radio" value="' . $currency_code . '" ' . checked( $currency_code, $selected_currency, false ) . ' onclick="this.form.submit()"> ' . $currencies[ $currency_code ] . '<br>';
				}
			}
		}
		// End
		if ( 'select' === $type ) {
			$html .= '</select>';
		}
		$html .= '</form>';
		return $html;
	}

	/**
	 * wcj_currency_select_radio_list.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
	 */
	function wcj_currency_select_radio_list( $atts, $content ) {
		return $this->get_currency_selector( $atts, $content, 'radio' );
	}

	/**
	 * wcj_currency_select_drop_down_list.
	 *
	 * @version 2.4.5
	 * @since   2.4.3
	 */
	function wcj_currency_select_drop_down_list( $atts, $content ) {
		return $this->get_currency_selector( $atts, $content, 'select' );
	}

	/**
	 * wcj_country_select_drop_down_list.
	 *
	 * @version 2.5.4
	 */
	function wcj_country_select_drop_down_list( $atts, $content ) {

		$html = '';

		$form_method  = $atts['form_method']; // get_option( 'wcj_price_by_country_country_selection_box_method', 'get' );
		$select_class = $atts['class'];       // get_option( 'wcj_price_by_country_country_selection_box_class', '' );
		$select_style = $atts['style'];       // get_option( 'wcj_price_by_country_country_selection_box_style', '' );

		$html .= '<form action="" method="' . $form_method . '">';

		$html .= '<select name="wcj-country" id="wcj-country" style="' . $select_style . '" class="' . $select_class . '" onchange="this.form.submit()">';
		$countries = wcj_get_countries();

		/* $shortcode_countries = get_option( 'wcj_price_by_country_shortcode_countries', array() );
		if ( '' == $shortcode_countries ) $shortcode_countries = array(); */
		$shortcode_countries = $atts['countries'];
		if ( '' == $shortcode_countries ) {
			$shortcode_countries = array();
		} else {
			$shortcode_countries = str_replace( ' ', '', $shortcode_countries );
			$shortcode_countries = trim( $shortcode_countries, ',' );
			$shortcode_countries = explode( ',', $shortcode_countries );
		}

		/* if ( 'get' == $form_method ) {
			$selected_country = ( isset( $_GET[ 'wcj-country' ] ) ) ? $_GET[ 'wcj-country' ] : '';
		} else {
			$selected_country = ( isset( $_POST[ 'wcj-country' ] ) ) ? $_POST[ 'wcj-country' ] : '';
		} */
		$selected_country = ( isset( $_SESSION[ 'wcj-country' ] ) ) ? $_SESSION[ 'wcj-country' ] : '';

		if ( 'yes' === $atts['replace_with_currency'] ) {
			$currencies_names_and_symbols = wcj_get_currencies_names_and_symbols();
		}

		if ( empty( $shortcode_countries ) ) {
			foreach ( $countries as $country_code => $country_name ) {

				$data_icon = '';
				if ( 'yes' === get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ) {
					$data_icon = ' data-icon="' . wcj_plugin_url() . '/assets/images/flag-icons/' . $country_code . '.png"';
				}

				$option_label = ( 'yes' === $atts['replace_with_currency'] ) ? $currencies_names_and_symbols[ wcj_get_currency_by_country( $country_code ) ] : $country_name;

				$html .= '<option' . $data_icon . ' value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $option_label . '</option>';
			}
		} else {
			foreach ( $shortcode_countries as $country_code ) {
				if ( isset( $countries[ $country_code ] ) ) {

					$data_icon = '';
					if ( 'yes' === get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ) {
						$data_icon = ' data-icon="' . wcj_plugin_url() . '/assets/images/flag-icons/' . $country_code . '.png"';
					}

					$option_label = ( 'yes' === $atts['replace_with_currency'] ) ? $currencies_names_and_symbols[ wcj_get_currency_by_country( $country_code ) ] : $countries[ $country_code ];

					$html .= '<option' . $data_icon . ' value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $option_label . '</option>';
				}
			}
		}

		$html .= '</select>';

		$html .= '</form>';

		return $html;
	}

	/**
	 * wcj_text.
	 *
	 * @since 2.2.9
	 */
	function wcj_text( $atts, $content ) {
		return /* do_shortcode(  */ $content /* ) */;
	}

	/**
	 * wcj_wpml.
	 *
	 * @version 2.2.9
	 */
	function wcj_wpml( $atts, $content ) {
		/* if ( '' == $atts['lang'] || ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE === $atts['lang'] ) ) return do_shortcode( $content );
		else return ''; */
		return do_shortcode( $content );
	}

	/**
	 * wcj_wpml_translate.
	 */
	function wcj_wpml_translate( $atts, $content ) {
		return $this->wcj_wpml( $atts, $content );
	}

	/**
	 * wcj_cart_items_total_weight.
	 */
	function wcj_cart_items_total_weight( $atts ) {
		$the_cart = WC()->cart;
		return $the_cart->cart_contents_weight;
	}

	/**
	 * wcj_current_date.
	 */
	function wcj_current_date( $atts ) {
		return date_i18n( $atts['date_format'] );
	}

	/**
	 * wcj_image.
	 */
	/*function wcj_image( $atts ) {
		return '<img src="' . $atts['url'] . '" class="' . $atts['class'] . '" width="' . $atts['width'] . '" height="' . $atts['height'] . '">';
	}*/
}

endif;

return new WCJ_General_Shortcodes();

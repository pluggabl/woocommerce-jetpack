<?php
/**
 * WooCommerce Jetpack General Shortcodes
 *
 * The WooCommerce Jetpack General Shortcodes class.
 *
 * @version 2.4.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_General_Shortcodes' ) ) :

class WCJ_General_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 2.4.3
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
			'wcj_text',
			'wcj_tcpdf_pagebreak',
		);

		$this->the_atts = array(
			'date_format' => get_option( 'date_format' ),
			/*'url'         => '',
			'class'       => '',
			'width'       => '',
			'height'      => '',*/
			'lang'        => '',
			'form_method' => 'post',//'get',
			'class'       => '',
			'style'       => '',
			'countries'   => '',
			'currencies'  => '',
		);

		parent::__construct();

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
	 * wcj_currency_select_drop_down_list.
	 *
	 * @version 2.4.3
	 * @since   2.4.3
	 */
	function wcj_currency_select_drop_down_list( $atts, $content ) {
		// Start
		$html = '';
		$form_method  = $atts['form_method'];
		$select_class = $atts['class'];
		$select_style = $atts['style'];
		$html .= '<form action="" method="' . $form_method . '">';
		$html .= '<select name="wcj-currency" id="wcj-currency" style="' . $select_style . '" class="' . $select_class . '" onchange="this.form.submit()">';
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
		// Options
		$currencies = wcj_get_currencies_names_and_symbols();
		$selected_currency = ( isset( $_SESSION['wcj-currency'] ) ) ? $_SESSION['wcj-currency'] : '';
		foreach ( $shortcode_currencies as $currency_code ) {
			if ( isset( $currencies[ $currency_code ] ) ) {
				$html .= '<option value="' . $currency_code . '" ' . selected( $currency_code, $selected_currency, false ) . '>' . $currencies[ $currency_code ] . '</option>';
			}
		}
		// End
		$html .= '</select>';
		$html .= '</form>';
		return $html;
	}

	/**
	 * wcj_country_select_drop_down_list.
	 *
	 * @version 2.4.0
	 */
	function wcj_country_select_drop_down_list( $atts, $content ) {

		$html = '';

		$form_method  = $atts['form_method'];//get_option( 'wcj_price_by_country_country_selection_box_method', 'get' );
		$select_class = $atts['class'];//get_option( 'wcj_price_by_country_country_selection_box_class', '' );
		$select_style = $atts['style'];//get_option( 'wcj_price_by_country_country_selection_box_style', '' );

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

		if ( empty( $shortcode_countries ) ) {
			foreach ( $countries as $country_code => $country_name ) {
				$html .= '<option value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $country_name . '</option>';
			}
		} else {
			foreach ( $shortcode_countries as $country_code ) {
				if ( isset( $countries[ $country_code ] ) ) {
					$html .= '<option value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $countries[ $country_code ] . '</option>';
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

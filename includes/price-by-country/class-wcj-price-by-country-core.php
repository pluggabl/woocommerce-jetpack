<?php
/**
 * WooCommerce Jetpack Price by Country Core
 *
 * The WooCommerce Jetpack Price by Country Core class.
 *
 * @version 2.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_by_Country_Core' ) ) :

class WCJ_Price_by_Country_Core {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->customer_country_group_id = null;
		add_action( 'init', array( $this, 'add_hooks' ) );
	}

	/**
	 * add_hooks.
	 *
	 * @version 2.3.0
	 */
	function add_hooks() {

		if ( 'by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {

			if ( ! session_id() ) {
				session_start();
			}

			if ( isset( $_REQUEST[ 'wcj-country' ] ) ) {
				$_SESSION[ 'wcj-country' ] = $_REQUEST[ 'wcj-country' ];
			}
		}

		//if (  ) { //todo

			// Price hooks
			add_filter( 'woocommerce_variation_prices',            array( $this, 'change_price_by_country_variations' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_price',                   array( $this, 'change_price_by_country' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_sale_price',              array( $this, 'change_price_by_country' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_regular_price',           array( $this, 'change_price_by_country' ), PHP_INT_MAX, 2 );
//			add_filter( 'woocommerce_get_variation_price',         array( $this, 'change_price_by_country' ), PHP_INT_MAX, 2 );
//			add_filter( 'woocommerce_get_variation_sale_price',    array( $this, 'change_price_by_country' ), PHP_INT_MAX, 2 );
//			add_filter( 'woocommerce_get_variation_regular_price', array( $this, 'change_price_by_country' ), PHP_INT_MAX, 2 );
//			add_filter( 'booking_form_calculated_booking_cost',    array( $this, 'change_price_by_country' ), PHP_INT_MAX );
//			add_filter( 'woocommerce_get_price_html',              array( $this, 'fix_variable_product_price_on_sale' ), 10 , 2 );

			// Currency hooks
			add_filter( 'woocommerce_currency_symbol',             array( $this, 'change_currency_symbol' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_currency',                    array( $this, 'change_currency_code' ), PHP_INT_MAX, 1 );

			// Shipping
			add_filter( 'woocommerce_package_rates',               array( $this, 'change_shipping_price_by_country' ), PHP_INT_MAX, 2 );
		//}

		// Country selection box
		/* if ( 'by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
			add_filter( 'woocommerce_get_price_html', array( $this, 'add_country_selection_box' ), PHP_INT_MAX, 2 );
		} */

		// Debug
//		add_shortcode( 'wcj_debug_price_by_country', 		array( $this, 'get_debug_info' ) );
	}

	/**
	 * add_country_selection_box.
	 *
	function add_country_selection_box( $price_html, $_product ) {
		$html = '';

		$form_method  = get_option( 'wcj_price_by_country_country_selection_box_method', 'get' );
		$select_class = get_option( 'wcj_price_by_country_country_selection_box_class', '' );
		$select_style = get_option( 'wcj_price_by_country_country_selection_box_style', '' );

		$html .= '<form action="" method="' . $form_method . '">';

		$html .= '<select name="wcj-country" id="wcj-country" style="' . $select_style . '" class="' . $select_class . '" onchange="this.form.submit()">';
		$countries = wcj_get_countries();

		/* if ( 'get' == $form_method ) {
			$selected_country = ( isset( $_GET[ 'wcj-country' ] ) ) ? $_GET[ 'wcj-country' ] : '';
		} else {
			$selected_country = ( isset( $_POST[ 'wcj-country' ] ) ) ? $_POST[ 'wcj-country' ] : '';
		} *//*
		$selected_country = ( isset( $_REQUEST[ 'wcj-country' ] ) ) ? $_REQUEST[ 'wcj-country' ] : '';

		foreach ( $countries as $country_code => $country_name ) {

			$html .= '<option value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $country_name . '</option>';
		}
		$html .= '</select>';

		$html .= '</form>';

		return $price_html . $html;
	}

	/**
	 * change_shipping_price_by_country.
	 */
	function change_shipping_price_by_country( $package_rates, $package ) {

		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {

			$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			$modified_package_rates = array();
			foreach ( $package_rates as $id => $package_rate ) {
				if ( 1 != $country_exchange_rate && isset( $package_rate->cost ) ) $package_rate->cost = $package_rate->cost * $country_exchange_rate;
				$modified_package_rates[ $id ] = $package_rate;
			}
			return $modified_package_rates;

		} else {
			return $package_rates;
		}
	}

	/**
	 * get_debug_info.
	 *
	function get_debug_info( $args ) {
		$html = '';
		if ( 'yes' === get_option( 'wcj_price_by_country_local_enabled' ) ) {
			$html .= '<p>';
			$html .= __( 'Price by Country on per Product Basis is enabled.', 'woocommerce-jetpack' );
			$html .= '</p>';
		}

		$data = array();
		$data[] = array( '#', __( 'Countries', 'woocommerce-jetpack' ), __( 'Focus Country', 'woocommerce-jetpack' ), __( 'Regular Price', 'woocommerce-jetpack' ), __( 'Sale Price', 'woocommerce-jetpack' ) );
		global $product;
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {

			$row = array();

			$row[] = $i;

			$country_exchange_rate_group = get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i );
			$country_exchange_rate_group = str_replace( ' ', '', $country_exchange_rate_group );
			$row[] = $country_exchange_rate_group;

			$country_exchange_rate_group = explode( ',', $country_exchange_rate_group );
			$_GET['country'] = $country_exchange_rate_group[0];
			$row[] = $country_exchange_rate_group[0];
			$currency_code = wcj_get_currency_symbol( get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i ) );
			$row[] = $product->get_regular_price() . ' ' . $currency_code;
			$row[] = $product->get_sale_price() . ' ' . $currency_code;

			$data[] = $row;
		}
		//$html .= wcj_get_table_html( $data, '', false );
		$html = wcj_get_table_html( $data, array( 'table_heading_type' => 'vertical', ) );
		return $html;
	}

	/**
	 * fix_variable_product_price_on_sale.
	 *
	public function fix_variable_product_price_on_sale( $price, $product ) {
		if ( $product->is_type( 'variable' ) ) {
			if ( ! $product->is_on_sale() ) {
				$start_position = strpos( $price, '<del>' );
				$length = strpos( $price, '</del>' ) - $start_position;
				// Fixing the price, i.e. removing the sale tags
				return substr_replace( $price, '', $start_position, $length );
			}
		}
		// No changes
		return $price;
	}

	/**
	 * get_customer_country_group_id.
	 *
	 * @version 2.2.6
	 */
	public function get_customer_country_group_id() {

		// We already know the group - nothing to calculate - return group
//		if ( null != $this->customer_country_group_id && $this->customer_country_group_id > 0 )
//			return $this->customer_country_group_id;

		// We've already tried - no country was detected, no need to try again
		/* if ( -1 === $this->customer_country_group_id )
			return null; */

		if ( isset( $_GET['country'] ) && '' != $_GET['country'] && is_super_admin() ) {

			$country = $_GET['country'];

		} elseif ( 'yes' === get_option( 'wcj_price_by_country_override_on_checkout_with_billing_country', 'no' )
			&& is_checkout()
			&& '' != WC()->customer->get_country()
		) {

			$country = WC()->customer->get_country();

		} else {

			if ( 'by_ip' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {

				// Get the country by IP
				$location = WC_Geolocation::geolocate_ip();
				// Base fallback
				if ( empty( $location['country'] ) ) {
					$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
				}
				$country = ( isset( $location['country'] ) ) ? $location['country'] : null;

			} elseif ( 'by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {

				/* $form_method  = get_option( 'wcj_price_by_country_country_selection_box_method', 'get' );
				if ( 'get' == $form_method ) {
					$country = ( isset( $_GET[ 'wcj-country' ] ) ) ? $_GET[ 'wcj-country' ] : null;
				} else {
					$country = ( isset( $_POST[ 'wcj-country' ] ) ) ? $_POST[ 'wcj-country' ] : null;
				} */
				$country = ( isset( $_SESSION[ 'wcj-country' ] ) ) ? $_SESSION[ 'wcj-country' ] : null;

			} elseif ( 'by_wpml' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {

				$country = ( defined( 'ICL_LANGUAGE_CODE' ) ) ? ICL_LANGUAGE_CODE : null;

			}
		}

		if ( null === $country ) {
			$this->customer_country_group_id = -1;
			return null;
		}

		// Get the country group id - go through all the groups, first found group is returned
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
			$country_exchange_rate_group = get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i );
			$country_exchange_rate_group = str_replace( ' ', '', $country_exchange_rate_group );
			$country_exchange_rate_group = explode( ',', $country_exchange_rate_group );
			if ( in_array( $country, $country_exchange_rate_group ) ) {
				$this->customer_country_group_id = $i;
				//wcj_log( 'customer_country_group_id=' . $this->customer_country_group_id );
				return $i;
			}
		}

		// No country group found
		$this->customer_country_group_id = -1;
		return null;
	}

	/**
	 * change_currency_symbol.
	 */
	public function change_currency_symbol( $currency_symbol, $currency ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			$country_currency_code = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $group_id );
			if ( '' != $country_currency_code )
				return wcj_get_currency_symbol( $country_currency_code );
		}
		return $currency_symbol;
	}

	/**
	 * change_currency_code.
	 */
	public function change_currency_code( $currency ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			$country_currency_code = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $group_id );
			if ( '' != $country_currency_code )
				return $country_currency_code;
		}
		return $currency;
	}

	/**
	 * change_price_by_country_variations.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	public function change_price_by_country_variations( $prices_array, $product ) {
		$modified_prices_array = $prices_array;
		foreach ( $prices_array as $price_type => $prices ) {
			foreach ( $prices as $variation_id => $price ) {
				$modified_prices_array[ $price_type ][ $variation_id ] = $this->change_price_by_country( $price, $variation_id );
			}
		}
		return $modified_prices_array;
	}

	/**
	 * change_price_by_country.
	 *
	 * @version 2.3.0
	 */
	public function change_price_by_country( $price, $product ) {

		if ( is_numeric( $product ) ) $the_product_id = $product;
		else $the_product_id = ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id;

		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {

			$is_price_modified = false;

			if ( 'yes' === get_option( 'wcj_price_by_country_local_enabled' ) ) {
				// Per product
				$meta_box_id = 'price_by_country';
				$scope = 'local';

				$meta_id = '_' . 'wcj_' . $meta_box_id . '_make_empty_price_' . $scope . '_' . $group_id;
				if ( 'on' === get_post_meta( $the_product_id, $meta_id, true ) ) {
					return '';
				}

				$price_by_country = '';
				if ( 'woocommerce_get_price' == current_filter() ) {

					$regular_or_sale = '_regular_price_';
					$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
					$regular_price = get_post_meta( $the_product_id, $meta_id, true );

					$regular_or_sale = '_sale_price_';
					$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
					$sale_price = get_post_meta( $the_product_id, $meta_id, true );

					if ( ! empty( $sale_price ) && $sale_price < $regular_price )
						$price_by_country = $sale_price;
					else
						$price_by_country = $regular_price;

				}
				elseif ( 'woocommerce_get_regular_price' == current_filter() || 'woocommerce_get_sale_price' == current_filter() ) {
					$regular_or_sale = ( 'woocommerce_get_regular_price' == current_filter() ) ? '_regular_price_' : '_sale_price_';
					$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
					$price_by_country = get_post_meta( $the_product_id, $meta_id, true );
				}

				if ( '' != $price_by_country ) {
					$modified_price = $price_by_country;
					$is_price_modified = true;
				}
			}

			if ( ! $is_price_modified ) {
				if ( 'yes' === get_option( 'wcj_price_by_country_make_empty_price_group_' . $group_id, 1 ) ) {
					return '';
				}
			}

			if ( ! $is_price_modified ) {
				// Globally
				$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
				if ( 1 != $country_exchange_rate ) {
					$modified_price = $price * $country_exchange_rate;
					$is_price_modified = true;
				}
			}

			if ( $is_price_modified ) {
				$rounding = get_option( 'wcj_price_by_country_rounding', 'none' );
				$precision = get_option( 'woocommerce_price_num_decimals', 2 );
				switch ( $rounding ) {
					case 'none':
						//return ( $modified_price );
						return round( $modified_price, $precision );
					case 'round':
						return round( $modified_price );
					case 'floor':
						return floor( $modified_price );
					case 'ceil':
						return ceil( $modified_price );
				}
			}
		}
		// No changes
		return $price;
	}
}

endif;

return new WCJ_Price_by_Country_Core();

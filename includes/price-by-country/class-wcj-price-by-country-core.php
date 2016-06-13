<?php
/**
 * WooCommerce Jetpack Price by Country Core
 *
 * The WooCommerce Jetpack Price by Country Core class.
 *
 * @version 2.5.2
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
	 * @version 2.5.0
	 */
	function add_hooks() {

		if (
			'by_user_selection'            === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ||
			'by_ip_then_by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' )
		) {
			if ( ! session_id() ) {
				session_start();
			}
			if ( isset( $_REQUEST[ 'wcj-country' ] ) ) {
				$_SESSION[ 'wcj-country' ] = $_REQUEST[ 'wcj-country' ];
			}
			if ( ! isset( $_SESSION[ 'wcj-country' ] ) && 'by_ip_then_by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
				if ( null != ( $country = $this->get_customer_country_by_ip() ) ) {
					$_SESSION[ 'wcj-country' ] = $country;
				}
			}
		}

		// Price hooks
		add_filter( 'woocommerce_get_price',         array( $this, 'change_price_by_country' ), PHP_INT_MAX - 1, 2 );
		add_filter( 'woocommerce_get_sale_price',    array( $this, 'change_price_by_country' ), PHP_INT_MAX - 1, 2 );
		add_filter( 'woocommerce_get_regular_price', array( $this, 'change_price_by_country' ), PHP_INT_MAX - 1, 2 );

		// Variable products
		add_filter( 'woocommerce_variation_prices_price',         array( $this, 'change_price_by_country' ), PHP_INT_MAX - 1, 2 );
		add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'change_price_by_country' ), PHP_INT_MAX - 1, 2 );
		add_filter( 'woocommerce_variation_prices_sale_price',    array( $this, 'change_price_by_country' ), PHP_INT_MAX - 1, 2 );
		add_filter( 'woocommerce_get_variation_prices_hash',      array( $this, 'get_variation_prices_hash' ), PHP_INT_MAX - 1, 3 );

		// Grouped products
		add_filter( 'woocommerce_get_price_including_tax', array( $this, 'change_price_by_country_grouped' ), PHP_INT_MAX - 1, 3 );
		add_filter( 'woocommerce_get_price_excluding_tax', array( $this, 'change_price_by_country_grouped' ), PHP_INT_MAX - 1, 3 );

		// Currency hooks
		add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), PHP_INT_MAX - 1, 2 );
		add_filter( 'woocommerce_currency',        array( $this, 'change_currency_code' ),   PHP_INT_MAX - 1, 1 );

		// Shipping
		add_filter( 'woocommerce_package_rates', array( $this, 'change_shipping_price_by_country' ), PHP_INT_MAX - 1, 2 );
	}

	/**
	 * change_price_by_country_grouped.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function change_price_by_country_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			if ( 'yes' === get_option( 'wcj_price_by_country_local_enabled' ) ) {
				$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
				foreach ( $_product->get_children() as $child_id ) {
					$the_price = get_post_meta( $child_id, '_price', true );
					$the_product = wc_get_product( $child_id );
					$the_price = $the_product->$get_price_method( 1, $the_price );
					if ( $the_price == $price ) {
						return $this->change_price_by_country( $price, $child_id );
					}
				}
			} else {
				return $this->change_price_by_country( $price, 0 );
			}
		}
		return $price;
	}

	/**
	 * get_customer_country_by_ip.
	 *
	 * @version 2.5.1
	 * @since   2.5.0
	 */
	function get_customer_country_by_ip() {
		if ( class_exists( 'WC_Geolocation' ) ) {
			// Get the country by IP
			$location = WC_Geolocation::geolocate_ip();
			// Base fallback
			if ( empty( $location['country'] ) ) {
				$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
			}
			return ( isset( $location['country'] ) ) ? $location['country'] : null;
		} else {
			return null;
		}
	}

	/**
	 * change_shipping_price_by_country.
	 *
	 * @version 2.4.4
	 */
	function change_shipping_price_by_country( $package_rates, $package ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			$modified_package_rates = array();
			foreach ( $package_rates as $id => $package_rate ) {
				if ( 1 != $country_exchange_rate && isset( $package_rate->cost ) ) {
					$package_rate->cost = $package_rate->cost * $country_exchange_rate;
					if ( isset( $package_rate->taxes ) && ! empty( $package_rate->taxes ) ) {
						foreach ( $package_rate->taxes as $tax_id => $tax ) {
							$package_rate->taxes[ $tax_id ] = $package_rate->taxes[ $tax_id ] * $country_exchange_rate;
						}
					}
				}
				$modified_package_rates[ $id ] = $package_rate;
			}
			return $modified_package_rates;
		} else {
			return $package_rates;
		}
	}

	/**
	 * get_customer_country_group_id.
	 *
	 * @version 2.5.2
	 */
	public function get_customer_country_group_id() {

		if ( 'yes' === get_option( 'wcj_price_by_country_revert', 'no' ) && is_checkout() ) {
			$this->customer_country_group_id = -1;
			return null;
		}

		// We already know the group - nothing to calculate - return group
		if ( null != $this->customer_country_group_id && $this->customer_country_group_id > 0 ) {
			return $this->customer_country_group_id;
		}

		// Get the country
		if ( isset( $_GET['country'] ) && '' != $_GET['country'] && wcj_is_user_role( 'administrator' ) ) {
			$country = $_GET['country'];
		} elseif ( 'yes' === get_option( 'wcj_price_by_country_override_on_checkout_with_billing_country', 'no' )
			/* && is_checkout() */
			&& '' != WC()->customer->get_country()
		) {
			$country = WC()->customer->get_country();
		} else {
			if ( 'by_ip' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
				$country = $this->get_customer_country_by_ip();
			} elseif ( 'by_ip_then_by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
				$country = ( isset( $_SESSION[ 'wcj-country' ] ) ) ? $_SESSION[ 'wcj-country' ] : $this->get_customer_country_by_ip();
			} elseif ( 'by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
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
			switch ( get_option( 'wcj_price_by_country_selection', 'comma_list' ) ) {
				case 'comma_list':
					$country_exchange_rate_group = get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i );
					$country_exchange_rate_group = str_replace( ' ', '', $country_exchange_rate_group );
					$country_exchange_rate_group = explode( ',', $country_exchange_rate_group );
					break;
				case 'multiselect':
					$country_exchange_rate_group = get_option( 'wcj_price_by_country_countries_group_' . $i );
					break;
				case 'chosen_select':
					$country_exchange_rate_group = get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i );
					break;
			}
			if ( is_array( $country_exchange_rate_group ) && in_array( $country, $country_exchange_rate_group ) ) {
				$this->customer_country_group_id = $i;
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
			if ( '' != $country_currency_code ) {
				return wcj_get_currency_symbol( $country_currency_code );
			}
		}
		return $currency_symbol;
	}

	/**
	 * change_currency_code.
	 */
	public function change_currency_code( $currency ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			$country_currency_code = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $group_id );
			if ( '' != $country_currency_code ) {
				return $country_currency_code;
			}
		}
		return $currency;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.5.0
	 * @since   2.4.3
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$group_id = $this->get_customer_country_group_id();
		$price_hash['wcj_price_by_country_group_id_data'] = array(
			$group_id,
			get_option( 'wcj_price_by_country_rounding' ),
			get_option( 'wcj_price_by_country_local_enabled' ),
//			get_option( 'wcj_price_by_country_selection' ),
//			get_option( 'wcj_price_by_country_total_groups_number' ),
//			get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $group_id ),
//			get_option( 'wcj_price_by_country_countries_group_' . $group_id ),
//			get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $group_id ),
			get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $group_id ),
			get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 ),
			get_option( 'wcj_price_by_country_make_empty_price_group_' . $group_id, 'no' ),
		);
		return $price_hash;
	}

	/**
	 * change_price_by_country.
	 *
	 * @version 2.5.0
	 */
	function change_price_by_country( $price, $product ) {

		if ( is_numeric( $product ) ) {
			$the_product_id = $product;
		} else {
			$the_product_id = ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id;
		}

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
				$the_current_filter = current_filter();
				if ( 'woocommerce_get_price_including_tax' == $the_current_filter || 'woocommerce_get_price_excluding_tax' == $the_current_filter ) {
					$_product = wc_get_product( $the_product_id );
					$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
					return $_product->$get_price_method();

				} elseif ( 'woocommerce_get_price' == $the_current_filter || 'woocommerce_variation_prices_price' == $the_current_filter ) {

					$regular_or_sale = '_regular_price_';
					$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
					$regular_price = get_post_meta( $the_product_id, $meta_id, true );

					$regular_or_sale = '_sale_price_';
					$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
					$sale_price = get_post_meta( $the_product_id, $meta_id, true );

					if ( ! empty( $sale_price ) && $sale_price < $regular_price ) {
						$price_by_country = $sale_price;
					} else {
						$price_by_country = $regular_price;
					}

				}
				elseif (
					'woocommerce_get_regular_price' == $the_current_filter ||
					'woocommerce_get_sale_price' == $the_current_filter ||
					'woocommerce_variation_prices_regular_price' == $the_current_filter ||
					'woocommerce_variation_prices_sale_price' == $the_current_filter
				) {
					$regular_or_sale = (
						'woocommerce_get_regular_price' == $the_current_filter || 'woocommerce_variation_prices_regular_price' == $the_current_filter
					) ? '_regular_price_' : '_sale_price_';
					$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
					$price_by_country = get_post_meta( $the_product_id, $meta_id, true );
				}

				if ( '' != $price_by_country ) {
					$modified_price = $price_by_country;
					$is_price_modified = true;
				}
			}

			if ( ! $is_price_modified ) {
				if ( 'yes' === get_option( 'wcj_price_by_country_make_empty_price_group_' . $group_id, 1 ) || '' === $price ) {
					return '';
				}
			}

			if ( ! $is_price_modified ) {
				// Globally
				$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
				if ( 1 != $country_exchange_rate ) {
					$modified_price = $price * $country_exchange_rate;
					$rounding = get_option( 'wcj_price_by_country_rounding', 'none' );
					$precision = get_option( 'woocommerce_price_num_decimals', 2 );
					switch ( $rounding ) {
						case 'round':
							$modified_price = round( $modified_price );
							break;
						case 'floor':
							$modified_price = floor( $modified_price );
							break;
						case 'ceil':
							$modified_price = ceil( $modified_price );
							break;
						default: // case 'none':
							$modified_price = round( $modified_price, $precision ); // $modified_price
							break;
					}
					$is_price_modified = true;
				}
			}

			if ( $is_price_modified ) {
				return $modified_price;
			}
		}
		// No changes
		return $price;
	}
}

endif;

return new WCJ_Price_by_Country_Core();

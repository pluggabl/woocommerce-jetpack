<?php
/**
 * WooCommerce Jetpack Price by Country Core
 *
 * The WooCommerce Jetpack Price by Country Core class.
 *
 * @version 2.5.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_by_Country_Core' ) ) :

class WCJ_Price_by_Country_Core {

	/**
	 * Constructor.
	 *
	 * @version 2.5.4
	 */
	public function __construct() {
		$this->customer_country_group_id = null;
		$this->add_hooks();
	}

	/**
	 * add_hooks.
	 *
	 * @version 2.5.4
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

		// Select with flags
		if ( 'yes' === get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_wselect_scripts' ) );
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

		// Price Filter Widget
		if ( 'yes' === get_option( 'wcj_price_by_country_price_filter_widget_support_enabled', 'no' ) ) {
			add_filter( 'woocommerce_price_filter_meta_keys',   array( $this, 'price_filter_meta_keys' ), PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_product_query_meta_query', array( $this, 'price_filter_meta_query' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * enqueue_wselect_scripts.
	 *
	 * @version 2.5.4
	 * @since   2.5.4
	 */
	function enqueue_wselect_scripts() {
		wp_enqueue_style(  'wcj-wSelect-style', wcj_plugin_url() . '/includes/lib/wSelect/wSelect.css' );
		wp_enqueue_script( 'wcj-wSelect',       wcj_plugin_url() . '/includes/lib/wSelect/wSelect.min.js', array(), false, true );
		wp_enqueue_script( 'wcj-wcj-wSelect',   wcj_plugin_url() . '/includes/js/wcj-wSelect.js', array(), false, true );
	}

	/**
	 * price_filter_meta_query.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function price_filter_meta_query( $meta_query, $_wc_query ) {
		foreach ( $meta_query as $_key => $_query ) {
			if ( isset( $_query['price_filter'] ) && true === $_query['price_filter'] && isset( $_query['key'] ) ) {
				if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
					$meta_query[ $_key ]['key'] = '_' . 'wcj_price_by_country_' . $group_id;
				}
			}
		}
		return $meta_query;
	}

	/**
	 * price_filter_meta_keys.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function price_filter_meta_keys( $keys ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			$keys = array( '_' . 'wcj_price_by_country_' . $group_id );
		}
		return $keys;
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
	 * @version 2.5.4
	 */
	public function get_customer_country_group_id() {

		if ( 'yes' === get_option( 'wcj_price_by_country_revert', 'no' ) && is_checkout() ) {
			$this->customer_country_group_id = -1;
			return null;
		}

		// We already know the group - nothing to calculate - return group
		/* if ( null != $this->customer_country_group_id && $this->customer_country_group_id > 0 ) {
			return $this->customer_country_group_id;
		} */

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
	 * @version 2.5.3
	 */
	function change_price_by_country( $price, $product ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			return wcj_price_by_country( $price, $product, $group_id );
		}
		// No changes
		return $price;
	}
}

endif;

return new WCJ_Price_by_Country_Core();

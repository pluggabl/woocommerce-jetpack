<?php
/**
 * Booster for WooCommerce - Price by Country - Core
 *
 * @version 3.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_by_Country_Core' ) ) :

class WCJ_Price_by_Country_Core {

	/**
	 * Constructor.
	 *
	 * @version 3.6.0
	 * @todo    check if we can just always execute `init()` on `init` hook
	 */
	function __construct() {
		$this->customer_country_group_id = null;
		if ( ( 'no' === get_option( 'wcj_price_by_country_for_bots_disabled', 'no' ) || ! wcj_is_bot() ) && ! wcj_is_admin_product_edit_page() ) {
			if ( in_array( get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ), array( 'by_user_selection', 'by_ip_then_by_user_selection' ) ) ) {
				if ( 'wc' === WCJ_SESSION_TYPE ) {
					// `init()` executed on `init` hook because we need to use `WC()->session`
					add_action( 'init', array( $this, 'init' ) );
				} else {
					$this->init();
				}
			}
			$this->add_hooks();
			// `maybe_init_customer_country_by_ip()` executed on `init` hook - in case we need to call `get_customer_country_by_ip()` `WC_Geolocation` class is ready
			add_action( 'init', array( $this, 'maybe_init_customer_country_by_ip' ) );
		}
	}

	/**
	 * init.
	 *
	 * @version 3.5.0
	 * @since   2.9.0
	 */
	function init() {
		wcj_session_maybe_start();
		if ( isset( $_REQUEST[ 'wcj-country' ] ) ) {
			wcj_session_set( 'wcj-country', $_REQUEST[ 'wcj-country' ] );
		}
		if ( isset( $_REQUEST[ 'wcj_country_selector' ] ) ) {
			wcj_session_set( 'wcj-country', $_REQUEST[ 'wcj_country_selector' ] );
		}
	}

	/**
	 * maybe_init_customer_country_by_ip.
	 *
	 * @version 3.4.0
	 * @since   2.9.0
	 */
	function maybe_init_customer_country_by_ip() {
		if ( 'by_ip_then_by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
			if ( null === wcj_session_get( 'wcj-country' ) ) {
				if ( null != ( $country = $this->get_customer_country_by_ip() ) ) {
					wcj_session_set( 'wcj-country', $country );
				}
			}
		}
	}

	/**
	 * add_hooks.
	 *
	 * @version 3.5.1
	 */
	function add_hooks() {

		// Select with flags
		if ( 'yes' === get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_wselect_scripts' ) );
		}

		$this->price_hooks_priority = wcj_get_module_price_hooks_priority( 'price_by_country' );

		// Price hooks
		wcj_add_change_price_hooks( $this, $this->price_hooks_priority );

		// Currency hooks
		add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), $this->price_hooks_priority, 2 );
		add_filter( 'woocommerce_currency',        array( $this, 'change_currency_code' ),   $this->price_hooks_priority, 1 );

		// Price Filter Widget
		if ( 'yes' === get_option( 'wcj_price_by_country_price_filter_widget_support_enabled', 'no' ) ) {
			add_filter( 'woocommerce_price_filter_meta_keys',    array( $this, 'price_filter_meta_keys' ), PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_product_query_meta_query',  array( $this, 'price_filter_meta_query' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_catalog_ordering_args', array( $this, 'sorting_by_price_fix' ), PHP_INT_MAX );
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

	/*
	 * sorting_by_price_fix.
	 *
	 * @version 2.7.0
	 * @since   2.5.6
	 */
	function sorting_by_price_fix( $args ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			// Get ordering from query string
			$orderby_value = ( WCJ_IS_WC_VERSION_BELOW_3 ?
				( isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) ) ) :
				( isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] )          : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) ) )
			);
			// Get orderby arg from string
			$orderby_value = explode( '-', $orderby_value );
			$orderby       = esc_attr( $orderby_value[0] );
			$orderby       = strtolower( $orderby );
			if ( 'price' == $orderby ) {
				$args['meta_key'] = '_' . 'wcj_price_by_country_' . $group_id;
			}
		}
		return $args;
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
	 * change_price_grouped.
	 *
	 * @version 2.7.0
	 * @since   2.5.0
	 */
	function change_price_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			if ( 'yes' === get_option( 'wcj_price_by_country_local_enabled', 'yes' ) ) {
				foreach ( $_product->get_children() as $child_id ) {
					$the_price = get_post_meta( $child_id, '_price', true );
					$the_product = wc_get_product( $child_id );
					$the_price = wcj_get_product_display_price( $the_product, $the_price, 1 );
					if ( $the_price == $price ) {
						return $this->change_price( $price, $child_id );
					}
				}
			} else {
				return $this->change_price( $price, 0 );
			}
		}
		return $price;
	}

	/**
	 * get_customer_country_by_ip.
	 *
	 * @version 3.8.0
	 * @since   2.5.0
	 */
	function get_customer_country_by_ip() {
		if ( isset( $this->customer_country_by_ip ) ) {
			return $this->customer_country_by_ip;
		}
		if ( class_exists( 'WC_Geolocation' ) ) {
			// Get the country by IP
			$location = WC_Geolocation::geolocate_ip( ( 'wc' === get_option( 'wcj_price_by_country_ip_detection_method', 'wc' ) ? '' : wcj_get_the_ip() ) );
			// Base fallback
			if ( empty( $location['country'] ) ) {
				$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
			}
			if ( ! empty( $location['country'] ) ) {
				$this->customer_country_by_ip = $location['country'];
			}
			return ( isset( $location['country'] ) ) ? $location['country'] : null;
		} else {
			return null;
		}
	}

	/**
	 * change_price_shipping.
	 *
	 * @version 3.2.0
	 */
	function change_price_shipping( $package_rates, $package ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			return wcj_change_price_shipping_package_rates( $package_rates, $country_exchange_rate );
		} else {
			return $package_rates;
		}
	}

	/**
	 * get_customer_country_group_id.
	 *
	 * @version 3.8.0
	 * @todo    (maybe) clean up - add `cart_and_checkout` override option
	 */
	function get_customer_country_group_id() {

		if ( 'yes' === get_option( 'wcj_price_by_country_revert', 'no' ) && is_checkout() ) {
			$this->customer_country_group_id = -1;
			return null;
		}

		// We already know the group - nothing to calculate - return group
		if ( isset( $this->customer_country_group_id ) && null != $this->customer_country_group_id && $this->customer_country_group_id > 0 ) {
			return $this->customer_country_group_id;
		}

		// Get the country
		if ( isset( $_GET['country'] ) && '' != $_GET['country'] && wcj_is_user_role( 'administrator' ) ) {
			$country = $_GET['country'];
		} elseif ( 'no' != ( $override_option = get_option( 'wcj_price_by_country_override_on_checkout_with_billing_country', 'no' ) )
			&& (
				( 'all'               === get_option( 'wcj_price_by_country_override_scope', 'all' ) ) ||
//				( 'cart_and_checkout' === get_option( 'wcj_price_by_country_override_scope', 'all' ) && ( is_cart() || is_checkout() ) ) ||
				( 'checkout'          === get_option( 'wcj_price_by_country_override_scope', 'all' ) && is_checkout() )
			)
			&& isset( WC()->customer )
			&& ( ( 'yes' === $override_option && '' != wcj_customer_get_country() ) || ( 'shipping_country' === $override_option && '' != WC()->customer->get_shipping_country() ) )
		) {
			$country = ( 'yes' === $override_option ) ? wcj_customer_get_country() : WC()->customer->get_shipping_country();
		} else {
			if ( 'by_ip' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
				$country = $this->get_customer_country_by_ip();
			} elseif ( 'by_ip_then_by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
				$country = ( null !== ( $session_value = wcj_session_get( 'wcj-country' ) ) ? $session_value : $this->get_customer_country_by_ip() );
			} elseif ( 'by_user_selection' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
				$country = wcj_session_get( 'wcj-country' );
			} elseif ( 'by_wpml' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
				$country = ( defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : null );
			}
		}

		if ( null === $country ) {
			$this->customer_country_group_id = -1;
			return null;
		}

		// Get the country group id - go through all the groups, first found group is returned
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
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
	function change_currency_symbol( $currency_symbol, $currency ) {
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
	function change_currency_code( $currency ) {
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
	 * @version 2.6.0
	 * @since   2.4.3
	 * @todo    clean up
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$group_id = $this->get_customer_country_group_id();
		$price_hash['wcj_price_by_country_group_id_data'] = array(
			$group_id,
			get_option( 'wcj_price_by_country_rounding', 'none' ),
			get_option( 'wcj_price_by_country_make_pretty', 'no' ),
			get_option( 'wcj_price_by_country_make_pretty_min_amount_multiplier', 1 ),
			get_option( 'woocommerce_price_num_decimals', 2 ),
			get_option( 'wcj_price_by_country_local_enabled', 'yes' ),
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
	 * change_price.
	 *
	 * @version 2.7.0
	 */
	function change_price( $price, $product ) {
		if ( null != ( $group_id = $this->get_customer_country_group_id() ) ) {
			return wcj_price_by_country( $price, $product, $group_id );
		}
		// No changes
		return $price;
	}
}

endif;

return new WCJ_Price_by_Country_Core();

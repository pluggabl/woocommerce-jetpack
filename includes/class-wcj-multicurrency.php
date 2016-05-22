<?php
/**
 * WooCommerce Jetpack Multicurrency
 *
 * The WooCommerce Jetpack Multicurrency class.
 *
 * @version 2.5.0
 * @since   2.4.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Multicurrency' ) ) :

class WCJ_Multicurrency extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	function __construct() {

		$this->id         = 'multicurrency';
		$this->short_desc = __( 'Multicurrency (Currency Switcher)', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add multiple currencies (currency switcher) to WooCommerce.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-multicurrency-currency-switcher/';
		parent::__construct();

		add_filter( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
//			add_filter( 'init', array( $this, 'add_hooks' ) );
			$this->add_hooks();

			if ( 'yes' === get_option( 'wcj_multicurrency_per_product_enabled' , 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}

			if ( is_admin() ) {
				include_once( 'reports/class-wcj-currency-reports.php' );
			}
		}
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.4.3
	 */
	function get_meta_box_options() {
		$main_product_id = get_the_ID();
		$_product = wc_get_product( $main_product_id );
		$products = array();
		if ( $_product->is_type( 'variable' ) ) {
			$available_variations = $_product->get_available_variations();
			foreach ( $available_variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );
				$products[ $variation['variation_id'] ] = ' (' . $variation_product->get_formatted_variation_attributes( true ) . ')';
			}
		} else {
			$products[ $main_product_id ] = '';
		}
		$currencies = array();
		$total_number = apply_filters( 'wcj_get_option_filter', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
		foreach ( $products as $product_id => $desc ) {
			for ( $i = 1; $i <= $total_number; $i++ ) {
				$currency_code = get_option( 'wcj_multicurrency_currency_' . $i );
				$currencies = array_merge( $currencies, array(
					array(
						'name'       => 'wcj_multicurrency_per_product_regular_price_' . $currency_code . '_' . $product_id,
						'default'    => '',
						'type'       => 'price',
						'title'      => '[' . $currency_code . '] ' . __( 'Regular Price', 'woocommerce-jetpack' ),
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_' . 'wcj_multicurrency_per_product_regular_price_' . $currency_code,
					),
					array(
						'name'       => 'wcj_multicurrency_per_product_sale_price_' . $currency_code . '_' . $product_id,
						'default'    => '',
						'type'       => 'price',
						'title'      => '[' . $currency_code . '] ' . __( 'Sale Price', 'woocommerce-jetpack' ),
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_' . 'wcj_multicurrency_per_product_sale_price_' . $currency_code,
					),
				) );
			}
		}
		return $currencies;
	}

	/**
	 * add_hooks.
	 *
	 * @version 2.5.0
	 */
	function add_hooks() {
		// Session
		if ( ! session_id() ) {
			session_start();
		}
		if ( isset( $_REQUEST['wcj-currency'] ) ) {
			$_SESSION['wcj-currency'] = $_REQUEST['wcj-currency'];
		}
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			// Prices
			add_filter( 'woocommerce_get_price',                      array( $this, 'change_price_by_currency' ), PHP_INT_MAX - 1, 2 );
			add_filter( 'woocommerce_get_sale_price',                 array( $this, 'change_price_by_currency' ), PHP_INT_MAX - 1, 2 );
			add_filter( 'woocommerce_get_regular_price',              array( $this, 'change_price_by_currency' ), PHP_INT_MAX - 1, 2 );
			// Currency hooks
			add_filter( 'woocommerce_currency_symbol',                array( $this, 'change_currency_symbol' ), PHP_INT_MAX - 1, 2 );
			add_filter( 'woocommerce_currency',                       array( $this, 'change_currency_code' ),   PHP_INT_MAX - 1, 1 );
			// Shipping
			add_filter( 'woocommerce_package_rates',                  array( $this, 'change_shipping_price_by_currency' ), PHP_INT_MAX - 1, 2 );
			// Variations
			add_filter( 'woocommerce_variation_prices_price',         array( $this, 'change_price_by_currency' ), PHP_INT_MAX - 1, 2 );
			add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'change_price_by_currency' ), PHP_INT_MAX - 1, 2 );
			add_filter( 'woocommerce_variation_prices_sale_price',    array( $this, 'change_price_by_currency' ), PHP_INT_MAX - 1, 2 );
			add_filter( 'woocommerce_get_variation_prices_hash',      array( $this, 'get_variation_prices_hash' ), PHP_INT_MAX - 1, 3 );
			// Grouped products
			add_filter( 'woocommerce_get_price_including_tax',        array( $this, 'change_price_by_currency_grouped' ), PHP_INT_MAX - 1, 3 );
			add_filter( 'woocommerce_get_price_excluding_tax',        array( $this, 'change_price_by_currency_grouped' ), PHP_INT_MAX - 1, 3 );
		}
	}

	/**
	 * change_price_by_currency_grouped.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function change_price_by_currency_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			if ( 'yes' === get_option( 'wcj_multicurrency_per_product_enabled' , 'yes' ) ) {
				$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
				foreach ( $_product->get_children() as $child_id ) {
					$the_price = get_post_meta( $child_id, '_price', true );
					$the_product = wc_get_product( $child_id );
					$the_price = $the_product->$get_price_method( 1, $the_price );
					if ( $the_price == $price ) {
						return $this->change_price_by_currency( $price, $the_product );
					}
				}
			} else {
				return $this->change_price_by_currency( $price, null );
			}
		}
		return $price;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.5.0
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$currency_code = $this->get_current_currency_code();
		$currency_exchange_rate = $this->get_currency_exchange_rate( $currency_code );
		$price_hash['wcj_multicurrency_data'] = array(
			$currency_code,
			$currency_exchange_rate,
			get_option( 'wcj_multicurrency_per_product_enabled', 'yes' ),
		);
		return $price_hash;
	}

	/**
	 * get_currency_exchange_rate.
	 *
	 * @version 2.4.3
	 */
	function get_currency_exchange_rate( $currency_code ) {
		$currency_exchange_rate = 1;
		$total_number = apply_filters( 'wcj_get_option_filter', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( $currency_code === get_option( 'wcj_multicurrency_currency_' . $i ) ) {
				$currency_exchange_rate = get_option( 'wcj_multicurrency_exchange_rate_' . $i );
				break;
			}
		}
		return $currency_exchange_rate;
	}

	/**
	 * do_revert.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function do_revert() {
		return ( 'yes' === get_option( 'wcj_multicurrency_revert', 'no' ) && is_checkout() );
	}

	/**
	 * change_price_by_currency.
	 *
	 * @version 2.5.0
	 */
	function change_price_by_currency( $price, $_product ) {

		if ( '' === $price ) {
			return $price;
		}

		if ( $this->do_revert() ) {
			return $price;
		}

		// Per product
		if ( 'yes' === get_option( 'wcj_multicurrency_per_product_enabled' , 'yes' ) ) {
			$the_product_id = ( isset( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
			if ( '' != ( $regular_price_per_product = get_post_meta( $the_product_id, '_' . 'wcj_multicurrency_per_product_regular_price_' . $this->get_current_currency_code(), true ) ) ) {
				$the_current_filter = current_filter();
				if ( 'woocommerce_get_price_including_tax' == $the_current_filter || 'woocommerce_get_price_excluding_tax' == $the_current_filter ) {
					$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
					return $_product->$get_price_method();
				} elseif ( 'woocommerce_get_price' == $the_current_filter || 'woocommerce_variation_prices_price' == $the_current_filter ) {
					$sale_price_per_product = get_post_meta( $the_product_id, '_' . 'wcj_multicurrency_per_product_sale_price_' . $this->get_current_currency_code(), true );
					return ( '' != $sale_price_per_product && $sale_price_per_product < $regular_price_per_product ) ? $sale_price_per_product : $regular_price_per_product;

				} elseif ( 'woocommerce_get_regular_price' == $the_current_filter || 'woocommerce_variation_prices_regular_price' == $the_current_filter ) {
					return $regular_price_per_product;

				} elseif ( 'woocommerce_get_sale_price' == $the_current_filter || 'woocommerce_variation_prices_sale_price' == $the_current_filter ) {
					$sale_price_per_product = get_post_meta( $the_product_id, '_' . 'wcj_multicurrency_per_product_sale_price_' . $this->get_current_currency_code(), true );
					return ( '' != $sale_price_per_product ) ? $sale_price_per_product : $price;
				}
			}
		}

		// Global
		if ( 1 != ( $currency_exchange_rate = $this->get_currency_exchange_rate( $this->get_current_currency_code() ) ) ) {
			return $price * $currency_exchange_rate;
		}

		// No changes
		return $price;
	}

	/**
	 * change_currency_symbol.
	 *
	 * @version 2.5.0
	 */
	function change_currency_symbol( $currency_symbol, $currency ) {
		if ( $this->do_revert() ) {
			return $currency_symbol;
		}
		return wcj_get_currency_symbol( $this->get_current_currency_code( $currency ) );
	}

	/**
	 * change_currency_code.
	 *
	 * @version 2.4.3
	 */
	function get_current_currency_code( $default_currency = '' ) {
		return ( isset( $_SESSION['wcj-currency'] ) ) ? $_SESSION['wcj-currency'] : $default_currency;
	}

	/**
	 * change_currency_code.
	 *
	 * @version 2.5.0
	 */
	function change_currency_code( $currency ) {
		if ( $this->do_revert() ) {
			return $currency;
		}
		return $this->get_current_currency_code( $currency );
	}

	/**
	 * change_shipping_price_by_currency.
	 *
	 * @version 2.5.0
	 */
	function change_shipping_price_by_currency( $package_rates, $package ) {
		if ( $this->do_revert() ) {
			return $package_rates;
		}
		$currency_exchange_rate = $this->get_currency_exchange_rate( $this->get_current_currency_code() );
		$modified_package_rates = array();
		foreach ( $package_rates as $id => $package_rate ) {
			if ( 1 != $currency_exchange_rate && isset( $package_rate->cost ) ) {
				$package_rate->cost = $package_rate->cost * $currency_exchange_rate;
				if ( isset( $package_rate->taxes ) && ! empty( $package_rate->taxes ) ) {
					foreach ( $package_rate->taxes as $tax_id => $tax ) {
						$package_rate->taxes[ $tax_id ] = $package_rate->taxes[ $tax_id ] * $currency_exchange_rate;
					}
				}
			}
			$modified_package_rates[ $id ] = $package_rate;
		}
		return $modified_package_rates;
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.4.3
	 */
	function add_settings_hook() {
		add_filter( 'wcj_multicurrency_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.3
	 */
	function get_settings() {
		$settings = apply_filters( 'wcj_multicurrency_settings', array() );
		return $this->add_standard_settings( $settings, __( 'After setting currencies in the Currencies Options below, use <em>Booster - Multicurrency Switcher</em> widget, or <em>[wcj_currency_select_drop_down_list]</em> shortcode. If you want to insert switcher in your PHP code, just use <em>echo do_shortcode( \'[wcj_currency_select_drop_down_list]\' );</em>', 'woocommerce-jetpack' ) );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.0
	 * @todo    rounding (maybe)
	 */
	function add_settings() {
		$currency_from = get_woocommerce_currency();
		$all_currencies = wcj_get_currencies_names_and_symbols();
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_multicurrency_options',
			),
			array(
				'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
				'id'       => 'wcj_multicurrency_exchange_rate_update_auto',
				'default'  => 'manual',
				'type'     => 'select',
				'options'  => array(
					'manual' => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
					'auto'   => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
				),
				'desc'     => ( '' == apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ) ) ?
					__( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
					:
					apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),
			array(
				'title'    => __( 'Multicurrency on per Product Basis', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This will add meta boxes in product edit.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_multicurrency_per_product_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Revert Currency to Default on Checkout', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_multicurrency_revert',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_multicurrency_options',
			),
			array(
				'title'    => __( 'Currencies Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'One currency probably should be set to current (original) shop currency with an exchange rate of 1.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_multicurrency_currencies_options',
			),
			array(
				'title'    => __( 'Total Currencies', 'woocommerce-jetpack' ),
				'id'       => 'wcj_multicurrency_total_number',
				'default'  => 2,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '2', )
				),
			),
		);
		$total_number = apply_filters( 'wcj_get_option_filter', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$currency_to = get_option( 'wcj_multicurrency_currency_' . $i, $currency_from );
			$custom_attributes = array(
				'currency_from'        => $currency_from,
				'currency_to'          => $currency_to,
				'multiply_by_field_id' => 'wcj_multicurrency_exchange_rate_' . $i,
			);
			if ( $currency_from == $currency_to ) {
				$custom_attributes['disabled'] = 'disabled';
			}
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Currency', 'woocommerce-jetpack' ) . ' #' . $i,
					'id'       => 'wcj_multicurrency_currency_' . $i,
					'default'  => $currency_from,
					'type'     => 'select',
					'options'  => $all_currencies,
					'css'      => 'width:250px;',
				),
				array(
					'title'                    => '',
					'id'                       => 'wcj_multicurrency_exchange_rate_' . $i,
					'default'                  => 1,
					'type'                     => 'exchange_rate',
					'custom_attributes'        => array( 'step' => '0.000001', 'min'  => '0', ),
					'custom_attributes_button' => $custom_attributes,
					'css'                      => 'width:100px;',
					'value'                    => $currency_from . '/' . $currency_to,
					'value_title'              => sprintf( __( 'Grab %s rate from Yahoo.com', 'woocommerce-jetpack' ), $currency_from . '/' . $currency_to ),
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_multicurrency_currencies_options',
			),
		) );
		return $settings;
	}

}

endif;

return new WCJ_Multicurrency();

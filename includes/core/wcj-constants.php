<?php
/**
 * Booster for WooCommerce Constants
 *
 * @version 5.6.1
 * @since   2.7.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WCJ_WC_VERSION' ) ) {
	/**
	 * WooCommerce version.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_WC_VERSION', wcj_get_option( 'woocommerce_version', null ) );
}

if ( ! defined( 'WCJ_IS_WC_VERSION_BELOW_3' ) ) {
	/**
	 * WooCommerce version - is below version 3.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_IS_WC_VERSION_BELOW_3', version_compare( WCJ_WC_VERSION, '3.0.0', '<' ) );
}

if ( ! defined( 'WCJ_IS_WC_VERSION_BELOW_3_2_0' ) ) {
	/**
	 * WooCommerce version - is below version 3.2.0.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	define( 'WCJ_IS_WC_VERSION_BELOW_3_2_0', version_compare( WCJ_WC_VERSION, '3.2.0', '<' ) );
}

if ( ! defined( 'WCJ_IS_WC_VERSION_BELOW_3_3_0' ) ) {
	/**
	 * WooCommerce version - is below version 3.3.0.
	 *
	 * @version 3.4.5
	 * @since   3.4.5
	 */
	define( 'WCJ_IS_WC_VERSION_BELOW_3_3_0', version_compare( WCJ_WC_VERSION, '3.3.0', '<' ) );
}

if ( ! defined( 'WCJ_IS_WC_VERSION_BELOW_3_4_0' ) ) {
	/**
	 * WooCommerce version - is below version 3.4.0.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 */
	define( 'WCJ_IS_WC_VERSION_BELOW_3_4_0', version_compare( WCJ_WC_VERSION, '3.4.0', '<' ) );
}

if ( ! defined( 'WCJ_PRODUCT_GET_PRICE_FILTER' ) ) {
	/**
	 * Price filters - price.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_PRODUCT_GET_PRICE_FILTER', ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_price' : 'woocommerce_product_get_price' ) );
}

if ( ! defined( 'WCJ_PRODUCT_GET_SALE_PRICE_FILTER' ) ) {
	/**
	 * Price filters - sale price.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_PRODUCT_GET_SALE_PRICE_FILTER', ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_sale_price' : 'woocommerce_product_get_sale_price' ) );
}

if ( ! defined( 'WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER' ) ) {
	/**
	 * Price filters - regular price.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER', ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_regular_price' : 'woocommerce_product_get_regular_price' ) );
}

if ( ! defined( 'WCJ_SESSION_TYPE' ) ) {
	/**
	 * Session type.
	 *
	 * @version 3.6.0
	 * @since   3.1.0
	 * @todo    (maybe) set default to `wc`
	 */
	define( 'WCJ_SESSION_TYPE', ( 'yes' === wcj_get_option( 'wcj_general_enabled', 'no' ) ? wcj_get_option( 'wcj_general_advanced_session_type', 'standard' ) : 'standard' ) );
}

if ( ! defined( 'WCJ_VERSION_OPTION' ) ) {
	/**
	 * Booster version option name.
	 *
	 * @version 5.6.1
	 * @since   3.3.0
	 * @todo    (maybe) option should have `wcj_` prefix (however it will be deleted during "Reset", and "Booster was updated to v..." message will show up)
	 */
	define( 'WCJ_VERSION_OPTION', ( 'woocommerce-jetpack.php' === basename( WCJ_FREE_PLUGIN_FILE ) ? 'booster_for_woocommerce_version' : 'booster_plus_for_woocommerce_version' ) );
}

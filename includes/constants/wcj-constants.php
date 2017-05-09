<?php
/**
 * Booster for WooCommerce Constants
 *
 * @version 2.7.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// WooCommerce version

if ( ! defined( 'WCJ_WC_VERSION' ) ) {
	define( 'WCJ_WC_VERSION', get_option( 'woocommerce_version', null ) );
}

if ( ! defined( 'WCJ_IS_WC_VERSION_BELOW_3' ) ) {
	define( 'WCJ_IS_WC_VERSION_BELOW_3', version_compare( WCJ_WC_VERSION, '3.0.0', '<' ) );
}

// Price filters

if ( ! defined( 'WCJ_PRODUCT_GET_PRICE_FILTER' ) ) {
	$filter = ( WCJ_IS_WC_VERSION_BELOW_3 ) ? 'woocommerce_get_price'         : 'woocommerce_product_get_price';
	define( 'WCJ_PRODUCT_GET_PRICE_FILTER', $filter );
}

if ( ! defined( 'WCJ_PRODUCT_GET_SALE_PRICE_FILTER' ) ) {
	$filter = ( WCJ_IS_WC_VERSION_BELOW_3 ) ? 'woocommerce_get_sale_price'    : 'woocommerce_product_get_sale_price';
	define( 'WCJ_PRODUCT_GET_SALE_PRICE_FILTER', $filter );
}

if ( ! defined( 'WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER' ) ) {
	$filter = ( WCJ_IS_WC_VERSION_BELOW_3 ) ? 'woocommerce_get_regular_price' : 'woocommerce_product_get_regular_price';
	define( 'WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER', $filter );
}
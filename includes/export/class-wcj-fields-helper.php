<?php
/**
 * Booster for WooCommerce Export Fields Helper
 *
 * @version 2.7.0
 * @since   2.5.9
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Export_Fields_Helper' ) ) :

class WCJ_Export_Fields_Helper {

	/**
	 * Constructor.
	 *
	 * @version 2.7.0
	 * @since   2.5.9
	 */
	function __construct() {
		return true;
	}

	/**
	 * get_customer_from_order_export_fields.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function get_customer_from_order_export_fields() {
		return array(
			'customer-nr'                 => __( 'Customer Nr.', 'woocommerce-jetpack' ),
			'customer-billing-email'      => __( 'Billing Email', 'woocommerce-jetpack' ),
			'customer-billing-first-name' => __( 'Billing First Name', 'woocommerce-jetpack' ),
			'customer-billing-last-name'  => __( 'Billing Last Name', 'woocommerce-jetpack' ),
			'customer-billing-company'    => __( 'Billing Company', 'woocommerce-jetpack' ),
			'customer-billing-address-1'  => __( 'Billing Address 1', 'woocommerce-jetpack' ),
			'customer-billing-address-2'  => __( 'Billing Address 2', 'woocommerce-jetpack' ),
			'customer-billing-city'       => __( 'Billing City', 'woocommerce-jetpack' ),
			'customer-billing-state'      => __( 'Billing State', 'woocommerce-jetpack' ),
			'customer-billing-postcode'   => __( 'Billing Postcode', 'woocommerce-jetpack' ),
			'customer-billing-country'    => __( 'Billing Country', 'woocommerce-jetpack' ),
			'customer-billing-phone'      => __( 'Billing Phone', 'woocommerce-jetpack' ),
			'customer-last-order-date'    => __( 'Last Order Date', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * get_customer_from_order_export_default_fields_ids.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function get_customer_from_order_export_default_fields_ids() {
		return array(
			'customer-nr',
			'customer-billing-email',
			'customer-billing-first-name',
			'customer-billing-last-name',
			'customer-last-order-date',
		);
	}

	/**
	 * get_customer_export_fields.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function get_customer_export_fields() {
		return array(
			'customer-id'           => __( 'Customer ID', 'woocommerce-jetpack' ),
			'customer-email'        => __( 'Email', 'woocommerce-jetpack' ),
			'customer-first-name'   => __( 'First Name', 'woocommerce-jetpack' ),
			'customer-last-name'    => __( 'Last Name', 'woocommerce-jetpack' ),
			'customer-login'        => __( 'Login', 'woocommerce-jetpack' ),
			'customer-nicename'     => __( 'Nicename', 'woocommerce-jetpack' ),
			'customer-url'          => __( 'URL', 'woocommerce-jetpack' ),
			'customer-registered'   => __( 'Registered', 'woocommerce-jetpack' ),
			'customer-display-name' => __( 'Display Name', 'woocommerce-jetpack' ),
//			'customer-debug'        => __( 'Debug', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * get_customer_export_default_fields_ids.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function get_customer_export_default_fields_ids() {
		return array(
			'customer-id',
			'customer-email',
			'customer-first-name',
			'customer-last-name',
		);
	}

	/**
	 * get_order_items_export_fields.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function get_order_items_export_fields() {
		return array(
			'order-id'                    => __( 'Order ID', 'woocommerce-jetpack' ),
			'order-number'                => __( 'Order Number', 'woocommerce-jetpack' ),
			'order-status'                => __( 'Order Status', 'woocommerce-jetpack' ),
			'order-date'                  => __( 'Order Date', 'woocommerce-jetpack' ),
			'order-time'                  => __( 'Order Time', 'woocommerce-jetpack' ),
			'order-item-count'            => __( 'Order Item Count', 'woocommerce-jetpack' ),
			'order-currency'              => __( 'Order Currency', 'woocommerce-jetpack' ),
			'order-total'                 => __( 'Order Total', 'woocommerce-jetpack' ),
			'order-total-tax'             => __( 'Order Total Tax', 'woocommerce-jetpack' ),
			'order-payment-method'        => __( 'Order Payment Method', 'woocommerce-jetpack' ),
			'order-notes'                 => __( 'Order Notes', 'woocommerce-jetpack' ),
			'billing-first-name'          => __( 'Billing First Name', 'woocommerce-jetpack' ),
			'billing-last-name'           => __( 'Billing Last Name', 'woocommerce-jetpack' ),
			'billing-company'             => __( 'Billing Company', 'woocommerce-jetpack' ),
			'billing-address-1'           => __( 'Billing Address 1', 'woocommerce-jetpack' ),
			'billing-address-2'           => __( 'Billing Address 2', 'woocommerce-jetpack' ),
			'billing-city'                => __( 'Billing City', 'woocommerce-jetpack' ),
			'billing-state'               => __( 'Billing State', 'woocommerce-jetpack' ),
			'billing-postcode'            => __( 'Billing Postcode', 'woocommerce-jetpack' ),
			'billing-country'             => __( 'Billing Country', 'woocommerce-jetpack' ),
			'billing-phone'               => __( 'Billing Phone', 'woocommerce-jetpack' ),
			'billing-email'               => __( 'Billing Email', 'woocommerce-jetpack' ),
			'shipping-first-name'         => __( 'Shipping First Name', 'woocommerce-jetpack' ),
			'shipping-last-name'          => __( 'Shipping Last Name', 'woocommerce-jetpack' ),
			'shipping-company'            => __( 'Shipping Company', 'woocommerce-jetpack' ),
			'shipping-address-1'          => __( 'Shipping Address 1', 'woocommerce-jetpack' ),
			'shipping-address-2'          => __( 'Shipping Address 2', 'woocommerce-jetpack' ),
			'shipping-city'               => __( 'Shipping City', 'woocommerce-jetpack' ),
			'shipping-state'              => __( 'Shipping State', 'woocommerce-jetpack' ),
			'shipping-postcode'           => __( 'Shipping Postcode', 'woocommerce-jetpack' ),
			'shipping-country'            => __( 'Shipping Country', 'woocommerce-jetpack' ),

			'item-name'                   => __( 'Item Name', 'woocommerce-jetpack' ),
			'item-meta'                   => __( 'Item Meta', 'woocommerce-jetpack' ),
			'item-variation-meta'         => __( 'Item Variation Meta', 'woocommerce-jetpack' ),
			'item-qty'                    => __( 'Item Quantity', 'woocommerce-jetpack' ),
			'item-tax-class'              => __( 'Item Tax Class', 'woocommerce-jetpack' ),
			'item-product-id'             => __( 'Item Product ID', 'woocommerce-jetpack' ),
			'item-variation-id'           => __( 'Item Variation ID', 'woocommerce-jetpack' ),
			'item-line-subtotal'          => __( 'Item Line Subtotal', 'woocommerce-jetpack' ),
			'item-line-total'             => __( 'Item Line Total', 'woocommerce-jetpack' ),
			'item-line-subtotal-tax'      => __( 'Item Line Subtotal Tax', 'woocommerce-jetpack' ),
			'item-line-tax'               => __( 'Item Line Tax', 'woocommerce-jetpack' ),
			'item-line-subtotal-plus-tax' => __( 'Item Line Subtotal Plus Tax', 'woocommerce-jetpack' ),
			'item-line-total-plus-tax'    => __( 'Item Line Total Plus Tax', 'woocommerce-jetpack' ),
			'item-product-input-fields'   => __( 'Item Product Input Fields', 'woocommerce-jetpack' ),
//			'item-debug'                  => __( 'Item Debug', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * get_order_items_export_default_fields_ids.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function get_order_items_export_default_fields_ids() {
		return array(
			'order-number',
			'order-status',
			'order-date',
			'order-currency',
			'order-payment-method',
			'item-name',
			'item-variation-meta',
			'item-qty',
			'item-tax-class',
			'item-product-id',
			'item-variation-id',
			'item-line-total',
			'item-line-tax',
			'item-line-total-plus-tax',
		);
	}

	/**
	 * get_order_export_fields.
	 *
	 * @version 2.5.7
	 * @since   2.5.6
	 */
	function get_order_export_fields() {
		return array(
			'order-id'                         => __( 'Order ID', 'woocommerce-jetpack' ),
			'order-number'                     => __( 'Order Number', 'woocommerce-jetpack' ),
			'order-status'                     => __( 'Order Status', 'woocommerce-jetpack' ),
			'order-date'                       => __( 'Order Date', 'woocommerce-jetpack' ),
			'order-time'                       => __( 'Order Time', 'woocommerce-jetpack' ),
			'order-item-count'                 => __( 'Order Item Count', 'woocommerce-jetpack' ),
			'order-items'                      => __( 'Order Items', 'woocommerce-jetpack' ),
			'order-items-product-input-fields' => __( 'Order Items Product Input Fields', 'woocommerce-jetpack' ),
			'order-currency'                   => __( 'Order Currency', 'woocommerce-jetpack' ),
			'order-total'                      => __( 'Order Total', 'woocommerce-jetpack' ),
			'order-total-tax'                  => __( 'Order Total Tax', 'woocommerce-jetpack' ),
			'order-payment-method'             => __( 'Order Payment Method', 'woocommerce-jetpack' ),
			'order-notes'                      => __( 'Order Notes', 'woocommerce-jetpack' ),
			'billing-first-name'               => __( 'Billing First Name', 'woocommerce-jetpack' ),
			'billing-last-name'                => __( 'Billing Last Name', 'woocommerce-jetpack' ),
			'billing-company'                  => __( 'Billing Company', 'woocommerce-jetpack' ),
			'billing-address-1'                => __( 'Billing Address 1', 'woocommerce-jetpack' ),
			'billing-address-2'                => __( 'Billing Address 2', 'woocommerce-jetpack' ),
			'billing-city'                     => __( 'Billing City', 'woocommerce-jetpack' ),
			'billing-state'                    => __( 'Billing State', 'woocommerce-jetpack' ),
			'billing-postcode'                 => __( 'Billing Postcode', 'woocommerce-jetpack' ),
			'billing-country'                  => __( 'Billing Country', 'woocommerce-jetpack' ),
			'billing-phone'                    => __( 'Billing Phone', 'woocommerce-jetpack' ),
			'billing-email'                    => __( 'Billing Email', 'woocommerce-jetpack' ),
			'shipping-first-name'              => __( 'Shipping First Name', 'woocommerce-jetpack' ),
			'shipping-last-name'               => __( 'Shipping Last Name', 'woocommerce-jetpack' ),
			'shipping-company'                 => __( 'Shipping Company', 'woocommerce-jetpack' ),
			'shipping-address-1'               => __( 'Shipping Address 1', 'woocommerce-jetpack' ),
			'shipping-address-2'               => __( 'Shipping Address 2', 'woocommerce-jetpack' ),
			'shipping-city'                    => __( 'Shipping City', 'woocommerce-jetpack' ),
			'shipping-state'                   => __( 'Shipping State', 'woocommerce-jetpack' ),
			'shipping-postcode'                => __( 'Shipping Postcode', 'woocommerce-jetpack' ),
			'shipping-country'                 => __( 'Shipping Country', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * get_order_export_default_fields_ids.
	 *
	 * @version 2.5.7
	 * @since   2.5.6
	 */
	function get_order_export_default_fields_ids() {
		return array(
			'order-id',
			'order-number',
			'order-status',
			'order-date',
			'order-time',
			'order-item-count',
			'order-items',
			'order-currency',
			'order-total',
			'order-total-tax',
			'order-payment-method',
			'order-notes',
			'billing-first-name',
			'billing-last-name',
			'billing-company',
			'billing-address-1',
			'billing-address-2',
			'billing-city',
			'billing-state',
			'billing-postcode',
			'billing-country',
			'billing-phone',
			'billing-email',
			'shipping-first-name',
			'shipping-last-name',
			'shipping-company',
			'shipping-address-1',
			'shipping-address-2',
			'shipping-city',
			'shipping-state',
			'shipping-postcode',
			'shipping-country',
		);
	}

	/**
	 * get_product_export_fields.
	 *
	 * @version 2.6.0
	 * @since   2.5.7
	 */
	function get_product_export_fields() {
		return array(
			'product-id'                         => __( 'Product ID', 'woocommerce-jetpack' ),
			'parent-product-id'                  => __( 'Parent Product ID', 'woocommerce-jetpack' ),
			'product-name'                       => __( 'Name', 'woocommerce-jetpack' ),
			'product-sku'                        => __( 'SKU', 'woocommerce-jetpack' ),
			'product-stock'                      => __( 'Total Stock', 'woocommerce-jetpack' ),
			'product-stock-quantity'             => __( 'Stock Quantity', 'woocommerce-jetpack' ),
			'product-regular-price'              => __( 'Regular Price', 'woocommerce-jetpack' ),
			'product-sale-price'                 => __( 'Sale Price', 'woocommerce-jetpack' ),
			'product-price'                      => __( 'Price', 'woocommerce-jetpack' ),
			'product-type'                       => __( 'Type', 'woocommerce-jetpack' ),
//			'product-attributes'                 => __( 'Attributes', 'woocommerce-jetpack' ),
			'product-image-url'                  => __( 'Image URL', 'woocommerce-jetpack' ),
			'product-short-description'          => __( 'Short Description', 'woocommerce-jetpack' ),
			'product-description'                => __( 'Description', 'woocommerce-jetpack' ),
			'product-status'                     => __( 'Status', 'woocommerce-jetpack' ),
			'product-url'                        => __( 'URL', 'woocommerce-jetpack' ),
			'product-shipping-class'             => __( 'Shipping Class', 'woocommerce-jetpack' ),
			'product-shipping-class-id'          => __( 'Shipping Class ID', 'woocommerce-jetpack' ),
			'product-width'                      => __( 'Width', 'woocommerce-jetpack' ),
			'product-length'                     => __( 'Length', 'woocommerce-jetpack' ),
			'product-height'                     => __( 'Height', 'woocommerce-jetpack' ),
			'product-weight'                     => __( 'Weight', 'woocommerce-jetpack' ),
			'product-downloadable'               => __( 'Downloadable', 'woocommerce-jetpack' ),
			'product-virtual'                    => __( 'Virtual', 'woocommerce-jetpack' ),
			'product-sold-individually'          => __( 'Sold Individually', 'woocommerce-jetpack' ),
			'product-tax-status'                 => __( 'Tax Status', 'woocommerce-jetpack' ),
			'product-tax-class'                  => __( 'Tax Class', 'woocommerce-jetpack' ),
			'product-manage-stock'               => __( 'Manage Stock', 'woocommerce-jetpack' ),
			'product-stock-status'               => __( 'Stock Status', 'woocommerce-jetpack' ),
			'product-backorders'                 => __( 'Backorders', 'woocommerce-jetpack' ),
			'product-featured'                   => __( 'Featured', 'woocommerce-jetpack' ),
			'product-visibility'                 => __( 'Visibility', 'woocommerce-jetpack' ),
			'product-price-including-tax'        => __( 'Price Including Tax', 'woocommerce-jetpack' ),
			'product-price-excluding-tax'        => __( 'Price Excluding Tax', 'woocommerce-jetpack' ),
			'product-display-price'              => __( 'Display Price', 'woocommerce-jetpack' ),
			'product-average-rating'             => __( 'Average Rating', 'woocommerce-jetpack' ),
			'product-rating-count'               => __( 'Rating Count', 'woocommerce-jetpack' ),
			'product-review-count'               => __( 'Review Count', 'woocommerce-jetpack' ),
			'product-categories'                 => __( 'Categories', 'woocommerce-jetpack' ),
			'product-tags'                       => __( 'Tags', 'woocommerce-jetpack' ),
			'product-dimensions'                 => __( 'Dimensions', 'woocommerce-jetpack' ),
			'product-formatted-name'             => __( 'Formatted Name', 'woocommerce-jetpack' ),
			'product-availability'               => __( 'Availability', 'woocommerce-jetpack' ),
			'product-availability-class'         => __( 'Availability Class', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * get_product_export_default_fields_ids.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_product_export_default_fields_ids() {
		return array(
			'product-id',
			'product-name',
			'product-sku',
			'product-stock',
			'product-regular-price',
			'product-sale-price',
			'product-price',
			'product-type',
			'product-image-url',
			'product-short-description',
			'product-status',
			'product-url',
		);
	}

}

endif;

return new WCJ_Export_Fields_Helper();

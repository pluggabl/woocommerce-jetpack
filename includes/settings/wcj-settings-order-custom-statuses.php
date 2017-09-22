<?php
/**
 * Booster for WooCommerce Settings - Order Custom Statuses
 *
 * @version 3.1.2
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Custom Statuses', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_orders_custom_statuses_options',
	),
	array(
		'title'    => __( 'Default Order Status', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable the module to add custom statuses to the list.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can change the default order status here. However payment gateways can change this status immediately on order creation. E.g. BACS gateway will change status to On-hold.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_custom_statuses_default_status',
		'default'  => apply_filters( 'woocommerce_default_order_status', 'pending' ),
		'type'     => 'select',
		'options'  => $this->get_order_statuses(),
	),
	array(
		'title'    => __( 'Add All Statuses to Admin Order Bulk Actions', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_custom_statuses_add_to_bulk_actions',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Add Custom Statuses to Admin Reports', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_custom_statuses_add_to_reports',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Make Custom Status Orders Editable', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_custom_statuses_is_order_editable',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'title'    => __( '"Processing" and "Complete" Action Buttons', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'By default, when order has custom status, "Processing" and "Complete" action buttons are hidden. You can enable it here. Possible values are: Show both; Show "Processing" only; Show "Complete" only; Hide (default).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_custom_statuses_processing_and_completed_actions',
		'default'  => 'hide',
		'type'     => 'select',
		'options'  => array(
			'show_both'       => __( 'Show both', 'woocommerce-jetpack' ),
			'show_processing' => __( 'Show "Processing" only', 'woocommerce-jetpack' ),
			'show_complete'   => __( 'Show "Complete" only', 'woocommerce-jetpack' ),
			'hide'            => __( 'Hide', 'woocommerce-jetpack' ),
		),
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Add Custom Statuses to Admin Order List Action Buttons', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_custom_statuses_add_to_order_list_actions',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'desc'     => __( 'Enable Colors', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_custom_statuses_add_to_order_list_actions_colored',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_orders_custom_statuses_options',
	),
);

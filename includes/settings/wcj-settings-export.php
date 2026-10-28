<?php
/**
 * Booster for WooCommerce - Settings - Export
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    add "Additional Export Fields" for "Customers from Orders" and (maybe) "Customers"
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message      = apply_filters( 'booster_message', '', 'desc' );
$settings     = array(
	array(
		'id'   => 'export_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'export_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'export_export_tab'                      => __( 'Export', 'woocommerce-jetpack' ),
			'export_export_order_tab'                => __( 'Export Orders', 'woocommerce-jetpack' ),
			'export_export_order_items_tab'          => __( 'Export Orders Items', 'woocommerce-jetpack' ),
			'export_export_products_tab'             => __( 'Export Products', 'woocommerce-jetpack' ),
			'export_export_customers_tab'            => __( 'Export Customers', 'woocommerce-jetpack' ),
			'export_export_customers_from_order_tab' => __( 'Export Customers from Orders', 'woocommerce-jetpack' ),
			'export_tools_tab'                       => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'export_export_tab',
		'type' => 'tab_start',
	),
	array(
		'title'   => __( 'CSV Separator', 'woocommerce-jetpack' ),
		'id'      => 'wcj_export_csv_separator',
		'default' => ',',
		'type'    => 'text',
	),
	array(
		'title'             => __( 'Smart Formatting', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'id'                => 'wcj_export_csv_smart_formatting',
		/* translators: %s: translators Added */
		'desc_tip'          => sprintf( __( 'Tries to handle special characters as commas and quotes, formatting fields according to <a href="%s">RFC4180</a>', 'woocommerce-jetpack' ), 'https://tools.ietf.org/html/rfc4180' ),
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'title'    => __( 'UTF-8 BOM', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Add UTF-8 BOM sequence', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_csv_add_utf_8_bom',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'id'   => 'wcj_export_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'export_export_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'export_export_order_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Export Orders Fields', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_orders_fields',
		'default'  => $this->fields_helper->get_order_export_default_fields_ids(),
		'type'     => 'multiselect',
		'options'  => $this->fields_helper->get_order_export_fields(),
		'css'      => 'height:300px;',
	),
	array(
		'title'             => __( 'Additional Export Orders Fields', 'woocommerce-jetpack' ),
		'id'                => 'wcj_export_orders_fields_additional_total_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ?
				apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array(
				'step' => '1',
				'min'  => '0',
			)
		),
	),
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_fields_additional_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'      => 'wcj_export_orders_fields_additional_enabled_' . $i,
				'desc'    => __( 'Enabled', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'desc'    => __( 'Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_export_orders_fields_additional_title_' . $i,
				'type'    => 'text',
				'default' => '',
			),
			array(
				'desc'    => __( 'Type', 'woocommerce-jetpack' ),
				'id'      => 'wcj_export_orders_fields_additional_type_' . $i,
				'type'    => 'select',
				'default' => 'meta',
				'options' => array(
					'meta'      => __( 'Order Meta', 'woocommerce-jetpack' ),
					'shortcode' => __( 'Order Shortcode', 'woocommerce-jetpack' ),
				),
			),
			array(
				'desc'     => __( 'Value', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter order meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
					' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Orders shortcodes here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_orders_fields_additional_value_' . $i,
				'type'     => 'text',
				'default'  => '',
			),
		)
	);
}
$settings     = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_export_orders_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'export_export_order_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'export_export_order_items_tab',
			'type' => 'tab_start',
		),
		array(
			'title'    => __( 'Export Orders Items Fields', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_orders_items_fields',
			'default'  => $this->fields_helper->get_order_items_export_default_fields_ids(),
			'type'     => 'multiselect',
			'options'  => $this->fields_helper->get_order_items_export_fields(),
			'css'      => 'height:300px;',
		),
		array(
			'title'             => __( 'Additional Export Orders Items Fields', 'woocommerce-jetpack' ),
			'id'                => 'wcj_export_orders_items_fields_additional_total_number',
			'default'           => 1,
			'type'              => 'custom_number',
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => array_merge(
				is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ?
				apply_filters( 'booster_message', '', 'readonly' ) : array(),
				array(
					'step' => '1',
					'min'  => '0',
				)
			),
		),
	)
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_items_fields_additional_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'      => 'wcj_export_orders_items_fields_additional_enabled_' . $i,
				'desc'    => __( 'Enabled', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'desc'    => __( 'Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_export_orders_items_fields_additional_title_' . $i,
				'type'    => 'text',
				'default' => '',
			),
			array(
				'desc'    => __( 'Type', 'woocommerce-jetpack' ),
				'id'      => 'wcj_export_orders_items_fields_additional_type_' . $i,
				'type'    => 'select',
				'default' => 'meta',
				'options' => array(
					'meta'              => __( 'Order Meta', 'woocommerce-jetpack' ),
					'item_meta'         => __( 'Order Item Meta', 'woocommerce-jetpack' ),
					'shortcode'         => __( 'Order Shortcode', 'woocommerce-jetpack' ),
					'meta_product'      => __( 'Product Meta', 'woocommerce-jetpack' ),
					'shortcode_product' => __( 'Product Shortcode', 'woocommerce-jetpack' ),
				),
			),
			array(
				'desc'     => __( 'Value', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter order/product meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
					' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Orders/Products shortcodes here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_orders_items_fields_additional_value_' . $i,
				'type'     => 'text',
				'default'  => '',
			),
		)
	);
}
$settings     = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_export_orders_items_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'export_export_order_items_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'export_export_products_tab',
			'type' => 'tab_start',
		),
		array(
			'title'    => __( 'Export Products Fields', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_products_fields',
			'default'  => $this->fields_helper->get_product_export_default_fields_ids(),
			'type'     => 'multiselect',
			'options'  => $this->fields_helper->get_product_export_fields(),
			'css'      => 'height:300px;',
		),
		array(
			'title'   => __( 'Variable Products', 'woocommerce-jetpack' ),
			'id'      => 'wcj_export_products_variable',
			'default' => 'variable_only',
			'type'    => 'select',
			'options' => array(
				'variable_only'           => __( 'Export variable (main) product only', 'woocommerce-jetpack' ),
				'variations_only'         => __( 'Export variation products only', 'woocommerce-jetpack' ),
				'variable_and_variations' => __( 'Export variable (main) and variation products', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'             => __( 'Additional Export Products Fields', 'woocommerce-jetpack' ),
			'id'                => 'wcj_export_products_fields_additional_total_number',
			'default'           => 1,
			'type'              => 'custom_number',
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => array_merge(
				is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ?
				apply_filters( 'booster_message', '', 'readonly' ) : array(),
				array(
					'step' => '1',
					'min'  => '0',
				)
			),
		),
	)
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_products_fields_additional_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'      => 'wcj_export_products_fields_additional_enabled_' . $i,
				'desc'    => __( 'Enabled', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'desc'    => __( 'Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_export_products_fields_additional_title_' . $i,
				'type'    => 'text',
				'default' => '',
			),
			array(
				'desc'    => __( 'Type', 'woocommerce-jetpack' ),
				'id'      => 'wcj_export_products_fields_additional_type_' . $i,
				'type'    => 'select',
				'default' => 'meta',
				'options' => array(
					'meta'      => __( 'Product Meta', 'woocommerce-jetpack' ),
					'shortcode' => __( 'Product Shortcode', 'woocommerce-jetpack' ),
				),
			),
			array(
				'desc'     => __( 'Value', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter product meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
					' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Products shortcodes here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_products_fields_additional_value_' . $i,
				'type'     => 'text',
				'default'  => '',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_export_products_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'export_export_products_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'export_export_customers_tab',
			'type' => 'tab_start',
		),
		array(
			'title'    => __( 'Export Customers Fields', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_customers_fields',
			'default'  => $this->fields_helper->get_customer_export_default_fields_ids(),
			'type'     => 'multiselect',
			'options'  => $this->fields_helper->get_customer_export_fields(),
			'css'      => 'height:150px;',
		),
		array(
			'id'   => 'wcj_export_customers_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'export_export_customers_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'export_export_customers_from_order_tab',
			'type' => 'tab_start',
		),
		array(
			'title'    => __( 'Export Customers from Orders Fields', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_customers_from_orders_fields',
			'default'  => $this->fields_helper->get_customer_from_order_export_default_fields_ids(),
			'type'     => 'multiselect',
			'options'  => $this->fields_helper->get_customer_from_order_export_fields(),
			'css'      => 'height:150px;',
		),
		array(
			'id'   => 'wcj_export_customers_from_orders_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'export_export_customers_from_order_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'export_tools_tab',
			'type' => 'tab_start',
		),
		array(
			'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_' . $this->id . '_module_tools',
			'type'     => 'custom_link',
			'link'     => ( $this->is_enabled() ) ?
			'<p><code><a href="' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=export_customers&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' . __( 'Export Customers', 'woocommerce-jetpack' ) . '</a></code>
			<p>
			<p><code><a href="' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=export_customers_from_orders&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' . __( 'Export Customers from Orders', 'woocommerce-jetpack' ) . '</a></code>
			<p>
			<p><code><a href="' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=export_orders&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' . __( 'Export Orders', 'woocommerce-jetpack' ) . '</a></code>
			<p>
			<p><code><a href="' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=export_orders_items&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' . __( 'Export Orders Items', 'woocommerce-jetpack' ) . '</a></code>
			<p>
			<p><code><a href="' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=export_products&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' . __( 'Export Products', 'woocommerce-jetpack' ) . '</a></code>
			<p>
			' :
				'<p><code>' . __( 'Export Customers', 'woocommerce-jetpack' ) . '</code></p>
				<p><code>' . __( 'Export Customers from Orders', 'woocommerce-jetpack' ) . '</code></p>
				<p><code>' . __( 'Export Orders', 'woocommerce-jetpack' ) . '</code></p>
				<p><code>' . __( 'Export Orders Items', 'woocommerce-jetpack' ) . '</code></p>
				<p><code>' . __( 'Export Products', 'woocommerce-jetpack' ) . '</code></p>
			',
		),
		array(
			'id'   => 'export_tools_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;

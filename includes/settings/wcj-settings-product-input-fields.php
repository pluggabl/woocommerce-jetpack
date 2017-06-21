<?php
/**
 * Booster for WooCommerce - Settings - Product Input Fields
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Product Input Fields per Product Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'Add custom input fields to product\'s single page for customer to fill before adding product to cart.', 'woocommerce-jetpack' ) . ' '
			. __( 'When enabled this module will add "Product Input Fields" tab to each product\'s "Edit" page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_local_options',
	),
	array(
		'title'    => __( 'Product Input Fields - per Product', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip' => __( 'Add custom input field on per product basis.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_local_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Default Number of Product Input Fields per Product', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_local_total_number_default',
		'desc_tip' => __( 'You will be able to change this number later as well as define the fields, for each product individually, in product\'s "Edit".', 'woocommerce-jetpack' ),
		'default'  => 1,
		'type'     => 'number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '1' )
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_input_fields_local_options',
	),
	array(
		'title'    => __( 'Product Input Fields Global Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'Add custom input fields to product\'s single page for customer to fill before adding product to cart.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_global_options',
	),
	array(
		'title'    => __( 'Product Input Fields - All Products', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip' => __( 'Add custom input fields to all products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_global_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Product Input Fields Number', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Click Save changes after you change this number.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_global_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '1' )
		),
	),
);
$options = $this->get_options();
for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_product_input_fields_global_total_number', 1 ) ); $i++ ) {
	foreach( $options as $option ) {
		$settings = array_merge( $settings, array(
			array(
				'title'    => ( 'wcj_product_input_fields_enabled_global_' === $option['id'] ) ? __( 'Product Input Field', 'woocommerce-jetpack' ) . ' #' . $i : '',
				'desc'     => $option['title'],
				'desc_tip' => ( isset( $option['desc_tip'] ) ) ? $option['desc_tip'] : '',
				'id'       => $option['id'] . $i,
				'default'  => $option['default'],
				'type'     => $option['type'],
				'options'  => isset( $option['options'] ) ? $option['options'] : '',
				'css'      => 'width:30%;min-width:300px;',
			),
		) );
	}
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_input_fields_global_options',
	),
	array(
		'title'    => __( 'Frontend View Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_input_fields_frontend_view_options',
	),
	array(
		'title'    => __( 'HTML Template - Start', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_start_template',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'title'    => __( 'HTML Template - Each Field', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_field_template',
		'default'  => '<p><label for="%field_id%">%field_title%</label> %field_html%</p>',
		'type'     => 'custom_textarea',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'title'    => __( 'HTML Template - End', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_end_template',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'title'    => __( 'HTML to add after required field title', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_frontend_view_required_html',
		'default'  => '&nbsp;<abbr class="required" title="required">*</abbr>',
		'type'     => 'custom_textarea',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'title'    => __( 'Cart Display Options', 'woocommerce-jetpack' ),
		'desc'     => __( 'When "Add to cart item data" is selected, "Cart HTML Template" options below will be ignored.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_display_options',
		'default'  => 'name',
		'type'     => 'select',
		'options'  => array(
			'name' => __( 'Add to cart item name', 'woocommerce-jetpack' ),
			'data' => __( 'Add to cart item data', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Cart HTML Template - Start', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_cart_start_template',
		'default'  => '<dl style="font-size:smaller;">',
		'type'     => 'custom_textarea',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'title'    => __( 'Cart HTML Template - Each Field', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_cart_field_template',
		'default'  => '<dt>%title%</dt><dd>%value%</dd>',
		'type'     => 'custom_textarea',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'title'    => __( 'Cart HTML Template - End', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_cart_end_template',
		'default'  => '</dl>',
		'type'     => 'custom_textarea',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'title'    => __( 'Order Table Template - Each Field', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Affects Order received page, Emails and Admin Orders View', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_frontend_view_order_table_format',
		'default'  => '&nbsp;| %title% %value%',
		'type'     => 'custom_textarea',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_input_fields_frontend_view_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Emails Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_input_fields_emails_options',
	),
	array(
		'title'    => __( 'Attach Files to Admin\'s New Order Emails', 'woocommerce-jetpack' ),
		'desc'     => __( 'Attach', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_attach_to_admin_new_order',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Attach Files to Customer\'s Processing Order Emails', 'woocommerce-jetpack' ),
		'desc'     => __( 'Attach', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_attach_to_customer_processing_order',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_input_fields_emails_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Admin Order View Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_input_fields_admin_view_options',
	),
	array(
		'title'    => __( 'Replace Field ID with Field Label', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_input_fields_make_nicer_name_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_input_fields_admin_view_options',
	),
) );
return $settings;

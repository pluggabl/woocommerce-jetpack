<?php
/**
 * Booster for WooCommerce - Settings - My Account
 *
 * @version 3.7.1
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_my_account_options',
	),
	array(
		'title'    => __( 'Add Order Status Actions', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Let your customers change order status manually.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_my_account_add_order_status_actions',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => wcj_get_order_statuses(),
	),
	array(
		'title'    => __( 'Add User Role Selection to Registration Form', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Let your customers choose their user role manually.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_my_account_registration_extra_fields_user_role_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc'     => __( 'Default user role', 'woocommerce-jetpack' ),
		'id'       => 'wcj_my_account_registration_extra_fields_user_role_default',
		'default'  => 'customer',
		'type'     => 'select',
		'options'  => wcj_get_user_roles_options(),
	),
	array(
		'desc'     => __( 'User roles options', 'woocommerce-jetpack' ),
		'id'       => 'wcj_my_account_registration_extra_fields_user_role_options',
		'default'  => array( 'customer' ),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => wcj_get_user_roles_options(),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_my_account_options',
	),
);
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Menu & Endpoints Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_my_account_menu_options',
	),
	array(
		'title'    => __( 'Customize Menu & Endpoints', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_my_account_menu_customize_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
) );
foreach ( array_merge( $this->account_menu_items, $this->account_menu_endpoints ) as $account_menu_item_id => $account_menu_item_title ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $account_menu_item_title,
			'desc_tip' => __( 'Sets title.', 'woocommerce-jetpack' ) . ' ' . __( 'Set empty, to use the default title.', 'woocommerce-jetpack' ),
			'id'       => "wcj_my_account_menu_title[{$account_menu_item_id}]",
			'default'  => '',
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Customize Menu Order', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_my_account_menu_order_customize_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc_tip' => sprintf( __( 'Default: %s', 'woocommerce-jetpack' ), '<br><em>' . str_replace( PHP_EOL, '<br>', $this->menu_order_default ) . '</em>' ),
		'id'       => 'wcj_my_account_menu_order',
		'default'  => $this->menu_order_default,
		'type'     => 'textarea',
		'css'      => 'height:200px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_my_account_menu_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Custom Info Blocks', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_my_account_custom_info_options',
	),
	array(
		'title'    => __( 'Custom Info Blocks', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_my_account_custom_info_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Total Blocks', 'woocommerce-jetpack' ),
		'id'       => 'wcj_my_account_custom_info_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_my_account_custom_info_options',
	),
) );
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_my_account_custom_info_total_number', 1 ) ); $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Info Block', 'woocommerce-jetpack' ) . ' #' . $i,
			'type'     => 'title',
			'id'       => 'wcj_my_account_custom_info_options_' . $i,
		),
		array(
			'title'    => __( 'Content', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_my_account_custom_info_content_' . $i,
			'default'  => '',
			'type'     => 'custom_textarea',
			'css'      => 'width:100%;height:100px;',
		),
		array(
			'title'    => __( 'Position', 'woocommerce-jetpack' ),
			'id'       => 'wcj_my_account_custom_info_hook_' . $i,
			'default'  => 'woocommerce_account_dashboard',
			'type'     => 'select',
			'options'  => array(
				'woocommerce_account_content'                  => __( 'Account content',  'woocommerce-jetpack' ),
				'woocommerce_account_dashboard'                => __( 'Account dashboard',  'woocommerce-jetpack' ),
				'woocommerce_account_navigation'               => __( 'Account navigation',  'woocommerce-jetpack' ),
				'woocommerce_after_account_downloads'          => __( 'After account downloads',  'woocommerce-jetpack' ),
				'woocommerce_after_account_navigation'         => __( 'After account navigation',  'woocommerce-jetpack' ),
				'woocommerce_after_account_orders'             => __( 'After account orders',  'woocommerce-jetpack' ),
				'woocommerce_after_account_payment_methods'    => __( 'After account payment methods',  'woocommerce-jetpack' ),
				'woocommerce_after_available_downloads'        => __( 'After available downloads',  'woocommerce-jetpack' ),
				'woocommerce_after_customer_login_form'        => __( 'After customer login form',  'woocommerce-jetpack' ),
				'woocommerce_after_edit_account_address_form'  => __( 'After edit account address form',  'woocommerce-jetpack' ),
				'woocommerce_after_edit_account_form'          => __( 'After edit account form',  'woocommerce-jetpack' ),
				'woocommerce_after_my_account'                 => __( 'After my account',  'woocommerce-jetpack' ),
				'woocommerce_available_download_end'           => __( 'Available download end',  'woocommerce-jetpack' ),
				'woocommerce_available_download_start'         => __( 'Available download start',  'woocommerce-jetpack' ),
				'woocommerce_available_downloads'              => __( 'Available downloads',  'woocommerce-jetpack' ),
				'woocommerce_before_account_downloads'         => __( 'Before account downloads',  'woocommerce-jetpack' ),
				'woocommerce_before_account_navigation'        => __( 'Before account navigation',  'woocommerce-jetpack' ),
				'woocommerce_before_account_orders'            => __( 'Before account orders',  'woocommerce-jetpack' ),
				'woocommerce_before_account_orders_pagination' => __( 'Before account orders pagination',  'woocommerce-jetpack' ),
				'woocommerce_before_account_payment_methods'   => __( 'Before account payment methods',  'woocommerce-jetpack' ),
				'woocommerce_before_available_downloads'       => __( 'Before Available downloads',  'woocommerce-jetpack' ),
				'woocommerce_before_customer_login_form'       => __( 'Before customer login form',  'woocommerce-jetpack' ),
				'woocommerce_before_edit_account_address_form' => __( 'Before edit account address form',  'woocommerce-jetpack' ),
				'woocommerce_before_edit_account_form'         => __( 'Before edit account form',  'woocommerce-jetpack' ),
				'woocommerce_before_my_account'                => __( 'Before my account',  'woocommerce-jetpack' ),
				'woocommerce_edit_account_form'                => __( 'Edit account form',  'woocommerce-jetpack' ),
				'woocommerce_edit_account_form_end'            => __( 'Edit account form end',  'woocommerce-jetpack' ),
				'woocommerce_edit_account_form_start'          => __( 'Edit account form start',  'woocommerce-jetpack' ),
				'woocommerce_login_form'                       => __( 'Login form',  'woocommerce-jetpack' ),
				'woocommerce_login_form_end'                   => __( 'Login form end',  'woocommerce-jetpack' ),
				'woocommerce_login_form_start'                 => __( 'Login form start',  'woocommerce-jetpack' ),
				'woocommerce_lostpassword_form'                => __( 'Lost password form',  'woocommerce-jetpack' ),
				'woocommerce_register_form'                    => __( 'Register form',  'woocommerce-jetpack' ),
				'woocommerce_register_form_end'                => __( 'Register form end',  'woocommerce-jetpack' ),
				'woocommerce_register_form_start'              => __( 'Register form start',  'woocommerce-jetpack' ),
				'woocommerce_resetpassword_form'               => __( 'Reset password form',  'woocommerce-jetpack' ),
				'woocommerce_view_order'                       => __( 'View order',  'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Position Order (i.e. Priority)', 'woocommerce-jetpack' ),
			'id'       => 'wcj_my_account_custom_info_priority_' . $i,
			'default'  => 10,
			'type'     => 'number',
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_my_account_custom_info_options_' . $i,
		),
	) );
}
return $settings;

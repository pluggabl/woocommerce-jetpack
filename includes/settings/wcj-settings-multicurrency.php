<?php
/**
 * Booster for WooCommerce - Settings - Multicurrency (Currency Switcher)
 *
 * @version 3.1.3
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    "pretty prices"
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$currency_from  = get_woocommerce_currency();
$all_currencies = wcj_get_currencies_names_and_symbols();
$settings = array(
	array(
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
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
		'desc'     => ( '' == apply_filters( 'booster_get_message', '', 'desc' ) ) ?
			__( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
			:
			apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
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
		'title'    => __( 'Rounding', 'woocommerce-jetpack' ),
		'desc'     => __( 'If using exchange rates, choose rounding here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_multicurrency_rounding',
		'default'  => 'no_round',
		'type'     => 'select',
		'options'  => array(
			'no_round'   => __( 'No rounding', 'woocommerce-jetpack' ),
			'round'      => __( 'Round', 'woocommerce-jetpack' ),
			'round_up'   => __( 'Round up', 'woocommerce-jetpack' ),
			'round_down' => __( 'Round down', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Rounding Precision', 'woocommerce-jetpack' ),
		'desc'     => __( 'If rounding enabled, set precision here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_multicurrency_rounding_precision',
		'default'  => absint( get_option( 'woocommerce_price_num_decimals', 2 ) ),
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Currency Switcher Template', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%currency_name%', '%currency_symbol%', '%currency_code%' ) ),
		'id'       => 'wcj_multicurrency_switcher_template',
		'default'  => '%currency_name% (%currency_symbol%)',
		'type'     => 'text',
		'class'    => 'widefat',
	),
	array(
		'title'    => __( 'Advanced: Additional Price Filters', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Add additional price filters here. One per line. Leave blank if not sure.' ),
		'desc'     => sprintf( __( 'E.g.: %s' ), '<code>' . 'woocommerce_subscriptions_product_price' . '</code>' . ', ' .'<code>' . 'woocommerce_get_price' . '</code>' . '.' ),
		'id'       => 'wcj_multicurrency_switcher_additional_price_filters',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'min-width:300px;height:150px;',
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
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '2', )
		),
	),
);
$total_number = apply_filters( 'booster_get_option', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
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
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_multicurrency_currencies_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Role Defaults', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => sprintf( __( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' ) ),
		'id'       => 'wcj_multicurrency_role_defaults_options',
	),
	array(
		'title'    => __( 'Roles', 'woocommerce-jetpack' ),
		'desc'     => __( 'Save settings after you change this option. Leave blank to disable.', 'woocommerce-jetpack' ),
		'type'     => 'multiselect',
		'id'       => 'wcj_multicurrency_role_defaults_roles',
		'default'  => '',
		'class'    => 'chosen_select',
		'options'  => wcj_get_user_roles_options(),
	),
) );
$module_currencies = array();
for ( $i = 1; $i <= $total_number; $i++ ) {
	$currency_code = get_option( 'wcj_multicurrency_currency_' . $i, $currency_from );
	$module_currencies[ $currency_code ] = $all_currencies[ $currency_code ];
}
$module_currencies = array_unique( $module_currencies );
$module_roles = get_option( 'wcj_multicurrency_role_defaults_roles', '' );
if ( ! empty( $module_roles ) ) {
	foreach ( $module_roles as $role_key ) { // wcj_get_user_roles() as $role_key => $role_data
		$settings = array_merge( $settings, array(
			array(
				'title'    => $role_key, // $role_data['name'],
				'id'       => 'wcj_multicurrency_role_defaults_' . $role_key,
				'default'  => '',
				'type'     => 'select',
				'options'  => array_merge( array( '' => __( 'No default currency', 'woocommerce-jetpack' ) ), $module_currencies ),
			),
		) );
	}
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_multicurrency_role_defaults_options',
	),
) );
return $settings;

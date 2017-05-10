<?php
/**
 * Booster for WooCommerce - Settings - Custom Gateways
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$wocommerce_checkout_settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout' );
$wocommerce_checkout_settings_url = '<a href="' . $wocommerce_checkout_settings_url . '">' . __( 'WooCommerce > Settings > Checkout', 'woocommerce-jetpack' ) . '</a>';
$settings = array(
	array(
		'title'    => __( 'Custom Payment Gateways Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_custom_payment_gateways_options',
		'desc'     => __( 'This section lets you set number of custom payment gateways to add.', 'woocommerce-jetpack' )
			. ' ' . sprintf( __( 'After setting the number, visit %s to set each gateway options.', 'woocommerce-jetpack' ), $wocommerce_checkout_settings_url ),
	),
	array(
		'title'    => __( 'Number of Gateways', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'desc_tip' => __( 'Number of custom payments gateways to be added. All settings for each new gateway are in WooCommerce > Settings > Checkout.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_payment_gateways_number',
		'default'  => 1,
		'type'     => 'number',
		'custom_attributes' => is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ?
			apply_filters( 'booster_get_message', '', 'readonly' ) : array( 'step' => '1', 'min' => '1' ),
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_payment_gateways_number', 1 ) ); $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Admin Title Custom Gateway', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_custom_payment_gateways_admin_title_' . $i,
			'default'  => __( 'Custom Gateway', 'woocommerce-jetpack' ) . ' #' . $i,
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_custom_payment_gateways_options',
	),
) );
return $settings;

<?php
/**
 * Booster for WooCommerce - Settings - Custom Emails
 *
 * @version 2.9.1
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Custom Emails', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_emails_custom_emails_options',
		'desc'     => sprintf(
			__( 'This section lets you set number of custom emails to add. After setting the number, visit <a href="%s">WooCommerce > Settings > Emails</a> to set each email options.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=email' )
		),
	),
	array(
		'title'    => __( 'Custom Emails Number', 'woocommerce-jetpack' ),
		'id'       => 'wcj_emails_custom_emails_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
);
$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Admin Title Custom Email', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_emails_custom_emails_admin_title_' . $i,
			'default'  => __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i,
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_emails_custom_emails_options',
	),
) );
return $settings;

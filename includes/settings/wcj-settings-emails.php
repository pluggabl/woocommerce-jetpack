<?php
/**
 * Booster for WooCommerce - Settings - Custom Emails
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings     = array(
	array(
		'id'   => 'emails_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'emails_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'emails_custom_emails_tab' => __( 'Custom Emails', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'emails_custom_emails_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Custom Emails', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_emails_custom_emails_options',
		'desc'  => sprintf(
						/* translators: %s: translators Added */
			__( 'This section lets you set number of custom emails to add. After setting the number, visit <a href="%s">WooCommerce > Settings > Emails</a> to set each email options.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=email' )
		),
	),
	array(
		'title'             => __( 'Custom Emails Number', 'woocommerce-jetpack' ),
		'id'                => 'wcj_emails_custom_emails_total_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_emails_custom_emails_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'Admin Title Custom Email', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'      => 'wcj_emails_custom_emails_admin_title_' . $i,
				'default' => __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'    => 'text',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_emails_custom_emails_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'emails_custom_emails_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;

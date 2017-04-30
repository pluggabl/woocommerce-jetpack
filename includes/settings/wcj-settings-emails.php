<?php
/**
 * WooCommerce Jetpack Settings - Emails
 *
 * @version 2.7.2
 * @since   2.7.2
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
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
);
$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) );
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
	array(
		'title'    => __( 'Product Info in Item Name', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_info_in_email_order_item_name_options',
	),
	array(
		'title'    => __( 'Add Product Info to Item Name', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_product_info_in_email_order_item_name_enabled',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Info', 'woocommerce-jetpack' ),
		'desc'     => sprintf( __( 'You can use <a target="_blank" href="%s">Booster\'s products shortcodes</a> here.', 'woocommerce-jetpack' ), 'http://booster.io/category/shortcodes/products-shortcodes/' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_product_info_in_email_order_item_name',
		'default'  => '[wcj_product_categories strip_tags="yes" before="<hr><em>" after="</em>"]',
		'css'      => 'width:66%;min-width:300px;height:150px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_info_in_email_order_item_name_options',
	),
	array(
		'title'    => __( 'Email Verification', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_emails_verification_options',
	),
	array(
		'title'    => __( 'Enable Email Verification', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
		'type'     => 'checkbox',
		'id'       => 'wcj_emails_verification_enabled',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Redirect to "My Account" Page After Successful Verification', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_emails_verification_redirect_on_success',
		'default'  => 'yes',
	),
	array(
		'title'    => __( 'Verification Email Subject', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_emails_verification_email_subject',
		'default'  => __( 'Please activate your account', 'woocommerce-jetpack' ),
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Verification Email Content', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_emails_verification_email_content',
		'default'  => __( 'Please click the following link to verify your email:<br><br><a href="%verification_url%">%verification_url%</a>', 'woocommerce-jetpack' ),
		'css'      => 'width:66%;min-width:300px;height:150px;',
	),
	array(
		'title'    => __( 'Verification Message - Success', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_emails_verification_success_message',
		'default'  => __( '<strong>Success:</strong> Your account has been activated!', 'woocommerce-jetpack' ),
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Verification Message - Error', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_emails_verification_error_message',
		'default'  => __( 'Your account has to be activated before you can login. You can resend email with verification link by clicking <a href="%resend_verification_url%">here</a>.', 'woocommerce-jetpack' ),
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Verification Message - Failed', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_emails_verification_failed_message',
		'default'  => __( '<strong>Error:</strong> Activation failed, please contact our administrator.', 'woocommerce-jetpack' ),
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Verification Message - Activate', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_emails_verification_activation_message',
		'default'  => __( 'Thank you for your registration. Your account has to be activated before you can login. Please check your email.', 'woocommerce-jetpack' ),
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Verification Message - Resend', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_emails_verification_email_resend_message',
		'default'  => __( '<strong>Success:</strong> Your activation email has been resend. Please check your email.', 'woocommerce-jetpack' ),
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_emails_verification_options',
	),
) );
$settings = array_merge( $settings, $this->get_emails_forwarding_settings() );
return $settings;

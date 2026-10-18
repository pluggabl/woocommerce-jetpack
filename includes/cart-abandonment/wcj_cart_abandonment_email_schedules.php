<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Booster Elite for WooCommerce - Module - Cart Abandonment Orders
 *
 * @version 7.2.7
 * @author  Pluggabl LLC.
 * @package Booster_Elite_For_WooCommerce/includes
 */

global $wpdb;
$from_name    = wcj_get_option( 'wcj_cart_abandonment_email_from_name' );
$from_email   = wcj_get_option( 'wcj_cart_abandonment_email_from_email' );
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_ca_email_template_total_number', 1 ) );
$templates    = array();
for ( $i = 1; $i <= $total_number; $i++ ) {
	$template_id = $i;
	$is_enable   = wcj_get_option( 'wcj_ca_email_template_enabled_' . $i );
	if ( 'yes' === $is_enable ) {
		$subject         = wcj_get_option( 'wcj_ca_email_template_subject_' . $i );
		$message         = wcj_get_option( 'wcj_ca_email_template_body_' . $i );
		$time            = wcj_get_option( 'wcj_ca_email_trigger_time_' . $i );
		$time_type       = wcj_get_option( 'wcj_ca_email_trigger_time_type_' . $i );
		$discount_type   = wcj_get_option( 'wcj_ca_email_discount_type_' . $i );
		$discount_amount = wcj_get_option( 'wcj_ca_email_discount_amount_' . $i );
		$auto_apply      = wcj_get_option( 'wcj_ca_auto_apply_coupon_' . $i );

		$email_trigger = '+' . $time . ' ' . $time_type;
		$ems           = $this->wcj_cart_abandonment_mail_schedule_data( $email_trigger, $template_id );

		if ( $ems ) {
			foreach ( $ems as $em ) {
				$user_details = unserialize( $em['checkout_data'] ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
				$token_data   = array( 'wcj_ca_session_id' => $em['session_id'] );
				$token        = rawurlencode( base64_encode( http_build_query( (array) $token_data ) ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				$checkout_url = get_permalink( $em['checkout_id'] ) . '?wcj_restore_ac_token=' . $token;
				$checkout_url = esc_url( $checkout_url );
				$coupon_code  = '';
				if ( 'no' !== $discount_type ) {
					$coupon_code = $this->wcj_generate_new_coupon_code( $discount_type, $discount_amount );
				}

				$user_details_billing_first_name = isset( $user_details['billing_first_name'] ) ? $user_details['billing_first_name'] : '';
				$user_details_billing_last_name  = isset( $user_details['billing_last_name'] ) ? $user_details['billing_last_name'] : '';

				$replaced_values = array(
					'%coupon_code%'   => $coupon_code,
					'%checkout_link%' => $checkout_url,
					'%customer_name%' => $user_details_billing_first_name . ' ' . $user_details_billing_last_name,
					'%admin_email%'   => 'admin@test.com',
				);

				$email_content = str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), $message );

				$headers[]    = 'Content-Type: text/html' . "\r\n";
				$headers[]    = 'From: ' . $from_name . ' <' . $from_email . '>';
				$to           = $em['email'];
				$email_status = 0;
				if ( wp_mail( $to, $subject, $email_content, $headers ) ) {
					$email_status = 1;
				}
				$scheduled_email_table = $wpdb->prefix . 'wcj_abandonment_email_history';
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->insert(
					$scheduled_email_table,
					array(
						'session_id'  => $em['session_id'],
						'email'       => $em['email'],
						'template_id' => $template_id,
						'coupon_code' => $coupon_code,
						'status'      => $email_status,
						'time'        => wcj_get_date_from_gmt( 'Y-m-d H:i:s' ),
					)
				);

				if ( 'yes' === $auto_apply ) {
					$session_id             = $em['session_id'];
					$cart_abandonment_table = $wpdb->prefix . 'wcj_cart_abandonment_data';
					$wpdb->query( "UPDATE $cart_abandonment_table SET coupon_code='$coupon_code' WHERE session_id='$session_id'" );
				}
				// phpcs:enable
			}
		}
	}
}


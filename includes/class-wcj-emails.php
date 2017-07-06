<?php
/**
 * Booster for WooCommerce - Module - Emails
 *
 * @version 2.9.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Emails' ) ) :

class WCJ_Emails extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.1
	 */
	function __construct() {

		$this->id         = 'emails';
		$this->short_desc = __( 'Custom Emails', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom emails to WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-custom-emails';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Custom Emails
			add_filter( 'woocommerce_email_actions',                 array( $this, 'add_custom_woocommerce_email_actions' ) );
			add_filter( 'woocommerce_email_classes',                 array( $this, 'add_custom_emails_to_wc' ) );
			add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'add_custom_emails_to_wc_resend_order_emails' ) );
		}
	}

	/**
	 * add_custom_woocommerce_email_actions.
	 *
	 * @version 2.9.1
	 * @since   2.4.5
	 */
	function add_custom_woocommerce_email_actions( $email_actions ) {
		$email_actions[] = 'woocommerce_checkout_order_processed';
		$order_statuses = wcj_get_order_statuses_v2();
		foreach ( $order_statuses as $slug => $name ) {
			$email_actions[] = 'woocommerce_order_status_' . $slug;
			foreach ( $order_statuses as $slug2 => $name2 ) {
				if ( $slug != $slug2 ) {
					$email_actions[] = 'woocommerce_order_status_' . $slug . '_to_' . $slug2;
				}
			}
		}
		return $email_actions;
	}

	/**
	 * add_custom_emails_to_wc_resend_order_emails.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function add_custom_emails_to_wc_resend_order_emails( $emails ) {
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
			$emails[] =  'wcj_custom' . '_' . $i;
		}
		return $emails;
	}

	/**
	 * add_custom_emails_to_wc.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function add_custom_emails_to_wc( $emails ) {
		if ( ! class_exists( 'WC_Email_WCJ_Custom' ) ) {
			require_once( 'emails/class-wc-email-wcj-custom.php' );
		}
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
			$emails[ 'WC_Email_WCJ_Custom_' . $i ] = new WC_Email_WCJ_Custom( $i );
		}
		return $emails;
	}

}

endif;

return new WCJ_Emails();

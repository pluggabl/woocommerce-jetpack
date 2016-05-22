<?php
/**
 * WooCommerce Jetpack Emails
 *
 * The WooCommerce Jetpack Emails class.
 *
 * @version 2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Emails' ) ) :

class WCJ_Emails extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	public function __construct() {

		$this->id         = 'emails';
		$this->short_desc = __( 'Emails', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom emails. Add another email recipient(s) to all WooCommerce emails.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-emails/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_email_actions', array( $this, 'add_custom_woocommerce_email_actions' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'add_custom_emails_to_wc' ) );
			add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'add_custom_emails_to_wc_resend_order_emails' ) );
			if ( '' != get_option( 'wcj_emails_bcc_email' ) ) {
				add_filter( 'woocommerce_email_headers', array( $this, 'add_bcc_email' ) );
			}
			if ( '' != get_option( 'wcj_emails_cc_email' ) ) {
				add_filter( 'woocommerce_email_headers', array( $this, 'add_cc_email' ) );
			}
//			add_action( 'woocommerce_email_after_order_table', array( $this, 'add_payment_method_to_new_order_email' ), 15, 2 );
			// Settings
			add_filter( 'woocommerce_email_settings', array( $this, 'add_email_forwarding_fields_to_wc_standard_settings' ), 100 );
		}
	}

	/**
	 * get_order_statuses.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_order_statuses() {
		$result = array();
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		foreach( $statuses as $status => $status_name ) {
			$result[ substr( $status, 3 ) ] = $statuses[ $status ];
		}
		return $result;
	}

	/**
	 * add_custom_woocommerce_email_actions.
	 *
	 * @version 2.4.8
	 * @since   2.4.5
	 */
	function add_custom_woocommerce_email_actions( $email_actions ) {

		$email_actions[] = 'woocommerce_new_order';

		$order_statuses = $this->get_order_statuses();
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
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
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
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
			$emails[ 'WC_Email_WCJ_Custom_' . $i ] = new WC_Email_WCJ_Custom( $i );
		}
		return $emails;
	}

	 /**
	 * Add another email recipient to all WooCommerce emails
	 */
	function add_bcc_email( $email_headers ) {
		return $email_headers . "Bcc: " . get_option( 'wcj_emails_bcc_email' ) . "\r\n";
	}

	 /**
	 * Add another email recipient to all WooCommerce emails
	 */
	function add_cc_email( $email_headers ) {
		return $email_headers . "Cc: " . get_option( 'wcj_emails_cc_email' ) . "\r\n";
	}

	/**
	 * get_emails_forwarding_settings.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function get_emails_forwarding_settings( $extended_title = false ) {
		return array(
			array(
				'title'    => ( $extended_title ) ?
					__( 'Booster: Email Forwarding Options', 'woocommerce-jetpack' ) :
					__( 'Email Forwarding Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you add another email recipient(s) to all WooCommerce emails. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_forwarding_options',
			),
			array(
				'title'    => __( 'Cc Email', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Cc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_cc_email',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Bcc Email', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Bcc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_bcc_email',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_emails_forwarding_options',
			),
		);
	}

	/**
	 * add_email_forwarding_fields_to_wc_standard_settings.
	 *
	 * @version 2.3.9
	 */
	function add_email_forwarding_fields_to_wc_standard_settings( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'email_template_options' == $section['id'] && isset( $section['type'] ) && 'title' == $section['type'] ) {
				$updated_settings = array_merge( $updated_settings, $this->get_emails_forwarding_settings( true ) );
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Custom Emails', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_emails_custom_emails_options',
				'desc'     => __( 'This section lets you set number of custom emails to add. After setting the number, visit "WooCommerce > Settings > Emails" to set each email options.', 'woocommerce-jetpack' ),
			),
			array(
				'title'    => __( 'Custom Emails Number', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_custom_emails_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
		);
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings [] = array(
				'title'    => __( 'Admin Title Custom Email', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'       => 'wcj_emails_custom_emails_admin_title_' . $i,
				'default'  => __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'     => 'text',
			);
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_emails_custom_emails_options',
			),
		) );
		$settings = array_merge( $settings, $this->get_emails_forwarding_settings() );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Emails();

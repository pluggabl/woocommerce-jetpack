<?php
/**
 * WooCommerce Jetpack Emails
 *
 * The WooCommerce Jetpack Emails class.
 *
 * @version 2.3.10
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Emails' ) ) :

class WCJ_Emails extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.9
	 */
	public function __construct() {

		$this->id         = 'emails';
		$this->short_desc = __( 'Emails', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom emails. Add another email recipient(s) to all WooCommerce emails.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
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
	 * @version 2.3.10
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
				'custom_attributes'
				           => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_emails_custom_emails_options',
			),
		);
		$settings = array_merge( $settings, $this->get_emails_forwarding_settings() );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Emails();

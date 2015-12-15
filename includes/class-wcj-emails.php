<?php
/**
 * WooCommerce Jetpack Emails
 *
 * The WooCommerce Jetpack Emails class.
 *
 * @version 2.3.9
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
		$this->desc       = __( 'Add another email recipient(s) to all WooCommerce emails.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
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
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Cc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_cc_email',
				'default'  => '',
				'type'     => 'text',
				'custom_attributes'
				           => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'title'    => __( 'Bcc Email', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Bcc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_bcc_email',
				'default'  => '',
				'type'     => 'text',
				'custom_attributes'
				           => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
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
	 * @version 2.3.9
	 */
	function get_settings() {
		$settings = array();
		$settings = array_merge( $settings, $this->get_emails_forwarding_settings() );
		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Emails();

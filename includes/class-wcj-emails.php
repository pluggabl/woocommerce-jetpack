<?php
/**
 * Booster for WooCommerce - Module - Emails
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Emails' ) ) :

class WCJ_Emails extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'emails';
		$this->short_desc = __( 'Emails', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom emails. Add another email recipient(s) to all WooCommerce emails.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-emails';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Custom Emails
			add_filter( 'woocommerce_email_actions', array( $this, 'add_custom_woocommerce_email_actions' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'add_custom_emails_to_wc' ) );
			add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'add_custom_emails_to_wc_resend_order_emails' ) );
			// Email Forwarding
			if ( '' != get_option( 'wcj_emails_bcc_email' ) ) {
				add_filter( 'woocommerce_email_headers', array( $this, 'add_bcc_email' ) );
			}
			if ( '' != get_option( 'wcj_emails_cc_email' ) ) {
				add_filter( 'woocommerce_email_headers', array( $this, 'add_cc_email' ) );
			}
			// Product Info
			if ( 'yes' === get_option( 'wcj_product_info_in_email_order_item_name_enabled', 'no' ) ) {
				add_filter( 'woocommerce_order_item_name', array( $this, 'add_product_info_to_email_order_item_name' ), PHP_INT_MAX, 2 );
			}
			// Settings
			add_filter( 'woocommerce_email_settings', array( $this, 'add_email_forwarding_fields_to_wc_standard_settings' ), PHP_INT_MAX );
		}
	}

	/**
	 * add_product_info_to_email_order_item_name.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function add_product_info_to_email_order_item_name( $item_name, $item ) {
		if ( $item['product_id'] ) {
			global $post;
			$post = get_post( $item['product_id'] );
			setup_postdata( $post );
			$item_name .= do_shortcode( get_option( 'wcj_product_info_in_email_order_item_name', '[wcj_product_categories strip_tags="yes" before="<hr><em>" after="</em>"]' ) );
			wp_reset_postdata();
		}
		return $item_name;
	}

	/**
	 * get_order_statuses.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 * @todo    use global wcj function
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
	 * @version 2.5.5
	 * @since   2.4.5
	 */
	function add_custom_woocommerce_email_actions( $email_actions ) {
		$email_actions[] = 'woocommerce_checkout_order_processed';
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
	 * @todo    (maybe) remove this completely (and then move `get_emails_forwarding_settings()` to settings file)
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

}

endif;

return new WCJ_Emails();

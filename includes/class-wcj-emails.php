<?php
/**
 * Booster for WooCommerce - Module - Custom Emails
 *
 * @version 5.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Emails' ) ) :

class WCJ_Emails extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 */
	function __construct() {

		$this->id         = 'emails';
		$this->short_desc = __( 'Custom Emails', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom emails to WooCommerce (1 custom email allowed in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Add custom emails to WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-custom-emails';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_email_actions',                 array( $this, 'add_custom_woocommerce_email_actions' ) );
			add_filter( 'woocommerce_email_classes',                 array( $this, 'add_custom_emails_to_wc' ) );
			add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'add_custom_emails_to_wc_resend_order_emails' ) );
			if ( ! WCJ_IS_WC_VERSION_BELOW_3_2_0 ) {
				add_filter( 'woocommerce_order_actions', array( $this, 'add_custom_emails_order_actions' ), PHP_INT_MAX, 1 );
				for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
					add_action( 'woocommerce_order_action_' . 'wcj_send_email_custom' . '_' . $i,
						array( $this, 'do_custom_emails_order_actions' ), PHP_INT_MAX, 1 );
				}
			}
		}
	}

	/**
	 * do_custom_emails_order_actions.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function do_custom_emails_order_actions( $order ) {
		$booster_action_prefix = 'woocommerce_order_action_' . 'wcj_send_email_custom' . '_';
		$_current_filter       = current_filter();
		if ( substr( $_current_filter, 0, strlen( $booster_action_prefix ) ) === $booster_action_prefix ) {
			$email_nr = substr( $_current_filter, strlen( $booster_action_prefix ) );
			WC()->payment_gateways();
			WC()->shipping();
			WC()->mailer()->emails[ 'WC_Email_WCJ_Custom_' . $email_nr ]->trigger( $order->get_id(), $order );
			$order->add_order_note(
				sprintf( __( 'Booster: Emails: %s manually sent.', 'woocommerce-jetpack' ),
					get_option( 'wcj_emails_custom_emails_admin_title_' . $email_nr, __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $email_nr ) ),
				false,
				true
			);
		}
	}

	/**
	 * add_custom_emails_order_actions.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 * @todo    (maybe) add "Add Custom Email(s) to Order Actions" option (in WC >= 3.2.0); same to `woocommerce_order_action_`
	 */
	function add_custom_emails_order_actions( $actions ) {
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
			$actions[ 'wcj_send_email_custom' . '_' . $i ] = sprintf( apply_filters( 'wcj_emails_custom_emails_order_action_text',
				__( 'Booster: Send Email: %s', 'woocommerce-jetpack' ), $i ),
					get_option( 'wcj_emails_custom_emails_admin_title_' . $i, __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i )
			);
		}
		return $actions;
	}

	/**
	 * add_custom_woocommerce_email_actions.
	 *
	 * @version 2.9.1
	 * @since   2.4.5
	 */
	function add_custom_woocommerce_email_actions( $email_actions ) {
		$email_actions[] = 'woocommerce_checkout_order_processed';
		$order_statuses = wcj_get_order_statuses();
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
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
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
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
			$emails[ 'WC_Email_WCJ_Custom_' . $i ] = new WC_Email_WCJ_Custom( $i );
		}
		return $emails;
	}

}

endif;

return new WCJ_Emails();

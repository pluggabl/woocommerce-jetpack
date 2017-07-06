<?php
/**
 * Booster for WooCommerce - Module - Email Options
 *
 * @version 2.9.1
 * @since   2.9.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Email_Options' ) ) :

class WCJ_Email_Options extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function __construct() {

		$this->id         = 'email_options';
		$this->short_desc = __( 'Email Options', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce email options.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-email-options';
		parent::__construct();

		if ( $this->is_enabled() ) {

		}
	}

}

endif;

return new WCJ_Email_Options();

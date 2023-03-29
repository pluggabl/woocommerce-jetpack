<?php
/**
 * Booster for WooCommerce - Module - Gateways PDF notes
 *
 * @version 6.0.5
 * @since   1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Payment_Gateways_Pdf_Notes' ) ) :
	/**
	 * WCJ_Payment_Gateways_Pdf_Notes.
	 */
	class WCJ_Payment_Gateways_Pdf_Notes extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 6.0.5
		 */
		public function __construct() {

			$this->id         = 'payment_gateways_pdf_notes';
			$this->short_desc = __( 'Gateways PDF Notes', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add PDF notes for various gateways', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-payment-gateways-pdf-notes';
			$this->extra_desc = sprintf(
				/* translators: %s: translation added */
				__( 'After setting Notes in the Gateways PDF Notes Options section below, you can use the below shortcode to the display notes: %s', 'woocommerce-jetpack' ),
				'<ol>' .
				'<li>' . sprintf(
								/* translators: %s: translation added */
					__( '<strong>Shortcodes:</strong> %s', 'woocommerce-jetpack' ),
					'<code>[wcj_order_payment_method_notes]</code>'
				) .
				'</li>' .
				'</ol>'
			);
			parent::__construct();

		}


	}

endif;

return new WCJ_Payment_Gateways_Pdf_Notes();

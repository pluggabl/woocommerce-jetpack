<?php
/**
 * WooCommerce Jetpack Shortcodes Module
 *
 * The WooCommerce Jetpack Shortcodes Module class.
 *
 * @version 2.3.12
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Shortcodes_Module' ) ) :

class WCJ_Shortcodes_Module extends WCJ_Module {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'shortcodes';
		$this->short_desc = __( 'Shortcodes', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster\'s shortcodes.', 'woocommerce-jetpack' );
		parent::__construct();
	}

}

endif;

return new WCJ_Shortcodes_Module();

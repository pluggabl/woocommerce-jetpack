<?php
/**
 * Booster for WooCommerce - Dummy Term
 *
 * @version 5.6.8
 * @since   2.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Dummy_Term' ) ) {
	/**
	 * WCJ_Dummy_Term class.
	 *
	 * @version 5.6.8
	 * @since   2.6.0
	 */
	class WCJ_Dummy_Term {
		/**
		 * Term_id class.
		 *
		 * @var term_id.
		 */

		public $term_id;
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->term_id = 0;
		}
	}
}

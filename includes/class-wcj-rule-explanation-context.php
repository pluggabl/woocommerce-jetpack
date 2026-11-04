<?php
/**
 * Booster for WooCommerce - explicit merchant fee scenario context.
 *
 * @version 9.0.0
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

if ( ! class_exists( 'WCJ_Rule_Explanation_Context' ) ) :

	/** Read-only getter adapter for merchant-supplied facts. Not a WooCommerce cart. */
	class WCJ_Rule_Explanation_Context {
		/** Explicit merchant facts, never persisted.
		 *
		 * @var array
		 */
		private $facts;
		/** Constructs a getter-only context.
		 *
		 * @param array $facts Validated merchant facts.
		 */
		public function __construct( $facts ) {
			$this->facts = $facts; }
		/** Returns the merchandise total. */
		public function get_cart_contents_total() {
			return $this->facts['subtotal']; }
		/** Returns the shipping total. */
		public function get_shipping_total() {
			return $this->facts['shipping']; }
		/** Returns merchandise subtotal tax. */
		public function get_subtotal_tax() {
			return $this->facts['tax']; }
		/** Returns shipping tax. */
		public function get_shipping_tax() {
			return $this->facts['shipping_tax']; }
		/** Returns only supplied product IDs in the evaluator's existing getter shape. */
		public function get_cart() {
			$items = array();
			foreach ( isset( $this->facts['product_ids'] ) ? $this->facts['product_ids'] : array() as $id ) {
				$items[] = array(
					'product_id'   => $id,
					'variation_id' => 0,
					'quantity'     => 1,
				);
			}
			return $items;
		}
	}
endif;

<?php
/**
 *
 * Namespace PHPMathParser;
 *
 * @package Booster_For_WooCommerce/lib
 */

/**
 * WCJ_Stack
 */
class WCJ_Stack {
	/**
	 * $data
	 */
	protected $data = array();
	/**
	 * Push
	 */
	public function push( $element ) {
		$this->data[] = $element;
	}
	/**
	 * Poke
	 */
	public function poke() {
		return end( $this->data );
	}
	/**
	 * Pop
	 */
	public function pop() {
		return array_pop( $this->data );
	}

}

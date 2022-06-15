<?php
/**
 * This file is part of Hashids.
 *
 * (c) Ivan Akimov <ivan@barreleye.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Booster_For_WooCommerce/lib
 */

namespace Hashids\Math;

require_once 'MathInterface.php';

/**
 * This is the Bc math class.
 *
 * @author Vincent Klaiber <hello@vinkla.com>
 * @author Jakub Kramarz <lenwe@lenwe.net>
 * @author Johnson Page <jwpage@gmail.com>
 */
class Bc implements MathInterface {

	/**
	 * Add two arbitrary-length integers.
	 *
	 * @param string $a Get one int value.
	 * @param string $b Get Second int value.
	 *
	 * @return string
	 */
	public function add( $a, $b ) {
		return bcadd( $a, $b, 0 );
	}

	/**
	 * Multiply two arbitrary-length integers.
	 *
	 * @param string $a Get one int value.
	 * @param string $b Get Second int value.
	 *
	 * @return string
	 */
	public function multiply( $a, $b ) {
		return bcmul( $a, $b, 0 );
	}

	/**
	 * Divide two arbitrary-length integers.
	 *
	 * @param string $a Get one int value.
	 * @param string $b Get Second int value.
	 *
	 * @return string
	 */
	public function divide( $a, $b ) {
		return bcdiv( $a, $b, 0 );
	}

	/**
	 * Compute arbitrary-length integer modulo.
	 *
	 * @param string $n Get one int value.
	 * @param string $d Get Second int value.
	 *
	 * @return string
	 */
	public function mod( $n, $d ) {
		return bcmod( $n, $d );
	}

	/**
	 * Compares two arbitrary-length integers.
	 *
	 * @param string $a Get one int value.
	 * @param string $b Get Second int value.
	 *
	 * @return bool
	 */
	public function greaterThan( $a, $b ) {
		return bccomp( $a, $b, 0 ) > 0;
	}

	/**
	 * Converts arbitrary-length integer to PHP integer.
	 *
	 * @param string $a Get one int value.
	 *
	 * @return int
	 */
	public function intval( $a ) {
		return intval( $a );
	}

	/**
	 * Converts arbitrary-length integer to PHP string.
	 *
	 * @param string $a Get one int value.
	 *
	 * @return string
	 */
	public function strval( $a ) {
		return $a;
	}

	/**
	 * Converts PHP integer to arbitrary-length integer.
	 *
	 * @param int $a Get one int value.
	 *
	 * @return string
	 */
	public function get( $a ) {
		return $a;
	}
}

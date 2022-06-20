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

/**
 * Interface for different math extensions.
 *
 * @author Vincent Klaiber <hello@vinkla.com>
 * @author Jakub Kramarz <lenwe@lenwe.net>
 * @author Johnson Page <jwpage@gmail.com>
 */
interface MathInterface {

	/**
	 * Add two arbitrary-length integers.
	 *
	 * @param string $a Get one int value.
	 * @param string $b Get Second int value.
	 *
	 * @return string
	 */
	public function add( $a, $b);

	/**
	 * Multiply two arbitrary-length integers.
	 *
	 * @param string $a Get one int value.
	 * @param string $b Get Second int value.
	 *
	 * @return string
	 */
	public function multiply( $a, $b);

	/**
	 * Divide two arbitrary-length integers.
	 *
	 * @param string $a Get one int value.
	 * @param string $b Get Second int value.
	 *
	 * @return string
	 */
	public function divide( $a, $b);

	/**
	 * Compute arbitrary-length integer modulo.
	 *
	 * @param string $n Get one int value.
	 * @param string $d Get Second int value.
	 *
	 * @return string
	 */
	public function mod( $n, $d);

	/**
	 * Compares two arbitrary-length integers.
	 *
	 * @param string $a Get one int value.
	 * @param string $b Get Second int value.
	 *
	 * @return bool
	 */
	public function greaterThan( $a, $b);

	/**
	 * Converts arbitrary-length integer to PHP integer.
	 *
	 * @param string $a Get one int value.
	 *
	 * @return int
	 */
	public function intval( $a);

	/**
	 * Converts arbitrary-length integer to PHP string.
	 *
	 * @param string $a Get one int value.
	 *
	 * @return string
	 */
	public function strval( $a);

	/**
	 * Converts PHP integer to arbitrary-length integer.
	 *
	 * @param int $a Get one int value.
	 *
	 * @return string
	 */
	public function get( $a);
}

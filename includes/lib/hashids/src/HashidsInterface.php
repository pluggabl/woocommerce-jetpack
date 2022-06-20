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

namespace Hashids;

/**
 * This is the hashids interface.
 *
 * @author Ivan Akimov <ivan@barreleye.com>
 * @author Vincent Klaiber <hello@vinkla.com>
 */
interface HashidsInterface {

	/**
	 * Encode parameters to generate a hash.
	 *
	 * @param mixed $numbers Get numbers.
	 *
	 * @return string
	 */
	public function encode( ...$numbers);

	/**
	 * Decode a hash to the original parameter values.
	 *
	 * @param string $hash Get decode hash value.
	 *
	 * @return array
	 */
	public function decode( $hash);

	/**
	 * Encode hexadecimal values and generate a hash string.
	 *
	 * @param string $str get str value.
	 *
	 * @return string
	 */
	public function encodeHex( $str);

	/**
	 * Decode a hexadecimal hash.
	 *
	 * @param string $hash get hash value.
	 *
	 * @return string
	 */
	public function decodeHex( $hash);
}

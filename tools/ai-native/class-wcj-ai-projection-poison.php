<?php
/**
 * Poison object for standalone projection tests, never a runtime component.
 *
 * @package Booster_For_WooCommerce/tools
 */

defined( 'ABSPATH' ) || exit;

/** Object methods must not run even when supplied at a nested data boundary. */
final class WCJ_AI_Projection_Poison implements JsonSerializable {
	/**
	 * Conversion invocations.
	 *
	 * @var int
	 */
	public static $calls = 0;
	/**
	 * Detect string conversion.
	 *
	 * @return string Private marker.
	 */
	public function __toString() {
		++self::$calls;
		return 'PRIVATE_CUSTOMER';
	}
	/**
	 * Detect serialization.
	 *
	 * @return mixed Private marker.
	 */
	public function jsonSerialize(): mixed {
		++self::$calls;
		return 'PRIVATE_CUSTOMER';
	}
}

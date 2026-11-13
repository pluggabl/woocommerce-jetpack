<?php
/**
 * Synthetic CLI fixture only. Never loaded in a WordPress request.
 *
 * @package Booster_For_WooCommerce/tools
 */

/** Synthetic native permission substitute. */
class WCJ_Task_Finder {
	/** Synthetic permission toggle.
	 *
	 * @var bool
	 */
	public static $allowed = true;
	/**
	 * Synthetic allowed fixture helper.
	 *
	 * @param mixed $id Synthetic id input.
	 * @return mixed
	 */
	public static function allowed( $id ) {
		return is_string( $id ) && self::$allowed; }
}

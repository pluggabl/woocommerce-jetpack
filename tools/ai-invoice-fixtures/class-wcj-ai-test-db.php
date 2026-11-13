<?php
/**
 * Synthetic CLI fixture only. Never loaded in a WordPress request.
 *
 * @package Booster_For_WooCommerce/tools
 */

/** Minimal database substitute; load() receives no business records. */
class WCJ_AI_Test_DB {
	/** Synthetic table name.
	 *
	 * @var string
	 */
	public $options = 'synthetic_options';
	/**
	 * Return no external option rows in this unit fixture.
	 *
	 * @param string $sql Native query shape, not executed.
	 * @param string $mode Native expected result shape.
	 * @return array
	 * @throws RuntimeException When the synthetic assertion or boundary fails.
	 */
	public function get_results( $sql, $mode ) {
		if ( ! is_string( $sql ) || ARRAY_A !== $mode ) {
			throw new RuntimeException( 'Invalid fixture query.' ); }
		return array();
	}
}

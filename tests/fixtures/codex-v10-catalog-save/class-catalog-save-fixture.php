<?php
/**
 * Source-bound Save fixture with observable bounded schema access.
 *
 * @package Booster_For_WooCommerce/Tests
 */

/**
 * Inherit both real production Save methods unchanged.
 */
class Catalog_Save_Fixture extends WCJ_Admin {

	/**
	 * Skip unrelated admin hook wiring, not the production Save methods.
	 */
	public function __construct() {
		// The suite explicitly invokes the inherited real Save entry point.
	}

	/**
	 * Return a two-checkbox schema and record its requested section.
	 *
	 * @param string $section Requested module section.
	 * @return array
	 */
	public function get_settings( $section = '' ) {
		++$GLOBALS['codex_catalog_save']['schema_reads'];
		$GLOBALS['codex_catalog_save']['schema_sections'][] = $section;
		return array( array( 'id' => 'wcj_add_to_cart_enabled' ), array( 'id' => 'wcj_price_labels_enabled' ) );
	}

	/**
	 * Return the section map used by the existing hook.
	 *
	 * @return array
	 */
	public function get_sections() {
		return array( 'add_to_cart' => 'Add to cart' );
	}

	/**
	 * Record autoload work without accessing a database.
	 *
	 * @param array $settings Schema supplied by production.
	 * @return void
	 */
	public function disable_autoload_options_from_section( $settings ) {
		++$GLOBALS['codex_catalog_save']['autoload_calls'];
		$GLOBALS['codex_catalog_save']['autoload_settings'][] = $settings;
	}
}

<?php
/**
 * Booster True V10 - Guided Editor Registry.
 *
 * Centralizes all V10 guided editors so REST, Bootstrap, and Catalog scale cleanly.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/editors/class-wcj-v10-editor-gateway-fees.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-pdf-invoicing.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-labels.php';

if ( ! class_exists( 'WCJ_V10_Registry' ) ) :

	/**
	 * Registry of all V10 guided editor classes.
	 */
	class WCJ_V10_Registry {

		/**
		 * Registered editor classes keyed by editor slug.
		 *
		 * @return array<string, string>
		 */
		public static function all() {
			$editors = array(
				'payment_gateways_fees' => 'WCJ_V10_Editor_Gateway_Fees',
				'pdf_invoicing'         => 'WCJ_V10_Editor_PDF_Invoicing',
				'button_price_labels'   => 'WCJ_V10_Editor_Labels',
			);
			return apply_filters( 'wcj_v10_editors', $editors );
		}

		/**
		 * Resolve an editor class by slug.
		 *
		 * @param string $slug Editor slug.
		 * @return string|null Class name or null if unknown.
		 */
		public static function get( $slug ) {
			$all = self::all();
			return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
		}

		/**
		 * Enqueueable script map (handle => plugin-relative path) across all editors.
		 *
		 * @return array<string, string>
		 */
		public static function editor_scripts() {
			$scripts = array();
			foreach ( self::all() as $slug => $cls ) {
				if ( method_exists( $cls, 'script' ) ) {
					$scripts[ 'wcj-v10-ed-' . sanitize_key( $slug ) ] = $cls::script();
				}
			}
			return $scripts;
		}

		/**
		 * Context query parameter keys accepted by an editor (for example gateway or doc_type).
		 *
		 * @param string $slug Editor slug.
		 * @return array<int, string>
		 */
		public static function context_keys( $slug ) {
			$cls = self::get( $slug );
			if ( $cls && method_exists( $cls, 'context_keys' ) ) {
				return (array) $cls::context_keys();
			}
			return array();
		}

		/**
		 * Primary Classic module section fallback when returning to Classic from a multi-module editor.
		 *
		 * @param string $slug_or_section Editor or section slug.
		 * @return string
		 */
		public static function primary_classic_section( $slug_or_section ) {
			$cls = self::get( $slug_or_section );
			if ( $cls && method_exists( $cls, 'primary_classic_section' ) ) {
				return (string) $cls::primary_classic_section();
			}
			return '';
		}

		/**
		 * Underlying Booster module IDs controlled by an editor (for allowlist and role enforcement).
		 *
		 * @param string $slug Editor slug.
		 * @return array<int, string>
		 */
		public static function required_module_ids( $slug ) {
			$cls = self::get( $slug );
			if ( $cls && method_exists( $cls, 'required_module_ids' ) ) {
				return (array) $cls::required_module_ids();
			}
			return array();
		}

		/**
		 * Regex alternation matching all registered editor slugs for REST routes.
		 *
		 * @return string
		 */
		public static function slug_pattern() {
			$quoted = array_map(
				static function ( $slug ) {
					return preg_quote( $slug, '/' );
				},
				array_keys( self::all() )
			);
			return '(?P<slug>' . implode( '|', $quoted ) . ')';
		}

		/**
		 * Map classic section IDs to guided editor slugs.
		 *
		 * @return array<string, string>
		 */
		public static function section_to_editor_map() {
			$map = array();
			foreach ( self::all() as $cls ) {
				$map = array_merge( $map, $cls::section_map() );
			}
			return $map;
		}

		/**
		 * Command Center summary cards for all guided editors.
		 *
		 * @return array<int, array<string, string>>
		 */
		public static function flagship_summaries() {
			$out = array();
			foreach ( self::all() as $cls ) {
				$out[] = $cls::summary();
			}
			return $out;
		}
	}

endif;

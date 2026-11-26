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
			return array(
				'payment_gateways_fees' => 'WCJ_V10_Editor_Gateway_Fees',
				'pdf_invoicing'         => 'WCJ_V10_Editor_PDF_Invoicing',
				'button_price_labels'   => 'WCJ_V10_Editor_Labels',
			);
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

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

require_once __DIR__ . '/class-wcj-v10-editor-base.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-gateway-fees.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-pdf-invoicing.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-labels.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-order-numbers.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-pricing.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-checkout-fields.php';
require_once __DIR__ . '/editors/class-wcj-v10-editor-product-addons.php';

if ( ! class_exists( 'WCJ_V10_Registry' ) ) :

	/**
	 * Registry of all V10 guided editor classes implementing WCJ_V10_Editor_Base.
	 */
	class WCJ_V10_Registry {

		/**
		 * Registered editor classes keyed by editor slug.
		 *
		 * @return array<string, string>
		 */
		public static function all() {
			$editors = array(
				'payment_gateways_fees'  => 'WCJ_V10_Editor_Gateway_Fees',
				'pdf_invoicing'          => 'WCJ_V10_Editor_PDF_Invoicing',
				'button_price_labels'    => 'WCJ_V10_Editor_Labels',
				'order_numbers'          => 'WCJ_V10_Editor_Order_Numbers',
				'customer_bulk_pricing'  => 'WCJ_V10_Editor_Pricing',
				'checkout_custom_fields' => 'WCJ_V10_Editor_Checkout_Fields',
				'product_addons'         => 'WCJ_V10_Editor_Product_Addons',
			);
			$filtered = apply_filters( 'wcj_v10_editors', $editors );
			$valid    = array();
			if ( is_array( $filtered ) ) {
				foreach ( $filtered as $slug => $cls ) {
					if (
						is_string( $cls )
						&& class_exists( $cls )
						&& is_subclass_of( $cls, 'WCJ_V10_Editor_Base' )
						&& method_exists( $cls, 'load_state' )
						&& method_exists( $cls, 'sanitize_draft' )
						&& method_exists( $cls, 'compute_preview' )
						&& method_exists( $cls, 'build_option_writes' )
					) {
						$valid[ sanitize_key( (string) $slug ) ] = $cls;
					}
				}
			}
			return $valid;
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
				$rel = $cls::script();
				if ( '' !== $rel ) {
					$scripts[ 'wcj-v10-ed-' . sanitize_key( $slug ) ] = $rel;
				}
			}
			return $scripts;
		}

		/**
		 * Context query parameter keys accepted by an editor.
		 *
		 * @param string $slug Editor slug.
		 * @return array<int, string>
		 */
		public static function context_keys( $slug ) {
			$cls = self::get( $slug );
			return $cls ? (array) $cls::context_keys() : array();
		}

		/**
		 * Volatile option keys excluded from stale base_hashes conflict checks.
		 *
		 * @param string $slug    Editor slug.
		 * @param array  $context Context params.
		 * @return array<int, string>
		 */
		public static function volatile_options( $slug, $context = array() ) {
			$cls = self::get( $slug );
			return $cls ? (array) $cls::volatile_options( $context ) : array();
		}

		/**
		 * Primary Classic module section fallback when returning to Classic from a multi-module editor.
		 *
		 * @param string $slug_or_section Editor or section slug.
		 * @return string
		 */
		public static function primary_classic_section( $slug_or_section ) {
			$cls = self::get( $slug_or_section );
			return $cls ? (string) $cls::primary_classic_section() : '';
		}

		/**
		 * Underlying Booster module IDs controlled by an editor (for allowlist and role enforcement).
		 *
		 * @param string $slug Editor slug.
		 * @return array<int, string>
		 */
		public static function required_module_ids( $slug ) {
			$cls = self::get( $slug );
			return $cls ? (array) $cls::required_module_ids() : array();
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

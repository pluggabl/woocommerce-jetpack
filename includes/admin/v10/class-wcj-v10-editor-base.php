<?php
/**
 * Booster True V10 - Abstract Base Contract for Guided Editors.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Base' ) ) :

	/**
	 * Shared contract and utility helpers for every Guided Editor.
	 */
	abstract class WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return '';
		}

		/**
		 * Plugin-relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array();
		}

		/**
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return static::slug();
		}

		/**
		 * Underlying Booster module IDs controlled by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array();
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array();
		}

		/**
		 * Classic section IDs mapped to this guided editor slug.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array();
		}

		/**
		 * Authoritative list of wp_options keys read/written by this editor.
		 *
		 * @param array $context Editor context.
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			unset( $context );
			return array();
		}

		/**
		 * Option keys mutated asynchronously by live checkout/orders (excluded from base_hashes stale checks).
		 *
		 * @param array $context Editor context.
		 * @return array<int, string>
		 */
		public static function volatile_options( $context = array() ) {
			unset( $context );
			return array();
		}

		/**
		 * Plain-english field label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array();
		}

		/**
		 * Impact grouping metadata (goes_live, stored_on_orders, impact) for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live?: bool, stored_on_orders?: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array();
		}

		/**
		 * Emergency shutoff option writes.
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			unset( $snapshot );
			return array();
		}

		/**
		 * Validate draft before publishing.
		 *
		 * @param array $draft Sanitized draft.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft ) {
			unset( $draft );
			return true;
		}

		/**
		 * Post-write side-effect hook called after atomic publish, rollback, or emergency-off.
		 *
		 * @param array<int, string> $written_keys Option keys written.
		 * @return void
		 */
		public static function on_after_write( $written_keys ) {
			unset( $written_keys );
		}

		/**
		 * Runtime tier probe matching Booster's authoritative booster_option filter.
		 *
		 * @return bool
		 */
		protected static function is_pro() {
			return WCJ_V10_Store::is_pro();
		}

		/**
		 * Detect Booster Elite edition specifically.
		 *
		 * @return bool
		 */
		protected static function is_elite() {
			return WCJ_V10_Store::is_elite();
		}

		/**
		 * Read a scalar value from a fresh snapshot tuple with fallback.
		 *
		 * @param array<string, array> $snapshot Snapshot map.
		 * @param string               $key      Option name.
		 * @param mixed                $default  Default when absent.
		 * @return mixed
		 */
		protected static function snap_val( $snapshot, $key, $default = '' ) {
			if ( isset( $snapshot[ $key ] ) ) {
				if ( empty( $snapshot[ $key ]['exists'] ) ) {
					return $default;
				}
				return WCJ_V10_Store::state_value( $snapshot[ $key ] );
			}
			return get_option( $key, $default );
		}

		/**
		 * Read an array option from a fresh snapshot tuple safely.
		 *
		 * @param array<string, array> $snapshot Snapshot map.
		 * @param string               $key      Option name.
		 * @return array
		 */
		protected static function snap_arr( $snapshot, $key ) {
			$val = self::snap_val( $snapshot, $key, array() );
			return is_array( $val ) ? $val : array();
		}
	}

endif;

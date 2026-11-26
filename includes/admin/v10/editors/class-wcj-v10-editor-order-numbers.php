<?php
/**
 * Booster True V10 - Guided Editor: Order Numbers.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Order_Numbers' ) ) :

	/**
	 * Order Numbers guided editor with UTC display parity and missing-row counter protection.
	 */
	class WCJ_V10_Editor_Order_Numbers extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'order_numbers';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/order-numbers.js';
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
			return 'order_numbers';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'order_numbers' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Order Numbers', 'woocommerce-jetpack' ),
				'description' => __( 'Configure sequential order numbers, prefixes, and counters with live sequence preview.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Order Sequence', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'order_numbers' => self::slug(),
			);
		}

		/**
		 * Authoritative list of wp_options keys read/written by this editor.
		 *
		 * @param array $context Editor context.
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			unset( $context );
			return array(
				'wcj_order_numbers_enabled',
				'wcj_order_number_sequential_enabled',
				'wcj_order_number_counter',
				'wcj_order_number_counter_previous_order_date',
				'wcj_order_number_prefix',
				'wcj_order_number_counter_reset_enabled',
				'wcj_order_number_date_prefix',
				'wcj_order_number_min_width',
				'wcj_order_number_suffix',
				'wcj_order_number_date_suffix',
				'wcj_order_number_search_by_custom_number_enabled',
				'wcj_order_number_order_tracking_enabled',
				'wcj_order_numbers_min_order_id',
			);
		}

		/**
		 * Order counter options mutate via checkout SQL outside editor requests.
		 *
		 * @param array $context Editor context.
		 * @return array<int, string>
		 */
		public static function volatile_options( $context = array() ) {
			unset( $context );
			return array(
				'wcj_order_number_counter',
				'wcj_order_number_counter_previous_order_date',
			);
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'        => __( 'Order Numbers Module', 'woocommerce-jetpack' ),
				'mode'           => __( 'Numbering Method', 'woocommerce-jetpack' ),
				'prefix'         => __( 'Order Number Prefix', 'woocommerce-jetpack' ),
				'counter'        => __( 'Next Counter Value', 'woocommerce-jetpack' ),
				'min_width'      => __( 'Minimum Number Width (Zero Padding)', 'woocommerce-jetpack' ),
				'date_prefix'    => __( 'Date Prefix Format', 'woocommerce-jetpack' ),
				'suffix'         => __( 'Order Number Suffix', 'woocommerce-jetpack' ),
				'date_suffix'    => __( 'Date Suffix Format', 'woocommerce-jetpack' ),
				'counter_reset'  => __( 'Reset Sequential Counter Period', 'woocommerce-jetpack' ),
				'min_order_id'   => __( 'Minimum Order ID Threshold', 'woocommerce-jetpack' ),
				'order_tracking' => __( 'Enable Order Tracking Lookup', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Impact grouping metadata (separates live order writes from display formatting).
		 *
		 * @return array<string, array{goes_live?: bool, stored_on_orders?: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'enabled'        => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Assigns custom numbers onto newly created WooCommerce orders.', 'woocommerce-jetpack' ),
				),
				'mode'           => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Changes how new orders get numbered (Sequential, Order ID, or Hash).', 'woocommerce-jetpack' ),
				),
				'counter'        => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Sets the next sequential number stored on future WooCommerce orders.', 'woocommerce-jetpack' ),
				),
				'prefix'         => array(
					'goes_live' => true,
					'impact'    => __( 'Displayed retroactively on order admin lists, emails, and customer My Account pages.', 'woocommerce-jetpack' ),
				),
				'min_width'      => array(
					'goes_live' => true,
					'impact'    => __( 'Pads displayed order numbers with leading zeros (retroactive across orders).', 'woocommerce-jetpack' ),
				),
				'date_prefix'    => array(
					'goes_live' => true,
					'impact'    => __( 'Formats a UTC date segment before displayed order numbers.', 'woocommerce-jetpack' ),
				),
				'suffix'         => array(
					'goes_live' => true,
					'impact'    => __( 'Appends a suffix to displayed order numbers across orders and emails.', 'woocommerce-jetpack' ),
				),
				'date_suffix'    => array(
					'goes_live' => true,
					'impact'    => __( 'Formats a UTC date segment after displayed order numbers.', 'woocommerce-jetpack' ),
				),
				'counter_reset'  => array(
					'goes_live' => false,
					'impact'    => __( 'Resets sequential counter automatically on schedule (requires date token).', 'woocommerce-jetpack' ),
				),
				'min_order_id'   => array(
					'goes_live' => false,
					'impact'    => __( 'Skips custom numbering for order IDs below this threshold.', 'woocommerce-jetpack' ),
				),
				'order_tracking' => array(
					'goes_live' => false,
					'impact'    => __( 'Allows customers to track orders using the custom formatted number.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Normalize numbering mode slug.
		 *
		 * @param mixed $raw Raw mode.
		 * @return string
		 */
		private static function normalize_mode( $raw ) {
			$val = (string) $raw;
			return in_array( $val, array( 'yes', 'no', 'hash_crc32' ), true ) ? $val : 'yes';
		}

		/**
		 * Format sample order number matching class-wcj-order-numbers.php runtime display rules.
		 *
		 * @param array $cfg    Editor state.
		 * @param int   $num    Raw counter/order ID.
		 * @param bool  $is_pro Runtime tier flag.
		 * @return string
		 */
		private static function format_order_num( $cfg, $num, $is_pro ) {
			$mode = self::normalize_mode( $cfg['mode'] );
			if ( 'hash_crc32' === $mode ) {
				$core = sprintf( '%u', crc32( (string) $num ) );
			} else {
				$core = (string) $num;
			}
			$ts     = time();
			$fmt_dt = static function ( $fmt ) use ( $ts ) {
				if ( '' === (string) $fmt ) {
					return '';
				}
				if ( function_exists( 'wp_date' ) ) {
					return (string) wp_date( (string) $fmt, $ts );
				}
				return function_exists( 'date_i18n' ) ? date_i18n( (string) $fmt, $ts ) : gmdate( (string) $fmt, $ts );
			};
			$prefix = (string) $cfg['prefix'];
			if ( false !== strpos( $prefix, '[' ) ) {
				$wp_def = (string) get_option( 'date_format', 'Y-m-d' );
				$prefix = (string) preg_replace_callback(
					'/\[wcj_(?:order|current)_date([^\]]*)\]/i',
					static function ( $m ) use ( $fmt_dt, $wp_def ) {
						$tok = preg_match( '/(?:date_)?format\s*=\s*["\']([^"\']+)["\']/i', $m[1], $fm ) ? $fm[1] : $wp_def;
						return $fmt_dt( $tok );
					},
					$prefix
				);
				if ( false !== strpos( $prefix, '[' ) ) {
					$prefix = wp_strip_all_tags( do_shortcode( $prefix ) );
				}
			}
			if ( ! $is_pro ) {
				return $prefix . $core;
			}
			$dp     = $fmt_dt( $cfg['date_prefix'] );
			$ds     = $fmt_dt( $cfg['date_suffix'] );
			$width  = max( 0, (int) $cfg['min_width'] );
			$padded = $width > 0 ? sprintf( '%0' . $width . 's', $core ) : $core;
			$suffix = (string) $cfg['suffix'];
			return $prefix . $dp . $padded . $suffix . $ds;
		}

		/**
		 * Read counter value directly from wp_options DB so concurrent checkout SQL increments are visible.
		 *
		 * @return int
		 */
		private static function fresh_counter() {
			$st = WCJ_V10_Store::read_state( 'wcj_order_number_counter' );
			if ( empty( $st['exists'] ) ) {
				return 1;
			}
			return max( 1, (int) WCJ_V10_Store::state_value( $st ) );
		}

		/**
		 * Load live state, metadata, and compatibility diagnostics.
		 *
		 * @param array $context   Request params.
		 * @param array $store_ctx Store context.
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			unset( $context );
			$nonce   = wp_create_nonce( 'wcj-cat-nonce' );
			$is_pro  = self::is_pro();
			$counter = self::fresh_counter();
			$prefix  = (string) get_option( 'wcj_order_number_prefix', '' );
			$dp      = (string) get_option( 'wcj_order_number_date_prefix', '' );
			$width   = (string) get_option( 'wcj_order_number_min_width', '0' );
			$suffix  = (string) get_option( 'wcj_order_number_suffix', '' );
			$ds      = (string) get_option( 'wcj_order_number_date_suffix', '' );

			$diagnostics = array();
			if ( ! $is_pro && ( '' !== $dp || '' !== $suffix || '' !== $ds || (int) $width > 0 ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Date prefix, suffix, or minimum width values from Classic/Elite are stored in your database (inactive in Free) and will take effect if you upgrade.', 'woocommerce-jetpack' ),
				);
			}
			if ( preg_match( '/[\/#(\[\.]/', $prefix ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Your prefix contains punctuation like "/", "#", "[", or "." — on non-HPOS stores this can interfere with admin order-number search.', 'woocommerce-jetpack' ),
				);
			}
			$diagnostics[] = array(
				'level' => 'info',
				'text'  => __( 'Prefix, suffix, and width formatting updates displayed order numbers retroactively. Invoice numbers are configured separately inside PDF Invoicing.', 'woocommerce-jetpack' ),
			);
			if ( ! empty( $store_ctx['is_blocks_checkout'] ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'Checkout Blocks detected: WooCommerce draft checkout orders may advance the sequential counter if a shopper starts checkout and abandons it.', 'woocommerce-jetpack' ),
				);
			}

			$date_tokens = array();
			$letters     = str_split( 'dDjlnNSwzWFmMntLoYyaAgGhHis' );
			$now_ts      = time();
			foreach ( $letters as $ltr ) {
				$date_tokens[ $ltr ] = function_exists( 'wp_date' )
					? (string) wp_date( $ltr, $now_ts )
					: ( function_exists( 'date_i18n' ) ? (string) date_i18n( $ltr, $now_ts ) : gmdate( $ltr, $now_ts ) );
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=shipping_and_orders&section=order_numbers&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'enabled'        => 'yes' === get_option( 'wcj_order_numbers_enabled', 'no' ),
					'mode'           => self::normalize_mode( get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ),
					'prefix'         => $prefix,
					'counter'        => (string) $counter,
					'min_width'      => $width,
					'date_prefix'    => $dp,
					'suffix'         => $suffix,
					'date_suffix'    => $ds,
					'counter_reset'  => (string) get_option( 'wcj_order_number_counter_reset_enabled', 'no' ),
					'min_order_id'   => (string) get_option( 'wcj_order_numbers_min_order_id', '0' ),
					'order_tracking' => 'yes' === get_option( 'wcj_order_number_order_tracking_enabled', 'yes' ),
				),
				'meta'        => array(
					'is_pro'          => $is_pro,
					'last_order_id'   => ! empty( $store_ctx['last_order_id'] ) ? (int) $store_ctx['last_order_id'] : 1041,
					'current_counter' => $counter,
					'date_tokens'     => $date_tokens,
					'date_format'     => (string) get_option( 'date_format', 'Y-m-d' ),
					'verify_url'      => $store_ctx['orders_url'],
					'verify_label'    => __( 'Open WooCommerce Orders', 'woocommerce-jetpack' ),
					'diagnostics'     => $diagnostics,
				),
			);
		}

		/**
		 * Sanitize draft values while keeping Free runtime-gated options untouched.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$is_pro = self::is_pro();
			$reset  = array_key_exists( 'counter_reset', $draft ) ? (string) $draft['counter_reset'] : (string) $live['counter_reset'];
			if ( ! in_array( $reset, array( 'no', 'daily', 'monthly', 'yearly' ), true ) ) {
				$reset = 'no';
			}

			$counter_untouched = ! array_key_exists( 'counter', $draft )
				|| (string) $draft['counter'] === (string) $live['counter']
				|| ( isset( $draft['counter_base'] ) && (string) $draft['counter'] === (string) $draft['counter_base'] );
			$counter_val       = $counter_untouched
				? $live['counter']
				: (string) max( 1, absint( $draft['counter'] ) );

			return array(
				'enabled'        => array_key_exists( 'enabled', $draft ) ? ! empty( $draft['enabled'] ) : ! empty( $live['enabled'] ),
				'mode'           => array_key_exists( 'mode', $draft ) ? self::normalize_mode( $draft['mode'] ) : $live['mode'],
				'prefix'         => ( ! array_key_exists( 'prefix', $draft ) || (string) $draft['prefix'] === (string) $live['prefix'] )
					? $live['prefix']
					: sanitize_text_field( (string) $draft['prefix'] ),
				'counter'        => $counter_val,
				'min_width'      => ! $is_pro
					? $live['min_width']
					: ( ( ! array_key_exists( 'min_width', $draft ) || (string) $draft['min_width'] === (string) $live['min_width'] )
						? $live['min_width']
						: (string) min( 16, max( 0, absint( $draft['min_width'] ) ) ) ),
				'date_prefix'    => ! $is_pro
					? $live['date_prefix']
					: ( ( ! array_key_exists( 'date_prefix', $draft ) || (string) $draft['date_prefix'] === (string) $live['date_prefix'] )
						? $live['date_prefix']
						: sanitize_text_field( (string) $draft['date_prefix'] ) ),
				'suffix'         => ! $is_pro
					? $live['suffix']
					: ( ( ! array_key_exists( 'suffix', $draft ) || (string) $draft['suffix'] === (string) $live['suffix'] )
						? $live['suffix']
						: sanitize_text_field( (string) $draft['suffix'] ) ),
				'date_suffix'    => ! $is_pro
					? $live['date_suffix']
					: ( ( ! array_key_exists( 'date_suffix', $draft ) || (string) $draft['date_suffix'] === (string) $live['date_suffix'] )
						? $live['date_suffix']
						: sanitize_text_field( (string) $draft['date_suffix'] ) ),
				'counter_reset'  => $reset,
				'min_order_id'   => ( ! array_key_exists( 'min_order_id', $draft ) || (string) $draft['min_order_id'] === (string) $live['min_order_id'] )
					? $live['min_order_id']
					: (string) max( 0, absint( $draft['min_order_id'] ) ),
				'order_tracking' => array_key_exists( 'order_tracking', $draft ) ? ! empty( $draft['order_tracking'] ) : ! empty( $live['order_tracking'] ),
			);
		}

		/**
		 * Check whether a PHP date format string changes across the requested reset period.
		 *
		 * @param string $fmt    PHP date format tokens.
		 * @param string $period Reset period ('daily', 'monthly', 'yearly').
		 * @return bool
		 */
		private static function date_format_covers_period( $fmt, $period ) {
			$clean     = preg_replace( '/\\\\./', '', (string) $fmt );
			$has_year  = (bool) preg_match( '/[Yyo]/', $clean );
			$has_month = (bool) preg_match( '/[mnMF]/', $clean );
			$has_day   = (bool) preg_match( '/[dj]/', $clean );
			$has_yday  = false !== strpos( $clean, 'z' );
			if ( 'yearly' === $period ) {
				return $has_year;
			}
			if ( 'monthly' === $period ) {
				return $has_year && $has_month;
			}
			if ( 'daily' === $period ) {
				return $has_year && ( ( $has_month && $has_day ) || $has_yday );
			}
			return true;
		}

		/**
		 * Extract date format tokens from [wcj_order_date] or [wcj_current_date] shortcodes in prefix.
		 *
		 * @param string $prefix Raw prefix string.
		 * @return string
		 */
		private static function shortcode_date_formats( $prefix ) {
			$formats = '';
			if ( preg_match_all( '/\[wcj_(?:order|current)_date([^\]]*)\]/i', (string) $prefix, $matches ) ) {
				$wp_def = (string) get_option( 'date_format', 'Y-m-d' );
				foreach ( $matches[1] as $attr_str ) {
					if ( preg_match( '/(?:date_)?format\s*=\s*["\']([^"\']+)["\']/i', $attr_str, $fm ) ) {
						$formats .= $fm[1];
					} else {
						$formats .= $wp_def;
					}
				}
			}
			return $formats;
		}

		/**
		 * Guard against duplicate order numbers from lowering counter or resetting without a period-safe date format.
		 *
		 * @param array $draft        Sanitized draft.
		 * @param array $live         Live state before publish.
		 * @param array $changed_keys Changed diff keys.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft, $live = array(), $changed_keys = array() ) {
			unset( $live, $changed_keys );
			$db_counter = self::fresh_counter();
			if ( ! empty( $draft['enabled'] ) && 'yes' === $draft['mode'] && (int) $draft['counter'] < $db_counter ) {
				return new WP_Error(
					'wcj_v10_counter_too_low',
					sprintf(
						/* translators: %d: Current sequential counter in database */
						__( 'Next Counter Value must be at least %d (your current live counter) to avoid duplicate order numbers.', 'woocommerce-jetpack' ),
						$db_counter
					),
					array( 'status' => 400 )
				);
			}

			if ( ! empty( $draft['enabled'] ) && 'no' !== $draft['counter_reset'] ) {
				$combined = self::shortcode_date_formats( (string) $draft['prefix'] );
				if ( self::is_pro() ) {
					$combined .= (string) $draft['date_prefix'] . (string) $draft['date_suffix'];
				}
				if ( ! self::date_format_covers_period( $combined, (string) $draft['counter_reset'] ) ) {
					return new WP_Error(
						'wcj_v10_reset_needs_date',
						sprintf(
							/* translators: %s: Counter reset frequency */
							__( 'A %s counter reset needs matching year/month/day date tokens (for example Y-m-d for daily, Y-m- for monthly, or Y for yearly) in Date Format or [wcj_order_date date_format="Y-m-d"] so numbers never repeat.', 'woocommerce-jetpack' ),
							$draft['counter_reset']
						),
						array( 'status' => 400 )
					);
				}
			}
			return true;
		}

		/**
		 * Project Before & After order sequence preview.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview parameters.
		 * @param array $meta    Editor metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			unset( $context );
			$is_pro   = self::is_pro();
			$last_id  = ! empty( $meta['last_order_id'] ) ? (int) $meta['last_order_id'] : 1041;
			$project  = static function ( $cfg ) use ( $is_pro, $last_id ) {
				$next_raw = ( 'yes' === $cfg['mode'] ) ? max( 1, (int) $cfg['counter'] ) : ( $last_id + 1 );
				if ( empty( $cfg['enabled'] ) ) {
					$next_display   = '#' . ( $last_id + 1 );
					$second_display = '#' . ( $last_id + 2 );
				} else {
					$next_display   = '#' . self::format_order_num( $cfg, $next_raw, $is_pro );
					$second_display = '#' . self::format_order_num( $cfg, $next_raw + 1, $is_pro );
				}
				return array(
					'enabled'         => ! empty( $cfg['enabled'] ),
					'mode'            => $cfg['mode'],
					'last_order_id'   => $last_id,
					'next_order'      => $next_display,
					'following_order' => $second_display,
					'reset_label'     => $cfg['counter_reset'],
					'min_order_id'    => (int) ( $cfg['min_order_id'] ?? 0 ),
					'order_tracking'  => ! empty( $cfg['order_tracking'] ),
				);
			};

			return array(
				'before' => $project( $live ),
				'after'  => $project( $draft ),
			);
		}

		/**
		 * Write only changed options and auto-seed missing counter row when activating Sequential mode.
		 *
		 * @param array                $draft        Sanitized draft.
		 * @param array<int, string>   $changed_keys Changed diff keys.
		 * @param array<string, array> $snapshot     Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft, $changed_keys = array(), $snapshot = array() ) {
			if ( empty( $changed_keys ) ) {
				return array();
			}

			$changed = array_flip( $changed_keys );
			$writes  = array();
			$map     = array(
				'enabled'        => array( 'wcj_order_numbers_enabled', ! empty( $draft['enabled'] ) ? 'yes' : 'no' ),
				'mode'           => array( 'wcj_order_number_sequential_enabled', $draft['mode'] ),
				'prefix'         => array( 'wcj_order_number_prefix', $draft['prefix'] ),
				'counter'        => array( 'wcj_order_number_counter', (string) max( 1, (int) $draft['counter'] ) ),
				'counter_reset'  => array( 'wcj_order_number_counter_reset_enabled', $draft['counter_reset'] ),
				'min_order_id'   => array( 'wcj_order_numbers_min_order_id', (string) max( 0, (int) $draft['min_order_id'] ) ),
				'order_tracking' => array( 'wcj_order_number_order_tracking_enabled', ! empty( $draft['order_tracking'] ) ? 'yes' : 'no' ),
			);
			if ( self::is_pro() ) {
				$map['min_width']   = array( 'wcj_order_number_min_width', $draft['min_width'] );
				$map['date_prefix'] = array( 'wcj_order_number_date_prefix', $draft['date_prefix'] );
				$map['suffix']      = array( 'wcj_order_number_suffix', $draft['suffix'] );
				$map['date_suffix'] = array( 'wcj_order_number_date_suffix', $draft['date_suffix'] );
			}

			foreach ( $map as $field => $pair ) {
				if ( isset( $changed[ $field ] ) ) {
					$writes[ $pair[0] ] = $pair[1];
				}
			}

			// Ensure wcj_order_number_counter row exists in wp_options so Booster's MySQL transaction never rolls back on NULL.
			if ( ! empty( $draft['enabled'] ) && 'yes' === $draft['mode'] && empty( $snapshot['wcj_order_number_counter']['exists'] ) ) {
				$init_counter = max( 1, (int) $draft['counter'] );
				if ( '' === trim( (string) $draft['prefix'] ) && ! isset( $changed['counter'] ) && $init_counter <= 1 && function_exists( 'wc_get_orders' ) ) {
					$latest = wc_get_orders(
						array(
							'limit'   => 1,
							'orderby' => 'ID',
							'order'   => 'DESC',
							'return'  => 'ids',
						)
					);
					if ( ! empty( $latest ) ) {
						$init_counter = max( $init_counter, (int) $latest[0] + 1 );
					}
				}
				$writes['wcj_order_number_counter'] = (string) $init_counter;
			}
			if ( isset( $changed['enabled'] ) && ! empty( $draft['enabled'] ) && empty( $snapshot['wcj_order_number_search_by_custom_number_enabled']['exists'] ) ) {
				$writes['wcj_order_number_search_by_custom_number_enabled'] = 'yes';
			}

			return $writes;
		}

		/**
		 * Emergency shutoff option writes.
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			if ( 'yes' !== self::snap_val( $snapshot, 'wcj_order_numbers_enabled', 'no' ) ) {
				return array();
			}
			return array(
				'wcj_order_numbers_enabled' => 'no',
			);
		}
	}

endif;

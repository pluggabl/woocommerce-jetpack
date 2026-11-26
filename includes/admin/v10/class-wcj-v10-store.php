<?php
/**
 * Booster True V10 - Safe CAS Option Store & Conflict-Aware Revision History.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Store' ) ) :

	/**
	 * Atomic option compare-exchange, locking, and LIFO revision rollback.
	 */
	class WCJ_V10_Store {

		const MAX_REVISIONS = 10;

		/**
		 * Resolve the active Booster singleton across Free, Plus, and Elite.
		 *
		 * @return object|null
		 */
		public static function core() {
			if ( function_exists( 'w_c_j' ) && defined( 'WCJ_FREE_PLUGIN_PATH' ) && 0 === strpos( wp_normalize_path( __DIR__ ), wp_normalize_path( WCJ_FREE_PLUGIN_PATH ) ) ) {
				return w_c_j();
			}
			if ( function_exists( 'WCJ' ) ) {
				return WCJ();
			}
			return function_exists( 'w_c_j' ) ? w_c_j() : null;
		}

		/**
		 * Detect active Booster edition slug: free, plus, or elite.
		 *
		 * @return string
		 */
		public static function edition() {
			$file = '';
			if ( defined( 'WCJ_PLUGIN_FILE' ) ) {
				$file = basename( WCJ_PLUGIN_FILE );
			} elseif ( defined( 'WCJ_FREE_PLUGIN_FILE' ) ) {
				$file = basename( WCJ_FREE_PLUGIN_FILE );
			}
			if ( 'booster-elite-for-woocommerce.php' === $file ) {
				return 'elite';
			}
			if ( 'booster-plus-for-woocommerce.php' === $file ) {
				return 'plus';
			}
			return 'free';
		}

		/**
		 * Read raw option state directly from wp_options bypassing object caches.
		 *
		 * @param string $key Option name.
		 * @return array{exists: bool, raw: string}
		 */
		public static function read_state( $key ) {
			global $wpdb;
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $row ? array(
				'exists' => true,
				'raw'    => (string) $row['option_value'],
			) : array(
				'exists' => false,
				'raw'    => '',
			);
		}

		/**
		 * Build a state tuple from a PHP value.
		 *
		 * @param mixed $value Option value.
		 * @return array{exists: bool, raw: string}
		 */
		public static function value_state( $value ) {
			return array(
				'exists' => true,
				'raw'    => (string) maybe_serialize( $value ),
			);
		}

		/**
		 * Extract PHP value from a state tuple.
		 *
		 * @param array $state State tuple.
		 * @return mixed
		 */
		public static function state_value( $state ) {
			return ! empty( $state['exists'] ) ? maybe_unserialize( $state['raw'] ) : null;
		}

		/**
		 * Deterministic SHA-256 fingerprint of a state tuple.
		 *
		 * @param array $state State tuple.
		 * @return string
		 */
		public static function state_hash( $state ) {
			return hash( 'sha256', ( ! empty( $state['exists'] ) ? '1' : '0' ) . "\0" . ( $state['raw'] ?? '' ) );
		}

		/**
		 * Compare-and-swap option update; clears WP option caches and Booster singleton cache.
		 *
		 * @param string $key         Option name.
		 * @param array  $before      Expected current state.
		 * @param array  $after       Target state.
		 * @param array  $no_autoload Keys that should insert with autoload='no'.
		 * @return bool
		 */
		public static function compare_exchange( $key, $before, $after, $no_autoload = array() ) {
			global $wpdb;
			if ( $before === $after ) {
				return self::read_state( $key ) === $before;
			}
			if ( empty( $before['exists'] ) ) {
				$autoload = in_array( $key, $no_autoload, true ) ? 'no' : 'yes';
				$result   = $wpdb->query(
					$wpdb->prepare(
						"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
						$key,
						$after['raw'],
						$autoload
					)
				); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			} elseif ( empty( $after['exists'] ) ) {
				$result = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = %s",
						$key,
						$before['raw']
					)
				); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			} else {
				$result = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s",
						$after['raw'],
						$key,
						$before['raw']
					)
				); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
			self::flush_option_caches( $key );
			return 1 === $result;
		}

		/**
		 * Flush WordPress and Booster caches for an option key.
		 *
		 * @param string $key Option name.
		 * @return void
		 */
		public static function flush_option_caches( $key ) {
			wp_cache_delete( $key, 'options' );
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_delete( 'notoptions', 'options' );
			$core = self::core();
			if ( $core && isset( $core->options ) && is_array( $core->options ) && array_key_exists( $key, $core->options ) ) {
				unset( $core->options[ $key ] );
			}
		}

		/**
		 * Acquire an exclusive lock with 10-minute stale recovery.
		 *
		 * @param string $key  Lock option name.
		 * @param string $busy Message when lock is held.
		 * @return array Lock state tuple.
		 * @throws RuntimeException When already locked.
		 */
		public static function lock( $key, $busy ) {
			$absent = array(
				'exists' => false,
				'raw'    => '',
			);
			$mine   = self::value_state(
				array(
					't'  => time(),
					'id' => bin2hex( random_bytes( 8 ) ),
				)
			);
			if ( self::compare_exchange( $key, $absent, $mine, array( $key ) ) ) {
				return $mine;
			}
			$held = self::read_state( $key );
			$data = self::state_value( $held );
			if ( $held['exists'] && is_array( $data ) && isset( $data['t'] ) && (int) $data['t'] < time() - 600 && self::compare_exchange( $key, $held, $mine, array( $key ) ) ) {
				return $mine;
			}
			throw new RuntimeException( esc_html( $busy ), 409 );
		}

		/**
		 * Release an acquired lock.
		 *
		 * @param string $key  Lock option name.
		 * @param array  $mine Lock state tuple.
		 * @return void
		 */
		public static function unlock( $key, $mine ) {
			self::compare_exchange(
				$key,
				$mine,
				array(
					'exists' => false,
					'raw'    => '',
				)
			);
		}

		/**
		 * Snapshot current DB states for a list of option keys.
		 *
		 * @param array $option_keys Option names.
		 * @return array<string, array>
		 */
		public static function snapshot_options( $option_keys ) {
			$states = array();
			foreach ( $option_keys as $key ) {
				$states[ $key ] = self::read_state( $key );
			}
			return $states;
		}

		/**
		 * Revision history option key for a module or editor slug.
		 *
		 * @param string $slug Module or editor slug.
		 * @return string
		 */
		public static function revision_option_key( $slug ) {
			return 'wcj_v10_rev_' . sanitize_key( $slug );
		}

		/**
		 * Return stored revisions (oldest to newest), capped to MAX_REVISIONS.
		 *
		 * @param string $slug Module or editor slug.
		 * @return array
		 */
		public static function get_revisions( $slug ) {
			$state = self::read_state( self::revision_option_key( $slug ) );
			if ( empty( $state['exists'] ) ) {
				return array();
			}
			$val = self::state_value( $state );
			return is_array( $val ) ? array_values( $val ) : array();
		}

		/**
		 * Record a new revision snapshot (stored with autoload='no').
		 *
		 * @param string $slug          Module or editor slug.
		 * @param string $summary       Human summary of changes.
		 * @param array  $before_states Map of option_key => state tuple before write.
		 * @param array  $after_states  Map of option_key => state tuple after write.
		 * @return array Recorded revision entry.
		 */
		public static function record_revision( $slug, $summary, $before_states, $after_states ) {
			$rev_key   = self::revision_option_key( $slug );
			$cur_state = self::read_state( $rev_key );
			$revs      = ! empty( $cur_state['exists'] ) && is_array( self::state_value( $cur_state ) )
				? array_values( self::state_value( $cur_state ) )
				: array();

			$after_hashes = array();
			foreach ( $after_states as $opt_key => $st ) {
				$after_hashes[ $opt_key ] = self::state_hash( $st );
			}

			$entry = array(
				'id'           => 'rev_' . bin2hex( random_bytes( 6 ) ),
				'timestamp'    => gmdate( 'c' ),
				'user_id'      => get_current_user_id(),
				'summary'      => (string) $summary,
				'before'       => $before_states,
				'after_hashes' => $after_hashes,
			);

			$revs[] = $entry;
			if ( count( $revs ) > self::MAX_REVISIONS ) {
				$revs = array_slice( $revs, -self::MAX_REVISIONS );
			}

			self::compare_exchange( $rev_key, $cur_state, self::value_state( $revs ), array( $rev_key ) );
			return $entry;
		}

		/**
		 * Roll back the latest revision only if no external edit modified any tracked option.
		 *
		 * @param string $slug        Module or editor slug.
		 * @param string $revision_id Optional specific revision ID (must match latest).
		 * @return array Remaining revisions after rollback.
		 * @throws RuntimeException On missing revision (404) or external conflict (409).
		 */
		public static function rollback_revision( $slug, $revision_id = '' ) {
			$lock_key = 'wcj_v10_lock_' . sanitize_key( $slug );
			$mine     = self::lock( $lock_key, __( 'Another save or undo is already in progress.', 'woocommerce-jetpack' ) );

			try {
				$rev_key   = self::revision_option_key( $slug );
				$rev_state = self::read_state( $rev_key );
				$revs      = ! empty( $rev_state['exists'] ) && is_array( self::state_value( $rev_state ) )
					? array_values( self::state_value( $rev_state ) )
					: array();

				if ( empty( $revs ) ) {
					throw new RuntimeException( esc_html__( 'No saved revision available to undo.', 'woocommerce-jetpack' ), 404 );
				}

				$latest = $revs[ count( $revs ) - 1 ];
				if ( '' !== $revision_id && $latest['id'] !== $revision_id ) {
					throw new RuntimeException( esc_html__( 'Only the most recent published change can be undone directly.', 'woocommerce-jetpack' ), 409 );
				}

				$current_states = array();
				foreach ( $latest['after_hashes'] as $opt_key => $expected_hash ) {
					$cur                        = self::read_state( $opt_key );
					$current_states[ $opt_key ] = $cur;
					if ( self::state_hash( $cur ) !== $expected_hash ) {
						throw new RuntimeException(
							esc_html__( 'Settings were modified externally after this publish. Reload to review current live values before changing.', 'woocommerce-jetpack' ),
							409
						);
					}
				}

				foreach ( $latest['before'] as $opt_key => $before_state ) {
					$cur = isset( $current_states[ $opt_key ] ) ? $current_states[ $opt_key ] : self::read_state( $opt_key );
					if ( ! self::compare_exchange( $opt_key, $cur, $before_state ) ) {
						throw new RuntimeException(
							esc_html__( 'Concurrent setting change detected during undo. Please reload.', 'woocommerce-jetpack' ),
							409
						);
					}
				}

				array_pop( $revs );
				$next_rev_state = empty( $revs )
					? array(
						'exists' => false,
						'raw'    => '',
					)
					: self::value_state( $revs );
				self::compare_exchange( $rev_key, $rev_state, $next_rev_state, array( $rev_key ) );

				return $revs;
			} finally {
				self::unlock( $lock_key, $mine );
			}
		}
	}

endif;

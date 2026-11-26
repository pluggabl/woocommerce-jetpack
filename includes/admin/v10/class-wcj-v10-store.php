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
		 * Authoritative runtime tier probe matching Booster's booster_option gate.
		 *
		 * @return bool True when Plus or Elite filters are unlocked at runtime.
		 */
		public static function is_pro() {
			return true === apply_filters( 'booster_option', false, true );
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
		 * @param string $key              Option name.
		 * @param array  $before           Expected current state.
		 * @param array  $after            Target state.
		 * @param array  $no_autoload      Keys that should insert with autoload='no'.
		 * @param bool   $flush_alloptions Whether to delete the 'alloptions' cache group entry.
		 * @return bool
		 */
		public static function compare_exchange( $key, $before, $after, $no_autoload = array(), $flush_alloptions = true ) {
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
			self::flush_option_caches( $key, $flush_alloptions );
			return 1 === $result;
		}

		/**
		 * Flush WordPress and Booster caches for an option key.
		 *
		 * @param string $key              Option name.
		 * @param bool   $flush_alloptions Whether to also delete 'alloptions'.
		 * @return void
		 */
		public static function flush_option_caches( $key, $flush_alloptions = true ) {
			wp_cache_delete( $key, 'options' );
			if ( $flush_alloptions ) {
				wp_cache_delete( 'alloptions', 'options' );
			}
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
			throw new RuntimeException( (string) $busy, 409 );
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
		 * Compute deterministic state_hash map from an option state snapshot.
		 *
		 * @param array<string, array> $states Snapshot map from snapshot_options().
		 * @return array<string, string>
		 */
		public static function hashes_from_snapshot( $states ) {
			$hashes = array();
			foreach ( $states as $key => $st ) {
				$hashes[ $key ] = self::state_hash( $st );
			}
			return $hashes;
		}

		/**
		 * Compare-and-swap write a batch of options atomically; rolls back prior writes against expected after-states on conflict.
		 *
		 * @param array<string, array> $before_states Pre-write option state tuples keyed by option name.
		 * @param array<string, mixed> $option_writes Map of option name => new PHP value.
		 * @param array<string>        $no_autoload   Optional option keys inserted with autoload='no'.
		 * @return array{ok: bool, after_states: array<string, array>}
		 */
		public static function atomic_write_batch( $before_states, $option_writes, $no_autoload = array() ) {
			$written_before = array();
			$written_after  = array();
			$after_states   = array();

			foreach ( $option_writes as $opt_key => $new_val ) {
				$before_st = isset( $before_states[ $opt_key ] ) ? $before_states[ $opt_key ] : self::read_state( $opt_key );
				$after_st  = self::value_state( $new_val );
				if ( ! self::compare_exchange( $opt_key, $before_st, $after_st, $no_autoload, false ) ) {
					// Revert only keys matching what we wrote so concurrent mutations are never clobbered.
					foreach ( array_reverse( $written_before, true ) as $undo_key => $undo_before ) {
						self::compare_exchange( $undo_key, $written_after[ $undo_key ], $undo_before, $no_autoload, false );
					}
					wp_cache_delete( 'alloptions', 'options' );
					return array(
						'ok'           => false,
						'after_states' => array(),
					);
				}
				$written_before[ $opt_key ] = $before_st;
				$written_after[ $opt_key ]  = $after_st;
				$after_states[ $opt_key ]   = self::read_state( $opt_key );
			}

			if ( ! empty( $option_writes ) ) {
				wp_cache_delete( 'alloptions', 'options' );
				foreach ( $option_writes as $opt_key => $new_val ) {
					$old_val = self::state_value( isset( $written_before[ $opt_key ] ) ? $written_before[ $opt_key ] : array( 'exists' => false, 'raw' => '' ) );
					do_action( "update_option_{$opt_key}", $old_val, $new_val, $opt_key );
					do_action( 'updated_option', $opt_key, $old_val, $new_val );
				}
			}

			return array(
				'ok'           => true,
				'after_states' => $after_states,
			);
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
		 * Roll back to the target revision (unwinding LIFO cleanly) only if no external edit occurred.
		 * Volatile runtime keys (like sequential order counters) never trigger false 409s and are never rewound once live orders change them.
		 *
		 * @param string             $slug             Module or editor slug.
		 * @param string             $revision_id      Optional specific revision ID (defaults to latest).
		 * @param array<int, string> $volatile_keys    Volatile option keys excluded from continuity checks and stale rewinding.
		 * @param array<int, string> $skipped_volatile Output list of volatile keys left untouched because live orders modified them.
		 * @return array Remaining revisions after rollback.
		 * @throws RuntimeException On missing revision (404) or external conflict (409).
		 */
		public static function rollback_revision( $slug, $revision_id = '', $volatile_keys = array(), &$skipped_volatile = array() ) {
			$skipped_volatile = array();
			$volatile_map     = array_flip( array_map( 'strval', (array) $volatile_keys ) );
			$lock_key         = 'wcj_v10_lock_' . sanitize_key( $slug );
			$mine             = self::lock( $lock_key, __( 'Another save or undo is already in progress.', 'woocommerce-jetpack' ) );

			try {
				$rev_key   = self::revision_option_key( $slug );
				$rev_state = self::read_state( $rev_key );
				$revs      = ! empty( $rev_state['exists'] ) && is_array( self::state_value( $rev_state ) )
					? array_values( self::state_value( $rev_state ) )
					: array();

				if ( empty( $revs ) ) {
					throw new RuntimeException( __( 'No saved revision available to undo.', 'woocommerce-jetpack' ), 404 );
				}

				$target_idx = count( $revs ) - 1;
				if ( '' !== $revision_id ) {
					$found = false;
					for ( $i = count( $revs ) - 1; $i >= 0; $i-- ) {
						if ( isset( $revs[ $i ]['id'] ) && $revs[ $i ]['id'] === $revision_id ) {
							$target_idx = $i;
							$found      = true;
							break;
						}
					}
					if ( ! $found ) {
						throw new RuntimeException( __( 'Selected revision could not be found.', 'woocommerce-jetpack' ), 404 );
					}
				}

				// Verify per-key continuity across every step from newest down to target_idx (catches non-adjacent gaps).
				$current_states     = array();
				$expected_next_hash = array();
				$target_restore     = array();
				$newest_after_hash  = array();

				for ( $idx = count( $revs ) - 1; $idx >= $target_idx; $idx-- ) {
					$step = $revs[ $idx ];
					foreach ( $step['before'] as $opt_key => $before_state ) {
						$is_volatile = isset( $volatile_map[ $opt_key ] );
						if ( ! array_key_exists( $opt_key, $current_states ) ) {
							$cur                        = self::read_state( $opt_key );
							$current_states[ $opt_key ] = $cur;
							if ( isset( $step['after_hashes'][ $opt_key ] ) ) {
								$newest_after_hash[ $opt_key ] = $step['after_hashes'][ $opt_key ];
								if ( ! $is_volatile && self::state_hash( $cur ) !== $step['after_hashes'][ $opt_key ] ) {
									throw new RuntimeException(
										__( 'Settings were modified externally after this publish. Reload to review current live values before changing.', 'woocommerce-jetpack' ),
										409
									);
								}
							}
						} elseif ( ! $is_volatile && isset( $expected_next_hash[ $opt_key ], $step['after_hashes'][ $opt_key ] ) && $step['after_hashes'][ $opt_key ] !== $expected_next_hash[ $opt_key ] ) {
							throw new RuntimeException(
								__( 'Settings were modified between these revisions (for example in Classic View). Undo the newest step or review live settings first.', 'woocommerce-jetpack' ),
								409
							);
						}

						$expected_next_hash[ $opt_key ] = self::state_hash( $before_state );
						$target_restore[ $opt_key ]     = $before_state;
					}
				}

				// Filter volatile keys: never rewind live sequential counters or undo if checkout orders moved them.
				foreach ( array_keys( $target_restore ) as $opt_key ) {
					if ( ! isset( $volatile_map[ $opt_key ] ) ) {
						continue;
					}
					$cur_st         = isset( $current_states[ $opt_key ] ) ? $current_states[ $opt_key ] : self::read_state( $opt_key );
					$before_st      = $target_restore[ $opt_key ];
					$hash_unchanged = isset( $newest_after_hash[ $opt_key ] ) && self::state_hash( $cur_st ) === $newest_after_hash[ $opt_key ];
					$cur_val        = self::state_value( $cur_st );
					$before_val     = ! empty( $before_st['exists'] ) ? self::state_value( $before_st ) : null;
					$counter_drop   = false !== strpos( $opt_key, 'counter' ) && is_numeric( $cur_val ) && ( empty( $before_st['exists'] ) || ! is_numeric( $before_val ) || (int) $before_val < (int) $cur_val );

					if ( ! $hash_unchanged || $counter_drop ) {
						unset( $target_restore[ $opt_key ] );
						$skipped_volatile[] = $opt_key;
					}
				}

				$written_undo = array();
				foreach ( $target_restore as $opt_key => $before_state ) {
					$cur = isset( $current_states[ $opt_key ] ) ? $current_states[ $opt_key ] : self::read_state( $opt_key );
					if ( ! self::compare_exchange( $opt_key, $cur, $before_state, array(), false ) ) {
						foreach ( array_reverse( $written_undo, true ) as $revert_key => $pair ) {
							self::compare_exchange( $revert_key, $pair['after'], $pair['before'], array(), false );
						}
						wp_cache_delete( 'alloptions', 'options' );
						throw new RuntimeException(
							__( 'Concurrent setting change detected during undo. Please reload.', 'woocommerce-jetpack' ),
							409
						);
					}
					$written_undo[ $opt_key ] = array(
						'before' => $cur,
						'after'  => $before_state,
					);
				}

				if ( ! empty( $written_undo ) ) {
					wp_cache_delete( 'alloptions', 'options' );
					foreach ( $written_undo as $opt_key => $pair ) {
						$old_val = self::state_value( $pair['before'] );
						$new_val = self::state_value( $pair['after'] );
						do_action( "update_option_{$opt_key}", $old_val, $new_val, $opt_key );
						do_action( 'updated_option', $opt_key, $old_val, $new_val );
					}
				}

				$remaining      = array_slice( $revs, 0, $target_idx );
				$next_rev_state = empty( $remaining )
					? array(
						'exists' => false,
						'raw'    => '',
					)
					: self::value_state( $remaining );
				self::compare_exchange( $rev_key, $rev_state, $next_rev_state, array( $rev_key ) );

				return $remaining;
			} finally {
				self::unlock( $lock_key, $mine );
			}
		}
	}

endif;

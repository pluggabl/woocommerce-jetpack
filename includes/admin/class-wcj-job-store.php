<?php
/**
 * Small storage seam shared by the guided jobs (branded invoice, checkout question): exact persisted
 * option state, conditional writes and a per-job lock. Lifted unchanged from the invoice job.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Job_Store' ) ) :

	/**
	 * Option state is { exists: bool, raw: string }; absence is distinct from an empty value.
	 */
	class WCJ_Job_Store {

		/**
		 * Edition seam: the Booster singleton that this edition's wcj_get_option() caches into.
		 * Free exposes w_c_j(); Plus and Elite expose WCJ(). No alias is defined.
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
		 * Read option bypassing caches.
		 *
		 * @param string $key Option.
		 * @return array
		 */
		public static function read_state( $key ) {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return $row ? array(
				'exists' => true,
				'raw'    => (string) $row['option_value'],
			) : array(
				'exists' => false,
				'raw'    => '',
			);
		}

		/**
		 * State for a value.
		 *
		 * @param mixed $value Value.
		 * @return array
		 */
		public static function value_state( $value ) {
			return array(
				'exists' => true,
				'raw'    => (string) maybe_serialize( $value ),
			);
		}

		/**
		 * Value from state.
		 *
		 * @param array $state State.
		 * @return mixed
		 */
		public static function state_value( $state ) {
			return $state['exists'] ? maybe_unserialize( $state['raw'] ) : null;
		}

		/**
		 * Fingerprint of a persisted state.
		 *
		 * @param array $state State.
		 * @return string
		 */
		public static function state_hash( $state ) {
			return hash( 'sha256', ( $state['exists'] ? '1' : '0' ) . "\0" . $state['raw'] );
		}

		/**
		 * Conditional write: only succeeds if the stored value still equals $before.
		 * New rows of $no_autoload keys are inserted with autoload = 'no'; other new rows keep the native 'yes'.
		 * UPDATE never changes an existing row's autoload.
		 *
		 * @param string $key Option.
		 * @param array  $before Expected state.
		 * @param array  $after Desired state.
		 * @param array  $no_autoload Keys inserted without autoload.
		 * @return bool
		 */
		public static function compare_exchange( $key, $before, $after, $no_autoload = array() ) {
			global $wpdb;
			if ( $before === $after ) {
				return self::read_state( $key ) === $before;
			}
			if ( ! $before['exists'] ) {
				$autoload = in_array( $key, $no_autoload, true ) ? 'no' : 'yes';
				$result   = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)", $key, $after['raw'], $autoload ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			} elseif ( ! $after['exists'] ) {
				$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = %s", $key, $before['raw'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			} else {
				$result = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s", $after['raw'], $key, $before['raw'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			wp_cache_delete( $key, 'options' );
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_delete( 'notoptions', 'options' );
			$core = self::core();
			if ( $core && isset( $core->options[ $key ] ) ) {
				unset( $core->options[ $key ] );
			}
			return 1 === $result;
		}

		/**
		 * Acquire a job lock; an abandoned lock older than ten minutes may be replaced exactly.
		 * The lock row is always inserted without autoload.
		 *
		 * @param string $key Lock option.
		 * @param string $busy Message when held.
		 * @return array Lock state.
		 * @throws RuntimeException When busy.
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
			throw new RuntimeException( esc_html( $busy ) );
		}

		/**
		 * Release a lock.
		 *
		 * @param string $key Lock option.
		 * @param array  $mine Lock state.
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
	}

endif;

<?php
/**
 * Actual private-store SQL protocol fixture. No server or SQL engine is used.
 *
 * @package Booster_For_WooCommerce
 */
if ( 'cli' !== PHP_SAPI || ! defined( 'WCJ_RETURNS_GUEST_OFFLINE' ) || extension_loaded( 'mysqli' ) ) {
	exit( 2 );
}

/** Explicit no-network connection double for actual auth-store statements. */
class WCJ_Guest_SQL_Connection extends WCJ_Returns_Test_Connection {
	public $auth = array();
	public $columns;
	public $indexes;
	public $present      = true;
	public $engine       = 'InnoDB';
	public $collation    = 'ascii_bin';
	public $trace        = array();
	public $affected     = null;
	public $snapshot     = null;
	public $delay_read   = false;
	public $delay_commit = false;
	public function __construct( $db ) {
		parent::__construct( $db );
		$this->columns = array();
		foreach ( array(
			'row_key'     => 'char(64)',
			'kind'        => 'varchar(10)',
			'order_id'    => 'bigint(20) unsigned',
			'expires_gmt' => 'datetime',
			'payload'     => 'varbinary(2048)',
		) as $name => $type ) {
			$this->columns[] = array(
				'Field'     => $name,
				'Type'      => $type,
				'Null'      => 'NO',
				'Extra'     => '',
				'Collation' => 'row_key' === $name ? 'ascii_bin' : null,
			);
		}
		$this->indexes = array(
			array(
				'Key_name'     => 'PRIMARY',
				'Non_unique'   => '0',
				'Seq_in_index' => '1',
				'Column_name'  => 'row_key',
				'Sub_part'     => null,
			),
			array(
				'Key_name'     => 'expires',
				'Non_unique'   => '1',
				'Seq_in_index' => '1',
				'Column_name'  => 'expires_gmt',
				'Sub_part'     => null,
			),
		);
	}
	public function rows( $sql ) {
		$this->trace[] = $sql;
		if ( false === strpos( str_replace( '\\_', '_', $sql ), 'return_auth' ) ) {
			return parent::rows( $sql ); }
		if ( 0 === strpos( $sql, 'SHOW TABLE STATUS' ) ) {
			return $this->present ? array(
				array(
					'Name'   => 'wp_wcj_return_auth',
					'Engine' => $this->engine,
				),
			) : array(); }
		if ( 0 === strpos( $sql, 'SHOW COLUMNS' ) ) {
			return $this->columns; }
		if ( 0 === strpos( $sql, 'SHOW FULL COLUMNS' ) ) {
			$rows                 = $this->columns;
			$rows[0]['Collation'] = $this->collation;
			return $rows; }
		if ( 0 === strpos( $sql, 'SHOW INDEX' ) ) {
			return $this->indexes; }
		if ( 0 === strpos( $sql, 'SELECT row_key' ) ) {
			return array_slice( array_values( $this->auth ), 0, 10001 ); }
		if ( preg_match( "/^SELECT \* FROM wp_wcj_return_auth WHERE row_key='([a-f0-9]{64})'( FOR UPDATE)?$/D", $sql, $match ) ) {
			if ( $this->delay_read && ! empty( $match[2] ) ) {
				$this->delay_read = false;
				usleep( 20000 ); }
			return isset( $this->auth[ $match[1] ] ) ? array( $this->auth[ $match[1] ] ) : array();
		}
		throw new RuntimeException( 'unrecognized_offline_sql' );
	}
	public function query( $sql ) {
		$this->trace[] = $sql;
		if ( 'START TRANSACTION' === $sql ) {
			$this->snapshot = $this->auth; }
		if ( 'COMMIT' === $sql ) {
			$this->snapshot = null;
			if ( $this->delay_commit ) {
				$this->delay_commit = false;
				usleep( 20000 ); }
		}
		if ( 'ROLLBACK' === $sql && null !== $this->snapshot ) {
			$this->auth     = $this->snapshot;
			$this->snapshot = null; }
		if ( false === strpos( str_replace( '\\_', '_', $sql ), 'return_auth' ) ) {
			return parent::query( $sql ); }
		if ( 0 === strpos( $sql, 'CREATE TABLE' ) ) {
			$this->present = true;
			return 0; }
		if ( null !== $this->affected ) {
			$value          = $this->affected;
			$this->affected = null;
			return $value; }
		if ( preg_match( "/^INSERT INTO wp_wcj_return_auth \(row_key,kind,order_id,expires_gmt,payload\) VALUES \('([a-f0-9]{64})','([a-z]+)',([0-9]+),'([^']+)','([^']+)'\)(.*)$/D", $sql, $match ) ) {
			if ( isset( $this->auth[ $match[1] ] ) ) {
				if ( $match[6] ) {
					return 0;
				} throw new RuntimeException( 'duplicate_offline_row' ); }
			$this->auth[ $match[1] ] = array(
				'row_key'     => $match[1],
				'kind'        => $match[2],
				'order_id'    => $match[3],
				'expires_gmt' => $match[4],
				'payload'     => $match[5],
			);
			return 1;
		}
		if ( preg_match( "/^UPDATE wp_wcj_return_auth SET payload='([^']+)' WHERE row_key='([a-f0-9]{64})' AND payload='([^']+)'$/D", $sql, $match ) ) {
			if ( ! isset( $this->auth[ $match[2] ] ) || $this->auth[ $match[2] ]['payload'] !== $match[3] ) {
				return 0; }
			$this->auth[ $match[2] ]['payload'] = $match[1];
			return 1;
		}
		if ( preg_match( "/^DELETE FROM wp_wcj_return_auth WHERE row_key='([a-f0-9]{64})'$/D", $sql, $match ) ) {
			if ( ! isset( $this->auth[ $match[1] ] ) ) {
				return 0;
			} unset( $this->auth[ $match[1] ] );
			return 1; }
		if ( preg_match( "/^DELETE FROM wp_wcj_return_auth WHERE kind<>'anchor' AND expires_gmt<'([^']+)' ORDER BY expires_gmt,row_key LIMIT 50$/D", $sql, $match ) ) {
			uasort(
				$this->auth,
				function ( $left, $right ) {
					return strcmp( $left['expires_gmt'] . $left['row_key'], $right['expires_gmt'] . $right['row_key'] );
				}
			);
			$n = 0;
			foreach ( $this->auth as $key => $row ) {
				if ( 50 === $n ) {
					break;
				} if ( 'anchor' !== $row['kind'] && $row['expires_gmt'] < $match[1] ) {
					unset( $this->auth[ $key ] );
					++$n; }
			}
			return $n;
		}
		throw new RuntimeException( 'unrecognized_offline_sql' );
	}
}

/** Literal expiry boundary tests, frozen before the deadline source change. */
function wcj_guest_expiry_cases() {
	list( $memory ) = wcj_guest_scenario();
	$db             = new WCJ_Returns_Test_DB();
	$sql            = new WCJ_Guest_SQL_Connection( $db );
	$store          = new WCJ_Returns_Store( $db, $sql );
	$sql->auth      = $memory->auth;
	foreach ( array( 1.0, false, '123', -1 ) as $position => $invalid ) {
		wcj_guest_check(
			'deadline rejects nonfinite/coerced input ' . $position,
			wcj_returns_test_thrown(
				function () use ( $store, $invalid ) {
					$store->transaction( function () {}, hrtime( true ), $invalid );
				}
			),
			'auth_invalid'
		);
	}
	$sql->trace = array();
	wcj_guest_check(
		'expired deadline denies before first private SQL',
		wcj_returns_test_thrown(
			function () use ( $store ) {
				$store->transaction( function () {}, hrtime( true ), hrtime( true ) - 1 );
			}
		),
		'auth_expired'
	);
	wcj_guest_check( 'expired deadline produced no SQL', $sql->trace, array() );
	$sql->delay_read = true;
	wcj_guest_check(
		'grant expiry after lock wait rolls back',
		wcj_returns_test_thrown(
			function () use ( $store ) {
				$store->transaction(
					function () use ( $store ) {
							$store->auth_get( WCJ_Returns_Store::auth_anchor_key(), true );
					},
					hrtime( true ),
					hrtime( true ) + 10000000
				);
			}
		),
		'auth_expired'
	);
	wcj_guest_check( 'lock-wait expiry attempted rollback', end( $sql->trace ), 'ROLLBACK' );
	$sql->trace        = array();
	$sql->delay_commit = true;
	wcj_guest_check(
		'COMMIT completion crossing deadline is unknown',
		wcj_returns_test_thrown(
			function () use ( $store ) {
				$store->transaction( function () {}, hrtime( true ), hrtime( true ) + 10000000 );
			}
		),
		'commit_unknown'
	);
	wcj_guest_check(
		'deadline-crossing COMMIT never replayed',
		count(
			array_filter(
				$sql->trace,
				function ( $statement ) {
					return 'COMMIT' === $statement;
				}
			)
		),
		1
	);
	wcj_guest_check(
		'expiry state clears for next ordinary account transaction',
		$store->transaction(
			function () {
				return 'next';
			}
		),
		'next'
	);
}

/** Run finite actual-store readiness, affected-row and capacity cases. */
function wcj_guest_sql_cases() {
	list( $memory ) = wcj_guest_scenario();
	$db             = new WCJ_Returns_Test_DB();
	$sql            = new WCJ_Guest_SQL_Connection( $db );
	$store          = new WCJ_Returns_Store( $db, $sql );
	$sql->auth      = $memory->auth;
	wcj_guest_check( 'actual auth exact schema plus durable anchor admitted', $store->require_auth_ready()['data']['rows'], 1 );
	$schema = array( 'missing', 'engine', 'short', 'nullable', 'prefix', 'expires', 'collation' );
	foreach ( $schema as $fault ) {
		$conn       = new WCJ_Guest_SQL_Connection( new WCJ_Returns_Test_DB() );
		$conn->auth = $memory->auth;
		if ( 'missing' === $fault ) {
			$conn->present = false; }
		if ( 'engine' === $fault ) {
			$conn->engine = 'MyISAM'; }
		if ( 'short' === $fault ) {
			$conn->columns[0]['Type'] = 'char(32)'; }
		if ( 'nullable' === $fault ) {
			$conn->columns[4]['Null'] = 'YES'; }
		if ( 'prefix' === $fault ) {
			$conn->indexes[0]['Sub_part'] = '32'; }
		if ( 'expires' === $fault ) {
			$conn->indexes[1]['Non_unique'] = '0'; }
		if ( 'collation' === $fault ) {
			$conn->collation = 'ascii_general_ci'; }
		$actual = new WCJ_Returns_Store( new WCJ_Returns_Test_DB(), $conn );
		wcj_guest_check(
			'actual auth schema ' . $fault . ' fails closed',
			wcj_returns_test_thrown(
				function () use ( $actual ) {
					$actual->require_auth_ready();
				}
			),
			'storage_unavailable'
		);
	}
	$key = str_repeat( 'c', 64 );
	$now = $GLOBALS['wcj_returns_test_now'];
	$row = array(
		'row_key'     => $key,
		'kind'        => 'rate',
		'order_id'    => 0,
		'expires_gmt' => gmdate( 'Y-m-d H:i:s', $now + 900 ),
		'payload'     => '{"v":1,"n":1}',
	);
	$store->transaction(
		function () use ( $store, $row, $now ) {
			$anchor = $store->auth_anchor( $now );
			$store->auth_insert( $anchor, $row );
		}
	);
	wcj_guest_check( 'actual auth INSERT increments inclusive anchor count', $store->require_auth_ready()['data']['rows'], 2 );
	wcj_guest_check( 'actual auth INSERT stores exact payload bytes', $store->auth_get( $key )['payload'], '{"v":1,"n":1}' );
	$store->transaction(
		function () use ( $store, $key, $now ) {
			$anchor = $store->auth_anchor( $now );
			$store->auth_delete( $anchor, $key );
		}
	);
	wcj_guest_check( 'actual auth DELETE decrements inclusive count', $store->require_auth_ready()['data']['rows'], 1 );
	wcj_guest_check(
		'actual ordinary admission never recreates missing anchor',
		wcj_returns_test_thrown(
			function () use ( $store, $sql ) {
				unset( $sql->auth[ WCJ_Returns_Store::auth_anchor_key() ] );
				$store->require_auth_ready();
			}
		),
		'auth_unavailable'
	);
	$sql->auth     = $memory->auth;
	$sql->trace    = array();
	$sql->affected = 2;
	wcj_guest_check(
		'actual unexpected INSERT affected count rolls back',
		wcj_returns_test_thrown(
			function () use ( $store, $row, $now ) {
				$store->transaction(
					function () use ( $store, $row ) {
							$anchor = $store->auth_get( WCJ_Returns_Store::auth_anchor_key(), true );
						$store->auth_insert( $anchor, $row );
					}
				);
			}
		),
		'auth_conflict'
	);
	wcj_guest_check( 'affected-count failure retains anchor-only rows', count( $sql->auth ), 1 );
	$sql->auth                           = $memory->auth;
	$anchor_key                          = WCJ_Returns_Store::auth_anchor_key();
	$data                                = json_decode( $sql->auth[ $anchor_key ]['payload'], true );
	$data['rows']                        = 10000;
	$sql->auth[ $anchor_key ]['payload'] = json_encode( $data );
	for ( $i = 1; $i < 10000; ++$i ) {
		$copy                          = $row;
		$copy['row_key']               = hash( 'sha256', 'capacity_' . $i );
		$copy['expires_gmt']           = gmdate( 'Y-m-d H:i:s', $now - 1 );
		$sql->auth[ $copy['row_key'] ] = $copy; }
	$store->transaction(
		function () use ( $store, $row, $now ) {
			$anchor = $store->auth_anchor( $now );
			$store->auth_insert( $anchor, $row );
		}
	);
	wcj_guest_check( 'actual full auth table prunes fifty BEFORE capacity refusal', $store->require_auth_ready()['data']['rows'], 9951 );
	wcj_guest_check( 'actual pruned count matches physical protocol rows', count( $sql->auth ), 9951 );
	wcj_guest_check(
		'actual auth no ordinary full-table recount',
		count(
			array_filter(
				$sql->trace,
				function ( $statement ) {
					return false !== strpos( $statement, 'LIMIT 10001' );
				}
			)
		),
		0
	);
	$db            = new WCJ_Returns_Test_DB();
	$conn          = new WCJ_Guest_SQL_Connection( $db );
	$conn->present = false;
	$actual        = new WCJ_Returns_Store( $db, $conn );
	wcj_returns_test_manager();
	wcj_guest_check(
		'auth setup wrong nonce denied before SQL',
		wcj_returns_test_thrown(
			function () use ( $actual, $now ) {
				$actual->install_auth( 'wrong', $now );
			}
		),
		'forbidden'
	);
	wcj_guest_check( 'denied setup made no private SQL', $conn->trace, array() );
	$actual->install_auth( wcj_returns_test_nonce( 'wcj_returns_guest_storage', 2 ), $now );
	wcj_guest_check( 'explicit nonce tick2 auth setup creates anchor', $actual->require_auth_ready()['data']['rows'], 1 );
	wcj_guest_check(
		'explicit setup consumes next transaction before CREATE',
		array_slice(
			array_values(
				array_filter(
					$conn->trace,
					function ( $statement ) {
						return in_array( $statement, array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'ROLLBACK' ), true ) || 0 === strpos( $statement, 'CREATE TABLE' );
					}
				)
			),
			0,
			3
		),
		array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'ROLLBACK' )
	);
}

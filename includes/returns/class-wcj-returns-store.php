<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Retain the established include path; the companion adapter/exception is intentionally colocated.
/**
 * Private transactional storage for Returns, schema version1.
 *
 * @package Booster_For_WooCommerce
 * @since 9.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wcj-returns-clock.php';

/** Fixed private SQL connection, using the existing handle without wpdb retry. */
class WCJ_Returns_Connection {
	/**
	 * Stock WordPress connection owner.
	 *
	 * @var wpdb Stock WordPress connection owner.
	 */
	private $db;
	/**
	 * Existing handle, never a new connection.
	 *
	 * @var mysqli Existing handle, never a new connection.
	 */
	private $handle;
	/**
	 * Original connection identity.
	 *
	 * @var int Original connection identity.
	 */
	private $thread;

	/**
	 * Admit stock wpdb/mysqlnd only, with no constructor SQL.
	 *
	 * @param wpdb $database Existing WordPress database object.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function __construct( $database ) {
		if ( 'wpdb' !== get_class( $database ) || ! extension_loaded( 'mysqli' ) || 0 !== strpos( mysqli_get_client_info(), 'mysqlnd' ) || ! $database->dbh instanceof mysqli ) { // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_client_info -- Use the captured wpdb mysqli session without reconnect/retry; replacing it can replay a transaction with an unknown COMMIT outcome.
			throw new RuntimeException( 'storage_unavailable' );
		}
		$this->db     = $database;
		$this->handle = $database->dbh;
		$this->thread = mysqli_thread_id( $this->handle ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_thread_id -- Use the captured wpdb mysqli session without reconnect/retry; replacing it can replay a transaction with an unknown COMMIT outcome.
		$this->identity();
	}

	/**
	 * No ping, reconnection, retry, query filter or global report-mode change.
	 *
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	private function identity() {
		if ( $this->db->dbh !== $this->handle || mysqli_thread_id( $this->handle ) !== $this->thread || $this->thread < 1 ) { // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_thread_id -- Use the captured wpdb mysqli session without reconnect/retry; replacing it can replay a transaction with an unknown COMMIT outcome.
			throw new RuntimeException( 'storage_failure' );
		}
	}

	/**
	 * Send one private SQL statement on the original handle, exactly once.
	 *
	 * @param string $sql Fixed or prepared SQL.
	 * @return mysqli_result|int Result or affected rows.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function query( $sql ) {
		try {
			$this->identity();
			$sql    = $this->db->remove_placeholder_escape( $sql );
			$result = mysqli_query( $this->handle, $sql ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_query -- Use the captured wpdb mysqli session without reconnect/retry; replacing it can replay a transaction with an unknown COMMIT outcome.
			$this->identity();
			if ( false === $result ) {
				throw new RuntimeException( 'storage_failure' );
			}
			return true === $result ? mysqli_affected_rows( $this->handle ) : $result; // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_affected_rows -- Use the captured wpdb mysqli session without reconnect/retry; replacing it can replay a transaction with an unknown COMMIT outcome.
		} catch ( Throwable $error ) {
			throw new RuntimeException( 'storage_failure' );
		}
	}

	/**
	 * Fetch direct results, never routed to a replica or stored in object cache.
	 *
	 * @param string $sql Fixed or prepared SQL.
	 * @return array Rows.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function rows( $sql ) {
		$result = $this->query( $sql );
		if ( ! $result instanceof mysqli_result ) {
			throw new RuntimeException( 'storage_failure' );
		}
		try {
			return mysqli_fetch_all( $result, MYSQLI_ASSOC ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_fetch_all -- Use the captured wpdb mysqli session without reconnect/retry; replacing it can replay a transaction with an unknown COMMIT outcome.
		} finally {
			mysqli_free_result( $result ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_free_result -- Use the captured wpdb mysqli session without reconnect/retry; replacing it can replay a transaction with an unknown COMMIT outcome.
		}
	}

	/**

	 * Insert identity from the same existing connection.
	 *
	 * @return int Insert identity from the same existing connection.
	 */
	public function insert_id() {
		$this->identity();
		return (int) mysqli_insert_id( $this->handle ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_insert_id -- Use the captured wpdb mysqli session without reconnect/retry; replacing it can replay a transaction with an unknown COMMIT outcome.
	}
}

/** Two private tables. No Woo order storage or destructive cleanup. */
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- The small internal adapter/exception is coupled to this existing include and is not independently loaded.
/** WCJ Returns Store implementation. */
class WCJ_Returns_Store {
	// phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound
	/**
	 * Database connection.
	 *
	 * @var wpdb Database connection.
	 */
	private $db;
	/**
	 * Requests table.
	 *
	 * @var string Requests table.
	 */
	private $requests;
	/**
	 * Claims table.
	 *
	 * @var string Claims table.
	 */
	private $claims;
	/**
	 * Private guest authentication table.
	 *
	 * @var string Private guest authentication table.
	 */
	private $auth;
	/**
	 * Whether this object owns an active transaction.
	 *
	 * @var bool Whether this object owns an active transaction.
	 */
	private $owned = false;
	/**
	 * Request-local schema verification only.
	 *
	 * @var bool Request-local schema verification only.
	 */
	private $ready = false;
	/**
	 * No-retry private SQL connection.
	 *
	 * @var WCJ_Returns_Connection No-retry private SQL connection.
	 */
	private $connection;
	/**
	 * Start of the five-second monotonic Phase A snapshot.
	 *
	 * @var int|null Start of the five-second monotonic Phase A snapshot.
	 */
	private $started;
	/**
	 * Captured monotonic auth expiry, never actor authority.
	 *
	 * @var int|null Captured monotonic auth expiry, never actor authority.
	 */
	private $expiry;

	/**
	 * Construct a private store.
	 *
	 * @param wpdb|null                   $database Optional isolated test connection.
	 * @param WCJ_Returns_Connection|null $connection Optional offline protocol double.
	 * @param int                         $purpose Fixed business-row namespace, one or two.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function __construct( $database = null, $connection = null, $purpose = 1 ) {
		global $wpdb;
		if ( ! in_array( $purpose, array( 1, 2 ), true ) ) {
			throw new RuntimeException( 'storage_unavailable' );
		}
		$this->purpose = $purpose;
		$this->db      = $database ? $database : $wpdb;
		if ( ! preg_match( '/^[A-Za-z0-9_]+$/D', $this->db->prefix ) ) {
			throw new RuntimeException( 'storage_unavailable' );
		}
		$this->requests   = $this->db->prefix . 'wcj_return_requests';
		$this->claims     = $this->db->prefix . 'wcj_return_claims';
		$this->auth       = $this->db->prefix . 'wcj_return_auth';
		$this->connection = $connection;
	}

	/** Lazy handle admission, so constructors do not query or fail on a drop-in. */
	private function connect() {
		if ( ! $this->connection ) {
			$this->connection = new WCJ_Returns_Connection( $this->db );
		}
	}

	/**
	 * Reject an aged snapshot, including after any lock wait.
	 *
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	private function fresh() {
		$now = WCJ_Returns_Clock::read();
		if ( null !== $this->expiry && $now >= $this->expiry ) {
			throw new RuntimeException( 'auth_expired' );
		}
		if ( null !== $this->started ) {
			if ( ! is_int( $this->started ) || $this->started < 0 || $now < $this->started ) {
				throw new RuntimeException( 'clock_unavailable' );
			}
			if ( $now - $this->started > 5000000000 ) {
				throw new RuntimeException( 'facts_stale' );
			}
		}
	}

	/**
	 * Execute SQL and refuse errors. Table identifiers are constructor-validated.
	 *
	 * @param string $sql Prepared or fixed SQL.
	 * @return int|bool
	 * @throws RuntimeException Storage failure.
	 */
	private function query( $sql ) {
		$this->connect();
		if ( 'ROLLBACK' !== $sql ) {
			$this->fresh();
		}
		$result = $this->connection->query( $sql );
		if ( 'ROLLBACK' !== $sql && 'COMMIT' !== $sql ) {
			$this->fresh();
		}
		return $result;
	}

	/**
	 * Read direct rows and enforce the same pre/post-lock time bound.
	 *
	 * @param string $sql Prepared or fixed SQL.
	 * @return array Rows.
	 */
	private function rows( $sql ) {
		$this->connect();
		$this->fresh();
		$rows = $this->connection->rows( $sql );
		$this->fresh();
		return $rows;
	}

	/**
	 * Require autocommit mode; SET TRANSACTION separately rejects an active TX.
	 *
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	private function admission() {
		$rows = $this->rows( 'SELECT @@SESSION.autocommit AS autocommit' );
		if ( count( $rows ) !== 1 || '1' !== (string) $rows[0]['autocommit'] ) {
			throw new RuntimeException( 'transaction_unavailable' );
		}
	}

	/**
	 * Explicit admin/nonce-controlled initialization caller only.
	 *
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function install() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			throw new RuntimeException( 'forbidden' );
		}
		$present = array();
		$columns = $this->columns();
		foreach ( $columns as $table => $expected ) {
			$rows = $this->rows( $this->db->prepare( 'SHOW TABLE STATUS LIKE %s', $this->db->esc_like( $table ) ) );
			if ( count( $rows ) > 1 || ( $rows && $rows[0]['Name'] !== $table ) ) {
				throw new RuntimeException( 'storage_unavailable' );
			}
			if ( $rows ) {
				$present[] = $table;
			}
		}
		if ( 2 === count( $present ) ) {
			$this->ready = false;
			$this->require_ready();
			return;
		}
		if ( 1 === count( $present ) ) {
			$table = $present[0];
			$this->verify_table( $table, $columns[ $table ] );
			if ( $this->rows( "SELECT order_id FROM {$table} LIMIT 1" ) ) {
				throw new RuntimeException( 'storage_unavailable' );
			}
		}
		$this->admission();
		$this->fresh();
		// This statement fails inside an existing transaction, without committing it.
		$this->connection->query( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED' );
		// Consume the next-transaction setting without an intervening clock check.
		try {
			$this->connection->query( 'START TRANSACTION' );
		} finally {
			$this->connection->query( 'ROLLBACK' );
		}
		$charset = $this->db->get_charset_collate();
		$this->query(
			"CREATE TABLE IF NOT EXISTS {$this->requests} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			purpose tinyint unsigned NOT NULL,
			order_id bigint unsigned NOT NULL,
			actor_id bigint unsigned NOT NULL,
			idempotency char(64) NOT NULL,
			input_hash char(64) NOT NULL,
			status varchar(16) NOT NULL,
			revision bigint unsigned NOT NULL,
			created_gmt datetime NOT NULL,
			updated_gmt datetime NOT NULL,
			payload longtext NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY request_token (purpose,order_id,actor_id,idempotency),
			KEY purpose_id (purpose,id),
			KEY queue (purpose,status,id),
			KEY actor_order (purpose,actor_id,order_id,id)
		) ENGINE=InnoDB {$charset}"
		);
		$this->query(
			"CREATE TABLE IF NOT EXISTS {$this->claims} (
			purpose tinyint unsigned NOT NULL,
			order_id bigint unsigned NOT NULL,
			item_id bigint unsigned NOT NULL,
			reserved bigint unsigned NOT NULL DEFAULT 0,
			facts_hash char(64) NOT NULL,
			needs_review tinyint unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY (purpose,order_id,item_id)
		) ENGINE=InnoDB {$charset}"
		);
		$this->ready = false;
		$this->require_ready();
	}

	/**
	 * Verify private engines, expected columns and admission uniqueness.
	 *
	 * @throws RuntimeException Uninitialized or incompatible schema.
	 */
	public function require_ready() {
		if ( $this->ready ) {
			return;
		}
		foreach ( $this->columns() as $table => $expected ) {
			$this->verify_table( $table, $expected );
		}
		$this->ready = true;
	}

	/**

	 * Fixed schema signature, never a migration target.
	 *
	 * @return array Fixed schema signature, never a migration target.
	 */
	private function columns() {
		return array(
			$this->requests => array(
				'id'          => 'bigint unsigned',
				'purpose'     => 'tinyint unsigned',
				'order_id'    => 'bigint unsigned',
				'actor_id'    => 'bigint unsigned',
				'idempotency' => 'char(64)',
				'input_hash'  => 'char(64)',
				'status'      => 'varchar(16)',
				'revision'    => 'bigint unsigned',
				'created_gmt' => 'datetime',
				'updated_gmt' => 'datetime',
				'payload'     => 'longtext',
			),
			$this->claims   => array(
				'purpose'      => 'tinyint unsigned',
				'order_id'     => 'bigint unsigned',
				'item_id'      => 'bigint unsigned',
				'reserved'     => 'bigint unsigned',
				'facts_hash'   => 'char(64)',
				'needs_review' => 'tinyint unsigned',
			),
		);
	}

	/**
	 * Verify an existing table before admitting it or a partial empty retry.
	 *
	 * @param string $table Constructor-validated table name.
	 * @param array  $expected Exact fixed column signature.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	private function verify_table( $table, $expected ) {
		$rows = $this->rows( $this->db->prepare( 'SHOW TABLE STATUS LIKE %s', $this->db->esc_like( $table ) ) );
		$row  = $rows[0] ?? null;
		if ( count( $rows ) !== 1 || ! is_array( $row ) || $row['Name'] !== $table || 'INNODB' !== strtoupper( $row['Engine'] ) ) {
			throw new RuntimeException( 'storage_unavailable' );
		}
		$actual = $this->rows( "SHOW COLUMNS FROM {$table}" );
		if ( array_column( $actual, 'Field' ) !== array_keys( $expected ) ) {
			throw new RuntimeException( 'storage_unavailable' );
		}
		foreach ( $actual as $column ) {
			$type  = preg_replace( '/\b(bigint|tinyint)\([0-9]+\)/', '$1', strtolower( $column['Type'] ) );
			$extra = $table === $this->requests && 'id' === $column['Field'] ? 'auto_increment' : '';
			if ( $type !== $expected[ $column['Field'] ] || 'NO' !== $column['Null'] || $column['Extra'] !== $extra ) {
				throw new RuntimeException( 'storage_unavailable' );
			}
		}
		$indexes = $this->rows( "SHOW INDEX FROM {$table}" );
		$unique  = array();
		foreach ( (array) $indexes as $index ) {
			if ( 0 === (int) $index['Non_unique'] && null === $index['Sub_part'] ) {
				$unique[ $index['Key_name'] ][ (int) $index['Seq_in_index'] ] = $index['Column_name'];
			}
		}
		$keys = array(
			$this->requests => array( 'request_token', array( 'purpose', 'order_id', 'actor_id', 'idempotency' ) ),
			$this->claims   => array( 'PRIMARY', array( 'purpose', 'order_id', 'item_id' ) ),
			$this->auth     => array( 'PRIMARY', array( 'row_key' ) ),
		);
		if ( ! isset( $keys[ $table ] ) ) {
			throw new RuntimeException( 'storage_unavailable' );
		}
		list( $key, $want ) = $keys[ $table ];
		$have               = $unique[ $key ] ?? array();
		ksort( $have );
		if ( array_values( $have ) !== $want ) {
			throw new RuntimeException( 'storage_unavailable' );
		}
		if ( $table === $this->requests && array_values( $unique['PRIMARY'] ?? array() ) !== array( 'id' ) ) {
			throw new RuntimeException( 'storage_unavailable' );
		}
		if ( $table === $this->auth ) {
			$expires = array();
			foreach ( $indexes as $index ) {
				if ( 'expires' === $index['Key_name'] && 1 === (int) $index['Non_unique'] && null === $index['Sub_part'] ) {
					$expires[ (int) $index['Seq_in_index'] ] = $index['Column_name'];
				}
			}
			ksort( $expires );
			$full = $this->rows( "SHOW FULL COLUMNS FROM {$table}" );
			if ( array_values( $expires ) !== array( 'expires_gmt' ) || count( $full ) !== 5 || 'ascii_bin' !== $full[0]['Collation'] ) {
				throw new RuntimeException( 'storage_unavailable' );
			}
		}
	}

	/**
	 * Own a short READ COMMITTED transaction; never join/commit another caller's.
	 *
	 * @param callable $operation Bounded operation.
	 * @param int|null $started Phase A monotonic start, null for private tests/setup.
	 * @param int|null $expiry Captured monotonic auth expiry, default null for accounts.
	 * @return mixed
	 * @throws Throwable Failure, including unknown commit completion.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function transaction( $operation, $started = null, $expiry = null ) {
		if ( $this->owned ) {
			throw new RuntimeException( 'transaction_unavailable' );
		}
		if ( null !== $expiry && ( ! is_int( $expiry ) || $expiry < 1 ) ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		$this->expiry  = $expiry;
		$this->started = $started;
		try {
			$this->require_ready();
			$this->admission();
			$this->fresh();
			$this->connection->query( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED' );
		} catch ( Throwable $error ) {
			$this->started = null;
			$this->expiry  = null;
			throw $error;
		}
		$this->owned = true;
		try {
			// Consume the next-TX setting even if the snapshot ages after SET.
			// No private business statement is allowed until the following guard.
			$this->connection->query( 'START TRANSACTION' );
			$this->fresh();
			$result = $operation();
			$this->fresh();
		} catch ( Throwable $error ) {
			$this->owned = false;
			try {
				$this->query( 'ROLLBACK' );
			} finally {
				$this->started = null;
				$this->expiry  = null;
			}
			throw $error;
		}
		// A failed COMMIT is unknown, not a safe automatic replay or new insert.
		$this->owned = false;
		try {
			$this->connection->query( 'COMMIT' );
			// A response crossing auth expiry after successful COMMIT is unknown,
			// never a claim that an already committed write was rolled back.
			if ( null !== $expiry ) {
				$this->fresh();
			} else {
				WCJ_Returns_Clock::read();
			}
		} catch ( Throwable $error ) {
			try {
				// Same original no-retry handle only. This is cleanup, not evidence
				// that the COMMIT did not happen, and never permits another send.
				$this->query( 'ROLLBACK' );
			} catch ( Throwable $cleanup_error ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Deliberately retain the safe fallback/held state and never expose private exception details or replay an uncertain mutation.
				// Lost/unusable original handle: retain the unknown outcome.
			}
			$this->started = null;
			$this->expiry  = null;
			throw new RuntimeException( 'commit_unknown' );
		}
		$this->started    = null;
			$this->expiry = null;
		return $result;
	}

	/**
	 * Create then lock an order anchor or item in deterministic caller order.
	 *
	 * @param int    $order_id Order ID.
	 * @param int    $item_id Item ID, zero for the order anchor.
	 * @param string $hash Initial facts hash.
	 * @return array
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function lock_claim( $order_id, $item_id, $hash ) {
		if ( ! $this->owned ) {
			throw new RuntimeException( 'transaction_required' );
		}
		$this->query( $this->db->prepare( "INSERT INTO {$this->claims} (purpose,order_id,item_id,reserved,facts_hash,needs_review) VALUES (%d,%d,%d,0,%s,0) ON DUPLICATE KEY UPDATE item_id=item_id", $this->purpose, $order_id, $item_id, $hash ) );
		$rows = $this->rows( $this->db->prepare( "SELECT * FROM {$this->claims} WHERE purpose=%d AND order_id=%d AND item_id=%d FOR UPDATE", $this->purpose, $order_id, $item_id ) );
		$row  = $rows[0] ?? null;
		if ( ! is_array( $row ) ) {
			throw new RuntimeException( 'storage_failure' );
		}
		return $row;
	}

	/**
	 * Update an already locked claim; no counter overwrite outside a transaction.
	 *
	 * @param int $order_id Order ID.
	 * @param int $item_id Item ID.
	 * @param int $reserved Scaled quantity.
	 * @param int $review Conflict flag.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function claim( $order_id, $item_id, $reserved, $review = 0 ) {
		if ( ! $this->owned || $reserved < 0 || $reserved > WCJ_Returns_Quantity::MAX ) {
			throw new RuntimeException( 'claim_invalid' );
		}
		$this->query( $this->db->prepare( "UPDATE {$this->claims} SET reserved=%d,needs_review=%d WHERE purpose=%d AND order_id=%d AND item_id=%d", $reserved, $review, $this->purpose, $order_id, $item_id ) );
	}

	/**
	 * Find a request by scoped digest while holding the order anchor.
	 *
	 * @param int    $order_id Order ID.
	 * @param int    $actor_id Owner ID.
	 * @param string $token Token digest.
	 * @return array|null
	 */
	public function replay( $order_id, $actor_id, $token ) {
		$rows = $this->rows( $this->db->prepare( "SELECT * FROM {$this->requests} WHERE purpose=%d AND order_id=%d AND actor_id=%d AND idempotency=%s", $this->purpose, $order_id, $actor_id, $token ) );
		$row  = $rows[0] ?? null;
		if ( $row ) {
			$this->payload( $row );
		}
		return $row;
	}

	/**
	 * Read a claim without creating or changing it on a GET view.
	 *
	 * @param int $order_id Order ID.
	 * @param int $item_id Item ID, zero for order anchor.
	 * @return array|null
	 */
	public function read_claim( $order_id, $item_id ) {
		$this->require_ready();
		$rows = $this->rows( $this->db->prepare( "SELECT * FROM {$this->claims} WHERE purpose=%d AND order_id=%d AND item_id=%d", $this->purpose, $order_id, $item_id ) );
		return $rows[0] ?? null;
	}

	/**
	 * Read an indexed request without exposing it to the caller before auth.
	 *
	 * @param int  $id Request ID.
	 * @param bool $lock Lock after the order anchor.
	 * @return array
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function get( $id, $lock = false ) {
		$this->require_ready();
		$rows = $this->rows( $this->db->prepare( "SELECT * FROM {$this->requests} WHERE purpose=%d AND id=%d" . ( $lock ? ' FOR UPDATE' : '' ), $this->purpose, $id ) );
		$row  = $rows[0] ?? null;
		if ( ! is_array( $row ) ) {
			throw new RuntimeException( 'request_unavailable' );
		}
		$this->payload( $row );
		return $row;
	}

	/**
	 * Decode a bounded private payload, never PHP unserialization.
	 *
	 * @param array $row Request row.
	 * @return array
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function payload( $row ) {
		if ( ! isset( $row['purpose'] ) || ! in_array( $row['purpose'], array( 1, 2, '1', '2' ), true ) || (int) $row['purpose'] !== $this->purpose ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		$actor = $row['actor_id'] ?? null;
		if ( ( ! is_int( $actor ) && ! is_string( $actor ) ) || ! preg_match( '/^(0|[1-9][0-9]{0,17})$/D', (string) $actor ) ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		$actor = (int) $actor;
		if ( ! is_string( $row['payload'] ) || strlen( $row['payload'] ) > 32768 ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		$data = json_decode( $row['payload'], true, 12 );
		if ( ! is_array( $data ) || ! isset( $data['items'], $data['history'], $data['outbox'], $data['policy'], $data['reason'] ) || ! is_array( $data['items'] ) || ! is_array( $data['history'] ) || ! is_array( $data['outbox'] ) || ! is_array( $data['policy'] ) || count( $data['items'] ) < 1 || count( $data['items'] ) > 20 || count( $data['history'] ) < 1 || count( $data['history'] ) > 2 || count( $data['outbox'] ) > 4 || ! is_string( $data['reason'] ) || strlen( $data['reason'] ) > 2000 || false === preg_match_all( '/./us', $data['reason'] ) || preg_match_all( '/./us', $data['reason'] ) > 500 ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		$keys = array_keys( $data );
		$want = array( 'items', 'history', 'outbox', 'policy', 'reason' );
		if ( 2 === $this->purpose ) {
			$want[] = 'synthetic';
			if ( $actor < 1 || ! isset( $data['synthetic'] ) ) {
				throw new RuntimeException( 'payload_invalid' );
			}
		}
		if ( 0 === $actor ) {
			$want[] = 'guest';
			$guest  = $data['guest'] ?? null;
			if ( ! is_array( $guest ) || count( $guest ) !== 2 || ! isset( $guest['v'], $guest['owner'] ) || 1 !== $guest['v'] || ! is_string( $guest['owner'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $guest['owner'] ) ) {
				throw new RuntimeException( 'payload_invalid' );
			}
		}
		sort( $keys );
		sort( $want );
		if ( $keys !== $want ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		$policy = $data['policy'];
		if ( ! isset( $policy['days'], $policy['notify'], $policy['version'] ) || ! is_int( $policy['days'] ) || $policy['days'] < 1 || $policy['days'] > 365 || ! is_bool( $policy['notify'] ) || 1 !== $policy['version'] ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		foreach ( $data['items'] as $id => $units ) {
			if ( ! preg_match( '/^[1-9][0-9]{0,17}$/D', (string) $id ) || ! is_int( $units ) || $units < 1 || $units > WCJ_Returns_Quantity::MAX ) {
				throw new RuntimeException( 'payload_invalid' );
			}
		}
		foreach ( $data['history'] as $position => $event ) {
			if ( ! is_array( $event ) || ! isset( $event['status'], $event['actor'], $event['time'] ) || ! is_int( $event['actor'] ) || ( $event['actor'] < 0 || ( 0 === $event['actor'] && ( 0 !== $actor || ( 0 !== $position && 'cancelled' !== $event['status'] ) ) ) ) || ! is_int( $event['time'] ) || $event['time'] < 1 || ( 0 === $position ? 'pending' !== $event['status'] : ! in_array( $event['status'], array( 'approved', 'declined', 'cancelled' ), true ) ) ) {
				throw new RuntimeException( 'payload_invalid' );
			}
		}
		foreach ( $data['outbox'] as $key => $event ) {
			if ( ! is_array( $event ) || ! isset( $event['role'], $event['event'], $event['state'], $event['attempts'], $event['due'], $event['lease'] ) || ! in_array( $event['role'], array( 'customer', 'merchant' ), true ) || ! in_array( $event['event'], array( 'submitted', 'decision' ), true ) || $key !== $event['event'] . '_' . $event['role'] || ! in_array( $event['state'], array( 'pending', 'sending', 'failed', 'accepted', 'ambiguous' ), true ) || ! is_int( $event['attempts'] ) || $event['attempts'] < 0 || $event['attempts'] > 3 || ! is_int( $event['due'] ) || $event['due'] < 1 || ! is_string( $event['lease'] ) || ( 'sending' === $event['state'] ? ! preg_match( '/^[a-f0-9]{64}$/D', $event['lease'] ) : '' !== $event['lease'] ) ) {
				throw new RuntimeException( 'payload_invalid' );
			}
		}
		if ( 2 === $this->purpose ) {
			$this->synthetic_payload( $row, $data );
		}
		return $data;
	}

	/**
	 * Insert a whole bounded request, with all claims in the same transaction.
	 *
	 * @param array $row Header and payload.
	 * @return array
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function insert( $row ) {
		$row['purpose'] = $this->purpose;
		$row['payload'] = $this->encode( $row['payload'], $row );
		if ( ! $this->owned || 1 !== $this->query( $this->db->prepare( "INSERT INTO {$this->requests} (purpose,order_id,actor_id,idempotency,input_hash,status,revision,created_gmt,updated_gmt,payload) VALUES (%d,%d,%d,%s,%s,%s,%d,%s,%s,%s)", $this->purpose, $row['order_id'], $row['actor_id'], $row['idempotency'], $row['input_hash'], $row['status'], $row['revision'], $row['created_gmt'], $row['updated_gmt'], $row['payload'] ) ) ) {
			throw new RuntimeException( 'storage_failure' );
		}
		return $this->get( $this->connection->insert_id(), true );
	}

	/**
	 * Compare-and-update a request revision already locked after its order anchor.
	 *
	 * @param array  $row Current request.
	 * @param array  $payload New payload.
	 * @param string $status New status.
	 * @param int    $now Current UTC epoch.
	 * @return array
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function save( $row, $payload, $status, $now ) {
		$old = $this->payload( $row );
		if ( ( $old['synthetic'] ?? null ) !== ( $payload['synthetic'] ?? null ) ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		$next                = $row;
		$next['status']      = $status;
		$next['revision']    = (int) $row['revision'] + 1;
		$next['updated_gmt'] = gmdate( 'Y-m-d H:i:s', $now );
		$json                = $this->encode( $payload, $next );
		if ( ! $this->owned || 1 !== $this->query( $this->db->prepare( "UPDATE {$this->requests} SET payload=%s,status=%s,revision=revision+1,updated_gmt=%s WHERE purpose=%d AND id=%d AND revision=%d", $json, $status, gmdate( 'Y-m-d H:i:s', $now ), $this->purpose, $row['id'], $row['revision'] ) ) ) {
			throw new RuntimeException( 'revision_conflict' );
		}
		return $this->get( (int) $row['id'], true );
	}

	/**
	 * Encode bounded history, selected items and finite outbox.
	 *
	 * @param array $data Payload.
	 * @param array $row Header context for payload validation.
	 * @return string
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	private function encode( $data, $row ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
		$json = json_encode( $data );
		if ( ! is_string( $json ) || strlen( $json ) > 32768 ) {
			throw new RuntimeException( 'payload_limit' );
		}
		$row['payload'] = $json;
		$this->payload( $row );
		return $json;
	}

	/**
	 * Bounded indexed queue or actor/order history. Callers must authorize rows.
	 *
	 * @param int $actor Owner ID, zero for staff queue.
	 * @param int $order Order ID, zero for any order.
	 * @param int $before Keyset pagination cursor.
	 * @return array
	 */
	public function listing( $actor = 0, $order = 0, $before = PHP_INT_MAX ) {
		$this->require_ready();
		$where = $this->db->prepare( 'purpose=%d AND id < %d', $this->purpose, $before );
		if ( $actor > 0 ) {
			$where .= $this->db->prepare( ' AND actor_id=%d', $actor );
		}
		if ( $order > 0 ) {
			$where .= $this->db->prepare( ' AND order_id=%d', $order );
		}
		return $this->rows( "SELECT * FROM {$this->requests} WHERE {$where} ORDER BY id DESC LIMIT 50" );
	}

	/**
	 * Bound retained requests while holding the order anchor, after replay lookup.
	 *
	 * @param int $order_id Order ID.
	 * @return bool Whether another request fits the fixed100-row limit.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function has_request_room( $order_id ) {
		if ( ! $this->owned ) {
			throw new RuntimeException( 'transaction_required' );
		}
		return $this->retained_count( $order_id ) < 100;
	}

	/**
	 * Read the bounded all-status count. Admission callers already hold the anchor.
	 *
	 * @param int $order_id Order ID.
	 * @return int Bounded retained count.
	 */
	public function retained_count( $order_id ) {
		$this->require_ready();
		return count( $this->rows( $this->db->prepare( "SELECT id FROM {$this->requests} WHERE purpose=%d AND order_id=%d LIMIT 101", $this->purpose, $order_id ) ) );
	}

	/**
	 * Exact guest-only history. Actor zero is never the staff-listing sentinel.
	 *
	 * @param int $order Exact order ID.
	 * @param int $before Last scanned keyset cursor.
	 * @return array Bounded rows, caller filters immutable owner digest.
	 */
	public function guest_listing( $order, $before = PHP_INT_MAX ) {
		$this->require_ready();
		return $this->rows( $this->db->prepare( "SELECT * FROM {$this->requests} WHERE purpose=%d AND actor_id=0 AND order_id=%d AND id<%d ORDER BY id DESC LIMIT 50", $this->purpose, $order, $before ) );
	}

	/**
	 * Immutable business-row namespace, never an HTTP selector.
	 *
	 * @var int Immutable business-row namespace, never an HTTP selector.
	 */
	private $purpose;

	/** Fixed literal exercise name, never a catalogue value. */
	const SYNTHETIC_NAME = 'Synthetic test item';
	/** Fixed literal reason, never customer input. */
	const SYNTHETIC_REASON = 'Synthetic workflow exercise';

	/**
	 * Require the constructor-bound namespace without changing it.
	 *
	 * @param int $purpose Expected finite purpose.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function assert_purpose( $purpose ) {
		if ( $this->purpose !== $purpose ) {
			throw new RuntimeException( 'request_unavailable' );
		}
	}

	/**
	 * Hash of the fixed, versioned exercise template.
	 *
	 * @return string Hash of the fixed, versioned exercise template.
	 */
	public static function synthetic_template() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
		return hash( 'sha256', json_encode( array( 1, 1, 2000000, self::SYNTHETIC_NAME, self::SYNTHETIC_REASON, 'completed', 30, false ) ) );
	}

	/**
	 * A bounded target, with the full digest retained to reject collisions.
	 *
	 * @param int    $blog Captured blog ID.
	 * @param int    $creator Captured staff ID.
	 * @param string $digest Full token digest.
	 * @return int Positive target, not a Woo order identity.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public static function synthetic_target( $blog, $creator, $digest ) {
		if ( PHP_INT_SIZE < 8 || ! is_int( $blog ) || $blog < 1 || $blog > 999999999999999999 || ! is_int( $creator ) || $creator < 1 || $creator > 999999999999999999 || ! is_string( $digest ) || ! preg_match( '/^[a-f0-9]{64}$/D', $digest ) ) {
			throw new RuntimeException( 'input_invalid' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
		$hash = hash( 'sha256', json_encode( array( 'wcj-returns-synthetic-target', 1, 2, $blog, $creator, $digest ) ) );
		return 1 + (int) hexdec( substr( $hash, 0, 14 ) );
	}

	/**
	 * Validate the exact witness and compute its full binding, not authority.
	 *
	 * @param array $witness Retained or privately constructed witness.
	 * @return string Full claim binding.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function synthetic_binding( $witness ) {
		$this->assert_purpose( 2 );
		$want = array( 'v', 'blog', 'creator', 'target', 'token', 'template', 'birth' );
		if ( ! is_array( $witness ) || array_keys( $witness ) !== $want || 1 !== $witness['v'] || ! is_int( $witness['birth'] ) || $witness['birth'] < 1 || $witness['birth'] > 253402300799 || ! is_string( $witness['template'] ) || ! hash_equals( self::synthetic_template(), $witness['template'] ) || ! is_int( $witness['target'] ) || self::synthetic_target( $witness['blog'], $witness['creator'], $witness['token'] ) !== $witness['target'] ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
		return hash( 'sha256', json_encode( array( 'wcj-returns-synthetic-binding', 1, 2, $witness['blog'], $witness['creator'], $witness['target'], $witness['token'], $witness['template'], $witness['birth'] ) ) );
	}

	/**
	 * Validate the synthetic representation without blessing missing data.
	 *
	 * @param array $row Complete immutable header context.
	 * @param array $data Decoded payload.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	private function synthetic_payload( $row, $data ) {
		$witness = $data['synthetic'];
		$this->synthetic_binding( $witness );
		if ( (string) $witness['creator'] !== (string) $row['actor_id'] || (string) ( $row['order_id'] ?? '' ) !== (string) $witness['target'] || ( $row['idempotency'] ?? '' ) !== $witness['token']
			|| array(
				'days'    => 30,
				'notify'  => false,
				'version' => 1,
			) !== $data['policy'] || array() !== $data['outbox'] || self::SYNTHETIC_REASON !== $data['reason']
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
			|| array( 1 ) !== array_keys( $data['items'] ) || $data['items'][1] > 2000000 || hash( 'sha256', json_encode( array( $data['items'], $data['reason'] ) ) ) !== ( $row['input_hash'] ?? '' )
			|| gmdate( 'Y-m-d H:i:s', $witness['birth'] ) !== ( $row['created_gmt'] ?? '' ) || $data['history'][0]['time'] !== $witness['birth'] ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		$last = end( $data['history'] );
		if ( (string) count( $data['history'] ) !== (string) ( $row['revision'] ?? '' ) || ( $row['status'] ?? '' ) !== $last['status'] || $last['time'] < $witness['birth'] || gmdate( 'Y-m-d H:i:s', $last['time'] ) !== ( $row['updated_gmt'] ?? '' ) ) {
			throw new RuntimeException( 'payload_invalid' );
		}
		foreach ( $data['history'] as $event ) {
			if ( $event['actor'] !== $witness['creator'] ) {
				throw new RuntimeException( 'payload_invalid' );
			}
		}
	}

	/**
	 * Lock an existing anchor without creating an orphan on a replay miss.
	 *
	 * @param int $target Synthetic target.
	 * @return array|null Existing anchor.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function existing_synthetic_anchor( $target ) {
		$this->assert_purpose( 2 );
		if ( ! $this->owned ) {
			throw new RuntimeException( 'transaction_required' );
		}
		$rows = $this->rows( $this->db->prepare( "SELECT * FROM {$this->claims} WHERE purpose=%d AND order_id=%d AND item_id=0 FOR UPDATE", $this->purpose, $target ) );
		return $rows[0] ?? null;
	}

	/**
	 * Bounded retained rows for an already admitted private target.
	 *
	 * @param int $target Synthetic target.
	 * @return array At most two request rows.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function synthetic_requests( $target ) {
		$this->assert_purpose( 2 );
		if ( ! $this->owned ) {
			throw new RuntimeException( 'transaction_required' );
		}
		return $this->rows( $this->db->prepare( "SELECT * FROM {$this->requests} WHERE purpose=%d AND order_id=%d LIMIT 2", $this->purpose, $target ) );
	}

	/**
	 * Detect unexpected/missing items without an unbounded claim scan.
	 *
	 * @param int $target Synthetic target.
	 * @return array At most three claim rows.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function synthetic_claims( $target ) {
		$this->assert_purpose( 2 );
		if ( ! $this->owned ) {
			throw new RuntimeException( 'transaction_required' );
		}
		return $this->rows( $this->db->prepare( "SELECT * FROM {$this->claims} WHERE purpose=%d AND order_id=%d ORDER BY item_id LIMIT 3", $this->purpose, $target ) );
	}

	/**
	 * Plain insertion only after absent anchor/request/item admission.
	 *
	 * @param int    $target Synthetic target.
	 * @param string $hash Captured full binding.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function insert_synthetic_anchor( $target, $hash ) {
		$this->assert_purpose( 2 );
		if ( ! $this->owned || ! is_string( $hash ) || ! preg_match( '/^[a-f0-9]{64}$/D', $hash ) || 1 !== $this->query( $this->db->prepare( "INSERT INTO {$this->claims} (purpose,order_id,item_id,reserved,facts_hash,needs_review) VALUES (%d,%d,0,0,%s,0)", $this->purpose, $target, $hash ) ) ) {
			throw new RuntimeException( 'storage_failure' );
		}
	}

	/**
	 * Observe setup state; never repair, create or migrate on a GET.
	 *
	 * @return string ready, initializable, incompatible or unavailable.
	 * @throws RuntimeException Internally rethrown storage mismatch, caught and converted to unavailable.
	 */
	public function setup_state() {
		try {
			$present = array();
			$columns = $this->columns();
			foreach ( $columns as $table => $expected ) {
				$rows = $this->rows( $this->db->prepare( 'SHOW TABLE STATUS LIKE %s', $this->db->esc_like( $table ) ) );
				if ( count( $rows ) > 1 || ( $rows && $rows[0]['Name'] !== $table ) ) {
					return 'incompatible';
				}
				if ( $rows ) {
					$present[] = $table;
				}
			}
			foreach ( $present as $table ) {
				try {
					$this->verify_table( $table, $columns[ $table ] );
				} catch ( RuntimeException $error ) {
					if ( 'storage_unavailable' !== $error->getMessage() ) {
						throw $error;
					}
					return 'incompatible';
				}
			}
			if ( 2 === count( $present ) ) {
				return 'ready';
			}
			if ( 1 === count( $present ) && $this->rows( "SELECT order_id FROM {$present[0]} LIMIT 1" ) ) {
				return 'incompatible';
			}
			return 'initializable';
		} catch ( Throwable $error ) {
			return 'unavailable';
		}
	}

	/** Fixed auth schema, distinct from the unchanged two-table installer. */
	private function auth_columns() {
		return array(
			'row_key'     => 'char(64)',
			'kind'        => 'varchar(10)',
			'order_id'    => 'bigint unsigned',
			'expires_gmt' => 'datetime',
			'payload'     => 'varbinary(2048)',
		);
	}

	/** Stable, nonpersonal installation anchor. It is never pruned. */
	public static function auth_anchor_key() {
		return hash( 'sha256', 'wcj_return_auth_anchor_v1' );
	}

	/**
	 * Exact schema and durable anchor admission, never option/cache fallback.
	 *
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function require_auth_ready() {
		$this->require_ready();
		$this->verify_table( $this->auth, $this->auth_columns() );
		$row = $this->auth_get( self::auth_anchor_key() );
		if ( ! $row || 'anchor' !== $row['kind'] ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		return $row;
	}

	/**
	 * Explicit capability and nonce protected auth setup/repair/expired cleanup.
	 * No ordinary guest operation can recreate the installation anchor.
	 *
	 * @param string $nonce Current user-bound setup nonce.
	 * @param int    $now Captured UTC epoch, before private SQL.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function install_auth( $nonce, $now ) {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
		if ( ! current_user_can( 'manage_woocommerce' ) || ! is_string( $nonce ) || ! in_array( wp_verify_nonce( $nonce, 'wcj_returns_guest_storage' ), array( 1, 2 ), true ) || ! is_int( $now ) || $now < 1 ) {
			throw new RuntimeException( 'forbidden' );
		}
		$this->require_ready();
		$this->admission();
		$this->fresh();
		$this->connection->query( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED' );
		// Consume the next-transaction setting without an intervening clock check.
		try {
			$this->connection->query( 'START TRANSACTION' );
		} finally {
			$this->connection->query( 'ROLLBACK' );
		}
		$this->query( "CREATE TABLE IF NOT EXISTS {$this->auth} (row_key char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,kind varchar(10) NOT NULL,order_id bigint unsigned NOT NULL,expires_gmt datetime NOT NULL,payload varbinary(2048) NOT NULL,PRIMARY KEY (row_key),KEY expires (expires_gmt)) ENGINE=InnoDB " . $this->db->get_charset_collate() );
		$this->verify_table( $this->auth, $this->auth_columns() );
		$this->transaction(
			function () use ( $now ) {
				$key = self::auth_anchor_key();
				// One fixed-key INSERT only in this explicit admin action. Concurrent
				// installers serialize on the same durable row before counting.
				// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
				$data = json_encode(
					array(
						'v'    => 1,
						'rows' => 1,
						'cg'   => array( 0, 0 ),
						'vg'   => array( 0, 0 ),
					)
				);
				$this->query( $this->db->prepare( "INSERT INTO {$this->auth} (row_key,kind,order_id,expires_gmt,payload) VALUES (%s,'anchor',0,'9999-12-31 23:59:59',%s) ON DUPLICATE KEY UPDATE row_key=row_key", $key, $data ) );
				$anchor = $this->auth_get( $key, true );
				if ( ! $anchor || 'anchor' !== $anchor['kind'] ) {
					throw new RuntimeException( 'auth_unavailable' );
				}
				$count = count( $this->rows( "SELECT row_key FROM {$this->auth} LIMIT 10001" ) );
				if ( $count < 1 || $count > 10000 ) {
					throw new RuntimeException( 'auth_unavailable' );
				}
				$payload         = $anchor['data'];
				$payload['rows'] = $count;
				$anchor          = $this->auth_save( $anchor, $payload );
				$this->auth_prune( $anchor, $now );
			}
		);
	}

	/**
	 * Read one digest-indexed auth row, optionally locked by its owning TX.
	 *
	 * @param string $key Full lowercase digest.
	 * @param bool   $lock Whether to lock.
	 * @return array|null Strict decoded row.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function auth_get( $key, $lock = false ) {
		if ( ! is_string( $key ) || ! preg_match( '/^[a-f0-9]{64}$/D', $key ) || ( $lock && ! $this->owned ) ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		$rows = $this->rows( $this->db->prepare( "SELECT * FROM {$this->auth} WHERE row_key=%s" . ( $lock ? ' FOR UPDATE' : '' ), $key ) );
		if ( count( $rows ) > 1 ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		if ( ! $rows ) {
			return null;
		}
		$row         = $rows[0];
		$row['data'] = $this->auth_payload( $row );
		return $row;
	}

	/**
	 * Closed, bounded auth payload and header validation, no unserialization.
	 *
	 * @param array $row Auth row.
	 * @return array Decoded private data.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function auth_payload( $row ) {
		if ( ! isset( $row['row_key'], $row['kind'], $row['order_id'], $row['expires_gmt'], $row['payload'] ) || ! is_string( $row['row_key'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $row['row_key'] ) || ! is_string( $row['payload'] ) || strlen( $row['payload'] ) > 2048 || ( ! is_string( $row['order_id'] ) && ! is_int( $row['order_id'] ) ) || ! preg_match( '/^(0|[1-9][0-9]{0,17})$/D', (string) $row['order_id'] ) || ! is_string( $row['kind'] ) || ! is_string( $row['expires_gmt'] ) || ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $row['expires_gmt'] ) ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $row['expires_gmt'], new DateTimeZone( 'UTC' ) );
		if ( ! $date || $date->format( 'Y-m-d H:i:s' ) !== $row['expires_gmt'] ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		$data = json_decode( $row['payload'], true, 6 );
		$map  = array(
			'anchor'    => array( 'v', 'rows', 'cg', 'vg' ),
			'challenge' => array( 'v', 'owner', 'issued', 'attempts', 'delivery', 'key_tag' ),
			'grant'     => array( 'v', 'owner', 'issued', 'writes', 'key_tag' ),
			'rate'      => array( 'v', 'n' ),
		);
		if ( ! is_array( $data ) || ! isset( $map[ $row['kind'] ], $data['v'] ) || 1 !== $data['v'] ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		$have = array_keys( $data );
		$want = $map[ $row['kind'] ];
		sort( $have );
		sort( $want );
		if ( $have !== $want ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		if ( 'anchor' === $row['kind'] ) {
			if ( self::auth_anchor_key() !== $row['row_key'] || 0 !== (int) $row['order_id'] || '9999-12-31 23:59:59' !== $row['expires_gmt'] || ! is_int( $data['rows'] ) || $data['rows'] < 1 || $data['rows'] > 10000 ) {
				throw new RuntimeException( 'auth_invalid' );
			}
			foreach (
				array(
					'cg' => 500,
					'vg' => 1000,
				) as $field => $limit
			) {
				if ( ! is_array( $data[ $field ] ) || array_keys( $data[ $field ] ) !== array( 0, 1 ) || ! is_int( $data[ $field ][0] ) || $data[ $field ][0] < 0 || ! is_int( $data[ $field ][1] ) || $data[ $field ][1] < 0 || $data[ $field ][1] > $limit ) {
					throw new RuntimeException( 'auth_invalid' );
				}
			}
		} elseif ( 'rate' === $row['kind'] ) {
			if ( 0 !== (int) $row['order_id'] || ! is_int( $data['n'] ) || $data['n'] < 1 || $data['n'] > 30 ) {
				throw new RuntimeException( 'auth_invalid' );
			}
		} else {
			if ( (int) $row['order_id'] < 1 || ! is_int( $data['issued'] ) || $data['issued'] < 1 || $data['issued'] > PHP_INT_MAX - 900 || ! is_string( $data['owner'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $data['owner'] ) || ! is_string( $data['key_tag'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $data['key_tag'] ) || gmdate( 'Y-m-d H:i:s', $data['issued'] + ( 'grant' === $row['kind'] ? 900 : 600 ) ) !== $row['expires_gmt'] ) {
				throw new RuntimeException( 'auth_invalid' );
			}
			if ( 'grant' === $row['kind'] ? ! is_int( $data['writes'] ) || $data['writes'] < 0 || $data['writes'] > 20 : ! is_int( $data['attempts'] ) || $data['attempts'] < 0 || $data['attempts'] > 4 || ! in_array( $data['delivery'], array( 'pending', 'sending', 'accepted', 'ambiguous' ), true ) ) {
				throw new RuntimeException( 'auth_invalid' );
			}
		}
		return $data;
	}

	/**
	 * Compare-and-set one already locked row using exact binary payload bytes.
	 *
	 * @param array $row Current row.
	 * @param array $data Next bounded payload.
	 * @return array Current strict row.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function auth_save( $row, $data ) {
		if ( ! $this->owned ) {
			throw new RuntimeException( 'transaction_required' );
		}
		$next = $row;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
		$next['payload'] = json_encode( $data );
		$this->auth_payload( $next );
		if ( $next['payload'] !== $row['payload'] && 1 !== $this->query( $this->db->prepare( "UPDATE {$this->auth} SET payload=%s WHERE row_key=%s AND payload=%s", $next['payload'], $row['row_key'], $row['payload'] ) ) ) {
			throw new RuntimeException( 'auth_conflict' );
		}
		$next['data'] = $data;
		return $next;
	}

	/**
	 * Lock the existing anchor, prune before any capacity admission.
	 *
	 * @param int $now Captured-plus-monotonic epoch.
	 * @return array Locked anchor.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function auth_anchor( $now ) {
		$anchor = $this->auth_get( self::auth_anchor_key(), true );
		if ( ! $anchor || 'anchor' !== $anchor['kind'] ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		return $this->auth_prune( $anchor, $now );
	}

	/**
	 * Operation-driven bounded expiry cleanup, not guaranteed while disabled.
	 *
	 * @param array $anchor Already locked installation anchor.
	 * @param int   $now Captured-plus-monotonic epoch.
	 * @return array Updated locked anchor.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	private function auth_prune( $anchor, $now ) {
		if ( ! $this->owned ) {
			throw new RuntimeException( 'transaction_required' );
		}
		$deleted = $this->query( $this->db->prepare( "DELETE FROM {$this->auth} WHERE kind<>'anchor' AND expires_gmt<%s ORDER BY expires_gmt,row_key LIMIT 50", gmdate( 'Y-m-d H:i:s', $now ) ) );
		if ( ! is_int( $deleted ) || $deleted < 0 || $deleted > 50 || $anchor['data']['rows'] - $deleted < 1 ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		$data          = $anchor['data'];
		$data['rows'] -= $deleted;
		return $this->auth_save( $anchor, $data );
	}

	/**
	 * Insert after anchor lock, adjusting its exact inclusive affected-row count.
	 *
	 * @param array $anchor Locked anchor, updated by reference.
	 * @param array $row New fixed-kind row.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function auth_insert( &$anchor, $row ) {
		$this->auth_payload( $row );
		if ( ! $this->owned || 'anchor' === $row['kind'] || $anchor['data']['rows'] >= 10000 ) {
			throw new RuntimeException( 'auth_capacity' );
		}
		if ( 1 !== $this->query( $this->db->prepare( "INSERT INTO {$this->auth} (row_key,kind,order_id,expires_gmt,payload) VALUES (%s,%s,%d,%s,%s)", $row['row_key'], $row['kind'], $row['order_id'], $row['expires_gmt'], $row['payload'] ) ) ) {
			throw new RuntimeException( 'auth_conflict' );
		}
		$data = $anchor['data'];
		++$data['rows'];
		$anchor = $this->auth_save( $anchor, $data );
	}

	/**
	 * Delete one existing non-anchor row, accounting actual affected rows.
	 *
	 * @param array  $anchor Locked anchor, updated by reference.
	 * @param string $key Row key.
	 * @throws RuntimeException When an explicit input, storage or transaction invariant is rejected.
	 */
	public function auth_delete( &$anchor, $key ) {
		if ( ! $this->owned || ! is_string( $key ) || ! preg_match( '/^[a-f0-9]{64}$/D', $key ) || self::auth_anchor_key() === $key ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		$deleted = $this->query( $this->db->prepare( "DELETE FROM {$this->auth} WHERE row_key=%s", $key ) );
		if ( ! is_int( $deleted ) || $deleted < 0 || $deleted > 1 || $anchor['data']['rows'] - $deleted < 1 ) {
			throw new RuntimeException( 'auth_invalid' );
		}
		$data          = $anchor['data'];
		$data['rows'] -= $deleted;
		$anchor        = $this->auth_save( $anchor, $data );
	}
}

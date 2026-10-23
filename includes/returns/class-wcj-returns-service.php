<?php
/**
 * Customer-owned Returns intake, finite decisions and durable notification intents.
 *
 * @package Booster_For_WooCommerce
 * @since 9.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wcj-returns-clock.php';

/** Connected Returns behavior. It never writes a Woo order, stock or payment. */
class WCJ_Returns_Service {
	/**
	 * Private store.
	 *
	 * @var WCJ_Returns_Store Private store.
	 */
	private $store;
	/**
	 * Fresh order facts.
	 *
	 * @var WCJ_Returns_Facts Fresh order facts.
	 */
	private $facts;
	/**
	 * UTC epoch provider.
	 *
	 * @var callable UTC epoch provider.
	 */
	private $clock;

	/**
	 * Construct the service. Optional primitives exist for deterministic tests.
	 *
	 * @param WCJ_Returns_Store|null $store Private storage.
	 * @param WCJ_Returns_Facts|null $facts Fresh facts.
	 * @param callable|null          $clock Epoch provider.
	 */
	public function __construct( $store = null, $facts = null, $clock = null ) {
		$this->store = $store;
		$this->facts = $facts ?: new WCJ_Returns_Facts();
		$this->clock = $clock ?: 'time';
	}

	/**
	 * Expose finite diagnostic codes, never raw exceptions/SQL/customer input.
	 *
	 * @param callable $operation Operation.
	 * @return mixed|WP_Error
	 */
	private function run( $operation ) {
		try {
			if ( 'yes' !== get_option( 'wcj_returns_enabled', 'no' ) ) {
				throw new RuntimeException( 'module_disabled' );
			}
			if ( ! $this->store ) {
				$this->store = new WCJ_Returns_Store();
			}
			$this->store->assert_purpose( 1 );
			$result = $operation();
			if ( is_array( $result ) && isset( $result['_wcj_error'] ) ) {
				throw new RuntimeException( $result['_wcj_error'] );
			}
			return $result;
		} catch ( Throwable $error ) {
			$known   = array( 'forbidden', 'nonce_invalid', 'module_disabled', 'quantity_invalid', 'input_invalid', 'policy_invalid', 'not_eligible', 'capacity_exceeded', 'idempotency_conflict', 'revision_conflict', 'transition_invalid', 'needs_review', 'notification_disabled', 'notification_not_due', 'notification_ambiguous', 'recipient_unavailable', 'commit_unknown', 'fresh_cache_unavailable', 'facts_limit', 'facts_stale', 'request_limit' );
			$code    = in_array( $error->getMessage(), $known, true ) ? $error->getMessage() : 'returns_unavailable';
			$message = 'request_limit' === $code ? __( 'This order has reached the limit of 100 retained return requests. Please contact the store.', 'woocommerce-jetpack' ) : __( 'The return request could not be completed. Please contact the store if the problem continues.', 'woocommerce-jetpack' );
			return new WP_Error( $code, $message );
		}
	}

	/**
	 * Verify current capability or exact nonzero owner, never nonce-only auth.
	 *
	 * @param array $facts Fresh facts.
	 * @param bool  $staff Whether staff access is required.
	 */
	private function authorize( $facts, $staff = false ) {
		if ( $staff ) {
			if ( ! get_current_user_id() || ! current_user_can( 'manage_woocommerce' ) ) {
				throw new RuntimeException( 'forbidden' );
			}
		} elseif ( ! get_current_user_id() || $facts['owner'] <= 0 || (int) get_current_user_id() !== $facts['owner'] ) {
			throw new RuntimeException( 'forbidden' );
		}
	}

	/**
	 * Verify a current user-bound nonce, accepting WP ticks1 and2.
	 *
	 * @param mixed  $nonce Nonce.
	 * @param string $action Action binding.
	 */
	private function nonce( $nonce, $action ) {
		if ( ! is_string( $nonce ) || strlen( $nonce ) > 128 || ! in_array( wp_verify_nonce( $nonce, $action ), array( 1, 2 ), true ) ) {
			throw new RuntimeException( 'nonce_invalid' );
		}
	}

	/**
	 * Reject coercion at service entry, not only in the HTML controller.
	 *
	 * @param mixed $id Order, request or revision identifier.
	 */
	private function identifier( $id ) {
		if ( ! is_int( $id ) || $id < 1 || $id > 999999999999999999 ) {
			throw new RuntimeException( 'input_invalid' );
		}
	}

	/**
	 * Resolve one storewide policy. Invalid saved values never default silently.
	 *
	 * @return array
	 */
	private function policy() {
		$days = get_option( 'wcj_returns_window_days', '30' );
		if ( ( ! is_string( $days ) && ! is_int( $days ) ) || ! preg_match( '/^[1-9][0-9]{0,2}$/D', (string) $days ) || (int) $days > 365 ) {
			throw new RuntimeException( 'policy_invalid' );
		}
		return array(
			'days'    => (int) $days,
			'notify'  => 'yes' === get_option( 'wcj_returns_notifications', 'no' ),
			'version' => 1,
		);
	}

	/**
	 * Confirm intake eligibility using elapsed UTC seconds, not local dates.
	 *
	 * @param array $facts Order facts.
	 * @param array $policy Store policy.
	 * @param int   $now Current epoch.
	 */
	private function eligible( $facts, $policy, $now ) {
		if ( 'completed' !== $facts['status'] || $facts['completed'] <= 0 || $now < $facts['completed'] || $now - $facts['completed'] > $policy['days'] * DAY_IN_SECONDS || $facts['amount_only'] ) {
			throw new RuntimeException( 'not_eligible' );
		}
	}

	/**
	 * Compare the locked anchor to fresh facts. Never silently reconcile claims.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $facts Current facts.
	 * @return bool Whether manual review is required.
	 */
	private function drift( $order_id, $facts ) {
		$anchor = $this->store->lock_claim( $order_id, 0, $facts['hash'] );
		if ( $anchor['facts_hash'] !== $facts['hash'] || $facts['amount_only'] || (int) $anchor['needs_review'] ) {
			$this->store->claim( $order_id, 0, (int) $anchor['reserved'], 1 );
			return true;
		}
		return false;
	}

	/**
	 * Append exactly two notification roles for each of two finite events.
	 *
	 * @param array  $payload Request payload.
	 * @param string $event submitted or decision.
	 * @param int    $now Epoch.
	 * @return array
	 */
	private function intents( $payload, $event, $now ) {
		if ( $payload['policy']['notify'] ) {
			foreach ( array( 'customer', 'merchant' ) as $role ) {
				$key                       = $event . '_' . $role;
				$payload['outbox'][ $key ] = array(
					'role'     => $role,
					'event'    => $event,
					'state'    => 'pending',
					'attempts' => 0,
					'due'      => $now,
					'lease'    => '',
				);
			}
		}
		return $payload;
	}

	/**
	 * Reserve final encoded space at intake; a terminal decision must fit too.
	 *
	 * @param array $payload Pending payload.
	 */
	private function future_room( $payload ) {
		$payload['history'][] = array(
			'status' => 'cancelled',
			'actor'  => 999999999999999999,
			'time'   => PHP_INT_MAX,
		);
		$payload              = $this->intents( $payload, 'decision', PHP_INT_MAX );
		foreach ( $payload['outbox'] as &$event ) {
			$event['state']    = 'ambiguous';
			$event['attempts'] = 3;
			$event['due']      = PHP_INT_MAX;
			$event['lease']    = str_repeat( 'f', 64 );
		}
		unset( $event );
		$json = json_encode( $payload );
		if ( ! is_string( $json ) || strlen( $json ) > 32768 ) {
			throw new RuntimeException( 'input_invalid' );
		}
	}

	/**
	 * Submit a current customer's request or replay the same authorized input.
	 *
	 * @param int    $order_id Order ID.
	 * @param array  $quantities Selected itemID => decimal quantity.
	 * @param string $reason Plain-text reason.
	 * @param string $token Random rendered token, hex32bytes.
	 * @param string $nonce User-bound nonce.
	 * @return array|WP_Error
	 */
	public function submit( $order_id, $quantities, $reason, $token, $nonce ) {
		return $this->run(
			function () use ( $order_id, $quantities, $reason, $token, $nonce ) {
				$started = WCJ_Returns_Clock::read();
				$this->identifier( $order_id );
				$this->nonce( $nonce, 'wcj_returns_submit_' . $order_id );
				if ( ! get_current_user_id() ) {
						throw new RuntimeException( 'forbidden' );
				}
				$initial = $this->facts->load( $order_id );
				$this->authorize( $initial );
				$selected = $this->selected( $quantities, $reason, $token );
				$actor        = (int) get_current_user_id();
				$input        = hash( 'sha256', json_encode( array( $selected, $reason ) ) );
				$digest       = hash( 'sha256', $token );
				$now          = call_user_func( $this->clock );
				$policy       = null;
				$policy_error = '';
				try {
					$policy = $this->policy();
				} catch ( RuntimeException $error ) {
					$policy_error = $error->getMessage();
				}
				$this->authorize( $initial );
				if ( (int) get_current_user_id() !== $actor ) {
					throw new RuntimeException( 'forbidden' );
				}
				return $this->store->transaction(
					function () use ( $order_id, $selected, $reason, $actor, $input, $digest, $initial, $now, $policy, $policy_error ) {
						return $this->submit_locked( $order_id, $selected, $reason, $actor, $input, $digest, $initial, $now, $policy, $policy_error );
					},
					$started
				);
			}
		);
	}


	/**
	 * One shared bounded input parser for account and proven guest requests.
	 *
	 * @param array $quantities Selected quantities.
	 * @param string $reason Plain text.
	 * @param string $token Rendered random token.
	 * @return array Exact fixed-point selections.
	 */
	private function selected( $quantities, $reason, $token ) {
		if ( ! is_array( $quantities ) || count( $quantities ) < 1 || count( $quantities ) > 20 || ! is_string( $reason ) || strlen( $reason ) > 2000 || false === preg_match_all( '/./us', $reason ) || preg_match_all( '/./us', $reason ) > 500 || sanitize_textarea_field( $reason ) !== $reason || ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) {
			throw new RuntimeException( 'input_invalid' );
		}
		$selected = array();
		foreach ( $quantities as $id => $quantity ) {
			if ( ! preg_match( '/^[1-9][0-9]{0,17}$/D', (string) $id ) || (int) $id < 1 || (string) (int) $id !== (string) $id ) {
				throw new RuntimeException( 'input_invalid' );
			}
			$units = WCJ_Returns_Quantity::parse( $quantity );
			if ( $units < 1 ) {
				throw new RuntimeException( 'quantity_invalid' );
			}
			$selected[ (int) $id ] = $units;
		}
		ksort( $selected, SORT_NUMERIC );

		return $selected;
	}

	/**
	 * One shared private claim engine, entered only after distinct authorization.
	 *
	 * @param int $order_id Order.
	 * @param array $selected Exact quantities.
	 * @param string $reason Bounded reason.
	 * @param int $actor Account actor or separately proven guest zero.
	 * @param string $input Input digest.
	 * @param string $digest Idempotency digest.
	 * @param array $initial Fresh Phase A facts.
	 * @param int $now Captured epoch.
	 * @param array|null $policy Captured policy.
	 * @param string $policy_error Finite policy failure.
	 * @param string $guest_owner Private immutable binding, never a caller flag.
	 * @return array
	 */
	private function submit_locked( $order_id, $selected, $reason, $actor, $input, $digest, $initial, $now, $policy, $policy_error, $guest_owner = '', $synthetic = null ) {
		$this->store->lock_claim( $order_id, 0, $initial['hash'] );
		$facts = $initial;
		$old   = $this->store->replay( $order_id, $actor, $digest );
		if ( $old ) {
			if ( $guest_owner ) {
				$old_data = $this->store->payload( $old );
				if ( ! hash_equals( $old_data['guest']['owner'], $guest_owner ) ) {
					throw new RuntimeException( 'idempotency_conflict' );
				}
			}
			if ( $old['input_hash'] !== $input ) {
				throw new RuntimeException( 'idempotency_conflict' );
			}
			return $old;
		}
		if ( ! $this->store->has_request_room( $order_id ) ) {
			throw new RuntimeException( 'request_limit' );
		}
		if ( $this->drift( $order_id, $facts ) ) {
			// Return rather than throw so the conflict marker is committed.
			return array( '_wcj_error' => 'needs_review' );
		}
		if ( '' !== $policy_error ) {
			throw new RuntimeException( $policy_error );
		}
		$this->eligible( $facts, $policy, $now );
		foreach ( $selected as $id => $units ) {
			$claim = $this->store->lock_claim( $order_id, $id, $facts['hash'] );
			if ( ! isset( $facts['capacities'][ $id ] ) || $claim['facts_hash'] !== $facts['hash'] || $units > $facts['capacities'][ $id ] - (int) $claim['reserved'] ) {
				throw new RuntimeException( 'capacity_exceeded' );
			}
			$this->store->claim( $order_id, $id, (int) $claim['reserved'] + $units );
		}
		$payload = array(
			'items'   => $selected,
			'reason'  => $reason,
			'policy'  => $policy,
			'history' => array(
				array(
					'status' => 'pending',
					'actor'  => $actor,
					'time'   => $now,
				),
			),
			'outbox'  => array(),
		);
		if ( $guest_owner ) {
			$payload['guest'] = array( 'v' => 1, 'owner' => $guest_owner );
		}
		if ( null !== $synthetic ) {
			$this->store->assert_purpose( 2 );
			if ( $guest_owner ) {
				throw new RuntimeException( 'payload_invalid' );
			}
			$this->store->synthetic_binding( $synthetic );
			$payload['synthetic'] = $synthetic;
		} else {
			$this->store->assert_purpose( 1 );
		}
		$payload = $this->intents( $payload, 'submitted', $now );
		$this->future_room( $payload );
		$date = gmdate( 'Y-m-d H:i:s', $now );
		return $this->store->insert(
			array(
				'order_id'    => $order_id,
				'actor_id'    => $actor,
				'idempotency' => $digest,
				'input_hash'  => $input,
				'status'      => 'pending',
				'revision'    => 1,
				'created_gmt' => $date,
				'updated_gmt' => $date,
				'payload'     => $payload,
			)
		);
	}

	/**
	 * One shared private finite transition/reservation-release engine.
	 *
	 * @param int $id Request.
	 * @param int $revision Expected revision.
	 * @param string $status Finite terminal decision.
	 * @param array $row Initial row.
	 * @param array $facts Fresh Phase A facts.
	 * @param bool $staff Captured current capability.
	 * @param int $actor Proven account/staff identity or guest zero.
	 * @param int $now Captured epoch.
	 * @param string $guest_owner Private separately proven binding.
	 * @return array
	 */
	private function transition_locked( $id, $revision, $status, $row, $facts, $staff, $actor, $now, $guest_owner = '' ) {
		$order_id = (int) $row['order_id'];
		$this->store->lock_claim( $order_id, 0, $facts['hash'] );
		$fresh   = $facts;
		$current = $this->store->get( $id, true );
		if ( $guest_owner ) {
			$this->guest_binding( $current, $fresh, $guest_owner );
		}
		if ( (int) $current['order_id'] !== $order_id || ( ! $staff && (int) $current['actor_id'] !== $actor ) ) {
				throw new RuntimeException( 'forbidden' );
		}
		if ( (int) $current['revision'] !== $revision ) {
			throw new RuntimeException( 'revision_conflict' );
		}
		if ( 'pending' !== $current['status'] || ! in_array( $status, array( 'approved', 'declined', 'cancelled' ), true ) ) {
			throw new RuntimeException( 'transition_invalid' );
		}
		$drift = $this->drift( $order_id, $fresh );
		$data = $this->store->payload( $current );
		$owner_matches = (int) $current['actor_id'] === $fresh['owner'];
		if ( 0 === (int) $current['actor_id'] ) {
			$owner_matches = $this->guest_matches( $fresh, $data['guest']['owner'] );
		}
		if ( 'approved' === $status && ( $drift || 'completed' !== $fresh['status'] || ! $owner_matches ) ) {
			return array( '_wcj_error' => 'needs_review' );
		}
		if ( 'approved' !== $status ) {
			foreach ( $data['items'] as $item_id => $units ) {
				$claim = $this->store->lock_claim( $order_id, (int) $item_id, $facts['hash'] );
				if ( (int) $claim['reserved'] < $units ) {
					throw new RuntimeException( 'claim_invalid' );
				}
				$this->store->claim( $order_id, (int) $item_id, (int) $claim['reserved'] - $units, $drift ? 1 : 0 );
			}
		}
		$data['history'][] = array(
			'status' => $status,
			'actor'  => $actor,
			'time'   => $now,
		);
		return $this->store->save( $current, $this->intents( $data, 'decision', $now ), $status, $now );
	}

	/**
	 * Fresh exact guest identity equality is not itself authentication.
	 *
	 * @param array $facts Fresh facts.
	 * @param string $owner Stored immutable digest.
	 * @return bool
	 */
	private function guest_matches( $facts, $owner ) {
		return 0 === $facts['owner'] && ! empty( $facts['guest']['valid'] ) && hash_equals( $facts['guest']['digest'], $owner );
	}

	/**
	 * Require a separately proven binding before guest replay/cancellation.
	 *
	 * @param array $row Exact request.
	 * @param array $facts Fresh facts.
	 * @param string $owner Proven immutable digest.
	 */
	private function guest_binding( $row, $facts, $owner ) {
		$data = $this->store->payload( $row );
		if ( 0 !== (int) $row['actor_id'] || ! $this->guest_matches( $facts, $owner ) || ! hash_equals( $data['guest']['owner'], $owner ) ) {
			throw new RuntimeException( 'forbidden' );
		}
	}

	/**
	 * Submit through the same private engine after cookie, nonce and fresh proof.
	 *
	 * @param string $cookie Grant cookie, not actor/order inputs.
	 * @param array $quantities Selected quantities.
	 * @param string $reason Bounded reason.
	 * @param string $token Rendered random token.
	 * @param string $nonce Cookie/operation-bound WP nonce.
	 * @return array|WP_Error
	 */
	public function guest_submit( $cookie, $quantities, $reason, $token, $nonce ) {
		return $this->run( function () use ( $cookie, $quantities, $reason, $token, $nonce ) {
			$guest = new WCJ_Returns_Guest( $this->store, $this->facts, $this->clock );
			$guest->charge_write( $cookie, 'submit', $nonce );
			$context = $guest->context( $cookie, 'submit', $nonce );
			$facts = $this->facts->load( $context['order'] );
			if ( ! $this->guest_matches( $facts, $context['owner'] ) ) {
				throw new RuntimeException( 'forbidden' );
			}
			$selected = $this->selected( $quantities, $reason, $token );
			$input = hash( 'sha256', json_encode( array( $selected, $reason ) ) );
			$digest = hash( 'sha256', $token );
			$policy = null;
			$policy_error = '';
			try { $policy = $this->policy(); } catch ( RuntimeException $error ) { $policy_error = $error->getMessage(); }
			return $this->store->transaction( function () use ( $guest, $context, $facts, $selected, $reason, $input, $digest, $policy, $policy_error ) {
				$guest->lock();
				return $this->submit_locked( $context['order'], $selected, $reason, 0, $input, $digest, $facts, $context['phase']['now'], $policy, $policy_error, $context['owner'] );
			}, $context['phase']['start'], $context['until'] );
		} );
	}

	/**
	 * Cancel only a bound pending guest request; login or a zero actor is no proof.
	 *
	 * @param string $cookie Grant cookie.
	 * @param int $id Request ID.
	 * @param int $revision Expected revision.
	 * @param string $nonce Cookie/operation-bound nonce.
	 * @return array|WP_Error
	 */
	public function guest_cancel( $cookie, $id, $revision, $nonce ) {
		return $this->run( function () use ( $cookie, $id, $revision, $nonce ) {
			$this->identifier( $id );
			$this->identifier( $revision );
			$guest = new WCJ_Returns_Guest( $this->store, $this->facts, $this->clock );
			$guest->charge_write( $cookie, 'cancel', $nonce );
			$context = $guest->context( $cookie, 'cancel', $nonce );
			$row = $this->store->get( $id );
			if ( (int) $row['order_id'] !== $context['order'] ) { throw new RuntimeException( 'forbidden' ); }
			$facts = $this->facts->load( $context['order'] );
			$this->guest_binding( $row, $facts, $context['owner'] );
			return $this->store->transaction( function () use ( $guest, $context, $row, $facts, $id, $revision ) {
				$guest->lock();
				return $this->transition_locked( $id, $revision, 'cancelled', $row, $facts, false, 0, $context['phase']['now'], $context['owner'] );
			}, $context['phase']['start'], $context['until'] );
		} );
	}

	/**
	 * Shared read-only availability and policy. Authorization is already proven.
	 *
	 * @param int $order_id Order.
	 * @param array $facts Fresh facts.
	 * @param array $rows Exactly authorized history.
	 * @return array
	 */
	private function view_data( $order_id, $facts, $rows ) {
		$policy         = null;
		$policy_invalid = false;
		try {
			$policy = $this->policy();
		} catch ( RuntimeException $error ) {
			if ( 'policy_invalid' !== $error->getMessage() ) {
				throw $error;
			}
			$policy_invalid = true;
		}
		$available = $facts['capacities'];
		$anchor    = $this->store->read_claim( $order_id, 0 );
		$review    = $facts['amount_only'] || ( $anchor && ( $anchor['facts_hash'] !== $facts['hash'] || (int) $anchor['needs_review'] ) );
		foreach ( $available as $item_id => $capacity ) {
			$claim = $this->store->read_claim( $order_id, $item_id );
			$held  = $claim ? (int) $claim['reserved'] : 0;
			if ( $held > $capacity || ( $claim && $claim['facts_hash'] !== $facts['hash'] ) ) {
				$review = true;
			}
			$available[ $item_id ] = max( 0, $capacity - $held );
		}
		$request_limit = $this->store->retained_count( $order_id ) >= 100;
		$eligible      = ! $policy_invalid && ! $review && ! $request_limit;
		if ( ! $policy_invalid ) {
			try {
				$this->eligible( $facts, $policy, call_user_func( $this->clock ) );
			} catch ( RuntimeException $error ) {
				$eligible = false;
			}
		}
		return array(
			'facts'          => $facts,
			'rows'           => $rows,
			'available'      => $available,
			'eligible'       => $eligible,
			'needs_review'   => $review,
			'request_limit'  => $request_limit,
			'policy_invalid' => $policy_invalid,
		);
	}

	/**
	 * Exact order/actor-zero scan, then owner digest filtering before disclosure.
	 * Cursor records the last scanned row, not the last visible matching row.
	 *
	 * @param string $cookie Grant cookie.
	 * @param int $before Keyset cursor.
	 * @return array|WP_Error
	 */
	public function guest_view( $cookie, $before = PHP_INT_MAX ) {
		return $this->run( function () use ( $cookie, $before ) {
			if ( PHP_INT_MAX !== $before ) { $this->identifier( $before ); }
			$guest = new WCJ_Returns_Guest( $this->store, $this->facts, $this->clock );
			$context = $guest->context( $cookie );
			$facts = $this->facts->load( $context['order'] );
			if ( ! $this->guest_matches( $facts, $context['owner'] ) ) { throw new RuntimeException( 'forbidden' ); }
			$scanned = $this->store->guest_listing( $context['order'], $before );
			$rows = array();
			foreach ( $scanned as $row ) {
				$data = $this->store->payload( $row );
				if ( hash_equals( $data['guest']['owner'], $context['owner'] ) ) { $rows[] = $row; }
			}
			$result = $this->view_data( $context['order'], $facts, $rows );
			$actions = $guest->actions( $context );
			$result['submit_action'] = $actions['submit'];
			$result['cancel_action'] = $actions['cancel'];
			$result['has_more'] = 50 === count( $scanned );
			$last = $scanned ? end( $scanned ) : null;
			$result['next_cursor'] = $last ? (int) $last['id'] : 0;
			$guest->recheck();
			return $result;
		} );
	}

	/**
	 * Decide a pending request, or allow its current owner to cancel it.
	 *
	 * @param int    $id Request ID.
	 * @param int    $revision Expected revision.
	 * @param string $status Terminal decision.
	 * @param string $nonce User-bound nonce.
	 * @return array|WP_Error
	 */
	public function transition( $id, $revision, $status, $nonce ) {
		return $this->run(
			function () use ( $id, $revision, $status, $nonce ) {
				$started = WCJ_Returns_Clock::read();
				$this->identifier( $id );
				$this->identifier( $revision );
				$this->nonce( $nonce, 'wcj_returns_transition_' . $id );
				if ( ! get_current_user_id() ) {
						throw new RuntimeException( 'forbidden' );
				}
				$row   = $this->store->get( $id );
				$facts = $this->facts->load( (int) $row['order_id'] );
				$staff = current_user_can( 'manage_woocommerce' );
				$this->authorize( $facts, $staff );
				$actor = (int) get_current_user_id();
				$now   = call_user_func( $this->clock );
				if ( ! $staff && ( (int) $row['actor_id'] !== (int) get_current_user_id() || 'cancelled' !== $status ) ) {
					throw new RuntimeException( 'forbidden' );
				}
				return $this->store->transaction(
					function () use ( $id, $revision, $status, $row, $facts, $staff, $actor, $now ) {
						return $this->transition_locked( $id, $revision, $status, $row, $facts, $staff, $actor, $now );
					},
					$started
				);
			}
		);
	}

	/**
	 * Current owner's order eligibility and private request history.
	 *
	 * @param int $order_id Order ID.
	 * @param int $before Keyset history cursor.
	 * @return array|WP_Error
	 */
	public function customer_view( $order_id, $before = PHP_INT_MAX ) {
		return $this->run(
			function () use ( $order_id, $before ) {
				$this->identifier( $order_id );
				if ( PHP_INT_MAX !== $before ) {
						$this->identifier( $before );
				}
				if ( ! get_current_user_id() ) {
					throw new RuntimeException( 'forbidden' );
				}
				$facts = $this->facts->load( $order_id );
				$this->authorize( $facts );
				$rows = $this->store->listing( (int) get_current_user_id(), $order_id, $before );
				return $this->view_data( $order_id, $facts, $rows );
			}
		);
	}

	/**
	 * Capability-protected bounded merchant queue, with current drift flags.
	 *
	 * @param int $before Keyset cursor.
	 * @return array|WP_Error
	 */
	public function queue( $before = PHP_INT_MAX ) {
		return $this->run(
			function () use ( $before ) {
				if ( PHP_INT_MAX !== $before ) {
						$this->identifier( $before );
				}
				$this->authorize( array(), true );
				$rows = $this->store->listing( 0, 0, $before );
				foreach ( $rows as &$row ) {
					$row['data']              = $this->store->payload( $row );
					$row['facts_unavailable'] = false;
					try {
						$facts = $this->facts->load( (int) $row['order_id'] );
					} catch ( Throwable $error ) {
						$row['facts_unavailable'] = true;
						$row['needs_review']      = true;
						continue;
					}
					$anchor              = $this->store->read_claim( (int) $row['order_id'], 0 );
					$row['needs_review'] = ( 0 === (int) $row['actor_id'] && ! $this->guest_matches( $facts, $row['data']['guest']['owner'] ) ) || $facts['amount_only'] || ( $anchor && ( $anchor['facts_hash'] !== $facts['hash'] || (int) $anchor['needs_review'] ) );
				}
				unset( $row );
				return $rows;
			}
		);
	}

	/**
	 * Claim and dispatch one opted-in notification outside the transaction.
	 * Unknown or already sending states never permit manual duplicate resend.
	 *
	 * @param int    $id Request ID.
	 * @param string $key Finite intent key.
	 * @param string $nonce User-bound dispatch nonce.
	 * @return array|WP_Error
	 */
	public function dispatch( $id, $key, $nonce ) {
		return $this->run(
			function () use ( $id, $key, $nonce ) {
				$started = WCJ_Returns_Clock::read();
				$this->identifier( $id );
				$this->nonce( $nonce, 'wcj_returns_dispatch_' . $id );
				$this->authorize( array(), true );
				if ( 'yes' !== get_option( 'wcj_returns_notifications', 'no' ) ) {
						throw new RuntimeException( 'notification_disabled' );
				}
				if ( ! in_array( $key, array( 'submitted_customer', 'submitted_merchant', 'decision_customer', 'decision_merchant' ), true ) ) {
					throw new RuntimeException( 'input_invalid' );
				}
				$row       = $this->store->get( $id );
				$facts     = $this->facts->load( (int) $row['order_id'] );
				$lease     = bin2hex( random_bytes( 32 ) );
				$now       = call_user_func( $this->clock );
				$intent    = $this->store->transaction(
					function () use ( $id, $key, $row, $facts, $lease, $now ) {
						$this->store->lock_claim( (int) $row['order_id'], 0, $facts['hash'] );
						$current = $this->store->get( $id, true );
						$data    = $this->store->payload( $current );
						if ( 0 === (int) $current['actor_id'] && ! $this->guest_matches( $facts, $data['guest']['owner'] ) ) {
							throw new RuntimeException( 'recipient_unavailable' );
						}
						$event   = $data['outbox'][ $key ] ?? null;
						if ( ! is_array( $event ) || ! in_array( $event['state'], array( 'pending', 'failed' ), true ) ) {
								throw new RuntimeException( 'notification_ambiguous' );
						}
						if ( $event['attempts'] >= 3 || $event['due'] > $now ) {
							throw new RuntimeException( 'notification_not_due' );
						}
						$event['state'] = 'sending';
						$event['lease'] = $lease;
						++$event['attempts'];
						$data['outbox'][ $key ] = $event;
						$this->store->save( $current, $data, $current['status'], $now );
						return array(
							'event'  => $event,
							'owner'  => (int) $current['actor_id'],
							'guest_owner' => $data['guest']['owner'] ?? '',
							'status' => $current['status'],
						);
					},
					$started
				);
				$state     = 'ambiguous';
				$attempted = false;
				try {
					$this->authorize( array(), true );
					$fresh = $this->facts->load( (int) $row['order_id'] );
					if ( $fresh['owner'] !== $intent['owner'] || ( 0 === $intent['owner'] && ! $this->guest_matches( $fresh, $intent['guest_owner'] ) ) || 'yes' !== get_option( 'wcj_returns_notifications', 'no' ) ) {
						throw new RuntimeException( 'recipient_unavailable' );
					}
					$user  = $intent['owner'] ? get_userdata( $intent['owner'] ) : false;
					$email = 'merchant' === $intent['event']['role'] ? get_option( 'admin_email' ) : ( 0 === $intent['owner'] ? $fresh['guest']['email'] : ( $user ? $user->user_email : '' ) );
					if ( ! is_string( $email ) || ! is_email( $email ) ) {
						throw new RuntimeException( 'recipient_unavailable' );
					}
					/* translators: %d: Private return request number. */
					$subject = sprintf( __( 'Return request #%d', 'woocommerce-jetpack' ), $id );
					/* translators: 1: Request number, 2: Request status. */
					$body      = '<p>' . esc_html( sprintf( __( 'Return request #%1$d has status: %2$s. Approval does not issue a refund or restock products.', 'woocommerce-jetpack' ), $id, $intent['status'] ) ) . '</p>';
					$attempted = true;
					$result    = WC()->mailer()->send( $email, $subject, $body );
					// A generic false can follow remote acceptance/timeout. Not retryable.
					$state = true === $result ? 'accepted' : 'ambiguous';
				} catch ( Throwable $error ) {
					// The mailer may already have accepted a message. Never guess/retry.
					$state = $attempted ? 'ambiguous' : 'failed';
				}
				$final_started = WCJ_Returns_Clock::read();
				$final_now     = call_user_func( $this->clock );
				return $this->store->transaction(
					function () use ( $id, $key, $row, $facts, $lease, $now, $state, $final_now ) {
						$this->store->lock_claim( (int) $row['order_id'], 0, $facts['hash'] );
						$current = $this->store->get( $id, true );
						$data    = $this->store->payload( $current );
						$event   = $data['outbox'][ $key ];
						if ( 'sending' !== $event['state'] || ! hash_equals( $event['lease'], $lease ) ) {
								throw new RuntimeException( 'notification_ambiguous' );
						}
						$event['state']         = $state;
						$event['lease']         = '';
						$event['due']           = $now + ( 1 === $event['attempts'] ? 60 : 300 );
						$data['outbox'][ $key ] = $event;
						return $this->store->save( $current, $data, $current['status'], $final_now );
					},
					$final_started
				);
			}
		);
	}
	/** Reviewed module/role admission, source-equivalent to Elite and Summary. */
	private function filtered_admission() {
		$plugin = function_exists( 'w_c_j' ) ? w_c_j() : ( function_exists( 'WCJ' ) ? WCJ() : null );
		$module = isset( $plugin->all_modules['returns'] ) ? $plugin->all_modules['returns'] : null;
		if ( ! $module instanceof WCJ_Returns || ! method_exists( $module, 'is_enabled' )
			|| ! function_exists( 'wcj_is_module_enabled' ) || ! function_exists( 'wcj_check_modules_by_user_roles' ) ) {
			throw new RuntimeException( 'returns_unavailable' );
		}
		$role_policy = wcj_is_module_enabled( 'modules_by_user_roles' );
		if ( ! is_bool( $role_policy ) ) {
			throw new RuntimeException( 'returns_unavailable' );
		}
		if ( $role_policy ) {
			if ( isset( $GLOBALS['wcj_modules_by_user_roles_data'] ) ) {
				$cached_policy = $GLOBALS['wcj_modules_by_user_roles_data'];
				if ( ! is_array( $cached_policy ) || ! isset( $cached_policy['role'] ) || ! function_exists( 'wp_get_current_user' ) ) {
					throw new RuntimeException( 'returns_unavailable' );
				}
				$current_user  = wp_get_current_user();
				$current_roles = isset( $current_user->roles ) && is_array( $current_user->roles ) ? $current_user->roles : array();
				$current_role  = empty( $current_roles ) ? 'guest' : reset( $current_roles );
				$current_role  = '' === $current_role ? 'guest' : $current_role;
				if ( ! is_string( $current_role ) || $cached_policy['role'] !== $current_role ) {
					throw new RuntimeException( 'returns_unavailable' );
				}
			}
			$allowed = wcj_check_modules_by_user_roles( 'returns' );
			if ( ! is_bool( $allowed ) ) {
				throw new RuntimeException( 'returns_unavailable' );
			}
			if ( ! $allowed ) {
				throw new RuntimeException( 'forbidden' );
			}
		}
		$enabled = $module->is_enabled();
		if ( true !== $enabled ) {
			throw new RuntimeException( false === $enabled ? 'module_disabled' : 'returns_unavailable' );
		}
	}


	/**
	 * Fresh private staff admission before any synthetic configuration or rows.
	 *
	 * @param callable $operation Captured-input operation.
	 * @return mixed|WP_Error Finite result only.
	 */
	private function synthetic_run( $operation ) {
		try {
			$started = WCJ_Returns_Clock::read();
			$this->authorize( array(), true );
			if ( 'yes' !== get_option( 'wcj_returns_enabled', 'no' ) ) {
				throw new RuntimeException( 'module_disabled' );
			}
			$this->filtered_admission();
			$actor = (int) get_current_user_id();
			$blog  = (int) get_current_blog_id();
			$now   = call_user_func( $this->clock );
			if ( ! is_int( $now ) || $now < 1 ) {
				throw new RuntimeException( 'input_invalid' );
			}
			if ( ! $this->store ) {
				$this->store = new WCJ_Returns_Store( null, null, 2 );
			}
			$this->store->assert_purpose( 2 );
			return $operation( $started, $actor, $blog, $now );
		} catch ( Throwable $error ) {
			$known = array( 'forbidden', 'module_disabled', 'nonce_invalid', 'input_invalid', 'quantity_invalid', 'capacity_exceeded', 'idempotency_conflict', 'revision_conflict', 'transition_invalid', 'needs_review', 'facts_stale', 'commit_unknown', 'request_unavailable', 'synthetic_conflict' );
			$code  = in_array( $error->getMessage(), $known, true ) ? $error->getMessage() : 'returns_unavailable';
			return new WP_Error( $code, __( 'The synthetic workflow exercise is unavailable or could not be completed. No customer order is involved.', 'woocommerce-jetpack' ) );
		}
	}

	/**
	 * Literal facts only; never invoke ordinary order or catalogue readers.
	 *
	 * @param array $witness Validated retained or private witness.
	 * @return array Captured fixed facts.
	 */
	private function synthetic_snapshot( $witness ) {
		return array(
			'owner'      => $witness['creator'],
			'status'     => 'completed',
			'completed'  => $witness['birth'],
			'amount_only' => false,
			'capacities' => array( 1 => 2000000 ),
			'names'      => array( 1 => WCJ_Returns_Store::SYNTHETIC_NAME ),
			'hash'       => $this->store->synthetic_binding( $witness ),
		);
	}

	/**
	 * Validate one retained exercise and its exact claims under its anchor lock.
	 *
	 * @param array  $anchor Locked existing anchor.
	 * @param int    $target Captured target.
	 * @param int    $actor Captured creator.
	 * @param int    $blog Captured blog.
	 * @param string $digest Full expected digest, empty only for creator-bound ID view.
	 * @return array Validated header, payload and fixed facts.
	 */
	private function synthetic_retained( $anchor, $target, $actor, $blog, $digest = '' ) {
		$rows = $this->store->synthetic_requests( $target );
		if ( ! is_array( $anchor ) || 1 !== count( $rows ) ) {
			throw new RuntimeException( 'synthetic_conflict' );
		}
		$row     = $rows[0];
		$data    = $this->store->payload( $row );
		$witness = $data['synthetic'];
		if ( $witness['creator'] !== $actor || $witness['blog'] !== $blog || $witness['target'] !== $target || ( '' !== $digest && $witness['token'] !== $digest ) ) {
			throw new RuntimeException( 'synthetic_conflict' );
		}
		$facts  = $this->synthetic_snapshot( $witness );
		$claims = $this->store->synthetic_claims( $target );
		$units  = in_array( $row['status'], array( 'pending', 'approved' ), true ) ? $data['items'][1] : 0;
		if ( 2 !== count( $claims ) || (string) ( $claims[0]['item_id'] ?? '' ) !== '0' || (string) ( $claims[1]['item_id'] ?? '' ) !== '1' || $claims[0] !== $anchor ) {
			throw new RuntimeException( 'synthetic_conflict' );
		}
		foreach ( $claims as $index => $claim ) {
			if ( ! in_array( $claim['purpose'] ?? null, array( 2, '2' ), true ) || (string) ( $claim['order_id'] ?? '' ) !== (string) $target || (string) ( $claim['reserved'] ?? '' ) !== (string) ( 0 === $index ? 0 : $units ) || ! in_array( $claim['needs_review'] ?? null, array( 0, '0' ), true ) || ( $claim['facts_hash'] ?? null ) !== $facts['hash'] ) {
				throw new RuntimeException( 'synthetic_conflict' );
			}
		}
		$row['data'] = $data;
		return array( $row, $facts );
	}

	/**
	 * A synthetic error result must rollback inside the closure, never commit a hold.
	 *
	 * @param mixed $result Shared kernel result.
	 * @return mixed Successful result.
	 */
	private function synthetic_result( $result ) {
		if ( is_array( $result ) && isset( $result['_wcj_error'] ) ) {
			throw new RuntimeException( 'needs_review' === $result['_wcj_error'] ? 'needs_review' : 'returns_unavailable' );
		}
		return $result;
	}

	/**
	 * Submit or replay a real private row using server-literal synthetic facts.
	 *
	 * @param mixed  $quantity One decimal quantity, no target/item input.
	 * @param string $token Original opaque token.
	 * @param string $nonce Current user-bound nonce.
	 * @return array|WP_Error Persisted result or finite error.
	 */
	public function synthetic_submit( $quantity, $token, $nonce ) {
		return $this->synthetic_run( function ( $started, $actor, $blog, $now ) use ( $quantity, $token, $nonce ) {
			$this->nonce( $nonce, 'wcj_returns_synthetic_create' );
			$reason   = WCJ_Returns_Store::SYNTHETIC_REASON;
			$selected = $this->selected( array( 1 => $quantity ), $reason, $token );
			$input    = hash( 'sha256', json_encode( array( $selected, $reason ) ) );
			$digest   = hash( 'sha256', $token );
			$target   = WCJ_Returns_Store::synthetic_target( $blog, $actor, $digest );
			$witness  = array( 'v' => 1, 'blog' => $blog, 'creator' => $actor, 'target' => $target, 'token' => $digest, 'template' => WCJ_Returns_Store::synthetic_template(), 'birth' => $now );
			$this->store->synthetic_binding( $witness );
			return $this->store->transaction( function () use ( $actor, $blog, $now, $reason, $selected, $input, $digest, $target, $witness ) {
				$anchor = $this->store->existing_synthetic_anchor( $target );
				if ( $anchor ) {
					list( $old, $facts ) = $this->synthetic_retained( $anchor, $target, $actor, $blog, $digest );
					// The locked retained witness wins, even after a Phase A absent race.
					$witness = $old['data']['synthetic'];
				} else {
					if ( $this->store->synthetic_requests( $target ) || $this->store->synthetic_claims( $target ) ) {
						throw new RuntimeException( 'synthetic_conflict' );
					}
					$facts = $this->synthetic_snapshot( $witness );
					$this->store->insert_synthetic_anchor( $target, $facts['hash'] );
				}
				$policy = array( 'days' => 30, 'notify' => false, 'version' => 1 );
				$result = $this->submit_locked( $target, $selected, $reason, $actor, $input, $digest, $facts, $now, $policy, '', '', $witness );
				return $this->synthetic_result( $result );
			}, $started );
		} );
	}

	/**
	 * Finite creator-bound staff decision using the same release/history kernel.
	 *
	 * @param int    $id Request ID.
	 * @param int    $revision Expected revision.
	 * @param string $status Finite terminal status.
	 * @param string $nonce Current user nonce.
	 * @return array|WP_Error Persisted result or finite error.
	 */
	public function synthetic_transition( $id, $revision, $status, $nonce ) {
		return $this->synthetic_run( function ( $started, $actor, $blog, $now ) use ( $id, $revision, $status, $nonce ) {
			$this->identifier( $id );
			$this->identifier( $revision );
			$this->nonce( $nonce, 'wcj_returns_synthetic_transition_' . $id );
			if ( ! in_array( $status, array( 'approved', 'declined', 'cancelled' ), true ) ) {
				throw new RuntimeException( 'transition_invalid' );
			}
			$row  = $this->store->get( $id );
			$data = $this->store->payload( $row );
			if ( $data['synthetic']['creator'] !== $actor || $data['synthetic']['blog'] !== $blog ) {
				throw new RuntimeException( 'forbidden' );
			}
			$target = $data['synthetic']['target'];
			return $this->store->transaction( function () use ( $id, $revision, $status, $actor, $blog, $now, $target ) {
				$anchor = $this->store->existing_synthetic_anchor( $target );
				list( $row, $facts ) = $this->synthetic_retained( $anchor, $target, $actor, $blog );
				if ( (int) $row['id'] !== $id ) {
					throw new RuntimeException( 'synthetic_conflict' );
				}
				return $this->synthetic_result( $this->transition_locked( $id, $revision, $status, $row, $facts, true, $actor, $now ) );
			}, $started );
		} );
	}

	/**
	 * Read retained history under fresh staff admission, without creating claims.
	 *
	 * @param int $id Exact synthetic request ID.
	 * @return array|WP_Error Private header/history.
	 */
	public function synthetic_view( $id ) {
		return $this->synthetic_run( function ( $started, $actor, $blog ) use ( $id ) {
			$this->identifier( $id );
			$row  = $this->store->get( $id );
			$data = $this->store->payload( $row );
			if ( $data['synthetic']['creator'] !== $actor || $data['synthetic']['blog'] !== $blog ) {
				throw new RuntimeException( 'forbidden' );
			}
			$target = $data['synthetic']['target'];
			return $this->store->transaction( function () use ( $id, $actor, $blog, $target ) {
				list( $row ) = $this->synthetic_retained( $this->store->existing_synthetic_anchor( $target ), $target, $actor, $blog );
				if ( (int) $row['id'] !== $id ) {
					throw new RuntimeException( 'synthetic_conflict' );
				}
				return $row;
			}, $started );
		} );
	}

	/**
	 * Product recovery is only a fresh same-token read, never a create retry.
	 *
	 * @param string $token Original token.
	 * @param string $nonce Current user nonce.
	 * @return array|WP_Error Retained result or still unknown.
	 */
	public function synthetic_recover( $token, $nonce ) {
		return $this->synthetic_run( function ( $started, $actor, $blog ) use ( $token, $nonce ) {
			$this->nonce( $nonce, 'wcj_returns_synthetic_recover' );
			if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) {
				throw new RuntimeException( 'input_invalid' );
			}
			$digest = hash( 'sha256', $token );
			$target = WCJ_Returns_Store::synthetic_target( $blog, $actor, $digest );
			return $this->store->transaction( function () use ( $actor, $blog, $digest, $target ) {
				$anchor = $this->store->existing_synthetic_anchor( $target );
				if ( ! $anchor ) {
					throw new RuntimeException( 'commit_unknown' );
				}
				list( $row ) = $this->synthetic_retained( $anchor, $target, $actor, $blog, $digest );
				return $row;
			}, $started );
		} );
	}

	/** @return string|WP_Error Finite admitted synthetic setup observation. */
	public function synthetic_setup_state() {
		return $this->synthetic_run( function () { return $this->store->setup_state(); } );
	}

	/** @return string|WP_Error Finite admitted live setup observation. */
	public function storage_state() {
		return $this->run( function () {
			$this->authorize( array(), true );
			$this->filtered_admission();
			return $this->store->setup_state();
		} );
	}

	/**
	 * Explicit existing setup action, with fresh private admission and nonce.
	 *
	 * @param string $nonce Current user nonce.
	 * @return bool|WP_Error Setup result, never a migration.
	 */
	public function initialize_storage( $nonce ) {
		return $this->run( function () use ( $nonce ) {
			$this->authorize( array(), true );
			$this->filtered_admission();
			$this->nonce( $nonce, 'wcj_returns_storage' );
			$this->store->install();
			return true;
		} );
	}

}

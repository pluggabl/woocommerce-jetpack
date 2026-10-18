<?php
/**
 * Booster Returns: connected account and merchant workflow.
 *
 * @package Booster_For_WooCommerce
 * @since 9.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/returns/class-wcj-returns-facts.php';
require_once __DIR__ . '/returns/class-wcj-returns-store.php';
require_once __DIR__ . '/returns/class-wcj-returns-service.php';
require_once __DIR__ . '/returns/class-wcj-returns-guest.php';

/** Existing Booster module registration; guest access is separately opted in. */
class WCJ_Returns extends WCJ_Module {
	/** Construct the disabled-by-default module. */
	public function __construct() {
		$this->id         = 'returns';
		$this->short_desc = 'Return Requests';
		wcj_init_presentation(
			function () {
				$this->short_desc = __( 'Return Requests', 'woocommerce-jetpack' );
			}
		);
		$this->desc = 'Customer-owned return requests and merchant decisions. No automatic refunds or restocking.';
		wcj_init_presentation(
			function () {
				$this->desc     = __( 'Customer-owned return requests and merchant decisions. No automatic refunds or restocking.', 'woocommerce-jetpack' );
				$this->desc_pro = $this->desc;
			}
		);
		$this->desc_pro = $this->desc;
		parent::__construct();
		if ( $this->is_enabled() ) {
			add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_post_wcj_returns_submit', array( $this, 'submit' ) );
			add_action( 'admin_post_wcj_returns_transition', array( $this, 'transition' ) );
			add_action( 'admin_post_wcj_returns_storage', array( $this, 'storage' ) );
			add_action( 'admin_post_wcj_returns_dispatch', array( $this, 'dispatch' ) );
			add_action( 'admin_post_wcj_returns_synthetic_create', array( $this, 'synthetic_create' ) );
			add_action( 'admin_post_wcj_returns_synthetic_transition', array( $this, 'synthetic_transition' ) );
			add_action( 'admin_post_wcj_returns_synthetic_recover', array( $this, 'synthetic_recover' ) );
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'customer' ) );
			add_action( 'woocommerce_account_content', array( $this, 'account_feedback' ), 5 );
			if ( 'yes' === get_option( 'wcj_returns_guest', 'no' ) ) {
				( new WCJ_Returns_Guest() )->register();
			}
		}
	}

	/** Register a capability-protected private merchant queue. */
	public function menu() {
		add_submenu_page( 'woocommerce', __( 'Return Requests', 'woocommerce-jetpack' ), __( 'Return Requests', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-returns', array( $this, 'admin' ) );
	}

	/**
	 * Read one bounded POST scalar; no client redirect/actor/recipient accepted.
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	private function posted( $key ) {
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) && strlen( $_POST[ $key ] ) <= 4096 ? wp_unslash( $_POST[ $key ] ) : '';
	}

	/**
	 * Read a strictly positive integer without truncation or absint coercion.
	 *
	 * @param string $key Field key.
	 * @return int
	 */
	private function integer( $key ) {
		$value = $this->posted( $key );
		return preg_match( '/^[1-9][0-9]{0,17}$/D', $value ) && (string) (int) $value === $value ? (int) $value : 0;
	}

	/**
	 * Fixed public feedback only. No exception message or request text is echoed.
	 *
	 * @return array Finite result code to translated feedback.
	 */
	private function result_messages() {
		return array(
			'saved'                  => __( 'Your return request was saved. View the order for its current status.', 'woocommerce-jetpack' ),
			'unavailable'            => __( 'The return request could not be completed. Please review its current status or contact the store.', 'woocommerce-jetpack' ),
			'request_limit'          => __( 'This order has reached the limit of 100 retained return requests. Please contact the store.', 'woocommerce-jetpack' ),
			'capacity_exceeded'      => __( 'The selected quantity is no longer available for a return request. Review the current order details.', 'woocommerce-jetpack' ),
			'revision_conflict'      => __( 'The request changed before this action was saved. Review its current status before taking another action.', 'woocommerce-jetpack' ),
			'idempotency_conflict'   => __( 'This request token was already used for different input. Review the existing request.', 'woocommerce-jetpack' ),
			'needs_review'           => __( 'Changed order details need store review. Existing claims remain held.', 'woocommerce-jetpack' ),
			'policy_invalid'         => __( 'The store return policy is invalid. New requests are unavailable; existing history and cancellation remain available.', 'woocommerce-jetpack' ),
			'commit_unknown'         => __( 'The action outcome is unknown. Review the current request history before taking any further action. Do not repeat the action automatically.', 'woocommerce-jetpack' ),
			'notification_ambiguous' => __( 'The notification outcome is unknown. This notification cannot be resent safely.', 'woocommerce-jetpack' ),
		);
	}

	/** Display only a bounded finite result on the existing destination. */
	private function result_feedback() {
		$code     = isset( $_GET['wcj_returns_result'] ) && is_string( $_GET['wcj_returns_result'] ) && strlen( $_GET['wcj_returns_result'] ) <= 32 ? $_GET['wcj_returns_result'] : '';
		$messages = $this->result_messages();
		if ( isset( $messages[ $code ] ) ) {
			echo '<p>' . esc_html( $messages[ $code ] ) . '</p>';
		}
	}

	/** Frontend feedback does not depend on a Woo session or notice API. */
	public function account_feedback() {
		if ( is_user_logged_in() && is_account_page() ) {
			$this->result_feedback();
		}
	}

	/**
	 * Redirect to a server-built destination with a finite result, without notices.
	 * Fresh supported facts must authorize an own-order destination independently.
	 *
	 * @param mixed $result Operation result.
	 * @param bool  $admin Whether this is a merchant action.
	 * @param int   $order_id Validated intake order ID, if available.
	 */
	private function finish( $result, $admin = false, $order_id = 0 ) {
		$url      = $admin ? admin_url( 'admin.php?page=wcj-returns' ) : wc_get_account_endpoint_url( 'orders' );
		$messages = $this->result_messages();
		$code     = is_wp_error( $result ) ? $result->get_error_code() : 'saved';
		$code     = is_string( $code ) && isset( $messages[ $code ] ) ? $code : 'unavailable';
		if ( ! $admin && get_current_user_id() > 0 ) {
			if ( is_array( $result ) && isset( $result['order_id'] ) ) {
				$order_id = (int) $result['order_id'];
			}
			if ( $order_id > 0 ) {
				try {
					$facts = ( new WCJ_Returns_Facts() )->load( $order_id );
					if ( $facts['owner'] > 0 && $facts['owner'] === (int) get_current_user_id() ) {
						$url = wc_get_endpoint_url( 'view-order', $order_id, wc_get_page_permalink( 'myaccount' ) );
					}
				} catch ( Throwable $error ) {
					// Unavailable or unsupported facts retain the safe account fallback.
				}
			}
		}
		wp_safe_redirect( add_query_arg( 'wcj_returns_result', $code, $url ) );
		exit;
	}

	/** Logged-in intake endpoint. Current nonce/ownership are checked in service. */
	public function submit() {
		$selected = array();
		$posted   = isset( $_POST['quantities'] ) && is_array( $_POST['quantities'] ) ? wp_unslash( $_POST['quantities'] ) : array();
		if ( count( $posted ) > 200 ) {
			$this->finish( new WP_Error( 'input_invalid', __( 'Too many selected items.', 'woocommerce-jetpack' ) ) );
		}
		foreach ( $posted as $id => $value ) {
			if ( '' !== $value ) {
				$selected[ $id ] = $value;
			}
		}
		$this->finish( ( new WCJ_Returns_Service() )->submit( $this->integer( 'order_id' ), $selected, $this->posted( 'reason' ), $this->posted( 'token' ), $this->posted( '_wpnonce' ) ), false, $this->integer( 'order_id' ) );
	}

	/** Staff decision or owner cancellation, with current revision/nonce. */
	public function transition() {
		$this->finish( ( new WCJ_Returns_Service() )->transition( $this->integer( 'request_id' ), $this->integer( 'revision' ), $this->posted( 'status' ), $this->posted( '_wpnonce' ) ), current_user_can( 'manage_woocommerce' ) );
	}

	/** Explicit nonce/capability-controlled schema creation, never on a GET. */
	public function storage() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! in_array( wp_verify_nonce( $this->posted( '_wpnonce' ), 'wcj_returns_storage' ), array( 1, 2 ), true ) || ! $this->is_enabled() ) {
			wp_die( esc_html__( 'You cannot initialize return-request storage.', 'woocommerce-jetpack' ) );
		}
		$result = ( new WCJ_Returns_Service() )->initialize_storage( $this->posted( '_wpnonce' ) );
		$this->finish( $result, true );
	}

	/** Explicit opted-in dispatch. Ambiguous outcomes are never resendable. */
	public function dispatch() {
		$this->finish( ( new WCJ_Returns_Service() )->dispatch( $this->integer( 'request_id' ), $this->posted( 'event' ), $this->posted( '_wpnonce' ) ), true );
	}

	/**
	 * Render current owner's request form/history on the existing order view.
	 *
	 * @param WC_Order $order Existing WooCommerce order.
	 */
	public function customer( $order ) {
		if ( ! is_user_logged_in() || ! is_account_page() || ! is_wc_endpoint_url( 'view-order' ) || ! $order instanceof WC_Order ) {
			return;
		}
		$id     = (int) $order->get_id();
		$before = isset( $_GET['wcj_returns_before'] ) && is_string( $_GET['wcj_returns_before'] ) && preg_match( '/^[1-9][0-9]{0,17}$/D', $_GET['wcj_returns_before'] ) ? (int) $_GET['wcj_returns_before'] : PHP_INT_MAX;
		$data   = ( new WCJ_Returns_Service() )->customer_view( $id, $before );
		if ( is_wp_error( $data ) ) {
			echo '<p>' . esc_html__( 'Return requests are unavailable for this order. Please contact the store if the problem continues.', 'woocommerce-jetpack' ) . '</p>';
			return;
		}
		echo '<section><h2>' . esc_html__( 'Return Requests', 'woocommerce-jetpack' ) . '</h2><p>' . esc_html__( 'A request or approval does not issue a refund or restock products. The store decides the next steps.', 'woocommerce-jetpack' ) . '</p>';
		if ( $data['policy_invalid'] ) {
			echo '<p>' . esc_html__( 'The store return policy is invalid. New requests are unavailable; existing history and cancellation remain available.', 'woocommerce-jetpack' ) . '</p>';
		}
		if ( $data['needs_review'] ) {
			echo '<p>' . esc_html__( 'Changed order details need store review. Existing requests remain recorded.', 'woocommerce-jetpack' ) . '</p>';
		}
		if ( $data['request_limit'] ) {
			echo '<p>' . esc_html__( 'This order has reached the limit of 100 retained return requests. Please contact the store.', 'woocommerce-jetpack' ) . '</p>';
		}
		foreach ( $data['rows'] as $row ) {
			$payload = ( new WCJ_Returns_Store() )->payload( $row );
			echo '<h3>' . esc_html( '#' . $row['id'] . ': ' . $row['status'] ) . '</h3>';
			$this->history( $payload );
			if ( 'pending' === $row['status'] ) {
				$this->decision_form( $row, array( 'cancelled' ) );
			}
		}
		if ( 50 === count( $data['rows'] ) ) {
			$last = end( $data['rows'] );
			echo '<a href="' . esc_url( add_query_arg( 'wcj_returns_before', $last['id'], wc_get_endpoint_url( 'view-order', $id, wc_get_page_permalink( 'myaccount' ) ) ) ) . '">' . esc_html__( 'Older requests', 'woocommerce-jetpack' ) . '</a>';
		}
		if ( $data['eligible'] ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_submit"><input type="hidden" name="order_id" value="' . esc_attr( $id ) . '"><input type="hidden" name="token" value="' . esc_attr( bin2hex( random_bytes( 32 ) ) ) . '">';
			wp_nonce_field( 'wcj_returns_submit_' . $id );
			foreach ( $data['available'] as $item_id => $units ) {
				if ( $units > 0 ) {
					echo '<p><label>' . esc_html( $data['facts']['names'][ $item_id ] ) . ' (' . esc_html( WCJ_Returns_Quantity::display( $units ) ) . ') <input type="text" inputmode="decimal" name="quantities[' . esc_attr( $item_id ) . ']" maxlength="14" autocomplete="off"></label></p>';
				}
			}
			echo '<p><label>' . esc_html__( 'Reason (optional, maximum 500 characters)', 'woocommerce-jetpack' ) . '<textarea name="reason" maxlength="500"></textarea></label></p><button type="submit">' . esc_html__( 'Request a return', 'woocommerce-jetpack' ) . '</button></form>';
		} else {
			echo '<p>' . esc_html__( 'This order is not currently eligible for another return request.', 'woocommerce-jetpack' ) . '</p>';
		}
		echo '</section>';
	}

	/**
	 * Render finite, escaped history and selected exact quantities.
	 *
	 * @param array $data Private bounded payload.
	 */
	private function history( $data ) {
		echo '<ul>';
		foreach ( $data['items'] as $item_id => $units ) {
			echo '<li>' . esc_html( '#' . $item_id . ': ' . WCJ_Returns_Quantity::display( $units ) ) . '</li>';
		}
		foreach ( $data['history'] as $event ) {
			echo '<li>' . esc_html( $event['status'] . ': ' . gmdate( 'Y-m-d H:i:s', $event['time'] ) . ' UTC' ) . '</li>';
		}
		echo '</ul><p>' . esc_html( $data['reason'] ) . '</p>';
	}

	/**
	 * Render an expected-revision form with a request/user-bound nonce.
	 *
	 * @param array $row Private request.
	 * @param array $statuses Allowed rendered statuses.
	 */
	private function decision_form( $row, $statuses ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_transition"><input type="hidden" name="request_id" value="' . esc_attr( $row['id'] ) . '"><input type="hidden" name="revision" value="' . esc_attr( $row['revision'] ) . '">';
		wp_nonce_field( 'wcj_returns_transition_' . $row['id'] );
		foreach ( $statuses as $status ) {
			echo '<button type="submit" name="status" value="' . esc_attr( $status ) . '">' . esc_html( $status ) . '</button> ';
		}
		echo '</form>';
	}

	/** Best-effort known-plugin warning, only in the authorized merchant context. */
	private function overlap_warning() {
		if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$admitted = false;
		$active   = null;
		try {
			if ( wcj_is_rest() ) {
				return;
			}
			if ( wcj_is_module_enabled( 'modules_by_user_roles' ) && isset( $GLOBALS['wcj_modules_by_user_roles_data'] ) ) {
				$cached = $GLOBALS['wcj_modules_by_user_roles_data'];
				$user   = wp_get_current_user();
				$roles  = isset( $user->roles ) && is_array( $user->roles ) ? $user->roles : array();
				$role   = empty( $roles ) ? 'guest' : reset( $roles );
				$role   = '' === $role ? 'guest' : $role;
				if ( ! is_array( $cached ) || ! isset( $cached['role'] ) || ! is_string( $role ) || $cached['role'] !== $role ) {
					return;
				}
			}
			if ( ! $this->is_enabled() ) {
				return;
			}
			$admitted = true;
			if ( function_exists( 'is_plugin_active' ) && function_exists( 'is_plugin_active_for_network' ) ) {
				// Verified main-file basename in the publisher's WordPress.org archive.
				$active = is_plugin_active( 'woo-refund-and-exchange-lite/woocommerce-refund-and-exchange-lite.php' );
			}
		} catch ( Throwable $error ) {
			// Unknown context/activation state is never a no-overlap conclusion.
			$active = null;
		}
		if ( ! $admitted ) {
			return;
		}
		if ( true === $active ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Return Refund and Exchange for WooCommerce is active on this site. Review overlapping customer return requests, notifications and refund workflows before using both plugins. This best-effort check does not confirm an enabled workflow, an actual conflict or tested interoperability, and does not cover every extension. Booster does not disable, import, migrate, refund or configure the other plugin.', 'woocommerce-jetpack' ) . '</p></div>';
		} elseif ( false !== $active ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Third-party return and refund overlap checking is unavailable. Review any other return or refund extensions before using Booster Return Requests. No absence or interoperability conclusion is available.', 'woocommerce-jetpack' ) . '</p></div>';
		}
	}

	/**
	 * Observe only four bounded raw configuration values after fresh admission.
	 * This is not policy validation, storage admission or guest readiness.
	 *
	 * @return array|null Configured values, or no admitted guide.
	 */
	private function setup_observations() {
		if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			return null;
		}
		try {
			if ( ! function_exists( 'wcj_is_rest' ) || false !== wcj_is_rest()
				|| ! function_exists( 'wcj_is_module_enabled' ) || ! function_exists( 'wcj_check_modules_by_user_roles' ) ) {
				return null;
			}
			$plugin = function_exists( 'w_c_j' ) ? w_c_j() : ( function_exists( 'WCJ' ) ? WCJ() : null );
			if ( ! isset( $plugin->all_modules['returns'] ) || $plugin->all_modules['returns'] !== $this ) {
				return null;
			}
			$role_policy = wcj_is_module_enabled( 'modules_by_user_roles' );
			if ( ! is_bool( $role_policy ) ) {
				return null;
			}
			if ( $role_policy ) {
				if ( isset( $GLOBALS['wcj_modules_by_user_roles_data'] ) ) {
					$cached_policy = $GLOBALS['wcj_modules_by_user_roles_data'];
					if ( ! is_array( $cached_policy ) || ! isset( $cached_policy['role'] ) || ! function_exists( 'wp_get_current_user' ) ) {
						return null;
					}
					$current_user  = wp_get_current_user();
					$current_roles = isset( $current_user->roles ) && is_array( $current_user->roles ) ? $current_user->roles : array();
					$current_role  = empty( $current_roles ) ? 'guest' : reset( $current_roles );
					$current_role  = '' === $current_role ? 'guest' : $current_role;
					if ( ! is_string( $current_role ) || $cached_policy['role'] !== $current_role ) {
						return null;
					}
				}
				if ( true !== wcj_check_modules_by_user_roles( 'returns' ) ) {
					return null;
				}
			}
			if ( true !== $this->is_enabled() ) {
				return null;
			}
		} catch ( Throwable $error ) {
			return null;
		}
		$defaults     = array(
			'wcj_returns_window_days'   => '30',
			'wcj_returns_notifications' => 'no',
			'wcj_returns_guest'         => 'no',
			'wcj_returns_guest_page'    => '',
		);
		$observations = array();
		foreach ( $defaults as $key => $default ) {
			$value = null;
			try {
				$value = get_option( $key, $default );
			} catch ( Throwable $error ) {
				// A failed observation is unavailable, never the configured default.
				$value = null;
			}
			if ( in_array( $key, array( 'wcj_returns_notifications', 'wcj_returns_guest' ), true ) ) {
				$observations[ $key ] = in_array( $value, array( 'yes', 'no' ), true ) ? $value : null;
			} else {
				$observations[ $key ] = ( is_string( $value ) || is_int( $value ) ) && strlen( (string) $value ) <= 64 && false === strpos( (string) $value, "\0" ) && 1 === preg_match( '//u', (string) $value ) ? (string) $value : null;
			}
		}
		return $observations;
	}

	/** Render private guidance only, without creating or checking any storage. */
	private function setup_guide() {
		$observations = $this->setup_observations();
		if ( null === $observations ) {
			return;
		}
		$labels = array(
			'wcj_returns_window_days'   => __( 'Configured return window (days)', 'woocommerce-jetpack' ),
			'wcj_returns_notifications' => __( 'Notification opt-in', 'woocommerce-jetpack' ),
			'wcj_returns_guest'         => __( 'Guest verification opt-in', 'woocommerce-jetpack' ),
			'wcj_returns_guest_page'    => __( 'Configured guest Page ID', 'woocommerce-jetpack' ),
		);
		echo '<section class="wcj-returns-setup-guide"><h2>' . esc_html__( 'Return Requests setup guide', 'woocommerce-jetpack' ) . '</h2><p>' . esc_html__( 'These are bounded configured values only, not a readiness or eligibility check. They do not confirm Page existence, content, HTTPS, cache privacy, storage or email delivery.', 'woocommerce-jetpack' ) . '</p><dl>';
		foreach ( $observations as $key => $value ) {
			echo '<dt>' . esc_html( $labels[ $key ] ) . '</dt><dd>';
			if ( in_array( $key, array( 'wcj_returns_notifications', 'wcj_returns_guest' ), true ) ) {
				echo esc_html( null === $value ? __( 'Unknown', 'woocommerce-jetpack' ) : ( 'yes' === $value ? __( 'On', 'woocommerce-jetpack' ) : __( 'Off', 'woocommerce-jetpack' ) ) );
			} elseif ( null === $value ) {
				echo esc_html__( 'Unavailable', 'woocommerce-jetpack' );
			} elseif ( '' === $value ) {
				echo esc_html__( '(empty)', 'woocommerce-jetpack' );
			} else {
				echo '<code>' . esc_html( $value ) . '</code>';
			}
			echo '</dd>';
		}
		echo '</dl><ol><li>' . esc_html__( 'Configure Return Requests in Booster settings. The return window uses elapsed 24-hour days after completion of a completed order; invalid policy values block new requests.', 'woocommerce-jetpack' ) . '</li><li>' . esc_html__( 'Use the existing explicit nonce-protected private return-storage initialization form when shown below. Opening this guide never initializes storage.', 'woocommerce-jetpack' ) . '</li><li>' . esc_html__( 'Logged-in customers submit and inspect history from their own My Account order view. Merchant approval does not issue a refund or restock products; payments and stock remain separate merchant actions.', 'woocommerce-jetpack' ) . '</li><li>' . esc_html__( 'Notifications require opt-in and queue notification intents. Dispatch is manual from the merchant queue; unknown delivery must not be blindly retried.', 'woocommerce-jetpack' ) . '</li><li>' . esc_html__( 'Guest verification is a separate opt-in. It requires a published, password-free, same-origin HTTPS Page containing [wcj_returns_guest], private cache behavior, strong configured authentication prerequisites and Action Scheduler. Use the existing explicit guest-auth-storage initialization form when available. A configured Page ID does not prove these requirements.', 'woocommerce-jetpack' ) . '</li></ol><h3>' . esc_html__( 'Manual test checklist: isolated staging only', 'woocommerce-jetpack' ) . '</h3><ul><li>' . esc_html__( 'Use a merchant-controlled synthetic customer and test order in isolated staging, never a real customer or production experiment.', 'woocommerce-jetpack' ) . '</li><li>' . esc_html__( 'Complete the test order and confirm its eligible quantity and return window through the actual customer flow before submitting a request.', 'woocommerce-jetpack' ) . '</li><li>' . esc_html__( 'Inspect the request in the merchant queue and the customer order history; verify that a decision alone has not refunded or restocked anything.', 'woocommerce-jetpack' ) . '</li><li>' . esc_html__( 'Inspect notification intents intentionally. Keep test mail isolated and dispatch only as a deliberate separate test action.', 'woocommerce-jetpack' ) . '</li></ul><p>' . esc_html__( 'Automatic safe-test requests are not provided by this guide. It creates no test order or request and sends no mail.', 'woocommerce-jetpack' ) . '</p></section>';
	}

	/** Render the bounded private queue and visible notification states. */
	public function admin() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You cannot review return requests.', 'woocommerce-jetpack' ) );
		}
		if ( isset( $_GET['wcj_returns_view'] ) && is_string( $_GET['wcj_returns_view'] ) && 'synthetic' === $_GET['wcj_returns_view'] ) {
			$this->synthetic_page();
			return;
		}
		$before = isset( $_GET['before'] ) && is_string( $_GET['before'] ) && preg_match( '/^[1-9][0-9]{0,17}$/D', $_GET['before'] ) ? (int) $_GET['before'] : PHP_INT_MAX;
		$rows   = ( new WCJ_Returns_Service() )->queue( $before );
		echo '<div class="wrap"><h1>' . esc_html__( 'Return Requests', 'woocommerce-jetpack' ) . '</h1><p>' . esc_html__( 'Approval records a decision only. Refunds, restocking and payments remain separate merchant actions. Guest access requires a separately enabled, compatible HTTPS verification page and private auth storage.', 'woocommerce-jetpack' ) . '</p>';
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wcj-returns&wcj_returns_view=synthetic' ) ) . '">' . esc_html__( 'Private synthetic workflow exercise', 'woocommerce-jetpack' ) . '</a></p>';
		$this->overlap_warning();
		$this->setup_guide();
		$this->result_feedback();
		if ( is_wp_error( $rows ) ) {
			$this->storage_observation( ( new WCJ_Returns_Service() )->storage_state() );
			echo '</div>';
			return;
		}
		if ( 'yes' === get_option( 'wcj_returns_guest', 'no' ) ) {
			echo '<p>' . esc_html__( 'Guest verification requires explicit email consent, a published same-origin HTTPS Page containing [wcj_returns_guest], strong configured keys and compatible private auth storage. Initialize or repair that storage here. Cleanup is bounded and operation-driven, not guaranteed while disabled.', 'woocommerce-jetpack' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_guest_storage">';
			wp_nonce_field( 'wcj_returns_guest_storage' );
			echo '<button class="button" type="submit">' . esc_html__( 'Initialize or repair guest auth storage and prune expired rows', 'woocommerce-jetpack' ) . '</button></form>';
		}
		foreach ( $rows as $row ) {
			echo '<section><h2>' . esc_html( '#' . $row['id'] . ' / order #' . $row['order_id'] . ': ' . $row['status'] ) . '</h2>';
			if ( $row['facts_unavailable'] ) {
				echo '<p><strong>' . esc_html__( 'Order facts are unavailable. Decisions and notification dispatch are blocked until current facts can be verified. Existing claims remain held.', 'woocommerce-jetpack' ) . '</strong></p>';
			} elseif ( $row['needs_review'] ) {
				echo '<p><strong>' . esc_html__( 'Needs review: changed order facts. Claims remain held. This version conservatively holds the whole order and has no automatic or manual reconciliation action.', 'woocommerce-jetpack' ) . '</strong></p>';
			}
			$this->history( $row['data'] );
			if ( ! $row['facts_unavailable'] && 'pending' === $row['status'] ) {
				$this->decision_form( $row, $row['needs_review'] ? array( 'declined', 'cancelled' ) : array( 'approved', 'declined', 'cancelled' ) );
			}
			foreach ( $row['data']['outbox'] as $key => $event ) {
				echo '<p>' . esc_html( $key . ': ' . $event['state'] ) . '</p>';
				if ( ! $row['facts_unavailable'] && in_array( $event['state'], array( 'pending', 'failed' ), true ) && $event['attempts'] < 3 && $event['due'] <= time() ) {
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_dispatch"><input type="hidden" name="request_id" value="' . esc_attr( $row['id'] ) . '"><input type="hidden" name="event" value="' . esc_attr( $key ) . '">';
					wp_nonce_field( 'wcj_returns_dispatch_' . $row['id'] );
					echo '<button type="submit">' . esc_html__( 'Dispatch notification', 'woocommerce-jetpack' ) . '</button></form>';
				}
			}
			echo '</section>';
		}
		if ( 50 === count( $rows ) ) {
			$last = end( $rows );
			echo '<a href="' . esc_url( add_query_arg( 'before', $last['id'], admin_url( 'admin.php?page=wcj-returns' ) ) ) . '">' . esc_html__( 'Older requests', 'woocommerce-jetpack' ) . '</a>';
		}
		echo '</div>';
	}
	/**
	 * Server-selected synthetic destination, never the ordinary order redirect.
	 *
	 * @param mixed  $result Synthetic result.
	 * @param string $token Original token, retained only in a private recovery form.
	 * @param bool   $recovery Whether this was the read-only recovery action.
	 * @param int    $request_id Parsed known decision request ID, zero otherwise.
	 */
	private function synthetic_finish( $result, $token = '', $recovery = false, $request_id = 0 ) {
		if ( is_wp_error( $result ) && ( $recovery || 'commit_unknown' === $result->get_error_code() ) ) {
			$this->synthetic_page( $token, true, $request_id );
			exit;
		}
		$url = admin_url( 'admin.php?page=wcj-returns&wcj_returns_view=synthetic' );
		if ( ! is_wp_error( $result ) && is_array( $result ) && isset( $result['id'] ) ) {
			$url = add_query_arg( array( 'request_id' => (int) $result['id'], 'wcj_returns_result' => 'saved' ), $url );
		} else {
			$url = add_query_arg( 'wcj_returns_result', 'unavailable', $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/** A separate staff-only create action; no target, reason or creator input. */
	public function synthetic_create() {
		$token = $this->posted( 'token' );
		$this->synthetic_finish( ( new WCJ_Returns_Service() )->synthetic_submit( $this->posted( 'quantity' ), $token, $this->posted( '_wpnonce' ) ), $token );
	}

	/** A separate creator-bound staff decision, never ordinary order transition. */
	public function synthetic_transition() {
		$id = $this->integer( 'request_id' );
		$this->synthetic_finish( ( new WCJ_Returns_Service() )->synthetic_transition( $id, $this->integer( 'revision' ), $this->posted( 'status' ), $this->posted( '_wpnonce' ) ), '', false, $id );
	}

	/** Read-only original-token recovery; an absent result remains unknown. */
	public function synthetic_recover() {
		$token = $this->posted( 'token' );
		$this->synthetic_finish( ( new WCJ_Returns_Service() )->synthetic_recover( $token, $this->posted( '_wpnonce' ) ), $token, true );
	}

	/**
	 * Render only the private fixed-facts exercise before any live queue/policy.
	 *
	 * @param string $token Original token after an unknown response, never a new one.
	 * @param bool   $unknown Whether only read-only recovery may be offered.
	 * @param int    $request_id Parsed known decision ID for observation only.
	 */
	private function synthetic_page( $token = '', $unknown = false, $request_id = 0 ) {
		$service = new WCJ_Returns_Service();
		$state   = $service->synthetic_setup_state();
		if ( is_wp_error( $state ) ) {
			echo '<p>' . esc_html__( 'The synthetic exercise is unavailable. Current merchant permission and module/role access are required.', 'woocommerce-jetpack' ) . '</p>';
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Synthetic Returns workflow exercise', 'woocommerce-jetpack' ) . '</h1><p>' . esc_html__( 'Fixed test facts only. This is not a Woo order, a catalogue-policy test, a notification-delivery test or a compatibility test. It creates retained private exercise rows; no customer order, refund, stock, payment or mail is changed.', 'woocommerce-jetpack' ) . '</p>';
		if ( $unknown || ( isset( $_GET['wcj_returns_result'] ) && 'commit_unknown' === $_GET['wcj_returns_result'] ) ) {
			echo '<p>' . esc_html__( 'The previous outcome is unknown. Use only a fresh read of the original exercise token. An absent result remains unknown. Do not create, retry, choose a new token or delete data to resolve it.', 'woocommerce-jetpack' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_synthetic_recover">';
			wp_nonce_field( 'wcj_returns_synthetic_recover' );
			$value = is_string( $token ) && preg_match( '/^[a-f0-9]{64}$/D', $token ) ? $token : '';
			echo '<label>' . esc_html__( 'Original exercise token', 'woocommerce-jetpack' ) . '<input name="token" maxlength="64" required value="' . esc_attr( $value ) . '"></label><button type="submit">' . esc_html__( 'Read retained outcome only', 'woocommerce-jetpack' ) . '</button></form>';
			if ( is_int( $request_id ) && $request_id > 0 && $request_id <= 999999999999999999 ) {
				$url = add_query_arg( 'request_id', $request_id, admin_url( 'admin.php?page=wcj-returns&wcj_returns_view=synthetic' ) );
				echo '<p><a href="' . esc_url( $url ) . '">' . esc_html__( 'Read current retained request only', 'woocommerce-jetpack' ) . '</a></p>';
			}
			echo '</div>';
			return;
		}
		if ( 'ready' !== $state ) {
			$this->storage_observation( $state );
			echo '</div>';
			return;
		}
		if ( isset( $_GET['wcj_returns_result'] ) && 'saved' === $_GET['wcj_returns_result'] ) {
			echo '<p>' . esc_html__( 'The synthetic exercise result was retained. Inspect its history below; no Woo order is involved.', 'woocommerce-jetpack' ) . '</p>';
		} elseif ( isset( $_GET['wcj_returns_result'] ) && 'unavailable' === $_GET['wcj_returns_result'] ) {
			echo '<p>' . esc_html__( 'The synthetic exercise action was unavailable. No automatic retry was made.', 'woocommerce-jetpack' ) . '</p>';
		}
		if ( isset( $_GET['request_id'] ) ) {
			$value = $_GET['request_id'];
			$id    = is_string( $value ) && preg_match( '/^[1-9][0-9]{0,17}$/D', $value ) ? (int) $value : 0;
			$row   = $service->synthetic_view( $id );
			if ( is_wp_error( $row ) ) {
				echo '<p>' . esc_html__( 'This retained exercise is unavailable to the current merchant.', 'woocommerce-jetpack' ) . '</p></div>';
				return;
			}
			echo '<h2>' . esc_html( '#' . $row['id'] . ': ' . $row['status'] ) . '</h2><p>' . esc_html__( 'Synthetic test item: fixed capacity 2. Approval retains the claim; decline or cancellation releases it. A terminal exercise cannot be reopened.', 'woocommerce-jetpack' ) . '</p>';
			$this->history( $row['data'] );
			if ( 'pending' === $row['status'] ) {
				foreach ( array( 'approved', 'declined', 'cancelled' ) as $status ) {
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_synthetic_transition"><input type="hidden" name="request_id" value="' . esc_attr( $row['id'] ) . '"><input type="hidden" name="revision" value="' . esc_attr( $row['revision'] ) . '">';
					wp_nonce_field( 'wcj_returns_synthetic_transition_' . $row['id'] );
					echo '<button type="submit" name="status" value="' . esc_attr( $status ) . '">' . esc_html( $status ) . '</button></form>';
				}
			}
			echo '</div>';
			return;
		}
		try {
			$token = bin2hex( random_bytes( 32 ) );
		} catch ( Throwable $error ) {
			echo '<p>' . esc_html__( 'A secure exercise token could not be created.', 'woocommerce-jetpack' ) . '</p></div>';
			return;
		}
		echo '<p>' . esc_html__( 'Keep the original token before submitting for read-only recovery if an outcome is unknown. Each deliberate create retains another private exercise; there is no automatic cleanup.', 'woocommerce-jetpack' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_synthetic_create">';
		wp_nonce_field( 'wcj_returns_synthetic_create' );
		echo '<label>' . esc_html__( 'Exercise token (keep a copy)', 'woocommerce-jetpack' ) . '<input name="token" readonly value="' . esc_attr( $token ) . '"></label><label>' . esc_html__( 'Synthetic quantity', 'woocommerce-jetpack' ) . '<input name="quantity" type="number" min="0.000001" max="2" step="0.000001" value="1" required></label><button type="submit">' . esc_html__( 'Create private synthetic request', 'woocommerce-jetpack' ) . '</button></form></div>';
	}

	/**
	 * Finite setup observation with no destructive or misleading retry action.
	 *
	 * @param mixed $state Fresh admitted state, never a raw exception.
	 */
	private function storage_observation( $state ) {
		if ( 'initializable' === $state ) {
			echo '<p>' . esc_html__( 'Private storage is absent or exactly new and empty. Initialize it explicitly before use.', 'woocommerce-jetpack' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_storage">';
			wp_nonce_field( 'wcj_returns_storage' );
			echo '<button class="button" type="submit">' . esc_html__( 'Initialize private return storage', 'woocommerce-jetpack' ) . '</button></form>';
		} elseif ( 'incompatible' === $state ) {
			echo '<p>' . esc_html__( 'Existing private return storage is incompatible and has been preserved. No automatic repair, migration or cleanup is available. A separately authorized compatibility decision is required.', 'woocommerce-jetpack' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Private return storage is unavailable. No initialization or repair was attempted.', 'woocommerce-jetpack' ) . '</p>';
		}
	}

}

return new WCJ_Returns();

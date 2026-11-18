<?php
/**
 * Returns job: storewide return window, notification intents, one Elite item rule (while Returns is on) and
 * (when off) the module switch in one draft, an illustrative elapsed-day Now/After example, conditional apply,
 * readback receipt and latest-only undo. Writes only wcj_returns_window_days, wcj_returns_notifications,
 * wcj_returns_enabled when explicitly ticked and saved item-rule lines, and only one rule line per apply: all
 * other rule lines keep their bytes. Activation is refused here whenever Elite item rules are saved, because the
 * native check cannot run before the module is on. Guest settings and role policy are read, never written.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wcj-job-store.php';

if ( ! class_exists( 'WCJ_Returns_Job' ) ) :

	/**
	 * Returns settings adapter. Intake, eligibility, storage and dispatch stay in the native Returns engine.
	 */
	class WCJ_Returns_Job {

		// Site-local job state. Not wcj_-prefixed: never exported, and a backup containing it is refused by import.
		const RECEIPT = 'booster_returns_job_receipt';
		const LOCK    = 'booster_returns_job_lock';

		const RECEIPT_V = 1;

		const MODULE = 'wcj_returns_enabled';
		const WINDOW = 'wcj_returns_window_days';
		const NOTIFY = 'wcj_returns_notifications';
		const RULES  = 'wcj_returns_policy_rules';
		const GUEST  = 'wcj_returns_guest';
		const PAGE   = 'wcj_returns_guest_page';
		const ROLES  = 'wcj_modules_by_user_roles_';

		const OPS = array( 'load', 'preview', 'apply', 'recover', 'resolve' );

		/** Field => option, in write order: the switch is always written last. Rule and switch never share an apply. */
		const EDIT = array(
			'window' => self::WINDOW,
			'notify' => self::NOTIFY,
			'rule'   => self::RULES,
			'module' => self::MODULE,
		);

		/** Native Elite rule line grammar, copied from WCJ_Returns_Service::policy_rules(). */
		const RULE_LINE = '/^(product|variation|category):([1-9][0-9]{0,17})=(exclude|[1-9][0-9]{0,2})$/D';

		/** Receipt states that leave settings needing an owner check. */
		const INCOMPLETE = array( 'apply_incomplete', 'undo_incomplete' );

		/** Synthetic elapsed days since completion used on both sides of every example. Not real orders. */
		const EXAMPLE_DAYS = array( 10, 20, 45 );

		/**
		 * Construct.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
			foreach ( self::OPS as $op ) {
				add_action( 'wp_ajax_wcj_returns_job_' . $op, array( $this, 'handle' ) );
			}
		}

		/**
		 * Page URL.
		 *
		 * @return string
		 */
		public static function url() {
			return admin_url( 'admin.php?page=wcj-returns-job' );
		}

		/**
		 * Native Returns settings.
		 *
		 * @return string
		 */
		public static function native_url() {
			return admin_url( wcj_admin_tab_url() . '&wcj-cat=shipping_and_orders&section=returns' );
		}

		/**
		 * Whether this edition's native engine enforces item rules. Decided by the actual service source: only
		 * the Elite service has policy_diagnostic() / resolve_policy(); Free and Plus never read the rule option.
		 *
		 * @return bool
		 */
		public static function rules_enforced() {
			return class_exists( 'WCJ_Returns_Service' ) && method_exists( 'WCJ_Returns_Service', 'policy_diagnostic' );
		}

		/**
		 * Capability check used by every entry point.
		 *
		 * @throws RuntimeException When the user lacks the native capability.
		 */
		public static function authorize() {
			if ( ! WCJ_Task_Finder::allowed( 'returns' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by WooCommerce.
				throw new RuntimeException( esc_html__( 'You need permission to manage WooCommerce.', 'woocommerce-jetpack' ) );
			}
		}

		/**
		 * Register submenu.
		 */
		public function register_page() {
		if ( ! WCJ_Task_Finder::allowed( 'returns' ) ) { return; }
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability.
			add_submenu_page( 'woocommerce', __( 'Return requests', 'woocommerce-jetpack' ), __( 'Return requests', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-returns-job', array( $this, 'render' ) );
		}

		/**
		 * Parse a submitted JSON object.
		 *
		 * @param string $field POST field.
		 * @return array
		 * @throws RuntimeException On malformed input.
		 */
		private static function posted_json( $field ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput -- Nonce verified in handle(); bounded JSON strictly validated per field.
			$raw = isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '{}';
			if ( strlen( $raw ) > 4000 ) {
				throw new RuntimeException( esc_html__( 'The draft is too large.', 'woocommerce-jetpack' ) );
			}
			$data = json_decode( $raw, true, 3 );
			if ( ! is_array( $data ) ) {
				throw new RuntimeException( esc_html__( 'The draft could not be read. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return $data;
		}

		// ---------------------------------------------------------------------
		// Snapshot: every Returns option and role-policy row in one SELECT, past every cache.
		// ---------------------------------------------------------------------

		/**
		 * One consistent read of the rows; not a lock.
		 *
		 * @return array|null Option => raw value sorted by name, or null when the read failed.
		 */
		private static function snapshot() {
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name IN (%s, %s, %s, %s, %s, %s)", $wpdb->esc_like( self::ROLES ) . '%', self::MODULE, self::WINDOW, self::NOTIFY, self::RULES, self::GUEST, self::PAGE ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( ! is_array( $rows ) || '' !== (string) $wpdb->last_error ) {
				return null;
			}
			$out = array();
			foreach ( $rows as $row ) {
				$out[ (string) $row['option_name'] ] = (string) $row['option_value'];
			}
			ksort( $out, SORT_STRING );
			return $out;
		}

		/**
		 * Snapshot for a read step; a failed read is never treated as absent settings.
		 *
		 * @return array
		 * @throws RuntimeException When the settings cannot be read.
		 */
		private static function read_snapshot() {
			$snap = self::snapshot();
			if ( null === $snap ) {
				throw new RuntimeException( esc_html__( 'The Returns settings could not be read. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return $snap;
		}

		/**
		 * Option state inside a snapshot, in the store's { exists, raw } shape.
		 *
		 * @param array  $snap snapshot().
		 * @param string $option Option.
		 * @return array
		 */
		private static function state_in( $snap, $option ) {
			return isset( $snap[ $option ] ) ? array(
				'exists' => true,
				'raw'    => $snap[ $option ],
			) : array(
				'exists' => false,
				'raw'    => '',
			);
		}

		/**
		 * Context this job depends on but does not write in this step: item rules (unless the step writes them,
		 * when they are checked exactly as an own write instead), guest settings, every role-policy row,
		 * the edition's rule enforcement and the runtime allowlist.
		 *
		 * @param array|null $snap snapshot().
		 * @param bool       $own_rules True when this apply or undo writes the rule option itself.
		 * @return string|null Fingerprint, null for a failed read.
		 */
		private static function guard( $snap, $own_rules = false ) {
			if ( null === $snap ) {
				return null;
			}
			$ctx = array(
				'rules'    => $own_rules ? 'own' : self::state_in( $snap, self::RULES ),
				'guest'    => self::state_in( $snap, self::GUEST ),
				'page'     => self::state_in( $snap, self::PAGE ),
				'enforced' => self::rules_enforced(),
				'allowed'  => ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( 'returns' ),
			);
			foreach ( $snap as $name => $raw ) {
				if ( 0 === strpos( $name, self::ROLES ) ) {
					$ctx[ $name ] = $raw;
				}
			}
			return hash( 'sha256', serialize( $ctx ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Fingerprint only.
		}

		/**
		 * Native window grammar: canonical 1-365, no leading zero, sign, exponent or space.
		 *
		 * @param mixed $value Value.
		 * @return int|null
		 */
		private static function window_of( $value ) {
			if ( ( ! is_string( $value ) && ! is_int( $value ) ) || ! preg_match( '/^[1-9][0-9]{0,2}$/D', (string) $value ) || (int) $value > 365 ) {
				return null;
			}
			return (int) $value;
		}

		/**
		 * Count of non-blank saved rule lines. Never parsed or rewritten here.
		 *
		 * @param array $state Rule option state.
		 * @return int
		 */
		private static function rule_lines( $state ) {
			if ( ! $state['exists'] ) {
				return 0;
			}
			$value = WCJ_Job_Store::state_value( $state );
			if ( ! is_string( $value ) ) {
				return -1;
			}
			$count = 0;
			foreach ( explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $value ) ) as $line ) {
				$count += '' !== trim( $line, " \t" ) ? 1 : 0;
			}
			return $count;
		}

		/**
		 * Saved rules under the native grammar and limits: 8192 bytes, 100 lines, canonical IDs, no duplicate target.
		 *
		 * @param array $state Rule option state.
		 * @return array|null 'kind:id' => value, null when the native parser would reject the saved option.
		 */
		private static function parse_rules( $state ) {
			$text = $state['exists'] ? WCJ_Job_Store::state_value( $state ) : '';
			if ( ! is_string( $text ) || strlen( $text ) > 8192 ) {
				return null;
			}
			$seen = array();
			foreach ( explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $text ) ) as $line ) {
				$line = trim( $line, " \t" );
				if ( '' === $line ) {
					continue;
				}
				if ( count( $seen ) >= 100 || ! preg_match( self::RULE_LINE, $line, $m ) || (string) (int) $m[2] !== $m[2] || isset( $seen[ $m[1] . ':' . $m[2] ] ) || ( 'exclude' !== $m[3] && (int) $m[3] > 365 ) ) {
					return null;
				}
				$seen[ $m[1] . ':' . $m[2] ] = $m[3];
			}
			return $seen;
		}

		/**
		 * Name of an existing rule target, checked the way the native resolver checks it.
		 *
		 * @param string $kind product, variation or category.
		 * @param int    $id ID.
		 * @return string|null Label, null when the target does not exist or is trashed.
		 */
		private static function target_label( $kind, $id ) {
			$names = array(
				'product'   => __( 'Product', 'woocommerce-jetpack' ),
				'variation' => __( 'Variation', 'woocommerce-jetpack' ),
				'category'  => __( 'Category', 'woocommerce-jetpack' ),
			);
			if ( 'category' === $kind ) {
				$term = get_term( $id, 'product_cat' );
				if ( ! $term instanceof WP_Term || (int) $term->term_id !== $id ) {
					return null;
				}
				$title = $term->name;
			} else {
				$post = get_post( $id );
				if ( ! $post instanceof WP_Post || (int) $post->ID !== $id || 'trash' === $post->post_status || ( 'product' === $kind ? 'product' : 'product_variation' ) !== $post->post_type ) {
					return null;
				}
				if ( 'variation' === $kind ) {
					$parent = $post->post_parent > 0 ? get_post( (int) $post->post_parent ) : null;
					if ( ! $parent instanceof WP_Post || 'product' !== $parent->post_type || 'trash' === $parent->post_status ) {
						return null;
					}
				}
				$title = $post->post_title;
			}
			return trim( $names[ $kind ] . ' #' . $id . ' ' . wp_html_excerpt( (string) $title, 60, '…' ) );
		}

		/**
		 * Plan one rule line: add it, or replace the line for the same target. Every other line keeps its bytes.
		 *
		 * @param mixed $line Drafted line in native grammar.
		 * @param array $snap snapshot().
		 * @param array $current view().
		 * @return array { issue } or { text, write, was, value, target }
		 */
		private static function rule_plan( $line, $snap, $current ) {
			if ( ! $current['env']['rules_edit'] ) {
				return array( 'issue' => __( 'Item rules cannot be changed here now. Use the full settings.', 'woocommerce-jetpack' ) );
			}
			if ( ! is_string( $line ) || ! preg_match( self::RULE_LINE, $line, $m ) || (string) (int) $m[2] !== $m[2] || ( 'exclude' !== $m[3] && (int) $m[3] > 365 ) ) {
				return array( 'issue' => __( 'Enter an ID, and days from 1 to 365 or exclude.', 'woocommerce-jetpack' ) );
			}
			$target = self::target_label( $m[1], (int) $m[2] );
			if ( null === $target ) {
				return array( 'issue' => __( 'No item with that ID and type was found. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			$state = self::state_in( $snap, self::RULES );
			$seen  = self::parse_rules( $state );
			$text  = $state['exists'] ? WCJ_Job_Store::state_value( $state ) : '';
			$key   = $m[1] . ':' . $m[2];
			$was   = isset( $seen[ $key ] ) ? $seen[ $key ] : null;
			if ( null === $was ) {
				$new   = $text . ( '' === $text || in_array( substr( $text, -1 ), array( "\n", "\r" ), true ) ? '' : "\n" ) . $line;
				$lines = count( $seen ) + 1;
			} else {
				$parts = preg_split( '/(\r\n|\r|\n)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
				$count = count( $parts );
				for ( $i = 0; $i < $count; $i += 2 ) {
					if ( 0 === strpos( trim( $parts[ $i ], " \t" ), $key . '=' ) ) {
						$parts[ $i ] = $line;
					}
				}
				$new   = implode( '', $parts );
				$lines = count( $seen );
			}
			if ( $lines > 100 || strlen( $new ) > 8192 ) {
				return array( 'issue' => __( 'The saved item rules are at their limit. Use the full settings.', 'woocommerce-jetpack' ) );
			}
			return array(
				'text'   => $new,
				'write'  => $was !== $m[3],
				'was'    => $was,
				'value'  => $m[3],
				'target' => $target,
			);
		}

		/**
		 * Saved values and environment from one snapshot.
		 *
		 * @param array $snap snapshot().
		 * @return array
		 */
		private static function view( $snap ) {
			$window  = WCJ_Job_Store::state_value( self::state_in( $snap, self::WINDOW ) );
			$window  = null === $window ? '30' : $window; // Native default when the row is absent.
			$notify  = WCJ_Job_Store::state_value( self::state_in( $snap, self::NOTIFY ) );
			$allowed = ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( 'returns' );
			$roles   = 'yes' === WCJ_Job_Store::state_value( self::state_in( $snap, self::ROLES . 'enabled' ) ) || ( function_exists( 'wcj_is_module_enabled' ) && true === wcj_is_module_enabled( 'modules_by_user_roles' ) );
			$base    = array( 'guard' => self::guard( $snap ) );
			foreach ( self::EDIT as $field => $option ) {
				$base[ $field ] = WCJ_Job_Store::state_hash( self::state_in( $snap, $option ) );
			}
			$saved    = self::window_of( $window );
			$on       = 'yes' === WCJ_Job_Store::state_value( self::state_in( $snap, self::MODULE ) );
			$rules    = self::state_in( $snap, self::RULES );
			$lines    = self::rule_lines( $rules );
			$enforced = self::rules_enforced();
			return array(
				'saved' => array(
					'window'       => null === $saved ? '' : (string) $saved,
					'window_raw'   => is_scalar( $window ) ? substr( (string) $window, 0, 40 ) : '',
					'window_valid' => null !== $saved,
					'notify'       => 'yes' === $notify ? 'yes' : 'no',
					'module'       => $on ? 'yes' : 'no',
				),
				'env'   => array(
					'allowed'    => $allowed,
					'roles'      => $roles,
					'guest'      => 'yes' === WCJ_Job_Store::state_value( self::state_in( $snap, self::GUEST ) ),
					'rules'      => $lines,
					'enforced'   => $enforced,
					// Saved Elite rules cannot be checked natively until Returns is on, so activation goes to the full settings.
					'lock'       => $enforced && 0 !== $lines,
					'rules_edit' => $enforced && $on && $allowed && null !== self::parse_rules( $rules ),
				),
				'base'  => $base,
			);
		}

		/**
		 * Current state and latest receipt.
		 *
		 * @param array|null $snap snapshot(); read now when null.
		 * @return array
		 */
		public static function load( $snap = null ) {
			self::authorize();
			$snap           = null === $snap ? self::read_snapshot() : $snap;
			$out            = self::view( $snap );
			$receipt        = get_option( self::RECEIPT, null );
			$out['receipt'] = is_array( $receipt ) ? self::receipt_view( $receipt, $snap ) : null;
			return $out;
		}

		// ---------------------------------------------------------------------
		// Draft validation and the illustrative example.
		// ---------------------------------------------------------------------

		/**
		 * Validate a draft against one snapshot and work out the options it changes.
		 *
		 * @param array $draft Posted draft.
		 * @param array $snap snapshot().
		 * @return array { issues, writes => [field => raw value], current }
		 * @throws RuntimeException On unknown fields.
		 */
		private static function validate( $draft, $snap ) {
			$current = self::view( $snap );
			foreach ( array_keys( $draft ) as $key ) {
				if ( ! isset( self::EDIT[ $key ] ) ) {
					throw new RuntimeException( esc_html__( 'Unknown Returns setting. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
			}
			$saved  = $current['saved'];
			$issues = array();
			$writes = array();
			$rule   = null;
			if ( array_key_exists( 'window', $draft ) ) {
				$days = is_string( $draft['window'] ) ? self::window_of( $draft['window'] ) : null;
				if ( null === $days ) {
					$issues['window'] = __( 'Enter a whole number of days from 1 to 365.', 'woocommerce-jetpack' );
				} elseif ( (string) $days !== $saved['window'] || ! $saved['window_valid'] ) {
					$writes['window'] = (string) $days;
				}
			} elseif ( ! $saved['window_valid'] ) {
				// Every apply needs a valid effective policy, including module-only or notification changes.
				$issues['window'] = __( 'Enter a whole number of days from 1 to 365.', 'woocommerce-jetpack' );
			}
			if ( array_key_exists( 'notify', $draft ) ) {
				if ( ! in_array( $draft['notify'], array( 'yes', 'no' ), true ) ) {
					$issues['notify'] = __( 'Choose on or off.', 'woocommerce-jetpack' );
				} elseif ( $draft['notify'] !== $saved['notify'] ) {
					$writes['notify'] = $draft['notify'];
				}
			}
			if ( array_key_exists( 'module', $draft ) ) {
				// Turning on only. Turning Returns off stays in the native settings.
				if ( 'yes' !== $draft['module'] ) {
					throw new RuntimeException( esc_html__( 'Return requests can only be turned on here. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
				if ( ! $current['env']['allowed'] ) {
					$issues['module'] = __( 'A site setting limits which Booster modules can run, and it does not include Returns.', 'woocommerce-jetpack' );
				} elseif ( 'yes' !== $saved['module'] && $current['env']['lock'] ) {
					$issues['module'] = __( 'Item rules are saved, so turn Returns on in the full settings, where those rules are checked.', 'woocommerce-jetpack' );
				} elseif ( 'yes' !== $saved['module'] ) {
					$writes['module'] = 'yes';
				}
			}
			if ( array_key_exists( 'rule', $draft ) ) {
				$plan = self::rule_plan( $draft['rule'], $snap, $current );
				if ( isset( $plan['issue'] ) ) {
					$issues['rule'] = $plan['issue'];
				} elseif ( $plan['write'] ) {
					$writes['rule'] = $plan['text'];
					$rule           = $plan;
				}
			}
			return array(
				'issues'  => $issues,
				'writes'  => $writes,
				'rule'    => $rule,
				'current' => $current,
			);
		}

		/**
		 * Plain elapsed-day arithmetic on the synthetic ages. Not the native resolver: it ignores item rules,
		 * reserved quantities, role policy, storage and order state.
		 *
		 * @param int|string|null $days Window, 'exclude' for an excluded item, null when invalid.
		 * @param bool            $on Module switched on.
		 * @return array
		 */
		private static function example( $days, $on ) {
			$days = is_string( $days ) && 'exclude' !== $days ? (int) $days : $days;
			$out  = array();
			foreach ( self::EXAMPLE_DAYS as $age ) {
				if ( ! $on ) {
					$state = 'off';
				} elseif ( 'exclude' === $days ) {
					$state = 'excluded';
				} elseif ( null === $days ) {
					$state = 'blocked';
				} else {
					$state = $age < $days ? 'inside' : ( $age === $days ? 'edge' : 'outside' );
				}
				$out[] = array(
					'age'   => $age,
					'state' => $state,
				);
			}
			return $out;
		}

		/**
		 * Old/new rows for review.
		 *
		 * @param array $check validate().
		 * @return array
		 */
		private static function change_rows( $check ) {
			$saved = $check['current']['saved'];
			$rows  = array();
			foreach ( $check['writes'] as $field => $value ) {
				if ( 'rule' === $field ) {
					$rule   = $check['rule'];
					$old    = null === $rule['was'] ? ( $saved['window_valid'] ? $saved['window'] : null ) : $rule['was'];
					$rows[] = array(
						'field'   => 'rule',
						'target'  => $rule['target'],
						'before'  => $rule['was'],
						'after'   => $rule['value'],
						'shorter' => 'exclude' === $rule['value'] ? 'exclude' !== $old : ( null !== $old && 'exclude' !== $old && (int) $rule['value'] < (int) $old ),
					);
					continue;
				}
				$before = 'window' === $field ? ( $saved['window_valid'] ? $saved['window'] : $saved['window_raw'] ) : $saved[ $field ];
				$rows[] = array(
					'field'   => $field,
					'before'  => $before,
					'after'   => $value,
					'shorter' => 'window' === $field && $saved['window_valid'] && (int) $value < (int) $saved['window'],
				);
			}
			return $rows;
		}

		/**
		 * Preview: saved Now and drafted After on the same synthetic ages. Writes nothing.
		 *
		 * @return array
		 */
		public static function preview() {
			self::authorize();
			$snap  = self::read_snapshot();
			$check = self::validate( self::posted_json( 'draft' ), $snap );
			if ( $check['issues'] ) {
				return array(
					'error'  => __( 'Fix the highlighted fields to see the example.', 'woocommerce-jetpack' ),
					'issues' => $check['issues'],
				);
			}
			if ( isset( $check['writes']['rule'] ) && ! self::rules_checkable() ) {
				return array(
					'error'  => __( 'Fix the highlighted fields to see the example.', 'woocommerce-jetpack' ),
					'issues' => array( 'rule' => self::unchecked_text() ),
				);
			}
			$saved     = $check['current']['saved'];
			$now_days  = $saved['window_valid'] ? (int) $saved['window'] : null;
			$now_on    = 'yes' === $saved['module'] && $check['current']['env']['allowed'];
			$after_win = isset( $check['writes']['window'] ) ? (int) $check['writes']['window'] : $now_days;
			$after_on  = $now_on || isset( $check['writes']['module'] );
			$item      = null;
			if ( $check['rule'] ) {
				// A line item matched only by this target: its own rule, else the storewide window.
				$item = array(
					'now'   => self::example( null === $check['rule']['was'] ? $now_days : $check['rule']['was'], $now_on ),
					'after' => self::example( $check['rule']['value'], $after_on ),
				);
			}
			return array(
				'changes' => self::change_rows( $check ),
				'now'     => self::example( $now_days, $now_on ),
				'after'   => self::example( $after_win, $after_on ),
				'item'    => $item,
				'env'     => $check['current']['env'],
			);
		}

		/**
		 * Whether the native Elite check passes on the saved settings now, so a rule write can be verified.
		 *
		 * @return bool
		 */
		private static function rules_checkable() {
			return in_array( self::diagnostic(), array( 'basic', 'valid' ), true );
		}

		/**
		 * Message when the native check cannot vouch for item rules.
		 *
		 * @return string
		 */
		private static function unchecked_text() {
			return __( 'Booster cannot check item rules on this site right now (Returns storage, role settings, caching or an invalid saved setting). Nothing was changed; use the full settings.', 'woocommerce-jetpack' );
		}

		/**
		 * Whether a receipt holds an unfinished apply or undo the owner has not yet confirmed as checked.
		 *
		 * @param mixed $receipt Stored receipt.
		 * @return bool
		 */
		private static function pending( $receipt ) {
			return is_array( $receipt ) && ( ( isset( $receipt['status'] ) && in_array( $receipt['status'], self::INCOMPLETE, true ) ) || ( isset( $receipt['unfinished'] ) && is_array( $receipt['unfinished'] ) ) );
		}

		// ---------------------------------------------------------------------
		// Writes.
		// ---------------------------------------------------------------------

		/**
		 * Put back own writes in reverse order, each only while it still holds what was written.
		 *
		 * @param array $journal Entries keyed by option, in write order.
		 * @param bool  $forward True to re-apply 'after' (reversing an undo), false to restore 'before'.
		 * @return array { done, unresolved } option => entry.
		 */
		private static function reverse( $journal, $forward ) {
			$done       = array();
			$unresolved = array();
			foreach ( array_reverse( $journal, true ) as $option => $entry ) {
				$from = $forward ? $entry['before'] : $entry['after'];
				$to   = $forward ? $entry['after'] : $entry['before'];
				$cur  = WCJ_Job_Store::read_state( $option );
				if ( $cur === $to || ( $cur === $from && WCJ_Job_Store::compare_exchange( $option, $from, $to ) ) ) {
					$done[ $option ] = $entry;
				} else {
					$unresolved[ $option ] = $entry;
				}
			}
			return array(
				'done'       => $done,
				'unresolved' => $unresolved,
			);
		}

		/**
		 * Native policy diagnostic on saved state (Elite only). Fixed finite code, never rule text.
		 *
		 * @return string
		 */
		private static function diagnostic() {
			if ( ! self::rules_enforced() ) {
				return '';
			}
			try {
				$code = ( new WCJ_Returns_Service() )->policy_diagnostic();
			} catch ( Throwable $error ) {
				return 'suppress';
			}
			return in_array( $code, array( 'basic', 'valid', 'policy_invalid', 'policy_facts_unavailable', 'suppress' ), true ) ? $code : 'suppress';
		}

		/**
		 * Apply one reviewed draft.
		 *
		 * @return array
		 * @throws RuntimeException On unconfirmed or concurrent requests.
		 */
		public static function apply() {
			self::authorize();
			$draft = self::posted_json( 'draft' );
			$base  = self::posted_json( 'base' );
			if ( ! $draft ) {
				throw new RuntimeException( esc_html__( 'The draft is empty. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			$lock = WCJ_Job_Store::lock( self::LOCK, __( 'Another Returns apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
			try {
				$snap = self::read_snapshot();
				// An unfinished record stays the latest until the owner confirms it was checked; nothing replaces it.
				if ( self::pending( get_option( self::RECEIPT, null ) ) ) {
					return array(
						'status' => 'pending',
						'error'  => __( 'An earlier change did not finish. Check it in the full settings and confirm below first. Nothing was changed.', 'woocommerce-jetpack' ),
						'fresh'  => self::load( $snap ),
					);
				}
				$check = self::validate( $draft, $snap );
				if ( $check['issues'] ) {
					return array(
						'status' => 'invalid',
						'issues' => $check['issues'],
						'error'  => __( 'Some changes need attention. Nothing was changed.', 'woocommerce-jetpack' ),
					);
				}
				if ( ! $check['writes'] ) {
					return array(
						'status'  => 'unchanged',
						'message' => __( 'These values are already saved. Nothing was written.', 'woocommerce-jetpack' ),
						'fresh'   => self::load( $snap ),
					);
				}
				// Every change here affects which new requests can start, so each goes through the review.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
				if ( ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] ) {
					throw new RuntimeException( esc_html__( 'Confirm the changes in the review before applying. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
				$fresh = $check['current']['base'];
				foreach ( array_merge( array_keys( self::EDIT ), array( 'guard' ) ) as $key ) {
					if ( ! isset( $base[ $key ] ) || ! is_string( $base[ $key ] ) || null === $fresh[ $key ] || ! hash_equals( $fresh[ $key ], $base[ $key ] ) ) {
						return array(
							'status' => 'stale',
							'error'  => __( 'Returns settings changed since you loaded this page. Nothing was changed. Compare what is saved now before applying.', 'woocommerce-jetpack' ),
							'fresh'  => self::load( $snap ),
						);
					}
				}
				$own_rules = isset( $check['writes']['rule'] );
				// A rule is written only when the native check passes before it, and kept only when it passes after.
				if ( $own_rules && ! self::rules_checkable() ) {
					return array(
						'status' => 'invalid',
						'issues' => array( 'rule' => self::unchecked_text() ),
						'error'  => __( 'Some changes need attention. Nothing was changed.', 'woocommerce-jetpack' ),
					);
				}
				$was_on  = 'yes' === $check['current']['saved']['module'];
				$expect  = (string) self::guard( $snap, $own_rules );
				$journal = array();
				$failed  = null;
				foreach ( self::EDIT as $field => $option ) {
					if ( ! isset( $check['writes'][ $field ] ) ) {
						continue;
					}
					$entry = array(
						'field'  => $field,
						'before' => self::state_in( $snap, $option ),
						'after'  => WCJ_Job_Store::value_state( $check['writes'][ $field ] ),
					);
					if ( 'rule' === $field ) {
						$entry['target'] = $check['rule']['target'];
						$entry['was']    = $check['rule']['was'];
						$entry['value']  = $check['rule']['value'];
					}
					if ( ! hash_equals( $expect, (string) self::guard( self::snapshot(), $own_rules ) ) || ! WCJ_Job_Store::compare_exchange( $option, $entry['before'], $entry['after'] ) ) {
						$failed = $field;
						break;
					}
					$journal[ $option ] = $entry;
				}
				$done = null;
				if ( null === $failed ) {
					$done = self::snapshot();
					foreach ( $journal as $option => $entry ) {
						if ( null === $done || self::state_in( $done, $option ) !== $entry['after'] ) {
							$failed = $entry['field'];
							break;
						}
					}
					if ( null === $failed && ! hash_equals( $expect, (string) self::guard( $done, $own_rules ) ) ) {
						$failed = 'context';
					}
				}
				$diagnostic = '';
				if ( null === $failed && $was_on ) {
					// Only meaningful when Returns was already on; on a same-request activation it cannot run.
					$diagnostic = self::diagnostic();
					if ( $own_rules && 'valid' !== $diagnostic ) {
						$failed = 'rule';
					}
				}
				if ( null !== $failed ) {
					$back = self::reverse( $journal, false );
					if ( $back['unresolved'] ) {
						self::save_receipt( 'apply_incomplete', $back['unresolved'], '' );
					}
					if ( 'rule' === $failed ) {
						$error = $back['unresolved'] ? __( 'Booster\'s Returns check did not accept the new item rule, and part of this apply could not be put back because it changed again. It is listed below.', 'woocommerce-jetpack' ) : __( 'Booster\'s Returns check did not accept the new item rule, so this apply was put back. Check the item in the full settings.', 'woocommerce-jetpack' );
					} else {
						$error = $back['unresolved'] ? __( 'Settings changed while applying. Part of this apply could not be put back because it changed again; it is listed and remains saved.', 'woocommerce-jetpack' ) : __( 'Settings changed while applying, so this apply was put back. Newer changes were kept.', 'woocommerce-jetpack' );
					}
					return array(
						'status' => 'failed',
						'error'  => $error,
						'fresh'  => self::load(),
					);
				}
				self::save_receipt( 'applied', $journal, (string) self::guard( $done, $own_rules ) );
				return array(
					'status'     => 'applied',
					'message'    => __( 'Saved and read back from the database.', 'woocommerce-jetpack' ),
					'diagnostic' => $diagnostic,
					'fresh'      => self::load(),
				);
			} finally {
				WCJ_Job_Store::unlock( self::LOCK, $lock );
			}
		}

		// ---------------------------------------------------------------------
		// Receipt and undo.
		// ---------------------------------------------------------------------

		/**
		 * Store the latest record (autoload off). Apply refuses while a record is pending, so this never replaces
		 * one; an unconfirmed earlier record is still nested whole as a backstop, never dropped.
		 *
		 * @param string $status Status.
		 * @param array  $entries Entries keyed by option.
		 * @param string $guard Guard fingerprint read back after apply.
		 */
		private static function save_receipt( $status, $entries, $guard ) {
			$previous = get_option( self::RECEIPT, null );
			$receipt  = array(
				'v'       => self::RECEIPT_V,
				'id'      => bin2hex( random_bytes( 8 ) ),
				'time'    => gmdate( 'c' ),
				'user'    => get_current_user_id(),
				'status'  => $status,
				'entries' => $entries,
				'guard'   => $guard,
			);
			if ( self::pending( $previous ) ) {
				$receipt['unfinished'] = $previous;
			}
			update_option( self::RECEIPT, $receipt, false );
		}

		/**
		 * Receipt entries for allowlisted options with exact state shapes.
		 *
		 * @param mixed $entries Stored entries.
		 * @return array
		 */
		private static function valid_entries( $entries ) {
			$allowed = array_flip( self::EDIT );
			$state   = static function ( $s ) {
				return is_array( $s ) && isset( $s['exists'], $s['raw'] ) && is_bool( $s['exists'] ) && is_string( $s['raw'] );
			};
			$valid   = array();
			foreach ( is_array( $entries ) ? $entries : array() as $option => $entry ) {
				if ( isset( $allowed[ $option ] ) && is_array( $entry ) && isset( $entry['field'], $entry['before'], $entry['after'] ) && $allowed[ $option ] === $entry['field'] && $state( $entry['before'] ) && $state( $entry['after'] ) ) {
					$valid[ $option ] = $entry;
				}
			}
			return $valid;
		}

		/**
		 * Receipt for display, with current match state from one snapshot.
		 *
		 * @param array $receipt Stored receipt.
		 * @param array $snap snapshot().
		 * @return array
		 */
		private static function receipt_view( $receipt, $snap ) {
			$entries = isset( $receipt['entries'] ) && is_array( $receipt['entries'] ) ? $receipt['entries'] : array();
			$valid   = self::valid_entries( $entries );
			$status  = isset( $receipt['status'] ) && is_string( $receipt['status'] ) ? $receipt['status'] : '';
			$rows    = self::receipt_rows( $valid, $snap );
			$earlier = array();
			for ( $nested = $receipt; isset( $nested['unfinished'] ) && is_array( $nested['unfinished'] ) && count( $earlier ) < 20; ) {
				$nested  = $nested['unfinished'];
				$earlier = array_merge( $earlier, self::receipt_rows( self::valid_entries( isset( $nested['entries'] ) ? $nested['entries'] : array() ), $snap ) );
			}
			$guard_ok = isset( $receipt['guard'] ) && is_string( $receipt['guard'] ) && '' !== $receipt['guard'] && hash_equals( $receipt['guard'], (string) self::guard( $snap, isset( $valid[ self::RULES ] ) ) );
			$stamp    = isset( $receipt['time'] ) && is_string( $receipt['time'] ) ? strtotime( $receipt['time'] ) : false;
			return array(
				'time'       => $stamp ? ( function_exists( 'wp_date' ) ? wp_date( 'M j, g:i a', $stamp ) : date_i18n( 'M j, g:i a', $stamp ) ) : '',
				'status'     => $status,
				'rows'       => $rows,
				'earlier'    => $earlier,
				'guard_ok'   => $guard_ok,
				'undo'       => $rows && count( $valid ) === count( $entries ) && isset( $receipt['v'] ) && self::RECEIPT_V === $receipt['v'] && in_array( $status, array( 'applied', 'undo_reversed' ), true ) && $guard_ok && ! in_array( false, wp_list_pluck( $rows, 'still_ours' ), true ),
				'unfinished' => isset( $receipt['unfinished'] ) && is_array( $receipt['unfinished'] ),
				'pending'    => self::pending( $receipt ),
			);
		}

		/**
		 * Display rows for receipt entries. A rule row shows its one target and value, never the whole rule text.
		 *
		 * @param array $valid valid_entries().
		 * @param array $snap snapshot().
		 * @return array
		 */
		private static function receipt_rows( $valid, $snap ) {
			$rows = array();
			foreach ( $valid as $option => $entry ) {
				$row = array(
					'field'      => $entry['field'],
					'created'    => ! $entry['before']['exists'],
					'still_ours' => self::state_in( $snap, $option ) === $entry['after'],
				);
				if ( 'rule' === $entry['field'] ) {
					$row['target'] = isset( $entry['target'] ) && is_string( $entry['target'] ) ? $entry['target'] : '';
					$row['before'] = isset( $entry['was'] ) && is_string( $entry['was'] ) ? $entry['was'] : null;
					$row['after']  = isset( $entry['value'] ) && is_string( $entry['value'] ) ? $entry['value'] : '';
				} else {
					$before = WCJ_Job_Store::state_value( $entry['before'] );
					// Show the reviewed native default only for an absent row; keep existing malformed values distinct.
					if ( ! $entry['before']['exists'] ) {
						$before = 'window' === $entry['field'] ? '30' : 'no';
					}
					$row['before'] = null === $before ? null : ( is_scalar( $before ) ? substr( (string) $before, 0, 40 ) : '' );
					$row['after']  = (string) WCJ_Job_Store::state_value( $entry['after'] );
				}
				$rows[] = $row;
			}
			return $rows;
		}

		/**
		 * Undo the latest apply as one group, only while every written option still holds exactly what the apply
		 * wrote and the unwritten context (item rules, guest settings, role policy) is unchanged since. The switch
		 * is only in the record when this apply turned Returns on, so a Returns module that was already on is
		 * never switched off here; when it is in the record it is put back first, then the rest in reverse order.
		 * Each option returns to its exact raw prior value, including absence. Native settings writes do not take
		 * this lock, so this is detection and conditional reversal, not a transaction.
		 *
		 * @return array
		 * @throws RuntimeException When nothing is recoverable.
		 */
		public static function recover() {
			self::authorize();
			$lock = WCJ_Job_Store::lock( self::LOCK, __( 'Another Returns apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
			try {
				$receipt = get_option( self::RECEIPT, null );
				if ( ! is_array( $receipt ) || empty( $receipt['entries'] ) || ! is_array( $receipt['entries'] ) ) {
					throw new RuntimeException( esc_html__( 'There is no applied change left to undo.', 'woocommerce-jetpack' ) );
				}
				$valid = self::valid_entries( $receipt['entries'] );
				if ( ! isset( $receipt['v'] ) || self::RECEIPT_V !== $receipt['v'] || ! isset( $receipt['status'] ) || ! in_array( $receipt['status'], array( 'applied', 'undo_reversed' ), true ) || ! $valid || count( $valid ) !== count( $receipt['entries'] ) || empty( $receipt['guard'] ) || ! is_string( $receipt['guard'] ) ) {
					throw new RuntimeException( esc_html__( 'This record cannot be undone here. Nothing was changed; check the Returns settings.', 'woocommerce-jetpack' ) );
				}
				$expect    = $receipt['guard'];
				$own_rules = isset( $valid[ self::RULES ] );
				$snap      = self::read_snapshot();
				if ( ! hash_equals( $expect, (string) self::guard( $snap, $own_rules ) ) ) {
					return self::recover_result( 'partial', __( 'Item rules, guest settings or role settings changed after your apply, so nothing was undone.', 'woocommerce-jetpack' ) );
				}
				foreach ( $valid as $option => $entry ) {
					if ( self::state_in( $snap, $option ) !== $entry['after'] ) {
						return self::recover_result( 'partial', __( 'Part of your apply changed again since, so nothing was undone and it stays as it is now.', 'woocommerce-jetpack' ) );
					}
				}
				$order = array_reverse( $valid, true );
				if ( isset( $order[ self::MODULE ] ) ) {
					$order = array( self::MODULE => $order[ self::MODULE ] ) + $order;
				}
				$journal = array();
				$stopped = false;
				foreach ( $order as $option => $entry ) {
					if ( ! hash_equals( $expect, (string) self::guard( self::snapshot(), $own_rules ) ) || ! WCJ_Job_Store::compare_exchange( $option, $entry['after'], $entry['before'] ) ) {
						$stopped = true;
						break;
					}
					$journal[ $option ] = $entry;
				}
				if ( ! $stopped ) {
					$done = self::snapshot();
					foreach ( $journal as $option => $entry ) {
						$stopped = $stopped || null === $done || self::state_in( $done, $option ) !== $entry['before'];
					}
					$stopped = $stopped || ! hash_equals( $expect, (string) self::guard( $done, $own_rules ) );
				}
				if ( ! $stopped ) {
					$receipt['entries'] = array();
					$receipt['status']  = 'restored';
					update_option( self::RECEIPT, $receipt, false );
					return self::recover_result( 'restored', __( 'Undone and read from the database.', 'woocommerce-jetpack' ) );
				}
				if ( ! $journal ) {
					return self::recover_result( 'partial', __( 'Returns settings changed just before the undo started, so nothing was undone.', 'woocommerce-jetpack' ) );
				}
				$back              = self::reverse( $journal, true );
				$receipt['status'] = $back['unresolved'] ? 'undo_incomplete' : 'undo_reversed';
				update_option( self::RECEIPT, $receipt, false );
				return self::recover_result( 'partial', $back['unresolved'] ? __( 'Settings changed while undoing. The undo was reversed where possible; check the Returns settings.', 'woocommerce-jetpack' ) : __( 'Settings changed while undoing, so the undo was reversed. Your apply stays in place and the newer change was kept.', 'woocommerce-jetpack' ) );
			} finally {
				WCJ_Job_Store::unlock( self::LOCK, $lock );
			}
		}

		/**
		 * Recovery response with fresh state.
		 *
		 * @param string $status restored or partial.
		 * @param string $message Message.
		 * @return array
		 */
		private static function recover_result( $status, $message ) {
			return array(
				'status'  => $status,
				'message' => $message . ' ' . __( 'Existing return requests and history were not touched.', 'woocommerce-jetpack' ),
				'fresh'   => self::load(),
			);
		}

		/**
		 * Owner confirms an unfinished apply or undo was checked in the full settings. Writes only the receipt;
		 * the listed rows stay in it for reference. New applies are refused until this is confirmed.
		 *
		 * @return array
		 * @throws RuntimeException When unconfirmed, nothing is pending or the record cannot be saved.
		 */
		public static function resolve() {
			self::authorize();
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
			if ( ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] ) {
				throw new RuntimeException( esc_html__( 'Confirm that you checked the listed settings. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			$lock = WCJ_Job_Store::lock( self::LOCK, __( 'Another Returns apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
			try {
				$receipt = get_option( self::RECEIPT, null );
				if ( ! self::pending( $receipt ) ) {
					throw new RuntimeException( esc_html__( 'There is nothing left to confirm.', 'woocommerce-jetpack' ) );
				}
				$receipt['checked'] = array(
					'user'    => get_current_user_id(),
					'time'    => gmdate( 'c' ),
					'status'  => isset( $receipt['status'] ) && is_string( $receipt['status'] ) ? $receipt['status'] : '',
					'earlier' => isset( $receipt['unfinished'] ) ? $receipt['unfinished'] : null,
				);
				$receipt['status']  = 'checked';
				unset( $receipt['unfinished'] );
				if ( ! update_option( self::RECEIPT, $receipt, false ) ) {
					throw new RuntimeException( esc_html__( 'The confirmation could not be saved. Reload and try again.', 'woocommerce-jetpack' ) );
				}
				return array(
					'status'  => 'checked',
					'message' => __( 'Marked as checked. No Returns setting was changed.', 'woocommerce-jetpack' ),
					'fresh'   => self::load(),
				);
			} finally {
				WCJ_Job_Store::unlock( self::LOCK, $lock );
			}
		}

		/**
		 * AJAX adapter: POST, capability and per-action nonce on every action.
		 */
		public function handle() {
		if ( ! WCJ_Task_Finder::allowed( 'returns' ) ) { wp_send_json_error( array( 'error' => esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ) ), 403 ); }
			try {
				self::authorize();
				$action = isset( $_POST['action'] ) && is_string( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$op     = str_replace( 'wcj_returns_job_', '', $action );
				if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! in_array( $op, self::OPS, true ) ) {
					throw new RuntimeException( esc_html__( 'Invalid request.', 'woocommerce-jetpack' ) );
				}
				check_ajax_referer( 'wcj_returns_job_' . $op, 'nonce' );
				$result = 'load' === $op ? self::load() : call_user_func( array( __CLASS__, $op ) );
				nocache_headers();
				if ( isset( $result['error'] ) ) {
					wp_send_json_error( $result, 409 );
				}
				wp_send_json_success( $result );
			} catch ( Throwable $error ) {
				nocache_headers();
				$message = $error instanceof RuntimeException ? $error->getMessage() : __( 'The request could not complete. No success is claimed; reload to see the saved values.', 'woocommerce-jetpack' );
				wp_send_json_error( array( 'error' => $message ), 400 );
			}
		}

		/**
		 * Render the page.
		 */
		public function render() {
			if ( ! WCJ_Task_Finder::allowed( 'returns' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
			}
			wp_enqueue_style( 'wcj-invoice-job', wcj_plugin_url() . '/assets/css/admin/wcj-invoice-job.css', array(), '2.5' );
			wp_enqueue_style( 'wcj-returns-job', wcj_plugin_url() . '/assets/css/admin/wcj-returns-job.css', array( 'wcj-invoice-job' ), '1.2' );
			wp_enqueue_script( 'wcj-returns-job', wcj_plugin_url() . '/assets/js/admin/wcj-returns-job.js', array(), '1.5', true );
			$nonces = array();
			foreach ( self::OPS as $op ) {
				$nonces[ $op ] = wp_create_nonce( 'wcj_returns_job_' . $op );
			}
			wp_localize_script(
				'wcj-returns-job',
				'wcjReturnsJob',
				array(
					'url'    => admin_url( 'admin-ajax.php' ),
					'nonces' => $nonces,
					'native' => self::native_url(),
					'days'   => self::EXAMPLE_DAYS,
				)
			);
			include __DIR__ . '/views/returns-job.php';
		}
	}

endif;

new WCJ_Returns_Job();

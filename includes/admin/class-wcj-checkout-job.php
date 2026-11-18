<?php
/**
 * Checkout question job: one question slot and the shared answer-display switches in one draft,
 * real WooCommerce field markup for Classic, a labelled illustration for Blocks, conditional apply,
 * readback receipt and matching-only recovery. Writes only allowlisted native Checkout Custom Fields options.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wcj-job-store.php';

if ( ! class_exists( 'WCJ_Checkout_Job' ) ) :

	/**
	 * Checkout question adapter. Field registration, order storage and validation at checkout stay in the module.
	 */
	class WCJ_Checkout_Job {

		// Site-local job state. Not wcj_-prefixed: never exported, and a backup containing it is refused by import.
		const RECEIPT = 'booster_checkout_job_receipt';
		const LOCK    = 'booster_checkout_job_lock';

		const MODULE = 'wcj_checkout_custom_fields_enabled';
		const P      = 'wcj_checkout_custom_field_';

		/** WooCommerce version from which the Blocks integration registers fields that have cart conditions. */
		const CONDITIONAL_FIELDS_MIN_VERSION = '9.9.0';

		const OPS = array( 'load', 'preview', 'apply', 'recover' );

		/**
		 * Construct.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
			foreach ( self::OPS as $op ) {
				add_action( 'wp_ajax_wcj_checkout_job_' . $op, array( $this, 'handle' ) );
			}
		}

		/**
		 * Page URL.
		 *
		 * @param int $slot Question slot, 0 for the default.
		 * @return string
		 */
		public static function url( $slot = 0 ) {
			return admin_url( 'admin.php?page=wcj-checkout-job' . ( $slot ? '&slot=' . absint( $slot ) : '' ) );
		}

		/**
		 * Native Checkout Custom Fields settings.
		 *
		 * @return string
		 */
		public static function native_url() {
			return admin_url( wcj_admin_tab_url() . '&wcj-cat=cart_and_checkout&section=checkout_custom_fields' );
		}

		/**
		 * Capability check used by every entry point.
		 *
		 * @throws RuntimeException When the user lacks the native capability.
		 */
		public static function authorize() {
			if ( ! WCJ_Task_Finder::allowed( 'checkout_custom_fields' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by WooCommerce.
				throw new RuntimeException( esc_html__( 'You need permission to manage WooCommerce.', 'woocommerce-jetpack' ) );
			}
		}

		/**
		 * Register submenu.
		 */
		public function register_page() {
		if ( ! WCJ_Task_Finder::allowed( 'checkout_custom_fields' ) ) { return; }
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability.
			add_submenu_page( 'woocommerce', __( 'Checkout question', 'woocommerce-jetpack' ), __( 'Checkout question', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-checkout-job', array( $this, 'render' ) );
		}

		/**
		 * Checkout Custom Fields module instance, if loaded.
		 *
		 * @return object|null
		 */
		private static function module() {
			$core = WCJ_Job_Store::core();
			return $core && isset( $core->all_modules['checkout_custom_fields'] ) ? $core->all_modules['checkout_custom_fields'] : null;
		}

		/**
		 * Effective number of question slots for this edition, from the module's own entitlement expression.
		 * Free has no booster_option handler, so it is 1 whatever is stored. Never adds slots.
		 *
		 * @return int
		 */
		public static function total() {
			$module = self::module();
			if ( $module && isset( $module->wcj_checkout_custom_fields_total_number ) ) {
				$total = $module->wcj_checkout_custom_fields_total_number;
			} else {
				$total = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_checkout_custom_fields_total_number', 1 ) );
			}
			return max( 1, (int) $total );
		}

		/**
		 * Slot from the request, re-checked against the edition's effective count.
		 *
		 * @return int
		 * @throws RuntimeException When out of range.
		 */
		private static function posted_slot() {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
			$raw = isset( $_POST['slot'] ) && is_string( $_POST['slot'] ) ? sanitize_text_field( wp_unslash( $_POST['slot'] ) ) : '';
			if ( ! preg_match( '/^[1-9][0-9]{0,3}$/D', $raw ) || (int) $raw > self::total() ) {
				throw new RuntimeException( esc_html__( 'That checkout question is not available in your Booster edition. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return (int) $raw;
		}

		/**
		 * Field definitions for one slot. Order is the safe write order when a question is turned on;
		 * turning one off writes the switch first. The three shared switches apply to every Booster checkout field.
		 * 'absent' is what the module does when the option was never saved.
		 *
		 * @param int $i Slot.
		 * @return array
		 */
		public static function fields( $i ) {
			$p = self::P;
			return array(
				'type'           => array( 'option' => $p . 'type_' . $i, 'kind' => 'choice', 'choices' => array( 'text', 'textarea', 'select', 'checkbox' ), 'absent' => 'text', 'label' => __( 'Answer type', 'woocommerce-jetpack' ) ),
				'options'        => array( 'option' => $p . 'select_options_' . $i, 'kind' => 'lines', 'absent' => '', 'label' => __( 'Choices', 'woocommerce-jetpack' ) ),
				'section'        => array( 'option' => $p . 'section_' . $i, 'kind' => 'choice', 'choices' => array( 'billing', 'shipping', 'order' ), 'absent' => 'billing', 'label' => __( 'Where it appears', 'woocommerce-jetpack' ) ),
				'label'          => array( 'option' => $p . 'label_' . $i, 'kind' => 'text', 'max' => 200, 'absent' => '', 'label' => __( 'Question', 'woocommerce-jetpack' ) ),
				'placeholder'    => array( 'option' => $p . 'placeholder_' . $i, 'kind' => 'text', 'max' => 200, 'absent' => '', 'label' => __( 'Hint', 'woocommerce-jetpack' ) ),
				'required'       => array( 'option' => $p . 'required_' . $i, 'kind' => 'yesno', 'absent' => 'no', 'label' => __( 'Customers must answer', 'woocommerce-jetpack' ) ),
				'enabled'        => array( 'option' => $p . 'enabled_' . $i, 'kind' => 'yesno', 'absent' => 'no', 'label' => __( 'Ask this question', 'woocommerce-jetpack' ) ),
				// The module reads both email switches without a default, so a never-saved switch is off at runtime.
				'email_admin'    => array( 'option' => 'wcj_checkout_custom_fields_email_all_to_admin', 'kind' => 'yesno', 'absent' => 'no', 'shared' => true, 'label' => __( 'Answers in emails to you', 'woocommerce-jetpack' ) ),
				'email_customer' => array( 'option' => 'wcj_checkout_custom_fields_email_all_to_customer', 'kind' => 'yesno', 'absent' => 'no', 'shared' => true, 'label' => __( 'Answers in customer emails', 'woocommerce-jetpack' ) ),
				'thanks'         => array( 'option' => 'wcj_checkout_custom_fields_add_to_order_received', 'kind' => 'yesno', 'absent' => 'yes', 'shared' => true, 'label' => __( 'Answers on thank-you and order pages', 'woocommerce-jetpack' ) ),
			);
		}

		/**
		 * Settings the job never takes from the caller but must write for a working checkbox question.
		 * The module reads the checkbox wording without a default (Classic order meta and the Blocks bridge),
		 * so never-saved rows show answers as 1/0. Only ABSENT rows are written, with the native setting
		 * defaults; saved, custom or empty values are left exactly as they are.
		 *
		 * @param int $i Slot.
		 * @return array
		 */
		private static function derived( $i ) {
			$p = self::P;
			return array(
				'checkbox_yes' => array( 'option' => $p . 'checkbox_yes_' . $i, 'kind' => 'text', 'max' => 200, 'absent' => '', 'default' => __( 'Yes', 'woocommerce-jetpack' ), 'label' => __( 'Ticked answer reads', 'woocommerce-jetpack' ) ),
				'checkbox_no'  => array( 'option' => $p . 'checkbox_no_' . $i, 'kind' => 'text', 'max' => 200, 'absent' => '', 'default' => __( 'No', 'woocommerce-jetpack' ), 'label' => __( 'Unticked answer reads', 'woocommerce-jetpack' ) ),
			);
		}

		/**
		 * Plain one-line text: valid UTF-8, no markup, shortcode brackets, control characters or
		 * HTML entities. The same rule decides what the page may edit and what it accepts, so a saved
		 * value is never decoded or reinterpreted: anything outside it is shown raw and kept as saved.
		 *
		 * @param mixed $value Value.
		 * @param int   $max Max bytes.
		 * @return bool
		 */
		private static function plain( $value, $max ) {
			return is_string( $value ) && strlen( $value ) <= $max && false !== preg_match( '//u', $value ) && ! preg_match( '/[<>\[\]\x00-\x1F\x7F]/u', $value ) && ! preg_match( '/&(?:#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i', $value );
		}

		/**
		 * Choice list check shared by validation and display.
		 *
		 * @param mixed $value One choice per line.
		 * @return array [ lines|null, message|null ]
		 */
		private static function lines_issue( $value ) {
			$generic = __( 'Up to 30 choices, one per line, each up to 100 characters of plain text.', 'woocommerce-jetpack' );
			if ( ! is_string( $value ) || strlen( $value ) > 5000 || false === preg_match( '//u', $value ) ) {
				return array( null, $generic );
			}
			$list = self::lines( $value );
			if ( count( $list ) > 30 ) {
				return array( null, $generic );
			}
			$seen = array();
			foreach ( $list as $line ) {
				$slug = urldecode( sanitize_title( $line ) );
				if ( ! self::plain( $line, 100 ) || '' === $slug ) {
					return array( null, $generic );
				}
				if ( isset( $seen[ $slug ] ) ) {
					/* translators: 1: choice, 2: earlier choice */
					return array( null, sprintf( __( '"%1$s" is too similar to "%2$s". Blocks checkout would treat them as the same answer.', 'woocommerce-jetpack' ), $line, $seen[ $slug ] ) );
				}
				$seen[ $slug ] = $line;
			}
			return array( $list, null );
		}

		/**
		 * Displayable value and lock reason for a persisted state. Values are never decoded.
		 *
		 * @param array $def Field.
		 * @param array $state State.
		 * @return array [ value, locked message|null ]
		 */
		private static function display( $def, $state ) {
			$raw  = WCJ_Job_Store::state_value( $state );
			$kept = __( 'It is kept exactly as saved; change it in full settings.', 'woocommerce-jetpack' );
			if ( null === $raw ) {
				return array( $def['absent'], null );
			}
			switch ( $def['kind'] ) {
				case 'yesno':
					// Same test the module uses at runtime.
					return array( 'yes' === $raw ? 'yes' : 'no', null );
				case 'choice':
					if ( is_string( $raw ) && in_array( $raw, $def['choices'], true ) ) {
						return array( $raw, null );
					}
					return array( is_string( $raw ) ? $raw : '', __( 'A setting this page does not offer is saved.', 'woocommerce-jetpack' ) . ' ' . $kept );
				case 'text':
					if ( ! self::plain( $raw, $def['max'] ) ) {
						return array( is_string( $raw ) ? $raw : '', __( 'Contains formatting, an encoded character, a line break or a shortcode, or is longer than this page allows.', 'woocommerce-jetpack' ) . ' ' . $kept );
					}
					return array( $raw, null );
				case 'lines':
					$check = self::lines_issue( $raw );
					if ( null === $check[0] ) {
						return array( is_string( $raw ) ? $raw : '', __( 'These choices use formatting, an encoded character or a shortcode, or are more than this page allows.', 'woocommerce-jetpack' ) . ' ' . $kept );
					}
					return array( implode( "\n", $check[0] ), null );
			}
			return array( '', __( 'Unsupported.', 'woocommerce-jetpack' ) );
		}

		/**
		 * Non-empty trimmed lines.
		 *
		 * @param string $text Text.
		 * @return array
		 */
		private static function lines( $text ) {
			return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
		}

		/**
		 * Checkout page type, by the same rule as Compatibility Status (whose detector is protected).
		 *
		 * @return string blocks|classic|unknown
		 */
		private static function architecture() {
			if ( ! function_exists( 'wc_get_page_id' ) ) {
				return 'unknown';
			}
			$page_id = wc_get_page_id( 'checkout' );
			$post    = $page_id > 0 ? get_post( $page_id ) : null;
			if ( ! $post || empty( $post->post_content ) ) {
				return 'unknown';
			}
			if ( ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $post ) ) || false !== strpos( $post->post_content, 'wp:woocommerce/checkout' ) ) {
				return 'blocks';
			}
			return has_shortcode( $post->post_content, 'woocommerce_checkout' ) ? 'classic' : 'unknown';
		}

		/**
		 * Whether the slot has cart conditions (products, categories, cart total).
		 *
		 * @param int $i Slot.
		 * @return bool
		 */
		private static function conditioned( $i ) {
			foreach ( array( 'categories_ex_', 'categories_in_', 'products_ex_', 'products_in_' ) as $k ) {
				$v = wcj_get_option( self::P . $k . $i, '' );
				if ( ! empty( $v ) ) {
					return true;
				}
			}
			return (float) wcj_get_option( self::P . 'min_cart_amount_' . $i, 0 ) > 0 || (float) wcj_get_option( self::P . 'max_cart_amount_' . $i, 0 ) > 0;
		}

		/**
		 * Slots for the picker. A slot is free when it is off and has no question.
		 *
		 * @return array
		 */
		private static function slots() {
			$out = array();
			for ( $i = 1; $i <= self::total(); $i++ ) {
				$label = WCJ_Job_Store::state_value( WCJ_Job_Store::read_state( self::P . 'label_' . $i ) );
				$label = is_string( $label ) ? $label : '';
				$on    = 'yes' === WCJ_Job_Store::state_value( WCJ_Job_Store::read_state( self::P . 'enabled_' . $i ) );
				$out[] = array(
					'slot'  => $i,
					'label' => self::plain( $label, 200 ) ? $label : ( '' === $label ? '' : __( '(formatted question)', 'woocommerce-jetpack' ) ),
					'on'    => $on,
					'free'  => ! $on && '' === trim( $label ),
				);
			}
			return $out;
		}

		/**
		 * Environment facts that change what the page can promise.
		 *
		 * @param int $i Slot.
		 * @return array
		 */
		private static function environment( $i ) {
			$module  = WCJ_Job_Store::read_state( self::MODULE );
			$enabled = 0;
			for ( $j = 1; $j <= self::total(); $j++ ) {
				if ( 'yes' === WCJ_Job_Store::state_value( WCJ_Job_Store::read_state( self::P . 'enabled_' . $j ) ) ) {
					$enabled++;
				}
			}
			$kept = array();
			if ( self::conditioned( $i ) ) {
				$kept[] = __( 'Shown only for some carts (products, categories or cart total).', 'woocommerce-jetpack' );
			}
			if ( '' !== (string) wcj_get_option( self::P . 'description_' . $i, '' ) ) {
				$kept[] = __( 'Has a description under the field.', 'woocommerce-jetpack' );
			}
			$section = wcj_get_option( self::P . 'section_' . $i, 'billing' );
			$wc      = defined( 'WC_VERSION' ) ? WC_VERSION : '';
			$words   = array();
			foreach ( self::derived( $i ) as $def ) {
				if ( ! WCJ_Job_Store::read_state( $def['option'] )['exists'] ) {
					$words[] = $def['default'];
				}
			}
			if ( 'checkbox' === wcj_get_option( self::P . 'type_' . $i, 'text' ) && ! $words ) {
				$kept[] = __( 'Checkbox answers are shown with your saved ticked/unticked wording.', 'woocommerce-jetpack' );
			}
			return array(
				'architecture'   => self::architecture(),
				'wc_version'     => $wc,
				'conditional_ok' => '' !== $wc && version_compare( $wc, self::CONDITIONAL_FIELDS_MIN_VERSION, '>=' ),
				'conditioned'    => self::conditioned( $i ),
				'module_enabled' => 'yes' === WCJ_Job_Store::state_value( $module ),
				'module_base'    => WCJ_Job_Store::state_hash( $module ),
				'enabled_count'  => $enabled,
				'answer_key'     => ( is_string( $section ) ? $section : 'billing' ) . '_wcj_checkout_field_' . $i,
				'kept'           => $kept,
				'total'          => self::total(),
				'slots'          => self::slots(),
				'render_ok'      => null !== self::module() && function_exists( 'woocommerce_form_field' ),
				// Native default checkbox wording that would be written for a checkbox question that is on.
				'checkbox_words' => $words,
			);
		}

		/**
		 * Current values, base fingerprints and environment for a slot.
		 *
		 * @param int|null $i Slot; read from the request when null.
		 * @return array
		 */
		public static function load( $i = null ) {
			self::authorize();
			$i   = null === $i ? self::posted_slot() : $i;
			$out = array();
			foreach ( self::fields( $i ) as $key => $def ) {
				$state       = WCJ_Job_Store::read_state( $def['option'] );
				$shown       = self::display( $def, $state );
				$out[ $key ] = array(
					'value'  => $shown[0],
					'locked' => $shown[1],
					'stored' => $state['exists'],
					'base'   => WCJ_Job_Store::state_hash( $state ),
				);
			}
			$receipt = get_option( self::RECEIPT, null );
			return array(
				'slot'    => $i,
				'fields'  => $out,
				'env'     => self::environment( $i ),
				'receipt' => is_array( $receipt ) ? self::receipt_view( $receipt ) : null,
			);
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
			if ( strlen( $raw ) > 20000 ) {
				throw new RuntimeException( esc_html__( 'The draft is too large.', 'woocommerce-jetpack' ) );
			}
			$data = json_decode( $raw, true, 4 );
			if ( ! is_array( $data ) ) {
				throw new RuntimeException( esc_html__( 'The draft could not be read. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return $data;
		}

		/**
		 * Validate draft changes for a slot against current state.
		 *
		 * @param int   $i Slot.
		 * @param array $changes Values by field key.
		 * @return array { stored => [key => value], issues => [key => message], current => load(), effective => [key => value] }
		 * @throws RuntimeException On unknown fields.
		 */
		private static function validate( $i, $changes ) {
			$fields  = self::fields( $i );
			$current = self::load( $i );
			$stored  = array();
			$issues  = array();
			foreach ( $changes as $key => $value ) {
				if ( ! is_string( $key ) || ! isset( $fields[ $key ] ) ) {
					throw new RuntimeException( esc_html__( 'Unknown checkout setting. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
				$def = $fields[ $key ];
				if ( $current['fields'][ $key ]['locked'] ) {
					$issues[ $key ] = __( 'This is kept as saved here. Change it in full settings.', 'woocommerce-jetpack' );
					continue;
				}
				switch ( $def['kind'] ) {
					case 'yesno':
						if ( 'yes' !== $value && 'no' !== $value ) {
							$issues[ $key ] = __( 'Choose on or off.', 'woocommerce-jetpack' );
						} else {
							$stored[ $key ] = $value;
						}
						break;
					case 'choice':
						if ( ! is_string( $value ) || ! in_array( $value, $def['choices'], true ) ) {
							$issues[ $key ] = __( 'Choose one of the options shown.', 'woocommerce-jetpack' );
						} else {
							$stored[ $key ] = $value;
						}
						break;
					case 'text':
						$text = is_string( $value ) ? trim( $value ) : null;
						if ( null === $text || ! self::plain( $text, $def['max'] ) ) {
							/* translators: %d: max length */
							$issues[ $key ] = sprintf( __( 'Use plain text on one line, up to %d characters, without < > [ ] or codes like &amp;.', 'woocommerce-jetpack' ), $def['max'] );
						} else {
							$stored[ $key ] = $text;
						}
						break;
					case 'lines':
						$check = self::lines_issue( $value );
						if ( null === $check[0] ) {
							$issues[ $key ] = $check[1];
						} else {
							// Same separator the module's option parser splits on.
							$stored[ $key ] = implode( PHP_EOL, $check[0] );
						}
						break;
				}
			}
			$effective = array();
			foreach ( $current['fields'] as $key => $f ) {
				$effective[ $key ] = array_key_exists( $key, $stored ) ? $stored[ $key ] : $f['value'];
			}
			if ( 'yes' === $effective['enabled'] ) {
				if ( '' === trim( (string) $effective['label'] ) && ! isset( $issues['label'] ) ) {
					$issues['label'] = __( 'Write the question customers will see.', 'woocommerce-jetpack' );
				}
				if ( 'select' === $effective['type'] && ! $current['fields']['options']['locked'] && array() === self::lines( $effective['options'] ) && ! isset( $issues['options'] ) ) {
					$issues['options'] = __( 'Add at least one choice, one per line.', 'woocommerce-jetpack' );
				}
			}
			return array(
				'stored'    => $stored,
				'issues'    => $issues,
				'current'   => $current,
				'effective' => $effective,
			);
		}

		/**
		 * Whether the draft needs the explicit confirmation shown in review.
		 *
		 * @param array $check validate() result.
		 * @return bool
		 */
		private static function needs_confirm( $check ) {
			$s   = $check['stored'];
			$cur = $check['current']['fields'];
			$on  = 'yes' === $check['effective']['enabled'];
			if ( isset( $s['enabled'] ) && 'yes' === $s['enabled'] && 'yes' !== $cur['enabled']['value'] ) {
				return true; // Customers start seeing a question.
			}
			if ( isset( $s['required'] ) && 'yes' === $s['required'] && 'yes' !== $cur['required']['value'] && $on ) {
				return true; // Orders can be blocked until answered.
			}
			if ( isset( $s['section'] ) && $s['section'] !== $cur['section']['value'] && ( 'yes' === $cur['enabled']['value'] || '' !== $cur['label']['value'] ) ) {
				return true; // New answers are stored under a different name.
			}
			return $on && ! $check['current']['env']['module_enabled'];
		}

		// ---------------------------------------------------------------------
		// Preview: Classic is real WooCommerce field markup; Blocks is drawn by the page as an illustration.
		// ---------------------------------------------------------------------

		/**
		 * Render the slot's Classic field through the module and woocommerce_form_field(), with option overlays
		 * that exist only for this call. Cart conditions are ignored here; nothing is saved.
		 *
		 * @param int   $i Slot.
		 * @param array $values Effective values by field key.
		 * @return array { html, note }
		 */
		private static function render_classic( $i, $values ) {
			if ( 'yes' !== $values['enabled'] ) {
				return array(
					'html' => '',
					'note' => __( 'Customers do not see this question.', 'woocommerce-jetpack' ),
				);
			}
			$module = self::module();
			if ( ! $module || ! method_exists( $module, 'add_custom_checkout_fields' ) || ! function_exists( 'woocommerce_form_field' ) ) {
				return array(
					'html' => null,
					'note' => __( 'The field could not be rendered here.', 'woocommerce-jetpack' ),
				);
			}
			$fields  = self::fields( $i );
			$overlay = array( self::P . 'enabled_' . $i => 'yes' );
			foreach ( array( 'type', 'section', 'label', 'placeholder', 'required' ) as $key ) {
				$overlay[ $fields[ $key ]['option'] ] = $values[ $key ];
			}
			// The module's option parser splits on PHP_EOL.
			$overlay[ $fields['options']['option'] ] = implode( PHP_EOL, self::lines( $values['options'] ) );
			// Without a cart, conditions cannot be evaluated: show the question as it appears when it is shown.
			foreach ( array( 'categories_ex_', 'categories_in_', 'products_ex_', 'products_in_' ) as $k ) {
				$overlay[ self::P . $k . $i ] = '';
			}
			$overlay[ self::P . 'min_cart_amount_' . $i ] = 0;
			$overlay[ self::P . 'max_cart_amount_' . $i ] = 0;
			// Only this question is built, so other questions' cart checks never run in admin.
			for ( $j = 1; $j <= self::total(); $j++ ) {
				if ( $j !== $i ) {
					$overlay[ self::P . 'enabled_' . $j ] = 'no';
				}
			}
			$hooks = array();
			// wcj_get_option() applies the option-name filter on every read, cached or not; no cache is touched.
			foreach ( $overlay as $option => $value ) {
				$cb      = static function () use ( $value ) {
					return $value;
				};
				$hooks[] = array( $option, $cb );
				add_filter( $option, $cb, PHP_INT_MAX );
			}
			add_filter( 'wcj_checkout_custom_field_always_visible_on_empty_cart', '__return_false', PHP_INT_MAX );
			try {
				$built   = $module->add_custom_checkout_fields( array() );
				$section = $values['section'];
				$key     = $section . '_wcj_checkout_field_' . $i;
				if ( ! isset( $built[ $section ][ $key ] ) || ! is_array( $built[ $section ][ $key ] ) ) {
					return array(
						'html' => '',
						'note' => __( 'Another plugin or filter hides this question, so it is not shown here.', 'woocommerce-jetpack' ),
					);
				}
				$args           = $built[ $section ][ $key ];
				$args['return'] = true;
				$html           = woocommerce_form_field( $key, $args, null );
			} finally {
				remove_filter( 'wcj_checkout_custom_field_always_visible_on_empty_cart', '__return_false', PHP_INT_MAX );
				foreach ( $hooks as $hook ) {
					remove_filter( $hook[0], $hook[1], PHP_INT_MAX );
				}
			}
			return array(
				'html' => is_string( $html ) ? $html : '',
				'note' => null,
			);
		}

		/**
		 * Preview service: Now and After in one call. Writes nothing.
		 *
		 * @return array
		 */
		public static function preview() {
			self::authorize();
			$i     = self::posted_slot();
			$check = self::validate( $i, self::posted_json( 'changes' ) );
			if ( $check['issues'] ) {
				return array(
					'error'  => __( 'Fix the highlighted fields to see the preview.', 'woocommerce-jetpack' ),
					'issues' => $check['issues'],
				);
			}
			$now = array();
			foreach ( $check['current']['fields'] as $key => $f ) {
				$now[ $key ] = $f['value'];
			}
			$limits = array(
				__( 'Classic: this is the field exactly as WooCommerce writes it, shown with simple styling. Your theme, the rest of your checkout and cart conditions are not shown.', 'woocommerce-jetpack' ),
				__( 'Blocks: an illustration of where the question appears. It is not your checkout page.', 'woocommerce-jetpack' ),
				__( 'No order is created and no email is sent. Emails and order pages are not previewed.', 'woocommerce-jetpack' ),
			);
			return array(
				'now'    => self::render_classic( $i, $now ),
				'after'  => self::render_classic( $i, $check['effective'] ),
				'limits' => $limits,
			);
		}

		// ---------------------------------------------------------------------
		// Apply, receipt, recovery.
		// ---------------------------------------------------------------------

		/**
		 * Apply one consolidated draft for one slot.
		 *
		 * @return array
		 * @throws RuntimeException On unconfirmed or concurrent requests.
		 */
		public static function apply() {
			self::authorize();
			$i       = self::posted_slot();
			$changes = self::posted_json( 'changes' );
			$base    = self::posted_json( 'base' );
			if ( ! $changes ) {
				throw new RuntimeException( esc_html__( 'The draft is empty. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			$lock = WCJ_Job_Store::lock( self::LOCK, __( 'Another checkout question apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
			try {
				$check = self::validate( $i, $changes );
				if ( $check['issues'] ) {
					return array(
						'status' => 'invalid',
						'issues' => $check['issues'],
						'error'  => __( 'Some changes need attention. Nothing was changed.', 'woocommerce-jetpack' ),
					);
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
				if ( self::needs_confirm( $check ) && ( ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] ) ) {
					throw new RuntimeException( esc_html__( 'Confirm the checkout changes in the review before applying. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
				$fields  = self::fields( $i );
				$derived = self::derived( $i );
				$defs    = $fields + $derived;
				$order   = array_keys( $fields );
				if ( isset( $check['stored']['enabled'] ) && 'no' === $check['stored']['enabled'] ) {
					// Turning a question off: hide it first, then adjust the rest.
					$order = array_merge( array( 'enabled' ), array_diff( $order, array( 'enabled' ) ) );
				} else {
					// Checkbox wording is in place before the question can be answered.
					array_splice( $order, array_search( 'enabled', $order, true ), 0, array_keys( $derived ) );
				}
				$on_checkbox = 'yes' === $check['effective']['enabled'] && 'checkbox' === $check['effective']['type'];
				$plan  = array();
				$stale = array();
				if ( 'yes' === $check['effective']['enabled'] && ! $check['current']['env']['module_enabled'] ) {
					$before = WCJ_Job_Store::read_state( self::MODULE );
					if ( ! isset( $base['module'] ) || ! is_string( $base['module'] ) || ! hash_equals( WCJ_Job_Store::state_hash( $before ), $base['module'] ) ) {
						$stale['module'] = 'yes' === WCJ_Job_Store::state_value( $before ) ? 'yes' : 'no';
					} else {
						$plan[ self::MODULE ] = array(
							'field'  => 'module',
							'before' => $before,
							'after'  => WCJ_Job_Store::value_state( 'yes' ),
						);
					}
				}
				foreach ( $order as $key ) {
					$def = $defs[ $key ];
					if ( isset( $derived[ $key ] ) ) {
						// Never from the caller: only an absent row, only for a checkbox question that is on.
						$before = WCJ_Job_Store::read_state( $def['option'] );
						if ( ! $on_checkbox || $before['exists'] ) {
							continue;
						}
						$after = WCJ_Job_Store::value_state( $def['default'] );
					} elseif ( array_key_exists( $key, $check['stored'] ) ) {
						$before = WCJ_Job_Store::read_state( $def['option'] );
						if ( ! isset( $base[ $key ] ) || ! is_string( $base[ $key ] ) || ! hash_equals( WCJ_Job_Store::state_hash( $before ), $base[ $key ] ) ) {
							$stale[ $key ] = $check['current']['fields'][ $key ]['value'];
							continue;
						}
						$after = WCJ_Job_Store::value_state( $check['stored'][ $key ] );
					} elseif ( 'yes' === $check['effective']['enabled'] && in_array( $key, array( 'type', 'section' ), true ) ) {
						// Classic reads type and section without a default; Blocks assumes text/billing.
						// A question that is on gets them written explicitly so both checkouts agree.
						$before = WCJ_Job_Store::read_state( $def['option'] );
						if ( $before['exists'] ) {
							continue;
						}
						$after = WCJ_Job_Store::value_state( $def['absent'] );
					} else {
						continue;
					}
					if ( $before !== $after ) {
						$plan[ $def['option'] ] = array(
							'field'  => $key,
							'before' => $before,
							'after'  => $after,
						);
					}
				}
				if ( $stale ) {
					return array(
						'status' => 'stale',
						'stale'  => $stale,
						'error'  => __( 'Someone changed these settings since you loaded them. Nothing was changed. Compare what is saved now, then apply again.', 'woocommerce-jetpack' ),
						'fresh'  => self::load( $i ),
					);
				}
				if ( ! $plan ) {
					return array(
						'status'  => 'unchanged',
						'message' => __( 'These values are already saved. Nothing was written.', 'woocommerce-jetpack' ),
						'fresh'   => self::load( $i ),
					);
				}
				$applied = array();
				$failed  = null;
				foreach ( $plan as $option => $entry ) {
					if ( ! WCJ_Job_Store::compare_exchange( $option, $entry['before'], $entry['after'] ) ) {
						$failed = $entry['field'];
						break;
					}
					$applied[ $option ] = $entry;
				}
				if ( null !== $failed ) {
					$kept = array();
					foreach ( array_reverse( $applied, true ) as $option => $entry ) {
						if ( ! WCJ_Job_Store::compare_exchange( $option, $entry['after'], $entry['before'] ) ) {
							$kept[] = $entry['field'];
						}
					}
					return array(
						'status' => 'failed',
						'error'  => $kept ? __( 'A write failed. Earlier writes could not all be undone because they changed again; they are listed and remain saved.', 'woocommerce-jetpack' ) : __( 'A write failed. Earlier writes from this apply were put back. Nothing from this draft remains saved.', 'woocommerce-jetpack' ),
						'failed' => $failed,
						'kept'   => $kept,
						'fresh'  => self::load( $i ),
					);
				}
				$entries = array();
				$all_ok  = true;
				foreach ( $applied as $option => $entry ) {
					$ok                 = WCJ_Job_Store::read_state( $option ) === $entry['after'];
					$all_ok             = $all_ok && $ok;
					$entries[ $option ] = array(
						'field'       => $entry['field'],
						'before'      => $entry['before'],
						'after'       => $entry['after'],
						'readback_ok' => $ok,
					);
				}
				$receipt = array(
					'id'      => bin2hex( random_bytes( 8 ) ),
					'time'    => gmdate( 'c' ),
					'user'    => get_current_user_id(),
					'slot'    => $i,
					'status'  => $all_ok ? 'applied' : 'readback_mismatch',
					'entries' => $entries,
				);
				update_option( self::RECEIPT, $receipt, false );
				return array(
					'status'  => $receipt['status'],
					'message' => $all_ok ? __( 'Saved and read back from the database.', 'woocommerce-jetpack' ) : __( 'Saved, but a value read back differently. See what was saved.', 'woocommerce-jetpack' ),
					'fresh'   => self::load( $i ),
				);
			} finally {
				WCJ_Job_Store::unlock( self::LOCK, $lock );
			}
		}

		/**
		 * Receipt slot, if well formed and still within this edition's count.
		 *
		 * @param array $receipt Stored receipt.
		 * @return int 0 when unusable.
		 */
		private static function receipt_slot( $receipt ) {
			$slot = isset( $receipt['slot'] ) ? $receipt['slot'] : 0;
			return is_int( $slot ) && $slot >= 1 && $slot <= self::total() ? $slot : 0;
		}

		/**
		 * Receipt entries for a known option of the receipt's slot (or the module) with an exact state shape.
		 *
		 * @param mixed $entries Stored entries.
		 * @param int   $i Slot.
		 * @return array
		 */
		private static function valid_entries( $entries, $i ) {
			$allowed = array( self::MODULE => 'module' );
			if ( $i ) {
				foreach ( self::fields( $i ) + self::derived( $i ) as $key => $def ) {
					$allowed[ $def['option'] ] = $key;
				}
			}
			$valid = array();
			foreach ( is_array( $entries ) ? $entries : array() as $option => $entry ) {
				$ok = isset( $allowed[ $option ] ) && is_array( $entry ) && isset( $entry['field'] ) && $allowed[ $option ] === $entry['field'];
				foreach ( array( 'before', 'after' ) as $side ) {
					$ok = $ok && isset( $entry[ $side ] ) && is_array( $entry[ $side ] ) && isset( $entry[ $side ]['exists'], $entry[ $side ]['raw'] ) && is_bool( $entry[ $side ]['exists'] ) && is_string( $entry[ $side ]['raw'] );
				}
				if ( $ok ) {
					$valid[ $option ] = $entry;
				}
			}
			return $valid;
		}

		/**
		 * Receipt for display, with current match state.
		 *
		 * @param array $receipt Stored receipt.
		 * @return array
		 */
		private static function receipt_view( $receipt ) {
			$i      = self::receipt_slot( $receipt );
			$fields = $i ? self::fields( $i ) + self::derived( $i ) : array();
			$rows   = array();
			foreach ( self::valid_entries( isset( $receipt['entries'] ) ? $receipt['entries'] : array(), $i ) as $option => $entry ) {
				if ( 'module' === $entry['field'] ) {
					$label  = __( 'Checkout Custom Fields', 'woocommerce-jetpack' );
					$before = 'yes' === WCJ_Job_Store::state_value( $entry['before'] ) ? 'yes' : 'no';
					$after  = 'yes';
				} else {
					$def    = $fields[ $entry['field'] ];
					$label  = $def['label'];
					$before = self::display( $def, $entry['before'] )[0];
					$after  = self::display( $def, $entry['after'] )[0];
				}
				$current = WCJ_Job_Store::read_state( $option );
				$rows[]  = array(
					'option'      => $option,
					'field'       => $entry['field'],
					'label'       => $label,
					'before'      => $before,
					'after'       => $after,
					'readback_ok' => ! empty( $entry['readback_ok'] ),
					'still_ours'  => $current === $entry['after'],
					'restored'    => $current === $entry['before'],
				);
			}
			$stamp = isset( $receipt['time'] ) && is_string( $receipt['time'] ) ? strtotime( $receipt['time'] ) : false;
			return array(
				'slot'   => $i,
				'time'   => $stamp ? ( function_exists( 'wp_date' ) ? wp_date( 'M j, g:i a', $stamp ) : date_i18n( 'M j, g:i a', $stamp ) ) : '',
				'undo'   => (bool) array_filter( wp_list_pluck( $rows, 'still_ours' ) ),
				'status' => isset( $receipt['status'] ) && is_string( $receipt['status'] ) ? $receipt['status'] : '',
				'rows'   => $rows,
			);
		}

		/**
		 * Restore only values still equal to what the last apply wrote. The module switch is decided last:
		 * it goes back off only when no Booster checkout question is still on. Writes target the receipt's
		 * own question; the returned state is always the caller's question, which the page is showing.
		 *
		 * @return array
		 * @throws RuntimeException When nothing is recoverable.
		 */
		public static function recover() {
			self::authorize();
			$caller = self::posted_slot();
			$only   = self::posted_json( 'options' );
			$only = array_values( array_filter( $only, 'is_string' ) );
			$lock = WCJ_Job_Store::lock( self::LOCK, __( 'Another checkout question apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
			try {
				$receipt = get_option( self::RECEIPT, null );
				if ( ! is_array( $receipt ) || empty( $receipt['entries'] ) || ! is_array( $receipt['entries'] ) ) {
					throw new RuntimeException( esc_html__( 'There is no applied change left to undo.', 'woocommerce-jetpack' ) );
				}
				$i         = self::receipt_slot( $receipt );
				$valid     = self::valid_entries( $receipt['entries'], $i );
				$ignored   = count( $receipt['entries'] ) - count( $valid );
				$restored  = array();
				$conflicts = array();
				$remaining = array();
				$ordered   = array_reverse( $valid, true );
				if ( isset( $ordered[ self::MODULE ] ) ) {
					$module_entry = $ordered[ self::MODULE ];
					unset( $ordered[ self::MODULE ] );
					$ordered[ self::MODULE ] = $module_entry;
				}
				foreach ( $ordered as $option => $entry ) {
					if ( $only && ! in_array( $option, $only, true ) ) {
						$remaining[ $option ] = $entry;
						continue;
					}
					$current = WCJ_Job_Store::read_state( $option );
					if ( $current === $entry['before'] ) {
						continue;
					}
					if ( self::MODULE === $option ) {
						$on = false;
						for ( $j = 1; $j <= self::total(); $j++ ) {
							$on = $on || 'yes' === WCJ_Job_Store::state_value( WCJ_Job_Store::read_state( self::P . 'enabled_' . $j ) );
						}
						if ( $on ) {
							$conflicts[]          = 'module';
							$remaining[ $option ] = $entry;
							continue;
						}
					}
					if ( $current === $entry['after'] && WCJ_Job_Store::compare_exchange( $option, $entry['after'], $entry['before'] ) ) {
						$restored[] = $entry['field'];
					} else {
						$conflicts[]          = $entry['field'];
						$remaining[ $option ] = $entry;
					}
				}
				// Unreadable entries are never written and are dropped from the local receipt; the result says so.
				$receipt['entries'] = array_intersect_key( $valid, $remaining );
				$receipt['status']  = $remaining ? 'partially_restored' : 'restored';
				update_option( self::RECEIPT, $receipt, false );
				return array(
					'status'    => $conflicts ? 'partial' : 'restored',
					'restored'  => $restored,
					'conflicts' => $conflicts,
					'ignored'   => $ignored,
					'message'   => $conflicts ? __( 'Undid what still matched. Settings changed after your apply were kept; they are listed. Orders and collected answers were not touched.', 'woocommerce-jetpack' ) : __( 'Undone and read from the database. Orders and collected answers were not touched.', 'woocommerce-jetpack' ),
					'slot'      => $i,
					'fresh'     => self::load( $caller ),
				);
			} finally {
				WCJ_Job_Store::unlock( self::LOCK, $lock );
			}
		}

		/**
		 * AJAX adapter: POST, capability and per-action nonce on every action.
		 */
		public function handle() {
		if ( ! WCJ_Task_Finder::allowed( 'checkout_custom_fields' ) ) { wp_send_json_error( array( 'error' => esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ) ), 403 ); }
			try {
				self::authorize();
				$action = isset( $_POST['action'] ) && is_string( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$op     = str_replace( 'wcj_checkout_job_', '', $action );
				if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! in_array( $op, self::OPS, true ) ) {
					throw new RuntimeException( esc_html__( 'Invalid request.', 'woocommerce-jetpack' ) );
				}
				check_ajax_referer( 'wcj_checkout_job_' . $op, 'nonce' );
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
			if ( ! WCJ_Task_Finder::allowed( 'checkout_custom_fields' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
			}
			$total = self::total();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page selection, re-checked on every request.
			$slot = isset( $_GET['slot'] ) ? absint( $_GET['slot'] ) : 0;
			if ( $slot < 1 || $slot > $total ) {
				$slot  = 0;
				$slots = self::slots();
				foreach ( $slots as $s ) {
					if ( $s['on'] ) {
						$slot = $s['slot'];
						break;
					}
				}
				if ( ! $slot ) {
					foreach ( $slots as $s ) {
						if ( $s['free'] ) {
							$slot = $s['slot'];
							break;
						}
					}
				}
				$slot = $slot ? $slot : 1;
			}
			wp_enqueue_style( 'wcj-invoice-job', wcj_plugin_url() . '/assets/css/admin/wcj-invoice-job.css', array(), '2.5' );
			wp_enqueue_style( 'wcj-checkout-job', wcj_plugin_url() . '/assets/css/admin/wcj-checkout-job.css', array( 'wcj-invoice-job' ), '1.3' );
			wp_enqueue_script( 'wcj-checkout-job', wcj_plugin_url() . '/assets/js/admin/wcj-checkout-job.js', array(), '1.6', true );
			$nonces = array();
			foreach ( self::OPS as $op ) {
				$nonces[ $op ] = wp_create_nonce( 'wcj_checkout_job_' . $op );
			}
			$fields = array();
			// Derived checkbox wording is listed for receipt and undo labels only; the page never drafts it.
			foreach ( self::fields( $slot ) + self::derived( $slot ) as $key => $def ) {
				$fields[ $key ] = array(
					'label'  => $def['label'],
					'option' => $def['option'],
					'shared' => ! empty( $def['shared'] ),
				);
			}
			wp_localize_script(
				'wcj-checkout-job',
				'wcjCheckoutJob',
				array(
					'url'    => admin_url( 'admin-ajax.php' ),
					'nonces' => $nonces,
					'fields' => $fields,
					'slot'   => $slot,
					'user'   => get_current_user_id(),
					'page'   => self::url(),
					'native' => self::native_url(),
				)
			);
			include __DIR__ . '/views/checkout-job.php';
		}
	}

endif;

new WCJ_Checkout_Job();

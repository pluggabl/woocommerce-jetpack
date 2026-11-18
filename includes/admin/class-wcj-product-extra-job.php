<?php
/**
 * Optional extra job: one global checkbox Product Addon (label, price, hint, required, exclusions)
 * and its activation in one draft, native product-page markup preview, conditional apply,
 * readback receipt and matching-only recovery. Writes only allowlisted native Product Addons options.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wcj-job-store.php';

if ( ! class_exists( 'WCJ_Product_Extra_Job' ) ) :

	/**
	 * Product extra adapter. Rendering, add-to-cart, price, cart, order and quantity logic stay in the module.
	 */
	class WCJ_Product_Extra_Job {

		// Site-local job state. Not wcj_-prefixed: never exported, and a backup containing it is refused by import.
		const RECEIPT = 'booster_product_extra_job_receipt';
		const LOCK    = 'booster_product_extra_job_lock';

		const MODULE = 'wcj_product_addons_enabled';
		const MASTER = 'wcj_product_addons_all_products_enabled';
		const TOTAL  = 'wcj_product_addons_all_products_total_number';
		const PER    = 'wcj_product_addons_per_product_enabled';
		const P      = 'wcj_product_addons_all_products_';

		/** Upper bound for slots this page will add; native has none. */
		const MAX_SLOTS = 100;

		const OPS = array( 'load', 'preview', 'apply', 'recover' );

		/**
		 * Construct.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
			foreach ( self::OPS as $op ) {
				add_action( 'wp_ajax_wcj_product_extra_job_' . $op, array( $this, 'handle' ) );
			}
		}

		/**
		 * Page URL.
		 *
		 * @param int $slot Extra slot, 0 for the default.
		 * @return string
		 */
		public static function url( $slot = 0 ) {
			return admin_url( 'admin.php?page=wcj-product-extra-job' . ( $slot ? '&slot=' . absint( $slot ) : '' ) );
		}

		/**
		 * Native Product Addons settings.
		 *
		 * @return string
		 */
		public static function native_url() {
			return admin_url( wcj_admin_tab_url() . '&wcj-cat=products&section=product_addons' );
		}

		/**
		 * Capability check used by every entry point.
		 *
		 * @throws RuntimeException When the user lacks the native capability.
		 */
		public static function authorize() {
			if ( ! WCJ_Task_Finder::allowed( 'product_addons' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by WooCommerce.
				throw new RuntimeException( esc_html__( 'You need permission to manage WooCommerce.', 'woocommerce-jetpack' ) );
			}
		}

		/**
		 * Register submenu.
		 */
		public function register_page() {
		if ( ! WCJ_Task_Finder::allowed( 'product_addons' ) ) { return; }
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability.
			add_submenu_page( 'woocommerce', __( 'Optional extra', 'woocommerce-jetpack' ), __( 'Optional extra', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-product-extra-job', array( $this, 'render' ) );
		}

		/**
		 * Product Addons module instance, if loaded.
		 *
		 * @return object|null
		 */
		private static function module() {
			$core = WCJ_Job_Store::core();
			return $core && isset( $core->all_modules['product_addons'] ) ? $core->all_modules['product_addons'] : null;
		}

		// ---------------------------------------------------------------------
		// Native state.
		// ---------------------------------------------------------------------

		/**
		 * Saved value of an option, or $absent when the row does not exist.
		 *
		 * @param string $option Option.
		 * @param mixed  $absent Value the module assumes for a missing row.
		 * @return mixed
		 */
		private static function saved( $option, $absent ) {
			$state = WCJ_Job_Store::read_state( $option );
			return $state['exists'] ? WCJ_Job_Store::state_value( $state ) : $absent;
		}

		/**
		 * Is a yes/no option on.
		 *
		 * @param string $option Option.
		 * @param string $absent Native default.
		 * @return bool
		 */
		private static function on( $option, $absent = 'no' ) {
			return 'yes' === self::saved( $option, $absent );
		}

		/**
		 * Effective number of global extras, from the module's own entitlement expression.
		 * Free has no booster_option handler, so it is 1 whatever is stored.
		 *
		 * @return int
		 */
		public static function total() {
			$stored = self::saved( self::TOTAL, 1 );
			return max( 0, (int) apply_filters( 'booster_option', 1, is_numeric( $stored ) ? (int) $stored : 1 ) );
		}

		/**
		 * Whether this edition lets one more global extra be added.
		 *
		 * @return bool
		 */
		public static function can_add() {
			$next = self::total() + 1;
			return $next <= self::MAX_SLOTS && $next === (int) apply_filters( 'booster_option', 1, $next );
		}

		/**
		 * Slot from the request: an existing slot, or the next one where the edition allows it.
		 *
		 * @return int
		 * @throws RuntimeException When out of range.
		 */
		private static function posted_slot() {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
			$raw = isset( $_POST['slot'] ) && is_string( $_POST['slot'] ) ? sanitize_text_field( wp_unslash( $_POST['slot'] ) ) : '';
			$max = self::total() + ( self::can_add() ? 1 : 0 );
			if ( ! preg_match( '/^[1-9][0-9]{0,2}$/D', $raw ) || (int) $raw > $max ) {
				throw new RuntimeException( esc_html__( 'That extra is not available in your Booster edition. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return (int) $raw;
		}

		/**
		 * Editable fields of one slot, in safe write order. 'absent' is what the module does without a saved row.
		 *
		 * @param int $i Slot.
		 * @return array
		 */
		public static function fields( $i ) {
			$p = self::P;
			return array(
				'label'    => array( 'option' => $p . 'label_' . $i, 'kind' => 'text', 'absent' => '', 'label' => __( 'Name', 'woocommerce-jetpack' ) ),
				'title'    => array( 'option' => $p . 'title_' . $i, 'kind' => 'text', 'absent' => '', 'label' => __( 'Heading', 'woocommerce-jetpack' ) ),
				'price'    => array( 'option' => $p . 'price_' . $i, 'kind' => 'price', 'absent' => '', 'label' => __( 'Price', 'woocommerce-jetpack' ) ),
				'tooltip'  => array( 'option' => $p . 'tooltip_' . $i, 'kind' => 'hint', 'absent' => '', 'label' => __( 'Hint', 'woocommerce-jetpack' ) ),
				'required' => array( 'option' => $p . 'required_' . $i, 'kind' => 'yesno', 'absent' => 'no', 'label' => __( 'Customers must choose it', 'woocommerce-jetpack' ) ),
				'exclude'  => array( 'option' => $p . 'exclude_products_' . $i, 'kind' => 'ids', 'absent' => array(), 'label' => __( 'Not on', 'woocommerce-jetpack' ) ),
				'enabled'  => array( 'option' => $p . 'enabled_' . $i, 'kind' => 'yesno', 'absent' => 'yes', 'label' => __( 'Extra switched on', 'woocommerce-jetpack' ) ),
			);
		}

		/**
		 * Native slot settings this page never writes, listed so the owner knows they stay.
		 *
		 * @param int $i Slot.
		 * @return array
		 */
		private static function kept( $i ) {
			$p = self::P;
			return array(
				'type'        => array( 'option' => $p . 'type_' . $i, 'absent' => 'checkbox', 'label' => __( 'Type', 'woocommerce-jetpack' ) ),
				'default'     => array( 'option' => $p . 'default_' . $i, 'absent' => '', 'label' => __( 'Default value', 'woocommerce-jetpack' ) ),
				'placeholder' => array( 'option' => $p . 'placeholder_' . $i, 'absent' => '', 'label' => __( 'Placeholder', 'woocommerce-jetpack' ) ),
				'class'       => array( 'option' => $p . 'class_' . $i, 'absent' => '', 'label' => __( 'HTML class', 'woocommerce-jetpack' ) ),
				'qty'         => array( 'option' => $p . 'qty_' . $i, 'absent' => '', 'label' => __( 'Quantity', 'woocommerce-jetpack' ) ),
			);
		}

		/**
		 * Plain one-line text this page can edit and the native renderer prints without surprises.
		 *
		 * @param mixed $value Value.
		 * @param int   $max Byte limit.
		 * @param bool  $attr Also refuse double quotes (native prints the hint inside title="").
		 * @return bool
		 */
		private static function is_plain( $value, $max, $attr = false ) {
			if ( ! is_string( $value ) || strlen( $value ) > $max || preg_match( '/[\r\n<>\[\]]/', $value ) ) {
				return false;
			}
			return ! ( $attr && false !== strpos( $value, '"' ) );
		}

		/**
		 * One price this page supports: an amount of 0 or more, or a whole percent (native replaces \d+%).
		 *
		 * @param mixed $value Value.
		 * @return bool
		 */
		private static function is_price( $value ) {
			return is_string( $value ) && 1 === preg_match( '/^(?:[0-9]{1,7}(?:\.[0-9]{1,4})?|(?:100|[1-9]?[0-9])%)$/D', $value );
		}

		/**
		 * Scalar option to a string; anything else stays as is so it can be refused.
		 *
		 * @param mixed $value Value.
		 * @return mixed
		 */
		private static function str( $value ) {
			return is_int( $value ) || is_float( $value ) ? (string) $value : $value;
		}

		/**
		 * Read and classify one slot. A slot is locked when it uses anything this page cannot edit
		 * faithfully; it is then shown read-only and left for the native settings.
		 *
		 * @param int $i Slot.
		 * @return array
		 */
		private static function classify( $i ) {
			$values  = array();
			$reasons = array();
			foreach ( self::fields( $i ) as $key => $def ) {
				$values[ $key ] = self::str( self::saved( $def['option'], $def['absent'] ) );
			}
			$kept = array();
			foreach ( self::kept( $i ) as $key => $def ) {
				$kept[ $key ] = self::str( self::saved( $def['option'], $def['absent'] ) );
			}
			if ( ! in_array( $kept['type'], array( 'checkbox', '' ), true ) ) {
				$reasons[] = __( 'It is not a checkbox (radio, select or text).', 'woocommerce-jetpack' );
			}
			if ( '' !== $kept['default'] ) {
				$reasons[] = __( 'It has a default value, so it may start ticked.', 'woocommerce-jetpack' );
			}
			if ( '' !== $kept['qty'] ) {
				$reasons[] = __( 'It has a quantity limit.', 'woocommerce-jetpack' );
			}
			if ( ! self::is_plain( $values['label'], 200 ) || ! self::is_plain( $values['title'], 200 ) ) {
				$reasons[] = __( 'Its name or heading has several lines, HTML or shortcodes.', 'woocommerce-jetpack' );
			}
			if ( ! self::is_plain( $values['tooltip'], 300, true ) ) {
				$reasons[] = __( 'Its hint has several lines, HTML, shortcodes or quotes.', 'woocommerce-jetpack' );
			}
			if ( '' !== $values['price'] && ! self::is_price( $values['price'] ) ) {
				$reasons[] = __( 'Its price uses several lines, a negative amount or a decimal percent.', 'woocommerce-jetpack' );
			}
			if ( '' === $values['exclude'] ) {
				$values['exclude'] = array();
			}
			if ( ! is_array( $values['exclude'] ) || array_filter( $values['exclude'], static function ( $id ) { return ! preg_match( '/^[1-9][0-9]*$/D', (string) $id ); } ) ) {
				$reasons[] = __( 'Its product exclusions are in an unexpected format.', 'woocommerce-jetpack' );
			} else {
				$values['exclude'] = array_values( array_map( 'strval', $values['exclude'] ) );
			}
			foreach ( array( 'enabled', 'required' ) as $key ) {
				if ( ! in_array( $values[ $key ], array( 'yes', 'no' ), true ) ) {
					$reasons[] = __( 'An on/off setting has an unexpected value.', 'woocommerce-jetpack' );
				}
			}
			$free = '' === $values['label'] && '' === $values['title'] && in_array( $values['price'], array( '', '0' ), true ) && ! $reasons;
			return array(
				'values'  => $values,
				'kept'    => $kept,
				'reasons' => $reasons,
				'free'    => $free,
			);
		}

		/**
		 * Site switches and counts that decide what customers see.
		 *
		 * @return array
		 */
		private static function env() {
			$counts = wp_count_posts( 'product' );
			return array(
				'module'   => self::on( self::MODULE ),
				'master'   => self::on( self::MASTER ),
				'per'      => self::on( self::PER ),
				'total'    => self::total(),
				'can_add'  => self::can_add(),
				'products' => isset( $counts->publish ) ? (int) $counts->publish : 0,
				'loaded'   => (bool) self::module(),
			);
		}

		/**
		 * Products with their own per-product extras switched on. Only counted when that native path is on.
		 *
		 * @return int
		 */
		private static function per_product_count() {
			if ( ! self::on( self::PER ) ) {
				return 0;
			}
			$query = new WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => 1,
					'meta_key'       => '_wcj_product_addons_per_product_settings_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			return (int) $query->found_posts;
		}

		/**
		 * Short name of a slot for lists.
		 *
		 * @param int   $j Slot.
		 * @param array $c Classification.
		 * @return string
		 */
		private static function slot_name( $j, $c ) {
			$name = is_string( $c['values']['label'] ) && '' !== $c['values']['label'] ? $c['values']['label'] : ( is_string( $c['values']['title'] ) ? $c['values']['title'] : '' );
			$name = wp_strip_all_tags( (string) $name );
			/* translators: %d: extra number. */
			return '' !== $name ? wp_html_excerpt( $name, 60, '…' ) : sprintf( __( 'Extra #%d', 'woocommerce-jetpack' ), $j );
		}

		/**
		 * All global slots with their customer-facing state.
		 *
		 * @return array
		 */
		private static function slots() {
			$env  = self::env();
			$list = array();
			for ( $j = 1; $j <= $env['total']; $j++ ) {
				$c = self::classify( $j );
				if ( $c['free'] ) {
					$state = 'free';
				} elseif ( 'yes' !== $c['values']['enabled'] ) {
					$state = 'off';
				} elseif ( $env['module'] && $env['master'] ) {
					$state = 'live';
				} else {
					$state = 'dormant';
				}
				$list[] = array(
					'slot'   => $j,
					'name'   => self::slot_name( $j, $c ),
					'state'  => $state,
					'locked' => (bool) $c['reasons'],
				);
			}
			return $list;
		}

		/**
		 * Other saved extras that are switched on but hidden only because the module or the
		 * all-products switch is off. Turning those switches on would show them too.
		 *
		 * @param int $i Slot being edited.
		 * @return array
		 */
		private static function dormant( $i ) {
			$out = array();
			foreach ( self::slots() as $s ) {
				if ( $s['slot'] !== $i && 'dormant' === $s['state'] ) {
					$out[ $s['slot'] ] = $s;
				}
			}
			return $out;
		}

		/**
		 * Recently edited products to preview with.
		 *
		 * @return array
		 */
		private static function samples() {
			$out = array();
			foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => 30, 'orderby' => 'modified', 'order' => 'DESC' ) ) as $product ) {
				$out[] = array(
					'id'    => $product->get_id(),
					'name'  => wp_strip_all_tags( $product->get_name() ),
					'type'  => $product->get_type(),
					'price' => html_entity_decode( wp_strip_all_tags( wc_price( wc_get_price_to_display( $product ) ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				);
			}
			return $out;
		}

		/**
		 * Names for product IDs.
		 *
		 * @param array $ids IDs.
		 * @return array
		 */
		private static function names( $ids ) {
			$out = array();
			foreach ( (array) $ids as $id ) {
				$title      = get_the_title( (int) $id );
				$out[ $id ] = ( '' !== $title ? wp_strip_all_tags( $title ) : '#' . $id ) . ( self::is_published_product( $id ) ? '' : ' ' . __( '(not a published product)', 'woocommerce-jetpack' ) );
			}
			return $out;
		}

		/**
		 * Plain display of a value.
		 *
		 * @param string $key Field.
		 * @param mixed  $value Value.
		 * @return string
		 */
		private static function display( $key, $value ) {
			if ( in_array( $key, array( 'enabled', 'required', 'module', 'master' ), true ) || 0 === strpos( $key, 'pause_' ) ) {
				return 'yes' === $value ? __( 'On', 'woocommerce-jetpack' ) : __( 'Off', 'woocommerce-jetpack' );
			}
			if ( 'price' === $key ) {
				if ( ! is_string( $value ) || '' === $value ) {
					return '—';
				}
				if ( '%' === substr( $value, -1 ) ) {
					/* translators: %s: percent. */
					return sprintf( __( '%s of the product price', 'woocommerce-jetpack' ), $value );
				}
				return self::amount( $value );
			}
			if ( 'exclude' === $key ) {
				$ids = is_array( $value ) ? $value : array();
				if ( ! $ids ) {
					return __( 'No exclusions', 'woocommerce-jetpack' );
				}
				$names = array_values( self::names( array_slice( $ids, 0, 3 ) ) );
				$more  = count( $ids ) > 3 ? ' ' . sprintf( /* translators: %d: count. */ __( '+%d more', 'woocommerce-jetpack' ), count( $ids ) - 3 ) : '';
				return implode( ', ', $names ) . $more;
			}
			if ( 'total' === $key ) {
				return (string) $value;
			}
			return is_string( $value ) && '' !== $value ? $value : '—';
		}

		/**
		 * A fixed amount in store currency, without tax adjustments.
		 *
		 * @param string $value Amount.
		 * @return string
		 */
		private static function amount( $value ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( (float) $value ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ) . ' ' . __( 'per item', 'woocommerce-jetpack' );
		}

		/**
		 * Every option whose value the owner reviewed: the switches, the edited slot (including settings
		 * this page never writes) and each hidden extra that a decision could show or keep hidden.
		 * Apply refuses when any of them changed since load, not only the ones it writes.
		 *
		 * @param int   $i Slot.
		 * @param array $others Dormant slots.
		 * @return array
		 */
		private static function watch( $i, $others ) {
			$list = array( self::MODULE, self::MASTER, self::TOTAL, self::PER );
			foreach ( array_merge( array( $i ), $others ) as $j ) {
				foreach ( self::fields( $j ) + self::kept( $j ) as $def ) {
					$list[] = $def['option'];
				}
			}
			return array_values( array_unique( $list ) );
		}

		/**
		 * Owner-facing name of a watched option.
		 *
		 * @param string $option Option.
		 * @param int    $i Slot being edited.
		 * @return string
		 */
		private static function option_label( $option, $i ) {
			$named = array(
				self::MODULE => 'module',
				self::MASTER => 'master',
				self::TOTAL  => 'total',
			);
			if ( isset( $named[ $option ] ) ) {
				return self::field_label( $named[ $option ], $i );
			}
			if ( self::PER === $option ) {
				return __( 'Per-product extras', 'woocommerce-jetpack' );
			}
			foreach ( self::fields( $i ) + self::kept( $i ) as $def ) {
				if ( $def['option'] === $option ) {
					return $def['label'];
				}
			}
			/* translators: %d: extra number. */
			return preg_match( '/_([0-9]+)$/', $option, $m ) ? sprintf( __( 'Other extra #%d', 'woocommerce-jetpack' ), (int) $m[1] ) : $option;
		}

		/**
		 * Whether an ID is a published product, which is where an exclusion changes what customers see.
		 *
		 * @param mixed $id ID.
		 * @return bool
		 */
		private static function is_published_product( $id ) {
			return 'product' === get_post_type( (int) $id ) && 'publish' === get_post_status( (int) $id );
		}

		/**
		 * IDs of products (any status) with their own per-product extras switched on.
		 *
		 * @return array
		 */
		private static function per_product_ids() {
			$query = new WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					'cache_results'  => false, // Safety fingerprint: read current product IDs, not a cached query.
					'meta_key'       => '_wcj_product_addons_per_product_settings_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			return array_map( 'intval', $query->posts );
		}

		/**
		 * Fingerprints of what Undo depends on: the slot's own rows (all of them, also the ones never
		 * written here) and everything else that the switches and the extras count keep visible:
		 * other global slots, the count, and the per-product path.
		 *
		 * @param int $i Slot.
		 * @return array slot and others hashes.
		 */
		private static function fingerprints( $i ) {
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name", $wpdb->esc_like( self::P ) . '%' ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$own  = array();
			foreach ( self::fields( $i ) + self::kept( $i ) as $def ) {
				$own[ $def['option'] ] = true;
			}
			$slot   = array();
			$others = array();
			foreach ( (array) $rows as $row ) {
				if ( isset( $own[ $row['option_name'] ] ) ) {
					$slot[ $row['option_name'] ] = $row['option_value'];
				} elseif ( self::MASTER !== $row['option_name'] ) {
					$others[ $row['option_name'] ] = $row['option_value'];
				}
			}
			$per                = WCJ_Job_Store::read_state( self::PER );
			$others['~per']     = ( $per['exists'] ? '1' : '0' ) . $per['raw'];
			$others['~per_ids'] = implode( ',', self::per_product_ids() );
			return array(
				'slot'   => hash( 'sha256', serialize( $slot ) ),
				'others' => hash( 'sha256', serialize( $others ) ),
			);
		}

		/**
		 * Load state for one slot.
		 *
		 * @param int|null $i Slot.
		 * @return array
		 */
		public static function load( $i = null ) {
			self::authorize();
			$i      = null === $i ? self::posted_slot() : (int) $i;
			$env    = self::env();
			$c      = self::classify( $i );
			$base    = array();
			$dormant = self::dormant( $i );
			foreach ( self::watch( $i, array_keys( $dormant ) ) as $option ) {
				$base[ $option ] = WCJ_Job_Store::state_hash( WCJ_Job_Store::read_state( $option ) );
			}
			$kept = array();
			foreach ( self::kept( $i ) as $key => $def ) {
				if ( '' !== $c['kept'][ $key ] && 'type' !== $key ) {
					$kept[] = array(
						'label' => $def['label'],
						'value' => is_string( $c['kept'][ $key ] ) ? wp_html_excerpt( $c['kept'][ $key ], 80, '…' ) : __( '(not text)', 'woocommerce-jetpack' ),
					);
				}
			}
			$receipt = get_option( self::RECEIPT, null );
			return array(
				'slot'     => $i,
				'append'   => $i > $env['total'],
				'live'     => $i <= $env['total'] && $env['module'] && $env['master'] && 'yes' === $c['values']['enabled'],
				'values'   => $c['values'],
				'locked'   => $c['reasons'],
				'kept'     => $kept,
				'base'     => $base,
				'env'      => $env,
				'slots'    => self::slots(),
				'dormant'  => array_values( $dormant ),
				'samples'  => self::samples(),
				'excluded' => is_array( $c['values']['exclude'] ) ? self::names( $c['values']['exclude'] ) : array(),
				'receipt'  => is_array( $receipt ) && ! empty( $receipt['entries'] ) ? self::receipt_view( $receipt ) : null,
			);
		}

		// ---------------------------------------------------------------------
		// Validation, plan and preview.
		// ---------------------------------------------------------------------

		/**
		 * Decode a posted JSON field.
		 *
		 * @param string $field Field.
		 * @return array
		 */
		private static function posted_json( $field ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in handle(); decoded and validated field by field.
			$raw = isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
			if ( strlen( $raw ) > 65536 ) {
				return array();
			}
			$data = json_decode( $raw, true );
			return is_array( $data ) ? $data : array();
		}

		/**
		 * Validate a draft against fresh saved values. Never trusts the caller's view of the current state.
		 *
		 * @param int   $i Slot.
		 * @param array $changes Draft.
		 * @return array
		 */
		private static function validate( $i, $changes ) {
			$env       = self::env();
			$c         = self::classify( $i );
			$current   = $c['values'];
			$issues    = array();
			$stored    = array();
			$append    = $i > $env['total'];
			$effective = $current;
			if ( $c['reasons'] ) {
				$issues['general'] = __( 'This extra uses settings this page cannot edit safely. Change it in the Product Addons settings.', 'woocommerce-jetpack' );
			}
			// The draft says whether customers should see the extra; the slot switch follows it.
			// Without that choice nothing is switched on, so editing a hidden extra never publishes it.
			$live_now = ! $append && $env['module'] && $env['master'] && 'yes' === $current['enabled'];
			$want     = $live_now;
			if ( array_key_exists( 'live', $changes ) ) {
				if ( in_array( $changes['live'], array( 'yes', 'no' ), true ) ) {
					$want = 'yes' === $changes['live'];
				} else {
					$issues['live'] = __( 'Choose whether customers see the extra.', 'woocommerce-jetpack' );
				}
			}
			unset( $changes['enabled'] );
			if ( array_key_exists( 'live', $changes ) || $append ) {
				$changes['enabled'] = $want ? 'yes' : 'no';
			}
			foreach ( self::fields( $i ) as $key => $def ) {
				if ( ! array_key_exists( $key, $changes ) ) {
					continue;
				}
				$value = $changes[ $key ];
				switch ( $def['kind'] ) {
					case 'yesno':
						if ( ! in_array( $value, array( 'yes', 'no' ), true ) ) {
							$issues[ $key ] = __( 'Choose on or off.', 'woocommerce-jetpack' );
							continue 2;
						}
						break;
					case 'text':
					case 'hint':
						// The typed text is checked as typed; only surrounding and repeated spaces are normalised.
						$hint = 'hint' === $def['kind'];
						if ( ! is_string( $value ) || ! self::is_plain( $value, $hint ? 300 : 200, $hint ) || wp_check_invalid_utf8( $value ) !== $value ) {
							$issues[ $key ] = $hint ? __( 'Use one line of plain text, without quotes, < > [ or ].', 'woocommerce-jetpack' ) : __( 'Use one line of plain text, without < > [ or ].', 'woocommerce-jetpack' );
							continue 2;
						}
						$value = trim( preg_replace( '/\s+/u', ' ', $value ) );
						break;
					case 'price':
						$value = is_string( $value ) ? trim( $value ) : ( is_int( $value ) || is_float( $value ) ? (string) $value : null );
						if ( null === $value || ( '' !== $value && ! self::is_price( $value ) ) ) {
							$issues[ $key ] = __( 'Enter an amount like 5 or 4.50, or a whole percent like 10%.', 'woocommerce-jetpack' );
							continue 2;
						}
						break;
					case 'ids':
						$ids = array();
						$bad = ! is_array( $value ) || count( $value ) > 500;
						foreach ( is_array( $value ) ? $value : array() as $id ) {
							$id = is_int( $id ) ? (string) $id : $id;
							if ( ! is_string( $id ) || ! preg_match( '/^[1-9][0-9]{0,19}$/D', $id ) ) {
								$bad = true;
								break;
							}
							// An exclusion already saved may point to a deleted product; it is kept. New ones must be real products.
							$known = is_array( $current['exclude'] ) && in_array( $id, $current['exclude'], true );
							if ( ! $known && ( 'product' !== get_post_type( (int) $id ) ) ) {
								$bad = true;
								break;
							}
							$ids[ $id ] = $id;
						}
						if ( $bad ) {
							$issues[ $key ] = __( 'Choose products from the list.', 'woocommerce-jetpack' );
							continue 2;
						}
						$value = array_values( $ids );
						break;
				}
				$effective[ $key ] = $value;
				if ( $value !== $current[ $key ] ) {
					$stored[ $key ] = $value;
				}
			}
			$on = $want;
			if ( ( $on || $append ) && '' === $effective['label'] && ! isset( $issues['label'] ) ) {
				$issues['label'] = __( 'Give the extra a name customers will see.', 'woocommerce-jetpack' );
			}
			if ( $on && '' === $effective['price'] && ! isset( $issues['price'] ) ) {
				$issues['price'] = __( 'Enter a price. Use 0 for a free extra.', 'woocommerce-jetpack' );
			}
			if ( $append ) {
				// A new slot gets every editable field written, so nothing depends on native defaults.
				foreach ( self::fields( $i ) as $key => $def ) {
					if ( ! WCJ_Job_Store::read_state( $def['option'] )['exists'] ) {
						$stored[ $key ] = $effective[ $key ];
					}
				}
			}
			$act_master = $on && ! $env['master'];
			$act_module = $on && ! $env['module'];
			$decisions  = array();
			$dormant    = ( $act_master || $act_module ) ? self::dormant( $i ) : array();
			$others     = isset( $changes['others'] ) && is_array( $changes['others'] ) ? $changes['others'] : array();
			foreach ( $dormant as $j => $s ) {
				$choice = isset( $others[ $j ] ) ? $others[ $j ] : ( isset( $others[ (string) $j ] ) ? $others[ (string) $j ] : '' );
				if ( ! in_array( $choice, array( 'pause', 'show' ), true ) ) {
					$issues['others'] = __( 'Decide what happens to the other saved extras listed below.', 'woocommerce-jetpack' );
					continue;
				}
				$decisions[ $j ] = $choice;
			}
			return array(
				'issues'     => $issues,
				'stored'     => $stored,
				'effective'  => $effective,
				'current'    => $current,
				'env'        => $env,
				'append'     => $append,
				'act_master' => $act_master,
				'act_module' => $act_module,
				'live_now'   => $live_now,
				'live_after' => $want,
				'dormant'    => $dormant,
				'decisions'  => $decisions,
			);
		}

		/**
		 * Whether the draft has consequences beyond wording.
		 *
		 * @param array $check Validation.
		 * @return bool
		 */
		private static function needs_confirm( $check ) {
			return $check['append'] || $check['act_master'] || $check['act_module'] || $check['decisions']
				|| array_intersect( array_keys( $check['stored'] ), array( 'enabled', 'price', 'required', 'exclude' ) );
		}

		/**
		 * Ordered writes for a validated draft. Content first, hidden slots paused before any switch
		 * that would show them, visibility last; turning the extra off hides it first.
		 *
		 * @param int   $i Slot.
		 * @param array $check Validation.
		 * @return array List of [ option, field, value ].
		 */
		private static function planned( $i, $check ) {
			$fields = self::fields( $i );
			$writes = array();
			$stored = $check['stored'];
			if ( isset( $stored['enabled'] ) && 'no' === $stored['enabled'] ) {
				$writes[] = array( $fields['enabled']['option'], 'enabled', 'no' );
				unset( $stored['enabled'] );
			}
			$type = self::P . 'type_' . $i;
			if ( $check['append'] && ! WCJ_Job_Store::read_state( $type )['exists'] ) {
				$writes[] = array( $type, 'type', 'checkbox' );
			}
			foreach ( array( 'label', 'title', 'price', 'tooltip', 'required', 'exclude' ) as $key ) {
				if ( array_key_exists( $key, $stored ) ) {
					$writes[] = array( $fields[ $key ]['option'], $key, $stored[ $key ] );
				}
			}
			if ( $check['append'] ) {
				$writes[] = array( self::TOTAL, 'total', $check['env']['total'] + 1 );
			}
			foreach ( $check['decisions'] as $j => $choice ) {
				if ( 'pause' === $choice ) {
					$writes[] = array( self::P . 'enabled_' . $j, 'pause_' . $j, 'no' );
				}
			}
			if ( array_key_exists( 'enabled', $stored ) ) {
				$writes[] = array( $fields['enabled']['option'], 'enabled', $stored['enabled'] );
			}
			if ( $check['act_master'] ) {
				$writes[] = array( self::MASTER, 'master', 'yes' );
			}
			if ( $check['act_module'] ) {
				$writes[] = array( self::MODULE, 'module', 'yes' );
			}
			return $writes;
		}

		/**
		 * Label for a planned or receipt field.
		 *
		 * @param string $field Field.
		 * @param int    $i Slot.
		 * @return string
		 */
		private static function field_label( $field, $i ) {
			$fields = self::fields( $i );
			if ( isset( $fields[ $field ] ) ) {
				return $fields[ $field ]['label'];
			}
			if ( 0 === strpos( $field, 'pause_' ) ) {
				$j = (int) substr( $field, 6 );
				/* translators: 1: extra number, 2: extra name. */
				return sprintf( __( 'Other extra #%1$d “%2$s”', 'woocommerce-jetpack' ), $j, self::slot_name( $j, self::classify( $j ) ) );
			}
			$labels = array(
				'type'   => __( 'Type', 'woocommerce-jetpack' ),
				'total'  => __( 'Number of extras', 'woocommerce-jetpack' ),
				'master' => __( 'Extras on all products', 'woocommerce-jetpack' ),
				'module' => __( 'Product Addons', 'woocommerce-jetpack' ),
			);
			return isset( $labels[ $field ] ) ? $labels[ $field ] : $field;
		}

		/**
		 * Exact review rows and consequences for a validated draft.
		 *
		 * @param int   $i Slot.
		 * @param array $check Validation.
		 * @return array
		 */
		private static function review_rows( $i, $check ) {
			$rows = array();
			foreach ( self::planned( $i, $check ) as $w ) {
				list( $option, $field, $value ) = $w;
				$state  = WCJ_Job_Store::read_state( $option );
				$before = $state['exists'] ? self::str( WCJ_Job_Store::state_value( $state ) ) : null;
				if ( 'exclude' === $field && '' === $before ) {
					$before = array();
				}
				if ( null === $before && 'total' === $field ) {
					$before = '1';
				}
				if ( null === $before ) {
					$before = 'enabled' === $field || 0 === strpos( $field, 'pause_' ) ? 'yes' : ( in_array( $field, array( 'module', 'master', 'required' ), true ) ? 'no' : ( 'exclude' === $field ? array() : '' ) );
				}
				$rows[] = array(
					'option' => $option,
					'field'  => $field,
					'label'  => self::field_label( $field, $i ),
					'before' => self::display( $field, $before ),
					'after'  => self::display( $field, $value ),
					'weight' => in_array( $field, array( 'label', 'title', 'tooltip', 'type' ), true ) ? 'plain' : 'consequential',
				);
			}
			$notes = array();
			$env   = $check['env'];
			if ( $check['live_after'] ) {
				// Only exclusions that point to a published product hide the extra from anyone; stale IDs stay saved.
				$reach   = max( 0, $env['products'] - count( array_filter( (array) $check['effective']['exclude'], static function ( $id ) { return self::is_published_product( $id ); } ) ) );
				/* translators: 1: products reached, 2: published products. */
				$notes[] = sprintf( __( 'Shows on %1$d of %2$d published products.', 'woocommerce-jetpack' ), $reach, $env['products'] );
			}
			foreach ( $check['decisions'] as $j => $choice ) {
				if ( 'show' === $choice ) {
					/* translators: %s: extra name. */
					$notes[] = sprintf( __( '“%s” also starts showing to customers.', 'woocommerce-jetpack' ), $check['dormant'][ $j ]['name'] );
				}
			}
			if ( $check['act_module'] ) {
				$per = self::per_product_count();
				if ( $per ) {
					/* translators: %d: product count. */
					$notes[] = sprintf( _n( 'Turning Product Addons on also shows the extras saved on %d product’s own page settings.', 'Turning Product Addons on also shows the extras saved on %d products’ own page settings.', $per, 'woocommerce-jetpack' ), $per );
				}
			}
			return array(
				'rows'    => $rows,
				'notes'   => $notes,
				'confirm' => (bool) self::needs_confirm( $check ),
			);
		}

		/**
		 * Request-only option values for the "After" render.
		 *
		 * @param int   $i Slot.
		 * @param array $check Validation.
		 * @return array option => value
		 */
		private static function overlay( $i, $check ) {
			$map = array();
			foreach ( self::planned( $i, $check ) as $w ) {
				$map[ $w[0] ] = $w[2];
			}
			// The module's switch is not read by its renderer; whether anything shows is decided by the caller.
			unset( $map[ self::MODULE ] );
			return $map;
		}

		/**
		 * The module's own product-page markup for one product, with request-only option filters.
		 * Returns null when the native renderer cannot be used faithfully in this request.
		 *
		 * @param WC_Product $product Product.
		 * @param array      $overlay option => value.
		 * @param string     $price Extra price to format natively, or ''.
		 * @return array|null html and price.
		 */
		private static function render_native( $product, $overlay, $price ) {
			$module = self::module();
			if ( ! $module || ! is_callable( array( $module, 'add_addons_to_frontend' ) ) || ! is_callable( array( $module, 'format_addon_price' ) ) ) {
				return null;
			}
			// The module caches addons per product for the request. Read-only check that nothing primed it.
			$peek = Closure::bind(
				function () {
					return isset( $this->product_addons_cache ) ? $this->product_addons_cache : array();
				},
				$module,
				get_class( $module )
			);
			if ( ! empty( $peek() ) ) {
				return null;
			}
			$filters = array();
			foreach ( $overlay as $option => $value ) {
				$cb                 = static function () use ( $value ) {
					return $value;
				};
				$filters[ $option ] = $cb;
				add_filter( $option, $cb, PHP_INT_MAX );
			}
			global $post;
			$saved_post  = $post;
			$shown       = $module->are_addons_displayed;
			$html        = '';
			$extra_price = '';
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored in finally.
			$post = get_post( $product->get_id() );
			setup_postdata( $post );
			$module->are_addons_displayed = null;
			ob_start();
			try {
				$module->add_addons_to_frontend();
				if ( '' !== $price ) {
					$extra_price = (string) $module->format_addon_price( $price, $product );
				}
			} finally {
				$html                         = (string) ob_get_clean();
				$module->are_addons_displayed = $shown;
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$post = $saved_post;
				if ( $saved_post ) {
					setup_postdata( $saved_post );
				} else {
					wp_reset_postdata();
				}
				foreach ( $filters as $option => $cb ) {
					remove_filter( $option, $cb, PHP_INT_MAX );
				}
			}
			$html = preg_replace( '/(name="wcj_product_addons-nonce" value=")[^"]*"/', '$1"', $html );
			return array(
				'html'  => $html,
				'price' => html_entity_decode( wp_strip_all_tags( $extra_price ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			);
		}

		/**
		 * Preview service: one side per request, because the module caches per request. Writes nothing.
		 *
		 * @return array
		 */
		public static function preview() {
			self::authorize();
			$i     = self::posted_slot();
			$check = self::validate( $i, self::posted_json( 'changes' ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
			$side = isset( $_POST['side'] ) && 'now' === $_POST['side'] ? 'now' : 'after';
			if ( 'after' === $side && $check['issues'] ) {
				return array(
					'error'  => __( 'Fix the highlighted fields to see the preview.', 'woocommerce-jetpack' ),
					'issues' => $check['issues'],
				);
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
			$sample  = isset( $_POST['sample'] ) && is_string( $_POST['sample'] ) ? absint( $_POST['sample'] ) : 0;
			$product = $sample ? wc_get_product( $sample ) : null;
			if ( ! $product || 'publish' !== $product->get_status() || $product->is_type( 'variation' ) ) {
				$first   = wc_get_products( array( 'status' => 'publish', 'limit' => 1, 'orderby' => 'modified', 'order' => 'DESC' ) );
				$product = $first ? $first[0] : null;
			}
			$limits = array(
				__( 'This is Booster’s own product-page markup with simple styling. It is not your theme.', 'woocommerce-jetpack' ),
				__( 'Nothing is added to a cart and no order is created. Cart totals, tax, shipping, coupons and currency switching are not shown.', 'woocommerce-jetpack' ),
			);
			$base = array(
				'side'   => $side,
				'limits' => $limits,
				'notes'  => array(),
			);
			if ( ! $product ) {
				return $base + array( 'state' => 'no_product' );
			}
			// The product's own price is read only after the native render: with Product Addons on, reading a
			// price runs the module's price filter, which fills its per-request addon cache for this product.
			$base['product'] = array(
				'id'    => $product->get_id(),
				'name'  => wp_strip_all_tags( $product->get_name() ),
				'type'  => $product->get_type(),
				'price' => '',
			);
			$values   = 'now' === $side ? $check['current'] : $check['effective'];
			$module   = 'now' === $side ? $check['env']['module'] : ( $check['env']['module'] || $check['act_module'] );
			$excluded = is_array( $values['exclude'] ) && in_array( (string) $product->get_id(), $values['exclude'], true );
			$live     = $module && ( 'now' === $side ? $check['live_now'] : $check['live_after'] );
			if ( $excluded ) {
				$base['notes'][] = __( 'This product is excluded, so the extra does not show here.', 'woocommerce-jetpack' );
			}
			if ( $live && is_string( $values['price'] ) && '%' === substr( $values['price'], -1 ) && ! $product->is_type( 'simple' ) ) {
				$base['notes'][] = 'yes' === wcj_get_option( 'wcj_product_addons_template_hide_percentage_price', 'yes' )
					? __( 'On this kind of product the percent price is not shown next to the extra (your Hide percentage price setting).', 'woocommerce-jetpack' )
					: __( 'On this kind of product the percent is shown as typed, not as an amount.', 'woocommerce-jetpack' );
			}
			if ( wc_tax_enabled() ) {
				$base['notes'][] = __( 'Prices follow your shop’s tax display for your store address.', 'woocommerce-jetpack' );
			}
			if ( 'after' === $side ) {
				$base['review'] = self::review_rows( $i, $check );
			}
			if ( ! $module ) {
				$base['product']['price'] = self::shown_price( $product );
				return $base + array( 'state' => 'module_off' );
			}
			$render                   = self::render_native( $product, 'now' === $side ? array() : self::overlay( $i, $check ), $live && ! $excluded && is_string( $values['price'] ) ? $values['price'] : '' );
			$base['product']['price'] = self::shown_price( $product );
			if ( null === $render ) {
				// No picture rather than a guess; say in words what customers get, from the same values.
				$required = 'yes' === $values['required'] ? __( 'customers must choose it', 'woocommerce-jetpack' ) : __( 'optional', 'woocommerce-jetpack' );
				return $base + array(
					'state'    => 'unavailable',
					/* translators: 1: extra name, 2: price, 3: optional or required. */
					'readback' => $live ? sprintf( __( 'Customers see “%1$s”, %2$s, %3$s.', 'woocommerce-jetpack' ), $values['label'], self::display( 'price', $values['price'] ), $required ) : __( 'Customers do not see this extra.', 'woocommerce-jetpack' ),
				);
			}
			$out = $base + array(
				'state' => 'rendered',
				'html'  => $render['html'],
				'extra' => $live && ! $excluded ? array(
					'name'  => $values['label'],
					'price' => $render['price'],
				) : null,
			);
			return $out;
		}

		/**
		 * The product's price as the shop displays it.
		 *
		 * @param WC_Product $product Product.
		 * @return string
		 */
		private static function shown_price( $product ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( wc_get_price_to_display( $product ) ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		}

		// ---------------------------------------------------------------------
		// Apply, receipt, recovery.
		// ---------------------------------------------------------------------

		/**
		 * Apply one consolidated draft for one slot. Every value is checked against what was loaded
		 * before the first write; a conflict found then writes nothing. A conflict during the writes
		 * puts back only the values that still match this apply and reports what stayed.
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
			$lock = WCJ_Job_Store::lock( self::LOCK, __( 'Another extra apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
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
					throw new RuntimeException( esc_html__( 'Confirm the changes in the review before applying. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
				$plan  = array();
				$stale = array();
				// Everything the owner saw must be unchanged, including values this apply does not write
				// (an unchanged price that would go live, a hidden extra that a decision would show).
				foreach ( self::watch( $i, array_keys( self::dormant( $i ) ) ) as $option ) {
					if ( ! isset( $base[ $option ] ) || ! is_string( $base[ $option ] ) || ! hash_equals( WCJ_Job_Store::state_hash( WCJ_Job_Store::read_state( $option ) ), $base[ $option ] ) ) {
						$stale[ self::option_label( $option, $i ) ] = true;
					}
				}
				$stale = array_keys( $stale );
				foreach ( self::planned( $i, $check ) as $w ) {
					list( $option, $field, $value ) = $w;
					$before = WCJ_Job_Store::read_state( $option );
					if ( ! isset( $base[ $option ] ) || ! is_string( $base[ $option ] ) || ! hash_equals( WCJ_Job_Store::state_hash( $before ), $base[ $option ] ) ) {
						$stale[] = self::field_label( $field, $i );
						continue;
					}
					$after = WCJ_Job_Store::value_state( $value );
					if ( $before !== $after ) {
						$plan[ $option ] = array(
							'field'  => $field,
							'before' => $before,
							'after'  => $after,
						);
					}
				}
				if ( $stale ) {
					return array(
						'status' => 'stale',
						'stale'  => array_values( array_unique( $stale ) ),
						'error'  => __( 'These settings changed since you loaded them. Nothing was changed. Your draft is kept; check the preview and review again before applying.', 'woocommerce-jetpack' ),
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
						$failed = self::field_label( $entry['field'], $i );
						break;
					}
					$applied[ $option ] = $entry;
				}
				if ( null !== $failed ) {
					$kept = array();
					foreach ( array_reverse( $applied, true ) as $option => $entry ) {
						if ( ! WCJ_Job_Store::compare_exchange( $option, $entry['after'], $entry['before'] ) ) {
							$kept[] = self::field_label( $entry['field'], $i );
						}
					}
					return array(
						'status' => 'failed',
						'error'  => $kept ? __( 'A setting changed while saving. Earlier writes that changed again could not be put back; they are listed and remain saved.', 'woocommerce-jetpack' ) : __( 'A setting changed while saving. Earlier writes from this apply were put back.', 'woocommerce-jetpack' ),
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
					$entries[ $option ] = $entry + array( 'readback_ok' => $ok );
				}
				$receipt = array(
					'id'      => bin2hex( random_bytes( 8 ) ),
					'time'    => gmdate( 'c' ),
					'user'    => get_current_user_id(),
					'slot'    => $i,
					'status'  => $all_ok ? 'applied' : 'readback_mismatch',
					'entries' => $entries,
					// What Undo depends on, as it stands right after this apply.
					'context' => self::fingerprints( $i ),
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
			return is_int( $slot ) && $slot >= 1 && $slot <= max( self::total(), 1 ) + 1 ? $slot : 0;
		}

		/**
		 * Receipt entries for an option this job may write for the receipt's slot, with an exact state shape.
		 *
		 * @param mixed $entries Stored entries.
		 * @param int   $i Slot.
		 * @return array
		 */
		private static function valid_entries( $entries, $i ) {
			$allowed = array(
				self::MODULE => 'module',
				self::MASTER => 'master',
				self::TOTAL  => 'total',
			);
			if ( $i ) {
				$allowed[ self::P . 'type_' . $i ] = 'type';
				foreach ( self::fields( $i ) as $key => $def ) {
					$allowed[ $def['option'] ] = $key;
				}
			}
			$valid = array();
			foreach ( is_array( $entries ) ? $entries : array() as $option => $entry ) {
				$field = is_array( $entry ) && isset( $entry['field'] ) && is_string( $entry['field'] ) ? $entry['field'] : '';
				$ok    = isset( $allowed[ $option ] ) ? $allowed[ $option ] === $field : ( $i && preg_match( '/^pause_([1-9][0-9]{0,2})$/D', $field, $m ) && (int) $m[1] !== $i && self::P . 'enabled_' . $m[1] === $option );
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
			$i    = self::receipt_slot( $receipt );
			$rows = array();
			foreach ( self::valid_entries( isset( $receipt['entries'] ) ? $receipt['entries'] : array(), $i ) as $option => $entry ) {
				$current = WCJ_Job_Store::read_state( $option );
				$before  = $entry['before']['exists'] ? self::str( WCJ_Job_Store::state_value( $entry['before'] ) ) : null;
				$rows[]  = array(
					'option'      => $option,
					'field'       => $entry['field'],
					'label'       => self::field_label( $entry['field'], $i ),
					'before'      => null === $before ? __( '(not set)', 'woocommerce-jetpack' ) : self::display( $entry['field'], $before ),
					'after'       => self::display( $entry['field'], self::str( WCJ_Job_Store::state_value( $entry['after'] ) ) ),
					'readback_ok' => ! empty( $entry['readback_ok'] ),
					'still_ours'  => $current === $entry['after'],
					'restored'    => $current === $entry['before'],
				);
			}
			// The writer refuses an unsafe group, even where one individual row still matches.
			$context_ok = false;
			if ( $i && array_filter( wp_list_pluck( $rows, 'still_ours' ) ) && isset( $receipt['context']['slot'], $receipt['context']['others'] ) && is_string( $receipt['context']['slot'] ) && is_string( $receipt['context']['others'] ) && ! empty( $receipt['entries'] ) && is_array( $receipt['entries'] ) ) {
				$current_ctx = self::fingerprints( $i );
				$wide = false;
				foreach ( self::valid_entries( $receipt['entries'], $i ) as $saved_entry ) {
					$wide = $wide || in_array( $saved_entry['field'], array( 'module', 'master', 'total' ), true ) || 0 === strpos( $saved_entry['field'], 'pause_' );
				}
				$context_ok = hash_equals( $receipt['context']['slot'], $current_ctx['slot'] ) && ( ! $wide || hash_equals( $receipt['context']['others'], $current_ctx['others'] ) );
			}
			$stamp = isset( $receipt['time'] ) && is_string( $receipt['time'] ) ? strtotime( $receipt['time'] ) : false;
			return array(
				'slot'   => $i,
				'time'   => $stamp ? ( function_exists( 'wp_date' ) ? wp_date( 'M j, g:i a', $stamp ) : date_i18n( 'M j, g:i a', $stamp ) ) : '',
				'undo'   => $context_ok && ! in_array( $receipt['status'] ?? '', array( 'restored', 'undo_incomplete' ), true ) && (bool) array_filter( wp_list_pluck( $rows, 'still_ours' ) ),
				'status' => isset( $receipt['status'] ) && is_string( $receipt['status'] ) ? $receipt['status'] : '',
				'rows'   => $rows,
			);
		}

		/**
		 * Fingerprint of every Product Addons setting and the per-product path, except the options this
		 * undo itself writes. Any difference between two readings means another writer was active.
		 *
		 * @param array $valid Receipt entries keyed by option.
		 * @return string
		 */
		private static function undo_context( $valid ) {
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name", $wpdb->esc_like( 'wcj_product_addons_' ) . '%' ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$keep = array();
			foreach ( (array) $rows as $row ) {
				if ( ! isset( $valid[ $row['option_name'] ] ) ) {
					$keep[ $row['option_name'] ] = $row['option_value'];
				}
			}
			$keep['~per_ids'] = implode( ',', self::per_product_ids() );
			return hash( 'sha256', serialize( $keep ) );
		}

		/**
		 * Put back what an interrupted undo wrote, newest undo write last (i.e. in apply order), each only
		 * while it still holds the value the undo wrote. A value that moved again is never overwritten.
		 *
		 * @param array $journal Undone entries keyed by option, in undo order.
		 * @return array reapplied and unresolved entries keyed by option.
		 */
		private static function reverse_journal( $journal ) {
			$reapplied  = array();
			$unresolved = array();
			foreach ( array_reverse( $journal, true ) as $option => $entry ) {
				$current = WCJ_Job_Store::read_state( $option );
				if ( $current === $entry['after'] ) {
					$reapplied[ $option ] = $entry;
				} elseif ( $current === $entry['before'] && WCJ_Job_Store::compare_exchange( $option, $entry['before'], $entry['after'] ) ) {
					$reapplied[ $option ] = $entry;
				} else {
					$unresolved[ $option ] = $entry;
				}
			}
			return array(
				'reapplied'  => $reapplied,
				'unresolved' => $unresolved,
			);
		}

		/**
		 * Recovery response with labels and fresh state.
		 *
		 * @param string $status restored or partial.
		 * @param array  $restored Fields left undone.
		 * @param array  $conflicts Labels kept or changed.
		 * @param int    $ignored Unreadable receipt entries.
		 * @param string $why Explanation for partial.
		 * @param int    $i Receipt slot.
		 * @param int    $caller Slot the page shows.
		 * @return array
		 */
		private static function recover_result( $status, $restored, $conflicts, $ignored, $why, $i, $caller ) {
			$fresh_slot = min( $caller, self::total() + ( self::can_add() ? 1 : 0 ) );
			return array(
				'status'    => $status,
				'restored'  => array_values( array_map( static function ( $field ) use ( $i ) { return self::field_label( $field, $i ); }, $restored ) ),
				'conflicts' => array_values( array_unique( $conflicts ) ),
				'ignored'   => $ignored,
				'message'   => 'restored' === $status ? __( 'Undone and read from the database. Carts and orders were not touched.', 'woocommerce-jetpack' ) : $why . ' ' . __( 'Carts and orders were not touched.', 'woocommerce-jetpack' ),
				'slot'      => $i,
				'fresh'     => self::load( max( 1, $fresh_slot ) ),
			);
		}

		/**
		 * Undo the last apply as one dependent group, never key by key. The slot's rows, the extras count,
		 * the switches and the hidden extras it paused depend on each other: lowering the count or
		 * switching extras off would hide newer work, and deleting only some slot rows would leave a
		 * blank, free extra (native treats a missing switch as on and a missing price as 0).
		 * So the group is put back only when the slot is exactly as this apply left it and, if the apply
		 * touched switches, count or other extras, nothing else that they keep visible changed since.
		 * Otherwise everything is kept and named. Writes run in reverse apply order (hide first) and are
		 * journalled. If a value changes during the undo (a write is refused, an undone value moves again,
		 * or anything the group depends on changes), the journal is reversed so the extra is back as the
		 * apply left it plus the newer change; values that changed again are left alone and named.
		 * Native settings writes do not take this job's lock, so this is detection and conditional
		 * reversal, not a transaction.
		 *
		 * @return array
		 * @throws RuntimeException When nothing is recoverable.
		 */
		public static function recover() {
			self::authorize();
			$caller = self::posted_slot();
			$lock   = WCJ_Job_Store::lock( self::LOCK, __( 'Another extra apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
			try {
				$receipt = get_option( self::RECEIPT, null );
				if ( ! is_array( $receipt ) || empty( $receipt['entries'] ) || ! is_array( $receipt['entries'] ) ) {
					throw new RuntimeException( esc_html__( 'There is no applied change left to undo.', 'woocommerce-jetpack' ) );
				}
				$i       = self::receipt_slot( $receipt );
				$valid   = self::valid_entries( $receipt['entries'], $i );
				$ignored = count( $receipt['entries'] ) - count( $valid );
				$ctx     = isset( $receipt['context']['slot'], $receipt['context']['others'] ) && is_string( $receipt['context']['slot'] ) && is_string( $receipt['context']['others'] ) ? $receipt['context'] : null;
				$now     = $i ? self::fingerprints( $i ) : null;
				$wide    = false;
				foreach ( $valid as $entry ) {
					$wide = $wide || in_array( $entry['field'], array( 'module', 'master', 'total' ), true ) || 0 === strpos( $entry['field'], 'pause_' );
				}
				$why = '';
				if ( ! $ctx || ! $now || ! hash_equals( $ctx['slot'], $now['slot'] ) ) {
					$why = __( 'This extra changed after your apply, so nothing was undone and it stays as it is now.', 'woocommerce-jetpack' );
				} elseif ( $wide && ! hash_equals( $ctx['others'], $now['others'] ) ) {
					$why = __( 'Other Product Addons settings changed after your apply. Switching extras off or lowering their number would hide that newer work, so nothing was undone.', 'woocommerce-jetpack' );
				}
				$conflicts = array();
				$remaining = array();
				$journal   = array();
				$stopped   = '' !== $why;
				// Everything the group depends on that this undo does not write: other extras, the count's
				// neighbours, templates, switches not in the receipt, the per-product path.
				$context = $stopped ? '' : self::undo_context( $valid );
				foreach ( array_reverse( $valid, true ) as $option => $entry ) {
					if ( $stopped ) {
						$remaining[ $option ] = $entry;
						$conflicts[]          = self::field_label( $entry['field'], $i );
						continue;
					}
					$current = WCJ_Job_Store::read_state( $option );
					if ( $current === $entry['before'] ) {
						continue;
					}
					if ( $current === $entry['after'] && WCJ_Job_Store::compare_exchange( $option, $entry['after'], $entry['before'] ) ) {
						$journal[ $option ] = $entry;
						continue;
					}
					$stopped     = true;
					$conflicts[] = self::field_label( $entry['field'], $i );
				}
				if ( '' === $context ) {
					// Refused before any write: unchanged behaviour.
					// Unreadable entries are never written and are dropped from the local receipt; the result says so.
					$receipt['entries'] = array_intersect_key( $valid, $remaining );
					$receipt['status']  = 'partially_restored';
					update_option( self::RECEIPT, $receipt, false );
					return self::recover_result( 'partial', array(), $conflicts, $ignored, $why, $i, $caller );
				}
				// A per-key CAS only proves each key at the moment it was written. Check the whole group
				// again now: every undone value must still be what this undo wrote, and nothing it depends
				// on may have changed during the undo.
				if ( ! $stopped ) {
					foreach ( $journal as $option => $entry ) {
						if ( WCJ_Job_Store::read_state( $option ) !== $entry['before'] ) {
							$stopped     = true;
							$conflicts[] = self::field_label( $entry['field'], $i );
						}
					}
					if ( ! hash_equals( $context, self::undo_context( $valid ) ) ) {
						$stopped     = true;
						$conflicts[] = __( 'Other Product Addons settings', 'woocommerce-jetpack' );
					}
				}
				if ( ! $stopped ) {
					$receipt['entries'] = array();
					$receipt['status']  = 'restored';
					update_option( self::RECEIPT, $receipt, false );
					return self::recover_result( 'restored', wp_list_pluck( $journal, 'field' ), array(), $ignored, '', $i, $caller );
				}
				// Something changed while undoing. A half-undone group can hide newer work (count lowered,
				// switch or price removed), so put back what this undo wrote, in apply order (content before
				// count and switches), each only while it still holds the value this undo wrote.
				$back = self::reverse_journal( $journal );
				// Never dropped while settings could still be mixed: Undo stays refused by the slot check,
				// and the journal records what happened.
				$receipt['entries']   = $valid;
				$receipt['status']    = $back['unresolved'] ? 'undo_incomplete' : 'undo_reversed';
				$receipt['last_undo'] = array(
					'time'       => gmdate( 'c' ),
					'conflicts'  => $conflicts,
					'reapplied'  => array_keys( $back['reapplied'] ),
					'unresolved' => array_keys( $back['unresolved'] ),
				);
				update_option( self::RECEIPT, $receipt, false );
				if ( $back['unresolved'] ) {
					/* translators: %s: setting names. */
					$why = sprintf( __( 'Settings changed while undoing. The undo was reversed where possible, but these settings changed again and were left as they are now: %s. Check this extra in the Product Addons settings.', 'woocommerce-jetpack' ), implode( ', ', array_map( static function ( $e ) use ( $i ) { return self::field_label( $e['field'], $i ); }, $back['unresolved'] ) ) );
				} else {
					$why = __( 'Settings changed while undoing, so the undo was reversed. This extra is back as it was after your apply, and the newer change was kept.', 'woocommerce-jetpack' );
				}
				$result = self::recover_result( 'partial', array(), $conflicts, $ignored, $why, $i, $caller );
				$result['unresolved'] = array_values( array_map( static function ( $entry ) use ( $i ) { return self::field_label( $entry['field'], $i ); }, $back['unresolved'] ) );
				return $result;
			} finally {
				WCJ_Job_Store::unlock( self::LOCK, $lock );
			}
		}

		/**
		 * AJAX adapter: POST, capability and per-action nonce on every action.
		 */
		public function handle() {
		if ( ! WCJ_Task_Finder::allowed( 'product_addons' ) ) { wp_send_json_error( array( 'error' => esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ) ), 403 ); }
			try {
				self::authorize();
				$action = isset( $_POST['action'] ) && is_string( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$op     = str_replace( 'wcj_product_extra_job_', '', $action );
				if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! in_array( $op, self::OPS, true ) ) {
					throw new RuntimeException( esc_html__( 'Invalid request.', 'woocommerce-jetpack' ) );
				}
				check_ajax_referer( 'wcj_product_extra_job_' . $op, 'nonce' );
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
			if ( ! WCJ_Task_Finder::allowed( 'product_addons' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
			}
			$total = self::total();
			$max   = $total + ( self::can_add() ? 1 : 0 );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page selection, re-checked on every request.
			$slot = isset( $_GET['slot'] ) ? absint( $_GET['slot'] ) : 0;
			if ( $slot < 1 || $slot > $max ) {
				$slot  = 0;
				$slots = self::slots();
				foreach ( array( 'live', 'free' ) as $want ) {
					foreach ( $slots as $s ) {
						if ( ! $slot && $want === $s['state'] && ! $s['locked'] ) {
							$slot = $s['slot'];
						}
					}
				}
				if ( ! $slot ) {
					$slot = $max > $total ? $max : 1;
				}
			}
			wp_enqueue_style( 'wcj-invoice-job', wcj_plugin_url() . '/assets/css/admin/wcj-invoice-job.css', array(), '2.5' );
			wp_enqueue_style( 'wcj-product-extra-job', wcj_plugin_url() . '/assets/css/admin/wcj-product-extra-job.css', array( 'wcj-invoice-job' ), '1.1' );
			wp_enqueue_script( 'wcj-product-extra-job', wcj_plugin_url() . '/assets/js/admin/wcj-product-extra-job.js', array(), '1.5', true );
			$nonces = array();
			foreach ( self::OPS as $op ) {
				$nonces[ $op ] = wp_create_nonce( 'wcj_product_extra_job_' . $op );
			}
			wp_localize_script(
				'wcj-product-extra-job',
				'wcjProductExtraJob',
				array(
					'url'    => admin_url( 'admin-ajax.php' ),
					'nonces' => $nonces,
					'search' => wp_create_nonce( 'search-products' ),
					'slot'   => $slot,
					'user'   => get_current_user_id(),
					'site'   => substr( md5( home_url() ), 0, 10 ),
					'page'   => self::url(),
					'native' => self::native_url(),
				)
			);
			include __DIR__ . '/views/product-extra-job.php';
		}
	}

endif;

new WCJ_Product_Extra_Job();

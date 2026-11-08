<?php
/**
 * One native per-product text question. No frontend renderer or pricing replacement.
 *
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-job-store.php';
require_once __DIR__ . '/product-question-groups.php';

final class WCJ_Product_Question_Job {
	const RECEIPT = 'booster_product_question_job_unused';
	const META    = '_wcj_product_input_fields';
	const MODULE  = 'wcj_product_input_fields_enabled';
	const LOCAL   = 'wcj_product_input_fields_local_enabled';
	const OPS     = array( 'search', 'load', 'save', 'undo' );

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register' ), 99 );
		add_action( 'add_meta_boxes_product', array( 'WCJ_Product_Question_Groups', 'panel_help' ), 100 );
		foreach ( self::OPS as $op ) {
			add_action( 'wp_ajax_wcj_product_question_' . $op, array( $this, 'handle' ) );
		}
	}
	public static function url() {
		return admin_url( 'admin.php?page=wcj-product-question-job' ); }
	public static function native_url() {
		return WCJ_Product_Question_Groups::native_url( 'product_input_fields' ); }
	/**
	 * Name the site-local provenance record for one product.
	 *
	 * @param mixed $id Id.
	 * @return mixed
	 */
	private static function receipt_key( $id ) {
		return 'booster_product_question_job_' . $id; }
	/**
	 * Reject the request without a success claim.
	 *
	 * @param mixed $message Message.
	 * @param mixed $code Code.
	 * @return void
	 */
	private static function fail( $message, $code = 422 ) {
		throw new RuntimeException( $message, $code ); }
	/**
	 * Enforce native module rights and selected product editing permission.
	 *
	 * @param mixed $id Id.
	 * @return void
	 */
	public static function authorize( $id = 0 ) {
		if ( ! WCJ_Task_Finder::allowed( 'product_input_fields' ) ) {
			self::fail( __( 'You need permission for Product Input Fields.', 'woocommerce-jetpack' ) );
		}
		if ( $id && ( 'product' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) || in_array( get_post_status( $id ), array( 'trash', 'auto-draft' ), true ) ) ) {
			self::fail( __( 'Choose a product you can edit.', 'woocommerce-jetpack' ) );
		}
	}
	public function register() {
		if ( WCJ_Task_Finder::allowed( 'product_input_fields' ) ) {
			add_submenu_page( 'woocommerce', __( 'Product question', 'woocommerce-jetpack' ), __( 'Product question', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-product-question-job', array( $this, 'render' ) );
		}
	}
	/**
	 * Read exact native configuration without invoking legacy conversion.
	 *
	 * @param mixed $id Id.
	 * @param mixed $lock Lock.
	 * @return mixed
	 */
	private static function context( $id, $lock = false ) {
		global $wpdb;
		$tail    = $lock ? ' FOR UPDATE' : '';
		$options = $wpdb->get_results( $wpdb->prepare( "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name LIMIT 10001" . $tail, $wpdb->esc_like( 'wcj_product_input_fields_' ) . '%' ), ARRAY_A );
		$meta    = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id,post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE %s ORDER BY meta_id LIMIT 10001" . $tail, $wpdb->esc_like( self::META ) . '%' ), ARRAY_A );
		if ( $wpdb->last_error || count( $options ) > 10000 || count( $meta ) > 10000 ) {
			self::fail( __( 'This configuration needs the full editor. Nothing was changed.', 'woocommerce-jetpack' ) );
		}
		$selected = array_values(
			array_filter(
				$meta,
				static function ( $row ) use ( $id ) {
					return (int) $row['post_id'] === $id;
				}
			)
		);
		return array(
			'options'  => $options,
			'meta'     => $meta,
			'selected' => $selected,
			'receipt'  => WCJ_Job_Store::read_state( self::receipt_key( $id ) ),
		);
	}
	/**
	 * Decode the internal receipt without instantiating serialized objects.
	 *
	 * @param mixed $state State.
	 * @return mixed
	 */
	private static function receipt_value( $state ) {
		return $state['exists'] && is_serialized( $state['raw'] ) ? unserialize( $state['raw'], array( 'allowed_classes' => false ) ) : null;
	}
	/**
	 * Keep unrelated product data out of ordinary edit conflicts.
	 *
	 * @param mixed $context Context.
	 * @param mixed $broad Broad.
	 * @return mixed
	 */
	private static function scoped_context( $context, $broad ) {
		if ( ! $broad ) { $context['meta'] = $context['selected']; }
		return $context;
	}
	/**
	 * Bind the user, product, write scope and exact current configuration.
	 *
	 * @param mixed $id Id.
	 * @param mixed $context Context.
	 * @param mixed $broad Broad.
	 * @return mixed
	 */
	private static function token_part( $id, $context, $broad ) {
		return hash_hmac( 'sha256', serialize( array( get_current_user_id(), $id, $broad, self::scoped_context( $context, $broad ) ) ), wp_salt( 'nonce' ) );
	}
	/**
	 * Return selected-product and wider-activation fingerprints.
	 *
	 * @param mixed $id Id.
	 * @param mixed $context Context.
	 * @return mixed
	 */
	private static function token( $id, $context ) {
		return self::token_part( $id, $context, false ) . '.' . self::token_part( $id, $context, true );
	}
	/**
	 * Read a scalar native option or its renderer default.
	 *
	 * @param mixed $context Context.
	 * @param mixed $key Key.
	 * @param mixed $default Default.
	 * @return mixed
	 */
	private static function option( $context, $key, $default = 'no' ) {
		foreach ( $context['options'] as $row ) {
			if ( $row['option_name'] === $key ) {
				return $row['option_value']; }
		}
		return $default;
	}
	/**
	 * Preserve absence separately from raw option bytes.
	 *
	 * @param mixed $context Context.
	 * @param mixed $key Key.
	 * @return mixed
	 */
	private static function option_state( $context, $key ) {
		foreach ( $context['options'] as $row ) {
			if ( $row['option_name'] === $key ) {
				return array(
					'exists' => true,
					'raw'    => $row['option_value'],
				); }
		}
		return array(
			'exists' => false,
			'raw'    => '',
		);
	}
	/**
	 * Accept only supported plain field configuration text.
	 *
	 * @param mixed $value Value.
	 * @param mixed $max Max.
	 * @return mixed
	 */
	private static function plain( $value, $max = 200 ) {
		return is_string( $value ) && strlen( $value ) <= $max && ! preg_match( '/[<>\[\]\r\n\x00-\x1f]/u', $value ) && 1 === preg_match( '//u', $value );
	}
	/**
	 * Refuse unsupported storage and determine the actual native slot.
	 *
	 * @param mixed $id Id.
	 * @param mixed $context Context.
	 * @return mixed
	 */
	private static function inspect( $id, $context ) {
		$result = array(
			'reason' => '',
			'fields' => array(),
			'data'   => array(),
			'slot'   => 1,
			'edit'   => false,
			'raw'    => null,
		);
		$rows   = $context['selected'];
		if ( count( $rows ) > 1 || ( $rows && self::META !== $rows[0]['meta_key'] ) ) {
			$result['reason'] = __( 'Legacy, mixed or duplicate field storage is present. Use the full product editor; this task will not migrate or overwrite it.', 'woocommerce-jetpack' );
			return $result;
		}
		if ( $rows ) {
			$raw = $rows[0]['meta_value'];
			// Disable object instantiation even for an unexpected stored serialized object.
			$data = is_serialized( $raw ) ? unserialize( $raw, array( 'allowed_classes' => false ) ) : null;
			if ( ! is_array( $data ) || strlen( $raw ) > 65536 || ! isset( $data['local_total_number'] ) || ! preg_match( '/^[0-9]{1,3}$/D', (string) $data['local_total_number'] ) ) {
				$result['reason'] = __( 'This product has custom field storage. Keep it in the full editor.', 'woocommerce-jetpack' );
				return $result;
			}
			$count = (int) $data['local_total_number'];
			foreach ( $data as $key => $value ) {
				if ( ! is_scalar( $value ) || ( 'local_total_number' !== $key && ( ! preg_match( '/^(?:enabled|order|type|title|placeholder|required|required_message|maxlength|class|type_checkbox_yes|type_checkbox_no|type_checkbox_default|type_file_accept|type_file_max_size|type_datepicker_format|type_datepicker_mindate|type_datepicker_maxdate|type_datepicker_changeyear|type_datepicker_yearrange|type_datepicker_firstday|type_timepicker_format|type_timepicker_mintime|type_timepicker_maxtime|type_timepicker_interval|type_select_options)_local_([1-9][0-9]*)$/D', $key, $m ) || (int) $m[1] > $count ) ) ) {
					$result['reason'] = __( 'Custom or out-of-range field data is present. Use the full editor.', 'woocommerce-jetpack' );
					return $result;
				}
			}
			$result['raw']  = $raw;
			$result['data'] = $data;
			$result['slot'] = $count + 1;
			for ( $i = 1; $i <= $count; ++$i ) {
				$result['fields'][] = array(
					'slot'    => $i,
					'title'   => isset( $data[ 'title_local_' . $i ] ) ? wp_strip_all_tags( $data[ 'title_local_' . $i ] ) : '',
					'type'    => isset( $data[ 'type_local_' . $i ] ) ? $data[ 'type_local_' . $i ] : 'text',
					'enabled' => isset( $data[ 'enabled_local_' . $i ] ) && in_array( $data[ 'enabled_local_' . $i ], array( 'yes', 'on' ), true ),
				);
			}
		}
		$receipt = self::receipt_value( $context['receipt'] );
		if ( is_array( $receipt ) && ! empty( $receipt['ownership'] ) ) {
			$own = $receipt['ownership'];
			if ( ! is_array( $own ) || ! isset( $own['raw'], $own['slot'] ) || ! is_string( $own['raw'] ) || ! is_int( $own['slot'] ) ) {
				$result['reason'] = __( 'The saved task identity is not supported. Use the full editor; nothing was changed.', 'woocommerce-jetpack' );
				return $result;
			}
			if ( $result['raw'] !== $own['raw'] || ! isset( $result['data'][ 'type_local_' . $own['slot'] ] ) || ! in_array( $result['data'][ 'type_local_' . $own['slot'] ], array( 'text', 'textarea' ), true ) ) {
				$result['reason'] = __( 'The question created here changed in another editor. It stays untouched; use full settings.', 'woocommerce-jetpack' );
				return $result;
			}
			$result['slot'] = (int) $own['slot'];
			$result['edit'] = true;
		}
		$slot = $result['slot'];
		if ( $slot > 100 || $slot !== (int) apply_filters( 'booster_option', 1, $slot ) ) {
			$result['reason'] = __( 'No additional field slot is available for this product in this edition. Existing fields stay in the full editor.', 'woocommerce-jetpack' );
		}
		return $result;
	}
	/**
	 * Describe current saved configuration without any mutation.
	 *
	 * @param mixed $id Id.
	 * @return mixed
	 */
	public static function load( $id ) {
		self::authorize( $id );
		$c     = self::context( $id );
		$i     = self::inspect( $id, $c );
		$slot  = $i['slot'];
		$draft = array(
			'title'       => '',
			'type'        => 'text',
			'placeholder' => '',
			'required'    => false,
			'maxlength'   => 0,
		);
		if ( $i['edit'] ) {
			foreach ( $draft as $key => $default ) {
				$draft[ $key ] = isset( $i['data'][ $key . '_local_' . $slot ] ) ? $i['data'][ $key . '_local_' . $slot ] : $default; }
			$draft['required'] = in_array( $draft['required'], array( 'yes', 'on' ), true );
		}
		$r    = self::receipt_value( $c['receipt'] );
		$undo = is_array( $r ) && isset( $r['undo'] ) && (int) $r['user'] === get_current_user_id();
		return array(
			'display' => array(
				'position' => self::option( $c, 'wcj_product_input_fields_position', 'woocommerce_before_add_to_cart_button' ),
				'cart'     => self::option( $c, 'wcj_product_input_fields_display_options', 'name' ),
			),
			'product' => $id,
			'title'   => get_the_title( $id ),
			'token'   => self::token( $id, $c ),
			'reason'  => $i['reason'],
			'fields'  => $i['fields'],
			'slot'    => $slot,
			'edit'    => $i['edit'],
			'draft'   => $draft,
			'module'  => 'yes' === self::option( $c, self::MODULE ),
			'local'   => 'yes' === self::option( $c, self::LOCAL ),
			'global'  => 'yes' === self::option( $c, 'wcj_product_input_fields_global_enabled' ),
			'changes' => $undo && isset( $r['changes'] ) ? $r['changes'] : array(),
			'undo'    => $undo,
			'saved'   => $i['edit'],
			'native'  => get_edit_post_link( $id, 'raw' ),
			'shopper' => get_permalink( $id ),
		);
	}
	private static function transactional() {
		global $wpdb;
		if ( true === wp_using_ext_object_cache() ) {
			self::fail( __( 'This task cannot safely save with persistent object caching enabled. Use full settings; nothing was changed.', 'woocommerce-jetpack' ) ); }
		foreach ( array( $wpdb->options, $wpdb->postmeta, $wpdb->posts ) as $table ) {
			$row = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A );
			if ( ! $row || 'InnoDB' !== $row['Engine'] ) {
				self::fail( __( 'This task needs transactional database tables. Use full settings; nothing was changed.', 'woocommerce-jetpack' ) ); }
		}
	}
	/**
	 * Conditionally write only the selected aggregate metadata row.
	 *
	 * @param mixed $id Id.
	 * @param mixed $rows Rows.
	 * @param mixed $raw Raw.
	 * @return void
	 */
	private static function meta_write( $id, $rows, $raw ) {
		global $wpdb;
		if ( count( $rows ) > 1 ) {
			self::fail( __( 'Duplicate storage cannot be changed here.', 'woocommerce-jetpack' ) ); }
		if ( $rows ) {
			$row = $rows[0];
			if ( $raw === $row['meta_value'] ) {
				return; }
			$result = null === $raw ? $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_id=%d AND post_id=%d AND meta_key=%s AND BINARY meta_value=%s", $row['meta_id'], $id, self::META, $row['meta_value'] ) ) : $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value=%s WHERE meta_id=%d AND post_id=%d AND meta_key=%s AND BINARY meta_value=%s", $raw, $row['meta_id'], $id, self::META, $row['meta_value'] ) );
		} else {
			if ( null === $raw ) {
				return; }
			$result = $wpdb->insert(
				$wpdb->postmeta,
				array(
					'post_id'    => $id,
					'meta_key'   => self::META,
					'meta_value' => $raw,
				),
				array( '%d', '%s', '%s' )
			);
		}
		if ( 1 !== $result ) {
			self::fail( __( 'The field changed elsewhere. Nothing from this save was applied.', 'woocommerce-jetpack' ), 409 ); }
	}
	/**
	 * Invalidate affected native configuration caches after transaction outcome.
	 *
	 * @param mixed $id Id.
	 * @return void
	 */
	private static function clear_cache( $id ) {
		wp_cache_delete( $id, 'post_meta' );
		foreach ( array( self::MODULE, self::LOCAL, self::receipt_key( $id ) ) as $key ) {
			wp_cache_delete( $key, 'options' ); }
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		$core = WCJ_Job_Store::core();
		if ( $core && isset( $core->options ) ) {
			unset( $core->options[ self::MODULE ], $core->options[ self::LOCAL ] ); }
	}
	/**
	 * Apply or undo one exact guarded native change atomically.
	 *
	 * @param mixed $id Id.
	 * @param mixed $token Token.
	 * @param mixed $draft Draft.
	 * @param mixed $undo Undo.
	 * @return mixed
	 */
	public static function mutate( $id, $token, $draft, $undo = false ) {
		global $wpdb;
		self::authorize( $id );
		self::transactional();
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			self::fail( __( 'Could not start a safe save.', 'woocommerce-jetpack' ) ); }
		try {
			$locked = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_type,post_status FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE", $id ), ARRAY_A );
			if ( ! $locked || 'product' !== $locked['post_type'] || in_array( $locked['post_status'], array( 'trash', 'auto-draft' ), true ) ) {
				self::fail( __( 'The selected product is no longer editable.', 'woocommerce-jetpack' ), 409 ); }
			wp_cache_delete( $id, 'posts' );
			self::authorize( $id );
			$c = self::context( $id, true );
			$prior = self::receipt_value( $c['receipt'] );
			$broad = $undo ? ( is_array( $prior ) && ! empty( $prior['undo']['options'] ) ) : ( ( ! empty( $draft['module'] ) && 'yes' !== self::option( $c, self::MODULE ) ) || ( ! empty( $draft['local'] ) && 'yes' !== self::option( $c, self::LOCAL ) ) );
			$parts = is_string( $token ) ? explode( '.', $token ) : array();
			if ( count( $parts ) !== 2 || ! hash_equals( self::token_part( $id, $c, $broad ), $parts[ $broad ? 1 : 0 ] ) ) {
				self::fail( __( 'Changed elsewhere since you opened this. Your draft is kept. Reload current before deciding what to do.', 'woocommerce-jetpack' ), 409 ); }
			$prior = self::receipt_value( $c['receipt'] );
			if ( $undo ) {
				if ( ! is_array( $prior ) || empty( $prior['undo'] ) || (int) $prior['user'] !== get_current_user_id() ) {
					self::fail( __( 'No matching saved change is available to undo.', 'woocommerce-jetpack' ) ); }
				$u     = $prior['undo'];
				$guard = self::scoped_context( $c, $broad );
				unset( $guard['receipt'] );
				if ( ! hash_equals( $u['guard'], hash( 'sha256', serialize( $guard ) ) ) ) {
					self::fail( __( 'Settings changed after this save. Undo will not overwrite newer fields or activation. Use full settings.', 'woocommerce-jetpack' ), 409 ); }
				self::meta_write( $id, $c['selected'], $u['raw'] );
				foreach ( $u['options'] as $key => $before ) {
					if ( ! WCJ_Job_Store::compare_exchange( $key, self::option_state( $c, $key ), $before ) ) {
						self::fail( __( 'Activation changed elsewhere. Undo was not applied.', 'woocommerce-jetpack' ), 409 ); }
				}
				$next = array(
					'ownership' => $u['ownership'],
					'user'      => get_current_user_id(),
				);
			} else {
				$i = self::inspect( $id, $c );
				if ( $i['reason'] ) {
					self::fail( $i['reason'] ); }
				foreach ( array( 'title', 'placeholder' ) as $key ) {
					if ( ! isset( $draft[ $key ] ) || ! self::plain( $draft[ $key ] ) || ( 'title' === $key && '' === trim( $draft[ $key ] ) ) ) {
						self::fail( __( 'Use plain text for the question and hint, up to 200 bytes, without markup or shortcodes.', 'woocommerce-jetpack' ) ); }
				}
				if ( ! isset( $draft['type'], $draft['maxlength'], $draft['required'] ) || ! in_array( $draft['type'], array( 'text', 'textarea' ), true ) || ! is_bool( $draft['required'] ) || ! is_int( $draft['maxlength'] ) || $draft['maxlength'] < 0 || $draft['maxlength'] > 10000 ) {
					self::fail( __( 'Use a short or long text answer and a valid character limit.', 'woocommerce-jetpack' ) ); }
				$before_options = array();
				foreach ( array(
					'module' => self::MODULE,
					'local'  => self::LOCAL,
				) as $flag => $key ) {
					if ( ! empty( $draft[ $flag ] ) && 'yes' !== self::option( $c, $key ) ) {
						if ( true !== ( $draft['ack'] ?? false ) ) {
							self::fail( __( 'Confirm the reach of activation before saving.', 'woocommerce-jetpack' ) ); }
						$before_options[ $key ] = self::option_state( $c, $key );
					}
				}
				$data = $i['data'];
				$slot = $i['slot'];
				if ( ! $i['edit'] ) {
					$data['local_total_number']       = (string) $slot;
					$data[ 'enabled_local_' . $slot ] = 'yes';
					$data[ 'required_message_local_' . $slot ] = __( 'Please answer this product question.', 'woocommerce-jetpack' );
					$data[ 'order_local_' . $slot ]   = '0'; }
				foreach ( array( 'title', 'type', 'placeholder', 'maxlength' ) as $key ) {
					$data[ $key . '_local_' . $slot ] = (string) $draft[ $key ]; }
				$data[ 'required_local_' . $slot ] = $draft['required'] ? 'yes' : 'no';
				$raw                               = maybe_serialize( $data );
				if ( $raw === $i['raw'] && ! $before_options ) {
					self::fail( __( 'These values are already saved.', 'woocommerce-jetpack' ) ); }
				self::meta_write( $id, $c['selected'], $raw );
				foreach ( $before_options as $key => $before ) {
					if ( ! WCJ_Job_Store::compare_exchange( $key, $before, WCJ_Job_Store::value_state( 'yes' ) ) ) {
						self::fail( __( 'Activation changed elsewhere. Nothing from this save was applied.', 'woocommerce-jetpack' ), 409 ); }
				}
				$after = self::scoped_context( self::context( $id ), ! empty( $before_options ) );
				unset( $after['receipt'] );
				$changes = array();
				if ( ! $i['edit'] ) {
					$changes[] = __( 'Added one product question; existing fields were kept.', 'woocommerce-jetpack' ); }
				$labels = array(
					'title'       => __( 'Question', 'woocommerce-jetpack' ),
					'type'        => __( 'Answer type', 'woocommerce-jetpack' ),
					'placeholder' => __( 'Hint', 'woocommerce-jetpack' ),
					'required'    => __( 'Required answer', 'woocommerce-jetpack' ),
					'maxlength'   => __( 'Browser character limit', 'woocommerce-jetpack' ),
				);
				foreach ( $labels as $key => $label ) {
					$old = $i['data'][ $key . '_local_' . $slot ] ?? '';
					$new = $data[ $key . '_local_' . $slot ];
					if ( (string) $old !== (string) $new ) {
						$changes[] = $label . ': ' . ( '' === (string) $old ? __( 'Not set', 'woocommerce-jetpack' ) : $old ) . ' -> ' . ( '' === (string) $new ? __( 'Not set', 'woocommerce-jetpack' ) : $new ); }
				}
				foreach ( $before_options as $key => $before ) {
					$changes[] = self::MODULE === $key ? __( 'Product Input Fields turned on, including existing enabled local/global questions.', 'woocommerce-jetpack' ) : __( 'Per-product questions turned on, including existing questions on other products when the module is on.', 'woocommerce-jetpack' ); }
				$next = array(
					'changes'   => $changes,
					'user'      => get_current_user_id(),
					'ownership' => array(
						'slot' => $slot,
						'raw'  => $raw,
					),
					'undo'      => array(
						'raw'       => $i['raw'],
						'ownership' => is_array( $prior ) && isset( $prior['ownership'] ) ? $prior['ownership'] : null,
						'options'   => $before_options,
						'guard'     => hash( 'sha256', serialize( $after ) ),
					),
				);
			}
			$key = self::receipt_key( $id );
			if ( ! WCJ_Job_Store::compare_exchange( $key, $c['receipt'], WCJ_Job_Store::value_state( $next ), array( $key ) ) ) {
				self::fail( __( 'The saved-change record changed elsewhere. Nothing was applied.', 'woocommerce-jetpack' ), 409 ); }
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				self::fail( __( 'The save could not be confirmed. Reload current before retrying.', 'woocommerce-jetpack' ), 500 ); }
		} catch ( Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );
			self::clear_cache( $id );
			throw $e;
		}
		self::clear_cache( $id );
		return self::load( $id );
	}
	public function handle() {
		try {
			self::authorize();
			$action = isset( $_POST['action'] ) && is_string( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
			$op     = str_replace( 'wcj_product_question_', '', $action );
			if ( ! in_array( $op, self::OPS, true ) || ! check_ajax_referer( 'wcj_product_question_' . $op, 'nonce', false ) ) {
				self::fail( __( 'Your security check expired. Reload this page.', 'woocommerce-jetpack' ) ); }
			if ( 'search' === $op ) {
				$q     = isset( $_POST['q'] ) && is_string( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
				$query = new WP_Query(
					array(
						'post_type'      => 'product',
						'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
						's'              => substr( $q, 0, 100 ),
						'posts_per_page' => 25,
						'no_found_rows'  => true,
					)
				);
				$rows  = array();
				foreach ( $query->posts as $p ) {
					if ( current_user_can( 'edit_post', $p->ID ) ) {
						$rows[] = array(
							'id'    => $p->ID,
							'title' => $p->post_title,
						);
					}
				} wp_send_json_success( $rows );
			}
			$id = isset( $_POST['product'] ) ? absint( $_POST['product'] ) : 0;
			if ( ! $id ) {
				self::fail( __( 'Choose a product first.', 'woocommerce-jetpack' ) ); }
			if ( 'load' === $op ) {
				wp_send_json_success( self::load( $id ) ); }
			$token = isset( $_POST['token'] ) && is_string( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
			$draft = isset( $_POST['draft'] ) && is_string( $_POST['draft'] ) ? json_decode( wp_unslash( $_POST['draft'] ), true ) : array();
			if ( ! is_array( $draft ) ) {
				self::fail( __( 'Invalid draft.', 'woocommerce-jetpack' ) ); }
			wp_send_json_success( self::mutate( $id, $token, $draft, 'undo' === $op ) );
		} catch ( Throwable $e ) {
			$code = $e instanceof RuntimeException ? $e->getCode() : 500;
			$kind = 422 === $code ? 'validation' : ( 409 === $code ? 'conflict' : 'uncertain' );
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'kind'    => $kind,
				),
				422 === $code ? 422 : 409
			);
		}
	}
	public function render() {
		self::authorize();
		wp_enqueue_style( 'wcj-product-question-job', wcj_plugin_url() . '/assets/css/admin/wcj-product-question-job.css', array(), '1.0' );
		wp_enqueue_script( 'wcj-product-question-job', wcj_plugin_url() . '/assets/js/admin/wcj-product-question-job.js', array( 'wp-i18n' ), '1.1', true );
		$nonces = array();
		foreach ( self::OPS as $op ) {
			$nonces[ $op ] = wp_create_nonce( 'wcj_product_question_' . $op ); }
		wp_localize_script(
			'wcj-product-question-job',
			'wcjProductQuestion',
			array(
				'url'    => admin_url( 'admin-ajax.php' ),
				'nonces' => $nonces,
			)
		);
		include __DIR__ . '/views/product-question-job.php';
	}
}
new WCJ_Product_Question_Job();

<?php
/**
 * Guided edits of one native wording setting at a time. No storefront filters.
 *
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;

abstract class WCJ_Label_Job {
	const GROUP = 'wording';
	const OVERRIDE = 'wcj_checkout_place_order_button_override';

	public static function catalogue() {
		return array(
			'more_button_labels' => array( __( 'Change Place order text', 'woocommerce-jetpack' ), __( 'Place order button text', 'woocommerce-jetpack' ), 'place-order-label', 'WCJ_Place_Order_Label_Job', 'woocommerce-more-button-labels', __( 'rename checkout wording confirm order purchase pay button gateway', 'woocommerce-jetpack' ) ),
			'add_to_cart' => array( __( 'Change Add to cart text', 'woocommerce-jetpack' ), __( 'Add to cart button text', 'woocommerce-jetpack' ), 'add-to-cart-label', 'WCJ_Add_To_Cart_Label_Job', 'woocommerce-add-to-cart-labels', __( 'rename purchase button catalog shop archive single product', 'woocommerce-jetpack' ) ),
			'price_labels' => array( __( 'Change price label', 'woocommerce-jetpack' ), __( 'Text beside the price', 'woocommerce-jetpack' ), 'price-label', 'WCJ_Price_Affix_Job', 'woocommerce-custom-price-labels', __( 'add wording before after amount price suffix prefix', 'woocommerce-jetpack' ) ),
			'free_price' => array( __( 'Show text for free products', 'woocommerce-jetpack' ), __( 'Text for zero-price products', 'woocommerce-jetpack' ), 'free-price-label', 'WCJ_Free_Price_Label_Job', 'woocommerce-free-price-labels', __( 'free zero price display label complimentary wording', 'woocommerce-jetpack' ) ),
		);
	}
	public static function summary( $id ) {
		$map = array(
			'more_button_labels' => __( 'Change the standard order button. Replacing a payment method’s own wording is a separate choice.', 'woocommerce-jetpack' ),
			'add_to_cart' => __( 'Choose one button by product type and location. Category and individual-product rules stay in full settings.', 'woocommerce-jetpack' ),
			'price_labels' => __( 'Put plain text beside a displayed price. Replacing or removing the price stays in full settings.', 'woocommerce-jetpack' ),
			'free_price' => __( 'Set wording for products already priced at zero. This does not change the amount charged.', 'woocommerce-jetpack' ),
		);
		return isset( $map[ $id ] ) ? $map[ $id ] : '';
	}
	public static function register() {
		foreach ( self::catalogue() as $t ) {
			$class = $t[3];
			if ( ! $class::allowed() ) { continue; }
			add_submenu_page( 'woocommerce', $t[1], $t[1], 'manage_woocommerce', 'wcj-' . $t[2] . '-job', array( $t[3], 'render' ) );
		}
	}
	public static function url( $field = '' ) {
		$t = self::catalogue()[static::ID];
		$url = admin_url( 'admin.php?page=wcj-' . $t[2] . '-job' );
		return $field ? add_query_arg( 'field', $field, $url ) : $url;
	}
	public static function native_url( $tab = '' ) {
		$url = admin_url( wcj_admin_tab_url() . '&wcj-cat=labels&section=' . static::ID );
		return $tab ? add_query_arg( 'active_tab', $tab, $url ) : $url;
	}
	public static function product_help( $id ) {
		$tasks = self::catalogue();
		if ( ! isset( $tasks[ $id ] ) || ! current_user_can( 'edit_post', get_the_ID() ) ||
			( function_exists( 'wcj_is_module_allowed_by_allowlist' ) && ! wcj_is_module_allowed_by_allowlist( $id ) ) ||
			( wcj_is_module_enabled( 'modules_by_user_roles' ) && ! wcj_check_modules_by_user_roles( $id ) ) ) { return; }
		$label = 'add_to_cart' === $id ? __( 'Help for per-product Add to cart wording (opens in a new tab)', 'woocommerce-jetpack' ) : __( 'Help for per-product price labels and visibility (opens in a new tab)', 'woocommerce-jetpack' );
		echo '<p><a target="_blank" rel="noopener noreferrer" href="' . esc_url( 'https://booster.io/docs/' . $tasks[ $id ][4] . '/#booster-v10-' . ( 'add_to_cart' === $id ? 'cart-product' : 'price-product' ) ) . '">' . esc_html( $label ) . '</a></p>';
	}
	public static function specialist_help( $id, $items ) {
		$tasks = self::catalogue();
		if ( ! isset( $tasks[ $id ] ) || ! $tasks[ $id ][3]::allowed() ) { return ''; }
		$links = array();
		foreach ( $items as $fragment => $label ) {
			$links[] = '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( 'https://booster.io/docs/' . $tasks[ $id ][4] . '/#booster-v10-' . $fragment ) . '">' . esc_html( $label ) . ' <span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'woocommerce-jetpack' ) . '</span></a>';
		}
		return ' ' . implode( ' &middot; ', $links );
	}
	private static function can_use_paid() {
		return ! defined( 'WCJ_FREE_PLUGIN_PATH' ) && 'yes' === apply_filters( 'booster_option', 'no', 'yes' );
	}
	/** Only literal native keys generated here are accepted. A disabled choice is not a write entitlement. */
	public static function fields() {
		$a = array(); $paid = self::can_use_paid();
		switch ( static::ID ) {
			case 'more_button_labels':
				$a['wcj_checkout_place_order_button_text'] = array( __( 'Standard checkout button', 'woocommerce-jetpack' ), true );
				break;
			case 'price_labels':
				$a['wcj_global_price_labels_add_after_text'] = array( __( 'After the price', 'woocommerce-jetpack' ), true );
				$a['wcj_global_price_labels_add_before_text'] = array( __( 'Before the price', 'woocommerce-jetpack' ), $paid );
				break;
			case 'free_price':
				foreach ( array( 'simple' => 'Simple / custom', 'variable' => 'Variable', 'grouped' => 'Grouped', 'external' => 'External' ) as $type => $name ) {
					$views = array( 'single' => 'Single product', 'related' => 'Related products', 'home' => 'Homepage', 'page' => 'Pages / shortcodes', 'archive' => 'Archives' );
					if ( 'variable' === $type ) { $views['variation'] = 'Selected variation'; }
					foreach ( $views as $view => $label ) { $a[ 'wcj_free_price_' . $type . '_' . $view ] = array( $name . ': ' . $label, 'variable' !== $type || $paid ); }
				}
				break;
			case 'add_to_cart':
				foreach ( array( 'simple', 'variable', 'external', 'grouped', 'other' ) as $type ) {
					$states = array( 'single' => 'Single product', 'archives' => 'Archive' );
					if ( 'variable' !== $type ) { $states += array( 'archives_not_in_stock' => 'Archive / out of stock', 'single_sale' => 'Single / on sale', 'archives_sale' => 'Archive / on sale', 'single_zero_price' => 'Single / zero price', 'archives_zero_price' => 'Archive / zero price', 'archives_no_price' => 'Archive / no price' ); }
					if ( 'external' !== $type ) { $states += array( 'single_in_cart' => 'Single / already in cart', 'archives_in_cart' => 'Archive / already in cart' ); }
					foreach ( $states as $state => $label ) { $a[ 'wcj_add_to_cart_text_on_' . $state . '_' . $type ] = array( ucfirst( $type ) . ': ' . $label, true ); }
				}
				break;
		}
		return $a;
	}
	private static function allowed() {
		return current_user_can( 'manage_woocommerce' ) && ( ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( static::ID ) ) && ( ! wcj_is_module_enabled( 'modules_by_user_roles' ) || wcj_check_modules_by_user_roles( static::ID ) );
	}
	private static function channel( $k ) { return self::OVERRIDE === $k ? 'gateway' : 'wording'; }
	private static function journal_key( $k ) { return 'booster_label_v10_' . static::ID . '_' . self::channel( $k ); }
	private static function read( $key ) { return WCJ_Job_Store::read_state( $key ); }
	private static function lock_reason( $k, $state ) {
		if ( has_filter( 'pre_option_' . $k ) || has_filter( 'option_' . $k ) || has_filter( $k ) ) { return __( 'A site filter owns this wording. Inspect it in full settings; no guided change is available.', 'woocommerce-jetpack' ); }
		if ( ! $state['exists'] || self::shipped_free_default( $state,$k ) ) { return ''; }
		if ( self::OVERRIDE === $k ) { return in_array( $state['raw'], array( 'yes', 'no' ), true ) ? '' : __( 'Nonstandard gateway setting. Use full settings.', 'woocommerce-jetpack' ); }
		return self::plain( $state['raw'] ) ? '' : __( 'Existing markup, shortcode, multiline or advanced text was kept. Use full settings to edit it.', 'woocommerce-jetpack' );
	}
	private static function shipped_free_default( $state,$k ) {
		return 'free_price' === static::ID && $state['exists'] && preg_match( '/^wcj_free_price_(simple|external)_/', $k ) && '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>' === $state['raw'];
	}
	private static function plain( $v ) {
		return is_string( $v ) && strlen( $v ) <= 500 && ! is_serialized( $v ) && false !== preg_match( '//u', $v ) && ! preg_match( '/[\x00-\x1F\x7F<>\[\]]/u', $v );
	}
	private static function guard_states( $k ) {
		$keys = array( $k, 'wcj_' . static::ID . '_enabled', self::journal_key( $k ) );
		if ( 'more_button_labels' === static::ID ) { $keys[] = self::OVERRIDE; $keys[] = 'wcj_checkout_place_order_button_text'; }
		if ( 'add_to_cart' === static::ID ) { $keys[]='wcj_add_to_cart_text_enabled'; }
		$states=array(); foreach ( array_unique( $keys ) as $key ) { $states[ $key ]=self::read( $key ); }
		return $states;
	}
	private static function guard( $k, $states = null ) {
		$states = null === $states ? self::guard_states( $k ) : $states;
		return hash_hmac( 'sha256', serialize( array( get_current_user_id(), static::ID, $k, $states, self::can_use_paid() ) ), wp_salt( 'nonce' ) );
	}
	private static function receipt( $k, $state = null ) {
		$r = WCJ_Job_Store::state_value( null === $state ? self::read( self::journal_key( $k ) ) : $state );
		if ( ! is_array( $r ) || ! isset( $r['v'], $r['user'], $r['field'], $r['before'], $r['after'] ) || 1 !== $r['v'] || get_current_user_id() !== $r['user'] || $r['field'] !== $k ) { return null; }
		return $r;
	}
	public static function resume() {
		if ( ! self::allowed() ) { return ''; }
		$r = get_option( self::journal_key( 'wording' ), null );
		if ( is_array( $r ) && isset( $r['field'], $r['user'], $r['after'] ) && get_current_user_id() === $r['user'] && ! empty( static::fields()[ $r['field'] ][1] ) && self::receipt( $r['field'] ) && self::read( $r['field'] ) === $r['after'] ) { return static::url( $r['field'] ); }
		return '';
	}
	private static function post( $name ) { return isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : ''; }
	/** Current actor, allowed key, permission, per-operation nonce and exact old state are all rechecked server-side. */
	public static function change( $field ) {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! self::allowed() ) { throw new RuntimeException( 'Ask an administrator for access.' ); }
		$op=self::post( 'wcj_label_op' ); $gateway=in_array( $op, array( 'save_gateway','undo_gateway' ), true );
		if ( ! in_array( $op, array( 'save_wording','undo_wording','save_gateway','undo_gateway' ), true ) || ! wp_verify_nonce( self::post( '_label_nonce' ), 'wcj_label_' . static::ID . '_' . $field . '_' . $op ) ) { throw new RuntimeException( 'This form expired. Reload before saving.' ); }
		$fields=static::fields(); if ( ! isset( $fields[ $field ] ) || ! $fields[ $field ][1] || ( $gateway && 'more_button_labels' !== static::ID ) ) { throw new RuntimeException( 'This choice is not available in this edition.' ); }
		$k=$gateway ? self::OVERRIDE : $field;
		$lockkey='booster_label_v10_' . static::ID . '_lock'; $lock=WCJ_Job_Store::lock( $lockkey, 'Another label edit is running. Reload.' );
		try {
			// Keep the same option and receipt preimages for authorization and compare-exchange. Never rebase on a native write made between those steps.
			$states = self::guard_states( $k );
			if ( ! hash_equals( self::guard( $k, $states ), self::post( 'label_guard' ) ) ) { throw new RuntimeException( 'Another value or receipt changed. Reload; the newer settings were kept.' ); }
			$old=$states[ $k ]; $why=self::lock_reason( $k, $old ); if ( $why ) { throw new RuntimeException( $why ); }
			$j=self::journal_key( $k ); $prior=$states[ $j ]; $blank=array( 'exists'=>false,'raw'=>'' );
			if ( 0 === strpos( $op, 'undo_' ) ) {
				$r=self::receipt( $k, $prior ); if ( ! $r || $old !== $r['after'] ) { throw new RuntimeException( 'This last change cannot be undone here. The current value was kept.' ); }
				if ( ! WCJ_Job_Store::compare_exchange( $k, $old, $r['before'] ) || self::read( $k ) !== $r['before'] ) { throw new RuntimeException( 'The original value was not verified. Check full settings.' ); }
				if ( ! WCJ_Job_Store::compare_exchange( $j,$prior,$blank ) ) { throw new RuntimeException( 'Value restored; the receipt changed. Check current settings.' ); }
				return 'Previous native value restored and read back.';
			}
			$v=self::post( 'label_value' );
			if ( ! isset( $_POST['label_value'] ) || ! is_string( $_POST['label_value'] ) ) { throw new RuntimeException( 'A single value is required.' ); }
			if ( $gateway && ( 'yes' !== self::post( 'confirm_gateway' ) || ! in_array( $v,array('yes','no'),true ) ) ) { throw new RuntimeException( 'Confirm this separate gateway change first.' ); }
			if ( ! $gateway && 'price_labels' !== static::ID && '' !== $v && ! preg_match( '/[^\s\p{Z}\p{Cf}]/u', $v ) ) { throw new RuntimeException( 'Enter visible wording, or clear the whole field to restore the default.' ); }
			if ( ! $gateway && ! self::plain( $v ) ) { throw new RuntimeException( 'Use plain, single-line text up to 500 bytes. Markup and shortcodes belong in full settings.' ); }
			$new=( ! $gateway && '' === $v && 'free_price' === static::ID ) ? $blank : WCJ_Job_Store::value_state( $v );
			if ( $old === $new || ( ! $old['exists'] && ! $gateway && '' === $v ) || ( $gateway && ! $old['exists'] && 'no' === $v ) ) { return 'No change needed.'; }
			$receipt=WCJ_Job_Store::value_state( array( 'v'=>1,'user'=>get_current_user_id(),'field'=>$k,'before'=>$old,'after'=>$new ) );
			if ( ! WCJ_Job_Store::compare_exchange( $j,$prior,$receipt,array($j) ) ) { throw new RuntimeException( 'Another change owns the receipt. Nothing was applied.' ); }
			if ( ! WCJ_Job_Store::compare_exchange( $k,$old,$new ) ) {
				WCJ_Job_Store::compare_exchange( $j,$receipt,$prior,array($j) ); throw new RuntimeException( 'The value changed before saving. Reload.' );
			}
			if ( self::read( $k ) !== $new || self::read( $j ) !== $receipt ) { throw new RuntimeException( 'Saved readback did not match. Review the current value before making another edit.' ); }
			return 'Saved and read back from the native setting.';
		} finally { WCJ_Job_Store::unlock( $lockkey,$lock ); }
	}
	private static function form_state( $field, $op, $k, $states ) {
		echo '<input type="hidden" name="_label_nonce" value="' . esc_attr( wp_create_nonce( 'wcj_label_' . static::ID . '_' . $field . '_' . $op ) ) . '">';
		echo '<input type="hidden" name="wcj_label_op" value="' . esc_attr( $op ) . '"><input type="hidden" name="label_guard" value="' . esc_attr( self::guard( $k, $states ) ) . '">';
	}
	private static function display( $state, $k ) {
		if ( self::OVERRIDE === $k ) { return $state['exists'] && 'yes' === $state['raw'] ? 'Replace gateway-specific text' : 'Keep gateway-specific text'; }
		if ( ( ! $state['exists'] || self::shipped_free_default( $state,$k ) ) && 'free_price' === static::ID ) { return __( 'Free!', 'woocommerce' ) . ' (module default)'; }
		if ( ! $state['exists'] || '' === $state['raw'] ) {
			if ( 'more_button_labels' === static::ID ) { return __( 'Place order', 'woocommerce' ) . ' (default or gateway wording)'; }
			return 'free_price' === static::ID ? 'Empty native label' : 'No override (inherited)';
		}
		return $state['raw'];
	}
	private static function history( $field, $k, $states ) {
		$r=self::receipt( $k, $states[ self::journal_key( $k ) ] );if ( ! $r ) { return; } $current=$states[ $k ];
		echo '<section class="wcj-label-history"><h3>' . esc_html__( 'Your last saved change', 'woocommerce-jetpack' ) . '</h3><p>Was: <strong>' . esc_html( self::display( $r['before'],$k ) ) . '</strong><br>Now saved: <strong>' . esc_html( self::display( $current,$k ) ) . '</strong></p>';
		if ( $current === $r['after'] ) {
			echo '<form method="post" action="' . esc_url( static::url( $field ) ) . '">';self::form_state( $field, self::OVERRIDE === $k ? 'undo_gateway':'undo_wording',$k,$states );
			echo '<button class="button" type="submit">' . esc_html( self::OVERRIDE === $k ? 'Undo gateway change' : 'Undo wording change' ) . '</button></form>';
		} else { echo '<p>The native value changed elsewhere. Undo is unavailable.</p>'; }
		echo '</section>';
	}
	public static function render() {
		if ( ! self::allowed() ) { wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ),'',array('response'=>403) ); }
		$fields=static::fields();$field=isset($_GET['field']) && is_string($_GET['field']) ? sanitize_key(wp_unslash($_GET['field'])) : array_key_first($fields);
		if ( ! isset($fields[$field]) ) { wp_die( esc_html__( 'Unknown label setting.', 'woocommerce-jetpack' ),'',array('response'=>400) ); }
		$t=self::catalogue()[static::ID];$message='';$error=false;
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { try { $message=static::change($field); } catch ( RuntimeException $e ) { $message=$e->getMessage();$error=true; } }
		$wording_state=self::guard_states($field); $current=$wording_state[$field]; $locked=!$fields[$field][1]?'This label is not editable in this edition. The native setting is unchanged.':self::lock_reason($field,$current);
		$on=wcj_is_module_enabled(static::ID);wp_enqueue_script('wcj-label-job',wcj_plugin_url().'/assets/js/admin/wcj-label-job.js',array(),'1.1',true);
		include __DIR__.'/views/label-job.php';
	}
}
final class WCJ_Place_Order_Label_Job extends WCJ_Label_Job { const ID='more_button_labels'; }
final class WCJ_Add_To_Cart_Label_Job extends WCJ_Label_Job { const ID='add_to_cart'; }
final class WCJ_Price_Affix_Job extends WCJ_Label_Job { const ID='price_labels'; }
final class WCJ_Free_Price_Label_Job extends WCJ_Label_Job { const ID='free_price'; }
add_action( 'admin_menu', array( 'WCJ_Label_Job','register' ), 99 );


add_action( 'admin_enqueue_scripts', static function () {
	$screen = get_current_screen();
	if ( ! $screen ) { return; }
	foreach ( WCJ_Label_Job::catalogue() as $task ) {
		if ( 'woocommerce_page_wcj-' . $task[2] . '-job' === $screen->id ) { wp_enqueue_style( 'wcj-label-job', wcj_plugin_url() . '/assets/css/admin/wcj-label-job.css', array(),'1.1' ); break; }
	}
} );

<?php
/**
 * Synthetic CLI fixture only. Never loaded in a WordPress request.
 *
 * @package Booster_For_WooCommerce/tools
 */

/** In-memory conditional storage substitute, deliberately not database evidence. */
class WCJ_Job_Store {
	/** In-memory option state.
	 *
	 * @var mixed
	 */
	public static $rows = array();
	/** Option whose conditional write fails.
	 *
	 * @var mixed
	 */
	public static $fail_key = null;
	/** Option changed concurrently at failure.
	 *
	 * @var mixed
	 */
	public static $later_edit = null;
	/** Synthetic lock state.
	 *
	 * @var mixed
	 */
	public static $held = false;
	/**
	 * Synthetic core helper.
	 *
	 * @return mixed
	 */
	public static function core() {
		return null; }
	/**
	 * Synthetic read state fixture helper.
	 *
	 * @param mixed $key Synthetic key input.
	 * @return mixed
	 */
	public static function read_state( $key ) {
		return self::$rows[ $key ] ?? array(
			'exists' => false,
			'raw'    => '',
		); }
	/**
	 * Synthetic state value fixture helper.
	 *
	 * @param mixed $state Synthetic state input.
	 * @return mixed
	 */
	public static function state_value( $state ) {
		return $state['exists'] ? maybe_unserialize( $state['raw'] ) : null; }
	/**
	 * Synthetic value state fixture helper.
	 *
	 * @param mixed $value Synthetic value input.
	 * @return mixed
	 */
	public static function value_state( $value ) {
		return array(
			'exists' => true,
			'raw'    => (string) maybe_serialize( $value ),
		); }
	/**
	 * Synthetic state hash fixture helper.
	 *
	 * @param mixed $state Synthetic state input.
	 * @return mixed
	 */
	public static function state_hash( $state ) {
		return hash( 'sha256', ( $state['exists'] ? '1' : '0' ) . "\0" . $state['raw'] ); }
	/**
	 * Synthetic compare exchange fixture helper.
	 *
	 * @param mixed $key Synthetic key input.
	 * @param mixed $before Synthetic before input.
	 * @param mixed $after Synthetic after input.
	 * @return mixed
	 */
	public static function compare_exchange( $key, $before, $after ) {
		if ( $key === self::$fail_key ) {
			if ( self::$later_edit ) {
				self::$rows[ self::$later_edit ] = self::value_state( '#abcdef' ); }
			return false;
		}
		if ( self::read_state( $key ) !== $before ) {
			return false; }
		if ( $after['exists'] ) {
			self::$rows[ $key ] = $after;
		} else {
			unset( self::$rows[ $key ] ); }
		return true;
	}
	/**
	 * Synthetic lock fixture helper.
	 *
	 * @param mixed $key Synthetic key input.
	 * @param mixed $message Synthetic message input.
	 * @return mixed
	 * @throws RuntimeException When the synthetic assertion or boundary fails.
	 */
	public static function lock( $key, $message ) {
		if ( ! is_string( $key ) ) {
			throw new RuntimeException( 'Invalid fixture lock key.' ); }
		if ( self::$held ) {
			throw new RuntimeException( esc_html( $message ) );
		} self::$held = true;
		return array(); }
	/**
	 * Synthetic unlock fixture helper.
	 *
	 * @param mixed $key Synthetic key input.
	 * @param mixed $mine Synthetic mine input.
	 * @return mixed
	 * @throws RuntimeException When the synthetic assertion or boundary fails.
	 */
	public static function unlock( $key, $mine ) {
		if ( ! is_string( $key ) || ! is_array( $mine ) ) {
			throw new RuntimeException( 'Invalid fixture lock.' ); }
		self::$held = false; }
}

<?php
/**
 * Typed invoice presentation proposal contract for the first AI-01 slice.
 *
 * Not registered as an Ability or route. This contract does not authorize,
 * persist, approve or execute a proposal. Connection and WordPress approval
 * controllers must supply those boundaries before this task can be exposed.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/** A closed, versioned task contract, not an arbitrary options writer. */
final class WCJ_AI_Invoice_Presentation {

	/** Stable task identifier. */
	const TASK = 'invoice_presentation';

	/** Explicit schema version; unknown future versions are denied. */
	const VERSION = 1;

	/** Colour-only first vertical slice. Other presentation fields are not yet supported. */
	const FIELDS = array( 'header_text_color', 'header_line_color', 'footer_text_color', 'footer_line_color' );

	/**
	 * Schema suitable for an Abilities API input contract after connection review.
	 *
	 * @return array
	 */
	public static function input_schema() {
		$changes = array();
		foreach ( self::FIELDS as $field ) {
			$changes[ $field ] = array(
				'type'      => 'string',
				'pattern'   => '^#[0-9a-fA-F]{6}$',
				'minLength' => 7,
				'maxLength' => 7,
			);
		}
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'schema_version', 'task', 'changes' ),
			'properties'           => array(
				'schema_version' => array(
					'type' => 'integer',
					'enum' => array( self::VERSION ),
				),
				'task'           => array(
					'type' => 'string',
					'enum' => array( self::TASK ),
				),
				'changes'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'minProperties'        => 1,
					'maxProperties'        => count( self::FIELDS ),
					'properties'           => $changes,
				),
			),
		);
	}

	/**
	 * Validate again at the service boundary; do not rely on adapter validation.
	 *
	 * The Lite task includes all four supported colours. The remote payload cannot choose
	 * an edition, actor, site, expiry, base state or approval status.
	 * Canonical order and lowercase colours make request comparison deterministic.
	 *
	 * @param mixed $input Decoded, bounded request object as an associative array.
	 * @return array Validated changes in canonical order.
	 * @throws InvalidArgumentException On any unsupported key, shape or value.
	 */
	public static function validate( $input ) {
		if ( ! is_array( $input ) || count( $input ) !== 3 ||
			array_diff( array_keys( $input ), array( 'schema_version', 'task', 'changes' ) ) ||
			! isset( $input['schema_version'], $input['task'], $input['changes'] ) ||
			self::VERSION !== $input['schema_version'] || self::TASK !== $input['task'] ||
			! is_array( $input['changes'] ) || ! $input['changes'] || count( $input['changes'] ) > count( self::FIELDS ) ) {
			throw new InvalidArgumentException( 'Unsupported invoice presentation proposal.' );
		}
		foreach ( $input['changes'] as $field => $value ) {
			if ( ! is_string( $field ) || ! in_array( $field, self::FIELDS, true ) ||
				! is_string( $value ) || 7 !== strlen( $value ) || 1 !== preg_match( '/^#[0-9a-fA-F]{6}$/D', $value ) ) {
				throw new InvalidArgumentException( 'Unsupported invoice presentation field or colour.' );
			}
		}
		$changes = array();
		foreach ( self::FIELDS as $field ) {
			if ( array_key_exists( $field, $input['changes'] ) ) {
				$changes[ $field ] = strtolower( $input['changes'][ $field ] );
			}
		}
		return $changes;
	}

	/**
	 * Project an authorized native field snapshot into the narrow external view.
	 *
	 * Caller authorization is mandatory before reading the native snapshot. This
	 * pure projection must not be used to call the full admin load under an
	 * impersonated owner. It deliberately omits bases, receipts, template values,
	 * business identity and other native fields. A connection controller must bind
	 * a separate opaque context token to its server-side snapshot.
	 *
	 * @param array $fields Native field records, obtained by authorized code.
	 * @return array Narrow safe current colours, with unsupported states explicit.
	 */
	public static function project_context( array $fields ) {
		$out = array();
		foreach ( self::FIELDS as $field ) {
			$record        = isset( $fields[ $field ] ) && is_array( $fields[ $field ] ) ? $fields[ $field ] : array();
			$value         = $record['value'] ?? null;
			$safe          = array_key_exists( 'locked', $record ) && null === $record['locked'] &&
				is_string( $value ) && 7 === strlen( $value ) && 1 === preg_match( '/^#[0-9a-fA-F]{6}$/D', $value );
			$out[ $field ] = array(
				'status' => $safe ? 'supported' : 'unsupported',
				'value'  => $safe ? strtolower( $value ) : null,
			);
		}
		return array(
			'schema_version' => self::VERSION,
			'task'           => self::TASK,
			'fields'         => $out,
		);
	}
}

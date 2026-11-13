<?php
/**
 * Private read-only first-client tools through the external official MCP Adapter.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-invoice-presentation.php';

/** No generic executor, business writes, plan creation, or diagnostic export. */
final class WCJ_AI_Abilities {

	/** Public disclosure for the read-only stage. */
	const LIMITS = 'Internal read-only connection. Any future change requires a separate saved proposal and explicit local WordPress approval. Native authentication/session bookkeeping may write; business settings are not written.';
	/** Exact context meaning, never a prepared-plan basis. */
	const BASIS = 'Saved raw values only; no inferred defaults, live rendered result or proposal authority.';

	/**
	 * Build a closed object schema with every declared property required.
	 *
	 * @param array $properties Fixed properties.
	 * @return array Schema.
	 */
	private static function object_schema( array $properties ) {
		return array(
			'type'                 => 'object',
			'properties'           => $properties,
			'required'             => array_keys( $properties ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Distinct complete contracts for the two deliberately narrow outputs.
	 *
	 * @param string $name Fixed tool name.
	 * @return array Schema.
	 */
	public static function output_schema( $name ) {
		$properties = array(
			'schema_version' => array(
				'type' => 'integer',
				'enum' => array( 1 ),
			),
			'task'           => array(
				'type' => 'string',
				'enum' => array( 'invoice_presentation' ),
			),
		);
		if ( 'schema' === $name ) {
			$properties['proposal_schema'] = array(
				'type' => 'object',
				'enum' => array( WCJ_AI_Invoice_Presentation::input_schema() ),
			);
			$properties['available']       = array(
				'type' => 'array',
				'enum' => array( array( 'schema', 'saved_colour_context' ) ),
			);
			$properties['unavailable']     = array(
				'type' => 'array',
				'enum' => array( array( 'prepare', 'apply', 'preview_pdf', 'recovery', 'pricing_capture_export', 'financial_report_export' ) ),
			);
			$properties['limits']          = array(
				'type' => 'string',
				'enum' => array( self::LIMITS ),
			);
		} else {
			$fields = array();
			foreach ( WCJ_AI_Invoice_Presentation::FIELDS as $field ) {
				$fields[ $field ] = array(
					'oneOf' => array(
						self::object_schema(
							array(
								'status' => array(
									'type' => 'string',
									'enum' => array( 'supported' ),
								),
								'value'  => array(
									'type'      => 'string',
									'pattern'   => '^#[a-f0-9]{6}$',
									'minLength' => 7,
									'maxLength' => 7,
								),
							)
						),
						self::object_schema(
							array(
								'status' => array(
									'type' => 'string',
									'enum' => array( 'missing', 'unavailable' ),
								),
								'value'  => array( 'type' => 'null' ),
							)
						),
					),
				);
			}
			$properties['fields'] = self::object_schema( $fields );
			$properties['basis']  = array(
				'type' => 'string',
				'enum' => array( self::BASIS ),
			);
		}
		return self::object_schema( $properties );
	}

	/** Register a private category only while explicitly enabled. */
	public static function category() {
		if ( WCJ_AI_Connection::enabled() && function_exists( 'wp_register_ability_category' ) ) {
			wp_register_ability_category(
				'booster-ai-preview',
				array(
					'label'       => 'Booster preview',
					'description' => 'Private, limited internal connection.',
				)
			);
		}
	}

	/** Register exactly two closed-input read abilities. */
	public static function register() {
		if ( ! WCJ_AI_Connection::enabled() || ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		foreach ( array(
			'schema'  => 'Describe the limited Booster invoice colour task. No remote preparation or execution is available.',
			'context' => 'Read four saved invoice colours only. No identity, orders, PDF, pricing capture or financial report is exported.',
		) as $name => $description ) {
			wp_register_ability(
				'booster-ai/' . $name,
				array(
					'label'               => 'Booster ' . $name,
					'description'         => $description,
					'category'            => 'booster-ai-preview',
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => new stdClass(),
						'additionalProperties' => false,
					),
					'output_schema'       => self::output_schema( $name ),
					'permission_callback' => array( 'WCJ_AI_Connection', 'allowed' ),
					'execute_callback'    => array( __CLASS__, $name ),
					'meta'                => array(
						'public'      => false,
						'mcp'         => array(
							'public' => false,
							'type'   => 'tool',
						),
						'annotations' => array(
							'readonly'    => true,
							'destructive' => false,
							'idempotent'  => true,
						),
					),
				)
			);
		}
	}

	/**
	 * Server.
	 *
	 * @param object $adapter Official adapter. No bundled protocol implementation.
	 */
	public static function server( $adapter ) {
		if ( WCJ_AI_Connection::enabled() ) {
			$adapter->create_server( 'booster-ai-preview', 'booster-ai/v1', 'mcp', 'Booster private preview', 'Two read-only tools. Remote proposals, writes and shared diagnostics are not available.', '0.1.0', array( '\WP\MCP\Transport\HttpTransport' ), null, null, array( 'booster-ai/schema', 'booster-ai/context' ), array(), array(), array( 'WCJ_AI_Connection', 'allowed' ) );
		}
	}

	/**
	 * Enforce the private empty-input contract.
	 *
	 * @param mixed $input Empty input only.
	 * @return bool Guard direct calls as well as registered abilities.
	 */
	private static function admitted( $input ) {
		return ( array() === $input || ( $input instanceof stdClass && ! get_object_vars( $input ) ) ) && WCJ_AI_Connection::allowed();
	}

	/**
	 * Schema.
	 *
	 * @param mixed $input Closed empty input.
	 * @return array|WP_Error Fixed capabilities and limits.
	 */
	public static function schema( $input = array() ) {
		if ( ! self::admitted( $input ) ) {
			return new WP_Error( 'wcj_ai_unavailable', 'Connection unavailable.' );
		}
		return array(
			'schema_version'  => 1,
			'task'            => 'invoice_presentation',
			'proposal_schema' => WCJ_AI_Invoice_Presentation::input_schema(),
			'available'       => array( 'schema', 'saved_colour_context' ),
			'unavailable'     => array( 'prepare', 'apply', 'preview_pdf', 'recovery', 'pricing_capture_export', 'financial_report_export' ),
			'limits'          => self::LIMITS,
		);
	}

	/**
	 * Context.
	 *
	 * @param mixed $input Closed empty input.
	 * @return array|WP_Error Minimal saved colour state, not an approval or execution basis.
	 */
	public static function context( $input = array() ) {
		if ( ! self::admitted( $input ) ) {
			return new WP_Error( 'wcj_ai_unavailable', 'Connection unavailable.' );
		}
		$fields = array();
		foreach ( WCJ_AI_Invoice_Presentation::FIELDS as $field ) {
			$raw              = WCJ_AI_Connection::raw( 'wcj_invoicing_invoice_' . $field );
			$supported        = $raw && $raw['exists'] && is_string( $raw['raw'] ) && 1 === preg_match( '/^#[0-9a-fA-F]{6}$/D', $raw['raw'] );
			$fields[ $field ] = array(
				'status' => $supported ? 'supported' : ( $raw && ! $raw['exists'] ? 'missing' : 'unavailable' ),
				'value'  => $supported ? strtolower( $raw['raw'] ) : null,
			);
		}
		if ( ! WCJ_AI_Connection::allowed() ) {
			return new WP_Error( 'wcj_ai_unavailable', 'Connection unavailable.' );
		}
		return array(
			'schema_version' => 1,
			'task'           => 'invoice_presentation',
			'fields'         => $fields,
			'basis'          => self::BASIS,
		);
	}
}

add_action( 'wp_abilities_api_categories_init', array( 'WCJ_AI_Abilities', 'category' ) );
add_action( 'wp_abilities_api_init', array( 'WCJ_AI_Abilities', 'register' ) );
add_action( 'mcp_adapter_init', array( 'WCJ_AI_Abilities', 'server' ) );

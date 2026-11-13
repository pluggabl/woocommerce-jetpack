<?php
/**
 * Private typed tools through the external official MCP Adapter.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-invoice-presentation.php';

/** No generic executor, remote business execution or diagnostic export. */
final class WCJ_AI_Abilities {

	/** Precise distinction between pending proposals and business execution. */
	const LIMITS = 'Private preview. Reading saved colours does not authorize preparation. The separate invoice:prepare scope may save a bounded pending proposal, never apply settings. A native WordPress owner must review and deliberately approve locally. Authentication, gate and proposal bookkeeping may write. No remote apply, recovery, PDF or diagnostic export.';
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
	 * Typed proposal plus explicit bounded idempotency key; no caller authority.
	 *
	 * @return array Closed preparation schema.
	 */
	public static function prepare_schema() {
		$schema                                  = WCJ_AI_Invoice_Presentation::input_schema();
		$schema['properties']['idempotency_key'] = array(
			'type'        => 'string',
			'minLength'   => 16,
			'maxLength'   => 64,
			'pattern'     => '^[A-Za-z0-9_-]{16,64}$',
			'description' => 'Generate one stable unique key per intended proposal. Reuse exactly the same key and payload for an uncertain retry; never generate a replacement key to retry an unknown result.',
		);
		$schema['required'][]                    = 'idempotency_key';
		return $schema;
	}

	/**
	 * Distinct complete contracts for the two deliberately narrow outputs.
	 *
	 * @param string $name Fixed tool name.
	 * @return array Schema.
	 */
	public static function output_schema( $name ) {
		if ( 'prepare' === $name ) {
			return self::object_schema(
				array(
					'schema_version'          => array(
						'type' => 'integer',
						'enum' => array( 1 ),
					),
					'plan_id'                 => array(
						'type'      => 'string',
						'pattern'   => '^[a-f0-9]{32}$',
						'minLength' => 32,
						'maxLength' => 32,
					),
					'status'                  => array(
						'type' => 'string',
						'enum' => array( 'pending', 'applying', 'applied', 'failed', 'partial', 'stale', 'cancelled', 'recovering', 'recovered', 'recovery_partial' ),
					),
					'expires_at'              => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
					'requires_local_approval' => array(
						'type' => 'boolean',
						'enum' => array( true ),
					),
					'non_atomic'              => array(
						'type' => 'boolean',
						'enum' => array( true ),
					),
				)
			);
		}
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
			$properties['schema_version']['enum'] = array( 2 );
			$properties['proposal_schema']        = array(
				'type' => 'object',
				'enum' => array( WCJ_AI_Invoice_Presentation::input_schema() ),
			);
			$properties['available']              = array(
				'type' => 'array',
				'enum' => array( array( 'schema', 'saved_colour_context' ), array( 'schema', 'saved_colour_context', 'prepare' ) ),
			);
			$properties['unavailable']            = array(
				'type' => 'array',
				'enum' => array( array( 'prepare', 'apply', 'preview_pdf', 'recovery', 'pricing_capture_export', 'financial_report_export' ), array( 'apply', 'preview_pdf', 'recovery', 'pricing_capture_export', 'financial_report_export' ) ),
			);
			$properties['prepare_schema']         = array(
				'type' => 'object',
				'enum' => array( self::prepare_schema() ),
			);
			$properties['limits']                 = array(
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

	/** Register two read abilities and one separately authorized typed preparation. */
	public static function register() {
		if ( ! WCJ_AI_Connection::enabled() || ! function_exists( 'wp_register_ability' ) || ! class_exists( 'WP_Ability' ) ) {
			return;
		}
		require_once __DIR__ . '/class-wcj-ai-prepare-ability.php';
		foreach ( array(
			'schema'  => 'Describe the limited Booster invoice colour task and whether this grant authorizes preparing a pending proposal. No remote execution is available.',
			'context' => 'Read four saved invoice colours only. No identity, orders, PDF, pricing capture or financial report is exported.',
			'prepare' => 'Save a typed pending invoice-colour proposal with a stable idempotency key. Requires explicit invoice:prepare scope; does not apply settings. Give the owner the plan ID to open in WooCommerce > Booster AI preview > Open saved proposal. The owner reviews/previews and approves in WordPress. Do not treat a pending proposal as an applied change.',
		) as $name => $description ) {
			wp_register_ability(
				'booster-ai/' . $name,
				array(
					'ability_class'       => 'prepare' === $name ? 'WCJ_AI_Prepare_Ability' : 'WP_Ability',
					'label'               => 'Booster ' . $name,
					'description'         => $description,
					'category'            => 'booster-ai-preview',
					'input_schema'        => 'prepare' === $name ? self::prepare_schema() : array(
						'type'                 => 'object',
						'properties'           => new stdClass(),
						'additionalProperties' => false,
					),
					'output_schema'       => self::output_schema( $name ),
					'permission_callback' => 'prepare' === $name ? array( __CLASS__, 'prepare_allowed' ) : array( 'WCJ_AI_Connection', 'allowed' ),
					'execute_callback'    => array( __CLASS__, $name ),
					'meta'                => array(
						'public'      => false,
						'mcp'         => array(
							'public' => false,
							'type'   => 'tool',
						),
						'annotations' => array(
							'readonly'    => 'prepare' !== $name,
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
			$adapter->create_server( 'booster-ai-preview', 'booster-ai/v1', 'mcp', 'Booster private preview', 'Saved invoice colours and separately scoped pending proposals. Settings apply only through deliberate local WordPress owner approval. No remote execution or diagnostic export.', '0.2.0', array( '\WP\MCP\Transport\HttpTransport' ), null, null, array( 'booster-ai/schema', 'booster-ai/context', 'booster-ai/prepare' ), array(), array(), array( 'WCJ_AI_Connection', 'allowed' ) );
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
		$prepare = self::prepare_allowed();
		return array(
			'schema_version'  => 2,
			'task'            => 'invoice_presentation',
			'proposal_schema' => WCJ_AI_Invoice_Presentation::input_schema(),
			'available'       => $prepare ? array( 'schema', 'saved_colour_context', 'prepare' ) : array( 'schema', 'saved_colour_context' ),
			'unavailable'     => $prepare ? array( 'apply', 'preview_pdf', 'recovery', 'pricing_capture_export', 'financial_report_export' ) : array( 'prepare', 'apply', 'preview_pdf', 'recovery', 'pricing_capture_export', 'financial_report_export' ),
			'prepare_schema'  => self::prepare_schema(),
			'limits'          => self::LIMITS,
		);
	}

	/**
	 * Separate per-tool authority; invoice:read never inherits preparation.
	 *
	 * @return bool Native authenticated preparation grant exists now.
	 */
	public static function prepare_allowed() {
		return null !== WCJ_AI_Connection::prepare_context();
	}

	/**
	 * Save only typed intent; all external failures use one finite response.
	 *
	 * @param mixed $input Typed proposal, validated again inside the service.
	 * @return array|WP_Error Closed handoff, never internal exception text.
	 */
	public static function prepare( $input ) {
		if ( ! self::prepare_allowed() ) {
			return new WP_Error( 'wcj_ai_prepare_unavailable', 'Proposal unavailable. Do not retry with a replacement idempotency key. Review the current state in WordPress.' );
		}
		require_once __DIR__ . '/admin/class-wcj-ai-invoice-plans.php';
		try {
			return WCJ_AI_Invoice_Plans::prepare( $input );
		} catch ( Throwable $exception ) {
			return new WP_Error( 'wcj_ai_prepare_unavailable', 'Proposal unavailable. Do not retry with a replacement idempotency key. Review the current state in WordPress.' );
		}
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

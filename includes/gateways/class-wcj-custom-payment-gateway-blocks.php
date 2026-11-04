<?php
/**
 * Public Blocks integration for the existing custom gateway processor.
 *
 * @package Booster_For_WooCommerce/gateways
 * @since 9.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The final POST handler is on the module, independent of this subclass loading. */
class WCJ_Custom_Payment_Gateway_Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
	/**
	 * Existing module, including its role gate.
	 *
	 * @var WCJ_Payment_Gateways
	 */
	private $module;

	/**
	 * Register a candidate name without constructing Woo's gateway singleton.
	 *
	 * @param string               $id Candidate gateway ID.
	 * @param WCJ_Payment_Gateways $module Existing module.
	 */
	public function __construct( $id, $module ) {
		$this->name   = $id;
		$this->module = $module;
	}

	/** Load options only. */
	public function initialize() {
		$settings       = get_option( 'woocommerce_' . $this->name . '_settings', array() );
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Do not resolve actual gateways during registry initialization.
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'yes' === $this->get_setting( 'enabled', 'no' );
	}

	/**
	 * Register the shared public-API client script once.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$handle = 'wcj-custom-payment-gateways-blocks';
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			wp_register_script(
				$handle,
				plugins_url( 'js/wcj-custom-payment-gateways-blocks.js', dirname( __DIR__ ) . '/class-wcj-payment-gateways.php' ),
				array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wc-sanitize', 'wp-i18n' ),
				'9.0.0',
				true
			);
		}
		return array( $handle );
	}

	/**
	 * Resolve the actual existing gateway only at script-data enqueue time.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		if ( ! $this->module->custom_gateway_blocks_is_allowed() ) {
			return array();
		}
		$gateways = WC()->payment_gateways()->payment_gateways();
		$gateway  = isset( $gateways[ $this->name ] ) ? $gateways[ $this->name ] : null;
		if ( ! $gateway instanceof WC_Gateway_WCJ_Custom_Template || $this->name !== $gateway->id || 'yes' !== $gateway->get_option( 'enabled', 'no' ) ) {
			return array();
		}
		$schema = WCJ_Custom_Payment_Gateway_Input_Schema::build( $gateway->id, $gateway->get_option( 'description' ) );
		if ( isset( $schema['unsupported'] ) ) {
			return array();
		}
		return array(
			'profile'  => 1,
			'title'    => $gateway->get_title(),
			'segments' => $schema['segments'],
			'fields'   => $schema['fields'],
			'hash'     => $schema['hash'],
			'supports' => array( 'products' ),
		);
	}
}

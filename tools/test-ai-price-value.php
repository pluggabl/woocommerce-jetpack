<?php
/**
 * Synthetic price-value projection tests, not native callback capture proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standalone core constant substitute.
define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/includes/class-wcj-ai-price-value.php';

/**
 * Check one finite, developer-authored fixture.
 *
 * @param string $name Finite test identifier.
 * @param bool   $condition Expected result.
 * @throws RuntimeException On a failed fixture.
 */
function wcj_ai_price_check( $name, $condition ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'Price projection assertion failed.' ); }
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI finite test identifier, not an HTTP response.
	echo $name . ": pass\n";
}

wcj_ai_price_check( 'blank_not_zero', 'empty_price' === WCJ_AI_Price_Value::project( '' )['kind'] && 'numeric' === WCJ_AI_Price_Value::project( 0 )['kind'] );
foreach ( array( 0, 1, -1, 1000000000, -1000000000, 0.0, -0.0, 0.1, 1.25, 1e-100, '0', '0.00', '-3.25', '1000000000.000' ) as $wcj_ai_index => $wcj_ai_value ) {
	$wcj_ai_before = $wcj_ai_value;
	$wcj_ai_record = WCJ_AI_Price_Value::project( $wcj_ai_value );
	wcj_ai_price_check( 'numeric_' . $wcj_ai_index, 'numeric' === $wcj_ai_record['kind'] && $wcj_ai_before === $wcj_ai_value );
	if ( is_float( $wcj_ai_value ) ) {
		wcj_ai_price_check( 'float_roundtrip_' . $wcj_ai_index, pack( 'E', $wcj_ai_value ) === pack( 'E', (float) $wcj_ai_record['value'] ) );
	}
}
$wcj_ai_object = new class() {
	/** Whether forbidden conversion ran.
	 *
	 * @var bool
	 */
	public $called = false;
	/**
	 * Detect object coercion even if its text is a valid numeric string.
	 *
	 * @return string
	 */
	public function __toString() {
		$this->called = true;
		return '42';
	}
};
foreach ( array( true, false, null, array( 'private' => 'CANARY' ), $wcj_ai_object, NAN, INF, -INF, 1000000001, -1000000001, '1e3', ' 1', "1\n", '01', '<script>CANARY</script>', str_repeat( '1', 65 ), '1000000000.01', '1000000000.000000000000001' ) as $wcj_ai_index => $wcj_ai_value ) {
	wcj_ai_price_check(
		'unsupported_' . $wcj_ai_index,
		array(
			'kind'   => 'unsupported',
			'reason' => 'unsupported_numeric_value',
		) === WCJ_AI_Price_Value::project( $wcj_ai_value )
	);
}
wcj_ai_price_check( 'preserve_numeric_string', '0.00' === WCJ_AI_Price_Value::project( '0.00' )['value'] );
wcj_ai_price_check( 'preserve_native_types', 'integer' === WCJ_AI_Price_Value::project( 1 )['native_type'] && 'float' === WCJ_AI_Price_Value::project( 1.0 )['native_type'] && 'string' === WCJ_AI_Price_Value::project( '1' )['native_type'] );

wcj_ai_price_check( 'object_conversion_not_invoked', false === $wcj_ai_object->called );

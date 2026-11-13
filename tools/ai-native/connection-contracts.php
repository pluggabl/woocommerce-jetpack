<?php
/** Installed WordPress validator test; no business options or credentials changed. */
define( 'WP_ADMIN', true );
$_SERVER['REQUEST_METHOD'] = 'GET';
require '/var/www/html/wp-load.php';
if ( 'cli' !== PHP_SAPI || 'synthetic_ai' !== DB_NAME || 'http://127.0.0.1:61646' !== getenv( 'AI_ORIGIN' ) ) { exit( 2 ); }
$cases = array();
function check_contract( $name, $value, $schema, $expected ) {
	global $cases;
	$result = rest_validate_value_from_schema( $value, $schema );
	$pass = $expected === ( true === $result );
	$cases[] = array( 'case' => $name, 'pass' => $pass );
	if ( ! $pass ) { echo json_encode( $cases ); exit( 1 ); }
}
$schema = WCJ_AI_Abilities::output_schema( 'context' );
$value = array( 'schema_version' => 1, 'task' => 'invoice_presentation', 'fields' => array(), 'basis' => WCJ_AI_Abilities::BASIS );
foreach ( WCJ_AI_Invoice_Presentation::FIELDS as $field ) { $value['fields'][ $field ] = array( 'status' => 'supported', 'value' => '#abcdef' ); }
check_contract( 'complete supported context', $value, $schema, true );
$field = WCJ_AI_Invoice_Presentation::FIELDS[0];
foreach ( array( 'missing', 'unavailable' ) as $status ) {
	$test = $value; $test['fields'][ $field ] = array( 'status' => $status, 'value' => null );
	check_contract( $status . ' with null', $test, $schema, true );
	$test['fields'][ $field ]['value'] = '#abcdef';
	check_contract( $status . ' with hex rejected', $test, $schema, false );
}
foreach ( array( null, '#ABCDEF', '#abc', 'red', '#abcdeg', "#abcdef\n" ) as $invalid ) {
	$test = $value; $test['fields'][ $field ]['value'] = $invalid;
	check_contract( 'unsupported value ' . json_encode( $invalid ), $test, $schema, false );
}
$test = $value; unset( $test['fields'][ $field ] ); check_contract( 'missing field rejected', $test, $schema, false );
$test = $value; $test['extra'] = 1; check_contract( 'extra root key rejected', $test, $schema, false );
$test = $value; $test['fields']['extra'] = 1; check_contract( 'extra field rejected', $test, $schema, false );
$test = $value; $test['fields'][ $field ]['extra'] = 1; check_contract( 'extra record key rejected', $test, $schema, false );
$test = $value; $test['basis'] = 'live rendered result'; check_contract( 'changed basis rejected', $test, $schema, false );
$schema = WCJ_AI_Abilities::output_schema( 'schema' );
$value = array( 'schema_version' => 1, 'task' => 'invoice_presentation', 'proposal_schema' => WCJ_AI_Invoice_Presentation::input_schema(), 'available' => array( 'schema', 'saved_colour_context' ), 'unavailable' => array( 'prepare', 'apply', 'preview_pdf', 'recovery', 'pricing_capture_export', 'financial_report_export' ), 'limits' => WCJ_AI_Abilities::LIMITS );
check_contract( 'complete fixed schema response', $value, $schema, true );
$test = $value; $test['available'][] = 'apply'; check_contract( 'changed availability rejected', $test, $schema, false );
$test = $value; $test['proposal_schema']['extra'] = true; check_contract( 'changed proposal schema rejected', $test, $schema, false );
echo json_encode( array( 'kind' => 'Installed native output validator tests, not authenticated tool execution', 'cases' => $cases ) );

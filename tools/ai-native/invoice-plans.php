<?php
/**
 * Native synthetic per-plan journal tests. No cookie/UI/remote-client claim.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
require __DIR__ . '/bootstrap.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/admin/class-wcj-ai-invoice-plans.php';
/**
 * Seed task-owned synthetic colours.
 */
function wcj_ai_plan_seed() {
	foreach ( WCJ_AI_Invoice_Presentation::FIELDS as $wcj_ai_field ) {
		update_option( 'wcj_invoicing_invoice_' . $wcj_ai_field, '#112233', false ); }
}
/**
 * Create a synthetic plan.
 *
 * @param int $wcj_ai_count Number of fields.
 * @return array Plan view.
 */
function wcj_ai_plan( $wcj_ai_count = 2 ) {
	return WCJ_AI_Invoice_Plans::create(
		array(
			'schema_version' => 1,
			'task'           => 'invoice_presentation',
			'changes'        => array_fill_keys( array_slice( WCJ_AI_Invoice_Presentation::FIELDS, 0, $wcj_ai_count ), '#445566' ),
		)
	);
}
/**
 * Read a synthetic journal.
 *
 * @param string $wcj_ai_id Plan identifier.
 * @return array Journal.
 */
function wcj_ai_plan_row( $wcj_ai_id ) {
	return json_decode( get_option( 'wcj_ai_invoice_plan_' . $wcj_ai_id ), true ); }
wcj_ai_plan_seed();
$wcj_ai_plan = wcj_ai_plan( 4 );
wcj_ai_check( 'prepare_no_setting_write', '#112233' === get_option( 'wcj_invoicing_invoice_header_text_color' ) );
wcj_ai_check( 'four_field_apply', 'applied' === WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] )['status'] );
wcj_ai_check( 'apply_replay_denied', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ) ) );
wcj_ai_check( 'own_receipt_recovery', 'recovered' === WCJ_AI_Invoice_Plans::recover( $wcj_ai_plan['id'] )['status'] );
wcj_ai_check( 'recovery_restored', '#112233' === get_option( 'wcj_invoicing_invoice_header_text_color' ) );
wcj_ai_check( 'recovery_replay_denied', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::recover( $wcj_ai_plan['id'] ) ) );
$wcj_ai_plan = wcj_ai_plan();
WCJ_AI_Invoice_Plans::cancel( $wcj_ai_plan['id'] );
wcj_ai_check( 'cancel_cannot_apply', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ) ) );
$wcj_ai_plan = wcj_ai_plan();
update_option( 'wcj_invoicing_invoice_header_text_color', '#abcdef', false );
wcj_ai_check( 'stale_no_overwrite', 'stale' === WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] )['status'] && '#abcdef' === get_option( 'wcj_invoicing_invoice_header_text_color' ) );
wcj_ai_plan_seed();
$wcj_ai_plan            = wcj_ai_plan();
$wcj_ai_doc             = wcj_ai_plan_row( $wcj_ai_plan['id'] );
$wcj_ai_doc['created'] -= 1000;
$wcj_ai_doc['expires'] -= 1000;
update_option( 'wcj_ai_invoice_plan_' . $wcj_ai_plan['id'], wp_json_encode( $wcj_ai_doc ), false );
wcj_ai_check( 'expired_denied', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ) ) );
$wcj_ai_plan = wcj_ai_plan();
wp_set_current_user( 0 );
wcj_ai_check( 'guest_denied', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::inspect( $wcj_ai_plan['id'] ) ) );
wp_set_current_user( $wcj_ai_owner->ID );
$wcj_ai_other = get_user_by( 'login', 'plan_other' );
if ( ! $wcj_ai_other ) {
	$wcj_ai_id    = wp_create_user( 'plan_other', wp_generate_password( 32 ), 'plan-other@example.invalid' );
	$wcj_ai_other = get_user_by( 'id', $wcj_ai_id );
	$wcj_ai_other->set_role( 'administrator' ); }
wp_set_current_user( $wcj_ai_other->ID );
wcj_ai_check( 'other_admin_cannot_read_or_apply', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::inspect( $wcj_ai_plan['id'] ) ) && wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ) ) );
wp_set_current_user( $wcj_ai_owner->ID );
$wcj_ai_lock = WCJ_Job_Store::lock( WCJ_Invoice_Job::LOCK, 'fixture' );
wcj_ai_check( 'native_lock_denies', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ) ) );
WCJ_Job_Store::unlock( WCJ_Invoice_Job::LOCK, $wcj_ai_lock );
// Reject exactly one chosen journal UPDATE, never unrelated database writes.
foreach ( array( 'intent', 'progress', 'receipt', 'recover_progress', 'recover_ack' ) as $wcj_ai_fault ) {
	wcj_ai_plan_seed();
	$wcj_ai_plan = wcj_ai_plan();
	$wcj_ai_key  = 'wcj_ai_invoice_plan_' . $wcj_ai_plan['id'];
	if ( str_starts_with( $wcj_ai_fault, 'recover' ) ) {
		WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ); }
	$wcj_ai_injected = false;
	$wcj_ai_filter   = static function ( $wcj_ai_query ) use ( &$wcj_ai_injected, $wcj_ai_fault, $wcj_ai_key ) {
		$wcj_ai_match         = str_starts_with( $wcj_ai_query, 'UPDATE ' ) && str_contains( $wcj_ai_query, "option_name = '" . $wcj_ai_key . "'" );
		$wcj_ai_journal_match = $wcj_ai_match && ( ( 'intent' === $wcj_ai_fault && str_contains( $wcj_ai_query, '\\"status\\":\\"applying\\"' ) && str_contains( $wcj_ai_query, '\\"written\\":[]' ) ) ||
			( 'progress' === $wcj_ai_fault && str_contains( $wcj_ai_query, '\\"written\\":[\\"header_text_color\\"]' ) ) ||
			( 'receipt' === $wcj_ai_fault && str_contains( $wcj_ai_query, '\\"status\\":\\"applied\\"' ) ) ||
			( 'recover_progress' === $wcj_ai_fault && str_contains( $wcj_ai_query, '\\"restored\\":[\\"header_line_color\\"]' ) ) );
		$wcj_ai_ack_match     = 'recover_ack' === $wcj_ai_fault && str_starts_with( $wcj_ai_query, 'UPDATE ' ) && str_contains( $wcj_ai_query, "option_name = 'wcj_invoicing_invoice_header_line_color'" );
		if ( ! $wcj_ai_injected && ( $wcj_ai_journal_match || $wcj_ai_ack_match ) ) {
			$wcj_ai_injected = true;
			if ( $wcj_ai_ack_match ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Replay the exact prepared task-owned undo query once to simulate lost acknowledgement.
				$wpdb->query( $wcj_ai_query ); } // Perform undo then simulate lost success acknowledgement.
			return 'SELECT 0 WHERE 1=0';
		}
		return $wcj_ai_query;
	};
	add_filter( 'query', $wcj_ai_filter, 999 );
	try {
		$wcj_ai_denied = wcj_ai_denied( static fn() => str_starts_with( $wcj_ai_fault, 'recover' ) ? WCJ_AI_Invoice_Plans::recover( $wcj_ai_plan['id'] ) : WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ) ); } finally {
		remove_filter( 'query', $wcj_ai_filter, 999 ); }
		wcj_ai_check( $wcj_ai_fault . '_injected_and_failed', $wcj_ai_injected && $wcj_ai_denied );
		$wcj_ai_view = WCJ_AI_Invoice_Plans::inspect( $wcj_ai_plan['id'] );
		wcj_ai_check( $wcj_ai_fault . '_honest_state', 'intent' === $wcj_ai_fault ? ( 'pending' === $wcj_ai_view['status'] && '#112233' === get_option( 'wcj_invoicing_invoice_header_text_color' ) ) : $wcj_ai_view['outcome_uncertain'] );
		if ( 'intent' !== $wcj_ai_fault ) {
			wcj_ai_check( $wcj_ai_fault . '_no_replay', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ) ) && wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::recover( $wcj_ai_plan['id'] ) ) ); }
}
wcj_ai_plan_seed();
$wcj_ai_comp_plan   = wcj_ai_plan();
$wcj_ai_phase       = 0;
$wcj_ai_comp_filter = static function ( $query ) use ( &$wcj_ai_phase ) {
	if ( str_starts_with( $query, 'UPDATE ' ) && 0 === $wcj_ai_phase && str_contains( $query, "option_name = 'wcj_invoicing_invoice_header_line_color'" ) ) {
		$wcj_ai_phase = 1;
		return 'SELECT 0 WHERE 1=0'; }
	if ( str_starts_with( $query, 'UPDATE ' ) && 1 === $wcj_ai_phase && str_contains( $query, "option_name = 'wcj_invoicing_invoice_header_text_color'" ) ) {
		$wcj_ai_phase = 2;
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Exact prepared test-owned undo, simulating lost acknowledgement.
		$wpdb->query( $query );
		return 'SELECT 0 WHERE 1=0';
	}
	return $query;
};
add_filter( 'query', $wcj_ai_comp_filter, 999 );
try {
	$wcj_ai_comp_denied = wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_comp_plan['id'] ) ); } finally {
	remove_filter( 'query', $wcj_ai_comp_filter, 999 ); }
	wcj_ai_check( 'compensation_ack_lost_stays_unknown', 2 === $wcj_ai_phase && $wcj_ai_comp_denied && WCJ_AI_Invoice_Plans::inspect( $wcj_ai_comp_plan['id'] )['outcome_uncertain'] );
	wcj_ai_plan_seed();
	$wcj_ai_a = wcj_ai_plan();
	WCJ_AI_Invoice_Plans::apply( $wcj_ai_a['id'] );
	$wcj_ai_base = array( 'footer_text_color' => WCJ_Job_Store::state_hash( WCJ_Job_Store::read_state( 'wcj_invoicing_invoice_footer_text_color' ) ) );
	WCJ_Invoice_Job::apply_draft( array( 'footer_text_color' => '#aabbcc' ), $wcj_ai_base );
	update_option( 'wcj_invoicing_invoice_header_text_color', '#abcdef', false );
	$wcj_ai_view = WCJ_AI_Invoice_Plans::recover( $wcj_ai_a['id'] );
	wcj_ai_check( 'recovery_preserves_later_edit', 'recovery_partial' === $wcj_ai_view['status'] && '#abcdef' === get_option( 'wcj_invoicing_invoice_header_text_color' ) );
	wcj_ai_check( 'recovery_never_uses_global_native_receipt', '#aabbcc' === get_option( 'wcj_invoicing_invoice_footer_text_color' ) );
	wcj_ai_done( 'native synthetic journal only; no cookie, UI, MCP or client proof' );

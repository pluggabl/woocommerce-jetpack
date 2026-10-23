<?php
/** Finite affected04 production admin/filter entry-point checks. No WordPress bootstrap. */
require __DIR__ . '/codex-9-0-payment-explanations.php';
$tier = 'booster-elite-for-woocommerce.php' === WCJ_PLUGIN_FILE ? 'elite' : ( 'booster-plus-for-woocommerce.php' === WCJ_PLUGIN_FILE ? 'plus' : 'free' );
function do_shortcode( $value ) { if ( ! is_string( $value ) || str_contains( $value, '[' ) ) { throw new RuntimeException( 'unsupported_shortcode_fixture' ); } return $value; }
$admin = true;
$nonce_checks = array();
$fee = require $root . '/includes/class-wcj-payment-gateways-fees.php';
$plugin->all_modules['payment_gateways_fees'] = $fee;
require $root . '/includes/class-wcj-rule-explanations-admin.php';
$renderer = new WCJ_Rule_Explanations_Admin();
function settings04( $extra = array() ) {
 fresh( array_merge( array( 'wcj_payment_gateways_fees_enabled' => 'yes', 'wcj_gateways_fees_text' => array( 'cod' => 'Fixed fixture fee' ), 'wcj_gateways_fees_type' => array( 'cod' => 'fixed' ), 'wcj_gateways_fees_value' => array( 'cod' => 2.5 ) ), $extra ) );
 $GLOBALS['nonce_checks'] = array();
 $GLOBALS['window']->setValue( null, array( 'state' => 'unarmed' ) );
}
function render04( $name, $post, $settings = array() ) {
 settings04( $settings ); $_POST = $post; $_SERVER['REQUEST_METHOD'] = null === $post ? 'GET' : 'POST'; if ( null === $post ) { $_POST = array(); }
 $die = null; ob_start();
 try { $GLOBALS['renderer']->render_page(); } catch ( RuntimeException $e ) { if ( ! str_starts_with( $e->getMessage(), 'wp_die:' ) ) { ob_end_clean(); throw $e; } $die = substr( $e->getMessage(), 7 ); }
 $html = ob_get_clean();
 $GLOBALS['records'][] = array( 'case' => $name . '_complete_html', 'html' => $html, 'wp_die' => $die, 'nonce_checks' => $GLOBALS['nonce_checks'], 'trace' => $GLOBALS['trace'] );
 eq( $name . '_no_runtime_warnings', $GLOBALS['errors'], array() );
 return array( $html, $die );
}
function subsection04( $name, $html ) {
 eq( $name . '_observation_subsection_count', substr_count( $html, '<section class="wcj-payment-observation-link">' ), 1 );
 preg_match( '~<section class="wcj-payment-observation-link">(.*?)</section>~s', $html, $m );
 eq( $name . '_observer_heading', str_contains( $m[1], '<h3>Initial order-review observation link</h3>' ), true );
 foreach ( WCJ_Rule_Explanations::payment_observation_labels() as $label ) { eq( $name . '_label_in_observation_' . count( $GLOBALS['records'] ), str_contains( $m[1], esc_html( $label ) ), true ); }
 eq( $name . '_observe_form_adjacent', str_contains( $m[1], 'value="observe"' ), true );
 eq( $name . '_no_scenario_result_in_observation', str_contains( $m[1], 'not a configuration snapshot.' ), false );
 return $m[1];
}
list( $html, $die ) = render04( 'initial_get', null );
eq( 'get_no_die', $die, null ); subsection04( 'get', $html ); eq( 'get_fee_layout', str_contains( $html, '<div class="wrap"><h1>Rule explanations</h1>' ), true );
$facts = array( 'currency' => array( 'state' => 'known', 'value' => 'USD' ), 'roles' => array( 'state' => 'known', 'value' => array( 'customer' ) ), 'billing' => array( 'country' => array( 'state' => 'known', 'value' => 'US' ), 'state' => array( 'state' => 'known', 'value' => 'NC' ), 'postcode' => array( 'state' => 'known', 'value' => '28277' ) ), 'shipping' => array( 'country' => array( 'state' => 'known_empty' ), 'state' => array( 'state' => 'known_empty' ), 'postcode' => array( 'state' => 'known_empty' ) ), 'contents_total' => array( 'state' => 'known', 'value' => '20.00' ), 'total_tax' => array( 'state' => 'known', 'value' => '0' ), 'shipping_total' => array( 'state' => 'known', 'value' => '0' ), 'needs_shipping' => array( 'state' => 'known', 'value' => true ), 'chosen_shipping_methods' => array( 'state' => 'known', 'value' => array( 'flat_rate:2' ) ) );
$scenario = array( 'mode' => 'scenario', 'gateway' => 'cod', 'facts' => $facts );
$compare = array( 'mode' => 'compare', 'gateway' => 'cod', 'scenarios' => array( $facts, $facts ) );
foreach ( array( 'observe', 'scenario', 'compare' ) as $action ) {
 foreach ( array( 'missing', 'invalid', 'valid' ) as $nonce ) {
  $post = array( 'wcj_payment_action' => $action, 'wcj_payment_gateway_key' => 'cod', 'wcj_payment_json' => json_encode( 'compare' === $action ? $compare : $scenario ) );
  if ( 'missing' !== $nonce ) { $post['wcj_payment_form_nonce'] = 'valid' === $nonce ? wp_create_nonce( 'wcj_payment_diagnostic' ) : 'invalid'; }
  // Both unrelated fee actions are deliberately present with invalid fee nonces.
  $post['wcj_rule_action'] = 'compare'; $post['wcj_rule_comparison_nonce'] = 'invalid'; $post['_wpnonce'] = 'invalid';
  $name = 'payment_' . $action . '_' . $nonce; list( $html, $die ) = render04( $name, $post );
  eq( $name . '_no_fee_dispatch_die', $die, null ); eq( $name . '_exclusive_nonce', $nonce_checks, array( 'wcj_payment_diagnostic' ) ); subsection04( $name, $html );
  if ( 'elite' === $tier ) { eq( $name . '_elite_outer_layout_retained', str_contains( $html, '<div class="wrap"><h1>Rule explanations</h1>' ), true ); }
  if ( 'valid' !== $nonce ) { eq( $name . '_payment_expiry', substr_count( $html, 'This form has expired. Reload this page.' ), 1 ); eq( $name . '_no_fee_expiry', str_contains( $html, 'Reload this page and try again.' ), false ); }
  elseif ( 'observe' === $action ) { eq( $name . '_real_link_nonce', str_contains( $html, esc_url( wp_create_nonce( 'wcj_payment_observe:3:7:cod' ) ) ), true ); }
  elseif ( 'elite' !== $tier ) { eq( $name . '_basic_denial', str_contains( $html, 'Payment scenarios are available in Booster Elite.' ), true ); }
  else {
   $label = 'compare' === $action ? 'Two sequential merchant-supplied scenarios; not an atomic comparison or a configuration snapshot.' : 'Merchant-supplied scenario; not a configuration snapshot.';
   eq( $name . '_scenario_context_label', str_contains( $html, esc_html( $label ) ), true );
   eq( $name . '_real_service_rows', substr_count( $html, 'unsupported_catalogue_scenario' ), 'compare' === $action ? 2 : 1 );
   $d03 = array_values( array_filter( $trace, function( $t ) { return 'get_option' === $t[0] && 'wcj_modules_by_user_roles_enabled' === $t[1]; } ) );
   // wcj_get_option caches a raw read; the option filter still executes at every admission call.
   $d03_filter = array_values( array_filter( $trace, function( $t ) { return 'filter' === $t[0] && 'wcj_modules_by_user_roles_enabled' === $t[1]; } ) );
   eq( $name . '_accepted_D03_call_count', count( $d03_filter ), 'compare' === $action ? 3 : 1 );
  }
 }
}
$single = array( '_wpnonce' => wp_create_nonce( 'wcj_explain_gateway_fee' ), 'gateway' => 'cod', 'subtotal' => '20', 'shipping' => '0', 'tax' => '0', 'shipping_tax' => '0' );
if ( 'elite' === $tier ) { $single['role'] = 'guest'; $single['product_ids'] = ''; }
list( $html, $die ) = render04( 'genuine_fee_single', $single );
eq( 'genuine_fee_single_no_die', $die, null ); eq( 'genuine_fee_nonce', $nonce_checks, array( 'wcj_explain_gateway_fee' ) ); eq( 'genuine_fee_single_eligible', str_contains( $html, 'evaluated: eligible' ), true ); eq( 'genuine_fee_single_amount', str_contains( $html, '<strong>2.5</strong>' ), true );
$fee_row = array( 'gateway' => 'cod', 'subtotal' => '20', 'shipping' => '0', 'tax' => '0', 'shipping_tax' => '0', 'role' => 'guest', 'product_ids' => '' );
$fee_compare = array( 'wcj_rule_action' => 'compare', 'wcj_rule_comparison_nonce' => wp_create_nonce( 'wcj_compare_gateway_fees' ), 'scenarios' => array( $fee_row, $fee_row ) );
list( $html, $die ) = render04( 'genuine_fee_compare', $fee_compare );
if ( 'elite' === $tier ) { eq( 'genuine_fee_compare_no_die', $die, null ); eq( 'genuine_fee_compare_nonce', $nonce_checks, array( 'wcj_compare_gateway_fees' ) ); eq( 'genuine_fee_compare_two_amounts', substr_count( $html, '<strong>2.5</strong>' ), 2 ); }
else { eq( 'genuine_fee_compare_basic_no_die', $die, null ); eq( 'genuine_fee_compare_basic_single_fee_nonce', $nonce_checks, array( 'wcj_explain_gateway_fee' ) ); eq( 'genuine_fee_compare_basic_single_fee_expiry', str_contains( $html, esc_html( 'This form has expired. Reload this page and try again.' ) ), true ); eq( 'genuine_fee_compare_basic_no_successful_result', str_contains( $html, 'evaluated: eligible' ), false ); eq( 'genuine_fee_compare_basic_no_comparison_amounts', substr_count( $html, '<strong>2.5</strong>' ), 0 ); }
// Minimal source-accurate Plus filter dependency, Free unhooked and Elite unchanged.
if ( 'plus' === $tier ) {
 $h = array_values( array_filter( $hooks['booster_option'], function( $h ) { return is_array( $h[0] ) && $h[0][0] instanceof WCJ_Plus_Filters; } ) );
 eq( 'plus_actual_priority_accepted_args', array( count( $h ), $h[0][1], $h[0][2] ), array( 1, 101, 2 ) ); eq( 'plus_real_second_argument', apply_filters( 'booster_option', 'no', 'yes' ), 'yes' );
}
$admin = false;
foreach ( array(
 'product_include_miss' => array( array( 'wcj_gateways_per_products_cod' => array( '99' ) ), array(), 'include_miss', false ),
 'product_exclude_hit' => array( array( 'wcj_gateways_per_products_excl_cod' => array( '11' ) ), array(), 'exclude_match', false ),
 'product_include_match' => array( array( 'wcj_gateways_per_products_cod' => array( '11' ) ), array(), 'rules_pass', true ),
 'product_exclude_miss' => array( array( 'wcj_gateways_per_products_excl_cod' => array( '99' ) ), array(), 'rules_pass', true ),
 'uncertain_terms_product_removal' => array( array( 'wcj_gateways_per_category_excl_cod' => array( '7' ), 'wcj_gateways_per_products_cod' => array( '99' ) ), array(), 'category_terms_uncertain', false ),
) as $name => $case ) {
 list( $settings, $terms, $reason, $retained ) = $case; $wc->cart->items = array( array( 'product_id' => 11, 'variation_id' => 0 ) );
 settings04( $settings ); $lc = get_class( $legacy['payment_gateways_per_category'] ); $old = new $lc(); $plugin->options = array(); $trace = array(); $errors = array(); $expected = $old->filter_available_payment_gateways_per_category( $gateways ); $reads = $trace; $warnings = $errors;
 settings04( $settings ); $actual_module = new WCJ_Payment_Gateways_Per_Category(); $plugin->all_modules['payment_gateways_per_category'] = $actual_module; $plugin->options = array(); $trace = array(); $errors = array(); open_window(); $actual = $actual_module->filter_available_payment_gateways_per_category( $gateways );
 eq( $name . '_ordinary_result_identity', $actual, $expected ); eq( $name . '_ordinary_lazy_trace', $trace, $reads ); eq( $name . '_ordinary_warnings', $errors, $warnings );
 $w = $window->getValue(); $w['state'] = 'closed'; $window->setValue( null, $w ); $receipt = $service->get_payment_observation(); eq( $name . '_receipt_valid', is_wp_error( $receipt ), false );
 if ( 'free' === $tier ) { $reason = 'uncertain_terms_product_removal' === $name ? 'category_terms_uncertain' : 'no_rules'; $retained = true; }
 eq( $name . '_actual_gateway_presence', array_key_exists( 'cod', $actual ), $retained ); eq( $name . '_conservative_reason_independent', $receipt['rows'][0]['reason'], $reason );
}
$raw_settings = array( 'wcj_general_enabled' => 'yes', 'wcj_general_user_role_changer_enabled' => 'yes' );
settings04( $raw_settings ); $decision = $plugin->all_modules['payment_gateways_by_user_role']->explain_payment_gateway( 'cod', $facts );
eq( 'R07_actual_raw_option_result', $decision, 'free' === $tier ? array( 'not_configured', 'no_rules', 'role' ) : array( 'unsupported', 'dynamic_role_policy', 'role' ) );
eq( 'R07_actual_raw_option_order', array_column( array_values( array_filter( $trace, function( $t ) { return 'get_option' === $t[0]; } ) ), 1 ), 'free' === $tier ? array( 'wcj_modules_by_user_roles_enabled', 'wcj_general_enabled', 'wcj_general_user_role_changer_enabled', 'wcj_payment_gateways_by_user_role_multi_role_check', 'wcj_gateways_user_roles_include_cod', 'wcj_gateways_user_roles_exclude_cod' ) : array( 'wcj_modules_by_user_roles_enabled', 'wcj_general_enabled', 'wcj_general_user_role_changer_enabled' ) );
eq( 'R07_no_private_actor_probe', array_values( array_filter( $trace, function( $t ) { return in_array( $t[0], array( 'user_meta', 'current_roles' ), true ); } ) ), array() );
eq( 'affected04_final_warnings', $errors, array() );
echo json_encode( array( 'tier' => $tier, 'pass' => true, 'observations' => $records, 'limits' => array( 'offline_real_admin_service_with_minimal_WP_boundaries', 'no_native_header_cache_browser_proof', 'N3_failed_arm_referrer_hardening_required_separate_design', 'style_unwaived_no_integration' ) ), JSON_UNESCAPED_SLASHES ) . "\n";

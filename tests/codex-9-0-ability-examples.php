<?php
/**
 * Focused actual private-admin renderer regressions. Source-only synthetic context.
 * Usage: php -n FILE ROOT TIER [normal|missing-api|missing-rest-helper].
 * No WordPress bootstrap, credential inventory, connector or Ability execution.
 *
 * @package Booster_Tests
 */
if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) ) {
	exit( 2 );
}
$root = $argv[1] ?? '';
$tier = $argv[2] ?? '';
$mode = $argv[3] ?? 'normal';
if ( ! is_file( $root . '/includes/class-wcj-rule-explanations-admin.php' ) || ! in_array( $tier, array( 'free', 'plus', 'elite' ), true ) || ! in_array( $mode, array( 'normal', 'missing-api', 'missing-rest-helper' ), true ) ) {
	exit( 2 );
}
define( 'ABSPATH', __DIR__ . '/' );
define( 'WCJ_PLUGIN_FILE', 'free' === $tier ? 'woocommerce-jetpack.php' : 'booster-' . $tier . '-for-woocommerce.php' );
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
$GLOBALS['examples_checks'] = array();

function examples_check( $name, $actual, $expected ) {
	$GLOBALS['examples_checks'][] = array( 'name' => $name, 'actual' => $actual, 'expected' => $expected, 'pass' => $actual === $expected );
}
function examples_reset() {
	$GLOBALS['examples_case'] = array( 'capable' => true, 'admin' => true, 'logged' => true, 'rest' => false, 'rest_throw' => false, 'registered' => true, 'meta_throw' => false, 'hostile_translation' => false, 'gateways' => true, 'module_present' => true, 'role_allowed' => true );
	$GLOBALS['examples_counts'] = array( 'options' => 0, 'roles' => 0, 'plugin' => 0, 'enabled' => 0, 'producer_reads' => 0, 'exists' => 0, 'fetches' => 0, 'metadata' => 0, 'user' => 0, 'availability' => 0, 'executions' => 0, 'permissions' => 0, 'evaluations' => 0, 'writes' => 0, 'credentials' => 0, 'network' => 0 );
	$GLOBALS['examples_translations'] = array();
	$_POST = array(); $_GET = array(); $_SERVER = array( 'REQUEST_METHOD' => 'GET' );
}
function current_user_can( $cap ) { return 'manage_woocommerce' === $cap && $GLOBALS['examples_case']['capable']; }
function is_admin() { return $GLOBALS['examples_case']['admin']; }
function is_user_logged_in() { return $GLOBALS['examples_case']['logged']; }
if ( 'missing-rest-helper' !== $mode ) {
	function wcj_is_rest() {
		if ( $GLOBALS['examples_case']['rest_throw'] ) { throw new RuntimeException( 'private-rest-error' ); }
		return $GLOBALS['examples_case']['rest'];
	}
}
function __( $text, $domain = '' ) {
	$GLOBALS['examples_translations'][] = array( $text, $domain );
	return $GLOBALS['examples_case']['hostile_translation'] ? '<script>synthetic-translation</script>' . $text : $text;
}
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain = '' ) { return esc_html( __( $text, $domain ) ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_html( $text ); }
function add_action( ...$args ) {}
function add_submenu_page( ...$args ) {}
function admin_url( $path = '' ) { return 'https://example.invalid/wp-admin/' . $path; }
function wp_nonce_field( $action ) { echo '<input name="_wpnonce" value="synthetic">'; }
function selected( $a, $b, $echo = true ) { return $a === $b ? 'selected' : ''; }
class Examples_Denied extends RuntimeException {}
function wp_die( $text ) { throw new Examples_Denied( 'denied' ); }
class WP_Error {
	private $message;
	public function __construct( $code, $message ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function did_action( $action ) { return 'init' === $action ? 1 : 0; }
class WP_Ability {
	private $name;
	public function __construct( $name ) { $this->name = $name; }
	public function get_name() { return $this->name; }
	public function get_meta() {
		++$GLOBALS['examples_counts']['metadata'];
		if ( $GLOBALS['examples_case']['meta_throw'] ) { throw new RuntimeException( 'private-metadata-error' ); }
		return array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => true, 'destructive' => false ) );
	}
	public function execute() { ++$GLOBALS['examples_counts']['executions']; throw new RuntimeException( 'Ability execution forbidden' ); }
	public function check_permissions() { ++$GLOBALS['examples_counts']['permissions']; throw new RuntimeException( 'Permission execution forbidden' ); }
}
if ( 'missing-api' !== $mode ) {
	function wp_has_ability( $name ) { ++$GLOBALS['examples_counts']['exists']; return $GLOBALS['examples_case']['registered']; }
	function wp_get_ability( $name ) { ++$GLOBALS['examples_counts']['fetches']; return new WP_Ability( $name ); }
}
function is_ssl() { return false; }
function wp_get_current_user() { ++$GLOBALS['examples_counts']['user']; return (object) array( 'roles' => array( 'shop_manager' ) ); }
function wp_is_application_passwords_available_for_user( $user ) { ++$GLOBALS['examples_counts']['availability']; return false; }
function wcj_get_option( $name, $default = null ) {
	++$GLOBALS['examples_counts']['options'];
	if ( 'wcj_gateways_fees_text' !== $name ) { throw new RuntimeException( 'New option read' ); }
	return $GLOBALS['examples_case']['gateways'] ? array( 'configured_gateway' => 'Literal title' ) : array();
}
function wp_roles() { ++$GLOBALS['examples_counts']['roles']; return (object) array( 'roles' => array( 'shop_manager' => array() ) ); }
class WCJ_Payment_Gateways_Fees {
	public function is_enabled() { ++$GLOBALS['examples_counts']['enabled']; return $GLOBALS['examples_case']['role_allowed']; }
	public function init_options() { throw new RuntimeException( 'No scenario calculation on GET' ); }
	public function wcj_get_option() { throw new RuntimeException( 'No evaluator options on GET' ); }
	public function evaluate_gateway_fee() { ++$GLOBALS['examples_counts']['evaluations']; throw new RuntimeException( 'No fee evaluation' ); }
	public function get_last_fee_decision() { ++$GLOBALS['examples_counts']['producer_reads']; return array(); }
	public function resolve_fee_gateway() { throw new RuntimeException( 'No scenario resolution on GET' ); }
}
function w_c_j() {
	++$GLOBALS['examples_counts']['plugin'];
	return (object) array( 'all_modules' => $GLOBALS['examples_case']['module_present'] ? array( 'payment_gateways_fees' => new WCJ_Payment_Gateways_Fees() ) : array() );
}
function update_option( ...$args ) { ++$GLOBALS['examples_counts']['writes']; throw new RuntimeException( 'No writes' ); }
function update_post_meta( ...$args ) { ++$GLOBALS['examples_counts']['writes']; throw new RuntimeException( 'No writes' ); }
function wp_remote_get( ...$args ) { ++$GLOBALS['examples_counts']['network']; throw new RuntimeException( 'No network' ); }
class WP_Application_Passwords {
	public static function get_user_application_passwords( ...$args ) { ++$GLOBALS['examples_counts']['credentials']; throw new RuntimeException( 'No credential reads' ); }
}
examples_reset();
require $root . '/includes/class-wcj-rule-explanations.php';
require $root . '/includes/class-wcj-returns-summary.php';
require $root . '/includes/class-wcj-rule-explanations-admin.php';
$admin = new WCJ_Rule_Explanations_Admin();
$service = new WCJ_Rule_Explanations();
$schema = $service->get_input_schema();
$returns_schema = ( new WCJ_Returns_Summary() )->get_input_schema();
examples_check( 'real fee schema basic keys', array_slice( array_keys( $schema['properties'] ), 0, 6 ), array( 'mode', 'gateway', 'subtotal', 'shipping', 'tax', 'shipping_tax' ) );
examples_check( 'real fee schema rejects extra fields', $schema['additionalProperties'], false );
examples_check( 'real fee schema role rights', isset( $schema['properties']['role'] ), 'elite' === $tier );
examples_check( 'real fee schema product rights', isset( $schema['properties']['product_ids'] ), 'elite' === $tier );
examples_check( 'real summary schema sole field', array_keys( $returns_schema['properties'] ), array( 'scope' ) );
examples_check( 'real summary schema exact value', $returns_schema['properties']['scope']['enum'], array( 'summary' ) );
examples_check( 'real summary schema no extra fields', $returns_schema['additionalProperties'], false );
examples_check( 'schema access no option/business read', array_sum( $GLOBALS['examples_counts'] ), 0 );

// Fixed literal requirements, never obtained from rendered output or rewritten from a run.
$expected = array(
	'Example questions for an authorized client',
	'These are static usage examples, not executed requests or results. A compatible client must map the question to the structured Ability inputs. Natural-language support and a working connection are not established here. Actual use remains subject to current permissions, module and role eligibility.',
	'Explain a configured gateway fee',
	'For the configured gateway identifier I supply, explain the fee or discount with merchandise total 100 after discounts excluding tax, shipping total 5 excluding tax, merchandise subtotal tax 10 before discounts, and shipping tax 0.5.',
	'Use booster/explain-gateway-fee with mode=scenario, your configured gateway identifier in gateway, subtotal=100, shipping=5, tax=10 and shipping_tax=0.5. These illustrative amounts are merchant-supplied store-currency facts, not a shopper or order lookup. Supply every amount explicitly, including zero when applicable.',
	'The basic example assumes guest with no supplied product IDs. Free and Plus do not accept role or product_ids inputs; product-dependent rules are unsupported in basic scenarios. For the same known guest/empty example on Elite, also supply role=guest and product_ids=[]; other role/product conditions require explicit applicable facts. Dynamic shortcode titles and active multicurrency are unsupported. The evaluator amount is before WooCommerce cart tax/rounding, not the final payable total or proof that a gateway is available.',
	'Summarize the bounded Returns sample',
	'Show status counts for the latest up to 50 return request IDs and how many sampled requests currently need review or have unavailable facts. Tell me whether the sample may be truncated.',
	'Use booster/returns-summary with exactly scope=summary and no additional inputs. Request status counts and the current needs_review and facts_unavailable counts, not request IDs, customers, reasons or amounts.',
	'The sample follows latest request IDs, not recent update time or whole-store totals. At 50 rows, possibly_truncated=true means there may be more rows, not that more rows definitely exist. Current flags are sequential reads, not an atomic snapshot. Unavailable counts are null, not zero. The summary never changes returns, orders, stock, refunds or notifications.',
);
$cases = array( 'normal', 'denied', 'logged-out', 'not-admin', 'rest', 'rest-exception', 'unregistered', 'metadata-unknown', 'no-configured-gateway', 'missing-producer', 'current-role-restricted', 'hostile-translation' );
$observations = array();
foreach ( $cases as $case ) {
	examples_reset();
	switch ( $case ) {
		case 'denied': $GLOBALS['examples_case']['capable'] = false; break;
		case 'logged-out': $GLOBALS['examples_case']['logged'] = false; break;
		case 'not-admin': $GLOBALS['examples_case']['admin'] = false; break;
		case 'rest': $GLOBALS['examples_case']['rest'] = true; break;
		case 'rest-exception': $GLOBALS['examples_case']['rest_throw'] = true; break;
		case 'unregistered': $GLOBALS['examples_case']['registered'] = false; break;
		case 'metadata-unknown': $GLOBALS['examples_case']['meta_throw'] = true; break;
		case 'no-configured-gateway': $GLOBALS['examples_case']['gateways'] = false; break;
		case 'missing-producer': $GLOBALS['examples_case']['module_present'] = false; break;
		case 'current-role-restricted': $GLOBALS['examples_case']['role_allowed'] = false; break;
		case 'hostile-translation': $GLOBALS['examples_case']['hostile_translation'] = true; break;
	}
	$_GET = array( 'gateway' => '<script>private-gateway</script>', 'customer' => 'private-customer', 'credential' => 'private-credential' );
	ob_start();
	$denied = false;
	try { $admin->render_page(); } catch ( Examples_Denied $error ) { $denied = true; }
	$html = ob_get_clean();
	$admitted = 'missing-rest-helper' !== $mode && ! in_array( $case, array( 'denied', 'logged-out', 'not-admin', 'rest', 'rest-exception' ), true );
	$match = array();
	preg_match( '/<section class="wcj-ability-examples">(.*?)<\/section>/s', $html, $match );
	$block = $match[0] ?? '';
	examples_check( $case . ' example admission', '' !== $block, $admitted );
	examples_check( $case . ' retained denial', $denied, 'denied' === $case );
	if ( $admitted ) {
		foreach ( $expected as $i => $text ) {
			examples_check( $case . ' exact escaped text ' . $i, false !== strpos( $block, esc_html( ( 'hostile-translation' === $case ? '<script>synthetic-translation</script>' : '' ) . $text ) ), true );
			examples_check( $case . ' translated domain ' . $i, in_array( array( $text, 'woocommerce-jetpack' ), $GLOBALS['examples_translations'], true ), true );
		}
		examples_check( $case . ' fixed fee ID', false !== strpos( $block, '<code>booster/explain-gateway-fee</code>' ), true );
		examples_check( $case . ' fixed summary ID', false !== strpos( $block, '<code>booster/returns-summary</code>' ), true );
		examples_check( $case . ' no interactive/request controls', preg_match( '/<(?:form|input|button|script|iframe|a)\b/i', $block ), 0 );
	}
	examples_check( $case . ' no input or exception spill', preg_match( '/private-(?:gateway|customer|credential|metadata-error|rest-error)/', $html ), 0 );
	examples_check( $case . ' no Ability/payment/data actions', array_intersect_key( $GLOBALS['examples_counts'], array_flip( array( 'executions', 'permissions', 'evaluations', 'writes', 'credentials', 'network' ) ) ), array( 'executions' => 0, 'permissions' => 0, 'evaluations' => 0, 'writes' => 0, 'credentials' => 0, 'network' => 0 ) );
	// Existing GET renderer reads are not mislabeled as zero reads or native security proof.
	if ( 'denied' === $case ) {
		examples_check( $case . ' no private renderer reads', array_sum( $GLOBALS['examples_counts'] ), 0 );
	} else {
		examples_check( $case . ' unchanged option read count', $GLOBALS['examples_counts']['options'], 1 );
		examples_check( $case . ' unchanged role metadata count', $GLOBALS['examples_counts']['roles'], 'elite' === $tier ? 1 : 0 );
		examples_check( $case . ' unchanged form count', substr_count( $html, '<form ' ), 'no-configured-gateway' === $case ? 0 : 1 );
		examples_check( $case . ' unchanged snapshot reads', $GLOBALS['examples_counts']['producer_reads'], in_array( $case, array( 'no-configured-gateway', 'missing-producer', 'current-role-restricted' ), true ) ? 0 : 1 );
		if ( 'current-role-restricted' === $case ) {
			examples_check( $case . ' retained service unavailability', false !== strpos( $html, 'module_disabled' ), true );
		}
	}
	$observations[] = array( 'case' => $case, 'admitted' => $admitted, 'counters' => $GLOBALS['examples_counts'], 'html_sha256' => hash( 'sha256', $html ), 'example_sha256' => hash( 'sha256', $block ) );
}
$failed = array_values( array_filter( $GLOBALS['examples_checks'], static function ( $check ) { return ! $check['pass']; } ) );
echo json_encode( array( 'tier' => $tier, 'mode' => $mode, 'pass' => count( $GLOBALS['examples_checks'] ) - count( $failed ), 'total' => count( $GLOBALS['examples_checks'] ), 'failures' => $failed, 'checks' => $GLOBALS['examples_checks'], 'observations' => $observations, 'limitations' => 'Actual renderer and fixed schema methods; synthetic capabilities/roles/helper declarations/nonce. Existing GET service reads retained. No native WordPress, actual role-policy execution, credential, connector, Ability execution or visual proof.' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
exit( $failed ? 1 : 0 );

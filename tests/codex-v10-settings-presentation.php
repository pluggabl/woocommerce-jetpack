<?php
/** CLI-only deterministic metadata/native-renderer fixture, not WordPress runtime QA. */
if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', __DIR__ );
define( 'WCJ_IS_WC_VERSION_BELOW_3', in_array( '--old-sort', $argv, true ) );
$options     = array();
$reads       = 0;
$conversions = 0;
$assets      = array();
$localized   = array();

$script_assets = array();

if ( in_array( '--text-products', $argv, true ) ) { $options['wcj_list_for_products'] = 'no'; }
function __( $text, $domain = '' ) { return $text; }
function get_option( $id, $default = '', $unused = null ) { global $options, $reads; ++$reads; return array_key_exists( $id, $options ) ? $options[ $id ] : $default; }
function wcj_get_option( $id, $default = '', $unused = null ) { return get_option( $id, $default ); }
function update_option() { throw new RuntimeException( 'Presentation must not write options.' ); }
function apply_filters( $hook, $value, ...$rest ) { return $value; }
function get_terms() { return array( (object) array( 'term_id' => 1, 'name' => 'Fixture category' ) ); }
function is_wp_error() { return false; }
function wcj_get_terms() { return array( 1 => 'Fixture term' ); }
function wcj_get_products() { return array( 10 => 'Fixture product' ); }
function wc_get_product_types() { return array( 'simple' => 'Simple' ); }
function wcj_maybe_convert_and_update_option_value() { global $conversions; ++$conversions; }
function wcj_get_settings_as_multiselect_or_text( $setting, $products, $multi ) { return array_merge( $setting, $multi ? array( 'type' => 'multiselect', 'options' => $products ) : array( 'type' => 'text' ) ); }
function wp_enqueue_style( $handle, ...$args ) { global $assets; $assets[] = $handle; }
function wp_enqueue_script( $handle, ...$args ) {
	global $assets, $script_assets;
	$assets[]                 = $handle;
	$script_assets[ $handle ] = $args;
}
function wp_script_is() { return true; }
function wp_localize_script( $handle, $name, $value ) { global $localized; $localized[ $name ] = $value; }
function wcj_plugin_url() { return '/fixture-plugin'; }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_url( $text ) { return $text; }
function wp_kses_post( $text ) { return $text; }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function wp_unslash( $text ) { return stripslashes( $text ); }
function sanitize_text_field( $text ) { return $text; }
function sanitize_key( $text ) { return $text; }
function wp_verify_nonce() { return false; }
function check_fixture( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
require __DIR__ . '/../includes/admin/class-wcj-settings-presentation.php';

// Execute the exact native output method without constructing WordPress hooks.
$source = file_get_contents( __DIR__ . '/../includes/core/class-wcj-admin.php' );
$start  = strpos( $source, 'public function output_settings(' );
$tokens = token_get_all( '<?php ' . substr( $source, $start ) );
$method = '';
$depth  = 0;
$opened = false;
foreach ( $tokens as $token ) {
	if ( is_array( $token ) && T_OPEN_TAG === $token[0] ) { continue; }
	$method .= is_array( $token ) ? $token[1] : $token;
	if ( '{' === $token ) { ++$depth; $opened = true; }
	if ( '}' === $token && 0 === --$depth && $opened ) { break; }
}
// __DIR__ in the extracted method must retain its actual source location.
$method = str_replace( '__DIR__', var_export( dirname( __DIR__ ) . '/includes/core', true ), $method );
eval( 'class NativeFixture { public $settings; public $calls = 0; public function get_settings($section) { ++$this->calls; return $this->settings; } public function is_dashboard_section($section) { return false; } public function enhance_settings_for_module($settings, $section) { return $settings; } ' . $method . ' }' );
$fixture = new NativeFixture();
$result  = array();
foreach ( array( 'add_to_cart' => 5, 'more_button_labels' => 0, 'price_labels' => 3, 'related_products' => 3 ) as $section => $count ) {
	$file     = str_replace( '_', '-', $section );
	$settings = require __DIR__ . '/../includes/settings/wcj-settings-' . $file . '.php';
	$before   = serialize( $settings );
	$read_at  = $reads;
	$config   = WCJ_Settings_Presentation::get_config( $section, $settings );
	check_fixture( count( $config['groups'] ) === $count, $section . ': exact eligible groups' );
	check_fixture( serialize( $settings ) === $before && $reads === $read_at, 'metadata must not mutate definitions or read options' );
	$fixture->settings = $settings;
	$fixture->calls    = 0;
	ob_start();
	$fixture->output_settings( $section );
	$html = ob_get_clean();
	check_fixture( 1 === $fixture->calls, 'definitions must be evaluated exactly once' );
	$result[ $section ] = array( 'settings' => $settings, 'config' => $config, 'html' => $html );
	if ( $count ) {
		$first_id = key( $config['groups'][0]['fields'] );
		foreach ( $settings as $i => $setting ) {
			if ( isset( $setting['id'] ) && $first_id === $setting['id'] ) { break; }
		}
		$duplicate   = $settings;
		$duplicate[] = $settings[ $i ];
		check_fixture( count( WCJ_Settings_Presentation::get_config( $section, $duplicate )['groups'] ) < $count, 'duplicate ID group stays native' );
		$altered = $settings;
		$altered[ $i ]['type'] = 'extension_custom_html';
		check_fixture( count( WCJ_Settings_Presentation::get_config( $section, $altered )['groups'] ) < $count, 'unsupported types stay native' );
		if ( count( $config['groups'][0]['fields'] ) > 1 ) {
			$injected = $settings;
			array_splice( $injected, $i + 1, 0, array( array( 'id' => 'extension_control', 'type' => 'text', 'default' => '' ) ) );
			check_fixture( count( WCJ_Settings_Presentation::get_config( $section, $injected )['groups'] ) < $count, 'injected definitions stay visible, not grouped' );
		}
	}
}
check_fixture( array() === WCJ_Settings_Presentation::get_config( 'unknown', array() ), 'unsupported module has no metadata' );
WCJ_Settings_Presentation::enqueue( 'wc-settings', 'price_labels' );
WCJ_Settings_Presentation::enqueue( 'wcj-plugins', 'unknown' );
check_fixture( array() === $assets, 'legacy and unrelated pages enqueue no presentation assets' );
WCJ_Settings_Presentation::enqueue( 'wcj-plugins', 'price_labels' );
check_fixture( array( 'wcj-settings-presentation', 'wcj-settings-presentation' ) === $assets, 'only supported modern route enqueues two assets' );
check_fixture( array( '/fixture-plugin/assets/js/admin/wcj-settings-presentation.js', array( 'wcj-admin-new-script' ), '1.3', true ) === $script_assets['wcj-settings-presentation'], 'changed presentation JavaScript uses cache version 1.3 with its exact native dependency and footer contract' );
check_fixture( 1 === $conversions, 'related-products inherited conversion was evaluated once, never repeated by metadata' );
if ( in_array( '--fixtures', $argv, true ) ) { echo json_encode( $result ); }
else { echo "PASS: exact native definitions/renderer and read-free metadata fixture; not WordPress runtime QA.\n"; }

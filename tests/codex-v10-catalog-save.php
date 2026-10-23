<?php
/**
 * Source-bound Save routing regression, not native WordPress/WooCommerce QA.
 *
 * Run with PHP CLI and an optional trusted local WCJ_Admin source path. Only its
 * final instance construction is removed; both Save methods remain real. The
 * bounded doubles do not prove native nonces, full sanitization or HTTP behavior.
 *
 * @package Booster_For_WooCommerce/Tests
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

define( 'ABSPATH', __DIR__ );
require __DIR__ . '/fixtures/codex-v10-catalog-save/functions.php';
require __DIR__ . '/fixtures/codex-v10-catalog-save/class-wc-admin-settings.php';

$source_path = realpath( $argv[1] ?? dirname( __DIR__ ) . '/includes/core/class-wcj-admin.php' );
if ( false === $source_path || ! is_file( $source_path ) || ! is_readable( $source_path ) || 'php' !== pathinfo( $source_path, PATHINFO_EXTENSION ) ) {
	throw new LogicException( 'A trusted local PHP source file is required.' );
}
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Verified local CLI source file, never a remote URL.
$source = file_get_contents( $source_path );
if ( false === $source || '<?php' !== substr( $source, 0, 5 ) || 1 !== substr_count( $source, 'return new WCJ_Admin();' ) || 1 !== preg_match( '/return new WCJ_Admin\(\);\s*$/', $source ) ) {
	throw new LogicException( 'Inspect the exact production source bootstrap marker.' );
}
$source_hash = hash( 'sha256', $source );
// phpcs:ignore Squiz.PHP.Eval.Discouraged -- Trusted, hash-recorded local source; remove only final unrelated instance construction, retaining both real Save methods.
eval( substr( str_replace( 'return new WCJ_Admin();', '', $source ), 5 ) );
require __DIR__ . '/fixtures/codex-v10-catalog-save/class-catalog-save-fixture.php';

$GLOBALS['codex_catalog_save_checks'] = array();
$fixture                              = new Catalog_Save_Fixture();
$baseline                             = array(
	'wcj_add_to_cart_enabled'  => 'yes',
	'wcj_price_labels_enabled' => 'yes',
	'wcj_unrelated_custom'     => array( '', 'keep', false ),
);
$switch_cases                         = array( array(), array( 'wcj_add_to_cart_enabled' => 'no' ), array( 'wcj_add_to_cart_enabled' => 'yes' ) );

foreach ( array( 'wcj_save_module_settings', 'wcj_save_other_modules_key_settings' ) as $method ) {
	$reflection = new ReflectionMethod( $fixture, $method );
	codex_catalog_save_check( 'WCJ_Admin' === $reflection->getDeclaringClass()->getName(), $method . ': inherited from the hash-recorded real source class' );
}

// Retain the nine original partial-form cases and their four original checks.
foreach ( array( null, '', 'active' ) as $section ) {
	foreach ( $switch_cases as $index => $switches ) {
		$label = 'catalog ' . ( null === $section ? 'missing' : ( '' === $section ? 'empty' : $section ) ) . ' switches ' . $index;
		codex_catalog_save_reset( $baseline );
		$_POST = array_merge( array( 'wcj-verify-save-module-settings' => wp_create_nonce( 'wcj-verify-save-module-settings' ) ), $switches );
		if ( null !== $section ) {
			$_POST['section'] = $section;
		}
		codex_catalog_save_expect_redirect( $fixture, $label );
		$state = $GLOBALS['codex_catalog_save'];
		codex_catalog_save_check( array_replace( $baseline, $switches ) === $state['options'], $label . ': only explicitly submitted switches change' );
		codex_catalog_save_check( 0 === $state['field_saves'] && 0 === $state['schema_reads'] && 0 === $state['autoload_calls'], $label . ': no global schema or autoload work' );
		codex_catalog_save_check( ( null === $section ? 0 : 1 ) === count( $state['actions'] ), $label . ': existing hook count retained' );
		$expected_actions = null === $section ? array() : array( array( 'woojetpack_after_settings_save', array( $fixture->get_sections(), $section ) ) );
		codex_catalog_save_check( $expected_actions === $state['actions'], $label . ': exact hook name and payload' );
		codex_catalog_save_check( count( $switches ) === count( $state['updates'] ), $label . ': no implicit option writes' );
		codex_catalog_save_check( array( admin_url( 'admin.php?page=wcj-plugins&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) ) ) === $state['redirects'], $label . ': exact existing admin redirect' );
	}
}

codex_catalog_save_reset( $baseline );
$_POST = array(
	'section'                         => 'add_to_cart',
	'wcj-verify-save-module-settings' => wp_create_nonce( 'wcj-verify-save-module-settings' ),
	'wcj_add_to_cart_enabled'         => 'yes',
);
codex_catalog_save_expect_redirect( $fixture, 'complete module form' );
$state = $GLOBALS['codex_catalog_save'];
codex_catalog_save_check( 1 === $state['field_saves'] && 1 === $state['schema_reads'] && 1 === $state['autoload_calls'], 'module: native schema routing retained' );
codex_catalog_save_check( 'no' === $state['options']['wcj_price_labels_enabled'], 'module: absent-checkbox serializer behavior retained' );
codex_catalog_save_check( array( 'add_to_cart' ) === $state['schema_sections'], 'module: exact section supplied to schema' );
codex_catalog_save_check( array( array( 'woojetpack_after_settings_save', array( $fixture->get_sections(), 'add_to_cart' ) ) ) === $state['actions'], 'module: exact hook contract' );
codex_catalog_save_check( $baseline['wcj_unrelated_custom'] === $state['options']['wcj_unrelated_custom'], 'module: unrelated typed option preserved' );

foreach ( array( null, 'invalid', wp_create_nonce( 'different-action' ) ) as $index => $nonce ) {
	codex_catalog_save_reset( $baseline );
	$_POST = array(
		'section'                 => 'active',
		'wcj_add_to_cart_enabled' => 'no',
	);
	if ( null !== $nonce ) {
		$_POST['wcj-verify-save-module-settings'] = $nonce;
	}
	$fixture->wcj_save_module_settings();
	$state = $GLOBALS['codex_catalog_save'];
	codex_catalog_save_check( $baseline === $state['options'], 'nonce ' . $index . ': cannot mutate options' );
	codex_catalog_save_check( array() === $state['updates'] && array() === $state['actions'] && array() === $state['redirects'], 'nonce ' . $index . ': no writes, hooks or redirects' );
	codex_catalog_save_check( 0 === $state['field_saves'] && 0 === $state['schema_reads'] && 0 === $state['autoload_calls'], 'nonce ' . $index . ': serializer never reached' );
}

// A valid synthetic nonce must not authorize a caller without capabilities.
codex_catalog_save_reset( $baseline );
$GLOBALS['codex_catalog_save']['capabilities'] = array();
$_POST  = array(
	'section'                         => 'active',
	'wcj-verify-save-module-settings' => wp_create_nonce( 'wcj-verify-save-module-settings' ),
	'wcj_add_to_cart_enabled'         => 'no',
);
$denied = false;
try {
	$fixture->wcj_save_module_settings();
} catch ( DomainException $exception ) {
	$denied = 'denied' === $exception->getMessage();
}
$state = $GLOBALS['codex_catalog_save'];
codex_catalog_save_check( $denied && 403 === $state['denials'][0][2]['response'], 'capability: production denial is HTTP 403' );
codex_catalog_save_check( $baseline === $state['options'] && array() === $state['updates'], 'capability: valid nonce cannot grant write permission' );
codex_catalog_save_check( array() === $state['actions'] && array() === $state['redirects'] && 0 === $state['schema_reads'], 'capability: denied request has no downstream work' );

// Exercise both ordinary capability branches using only local request doubles.
foreach ( array( 'no', 'yes' ) as $admin_tools ) {
	foreach ( array( 'no', 'yes' ) as $admin_only ) {
		$required = 'yes' === $admin_tools && 'yes' === $admin_only ? 'manage_options' : 'manage_woocommerce';
		foreach ( array( 'manage_woocommerce', 'manage_options' ) as $granted ) {
			$options = array_merge(
				$baseline,
				array(
					'wcj_admin_tools_enabled' => $admin_tools,
					'wcj_admin_tools_show_menus_to_admin_only' => $admin_only,
				)
			);
			codex_catalog_save_reset( $options );
			$GLOBALS['codex_catalog_save']['capabilities'] = array( $granted => true );
			$_POST = array(
				'section'                         => 'active',
				'wcj-verify-save-module-settings' => wp_create_nonce( 'wcj-verify-save-module-settings' ),
				'wcj_add_to_cart_enabled'         => 'no',
			);
			$label = 'capability tools=' . $admin_tools . ' admin-only=' . $admin_only . ' granted=' . $granted;
			if ( $required === $granted ) {
				codex_catalog_save_expect_redirect( $fixture, $label );
				codex_catalog_save_check( array_replace( $options, array( 'wcj_add_to_cart_enabled' => 'no' ) ) === $GLOBALS['codex_catalog_save']['options'], $label . ': authorized explicit switch persists' );
			} else {
				$denied = false;
				try {
					$fixture->wcj_save_module_settings();
				} catch ( DomainException $exception ) {
					$denied = 'denied' === $exception->getMessage();
				}
				codex_catalog_save_check( $denied && $options === $GLOBALS['codex_catalog_save']['options'] && array() === $GLOBALS['codex_catalog_save']['updates'], $label . ': other capability does not grant access' );
			}
			codex_catalog_save_check( array( $required ) === $GLOBALS['codex_catalog_save']['capability_reads'], $label . ': exact menu-equivalent capability requested' );
		}
	}
}

// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Standalone CLI JSON evidence without the WordPress JSON layer.
echo json_encode(
	array(
		'status'        => 'PASS',
		'checks'        => count( $GLOBALS['codex_catalog_save_checks'] ),
		'check_labels'  => $GLOBALS['codex_catalog_save_checks'],
		'source_path'   => $source_path,
		'source_sha256' => $source_hash,
		'scope'         => 'Source-bound routing and explicit-key persistence doubles; not native HTTP/WooCommerce acceptance.',
	),
	JSON_UNESCAPED_SLASHES
) . PHP_EOL;

<?php
/**
 * Real-WordPress localization integration checks. Not a runtime plugin file.
 *
 * Run in a disposable installed WordPress using WP-CLI --skip-plugins --skip-themes:
 * WCJ_LOCALIZATION_TEST_ISOLATED=1 wp eval-file tests/localization/integration.php community
 * One fresh PHP process per scenario. Never run on merchant/staging/production data.
 *
 * @package Booster_For_WooCommerce/Tests
 */

if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'WCJ_LOCALIZATION_TEST_ISOLATED' ) ) {
	throw new RuntimeException( 'Requires real WordPress and explicit isolated-test environment flag.' );
}

$wcj_test_scenario = isset( $args[0] ) ? $args[0] : 'community';
$wcj_test_allowed  = array( 'no_pack', 'community', 'custom_redirect', 'full_override', 'php_preference', 'switch_restore', 'site_admin', 'english', 'explicit_reload' );
if ( ! in_array( $wcj_test_scenario, $wcj_test_allowed, true ) ) {
	throw new RuntimeException( 'Unknown scenario.' );
}
if ( class_exists( 'WCJ_Localization', false ) || is_textdomain_loaded( 'woocommerce-jetpack' ) ) {
	throw new RuntimeException( 'Start a fresh PHP process with all Booster plugins skipped.' );
}

/** Simple assertions with expected-versus-actual evidence. */
function wcj_l10n_assert( $label, $expected, $actual ) {
	$GLOBALS['wcj_l10n_checks'][] = array( 'label' => $label, 'expected' => $expected, 'actual' => $actual, 'pass' => $expected === $actual );
	if ( $expected !== $actual ) {
		throw new RuntimeException( $label . ': ' . wp_json_encode( array( 'expected' => $expected, 'actual' => $actual ) ) );
	}
}

/** Compile synthetic fixture messages using the documented GNU MO binary layout. */
function wcj_l10n_fixture_mo( $messages, $locale = 'nl_NL' ) {
	$messages[''] = "Project-Id-Version: Booster synthetic locale test\nLanguage: {$locale}\nContent-Type: text/plain; charset=UTF-8\nPlural-Forms: nplurals=2; plural=(n != 1);\n";
	ksort( $messages, SORT_STRING );
	$count        = count( $messages );
	$offset       = 28 + 16 * $count;
	$originals    = '';
	$translations = '';
	$original_map = '';
	$target_map   = '';
	foreach ( $messages as $key => $value ) {
		$original_map .= pack( 'V2', strlen( $key ), $offset + strlen( $originals ) );
		$originals   .= $key . "\0";
	}
	$offset += strlen( $originals );
	foreach ( $messages as $value ) {
		$target_map   .= pack( 'V2', strlen( $value ), $offset + strlen( $translations ) );
		$translations .= $value . "\0";
	}
	return pack( 'V7', 0x950412de, 0, $count, 28, 28 + 8 * $count, 0, 0 ) . $original_map . $target_map . $originals . $translations;
}

/** Create only absent fixture paths; never overwrite an existing language pack. */
function wcj_l10n_fixture_write( $path, $contents ) {
	$handle = fopen( $path, 'x' );
	if ( false === $handle ) {
		throw new RuntimeException( 'Refusing to overwrite an existing file: ' . $path );
	}
	fwrite( $handle, $contents );
	fclose( $handle );
	$GLOBALS['wcj_l10n_created_files'][] = $path;
	$GLOBALS['wcj_l10n_fixture_hashes'][ $path ] = hash_file( 'sha256', $path );
}

/** Make only absent fixture directories, retaining their explicit cleanup list. */
function wcj_l10n_fixture_mkdir( $path ) {
	if ( ! is_dir( $path ) ) {
		if ( ! mkdir( $path ) ) {
			throw new RuntimeException( 'Cannot create fixture directory: ' . $path );
		}
		$GLOBALS['wcj_l10n_created_dirs'][] = $path;
	}
}

$GLOBALS['wcj_l10n_checks']         = array();
$GLOBALS['wcj_l10n_created_files']  = array();
$GLOBALS['wcj_l10n_created_dirs']   = array();
$GLOBALS['wcj_l10n_fixture_hashes'] = array();
$wcj_test_error                     = null;
$wcj_test_user                      = 0;
$wcj_test_fixture                   = WP_PLUGIN_DIR . '/wcj-l10n-fixture-' . wp_generate_uuid4();
$wcj_test_helper                    = dirname( __DIR__, 2 ) . '/includes/core/class-wcj-localization.php';
$wcj_test_protected_options         = array( 'wcj_invoicing_invoice_template', 'wcj_invoicing_invoice_numbering_counter', 'wcj_invoicing_invoice_header_title_text', 'wcj_checkout_custom_field_label_1' );
$wcj_test_before                    = array();
foreach ( $wcj_test_protected_options as $wcj_test_key ) {
	$wcj_test_before[ $wcj_test_key ] = get_option( $wcj_test_key );
}

try {
	wcj_l10n_fixture_mkdir( WP_LANG_DIR );
	wcj_l10n_fixture_mkdir( WP_LANG_DIR . '/plugins' );
	wcj_l10n_fixture_mkdir( $wcj_test_fixture );
	wcj_l10n_fixture_mkdir( $wcj_test_fixture . '/langs' );
	wcj_l10n_fixture_write( $wcj_test_fixture . '/booster.php', "<?php // Isolated synthetic fixture, never activated.\n" );
	$wcj_test_bundle = array(
		'Fixture shared' => 'Bundel gedeeld',
		'Fixture paid only' => 'Alleen in betaald pakket',
		'Fixture exact English' => 'Bundel vertaald Engels',
		"Reset filters\4Clear" => 'Filters wissen',
		"Reset form\4Clear" => 'Formulier wissen',
		"Clear floated form-field layout\4Clear" => 'Nieuwe regel na veld',
		"%d day\0%d days" => "%d dag\0%d dagen",
	);
	wcj_l10n_fixture_write( $wcj_test_fixture . '/langs/woocommerce-jetpack-nl_NL.mo', wcj_l10n_fixture_mo( $wcj_test_bundle ) );
	$wcj_test_primary = WP_LANG_DIR . '/plugins/woocommerce-jetpack-nl_NL.mo';
	if ( ! in_array( $wcj_test_scenario, array( 'no_pack', 'english' ), true ) ) {
		wcj_l10n_fixture_write( $wcj_test_primary, wcj_l10n_fixture_mo( array( 'Fixture shared' => 'Community gedeeld', 'Fixture exact English' => 'Fixture exact English' ) ) );
	}
	if ( 'php_preference' === $wcj_test_scenario ) {
		$wcj_test_php = array( 'language' => 'nl_NL', 'plural-forms' => 'nplurals=2; plural=(n != 1);', 'messages' => array( 'Fixture shared' => 'PHP community gedeeld', 'Fixture exact English' => 'Fixture exact English' ) );
		wcj_l10n_fixture_write( WP_LANG_DIR . '/plugins/woocommerce-jetpack-nl_NL.l10n.php', '<?php return ' . var_export( $wcj_test_php, true ) . ';' );
	}
	if ( in_array( $wcj_test_scenario, array( 'custom_redirect', 'full_override' ), true ) ) {
		$wcj_test_custom = $wcj_test_fixture . '/custom-nl_NL.mo';
		wcj_l10n_fixture_write( $wcj_test_custom, wcj_l10n_fixture_mo( array( 'Fixture shared' => 'Merchant custom', 'Fixture exact English' => 'Fixture exact English' ) ) );
		add_filter(
			'load_textdomain_mofile',
			function ( $file, $domain ) use ( $wcj_test_custom, $wcj_test_primary, $wcj_test_scenario ) {
				if ( 'woocommerce-jetpack' === $domain && ( 'full_override' === $wcj_test_scenario || $file === $wcj_test_primary ) ) {
					return $wcj_test_custom;
				}
				return $file;
			},
			10,
			2
		);
	}
	// Install a synthetic available-language marker only if no real one exists.
	if ( ! file_exists( WP_LANG_DIR . '/nl_NL.mo' ) ) {
		wcj_l10n_fixture_write( WP_LANG_DIR . '/nl_NL.mo', wcj_l10n_fixture_mo( array() ) );
	}
	$wcj_test_original_switcher = $GLOBALS['wp_locale_switcher'];
	remove_filter( 'locale', array( $wcj_test_original_switcher, 'filter_locale' ) );
	remove_filter( 'determine_locale', array( $wcj_test_original_switcher, 'filter_locale' ) );
	$wcj_test_site_locale = 'english' === $wcj_test_scenario || 'site_admin' === $wcj_test_scenario ? 'en_US' : 'nl_NL';
	add_filter( 'locale', function () use ( $wcj_test_site_locale ) { return $wcj_test_site_locale; }, 5 );
	$GLOBALS['wp_locale_switcher'] = new WP_Locale_Switcher();
	$GLOBALS['wp_locale_switcher']->init();

	if ( 'site_admin' === $wcj_test_scenario ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		$wcj_test_user = wp_insert_user( array( 'user_login' => 'wcj-l10n-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password( 40 ), 'role' => 'administrator', 'locale' => 'nl_NL' ) );
		if ( is_wp_error( $wcj_test_user ) ) {
			throw new RuntimeException( 'Could not create isolated locale user.' );
		}
		wp_set_current_user( $wcj_test_user );
		set_current_screen( 'dashboard' );
		wcj_l10n_assert( 'Site remains English', 'en_US', get_locale() );
		wcj_l10n_assert( 'Admin user locale selected', 'nl_NL', determine_locale() );
	}

	require_once $wcj_test_helper;
	$wcj_test_loader = new WCJ_Localization( $wcj_test_fixture . '/booster.php' );
	if ( 'english' === $wcj_test_scenario ) {
		wcj_l10n_assert( 'English remains English', 'Fixture paid only', __( 'Fixture paid only', 'woocommerce-jetpack' ) );
	} else {
		$wcj_test_expected_shared = 'no_pack' === $wcj_test_scenario ? 'Bundel gedeeld' : ( in_array( $wcj_test_scenario, array( 'custom_redirect', 'full_override' ), true ) ? 'Merchant custom' : ( 'php_preference' === $wcj_test_scenario ? 'PHP community gedeeld' : 'Community gedeeld' ) );
		wcj_l10n_assert( 'Primary overlapping key wins', $wcj_test_expected_shared, __( 'Fixture shared', 'woocommerce-jetpack' ) );
		wcj_l10n_assert( 'Missing paid key fallback or explicit override respected', 'full_override' === $wcj_test_scenario ? 'Fixture paid only' : 'Alleen in betaald pakket', __( 'Fixture paid only', 'woocommerce-jetpack' ) );
		if ( 'full_override' !== $wcj_test_scenario ) {
			wcj_l10n_assert( 'Unknown key uses source English', 'Never in a fixture', __( 'Never in a fixture', 'woocommerce-jetpack' ) );
			wcj_l10n_assert( 'Reset context distinct', 'Filters wissen', _x( 'Clear', 'Reset filters', 'woocommerce-jetpack' ) );
			wcj_l10n_assert( 'Layout context distinct', 'Nieuwe regel na veld', _x( 'Clear', 'Clear floated form-field layout', 'woocommerce-jetpack' ) );
			foreach ( array( 0, 1, 2, 10, 101 ) as $wcj_test_count ) {
				wcj_l10n_assert( 'Dutch plural ' . $wcj_test_count, 1 === $wcj_test_count ? '%d dag' : '%d dagen', _n( '%d day', '%d days', $wcj_test_count, 'woocommerce-jetpack' ) );
			}
		}
		if ( 'no_pack' !== $wcj_test_scenario ) {
			wcj_l10n_assert( 'Primary intentional English is not replaced', 'Fixture exact English', __( 'Fixture exact English', 'woocommerce-jetpack' ) );
		}
		$wcj_test_loader->load();
		$wcj_test_loader->load();
		wcj_l10n_assert( 'Repeated loading preserves primary', $wcj_test_expected_shared, __( 'Fixture shared', 'woocommerce-jetpack' ) );
	}
	if ( 'switch_restore' === $wcj_test_scenario ) {
		wcj_l10n_assert( 'Switch to English', true, switch_to_locale( 'en_US' ) );
		wcj_l10n_assert( 'No Dutch leak after switch', 'Fixture paid only', __( 'Fixture paid only', 'woocommerce-jetpack' ) );
		wcj_l10n_assert( 'Restore Dutch', 'nl_NL', restore_previous_locale() );
		wcj_l10n_assert( 'Primary survives restore', 'Community gedeeld', __( 'Fixture shared', 'woocommerce-jetpack' ) );
		wcj_l10n_assert( 'Paid fallback survives restore', 'Alleen in betaald pakket', __( 'Fixture paid only', 'woocommerce-jetpack' ) );
		wcj_l10n_assert( 'Nested switch English', true, switch_to_locale( 'en_US' ) );
		wcj_l10n_assert( 'Nested switch Dutch', true, switch_to_locale( 'nl_NL' ) );
		wcj_l10n_assert( 'Nested restore English', 'en_US', restore_previous_locale() );
		wcj_l10n_assert( 'Nested no Dutch leak', 'Fixture shared', __( 'Fixture shared', 'woocommerce-jetpack' ) );
		wcj_l10n_assert( 'Nested restore Dutch', 'nl_NL', restore_previous_locale() );
	}
	if ( 'explicit_reload' === $wcj_test_scenario ) {
		unload_textdomain( 'woocommerce-jetpack' );
		$wcj_test_loader->load();
		wcj_l10n_assert( 'Intentional non-reloadable unload respected', 'Fixture shared', __( 'Fixture shared', 'woocommerce-jetpack' ) );
		wcj_l10n_assert( 'Intentional unload does not resurrect paid fallback', 'Fixture paid only', __( 'Fixture paid only', 'woocommerce-jetpack' ) );
	}
	foreach ( $GLOBALS['wcj_l10n_fixture_hashes'] as $wcj_test_path => $wcj_test_hash ) {
		wcj_l10n_assert( 'No catalog write: ' . basename( $wcj_test_path ), $wcj_test_hash, hash_file( 'sha256', $wcj_test_path ) );
	}
	foreach ( $wcj_test_before as $wcj_test_key => $wcj_test_value ) {
		wcj_l10n_assert( 'Saved option unchanged: ' . $wcj_test_key, $wcj_test_value, get_option( $wcj_test_key ) );
	}
} catch ( Throwable $wcj_test_exception ) {
	$wcj_test_error = $wcj_test_exception->getMessage();
} finally {
	// Only paths created exclusively by this run are removed; no recursive delete.
	foreach ( array_reverse( $GLOBALS['wcj_l10n_created_files'] ) as $wcj_test_path ) {
		unlink( $wcj_test_path );
	}
	foreach ( array_reverse( $GLOBALS['wcj_l10n_created_dirs'] ) as $wcj_test_path ) {
		rmdir( $wcj_test_path );
	}
	if ( is_int( $wcj_test_user ) && $wcj_test_user > 0 ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $wcj_test_user );
	}
}

echo wp_json_encode( array( 'scenario' => $wcj_test_scenario, 'status' => null === $wcj_test_error ? 'PASS' : 'FAIL', 'error' => $wcj_test_error, 'wordpress' => get_bloginfo( 'version' ), 'php' => PHP_VERSION, 'helper_sha256' => hash_file( 'sha256', $wcj_test_helper ), 'checks' => $GLOBALS['wcj_l10n_checks'] ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
if ( null !== $wcj_test_error ) {
	throw new RuntimeException( $wcj_test_error );
}

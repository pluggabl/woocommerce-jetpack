<?php
/**
 * Source-bound catalog URL regression checks using real WordPress URL helpers.
 * Run with wp eval-file FILE SOURCE_ROOT. Read-only, not merchant Save acceptance.
 */
if ( ! defined( 'ABSPATH' ) || empty( $args[0] ) ) {
	throw new RuntimeException( 'Run under WordPress with the source root argument.' );
}
$template = file_get_contents( $args[0] . '/includes/admin/wcj-settings-plugins.php' );
$admin    = file_get_contents( $args[0] . '/includes/core/class-wcj-admin.php' );
if ( 1 !== preg_match( '/(\$wpnonce\s*=.*?\$search_url\s*=.*?;)/s', $template, $catalog ) ||
	1 !== preg_match( '/(\$active_tab = isset\( \$_POST\[\x27wcj_setting_active_tab\x27\].*?)wp_safe_redirect\( \$return_url \);/s', $admin, $redirect ) ) {
	throw new RuntimeException( 'Source pipeline markers changed; inspect fixture.' );
}
$checks = 0;
$check  = static function ( $condition, $label ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $label );
	}
	++$checks;
};
$original = array( $_GET, $_POST, $_REQUEST );
foreach ( array( 'Checkout Files Upload', 'invoice + label % café', 'label & invoice', '' ) as $query ) {
	foreach ( array( '', 'active' ) as $view ) {
		foreach ( array( '', 'labels' ) as $category ) {
			$_GET     = array( 'page' => 'wcj-plugins', 'wcj_search' => $query, 'section' => $view );
			$_GET['wcj-cat'] = $category;
			$_REQUEST = $_GET;
			eval( $catalog[1] );
			$_POST = array( 'return_url' => wp_slash( $search_url ), 'wcj_setting_active_tab' => 'main' );
			unset( $return_url );
			eval( $redirect[1] . '}' );
			parse_str( wp_parse_url( $return_url, PHP_URL_QUERY ), $decoded );
			$check( $query === ( $decoded['wcj_search'] ?? '' ), 'Query retained through source pipeline' );
			$check( $view === ( $decoded['section'] ?? '' ), 'All/My tools retained' );
			$check( $category === ( $decoded['wcj-cat'] ?? '' ), 'Category retained' );
			$check( '1' === $decoded['success'] && 'main' === $decoded['active_tab'], 'Save redirect metadata retained' );
			$check( $return_url === wp_validate_redirect( $return_url, admin_url() ), 'Same-host return allowed' );
		}
	}
}
foreach ( array( array( 'unexpected' ), null ) as $invalid ) {
	$_POST = array( 'return_url' => $invalid );
	unset( $return_url );
	eval( $redirect[1] . '}' );
	$check( ! isset( $return_url ), 'Non-string takes existing fallback branch without TypeError' );
}
$_POST = array( 'return_url' => 'https://outside.invalid/wp-admin/?wcj_search=invoice%20guide' );
eval( $redirect[1] . '}' );
$check( admin_url() === wp_validate_redirect( $return_url, admin_url() ), 'External return rejected by native safe-redirect validation' );
$check( false !== strpos( $template, 'esc_url( $search_url )' ), 'Hidden field uses shared catalog URL' );
list( $_GET, $_POST, $_REQUEST ) = $original;
echo wp_json_encode( array( 'status' => 'PASS', 'checks' => $checks, 'wordpress' => get_bloginfo( 'version' ), 'scope' => 'Actual WordPress URL functions and source-extracted pipeline. No native Save or persistent mutation.' ) ) . PHP_EOL;

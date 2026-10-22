<?php
/** Source-bound discovery fixture. Not WordPress/browser acceptance. */
define( 'ABSPATH', __DIR__ );
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_html( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function wp_unslash( $text ) { return stripslashes( $text ); }
function sanitize_text_field( $text ) { return strip_tags( $text ); }
function sanitize_key( $text ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $text ) ); }
function sanitize_title( $text ) { return sanitize_key( $text ); }
function wp_kses_post( $text ) { return $text; }
function wp_create_nonce( $action ) { return 'fixture'; }
function wp_verify_nonce( $nonce, $action ) { return true; }
function admin_url( $path = '' ) { return 'https://fixture.invalid/wp-admin/' . $path; }
function wcj_is_guided_admin_experience_enabled() { return true; }
function WCJ() { return (object) array( 'version' => '9.0.0' ); }
function wp_enqueue_style( $handle, $url, $deps, $version ) { $GLOBALS['assets'][] = array( $handle, $url, $version ); }
function wp_enqueue_script( $handle, $url, $deps, $version, $footer ) { $GLOBALS['assets'][] = array( $handle, $url, $version ); }
function wp_localize_script( $handle, $name, $data ) { $GLOBALS['labels'] = $data; }
function wcj_plugin_url() { return 'https://fixture.invalid/plugin'; }
function apply_filters( $name, $value ) { return $value; }
function wcj_get_option( $key, $default = '' ) { return isset( $GLOBALS['options'][ $key ] ) ? $GLOBALS['options'][ $key ] : $default; }
function wcj_check_modules_by_user_roles( $id ) { return ! isset( $GLOBALS['deny_pdf'] ) || ! $GLOBALS['deny_pdf']; }
function wcj_is_module_enabled( $id ) { return 'yes' === wcj_get_option( 'wcj_' . $id . '_enabled', 'no' ); }
function wcj_is_module_deprecated( $module, $unused = false, $unused_two = true ) { return 'old' === $module; }
function get_current_user_id() { return 1; }
function get_user_meta( $id, $key, $single = true ) { return 'wcj_getting_started_dismissed' === $key ? 'yes' : array(); }
function update_user_meta() { throw new Exception( 'Unexpected write' ); }
function update_option() { throw new Exception( 'Unexpected write' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
class WCJ_Invoice_Setup { public static function url() { return admin_url( 'admin.php?page=wcj-getting-started&wcj-invoice-setup=1' ); } }
class WCJ_Presets { public static function get_module_allowlist() { return array( 'pdf_invoicing', 'multicurrency' ); } public static function get_presets() { return array( array( 'id' => 'grow_sales', 'title' => 'Grow sales now' ) ); } }
require dirname( __DIR__ ) . '/includes/admin/class-wcj-module-filters.php';
require dirname( __DIR__ ) . '/includes/admin/class-wcj-getting-started-hub.php';
$checks = 0;
function check( $condition, $label ) {
    global $checks;
    if ( ! $condition ) { throw new Exception( $label ); }
    ++$checks;
}
// Extract the actual methods, not a second implementation of the catalog predicates.
function source_method( $source, $name ) {
    $tokens = token_get_all( $source );
    $start = false; $capture = ''; $depth = 0; $opened = false;
    foreach ( $tokens as $i => $token ) {
        if ( is_array( $token ) && T_FUNCTION === $token[0] ) {
            for ( $j = $i + 1; $j < count( $tokens ); ++$j ) {
                if ( is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) { continue; }
                $start = is_array( $tokens[ $j ] ) && $name === $tokens[ $j ][1];
                break;
            }
        }
        if ( ! $start ) { continue; }
        $text = is_array( $token ) ? $token[1] : $token;
        $capture .= $text;
        if ( '{' === $token ) { ++$depth; $opened = true; }
        if ( '}' === $token && 0 === --$depth ) { return 'public ' . $capture; }
    }
    throw new Exception( 'Missing method: ' . $name );
}
$source = file_get_contents( dirname( __DIR__ ) . '/includes/core/class-wcj-admin.php' );
$methods = '';
foreach ( array( 'output_modules', 'output_cats_submenu', 'get_cat_by_section', 'compare_for_usort' ) as $name ) {
    $methods .= source_method( $source, $name );
}
eval( 'class CatalogFixture { public $cats; public $module_statuses; ' . $methods . '}' );
$admin = new CatalogFixture();
$admin->cats = array();
foreach ( array( 'labels' => array( 'add_to_cart', 'more_button_labels', 'old' ), 'products' => array( 'related_products' ), 'pdf_invoicing' => array( 'pdf_invoicing' ) ) as $cat => $ids ) {
    $admin->cats[ $cat ] = array( 'all_cat_ids' => $ids, 'label' => $cat, 'icon' => 'fixture.png' );
}
$admin->module_statuses = array();
foreach ( array( 'add_to_cart' => 'Add to Cart', 'more_button_labels' => 'More Button Labels', 'related_products' => 'Related Products', 'pdf_invoicing' => 'PDF Invoicing', 'old' => 'Old Tool' ) as $id => $title ) {
    $admin->module_statuses[] = array( 'id' => 'wcj_' . $id . '_enabled', 'title' => $title, 'type' => 'checkbox', 'wcj_desc' => 'Fixture description', 'wcj_link' => 'https://fixture.invalid/docs/' );
}
$admin->module_statuses[] = array( 'id' => 'heading', 'title' => 'Not a module', 'type' => 'title' );
$GLOBALS['options'] = array( 'wcj_add_to_cart_enabled' => 'yes', 'wcj_related_products_enabled' => 'yes' );
$GLOBALS['wcj_module_filters'] = ( new ReflectionClass( 'WCJ_Module_Filters' ) )->newInstanceWithoutConstructor();
function catalog( $args, $skip = '' ) {
    global $admin;
    $_GET = $args; $_REQUEST = $args;
    ob_start(); $admin->output_modules( $skip ); return ob_get_clean();
}
function ids( $html ) { preg_match_all( '/data-module="([^"]+)"/', $html, $matches ); sort( $matches[1] ); return $matches[1]; }
check( array( 'add_to_cart', 'more_button_labels', 'pdf_invoicing', 'related_products' ) === ids( catalog( array() ) ), 'All catalog includes every category, excludes nonmodules/deprecated' );
check( array( 'add_to_cart', 'related_products' ) === ids( catalog( array( 'section' => 'active' ), 'active' ) ), 'My tools only enabled' );
check( array( 'add_to_cart' ) === ids( catalog( array( 'section' => 'active', 'wcj-cat' => 'labels', 'wcj_search' => 'button' ), 'active' ) ), 'My/category/alias intersection' );
check( array() === ids( catalog( array( 'section' => 'active', 'wcj-cat' => 'labels', 'wcj_search' => 'place order' ), 'active' ) ), 'Search cannot bypass enabled filter' );
check( array() === ids( catalog( array( 'wcj-cat' => 'products', 'wcj_search' => 'button' ) ) ), 'Search cannot bypass category' );
check( array( 'more_button_labels' ) === ids( catalog( array( 'wcj_search' => 'place order' ) ) ), 'Authored task alias' );
check( array( 'pdf_invoicing' ) === ids( catalog( array( 'wcj-cat' => 'pdf_invoicing' ) ) ), 'PDF catalog has one real module card' );
$GLOBALS['options']['wcj_modules_by_user_roles_enabled'] = 'yes'; $GLOBALS['deny_pdf'] = true;
check( array() === ids( catalog( array( 'wcj-cat' => 'pdf_invoicing' ) ) ), 'Native PDF role restriction preserved' );
$GLOBALS['deny_pdf'] = false;
$html = catalog( array( 'wcj_search' => 'recommendations' ) );
check( false !== strpos( html_entity_decode( $html ), 'wcj-cat=products&wcj-cat-nonce=fixture&section=related_products' ), 'Search result uses actual category' );
check( false !== strpos( $html, 'name="wcj_related_products_enabled" value="yes"' ), 'Native activation hidden input retained' );
check( false !== strpos( catalog( array( 'wcj_search' => 'no-match' ) ), 'role="status"' ), 'Server empty status' );
foreach ( array( '&', '+', "'", '"', '/', '[]', '.*', 'CafÃ©', str_repeat( 'x', 3000 ) ) as $literal ) {
    check( WCJ_Module_Filters::matches_catalog_search( 'fixture', 'Exact ' . $literal, '', $literal ), 'Literal positive: ' . $literal );
    check( ! WCJ_Module_Filters::matches_catalog_search( 'fixture', 'No match', '', $literal ), 'Literal negative: ' . $literal );
}
check( WCJ_Module_Filters::matches_catalog_search( 'add_to_cart', 'Native title', '', 'BUTTON text' ), 'Case-insensitive alias terms' );
check( WCJ_Module_Filters::matches_catalog_search( 'product_addons', 'Native title', '', 'extra options' ), 'Product-options task alias' );
check( WCJ_Module_Filters::matches_catalog_search( 'pdf_invoicing', 'Native title', '', 'branded invoice' ), 'Invoice task alias' );
$_GET = array( 'wcj-cat' => 'labels', 'section' => 'active', 'wcj_search' => 'a&b+' );
ob_start(); $admin->output_cats_submenu(); $nav = html_entity_decode( ob_get_clean() );
check( false !== strpos( $nav, 'section=active' ) && false !== strpos( $nav, 'wcj_search=a%26b%2B' ), 'Category navigation preserves view and encoded query' );
check( false !== strpos( $nav, 'aria-current="page"' ), 'Selected category accessible' );
$hub = ( new ReflectionClass( 'WCJ_Getting_Started_Hub' ) )->newInstanceWithoutConstructor();
ob_start(); $hub->render(); $dismissed = ob_get_clean();
check( false !== strpos( $dismissed, 'wcj-getting-started' ) && false !== strpos( $dismissed, 'wcj-invoice-setup=1' ) && false === strpos( $dismissed, 'wcj-hub-card' ), 'Dismissal preserves guide/invoice routes' );
check( false !== strpos( $dismissed, '#launch-onboarding-modal' ), 'Persistent setup entry uses the existing direct modal route' );
$native_blueprints = false !== strpos( file_get_contents( dirname( __DIR__ ) . '/includes/admin/class-booster-onboarding.php' ), 'booster_apply_blueprint' );
check( false !== strpos( $dismissed, $native_blueprints ? 'Setup and blueprints' : 'Setup guide' ), 'Setup label matches native edition flow' );
$jobs = new ReflectionMethod( 'WCJ_Getting_Started_Hub', 'get_jobs' ); $jobs->setAccessible( true );
foreach ( $jobs->invoke( $hub ) as $job ) { check( false === strpos( $job['destination'], 'apply_preset' ), 'Casual task does not apply a preset' ); }
ob_start(); $GLOBALS['wcj_module_filters']->render_filter_buttons(); $filters = ob_get_clean();
check( false === strpos( $filters, 'data-filter="recent"' ) && 2 === substr_count( $filters, 'type="button"' ), 'No misleading Recent or submit filters' );
foreach ( array( 'wcj-dashboard', 'wcj-plugins', 'shop', '' ) as $page ) {
    $_GET = array( 'page' => $page ); $GLOBALS['assets'] = array(); $hub->enqueue_assets();
    $expected = in_array( $page, array( 'wcj-dashboard', 'wcj-plugins' ), true );
    check( ( $expected ? 2 : 0 ) === count( $GLOBALS['assets'] ), 'Assets scoped to existing admin surfaces' );
    foreach ( $GLOBALS['assets'] as $asset ) { check( false !== strpos( $asset[2], '-discovery-1' ), 'Changed hub assets use a distinct cache token' ); }
}
echo json_encode( array( 'status' => 'PASS', 'checks' => $checks, 'scope' => 'Source-bound PHP fixture, no WordPress or browser' ) ) . PHP_EOL;

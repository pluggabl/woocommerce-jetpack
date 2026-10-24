<?php
/**
 * Source-bound no-JavaScript fallback checks, not browser or native Save QA.
 *
 * @package Booster_For_WooCommerce/Tests
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

/**
 * Translate and escape output in this CLI-only fixture.
 *
 * @param string $text   Fixture text.
 * @param string $domain Fixture text domain.
 */
function esc_html_e( $text, $domain = '' ) {
	$GLOBALS['codex_fixture_escaped_domains'][] = $domain;
	echo esc_html( $GLOBALS['codex_nojs_translations'][ $text ] ?? $text );
}

ob_start();
require __DIR__ . '/codex-v10-settings-presentation.php';
ob_end_clean();

// Reuse the exact production renderer and actual edition-specific definitions.
$cohort = array( 'add_to_cart', 'more_button_labels', 'price_labels', 'related_products' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Fixed local CLI source, never an external URL.
$categories = file_get_contents( __DIR__ . '/../includes/admin/wcj-modules-cats.php' );
check_fixture( 1 === preg_match( "/'pdf_invoicing'\s*=>\s*array\(.*?'all_cat_ids'\s*=>\s*array\((.*?)\)/s", $categories, $family ), 'native invoice category must remain inspectable' );
preg_match_all( "/'(pdf_invoicing[^']*)'/", $family[1], $invoice_ids );
$eligible = array_merge( $cohort, $invoice_ids[1] );

foreach ( $eligible as $section ) {
	check_fixture( WCJ_Settings_Presentation::supports_no_script( $section ), $section . ': actual native cohort supported' );
	check_fixture( in_array( $section, $cohort, true ) === WCJ_Settings_Presentation::supports( $section ), $section . ': JavaScript enhancement cohort unchanged' );
}
foreach ( array_diff( array( 'pdf_invoicing_paid_stamp', 'pdf_invoicing_extra_columns' ), $invoice_ids[1] ) as $unavailable ) {
	check_fixture( ! WCJ_Settings_Presentation::supports_no_script( $unavailable ), 'absent edition invoice editor is not included: ' . $unavailable );
}
foreach ( array( '', 'active', 'unknown', 'checkout_files_upload', 'pdf_invoicing_fake', 'price_labels<script>' ) as $section ) {
	$read_at = $reads;
	ob_start();
	WCJ_Settings_Presentation::render_no_script( $section );
	$notice = ob_get_clean();
	check_fixture( '' === $notice && '' === WCJ_Settings_Presentation::no_script_heading( $section, 'tab', array( 'tab' => 'Label' ) ), $section . ': no unrelated fallback markup' );
	check_fixture( '' === WCJ_Settings_Presentation::no_script_checkbox_state( $section, 'yes' ) && $reads === $read_at, $section . ': unrelated state untouched' );
}

$read_at = $reads;
ob_start();
WCJ_Settings_Presentation::render_no_script( 'price_labels' );
$notice = ob_get_clean();
check_fixture( $reads === $read_at, 'fallback performs no option reads' );
check_fixture( in_array( 'woocommerce-jetpack', $GLOBALS['codex_fixture_escaped_domains'], true ), 'new notice uses the plugin translation domain' );
$native_notice = 'JavaScript is off. All settings panels for this module are shown below. Use Save Changes for editable fields. Turning modules on or off, changing checkbox settings and JavaScript-powered selectors requires JavaScript. Their saved values are unchanged.';

$GLOBALS['codex_nojs_translations'][ $native_notice ] = '<translated> Ελληνικά & café 中文';
ob_start();
WCJ_Settings_Presentation::render_no_script( 'price_labels' );
$translated_notice = ob_get_clean();
check_fixture( false !== strpos( $translated_notice, esc_html( '<translated> Ελληνικά & café 中文' ) ) && false === strpos( $translated_notice, '<translated>' ), 'translated notice is escaped without losing Unicode' );
unset( $GLOBALS['codex_nojs_translations'] );

check_fixture( 1 === substr_count( $notice, '<noscript>' ) && 1 === substr_count( $notice, '</noscript>' ), 'all fallback CSS and notice are no-script only' );
foreach ( array( '.wcj-tab-box', '.wcj-plugins-sing-acc-sub-cnt', '.wcj-tab-menu', '.wcj_enable_plugin', '.wcj_setting_checkbox_key' ) as $selector ) {
	check_fixture( false !== strpos( $notice, '#wcj-native-settings-nojs ' . $selector ), 'every fallback selector is scoped to the selected native editor: ' . $selector );
}
check_fixture( false !== strpos( $notice, '#wcj-native-invoice-parent .wcj_enable_plugin' ), 'only the existing native invoice parent activation buttons are hidden' );
check_fixture( false !== strpos( $notice, 'Use Save Changes for editable fields.' ) && false !== strpos( $notice, 'JavaScript-powered selectors requires JavaScript.' ), 'native fallback states precise editing limits, not full parity' );
check_fixture( 0 === preg_match( '/<(?:form|input|select|textarea|button)\b/i', $notice ), 'notice creates no form, action or successful control' );

$label = '<script>alert("x")</script> Ελληνικά & café 中文';
check_fixture( '<noscript><h2 class="wcj-native-nojs-heading">' . esc_html( $label ) . '</h2></noscript>' === WCJ_Settings_Presentation::no_script_heading( 'price_labels', 'tab', array( 'tab' => $label ) ), 'actual tab label is escaped without losing Unicode context' );
check_fixture( '' === WCJ_Settings_Presentation::no_script_heading( 'price_labels', 'missing', array() ), 'missing tab label does not invent a heading' );
check_fixture( '' === WCJ_Settings_Presentation::no_script_heading( 'price_labels', 'tab', array( 'tab' => array() ) ), 'non-scalar extension label is not rendered' );
check_fixture( false !== strpos( WCJ_Settings_Presentation::no_script_checkbox_state( 'price_labels', 'yes' ), 'On (requires JavaScript to change)' ), 'saved checked value is explained' );
check_fixture( false !== strpos( WCJ_Settings_Presentation::no_script_checkbox_state( 'price_labels', 'no' ), 'Off (requires JavaScript to change)' ), 'saved unchecked value is explained' );
check_fixture( false !== strpos( WCJ_Settings_Presentation::no_script_checkbox_state( 'price_labels', '<script>' ), 'Off (requires JavaScript to change)' ), 'unexpected stored checkbox value is never echoed' );

foreach ( $result as $section => $data ) {
	$fixture->settings = $data['settings'];
	$fixture->calls    = 0;
	ob_start();
	$fixture->output_settings( 'unrelated_fixture_module' );
	$without_fallback = ob_get_clean();
	check_fixture( 1 === $fixture->calls, $section . ': native definitions evaluated once' );
	check_fixture( preg_replace( '/<noscript>.*?<\/noscript>/s', '', $data['html'] ) === $without_fallback, $section . ': exact native markup outside no-script additions is unchanged' );
	$tab_count = 0;
	foreach ( $data['settings'] as $setting ) {
		if ( 'tab_start' === ( $setting['type'] ?? '' ) ) {
			++$tab_count;
		}
	}
	check_fixture( substr_count( $data['html'], '<h2 class="wcj-native-nojs-heading">' ) === $tab_count, $section . ': every existing tab retains its actual heading' );
	check_fixture( 0 === preg_match( '/<noscript>[^<]*<(?:input|select|textarea|form)\b/i', $data['html'] ), $section . ': no duplicate settings inputs' );
}

// Preserve current values, disabled/readonly rights and the native activation field.
$fixture->settings               = array(
	array(
		'type' => 'sectionend',
		'id'   => 'fixture_start',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'fixture_start',
	),
	array(
		'type'  => 'module_head',
		'id'    => 'wcj_fixture_enabled',
		'title' => 'Fixture module',
	),
	array(
		'type'    => 'tab_ids',
		'id'      => 'fixture_tabs',
		'tab_ids' => array( 'fixture_tab' => 'Actual tab heading' ),
	),
	array(
		'type' => 'tab_start',
		'id'   => 'fixture_tab',
	),
	array(
		'type'              => 'checkbox',
		'id'                => 'wcj_fixture_checkbox',
		'default'           => 'no',
		'custom_attributes' => array( 'disabled' => 'disabled' ),
	),
	array(
		'type'              => 'text',
		'id'                => 'wcj_fixture_text',
		'default'           => '',
		'custom_attributes' => array( 'readonly' => 'readonly' ),
	),
	array(
		'type' => 'tab_end',
		'id'   => 'fixture_tab',
	),
);
$options['wcj_fixture_enabled']  = 'yes';
$options['wcj_fixture_checkbox'] = 'yes';
$options['wcj_fixture_text']     = 'Saved custom value';
ob_start();
$fixture->output_settings( 'price_labels' );
$supported_html = ob_get_clean();
ob_start();
$fixture->output_settings( 'unrelated_fixture_module' );
$native_html = ob_get_clean();
check_fixture( preg_replace( '/<noscript>.*?<\/noscript>/s', '', $supported_html ) === $native_html, 'custom values, attributes, hidden activation and native controls are byte-identical outside no-script additions' );
check_fixture( false !== strpos( $supported_html, 'name="wcj_fixture_enabled" value="yes"' ), 'activation retains exactly the resolved hidden value' );
check_fixture( 2 === substr_count( $supported_html, '<span class="wcj-native-nojs-checkbox">On (requires JavaScript to change)</span>' ), 'module activation and checkbox both show their resolved state without a new getter' );
check_fixture( false !== strpos( $supported_html, 'name="wcj_fixture_checkbox" value="yes"' ) && false !== strpos( $supported_html, 'On (requires JavaScript to change)' ), 'checkbox retains hidden named value and visible no-script state' );

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Fixed local CLI source, never an external URL.
$template = file_get_contents( __DIR__ . '/../includes/admin/wcj-settings-plugins.php' );
check_fixture( 1 === substr_count( $template, 'id="wcj-native-invoice-parent"' ), 'existing invoice parent header has its own bounded selector' );
check_fixture( 1 === substr_count( $template, 'id="wcj-native-settings-nojs"' ), 'only one existing native module wrapper gains the fixed scope marker' );
check_fixture( 1 === substr_count( $template, 'WCJ_Settings_Presentation::render_no_script( $section );' ), 'catalog and search do not receive fallback markup' );
check_fixture( false !== strpos( $template, 'method="post"' ) && false !== strpos( $template, 'value="wcj_save_module_settings"' ), 'existing native submission route retained' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Fixed local CLI source, never an external URL.
$guide = file_get_contents( __DIR__ . '/../includes/admin/views/invoice-setup.php' );
check_fixture( 1 === preg_match( '/<noscript>(.*?)<\/noscript>/s', $guide, $guide_notice ), 'guide has an explicit no-script notice' );
foreach ( array( '#wcj-invoice-setup-form', '#wcj-setup-status', '#wcj-setup-undo' ) as $selector ) {
	check_fixture( false !== strpos( $guide_notice[1], $selector ), 'guide hides only its inert JavaScript surfaces: ' . $selector );
}
check_fixture( false !== strpos( $guide_notice[1], 'generate samples, test attachments, review settings changes, save and activate, or undo changes' ), 'guide truthfully names JavaScript-only operations' );
check_fixture( false !== strpos( $guide_notice[1], 'esc_html_e(' ) && false !== strpos( $guide_notice[1], "'woocommerce-jetpack'" ), 'guide notice uses escaped translation output' );
check_fixture( 1 === substr_count( $guide, 'page=wcj-plugins&tab=jetpack&wcj-cat=pdf_invoicing&section=pdf_invoicing' ), 'existing full native invoice route retained outside no-script notice' );
check_fixture( 1 === substr_count( $guide, '<form ' ) && false === strpos( $guide_notice[1], '<form' ), 'no second guide form or server action added' );

echo "PASS: bounded no-JavaScript source/renderer contracts, native field preservation, edition invoice cohort, notices and escaping. Not browser or native Save QA.\n";

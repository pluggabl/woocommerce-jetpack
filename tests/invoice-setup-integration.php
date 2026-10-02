<?php
/**
 * Source integration fixture, only for isolated WP CLI QA databases.
 *
 * @package Booster_For_WooCommerce/tests
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'BOOSTER_INVOICE_SETUP_ISOLATED_QA' ) ) {
	throw new RuntimeException( 'Isolated QA opt-in required.' );
}
$wcj_qa_plugin = defined( 'WCJ_FREE_PLUGIN_PATH' ) ? WCJ_FREE_PLUGIN_PATH : WCJ_PLUGIN_PATH;
require_once $wcj_qa_plugin . '/includes/admin/class-wcj-invoice-setup.php';
$wcj_qa_file          = defined( 'WCJ_PLUGIN_FILE' ) ? WCJ_PLUGIN_FILE : WCJ_FREE_PLUGIN_FILE;
$wcj_qa_tier          = false !== strpos( basename( $wcj_qa_file ), 'elite' ) ? 'elite' : ( false !== strpos( basename( $wcj_qa_file ), 'plus' ) ? 'plus' : 'free' );
$wcj_qa_state         = 'booster_' . $wcj_qa_tier . '_onboarding';
$wcj_qa_journal       = $wcj_qa_state . '_invoice_setup';
$wcj_qa_guide         = new WCJ_Invoice_Setup( $wcj_qa_state );
$wcj_qa_tests         = array();
$wcj_qa_original_user = get_current_user_id();
$wcj_qa_admins        = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);
if ( ! $wcj_qa_admins ) {
	throw new RuntimeException( 'QA administrator missing.' );
}
wp_set_current_user( $wcj_qa_admins[0] );
global $wpdb;
$wcj_qa_rows        = static function () use ( $wpdb, $wcj_qa_state, $wcj_qa_journal ) {
	$wcj_qa_out = array();
	foreach ( $wpdb->get_results( "SELECT option_name,option_value,autoload FROM {$wpdb->options}", ARRAY_A ) as $wcj_qa_row ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Isolated QA must compare persisted bytes independently of option caches.
		if ( 0 === strpos( $wcj_qa_row['option_name'], 'wcj_invoicing_' ) || in_array( $wcj_qa_row['option_name'], array( $wcj_qa_state, $wcj_qa_journal, 'wcj_pdf_invoicing_enabled', 'wcj_general_advanced_disable_save_sys_temp_dir', 'wcj_invoice_setup_lock' ), true ) ) {
			$wcj_qa_out[ $wcj_qa_row['option_name'] ] = $wcj_qa_row;
		}
	}
	ksort( $wcj_qa_out );
	return $wcj_qa_out;
};
$wcj_qa_backup      = $wcj_qa_rows();
$wcj_qa_order_state = static function () use ( $wpdb ) {
	$wcj_qa_hashes = array();
	foreach ( array( 'posts', 'postmeta', 'wc_orders', 'wc_orders_meta', 'woocommerce_order_items', 'woocommerce_order_itemmeta' ) as $wcj_qa_suffix ) {
		$wcj_qa_table = $wpdb->prefix . $wcj_qa_suffix;
		if ( $wcj_qa_table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wcj_qa_table ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Discover only fixed QA history tables.
			$wcj_qa_data                     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $wcj_qa_table ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Hash persisted history, not a cache, to detect unexpected writes.
			$wcj_qa_hashes[ $wcj_qa_suffix ] = array(
				'rows'   => count( $wcj_qa_data ),
				'sha256' => hash( 'sha256', wp_json_encode( $wcj_qa_data ) ),
			);
		}
	}
	return $wcj_qa_hashes;
};
$wcj_qa_assert      = static function ( $wcj_qa_name, $wcj_qa_ok ) use ( &$wcj_qa_tests ) {
	if ( ! $wcj_qa_ok ) {
		throw new RuntimeException( esc_html( 'FAIL: ' . $wcj_qa_name ) );
	}
	$wcj_qa_tests[] = array(
		'test'   => $wcj_qa_name,
		'status' => 'pass',
	);
};
$wcj_qa_reject      = static function ( $wcj_qa_name, $wcj_qa_call ) use ( $wcj_qa_assert ) {
	$wcj_qa_failed = false;
	try {
		$wcj_qa_call();
	} catch ( RuntimeException $wcj_qa_error ) {
		$wcj_qa_failed = true;
	}
	$wcj_qa_assert( $wcj_qa_name, $wcj_qa_failed );
};
$wcj_qa_draft       = array(
	'business_name'      => 'Booster QA Shop',
	'business_address'   => "12 Example Street\nExample City",
	'business_reference' => 'DEMO ONLY',
	'logo_attachment_id' => '0',
	'accent_color'       => '#0073aa',
	'footer_note'        => 'Thank you.',
	'document_type'      => 'invoice',
	'operation'          => 'completed',
	'email'              => 'customer_completed_order',
);
$wcj_qa_http        = 0;
$wcj_qa_mail        = 0;
$wcj_qa_deny_http   = static function () use ( &$wcj_qa_http ) {
	++$wcj_qa_http;
	return new WP_Error( 'qa_no_network', 'Forbidden in sample QA.' );
};
$wcj_qa_deny_mail   = static function () use ( &$wcj_qa_mail ) {
	++$wcj_qa_mail;
	return true;
};
add_filter( 'pre_http_request', $wcj_qa_deny_http, 1, 3 );
add_filter( 'pre_wp_mail', $wcj_qa_deny_mail, 1, 2 );
$wcj_qa_failure = null;
try {
	foreach ( array_keys( $wcj_qa_rows() ) as $wcj_qa_key ) {
		delete_option( $wcj_qa_key );
	}
	update_option(
		$wcj_qa_state,
		array(
			'sentinel'        => 'preserved',
			'completed_goals' => array( 'unrelated' ),
			'snapshots'       => array( 'legacy' => array( 'before' => array( 'old' => 'value' ) ) ),
		)
	);
	update_option( 'wcj_invoicing_invoice_numbering_counter', 811 );
	update_option( 'wcj_invoicing_invoice_sequential_enabled', 'yes' );
	$wcj_qa_before = $wcj_qa_rows();
	$wcj_qa_orders = $wcj_qa_order_state();
	$wcj_qa_pdf    = WCJ_PDF_Invoice::setup_sample_pdf( $wcj_qa_draft );
	$wcj_qa_assert( 'INV-PDF-real-bytes', 0 === strpos( $wcj_qa_pdf, '%PDF-' ) && strlen( $wcj_qa_pdf ) > 500 );
	file_put_contents( '/tmp/booster85-starter-' . $wcj_qa_tier . '.pdf', $wcj_qa_pdf ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Isolated CLI evidence output, never a merchant-controlled path.
	$wcj_qa_assert( 'INV-SAMPLE-options-unchanged', $wcj_qa_before === $wcj_qa_rows() );
	$wcj_qa_run = $wcj_qa_guide->rehearse( $wcj_qa_draft );
	$wcj_qa_assert( 'INV-ATTACHMENT-actual-file-construction-and-cleanup', $wcj_qa_run['constructed'] && $wcj_qa_run['removed'] && $wcj_qa_run['bytes'] > 500 );
	$wcj_qa_assert( 'INV-SAMPLE-no-options-orders-hpos-meta-history-mail-http', $wcj_qa_before === $wcj_qa_rows() && $wcj_qa_orders === $wcj_qa_order_state() && 0 === $wcj_qa_http && 0 === $wcj_qa_mail );
	$wcj_qa_assert( 'INV-REHEARSAL-no-owned-leftovers', ! glob( sys_get_temp_dir() . '/booster-setup-*' ) );
	$wcj_qa_uploads    = wp_upload_dir( null, false );
	$wcj_qa_logo_path  = $wcj_qa_uploads['basedir'] . '/booster-qa-aspect-' . bin2hex( random_bytes( 8 ) ) . '.png';
	$wcj_qa_logo_bytes = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAfQCAIAAACMsqLaAAAAIElEQVR4nO3DAQ0AAAzDoPo3/QsZJHSVqqqqqqqqqo5/9irImqLYubcAAAAASUVORK5CYII=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Fixed synthetic 1 x 2000 pixel PNG, not executable input.
	file_put_contents( $wcj_qa_logo_path, $wcj_qa_logo_bytes ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Caller-owned isolated QA image; always removed below.
	$wcj_qa_logo_meta = static function ( $wcj_qa_value, $wcj_qa_id, $wcj_qa_key ) use ( $wcj_qa_logo_path ) {
		return 2147483647 === $wcj_qa_id && '_wp_attached_file' === $wcj_qa_key ? $wcj_qa_logo_path : $wcj_qa_value;
	};
	add_filter( 'get_post_metadata', $wcj_qa_logo_meta, 10, 3 );
	try {
		$wcj_qa_reject(
			'INV-LOGO-extreme-aspect-rejected',
			static function () use ( $wcj_qa_draft ) {
				WCJ_PDF_Invoice::setup_sample_pdf( array_merge( $wcj_qa_draft, array( 'logo_attachment_id' => '2147483647' ) ) );
			}
		);
	} finally {
		remove_filter( 'get_post_metadata', $wcj_qa_logo_meta, 10 );
		wp_delete_file( $wcj_qa_logo_path );
	}
	$wcj_qa_assert( 'INV-LOGO-extreme-aspect-cleanup', ! file_exists( $wcj_qa_logo_path ) );
	foreach ( array(
		'business_name'      => '[wcj_order_number]',
		'business_address'   => '<tcpdf method="Output">',
		'accent_color'       => 'url(http://example.com)',
		'logo_attachment_id' => '../etc/passwd',
		'document_type'      => 'custom_doc',
		'email'              => 'person@example.com',
		'operation'          => 'woocommerce_new_order',
		'option_key'         => 'siteurl',
	) as $wcj_qa_key => $wcj_qa_bad ) {
		$wcj_qa_input = array_merge( $wcj_qa_draft, array( $wcj_qa_key => $wcj_qa_bad ) );
		$wcj_qa_reject(
			'INV-INPUT-reject-' . $wcj_qa_key,
			static function () use ( $wcj_qa_input ) {
				WCJ_Invoice_Setup::validate( $wcj_qa_input );
			}
		);
	}
	if ( 'elite' === $wcj_qa_tier ) {
		foreach ( array( 'invoice', 'packing_slip', 'proforma_invoice', 'credit_note' ) as $wcj_qa_type ) {
			$wcj_qa_input    = array_merge(
				$wcj_qa_draft,
				array(
					'document_type' => $wcj_qa_type,
					'operation'     => 'manual',
					'email'         => '',
				)
			);
			$wcj_qa_type_pdf = WCJ_PDF_Invoice::setup_sample_pdf( $wcj_qa_input );
			$wcj_qa_assert( 'INV-FULL-real-pdf-' . $wcj_qa_type, ( 0 === strpos( $wcj_qa_type_pdf, '%PDF-' ) ) );
		}
	} else {
		$wcj_qa_input = array_merge( $wcj_qa_draft, array( 'document_type' => 'packing_slip' ) );
		$wcj_qa_reject(
			'INV-TIER-forged-full-rejected',
			static function () use ( $wcj_qa_input ) {
				WCJ_Invoice_Setup::validate( $wcj_qa_input );
			}
		);
	}
	if ( 'plus' === $wcj_qa_tier ) {
		$wcj_qa_assert( 'INV-PLUS-legacy-rights', array( 'manual' ) === apply_filters( 'booster_option', '', array( 'manual' ) ) );
	}
	wp_set_current_user( 0 );
	$wcj_qa_reject(
		'INV-CAP-sample-service',
		static function () use ( $wcj_qa_draft ) {
			WCJ_PDF_Invoice::setup_sample_pdf( $wcj_qa_draft );
		}
	);
	$wcj_qa_reject(
		'INV-CAP-review-service',
		static function () use ( $wcj_qa_draft, $wcj_qa_guide ) {
			$wcj_qa_guide->review( $wcj_qa_draft );
		}
	);
	$wcj_qa_reject(
		'INV-CAP-activate-service',
		static function () use ( $wcj_qa_draft, $wcj_qa_guide ) {
			$wcj_qa_guide->activate( $wcj_qa_draft, 'forged' );
		}
	);
	$wcj_qa_reject(
		'INV-CAP-undo-service',
		static function () use ( $wcj_qa_guide ) {
			$wcj_qa_guide->undo();
		}
	);
	wp_set_current_user( $wcj_qa_admins[0] );
	update_option( 'wcj_invoicing_invoice_template', '<h1>Merchant custom</h1>' );
	$wcj_qa_reject(
		'INV-CUSTOM-blocked-preserved',
		static function () use ( $wcj_qa_draft, $wcj_qa_guide ) {
			$wcj_qa_guide->review( $wcj_qa_draft );
		}
	);
	$wcj_qa_assert( 'INV-CUSTOM-not-overwritten', '<h1>Merchant custom</h1>' === get_option( 'wcj_invoicing_invoice_template' ) );
	delete_option( 'wcj_invoicing_invoice_template' );
	update_option( 'wcj_invoicing_packing_slip_create_on', array( 'manual' ) );
	$wcj_qa_reject(
		'INV-PARENT-other-documents-blocked',
		static function () use ( $wcj_qa_draft, $wcj_qa_guide ) {
			$wcj_qa_guide->review( $wcj_qa_draft );
		}
	);
	delete_option( 'wcj_invoicing_packing_slip_create_on' );
	update_option( 'wcj_pdf_invoicing_enabled', 'yes' );
	update_option( 'wcj_invoicing_invoice_create_on', array( 'manual' ) );
	$wcj_qa_reject(
		'INV-LIVE-active-selected-document-blocked',
		static function () use ( $wcj_qa_draft, $wcj_qa_guide ) {
			$wcj_qa_guide->review( $wcj_qa_draft );
		}
	);
	delete_option( 'wcj_pdf_invoicing_enabled' );
	delete_option( 'wcj_invoicing_invoice_create_on' );
	$wcj_qa_review = $wcj_qa_guide->review( $wcj_qa_draft );
	update_option( 'wcj_invoicing_invoice_header_text', 'Later edit' );
	$wcj_qa_reject(
		'INV-ACTIVATE-stale-review',
		static function () use ( $wcj_qa_draft, $wcj_qa_guide, $wcj_qa_review ) {
			$wcj_qa_guide->activate( $wcj_qa_draft, $wcj_qa_review['fingerprint'] );
		}
	);
	delete_option( 'wcj_invoicing_invoice_header_text' );
	update_option( 'wcj_invoicing_invoice_header_text_color', '#0073aa' );
	$wcj_qa_review = $wcj_qa_guide->review( $wcj_qa_draft );
	$wcj_qa_run    = $wcj_qa_guide->activate( $wcj_qa_draft, $wcj_qa_review['fingerprint'] );
	$wcj_qa_assert( 'INV-ACTIVATE-settings', $wcj_qa_run['activated'] && 'yes' === get_option( 'wcj_pdf_invoicing_enabled' ) && array( 'woocommerce_order_status_completed' ) === get_option( 'wcj_invoicing_invoice_create_on' ) && array( 'customer_completed_order' ) === get_option( 'wcj_invoicing_invoice_attach_to_emails' ) );
	$wcj_qa_assert( 'INV-ACTIVATE-seller-identity', false !== strpos( get_option( 'wcj_invoicing_invoice_template' ), 'Booster QA Shop' ) && false === strpos( get_option( 'wcj_invoicing_invoice_template' ), 'COMPANY NAME' ) );
	$wcj_qa_assert( 'INV-ACTIVATE-counter-preserved', 811 === (int) get_option( 'wcj_invoicing_invoice_numbering_counter' ) );
	$wcj_qa_assert( 'INV-ACTIVATE-onboarding-preserved', 'preserved' === get_option( $wcj_qa_state )['sentinel'] && isset( get_option( $wcj_qa_state )['snapshots']['legacy'] ) );
	$wcj_qa_legacy_write                  = get_option( $wcj_qa_state );
	$wcj_qa_legacy_write['applied_goals'] = array( 'unrelated_legacy_goal' );
	update_option( $wcj_qa_state, $wcj_qa_legacy_write );
	$wcj_qa_assert( 'INV-JOURNAL-legacy-whole-option-write-cannot-erase-undo', isset( get_option( $wcj_qa_journal )['invoice_setup_v1']['entries'] ) && ! isset( get_option( $wcj_qa_state )['invoice_setup_v1'] ) && $wcj_qa_guide->is_active() );
	$wcj_qa_prior = $wcj_qa_rows();
	$wcj_qa_run   = $wcj_qa_guide->activate( $wcj_qa_draft, $wcj_qa_review['fingerprint'] );
	$wcj_qa_assert( 'INV-ACTIVATE-replay-idempotent', $wcj_qa_run['activated'] && $wcj_qa_prior === $wcj_qa_rows() );
	$wcj_qa_assert( 'INV-COMPLETION-full-settings-current', $wcj_qa_guide->is_active() );
	$wcj_qa_assert( 'INV-UNDO-snapshot-only-actual-writes', ! isset( get_option( $wcj_qa_journal )['invoice_setup_v1']['entries']['wcj_invoicing_invoice_header_text_color'] ) );
	update_option( 'wcj_invoicing_invoice_header_text_color', '#111111' );
	$wcj_qa_assert( 'INV-COMPLETION-preexisting-matching-setting-edit-detected', ! $wcj_qa_guide->is_active() );
	$wcj_qa_reject(
		'INV-REPLAY-preexisting-matching-setting-edit-detected',
		static function () use ( $wcj_qa_draft, $wcj_qa_guide, $wcj_qa_review ) {
			$wcj_qa_guide->activate( $wcj_qa_draft, $wcj_qa_review['fingerprint'] );
		}
	);
	update_option( 'wcj_invoicing_invoice_header_text', 'Keep later edit' );
	update_option( 'wcj_invoicing_packing_slip_create_on', array( 'manual' ) );
	$wcj_qa_run = $wcj_qa_guide->undo();
	$wcj_qa_assert( 'INV-UNDO-later-document-parent-preserved', in_array( 'wcj_pdf_invoicing_enabled', $wcj_qa_run['conflicts'], true ) && 'yes' === get_option( 'wcj_pdf_invoicing_enabled' ) && array( 'manual' ) === get_option( 'wcj_invoicing_packing_slip_create_on' ) );
	delete_option( 'wcj_invoicing_packing_slip_create_on' );
	$wcj_qa_run = $wcj_qa_guide->undo();
	$wcj_qa_assert( 'INV-UNDO-conflict-preserved', ! $wcj_qa_run['restored'] && in_array( 'wcj_invoicing_invoice_header_text', $wcj_qa_run['conflicts'], true ) && 'Keep later edit' === get_option( 'wcj_invoicing_invoice_header_text' ) );
	$wcj_qa_assert( 'INV-UNDO-conflict-snapshot-retained', isset( get_option( $wcj_qa_journal )['invoice_setup_v1']['entries']['wcj_invoicing_invoice_header_text'] ) );
	$wcj_qa_assert( 'INV-UNDO-originally-absent-restored', false === get_option( 'wcj_pdf_invoicing_enabled', false ) );
	$wcj_qa_assert( 'INV-UNDO-never-written-setting-preserved', '#111111' === get_option( 'wcj_invoicing_invoice_header_text_color' ) );
	update_option( 'wcj_invoicing_invoice_header_text', 'Booster QA Shop' );
	$wcj_qa_run = $wcj_qa_guide->undo();
	$wcj_qa_assert( 'INV-UNDO-resolved', $wcj_qa_run['restored'] && false === get_option( 'wcj_invoicing_invoice_header_text', false ) );
	$wcj_qa_review = $wcj_qa_guide->review( $wcj_qa_draft );
	$wcj_qa_guide->activate( $wcj_qa_draft, $wcj_qa_review['fingerprint'] );
	update_option( 'wcj_invoicing_invoice_create_on', array( 'manual' ) );
	$wcj_qa_run = $wcj_qa_guide->undo();
	$wcj_qa_assert( 'INV-UNDO-later-selected-recipe-parent-preserved', 'yes' === get_option( 'wcj_pdf_invoicing_enabled' ) && array( 'manual' ) === get_option( 'wcj_invoicing_invoice_create_on' ) && in_array( 'wcj_pdf_invoicing_enabled', $wcj_qa_run['conflicts'], true ) && in_array( 'wcj_invoicing_invoice_create_on', $wcj_qa_run['conflicts'], true ) );
	update_option( 'wcj_invoicing_invoice_create_on', array( 'woocommerce_order_status_completed' ) );
	$wcj_qa_run = $wcj_qa_guide->undo();
	$wcj_qa_assert( 'INV-UNDO-selected-recipe-conflict-resolved', $wcj_qa_run['restored'] && false === get_option( 'wcj_pdf_invoicing_enabled', false ) && false === get_option( 'wcj_invoicing_invoice_create_on', false ) );
	$wcj_qa_review    = $wcj_qa_guide->review( $wcj_qa_draft );
	$wcj_qa_injecting = false;
	$wcj_qa_fault     = static function ( $wcj_qa_sql ) use ( &$wcj_qa_injecting, $wpdb ) {
		if ( ! $wcj_qa_injecting && 0 === strpos( $wcj_qa_sql, 'UPDATE ' ) && false !== strpos( $wcj_qa_sql, 'invoice_setup_v1' ) && false !== strpos( $wcj_qa_sql, 'active' ) ) {
			$wcj_qa_injecting = true;
			update_option( 'wcj_invoicing_packing_slip_create_on', array( 'manual' ) );
			update_option( 'wcj_invoicing_invoice_create_on', array( 'manual' ) );
			$wcj_qa_injecting = false;
			return "UPDATE {$wpdb->options} SET option_value = option_value WHERE option_name = 'wcj_qa_intentionally_missing_option'";
		}
		return $wcj_qa_sql;
	};
	add_filter( 'query', $wcj_qa_fault );
	try {
		$wcj_qa_reject(
			'INV-ROLLBACK-active-journal-failure-injected',
			static function () use ( $wcj_qa_guide, $wcj_qa_draft, $wcj_qa_review ) {
				$wcj_qa_guide->activate( $wcj_qa_draft, $wcj_qa_review['fingerprint'] );
			}
		);
	} finally {
		remove_filter( 'query', $wcj_qa_fault );
	}
	$wcj_qa_assert( 'INV-ROLLBACK-later-document-parent-preserved', 'yes' === get_option( 'wcj_pdf_invoicing_enabled' ) && isset( get_option( $wcj_qa_journal )['invoice_setup_v1']['entries']['wcj_pdf_invoicing_enabled'] ) && array( 'manual' ) === get_option( 'wcj_invoicing_packing_slip_create_on' ) );
	delete_option( 'wcj_invoicing_packing_slip_create_on' );
	$wcj_qa_run = $wcj_qa_guide->undo();
	$wcj_qa_assert( 'INV-ROLLBACK-later-selected-recipe-parent-preserved', 'yes' === get_option( 'wcj_pdf_invoicing_enabled' ) && array( 'manual' ) === get_option( 'wcj_invoicing_invoice_create_on' ) && in_array( 'wcj_pdf_invoicing_enabled', $wcj_qa_run['conflicts'], true ) );
	update_option( 'wcj_invoicing_invoice_create_on', array( 'woocommerce_order_status_completed' ) );
	$wcj_qa_run = $wcj_qa_guide->undo();
	$wcj_qa_assert( 'INV-ROLLBACK-parent-conflict-resolved', $wcj_qa_run['restored'] && false === get_option( 'wcj_pdf_invoicing_enabled', false ) );
	$wcj_qa_read = new ReflectionMethod( $wcj_qa_guide, 'read_option' );
	$wcj_qa_read->setAccessible( true );
	$wcj_qa_cas = new ReflectionMethod( $wcj_qa_guide, 'compare_exchange' );
	$wcj_qa_cas->setAccessible( true );
	update_option( 'wcj_invoicing_invoice_header_text', 'old' );
	$wcj_qa_old = $wcj_qa_read->invoke( $wcj_qa_guide, 'wcj_invoicing_invoice_header_text' );
	update_option( 'wcj_invoicing_invoice_header_text', 'intervening' );
	$wcj_qa_assert(
		'INV-CAS-preserves-intervening-write',
		false === $wcj_qa_cas->invoke(
			$wcj_qa_guide,
			'wcj_invoicing_invoice_header_text',
			$wcj_qa_old,
			array(
				'exists' => true,
				'raw'    => 'overwrite',
			)
		) && 'intervening' === get_option( 'wcj_invoicing_invoice_header_text' )
	);
	$wcj_qa_assert( 'INV-FINAL-no-mail-http', 0 === $wcj_qa_http && 0 === $wcj_qa_mail );
	$wcj_qa_assert( 'INV-FINAL-orders-hpos-meta-history-unchanged', $wcj_qa_orders === $wcj_qa_order_state() );
} catch ( Throwable $wcj_qa_error ) {
	$wcj_qa_failure = $wcj_qa_error->getMessage();
} finally {
	remove_filter( 'pre_http_request', $wcj_qa_deny_http, 1 );
	remove_filter( 'pre_wp_mail', $wcj_qa_deny_mail, 1 );
	foreach ( array_keys( $wcj_qa_rows() ) as $wcj_qa_key ) {
		$wpdb->delete( $wpdb->options, array( 'option_name' => $wcj_qa_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Restore isolated QA raw-state snapshot; invalidate caches immediately below.
		wp_cache_delete( $wcj_qa_key, 'options' );
	}
	foreach ( $wcj_qa_backup as $wcj_qa_row ) {
		$wpdb->insert( $wpdb->options, $wcj_qa_row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Restore exact value and autoload bytes; invalidate caches immediately below.
		wp_cache_delete( $wcj_qa_row['option_name'], 'options' );
	}
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	wp_set_current_user( $wcj_qa_original_user );
}
echo wp_json_encode(
	array(
		'lane'                      => 'source-integration-only',
		'tier'                      => $wcj_qa_tier,
		'tests'                     => $wcj_qa_tests,
		'failure'                   => $wcj_qa_failure,
		'original_options_restored' => $wcj_qa_backup === $wcj_qa_rows(),
		'sample'                    => '/tmp/booster85-starter-' . $wcj_qa_tier . '.pdf',
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . "\n";
if ( $wcj_qa_failure ) {
	WP_CLI::error( $wcj_qa_failure );
}

<?php
/**
 * Isolated native producer-to-projection checks, not a browser or remote client.
 * Existing synthetic product/orders only; no business settings or entity writes.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
require __DIR__ . '/observation-projection-bootstrap.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-wcj-ai-observation-projection.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/class-wcj-ai-revenue-observation.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/class-wcj-ai-role-observation.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/class-wcj-price-by-user-role.php';

/**
 * Read native synthetic session storage uncached; never emit its contents.
 *
 * @param int $wcj_ai_owner_id Synthetic owner.
 * @return array Native session map.
 * @throws RuntimeException On ambiguous storage.
 */
function wcj_ai_projection_sessions( $wcj_ai_owner_id ) {
	global $wpdb;
	$wpdb->last_error = '';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Independent uncached cleanup verification in the isolated synthetic database only.
	$wcj_ai_rows = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s", $wcj_ai_owner_id, 'session_tokens' ) );
	if ( '' !== $wpdb->last_error || ! is_array( $wcj_ai_rows ) || count( $wcj_ai_rows ) > 1 ) {
		throw new RuntimeException( 'Session verification unavailable.' );
	}
	if ( ! $wcj_ai_rows ) {
		return array(); }
	if ( ! is_string( $wcj_ai_rows[0] ) || strlen( $wcj_ai_rows[0] ) > 262144 || ! str_starts_with( $wcj_ai_rows[0], 'a:' ) || ! is_serialized( $wcj_ai_rows[0], true ) ) {
		throw new RuntimeException( 'Session verification unavailable.' );
	}
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Native WordPress session format, bounded bytes/depth and object construction disabled; independent cleanup readback only.
	$wcj_ai_sessions = unserialize(
		$wcj_ai_rows[0],
		array(
			'allowed_classes' => false,
			'max_depth'       => 16,
		)
	);
	if ( ! is_array( $wcj_ai_sessions ) ) {
		throw new RuntimeException( 'Session verification unavailable.' ); }
	foreach ( $wcj_ai_sessions as $wcj_ai_key => $wcj_ai_entry ) {
		if ( ! is_string( $wcj_ai_key ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $wcj_ai_key ) || ! is_array( $wcj_ai_entry ) || ! isset( $wcj_ai_entry['expiration'] ) || ! is_int( $wcj_ai_entry['expiration'] ) ) {
			throw new RuntimeException( 'Session verification unavailable.' );
		}
	}
	return $wcj_ai_sessions;
}

/**
 * Guard against new WordPress/SQL activity while projecting an existing DTO.
 *
 * @param string $kind Projection entry point.
 * @param array  $value Already-observed native DTO.
 * @return array Projected value.
 */
function wcj_ai_project_without_hooks( $kind, $value ) {
	$wcj_ai_count = 0;
	$wcj_ai_hook  = static function () use ( &$wcj_ai_count ) {
		++$wcj_ai_count;
	};
	add_action( 'all', $wcj_ai_hook );
	try {
		$wcj_ai_result = WCJ_AI_Observation_Projection::$kind( $value );
	} finally {
		remove_action( 'all', $wcj_ai_hook );
	}
	wcj_ai_check( $kind . '_projection_no_wp_hooks_including_sql', 0 === $wcj_ai_count );
	return $wcj_ai_result;
}

/**
 * Use a fresh native module on a real Woo hook with one guarded nested call.
 *
 * @param WC_Product $wcj_ai_product Existing synthetic product.
 * @return array Native output/type and observable callback counts.
 */
function wcj_ai_projection_nested_native( WC_Product $wcj_ai_product ) {
	$wcj_ai_instance                            = ( new ReflectionClass( WCJ_Price_By_User_Role::class ) )->newInstanceWithoutConstructor();
	$wcj_ai_instance->price_hooks_priority      = 10;
	$wcj_ai_instance->disable_for_regular_price = false;
	$wcj_ai_reentered                           = false;
	$wcj_ai_nested_result                       = null;
	$wcj_ai_counts                              = array();
	$wcj_ai_counter                             = static function ( $wcj_ai_name ) use ( &$wcj_ai_counts ) {
		if ( str_starts_with( $wcj_ai_name, 'wcj_' ) || str_starts_with( $wcj_ai_name, 'pre_option_wcj_' ) || 'woocommerce_product_get_price' === $wcj_ai_name ) {
			$wcj_ai_counts[ $wcj_ai_name ] = ( $wcj_ai_counts[ $wcj_ai_name ] ?? 0 ) + 1;
		}
	};
	$wcj_ai_reenter                             = static function ( $wcj_ai_value ) use ( &$wcj_ai_reentered, &$wcj_ai_nested_result, $wcj_ai_product ) {
		if ( ! $wcj_ai_reentered ) {
			$wcj_ai_reentered     = true;
			$wcj_ai_nested_result = apply_filters( 'woocommerce_product_get_price', '100', $wcj_ai_product ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Exercise the existing native WooCommerce hook in this isolated fixture.
		}
		return $wcj_ai_value;
	};
	add_filter( 'woocommerce_product_get_price', array( $wcj_ai_instance, 'change_price' ), 10, 2 );
	add_filter( 'wcj_general_enabled', $wcj_ai_reenter );
	add_action( 'all', $wcj_ai_counter );
	try {
		$wcj_ai_result = apply_filters( 'woocommerce_product_get_price', '100', $wcj_ai_product ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Exercise the existing native WooCommerce hook in this isolated fixture.
	} finally {
		remove_action( 'all', $wcj_ai_counter );
		remove_filter( 'wcj_general_enabled', $wcj_ai_reenter );
		remove_filter( 'woocommerce_product_get_price', array( $wcj_ai_instance, 'change_price' ), 10 );
	}
	ksort( $wcj_ai_counts );
	return array( $wcj_ai_result, $wcj_ai_nested_result, $wcj_ai_reentered, $wcj_ai_counts );
}

$wcj_ai_session         = null;
$wcj_ai_token           = null;
$wcj_ai_removed         = array();
$wcj_ai_cleanup         = false;
$wcj_ai_failed          = false;
$wcj_ai_sessions_before = null;
$wcj_ai_expired_pruned  = 0;
try {
	$wcj_ai_raw_report = WCJ_AI_Revenue_Observation::observe();
	$wcj_ai_report     = wcj_ai_project_without_hooks( 'revenue', $wcj_ai_raw_report );
	wcj_ai_check( 'native_report_admitted_not_just_unavailable', 'legacy_native_uncached' === $wcj_ai_report['storage'] && null !== $wcj_ai_report['periods'] && 'observed_subset' === $wcj_ai_report['streams']['paid_cohort_order_totals']['status'] && 'observed_subset' === $wcj_ai_report['streams']['recorded_refund_events']['status'] );
	wcj_ai_check( 'native_report_nonempty_order_and_refund_positive', count( $wcj_ai_report['streams']['paid_cohort_order_totals']['groups'] ) > 0 && count( $wcj_ai_report['streams']['recorded_refund_events']['groups'] ) > 0 );
	wcj_ai_check( 'native_report_exact_groups_and_frozen_periods', $wcj_ai_raw_report['periods'] === $wcj_ai_report['periods'] && $wcj_ai_raw_report['streams']['paid_cohort_order_totals']['groups'] === $wcj_ai_report['streams']['paid_cohort_order_totals']['groups'] && $wcj_ai_raw_report['streams']['recorded_refund_events']['groups'] === $wcj_ai_report['streams']['recorded_refund_events']['groups'] );
	wcj_ai_check( 'native_report_no_invented_totals', null === $wcj_ai_report['net_revenue'] && false === $wcj_ai_report['overall_complete'] && false === $wcj_ai_report['membership_complete'] );
	$wcj_ai_product = wc_get_product( 10 );
	wcj_ai_check( 'known_synthetic_product', $wcj_ai_product instanceof WC_Product_Simple && 'publish' === $wcj_ai_product->get_status( 'edit' ) && '100' === $wcj_ai_product->get_regular_price( 'edit' ) );
	// Remove only same-request role instances; persistent configuration is untouched.
	global $wp_filter;
	foreach ( $wp_filter['woocommerce_product_get_price']->callbacks ?? array() as $wcj_ai_priority => $wcj_ai_callbacks ) {
		foreach ( $wcj_ai_callbacks as $wcj_ai_callback ) {
			$wcj_ai_function = $wcj_ai_callback['function'];
			if ( is_array( $wcj_ai_function ) && $wcj_ai_function[0] instanceof WCJ_Price_By_User_Role && 'change_price' === $wcj_ai_function[1] ) {
				$wcj_ai_removed[] = array( $wcj_ai_function, $wcj_ai_priority, $wcj_ai_callback['accepted_args'] );
				remove_filter( 'woocommerce_product_get_price', $wcj_ai_function, $wcj_ai_priority );
			}
		}
	}
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Request-local synthetic query context; no endpoint or database write.
	$GLOBALS['wp_query'] = new WP_Query(
		array(
			'post_type' => 'product',
			'p'         => 10,
		)
	);
	$_GET                = array();
	$_POST               = array();
	$wcj_ai_session      = WP_Session_Tokens::get_instance( $wcj_ai_owner->ID );
	wcj_ai_check( 'native_session_manager', 'WP_User_Meta_Session_Tokens' === get_class( $wcj_ai_session ) );
	$wcj_ai_sessions_before = wcj_ai_projection_sessions( $wcj_ai_owner->ID );
	$wcj_ai_candidate_token = bin2hex( random_bytes( 32 ) );
	wcj_ai_check( 'unique_owned_session_token', ! array_key_exists( hash( 'sha256', $wcj_ai_candidate_token ), $wcj_ai_sessions_before ) );
	$wcj_ai_token = $wcj_ai_candidate_token;
	$wcj_ai_session->update( $wcj_ai_token, array( 'expiration' => time() + 600 ) );
	$wcj_ai_sessions_after = wcj_ai_projection_sessions( $wcj_ai_owner->ID );
	wcj_ai_check( 'native_session_persistence_verified', isset( $wcj_ai_sessions_after[ hash( 'sha256', $wcj_ai_token ) ] ) );
	$_COOKIE[ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie( $wcj_ai_owner->ID, time() + 600, 'logged_in', $wcj_ai_token );
	$wcj_ai_nonce                = wp_create_nonce( 'wcj_ai_role_capture_10' );
	wcj_ai_projection_nested_native( $wcj_ai_product ); // Warm request-local native option caches before measuring either mode.
	$wcj_ai_off = wcj_ai_projection_nested_native( $wcj_ai_product );
	wcj_ai_check( 'own_cookie_collector_admitted', WCJ_AI_Role_Observation::arm( 10, $wcj_ai_nonce ) );
	$wcj_ai_on       = wcj_ai_projection_nested_native( $wcj_ai_product );
	$wcj_ai_raw_role = WCJ_AI_Role_Observation::close();
	$wcj_ai_role     = wcj_ai_project_without_hooks( 'role', $wcj_ai_raw_role );
	wcj_ai_check( 'nested_native_on_off_values_types_counts', $wcj_ai_off === $wcj_ai_on && true === $wcj_ai_on[2] && 120.0 === $wcj_ai_on[0] );
	wcj_ai_check( 'native_nested_later_origin', 2 === count( $wcj_ai_raw_role['events'] ) && 'hit' === $wcj_ai_raw_role['events'][0]['cache'] && 1 === $wcj_ai_raw_role['events'][0]['cache_origin'] && 'miss' === $wcj_ai_raw_role['events'][1]['cache'] && 0 === $wcj_ai_raw_role['events'][1]['parent'] );
	wcj_ai_check( 'projected_later_origin_preserved', 1 === $wcj_ai_role['events'][0]['cache_origin'] && $wcj_ai_role['events'][1]['input'] === $wcj_ai_raw_role['events'][1]['input'] && $wcj_ai_role['events'][1]['output'] === $wcj_ai_raw_role['events'][1]['output'] && $wcj_ai_role['events'][1]['decisions'] === $wcj_ai_raw_role['events'][1]['decisions'] );
	wcj_ai_check( 'native_nonempty_rule_observation', 'global_multiplier' === $wcj_ai_role['events'][1]['decisions'][0]['reason'] && 'woocommerce_product_get_price' === $wcj_ai_role['events'][0]['hook'] );
} catch ( Throwable $wcj_ai_exception ) {
	$wcj_ai_failed = true;
} finally {
	foreach ( $wcj_ai_removed as $wcj_ai_callback ) {
		add_filter( 'woocommerce_product_get_price', $wcj_ai_callback[0], $wcj_ai_callback[1], $wcj_ai_callback[2] );
	}
	try {
		if ( null !== $wcj_ai_token && null !== $wcj_ai_session ) {
			$wcj_ai_session->destroy( $wcj_ai_token );
			$wcj_ai_sessions_after = wcj_ai_projection_sessions( $wcj_ai_owner->ID );
			$wcj_ai_cleanup        = ! array_key_exists( hash( 'sha256', $wcj_ai_token ), $wcj_ai_sessions_after ) && ! array_diff_key( $wcj_ai_sessions_after, $wcj_ai_sessions_before );
			foreach ( $wcj_ai_sessions_before as $wcj_ai_key => $wcj_ai_entry ) {
				if ( array_key_exists( $wcj_ai_key, $wcj_ai_sessions_after ) ) {
					$wcj_ai_cleanup = $wcj_ai_cleanup && $wcj_ai_entry === $wcj_ai_sessions_after[ $wcj_ai_key ];
				} elseif ( $wcj_ai_entry['expiration'] < time() ) {
					++$wcj_ai_expired_pruned; // Native session manager drops expired entries; active sessions must survive.
				} else {
					$wcj_ai_cleanup = false;
				}
			}
		} else {
			$wcj_ai_cleanup = true; // Token is assigned before the first persistence attempt.
		}
	} catch ( Throwable $wcj_ai_cleanup_exception ) {
		$wcj_ai_cleanup = false;
	}
	unset( $_COOKIE[ LOGGED_IN_COOKIE ], $wcj_ai_token );
}
echo wp_json_encode(
	array(
		'scope'                                     => 'native synthetic method/projection only, not HTTP/browser/client, receipt or consent proof',
		'cases'                                     => $wcj_ai_cases,
		'exception'                                 => $wcj_ai_failed,
		'dedicated_session_cleanup_verified'        => $wcj_ai_cleanup,
		'expired_sessions_pruned_by_native_manager' => $wcj_ai_expired_pruned,
	),
	JSON_PRETTY_PRINT
);
exit( $wcj_ai_failed || ! $wcj_ai_cleanup ? 1 : 0 );

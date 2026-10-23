<?php
/**
 * Finite private Returns setup guide regressions on the actual module.
 * Exact Booster option/REST/role helpers and parent is_enabled method are loaded
 * from the supplied source. Only request, translation and option primitives are
 * synthetic. No native WordPress, database, orders, scheduling or mail execution.
 *
 * Usage: php -n FILE ROOT [normal|missing-role].
 *
 * @package Booster_Tests
 */

if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) || extension_loaded( 'mysqli' ) ) {
	exit( 2 );
}
$wcj_setup_root = $argv[1] ?? '';
$wcj_setup_mode = $argv[2] ?? 'normal';
$GLOBALS['wcj_setup_composition_mode'] = $wcj_setup_mode;
if ( ! is_file( $wcj_setup_root . '/includes/class-wcj-returns.php' ) || ! in_array( $wcj_setup_mode, array( 'normal', 'missing-role', 'composition' ), true ) ) {
	exit( 2 );
}
error_reporting( E_ALL );
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
$GLOBALS['wcj_setup_fixture'] = array(
	'checks'  => array(),
	'reads'   => array(),
	'calls'   => array(),
	'imports' => array(),
);

/**
 * Extract one whole actual function with token-balanced braces.
 *
 * @param string $path Source file.
 * @param string $name Function or method name.
 * @return string Actual complete function bytes.
 * @throws RuntimeException When the exact pinned function is absent.
 */
function wcj_setup_function( $path, $name ) {
	$tokens = token_get_all( file_get_contents( $path ) );
	$count  = count( $tokens );
	for ( $i = 0; $i < $count; ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) {
			continue;
		}
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
			++$j;
		}
		if ( ! is_array( $tokens[ $j ] ) || T_STRING !== $tokens[ $j ][0] || $name !== $tokens[ $j ][1] ) {
			continue;
		}
		$code    = '';
		$depth   = 0;
		$started = false;
		for ( $k = $i; $k < $count; ++$k ) {
			$token = $tokens[ $k ];
			$code .= is_array( $token ) ? $token[1] : $token;
			if ( '{' === $token ) {
				++$depth;
				$started = true;
			} elseif ( '}' === $token ) {
				--$depth;
			}
			if ( $started && 0 === $depth ) {
				$GLOBALS['wcj_setup_fixture']['imports'][ $name ] = hash( 'sha256', $code );
				return $code;
			}
		}
	}
	throw new RuntimeException( 'Pinned function missing' );
}

/**
 * Record a literal assertion without suppression or skipped cases.
 *
 * @param string $name Assertion identity.
 * @param mixed  $actual Actual result.
 * @param mixed  $expected Frozen expected value.
 */
function wcj_setup_check( $name, $actual, $expected ) {
	$GLOBALS['wcj_setup_fixture']['checks'][] = array(
		'name'     => $name,
		'pass'     => $actual === $expected,
		'actual'   => $actual,
		'expected' => $expected,
	);
}

/** Reset only synthetic request primitives, keeping the actual module object. */
function wcj_setup_reset() {
	$GLOBALS['wcj_setup_fixture']['context'] = array(
		'admin'       => true,
		'logged'      => true,
		'capable'     => true,
		'rest'        => false,
		'translation' => false,
		'allowlist'   => null,
		'throw_role'  => false,
	);
	$GLOBALS['wcj_setup_fixture']['options'] = array(
		'wcj_returns_enabled'               => 'yes',
		'wcj_modules_by_user_roles_enabled' => 'no',
	);
	$GLOBALS['wcj_setup_fixture']['roles']   = array( 'administrator' );
	$GLOBALS['wcj_setup_fixture']['reads']   = array();
	$GLOBALS['wcj_setup_fixture']['calls']   = array();
	$GLOBALS['wcj_setup_fixture']['throw']   = array();
	$GLOBALS['wcj_setup_fixture']['plugin']  = (object) array(
		'all_modules' => array(),
		'options'     => array(),
	);
	if ( isset( $GLOBALS['wcj_setup_fixture']['module'] ) ) {
		$GLOBALS['wcj_setup_fixture']['plugin']->all_modules['returns'] = $GLOBALS['wcj_setup_fixture']['module'];
	}
	unset( $GLOBALS['wcj_modules_by_user_roles_data'] );
	$_GET  = array();
	$_POST = array();
}

/** Current synthetic admin context. */
function is_admin() {
	return $GLOBALS['wcj_setup_fixture']['context']['admin']; }
/** Current synthetic authentication. */
function is_user_logged_in() {
	return $GLOBALS['wcj_setup_fixture']['context']['logged']; }
/**
 * Current synthetic staff capability.
 *
 * @param mixed $cap Synthetic primitive input.
 */
function current_user_can( $cap ) {
	return 'manage_woocommerce' === $cap && $GLOBALS['wcj_setup_fixture']['context']['capable']; }
/** Current synthetic actor ID. */
function get_current_user_id() {
	return $GLOBALS['wcj_setup_fixture']['context']['logged'] ? 7 : 0; }
/**
 * Actual role helper consumes this synthetic role object.
 *
 * @throws RuntimeException On the selected role-reader failure.
 */
function wp_get_current_user() {
	if ( $GLOBALS['wcj_setup_fixture']['context']['throw_role'] ) {
		throw new RuntimeException( 'private-role-exception' );
	}
	return (object) array( 'roles' => $GLOBALS['wcj_setup_fixture']['roles'] );
}
/** Actual cached option helper consumes this synthetic plugin object. */
function WCJ() {
	return $GLOBALS['wcj_setup_fixture']['plugin']; }
/** Registered module identity getter. */
function w_c_j() {
	return WCJ(); }
/**
 * Record exact raw option key/default/order.
 *
 * @param mixed $key Synthetic primitive input.
 * @param mixed $default Synthetic primitive input.
 * @throws RuntimeException On explicitly selected fixture failure.
 */
function get_option( $key, $default = false ) {
    if ( 'composition' === $GLOBALS['wcj_setup_composition_mode'] && 'wcj_returns_enabled' === $key && isset( $GLOBALS['wcj_setup_fixture']['store'] ) ) {
        foreach ( debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 4 ) as $frame ) {
            if ( isset( $frame['object'] ) && $frame['object'] instanceof WCJ_Returns_Service ) {
                ( new ReflectionProperty( WCJ_Returns_Service::class, 'store' ) )->setValue( $frame['object'], $GLOBALS['wcj_setup_fixture']['store'] );
            }
        }
    }
    if ( 'composition' === $GLOBALS['wcj_setup_composition_mode'] && 'active_plugins' === $key ) {
        $GLOBALS['wcj_setup_fixture']['activation_reads'][] = $key;
    }
	$GLOBALS['wcj_setup_fixture']['reads'][] = array(
		'key'     => $key,
		'default' => $default,
	);
	if ( in_array( $key, $GLOBALS['wcj_setup_fixture']['throw'], true ) ) {
		throw new RuntimeException( 'private-option-exception' );
	}
	return array_key_exists( $key, $GLOBALS['wcj_setup_fixture']['options'] ) ? $GLOBALS['wcj_setup_fixture']['options'][ $key ] : $default;
}
/**
 * Allowlist primitive consumed by actual Booster helpers.
 *
 * @param mixed $hook Synthetic primitive input.
 * @param mixed $value Synthetic primitive input.
 */
function apply_filters( $hook, $value ) {
	return 'wcj_enabled_module_allowlist' === $hook ? $GLOBALS['wcj_setup_fixture']['context']['allowlist'] : $value; }
/**
 * Minimal WordPress key sanitation primitive.
 *
 * @param mixed $value Synthetic primitive input.
 */
function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
/** Actual REST helper prefix primitive. */
function rest_get_url_prefix() {
	return 'wp-json'; }
/**
 * Actual REST helper site primitive.
 *
 * @param mixed $path Synthetic primitive input.
 */
function site_url( $path ) {
	return 'https://example.invalid/' . $path; }
/**
 * Actual REST helper parser primitive.
 *
 * @param mixed $url Synthetic primitive input.
 */
function wp_parse_url( $url ) {
	return parse_url( $url ); }
/**
 * Actual REST helper current URL primitive.
 *
 * @param mixed $args Synthetic primitive input.
 */
function add_query_arg( $args ) {
	return 'https://example.invalid/' . ( $GLOBALS['wcj_setup_fixture']['context']['rest'] ? 'wp-json/store/v1/cart' : 'wp-admin/admin.php?page=wcj-returns' ); }
/**
 * Actual REST helper input primitive.
 *
 * @param mixed $value Synthetic primitive input.
 */
function sanitize_text_field( $value ) {
	return strip_tags( $value ); }
/**
 * Minimal input primitive.
 *
 * @param mixed $value Synthetic primitive input.
 */
function wp_unslash( $value ) {
	return $value; }
/**
 * Fixed translation primitive, optionally hostile for escaping proof.
 *
 * @param mixed $text Synthetic primitive input.
 * @param mixed $domain Synthetic primitive input.
 */
function __( $text, $domain = '' ) {
	return $GLOBALS['wcj_setup_fixture']['context']['translation'] ? '<script>translation</script>' : $text; }
/**
 * HTML escaping primitive.
 *
 * @param mixed $text Synthetic primitive input.
 */
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
/**
 * Translated escaping primitive.
 *
 * @param mixed $text Synthetic primitive input.
 * @param mixed $domain Synthetic primitive input.
 */
function esc_html__( $text, $domain = '' ) {
	return esc_html( __( $text, $domain ) ); }
/**
 * URL escaping primitive.
 *
 * @param mixed $value Synthetic primitive input.
 */
function esc_url( $value ) {
	return esc_html( $value ); }
/**
 * Existing storage-form destination primitive.
 *
 * @param mixed $path Synthetic primitive input.
 */
function admin_url( $path = '' ) {
	return 'https://example.invalid/wp-admin/' . $path; }
/**
 * Existing literal nonce markup primitive, not nonce acceptance.
 *
 * @param mixed $action Synthetic primitive input.
 */
function wp_nonce_field( $action ) {
	echo '<input type="hidden" name="_wpnonce" value="synthetic-' . esc_html( $action ) . '">'; }
/**
 * Constructor hook observation primitive.
 *
 * @param mixed $name Synthetic primitive input.
 * @param mixed $callback Synthetic primitive input.
 * @param mixed $priority Synthetic primitive input.
 */
function add_action( $name, $callback, $priority = 10 ) {
	$GLOBALS['wcj_setup_fixture']['calls'][] = $name; }
/**
 * Existing capability denial observation primitive.
 *
 * @param mixed $message Synthetic primitive input.
 * @throws RuntimeException On explicitly selected fixture failure.
 */
function wp_die( $message ) {
	throw new RuntimeException( 'denied' ); }
/**
 * Error type primitive.
 *
 * @param mixed $value Synthetic primitive input.
 */
function is_wp_error( $value ) {
	return $value instanceof WP_Error; }
/** Minimal error container, never business data. */
class WP_Error {
	/**
	 * Finite code.
	 *
	 * @var string
	 */
	private $code;
	/**
	 * Store a finite code.
	 *
	 * @param mixed $code Synthetic primitive input.
	 * @param mixed $message Synthetic primitive input.
	 */
	public function __construct( $code, $message = '' ) {
		$this->code = $code; }
	/** Return the finite code. */
	public function get_error_code() {
		return $this->code; }
}

if ( 'composition' === $wcj_setup_mode ) {
    $wcj_setup_wp_plugin = $argv[3] ?? '';
    if ( ! is_file( $wcj_setup_wp_plugin ) || '0e50769b8e2a82aa9687e89852c1a51d5682a95636ff38ec80435fe956bb7236' !== hash_file( 'sha256', $wcj_setup_wp_plugin ) ) { exit( 2 ); }
    eval( wcj_setup_function( $wcj_setup_wp_plugin, 'did_action' ) );
}
wcj_setup_reset();
$wcj_setup_core      = $wcj_setup_root . '/includes/functions/wcj-functions-booster-core.php';
$wcj_setup_functions = array( 'wcj_is_rest', 'wcj_is_module_enabled' );
if ( false !== strpos( file_get_contents( $wcj_setup_core ), 'function wcj_get_enabled_module_allowlist(' ) ) {
	$wcj_setup_functions = array( 'wcj_is_rest', 'wcj_get_enabled_module_allowlist', 'wcj_is_module_allowed_by_allowlist', 'wcj_is_module_enabled' );
}
foreach ( $wcj_setup_functions as $wcj_setup_name ) {
	eval( wcj_setup_function( $wcj_setup_core, $wcj_setup_name ) );
}
if ( in_array( $wcj_setup_mode, array( 'normal', 'composition' ), true ) ) {
	eval( wcj_setup_function( $wcj_setup_core, 'wcj_check_modules_by_user_roles' ) );
}
eval( wcj_setup_function( $wcj_setup_root . '/includes/functions/wcj-functions-core.php', 'wcj_get_option' ) );
// Only the parent's actual admission method is needed, not full plugin bootstrap.
eval( 'class WCJ_Module { public $id; public $short_desc; public $desc; public $desc_pro; public $type="module"; public $parent_id; public function __construct() {} public ' . wcj_setup_function( $wcj_setup_root . '/includes/classes/class-wcj-module.php', 'is_enabled' ) . ' }' );
$GLOBALS['wcj_setup_fixture']['options']['wcj_returns_enabled'] = 'no';
$GLOBALS['wpdb']                        = (object) array( 'prefix' => '!' ); // Actual service fails before SQL; no DB double runs queries.
$wcj_setup_module                       = require $wcj_setup_root . '/includes/class-wcj-returns.php';
$GLOBALS['wcj_setup_fixture']['module'] = $wcj_setup_module;
$wcj_setup_observer                     = method_exists( $wcj_setup_module, 'setup_observations' ) ? new ReflectionMethod( $wcj_setup_module, 'setup_observations' ) : null;
$wcj_setup_renderer                     = method_exists( $wcj_setup_module, 'setup_guide' ) ? new ReflectionMethod( $wcj_setup_module, 'setup_guide' ) : null;

/**
 * Invoke the actual collector; absent method is literal baseline red.
 *
 * @param mixed $module Synthetic primitive input.
 * @param mixed $method Synthetic primitive input.
 */
function wcj_setup_observe( $module, $method ) {
	return $method ? $method->invoke( $module ) : null; }
/**
 * Invoke the actual renderer, with no replacement implementation.
 *
 * @param mixed $module Synthetic primitive input.
 * @param mixed $method Synthetic primitive input.
 */
function wcj_setup_render( $module, $method ) {
	ob_start();
	if ( $method ) {
		$method->invoke( $module );
	}
	return ob_get_clean();
}
/** Return only four guide observations, separating legacy/module admission reads. */
function wcj_setup_reads() {
	return array_values(
		array_filter(
			$GLOBALS['wcj_setup_fixture']['reads'],
			static function ( $read ) {
				return in_array( $read['key'], array( 'wcj_returns_window_days', 'wcj_returns_notifications', 'wcj_returns_guest', 'wcj_returns_guest_page' ), true );
			}
		)
	);
}

$wcj_setup_defaults  = array(
	'wcj_returns_window_days'   => '30',
	'wcj_returns_notifications' => 'no',
	'wcj_returns_guest'         => 'no',
	'wcj_returns_guest_page'    => '',
);
$wcj_setup_read_gold = array(
	array(
		'key'     => 'wcj_returns_window_days',
		'default' => '30',
	),
	array(
		'key'     => 'wcj_returns_notifications',
		'default' => 'no',
	),
	array(
		'key'     => 'wcj_returns_guest',
		'default' => 'no',
	),
	array(
		'key'     => 'wcj_returns_guest_page',
		'default' => '',
	),
);
$wcj_setup_legacy    = '<div class="wrap"><h1>Return Requests</h1><p>Approval records a decision only. Refunds, restocking and payments remain separate merchant actions. Guest access requires a separately enabled, compatible HTTPS verification page and private auth storage.</p><p>Private storage must be initialized and compatible with this store before requests can be used.</p><form method="post" action="https://example.invalid/wp-admin/admin-post.php"><input type="hidden" name="action" value="wcj_returns_storage"><input type="hidden" name="_wpnonce" value="synthetic-wcj_returns_storage"><button class="button" type="submit">Initialize private return storage</button></form></div>';

/** Fixed composition-only checks inserted into each maintained guide fixture. */
if ( 'composition' === $wcj_setup_mode ) {
    $wcj_composition_activation = $argv[4] ?? '';
    if ( ! is_file( $wcj_composition_activation ) || 'cf6c5f8a91bd107d3ff06f503708d6117d7a375dce5a05c801acf1f13ca92e49' !== hash_file( 'sha256', $wcj_composition_activation ) ) {
        exit( 2 );
    }
    eval( wcj_setup_function( $wcj_composition_activation, 'is_plugin_active' ) );
    eval( wcj_setup_function( $wcj_composition_activation, 'is_plugin_active_for_network' ) );

    /** The actual inherited listing calls readiness; no SQL may execute. */
    class WCJ_Composed_Readiness_Store extends WCJ_Returns_Store {
        public $readiness_calls = 0;

        public function require_ready() {
            ++$this->readiness_calls;
            throw new RuntimeException( 'storage_unavailable' );
        }
    }

    function is_multisite() {
        return true;
    }
    function get_site_option( $key, $default = false ) {
        $GLOBALS['wcj_setup_fixture']['activation_reads'][] = $key;
        return array();
    }
    function remove_query_arg( $keys ) {
        return 'https://example.invalid/wp-admin/admin.php?page=wcj-returns';
    }

    /** Exact method/action identities, not a total-form snapshot. */
    function wcj_composition_forms( $html ) {
        preg_match_all( '/<form\b([^>]*)>(.*?)<\/form>/s', $html, $matches, PREG_SET_ORDER );
        $forms = array();
        foreach ( $matches as $match ) {
            preg_match( '/method="([^"]+)"/', $match[1], $method );
            preg_match( '/action="([^"]+)"/', $match[1], $url );
            preg_match( '/name="action" value="([^"]+)"/', $match[2], $action );
            preg_match( '/name="page" value="([^"]+)"/', $match[2], $page );
            $forms[] = array( $method[1] ?? '', $url[1] ?? '', $action[1] ?? ( $page[1] ?? '' ) );
        }
        return $forms;
    }

    function wcj_composition_context() {
        wcj_setup_reset();
        $GLOBALS['wp_actions'] = array( 'plugins_loaded' => 1 );
        $GLOBALS['wpdb'] = (object) array( 'prefix' => 'offline_' );
        $GLOBALS['wcj_setup_fixture']['store'] = new WCJ_Composed_Readiness_Store();
        $GLOBALS['wcj_setup_fixture']['activation_reads'] = array();
        $GLOBALS['wcj_setup_fixture']['options']['active_plugins'] = array();
        $GLOBALS['wcj_setup_fixture']['plugin']->options['wcj_returns_enabled'] = 'yes';
    }

    function wcj_composition_admin( $module ) {
        ob_start();
        $error = '';
        try {
            $module->admin();
        } catch ( RuntimeException $caught ) {
            $error = $caught->getMessage();
        }
        return array( ob_get_clean(), $error );
    }

    // All literal expected outputs and counters are fixed before execution.
    $wcj_composition_heading = '<div class="wrap"><h1>Return Requests</h1><p>Approval records a decision only. Refunds, restocking and payments remain separate merchant actions. Guest access requires a separately enabled, compatible HTTPS verification page and private auth storage.</p>';
    $wcj_composition_warning = '<div class="notice notice-warning inline"><p>Return Refund and Exchange for WooCommerce is active on this site. Review overlapping customer return requests, notifications and refund workflows before using both plugins. This best-effort check does not confirm an enabled workflow, an actual conflict or tested interoperability, and does not cover every extension. Booster does not disable, import, migrate, refund or configure the other plugin.</p></div>';
    $wcj_composition_unknown = '<div class="notice notice-info inline"><p>Third-party return and refund overlap checking is unavailable. Review any other return or refund extensions before using Booster Return Requests. No absence or interoperability conclusion is available.</p></div>';
    $wcj_composition_guide = '<section class="wcj-returns-setup-guide"><h2>Return Requests setup guide</h2><p>These are bounded configured values only, not a readiness or eligibility check. They do not confirm Page existence, content, HTTPS, cache privacy, storage or email delivery.</p><dl><dt>Configured return window (days)</dt><dd><code>30</code></dd><dt>Notification opt-in</dt><dd>Off</dd><dt>Guest verification opt-in</dt><dd>Off</dd><dt>Configured guest Page ID</dt><dd>(empty)</dd></dl>';
    $wcj_composition_guide_end = '<p>Automatic safe-test requests are not provided by this guide. It creates no test order or request and sends no mail.</p></section>';
    $wcj_composition_storage_form = array( 'post', 'https://example.invalid/wp-admin/admin-post.php', 'wcj_returns_storage' );
    $wcj_composition_filter_form = array( 'get', 'https://example.invalid/wp-admin/admin.php', 'wcj-returns' );
    $wcj_composition_elite = method_exists( WCJ_Returns_Service::class, 'filtered_queue' );
    $wcj_composition_branches = $wcj_composition_elite ? array( 'storage', 'input', 'non-storage' ) : array( 'storage' );

    foreach ( $wcj_composition_branches as $wcj_composition_branch ) {
        foreach ( array( 'active', 'unknown', 'silent' ) as $wcj_composition_state ) {
            wcj_composition_context();
            if ( 'active' === $wcj_composition_state ) {
                $GLOBALS['wcj_setup_fixture']['options']['active_plugins'] = array( 'woo-refund-and-exchange-lite/woocommerce-refund-and-exchange-lite.php' );
            } elseif ( 'unknown' === $wcj_composition_state ) {
                $GLOBALS['wcj_setup_fixture']['throw'] = array( 'active_plugins' );
            }
            if ( 'input' === $wcj_composition_branch ) {
                $_GET = array( 'wcj_returns_status' => array( 'approved' ) );
            } elseif ( 'non-storage' === $wcj_composition_branch ) {
                // Constructor failure, deliberately NOT the valid-prefix readiness case.
                unset( $GLOBALS['wcj_setup_fixture']['store'] );
                $GLOBALS['wpdb']->prefix = '!';
            }
            list( $wcj_composition_html, $wcj_composition_error ) = wcj_composition_admin( $wcj_setup_module );
            $wcj_composition_label = $wcj_composition_branch . '/' . $wcj_composition_state . ' ';
            $wcj_composition_panel = 'active' === $wcj_composition_state ? $wcj_composition_warning : ( 'unknown' === $wcj_composition_state ? $wcj_composition_unknown : '' );
            wcj_setup_check( $wcj_composition_label . 'entry no exception', $wcj_composition_error, '' );
            wcj_setup_check( $wcj_composition_label . 'heading warning guide exact prefix', strpos( $wcj_composition_html, $wcj_composition_heading . $wcj_composition_panel . $wcj_composition_guide ), 0 );
            wcj_setup_check( $wcj_composition_label . 'guide disclaimer exact', substr_count( $wcj_composition_html, $wcj_composition_guide_end ), 1 );
            wcj_setup_check( $wcj_composition_label . 'one outer wrap', substr_count( $wcj_composition_html, 'class="wrap"' ), 1 );
            wcj_setup_check( $wcj_composition_label . 'no nested guide wrap', strpos( $wcj_composition_html, 'class="wrap wcj-returns-setup-guide"' ), false );
            wcj_setup_check( $wcj_composition_label . 'exact observations before branch', wcj_setup_reads(), $wcj_setup_read_gold );
            $wcj_composition_expected_forms = 'non-storage' === $wcj_composition_branch ? array() : ( $wcj_composition_elite ? array( $wcj_composition_filter_form ) : array() );
            if ( 'storage' === $wcj_composition_branch ) {
                $wcj_composition_expected_forms[] = $wcj_composition_storage_form;
            }
            wcj_setup_check( $wcj_composition_label . 'exact recognized forms no decisions dispatch', wcj_composition_forms( $wcj_composition_html ), $wcj_composition_expected_forms );
            wcj_setup_check( $wcj_composition_label . 'no operations or writes', $GLOBALS['wcj_setup_fixture']['calls'], array() );
            wcj_setup_check( $wcj_composition_label . 'POST untouched', $_POST, array() );
            if ( 'storage' === $wcj_composition_branch ) {
                wcj_setup_check( $wcj_composition_label . 'readiness actually reached once', $GLOBALS['wcj_setup_fixture']['store']->readiness_calls, 1 );
                wcj_setup_check( $wcj_composition_label . 'valid prefix unchanged', $GLOBALS['wpdb']->prefix, 'offline_' );
                wcj_setup_check( $wcj_composition_label . 'storage message after guide', strpos( $wcj_composition_html, 'Private storage must be initialized' ) > strpos( $wcj_composition_html, $wcj_composition_guide_end ), true );
            } elseif ( 'input' === $wcj_composition_branch ) {
                wcj_setup_check( $wcj_composition_label . 'invalid input never reaches readiness', $GLOBALS['wcj_setup_fixture']['store']->readiness_calls, 0 );
                wcj_setup_check( $wcj_composition_label . 'fixed input message after guide', strpos( $wcj_composition_html, 'The request filters are invalid. Choose a listed status and a positive exact order ID, or reset the filters.' ) > strpos( $wcj_composition_html, $wcj_composition_guide_end ), true );
            } else {
                wcj_setup_check( $wcj_composition_label . 'fixed unavailable message after guide', strpos( $wcj_composition_html, 'Verify current permission and module access' ) > strpos( $wcj_composition_html, $wcj_composition_guide_end ), true );
            }
        }
    }

    // Panel denial and real filtered-service denial are different contracts.
    $wcj_composition_denials = array( 'frontend', 'rest', 'logged-out', 'capability', 'disabled', 'excluded-role', 'not-included-role', 'stale-cache', 'malformed-cache' );
    if ( $wcj_composition_elite ) {
        $wcj_composition_denials[] = 'allowlist-excluded';
    }
    foreach ( $wcj_composition_denials as $wcj_composition_denial ) {
        wcj_composition_context();
        if ( 'frontend' === $wcj_composition_denial ) {
            $GLOBALS['wcj_setup_fixture']['context']['admin'] = false;
        } elseif ( 'rest' === $wcj_composition_denial ) {
            $GLOBALS['wcj_setup_fixture']['context']['rest'] = true;
        } elseif ( 'logged-out' === $wcj_composition_denial ) {
            $GLOBALS['wcj_setup_fixture']['context']['logged'] = false;
        } elseif ( 'capability' === $wcj_composition_denial ) {
            $GLOBALS['wcj_setup_fixture']['context']['capable'] = false;
        } elseif ( 'disabled' === $wcj_composition_denial ) {
            $GLOBALS['wcj_setup_fixture']['options']['wcj_returns_enabled'] = 'no';
            $GLOBALS['wcj_setup_fixture']['plugin']->options['wcj_returns_enabled'] = 'no';
        } elseif ( 'allowlist-excluded' === $wcj_composition_denial ) {
            $GLOBALS['wcj_setup_fixture']['context']['allowlist'] = array();
        } else {
            $GLOBALS['wcj_setup_fixture']['options']['wcj_modules_by_user_roles_enabled'] = 'yes';
            if ( 'excluded-role' === $wcj_composition_denial ) {
                $GLOBALS['wcj_setup_fixture']['options']['wcj_modules_by_user_roles_excl_administrator'] = array( 'returns' );
            } elseif ( 'not-included-role' === $wcj_composition_denial ) {
                $GLOBALS['wcj_setup_fixture']['options']['wcj_modules_by_user_roles_incl_administrator'] = array( 'other' );
            } else {
                $GLOBALS['wcj_modules_by_user_roles_data'] = 'stale-cache' === $wcj_composition_denial ? array( 'role' => 'customer', 'modules_incl' => '', 'modules_excl' => '' ) : false;
            }
        }
        list( $wcj_composition_html ) = wcj_composition_admin( $wcj_setup_module );
        wcj_setup_check( $wcj_composition_denial . ' full page guide absent', strpos( $wcj_composition_html, 'wcj-returns-setup-guide' ), false );
        wcj_setup_check( $wcj_composition_denial . ' full page warning absent', strpos( $wcj_composition_html, 'overlap checking' ) === false && strpos( $wcj_composition_html, 'Return Refund and Exchange' ) === false, true );
        wcj_setup_check( $wcj_composition_denial . ' no guide observations', wcj_setup_reads(), array() );
        wcj_setup_check( $wcj_composition_denial . ' no activation reads', $GLOBALS['wcj_setup_fixture']['activation_reads'], array() );
        wcj_setup_check( $wcj_composition_denial . ' no operations writes', $GLOBALS['wcj_setup_fixture']['calls'], array() );
        if ( $wcj_composition_elite && ! in_array( $wcj_composition_denial, array( 'frontend', 'rest' ), true ) ) {
            wcj_setup_check( $wcj_composition_denial . ' real service no readiness', $GLOBALS['wcj_setup_fixture']['store']->readiness_calls, 0 );
            wcj_setup_check( $wcj_composition_denial . ' real denial no storage initialization', strpos( $wcj_composition_html, 'value="wcj_returns_storage"' ), false );
        }
    }
    restore_error_handler();
    $wcj_composition_failed = count( array_filter( $GLOBALS['wcj_setup_fixture']['checks'], static function ( $check ) { return ! $check['pass']; } ) );
    echo json_encode( array( 'kind' => 'composition06_actual_merchant_offline_private_panels', 'assertions' => count( $GLOBALS['wcj_setup_fixture']['checks'] ), 'failed' => $wcj_composition_failed, 'cases' => $GLOBALS['wcj_setup_fixture']['checks'], 'imports' => $GLOBALS['wcj_setup_fixture']['imports'] ), JSON_PRETTY_PRINT ) . PHP_EOL;
    exit( $wcj_composition_failed ? 1 : 0 );
}

if ( 'missing-role' === $wcj_setup_mode ) {
	wcj_setup_reset();
	wcj_setup_check( 'missing role dependency no guide', wcj_setup_render( $wcj_setup_module, $wcj_setup_renderer ), '' );
	wcj_setup_check( 'missing role dependency no observations', wcj_setup_reads(), array() );
	wcj_setup_check( 'missing role dependency collector denied', wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), null );
} else {
	wcj_setup_reset();
	wcj_setup_check( 'default exact four observations', wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), $wcj_setup_defaults );
	wcj_setup_check( 'default exact raw keys defaults order count', wcj_setup_reads(), $wcj_setup_read_gold );
	wcj_setup_check( 'guide collector invokes no IO/hooks', $GLOBALS['wcj_setup_fixture']['calls'], array() );
	wcj_setup_check( 'guide collector leaves POST empty', $_POST, array() );
	wcj_setup_check(
		'guide collector leaves raw settings unchanged',
		$GLOBALS['wcj_setup_fixture']['options'],
		array(
			'wcj_returns_enabled'               => 'yes',
			'wcj_modules_by_user_roles_enabled' => 'no',
		)
	);
	wcj_setup_check( 'guide collector leaves invalid DB prefix untouched', $GLOBALS['wpdb']->prefix, '!' );

	$wcj_setup_denials = array( 'frontend', 'logged-out', 'no-capability', 'rest-url', 'rest-query', 'disabled', 'role-excluded', 'role-not-included', 'stale-role', 'malformed-role-cache', 'missing-role-cache-field', 'non-string-current-role', 'role-reader-throws', 'stale-module', 'missing-module', 'missing-plugin' );
	if ( function_exists( 'wcj_get_enabled_module_allowlist' ) ) {
		$wcj_setup_denials[] = 'allowlist-excluded';
	}
	foreach ( $wcj_setup_denials as $wcj_setup_denial ) {
		wcj_setup_reset();
		if ( 'frontend' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['context']['admin'] = false; }
		if ( 'logged-out' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['context']['logged'] = false; }
		if ( 'no-capability' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['context']['capable'] = false; }
		if ( 'rest-url' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['context']['rest'] = true; }
		if ( 'rest-query' === $wcj_setup_denial ) {
			$_GET['rest_route'] = '/wp-json/wc/store/v1/cart'; }
		if ( 'disabled' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['options']['wcj_returns_enabled'] = 'no'; }
		if ( 'allowlist-excluded' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['context']['allowlist'] = array( 'other' ); }
		if ( in_array( $wcj_setup_denial, array( 'role-excluded', 'role-not-included', 'stale-role', 'malformed-role-cache', 'missing-role-cache-field', 'non-string-current-role', 'role-reader-throws' ), true ) ) {
			$GLOBALS['wcj_setup_fixture']['options']['wcj_modules_by_user_roles_enabled'] = 'yes'; }
		if ( 'role-excluded' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['options']['wcj_modules_by_user_roles_excl_administrator'] = array( 'returns' ); }
		if ( 'role-not-included' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['options']['wcj_modules_by_user_roles_incl_administrator'] = array( 'other' ); }
		if ( 'stale-role' === $wcj_setup_denial ) {
			$GLOBALS['wcj_modules_by_user_roles_data'] = array(
				'role'         => 'shop_manager',
				'modules_incl' => '',
				'modules_excl' => '',
			); }
		if ( 'malformed-role-cache' === $wcj_setup_denial ) {
			$GLOBALS['wcj_modules_by_user_roles_data'] = 'private-cache-text'; }
		if ( 'missing-role-cache-field' === $wcj_setup_denial ) {
			$GLOBALS['wcj_modules_by_user_roles_data'] = array(
				'modules_incl' => '',
				'modules_excl' => '',
			); }
		if ( 'non-string-current-role' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['roles']     = array( 7 );
			$GLOBALS['wcj_modules_by_user_roles_data'] = array(
				'role'         => 7,
				'modules_incl' => '',
				'modules_excl' => '',
			); }
		if ( 'role-reader-throws' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['context']['throw_role'] = true;
			$GLOBALS['wcj_modules_by_user_roles_data']             = array(
				'role'         => 'administrator',
				'modules_incl' => '',
				'modules_excl' => '',
			); }
		if ( 'stale-module' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['plugin']->all_modules['returns'] = clone $wcj_setup_module; }
		if ( 'missing-module' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['plugin']->all_modules = array(); }
		if ( 'missing-plugin' === $wcj_setup_denial ) {
			$GLOBALS['wcj_setup_fixture']['plugin'] = null; }
		$wcj_setup_cache = $GLOBALS['wcj_modules_by_user_roles_data'] ?? null;
		wcj_setup_check( $wcj_setup_denial . ' guide output denied', wcj_setup_render( $wcj_setup_module, $wcj_setup_renderer ), '' );
		wcj_setup_check( $wcj_setup_denial . ' no guide raw reads', wcj_setup_reads(), array() );
		wcj_setup_check( $wcj_setup_denial . ' collector denied', wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), null );
		wcj_setup_check( $wcj_setup_denial . ' no IO/hooks', $GLOBALS['wcj_setup_fixture']['calls'], array() );
		if ( null !== $wcj_setup_cache ) {
			wcj_setup_check( $wcj_setup_denial . ' primed role cache preserved', $GLOBALS['wcj_modules_by_user_roles_data'], $wcj_setup_cache ); }
	}

	foreach ( array( 'administrator', 'shop_manager', 'custom_manager' ) as $wcj_setup_role ) {
		wcj_setup_reset();
		$GLOBALS['wcj_setup_fixture']['roles']                                        = array( $wcj_setup_role );
		$GLOBALS['wcj_setup_fixture']['options']['wcj_modules_by_user_roles_enabled'] = 'yes';
		$GLOBALS['wcj_setup_fixture']['options'][ 'wcj_modules_by_user_roles_incl_' . $wcj_setup_role ] = array( 'returns' );
		wcj_setup_check( $wcj_setup_role . ' actual allowed role helper', wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), $wcj_setup_defaults );
		wcj_setup_check( $wcj_setup_role . ' four observations', wcj_setup_reads(), $wcj_setup_read_gold );
	}
	wcj_setup_reset();
	$GLOBALS['wcj_setup_fixture']['options']['wcj_modules_by_user_roles_enabled'] = 'yes';
	$GLOBALS['wcj_modules_by_user_roles_data']                                    = array(
		'role'         => 'administrator',
		'modules_incl' => array( 'returns' ),
		'modules_excl' => '',
	);
	wcj_setup_check( 'same current primed role admitted', wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), $wcj_setup_defaults );

	foreach ( array( 'wcj_returns_window_days', 'wcj_returns_guest_page' ) as $wcj_setup_scalar_key ) {
		foreach ( array(
			'empty'                        => '',
			'zero-string'                  => '0',
			'zero-int'                     => 0,
			'int'                          => 77,
			'negative-int'                 => -2,
			'invalid-policy-not-validated' => '999',
			'markup'                       => '<script>x</script>',
			'unicode'                      => 'ÃƒÂ©',
			'byte64'                       => str_repeat( 'x', 64 ),
		) as $wcj_setup_label => $wcj_setup_value ) {
			wcj_setup_reset();
			$GLOBALS['wcj_setup_fixture']['options'][ $wcj_setup_scalar_key ] = $wcj_setup_value;
			$wcj_setup_expected                          = $wcj_setup_defaults;
			$wcj_setup_expected[ $wcj_setup_scalar_key ] = (string) $wcj_setup_value;
			wcj_setup_check( $wcj_setup_scalar_key . ' scalar ' . $wcj_setup_label, wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), $wcj_setup_expected );
		}
		foreach ( array(
			'null'         => null,
			'false'        => false,
			'true'         => true,
			'float'        => 30.0,
			'array'        => array( 'private-secret' ),
			'object'       => (object) array( 'private' => 'secret' ),
			'byte65'       => str_repeat( 'x', 65 ),
			'invalid-utf8' => "\xC3",
			'nul'          => "a\0b",
		) as $wcj_setup_label => $wcj_setup_value ) {
			wcj_setup_reset();
			$GLOBALS['wcj_setup_fixture']['options'][ $wcj_setup_scalar_key ] = $wcj_setup_value;
			$wcj_setup_expected                          = $wcj_setup_defaults;
			$wcj_setup_expected[ $wcj_setup_scalar_key ] = null;
			wcj_setup_check( $wcj_setup_scalar_key . ' unavailable ' . $wcj_setup_label, wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), $wcj_setup_expected );
		}
	}
	foreach ( array( 'wcj_returns_notifications', 'wcj_returns_guest' ) as $wcj_setup_switch_key ) {
		foreach ( array( 'yes', 'no', 'YES', '', '0', 0, true, false, null, array( 'yes' ) ) as $wcj_setup_index => $wcj_setup_value ) {
			wcj_setup_reset();
			$GLOBALS['wcj_setup_fixture']['options'][ $wcj_setup_switch_key ] = $wcj_setup_value;
			$wcj_setup_expected                          = $wcj_setup_defaults;
			$wcj_setup_expected[ $wcj_setup_switch_key ] = in_array( $wcj_setup_value, array( 'yes', 'no' ), true ) ? $wcj_setup_value : null;
			wcj_setup_check( $wcj_setup_switch_key . ' exact switch ' . $wcj_setup_index, wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), $wcj_setup_expected );
		}
	}
	foreach ( array_keys( $wcj_setup_defaults ) as $wcj_setup_throw_key ) {
		wcj_setup_reset();
		$GLOBALS['wcj_setup_fixture']['throw']      = array( $wcj_setup_throw_key );
		$wcj_setup_expected                         = $wcj_setup_defaults;
		$wcj_setup_expected[ $wcj_setup_throw_key ] = null;
		wcj_setup_check( $wcj_setup_throw_key . ' observation exception fixed unavailable', wcj_setup_observe( $wcj_setup_module, $wcj_setup_observer ), $wcj_setup_expected );
		wcj_setup_check( $wcj_setup_throw_key . ' read exception still exactly four attempts', wcj_setup_reads(), $wcj_setup_read_gold );
	}

	wcj_setup_reset();
	$wcj_setup_html = wcj_setup_render( $wcj_setup_module, $wcj_setup_renderer );
	foreach ( array( 'Return Requests setup guide', 'Configured return window (days)', '<code>30</code>', 'Notification opt-in', 'Guest verification opt-in', 'Configured guest Page ID', 'Off', '(empty)', 'configured values only', 'not a readiness or eligibility check', 'elapsed 24-hour days', 'My Account', 'does not issue a refund or restock', 'unknown delivery must not be blindly retried', '[wcj_returns_guest]', 'Action Scheduler', 'same-origin HTTPS', 'isolated staging', 'Automatic safe-test requests are not provided by this guide.' ) as $wcj_setup_literal ) {
		wcj_setup_check( 'default guide contains fixed literal ' . $wcj_setup_literal, false !== strpos( $wcj_setup_html, $wcj_setup_literal ), true );
	}
	wcj_setup_check( 'guide renderer exactly four observations', wcj_setup_reads(), $wcj_setup_read_gold );
	wcj_setup_check( 'guide has no form or link or script', (bool) preg_match( '/<(form|a|script)\b/i', $wcj_setup_html ), false );
	wcj_setup_check( 'guide has no IO/hook calls', $GLOBALS['wcj_setup_fixture']['calls'], array() );
	wcj_setup_reset();
	$GLOBALS['wcj_setup_fixture']['options']['wcj_returns_window_days']   = '<script>x</script>';
	$GLOBALS['wcj_setup_fixture']['options']['wcj_returns_guest_page']    = '<b>77</b>';
	$GLOBALS['wcj_setup_fixture']['options']['wcj_returns_notifications'] = 'yes';
	$GLOBALS['wcj_setup_fixture']['options']['wcj_returns_guest']         = 'YES';
	$wcj_setup_html = wcj_setup_render( $wcj_setup_module, $wcj_setup_renderer );
	wcj_setup_check( 'configured window HTML escaped', false !== strpos( $wcj_setup_html, '<code>&lt;script&gt;x&lt;/script&gt;</code>' ), true );
	wcj_setup_check( 'configured page HTML escaped', false !== strpos( $wcj_setup_html, '<code>&lt;b&gt;77&lt;/b&gt;</code>' ), true );
	wcj_setup_check( 'exact yes switch On', false !== strpos( $wcj_setup_html, '<dt>Notification opt-in</dt><dd>On</dd>' ), true );
	wcj_setup_check( 'nonexact yes switch Unknown', false !== strpos( $wcj_setup_html, '<dt>Guest verification opt-in</dt><dd>Unknown</dd>' ), true );
	wcj_setup_check( 'configured HTML cannot execute', (bool) preg_match( '/<(script|b)\b/', $wcj_setup_html ), false );
	wcj_setup_reset();
	$GLOBALS['wcj_setup_fixture']['options']['wcj_returns_window_days'] = array( 'private-secret' );
	$GLOBALS['wcj_setup_fixture']['options']['wcj_returns_guest_page']  = str_repeat( 'private-secret', 20 );
	$wcj_setup_html = wcj_setup_render( $wcj_setup_module, $wcj_setup_renderer );
	wcj_setup_check( 'malformed scalar fixed unavailable twice', substr_count( $wcj_setup_html, '<dd>Unavailable</dd>' ), 2 );
	wcj_setup_check( 'malformed scalar private text not printed', false === strpos( $wcj_setup_html, 'private-secret' ), true );
	wcj_setup_reset();
	$GLOBALS['wcj_setup_fixture']['throw'] = array( 'wcj_returns_window_days' );
	$wcj_setup_html                        = wcj_setup_render( $wcj_setup_module, $wcj_setup_renderer );
	wcj_setup_check( 'observation exception text not printed', false === strpos( $wcj_setup_html, 'private-option-exception' ), true );
	wcj_setup_check( 'observation exception visible Unavailable', false !== strpos( $wcj_setup_html, '<dd>Unavailable</dd>' ), true );
	wcj_setup_reset();
	$GLOBALS['wcj_setup_fixture']['context']['translation'] = true;
	$wcj_setup_html = wcj_setup_render( $wcj_setup_module, $wcj_setup_renderer );
	wcj_setup_check( 'translations escaped', false !== strpos( $wcj_setup_html, '&lt;script&gt;translation&lt;/script&gt;' ), true );
	wcj_setup_check( 'hostile translation no script tag', false === strpos( $wcj_setup_html, '<script>' ), true );

	// Complete actual entry uses original service's storage error, not a fake queue.
	wcj_setup_reset();
	$wcj_setup_guide                       = wcj_setup_render( $wcj_setup_module, $wcj_setup_renderer );
	$GLOBALS['wcj_setup_fixture']['reads'] = array();
	ob_start();
	$wcj_setup_module->admin();
	$wcj_setup_html = ob_get_clean();
	wcj_setup_check( 'unavailable storage actual admin guide present', false !== strpos( $wcj_setup_html, 'Return Requests setup guide' ), true );
	wcj_setup_check( 'unavailable storage actual admin guide before legacy wrap', false !== strpos( $wcj_setup_html, 'Return Requests setup guide' ) && strpos( $wcj_setup_html, '<h1>Return Requests</h1>' ) > strpos( $wcj_setup_html, 'Return Requests setup guide' ), true );
	wcj_setup_check( 'retained unavailable admin form exact suffix', substr( $wcj_setup_html, strlen( $wcj_setup_guide ) ), $wcj_setup_legacy );
	wcj_setup_check( 'admin guide four observations only', wcj_setup_reads(), $wcj_setup_read_gold );
	wcj_setup_check( 'admin no hook/scheduler/mail operations', $GLOBALS['wcj_setup_fixture']['calls'], array() );
	wcj_setup_check( 'admin no POST/write intent created', $_POST, array() );
	wcj_setup_check( 'admin invalid DB prefix unchanged', $GLOBALS['wpdb']->prefix, '!' );
	wcj_setup_reset();
	$GLOBALS['wcj_setup_fixture']['context']['capable'] = false;
	$wcj_setup_denied                                   = false;
	ob_start();
	try {
		$wcj_setup_module->admin();
	} catch ( RuntimeException $wcj_setup_error ) {
		$wcj_setup_denied = 'denied' === $wcj_setup_error->getMessage();
	}
	$wcj_setup_html = ob_get_clean();
	wcj_setup_check( 'retained full admin capability denial', $wcj_setup_denied, true );
	wcj_setup_check( 'denied full admin emits no output', $wcj_setup_html, '' );
	wcj_setup_check( 'denied full admin no observations or admission reads', $GLOBALS['wcj_setup_fixture']['reads'], array() );
}
$wcj_setup_checks = $GLOBALS['wcj_setup_fixture']['checks'];
$wcj_setup_failed = count(
	array_filter(
		$wcj_setup_checks,
		static function ( $check ) {
			return ! $check['pass'];
		}
	)
);
echo json_encode(
	array(
		'mode'       => $wcj_setup_mode,
		'assertions' => count( $wcj_setup_checks ),
		'failed'     => $wcj_setup_failed,
		'imports'    => $GLOBALS['wcj_setup_fixture']['imports'],
		'checks'     => $wcj_setup_checks,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;
exit( $wcj_setup_failed ? 1 : 0 );

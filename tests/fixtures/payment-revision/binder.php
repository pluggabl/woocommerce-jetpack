<?php
/**
 * Offline payment-revision fixture binder. Never loads wp-load.php or a store.
 * Caller supplies plugin, WooCommerce 11.1.0 and pinned WordPress source roots.
 */

function codex_revision_source( $root, $relative, $sha256 ) {
	$root = realpath( $root );
	$path = false === $root ? false : realpath( $root . '/' . $relative );
	if ( false === $path || 0 !== strpos( $path, $root . DIRECTORY_SEPARATOR ) || ! is_file( $path ) || hash_file( 'sha256', $path ) !== $sha256 ) {
		throw new RuntimeException( 'PINNED_SOURCE_MISMATCH:' . $relative );
	}
	return array( 'path' => $path, 'bytes' => filesize( $path ), 'sha256' => $sha256 );
}

function codex_revision_bind( $plugin_root, $woo_root, $wp_root, $tier ) {
	$plugin_pins = array(
		'free'  => '262d3a602ebc2c5009093a5f51c41b201ed333938204656fe8246ba7c6381335',
		'plus'  => 'd8beb0b1ed83aa87f8a9ba7f7fbdf45abdd1d1cca5b5c6709d687afd322313aa',
		'elite' => '79399d5ec84253dbc8af229a90308d548e258ab9ba9eff1a2369e15a2e2ad9d2',
	);
	if ( ! isset( $plugin_pins[ $tier ] ) ) {
		throw new RuntimeException( 'UNKNOWN_TIER' );
	}
	$dependencies = array();
	foreach ( array(
		'includes/abstracts/abstract-wc-data.php' => '7a9c9f5150506a1d86324f9d95c5f6ed5db8b73ed0d3fdd0ebb341ec7b0f2daf',
		'includes/class-wc-datetime.php' => '1fa32f308786fa66284c673984e33602dae1d642255f923b16551e874828d6df',
		'includes/class-wc-meta-data.php' => '8aa027314ad537d2dfe5207f1e8372ae14f061aacb6e5da20dac7dd1e45f4d3a',
		'includes/abstracts/abstract-wc-order.php' => '2b3384b5e8a49ad8d6e948eaa40cd5492c7e8f0354617df6c6db9774b02fbe64',
	) as $relative => $sha256 ) {
		$dependencies[ $relative ] = codex_revision_source( $woo_root, $relative, $sha256 );
	}
	$wp_list_util = codex_revision_source( $wp_root, 'wp-includes/class-wp-list-util.php', '90171523d0b89315e0cba603b03dc177998de8f3e9895a956cbeed42955a085b' );
	$wp_functions = codex_revision_source( $wp_root, 'wp-includes/functions.php', 'cac8a17e293df99f0683a88debd3ba5e5cce274183a4e9fd3c9f18375747f41c' );
	$pluck = file_get_contents( __DIR__ . '/wp-list-pluck.php' );
	if ( hash( 'sha256', $pluck ) !== 'da5ba573e329acba6ed636fdc862bb06d384bc309b474ca1970d267601fdf449' || 1 !== substr_count( file_get_contents( $wp_functions['path'] ), substr( $pluck, 6 ) ) ) {
		throw new RuntimeException( 'WP_PLUCK_BOUNDARY' );
	}
	$date = file_get_contents( __DIR__ . '/order-date-view.php' );
	if ( hash( 'sha256', $date ) !== '37b3cab99ffde5da886bf1174c520d449ba1f94fea721af029cfb98b59856d2a' ) {
		throw new RuntimeException( 'ORDER_DATE_VIEW' );
	}
	$date_methods = substr( $date, strpos( $date, "\tpublic function get_date_modified(" ), -2 );
	if ( 1 !== substr_count( file_get_contents( $dependencies['includes/abstracts/abstract-wc-order.php']['path'] ), $date_methods ) ) {
		// The two original public methods are separated in the full Woo class.
		foreach ( array(
			"\tpublic function get_date_modified( \$context = 'view' ) {\n\t\treturn \$this->get_prop( 'date_modified', \$context );\n\t}",
			"\tpublic function set_date_modified( \$date = null ) {\n\t\t\$this->set_date_prop( 'date_modified', \$date );\n\t}",
		) as $method ) {
			if ( 1 !== substr_count( file_get_contents( $dependencies['includes/abstracts/abstract-wc-order.php']['path'] ), $method ) || 1 !== substr_count( $date, $method ) ) {
				throw new RuntimeException( 'ORDER_DATE_METHOD_BOUNDARY' );
			}
		}
	}
	$plugin = codex_revision_source( $plugin_root, 'includes/class-wcj-payment-gateways-fees.php', $plugin_pins[ $tier ] );
	$code = file_get_contents( $plugin['path'] );
	$terminal = 'return new WCJ_Payment_Gateways_Fees();';
	if ( 1 !== substr_count( $code, $terminal ) || trim( substr( $code, strrpos( $code, $terminal ) ) ) !== $terminal || 1 !== substr_count( $code, 'class WCJ_Payment_Gateways_Fees extends WCJ_Module {' ) ) {
		throw new RuntimeException( 'PLUGIN_CLASS_BOUNDARY' );
	}
	$code = str_replace( $terminal, '/* class-only908: no constructor */', $code );
	return array(
		'dependencies' => $dependencies,
		'wp_list_util' => $wp_list_util,
		'wp_list_pluck' => array( 'code' => $pluck ),
		'order_date_class' => array( 'code' => $date ),
		'views' => array( $tier . '-after' => array( 'code' => $code ) ),
	);
}

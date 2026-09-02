<?php
/**
 * Booster 8.4 Light-tier wishlist archive boundary for wp eval-file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$failures = array();
$assert   = function ( $condition, $message ) use ( &$failures ) {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

remove_shortcode( 'wcj_wishlist_button' );
$source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-wcj-wishlist.php' );
$assert( false === strpos( $source, "add_shortcode( 'wcj_wishlist_button'" ), 'Light tier registered the Elite-only builder shortcode.' );
$assert( false === strpos( $source, 'render_archive_wishlist_button' ), 'Light tier contains the Elite-only archive renderer.' );
$assert( ! shortcode_exists( 'wcj_wishlist_button' ), 'Light tier exposed the Elite-only builder shortcode.' );

$result = array(
	'passed'   => empty( $failures ),
	'edition'  => 'light',
	'failures' => $failures,
);
if ( ! empty( $failures ) && class_exists( 'WP_CLI' ) ) {
	WP_CLI::error( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
}
echo wp_json_encode( $result, JSON_PRETTY_PRINT ) . PHP_EOL;

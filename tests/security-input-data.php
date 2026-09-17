<?php
/** Data-only legacy field decoding regression. Run using wp eval-file. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 1 ); }
$cases = array(
    'plain' => array( 'engraving', 'engraving' ),
    'serialized-string' => array( serialize( 'engraving' ), 'engraving' ),
    'false' => array( 'b:0;', false ),
    'zero' => array( 'i:0;', 0 ),
    'list' => array( serialize( array( 'red', 'blue' ) ), array( 'red', 'blue' ) ),
    'nested-data' => array( serialize( array( array( 'red' ) ) ), array( array( 'red' ) ) ),
    'object-rejected' => array( serialize( new stdClass() ), '' ),
    'nested-object-rejected' => array( serialize( array( new stdClass() ) ), '' ),
);
foreach ( $cases as $id => $case ) {
    if ( wcj_maybe_unserialize_plain_data( $case[0] ) !== $case[1] ) { throw new RuntimeException( $id ); }
}
if ( 'red, blue' !== wcj_maybe_unserialize_and_implode( serialize( array( 'red', 'blue' ) ), ', ' ) ) { throw new RuntimeException( 'formatted-list' ); }
echo "PASS: data-only decoding and ordinary field values\n";

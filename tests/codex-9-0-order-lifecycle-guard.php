<?php
/**
 * Offline actual-method order lifecycle guard proof.
 * Hash-bound methods are extracted from complete production files into isolated
 * classes. Order lookup, options, filter and order methods are explicit doubles.
 * No WordPress bootstrap, real Woo order, database, network or native UI.
 *
 * @package Booster_Tests
 */
if ( 'cli' !== PHP_SAPI || ! in_array( $argc, array( 4, 5 ), true ) ) {
 exit( 2 );
}
$root = realpath( $argv[1] );
$tier = $argv[2];
$base = realpath( $argv[3] );
$mode = isset( $argv[4] ) ? $argv[4] : 'full';
if ( ! in_array( $mode, array( 'full', 'string-zero-only' ), true ) || ( 'string-zero-only' === $mode && 'free' !== $tier ) ) {
 exit( 2 );
}
if ( ! $root || ! $base || ! in_array( $tier, array( 'free', 'plus', 'elite' ), true ) ) {
 exit( 2 );
}
$guards = json_decode( <<<'GUARDS'
{
  "free": {
    "before": "bc3f32537dae68628817aec3122d4efde0612122fd1401ebdaebb61d08d49016",
    "after": "af78b5b799e65a65c9bdad62de2ff981614cbb1ed0b4160253d83e83c4692bc7",
    "before_method": "9777620498999ab7cf047a356f57a00e4f186b9c87be44170b1686c8a0e1a243",
    "after_method": "4e06ab48c9ccf0f1ae83b4c8e7c33667ebd6059a0de35eeb1f16af7bffc5c6e0"
  },
  "plus": {
    "before": "014d9ef39aacf355bf9fc0680804aa6da938c1ce6e4b2e6cbb68e05fada0ad6f",
    "after": "ac05f59fa15e11590426000eced784341f876151075db6033e87783ab651f8f0",
    "before_method": "9777620498999ab7cf047a356f57a00e4f186b9c87be44170b1686c8a0e1a243",
    "after_method": "4e06ab48c9ccf0f1ae83b4c8e7c33667ebd6059a0de35eeb1f16af7bffc5c6e0"
  },
  "elite": {
    "before": "adc04b6e8f9e77f4237d972a5ea216cc650ce641e2872b5bf15d03c3d8ce3109",
    "after": "6fcd963a9a169f0015fef1eed65dabf2c08da3326b258e9bb99bcdc685a2cf99",
    "before_method": "9777620498999ab7cf047a356f57a00e4f186b9c87be44170b1686c8a0e1a243",
    "after_method": "4e06ab48c9ccf0f1ae83b4c8e7c33667ebd6059a0de35eeb1f16af7bffc5c6e0"
  }
}
GUARDS
, true );
$cases = json_decode( <<<'CASES'
[
  {
    "scenario": {
      "name": "int-zero",
      "id": 0,
      "exists": false,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "after": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "string-zero",
      "id": "0",
      "exists": false,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "after": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "false",
      "id": false,
      "exists": false,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "after": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "null",
      "id": null,
      "exists": false,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "after": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "empty-string",
      "id": "",
      "exists": false,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "after": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "float-zero",
      "id": 0.0,
      "exists": false,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "after": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "empty-array",
      "id": [],
      "exists": false,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "after": {
      "events": [],
      "return": null,
      "error": null,
      "final_status": null
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "unresolved-nonzero-order",
      "id": 314,
      "exists": false,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ]
      ],
      "return": null,
      "error": "Error",
      "final_status": null
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ]
      ],
      "return": null,
      "error": null,
      "final_status": null
    },
    "parity": false
  },
  {
    "scenario": {
      "name": "failed-unrestricted",
      "id": 314,
      "exists": true,
      "status": "failed",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "failed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "failed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "failed-restricted",
      "id": 314,
      "exists": true,
      "status": "failed",
      "method": "cod",
      "option": [],
      "filtered": [
        "cod"
      ]
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "failed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "failed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "unrestricted-empty-array",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "unrestricted-filter-empty-string",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": ""
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "unrestricted-filter-false",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": false
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "allowed-method",
      "id": 314,
      "exists": true,
      "status": "pending",
      "method": "cod",
      "option": [],
      "filtered": [
        "cod"
      ]
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "disallowed-method",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "bacs",
      "option": [],
      "filtered": [
        "cod"
      ]
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "processing"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "processing"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "strict-numeric-method-mismatch",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "7",
      "option": [],
      "filtered": [
        7
      ]
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "processing"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "processing"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "strict-string-method-match",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "7",
      "option": [],
      "filtered": [
        "7"
      ]
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "method-case-mismatch",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "COD",
      "option": [],
      "filtered": [
        "cod"
      ]
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "processing"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "processing"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "already-completed-still-same-call",
      "id": 314,
      "exists": true,
      "status": "completed",
      "method": "cod",
      "option": [],
      "filtered": []
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "filter-return-authority",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "cod",
      "option": [
        "bacs"
      ],
      "filtered": [
        "cod"
      ]
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          [
            "bacs"
          ]
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          [
            "bacs"
          ]
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ],
        [
          "update_status",
          "completed"
        ]
      ],
      "return": null,
      "error": null,
      "final_status": "completed"
    },
    "parity": true
  },
  {
    "scenario": {
      "name": "unsupported-filter-type-preserved",
      "id": 314,
      "exists": true,
      "status": "processing",
      "method": "cod",
      "option": [],
      "filtered": "cod"
    },
    "before": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ]
      ],
      "return": null,
      "error": "TypeError",
      "final_status": "processing"
    },
    "after": {
      "events": [
        [
          "lookup",
          314
        ],
        [
          "option",
          "wcj_order_auto_complete_payment_methods",
          []
        ],
        [
          "filter",
          "booster_option",
          "",
          []
        ],
        [
          "status"
        ],
        [
          "payment_method"
        ]
      ],
      "return": null,
      "error": "TypeError",
      "final_status": "processing"
    },
    "parity": true
  }
]
CASES
, true );
$GLOBALS['codex_rows'] = array();
$GLOBALS['codex_env'] = array();
$GLOBALS['codex_events'] = array();
/** Record exact typed expectations, not a second order-status calculator. */
function codex_check( $name, $expected, $actual ) {
 $GLOBALS['codex_rows'][] = array( 'name' => $name, 'expected' => $expected, 'actual' => $actual, 'pass' => $expected === $actual );
}
/** Hash/inverse admission checks are uncounted in the focused Free mode. */
function codex_source_check( $name, $expected, $actual ) {
 if ( $expected !== $actual ) {
  throw new RuntimeException( 'Production source precondition mismatch: ' . $name );
 }
 if ( 'full' === $GLOBALS['mode'] ) {
  codex_check( $name, $expected, $actual );
 }
}
$before = file_get_contents( $base . '/includes/class-wcj-orders.php' );
$after = file_get_contents( $root . '/includes/class-wcj-orders.php' );
foreach ( array( 'before' => $before, 'after' => $after ) as $side => $source ) {
 if ( ! hash_equals( $guards[ $tier ][ $side ], hash( 'sha256', $source ) ) ) {
  throw new RuntimeException( 'Production source identity mismatch' );
 }
 codex_source_check( 'complete-' . $side . '-source-hash', $guards[ $tier ][ $side ], hash( 'sha256', $source ) );
}
$pattern = '/^\t\tpublic function auto_complete_order\( \$order_id \) \{.*?^\t\t\}/ms';
if ( 1 !== preg_match_all( $pattern, $before, $before_matches ) || 1 !== preg_match_all( $pattern, $after, $after_matches ) ) {
 throw new RuntimeException( 'Actual method boundary mismatch' );
}
$before_method = $before_matches[0][0];
$after_method = $after_matches[0][0];
codex_source_check( 'actual-before-method-hash', $guards[ $tier ]['before_method'], hash( 'sha256', $before_method ) );
codex_source_check( 'actual-after-method-hash', $guards[ $tier ]['after_method'], hash( 'sha256', $after_method ) );
$eol = false !== strpos( $before, "\r\n" ) ? "\r\n" : "\n";
$guard = "\t\t\tif ( ! \$order ) {" . $eol . "\t\t\t\treturn;" . $eol . "\t\t\t}" . $eol;
codex_source_check( 'exactly-one-method-guard', 1, substr_count( $after_method, $guard ) );
codex_source_check( 'whole-method-inverse', $before_method, str_replace( $guard, '', $after_method ) );
codex_source_check( 'whole-file-inverse', $before, str_replace( $guard, '', $after ) );
// Eval is restricted to these exact hash-bound extracted methods, never request data.
eval( 'class Codex_Lifecycle_Before {' . $before_method . '}' );
eval( 'class Codex_Lifecycle_After {' . $after_method . '}' );
/** Public lookup contract double only. No actual factory or data store executes. */
function wc_get_order( $id ) {
 $GLOBALS['codex_events'][] = array( 'lookup', $id );
 return $GLOBALS['codex_order'];
}
function wcj_get_option( $key, $default = false ) {
 $GLOBALS['codex_events'][] = array( 'option', $key, $default );
 return $GLOBALS['codex_env']['option'];
}
function apply_filters( $hook, $value, $option ) {
 $GLOBALS['codex_events'][] = array( 'filter', $hook, $value, $option );
 return $GLOBALS['codex_env']['filtered'];
}
/** In-memory order double. update_status cannot write a database or trigger hooks. */
class Codex_Synthetic_Order {
 public $status;
 public $payment_method;
 public function __construct( $status, $payment_method ) {
  $this->status = $status;
  $this->payment_method = $payment_method;
 }
 public function get_status() {
  $GLOBALS['codex_events'][] = array( 'status' );
  return $this->status;
 }
 public function get_payment_method() {
  $GLOBALS['codex_events'][] = array( 'payment_method' );
  return $this->payment_method;
 }
 public function update_status( $status ) {
  $GLOBALS['codex_events'][] = array( 'update_status', $status );
  $this->status = $status;
 }
}
function codex_run_method( $class, $scenario ) {
 $GLOBALS['codex_env'] = $scenario;
 $GLOBALS['codex_events'] = array();
 $GLOBALS['codex_order'] = $scenario['exists'] ? new Codex_Synthetic_Order( $scenario['status'], $scenario['method'] ) : false;
 $result = null;
 $error = null;
 try {
  $result = ( new $class() )->auto_complete_order( $scenario['id'] );
 } catch ( Throwable $exception ) {
  $error = get_class( $exception );
 }
 return array( 'events' => $GLOBALS['codex_events'], 'return' => $result, 'error' => $error, 'final_status' => $GLOBALS['codex_order'] ? $GLOBALS['codex_order']->status : null );
}
if ( 'string-zero-only' === $mode ) {
 $cases = array_values( array_filter( $cases, function ( $case ) { return 'string-zero' === $case['scenario']['name']; } ) );
 if ( 1 !== count( $cases ) ) {
  throw new RuntimeException( 'Focused case selection mismatch' );
 }
}
$executed_scenarios = array();
foreach ( $cases as $case ) {
 $executed_scenarios[] = $case['scenario']['name'];
 $scenario = $case['scenario'];
 $before_result = codex_run_method( 'Codex_Lifecycle_Before', $scenario );
 $after_result = codex_run_method( 'Codex_Lifecycle_After', $scenario );
 codex_check( $scenario['name'] . '-baseline-literal', $case['before'], $before_result );
 codex_check( $scenario['name'] . '-corrected-literal', $case['after'], $after_result );
 if ( $case['parity'] ) {
  codex_check( $scenario['name'] . '-exact-valid-or-falsy-parity', $before_result, $after_result );
 } else {
  codex_check( $scenario['name'] . '-no-policy-status-or-update', array( array( 'lookup', 314 ) ), $after_result['events'] );
  codex_check( $scenario['name'] . '-original-Error-observed-offline', 'Error', $before_result['error'] );
 }
}
$failed = array_filter( $GLOBALS['codex_rows'], function ( $row ) { return ! $row['pass']; } );
echo json_encode( array( 'tier' => $tier, 'mode' => $mode, 'executed_scenarios' => $executed_scenarios, 'cases' => count( $cases ), 'assertions' => count( $GLOBALS['codex_rows'] ), 'failures' => count( $failed ), 'source_only' => true, 'native_or_DB_execution' => false, 'limits' => 'Hash-bound actual-method execution with pure doubles; not actual Woo factory, tier policy filters, status persistence or native hooks.', 'checks' => $GLOBALS['codex_rows'] ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
exit( empty( $failed ) ? 0 : 1 );

<?php
/**
 * Source-bound admin-search compatibility checks.
 *
 * Run only from CLI with current and original admin source paths.
 * This fixture extracts one actual method, not the WordPress admin bootstrap.
 *
 * @package Booster\Tests
 */

if ( 'cli' !== PHP_SAPI || 3 !== $argc ) {
	exit( 2 );
}

$rows319     = array();
$failures319 = 0;

/**
 * Record one strict typed assertion.
 *
 * @param string $label Assertion label.
 * @param mixed  $actual Actual value.
 * @param mixed  $expected Literal expected value.
 * @return void
 */
function wcj_admin_search_check319( $label, $actual, $expected ) {
	global $rows319, $failures319;
	$passed    = $actual === $expected;
	$rows319[]  = array(
		'label'    => $label,
		'actual'   => $actual,
		'expected' => $expected,
		'passed'   => $passed,
	);
	if ( ! $passed ) {
		++$failures319;
	}
}

/**
 * Extract only the known, source-bound method.
 *
 * @param string $source Complete admin source bytes.
 * @return string
 */
function wcj_admin_search_method319( $source ) {
	$start = strpos( $source, 'public function wcj_array_search(' );
	if ( false === $start ) {
		throw new RuntimeException( 'Bound method start unavailable.' );
	}
	$end = strpos( $source, "\n\t\t}", $start );
	if ( false === $end ) {
		throw new RuntimeException( 'Bound method end unavailable.' );
	}
	return substr( $source, $start, $end + strlen( "\n\t\t}" ) - $start );
}

$current319  = file_get_contents( $argv[1] );
$original319 = file_get_contents( $argv[2] );
if ( false === $current319 || false === $original319 ) {
	throw new RuntimeException( 'Bound source unavailable.' );
}

wcj_admin_search_check319( 'original_source_sha256', hash( 'sha256', $original319 ), '1c47739187c5a0e9c0778bb1b69a0a83e25d4048673fd518171a8db609e49803' );
wcj_admin_search_check319( 'current_source_sha256', hash( 'sha256', $current319 ), 'f9cb8a1362c88ebfe15001b759167952d6377881e2fd58f1923e5ed55ea9cccc' );
$typed319   = 'static function ( mixed $value ) use ( $patron ): bool {';
$untyped319 = 'static function ( $value ) use ( $patron ): bool {';
if ( 1 !== substr_count( $original319, $typed319 ) || 1 !== substr_count( $current319, $untyped319 ) ) {
	throw new RuntimeException( 'Bound callback occurrence changed.' );
}
wcj_admin_search_check319( 'exact_single_parameter_inverse', str_replace( $untyped319, $typed319, $current319 ) === $original319, true );
$original_method319 = wcj_admin_search_method319( $original319 );
$current_method319  = wcj_admin_search_method319( $current319 );
wcj_admin_search_check319( 'original_method_sha256', hash( 'sha256', $original_method319 ), 'eb78730bfa8ebcd96010b651890e7a0f2699dfceb360723bec29a4b96a1e1418' );
wcj_admin_search_check319( 'current_method_sha256', hash( 'sha256', $current_method319 ), '9a2677bc0bbd6e63b046858a93fcbc4444ada1ba1c6405758bff3444937116ef' );

eval( 'class WCJ_Admin_Search_Unit319 {' . $current_method319 . '}' );
$unit319 = new WCJ_Admin_Search_Unit319();
$case_json319 = <<<'WCJ_ADMIN_SEARCH_CASES319'
[
  {
    "label": "exact_title_keys",
    "input": [
      [
        12,
        "Gateway Fees"
      ],
      [
        "legacy",
        "HPOS"
      ],
      [
        5,
        "Gateway Fees Extra"
      ]
    ],
    "pattern": "Gateway Fees",
    "expected": [
      [
        "integer",
        12,
        "Gateway Fees"
      ]
    ]
  },
  {
    "label": "case_insensitive",
    "input": [
      [
        12,
        "Gateway Fees"
      ],
      [
        "legacy",
        "HPOS"
      ],
      [
        5,
        "Gateway Fees Extra"
      ]
    ],
    "pattern": "hpos",
    "expected": [
      [
        "string",
        "legacy",
        "HPOS"
      ]
    ]
  },
  {
    "label": "leading_percent",
    "input": [
      [
        "a",
        "Gateway Fees"
      ],
      [
        "b",
        "Fees"
      ],
      [
        "c",
        "Fees Extra"
      ]
    ],
    "pattern": "%Fees",
    "expected": [
      [
        "string",
        "a",
        "Gateway Fees"
      ],
      [
        "string",
        "b",
        "Fees"
      ]
    ]
  },
  {
    "label": "trailing_percent",
    "input": [
      [
        "a",
        "Gateway Fees"
      ],
      [
        "b",
        "Gateway"
      ],
      [
        "c",
        "Other Gateway"
      ]
    ],
    "pattern": "Gateway%",
    "expected": [
      [
        "string",
        "a",
        "Gateway Fees"
      ],
      [
        "string",
        "b",
        "Gateway"
      ]
    ]
  },
  {
    "label": "both_edge_percent",
    "input": [
      [
        7,
        "Gateway Fees"
      ],
      [
        18,
        "Shipping gate"
      ],
      [
        "other",
        "Shipping"
      ]
    ],
    "pattern": "%gate%",
    "expected": [
      [
        "integer",
        7,
        "Gateway Fees"
      ],
      [
        "integer",
        18,
        "Shipping gate"
      ]
    ]
  },
  {
    "label": "no_match",
    "input": [
      [
        1,
        "Gateway Fees"
      ]
    ],
    "pattern": "Shipping",
    "expected": []
  },
  {
    "label": "empty_array",
    "input": [],
    "pattern": "%anything%",
    "expected": []
  },
  {
    "label": "existing_regex_metacharacter",
    "input": [
      [
        "one",
        "Fees"
      ],
      [
        "two",
        "FeeX"
      ],
      [
        "three",
        "Fee"
      ]
    ],
    "pattern": "Fee.",
    "expected": [
      [
        "string",
        "one",
        "Fees"
      ],
      [
        "string",
        "two",
        "FeeX"
      ]
    ]
  },
  {
    "label": "original_mixed_keys",
    "input": [
      [
        0,
        "Zero"
      ],
      [
        7,
        "Zero"
      ],
      [
        "named",
        "Zero"
      ],
      [
        -2,
        "Zero"
      ]
    ],
    "pattern": "zero",
    "expected": [
      [
        "integer",
        0,
        "Zero"
      ],
      [
        "integer",
        7,
        "Zero"
      ],
      [
        "string",
        "named",
        "Zero"
      ],
      [
        "integer",
        -2,
        "Zero"
      ]
    ]
  },
  {
    "label": "internal_percent_literal",
    "input": [
      [
        "one",
        "Gate%way"
      ],
      [
        "two",
        "Gateway"
      ]
    ],
    "pattern": "Gate%way",
    "expected": [
      [
        "string",
        "one",
        "Gate%way"
      ]
    ]
  },
  {
    "label": "single_percent_all",
    "input": [
      [
        1,
        ""
      ],
      [
        2,
        "Anything"
      ]
    ],
    "pattern": "%",
    "expected": [
      [
        "integer",
        1,
        ""
      ],
      [
        "integer",
        2,
        "Anything"
      ]
    ]
  },
  {
    "label": "empty_pattern",
    "input": [
      [
        1,
        ""
      ],
      [
        2,
        "Anything"
      ]
    ],
    "pattern": "",
    "expected": [
      [
        "integer",
        1,
        ""
      ]
    ]
  },
  {
    "label": "scalar_integer_preserves_values",
    "input": [
      [
        "int",
        7
      ],
      [
        "zero",
        0
      ],
      [
        "string",
        "7"
      ]
    ],
    "pattern": "7",
    "expected": [
      [
        "string",
        "int",
        7
      ],
      [
        "string",
        "string",
        "7"
      ]
    ]
  },
  {
    "label": "scalar_true_preserves_boolean",
    "input": [
      [
        "yes",
        true
      ],
      [
        "no",
        false
      ],
      [
        "string",
        "1"
      ]
    ],
    "pattern": "1",
    "expected": [
      [
        "string",
        "yes",
        true
      ],
      [
        "string",
        "string",
        "1"
      ]
    ]
  },
  {
    "label": "scalar_false_empty_match",
    "input": [
      [
        "false",
        false
      ],
      [
        "empty",
        ""
      ],
      [
        "zero",
        0
      ]
    ],
    "pattern": "",
    "expected": [
      [
        "string",
        "false",
        false
      ],
      [
        "string",
        "empty",
        ""
      ]
    ]
  }
]
WCJ_ADMIN_SEARCH_CASES319;
$cases319 = json_decode( $case_json319, true );
if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $cases319 ) ) {
	throw new RuntimeException( 'Literal cases unavailable.' );
}
foreach ( $cases319 as $case319 ) {
	$input319 = array();
	foreach ( $case319['input'] as $pair319 ) {
		$input319[ $pair319[0] ] = $pair319[1];
	}
	$result319 = $unit319->wcj_array_search( $input319, $case319['pattern'] );
	$actual319 = array();
	foreach ( $result319 as $key319 => $value319 ) {
		$actual319[] = array( gettype( $key319 ), $key319, $value319 );
	}
	wcj_admin_search_check319( $case319['label'], $actual319, $case319['expected'] );
}
$output319 = array(
	'fixture'        => 'admin-search-php73-current319',
	'php_version'    => PHP_VERSION,
	'assertions'     => count( $rows319 ),
	'failed'         => $failures319,
	'rows'           => $rows319,
	'product_loaded' => false,
	'limitation'     => 'Actual extracted method only, not WordPress native admin execution.',
);
$encoded319 = json_encode( $output319, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
if ( false === $encoded319 ) {
	throw new RuntimeException( 'Fixture encoding failed.' );
}
echo $encoded319, PHP_EOL;
exit( 0 === $failures319 ? 0 : 1 );

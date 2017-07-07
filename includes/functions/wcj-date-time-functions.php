<?php
/**
 * Booster for WooCommerce - Functions - Date and Time
 *
 * @version 2.9.1
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_check_single_date' ) ) {
	/**
	 * wcj_check_single_date.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function wcj_check_single_date( $_date, $args ) {
		$_date = explode( '-', $_date );
		if ( isset( $_date[0] ) ) {
			if ( $args['day_now'] < $_date[0] ) {
				return false;
			}
		}
		if ( isset( $_date[1] ) ) {
			if ( $args['day_now'] > $_date[1] ) {
				return false;
			}
		}
		return true;
	}
}

if ( ! function_exists( 'wcj_check_date' ) ) {
	/**
	 * wcj_check_date.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function wcj_check_date( $_date, $args = array() ) {
		if ( empty( $args ) ) {
			$time_now        = current_time( 'timestamp' );
			$args['day_now'] = intval( date( 'j', $time_now ) );
		}
		$_date = explode( ',', $_date );
		foreach ( $_date as $_single_date ) {
			if ( wcj_check_single_date( $_single_date, $args ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_check_time_from' ) ) {
	/**
	 * wcj_check_time_from.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_check_time_from( $time_from, $args ) {
		$time_from = explode( ':', $time_from );
		if ( isset( $time_from[0] ) && $args['hours_now'] < $time_from[0] ) {
			return false;
		}
		if ( isset( $time_from[1] ) && $time_from[0] == $args['hours_now'] && $args['minutes_now'] < $time_from[1] ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'wcj_check_time_to' ) ) {
	/**
	 * wcj_check_time_to.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_check_time_to( $time_to, $args ) {
		$time_to = explode( ':', $time_to );
		if ( isset( $time_to[0] ) && $args['hours_now'] > $time_to[0] ) {
			return false;
		}
		if ( isset( $time_to[1] ) && $time_to[0] == $args['hours_now'] && $args['minutes_now'] > $time_to[1] ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'wcj_check_single_time' ) ) {
	/**
	 * wcj_check_single_time.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_check_single_time( $_time, $args ) {
		$_time = explode( '-', $_time );
		if ( isset( $_time[0] ) ) {
			if ( ! wcj_check_time_from( $_time[0], $args ) ) {
				return false;
			}
		}
		if ( isset( $_time[1] ) ) {
			if ( ! wcj_check_time_to( $_time[1], $args ) ) {
				return false;
			}
		}
		return true;
	}
}

if ( ! function_exists( 'wcj_check_time' ) ) {
	/**
	 * wcj_check_time.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_check_time( $_time, $args = array() ) {
		if ( empty( $args ) ) {
			$time_now = current_time( 'timestamp' );
			$args['hours_now']   = intval( date( 'H', $time_now ) );
			$args['minutes_now'] = intval( date( 'i', $time_now ) );
		}
		$_time = explode( ',', $_time );
		foreach ( $_time as $_single_time ) {
			if ( wcj_check_single_time( $_single_time, $args ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_date_format_php_to_js_v2' ) ) {
	/*
	 * Matches each symbol of PHP date format standard
	 * with jQuery equivalent codeword
	 * http://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
	 *
	 * @author  Tristan Jahier
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_date_format_php_to_js_v2( $php_format ) {
		$SYMBOLS_MATCHING = array(
			// Day
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week
			'W' => '',
			// Month
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// Year
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// Time
			'a' => '',
			'A' => '',
			'B' => '',
			'g' => '',
			'G' => '',
			'h' => '',
			'H' => '',
			'i' => '',
			's' => '',
			'u' => ''
		);
		$jqueryui_format = "";
		$escaping = false;
		for ( $i = 0; $i < strlen( $php_format ); $i++ ) {
			$char = $php_format[ $i ];
			if ( $char === '\\' ) { // PHP date format escaping character
				$i++;
				$jqueryui_format .= ( $escaping ) ? $php_format[ $i ] : '\'' . $php_format[ $i ];
				$escaping = true;
			} else {
				if ( $escaping ) {
					$jqueryui_format .= "'";
					$escaping = false;
				}
				$jqueryui_format .= ( isset( $SYMBOLS_MATCHING[ $char ] ) ) ? $SYMBOLS_MATCHING[ $char ] : $char;
			}
		}
		return $jqueryui_format;
	}
}

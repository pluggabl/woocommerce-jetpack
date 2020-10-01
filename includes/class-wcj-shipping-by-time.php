<?php
/**
 * Booster for WooCommerce - Module - Shipping by Time
 *
 * @version 5.2.0
 * @since   4.0.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_Time' ) ) :

class WCJ_Shipping_By_Time extends WCJ_Module_Shipping_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   4.0.0
	 * @todo    [dev] add more "Valid time input" examples
	 * @todo    [feature] multiple time values (i.e. `__( 'Otherwise enter time one per line.', 'woocommerce-jetpack' )`)
	 */
	function __construct() {

		$this->id         = 'shipping_by_time';
		$this->short_desc = __( 'Shipping Methods by Current Date/Time', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set date and/or time to include/exclude for shipping methods to show up. (Free shipping available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Set date and/or time to include/exclude for shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-current-date-time';

		$this->condition_options = array(
			'time' => array(
				'title' => __( 'Current Date/Time', 'woocommerce-jetpack' ),
				'desc'  => '<br>' . sprintf( __( 'Current time: %s.', 'woocommerce-jetpack' ), '<code>' . current_time( 'Y-m-d H:i:s' ) . '</code>' ) . '<br>' .
					sprintf( __( 'Time <em>from</em> and time <em>to</em> must be separated with %s symbol.', 'woocommerce-jetpack' ), '<code>~</code>' ) . ' ' .
					sprintf( __( 'Each time input must be set in format that is parsable by PHP %s function.', 'woocommerce-jetpack' ),
						'<a href="http://php.net/manual/en/function.strtotime.php" target="_blank"><code>strtotime()</code></a>' ) . ' ' .
					sprintf( __( 'Valid time input examples are: %s', 'woocommerce-jetpack' ), '<ul><li><code>' . implode( '</code></li><li><code>', array(
							'this week Thursday 4:30pm ~ this week Friday 4:30pm',
							'this year September 1 ~ this year September 30',
						) ) . '</code></li></ul>' ),
				'type'  => 'text',
				'class' => '',
				'css'   => 'width:100%',
			),
		);

		parent::__construct();

	}

	/**
	 * parse_time.
	 *
	 * @version 4.0.0
	 * @since   4.0.0
	 */
	function parse_time( $value ) {
		$value = explode( '~', $value );
		if ( 2 != count( $value ) ) {
			return false;
		}
		if ( false === ( $time_from = strtotime( $value[0] ) ) ) {
			return false;
		}
		if ( false === ( $time_to   = strtotime( $value[1] ) ) ) {
			return false;
		}
		return array( 'time_from' => $time_from, 'time_to' => $time_to );
	}

	/**
	 * check.
	 *
	 * @version 4.0.0
	 * @since   4.0.0
	 */
	function check( $options_id, $values, $include_or_exclude, $package ) {
		switch( $options_id ) {
			case 'time':
				if ( $parsed_time = $this->parse_time( $values ) ) {
					$current_time = (int) current_time( 'timestamp' );
					return ( $current_time >= $parsed_time['time_from'] && $current_time <= $parsed_time['time_to'] );
				}
				return ( 'include' == $include_or_exclude ); // not parsable time input - leaving shipping method enabled
		}
	}

	/**
	 * get_condition_options.
	 *
	 * @version 4.0.0
	 * @since   4.0.0
	 */
	function get_condition_options( $options_id ) {
		switch( $options_id ) {
			case 'time':
				return '';
		}
	}

	/**
	 * get_extra_option_desc.
	 *
	 * @version 4.0.0
	 * @since   4.0.0
	 */
	function get_extra_option_desc( $option_id ) {
		$values = wcj_get_option( $option_id, '' );
		if ( ! empty( $values ) ) {
			if ( $parsed_time = $this->parse_time( $values ) ) {
				return '. ' . sprintf( __( 'According to current time, your time input will be parsed as: from %s to %s.', 'woocommerce-jetpack' ),
					'<code>' . date( 'Y-m-d H:i:s', $parsed_time['time_from'] ) . '</code>', '<code>' . date( 'Y-m-d H:i:s', $parsed_time['time_to'] ) . '</code>' );
			} else {
				return '. <strong>' . sprintf( __( 'Error: %s', 'woocommerce-jetpack' ), __( 'Time input is not parsable!', 'woocommerce-jetpack' ) ) . '</strong>';
			}
		}
		return '';
	}

}

endif;

return new WCJ_Shipping_By_Time();

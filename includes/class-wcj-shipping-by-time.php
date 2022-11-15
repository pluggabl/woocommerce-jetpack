<?php
/**
 * Booster for WooCommerce - Module - Shipping by Time
 *
 * @version 5.6.8
 * @since   4.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Shipping_By_Time' ) ) :
	/**
	 * WCJ_Shipping_By_Time.
	 *
	 * @version 5.2.0
	 * @since   4.0.0
	 */
	class WCJ_Shipping_By_Time extends WCJ_Module_Shipping_By_Condition {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   4.0.0
		 * @todo    [dev] add more "Valid time input" examples
		 * @todo    [feature] multiple time values (i.e. `__( 'Otherwise enter time one per line.', 'woocommerce-jetpack' )`)
		 */
		public function __construct() {

			$this->id         = 'shipping_by_time';
			$this->short_desc = __( 'Shipping Methods by Current Date/Time', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set date and/or time to include/exclude for shipping methods to show up. (Free shipping available in Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Set date and/or time to include/exclude for shipping methods to show up.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-shipping-methods-by-current-date-time';

			$this->condition_options = array(
				'time' => array(
					'title' => __( 'Current Date/Time', 'woocommerce-jetpack' ),
					/* translators: %s: translators Added */
					'desc'  => '<br>' . sprintf( __( 'Current time: %s.', 'woocommerce-jetpack' ), '<code>' . current_time( 'Y-m-d H:i:s' ) . '</code>' ) . '<br>' .
					/* translators: %s: translators Added */
					sprintf( __( 'Time <em>from</em> and time <em>to</em> must be separated with %s symbol.', 'woocommerce-jetpack' ), '<code>~</code>' ) . ' ' .
					sprintf(
						/* translators: %s: translators Added */
						__( 'Each time input must be set in format that is parsable by PHP %s function.', 'woocommerce-jetpack' ),
						'<a href="http://php.net/manual/en/function.strtotime.php" target="_blank"><code>strtotime()</code></a>'
					) . ' ' .
					sprintf(
						/* translators: %s: translators Added */
						__( 'Valid time input examples are: %s', 'woocommerce-jetpack' ),
						'<ul><li><code>' . implode(
							'</code></li><li><code>',
							array(
								'this week Thursday 4:30pm ~ this week Friday 4:30pm',
								'this year September 1 ~ this year September 30',
							)
						) . '</code></li></ul>'
					),
					'type'  => 'text',
					'class' => '',
					'css'   => 'width:100%',
				),
			);

			parent::__construct();

		}

		/**
		 * Parse_time.
		 *
		 * @version 4.0.0
		 * @since   4.0.0
		 * @param  array $value defines the value.
		 */
		public function parse_time( $value ) {
			$value = explode( '~', $value );
			if ( 2 !== count( $value ) ) {
				return false;
			}
			$time_from = strtotime( $value[0] );
			if ( false === $time_from ) {
				return false;
			}
			$time_to = strtotime( $value[1] );
			if ( false === $time_to ) {
				return false;
			}
			return array(
				'time_from' => $time_from,
				'time_to'   => $time_to,
			);
		}

		/**
		 * Check.
		 *
		 * @version 5.6.8
		 * @since   4.0.0
		 * @param  string $options_id defines the options_id.
		 * @param  array  $values defines the values.
		 * @param  string $include_or_exclude defines the include_or_exclude.
		 * @param  array  $package defines the package.
		 */
		public function check( $options_id, $values, $include_or_exclude, $package ) {
			switch ( $options_id ) {
				case 'time':
					$parsed_time = $this->parse_time( $values );
					if ( $parsed_time ) {
						$current_time = wcj_get_timestamp_date_from_gmt();
						return ( $current_time >= $parsed_time['time_from'] && $current_time <= $parsed_time['time_to'] );
					}
					return ( 'include' === $include_or_exclude ); // not parsable time input - leaving shipping method enabled.
			}
		}

		/**
		 * Get_condition_options.
		 *
		 * @version 4.0.0
		 * @since   4.0.0
		 * @param  string $options_id defines the options_id.
		 */
		public function get_condition_options( $options_id ) {
			switch ( $options_id ) {
				case 'time':
					return '';
			}
		}

		/**
		 * Get_extra_option_desc.
		 *
		 * @version 5.6.1
		 * @since   4.0.0
		 * @param  string $option_id defines the option_id.
		 */
		public function get_extra_option_desc( $option_id ) {
			$values = wcj_get_option( $option_id, '' );
			if ( ! empty( $values ) ) {
				$parsed_time = $this->parse_time( $values );
				if ( $parsed_time ) {
					return '. ' . sprintf(
						/* translators: %1$s,%2$s: translators Added */
						__( 'According to current time, your time input will be parsed as: from %1$s to %2$s.', 'woocommerce-jetpack' ),
						'<code>' . gmdate( 'Y-m-d H:i:s', $parsed_time['time_from'] ) . '</code>',
						'<code>' . gmdate( 'Y-m-d H:i:s', $parsed_time['time_to'] ) . '</code>'
					);
				} else {
					/* translators: %s: translators Added */
					return '. <strong>' . sprintf( __( 'Error: %s', 'woocommerce-jetpack' ), __( 'Time input is not parsable!', 'woocommerce-jetpack' ) ) . '</strong>';
				}
			}
			return '';
		}

	}

endif;

return new WCJ_Shipping_By_Time();

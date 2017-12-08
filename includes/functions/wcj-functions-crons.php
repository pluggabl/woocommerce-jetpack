<?php
/**
 * Booster for WooCommerce - Functions - Crons
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 * @todo    use all functions where applicable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_crons_get_all_intervals' ) ) {
	/**
	 * wcj_crons_get_all_intervals.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function wcj_crons_get_all_intervals( $action = '', $skip_intervals = array() ) {
		if ( '' === $action ) {
			$action = __( 'Update', 'woocommerce-jetpack' );
		}
		$return = array(
			'minutely'   => sprintf( __( '%s every minute', 'woocommerce-jetpack' ), $action ),
			'minute_5'   => sprintf( __( '%s every 5 minutes', 'woocommerce-jetpack' ), $action ),
			'minute_15'  => sprintf( __( '%s every 15 minutes', 'woocommerce-jetpack' ), $action ),
			'minute_30'  => sprintf( __( '%s every 30 minutes', 'woocommerce-jetpack' ), $action ),
			'hourly'     => sprintf( __( '%s hourly', 'woocommerce-jetpack' ), $action ),
			'twicedaily' => sprintf( __( '%s twice daily', 'woocommerce-jetpack' ), $action ),
			'daily'      => sprintf( __( '%s daily', 'woocommerce-jetpack' ), $action ),
			'weekly'     => sprintf( __( '%s weekly', 'woocommerce-jetpack' ), $action ),
		);
		if ( ! empty( $skip_intervals ) ) {
			foreach ( $skip_intervals as $skip_interval ) {
				unset( $return[ $skip_interval ] );
			}
		}
		return $return;
	}
}

if ( ! function_exists( 'wcj_crons_schedule_the_events' ) ) {
	/**
	 * wcj_crons_schedule_the_events.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function wcj_crons_schedule_the_events( $event_hook, $selected_interval ) {
		$intervals = array_keys( wcj_crons_get_all_intervals() );
		foreach ( $intervals as $interval ) {
			$event_timestamp = wp_next_scheduled( $event_hook, array( $interval ) );
			if ( $selected_interval === $interval ) {
				update_option( $event_hook . '_time', $event_timestamp );
			}
			if ( ! $event_timestamp && $selected_interval === $interval ) {
				wp_schedule_event( time(), $selected_interval, $event_hook, array( $selected_interval ) );
			} elseif ( $event_timestamp && $selected_interval !== $interval ) {
				wp_unschedule_event( $event_timestamp, $event_hook, array( $interval ) );
			}
		}
	}
}

if ( ! function_exists( 'wcj_crons_get_next_event_time_message' ) ) {
	/**
	 * wcj_crons_get_next_event_time_message.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @todo    (maybe) move to "date-time" functions
	 */
	function wcj_crons_get_next_event_time_message( $time_option_name ) {
		if ( '' != get_option( $time_option_name, '' ) ) {
			$scheduled_time_diff = get_option( $time_option_name, '' ) - time();
			if ( $scheduled_time_diff > 60 ) {
				return '<br><em>' . sprintf( __( '%s till next run.', 'woocommerce-jetpack' ), human_time_diff( 0, $scheduled_time_diff ) ) . '</em>';
			} elseif ( $scheduled_time_diff > 0 ) {
				return '<br><em>' . sprintf( __( '%s seconds till next run.', 'woocommerce-jetpack' ), $scheduled_time_diff ) . '</em>';
			}
		}
		return '';
	}
}

if ( ! function_exists( 'wcj_crons_add_custom_intervals' ) ) {
	/**
	 * wcj_crons_add_custom_intervals.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function wcj_crons_add_custom_intervals( $schedules ) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Once weekly', 'woocommerce-jetpack' )
		);
		$schedules['minute_30'] = array(
			'interval' => 1800,
			'display'  => __( 'Once every 30 minutes', 'woocommerce-jetpack' )
		);
		$schedules['minute_15'] = array(
			'interval' => 900,
			'display'  => __( 'Once every 15 minutes', 'woocommerce-jetpack' )
		);
		$schedules['minute_5'] = array(
			'interval' => 300,
			'display'  => __( 'Once every 5 minutes', 'woocommerce-jetpack' )
		);
		$schedules['minutely'] = array(
			'interval' => 60,
			'display'  => __( 'Once a minute', 'woocommerce-jetpack' )
		);
		return $schedules;
	}
}

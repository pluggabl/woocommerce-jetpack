<?php
/**
 * Booster for WooCommerce - Functions - Crons
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @todo    use all functions where applicable
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_crons_get_all_intervals' ) ) {
	/**
	 * Wcj_crons_get_all_intervals.
	 *
	 * @version 3.2.4
	 * @since   3.2.4]
	 * @param   int  $action defines the action.
	 * @param   null $skip_intervals defines the skip_intervals.
	 */
	function wcj_crons_get_all_intervals( $action = '', $skip_intervals = array() ) {
		if ( '' === $action ) {
			$action = __( 'Update', 'woocommerce-jetpack' );
		}
		$return = array(
			/* translators: %s: search term */
			'minutely'   => sprintf( __( '%s every minute', 'woocommerce-jetpack' ), $action ),
			/* translators: %s: search term */
			'minute_5'   => sprintf( __( '%s every 5 minutes', 'woocommerce-jetpack' ), $action ),
			/* translators: %s: search term */
			'minute_15'  => sprintf( __( '%s every 15 minutes', 'woocommerce-jetpack' ), $action ),
			/* translators: %s: search term */
			'minute_30'  => sprintf( __( '%s every 30 minutes', 'woocommerce-jetpack' ), $action ),
			/* translators: %s: search term */
			'hourly'     => sprintf( __( '%s hourly', 'woocommerce-jetpack' ), $action ),
			/* translators: %s: search term */
			'twicedaily' => sprintf( __( '%s twice daily', 'woocommerce-jetpack' ), $action ),
			/* translators: %s: search term */
			'daily'      => sprintf( __( '%s daily', 'woocommerce-jetpack' ), $action ),
			/* translators: %s: search term */
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
	 * Wcj_crons_schedule_the_events.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @param   string $event_hook defines the event_hook.
	 * @param   string $selected_interval defines the selected_interval.
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
	 * Wcj_crons_get_next_event_time_message.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @todo    (maybe) move to "date-time" functions
	 * @param   string $time_option_name defines the time_option_name.
	 */
	function wcj_crons_get_next_event_time_message( $time_option_name ) {
		if ( '' !== wcj_get_option( $time_option_name, '' ) ) {
			$scheduled_time_diff = wcj_get_option( $time_option_name, '' ) - time();
			if ( $scheduled_time_diff > 60 ) {
				/* translators: %s: search term */
				return '<br><em>' . sprintf( __( '%s till next run.', 'woocommerce-jetpack' ), human_time_diff( 0, $scheduled_time_diff ) ) . '</em>';
			} elseif ( $scheduled_time_diff > 0 ) {
				/* translators: %s: search term */
				return '<br><em>' . sprintf( __( '%s seconds till next run.', 'woocommerce-jetpack' ), $scheduled_time_diff ) . '</em>';
			}
		}
		return '';
	}
}

if ( ! function_exists( 'wcj_crons_add_custom_intervals' ) ) {
	/**
	 * Wcj_crons_add_custom_intervals.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @param   array $schedules defines the schedules.
	 */
	function wcj_crons_add_custom_intervals( $schedules ) {
		$schedules['weekly']    = array(
			'interval' => 604800,
			'display'  => __( 'Once weekly', 'woocommerce-jetpack' ),
		);
		$schedules['minute_30'] = array(
			'interval' => 1800,
			'display'  => __( 'Once every 30 minutes', 'woocommerce-jetpack' ),
		);
		$schedules['minute_15'] = array(
			'interval' => 900,
			'display'  => __( 'Once every 15 minutes', 'woocommerce-jetpack' ),
		);
		$schedules['minute_5']  = array(
			'interval' => 300,
			'display'  => __( 'Once every 5 minutes', 'woocommerce-jetpack' ),
		);
		$schedules['minutely']  = array(
			'interval' => 60,
			'display'  => __( 'Once a minute', 'woocommerce-jetpack' ),
		);
		return $schedules;
	}
}

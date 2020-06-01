<?php
/**
 * Booster for WooCommerce - Reports - Product Sales - Gateways
 *
 * @version 3.6.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Reports_Product_Sales_Gateways' ) ) :

class WCJ_Reports_Product_Sales_Gateways {

	/**
	 * Constructor.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function __construct( $args = null ) {
		return true;
	}

	/**
	 * get_report.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_report() {
		$this->get_report_args();
		$this->get_report_data();
		return $this->output_report_data();
	}

	/*
	 * get_report_args.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_report_args() {
		$current_time = (int) current_time( 'timestamp' );
		$this->start_date    = isset( $_GET['start_date'] )    ? $_GET['start_date']    : date( 'Y-m-d', strtotime( '-7 days', $current_time ) );
		$this->end_date      = isset( $_GET['end_date'] )      ? $_GET['end_date']      : date( 'Y-m-d', $current_time );
	}

	/*
	 * get_report_data.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    by "order status"
	 */
	function get_report_data() {
		$this->gateways = array();
		$offset         = 0;
		$block_size     = 1024;
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'after'     => $this->start_date,
						'before'    => $this->end_date,
						'inclusive' => true,
					),
				),
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) {
				break;
			}
			foreach ( $loop_orders->posts as $order_id ) {
				if ( '' == ( $payment_gateway = get_post_meta( $order_id, '_payment_method_title', true ) ) ) {
					$payment_gateway = __( 'N/A', 'woocommerce-jetpack' );
				}
				if ( ! isset( $this->gateways[ $payment_gateway ] ) ) {
					$this->gateways[ $payment_gateway ] = 0;
				}
				$this->gateways[ $payment_gateway ]++;
			}
			$offset += $block_size;
		}
	}

	/*
	 * output_report_data.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function output_report_data() {
		return $this->output_report_header() . $this->output_report_results();
	}

	/*
	 * output_report_header.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function output_report_header() {
		// Settings link and dates menu
		$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=reports' ) . '">' .
			'<< ' . __( 'Reports Settings', 'woocommerce-jetpack' ) . '</a>';
		$menu = '';
		$menu .= '<ul class="subsubsub">';
		foreach ( array_merge( wcj_get_reports_standard_ranges(), wcj_get_reports_custom_ranges() ) as $custom_range ) {
			$menu .= '<li><a ' .
				'href="' . add_query_arg( array( 'start_date' => $custom_range['start_date'], 'end_date' => $custom_range['end_date'] ) ) . '" ' .
				'class="' . ( ( $this->start_date == $custom_range['start_date'] && $this->end_date == $custom_range['end_date'] ) ? 'current' : '' ) . '"' .
			'>' . $custom_range['title'] . '</a> | </li>';
		}
		$menu .= '</ul>';
		$menu .= '<br class="clear">';
		// Date filter form
		$filter_form = '';
		$filter_form .= '<form method="get" action="">';
		$filter_form .= '<input type="hidden" name="page" value="'       . $_GET['page']     . '" />';
		$filter_form .= '<input type="hidden" name="tab" value="'        . $_GET['tab']      . '" />';
		$filter_form .= '<input type="hidden" name="report" value="'     . $_GET['report']   . '" />';
		$filter_form .= '<label style="font-style:italic;" for="start_date">' . __( 'From:', 'woocommerce-jetpack' ) . '</label>' . ' ' .
			'<input type="text" display="date" dateformat="' . wcj_date_format_php_to_js( 'Y-m-d' ) . '" name="start_date" title="" value="' . $this->start_date . '" />';
		$filter_form .= ' ';
		$filter_form .= '<label style="font-style:italic;" for="end_date">' . __( 'To:', 'woocommerce-jetpack' ) . '</label>' . ' ' .
			'<input type="text" display="date" dateformat="' . wcj_date_format_php_to_js( 'Y-m-d' ) . '" name="end_date" title="" value="' . $this->end_date . '" />';
		$filter_form .= ' ';
		$filter_form .= '<input type="submit" value="' . __( 'Filter', 'woocommerce-jetpack' ) . '" />';
		$filter_form .= '</form>';
		// Final result
		return '<p>' . $settings_link . '</p>' . '<p>' . $menu . '</p>' . '<p>' . $filter_form . '</p>';
	}

	/*
	 * output_report_results.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function output_report_results() {
		if ( ! empty( $this->gateways ) ) {
			$table_data = array();
			$table_data[] = array(
				'<strong><em>' . __( 'Gateway', 'woocommerce-jetpack' ) . '</em></strong>', '<strong><em>' . __( 'Orders', 'woocommerce-jetpack' ) . '</em></strong>' );
			foreach ( $this->gateways as $gateway => $total ) {
				$table_data[] = array( '<em>' . $gateway . '</em>', $total );
			}
			$result = wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'none' ) ) .
				'<p><em>' . sprintf( __( 'Total orders: %d', 'woocommerce-jetpack' ), array_sum( $this->gateways ) ) . '</em></p>';
		} else {
			$result = '<p><em>' . __( 'No sales data for current period.', 'woocommerce-jetpack' ) . '</em></p>';
		}
		return $result;
	}
}

endif;

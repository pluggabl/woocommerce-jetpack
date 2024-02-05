<?php
/**
 * Booster for WooCommerce - Reports - Product Sales - Gateways
 *
 * @version 7.1.6
 * @since   3.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Reports_Sales_Gateways' ) ) :
		/**
		 * WCJ_Reports_Sales_Gateways.
		 *
		 * @version 7.1.6
		 * @since   3.6.0
		 */
	class WCJ_Reports_Sales_Gateways {

		/**
		 * The module start_date
		 *
		 * @var varchar $start_date Module start_date.
		 */
		public $start_date;

		/**
		 * The module end_date
		 *
		 * @var varchar $end_date Module end_date.
		 */
		public $end_date;

		/**
		 * The module gateways
		 *
		 * @var varchar $gateways Module gateways.
		 */
		public $gateways;

		/**
		 * Constructor.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @param null $args Get null value.
		 */
		public function __construct( $args = null ) {
			return true;
		}

		/**
		 * Get_report.
		 *
		 * @version 7.1.4
		 * @since   3.6.0
		 */
		public function get_report() {
			$this->get_report_args();
			if ( true === wcj_is_hpos_enabled() ) {
				$this->get_report_data_hpos();
			} else {
				$this->get_report_data();
			}
			return $this->output_report_data();
		}

		/**
		 * Get_report_args.
		 *
		 * @version 6.0.0
		 * @since   3.6.0
		 */
		public function get_report_args() {
			if ( isset( $_REQUEST['filter_submit'] ) ) {
				$wpnonce = isset( $_REQUEST['wcj-reports-sales-gateways-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-reports-sales-gateways-nonce'] ), 'wcj-reports-sales-gateways' ) : false;
			} else {
				$wpnonce = true;
			}
			$current_time     = wcj_get_timestamp_date_from_gmt();
			$this->start_date = $wpnonce && isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : gmdate( 'Y-m-d', strtotime( '-7 days', $current_time ) );
			$this->end_date   = $wpnonce && isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : gmdate( 'Y-m-d', $current_time );
		}

		/**
		 * Get_report_data.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @todo    by "order status"
		 */
		public function get_report_data() {
			$this->gateways = array();
			$offset         = 0;
			$block_size     = 1024;
			while ( true ) {
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
					$payment_gateway = get_post_meta( $order_id, '_payment_method_title', true );
					if ( '' === ( $payment_gateway ) ) {
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

		/**
		 * Get_report_data_hpos.
		 *
		 * @version 7.1.4
		 * @since  1.0.0
		 * @todo    by "order status"
		 */
		public function get_report_data_hpos() {
			$this->gateways = array();
			$offset         = 0;
			$block_size     = 1024;
			while ( true ) {
				$args_orders = array(
					'type'           => 'shop_order',
					'status'         => 'any',
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
				$orders      = wc_get_orders( $args_orders );
				if ( ! $orders ) {
					break;
				}
				foreach ( $orders as $order ) {
					$order_id        = $order->get_id();
					$order           = wc_get_order( $order_id );
					$payment_gateway = $order->get_meta( '_payment_method_title' );
					if ( '' === ( $payment_gateway ) ) {
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

		/**
		 * Output_report_data.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		public function output_report_data() {
			return $this->output_report_header() . $this->output_report_results();
		}

		/**
		 * Output_report_header.
		 *
		 * @version 6.0.0
		 * @since   3.6.0
		 */
		public function output_report_header() {
			// Settings link and dates menu.
			$settings_link = '<a href="' . admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=reports' ) . '">' .
			'<< ' . __( 'Reports Settings', 'woocommerce-jetpack' ) . '</a>';
			$menu          = '';
			$menu         .= '<div id="poststuff" class="wcj-reports-wide woocommerce-reports-wide"><div class="postbox"><div class="stats_range"><ul class="">';
			foreach ( array_merge( wcj_get_reports_standard_ranges(), wcj_get_reports_custom_ranges() ) as $custom_range ) {
				$is_active = $this->start_date === $custom_range['start_date'] && $this->end_date === $custom_range['end_date'] ? 'active' : '';

				$menu .= '<li class="' . $is_active . '"><a ' .
				'href="' . esc_url(
					add_query_arg(
						array(
							'start_date' => $custom_range['start_date'],
							'end_date'   => $custom_range['end_date'],
						)
					)
				) . '" ' .
				'class="' . ( ( $this->start_date === $custom_range['start_date'] && $this->end_date === $custom_range['end_date'] ) ? 'current' : '' ) . '"' .
				'>' . $custom_range['title'] . '</a></li>';
			}
			// phpcs:disable WordPress.Security.NonceVerification
			$page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			$tab    = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
			$report = isset( $_GET['report'] ) ? sanitize_text_field( wp_unslash( $_GET['report'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification

			// Date filter form.
			$filter_form  = '<li class="custom">Custom:';
			$filter_form .= '<form method="get" action="">';
			$filter_form .= '<input type="hidden" name="page" value="' . esc_attr( $page ) . '" />';
			$filter_form .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />';
			$filter_form .= '<input type="hidden" name="report" value="' . esc_attr( $report ) . '" />';
			$filter_form .= '<input class="range_datepicker" size="9" type="text" display="date" dateformat="' . wcj_date_format_php_to_js( 'Y-m-d' ) . '" name="start_date" title="" value="' . $this->start_date . '" />';
			$filter_form .= '<input class="range_datepicker" size="9" type="text" display="date" dateformat="' . wcj_date_format_php_to_js( 'Y-m-d' ) . '" name="end_date" title="" value="' . $this->end_date . '" />';
			$filter_form .= '<input type="hidden" name="wcj-reports-sales-gateways-nonce" value="' . wp_create_nonce( 'wcj-reports-sales-gateways' ) . '" />';
			$filter_form .= '<input type="submit" class="button" value="' . __( 'Filter', 'woocommerce-jetpack' ) . '" />';
			$filter_form .= '</form>';
			$filter_form .= '</li></ul><br class="clear">';
			// Final result.
			return '<p>' . $settings_link . '</p> ' . $menu . $filter_form;
		}

		/**
		 * Output_report_results.
		 *
		 * @version 6.0.0
		 * @since   3.6.0
		 */
		public function output_report_results() {
			if ( ! empty( $this->gateways ) ) {
				$table_data   = array();
				$table_data[] = array(
					'<strong><em>' . __( 'Gateway', 'woocommerce-jetpack' ) . '</em></strong>',
					'<strong><em>' . __( 'Orders', 'woocommerce-jetpack' ) . '</em></strong>',
				);
				foreach ( $this->gateways as $gateway => $total ) {
					$table_data[] = array( '<em>' . $gateway . '</em>', $total );
				}
				$result = wcj_get_table_html(
					$table_data,
					array(
						'table_class'        => 'widefat striped',
						'table_heading_type' => 'horizontal',
					)
				) .
				/* translators: %d: translation added */
				'<p><em>' . sprintf( __( 'Total orders: %d', 'woocommerce-jetpack' ), array_sum( $this->gateways ) ) . '</em></p>';
			} else {
				$result = '<p><em>' . __( 'No sales data for current period.', 'woocommerce-jetpack' ) . '</em></p>';
			}
			return '<div class="inside">' . $result . '</div></div></div></div>';
		}
	}

endif;

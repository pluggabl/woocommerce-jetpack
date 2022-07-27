<?php //phpcs:ignore
/**
 * Booster for WooCommerce - Reports - Product Sales - Gateways
 *
 * @version 5.6.2
 * @since   3.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Reports_Product_Sales_Gateways' ) ) :
		/**
		 * WCJ_Reports_Product_Sales_Gateways.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
	class WCJ_Reports_Product_Sales_Gateways {

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
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		public function get_report() {
			$this->get_report_args();
			$this->get_report_data();
			return $this->output_report_data();
		}

		/**
		 * Get_report_args.
		 *
		 * @version 5.6.2
		 * @since   3.6.0
		 */
		public function get_report_args() {
			$wpnonce = true;
			if ( function_exists( 'wp_verify_nonce' ) ) {
				$wpnonce = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '' ), 'woocommerce-settings' ) : true;
			}
			$current_time     = (int) gmdate( 'U' );
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
		 * @version 5.6.2
		 * @since   3.6.0
		 */
		public function output_report_header() {
			// Settings link and dates menu.
			$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=reports' ) . '">' .
			'<< ' . __( 'Reports Settings', 'woocommerce-jetpack' ) . '</a>';
			$menu          = '';
			$menu         .= '<ul class="subsubsub">';
			foreach ( array_merge( wcj_get_reports_standard_ranges(), wcj_get_reports_custom_ranges() ) as $custom_range ) {
				$menu .= '<li><a ' .
				'href="' . esc_url(
					add_query_arg(
						array(
							'start_date' => $custom_range['start_date'],
							'end_date'   => $custom_range['end_date'],
						)
					)
				) . '" ' .
				'class="' . ( ( $this->start_date === $custom_range['start_date'] && $this->end_date === $custom_range['end_date'] ) ? 'current' : '' ) . '"' .
				'>' . $custom_range['title'] . '</a> | </li>';
			}
			$menu   .= '</ul>';
			$menu   .= '<br class="clear">';
			$wpnonce = true;
			if ( function_exists( 'wp_verify_nonce' ) ) {
				$wpnonce = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '' ), 'woocommerce-settings' ) : true;
			}
			$page   = $wpnonce && isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			$tab    = $wpnonce && isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
			$report = $wpnonce && isset( $_GET['report'] ) ? sanitize_text_field( wp_unslash( $_GET['report'] ) ) : '';

			// Date filter form.
			$filter_form  = '';
			$filter_form .= '<form method="get" action="">';
			$filter_form .= '<input type="hidden" name="page" value="' . $page . '" />';
			$filter_form .= '<input type="hidden" name="tab" value="' . $tab . '" />';
			$filter_form .= '<input type="hidden" name="report" value="' . $report . '" />';
			$filter_form .= '<label style="font-style:italic;" for="start_date">' . __( 'From:', 'woocommerce-jetpack' ) . '</label> ' .
			'<input type="text" display="date" dateformat="' . wcj_date_format_php_to_js( 'Y-m-d' ) . '" name="start_date" title="" value="' . $this->start_date . '" />';
			$filter_form .= ' ';
			$filter_form .= '<label style="font-style:italic;" for="end_date">' . __( 'To:', 'woocommerce-jetpack' ) . '</label>' .
			'<input type="text" display="date" dateformat="' . wcj_date_format_php_to_js( 'Y-m-d' ) . '" name="end_date" title="" value="' . $this->end_date . '" />';
			$filter_form .= ' ';
			$filter_form .= '<input type="submit" value="' . __( 'Filter', 'woocommerce-jetpack' ) . '" />';
			$filter_form .= '</form>';
			// Final result.
			return '<p>' . $settings_link . '</p> <p>' . $menu . '</p> <p>' . $filter_form . '</p>';
		}

		/**
		 * Output_report_results.
		 *
		 * @version 3.6.0
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
						'table_heading_type' => 'none',
					)
				) .
				/* translators: %d: translation added */
				'<p><em>' . sprintf( __( 'Total orders: %d', 'woocommerce-jetpack' ), array_sum( $this->gateways ) ) . '</em></p>';
			} else {
				$result = '<p><em>' . __( 'No sales data for current period.', 'woocommerce-jetpack' ) . '</em></p>';
			}
			return $result;
		}
	}

endif;

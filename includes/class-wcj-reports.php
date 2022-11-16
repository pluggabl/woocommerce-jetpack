<?php
/**
 * Booster for WooCommerce - Module - Reports
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Reports' ) ) :
	/**
	 * WCJ_Reports.
	 */
	class WCJ_Reports extends WCJ_Module {

		/**
		 * Report_id.
		 *
		 * @var report_id.
		 */
		public $report_id;

		/**
		 * Range_days.
		 *
		 * @var range_days.
		 */
		public $range_days;

		/**
		 * Group_countries.
		 *
		 * @var group_countries.
		 */
		public $group_countries;

		/**
		 * Constructor.
		 *
		 * @version 5.6.8
		 * @todo    "orders report by meta" abstract class (see `WCJ_Reports_Product_Sales_Gateways`): by referer (`_wcj_track_users_http_referer`); by shipping (stored as item); by country (`_billing_country` or `_shipping_country`) etc.
		 */
		public function __construct() {

			$this->id         = 'reports';
			$this->short_desc = __( 'Reports', 'woocommerce-jetpack' );
			$this->desc       = __( 'Stock, sales, customers etc. reports.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-reports';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( is_admin() ) {

					add_filter( 'woocommerce_admin_reports', array( $this, 'add_customers_by_country_report' ) );
					add_filter( 'woocommerce_admin_reports', array( $this, 'add_stock_reports' ) );
					add_filter( 'woocommerce_admin_reports', array( $this, 'add_sales_reports' ) );
					add_action( 'init', array( $this, 'catch_arguments' ) );

					include_once 'reports/class-wcj-reports-customers.php';
					include_once 'reports/class-wcj-reports-stock.php';
					include_once 'reports/class-wcj-reports-product-sales-daily.php';
					include_once 'reports/class-wcj-reports-sales-gateways.php';
					include_once 'reports/class-wcj-reports-sales.php';
					include_once 'reports/class-wcj-reports-monthly-sales.php';

					add_action( 'admin_bar_menu', array( $this, 'add_custom_order_reports_ranges_to_admin_bar' ), PHP_INT_MAX );
					add_action( 'admin_bar_menu', array( $this, 'add_custom_order_reports_ranges_by_month_to_admin_bar' ), PHP_INT_MAX );
				}
			}
		}

		/**
		 * Add_custom_order_reports_ranges_by_month_to_admin_bar.
		 *
		 * @version 5.6.8
		 * @since   2.2.4
		 * @param string | array $wp_admin_bar defines the wp_admin_bar.
		 */
		public function add_custom_order_reports_ranges_by_month_to_admin_bar( $wp_admin_bar ) {
			// phpcs:disable WordPress.Security.NonceVerification
			$is_reports        = ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] );
			$is_orders_reports = ( isset( $_GET['tab'] ) && 'orders' === $_GET['tab'] || ! isset( $_GET['tab'] ) );
			// phpcs:enable WordPress.Security.NonceVerification
			if ( $is_reports && $is_orders_reports ) {

				$parent = 'reports_orders_more_ranges_months';
				$args   = array(
					'parent' => false,
					'id'     => $parent,
					'title'  => __( 'Booster: More Ranges - Months', 'woocommerce-jetpack' ),
					'href'   => false,
					'meta'   => array( 'title' => __( 'Select Range', 'woocommerce-jetpack' ) ),
				);
				$wp_admin_bar->add_node( $args );

				$custom_range_nonce = wp_create_nonce( 'custom_range' );
				$current_time       = wcj_get_timestamp_date_from_gmt();
				for ( $i = 1; $i <= 12; $i++ ) {
					$month_start_date = strtotime( gmdate( 'Y-m-01', $current_time ) . " -$i months" );
					$month_num        = gmdate( 'm', $month_start_date );
					$month_name       = gmdate( 'Y F', $month_start_date );
					$start_date       = gmdate( 'Y-m-01', $month_start_date );
					$end_date         = gmdate( 'Y-m-t', $month_start_date );
					$node             = array(
						'parent' => $parent,
						'id'     => $parent . '_' . $month_num,
						'title'  => $month_name,
						'href'   => esc_url(
							add_query_arg(
								array(
									'range'            => 'custom',
									'start_date'       => $start_date,
									'end_date'         => $end_date,
									'wc_reports_nonce' => $custom_range_nonce,
								)
							)
						),
						'meta'   => array( 'title' => $month_name ),
					);
					$wp_admin_bar->add_node( $node );
				}
			}
		}

		/**
		 * Add_custom_order_reports_ranges_to_admin_bar.
		 *
		 * @version 5.6.8
		 * @param string | array $wp_admin_bar defines the wp_admin_bar.
		 */
		public function add_custom_order_reports_ranges_to_admin_bar( $wp_admin_bar ) {
			// phpcs:disable WordPress.Security.NonceVerification
			$is_reports        = ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] );
			$is_orders_reports = ( isset( $_GET['tab'] ) && 'orders' === $_GET['tab'] || ! isset( $_GET['tab'] ) );
			// phpcs:enable WordPress.Security.NonceVerification
			if ( $is_reports && $is_orders_reports ) {

				$parent = 'reports_orders_more_ranges';
				$args   = array(
					'parent' => false,
					'id'     => $parent,
					'title'  => __( 'Booster: More Ranges', 'woocommerce-jetpack' ),
					'href'   => false,
					'meta'   => array( 'title' => __( 'Select Range', 'woocommerce-jetpack' ) ),
				);
				$wp_admin_bar->add_node( $args );

				$custom_range_nonce = wp_create_nonce( 'custom_range' );
				foreach ( wcj_get_reports_custom_ranges() as $custom_range_id => $custom_range ) {
					$node = array(
						'parent' => $parent,
						'id'     => $parent . '_' . $custom_range_id,
						'title'  => $custom_range['title'],
						'href'   => esc_url(
							add_query_arg(
								array(
									'range'            => 'custom',
									'start_date'       => $custom_range['start_date'],
									'end_date'         => $custom_range['end_date'],
									'wc_reports_nonce' => $custom_range_nonce,
								)
							)
						),
						'meta'   => array( 'title' => $custom_range['title'] ),
					);
					$wp_admin_bar->add_node( $node );
				}
			}
		}

		/**
		 * Catch_arguments.
		 */
		public function catch_arguments() {
			// phpcs:disable WordPress.Security.NonceVerification
			$this->report_id       = ( isset( $_GET['report'] ) ) ? sanitize_text_field( wp_unslash( $_GET['report'] ) ) : 'on_stock';
			$this->range_days      = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : 30;
			$this->group_countries = ( 'customers_by_country_sets' === $this->report_id ) ? 'yes' : 'no';
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 * Get_report_sales.
		 */
		public function get_report_sales() {
			$report = new WCJ_Reports_Sales();
			echo wp_kses_post( $report->get_report() );
		}

		/**
		 * Get_report_products_sales_daily.
		 *
		 * @version 5.6.8
		 * @since   2.9.0
		 */
		public function get_report_products_sales_daily() {
			$report = new WCJ_Reports_Product_Sales_Daily();
			echo wp_kses_post( $report->get_report() );
		}

		/**
		 * Get_report_monthly_sales.
		 *
		 * @version 5.6.8
		 * @since   2.4.7
		 */
		public function get_report_monthly_sales() {
			$report = new WCJ_Reports_Monthly_Sales();
			echo wp_kses_post( $report->get_report() );
		}

		/**
		 * Get_report_orders_gateways.
		 *
		 * @version 5.6.8
		 * @since   3.6.0
		 */
		public function get_report_orders_gateways() {
			$report = new WCJ_Reports_Sales_Gateways();
			echo wp_kses_post( $report->get_report() );
		}

		/**
		 * Get_report_stock.
		 */
		public function get_report_stock() {
			$report = new WCJ_Reports_Stock(
				array(
					'report_id'  => $this->report_id,
					'range_days' => $this->range_days,
				)
			);
			echo wp_kses_post( $report->get_report_html() );
		}

		/**
		 * Get_report_customers.
		 */
		public function get_report_customers() {
			$report = new WCJ_Reports_Customers( array( 'group_countries' => $this->group_countries ) );
			echo wp_kses_post( $report->get_report() );
		}

		/**
		 * Add reports to WooCommerce > Reports > Sales.
		 *
		 * @version 3.6.0
		 * @since   2.3.0
		 * @param array $reports defines the reports.
		 */
		public function add_sales_reports( $reports ) {

			$reports['orders']['reports']['booster_products_sales_daily'] = array(
				'title'       => __( 'Booster: Product Sales (Daily)', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => false,
				'callback'    => array( $this, 'get_report_products_sales_daily' ),
			);

			$reports['orders']['reports']['booster_products_sales'] = array(
				'title'       => __( 'Booster: Product Sales (Monthly)', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => false,
				'callback'    => array( $this, 'get_report_sales' ),
			);

			$reports['orders']['reports']['booster_monthly_sales'] = array(
				'title'       => __( 'Booster: Monthly Sales (with Currency Conversion)', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => false,
				'callback'    => array( $this, 'get_report_monthly_sales' ),
			);

			$reports['orders']['reports']['booster_gateways'] = array(
				'title'       => __( 'Booster: Payment Gateways', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => false,
				'callback'    => array( $this, 'get_report_orders_gateways' ),
			);

			return $reports;
		}

		/**
		 * Add reports to WooCommerce > Reports > Stock.
		 *
		 * @param array $reports defines the reports.
		 */
		public function add_stock_reports( $reports ) {

			$reports['stock']['reports']['on_stock'] = array(
				'title'       => __( 'Booster: All in stock', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => true,
				'callback'    => array( $this, 'get_report_stock' ),
			);

			$reports['stock']['reports']['understocked'] = array(
				'title'       => __( 'Booster: Understocked', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => true,
				'callback'    => array( $this, 'get_report_stock' ),
			);

			$reports['stock']['reports']['overstocked'] = array(
				'title'       => __( 'Booster: Overstocked', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => true,
				'callback'    => array( $this, 'get_report_stock' ),
			);

			return $reports;
		}

		/**
		 * Add reports to WooCommerce > Reports > Customers.
		 *
		 * @param array $reports defines the reports.
		 */
		public function add_customers_by_country_report( $reports ) {

			$reports['customers']['reports']['customers_by_country'] = array(
				'title'       => __( 'Booster: Customers by Country', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => true,
				'callback'    => array( $this, 'get_report_customers' ),
			);

			$reports['customers']['reports']['customers_by_country_sets'] = array(
				'title'       => __( 'Booster: Customers by Country Sets', 'woocommerce-jetpack' ),
				'description' => '',
				'hide_title'  => true,
				'callback'    => array( $this, 'get_report_customers' ),
			);

			return $reports;
		}

	}

endif;

return new WCJ_Reports();

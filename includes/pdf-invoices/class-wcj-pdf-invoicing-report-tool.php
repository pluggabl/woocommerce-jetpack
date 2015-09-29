<?php
/**
 * WooCommerce Jetpack PDF Invoices Report Tool
 *
 * The WooCommerce Jetpack PDF Invoices Report Tool class.
 *
 * @version 2.2.1
 * @since   2.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Report_Tool' ) ) :

class WCJ_PDF_Invoicing_Report_Tool {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wcj_tools_tabs',            array( $this, 'add_invoices_report_tool_tab' ), 100 );
		add_action( 'wcj_tools_invoices_report', array( $this, 'create_invoices_report_tool' ), 100 );
		add_action( 'wcj_tools_dashboard',       array( $this, 'add_invoices_report_tool_info_to_tools_dashboard' ), 100 );
	}

	/**
	 * add_invoices_report_tool_info_to_tools_dashboard.
	 */
	public function add_invoices_report_tool_info_to_tools_dashboard() {
		echo '<tr>';
		if ( 'yes' === get_option( 'wcj_pdf_invoicing_enabled') )
			$is_enabled = '<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' ) . '</span>';
		else
			$is_enabled = '<span style="color:gray;font-style:italic;">' . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
		echo '<td>' . __( 'Invoices Report', 'woocommerce-jetpack' ) . '</td>';
		echo '<td>' . $is_enabled . '</td>';
		echo '<td>' . __( 'Invoices Monthly Reports.', 'woocommerce-jetpack' ) . '</td>';
		echo '</tr>';
	}

	/**
	 * add_invoices_report_tool_tab.
	 */
	public function add_invoices_report_tool_tab( $tabs ) {
		$tabs[] = array(
			'id'    => 'invoices_report',
			'title' => __( 'Invoices Report', 'woocommerce-jetpack' ),
		);
		return $tabs;
	}

	/**
	 * Add Invoices Report tool to WooCommerce menu (the content).
	 */
	public function create_invoices_report_tool() {

		$result_message = '';

		$the_year = ( ! empty( $_POST['report_year'] ) ) ? $_POST['report_year'] : '';
		$the_month = ( ! empty( $_POST['report_month'] ) ) ? $_POST['report_month'] : '';

		if ( isset( $_POST['get_invoices_report'] ) ) {
			if ( ! empty( $the_year ) && ! empty( $the_month ) ) {
				$result_message = $this->get_invoices_report( $the_year, $the_month );
			} else {
				$result_message = '<div class="error"><p><strong>' . __( 'Please fill year and month values.', 'woocommerce-jetpack' ) . '</strong></p></div>';
			}
		}
		?><div>
			<h2><?php echo __( 'WooCommerce Jetpack - Invoices Report', 'woocommerce-jetpack' ); ?></h2>
			<p><?php echo __( 'Invoices Monthly Reports.', 'woocommerce-jetpack' ); ?></p>
			<?php echo $result_message; ?>
			<p><form method="post" action="">
				<?php

				// Year
				$data[] = array(
					__( 'Year', 'woocommerce-jetpack' ),
					'<input class="input-text" type="text" name="report_year" value="' . $the_year . '">',
					//'<em>' . __( 'Year.', 'woocommerce-jetpack' ) . '</em>',
				);

				// Month
				$data[] = array(
					__( 'Month', 'woocommerce-jetpack' ),
					'<input class="input-text" type="text" name="report_month" value="' . $the_month . '">',
					//'<em>' . __( 'Month.', 'woocommerce-jetpack' ) . '</em>',
				);

				// Print all
				echo wcj_get_table_html( $data, array( 'table_heading_type' => 'vertical', ) );

				?>
				<input type="submit" name="get_invoices_report" value="<?php _e( 'Get Report', 'woocommerce-jetpack' ); ?>">
			</form></p>
		</div><?php
	}

	/**
	 * Invoices Report function.
	 */
	function get_invoices_report( $year, $month ) {

		$output = '';

		$data = array();
		$data[] = array(
			__( 'Invoice Nr.', 'woocommerce-jetpack' ),
			__( 'Invoice Date', 'woocommerce-jetpack' ),
			__( 'Order ID', 'woocommerce-jetpack' ),
			__( 'Customer Country', 'woocommerce-jetpack' ),
			__( 'Tax %', 'woocommerce-jetpack' ),
			__( 'Order Total Tax Excl.', 'woocommerce-jetpack' ),
			__( 'Order Taxes', 'woocommerce-jetpack' ),
			__( 'Order Total', 'woocommerce-jetpack' ),
			__( 'Order Currency', 'woocommerce-jetpack' ),
		);

		$total_sum = 0;
		$total_sum_excl_tax = 0;
		$total_tax = 0;

		$args = array(
			'post_type'			=> 'shop_order',
			'post_status' 		=> 'any',
			'posts_per_page' 	=> -1,
			'orderby'			=> 'date',
			'order'				=> 'ASC',

			'year'				=> $year,
			'monthnum'			=> $month,
		);
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) : $loop->the_post();
			$order_id = $loop->post->ID;
			$invoice_type_id = 'invoice';


			if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {

				$the_order = wc_get_order( $order_id );

				$user_meta = get_user_meta( $the_order->get_user_id() );
				$billing_country  = isset( $user_meta['billing_country'][0] )  ? $user_meta['billing_country'][0]  : '';
				$shipping_country = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
				$customer_country = ( '' == $billing_country ) ? $shipping_country : $billing_country;

				$order_total = $the_order->get_total();

				$order_tax = apply_filters( 'wcj_order_total_tax', $the_order->get_total_tax(), $the_order );
				//$order_tax_percent = ( isset( $taxes_by_countries_eu[ $customer_country ] ) ) ? $taxes_by_countries_eu[ $customer_country ] : 0;
				//$order_tax_percent /= 100;
				//$order_tax = $order_total * $order_tax_percent;
				$order_total_exlc_tax = $order_total - $order_tax;
				$order_tax_percent = ( 0 == $order_total ) ? 0 : $order_tax / $order_total_exlc_tax;

				$total_sum += $order_total;
				$total_sum_excl_tax += $order_total_exlc_tax;
				$total_tax += $order_tax;

				//$order_tax_html = ( 0 == $order_tax ) ? '' : sprintf( '$ %.2f', $order_tax );
				$order_tax_html = sprintf( '%.2f', $order_tax );

				$data[] = array(
					wcj_get_invoice_number( $order_id, $invoice_type_id ),
					wcj_get_invoice_date( $order_id, $invoice_type_id, 0, get_option( 'date_format' ) ),
					$order_id,
					$customer_country,
					sprintf( '%.0f %%', $order_tax_percent * 100 ),
					sprintf( '%.2f', $order_total_exlc_tax ),
					$order_tax_html,
					sprintf( '%.2f', $order_total ),
					$the_order->get_order_currency(),
				);
			}
		endwhile;

		/* $output .= '<h3>' . 'Total Sum Excl. Tax: ' . sprintf( '$ %.2f', $total_sum_excl_tax ) . '</h3>';
		$output .= '<h3>' . 'Total Sum: ' . sprintf( '$ %.2f', $total_sum ) . '</h3>';
		$output .= '<h3>' . 'Total Tax: ' . sprintf( '$ %.2f', $total_tax ) . '</h3>'; */
		$output .= wcj_get_table_html( $data, array( 'table_class' => 'widefat', ) );
		/**/

		return $output;
	}
	/**/
}

endif;

return new WCJ_PDF_Invoicing_Report_Tool();

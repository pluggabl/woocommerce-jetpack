<?php
/**
 * WooCommerce Jetpack PDF Invoices
 *
 * The WooCommerce Jetpack PDF Invoices class.
 *
 * @version 2.3.10
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_PDF_Invoices' ) ) :

class WCJ_PDF_Invoices {

	public $default_css;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->default_css =
			'.pdf_invoice_header_text_wcj { text-align: right; color: gray; font-weight: bold; } ' .
			'.pdf_invoice_number_and_date_table_wcj { width: 50%; } ' .
			'.pdf_invoice_items_table_wcj { padding: 5px; width: 100%; } ' .
			'.pdf_invoice_items_table_wcj th { border: 1px solid #F0F0F0; font-weight: bold; text-align: center; } ' .
			'.pdf_invoice_items_table_wcj td { border: 1px solid #F0F0F0; } ' .
			'.pdf_invoice_totals_table_wcj { padding: 5px; width: 100%; } ' .
			'.pdf_invoice_totals_table_wcj th { width: 80%; text-align: right; } ' .
			'.pdf_invoice_totals_table_wcj td { width: 20%; text-align: right; border: 1px solid #F0F0F0; }';

//		add_shortcode( 'wcj_order_date',            array( $this, 'shortcode_pdf_invoices_order_date' ) );
//		add_shortcode( 'wcj_order_billing_address', array( $this, 'shortcode_pdf_invoices_billing_address' ) );
//		add_shortcode( 'wcj_items_total_weight',    array( $this, 'shortcode_pdf_invoices_items_total_weight' ) );
//		add_shortcode( 'wcj_items_total_quantity',  array( $this, 'shortcode_pdf_invoices_items_total_quantity' ) );
//		add_shortcode( 'wcj_items_total_number',    array( $this, 'shortcode_pdf_invoices_items_total_number' ) );

	    // Main hooks
	    if ( get_option( 'wcj_pdf_invoices_enabled' ) == 'yes' ) {

			add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_pdf_invoices_link_to_order_list' ), 100, 2 );

			add_action( 'init', array( $this, 'generate_pdf' ), 10 );

			add_action( 'admin_head', array( $this, 'add_pdf_invoice_icon_css' ) );

			if ( 'yes' === apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_pdf_invoices_enabled_for_customers' ) ) )
				add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_pdf_invoices_link_to_my_account' ), 100, 2 );

			if ( 'yes' === apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_pdf_invoices_attach_to_email_enabled' ) ) )
				add_filter( 'woocommerce_email_attachments', array( $this, 'add_pdf_invoice_email_attachment' ), 100, 3 );

			add_filter( 'woocommerce_payment_gateways_settings', array( $this, 'add_attach_invoice_settings' ), 100 );
	    }

	    // Settings hooks
	    add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );
	    add_filter( 'wcj_settings_pdf_invoices', array( $this, 'get_settings' ), 100 );
	    add_filter( 'wcj_features_status', array( $this, 'add_enabled_option' ), 100 );
	}

	/**
	 * add_attach_invoice_settings.
	 */
	function add_attach_invoice_settings( $settings ) {
	    $settings[] = array( 'title' => __( 'Payment Gateways Attach PDF Invoice V1 Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you choose when to attach PDF invoice to customers emails.', 'woocommerce-jetpack' ), 'id' => 'wcj_gateways_attach_invoice_options' );
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $key => $gateway ) {

			$settings = array_merge( $settings, array(

				array(
					'title'		=> $gateway->title,
					//'desc'		=> __( 'Attach PDF invoice to customers emails.', 'woocommerce-jetpack' ),
					'desc'		=> __( 'Attach PDF invoice.', 'woocommerce-jetpack' ),
					'id'       	=> 'wcj_gateways_attach_invoice_' . $key,
					'default'  	=> 'yes',
					'type'		=> 'checkbox',
				),


			) );
	    }

	    $settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_gateways_attach_invoice_options' );

		return $settings;
	}

	/**
	 * Shortcodes.
	 */
	public function get_shortcode( $shortcode, $atts ) {
		$atts = shortcode_atts( array(
			'before' 			=> '',
			'after' 			=> '',
			'visibility' 		=> '',
			'options' 			=> '',
			'id' 				=> '',
		), $atts, $shortcode );
		if ( 'admin' === $atts['visibility'] && ! is_super_admin() )
			return '';
		if ( '' != ( $result = $this->get_pdf_invoices_short_code( $shortcode, $atts['id'], $atts['options'] ) ) )
			return $atts['before'] . $result . $atts['after'];
		return '';
	}

	public function get_pdf_invoices_short_code( $shortcode, $id, $options ) {
		// Get the order id
		if ( isset( $_GET['pdf_invoice'] ) )
			$order_id = $_GET['pdf_invoice'];
		else if ( '' != $id )
			$order_id = $id;
		// Get the order
		$the_order = wc_get_order( $order_id );
		if ( false == $the_order )
			return '';
		// The shortcodes
		switch ( $shortcode ) {
			case '%order_date%':
				return date_i18n( get_option( 'date_format' ), strtotime( $the_order->order_date ) );
			case '%billing_address%':
				return $the_order->get_formatted_billing_address();
			case '%items_total_weight%':
				return $this->get_order_items_total_weight( $the_order );
			case '%items_total_number%':
				return $this->get_order_items_total_number( $the_order );
			case '%items_total_quantity%':
				return $this->get_order_items_total_quantity( $the_order );
			default:
				return '';
		}
		return '';
	}

	public function get_order_items_total_weight( $the_order ) {
		$total_weight = 0;
		$the_items = $the_order->get_items();
		foreach( $the_items as $the_item ) {
			//$the_product = new WC_Product( $the_item['product_id'] );
			$the_product = wc_get_product( $the_item['product_id'] );
			$total_weight += $the_item['qty'] * $the_product->get_weight();
		}
		return ( 0 == $total_weight ) ? '' : $total_weight;
	}

	public function get_order_items_total_number( $the_order ) {
		$total_number = count( $the_order->get_items() );
		return ( 0 == $total_number ) ? '' : $total_number;
	}

	public function get_order_items_total_quantity( $the_order ) {
		$total_quantity = 0;
		$the_items = $the_order->get_items();
		foreach( $the_items as $the_item )
			$total_quantity += $the_item['qty'];
		return ( 0 == $total_quantity ) ? '' : $total_quantity;
	}

	public function shortcode_pdf_invoices_order_date( $atts ) {
		return $this->get_shortcode( '%order_date%', $atts );
	}

	public function shortcode_pdf_invoices_billing_address( $atts ) {
		return $this->get_shortcode( '%billing_address%', $atts );
	}

	public function shortcode_pdf_invoices_items_total_weight( $atts ) {
		return $this->get_shortcode( '%items_total_weight%', $atts );
	}

	public function shortcode_pdf_invoices_items_total_number( $atts ) {
		return $this->get_shortcode( '%items_total_number%', $atts );
	}

	public function shortcode_pdf_invoices_items_total_quantity( $atts ) {
		return $this->get_shortcode( '%items_total_quantity%', $atts );
	}

	/**
	 * do_attach_for_payment_method.
	 */
	public function do_attach_for_payment_method( $payment_method ) {
		return ( 'no' === get_option( 'wcj_gateways_attach_invoice_' . $payment_method, 'yes' ) ) ? false : true;
	}

	/**
	 * add_pdf_invoice_email_attachment.
	 */
	public function add_pdf_invoice_email_attachment( $attachments, $status, $order ) {
		if ( isset( $status ) && 'customer_completed_order' === $status && isset( $order ) && true === $this->do_attach_for_payment_method( $order->payment_method ) ) {
			$file_name = sys_get_temp_dir() . '/invoice-' .  $order->id . '.pdf';
			$result = file_put_contents( $file_name, $this->generate_pdf( $order->id ) );
			$attachments[] = $file_name;
		}
		return $attachments;
	}

	/**
	 * Unlocks - PDF Invoices - add_pdf_invoices_link_to_my_account.
	 */
	public function add_pdf_invoices_link_to_my_account( $actions, $the_order ) {

		if ( 'no' === get_option( 'wcj_pdf_invoices_save_as_enabled' ) )
			$actions['pdf_invoice'] = array(
				'url'  => $_SERVER['REQUEST_URI'] . '?pdf_invoice=' . $the_order->id,
				'name' => __( 'Invoice', 'woocommerce-jetpack' ),
			);
		else
			$actions['save_pdf_invoice'] = array(
				'url'  => $_SERVER['REQUEST_URI'] . '?pdf_invoice=' . $the_order->id . '&save_pdf_invoice=1',
				'name' => __( 'Invoice', 'woocommerce-jetpack' ),
			);

		return $actions;
	}

	/**
	 * add_pdf_invoice_icon_css.
	 */
	function add_pdf_invoice_icon_css() {

		echo '<style> a.button.tips.view.pdf_invoice:after { content: "\e028" !important; } </style>';
		echo '<style> a.button.tips.view.save_pdf_invoice:after { content: "\e028" !important; } </style>';
	}

	/**
	 * generate_pdf.
	 */
	public function generate_pdf( $get_by_order_id = 0 ) {

		if ( ! isset( $_GET['pdf_invoice'] ) && 0 == $get_by_order_id ) return;

		$order_id = ( 0 == $get_by_order_id ) ? $_GET['pdf_invoice'] : $get_by_order_id;

		if ( ! is_user_logged_in() && 0 == $get_by_order_id ) return;

		if ( ( ! current_user_can( 'administrator' ) ) && ( ! is_shop_manager() ) && ( get_current_user_id() != intval( get_post_meta( $order_id, '_customer_user', true ) ) ) && ( 0 == $get_by_order_id ) ) return;

		// Include the main TCPDF library (search for installation path).
		//require_once('tcpdf_include.php');
		require_once( 'lib/tcpdf_min/tcpdf.php' );

		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator( PDF_CREATOR );
		//$pdf->SetAuthor( 'Algoritmika Ltd.' );
		$pdf->SetTitle( 'Invoice' );
		$pdf->SetSubject( 'Invoice PDF' );
		$pdf->SetKeywords( 'invoice, PDF' );

		// set default header data
		// TODO 2014.09.21
//		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		//$pdf->SetHeaderData( get_option( 'wcj_pdf_invoices_seller_logo_url' ), 30, get_option( 'wcj_pdf_invoices_header_title' ), get_option( 'wcj_pdf_invoices_header_string' ), array(0,64,255), array(0,64,128));
		$pdf->SetPrintHeader(false);
		$pdf->setFooterData(array(0,64,0), array(0,64,128));
		//$pdf->SetPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		/*
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}
		*/

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		$pdf->SetFont(
			apply_filters( 'wcj_get_option_filter', 'dejavusans', get_option( 'wcj_pdf_invoices_general_font_family', 'dejavusans' ) ),
			'',
			apply_filters( 'wcj_get_option_filter', 8, get_option( 'wcj_pdf_invoices_general_font_size' ) ),
			'',
			true);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		if ( 'yes' === get_option( 'wcj_pdf_invoices_general_font_shadowed', 'yes' ) ) {
			// set text shadow effect
			$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
		}

		$html = $this->get_invoice_html( $order_id );

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
//		$the_order = new WC_Order( $order_id );
//		$order_number = $the_order->get_order_number();






/**/

		if ( $get_by_order_id > 0 )
			return $pdf->Output('invoice-' . $order_id . '.pdf', 'S');
		else
		{
			$file_name = 'invoice-' .  $order_id . '.pdf';
			$file_path = sys_get_temp_dir() . '/' . $file_name;
			$result = file_put_contents( $file_path, $pdf->Output( '', 'S' ) );
			//echo $pdf->Output( '', 'S' );

			if ( isset( $_GET['save_pdf_invoice'] ) && '1' == $_GET['save_pdf_invoice'] ) {
				//$pdf->Output('invoice-' . $order_id . '.pdf', 'D');

				header("Content-Type: application/octet-stream");

				//$file = $file_name;//$_GET["file"] .".pdf";
				header("Content-Disposition: attachment; filename=" . urlencode($file_name));
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");

			}
			else {
				//$pdf->Output('invoice-' . $order_id . '.pdf', 'I');

				header("Content-type: application/pdf");
				header("Content-Disposition: inline; filename=" . urlencode($file_name));
			}

			header("Content-Length: " . filesize($file_path));
			flush(); // this doesn't really matter.
			if ( false !== ( $fp = fopen($file_path, "r") ) ) {
				while (!feof($fp))
				{
					echo fread($fp, 65536);
					flush(); // this is essential for large downloads
				}
				fclose($fp);
			} else {
				die( __( 'Unexpected error', 'woocommerce-jetpack' ) );
			}
		}




			/**/





			/**


		if ( $get_by_order_id > 0 )
			return $pdf->Output('invoice-' . $order_id . '.pdf', 'S');
		if ( isset( $_GET['save_pdf_invoice'] ) && '1' == $_GET['save_pdf_invoice'] )
			$pdf->Output('invoice-' . $order_id . '.pdf', 'D');
		else
			$pdf->Output('invoice-' . $order_id . '.pdf', 'I');

			/**/







	}


	/**
	 * add_custom_checkout_fields_to_pdf.
	 */
	public function add_custom_checkout_fields_to_pdf( $order_id, $section ) {

		$result = '';

		$post_meta = get_post_meta( $order_id );
		foreach( $post_meta as $key => $values ) {
			$value = maybe_unserialize( $values[0] );
			if ( isset( $value['section'] ) && $section === $value['section'] ) {
				if ( '' != $value['value']  ) {
					$the_label = $value['label'];
					if ( '' != $the_label )
						$the_label = $the_label . ': ';
					$result .= '<p>' . $the_label . $value['value'] . '</p>';
				}
			}
		}

		return $result;
	}

	/**
	 * get_columns.
	 */
	public function get_columns() {

		// Count optional columns number for column width calculation
		$total_optional_columns = 0;
		if ( '' != get_option( 'wcj_pdf_invoices_column_single_price_tax_excl_text' ) ) $total_optional_columns++;
		if ( '' != get_option( 'wcj_pdf_invoices_column_single_price_tax_text' ) ) $total_optional_columns++;
		if ( '' != get_option( 'wcj_pdf_invoices_column_single_price_tax_incl_text' ) ) $total_optional_columns++;
		if ( '' != get_option( 'wcj_pdf_invoices_column_price_tax_excl_text' ) ) $total_optional_columns++;
		if ( '' != get_option( 'wcj_pdf_invoices_column_price_tax_percent' ) ) $total_optional_columns++;
		if ( '' != get_option( 'wcj_pdf_invoices_column_price_tax_text' ) ) $total_optional_columns++;
		$price_column_width = 50 / ( 1 + $total_optional_columns );

		// Columns array //
		// - if required == false (i.e. optional), then title != '' will be checked
		return array(
			array(
				//'name'		=> 'item-counter',
				'title'		=> get_option( 'wcj_pdf_invoices_column_nr_text' ),
				'css'		=> 'width:7%;',
				'required'	=> true,
				'value_var'	=> 'item_counter',
				'td_css'	=> 'text-align:center;',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_item_name_text' ),
				'css'		=> 'width:36%;',
				'required'	=> true,
				'value_var'	=> 'item_name',
				'td_css'	=> '',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_qty_text' ),
				'css'		=> 'width:7%;',
				'required'	=> true,
				'value_var'	=> 'item_quantity',
				'td_css'	=> 'text-align:center;',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_single_price_tax_excl_text' ),
				'css'		=> 'width:' . $price_column_width . '%;',
				'required'	=> false,
				'value_var'	=> 'item_total_excl_tax_formatted',
				'td_css'	=> 'text-align:right;',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_single_price_tax_text' ),
				'css'		=> 'width:' . $price_column_width . '%;',
				'required'	=> false,
				'value_var'	=> 'item_total_tax_formatted',
				'td_css'	=> 'text-align:right;',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_single_price_tax_incl_text' ),
				'css'		=> 'width:' . $price_column_width . '%;',
				'required'	=> false,
				'value_var'	=> 'item_total_incl_tax_formatted',
				'td_css'	=> 'text-align:right;',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_price_tax_excl_text' ),
				'css'		=> 'width:' . $price_column_width . '%;',
				'required'	=> false,
				'value_var'	=> 'line_total_excl_tax_formatted',
				'td_css'	=> 'text-align:right;',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_price_tax_percent' ),
				'css'		=> 'width:' . $price_column_width . '%;',
				'required'	=> false,
				'value_var'	=> 'line_total_tax_percent',
				'td_css'	=> 'text-align:right;',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_price_tax_text' ),
				'css'		=> 'width:' . $price_column_width . '%;',
				'required'	=> false,
				'value_var'	=> 'line_total_tax_formatted',
				'td_css'	=> 'text-align:right;',
			),
			array(
				'title'		=> get_option( 'wcj_pdf_invoices_column_price_text' ),
				'css'		=> 'width:' . $price_column_width . '%;',
				'required'	=> true,
				'value_var'	=> 'line_total_incl_tax_formatted',
				'td_css'	=> 'text-align:right;',
			),
		);
	}

	/**
	 * get_header.
	 */
	public function get_header( $the_order ) {
		// Starting output
		// Css
		$html = '<style>' . apply_filters( 'wcj_get_option_filter', $this->default_css, get_option( 'wcj_pdf_invoices_general_css' ) ) . '</style>';

		// HEADER //
		// TEXT AND LOGO //
		$html .= '<p>';
		if ( '' != get_option( 'wcj_pdf_invoices_seller_logo_url' ) )
			$html .= '<img src="' . get_option( 'wcj_pdf_invoices_seller_logo_url' ) . '">';
		$html .= '<div class="pdf_invoice_header_text_wcj">' . get_option( 'wcj_pdf_invoices_header_text' ) . '</div>';
		// NUMBER AND DATE //
		$html .= '<table class="pdf_invoice_number_and_date_table_wcj"><tbody>';
		$html .= '<tr><td>' . get_option( 'wcj_pdf_invoices_invoice_number_text' ) . '</td><td>' . $the_order->get_order_number() . '</td></tr>';
		if ( '' != get_option( 'wcj_pdf_invoices_order_date_text' ) )
			$html .= '<tr><td>' . get_option( 'wcj_pdf_invoices_order_date_text' ) . '</td><td>' . date_i18n( get_option( 'date_format' ), strtotime( $the_order->order_date ) ) . '</td></tr>';
		if ( '' != get_option( 'wcj_pdf_invoices_order_time_text' ) )
			$html .= '<tr><td>' . get_option( 'wcj_pdf_invoices_order_time_text' ) . '</td><td>' . date_i18n( get_option( 'time_format' ), strtotime( $the_order->order_date ) ) . '</td></tr>';
		$html .= '<tr><td>' . get_option( 'wcj_pdf_invoices_invoice_date_text' ) . '</td><td>' . date_i18n( get_option( 'date_format' ), strtotime( $the_order->order_date ) ) . '</td></tr>';
		if ( '' != get_option( 'wcj_pdf_invoices_invoice_due_date_text' ) )
			$html .= '<tr><td>' . get_option( 'wcj_pdf_invoices_invoice_due_date_text' ) . '</td><td>' . date_i18n( get_option( 'date_format' ), ( strtotime( $the_order->order_date ) + get_option( 'wcj_pdf_invoices_invoice_due_date_days' ) * 24 * 60 *60 ) ) . '</td></tr>';
		if ( '' != get_option( 'wcj_pdf_invoices_invoice_fulfillment_date_text' ) )
			$html .= '<tr><td>' . get_option( 'wcj_pdf_invoices_invoice_fulfillment_date_text' ) . '</td><td>' . date_i18n( get_option( 'date_format' ), ( strtotime( $the_order->order_date ) + get_option( 'wcj_pdf_invoices_invoice_fulfillment_date_days' ) * 24 * 60 *60 ) ) . '</td></tr>';
		$html .= '</tbody></table>';
		$html .= '</p>';

		// SELLER AND BUYER INFO //
		$html .= '<p>';
		$html .= '<table class="pdf_invoice_seller_and_buyer_table_wcj"><tbody>';
		$html .= '<tr><td>';
		$html .= '<h2>' . get_option( 'wcj_pdf_invoices_seller_text' ) . '</h2>';
		$html .= str_replace( PHP_EOL, '<br>', get_option( 'wcj_pdf_invoices_seller_info' ) );
		$html .= '</td><td>';
		$html .= '<h2>' . get_option( 'wcj_pdf_invoices_buyer_text' ) . '</h2>';
		$html .= $the_order->get_formatted_billing_address() . $this->add_custom_checkout_fields_to_pdf( $the_order->id, 'billing' );
		$html .= '</td></tr></tbody></table>';
		$html .= '</p>';

		return $html;
	}

	/**
	 * get_items_table.
	 */
	public function get_items_table( $the_order ) {

		$html = '';

		// ITEMS //
		$the_items = $the_order->get_items();
		$html .= '<h2>' . get_option( 'wcj_pdf_invoices_items_text' ) . '</h2>';
		$html .= '<table class="pdf_invoice_items_table_wcj"><tbody>';
		$columns = $this->get_columns();
		// Adding to output //
		$html .= '<tr>';
		foreach ( $columns as $column )
			if ( ( true === $column['required'] ) || ( '' != $column['title'] ) )
				$html .= '<th style="' . $column['css'] . '">' . $column['title'] . '</th>';
		$html .= '</tr>';



		// Shipping as item
		$order_total_shipping = $the_order->get_total_shipping();
		if ( ( '' != get_option( 'wcj_pdf_invoices_display_shipping_as_item_text' ) ) && ( $order_total_shipping > 0 ) ) {

			$shipping_item_name = get_option( 'wcj_pdf_invoices_display_shipping_as_item_text' );

			// Add shipping method text
			if ( 'yes' === get_option( 'wcj_pdf_invoices_display_shipping_as_item_shipping_method' ) )
				$shipping_item_name .= '<div style="font-size:x-small;">' . $the_order->get_shipping_method() . '</div>';
			else if ( 'replace' === get_option( 'wcj_pdf_invoices_display_shipping_as_item_shipping_method' ) )
				$shipping_item_name = $the_order->get_shipping_method();

			// Create item
			$the_items[] = array(
				'name'				=> $shipping_item_name,
				'type' 				=> 'line_item',
				'qty' 				=> 1,
				'line_subtotal' 	=> $order_total_shipping,
				'line_total' 		=> $order_total_shipping,
				'line_tax' 			=> $the_order->get_shipping_tax(),
				'line_subtotal_tax' => $the_order->get_shipping_tax(),
				'item_meta'			=> array(
					'_qty' 					=> array( 1 ),
					'_line_subtotal' 		=> array( $order_total_shipping ),
					'_line_total' 			=> array( $order_total_shipping ),
					'_line_tax' 			=> array( $the_order->get_shipping_tax() ),
					'_line_subtotal_tax' 	=> array( $the_order->get_shipping_tax() ),
				),
			);
		}

		// Discount as item
		$total_tax_excl = $the_order->get_total_discount( true );
		if ( ( '' != get_option( 'wcj_pdf_invoices_display_discount_as_item_text' ) ) && ( 0 != $total_tax_excl ) ) {

			//$total_tax_excl = $the_order->get_total_discount( true );
			$tax = $the_order->get_total_discount( false ) - $total_tax_excl;

			if ( false != ( $the_tax = $this->wcj_order_get_cart_discount_tax( $the_order ) ) ) {
				$total_tax_excl -= $the_tax;
				$tax += $the_tax;
			}

			$total_tax_excl *= -1;
			$tax *= -1;

			$the_items[] = array(
				'name'				=> get_option( 'wcj_pdf_invoices_display_discount_as_item_text' ),
				'type' 				=> 'line_item',
				'qty' 				=> 1,
				'line_subtotal' 	=> $total_tax_excl,
				'line_total' 		=> $total_tax_excl,
				'line_tax' 			=> $tax,
				'line_subtotal_tax' => $tax,
				'item_meta'			=> array(
					'_qty' 					=> array( 1 ),
					'_line_subtotal' 		=> array( $total_tax_excl ),
					'_line_total' 			=> array( $total_tax_excl ),
					'_line_tax' 			=> array( $tax ),
					'_line_subtotal_tax' 	=> array( $tax ),
				),
			);
		}


		$order_currency_array = array( 'currency' => $the_order->get_order_currency() );


		// ITEMS LOOP //
		$item_counter = 0;
		foreach ( $the_items as $item ) {

			// Preparing data //
			// Item Prices
			$item_total_excl_tax 	= $the_order->get_item_subtotal( $item, false, false );
//			$item_tax 				= $the_order->get_item_tax( $item, true );
			$item_tax 				= $the_order->get_item_subtotal( $item, true, false ) - $the_order->get_item_subtotal( $item, false, false );
			$item_total_incl_tax 	= $the_order->get_item_subtotal( $item, true, false );
			// Line Prices
			$line_total_excl_tax 	= $the_order->get_line_subtotal( $item, false, false );
			$line_tax 				= $the_order->get_line_subtotal( $item, true, false ) - $the_order->get_line_subtotal( $item, false, false );
//			$line_tax 				= $the_order->get_line_tax( $item );
			$line_total_incl_tax 	= $the_order->get_line_subtotal( $item, true, false );
			$line_total_tax_percent = 0;
			if ( 0 != $the_order->get_item_total( $item, false, false ) /* $line_total_excl_tax */ ) {
				$line_total_tax_percent = $the_order->get_item_tax( $item, false ) / $the_order->get_item_total( $item, false, false ) * 100;//round( ( $line_tax / $line_total_excl_tax * 100 ), 2 );
				$line_total_tax_percent = sprintf( '%.2f %%', $line_total_tax_percent );
			}

			$item_total_excl_tax_formatted =  wc_price( $item_total_excl_tax, $order_currency_array );
			$item_total_tax_formatted 	   =  wc_price( $item_tax, $order_currency_array );
			$item_total_incl_tax_formatted =  wc_price( $item_total_incl_tax, $order_currency_array );

			$line_total_excl_tax_formatted =  wc_price( $line_total_excl_tax, $order_currency_array );
			$line_total_tax_formatted      =  wc_price( $line_tax, $order_currency_array );
			$line_total_incl_tax_formatted =  wc_price( $line_total_incl_tax, $order_currency_array );

			// Item Quantity
			$item_quantity			= $item['qty'];

			// Item Name
			$item_name				= $item['name'];	//$product->get_title();


			$product = $the_order->get_product_from_item( $item );
			if ( $product ) {
				// Additional info (e.g. SKU)
				if ( '' != get_option( 'wcj_pdf_invoices_column_item_name_additional_text' ) && $product->get_sku() )
					$item_name .= ' ' . str_replace( '%sku%', $product->get_sku(), get_option( 'wcj_pdf_invoices_column_item_name_additional_text' ) );

				// Variation (if needed)
				if ( $product->is_type( 'variation' ) )
					$item_name .= '<div style="font-size:smaller;">' . wc_get_formatted_variation( $product->variation_data, true ) . '</div>';
			}

			// Item Counter
			$item_counter++;

			// Adding to output //
			$html .= '<tr>';
			foreach ( $columns as $column )
				if ( ( true === $column['required'] ) || ( '' != $column['title'] ) ) {
					$html .= '<td style="' . $column['td_css'] . '">' . $$column['value_var'] . '</td>';
				}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';

		return $html;
	}


	/**
	 * wcj_order_get_cart_discount_tax.
	 */
	function wcj_order_get_cart_discount_tax( $the_order ) {

		$the_cart_discount = $the_order->get_cart_discount();
		$is_discount_taxable = ( $the_cart_discount > 0 ) ? true : false;

		if ( $is_discount_taxable ) {

			/* $order_total_incl_tax = $this->the_order->get_total();
			$order_total_tax      = $this->the_order->get_total_tax(); */



			$order_total_incl_tax = 0;
			$order_total_tax = 0;
			$items = $the_order->get_items();
			foreach ( $items as $item ) {
				$order_total_incl_tax += $item['line_total'] + $item['line_tax'];
				$order_total_tax += $item['line_tax'];
			}





			if ( 0 != $order_total_incl_tax ) {

//				$order_tax_rate = $order_total_tax / ( $order_total_incl_tax - $order_total_tax );
				$order_tax_rate = $order_total_tax / $order_total_incl_tax;
				//$order_tax_rate = round( $order_tax_rate, 4 );

				$the_tax = $the_cart_discount * $order_tax_rate;//$the_cart_discount * $order_tax_rate;

				/* wcj_log( $order_total_incl_tax );
				wcj_log( $order_total_tax );
				wcj_log( $order_tax_rate );
				wcj_log( $the_tax );
				wcj_log( $the_tax / ( $the_cart_discount - $the_tax) );
				wcj_log( $order_total_tax / ( $order_total_incl_tax - $order_total_tax ) ); */

				return $the_tax;
			}
		}

		return false;
	}

	/**
	 * get_footer.
	 */
	public function get_footer( $the_order ) {

		// ORDER TOTALS //





		// Getting Totals
		$order_total_incl_tax		   = $the_order->get_total();
		$order_total_tax			   = $the_order->get_total_tax();
		$order_total_excl_tax 		   = $order_total_incl_tax - $order_total_tax;
		//$order_total_discount_incl_tax = $the_order->get_total_discount( false );
		$order_total_discount_excl_tax = $the_order->get_total_discount( true ); //excl_tax

		$is_discount_taxable = ( $the_order->get_cart_discount() === $the_order->get_total_discount() ) ? true : false;
		if ( $is_discount_taxable && 0 != $order_total_incl_tax ) {
			$order_tax_rate = $order_total_tax / $order_total_incl_tax;
			$order_total_discount_excl_tax = $order_total_discount_excl_tax - $order_total_discount_excl_tax * $order_tax_rate;
		}

		//$order_subtotal 			   = $order_total_excl_tax + $order_total_discount_excl_tax;
		$order_subtotal 			   = $the_order->get_subtotal();
		$order_total_shipping_excl_tax = $the_order->get_total_shipping();
		/* if ( '' === get_option( 'wcj_pdf_invoices_display_shipping_as_item_text' ) )
			$order_subtotal            = $order_subtotal - $order_total_shipping_excl_tax; */
		if ( '' != get_option( 'wcj_pdf_invoices_display_shipping_as_item_text' ) )
			$order_subtotal            = $order_subtotal + $order_total_shipping_excl_tax;

		if ( '' != get_option( 'wcj_pdf_invoices_display_discount_as_item_text' ) )
			$order_subtotal            = $order_subtotal - $order_total_discount_excl_tax;


		$order_currency_array = array( 'currency' => $the_order->get_order_currency() );
		$html = '';
		$html .= '<p><table class="pdf_invoice_totals_table_wcj"><tbody>';
		// FEES
		$fees_array = $the_order->get_fees();
		if ( ! empty( $fees_array ) )
			foreach ( $fees_array as $key => $fee_array )
				$html .= '<tr><th>' . $fee_array['name'] . '</th><td>' . wc_price( $fee_array['line_total'], $order_currency_array ) . '</td></tr>';
		// SUBTOTAL
		//$html .= '<tr><th>' . get_option( 'wcj_pdf_invoices_order_subtotal_text' ) . '</th><td>' . wc_price( ( $order_subtotal + $order_total_discount_excl_tax ), $order_currency_array ) . '</td></tr>';
		$html .= '<tr><th>' . get_option( 'wcj_pdf_invoices_order_subtotal_text' ) . '</th><td>' . wc_price( $order_subtotal, $order_currency_array ) . '</td></tr>';
		// SHIPPING
		if ( ( '' === get_option( 'wcj_pdf_invoices_display_shipping_as_item_text' ) ) && ( $order_total_shipping_excl_tax > 0 ) )
			$html .= '<tr><th>' . get_option( 'wcj_pdf_invoices_order_shipping_text' ) . '</th><td>' .  wc_price( $order_total_shipping_excl_tax, $order_currency_array ) . '</td></tr>';
		// DISCOUNT
		if ( ( '' === get_option( 'wcj_pdf_invoices_display_discount_as_item_text' ) ) && ( 0 != $order_total_discount_excl_tax ) )
			$html .= '<tr><th>' . get_option( 'wcj_pdf_invoices_order_discount_text' ) . '</th><td>-' .  wc_price( ( $order_total_discount_excl_tax /* + $order_total_discount_tax */ ), $order_currency_array ) . '</td></tr>';
		// SUBTOTAL2 - with discount and shipping
		//if ( $order_total_excl_tax != $order_subtotal )
		if ( wc_price( $order_total_excl_tax, $order_currency_array ) != wc_price( $order_subtotal, $order_currency_array ) )
			$html .= '<tr><th>' . get_option( 'wcj_pdf_invoices_order_total_excl_tax_text' ) . '</th><td>' . wc_price( $order_total_excl_tax, $order_currency_array ) . '</td></tr>';
		// TAXES
		if ( 0 != $order_total_tax )
			$html .= '<tr><th>' . get_option( 'wcj_pdf_invoices_order_total_tax_text' ) . '</th><td>' .  wc_price( $order_total_tax, $order_currency_array ) . '</td></tr>';
		// TOTAL
		$html .= '<tr><th>' . get_option( 'wcj_pdf_invoices_order_total_text' ) . '</th><td>' . $the_order->get_formatted_order_total() . '</td></tr>';
		$html .= '</tbody></table></p>';






		// PAYMENT METHOD
		if ( '' != get_option( 'wcj_pdf_invoices_order_payment_method_text' ) )
			$html .= '<p>' . get_option( 'wcj_pdf_invoices_order_payment_method_text' ). ': ' . $the_order->payment_method_title . '</p>';
		// SHIPPING METHOD
		if ( '' != get_option( 'wcj_pdf_invoices_order_shipping_method_text' ) )
			$html .= '<p>' . get_option( 'wcj_pdf_invoices_order_shipping_method_text' ). ': ' . $the_order->get_shipping_method() . '</p>';
		// SHIPPING ADDRESS
		if ( '' != get_option( 'wcj_pdf_invoices_order_shipping_address_text' ) &&
			 $the_order->get_formatted_billing_address() != $the_order->get_formatted_shipping_address() )
			$html .= '<p>' . get_option( 'wcj_pdf_invoices_order_shipping_address_text' ). ':<br>' . $the_order->get_formatted_shipping_address() . $this->add_custom_checkout_fields_to_pdf( $the_order->id, 'shipping' ) . '</p>';

		return $html;
	}

	/**
	 * get_invoice_html.
	 */
	public function get_invoice_html( $order_id ) {

		// PREPARING DATA //
		// General
		//$the_order = new WC_Order( $order_id );
		$the_order = wc_get_order( $order_id );


		$html = '';
		$html .= $this->get_header( $the_order );
		$html .= $this->get_items_table( $the_order );
		$html .= $this->get_footer( $the_order );



		// ADDITIONAL FOOTER
		if ( '' != get_option( 'wcj_pdf_invoices_footer_text' ) ) {
			$additional_footer = str_replace( PHP_EOL, '<br>', get_option( 'wcj_pdf_invoices_footer_text' ) );
			$additional_footer = apply_filters( 'the_content', $additional_footer );
			$html .= '<p>' . $additional_footer . '</p>';
		}

		// ADDITIONAL HEADER
		if ( '' != get_option( 'wcj_pdf_invoices_additional_header_text' ) ) {
			$additional_header = str_replace( PHP_EOL, '<br>', get_option( 'wcj_pdf_invoices_additional_header_text' ) );
			$additional_header = apply_filters( 'the_content', $additional_header );
			$html = '<p>' . $additional_header . '</p>' . $html;
		}

		return $html;
	}

	/**
	 * add_pdf_invoices_link_to_order_list.
	 */
	public function add_pdf_invoices_link_to_order_list( $actions, $the_order ) {
		if ( 'no' === get_option( 'wcj_pdf_invoices_save_as_enabled' ) )
			$actions['pdf_invoice'] = array(
				'url' 		=> basename( $_SERVER['REQUEST_URI'] ) . '&pdf_invoice=' . $the_order->id,
				'name' 		=> __( 'PDF Invoice', 'woocommerce-jetpack' ),
				'action' 	=> "view pdf_invoice"
			);
		else
			$actions['save_pdf_invoice'] = array(
				'url' 		=> basename( $_SERVER['REQUEST_URI'] ) . '&pdf_invoice=' . $the_order->id . '&save_pdf_invoice=1',
				'name' 		=> __( 'PDF Invoice', 'woocommerce-jetpack' ),
				'action' 	=> "view save_pdf_invoice"
			);

	    return $actions;
	}

	/**
	 * add_enabled_option.
	 */
	public function add_enabled_option( $settings ) {

	    $all_settings = $this->get_settings();
	    $settings[] = $all_settings[1];

	    return $settings;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

	    $settings = array(

	        array( 'title' => __( 'PDF Invoices Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_options' ),

	        array(
	            'title'    => __( 'PDF Invoices', 'woocommerce-jetpack' ) . ' V1 - ' . __( 'depreciated', 'woocommerce-jetpack' ),
	            'desc'     => '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
	            'desc_tip' => __( 'Add PDF invoices for the WooCommerce store owners and for the customers.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_enabled',
	            'default'  => 'no',
	            'type'     => 'checkbox',
	        ),

			array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_options' ),

			array( 'title' => __( 'Invoice Header', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you set texts for required invoice number and date, and optional logo, header text, invoice due and fulfillment dates.', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_header_options' ),

	        array(
	            'title'    => __( 'Your Logo URL', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Enter a URL to an image you want to show in the invoice\'s header. Upload your image using the <a href="/wp-admin/media-new.php">media uploader</a>.', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_seller_logo_url',
	            'default'  => '',
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Header Text', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
				'desc' 	   => __( 'Default: INVOICE', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_header_text',
	            'default'  => __( 'INVOICE', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Invoice Number', 'woocommerce-jetpack' ),
	            'desc' 	   => __( 'Default: Invoice number', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_invoice_number_text',
	            'default'  => __( 'Invoice number', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Order Date', 'woocommerce-jetpack' ),
	            'desc' 	   => __( 'Default: Order date', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_order_date_text',
	            'default'  => __( 'Order date', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Order Time', 'woocommerce-jetpack' ),
	            'desc' 	   => __( 'Default: Order time', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_order_time_text',
	            'default'  => __( 'Order time', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Invoice Date', 'woocommerce-jetpack' ),
	            'desc' 	   => __( 'Default: Invoice date', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_invoice_date_text',
	            'default'  => __( 'Invoice date', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => 'Invoice Due Date',
	            'desc' 	   => __( 'Default: Invoice due date', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_invoice_due_date_text',
	            'default'  => __( 'Invoice due date', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => '',
	            'desc' 	   => __( 'days', 'woocommerce-jetpack' ),
				//'desc_tip' => __( 'Set to 0 to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_invoice_due_date_days',
	            'default'  => 0,
	            'type'     => 'number',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => 'Invoice Fulfillment Date',
	            'desc' 	   => __( 'Default: Invoice fulfillment date', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_invoice_fulfillment_date_text',
	            'default'  => __( 'Invoice fulfillment date', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => '',
	            'desc' 	   => __( 'days', 'woocommerce-jetpack' ),
				//'desc_tip' => __( 'Set to 0 to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_invoice_fulfillment_date_days',
	            'default'  => 0,
	            'type'     => 'number',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Additional Header', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Additional header - will be displayed above all data on invoice. You can use html and/or shortcodes here.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_additional_header_text',
	            'default'  => '',
	            'type'     => 'textarea',
				'css'	   => 'width:33%;min-width:300px;min-height:300px;',
	        ),

			array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_header_options' ),

			array( 'title' => __( 'Seller and Buyer Info', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_seller_and_buyer_options' ),

	        array(
	            'title'    => __( 'Seller', 'woocommerce-jetpack' ),
	            //'desc_tip' => __( 'Seller text', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_seller_text',
	            'default'  => __( 'Seller', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Your business information', 'woocommerce-jetpack' ),
	            //'desc_tip' => __( 'Seller information', 'woocommerce-jetpack' ),
				'desc' 	   => __( 'New lines are added automatically.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_seller_info',
	            'default'  => __( '<strong>Company Name</strong>', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
				'css'	   => 'width:33%;min-width:300px;min-height:300px;',
	        ),

	        array(
	            'title'    => __( 'Buyer', 'woocommerce-jetpack' ),
	            //'desc_tip' => __( 'Buyer text', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_buyer_text',
	            'default'  => __( 'Buyer', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_seller_and_buyer_options' ),

			array( 'title' => __( 'Items', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_items_options' ),

	        array(
	            'title'    => __( 'Items Table Heading Text', 'woocommerce-jetpack' ),
	            //'desc_tip' => __( 'Items text', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_items_text',
	            'default'  => __( 'Items', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Shipping as Item', 'woocommerce-jetpack' ),
				'desc_tip'     => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Display shipping as item', 'woocommerce-jetpack' ),
				//'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	            'id'       => 'wcj_pdf_invoices_display_shipping_as_item_text',
	            'default'  => '',
	            'type'     => 'text',
				//'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
	        ),

	        /*array(
	            'title'    => '',
	            'desc'     => __( 'Add shipping method info', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_display_shipping_as_item_method_enabled',
	            'default'  => 'yes',
	            'type'     => 'checkbox',
	        ),*/

			array(
				'title'    => '',
				'desc'     => __( 'Add shipping method info', 'woocommerce-jetpack' ),
				'id'       => 'wcj_pdf_invoices_display_shipping_as_item_shipping_method',
				'css'      => 'min-width:350px;',
				'class'    => 'chosen_select',
				'default'  => 'no',
				'type'     => 'select',
				'options'  => array(
					'no'        => __( 'Do not add shipping method info', 'woocommerce-jetpack' ),
					'yes'       => __( 'Add shipping method info', 'woocommerce-jetpack' ),
					'replace'  	=> __( 'Replace with shipping method info', 'woocommerce-jetpack' ),
				),
				'desc_tip' =>  true,
			),

			array(
	            'title'    => __( 'Discount as Item', 'woocommerce-jetpack' ),
				'desc_tip'     => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Display discount as item', 'woocommerce-jetpack' ),
				//'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	            'id'       => 'wcj_pdf_invoices_display_discount_as_item_text',
	            'default'  => '',
	            'type'     => 'text',
				//'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
	        ),

			array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_items_options' ),

			array( 'title' => __( 'Items Columns', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you set column names in invoice items table. You can disable some columns by leaving blank column name.', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_items_columns_options' ),

	        array(
	            'title'    => __( 'Nr.', 'woocommerce-jetpack' ),
	            //'desc_tip' => __( 'Nr. text', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_nr_text',
	            'default'  => __( 'Nr.', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Item Name', 'woocommerce-jetpack' ),
	            //'desc_tip' => __( 'Item name text', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_item_name_text',
	            'default'  => __( 'Item Name', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Item Name Additional Info', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Here you can add more info to item\'s name column (e.g. sku). Default is (SKU: %sku%)', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_item_name_additional_text',
	            'default'  => __( '(SKU: %sku%)', 'woocommerce-jetpack' ),
	            'type'     => 'textarea',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

	        array(
	            'title'    => __( 'Qty', 'woocommerce-jetpack' ),
	            //'desc_tip' => __( 'Qty text', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_qty_text',
	            'default'  => __( 'Qty', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Single Item Price (TAX excl.)', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_single_price_tax_excl_text',
	            'default'  => __( 'Price (TAX excl.)', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Single Item TAX', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_single_price_tax_text',
	            'default'  => __( 'TAX', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Single Item Price (TAX incl.)', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_single_price_tax_incl_text',
	            'default'  => __( 'Price (TAX incl.)', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Sum (TAX excl.)', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_price_tax_excl_text',
	            'default'  => __( 'Sum (TAX excl.)', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Tax Percent', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_price_tax_percent',
	            'default'  => __( 'Taxes %', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Taxes', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_price_tax_text',
	            'default'  => __( 'Taxes', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Sum (TAX incl.)', 'woocommerce-jetpack' ),
	            //'desc_tip' => __( 'Price text', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_column_price_text',
	            'default'  => __( 'Sum (TAX incl.)', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_items_columns_options' ),

			array( 'title' => __( 'Totals', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you set texts for totals table.', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_totals_options' ),

				array(
					'title'    => __( 'Order Subtotal', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Order Subtotal = Total - Taxes - Shipping - Discounts', 'woocommerce-jetpack' ),
					'id'       => 'wcj_pdf_invoices_order_subtotal_text',
					'default'  => __( 'Order Subtotal', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'css'	   => 'width:33%;min-width:300px;',
				),

				array(
					'title'    => __( 'Order Shipping Price', 'woocommerce-jetpack' ),
					//'desc_tip' => __( 'Order Shipping text', 'woocommerce-jetpack' ),
					'id'       => 'wcj_pdf_invoices_order_shipping_text',
					'default'  => __( 'Shipping', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'css'	   => 'width:33%;min-width:300px;',
				),

				array(
					'title'    => __( 'Total Discount', 'woocommerce-jetpack' ),
					//'desc_tip' => __( 'Total Discount text', 'woocommerce-jetpack' ),
					'id'       => 'wcj_pdf_invoices_order_discount_text',
					'default'  => __( 'Discount', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'css'	   => 'width:33%;min-width:300px;',
				),

				array(
					'title'    => __( 'Order Total (TAX excl.)', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Order Total (TAX excl.) = Total - Taxes. Shown only if discount or shipping is not equal to zero. In other words: if "Order Total (TAX excl.)" not equal to "Order Subtotal"', 'woocommerce-jetpack' ),
					'id'       => 'wcj_pdf_invoices_order_total_excl_tax_text',
					'default'  => __( 'Order Total (TAX excl.)', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'css'	   => 'width:33%;min-width:300px;',
				),

				array(
					'title'    => __( 'Order Total Taxes', 'woocommerce-jetpack' ),
					//'desc_tip' => __( 'Order Subtotal = Total - Taxes - Shipping - Discounts', 'woocommerce-jetpack' ),
					'id'       => 'wcj_pdf_invoices_order_total_tax_text',
					'default'  => __( 'Taxes', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'css'	   => 'width:33%;min-width:300px;',
				),

				array(
					'title'    => __( 'Order Total', 'woocommerce-jetpack' ),
					//'desc_tip' => __( 'Order Total text', 'woocommerce-jetpack' ),
					'id'       => 'wcj_pdf_invoices_order_total_text',
					'default'  => __( 'Order Total', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'css'	   => 'width:33%;min-width:300px;',
				),

			array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_totals_options' ),

			array( 'title' => __( 'Footer', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_footer_options' ),

			array(
	            'title'    => __( 'Payment Method', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_order_payment_method_text',
	            'default'  => __( 'Payment Method', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Shipping Method', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_order_shipping_method_text',
	            'default'  => __( 'Shipping Method', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Shipping Address', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Will be displayed only if customer\'s shipping address differs from billing address. Leave blank to disable', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_order_shipping_address_text',
	            'default'  => __( 'Shipping Address', 'woocommerce-jetpack' ),
	            'type'     => 'text',
				'css'	   => 'width:33%;min-width:300px;',
	        ),

			array(
	            'title'    => __( 'Additional Footer', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Additional footer - will be displayed below all other data on invoice. You can use html and/or shortcodes here.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_footer_text',
	            'default'  => '',
	            'type'     => 'textarea',
				'css'	   => 'width:33%;min-width:300px;min-height:300px;',
	        ),

			array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_footer_options' ),

	        array( 'title' => __( 'General Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_general_options' ),

	        array(
	            'title'    => __( 'Font Family', 'woocommerce-jetpack' ),
	            //'desc'     => __( 'Default: dejavusans', 'woocommerce-jetpack' ),
				'desc'	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	            'id'       => 'wcj_pdf_invoices_general_font_family',
	            'default'  => 'dejavusans',
	            'type'     => 'select',
				'options'  => array(
								'dejavusans' => 'DejaVu Sans',
								'courier'    => 'Courier',
								'helvetica'  => 'Helvetica',
								'times'      => 'Times',
				),
				'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
	        ),

	        array(
	            'title'    => __( 'Font Size', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Default: 8', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_general_font_size',
	            'default'  => 8,
	            'type'     => 'number',
	        ),

	        array(
	            'title'    => __( 'Make Font Shadowed', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Default: Yes', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_pdf_invoices_general_font_shadowed',
	            'default'  => 'yes',
	            'type'     => 'checkbox',
	        ),

	        array(
	            'title'    => __( 'CSS', 'woocommerce-jetpack' ),
	            'desc'	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	            'id'       => 'wcj_pdf_invoices_general_css',
	            'default'  => $this->default_css,
	            'type'     => 'textarea',
				'css'	   => 'width:66%;min-width:300px;min-height:300px;',
				'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
	        ),

			//array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_general_options' ),

			//array( 'title' => __( 'More Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_pdf_invoices_more_options' ),

	        array(
	            'title'    => __( 'PDF Invoices for Customers (in My Account)', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Enable the PDF Invoices in customers account', 'woocommerce-jetpack' ),
				'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	            'id'       => 'wcj_pdf_invoices_enabled_for_customers',
	            'default'  => 'no',
	            'type'     => 'checkbox',
				'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
	        ),

	        array(
	            'title'    => __( 'PDF Invoices for Customers (Email attachment)', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Enable the PDF Invoices attachment files in customers email on order completed', 'woocommerce-jetpack' ),
				'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	            'id'       => 'wcj_pdf_invoices_attach_to_email_enabled',
	            'default'  => 'no',
	            'type'     => 'checkbox',
				'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
	        ),

	        /* array(
	            'title'    => __( '', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Attach PDF invoice only on customer\'s request', 'woocommerce-jetpack' ),
				'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	            'id'       => 'wcj_pdf_invoices_attach_to_email_on_request_only',
	            'default'  => 'no',
	            'type'     => 'checkbox',
				'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
	        ), */

	        array(
	            'title'    => __( 'Enable Save as', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Enable save as pdf instead of view pdf', 'woocommerce-jetpack' ),
				//'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	            'id'       => 'wcj_pdf_invoices_save_as_enabled',
	            'default'  => 'yes',
	            'type'     => 'checkbox',
				//'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
	        ),

	        //array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_more_options' ),
			array( 'type'  => 'sectionend', 'id' => 'wcj_pdf_invoices_general_options' ),
	    );

	    return $settings;
	}

	/**
	 * settings_section.
	 *
	 * @version 2.3.10
	 */
	function settings_section( $sections ) {
	    $sections['pdf_invoices'] = __( 'PDF Invoices', 'woocommerce-jetpack' ) . ' v1 - <em>' . __( 'Depreciated', 'woocommerce-jetpack' ) . '</em>';
	    return $sections;
	}
}

endif;

return new WCJ_PDF_Invoices();

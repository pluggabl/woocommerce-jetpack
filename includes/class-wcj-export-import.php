<?php
/**
 * Booster for WooCommerce - Module - Export
 *
 * @version 3.0.0
 * @since   2.5.4
 * @author  Algoritmika Ltd.
 * @todo    import products, customers and (maybe) orders
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Export_Import' ) ) :

class WCJ_Export_Import extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.5.4
	 */
	function __construct() {

		$this->id         = 'export';
		$this->short_desc = __( 'Export', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce export tools.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-export-tools';
		parent::__construct();

		$this->add_tools( array(
			'export_customers' => array(
				'title'     => __( 'Export Customers', 'woocommerce-jetpack' ),
				'desc'      => __( 'Export Customers.', 'woocommerce-jetpack' ),
			),
			'export_customers_from_orders' => array(
				'title'     => __( 'Export Customers from Orders', 'woocommerce-jetpack' ),
				'desc'      => __( 'Export Customers (extracted from orders).', 'woocommerce-jetpack' ) . ' ' . __( 'Customers are identified by billing email.', 'woocommerce-jetpack' ),
			),
			'export_orders' => array(
				'title'     => __( 'Export Orders', 'woocommerce-jetpack' ),
				'desc'      => __( 'Export Orders.', 'woocommerce-jetpack' ),
			),
			'export_orders_items' => array(
				'title'     => __( 'Export Orders Items', 'woocommerce-jetpack' ),
				'desc'      => __( 'Export Orders Items.', 'woocommerce-jetpack' ),
			),
			'export_products' => array(
				'title'     => __( 'Export Products', 'woocommerce-jetpack' ),
				'desc'      => __( 'Export Products.', 'woocommerce-jetpack' ),
			),
		) );

		$this->fields_helper = require_once( 'export/class-wcj-fields-helper.php' );

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'export_csv' ) );
			add_action( 'init', array( $this, 'export_xml' ) );
		}
	}

	/**
	 * export.
	 *
	 * @version 2.5.9
	 * @since   2.4.8
	 * @todo    when filtering now using strpos, but other options would be stripos (case-insensitive) or strict equality
	 * @todo    (maybe) do filtering directly in WP_Query
	 */
	function export( $tool_id ) {
		$data = array();
		switch ( $tool_id ) {
			case 'customers':
				$exporter = require_once( 'export/class-wcj-exporter-customers.php' );
				$data = $exporter->export_customers( $this->fields_helper );
				break;
			case 'customers_from_orders':
				$exporter = require_once( 'export/class-wcj-exporter-customers.php' );
				$data = $exporter->export_customers_from_orders( $this->fields_helper );
				break;
			case 'orders':
				$exporter = require_once( 'export/class-wcj-exporter-orders.php' );
				$data = $exporter->export_orders( $this->fields_helper );
				break;
			case 'orders_items':
				$exporter = require_once( 'export/class-wcj-exporter-orders.php' );
				$data = $exporter->export_orders_items( $this->fields_helper );
				break;
			case 'products':
				$exporter = require_once( 'export/class-wcj-exporter-products.php' );
				$data = $exporter->export_products( $this->fields_helper );
				break;
		}
		if ( isset( $_POST['wcj_export_filter_all_columns'] ) && '' != $_POST['wcj_export_filter_all_columns'] ) {
			foreach ( $data as $row_id => $row ) {
				if ( 0 == $row_id ) {
					continue;
				}
				$is_filtered = false;
				foreach ( $row as $cell ) {
					if ( false !== strpos( $cell, $_POST['wcj_export_filter_all_columns'] ) ) {
						$is_filtered = true;
						break;
					}
				}
				if ( ! $is_filtered ) {
					unset( $data[ $row_id ] );
				}
			}
		}
		/* if ( 1 == count( $data ) ) {
			return '<em>' . __( 'No results found.', 'woocommerce-jetpack' ) . '</em>';
		} */
		return $data;
	}

	/**
	 * export_xml.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 * @todo    templates for xml_start, xml_end, xml_item.
	 * @todo    strip_tags (same to Export WooCommerce plugin)
	 */
	function export_xml() {
		if ( isset( $_POST['wcj_export_xml'] ) ) {
			$data = $this->export( $_POST['wcj_export_xml'] );
			if ( is_array( $data ) ) {
				$xml = '';
				$xml .= '<?xml version = "1.0" encoding = "utf-8" ?>' . PHP_EOL . '<root>' . PHP_EOL;
				foreach ( $data as $row_num => $row ) {
					if ( 0 == $row_num ) {
						foreach ( $row as $cell_id => $cell_value ) {
							$cell_ids[ $cell_id ] = sanitize_title_with_dashes( $cell_value );
						}
						continue;
					}
					$xml .= '<item>' . PHP_EOL;
					foreach ( $row as $cell_id => $cell_value ) {
						$xml .= "\t" . '<' . $cell_ids[ $cell_id ] . '>' . $cell_value . '</' . $cell_ids[ $cell_id ] . '>' . PHP_EOL;
					}
					$xml .= '</item>' . PHP_EOL;
				}
				$xml .= '</root>';
				header( "Content-Disposition: attachment; filename=" . $_POST['wcj_export_xml'] . ".xml" );
				header( "Content-Type: Content-Type: text/html; charset=utf-8" );
				header( "Content-Description: File Transfer" );
				header( "Content-Length: " . strlen( $xml ) );
				echo $xml;
				die();
			}
		}
	}

	/**
	 * export_csv.
	 *
	 * @version 2.5.9
	 * @since   2.4.8
	 */
	function export_csv() {
		if ( isset( $_POST['wcj_export'] ) ) {
			$data = $this->export( $_POST['wcj_export'] );
			if ( is_array( $data ) ) {
				$csv = '';
				foreach ( $data as $row ) {
					$csv .= implode( get_option( 'wcj_export_csv_separator', ',' ), $row ) . PHP_EOL;
				}
				if ( 'yes' === get_option( 'wcj_export_csv_add_utf_8_bom', 'yes' ) ) {
					$csv = "\xEF\xBB\xBF" . $csv; // UTF-8 BOM
				}
				header( "Content-Disposition: attachment; filename=" . $_POST['wcj_export'] . ".csv" );
				header( "Content-Type: Content-Type: text/html; charset=utf-8" );
				header( "Content-Description: File Transfer" );
				header( "Content-Length: " . strlen( $csv ) );
				echo $csv;
				die();
			}
		}
	}

	/**
	 * export_filter_fields.
	 *
	 * @version 2.5.9
	 * @since   2.5.5
	 * @todo    filter each field separately
	 */
	function export_filter_fields( $tool_id ) {
		$fields = array();
		switch ( $tool_id ) {
			case 'orders':
				$fields = array(
					'wcj_filter_by_order_billing_country' => __( 'Filter by Billing Country', 'woocommerce-jetpack' ),
					'wcj_filter_by_product_title'         => __( 'Filter by Product Title', 'woocommerce-jetpack' ),
				);
				break;
			case 'orders_items':
				$fields = array(
					'wcj_filter_by_order_billing_country' => __( 'Filter by Billing Country', 'woocommerce-jetpack' ),
				);
				break;
		}
		if ( ! empty( $fields ) ) {
			$data = array();
			foreach( $fields as $field_id => $field_desc ) {
				$field_value = ( isset( $_POST[ $field_id ] ) ) ? $_POST[ $field_id ] : '';
				$data[] = array(
					'<label for="' . $field_id . '">' . $field_desc . '</label>',
					'<input name="' . $field_id . '" id="' . $field_id . '" type="text" value="' . $field_value . '">',
				);
			}
			$data[] = array(
				'<button class="button-primary" type="submit" name="wcj_export_filter" value="' . $tool_id . '">' . __( 'Filter', 'woocommerce-jetpack' ) . '</button>',
				'',
			);
			return wcj_get_table_html( $data, array( 'table_class' => 'widefat', 'table_style' => 'width:50%;min-width:300px;', 'table_heading_type' => 'vertical', ) );
		}
	}

	/**
	 * export_date_fields.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 * @todo    mark current (i.e. active) link (if exists)
	 */
	function export_date_fields( $tool_id ) {
		$current_start_date = ( isset( $_GET['start_date'] ) ? $_GET['start_date'] : '' );
		$current_end_date   = ( isset( $_GET['end_date'] )   ? $_GET['end_date']   : '' );
		$predefined_ranges = array();
		$predefined_ranges[] = '<a href="' . add_query_arg( 'range', 'all_time', remove_query_arg( array( 'start_date', 'end_date' ) ) ) . '">' .
			__( 'All time', 'woocommerce-jetpack' ) . '</a>';
		foreach ( array_merge( wcj_get_reports_standard_ranges(), wcj_get_reports_custom_ranges() ) as $range_id => $range_data ) {
			$link = add_query_arg( array(
				'start_date' => $range_data['start_date'],
				'end_date'   => $range_data['end_date'],
				'range'      => $range_id,
			) );
			$predefined_ranges[] = '<a href="' . $link . '">' . $range_data['title'] . '</a>';
		}
		$predefined_ranges = implode( ' | ', $predefined_ranges );
		$date_input_fields = '<form method="get" action="">' .
			'<input type="hidden" name="page" value="wcj-tools">' .
			'<input type="hidden" name="tab" value="export_' . $tool_id . '">' .
			'<strong>' . __( 'Custom:', 'woocommerce-jetpack' ) . '</strong>' . ' ' .
			'<input name="start_date" id="start_date" type="text" display="date" value="' . $current_start_date . '">' .
			'<strong>' . ' - ' . '</strong>' .
			'<input name="end_date" id="end_date" type="text" display="date" value="' . $current_end_date . '">' .
			' ' .
			'<button class="button-primary" name="range" id="range" type="submit" value="custom">' . __( 'Go', 'woocommerce-jetpack' ) . '</button>' .
		'</form>';
		return $predefined_ranges . '<br>' . $date_input_fields;
	}

	/**
	 * create_export_tool.
	 *
	 * @version 3.0.0
	 * @since   2.4.8
	 */
	function create_export_tool( $tool_id ) {
		echo $this->get_tool_header_html( 'export_' . $tool_id );
		echo '<p>' . $this->export_date_fields( $tool_id ) . '</p>';
		if ( ! isset( $_GET['range'] ) ) {
			return;
		}
		echo '<form method="post" action="">';
		echo '<p>' . $this->export_filter_fields( $tool_id ) . '</p>';
		echo '<p>';
		echo '<button class="button-primary" type="submit" name="wcj_export" value="' . $tool_id . '">' . __( 'Download CSV', 'woocommerce-jetpack' ) . '</button>';
		echo ' ';
		echo '<button class="button-primary" type="submit" name="wcj_export_xml" value="' . $tool_id . '">' . __( 'Download XML', 'woocommerce-jetpack' ) . '</button>';
		echo '<button style="float:right;margin-right:10px;" class="button-primary" type="submit" name="wcj_export_filter" value="' . $tool_id . '">' . __( 'Filter by All Fields', 'woocommerce-jetpack' ) . '</button>';
		echo '<input style="float:right;margin-right:10px;" type="text" name="wcj_export_filter_all_columns" value="' . ( isset( $_POST['wcj_export_filter_all_columns'] ) ? $_POST['wcj_export_filter_all_columns'] : '' ) . '">';
		echo '</p>';
		echo '</form>';
		$data = $this->export( $tool_id );
		echo ( is_array( $data ) ) ? wcj_get_table_html( $data, array( 'table_class' => 'widefat striped' ) ) : $data;
	}

	/**
	 * create_export_customers_tool.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function create_export_customers_tool() {
		$this->create_export_tool( 'customers' );
	}

	/**
	 * create_export_orders_tool.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function create_export_orders_tool() {
		$this->create_export_tool( 'orders' );
	}

	/**
	 * create_export_orders_items_tool.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function create_export_orders_items_tool() {
		$this->create_export_tool( 'orders_items' );
	}

	/**
	 * create_export_products_tool.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function create_export_products_tool() {
		$this->create_export_tool( 'products' );
	}

	/**
	 * create_export_customers_from_orders_tool.
	 *
	 * @version 2.4.8
	 * @since   2.3.9
	 */
	function create_export_customers_from_orders_tool() {
		$this->create_export_tool( 'customers_from_orders' );
	}

}

endif;

return new WCJ_Export_Import();

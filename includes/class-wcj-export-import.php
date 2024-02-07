<?php
/**
 * Booster for WooCommerce - Module - Export
 *
 * @version 7.1.6
 * @since   2.5.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Export_Import' ) ) :
	/**
	 * WCJ_Currencies.
	 */
	class WCJ_Export_Import extends WCJ_Module {

		/**
		 * The module fields_helper
		 *
		 * @var varchar $fields_helper Module.
		 */
		public $fields_helper;
		/**
		 * Constructor.
		 *
		 * @version 5.4.0
		 * @since   2.5.4
		 * @todo    [feature] import products, customers and (maybe) orders
		 */
		public function __construct() {

			$this->id         = 'export';
			$this->short_desc = __( 'Export', 'woocommerce-jetpack' );
			$this->desc       = __( 'WooCommerce export tools. Additional export fields (1 field allowed in free version).', 'woocommerce-jetpack' );
			/* translators: %s: translation added */
			$this->desc_pro  = sprintf( __( 'WooCommerce export tools. Check and use the shortcodes from <a href="%s" target=\'blank\'>here</a>', 'woocommerce-jetpack' ), 'https://booster.io/category/shortcodes/' );
			$this->link_slug = 'woocommerce-export-tools';
			parent::__construct();

			$this->add_tools(
				array(
					'export_customers'             => array(
						'title' => __( 'Export Customers', 'woocommerce-jetpack' ),
						'desc'  => __( 'Export Customers.', 'woocommerce-jetpack' ),
					),
					'export_customers_from_orders' => array(
						'title' => __( 'Export Customers from Orders', 'woocommerce-jetpack' ),
						'desc'  => __( 'Export Customers (extracted from orders).', 'woocommerce-jetpack' ) . ' ' .
							__( 'Customers are identified by billing email.', 'woocommerce-jetpack' ),
					),
					'export_orders'                => array(
						'title' => __( 'Export Orders', 'woocommerce-jetpack' ),
						'desc'  => __( 'Export Orders.', 'woocommerce-jetpack' ),
					),
					'export_orders_items'          => array(
						'title' => __( 'Export Orders Items', 'woocommerce-jetpack' ),
						'desc'  => __( 'Export Orders Items.', 'woocommerce-jetpack' ),
					),
					'export_products'              => array(
						'title' => __( 'Export Products', 'woocommerce-jetpack' ),
						'desc'  => __( 'Export Products.', 'woocommerce-jetpack' ),
					),
				)
			);

			$this->fields_helper = require_once 'export/class-wcj-export-fields-helper.php';

			if ( $this->is_enabled() ) {
				add_action( 'init', array( $this, 'export_csv' ) );
				add_action( 'init', array( $this, 'export_xml' ) );
			}
		}

		/**
		 * Export.
		 *
		 * @version 7.1.4
		 * @since   2.4.8
		 * @todo    [dev] when filtering now using `strpos`, but other options would be `stripos` (case-insensitive) or strict equality
		 * @todo    [dev] (maybe) do filtering directly in WP_Query
		 * @param int $tool_id defines the tool_id.
		 */
		public function export( $tool_id ) {
			$data = array();
			switch ( $tool_id ) {
				case 'customers':
					$exporter = require_once 'export/class-wcj-exporter-customers.php';
					$data     = $exporter->export_customers( $this->fields_helper );
					break;
				case 'customers_from_orders':
					$exporter = require_once 'export/class-wcj-exporter-customers.php';
					if ( true === wcj_is_hpos_enabled() ) {
						$data = $exporter->export_customers_from_orders_hpos( $this->fields_helper );
					} else {
						$data = $exporter->export_customers_from_orders( $this->fields_helper );
					}
					break;
				case 'orders':
					$exporter = require_once 'export/class-wcj-exporter-orders.php';
					if ( true === wcj_is_hpos_enabled() ) {
						$data = $exporter->export_orders_hpos( $this->fields_helper );
					} else {
						$data = $exporter->export_orders( $this->fields_helper );
					}
					break;
				case 'orders_items':
					$exporter = require_once 'export/class-wcj-exporter-orders.php';
					if ( true === wcj_is_hpos_enabled() ) {
						$data = $exporter->export_orders_items_hpos( $this->fields_helper );
					} else {
						$data = $exporter->export_orders_items( $this->fields_helper );
					}
					break;
				case 'products':
					$exporter = require_once 'export/class-wcj-exporter-products.php';
					$data     = $exporter->export_products( $this->fields_helper );
					break;
			}
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			if ( $wpnonce && isset( $_POST['wcj_export_filter_all_columns'] ) && '' !== $_POST['wcj_export_filter_all_columns'] ) {
				foreach ( $data as $row_id => $row ) {
					if ( 0 === $row_id ) {
						continue;
					}
					$is_filtered = false;
					foreach ( $row as $cell ) {
						if ( false !== strpos( $cell, sanitize_text_field( wp_unslash( $_POST['wcj_export_filter_all_columns'] ) ) ) ) {
							$is_filtered = true;
							break;
						}
					}
					if ( ! $is_filtered ) {
						unset( $data[ $row_id ] );
					}
				}
			}
			return $data;
		}

		/**
		 * Export_xml.
		 *
		 * @version 6.0.0
		 * @since   2.5.9
		 * @todo    [dev] templates for xml_start, xml_end, xml_item.
		 * @todo    [dev] `strip_tags`
		 */
		public function export_xml() {
			global $wp_filesystem;
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			if ( $wpnonce && isset( $_POST['wcj_export_xml'] ) ) {
				$data = $this->export( sanitize_text_field( wp_unslash( $_POST['wcj_export_xml'] ) ) );
				if ( is_array( $data ) ) {
					$xml  = '';
					$xml .= '<?xml version = "1.0" encoding = "utf-8" ?>' . PHP_EOL . '<root>' . PHP_EOL;
					foreach ( $data as $row_num => $row ) {
						if ( 0 === $row_num ) {
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

					$file_name = sanitize_text_field( wp_unslash( $_POST['wcj_export_xml'] ) ) . '.xml';
					$file_path = wp_tempnam();
					$wp_filesystem->put_contents( $file_path, $xml, FS_CHMOD_FILE );
					WC_Download_Handler::download_file_force( $file_path, $file_name );
					die();
				}
			}
		}

		/**
		 * Smart_format_fields.
		 *
		 * @see https://stackoverflow.com/a/4617967/1193038
		 *
		 * @version 5.6.2
		 * @since   5.1.0
		 *
		 * @param array $row defines the row.
		 *
		 * @return array
		 */
		public function smart_format_fields( $row ) {
			if ( 'no' === wcj_get_option( 'wcj_export_csv_smart_formatting', 'no' ) ) {
				return $row;
			}
			$row = array_map(
				function ( $item ) {
					$item = str_replace( '"', '""', $item );
					if (
					false !== strpos( $item, '"' )
					|| false !== strpos( $item, ',' )
					) {
						$item = '"' . $item . '"';
					}
					return $item;
				},
				$row
			);
			return $row;
		}

		/**
		 * Export_csv.
		 *
		 * @version 5.6.8
		 * @since   2.4.8
		 */
		public function export_csv() {
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			if ( $wpnonce && isset( $_POST['wcj_export'] ) ) {
				$data = $this->export( sanitize_text_field( wp_unslash( $_POST['wcj_export'] ) ) );
				if ( is_array( $data ) ) {
					$csv = '';
					foreach ( $data as $row ) {
						$row  = $this->smart_format_fields( $row );
						$row  = implode( ',', $row );
						$row  = trim( preg_replace( '/\s+/', ' ', $row ) );
						$row  = explode( ',', $row );
						$csv .= implode( wcj_get_option( 'wcj_export_csv_separator', ',' ), $row ) . PHP_EOL;
					}
					if ( 'yes' === wcj_get_option( 'wcj_export_csv_add_utf_8_bom', 'yes' ) ) {
						$csv = "\xEF\xBB\xBF" . $csv; // UTF-8 BOM.
					}
					header( 'Content-Disposition: attachment; filename=' . sanitize_text_field( wp_unslash( $_POST['wcj_export'] ) ) . '.csv' );
					header( 'Content-Type: Content-Type: text/html; charset=utf-8' );
					header( 'Content-Description: File Transfer' );
					header( 'Content-Length: ' . strlen( $csv ) );
					echo wp_kses_post( $csv );
					die();
				}
			}
		}

		/**
		 * Export_filter_fields.
		 *
		 * @version 5.6.8
		 * @since   2.5.5
		 * @todo    [dev] filter each field separately
		 * @param int $tool_id defines the tool_id.
		 */
		public function export_filter_fields( $tool_id ) {
			$fields  = array();
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			switch ( $tool_id ) {
				case 'orders':
					$fields = array(
						'wcj_filter_by_order_billing_country' => __( 'Filter by Billing Country', 'woocommerce-jetpack' ),
						'wcj_filter_by_product_title' => __( 'Filter by Product Title', 'woocommerce-jetpack' ),
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
				foreach ( $fields as $field_id => $field_desc ) {
					$field_value = ( $wpnonce && isset( $_POST[ $field_id ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ) : '';
					$data[]      = array(
						'<label for="' . $field_id . '">' . $field_desc . '</label>',
						'<input name="' . $field_id . '" id="' . $field_id . '" type="text" value="' . $field_value . '">',
					);
				}
				$data[] = array(
					'<button class="button-primary" type="submit" name="wcj_export_filter" value="' . $tool_id . '">' . __( 'Filter', 'woocommerce-jetpack' ) . '</button>',
					'',
				);
				return wcj_get_table_html(
					$data,
					array(
						'table_class'        => 'widefat',
						'table_style'        => 'width:50%;min-width:300px;',
						'table_heading_type' => 'vertical',
					)
				);
			}
		}

		/**
		 * Export_date_fields.
		 *
		 * @version 5.6.8
		 * @since   3.0.0
		 * @todo    [dev] maybe make `$dateformat` optional
		 * @todo    [dev] mark current (i.e. active) link (if exists)
		 * @param int $tool_id defines the tool_id.
		 */
		public function export_date_fields( $tool_id ) {
			$wpnonce             = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			$current_start_date  = ( $wpnonce && isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '' );
			$current_end_date    = ( $wpnonce && isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '' );
			$predefined_ranges   = array();
			$predefined_ranges[] = '<a href="' . esc_url( add_query_arg( 'range', 'all_time', remove_query_arg( array( 'start_date', 'end_date' ) ) ) ) . '">' .
			__( 'All time', 'woocommerce-jetpack' ) . '</a>';
			foreach ( array_merge( wcj_get_reports_standard_ranges(), wcj_get_reports_custom_ranges() ) as $range_id => $range_data ) {
				$link                = esc_url(
					add_query_arg(
						array(
							'start_date' => $range_data['start_date'],
							'end_date'   => $range_data['end_date'],
							'range'      => $range_id,
						)
					)
				);
				$predefined_ranges[] = '<a href="' . $link . '">' . $range_data['title'] . '</a>';

			}
			$predefined_ranges = implode( ' | ', $predefined_ranges );
			$dateformat        = ' dateformat="yy-mm-dd"';
			$date_input_fields = '<form method="get" action="">' .
			'<input type="hidden" name="page" value="wcj-tools">' .
			'<input type="hidden" name="tab" value="export_' . $tool_id . '">' .
			'<strong>' . __( 'Custom:', 'woocommerce-jetpack' ) . '</strong> ' .
			'<input name="start_date" id="start_date" type="text" display="date"' . $dateformat . ' value="' . $current_start_date . '">' .
			'<strong> - </strong>' .
			'<input name="end_date" id="end_date" type="text" display="date"' . $dateformat . ' value="' . $current_end_date . '">' .
			' ' .
			'<input name="wcj_tools_nonce" id="wcj_tools_nonce" type="hidden" value="' . wp_create_nonce( 'wcj_tools' ) . '">' .
			'<button class="button-primary" name="range" id="range" type="submit" value="custom">' . __( 'Go', 'woocommerce-jetpack' ) . '</button>' .
			'</form>';
			return $predefined_ranges . '<br>' . $date_input_fields;
		}

		/**
		 * Create_export_tool.
		 *
		 * @version 6.0.0
		 * @since   2.4.8
		 * @param int $tool_id defines the tool_id.
		 */
		public function create_export_tool( $tool_id ) {
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			if ( ! $wpnonce ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wcj-tools' ) );
				exit;
			}
			echo '<div class="wcj-setting-jetpack-body wcj_tools_cnt_main">';
			echo wp_kses_post( $this->get_tool_header_html( 'export_' . $tool_id ) );
			echo '<p>' . wp_kses_post( $this->export_date_fields( $tool_id ) ) . '</p>';
			if ( ! isset( $_GET['range'] ) ) {
				echo '</div>';
				return;
			}
			echo '<form method="post" action="">';
			echo '<p>' . wp_kses_post( $this->export_filter_fields( $tool_id ) ) . '</p>';
			echo '<p>';
			echo '<button class="button-primary" type="submit" name="wcj_export" value="' . wp_kses_post( $tool_id ) . '">' . wp_kses_post( 'Download CSV', 'woocommerce-jetpack' ) . '</button>';
			echo ' ';
			echo '<button class="button-primary" type="submit" name="wcj_export_xml" value="' . wp_kses_post( $tool_id ) . '">' .
			wp_kses_post( 'Download XML', 'woocommerce-jetpack' ) . '</button>';
			echo '<button style="float:right;margin-right:10px;" class="button-primary" type="submit" name="wcj_export_filter" value="' . wp_kses_post( $tool_id ) . '">' .
			wp_kses_post( 'Filter by All Fields', 'woocommerce-jetpack' ) . '</button>';
			echo '<input style="float:right;margin-right:10px;" type="text" name="wcj_export_filter_all_columns" value="' .
			( isset( $_POST['wcj_export_filter_all_columns'] ) ? wp_kses_post( sanitize_text_field( wp_unslash( $_POST['wcj_export_filter_all_columns'] ) ) ) : '' ) . '">';
			echo '</p>';
			echo '</form>';
			$data = $this->export( $tool_id );
			echo is_array( $data ) ? wp_kses_post( wcj_get_table_html( $data, array( 'table_class' => 'widefat striped' ) ) ) : wp_kses_post( $data );
			echo '</div>';
		}

		/**
		 * Create_export_customers_tool.
		 *
		 * @version 2.4.8
		 * @since   2.4.8
		 */
		public function create_export_customers_tool() {
			$this->create_export_tool( 'customers' );
		}

		/**
		 * Create_export_orders_tool.
		 *
		 * @version 2.4.8
		 * @since   2.4.8
		 */
		public function create_export_orders_tool() {
			$this->create_export_tool( 'orders' );
		}

		/**
		 * Create_export_orders_items_tool.
		 *
		 * @version 2.5.9
		 * @since   2.5.9
		 */
		public function create_export_orders_items_tool() {
			$this->create_export_tool( 'orders_items' );
		}

		/**
		 * Create_export_products_tool.
		 *
		 * @version 2.5.3
		 * @since   2.5.3
		 */
		public function create_export_products_tool() {
			$this->create_export_tool( 'products' );
		}

		/**
		 * Create_export_customers_from_orders_tool.
		 *
		 * @version 2.4.8
		 * @since   2.3.9
		 */
		public function create_export_customers_from_orders_tool() {
			$this->create_export_tool( 'customers_from_orders' );
		}

	}

endif;

return new WCJ_Export_Import();

<?php
/**
 * WooCommerce Jetpack Export Import
 *
 * The WooCommerce Jetpack Export Import class.
 *
 * @version 2.5.9
 * @since   2.5.4
 * @author  Algoritmika Ltd.
 * @todo    import products, (maybe) orders, customers tools;
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Export_Import' ) ) :

class WCJ_Export_Import extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.9
	 * @since   2.5.4
	 */
	public function __construct() {

		$this->id         = 'export';
		$this->short_desc = __( 'Export', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce export tools.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-export-tools/';
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
	 * @todo    filter each field.
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
	 * create_export_tool.
	 *
	 * @version 2.5.9
	 * @since   2.4.8
	 */
	function create_export_tool( $tool_id ) {
		$data = $this->export( $tool_id );
		echo $this->get_tool_header_html( 'export_' . $tool_id );
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

	/**
	 * get_settings.
	 *
	 * @version 2.5.9
	 * @since   2.5.4
	 * @todo    add "Additional Export Fields" for "Customers from Orders" and (maybe) "Customers"
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Export Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_export_options',
			),
			array(
				'title'    => __( 'CSV Separator', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_csv_separator',
				'default'  => ',',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'UTF-8 BOM', 'woocommerce-jetpack' ),
				'desc'     => __( 'Add', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Add UTF-8 BOM sequence', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_csv_add_utf_8_bom',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_export_options',
			),
			array(
				'title'    => __( 'Export Orders Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_export_orders_options',
			),
			array(
				'title'    => __( 'Export Orders Fields', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_orders_fields',
				'default'  => $this->fields_helper->get_order_export_default_fields_ids(),
				'type'     => 'multiselect',
				'options'  => $this->fields_helper->get_order_export_fields(),
				'css'      => 'height:300px;',
			),
			array(
				'title'    => __( 'Additional Export Orders Fields', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_orders_fields_additional_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ?
						apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '0', )
				),
			),
		);
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_export_orders_fields_additional_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
					'id'       => 'wcj_export_orders_fields_additional_enabled_' . $i,
					'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
					'type'     => 'checkbox',
					'default'  => 'no',
				),
				array(
					'desc'     => __( 'Title', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_orders_fields_additional_title_' . $i,
					'type'     => 'text',
					'default'  => '',
				),
				array(
					'desc'     => __( 'Type', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_orders_fields_additional_type_' . $i,
					'type'     => 'select',
					'default'  => 'meta',
					'options'  => array(
						'meta'      => __( 'Order Meta', 'woocommerce-jetpack' ),
						'shortcode' => __( 'Order Shortcode', 'woocommerce-jetpack' ),
					),
				),
				array(
					'desc'     => __( 'Value', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter order meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
						' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Orders shortcodes here.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_orders_fields_additional_value_' . $i,
					'type'     => 'text',
					'default'  => '',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_export_orders_options',
			),
			array(
				'title'    => __( 'Export Orders Items Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_export_orders_items_options',
			),
			array(
				'title'    => __( 'Export Orders Items Fields', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_orders_items_fields',
				'default'  => $this->fields_helper->get_order_items_export_default_fields_ids(),
				'type'     => 'multiselect',
				'options'  => $this->fields_helper->get_order_items_export_fields(),
				'css'      => 'height:300px;',
			),
			array(
				'title'    => __( 'Additional Export Orders Items Fields', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_orders_items_fields_additional_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ?
						apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '0', )
				),
			),
		) );
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_export_orders_items_fields_additional_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
					'id'       => 'wcj_export_orders_items_fields_additional_enabled_' . $i,
					'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
					'type'     => 'checkbox',
					'default'  => 'no',
				),
				array(
					'desc'     => __( 'Title', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_orders_items_fields_additional_title_' . $i,
					'type'     => 'text',
					'default'  => '',
				),
				array(
					'desc'     => __( 'Type', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_orders_items_fields_additional_type_' . $i,
					'type'     => 'select',
					'default'  => 'meta',
					'options'  => array(
						'meta'              => __( 'Order Meta', 'woocommerce-jetpack' ),
						'shortcode'         => __( 'Order Shortcode', 'woocommerce-jetpack' ),
						'meta_product'      => __( 'Product Meta', 'woocommerce-jetpack' ),
						'shortcode_product' => __( 'Product Shortcode', 'woocommerce-jetpack' ),
					),
				),
				array(
					'desc'     => __( 'Value', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter order/product meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
						' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Orders/Products shortcodes here.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_orders_items_fields_additional_value_' . $i,
					'type'     => 'text',
					'default'  => '',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_export_orders_items_options',
			),
			array(
				'title'    => __( 'Export Products Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_export_products_options',
			),
			array(
				'title'    => __( 'Export Products Fields', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_products_fields',
				'default'  => $this->fields_helper->get_product_export_default_fields_ids(),
				'type'     => 'multiselect',
				'options'  => $this->fields_helper->get_product_export_fields(),
				'css'      => 'height:300px;',
			),
			array(
				'title'    => __( 'Additional Export Products Fields', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_products_fields_additional_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ?
						apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '0', )
				),
			),
		) );
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_export_products_fields_additional_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
					'id'       => 'wcj_export_products_fields_additional_enabled_' . $i,
					'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
					'type'     => 'checkbox',
					'default'  => 'no',
				),
				array(
					'desc'     => __( 'Title', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_products_fields_additional_title_' . $i,
					'type'     => 'text',
					'default'  => '',
				),
				array(
					'desc'     => __( 'Type', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_products_fields_additional_type_' . $i,
					'type'     => 'select',
					'default'  => 'meta',
					'options'  => array(
						'meta'      => __( 'Product Meta', 'woocommerce-jetpack' ),
						'shortcode' => __( 'Product Shortcode', 'woocommerce-jetpack' ),
					),
				),
				array(
					'desc'     => __( 'Value', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter product meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
						' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Products shortcodes here.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_export_products_fields_additional_value_' . $i,
					'type'     => 'text',
					'default'  => '',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_export_products_options',
			),
			array(
				'title'    => __( 'Export Customers Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_export_customers_options',
			),
			array(
				'title'    => __( 'Export Customers Fields', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_customers_fields',
				'default'  => $this->fields_helper->get_customer_export_default_fields_ids(),
				'type'     => 'multiselect',
				'options'  => $this->fields_helper->get_customer_export_fields(),
				'css'      => 'height:150px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_export_customers_options',
			),
			array(
				'title'    => __( 'Export Customers from Orders Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_export_customers_from_orders_options',
			),
			array(
				'title'    => __( 'Export Customers from Orders Fields', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_customers_from_orders_fields',
				'default'  => $this->fields_helper->get_customer_from_order_export_default_fields_ids(),
				'type'     => 'multiselect',
				'options'  => $this->fields_helper->get_customer_from_order_export_fields(),
				'css'      => 'height:150px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_export_customers_from_orders_options',
			),
		) );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Export_Import();

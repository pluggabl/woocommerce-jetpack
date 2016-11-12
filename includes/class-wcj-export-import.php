<?php
/**
 * WooCommerce Jetpack Export Import
 *
 * The WooCommerce Jetpack Export Import class.
 *
 * @version 2.5.7
 * @since   2.5.4
 * @author  Algoritmika Ltd.
 * @todo    import products (maybe orders, customers) tool(s);
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Export_Import' ) ) :

class WCJ_Export_Import extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.4
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
				'desc'      => __( 'Export Customers (extracted from orders).', 'woocommerce-jetpack' ),
			),
			'export_orders' => array(
				'title'     => __( 'Export Orders', 'woocommerce-jetpack' ),
				'desc'      => __( 'Export Orders.', 'woocommerce-jetpack' ),
			),
			'export_products' => array(
				'title'     => __( 'Export Products', 'woocommerce-jetpack' ),
				'desc'      => __( 'Export Products.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'export_csv' ) );
		}
	}

	/**
	 * export.
	 *
	 * @version 2.5.3
	 * @since   2.4.8
	 */
	function export( $tool_id ) {
		$data = array();
		switch ( $tool_id ) {
			case 'customers':
				$data = $this->export_customers();
				break;
			case 'customers_from_orders':
				$data = $this->export_customers_from_orders();
				break;
			case 'orders':
				$data = $this->export_orders();
				break;
			case 'products':
				$data = $this->export_products();
				break;
		}
		return $data;
	}

	/**
	 * export_csv.
	 *
	 * @version 2.5.7
	 * @since   2.4.8
	 */
	function export_csv() {
		if ( isset( $_POST['wcj_export'] ) ) {
			$data = $this->export( $_POST['wcj_export'] );
			$csv = '';
			foreach ( $data as $row ) {
				$csv .= implode( get_option( 'wcj_export_csv_separator', ',' ), $row ) . PHP_EOL;
			}
			header( "Content-Disposition: attachment; filename=" . $_POST['wcj_export'] . ".csv" );
			header( "Content-Type: Content-Type: text/html; charset=utf-8" );
			header( "Content-Description: File Transfer" );
			header( "Content-Length: " . strlen( $csv ) );
			if ( 'yes' === get_option( 'wcj_export_csv_add_utf_8_bom', 'yes' ) ) {
				echo "\xEF\xBB\xBF"; // UTF-8 BOM
			}
			echo $csv;
			die();
		}
	}

	/**
	 * export_filter_fields.
	 *
	 * @version 2.5.6
	 * @since   2.5.5
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
	 * get_order_export_fields.
	 *
	 * @version 2.5.7
	 * @since   2.5.6
	 */
	function get_order_export_fields() {
		return array(
			'order-id'                         => __( 'Order ID', 'woocommerce-jetpack' ),
			'order-number'                     => __( 'Order Number', 'woocommerce-jetpack' ),
			'order-status'                     => __( 'Order Status', 'woocommerce-jetpack' ),
			'order-date'                       => __( 'Order Date', 'woocommerce-jetpack' ),
			'order-time'                       => __( 'Order Time', 'woocommerce-jetpack' ),
			'order-item-count'                 => __( 'Order Item Count', 'woocommerce-jetpack' ),
			'order-items'                      => __( 'Order Items', 'woocommerce-jetpack' ),
			'order-items-product-input-fields' => __( 'Order Items Product Input Fields', 'woocommerce-jetpack' ),
			'order-currency'                   => __( 'Order Currency', 'woocommerce-jetpack' ),
			'order-total'                      => __( 'Order Total', 'woocommerce-jetpack' ),
			'order-total-tax'                  => __( 'Order Total Tax', 'woocommerce-jetpack' ),
			'order-payment-method'             => __( 'Order Payment Method', 'woocommerce-jetpack' ),
			'order-notes'                      => __( 'Order Notes', 'woocommerce-jetpack' ),
			'billing-first-name'               => __( 'Billing First Name', 'woocommerce-jetpack' ),
			'billing-last-name'                => __( 'Billing Last Name', 'woocommerce-jetpack' ),
			'billing-company'                  => __( 'Billing Company', 'woocommerce-jetpack' ),
			'billing-address-1'                => __( 'Billing Address 1', 'woocommerce-jetpack' ),
			'billing-address-2'                => __( 'Billing Address 2', 'woocommerce-jetpack' ),
			'billing-city'                     => __( 'Billing City', 'woocommerce-jetpack' ),
			'billing-state'                    => __( 'Billing State', 'woocommerce-jetpack' ),
			'billing-postcode'                 => __( 'Billing Postcode', 'woocommerce-jetpack' ),
			'billing-country'                  => __( 'Billing Country', 'woocommerce-jetpack' ),
			'billing-phone'                    => __( 'Billing Phone', 'woocommerce-jetpack' ),
			'billing-email'                    => __( 'Billing Email', 'woocommerce-jetpack' ),
			'shipping-first-name'              => __( 'Shipping First Name', 'woocommerce-jetpack' ),
			'shipping-last-name'               => __( 'Shipping Last Name', 'woocommerce-jetpack' ),
			'shipping-company'                 => __( 'Shipping Company', 'woocommerce-jetpack' ),
			'shipping-address-1'               => __( 'Shipping Address 1', 'woocommerce-jetpack' ),
			'shipping-address-2'               => __( 'Shipping Address 2', 'woocommerce-jetpack' ),
			'shipping-city'                    => __( 'Shipping City', 'woocommerce-jetpack' ),
			'shipping-state'                   => __( 'Shipping State', 'woocommerce-jetpack' ),
			'shipping-postcode'                => __( 'Shipping Postcode', 'woocommerce-jetpack' ),
			'shipping-country'                 => __( 'Shipping Country', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * get_order_export_default_fields_ids.
	 *
	 * @version 2.5.7
	 * @since   2.5.6
	 */
	function get_order_export_default_fields_ids() {
		return array(
			'order-id',
			'order-number',
			'order-status',
			'order-date',
			'order-time',
			'order-item-count',
			'order-items',
			'order-currency',
			'order-total',
			'order-total-tax',
			'order-payment-method',
			'order-notes',
			'billing-first-name',
			'billing-last-name',
			'billing-company',
			'billing-address-1',
			'billing-address-2',
			'billing-city',
			'billing-state',
			'billing-postcode',
			'billing-country',
			'billing-phone',
			'billing-email',
			'shipping-first-name',
			'shipping-last-name',
			'shipping-company',
			'shipping-address-1',
			'shipping-address-2',
			'shipping-city',
			'shipping-state',
			'shipping-postcode',
			'shipping-country',
		);
	}

	/**
	 * create_export_tool.
	 *
	 * @version 2.5.6
	 * @since   2.4.8
	 */
	function create_export_tool( $tool_id ) {
		$data = $this->export( $tool_id );
		echo '<form method="post" action="">';
		echo '<p>' . $this->export_filter_fields( $tool_id ) . '</p>';
		echo '<p><button class="button-primary" type="submit" name="wcj_export" value="' . $tool_id . '">' . __( 'Download CSV', 'woocommerce-jetpack' ) . '</button></p>';
		echo '</form>';
		echo wcj_get_table_html( $data, array( 'table_class' => 'widefat striped' ) );
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
	 * export_customers.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function export_customers() {
		$data = array();
		$data[] = array(
			__( 'Customer ID', 'woocommerce-jetpack' ),
			__( 'Customer Email', 'woocommerce-jetpack' ),
			__( 'Customer First Name', 'woocommerce-jetpack' ),
			__( 'Customer Last Name', 'woocommerce-jetpack' ),
		);
		$customers = get_users( 'role=customer' );
		foreach ( $customers as $customer ) {
			$data[] = array( $customer->ID, $customer->user_email, $customer->first_name, $customer->last_name, );
		}
		return $data;
	}

	/**
	 * export_orders.
	 *
	 * @version 2.5.7
	 * @since   2.4.8
	 */
	function export_orders() {
		$all_fields = $this->get_order_export_fields();
		$fields_ids = get_option( 'wcj_export_orders_fields', $this->get_order_export_default_fields_ids() );
		$titles = array();
		foreach( $fields_ids as $field_id ) {
			$titles[] = $all_fields[ $field_id ];
		}
		$data = array();
		$data[] = $titles;
		$offset = 0;
		$block_size = 1024;
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) break;
			foreach ( $loop_orders->posts as $order_id ) {
				$order = wc_get_order( $order_id );

				if ( isset( $_POST['wcj_filter_by_order_billing_country'] ) && '' != $_POST['wcj_filter_by_order_billing_country'] ) {
					if ( $order->billing_country != $_POST['wcj_filter_by_order_billing_country'] ) {
						continue;
					}
				}

				$filter_by_product_title = true;
				if ( isset( $_POST['wcj_filter_by_product_title'] ) && '' != $_POST['wcj_filter_by_product_title'] ) {
					$filter_by_product_title = false;
				}
				$items = array();
				$items_product_input_fields = array();
				foreach ( $order->get_items() as $item ) {
					$items[] = $item['name'];
					$item_product_input_fields = wcj_get_product_input_fields( $item );
					if ( '' != $item_product_input_fields ) {
						$items_product_input_fields[] = $item_product_input_fields;
					}
					if ( ! $filter_by_product_title ) {
//						if ( $item['name'] === $_POST['wcj_filter_by_product_title'] ) {
						if ( false !== strpos( $item['name'], $_POST['wcj_filter_by_product_title'] ) ) {
							$filter_by_product_title = true;
						}
					}
				}
				$items = implode( ' / ', $items );
				$items_product_input_fields = implode( ' / ', $items_product_input_fields );
				if ( ! $filter_by_product_title ) {
					continue;
				}

				$row = array();
				foreach( $fields_ids as $field_id ) {
					switch ( $field_id ) {
						case 'order-id':
							$row[] = $order_id;
							break;
						case 'order-number':
							$row[] = $order->get_order_number();
							break;
						case 'order-status':
							$row[] = $order->get_status();
							break;
						case 'order-date':
							$row[] = get_the_date( get_option( 'date_format' ), $order_id );
							break;
						case 'order-time':
							$row[] = get_the_time( get_option( 'time_format' ), $order_id );
							break;
						case 'order-item-count':
							$row[] = $order->get_item_count();
							break;
						case 'order-items':
							$row[] = $items;
							break;
						case 'order-items-product-input-fields':
							$row[] = $items_product_input_fields;
							break;
						case 'order-currency':
							$row[] = $order->get_order_currency();
							break;
						case 'order-total':
							$row[] = $order->get_total();
							break;
						case 'order-total-tax':
							$row[] = $order->get_total_tax();
							break;
						case 'order-payment-method':
							$row[] = $order->payment_method_title;
							break;
						case 'order-notes':
							$row[] = $order->customer_note;
							break;
						case 'billing-first-name':
							$row[] = $order->billing_first_name;
							break;
						case 'billing-last-name':
							$row[] = $order->billing_last_name;
							break;
						case 'billing-company':
							$row[] = $order->billing_company;
							break;
						case 'billing-address-1':
							$row[] = $order->billing_address_1;
							break;
						case 'billing-address-2':
							$row[] = $order->billing_address_2;
							break;
						case 'billing-city':
							$row[] = $order->billing_city;
							break;
						case 'billing-state':
							$row[] = $order->billing_state;
							break;
						case 'billing-postcode':
							$row[] = $order->billing_postcode;
							break;
						case 'billing-country':
							$row[] = $order->billing_country;
							break;
						case 'billing-phone':
							$row[] = $order->billing_phone;
							break;
						case 'billing-email':
							$row[] = $order->billing_email;
							break;
						case 'shipping-first-name':
							$row[] = $order->shipping_first_name;
							break;
						case 'shipping-last-name':
							$row[] = $order->shipping_last_name;
							break;
						case 'shipping-company':
							$row[] = $order->shipping_company;
							break;
						case 'shipping-address-1':
							$row[] = $order->shipping_address_1;
							break;
						case 'shipping-address-2':
							$row[] = $order->shipping_address_2;
							break;
						case 'shipping-city':
							$row[] = $order->shipping_city;
							break;
						case 'shipping-state':
							$row[] = $order->shipping_state;
							break;
						case 'shipping-postcode':
							$row[] = $order->shipping_postcode;
							break;
						case 'shipping-country':
							$row[] = $order->shipping_country;
							break;
					}
				}
				$data[] = $row;
			}
			$offset += $block_size;
		}
		return $data;
	}

	/**
	 * export_products.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function export_products() {
		$data = array();
		$data[] = array(
			__( 'Product ID', 'woocommerce-jetpack' ),
			__( 'Name', 'woocommerce-jetpack' ),
			__( 'SKU', 'woocommerce-jetpack' ),
			__( 'Stock', 'woocommerce-jetpack' ),
			__( 'Regular Price', 'woocommerce-jetpack' ),
			__( 'Sale Price', 'woocommerce-jetpack' ),
			__( 'Price', 'woocommerce-jetpack' ),
			__( 'Type', 'woocommerce-jetpack' ),
//			__( 'Attributes', 'woocommerce-jetpack' ),
		);
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$product_id = $loop->post->ID;
				$_product = wc_get_product( $product_id );
				$data[] = array(
					$product_id,
					$_product->get_title(),
					$_product->get_sku(),
					$_product->/* get_total_stock() */get_stock_quantity(),
					$_product->get_regular_price(),
					$_product->get_sale_price(),
					( $_product->is_type( 'variable' ) || $_product->is_type( 'grouped' ) ? '' : $_product->get_price() ),
					$_product->get_type(),
//					( ! empty( $_product->get_attributes() ) ? serialize( $_product->get_attributes() ) : '' ),
				);
			endwhile;
			$offset += $block_size;
		}
		return $data;
	}

	/**
	 * export_customers_from_orders.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function export_customers_from_orders() {
		$data = array();
		$data[] = array(
			__( 'Nr.', 'woocommerce-jetpack' ),
			__( 'Email', 'woocommerce-jetpack' ),
			__( 'First Name', 'woocommerce-jetpack' ),
			__( 'Last Name', 'woocommerce-jetpack' ),
			__( 'Last Order Date', 'woocommerce-jetpack' ),
		);
		$total_customers = 0;
		$orders = array();
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) break;
			while ( $loop_orders->have_posts() ) : $loop_orders->the_post();
				$order_id = $loop_orders->post->ID;
				$order = wc_get_order( $order_id );
				if ( isset( $order->billing_email ) && '' != $order->billing_email && ! in_array( $order->billing_email, $orders ) ) {
					$emails_to_skip = array();
					if ( ! in_array( $order->billing_email, $emails_to_skip ) ) {
						$total_customers++;
						$data[] = array( $total_customers, $order->billing_email, $order->billing_first_name, $order->billing_last_name, get_the_date( 'Y/m/d' ), );
						$orders[] = $order->billing_email;
					}
				}
			endwhile;
			$offset += $block_size;
		}
		return $data;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.7
	 * @since   2.5.4
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
				'title'    => __( 'Export Orders Fields', 'woocommerce-jetpack' ),
				'id'       => 'wcj_export_orders_fields',
				'default'  => $this->get_order_export_default_fields_ids(),
				'type'     => 'multiselect',
				'options'  => $this->get_order_export_fields(),
				'css'      => 'height:300px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_export_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Export_Import();

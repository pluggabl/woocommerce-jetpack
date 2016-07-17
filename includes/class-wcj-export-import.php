<?php
/**
 * WooCommerce Jetpack Export Import
 *
 * The WooCommerce Jetpack Export Import class.
 *
 * @version 2.5.4
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
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function export_csv() {
		if ( isset( $_POST['wcj_export'] ) ) {
			$data = $this->export( $_POST['wcj_export'] );
			$csv = '';
			foreach ( $data as $row ) {
				$csv .= implode( ',', $row ) . PHP_EOL;
			}
			header( "Content-Type: application/octet-stream" );
			header( "Content-Disposition: attachment; filename=" . $_POST['wcj_export'] . ".csv" );
			header( "Content-Type: application/octet-stream" );
			header( "Content-Type: application/download" );
			header( "Content-Description: File Transfer" );
			header( "Content-Length: " . strlen( $csv ) );
			echo $csv;
		}
	}

	/**
	 * create_export_tool.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function create_export_tool( $tool_id ) {
		$data = $this->export( $tool_id );
		echo '<p><form method="post" action="">';
		echo '<button class="button-primary" type="submit" name="wcj_export" value="' . $tool_id . '">' . __( 'Download CSV', 'woocommerce-jetpack' ) . '</button>';
		echo '</form></p>';
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
	 * @version 2.5.4
	 * @since   2.4.8
	 */
	function export_orders() {
		$data = array();
		$data[] = array(
			__( 'Order ID', 'woocommerce-jetpack' ),
			__( 'Order Number', 'woocommerce-jetpack' ),
			__( 'Order Status', 'woocommerce-jetpack' ),
			__( 'Order Date', 'woocommerce-jetpack' ),
			__( 'Order Item Count', 'woocommerce-jetpack' ),
			__( 'Order Total', 'woocommerce-jetpack' ),
			__( 'Order Payment Method', 'woocommerce-jetpack' ),
			__( 'Billing First Name', 'woocommerce-jetpack' ),
			__( 'Billing Last Name', 'woocommerce-jetpack' ),
			__( 'Billing Company', 'woocommerce-jetpack' ),
			__( 'Billing Address 1', 'woocommerce-jetpack' ),
			__( 'Billing Address 2', 'woocommerce-jetpack' ),
			__( 'Billing City', 'woocommerce-jetpack' ),
			__( 'Billing State', 'woocommerce-jetpack' ),
			__( 'Billing Postcode', 'woocommerce-jetpack' ),
			__( 'Billing Country', 'woocommerce-jetpack' ),
			__( 'Billing Phone', 'woocommerce-jetpack' ),
			__( 'Billing Email', 'woocommerce-jetpack' ),
			__( 'Shipping First Name', 'woocommerce-jetpack' ),
			__( 'Shipping Last Name', 'woocommerce-jetpack' ),
			__( 'Shipping Company', 'woocommerce-jetpack' ),
			__( 'Shipping Address 1', 'woocommerce-jetpack' ),
			__( 'Shipping Address 2', 'woocommerce-jetpack' ),
			__( 'Shipping City', 'woocommerce-jetpack' ),
			__( 'Shipping State', 'woocommerce-jetpack' ),
			__( 'Shipping Postcode', 'woocommerce-jetpack' ),
			__( 'Shipping Country', 'woocommerce-jetpack' ),
		);
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
				$data[] = array(
					$order_id,
					$order->get_order_number(),
					$order->get_status(),
					get_the_date( 'Y/m/d' ),
					$order->get_item_count(),
					$order->get_total() . ' ' . $order->get_order_currency(),
					$order->payment_method_title,
					$order->billing_first_name,
					$order->billing_last_name,
					$order->billing_company,
					$order->billing_address_1,
					$order->billing_address_2,
					$order->billing_city,
					$order->billing_state,
					$order->billing_postcode,
					$order->billing_country,
					$order->billing_phone,
					$order->billing_email,
					$order->shipping_first_name,
					$order->shipping_last_name,
					$order->shipping_company,
					$order->shipping_address_1,
					$order->shipping_address_2,
					$order->shipping_city,
					$order->shipping_state,
					$order->shipping_postcode,
					$order->shipping_country,
				);
			endwhile;
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
	 * @version 2.5.4
	 * @since   2.5.4
	 */
	function get_settings() {
		$settings = array();
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Export_Import();

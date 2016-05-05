<?php
/**
 * WooCommerce Jetpack Purchase Data
 *
 * The WooCommerce Jetpack Purchase Data class.
 *
 * @version 2.4.8
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Purchase_Data' ) ) :

class WCJ_Purchase_Data extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 */
	function __construct() {

		$this->id         = 'purchase_data';
		$this->short_desc = __( 'Product Cost Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Save WooCommerce product purchase costs data for admin reports.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-cost-price/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			if ( 'yes' === get_option( 'wcj_purchase_data_custom_columns_profit', 'no' ) ) {
				add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_columns' ),     PHP_INT_MAX );
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_order_columns.
	 *
	 * @since 2.2.4
	 */
	function add_order_columns( $columns ) {
		$columns['profit'] = __( 'Profit', 'woocommerce-jetpack' );
		return $columns;
	}

	/**
	 * Output custom columns for orders
	 *
	 * @param   string $column
	 * @version 2.4.5
	 * @since   2.2.4
	 * @todo    forecasted profit
	 */
	function render_order_columns( $column ) {
		if ( 'profit' === $column ) {
			$total_profit = 0;
			$the_order = wc_get_order( get_the_ID() );
			if ( 'completed' === $the_order->get_status() ) {
				$is_forecasted = false;
				foreach ( $the_order->get_items() as $item_id => $item ) {
					$the_profit = 0;
					$product_id = ( isset( $item['variation_id'] ) && 0 != $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
					if ( 0 != ( $purchase_price = wc_get_product_purchase_price( $product_id ) ) ) {
						$the_profit = ( $item['line_total'] + $item['line_tax'] ) - $purchase_price * $item['qty'];
					} else {
//						$the_profit = ( $item['line_total'] + $item['line_tax'] ) * $average_profit_margin;
						$is_forecasted = true;
					}
					$total_profit += $the_profit;
				}
			}
			if ( 0 != $total_profit ) {
				if ( ! $is_forecasted ) echo '<span style="color:green;">';
				echo wc_price( $total_profit );
				if ( ! $is_forecasted ) echo '</span>';
			}
		}
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.4.8
	 * @since   2.4.5
	 * @todo    wcj_purchase_price_currency
	 */
	function get_meta_box_options() {
		$main_product_id = get_the_ID();
		$_product = wc_get_product( $main_product_id );
		$products = array();
		if ( $_product->is_type( 'variable' ) ) {
			$available_variations = $_product->get_available_variations();
			foreach ( $available_variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );
				$products[ $variation['variation_id'] ] = ' (' . $variation_product->get_formatted_variation_attributes( true ) . ')';
			}
		} else {
			$products[ $main_product_id ] = '';
		}
		$options = array();
		foreach ( $products as $product_id => $desc ) {
			$product_options = array(
				array(
					'name'       => 'wcj_purchase_price_' . $product_id,
					'default'    => 0,
					'type'       => 'price',
					'title'      => __( 'Product cost (purchase) price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_' . 'wcj_purchase_price',
					'enabled'    => get_option( 'wcj_purchase_price_enabled', 'yes' ),
				),
				array(
					'name'       => 'wcj_purchase_price_extra_' . $product_id,
					'default'    => 0,
					'type'       => 'price',
					'title'      => __( 'Extra expenses (shipping etc.)', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_' . 'wcj_purchase_price_extra',
					'enabled'    => get_option( 'wcj_purchase_price_extra_enabled', 'yes' ),
				),
				array(
					'name'       => 'wcj_purchase_price_affiliate_commission_' . $product_id,
					'default'    => 0,
					'type'       => 'price',
					'title'      => __( 'Affiliate commission', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_' . 'wcj_purchase_price_affiliate_commission',
					'enabled'    => get_option( 'wcj_purchase_price_affiliate_commission_enabled', 'no' ),
				),
			);
			$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_purchase_data_custom_price_fields_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				$the_title = get_option( 'wcj_purchase_data_custom_price_field_name_' . $i, '' );
				if ( '' == $the_title ) {
					continue;
				}
				$the_type           = get_option( 'wcj_purchase_data_custom_price_field_type_' . $i, 'fixed' );
				$the_default_value  = get_option( 'wcj_purchase_data_custom_price_field_default_value_' . $i, 0 );
				$the_title .= ( 'fixed' === $the_type ) ? ' (' . get_woocommerce_currency_symbol() . ')'  : ' (' . '%' . ')';
				$product_options[] = array(
					'name'       => 'wcj_purchase_price_custom_field_' . $i . '_' . $product_id,
					'default'    => $the_default_value,
					'type'       => 'price',
					'title'      => $the_title,
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_' . 'wcj_purchase_price_custom_field_' . $i,
					'enabled'    => 'yes',
				);
			}
			$product_options = array_merge( $product_options, array(
				array(
					'name'       => 'wcj_purchase_date_' . $product_id,
					'default'    => '',
					'type'       => 'date',
					'title'      => '<em>' . __( '(Last) Purchase date', 'woocommerce-jetpack' ) . '</em>',
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_' . 'wcj_purchase_date',
					'enabled'    => get_option( 'wcj_purchase_date_enabled', 'yes' ),
				),
				array(
					'name'       => 'wcj_purchase_partner_' . $product_id,
					'default'    => '',
					'type'       => 'text',
					'title'      => '<em>' . __( 'Seller', 'woocommerce-jetpack' ) . '</em>',
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_' . 'wcj_purchase_partner',
					'enabled'    => get_option( 'wcj_purchase_partner_enabled', 'yes' ),
				),
				array(
					'name'       => 'wcj_purchase_info_' . $product_id,
					'default'    => '',
					'type'       => 'textarea',
					'title'      => '<em>' . __( 'Purchase info', 'woocommerce-jetpack' ) . '</em>',
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_' . 'wcj_purchase_info',
					'enabled'    => get_option( 'wcj_purchase_info_enabled', 'yes' ),
				),
			) );
			$product_options = apply_filters( 'wcj_purchase_data_product_options', $product_options, $product_id, $desc );
			$options = array_merge( $options, $product_options );
		}
		return $options;
	}

	/**
	 * create_meta_box.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
	 * @todo    min_profit
	 */
	function create_meta_box() {

		parent::create_meta_box();

		// Report
		$main_product_id = get_the_ID();
		$_product = wc_get_product( $main_product_id );
		$products = array();
		if ( $_product->is_type( 'variable' ) ) {
			$available_variations = $_product->get_available_variations();
			foreach ( $available_variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );
				$products[ $variation['variation_id'] ] = ' (' . $variation_product->get_formatted_variation_attributes( true ) . ')';
			}
		} else {
			$products[ $main_product_id ] = '';
		}
		foreach ( $products as $product_id => $desc ) {
			$purchase_price = wc_get_product_purchase_price( $product_id );
			if ( 0 != $purchase_price ) {
				$the_product = wc_get_product( $product_id );
				$the_price = $the_product->get_price();
				if ( 0 != $the_price ) {
					$the_profit = $the_price - $purchase_price;
					$table_data = array();
					$table_data[] = array( __( 'Selling', 'woocommerce-jetpack' ), wc_price( $the_price ) );
					$table_data[] = array( __( 'Buying', 'woocommerce-jetpack' ),  wc_price( $purchase_price ) );
					$table_data[] = array( __( 'Profit', 'woocommerce-jetpack' ),  wc_price( $the_profit )
						. sprintf( ' (%0.2f %%)', ( $the_profit / $purchase_price * 100 ) ) );
//						. sprintf( ' (%0.2f %%)', ( $the_profit / $the_price * 100 ) ) );
					/* $the_min_profit = $purchase_price * 0.25;
					$the_min_price = $purchase_price * 1.25;
					$html .= __( 'Min Profit', 'woocommerce-jetpack' );
					$html .= wc_price( $the_min_profit ) . ' ' . __( 'at', 'woocommerce-jetpack' ) . ' ' . wc_price( $the_min_price ); */
					$html = '';
					$html .= '<h5>' . __( 'Report', 'woocommerce-jetpack' ) . $desc . '</h5>';
					$html .= wcj_get_table_html( $table_data, array(
						'table_heading_type' => 'none',
						'table_class'        => 'widefat striped',
						'table_style'        => 'width:50%;min-width:300px;',
						'columns_styles'     => array( 'width:33%;' ),
					) );
					echo $html;
				}
			}
		}
	}

	/**
	 * calculate_all_products_profit.
	 *
	 * @todo
	 */
	/* function calculate_all_products_profit() { } */

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 * @todo    add options to set fields and column titles
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'     => __( 'Price Fields', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'This fields will be added to product\'s edit page and will be included in product\'s purchase cost calculation.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_data_price_fields_options',
			),
			array(
				'title'     => __( 'Product cost (purchase) price', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_price_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Extra expenses (shipping etc.)', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_price_extra_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Affiliate commission', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_price_affiliate_commission_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_purchase_data_price_fields_options',
			),
			array(
				'title'     => __( 'Custom Price Fields', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'This fields will be added to product\'s edit page and will be included in product\'s purchase cost calculation.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_data_custom_price_fields_options',
			),
			array(
				'title'     => __( 'Total Custom Price Fields', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_data_custom_price_fields_total_number',
				'default'   => 1,
				'type'      => 'custom_number',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
		);
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_purchase_data_custom_price_fields_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'     => __( 'Custom Price Field', 'woocommerce-jetpack' ) . ' #' . $i,
					'id'        => 'wcj_purchase_data_custom_price_field_name_' . $i,
					'desc'      => __( 'Title', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
					'default'   => '',
					'type'      => 'text',
				),
				array(
					'id'        => 'wcj_purchase_data_custom_price_field_type_' . $i,
					'desc'      => __( 'Type', 'woocommerce-jetpack' ),
					'default'   => 'fixed',
					'type'      => 'select',
					'options'   => array(
						'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
						'percent' => __( 'Percent', 'woocommerce-jetpack' ),
					),
				),
				array(
					'id'        => 'wcj_purchase_data_custom_price_field_default_value_' . $i,
					'desc'      => __( 'Default Value', 'woocommerce-jetpack' ),
					'default'   => 0,
					'type'      => 'number',
					'custom_attributes' => array( 'step' => '0.0001' ),
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_purchase_data_custom_price_fields_options',
			),
			array(
				'title'     => __( 'Info Fields', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'This fields will be added to product\'s edit page.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_data_info_fields_options',
			),
			array(
				'title'     => __( '(Last) Purchase date', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_date_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Seller', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_partner_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Purchase info', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_info_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_purchase_data_info_fields_options',
			),
			array(
				'title'     => __( 'Orders List Custom Columns', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'This section lets you add custom columns to WooCommerce orders list.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_data_custom_columns_options',
			),
			array(
				'title'     => __( 'Profit', 'woocommerce-jetpack' ),
				'desc'      => __( 'Add', 'woocommerce-jetpack' ),
				'id'        => 'wcj_purchase_data_custom_columns_profit',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_purchase_data_custom_columns_options',
			),
		) );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Purchase_Data();

<?php
/**
 * Booster for WooCommerce - Module - Product Cost Price
 *
 * @version 2.8.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Purchase_Data' ) ) :

class WCJ_Purchase_Data extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'purchase_data';
		$this->short_desc = __( 'Product Cost Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Save WooCommerce product purchase costs data for admin reports.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-cost-price';
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			if ( 'yes' === get_option( 'wcj_purchase_data_custom_columns_profit', 'yes' ) || 'yes' === get_option( 'wcj_purchase_data_custom_columns_purchase_cost', 'no' ) ) {
				add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_columns' ),    PHP_INT_MAX - 2 );
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_order_columns.
	 *
	 * @version 2.6.0
	 * @since   2.2.4
	 */
	function add_order_columns( $columns ) {
		if ( 'yes' === get_option( 'wcj_purchase_data_custom_columns_profit', 'yes' ) ) {
			$columns['profit'] = __( 'Profit', 'woocommerce-jetpack' );
		}
		if ( 'yes' === get_option( 'wcj_purchase_data_custom_columns_purchase_cost', 'no' ) ) {
			$columns['purchase_cost'] = __( 'Purchase Cost', 'woocommerce-jetpack' );
		}
		return $columns;
	}

	/**
	 * Output custom columns for orders
	 *
	 * @param   string $column
	 * @version 2.7.0
	 * @since   2.2.4
	 * @todo    forecasted profit
	 */
	function render_order_columns( $column ) {
		if ( 'profit' === $column || 'purchase_cost' === $column ) {
			$total = 0;
			$the_order = wc_get_order( get_the_ID() );
			if ( ! in_array( $the_order->get_status(), array( 'cancelled', 'refunded', 'failed' ) ) ) {
				$is_forecasted = false;
				foreach ( $the_order->get_items() as $item_id => $item ) {
					$value = 0;
					$product_id = ( isset( $item['variation_id'] ) && 0 != $item['variation_id'] && 'no' === get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' ) )
						? $item['variation_id']
						: $item['product_id'];
					if ( 0 != ( $purchase_price = wc_get_product_purchase_price( $product_id ) ) ) {
						if ( 'profit' === $column ) {
//							$line_total = ( 'yes' === get_option('woocommerce_prices_include_tax') ) ? ( $item['line_total'] + $item['line_tax'] ) : $item['line_total'];
							$_order_prices_include_tax = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_order->prices_include_tax : $the_order->get_prices_include_tax() );
							$line_total = ( $_order_prices_include_tax ) ? ( $item['line_total'] + $item['line_tax'] ) : $item['line_total'];
							$value = $line_total - $purchase_price * $item['qty'];
						} else { // if ( 'purchase_cost' === $column )
							$value = $purchase_price * $item['qty'];
						}
					} else {
//						$value = ( $item['line_total'] + $item['line_tax'] ) * $average_profit_margin;
						$is_forecasted = true;
					}
					$total += $value;
				}
			}
			if ( 0 != $total ) {
				if ( ! $is_forecasted ) echo '<span style="color:green;">';
				echo wc_price( $total );
				if ( ! $is_forecasted ) echo '</span>';
			}
		}
	}

	/**
	 * create_meta_box.
	 *
	 * @version 2.7.0
	 * @since   2.4.5
	 * @todo    min_profit
	 */
	function create_meta_box() {

		parent::create_meta_box();

		// Report
		$main_product_id = get_the_ID();
		$_product = wc_get_product( $main_product_id );
		$products = array();
		if ( $_product->is_type( 'variable' ) && 'no' === get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' ) ) {
			$available_variations = $_product->get_available_variations();
			foreach ( $available_variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );
				$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
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

}

endif;

return new WCJ_Purchase_Data();

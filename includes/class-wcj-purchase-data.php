<?php
/**
 * Booster for WooCommerce - Module - Cost of Goods (formerly Product Cost Price)
 *
 * @version 2.9.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Purchase_Data' ) ) :

class WCJ_Purchase_Data extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 * @todo    (maybe) pre-calculate profit for orders
	 * @todo    (maybe) "Apply costs to orders that do not have costs set"
	 * @todo    (maybe) "Apply costs to all orders, overriding previous costs"
	 * @todo    (maybe) `calculate_all_products_profit()`
	 */
	function __construct() {

		$this->id         = 'purchase_data';
		$this->short_desc = __( 'Cost of Goods', 'woocommerce-jetpack' );
		$this->desc       = __( 'Save WooCommerce product purchase costs data for admin reports.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-cost-of-goods';
		parent::__construct();

		$this->add_tools( array(
			'import_from_wc_cog' => array(
				'title'     => __( '"WooCommerce Cost of Goods" Data Import', 'woocommerce-jetpack' ),
				'desc'      => __( 'Import products costs from "WooCommerce Cost of Goods".', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {

			// Products meta boxes
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			// Orders columns
			if (
				'yes' === get_option( 'wcj_purchase_data_custom_columns_profit', 'yes' ) ||
				'yes' === get_option( 'wcj_purchase_data_custom_columns_purchase_cost', 'no' )
			) {
				add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_columns' ),    PHP_INT_MAX - 2 );
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), PHP_INT_MAX );
			}

			// Products columns
			if (
				'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_purchase_data_custom_products_columns_purchase_cost', 'no' ) ) ||
				'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_purchase_data_custom_products_columns_profit', 'no' ) )
			) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),    PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_columns' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * create_import_from_wc_cog_tool.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function create_import_from_wc_cog_tool() {
		// Action and Products list
		$perform_import = ( isset( $_POST['wcj_import_from_wc_cog'] ) );
		$table_data = array();
		$table_data[] = array(
			__( 'Product ID', 'woocommerce-jetpack' ),
			__( 'Product Title', 'woocommerce-jetpack' ),
			__( 'WooCommerce Cost of Goods (source)', 'woocommerce-jetpack' ),
			__( 'Booster: Product cost (destination)', 'woocommerce-jetpack' ),
		);
		foreach ( wcj_get_products( array(), 'any', 512, true  ) as $product_id => $product_title ) {
			$wc_cog_cost = get_post_meta( $product_id, '_wc_cog_cost', true );
			if ( $perform_import ) {
				update_post_meta( $product_id, '_wcj_purchase_price', $wc_cog_cost );
			}
			$wcj_purchase_price = get_post_meta( $product_id, '_wcj_purchase_price', true );
			$table_data[] = array( $product_id, $product_title, $wc_cog_cost, $wcj_purchase_price );
		}
		// Button form
		$button_form = '';
		$button_form .= '<form method="post" action="">';
		$button_form .= '<input type="submit" name="wcj_import_from_wc_cog" class="button-primary" value="' . __( 'Import', 'woocommerce-jetpack' ) . '"' .
			' onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">';
		$button_form .= '</form>';
		// Output
		$html = '';
		$html .= '<div class="wrap">';
		$html .= '<p>' . $this->get_tool_header_html( 'import_from_wc_cog' ) . '</p>';
		$html .= '<p>' . $button_form . '</p>';
		$html .= '<p>' . wcj_get_table_html( $table_data, array( 'table_heading_type' => 'horizontal', 'table_class' => 'widefat striped' ) ) . '</p>';
		$html .= '</div>';
		echo $html;
	}

	/**
	 * add_product_columns.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) output columns immediately after standard "Price"
	 */
	function add_product_columns( $columns ) {
		if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_purchase_data_custom_products_columns_purchase_cost', 'no' ) ) ) {
			$columns['purchase_cost'] = __( 'Cost', 'woocommerce-jetpack' );
		}
		if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_purchase_data_custom_products_columns_profit', 'no' ) ) ) {
			$columns['profit'] = __( 'Profit', 'woocommerce-jetpack' );
		}
		return $columns;
	}

	/**
	 * render_product_columns.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    variable products when `if ( 'no' === get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' ) )`
	 */
	function render_product_columns( $column ) {
		if ( 'profit' === $column || 'purchase_cost' === $column ) {
			$product_id = get_the_ID();
			$purchase_cost = wc_get_product_purchase_price( $product_id );
			if ( 'purchase_cost' === $column ) {
				echo wc_price( $purchase_cost );
			} elseif ( 'profit' === $column ) {
				$_product = wc_get_product( $product_id );
				echo wc_price( $_product->get_price() - $purchase_cost );
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
	 * @todo    forecasted profit `$value = $line_total * $average_profit_margin`
	 * @todo    (maybe) use `[wcj_order_profit]` and `[wcj_order_items_cost]`
	 */
	function render_order_columns( $column ) {
		if ( 'profit' === $column || 'purchase_cost' === $column ) {
			$total = 0;
			$the_order = wc_get_order( get_the_ID() );
			if ( ! in_array( $the_order->get_status(), array( 'cancelled', 'refunded', 'failed' ) ) ) {
				$is_forecasted = false;
				foreach ( $the_order->get_items() as $item_id => $item ) {
					$value = 0;
					$product_id = ( isset( $item['variation_id'] ) && 0 != $item['variation_id'] && 'no' === get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' )
						? $item['variation_id'] : $item['product_id'] );
					if ( 0 != ( $purchase_price = wc_get_product_purchase_price( $product_id ) ) ) {
						if ( 'profit' === $column ) {
							$_order_prices_include_tax = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_order->prices_include_tax : $the_order->get_prices_include_tax() );
							$line_total = ( $_order_prices_include_tax ) ? ( $item['line_total'] + $item['line_tax'] ) : $item['line_total'];
							$value = $line_total - $purchase_price * $item['qty'];
						} else { // if ( 'purchase_cost' === $column )
							$value = $purchase_price * $item['qty'];
						}
					} else {
						$is_forecasted = true;
					}
					$total += $value;
				}
			}
			if ( 0 != $total ) {
				if ( ! $is_forecasted ) {
					echo '<span style="color:green;">';
				}
				echo wc_price( $total );
				if ( ! $is_forecasted ) {
					echo '</span>';
				}
			}
		}
	}

	/**
	 * create_meta_box.
	 *
	 * @version 2.7.0
	 * @since   2.4.5
	 * @todo    (maybe) min_profit
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

}

endif;

return new WCJ_Purchase_Data();

<?php
/**
 * WooCommerce Jetpack Purchase Data
 *
 * The WooCommerce Jetpack Purchase Data class.
 *
 * @version 2.4.5
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Purchase_Data' ) ) :

class WCJ_Purchase_Data extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.5
	 */
	function __construct() {

		$this->id         = 'purchase_data';
		$this->short_desc = __( 'Product Cost Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Save WooCommerce product purchase costs data for admin reports.', 'woocommerce-jetpack' );
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
	 * @version 2.2.6
	 * @since   2.2.4
	 */
	function render_order_columns( $column ) {
		if ( 'profit' === $column ) {
			$total_profit = 0;
			$the_order = wc_get_order( get_the_ID() );
			if ( 'completed' === $the_order->get_status() ) {
				$is_forecasted = false;
				foreach ( $the_order->get_items() as $item_id => $item ) {
//					$product = $this->get_product_from_item( $item );
					$the_profit = 0;
					if ( 0 != ( $purchase_price = wc_get_product_purchase_price( $item['product_id'] ) ) ) {
						$the_profit = ( $item['line_total'] + $item['line_tax'] ) - $purchase_price * $item['qty'];
//						$total_profit += $the_profit;
//						echo $item['line_total'] . ' ~ ' . $purchase_price . ' ~ ' . $item['qty'];
					} else {
						//$the_profit = ( $item['line_total'] + $item['line_tax'] ) * 0.2;
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
	 * @version 2.4.5
	 * @since   2.4.5
	 * @todo    wcj_purchase_price_currency
	 */
	function get_meta_box_options() {
		$product_id = get_the_ID();
		$desc = '';
		$options = array(
			array(
				'name'       => 'wcj_purchase_price_' . $product_id,
				'default'    => 0,
				'type'       => 'price',
				'title'      => __( 'Product cost (purchase) price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_purchase_price',
			),
			array(
				'name'       => 'wcj_purchase_price_extra_' . $product_id,
				'default'    => 0,
				'type'       => 'price',
				'title'      => __( 'Extra expenses (shipping etc.)', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_purchase_price_extra',
			),
			array(
				'name'       => 'wcj_purchase_date_' . $product_id,
				'default'    => '',
				'type'       => 'date',
				'title'      => __( '(Last) Purchase date', 'woocommerce-jetpack' ),
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_purchase_date',
			),
			array(
				'name'       => 'wcj_purchase_partner_' . $product_id,
				'default'    => '',
				'type'       => 'text',
				'title'      => __( 'Seller', 'woocommerce-jetpack' ),
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_purchase_partner',
			),
			array(
				'name'       => 'wcj_purchase_info_' . $product_id,
				'default'    => '',
				'type'       => 'textarea',
				'title'      => __( 'Purchase info', 'woocommerce-jetpack' ),
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_purchase_info',
			),
		);
		return $options;
	}

	/**
	 * create_meta_box.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
	 */
	function create_meta_box() {

		parent::create_meta_box();

		$current_post_id = get_the_ID();
		$purchase_price = wc_get_product_purchase_price( $current_post_id );
		if ( 0 != $purchase_price ) {
			$the_product = wc_get_product( $current_post_id );
			$the_price = $the_product->get_price();
			if ( 0 != $the_price ) {
				$html = '';
				$html .= '<h5>' . __( 'Report', 'woocommerce-jetpack' ) . '</h5>';
				$html .= '<table class="widefat" style="width:300px;">';

				$html .= '<tr>';
				$html .= '<th>';
				$html .= __( 'Selling', 'woocommerce-jetpack' );
				$html .= '</th>';
				$html .= '<td>';
				$html .= wc_price( $the_price );
				$html .= '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<th>';
				$html .= __( 'Buying', 'woocommerce-jetpack' );
				$html .= '</th>';
				$html .= '<td>';
				$html .= wc_price( $purchase_price );
				$html .= '</td>';
				$html .= '</tr>';

				$the_profit = $the_price - $purchase_price;
				$html .= '<tr>';
				$html .= '<th>';
				$html .= __( 'Profit', 'woocommerce-jetpack' );
				$html .= '</th>';
				$html .= '<td>';
				$html .= wc_price( $the_profit )
					. sprintf( ' (%0.2f %%)', ( $the_profit / $purchase_price * 100 ) );
					//. sprintf( ' (%0.2f %%)', ( $the_profit / $the_price * 100 ) );
				$html .= '</td>';
				$html .= '</tr>';

				/* $the_min_profit = $purchase_price * 0.25;
				$the_min_price = $purchase_price * 1.25;
				$html .= '<tr>';
				$html .= '<th>';
				$html .= __( 'Min Profit', 'woocommerce-jetpack' );
				$html .= '</th>';
				$html .= '<td>';
				$html .= wc_price( $the_min_profit ) . ' ' . __( 'at', 'woocommerce-jetpack' ) . ' ' . wc_price( $the_min_price );
				$html .= '</td>';
				$html .= '</tr>'; */

				$html .= '</table>';
				echo $html;
			}
		}
	}

	/**
	 * calculate_all_products_profit.
	 */
	/* function calculate_all_products_profit() {
		$args = array(
			'post_type'			=> 'product',
			'post_status' 		=> 'any',
			'posts_per_page' 	=> -1,
		);
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) : $loop->the_post();

			$current_post_id = $loop->post->ID;
			$option_name = 'wcj_purchase_price';
			if ( ! ( $purchase_price = get_post_meta( $current_post_id, '_' . $option_name, true ) ) )
				$purchase_price = 0;

			$the_product = wc_get_product( $current_post_id );
			$the_price = $the_product->get_price();

			if ( 0 != $purchase_price ) {
				//echo( '<p>' );
				/*wcj_log(
					get_the_title()
					. ' - '
					. wc_price( $purchase_price )
					. ' - '
					. wc_price( $the_price )
					. ' - '
					. wc_price( $the_price - $purchase_price )
				);*//*
				//echo( '</p>' );

				//$the_total
			}

		endwhile;

		//wp_reset_query();
		//die();
	} */

	/**
	 * get_settings.
	 *
	 * @version 2.4.5
	 */
	function get_settings() {
		$settings = array(
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
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Purchase_Data();

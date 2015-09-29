<?php
/**
 * WooCommerce Jetpack Purchase Data
 *
 * The WooCommerce Jetpack Purchase Data class.
 *
 * @version 2.2.6
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Purchase_Data' ) ) :

class WCJ_Purchase_Data extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.2.4
	 */
	public function __construct() {

		$this->id         = 'purchase_data';
		$this->short_desc = __( 'Product Cost Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Save WooCommerce product purchase costs data for admin reports.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_action( 'add_meta_boxes',    array( $this, 'add_purchase_price_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_purchase_price_meta_box' ), PHP_INT_MAX, 2 );

			//add_action( 'init', array( $this, 'calculate_all_products_profit' ) );

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
	public function render_order_columns( $column ) {

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
	 * get_options.
	 */
	public function get_options() {
		return array(
			array(
				'name'    => 'wcj_purchase_price',
				'default' => 0,
			),
			array(
				'name'    => 'wcj_purchase_price_extra',
				'default' => 0,
			),
			/* array(
				'name'    => 'wcj_purchase_price_currency',
				'default' => '',
			), */
			array(
				'name'    => 'wcj_purchase_date',
				'default' => '',
			),
			array(
				'name'    => 'wcj_purchase_partner',
				'default' => '',
			),
			array(
				'name'    => 'wcj_purchase_info',
				'default' => '',
			),
		);
	}

	/**
	 * save_purchase_price_meta_box.
	 */
	public function save_purchase_price_meta_box( $post_id, $post ) {

		// Check that we are saving with purchase price metabox displayed.
		if ( ! isset( $_POST['woojetpack_purchase_price_save_post'] ) )
			return;

		// Save options
		foreach ( $this->get_options() as $option ) {
			$option_value = isset( $_POST[ $option['name'] ] ) ? $_POST[ $option['name'] ] : $option['default'];
			update_post_meta( $post_id, '_' . $option['name'], $option_value );
		}
	}

	/**
	 * add_purchase_price_meta_box.
	 */
	public function add_purchase_price_meta_box() {
		add_meta_box(
			'wc-jetpack-purchase-price',
			__( 'WooCommerce Jetpack', 'woocommerce-jetpack' ) . ': ' . $this->short_desc,
			array( $this, 'create_purchase_price_meta_box' ),
			'product',
			'normal',
			'high' );
	}

	/**
	 * create_purchase_price_meta_box.
	 */
	public function create_purchase_price_meta_box() {

		$current_post_id = get_the_ID();

		$purchase_price = 0;

		$html = '';
		$html .= '<div style="width:40%;">';
		$html .= '<table>';
		foreach ( $this->get_options() as $option ) {

			$option_value = get_post_meta( $current_post_id, '_' . $option['name'], true );

			switch ( $option['name'] ) {
				case 'wcj_purchase_price':
					$title = __( 'Product cost (purchase) price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')';
					$field_html = '<input class="short wc_input_price" type="number" step="0.0001"';
					break;
				case 'wcj_purchase_price_extra':
					$title = __( 'Extra expenses (shipping etc.)', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')';
					$field_html = '<input class="short wc_input_price" type="number" step="0.0001"';
					break;
				case 'wcj_purchase_partner':
					$title = __( 'Seller', 'woocommerce-jetpack' );
					$field_html = '<input class="input-text" type="text"';
					break;
				case 'wcj_purchase_date':
					$title = __( '(Last) Purchase date', 'woocommerce-jetpack' );
					$field_html = '<input class="input-text" display="date" type="text"';
					break;
				case 'wcj_purchase_info':
					$title = __( 'Purchase info', 'woocommerce-jetpack' );
					$field_html = '<textarea id="' . $option['name'] . '" name="' . $option['name'] . '">' . $option_value . '</textarea>';
					break;
			}

			if ( 'wcj_purchase_info' != $option['name'] ) {
				$field_html .= ' id="' . $option['name'] . '" name="' . $option['name'] . '" value="' . $option_value . '">';
			}

			if ( 'wcj_purchase_price' == $option['name']  || 'wcj_purchase_price_extra' == $option['name'] ) {
				// Saving for later use
				$purchase_price += $option_value;
			}

			$html .= '<tr>';
			$html .= '<th>' . $title . '</th>';
			$html .= '<td>' . $field_html . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';
		$html .= '<input type="hidden" name="woojetpack_purchase_price_save_post" value="woojetpack_purchase_price_save_post">';
		$html .= '</div>';
		echo $html;

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
	 *
	function calculate_all_products_profit() {
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
	}

	/**
	 * get_settings.
	 *
	 * @version 2.2.4
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
		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Purchase_Data();

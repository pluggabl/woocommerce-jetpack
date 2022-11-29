<?php
/**
 * Booster for WooCommerce - Reports - Stock
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Reports_Stock' ) ) :
		/**
		 * WCJ_Reports_Stock.
		 *
		 * @version 3.9.0
		 */
	class WCJ_Reports_Stock {

		/**
		 * Ranges_in_days
		 *
		 * @var array Possible ranges in days values.
		 */
		public $ranges_in_days;

		/**
		 * Constructor.
		 *
		 * @version 3.9.0
		 * @todo    (maybe) `most_stock_price`, `sales_up`, `good_sales_low_stock`
		 * @todo    (maybe) `echo __( 'Here you can generate reports. Some reports are generated using all your orders and products, so if you have a lot of them - it may take a while.', 'woocommerce-jetpack' );`
		 * @todo    (maybe) wcj_get_option( 'woocommerce_manage_stock' ) -> `echo __( 'Please enable stock management in <strong>WooCommerce > Settings > Products > Inventory</strong> to generate stock based reports.', 'woocommerce-jetpack' );`
		 * @param null $args Get null value.
		 */
		public function __construct( $args = null ) {
			$this->ranges_in_days           = array( 7, 14, 30, 60, 90, 180, 360 );
			$this->report_id                = isset( $args['report_id'] ) ? $args['report_id'] : 'on_stock';
			$this->range_days               = isset( $args['range_days'] ) ? $args['range_days'] : 30;
			$this->reports_info             = array(
				'on_stock'     => array(
					'id'    => 'on_stock',
					'title' => __( 'All Products on Stock', 'woocommerce-jetpack' ),
					'desc'  => __( 'Report shows all products that are on stock and some sales info.', 'woocommerce-jetpack' ),
				),
				'understocked' => array(
					'id'    => 'understocked',
					'title' => __( 'Understocked', 'woocommerce-jetpack' ),
					'desc'  => __( 'Report shows all products that are low in stock calculated on product\'s sales data.', 'woocommerce-jetpack' ) . ' ' .
						__( 'Threshold for minimum stock is equal to half of the sales in selected days range.', 'woocommerce-jetpack' ),
				),
				'overstocked'  => array(
					'id'    => 'overstocked',
					'title' => __( 'Overstocked', 'woocommerce-jetpack' ),
					'desc'  => __( 'Report shows all products that are on stock, but have no sales in selected period. Only products added before the start date of selected period are accounted.', 'woocommerce-jetpack' ),
				),
			);
			$this->product_type             = wcj_get_option( 'wcj_reports_stock_product_type', 'product' );
			$this->include_deleted_products = ( 'yes' === wcj_get_option( 'wcj_reports_stock_include_deleted_products', 'yes' ) );
			$this->start_time               = microtime( true );
			$products_info                  = array();
			$this->gather_products_data( $products_info );
			$this->gather_orders_data( $products_info );
			$info = $this->get_stock_summary( $products_info );
			if ( 'on_stock' === $this->report_id || 'overstocked' === $this->report_id ) {
				$this->sort_products_info( $products_info, 'stock_price' );
			}
			if ( 'understocked' === $this->report_id ) {
				$this->sort_products_info( $products_info, 'sales_in_period', $this->range_days );
			}
			$this->data_products = $products_info;
			$this->data_summary  = $info;
			$this->data_reports  = $this->reports_info[ $this->report_id ];
		}

		/**
		 * Get_submenu_html.
		 *
		 * @version 6.0.0
		 */
		public function get_submenu_html() {
			$html  = '';
			$html .= '<div id="poststuff" class="wcj-reports-wide woocommerce-reports-wide"><div class="postbox"><div class="stats_range"><ul class="">';
			foreach ( $this->ranges_in_days as $the_period ) {
				$is_active = (string) $the_period === $this->range_days ? 'active' : '';

				$html .= '<li class="' . $is_active . '">';
				$html .= '<a href="' . get_admin_url() . 'admin.php?page=wc-reports&tab=stock&report=' . $this->report_id . '&period=' . $the_period . '" class="">' . $the_period . ' days</a>';
				$html .= '</li>';
			}
			$html .= '</ul>';
			return $html;
		}

		/**
		 * Gather_products_data.
		 *
		 * @version 3.9.0
		 * @param Array $products_info Get product data.
		 */
		public function gather_products_data( &$products_info ) {
			$offset     = 0;
			$block_size = 1024;
			while ( true ) {
				$args = array(
					'post_type'      => ( 'both' === $this->product_type ? array( 'product', 'product_variation' ) : $this->product_type ),
					'posts_per_page' => $block_size,
					'offset'         => $offset,
					'fields'         => 'ids',
				);
				$loop = new WP_Query( $args );
				if ( ! $loop->have_posts() ) {
					break;
				}
				foreach ( $loop->posts as $product_id ) {
					$the_title = get_the_title( $product_id );
					if ( '' === $the_title ) {
						if ( $this->include_deleted_products ) {
							$the_title = __( 'N/A', 'woocommerce-jetpack' );
						} else {
							continue;
						}
					}
					$the_product        = wc_get_product( $product_id );
					$the_price          = $the_product->get_price();
					$the_stock          = wcj_get_product_total_stock( $the_product );
					$the_categories     = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_product->get_categories() : wc_get_product_category_list( $product_id ) );
					$the_date           = get_the_date( wcj_get_option( 'date_format' ), $product_id );
					$the_permalink      = get_the_permalink( $product_id );
					$post_custom        = get_post_custom( $product_id );
					$total_sales        = isset( $post_custom['total_sales'][0] ) ? $post_custom['total_sales'][0] : 0;
					$purchase_price     = wc_get_product_purchase_price( $product_id );
					$sales_in_day_range = array();
					foreach ( $this->ranges_in_days as $the_range ) {
						$sales_in_day_range[ $the_range ] = 0;
					}
					$products_info[ $product_id ] = array(
						'ID'              => $product_id,
						'title'           => $the_title . ' (#' . $product_id . ')',
						'category'        => $the_categories,
						'permalink'       => $the_permalink,
						'price'           => $the_price,
						'stock'           => $the_stock,
						'stock_price'     => ( is_numeric( $the_price ) && is_numeric( $the_stock ) ? $the_price * $the_stock : 0 ),
						'total_sales'     => $total_sales,
						'date_added'      => $the_date,
						'purchase_price'  => $purchase_price,
						'last_sale'       => 0,
						'sales_in_period' => $sales_in_day_range,
					);
				}
				$offset += $block_size;
			}
		}

		/**
		 * Gather_orders_data.
		 *
		 * @version 3.9.0
		 * @param Array $products_info Get product data.
		 */
		public function gather_orders_data( &$products_info ) {
			$offset     = 0;
			$block_size = 1024;
			while ( true ) {
				$args_orders = array(
					'post_type'      => 'shop_order',
					'post_status'    => 'wc-completed',
					'posts_per_page' => $block_size,
					'offset'         => $offset,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'date_query'     => array(
						array(
							'column' => 'post_date_gmt',
							'after'  => $this->range_days . ' days ago',
						),
					),
					'fields'         => 'ids',
				);
				$the_period  = $this->range_days;
				$loop_orders = new WP_Query( $args_orders );
				if ( ! $loop_orders->have_posts() ) {
					break;
				}
				foreach ( $loop_orders->posts as $order_id ) {
					$order = wc_get_order( $order_id );
					$items = $order->get_items();
					foreach ( $items as $item ) {
						$product_id = ( 'product' !== $this->product_type && ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
						if ( ! isset( $products_info[ $product_id ] ) && $this->include_deleted_products ) {
							// Deleted product.
							$products_info[ $product_id ] = array(
								'ID'              => $product_id,
								'title'           => $item['name'] . ' (' . __( 'N/A', 'woocommerce-jetpack' ) . ')',
								'category'        => '',
								'permalink'       => '',
								'price'           => '',
								'stock'           => '',
								'stock_price'     => '',
								'total_sales'     => '',
								'date_added'      => '',
								'purchase_price'  => '',
								'last_sale'       => 0,
								'sales_in_period' => array( $the_period => 0 ),
							);
						}
						if ( isset( $products_info[ $product_id ] ) ) {
							$products_info[ $product_id ]['sales_in_period'][ $the_period ] += $item['qty'];
							if ( 0 === $products_info[ $product_id ]['last_sale'] ) {
								$products_info[ $product_id ]['last_sale'] = get_the_time( 'U', $order_id );
							}
						}
					}
				}
				$offset += $block_size;
			}
		}

		/**
		 * Get_stock_summary.
		 *
		 * @version 3.9.0
		 * @param Array $products_info Get product data.
		 */
		public function get_stock_summary( $products_info ) {
			$info                        = array();
			$info['total_stock_price']   = 0;
			$info['stock_price_average'] = 0;
			$info['stock_average']       = 0;
			$info['sales_in_period_average'][ $this->range_days ] = 0;
			$stock_non_zero_number                                = 0;
			foreach ( $products_info as $product_info ) {
				if ( $product_info['stock_price'] > 0 ) {
					$info['stock_price_average']                          += $product_info['stock_price'];
					$info['stock_average']                                += $product_info['stock'];
					$info['sales_in_period_average'][ $this->range_days ] += $product_info['sales_in_period'][ $this->range_days ];
					$stock_non_zero_number++;
				}
				if ( is_numeric( $product_info['stock_price'] ) ) {
					$info['total_stock_price'] += $product_info['stock_price'];
				}
			}
			if ( 0 !== $stock_non_zero_number ) {
				$info['stock_price_average']                          /= $stock_non_zero_number;
				$info['stock_average']                                /= $stock_non_zero_number;
				$info['sales_in_period_average'][ $this->range_days ] /= $stock_non_zero_number;
			}
			return $info;
		}

		/**
		 * Gort_products_info.
		 *
		 * @param Array  $products_info Get product data.
		 * @param string $field_name Get field name.
		 * @param string $second_field_name Get field name.
		 * @param string $order_of_sorting order shorting.
		 */
		public function sort_products_info( &$products_info, $field_name, $second_field_name = '', $order_of_sorting = SORT_DESC ) {
			$field_name_array = array();
			foreach ( $products_info as $key => $row ) {
				$field_name_array[ $key ] = ( '' === $second_field_name ? $row[ $field_name ] : $row[ $field_name ][ $second_field_name ] );
			}
			array_multisort( $field_name_array, $order_of_sorting, $products_info );
		}

		/**
		 * Get_report_html.
		 *
		 * @version 6.0.0
		 */
		public function get_report_html() {
			$products_info = $this->data_products;
			$info          = $this->data_summary;
			$report_info   = $this->data_reports;
			$html          = '';
			// Style.
			$html .= '<style>';
			$html .= '.wcj_report_table_sales_columns { background-color: #F6F6F6; }';
			$html .= '.widefat { width: 100%; }';
			$html .= '</style>';
			// Products table - header.
			$html .= '<div class="main"><table class="widefat striped" style="clear:inherit"><tbody>';
			$html .= '<tr class="wcj-row">';
			$html .= '<th>#</th>';
			$html .= '<th>' . __( 'Product', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th>' . __( 'Category', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th>' . __( 'Price', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th>' . __( 'Stock', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th>' . __( 'Stock price', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th>' . __( 'Total stock price', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th class="wcj_report_table_sales_columns">' . __( 'Last sale', 'woocommerce-jetpack' ) . '</th>';
			/* translators: %s: translation added */
			$html .= '<th class="wcj_report_table_sales_columns">' . sprintf( __( 'Sales in last %s days', 'woocommerce-jetpack' ), $this->range_days ) . '</th>';
			$html .= '<th class="wcj_report_table_sales_columns">' . __( 'Total sales', 'woocommerce-jetpack' ) . '</th>';
			if ( 'understocked' === $this->report_id ) {
				$html .= '<th>' . __( 'Stock to minimum', 'woocommerce-jetpack' ) . '</th>';
			}
			$html .= '</tr>';
			// Products table - info loop.
			$total_current_stock_price          = 0;
			$product_counter                    = 0;
			$total_current_stock_purchase_price = 0;
			foreach ( $products_info as $product_info ) {
				if (
				( ( 'on_stock' === $report_info['id'] ) &&
				( 0 !== $product_info['stock'] ) ) ||

				( ( 'overstocked' === $report_info['id'] ) &&
				( $product_info['stock'] > 0 ) &&

				( ( wcj_get_timestamp_date_from_gmt() - strtotime( $product_info['date_added'] ) ) > $this->range_days * 24 * 60 * 60 ) &&
				( 0 === $product_info['sales_in_period'][ $this->range_days ] ) ) ||

				( ( 'understocked' === $report_info['id'] ) &&
				( '' !== $product_info['stock'] ) &&
				( $product_info['sales_in_period'][ $this->range_days ] > 1 ) &&
				( $product_info['stock'] < ( $product_info['sales_in_period'][ $this->range_days ] / 2 ) ) )
				) {
					$total_current_stock_price += $product_info['stock_price'];
					$product_counter++;
					$html                               .= '<tr>';
					$html                               .= '<td>' . $product_counter . '</td>';
					$html                               .= '<th> <a href=' . $product_info['permalink'] . '>' . $product_info['title'] . '</a> </th>';
					$html                               .= '<th> <a href=' . $product_info['permalink'] . '>' . $product_info['category'] . '</a> </th>';
					$purchase_price_html                 = ( $product_info['purchase_price'] > 0 ) ?
					'<br><em>' . __( 'purchase price:', 'woocommerce-jetpack' ) . '</em>' . wc_price( $product_info['purchase_price'] ) : '';
					$html                               .= '<td>' . wc_price( $product_info['price'] ) . $purchase_price_html . '</td>';
					$html                               .= '<td>' . $product_info['stock'] . '</td>';
					$purchase_stock_price_html           = ( $product_info['purchase_price'] > 0 ) ?
					'<br><em>' . __( 'stock purchase price:', 'woocommerce-jetpack' ) . '</em>' . wc_price( $product_info['purchase_price'] * $product_info['stock'] ) : '';
					$total_current_stock_purchase_price += ( $product_info['purchase_price'] > 0 ? $product_info['purchase_price'] * $product_info['stock'] : 0 );
					$html                               .= '<td>' . wc_price( $product_info['stock_price'] ) . $purchase_stock_price_html . '</td>';
					$html                               .= '<td>' . wc_price( $total_current_stock_price ) . '</td>';
					$html                               .= '<td class="wcj_report_table_sales_columns">';
					$html                               .= ( 0 === $product_info['last_sale'] ? __( 'No sales yet', 'woocommerce-jetpack' ) : date_i18n( wcj_get_option( 'date_format' ), $product_info['last_sale'] ) );
					$html                               .= '</td>';
					$profit_html                         = ( $product_info['purchase_price'] > 0 && $product_info['sales_in_period'][ $this->range_days ] > 0 ) ?
					'<br><em>' . __( 'profit:', 'woocommerce-jetpack' ) . '</em> '
						. wc_price( ( $product_info['price'] - $product_info['purchase_price'] ) * $product_info['sales_in_period'][ $this->range_days ] ) :
					'';
					$html                               .= '<td class="wcj_report_table_sales_columns">' . $product_info['sales_in_period'][ $this->range_days ] . $profit_html . '</td>';
					$html                               .= '<td class="wcj_report_table_sales_columns">' . $product_info['total_sales'] . '</td>';
					if ( $product_info['sales_in_period'][ $this->range_days ] > 0 ) {
						$stock_to_minimum = ( $product_info['sales_in_period'][ $this->range_days ] / 2 ) - $product_info['stock'];
						$stock_to_minimum = ( $stock_to_minimum > 0 ) ? round( $stock_to_minimum ) : '';
					} else {
						$stock_to_minimum = '';
					}
					if ( 'understocked' === $this->report_id ) {
						$html .= '<td>' . $stock_to_minimum . '</td>';
					}
					$html .= '</tr>';
				}
			}
			if ( $product_counter <= 0 ) {
				$html .= '<tr class="wcj-row"> <td colspan="10"> ' . __( 'Product not available', 'woocommerce-jetpack' ) . '</td> </tr>';
			}
			$html        .= '</tbody></table></div>';
			$html_header  = '<div class="inside"><h4>' . $report_info['title'] . ': ' . $report_info['desc'] . '</h4></div>';
			$html_header .= '<div class="inside chart-with-sidebar"><div class="chart-sidebar"><table class="widefat striped" style="clear:inherit"><tbody>';
			$html_header .= '<tr class="wcj-row"> <th>' . wc_price( $total_current_stock_price ) . '<br class="clear"><span style="font-weight: 400;">' . __( 'Total current stock value', 'woocommerce-jetpack' ) . '</span></th> </tr>';
			$html_header .= '<tr class="wcj-row"> <th>' . wc_price( $info['total_stock_price'] ) . '<br class="clear"><span style="font-weight: 400;">' . __( 'Total stock value', 'woocommerce-jetpack' ) . '</span></th> </tr>';
			$html_header .= '<tr class="wcj-row"> <th>' . wc_price( $info['stock_price_average'] ) . '<br class="clear"><span style="font-weight: 400;">' . __( 'Product stock value average', 'woocommerce-jetpack' ) . '</span></th> </tr>';
			$html_header .= '<tr class="wcj-row"> <th><span class="woocommerce-Price-amount amount" >' . number_format( $info['stock_average'], 2, '.', '' ) . '</span><br class="clear"><span style="font-weight: 400;">' . __( 'Product stock average', 'woocommerce-jetpack' ) . '</span></th> </tr>';
			if ( 0 !== $total_current_stock_purchase_price ) {
				$html_header .= '<tr class="wcj-row"> <th>' . wc_price( $total_current_stock_purchase_price ) . '<br class="clear"><span style="font-weight: 400;">' . __( 'Total current stock purchase price', 'woocommerce-jetpack' ) . '</span></th> </tr>';
			}
			$html_header      .= '</tbody></table>';
			$html_header      .= '</div>';
			$time_elapsed_html = '<p><em>' . __( 'Report was generated in: ', 'woocommerce-jetpack' ) . intval( microtime( true ) - $this->start_time ) . ' s </em></p>';
			$time_elapsed_html = '</div>';
			return $this->get_submenu_html() . $html_header . $html . $time_elapsed_html;
		}
	}

endif;

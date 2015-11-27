<?php
/**
 * WooCommerce Jetpack Sales Reports
 *
 * The WooCommerce Jetpack Sales Reports class.
 *
 * @version 2.3.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Reports_Sales' ) ) :

class WCJ_Reports_Sales {

	/**
	 * Constructor.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	public function __construct( $args = null ) {

	}

	/**
	 * get_report.
	 *
	 * @version 2.3.9
	 * @since   2.3.0
	 */
	public function get_report() {

		$html = '';

		$this->year          = isset( $_GET['year'] )          ? $_GET['year']          : date( 'Y' );
		$this->product_title = isset( $_GET['product_title'] ) ? $_GET['product_title'] : '';

		$html .= $this->get_products_sales();

		return $html;
	}

	/*
	 * sort_by_total_sales.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function sort_by_total_sales( $a, $b ) {
		if ( $a['sales'] == $b['sales'] ) {
			return 0;
		}
		return ( $a['sales'] < $b['sales'] ) ? 1 : -1;
	}

	/*
	 * get_products_sales.
	 *
	 * @version 2.3.9
	 * @since   2.3.0
	 */
	function get_products_sales() {

		$products_data = array();

		$years = array();

		$total_orders = 0;

		$offset = 0;
		$block_size = 96;
		while( true ) {

			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'wc-completed',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
				'date_query' => array(
					array(
						'year'  => $this->year,
					),
				),
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) break;
			while ( $loop_orders->have_posts() ) : $loop_orders->the_post();

				$order_id = $loop_orders->post->ID;
				$order = new WC_Order( $order_id );
				$items = $order->get_items();
				foreach ( $items as $item ) {

					if ( ! isset( $products_data[ $item['product_id'] ][ 'sales' ] ) ) {
						$products_data[ $item['product_id'] ][ 'sales' ] = 0;
					}
					$products_data[ $item['product_id'] ][ 'sales' ] += $item['qty'];

					$month = date( 'n', get_the_time( 'U' ) );
					$year  = date( 'Y', get_the_time( 'U' ) );
					$years[ $year ] = true;
					if ( ! isset( $products_data[ $item['product_id'] ][ 'sales_by_month' ][ $year ][ $month ] ) ) {
						$products_data[ $item['product_id'] ][ 'sales_by_month' ][ $year ][ $month ] = 0;
					}
					$products_data[ $item['product_id'] ][ 'sales_by_month' ][ $year ][ $month ] += $item['qty'];

					if ( ! isset( $products_data[ $item['product_id'] ][ 'title' ] ) ) {
						$products_data[ $item['product_id'] ][ 'title' ] = get_the_title( $item['product_id'] );
					}

					if ( ! isset( $products_data[ $item['product_id'] ][ 'last_sale' ] ) ) {
						$products_data[ $item['product_id'] ][ 'last_sale' ] = date( 'Y-m-d H:i:s', get_the_time( 'U' ) );
					}

				}

				$total_orders++;

			endwhile;

			$offset += $block_size;

		}

		usort( $products_data, array( $this, 'sort_by_total_sales' ) );

		$table_data = array();
		$the_header = array( __( 'Product', 'woocommerce-jetpack' ), );
		foreach ( $years as $year => $value ) {
			if ( $year != $this->year ) continue;
			for ( $i = 12; $i >= 1; $i-- ) {
				$the_header[] = sprintf( '%04d.%02d', $year, $i );
			}
		}
		$table_data[] = $the_header;
		foreach ( $products_data as $product_id => $the_data ) {
			if ( '' == $this->product_title || false !== stripos( $the_data['title'], $this->product_title ) ) {
				$the_row = array( $the_data['title'] . ' (' . $the_data['sales'] . ')', );
				foreach ( $years as $year => $value ) {
					if ( $year != $this->year ) continue;
					for ( $i = 12; $i >= 1; $i-- ) {
						if ( isset( $the_data['sales_by_month'][ $year ][ $i ] ) ) {
							if ( $i > 1 ) {
								$prev_month_data = ( isset( $the_data['sales_by_month'][ $year ][ $i - 1 ] ) ) ?
									$the_data['sales_by_month'][ $year ][ $i - 1 ] :
									0;
								$color = ( $prev_month_data >= $the_data['sales_by_month'][ $year ][ $i ] ) ? 'red' : 'green';
							} else {
								$color = 'black';
							}
							$the_row[] = '<span style="color:' . $color . ';">' . $the_data['sales_by_month'][ $year ][ $i ] . '</span>';
						} else {
							$the_row[] = '';
						}
					}
				}
				$table_data[] = $the_row;
			}
		}

		$menu = '';
		$menu .= '<ul class="subsubsub">';
		$menu .= '<li><a href="' . add_query_arg( 'year', date( 'Y' ) )         . '" class="' . ( ( $this->year == date( 'Y' ) ) ? 'current' : '' ) . '">' . date( 'Y' ) . '</a> | </li>';
		$menu .= '<li><a href="' . add_query_arg( 'year', ( date( 'Y' ) - 1 ) ) . '" class="' . ( ( $this->year == ( date( 'Y' ) - 1 ) ) ? 'current' : '' ) . '">' . ( date( 'Y' ) - 1 ) . '</a> | </li>';
		$menu .= '</ul>';
		$menu .= '<br class="clear">';

		$filter_form = '';
		$filter_form .= '<form method="get" action="">';
		$filter_form .= '<input type="hidden" name="page" value="' . $_GET['page'] . '" />';
		$filter_form .= '<input type="hidden" name="tab" value="' . $_GET['tab'] . '" />';
		$filter_form .= '<input type="hidden" name="report" value="' . $_GET['report'] . '" />';
		$filter_form .= '<input type="text" name="product_title" title="" value="' . $this->product_title . '" /><input type="submit" value="' . __( 'Filter', 'woocommerce' ) . '" />';
		$filter_form .= '</form>';

		return '<p>' . $menu . '</p>' . '<p>' . $filter_form . '</p>' . wcj_get_table_html( $table_data, array( 'table_class' => 'widefat' ) );
	}
}

endif;

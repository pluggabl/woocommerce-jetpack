<?php
/**
 * Booster for WooCommerce - Reports - Customers
 *
 * @version 3.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Reports_Customers' ) ) :

class WCJ_Reports_Customers {

	public $country_sets;

	/**
	 * Constructor.
	 */
	function __construct( $args = null ) {
		$this->country_sets = ( isset( $args['group_countries'] ) && 'yes' === $args['group_countries'] ) ?
			include( 'countries/wcj-country-sets.php' ) : array();
	}

	/**
	 * get_report function.
	 */
	function get_report() {

		$report_type = isset( $_GET['country'] ) ? $_GET['country'] : 'all_countries';

		$html = '';

		// Get customers
		$customers = get_users( 'role=customer&orderby=registered&order=DESC' );
		$total_customers = count( $customers );
		if ( $total_customers < 1 ) {
			return '<h5>' . __( 'No customers found.', 'woocommerce-jetpack' ) . '</h5>';
		}

		// Count data
		$the_data = $this->get_data( $customers, $report_type );

		// Get HTML
		$html = $this->get_html( $the_data, $total_customers, $report_type );

		/*
		$no_sales_countries = array();
		foreach( WC_Countries::get_countries() as $country_code => $country_name ) {
			if ( ! isset( $the_data[ $country_code ] ) ) $no_sales_countries[] = $country_code;// . ' - ' . $country_name;
		}
		return $html . count( $no_sales_countries ) . ': ' . implode( '<br>', $no_sales_countries );
		*/

		return $html;
	}

	/**
	 * get_data function.
	 */
	function get_data( $customers, $report_type = 'all_countries' ) {

		foreach ( $customers as $customer ) {

			// Get country (billing or shipping)
			$user_meta        = get_user_meta( $customer->ID );
			$billing_country  = isset( $user_meta['billing_country'][0] )  ? $user_meta['billing_country'][0]  : '';
			$shipping_country = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
			$customer_country = ( '' == $billing_country ) ? $shipping_country : $billing_country;
			// If available - change to country set instead
			foreach ( $this->country_sets as $id => $countries ) {
				if ( in_array( $customer_country, $countries ) ) {
					$customer_country = $id;
					break;
				}
			}
			// N/A
			if ( '' == $customer_country ) {
				$customer_country = __( 'Non Available', 'woocommerce-jetpack' );
			}

			if ( 'all_countries' === $report_type ) {
				// Counter
				if ( ! isset( $result[ $customer_country ]['customer_counter'] ) ) {
					$result[ $customer_country ]['customer_counter'] = 0;
				}
				$result[ $customer_country ]['customer_counter']++;
			} else { // single country
				if ( ! isset( $result[ $customer_country ]['total_spent'] ) ) {
					$result[ $customer_country ]['total_spent'] = array(
						array(
							__( 'Customer Name', 'woocommerce-jetpack' ),
							__( 'Email', 'woocommerce-jetpack' ),
							__( 'Total Spent', 'woocommerce-jetpack' ),
							__( 'Registered', 'woocommerce-jetpack' ),
						),
					);
				}
				$customer_total_spent = wc_get_customer_total_spent( $customer->ID );
//				if ( '' != $customer_total_spent ) {
					$result[ $customer_country ]['total_spent'][] = array(
						$customer->data->display_name,
						$customer->data->user_email,
						$customer_total_spent,
						$customer->data->user_registered,
					);
//				}
			}
		}
		if ( 'all_countries' === $report_type ) {
			uasort( $result, array( $this, 'custom_sort_for_data' ) );
		}
		return $result;
	}

	/**
	 * custom_sort_for_data.
	 */
	function custom_sort_for_data( $a, $b ) {
		if ( $a['customer_counter'] == $b['customer_counter'] ) {
			return 0;
		}
		return ( $a['customer_counter'] > $b['customer_counter'] ) ? -1 : 1;
	}

	/**
	 * get_data function.
	 *
	 * @version 3.3.0
	 */
	function get_html( $data, $total_customers, $report_type = 'all_countries' ) {
		$html = '';
		if ( 'all_countries' === $report_type ) {
			$html .= '<h5>' . __( 'Total customers', 'woocommerce-jetpack' ) . ': ' . $total_customers . '</h5>';
			$html .= '<table class="widefat" style="width:100% !important;"><tbody>';
			$html .= '<tr>';
			$html .= '<th></th>';
			$html .= '<th>' . __( 'Country Code', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th>' . __( 'Customers Count', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th>' . __( 'Percent of total', 'woocommerce-jetpack' ) . '</th>';
			$html .= '<th></th>';
			$html .= '<th></th>';
			$html .= '</tr>';
			$i = 0;
			foreach ( $data as $country_code => $result ) {
				$result = $result['customer_counter'];
				$html .= '<tr>';
				$html .= '<td>' . ++$i . '</td>';
				$country_code_link = '<a href="' . add_query_arg( array ( 'country' => $country_code ) ) . '">' . $country_code . '</a>';
				$html .= ( 2 == strlen( $country_code ) ) ? '<td>' . $country_code_link . '</td>' : '<td>' . $country_code . '</td>' ;
				$html .= '<td>' . $result . '</td>';
				$html .= ( 0 != $total_customers ) ? '<td>' . number_format( ( $result / $total_customers ) * 100, 2 ) . '%' . '</td>' : '<td></td>';
				$country_flag_img = wcj_get_country_flag_by_code( $country_code );
				$country_flag_img_link = '<a href="' . add_query_arg( array ( 'country' => $country_code ) ) . '">' .
					$country_flag_img . ' ' . wcj_get_country_name_by_code( $country_code ) . '</a>';
				$html .= ( 2 == strlen( $country_code ) ) ? '<td>' . $country_flag_img_link . '</td>' : '<td></td>';
				$html .= '</tr>';
			}
			$html .= '</tbody></table>';
		} else { // single country
			$country_code = $report_type;
			$html .= '<h5>' . __( 'Report for:', 'woocommerce-jetpack' ) . ' ' . wcj_get_country_name_by_code( $country_code ) . ' [' . $country_code .  ']' . '</h5>';
			$html .= ( 2 == strlen( $country_code ) ) ? wcj_get_table_html( $data[ $country_code ]['total_spent'], array( 'table_class' => 'widefat', ) ) : '';
		}
		return $html;
	}
}

endif;

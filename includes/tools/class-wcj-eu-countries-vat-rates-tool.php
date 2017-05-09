<?php
/**
 * Booster for WooCommerce - Tool - EU Countries VAT Rates
 *
 * @version 2.5.0
 * @since   2.3.10
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_EU_Countries_VAT_Rates_Tool' ) ) :

class WCJ_EU_Countries_VAT_Rates_Tool {

	/**
	 * Constructor.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function __construct() {
		add_action( 'init', array( $this, 'add_eu_countries_vat_rates' ) );
	}

	/**
	 * add_eu_countries_vat_rates.
	 *
	 * @version 2.5.0
	 * @since   2.3.10
	 */
	function add_eu_countries_vat_rates() {
		if ( ! isset( $_POST['add_eu_countries_vat_rates'] ) ) return;
		if ( ! wcj_is_user_role( 'administrator' ) && ! is_shop_manager() ) return;
		$loop = 0;
		foreach ( wcj_get_european_union_countries_with_vat() as $country => $rate ) {
			$tax_rate = array(
				'tax_rate_country'  => $country,
				'tax_rate'          => $rate,

				'tax_rate_name'     => isset( $_POST['wcj_tax_name'] ) ? $_POST['wcj_tax_name'] : __( 'VAT', 'woocommerce' ),
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,

				'tax_rate_order'    => $loop++,
				'tax_rate_class'    => '',
			);
			$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
			WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, '' );
			WC_Tax::_update_tax_rate_cities( $tax_rate_id, '' );
		}
	}

	/**
	 * create_eu_countries_vat_rates_tool.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function create_eu_countries_vat_rates_tool( $header_html ) {

		$the_tool_html = '';
		$the_tool_html .= $header_html;

//		$the_tool_html .= '<h4>' . __( 'Settings', 'woocommerce-jetpack' ) . '</h4>';
		$data = array();
		$the_name = ( isset( $_POST['wcj_tax_name'] ) ) ? $_POST['wcj_tax_name'] : __( 'VAT', 'woocommerce' );
		$data[] = array(
			__( 'Name', 'woocommerce-jetpack' ),
			'<input class="input-text" type="text" name="wcj_tax_name" value="' . $the_name . '">',
		);
		$data[] = array(
			'',
			'<input class="button-primary" type="submit" name="add_eu_countries_vat_rates" value="' . __( 'Add EU Countries VAT Rates', 'woocommerce-jetpack' ) . '">' . ' ' . __( 'Note: will add duplicates.', 'woocommerce-jetpack' ),
		);
		$the_tool_html .= '<p>';
		$the_tool_html .= '<form method="post" action="">';
		$the_tool_html .=  wcj_get_table_html( $data, array( 'table_heading_type' => 'vertical', ) );
		$the_tool_html .= '</form>';
		$the_tool_html .= '</p>';

		$the_tool_html .= '<h4>' . __( 'List of EU VAT rates to be added', 'woocommerce-jetpack' ) . '</h4>';
		$eu_vat_rates = wcj_get_european_union_countries_with_vat();
		$data = array();
		$data[] = array(
			'',
			__( 'Country', 'woocommerce-jetpack' ),
			__( 'Rate', 'woocommerce-jetpack' ),
		);
		$i = 1;
		foreach ( $eu_vat_rates as $country => $rate ) {
			$data[] = array( $i++, $country . ' - ' . wcj_get_country_name_by_code( $country ), $rate . '%' );
		}
		$the_tool_html .= wcj_get_table_html( $data, array( 'table_class' => 'widefat', 'table_style' => 'width:50%;min-width:300px;', ) );

		$the_tool_html .= '<h4>' . __( 'Current standard tax rates', 'woocommerce-jetpack' ) . '</h4>';
		$standard_tax_rates = wcj_get_rates_for_tax_class( '' );
		$data = array();
		$data[] = array(
			'',
			__( 'Country', 'woocommerce-jetpack' ),
			__( 'Rate', 'woocommerce-jetpack' ),
			__( 'Name', 'woocommerce-jetpack' ),
		);
		$i = 1;
		foreach ( $standard_tax_rates as $tax_rate_object ) {
			$data[] = array( $i++, $tax_rate_object->tax_rate_country . ' - ' . wcj_get_country_name_by_code( $tax_rate_object->tax_rate_country ), $tax_rate_object->tax_rate . '%', $tax_rate_object->tax_rate_name, );
		}
		$the_tool_html .= wcj_get_table_html( $data, array( 'table_class' => 'widefat', 'table_style' => 'width:75%;min-width:300px;', ) );

		echo $the_tool_html;
	}
}

endif;

return new WCJ_EU_Countries_VAT_Rates_Tool();

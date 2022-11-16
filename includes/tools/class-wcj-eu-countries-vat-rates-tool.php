<?php
/**
 * Booster for WooCommerce - Tool - EU Countries VAT Rates
 *
 * @version 5.6.8
 * @since   2.3.10
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_EU_Countries_VAT_Rates_Tool' ) ) :

		/**
		 * WCJ_EU_Countries_VAT_Rates_Tool.
		 *
		 * @version 2.5.0
		 * @since   2.3.10
		 */
	class WCJ_EU_Countries_VAT_Rates_Tool {

		/**
		 * Constructor.
		 *
		 * @version 2.3.10
		 * @since   2.3.10
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'add_eu_countries_vat_rates' ) );
		}

		/**
		 * Add_eu_countries_vat_rates.
		 *
		 * @version 5.6.8
		 * @since   2.3.10
		 */
		public function add_eu_countries_vat_rates() {
			$wpnonce = isset( $_REQUEST['add_eu_countries_vat_rates-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['add_eu_countries_vat_rates-nonce'] ), 'add_eu_countries_vat_rates' ) : false;
			if ( ! $wpnonce || ! isset( $_POST['add_eu_countries_vat_rates'] ) ) {
				return;
			}
			if ( ! wcj_is_user_role( 'administrator' ) && ! is_shop_manager() ) {
				return;
			}
			$loop = 0;
			foreach ( wcj_get_european_union_countries_with_vat() as $country => $rate ) {
				$tax_rate    = array(
					'tax_rate_country'  => $country,
					'tax_rate'          => $rate,

					'tax_rate_name'     => isset( $_POST['wcj_tax_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_tax_name'] ) ) : __( 'VAT', 'woocommerce' ),
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
		 * Create_eu_countries_vat_rates_tool.
		 *
		 * @version 5.6.8
		 * @since   2.3.10
		 * @param string $header_html Get html data.
		 */
		public function create_eu_countries_vat_rates_tool( $header_html ) {
			$wpnonce = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			if ( ! $wpnonce ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wcj-tools' ) );
				exit;
			}

			$the_tool_html  = '';
			$the_tool_html .= '<div class="wcj-setting-jetpack-body wcj_tools_cnt_main">';
			$the_tool_html .= $header_html;

			$data           = array();
			$the_name       = isset( $_POST['wcj_tax_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_tax_name'] ) ) : __( 'VAT', 'woocommerce' );
			$data[]         = array(
				__( 'Name', 'woocommerce-jetpack' ),
				'<input class="input-text" type="text" name="wcj_tax_name" value="' . $the_name . '">',
				'',
			);
			$data[]         = array(
				'',
				'<input class="button-primary" type="submit" name="add_eu_countries_vat_rates" value="' . __( 'Add EU Countries VAT Rates', 'woocommerce-jetpack' ) . '">' . wp_nonce_field( 'add_eu_countries_vat_rates', 'add_eu_countries_vat_rates-nonce' ),
				__( 'Note: will add duplicates.', 'woocommerce-jetpack' ),
			);
			$the_tool_html .= '<form method="post" action="">';
			$the_tool_html .= wcj_get_table_html(
				$data,
				array(
					'table_class'        => 'widefat striped',
					'table_heading_type' => 'vertical',
				)
			);
			$the_tool_html .= '</form>';
			$the_tool_html .= '<h4>' . __( 'List of EU VAT rates to be added', 'woocommerce-jetpack' ) . '</h4>';
			$eu_vat_rates   = wcj_get_european_union_countries_with_vat();
			$data           = array();
			$data[]         = array(
				'',
				__( 'Country', 'woocommerce-jetpack' ),
				__( 'Rate', 'woocommerce-jetpack' ),
			);
			$i              = 1;
			foreach ( $eu_vat_rates as $country => $rate ) {
				$data[] = array( $i++, $country . ' - ' . wcj_get_country_name_by_code( $country ), $rate . '%' );
			}
			$the_tool_html .= wcj_get_table_html(
				$data,
				array(
					'table_class' => 'widefat striped',
					'table_style' => 'width:50%;min-width:300px;',
				)
			);

			$the_tool_html     .= '<h4>' . __( 'Current standard tax rates', 'woocommerce-jetpack' ) . '</h4>';
			$standard_tax_rates = wcj_get_rates_for_tax_class( '' );
			$data               = array();
			$data[]             = array(
				'',
				__( 'Country', 'woocommerce-jetpack' ),
				__( 'Rate', 'woocommerce-jetpack' ),
				__( 'Name', 'woocommerce-jetpack' ),
			);
			$i                  = 1;
			foreach ( $standard_tax_rates as $tax_rate_object ) {
				$data[] = array( $i++, $tax_rate_object->tax_rate_country . ' - ' . wcj_get_country_name_by_code( $tax_rate_object->tax_rate_country ), $tax_rate_object->tax_rate . '%', $tax_rate_object->tax_rate_name );
			}
			$the_tool_html .= wcj_get_table_html(
				$data,
				array(
					'table_class' => 'widefat',
					'table_style' => 'width:75%;min-width:300px;',
				)
			);
			$the_tool_html .= '</div>';
			echo wp_kses_post( $the_tool_html );
		}
	}

endif;

return new WCJ_EU_Countries_VAT_Rates_Tool();

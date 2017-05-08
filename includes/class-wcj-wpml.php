<?php
/**
 * Booster for WooCommerce - Module - WPML
 *
 * @version 2.8.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_WPML' ) ) :

class WCJ_WPML extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'wpml';
		$this->short_desc = __( 'Booster WPML', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster for WooCommerce basic WPML support.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-booster-wpml';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'create_wpml_xml_file_tool' ), PHP_INT_MAX );
		}

		$this->notice = '';
	}

	/**
	 * create_wpml_xml_file.
	 *
	 * @version 2.5.0
	 * @since   2.4.1
	 */
	function create_wpml_xml_file_tool() {
		if ( ! isset( $_GET['create_wpml_xml_file'] ) || ! wcj_is_user_role( 'administrator' ) ) {
			return;
		}
		if ( ! isset( $_GET['section'] ) || 'wpml' != $_GET['section'] ) {
			return;
		}
		$this->create_wpml_xml_file();
		$this->notice = __( 'File wpml-config.xml successfully regenerated!', 'woocommerce-jetpack' );
	}

	/**
	 * create_wpml_xml_file.
	 *
	 * @version 2.4.4
	 */
	function create_wpml_xml_file() {
		$file_path = wcj_plugin_path() . '/wpml-config.xml';
		if ( false !== ( $handle = fopen( $file_path, 'w' ) ) ) {
			fwrite( $handle, '<wpml-config>' . PHP_EOL );
			fwrite( $handle, "\t" );
			fwrite( $handle, '<admin-texts>' . PHP_EOL );
			$sections = apply_filters( 'wcj_settings_sections', array() );
			foreach ( $sections as $section => $section_title ) {
				if ( $this->is_wpml_section( $section ) ) {
					$settings = apply_filters( 'wcj_settings_' . $section, array() );
					foreach ( $settings as $value ) {
						if ( $this->is_wpml_value( $value ) ) {
							fwrite( $handle, "\t\t" );
							fwrite( $handle, '<key name="' . $value['id'] . '" />' . PHP_EOL );
						}
					}
				}
			}
			fwrite( $handle, "\t" );
			fwrite( $handle, '</admin-texts>' . PHP_EOL );
			fwrite( $handle, '</wpml-config>' . PHP_EOL );
			fclose( $handle );
		}
	}

	/**
	 * is_wpml_section.
	 *
	 * @version 2.8.0
	 * @since   2.4.4
	 */
	function is_wpml_section( $section ) {
		$sections_to_skip = array(

			'price_by_country',
			'multicurrency',
			'multicurrency_base_price',
			'currency',
			'currency_external_products',
			'bulk_price_converter',
			'currency_exchange_rates',

			'product_listings',
			'related_products',
			'sku',
			'product_add_to_cart',
			'purchase_data',
			'crowdfunding',

			'payment_gateways',
			'payment_gateways_icons',
			'payment_gateways_per_category',
			'payment_gateways_currency',
			'payment_gateways_min_max',
			'payment_gateways_by_country',

			'shipping',
			'shipping_calculator',
			'address_formats',
			'order_numbers',
			'order_custom_statuses',

			'pdf_invoicing',
			'pdf_invoicing_numbering',
			'pdf_invoicing_styling',
			'pdf_invoicing_page',
			'pdf_invoicing_emails',

			'general',
			'old_slugs',
			'reports',
			'admin_tools',
			'emails',
			'wpml',

		);
		return ( ! in_array( $section, $sections_to_skip ) );
	}

	/**
	 * is_wpml_value.
	 *
	 * @version 2.8.0
	 */
	function is_wpml_value( $value ) {

		// Type
		$is_type_ok = ( 'textarea' === $value['type'] || 'text' === $value['type'] );

		// ID
		$values_to_skip = array(

			'wcj_product_info_products_to_exclude',

			'wcj_custom_product_tabs_title_global_hide_in_product_ids_',
			'wcj_custom_product_tabs_title_global_hide_in_cats_ids_',
			'wcj_custom_product_tabs_title_global_show_in_product_ids_',
			'wcj_custom_product_tabs_title_global_show_in_cats_ids_',

			'wcj_empty_cart_div_style',

		);
		$is_id_ok = true;
		foreach ( $values_to_skip as $value_to_skip ) {
			if ( false !== strpos( $value['id'], $value_to_skip ) ) {
				$is_id_ok = false;
				break;
			}
		}

		// Final return
		return ( $is_type_ok && $is_id_ok );
	}

}

endif;

return new WCJ_WPML();

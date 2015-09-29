<?php
/**
 * WooCommerce Jetpack WPML
 *
 * The WooCommerce Jetpack WPML class.
 *
 * @version 2.2.8
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_WPML' ) ) :

class WCJ_WPML extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.2.8
	 */
	function __construct() {

		$this->id         = 'wpml';
		$this->short_desc = __( 'WPML', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster for WooCommerce basic WPML support.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'woocommerce_init', array( $this, 'create_wpml_xml_file' ), PHP_INT_MAX );
		}

		$this->notice = '';
	}

	/**
	 * get_settings.
	 *
	 * @version 2.2.8
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => $this->short_desc . ' ' . __( 'Tools', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcj_' . $this->id . '_tools_options'
			),
			array(
				'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
				'id'       => 'wcj_' . $this->id . '_module_tools',
				'type'     => 'custom_link',
				'link'     => '<pre><a href="' . add_query_arg( 'create_wpml_xml_file', '1' ) . '">' . __( 'Regenerate wpml-config.xml file', 'woocommerce-jetpack' ) . '</a></pre>'
				              . '<pre>' . $this->notice . '</pre>',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_' . $this->id . '_tools_options'
			),
		);
		$this->notice = '';
		return $this->add_enable_module_setting( $settings );
	}

	/**
	 * create_wpml_xml_file.
	 *
	 * @version 2.2.8
	 */
	function create_wpml_xml_file() {

		if ( ! isset( $_GET['create_wpml_xml_file'] ) || ! is_super_admin() ) {
			return;
		}

		if ( ! isset( $_GET['section'] ) || 'wpml' != $_GET['section'] ) {
			return;
		}

		$file_path = wcj_plugin_path() . '/wpml-config.xml';
		if ( false !== ( $handle = fopen( $file_path, 'w' ) ) ) {

			fwrite( $handle, '<wpml-config>' . PHP_EOL );
			fwrite( $handle, "\t" );
			fwrite( $handle, '<admin-texts>' . PHP_EOL );

//			$sections = $this->get_sections();
			$sections = apply_filters( 'wcj_settings_sections', array() );
			foreach ( $sections as $section => $section_title ) {

//				$settings = $this->get_settings( $section );
				$settings = apply_filters( 'wcj_settings_' . $section, array() );

				foreach ( $settings as $value ) {
					if ( $this->is_wpml_value( $section, $value ) ) {
						fwrite( $handle, "\t\t" );
						fwrite( $handle, '<key name="' . $value['id'] . '" />' . PHP_EOL );
					}
				}
			}

			fwrite( $handle, "\t" );
			fwrite( $handle, '</admin-texts>' . PHP_EOL );
			fwrite( $handle, '</wpml-config>' . PHP_EOL );

			fclose( $handle );

			$this->notice = __( 'File wpml-config.xml successfully regenerated!', 'woocommerce-jetpack' ) /* . PHP_EOL
			              . __( 'File path:', 'woocommerce-jetpack' ) . ' ' . $file_path . PHP_EOL */;

		}

	}

	/**
	 * is_wpml_value.
	 */
	function is_wpml_value( $section, $value ) {

		// Type
		$is_type_ok = ( 'textarea' === $value['type'] || 'text' === $value['type'] ) ? true : false;

		// Section
		$sections_with_wpml = array(
			'call_for_price',
			'price_labels',
			'add_to_cart',
			'more_button_labels',

			'product_info',
			'product_tabs',
			'sorting',
			'product_input_fields',

			'cart',
			'mini_cart',
			'checkout_core_fields',
			'checkout_custom_fields',
			'checkout_custom_info',

			'orders',

			'pdf_invoicing_templates',
			'pdf_invoicing_header',
			'pdf_invoicing_footer',
			'pdf_invoicing_display',

			'pdf_invoices',
		);
		$is_section_ok = ( in_array( $section, $sections_with_wpml ) ) ? true : false;

		// ID
		$values_to_skip = array(
			'wcj_product_info_products_to_exclude',

			'wcj_custom_product_tabs_title_global_hide_in_product_ids_',
			'wcj_custom_product_tabs_title_global_hide_in_cats_ids_',
			'wcj_custom_product_tabs_title_global_show_in_product_ids_',
			'wcj_custom_product_tabs_title_global_show_in_cats_ids_',

			'wcj_empty_cart_div_style',
		);
//		$is_id_ok = ( in_array( $value['id'], $values_to_skip ) ) ? false : true;
		$is_id_ok = true;
		foreach ( $values_to_skip as $value_to_skip ) {
			if ( false !== strpos( $value['id'], $value_to_skip ) ) {
				$is_id_ok = false;
				break;
			}
		}

		// Final return
		return ( $is_type_ok && $is_section_ok && $is_id_ok );
	}

}

endif;

return new WCJ_WPML();

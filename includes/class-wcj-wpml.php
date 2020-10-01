<?php
/**
 * Booster for WooCommerce - Module - WPML
 *
 * @version 5.1.0
 * @since   2.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_WPML' ) ) :

class WCJ_WPML extends WCJ_Module {

	/**
	 * @var WCJ_WPML_Meta_Sync
	 */
	protected $bkg_process_meta_sync;

	/**
	 * Constructor.
	 *
	 * @version 5.1.0
	 */
	function __construct() {

		$this->id         = 'wpml';
		$this->short_desc = __( 'Booster WPML', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster for WooCommerce basic WPML support.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-booster-wpml';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'create_wpml_xml_file_tool' ), PHP_INT_MAX );
			if ( 'yes' === wcj_get_option( 'wcj_wpml_config_xml_auto_regenerate', 'no' ) ) {
				add_action( 'wcj_version_updated', array( $this, 'create_wpml_xml_file' ) );
			}
			add_action( 'wcml_switch_currency', array( $this, 'switch_currency_using_multicurrency' ) );
			add_filter( 'wcml_client_currency', function ( $currency ) {
				$this->switch_currency_using_multicurrency( $currency );
				return $currency;
			} );

			// WPML Meta Synchronizer
			add_action( 'updated_postmeta', array( $this, 'update_meta_on_correspondent_language_product' ), 10, 4 );
		}

		$this->notice = '';
	}

	/**
	 * update_meta_on_correspondent_language_product.
	 *
	 * @version 5.1.0
	 * @since   5.1.0
	 *
	 * @param $meta_id
	 * @param $object_id
	 * @param $meta_key
	 * @param $meta_value
	 */
	function update_meta_on_correspondent_language_product( $meta_id, $object_id, $meta_key, $meta_value ) {
		$allowed_metas_like = array( '_wcj_price_by_country_' );
		if (
			'no' === wcj_get_option( 'wcj_wpml_sync_metas', 'no' )
			|| ( 'product' != get_post_type( $object_id ) && 'product_variation' != get_post_type( $object_id ) )
			|| count( array_filter( $allowed_metas_like, function ( $item ) use ( $meta_key ) {
				return strpos( $meta_key, $item ) !== false;
			} ) ) == 0
		) {
			return;
		}
		$current_lang   = apply_filters( 'wpml_current_language', null );
		$languages      = apply_filters( 'wpml_active_languages', null );
		$wpml_post_info = apply_filters( 'wpml_post_language_details', null, $object_id );
		if (
			empty( $wpml_post_info )
			|| ! isset( $wpml_post_info['language_code'] )
			|| $current_lang != $wpml_post_info['language_code']
		) {
			return;
		}
		unset( $languages[ $current_lang ] );
		foreach ( $languages as $language ) {
			$translated_post_id = apply_filters( 'wpml_object_id', $object_id, 'post', false, $language['code'] );
			if ( ! empty( $translated_post_id ) && $translated_post_id != $object_id ) {
				update_post_meta( $translated_post_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * Set Booster currency based on WPML currency
	 *
	 * @version 5.1.0
	 * @since   4.5.0
	 *
	 * @param $currency
	 */
	function switch_currency_using_multicurrency( $currency ) {
		if (
			'no' === wcj_get_option( 'wcj_wpml_switch_booster_currency', 'no' ) ||
			! WCJ()->modules['multicurrency']->is_enabled()
		) {
			return;
		}
		wcj_session_maybe_start();
		wcj_session_set( 'wcj-currency', $currency );
	}

	/**
	 * get_default_modules_to_skip.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 */
	function get_default_modules_to_skip() {
		return array(
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
	}

	/**
	 * get_default_values_to_skip.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 */
	function get_default_values_to_skip() {
		return 'wcj_product_info_products_to_exclude|' .
			'wcj_custom_product_tabs_title_global_hide_in_product_ids_|wcj_custom_product_tabs_title_global_hide_in_cats_ids_|' .
			'wcj_custom_product_tabs_title_global_show_in_product_ids_|wcj_custom_product_tabs_title_global_show_in_cats_ids_|' .
			'wcj_empty_cart_div_style|';
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
	 * @version 3.8.0
	 * @see     https://wpml.org/documentation/support/language-configuration-files/#admin-texts
	 */
	function create_wpml_xml_file() {
		$file_path = wcj_plugin_path() . '/wpml-config.xml';
		if ( false !== ( $handle = fopen( $file_path, 'w' ) ) ) {
			fwrite( $handle, '<wpml-config>' . PHP_EOL );
			fwrite( $handle, "\t" );
			fwrite( $handle, '<admin-texts>' . PHP_EOL );
			$sections = apply_filters( 'wcj_settings_sections', array() );
			$added_keys = array();
			foreach ( $sections as $section => $section_title ) {
				if ( $this->is_wpml_section( $section ) ) {
					$settings = apply_filters( 'wcj_settings_' . $section, array() );
					foreach ( $settings as $value ) {
						if ( $this->is_wpml_value( $value ) ) {
							if ( false === ( $pos = strpos( $value['id'], '[' ) ) ) {
								fwrite( $handle, "\t\t" );
								fwrite( $handle, '<key name="' . $value['id'] . '" />' . PHP_EOL );
							} else {
								$key_name = substr( $value['id'], 0, $pos );
								if ( in_array( $key_name, $added_keys ) ) {
									continue;
								}
								fwrite( $handle, "\t\t" );
								fwrite( $handle, '<key name="' . $key_name . '"><key name="*" /></key>' . PHP_EOL );
								$added_keys[] = $key_name;
							}
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
	 * @version 3.8.0
	 * @since   2.4.4
	 */
	function is_wpml_section( $section ) {
		return ( ! in_array( $section, wcj_get_option( 'wcj_wpml_config_xml_modules_to_skip', $this->get_default_modules_to_skip() ) ) );
	}

	/**
	 * is_wpml_value.
	 *
	 * @version 3.8.0
	 */
	function is_wpml_value( $value ) {

		// Type
		$is_type_ok = ( 'textarea' === $value['type'] || 'custom_textarea' === $value['type'] || 'text' === $value['type'] );

		// ID
		$values_to_skip = array_filter( array_map( 'trim', explode( '|', wcj_get_option( 'wcj_wpml_config_xml_values_to_skip', $this->get_default_values_to_skip() ) ) ) );
		$is_id_ok       = true;
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

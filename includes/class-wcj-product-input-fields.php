<?php
/**
 * Booster for WooCommerce - Module - Product Input Fields
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Input_Fields' ) ) :

class WCJ_Product_Input_Fields extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'product_input_fields';
		$this->short_desc = __( 'Product Input Fields', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce product input fields.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-input-fields';
		parent::__construct();

		include_once( 'input-fields/class-wcj-product-input-fields-abstract.php' );

		if ( $this->is_enabled() ) {

			add_action( 'woocommerce_delete_order_items', array( $this, 'delete_file_uploads' ) );

			add_action( 'init', array( $this, 'handle_downloads' ) );

			include_once( 'input-fields/class-wcj-product-input-fields-global.php' );
			include_once( 'input-fields/class-wcj-product-input-fields-per-product.php' );

			if ( 'yes' === get_option( 'wcj_product_input_fields_global_enabled' ) || 'yes' === get_option( 'wcj_product_input_fields_local_enabled' ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'init',               array( $this, 'register_scripts' ) );
			}
		}
	}

	/**
	 * delete_file_uploads.
	 *
	 * @version 2.2.2
	 * @since   2.2.2
	 */
	function delete_file_uploads( $postid ) {
		$the_order = wc_get_order( $postid );
		$the_items = $the_order->get_items();
		foreach ( $the_items as $item ) {
			foreach ( $item as $item_field ) {
				$item_field = maybe_unserialize( $item_field );
				if ( is_array( $item_field ) && isset( $item_field['wcj_type'] ) && 'file' === $item_field['wcj_type'] ) {
					unlink( $item_field['tmp_name'] );
				}
			}
		}
	}

	/**
	 * handle_downloads.
	 *
	 * @version 2.5.0
	 * @since   2.2.2
	 */
	function handle_downloads() {
		if ( isset ( $_GET['wcj_download_file'] ) ) {
			$file_name = $_GET['wcj_download_file'];
			$upload_dir = wcj_get_wcj_uploads_dir( 'input_fields_uploads' );
			$file_path = $upload_dir . '/' . $file_name;
			if ( wcj_is_user_role( 'administrator' ) || is_shop_manager() ) {
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Cache-Control: private", false );
				header( 'Content-disposition: attachment; filename=' . $file_name );
				header( "Content-Transfer-Encoding: binary" );
				header( "Content-Length: ". filesize( $file_path ) );
				readfile( $file_path );
				exit();
			}
		}
	}

	/**
	 * register_script.
	 *
	 * @version 2.9.0
	 */
	function register_scripts() {
		wp_register_script( 'wcj-product-input-fields', wcj_plugin_url() . '/includes/js/wcj-product-input-fields.js', array( 'jquery' ), WCJ()->version, true );
	}

	/**
	 * enqueue_checkout_script.
	 */
	function enqueue_scripts() {
		if( ! is_product() ) {
			return;
		}
		wp_enqueue_script( 'wcj-product-input-fields' );
	}

	/**
	 * get_options.
	 */
	function get_options() {
		$product_input_fields_abstract = new WCJ_Product_Input_Fields_Abstract();
		$product_input_fields_abstract->scope = 'global';
		return $product_input_fields_abstract->get_options();
	}

}

endif;

return new WCJ_Product_Input_Fields();

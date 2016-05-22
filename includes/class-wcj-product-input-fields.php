<?php
/**
 * WooCommerce Jetpack Product Input Fields
 *
 * The WooCommerce Jetpack Product Input Fields class.
 *
 * @version 2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Input_Fields' ) ) :

class WCJ_Product_Input_Fields extends WCJ_Module {

	/**
	 * Constructor.
	 * @version 2.4.8
	 */
	public function __construct() {

		$this->id         = 'product_input_fields';
		$this->short_desc = __( 'Product Input Fields', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce product input fields.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-input-fields/';
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
	public function delete_file_uploads( $postid ) {
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
	public function handle_downloads() {
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
	 */
	public function register_scripts() {
		wp_register_script( 'wcj-product-input-fields', WCJ()->plugin_url() . '/includes/js/product-input-fields.js', array( 'jquery' ), false, true );
	}

	/**
	 * enqueue_checkout_script.
	 */
	public function enqueue_scripts() {
		if( ! is_product() ) return;
		wp_enqueue_script( 'wcj-product-input-fields' );
	}

	/**
	 * get_options.
	 */
	public function get_options() {
		$product_input_fields_abstract = new WCJ_Product_Input_Fields_Abstract();
		$product_input_fields_abstract->scope = 'global';
		return $product_input_fields_abstract->get_options();
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'    => __( 'Product Input Fields per Product Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'Add custom input fields to product\'s single page for customer to fill before adding product to cart.', 'woocommerce-jetpack' ) . ' '
					. __( 'When enabled this module will add "Product Input Fields" tab to each product\'s "Edit" page.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_local_options',
			),

			array(
				'title'    => __( 'Product Input Fields - per Product', 'woocommerce-jetpack' ),
				'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
				'desc_tip' => __( 'Add custom input field on per product basis.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_local_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),

			array(
				'title'    => __( 'Default Number of Product Input Fields per Product', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_local_total_number_default',
				'desc_tip' => __( 'You will be able to change this number later as well as define the fields, for each product individually, in product\'s "Edit".', 'woocommerce-jetpack' ),
				'default'  => 1,
				'type'     => 'number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array(
						'step' => '1',
						'min'  => '1',
					)
				),
			),

			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_input_fields_local_options',
			),

			array(
				'title'    => __( 'Product Input Fields Global Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'Add custom input fields to product\'s single page for customer to fill before adding product to cart.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_global_options',
			),

			array(
				'title'    => __( 'Product Input Fields - All Products', 'woocommerce-jetpack' ),
				'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
				'desc_tip' => __( 'Add custom input fields to all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_global_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),

			array(
				'title'    => __( 'Product Input Fields Number', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_global_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array(
						'step' => '1',
						'min'  => '1',
					)
				),
			),
		);

		$options = $this->get_options();
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_input_fields_global_total_number', 1 ) ); $i++ ) {
			foreach( $options as $option ) {
				$settings[] = array(
					'title'    => ( 'wcj_product_input_fields_enabled_global_' === $option['id'] ) ? __( 'Product Input Field', 'woocommerce-jetpack' ) . ' #' . $i : '',
					'desc'     => $option['title'],
					'desc_tip' => ( isset( $option['desc_tip'] ) ) ? $option['desc_tip'] : '',
					'id'       => $option['id'] . $i,
					'default'  => $option['default'],
					'type'     => $option['type'],
					'options'  => isset( $option['options'] ) ? $option['options'] : '',
					'css'      => 'width:30%;min-width:300px;',
				);
			}
		}

		$settings[] = array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_input_fields_global_options',
		);

		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Frontend View Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_input_fields_frontend_view_options',
			),

			array(
				'title'    => __( 'HTML to add after required field title', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_frontend_view_required_html',
				'default'  => '&nbsp;<abbr class="required" title="required">*</abbr>',
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;',
			),

			array(
				'title'    => __( 'Item Name Order Table Format', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Affects Checkout, Emails and Admin Orders View', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_frontend_view_order_table_format',
				'default'  => '&nbsp;| %title% %value%',
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;',
			),

			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_input_fields_frontend_view_options',
			),
		) );

		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Emails Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_input_fields_emails_options',
			),

			array(
				'title'    => __( 'Attach Files to Admin\'s New Order Emails', 'woocommerce-jetpack' ),
				'desc'     => __( 'Attach', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_attach_to_admin_new_order',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),

			array(
				'title'    => __( 'Attach Files to Customer\'s Processing Order Emails', 'woocommerce-jetpack' ),
				'desc'     => __( 'Attach', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_attach_to_customer_processing_order',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),

			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_input_fields_emails_options',
			),
		) );

		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Admin Order View Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_input_fields_admin_view_options',
			),

			array(
				'title'    => __( 'Replace Field ID with Field Label', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_input_fields_make_nicer_name_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),

			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_input_fields_admin_view_options',
			),
		) );

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Input_Fields();

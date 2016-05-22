<?php
/**
 * WooCommerce Jetpack Product Input Fields per Product
 *
 * The WooCommerce Jetpack Product Input Fields per Product class.
 *
 * @version 2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Input_Fields_Per_Product' ) ) :

class WCJ_Product_Input_Fields_Per_Product extends WCJ_Product_Input_Fields_Abstract {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	public function __construct() {

		$this->scope = 'local';

		// Main hooks
		if ( 'yes' === get_option( 'wcj_product_input_fields_local_enabled' ) ) {

			// Product Edit
			add_action( 'add_meta_boxes',                           array( $this, 'add_local_product_input_fields_meta_box_to_product_edit' ) );
			add_action( 'save_post_product',                        array( $this, 'save_local_product_input_fields_on_product_edit' ), 999, 2 );

			// Show fields at frontend
			add_action( 'woocommerce_before_add_to_cart_button',    array( $this, 'add_product_input_fields_to_frontend' ), 100 );

			// Process from $_POST to cart item data
			add_filter( 'woocommerce_add_to_cart_validation',       array( $this, 'validate_product_input_fields_on_add_to_cart' ), 100, 2 );
			add_filter( 'woocommerce_add_cart_item_data',           array( $this, 'add_product_input_fields_to_cart_item_data' ), 100, 3 );
			// from session
			add_filter( 'woocommerce_get_cart_item_from_session',   array( $this, 'get_cart_item_product_input_fields_from_session' ), 100, 3 );

			// Show details at cart, order details, emails
			add_filter( 'woocommerce_cart_item_name',               array( $this, 'add_product_input_fields_to_cart_item_name' ), 100, 3 );
			add_filter( 'woocommerce_order_item_name',              array( $this, 'add_product_input_fields_to_order_item_name' ), 100, 2 );

			// Add item meta from cart to order
			add_action( 'woocommerce_add_order_item_meta',          array( $this, 'add_product_input_fields_to_order_item_meta' ), 100, 3 );

			// Make nicer name for product input fields in order at backend (shop manager)
//			add_action( 'woocommerce_before_order_itemmeta',        array( $this, 'start_making_nicer_name_for_product_input_fields' ), 100, 3 );
//			add_action( 'woocommerce_before_order_itemmeta',        'ob_start' );
//			add_action( 'woocommerce_after_order_itemmeta',         array( $this, 'finish_making_nicer_name_for_product_input_fields' ), 100, 3 );
			add_action( 'woocommerce_after_order_itemmeta',         array( $this, 'output_custom_input_fields_in_admin_order' ), 100, 3 );
			if ( 'yes' === get_option( 'wcj_product_input_fields_make_nicer_name_enabled' ) ) {
				add_filter( 'woocommerce_hidden_order_itemmeta',    array( $this, 'hide_custom_input_fields_default_output_in_admin_order' ), 100 );
			}
//			add_filter( 'woocommerce_attribute_label',              array( $this, 'change_woocommerce_attribute_label' ), PHP_INT_MAX, 2 );

			// Add to emails
			add_filter( 'woocommerce_email_attachments',            array( $this, 'add_files_to_email_attachments' ), PHP_INT_MAX, 3 );
		}
	}

	/**
	 * get_value.
	 */
	public function get_value( $option_name, $product_id, $default ) {
		return get_post_meta( $product_id, '_' . $option_name, true );
	}

	/**
	 * Save product input fields on Product Edit.
	 *
	 * @version 2.2.9
	 */
	public function save_local_product_input_fields_on_product_edit( $post_id, $post ) {

		// Check that we are saving with input fields displayed.
		if ( ! isset( $_POST['woojetpack_product_input_fields_save_post'] ) )
			return;

		// Save enabled, required, title etc.
		$default_total_input_fields = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_input_fields_local_total_number_default', 1 ) );
		$total_input_fields_before_saving = get_post_meta( $post_id, '_' . 'wcj_product_input_fields_local_total_number', true );
		$total_input_fields_before_saving = ( '' != $total_input_fields_before_saving ) ? $total_input_fields_before_saving : $default_total_input_fields;
		$options = $this->get_options();
		for ( $i = 1; $i <= $total_input_fields_before_saving; $i++ ) {
			foreach ( $options as $option ) {
				if ( isset( $_POST[ $option['id'] . $i ] ) )
					update_post_meta( $post_id, '_' . $option['id'] . $i, $_POST[ $option['id'] . $i ] );
//				elseif ( 'wcj_product_input_fields_title_local_' != $option['id'] && 'wcj_product_input_fields_placeholder_local_' != $option['id'] )
				elseif ( 'checkbox' === $option['type'] )
					update_post_meta( $post_id, '_' . $option['id'] . $i, 'off' );
			}
		}

		// Save total product input fields number
		$option_name = 'wcj_product_input_fields_local_total_number';
		$total_input_fields = isset( $_POST[ $option_name ] ) ? $_POST[ $option_name ] : $default_total_input_fields;
		update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
	}

	/**
	 * add_local_product_input_fields_meta_box_to_product_edit.
	 *
	 * @version 2.4.8
	 */
	public function add_local_product_input_fields_meta_box_to_product_edit() {
		add_meta_box(
			'wc-jetpack-product-input-fields',
			__( 'Booster: Product Input Fields', 'woocommerce-jetpack' ),
			array( $this, 'create_local_product_input_fields_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * create_local_product_input_fields_meta_box.
	 *
	 * @version 2.2.9
	 */
	public function create_local_product_input_fields_meta_box() {

		$meta_box_id   = 'product_input_fields';
		$meta_box_desc =  __( 'Product Input Fields', 'woocommerce-jetpack' );

		$options = $this->get_options();

		// Get total number
		$current_post_id = get_the_ID();
		$option_name = 'wcj_' . $meta_box_id . '_local_total_number';
		// If none total number set - check for the default
		if ( ! ( $total_number = apply_filters( 'wcj_get_option_filter', 1, get_post_meta( $current_post_id, '_' . $option_name, true ) ) ) )
			$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_' . $meta_box_id . '_local_total_number_default', 1 ) );

		// Start html
		$html = '';

		// Total number
		$is_disabled = apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly_string' );
		$is_disabled_message = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' );
		$html .= '<table>';
		$html .= '<tr>';
		$html .= '<th>';
		$html .= __( 'Total number of ', 'woocommerce-jetpack' ) . $meta_box_desc;
		$html .= '</th>';
		$html .= '<td>';
		$html .= '<input type="number" id="' . $option_name . '" name="' . $option_name . '" value="' . $total_number . '" ' . $is_disabled . '>';
		$html .= '</td>';
		$html .= '<td>';
		$html .= __( 'Click "Update" product after you change this number.', 'woocommerce-jetpack' ) . '<br>' . $is_disabled_message;
		$html .= '</td>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';

		// The options
//		$html .= '<h4>' . $meta_box_desc . '</h4>';
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$data = array();
			$html .= '<hr>';
			$html .= '<h4>' . __( 'Product Input Field', 'woocommerce-jetpack' ) . ' #' . $i . '</h4>';
			foreach ( $options as $option ) {
				$option_id = $option['id'] . $i;
				$option_value = get_post_meta( $current_post_id, '_' . $option_id, true );

				if ( ! metadata_exists( 'post', $current_post_id, '_' . $option_id ) ) {
					$option_value = $option['default'];
				}

				if ( 'checkbox' === $option['type'] )
					$is_checked = checked( $option_value, 'on', false );

				$select_options_html = '';
				if ( 'select' === $option['type'] ) {
					foreach( $option['options'] as $select_option_id => $select_option_label ) {
						$select_options_html .= '<option value="' . $select_option_id . '"' . selected( $option_value, $select_option_id, false ) . '>' . $select_option_label . '</option>';
					}
				}

				switch ( $option['type'] ) {
					case 'number':
					case 'text':
						$the_field = '<input type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '">';
						break;
					case 'textarea':
						$the_field = '<textarea style="width:100%;" id="' . $option_id . '" name="' . $option_id . '">' . $option_value . '</textarea>';
						break;
					case 'checkbox':
						$the_field = '<input class="checkbox" type="checkbox" name="' . $option_id . '" id="' . $option_id . '" ' . $is_checked . ' />';
						break;
					case 'select':
						$the_field = '<select id="' . $option_id . '" name="' . $option_id . '">' . $select_options_html . '</select>';
						break;
				}
				$data[] = array(
					( isset( $option['short_title'] ) ) ? $option['short_title'] : $option['title'],
					$the_field,
				);
			}
			$html .= wcj_get_table_html( $data, array( 'table_heading_type' => 'vertical', 'columns_styles' => array( 'text-align:right;', ), ) );
		}
		$html .= '<input type="hidden" name="woojetpack_' . $meta_box_id . '_save_post" value="woojetpack_' . $meta_box_id . '_save_post">';

		// Output
		echo $html;
	}

}

endif;

return new WCJ_Product_Input_Fields_Per_Product();

<?php
/**
 * WooCommerce Jetpack Product Bulk Meta Editor
 *
 * The WooCommerce Jetpack Product Bulk Meta Editor class.
 *
 * @version 2.7.2
 * @version 2.7.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Bulk_Meta_Editor' ) ) :

class WCJ_Product_Bulk_Meta_Editor extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.7.2
	 * @since   2.7.2
	 */
	function __construct() {

		$this->id         = 'product_bulk_meta_editor';
		$this->short_desc = __( 'Product Bulk Meta Editor', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change WooCommerce products meta with bulk editor.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-bulk-meta-editor/';
		parent::__construct();

		$this->add_tools( array(
			'product_bulk_meta_editor' => array(
				'title' => __( 'Product Bulk Meta Editor', 'woocommerce-jetpack' ),
				'desc'  => __( 'Product Bulk Meta Editor Tool.', 'woocommerce-jetpack' ),
			),
		) );
	}

	/**
	 * create_product_bulk_meta_editor_tool.
	 *
	 * @version 2.7.2
	 * @since   2.7.2
	 * @todo    not all products, but only selected (one by one (select all); category; tag; custom taxonomy)
	 * @todo    one value for all selected products meta
	 * @todo    (maybe) sanitize meta value
	 * @todo    (maybe) mark if not exists (on `'no' === get_option( 'wcj_product_bulk_meta_editor_check_if_exists', 'yes' )`)
	 * @todo    (maybe) "save meta" button for each product
	 * @todo    (maybe) checkboxes for each product
	 * @todo    (maybe) sortable columns in meta table
	 * @todo    (maybe) real permalink instead of `/?p=`
	 */
	function create_product_bulk_meta_editor_tool() {

		// Actions
		$meta_name = '';
		$result_message = '';
		if ( isset( $_POST['wcj_product_bulk_meta_editor_save'] ) && isset( $_POST['wcj_product_bulk_meta_editor_meta'] ) && '' !== $_POST['wcj_product_bulk_meta_editor_meta'] ) {
			$meta_name = $_POST['wcj_product_bulk_meta_editor_meta'];
			$key_start_length = strlen( 'wcj_product_bulk_meta_editor_id_' );
			$success = 0;
			$fail = 0;
			foreach ( $_POST as $key => $value ) {
				if ( false !== strpos( $key, 'wcj_product_bulk_meta_editor_id_' ) ) {
					$product_id = substr( $key, $key_start_length - strlen( $key ) );
					if ( update_post_meta( $product_id, $meta_name, $value ) ) {
						$success++;
					} else {
						$fail++;
					}
				}
			}
			if ( $success > 0 ) {
				$result_message .= '<p><div class="updated"><p>' . sprintf( __( 'Meta for <strong>%d</strong> product(s) updated.', 'woocommerce-jetpack' ), $success ) . '</p></div></p>';
			}
			if ( $fail > 0 ) {
				$result_message .= '<p><div class="error"><p>' . sprintf( __( 'Meta for <strong>%d</strong> product(s) not updated.', 'woocommerce-jetpack' ), $fail ) . '</p></div></p>';
			}
		} elseif ( isset( $_GET['wcj_product_bulk_meta_editor_show'] ) ) {
			$meta_name = $_GET['wcj_product_bulk_meta_editor_meta'];
		}

		// Output
		$html = '';
		$html .= '<div class="wrap">';
		$html .= $this->get_tool_header_html( 'product_bulk_meta_editor' );
		$html .= $result_message;
		// "Show meta" form
		$html .= '<form method="get" action="">';
		$html .= '<input type="hidden" name="page" value="' . $_GET['page'] . '" />';
		$html .= '<input type="hidden" name="tab" value="'  . $_GET['tab']  . '" />';
		$html .= '<p>';
		$html .= __( 'Meta', 'woocommerce-jetpack' );
		if ( '' == $meta_name ) {
			$html .= ', ' . sprintf( __( 'for example %s', 'woocommerce-jetpack' ), '<code>_sku</code>' );
		}
		$html .= ' ' . '<input type="text" name="wcj_product_bulk_meta_editor_meta" value="' . $meta_name . '">';
		$html .= ' ' . '<button class="button-primary" type="submit" name="wcj_product_bulk_meta_editor_show" value="show">' . __( 'Show', 'woocommerce-jetpack' ) . '</button>';
		$html .= '</p>';
		$html .= '</form>';
		// "Meta table" form
		$html .= '<form method="post" action="">';
		if ( '' != $meta_name ) {
			$table_data = array();
			$table_data[] = array(
				__( 'Product', 'woocommerce-jetpack' ),
				__( 'Meta', 'woocommerce-jetpack' ) . ': <code>' . $meta_name . '</code>',
			);
			foreach ( wcj_get_products() as $product_id => $product_title ) {
				if ( ! metadata_exists( 'post', $product_id, $meta_name ) && 'yes' === get_option( 'wcj_product_bulk_meta_editor_check_if_exists', 'yes' ) ) {
					$_post_meta = '';
				} else {
					$_post_meta = get_post_meta( $product_id, $meta_name, true );
					if ( is_array( $_post_meta ) || is_object( $_post_meta ) ) {
						$_post_meta = print_r( $_post_meta, true );
					} else {
						$_post_meta = '<input style="width:100%;" type="text" name="wcj_product_bulk_meta_editor_id_' . $product_id . '" value="' . $_post_meta . '">';
					}
				}
				$table_data[] = array(
					'<a href="/?p=' . $product_id . '"> ' . $product_title . '</a> <em>(ID: ' . $product_id . ')</em>',
					$_post_meta,
				);
			}
			$html .= '<p><input class="button-primary" type="submit" name="wcj_product_bulk_meta_editor_save" value="' . __( 'Save all', 'woocommerce-jetpack' ) . '"></p>';
			$html .= '<input type="hidden" name="wcj_product_bulk_meta_editor_meta" value="' . $meta_name . '">';
			$html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );
		}
		$html .= '</form>';
		// Finalize
		$html .= '</div>';
		echo $html;
	}

}

endif;

return new WCJ_Product_Bulk_Meta_Editor();

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
		$this->desc       = __( 'Set WooCommerce products meta with bulk editor.', 'woocommerce-jetpack' );
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
	 * get_result_message.
	 *
	 * @version 2.7.2
	 * @since   2.7.2
	 */
	function get_result_message( $success, $fail ) {
		$result_message = '';
		if ( $success > 0 ) {
			$result_message .= '<p><div class="updated"><p>' . sprintf( __( 'Meta for <strong>%d</strong> was product(s) updated.', 'woocommerce-jetpack' ), $success ) . '</p></div></p>';
		}
		if ( $fail > 0 ) {
			$result_message .= '<p><div class="error"><p>' . sprintf( __( 'Meta for <strong>%d</strong> product(s) was not updated.', 'woocommerce-jetpack' ), $fail ) . '</p></div></p>';
		}
		return $result_message;
	}

	/**
	 * create_product_bulk_meta_editor_tool.
	 *
	 * @version 2.7.2
	 * @since   2.7.2
	 * @todo    - tab order (go through input fields in meta table)
	 * @todo    - options (variations$$$? columns? products(e.g. always select all)? etc.)
	 * @todo    - restyle "Products and Meta key" (as table?)
	 * @todo    - all "submit" inputs as single "name" but with different "value"
	 * @todo    - "save" on enter key ("wrong form" issue)
	 * @todo    - issue when wcj_product_bulk_meta_editor_set is empty (make required?)
	 * @todo    - not all products, but only selected (one by one (select all); category; tag; custom taxonomy)
	 * @todo    ~ "delete meta" button for each product - needs confirmation
	 * @todo    ~ one value for all selected products meta
	 * @todo    ~ mark if not exists (on `'no' === get_option( 'wcj_product_bulk_meta_editor_check_if_exists', 'yes' )`)
	 * @todo    ~ show products meta keys
	 * @todo    + "save meta" button for each product
	 * @todo    (maybe) multiple meta keys
	 * @todo    (maybe) "delete all meta" button (for single product) - needs confirmation
	 * @todo    (maybe) "delete all" button (for all products) - needs confirmation
	 * @todo    (maybe) sanitize meta value
	 * @todo    (maybe) checkboxes for each product
	 * @todo    (maybe) sortable columns in meta table
	 * @todo    (maybe) real permalink instead of `/?p=`
	 */
	function create_product_bulk_meta_editor_tool() {

		// Actions
		$meta_name = '';
		$set_meta = '';
		$result_message = '';
		if (
			isset( $_POST['wcj_product_bulk_meta_editor_save_single'] ) && 0 != $_POST['wcj_product_bulk_meta_editor_save_single'] &&
			isset( $_POST['wcj_product_bulk_meta_editor_meta'] ) && '' !== $_POST['wcj_product_bulk_meta_editor_meta']
		) {
			$meta_name = $_POST['wcj_product_bulk_meta_editor_meta'];
			$success = 0;
			$fail = 0;
			$_product_id = $_POST['wcj_product_bulk_meta_editor_save_single'];
			if ( update_post_meta( $_product_id, $meta_name, $_POST[ 'wcj_product_bulk_meta_editor_id_' . $_product_id ] ) ) {
				$success++;
			} else {
				$fail++;
			}
			$result_message = $this->get_result_message( $success, $fail );
		} elseif (
			isset( $_POST['wcj_product_bulk_meta_editor_delete_single'] ) && 0 != $_POST['wcj_product_bulk_meta_editor_delete_single'] &&
			isset( $_POST['wcj_product_bulk_meta_editor_meta'] ) && '' !== $_POST['wcj_product_bulk_meta_editor_meta']
		) {
			$meta_name = $_POST['wcj_product_bulk_meta_editor_meta'];
			$success = 0;
			$fail = 0;
			$_product_id = $_POST['wcj_product_bulk_meta_editor_delete_single'];
			if ( delete_post_meta( $_product_id, $meta_name ) ) {
				$success++;
			} else {
				$fail++;
			}
			$result_message = $this->get_result_message( $success, $fail );
		} elseif (
			isset( $_POST['wcj_product_bulk_meta_editor_save'] ) &&
			isset( $_POST['wcj_product_bulk_meta_editor_meta'] ) && '' !== $_POST['wcj_product_bulk_meta_editor_meta']
		) {
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
			$result_message = $this->get_result_message( $success, $fail );
		} elseif (
			isset( $_POST['wcj_product_bulk_meta_editor_set'] ) &&
			isset( $_POST['wcj_product_bulk_meta_editor_set_meta'] ) && '' !== $_POST['wcj_product_bulk_meta_editor_set_meta'] &&
			isset( $_POST['wcj_product_bulk_meta_editor_meta'] ) && '' !== $_POST['wcj_product_bulk_meta_editor_meta']
		) {
			$meta_name = $_POST['wcj_product_bulk_meta_editor_meta'];
			$set_meta = $_POST['wcj_product_bulk_meta_editor_set_meta'];
			$key_start_length = strlen( 'wcj_product_bulk_meta_editor_id_' );
			$success = 0;
			$fail = 0;
			foreach ( $_POST as $key => $value ) {
				if ( false !== strpos( $key, 'wcj_product_bulk_meta_editor_id_' ) ) {
					$product_id = substr( $key, $key_start_length - strlen( $key ) );
					if ( update_post_meta( $product_id, $meta_name, $set_meta ) ) {
						$success++;
					} else {
						$fail++;
					}
				}
			}
			$result_message = $this->get_result_message( $success, $fail );
		} elseif ( isset( $_POST['wcj_product_bulk_meta_editor_show'] ) ) {
			$meta_name = $_POST['wcj_product_bulk_meta_editor_show_meta'];
		}

		// Preparing data
		$_products = $this->get_products();
		$selected_products = isset( $_POST['wcj_product_bulk_meta_editor_products'] ) ? $_POST['wcj_product_bulk_meta_editor_products'] : array();

		// Output
		echo $this->get_tool_html( $meta_name, $result_message, $_products, $selected_products, $set_meta );
	}

	/**
	 * get_products.
	 *
	 * @version 2.7.2
	 * @since   2.7.2
	 * @todo    use global function (and add "add_variations" param)
	 */
	function get_products( $products = array(), $post_status = 'any' ) {
		$offset = 0;
		$block_size = 512;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => $post_status,
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $post_id ) {
				$products[ $post_id ] = get_the_title( $post_id );
				$_product = wc_get_product( $post_id );
				if ( $_product->is_type( 'variable' ) ) {
					foreach ( $_product->get_children() as $child_id ) {
						$products[ $child_id ] = get_the_title( $child_id );
					}
				}
			}
			$offset += $block_size;
		}
		return $products;
	}

	/**
	 * get_tool_html.
	 *
	 * @version 2.7.2
	 * @since   2.7.2
	 */
	function get_tool_html( $meta_name, $result_message, $_products, $selected_products, $set_meta ) {
		$html = '';
		$html .= '<div class="wrap">';
		$html .= $this->get_tool_header_html( 'product_bulk_meta_editor' );
		$html .= $result_message;
		$html .= '<form method="post" action="">';
		// Products and Meta key
		/* $html .= __( 'Products', 'woocommerce-jetpack' );
		$html .= '<br>';
		$html .= '<em>';
		$html .= __( 'Hold <strong>Control</strong> key to select multiple products. Press <strong>Control</strong> + <strong>A</strong> to select all products.', 'woocommerce-jetpack' );
		$html .= '</em>';
		$html .= '<br>';
		$html .= '<select name="wcj_product_bulk_meta_editor_products[]" multiple style="height:300px;">';
		foreach ( $_products as $product_id => $product_title ) {
			$selected = ( empty( $selected_products ) || in_array( $product_id, $selected_products ) ? ' selected' : '' );
			$html .= '<option' . $selected . ' value="' . $product_id . '">' . $product_title . '</option>';
		}
		$html .= '</select>';
//		$html .= '<input type="hidden" name="page" value="' . $_GET['page'] . '" />';
//		$html .= '<input type="hidden" name="tab" value="'  . $_GET['tab']  . '" />';
		$html .= '<p>';
		$html .= __( 'Meta key', 'woocommerce-jetpack' );
		if ( '' == $meta_name ) {
			$html .= ', ' . sprintf( __( 'for example %s', 'woocommerce-jetpack' ), '<code>_sku</code>' );
		}
		$html .= ' ' . '<input type="text" name="wcj_product_bulk_meta_editor_show_meta" value="' . $meta_name . '">';
		$html .= ' ' . '<button class="button-primary" type="submit" name="wcj_product_bulk_meta_editor_show" value="show">' . __( 'Show', 'woocommerce-jetpack' ) . '</button>';
		$html .= '</p>'; */
		$products_html = '';
		$products_html .= '<select name="wcj_product_bulk_meta_editor_products[]" multiple style="height:300px;">';
		foreach ( $_products as $product_id => $product_title ) {
			$selected = ( empty( $selected_products ) || in_array( $product_id, $selected_products ) ? ' selected' : '' );
			$products_html .= '<option' . $selected . ' value="' . $product_id . '">' . $product_title . '</option>';
		}
		$products_html .= '</select>';
		$meta_html = '';
		$meta_html .= '<p>';
		$meta_html .= __( 'Meta key', 'woocommerce-jetpack' );
		if ( '' == $meta_name ) {
			$meta_html .= ', ' . sprintf( __( 'for example %s', 'woocommerce-jetpack' ), '<code>_sku</code>' );
		}
		$meta_html .= ' ' . '<input type="text" name="wcj_product_bulk_meta_editor_show_meta" value="' . $meta_name . '">';
		$meta_html .= ' ' . '<button class="button-primary" type="submit" name="wcj_product_bulk_meta_editor_show" value="show">' . __( 'Show', 'woocommerce-jetpack' ) . '</button>';
		$meta_html .= '</p>';
		$tip = __( 'Hold <strong>Control</strong> key to select multiple products. Press <strong>Control</strong> + <strong>A</strong> to select all products.', 'woocommerce-jetpack' );
		$tip = ' <img style="display:inline;" class="wcj-question-icon" src="' . wcj_plugin_url() . '/assets/images/question-icon.png' . '" title="' . $tip . '">';
		$table_data = array();
		$table_data[] = array(
			__( 'Products', 'woocommerce-jetpack' ) . $tip,
		);
		$table_data[] = array(
			$products_html,
		);
		$html .= '<p>' . wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) ) . '</p>';
		$table_data = array();
		$table_data[] = array(
			__( 'Meta', 'woocommerce-jetpack' ),
		);
		$table_data[] = array(
			$meta_html,
		);
		$html .= '<p>' . wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) ) . '</p>';
		// Meta table
		if ( '' != $meta_name ) {
			$html .= '<p>';
			$html .= __( 'Single value', 'woocommerce-jetpack' );
//			$html .= ' ' . '<input required type="text" name="wcj_product_bulk_meta_editor_set_meta" value="' . $set_meta . '">';
			$html .= ' ' . '<input type="text" name="wcj_product_bulk_meta_editor_set_meta" value="' . $set_meta . '">';
			$html .= ' ' . '<button class="button-primary" type="submit" name="wcj_product_bulk_meta_editor_set" value="set">' . __( 'Set', 'woocommerce-jetpack' ) . '</button>';
			$html .= '</p>';
			$table_data = array();
			$table_data[] = array(
				__( 'Product', 'woocommerce-jetpack' ),
				__( 'Meta', 'woocommerce-jetpack' ) . ': <code>' . $meta_name . '</code>',
				'',
				'',
			);
			foreach ( $_products as $product_id => $product_title ) {
				if ( ! in_array( $product_id, $selected_products ) ) {
					continue;
				}
				if ( ! metadata_exists( 'post', $product_id, $meta_name ) && 'yes' === get_option( 'wcj_product_bulk_meta_editor_check_if_exists', 'yes' ) ) {
					$_post_meta = '';
				} else {
					$_post_meta = get_post_meta( $product_id, $meta_name, true );
					if ( is_array( $_post_meta ) || is_object( $_post_meta ) ) {
						$_post_meta  = ( ! metadata_exists( 'post', $product_id, $meta_name ) ? '<em>' . 'N/A' . '</em>' : print_r( $_post_meta, true ) );
					} else {
						$placeholder = ( ! metadata_exists( 'post', $product_id, $meta_name ) ? ' placeholder="N/A"' : '' );
						$_post_meta  = '<input' . $placeholder . ' style="width:100%;" type="text" name="wcj_product_bulk_meta_editor_id_' . $product_id . '" value="' . $_post_meta . '">';
					}
				}
				$table_data[] = array(
					'<a href="/?p=' . $product_id . '"> ' . $product_title . '</a> <em>(ID: ' . $product_id . ') ' . get_post_status( $product_id ) . '</em>',
					$_post_meta,
					'<button class="button-primary" type="submit" name="wcj_product_bulk_meta_editor_save_single" value="' . $product_id . '">'
						. __( 'Save', 'woocommerce-jetpack' ) . '</button>' .
					' ' .
					'<button class="button-primary" type="submit" name="wcj_product_bulk_meta_editor_delete_single" value="' . $product_id . '" onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">'
						. __( 'Delete', 'woocommerce-jetpack' ) . '</button>',
					'<details style="color:gray;">' .
						'<summary><em>' . __( 'Show all product\'s meta keys', 'woocommerce-jetpack' ) . '</em></summary>' .
						'<p>' . implode( '<br>', array_keys( get_post_meta( $product_id ) ) ) . '</p>' .
					'</details>',
				);
			}
			$html .= '<p><input class="button-primary" type="submit" name="wcj_product_bulk_meta_editor_save" value="' . __( 'Save all', 'woocommerce-jetpack' ) . '"></p>';
			$html .= '<input type="hidden" name="wcj_product_bulk_meta_editor_meta" value="' . $meta_name . '">';
			$html .= '<p>' . wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) ) . '</p>';
		}
		$html .= '</form>';
		// Finalize
		$html .= '</div>';
		return $html;
	}

}

endif;

return new WCJ_Product_Bulk_Meta_Editor();

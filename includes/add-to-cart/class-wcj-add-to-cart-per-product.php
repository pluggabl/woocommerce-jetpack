<?php
/**
 * Booster for WooCommerce Add to Cart per Product
 *
 * @version 2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Add_To_Cart_Per_Product' ) ) :

class WCJ_Add_To_Cart_Per_Product {

	/**
	 * Constructor.
	 */
	function __construct() {
		if ( 'yes' === get_option( 'wcj_add_to_cart_per_product_enabled' ) ) {
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_button_text_single' ),  PHP_INT_MAX );
			add_filter( 'woocommerce_product_add_to_cart_text',        array( $this, 'change_add_to_cart_button_text_archive' ), PHP_INT_MAX );
			add_action( 'add_meta_boxes',                              array( $this, 'add_custom_add_to_cart_meta_box' ) );
			add_action( 'save_post_product',                           array( $this, 'save_custom_add_to_cart_meta_box' ), 100, 2 );
		}
	}

	/**
	 * change_add_to_cart_button_text_single.
	 */
	function change_add_to_cart_button_text_single( $add_to_cart_text ) {
		return $this->change_add_to_cart_button_text( $add_to_cart_text, 'single' );
	}

	/**
	 * change_add_to_cart_button_text_archive.
	 */
	function change_add_to_cart_button_text_archive( $add_to_cart_text ) {
		return $this->change_add_to_cart_button_text( $add_to_cart_text, 'archive' );
	}

	/**
	 * change_add_to_cart_button_text.
	 *
	 * @version 2.7.0
	 */
	function change_add_to_cart_button_text( $add_to_cart_text, $single_or_archive ) {
		global $product;
		if ( ! $product ) {
			return $add_to_cart_text;
		}
		$local_custom_add_to_cart_option_id = 'wcj_custom_add_to_cart_local_' . $single_or_archive;
		$local_custom_add_to_cart_option_value = get_post_meta( wcj_get_product_id_or_variation_parent_id( $product ), '_' . $local_custom_add_to_cart_option_id, true );
		if ( '' != $local_custom_add_to_cart_option_value ) {
			return $local_custom_add_to_cart_option_value;
		}
		return $add_to_cart_text;
	}

	/**
	 * save_custom_add_to_cart_meta_box.
	 */
	function save_custom_add_to_cart_meta_box( $post_id, $post ) {
		// Check that we are saving with custom add to cart metabox displayed.
		if ( ! isset( $_POST['woojetpack_custom_add_to_cart_save_post'] ) ) {
			return;
		}
		$option_name = 'wcj_custom_add_to_cart_local_' . 'single';
		update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
		$option_name = 'wcj_custom_add_to_cart_local_' . 'archive';
		update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
	}

	/**
	 * add_custom_add_to_cart_meta_box.
	 *
	 * @version 2.4.8
	 */
	function add_custom_add_to_cart_meta_box() {
		add_meta_box(
			'wc-jetpack-custom-add-to-cart',
			__( 'Booster: Custom Add to Cart', 'woocommerce-jetpack' ),
			array( $this, 'create_custom_add_to_cart_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * create_custom_add_to_cart_meta_box.
	 */
	function create_custom_add_to_cart_meta_box() {

		$current_post_id = get_the_ID();

		$options = array(
			'single'  => __( 'Single product view', 'woocommerce-jetpack' ),
			'archive' => __( 'Product category (archive) view', 'woocommerce-jetpack' ),
		);

		$html = '<table style="width:50%;min-width:300px;">';
		foreach ( $options as $option_key => $option_desc ) {
			$option_type = 'textarea';
			if ( 'url' == $option_key )
				$option_type = 'text';
			$html .= '<tr>';
			$html .= '<th>' . $option_desc . '</th>';

			$option_id = 'wcj_custom_add_to_cart_local_' . $option_key;
			$option_value = get_post_meta( $current_post_id, '_' . $option_id, true );

			if ( 'textarea' === $option_type )
				$html .= '<td style="width:80%;">';
			else
				$html .= '<td>';
			//switch ( $option_type ) {
				//case 'number':
				//case 'text':
				//	$html .= '<input style="width:100%;" type="' . $option_type . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '">';
				//	break;
				//case 'textarea':
					$html .= '<textarea style="width:100%;" id="' . $option_id . '" name="' . $option_id . '">' . $option_value . '</textarea>';
				//	break;
			//}
			$html .= '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';
		$html .= '<input type="hidden" name="woojetpack_custom_add_to_cart_save_post" value="woojetpack_custom_add_to_cart_save_post">';
		echo $html;
	}
}

endif;

return new WCJ_Add_To_Cart_Per_Product();

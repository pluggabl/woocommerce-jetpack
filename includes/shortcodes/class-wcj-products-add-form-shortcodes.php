<?php
/**
 * WooCommerce Jetpack Products Add Form Shortcodes
 *
 * The WooCommerce Jetpack Products Add Form Shortcodes class.
 *
 * @version 2.4.9
 * @since   2.4.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Products_Add_Form_Shortcodes' ) ) :

class WCJ_Products_Add_Form_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function __construct() {

		$this->the_shortcodes = array(
			'wcj_product_add_new',
		);

		$this->the_atts = array(
			'product_id'  => 0, // todo (for editing?)
			'post_status' => 'publish', // todo (for editing?)
		);

		parent::__construct();
	}

	/**
	 * Inits shortcode atts and properties.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function init_atts( $atts ) {
		return $atts;
	}

	/**
	 * wc_add_new_product.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function wc_add_new_product( $args, $shortcode_atts ) {

		$new_product_post = array(
			'post_title'    => $args['title'],
			'post_content'  => $args['description'],
			'post_excerpt'  => $args['short_description'],
			'post_type'     => 'product',
			'post_status'   => 'draft',
		);

		// Insert the post into the database
		if ( 0 != ( $new_product_id = wp_insert_post( $new_product_post ) ) ) {

			wp_set_object_terms( $new_product_id, 'simple', 'product_type' );
			wp_set_object_terms( $new_product_id, $args['cats'], 'product_cat' );
			wp_set_object_terms( $new_product_id, $args['tags'], 'product_tag' );

			update_post_meta( $new_product_id, '_visibility', 'visible' );
			update_post_meta( $new_product_id, '_stock_status', 'instock' );

			wp_update_post( array( 'ID' => $new_product_id, 'post_status' => $shortcode_atts['post_status'] ) );
		}

		return $new_product_id;
	}

	/**
	 * validate_args.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function validate_args( $args, $shortcode_atts ) {
		if ( '' == $args['title'] ) {
			return false;
		}
		return true;
	}

	/**
	 * wcj_product_add_new.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function wcj_product_add_new( $atts ) {

		$header_html       = '';
		$notice_html       = '';
		$input_fields_html = '';
		$footer_html       = '';

		if ( isset( $_REQUEST['wcj_add_new_product'] ) ) {
			$args = array(
				'title'             => $_REQUEST['wcj_add_new_product_title'],
				'description'       => $_REQUEST['wcj_add_new_product_desc'],
				'short_description' => $_REQUEST['wcj_add_new_product_short_desc'],
				'cats'              => isset( $_REQUEST['wcj_add_new_product_cats'] ) ? $_REQUEST['wcj_add_new_product_cats'] : array(),
				'tags'              => isset( $_REQUEST['wcj_add_new_product_tags'] ) ? $_REQUEST['wcj_add_new_product_tags'] : array(),
			);
			if ( $this->validate_args( $args, $atts ) ) {
				$result = $this->wc_add_new_product( $args, $atts );
				$notice_html .= ( 0 == $result )
					? '<div class="woocommerce"><ul class="woocommerce-error"><li>' . __( 'Error!', 'woocommerce-jetpack' ) . '</li></ul></div>'
					: '<div class="woocommerce"><div class="woocommerce-message">' . __( 'Success!', 'woocommerce-jetpack' ) . '</div></div>';
			} else {
				$notice_html .= '<div class="woocommerce"><ul class="woocommerce-error"><li>' . __( 'Error Validating!', 'woocommerce-jetpack' ) . '</li></ul></div>';
			}
		}

		$header_html .= '<h3>' . __( 'Add New Product', 'woocommerce-jetpack' ) . '</h3>';
		$header_html .= '<form method="post" action="">';

		$table_data = array();
		$input_style = 'width:100%;';
		$table_data[] = array(
			__( 'Title', 'woocommerce-jetpack' ),
			'<input type="text" style="' . $input_style . '" name="wcj_add_new_product_title">'
		);
		$table_data[] = array(
			__( 'Description', 'woocommerce-jetpack' ),
			'<textarea style="' . $input_style . '" name="wcj_add_new_product_desc"></textarea>'
		);
		$table_data[] = array(
			__( 'Short Description', 'woocommerce-jetpack' ),
			'<textarea style="' . $input_style . '" name="wcj_add_new_product_short_desc"></textarea>'
		);
		$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		$product_categories_as_select_options = '';
		foreach ( $product_categories as $product_category ) {
			$product_categories_as_select_options .= '<option value="' . $product_category->slug . '">' . $product_category->name .'</option>';
		}
		$table_data[] = array(
			__( 'Categories', 'woocommerce-jetpack' ),
			'<select multiple style="' . $input_style . '" name="wcj_add_new_product_cats[]">' . $product_categories_as_select_options . '</select>'
		);
		$products_tags = get_terms( 'product_tag', 'orderby=name&hide_empty=0' );
		$products_tags_as_select_options = '';
		foreach ( $products_tags as $products_tag ) {
			$products_tags_as_select_options .= '<option value="' . $products_tag->slug . '">' . $products_tag->name .'</option>';
		}
		$table_data[] = array(
			__( 'Tags', 'woocommerce-jetpack' ),
			'<select multiple style="' . $input_style . '" name="wcj_add_new_product_tags[]">' . $products_tags_as_select_options . '</select>'
		);
		$input_fields_html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat', 'table_heading_type' => 'vertical', ) );

		$footer_html .= '<input type="submit" class="button" name="wcj_add_new_product" value="' . __( 'Add', 'woocommerce-jetpack' ) . '">';
		$footer_html .= '</form>';

		return $notice_html . $header_html . $input_fields_html . $footer_html;
	}
}

endif;

return new WCJ_Products_Add_Form_Shortcodes();

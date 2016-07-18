<?php
/**
 * WooCommerce Jetpack Products Add Form Shortcodes
 *
 * The WooCommerce Jetpack Products Add Form Shortcodes class.
 *
 * @version 2.5.4
 * @since   2.5.0
 * @author  Algoritmika Ltd.
 * @todo    refill image on not validated (or after successful addition); more messages options; more styling options; custom fields;
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Products_Add_Form_Shortcodes' ) ) :

class WCJ_Products_Add_Form_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 * @since   2.5.0
	 */
	function __construct() {

		$this->the_shortcodes = array(
			'wcj_product_add_new',
		);

		$this->the_atts = array(
			'product_id'             => /* ( isset( $_GET['wcj_edit_product'] ) ) ? $_GET['wcj_edit_product'] :  */ 0, // todo?
			'post_status'            => get_option( 'wcj_product_by_user_status', 'draft' ),

			'desc_enabled'           => get_option( 'wcj_product_by_user_desc_enabled', 'no' ),
			'short_desc_enabled'     => get_option( 'wcj_product_by_user_short_desc_enabled', 'no' ),
			'regular_price_enabled'  => get_option( 'wcj_product_by_user_regular_price_enabled', 'no' ),
			'sale_price_enabled'     => get_option( 'wcj_product_by_user_sale_price_enabled', 'no' ),
			'cats_enabled'           => get_option( 'wcj_product_by_user_cats_enabled', 'no' ),
			'tags_enabled'           => get_option( 'wcj_product_by_user_tags_enabled', 'no' ),
			'image_enabled'          => apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_product_by_user_image_enabled', 'no' ) ),

			'desc_required'          => get_option( 'wcj_product_by_user_desc_required', 'no' ),
			'short_desc_required'    => get_option( 'wcj_product_by_user_short_desc_required', 'no' ),
			'regular_price_required' => get_option( 'wcj_product_by_user_regular_price_required', 'no' ),
			'sale_price_required'    => get_option( 'wcj_product_by_user_sale_price_required', 'no' ),
			'cats_required'          => get_option( 'wcj_product_by_user_cats_required', 'no' ),
			'tags_required'          => get_option( 'wcj_product_by_user_tags_required', 'no' ),
			'image_required'         => apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_product_by_user_image_required', 'no' ) ),

			'visibility'             => implode( ',', get_option( 'wcj_product_by_user_user_visibility', array() ) ),
			'module'                 => 'product_by_user',
			'module_name'            => __( 'Product by User', 'woocommerce-jetpack' ),
		);

		parent::__construct();
	}

	/**
	 * wc_add_new_product.
	 *
	 * @version 2.5.2
	 * @since   2.5.0
	 */
	function wc_add_new_product( $args, $shortcode_atts ) {

		$product_post = array(
			'post_title'    => $args['title'],
			'post_content'  => $args['desc'],
			'post_excerpt'  => $args['short_desc'],
			'post_type'     => 'product',
			'post_status'   => 'draft',
		);

		if ( 0 == $shortcode_atts['product_id'] ) {
			$product_id = wp_insert_post( $product_post );
		} else {
			$product_id = $shortcode_atts['product_id'];
			wp_update_post( array_merge( array( 'ID' => $product_id ), $product_post ) );
		}

		// Insert the post into the database
		if ( 0 != $product_id ) {

			wp_set_object_terms( $product_id, 'simple', 'product_type' );
			wp_set_object_terms( $product_id, $args['cats'], 'product_cat' );
			wp_set_object_terms( $product_id, $args['tags'], 'product_tag' );

			update_post_meta( $product_id, '_regular_price', $args['regular_price'] );
			update_post_meta( $product_id, '_sale_price', $args['sale_price'] );
			if ( '' == $args['sale_price'] ) {
				update_post_meta( $product_id, '_price', $args['regular_price'] );
			} else {
				update_post_meta( $product_id, '_price', $args['sale_price'] );
			}
			update_post_meta( $product_id, '_visibility', 'visible' );
			update_post_meta( $product_id, '_stock_status', 'instock' );

			// Image
			if ( '' != $args['image'] && '' != $args['image']['tmp_name'] ) {
				$upload_dir = wp_upload_dir();
				$filename = $args['image']['name'];
				$file = ( wp_mkdir_p( $upload_dir['path'] ) ) ? $upload_dir['path'] : $upload_dir['basedir'];
				$file .= '/' . $filename;

				move_uploaded_file( $args['image']['tmp_name'], $file );

				$wp_filetype = wp_check_filetype( $filename, null );
				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => sanitize_file_name( $filename ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
				$attach_id = wp_insert_attachment( $attachment, $file, $product_id );
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				set_post_thumbnail( $product_id, $attach_id );
			}

			wp_update_post( array( 'ID' => $product_id, 'post_status' => $shortcode_atts['post_status'] ) );
		}

		return $product_id;
	}

	/**
	 * validate_args.
	 *
	 * @version 2.5.4
	 * @since   2.5.0
	 */
	function validate_args( $args, $shortcode_atts ) {
		$errors = '';
		if ( '' == $args['title'] ) {
			$errors .= '<li>' . __( 'Title is required!', 'woocommerce-jetpack' ) . '</li>';
		}

		if ( 'yes' === get_option( 'wcj_product_by_user_require_unique_title', 'no' ) && 0 == $shortcode_atts['product_id'] ) {
			if ( ! function_exists( 'post_exists' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/post.php' );
			}
			if ( post_exists( $args['title'] ) ) {
				$errors .= '<li>' . __( 'Product exists!', 'woocommerce-jetpack' ) . '</li>';
			}
		}

		$fields = array(
			'desc'          => __( 'Description', 'woocommerce-jetpack' ),
			'short_desc'    => __( 'Short Description', 'woocommerce-jetpack' ),
			'image'         => __( 'Image', 'woocommerce-jetpack' ),
			'regular_price' => __( 'Regular Price', 'woocommerce-jetpack' ),
			'sale_price'    => __( 'Sale Price', 'woocommerce-jetpack' ),
			'cats'          => __( 'Categories', 'woocommerce-jetpack' ),
			'tags'          => __( 'Tags', 'woocommerce-jetpack' ),
		);
		foreach ( $fields as $field_id => $field_desc ) {
			if (  'yes' === $shortcode_atts[ $field_id . '_enabled' ] && 'yes' === $shortcode_atts[ $field_id . '_required' ] ) {
				$is_missing = false;
				if ( 'image' === $field_id && ( '' == $args[ $field_id ] || ! isset( $args[ $field_id ]['tmp_name'] ) || '' == $args[ $field_id ]['tmp_name'] ) ) {
					$is_missing = true;
				} elseif ( empty( $args[ $field_id ] ) ) {
					$is_missing = true;
				}
				if ( $is_missing ) {
					$errors .= '<li>' . sprintf( __( '%s is required!', 'woocommerce-jetpack' ), $field_desc ) . '</li>';
				}
			}
		}

		if ( $args['sale_price'] > $args['regular_price'] ) {
			$errors .= '<li>' . __( 'Sale price must be less than the regular price!', 'woocommerce-jetpack' ) . '</li>';
		}
		return ( '' === $errors ) ? true : $errors;
	}

	/**
	 * wcj_product_add_new.
	 *
	 * @version 2.5.4
	 * @since   2.5.0
	 */
	function wcj_product_add_new( $atts ) {

		$header_html       = '';
		$notice_html       = '';
		$input_fields_html = '';
		$footer_html       = '';

		$args = array(
			'title'             => isset( $_REQUEST['wcj_add_new_product_title'] )         ? $_REQUEST['wcj_add_new_product_title']         : '',
			'desc'              => isset( $_REQUEST['wcj_add_new_product_desc'] )          ? $_REQUEST['wcj_add_new_product_desc']          : '',
			'short_desc'        => isset( $_REQUEST['wcj_add_new_product_short_desc'] )    ? $_REQUEST['wcj_add_new_product_short_desc']    : '',
			'regular_price'     => isset( $_REQUEST['wcj_add_new_product_regular_price'] ) ? $_REQUEST['wcj_add_new_product_regular_price'] : '',
			'sale_price'        => isset( $_REQUEST['wcj_add_new_product_sale_price'] )    ? $_REQUEST['wcj_add_new_product_sale_price']    : '',
			'cats'              => isset( $_REQUEST['wcj_add_new_product_cats'] )          ? $_REQUEST['wcj_add_new_product_cats']          : array(),
			'tags'              => isset( $_REQUEST['wcj_add_new_product_tags'] )          ? $_REQUEST['wcj_add_new_product_tags']          : array(),
			'image'             => isset( $_FILES['wcj_add_new_product_image'] )           ? $_FILES['wcj_add_new_product_image']           : '',
		);

		if ( isset( $_REQUEST['wcj_add_new_product'] ) ) {
			if ( true === ( $validate_args = $this->validate_args( $args, $atts ) ) ) {
				$result = $this->wc_add_new_product( $args, $atts );
				if ( 0 == $result ) {
					// Error
					$notice_html .= '<div class="woocommerce"><ul class="woocommerce-error"><li>' . __( 'Error!', 'woocommerce-jetpack' ) . '</li></ul></div>';
				} else {
					// Success
					if ( 0 == $atts['product_id'] ) {
						$notice_html .= '<div class="woocommerce"><div class="woocommerce-message">' .
							str_replace(
								'%product_title%',
								$args['title'],
								get_option( 'wcj_product_by_user_message_product_successfully_added', __( '"%product_title%" successfully added!', 'woocommerce-jetpack' ) ) ) .
							'</div></div>';
					} else {
						$notice_html .= '<div class="woocommerce"><div class="woocommerce-message">' .
							str_replace(
								'%product_title%',
								$args['title'],
								get_option( 'wcj_product_by_user_message_product_successfully_edited', __( '"%product_title%" successfully edited!', 'woocommerce-jetpack' ) ) ) .
							'</div></div>';
					}
//					$atts['product_id'] = $result;
				}
			} else {
				$notice_html .= '<div class="woocommerce"><ul class="woocommerce-error">' . $validate_args . '</ul></div>';
			}
		}

		if ( isset( $_GET['wcj_edit_product_image_delete'] ) ) {
			$product_id = $_GET['wcj_edit_product_image_delete'];
			$post_author_id = get_post_field( 'post_author', $product_id );
			$user_ID = get_current_user_id();
			if ( $user_ID != $post_author_id ) {
				echo '<p>' . __( 'Wrong user ID!', 'woocommerce-jetpack' ) . '</p>';
			} else {
				$image_id = get_post_thumbnail_id( $product_id );
				wp_delete_post( $image_id, true );
			}
		}

		if ( 0 != $atts['product_id'] ) {
			$this->the_product = wc_get_product( $atts['product_id'] );
		}

		$header_html .= '<h3>';
		$header_html .= ( 0 == $atts['product_id'] ) ? __( 'Add New Product', 'woocommerce-jetpack' ) : __( 'Edit Product', 'woocommerce-jetpack' );
		$header_html .= '</h3>';
		$header_html .= '<form method="post" action="' . remove_query_arg( array( 'wcj_edit_product_image_delete', 'wcj_delete_product' ) ) . '" enctype="multipart/form-data">'; // todo multipart only if image...

		$required_mark_html_template = '&nbsp;<abbr class="required" title="required">*</abbr>';

		$table_data = array();
		$input_style = 'width:100%;';
		$table_data[] = array(
			'<label for="wcj_add_new_product_title">' . __( 'Title', 'woocommerce-jetpack' ) . $required_mark_html_template . '</label>',
			'<input required type="text" style="' . $input_style . '" id="wcj_add_new_product_title" name="wcj_add_new_product_title" value="' . ( ( 0 != $atts['product_id'] ) ? $this->the_product->get_title() : $args['title'] ) . '">'
		);
		if ( 'yes' === $atts['desc_enabled'] ) {
			$required_html      = ( 'yes' === $atts['desc_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['desc_required'] ) ? $required_mark_html_template : '';
			$table_data[] = array(
				'<label for="wcj_add_new_product_desc">' . __( 'Description', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<textarea' . $required_html . ' style="' . $input_style . '" id="wcj_add_new_product_desc" name="wcj_add_new_product_desc">' . ( ( 0 != $atts['product_id'] ) ? get_post_field( 'post_content', $atts['product_id'] ) : $args['desc'] ) . '</textarea>'
			);
		}
		if ( 'yes' === $atts['short_desc_enabled'] ) {
			$required_html      = ( 'yes' === $atts['short_desc_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['short_desc_required'] ) ? $required_mark_html_template : '';
			$table_data[] = array(
				'<label for="wcj_add_new_product_short_desc">' . __( 'Short Description', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<textarea' . $required_html . ' style="' . $input_style . '" id="wcj_add_new_product_short_desc" name="wcj_add_new_product_short_desc">' . ( ( 0 != $atts['product_id'] ) ? get_post_field( 'post_excerpt', $atts['product_id'] ) : $args['short_desc'] ) . '</textarea>'
			);
		}
		if ( 'yes' === $atts['image_enabled'] ) {
			$required_html      = ( 'yes' === $atts['image_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['image_required'] ) ? $required_mark_html_template : '';
			$new_image_field = '<input' . $required_html . ' type="file" id="wcj_add_new_product_image" name="wcj_add_new_product_image" accept="image/*">';
			if ( 0 != $atts['product_id'] ) {
				$the_field = ( '' == get_post_thumbnail_id( $atts['product_id'] ) ) ?
					$new_image_field :
					'<a href="' . add_query_arg( 'wcj_edit_product_image_delete', $atts['product_id'] ) . '" onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">' . __( 'Delete', 'woocommerce-jetpack' ) . '</a><br>' . get_the_post_thumbnail( $atts['product_id'], array( 50, 50 ) , array( 'class' => 'alignleft' ) );
			} else {
				$the_field = $new_image_field;
			}
			$table_data[] = array(
				'<label for="wcj_add_new_product_image">' . __( 'Image', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				$the_field
			);
		}
		if ( 'yes' === $atts['regular_price_enabled'] ) {
			$required_html      = ( 'yes' === $atts['regular_price_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['regular_price_required'] ) ? $required_mark_html_template : '';
			$table_data[] = array(
				'<label for="wcj_add_new_product_regular_price">' . __( 'Regular Price', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<input' . $required_html . ' type="number" min="0" step="0.01" id="wcj_add_new_product_regular_price" name="wcj_add_new_product_regular_price" value="' . ( ( 0 != $atts['product_id'] ) ? get_post_meta( $atts['product_id'], '_regular_price', true ) : $args['regular_price'] ) . '">'
			);
		}
		if ( 'yes' === $atts['sale_price_enabled'] ) {
			$required_html      = ( 'yes' === $atts['sale_price_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['sale_price_required'] ) ? $required_mark_html_template : '';
			$table_data[] = array(
				'<label for="wcj_add_new_product_sale_price">' . __( 'Sale Price', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<input' . $required_html . ' type="number" min="0" step="0.01" id="wcj_add_new_product_sale_price" name="wcj_add_new_product_sale_price" value="' . ( ( 0 != $atts['product_id'] ) ? get_post_meta( $atts['product_id'], '_sale_price', true ) : $args['sale_price'] ) . '">'
			);
		}
		if ( 'yes' === $atts['cats_enabled'] ) {
			$required_html      = ( 'yes' === $atts['cats_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['cats_required'] ) ? $required_mark_html_template : '';
			$current_product_categories = ( 0 != $atts['product_id'] ) ? get_the_terms( $atts['product_id'], 'product_cat' ) : $args['cats'];
			$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
			$product_categories_as_select_options = '';
			foreach ( $product_categories as $product_category ) {
				$selected = '';
				if ( ! empty( $current_product_categories ) ) {
					foreach ( $current_product_categories as $current_product_category ) {
						if ( is_object( $current_product_category ) ) {
							$current_product_category = $current_product_category->slug;
						}
						$selected .= selected( $current_product_category, $product_category->slug, false );
					}
				}
				$product_categories_as_select_options .= '<option value="' . $product_category->slug . '" ' . $selected . '>' . $product_category->name .'</option>';
			}
			$table_data[] = array(
				'<label for="wcj_add_new_product_cats">' . __( 'Categories', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<select' . $required_html . ' multiple style="' . $input_style . '" id="wcj_add_new_product_cats" name="wcj_add_new_product_cats[]">' . $product_categories_as_select_options . '</select>'
			);
		}
		if ( 'yes' === $atts['tags_enabled'] ) {
			$required_html      = ( 'yes' === $atts['tags_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['tags_required'] ) ? $required_mark_html_template : '';
			$current_product_tags = ( 0 != $atts['product_id'] ) ? get_the_terms( $atts['product_id'], 'product_tag' ) : $args['tags'];
			$products_tags = get_terms( 'product_tag', 'orderby=name&hide_empty=0' );
			$products_tags_as_select_options = '';
			foreach ( $products_tags as $products_tag ) {
				$selected = '';
				if ( ! empty( $current_product_tags ) ) {
					foreach ( $current_product_tags as $current_product_tag ) {
						if ( is_object( $current_product_tag ) ) {
							$current_product_tag = $current_product_tag->slug;
						}
						$selected .= selected( $current_product_tag, $products_tag->slug, false );
					}
				}
				$products_tags_as_select_options .= '<option value="' . $products_tag->slug . '" ' . $selected . '>' . $products_tag->name .'</option>';
			}
			$table_data[] = array(
				'<label for="wcj_add_new_product_tags">' . __( 'Tags', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<select' . $required_html . ' multiple style="' . $input_style . '" id="wcj_add_new_product_tags" name="wcj_add_new_product_tags[]">' . $products_tags_as_select_options . '</select>'
			);
		}

		$input_fields_html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat', 'table_heading_type' => 'vertical', ) );

		$footer_html .= '<input type="submit" class="button" name="wcj_add_new_product" value="' . ( ( 0 == $atts['product_id'] ) ? __( 'Add', 'woocommerce-jetpack' ) : __( 'Edit', 'woocommerce-jetpack' ) ) . '">';
		$footer_html .= '</form>';

		return $notice_html . $header_html . $input_fields_html . $footer_html;
	}
}

endif;

return new WCJ_Products_Add_Form_Shortcodes();

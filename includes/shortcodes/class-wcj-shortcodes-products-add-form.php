<?php
/**
 * Booster for WooCommerce - Shortcodes - Products Add Form
 *
 * @version 2.8.0
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
	 * @version 2.8.0
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
			'external_url_enabled'   => get_option( 'wcj_product_by_user_external_url_enabled', 'no' ),
			'cats_enabled'           => get_option( 'wcj_product_by_user_cats_enabled', 'no' ),
			'tags_enabled'           => get_option( 'wcj_product_by_user_tags_enabled', 'no' ),
			'image_enabled'          => apply_filters( 'booster_option', 'no', get_option( 'wcj_product_by_user_image_enabled', 'no' ) ),

			'desc_required'          => get_option( 'wcj_product_by_user_desc_required', 'no' ),
			'short_desc_required'    => get_option( 'wcj_product_by_user_short_desc_required', 'no' ),
			'regular_price_required' => get_option( 'wcj_product_by_user_regular_price_required', 'no' ),
			'sale_price_required'    => get_option( 'wcj_product_by_user_sale_price_required', 'no' ),
			'external_url_required'  => get_option( 'wcj_product_by_user_external_url_required', 'no' ),
			'cats_required'          => get_option( 'wcj_product_by_user_cats_required', 'no' ),
			'tags_required'          => get_option( 'wcj_product_by_user_tags_required', 'no' ),
			'image_required'         => apply_filters( 'booster_option', 'no', get_option( 'wcj_product_by_user_image_required', 'no' ) ),

			'visibility'             => implode( ',', get_option( 'wcj_product_by_user_user_visibility', array() ) ),
			'module'                 => 'product_by_user',
			'module_name'            => __( 'Product by User', 'woocommerce-jetpack' ),
		);

		if ( 'external' !== get_option( 'wcj_product_by_user_product_type', 'simple' ) ) {
			$this->the_atts['external_url_enabled']  = 'no';
			$this->the_atts['external_url_required'] = 'no';
		}

		$this->the_atts['custom_taxonomies_total'] = apply_filters( 'booster_option', 1, get_option( 'wcj_product_by_user_custom_taxonomies_total', 1 ) );
		for ( $i = 1; $i <= $this->the_atts['custom_taxonomies_total']; $i++ ) {
			$this->the_atts[ 'custom_taxonomy_' . $i . '_enabled' ]  = get_option( 'wcj_product_by_user_custom_taxonomy_' . $i . '_enabled',  'no' );
			$this->the_atts[ 'custom_taxonomy_' . $i . '_required' ] = get_option( 'wcj_product_by_user_custom_taxonomy_' . $i . '_required', 'no' );
			$this->the_atts[ 'custom_taxonomy_' . $i . '_id' ]       = get_option( 'wcj_product_by_user_custom_taxonomy_' . $i . '_id',       '' );
			$this->the_atts[ 'custom_taxonomy_' . $i . '_title' ]    = get_option( 'wcj_product_by_user_custom_taxonomy_' . $i . '_title',    '' );
		}

		parent::__construct();
	}

	/**
	 * wc_add_new_product.
	 *
	 * @version 2.8.0
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

			wp_set_object_terms( $product_id, get_option( 'wcj_product_by_user_product_type', 'simple' ), 'product_type' );
			wp_set_object_terms( $product_id, $args['cats'], 'product_cat' );
			wp_set_object_terms( $product_id, $args['tags'], 'product_tag' );

			for ( $i = 1; $i <= $this->the_atts['custom_taxonomies_total']; $i++ ) {
				if ( 'yes' === $this->the_atts[ 'custom_taxonomy_' . $i . '_enabled' ] && '' != $this->the_atts[ 'custom_taxonomy_' . $i . '_id' ] ) {
					wp_set_object_terms( $product_id, $args[ 'custom_taxonomy_' . $i ], $this->the_atts[ 'custom_taxonomy_' . $i . '_id' ] );
				}
			}

			update_post_meta( $product_id, '_regular_price', $args['regular_price'] );
			update_post_meta( $product_id, '_sale_price', $args['sale_price'] );
			if ( '' == $args['sale_price'] ) {
				update_post_meta( $product_id, '_price', $args['regular_price'] );
			} else {
				update_post_meta( $product_id, '_price', $args['sale_price'] );
			}
			update_post_meta( $product_id, '_visibility', 'visible' );
			update_post_meta( $product_id, '_stock_status', 'instock' );

			if ( 'external' === get_option( 'wcj_product_by_user_product_type', 'simple' ) ) {
				update_post_meta( $product_id, '_product_url', $args['external_url'] );
			}

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

			// TODO:
			// Image gallery
			// <input type="file" multiple> or separate <input type="file"> for each file
			// update_post_meta( $product_id, '_product_image_gallery', implode( ',', $attach_ids ) );

			wp_update_post( array( 'ID' => $product_id, 'post_status' => $shortcode_atts['post_status'] ) );
		}

		return $product_id;
	}

	/**
	 * validate_args.
	 *
	 * @version 2.8.0
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
			'external_url'  => __( 'Product URL', 'woocommerce-jetpack' ),
			'cats'          => __( 'Categories', 'woocommerce-jetpack' ),
			'tags'          => __( 'Tags', 'woocommerce-jetpack' ),
		);
		for ( $i = 1; $i <= $this->the_atts['custom_taxonomies_total']; $i++ ) {
			$fields[ 'custom_taxonomy_' . $i ] = $this->the_atts[ 'custom_taxonomy_' . $i . '_title' ];
		}
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
	 * maybe_add_taxonomy_field.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function maybe_add_taxonomy_field( $atts, $args, $option_id, $taxonomy_id, $title, $input_style, $required_mark_html_template, $table_data ) {
		if ( 'yes' === $atts[ $option_id . '_enabled' ] ) {
			$product_taxonomies = get_terms( $taxonomy_id, 'orderby=name&hide_empty=0' );
			if ( is_wp_error( $product_taxonomies ) ) {
				return $table_data;
			}
			$required_html                        = ( 'yes' === $atts[ $option_id . '_required' ] ) ? ' required' : '';
			$required_mark_html                   = ( 'yes' === $atts[ $option_id . '_required' ] ) ? $required_mark_html_template : '';
			$current_product_taxonomies           = ( 0 != $atts['product_id'] ) ? get_the_terms( $atts['product_id'], $taxonomy_id ) : $args[ $option_id ];
			$product_taxonomies_as_select_options = '';
			foreach ( $product_taxonomies as $product_taxonomy ) {
				$selected = '';
				if ( ! empty( $current_product_taxonomies ) ) {
					foreach ( $current_product_taxonomies as $current_product_taxonomy ) {
						if ( is_object( $current_product_taxonomy ) ) {
							$current_product_taxonomy = $current_product_taxonomy->slug;
						}
						$selected .= selected( $current_product_taxonomy, $product_taxonomy->slug, false );
					}
				}
				$product_taxonomies_as_select_options .= '<option value="' . $product_taxonomy->slug . '" ' . $selected . '>' . $product_taxonomy->name . '</option>';
			}
			$table_data[] = array(
				'<label for="wcj_add_new_product_' . $option_id . '">' . $title . $required_mark_html . '</label>',
				'<select' . $required_html . ' multiple style="' . $input_style . '" id="wcj_add_new_product_' . $option_id . '" name="wcj_add_new_product_' . $option_id . '[]">' .
					$product_taxonomies_as_select_options .
				'</select>'
			);
		}
		return $table_data;
	}

	/**
	 * wcj_product_add_new.
	 *
	 * @version 2.8.0
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
			'external_url'      => isset( $_REQUEST['wcj_add_new_product_external_url'] )  ? $_REQUEST['wcj_add_new_product_external_url']  : '',
			'cats'              => isset( $_REQUEST['wcj_add_new_product_cats'] )          ? $_REQUEST['wcj_add_new_product_cats']          : array(),
			'tags'              => isset( $_REQUEST['wcj_add_new_product_tags'] )          ? $_REQUEST['wcj_add_new_product_tags']          : array(),
			'image'             => isset( $_FILES['wcj_add_new_product_image'] )           ? $_FILES['wcj_add_new_product_image']           : '',
		);
		for ( $i = 1; $i <= $this->the_atts['custom_taxonomies_total']; $i++ ) {
			$param_id = 'wcj_add_new_product_' . 'custom_taxonomy_' . $i;
			$args[ 'custom_taxonomy_' . $i ] = isset( $_REQUEST[ $param_id ] ) ? $_REQUEST[ $param_id ] : array();
		}

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
		$header_html .= '<form method="post" action="' . remove_query_arg( array( 'wcj_edit_product_image_delete', 'wcj_delete_product' ) ) .
			'" enctype="multipart/form-data">'; // todo multipart only if image...

		$required_mark_html_template = '&nbsp;<abbr class="required" title="' . __( 'required', 'woocommerce-jetpack' ) . '">*</abbr>';

		$price_step = sprintf( "%f", ( 1 / pow( 10, get_option( 'wcj_product_by_user_price_step', get_option( 'woocommerce_price_num_decimals', 2 ) ) ) ) );

		$table_data = array();
		$input_style = 'width:100%;';
		$table_data[] = array(
			'<label for="wcj_add_new_product_title">' . __( 'Title', 'woocommerce-jetpack' ) . $required_mark_html_template . '</label>',
			'<input required type="text" style="' . $input_style . '" id="wcj_add_new_product_title" name="wcj_add_new_product_title" value="' . ( ( 0 != $atts['product_id'] ) ?
				$this->the_product->get_title() : $args['title'] ) . '">'
		);
		if ( 'yes' === $atts['desc_enabled'] ) {
			$required_html      = ( 'yes' === $atts['desc_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['desc_required'] ) ? $required_mark_html_template : '';
			$table_data[] = array(
				'<label for="wcj_add_new_product_desc">' . __( 'Description', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<textarea' . $required_html . ' style="' . $input_style . '" id="wcj_add_new_product_desc" name="wcj_add_new_product_desc">' . ( ( 0 != $atts['product_id'] ) ?
					get_post_field( 'post_content', $atts['product_id'] ) : $args['desc'] ) . '</textarea>'
			);
		}
		if ( 'yes' === $atts['short_desc_enabled'] ) {
			$required_html      = ( 'yes' === $atts['short_desc_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['short_desc_required'] ) ? $required_mark_html_template : '';
			$table_data[] = array(
				'<label for="wcj_add_new_product_short_desc">' . __( 'Short Description', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<textarea' . $required_html . ' style="' . $input_style . '" id="wcj_add_new_product_short_desc" name="wcj_add_new_product_short_desc">' .
					( ( 0 != $atts['product_id'] ) ? get_post_field( 'post_excerpt', $atts['product_id'] ) : $args['short_desc'] ) . '</textarea>'
			);
		}
		if ( 'yes' === $atts['image_enabled'] ) {
			$required_html      = ( 'yes' === $atts['image_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['image_required'] ) ? $required_mark_html_template : '';
			$new_image_field = '<input' . $required_html . ' type="file" id="wcj_add_new_product_image" name="wcj_add_new_product_image" accept="image/*">';
			if ( 0 != $atts['product_id'] ) {
				$the_field = ( '' == get_post_thumbnail_id( $atts['product_id'] ) ) ?
					$new_image_field :
					'<a href="' . add_query_arg( 'wcj_edit_product_image_delete', $atts['product_id'] ) . '" onclick="return confirm(\'' .
						__( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">' . __( 'Delete', 'woocommerce-jetpack' ) . '</a><br>' .
						get_the_post_thumbnail( $atts['product_id'], array( 50, 50 ) , array( 'class' => 'alignleft' ) );
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
				'<input' . $required_html . ' type="number" min="0" step="' . $price_step . '" id="wcj_add_new_product_regular_price" name="wcj_add_new_product_regular_price" value="' .
					( ( 0 != $atts['product_id'] ) ? get_post_meta( $atts['product_id'], '_regular_price', true ) : $args['regular_price'] ) . '">'
			);
		}
		if ( 'yes' === $atts['sale_price_enabled'] ) {
			$required_html      = ( 'yes' === $atts['sale_price_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['sale_price_required'] ) ? $required_mark_html_template : '';
			$table_data[] = array(
				'<label for="wcj_add_new_product_sale_price">' . __( 'Sale Price', 'woocommerce-jetpack' ) . $required_mark_html . '</label>',
				'<input' . $required_html . ' type="number" min="0" step="' . $price_step . '" id="wcj_add_new_product_sale_price" name="wcj_add_new_product_sale_price" value="' .
					( ( 0 != $atts['product_id'] ) ? get_post_meta( $atts['product_id'], '_sale_price', true ) : $args['sale_price'] ) . '">'
			);
		}
		if ( 'yes' === $atts['external_url_enabled'] ) {
			$required_html      = ( 'yes' === $atts['external_url_required'] ) ? ' required' : '';
			$required_mark_html = ( 'yes' === $atts['external_url_required'] ) ? $required_mark_html_template : '';
			$table_data[] = array(
				'<label for="wcj_add_new_product_external_url">' . __( 'Product URL', 'woocommerce-jetpack' ) . $required_mark_html_template . '</label>',
				'<input' . $required_html . ' style="' . $input_style . '" type="url" id="wcj_add_new_product_external_url" name="wcj_add_new_product_external_url" value="' .
					( ( 0 != $atts['product_id'] ) ? get_post_meta( $atts['product_id'], '_product_url', true ) : $args['external_url'] ) . '">',
			);
		}
		$table_data = $this->maybe_add_taxonomy_field(
			$atts, $args,
			'cats', 'product_cat', __( 'Categories', 'woocommerce-jetpack' ),
			$input_style, $required_mark_html_template, $table_data
		);
		$table_data = $this->maybe_add_taxonomy_field(
			$atts, $args,
			'tags', 'product_tag', __( 'Tags', 'woocommerce-jetpack' ),
			$input_style, $required_mark_html_template, $table_data
		);
		for ( $i = 1; $i <= $this->the_atts['custom_taxonomies_total']; $i++ ) {
			$table_data = $this->maybe_add_taxonomy_field(
				$atts, $args,
				'custom_taxonomy_' . $i, $atts[ 'custom_taxonomy_' . $i . '_id' ], $atts[ 'custom_taxonomy_' . $i . '_title' ],
				$input_style, $required_mark_html_template, $table_data
			);
		}

		$input_fields_html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat', 'table_heading_type' => 'vertical', ) );

		$footer_html .= '<input type="submit" class="button" name="wcj_add_new_product" value="' . ( ( 0 == $atts['product_id'] ) ?
			__( 'Add', 'woocommerce-jetpack' ) : __( 'Edit', 'woocommerce-jetpack' ) ) . '">';
		$footer_html .= '</form>';

		return $notice_html . $header_html . $input_fields_html . $footer_html;
	}

}

endif;

return new WCJ_Products_Add_Form_Shortcodes();

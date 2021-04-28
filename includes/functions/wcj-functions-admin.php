<?php
/**
 * Booster for WooCommerce - Functions - Admin
 *
 * @version 5.3.3
 * @since   2.9.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_get_module_settings_admin_url' ) ) {
	/**
	 * wcj_get_module_settings_admin_url.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    use this function where needed
	 */
	function wcj_get_module_settings_admin_url( $module_id ) {
		return admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . wcj_get_module_category( $module_id ) . '&section=' . $module_id );
	}
}

if ( ! function_exists( 'wcj_get_module_category' ) ) {
	/**
	 * wcj_get_module_category.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    better solution for `global $wcj_modules_cats`
	 * @todo    use this function where needed (e.g. in `class-wc-settings-jetpack.php`)
	 */
	function wcj_get_module_category( $module_id ) {
		global $wcj_modules_cats;
		if ( ! isset( $wcj_modules_cats ) ) {
			$wcj_modules_cats = include( WCJ_PLUGIN_PATH . '/includes/admin/wcj-modules-cats.php' );
		}
		foreach ( $wcj_modules_cats as $cat_id => $cat_data ) {
			if ( ! empty( $cat_data['all_cat_ids'] ) && in_array( $module_id, $cat_data['all_cat_ids'] ) ) {
				return $cat_id;
			}
		}
		return '';
	}
}

if ( ! function_exists( 'wcj_get_product_ids_for_meta_box_options' ) ) {
	/**
	 * wcj_get_product_ids_for_meta_box_options.
	 *
	 * @version 3.5.0
	 * @since   3.3.0
	 * @todo    use this function where needed
	 */
	function wcj_get_product_ids_for_meta_box_options( $main_product_id, $do_get_all_variations = false ) {
		$_product = wc_get_product( $main_product_id );
		if ( ! $_product ) {
			return array();
		}
		$products = array();
		if ( $_product->is_type( 'variable' ) ) {
			if ( $do_get_all_variations ) {
				$all_variations = $_product->get_children();
				foreach ( $all_variations as $variation_id ) {
					$variation_product = wc_get_product( $variation_id );
					$products[ $variation_id ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
				}
			} else {
				$available_variations = $_product->get_available_variations();
				foreach ( $available_variations as $variation ) {
					$variation_product = wc_get_product( $variation['variation_id'] );
					$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
				}
			}
		} else {
			$products[ $main_product_id ] = '';
		}
		return $products;
	}
}

if ( ! function_exists( 'wcj_is_admin_product_edit_page' ) ) {
	/**
	 * wcj_is_admin_product_edit_page.
	 *
	 * @version 3.6.0
	 * @since   3.2.4
	 * @todo    use where appropriate
	 * @todo    (maybe) move to `wcj-functions-conditional.php`
	 */
	function wcj_is_admin_product_edit_page() {
		global $pagenow;
		if ( is_admin() && 'post.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === $_GET['action'] && 'product' === get_post_type() ) {
			return true;
		} elseif ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && 'woocommerce_load_variations' === $_REQUEST['action'] ) {
			return true;
		} else {
			return false;
		}
	}
}


if ( ! function_exists( 'wcj_is_admin_product_quick_edit_page' ) ) {
	/**
	 * wcj_is_admin_product_quick_edit_page.
	 *
	 * @version 5.4.0
	 * @since   3.2.5
	 * @todo    use where appropriate
	 * @todo    (maybe) move to `wcj-functions-conditional.php`
	 */
	function wcj_is_admin_product_quick_edit_page() {
		global $pagenow;
		if (( $pagenow == 'admin-ajax.php' ) && is_admin()) {
			return true;
		}
		 else {
		return false;
		}
	}
}



if ( ! function_exists( 'wcj_admin_notices_version_updated' ) ) {
	/**
	 * wcj_admin_notices_version_updated.
	 *
	 * @version 3.3.0
	 * @since   2.8.0
	 */
	function wcj_admin_notices_version_updated() {
		if ( wcj_get_option( WCJ_VERSION_OPTION ) === WCJ()->version ) {
			$class   = 'notice notice-success is-dismissible';
			$message = sprintf( __( '<strong>Booster for WooCommerce</strong> plugin was successfully updated to version <strong>%s</strong>.', 'woocommerce-jetpack' ), WCJ()->version );
			echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}
	}
}

if ( ! function_exists( 'wcj_get_ajax_settings' ) ) {
	/**
	 * wcj_get_ajax_settings
	 *
	 * @version 5.3.3
	 * @since   4.3.0
	 *
	 * @param $values
	 * @param string $search_type Possible values 'woocommerce_json_search_products', 'woocommerce_json_search_products_and_variations' , 'woocommerce_json_search_categories', 'woocommerce_json_search_customers'
	 * @param bool $allow_multiple_values
	 *
	 * @return array
	 */
	function wcj_get_ajax_settings( $values, $allow_multiple_values = false, $search_type = 'woocommerce_json_search_products' ) {
		$options_raw = wcj_get_option( $values['id'], isset( $values['default'] ) ? $values['default'] : '' );
		$options_raw = empty( $options_raw ) ? array() : $options_raw;
		$options     = array();
		$class       = '';
		if ( $search_type == 'woocommerce_json_search_products' || $search_type == 'woocommerce_json_search_products_and_variations' ) {
			$class = 'wc-product-search';
			if( $options_raw ) {
				foreach ( $options_raw as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_a( $product, 'WC_Product' ) ) {
						$options[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
					}
				}
			}
		} elseif ( $search_type == 'woocommerce_json_search_categories' ) {
			$class = 'wc-category-search';
			foreach ( $options_raw as $term_id ) {
				$term                = get_term_by( 'slug', $term_id, 'product_cat' );
				$options[ $term_id ] = wp_kses_post( $term->name );
			}
		} elseif ( $search_type == 'woocommerce_json_search_customers' ) {
			$class = 'wc-customer-search';
			foreach ( $options_raw as $term_id ) {
				$user                = get_user_by( 'id', $term_id );
				$options[ $term_id ] = wp_kses_post( $user->display_name );
			}
		}
		$placeholder = isset( $values['placeholder'] ) ? isset( $values['placeholder'] ) : __( "Search&hellip;", 'woocommerce-jetpack' );
		return array_merge( $values, array(
			'custom_attributes'            => array(
				'data-action'      => $search_type,
				'data-allow_clear' => "true",
				'aria-hidden'      => "true",
				'data-sortable'    => "true",
				'data-placeholder' => $placeholder
			),
			'type'                         => $allow_multiple_values ? 'multiselect' : 'select',
			'options'                      => $options,
			'class'                        => $class,
			'ignore_enhanced_select_class' => true
		) );
	}
}

if ( ! function_exists( 'wcj_get_settings_as_multiselect_or_text' ) ) {
	/**
	 * wcj_get_settings_as_multiselect_or_text.
	 *
	 * @version 4.3.0
	 * @since   2.9.1
	 */
	function wcj_get_settings_as_multiselect_or_text( $values, $multiselect_options, $is_multiselect ) {
		$prev_desc = ( isset( $values['desc'] ) ? $values['desc'] . ' ' : '' );

		if ( $is_multiselect ) {
			if ( ! empty( $multiselect_options ) ) {
				return array_merge( $values, array(
					'type'    => 'multiselect',
					'class'   => 'chosen_select',
					'options' => $multiselect_options,
				) );
			} else {
				return wcj_get_ajax_settings( $values, true );
			}
		} else {
			return array_merge( $values, array(
				'type' => 'text',
				'desc' => $prev_desc . __( 'Enter comma separated list of IDs.', 'woocommerce-jetpack' ),
			) );
		}
	}
}

if ( ! function_exists( 'wcj_convert_string_to_array' ) ) {
	/**
	 * wcj_convert_string_to_array.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    check `custom_explode` function
	 */
	function wcj_convert_string_to_array( $value ) {
		if ( '' === $value ) {
			$value = array();
		} else {
			$value = str_replace( ' ', '', $value );
			$value = explode( ',', $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'wcj_maybe_convert_and_update_option_value' ) ) {
	/**
	 * wcj_maybe_convert_and_update_option_value.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function wcj_maybe_convert_and_update_option_value( $options, $is_multiselect ) {
		foreach ( $options as $option ) {
			$value = wcj_get_option( $option['id'], $option['default'] );
			if ( ! $is_multiselect ) {
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
					update_option( $option['id'], $value );
				}
			} else {
				if ( is_string( $value ) ) {
					$value = wcj_convert_string_to_array( $value );
					update_option( $option['id'], $value );
				}
			}
		}
	}
}

if ( ! function_exists( 'wcj_maybe_convert_string_to_array' ) ) {
	/**
	 * wcj_maybe_convert_string_to_array.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function wcj_maybe_convert_string_to_array( $value ) {
		if ( is_string( $value ) ) {
			$value = wcj_convert_string_to_array( $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'wcj_message_replaced_values' ) ) {
	/**
	 * wcj_message_replaced_values.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    use this function in all applicable settings descriptions
	 */
	function wcj_message_replaced_values( $values ) {
		$message_template = ( 1 == count( $values ) ? __( 'Replaced value: %s', 'woocommerce-jetpack' ) : __( 'Replaced values: %s', 'woocommerce-jetpack' ) );
		return sprintf( $message_template, '<code>' . implode( '</code>, <code>', $values ) . '</code>' );
	}
}

if ( ! function_exists( 'wcj_get_5_rocket_image' ) ) {
	/**
	 * wcj_get_5_rocket_image.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function wcj_get_5_rocket_image() {
		return '<img class="wcj-rocket-icon" src="' . wcj_plugin_url() . '/assets/images/5-rockets.png' . '" title="">';
	}
}

if ( ! function_exists( 'wcj_get_plus_message' ) ) {
	/**
	 * wcj_get_plus_message.
	 *
	 * @version 3.6.0
	 */
	function wcj_get_plus_message( $value, $message_type, $args = array() ) {

		switch ( $message_type ) {

			case 'global':
				return '<div class="notice notice-warning">' .
						'<p><strong>' . __( 'Install Booster Plus to unlock all features', 'woocommerce-jetpack' ) . '</strong></p>' .
						'<p><span>' . sprintf( __( 'Some settings fields are locked and you will need %s to modify all locked fields.', 'woocommerce-jetpack'),
							'<a href="https://booster.io/plus/" target="_blank">Booster for WooCommerce Plus</a>' ) . '</span></p>' .
						'<p>' .
							'<a href="https://booster.io/plus/" target="_blank" class="button button-primary">' . __( 'Buy now', 'woocommerce-jetpack' ) . '</a>' . ' ' .
							'<a href="https://booster.io" target="_blank" class="button">' . __( 'Visit Booster Site', 'woocommerce-jetpack' ) . '</a>' .
						'</p>' .
					'</div>';

			case 'desc':
				return sprintf( __( 'Get <a href="%s" target="_blank">Booster Plus</a> to change value.', 'woocommerce-jetpack' ), 'https://booster.io/plus/' );

			case 'desc_advanced':
				return sprintf( __( 'Get <a href="%s" target="_blank">Booster Plus</a> to enable "%s" option.', 'woocommerce-jetpack' ), 'https://booster.io/plus/', $args['option'] );

			case 'desc_advanced_no_link':
				return sprintf( __( 'Get Booster Plus to enable "%s" option.', 'woocommerce-jetpack' ), $args['option'] );

			case 'desc_below':
				return sprintf( __( 'Get <a href="%s" target="_blank">Booster Plus</a> to change values below.', 'woocommerce-jetpack' ), 'https://booster.io/plus/' );

			case 'desc_above':
				return sprintf( __( 'Get <a href="%s" target="_blank">Booster Plus</a> to change values above.', 'woocommerce-jetpack' ), 'https://booster.io/plus/' );

			case 'desc_no_link':
				return __( 'Get Booster Plus to change value.', 'woocommerce-jetpack' );

			case 'readonly':
				return array( 'readonly' => 'readonly' );

			case 'disabled':
				return array( 'disabled' => 'disabled' );

			case 'readonly_string':
				return 'readonly';

			case 'disabled_string':
				return 'disabled';
		}

		return $value;
	}
}

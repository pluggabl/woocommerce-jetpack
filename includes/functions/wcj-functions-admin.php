<?php
/**
 * Booster for WooCommerce - Functions - Admin
 *
 * @version 7.2.9
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_get_module_settings_admin_url' ) ) {
	/**
	 * Wcj_get_module_settings_admin_url.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    use this function where needed
	 * @param   int $module_id defines the module_id.
	 */
	function wcj_get_module_settings_admin_url( $module_id ) {
		return admin_url( wcj_admin_tab_url() . '&wcj-cat=' . wcj_get_module_category( $module_id ) . '&section=' . $module_id );
	}
}

if ( ! function_exists( 'wcj_get_module_category' ) ) {
	/**
	 * Wcj_get_module_category.
	 *
	 * @version 5.6.1
	 * @since   3.3.0
	 * @todo    better solution for `global $wcj_modules_cats`
	 * @todo    use this function where needed (e.g. in `class-wc-settings-jetpack.php`)
	 * @param   int $module_id defines the module_id.
	 */
	function wcj_get_module_category( $module_id ) {
		global $wcj_modules_cats;
		if ( ! isset( $wcj_modules_cats ) ) {
			$wcj_modules_cats = include WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-modules-cats.php';
		}
		foreach ( $wcj_modules_cats as $cat_id => $cat_data ) {
			if ( ! empty( $cat_data['all_cat_ids'] ) && in_array( $module_id, $cat_data['all_cat_ids'], true ) ) {
				return $cat_id;
			}
		}
		return '';
	}
}

if ( ! function_exists( 'wcj_get_product_ids_for_meta_box_options' ) ) {
	/**
	 * Wcj_get_product_ids_for_meta_box_options.
	 *
	 * @version 3.5.0
	 * @since   3.3.0
	 * @todo    use this function where needed
	 * @param   int  $main_product_id defines the main_product_id.
	 * @param   bool $do_get_all_variations defines the do_get_all_variations.
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
					$variation_product         = wc_get_product( $variation_id );
					$products[ $variation_id ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
				}
			} else {
				$available_variations = $_product->get_available_variations();
				foreach ( $available_variations as $variation ) {
					$variation_product                      = wc_get_product( $variation['variation_id'] );
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
	 * Wcj_is_admin_product_edit_page.
	 *
	 * @version 5.6.8
	 * @since   3.2.4
	 * @todo    use where appropriate
	 * @todo    (maybe) move to `wcj-functions-conditional.php`
	 */
	function wcj_is_admin_product_edit_page() {
		global $pagenow;
		// phpcs:disable WordPress.Security.NonceVerification
		if ( is_admin() && 'post.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === $_GET['action'] && 'product' === get_post_type() ) {
			return true;
		} elseif ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && 'woocommerce_load_variations' === $_REQUEST['action'] ) {
			return true;
		} else {
			return false;
		}
		// phpcs:enable WordPress.Security.NonceVerification
	}
}


if ( ! function_exists( 'wcj_is_admin_product_quick_edit_page' ) ) {
	/**
	 * Wcj_is_admin_product_quick_edit_page.
	 *
	 * @version 5.4.0
	 * @since   3.2.5
	 * @todo    use where appropriate
	 * @todo    (maybe) move to `wcj-functions-conditional.php`
	 */
	function wcj_is_admin_product_quick_edit_page() {
		global $pagenow;
		if ( ( 'admin-ajax.php' === $pagenow ) && is_admin() ) {
			return true;
		} else {
			return false;
		}
	}
}



if ( ! function_exists( 'wcj_admin_notices_version_updated' ) ) {
	/**
	 * Wcj_admin_notices_version_updated.
	 *
	 * @version 3.3.0
	 * @since   2.8.0
	 */
	function wcj_admin_notices_version_updated() {
		if ( wcj_get_option( WCJ_VERSION_OPTION ) === w_c_j()->version ) {
			$class = 'notice notice-success is-dismissible';
			/* translators: %s: translation added */
			$message = sprintf( __( '<strong>Booster for WooCommerce</strong> plugin was successfully updated to version <strong>%s</strong>.', 'woocommerce-jetpack' ), w_c_j()->version );
			printf( '<div class="%1$s"><p>%2$s</p></div>', wp_kses_post( $class ), wp_kses_post( $message ) );
		}
	}
}

if ( ! function_exists( 'wcj_get_ajax_settings' ) ) {
	/**
	 * Wcj_get_ajax_settings
	 *
	 * @version 6.0.1
	 * @since   4.3.0
	 *
	 * @param array  $values get values.
	 * @param   bool   $allow_multiple_values defines the allow_multiple_values.
	 * @param string $search_type Possible values 'woocommerce_json_search_products', 'woocommerce_json_search_products_and_variations' , 'woocommerce_json_search_categories', 'woocommerce_json_search_customers'.
	 *
	 * @return array
	 */
	function wcj_get_ajax_settings( $values, $allow_multiple_values = false, $search_type = 'woocommerce_json_search_products' ) {
		$options_raw = get_option( $values['id'], isset( $values['default'] ) ? $values['default'] : '' );
		$options_raw = empty( $options_raw ) ? array() : $options_raw;
		$options     = array();
		$class       = '';
		if ( 'woocommerce_json_search_products' === $search_type || 'woocommerce_json_search_products_and_variations' === $search_type ) {
			$class = 'wc-product-search';
			if ( $options_raw ) {
				foreach ( $options_raw as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_a( $product, 'WC_Product' ) ) {
						$options[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
					}
				}
			}
		} elseif ( 'woocommerce_json_search_categories' === $search_type ) {
			$class = 'wc-category-search';
			foreach ( $options_raw as $term_id ) {
				$term                = get_term_by( 'slug', $term_id, 'product_cat' );
				$options[ $term_id ] = wp_kses_post( $term->name );
			}
		} elseif ( 'woocommerce_json_search_customers' === $search_type ) {
			$class = 'wc-customer-search';
			foreach ( $options_raw as $term_id ) {
				$user                = get_user_by( 'id', $term_id );
				$options[ $term_id ] = wp_kses_post( $user->display_name );
			}
		}
		$placeholder = isset( $values['placeholder'] ) ? $values['placeholder'] : __( 'Search&hellip;', 'woocommerce-jetpack' );
		return array_merge(
			$values,
			array(
				'custom_attributes'            => array(
					'data-action'      => $search_type,
					'data-allow_clear' => 'true',
					'aria-hidden'      => 'true',
					'data-sortable'    => 'true',
					'data-placeholder' => $placeholder,
				),
				'type'                         => $allow_multiple_values ? 'multiselect' : 'select',
				'options'                      => $options,
				'class'                        => $class,
				'ignore_enhanced_select_class' => true,
			)
		);
	}
}

if ( ! function_exists( 'wcj_get_settings_as_multiselect_or_text' ) ) {
	/**
	 * Wcj_get_settings_as_multiselect_or_text.
	 *
	 * @version 4.3.0
	 * @since   2.9.1
	 * @param   array         $values defines the values.
	 * @param   string        $multiselect_options defines the multiselect_options.
	 * @param   bool | string $is_multiselect defines the is_multiselect.
	 */
	function wcj_get_settings_as_multiselect_or_text( $values, $multiselect_options, $is_multiselect ) {
		$prev_desc = ( isset( $values['desc'] ) ? $values['desc'] . ' ' : '' );

		if ( $is_multiselect ) {
			if ( ! empty( $multiselect_options ) ) {
				return array_merge(
					$values,
					array(
						'type'    => 'multiselect',
						'class'   => 'chosen_select',
						'options' => $multiselect_options,
					)
				);
			} else {
				return wcj_get_ajax_settings( $values, true );
			}
		} else {
			return array_merge(
				$values,
				array(
					'type' => 'text',
					'desc' => $prev_desc . __( 'Enter comma separated list of IDs.', 'woocommerce-jetpack' ),
				)
			);
		}
	}
}

if ( ! function_exists( 'wcj_convert_string_to_array' ) ) {
	/**
	 * Wcj_convert_string_to_array.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    check `custom_explode` function
	 * @param   array | string $value defines the value.
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
	 * Wcj_maybe_convert_and_update_option_value.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @param   array         $options defines the options.
	 * @param   bool | string $is_multiselect defines the is_multiselect.
	 */
	function wcj_maybe_convert_and_update_option_value( $options, $is_multiselect ) {
		foreach ( $options as $option ) {
			$value = wcj_get_option( $option['id'], $option['default'] );
			if ( ! $is_multiselect ) {
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
					update_option( $option['id'], $value );
				}
			} elseif ( is_string( $value ) ) {
					$value = wcj_convert_string_to_array( $value );
					update_option( $option['id'], $value );
			}
		}
	}
}

if ( ! function_exists( 'wcj_maybe_convert_string_to_array' ) ) {
	/**
	 * Wcj_maybe_convert_string_to_array.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @param   string $value defines the value.
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
	 * Wcj_message_replaced_values.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    use this function in all applicable settings descriptions
	 * @param   string | array $values defines the values.
	 */
	function wcj_message_replaced_values( $values ) {
		/* translators: %s: translation added */
		$message_template = ( 1 === count( $values ) ? __( 'Replaced value: %s', 'woocommerce-jetpack' ) : __( 'Replaced values: %s', 'woocommerce-jetpack' ) );
		return sprintf( $message_template, '<code>' . implode( '</code>, <code>', $values ) . '</code>' );
	}
}

if ( ! function_exists( 'wcj_get_5_rocket_image' ) ) {
	/**
	 * Wcj_get_5_rocket_image.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function wcj_get_5_rocket_image() {
		return '<img class="wcj-rocket-icon" src="' . wcj_plugin_url() . '/assets/images/5-rockets.png " title="">';
	}
}

if ( ! function_exists( 'wcj_build_commercial_url' ) ) {
	/**
	 * Build a tagged Booster commercial/account URL for in-plugin CTAs.
	 *
	 * Internal helper used under existing public CTA mechanisms so routing and
	 * UTM tagging stay consistent without a broad API migration.
	 *
	 * @version 7.11.4
	 * @since   7.11.4
	 * @param   string $intent  compare|buy|assist|account|renewal.
	 * @param   array  $context Optional routing and tagging context.
	 * @return  string
	 */
	function wcj_build_commercial_url( $intent, $context = array() ) {
		$context = wp_parse_args(
			$context,
			array(
				'url'        => '',
				'path'       => '',
				'source'     => 'booster',
				'medium'     => 'inplugin',
				'campaign'   => '',
				'content'    => '',
				'surface'    => '',
				'module_id'  => '',
				'cta_id'     => '',
				'fragment'   => '',
				'query_args' => array(),
			)
		);

		$intent           = sanitize_key( $intent );
		$base_url         = 'https://booster.io/';
		$default_campaign = 'generic_upsell';
		$default_suffix   = 'assist';

		switch ( $intent ) {
			case 'compare':
				$base_url         = 'https://booster.io/free-vs-elite/';
				$default_campaign = 'generic_upsell';
				$default_suffix   = 'compare';
				break;
			case 'buy':
				$base_url         = 'https://booster.io/buy-booster/';
				$default_campaign = 'generic_upsell';
				$default_suffix   = 'buy';
				break;
			case 'account':
				$base_url         = 'https://booster.io/my-account/';
				$default_campaign = 'account';
				$default_suffix   = 'account';
				break;
			case 'renewal':
				$base_url         = 'https://booster.io/my-account/';
				$default_campaign = 'renewal';
				$default_suffix   = 'account';
				break;
		}

		if ( ! empty( $context['url'] ) ) {
			$base_url = (string) $context['url'];
		} elseif ( ! empty( $context['path'] ) ) {
			$base_url = 'https://booster.io/' . ltrim( (string) $context['path'], '/' );
			if ( false === strpos( $base_url, '?' ) && false === strpos( $base_url, '#' ) && '/' !== substr( $base_url, -1 ) ) {
				$base_url .= '/';
			}
		}

		$campaign = ( ! empty( $context['campaign'] ) ? sanitize_key( $context['campaign'] ) : $default_campaign );

		if ( ! empty( $context['content'] ) ) {
			$content = sanitize_key( $context['content'] );
		} else {
			$content_parts = array_filter(
				array(
					sanitize_key( $context['surface'] ),
					sanitize_key( $context['module_id'] ),
					sanitize_key( $context['cta_id'] ),
				)
			);
			$content       = ( empty( $content_parts ) ? 'admin' : implode( '_', $content_parts ) ) . '__' . $default_suffix;
		}

		$query_args = array(
			'utm_source'   => sanitize_key( $context['source'] ),
			'utm_medium'   => sanitize_key( $context['medium'] ),
			'utm_campaign' => $campaign,
			'utm_content'  => $content,
		);

		if ( ! empty( $context['query_args'] ) && is_array( $context['query_args'] ) ) {
			$query_args = array_merge( $query_args, $context['query_args'] );
		}

		$url = add_query_arg( array_filter( $query_args, 'strlen' ), $base_url );

		if ( ! empty( $context['fragment'] ) ) {
			$url .= '#' . ltrim( (string) $context['fragment'], '#' );
		}

		return $url;
	}
}

if ( ! function_exists( 'wcj_replace_booster_url' ) ) {
	/**
	 * Replace a hardcoded Booster URL inside translated HTML.
	 *
	 * @version 7.11.4
	 * @since   7.11.4
	 * @param   string $text         HTML/text that contains the original URL.
	 * @param   string $new_url      Replacement URL.
	 * @param   string $original_url URL currently embedded in the string.
	 * @return  string
	 */
	function wcj_replace_booster_url( $text, $new_url, $original_url = 'https://booster.io/buy-booster/' ) {
		return str_replace( $original_url, esc_url( $new_url ), $text );
	}
}

if ( ! function_exists( 'wcj_get_plus_message' ) ) {
	/**
	 * Wcj_get_plus_message.
	 *
	 * @version 7.11.4
	 * @param   string | array $value defines the value.
	 * @param   string         $message_type defines the message_type.
	 * @param   array          $args defines the args.
	 */
	function wcj_get_plus_message( $value, $message_type, $args = array() ) {

		switch ( $message_type ) {

			case 'global':
				$compare_text_url = esc_url(
					wcj_build_commercial_url(
						'compare',
						array(
							'campaign' => 'generic_upsell',
							'content'  => 'plus_message_global__compare_text',
						)
					)
				);
				$buy_url          = esc_url(
					wcj_build_commercial_url(
						'buy',
						array(
							'campaign' => 'generic_upsell',
							'content'  => 'plus_message_global__buy_button',
						)
					)
				);
				$site_url         = esc_url(
					wcj_build_commercial_url(
						'assist',
						array(
							'campaign' => 'generic_upsell',
							'content'  => 'plus_message_global__assist',
							'url'      => 'https://booster.io/',
						)
					)
				);
				return '<div class="notice notice-warning">' .
					'<p><strong>' . __( 'Upgrade Booster to unlock this feature', 'woocommerce-jetpack' ) . '</strong></p>' .
					'<p><span>' . sprintf(
						/* translators: %s: translation added */
						__( 'Some settings fields are locked and you will need %s to modify all locked fields.', 'woocommerce-jetpack' ),
						'<a href="' . $compare_text_url . '" target="_blank">Booster for WooCommerce </a>'
					) . '</span></p>' .
					'<p>' .
					'<a href="' . $buy_url . '" target="_blank" class="button button-primary">' . __( 'Buy now', 'woocommerce-jetpack' ) . '</a> <a href="' . $site_url . '" target="_blank" class="button">' . __( 'Visit Booster Site', 'woocommerce-jetpack' ) . '</a>' .
					'</p>' .
					'</div>';

			case 'desc':
				return sprintf(
					/* translators: %s: translation added */
					__( 'Upgrade <a href="%s" target="_blank">Booster</a> to change value.', 'woocommerce-jetpack' ),
					esc_url(
						wcj_build_commercial_url(
							'compare',
							array(
								'campaign' => 'generic_upsell',
								'content'  => 'plus_message_desc__compare',
							)
						)
					)
				);

			case 'desc_advanced':
				return sprintf(
					/* translators: %s: translation added */
					__( 'Upgrade <a href="%1$s" target="_blank">Booster to unlock this feature</a> to enable "%2$s" option.', 'woocommerce-jetpack' ),
					esc_url(
						wcj_build_commercial_url(
							'compare',
							array(
								'campaign' => 'generic_upsell',
								'content'  => 'plus_message_desc_advanced__compare',
							)
						)
					),
					$args['option']
				);

			case 'desc_advanced_no_link':
				/* translators: %s: translation added */
				return sprintf( __( 'Upgrade Booster to to enable "%s" option.', 'woocommerce-jetpack' ), $args['option'] );

			case 'desc_below':
				return sprintf(
					/* translators: %s: translation added */
					__( 'Upgrade  <a href="%s" target="_blank">Booster</a> to change values below.', 'woocommerce-jetpack' ),
					esc_url(
						wcj_build_commercial_url(
							'compare',
							array(
								'campaign' => 'generic_upsell',
								'content'  => 'plus_message_desc_below__compare',
							)
						)
					)
				);

			case 'desc_above':
				return sprintf(
					/* translators: %s: translation added */
					__( 'Upgrade  <a href="%s" target="_blank">Booster </a> to change values above.', 'woocommerce-jetpack' ),
					esc_url(
						wcj_build_commercial_url(
							'compare',
							array(
								'campaign' => 'generic_upsell',
								'content'  => 'plus_message_desc_above__compare',
							)
						)
					)
				);

			case 'desc_no_link':
				return __( 'Upgrade Booster to change value.', 'woocommerce-jetpack' );

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

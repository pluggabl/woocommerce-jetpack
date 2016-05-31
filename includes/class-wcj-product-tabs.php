<?php
/**
 * WooCommerce Jetpack Product Tabs
 *
 * The WooCommerce Jetpack Product Tabs class.
 *
 * @version 2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Tabs' ) ) :

class WCJ_Product_Tabs extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 */
	public function __construct() {

		$this->id         = 'product_tabs';
		$this->short_desc = __( 'Product Tabs', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom product tabs - globally or per product. Customize or completely remove WooCommerce default product tabs.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-custom-product-tabs/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_product_tabs', array( $this, 'customize_product_tabs' ), 98 );
			if ( 'yes' === get_option( 'wcj_custom_product_tabs_local_enabled' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_custom_tabs_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_custom_tabs_meta_box' ), 100, 2 );
			}
		}
	}

	/**
	 * Customize the product tabs.
	 *
	 * @version 2.5.2
	 */
	function customize_product_tabs( $tabs ) {

		// DEFAULT TABS
		// Unset
		if ( get_option( 'wcj_product_info_product_tabs_description_disable' ) === 'yes' )
			unset( $tabs['description'] );
		if ( get_option( 'wcj_product_info_product_tabs_reviews_disable' ) === 'yes' )
			unset( $tabs['reviews'] );
		if ( get_option( 'wcj_product_info_product_tabs_additional_information_disable' ) === 'yes' )
			unset( $tabs['additional_information'] );

		// Priority and Title
		if ( isset( $tabs['description'] ) ) {
			$tabs['description']['priority'] = apply_filters( 'wcj_get_option_filter', 10, get_option( 'wcj_product_info_product_tabs_description_priority' ) );
			if ( get_option( 'wcj_product_info_product_tabs_description_title' ) !== '' )
				$tabs['description']['title'] = get_option( 'wcj_product_info_product_tabs_description_title' );
		}
		if ( isset( $tabs['reviews'] ) ) {
			$tabs['reviews']['priority'] = apply_filters( 'wcj_get_option_filter', 20, get_option( 'wcj_product_info_product_tabs_reviews_priority' ) );
			if ( get_option( 'wcj_product_info_product_tabs_reviews_title' ) !== '' )
				$tabs['reviews']['title'] = get_option( 'wcj_product_info_product_tabs_reviews_title' );
		}
		if ( isset( $tabs['additional_information'] ) ) {
			$tabs['additional_information']['priority'] = apply_filters( 'wcj_get_option_filter', 30, get_option( 'wcj_product_info_product_tabs_additional_information_priority' ) );
			if ( get_option( 'wcj_product_info_product_tabs_additional_information_title' ) !== '' )
				$tabs['additional_information']['title'] = get_option( 'wcj_product_info_product_tabs_additional_information_title' );
		}

		// CUSTOM TABS
		// Add New
		// Add New - Global
		global $product;
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_custom_product_tabs_global_total_number', 1 ) ); $i++ ) {
			$key = 'global_' . $i;
			if ( '' != get_option( 'wcj_custom_product_tabs_title_' . $key, '' ) && '' != get_option( 'wcj_custom_product_tabs_content_' . $key, '' ) ) {

				// Exclude by product id
				$array_to_exclude = get_option( 'wcj_custom_product_tabs_global_hide_in_products_' . $i );
				if ( '' == $array_to_exclude || empty( $array_to_exclude ) ) {
					$list_to_exclude = get_option( 'wcj_custom_product_tabs_title_global_hide_in_product_ids_' . $i );
					if ( '' != $list_to_exclude ) {
						$array_to_exclude = explode( ',', $list_to_exclude );
					}
				}
				if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
					if ( $product && $array_to_exclude && in_array( $product->id, $array_to_exclude ) ) {
						continue;
					}
				}

				// Exclude by product category
				$array_to_exclude = get_option( 'wcj_custom_product_tabs_global_hide_in_cats_' . $i );
				if ( '' == $array_to_exclude || empty( $array_to_exclude ) ) {
					$list_to_exclude = get_option( 'wcj_custom_product_tabs_title_global_hide_in_cats_ids_' . $i );
					if ( '' != $list_to_exclude ) {
						$array_to_exclude = explode( ',', $list_to_exclude );
					}
				}
				if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
					$do_exclude = false;
					$product_categories_objects = get_the_terms( $product->id, 'product_cat' );
					if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
						foreach ( $product_categories_objects as $product_categories_object ) {
							if ( $product && $array_to_exclude && in_array( $product_categories_object->term_id, $array_to_exclude ) ) {
								$do_exclude = true;
								break;
							}
						}
					}
					if ( $do_exclude ) {
						continue;
					}
				}

				// Exclude by product tag
				$array_to_exclude = get_option( 'wcj_custom_product_tabs_global_hide_in_tags_' . $i );
				if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
					$do_exclude = false;
					$product_tags_objects = get_the_terms( $product->id, 'product_tag' );
					if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
						foreach ( $product_tags_objects as $product_tags_object ) {
							if ( $product && $array_to_exclude && in_array( $product_tags_object->term_id, $array_to_exclude ) ) {
								$do_exclude = true;
								break;
							}
						}
					}
					if ( $do_exclude ) {
						continue;
					}
				}

				// Include by product id
				$array_to_include = get_option( 'wcj_custom_product_tabs_global_show_in_products_' . $i );
				if ( '' == $array_to_include || empty( $array_to_include ) ) {
					$list_to_include = get_option( 'wcj_custom_product_tabs_title_global_show_in_product_ids_' . $i );
					if ( '' != $list_to_include ) {
						$array_to_include = explode( ',', $list_to_include );
					}
				}
				if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
					// If NOT in array then hide this tab for this product
					if ( $product && $array_to_include && ! in_array( $product->id, $array_to_include ) ) {
						continue;
					}
				}

				// Include by product category
				$array_to_include = get_option( 'wcj_custom_product_tabs_global_show_in_cats_' . $i );
				if ( '' == $array_to_include || empty( $array_to_include ) ) {
					$list_to_include = get_option( 'wcj_custom_product_tabs_title_global_show_in_cats_ids_' . $i );
					if ( '' != $list_to_include ) {
						$array_to_include = explode( ',', $list_to_include );
					}
				}
				if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
					$do_include = false;
					$product_categories_objects = get_the_terms( $product->id, 'product_cat' );
					if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
						foreach ( $product_categories_objects as $product_categories_object ) {
							if ( $product && $array_to_include && in_array( $product_categories_object->term_id, $array_to_include ) ) {
								$do_include = true;
								break;
							}
						}
					}
					if ( ! $do_include ) {
						continue;
					}
				}

				// Include by product tag
				$array_to_include = get_option( 'wcj_custom_product_tabs_global_show_in_tags_' . $i );
				if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
					$do_include = false;
					$product_tags_objects = get_the_terms( $product->id, 'product_tag' );
					if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
						foreach ( $product_tags_objects as $product_tags_object ) {
							if ( $product && $array_to_include && in_array( $product_tags_object->term_id, $array_to_include ) ) {
								$do_include = true;
								break;
							}
						}
					}
					if ( ! $do_include ) {
						continue;
					}
				}

				// Adding the tab
				$tabs[ $key ] = array(
					'title'    => do_shortcode( get_option( 'wcj_custom_product_tabs_title_' . $key ) ),
					'priority' => get_option( 'wcj_custom_product_tabs_priority_' . $key, 40 ),
					'callback' => array( $this, 'create_new_custom_product_tab_global' ),
				);
			}
		}
		// Add New - Local
		$current_post_id = get_the_ID();
		$option_name = 'wcj_custom_product_tabs_local_total_number';
		if ( ! ( $total_custom_tabs = get_post_meta( $current_post_id, '_' . $option_name, true ) ) )
			$total_custom_tabs = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
		if ( 'yes' !== get_option( 'wcj_custom_product_tabs_local_enabled' ) )
			$total_custom_tabs = 0;

		for ( $i = 1; $i <= $total_custom_tabs; $i++ ) {
			if ( $this->is_local_tab_visible( $i, $current_post_id ) ) {
				$key = 'local_' . $i;

				$tab_priority = get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_priority_' . $key, true );
				if ( ! $tab_priority ) {
					$tab_priority = (50 + $i - 1);
				}

				if ( '' != get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_title_' . $key, true ) && '' != get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_content_' . $key, true ) ) {
					$tabs[ $key ] = array(
						'title'    => do_shortcode( get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_title_' . $key, true ) ),
						'priority' => $tab_priority,
						'callback' => array( $this, 'create_new_custom_product_tab_local' ),
					);
				}
			}
		}

		return $tabs;
	}

	/**
	 * create_new_custom_product_tab_local.
	 */
	function create_new_custom_product_tab_local( $key, $tab ) {
		$current_post_id = get_the_ID();
		echo apply_filters( 'the_content', get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_content_' . $key, true ) );
	}

	/**
	 * create_new_custom_product_tab_global.
	 */
	function create_new_custom_product_tab_global( $key, $tab ) {
		echo apply_filters( 'the_content', get_option( 'wcj_custom_product_tabs_content_' . $key ) );
	}

	/**
	 * save_custom_tabs_meta_box.
	 *
	 * @version 2.4.7
	 */
	public function save_custom_tabs_meta_box( $post_id, $post ) {

		// Check that we are saving with custom tab metabox displayed.
		if ( ! isset( $_POST['woojetpack_custom_tabs_save_post'] ) )
			return;

		// Save: title, priority, content
		$option_names = array(
			'wcj_custom_product_tabs_title_local_',
			'wcj_custom_product_tabs_priority_local_',
			'wcj_custom_product_tabs_content_local_',
		);
		$default_total_custom_tabs = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
		$total_custom_tabs_before_saving = get_post_meta( $post_id, '_' . 'wcj_custom_product_tabs_local_total_number', true );
		$total_custom_tabs_before_saving = ( '' != $total_custom_tabs_before_saving ) ? $total_custom_tabs_before_saving : $default_total_custom_tabs;
		for ( $i = 1; $i <= $total_custom_tabs_before_saving; $i++ ) {
			if ( $this->is_local_tab_visible( $i, $post_id ) ) {
				foreach ( $option_names as $option_name ) {
					update_post_meta( $post_id, '_' . $option_name . $i, $_POST[ $option_name . $i ] );
				}
			}
		}

		// Save: total custom tabs number
		$option_name = 'wcj_custom_product_tabs_local_total_number';
		$total_custom_tabs = isset( $_POST[ $option_name ] ) ? $_POST[ $option_name ] : $default_total_custom_tabs;
		update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
	}

	/**
	 * add_custom_tabs_meta_box.
	 *
	 * @version 2.4.7
	 */
	public function add_custom_tabs_meta_box() {
		add_meta_box(
			'wc-jetpack-custom-tabs',
			__( 'Booster: Custom Tabs', 'woocommerce-jetpack' ),
			array( $this, 'create_custom_tabs_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * is_local_tab_visible.
	 *
	 * @version 2.4.7
	 * @since   2.4.7
	 */
	function is_local_tab_visible( $custom_tab_index, $product_id ) {

		// Exclude by product id
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_local_hide_in_products_' . $custom_tab_index );
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			if ( $array_to_exclude && in_array( $product_id, $array_to_exclude ) ) {
				return false;
			}
		}

		// Exclude by product category
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_local_hide_in_cats_' . $custom_tab_index );
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			$product_categories_objects = get_the_terms( $product_id, 'product_cat' );
			if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
				foreach ( $product_categories_objects as $product_categories_object ) {
					if ( $array_to_exclude && in_array( $product_categories_object->term_id, $array_to_exclude ) ) {
						return false;
					}
				}
			}
		}

		// Exclude by product tag
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_local_hide_in_tags_' . $custom_tab_index );
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			$product_tags_objects = get_the_terms( $product_id, 'product_tag' );
			if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
				foreach ( $product_tags_objects as $product_tags_object ) {
					if ( $array_to_exclude && in_array( $product_tags_object->term_id, $array_to_exclude ) ) {
						return false;
					}
				}
			}
		}

		// Include by product id
		$array_to_include = get_option( 'wcj_custom_product_tabs_local_show_in_products_' . $custom_tab_index );
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			// If NOT in array then hide this tab for this product
			if ( $array_to_include && ! in_array( $product_id, $array_to_include ) ) {
				return false;
			}
		}

		// Include by product category
		$array_to_include = get_option( 'wcj_custom_product_tabs_local_show_in_cats_' . $custom_tab_index );
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			$do_include = false;
			$product_categories_objects = get_the_terms( $product_id, 'product_cat' );
			if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
				foreach ( $product_categories_objects as $product_categories_object ) {
					if ( $array_to_include && in_array( $product_categories_object->term_id, $array_to_include ) ) {
						$do_include = true;
						break;
					}
				}
			}
			if ( ! $do_include ) {
				return false;
			}
		}

		// Include by product tag
		$array_to_include = get_option( 'wcj_custom_product_tabs_local_show_in_tags_' . $custom_tab_index );
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			$do_include = false;
			$product_tags_objects = get_the_terms( $product_id, 'product_tag' );
			if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
				foreach ( $product_tags_objects as $product_tags_object ) {
					if ( $array_to_include && in_array( $product_tags_object->term_id, $array_to_include ) ) {
						$do_include = true;
						break;
					}
				}
			}
			if ( ! $do_include ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * create_custom_tabs_meta_box.
	 *
	 * @version 2.5.0
	 */
	public function create_custom_tabs_meta_box() {

		$current_post_id = get_the_ID();
		$option_name = 'wcj_custom_product_tabs_local_total_number';
		if ( ! ( $total_custom_tabs = get_post_meta( $current_post_id, '_' . $option_name, true ) ) )
			$total_custom_tabs = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
		$html = '';

		$is_disabled = apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly_string' );
		$is_disabled_message = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' );

		$html .= '<table>';
		$html .= '<tr>';
		$html .= '<th>';
		$html .= __( 'Total number of custom tabs', 'woocommerce-jetpack' );
		$html .= '</th>';
		$html .= '<td>';
		$html .= '<input type="number" min="0" id="' . $option_name . '" name="' . $option_name . '" value="' . $total_custom_tabs . '" ' . $is_disabled . '>';
		$html .= '</td>';
		$html .= '<td>';
		$html .= __( 'Click "Update" product after you change this number.', 'woocommerce-jetpack' ) . '<br>' . $is_disabled_message;
		$html .= '</td>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		echo $html;

		$options = array(
			array(
				'id'    => 'wcj_custom_product_tabs_title_local_',
				'title' => __( 'Title', 'woocommerce-jetpack' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'wcj_custom_product_tabs_priority_local_',
				'title' => __( 'Order', 'woocommerce-jetpack' ),
				'type'  => 'number',
			),
			array(
				'id'    => 'wcj_custom_product_tabs_content_local_',
				'title' => __( 'Content', 'woocommerce-jetpack' ),
				'type'  => 'textarea',
			),
		);
		$enable_wp_editor = get_option( 'wcj_custom_product_tabs_local_wp_editor_enabled', 'yes' );
		$enable_wp_editor = ( 'yes' === $enable_wp_editor ) ? true : false;
		for ( $i = 1; $i <= $total_custom_tabs; $i++ ) {
			$is_local_tab_visible = $this->is_local_tab_visible( $i, $current_post_id );
			$readonly = ( $is_local_tab_visible ) ? '' : ' readonly'; // not really used
			$disabled = ( $is_local_tab_visible ) ? '' : ' - ' . __( 'Disabled', 'woocommerce-jetpack' );
			$data = array();
			$html = '<hr>';
			$html .= '<h4>' . __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i . $disabled . '</h4>';
			if ( $enable_wp_editor ) {
				$the_field_wp_editor = array();
			}
			if ( $is_local_tab_visible ) {
				foreach ( $options as $option ) {
					$option_id = $option['id'] . $i;
					if ( ! ( $option_value = get_post_meta( $current_post_id, '_' . $option_id, true ) ) ) {
						$option_value = get_option( $option['id'] . 'default_' . $i, '' );
						if ( '' === $option_value && 'wcj_custom_product_tabs_priority_local_' === $option['id'] ) {
							$option_value = 50;
						}
					}
					switch ( $option['type'] ) {
						case 'number':
							$the_field = '<input style="width:25%;min-width:100px;" type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '"' . $readonly . '>';
							break;
						case 'text':
							$the_field = '<input style="width:50%;min-width:150px;" type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '"' . $readonly . '>';
							break;
						case 'textarea':
							if ( $enable_wp_editor ) {
								$the_field = '';
								$the_field_wp_editor = array( $option_id => $option_value );
							} else {
								$the_field = '<textarea style="width:100%;height:300px;" id="' . $option_id . '" name="' . $option_id . '"' . $readonly . '>' . $option_value . '</textarea>';
							}
							break;
					}
					if ( '' != $the_field ) {
						$data[] = array( $option['title'], $the_field );
					}
				}
				$html .= wcj_get_table_html( $data, array( 'table_class' => 'widefat', 'table_style' => 'margin-bottom:20px;', 'table_heading_type' => 'vertical', 'columns_styles' => array( 'width:10%;', ) ) );
			}
			echo $html;
			if ( $enable_wp_editor && ! empty( $the_field_wp_editor ) ) {
				wp_editor( current( $the_field_wp_editor ), key( $the_field_wp_editor ) );
			}
		}
		$html = '<input type="hidden" name="woojetpack_custom_tabs_save_post" value="woojetpack_custom_tabs_save_post">';
		echo $html;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 */
	function get_settings() {

		$settings = array(
			// Global Custom Tabs
			array(
				'title'     => __( 'Custom Product Tabs Options', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'This section lets you add custom single product tabs.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_options',
			),
			array(
				'title'     => __( 'Custom Product Tabs Number', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_global_total_number',
				'default'   => 1,
				'type'      => 'number',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array(
						'step' => '1',
						'min'  => '0',
					)
				),
			),
		);

		$product_tags_options = array();
		$product_tags = get_terms( 'product_tag', 'orderby=name&hide_empty=0' );
		if ( ! empty( $product_tags ) && ! is_wp_error( $product_tags ) ){
			foreach ( $product_tags as $product_tag ) {
				$product_tags_options[ $product_tag->term_id ] = $product_tag->name;
			}
		}

		$product_cats_options = array();
		$product_cats = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ){
			foreach ( $product_cats as $product_cat ) {
				$product_cats_options[ $product_cat->term_id ] = $product_cat->name;
			}
		}

		$products_options = apply_filters( 'wcj_get_products_filter', array() );

		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_custom_product_tabs_global_total_number', 1 ) ); $i++ ) {
			$settings = array_merge( $settings,
				array(
					array(
						'title'     => __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i,
						'desc'      => __( 'Title', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_title_global_' . $i,
						'default'   => '',
						'type'      => 'text',
						'css'       => 'width:30%;min-width:300px;',
					),
					array(
						'title'     => '',
						'desc'      => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_priority_global_' . $i,
						'default'   => (40 + $i - 1),
						'type'      => 'number',
					),
					array(
						'title'     => '',
						'desc'      => __( 'Content', 'woocommerce-jetpack' ),
						'desc_tip'  => __( 'You can use shortcodes here...', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_content_global_' . $i,
						'default'   => '',
						'type'      => 'textarea',
						'css'       => 'width:50%;min-width:300px;height:200px;',
					),
					array(
						'title'     => '',
						'desc'      => __( 'PRODUCTS to HIDE this tab', 'woocommerce-jetpack' ),
						'desc_tip'  => __( 'To hide this tab from some products, enter products here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_global_hide_in_products_' . $i,
						'default'   => '',
						'class'     => 'chosen_select',
						'type'      => 'multiselect',
						'options'   => $products_options,
					),
					array(
						'title'     => '',
						'desc'      => __( 'PRODUCTS to SHOW this tab', 'woocommerce-jetpack' ),
						'desc_tip'  => __( 'To show this tab only for some products, enter products here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_global_show_in_products_' . $i,
						'default'   => '',
						'class'     => 'chosen_select',
						'type'      => 'multiselect',
						'options'   => $products_options,
					),
					array(
						'title'     => '',
						'desc'      => __( 'CATEGORIES to HIDE this tab', 'woocommerce-jetpack' ),
						'desc_tip'  => __( 'To hide this tab from some categories, enter categories here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_global_hide_in_cats_' . $i,
						'default'   => '',
						'class'     => 'chosen_select',
						'type'      => 'multiselect',
						'options'   => $product_cats_options,
					),
					array(
						'title'     => '',
						'desc'      => __( 'CATEGORIES to SHOW this tab', 'woocommerce-jetpack' ),
						'desc_tip'  => __( 'To show this tab only for some categories, enter categories here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_global_show_in_cats_' . $i,
						'default'   => '',
						'class'     => 'chosen_select',
						'type'      => 'multiselect',
						'options'   => $product_cats_options,
					),
					array(
						'title'     => '',
						'desc'      => __( 'TAGS to HIDE this tab', 'woocommerce-jetpack' ),
						'desc_tip'  => __( 'To hide this tab from some tags, enter tags here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_global_hide_in_tags_' . $i,
						'default'   => '',
						'class'     => 'chosen_select',
						'type'      => 'multiselect',
						'options'   => $product_tags_options,
					),
					array(
						'title'     => '',
						'desc'      => __( 'TAGS to SHOW this tab', 'woocommerce-jetpack' ),
						'desc_tip'  => __( 'To show this tab only for some tags, enter tags here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_global_show_in_tags_' . $i,
						'default'   => '',
						'class'     => 'chosen_select',
						'type'      => 'multiselect',
						'options'   => $product_tags_options,
					),
					array(
						'title'     => '',
						'desc'      => __( 'Comma separated PRODUCT IDs to HIDE this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Depreciated', 'woocommerce-jetpack' ) . '!</em></strong>',
						'desc_tip'  => __( 'To hide this tab from some products, enter product IDs here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_title_global_hide_in_product_ids_' . $i,
						'default'   => '',
						'type'      => 'text',
						'css'       => 'width:30%;min-width:300px;',
					),
					array(
						'title'     => '',
						'desc'      => __( 'Comma separated PRODUCT IDs to SHOW this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Depreciated', 'woocommerce-jetpack' ) . '!</em></strong>',
						'desc_tip'  => __( 'To show this tab only for some products, enter product IDs here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_title_global_show_in_product_ids_' . $i,
						'default'   => '',
						'type'      => 'text',
						'css'       => 'width:30%;min-width:300px;',
					),
					array(
						'title'     => '',
						'desc'      => __( 'Comma separated CATEGORY IDs to HIDE this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Depreciated', 'woocommerce-jetpack' ) . '!</em></strong>',
						'desc_tip'  => __( 'To hide this tab from some categories, enter category IDs here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_title_global_hide_in_cats_ids_' . $i,
						'default'   => '',
						'type'      => 'text',
						'css'       => 'width:30%;min-width:300px;',
					),
					array(
						'title'     => '',
						'desc'      => __( 'Comma separated CATEGORY IDs to SHOW this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Depreciated', 'woocommerce-jetpack' ) . '!</em></strong>',
						'desc_tip'  => __( 'To show this tab only for some categories, enter category IDs here.', 'woocommerce-jetpack' ),
						'id'        => 'wcj_custom_product_tabs_title_global_show_in_cats_ids_' . $i,
						'default'   => '',
						'type'      => 'text',
						'css'       => 'width:30%;min-width:300px;',
					),
				)
			);
		}

		$settings = array_merge( $settings, array(
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_custom_product_tabs_options',
			),
			// Local Custom Tabs
			array(
				'title'     => __( 'Custom Product Tabs - Per Product', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'This section lets you set defaults for per product custom tabs.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_options_local',
			),
			array(
				'title'     => __( 'Enable Per Product Custom Product Tabs', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'This will add meta boxes to each product\'s edit page.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_local_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Use Visual Editor', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_local_wp_editor_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Default Per Product Custom Product Tabs Number', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_local_total_number_default',
				'default'   => 1,
				'type'      => 'number',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array(
						'step' => '1',
						'min'  => '0',
					)
				),
			),
		) );

		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) ); $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'     => __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i,
					'desc'      => __( 'Default Title', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_title_local_default_' . $i,
					'default'   => '',
					'type'      => 'text',
					'css'       => 'width:30%;min-width:300px;',
				),
				array(
					'title'     => '',
					'desc'      => __( 'Default Priority (i.e. Order)', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_priority_local_default_' . $i,
					'default'   => 50,
					'type'      => 'number',
				),
				array(
					'title'     => '',
					'desc'      => __( 'Default Content', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'You can use shortcodes here...', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_content_local_default_' . $i,
					'default'   => '',
					'type'      => 'textarea',
					'css'       => 'width:50%;min-width:300px;height:200px;',
				),
				array(
					'title'     => '',
					'desc'      => __( 'PRODUCTS to HIDE this tab', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'To hide this tab from some products, enter products here.', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_local_hide_in_products_' . $i,
					'default'   => '',
					'class'     => 'chosen_select',
					'type'      => 'multiselect',
					'options'   => $products_options,
				),
				array(
					'title'     => '',
					'desc'      => __( 'PRODUCTS to SHOW this tab', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'To show this tab only for some products, enter products here.', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_local_show_in_products_' . $i,
					'default'   => '',
					'class'     => 'chosen_select',
					'type'      => 'multiselect',
					'options'   => $products_options,
				),
				array(
					'title'     => '',
					'desc'      => __( 'CATEGORIES to HIDE this tab', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'To hide this tab from some categories, enter categories here.', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_local_hide_in_cats_' . $i,
					'default'   => '',
					'class'     => 'chosen_select',
					'type'      => 'multiselect',
					'options'   => $product_cats_options,
				),
				array(
					'title'     => '',
					'desc'      => __( 'CATEGORIES to SHOW this tab', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'To show this tab only for some categories, enter categories here.', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_local_show_in_cats_' . $i,
					'default'   => '',
					'class'     => 'chosen_select',
					'type'      => 'multiselect',
					'options'   => $product_cats_options,
				),
				array(
					'title'     => '',
					'desc'      => __( 'TAGS to HIDE this tab', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'To hide this tab from some tags, enter tags here.', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_local_hide_in_tags_' . $i,
					'default'   => '',
					'class'     => 'chosen_select',
					'type'      => 'multiselect',
					'options'   => $product_tags_options,
				),
				array(
					'title'     => '',
					'desc'      => __( 'TAGS to SHOW this tab', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'To show this tab only for some tags, enter tags here.', 'woocommerce-jetpack' ),
					'id'        => 'wcj_custom_product_tabs_local_show_in_tags_' . $i,
					'default'   => '',
					'class'     => 'chosen_select',
					'type'      => 'multiselect',
					'options'   => $product_tags_options,
				),
			) );
		}

		$settings = array_merge( $settings, array(
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_custom_product_tabs_options_local',
			),
			// Standard WooCommerce Tabs
			array(
				'title'     => __( 'WooCommerce Standard Product Tabs Options', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'This section lets you customize single product tabs.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_product_info_product_tabs_options',
			),
			array(
				'title'     => __( 'Description Tab', 'woocommerce-jetpack' ),
				'desc'      => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
				'id'        => 'wcj_product_info_product_tabs_description_disable',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Title.', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
				'id'        => 'wcj_product_info_product_tabs_description_title',
				'default'   => '',
				'type'      => 'text',
			),
			array(
				'title'     => '',
				'id'        => 'wcj_product_info_product_tabs_description_priority',
				'default'   => 10,
				'type'      => 'number',
				'desc'      => __( 'Priority (i.e. Order).', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'title'     => __( 'Additional Information Tab', 'woocommerce-jetpack' ),
				'desc'      => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
				'id'        => 'wcj_product_info_product_tabs_additional_information_disable',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Title.', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
				'id'        => 'wcj_product_info_product_tabs_additional_information_title',
				'default'   => '',
				'type'      => 'text',
			),
			array(
				'title'     => '',
				'id'        => 'wcj_product_info_product_tabs_additional_information_priority',
				'default'   => 20,
				'type'      => 'number',
				'desc'      => __( 'Priority (i.e. Order).', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'title'     => __( 'Reviews Tab', 'woocommerce-jetpack' ),
				'desc'      => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
				'id'        => 'wcj_product_info_product_tabs_reviews_disable',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Title.', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
				'id'        => 'wcj_product_info_product_tabs_reviews_title',
				'default'   => '',
				'type'      => 'text',
			),
			array(
				'title'     => '',
				'id'        => 'wcj_product_info_product_tabs_reviews_priority',
				'default'   => 30,
				'type'      => 'number',
				'desc'      => __( 'Priority (i.e. Order).', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_product_info_product_tabs_options',
			),
		) );

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Tabs();

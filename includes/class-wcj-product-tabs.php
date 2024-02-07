<?php
/**
 * Booster for WooCommerce - Module - Product Tabs
 *
 * @version 7.1.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Product_Tabs' ) ) :
	/**
	 * WCJ_Product_Tabs.
	 */
	class WCJ_Product_Tabs extends WCJ_Module {

		/**
		 * The module tab_option_keys
		 *
		 * @var array
		 */
		public $tab_option_keys = array();

		/**
		 * Constructor.
		 *
		 * @version 5.6.2
		 * @todo    code refactoring and clean-up
		 */
		public function __construct() {

			$this->id         = 'product_tabs';
			$this->short_desc = __( 'Product Tabs', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add custom product tabs - globally or per product. Customize or completely remove WooCommerce default product tabs (1 custom tab allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add custom product tabs - globally or per product. Customize or completely remove WooCommerce default product tabs.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-custom-product-tabs';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_action( 'wp_head', array( $this, 'maybe_add_js_links' ) );
				add_filter( 'woocommerce_product_tabs', array( $this, 'customize_product_tabs' ), 98 );
				if ( 'yes' === wcj_get_option( 'wcj_custom_product_tabs_local_enabled', 'yes' ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_custom_tabs_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_custom_tabs_meta_box' ), 100, 2 );
					if ( 'yes' === wcj_get_option( 'wcj_custom_product_tabs_yoast_seo_enabled', 'no' ) ) {
						add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
					}
				}
			}
		}

		/**
		 * Enqueue_admin_scripts.
		 *
		 * @version 5.6.2
		 * @since   3.2.4
		 */
		public function enqueue_admin_scripts() {
			if ( wcj_is_admin_product_edit_page() ) {
				wp_enqueue_script(
					'wcj-custom-tabs-yoast-seo',
					wcj_plugin_url() . '/includes/js/wcj-custom-tabs-yoast-seo.js',
					array( 'jquery', 'yoast-seo-admin-script' ),
					w_c_j()->version,
					false
				);
			}
		}

		/**
		 * Get_tab_key.
		 *
		 * @version 5.6.2
		 * @since   3.1.0
		 * @param string $tab_option_key defines the tab_option_key.
		 * @param string $scope defines the scope.
		 * @param int    $product_id defines the product_id.
		 */
		public function get_tab_key( $tab_option_key, $scope, $product_id = 0 ) {
			$tab_key = ( 'global' === $scope ?
				get_option( 'wcj_custom_product_tabs_key_' . $tab_option_key, $tab_option_key ) :
				get_post_meta( $product_id, '_wcj_custom_product_tabs_key_' . $tab_option_key, true )
			);
			return ( '' === $tab_key || null === $tab_key ? $tab_option_key : $tab_key );
		}

		/**
		 * Customize_default_tabs.
		 *
		 * @version 5.6.2
		 * @since   3.1.0
		 * @param array $tabs defines the tabs.
		 */
		public function customize_default_tabs( $tabs ) {

			// Unset.
			if ( 'yes' === wcj_get_option( 'wcj_product_info_product_tabs_description_disable', 'no' ) ) {
				unset( $tabs['description'] );
			}
			if ( 'yes' === wcj_get_option( 'wcj_product_info_product_tabs_reviews_disable', 'no' ) ) {
				unset( $tabs['reviews'] );
			}
			if ( 'yes' === wcj_get_option( 'wcj_product_info_product_tabs_additional_information_disable', 'no' ) ) {
				unset( $tabs['additional_information'] );
			}

			// Priority and Title.
			if ( isset( $tabs['description'] ) ) {
				$title                           = wcj_get_option( 'wcj_product_info_product_tabs_description_title', '' );
				$tabs['description']['priority'] = apply_filters( 'booster_option', 10, wcj_get_option( 'wcj_product_info_product_tabs_description_priority', 10 ) );
				if ( '' !== $title ) {
					$tabs['description']['title'] = $title;
				}
			}
			if ( isset( $tabs['additional_information'] ) ) {
				$title                                      = wcj_get_option( 'wcj_product_info_product_tabs_additional_information_title', '' );
				$tabs['additional_information']['priority'] = apply_filters( 'booster_option', 20, wcj_get_option( 'wcj_product_info_product_tabs_additional_information_priority', 20 ) );
				if ( '' !== $title ) {
					$tabs['additional_information']['title'] = $title;
				}
			}
			if ( isset( $tabs['reviews'] ) ) {
				$title                       = wcj_get_option( 'wcj_product_info_product_tabs_reviews_title', '' );
				$tabs['reviews']['priority'] = apply_filters( 'booster_option', 30, wcj_get_option( 'wcj_product_info_product_tabs_reviews_priority', 30 ) );
				if ( '' !== $title ) {
					$tabs['reviews']['title'] = $title;
				}
			}

			// The end.
			return $tabs;
		}

		/**
		 * Gets local title, including from other possible languages when using WPML.
		 *
		 * @version 5.6.2
		 * @since   4.2.0
		 *
		 * @param int    $product_id defines the product_id.
		 * @param string $option_key defines the option_key.
		 * @param string $i defines the i.
		 *
		 * @return mixed
		 */
		public function get_local_title( $product_id, $option_key, $i ) {
			$title = get_post_meta( $product_id, '_wcj_custom_product_tabs_title_' . $option_key, true );
			if ( function_exists( 'icl_object_id' ) ) {
				if ( ! empty( icl_object_id( $product_id, 'product', true ) ) ) {
					$curr_language_product_id = ! empty( icl_object_id( $product_id, 'product', true ) );
					// Try to get title from wp_options first, like 'wcj_custom_product_tabs_title_local_default_1'.
					$title_option = wcj_get_option( 'wcj_custom_product_tabs_title_local_default_' . $i, true );
					$title        = ! empty( $title_option ) ? $title_option : $title;

					// Overwrite by post meta if there is any.
					$translated_title = get_post_meta( $curr_language_product_id, '_wcj_custom_product_tabs_title_' . $option_key, true );
					$title            = ! empty( $translated_title ) ? $translated_title : $title;
				}
			}
			return $title;
		}

		/**
		 * Add_custom_product_tabs.
		 *
		 * @version 5.6.2
		 * @since   3.1.0
		 * @todo    add visibility by user roles
		 * @param string $scope defines the scope.
		 * @param array  $tabs defines the tabs.
		 * @param int    $product_id defines the product_id.
		 */
		public function add_custom_product_tabs( $scope, $tabs, $product_id ) {
			switch ( $scope ) {
				case 'global':
					$total_custom_tabs = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_product_tabs_global_total_number', 1 ) );
					break;
				default: // 'local'.
					$total_custom_tabs = get_post_meta( $product_id, '_wcj_custom_product_tabs_local_total_number', true );
					if ( ! $total_custom_tabs ) {
						$total_custom_tabs = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
					}
					break;
			}
			for ( $i = 1; $i <= $total_custom_tabs; $i++ ) {
				$visibility_check_function = 'is_' . $scope . '_tab_visible';
				if ( $this->$visibility_check_function( $i, $product_id ) ) {
					$option_key = $scope . '_' . $i;
					switch ( $scope ) {
						case 'global':
							$title    = wcj_get_option( 'wcj_custom_product_tabs_title_' . $option_key, '' );
							$content  = wcj_get_option( 'wcj_custom_product_tabs_content_' . $option_key, '' );
							$priority = wcj_get_option( 'wcj_custom_product_tabs_priority_' . $option_key, 40 );
							$link     = wcj_get_option( 'wcj_custom_product_tabs_link_' . $option_key, '' );
							break;
						default: // 'local'.
							$title    = $this->get_local_title( $product_id, $option_key, $i );
							$content  = get_post_meta( $product_id, '_wcj_custom_product_tabs_content_' . $option_key, true );
							$priority = get_post_meta( $product_id, '_wcj_custom_product_tabs_priority_' . $option_key, true );
							$link     = get_post_meta( $product_id, '_wcj_custom_product_tabs_link_' . $option_key, true );
							if ( ! $priority ) {
								$priority = ( 50 + $i - 1 );
							}
							break;
					}
					if ( '' !== $title && ( '' !== do_shortcode( $content ) || '' !== $link ) ) {
						// Adding the tab.
						$tab_key                                     = $this->get_tab_key( $option_key, $scope, $product_id );
						$this->tab_option_keys[ $scope ][ $tab_key ] = $option_key;
						$tabs[ $tab_key ]                            = array(
							'title'    => do_shortcode( $title ),
							'priority' => $priority,
							'callback' => array( $this, 'create_new_custom_product_tab_' . $scope ),
						);
					}
				}
			}
			return $tabs;
		}

		/**
		 * Customize the product tabs.
		 *
		 * @version 5.6.2
		 * @param string $tabs defines the tabs.
		 */
		public function customize_product_tabs( $tabs ) {

			$product_id = wcj_maybe_get_product_id_wpml( get_the_ID() );

			// Default Tabs.
			$tabs = $this->customize_default_tabs( $tabs );

			// Custom Tabs - Global.
			if ( 'yes' === wcj_get_option( 'wcj_custom_product_tabs_global_enabled', 'yes' ) ) {
				$tabs = $this->add_custom_product_tabs( 'global', $tabs, $product_id );
			}

			// Custom Tabs - Local.
			if ( 'yes' === wcj_get_option( 'wcj_custom_product_tabs_local_enabled', 'yes' ) ) {
				$tabs = $this->add_custom_product_tabs( 'local', $tabs, $product_id );
			}

			return $tabs;
		}

		/**
		 * Is_global_tab_visible.
		 *
		 * @version 5.6.2
		 * @since   2.8.0
		 * @param int $i defines the i.
		 * @param int $_product_id defines the _product_id.
		 */
		public function is_global_tab_visible( $i, $_product_id ) {

			// Exclude by product id.
			$array_to_exclude = wcj_get_option( 'wcj_custom_product_tabs_global_hide_in_products_' . $i );
			if ( '' === $array_to_exclude || empty( $array_to_exclude ) ) {
				$list_to_exclude = wcj_get_option( 'wcj_custom_product_tabs_title_global_hide_in_product_ids_' . $i );
				if ( '' !== $list_to_exclude && null !== $list_to_exclude ) {
					$array_to_exclude = explode( ',', $list_to_exclude );
				}
			}
			if ( '' !== $array_to_exclude && ! empty( $array_to_exclude ) ) {
				if ( $array_to_exclude && in_array( (string) $_product_id, $array_to_exclude, true ) ) {
					return false;
				}
			}

			// Exclude by product category.
			$array_to_exclude = wcj_get_option( 'wcj_custom_product_tabs_global_hide_in_cats_' . $i );
			if ( '' === $array_to_exclude || empty( $array_to_exclude ) ) {
				$list_to_exclude = wcj_get_option( 'wcj_custom_product_tabs_title_global_hide_in_cats_ids_' . $i );
				if ( '' !== $list_to_exclude && null !== $list_to_exclude ) {
					$array_to_exclude = explode( ',', $list_to_exclude );
				}
			}
			if ( '' !== $array_to_exclude && ! empty( $array_to_exclude ) ) {
				$do_exclude                 = false;
				$product_categories_objects = get_the_terms( $_product_id, 'product_cat' );
				if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
					foreach ( $product_categories_objects as $product_categories_object ) {
						if ( $array_to_exclude && in_array( (string) $product_categories_object->term_id, $array_to_exclude, true ) ) {
							$do_exclude = true;
							break;
						}
					}
				}
				if ( $do_exclude ) {
					return false;
				}
			}

			// Exclude by product tag.
			$array_to_exclude = wcj_get_option( 'wcj_custom_product_tabs_global_hide_in_tags_' . $i );
			if ( '' !== $array_to_exclude && ! empty( $array_to_exclude ) ) {
				$do_exclude           = false;
				$product_tags_objects = get_the_terms( $_product_id, 'product_tag' );
				if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
					foreach ( $product_tags_objects as $product_tags_object ) {
						if ( $array_to_exclude && in_array( (string) $product_tags_object->term_id, $array_to_exclude, true ) ) {
							$do_exclude = true;
							break;
						}
					}
				}
				if ( $do_exclude ) {
					return false;
				}
			}

			// Include by product id.
			$array_to_include = wcj_get_option( 'wcj_custom_product_tabs_global_show_in_products_' . $i );
			if ( '' === $array_to_include || empty( $array_to_include ) ) {
				$list_to_include = wcj_get_option( 'wcj_custom_product_tabs_title_global_show_in_product_ids_' . $i );
				if ( '' !== $list_to_include && null !== $list_to_include ) {
					$array_to_include = explode( ',', $list_to_include );
				}
			}
			if ( '' !== $array_to_include && ! empty( $array_to_include ) ) {
				// If NOT in array then hide this tab for this product.
				if ( $array_to_include && ! in_array( (string) $_product_id, $array_to_include, true ) ) {
					return false;
				}
			}

			// Include by product category.
			$array_to_include = wcj_get_option( 'wcj_custom_product_tabs_global_show_in_cats_' . $i );
			if ( '' === $array_to_include || empty( $array_to_include ) ) {
				$list_to_include = wcj_get_option( 'wcj_custom_product_tabs_title_global_show_in_cats_ids_' . $i );
				if ( '' !== $list_to_include && null !== $list_to_include ) {
					$array_to_include = explode( ',', $list_to_include );
				}
			}
			if ( '' !== $array_to_include && ! empty( $array_to_include ) ) {
				$do_include                 = false;
				$product_categories_objects = get_the_terms( $_product_id, 'product_cat' );
				if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
					foreach ( $product_categories_objects as $product_categories_object ) {
						if ( $array_to_include && in_array( (string) $product_categories_object->term_id, $array_to_include, true ) ) {
							$do_include = true;
							break;
						}
					}
				}
				if ( ! $do_include ) {
					return false;
				}
			}

			// Include by product tag.
			$array_to_include = wcj_get_option( 'wcj_custom_product_tabs_global_show_in_tags_' . $i );
			if ( '' !== $array_to_include && ! empty( $array_to_include ) ) {
				$do_include           = false;
				$product_tags_objects = get_the_terms( $_product_id, 'product_tag' );
				if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
					foreach ( $product_tags_objects as $product_tags_object ) {
						if ( $array_to_include && in_array( (string) $product_tags_object->term_id, $array_to_include, true ) ) {
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
		 * Is_local_tab_visible.
		 *
		 * @version 5.6.2
		 * @since   2.4.7
		 * @param string $custom_tab_index defines the custom_tab_index.
		 * @param int    $product_id defines the product_id.
		 */
		public function is_local_tab_visible( $custom_tab_index, $product_id ) {

			// Exclude by product id.
			$array_to_exclude = wcj_get_option( 'wcj_custom_product_tabs_local_hide_in_products_' . $custom_tab_index );
			if ( '' !== $array_to_exclude && ! empty( $array_to_exclude ) ) {
				if ( $array_to_exclude && in_array( (string) $product_id, $array_to_exclude, true ) ) {
					return false;
				}
			}

			// Exclude by product category.
			$array_to_exclude = wcj_get_option( 'wcj_custom_product_tabs_local_hide_in_cats_' . $custom_tab_index );
			if ( '' !== $array_to_exclude && ! empty( $array_to_exclude ) ) {
				$product_categories_objects = get_the_terms( $product_id, 'product_cat' );
				if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
					foreach ( $product_categories_objects as $product_categories_object ) {
						if ( $array_to_exclude && in_array( (string) $product_categories_object->term_id, $array_to_exclude, true ) ) {
							return false;
						}
					}
				}
			}

			// Exclude by product tag.
			$array_to_exclude = wcj_get_option( 'wcj_custom_product_tabs_local_hide_in_tags_' . $custom_tab_index );
			if ( '' !== $array_to_exclude && ! empty( $array_to_exclude ) ) {
				$product_tags_objects = get_the_terms( $product_id, 'product_tag' );
				if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
					foreach ( $product_tags_objects as $product_tags_object ) {
						if ( $array_to_exclude && in_array( (string) $product_tags_object->term_id, $array_to_exclude, true ) ) {
							return false;
						}
					}
				}
			}

			// Include by product id.
			$array_to_include = wcj_get_option( 'wcj_custom_product_tabs_local_show_in_products_' . $custom_tab_index );
			if ( '' !== $array_to_include && ! empty( $array_to_include ) ) {
				// If NOT in array then hide this tab for this product.
				if ( $array_to_include && ! in_array( (string) $product_id, $array_to_include, true ) ) {
					return false;
				}
			}

			// Include by product category.
			$array_to_include = wcj_get_option( 'wcj_custom_product_tabs_local_show_in_cats_' . $custom_tab_index );
			if ( '' !== $array_to_include && ! empty( $array_to_include ) ) {
				$do_include                 = false;
				$product_categories_objects = get_the_terms( $product_id, 'product_cat' );
				if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
					foreach ( $product_categories_objects as $product_categories_object ) {
						if ( $array_to_include && in_array( (string) $product_categories_object->term_id, $array_to_include, true ) ) {
							$do_include = true;
							break;
						}
					}
				}
				if ( ! $do_include ) {
					return false;
				}
			}

			// Include by product tag.
			$array_to_include = wcj_get_option( 'wcj_custom_product_tabs_local_show_in_tags_' . $custom_tab_index );
			if ( '' !== $array_to_include && ! empty( $array_to_include ) ) {
				$do_include           = false;
				$product_tags_objects = get_the_terms( $product_id, 'product_tag' );
				if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
					foreach ( $product_tags_objects as $product_tags_object ) {
						if ( $array_to_include && in_array( (string) $product_tags_object->term_id, $array_to_include, true ) ) {
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
		 * Get_js_link_script.
		 *
		 * @version 5.6.2
		 * @since   2.8.0
		 * @param string $link defines the link.
		 * @param string $tab_key defines the tab_key.
		 * @param string $do_open_in_new_window defines the do_open_in_new_window.
		 */
		public function get_js_link_script( $link, $tab_key, $do_open_in_new_window ) {
			$link    = do_shortcode( $link );
			$command = ( 'yes' === $do_open_in_new_window ) ?
			'window.open("' . $link . '","_blank");' :
			'window.location = "' . $link . '";';
			return '<script>' .
			'jQuery(document).ready(function() {
				jQuery("li.' . $tab_key . '_tab").click(function(){
					' . $command . '
					return false;
				});
			});' .
			'</script>';
		}

		/**
		 * Maybe_add_js_links.
		 *
		 * @version 5.6.2
		 * @since   2.8.0
		 */
		public function maybe_add_js_links() {
			$current_post_id                             = wcj_maybe_get_product_id_wpml( get_the_ID() );
			$wcj_custom_product_tabs_global_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_product_tabs_global_total_number', 1 ) );
			// Global tabs.
			for ( $i = 1; $i <= $wcj_custom_product_tabs_global_total_number; $i++ ) {

				if ( $this->is_global_tab_visible( $i, $current_post_id ) ) {
					$key  = 'global_' . $i;
					$link = wcj_get_option( 'wcj_custom_product_tabs_link_' . $key, '' );
					if ( '' !== wcj_get_option( 'wcj_custom_product_tabs_title_' . $key, '' ) && '' !== $link ) {
						echo wp_kses_post( $this->get_js_link_script( $link, $this->get_tab_key( $key, 'global' ), wcj_get_option( 'wcj_custom_product_tabs_link_new_tab_' . $key, true ) ) );
					}
				}
			}
			// Local tabs.
			if ( 'yes' !== wcj_get_option( 'wcj_custom_product_tabs_local_enabled', 'yes' ) ) {
				return;
			}
			$total_custom_tabs = get_post_meta( $current_post_id, '_wcj_custom_product_tabs_local_total_number', true );
			if ( ! $total_custom_tabs ) {
				$total_custom_tabs = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
			}
			for ( $i = 1; $i <= $total_custom_tabs; $i++ ) {
				if ( $this->is_local_tab_visible( $i, $current_post_id ) ) {
					$key  = 'local_' . $i;
					$link = get_post_meta( $current_post_id, '_wcj_custom_product_tabs_link_' . $key, true );
					if ( '' !== get_post_meta( $current_post_id, '_wcj_custom_product_tabs_title_' . $key, true ) ) {
						if ( '' !== $link ) {
							echo wp_kses_post( $this->get_js_link_script( $link, $this->get_tab_key( $key, 'local', $current_post_id ), get_post_meta( $current_post_id, '_wcj_custom_product_tabs_link_new_tab_' . $key, true ) ) );
						}
					}
				}
			}
		}

		/**
		 * Get_tab_output.
		 *
		 * @version 5.6.2
		 * @since   3.4.5
		 * @param string $content defines the content.
		 */
		public function get_tab_output( $content ) {
			switch ( wcj_get_option( 'wcj_custom_product_tabs_general_content_processing', 'the_content' ) ) {
				case 'disabled':
					return $content;
				case 'do_shortcode':
					return do_shortcode( $content );
				default:
					return apply_filters( 'the_content', $content );
			}
		}

		/**
		 * Create_new_custom_product_tab_local.
		 *
		 * @version 5.6.8
		 * @param string $key defines the key.
		 * @param string $tab defines the tab.
		 */
		public function create_new_custom_product_tab_local( $key, $tab ) {
			echo wp_kses_post( $this->get_tab_output( get_post_meta( wcj_maybe_get_product_id_wpml( get_the_ID() ), '_wcj_custom_product_tabs_content_' . $this->tab_option_keys['local'][ $key ], true ) ) );
		}

		/**
		 * Create_new_custom_product_tab_global.
		 *
		 * @version 5.6.8
		 * @param string $key defines the key.
		 * @param string $tab defines the tab.
		 */
		public function create_new_custom_product_tab_global( $key, $tab ) {
			echo wp_kses_post( $this->get_tab_output( wcj_get_option( 'wcj_custom_product_tabs_content_' . $this->tab_option_keys['global'][ $key ] ) ) );
		}


		/**
		 * Save_custom_tabs_meta_box.
		 *
		 * @version 5.6.8
		 * @todo    rewrite as standard `WCJ_Module` function
		 * @param int            $post_id defines the post_id.
		 * @param string | array $post defines the post.
		 */
		public function save_custom_tabs_meta_box( $post_id, $post ) {

			// Check that we are saving with custom tab metabox displayed.
			if ( ! isset( $_POST['woojetpack_custom_tabs_save_post'] ) ) {
				return;
			}

			// Save: title, priority, content etc.
			$option_names                    = array(
				'wcj_custom_product_tabs_title_local_',
				'wcj_custom_product_tabs_key_local_',
				'wcj_custom_product_tabs_priority_local_',
				'wcj_custom_product_tabs_content_local_',
				'wcj_custom_product_tabs_link_local_',
				'wcj_custom_product_tabs_link_new_tab_local_',
			);
			$default_total_custom_tabs       = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
			$total_custom_tabs_before_saving = get_post_meta( $post_id, '_wcj_custom_product_tabs_local_total_number', true );
			$total_custom_tabs_before_saving = ( '' !== $total_custom_tabs_before_saving && null !== $total_custom_tabs_before_saving && '' !== $total_custom_tabs_before_saving ) ? $total_custom_tabs_before_saving : $default_total_custom_tabs;
			$wpnonce                         = isset( $_POST['woocommerce_meta_nonce'] ) ? wp_verify_nonce( wp_unslash( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) ), 'woocommerce_save_data' ) : false;

			for ( $i = 1; $i <= $total_custom_tabs_before_saving; $i++ ) {
				if ( $this->is_local_tab_visible( $i, $post_id ) ) {
					foreach ( $option_names as $option_name ) {
						$sanitized_option_name = $wpnonce && isset( $_POST[ $option_name . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ $option_name . $i ] ) ) : '';
						update_post_meta( $post_id, '_' . $option_name . $i, wp_kses_post( wp_unslash( $_POST[ $option_name . $i ] ) ) );
					}
				}
			}

			// Save: total custom tabs number.
			$option_name       = 'wcj_custom_product_tabs_local_total_number';
			$total_custom_tabs = isset( $_POST[ $option_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) ) : $default_total_custom_tabs;
			update_post_meta( $post_id, '_' . $option_name, sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) ) );
		}

		/**
		 * Add_custom_tabs_meta_box.
		 *
		 * @version 5.6.2
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
		 * Create_custom_tabs_meta_box.
		 *
		 * @version 5.6.8
		 */
		public function create_custom_tabs_meta_box() {
			$current_post_id   = wcj_maybe_get_product_id_wpml( get_the_ID() );
			$option_name       = 'wcj_custom_product_tabs_local_total_number';
			$total_custom_tabs = get_post_meta( $current_post_id, '_' . $option_name, true );
			if ( ! $total_custom_tabs ) {
				$total_custom_tabs = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
			}
			$html = '';

			$is_disabled         = apply_filters( 'booster_message', '', 'readonly_string' );
			$is_disabled_message = apply_filters( 'booster_message', '', 'desc' );

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
			echo wp_kses_post( $html );

			$options          = array(
				array(
					'id'    => 'wcj_custom_product_tabs_title_local_',
					'title' => __( 'Title', 'woocommerce-jetpack' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'wcj_custom_product_tabs_key_local_',
					'title' => __( 'Key', 'woocommerce-jetpack' ),
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
				array(
					'id'       => 'wcj_custom_product_tabs_link_local_',
					'title'    => __( 'Link', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'desc_tip' => __( 'If you wish to forward tab to new link, enter it here. In this case content is ignored. Leave blank to show content.', 'woocommerce-jetpack' ),
				),
				array(
					'id'      => 'wcj_custom_product_tabs_link_new_tab_local_',
					'title'   => __( 'Link - Open in New Window', 'woocommerce-jetpack' ),
					'type'    => 'select',
					'options' => array(
						'no'  => __( 'No', 'woocommerce-jetpack' ),
						'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					),
				),
			);
			$enable_wp_editor = ( 'yes' === wcj_get_option( 'wcj_custom_product_tabs_local_wp_editor_enabled', 'yes' ) );
			for ( $i = 1; $i <= $total_custom_tabs; $i++ ) {
				$is_local_tab_visible = $this->is_local_tab_visible( $i, $current_post_id );
				$readonly             = ( $is_local_tab_visible ) ? '' : ' readonly'; // not really used.
				$disabled             = ( $is_local_tab_visible ) ? '' : ' - ' . __( 'Disabled', 'woocommerce-jetpack' );
				$data                 = array();
				$html                 = '<hr>';
				$html                .= '<h4>' . __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i . $disabled . '</h4>';
				if ( $is_local_tab_visible ) {
					foreach ( $options as $option ) {
						$option_id    = $option['id'] . $i;
						$option_value = get_post_meta( $current_post_id, '_' . $option_id, true );
						if ( ! $option_value ) {
							$option_value = wcj_get_option( $option['id'] . 'default_' . $i, '' );
							if ( '' === $option_value && 'wcj_custom_product_tabs_priority_local_' === $option['id'] ) {
								$option_value = ( 50 + $i - 1 );
							}
							if ( '' === $option_value && 'wcj_custom_product_tabs_key_local_' === $option['id'] ) {
								$option_value = 'local_' . $i;
							}
						}
						switch ( $option['type'] ) {
							case 'select':
								$the_field = '<select type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '"' . $readonly . '>';
								foreach ( $option['options'] as $select_option_id => $select_option_title ) {
									$the_field .= '<option value="' . $select_option_id . '" ' . selected( $option_value, $select_option_id, false ) . '>' . $select_option_title . '</option>';
								}
								$the_field .= '</select>';
								break;
							case 'number':
								$the_field = '<input style="width:25%;min-width:100px;" type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '"' . $readonly . '>';
								break;
							case 'text':
								$the_field = '<input style="width:50%;min-width:150px;" type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '"' . $readonly . '>';
								break;
							case 'textarea':
								if ( $enable_wp_editor ) {
									ob_start();
									wp_editor( $option_value, $option_id );
									$the_field = ob_get_clean();
								} else {
									$the_field = '<textarea style="width:100%;height:300px;" id="' . $option_id . '" name="' . $option_id . '"' . $readonly . '>' . $option_value . '</textarea>';
								}
								break;
						}
						if ( '' !== $the_field ) {
							if ( isset( $option['desc_tip'] ) && $option['desc_tip'] && '' !== $option['desc_tip'] && null !== $option['desc_tip'] ) {
								$option['title'] .= '<span class="woocommerce-help-tip" data-tip="' . $option['desc_tip'] . '"></span>';
							}
							$data[] = array( $option['title'], $the_field );
						}
					}
					$html .= wcj_get_table_html(
						$data,
						array(
							'table_class'        => 'widefat',
							'table_style'        => 'margin-bottom:20px;',
							'table_heading_type' => 'vertical',
							'columns_styles'     => array( 'width:10%;' ),
						)
					);
				}
				echo wp_kses_post( $html );
			}
			$html = '<input type="hidden" name="woojetpack_custom_tabs_save_post" value="woojetpack_custom_tabs_save_post">';
			echo wp_kses_post( $html );
		}

	}

endif;

return new WCJ_Product_Tabs();

<?php
/**
 * Booster for WooCommerce - Product Input Fields - Core
 *
 * @version 7.2.5
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_Input_Fields_Core' ) ) :
		/**
		 * WCJ_Product_Input_Fields_Core
		 *
		 * @version 7.1.6
		 * @since   4.2.0
		 */
	class WCJ_Product_Input_Fields_Core {

		/**
		 * Ccope
		 *
		 * @var string scope.
		 */
		public $scope = '';

		/**
		 * The module are_product_input_fields_displayed
		 *
		 * @var varchar $are_product_input_fields_displayed Module are_product_input_fields_displayed.
		 */
		public $are_product_input_fields_displayed;

		/**
		 * Constructor.
		 *
		 * @version 4.2.0
		 * @todo    save all info (e.g. label etc.) in order meta
		 * @todo    add `do_shortcode()` to all applicable options (e.g. "Message on required")
		 * @todo    add positions: `woocommerce_before_add_to_cart_quantity`, `woocommerce_after_add_to_cart_quantity` (same to all similar modules, search for `woocommerce_before_add_to_cart_button`)
		 * @todo    add variable alternative positions: `woocommerce_before_variations_form`, `woocommerce_before_single_variation`, `woocommerce_single_variation`, `woocommerce_after_single_variation`, `woocommerce_after_variations_form` (same to all similar modules, search for `woocommerce_before_add_to_cart_button`)
		 * @param  string $scope define scope.
		 */
		public function __construct( $scope ) {
			$this->scope = $scope;
			if ( 'yes' === wcj_get_option( 'wcj_product_input_fields_' . $this->scope . '_enabled', 'no' ) ) {

				// Show fields at frontend.
				add_action(
					wcj_get_option( 'wcj_product_input_fields_position', 'woocommerce_before_add_to_cart_button' ),
					array( $this, 'add_product_input_fields_to_frontend' ),
					wcj_get_option( 'wcj_product_input_fields_position_priority', 100 )
				);

				// Process from $_POST to cart item data.
				add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_product_input_fields_on_add_to_cart' ), 100, 2 );
				add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_product_input_fields_to_cart_item_data' ), 100, 3 );
				// from session.
				add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_product_input_fields_from_session' ), 100, 3 );

				// Show details at cart, order details, emails.
				if ( 'data' === wcj_get_option( 'wcj_product_input_fields_display_options', 'name' ) ) {
					add_filter( 'woocommerce_get_item_data', array( $this, 'add_product_input_fields_to_cart_item_display_data' ), PHP_INT_MAX, 2 );
				} else {
					add_filter( 'woocommerce_cart_item_name', array( $this, 'add_product_input_fields_to_cart_item_name' ), 100, 3 );
				}
				add_filter( 'woocommerce_order_item_name', array( $this, 'add_product_input_fields_to_order_item_name' ), 100, 2 );

				// Compatibility with YITH Quote plugin.
				add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'yith_raq_display_items_on_order' ), 20, 2 );
				add_filter( 'ywraq_request_quote_view_item_data', array( $this, 'add_product_input_fields_to_cart_item_display_data' ), PHP_INT_MAX, 2 );
				add_filter( 'ywraq_add_item', array( $this, 'yith_raq_add_item' ), 10, 2 );

				// Add item meta from cart to order.
				if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
					add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_product_input_fields_to_order_item_meta_old' ), 100, 3 );
				} else {
					add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_product_input_fields_to_order_item_meta' ), 100, 4 );
				}

				// Make nicer name for product input fields in order at backend (shop manager).
				add_action( 'woocommerce_after_order_itemmeta', array( $this, 'output_custom_input_fields_in_admin_order' ), 100, 3 );
				if ( 'yes' === wcj_get_option( 'wcj_product_input_fields_make_nicer_name_enabled', 'yes' ) ) {
					add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_custom_input_fields_default_output_in_admin_order' ), 100 );
				}

				// Add to emails.
				add_filter( 'woocommerce_email_attachments', array( $this, 'add_files_to_email_attachments' ), PHP_INT_MAX, 3 );

				if ( 'local' === $this->scope ) {
					// Product Edit.
					add_action( 'add_meta_boxes', array( $this, 'add_local_product_input_fields_meta_box_to_product_edit' ) );
					add_action( 'save_post_product', array( $this, 'save_local_product_input_fields_on_product_edit' ), 999, 2 );
				}
			}
		}

		/**
		 * Adds input fields to YITH Quote plugin when Add to Quote button is pressed
		 *
		 * @version 4.2.0
		 * @since   4.2.0
		 *
		 * @param Array $raq Get raq.
		 * @param Array $product_raq Get product raq item.
		 *
		 * @return array
		 */
		public function yith_raq_add_item( $raq, $product_raq ) {
			$result = array_filter(
				$product_raq,
				function ( $key ) {
					return strpos( $key, 'wcj_product_input_fields' ) !== false;
				},
				ARRAY_FILTER_USE_KEY
			);
			if ( ! empty( $result ) ) {
				$raq = array_merge( $raq, $result );
			}
			return $raq;
		}

		/**
		 * Displays input fields on order page
		 *
		 * @version 4.2.0
		 * @since   4.2.0
		 *
		 * @param Array $formatted_meta Get formatted meta.
		 * @param Array $order_item Get order item.
		 *
		 * @return array
		 */
		public function yith_raq_display_items_on_order( $formatted_meta, $order_item ) {
			$order_id    = $order_item->get_order_id();
			$raq_request = get_post_meta( $order_id, '_raq_request', true );
			if ( empty( $raq_request ) ) {
				return $formatted_meta;
			}
			$raq_content = isset( $raq_request['raq_content'] ) ? $raq_request['raq_content'] : '';
			if ( ! empty( $raq_content ) ) {
				reset( $raq_content );
				$first_key = key( $raq_content );
				$data      = $this->add_product_input_fields_to_cart_item_display_data( array(), $raq_content[ $first_key ] );
				if ( is_array( $data ) && $data > 0 ) {
					foreach ( $data as $inf ) {
						$formatted_meta[] = (object) array(
							'key'           => $inf['key'],
							'display_key'   => $inf['key'],
							'display_value' => $inf['value'],
							'value'         => $inf['value'],
						);
					}
				}
			}
			return $formatted_meta;
		}

		/**
		 * Save product input fields on Product Edit.
		 *
		 * @version 5.6.3
		 * @param int         $post_id Get post ID.
		 * @param obj | Array $post Get post.
		 */
		public function save_local_product_input_fields_on_product_edit( $post_id, $post ) {
			$wpnonce = wp_verify_nonce( wp_unslash( isset( $_POST['woocommerce_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) : '' ), 'woocommerce_save_data' );
			// Check that we are saving with input fields displayed.
			if ( ! $wpnonce || ! isset( $_POST['woojetpack_product_input_fields_save_post'] ) ) {
				return;
			}
			// Save values.
			$default_total_input_fields       = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_product_input_fields_local_total_number_default', 1 ) );
			$total_input_fields_before_saving = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_local_total_number', $post_id, 1 ) );
			$total_input_fields_before_saving = ( '' !== $total_input_fields_before_saving ) ? $total_input_fields_before_saving : $default_total_input_fields;
			$options                          = $this->get_options();
			$values                           = array();
			$values['local_total_number']     = isset( $_POST['wcj_product_input_fields_local_total_number'] ) ?
			sanitize_text_field( wp_unslash( $_POST['wcj_product_input_fields_local_total_number'] ) ) : $default_total_input_fields;
			for ( $i = 1; $i <= $total_input_fields_before_saving; $i++ ) {
				foreach ( $options as $option ) {
					$option_name = str_replace( 'wcj_product_input_fields_', '', $option['id'] . $i );
					if ( isset( $_POST[ $option['id'] . $i ] ) ) {
						$values[ $option_name ] = isset( $_POST[ $option['id'] . $i ] ) ? wp_kses_post( wp_unslash( $_POST[ $option['id'] . $i ] ) ) : '';
					} elseif ( 'checkbox' === $option['type'] ) {
						$values[ $option_name ] = 'off';
					}
				}
			}
			update_post_meta( $post_id, '_wcj_product_input_fields', $values );
		}

		/**
		 * Add_local_product_input_fields_meta_box_to_product_edit.
		 *
		 * @version 2.4.8
		 */
		public function add_local_product_input_fields_meta_box_to_product_edit() {
			add_meta_box(
				'wc-jetpack-product-input-fields',
				__( 'Booster: Product Input Fields', 'woocommerce-jetpack' ),
				array( $this, 'create_local_product_input_fields_meta_box' ),
				'product',
				'normal',
				'high'
			);
		}

		/**
		 * Create_local_product_input_fields_meta_box.
		 *
		 * @version 3.5.0
		 * @todo    output options via standard "meta-box" mechanism (and make "serialized" meta box type)
		 */
		public function create_local_product_input_fields_meta_box() {

			$meta_box_id   = 'product_input_fields';
			$meta_box_desc = __( 'Product Input Fields', 'woocommerce-jetpack' );

			$options = $this->get_options();

			// Get total number.
			$current_post_id = get_the_ID();
			$this->maybe_update_local_input_fields( $current_post_id );
			$option_name = 'wcj_' . $meta_box_id . '_local_total_number';
			// If none total number set - check for the default.
			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_local_total_number', $current_post_id, 1 ) );
			if ( ! ( $total_number ) ) {
				$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_' . $meta_box_id . '_local_total_number_default', 1 ) );
			}

			// Start html.
			$html = '';

			// Total number.
			$is_disabled         = apply_filters( 'booster_message', '', 'readonly_string' );
			$is_disabled_message = apply_filters( 'booster_message', '', 'desc' );
			$html               .= '<table>';
			$html               .= '<tr>';
			$html               .= '<th>';
			$html               .= __( 'Total number of ', 'woocommerce-jetpack' ) . $meta_box_desc;
			$html               .= '</th>';
			$html               .= '<td>';
			$html               .= '<input type="number" id="' . $option_name . '" name="' . $option_name . '" value="' . $total_number . '" ' . $is_disabled . '>';
			$html               .= '</td>';
			$html               .= '<td>';
			$html               .= __( 'Click "Update" product after you change this number.', 'woocommerce-jetpack' ) . '<br>' . $is_disabled_message;
			$html               .= '</td>';
			$html               .= '</td>';
			$html               .= '</tr>';
			$html               .= '</table>';

			// The options.
			for ( $i = 1; $i <= $total_number; $i++ ) {
				$data  = array();
				$html .= '<hr>';
				$html .= '<h3>' . __( 'Product Input Field', 'woocommerce-jetpack' ) . ' #' . $i . '</h3>';
				foreach ( $options as $option ) {
					$option_id    = $option['id'] . $i;
					$option_value = $this->get_value( $option_id, $current_post_id, $option['default'] );

					if ( 'checkbox' === $option['type'] ) {
						$is_checked = checked( $option_value, 'on', false );
					}

					$select_options_html = '';
					if ( 'select' === $option['type'] ) {
						foreach ( $option['options'] as $select_option_id => $select_option_label ) {
							$select_options_html .= '<option value="' . $select_option_id . '"' . selected( $option_value, $select_option_id, false ) . '>' .
							$select_option_label . '</option>';
						}
					}

					switch ( $option['type'] ) {
						case 'number':
						case 'text':
							$the_field = '<input class="widefat" type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '">';
							break;
						case 'textarea':
							$the_field = '<textarea  style="' . ( isset( $option['css'] ) ? $option['css'] : '' ) .
							'" class="widefat" style="" id="' . $option_id . '" name="' . $option_id . '">' . $option_value . '</textarea>';
							break;
						case 'checkbox':
							$the_field = '<input class="checkbox" type="checkbox" name="' . $option_id . '" id="' . $option_id . '" ' . $is_checked . ' /> ' . $option['title'];
							break;
						case 'select':
							$the_field = '<select class="widefat" id="' . $option_id . '" name="' . $option_id . '">' . $select_options_html . '</select>';
							break;
					}

					$maybe_tooltip = ( isset( $option['short_title'] ) && $option['short_title'] !== $option['title'] ? wc_help_tip( $option['title'], true ) : '' );

					$data[] = array(
						( isset( $option['short_title'] ) ? $option['short_title'] : $option['title'] ) . $maybe_tooltip,
						$the_field,
					);
				}
				$html .= wcj_get_table_html(
					$data,
					array(
						'table_heading_type' => 'vertical',
						'table_class'        => 'widefat striped',
						'columns_styles'     => array( 'width:33%;' ),
					)
				);
			}
			$html .= '<input type="hidden" name="woojetpack_' . $meta_box_id . '_save_post" value="woojetpack_' . $meta_box_id . '_save_post">';

			// Output.
			echo wp_kses_post( $html );
		}

		/**
		 * Get_options.
		 *
		 * @version 3.1.0
		 */
		public function get_options() {
			return require 'wcj-product-input-fields-options.php';
		}

		/**
		 * Is_enabled_for_product_global.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param string $field_nr Get fields.
		 * @param int    $product_id Get product id.
		 */
		public function is_enabled_for_product_global( $field_nr, $product_id ) {
			return wcj_is_enabled_for_product(
				$product_id,
				array(
					'include_products'   => wcj_get_option( 'wcj_product_input_fields_in_products_global_' . $field_nr, '' ),
					'exclude_products'   => wcj_get_option( 'wcj_product_input_fields_ex_products_global_' . $field_nr, '' ),
					'include_categories' => wcj_get_option( 'wcj_product_input_fields_in_cats_global_' . $field_nr, '' ),
					'exclude_categories' => wcj_get_option( 'wcj_product_input_fields_ex_cats_global_' . $field_nr, '' ),
					'include_tags'       => wcj_get_option( 'wcj_product_input_fields_in_tags_global_' . $field_nr, '' ),
					'exclude_tags'       => wcj_get_option( 'wcj_product_input_fields_ex_tags_global_' . $field_nr, '' ),
				)
			);
		}

		/**
		 * Is_enabled.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param string $field_nr Get fields.
		 * @param int    $product_id Get product id.
		 */
		public function is_enabled( $field_nr, $product_id ) {
			$is_enabled = $this->get_value( 'wcj_product_input_fields_enabled_' . $this->scope . '_' . $field_nr, $product_id, 'no' );
			return ( ( 'on' === $is_enabled || 'yes' === $is_enabled ) && ( 'local' === $this->scope || $this->is_enabled_for_product_global( $field_nr, $product_id ) ) );
		}

		/**
		 * Add_files_to_email_attachments.
		 *
		 * @version 4.4.0
		 * @since   2.5.0
		 * @param Array  $attachments Get attachments.
		 * @param string $status Get status.
		 * @param Array  $order Get order.
		 */
		public function add_files_to_email_attachments( $attachments, $status, $order ) {
			if ( is_null( $order ) ) {
				return $attachments;
			}

			if (
			( 'new_order' === $status && 'yes' === wcj_get_option( 'wcj_product_input_fields_attach_to_admin_new_order', 'yes' ) ) ||
			( 'customer_processing_order' === $status && 'yes' === wcj_get_option( 'wcj_product_input_fields_attach_to_customer_processing_order', 'yes' ) )
			) {
				foreach ( $order->get_items() as $item_key => $item ) {
					$product_id   = $item['product_id'];
					$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $product_id, 1 ) );
					for ( $i = 1; $i <= $total_number; $i++ ) {
						$meta_key = 'wcj_product_input_fields_' . $this->scope . '_' . $i;
						if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
							$meta_key = '_' . $meta_key;
						}
						$meta_exists = ( WCJ_IS_WC_VERSION_BELOW_3 ? isset( $item[ $meta_key ] ) : $item->meta_exists( $meta_key ) );
						if ( $meta_exists ) {
							$_value = ( WCJ_IS_WC_VERSION_BELOW_3 ? $item[ $meta_key ] : $item->get_meta( $meta_key ) );
							$_value = maybe_unserialize( $_value );
							if ( isset( $_value['wcj_type'] ) && 'file' === $_value['wcj_type'] && isset( $_value['tmp_name'] ) ) {
								$file_path     = $_value['tmp_name'];
								$attachments[] = $file_path;
							}
						}
					}
				}
			}
			return $attachments;
		}

		/**
		 * Hide_custom_input_fields_default_output_in_admin_order.
		 *
		 * @todo    get actual (max) number of fields in case of local scope
		 * @param string $hidden_metas Get hidden metas.
		 */
		public function hide_custom_input_fields_default_output_in_admin_order( $hidden_metas ) {
			$total_number = 0;
			if ( 'global' === $this->scope ) {
				$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', 0, 1 ) );
			} else {
				$max_number_of_fields_for_local = 100;
				$total_number                   = $max_number_of_fields_for_local;
			}

			for ( $i = 1; $i <= $total_number; $i++ ) {
				$hidden_metas[] = '_wcj_product_input_fields_' . $this->scope . '_' . $i;
			}
			return $hidden_metas;
		}

		/**
		 * Output_custom_input_fields_in_admin_order.
		 *
		 * @version 5.6.7
		 * @param int   $item_id Get item id.
		 * @param Array $item Get item.
		 * @param Array $_product Get products.
		 */
		public function output_custom_input_fields_in_admin_order( $item_id, $item, $_product ) {
			if ( null === $_product ) {
				// Shipping.
				return;
			}
			echo '<table cellspacing="0" class="display_meta">';
			$_product_id  = wcj_get_product_id_or_variation_parent_id( $_product );
			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $_product_id, 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				$value = isset( $item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ] ) ? $item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ] : '';
				$type  = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $_product_id, '' );
				if ( 'file' === $type ) {
					$value = maybe_unserialize( $value );
					if ( isset( $value['name'] ) ) {
						if ( isset( $value['wcj_uniqid'] ) ) {
							$value = '<a href="' . esc_url(
								add_query_arg(
									array(
										'wcj_download_file' => $_product_id . '_' . $i . '_' . $value['wcj_uniqid'] . '.' . pathinfo( $value['name'], PATHINFO_EXTENSION ),
										'wcj_download_file_nonce' => wp_create_nonce( 'wcj_download_file_nonce' ),
									)
								)
							) . '">' . $value['name'] . '</a>';
						} else {
							$value = '<a href="' . esc_url( add_query_arg( 'wcj_download_file', $item_id . '_' . $i . '.' . pathinfo( $value['name'], PATHINFO_EXTENSION ) ) ) . '">' . $value['name'] . '</a>';
						}
					}
				} else {
					if ( 'no' === wcj_get_option( 'wcj_product_input_fields_make_nicer_name_enabled', 'yes' ) ) {
						continue;
					}
				}
				if ( '' !== $value ) {
					$nice_name = $this->get_value( 'wcj_product_input_fields_title_' . $this->scope . '_' . $i, $_product_id, '' );
					if ( '' === ( $nice_name ) ) {
						$nice_name = __( 'Product Input Field', 'woocommerce-jetpack' ) . ' (' . $this->scope . ') #' . $i;
					}
					echo '<tr><th>' . wp_kses_post( $nice_name ) . ':</th><td>' . wp_kses_post( $value ) . '</td></tr>';
				}
			}
			echo '</table>';
		}

		/**
		 * Get_value.
		 *
		 * @version 5.6.2
		 * @todo    `wcj_get_product_input_field_value()` is almost identical
		 * @param string $option_name Get option name.
		 * @param int    $product_id Get product id.
		 * @param string $default Get default value.
		 */
		public function get_value( $option_name, $product_id, $default ) {
			if ( 'global' === $this->scope ) {
				return wcj_get_option( $option_name, $default );
			} else { // local.
				$options = get_post_meta( $product_id, '_wcj_product_input_fields', true );
				if ( '' !== ( $options ) ) {
					$option_name = str_replace( 'wcj_product_input_fields_', '', $option_name );
					return ( isset( $options[ $option_name ] ) ? $options[ $option_name ] : $default );
				} else { // Booster version  < 3.5.0.
					return get_post_meta( $product_id, '_' . $option_name, true );
				}
			}
		}

		/**
		 * Validate_product_input_fields_on_add_to_cart.
		 *
		 * @version 7.2.5
		 * @param bool $passed define passed value.
		 * @param int  $product_id Get product id.
		 */
		public function validate_product_input_fields_on_add_to_cart( $passed, $product_id ) {
			$wpnonce = isset( $_REQUEST['wcj_product_input_fields-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_product_input_fields-nonce'] ), 'wcj_product_input_fields' ) : false;

			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $product_id, 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {

				if ( ! $this->is_enabled( $i, $product_id ) ) {
					continue;
				}

				$type       = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $product_id, '' );
				$field_name = 'wcj_product_input_fields_' . $this->scope . '_' . $i;

				if ( 'checkbox' === $type && ! isset( $_POST[ $field_name ] ) ) {
					$_POST[ $field_name ] = 'off';
				}

				$is_required = $this->get_value( 'wcj_product_input_fields_required_' . $this->scope . '_' . $i, $product_id, 'no' );
				if ( 'on' === $is_required || 'yes' === $is_required ) {
					if ( 'file' === $type ) {
						$field_value = ( isset( $_FILES[ $field_name ]['name'] ) ) ? sanitize_text_field( wp_unslash( $_FILES[ $field_name ]['name'] ) ) : '';
					} else {
						$field_value = ( isset( $_POST[ $field_name ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : '';
						if ( 'checkbox' === $type && 'off' === $field_value ) {
							$field_value = '';
						}
					}
					if ( '' === $field_value || ! $wpnonce ) {
						$passed = false;
						wc_add_notice( $this->get_value( 'wcj_product_input_fields_required_message_' . $this->scope . '_' . $i, $product_id, '' ), 'error' );
					}
				}

				if ( 'file' === $type && isset( $_FILES[ $field_name ] ) && '' !== $_FILES[ $field_name ]['name'] ) {
					// Validate file type.
					$validate = wp_check_filetype( $_FILES[ $field_name ]['name'] );
					if ( empty( $validate['type'] ) ) {
						$passed = false;
						wc_add_notice( __( 'File type is not allowed.', 'woocommerce-jetpack' ), 'error' );
					}
					$file_accept = $this->get_value( 'wcj_product_input_fields_type_file_accept_' . $this->scope . '_' . $i, $product_id, '' );
					if ( '' !== ( $file_accept ) ) {
						$file_accept = explode( ',', $file_accept );
						if ( is_array( $file_accept ) && ! empty( $file_accept ) ) {
							$file_type = '.' . pathinfo( sanitize_text_field( wp_unslash( $_FILES[ $field_name ]['name'] ) ), PATHINFO_EXTENSION );
							if ( ! in_array( $file_type, $file_accept, true ) ) {
								$passed = false;
								wc_add_notice( __( 'Wrong file type!', 'woocommerce-jetpack' ), 'error' );
							}
						}
					}
					// Validate file max size.
					$max_file_size = $this->get_value( 'wcj_product_input_fields_type_file_max_size_' . $this->scope . '_' . $i, $product_id, '' );
					if ( $max_file_size > 0 ) {
						if ( isset( $_FILES[ $field_name ]['size'] ) && sanitize_text_field( wp_unslash( $_FILES[ $field_name ]['size'] ) ) > $max_file_size ) {
							$passed = false;
							wc_add_notice( __( 'File is too big!', 'woocommerce-jetpack' ), 'error' );
						}
					}
				}
			}

			return $passed;
		}

		/**
		 * Maybe_update_local_input_fields.
		 *
		 * @version 3.5.0
		 * @since   3.5.0
		 * @todo    (maybe) if `$total_number` is not set - use `$default_total_input_fields` instead
		 * @param int $_product_id get product id.
		 */
		public function maybe_update_local_input_fields( $_product_id ) {
			if ( 'local' !== $this->scope ) {
				return;
			}
			if ( '' !== get_post_meta( $_product_id, '_wcj_product_input_fields', true ) ) {
				return;
			}
			$values                                   = array();
			$options                                  = $this->get_options();
			$total_number                             = apply_filters( 'booster_option', 1, get_post_meta( $_product_id, '_wcj_product_input_fields_' . $this->scope . '_total_number', true ) );
			$values[ $this->scope . '_total_number' ] = $total_number;
			delete_post_meta( $_product_id, '_wcj_product_input_fields_' . $this->scope . '_total_number' );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				foreach ( $options as $option ) {
					$option_id = $option['id'] . $i;
					if ( metadata_exists( 'post', $_product_id, '_' . $option_id ) ) {
						$option_value = get_post_meta( $_product_id, '_' . $option_id, true );
						delete_post_meta( $_product_id, '_' . $option_id );
					} else {
						$option_value = $option['default'];
					}
					$values[ str_replace( 'wcj_product_input_fields_', '', $option_id ) ] = $option_value;
				}
			}
			update_post_meta( $_product_id, '_wcj_product_input_fields', $values );
		}

		/**
		 * Maybe_stripslashes.
		 *
		 * @version 3.9.1
		 * @since   3.9.1
		 * @param string $str Get string value.
		 */
		public function maybe_stripslashes( $str ) {
			return ( 'yes' === wcj_get_option( 'wcj_product_input_fields_stripslashes', 'no' ) ? stripslashes( $str ) : $str );
		}

		/**
		 * Add_product_input_fields_to_frontend.
		 *
		 * @version
		 * @todo    `$set_value` - add "default" option for all other types except checkbox
		 * @todo    `$set_value` - 'file' type
		 * @todo    add `required` attributes
		 */
		public function add_product_input_fields_to_frontend() {
			if ( isset( $this->are_product_input_fields_displayed ) && 'yes' === wcj_get_option( 'wcj_product_input_fields_check_for_outputted_data', 'yes' ) ) {
				return;
			}
			global $product;
			if ( ! $product ) {
				return;
			}

			$_product_id = wcj_get_product_id_or_variation_parent_id( $product );
			$this->maybe_update_local_input_fields( $_product_id );
			$fields       = array();
			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $_product_id, 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {

				$type        = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $_product_id, 'text' );
				$is_required = $this->get_value( 'wcj_product_input_fields_required_' . $this->scope . '_' . $i, $_product_id, 'no' );
				$title       = $this->get_value( 'wcj_product_input_fields_title_' . $this->scope . '_' . $i, $_product_id, '' );
				$placeholder = $this->get_value( 'wcj_product_input_fields_placeholder_' . $this->scope . '_' . $i, $_product_id, '' );
				$maxlength   = '';
				if ( ( 'text' === $type || 'textarea' === $type ) && '0' < $this->get_value( 'wcj_product_input_fields_maxlength_' . $this->scope . '_' . $i, $_product_id, '' ) ) {
					$maxlength = ' maxlength="' . $this->get_value( 'wcj_product_input_fields_maxlength_' . $this->scope . '_' . $i, $_product_id, '' ) . '"';
				}

				$datepicker_format = $this->get_value( 'wcj_product_input_fields_type_datepicker_format_' . $this->scope . '_' . $i, $_product_id, '' );
				if ( '' === $datepicker_format ) {
					$datepicker_format = wcj_get_option( 'date_format' );
				}
				$datepicker_format     = wcj_date_format_php_to_js( $datepicker_format );
				$datepicker_mindate    = $this->get_value( 'wcj_product_input_fields_type_datepicker_mindate_' . $this->scope . '_' . $i, $_product_id, -365 );
				$datepicker_maxdate    = $this->get_value( 'wcj_product_input_fields_type_datepicker_maxdate_' . $this->scope . '_' . $i, $_product_id, 365 );
				$datepicker_firstday   = $this->get_value( 'wcj_product_input_fields_type_datepicker_firstday_' . $this->scope . '_' . $i, $_product_id, 0 );
				$datepicker_changeyear = $this->get_value( 'wcj_product_input_fields_type_datepicker_changeyear_' . $this->scope . '_' . $i, $_product_id, 'no' );
				$datepicker_yearrange  = $this->get_value( 'wcj_product_input_fields_type_datepicker_yearrange_' . $this->scope . '_' . $i, $_product_id, 'c-10:c+10' );
				if ( 'on' === $datepicker_changeyear || 'yes' === $datepicker_changeyear ) {
					$datepicker_year = 'changeyear="1" yearRange="' . $datepicker_yearrange . '" ';
				} else {
					$datepicker_year = '';
				}

				$timepicker_format   = $this->get_value( 'wcj_product_input_fields_type_timepicker_format_' . $this->scope . '_' . $i, $_product_id, 'hh:mm p' );
				$timepicker_interval = $this->get_value( 'wcj_product_input_fields_type_timepicker_interval_' . $this->scope . '_' . $i, $_product_id, 15 );
				$timepicker_mintime  = $this->get_value( 'wcj_product_input_fields_type_timepicker_mintime_' . $this->scope . '_' . $i, $_product_id, '' );
				$timepicker_maxtime  = $this->get_value( 'wcj_product_input_fields_type_timepicker_maxtime_' . $this->scope . '_' . $i, $_product_id, '' );
				if ( '' !== $timepicker_mintime ) {
					$timepicker_mintime = ' mintime="' . $timepicker_mintime . '"';
				}
				if ( '' !== $timepicker_maxtime ) {
					$timepicker_maxtime = ' maxtime="' . $timepicker_maxtime . '"';
				}

				$file_accept       = $this->get_value( 'wcj_product_input_fields_type_file_accept_' . $this->scope . '_' . $i, $_product_id, '' );
				$custom_attributes = ( 'file' === $type ) ? ' accept="' . $file_accept . '"' : '';
				$field_name        = 'wcj_product_input_fields_' . $this->scope . '_' . $i;

				if ( 'on' === $is_required || 'yes' === $is_required ) {
					$title .= wcj_get_option( 'wcj_product_input_fields_frontend_view_required_html', '&nbsp;<abbr class="required" title="required">*</abbr>' );
				}

				if ( $this->is_enabled( $i, $_product_id ) ) {

					$wpnonce   = isset( $_REQUEST['wcj_product_input_fields-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_product_input_fields-nonce'] ), 'wcj_product_input_fields' ) : false;
					$set_value = ( $wpnonce && isset( $_POST[ $field_name ] ) ?
					$this->maybe_stripslashes( sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) ) :
					( 'checkbox' === $type ?
						( 'yes' === $this->get_value( 'wcj_product_input_fields_type_checkbox_default_' . $this->scope . '_' . $i, $_product_id, 'no' ) ? 'on' : 'off' ) :
						''
					)
					);

					$html = '';

					$class = $this->get_value( 'wcj_product_input_fields_class_' . $this->scope . '_' . $i, $_product_id, '' );
					if ( '' !== $class ) {
						$class = ' ' . $class;
					}

					switch ( $type ) {

						case 'number':
						case 'text':
						case 'file':
						case 'password':
						case 'email':
						case 'tel':
							$html = '<input value="' . $set_value . '" class="wcj_product_input_fields' . $class . '" id="' . $field_name . '" type="' . $type . '" name="' .
							$field_name . '" placeholder="' . $placeholder . '"' . $custom_attributes . $maxlength . '>';

							break;

						case 'checkbox':
							$checked = checked(
								$set_value,
								'on',
								false
							);
							$html    = '<input class="wcj_product_input_fields' . $class . '" id="' . $field_name . '" type="' . $type . '" name="' . $field_name . '"' .
								$custom_attributes . $checked . '>';
							break;

						case 'datepicker':
							$html = '<input value="' . $set_value . '" class="wcj_product_input_fields' . $class . '" id="' . $field_name . '" ' . $datepicker_year . 'firstday="' .
							$datepicker_firstday . '" dateformat="' . $datepicker_format . '" mindate="' .
							$datepicker_mindate . '" maxdate="' . $datepicker_maxdate . '" type="' .
							$type . '" display="date" name="' . $field_name . '" placeholder="' . $placeholder . '"' . $custom_attributes . '>';
							break;

						case 'weekpicker':
							$html = '<input value="' . $set_value . '" class="wcj_product_input_fields' . $class . '" id="' . $field_name . '" ' . $datepicker_year . 'firstday="' .
							$datepicker_firstday . '" dateformat="' . $datepicker_format . '" mindate="' .
							$datepicker_mindate . '" maxdate="' . $datepicker_maxdate . '" type="' .
							$type . '" display="week" name="' . $field_name . '" placeholder="' . $placeholder . '"' . $custom_attributes . '>';
							break;

						case 'timepicker':
							$html = '<input value="' . $set_value . '" class="wcj_product_input_fields' . $class . '" id="' . $field_name . '"' .
							$timepicker_mintime . $timepicker_maxtime . ' interval="' . $timepicker_interval . '" timeformat="' .
							$timepicker_format . '" type="' . $type . '" display="time" name="' . $field_name . '" placeholder="' .
							$placeholder . '"' . $custom_attributes . '>';
							break;

						case 'textarea':
							$html = '<textarea' . $maxlength . ' class="wcj_product_input_fields' . $class . '" id="' . $field_name . '" name="' . $field_name . '" placeholder="' . $placeholder . '">' .
							$set_value . '</textarea>';
							break;

						case 'select':
							$select_options_raw = $this->get_value( 'wcj_product_input_fields_type_select_options_' . $this->scope . '_' . $i, $_product_id, '' );
							$select_options     = wcj_get_select_options( $select_options_raw, false );
							if ( '' !== $placeholder ) {
								$select_options = array_replace( array( '' => $placeholder ), $select_options );
							}
							$select_options_html = '';
							if ( ! empty( $select_options ) ) {
								reset( $select_options );
								$value = ( '' !== $set_value ? $set_value : key( $select_options ) );
								foreach ( $select_options as $select_option_key => $select_option_title ) {
									$select_options_html .= '<option value="' . $select_option_key . '" ' . selected( $value, $select_option_key, false ) . '>';
									$select_options_html .= $select_option_title;
									$select_options_html .= '</option>';
								}
							}
							$html = '<select class="wcj_product_input_fields' . $class . '" id="' . $field_name . '" name="' . $field_name . '">' . $select_options_html . '</select>';
							break;

						case 'radio':
							$select_options_raw  = $this->get_value( 'wcj_product_input_fields_type_select_options_' . $this->scope . '_' . $i, $_product_id, '' );
							$select_options      = wcj_get_select_options( $select_options_raw, false );
							$select_options_html = '';
							if ( ! empty( $select_options ) ) {
								reset( $select_options );
								$value    = ( '' !== $set_value ? $set_value : key( $select_options ) );
								$template = wcj_get_option(
									'wcj_product_input_fields_field_template_radio',
									'%radio_field_html%<label for="%radio_field_id%" class="radio">%radio_field_title%</label><br>'
								);
								foreach ( $select_options as $option_key => $option_text ) {
									$replaced_values      = array(
										'%radio_field_html%' => '<input type="radio" class="input-radio wcj_product_input_fields' . $class . '" value="' . esc_attr( $option_key ) .
										'" name="' . $field_name . '" id="' . $field_name . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />',
										'%radio_field_id%' => $field_name . '_' . esc_attr( $option_key ),
										'%radio_field_title%' => $option_text,
									);
									$select_options_html .= str_replace( array_keys( $replaced_values ), $replaced_values, $template );
								}
								$html = $select_options_html;
							}
							break;

						case 'country':
							$countries = WC()->countries->get_allowed_countries();
							if ( count( $countries ) > 1 ) {
								$value = ( '' !== $set_value ? $set_value : key( $countries ) );
								$field = '<select name="' . $field_name . '" id="' . $field_name . '" class="country_to_state country_select wcj_product_input_fields' . $class . '">' .
								'<option value="">' . __( 'Select a country&hellip;', 'woocommerce' ) . '</option>';
								foreach ( $countries as $ckey => $cvalue ) {
									$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . $cvalue . '</option>';
								}
								$field .= '</select>';
								$html   = $field;
							}
							break;

					}
					$html        = str_replace(
						array( '%field_title%', '%field_id%', '%field_html%' ),
						array( $title, $field_name, $html ),
						get_option( 'wcj_product_input_fields_field_template', '<p><label for="%field_id%">%field_title%</label> %field_html%</p>' )
					);
					$field_order = $this->get_value( 'wcj_product_input_fields_order_' . $this->scope . '_' . $i, $_product_id, 0 );
					if ( '0' === (string) $field_order ) {
						$field_order = $i;
					}
					$fields[ $field_order ] = apply_filters(
						'wcj_product_input_field_frontend_html',
						$html,
						array(
							'title'             => do_shortcode( $title ),
							'type'              => $type,
							'field_name'        => $field_name,
							'placeholder'       => do_shortcode( $placeholder ),
							'custom_attributes' => $custom_attributes,
							'_product_id'       => $_product_id,
							'_field_nr'         => $i,
							'_scope'            => $this->scope,
						)
					);
				}
			}
			ksort( $fields );
			if ( ! empty( $fields ) ) {
				echo wp_kses_post( wcj_get_option( 'wcj_product_input_fields_start_template', '' ) . implode( $fields ) . wp_nonce_field( 'wcj_product_input_fields', 'wcj_product_input_fields-nonce' ) . wcj_get_option( 'wcj_product_input_fields_end_template', '' ) );
				$this->are_product_input_fields_displayed = true;
			}
		}

		/**
		 * Add_product_input_fields_to_cart_item_data.
		 *
		 * From `$_POST` to `$cart_item_data`
		 *
		 * @version 5.6.8
		 * @param Array $cart_item_data Get cart items.
		 * @param int   $product_id Get product id.
		 * @param int   $variation_id Get variation id.
		 */
		public function add_product_input_fields_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
			$wpnonce = isset( $_REQUEST['wcj_product_input_fields-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_product_input_fields-nonce'] ), 'wcj_product_input_fields' ) : false;
			if ( ! $wpnonce ) {
				return $cart_item_data;
			}
			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $product_id, 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( ! $this->is_enabled( $i, $product_id ) ) {
					continue;
				}
				$type       = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $product_id, '' );
				$value_name = 'wcj_product_input_fields_' . $this->scope . '_' . $i;
				if ( 'file' === $type ) {
					if ( isset( $_FILES[ $value_name ] ) ) {
						$cart_item_data[ $value_name ] = array_map( 'sanitize_text_field', wp_unslash( $_FILES[ $value_name ] ) );
						$tmp_dest_file                 = tempnam( sys_get_temp_dir(), 'wcj' );
						move_uploaded_file( $cart_item_data[ $value_name ]['tmp_name'], $tmp_dest_file );
						$cart_item_data[ $value_name ]['tmp_name'] = $tmp_dest_file;
					}
				} else {
					if ( isset( $_POST[ $value_name ] ) ) {
						$cart_item_data[ $value_name ] = $this->maybe_stripslashes( sanitize_text_field( wp_unslash( $_POST[ $value_name ] ) ) );
					}
				}
			}
			return $cart_item_data;
		}

		/**
		 * Aet_cart_item_product_input_fields_from_session.
		 *
		 * @param Array  $item Get items.
		 * @param Array  $values Get values.
		 * @param string $key Get key value.
		 */
		public function get_cart_item_product_input_fields_from_session( $item, $values, $key ) {
			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $item['product_id'], 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( array_key_exists( 'wcj_product_input_fields_' . $this->scope . '_' . $i, $values ) ) {
					$item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ] = $values[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ];
				}
			}
			return $item;
		}

		/**
		 * Adds product input values to order details (and emails).
		 *
		 * @version 3.1.0
		 * @param string $name Get order item name.
		 * @param Array  $item Get item data.
		 * @param bool   $is_cart get is cart value.
		 */
		public function add_product_input_fields_to_order_item_name( $name, $item, $is_cart = false ) {
			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $item['product_id'], 1 ) );
			if ( $total_number < 1 ) {
				return $name;
			}
			if ( $is_cart ) {
				$name         .= wcj_get_option( 'wcj_product_input_fields_cart_start_template', '<dl style="font-size:smaller;">' );
				$item_template = wcj_get_option( 'wcj_product_input_fields_cart_field_template', '<dt>%title%</dt><dd>%value%</dd>' );
			} else {
				$item_template = wcj_get_option( 'wcj_product_input_fields_frontend_view_order_table_format', '&nbsp;| %title% %value%' );
			}
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( ! $this->is_enabled( $i, $item['product_id'] ) ) {
					continue;
				}
				$type     = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $item['product_id'], '' );
				$meta_key = 'wcj_product_input_fields_' . $this->scope . '_' . $i;
				if ( ! WCJ_IS_WC_VERSION_BELOW_3 && ! $is_cart ) {
					$meta_key = '_' . $meta_key;
				}
				$meta_exists = ( WCJ_IS_WC_VERSION_BELOW_3 || $is_cart ? array_key_exists( $meta_key, $item ) : $item->meta_exists( $meta_key ) );
				if ( $meta_exists ) {
					$value = ( WCJ_IS_WC_VERSION_BELOW_3 || $is_cart ? $item[ $meta_key ] : $item->get_meta( $meta_key ) );
				} elseif ( 'checkbox' === $type ) {
					$value       = 'off';
					$meta_exists = true;
				}
				if ( $meta_exists ) {
					$title     = $this->get_value( 'wcj_product_input_fields_title_' . $this->scope . '_' . $i, $item['product_id'], '' );
					$yes_value = $this->get_value( 'wcj_product_input_fields_type_checkbox_yes_' . $this->scope . '_' . $i, $item['product_id'], '' );
					$no_value  = $this->get_value( 'wcj_product_input_fields_type_checkbox_no_' . $this->scope . '_' . $i, $item['product_id'], '' );
					if ( 'checkbox' === $type ) {
						$value = ( 'on' === $value ) ? $yes_value : $no_value;
					}
					if ( 'file' === $type ) {
						$value = maybe_unserialize( $value );
						$value = ( isset( $value['name'] ) ) ? $value['name'] : '';
					}
					if ( '' !== $value && is_string( $value ) ) {
						$name .= str_replace( array( '%title%', '%value%' ), array( $title, $value ), $item_template );
					}
				}
			}
			if ( $is_cart ) {
				$name .= wcj_get_option( 'wcj_product_input_fields_cart_end_template', '</dl>' );
			}
			return $name;
		}

		/**
		 * Adds product input values to cart item details.
		 *
		 * @version 2.4.0
		 * @param string $name Get order item name.
		 * @param Array  $cart_item Get cart items.
		 * @param string $cart_item_key Get cart item key value.
		 */
		public function add_product_input_fields_to_cart_item_name( $name, $cart_item, $cart_item_key ) {
			return $this->add_product_input_fields_to_order_item_name( $name, $cart_item, true );
		}

		/**
		 * Add_product_input_fields_to_cart_item_display_data.
		 *
		 * @version 4.2.0
		 * @since   2.9.0
		 * @param Array $item_data Get item data.
		 * @param Array $item Get items.
		 */
		public function add_product_input_fields_to_cart_item_display_data( $item_data, $item ) {
			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $item['product_id'], 1 ) );
			if ( $total_number < 1 ) {
				return $item_data;
			}
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( ! $this->is_enabled( $i, $item['product_id'] ) ) {
					continue;
				}
				$type        = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $item['product_id'], '' );
				$meta_key    = 'wcj_product_input_fields_' . $this->scope . '_' . $i;
				$meta_exists = array_key_exists( $meta_key, $item );
				if ( $meta_exists ) {
					$value = $item[ $meta_key ];
				} elseif ( 'checkbox' === $type ) {
					$value       = 'off';
					$meta_exists = true;
				}
				if ( $meta_exists ) {
					$title     = $this->get_value( 'wcj_product_input_fields_title_' . $this->scope . '_' . $i, $item['product_id'], '' );
					$yes_value = $this->get_value( 'wcj_product_input_fields_type_checkbox_yes_' . $this->scope . '_' . $i, $item['product_id'], '' );
					$no_value  = $this->get_value( 'wcj_product_input_fields_type_checkbox_no_' . $this->scope . '_' . $i, $item['product_id'], '' );
					if ( 'checkbox' === $type ) {
						$value = ( 'on' === $value ) ? $yes_value : $no_value;
					}
					if ( 'file' === $type ) {
						$value = maybe_unserialize( $value );
						$value = ( isset( $value['name'] ) ) ? $value['name'] : '';
					}
					if ( '' !== $value ) {
						$item_data[] = array(
							'key'     => $title,
							'display' => $value,
							'value'   => $value,
						);
					}
				}
			}
			return $item_data;
		}

		/**
		 * Add_product_input_fields_to_order_item_meta.
		 *
		 * @see https://stackoverflow.com/a/49419394/1193038
		 * @version 4.2.0
		 * @since   4.2.0
		 * @param Array  $item Get items.
		 * @param string $cart_item_key Get cart item key value.
		 * @param Array  $values Get values.
		 * @param Array  $order Get order data.
		 */
		public function add_product_input_fields_to_order_item_meta( $item, $cart_item_key, $values, $order ) {
			$this->add_input_fields_to_order_item_meta( $item, '', $values, $cart_item_key );
		}

		/**
		 * Add_product_input_fields_to_order_item_meta_old.
		 *
		 * @version 4.2.0
		 * @since   4.2.0
		 * @param int    $item_id Get item id.
		 * @param Array  $values Get values.
		 * @param string $cart_item_key Get cart item key value.
		 */
		public function add_product_input_fields_to_order_item_meta_old( $item_id, $values, $cart_item_key ) {
			$this->add_input_fields_to_order_item_meta( '', $item_id, $values, $cart_item_key );
		}

		/**
		 * Add_input_fields_to_order_item_meta.
		 *
		 * @version 5.6.8
		 * @since   3.4.0
		 * @param Array  $item Get items.
		 * @param int    $item_id Get item id.
		 * @param Array  $values Get values.
		 * @param string $cart_item_key Get cart item key value.
		 *
		 * @throws Exception Exception.
		 */
		public function add_input_fields_to_order_item_meta( $item, $item_id, $values, $cart_item_key ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			global $wp_filesystem;
			WP_Filesystem();
			$total_number = apply_filters( 'booster_option', 1, $this->get_value( 'wcj_product_input_fields_' . $this->scope . '_total_number', $values['product_id'], 1 ) );
			for ( $i = 1; $i <= $total_number; $i ++ ) {
				if ( array_key_exists( 'wcj_product_input_fields_' . $this->scope . '_' . $i, $values ) ) {
					$type              = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $values['product_id'], '' );
					$input_field_value = $values[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ];
					if ( 'file' === $type ) {
						$unique_id  = uniqid();
						$tmp_name   = $input_field_value['tmp_name'];
						$ext        = pathinfo( $input_field_value['name'], PATHINFO_EXTENSION );
						$name       = $item->get_product_id() . '_' . $i . '_' . $unique_id . '.' . $ext;
						$upload_dir = wcj_get_wcj_uploads_dir( 'input_fields_uploads' );
						if ( ! file_exists( $upload_dir ) ) {
							mkdir( $upload_dir, 0755, true );
						}
						$upload_dir_and_name = $upload_dir . '/' . $name;
						$file_data           = $wp_filesystem->get_contents( $tmp_name );
						$wp_filesystem->put_contents( $upload_dir_and_name, $file_data, FS_CHMOD_FILE );
						unlink( $tmp_name );
						$input_field_value['tmp_name']   = addslashes( $upload_dir_and_name );
						$input_field_value['wcj_type']   = 'file';
						$input_field_value['wcj_uniqid'] = $unique_id;
					}
					if ( $item ) {
						$item->update_meta_data( '_wcj_product_input_fields_' . $this->scope . '_' . $i, $input_field_value );
					} else {
						wc_add_order_item_meta( $item_id, '_wcj_product_input_fields_' . $this->scope . '_' . $i, $input_field_value );
					}
				}
			}
		}
	}

endif;

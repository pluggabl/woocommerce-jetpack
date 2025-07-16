<?php
/**
 * Booster for WooCommerce - Module - Product Variation Swatches
 *
 * @version 7.2.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Product_Variation_Swatches' ) ) :
	/**
	 * WCJ_Product_Variation_Swatches.
	 *
	 * @version 6.0.0
	 */
	class WCJ_Product_Variation_Swatches extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 */
		public function __construct() {
			$this->id         = 'product_variation_swatches';
			$this->short_desc = __( 'Product Variation Swatches', 'woocommerce-jetpack' );
			$this->desc       = __( 'Product Variation Swatches', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-variation-swatches';
			parent::__construct();

			if ( $this->is_enabled() ) {

				add_action( 'woocommerce_after_add_attribute_fields', array( $this, 'wcj_add_column_on_product_attributes' ), 10 );
				add_action( 'woocommerce_after_edit_attribute_fields', array( $this, 'wcj_add_column_on_product_attributes' ), 10 );

				add_action( 'woocommerce_attribute_added', array( $this, 'wcj_save_column_on_product_attributes' ), 10 );
				add_action( 'woocommerce_attribute_updated', array( $this, 'wcj_save_column_on_product_attributes' ), 10 );

				add_action( 'admin_init', array( $this, 'wcj_add_product_taxonomy_meta' ), 10 );

				add_action( 'admin_enqueue_scripts', array( $this, 'wcj_backend_pvs_enqueue_scripts' ) );

				if ( wcj_is_frontend() ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'wcj_frontend_pvs_enqueue_scripts' ) );
					add_filter( 'woocommerce_dropdown_variation_attribute_options_html', array( $this, 'wcj_variation_attribute_options_html' ), 10, 2 );
				}
			}
		}

		/**
		 * Wcj_backend_pvs_enqueue_scripts.
		 *
		 * @version 6.0.0
		 */
		public function wcj_backend_pvs_enqueue_scripts() {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'wcj-backend-pvs-script', wcj_plugin_url() . '/includes/js/wcj-backend-pvs-script.js', array(), w_c_j()->version, true );
		}

		/**
		 * Wcj_frontend_pvs_enqueue_scripts.
		 *
		 * @version 6.0.0
		 */
		public function wcj_frontend_pvs_enqueue_scripts() {
			wp_enqueue_style( 'wcj-frontend-pvs-style', wcj_plugin_url() . '/includes/css/wcj-frontend-pvs-style.css', array(), w_c_j()->version );
			wp_enqueue_script( 'wcj-frontend-pvs-script', wcj_plugin_url() . '/includes/js/wcj-frontend-pvs-script.js', array(), w_c_j()->version, true );
		}

		/**
		 * Wcj_available_attributes_types.
		 *
		 * @version 6.0.0
		 * @param bool $type defines the type.
		 */
		public static function wcj_available_attributes_types( $type = false ) {
			$wcj_type = array();

			$wcj_type['wcj_color'] = array(
				'title'           => esc_html__( 'Color', 'woocommerce-jetpack' ),
				'output_function' => 'wcj_color_variation_attribute_options',
				'preview'         => 'wcj_color_variation_attribute_preview',
			);

			$wcj_type['wcj_image'] = array(
				'title'           => esc_html__( 'Image', 'woocommerce-jetpack' ),
				'output_function' => 'wcj_image_variation_attribute_options',
				'preview'         => 'wcj_image_variation_attribute_preview',
			);

			if ( $type ) {
				return isset( $wcj_type[ $type ] ) ? $wcj_type[ $type ] : array();
			}

			return $wcj_type;
		}

		/**
		 * Wcj_taxonomy_meta_fields.
		 *
		 * @version 6.0.0
		 * @param bool $field_id defines the field_id.
		 */
		public static function wcj_taxonomy_meta_fields( $field_id = false ) {
			$fields = array();

			$fields['wcj_color'] = array(
				array(
					'label' => esc_html__( 'Color', 'woocommerce-jetpack' ),
					'desc'  => esc_html__( 'Choose a color', 'woocommerce-jetpack' ),
					'id'    => 'wcj_product_attribute_color',
					'type'  => 'wcj_color',
				),
			);

			$fields['wcj_image'] = array(
				array(
					'label' => esc_html__( 'Image', 'woocommerce-jetpack' ),
					'desc'  => esc_html__( 'Choose an Image', 'woocommerce-jetpack' ),
					'id'    => 'wcj_product_attribute_image',
					'type'  => 'wcj_image',
				),
			);

			if ( $field_id ) {
				return isset( $fields[ $field_id ] ) ? $fields[ $field_id ] : array();
			}

			return $fields;
		}

		/**
		 * Wcj_get_wc_attribute_taxonomy.
		 *
		 * @version 6.0.2
		 * @param string $attribute_name defines the $attribute_name.
		 */
		public function wcj_get_wc_attribute_taxonomy( $attribute_name ) {
			global $wpdb;
			$attribute_name     = str_replace( 'pa_', '', wc_sanitize_taxonomy_name( $attribute_name ) );
			$attribute_taxonomy = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name='{$attribute_name}'" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $attribute_taxonomy;
		}

		/**
		 * Wcj_get_product_attribute_color.
		 *
		 * @version 6.0.0
		 * @param obj|array $term defines the $term.
		 */
		public function wcj_get_product_attribute_color( $term ) {
			if ( ! is_object( $term ) ) {
				return false;
			}
			return get_term_meta( $term->term_id, 'wcj_product_attribute_color', true );
		}

		/**
		 * Wcj_get_product_attribute_image.
		 *
		 * @version 6.0.0
		 * @param obj|array $term defines the $term.
		 */
		public function wcj_get_product_attribute_image( $term ) {
			if ( ! is_object( $term ) ) {
				return false;
			}

			return get_term_meta( $term->term_id, 'wcj_product_attribute_image', true );
		}

		/**
		 * Add_term_meta.
		 *
		 * @version 6.0.2
		 * @param string $taxonomy defines the taxonomy.
		 * @param string $post_type defines the post_type.
		 * @param array  $fields defines the fields.
		 */
		public function add_term_meta( $taxonomy, $post_type, $fields ) {
			return new WCJ_Product_Variation_Swatches_Term_Meta( $taxonomy, $post_type, $fields );
		}

		/**
		 * Wcj_save_column_on_product_attributes.
		 *
		 * @version 6.0.2
		 * @param int $id defines the id.
		 */
		public function wcj_save_column_on_product_attributes( $id ) {
			$wpnonce = isset( $_POST['wcj_attribute_type-nonce'] ) ? wp_verify_nonce( sanitize_key( $_POST['wcj_attribute_type-nonce'] ), 'wcj_attribute_type' ) : false;
			if ( is_admin() && $wpnonce && isset( $_POST['wcj_attribute_type'] ) ) {
				$option = "wcj_attribute_type_$id";
				update_option( $option, sanitize_text_field( wp_unslash( $_POST['wcj_attribute_type'] ) ) );
			}
		}

		/**
		 * Wcj_add_product_taxonomy_meta.
		 *
		 * @version 6.0.0
		 */
		public function wcj_add_product_taxonomy_meta() {

			$fields         = $this->wcj_taxonomy_meta_fields();
			$meta_added_for = apply_filters( 'wcj_product_taxonomy_meta_for', array_keys( $fields ) );

			if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {

				$attribute_taxonomies = wc_get_attribute_taxonomies();
				if ( $attribute_taxonomies ) {
					foreach ( $attribute_taxonomies as $tax ) {
						$product_attr      = wc_attribute_taxonomy_name( $tax->attribute_name );
						$product_attr_type = get_option( "wcj_attribute_type_$tax->attribute_id" );
						if ( in_array( $product_attr_type, $meta_added_for, true ) ) {
							$this->add_term_meta( $product_attr, 'product', $fields[ $product_attr_type ] );
						}
					}
				}
			}
		}

		/**
		 * Wcj_add_column_on_product_attributes.
		 *
		 * @version 6.0.2
		 */
		public function wcj_add_column_on_product_attributes() {

			$id    = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$value = $id ? get_option( "wcj_attribute_type_$id" ) : '';
			if ( 0 === $id ) {
				?>
			<div class="form-field">
				<label for="wcj_attribute_type"> <?php echo wp_kses_post( 'Booster Variation Swatches - Type', 'woocommerce-jetpack' ); ?> </label>
				<select name="wcj_attribute_type" id="wcj_attribute_type">
					<option value=""><?php echo wp_kses_post( 'Select', 'woocommerce-jetpack' ); ?></option>
					<?php
					foreach ( $this->wcj_available_attributes_types() as $key => $options ) {
						$checked = ( $key === $value ) ? 'checked' : '';
						echo wp_kses_post( '<option "' . $checked . '" value="' . $key . '">' . $options['title'] . '</option>' );
					}
					?>
				</select>
				<p class="description">
					<?php echo wp_kses_post( "Determines how this attribute's values are displayed like Color Box or As Image", 'woocommerce-jetpack' ); ?>
				</p>
				<p class="description">
					<?php echo wp_kses_post( 'Want to enable swatches for all your attributes, use button/label swatches, or automatically convert all dropdowns? Upgrade to <a href="https://booster.io/buy-booster/" target="_blank"> Booster Elite </a> for advanced swatch control!', 'woocommerce-jetpack' ); ?>
				</p>
			</div>
				<?php
			} else {
				?>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="my-field"><?php echo wp_kses_post( 'Booster Variation Swatches - Type', 'woocommerce-jetpack' ); ?></label>
				</th>
				<td>
					<select name="wcj_attribute_type" id="wcj_attribute_type">
						<option value=""><?php echo wp_kses_post( 'Select', 'woocommerce-jetpack' ); ?></option>
						<?php
						foreach ( $this->wcj_available_attributes_types() as $key => $options ) {
							$checked = ( $key === $value ) ? 'selected' : '';
							echo wp_kses_post( '<option ' . $checked . ' value="' . $key . '">' . $options['title'] . '</option>' );
						}
						?>
					</select>
					<p class="description">
						<?php echo wp_kses_post( "Determines how this attribute's values are displayed like Color Box, As Image, As Button", 'woocommerce-jetpack' ); ?>
					</p> 
				</td>
			</tr>
				<?php
			}
			wp_nonce_field( 'wcj_attribute_type', 'wcj_attribute_type-nonce' );
		}

		/**
		 * Wcj_variation_attribute_options_html.
		 *
		 * @version 6.0.0
		 * @param array $html defines the html.
		 * @param array $args defines the args.
		 */
		public function wcj_variation_attribute_options_html( $html, $args ) {

			if ( 'no' === wcj_get_option( 'wcj_is_show_product_variation_archive', 'yes' ) && is_archive() ) {
				return $html;
			}

			if ( apply_filters( 'default_wcj_variation_attribute_options_html', false, $args, $html ) ) {
				return $html;
			}

			$product = $args['product'];

			$args['show_option_none'] = esc_html__( 'Choose an option', 'woocommerce-jetpack' );

			$is_default_to_button = get_option( 'wcj_product_variation_defualt_to_button' ) === 'yes' ? 1 : 0;

			ob_start();

			if ( apply_filters( 'wcj_no_individual_settings', true, $args, $is_default_to_button ) ) {

				$attributes = $product->get_variation_attributes();
				$variations = $product->get_available_variations();

				$available_type_keys = array_keys( $this->wcj_available_attributes_types() );
				$available_types     = $this->wcj_available_attributes_types();
				$default             = true;

				foreach ( $available_type_keys as $type ) {
					if ( $this->wcj_wc_product_has_attribute_type( $type, $args['attribute'] ) ) {

						$output_function = apply_filters( 'wcj_variation_attribute_options_callback', $available_types[ $type ]['output_function'], $available_types, $type, $args, $html );
						$this->$output_function(
							apply_filters(
								'wcj_variation_attribute_options_args',
								wp_parse_args(
									$args,
									array(
										'options'    => $args['options'],
										'attribute'  => $args['attribute'],
										'product'    => $product,
										'selected'   => $args['selected'],
										'type'       => $type,
										'is_archive' => ( isset( $args['is_archive'] ) && $args['is_archive'] ),
									)
								)
							)
						);
						$default = false;
					}
				}

				if ( $default && $is_default_to_button ) {

					if ( $is_default_to_button ) {

						$this->wcj_default_button_variation_attribute_options(
							apply_filters(
								'wcj_variation_attribute_options_args',
								wp_parse_args(
									$args,
									array(
										'options'    => $args['options'],
										'attribute'  => $args['attribute'],
										'product'    => $product,
										'selected'   => $args['selected'],
										'is_archive' => ( isset( $args['is_archive'] ) && $args['is_archive'] ),
									)
								)
							)
						);
					} else {
						echo wp_kses_post( $html );
					}
				} elseif ( $default && ! $is_default_to_button ) {
					echo wp_kses_post( $html );
				}
			}

			$data = ob_get_clean();

			$html = apply_filters( 'wcj_variation_attribute_options_html', $data, $args, $is_default_to_button );

			return $html;
		}

		/**
		 * Wcj_wc_product_has_attribute_type.
		 *
		 * @version 6.0.0
		 * @param string $type defines the type.
		 * @param string $attribute_name defines the attribute_name.
		 */
		public function wcj_wc_product_has_attribute_type( $type, $attribute_name ) {

			$attributes           = wc_get_attribute_taxonomies();
			$attribute_name_clean = str_replace( 'pa_', '', wc_sanitize_taxonomy_name( $attribute_name ) );

			// Created Attribute.
			if ( 'pa_' === substr( $attribute_name, 0, 3 ) ) {

				$attribute = array_values(
					array_filter(
						$attributes,
						function ( $attribute ) use ( $type, $attribute_name_clean ) {
							return $attribute_name_clean === $attribute->attribute_name;
						}
					)
				);

				if ( ! empty( $attribute ) ) {
					$attribute = apply_filters( 'wcj_get_wc_attribute_taxonomy', $attribute[0], $attribute_name );
				} else {
					$attribute = $this->wcj_get_wc_attribute_taxonomy( $attribute_name );
				}

				$attribute_type = '';
				if ( isset( $attribute->attribute_id ) ) {
					$attribute_type = get_option( "wcj_attribute_type_$attribute->attribute_id" );
				}

				return apply_filters( 'wcj_wc_product_has_attribute_type', ( isset( $attribute_type ) && ( $attribute_type === $type ) ), $type, $attribute_name, $attribute );
			} else {
				return apply_filters( 'wcj_wc_product_has_attribute_type', false, $type, $attribute_name, null );
			}
		}

		/**
		 * Wcj_color_variation_attribute_options.
		 *
		 * @version 6.0.2
		 * @param array $args defines the args.
		 */
		public function wcj_color_variation_attribute_options( $args = array() ) {

			$args = wp_parse_args(
				$args,
				array(
					'options'          => false,
					'attribute'        => false,
					'product'          => false,
					'selected'         => false,
					'name'             => '',
					'id'               => '',
					'class'            => '',
					'type'             => '',
					'show_option_none' => esc_html__( 'Choose an option', 'woocommerce-jetpack' ),
				)
			);

			$type                  = $args['type'];
			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : wc_variation_attribute_name( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = $args['show_option_none'] ? true : false;
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : esc_html__( 'Choose an option', 'woocommerce' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

			if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
				$attributes = $product->get_variation_attributes();
				$options    = $attributes[ $attribute ];
			}

			if ( $product && taxonomy_exists( $attribute ) ) {
				echo '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . ' hide wcj_pvs_select wcj_pvs_select_type_' . esc_attr( $type ) . '" style="display:none" name="' . esc_attr( $name ) . '" data-attribute_name="' . esc_attr( wc_variation_attribute_name( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			} else {
				echo '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="' . esc_attr( wc_variation_attribute_name( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			}

			if ( $args['show_option_none'] ) {
				echo '<option value="">' . esc_html( $show_option_none_text ) . '</option>';
			}

			if ( ! empty( $options ) ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					// Get terms if this is a taxonomy - ordered. We need the names too.
					$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . wp_kses_post( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
						}
					}
				} else {
					foreach ( $options as $option ) {
						// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
						$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : wp_kses_post( selected( $args['selected'], $option, false ) );
						echo '<option value="' . esc_attr( $option ) . '" ' . esc_html( $selected ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
					}
				}
			}

			echo '</select>';

			$content = $this->wcj_variable_item( $type, $options, $args );

			echo wp_kses_post( $this->wcj_variable_items_wrapper( $content, $type, $args ) );
		}

		/**
		 * Wcj_image_variation_attribute_options.
		 *
		 * @version 6.0.2
		 * @param array $args defines the args.
		 */
		public function wcj_image_variation_attribute_options( $args = array() ) {

			$args = wp_parse_args(
				$args,
				array(
					'options'          => false,
					'attribute'        => false,
					'product'          => false,
					'selected'         => false,
					'name'             => '',
					'id'               => '',
					'class'            => '',
					'type'             => '',
					'show_option_none' => esc_html__( 'Choose an option', 'woocommerce-jetpack' ),
				)
			);

			$type                  = $args['type'];
			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : wc_variation_attribute_name( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = $args['show_option_none'] ? true : false;
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : esc_html__( 'Choose an option', 'woocommerce' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

			if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
				$attributes = $product->get_variation_attributes();
				$options    = $attributes[ $attribute ];
			}

			if ( $product && taxonomy_exists( $attribute ) ) {

				echo '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . ' hide wcj_pvs_select wcj_pvs_select_type_' . esc_attr( $type ) . '" style="display:none" name="' . esc_attr( $name ) . '" data-attribute_name="' . esc_attr( wc_variation_attribute_name( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			} else {
				echo '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="' . esc_attr( wc_variation_attribute_name( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			}

			if ( $args['show_option_none'] ) {
				echo '<option value="">' . esc_html( $show_option_none_text ) . '</option>';
			}

			if ( ! empty( $options ) ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					// Get terms if this is a taxonomy - ordered. We need the names too.
					$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . wp_kses_post( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
						}
					}
				} else {
					foreach ( $options as $option ) {
						// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
						$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
						echo '<option value="' . esc_attr( $option ) . '" ' . esc_html( $selected ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
					}
				}
			}

			echo '</select>';

			$content = wp_kses_post( $this->wcj_variable_item( $type, $options, $args ) );

			echo wp_kses_post( $this->wcj_variable_items_wrapper( $content, $type, $args ) );
		}

		/**
		 * Wcj_button_variation_attribute_options.
		 *
		 * @version 6.0.2
		 * @param array $args defines the args.
		 */
		public function wcj_button_variation_attribute_options( $args = array() ) {

			$args = wp_parse_args(
				$args,
				array(
					'options'          => false,
					'attribute'        => false,
					'product'          => false,
					'selected'         => false,
					'name'             => '',
					'id'               => '',
					'class'            => '',
					'type'             => '',
					'show_option_none' => esc_html__( 'Choose an option', 'woocommerce-jetpack' ),
				)
			);

			$type                  = $args['type'];
			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : wc_variation_attribute_name( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = $args['show_option_none'] ? true : false;
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : esc_html__( 'Choose an option', 'woocommerce' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

			if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
				$attributes = $product->get_variation_attributes();
				$options    = $attributes[ $attribute ];
			}

			if ( $product && taxonomy_exists( $attribute ) ) {
				echo '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . ' hide wcj_pvs_select wcj_pvs_select_type_' . esc_attr( $type ) . '" style="display:none" name="' . esc_attr( $name ) . '" data-attribute_name="' . esc_attr( wc_variation_attribute_name( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			} else {
				echo '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="' . esc_attr( wc_variation_attribute_name( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			}

			if ( $args['show_option_none'] ) {
				echo '<option value="">' . esc_html( $show_option_none_text ) . '</option>';
			}

			if ( ! empty( $options ) ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					// Get terms if this is a taxonomy - ordered. We need the names too.
					$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
						}
					}
				} else {
					foreach ( $options as $option ) {
						// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
						$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
						echo '<option value="' . esc_attr( $option ) . '" ' . esc_html( $selected ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
					}
				}
			}

			echo '</select>';

			$content = $this->wcj_variable_item( $type, $options, $args );

			echo wp_kses_post( $this->wcj_variable_items_wrapper( $content, $type, $args ) );
		}

		/**
		 * Wcj_default_button_variation_attribute_options.
		 *
		 * @version 6.0.0
		 * @param array $args defines the args.
		 */
		public function wcj_default_button_variation_attribute_options( $args = array() ) {

			$args = wp_parse_args(
				$args,
				array(
					'options'          => false,
					'attribute'        => false,
					'product'          => false,
					'selected'         => false,
					'name'             => '',
					'id'               => '',
					'class'            => '',
					'type'             => '',
					'assigned'         => '',
					'show_option_none' => esc_html__( 'Choose an option', 'woocommerce-jetpack' ),
				)
			);

			$type                  = $args['type'] ? $args['type'] : 'button';
			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : wc_variation_attribute_name( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = $args['show_option_none'] ? true : false;
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : esc_html__( 'Choose an option', 'woocommerce' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

			if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
				$attributes = $product->get_variation_attributes();
				$options    = $attributes[ $attribute ];
			}

			if ( $product ) {
				echo '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . ' hide wcj_pvs_select wcj_pvs_select_type_' . wp_kses_post( $type ) . '" style="display:none" name="' . esc_attr( $name ) . '" data-attribute_name="' . esc_attr( wc_variation_attribute_name( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			}

			if ( $args['show_option_none'] ) {
				echo '<option value="">' . esc_html( $show_option_none_text ) . '</option>';
			}

			if ( ! empty( $options ) ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					// Get terms if this is a taxonomy - ordered. We need the names too.
					$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
						}
					}
				} else {
					foreach ( $options as $option ) {
						// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
						$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
						echo '<option value="' . esc_attr( $option ) . '" ' . wp_kses_post( $selected ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
					}
				}
			}

			echo '</select>';

			$content = $this->wcj_default_variable_item( $type, $options, $args );

			echo wp_kses_post( $this->wcj_variable_items_wrapper( $content, $type, $args ) );
		}

		/**
		 * Wcj_variable_item.
		 *
		 * @version 6.0.0
		 * @param string $type defines the type.
		 * @param string $options defines the options.
		 * @param array  $args defines the args.
		 * @param array  $saved_attribute defines the saved_attribute.
		 */
		public function wcj_variable_item( $type, $options, $args, $saved_attribute = array() ) {

			$product   = $args['product'];
			$attribute = $args['attribute'];
			$data      = '';

			if ( ! empty( $options ) ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
					$name  = uniqid( wc_variation_attribute_name( $attribute ) );
					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {

							// aria-checked="false".
							$option = esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) );

							$is_selected    = ( sanitize_title( $args['selected'] ) === $term->slug );
							$selected_class = $is_selected ? 'selected' : '';
							$tooltip        = trim( apply_filters( 'wcj_variable_item_tooltip', $option, $term, $args ) );

							$tooltip_html_attr       = ! empty( $tooltip ) ? sprintf( ' data-tooltip="%s"', esc_attr( $tooltip ) ) : '';
							$screen_reader_html_attr = $is_selected ? ' aria-checked="true"' : ' aria-checked="false"';

							if ( wp_is_mobile() ) {
								$tooltip_html_attr .= ! empty( $tooltip ) ? ' tabindex="2"' : '';
							}

							$defualt_width  = '';
							$defualt_height = '';
							$defualt_style  = '';

							if ( 'wcj_color' === $type ) {
								$defualt_width  = get_option( 'wcj_product_color_variation_item_width' );
								$defualt_height = get_option( 'wcj_product_color_variation_item_height' );
								$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
							} elseif ( 'wcj_image' === $type ) {
								$defualt_width  = get_option( 'wcj_product_image_variation_item_width' );
								$defualt_height = get_option( 'wcj_product_image_variation_item_height' );
								$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
							} elseif ( 'wcj_button' === $type ) {
								$defualt_width  = get_option( 'wcj_product_button_variation_item_width' );
								$defualt_height = get_option( 'wcj_product_button_variation_item_height' );
								$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
							}

							$data .= sprintf( '<li %1$s class="wcj_pvs_tooltip variable-item %2$s-variable-item %2$s-variable-item-%3$s %4$s" data-value="%3$s" role="radio" tabindex="0" data-type="%2$s" %6$s><div class="variable-item-contents">', $screen_reader_html_attr . $tooltip_html_attr, esc_attr( $type ), esc_attr( $term->slug ), esc_attr( $selected_class ), $option, $defualt_style );

							switch ( $type ) :
								case 'wcj_color':
									$color = sanitize_hex_color( $this->wcj_get_product_attribute_color( $term ) );
									$data .= sprintf( '<span class="variable-item-span variable-item-span-%s" style="background-color:%s;"></span>', esc_attr( $type ), esc_attr( $color ) );
									break;

								case 'wcj_image':
									$attachment_id = apply_filters( 'wcj_product_global_attribute_image_id', absint( $this->wcj_get_product_attribute_image( $term ) ), $term, $args );
									$image_size    = 100;
									$image         = wp_get_attachment_image_src( $attachment_id, apply_filters( 'wcj_product_attribute_image_size', $image_size, $attribute, $product ) );

									if ( ! empty( $image ) ) {
										$data .= sprintf( '<img class="variable-item-image" aria-hidden="true" alt="%s" src="%s" width="%d" height="%d" />', esc_attr( $option ), esc_url( $image[0] ), esc_attr( $image[1] ), esc_attr( $image[2] ) );
									} else {

										$image = wcj_plugin_url() . '/assets/images/woocommerce-placeholder.png';
										$data .= sprintf( '<img class="variable-item-image" aria-hidden="true" alt="%s" src="%s" width="%d" height="%d" />', esc_attr( $option ), esc_url( $image ), '', '' );
									}

									break;

								case 'wcj_button':
									$data .= sprintf( '<span class="variable-item-span variable-item-span-%s">%s</span>', esc_attr( $type ), $option );
									break;

								default:
									$data .= apply_filters( 'wcj_variable_default_item_content', '', $term, $args, $saved_attribute );
									break;
							endswitch;
							$data .= '</div>';

							$is_default_lable = get_option( 'wcj_is_show_product_variation_lable' ) === 'no' ? 0 : 1;

							if ( 1 === $is_default_lable ) {
								$data .= '<span class="wcj_pvs_tooltiptext">' . $option . '</span>';
							}

							$data .= '</li>';
						}
					}
				}
			}

			return apply_filters( 'wcj_variable_item', $data, $type, $options, $args, $saved_attribute );
		}

		/**
		 * Wcj_variable_items_wrapper.
		 *
		 * @version 6.0.0
		 * @param string $contents defines the contents.
		 * @param string $type defines the type.
		 * @param array  $args defines the args.
		 * @param array  $saved_attribute defines the saved_attribute.
		 */
		public function wcj_variable_items_wrapper( $contents, $type, $args, $saved_attribute = array() ) {

			$attribute = $args['attribute'];
			$options   = $args['options'];

			$css_classes = apply_filters( 'wcj_variable_items_wrapper_class', array( "{$type}-variable-wrapper" ), $type, $args, $saved_attribute );

			$clear_on_reselect = '';

			array_push( $css_classes, $clear_on_reselect );

			$wcj_product_pvs_attr_display_style = get_option( 'wcj_product_pvs_attr_display_style' );

			// <div aria-live="polite" aria-atomic="true" class="screen-reader-text">%1$s: <span data-default=""></span></div>
			$data = sprintf( '<ul role="radiogroup" aria-label="%1$s"  class="wcj_variable_items_wrapper variable-items-wrapper %2$s %6$s" data-attribute_name="%3$s" data-attribute_values="%4$s">%5$s</ul>', esc_attr( wc_attribute_label( $attribute ) ), trim( implode( ' ', array_unique( $css_classes ) ) ), esc_attr( wc_variation_attribute_name( $attribute ) ), wc_esc_json( wp_json_encode( array_values( $options ) ) ), $contents, $wcj_product_pvs_attr_display_style );

			return apply_filters( 'wcj_variable_items_wrapper', $data, $contents, $type, $args, $saved_attribute );
		}

		/**
		 * Wcj_default_variable_item.
		 *
		 * @version 6.0.2
		 * @param string $type defines the type.
		 * @param string $options defines the options.
		 * @param array  $args defines the args.
		 * @param array  $saved_attribute defines the saved_attribute.
		 */
		public function wcj_default_variable_item( $type, $options, $args, $saved_attribute = array() ) {

			$product   = $args['product'];
			$attribute = $args['attribute'];
			$assigned  = $args['assigned'];

			$is_archive           = ( isset( $args['is_archive'] ) && $args['is_archive'] );
			$show_archive_tooltip = '';

			$data = '';

			if ( ! empty( $options ) ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
					$name  = uniqid( wc_variation_attribute_name( $attribute ) );
					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {

							$option = esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) );

							$is_selected = ( sanitize_title( $args['selected'] ) === $term->slug );

							$selected_class = $is_selected ? 'selected' : '';
							$tooltip        = trim( apply_filters( 'wcj_variable_item_tooltip', $option, $term, $args ) );

							if ( $is_archive && ! $show_archive_tooltip ) {
								$tooltip = false;
							}

							$tooltip_html_attr       = ! empty( $tooltip ) ? sprintf( ' data-tooltip="%s"', esc_attr( $tooltip ) ) : '';
							$screen_reader_html_attr = $is_selected ? ' aria-checked="true"' : ' aria-checked="false"';

							if ( wp_is_mobile() ) {
								$tooltip_html_attr .= ! empty( $tooltip ) ? ' tabindex="2"' : '';
							}

							$type = isset( $assigned[ $term->slug ] ) ? $assigned[ $term->slug ]['type'] : $type;

							if ( ! isset( $assigned[ $term->slug ] ) || empty( $assigned[ $term->slug ]['image_id'] ) ) {
								$type = 'button';
							}

							$defualt_width  = '';
							$defualt_height = '';
							$defualt_style  = '';

							if ( 'wcj_color' === $type ) {
								$defualt_width  = get_option( 'wcj_product_color_variation_item_width' );
								$defualt_height = get_option( 'wcj_product_color_variation_item_height' );
								$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
							} elseif ( 'wcj_image' === $type ) {
								$defualt_width  = get_option( 'wcj_product_image_variation_item_width' );
								$defualt_height = get_option( 'wcj_product_image_variation_item_height' );
								$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
							} elseif ( 'wcj_button' === $type ) {
								$defualt_width  = get_option( 'wcj_product_button_variation_item_width' );
								$defualt_height = get_option( 'wcj_product_button_variation_item_height' );
								$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
							}

							$data .= sprintf( '<li %1$s class="wcj_pvs_tooltip variable-item %2$s-variable-item %2$s-variable-item-%3$s %4$s"  data-value="%3$s" role="radio" tabindex="0" %6$s><div class="variable-item-contents" data-type="%2$s">', $screen_reader_html_attr . $tooltip_html_attr, esc_attr( $type ), esc_attr( $term->slug ), esc_attr( $selected_class ), $option, $defualt_style );

							switch ( $type ) :

								case 'button':
									$data .= sprintf( '<span class="variable-item-span variable-item-span-%s">%s</span>', esc_attr( $type ), $option );
									break;

								case 'wcj_button':
									$data .= sprintf( '<span class="variable-item-span variable-item-span-%s">%s</span>', esc_attr( $type ), $option );
									break;

								default:
									$data .= apply_filters( 'wcj_variable_default_item_content', '', $term, $args, $saved_attribute );
									break;
							endswitch;
							$data .= '</div>';

							$is_default_lable = 'no' === get_option( 'wcj_is_show_product_variation_lable' ) ? 0 : 1;

							if ( 1 === $is_default_lable ) {
								$data .= '<span class="wcj_pvs_tooltiptext">' . $option . '</span>';
							}

							$data .= '</li>';
						}
					}
				} else {

					foreach ( $options as $option ) {
						// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.

						$option = esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) );

						$is_selected = ( sanitize_title( $option ) === sanitize_title( $args['selected'] ) );

						$selected_class = $is_selected ? 'selected' : '';
						$tooltip        = trim( apply_filters( 'wcj_variable_item_tooltip', $option, $options, $args ) );

						if ( $is_archive && ! $show_archive_tooltip ) {
							$tooltip = false;
						}

						$tooltip_html_attr       = ! empty( $tooltip ) ? sprintf( 'data-tooltip="%s"', esc_attr( $tooltip ) ) : '';
						$screen_reader_html_attr = $is_selected ? ' aria-checked="true"' : ' aria-checked="false"';

						if ( wp_is_mobile() ) {
							$tooltip_html_attr .= ! empty( $tooltip ) ? ' tabindex="2"' : '';
						}

						$type = isset( $assigned[ $option ] ) ? $assigned[ $option ]['type'] : $type;

						if ( ! isset( $assigned[ $option ] ) || empty( $assigned[ $option ]['image_id'] ) ) {
							$type = 'button';
						}

						$defualt_width  = '';
						$defualt_height = '';
						$defualt_style  = '';

						if ( 'wcj_color' === $type ) {
							$defualt_width  = get_option( 'wcj_product_color_variation_item_width' );
							$defualt_height = get_option( 'wcj_product_color_variation_item_height' );
							$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
						} elseif ( 'wcj_image' === $type ) {
							$defualt_width  = get_option( 'wcj_product_image_variation_item_width' );
							$defualt_height = get_option( 'wcj_product_image_variation_item_height' );
							$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
						} elseif ( 'wcj_button' === $type ) {
							$defualt_width  = get_option( 'wcj_product_button_variation_item_width' );
							$defualt_height = get_option( 'wcj_product_button_variation_item_height' );
							$defualt_style  = 'style="width:' . $defualt_width . ';height:' . $defualt_height . ';"';
						}

						$data .= sprintf( '<li %1$s class="wcj_pvs_tooltip variable-item %2$s-variable-item %2$s-variable-item-%3$s %4$s" data-value="%3$s" role="radio" tabindex="0" data-type="%2$s" %6$s><div class="variable-item-contents">', $screen_reader_html_attr . $tooltip_html_attr, esc_attr( $type ), esc_attr( $option ), esc_attr( $selected_class ), esc_html( $option ), $defualt_style );

						switch ( $type ) :

							case 'button':
								$data .= sprintf( '<span class="variable-item-span variable-item-span-%s">%s</span>', esc_attr( $type ), esc_html( $option ) );
								break;

							case 'wcj_button':
								$data .= sprintf( '<span class="variable-item-span variable-item-span-%s">%s</span>', esc_attr( $type ), esc_html( $option ) );
								break;

							default:
								$data .= apply_filters( 'wcj_variable_default_item_content', '', $option, $args, array() );
								break;
						endswitch;
						$data .= '</div>';

						$is_default_lable = 'no' === get_option( 'wcj_is_show_product_variation_lable' ) ? 0 : 1;

						if ( 1 === $is_default_lable ) {
							$data .= '<span class="wcj_pvs_tooltiptext">' . $option . '</span>';
						}

						$data .= '</li>';
					}
				}
			}

			return apply_filters( 'wcj_default_variable_item', $data, $type, $options, $args, array() );
		}
	}

endif;

return new WCJ_Product_Variation_Swatches();

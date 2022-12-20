<?php
/**
 * Booster for WooCommerce - Module - Product Price by Formula
 *
 * @version 6.0.1
 * @since   2.5.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_Price_By_Formula' ) ) :
	/**
	 * WCJ_Product_Price_By_Formula.
	 */
	class WCJ_Product_Price_By_Formula extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 * @since   2.5.1
		 * @todo    use WC math library instead of `PHPMathParser`
		 */
		public function __construct() {

			$this->id         = 'product_price_by_formula';
			$this->short_desc = __( 'Product Price by Formula', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set formula for automatic product price calculation (Available per product in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Set formula for automatic product price calculation.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-price-formula';
			parent::__construct();

			if ( $this->is_enabled() ) {
				require_once wcj_free_plugin_path() . '/includes/lib/PHPMathParser/Math.php';

				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
				if (
				( wcj_is_frontend() && 'yes' === wcj_get_option( 'wcj_product_price_by_formula_admin_scope', 'yes' ) ) ||
				( 'no' === wcj_get_option( 'wcj_product_price_by_formula_admin_scope', 'yes' ) && ( wcj_is_frontend() || is_admin() ) ) ||
				( $wpnonce && isset( $_GET['wcj_create_products_xml'] ) )
				) {
					wcj_add_change_price_hooks( $this, wcj_get_module_price_hooks_priority( 'product_price_by_formula' ), false );
				}

				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );

				if ( 'yes' === wcj_get_option( 'wcj_product_price_by_formula_woo_booking_plugin', 'no' ) ) {
					add_filter( 'woocommerce_bookings_calculated_booking_cost_success_output', array( $this, 'booking_price_change' ), PHP_INT_MAX, 10, 3 );
				}

				if ( 'yes' === wcj_get_option( 'wcj_product_price_by_formula_woo_booking_promotional', 'no' ) ) {
					add_filter( 'woocommerce_get_price_html', array( $this, 'promotional_price_showing' ), PHP_INT_MAX, 10, 2 );
				}
				$this->rounding           = wcj_get_option( 'wcj_product_price_by_formula_rounding', 'no_rounding' );
				$this->rounding_precision = wcj_get_option( 'wcj_product_price_by_formula_rounding_precision', 0 );
			}
		}

		/**
		 * Booking_price_change.
		 *
		 * @version 5.4.7
		 * @since   5.4.7
		 * @param mixed          $output defines the output.
		 * @param int            $display_price defines the display_price.
		 * @param string | array $_product defines the _product.
		 */
		public function booking_price_change( $output, $display_price, $_product ) {
			if ( $this->is_price_by_formula_product( $_product ) && '' !== $display_price ) {
				$_product_id = wcj_get_product_id_or_variation_parent_id( $_product );
				$saved_price = $this->get_saved_price( $_product_id );
				if ( false !== $saved_price ) {
					return $saved_price;
				}
				$is_per_product = ( 'per_product' === get_post_meta( $_product_id, '_wcj_product_price_by_formula_calculation', true ) );
				$the_formula    = ( $is_per_product )
				? get_post_meta( $_product_id, '_wcj_product_price_by_formula_eval', true )
				: wcj_get_option( 'wcj_product_price_by_formula_eval', '' );
				$the_formula    = do_shortcode( $the_formula );
				if ( '' !== $the_formula ) {
					$total_params = ( $is_per_product )
					? get_post_meta( $_product_id, '_wcj_product_price_by_formula_total_params', true )
					: wcj_get_option( 'wcj_product_price_by_formula_total_params', 1 );
					if ( $total_params > 0 ) {
						$the_current_filter = current_filter();
						if ( 'woocommerce_get_price_including_tax' === $the_current_filter || 'woocommerce_get_price_excluding_tax' === $the_current_filter ) {
							$display_price = wcj_get_product_display_price( $_product );
							$this->save_price( $_product_id, $display_price );
							return $display_price;
						}
						$math = new WCJ_Math();
						$math->registerVariable( 'x', $display_price );
						for ( $i = 1; $i <= $total_params; $i++ ) {
							$the_param = ( $is_per_product )
							? get_post_meta( $_product_id, '_wcj_product_price_by_formula_param_' . $i, true )
							: wcj_get_option( 'wcj_product_price_by_formula_param_' . $i, '' );
							$the_param = $this->add_product_id_param( $the_param, $_product );
							$the_param = do_shortcode( $the_param );
							if ( '' !== $the_param ) {
								$math->registerVariable( 'p' . $i, $the_param );
							}
						}
						$the_formula = str_replace( 'x', '$x', $the_formula );
						$the_formula = str_replace( 'p', '$p', $the_formula );
						try {
							$display_price = $math->evaluate( $the_formula );
						} catch ( Exception $e ) {
							if ( $output_errors ) {
								echo '<p style="color:red;">' . wp_kses_post( 'Error in formula', 'woocommerce-jetpack' ) . ': ' . wp_kses_post( $e->getMessage() ) . '</p>';
							}
						}
						if ( 'no_rounding' !== $this->rounding ) {
							$display_price = wcj_round( $display_price, $this->rounding_precision, $this->rounding );
						}
						$this->save_price( $_product_id, $display_price );
					}
				}
			}

			$output = apply_filters( 'woocommerce_bookings_booking_cost_string', __( 'Booking cost', 'woocommerce-jetpack' ), $_product ) . ': <strong>' . wc_price( $display_price ) . $price_suffix . '</strong>';
			return $output;
		}

		/**
		 * Promotional_price_showing.
		 *
		 * @version 5.4.7
		 * @since   5.4.7
		 * @param int            $price defines the price.
		 * @param string | array $_product defines the _product.
		 */
		public function promotional_price_showing( $price, $_product ) {
			$price_html = $_product->get_price();
			/* translators: %s: translation added */
			$price_html = sprintf( __( 'From: %s', 'woocommerce-jetpack' ), wc_price( $price_html ) );
			return $price_html;
		}

		/**
		 * Add_reset_settings_button.
		 *
		 * @version 5.3.0
		 * @since   5.3.0
		 *
		 * @param array $settings defines the settings.
		 *
		 * @return array
		 */
		public function add_reset_settings_button( $settings ) {
			$settings = parent::add_reset_settings_button( $settings );
			$results  = wp_list_filter(
				$settings,
				array(
					'type' => 'title',
					'id'   => 'wcj_product_price_by_formula_reset_settings_options',
				)
			);
			if ( is_array( $results ) && $results > 0 ) {
				$message      = apply_filters( 'booster_message', '', 'desc' );
				$new_settings = array(
					array(
						'title'             => __( 'Reset products', 'woocommerce-jetpack' ),
						'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
						'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
						'type'              => 'checkbox',
						'id'                => 'wcj_product_price_by_formula_reset_products',
						'default'           => 'no',
						'desc_tip'          => __( 'If enabled will also reset formula related settings on products when <code>Reset Settings</code> button is pressed.', 'woocommerce-jetpack' ) . '<br />' . __( 'It\'s necessary to <code>Save changes</code> first with the option enabled so the <code>Reset settings</code> can be pressed.', 'woocommerce-jetpack' ),
					),
				);
				reset( $results );
				$first_key = key( $results );
				array_splice( $settings, $first_key + 1, 0, $new_settings ); // splice in at position 3.
			}
			return $settings;
		}

		/**
		 * Reset_settings.
		 *
		 * @version 6.0.0
		 * @since   5.3.0
		 */
		public function reset_settings() {
			$wpnonce = isset( $_GET['wcj_reset_settings-product_price_by_formula-nonce'] ) ? wp_verify_nonce( sanitize_key( $_GET['wcj_reset_settings-product_price_by_formula-nonce'] ), 'wcj_reset_settings' ) : false;
			if (
			$wpnonce
			&& isset( $_GET['wcj_reset_settings'] )
			&& $this->id === $_GET['wcj_reset_settings']
			&& wcj_is_user_role( 'administrator' )
			&& ! isset( $_POST['save'] )
			&& 'yes' === wcj_get_option( 'wcj_product_price_by_formula_reset_products', 'no' )
			) {
				global $wpdb;
				$prefix = '_wcj_product_price_by_formula';
				$wpdb->query( $wpdb->prepare( 'delete from ' . $wpdb->postmeta . ' where meta_key REGEXP %s', $prefix ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
			parent::reset_settings(); // TODO: Change the autogenerated stub.
		}

		/**
		 * Change_price_grouped.
		 *
		 * @version 2.7.0
		 * @since   2.5.0
		 * @param int   $price defines the price.
		 * @param int   $qty defines the qty.
		 * @param array $_product defines the _product.
		 */
		public function change_price_grouped( $price, $qty, $_product ) {
			if ( $_product->is_type( 'grouped' ) ) {
				foreach ( $_product->get_children() as $child_id ) {
					$the_price   = get_post_meta( $child_id, '_price', true );
					$the_product = wc_get_product( $child_id );
					$the_price   = wcj_get_product_display_price( $the_product, $the_price, 1 );
					if ( $the_price === $price ) {
						return $this->change_price( $price, $the_product );
					}
				}
			}
			return $price;
		}

		/**
		 * Adds product id param on shortcodes.
		 *
		 * @version 4.1.0
		 * @since   4.1.0
		 *
		 * @param string $the_param defines the the_param.
		 * @param array  $_product defines the _product.
		 *
		 * @return string
		 */
		public function add_product_id_param( $the_param, $_product ) {
			if (
			preg_match( '/^\[.*\]$/', $the_param ) &&
			! preg_match( '/product_id=[\'"]?\d+[\'"]?/', $the_param )
			) {
				$product_id = $_product->get_id();
				$the_param  = preg_replace( '/\[[^\]]*/', "$0 product_id='{$product_id}'", $the_param );
			}
			return $the_param;
		}

		/**
		 * Save_price.
		 *
		 * @version 6.0.1
		 * @since   5.1.1
		 *
		 * @param int $product_id defines the product_id.
		 * @param int $price defines the price.
		 *
		 * @return bool
		 */
		public function save_price( $product_id, $price ) {
			if ( 'no' === wcj_get_option( 'wcj_product_price_by_formula_save_prices', 'no' ) ) {
				return false;
			}
			$filter = current_filter();
			if ( '' === $filter ) {
				$filter = 'wcj_filter__none';
			}
			w_c_j()->all_modules['product_price_by_formula']->calculated_products_prices[ $product_id ][ $filter ] = $price;
		}

		/**
		 * Get_saved_price
		 *
		 * @version 6.0.1
		 * @since   5.1.1
		 *
		 * @param int $product_id defines the product_id.
		 *
		 * @return bool
		 */
		public function get_saved_price( $product_id ) {
			if ( 'no' === wcj_get_option( 'wcj_product_price_by_formula_save_prices', 'no' ) ) {
				return false;
			}
			$filter = current_filter();
			if ( '' === $filter ) {
				$filter = 'wcj_filter__none';
			}
			if ( isset( w_c_j()->all_modules['product_price_by_formula']->calculated_products_prices[ $product_id ][ $filter ] ) ) {
				return w_c_j()->all_modules['product_price_by_formula']->calculated_products_prices[ $product_id ][ $filter ];
			}
			return false;
		}

		/**
		 * Change_price.
		 *
		 * @version 5.4.0
		 * @since   2.5.0
		 * @param int   $price defines the price.
		 * @param array $_product defines the _product.
		 * @param bool  $output_errors defines the output_errors.
		 */
		public function change_price( $price, $_product, $output_errors = false ) {
			if ( 'yes' === wcj_get_option( 'wcj_product_price_by_formula_admin_quick_edit_product_scope', 'no' ) ) {
				if ( wcj_is_admin_product_quick_edit_page() ) {
					return $price;
				}
			}
			if ( $this->is_price_by_formula_product( $_product ) && '' !== $price ) {
				$_product_id = wcj_get_product_id_or_variation_parent_id( $_product );
				$saved_price = $this->get_saved_price( $_product_id );
				if ( false !== $saved_price ) {
					return $saved_price;
				}
				$is_per_product = ( 'per_product' === get_post_meta( $_product_id, '_wcj_product_price_by_formula_calculation', true ) );
				$the_formula    = ( $is_per_product )
				? get_post_meta( $_product_id, '_wcj_product_price_by_formula_eval', true )
				: wcj_get_option( 'wcj_product_price_by_formula_eval', '' );
				$the_formula    = do_shortcode( $the_formula );
				if ( '' !== $the_formula ) {
					$total_params = ( $is_per_product )
					? get_post_meta( $_product_id, '_wcj_product_price_by_formula_total_params', true )
					: wcj_get_option( 'wcj_product_price_by_formula_total_params', 1 );
					if ( $total_params > 0 ) {
						$the_current_filter = current_filter();
						if ( 'woocommerce_get_price_including_tax' === $the_current_filter || 'woocommerce_get_price_excluding_tax' === $the_current_filter ) {
							$price = wcj_get_product_display_price( $_product );
							$this->save_price( $_product_id, $price );
							return $price;
						}
						$math = new WCJ_Math();
						$math->registerVariable( 'x', $price );
						for ( $i = 1; $i <= $total_params; $i++ ) {
							$the_param = ( $is_per_product )
							? get_post_meta( $_product_id, '_wcj_product_price_by_formula_param_' . $i, true )
							: wcj_get_option( 'wcj_product_price_by_formula_param_' . $i, '' );
							$the_param = $this->add_product_id_param( $the_param, $_product );
							$the_param = do_shortcode( $the_param );
							if ( '' !== $the_param ) {
								$math->registerVariable( 'p' . $i, $the_param );
							}
						}
						$the_formula = str_replace( 'x', '$x', $the_formula );
						$the_formula = str_replace( 'p', '$p', $the_formula );
						try {
							$price = $math->evaluate( $the_formula );
						} catch ( Exception $e ) {
							if ( $output_errors ) {
								echo '<p style="color:red;">' . wp_kses_post( 'Error in formula', 'woocommerce-jetpack' ) . ': ' . wp_kses_post( $e->getMessage() ) . '</p>';
							}
						}
						if ( 'no_rounding' !== $this->rounding ) {
							$price = wcj_round( $price, $this->rounding_precision, $this->rounding );
						}
						$this->save_price( $_product_id, $price );
					}
				}
			}
			return $price;
		}

		/**
		 * Get_variation_prices_hash.
		 *
		 * @version 3.6.0
		 * @since   2.5.0
		 * @param array         $price_hash defines the price_hash.
		 * @param array         $_product defines the _product.
		 * @param string | bool $display defines the display.
		 */
		public function get_variation_prices_hash( $price_hash, $_product, $display ) {
			if ( $this->is_price_by_formula_product( $_product ) ) {
				$the_formula  = wcj_get_option( 'wcj_product_price_by_formula_eval', '' );
				$total_params = get_post_meta( wcj_get_product_id_or_variation_parent_id( $_product ), '_wcj_product_price_by_formula_total_params', true );
				$the_params   = array();
				for ( $i = 1; $i <= $total_params; $i++ ) {
					$the_params[] = wcj_get_option( 'wcj_product_price_by_formula_param_' . $i, '' );
				}
				$price_hash['wcj_price_by_formula'] = array(
					'formula'            => $the_formula,
					'total_params'       => $total_params,
					'params'             => $the_params,
					'rounding'           => $this->rounding,
					'rounding_precision' => $this->rounding_precision,
				);
			}
			return $price_hash;
		}

		/**
		 * Save_meta_box_value.
		 *
		 * @version 5.6.8
		 * @since   2.5.0
		 * @param string $option_value defines the option_value.
		 * @param string $option_name defines the option_name.
		 * @param int    $module_id defines the module_id.
		 */
		public function save_meta_box_value( $option_value, $option_name, $module_id ) {
			if ( true === apply_filters( 'booster_option', false, true ) ) {
				return $option_value;
			}
			if ( 'no' === $option_value ) {
				return $option_value;
			}
			if ( $this->id === $module_id && 'wcj_product_price_by_formula_enabled' === $option_name ) {
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'meta_key'       => '_wcj_product_price_by_formula_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'post__not_in'   => array( get_the_ID() ),
				);
				$loop = new WP_Query( $args );
				$c    = $loop->found_posts + 1;
				if ( $c >= 2 ) {
					add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
					return 'no';
				}
			}
			return $option_value;
		}

		/**
		 * Add_notice_query_var.
		 *
		 * @version 5.6.8
		 * @since   2.5.0
		 * @param string $location defines the location.
		 */
		public function add_notice_query_var( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
			$query_arg = array(
				'wcj_product_price_by_formula_admin_notice' => true,
				'wcj_product_price_by_formula_admin_notice-nonce' => wp_create_nonce( 'wcj_product_price_by_formula_admin_notice' ),
			);
			return esc_url_raw( add_query_arg( $query_arg, $location ) );
		}

		/**
		 * Admin_notices.
		 *
		 * @version 5.6.8
		 * @since   2.5.0
		 */
		public function admin_notices() {
			$wpnonce = isset( $_REQUEST['wcj_product_price_by_formula_admin_notice-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_product_price_by_formula_admin_notice-nonce'] ), 'wcj_product_price_by_formula_admin_notice' ) : false;
			if ( ! $wpnonce || ! isset( $_GET['wcj_product_price_by_formula_admin_notice'] ) ) {
				return;
			}
			?><div class="error"><p>
			<?php
			echo '<div class="message">'
				. wp_kses_post( 'Booster: Free plugin\'s version is limited to only one price by formula product enabled at a time. You will need to get <a href="https://booster.io/buy-booster/" target="_blank">Booster Plus</a> to add unlimited number of price by formula products.', 'woocommerce-jetpack' )
				. '</div>';
			?>
		</p></div>
			<?php
		}

		/**
		 * Is_price_by_formula_product.
		 *
		 * @version 2.7.0
		 * @since   2.5.0
		 * @param array $_product defines the _product.
		 */
		public function is_price_by_formula_product( $_product ) {
			return (
			'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_price_by_formula_enable_for_all_products', 'no' ) ) ||
			'yes' === get_post_meta( wcj_get_product_id_or_variation_parent_id( $_product ), '_wcj_product_price_by_formula_enabled', true )
			);
		}

		/**
		 * Create_meta_box.
		 *
		 * @version 4.2.0
		 * @since   2.5.0
		 */
		public function create_meta_box() {

			parent::create_meta_box();

			$the_product = wc_get_product();
			if ( $this->is_price_by_formula_product( $the_product ) ) {
				$the_price = $the_product->get_price();
				if ( 'yes' === wcj_get_option( 'wcj_product_price_by_formula_admin_scope', 'yes' ) ) {
					$the_price = $this->change_price( $the_price, $the_product, true );
				}
				echo '<h4>' . wp_kses_post( 'Final Price Preview', 'woocommerce-jetpack' ) . '</h4>';
				echo wp_kses_post( wc_price( $the_price ) );
			}
		}

	}

endif;

return new WCJ_Product_Price_By_Formula();

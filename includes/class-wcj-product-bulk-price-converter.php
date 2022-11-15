<?php
/**
 * Booster for WooCommerce - Module - Bulk Price Converter
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_Bulk_Price_Converter' ) ) :
	/**
	 * WCJ_Product_Bulk_Price_Converter.
	 */
	class WCJ_Product_Bulk_Price_Converter extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 2.8.0
		 */
		public function __construct() {

			$this->id         = 'bulk_price_converter';
			$this->short_desc = __( 'Bulk Price Converter', 'woocommerce-jetpack' );
			$this->desc       = __( 'Multiply all products prices by set value.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-bulk-price-converter';
			parent::__construct();

			$this->add_tools(
				array(
					'bulk_price_converter' => array(
						'title' => __( 'Bulk Price Converter', 'woocommerce-jetpack' ),
						'desc'  => __( 'Bulk Price Converter Tool.', 'woocommerce-jetpack' ),
					),
				)
			);
		}

		/**
		 * Change_price_by_type.
		 *
		 * @version 5.6.8
		 * @param int           $product_id defines the product_id.
		 * @param string        $multiply_price_by defines the multiply_price_by.
		 * @param string        $price_type defines the price_type.
		 * @param string | bool $is_preview defines the is_preview.
		 * @param int           $parent_product_id defines the parent_product_id.
		 * @param int           $min_price defines the min_price.
		 * @param int           $max_price defines the max_price.
		 */
		public function change_price_by_type( $product_id, $multiply_price_by, $price_type, $is_preview, $parent_product_id, $min_price = 0, $max_price = 0 ) {
			$the_price          = get_post_meta( $product_id, '_' . $price_type, true );
			$the_modified_price = $the_price;
			$wpnonce            = isset( $_REQUEST['bulk_change_prices-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['bulk_change_prices-nonce'] ), 'bulk_change_prices' ) : false;
			if ( '' !== $the_price && 0 !== $the_price ) {
				$precision          = wcj_get_option( 'woocommerce_price_num_decimals', 2 );
				$the_modified_price = round( $the_price * $multiply_price_by, $precision );
				if ( $wpnonce && isset( $_POST['make_pretty_prices_threshold'] ) && apply_filters( 'booster_option', 0, sanitize_text_field( wp_unslash( $_POST['make_pretty_prices_threshold'] ) ) ) > 0 ) {
					$the_modified_price = $this->make_pretty_price( $the_modified_price );
				}
				if ( $the_modified_price < 0 ) {
					$the_modified_price = 0;
				}
				if ( 0 !== $min_price && $the_modified_price < $min_price ) {
					$the_modified_price = $min_price;
				}
				if ( 0 !== $max_price && $the_modified_price > $max_price ) {
					$the_modified_price = $max_price;
				}
				if ( ! $is_preview ) {
					update_post_meta( $product_id, '_' . $price_type, $the_modified_price );
				}
			}

			$product_cats  = array();
			$product_terms = get_the_terms( $parent_product_id, 'product_cat' );
			if ( is_array( $product_terms ) ) {
				foreach ( $product_terms as $term ) {
					$product_cats[] = esc_html( $term->name );
				}
			}
			if ( '' !== $the_price || '' !== $the_modified_price ) {
				echo '<tr>' .
					'<td>' . esc_html( get_the_title( $product_id ) ) . '</td>' .
					'<td>' . wp_kses_post( implode( ', ', array_map( 'sanitize_text_field', $product_cats ) ) ) . '</td>' .
					'<td><em>' . wp_kses_post( $price_type ) . '</em></td>' .
					'<td>' . wp_kses_post( $the_price ) . '</td>' .
					'<td>' . wp_kses_post( $the_modified_price ) . '</td>' .
				'</tr>';
			}
		}

		/**
		 * Change_price_all_types.
		 *
		 * @version 5.6.8
		 * @param int           $product_id defines the product_id.
		 * @param int           $multiply_price_by defines the multiply_price_by.
		 * @param string | bool $is_preview defines the is_preview.
		 * @param int           $parent_product_id defines the parent_product_id.
		 */
		public function change_price_all_types( $product_id, $multiply_price_by, $is_preview, $parent_product_id ) {
			$wpnonce               = isset( $_REQUEST['bulk_change_prices-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['bulk_change_prices-nonce'] ), 'bulk_change_prices' ) : false;
			$what_prices_to_modify = ( $wpnonce && isset( $_POST['wcj_price_types'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wcj_price_types'] ) ) : 'wcj_both';
			if ( 'wcj_both' === $what_prices_to_modify ) {
				$this->change_price_by_type( $product_id, $multiply_price_by, 'price', $is_preview, $parent_product_id );
				$this->change_price_by_type( $product_id, $multiply_price_by, 'sale_price', $is_preview, $parent_product_id );
				$this->change_price_by_type( $product_id, $multiply_price_by, 'regular_price', $is_preview, $parent_product_id );
			} elseif ( 'wcj_sale_prices' === $what_prices_to_modify ) {
				if ( get_post_meta( $product_id, '_price', true ) === get_post_meta( $product_id, '_sale_price', true ) ) {
					$this->change_price_by_type(
						$product_id,
						$multiply_price_by,
						'price',
						$is_preview,
						$parent_product_id,
						0,
						get_post_meta( $product_id, '_regular_price', true )
					);
				}
				$this->change_price_by_type(
					$product_id,
					$multiply_price_by,
					'sale_price',
					$is_preview,
					$parent_product_id,
					0,
					get_post_meta( $product_id, '_regular_price', true )
				);
			} elseif ( 'wcj_regular_prices' === $what_prices_to_modify ) {
				if ( get_post_meta( $product_id, '_price', true ) === get_post_meta( $product_id, '_regular_price', true ) ) {
					if (
					get_post_meta( $product_id, '_price', true ) !== get_post_meta( $product_id, '_sale_price', true ) ||
					$multiply_price_by <= 1
					) {
						$this->change_price_by_type(
							$product_id,
							$multiply_price_by,
							'price',
							$is_preview,
							$parent_product_id,
							get_post_meta( $product_id, '_sale_price', true ),
							0
						);
					}
				}
				$this->change_price_by_type(
					$product_id,
					$multiply_price_by,
					'regular_price',
					$is_preview,
					$parent_product_id,
					get_post_meta( $product_id, '_sale_price', true ),
					0
				);
			}
		}

		/**
		 * Change_product_price.
		 *
		 * @version 2.4.0
		 * @param int           $product_id defines the product_id.
		 * @param int           $multiply_price_by defines the multiply_price_by.
		 * @param string | bool $is_preview defines the is_preview.
		 */
		public function change_product_price( $product_id, $multiply_price_by, $is_preview ) {
			$this->change_price_all_types( $product_id, $multiply_price_by, $is_preview, $product_id );
			// Handling variable products.
			$product = wc_get_product( $product_id );
			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_available_variations();
				foreach ( $variations as $variation ) {
					$this->change_price_all_types( $variation['variation_id'], $multiply_price_by, $is_preview, $product_id );
				}
			}
		}

		/**
		 * Change_all_products_prices.
		 *
		 * @version 5.6.8
		 * @todo    (dev) clear products transients after converting prices
		 * @param int           $multiply_prices_by defines the multiply_prices_by.
		 * @param string | bool $is_preview defines the is_preview.
		 */
		public function change_all_products_prices( $multiply_prices_by, $is_preview ) {
			$multiply_prices_by = floatval( $multiply_prices_by );
			if ( $multiply_prices_by <= 0 ) {
				return;
			}

			$wpnonce = isset( $_REQUEST['bulk_change_prices-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['bulk_change_prices-nonce'] ), 'bulk_change_prices' ) : false;

			ob_start();

			echo '<table class="widefat striped" style="margin-top: 10px;">';
			echo '<tr>' .
				'<th>' . wp_kses_post( 'Product', 'woocommerce-jetpack' ) . '</th>' .
				'<th>' . wp_kses_post( 'Categories', 'woocommerce-jetpack' ) . '</th>' .
				'<th>' . wp_kses_post( 'Price Type', 'woocommerce-jetpack' ) . '</th>' .
				'<th>' . wp_kses_post( 'Original Price', 'woocommerce-jetpack' ) . '</th>' .
				'<th>' . wp_kses_post( 'Modified Price', 'woocommerce-jetpack' ) . '</th>' .
			'</tr>';

			$offset     = 0;
			$block_size = 1024;
			while ( true ) {
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => $block_size,
					'offset'         => $offset,
					'orderby'        => 'ID',
					'order'          => 'DESC',
					'fields'         => 'ids',
				);
				if ( $wpnonce && isset( $_POST['wcj_product_cat'] ) && 'wcj_any' !== $_POST['wcj_product_cat'] && 'any' !== apply_filters( 'booster_option', 'any', '' ) ) {
					$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'slug',
							'terms'    => array( sanitize_text_field( wp_unslash( $_POST['wcj_product_cat'] ) ) ),
							'operator' => ( 'wcj_none' !== sanitize_text_field( wp_unslash( $_POST['wcj_product_cat'] ) ) ) ? 'IN' : 'NOT EXISTS',
						),
					);
				}
				$loop = new WP_Query( $args );
				if ( ! $loop->have_posts() ) {
					break;
				}
				foreach ( $loop->posts as $product_id ) {
					$this->change_product_price( $product_id, $multiply_prices_by, $is_preview );
				}
				$offset += $block_size;
			}

			echo '</table>';

			return ob_get_clean();
		}

		/**
		 * Create_bulk_price_converter_tool.
		 *
		 * @version 5.6.8
		 */
		public function create_bulk_price_converter_tool() {
			$result_message     = '';
			$wpnonce            = isset( $_REQUEST['bulk_change_prices-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['bulk_change_prices-nonce'] ), 'bulk_change_prices' ) : false;
			$multiply_prices_by = $wpnonce && isset( $_POST['multiply_prices_by'] ) ? sanitize_text_field( wp_unslash( $_POST['multiply_prices_by'] ) ) : 1;
			$is_preview         = isset( $_POST['bulk_change_prices_preview'] );

			$result_changing_prices = '';

			if ( $multiply_prices_by <= 0 ) {
				$result_message     = '<p><div class="error"><p><strong>' . __( 'Multiply value must be above zero.', 'woocommerce-jetpack' ) . '</strong></p></div></p>';
				$multiply_prices_by = 1;
			} else {
				if ( isset( $_POST['bulk_change_prices'] ) || isset( $_POST['bulk_change_prices_preview'] ) ) {
					$result_changing_prices = $this->change_all_products_prices( $multiply_prices_by, $is_preview );
					if ( ! $is_preview ) {
						$result_message     = '<p><div class="updated"><p><strong>' . __( 'Prices changed successfully!', 'woocommerce-jetpack' ) .
						'</strong></p></div></p>';
						$multiply_prices_by = 1;
					}
				}
			}

			$select_options_html = '';
			$selected_option     = ( isset( $_POST['wcj_product_cat'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wcj_product_cat'] ) ) : '';
			$product_categories  = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
			if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) {
				foreach ( $product_categories as $product_category ) {
					$select_options_html .= '<option value="' . $product_category->slug . '"' . selected( $product_category->slug, $selected_option, false ) . '>' .
					$product_category->name .
					'</option>';
				}
			}

			// Output HTML.
			echo '<div class="wcj-setting-jetpack-body wcj_tools_cnt_main">';
			echo wp_kses_post( $this->get_tool_header_html( 'bulk_price_converter' ) );
			echo wp_kses_post( $result_message );
			echo '<form method="post" action="">';
				$data_table   = array();
				$data_table[] = array(
					__( 'Multiply all product prices by', 'woocommerce-jetpack' ),
					'<input class="" style="width:100%;" type="number" step="0.000001" min="0.000001" name="multiply_prices_by" id="multiply_prices_by" value="' .
						$multiply_prices_by . '">',
					'',
				);

				$selected_option_price_types = ( isset( $_POST['wcj_price_types'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wcj_price_types'] ) ) : '';
				$data_table[]                = array(
					__( 'Price type to modify', 'woocommerce-jetpack' ),
					'<select style="width:100%;" name="wcj_price_types">' .
						'<option value="wcj_both">' . __( 'Both', 'woocommerce-jetpack' ) . '</option>' .
						'<option value="wcj_sale_prices"' . selected( 'wcj_sale_prices', $selected_option_price_types, false ) . '>'
							. __( 'Sale prices only', 'woocommerce-jetpack' ) . '</option>' .
						'<option value="wcj_regular_prices"' . selected( 'wcj_regular_prices', $selected_option_price_types, false ) . '>'
							. __( 'Regular prices only', 'woocommerce-jetpack' ) . '</option>' .
					'</select>',
					'',
				);

				if ( '' !== $select_options_html ) {
					$data_table[] = array(
						__( 'Products category', 'woocommerce-jetpack' ),
						'<select style="width:100%;" name="wcj_product_cat" ' . apply_filters( 'booster_option', 'disabled', '' ) . '>' .
							'<option value="wcj_any">' . __( 'Any', 'woocommerce-jetpack' ) . '</option>' .
							$select_options_html .
							'<option value="wcj_none"' . selected( 'wcj_none', $selected_option, false ) . '>' . __( 'None', 'woocommerce-jetpack' ) . '</option>' .
						'</select>',
						'<em>' . apply_filters( 'booster_message', '', 'desc' ) . '</em>',
					);
				}
				$make_pretty_prices_threshold = isset( $_POST['make_pretty_prices_threshold'] ) ? sanitize_text_field( wp_unslash( $_POST['make_pretty_prices_threshold'] ) ) : 0;
				$data_table[]                 = array(
					__( '"Pretty prices" threshold', 'woocommerce-jetpack' ) . wcj_help_tip(
						__( 'Leave zero to disable.', 'woocommerce-jetpack' ) . ' ' .
						__( 'Otherwise - all prices below "threshold" will end with 0,99 and all prices above or equal to "threshold" will end with 9,00.', 'woocommerce-jetpack' ) . ' ' .
						__( 'E.g.: if you set "threshold" to 100, then all prices below 100 will be like 45,99 and all other prices will be like 109,00.', 'woocommerce-jetpack' )
					),
					'<input style="width:100%;" class="" type="number" step="0.000001" min="0" name="make_pretty_prices_threshold" id="make_pretty_prices_threshold" ' . apply_filters( 'booster_option', 'disabled', '' ) . ' value="' .
						$make_pretty_prices_threshold . '">',
					'<em>' . apply_filters( 'booster_message', '', 'desc' ) . '</em>',
				);
				$data_table[]                 = array(
					'<input class="button-primary" type="submit" name="bulk_change_prices_preview" id="bulk_change_prices_preview" value="' .
						__( 'Preview Prices', 'woocommerce-jetpack' ) . '">',
					'',
					'',
				);
				if ( isset( $_POST['bulk_change_prices_preview'] ) ) {
					$data_table[] = array(
						'<input class="button-primary" type="submit" name="bulk_change_prices" id="bulk_change_prices" value="' .
							__( 'Change Prices', 'woocommerce-jetpack' ) . '">',
						'',
						'',
					);
				}
				$data_table[] = array(
					wp_nonce_field( 'bulk_change_prices', 'bulk_change_prices-nonce' ),
					'',
					'',
				);
				echo (
					wp_kses_post(
						wcj_get_table_html(
							$data_table,
							array(
								'table_heading_type' => 'none',
								'table_class'        => 'widefat striped',
								'table_style'        => 'width:50%;min-width:300px;',
							)
						)
					)
				);
				echo '</form>';
			if ( $is_preview ) {
				echo wp_kses_post( $result_changing_prices );
			}
			echo '</div>';
		}


		/**
		 * Make_pretty_price.
		 *
		 * @version 5.6.8
		 * @since   2.4.4
		 * @param int $price defines the price.
		 */
		public function make_pretty_price( $price ) {
			if ( 0 === $price ) {
				return $price;
			}
			$wpnonce            = isset( $_REQUEST['bulk_change_prices-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['bulk_change_prices-nonce'] ), 'bulk_change_prices' ) : false;
			$the_modified_price = round( $price );
			if ( $wpnonce && isset( $_POST['make_pretty_prices_threshold'] ) && $price < $_POST['make_pretty_prices_threshold'] ) {
				$the_modified_price -= 0.01; // E.g. 49.49 -> 48.99 and 49.50 -> 49.99.
			} else {
				$mod_10 = $the_modified_price % 10;
				if ( 9 !== $mod_10 ) {
					$the_modified_price = ( $mod_10 < 5 ) ?
					$the_modified_price - ( $mod_10 + 1 ) : // E.g. 114.00 -> 109.00.
					$the_modified_price + ( 10 - ( $mod_10 + 1 ) ); // E.g. 115.00 -> 119.00.
				}
			}
			return $the_modified_price;
		}

	}

endif;

return new WCJ_Product_Bulk_Price_Converter();

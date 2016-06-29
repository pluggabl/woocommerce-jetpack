<?php
/**
 * WooCommerce Jetpack SKU
 *
 * The WooCommerce Jetpack SKU class.
 *
 * @version 2.5.3
 * @author  Algoritmika Ltd.
 * @todo    add "random number" option
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_SKU' ) ) :

class WCJ_SKU extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.3
	 */
	function __construct() {

		$this->id         = 'sku';
		$this->short_desc = __( 'SKU', 'woocommerce-jetpack' );
		$this->desc       = __( 'Generate WooCommerce SKUs automatically.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-sku/';
		parent::__construct();

		$this->add_tools( array(
			'sku' => array(
				'title' => __( 'Autogenerate SKUs', 'woocommerce-jetpack' ),
				'desc'  => __( 'The tool generates and sets product SKUs for existing products.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {

			add_action( 'wp_insert_post', array( $this, 'set_sku_for_new_product' ), PHP_INT_MAX, 3 );

			if ( 'yes' === get_option( 'wcj_sku_allow_duplicates_enabled', 'no' ) ) {
				add_filter( 'wc_product_has_unique_sku', '__return_false', PHP_INT_MAX );
			}
		}
	}

	/**
	 * get_available_variations.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_all_variations( $_product ) {
		$all_variations = array();
		foreach ( $_product->get_children() as $child_id ) {
			$variation = $_product->get_child( $child_id );
			$all_variations[] = $_product->get_available_variation( $variation );
		}
		return $all_variations;
	}

	/**
	 * set_sku_with_variable.
	 *
	 * @version 2.5.2
	 * @todo    Handle cases with more than 26 variations
	 */
	function set_sku_with_variable( $product_id, $is_preview ) {

		/* if ( 'random' === apply_filters( 'wcj_get_option_filter', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
			$sku_number = rand();
		} */
		if ( 'sequential' === apply_filters( 'wcj_get_option_filter', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
			$sku_number = $this->sequential_counter;
			$this->sequential_counter++;
		} else { // if 'product_id'
			$sku_number = $product_id;
		}

		$this->set_sku( $product_id, $sku_number, '', $is_preview, $product_id );

		// Handling variable products
		$variation_handling = apply_filters( 'wcj_get_option_filter', 'as_variable', get_option( 'wcj_sku_variations_handling', 'as_variable' ) );
		$product = wc_get_product( $product_id );
		if ( $product->is_type( 'variable' ) ) {
			$variations = $this->get_all_variations( $product );
			if ( 'as_variable' === $variation_handling ) {
				foreach ( $variations as $variation ) {
					$this->set_sku( $variation['variation_id'], $sku_number, '', $is_preview, $product_id );
				}
			}
			else if ( 'as_variation' === $variation_handling ) {
				foreach ( $variations as $variation ) {
					if ( 'sequential' === apply_filters( 'wcj_get_option_filter', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
						$sku_number = $this->sequential_counter;
						$this->sequential_counter++;
					} else { // if 'product_id'
						$sku_number = $variation['variation_id'];
					}
					$this->set_sku( $variation['variation_id'], $sku_number, '', $is_preview, $product_id );
				}
			}
			else if ( 'as_variable_with_suffix' === $variation_handling ) {
				$variation_suffixes = 'abcdefghijklmnopqrstuvwxyz';
				$abc = 0;
				foreach ( $variations as $variation ) {
					$this->set_sku( $variation['variation_id'], $sku_number, $variation_suffixes[ $abc++ ], $is_preview, $product_id );
					if ( 26 == $abc ) {
						$abc = 0;
					}
				}
			}
		}
	}

	/**
	 * set_sku.
	 *
	 * @version 2.4.0
	 */
	function set_sku( $product_id, $sku_number, $variation_suffix, $is_preview, $parent_product_id ) {

		$category_prefix = '';
		$category_suffix = '';
		$product_cat = '';
		$product_terms = get_the_terms( $parent_product_id, 'product_cat' );
		if ( is_array( $product_terms ) ) {
			foreach ( $product_terms as $term ) {
				$product_cat = esc_html( $term->name );
				$category_prefix = get_option( 'wcj_sku_prefix_cat_' . $term->term_id, '' );
				$category_suffix = get_option( 'wcj_sku_suffix_cat_' . $term->term_id, '' );
				break;
			}
		}

		$the_sku = sprintf( '%s%s%0' . get_option( 'wcj_sku_minimum_number_length', 0 ) . 'd%s%s%s',
			apply_filters( 'wcj_get_option_filter', '', $category_prefix ),
			get_option( 'wcj_sku_prefix', '' ),
			$sku_number,
			get_option( 'wcj_sku_suffix', '' ),
			$variation_suffix,
			$category_suffix
		);

		if ( $is_preview ) {
			echo '<tr>' .
					'<td>' . $this->product_counter++       . '</td>' .
					'<td>' . get_the_title( $product_id ) . ' (' . __( 'ID', 'woocommerce-jetpack' ) . ':' . $product_id . ')' . '</td>' .
					'<td>' . $product_cat                   . '</td>' .
					'<td>' . $the_sku                       . '</td>' .
				'</tr>';
		}
		else {
			update_post_meta( $product_id, '_' . 'sku', $the_sku );
		}
	}

	/**
	 * set_all_products_skus.
	 *
	 * @version 2.5.2
	 */
	function set_all_products_skus( $is_preview ) {
		if ( 'sequential' === apply_filters( 'wcj_get_option_filter', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
			$this->sequential_counter = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_sku_number_generation_sequential', 1 ) );
		}
		$limit = 96;
		$offset = 0;
		while ( TRUE ) {
			$posts = new WP_Query( array(
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'post_type'      => 'product',
				'post_status'    => 'any',
				'order'          => 'ASC',
				'orderby'        => 'date',
			));
			if ( ! $posts->have_posts() ) break;
			while ( $posts->have_posts() ) {
				$posts->the_post();
				$this->set_sku_with_variable( $posts->post->ID, $is_preview );
			}
			$offset += $limit;
		}
		wp_reset_postdata();
		if ( 'sequential' === apply_filters( 'wcj_get_option_filter', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) && ! $is_preview ) {
			update_option( 'wcj_sku_number_generation_sequential', $this->sequential_counter );
		}
	}

	/**
	 * set_sku_for_new_product.
	 *
	 * @version 2.5.2
	 */
	function set_sku_for_new_product( $post_ID, $post, $update ) {
		if ( 'product' != $post->post_type ) {
			return;
		}
		if ( false === $update ) {
			if ( 'sequential' === apply_filters( 'wcj_get_option_filter', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
				$this->sequential_counter = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_sku_number_generation_sequential', 1 ) );
			}
			$this->set_sku_with_variable( $post_ID, false );
			if ( 'sequential' === apply_filters( 'wcj_get_option_filter', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
				update_option( 'wcj_sku_number_generation_sequential', $this->sequential_counter );
			}
		}
	}

	/**
	 * create_sku_tool
	 *
	 * @version 2.4.0
	 */
	function create_sku_tool() {
		$result_message = '';
		$is_preview = ( isset( $_POST['preview_sku'] ) ) ? true : false;
		if ( isset( $_POST['set_sku'] ) || isset( $_POST['preview_sku'] ) ) {
			$this->product_counter = 1;
			$preview_html = '<table class="widefat" style="width:50%; min-width: 300px; margin-top: 10px;">';
			$preview_html .=
				'<tr>' .
					'<th></th>' .
					'<th>' . __( 'Product', 'woocommerce-jetpack' )    . '</th>' .
					'<th>' . __( 'Categories', 'woocommerce-jetpack' ) . '</th>' .
					'<th>' . __( 'SKU', 'woocommerce-jetpack' )        . '</th>' .
				'</tr>';
			ob_start();
			$this->set_all_products_skus( $is_preview );
			$preview_html .= ob_get_clean();
			$preview_html .= '</table>';
			$result_message = '<p><div class="updated"><p><strong>' . __( 'SKUs generated and set successfully!', 'woocommerce-jetpack' ) . '</strong></p></div></p>';
		}
		?><div>
			<?php echo $this->get_tool_header_html( 'sku' ); ?>
			<?php if ( ! $is_preview ) echo $result_message; ?>
			<form method="post" action="">
				<input class="button-primary" type="submit" name="preview_sku" id="preview_sku" value="<?php _e( 'Preview SKUs', 'woocommerce-jetpack' ); ?>">
				<input class="button-primary" type="submit" name="set_sku" value="<?php _e( 'Set SKUs', 'woocommerce-jetpack' ); ?>">
			</form>
			<?php if ( $is_preview ) echo $preview_html; ?>
		</div><?php
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.3
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'SKU Format Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_sku_format_options',
			),
			array(
				'title'    => __( 'Number Generation', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_number_generation',
				'default'  => 'product_id',
				'type'     => 'select',
				'options'  => array(
					'product_id' => __( 'From product ID', 'woocommerce-jetpack' ),
					'sequential' => __( 'Sequential', 'woocommerce-jetpack' ),
//					'random'     => __( 'Random (including variations)', 'woocommerce-jetpack' ),
				),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),
			array(
				'title'    => __( 'Sequential Number Generation Counter', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_number_generation_sequential',
				'default'  => 1,
				'type'     => 'number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '0', )
				),
			),
			array(
				'title'    => __( 'Prefix', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_prefix',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Minimum Number Length', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_minimum_number_length',
				'default'  => 0,
				'type'     => 'number',
			),
			array(
				'title'    => __( 'Suffix', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_suffix',
				'default'  => '',
				'type'     => 'text',
//				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
//				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'title'    => __( 'Variable Products Variations', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Please note, that on new variable product creation, variations will get same SKUs as parent product, and if you want variations to have different SKUs, you will need to run "Autogenerate SKUs" tool manually.' ),
				'id'       => 'wcj_sku_variations_handling',
				'default'  => 'as_variable',
				'type'     => 'select',
				'options'  => array(
					'as_variable'             => __( 'SKU same as parent\'s product', 'woocommerce-jetpack' ),
					'as_variation'            => __( 'Generate different SKU for each variation', 'woocommerce-jetpack' ),
					'as_variable_with_suffix' => __( 'SKU same as parent\'s product + variation letter suffix', 'woocommerce-jetpack' ),
				),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_sku_format_options',
			),
		);
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Categories Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_sku_categories_options',
			),
		) );
		$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ){
			foreach ( $product_categories as $product_category ) {
				$settings = array_merge( $settings, array(
					array(
						'title'    => $product_category->name,
						'desc'     => __( 'Prefix', 'woocommerce-jetpack' ),
						'id'       => 'wcj_sku_prefix_cat_' . $product_category->term_id,
						'default'  => '',
						'type'     => 'text',
						'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' ),
						'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
					),
					array(
						'title'    => '',
						'desc'     => __( 'Suffix', 'woocommerce-jetpack' ),
						'id'       => 'wcj_sku_suffix_cat_' . $product_category->term_id,
						'default'  => '',
						'type'     => 'text',
//						'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' ),
//						'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
					),
				) );
			}
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_sku_categories_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'More Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_sku_more_options',
			),
			array(
				'title'    => __( 'Allow Duplicate SKUs', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_allow_duplicates_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_sku_more_options',
			),
		) );
		return $this->add_standard_settings(
			$settings,
			__( 'When enabled - all new products will be given (autogenerated) SKU.', 'woocommerce-jetpack' ) . '<br>' .
			__( 'If you wish to set SKUs for existing products, use "Autogenerate SKUs" Tool.', 'woocommerce-jetpack' )
		);
	}
}

endif;

return new WCJ_SKU();

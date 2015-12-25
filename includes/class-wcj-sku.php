<?php
/**
 * WooCommerce Jetpack SKU
 *
 * The WooCommerce Jetpack SKU class.
 *
 * @version 2.3.10
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_SKU' ) ) :

class WCJ_SKU extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.10
	 */
	function __construct() {

		$this->id         = 'sku';
		$this->short_desc = __( 'SKU', 'woocommerce-jetpack' );
		$this->desc       = __( 'Generate WooCommerce SKUs automatically.', 'woocommerce-jetpack' );
		parent::__construct();

		$this->add_tools( array(
			'sku' => array(
				'title' => __( 'Autogenerate SKUs', 'woocommerce-jetpack' ),
				'desc'  => __( 'The tool generates and sets product SKUs.', 'woocommerce-jetpack' ),//__( 'Autogenerate SKUs for Existing Products', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {
			add_action( 'wp_insert_post', array( $this, 'set_sku_for_new_product' ), PHP_INT_MAX, 3 );
		}
	}

	/**
	 * set_sku_with_variable.
	 */
	function set_sku_with_variable( $product_id, $is_preview ) {

		$this->set_sku( $product_id, $product_id, '', $is_preview );

		// Handling variable products
		$variation_handling = apply_filters( 'wcj_get_option_filter', 'as_variable', get_option( 'wcj_sku_variations_handling', 'as_variable' ) );
		$product = wc_get_product( $product_id );
		if ( $product->is_type( 'variable' ) ) {

			$variations = $product->get_available_variations();

			if ( 'as_variable' === $variation_handling ) {
				foreach( $variations as $variation )
					$this->set_sku( $variation['variation_id'], $product_id, '', $is_preview );
			}
			else if ( 'as_variation' === $variation_handling ) {
				foreach( $variations as $variation )
					$this->set_sku( $variation['variation_id'], $variation['variation_id'], '', $is_preview );
			}
			else if ( 'as_variable_with_suffix' === $variation_handling ) {
				$variation_suffixes = 'abcdefghijklmnopqrstuvwxyz';
				$abc = 0;
				foreach( $variations as $variation ) {
					$this->set_sku( $variation['variation_id'], $product_id, $variation_suffixes[ $abc++ ], $is_preview );
					if ( 26 == $abc )
						$abc = 0;
				}
			}
		}
	}

	/**
	 * set_sku.
	 */
	function set_sku( $product_id, $sku_number, $variation_suffix, $is_preview ) {
		$the_sku = sprintf( '%s%0' . get_option( 'wcj_sku_minimum_number_length', 0 ) . 'd%s%s',
			get_option( 'wcj_sku_prefix', '' ),
			$sku_number,
			apply_filters( 'wcj_get_option_filter', '', get_option( 'wcj_sku_suffix', '' ) ),
			$variation_suffix );
		if ( $is_preview ) {
			echo '<tr>' .
				'<td>' . $this->product_counter++ . '</td>' .
				'<td>' . get_the_title( $product_id ) . '</td>' .
				'<td>' . $the_sku . '</td>' .
			 '</tr>';
		}
		else {
			update_post_meta( $product_id, '_' . 'sku', $the_sku );
		}
	}

	/**
	 * set_all_products_skus.
	 *
	 * @version 2.3.10
	 */
	function set_all_products_skus( $is_preview ) {
		$limit = 1000;
		$offset = 0;
		while ( TRUE ) {
			$posts = new WP_Query( array(
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'post_type'      => 'product',
				'post_status'    => 'any',
			));
			if ( ! $posts->have_posts() ) break;
			while ( $posts->have_posts() ) {
					$posts->the_post();
					$this->set_sku_with_variable( $posts->post->ID, $is_preview );
			}
			$offset += $limit;
		}
	}

	/**
	 * set_sku_for_new_product.
	 *
	 * @version 2.3.10
	 */
	function set_sku_for_new_product( $post_ID, $post, $update ) {
		if ( 'product' != $post->post_type ) {
			return;
		}
		if ( false === $update ) {
			$this->set_sku_with_variable( $post_ID, false );
		}
	}

	/**
	 * create_sku_tool
	 *
	 * @version 2.3.10
	 */
	function create_sku_tool() {
		$result_message = '';
		$is_preview = ( isset( $_POST['preview_sku'] ) ) ? true : false;
		if ( isset( $_POST['set_sku'] ) || isset( $_POST['preview_sku'] ) ) {
			$this->product_counter = 1;
			$preview_html = '<table class="widefat" style="width:50%; min-width: 300px;">';
			$preview_html .=
				'<tr>' .
					'<th></th>' .
					'<th>' . __( 'Product', 'woocommerce-jetpack' ) . '</th>' .
					'<th>' . __( 'SKU', 'woocommerce-jetpack' )     . '</th>' .
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
			<p><form method="post" action="">
				<input class="button-primary" type="submit" name="preview_sku" id="preview_sku" value="<?php _e( 'Preview SKUs', 'woocommerce-jetpack' ); ?>">
				<input class="button-primary" type="submit" name="set_sku" value="<?php _e( 'Set SKUs', 'woocommerce-jetpack' ); ?>">
			</form></p>
			<?php if ( $is_preview ) echo $preview_html; ?>
		</div><?php
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'    => __( 'SKU Format Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcj_sku_format_options',
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
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),

			array(
				'title'    => __( 'Variable Products Variations', 'woocommerce-jetpack' ),
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

		return $this->add_standard_settings( $settings, __( 'When enabled - all new products will be given (autogenerated) SKU.<br>If you wish to set SKUs for existing products, use Autogenerate SKUs Tool.', 'woocommerce-jetpack' ) );
	}
}

endif;

return new WCJ_SKU();

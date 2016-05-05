<?php
/**
 * WooCommerce Jetpack Product Price by Formula
 *
 * The WooCommerce Jetpack Product Price by Formula class.
 *
 * @version 2.4.9
 * @since   2.4.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Price_by_Formula' ) ) :

class WCJ_Product_Price_by_Formula extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function __construct() {

		$this->id         = 'product_price_by_formula';
		$this->short_desc = __( 'Product Price by Formula', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set formula for automatic WooCommerce price calculation.', 'woocommerce-jetpack' );
		$this->link       = '';
		parent::__construct();

		if ( $this->is_enabled() ) {
			require_once( wcj_plugin_path() . '/includes/lib/evalmath.class.php' );

			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				// Prices
				add_filter( 'woocommerce_get_price',                      array( $this, 'change_price_by_formula' ), PHP_INT_MAX - 100, 2 );
				add_filter( 'woocommerce_get_sale_price',                 array( $this, 'change_price_by_formula' ), PHP_INT_MAX - 100, 2 );
				add_filter( 'woocommerce_get_regular_price',              array( $this, 'change_price_by_formula' ), PHP_INT_MAX - 100, 2 );
				// Variations
				add_filter( 'woocommerce_variation_prices_price',         array( $this, 'change_price_by_formula' ), PHP_INT_MAX - 100, 2 );
				add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'change_price_by_formula' ), PHP_INT_MAX - 100, 2 );
				add_filter( 'woocommerce_variation_prices_sale_price',    array( $this, 'change_price_by_formula' ), PHP_INT_MAX - 100, 2 );
				add_filter( 'woocommerce_get_variation_prices_hash',      array( $this, 'get_variation_prices_hash' ), PHP_INT_MAX - 100, 3 );
			}
		}
	}

	/**
	 * change_price_by_formula.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function change_price_by_formula( $price, $_product ) {
		if ( '' != $price ) {
			$the_formula = get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_eval', true );
			if ( '' != $the_formula ) {
				$total_params = get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_total_params', true );
				if ( $total_params > 0 ) {
					$m = new EvalMath;
					$m->suppress_errors = true;
					$m->evaluate( 'x = ' . $price );
					for ( $i = 1; $i <= $total_params; $i++ ) {
						$the_param = get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_param_' . $i, true );
						if ( '' != $the_param ) {
							$m->evaluate( 'p' . $i . ' = ' . $the_param );
						}
					}
					$price = $m->evaluate( $the_formula );
				}
			}
		}
		return $price;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$price_hash['wcj_price_by_formula_total_params'] = get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_total_params', true ); // TODO?
		return $price_hash;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_product_price_by_formula_eval',
				'default'    => get_option( 'wcj_product_price_by_formula_eval', '' ),
				'type'       => 'text',
				'title'      => __( 'Formula', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_product_price_by_formula_total_params',
				'default'    => get_option( 'wcj_product_price_by_formula_total_params', 1 ),
				'type'       => 'number',
				'title'      => __( 'Number of Parameters', 'woocommerce-jetpack' ),
			),
		);
		$total_params = get_post_meta( get_the_ID(), '_' . 'wcj_product_price_by_formula_total_params', false );
		if ( empty( $total_params ) ) {
			$total_params = get_option( 'wcj_product_price_by_formula_total_params', 1 );
		} else {
			$total_params = $total_params[0];
		}
		for ( $i = 1; $i <= $total_params; $i++ ) {
			$options[] = array(
				'name'       => 'wcj_product_price_by_formula_param_' . $i,
				'default'    => get_option( 'wcj_product_price_by_formula_param_' . $i, '' ),
				'type'       => 'text',
				'title'      => 'p' . $i,
			);
		}
		return $options;
	}

	/**
	 * create_meta_box.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function create_meta_box() {

		parent::create_meta_box();

		$the_product = wc_get_product();
		$the_price   = $the_product->get_price();
		$the_price   = $this->change_price_by_formula( $the_price, $the_product );
		echo '<h4>' . __( 'Final Price Preview', 'woocommerce-jetpack' ) . '</h4>';
		echo wc_price( $the_price );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.9
	 * @since   2.4.9
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Default Settings', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'You can optionally set default settings here. All settings can later be changed in individual product\'s edit page.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_price_by_formula_options',
			),
			array(
				'title'    => __( 'Formula', 'woocommerce-jetpack' ),
				'desc'     => __( 'Use "x" variable for product\'s base price. For example: x+p1*p2', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'id'       => 'wcj_product_price_by_formula_eval',
				'default'  => '',
			),
			array(
				'title'    => __( 'Total Params', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_price_by_formula_total_params',
				'default'  => 1,
				'type'     => 'custom_number',
				/* 'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ), */
			),
		);
		$total_number = get_option( 'wcj_product_price_by_formula_total_params', 1 );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings[] = array(
				'title'    => 'p' . $i,
				'id'       => 'wcj_product_price_by_formula_param_' . $i,
				'default'  => '',
				'type'     => 'text',
			);
		}
		$settings[] = array(
			'type'         => 'sectionend',
			'id'           => 'wcj_product_price_by_formula_options',
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Price_by_Formula();

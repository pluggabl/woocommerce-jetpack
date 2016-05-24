<?php
/**
 * WooCommerce Jetpack Product Price by Formula
 *
 * The WooCommerce Jetpack Product Price by Formula class.
 *
 * @version 2.5.1
 * @since   2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Price_by_Formula' ) ) :

class WCJ_Product_Price_by_Formula extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function __construct() {

		$this->id         = 'product_price_by_formula';
		$this->short_desc = __( 'Product Price by Formula', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set formula for automatic WooCommerce product price calculation.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-price-formula/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			require_once( wcj_plugin_path() . '/includes/lib/PHPMathParser/Math.php' );

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
				// Grouped products
				add_filter( 'woocommerce_get_price_including_tax',        array( $this, 'change_price_by_formula_grouped' ), PHP_INT_MAX - 100, 3 );
				add_filter( 'woocommerce_get_price_excluding_tax',        array( $this, 'change_price_by_formula_grouped' ), PHP_INT_MAX - 100, 3 );
			}

			add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
			add_action( 'admin_notices',           array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * change_price_by_formula_grouped.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function change_price_by_formula_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
			foreach ( $_product->get_children() as $child_id ) {
				$the_price = get_post_meta( $child_id, '_price', true );
				$the_product = wc_get_product( $child_id );
				$the_price = $the_product->$get_price_method( 1, $the_price );
				if ( $the_price == $price ) {
					return $this->change_price_by_formula( $price, $the_product );
				}
			}
		}
		return $price;
	}

	/**
	 * change_price_by_formula.
	 *
	 * @version 2.5.1
	 * @since   2.5.0
	 */
	function change_price_by_formula( $price, $_product, $output_errors = false ) {
		if ( $this->is_price_by_formula_product( $_product ) && '' != $price ) {
			$is_per_product = ( 'per_product' === get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_calculation', true ) ) ? true : false;
			$the_formula = ( $is_per_product )
				? get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_eval', true )
				: get_option( 'wcj_product_price_by_formula_eval', '' );
			if ( '' != $the_formula ) {
				$total_params = ( $is_per_product )
					? get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_total_params', true )
					: get_option( 'wcj_product_price_by_formula_total_params', 1 );
				if ( $total_params > 0 ) {
					$the_current_filter = current_filter();
					if ( 'woocommerce_get_price_including_tax' == $the_current_filter || 'woocommerce_get_price_excluding_tax' == $the_current_filter ) {
						$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
						return $_product->$get_price_method();
					}
					$math = new /* PHPMathParser\ */Alg_Math();
					$math->registerVariable( 'x', $price );
					for ( $i = 1; $i <= $total_params; $i++ ) {
						$the_param = ( $is_per_product )
							? get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_param_' . $i, true )
							: get_option( 'wcj_product_price_by_formula_param_' . $i, '' );
						if ( '' != $the_param ) {
							$math->registerVariable( 'p' . $i, $the_param );
						}
					}
					$the_formula = str_replace( 'x', '$x', $the_formula );
					$the_formula = str_replace( 'p', '$p', $the_formula );
					try {
						$price = $math->evaluate( $the_formula );
					} catch ( Exception $e ) {
						if ( $output_errors ) {
							echo '<p style="color:red;">' . __( 'Error in formula', 'woocommerce-jetpack' ) . ': ' . $e->getMessage() . '</p>';
						}
					}
				}
			}
		}
		return $price;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		if ( $this->is_price_by_formula_product( $_product ) ) {
			$the_formula = get_option( 'wcj_product_price_by_formula_eval', '' );
			$total_params = get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_total_params', true );
			$the_params = array();
			for ( $i = 1; $i <= $total_params; $i++ ) {
				$the_params[] = get_option( 'wcj_product_price_by_formula_param_' . $i, '' );
			}
			$price_hash['wcj_price_by_formula'] = array(
				$the_formula,
				$total_params,
				$the_params,
			);
		}
		return $price_hash;
	}

	/**
	 * save_meta_box_value.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function save_meta_box_value( $option_value, $option_name, $module_id ) {
		if ( true === apply_filters( 'wcj_get_option_filter', false, true ) ) {
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
				'meta_key'       => '_' . 'wcj_product_price_by_formula_enabled',
				'meta_value'     => 'yes',
				'post__not_in'   => array( get_the_ID() ),
			);
			$loop = new WP_Query( $args );
			$c = $loop->found_posts + 1;
			if ( $c >= 2 ) {
				add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
				return 'no';
			}
		}
		return $option_value;
	}

	/**
	 * add_notice_query_var.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
		return add_query_arg( array( 'wcj_product_price_by_formula_admin_notice' => true ), $location );
	}

	/**
	 * admin_notices.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function admin_notices() {
		if ( ! isset( $_GET['wcj_product_price_by_formula_admin_notice'] ) ) {
			return;
		}
		?><div class="error"><p><?php
			echo '<div class="message">'
				. __( 'Booster: Free plugin\'s version is limited to only one price by formula product enabled at a time. You will need to get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to add unlimited number of price by formula products.', 'woocommerce-jetpack' )
				. '</div>';
		?></p></div><?php
	}

	/**
	 * is_price_by_formula_product.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function is_price_by_formula_product( $_product ) {
		return ( 'yes' === get_post_meta( $_product->id, '_' . 'wcj_product_price_by_formula_enabled', true ) ) ? true : false;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_product_price_by_formula_enabled',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Enabled', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_product_price_by_formula_calculation',
				'default'    => 'per_product',
				'type'       => 'select',
				'options'    => array(
					'per_product' => __( 'Use values below', 'woocommerce-jetpack' ),
					'global'      => __( 'Use default values', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Calculation', 'woocommerce-jetpack' ),
			),
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
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function create_meta_box() {

		parent::create_meta_box();

		$the_product = wc_get_product();
		if ( $this->is_price_by_formula_product( $the_product ) ) {
			$the_price   = $the_product->get_price();
			$the_price   = $this->change_price_by_formula( $the_price, $the_product, true );
			echo '<h4>' . __( 'Final Price Preview', 'woocommerce-jetpack' ) . '</h4>';
			echo wc_price( $the_price );
		}
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Default Settings', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'You can set default settings here. All settings can later be changed in individual product\'s edit page.', 'woocommerce-jetpack' ),
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

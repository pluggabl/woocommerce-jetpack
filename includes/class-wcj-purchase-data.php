<?php
/**
 * Booster for WooCommerce - Module - Cost of Goods (formerly Product Cost Price)
 *
 * @version 5.2.0
 * @since   2.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Purchase_Data' ) ) :

class WCJ_Purchase_Data extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @todo    (maybe) pre-calculate profit for orders
	 * @todo    (maybe) "Apply costs to orders that do not have costs set"
	 * @todo    (maybe) "Apply costs to all orders, overriding previous costs"
	 * @todo    (maybe) `calculate_all_products_profit()`
	 */
	function __construct() {

		$this->id         = 'purchase_data';
		$this->short_desc = __( 'Cost of Goods', 'woocommerce-jetpack' );
		$this->desc       = __( 'Save product purchase costs data for admin reports (1 custom field allowed in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Save product purchase costs data for admin reports.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-cost-of-goods';
		parent::__construct();

		$this->add_tools( array(
			'import_from_wc_cog' => array(
				'title'     => __( '"WooCommerce Cost of Goods" Data Import', 'woocommerce-jetpack' ),
				'desc'      => __( 'Import products costs from "WooCommerce Cost of Goods".', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {

			// Products meta boxes
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			// Orders columns
			if (
				'yes' === wcj_get_option( 'wcj_purchase_data_custom_columns_profit', 'yes' ) ||
				'yes' === wcj_get_option( 'wcj_purchase_data_custom_columns_purchase_cost', 'no' )
			) {
				add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_columns' ),    PHP_INT_MAX - 2 );
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), PHP_INT_MAX );
			}

			// Products columns
			if (
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_purchase_data_custom_products_columns_purchase_cost', 'no' ) ) ||
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_purchase_data_custom_products_columns_profit', 'no' ) )
			) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),    PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_columns' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * create_import_from_wc_cog_tool.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function create_import_from_wc_cog_tool() {
		// Action and Products list
		$perform_import = ( isset( $_POST['wcj_import_from_wc_cog'] ) );
		$table_data = array();
		$table_data[] = array(
			__( 'Product ID', 'woocommerce-jetpack' ),
			__( 'Product Title', 'woocommerce-jetpack' ),
			__( 'WooCommerce Cost of Goods (source)', 'woocommerce-jetpack' ),
			__( 'Booster: Product cost (destination)', 'woocommerce-jetpack' ),
		);
		foreach ( wcj_get_products( array(), 'any', 512, true  ) as $product_id => $product_title ) {
			$wc_cog_cost = get_post_meta( $product_id, '_wc_cog_cost', true );
			if ( $perform_import ) {
				update_post_meta( $product_id, '_wcj_purchase_price', $wc_cog_cost );
			}
			$wcj_purchase_price = get_post_meta( $product_id, '_wcj_purchase_price', true );
			$table_data[] = array( $product_id, $product_title, $wc_cog_cost, $wcj_purchase_price );
		}
		// Button form
		$button_form = '';
		$button_form .= '<form method="post" action="">';
		$button_form .= '<input type="submit" name="wcj_import_from_wc_cog" class="button-primary" value="' . __( 'Import', 'woocommerce-jetpack' ) . '"' .
			' onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">';
		$button_form .= '</form>';
		// Output
		$html = '';
		$html .= '<div class="wrap">';
		$html .= '<p>' . $this->get_tool_header_html( 'import_from_wc_cog' ) . '</p>';
		$html .= '<p>' . $button_form . '</p>';
		$html .= '<p>' . wcj_get_table_html( $table_data, array( 'table_heading_type' => 'horizontal', 'table_class' => 'widefat striped' ) ) . '</p>';
		$html .= '</div>';
		echo $html;
	}

	/**
	 * add_product_columns.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) output columns immediately after standard "Price"
	 */
	function add_product_columns( $columns ) {
		if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_purchase_data_custom_products_columns_purchase_cost', 'no' ) ) ) {
			$columns['purchase_cost'] = __( 'Cost', 'woocommerce-jetpack' );
		}
		if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_purchase_data_custom_products_columns_profit', 'no' ) ) ) {
			$columns['profit'] = __( 'Profit', 'woocommerce-jetpack' );
		}
		return $columns;
	}

	/**
	 * render_product_columns.
	 *
	 * @version 3.9.0
	 * @since   2.9.0
	 */
	function render_product_columns( $column ) {
		if ( 'profit' === $column || 'purchase_cost' === $column ) {
			$product_id = get_the_ID();
			$_product   = wc_get_product( $product_id );
			if ( $_product->is_type( 'variable' ) && 'no' === wcj_get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' ) ) {
				$purchase_costs       = array();
				$profits              = array();
				$available_variations = $_product->get_available_variations();
				foreach ( $available_variations as $variation ) {
					$variation_id   = $variation['variation_id'];
					$variation_cost = wc_get_product_purchase_price( $variation_id );
					if ( 'purchase_cost' === $column ) {
						$purchase_costs[]  = $variation_cost;
					} elseif ( 'profit' === $column ) {
						$variation_product = wc_get_product( $variation_id );
						$variation_price   = $variation_product->get_price();
						if ( is_numeric( $variation_price ) ) {
							$profits[] = $variation_price - $variation_cost;
						}
					}
				}
				if ( 'purchase_cost' === $column ) {
					$min_cost = min( $purchase_costs );
					$max_cost = max( $purchase_costs );
					echo ( $min_cost === $max_cost ? wc_price( $min_cost ) : wc_format_price_range( $min_cost, $max_cost ) );
				} elseif ( 'profit' === $column ) {
					$min_profit = min( $profits );
					$max_profit = max( $profits );
					echo ( $min_profit === $max_profit ? wc_price( $min_profit ) : wc_format_price_range( $min_profit, $max_profit ) );
				}
			} else {
				$purchase_cost = wc_get_product_purchase_price( $product_id );
				if ( 'purchase_cost' === $column ) {
					echo wc_price( $purchase_cost );
				} elseif ( 'profit' === $column ) {
					$_price = $_product->get_price();
					if ( is_numeric( $_price ) ) {
						echo wc_price( $_price - $purchase_cost );
					}
				}
			}
		}
	}

	/**
	 * add_order_columns.
	 *
	 * @version 2.6.0
	 * @since   2.2.4
	 */
	function add_order_columns( $columns ) {
		if ( 'yes' === wcj_get_option( 'wcj_purchase_data_custom_columns_profit', 'yes' ) ) {
			$columns['profit'] = __( 'Profit', 'woocommerce-jetpack' );
		}
		if ( 'yes' === wcj_get_option( 'wcj_purchase_data_custom_columns_purchase_cost', 'no' ) ) {
			$columns['purchase_cost'] = __( 'Purchase Cost', 'woocommerce-jetpack' );
		}
		return $columns;
	}

	/**
	 * For Getting The Price by Formulla Apply.
	 *
	 * @version 5.3.7
	 * @since   2.2.4
	 * 
	 */
	public function is_price_by_formula_product($_product)
	{
		return (
			'yes' === apply_filters('booster_option', 'no', wcj_get_option(' wcj_product_price_by_formula_enable_for_all_products', ' no ')) ||
			'yes' === get_post_meta(wcj_get_product_id_or_variation_parent_id($_product), '_' . 'wcj_product_price_by_formula_enabled', true)

		);
	}

	/**
	 * For Getting The Wholesale Value If enable.
	 *
	 * @version 5.3.7
	 * @since   5.3.7
	 * 
	 */

	public function change_price($price, $_product, $output_errors = false)
	{
		if ($this->is_price_by_formula_product($_product) && '' != $price) {
			$_product_id = wcj_get_product_id_or_variation_parent_id($_product);
			$is_per_product = ('per_product' === get_post_meta($_product_id, '_' . 'wcj_product_price_by_formula_calculation', true));
			$the_formula = ($is_per_product)
				? get_post_meta($_product_id, '_' . 'wcj_product_price_by_formula_eval', true)
				: wcj_get_option('wcj_product_price_by_formula_eval', '');
			//$the_formula = do_shortcode( $the_formula );
			if ('' != $the_formula) {
				$total_params = ($is_per_product)
					? get_post_meta($_product_id, '_' . 'wcj_product_price_by_formula_total_params', true)
					: wcj_get_option('wcj_product_price_by_formula_total_params', 1);
				if ($total_params > 0) {
					$the_current_filter = current_filter();
					if ('woocommerce_get_price_including_tax' == $the_current_filter || 'woocommerce_get_price_excluding_tax' == $the_current_filter) {
						$price = wcj_get_product_display_price($_product);
						$this->save_price($_product_id, $price);
						return $price;
					}
					$math = new WCJ_Math();
					$math->registerVariable('x', $price);
					for ($i = 1; $i <= $total_params; $i++) {
						$the_param = ($is_per_product)
							? get_post_meta($_product_id, '_' . 'wcj_product_price_by_formula_param_' . $i, true)
							: wcj_get_option('wcj_product_price_by_formula_param_' . $i, '');
						$the_param = do_shortcode($the_param);
						if ('' != $the_param) {
							$math->registerVariable('p' . $i, $the_param);
						}
					}
					$the_formula = str_replace('x', '$x', $the_formula);
					$the_formula = str_replace('p', '$p', $the_formula);
					try {
						$price = $math->evaluate($the_formula);
					} catch (Exception $e) {
						if ($output_errors) {
							echo '<p style="color:red;">' . __('Error in formula', 'woocommerce-jetpack') . ': ' . $e->getMessage() . '</p>';
						}
					}
				}
			}
		}
		return $price;
	}

	/**
	 * get_discount_by_quantity.
	 *
	 * @version 5.3.7
	 * @since   5.3.7
	 * 
	 */

	private function get_discount_by_quantity($quantity, $product_id)
	{
		// Check for user role options
		$role_option_name_addon = '';
		$user_roles = wcj_get_option('wcj_wholesale_price_by_user_role_roles', '');
		if (!empty($user_roles)) {
			$current_user_role = wcj_get_current_user_first_role();
			foreach ($user_roles as $user_role_key) {
				if ($current_user_role === $user_role_key) {
					$role_option_name_addon = '_' . $user_role_key;
					break;
				}
			}
		}
		// Get discount
		$max_qty_level = wcj_get_option('wcj_wholesale_price_max_qty_level', 1);
		$discount = 0;
		if (wcj_is_product_wholesale_enabled_per_product($product_id)) {
			for ($i = 1; $i <= apply_filters('booster_option', 1, get_post_meta($product_id, '_' . 'wcj_wholesale_price_levels_number' . $role_option_name_addon, true)); $i++) {
				$level_qty = get_post_meta($product_id, '_' . 'wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, true);
				if ($quantity >= $level_qty && $level_qty >= $max_qty_level) {
					$max_qty_level = $level_qty;
					$discount = get_post_meta($product_id, '_' . 'wcj_wholesale_price_level_discount' . $role_option_name_addon . '_' . $i, true);
				}
			}
		} else {
			for ($i = 1; $i <= apply_filters('booster_option', 1, wcj_get_option('wcj_wholesale_price_levels_number' . $role_option_name_addon, 1)); $i++) {
				$level_qty = wcj_get_option('wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, PHP_INT_MAX);
				if ($quantity >= $level_qty && $level_qty >= $max_qty_level) {
					$max_qty_level = $level_qty;
					$discount = wcj_get_option('wcj_wholesale_price_level_discount_percent' . $role_option_name_addon . '_' . $i, 0);
				}
			}
		}
		return $discount;
	}

	/**
	 * Output custom columns for orders.
	 *
	 * @param   string $column
	 * @version 2.7.0
	 * @since   2.2.4
	 * @todo    forecasted profit `$value = $line_total * $average_profit_margin`
	 * @todo    (maybe) use `[wcj_order_profit]` and `[wcj_order_items_cost]`
	 */
	public function render_order_columns($column) {
		if ('profit' === $column || 'purchase_cost' === $column) {
			$total = 0;
			$the_order = wc_get_order(get_the_ID());
			if (!in_array($the_order->get_status(), array('cancelled', 'refunded', 'failed'))) {
				$is_forecasted = false;
				foreach ($the_order->get_items() as $item_id => $item) {
					$value = 0;
					$product_id = (isset($item['variation_id']) && 0 != $item['variation_id'] && 'no' === wcj_get_option('wcj_purchase_data_variable_as_simple_enabled', 'no')
						? $item['variation_id'] : $item['product_id']);
					if (0 != ($purchase_price = wc_get_product_purchase_price($product_id))) {
						if ('profit' === $column) {
							//Get The Coupon Code if apply customer
							$coupon_code_apply = $the_order->get_coupon_codes();
							$get_coupon_discount_total = $the_order->get_discount_total();

							//Get The Store Default Currency with default Price Of Product.
							$the_product = wc_get_product($product_id);
							$the_price = $the_product->get_price();
							$original_price = $the_price - $purchase_price;

							//Wholesale price
							//Checking the Wholesale Discount Type
							$discount_type = (wcj_is_product_wholesale_enabled_per_product($item['product_id']))
								? get_post_meta($item['product_id'], '_' . 'wcj_wholesale_price_discount_type', true)
								: wcj_get_option('wcj_wholesale_price_discount_type', 'percent');
							$product_meta_data_info = get_post_meta($product_id);
							$discount = $this->get_discount_by_quantity($item['qty'], $product_id);
							if ($discount_type == 'fixed') {
								$per_product_price = $the_price - $discount;
								$per_product_qty = $per_product_price;
								$discount_qty = $discount;
								$total_sum_of_ammount = $discount_qty * $item['qty'];
								$original_price = $per_product_qty - $total_sum_of_ammount;
							}
							if ($discount_type == 'percent') {

								$count = number_format($discount / 100, 8) . '%';
								$percentage = $the_price * $count;
								$per_product_price = $the_price - $percentage;
								$sum_of_the_qty = $per_product_price;
								$original_price = $sum_of_the_qty - $original_price;
							}
							if ($discount_type == 'price_directly') {
								$original_price = $discount;
							}
							// Price By Formula...
							if ("yes" === wcj_get_option('wcj_product_price_by_formula_enabled', 'yes')) {
								if ($this->is_price_by_formula_product($the_product)) {
									if ("yes" === wcj_get_option('wcj_product_price_by_formula_admin_scope', 'yes')) {
										$the_price = $this->change_price($the_price, $the_product, true);
									}
									$discount_type = (wcj_is_product_wholesale_enabled_per_product($item['product_id']))
										? get_post_meta($item['product_id'], '_' . 'wcj_wholesale_price_discount_type', true)
										: wcj_get_option('wcj_wholesale_price_discount_type', 'percent');
									$product_meta_data_info = get_post_meta($product_id);
									$discount = $this->get_discount_by_quantity($item['qty'], $product_id);
									//print_r($discount);
									if ($discount_type == 'fixed') {
										$per_product_price = $the_price - $discount;
										$per_product_qty = $per_product_price;
										$discount_qty = $discount;
										$total_sum_of_ammount = $discount_qty * $item['qty'];
										$original_price = $per_product_qty - $total_sum_of_ammount;
									}
									if ($discount_type == 'percent') {
										$count = number_format($discount / 100, 8) . '%';
										$percentage = $the_price * $count;
										$per_product_price = $the_price - $percentage;
										$sum_of_the_qty = $per_product_price;
										$purchase_total_qty = $purchase_price;
										$original_price = $sum_of_the_qty - $purchase_total_qty;
									}
									if ($discount_type == 'price_directly') {
										$original_price = $discount;
									}
								}
							}
							// Price By Formula END...
							$value = $original_price * $item['qty'] - $get_coupon_discount_total;

						} else {
							$value = $purchase_price * $item['qty'];
						}
					} else {
						$is_forecasted = true;
					}
					$total += $value;
				}
			}
			if (0 != $total) {
				if (!$is_forecasted) {
					echo '<span style="color:green;">';
				}
				echo wc_price($total);
				if (!$is_forecasted) {
					echo '</span>';
				}
			}
		}
    }

	/**
	 * create_meta_box.
	 *
	 * @version 4.5.0
	 * @since   2.4.5
	 * @todo    (maybe) min_profit
	 */
	function create_meta_box() {

		parent::create_meta_box();

		// Report
		$main_product_id = get_the_ID();
		$_product = wc_get_product( $main_product_id );
		$products = array();
		if ( $_product->is_type( 'variable' ) && 'no' === wcj_get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' ) ) {
			$available_variations = $_product->get_available_variations();
			foreach ( $available_variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );
				$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
			}
		} else {
			$products[ $main_product_id ] = '';
		}
		foreach ( $products as $product_id => $desc ) {
			$purchase_price = wc_get_product_purchase_price( $product_id );
			if ( 0 != $purchase_price ) {
				$the_product = wc_get_product( $product_id );
				$the_price = $the_product->get_price();
				if ( 0 != $the_price ) {
					$the_profit = $the_price - $purchase_price;
					$table_data = array();
					$table_data[] = array( __( 'Selling', 'woocommerce-jetpack' ), wc_price( $the_price ) );
					$table_data[] = array( __( 'Buying', 'woocommerce-jetpack' ),  wc_price( $purchase_price ) );
					$table_data[] = array( __( 'Profit', 'woocommerce-jetpack' ),  wc_price( $the_profit )
						. sprintf( ' (%0.2f %%)', ( $this->get_profit_percentage(array(
							'profit'         => $the_profit,
							'purchase_price' => $purchase_price,
							'selling_price'  => $the_price
						)) ) ) );
					$html = '';
					$html .= '<h5>' . __( 'Report', 'woocommerce-jetpack' ) . $desc . '</h5>';
					$html .= wcj_get_table_html( $table_data, array(
						'table_heading_type' => 'none',
						'table_class'        => 'widefat striped',
						'table_style'        => 'width:50%;min-width:300px;',
						'columns_styles'     => array( 'width:33%;' ),
					) );
					echo $html;
				}
			}
		}
	}

	/**
	 * get_profit_percentage.
	 *
	 * @version 4.5.0
	 * @since   4.5.0
	 *
	 * @param array $args
	 *
	 * @return float|int
	 */
	function get_profit_percentage( $args = array() ) {
		$args  = wp_parse_args( $args, array(
			'percentage_type' => wcj_get_option( 'wcj_purchase_price_profit_percentage_type', 'markup' ),
			'profit'          => null,
			'selling_price'   => null,
			'purchase_price'  => null,
		) );
		$price = $args['purchase_price'];
		if ( 'margin' === $args['percentage_type'] ) {
			$price = $args['selling_price'];
		}
		return ( $args['profit'] / $price * 100 );
	}

}

endif;

return new WCJ_Purchase_Data();

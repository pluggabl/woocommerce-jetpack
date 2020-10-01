<?php
/**
 * Booster for WooCommerce - Module - Order Min/Max Quantities
 *
 * @version 4.4.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Order_Quantities' ) ) :

class WCJ_Order_Quantities extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.4.0
	 * @since   2.9.0
	 * @todo    [dev] maybe rename the module to "Order Product Quantities" or "Product Quantities"?
	 * @todo    [dev] loop (`woocommerce_loop_add_to_cart_link`)
	 * @todo    [dev] apply quantity **step per variation**
	 * @todo    [dev] (maybe) order quantities by user roles
	 * @todo    [dev] (maybe) validate (and optionally auto-correct) **on add to cart**
	 */
	function __construct() {

		$this->id         = 'order_quantities';
		$this->short_desc = __( 'Order Quantities', 'woocommerce-jetpack' );
		$this->desc       = __( 'Manage product quantities in WooCommerce order: set min, max, step; enable decimal quantities etc.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-order-min-max-quantities';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Validation
			if (
				'yes' === wcj_get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) || 'yes' === wcj_get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ||
				'yes' === wcj_get_option( 'wcj_order_quantities_step_section_enabled', 'no' )
			) {
				add_action( 'woocommerce_checkout_process', array( $this, 'check_order_quantities' ) );
				add_action( 'woocommerce_before_cart',      array( $this, 'check_order_quantities' ) );
				if ( 'yes' === wcj_get_option( 'wcj_order_quantities_stop_from_seeing_checkout', 'no' ) ) {
					add_action( 'wp', array( $this, 'stop_from_seeing_checkout' ), PHP_INT_MAX );
				}
			}
			// Min/max quantities
			if ( 'yes' === wcj_get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) || 'yes' === wcj_get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
				add_filter( 'woocommerce_available_variation', array( $this, 'set_quantity_input_min_max_variation' ), PHP_INT_MAX, 3 );
				if ( 'yes' === wcj_get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
					add_filter( 'woocommerce_quantity_input_min', array( $this, 'set_quantity_input_min' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === wcj_get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) ) {
					add_filter( 'woocommerce_quantity_input_max', array( $this, 'set_quantity_input_max' ), PHP_INT_MAX, 2 );
				}
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
			}
			// Quantity step
			if ( 'yes' === wcj_get_option( 'wcj_order_quantities_step_section_enabled', 'no' ) ) {
				add_filter( 'woocommerce_quantity_input_step', array( $this, 'set_quantity_input_step' ), PHP_INT_MAX, 2 );
			}
			// Meta box
			$this->is_min_per_product_enabled = ( 'yes' === wcj_get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) &&
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_quantities_min_per_item_quantity_per_product', 'no' ) ) );
			$this->is_max_per_product_enabled = ( 'yes' === wcj_get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) &&
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_quantities_max_per_item_quantity_per_product', 'no' ) ) );
			$this->is_step_per_product_enabled = ( 'yes' === wcj_get_option( 'wcj_order_quantities_step_section_enabled', 'no' ) &&
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_quantities_step_per_product', 'no' ) ) );
			if ( $this->is_min_per_product_enabled || $this->is_max_per_product_enabled || $this->is_step_per_product_enabled ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
			// Limit cart items (i.e. "Single Item Cart" Mode)
			if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_quantities_single_item_cart_enabled', 'no' ) ) ) {
				add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'single_item_cart' ), PHP_INT_MAX, 4 );
			}
			// For cart and `input_value`
			add_filter( 'woocommerce_quantity_input_args', array( $this, 'set_quantity_input_args' ), PHP_INT_MAX, 2 );
			// Handle Add to cart button on loop
			add_filter( 'woocommerce_loop_add_to_cart_args', array( $this, 'handle_wc_loop_add_to_cart_args' ), 10, 2 );
			add_action( 'wp_footer',                         array( $this, 'sync_qty_input_with_add_to_cart_btn_on_loop' ) );
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'replace_quantity_attribute_on_loop_cart_link' ), PHP_INT_MAX, 2 );
			// Decimal qty
			if ( 'yes' === wcj_get_option( 'wcj_order_quantities_decimal_qty_enabled', 'no' ) ) {
				add_action( 'init',                               array( $this, 'float_stock_amount' ) );
				add_action( 'woocommerce_quantity_input_pattern', array( $this, 'float_quantity_input_pattern' ) );
			}

			// Prevent outdated min/max Quantity Options
			add_action( 'woocommerce_update_product', array( $this, 'prevent_outdated_min_max' ), 10 );
		}
	}

	/**
	 * Prevents outdated min/max Quantity options.
	 *
	 * @version 4.4.0
	 * @since   4.4.0
	 * @param $product_id
	 */
	function prevent_outdated_min_max( $product_id ) {
		$product = wc_get_product( $product_id );
		if (
			! $product->is_type( 'variable' ) ||
			isset( $_POST['_wcj_order_quantities_min'] )
		) {
			return;
		}
		delete_post_meta( $product_id, '_wcj_order_quantities_min' );
	}

	/**
	 * float_quantity_input_pattern.
	 *
	 * @version 4.3.1
	 * @since   4.3.1
	 */
	function float_quantity_input_pattern( $pattern ) {
		return '[0-9.]*';
	}

	/**
	 * float_stock_amount.
	 *
	 * @version 4.2.0
	 * @since   4.2.0
	 */
	function float_stock_amount() {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter(    'woocommerce_stock_amount', 'floatval' );
	}

	/**
	 * Replaces quantity attribute on loop cart link.
	 *
	 * @version 4.2.0
	 * @since   4.2.0
	 */
	function replace_quantity_attribute_on_loop_cart_link( $html, $product ) {
		$quantity = $this->get_product_quantity( 'min', $product, 1 );
		$html     = preg_replace( '/(data\-quantity)=\"[0-9]*\"/i', 'data-quantity="' . $quantity . '"', $html );
		$html     .= "<script>var wcj_evt = new Event('wcj_add_to_cart_quantity');wcj_evt.prodID=" . $product->get_id() . ";wcj_evt.quantity=" . $quantity . ";window.dispatchEvent(wcj_evt);</script>";
		return $html;
	}

	/**
	 * Syncs Quantity input with Add to cart button on loop page.
	 *
	 * @version 4.2.0
	 * @since   4.1.0
	 */
	function sync_qty_input_with_add_to_cart_btn_on_loop() {
		if ( ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_product_taxonomy() ) {
			return;
		}
		$js = "
			var wcj_sqwb = {
				init: function () {
					var qtyInput = document.querySelectorAll('.quantity .qty');
					[].forEach.call(qtyInput, function (el) {
						if(!el.classList.contains('wcj-quantity')){
							var productWrapper = el.closest('.products .product');
							var addToCartBtn = productWrapper.querySelector('.ajax_add_to_cart');
							var dataProductIDAttr = addToCartBtn.getAttribute('data-product_id');
							var addToCartQty = addToCartBtn.getAttribute('data-quantity');
							el.value = addToCartQty;
							el.addEventListener('change', function() {
								if(!this.checkValidity()){
									addToCartBtn.removeAttribute('data-product_id');
									this.reportValidity();
								}else{
									wcj_sqwb.sync(this.value,addToCartBtn);
									addToCartBtn.setAttribute('data-product_id',dataProductIDAttr);
								}
							});
							el.classList.add('wcj-quantity');
						}
					});
				},
				sync:function(qty_value,addToCartBtn){
					addToCartBtn.setAttribute('data-quantity', qty_value);
				}
			};
			jQuery(document).ready(function(){
				wcj_sqwb.init();
			});
			window.addEventListener('wcj_add_to_cart_quantity',function(e){
				 wcj_sqwb.init();
			});
		";
		wc_enqueue_js( $js );
	}

	/**
	 * Handles arguments passed to add to cart loop.
	 *
	 * @version 4.1.0
	 * @since   4.1.0
	 *
	 * @param $args
	 * @param $product
	 *
	 * @return mixed
	 */
	function handle_wc_loop_add_to_cart_args( $args, $product ) {
		$args['quantity'] = $this->get_product_quantity( 'min', $product, $args['quantity'] );
		return $args;
	}

	/**
	 * set_quantity_input_args.
	 *
	 * @version 4.2.0
	 * @since   3.7.0
	 */
	function set_quantity_input_args( $args, $product ) {
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
			$args['min_value'] = $this->set_quantity_input_min( $args['min_value'], $product );
		}
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) ) {
			$args['max_value'] = $this->set_quantity_input_max( $args['max_value'], $product );
		}
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_step_section_enabled', 'no' ) ) {
			$args['step'] = $this->set_quantity_input_step( $args['step'], $product );
		}
		if ( 'disabled' != ( $force_on_single = wcj_get_option( 'wcj_order_quantities_force_on_single', 'disabled' ) ) && is_product() ) {
			$args['input_value'] = ( 'min' === $force_on_single ?
				$this->set_quantity_input_min( $args['min_value'], $product ) : $this->set_quantity_input_max( $args['max_value'], $product ) );
		}
		return $args;
	}

	/**
	 * set_quantity_input_step.
	 *
	 * @version 3.7.0
	 * @since   3.7.0
	 */
	function set_quantity_input_step( $qty, $product ) {
		if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_quantities_step_per_product', 'no' ) ) ) {
			if ( '' != ( $step = get_post_meta( wcj_get_product_id_or_variation_parent_id( $product ), '_' . 'wcj_order_quantities_step', true ) ) && 0 != $step ) {
				return $step;
			}
		}
		return ( 0 != ( $step = wcj_get_option( 'wcj_order_quantities_step', 1 ) ) ? $step : $qty );
	}

	/**
	 * single_item_cart.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function single_item_cart( $passed, $product_id, $quantity = 0, $variation_id = 0 ) {
		if ( ! WC()->cart->is_empty() ) {
			if ( is_array( WC()->cart->cart_contents ) && 1 == count( WC()->cart->cart_contents ) && wcj_is_product_in_cart( ( 0 != $variation_id ? $variation_id : $product_id ) ) ) {
				return $passed;
			} else {
				wc_add_notice( wcj_get_option( 'wcj_order_quantities_single_item_cart_message',
					__( 'Only one item can be added to the cart. Clear the cart or finish the order, before adding another item to the cart.', 'woocommerce-jetpack' ) ), 'error' );
				return false;
			}
		}
		return $passed;
	}

	/**
	 * enqueue_script.
	 *
	 * @version 3.2.3
	 * @since   3.2.2
	 * @todo    [dev] `force_on_add_to_cart` for simple products
	 * @todo    [dev] make this optional?
	 */
	function enqueue_script() {
		$_product = wc_get_product();
		if ( $_product && $_product->is_type( 'variable' ) ) {
			$quantities_options = array(
				'reset_to_min'         => ( 'reset_to_min' === wcj_get_option( 'wcj_order_quantities_variable_variation_change', 'do_nothing' ) ),
				'reset_to_max'         => ( 'reset_to_max' === wcj_get_option( 'wcj_order_quantities_variable_variation_change', 'do_nothing' ) ),
				'force_on_add_to_cart' => ( 'yes' === wcj_get_option( 'wcj_order_quantities_variable_force_on_add_to_cart', 'no' ) ),
			);
			$product_quantities = array();
			foreach ( $_product->get_available_variations() as $variation ) {
				$product_quantities[ $variation['variation_id'] ] = array(
					'min_qty' => $variation['min_qty'],
					'max_qty' => $variation['max_qty'],
				);
			}
			wp_enqueue_script(  'wcj-order-quantities',  trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-order-quantities.js', array( 'jquery' ), WCJ()->version, true );
			wp_localize_script( 'wcj-order-quantities', 'product_quantities', $product_quantities );
			wp_localize_script( 'wcj-order-quantities', 'quantities_options', $quantities_options );
		}
	}

	/**
	 * get_product_quantity.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function get_product_quantity( $min_or_max, $_product, $default_qty ) {
		if ( 'no' === wcj_get_option( 'wcj_order_quantities_' . $min_or_max . '_section_enabled', 'no' ) ) {
			return $default_qty;
		}
		if (
			'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity_per_product', 'no' ) ) &&
			0 != ( $max_or_max_per_item_quantity_per_product = get_post_meta( wcj_get_product_id( $_product ), '_' . 'wcj_order_quantities_' . $min_or_max, true ) )
		) {
			return $max_or_max_per_item_quantity_per_product;
		} elseif ( 0 != ( $max_or_max_per_item_quantity = apply_filters( 'booster_option', 0, wcj_get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity', 0 ) ) ) ) {
			return $max_or_max_per_item_quantity;
		} else {
			return $default_qty;
		}
	}

	/**
	 * set_quantity_input_min_max_variation.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function set_quantity_input_min_max_variation( $args, $_product, $_variation ) {
		$args['min_qty'] = $this->get_product_quantity( 'min', $_variation, $args['min_qty'] );
		$args['max_qty'] = $this->get_product_quantity( 'max', $_variation, $args['max_qty'] );
		$_max = $_variation->get_max_purchase_quantity();
		if ( -1 != $_max && $args['max_qty'] > $_max ) {
			$args['max_qty'] = $_max;
		}
		if ( $args['min_qty'] < 0 ) {
			$args['min_qty'] = '';
		}
		if ( $args['max_qty'] < 0 ) {
			$args['max_qty'] = '';
		}
		return $args;
	}

	/**
	 * set_quantity_input_min.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function set_quantity_input_min( $qty, $_product ) {
		if ( ! $_product->is_type( 'variable' ) ) {
			$min  = $this->get_product_quantity( 'min', $_product, $qty );
			$_max = $_product->get_max_purchase_quantity();
			return ( -1 == $_max || $min < $_max ? $min : $_max );
		} else {
			return $qty;
		}
	}

	/**
	 * set_quantity_input_max.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function set_quantity_input_max( $qty, $_product ) {
		if ( ! $_product->is_type( 'variable' ) ) {
			$max  = $this->get_product_quantity( 'max', $_product, $qty );
			$_max = $_product->get_max_purchase_quantity();
			return ( -1 == $_max || $max < $_max ? $max : $_max );
		} else {
			return $qty;
		}
	}

	/**
	 * stop_from_seeing_checkout.
	 *
	 * @version 4.4.0
	 * @since   2.9.0
	 */
	function stop_from_seeing_checkout() {
		if ( ! isset( WC()->cart ) ) {
			return;
		}
		if ( ! is_checkout() ) {
			return;
		}
		$cart_item_quantities = wcj_get_cart_item_quantities();
		if ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) ) {
			return;
		}
		$cart_total_quantity = array_sum( $cart_item_quantities );
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) ) {
			if ( ! $this->check_quantities( 'max', $cart_item_quantities, $cart_total_quantity, false, true ) ) {
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
			if ( ! $this->check_quantities( 'min', $cart_item_quantities, $cart_total_quantity, false, true ) ) {
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_step_section_enabled', 'no' ) ) {
			if ( ! $this->check_quantities_step( $cart_item_quantities, false, true ) ) {
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}
	}

	/**
	 * print_message.
	 *
	 * @version 4.2.0
	 * @since   2.9.0
	 */
	function print_message( $message_type, $_is_cart, $required_quantity, $total_quantity, $_product_id = 0 ) {
		if ( $_is_cart ) {
			if ( 'no' === wcj_get_option( 'wcj_order_quantities_cart_notice_enabled', 'no' ) ) {
				return;
			}
		}
		switch ( $message_type ) {
			case 'max_cart_total_quantity':
				$replaced_values = array(
					'%max_cart_total_quantity%' => $required_quantity,
					'%cart_total_quantity%'     => $total_quantity,
				);
				$message_template = wcj_get_option( 'wcj_order_quantities_max_cart_total_message',
					__( 'Maximum allowed order quantity is %max_cart_total_quantity%. Your current order quantity is %cart_total_quantity%.', 'woocommerce-jetpack' ) );
				break;
			case 'min_cart_total_quantity':
				$replaced_values = array(
					'%min_cart_total_quantity%' => $required_quantity,
					'%cart_total_quantity%'     => $total_quantity,
				);
				$message_template = wcj_get_option( 'wcj_order_quantities_min_cart_total_message',
					__( 'Minimum allowed order quantity is %min_cart_total_quantity%. Your current order quantity is %cart_total_quantity%.', 'woocommerce-jetpack' ) );
				break;
			case 'max_per_item_quantity':
				$_product = wc_get_product( $_product_id );
				$replaced_values = array(
					'%max_per_item_quantity%' => $required_quantity,
					'%item_quantity%'         => $total_quantity,
					'%product_title%'         => $_product->get_title(),
				);
				$message_template = wcj_get_option( 'wcj_order_quantities_max_per_item_message',
					__( 'Maximum allowed quantity for %product_title% is %max_per_item_quantity%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ) );
				break;
			case 'min_per_item_quantity':
				$_product = wc_get_product( $_product_id );
				$replaced_values = array(
					'%min_per_item_quantity%' => $required_quantity,
					'%item_quantity%'         => $total_quantity,
					'%product_title%'         => $_product->get_title(),
				);
				$message_template = wcj_get_option( 'wcj_order_quantities_min_per_item_message',
					__( 'Minimum allowed quantity for %product_title% is %min_per_item_quantity%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ) );
				break;
			case 'step':
				$_product = wc_get_product( $_product_id );
				$replaced_values = array(
					'%required_step%'         => $required_quantity,
					'%item_quantity%'         => $total_quantity,
					'%product_title%'         => $_product->get_title(),
				);
				$message_template = wcj_get_option( 'wcj_order_quantities_step_message',
					__( 'Required step for %product_title% is %required_step%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ) );
				break;
		}
		$_notice = str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), $message_template );
		if ( $_is_cart ) {
			wc_print_notice( $_notice, 'notice' );
		} else {
			wc_add_notice( $_notice, 'error' );
		}
	}

	/**
	 * check_step.
	 *
	 * @version 4.2.0
	 * @since   4.2.0
	 */
	function check_step( $product_id, $product_qty_step, $quantity ) {
		$min_value = $this->get_product_quantity( 'min', wc_get_product( $product_id ), 0 );
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_decimal_qty_enabled', 'no' ) ) {
			$multiplier         = floatval( 1000000 );
			$_min_value         = intval( round( floatval( $min_value )        * $multiplier ) );
			$_quantity          = intval( round( floatval( $quantity )         * $multiplier ) );
			$_product_qty_step  = intval( round( floatval( $product_qty_step ) * $multiplier ) );
		} else {
			$_min_value         = $min_value;
			$_quantity          = $quantity;
			$_product_qty_step  = $product_qty_step;
		}
		$_quantity = $_quantity - $_min_value;
		$_reminder = $_quantity % $_product_qty_step;
		return ( 0 == $_reminder );
	}

	/**
	 * check_quantities_step.
	 *
	 * @version 4.4.0
	 * @since   4.2.0
	 */
	function check_quantities_step( $cart_item_quantities, $_is_cart, $_return ) {
		if ( 'yes' != wcj_get_option( 'wcj_order_quantities_step_additional_validation_enabled', 'no' ) ) {
			return true;
		}
		if ( $this->is_step_per_product_enabled ) {
			foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
				$step    = get_post_meta( $_product_id, '_' . 'wcj_order_quantities_step', true );
				$product = empty( $step ) ? wc_get_product( $_product_id ) : null;
				if (
					empty( $step ) &&
					$product &&
					$product->get_type() == 'variation' &&
					! empty( $product->get_parent_id() )
				) {
					$step = get_post_meta( $product->get_parent_id(), '_' . 'wcj_order_quantities_step', true );
				}
				if ( '' != $step && 0 != $step ) {
					if ( ! $this->check_step( $_product_id, $step, $cart_item_quantity ) ) {
						if ( $_return ) {
							return false;
						} else {
							$this->print_message( 'step', $_is_cart, $step, $cart_item_quantity, $_product_id );
						}
					}
				}
			}
		}
		if (
			empty( $step ) &&
			0 != ( $step = wcj_get_option( 'wcj_order_quantities_step', 1 ) )
		) {
			foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
				if ( $this->is_step_per_product_enabled && 0 != get_post_meta( $_product_id, '_' . 'wcj_order_quantities_step', true ) ) {
					continue;
				}
				if ( ! $this->check_step( $_product_id, $step, $cart_item_quantity ) ) {
					if ( $_return ) {
						return false;
					} else {
						$this->print_message( 'step', $_is_cart, $step, $cart_item_quantity, $_product_id );
					}
				}
			}
		}
		if ( $_return ) {
			return true;
		}
	}

	/**
	 * check_quantities.
	 *
	 * @version 4.2.0
	 * @since   2.9.0
	 */
	function check_quantities( $min_or_max, $cart_item_quantities, $cart_total_quantity, $_is_cart, $_return ) {
		if ( 0 != ( $min_or_max_cart_total_quantity = wcj_get_option( 'wcj_order_quantities_' . $min_or_max . '_cart_total_quantity', 0 ) ) ) {
			if (
				( 'max' === $min_or_max && $cart_total_quantity > $min_or_max_cart_total_quantity ) ||
				( 'min' === $min_or_max && $cart_total_quantity < $min_or_max_cart_total_quantity )
			) {
				if ( $_return ) {
					return false;
				} else {
					$this->print_message( $min_or_max . '_cart_total_quantity', $_is_cart, $min_or_max_cart_total_quantity, $cart_total_quantity );
				}
			}
		}
		if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity_per_product', 'no' ) ) ) {
			foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
				if ( 0 != ( $max_or_max_per_item_quantity = get_post_meta( $_product_id, '_' . 'wcj_order_quantities_' . $min_or_max, true ) ) ) {
					if (
						( 'max' === $min_or_max && $cart_item_quantity > $max_or_max_per_item_quantity ) ||
						( 'min' === $min_or_max && $cart_item_quantity < $max_or_max_per_item_quantity )
					) {
						if ( $_return ) {
							return false;
						} else {
							$this->print_message( $min_or_max . '_per_item_quantity', $_is_cart, $max_or_max_per_item_quantity, $cart_item_quantity, $_product_id );
						}
					}
				}
			}
		}
		if ( 0 != ( $max_or_max_per_item_quantity = apply_filters( 'booster_option', 0, wcj_get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity', 0 ) ) ) ) {
			foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
				if (
					'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity_per_product', 'no' ) ) &&
					0 != get_post_meta( $_product_id, '_' . 'wcj_order_quantities_' . $min_or_max, true )
				) {
					continue;
				}
				if (
					( 'max' === $min_or_max && $cart_item_quantity > $max_or_max_per_item_quantity ) ||
					( 'min' === $min_or_max && $cart_item_quantity < $max_or_max_per_item_quantity )
				) {
					if ( $_return ) {
						return false;
					} else {
						$this->print_message( $min_or_max . '_per_item_quantity', $_is_cart, $max_or_max_per_item_quantity, $cart_item_quantity, $_product_id );
					}
				}
			}
		}
		if ( $_return ) {
			return true;
		}
	}

	/**
	 * check_order_quantities.
	 *
	 * @version 4.4.0
	 * @since   2.9.0
	 */
	function check_order_quantities() {
		if ( ! isset( WC()->cart ) ) {
			return;
		}
		$cart_item_quantities = wcj_get_cart_item_quantities();
		if ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) ) {
			return;
		}
		$cart_total_quantity = array_sum( $cart_item_quantities );
		$_is_cart = is_cart();
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) ) {
			$this->check_quantities( 'max', $cart_item_quantities, $cart_total_quantity, $_is_cart, false );
		}
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
			$this->check_quantities( 'min', $cart_item_quantities, $cart_total_quantity, $_is_cart, false );
		}
		if ( 'yes' === wcj_get_option( 'wcj_order_quantities_step_section_enabled', 'no' ) ) {
			$this->check_quantities_step( $cart_item_quantities, $_is_cart, false );
		}
	}

}

endif;

return new WCJ_Order_Quantities();

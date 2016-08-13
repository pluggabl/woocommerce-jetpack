<?php
/**
 * WooCommerce Jetpack Product Addons
 *
 * The WooCommerce Jetpack Product Addons class.
 *
 * @version 2.5.5
 * @since   2.5.3
 * @author  Algoritmika Ltd.
 * @todo    admin order view (names);
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Addons' ) ) :

class WCJ_Product_Addons extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function __construct() {

		$this->id         = 'product_addons';
		$this->short_desc = __( 'Product Addons', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add (paid/free/discount) addons to WooCommerce products.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-addons/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_product_addons_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',          array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product',       array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices',           array( $this, 'admin_notices' ) );
				$this->co = 'wcj_product_addons_per_product_settings_enabled';
			}
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( 'yes' === get_option( 'wcj_product_addons_ajax_enabled', 'no' ) ) {
					// Scripts
					add_action( 'wp_enqueue_scripts',                         array( $this, 'enqueue_scripts' ) );
					add_action( 'wp_ajax_product_addons_price_change',        array( $this, 'price_change_ajax' ) );
					add_action( 'wp_ajax_nopriv_product_addons_price_change', array( $this, 'price_change_ajax' ) );
				}
				// Single Page
				add_action( 'woocommerce_before_add_to_cart_button',      array( $this, 'add_addons_to_frontend' ), PHP_INT_MAX );
				// Add to cart
				add_filter( 'woocommerce_add_cart_item_data',             array( $this, 'add_addons_price_to_cart_item_data' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_add_cart_item',                  array( $this, 'add_addons_price_to_cart_item' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_cart_item_from_session',     array( $this, 'get_cart_item_addons_price_from_session' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_add_to_cart_validation',         array( $this, 'validate_on_add_to_cart' ), PHP_INT_MAX, 2 );
				// Prices
				add_filter( 'woocommerce_get_price',                      array( $this, 'change_price' ), PHP_INT_MAX - 100, 2 );
				// Show details at cart, order details, emails
				add_filter( 'woocommerce_cart_item_name',                 array( $this, 'add_info_to_cart_item_name' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_order_item_name',                array( $this, 'add_info_to_order_item_name' ), PHP_INT_MAX, 2 );
				add_action( 'woocommerce_add_order_item_meta',            array( $this, 'add_info_to_order_item_meta' ), PHP_INT_MAX, 3 );
			}
		}
	}

	/**
	 * validate_on_add_to_cart.
	 *
	 * @version 2.5.5
	 * @since   2.5.5
	 */
	function validate_on_add_to_cart( $passed, $product_id ) {
		$addons = $this->get_product_addons( $product_id );
		foreach ( $addons as $addon ) {
			if ( 'yes' === $addon['is_required'] ) {
				if ( ! isset( $_POST[ $addon['checkbox_key'] ] ) ) {
					wc_add_notice( __( 'Some of the required addons are not selected!', 'woocommerce-jetpack' ), 'error' );
					return false;
				}
			}
		}
		return $passed;
	}

	/**
	 * get_the_notice.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_the_notice() {
		return __( 'Booster: Free plugin\'s version is limited to only three products with per product addons enabled at a time. You will need to get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to add unlimited number of products with per product addons.', 'woocommerce-jetpack' );
	}

	/**
	 * price_change_ajax.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function price_change_ajax( $param ) {
		$the_product = wc_get_product( $_POST['product_id'] );
		$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
		$the_price = $the_product->$get_price_method();
		$parent_product_id = ( $the_product->is_type( 'variation' ) ) ? wp_get_post_parent_id( $_POST['product_id'] ) : $_POST['product_id'];
		$addons = $this->get_product_addons( $parent_product_id );
		$the_addons_price = 0;
		foreach ( $addons as $addon ) {
			if ( isset( $_POST[ $addon['checkbox_key'] ] ) ) {
				if ( 'checkbox' === $addon['type'] || '' == $addon['type'] ) {
					$the_addons_price += $addon['price_value'];
				} elseif ( 'radio' === $addon['type'] ) {
					$labels = explode( PHP_EOL, $addon['label_value'] );
					$prices = explode( PHP_EOL, $addon['price_value'] );
					if ( count( $labels ) === count( $prices ) ) {
						foreach ( $labels as $i => $label ) {
							if ( $_POST[ $addon['checkbox_key'] ] == sanitize_title( $label ) ) {
								$the_addons_price += $prices[ $i ];
								break;
							}
						}
					}
				}
			}
		}
		if ( 0 != $the_addons_price ) {
			echo wc_price( $the_price + $the_addons_price );
		} else {
			echo $the_product->get_price_html();
		}
		wp_die();
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function enqueue_scripts() {
		if ( is_product() ) {
			$the_product = wc_get_product();
			$addons = $this->get_product_addons( $the_product->id );
			if ( ! empty( $addons ) ) {
				wp_enqueue_script(  'wcj-product-addons', wcj_plugin_url() . '/includes/js/wcj-product-addons.js', array(), false, true );
				wp_localize_script( 'wcj-product-addons', 'ajax_object', array(
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'product_id'          => get_the_ID(),
				) );
			}
		}
	}

	/**
	 * get_product_addons.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function get_product_addons( $product_id ) {
		$addons = array();
		// All Products
		if ( 'yes' === get_option( 'wcj_product_addons_all_products_enabled', 'no' ) ) {
			$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === get_option( 'wcj_product_addons_all_products_enabled_' . $i, 'yes' ) ) {
					$addons[] = array(
//						'scope'        => 'all_products',
//						'index'        => $i,
						'checkbox_key' => 'wcj_product_all_products_addons_' . $i,
						'price_key'    => 'wcj_product_all_products_addons_price_' . $i,
						'label_key'    => 'wcj_product_all_products_addons_label_' . $i,
						'price_value'  => get_option( 'wcj_product_addons_all_products_price_' . $i ),
						'label_value'  => get_option( 'wcj_product_addons_all_products_label_' . $i ),
						'tooltip'      => get_option( 'wcj_product_addons_all_products_tooltip_' . $i, '' ),
						'type'         => get_option( 'wcj_product_addons_all_products_type_' . $i, 'checkbox' ),
						'default'      => get_option( 'wcj_product_addons_all_products_default_' . $i, '' ),
						'is_required'  => get_option( 'wcj_product_addons_all_products_required_' . $i, 'no' ),
					);
				}
			}
		}
		// Per product
		if ( 'yes' === get_option( 'wcj_product_addons_per_product_enabled', 'no' ) ) {
			if ( 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_settings_enabled', true ) ) {
				$total_number = get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_total_number', true );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_enabled_' . $i, true ) ) {
						$addons[] = array(
//							'scope'        => 'per_product',
//							'index'        => $i,
							'checkbox_key' => 'wcj_product_per_product_addons_' . $i,
							'price_key'    => 'wcj_product_per_product_addons_price_' . $i,
							'label_key'    => 'wcj_product_per_product_addons_label_' . $i,
							'price_value'  => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_price_' . $i, true ),
							'label_value'  => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_label_' . $i, true ),
							'tooltip'      => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_tooltip_' . $i, true ),
							'type'         => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_type_' . $i, true ),
							'default'      => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_default_' . $i, true ),
							'is_required'  => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_required_' . $i, true ),
						);
					}
				}
			}
		}
		return $addons;
	}

	/**
	 * add_info_to_order_item_meta.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_info_to_order_item_meta( $item_id, $values, $cart_item_key  ) {
		$addons = $this->get_product_addons( $values['product_id'] );
		foreach ( $addons as $addon ) {
			if ( isset( $values[ $addon['price_key'] ] ) ) {
				wc_add_order_item_meta( $item_id, '_' . $addon['price_key'], $values[ $addon['price_key'] ] );
				wc_add_order_item_meta( $item_id, '_' . $addon['label_key'], $values[ $addon['label_key'] ] );
			}
		}
	}

	/**
	 * Adds info to order details (and emails).
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_info_to_order_item_name( $name, $item, $is_cart = false ) {
		if ( $is_cart ) {
			$name .= '<dl class="variation">';
		}
		$addons = $this->get_product_addons( $item['product_id'] );
		foreach ( $addons as $addon ) {
			if ( isset( $item[ $addon['price_key'] ] ) ) {
				if ( $is_cart ) {
					$name .= '<dt>' . $item[ $addon['label_key'] ] . ':' . '</dt>';
					$name .= '<dd>' . wc_price( $item[ $addon['price_key'] ] ) . '</dd>';
				} else {
					$name .= ' | ' . $item[ $addon['label_key'] ] . ': ' . wc_price( $item[ $addon['price_key'] ] );
				}
			}
		}
		if ( $is_cart ) {
			$name .= '</dl>';
		}
		return $name;
	}

	/**
	 * Adds info to cart item details.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_info_to_cart_item_name( $name, $cart_item, $cart_item_key  ) {
		return $this->add_info_to_order_item_name( $name, $cart_item, true );
	}

	/**
	 * change_price.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function change_price( $price, $_product ) {
		$addons = $this->get_product_addons( $_product->id );
		foreach ( $addons as $addon ) {
			if ( isset( $_product->$addon['price_key'] ) ) {
				$price += $_product->$addon['price_key'];
			}
		}
		return $price;
	}

	/**
	 * add_addons_price_to_cart_item.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_addons_price_to_cart_item( $cart_item_data, $cart_item_key ) {
		$addons = $this->get_product_addons( $cart_item_data['data']->product_id );
		foreach ( $addons as $addon ) {
			if ( isset( $cart_item_data[ $addon['price_key'] ] ) ) {
				$cart_item_data['data']->$addon['price_key'] = $cart_item_data[ $addon['price_key'] ];
			}
		}
		return $cart_item_data;
	}

	/**
	 * get_cart_item_addons_price_from_session.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_cart_item_addons_price_from_session( $item, $values, $addon ) {
		$addons = $this->get_product_addons( $item['product_id'] );
		foreach ( $addons as $addon ) {
			if ( array_key_exists( $addon['price_key'], $values ) ) {
				$item['data']->$addon['price_key'] = $values[ $addon['price_key'] ];
			}
		}
		return $item;
	}

	/**
	 * add_addons_price_to_cart_item_data.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function add_addons_price_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$addons = $this->get_product_addons( $product_id );
		foreach ( $addons as $addon ) {
			if ( isset( $_POST[ $addon['checkbox_key'] ] ) ) {
				if ( 'checkbox' === $addon['type'] || '' == $addon['type'] ) {
					$cart_item_data[ $addon['price_key'] ] = $addon['price_value'];
					$cart_item_data[ $addon['label_key'] ] = $addon['label_value'];
				} elseif ( 'radio' === $addon['type'] ) {
					$prices = explode( PHP_EOL, $addon['price_value'] );
					$labels = explode( PHP_EOL, $addon['label_value'] );
					if ( count( $labels ) === count( $prices ) ) {
						foreach ( $labels as $i => $label ) {
							if ( $_POST[ $addon['checkbox_key'] ] == sanitize_title( $label ) ) {
								$cart_item_data[ $addon['price_key'] ] = $prices[ $i ];
								$cart_item_data[ $addon['label_key'] ] = $labels[ $i ];
								break;
							}
						}
					}
				}
			}
		}
		return $cart_item_data;
	}

	/**
	 * add_addons_to_frontend.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function add_addons_to_frontend() {
		$html = '';
		$addons = $this->get_product_addons( get_the_ID() );
		foreach ( $addons as $addon ) {
			$is_required = ( 'yes' === $addon['is_required'] ) ? ' required' : '';
			if ( 'checkbox' === $addon['type'] || '' == $addon['type'] ) {
				$is_checked = '';
				if ( isset( $_POST[ $addon['checkbox_key'] ] ) ) {
					$is_checked = ' checked';
				} elseif ( 'checked' === $addon['default'] ) {
					$is_checked = ' checked';
				}
				$maybe_tooltip = ( '' != $addon['tooltip'] ) ?
					' <img style="display:inline;" class="wcj-question-icon" src="' . wcj_plugin_url() . '/assets/images/question-icon.png' . '" title="' . $addon['tooltip'] . '">' :
					'';
				$html .= '<p>' .
						'<input type="checkbox" id="' . $addon['checkbox_key'] . '" name="' . $addon['checkbox_key'] . '"' . $is_checked . $is_required . '>' . ' ' .
						'<label for="' . $addon['checkbox_key'] . '">' . $addon['label_value'] . ' ('. wc_price( $addon['price_value'] ) . ')' . '</label>' .
						$maybe_tooltip .
					'</p>';
			} elseif ( 'radio' === $addon['type'] ) {
				$prices   = explode( PHP_EOL, $addon['price_value'] );
				$labels   = explode( PHP_EOL, $addon['label_value'] );
				$tooltips = explode( PHP_EOL, $addon['tooltip'] );
				if ( count( $labels ) === count( $prices ) ) {
					foreach ( $labels as $i => $label ) {
						$label = sanitize_title( $label );
						$is_checked = '';
						if ( isset( $_POST[ $addon['checkbox_key'] ] ) ) {
							$is_checked = ( $label === $_POST[ $addon['checkbox_key'] ] ) ? ' checked' : '';
						} elseif ( '' != $addon['default'] ) {
							$is_checked = ( $label === sanitize_title( $addon['default'] ) ) ? ' checked' : '';
						}
						$maybe_tooltip = ( isset( $tooltips[ $i ] ) && '' != $tooltips[ $i ] ) ?
							' <img style="display:inline;" class="wcj-question-icon" src="' . wcj_plugin_url() . '/assets/images/question-icon.png' . '" title="' . $tooltips[ $i ] . '">' :
							'';
						$html .= '<p>' .
							'<input type="radio" id="' . $addon['checkbox_key'] . '-' . $label . '" name="' . $addon['checkbox_key'] . '" value="' . $label . '"' . $is_checked . $is_required . '>' . ' ' .
							'<label for="' . $addon['checkbox_key'] . '-' . $label . '">' . $labels[ $i ] . ' ('. wc_price( $prices[ $i ] ) . ')' . '</label>' .
							$maybe_tooltip .
						'</p>';
					}
				}
			}
		}
		// Output
		if ( ! empty( $html ) ) {
			echo '<div id="wcj_product_addons">' . $html . '</div>';
		}
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_product_addons_per_product_settings_enabled',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Enabled', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_product_addons_per_product_total_number',
				'tooltip'    => __( 'Save product after you change this number.', 'woocommerce-jetpack' ),
				'default'    => 0,
				'type'       => 'number',
				'title'      => __( 'Product Addons Total Number', 'woocommerce-jetpack' ),
			),
		);
		$total_number = get_post_meta( get_the_ID(), '_' . 'wcj_product_addons_per_product_total_number', true );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$options = array_merge( $options, array(
				array(
					'title'    => __( 'Product Addon', 'woocommerce-jetpack' ) . ' #' . $i . ' - ' . __( 'Enable', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_enabled_' . $i,
					'default'  => 'yes',
					'type'     => 'select',
					'options'  => array(
						'yes' => __( 'Yes', 'woocommerce-jetpack' ),
						'no'  => __( 'No', 'woocommerce-jetpack' ),
					),
				),
				array(
					'title'    => __( 'Type', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_type_' . $i,
					'default'  => 'checkbox',
					'type'     => 'select',
					'options'  => array(
						'checkbox' => __( 'Checkbox', 'woocommerce-jetpack' ),
						'radio'    => __( 'Radio Buttons', 'woocommerce-jetpack' ),
					),
				),
				array(
					'title'    => __( 'Label(s)', 'woocommerce-jetpack' ),
					'tooltip'  => __( 'For radio enter one value per line.', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_label_' . $i,
					'default'  => '',
					'type'     => 'textarea',
				),
				array(
					'title'    => __( 'Price(s)', 'woocommerce-jetpack' ),
					'tooltip'  => __( 'For radio enter one value per line.', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_price_' . $i,
					'default'  => 0,
					'type'     => 'textarea',
				),
				array(
					'title'    => __( 'Tooltip(s)', 'woocommerce-jetpack' ),
					'tooltip'  => __( 'For radio enter one value per line.', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_tooltip_' . $i,
					'default'  => '',
					'type'     => 'textarea',
				),
				array(
					'title'    => __( 'Default Value', 'woocommerce-jetpack' ),
					'tooltip'  => __( 'For checkbox use \'checked\'; for radio enter default label. Leave blank for no default value.', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_default_' . $i,
					'default'  => '',
					'type'     => 'text',
				),
				array(
					'title'    => __( 'Is required', 'woocommerce-jetpack' ),
					'name'     => 'wcj_product_addons_per_product_required_' . $i,
					'default'  => 'no',
					'type'     => 'select',
					'options'  => array(
						'yes' => __( 'Yes', 'woocommerce-jetpack' ),
						'no'  => __( 'No', 'woocommerce-jetpack' ),
					),
				),
			) );
		}
		return $options;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function get_settings() {
		$settings = array();
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Per Product Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_addons_per_product_options',
			),
			array(
				'title'    => __( 'Enable per Product Addons', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'When enabled, this will add new "Booster: Product Addons" meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_addons_per_product_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_addons_per_product_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'All Product Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_addons_all_products_options',
			),
			array(
				'title'    => __( 'Enable All Products Addons', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'When enabled, this will add addons below to all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_addons_all_products_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Product Addons Total Number', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Save changes after you change this number.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_addons_all_products_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '0', )
				),
			),
		) );
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Product Addon', 'woocommerce-jetpack' ) . ' #' . $i,
					'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_enabled_' . $i,
					'default'  => 'yes',
					'type'     => 'checkbox',
				),
				array(
					'desc'     => __( 'Type', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_type_' . $i,
					'default'  => 'checkbox',
					'type'     => 'select',
					'css'      => 'width:300px;',
					'options'  => array(
						'checkbox' => __( 'Checkbox', 'woocommerce-jetpack' ),
						'radio'    => __( 'Radio Buttons', 'woocommerce-jetpack' ),
					),
				),
				array(
					'desc'     => __( 'Label(s)', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'For radio enter one value per line.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_label_' . $i,
					'default'  => '',
					'type'     => 'textarea',
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Price(s)', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'For radio enter one value per line.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_price_' . $i,
					'default'  => 0,
					'type'     => 'textarea',
					'css'      => 'width:300px;',
					'custom_attributes' => array( 'step' => '0.0001' ),
				),
				array(
					'desc'     => __( 'Tooltip(s)', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'For radio enter one value per line.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_tooltip_' . $i,
					'default'  => '',
					'type'     => 'textarea',
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Default Value', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'For checkbox use \'checked\'; for radio enter default label. Leave blank for no default value.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_default_' . $i,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Is Required', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_addons_all_products_required_' . $i,
					'default'  => 'no',
					'type'     => 'checkbox',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_addons_all_products_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_addons_options',
			),
			array(
				'title'    => __( 'Enable AJAX on Single Product Page', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_addons_ajax_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_addons_options',
			),
		) );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Addons();

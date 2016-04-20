<?php
/**
 * WooCommerce Jetpack Custom Shipping
 *
 * The WooCommerce Jetpack Custom Shipping class.
 *
 * @version 2.4.8
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

add_action( 'woocommerce_shipping_init', 'init_wc_shipping_wcj_custom_class' );

function init_wc_shipping_wcj_custom_class() {

	if ( ! class_exists( 'WC_Shipping_WCJ_Custom_1' ) ) {

		class WC_Shipping_WCJ_Custom_1 extends WC_Shipping_Method {

			/**
			 * Constructor shipping class
			 *
			 * @access public
			 * @return void
			 */
			function __construct() {
				$id_count = 1;
				$this->id                 = 'booster_custom_shipping_' . $id_count;
				$this->method_title       = __( 'Custom Shipping', 'woocommerce-jetpack' ) . ' #' . $id_count . ' [' . __( 'Beta', 'woocommerce-jetpack' ) . ']';
				$this->method_description = __( 'Booster: Custom Shipping Method', 'woocommerce-jetpack' ) . ' #' . $id_count;
				$this->init();

				// Save settings in admin
				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			}

			/**
			 * Init settings
			 *
			 * @access public
			 * @return void
			 */
			function init() {
				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();

				// Define user set variables
				$this->enabled  = $this->get_option( 'enabled' );
				$this->title    = $this->get_option( 'title' );
				$this->cost     = $this->get_option( 'cost' );
				$this->type     = $this->get_option( 'type' );
			}

			/**
			 * Initialise Settings Form Fields
			 */
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'woocommerce' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable Custom Shipping', 'woocommerce-jetpack' ),
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
						'default'     => __( 'Custom Shipping', 'woocommerce-jetpack' ),
						'desc_tip'    => true,
					),
					'type' => array(
						'title'       => __( 'Type', 'woocommerce' ),
						'type'        => 'select',
						'description' => __( 'Cost calculation type.', 'woocommerce-jetpack' ),
						'default'     => 'flat_rate',
						'desc_tip'    => true,
						'options'     => array(
							'flat_rate'            => __( 'Flat Rate', 'woocommerce-jetpack' ),
							'by_total_cart_weight' => __( 'By Total Cart Weight', 'woocommerce-jetpack' ),
						),
					),
					'cost' => array(
						'title'       => __( 'Cost', 'woocommerce' ),
						'type'        => 'number',
						'description' => __( 'Cost. If calculating by weight - then cost per one weight unit.', 'woocommerce-jetpack' ),
						'default'     => 0,
						'desc_tip'    => true,
						'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
					),
				);
			}

			/**
			 * calculate_shipping function.
			 *
			 * @access public
			 * @param mixed $package
			 * @return void
			 */
			function calculate_shipping( $package ) {
				switch ( $this->type ) {
					case 'by_total_cart_weight':
						$cost = $this->cost * WC()->cart->cart_contents_weight;
						break;
					default: // 'flat_rate'
						$cost = $this->cost;
						break;
				}
				$rate = array(
					'id'       => $this->id,
					'label'    => $this->title,
					'cost'     => $cost,
					'calc_tax' => 'per_order',
				);
				// Register the rate
				$this->add_rate( $rate );
			}
		}
	}
}

function add_wc_shipping_wcj_custom_class( $methods ) {
	$methods[] = 'WC_Shipping_WCJ_Custom_1';
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_wc_shipping_wcj_custom_class' );

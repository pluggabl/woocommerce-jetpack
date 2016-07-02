<?php
/**
 * WooCommerce Jetpack Custom Shipping
 *
 * The WooCommerce Jetpack Custom Shipping class.
 *
 * @version 2.5.3
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

add_action( 'woocommerce_shipping_init', 'init_wc_shipping_wcj_custom_class' );

if ( ! function_exists( 'init_wc_shipping_wcj_custom_class' ) ) {

	function init_wc_shipping_wcj_custom_class() {

		if ( class_exists( 'WC_Shipping_Method' ) ) {

			class WC_Shipping_WCJ_Custom_Template extends WC_Shipping_Method {

				/**
				 * Constructor shipping class
				 *
				 * @access public
				 * @return void
				 */
				function __construct() {
				}

				/**
				 * Init settings
				 *
				 * @version 2.5.2
				 * @access  public
				 * @return  void
				 */
				function init( $id_count ) {

					$this->id                 = 'booster_custom_shipping_' . $id_count;
					$this->method_title       = get_option( 'wcj_shipping_custom_shipping_admin_title_' . $id_count, __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $id_count );
					$this->method_description = __( 'Booster: Custom Shipping Method', 'woocommerce-jetpack' ) . ' #' . $id_count;

					// Load the settings.
					$this->init_form_fields();
					$this->init_settings();

					// Define user set variables
					$this->enabled  = $this->get_option( 'enabled' );
					$this->title    = $this->get_option( 'title' );
					$this->cost     = $this->get_option( 'cost' );
					$this->type     = $this->get_option( 'type' );
					$this->weight_table_total_rows = $this->get_option( 'weight_table_total_rows' );
					for ( $i = 1; $i <= $this->weight_table_total_rows; $i++ ) {
						$option_name = 'weight_table_weight_row_' . $i;
						$this->$option_name = $this->get_option( $option_name );
						$option_name = 'weight_table_cost_row_' . $i;
						$this->$option_name = $this->get_option( $option_name );
					}

					// Save settings in admin
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				/**
				 * Initialise Settings Form Fields
				 *
				 * @version 2.5.2
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
								'flat_rate'                  => __( 'Flat Rate', 'woocommerce-jetpack' ),
								'by_total_cart_weight'       => __( 'By Total Cart Weight', 'woocommerce-jetpack' ),
								'by_total_cart_weight_table' => __( 'By Total Cart Weight Table', 'woocommerce-jetpack' ),
								'by_total_cart_quantity'     => __( 'By Total Cart Quantity', 'woocommerce-jetpack' ),
							),
						),
						'cost' => array(
							'title'       => __( 'Cost', 'woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Cost. If calculating by weight - then cost per one weight unit. If calculating by quantity - then cost per one piece.', 'woocommerce-jetpack' ),
							'default'     => 0,
							'desc_tip'    => true,
							'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
						),
						'weight_table_total_rows' => array(
							'title'       => __( 'Weight Table Total Rows', 'woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Press Save changes after you change this number.', 'woocommerce-jetpack' ),
							'default'     => 0,
							'desc_tip'    => true,
							'custom_attributes' => array( 'min'  => '0', ),
						),
					);
					for ( $i = 1; $i <= $this->get_option( 'weight_table_total_rows' ); $i++ ) {
						$this->form_fields = array_merge( $this->form_fields, array(
							'weight_table_weight_row_' . $i => array(
								'title'       => __( 'Max Weight', 'woocommerce' ) . ' #' . $i,
								'type'        => 'number',
								'default'     => 0,
								'desc_tip'    => true,
								'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
							),
							'weight_table_cost_row_' . $i => array(
								'title'       => __( 'Cost', 'woocommerce' ) . ' #' . $i,
								'type'        => 'number',
								'default'     => 0,
								'desc_tip'    => true,
								'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
							),
						) );
					}
				}

				/**
				 * calculate_shipping_by_weight_table.
				 *
				 * @version 2.5.2
				 * @since   2.5.2
				 */
				function calculate_shipping_by_weight_table( $weight ) {
					if ( 0 == $this->weight_table_total_rows ) {
						return $this->cost * $weight; // fallback
					}
					$option_name_weight = $option_name_cost = '';
					for ( $i = 1; $i <= $this->weight_table_total_rows; $i++ ) {
						$option_name_weight = 'weight_table_weight_row_' . $i;
						$option_name_cost = 'weight_table_cost_row_' . $i;
						if ( $weight <= $this->$option_name_weight ) {
							return $this->$option_name_cost;
						}
					}
					return $this->$option_name_cost; // fallback - last row
				}

				/**
				 * calculate_shipping function.
				 *
				 * @version 2.5.3
				 * @access  public
				 * @param   mixed $package
				 * @return  void
				 */
				function calculate_shipping( $package = array() ) {
					switch ( $this->type ) {
						case 'by_total_cart_quantity':
							$cart_quantity = 0;
							foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
								$cart_quantity += $values['quantity'];
							}
							$cost = $this->cost * $cart_quantity;
							break;
						case 'by_total_cart_weight':
							$cost = $this->cost * WC()->cart->get_cart_contents_weight();
							break;
						case 'by_total_cart_weight_table':
							$cost = $this->calculate_shipping_by_weight_table( WC()->cart->get_cart_contents_weight() );
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

			class WC_Shipping_WCJ_Custom_1  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 1 );  } }
			class WC_Shipping_WCJ_Custom_2  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 2 );  } }
			class WC_Shipping_WCJ_Custom_3  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 3 );  } }
			class WC_Shipping_WCJ_Custom_4  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 4 );  } }
			class WC_Shipping_WCJ_Custom_5  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 5 );  } }
			class WC_Shipping_WCJ_Custom_6  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 6 );  } }
			class WC_Shipping_WCJ_Custom_7  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 7 );  } }
			class WC_Shipping_WCJ_Custom_8  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 8 );  } }
			class WC_Shipping_WCJ_Custom_9  extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 9 );  } }
			class WC_Shipping_WCJ_Custom_10 extends WC_Shipping_WCJ_Custom_Template { public function __construct() { $this->init( 10 ); } }

			/*
			 * add_wc_shipping_wcj_custom_class.
			 */
			function add_wc_shipping_wcj_custom_class( $methods ) {
				$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_shipping_custom_shipping_total_number', 1 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					$methods[] = 'WC_Shipping_WCJ_Custom_' . $i;
				}
				return $methods;
			}
			add_filter( 'woocommerce_shipping_methods', 'add_wc_shipping_wcj_custom_class' );
		}
	}
}

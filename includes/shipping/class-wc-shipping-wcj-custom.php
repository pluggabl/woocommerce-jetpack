<?php
/**
 * Booster for WooCommerce - Shipping - Custom Shipping
 *
 * @version 2.8.0
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

add_action( 'init', 'init_wc_shipping_wcj_custom_class' );

if ( ! function_exists( 'init_wc_shipping_wcj_custom_class' ) ) {

	function init_wc_shipping_wcj_custom_class() {

		if ( class_exists( 'WC_Shipping_Method' ) ) {

			/*
			 * WC_Shipping_WCJ_Custom_Template class.
			 *
			 * @version 2.8.0
			 */
			class WC_Shipping_WCJ_Custom_Template extends WC_Shipping_Method {

				/**
				 * Constructor shipping class
				 *
				 * @version 2.8.0
				 * @access  public
				 * @return  void
				 */
				function __construct() {
					return true;
				}

				/**
				 * Init settings
				 *
				 * @version 2.8.0
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
					$this->enabled    = $this->get_option( 'enabled' );
					$this->title      = $this->get_option( 'title' );
					$this->cost       = $this->get_option( 'cost' );
					$this->min_weight = $this->get_option( 'min_weight' );
					$this->max_weight = $this->get_option( 'max_weight' );
					$this->type       = $this->get_option( 'type' );
					$this->weight_table_total_rows = $this->get_option( 'weight_table_total_rows' );
					for ( $i = 1; $i <= $this->weight_table_total_rows; $i++ ) {
						$option_name = 'weight_table_weight_row_' . $i;
						$this->{$option_name} = $this->get_option( $option_name );
						$option_name = 'weight_table_cost_row_' . $i;
						$this->{$option_name} = $this->get_option( $option_name );
					}

					// Save settings in admin
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				/**
				 * Is this method available?
				 *
				 * @version 2.8.0
				 * @since   2.8.0
				 * @param   array $package
				 * @return  bool
				 */
				function is_available( $package ) {
					$available = parent::is_available( $package );
					if ( $available ) {
						$total_weight = WC()->cart->get_cart_contents_weight();
						if ( 0 != $this->min_weight && $total_weight < $this->min_weight ) {
							$available = false;
						} elseif ( 0 != $this->max_weight && $total_weight > $this->max_weight ) {
							$available = false;
						}
					}
					return $available;
				}

				/**
				 * Initialise Settings Form Fields
				 *
				 * @version 2.8.0
				 */
				function init_form_fields() {
					$type_options = array(
						'flat_rate'                    => __( 'Flat Rate', 'woocommerce-jetpack' ),
						'by_total_cart_weight'         => __( 'By Total Cart Weight', 'woocommerce-jetpack' ),
						'by_total_cart_weight_table'   => __( 'By Total Cart Weight Table', 'woocommerce-jetpack' ),
						'by_total_cart_quantity'       => __( 'By Total Cart Quantity', 'woocommerce-jetpack' ),
					);
					$type_options = apply_filters( 'booster_option', $type_options, array_merge( $type_options, array(
						'by_total_cart_quantity_table' => __( 'By Total Cart Quantity Table', 'woocommerce-jetpack' ),
					) ) );
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
							'description' => __( 'Cost calculation type.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc_advanced_no_link', array( 'option' => __( 'By Total Cart Quantity Table', 'woocommerce-jetpack' ) ) ),
							'default'     => 'flat_rate',
							'desc_tip'    => true,
							'options'     => $type_options,
						),
						'cost' => array(
							'title'       => __( 'Cost', 'woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Cost. If calculating by weight - then cost per one weight unit. If calculating by quantity - then cost per one piece.', 'woocommerce-jetpack' ),
							'default'     => 0,
							'desc_tip'    => true,
							'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
						),
						'min_weight' => array(
							'title'       => __( 'Min Weight', 'woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Minimum total cart weight. Set zero to disable.', 'woocommerce-jetpack' ),
							'default'     => 0,
							'desc_tip'    => true,
							'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
						),
						'max_weight' => array(
							'title'       => __( 'Max Weight', 'woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Maximum total cart weight. Set zero to disable.', 'woocommerce-jetpack' ),
							'default'     => 0,
							'desc_tip'    => true,
							'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
						),
						'weight_table_total_rows' => array(
							'title'       => __( 'Table Total Rows', 'woocommerce' ),
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
								'title'       => __( 'Max Weight or Quantity', 'woocommerce' ) . ' #' . $i,
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
				 * calculate_shipping_by_table.
				 *
				 * @version 2.8.0
				 * @since   2.5.2
				 */
				function calculate_shipping_by_table( $weight ) {
					if ( 0 == $this->weight_table_total_rows ) {
						return $this->cost * $weight; // fallback
					}
					$option_name_weight = $option_name_cost = '';
					for ( $i = 1; $i <= $this->weight_table_total_rows; $i++ ) {
						$option_name_weight = 'weight_table_weight_row_' . $i;
						$option_name_cost = 'weight_table_cost_row_' . $i;
						if ( $weight <= $this->{$option_name_weight} ) {
							return $this->{$option_name_cost};
						}
					}
					return $this->{$option_name_cost}; // fallback - last row
				}

				/**
				 * cget_total_cart_quantity.
				 *
				 * @version 2.8.0
				 * @since   2.8.0
				 */
				function get_total_cart_quantity() {
					$cart_quantity = 0;
					foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
						$cart_quantity += $values['quantity'];
					}
					return $cart_quantity;
				}

				/**
				 * calculate_shipping function.
				 *
				 * @version 2.8.0
				 * @access  public
				 * @param   mixed $package
				 * @return  void
				 */
				function calculate_shipping( $package = array() ) {
					switch ( $this->type ) {
						case 'by_total_cart_quantity':
							$cost = $this->cost * $this->get_total_cart_quantity();
							break;
						case 'by_total_cart_weight':
							$cost = $this->cost * WC()->cart->get_cart_contents_weight();
							break;
						case 'by_total_cart_quantity_table':
							$cost = $this->calculate_shipping_by_table( $this->get_total_cart_quantity() );
							break;
						case 'by_total_cart_weight_table':
							$cost = $this->calculate_shipping_by_table( WC()->cart->get_cart_contents_weight() );
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

			/*
			 * add_wc_shipping_wcj_custom_class.
			 *
			 * @version 2.8.0
			 */
			function add_wc_shipping_wcj_custom_class( $methods ) {
				$total_number = get_option( 'wcj_shipping_custom_shipping_total_number', 1 );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					$the_method = new WC_Shipping_WCJ_Custom_Template();
					$the_method->init( $i );
					$methods[ $the_method->id ] = $the_method;
				}
				return $methods;
			}
			add_filter( 'woocommerce_shipping_methods', 'add_wc_shipping_wcj_custom_class' );
		}
	}
}

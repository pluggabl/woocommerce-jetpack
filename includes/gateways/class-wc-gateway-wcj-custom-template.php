<?php
/**
 * Booster for WooCommerce - Custom Payment Gateway
 *
 * @version 7.1.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! function_exists( 'init_wc_gateway_wcj_custom' ) ) {

			/**
			 * Init_wc_gateway_wcj_custom class.
			 *
			 * @version 5.3.4
			 */
	function init_wc_gateway_wcj_custom() {

		if ( class_exists( 'WC_Payment_Gateway' ) ) {

			/**
			 * WC_Gateway_WCJ_Custom_Template class.
			 *
			 * @version 7.1.6
			 */
			class WC_Gateway_WCJ_Custom_Template extends WC_Payment_Gateway {

				/**
				 * The module id_count
				 *
				 * @var varchar $id_count Module.
				 */
				public $id_count;

				/**
				 * The module min_amount
				 *
				 * @var varchar $min_amount Module.
				 */
				public $min_amount;

				/**
				 * The module instructions
				 *
				 * @var varchar $instructions Module.
				 */
				public $instructions;

				/**
				 * The module custom_return_url
				 *
				 * @var varchar $custom_return_url Module.
				 */
				public $custom_return_url;

				/**
				 * The module enable_for_methods
				 *
				 * @var varchar $enable_for_methods Module.
				 */
				public $enable_for_methods;

				/**
				 * The module enable_for_virtual
				 *
				 * @var varchar $enable_for_virtual Module.
				 */
				public $enable_for_virtual;

				/**
				 * The module send_email_to_admin
				 *
				 * @var varchar $send_email_to_admin Module.
				 */
				public $send_email_to_admin;

				/**
				 * The module default_order_status
				 *
				 * @var varchar $default_order_status Module.
				 */
				public $default_order_status;

				/**
				 * The module instructions_in_email
				 *
				 * @var varchar $instructions_in_email Module.
				 */
				public $instructions_in_email;

				/**
				 * The module send_email_to_customer
				 *
				 * @var varchar $send_email_to_customer Module.
				 */
				public $send_email_to_customer;

				/**
				 * Initialise Gateway Settings Form Fields
				 *
				 * @version 2.5.7
				 */
				public function init_form_fields() {
					global $woocommerce;

					$shipping_methods = array();

					if ( is_admin() ) {
						foreach ( WC()->shipping->load_shipping_methods() as $method ) {
							$shipping_methods[ $method->id ] = $method->get_method_title();
						}
					}

					$desc     = '';
					$icon_url = $this->get_option( 'icon', '' );
					if ( '' !== $icon_url ) {
						$desc = '<img src="' . $icon_url . '" alt="' . $this->title . '" title="' . $this->title . '" />';
					}

					$this->form_fields = array(
						'enabled'                => array(
							'title'   => __( 'Enable/Disable', 'woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable Custom Payment', 'woocommerce' ),
							'default' => 'no',
						),

						'title'                  => array(
							'title'       => __( 'Title', 'woocommerce' ),
							'type'        => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default'     => __( 'Custom Payment', 'woocommerce' ),
							'desc_tip'    => true,
						),

						'description'            => array(
							'title'       => __( 'Description', 'woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ) . ' ' .
												__( 'You can add input fields with [wcj_input_field] shortcode.', 'woocommerce' ),
							'default'     => __( 'Custom Payment Description.', 'woocommerce' ),
							'desc_tip'    => true,
						),

						'instructions'           => array(
							'title'       => __( 'Instructions', 'woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce-jetpack' ),
							'default'     => '',
							'desc_tip'    => true,
						),

						'instructions_in_email'  => array(
							'title'       => __( 'Email Instructions', 'woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'Instructions that will be added to the emails.', 'woocommerce-jetpack' ),
							'default'     => '',
							'desc_tip'    => true,
						),

						'icon'                   => array(
							'title'       => __( 'Icon', 'woocommerce-jetpack' ),
							'type'        => 'text',
							'desc_tip'    => __( 'If you want to show an image next to the gateway\'s name on the frontend, enter a URL to an image.', 'woocommerce-jetpack' ),
							'default'     => '',
							'description' => $desc,
							'css'         => 'min-width:300px;width:50%;',
						),

						'min_amount'             => array(
							'title'             => __( 'Minimum order amount', 'woocommerce-jetpack' ),
							'type'              => 'number',
							'desc_tip'          => __( 'If you want to set minimum order amount (excluding fees) to show this gateway on frontend, enter a number here. Set to 0 to disable.', 'woocommerce-jetpack' ),
							'default'           => 0,
							'description'       => apply_filters( 'booster_message', '', 'desc' ),
							'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
						),

						'enable_for_methods'     => array(
							'title'             => __( 'Enable for shipping methods', 'woocommerce' ),
							'type'              => 'multiselect',
							'class'             => 'chosen_select',
							'css'               => 'width: 450px;',
							'default'           => '',
							'description'       => __( 'If gateway is only available for certain shipping methods, set it up here. Leave blank to enable for all methods.', 'woocommerce-jetpack' ),
							'options'           => $shipping_methods,
							'desc_tip'          => true,
							'custom_attributes' => array( 'data-placeholder' => __( 'Select shipping methods', 'woocommerce' ) ),
						),

						'enable_for_virtual'     => array(
							'title'   => __( 'Enable for virtual orders', 'woocommerce' ),
							'label'   => __( 'Enable gateway if the order is virtual', 'woocommerce-jetpack' ),
							'type'    => 'checkbox',
							'default' => 'yes',
						),

						'default_order_status'   => array(
							'title'       => __( 'Default Order Status', 'woocommerce-jetpack' ),
							'description' => __( 'Enable Custom Statuses feature to add custom statuses to the list.', 'woocommerce-jetpack' ),
							'default'     => apply_filters( 'woocommerce_default_order_status', 'pending' ),
							'type'        => 'select',
							'options'     => $this->get_order_statuses(),
						),

						'send_email_to_admin'    => array(
							'title'   => __( 'Send Additional Emails', 'woocommerce-jetpack' ),
							'label'   => __( 'Send to Admin', 'woocommerce-jetpack' ),
							'default' => 'no',
							'type'    => 'checkbox',
						),

						'send_email_to_customer' => array(
							'label'       => __( 'Send to Customer', 'woocommerce-jetpack' ),
							'description' => __( 'This may help if you are using pending or custom default status and not getting new order emails.', 'woocommerce-jetpack' ),
							'default'     => 'no',
							'type'        => 'checkbox',
						),

						'custom_return_url'      => array(
							'title'       => __( 'Custom Return URL (Thank You Page)', 'woocommerce-jetpack' ),
							'label'       => __( 'URL', 'woocommerce-jetpack' ),
							'desc_tip'    => __( 'Enter full URL with http(s).', 'woocommerce-jetpack' ),
							'description' => __( 'Optional. Leave blank to use default URL.', 'woocommerce-jetpack' ),
							'default'     => '',
							'type'        => 'text',
						),
					);

					if ( 1 !== $this->id_count ) {
						$this->form_fields['enabled']['description']       = apply_filters( 'booster_message', '', 'desc' );
						$this->form_fields['enabled']['custom_attributes'] = apply_filters( 'booster_message', '', 'disabled' );
					}
				}

				/**
				 * Get_order_statuses.
				 */
				public function get_order_statuses() {
					$result   = array();
					$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
					foreach ( $statuses as $status => $status_name ) {
						$result[ substr( $status, 3 ) ] = $statuses[ $status ];
					}
					return $result;
				}

				/**
				 * Check If The Gateway Is Available For Use.
				 *
				 * @version 5.3.0
				 * @return  bool
				 */
				public function is_available() {

					// Check min amount.
					$min_amount = apply_filters( 'booster_option', 0, $this->min_amount );
					if ( $min_amount > 0 && isset( WC()->cart->total ) && '' !== WC()->cart->total && isset( WC()->cart->fee_total ) ) {
						$total_excluding_fees = WC()->cart->total - WC()->cart->fee_total;
						if ( $total_excluding_fees < $min_amount ) {
							return false;
						}
					}

					// Check shipping methods and is virtual.
					$order = null;

					if ( ! $this->enable_for_virtual ) {
						if ( WC()->cart && ! WC()->cart->needs_shipping() ) {
							return false;
						}

						if ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
							$order_id = absint( get_query_var( 'order-pay' ) );
							$order    = new WC_Order( $order_id );

							// Test if order needs shipping.
							$needs_shipping = false;

							if ( 0 < count( $order->get_items() ) ) {
								foreach ( $order->get_items() as $item ) {
									$_product = $item->get_product();

									if ( $_product->needs_shipping() ) {
										$needs_shipping = true;
										break;
									}
								}
							}

							$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

							if ( $needs_shipping ) {
								return false;
							}
						}
					}

					if ( ! empty( $this->enable_for_methods ) ) {

						// Only apply if all packages are being shipped via.
						$session_object                  = WC()->session;
						$chosen_shipping_methods_session = ( is_object( $session_object ) ) ? $session_object->get( 'chosen_shipping_methods' ) : null;

						if ( isset( $chosen_shipping_methods_session ) ) {
							$chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
						} else {
							$chosen_shipping_methods = array();
						}

						$check_method = false;

						if ( is_object( $order ) ) {
							if ( $order->shipping_method ) {
								$check_method = $order->shipping_method;
							}
						} elseif ( empty( $chosen_shipping_methods ) || count( $chosen_shipping_methods ) > 1 ) {
							$check_method = false;
						} elseif ( count( $chosen_shipping_methods ) === 1 ) {
							$check_method = $chosen_shipping_methods[0];
						}

						if ( ! $check_method ) {
							return false;
						}

						$found = false;

						foreach ( $this->enable_for_methods as $method_id ) {
							if ( strpos( $check_method, $method_id ) === 0 ) {
								$found = true;
								break;
							}
						}

						if ( ! $found ) {
							return false;
						}
					}

					return parent::is_available();
				}

				/**
				 * Output for the order received page.
				 *
				 * @version 4.7.1
				 * @param int $order_id Get order ID.
				 */
				public function thankyou_page( $order_id ) {
					if ( $this->instructions ) {
						$this->instructions = str_replace( '[wcj_order_meta', '[wcj_order_meta order_id="' . $order_id . '" ', $this->instructions );
						echo do_shortcode( wpautop( wptexturize( $this->instructions ) ) );
					}
				}

				/**
				 * Add content to the WC emails.
				 *
				 * @version 2.8.0
				 * @access  public
				 * @param   WC_Order $order Get order.
				 * @param   bool     $sent_to_admin Sent to admin.
				 * @param   bool     $plain_text Get plain text.
				 */
				public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
					if (
						$this->instructions_in_email && ! $sent_to_admin && wcj_order_get_payment_method( $order ) === $this->id &&
						( WCJ_IS_WC_VERSION_BELOW_3 ? $order->status : $order->get_status() ) === $this->default_order_status
					) {
						echo do_shortcode( wpautop( wptexturize( $this->instructions_in_email ) ) . PHP_EOL );
					}
				}

				/**
				 * Process the payment and return the result.
				 *
				 * @version 2.9.0
				 * @param   int $order_id Get order ID.
				 * @return  array
				 */
				public function process_payment( $order_id ) {

					$order = wc_get_order( $order_id );

					// Mark as on-hold (we're awaiting the payment).
					$statuses = $this->get_order_statuses();
					$note     = isset( $statuses[ $this->default_order_status ] ) ? $statuses[ $this->default_order_status ] : '';
					$order->update_status( $this->default_order_status, $note ); // e.g. 'on-hold', __( 'Awaiting payment', 'woocommerce' ).

					if ( 'yes' === $this->send_email_to_admin || 'yes' === $this->send_email_to_customer ) {
						$woocommerce_mailer = WC()->mailer();
						if ( 'yes' === $this->send_email_to_admin ) {
							$woocommerce_mailer->emails['WC_Email_New_Order']->trigger( $order_id );
						}
						if ( 'yes' === $this->send_email_to_customer ) {
							$woocommerce_mailer->emails['WC_Email_Customer_Processing_Order']->trigger( $order_id );
						}
					}

					// Reduce stock levels.
					if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
						$order->reduce_order_stock();
					} else {
						wc_reduce_stock_levels( $order_id );
					}

					// Remove cart.
					WC()->cart->empty_cart();

					// Return thankyou redirect.
					return array(
						'result'   => 'success',
						'redirect' => ( '' === $this->custom_return_url ) ? $this->get_return_url( $order ) : $this->custom_return_url,
					);
				}

				/**
				 * Init.
				 *
				 * @version 5.3.4
				 * @param  int $id_count Get order ID Count.
				 */
				public function init( $id_count ) {
					$this->id                 = ( 1 === $id_count ) ? 'jetpack_custom_gateway' : 'jetpack_custom_gateway_' . $id_count;
					$this->has_fields         = false;
					$this->method_title       = get_option(
						'wcj_custom_payment_gateways_admin_title_' . $id_count,
						__( 'Custom Gateway', 'woocommerce-jetpack' ) . ' #' . $id_count
					);
					$this->method_description = __( 'Booster for WooCommerce: Custom Payment Gateway', 'woocommerce-jetpack' ) . ' #' . $id_count;
					$this->id_count           = $id_count;
					// Load the settings.
					$this->init_form_fields();
					$this->init_settings();
					// Define user set variables.
					$this->title                  = $this->get_option( 'title' );
					$this->description            = do_shortcode(
						str_replace(
							'[wcj_input_field',
							'[wcj_input_field name_array="' . $this->id . '" attach_to="' . $this->id . '"',
							$this->get_option( 'description' )
						)
					);
					$this->instructions           = $this->get_option( 'instructions', '' );
					$this->instructions_in_email  = $this->get_option( 'instructions_in_email', '' );
					$this->icon                   = $this->get_option( 'icon', '' );
					$this->min_amount             = $this->get_option( 'min_amount', 0 );
					$this->enable_for_methods     = $this->get_option( 'enable_for_methods', array() );
					$this->enable_for_virtual     = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
					$this->default_order_status   = $this->get_option( 'default_order_status', 'pending' );
					$this->send_email_to_admin    = $this->get_option( 'send_email_to_admin', 'no' );
					$this->send_email_to_customer = $this->get_option( 'send_email_to_customer', 'no' );
					$this->custom_return_url      = $this->get_option( 'custom_return_url', '' );
					// Actions.
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
					add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
					add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 ); // Customer Emails.
				}

				/**
				 * Constructor.
				 */
				public function __construct() {

				}
			}

			/**
			 * Add_wc_gateway_wcj_custom_classes.
			 *
			 * @version 2.5.6
			 * @param  array $methods Get Payment method.
			 */
			function add_wc_gateway_wcj_custom_classes( $methods ) {
				$the_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_payment_gateways_number', 1 ) );
				for ( $i = 1; $i <= $the_number; $i++ ) {
					$the_method = new WC_Gateway_WCJ_Custom_Template();
					$the_method->init( $i );
					$methods[] = $the_method;
				}
				return $methods;
			}
			add_filter( 'woocommerce_payment_gateways', 'add_wc_gateway_wcj_custom_classes' );
		}
	}
}

if ( 'no' === wcj_get_option( 'wcj_load_modules_on_init', 'no' ) ) {
	add_action( 'plugins_loaded', 'init_wc_gateway_wcj_custom', PHP_INT_MAX );
} else {
	init_wc_gateway_wcj_custom();
}

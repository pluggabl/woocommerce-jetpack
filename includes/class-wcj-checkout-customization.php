<?php
/**
 * Booster for WooCommerce - Module - Checkout Customization
 *
 * @version 
 * @since   2.7.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Checkout_Customization' ) ) :
	/**
	 * WCJ_Checkout_Customization.
	 *
	 * @version 2.7.0
	 */
	class WCJ_Checkout_Customization extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.7.0
		 */
		public function __construct() {

			$this->id         = 'checkout_customization';
			$this->short_desc = __( 'Checkout Customization', 'woocommerce-jetpack' );
			$this->desc       = __( 'Customize WooCommerce checkout - restrict countries by customer\'s IP (Plus); hide "Order Again" button; disable selected fields on checkout for logged users and more (Custom fields available in Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Customize WooCommerce checkout - restrict countries by customer\'s IP; hide "Order Again" button; disable selected fields on checkout for logged users and more.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-checkout-customization';
			parent::__construct();

			if ( $this->is_enabled() ) {
				// "Create an account?" Checkbox.
				$create_account_default = wcj_get_option( 'wcj_checkout_create_account_default_checked', 'default' );
				if ( 'default' !== ( $create_account_default ) ) {
					if ( 'checked' === $create_account_default ) {
						add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
					} elseif ( 'not_checked' === $create_account_default ) {
						add_filter( 'woocommerce_create_account_default_checked', '__return_false' );
					}
				}
				// Hide "Order Again" button.
				if ( 'yes' === wcj_get_option( 'wcj_checkout_hide_order_again', 'no' ) ) {
					add_action( 'init', array( $this, 'checkout_hide_order_again' ), PHP_INT_MAX );
				}
				// Disable Fields on Checkout for Logged Users.
				add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_disable_fields' ), PHP_INT_MAX );
				$checkout_fields_types = array(
					'country',
					'state',
					'textarea',
					'checkbox',
					'password',
					'text',
					'email',
					'tel',
					'number',
					'select',
					'radio',
				);
				foreach ( $checkout_fields_types as $checkout_fields_type ) {
					add_filter( 'woocommerce_form_field_' . $checkout_fields_type, array( $this, 'maybe_add_description' ), PHP_INT_MAX, 4 );
				}
				// Custom "order received" message text.
				if ( 'yes' === wcj_get_option( 'wcj_checkout_customization_order_received_message_enabled', 'no' ) ) {
					add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'customize_order_received_message' ), PHP_INT_MAX, 2 );
				}
				// Custom checkout login message.
				if ( 'yes' === wcj_get_option( 'wcj_checkout_customization_checkout_login_message_enabled', 'no' ) ) {
					add_filter( 'woocommerce_checkout_login_message', array( $this, 'checkout_login_message' ), PHP_INT_MAX );
				}
				// Restrict countries by customer's IP.
				if ( 'yes' === wcj_get_option( 'wcj_checkout_restrict_countries_by_customer_ip_billing', 'no' ) ) {
					add_filter( 'woocommerce_countries_allowed_countries', array( $this, 'restrict_countries_by_customer_ip' ), PHP_INT_MAX );
				}
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_checkout_restrict_countries_by_customer_ip_shipping', 'no' ) ) ) {
					add_filter( 'woocommerce_countries_shipping_countries', array( $this, 'restrict_countries_by_customer_ip' ), PHP_INT_MAX );
				}
				// Recalculate Checkout.
				if ( 'yes' === wcj_get_option( 'wcj_checkout_recalculate_checkout_update_enable', 'no' ) ) {
					add_action( 'wp_footer', array( $this, 'recalculate_checkout' ), 50 );
				}
				// Update Checkout.
				if ( 'yes' === wcj_get_option( 'wcj_checkout_force_checkout_update_enable', 'no' ) ) {
					add_action( 'wp_footer', array( $this, 'update_checkout' ), 50 );
				}
			}
		}

		/**
		 * Updates checkout.
		 *
		 * @version 4.1.0
		 * @since   4.1.0
		 */
		public function update_checkout() {
			if ( ! is_checkout() ) {
				return;
			}
			?>
		<script type="text/javascript">
			jQuery(function ($) {
				var selector = <?php echo wp_json_encode( wcj_get_option( 'wcj_checkout_force_checkout_update_fields', '' ) ); ?>;
				jQuery(selector).on('change', function () {
					$('body').trigger('update_checkout');
				});
			});
		</script>
			<?php
		}

		/**
		 * Recalculates checkout.
		 *
		 * @version 4.1.0
		 * @since   4.1.0
		 */
		public function recalculate_checkout() {
			if ( ! is_checkout() ) {
				return;
			}
			?>
		<script type="text/javascript">
			jQuery(function ($) {
				var old_values = [];
				var selector = <?php echo wp_json_encode( wcj_get_option( 'wcj_checkout_recalculate_checkout_update_fields', '#billing_country, #shipping_country' ) ); ?>;
				jQuery(document.body).on("updated_checkout", function () {
					jQuery(selector).each(function (index) {
						if (old_values[index] != $(this).val()) {
							$('body').trigger('update_checkout');
						}
						old_values[index] = $(this).val();
					});
				});
			});
		</script>
			<?php
		}

		/**
		 * Checks if conditions are valid in order for the country restriction to work.
		 *
		 * @version 4.6.0
		 * @since   4.6.0
		 *
		 * @return bool
		 */
		public function are_conditions_valid() {
			$valid      = false;
			$conditions = wcj_get_option( 'wcj_checkout_restrict_countries_by_customer_ip_conditions', array() );
			foreach ( $conditions as $key => $condition ) {
				$function = $condition;
				$valid    = $function();
				if ( $valid ) {
					break;
				}
			}
			return $valid;
		}

		/**
		 * Restrict_countries_by_customer_ip.
		 *
		 * @version 4.6.0
		 * @since   3.4.0
		 * @todo    (maybe) handle case when `wcj_get_country_by_ip()` returns empty string
		 * @todo    (maybe) for shipping countries - filter `woocommerce_ship_to_countries` option
		 * @param string $countries defines the countries.
		 */
		public function restrict_countries_by_customer_ip( $countries ) {
			if (
			( 'yes' === wcj_get_option( 'wcj_checkout_restrict_countries_by_customer_ip_ignore_admin', 'no' ) && is_admin() ) ||
			( ! empty( wcj_get_option( 'wcj_checkout_restrict_countries_by_customer_ip_conditions', array() ) ) && ! $this->are_conditions_valid() )
			) {
				return $countries;
			}
			$user_country = wcj_get_country_by_ip();

			// Get country from 'billing_country' user meta.
			$user_id              = get_current_user_id();
			$user_billing_country = get_user_meta( $user_id, 'billing_country', true );
			if (
			'yes' === wcj_get_option( 'wcj_checkout_restrict_countries_by_user_billing_country', 'no' ) &&
			0 !== ( $user_id )
			) {
				$user_country = ! empty( $user_billing_country ) ? $user_billing_country : wcj_get_country_by_ip();
			}

			// Get country from a manual order ID created by YITH Request a Quote plugin.
			if ( 'yes' === wcj_get_option( 'wcj_checkout_restrict_countries_based_on_yith_raq', 'no' ) && class_exists( 'YITH_Request_Quote' ) ) {
				$yith_order_id = WC()->session->get( 'order_awaiting_payment' );
				if ( ! empty( $yith_order_id ) ) {
					$order_billing_country = get_post_meta( $yith_order_id, '_billing_country', true );
					$user_country          = ! empty( $order_billing_country ) ? $order_billing_country : wcj_get_country_by_ip();
				}
			}
			return array( $user_country => wcj_get_country_name_by_code( $user_country ) );
		}

		/**
		 * Checkout_login_message.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @param string $message defines the message.
		 */
		public function checkout_login_message( $message ) {
			return wcj_get_option( 'wcj_checkout_customization_checkout_login_message', __( 'Returning customer?', 'woocommerce' ) );
		}

		/**
		 * Customize_order_received_message.
		 *
		 * @version 
		 * @since   3.1.0
		 * @param string       $message defines the message.
		 * @param int | string $_order defines the _order.
		 */
		public function customize_order_received_message( $message, $_order ) {
			if ( null !== $_order ) {
				global $post;
				$post = get_post( wcj_get_order_id( $_order ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );
			}
			$message = do_shortcode( wcj_get_option( 'wcj_checkout_customization_order_received_message', __( 'Thank you. Your order has been received.', 'woocommerce' ) ) );
			if ( null !== $_order ) {
				wp_reset_postdata();
			}
			return $message;
		}

		/**
		 * Maybe_add_description.
		 *
		 * @version 5.6.2
		 * @since   2.9.0
		 * @param string       $field defines the field.
		 * @param string | int $key defines the key.
		 * @param string       $args defines the args.
		 * @param string | int $value defines the value.
		 */
		public function maybe_add_description( $field, $key, $args, $value ) {
			if ( is_user_logged_in() ) {
				$fields_to_disable = wcj_get_option( 'wcj_checkout_customization_disable_fields_for_logged', array() );
				if ( empty( $fields_to_disable ) ) {
					$fields_to_disable = array();
				}
				$fields_to_disable_custom_r = array_map( 'trim', explode( ',', apply_filters( 'booster_option', '', wcj_get_option( 'wcj_checkout_customization_disable_fields_for_logged_custom_r', '' ) ) ) );
				$fields_to_disable_custom_d = array_map( 'trim', explode( ',', apply_filters( 'booster_option', '', wcj_get_option( 'wcj_checkout_customization_disable_fields_for_logged_custom_d', '' ) ) ) );
				$fields_to_disable          = array_merge( $fields_to_disable, $fields_to_disable_custom_r, $fields_to_disable_custom_d );
				if ( ! empty( $fields_to_disable ) ) {
					if ( in_array( $key, $fields_to_disable, true ) ) {
						$desc = wcj_get_option(
							'wcj_checkout_customization_disable_fields_for_logged_message',
							'<em>' . __( 'This field can not be changed', 'woocommerce-jetpack' ) . '</em>'
						);
						if ( '' !== $desc ) {
							$field = str_replace( '__WCJ_TEMPORARY_VALUE_TO_REPLACE__', $desc, $field );
						}
					}
				}
			}
			return $field;
		}

		/**
		 * Maybe_disable_fields.
		 *
		 * @version 5.6.2
		 * @since   2.9.0
		 * @see     woocommerce_form_field
		 * @todo    (maybe) add single option (probably checkbox) to disable all fields
		 * @todo    (maybe) on `'billing_country', 'shipping_country'` change to simple `select` (i.e. probably remove `wc-enhanced-select` class)
		 * @param array $checkout_fields defines the checkout_fields.
		 */
		public function maybe_disable_fields( $checkout_fields ) {
			if ( is_user_logged_in() ) {
				$fields_to_disable = wcj_get_option( 'wcj_checkout_customization_disable_fields_for_logged', array() );
				if ( empty( $fields_to_disable ) ) {
					$fields_to_disable = array();
				}
				$fields_to_disable_custom_r = array_map( 'trim', explode( ',', apply_filters( 'booster_option', '', wcj_get_option( 'wcj_checkout_customization_disable_fields_for_logged_custom_r', '' ) ) ) );
				$fields_to_disable_custom_d = array_map( 'trim', explode( ',', apply_filters( 'booster_option', '', wcj_get_option( 'wcj_checkout_customization_disable_fields_for_logged_custom_d', '' ) ) ) );
				$fields_to_disable          = array_merge( $fields_to_disable, $fields_to_disable_custom_r, $fields_to_disable_custom_d );
				$disable_type_fields        = array_merge( array( 'billing_country', 'shipping_country' ), $fields_to_disable_custom_d );
				$do_add_desc_placeholder    = ( '' !== wcj_get_option(
					'wcj_checkout_customization_disable_fields_for_logged_message',
					'<em>' . __( 'This field can not be changed', 'woocommerce-jetpack' ) . '</em>'
				) );
				if ( ! empty( $fields_to_disable ) ) {
					foreach ( $fields_to_disable as $field_to_disable ) {
						$section = explode( '_', $field_to_disable );
						$section = $section[0];
						if ( isset( $checkout_fields[ $section ][ $field_to_disable ] ) ) {
							if ( ! isset( $checkout_fields[ $section ][ $field_to_disable ]['custom_attributes'] ) ) {
								$checkout_fields[ $section ][ $field_to_disable ]['custom_attributes'] = array();
							}
							$custom_attributes = ( in_array( $field_to_disable, $disable_type_fields, true ) ? array( 'disabled' => 'disabled' ) : array( 'readonly' => 'readonly' ) );
							$checkout_fields[ $section ][ $field_to_disable ]['custom_attributes'] = array_merge(
								$checkout_fields[ $section ][ $field_to_disable ]['custom_attributes'],
								$custom_attributes
							);
							if ( $do_add_desc_placeholder ) {
									$checkout_fields[ $section ][ $field_to_disable ]['description'] = '__WCJ_TEMPORARY_VALUE_TO_REPLACE__';
							}
						}
					}
				}
			}
			return $checkout_fields;
		}

		/**
		 * Checkout_hide_order_again.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 */
		public function checkout_hide_order_again() {
			remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
		}

	}

endif;

return new WCJ_Checkout_Customization();

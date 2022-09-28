<?php
/**
 * Booster for WooCommerce - Module - Checkout Core Fields
 *
 * @version 5.6.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Checkout_Core_Fields' ) ) :

	/**
	 * WCJ_Checkout_Core_Fields.
	 *
	 * @version 2.7.0
	 */
	class WCJ_Checkout_Core_Fields extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.4.0
		 * @see     https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
		 * @todo    (maybe) default overrides should be `disable`
		 */
		public function __construct() {

			$this->id         = 'checkout_core_fields';
			$this->short_desc = __( 'Checkout Core Fields', 'woocommerce-jetpack' );
			$this->desc       = __( 'Customize core checkout fields. Disable/enable fields, set required, change labels and/or placeholders; Setup fields by category (Plus)', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Customize core checkout fields. Disable/enable fields, set required, change labels and/or placeholders etc.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-checkout-core-fields';
			parent::__construct();

			$this->woocommerce_core_checkout_fields = array(
				'billing_country',
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_state',
				'billing_postcode',
				'billing_email',
				'billing_phone',
				'shipping_country',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_state',
				'shipping_postcode',
				'account_username',
				'account_password',
				'account_password-2',
				'order_comments',
			);

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_override_checkout_fields' ), PHP_INT_MAX );
				add_action( 'woocommerce_checkout_fields', array( $this, 'enqueue_scripts' ), PHP_INT_MAX );
				$this->country_locale_override = wcj_get_option( 'wcj_checkout_core_fields_override_country_locale_fields', 'billing' );
				if ( 'disable' !== ( $this->country_locale_override ) ) {
					add_filter( 'woocommerce_get_country_locale', array( $this, 'custom_override_country_locale_fields' ), PHP_INT_MAX );
				}
				$this->default_address_override = wcj_get_option( 'wcj_checkout_core_fields_override_default_address_fields', 'billing' );
				if ( 'disable' !== ( $this->default_address_override ) ) {
					add_filter( 'woocommerce_default_address_fields', array( $this, 'custom_override_default_address_fields' ), PHP_INT_MAX );
				}
			}
		}

		/**
		 * Maybe_override_fields.
		 *
		 * @version 5.6.6
		 * @since   3.1.0
		 * @todo    (maybe) add option to choose `$options_to_override`
		 * @todo    (maybe) add to `$options_to_override`: enabled; class;
		 * @param string $fields defines the fields.
		 * @param string $override_with_section defines the override_with_section.
		 */
		public function maybe_override_fields( $fields, $override_with_section ) {
			$options_to_override = array(
				'label'       => array(
					'default' => '',
				),
				'placeholder' => array(
					'default' => '',
				),
				'description' => array(
					'default' => '',
				),
				'priority'    => array(
					'default' => 0,
				),
				'required'    => array(
					'default'   => 'default',
					'option_id' => 'is_required',
					'values'    => array(
						'yes' => true,
						'no'  => false,
					),
				),
			);
			foreach ( $fields as $field_key => $field_values ) {
				$field = $override_with_section . '_' . $field_key;
				foreach ( $options_to_override as $option => $option_data ) {
					$default_value = $option_data['default'];
					$option_id     = ( isset( $option_data['option_id'] ) ? $option_data['option_id'] : $option );
					$option_id     = 'wcj_checkout_fields_' . $field . '_' . $option_id;
					$value         = wcj_get_option( $option_id, $default_value );
					if ( (string) $default_value !== $value ) {
						$value                           = ( isset( $option_data['values'][ $value ] ) ? $option_data['values'][ $value ] : $value );
						$fields[ $field_key ][ $option ] = $value;
					}
				}
			}
			return $fields;
		}

		/**
		 * Custom_override_country_locale_fields.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param string $fields defines the fields.
		 */
		public function custom_override_country_locale_fields( $fields ) {
			foreach ( $fields as $country => $country_fields ) {
				$fields[ $country ] = $this->maybe_override_fields( $country_fields, $this->country_locale_override );
			}
			return $fields;
		}

		/**
		 * Custom_override_default_address_fields.
		 *
		 * @version 3.1.0
		 * @since   2.3.8
		 * @param string $fields defines the fields.
		 */
		public function custom_override_default_address_fields( $fields ) {
			return $this->maybe_override_fields( $fields, $this->default_address_override );
		}

		/**
		 * Custom_override_checkout_fields.
		 *
		 * @version 5.3.7
		 * @todo    add "per products", "per products tags"
		 * @todo    (maybe) fix - priority seems to not affect tab order (same in Checkout Custom Fields module)
		 * @todo    (maybe) enable if was not enabled by default, i.e. `! isset( $checkout_fields[ $section ][ $field ] )`
		 * @param string $checkout_fields defines the checkout_fields.
		 */
		public function custom_override_checkout_fields( $checkout_fields ) {
			foreach ( $this->woocommerce_core_checkout_fields as $field ) {
				$field_parts = explode( '_', $field, 2 );
				$section     = ( ! empty( $field_parts ) && is_array( $field_parts ) ? $field_parts[0] : '' ); // billing or shipping
				// enabled.
				$is_enabled = wcj_get_option( 'wcj_checkout_fields_' . $field . '_is_enabled', 'default' );
				if ( 'no' === ( $is_enabled ) ) {
					if ( isset( $checkout_fields[ $section ][ $field ] ) ) {
						unset( $checkout_fields[ $section ][ $field ] );
						continue;
					}
				}
				// enabled - per products categories.
				if ( ! $this->is_visible(
					array(
						'include_products'   => '',
						'exclude_products'   => '',
						'include_categories' => apply_filters( 'booster_option', '', wcj_get_option( 'wcj_checkout_fields_' . $field . '_cats_incl', '' ) ),
						'exclude_categories' => apply_filters( 'booster_option', '', wcj_get_option( 'wcj_checkout_fields_' . $field . '_cats_excl', '' ) ),
						'include_tags'       => '',
						'exclude_tags'       => '',
					),
					wcj_get_option( 'wcj_checkout_core_fields_checking_relation', 'all' )
				)
				) {
					unset( $checkout_fields[ $section ][ $field ] );
					continue;
				}
				if ( isset( $checkout_fields[ $section ][ $field ] ) ) {
					// required.
					$is_required = wcj_get_option( 'wcj_checkout_fields_' . $field . '_is_required', 'default' );
					if ( 'default' !== ( $is_required ) ) {
						$checkout_fields[ $section ][ $field ]['required'] = ( 'yes' === $is_required );
					}
					// label.
					$label = wcj_get_option( 'wcj_checkout_fields_' . $field . '_label', '' );
					if ( '' !== ( $label ) ) {
						$checkout_fields[ $section ][ $field ]['label'] = $label;
					}
					// placeholder.
					$placeholder = wcj_get_option( 'wcj_checkout_fields_' . $field . '_placeholder', '' );
					if ( '' !== ( $placeholder ) ) {
						$checkout_fields[ $section ][ $field ]['placeholder'] = $placeholder;
					}
					// description.
					$description = wcj_get_option( 'wcj_checkout_fields_' . $field . '_description', '' );
					if ( '' !== ( $description ) ) {
						$checkout_fields[ $section ][ $field ]['description'] = $description;
					}
					// class.
					$class = wcj_get_option( 'wcj_checkout_fields_' . $field . '_class', 'default' );
					if ( 'default' !== ( $class ) ) {
						$checkout_fields[ $section ][ $field ]['class'] = array( $class );
					}
					// priority.
					$priority = apply_filters( 'booster_option', 0, wcj_get_option( 'wcj_checkout_fields_' . $field . '_priority', 0 ) );
					if ( 0 !== ( $priority ) ) {
						$checkout_fields[ $section ][ $field ]['priority'] = $priority;
					}
				}
			}
			if ( 'yes' === wcj_get_option( 'wcj_checkout_core_fields_force_sort_by_priority', 'no' ) ) {
				$field_sets = array( 'billing', 'shipping', 'account', 'order' );
				foreach ( $field_sets as $field_set ) {
					if ( isset( $checkout_fields[ $field_set ] ) ) {
						uasort( $checkout_fields[ $field_set ], array( $this, 'sort_by_priority' ) );
					}
				}
			}
			wcj_session_set( 'wcj_checkout_fields', $checkout_fields );
			return $checkout_fields;
		}

		/**
		 * Is_visible.
		 *
		 * @version 4.9.0
		 * @since   3.4.0
		 * @todo    (maybe) save `$this->cart_product_ids` array (instead of calling `WC()->cart->get_cart()` for each field)
		 *
		 * @param array  $args defines the arguments.
		 * @param string $relation defines the relation.
		 *
		 * @return bool
		 */
		public function is_visible( $args, $relation = 'and' ) {
			$relation  = strtolower( $relation );
			$all_empty = true;
			foreach ( $args as $arg ) {
				if ( ! empty( $arg ) ) {
					$all_empty = false;
					// At least one arg is filled - checking products in cart.
					foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
						if ( 'and' === $relation ) {
							if ( ! wcj_is_enabled_for_product( $values['product_id'], $args ) ) {
								return false;
							}
						} elseif ( 'or' === $relation ) {
							if ( wcj_is_enabled_for_product( $values['product_id'], $args ) ) {
								return true;
							}
						}
					}
					break;
				}
			}
			if ( $all_empty ) {
				return true;
			}
			return 'and' === $relation ? true : false;
		}

		/**
		 * Sort_by_priority.
		 *
		 * @version 3.4.0
		 * @since   3.4.0
		 * @param string $a defines the a.
		 * @param string $b defines the b.
		 */
		public function sort_by_priority( $a, $b ) {
			$a = ( isset( $a['priority'] ) ? $a['priority'] : 0 );
			$b = ( isset( $b['priority'] ) ? $b['priority'] : 0 );
			if ( $a === $b ) {
				return 0;
			}
			return ( $a < $b ) ? -1 : 1;
		}
		/**
		 * Enqueue_scripts.
		 *
		 * @version 5.3.7
		 * @param string $checkout_fields defines the checkout_fields.
		 */
		public function enqueue_scripts( $checkout_fields ) {
			wp_enqueue_script( 'wcj-checkout-core-fields', wcj_plugin_url() . '/includes/js/wcj-checkout-core-fields.js', array(), w_c_j()->version, false );
			wp_localize_script(
				'wcj-checkout-core-fields',
				'wcj_checkout_core_fields',
				array(
					'checkout_fields' => wcj_session_get( 'wcj_checkout_fields' ),
				)
			);
			return $checkout_fields;
		}

	}

endif;

return new WCJ_Checkout_Core_Fields();

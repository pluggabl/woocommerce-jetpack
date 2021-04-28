<?php
/**
 * Booster for WooCommerce - Module - Checkout Core Fields
 *
 * @version 5.3.7
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Checkout_Core_Fields' ) ) :

class WCJ_Checkout_Core_Fields extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.4.0
	 * @see     https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
	 * @todo    (maybe) default overrides should be `disable`
	 */
	function __construct() {

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
			add_filter( 'woocommerce_checkout_fields' ,            array( $this, 'custom_override_checkout_fields' ),        PHP_INT_MAX );
			add_action('woocommerce_checkout_fields', array($this, 'enqueue_scripts'), PHP_INT_MAX);
			if ( 'disable' != ( $this->country_locale_override = wcj_get_option( 'wcj_checkout_core_fields_override_country_locale_fields', 'billing' ) ) ) {
				add_filter( 'woocommerce_get_country_locale',      array( $this, 'custom_override_country_locale_fields' ),  PHP_INT_MAX );
			}
			if ( 'disable' != ( $this->default_address_override = wcj_get_option( 'wcj_checkout_core_fields_override_default_address_fields', 'billing' ) ) ) {
				add_filter( 'woocommerce_default_address_fields',  array( $this, 'custom_override_default_address_fields' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * maybe_override_fields.
	 *
	 * @version 3.6.0
	 * @since   3.1.0
	 * @todo    (maybe) add option to choose `$options_to_override`
	 * @todo    (maybe) add to `$options_to_override`: enabled; class;
	 */
	function maybe_override_fields( $fields, $override_with_section ) {
		$options_to_override = array(
			'label'       => array(
				'default'   => '',
			),
			'placeholder' => array(
				'default'   => '',
			),
			'description' => array(
				'default'   => '',
			),
			'priority'    => array(
				'default'   => 0,
			),
			'required'    =>  array(
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
				if ( $default_value != ( $value = wcj_get_option( $option_id, $default_value ) ) ) {
					$value = ( isset( $option_data['values'][ $value ] ) ? $option_data['values'][ $value ] : $value );
					$fields[ $field_key ][ $option ] = $value;
				}
			}
		}
		return $fields;
	}

	/**
	 * custom_override_country_locale_fields.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function custom_override_country_locale_fields( $fields ) {
		foreach ( $fields as $country => $country_fields ) {
			$fields[ $country ] = $this->maybe_override_fields( $country_fields, $this->country_locale_override );
		}
		return $fields;
	}

	/**
	 * custom_override_default_address_fields.
	 *
	 * @version 3.1.0
	 * @since   2.3.8
	 */
	function custom_override_default_address_fields( $fields ) {
		return $this->maybe_override_fields( $fields, $this->default_address_override );
	}

	/**
	 * custom_override_checkout_fields.
	 *
	 * @version 5.3.7
	 * @todo    add "per products", "per products tags"
	 * @todo    (maybe) fix - priority seems to not affect tab order (same in Checkout Custom Fields module)
	 * @todo    (maybe) enable if was not enabled by default, i.e. `! isset( $checkout_fields[ $section ][ $field ] )`
	 */
	function custom_override_checkout_fields( $checkout_fields ) {
		foreach ( $this->woocommerce_core_checkout_fields as $field ) {
			$field_parts = explode( '_', $field, 2 );
			$section     = ( ! empty( $field_parts ) && is_array( $field_parts ) ? $field_parts[0] : '' ); // billing or shipping
			// enabled
			if ( 'no' === ( $is_enabled = wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'is_enabled', 'default' ) ) ) {
				if ( isset( $checkout_fields[ $section ][ $field ] ) ) {
					unset( $checkout_fields[ $section ][ $field ] ); // e.g. unset( $checkout_fields['billing']['billing_country'] );
					continue;
				}
			}
			// enabled - per products categories
			if ( ! $this->is_visible( array(
					'include_products'   => '',
					'exclude_products'   => '',
					'include_categories' => apply_filters( 'booster_option', '', wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'cats_incl', '' ) ),
					'exclude_categories' => apply_filters( 'booster_option', '', wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'cats_excl', '' ) ),
					'include_tags'       => '',
					'exclude_tags'       => '',
				), wcj_get_option( 'wcj_checkout_core_fields_checking_relation', 'all' ) )
			) {
				unset( $checkout_fields[ $section ][ $field ] );
				continue;
			}
			if ( isset( $checkout_fields[ $section ][ $field ] ) ) {
				// required
				if ( 'default' != ( $is_required = wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'is_required', 'default' ) ) ) {
					$checkout_fields[ $section ][ $field ]['required'] = ( 'yes' === $is_required );
				}
				// label
				if ( '' != ( $label = wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'label', '' ) ) ) {
					$checkout_fields[ $section ][ $field ]['label'] = $label;
				}
				// placeholder
				if ( '' != ( $placeholder = wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'placeholder', '' ) ) ) {
					$checkout_fields[ $section ][ $field ]['placeholder'] = $placeholder;
				}
				// description
				if ( '' != ( $description = wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'description', '' ) ) ) {
					$checkout_fields[ $section ][ $field ]['description'] = $description;
				}
				// class
				if ( 'default' != ( $class = wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'class', 'default' ) ) ) {
					$checkout_fields[ $section ][ $field ]['class'] = array( $class );
				}
				// priority
				if ( 0 != ( $priority = apply_filters( 'booster_option', 0, wcj_get_option( 'wcj_checkout_fields_' . $field . '_' . 'priority', 0 ) ) ) ) {
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
		wcj_session_set("wcj_checkout_fields", $checkout_fields);
		return $checkout_fields;
	}

	/**
	 * is_visible.
	 *
	 * @version 4.9.0
	 * @since   3.4.0
	 * @todo    (maybe) save `$this->cart_product_ids` array (instead of calling `WC()->cart->get_cart()` for each field)
	 *
	 * @param $args
	 * @param string $relation
	 *
	 * @return bool
	 */
	function is_visible( $args, $relation = 'and' ) {
		$relation  = strtolower( $relation );
		$all_empty = true;
		foreach ( $args as $arg ) {
			if ( ! empty( $arg ) ) {
				$all_empty = false;
				// At least one arg is filled - checking products in cart
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
	 * sort_by_priority.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function sort_by_priority( $a, $b ) {
		$a = ( isset( $a['priority'] ) ? $a['priority'] : 0 );
		$b = ( isset( $b['priority'] ) ? $b['priority'] : 0 );
		if ( $a == $b ) {
			return 0;
		}
		return ( $a < $b ) ? -1 : 1;
	}
	/**
	 * enqueue_scripts.
	 *
	 * @version 5.3.7
	 */
	function enqueue_scripts($checkout_fields)
	{
		wp_enqueue_script('wcj-checkout-core-fields', wcj_plugin_url() . '/includes/js/wcj-checkout-core-fields.js', array(), wcj()->version, false);
		wp_localize_script('wcj-checkout-core-fields', 'wcj_checkout_core_fields', array(
			'checkout_fields' => wcj_session_get("wcj_checkout_fields")
		));
		return $checkout_fields;
	}

}

endif;

return new WCJ_Checkout_Core_Fields();

<?php
/**
 * WooCommerce Jetpack Shipping
 *
 * The WooCommerce Jetpack Shipping class.
 *
 * @version 2.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

class WCJ_Shipping extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.6.0
	 */
	function __construct() {

		$this->id         = 'shipping';
		$this->short_desc = __( 'Shipping', 'woocommerce-jetpack' );
		$this->desc       =
			__( 'Add multiple custom shipping methods to WooCommerce.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Add descriptions and icons to shipping methods on frontend.', 'woocommerce-jetpack') . ' ' .
			__( 'Hide WooCommerce shipping when free is available.', 'woocommerce-jetpack');
		$this->link       = 'http://booster.io/features/woocommerce-shipping/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {

			// Custom Shipping
			include_once( 'shipping/class-wc-shipping-wcj-custom.php' );
			if ( 'yes' === get_option( 'wcj_shipping_custom_shipping_w_zones_enabled', 'no' ) ) {
				include_once( 'shipping/class-wc-shipping-wcj-custom-with-shipping-zones.php' );
			}

			// Hide if free is available
			if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_all', 'no' ) ) {
				add_filter( 'woocommerce_package_rates', array( $this, 'hide_shipping_when_free_is_available' ), 10, 2 );
			}
			add_filter( 'woocommerce_shipping_settings', array( $this, 'add_hide_shipping_if_free_available_fields' ), 100 );

			// Shipping Descriptions
			if ( 'yes' === get_option( 'wcj_shipping_description_enabled', 'no' ) ) {
				add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_description' ), PHP_INT_MAX, 2 );
			}

			// Shipping Icons
			if ( 'yes' === get_option( 'wcj_shipping_icons_enabled', 'no' ) ) {
				add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_icon' ), PHP_INT_MAX, 2 );
			}

			// Free shipping by product
			if ( 'yes' === get_option( 'wcj_shipping_free_shipping_by_product_enabled', 'no' ) ) {
				add_filter( 'woocommerce_shipping_free_shipping_is_available', array( $this, 'free_shipping_by_product' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * free_shipping_by_product.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 * @return  bool
	 */
	function free_shipping_by_product( $is_available, $package ) {
		$free_shipping_granting_products = get_option( 'wcj_shipping_free_shipping_by_product_products', '' );
		if ( empty( $free_shipping_granting_products ) ) {
			return $is_available;
		}
		$free_shipping_granting_products_type = apply_filters( 'booster_get_option', 'all', get_option( 'wcj_shipping_free_shipping_by_product_type', 'all' ) );
		$package_grants_free_shipping = false;
		foreach( $package['contents'] as $item ) {
			if ( in_array( $item['product_id'], $free_shipping_granting_products ) ) {
				if ( 'at_least_one' === $free_shipping_granting_products_type ) {
					return true;
				} elseif ( ! $package_grants_free_shipping ) {
					$package_grants_free_shipping = true;
				}
			} else {
				if ( 'all' === $free_shipping_granting_products_type ) {
					return $is_available;
				}
			}
		}
		return ( $package_grants_free_shipping ) ? true : $is_available;
	}

	/**
	 * shipping_icon.
	 *
	 * @version 2.6.0
	 * @since   2.5.6
	 */
	function shipping_icon( $label, $method ) {
		$shipping_icons_visibility = apply_filters( 'booster_get_option', 'both', get_option( 'wcj_shipping_icons_visibility', 'both' ) );
		if ( 'checkout_only' === $shipping_icons_visibility && is_cart() ) {
			return $label;
		}
		if ( 'cart_only' === $shipping_icons_visibility && is_checkout() ) {
			return $label;
		}
		if ( '' != ( $icon_url = get_option( 'wcj_shipping_icon_' . $method->method_id, '' ) ) ) {
			$style_html = ( '' != ( $style = get_option( 'wcj_shipping_icons_style', 'display:inline;' ) ) ) ?  'style="' . $style . '" ' : '';
			$img = '<img ' . $style_html . 'class="wcj_shipping_icon" id="wcj_shipping_icon_' . $method->method_id . '" src="' . $icon_url . '">';
			$label = ( 'before' === get_option( 'wcj_shipping_icons_position', 'before' ) ) ? $img . ' ' . $label : $label . ' ' . $img;
		}
		return $label;
	}

	/**
	 * shipping_description.
	 *
	 * @version 2.6.0
	 * @since   2.5.6
	 */
	function shipping_description( $label, $method ) {
		$shipping_descriptions_visibility = apply_filters( 'booster_get_option', 'both', get_option( 'wcj_shipping_descriptions_visibility', 'both' ) );
		if ( 'checkout_only' === $shipping_descriptions_visibility && is_cart() ) {
			return $label;
		}
		if ( 'cart_only' === $shipping_descriptions_visibility && is_checkout() ) {
			return $label;
		}
		if ( '' != ( $desc = get_option( 'wcj_shipping_description_' . $method->method_id, '' ) ) ) {
			$label .= $desc;
		}
		return $label;
	}

	/**
	 * hide_shipping_when_free_is_available.
	 *
	 * @version 2.5.3
	 * @todo    if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_local_delivery' ) ) { unset( $rates['local_delivery'] ); }
	 */
	function hide_shipping_when_free_is_available( $rates, $package ) {
		$free_shipping_rates = array();
		$is_free_shipping_available = false;
		foreach ( $rates as $rate_key => $rate ) {
			if ( false !== strpos( $rate_key, 'free_shipping' ) ) {
				$is_free_shipping_available = true;
				$free_shipping_rates[ $rate_key ] = $rate;
			}
		}
		return ( $is_free_shipping_available ) ? $free_shipping_rates : $rates;
	}

	/**
	 * add_hide_shipping_if_free_available_fields.
	 *
	 * @version 2.5.3
	 */
	function add_hide_shipping_if_free_available_fields( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			$updated_settings[] = $section;
			if ( isset( $section['id'] ) && 'woocommerce_ship_to_destination' === $section['id'] ) {
				/* $updated_settings[] = array(
					'title'    => __( 'Booster: Hide shipping', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
					'desc_tip' => __( '', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
					'default'  => 'no',
					'type'     => 'checkbox',
					'checkboxgroup' => 'start',
				); */
				$updated_settings[] = array(
					'title'    => __( 'Booster: Hide shipping', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_all',
					'default'  => 'no',
					'type'     => 'checkbox',
					/* 'checkboxgroup' => 'end', */
				);
			}
		}
		return $updated_settings;
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function add_settings_hook() {
		add_filter( 'wcj_' . $this->id . '_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.6
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_' . $this->id . '_settings', $settings );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.6.0
	 * @since   2.5.6
	 */
	function add_settings( $settings ) {
		$wocommerce_shipping_settings_url = admin_url( 'admin.php?page=wc-settings&tab=shipping' );
		$wocommerce_shipping_settings_url = '<a href="' . $wocommerce_shipping_settings_url . '">' . __( 'WooCommerce > Settings > Shipping', 'woocommerce-jetpack' ) . '</a>';
		$settings = array(
			array(
				'title'    => __( 'Custom Shipping', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_shipping_custom_shipping_w_zones_options',
				'desc'     => __( 'This section lets you add custom shipping method.', 'woocommerce-jetpack' )
					. ' ' . sprintf( __( 'Visit %s to set method\'s options.', 'woocommerce-jetpack' ), $wocommerce_shipping_settings_url ),
			),
			array(
				'title'    => __( 'Custom Shipping', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_custom_shipping_w_zones_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Admin Title', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_custom_shipping_w_zones_admin_title',
				'default'  => __( 'Booster: Custom Shipping', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'css'      => 'width:300px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_custom_shipping_w_zones_options',
			),
		);
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Custom Shipping (Legacy - without Shipping Zones)', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_shipping_custom_shipping_options',
				'desc'     => __( 'This section lets you set number of custom shipping methods to add.', 'woocommerce-jetpack' )
					. ' ' . sprintf( __( 'After setting the number, visit %s to set each method options.', 'woocommerce-jetpack' ), $wocommerce_shipping_settings_url ),
			),
			array(
				'title'    => __( 'Custom Shipping Methods Number', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_custom_shipping_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ?
					apply_filters( 'booster_get_message', '', 'readonly' ) : array( 'step' => '1', 'min' => '0' ),
			),
		) );
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_shipping_custom_shipping_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings[] = array(
				'title'    => __( 'Admin Title Custom Shipping', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'       => 'wcj_shipping_custom_shipping_admin_title_' . $i,
				'default'  => __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'     => 'text',
			);
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_custom_shipping_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Hide if Free is Available', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you hide other shipping options when free shipping is available on shop frontend.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_options',
			),
			/* array(
				'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			), */
			array(
				'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_all',
				'default'  => 'no',
				'type'     => 'checkbox',
				/* 'checkboxgroup' => 'end', */
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_hide_if_free_available_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Free Shipping by Product', 'woocommerce-jetpack' ),
				'desc'     => __( 'In this section you can select products which grant free shipping when added to cart.', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_shipping_free_shipping_by_product_options',
			),
			array(
				'title'    => __( 'Free Shipping by Product', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_free_shipping_by_product_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Products', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_free_shipping_by_product_products',
				'default'  => '',
				'type'     => 'multiselect',
				'options'  => wcj_get_products(),
				'class'    => 'chosen_select',
			),
			array(
				'title'    => __( 'Type', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Select either <strong>all products</strong> or <strong>at least one product</strong> in cart must grant free shipping.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_free_shipping_by_product_type',
				'default'  => 'all',
				'type'     => 'select',
				'options'  => array(
					'all'          => __( 'All products in cart must grant free shipping', 'woocommerce-jetpack' ),
					'at_least_one' => __( 'At least one product in cart must grant free shipping', 'woocommerce-jetpack' ),
				),
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_free_shipping_by_product_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Shipping Descriptions', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => sprintf( __( 'This section will allow you to add any text (e.g. description) for shipping method. Text will be visible on cart and checkout pages. You can add HTML tags here, e.g. try "%s"', 'woocommerce-jetpack' ), esc_html( '<br><small>Your shipping description.</small>' ) ),
				'id'       => 'wcj_shipping_description_options',
			),
			array(
				'title'    => __( 'Shipping Descriptions', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_description_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Description Visibility', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_descriptions_visibility',
				'default'  => 'both',
				'type'     => 'select',
				'options'  => array(
					'both'          => __( 'On both cart and checkout pages', 'woocommerce-jetpack' ),
					'cart_only'     => __( 'Only on cart page', 'woocommerce-jetpack' ),
					'checkout_only' => __( 'Only on checkout page', 'woocommerce-jetpack' ),
				),
				'desc_tip' => __( 'Possible values: on both cart and checkout pages; only on cart page; only on checkout page', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
			),
		) );
		foreach ( WC()->shipping->get_shipping_methods() as $method ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => $method->method_title,
					'id'       => 'wcj_shipping_description_' . $method->id,
					'default'  => '',
					'type'     => 'textarea',
					'css'      => 'width:30%;min-width:300px;',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_description_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Shipping Icons', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section will allow you to add icons for shipping method. Icons will be visible on cart and checkout pages.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_icons_options',
			),
			array(
				'title'    => __( 'Shipping Icons', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_icons_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Icon Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_icons_position',
				'default'  => 'before',
				'type'     => 'select',
				'options'  => array(
					'before' => __( 'Before label', 'woocommerce-jetpack' ),
					'after'  => __( 'After label', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Icon Visibility', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_icons_visibility',
				'default'  => 'both',
				'type'     => 'select',
				'options'  => array(
					'both'          => __( 'On both cart and checkout pages', 'woocommerce-jetpack' ),
					'cart_only'     => __( 'Only on cart page', 'woocommerce-jetpack' ),
					'checkout_only' => __( 'Only on checkout page', 'woocommerce-jetpack' ),
				),
				'desc_tip' => __( 'Possible values: on both cart and checkout pages; only on cart page; only on checkout page', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
			),
			array(
				'title'    => __( 'Icon Style', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'You can also style icons with CSS class "wcj_shipping_icon", or id "wcj_shipping_icon_method_id"', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_icons_style',
				'default'  => 'display:inline;',
				'type'     => 'text',
				'css'      => 'width:20%;min-width:300px;',
			),
		) );
		foreach ( WC()->shipping->get_shipping_methods() as $method ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => $method->method_title,
					'desc_tip' => __( 'Image URL', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_icon_' . $method->id,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:30%;min-width:300px;',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_icons_options',
			),
		) );
		return $settings;
	}
}

endif;

return new WCJ_Shipping();

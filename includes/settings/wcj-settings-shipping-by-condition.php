<?php
/**
 * Booster for WooCommerce - Settings - Shipping by Condition
 *
 * @version 7.0.0
 * @since   3.2.1
 * @author  Pluggabl LLC.
 * @todo    [dev] hide settings for the disabled subsection
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message                = apply_filters( 'booster_message', '', 'desc' );
$use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_' . $this->id . '_use_shipping_instance', 'no' ) );
$shipping_methods       = ( $use_shipping_instances ? wcj_get_shipping_methods_instances( true ) : WC()->shipping()->load_shipping_methods() );
$settings               = array();

// Multiple Roles Option.
$check_multiple_roles_option = array(
	'title'             => __( 'Multiple Role Checking', 'woocommerce-jetpack' ),
	'type'              => 'checkbox',
	'default'           => 'no',
	'desc_tip'          => __( 'Enable if you have some plugin that allows users with multiple roles like "User Role Editor".', 'woocommerce-jetpack' ),
	'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
	'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	'id'                => 'wcj_' . $this->id . '_check_multiple_roles',
);

if ( 'shipping_by_time' === $this->id ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'shipping_by_condition_options',
				'type' => 'sectionend',
			),
			array(
				'id'      => 'shipping_by_condition_options',
				'type'    => 'tab_ids',
				'tab_ids' => array(
					'shipping_by_condition_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_time_tab' => __( 'By Date/Time', 'woocommerce-jetpack' ),
					'shipping_by_condition_advanced_options_tab' => __( 'Advanced Options', 'woocommerce-jetpack' ),
				),
			),
			array(
				'id'   => 'shipping_by_condition_general_options_tab',
				'type' => 'tab_start',
			),
		)
	);
}
if ( 'shipping_by_cities' === $this->id ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'shipping_by_condition_options',
				'type' => 'sectionend',
			),
			array(
				'id'      => 'shipping_by_condition_options',
				'type'    => 'tab_ids',
				'tab_ids' => array(
					'shipping_by_condition_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_cities_tab' => __( 'By Cities', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_postcodes_tab' => __( 'By Postcodes', 'woocommerce-jetpack' ),
					'shipping_by_condition_advanced_options_tab' => __( 'Advanced Options', 'woocommerce-jetpack' ),
				),
			),
			array(
				'id'   => 'shipping_by_condition_general_options_tab',
				'type' => 'tab_start',
			),
		)
	);
}
if ( 'shipping_by_products' === $this->id ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'shipping_by_condition_options',
				'type' => 'sectionend',
			),
			array(
				'id'      => 'shipping_by_condition_options',
				'type'    => 'tab_ids',
				'tab_ids' => array(
					'shipping_by_condition_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_products_tab' => __( 'By Products', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_product_cats_tab' => __( 'By Product Categories', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_product_tags_tab' => __( 'By Product Tags', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_classes_tab' => __( 'By Product Shipping Classes', 'woocommerce-jetpack' ),
					'shipping_by_condition_advanced_options_tab' => __( 'Advanced Options', 'woocommerce-jetpack' ),
				),
			),
			array(
				'id'   => 'shipping_by_condition_general_options_tab',
				'type' => 'tab_start',
			),
		)
	);
}
if ( 'shipping_by_user_role' === $this->id ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'shipping_by_condition_options',
				'type' => 'sectionend',
			),
			array(
				'id'      => 'shipping_by_condition_options',
				'type'    => 'tab_ids',
				'tab_ids' => array(
					'shipping_by_condition_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_user_roles_tab' => __( 'By User Roles', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_user_id_tab' => __( 'By Users', 'woocommerce-jetpack' ),
					'shipping_by_condition_by_user_membership_tab' => __( 'By User Membership Plans', 'woocommerce-jetpack' ),
					'shipping_by_condition_advanced_options_tab' => __( 'Advanced Options', 'woocommerce-jetpack' ),
				),
			),
			array(
				'id'   => 'shipping_by_condition_general_options_tab',
				'type' => 'tab_start',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'title' => __( 'General Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_' . $this->id . '_general_options',
		),
		array(
			'title'    => __( 'Use Shipping Instances', 'woocommerce-jetpack' ),
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Enable this if you want to use shipping methods instances instead of shipping methods.', 'woocommerce-jetpack' ) . ' ' .
				__( 'Save changes after enabling this option.', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'id'       => 'wcj_' . $this->id . '_use_shipping_instance',
			'default'  => 'no',
		),
		$this->add_multiple_roles_option() ? $check_multiple_roles_option : array(),
		array(
			'id'   => 'wcj_' . $this->id . '_general_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'shipping_by_condition_general_options_tab',
			'type' => 'tab_end',
		),
	)
);

foreach ( $this->condition_options as $options_id => $options_data ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'shipping_by_condition_by_' . $options_id . '_tab',
				'type' => 'tab_start',
			),
			array(
				/* translators: %s: translators Added */
				'title' => sprintf( __( 'Shipping Methods by %s', 'woocommerce-jetpack' ), $options_data['title'] ),
				'type'  => 'title',
				'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' ) . ' ' . $options_data['desc'],
				'id'    => 'wcj_shipping_by_' . $options_id . '_options',
			),
			array(
				/* translators: %s: translators Added */
				'title'   => sprintf( __( 'Shipping Methods by %s', 'woocommerce-jetpack' ), $options_data['title'] ),
				'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
				'id'      => 'wcj_shipping_by_' . $options_id . '_section_enabled',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		)
	);
	$settings = array_merge( $settings, $this->get_additional_section_settings( $options_id ) );
	$options  = $this->get_condition_options( $options_id );
	$types    = ( isset( $options_data['type'] ) ? $options_data['type'] : 'multiselect' );
	$class    = ( isset( $options_data['class'] ) ? $options_data['class'] : 'chosen_select' );
	$css      = ( isset( $options_data['css'] ) ? $options_data['css'] : '' );
	foreach ( $shipping_methods as $method ) {
		$method_id = ( $use_shipping_instances ? $method['shipping_method_id'] : $method->id );
		if ( ! in_array( $method_id, array( 'flat_rate', 'local_pickup' ), true ) ) {
			$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
			if ( '' === $custom_attributes ) {
				$custom_attributes = array();
			}
			$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
		} else {
			$custom_attributes = array();
			$desc_tip          = '';
		}
		$include_id = 'wcj_shipping_' . $options_id . '_include_' . ( $use_shipping_instances ? 'instance_' . $method['shipping_method_instance_id'] : $method->id );
		$exclude_id = 'wcj_shipping_' . $options_id . '_exclude_' . ( $use_shipping_instances ? 'instance_' . $method['shipping_method_instance_id'] : $method->id );

		if ( 'user_id' === $options_id ) {
			$settings = array_merge(
				$settings,
				array(
					wcj_get_ajax_settings(
						array(
							'title'             => ( $use_shipping_instances ? $method['zone_name'] . ': ' . $method['shipping_method_title'] : $method->get_method_title() ),
							'desc_tip'          => $desc_tip,
							/* translators: %s: translators Added */
							'desc'              => '<br>' . sprintf( __( 'Include %s', 'woocommerce-jetpack' ), $options_data['title'] ) . $this->get_extra_option_desc( $include_id ),
							'id'                => $include_id,
							'default'           => '',
							'css'               => $css,
							'custom_attributes' => $custom_attributes,
						),
						true,
						'woocommerce_json_search_customers'
					),
					wcj_get_ajax_settings(
						array(
							'desc_tip'          => $desc_tip,
							/* translators: %s: translators Added */
							'desc'              => '<br>' . sprintf( __( 'Exclude %s', 'woocommerce-jetpack' ), $options_data['title'] ) . $this->get_extra_option_desc( $exclude_id ),
							'id'                => $exclude_id,
							'default'           => '',
							'css'               => $css,
							'custom_attributes' => $custom_attributes,
						),
						true,
						'woocommerce_json_search_customers'
					),
				)
			);
		} else {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title'             => ( $use_shipping_instances ? $method['zone_name'] . ': ' . $method['shipping_method_title'] : $method->get_method_title() ),
						'desc_tip'          => $desc_tip,
						/* translators: %s: translators Added */
						'desc'              => '<br>' . sprintf( __( 'Include %s', 'woocommerce-jetpack' ), $options_data['title'] ) . $this->get_extra_option_desc( $include_id ),
						'id'                => $include_id,
						'default'           => '',
						'type'              => $types,
						'class'             => $class,
						'css'               => $css,
						'options'           => $options,
						'custom_attributes' => $custom_attributes,
					),
					array(
						'desc_tip'          => $desc_tip,
						/* translators: %s: translators Added */
						'desc'              => '<br>' . sprintf( __( 'Exclude %s', 'woocommerce-jetpack' ), $options_data['title'] ) . $this->get_extra_option_desc( $exclude_id ),
						'id'                => $exclude_id,
						'default'           => '',
						'type'              => $types,
						'class'             => $class,
						'css'               => $css,
						'options'           => $options,
						'custom_attributes' => $custom_attributes,
					),
				)
			);
		}
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_shipping_by_' . $options_id . '_options',
				'type' => 'sectionend',
			),
			array(
				'id'   => 'shipping_by_condition_by_' . $options_id . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'shipping_by_condition_advanced_options_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Advanced Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_' . $this->id . '_advanced_options',
		),
		array(
			'title'    => __( 'Filter Priority', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set to zero to use the default priority.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_' . $this->id . '_filter_priority',
			'default'  => 0,
			'type'     => 'number',
			'desc'     => __( 'Change the Priority of the current module\'s execution, Greater value for late execution & Lower value for early execution.', 'woocommerce-jetpack' ),
		),
		array(
			'id'   => 'wcj_' . $this->id . '_advanced_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'shipping_by_condition_advanced_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;

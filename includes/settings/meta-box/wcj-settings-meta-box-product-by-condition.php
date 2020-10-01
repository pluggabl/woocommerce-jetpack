<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Visibility by Condition
 *
 * @version 3.6.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$option_styling = ( 'standard' === wcj_get_option( 'wcj_' . $this->id . '_select_style', 'chosen_select' ) ?
	array(
		'tooltip'    => __( 'Use "Control" key to select/deselect multiple options. Hold "Control" and "A" to select all options. Leave empty to disable.', 'woocommerce-jetpack' ),
		'css'        => 'height:200px;',
		'class'      => 'widefat',
		'show_value' => true,
	) :
	array(
		'css'        => 'width:100%;',
		'class'      => 'chosen_select',
	)
);
$all_options = $this->get_options_list();
$options = array();
if ( 'invisible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
	$options = array_merge( $options, array( array_merge(
		array(
			'title'    => __( 'Visible', 'woocommerce-jetpack' ),
			'name'     => 'wcj_' . $this->id . '_visible',
			'default'  => '',
			'type'     => 'select',
			'options'  => $all_options,
			'multiple' => true,
		), $option_styling )
	) );
}
if ( 'visible' != apply_filters( 'booster_option', 'visible', wcj_get_option( 'wcj_' . $this->id . '_visibility_method', 'visible' ) ) ) {
	$options = array_merge( $options, array( array_merge(
		array(
			'title'    => __( 'Invisible', 'woocommerce-jetpack' ),
			'name'     => 'wcj_' . $this->id . '_invisible',
			'default'  => '',
			'type'     => 'select',
			'options'  => $all_options,
			'multiple' => true,
		), $option_styling )
	) );
}
return $options;

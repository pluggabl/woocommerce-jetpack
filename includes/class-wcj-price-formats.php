<?php
/**
 * WooCommerce Jetpack Price Formats
 *
 * The WooCommerce Jetpack Price Formats class.
 *
 * @version 2.5.2
 * @since   2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_Formats' ) ) :

class WCJ_Price_Formats extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function __construct() {

		$this->id         = 'price_formats';
		$this->short_desc = __( 'Price Formats', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set different WooCommerce price formats for different currencies.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-price-formats/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
			add_filter( 'wc_price_args', array( $this, 'price_format' ), PHP_INT_MAX );
		}
	}

	/**
	 * price_format.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function price_format( $args ) {
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_formats_total_number', 1 ) ); $i++ ) {
			if ( get_woocommerce_currency() === get_option( 'wcj_price_formats_currency_' . $i ) ) {
				$args['price_format']       = $this->get_woocommerce_price_format( get_option( 'wcj_price_formats_currency_position_' . $i ) );
				$args['decimal_separator']  = get_option( 'wcj_price_formats_decimal_separator_'  . $i );
				$args['thousand_separator'] = get_option( 'wcj_price_formats_thousand_separator_' . $i );
				$args['decimals']           = absint( get_option( 'wcj_price_formats_number_of_decimals_' . $i ) );
			}
		}
		return $args;
	}

	/**
	 * get_woocommerce_price_format.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_woocommerce_price_format( $currency_pos ) {
		$format = '%1$s%2$s';

		switch ( $currency_pos ) {
			case 'left' :
				$format = '%1$s%2$s';
			break;
			case 'right' :
				$format = '%2$s%1$s';
			break;
			case 'left_space' :
				$format = '%1$s&nbsp;%2$s';
			break;
			case 'right_space' :
				$format = '%2$s&nbsp;%1$s';
			break;
		}

		return apply_filters( 'woocommerce_price_format', $format, $currency_pos );
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_settings_hook() {
		add_filter( 'wcj_price_formats_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_settings() {
		$settings = apply_filters( 'wcj_price_formats_settings', array() );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_settings() {
		$settings = array(
			array(
				'title'    => __( 'Formats', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_price_formats_options',
			),
			array(
				'title'    => __( 'Total Number', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_formats_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '0', )
				),
			),
		);
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_formats_total_number', 1 ) ); $i++ ) {
			$currency_symbol = wcj_get_currency_symbol( get_option( 'wcj_price_formats_currency_' . $i, get_woocommerce_currency() ) );
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Format', 'woocommerce-jetpack' ) . ' #' . $i,
					'desc'     => __( 'Currency', 'woocommerce-jetpack' ),
					'id'       => 'wcj_price_formats_currency_' . $i,
					'default'  => get_woocommerce_currency(),
					'type'     => 'select',
					'options'  => wcj_get_currencies_names_and_symbols(),
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Currency Position', 'woocommerce-jetpack' ),
					'id'       => 'wcj_price_formats_currency_position_' . $i,
					'default'  => get_option( 'woocommerce_currency_pos' ),
					'type'     => 'select',
					'options'  => array(
						'left'        => __( 'Left', 'woocommerce' ) . ' (' . $currency_symbol . '99.99)',
						'right'       => __( 'Right', 'woocommerce' ) . ' (99.99' . $currency_symbol . ')',
						'left_space'  => __( 'Left with space', 'woocommerce' ) . ' (' . $currency_symbol . ' 99.99)',
						'right_space' => __( 'Right with space', 'woocommerce' ) . ' (99.99 ' . $currency_symbol . ')'
					),
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Thousand Separator', 'woocommerce-jetpack' ),
					'id'       => 'wcj_price_formats_thousand_separator_' . $i,
					'default'  => wc_get_price_thousand_separator(),
					'type'     => 'text',
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Decimal Separator', 'woocommerce-jetpack' ),
					'id'       => 'wcj_price_formats_decimal_separator_' . $i,
					'default'  => wc_get_price_decimal_separator(),
					'type'     => 'text',
					'css'      => 'width:300px;',
				),
				array(
					'desc'     => __( 'Number of Decimals', 'woocommerce-jetpack' ),
					'id'       => 'wcj_price_formats_number_of_decimals_' . $i,
					'default'  => wc_get_price_decimals(),
					'type'     => 'number',
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1
					),
					'css'      => 'width:300px;',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_price_formats_options',
			),
		) );
		return $settings;
	}
}

endif;

return new WCJ_Price_Formats();

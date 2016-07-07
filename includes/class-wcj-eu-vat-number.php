<?php
/**
 * WooCommerce Jetpack EU VAT Number
 *
 * The WooCommerce Jetpack EU VAT Number class.
 *
 * @version 2.5.4
 * @since   2.3.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_EU_VAT_Number' ) ) :

class WCJ_EU_VAT_Number extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.4
	 */
	function __construct() {

		$this->id         = 'eu_vat_number';
		$this->short_desc = __( 'EU VAT Number', 'woocommerce-jetpack' );
		$this->desc       = __( 'Collect and validate EU VAT numbers on WooCommerce checkout. Automatically disable VAT for valid numbers. Add all EU countries VAT standard rates to WooCommerce.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-eu-vat-number/';
		parent::__construct();

		$this->add_tools( array(
			'eu_countries_vat_rates' => array(
				'title' => __( 'EU Countries VAT Rates', 'woocommerce-jetpack' ),
				'desc'  => __( 'Add all EU countries VAT standard rates to WooCommerce.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {
			/* if ( ! session_id() ) {
				session_start();
			} */
//			add_action( 'init',                                        'session_start' );
			add_action( 'init',                                        array( $this, 'start_session' ) );
			add_filter( 'woocommerce_checkout_fields',                 array( $this, 'add_eu_vat_number_checkout_field_to_frontend' ), PHP_INT_MAX );
			add_filter( 'woocommerce_admin_billing_fields',            array( $this, 'add_billing_eu_vat_number_field_to_admin_order_display' ), PHP_INT_MAX );
			add_action( 'wp_enqueue_scripts',                          array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_ajax_wcj_validate_eu_vat_number',          array( $this, 'wcj_validate_eu_vat_number' ) );
			add_action( 'wp_ajax_nopriv_wcj_validate_eu_vat_number',   array( $this, 'wcj_validate_eu_vat_number' ) );
//			add_filter( 'woocommerce_form_field_text',                 array( $this, 'add_eu_vat_verify_button' ), PHP_INT_MAX, 4 );
//			add_action( 'init',                                        array( $this, 'wcj_validate_eu_vat_number' ) );
//			add_filter( 'woocommerce_find_rates',                      array( $this, 'maybe_exclude_vat' ), PHP_INT_MAX, 2 );
			add_filter( 'init',                                        array( $this, 'maybe_exclude_vat' ), PHP_INT_MAX );
			add_action( 'woocommerce_after_checkout_validation',       array( $this, 'checkout_validate_vat' ), PHP_INT_MAX );
			add_filter( 'woocommerce_customer_meta_fields',            array( $this, 'add_eu_vat_number_customer_meta_field' ) );
			add_filter( 'default_checkout_billing_eu_vat_number',      array( $this, 'add_default_checkout_billing_eu_vat_number' ), PHP_INT_MAX, 2 );

			if ( 'after_order_table' === get_option( 'wcj_eu_vat_number_display_position', 'after_order_table' ) ) {
				add_action( 'woocommerce_order_details_after_order_table', array( $this, 'add_eu_vat_number_to_order_display' ), PHP_INT_MAX );
				add_action( 'woocommerce_email_after_order_table',         array( $this, 'add_eu_vat_number_to_order_display' ), PHP_INT_MAX );
			} else {
				add_filter( 'woocommerce_order_formatted_billing_address',         array( $this, 'add_eu_vat_number_to_order_billing_address' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'add_eu_vat_number_to_my_account_billing_address' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_localisation_address_formats',            array( $this, 'add_eu_vat_number_to_address_formats' ) );
				add_filter( 'woocommerce_formatted_address_replacements',          array( $this, 'replace_eu_vat_number_in_address_formats' ), PHP_INT_MAX, 2 );
			}

			$this->eu_countries_vat_rates_tool = include_once( 'tools/class-wcj-eu-countries-vat-rates-tool.php' );
		}
	}

	/**
	 * replace_eu_vat_number_in_address_formats.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function replace_eu_vat_number_in_address_formats( $replacements, $args ) {
		$field_name = 'billing_' . $this->id;
		$replacements['{' . $field_name . '}'] = ( isset( $args[ $field_name ] ) ) ? $args[ $field_name ] : '';
		return $replacements;
	}

	/**
	 * add_eu_vat_number_to_address_formats.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_eu_vat_number_to_address_formats( $address_formats ) {
		$field_name = 'billing_' . $this->id;
		$modified_address_formats = array();
		foreach ( $address_formats as $country => $address_format ) {
			$modified_address_formats[ $country ] = $address_format . "\n{" . $field_name . '}';
		}
		return $modified_address_formats;
	}

	/**
	 * add_eu_vat_number_to_my_account_billing_address.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_eu_vat_number_to_my_account_billing_address( $fields, $customer_id, $name ) {
		if ( 'billing' === $name ) {
			$field_name = 'billing_' . $this->id;
			$fields[ $field_name ] = get_user_meta( $customer_id, $field_name, true );
		}
		return $fields;
	}

	/**
	 * add_eu_vat_number_to_order_billing_address.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_eu_vat_number_to_order_billing_address( $fields, $_order ) {
		$field_name = 'billing_' . $this->id;
		$fields[ $field_name ] = get_post_meta( $_order->id, '_' . $field_name, true );
		return $fields;
	}

	/**
	 * add_eu_vat_number_to_order_display.
	 *
	 * @version 2.4.7
	 * @since   2.4.7
	 */
	function add_eu_vat_number_to_order_display( $order ) {
		$order_id = $order->id;
		$html = '';
		$option_name = '_billing_' . $this->id;
		$the_eu_vat_number = get_post_meta( $order_id, $option_name, true );
		if ( '' != $the_eu_vat_number ) {
			$the_label = get_option( 'wcj_eu_vat_number_field_label', __( 'EU VAT Number', 'woocommerce-jetpack' ) );
			$html .= $the_label . ': ' . $the_eu_vat_number . '<br>';
		}
		echo $html;
	}

	/**
	 * create_eu_countries_vat_rates_tool.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function create_eu_countries_vat_rates_tool() {
		return $this->eu_countries_vat_rates_tool->create_eu_countries_vat_rates_tool( $this->get_tool_header_html( 'eu_countries_vat_rates' ) );
	}

	/**
	 * add_default_checkout_billing_eu_vat_number.
	 */
	function add_default_checkout_billing_eu_vat_number( $default_value, $field_key ) {
		if ( isset( $_SESSION['wcj_eu_vat_number_to_check'] ) ) {
			return $_SESSION['wcj_eu_vat_number_to_check'];
		} elseif ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( $meta = get_user_meta( $current_user->ID, 'billing_eu_vat_number', true ) ) {
				return $meta;
			}
		}
		return $default_value;
	}

	/**
	 * add_eu_vat_number_customer_meta_field.
	 */
	function add_eu_vat_number_customer_meta_field( $fields ) {
		$fields['billing']['fields']['billing_eu_vat_number'] = array(
			'label'       => get_option( 'wcj_eu_vat_number_field_label' ),
			'description' => ''
		);
		return $fields;
	}

	/**
	 * start_session.
	 */
	function start_session() {
		if ( ! session_id() ) {
			session_start();
		}
		$args = array();
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $args );
			if ( isset( $args['billing_eu_vat_number'] ) && isset( $_SESSION['wcj_eu_vat_number_to_check'] ) && $_SESSION['wcj_eu_vat_number_to_check'] != $args['billing_eu_vat_number'] ) {
				unset( $_SESSION['wcj_is_eu_vat_number_valid'] );
				unset( $_SESSION['wcj_eu_vat_number_to_check'] );
			}
		}
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 2.5.4
	 */
	function enqueue_scripts() {
		if ( 'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) ) {
			wp_enqueue_script( 'wcj-eu-vat-number', wcj_plugin_url() . '/includes/js/eu-vat-number.js', array(), false, true );
			wp_localize_script( 'wcj-eu-vat-number', 'ajax_object', array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
			) );
		}
	}

	/**
	 * wcj_validate_eu_vat_number.
	 *
	 * @version 2.5.4
	 */
	function wcj_validate_eu_vat_number( $param ) {
//		if ( ! isset( $_GET['wcj_validate_eu_vat_number'] ) ) return;
		if ( isset( $_POST['wcj_eu_vat_number_to_check'] ) && '' != $_POST['wcj_eu_vat_number_to_check'] ) {
			$eu_vat_number_to_check = substr( $_POST['wcj_eu_vat_number_to_check'], 2 );
			$eu_vat_number_country_to_check = substr( $_POST['wcj_eu_vat_number_to_check'], 0, 2 );
			if ( 'yes' === apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_eu_vat_number_check_ip_location_country', 'no' ) ) ) {
				$location = WC_Geolocation::geolocate_ip();
				if ( empty( $location['country'] ) ) {
					$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
				}
				$is_valid = ( $location['country'] === $eu_vat_number_country_to_check ) ?
					validate_VAT( $eu_vat_number_country_to_check, $eu_vat_number_to_check ) :
					false;
			} else {
				$is_valid = validate_VAT( $eu_vat_number_country_to_check, $eu_vat_number_to_check );
			}
		} else {
			$is_valid = null;
		}
		$_SESSION['wcj_is_eu_vat_number_valid'] = $is_valid;
		$_SESSION['wcj_eu_vat_number_to_check'] = $_POST['wcj_eu_vat_number_to_check'];
		echo $is_valid;
		die();
	}

	/**
	 * maybe_exclude_vat.
	 *
	 * @version 2.5.4
	 */
//	function maybe_exclude_vat( $matched_tax_rates, $args ) {
	function maybe_exclude_vat() {
		if (
			( is_checkout() || is_cart() || defined( 'WOOCOMMERCE_CHECKOUT' ) || defined( 'WOOCOMMERCE_CART' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) &&
			! empty( WC()->customer ) &&
			'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) &&
			'yes' === get_option( 'wcj_eu_vat_number_disable_for_valid', 'yes' ) &&
			isset( $_SESSION['wcj_is_eu_vat_number_valid'] ) && true === $_SESSION['wcj_is_eu_vat_number_valid'] && isset( $_SESSION['wcj_eu_vat_number_to_check'] )
		) {
			$preserve_base_country_check_passed = true;
			if ( 'yes' === apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_eu_vat_number_preserve_in_base_country', 'no' ) ) ) {
				$location = wc_get_base_location();
				if ( empty( $location['country'] ) ) {
					$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
				}
				$selected_country = substr( $_SESSION['wcj_eu_vat_number_to_check'], 0, 2 );
				$preserve_base_country_check_passed = ( $location['country'] !== $selected_country ) ? true : false;
			}
			if ( $preserve_base_country_check_passed ) {
				/* $modified_matched_tax_rates = array();
				foreach ( $matched_tax_rates as $i => $matched_tax_rate ) {
					$matched_tax_rate['rate'] = 0;
					$modified_matched_tax_rates[ $i ] = $matched_tax_rate;
				}
				return $modified_matched_tax_rates; */
				WC()->customer->set_is_vat_exempt( true );
			} else {
				WC()->customer->set_is_vat_exempt( false );
			}
		} else {
			if ( ! empty( WC()->customer ) ) {
				WC()->customer->set_is_vat_exempt( false );
			}
		}
//		return $matched_tax_rates;
	}

	/**
	 * checkout_validate_vat.
	 */
	function checkout_validate_vat( $_posted ) {
		if ( 'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) ) {
			if (
				( 'yes' === get_option( 'wcj_eu_vat_number_field_required', 'no' ) && '' == $_posted['billing_eu_vat_number'] ) ||
				(
					( '' != $_posted['billing_eu_vat_number'] ) &&
					(
						! isset( $_SESSION['wcj_is_eu_vat_number_valid'] ) || false == $_SESSION['wcj_is_eu_vat_number_valid'] ||
						! isset( $_SESSION['wcj_eu_vat_number_to_check'] ) || $_posted['billing_eu_vat_number'] != $_SESSION['wcj_eu_vat_number_to_check']
					)
				)
			) {
				wc_add_notice(
					get_option( 'wcj_eu_vat_number_not_valid_message', __( '<strong>EU VAT Number</strong> is not valid.', 'woocommerce-jetpack' ) ),
					'error'
				);
			}
		}
	}

	/**
	 * add_billing_eu_vat_number_field_to_admin_order_display.
	 */
	function add_billing_eu_vat_number_field_to_admin_order_display( $fields ) {
		$fields[ $this->id ] = array(
			'type'  => 'text',
			'label' => get_option( 'wcj_eu_vat_number_field_label' ),
			'show'  => true,
		);
		return $fields;
	}

	/**
	 * add_eu_vat_verify_button.
	 *
	function add_eu_vat_verify_button( $field, $key, $args, $value ) {
		return ( 'billing_eu_vat_number' === $key ) ?
			$field . '<span style="font-size:smaller !important;">' . '[<a name="billing_eu_vat_number_verify" href="">' . __( 'Verify', 'woocommerce-jetpack' ) . '</a>]' . '</span>' :
			$field;
	}

	/**
	 * add_eu_vat_number_checkout_field_to_frontend.
	 *
	 * @version 2.4.0
	 */
	function add_eu_vat_number_checkout_field_to_frontend( $fields ) {
		$fields['billing'][ 'billing_' . $this->id ] = array(
			'type'              => 'text',
//			'default'           => isset( $_SESSION['wcj_eu_vat_number_to_check'] ) ? $_SESSION['wcj_eu_vat_number_to_check'] : '',
			'label'             => get_option( 'wcj_eu_vat_number_field_label' ),
			'description'       => get_option( 'wcj_eu_vat_number_field_description' ),
			'placeholder'       => get_option( 'wcj_eu_vat_number_field_placeholder' ),
			'required'          => ( 'yes' === get_option( 'wcj_eu_vat_number_field_required', 'no' ) ) ? true : false,
			'custom_attributes' => array(),
			'clear'             => ( 'yes' === get_option( 'wcj_eu_vat_number_field_clear', 'yes' ) ) ? true : false,
			'class'             => array( get_option( 'wcj_eu_vat_number_field_class', 'form-row-wide' ) ),
			'validate'          => ( 'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) ) ? array( 'eu-vat-number' ) : array(),
		);
		return $fields;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.2
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'   => __( 'Options', 'woocommerce-jetpack' ),
				'type'    => 'title',
				'id'      => 'wcj_eu_vat_number_options'
			),
			array(
				'title'   => __( 'Field Label', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_field_label',
				'default' => __( 'EU VAT Number', 'woocommerce-jetpack' ),
				'type'    => 'text',
				'css'     => 'width:300px;',
			),
			array(
				'title'   => __( 'Placeholder', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_field_placeholder',
				'default' => __( 'EU VAT Number', 'woocommerce-jetpack' ),
				'type'    => 'text',
				'css'     => 'width:300px;',
			),
			array(
				'title'   => __( 'Description', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_field_description',
				'default' => '',
				'type'    => 'text',
				'css'     => 'width:300px;',
			),
			/* array(
				'title'   => __( 'Require Country Code in VAT Number', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_field_require_country_code',
				'default' => 'yes',
				'type'    => 'checkbox',
			), */
			array(
				'title'   => __( 'Required', 'woocommerce-jetpack' ),
				'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_field_required',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Clear', 'woocommerce-jetpack' ),
				'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_field_clear',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Class', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_field_class',
				'default' => 'form-row-wide',
				'type'    => 'select',
				'options' => array(
					'form-row-wide'  => __( 'Wide', 'woocommerce-jetpack' ),
					'form-row-first' => __( 'First', 'woocommerce-jetpack' ),
					'form-row-last'  => __( 'Last', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'   => __( 'Validate', 'woocommerce-jetpack' ),
				'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_validate',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => '',
				'desc'    => __( 'Message on not valid', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_not_valid_message',
				'default' => __( '<strong>EU VAT Number</strong> is not valid.', 'woocommerce-jetpack' ),
				'type'    => 'textarea',
				'css'     => 'width:300px;',
			),
			array(
				'title'   => __( 'Exempt VAT for Valid Numbers', 'woocommerce-jetpack' ),
				'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_disable_for_valid',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Preserve VAT in Base Country', 'woocommerce-jetpack' ),
				'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_preserve_in_base_country',
				'default' => 'no',
				'type'    => 'checkbox',
				'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
				           => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),
			/* array(
				'title'   => '',
				'desc'    => __( 'Message if customer is in base country and VAT is NOT exempted.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_preserve_in_base_country_message',
				'default' => __( 'EU VAT Number is valid, however VAT is not exempted.', 'woocommerce-jetpack' ),
				'type'    => 'textarea',
				'css'     => 'width:300px;',
			), */
			array(
				'title'   => __( 'Check for IP Location Country', 'woocommerce-jetpack' ),
				'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_check_ip_location_country',
				'default' => 'no',
				'type'    => 'checkbox',
				'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
				           => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),
			/* array(
				'title'   => '',
				'desc'    => __( 'Message if customer\'s check for IP location country has failed.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_check_ip_location_country_message',
				'default' => __( 'IP must be from same country as VAT ID.', 'woocommerce-jetpack' ),
				'type'    => 'textarea',
				'css'     => 'width:300px;',
			), */
			array(
				'title'   => __( 'Display', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_display_position',
				'default' => 'after_order_table',
				'type'    => 'select',
				'options' => array(
					'after_order_table'  => __( 'After order table', 'woocommerce-jetpack' ),
					'in_billing_address' => __( 'In billing address', 'woocommerce-jetpack' ),
				),
			),
			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_eu_vat_number_options'
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_EU_VAT_Number();

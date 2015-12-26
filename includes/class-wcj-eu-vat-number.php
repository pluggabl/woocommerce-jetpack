<?php
/**
 * WooCommerce Jetpack EU VAT Number
 *
 * The WooCommerce Jetpack EU VAT Number class.
 *
 * @version 2.3.10
 * @since   2.3.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_EU_VAT_Number' ) ) :

class WCJ_EU_VAT_Number extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.10
	 */
	function __construct() {

		$this->id         = 'eu_vat_number';
		$this->short_desc = __( 'EU VAT Number', 'woocommerce-jetpack' );
		$this->desc       = __( 'Collect and validate EU VAT numbers on WooCommerce checkout. Automatically disable VAT for valid numbers.', 'woocommerce-jetpack' );
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
//			add_action( 'init',                                   'session_start' );
			add_action( 'init',                                   array( $this, 'start_session' ) );
			add_filter( 'woocommerce_checkout_fields',            array( $this, 'add_eu_vat_number_checkout_field_to_frontend' ), PHP_INT_MAX );
			add_filter( 'woocommerce_admin_billing_fields',       array( $this, 'add_billing_eu_vat_number_field_to_admin_order_display' ), PHP_INT_MAX );
			add_action( 'wp_enqueue_scripts',                     array( $this, 'enqueue_scripts' ) );
//			add_filter( 'woocommerce_form_field_text',            array( $this, 'add_eu_vat_verify_button' ), PHP_INT_MAX, 4 );
			add_action( 'init',                                   array( $this, 'wcj_validate_eu_vat_number' ) );
			add_filter( 'woocommerce_matched_rates',              array( $this, 'maybe_exclude_vat' ), PHP_INT_MAX, 2 );
			add_action( 'woocommerce_after_checkout_validation',  array( $this, 'checkout_validate_vat' ), PHP_INT_MAX );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_eu_vat_number_checkout_field_order_meta' ) );
			add_filter( 'woocommerce_customer_meta_fields',       array( $this, 'add_eu_vat_number_customer_meta_field' ) );
			add_filter( 'default_checkout_billing_eu_vat_number', array( $this, 'add_default_checkout_billing_eu_vat_number' ), PHP_INT_MAX, 2 );

			$this->eu_countries_vat_rates_tool = include_once( 'tools/class-wcj-eu-countries-vat-rates-tool.php' );
		}
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
	 */
	function enqueue_scripts() {
		if ( 'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) ) {
			wp_enqueue_script( 'wcj-eu-vat-number', wcj_plugin_url() . '/includes/js/eu-vat-number.js', array(), false, true );
		}
	}

	/**
	 * wcj_validate_eu_vat_number.
	 */
	function wcj_validate_eu_vat_number() {
		if ( ! isset( $_GET['wcj_validate_eu_vat_number'] ) ) return;
		if ( isset( $_GET['wcj_eu_vat_number_to_check'] ) && '' != $_GET['wcj_eu_vat_number_to_check'] ) {
			$eu_vat_number_to_check = substr( $_GET['wcj_eu_vat_number_to_check'], 2 );
			$eu_vat_number_country_to_check = substr( $_GET['wcj_eu_vat_number_to_check'], 0, 2 );
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
		$_SESSION['wcj_eu_vat_number_to_check'] = $_GET['wcj_eu_vat_number_to_check'];
		echo $is_valid;
		die();
	}

	/**
	 * maybe_exclude_vat.
	 */
	function maybe_exclude_vat( $matched_tax_rates, $tax_class ) {
		/* wcj_log( explode( '&', $_POST['post_data'] ) ); */
		/* if ( ! isset( $_POST['billing_eu_vat_number'] ) ) return $matched_tax_rates; */
		if (
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
				$modified_matched_tax_rates = array();
				foreach ( $matched_tax_rates as $i => $matched_tax_rate ) {
					$matched_tax_rate['rate'] = 0;
					$modified_matched_tax_rates[ $i ] = $matched_tax_rate;
				}
				return $modified_matched_tax_rates;
			}
		}
		return $matched_tax_rates;
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
	 * update_eu_vat_number_checkout_field_order_meta.
	 */
	function update_eu_vat_number_checkout_field_order_meta( $order_id ) {
		$option_name = '_billing_' . $this->id;
		if ( isset( $_POST[ $option_name ] ) ) {
			update_post_meta( $order_id, $option_name, wc_clean( $_POST[ $option_name ] ) );
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
	 */
	function add_eu_vat_number_checkout_field_to_frontend( $fields ) {
		$fields['billing'][ 'billing_' . $this->id ] = array(
			'type'              => 'text',
//			'default'           => isset( $_SESSION['wcj_eu_vat_number_to_check'] ) ? $_SESSION['wcj_eu_vat_number_to_check'] : '',
			'label'             => get_option( 'wcj_eu_vat_number_field_label' ),
//			'description'       => '',
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
	 * @version 2.3.10
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
			),
			array(
				'title'   => __( 'Placeholder', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_field_placeholder',
				'default' => __( 'EU VAT Number', 'woocommerce-jetpack' ),
				'type'    => 'text',
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
				'title'   => __( 'Message on Not Valid', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_not_valid_message',
				'default' => '<strong>EU VAT Number</strong> is not valid.',
				'type'    => 'textarea',
				'css'     => 'width:300px;',
			),
			array(
				'title'   => __( 'Disable VAT for Valid Numbers', 'woocommerce-jetpack' ),
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

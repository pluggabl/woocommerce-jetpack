<?php
/**
 * WooCommerce Jetpack EU VAT Number
 *
 * The WooCommerce Jetpack EU VAT Number class.
 *
 * @version 2.3.9
 * @since   2.3.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_EU_VAT_Number' ) ) :

class WCJ_EU_VAT_Number extends WCJ_Module {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id         = 'eu_vat_number';
		$this->short_desc = __( 'EU VAT Number', 'woocommerce-jetpack' );
		$this->desc       = __( 'Collect and validate EU VAT numbers on WooCommerce checkout. Automatically disable VAT for valid numbers.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( ! session_id() ) {
				session_start();
			}
			add_filter( 'woocommerce_checkout_fields',            array( $this, 'add_eu_vat_number_checkout_field_to_frontend' ), PHP_INT_MAX );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_eu_vat_number_checkout_field_order_meta' ) );
			add_action( 'woocommerce_admin_billing_fields',       array( $this, 'add_billing_eu_vat_number_field_to_admin_order_display' ), PHP_INT_MAX );
			add_action( 'woocommerce_after_checkout_validation',  array( $this, 'checkout_validate_vat' ), PHP_INT_MAX );
			add_filter( 'woocommerce_matched_rates',              array( $this, 'maybe_exclude_vat' ), PHP_INT_MAX, 2 );
			add_action( 'wp_enqueue_scripts',                     array( $this, 'enqueue_scripts' ) );
			add_action( 'init',                                   array( $this, 'wcj_validate_eu_vat_number' ) );
		}
	}

	/**
	 * checkout_validate_vat.
	 */
	function checkout_validate_vat( $_posted ) {
		//wcj_log( $_posted );
		if ( 'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) ) {
			if (
				( '' != $_posted['billing_eu_vat_number'] ) &&
				( ! isset( $_SESSION['wcj_is_eu_vat_number_valid'] ) || false == $_SESSION['wcj_is_eu_vat_number_valid'] )
			) {
				wc_add_notice( get_option( 'wcj_eu_vat_number_not_valid_message', __( '<strong>EU VAT Number</strong> is not valid.', 'woocommerce-jetpack' ) ), 'error' );
			}
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
			if ( 'yes' === get_option( 'wcj_eu_vat_number_check_ip_location_country', 'yes' ) ) {
				$location = WC_Geolocation::geolocate_ip();
				if ( empty( $location['country'] ) ) {
					$location = wc_format_country_state_string(
						apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) )
					);
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
	 * enqueue_scripts.
	 */
	function enqueue_scripts() {
		if ( 'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) ) {
			wp_enqueue_script( 'wcj-eu-vat-number', wcj_plugin_url() . '/includes/js/eu-vat-number.js', array(), false, true );
		}
	}

	/**
	 * maybe_exclude_vat.
	 */
	function maybe_exclude_vat( $matched_tax_rates, $tax_class ) {
		if (
			'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) &&
			'yes' === get_option( 'wcj_eu_vat_number_disable_for_valid', 'yes' ) &&
			isset( $_SESSION['wcj_is_eu_vat_number_valid'] ) && $_SESSION['wcj_is_eu_vat_number_valid'] && isset( $_SESSION['wcj_eu_vat_number_to_check'] )
		) {
			$preserve_base_country_check_passed = true;
			if ( 'yes' === get_option( 'wcj_eu_vat_number_preserve_in_base_country', 'yes' ) ) {
				$location = wc_get_base_location();
				if ( empty( $location['country'] ) ) {
					$location = wc_format_country_state_string(
						apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) )
					);
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
	 * add_billing_eu_vat_number_field_to_admin_order_display.
	 */
	function add_billing_eu_vat_number_field_to_admin_order_display( $fields ) {
		$fields[ $this->id ] = array(
			'type'  => 'text',
			'label' => __( 'EU VAT Number', 'woocommerce-jetpack' ),
			'show'  => true,
		);
		return $fields;
	}

	/**
	 * update_eu_vat_number_checkout_field_order_meta.
	 */
	function update_eu_vat_number_checkout_field_order_meta( $order_id ) {
		$the_section = 'billing';
		$option_name = $the_section . '_' . $this->id;
		if ( ! empty( $_POST[ $option_name ] ) ) {
			$the_value = (
				'yes' === get_option( 'wcj_eu_vat_number_validate', 'yes' ) &&
				isset( $_SESSION['wcj_is_eu_vat_number_valid'] ) && $_SESSION['wcj_is_eu_vat_number_valid']
			) ? $_SESSION['wcj_eu_vat_number_to_check'] : $_POST[ $option_name ];
			update_post_meta( $order_id, '_' . $option_name, wc_clean( $the_value ) );
		}
	}

	/**
	 * add_eu_vat_number_checkout_field_to_frontend.
	 */
	function add_eu_vat_number_checkout_field_to_frontend( $fields ) {
		$the_section = 'billing';
		$fields[ $the_section ][ $the_section . '_' . $this->id ] = array(
			'type'              => 'text',
			'label'             => get_option( 'wcj_eu_vat_number_field_label' ) .
				' ' . '<span style="font-size:smaller !important;">[' . '<a name="billing_eu_vat_number_verify" href="">' . __( 'Verify', 'woocommerce-jetpack' ) . '</a>]</span>',
			'placeholder'       => get_option( 'wcj_eu_vat_number_field_placeholder' ),
			'required'          => ( 'yes' === get_option( 'wcj_eu_vat_number_field_required', 'no' ) ) ? true : false,
			'custom_attributes' => array(),
			'clear'             => ( 'yes' === get_option( 'wcj_eu_vat_number_field_clear', 'yes' ) ) ? true : false,
			'class'             => array( get_option( 'wcj_eu_vat_number_field_class', 'form-row-wide' ) ),
			'validate'          => array( 'eu-vat-number' ),
		);
		return $fields;
	}

	/**
	 * get_settings.
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
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Check for IP Location Country', 'woocommerce-jetpack' ),
				'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
				'id'      => 'wcj_eu_vat_number_check_ip_location_country',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_eu_vat_number_options'
			),
		);

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_EU_VAT_Number();

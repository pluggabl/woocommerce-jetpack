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

		$this->add_tools( array( 'eu_countries_vat_rates' => __( 'EU Countries VAT Rates', 'woocommerce-jetpack' ), ) );

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

			add_filter( 'wcj_tools_tabs',                        array( $this, 'add_tool_tab' ), 100 );
			add_action( 'wcj_tools_' . 'eu_countries_vat_rates', array( $this, 'create_tool' ), 100 );
			add_action( 'init', array( $this, 'add_eu_countries_vat_rates' ) );
		}
		add_action( 'wcj_tools_dashboard', array( $this, 'add_tool_info_to_tools_dashboard' ), 100 );
	}

	/**
	 * add_tool_info_to_tools_dashboard.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	public function add_tool_info_to_tools_dashboard() {
		echo '<tr>';
		$is_enabled = ( $this->is_enabled() ) ?
			'<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' )  . '</span>' :
			'<span style="color:gray;font-style:italic;">'  . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
		echo '<td>' . __( 'EU Countries VAT Rates', 'woocommerce-jetpack' ) . '</td>';
		echo '<td>' . $is_enabled . '</td>';
		echo '<td>' . __( 'EU Countries VAT Rates.', 'woocommerce-jetpack' ) . '</td>';
		echo '</tr>';
	}

	/**
	 * add_tool_tab.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	public function add_tool_tab( $tabs ) {
		$tabs[] = array(
			'id'    => 'eu_countries_vat_rates',
			'title' => __( 'EU Countries VAT Rates', 'woocommerce-jetpack' ),
		);
		return $tabs;
	}

	/**
	 * add_eu_countries_vat_rates.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	public function add_eu_countries_vat_rates() {

		if ( ! isset( $_GET['add_eu_countries_vat_rates'] ) ) return;
		if ( ! is_super_admin() ) return;

		$loop = 0;

		foreach ( $this->get_european_union_countries_with_vat_rate() as $country => $rate ) {

			$tax_rate = array(
				'tax_rate_country'  => $country,
				'tax_rate'          => $rate,

				'tax_rate_name'     => __( 'VAT', 'woocommerce' ),//$name,
				'tax_rate_priority' => 1,//$priority,
				'tax_rate_compound' => 0,//$compound ? 1 : 0,
				'tax_rate_shipping' => 1,//$shipping ? 1 : 0,

				'tax_rate_order'    => $loop ++,
				'tax_rate_class'    => '',
			);

			$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
			WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, '' );
			WC_Tax::_update_tax_rate_cities( $tax_rate_id, '' );
		}
	}

	/* Used by admin settings page.
	 *
	 * @param string $tax_class
	 *
	 * @return array|null|object
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	public static function get_rates_for_tax_class( $tax_class ) {
		global $wpdb;

		// Get all the rates and locations. Snagging all at once should significantly cut down on the number of queries.
		$rates     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rates` WHERE `tax_rate_class` = %s ORDER BY `tax_rate_order`;", sanitize_title( $tax_class ) ) );
		$locations = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rate_locations`" );

		// Set the rates keys equal to their ids.
		$rates = array_combine( wp_list_pluck( $rates, 'tax_rate_id' ), $rates );

		// Drop the locations into the rates array.
		foreach ( $locations as $location ) {
			// Don't set them for unexistent rates.
			if ( ! isset( $rates[ $location->tax_rate_id ] ) ) {
				continue;
			}
			// If the rate exists, initialize the array before appending to it.
			if ( ! isset( $rates[ $location->tax_rate_id ]->{$location->location_type} ) ) {
				$rates[ $location->tax_rate_id ]->{$location->location_type} = array();
			}
			$rates[ $location->tax_rate_id ]->{$location->location_type}[] = $location->location_code;
		}

		return $rates;
	}

	/**
	 * get_european_union_countries_with_vat_rate.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function get_european_union_countries_with_vat_rate() {
		return array(
			'AT' => 20,
			'BE' => 21,
			'BG' => 20,
			'CY' => 19,
			'CZ' => 21,
			'DE' => 19,
			'DK' => 25,
			'EE' => 20,
			'ES' => 21,
			'FI' => 24,
			'FR' => 20,
			'GB' => 20,
			'GR' => 23,
			'HU' => 27,
			'HR' => 25,
			'IE' => 23,
			'IT' => 22,
			'LT' => 21,
			'LU' => 17,
			'LV' => 21,
			'MT' => 18,
			'NL' => 21,
			'PL' => 23,
			'PT' => 23,
			'RO' => 24,
			'SE' => 25,
			'SI' => 22,
			'SK' => 20,
		);
	}

	/**
	 * create_tool.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	public function create_tool() {
		$the_tool_html = '';
		$the_tool_html .= $this->get_back_to_settings_link_html();
		$the_tool_html .= '<br>';
		$the_tool_html .= '<a href="' . add_query_arg( 'add_eu_countries_vat_rates', '1' ) . '">' . __( 'Add EU Countries VAT Rates', 'woocommerce-jetpack' ) . '</a>';
		$eu_vat_rates = $this->get_european_union_countries_with_vat_rate();
		$the_tool_html .= '<pre>' . print_r( count( $eu_vat_rates ), true ) . PHP_EOL . print_r( $eu_vat_rates, true ) . '</pre>';
		$standard_tax_rates = self::get_rates_for_tax_class( '' );
		$the_tool_html .= '<pre>' . print_r( count( $standard_tax_rates ), true ) . PHP_EOL . print_r( $standard_tax_rates, true ) . '</pre>';
		echo $the_tool_html;
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

		$settings = $this->add_tools_list( $settings );

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_EU_VAT_Number();

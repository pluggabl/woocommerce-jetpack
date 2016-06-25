<?php
/**
 * Abstract WooCommerce Jetpack Product Input Fields
 *
 * The WooCommerce Jetpack Product Input Fields abstract class.
 *
 * @version 2.5.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Input_Fields_Abstract' ) ) :

class WCJ_Product_Input_Fields_Abstract {

	/** @var string scope. */
	public $scope = '';

	/**
	 * Constructor.
	 */
	public function __construct() {

	}

	/**
	 * get_options.
	 *
	 * @version 2.5.2
	 */
	public function get_options() {
		$options = array(

			array(
				'id'                => 'wcj_product_input_fields_enabled_' . $this->scope . '_',
				'title'             => __( 'Enabled', 'woocommerce-jetpack' ),
				'type'              => 'checkbox',
				'default'           => 'no',
			),

			array(
				'id'                => 'wcj_product_input_fields_type_' . $this->scope . '_',
				'title'             => __( 'Type', 'woocommerce-jetpack' ),
				'type'              => 'select',
				'default'           => 'text',
				'options'           => array(
					'text'       => __( 'Text', 'woocommerce-jetpack' ),
					'textarea'   => __( 'Textarea', 'woocommerce-jetpack' ),
					'number'     => __( 'Number', 'woocommerce-jetpack' ),
					'checkbox'   => __( 'Checkbox', 'woocommerce-jetpack' ),
					'file'       => __( 'File', 'woocommerce-jetpack' ),
					'datepicker' => __( 'Datepicker', 'woocommerce-jetpack' ),
					'weekpicker' => __( 'Weekpicker', 'woocommerce-jetpack' ),
					'timepicker' => __( 'Timepicker', 'woocommerce-jetpack' ),
					'select'     => __( 'Select', 'woocommerce-jetpack' ),
					'radio'      => __( 'Radio', 'woocommerce-jetpack' ),
					'password'   => __( 'Password', 'woocommerce-jetpack' ),
					'country'    => __( 'Country', 'woocommerce-jetpack' ),
//					'state'      => __( 'State', 'woocommerce-jetpack' ),
					'email'      => __( 'Email', 'woocommerce-jetpack' ),
					'tel'        => __( 'Phone', 'woocommerce-jetpack' ),
				),
			),

			/* array(
				'id'                => 'wcj_product_input_fields_type_checkbox_' . $this->scope . '_',
				'title'             => __( 'If checkbox is selected, set possible pairs here.', 'woocommerce-jetpack' ),
				'type'              => 'select',
				'default'           => 'yes_no',
				'options'           => array(
					'yes_no' => __( 'Yes / No', 'woocommerce-jetpack' ),
					'on_off' => __( 'On / Off', 'woocommerce-jetpack' ),
				),
			), */

			array(
				'id'                => 'wcj_product_input_fields_type_checkbox_yes_' . $this->scope . '_',
				'title'             => __( 'If checkbox is selected, set value for ON here', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Checkbox: ON', 'woocommerce-jetpack' ),
				'type'              => 'text',
				'default'           => __( 'Yes', 'woocommerce-jetpack' ),
			),

			array(
				'id'                => 'wcj_product_input_fields_type_checkbox_no_' . $this->scope . '_',
				'title'             => __( 'If checkbox is selected, set value for OFF here', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Checkbox: OFF', 'woocommerce-jetpack' ),
				'type'              => 'text',
				'default'           => __( 'No', 'woocommerce-jetpack' ),
			),

			array(
				'id'                => 'wcj_product_input_fields_type_checkbox_default_' . $this->scope . '_',
				'title'             => __( 'If checkbox is selected, set default value here', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Checkbox: Default', 'woocommerce-jetpack' ),
				'type'              => 'select',
				'default'           => 'no',
				'options'           => array(
					'no'  => __( 'Not Checked', 'woocommerce-jetpack' ),
					'yes' => __( 'Checked', 'woocommerce-jetpack' ),
				),
			),

			// TODO http://www.w3schools.com/tags/att_input_accept.asp
			array(
				'id'                => 'wcj_product_input_fields_type_file_accept_' . $this->scope . '_',
				'title'             => __( 'If file is selected, set accepted file types here. E.g.: ".jpg,.jpeg,.png". Leave blank to accept all files', 'woocommerce-jetpack' ),
				'short_title'       => __( 'File: Accepted types', 'woocommerce-jetpack' ),
				'type'              => 'text',
				'default'           => __( '.jpg,.jpeg,.png', 'woocommerce-jetpack' ),
			),

			array(
				'id'                => 'wcj_product_input_fields_type_file_max_size_' . $this->scope . '_',
				'title'             => __( 'If file is selected, set max file size here. Set to zero to accept all files', 'woocommerce-jetpack' ),
				'short_title'       => __( 'File: Max size', 'woocommerce-jetpack' ),
				'type'              => 'number',
				'default'           => 0,
			),

			array(
				'id'                => 'wcj_product_input_fields_type_datepicker_format_' . $this->scope . '_',
				'title'             => __( 'If datepicker/weekpicker is selected, set date format here. Visit <a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">documentation on date and time formatting</a> for valid date formats.', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Leave blank to use your current WordPress format', 'woocommerce-jetpack' ) . ': ' . get_option( 'date_format' ),
				'short_title'       => __( 'Datepicker/Weekpicker: Date format', 'woocommerce-jetpack' ),
				'type'              => 'text',
				'default'           => '',
			),

			array(
				'id'                => 'wcj_product_input_fields_type_datepicker_mindate_' . $this->scope . '_',
				'title'             => __( 'If datepicker/weekpicker is selected, set min date (in days) here', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Datepicker/Weekpicker: Min date', 'woocommerce-jetpack' ),
				'type'              => 'number',
				'default'           => -365,
			),

			array(
				'id'                => 'wcj_product_input_fields_type_datepicker_maxdate_' . $this->scope . '_',
				'title'             => __( 'If datepicker/weekpicker is selected, set max date (in days) here', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Datepicker/Weekpicker: Max date', 'woocommerce-jetpack' ),
				'type'              => 'number',
				'default'           => 365,
			),

			array(
				'id'                => 'wcj_product_input_fields_type_datepicker_changeyear_' . $this->scope . '_',
				'title'             => __( 'If datepicker/weekpicker is selected, set if you want to add year selector', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Datepicker/Weekpicker: Change year', 'woocommerce-jetpack' ),
				'type'              => 'checkbox',
				'default'           => 'no',
			),

			array(
				'id'                => 'wcj_product_input_fields_type_datepicker_yearrange_' . $this->scope . '_',
				'title'             => __( 'If datepicker/weekpicker is selected, and year selector is enabled, set year range here', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Datepicker/Weekpicker: Year range', 'woocommerce-jetpack' ),
//				'desc_tip'          => __( 'The range of years displayed in the year drop-down: either relative to today\'s year ("-nn:+nn"), relative to the currently selected year ("c-nn:c+nn"), absolute ("nnnn:nnnn"), or combinations of these formats ("nnnn:-nn"). Note that this option only affects what appears in the drop-down, to restrict which dates may be selected use the minDate and/or maxDate options.', 'woocommerce-jetpack' ),
				'type'              => 'text',
				'default'           => 'c-10:c+10',
			),

			array(
				'id'                => 'wcj_product_input_fields_type_datepicker_firstday_' . $this->scope . '_',
				'title'             => __( 'If datepicker/weekpicker is selected, set first week day here', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Datepicker/Weekpicker: First week day', 'woocommerce-jetpack' ),
				'type'              => 'select',
				'default'           => 0,
				'options'           => array(
					__( 'Sunday', 'woocommerce-jetpack' ),
					__( 'Monday', 'woocommerce-jetpack' ),
					__( 'Tuesday', 'woocommerce-jetpack' ),
					__( 'Wednesday', 'woocommerce-jetpack' ),
					__( 'Thursday', 'woocommerce-jetpack' ),
					__( 'Friday', 'woocommerce-jetpack' ),
					__( 'Saturday', 'woocommerce-jetpack' ),
				),
			),

			array(
				'id'                => 'wcj_product_input_fields_type_timepicker_format_' . $this->scope . '_',
				'title'             => __( 'If timepicker is selected, set time format here. Visit <a href="http://timepicker.co/options/" target="_blank">timepicker options page</a> for valid time formats.', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Timepicker: Time format', 'woocommerce-jetpack' ),
				'type'              => 'text',
				'default'           => 'hh:mm p',
			),

			array(
				'id'                => 'wcj_product_input_fields_type_timepicker_interval_' . $this->scope . '_',
				'title'             => __( 'If timepicker is selected, set interval (in minutes) here', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Timepicker: Interval', 'woocommerce-jetpack' ),
				'type'              => 'number',
				'default'           => 15,
			),

			array(
				'id'                => 'wcj_product_input_fields_type_select_options_' . $this->scope . '_',
				'title'             => __( 'If select/radio is selected, set options here. One option per line', 'woocommerce-jetpack' ),
				'short_title'       => __( 'Select/Radio: Options', 'woocommerce-jetpack' ),
				'type'              => 'textarea',
				'default'           => '',
			),

			array(
				'id'                => 'wcj_product_input_fields_required_' . $this->scope . '_',
				'title'             => __( 'Required', 'woocommerce-jetpack' ),
				'type'              => 'checkbox',
				'default'           => 'no',
			),

			array(
				'id'                => 'wcj_product_input_fields_title_' . $this->scope . '_',
				'title'             => __( 'Title', 'woocommerce-jetpack' ),
				'type'              => 'textarea',
				'default'           => '',
			),

			array(
				'id'                => 'wcj_product_input_fields_placeholder_' . $this->scope . '_',
				'title'             => __( 'Placeholder', 'woocommerce-jetpack' ),
				'type'              => 'textarea',
				'default'           => '',
			),

			array(
				'id'                => 'wcj_product_input_fields_required_message_' . $this->scope . '_',
				'title'             => __( 'Message on required', 'woocommerce-jetpack' ),
				'type'              => 'textarea',
				'default'           => '',
			),

		);
		return $options;
	}

	/**
	 * add_files_to_email_attachments.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_files_to_email_attachments( $attachments, $status, $order ) {
		if (
			( 'new_order' === $status                 && 'yes' === get_option( 'wcj_product_input_fields_attach_to_admin_new_order',           'yes' ) ) ||
			( 'customer_processing_order' === $status && 'yes' === get_option( 'wcj_product_input_fields_attach_to_customer_processing_order', 'yes' ) )
		) {
			foreach ( $order->get_items() as $item_key => $item ) {
				$product_id = $item['product_id'];
				$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', $product_id, 1 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( isset( $item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ] ) ) {
						$the_value = $item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ];
						$the_value = maybe_unserialize( $the_value );
						if ( isset( $the_value['wcj_type'] ) && 'file' === $the_value['wcj_type'] && isset( $the_value['tmp_name'] ) ) {
							$file_path = $the_value['tmp_name'];
							$attachments[] = $file_path;
						}
					}
				}
			}
		}
		return $attachments;
	}

	/**
	 * hide_custom_input_fields_default_output_in_admin_order.
	 * @todo Get actual (max) number of fields in case of local scape.
	 */
	function hide_custom_input_fields_default_output_in_admin_order( $hidden_metas ) {
		$total_number = 0;
		if ( 'global' === $this->scope ) {
			$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', 0, 1 ) );
		} else {
			$max_number_of_fields_for_local = 100;
			$total_number = $max_number_of_fields_for_local; // TODO: not the best solution!
		}

		for ( $i = 1; $i <= $total_number; $i++ ) {
			$hidden_metas[] = '_' . 'wcj_product_input_fields_' . $this->scope . '_' . $i;
		}
		return $hidden_metas;
	}

	/**
	 * output_custom_input_fields_in_admin_order.
	 *
	 * @version 2.5.3
	 */
	function output_custom_input_fields_in_admin_order( $item_id, $item, $_product ) {
		if ( null === $_product ) {
			// Shipping
			return;
		}
		echo '<table cellspacing="0" class="display_meta">';
		$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', $_product->id, 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {

			$the_nice_name = $this->get_value( 'wcj_product_input_fields_title_' . $this->scope . '_' . $i, $_product->id, '' );
			if ( '' == $the_nice_name ) $the_nice_name = __( 'Product Input Field', 'woocommerce-jetpack' ) . ' (' . $this->scope . ') #' . $i;

			$the_value = isset( $item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ] ) ? $item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ] : '';

			$type = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $_product->id, '' );
			if ( 'file' === $type ) {
				/* $file_name = $the_value;
				$upload_dir = wp_upload_dir();
				$upload_url = $upload_dir['baseurl'];
				$the_value = $upload_url . '/woocommerce_uploads/' . $file_name;
				//$the_value = $upload_url . '/' . $the_value;
				//$the_value = '<img style="width:50px;" src="' . $the_value . '">'; */
				$the_value = maybe_unserialize( $the_value );
				if ( isset( $the_value['name'] ) ) {
					$the_value = '<a href="' . add_query_arg( 'wcj_download_file', $item_id . '.' . pathinfo( $the_value['name'], PATHINFO_EXTENSION ) ) . '">' . $the_value['name'] . '</a>';
				}
			} else {
				if ( 'no' === get_option( 'wcj_product_input_fields_make_nicer_name_enabled' ) ) {
					continue;
				}
			}

			if ( '' != $the_value ) {
				echo '<tr><th>' . $the_nice_name . ':</th><td>' . $the_value . '</td></tr>';
			}
		}
		echo '</table>';
	}

	/**
	 * starts_with.
	 *
	function starts_with( $haystack, $needle ) {
		// search backwards starting from haystack length characters from the end
		//return ( '' === $needle ) || ( strpos( $haystack, $needle, strlen( $haystack ) ) !== false );
		return $needle === substr( $haystack, 0, strlen( $needle ) );
		//return substr( $haystack, 0, strlen( $needle ) );
		//return strpos( $haystack, $needle ) !== false;
	}

	/**
	 * change_woocommerce_attribute_label.
	 *
	function change_woocommerce_attribute_label( $label, $name ) {

		if ( $this->starts_with( $label, '_wcj_product_input_fields_global_' ) ) {
			$title_option_id = trim( $label, '_' );
			$title_option_id = str_replace( 'wcj_product_input_fields_global_', 'wcj_product_input_fields_title_global_', $title_option_id );
			//$the_nice_name = $this->get_value( $label, 0, '' );
			$title = get_option( $title_option_id, '' );

			$label = ( '' == $title ) ?
				str_replace(
					'_wcj_product_input_fields_' . $this->scope . '_',
					__( 'Product Input Field', 'woocommerce-jetpack' ) . ' (' . $this->scope . ') #',
					$label ) :
				$title;

		} elseif ( $this->starts_with( $label, '_wcj_product_input_fields_local_' ) ) {

			$title = '';//$label;

			$label = ( '' == $title ) ?
				str_replace(
					'_wcj_product_input_fields_' . $this->scope . '_',
					__( 'Product Input Field', 'woocommerce-jetpack' ) . ' (' . $this->scope . ') #',
					$label ) :
				$title;
		}

		return $label;
	}

	/**
	 * finish_making_nicer_name_for_product_input_fields.
	 *
	public function finish_making_nicer_name_for_product_input_fields( $item_id, $item, $_product ) {
		$buffer = ob_get_clean();
		$the_ugly_name = '_wcj_product_input_fields_' . $this->scope . '_';
		$the_nice_name = $this->get_value( 'wcj_product_input_fields_title_' . $this->scope . '_' . '1', $_product->id, '' );
		if ( '' == $the_nice_name ) $the_nice_name = __( 'Product Input Field', 'woocommerce-jetpack' ) . ' (' . $this->scope . ') #';
		$buffer = str_replace(
			$the_ugly_name,
			$the_nice_name,
			$buffer
		);
		echo $buffer;
	}

	/**
	 * make_nicer_name.
	 *
	public function make_nicer_name( $buffer ) {
		$the_ugly_name = '_wcj_product_input_fields_' . $this->scope . '_';
		$the_nice_name = () ? : __( 'Product Input Field', 'woocommerce-jetpack' ) . ' (' . $this->scope . ') #';
		return str_replace(
			$the_ugly_name,
			$the_nice_name,
			$buffer
		);
	}

	/**
	 * start_making_nicer_name_for_product_input_fields.
	 *
	public function start_making_nicer_name_for_product_input_fields( $item_id, $item, $_product ) {
		ob_start( array( $this, 'make_nicer_name' ) );
	}

	/**
	 * finish_making_nicer_name_for_product_input_fields.
	 *
	public function finish_making_nicer_name_for_product_input_fields( $item_id, $item, $_product ) {
		ob_end_flush();
	}

	/**
	 * get_value.
	 */
	public function get_value( $option_name, $product_id, $default ) {
		return false;
	}

	/**
	 * validate_product_input_fields_on_add_to_cart.
	 *
	 * @version 2.5.2
	 */
	public function validate_product_input_fields_on_add_to_cart( $passed, $product_id ) {
		$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', $product_id, 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {

			$is_enabled  = $this->get_value( 'wcj_product_input_fields_enabled_' . $this->scope . '_' . $i, $product_id, 'no' );
			if ( ! $is_enabled ) {
				continue;
			}

			$type = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $product_id, '' );
			$field_name = 'wcj_product_input_fields_' . $this->scope . '_' . $i;

			if ( 'checkbox' === $type && ! isset( $_POST[ $field_name ] ) ) {
				$_POST[ $field_name ] = 'off';
			}

			$is_required = $this->get_value( 'wcj_product_input_fields_required_' . $this->scope . '_' . $i, $product_id, 'no' );
			if ( 'on' === $is_required  || 'yes' === $is_required ) {
				if ( 'file' === $type ) {
					$field_value = ( isset( $_FILES[ $field_name ]['name'] ) ) ? $_FILES[ $field_name ]['name'] : '';
				} else {
					$field_value = ( isset( $_POST[ $field_name ] ) ) ? $_POST[ $field_name ] : '';
					if ( 'checkbox' === $type && 'off' === $field_value ) {
						$field_value = '';
					}
				}
				if ( '' == $field_value ) {
					$passed = false;
					wc_add_notice( $this->get_value( 'wcj_product_input_fields_required_message_' . $this->scope . '_' . $i, $product_id, '' ), 'error' );
				}
			}

			if ( 'file' === $type && isset( $_FILES[ $field_name ] ) && '' != $_FILES[ $field_name ]['name'] ) {
				// Validate file type
				if ( '' != ( $file_accept = $this->get_value( 'wcj_product_input_fields_type_file_accept_' . $this->scope . '_' . $i, $product_id, '' ) ) ) {
					$file_accept = explode( ',', $file_accept );
					if ( is_array( $file_accept ) && ! empty( $file_accept ) ) {
						$file_type = '.' . pathinfo( $_FILES[ $field_name ]['name'], PATHINFO_EXTENSION );
						if ( ! in_array( $file_type, $file_accept ) ) {
							$passed = false;
							wc_add_notice( __( 'Wrong file type!', 'woocommerce-jetpack' ), 'error' );
//							wc_add_notice( $this->get_value( 'wcj_product_input_fields_wrong_file_type_msg_' . $this->scope . '_' . $i, $product_id, '' ), 'error' );
						}
					}
				}
				// Validate file max size
				if ( ( $max_file_size = $this->get_value( 'wcj_product_input_fields_type_file_max_size_' . $this->scope . '_' . $i, $product_id, '' ) ) > 0 ) {
					if ( $_FILES[ $field_name ]['size'] > $max_file_size ) {
						$passed = false;
						wc_add_notice( __( 'File is too big!', 'woocommerce-jetpack' ), 'error' );
					}
				}
			}
		}

		return $passed;
	}

	/**
	 * add_product_input_fields_to_frontend.
	 *
	 * @version 2.4.7
	 */
	public function add_product_input_fields_to_frontend() {
		global $product;
		//if ( ! $product ) // return;
		$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', $product->id, 1 ) );

		for ( $i = 1; $i <= $total_number; $i++ ) {

			$type        = $this->get_value( 'wcj_product_input_fields_type_' .        $this->scope . '_' . $i, $product->id, 'text' );
			$is_enabled  = $this->get_value( 'wcj_product_input_fields_enabled_' .     $this->scope . '_' . $i, $product->id, 'no' );
			$is_required = $this->get_value( 'wcj_product_input_fields_required_' .    $this->scope . '_' . $i, $product->id, 'no' );
			$title       = $this->get_value( 'wcj_product_input_fields_title_' .       $this->scope . '_' . $i, $product->id, '' );
			$placeholder = $this->get_value( 'wcj_product_input_fields_placeholder_' . $this->scope . '_' . $i, $product->id, '' );

			$datepicker_format = $this->get_value( 'wcj_product_input_fields_type_datepicker_format_'  . $this->scope . '_' . $i, $product->id, '' );
			if ( '' == $datepicker_format ) {
				$datepicker_format = get_option( 'date_format' );
			}
			$datepicker_format     = wcj_date_format_php_to_js_v2( $datepicker_format );
			$datepicker_mindate    = $this->get_value( 'wcj_product_input_fields_type_datepicker_mindate_' . $this->scope . '_' . $i, $product->id, -365 );
			$datepicker_maxdate    = $this->get_value( 'wcj_product_input_fields_type_datepicker_maxdate_' . $this->scope . '_' . $i, $product->id, 365 );
			$datepicker_firstday   = $this->get_value( 'wcj_product_input_fields_type_datepicker_firstday_' . $this->scope . '_' . $i, $product->id, 0 );
			$datepicker_changeyear = $this->get_value( 'wcj_product_input_fields_type_datepicker_changeyear_' . $this->scope . '_' . $i, $product->id, 'no' );
			$datepicker_yearrange  = $this->get_value( 'wcj_product_input_fields_type_datepicker_yearrange_' . $this->scope . '_' . $i, $product->id, 'c-10:c+10' );
			if ( 'on' === $datepicker_changeyear || 'yes' === $datepicker_changeyear ) {
				$datepicker_year = 'changeyear="1" yearRange="' . $datepicker_yearrange . '" ';
			} else {
				$datepicker_year = '';
			}

			$timepicker_format = $this->get_value( 'wcj_product_input_fields_type_timepicker_format_' . $this->scope . '_' . $i, $product->id, 'hh:mm p' );
			$timepicker_interval = $this->get_value( 'wcj_product_input_fields_type_timepicker_interval_' . $this->scope . '_' . $i, $product->id, 15 );

			$file_accept = $this->get_value( 'wcj_product_input_fields_type_file_accept_' . $this->scope . '_' . $i, $product->id, '' );
			$custom_attributes = ( 'file' === $type ) ? ' accept="' . $file_accept . '"' : '';
			$field_name = 'wcj_product_input_fields_' . $this->scope . '_' . $i;

			if ( 'on' === $is_required || 'yes' === $is_required ) {
				$title .= get_option( 'wcj_product_input_fields_frontend_view_required_html', '&nbsp;<abbr class="required" title="required">*</abbr>' );
			}

			if ( 'on' === $is_enabled || 'yes' === $is_enabled ) {
				switch ( $type ) {

					case 'number':
					case 'text':
					case 'file':
					case 'password':
					case 'email':
					case 'tel':

						echo '<p>' . $title . '<input type="' . $type . '" name="' . $field_name . '" placeholder="' . $placeholder . '"' . $custom_attributes . '>' . '</p>';
						break;

					case 'checkbox':

						$checked = checked(
							$this->get_value( 'wcj_product_input_fields_type_checkbox_default_' . $this->scope . '_' . $i, $product->id, 'no' ),
							'yes',
							false
						);
						echo '<p>' . $title . '<input type="' . $type . '" name="' . $field_name . '"' . $custom_attributes . $checked . '>' . '</p>';
						break;

					case 'datepicker':

						echo '<p>' . $title . '<input ' . $datepicker_year . 'firstday="' . $datepicker_firstday . '" dateformat="' . $datepicker_format . '" mindate="' . $datepicker_mindate . '" maxdate="' . $datepicker_maxdate . '" type="' . $type . '" display="date" name="' . $field_name . '" placeholder="' . $placeholder . '"' . $custom_attributes . '>' . '</p>';
						break;

					case 'weekpicker':

						echo '<p>' . $title . '<input ' . $datepicker_year . 'firstday="' . $datepicker_firstday . '" dateformat="' . $datepicker_format . '" mindate="' . $datepicker_mindate . '" maxdate="' . $datepicker_maxdate . '" type="' . $type . '" display="week" name="' . $field_name . '" placeholder="' . $placeholder . '"' . $custom_attributes . '>' . '</p>';
						break;

					case 'timepicker':

						echo '<p>' . $title . '<input interval="' . $timepicker_interval . '" timeformat="' . $timepicker_format . '" type="' . $type . '" display="time" name="' . $field_name . '" placeholder="' . $placeholder . '"' . $custom_attributes . '>' . '</p>';
						break;

					case 'textarea':

						echo '<p>' . $title . '<textarea name="' . $field_name . '" placeholder="' . $placeholder . '">' . '</textarea>' . '</p>';
						break;

					case 'select':

						$select_options_raw = $this->get_value( 'wcj_product_input_fields_type_select_options_' . $this->scope . '_' . $i, $product->id, '' );
						$select_options = wcj_get_select_options( $select_options_raw );
						if ( '' != $placeholder ) {
							$select_options = array_merge( array( '' => $placeholder ), $select_options );
						}
						$select_options_html = '';
						if ( ! empty( $select_options ) ) {
							reset( $select_options );
							$value = key( $select_options );
							foreach ( $select_options as $select_option_key => $select_option_title ) {
								$select_options_html .= '<option value="' . $select_option_key . '" ' . selected( $value, $select_option_key, false ) . '>';
								$select_options_html .= $select_option_title;
								$select_options_html .= '</option>';
							}
						}
						echo '<p>' . $title . '<select name="' . $field_name . '">' . $select_options_html . '</select>' . '</p>';
						break;

					case 'radio':

						$select_options_raw = $this->get_value( 'wcj_product_input_fields_type_select_options_' . $this->scope . '_' . $i, $product->id, '' );
						$select_options = wcj_get_select_options( $select_options_raw );
						$select_options_html = '';
						//$label_id = current( array_keys( $args['options'] ) );
						if ( ! empty( $select_options ) ) {
							reset( $select_options );
							$value = key( $select_options );
							foreach ( $select_options as $option_key => $option_text ) {
								$select_options_html .= '<input type="radio" class="input-radio" value="' . esc_attr( $option_key ) .
									'" name="' . $field_name . '" id="' . $field_name . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
								$select_options_html .= '<label for="' . $field_name . '_' . esc_attr( $option_key ) .
									'" class="radio">' . $option_text . '</label><br>';
							}
							echo '<p>' . $title . $select_options_html . '</p>';
						}
						break;

					case 'country':

						$countries = WC()->countries->get_allowed_countries();
						if ( sizeof( $countries ) > 1 ) {
							$value = key( $countries );
							$field = '<select name="' . $field_name . '" id="' . $field_name . '" class="country_to_state country_select">' .
								'<option value="">'.__( 'Select a country&hellip;', 'woocommerce' ) .'</option>';
							foreach ( $countries as $ckey => $cvalue ) {
								$field .= '<option value="' . esc_attr( $ckey ) . '" '.selected( $value, $ckey, false ) .'>'.__( $cvalue, 'woocommerce' ) .'</option>';
							}
							$field .= '</select>';
							echo '<p>' . $title . $field . '</p>';
						}
						break;

					/* case 'state' : // from woocommerce_form_field()

						// Get Country
						$country_key = $key == 'billing_state'? 'billing_country' : 'shipping_country';
						$current_cc  = WC()->checkout->get_value( $country_key );
						$states      = WC()->countries->get_states( $current_cc );

						if ( is_array( $states ) && empty( $states ) ) {

							$field_container = '<p class="form-row %1$s" id="%2$s" style="display: none">%3$s</p>';

							$field .= '<input type="hidden" class="hidden" name="' . esc_attr( $key )  . '" id="' . esc_attr( $args['id'] ) . '" value="" ' . implode( ' ', $custom_attributes ) . ' placeholder="' . esc_attr( $args['placeholder'] ) . '" />';

						} elseif ( is_array( $states ) ) {

							$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="state_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) .'" ' . implode( ' ', $custom_attributes ) . ' placeholder="' . esc_attr( $args['placeholder'] ) . '">
								<option value="">'.__( 'Select a state&hellip;', 'woocommerce' ) .'</option>';

							foreach ( $states as $ckey => $cvalue ) {
								$field .= '<option value="' . esc_attr( $ckey ) . '" '.selected( $value, $ckey, false ) .'>'.__( $cvalue, 'woocommerce' ) .'</option>';
							}

							$field .= '</select>';

						} else {

							$field .= '<input type="text" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) .'" value="' . esc_attr( $value ) . '"  placeholder="' . esc_attr( $args['placeholder'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

						}

						break; */
				}
			}
		}
	}

	/**
	 * add_product_input_fields_to_cart_item_data - from $_POST to $cart_item_data
	 */
	public function add_product_input_fields_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', $product_id, 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$type = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $product_id, '' );
			$value_name = 'wcj_product_input_fields_' . $this->scope . '_' . $i;
			if ( 'file' === $type ) {
				if ( isset( $_FILES[ $value_name ] ) ) {
					$cart_item_data[ $value_name ] = $_FILES[ $value_name ];
					$tmp_dest_file = tempnam( sys_get_temp_dir(), 'wcj' );
					move_uploaded_file( $cart_item_data[ $value_name ]['tmp_name'], $tmp_dest_file );
					$cart_item_data[ $value_name ]['tmp_name'] = $tmp_dest_file;
				}
			} else {
				if ( isset( $_POST[ $value_name ] ) ) {
					$cart_item_data[ $value_name ] = $_POST[ $value_name ];
				}
			}
		}
		return $cart_item_data;
	}

	/**
	 * get_cart_item_product_input_fields_from_session.
	 */
	public function get_cart_item_product_input_fields_from_session( $item, $values, $key ) {
		$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', $item['product_id'], 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( array_key_exists( 'wcj_product_input_fields_' . $this->scope . '_' . $i, $values ) )
				$item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ] = $values[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ];
		}
		return $item;
	}

	/**
	 * Adds product input values to order details (and emails).
	 *
	 * @version 2.4.7
	 */
	public function add_product_input_fields_to_order_item_name( $name, $item, $is_cart = false ) {

		$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', $item['product_id'], 1 ) );
		if ( $total_number < 1 ) {
			return $name;
		}

		if ( $is_cart ) {
			$name .= '<dl style="font-size:smaller;">';
		}
		for ( $i = 1; $i <= $total_number; $i++ ) {

			$is_enabled  = $this->get_value( 'wcj_product_input_fields_enabled_' . $this->scope . '_' . $i, $item['product_id'], 'no' );
			if ( ! $is_enabled ) {
				continue;
			}

			$type = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $item['product_id'], '' );

			if ( 'checkbox' === $type && ! array_key_exists( 'wcj_product_input_fields_' . $this->scope . '_' . $i, $item ) ) {
				$item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ] = 'off';
			}

			if ( array_key_exists( 'wcj_product_input_fields_' . $this->scope . '_' . $i, $item ) ) {
				$title = $this->get_value( 'wcj_product_input_fields_title_' . $this->scope . '_' . $i, $item['product_id'], '' );

				$value = $item[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ];

				$yes_value = $this->get_value( 'wcj_product_input_fields_type_checkbox_yes_' . $this->scope . '_' . $i, $item['product_id'], '' );
				$no_value  = $this->get_value( 'wcj_product_input_fields_type_checkbox_no_'  . $this->scope . '_' . $i, $item['product_id'], '' );
//				$type      = $this->get_value( 'wcj_product_input_fields_type_'              . $this->scope . '_' . $i, $item['product_id'], '' );
				if ( 'checkbox' === $type ) {
					$value = ( 'on' === $value ) ? $yes_value : $no_value;
				}

				if ( 'file' === $type ) {
					$value = maybe_unserialize( $value );
					$value = ( isset( $value['name'] ) ) ? $value['name'] : '';
				}

				if ( '' != $value ) {
					if ( $is_cart ) {
						$name .= '<dt>'
							  . $title
							  . '</dt>'
							  . '<dd>'
							  . $value
							  . '</dd>';
					} else {
						$name .= str_replace(
							array( '%title%', '%value%' ),
							array( $title, $value ),
							get_option( 'wcj_product_input_fields_frontend_view_order_table_format', '&nbsp;| %title% %value%' )
						);
					}
				}
			}
		}
		if ( $is_cart ) {
			$name .= '</dl>';
		}

		return $name;
	}

	/**
	 * Adds product input values to cart item details.
	 *
	 * @version 2.4.0
	 */
	public function add_product_input_fields_to_cart_item_name( $name, $cart_item, $cart_item_key  ) {
		return $this->add_product_input_fields_to_order_item_name( $name, $cart_item, true );
	}

	/**
	 * add_product_input_fields_to_order_item_meta.
	 *
	 * @version 2.5.0
	 */
	public function add_product_input_fields_to_order_item_meta(  $item_id, $values, $cart_item_key  ) {
		$total_number = apply_filters( 'wcj_get_option_filter', 1, $this->get_value( 'wcj_' . 'product_input_fields' . '_' . $this->scope . '_total_number', $values['product_id'], 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( array_key_exists( 'wcj_product_input_fields_' . $this->scope . '_' . $i , $values ) ) {
				$type = $this->get_value( 'wcj_product_input_fields_type_' . $this->scope . '_' . $i, $values['product_id'], '' );
				$input_field_value = $values[ 'wcj_product_input_fields_' . $this->scope . '_' . $i ];

				if ( 'file' === $type ) {
					$tmp_name = $input_field_value['tmp_name'];
					$ext = pathinfo( $input_field_value['name'], PATHINFO_EXTENSION );
					$name = $item_id . '.' . $ext;//$input_field_value['name'];
					$upload_dir = wcj_get_wcj_uploads_dir( 'input_fields_uploads' );
					if ( ! file_exists( $upload_dir ) ) {
						mkdir( $upload_dir, 0755, true );
					}
					//$upload_dir = ( wp_mkdir_p( $upload_dir['path'] ) ) ? $upload_dir['path'] : $upload_dir['basedir'];
					$upload_dir_and_name = $upload_dir . '/' . $name;
					//move_uploaded_file( $tmp_name, $upload_dir_and_name );
					$file_data = file_get_contents( $tmp_name );
					file_put_contents( $upload_dir_and_name, $file_data );
					unlink( $tmp_name );
					//unset( $input_field_value['tmp_name'] );
					$input_field_value['tmp_name'] = addslashes( $upload_dir_and_name );
					$input_field_value['wcj_type'] = 'file';
					//$orig_file_name = $input_field_value['name'];
					//wc_add_order_item_meta( $item_id, '_wcj_product_input_fields_' . $this->scope . '_' . $i . '_orig_file_name', $orig_file_name );
					//$input_field_value = '<a href="' . add_query_arg( 'wcj_download_file', $name ) . '">' . $orig_file_name . '</a>';
					//$input_field_value = $orig_file_name;
				}

				wc_add_order_item_meta( $item_id, '_wcj_product_input_fields_' . $this->scope . '_' . $i, $input_field_value );
			}
		}
	}
}

endif;

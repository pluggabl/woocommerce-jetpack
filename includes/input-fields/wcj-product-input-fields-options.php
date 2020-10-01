<?php
/**
 * Booster for WooCommerce - Product Input Fields - Options
 *
 * @version 3.4.0
 * @since   3.1.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return array(
	array(
		'id'                => 'wcj_product_input_fields_enabled_' . $this->scope . '_',
		'title'             => __( 'Enabled', 'woocommerce-jetpack' ),
		'type'              => 'checkbox',
		'default'           => 'no',
	),
	array(
		'id'                => 'wcj_product_input_fields_order_' . $this->scope . '_',
		'short_title'       => __( 'Order', 'woocommerce-jetpack' ),
		'title'             => __( 'Set to zero for default order.', 'woocommerce-jetpack' ),
		'type'              => 'number',
		'default'           => 0,
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
//			'state'      => __( 'State', 'woocommerce-jetpack' ),
			'email'      => __( 'Email', 'woocommerce-jetpack' ),
			'tel'        => __( 'Phone', 'woocommerce-jetpack' ),
		),
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
		'id'                => 'wcj_product_input_fields_required_' . $this->scope . '_',
		'title'             => __( 'Required', 'woocommerce-jetpack' ),
		'type'              => 'checkbox',
		'default'           => 'no',
	),
	array(
		'id'                => 'wcj_product_input_fields_required_message_' . $this->scope . '_',
		'title'             => __( 'Message on required', 'woocommerce-jetpack' ),
		'type'              => 'textarea',
		'default'           => '',
	),
	array(
		'id'                => 'wcj_product_input_fields_class_' . $this->scope . '_',
		'title'             => __( 'HTML Class', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'default'           => '',
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
	array(
		'id'                => 'wcj_product_input_fields_type_file_accept_' . $this->scope . '_',
		'title'             => __( 'If file is selected, set accepted file types here. E.g.: ".jpg,.jpeg,.png". Leave blank to accept all files', 'woocommerce-jetpack' )
			. '. ' . __( 'Visit <a href="https://www.w3schools.com/tags/att_input_accept.asp" target="_blank">documentation on input accept attribute</a> for valid option formats', 'woocommerce-jetpack' ),
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
		'title'             => __( 'If datepicker/weekpicker is selected, set date format here. Visit <a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">documentation on date and time formatting</a> for valid date formats', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Leave blank to use your current WordPress format', 'woocommerce-jetpack' ) . ': ' . wcj_get_option( 'date_format' ),
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
//		'desc_tip'          => __( 'The range of years displayed in the year drop-down: either relative to today\'s year ("-nn:+nn"), relative to the currently selected year ("c-nn:c+nn"), absolute ("nnnn:nnnn"), or combinations of these formats ("nnnn:-nn"). Note that this option only affects what appears in the drop-down, to restrict which dates may be selected use the minDate and/or maxDate options.', 'woocommerce-jetpack' ),
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
		'title'             => __( 'If timepicker is selected, set time format here. Visit <a href="http://timepicker.co/options/" target="_blank">timepicker options page</a> for valid time formats', 'woocommerce-jetpack' ),
		'short_title'       => __( 'Timepicker: Time format', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'default'           => 'hh:mm p',
	),
	array(
		'id'                => 'wcj_product_input_fields_type_timepicker_mintime_' . $this->scope . '_',
		'title'             => __( 'If timepicker is selected, set min time here. Visit <a href="http://timepicker.co/options/" target="_blank">timepicker options page</a> for valid option formats', 'woocommerce-jetpack' ),
		'short_title'       => __( 'Timepicker: Min Time', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'default'           => '',
	),
	array(
		'id'                => 'wcj_product_input_fields_type_timepicker_maxtime_' . $this->scope . '_',
		'title'             => __( 'If timepicker is selected, set max time here. Visit <a href="http://timepicker.co/options/" target="_blank">timepicker options page</a> for valid option formats', 'woocommerce-jetpack' ),
		'short_title'       => __( 'Timepicker: Max Time', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'default'           => '',
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
		'css'               => 'height:200px;',
	),
);

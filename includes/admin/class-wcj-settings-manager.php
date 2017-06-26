<?php
/**
 * Booster for WooCommerce - Settings Manager
 *
 * @version 2.9.0
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Settings_Manager' ) ) :

class WCJ_Settings_Manager {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 * @version 2.9.0
	 */
	function __construct() {
		// Import / Export / Reset Booster's settings
		add_action( 'wp_loaded', array( $this, 'manage_options' ), PHP_INT_MAX );
	}

	/**
	 * manage_options.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function manage_options() {
		if ( is_admin() ) {
			if ( isset( $_POST['booster_import_settings'] ) ) {
				$this->manage_options_import();
			}
			if ( isset( $_POST['booster_export_settings'] ) ) {
				$this->manage_options_export();
			}
			if ( isset( $_POST['booster_reset_settings'] ) ) {
				$this->manage_options_reset();
			}
		}
	}

	/**
	 * manage_options_import.
	 *
	 * @version 2.5.4
	 * @since   2.5.2
	 */
	function manage_options_import() {
		global $wcj_notice;
		if( ! isset( $_FILES['booster_import_settings_file']['tmp_name'] ) || '' == $_FILES['booster_import_settings_file']['tmp_name'] ) {
			$wcj_notice .= __( 'Please upload a file to import!', 'woocommerce-jetpack' );
			$import_settings = array();
			unset( $_POST['booster_import_settings'] );
		} else {
			$import_counter = 0;
			$import_settings = file_get_contents( $_FILES['booster_import_settings_file']['tmp_name'] );
			$import_settings = explode( PHP_EOL, preg_replace( '~(*BSR_ANYCRLF)\R~', PHP_EOL, $import_settings ) );
			if ( ! is_array( $import_settings ) || 2 !== count( $import_settings ) ) {
				$wcj_notice .= __( 'Wrong file format!', 'woocommerce-jetpack' );
			} else {
				$import_header = $import_settings[0];
				$required_header = 'Booster for WooCommerce';
				if ( $required_header !== substr( $import_header, 0, strlen( $required_header ) ) ) {
					$wcj_notice .= __( 'Wrong file format!', 'woocommerce-jetpack' );
				} else {
					$import_settings = json_decode( $import_settings[1], true );
					foreach ( $import_settings as $import_key => $import_setting ) {
						update_option( $import_key, $import_setting );
						$import_counter++;
					}
					$wcj_notice .= sprintf( __( '%d options successfully imported.', 'woocommerce-jetpack' ), $import_counter );
				}
			}
		}
	}

	/**
	 * manage_options_export.
	 *
	 * @version 2.9.0
	 * @since   2.5.2
	 */
	function manage_options_export() {
		$export_settings = array();
		$export_counter = array();
		foreach ( WCJ()->modules as $module ) {
			$values = $module->get_settings();
			foreach ( $values as $value ) {
				if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
					if ( isset ( $_POST['booster_export_settings'] ) ) {
						$export_settings[ $value['id'] ] = get_option( $value['id'], $value['default'] );
						if ( ! isset( $export_counter[ $module->short_desc ] ) ) {
							$export_counter[ $module->short_desc ] = 0;
						}
						$export_counter[ $module->short_desc ]++;
					}
				}
			}
		}
		$export_settings = json_encode( $export_settings );
		$export_settings = 'Booster for WooCommerce v' . get_option( 'booster_for_woocommerce_version', 'NA' ) . PHP_EOL . $export_settings;
		header( "Content-Type: application/octet-stream" );
		header( "Content-Disposition: attachment; filename=booster_settings.txt" );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Type: application/download" );
		header( "Content-Description: File Transfer" );
		header( "Content-Length: " . strlen( $export_settings ) );
		echo $export_settings;
		die();
	}

	/**
	 * manage_options_reset.
	 *
	 * @version 2.9.0
	 * @since   2.5.2
	 */
	function manage_options_reset() {
		global $wcj_notice;
		$delete_counter = 0;
		foreach ( WCJ()->modules as $module ) {
			$values = $module->get_settings();
			foreach ( $values as $value ) {
				if ( isset( $value['id'] ) ) {
					if ( isset ( $_POST['booster_reset_settings'] ) ) {
						require_once( ABSPATH . 'wp-includes/pluggable.php' );
						if ( wcj_is_user_role( 'administrator' ) ) {
							delete_option( $value['id'] );
							$delete_counter++;
						}
					}
				}
			}
		}
		if ( $delete_counter > 0 ) {
			$wcj_notice .= sprintf( __( '%d options successfully deleted.', 'woocommerce-jetpack' ), $delete_counter );
		}
	}

}

endif;

return new WCJ_Settings_Manager();

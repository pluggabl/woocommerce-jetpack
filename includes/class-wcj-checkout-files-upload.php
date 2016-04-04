<?php
/**
 * WooCommerce Jetpack Checkout Files Upload
 *
 * The WooCommerce Jetpack Checkout Files Upload class.
 *
 * @version 2.4.5
 * @since   2.4.5
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Checkout_Files_Upload' ) ) :

class WCJ_Checkout_Files_Upload extends WCJ_Module {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id         = 'checkout_files_upload';
		$this->short_desc = __( 'Checkout Files Upload', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let customers upload files on WooCommerce checkout.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_file_admin_order_meta_box' ) );
			add_action( 'init', array( $this, 'process_checkout_files_upload' ) );
			add_action(
				get_option( 'wcj_checkout_files_upload_hook', 'woocommerce_before_checkout_form' ),
				array( $this, 'add_files_upload_form_to_checkout_frontend' )
			);
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_files_to_order' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * add_file_admin_order_meta_box.
	 */
	function add_file_admin_order_meta_box() {
		$screen   = 'shop_order';
		$context  = 'side';
		$priority = 'high';
		add_meta_box(
			'wc-jetpack-' . $this->id,
			__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Uploaded Files', 'woocommerce-jetpack' ),
			array( $this, 'create_file_admin_order_meta_box' ),
			$screen,
			$context,
			$priority
		);
	}

	/**
	 * create_file_admin_order_meta_box.
	 */
	function create_file_admin_order_meta_box() {
		$order_id = get_the_ID();
		$html = '';
		$total_files = get_post_meta( $order_id, '_' . 'wcj_checkout_files_total_files', true );
		for ( $i = 1; $i <= $total_files; $i++ ) {
			$order_file_name = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_'           . $i, true );
			$real_file_name  = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i, true );
			if ( '' != $order_file_name ) {
				$html .= '<p><a href="' . add_query_arg(
					array(
						'wcj_download_checkout_file_admin' => $order_file_name,
						'wcj_checkout_file_number'         => $i,
					) ) . '">' . $real_file_name . '</a></p>';
			}
		}
		echo $html;
	}

	/**
	 * add_files_to_order.
	 */
	function add_files_to_order( $order_id, $posted ) {
		$upload_dir = wcj_get_wcj_uploads_dir( 'checkout_files_upload' );
		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir, 0755, true );
		}
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( isset( $_SESSION[ 'wcj_checkout_files_upload_' . $i ] ) ) {
				$file_name          = $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['name'];
				$ext                = pathinfo( $file_name, PATHINFO_EXTENSION );
				$download_file_name = $order_id . '_' . $i . '.' . $ext;
				$file_path          = $upload_dir . '/' . $download_file_name;
				$tmp_file_name      = $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['tmp_name'];
				$file_data          = file_get_contents( $tmp_file_name );
				file_put_contents( $file_path, $file_data );
				unlink( $tmp_file_name );
				unset( $_SESSION[ 'wcj_checkout_files_upload_' . $i ] );
				update_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_' . $i, $download_file_name );
				update_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i, $file_name );
			}
		}
		update_post_meta( $order_id, '_' . 'wcj_checkout_files_total_files', $total_number );
	}

	/**
	 * process_checkout_files_upload.
	 */
	function process_checkout_files_upload() {
		if ( ! session_id() ) {
			session_start();
		}
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( isset( $_POST[ 'wcj_remove_checkout_file_' . $i ] ) ) {
				$file_name = 'wcj_checkout_files_upload_' . $i;
				unlink( $_SESSION[ $file_name ]['tmp_name'] );
				wc_add_notice( sprintf( __( 'File "%s" was successfully removed', 'woocommerce-jetpack' ), $_SESSION[ $file_name ]['name'] ) );
				unset( $_SESSION[ $file_name ] );
			}
		}
		if ( isset( $_GET['wcj_download_checkout_file_admin'] ) ) {
			$tmp_file_name = wcj_get_wcj_uploads_dir( 'checkout_files_upload' ) . '/' . $_GET['wcj_download_checkout_file_admin'];
			$file_name     = get_post_meta( $_GET['post'], '_' . 'wcj_checkout_files_upload_real_name_' . $_GET['wcj_checkout_file_number'], true );
			if ( is_super_admin() || is_shop_manager() ) {
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Cache-Control: private", false );
				header( 'Content-disposition: attachment; filename=' . $file_name );
				header( "Content-Transfer-Encoding: binary" );
				header( "Content-Length: ". filesize( $tmp_file_name ) );
				readfile( $tmp_file_name );
				exit();
			}
		}
		if ( isset( $_GET['wcj_download_checkout_file'] ) ) {
			$tmp_file_name = $_SESSION[ $_GET['wcj_download_checkout_file'] ]['tmp_name'];
			$file_name     = $_SESSION[ $_GET['wcj_download_checkout_file'] ]['name'];
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: private", false );
			header( 'Content-disposition: attachment; filename=' . $file_name );
			header( "Content-Transfer-Encoding: binary" );
			header( "Content-Length: ". filesize( $tmp_file_name ) );
			readfile( $tmp_file_name );
			exit();
		}
		if ( isset( $_POST['wcj_checkout_files_upload_submit'] ) ) {
			$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( isset( $_FILES[ 'wcj_checkout_files_upload_' . $i ] ) && '' != $_FILES[ 'wcj_checkout_files_upload_' . $i ]['tmp_name'] ) {
					$_SESSION[ 'wcj_checkout_files_upload_' . $i ] = $_FILES[ 'wcj_checkout_files_upload_' . $i ];
					$tmp_dest_file = tempnam( sys_get_temp_dir(), 'wcj' );
					move_uploaded_file( $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['tmp_name'], $tmp_dest_file );
					$_SESSION[ 'wcj_checkout_files_upload_' . $i ]['tmp_name'] = $tmp_dest_file;
					wc_add_notice( sprintf( __( 'File "%s" was successfully uploaded', 'woocommerce-jetpack' ),
						$_SESSION[ 'wcj_checkout_files_upload_' . $i ]['name'] ) );
				}
			}
		}
	}

	/**
	 * add_files_upload_form_to_checkout_frontend.
	 */
	function add_files_upload_form_to_checkout_frontend() {
		$html = '<form enctype="multipart/form-data" action="" method="POST">';
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		$html .= '<table>';
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( ! isset( $_SESSION[ 'wcj_checkout_files_upload_' . $i ] ) ) {
				$html .= '<tr>';
				$html .= '<td colspan="2">';
				$html .= '<input type="file" name="wcj_checkout_files_upload_' . $i . '" id="wcj_checkout_files_upload_' . $i .
					'" accept="' . get_option( 'wcj_checkout_files_upload_file_accept_' . $i ) . '">';
				$html .= '</td>';
				$html .= '</tr>';
			} else {
				$html .= '<tr>';
				$html .= '<td>';
				$html .= '<a href="' . add_query_arg( 'wcj_download_checkout_file', 'wcj_checkout_files_upload_' . $i ) . '">' .
					$_SESSION[ 'wcj_checkout_files_upload_' . $i ]['name'] . '</a>';
				$html .= '</td>';
				$html .= '<td>';
				$html .= '<input type="submit"' .
					' class="button alt"' .
					' name="wcj_remove_checkout_file_' . $i . '"' .
					' id="wcj_remove_checkout_file_' . $i . '"' .
					' value="' . __( 'Remove', 'woocommerce-jetpack' ) . '"' .
					' data-value="' . __( 'Remove', 'woocommerce-jetpack' ) . '">';
				$html .= '</td>';
				$html .= '</tr>';
			}
		}
		$html .= '<tr>';
		$html .= '<td colspan="2">';
		$html .= '<input type="submit"' .
			' class="button alt"' .
			' name="wcj_checkout_files_upload_submit"' .
			' id="wcj_checkout_files_upload_submit"' .
			' value="' . __( 'Upload', 'woocommerce-jetpack' ) . '"' .
			' data-value="' . __( 'Upload', 'woocommerce-jetpack' ) . '"></p>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</form>';
		echo $html;
	}

	/**
	 * get_settings.
	 *
	 * @todo required; label; (maybe) position and position priority (i.e. order);
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_checkout_files_upload_options',
			),
			array(
				'title'    => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_hook',
				'default'  => 'woocommerce_before_checkout_form',
				'type'     => 'select',
				'options'  => array(
					'woocommerce_before_checkout_form' => __( 'Before checkout form', 'woocommerce-jetpack' ),
					'woocommerce_after_checkout_form'  => __( 'After checkout form', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Total Files', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ?
						apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '1', )
				),
			),
		);
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'             => __( 'File', 'woocommerce-jetpack' ) . ' #' . $i,
					'id'                => 'wcj_checkout_files_upload_enabled_' . $i,
					'desc'              => __( 'Enabled', 'woocommerce-jetpack' ),
					'type'              => 'checkbox',
					'default'           => 'yes',
				),
				array(
					'desc'              => __( 'Accepted file types', 'woocommerce-jetpack' ),
					'desc_tip'          => __( 'Accepted file types. E.g.: ".jpg,.jpeg,.png". Leave blank to accept all files', 'woocommerce-jetpack' ),
					'id'                => 'wcj_checkout_files_upload_file_accept_' . $i,
					'default'           => '',
					'type'              => 'text',
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_checkout_files_upload_options',
			),
		) );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Checkout_Files_Upload();

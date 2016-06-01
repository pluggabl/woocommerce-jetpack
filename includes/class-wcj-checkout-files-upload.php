<?php
/**
 * WooCommerce Jetpack Checkout Files Upload
 *
 * The WooCommerce Jetpack Checkout Files Upload class.
 *
 * @version 2.5.2
 * @since   2.4.5
 * @author  Algoritmika Ltd.
 * @todo    styling options;
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Checkout_Files_Upload' ) ) :

class WCJ_Checkout_Files_Upload extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 * @since   2.4.5
	 */
	function __construct() {

		$this->id         = 'checkout_files_upload';
		$this->short_desc = __( 'Checkout Files Upload', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let customers upload files on (or after) WooCommerce checkout.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-checkout-files-upload/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_file_admin_order_meta_box' ) );
			add_action( 'init', array( $this, 'process_checkout_files_upload' ) );
			$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'disable' != ( $the_hook = get_option( 'wcj_checkout_files_upload_hook_' . $i, 'woocommerce_before_checkout_form' ) ) ) {
					add_action( $the_hook, array( $this, 'add_files_upload_form_to_checkout_frontend' ), get_option( 'wcj_checkout_files_upload_hook_priority_' . $i, 10 ) );
				}
				if ( 'yes' === get_option( 'wcj_checkout_files_upload_add_to_thankyou_' . $i, 'no' ) ) {
					add_action( 'woocommerce_thankyou',   array( $this, 'add_files_upload_form_to_thankyou_and_myaccount_page' ), PHP_INT_MAX, 1 );
				}
				if ( 'yes' === get_option( 'wcj_checkout_files_upload_add_to_myaccount_' . $i, 'no' ) ) {
					add_action( 'woocommerce_view_order', array( $this, 'add_files_upload_form_to_thankyou_and_myaccount_page' ), PHP_INT_MAX, 1 );
				}
			}
			add_action( 'woocommerce_checkout_order_processed',        array( $this, 'add_files_to_order' ), PHP_INT_MAX, 2 );
			add_action( 'woocommerce_after_checkout_validation',       array( $this, 'validate_on_checkout' ) );
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'add_files_to_order_display' ), PHP_INT_MAX );
			add_action( 'woocommerce_email_after_order_table',         array( $this, 'add_files_to_order_display' ), PHP_INT_MAX );
		}
	}

	/**
	 * add_files_to_order_display.
	 *
	 * @version 2.4.7
	 * @since   2.4.7
	 */
	function add_files_to_order_display( $order ) {
		$order_id = $order->id;
		$html = '';
		$total_files = get_post_meta( $order_id, '_' . 'wcj_checkout_files_total_files', true );
		for ( $i = 1; $i <= $total_files; $i++ ) {
			$real_file_name = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i, true );
			if ( '' != $real_file_name ) {
				$html .= __( 'File', 'woocommerce-jetpack' ) . ': ' . $real_file_name . '<br>';
			}
		}
		echo $html;
	}

	/**
	 * validate_on_checkout.
	 *
	 * @version 2.5.0
	 * @since   2.4.5
	 */
	function validate_on_checkout( $posted ) {
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if (
				'yes' === get_option( 'wcj_checkout_files_upload_enabled_' . $i, 'yes' ) &&
				$this->is_visible( $i ) &&
				'disable' != get_option( 'wcj_checkout_files_upload_hook_' . $i, 'woocommerce_before_checkout_form' )
			) {
				if ( 'yes' === get_option( 'wcj_checkout_files_upload_required_' . $i, 'no' ) && ! isset( $_SESSION[ 'wcj_checkout_files_upload_' . $i ] ) ) {
					// Is required
					wc_add_notice( get_option( 'wcj_checkout_files_upload_notice_required_' . $i, __( 'File is required!', 'woocommerce-jetpack' ) ), 'error' );
				}
				if ( '' != ( $file_accept = get_option( 'wcj_checkout_files_upload_file_accept_' . $i, '' ) ) && isset( $_SESSION[ 'wcj_checkout_files_upload_' . $i ] ) ) {
					// Validate file type
					$file_accept = explode( ',', $file_accept );
					if ( is_array( $file_accept ) && ! empty( $file_accept ) ) {
						$file_name = $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['name'];
						$file_type = '.' . pathinfo( $file_name, PATHINFO_EXTENSION );
						if ( ! in_array( $file_type, $file_accept ) ) {
							wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
								__( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ) ), $file_name ), 'error' );
						}
					}
				}
			}
		}
	}

	/**
	 * add_file_admin_order_meta_box.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
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
	 *
	 * @version 2.4.5
	 * @since   2.4.5
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
	 *
	 * @version 2.4.5
	 * @since   2.4.5
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
	 *
	 * @version 2.5.0
	 * @since   2.4.5
	 */
	function process_checkout_files_upload() {
		if ( ! session_id() ) {
			session_start();
		}
		// Remove file
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( isset( $_POST[ 'wcj_remove_checkout_file_' . $i ] ) ) {
				if ( isset( $_POST[ 'wcj_checkout_files_upload_order_id_' . $i ] ) ) {
					$order_id = $_POST[ 'wcj_checkout_files_upload_order_id_' . $i ];
					$order_file_name = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_' . $i, true );
					if ( '' != $order_file_name ) {
						$file_path = wcj_get_wcj_uploads_dir( 'checkout_files_upload' ) . '/' . $order_file_name;
						unlink( $file_path );
						$file_name = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i, true );
						wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_success_remove_' . $i,
							__( 'File "%s" was successfully removed.', 'woocommerce-jetpack' ) ), $file_name ) );
						delete_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_' . $i );
						delete_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i );
					}
				} else {
					$file_name = 'wcj_checkout_files_upload_' . $i;
					unlink( $_SESSION[ $file_name ]['tmp_name'] );
					wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_success_remove_' . $i,
						__( 'File "%s" was successfully removed.', 'woocommerce-jetpack' ) ), $_SESSION[ $file_name ]['name'] ) );
					unset( $_SESSION[ $file_name ] );
				}
			}
		}
		// Upload file
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( isset( $_POST[ 'wcj_upload_checkout_file_' . $i ] ) ) {
				$file_name = 'wcj_checkout_files_upload_' . $i;
				if ( isset( $_FILES[ $file_name ] ) && '' != $_FILES[ $file_name ]['tmp_name'] ) {
					// Validate
					$is_valid = true;
					if ( '' != ( $file_accept = get_option( 'wcj_checkout_files_upload_file_accept_' . $i, '' ) ) && isset( $_FILES[ $file_name ] ) ) {
						// Validate file type
						$file_accept = explode( ',', $file_accept );
						if ( is_array( $file_accept ) && ! empty( $file_accept ) ) {
							$real_file_name = $_FILES[ $file_name ]['name'];
							$file_type      = '.' . pathinfo( $real_file_name, PATHINFO_EXTENSION );
							if ( ! in_array( $file_type, $file_accept ) ) {
								wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
									__( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ) ), $real_file_name ), 'error' );
								$is_valid = false;
							}
						}
					}
					if ( $is_valid ) {
						// To session
						$_SESSION[ $file_name ] = $_FILES[ $file_name ];
						$tmp_dest_file = tempnam( sys_get_temp_dir(), 'wcj' );
						move_uploaded_file( $_SESSION[ $file_name ]['tmp_name'], $tmp_dest_file );
						$_SESSION[ $file_name ]['tmp_name'] = $tmp_dest_file;
						wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_success_upload_' . $i,
							__( 'File "%s" was successfully uploaded.', 'woocommerce-jetpack' ) ), $_SESSION[ $file_name ]['name'] ) );
						// To order
						if ( isset( $_POST[ 'wcj_checkout_files_upload_order_id_' . $i ] ) ) {
							$this->add_files_to_order( $_POST[ 'wcj_checkout_files_upload_order_id_' . $i ], null );
						}
					}
				} else {
					wc_add_notice( get_option( 'wcj_checkout_files_upload_notice_upload_no_file_' . $i,
						__( 'Please select file to upload!', 'woocommerce-jetpack' ) ), 'notice' );
				}
			}
		}
		// Admin file download
		if ( isset( $_GET['wcj_download_checkout_file_admin'] ) ) {
			$tmp_file_name = wcj_get_wcj_uploads_dir( 'checkout_files_upload' ) . '/' . $_GET['wcj_download_checkout_file_admin'];
			$file_name     = get_post_meta( $_GET['post'], '_' . 'wcj_checkout_files_upload_real_name_' . $_GET['wcj_checkout_file_number'], true );
			if ( wcj_is_user_role( 'administrator' ) || is_shop_manager() ) {
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
		// User file download
		if ( isset( $_GET['wcj_download_checkout_file'] ) && isset( $_GET['_wpnonce'] ) && ( false !== wp_verify_nonce( $_GET['_wpnonce'], 'wcj_download_checkout_file' ) ) ) {
			$i = $_GET['wcj_download_checkout_file'];
			if ( isset( $_GET['order-received'] ) || isset( $_GET['view-order'] ) ) {
				$order_id = isset( $_GET['order-received'] ) ? $_GET['order-received'] : $_GET['view-order'];
				$the_order = wc_get_order( $order_id );
				if ( ! $the_order->key_is_valid( $_GET['key'] ) ) {
					return;
				}
				$order_file_name = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_' . $i, true );
				$tmp_file_name = wcj_get_wcj_uploads_dir( 'checkout_files_upload' ) . '/' . $order_file_name;
				$file_name     = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i, true );
			} else {
				$tmp_file_name = $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['tmp_name'];
				$file_name     = $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['name'];
			}
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

	/**
	 * is_visible.
	 *
	 * @version 2.5.0
	 * @since   2.4.7
	 */
	function is_visible( $i, $order_id = 0 ) {

		// Include by product id
		$products_in = get_option( 'wcj_checkout_files_upload_show_products_in_' . $i );
		if ( ! empty( $products_in ) ) {
			$do_skip_by_products = true;
			if ( 0 != $order_id ) {
				$the_order = wc_get_order( $order_id );
				$the_items = $the_order->get_items();
			} else {
				$the_items = WC()->cart->get_cart();
			}
			foreach ( $the_items as $cart_item_key => $values ) {
				if ( in_array( $values['product_id'], $products_in ) ) {
					$do_skip_by_products = false;
					break;
				}
			}
			if ( $do_skip_by_products ) return false;
		}

		// Include by product category
		$categories_in = get_option( 'wcj_checkout_files_upload_show_cats_in_' . $i );
		if ( ! empty( $categories_in ) ) {
			$do_skip_by_cats = true;
			if ( 0 != $order_id ) {
				$the_order = wc_get_order( $order_id );
				$the_items = $the_order->get_items();
			} else {
				$the_items = WC()->cart->get_cart();
			}
			foreach ( $the_items as $cart_item_key => $values ) {
				$product_categories = get_the_terms( $values['product_id'], 'product_cat' );
				if ( empty( $product_categories ) ) continue;
				foreach( $product_categories as $product_category ) {
					if ( in_array( $product_category->term_id, $categories_in ) ) {
						$do_skip_by_cats = false;
						break;
					}
				}
				if ( ! $do_skip_by_cats ) break;
			}
			if ( $do_skip_by_cats ) return false;
		}

		// Include by product tag
		$tags_in = get_option( 'wcj_checkout_files_upload_show_tags_in_' . $i );
		if ( ! empty( $tags_in ) ) {
			$do_skip_by_tags = true;
			if ( 0 != $order_id ) {
				$the_order = wc_get_order( $order_id );
				$the_items = $the_order->get_items();
			} else {
				$the_items = WC()->cart->get_cart();
			}
			foreach ( $the_items as $cart_item_key => $values ) {
				$product_tags = get_the_terms( $values['product_id'], 'product_tag' );
				if ( empty( $product_tags ) ) continue;
				foreach( $product_tags as $product_tag ) {
					if ( in_array( $product_tag->term_id, $tags_in ) ) {
						$do_skip_by_tags = false;
						break;
					}
				}
				if ( ! $do_skip_by_tags ) break;
			}
			if ( $do_skip_by_tags ) return false;
		}

		return true;
	}

	/**
	 * get_the_form.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_the_form( $i, $file_name, $order_id = 0 ) {
		$html = '';
		$html .= '<form enctype="multipart/form-data" action="" method="POST">';
		$html .= '<table>';
		if ( '' != ( $the_label = get_option( 'wcj_checkout_files_upload_label_' . $i, '' ) ) ) {
			$html .= '<tr>';
			$html .= '<td colspan="2">';
			$html .= '<label for="wcj_checkout_files_upload_' . $i . '">' . $the_label . '</label>';
			if ( 'yes' === get_option( 'wcj_checkout_files_upload_required_' . $i, 'no' ) ) {
				$html .= '&nbsp;<abbr class="required" title="required">*</abbr>';
			}
			$html .= '</td>';
			$html .= '</tr>';
		}
		if ( '' == $file_name ) {
			$html .= '<tr>';
			$html .= '<td style="width:50%;">';
			$html .= '<input type="file" name="wcj_checkout_files_upload_' . $i . '" id="wcj_checkout_files_upload_' . $i .
				'" accept="' . get_option( 'wcj_checkout_files_upload_file_accept_' . $i, '' ) . '">';
			$html .= '</td>';
			$html .= '<td style="width:50%;">';
			$html .= '<input type="submit"' .
				' class="button alt"' .
				' style="width:100%;"' .
				' name="wcj_upload_checkout_file_' . $i . '"' .
				' id="wcj_upload_checkout_file_' . $i . '"' .
				' value="'      . get_option( 'wcj_checkout_files_upload_label_upload_button_' . $i, __( 'Upload', 'woocommerce-jetpack' ) ) . '"' .
				' data-value="' . get_option( 'wcj_checkout_files_upload_label_upload_button_' . $i, __( 'Upload', 'woocommerce-jetpack' ) ) . '">';
			$html .= '</td>';
			$html .= '</tr>';
		} else {
			$html .= '<tr>';
			$html .= '<td style="width:50%;">';
			$html .= '<a href="' . add_query_arg( array( 'wcj_download_checkout_file' => $i, '_wpnonce' => wp_create_nonce( 'wcj_download_checkout_file' ) ) ) . '">' . $file_name . '</a>';
			$html .= '</td>';
			$html .= '<td style="width:50%;">';
			$html .= '<input type="submit"' .
				' class="button"' .
				' style="width:100%;"' .
				' name="wcj_remove_checkout_file_' . $i . '"' .
				' id="wcj_remove_checkout_file_' . $i . '"' .
				' value="'      . get_option( 'wcj_checkout_files_upload_label_remove_button_' . $i, __( 'Remove', 'woocommerce-jetpack' ) ) . '"' .
				' data-value="' . get_option( 'wcj_checkout_files_upload_label_remove_button_' . $i, __( 'Remove', 'woocommerce-jetpack' ) ) . '">';
			$html .= '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';
		if ( 0 != $order_id ) {
			$html .= '<input type="hidden" name="wcj_checkout_files_upload_order_id_' . $i . '" value="' . $order_id . '">';
		}
		$html .= '</form>';
		return $html;
	}

	/**
	 * add_files_upload_form_to_thankyou_and_myaccount_page.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_files_upload_form_to_thankyou_and_myaccount_page( $order_id ) {
		$html = '';
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_checkout_files_upload_enabled_' . $i, 'yes' ) && $this->is_visible( $i, $order_id ) ) {
				$file_name = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i, true );
				$html .= $this->get_the_form( $i, $file_name, $order_id );
			}
		}
		echo $html;
	}

	/**
	 * add_files_upload_form_to_checkout_frontend.
	 *
	 * @version 2.5.2
	 * @since   2.4.5
	 */
	function add_files_upload_form_to_checkout_frontend() {
		$this->add_files_upload_form_to_checkout_frontend_all();
	}

	/**
	 * add_files_upload_form_to_checkout_frontend_all.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_files_upload_form_to_checkout_frontend_all( $is_direct_call = false ) {
		$html = '';
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		if ( ! $is_direct_call ) {
			$current_filter = current_filter();
			$current_filter_priority = wcj_current_filter_priority();
		}
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$is_filter_ok = ( $is_direct_call ) ? true : (
				$current_filter === get_option( 'wcj_checkout_files_upload_hook_' . $i, 'woocommerce_before_checkout_form' ) &&
				$current_filter_priority == get_option( 'wcj_checkout_files_upload_hook_priority_' . $i, 10 )
			);
			if ( 'yes' === get_option( 'wcj_checkout_files_upload_enabled_' . $i, 'yes' ) && $is_filter_ok && $this->is_visible( $i ) ) {
				$file_name = ( isset( $_SESSION[ 'wcj_checkout_files_upload_' . $i ] ) ) ? $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['name'] : '';
				$html .= $this->get_the_form( $i, $file_name );
			}
		}
		echo $html;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 * @since   2.4.5
	 */
	function get_settings() {

		$product_tags_options = array();
		$product_tags = get_terms( 'product_tag', 'orderby=name&hide_empty=0' );
		if ( ! empty( $product_tags ) && ! is_wp_error( $product_tags ) ){
			foreach ( $product_tags as $product_tag ) {
				$product_tags_options[ $product_tag->term_id ] = $product_tag->name;
			}
		}

		$product_cats_options = array();
		$product_cats = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ){
			foreach ( $product_cats as $product_cat ) {
				$product_cats_options[ $product_cat->term_id ] = $product_cat->name;
			}
		}

		$products_options = apply_filters( 'wcj_get_products_filter', array() );

		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_checkout_files_upload_options',
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
					'title'    => __( 'File', 'woocommerce-jetpack' ) . ' #' . $i,
					'id'       => 'wcj_checkout_files_upload_enabled_' . $i,
					'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
					'type'     => 'checkbox',
					'default'  => 'yes',
				),
				array(
					'id'       => 'wcj_checkout_files_upload_required_' . $i,
					'desc'     => __( 'Required', 'woocommerce-jetpack' ),
					'type'     => 'checkbox',
					'default'  => 'no',
				),
				array(
					'id'       => 'wcj_checkout_files_upload_hook_' . $i,
					'desc'     => __( 'Position', 'woocommerce-jetpack' ),
					'default'  => 'woocommerce_before_checkout_form',
					'type'     => 'select',
					'options'  => array(
						'woocommerce_before_checkout_form'              => __( 'Before checkout form', 'woocommerce-jetpack' ),
						'woocommerce_after_checkout_form'               => __( 'After checkout form', 'woocommerce-jetpack' ),
						'disable'                                       => __( 'Do not add on checkout', 'woocommerce-jetpack' ),
					),
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Position order', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_hook_priority_' . $i,
					'default'  => 20,
					'type'     => 'number',
					'custom_attributes' => array( 'min' => '0' ),
					'css'      => 'width:250px;',
				),
				array(
					'id'       => 'wcj_checkout_files_upload_add_to_thankyou_' . $i,
					'desc'     => __( 'Add to Thank You page', 'woocommerce-jetpack' ),
					'type'     => 'checkbox',
					'default'  => 'no',
				),
				array(
					'id'       => 'wcj_checkout_files_upload_add_to_myaccount_' . $i,
					'desc'     => __( 'Add to My Account page', 'woocommerce-jetpack' ),
					'type'     => 'checkbox',
					'default'  => 'no',
				),
				array(
					'desc'     => __( 'Label', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Leave blank to disable label', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_label_' . $i,
					'default'  => __( 'Please select file to upload', 'woocommerce-jetpack' ),
					'type'     => 'textarea',
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Accepted file types', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Accepted file types. E.g.: ".jpg,.jpeg,.png". Leave blank to accept all files', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_file_accept_' . $i,
					'default'  => '.jpg,.jpeg,.png',
					'type'     => 'text',
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Label: Upload button', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_label_upload_button_' . $i,
					'default'  =>  __( 'Upload', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Label: Remove button', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_label_remove_button_' . $i,
					'default'  =>  __( 'Remove', 'woocommerce-jetpack' ),
					'type'     => 'text',
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Notice: Wrong file type', 'woocommerce-jetpack' ),
					'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
					'default'  =>  __( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ),
					'type'     => 'textarea',
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Notice: File is required', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_notice_required_' . $i,
					'default'  =>  __( 'File is required!', 'woocommerce-jetpack' ),
					'type'     => 'textarea',
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Notice: File was successfully uploaded', 'woocommerce-jetpack' ),
					'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_notice_success_upload_' . $i,
					'default'  =>  __( 'File "%s" was successfully uploaded.', 'woocommerce-jetpack' ),
					'type'     => 'textarea',
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Notice: No file selected', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_notice_upload_no_file_' . $i,
					'default'  =>  __( 'Please select file to upload!', 'woocommerce-jetpack' ),
					'type'     => 'textarea',
					'css'      => 'width:250px;',
				),
				array(
					'desc'     => __( 'Notice: File was successfully removed', 'woocommerce-jetpack' ),
					'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_notice_success_remove_' . $i,
					'default'  =>  __( 'File "%s" was successfully removed.', 'woocommerce-jetpack' ),
					'type'     => 'textarea',
					'css'      => 'width:250px;',
				),
				array(
					'title'    => '',
					'desc'     => __( 'PRODUCTS to show this field', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'To show this field only if at least one selected product is in cart, enter products here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_show_products_in_' . $i,
					'default'  => '',
					'class'    => 'chosen_select',
					'type'     => 'multiselect',
					'options'  => $products_options,
				),
				array(
					'title'    => '',
					'desc'     => __( 'CATEGORIES to show this field', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'To show this field only if at least one product of selected category is in cart, enter categories here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_show_cats_in_' . $i,
					'default'  => '',
					'class'    => 'chosen_select',
					'type'     => 'multiselect',
					'options'  => $product_cats_options,
				),
				array(
					'title'    => '',
					'desc'     => __( 'TAGS to show this field', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'To show this field only if at least one product of selected tag is in cart, enter tags here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_checkout_files_upload_show_tags_in_' . $i,
					'default'  => '',
					'class'    => 'chosen_select',
					'type'     => 'multiselect',
					'options'  => $product_tags_options,
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

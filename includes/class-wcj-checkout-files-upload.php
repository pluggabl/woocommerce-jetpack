<?php
/**
 * Booster for WooCommerce - Module - Checkout Files Upload
 *
 * @version 3.2.3
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
	 * @version 2.8.0
	 * @since   2.4.5
	 */
	function __construct() {

		$this->id         = 'checkout_files_upload';
		$this->short_desc = __( 'Checkout Files Upload', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let customers upload files on (or after) WooCommerce checkout.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-checkout-files-upload';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_file_admin_order_meta_box' ) );
			add_action( 'init', array( $this, 'process_checkout_files_upload' ) );
			$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
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
			add_filter( 'woocommerce_email_attachments',               array( $this, 'add_files_to_email_attachments' ), PHP_INT_MAX, 3 );
		}
	}

	/**
	 * add_files_to_email_attachments.
	 *
	 * @version 2.7.0
	 * @since   2.5.5
	 */
	function add_files_to_email_attachments( $attachments, $status, $order ) {
		if (
			( 'new_order'                 === $status && 'yes' === get_option( 'wcj_checkout_files_upload_attach_to_admin_new_order',           'yes' ) ) ||
			( 'customer_processing_order' === $status && 'yes' === get_option( 'wcj_checkout_files_upload_attach_to_customer_processing_order', 'yes' ) )
		) {
			$total_files = get_post_meta( wcj_get_order_id( $order ), '_' . 'wcj_checkout_files_total_files', true );
			for ( $i = 1; $i <= $total_files; $i++ ) {
				$attachments[] = wcj_get_wcj_uploads_dir( 'checkout_files_upload' ) . '/' . get_post_meta( wcj_get_order_id( $order ), '_' . 'wcj_checkout_files_upload_' . $i, true );
			}
		}
		return $attachments;
	}

	/**
	 * add_files_to_order_display.
	 *
	 * @version 2.7.0
	 * @since   2.4.7
	 */
	function add_files_to_order_display( $order ) {
		$order_id = wcj_get_order_id( $order );
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
	 * @version 3.2.3
	 * @since   2.4.5
	 */
	function validate_on_checkout( $posted ) {
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
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
				if ( ! isset( $_SESSION[ 'wcj_checkout_files_upload_' . $i ] ) ) {
					continue;
				}
				$file_name = $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['name'];
				$file_type = '.' . pathinfo( $file_name, PATHINFO_EXTENSION );
				if ( '' != ( $file_accept = get_option( 'wcj_checkout_files_upload_file_accept_' . $i, '' ) ) ) {
					// Validate file type
					$file_accept = explode( ',', $file_accept );
					if ( is_array( $file_accept ) && ! empty( $file_accept ) ) {
						if ( ! in_array( $file_type, $file_accept ) ) {
							wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
								__( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ) ), $file_name ), 'error' );
						}
					}
				}
				if ( $this->is_extension_blocked( $file_type ) ) {
					wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
						__( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ) ), $file_name ), 'error' );
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
	 * @version 2.5.6
	 * @since   2.4.5
	 */
	function create_file_admin_order_meta_box() {
		$order_id = get_the_ID();
		$html = '';
		$total_files = get_post_meta( $order_id, '_' . 'wcj_checkout_files_total_files', true );
		$files_exists = false;
		for ( $i = 1; $i <= $total_files; $i++ ) {
			$order_file_name = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_'           . $i, true );
			$real_file_name  = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i, true );
			if ( '' != $order_file_name ) {
				$files_exists = true;
				$html .= '<p><a href="' . add_query_arg(
					array(
						'wcj_download_checkout_file_admin' => $order_file_name,
						'wcj_checkout_file_number'         => $i,
					) ) . '">' . $real_file_name . '</a></p>';
			}
		}
		if ( ! $files_exists ) {
			$html .= '<p><em>' . __( 'No files uploaded.', 'woocommerce-jetpack' ) . '</em></p>';
		}
		echo $html;
	}

	/**
	 * is_extension_blocked.
	 *
	 * @version 3.2.3
	 * @since   3.2.3
	 */
	function is_extension_blocked( $ext ) {
		if ( 'no' === get_option( 'wcj_checkout_files_upload_block_files_enabled', 'yes' ) ) {
			return false;
		}
		$ext = strtolower( $ext );
		if ( strlen( $ext ) > 0 && '.' === $ext[0] ) {
			$ext = substr( $ext, 1 );
		}
		$blocked_file_exts = get_option( 'wcj_checkout_files_upload_block_files_exts',
			'bat|exe|cmd|sh|php|php0|php1|php2|php3|php4|php5|php6|php7|php8|php9|ph|ph0|ph1|ph2|ph3|ph4|ph5|ph6|ph7|ph8|ph9|pl|cgi|386|dll|com|torrent|js|app|jar|pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh|html|htm'
		);
		$blocked_file_exts = explode( '|', $blocked_file_exts );
		return in_array( $ext, $blocked_file_exts );
	}

	/**
	 * add_files_to_order.
	 *
	 * @version 3.2.3
	 * @since   2.4.5
	 */
	function add_files_to_order( $order_id, $posted ) {
		$upload_dir = wcj_get_wcj_uploads_dir( 'checkout_files_upload' );
		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir, 0755, true );
		}
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( isset( $_SESSION[ 'wcj_checkout_files_upload_' . $i ] ) ) {
				$file_name          = $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['name'];
				$ext                = pathinfo( $file_name, PATHINFO_EXTENSION );
				$download_file_name = $order_id . '_' . $i . '.' . $ext;
				$file_path          = $upload_dir . '/' . $download_file_name;
				$tmp_file_name      = $_SESSION[ 'wcj_checkout_files_upload_' . $i ]['tmp_name'];
				$file_data          = file_get_contents( $tmp_file_name );
				if ( ! $this->is_extension_blocked( $ext ) ) { // should already be validated earlier, but just in case...
					file_put_contents( $file_path, $file_data );
				}
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
	 * @version 3.2.3
	 * @since   2.4.5
	 */
	function process_checkout_files_upload() {
		if ( ! session_id() ) {
			session_start();
		}
		// Remove file
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
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
					$real_file_name = $_FILES[ $file_name ]['name'];
					$file_type      = '.' . pathinfo( $real_file_name, PATHINFO_EXTENSION );
					if ( '' != ( $file_accept = get_option( 'wcj_checkout_files_upload_file_accept_' . $i, '' ) ) ) {
						// Validate file type
						$file_accept = explode( ',', $file_accept );
						if ( is_array( $file_accept ) && ! empty( $file_accept ) ) {
							if ( ! in_array( $file_type, $file_accept ) ) {
								wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
									__( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ) ), $real_file_name ), 'error' );
								$is_valid = false;
							}
						}
					}
					if ( $this->is_extension_blocked( $file_type ) ) {
						wc_add_notice( sprintf( get_option( 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
							__( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ) ), $real_file_name ), 'error' );
						$is_valid = false;
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
	 * @version 2.5.8
	 * @since   2.4.7
	 */
	function is_visible( $i, $order_id = 0 ) {

		if ( apply_filters( 'wcj_checkout_files_always_visible_on_empty_cart', false ) && 0 == $order_id && WC()->cart->is_empty() ) {
			// Added for "One Page Checkout" plugin compatibility.
			return true;
		}

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

		// Exclude by product id
		$products_in = get_option( 'wcj_checkout_files_upload_hide_products_in_' . $i );
		if ( ! empty( $products_in ) ) {
			if ( 0 != $order_id ) {
				$the_order = wc_get_order( $order_id );
				$the_items = $the_order->get_items();
			} else {
				$the_items = WC()->cart->get_cart();
			}
			foreach ( $the_items as $cart_item_key => $values ) {
				if ( in_array( $values['product_id'], $products_in ) ) {
					return false;
				}
			}
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

		// Exclude by product category
		$categories_in = get_option( 'wcj_checkout_files_upload_hide_cats_in_' . $i );
		if ( ! empty( $categories_in ) ) {
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
						return false;
					}
				}
			}
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

		// Exclude by product tag
		$tags_in = get_option( 'wcj_checkout_files_upload_hide_tags_in_' . $i );
		if ( ! empty( $tags_in ) ) {
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
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * get_the_form.
	 *
	 * @version 2.5.6
	 * @since   2.5.0
	 */
	function get_the_form( $i, $file_name, $order_id = 0 ) {
		$html = '';
		$html .= '<form enctype="multipart/form-data" action="" method="POST">';
		$html .= get_option( 'wcj_checkout_files_upload_form_template_before', '<table>' );
		if ( '' != ( $the_label = get_option( 'wcj_checkout_files_upload_label_' . $i, '' ) ) ) {
			$template = get_option( 'wcj_checkout_files_upload_form_template_label',
				'<tr><td colspan="2"><label for="%field_id%">%field_label%</label>%required_html%</td></tr>' );
			$required_html = ( 'yes' === get_option( 'wcj_checkout_files_upload_required_' . $i, 'no' ) ) ?
				'&nbsp;<abbr class="required" title="required">*</abbr>' : '';
			$html .= str_replace(
				array( '%field_id%', '%field_label%', '%required_html%' ),
				array( 'wcj_checkout_files_upload_' . $i, $the_label, $required_html ),
				$template
			);
		}
		if ( '' == $file_name ) {
			$field_html = '<input type="file" name="wcj_checkout_files_upload_' . $i . '" id="wcj_checkout_files_upload_' . $i .
				'" accept="' . get_option( 'wcj_checkout_files_upload_file_accept_' . $i, '' ) . '">';
			$button_html = '<input type="submit"' .
				' class="button alt"' .
				' style="width:100%;"' .
				' name="wcj_upload_checkout_file_' . $i . '"' .
				' id="wcj_upload_checkout_file_' . $i . '"' .
				' value="'      . get_option( 'wcj_checkout_files_upload_label_upload_button_' . $i, __( 'Upload', 'woocommerce-jetpack' ) ) . '"' .
				' data-value="' . get_option( 'wcj_checkout_files_upload_label_upload_button_' . $i, __( 'Upload', 'woocommerce-jetpack' ) ) . '">';
		} else {
			$field_html = '<a href="' . add_query_arg( array( 'wcj_download_checkout_file' => $i, '_wpnonce' => wp_create_nonce( 'wcj_download_checkout_file' ) ) ) . '">' . $file_name . '</a>';
			$button_html = '<input type="submit"' .
				' class="button"' .
				' style="width:100%;"' .
				' name="wcj_remove_checkout_file_' . $i . '"' .
				' id="wcj_remove_checkout_file_' . $i . '"' .
				' value="'      . get_option( 'wcj_checkout_files_upload_label_remove_button_' . $i, __( 'Remove', 'woocommerce-jetpack' ) ) . '"' .
				' data-value="' . get_option( 'wcj_checkout_files_upload_label_remove_button_' . $i, __( 'Remove', 'woocommerce-jetpack' ) ) . '">';
		}
		$template = get_option( 'wcj_checkout_files_upload_form_template_field',
			'<tr><td style="width:50%;">%field_html%</td><td style="width:50%;">%button_html%</td></tr>' );
		$html .= str_replace(
			array( '%field_html%', '%button_html%' ),
			array( $field_html, $button_html ),
			$template
		);
		$html .= get_option( 'wcj_checkout_files_upload_form_template_after', '</table>' );
		if ( 0 != $order_id ) {
			$html .= '<input type="hidden" name="wcj_checkout_files_upload_order_id_' . $i . '" value="' . $order_id . '">';
		}
		$html .= '</form>';
		return $html;
	}

	/**
	 * add_files_upload_form_to_thankyou_and_myaccount_page.
	 *
	 * @version 2.5.6
	 * @since   2.5.0
	 */
	function add_files_upload_form_to_thankyou_and_myaccount_page( $order_id ) {
		$html = '';
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
		$current_filter = current_filter();
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_checkout_files_upload_enabled_' . $i, 'yes' ) && $this->is_visible( $i, $order_id ) ) {
				if (
					( 'yes' === get_option( 'wcj_checkout_files_upload_add_to_thankyou_'  . $i, 'no' ) && 'woocommerce_thankyou'   === $current_filter ) ||
					( 'yes' === get_option( 'wcj_checkout_files_upload_add_to_myaccount_' . $i, 'no' ) && 'woocommerce_view_order' === $current_filter )
				) {
					$file_name = get_post_meta( $order_id, '_' . 'wcj_checkout_files_upload_real_name_' . $i, true );
					$html .= $this->get_the_form( $i, $file_name, $order_id );
				}
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
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
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

}

endif;

return new WCJ_Checkout_Files_Upload();

<?php
/**
 * Booster for WooCommerce - Functions - Invoicing
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! function_exists( 'wcj_get_invoicing_temp_dir' ) ) {
	/**
	 * Wcj_get_invoicing_temp_dir.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function wcj_get_invoicing_temp_dir() {
		$tmp_dir = wcj_get_option( 'wcj_invoicing_general_tmp_dir', '' );
		return ( '' === ( $tmp_dir ) ? sys_get_temp_dir() : $tmp_dir );
	}
}

if ( ! function_exists( 'wcj_get_invoicing_current_image_path_desc' ) ) {
	/**
	 * Wcj_get_invoicing_current_image_path_desc.
	 *
	 * @version 3.4.3
	 * @since   3.4.3
	 * @param   string $option_name defines the option_name.
	 */
	function wcj_get_invoicing_current_image_path_desc( $option_name ) {
		$current_image = wcj_get_option( $option_name, '' );
		if ( '' !== ( $current_image ) ) {
			$default_images_directory = wcj_get_invoicing_default_images_directory();
			if ( false !== ( $default_images_directory ) ) {
				$image_path = $default_images_directory . wp_parse_url( $current_image, PHP_URL_PATH );
				$style      = ( file_exists( $image_path ) ? ' style="color:green;"' : '' );
				/* translators: %s: search term */
				$current_image = '<br>' . sprintf( __( 'Current image path: %s.', 'woocommerce-jetpack' ), '<code' . $style . '>' . $image_path . '</code>' );
			} else {
				$current_image = '';
			}
		}
		return $current_image;
	}
}

if ( ! function_exists( 'wcj_get_invoicing_default_images_directory' ) ) {
	/**
	 * Wcj_get_invoicing_default_images_directory.
	 *
	 * @version 5.6.8
	 * @since   3.4.2
	 */
	function wcj_get_invoicing_default_images_directory() {
		switch ( wcj_get_option( 'wcj_invoicing_general_header_images_path', 'document_root' ) ) {
			case 'empty':
				return '';
			case 'document_root':
				return getenv( 'DOCUMENT_ROOT' );
			case 'abspath':
				return ABSPATH;
			default: // tcpdf_default.
				return false;
		}
	}
}

if ( ! function_exists( 'wcj_get_fonts_list' ) ) {
	/**
	 * Wcj_get_fonts_list.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) update existing fonts files
	 * @todo    (maybe) add more fonts
	 */
	function wcj_get_fonts_list() {
		return array(
			'angsanaupc.ctg.z',
			'angsanaupc.php',
			'angsanaupc.z',
			'angsanaupcb.ctg.z',
			'angsanaupcb.php',
			'angsanaupcb.z',
			'angsanaupcbi.ctg.z',
			'angsanaupcbi.php',
			'angsanaupcbi.z',
			'angsanaupci.ctg.z',
			'angsanaupci.php',
			'angsanaupci.z',
			'cid0ct.php',
			'cordiaupc.ctg.z',
			'cordiaupc.php',
			'cordiaupc.z',
			'cordiaupcb.ctg.z',
			'cordiaupcb.php',
			'cordiaupcb.z',
			'cordiaupcbi.ctg.z',
			'cordiaupcbi.php',
			'cordiaupcbi.z',
			'cordiaupci.ctg.z',
			'cordiaupci.php',
			'cordiaupci.z',
			'courier.php',
			'courierb.php',
			'courierbi.php',
			'courieri.php',
			'dejavusans.ctg.z',
			'dejavusans.php',
			'dejavusans.z',
			'dejavusansb.ctg.z',
			'dejavusansb.php',
			'dejavusansb.z',
			'dejavusansbi.ctg.z',
			'dejavusansbi.php',
			'dejavusansbi.z',
			'droidsansfallback.ctg.z',
			'droidsansfallback.php',
			'droidsansfallback.z',
			'helvetica.php',
			'helveticab.php',
			'helveticabi.php',
			'helveticai.php',
			'stsongstdlight.php',
			'symbol.php',
			'thsarabun.ctg.z',
			'thsarabun.php',
			'thsarabun.z',
			'thsarabunb.ctg.z',
			'thsarabunb.php',
			'thsarabunb.z',
			'thsarabunbi.ctg.z',
			'thsarabunbi.php',
			'thsarabunbi.z',
			'thsarabuni.ctg.z',
			'thsarabuni.php',
			'thsarabuni.z',
			'times.php',
			'timesb.php',
			'timesbi.php',
			'timesi.php',
			'uni2cid_aj16.php',
			'zapfdingbats.php',
		);
	}
}

if ( ! function_exists( 'wcj_get_tcpdf_font' ) ) {
	/**
	 * Wcj_get_tcpdf_font.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @param   string $invoice_type defines the invoice_type.
	 */
	function wcj_get_tcpdf_font( $invoice_type ) {
		return ( wcj_check_tcpdf_fonts_version( true ) ?
			get_option( 'wcj_invoicing_' . $invoice_type . '_general_font_family', 'helvetica' ) :
			get_option( 'wcj_invoicing_' . $invoice_type . '_general_font_family_fallback', 'helvetica' )
		);
	}
}

if ( ! function_exists( 'wcj_get_tcpdf_fonts_version' ) ) {
	/**
	 * Wcj_get_tcpdf_fonts_version.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) save old (i.e. fallback) versions
	 */
	function wcj_get_tcpdf_fonts_version() {
		return '2.9.0';
	}
}

if ( ! function_exists( 'wcj_check_tcpdf_fonts_version' ) ) {
	/**
	 * Wcj_check_tcpdf_fonts_version.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @param   bool $force_file_check defines the force_file_check.
	 */
	function wcj_check_tcpdf_fonts_version( $force_file_check = false ) {
		if ( 'yes' === wcj_get_option( 'wcj_invoicing_fonts_manager_do_not_download', 'no' ) ) {
			return false;
		}
		$result = ( 0 === version_compare( wcj_get_option( 'wcj_invoicing_fonts_version', null ), wcj_get_tcpdf_fonts_version() ) );
		if ( $result && $force_file_check ) {
			$tcpdf_fonts_dir       = wcj_get_wcj_uploads_dir( 'tcpdf_fonts' ) . '/';
			$tcpdf_fonts_dir_files = scandir( $tcpdf_fonts_dir );
			$tcpdf_fonts_files     = wcj_get_fonts_list();
			foreach ( $tcpdf_fonts_files as $tcpdf_fonts_file ) {
				if ( ! in_array( $tcpdf_fonts_file, $tcpdf_fonts_dir_files, true ) ) {
					return false;
				}
			}
		}
		return $result;
	}
}

if ( ! function_exists( 'wcj_check_and_maybe_download_tcpdf_fonts' ) ) {
	/**
	 * Wcj_check_and_maybe_download_tcpdf_fonts.
	 *
	 * @version 5.6.8
	 * @since   2.9.0
	 * @todo    (maybe) check file size > 0 or even for exact size (not only if file exists in directory)
	 * @todo    (maybe) use `download_url()` instead of `file_get_contents()` or `curl` (in all Booster files)
	 * @param   bool $force_download defines the force_download.
	 */
	function wcj_check_and_maybe_download_tcpdf_fonts( $force_download = false ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		global $wp_filesystem;
		WP_Filesystem();
		if ( 'yes' === wcj_get_option( 'wcj_invoicing_fonts_manager_do_not_download', 'no' ) ) {
			return false;
		}
		if ( ! $force_download ) {
			if ( wcj_check_tcpdf_fonts_version( true ) ) {
				return true;
			}
			if ( ( wcj_get_timestamp_date_from_gmt() - wcj_get_option( 'wcj_invoicing_fonts_version_timestamp', null ) ) < 60 * 60 ) {
				return false;
			}
		}
		update_option( 'wcj_invoicing_fonts_version_timestamp', wcj_get_timestamp_date_from_gmt() );
		$tcpdf_fonts_dir = wcj_get_wcj_uploads_dir( 'tcpdf_fonts' ) . '/';
		if ( ! file_exists( $tcpdf_fonts_dir ) ) {
			mkdir( $tcpdf_fonts_dir );
		}
		$tcpdf_fonts_dir_files = scandir( $tcpdf_fonts_dir );
		$tcpdf_fonts_files     = wcj_get_fonts_list();
		require_once ABSPATH . 'wp-admin/includes/file.php';
		foreach ( $tcpdf_fonts_files as $tcpdf_fonts_file ) {
			if ( ! in_array( $tcpdf_fonts_file, $tcpdf_fonts_dir_files, true ) ) {
				$url = 'http://storage.booster.io/tcpdf_fonts/' . $tcpdf_fonts_file;
				if ( '.php' === substr( $tcpdf_fonts_file, -4 ) ) {
					$url .= '.data';
				}
				$response_file_name = download_url( $url );
				if ( ! is_wp_error( $response_file_name ) ) {
					$response = $wp_filesystem->get_contents( $response_file_name );
					if ( $response ) {
						if ( ! $wp_filesystem->put_contents( $tcpdf_fonts_dir . $tcpdf_fonts_file, $response, FS_CHMOD_FILE ) ) {
							return false;
						}
					} else {
						return false;
					}
					unlink( $response_file_name );
				} else {
					return false;
				}
			}
		}
		if (
			update_option( 'wcj_invoicing_fonts_version', wcj_get_tcpdf_fonts_version() ) &&
			update_option( 'wcj_invoicing_fonts_version_timestamp', wcj_get_timestamp_date_from_gmt() )
		) {
			return true;
		}
	}
}

if ( ! function_exists( 'wcj_get_invoice_types' ) ) {
	/**
	 * Wcj_get_invoice_types.
	 *
	 * @version 3.4.0
	 */
	function wcj_get_invoice_types() {
		$invoice_types     = array(
			array(
				'id'       => 'invoice',
				'title'    => wcj_get_option( 'wcj_invoicing_invoice_admin_title', __( 'Invoice', 'woocommerce-jetpack' ) ),
				'defaults' => array( 'init' => 'disabled' ),
				'color'    => 'green',
			),
			array(
				'id'       => 'proforma_invoice',
				'title'    => wcj_get_option( 'wcj_invoicing_proforma_invoice_admin_title', __( 'Proforma Invoice', 'woocommerce-jetpack' ) ),
				'defaults' => array( 'init' => 'disabled' ),
				'color'    => 'orange',
			),
			array(
				'id'       => 'packing_slip',
				'title'    => wcj_get_option( 'wcj_invoicing_packing_slip_admin_title', __( 'Packing Slip', 'woocommerce-jetpack' ) ),
				'defaults' => array( 'init' => 'disabled' ),
				'color'    => 'blue',
			),
			array(
				'id'       => 'credit_note',
				'title'    => wcj_get_option( 'wcj_invoicing_credit_note_admin_title', __( 'Credit Note', 'woocommerce-jetpack' ) ),
				'defaults' => array( 'init' => 'disabled' ),
				'color'    => 'red',
			),
		);
		$total_custom_docs = min( wcj_get_option( 'wcj_invoicing_custom_doc_total_number', 1 ), 100 );
		for ( $i = 1; $i <= $total_custom_docs; $i++ ) {
			$invoice_types[] = array(
				'id'            => ( 1 === $i ? 'custom_doc' : 'custom_doc_' . $i ),
				'title'         => wcj_get_option(
					'wcj_invoicing_' . ( 1 === $i ? 'custom_doc' : 'custom_doc_' . $i ) . '_admin_title',
					__( 'Custom Document', 'woocommerce-jetpack' ) . ' #' . $i
				),
				'defaults'      => array( 'init' => 'disabled' ),
				'color'         => 'gray',
				'is_custom_doc' => true,
				'custom_doc_nr' => $i,
			);
		}
		return $invoice_types;
	}
}

if ( ! function_exists( 'wcj_get_invoice_create_on' ) ) {
	/**
	 * Wcj_get_invoice_create_on.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 * @param   string $invoice_type defines the invoice_type.
	 */
	function wcj_get_invoice_create_on( $invoice_type ) {
		$create_on = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_create_on', '' );
		if ( empty( $create_on ) ) {
			return array();
		}
		if ( ! is_array( $create_on ) ) {
			// Backward compatibility with Booster version <= 3.1.3.
			if ( 'disabled' === $create_on ) {
				update_option( 'wcj_invoicing_' . $invoice_type . '_create_on', '' );
				return array();
			} elseif ( 'wcj_pdf_invoicing_create_on_any_refund' === $create_on ) {
				$create_on = array( 'woocommerce_order_status_refunded', 'woocommerce_order_partially_refunded_notification' );
				update_option( 'wcj_invoicing_' . $invoice_type . '_create_on', $create_on );
				return $create_on;
			} else {
				$create_on = array( $create_on );
				update_option( 'wcj_invoicing_' . $invoice_type . '_create_on', $create_on );
				return $create_on;
			}
		}
		return $create_on;
	}
}

if ( ! function_exists( 'wcj_get_enabled_invoice_types' ) ) {
	/**
	 * Wcj_get_enabled_invoice_types.
	 *
	 * @version 3.2.0
	 */
	function wcj_get_enabled_invoice_types() {
		$invoice_types         = wcj_get_invoice_types();
		$enabled_invoice_types = array();
		foreach ( $invoice_types as $k => $invoice_type ) {
			$z = ( 0 === $k ) ? wcj_get_invoice_create_on( $invoice_type['id'] ) : apply_filters( 'booster_option', '', wcj_get_invoice_create_on( $invoice_type['id'] ) );
			if ( empty( $z ) ) {
				continue;
			}
			$enabled_invoice_types[] = $invoice_type;
		}
		return $enabled_invoice_types;
	}
}

if ( ! function_exists( 'wcj_get_enabled_invoice_types_ids' ) ) {
	/**
	 * Wcj_get_enabled_invoice_types_ids.
	 */
	function wcj_get_enabled_invoice_types_ids() {
		$invoice_types     = wcj_get_enabled_invoice_types();
		$invoice_types_ids = array();
		foreach ( $invoice_types as $invoice_type ) {
			$invoice_types_ids[] = $invoice_type['id'];
		}
		return $invoice_types_ids;
	}
}

if ( ! function_exists( 'wcj_get_pdf_invoice' ) ) {
	/**
	 * Wcj_get_pdf_invoice.
	 *
	 * @param   int           $order_id defines the order_id.
	 * @param   int  | string $invoice_type_id defines the invoice_type_id.
	 */
	function wcj_get_pdf_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = new WCJ_PDF_Invoice( $order_id, $invoice_type_id );
		return $the_invoice;
	}
}

if ( ! function_exists( 'wcj_get_invoice' ) ) {
	/**
	 * Wcj_get_invoice.
	 *
	 * @param   int  | string $order_id defines the order_id.
	 * @param   int  | string $invoice_type_id defines the invoice_type_id.
	 */
	function wcj_get_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = new WCJ_Invoice( $order_id, $invoice_type_id );
		return $the_invoice;
	}
}

if ( ! function_exists( 'wcj_get_invoice_date' ) ) {
	/**
	 * Wcj_get_invoice_date.
	 *
	 * @version 2.9.0
	 * @param   int  | string $order_id defines the order_id.
	 * @param   int  | string $invoice_type_id defines the invoice_type_id.
	 * @param   int  | string $extra_days defines the extra_days.
	 * @param   int  | string $date_format defines the date_format.
	 */
	function wcj_get_invoice_date( $order_id, $invoice_type_id, $extra_days, $date_format ) {
		$the_invoice            = wcj_get_invoice( $order_id, $invoice_type_id );
		$invoice_date_timestamp = $the_invoice->get_invoice_date();
		if ( $invoice_date_timestamp ) {
			$extra_days_in_sec = $extra_days * 24 * 60 * 60;
			return date_i18n( $date_format, $invoice_date_timestamp + $extra_days_in_sec );
		} else {
			return '';
		}
	}
}

if ( ! function_exists( 'wcj_get_invoice_number' ) ) {
	/**
	 * Wcj_get_invoice_number.
	 *
	 * @param   int  | string $order_id defines the order_id.
	 * @param   int  | string $invoice_type_id defines the invoice_type_id.
	 */
	function wcj_get_invoice_number( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		return $the_invoice->get_invoice_number();
	}
}

if ( ! function_exists( 'wcj_delete_invoice' ) ) {
	/**
	 * Wcj_delete_invoice.
	 *
	 * @param   int  | string $order_id defines the order_id.
	 * @param   int  | string $invoice_type_id defines the invoice_type_id.
	 */
	function wcj_delete_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		$the_invoice->delete();
	}
}

if ( ! function_exists( 'wcj_create_invoice' ) ) {
	/**
	 * Wcj_create_invoice.
	 *
	 * @param   int  | string $order_id defines the order_id.
	 * @param   int  | string $invoice_type_id defines the invoice_type_id.
	 * @param   null          $date defines the date.
	 */
	function wcj_create_invoice( $order_id, $invoice_type_id, $date = '' ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		$the_invoice->create( $date );
	}
}

if ( ! function_exists( 'wcj_is_invoice_created' ) ) {
	/**
	 * Wcj_is_invoice_created.
	 *
	 * @param   int  | string $order_id defines the order_id.
	 * @param   int  | string $invoice_type_id defines the invoice_type_id.
	 */
	function wcj_is_invoice_created( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		return $the_invoice->is_created();
	}
}

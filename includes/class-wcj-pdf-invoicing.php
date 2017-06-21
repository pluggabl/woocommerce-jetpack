<?php
/**
 * Booster for WooCommerce - Module - PDF Invoicing
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_PDF_Invoicing' ) ) :

class WCJ_PDF_Invoicing extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id            = 'pdf_invoicing';
		$this->short_desc    = __( 'PDF Invoicing', 'woocommerce-jetpack' );
		$this->section_title = __( 'General', 'woocommerce-jetpack' );
		$this->desc          = __( 'WooCommerce Invoices, Proforma Invoices, Credit Notes and Packing Slips.', 'woocommerce-jetpack' );
		$this->link_slug     = 'woocommerce-pdf-invoicing-and-packing-slips';
		parent::__construct();

		$this->add_tools( array(
			'renumerate_invoices' => array(
				'title' => __( 'Invoices Renumerate', 'woocommerce-jetpack' ),
				'desc'  => __( 'Tool renumerates all invoices, proforma invoices, credit notes and packing slips.', 'woocommerce-jetpack' ),
			),
			'invoices_report' => array(
				'title' => __( 'Invoices Report', 'woocommerce-jetpack' ),
				'desc'  => __( 'Invoices Monthly Reports.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {

			add_action( 'init', array( $this, 'catch_args' ) );
			add_action( 'init', array( $this, 'generate_pdf_on_init' ) );

			// Bulk actions
			add_action( 'admin_footer-edit.php',  array( $this, 'bulk_actions_pdfs_admin_footer' ) );
			add_action( 'load-edit.php',          array( $this, 'bulk_actions_pdfs' ) );
			add_action( 'admin_notices',          array( $this, 'bulk_actions_pdfs_notices' ) );

			$this->the_pdf_invoicing_report_tool = include_once( 'pdf-invoices/class-wcj-pdf-invoicing-report-tool.php' );

			$invoice_types = wcj_get_enabled_invoice_types();
			foreach ( $invoice_types as $invoice_type ) {
				$the_hook = get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_create_on', 'woocommerce_new_order' );
				if ( 'disabled' != $the_hook && 'manual' != $the_hook && '' != $the_hook ) {
					add_action( $the_hook, array( $this, 'create_document_hook' ) );
					if ( 'woocommerce_new_order' === $the_hook ) {
						add_action( 'woocommerce_api_create_order',         array( $this, 'create_document_hook' ) );
						add_action( 'woocommerce_cli_create_order',         array( $this, 'create_document_hook' ) );
						add_action( 'kco_before_confirm_order',             array( $this, 'create_document_hook' ) );
						add_action( 'woocommerce_checkout_order_processed', array( $this, 'create_document_hook' ) );
					}
				}
			}
		}
	}

	/**
	 * Add extra bulk action options to generate/download documents
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
	 *
	 * @todo    add "print" action
	 * @see     https://www.skyverge.com/blog/add-custom-bulk-action/
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function bulk_actions_pdfs_admin_footer() {
		global $post_type;
		if ( 'shop_order' == $post_type ) {
			?><script type="text/javascript"><?php
			$invoice_types = wcj_get_enabled_invoice_types();
			foreach ( $invoice_types as $invoice_type ) {
				$key   = $invoice_type['id'];
				$title = $invoice_type['title'];
				?>jQuery(function() {
					jQuery('<option>').val('generate_<?php echo $key; ?>').text('<?php echo __( 'Generate', 'woocommerce-jetpack' ) . ' ' . $title; ?>').appendTo('select[name="action"]');
					jQuery('<option>').val('generate_<?php echo $key; ?>').text('<?php echo __( 'Generate', 'woocommerce-jetpack' ) . ' ' . $title; ?>').appendTo('select[name="action2"]');
				});<?php
			}
			foreach ( $invoice_types as $invoice_type ) {
				$key   = $invoice_type['id'];
				$title = $invoice_type['title'];
				?>jQuery(function() {
					jQuery('<option>').val('download_<?php echo $key; ?>').text('<?php echo __( 'Download', 'woocommerce-jetpack' ) . ' ' . $title; ?>').appendTo('select[name="action"]');
					jQuery('<option>').val('download_<?php echo $key; ?>').text('<?php echo __( 'Download', 'woocommerce-jetpack' ) . ' ' . $title; ?>').appendTo('select[name="action2"]');
				});<?php
			}
			?></script><?php
		}
	}

	/**
	 * bulk_actions_pdfs_notices.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function bulk_actions_pdfs_notices() {
		global $post_type, $pagenow;
		if ( $pagenow == 'edit.php' && 'shop_order' == $post_type && isset( $_REQUEST['generated'] ) && (int) $_REQUEST['generated'] ) {
			$message = sprintf( _n( 'Document generated.', '%s documents generated.', $_REQUEST['generated'] ), number_format_i18n( $_REQUEST['generated'] ) );
			echo "<div class='updated'><p>{$message}</p></div>";
		}
	}

	/**
	 * bulk_actions_pdfs.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function bulk_actions_pdfs() {

		// Get the action
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action = $wp_list_table->current_action();

		// TODO: Security check
//		check_admin_referer('bulk-posts');

		// Validate the action
		$action_exploded = explode( '_', $action, 2 );
		if ( empty( $action_exploded ) || ! is_array( $action_exploded ) || 2 !== count( $action_exploded ) || ! in_array( $action_exploded[0], array( 'generate', 'download' ) ) ) {
			return;
		}
		// Perform the action
		$post_ids   = $_GET['post'];
		$the_action = $action_exploded[0];
		$the_type   = $action_exploded[1];
		switch( $the_action ) {
			case 'generate':
				// TODO: Validate user permissions/capabilities
//				if ( ! current_user_can( $post_type_object->cap->export_post, $post_id ) ) {
//					wp_die( __( 'You are not allowed to export this post.' ) );
//				}
				$generated = 0;
				foreach( $post_ids as $post_id ) {
					if ( $this->create_document( $post_id, $the_type ) ) {
						$generated++;
					}
				}
				// Build the redirect url
				$sendback = add_query_arg(
					array(
						'post_type'              => 'shop_order',
						'generated'              => $generated,
						'generated_type'         => $the_type,
						'generated_' . $the_type => 1,
						'ids'                    => join( ',', $post_ids ),
						'post_status'            => $_GET['post_status'],
					),
					$sendback
				);
				break;
			case 'download':
				$this->get_invoices_zip( $the_type, $post_ids );
				exit();
			default:
				return;
		}

		// Redirect client
		wp_redirect( esc_url_raw( $sendback ) );
		exit();
	}

	/**
	 * get_invoices_zip.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_invoices_zip( $invoice_type_id, $post_ids ) {

		$zip = new ZipArchive();
		$zip_file_name = $invoice_type_id . '.zip';
		$zip_file_path = sys_get_temp_dir() . '/' . $zip_file_name;
		if ( file_exists( $zip_file_path ) ) {
			unlink ( $zip_file_path );
		}
		if ( $zip->open( $zip_file_path, ZipArchive::CREATE ) !== TRUE ) {
			return false;
		}
		foreach( $post_ids as $post_id ) {
			if ( wcj_is_invoice_created( $post_id, $invoice_type_id ) ) {
				$the_invoice = wcj_get_pdf_invoice( $post_id, $invoice_type_id );
				$file_name = $the_invoice->get_pdf( 'F' );
				$zip->addFile( $file_name, $the_invoice->get_file_name() );
			}
		}
		$zip->close();

		header( "Content-Type: application/octet-stream" );
		header( "Content-Disposition: attachment; filename=" . urlencode( $zip_file_name ) );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Type: application/download" );
		header( "Content-Description: File Transfer" );
		header( "Content-Length: " . filesize( $zip_file_path ) );
		flush(); // this doesn't really matter.
		if ( false !== ( $fp = fopen( $zip_file_path, "r" ) ) ) {
			while ( ! feof( $fp ) ) {
				echo fread( $fp, 65536 );
				flush(); // this is essential for large downloads
			}
			fclose( $fp );
		} else {
			die( __( 'Unexpected error', 'woocommerce-jetpack' ) );
		}
		return true;
	}

	/**
	 * create_invoices_report_tool.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function create_invoices_report_tool() {
		return $this->the_pdf_invoicing_report_tool->create_invoices_report_tool();
	}

	/**
	 * create_renumerate_invoices_tool.
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function create_renumerate_invoices_tool() {
		$the_tool = include_once( 'pdf-invoices/class-wcj-pdf-invoicing-renumerate-tool.php' );
		return $the_tool->create_renumerate_invoices_tool();
	}

	/**
	 * create_document_hook.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function create_document_hook( $order_id ) {
		$current_filter = current_filter();
		if ( in_array( $current_filter, array( 'woocommerce_api_create_order', 'woocommerce_cli_create_order', 'kco_before_confirm_order', 'woocommerce_checkout_order_processed', ) ) ) {
			$current_filter = 'woocommerce_new_order';
		}
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			$the_hook = get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_create_on', 'woocommerce_new_order' );
			if ( 'disabled' != $the_hook && 'manual' != $the_hook && '' != $the_hook ) {
				if ( $current_filter === $the_hook ) {
					$this->create_document( $order_id, $invoice_type['id'] );
				}
			}
		}
	}

	/**
	 * create_document.
	 *
	 * @version 2.5.7
	 */
	function create_document( $order_id, $invoice_type ) {
		if ( false == wcj_is_invoice_created( $order_id, $invoice_type ) ) {
			wcj_create_invoice( $order_id, $invoice_type );
			return true;
		}
		return false;
	}

	/**
	 * delete_document.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function delete_document( $order_id, $invoice_type ) {
		if ( true == wcj_is_invoice_created( $order_id, $invoice_type ) ) {
			wcj_delete_invoice( $order_id, $invoice_type );
		}
	}

	/**
	 * catch_args.
	 *
	 * @version 2.5.0
	 */
	function catch_args() {
		$this->order_id        = ( isset( $_GET['order_id'] ) )                                             ? $_GET['order_id'] : 0;
		$this->invoice_type_id = ( isset( $_GET['invoice_type_id'] ) )                                      ? $_GET['invoice_type_id'] : '';
		$this->save_as_pdf     = ( isset( $_GET['save_pdf_invoice'] ) && '1' == $_GET['save_pdf_invoice'] ) ? true : false;
		$this->get_invoice     = ( isset( $_GET['get_invoice'] ) && '1' == $_GET['get_invoice'] )           ? true : false;

		if ( isset( $_GET['create_invoice_for_order_id'] ) && ( wcj_is_user_role( 'administrator' ) || is_shop_manager() ) ) {
			$this->create_document( $_GET['create_invoice_for_order_id'], $this->invoice_type_id );
		}
		if ( isset( $_GET['delete_invoice_for_order_id'] ) && ( wcj_is_user_role( 'administrator' ) || is_shop_manager() ) ) {
			$this->delete_document( $_GET['delete_invoice_for_order_id'], $this->invoice_type_id );
		}
	}

	/**
	 * check_user_roles.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    apply user role checking to other actions - create etc.
	 * @todo    check if `current_user_can( 'administrator' )` is the same as checking role directly
	 */
	function check_user_roles() {
		if ( get_current_user_id() == intval( get_post_meta( $this->order_id, '_customer_user', true ) ) ) {
			return true;
		}
		$allowed_user_roles = get_option( 'wcj_invoicing_' . $this->invoice_type_id . '_roles', array( 'administrator', 'shop_manager' ) );
		if ( empty( $allowed_user_roles ) ) {
			$allowed_user_roles = array( 'administrator' );
		}
		if ( wcj_is_user_role( $allowed_user_roles ) ) {
			return true;
		} else {
			add_action( 'admin_notices', array( $this, 'wrong_user_role_notice' ) );
			return false;
		}
	}

	/**
	 * wrong_user_role_notice.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wrong_user_role_notice() {
		echo '<div class="notice notice-error is-dismissible"><p>' . __( 'You are not allowed to view the invoice.', 'woocommerce-jetpack' ) . '</p></div>';
	}

	/**
	 * generate_pdf_on_init.
	 *
	 * @version 2.9.0
	 */
	function generate_pdf_on_init() {

		// Check if all is OK
		if ( true !== $this->get_invoice || 0 == $this->order_id || ! is_user_logged_in() || ! $this->check_user_roles() ) {
			return;
		}

		$the_invoice = wcj_get_pdf_invoice( $this->order_id, $this->invoice_type_id );
		$dest = ( true === $this->save_as_pdf ) ? 'D' : 'I';
		$the_invoice->get_pdf( $dest );
		die();
	}

}

endif;

return new WCJ_PDF_Invoicing();

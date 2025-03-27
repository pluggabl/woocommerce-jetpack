<?php
/**
 * Booster for WooCommerce - Module - PDF Invoicing
 *
 * @version 7.2.5
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_PDF_Invoicing' ) ) :
	/**
	 * WCJ_PDF_Invoicing.
	 */
	class WCJ_PDF_Invoicing extends WCJ_Module {

		/**
		 * The module order_id
		 *
		 * @var varchar $order_id Module.
		 */
		public $order_id;
		/**
		 * The module get_invoice
		 *
		 * @var varchar $get_invoice Module.
		 */
		public $get_invoice;
		/**
		 * The module save_as_pdf
		 *
		 * @var varchar $save_as_pdf Module.
		 */
		public $save_as_pdf;
		/**
		 * The module invoice_type_id
		 *
		 * @var varchar $invoice_type_id Module.
		 */
		public $invoice_type_id;
		/**
		 * The module section_title
		 *
		 * @var varchar $section_title Module.
		 */
		public $section_title;
		/**
		 * The module the_pdf_invoicing_report_tool
		 *
		 * @var varchar $the_pdf_invoicing_report_tool Module.
		 */
		public $the_pdf_invoicing_report_tool;
		/**
		 * Constructor.
		 *
		 * @version 7.2.5
		 */
		public function __construct() {

			$this->id            = 'pdf_invoicing';
			$this->short_desc    = __( 'PDF Invoicing', 'woocommerce-jetpack' );
			$this->section_title = __( 'General', 'woocommerce-jetpack' );
			$this->desc          = __( 'Invoices, Proforma Invoices (Plus), Credit Notes (Plus) and Packing Slips (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro      = __( 'Invoices, Proforma Invoices, Credit Notes and Packing Slips.', 'woocommerce-jetpack' );
			$this->link_slug     = 'woocommerce-pdf-invoicing-and-packing-slips';
			parent::__construct();

			$this->add_tools(
				array(
					'renumerate_invoices' => array(
						'title' => __( 'Invoices Renumerate', 'woocommerce-jetpack' ),
						'desc'  => __( 'Tool renumerates all invoices, proforma invoices, credit notes and packing slips.', 'woocommerce-jetpack' ),
					),
					'invoices_report'     => array(
						'title' => __( 'Invoices Report', 'woocommerce-jetpack' ),
						'desc'  => __( 'Invoices Monthly Reports.', 'woocommerce-jetpack' ),
					),
				)
			);

			if ( $this->is_enabled() ) {
				if ( 'init' === current_filter() ) {
					$this->catch_args();
					$this->generate_pdf_on_init();
				} else {
					add_action( 'init', array( $this, 'catch_args' ) );
					add_action( 'init', array( $this, 'generate_pdf_on_init' ) );
				}

				// Bulk actions.
				add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_actions_register' ) );
				add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'bulk_actions_register' ) );
				add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'bulk_actions_handle' ), 10, 3 );
				add_action( 'all_admin_notices', array( $this, 'bulk_actions_pdfs_notices' ) ); // Ensure it runs on HPOS screens.
				add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'bulk_actions_handle' ), 10, 3 );

				$this->the_pdf_invoicing_report_tool = include_once 'pdf-invoices/class-wcj-pdf-invoicing-report-tool.php';

				$invoice_types = wcj_get_enabled_invoice_types();
				foreach ( $invoice_types as $invoice_type ) {
					$the_hooks = wcj_get_invoice_create_on( $invoice_type['id'] );
					foreach ( $the_hooks as $the_hook ) {
						if ( 'manual' !== $the_hook ) {
							add_action( $the_hook, array( $this, 'create_document_hook' ) );
							if ( 'woocommerce_new_order' === $the_hook ) {
								add_action( 'woocommerce_api_create_order', array( $this, 'create_document_hook' ) );
								add_action( 'woocommerce_cli_create_order', array( $this, 'create_document_hook' ) );
								add_action( 'kco_before_confirm_order', array( $this, 'create_document_hook' ) );
								add_action( 'woocommerce_checkout_order_processed', array( $this, 'create_document_hook' ) );
							}
						}
					}
				}

				// Editable numbers in meta box.
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_add_order_meta_box_numbering', 'yes' ) ) {
					if ( true === wcj_is_hpos_enabled() ) {
						add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box_hpos' ), PHP_INT_MAX, 2 );
					} else {
						add_action( 'save_post_shop_order', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
					}
				}
			}
		}

		/**
		 * Adds extra bulk action options to generate/download documents.
		 *
		 * @version 4.3.0
		 * @since   2.5.7
		 *
		 * @param string | array $actions defines the actions.
		 *
		 * @return array
		 */
		public function bulk_actions_register( $actions ) {
			$invoice_types      = wcj_get_enabled_invoice_types();
			$new_actions_source = array(
				'generate' => __( 'Generate', 'woocommerce-jetpack' ),
				'download' => __( 'Download (Zip)', 'woocommerce-jetpack' ),
				'merge'    => __( 'Merge (Print)', 'woocommerce-jetpack' ),
			);
			$new_actions        = array();
			foreach ( $new_actions_source as $source_key => $source_value ) {
				foreach ( $invoice_types as $type_key => $type_value ) {
					$new_actions[ $source_key . '_' . $type_value['id'] ] = $source_value . ' ' . $type_value['title'];
				}
			}
			$actions = array_merge( $actions, $new_actions );
			return $actions;
		}

		/**
		 * Bulk_actions_pdfs_notices.
		 *
		 * @version 7.2.5
		 * @since   2.5.7
		 */
		public function bulk_actions_pdfs_notices() {
			global $pagenow, $typenow;

			$current_screen = get_current_screen();

			// Check for both Classic and HPOS screens.
			$is_shop_order_screen = (
				( 'edit.php' === $pagenow && 'shop_order' === $typenow ) ||
				( 'woocommerce_page_wc-orders' === $current_screen->id )
			);

			if ( ! $is_shop_order_screen ) {
				return;
			}

			$wpnonce = isset( $_REQUEST['pdf-generated-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['pdf-generated-nonce'] ), 'pdf-generated' ) : false;
			if ( $wpnonce && isset( $_REQUEST['generated'] ) && (int) $_REQUEST['generated'] ) {
				/* translators: %s: translation added */
				$message = sprintf( wp_kses_post( 'Document generated.', '%s documents generated.', sanitize_text_field( wp_unslash( $_REQUEST['generated'] ) ) ), number_format_i18n( sanitize_text_field( wp_unslash( $_REQUEST['generated'] ) ) ) );

				echo wp_kses_post( "<div class='updated'><p>{$message}</p></div>" );
			}
			if ( isset( $_GET['wcj_notice'] ) ) {
				switch ( $_GET['wcj_notice'] ) {
					case 'ziparchive_class_missing':
						echo '<div class="notice notice-error"><p><strong>' .
						/* translators: %s: translation added */
						sprintf(
							wp_kses_post( 'Booster: %s class is not accessible on your server. Please contact your hosting provider.', 'woocommerce-jetpack' ),
							'<a target="_blank" href="http://php.net/manual/en/class.ziparchive.php">PHP ZipArchive</a>'
						) .
						'</strong></p></div>';
						break;
					case 'ziparchive_error':
						echo '<div class="notice notice-error"><p>' .
						wp_kses_post( 'Booster: ZipArchive error.', 'woocommerce-jetpack' ) .
						'</p></div>';
						break;
					case 'merge_pdfs_no_files':
						echo '<div class="notice notice-error"><p>' .
						wp_kses_post( 'Booster: Merge PDFs: No files.', 'woocommerce-jetpack' ) .
						'</p></div>';
						break;
					case 'merge_pdfs_php_version':
						echo '<div class="notice notice-error"><p>' .
						sprintf( wp_kses_post( 'Booster: Merge PDFs: Command requires PHP version 5.3.0 at least. You have PHP version %s installed.', 'woocommerce-jetpack' ), PHP_VERSION ) .
						'</p></div>';
						break;
					default:
						echo '<div class="notice notice-error"><p>' .
						/* translators: %s: translation added */
						sprintf( wp_kses_post( __( 'Booster: %s.', 'woocommerce-jetpack' ), '<code>' . sanitize_text_field( wp_unslash( $_GET['wcj_notice'] ) ) . '</code>' ) ) .
						'</p></div>';
						break;
				}
			}
		}

		/**
		 * Processes the PDF bulk actions.
		 *
		 * @version 7.2.5
		 * @since   2.5.7
		 * @todo    on `generate` (and maybe other actions) validate user permissions/capabilities - `if ( ! current_user_can( $post_type_object->cap->export_post, $post_id ) ) { wp_die( __( 'You are not allowed to export this post.' ) ); }`
		 *
		 * @param string | array $redirect_to defines the redirect_to.
		 * @param string | array $action defines the action.
		 * @param array          $post_ids defines the post_ids.
		 *
		 * @return string
		 */
		public function bulk_actions_handle( $redirect_to, $action, $post_ids ) {
			if (
			false === preg_match( '(generate|download|merge)', $action ) ||
			false === preg_match( '(invoice|packing_slip|credit_note)', $action )
			) {
				return $redirect_to;
			}

			// OPTIONAL: Sanity check for classic orders screen only.
			$screen = get_current_screen();
			if ( 'edit-shop_order' === $screen->id ) {
				check_admin_referer( 'bulk-posts' );
			}

			// Ensure we have a valid redirect.
			if ( empty( $redirect_to ) ) {
				$redirect_to = admin_url( 'edit.php?post_type=shop_order' );
			}

			// Validate the action.
			$action_exploded = explode( '_', $action, 2 );

			// Perform the action.
			$the_action = $action_exploded[0];
			$the_type   = $action_exploded[1];

			switch ( $the_action ) {
				case 'generate':
					$generated = 0;

					if ( 'yes' === get_option( 'wcj_invoicing_desc_order_sequence' ) ) {

						$invoice_num = array_reverse( $post_ids );
						foreach ( $invoice_num as $post_id ) {
							if ( $this->create_document( $post_id, $the_type ) ) {
								$generated++;
							}
						}
						// Build the redirect url.
						$redirect_to = esc_url_raw(
							add_query_arg(
								array(
									'generated'           => $generated,
									'generated_type'      => $the_type,
									'generated_' . $the_type => 1,
									'ids'                 => join( ',', $invoice_num ),
									'post_status'         => isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '',
									'pdf-generated-nonce' => wp_create_nonce( 'pdf-generated' ),
								),
								$redirect_to
							)
						);
					}

					foreach ( $post_ids as $post_id ) {
						if ( $this->create_document( $post_id, $the_type ) ) {
							$generated++;
						}
					}
					// Build the redirect url.
					$redirect_to = esc_url_raw(
						add_query_arg(
							array(
								'generated'              => $generated,
								'generated_type'         => $the_type,
								'generated_' . $the_type => 1,
								'ids'                    => join( ',', $post_ids ),
								'post_status'            => isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '',
								'pdf-generated-nonce'    => wp_create_nonce( 'pdf-generated' ),
							),
							$redirect_to
						)
					);
					break;
				case 'download':
					$result = $this->get_invoices_zip( $the_type, $post_ids );
					if ( '' !== ( $result ) ) {
						// Build the redirect url.
						$redirect_to = esc_url(
							add_query_arg(
								array(
									'post_status' => isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '',
									'wcj_notice'  => $result,
								),
								$redirect_to
							)
						);
					}
					break;
				case 'merge':
					$merge_ids = array();
					foreach ( $post_ids as $post_id ) {
						if ( wcj_is_invoice_created( $post_id, $the_type ) ) {
							$merge_ids[] = $post_id;
						}
					}
					if ( ! empty( $merge_ids ) ) {
						$result = $this->merge_pdfs( $the_type, $merge_ids );
						if ( '' !== ( $result ) ) {
							// Build the redirect url.
							$redirect_to = esc_url(
								add_query_arg(
									array(
										'post_status' => isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '',
										'wcj_notice'  => $result,
									),
									$redirect_to
								)
							);
						}
					}
					break;
				default:
					return $redirect_to;
			}
			return $redirect_to;
		}

		/**
		 * Merge_pdfs.
		 *
		 * @version 3.5.2
		 * @since   3.5.0
		 * @see     https://www.setasign.com/products/fpdi/demos/concatenate-fake/
		 * @todo    rethink filename (i.e. 'docs.pdf')
		 * @todo    (maybe) always save/download instead of display on output
		 * @param  int   $invoice_type_id defines the invoice_type_id.
		 * @param array $post_ids defines the post_ids.
		 */
		public function merge_pdfs( $invoice_type_id, $post_ids ) {
			if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
				return 'merge_pdfs_php_version';
			}
			$files = array();
			foreach ( $post_ids as $post_id ) {
				$the_invoice = wcj_get_pdf_invoice( $post_id, $invoice_type_id );
				$files[]     = $the_invoice->get_pdf( 'F' );
			}
			if ( empty( $files ) ) {
				return 'merge_pdfs_no_files';
			}
			require_once wcj_free_plugin_path() . '/includes/lib/FPDI/src/autoload.php';
			$fpdi_pdf = require_once wcj_free_plugin_path() . '/includes/pdf-invoices/tcpdffpdi.php';
			$fpdi_pdf->SetTitle( 'docs.pdf' );
			$fpdi_pdf->setPrintHeader( false );
			$fpdi_pdf->setPrintFooter( false );
			foreach ( $files as $file ) {
				$page_count = $fpdi_pdf->setSourceFile( $file );
				for ( $page_nr = 1; $page_nr <= $page_count; $page_nr++ ) {
					$page_id = $fpdi_pdf->ImportPage( $page_nr );
					$s       = $fpdi_pdf->getTemplatesize( $page_id );
					$fpdi_pdf->AddPage( $s['orientation'], $s );
					$fpdi_pdf->useImportedPage( $page_id );
				}
			}
			$fpdi_pdf->Output( 'docs.pdf', ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type_id . '_save_as_enabled', 'no' ) ? 'D' : 'I' ) );
			die();
		}

		/**
		 * Get_invoices_zip.
		 *
		 * @version 6.0.0
		 * @since   2.5.7
		 * @todo    (maybe) add timestamp to filename
		 * @todo    add `ZipArchive` fallback
		 * @param  int   $invoice_type_id defines the invoice_type_id.
		 * @param array $post_ids defines the post_ids.
		 */
		public function get_invoices_zip( $invoice_type_id, $post_ids ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return 'ziparchive_class_missing';
			}
			// Creating Zip.
			$zip           = new ZipArchive();
			$zip_file_name = $invoice_type_id . '-' .
			sanitize_title( str_replace( array( 'http://', 'https://' ), '', site_url() ) ) . '-' .
			min( $post_ids ) . '-' . max( $post_ids ) .
			'.zip';
			$zip_file_path = wcj_get_invoicing_temp_dir() . '/' . $zip_file_name;
			if ( file_exists( $zip_file_path ) ) {
				unlink( $zip_file_path );
			}
			if ( $zip->open( $zip_file_path, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE ) !== true ) {
				return 'ziparchive_error';
			}
			foreach ( $post_ids as $post_id ) {
				if ( wcj_is_invoice_created( $post_id, $invoice_type_id ) ) {
					$the_invoice = wcj_get_pdf_invoice( $post_id, $invoice_type_id );
					$file_name   = $the_invoice->get_pdf( 'F' );
					$zip->addFile( $file_name, $the_invoice->get_file_name() );
				}
			}
			$zip->close();
			// Sending Zip.
			WC_Download_Handler::download_file_force( $zip_file_path, $zip_file_name );
			return '';
		}

		/**
		 * Create_invoices_report_tool.
		 *
		 * @version 2.3.10
		 * @since   2.3.10
		 */
		public function create_invoices_report_tool() {
			return $this->the_pdf_invoicing_report_tool->create_invoices_report_tool();
		}

		/**
		 * Create_renumerate_invoices_tool.
		 *
		 * @version 2.3.10
		 * @since   2.3.10
		 */
		public function create_renumerate_invoices_tool() {
			$the_tool = include_once 'pdf-invoices/class-wcj-pdf-invoicing-renumerate-tool.php';
			return $the_tool->create_renumerate_invoices_tool();
		}

		/**
		 * Create_document_hook.
		 *
		 * @version 3.2.0
		 * @since   2.7.0
		 * @param  int $order_id defines the order_id.
		 */
		public function create_document_hook( $order_id ) {
			$current_filter = current_filter();
			if ( in_array(
				$current_filter,
				array( 'woocommerce_api_create_order', 'woocommerce_cli_create_order', 'kco_before_confirm_order', 'woocommerce_checkout_order_processed' ),
				true
			)
			) {
				$current_filter = 'woocommerce_new_order';
			}
			$invoice_types = wcj_get_enabled_invoice_types();
			foreach ( $invoice_types as $invoice_type ) {
				$the_hooks = wcj_get_invoice_create_on( $invoice_type['id'] );
				foreach ( $the_hooks as $the_hook ) {
					if ( 'manual' !== $the_hook ) {
						if ( $current_filter === $the_hook ) {
							$this->create_document( $order_id, $invoice_type['id'] );
						}
					}
				}
			}
		}

		/**
		 * Create_document.
		 *
		 * @version 2.5.7
		 * @param  int    $order_id defines the order_id.
		 * @param  string $invoice_type defines the invoice_type.
		 */
		public function create_document( $order_id, $invoice_type ) {
			if ( false === wcj_is_invoice_created( $order_id, $invoice_type ) ) {
				wcj_create_invoice( $order_id, $invoice_type );
				return true;
			}
			return false;
		}

		/**
		 * Delete_document.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 * @param  int    $order_id defines the order_id.
		 * @param  string $invoice_type defines the invoice_type.
		 */
		public function delete_document( $order_id, $invoice_type ) {
			if ( true === wcj_is_invoice_created( $order_id, $invoice_type ) ) {
				wcj_delete_invoice( $order_id, $invoice_type );
			}
		}

		/**
		 * Catch_args.
		 *
		 * @version 5.6.7
		 */
		public function catch_args() {
			$create_invoice_for_order_id_wpnonce = isset( $_REQUEST['create_invoice_for_order_id-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['create_invoice_for_order_id-nonce'] ), 'create_invoice_for_order_id' ) : false;
			$delete_invoice_for_order_id_wpnonce = isset( $_REQUEST['delete_invoice_for_order_id-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['delete_invoice_for_order_id-nonce'] ), 'delete_invoice_for_order_id' ) : false;

			$this->order_id        = ( isset( $_GET['order_id'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) : 0;
			$this->invoice_type_id = ( isset( $_GET['invoice_type_id'] ) ) ? sanitize_text_field( wp_unslash( $_GET['invoice_type_id'] ) ) : '';
			$this->save_as_pdf     = ( isset( $_GET['save_pdf_invoice'] ) && '1' === $_GET['save_pdf_invoice'] );
			$this->get_invoice     = ( isset( $_GET['get_invoice'] ) && '1' === $_GET['get_invoice'] );

			if ( $create_invoice_for_order_id_wpnonce && isset( $_GET['create_invoice_for_order_id'] ) && $this->check_user_roles( false ) ) {
				$this->create_document( sanitize_text_field( wp_unslash( $_GET['create_invoice_for_order_id'] ) ), $this->invoice_type_id );
			}
			if ( $delete_invoice_for_order_id_wpnonce && isset( $_GET['delete_invoice_for_order_id'] ) && $this->check_user_roles( false ) ) {
				$this->delete_document( sanitize_text_field( wp_unslash( $_GET['delete_invoice_for_order_id'] ) ), $this->invoice_type_id );
			}
		}

		/**
		 * Check_user_roles.
		 *
		 * @version 6.0.1
		 * @since   2.9.0
		 * @todo    check if `current_user_can( 'administrator' )` is the same as checking role directly
		 * @param  bool $allow_order_owner defines the allow_order_owner.
		 */
		public function check_user_roles( $allow_order_owner = true ) {
			if ( is_user_logged_in() && $allow_order_owner && get_current_user_id() === intval( get_post_meta( $this->order_id, '_customer_user', true ) ) ) {
				return true;
			}
			$allowed_user_roles = wcj_get_option( 'wcj_invoicing_' . $this->invoice_type_id . '_roles', array( 'administrator', 'shop_manager' ) );
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
		 * Wrong_user_role_notice.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 */
		public function wrong_user_role_notice() {
			echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post( 'You are not allowed to view the invoice.', 'woocommerce-jetpack' ) . '</p></div>';
		}

		/**
		 * Generate_pdf_on_init.
		 *
		 * @version 7.0.0
		 */
		public function generate_pdf_on_init() {
			// Check if all is OK.
			if ( true !== $this->get_invoice || 0 === $this->order_id || ! $this->check_user_roles() ) {
				return;
			}
			// Get PDF.
			$the_invoice = wcj_get_pdf_invoice( $this->order_id, $this->invoice_type_id );
			$dest        = ( '1' === $this->save_as_pdf || true === $this->save_as_pdf ? 'D' : 'I' );
			if ( 'yes' === wcj_get_option( 'wcj_general_advanced_disable_output_buffer', 'no' ) ) {
				ob_clean();
				ob_flush();
				$the_invoice->get_pdf( $dest );
				ob_end_flush();
				if ( ob_get_contents() ) {
					ob_end_clean();
				}
			} else {
				$the_invoice->get_pdf( $dest );
			}
			die();
		}

	}

endif;

return new WCJ_PDF_Invoicing();

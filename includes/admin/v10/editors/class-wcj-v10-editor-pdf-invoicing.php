<?php
/**
 * Booster True V10 - Guided Editor: PDF Invoicing & Packing Slips.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_PDF_Invoicing' ) ) :

	/**
	 * PDF Invoicing guided editor supporting multiple document types and Tier-2 rules.
	 */
	class WCJ_V10_Editor_PDF_Invoicing {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'pdf_invoicing';
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'PDF Invoicing & Packing Slips', 'woocommerce-jetpack' ),
				'description' => __( 'Brand your invoices with logo, colors, business details, and live PDF preview.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Document Preview', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'pdf_invoicing'           => self::slug(),
				'pdf_invoicing_numbering' => self::slug(),
				'pdf_invoicing_header'    => self::slug(),
				'pdf_invoicing_footer'    => self::slug(),
				'pdf_invoicing_templates' => self::slug(),
				'pdf_invoicing_styling'   => self::slug(),
				'pdf_invoicing_page'      => self::slug(),
				'pdf_invoicing_emails'    => self::slug(),
				'pdf_invoicing_display'   => self::slug(),
				'pdf_invoicing_advanced'  => self::slug(),
			);
		}

		/**
		 * Supported document types map.
		 *
		 * @return array<string, string>
		 */
		public static function doc_types() {
			return array(
				'invoice'          => __( 'Invoice', 'woocommerce-jetpack' ),
				'proforma_invoice' => __( 'Proforma Invoice', 'woocommerce-jetpack' ),
				'packing_slip'     => __( 'Packing Slip', 'woocommerce-jetpack' ),
				'credit_note'      => __( 'Credit Note', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Normalize requested document type slug.
		 *
		 * @param mixed $raw Raw doc_type.
		 * @return string
		 */
		private static function normalize_doc_type( $raw ) {
			$doc   = sanitize_key( (string) $raw );
			$valid = self::doc_types();
			return isset( $valid[ $doc ] ) ? $doc : 'invoice';
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'              => __( 'PDF Invoicing Module', 'woocommerce-jetpack' ),
				'header_enabled'       => __( 'Invoice Header', 'woocommerce-jetpack' ),
				'header_image'         => __( 'Header Logo URL', 'woocommerce-jetpack' ),
				'header_title'         => __( 'Document Title', 'woocommerce-jetpack' ),
				'header_text'          => __( 'Business Header Text', 'woocommerce-jetpack' ),
				'header_text_color'    => __( 'Header Text Color', 'woocommerce-jetpack' ),
				'header_line_color'    => __( 'Header Line Color', 'woocommerce-jetpack' ),
				'numbering_prefix'     => __( 'Document Prefix', 'woocommerce-jetpack' ),
				'numbering_suffix'     => __( 'Document Suffix', 'woocommerce-jetpack' ),
				'footer_text'          => __( 'Document Footer Text', 'woocommerce-jetpack' ),
				'create_on_completed'  => __( 'Auto-Create on Completed Order', 'woocommerce-jetpack' ),
				'create_on_processing' => __( 'Auto-Create on Processing Order', 'woocommerce-jetpack' ),
				'attach_completed'     => __( 'Attach to Completed Email', 'woocommerce-jetpack' ),
				'customer_my_account'  => __( 'Show Download in My Account', 'woocommerce-jetpack' ),
				'thankyou_page'        => __( 'Show Download on Thank You Page', 'woocommerce-jetpack' ),
				'skip_zero_total'      => __( 'Skip $0 / Free Orders', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Load live state, metadata, and compatibility diagnostics.
		 *
		 * @param array $context   Request params (doc_type).
		 * @param array $store_ctx Store context from WCJ_V10_REST::get_store_sample_data().
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			$nonce     = wp_create_nonce( 'wcj-cat-nonce' );
			$doc_types = self::doc_types();
			$doc_type  = self::normalize_doc_type( isset( $context['doc_type'] ) ? $context['doc_type'] : 'invoice' );
			$prefix    = 'wcj_invoicing_' . $doc_type . '_';

			$emails        = (array) get_option( $prefix . 'attach_to_emails', array() );
			$create_on     = (array) get_option( $prefix . 'create_on', array() );
			$default_title = isset( $doc_types[ $doc_type ] ) ? $doc_types[ $doc_type ] : __( 'Invoice', 'woocommerce-jetpack' );

			$diagnostics = array(
				array(
					'level' => 'info',
					'text'  => __( 'Documents generate on WooCommerce orders. Pick any Document Type (Invoice, Packing Slip, Proforma, Credit Note) above to customize independently.', 'woocommerce-jetpack' ),
				),
			);

			$doc_list = array();
			foreach ( $doc_types as $id => $title ) {
				$doc_list[] = array(
					'id'    => $id,
					'title' => $title,
				);
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'enabled'              => 'yes' === get_option( 'wcj_pdf_invoicing_enabled', 'no' ),
					'doc_type'             => $doc_type,
					'header_enabled'       => 'yes' === get_option( $prefix . 'header_enabled', 'yes' ),
					'header_image'         => (string) get_option( $prefix . 'header_image', '' ),
					'header_title'         => (string) get_option( $prefix . 'header_title_text', $default_title ),
					'header_text'          => (string) get_option( $prefix . 'header_text', __( 'Company Name', 'woocommerce-jetpack' ) ),
					'header_text_color'    => (string) get_option( $prefix . 'header_text_color', '#cccccc' ),
					'header_line_color'    => (string) get_option( $prefix . 'header_line_color', '#cccccc' ),
					'numbering_prefix'     => (string) get_option( $prefix . 'numbering_prefix', '' ),
					'numbering_suffix'     => (string) get_option( $prefix . 'numbering_suffix', '' ),
					'footer_text'          => (string) get_option( $prefix . 'footer_text', '' ),
					'create_on_completed'  => in_array( 'woocommerce_order_status_completed', $create_on, true ),
					'create_on_processing' => in_array( 'woocommerce_order_status_processing', $create_on, true ),
					'attach_completed'     => in_array( 'customer_completed_order', $emails, true ),
					'customer_my_account'  => 'yes' === get_option( $prefix . 'enabled_for_customers', 'no' ),
					'thankyou_page'        => 'yes' === get_option( $prefix . 'enabled_on_thankyou_page', 'no' ),
					'skip_zero_total'      => 'yes' === get_option( $prefix . 'skip_zero_total', 'no' ),
				),
				'meta'        => array(
					'currency_symbol'  => $store_ctx['currency_symbol'],
					'site_name'        => $store_ctx['store_defaults']['name'],
					'verify_url'       => $store_ctx['orders_url'],
					'verify_label'     => __( 'Open Orders to Generate Sample', 'woocommerce-jetpack' ),
					'store_defaults'   => $store_ctx['store_defaults'],
					'doc_types'        => $doc_list,
					'diagnostics'      => $diagnostics,
					'classic_sections' => array(
						'pdf_invoicing'           => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_numbering' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_numbering&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_header'    => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_header&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_footer'    => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_footer&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_templates' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_templates&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_styling'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_styling&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_emails'    => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_emails&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_display'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_display&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
					),
				),
			);
		}

		/**
		 * Sanitize draft values.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$text_color   = isset( $draft['header_text_color'] ) ? sanitize_hex_color( (string) $draft['header_text_color'] ) : '';
			$line_color   = isset( $draft['header_line_color'] ) ? sanitize_hex_color( (string) $draft['header_line_color'] ) : '';
			$image_url    = isset( $draft['header_image'] ) ? esc_url_raw( trim( (string) $draft['header_image'] ) ) : '';
			$header_title = isset( $draft['header_title'] ) ? sanitize_text_field( (string) $draft['header_title'] ) : '';
			$header_text  = isset( $draft['header_text'] ) ? sanitize_textarea_field( (string) $draft['header_text'] ) : '';
			$header_on    = array_key_exists( 'header_enabled', $draft )
				? ! empty( $draft['header_enabled'] )
				: ( '' !== $header_title || '' !== $header_text || '' !== $image_url || ! empty( $live['header_enabled'] ) );

			return array(
				'enabled'              => ! empty( $draft['enabled'] ),
				'doc_type'             => self::normalize_doc_type( isset( $draft['doc_type'] ) ? $draft['doc_type'] : $live['doc_type'] ),
				'header_enabled'       => $header_on,
				'header_image'         => $image_url,
				'header_title'         => $header_title,
				'header_text'          => $header_text,
				'header_text_color'    => $text_color ? $text_color : '#cccccc',
				'header_line_color'    => $line_color ? $line_color : ( $text_color ? $text_color : '#cccccc' ),
				'numbering_prefix'     => isset( $draft['numbering_prefix'] ) ? sanitize_text_field( (string) $draft['numbering_prefix'] ) : '',
				'numbering_suffix'     => isset( $draft['numbering_suffix'] ) ? sanitize_text_field( (string) $draft['numbering_suffix'] ) : '',
				'footer_text'          => isset( $draft['footer_text'] ) ? sanitize_textarea_field( (string) $draft['footer_text'] ) : '',
				'create_on_completed'  => ! empty( $draft['create_on_completed'] ),
				'create_on_processing' => ! empty( $draft['create_on_processing'] ),
				'attach_completed'     => ! empty( $draft['attach_completed'] ),
				'customer_my_account'  => ! empty( $draft['customer_my_account'] ),
				'thankyou_page'        => ! empty( $draft['thankyou_page'] ),
				'skip_zero_total'      => ! empty( $draft['skip_zero_total'] ),
			);
		}

		/**
		 * Validate draft before publishing.
		 *
		 * @param array $draft Sanitized draft.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft ) {
			unset( $draft );
			return true;
		}

		/**
		 * Project Before & After document preview sheets.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview parameters.
		 * @param array $meta    Editor metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			unset( $context, $meta );
			$doc_labels = self::doc_types();

			$project = static function ( $cfg ) use ( $doc_labels ) {
				$fallback_title = isset( $doc_labels[ $cfg['doc_type'] ] ) ? $doc_labels[ $cfg['doc_type'] ] : __( 'Invoice', 'woocommerce-jetpack' );
				$triggers       = array();
				if ( ! empty( $cfg['create_on_processing'] ) ) {
					$triggers[] = __( 'Processing', 'woocommerce-jetpack' );
				}
				if ( ! empty( $cfg['create_on_completed'] ) ) {
					$triggers[] = __( 'Completed', 'woocommerce-jetpack' );
				}
				$trigger_str = ! empty( $triggers )
					? sprintf( __( 'Auto-creates on %s', 'woocommerce-jetpack' ), implode( ' / ', $triggers ) )
					: __( 'Manual creation', 'woocommerce-jetpack' );

				$email_str = ! empty( $cfg['attach_completed'] )
					? __( 'Attaches to Completed Email', 'woocommerce-jetpack' )
					: __( 'No email attachment', 'woocommerce-jetpack' );

				return array(
					'enabled'             => ! empty( $cfg['enabled'] ),
					'doc_type'            => $cfg['doc_type'],
					'header_enabled'      => ! empty( $cfg['header_enabled'] ),
					'header_image'        => $cfg['header_image'],
					'header_title'        => '' !== trim( $cfg['header_title'] ) ? $cfg['header_title'] : $fallback_title,
					'header_text'         => '' !== trim( $cfg['header_text'] ) ? $cfg['header_text'] : __( 'Company Name', 'woocommerce-jetpack' ),
					'header_text_color'   => $cfg['header_text_color'],
					'header_line_color'   => $cfg['header_line_color'],
					'invoice_number'      => $cfg['numbering_prefix'] . '1042' . $cfg['numbering_suffix'],
					'footer_text'         => trim( (string) $cfg['footer_text'] ),
					'create_on_completed' => ! empty( $cfg['create_on_completed'] ),
					'email_status'        => $trigger_str . ' • ' . $email_str,
				);
			};

			return array(
				'before' => $project( $live ),
				'after'  => $project( $draft ),
			);
		}

		/**
		 * Map sanitized draft values back onto native wp_options keys.
		 *
		 * @param array $draft Sanitized draft.
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft ) {
			$doc_type = self::normalize_doc_type( isset( $draft['doc_type'] ) ? $draft['doc_type'] : 'invoice' );
			$prefix   = 'wcj_invoicing_' . $doc_type . '_';

			$emails = array_values( array_filter( (array) get_option( $prefix . 'attach_to_emails', array() ), 'is_string' ) );
			if ( ! empty( $draft['attach_completed'] ) ) {
				if ( ! in_array( 'customer_completed_order', $emails, true ) ) {
					$emails[] = 'customer_completed_order';
				}
			} else {
				$emails = array_values( array_diff( $emails, array( 'customer_completed_order' ) ) );
			}

			$create_on = array_values( array_filter( (array) get_option( $prefix . 'create_on', array() ), 'is_string' ) );
			foreach ( array(
				'create_on_completed'  => 'woocommerce_order_status_completed',
				'create_on_processing' => 'woocommerce_order_status_processing',
			) as $field_key => $hook_name ) {
				if ( ! empty( $draft[ $field_key ] ) ) {
					if ( ! in_array( $hook_name, $create_on, true ) ) {
						$create_on[] = $hook_name;
					}
				} else {
					$create_on = array_values( array_diff( $create_on, array( $hook_name ) ) );
				}
			}

			$has_footer = '' !== trim( (string) $draft['footer_text'] );

			return array(
				'wcj_pdf_invoicing_enabled'          => $draft['enabled'] ? 'yes' : 'no',
				$prefix . 'header_enabled'           => $draft['header_enabled'] ? 'yes' : 'no',
				$prefix . 'header_image'             => $draft['header_image'],
				$prefix . 'header_title_text'        => $draft['header_title'],
				$prefix . 'header_text'              => $draft['header_text'],
				$prefix . 'header_text_color'        => $draft['header_text_color'],
				$prefix . 'header_line_color'        => $draft['header_line_color'],
				$prefix . 'numbering_prefix'         => $draft['numbering_prefix'],
				$prefix . 'numbering_suffix'         => $draft['numbering_suffix'],
				$prefix . 'footer_enabled'           => $has_footer ? 'yes' : get_option( $prefix . 'footer_enabled', 'no' ),
				$prefix . 'footer_text'              => $draft['footer_text'],
				$prefix . 'create_on'                => $create_on,
				$prefix . 'attach_to_emails'         => $emails,
				$prefix . 'enabled_for_customers'    => ! empty( $draft['customer_my_account'] ) ? 'yes' : 'no',
				$prefix . 'enabled_on_thankyou_page' => ! empty( $draft['thankyou_page'] ) ? 'yes' : 'no',
				$prefix . 'skip_zero_total'          => ! empty( $draft['skip_zero_total'] ) ? 'yes' : 'no',
			);
		}
	}

endif;

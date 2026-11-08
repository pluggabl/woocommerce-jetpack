<?php
/**
 * Branded invoice job: one draft, real-engine synthetic preview, conditional apply,
 * readback receipt and matching-only recovery. Writes only allowlisted native options.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wcj-job-store.php';

if ( ! class_exists( 'WCJ_Invoice_Job' ) ) :

	/**
	 * Invoice adapter plus the small draft/apply/receipt/recovery seams it needs.
	 */
	class WCJ_Invoice_Job {

		const TYPE    = 'invoice';
		const RECEIPT = 'wcj_invoice_job_receipt';
		const LOCK    = 'wcj_invoice_job_lock';
		const MODULE  = 'wcj_pdf_invoicing_enabled';

		// Owned seller identity; the stock template is derived from it and never parsed.
		const IDENTITY = 'wcj_invoice_job_identity';

		const PLACEHOLDER = 'COMPANY NAME<br>COMPANY ADDRESS 1<br>COMPANY ADDRESS 2<br>';
		const HEADING     = '<h1>Invoice</h1>';

		/**
		 * Construct.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
			foreach ( array( 'load', 'preview', 'apply', 'recover' ) as $op ) {
				add_action( 'wp_ajax_wcj_invoice_job_' . $op, array( $this, 'handle' ) );
			}
		}

		/**
		 * Edition seam: the Booster singleton that this edition's wcj_get_option() caches into.
		 * Free exposes w_c_j(); Plus and Elite expose WCJ(). No alias is defined.
		 *
		 * @return object|null
		 */
		private static function core() {
			return WCJ_Job_Store::core();
		}

		/**
		 * Edition module instance, if loaded.
		 *
		 * @param string $id Module id.
		 * @return object|null
		 */
		private static function module( $id ) {
			$core = self::core();
			return $core && isset( $core->all_modules[ $id ] ) ? $core->all_modules[ $id ] : null;
		}

		/**
		 * Page URL.
		 *
		 * @return string
		 */
		public static function url() {
			return admin_url( 'admin.php?page=wcj-invoice-job' );
		}

		/**
		 * Capability check used by every entry point.
		 *
		 * @throws RuntimeException When the user lacks the native capability.
		 */
		public static function authorize() {
			if ( ! WCJ_Task_Finder::allowed( 'pdf_invoicing' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by WooCommerce.
				throw new RuntimeException( esc_html__( 'You need permission to manage WooCommerce.', 'woocommerce-jetpack' ) );
			}
		}

		/**
		 * Register submenu.
		 */
		public function register_page() {
		if ( ! WCJ_Task_Finder::allowed( 'pdf_invoicing' ) ) { return; }
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability.
			add_submenu_page( 'woocommerce', __( 'Branded invoice', 'woocommerce-jetpack' ), __( 'Branded invoice', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-invoice-job', array( $this, 'render' ) );
		}

		/**
		 * Native settings section URL.
		 *
		 * @param string $section Native section.
		 * @return string
		 */
		public static function native_url( $section ) {
			return admin_url( wcj_admin_tab_url() . '&wcj-cat=pdf_invoicing&section=' . $section );
		}

		/**
		 * Field definitions. Order is the safe write order: appearance, creation, attachment.
		 *
		 * @return array
		 */
		public static function fields() {
			$p = 'wcj_invoicing_' . self::TYPE . '_';
			return array(
				'seller_name'           => array( 'option' => self::IDENTITY, 'type' => 'seller', 'part' => 'name', 'max' => 120, 'default' => '', 'label' => __( 'Business name', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_templates' ),
				'seller_address'        => array( 'option' => self::IDENTITY, 'type' => 'seller', 'part' => 'address', 'max' => 300, 'default' => '', 'label' => __( 'Address', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_templates' ),
				'seller_reference'      => array( 'option' => self::IDENTITY, 'type' => 'seller', 'part' => 'reference', 'max' => 120, 'default' => '', 'label' => __( 'Tax or company number', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_templates' ),
				'header_image'          => array( 'option' => $p . 'header_image', 'type' => 'logo', 'default' => '', 'label' => __( 'Logo', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_header' ),
				'header_image_width_mm' => array( 'option' => $p . 'header_image_width_mm', 'type' => 'int', 'min' => 5, 'max' => 150, 'default' => '50', 'label' => __( 'Logo width (mm)', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_header' ),
				'header_text'           => array( 'option' => $p . 'header_text', 'type' => 'text', 'max' => 120, 'default' => __( 'Company Name', 'woocommerce-jetpack' ), 'label' => __( 'Header line', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_header' ),
				'header_title_text'     => array( 'option' => $p . 'header_title_text', 'type' => 'text', 'max' => 120, 'default' => __( 'Invoice', 'woocommerce-jetpack' ), 'label' => __( 'Title', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_header' ),
				'header_text_color'     => array( 'option' => $p . 'header_text_color', 'type' => 'color', 'default' => '#cccccc', 'label' => __( 'Header colour', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_header' ),
				'header_line_color'     => array( 'option' => $p . 'header_line_color', 'type' => 'color', 'default' => '#cccccc', 'label' => __( 'Header line colour', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_header' ),
				'header_enabled'        => array( 'option' => $p . 'header_enabled', 'type' => 'yesno', 'default' => 'yes', 'label' => __( 'Show header', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_header' ),
				'footer_text'           => array( 'option' => $p . 'footer_text', 'type' => 'footer', 'max' => 240, 'default' => __( 'Page %page_number% / %total_pages%', 'woocommerce-jetpack' ), 'label' => __( 'Footer note', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_footer' ),
				'footer_text_color'     => array( 'option' => $p . 'footer_text_color', 'type' => 'color', 'default' => '#cccccc', 'label' => __( 'Footer text colour', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_footer' ),
				'footer_line_color'     => array( 'option' => $p . 'footer_line_color', 'type' => 'color', 'default' => '#cccccc', 'label' => __( 'Footer line colour', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_footer' ),
				'footer_enabled'        => array( 'option' => $p . 'footer_enabled', 'type' => 'yesno', 'default' => 'yes', 'label' => __( 'Show footer', 'woocommerce-jetpack' ), 'group' => 'customers', 'tier' => 0, 'section' => 'pdf_invoicing_footer' ),
				'create_on'             => array( 'option' => $p . 'create_on', 'type' => 'create', 'default' => 'off', 'label' => __( 'Create invoices', 'woocommerce-jetpack' ), 'group' => 'records', 'tier' => 2, 'section' => 'pdf_invoicing' ),
				'attach_to_emails'      => array( 'option' => $p . 'attach_to_emails', 'type' => 'attach', 'default' => 'none', 'label' => __( 'Attach the PDF to', 'woocommerce-jetpack' ), 'group' => 'emails', 'tier' => 2, 'section' => 'pdf_invoicing_emails' ),
			);
		}

		/**
		 * Creation choices mapped to exact native create_on arrays.
		 *
		 * @return array
		 */
		public static function create_choices() {
			return array(
				'off'        => array( 'label' => __( 'never (invoices off)', 'woocommerce-jetpack' ), 'value' => array() ),
				'manual'     => array( 'label' => __( 'only when I create one by hand', 'woocommerce-jetpack' ), 'value' => array( 'manual' ) ),
				'processing' => array( 'label' => __( 'when an order becomes Processing', 'woocommerce-jetpack' ), 'value' => array( 'woocommerce_order_status_processing' ) ),
				'completed'  => array( 'label' => __( 'when an order becomes Completed', 'woocommerce-jetpack' ), 'value' => array( 'woocommerce_order_status_completed' ) ),
			);
		}

		/**
		 * Attachment choices mapped to exact native attach_to_emails arrays.
		 *
		 * @return array
		 */
		public static function attach_choices() {
			return array(
				'none'       => array( 'label' => __( 'no email', 'woocommerce-jetpack' ), 'value' => array() ),
				'processing' => array( 'label' => __( 'the customer Processing order email', 'woocommerce-jetpack' ), 'value' => array( 'customer_processing_order' ) ),
				'completed'  => array( 'label' => __( 'the customer Completed order email', 'woocommerce-jetpack' ), 'value' => array( 'customer_completed_order' ) ),
			);
		}

		// ---------------------------------------------------------------------
		// Storage seam: exact persisted identity, absence distinct from empty.
		// Shared primitives live in WCJ_Job_Store; these wrappers keep the invoice contracts.
		// ---------------------------------------------------------------------

		/**
		 * Read option bypassing caches.
		 *
		 * @param string $key Option.
		 * @return array
		 */
		private static function read_state( $key ) {
			return WCJ_Job_Store::read_state( $key );
		}

		/**
		 * State for a value.
		 *
		 * @param mixed $value Value.
		 * @return array
		 */
		private static function value_state( $value ) {
			return WCJ_Job_Store::value_state( $value );
		}

		/**
		 * Value from state.
		 *
		 * @param array $state State.
		 * @return mixed
		 */
		private static function state_value( $state ) {
			return WCJ_Job_Store::state_value( $state );
		}

		/**
		 * Fingerprint of a persisted state.
		 *
		 * @param array $state State.
		 * @return string
		 */
		private static function state_hash( $state ) {
			return WCJ_Job_Store::state_hash( $state );
		}

		/**
		 * Conditional write: only succeeds if the stored value still equals $before.
		 * New job state and the large generated template are read only by PDF code: not autoloaded.
		 * Small native settings keep the native autoloaded default. UPDATE never changes an existing row's autoload.
		 *
		 * @param string $key Option.
		 * @param array  $before Expected state.
		 * @param array  $after Desired state.
		 * @return bool
		 */
		private static function compare_exchange( $key, $before, $after ) {
			return WCJ_Job_Store::compare_exchange( $key, $before, $after, array( self::IDENTITY, self::LOCK, self::template_option() ) );
		}

		/**
		 * Acquire the job lock; an abandoned lock older than ten minutes may be replaced exactly.
		 *
		 * @return array Lock state.
		 * @throws RuntimeException When busy.
		 */
		private static function lock() {
			return WCJ_Job_Store::lock( self::LOCK, __( 'Another invoice apply or restore is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
		}

		/**
		 * Release lock.
		 *
		 * @param array $mine Lock state.
		 */
		private static function unlock( $mine ) {
			WCJ_Job_Store::unlock( self::LOCK, $mine );
		}

		// ---------------------------------------------------------------------
		// Adapter: current values, environment, validation, consequences.
		// ---------------------------------------------------------------------

		/**
		 * Stock template text.
		 *
		 * @return string
		 */
		private static function stock_template() {
			$templates = self::module( 'pdf_invoicing_templates' );
			if ( $templates ) {
				return (string) $templates->get_default_template( self::TYPE );
			}
			// Same shipped file the module includes; resolved from this edition's own includes/ directory.
			$text = file_get_contents( dirname( __DIR__ ) . '/settings/pdf-invoicing/wcj-content-template-' . self::TYPE . '.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Fixed shipped file.
			return false === $text ? '' : $text;
		}

		/**
		 * Environment facts that change what the canvas can promise.
		 *
		 * @return array
		 */
		public static function environment() {
			$tpl    = self::template_info();
			$custom = 'custom' === $tpl['mode'];
			$module = self::read_state( self::MODULE );
			return array(
				'custom_template'   => $custom,
				'template_mode'     => $tpl['mode'],
				'identity_base'     => $tpl['base'],
				'module_enabled'    => 'yes' === self::state_value( $module ),
				'module_base'       => self::state_hash( $module ),
				'temp_dir_disabled' => 'yes' === self::state_value( self::read_state( 'wcj_general_advanced_disable_save_sys_temp_dir' ) ),
				'other_documents'   => self::other_documents(),
				'images_dir'        => self::images_root_ok( wcj_get_invoicing_default_images_directory() ),
			);
		}

		/**
		 * Template option name.
		 *
		 * @return string
		 */
		private static function template_option() {
			return 'wcj_invoicing_' . self::TYPE . '_template';
		}

		/**
		 * Normalize an identity array.
		 *
		 * @param mixed $value Stored or proposed identity.
		 * @return array
		 */
		private static function identity( $value ) {
			$out = array(
				'name'      => '',
				'address'   => '',
				'reference' => '',
				'heading'   => 'Invoice',
			);
			if ( is_array( $value ) ) {
				foreach ( $out as $k => $default ) {
					if ( isset( $value[ $k ] ) && is_string( $value[ $k ] ) ) {
						$out[ $k ] = $value[ $k ];
					}
				}
			}
			if ( '' === $out['heading'] ) {
				$out['heading'] = 'Invoice';
			}
			return $out;
		}

		/**
		 * Exact derivation: stock template with only the seller cell and the H1 text replaced.
		 *
		 * @param array $identity Identity.
		 * @return string|null Template, or null when the stock template lacks the known markers.
		 */
		private static function generate_template( $identity ) {
			$stock = self::stock_template();
			$h_at  = strpos( $stock, self::HEADING );
			$s_at  = strpos( $stock, self::PLACEHOLDER );
			if ( false === $h_at || false === $s_at || $h_at > $s_at ) {
				return null;
			}
			$identity = self::identity( $identity );
			$lines    = array( trim( $identity['name'] ) );
			foreach ( preg_split( '/\n/', $identity['address'] ) as $line ) {
				$lines[] = trim( $line );
			}
			$lines[] = trim( $identity['reference'] );
			$lines   = array_values( array_filter( $lines, 'strlen' ) );
			$seller  = $lines ? implode( '<br>', array_map( array( __CLASS__, 'literal' ), $lines ) ) . '<br>' : self::PLACEHOLDER;
			// Replace the later marker first so the earlier offset stays valid.
			$text = substr_replace( $stock, $seller, $s_at, strlen( self::PLACEHOLDER ) );
			return substr_replace( $text, '<h1>' . self::literal( $identity['heading'] ) . '</h1>', $h_at, strlen( self::HEADING ) );
		}

		/**
		 * Encode owner plain text for HTML so the PDF shows exactly what was typed.
		 * Unlike esc_html(), existing entities are encoded too: typing "A &amp; B" prints "A &amp; B".
		 *
		 * @param string $text Plain text.
		 * @return string
		 */
		private static function literal( $text ) {
			return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', true );
		}

		/**
		 * Whether a seller identity (stored, imported or proposed) has the exact supported shape and bounds.
		 *
		 * @param mixed $value Candidate.
		 * @return bool
		 */
		private static function identity_valid( $value ) {
			if ( ! is_array( $value ) || array_diff( array_keys( $value ), array( 'name', 'address', 'reference', 'heading' ) ) ) {
				return false;
			}
			$limits = array(
				'name'      => array( 120, 1 ),
				'address'   => array( 300, 4 ),
				'reference' => array( 120, 1 ),
				'heading'   => array( 120, 1 ),
			);
			foreach ( $value as $key => $text ) {
				if ( ! is_string( $text ) || strlen( $text ) > $limits[ $key ][0] ) {
					return false;
				}
				$lines = explode( "\n", $text );
				if ( count( $lines ) > $limits[ $key ][1] ) {
					return false;
				}
				foreach ( $lines as $line ) {
					if ( '' !== $line && ! self::plain( $line, $limits[ $key ][0] ) ) {
						return false;
					}
				}
			}
			return true;
		}

		/**
		 * Settings export: add the seller identity only while it still exactly generates the saved template.
		 * Site-local receipts and locks are never exported.
		 *
		 * @param array $settings Native export map.
		 * @return array
		 */
		public static function export_settings( $settings ) {
			unset( $settings[ self::RECEIPT ], $settings[ self::LOCK ], $settings['wcj_invoice_setup_lock'] );
			$info = self::template_info();
			if ( 'managed' === $info['mode'] && self::identity_valid( self::state_value( $info['identity_state'] ) ) ) {
				$settings[ self::IDENTITY ] = self::state_value( $info['identity_state'] );
			}
			return $settings;
		}

		/**
		 * Settings import, called after key validation and before any write.
		 * - Receipts and locks (job and legacy setup) are site-local: removed, never written.
		 * - The identity is accepted only with the exact shape/bounds and when it generates the template that will be
		 *   saved after import (imported template, else the current one). Otherwise it is dropped and the template stays
		 *   custom/locked: an honest downgrade, never a rewrite of HTML. Old backups without identity work unchanged.
		 *
		 * @param array $settings Validated import map.
		 * @return array { settings (without identity), identity|null, notes[] }
		 */
		public static function import_settings( $settings ) {
			$notes = array();
			foreach ( array( self::RECEIPT, self::LOCK, 'wcj_invoice_setup_lock' ) as $local ) {
				if ( array_key_exists( $local, $settings ) ) {
					unset( $settings[ $local ] );
					$notes['local'] = __( 'Site-local undo history and locks in the file were skipped.', 'woocommerce-jetpack' );
				}
			}
			$identity = null;
			if ( array_key_exists( self::IDENTITY, $settings ) ) {
				$candidate = $settings[ self::IDENTITY ];
				unset( $settings[ self::IDENTITY ] );
				$tpl_key  = self::template_option();
				$template = array_key_exists( $tpl_key, $settings ) ? $settings[ $tpl_key ] : self::state_value( self::read_state( $tpl_key ) );
				if ( self::identity_valid( $candidate ) && is_string( $template ) && self::generate_template( $candidate ) === $template ) {
					$identity = $candidate;
				} else {
					$notes['identity'] = __( 'Branded invoice seller details in the file did not match its invoice template and were not imported. The template was kept as saved; edit it in the HTML template settings.', 'woocommerce-jetpack' );
				}
			}
			return array(
				'settings' => $settings,
				'identity' => $identity,
				'notes'    => array_values( $notes ),
			);
		}

		/**
		 * After a successful native import: write an accepted identity (not autoloaded) and clear the local undo
		 * receipt, whose recorded "before" values no longer describe this site's settings.
		 *
		 * @param array|null $identity Accepted identity.
		 */
		public static function after_import( $identity ) {
			if ( null !== $identity ) {
				update_option( self::IDENTITY, $identity, false );
			}
			delete_option( self::RECEIPT );
		}

		/**
		 * Whether the saved template is stock, derived from the saved identity, or custom.
		 *
		 * @return array { mode, identity, base, template_state, identity_state }
		 */
		private static function template_info() {
			$tpl   = self::read_state( self::template_option() );
			$ids   = self::read_state( self::IDENTITY );
			$base  = hash( 'sha256', self::state_hash( $tpl ) . self::state_hash( $ids ) );
			$stock = self::stock_template();
			$value = self::state_value( $tpl );
			$mode  = 'custom';
			$ident = self::identity( null );
			if ( null === self::generate_template( $ident ) ) {
				$mode = 'unsupported';
			} elseif ( ! $tpl['exists'] || '' === trim( (string) $value ) || trim( (string) $value ) === trim( $stock ) ) {
				$mode = 'stock';
			} elseif ( $ids['exists'] && is_string( $value ) && self::generate_template( self::state_value( $ids ) ) === $value ) {
				$mode  = 'managed';
				$ident = self::identity( self::state_value( $ids ) );
			}
			return array(
				'mode'           => $mode,
				'identity'       => $ident,
				'base'           => $base,
				'template_state' => $tpl,
				'identity_state' => $ids,
			);
		}

		/**
		 * The engine resolves header images as root . URL path; only a real directory can resolve uploads.
		 * "tcpdf_default" (false) and "empty" ('') cannot.
		 *
		 * @param mixed $root Native images directory.
		 * @return bool
		 */
		private static function images_root_ok( $root ) {
			return is_string( $root ) && '' !== $root && is_dir( $root );
		}

		/**
		 * Other document types with saved generation rules.
		 *
		 * @return array Option names.
		 */
		private static function other_documents() {
			global $wpdb;
			$rows  = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'wcj\\_invoicing\\_%\\_create\\_on'", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$other = array();
			foreach ( (array) $rows as $row ) {
				if ( 'wcj_invoicing_' . self::TYPE . '_create_on' === $row['option_name'] ) {
					continue;
				}
				$value = maybe_unserialize( $row['option_value'] );
				if ( ! empty( $value ) && 'disabled' !== $value ) {
					$other[] = $row['option_name'];
				}
			}
			sort( $other );
			return $other;
		}

		/**
		 * Map a stored value to the canvas value, or null when the canvas cannot represent it.
		 *
		 * @param array $def Field.
		 * @param array $state Stored state.
		 * @return array { value, locked reason|null }
		 */
		private static function display( $def, $state ) {
			$raw = self::state_value( $state );
			switch ( $def['type'] ) {
				case 'create':
				case 'attach':
					$choices = 'create' === $def['type'] ? self::create_choices() : self::attach_choices();
					$list    = ( null === $raw || '' === $raw || 'disabled' === $raw ) ? array() : $raw;
					if ( is_string( $list ) ) {
						$list = array( $list );
					}
					foreach ( $choices as $key => $choice ) {
						if ( is_array( $list ) && array_values( $list ) === $choice['value'] ) {
							return array( $key, null );
						}
					}
					return array( 'custom', __( 'A custom combination is saved. It is preserved; change it in advanced settings.', 'woocommerce-jetpack' ) );
				case 'footer':
					if ( null === $raw ) {
						return array( $def['default'], null );
					}
					if ( ! is_string( $raw ) || preg_match( '/[<\[]/', $raw ) ) {
						return array( is_string( $raw ) ? $raw : '', __( 'Contains HTML or shortcodes. It is preserved; edit it in advanced settings.', 'woocommerce-jetpack' ) );
					}
					return array( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ), null );
				case 'text':
					if ( null === $raw ) {
						return array( $def['default'], null );
					}
					if ( ! is_string( $raw ) || false !== strpos( $raw, '[' ) ) {
						return array( is_string( $raw ) ? $raw : '', __( 'Uses a shortcode. It is preserved; edit it in advanced settings.', 'woocommerce-jetpack' ) );
					}
					return array( $raw, null );
				case 'color':
					$value = null === $raw ? $def['default'] : $raw;
					if ( ! is_string( $value ) || ! preg_match( '/^#[0-9a-fA-F]{6}$/D', $value ) ) {
						return array( is_string( $value ) ? $value : '', __( 'Saved value is not a plain colour. It is preserved; edit it in advanced settings.', 'woocommerce-jetpack' ) );
					}
					return array( strtolower( $value ), null );
				case 'yesno':
					return array( null === $raw ? $def['default'] : ( 'yes' === $raw ? 'yes' : 'no' ), null );
				case 'int':
					return array( null === $raw ? $def['default'] : (string) $raw, null );
				case 'logo':
					if ( null !== $raw && ( ! is_string( $raw ) || false !== strpos( $raw, '[' ) ) ) {
						return array( is_string( $raw ) ? $raw : '', __( 'Uses a shortcode. It is preserved; edit it in advanced settings.', 'woocommerce-jetpack' ) );
					}
					return array( null === $raw ? '' : $raw, null );
			}
			return array( '', __( 'Unsupported.', 'woocommerce-jetpack' ) );
		}

		/**
		 * Current values, base fingerprints and environment.
		 *
		 * @return array
		 */
		public static function load() {
			self::authorize();
			$out = array();
			$tpl = self::template_info();
			foreach ( self::fields() as $key => $def ) {
				if ( 'seller' === $def['type'] ) {
					$out[ $key ] = array(
						'value'  => $tpl['identity'][ $def['part'] ],
						'locked' => in_array( $tpl['mode'], array( 'stock', 'managed' ), true ) ? null : __( 'Your custom HTML template sets these details. It is kept exactly as saved.', 'woocommerce-jetpack' ),
						'stored' => $tpl['identity_state']['exists'],
						'base'   => $tpl['base'],
					);
					continue;
				}
				$state       = self::read_state( $def['option'] );
				$shown       = self::display( $def, $state );
				$out[ $key ] = array(
					'value'  => $shown[0],
					'locked' => $shown[1],
					'stored' => $state['exists'],
					'base'   => self::state_hash( $state ),
				);
			}
			$receipt = get_option( self::RECEIPT, null );
			return array(
				'fields'  => $out,
				'env'     => self::environment(),
				'receipt' => is_array( $receipt ) ? self::receipt_view( $receipt ) : null,
			);
		}

		/**
		 * Plain-text check.
		 *
		 * @param string $value Value.
		 * @param int    $max Max length.
		 * @return bool
		 */
		private static function plain( $value, $max ) {
			return is_string( $value ) && strlen( $value ) <= $max && false !== preg_match( '//u', $value ) && ! preg_match( '/[<>\[\]\x00-\x1F\x7F]/u', $value );
		}

		/**
		 * Validate a local logo URL the engine itself can resolve, without fetching anything.
		 *
		 * @param string $url Candidate URL.
		 * @return string|null Error or null.
		 */
		private static function logo_error( $url ) {
			if ( '' === $url ) {
				return null;
			}
			if ( ! is_string( $url ) || strlen( $url ) > 2000 || ! wp_http_validate_url( $url ) ) {
				return __( 'Choose an image from this site\'s Media Library.', 'woocommerce-jetpack' );
			}
			$uploads = wp_upload_dir( null, false );
			if ( wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( $uploads['baseurl'], PHP_URL_HOST ) ) {
				return __( 'Remote or offloaded images are not supported. Choose a local Media Library image.', 'woocommerce-jetpack' );
			}
			$root = wcj_get_invoicing_default_images_directory();
			if ( ! self::images_root_ok( $root ) ) {
				return __( 'The PDF engine\'s image directory setting cannot resolve uploaded logos. Review Advanced > Default Images Directory, or continue without a logo.', 'woocommerce-jetpack' );
			}
			$path = realpath( $root . wp_parse_url( $url, PHP_URL_PATH ) );
			$base = realpath( $uploads['basedir'] );
			if ( ! $path || ! $base || 0 !== strpos( $path, $base . DIRECTORY_SEPARATOR ) || ! is_file( $path ) || filesize( $path ) > 2097152 ) {
				return __( 'The PDF engine cannot find this image in the uploads folder with the current image directory setting, or it is larger than 2 MB.', 'woocommerce-jetpack' );
			}
			$size = getimagesize( $path );
			if ( ! $size || ! in_array( $size[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG ), true ) || $size[0] > 4096 || $size[1] > 4096 ) {
				return __( 'Use a PNG or JPEG of at most 4096 pixels per side.', 'woocommerce-jetpack' );
			}
			return null;
		}

		/**
		 * Normalize one canvas value to the exact native stored value.
		 *
		 * @param array $def Field.
		 * @param mixed $value Submitted value.
		 * @return array { stored, error|null }
		 */
		private static function normalize( $def, $value ) {
			if ( ! is_string( $value ) ) {
				return array( null, __( 'Invalid value.', 'woocommerce-jetpack' ) );
			}
			switch ( $def['type'] ) {
				case 'logo':
					$error = self::logo_error( $value );
					return array( $value, $error );
				case 'int':
					if ( ! preg_match( '/^[0-9]{1,3}$/D', $value ) || (int) $value < $def['min'] || (int) $value > $def['max'] ) {
						/* translators: 1: min, 2: max */
						return array( null, sprintf( __( 'Enter a whole number from %1$d to %2$d.', 'woocommerce-jetpack' ), $def['min'], $def['max'] ) );
					}
					return array( (string) (int) $value, null );
				case 'seller':
					$value = trim( str_replace( "\r", '', $value ) );
					$lines = explode( "\n", $value );
					$ok    = count( $lines ) <= ( 'address' === $def['part'] ? 4 : 1 );
					foreach ( $lines as $line ) {
						$ok = $ok && ( '' === $line || self::plain( $line, $def['max'] ) );
					}
					if ( ! $ok || strlen( $value ) > $def['max'] ) {
						return array( null, 'address' === $def['part'] ? __( 'Up to 4 lines of plain text.', 'woocommerce-jetpack' ) : __( 'Plain text only, no HTML or shortcodes.', 'woocommerce-jetpack' ) );
					}
					return array( $value, null );
				case 'text':
					$value = trim( $value );
					if ( ! self::plain( $value, $def['max'] ) ) {
						/* translators: %d: max */
						return array( null, sprintf( __( 'Use plain text without HTML or shortcodes, up to %d characters.', 'woocommerce-jetpack' ), $def['max'] ) );
					}
					return array( $value, null );
				case 'footer':
					$value = trim( $value );
					// Angle brackets and ampersands are encoded as text below; square brackets stay rejected because the
					// engine runs do_shortcode() on the footer even when HTML-encoded.
					if ( strlen( $value ) > $def['max'] || false === preg_match( '//u', $value ) || preg_match( '/[\[\]\x00-\x1F\x7F]/u', $value ) ) {
						/* translators: %d: max */
						return array( null, sprintf( __( 'Use plain text without shortcodes, up to %d characters. %%page_number%% and %%total_pages%% are allowed.', 'woocommerce-jetpack' ), $def['max'] ) );
					}
					// Stored as HTML (the footer is HTML in the engine); display decodes it back, so typed text round-trips exactly.
					return array( self::literal( $value ), null );
				case 'color':
					if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/D', $value ) ) {
						return array( null, __( 'Choose a colour like #1a1a2e.', 'woocommerce-jetpack' ) );
					}
					return array( strtolower( $value ), null );
				case 'yesno':
					return in_array( $value, array( 'yes', 'no' ), true ) ? array( $value, null ) : array( null, __( 'Invalid value.', 'woocommerce-jetpack' ) );
				case 'create':
					$choices = self::create_choices();
					return isset( $choices[ $value ] ) ? array( $choices[ $value ]['value'], null ) : array( null, __( 'Choose one of the listed options.', 'woocommerce-jetpack' ) );
				case 'attach':
					$choices = self::attach_choices();
					return isset( $choices[ $value ] ) ? array( $choices[ $value ]['value'], null ) : array( null, __( 'Choose one of the listed options.', 'woocommerce-jetpack' ) );
			}
			return array( null, __( 'Unsupported field.', 'woocommerce-jetpack' ) );
		}

		/**
		 * Parse a submitted draft.
		 *
		 * @param string $field POST field.
		 * @return array
		 * @throws RuntimeException On malformed input.
		 */
		private static function posted_json( $field ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput -- Nonce verified in handle(); bounded JSON strictly validated per field.
			$raw = isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '{}';
			if ( strlen( $raw ) > 20000 ) {
				throw new RuntimeException( esc_html__( 'The draft is too large.', 'woocommerce-jetpack' ) );
			}
			$data = json_decode( $raw, true, 4 );
			if ( ! is_array( $data ) ) {
				throw new RuntimeException( esc_html__( 'The draft could not be read. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return $data;
		}

		/**
		 * Validate draft changes against current state.
		 *
		 * @param array $changes Canvas values by field key.
		 * @param bool  $visual_only Ignore non-visual fields (preview).
		 * @return array { stored => [key => value], issues => [key => message], current => load fields }
		 * @throws RuntimeException On unknown fields.
		 */
		private static function validate( $changes, $visual_only ) {
			$fields  = self::fields();
			$current = self::load();
			$stored  = array();
			$issues  = array();
			foreach ( $changes as $key => $value ) {
				if ( ! is_string( $key ) || ! isset( $fields[ $key ] ) ) {
					throw new RuntimeException( esc_html__( 'Unknown invoice setting. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
				if ( $visual_only && 0 !== $fields[ $key ]['tier'] ) {
					continue;
				}
				if ( null !== $current['fields'][ $key ]['locked'] ) {
					$issues[ $key ] = $current['fields'][ $key ]['locked'];
					continue;
				}
				$result = self::normalize( $fields[ $key ], $value );
				if ( null !== $result[1] ) {
					$issues[ $key ] = $result[1];
				} else {
					$stored[ $key ] = $result[0];
				}
			}
			if ( ! $visual_only ) {
				$create = isset( $changes['create_on'] ) ? $changes['create_on'] : $current['fields']['create_on']['value'];
				$attach = isset( $changes['attach_to_emails'] ) ? $changes['attach_to_emails'] : $current['fields']['attach_to_emails']['value'];
				$touch  = isset( $changes['create_on'] ) || isset( $changes['attach_to_emails'] );
				if ( $touch && 'none' !== $attach && 'custom' !== $attach ) {
					$ok = ( 'processing' === $attach && 'processing' === $create ) || ( 'completed' === $attach && in_array( $create, array( 'processing', 'completed' ), true ) );
					if ( ! $ok ) {
						$issues['attach_to_emails'] = __( 'The invoice must be created automatically at or before that email is sent. Choose a matching creation event, or no email.', 'woocommerce-jetpack' );
					} elseif ( $current['env']['temp_dir_disabled'] ) {
						$issues['attach_to_emails'] = __( 'PDF email attachments are switched off by the existing "disable save to temp dir" advanced setting. Choose no email or review advanced settings.', 'woocommerce-jetpack' );
					}
				}
				if ( isset( $changes['create_on'] ) && ! in_array( $create, array( 'off', 'custom' ), true ) && ! $current['env']['module_enabled'] && $current['env']['other_documents'] ) {
					$issues['create_on'] = __( 'PDF Invoicing is off and other documents have saved creation rules. Turning it on would start those too. Review advanced settings first.', 'woocommerce-jetpack' );
				}
			}
			// Seller details and the body heading become one derived template write, only for stock/managed templates.
			$derived = array();
			$tpl     = self::template_info();
			$touched = isset( $stored['header_title_text'] );
			foreach ( $stored as $key => $value ) {
				$touched = $touched || 'seller' === $fields[ $key ]['type'];
			}
			if ( $touched && in_array( $tpl['mode'], array( 'stock', 'managed' ), true ) ) {
				$ident = $tpl['identity'];
				foreach ( $stored as $key => $value ) {
					if ( 'seller' === $fields[ $key ]['type'] ) {
						$ident[ $fields[ $key ]['part'] ] = $value;
					}
				}
				if ( isset( $stored['header_title_text'] ) ) {
					$ident['heading'] = '' === $stored['header_title_text'] ? 'Invoice' : $stored['header_title_text'];
				}
				if ( $ident !== $tpl['identity'] ) {
					$template                  = self::generate_template( $ident );
					$derived[ self::IDENTITY ] = $ident;
					if ( ! ( 'stock' === $tpl['mode'] && $template === self::stock_template() ) ) {
						$derived[ self::template_option() ] = $template;
					}
				}
			}
			return array(
				'stored'  => $stored,
				'derived' => $derived,
				'tpl'     => $tpl,
				'issues'  => $issues,
				'current' => $current,
			);
		}

		// ---------------------------------------------------------------------
		// Preview: the existing engine with request-local overrides and synthetic data.
		// ---------------------------------------------------------------------

		/**
		 * Make merchant HTML safe for a synthetic preview: no images, TCPDF calls or remote CSS.
		 *
		 * @param string $html HTML.
		 * @return string
		 */
		private static function preview_safe( $html ) {
			$html = preg_replace( '/<img\b[^>]*>/i', '', $html );
			$html = preg_replace( '/<tcpdf\b[^>]*>/i', '', $html );
			$html = preg_replace( '/@import[^;]*;|url\s*\([^)]*\)/i', '', $html );
			return $html;
		}

		/**
		 * Replace fixed known tokens with Example Store sample text. Never calls order shortcodes.
		 *
		 * @param string $html Template.
		 * @return array { html, unsupported count }
		 */
		private static function sample_body( $html ) {
			$items  = '<table border="1" cellpadding="5"><tr><th>' . esc_html__( 'Item', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Qty', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Total', 'woocommerce-jetpack' ) . '</th></tr><tr><td>' . esc_html__( 'Sample product', 'woocommerce-jetpack' ) . '</td><td>1</td><td>100.00</td></tr></table>';
			$buyer  = 'Alex Example<br>1 Example Street<br>Example City';
			$tokens = array(
				'wcj_order_items_table'      => $items,
				'wcj_order_billing_address'  => $buyer,
				'wcj_order_shipping_address' => $buyer,
				'wcj_order_number'           => 'SAMPLE-ORDER',
				'wcj_order_date'             => '2026-01-01',
				'wcj_order_shipping_method'  => 'Sample shipping',
				'wcj_order_payment_method'   => 'Sample payment',
				'wcj_order_total_excl_tax'   => '100.00',
				'wcj_order_subtotal'         => '100.00',
				'wcj_order_total_tax'        => '0.00',
				'wcj_order_total'            => '100.00',
				'wcj_invoice_number'         => 'SAMPLE-001',
				'wcj_invoice_date'           => '2026-01-01',
			);
			$html   = preg_replace_callback(
				'/\[(wcj_[a-z0-9_]+)(?:\s[^\]]*)?\]/',
				static function ( $m ) use ( $tokens ) {
					return isset( $tokens[ $m[1] ] ) ? $tokens[ $m[1] ] : $m[0];
				},
				$html
			);
			$left   = preg_match_all( '/\[\/?[a-zA-Z_][^\]]*\]/', $html );
			return array( $html, (int) $left );
		}

		/**
		 * Render a full PDF with WCJ_PDF_Invoice::prepare_pdf() and the saved template/CSS.
		 *
		 * @param array $overlay Native option name => value, request-local only.
		 * @return array { bytes, limits[], body_previewed }
		 * @throws RuntimeException On render failure.
		 */
		private static function render_pdf( $overlay, $side = 'now' ) {
			$limits = array();
			$p      = 'wcj_invoicing_' . self::TYPE . '_';
			// Never download fonts or write font options during a preview.
			if ( ! wcj_check_tcpdf_fonts_version( true ) ) {
				$overlay['wcj_invoicing_fonts_manager_do_not_download'] = 'yes';
				$limits[] = __( 'Extra PDF fonts are not installed, so the preview uses the fallback font (as real invoices currently do).', 'woocommerce-jetpack' );
			}
			if ( '' !== (string) wcj_get_option( $p . 'background_image', '' ) ) {
				$overlay[ $p . 'background_image' ] = '';
				$limits[] = __( 'The background image is not drawn in this preview.', 'woocommerce-jetpack' );
			}
			// Merchant text that the engine would pass through do_shortcode is shown literally, never executed without an order.
			$literal = static function ( $value ) {
				return is_string( $value ) ? str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), $value ) : $value;
			};
			foreach ( array( 'header_title_text', 'header_text', 'header_image' ) as $suffix ) {
				$key = $p . $suffix;
				if ( ! array_key_exists( $key, $overlay ) ) {
					$value = wcj_get_option( $key, null );
					if ( is_string( $value ) && false !== strpos( $value, '[' ) ) {
						$overlay[ $key ] = 'header_image' === $suffix ? '' : $literal( $value );
						$limits[]        = __( 'Shortcodes in saved header settings are shown as written; real invoices fill them from the order.', 'woocommerce-jetpack' );
					}
				}
			}
			$footer_key             = $p . 'footer_text';
			$footer                 = array_key_exists( $footer_key, $overlay ) ? $overlay[ $footer_key ] : wcj_get_option( $footer_key, __( 'Page %page_number% / %total_pages%', 'woocommerce-jetpack' ) );
			$overlay[ $footer_key ] = self::preview_safe( $literal( (string) $footer ) );
			$hooks                  = array();
			foreach ( $overlay as $option => $value ) {
				$cb      = static function () use ( $value ) {
					return $value;
				};
				$hooks[] = array( $option, $cb );
				add_filter( $option, $cb, PHP_INT_MAX );
				add_filter( 'pre_option_' . $option, $cb, PHP_INT_MAX );
			}
			try {
				$invoice  = new WCJ_PDF_Invoice( 0, self::TYPE );
				$pdf      = $invoice->prepare_pdf();
				$template = wcj_get_option( $p . 'template', self::stock_template() );
				$body     = self::sample_body( self::preview_safe( (string) $template ) );
				$styling  = self::module( 'pdf_invoicing_styling' );
				$css      = self::preview_safe( (string) wcj_get_option( $p . 'css', $styling ? $styling->get_default_css_template( self::TYPE ) : '' ) );
				$state    = 'after' === $side ? __( 'AFTER (draft, not saved)', 'woocommerce-jetpack' ) : __( 'NOW (saved settings)', 'woocommerce-jetpack' );
				$banner   = '<p style="color:#b42318;font-weight:bold;">' . esc_html__( 'SAMPLE - NOT A VALID INVOICE', 'woocommerce-jetpack' ) . ' · ' . esc_html( $state ) . ' · ' . esc_html( function_exists( 'wp_date' ) ? wp_date( 'M j, g:i:s a' ) : date_i18n( 'M j, g:i:s a' ) ) . '</p>';
				if ( $body[1] > 0 ) {
					$html = $banner . '<table border="1" cellpadding="8"><tr><td>' . esc_html(
						sprintf(
							/* translators: %d: count */
							_n( 'Document body not previewed: your custom template uses %d shortcode that needs a real order. The header, footer, logo and colours on this page are the real engine output.', 'Document body not previewed: your custom template uses %d shortcodes that need a real order. The header, footer, logo and colours on this page are the real engine output.', $body[1], 'woocommerce-jetpack' ),
							$body[1]
						)
					) . '</td></tr></table>';
				} else {
					$html = $banner . force_balance_tags( $body[0] );
				}
				$pdf->writeHTMLCell( 0, 0, '', '', '<style>' . $css . '</style>' . $html, 0, 1, 0, true, '', true );
				$bytes = $pdf->Output( '', 'S' );
			} finally {
				foreach ( $hooks as $hook ) {
					remove_filter( $hook[0], $hook[1], PHP_INT_MAX );
					remove_filter( 'pre_option_' . $hook[0], $hook[1], PHP_INT_MAX );
				}
			}
			if ( ! is_string( $bytes ) || 0 !== strpos( $bytes, '%PDF-' ) || strlen( $bytes ) > 8388608 ) {
				throw new RuntimeException( esc_html__( 'The PDF engine did not return a usable document. Nothing was saved.', 'woocommerce-jetpack' ) );
			}
			$limits[] = __( 'Paid stamp, real item rows, numbering and tax are not reproduced; sample values only.', 'woocommerce-jetpack' );
			$limits[] = __( 'Images and TCPDF tags inside template or footer HTML are not drawn in previews.', 'woocommerce-jetpack' );
			return array(
				'bytes'          => $bytes,
				'limits'         => array_values( array_unique( $limits ) ),
				'body_previewed' => 0 === $body[1],
			);
		}

		/**
		 * Preview service.
		 *
		 * @return array
		 */
		public static function preview() {
			self::authorize();
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
			$side    = isset( $_POST['side'] ) && 'after' === $_POST['side'] ? 'after' : 'now';
			$overlay = array();
			if ( 'after' === $side ) {
				$check = self::validate( self::posted_json( 'changes' ), true );
				if ( $check['issues'] ) {
					return array(
						'error'  => __( 'Fix the highlighted fields before previewing.', 'woocommerce-jetpack' ),
						'issues' => $check['issues'],
					);
				}
				$fields = self::fields();
				foreach ( $check['stored'] as $key => $value ) {
					if ( 'seller' !== $fields[ $key ]['type'] ) {
						$overlay[ $fields[ $key ]['option'] ] = $value;
					}
				}
				if ( isset( $check['derived'][ self::template_option() ] ) ) {
					$overlay[ self::template_option() ] = $check['derived'][ self::template_option() ];
				}
			}
			$result = self::render_pdf( $overlay, $side );
			$env    = self::environment();
			return array(
				'side'           => $side,
				'pdf'            => base64_encode( $result['bytes'] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary PDF transport.
				'limits'         => $result['limits'],
				'body_previewed' => $result['body_previewed'],
				'custom'         => $env['custom_template'],
				'label'          => $result['body_previewed'] ? __( 'Real PDF · sample order', 'woocommerce-jetpack' ) : __( 'Header and footer only · custom body needs a real order', 'woocommerce-jetpack' ),
			);
		}

		// ---------------------------------------------------------------------
		// Apply, receipt, recovery.
		// ---------------------------------------------------------------------

		/**
		 * Apply one consolidated draft.
		 *
		 * @return array
		 * @throws RuntimeException On stale/invalid/failed writes.
		 */
		public static function apply() {
			self::authorize();
			$changes = self::posted_json( 'changes' );
			$base    = self::posted_json( 'base' );
			if ( ! $changes ) {
				throw new RuntimeException( esc_html__( 'The draft is empty. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			// Creation and email changes need the explicit confirmation shown in review, not only a click on Apply.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
			if ( ( isset( $changes['create_on'] ) || isset( $changes['attach_to_emails'] ) ) && ( ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] ) ) {
				throw new RuntimeException( esc_html__( 'Confirm the invoice and email changes before applying. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			$lock = self::lock();
			try {
				$check = self::validate( $changes, false );
				if ( $check['issues'] ) {
					return array(
						'status' => 'invalid',
						'issues' => $check['issues'],
						'error'  => __( 'Some changes need attention. Nothing was changed.', 'woocommerce-jetpack' ),
					);
				}
				$fields      = self::fields();
				$plan        = array();
				$stale       = array();
				$group_stale = false;
				if ( $check['derived'] ) {
					// Identity and template are checked together: either changing since load is a conflict.
					$sent = isset( $base['identity'] ) && is_string( $base['identity'] ) ? $base['identity'] : '';
					if ( ! hash_equals( $check['tpl']['base'], $sent ) ) {
						// The group base is one opaque hash: which line changed elsewhere is unknown. Report the current
						// saved value of every pending seller/title edit so the owner compares all of them before rebasing.
						$group_stale = true;
						foreach ( array_keys( $changes ) as $key ) {
							if ( isset( $fields[ $key ] ) && ( 'seller' === $fields[ $key ]['type'] || 'header_title_text' === $key ) ) {
								$stale[ $key ] = $check['current']['fields'][ $key ]['value'];
							}
						}
					} else {
						foreach ( array( self::IDENTITY => 'identity_state', self::template_option() => 'template_state' ) as $option => $state_key ) {
							if ( array_key_exists( $option, $check['derived'] ) ) {
								$before = $check['tpl'][ $state_key ];
								$after  = self::value_state( $check['derived'][ $option ] );
								if ( $before !== $after ) {
									$plan[ $option ] = array(
										'field'  => self::IDENTITY === $option ? 'identity' : 'template',
										'before' => $before,
										'after'  => $after,
									);
								}
							}
						}
					}
				}
				foreach ( $fields as $key => $def ) {
					if ( ! array_key_exists( $key, $check['stored'] ) || 'seller' === $def['type'] ) {
						continue;
					}
					$before = self::read_state( $def['option'] );
					if ( ! isset( $base[ $key ] ) || ! is_string( $base[ $key ] ) || ! hash_equals( self::state_hash( $before ), $base[ $key ] ) ) {
						$stale[ $key ] = $check['current']['fields'][ $key ]['value'];
						continue;
					}
					$after = self::value_state( $check['stored'][ $key ] );
					if ( $before !== $after ) {
						$plan[ $def['option'] ] = array(
							'field'  => $key,
							'before' => $before,
							'after'  => $after,
						);
					}
				}
				$create = isset( $changes['create_on'] ) ? $changes['create_on'] : null;
				if ( null !== $create && ! in_array( $create, array( 'off', 'custom' ), true ) && ! $check['current']['env']['module_enabled'] ) {
					$before = self::read_state( self::MODULE );
					if ( ! isset( $base['module'] ) || ! is_string( $base['module'] ) || ! hash_equals( self::state_hash( $before ), $base['module'] ) ) {
						$stale['module'] = 'yes' === self::state_value( $before ) ? 'yes' : 'no';
					} else {
						$plan[ self::MODULE ] = array(
							'field'  => 'module',
							'before' => $before,
							'after'  => self::value_state( 'yes' ),
						);
					}
				}
				if ( $stale ) {
					return array(
						'status' => 'stale',
						'stale'  => $stale,
						'group'  => ! empty( $group_stale ),
						'error'  => __( 'Someone changed these settings since you loaded them. Nothing was changed. Compare the current saved values, then apply again.', 'woocommerce-jetpack' ),
						'fresh'  => self::load(),
					);
				}
				if ( ! $plan ) {
					return array(
						'status'  => 'unchanged',
						'message' => __( 'These values are already saved. Nothing was written.', 'woocommerce-jetpack' ),
						'fresh'   => self::load(),
					);
				}
				$applied = array();
				$failed  = null;
				foreach ( $plan as $option => $entry ) {
					if ( ! self::compare_exchange( $option, $entry['before'], $entry['after'] ) ) {
						$failed = $entry['field'];
						break;
					}
					$applied[ $option ] = $entry;
				}
				if ( null !== $failed ) {
					$kept = array();
					foreach ( array_reverse( $applied, true ) as $option => $entry ) {
						if ( ! self::compare_exchange( $option, $entry['after'], $entry['before'] ) ) {
							$kept[] = $entry['field'];
						}
					}
					return array(
						'status' => 'failed',
						'error'  => $kept ? __( 'A write failed. Earlier writes could not all be undone because they changed again; they are listed and remain saved.', 'woocommerce-jetpack' ) : __( 'A write failed. Earlier writes from this apply were put back. Nothing from this draft remains saved.', 'woocommerce-jetpack' ),
						'failed' => $failed,
						'kept'   => $kept,
						'fresh'  => self::load(),
					);
				}
				$entries = array();
				$all_ok  = true;
				foreach ( $applied as $option => $entry ) {
					$ok                 = self::read_state( $option ) === $entry['after'];
					$all_ok             = $all_ok && $ok;
					$entries[ $option ] = array(
						'field'       => $entry['field'],
						'before'      => $entry['before'],
						'after'       => $entry['after'],
						'readback_ok' => $ok,
					);
				}
				$receipt = array(
					'id'      => bin2hex( random_bytes( 8 ) ),
					'time'    => gmdate( 'c' ),
					'user'    => get_current_user_id(),
					'status'  => $all_ok ? 'applied' : 'readback_mismatch',
					'entries' => $entries,
				);
				update_option( self::RECEIPT, $receipt, false );
				return array(
					'status'  => $receipt['status'],
					'message' => $all_ok ? __( 'Saved and read back from the database.', 'woocommerce-jetpack' ) : __( 'Saved, but a value read back differently. See the receipt.', 'woocommerce-jetpack' ),
					'fresh'   => self::load(),
				);
			} finally {
				self::unlock( $lock );
			}
		}

		/**
		 * Receipt for display, with current match state.
		 *
		 * @param array $receipt Stored receipt.
		 * @return array
		 */
		private static function receipt_view( $receipt ) {
			$fields  = self::fields();
			$rows    = array();
			$entries = self::valid_entries( isset( $receipt['entries'] ) ? $receipt['entries'] : array() );
			$tpl_key = self::template_option();
			// The generated template is shown and selected with its seller identity; one row, one checkbox.
			if ( isset( $entries[ self::IDENTITY ], $entries[ $tpl_key ] ) ) {
				$tpl_entry = $entries[ $tpl_key ];
				unset( $entries[ $tpl_key ] );
			}
			foreach ( $entries as $option => $entry ) {
				$def = isset( $fields[ $entry['field'] ] ) ? $fields[ $entry['field'] ] : null;
				if ( $def ) {
					$label  = $def['label'];
					$before = self::display( $def, $entry['before'] )[0];
					$after  = self::display( $def, $entry['after'] )[0];
				} elseif ( 'identity' === $entry['field'] ) {
					$label  = __( 'Seller details', 'woocommerce-jetpack' );
					$b      = self::identity( self::state_value( $entry['before'] ) );
					$a      = self::identity( self::state_value( $entry['after'] ) );
					$before = '' === $b['name'] ? __( 'Not set', 'woocommerce-jetpack' ) : $b['name'];
					$after  = '' === $a['name'] ? __( 'Not set', 'woocommerce-jetpack' ) : $a['name'];
				} elseif ( 'template' === $entry['field'] ) {
					$label  = __( 'Invoice body (seller and title)', 'woocommerce-jetpack' );
					$before = __( 'Previous body', 'woocommerce-jetpack' );
					$after  = __( 'Updated body', 'woocommerce-jetpack' );
				} else {
					$label  = __( 'PDF Invoicing module', 'woocommerce-jetpack' );
					$before = 'yes' === self::state_value( $entry['before'] ) ? __( 'On', 'woocommerce-jetpack' ) : __( 'Off', 'woocommerce-jetpack' );
					$after  = __( 'On', 'woocommerce-jetpack' );
				}
				$current = self::read_state( $option );
				$row     = array(
					'option'      => $option,
					'options'     => array( $option ),
					'field'       => $entry['field'],
					'label'       => $label,
					'before'      => $before,
					'after'       => $after,
					'readback_ok' => ! empty( $entry['readback_ok'] ),
					'still_ours'  => $current === $entry['after'],
					'restored'    => $current === $entry['before'],
				);
				if ( self::IDENTITY === $option && isset( $tpl_entry ) ) {
					$tpl_now            = self::read_state( $tpl_key );
					$row['options'][]   = $tpl_key;
					$row['label']       = __( 'Seller details and invoice body', 'woocommerce-jetpack' );
					$row['readback_ok'] = $row['readback_ok'] && ! empty( $tpl_entry['readback_ok'] );
					// Restorable only when neither half changed later and at least one still holds the applied value.
					$halves_ok         = ( $current === $entry['after'] || $current === $entry['before'] ) && ( $tpl_now === $tpl_entry['after'] || $tpl_now === $tpl_entry['before'] );
					$row['still_ours'] = $halves_ok && ( $current === $entry['after'] || $tpl_now === $tpl_entry['after'] );
					$row['restored']   = $current === $entry['before'] && $tpl_now === $tpl_entry['before'];
				}
				$rows[] = $row;
			}
			$stamp = isset( $receipt['time'] ) ? strtotime( $receipt['time'] ) : false;
			return array(
				'id'     => isset( $receipt['id'] ) ? $receipt['id'] : '',
				'time'   => $stamp ? ( function_exists( 'wp_date' ) ? wp_date( 'M j, g:i a', $stamp ) : date_i18n( 'M j, g:i a', $stamp ) ) : '',
				'undo'   => (bool) array_filter( wp_list_pluck( $rows, 'still_ours' ) ),
				'status' => isset( $receipt['status'] ) ? $receipt['status'] : '',
				'rows'   => $rows,
			);
		}

		/**
		 * Receipt entries with a known option and an exact before/after state shape.
		 *
		 * @param mixed $entries Stored entries.
		 * @return array
		 */
		private static function valid_entries( $entries ) {
			$allowed = array( self::MODULE, self::IDENTITY, self::template_option() );
			foreach ( self::fields() as $def ) {
				$allowed[] = $def['option'];
			}
			$valid = array();
			foreach ( is_array( $entries ) ? $entries : array() as $option => $entry ) {
				$ok = in_array( $option, $allowed, true ) && is_array( $entry ) && isset( $entry['field'] ) && is_string( $entry['field'] );
				foreach ( array( 'before', 'after' ) as $side ) {
					$ok = $ok && isset( $entry[ $side ] ) && is_array( $entry[ $side ] ) && isset( $entry[ $side ]['exists'], $entry[ $side ]['raw'] ) && is_bool( $entry[ $side ]['exists'] ) && is_string( $entry[ $side ]['raw'] );
				}
				if ( $ok ) {
					$valid[ $option ] = $entry;
				}
			}
			return $valid;
		}

		/**
		 * Restore identity and generated template together. Both current values are checked first; if the
		 * second write fails, the first is put back where it still matches. Not a database transaction.
		 *
		 * @param array $unit Valid entries for the pair present in the receipt.
		 * @param array $pair Write order.
		 * @return string restored|conflict|noop
		 */
		private static function restore_unit( $unit, $pair ) {
			$todo = array();
			foreach ( $unit as $option => $entry ) {
				$current = self::read_state( $option );
				if ( $current === $entry['before'] ) {
					continue;
				}
				if ( $current !== $entry['after'] ) {
					return 'conflict'; // Either half changed later: restore neither.
				}
				$todo[ $option ] = $entry;
			}
			if ( ! $todo ) {
				return 'noop';
			}
			$done = array();
			foreach ( $pair as $option ) {
				if ( ! isset( $todo[ $option ] ) ) {
					continue;
				}
				if ( ! self::compare_exchange( $option, $todo[ $option ]['after'], $todo[ $option ]['before'] ) ) {
					foreach ( array_reverse( $done, true ) as $back => $entry ) {
						self::compare_exchange( $back, $entry['before'], $entry['after'] );
					}
					return 'conflict';
				}
				$done[ $option ] = $todo[ $option ];
			}
			return 'restored';
		}

		/**
		 * Restore only values still equal to what the last apply wrote.
		 *
		 * @return array
		 * @throws RuntimeException When nothing is recoverable.
		 */
		public static function recover() {
			self::authorize();
			$only = self::posted_json( 'options' );
			$lock = self::lock();
			try {
				$receipt = get_option( self::RECEIPT, null );
				if ( ! is_array( $receipt ) || empty( $receipt['entries'] ) ) {
					throw new RuntimeException( esc_html__( 'There is no applied change left to restore.', 'woocommerce-jetpack' ) );
				}
				$restored  = array();
				$conflicts = array();
				$remaining = array();
				$valid     = self::valid_entries( $receipt['entries'] );
				$ignored   = count( $receipt['entries'] ) - count( $valid );
				// Seller identity and the template generated from it are one logical unit: selecting either selects both.
				$pair = array( self::template_option(), self::IDENTITY );
				$unit = array_intersect_key( $valid, array_flip( $pair ) );
				if ( $only && array_intersect( $only, $pair ) ) {
					$only = array_values( array_unique( array_merge( $only, $pair ) ) );
				}
				if ( $unit && ( ! $only || array_intersect( array_keys( $unit ), $only ) ) ) {
					$result = self::restore_unit( $unit, $pair );
					if ( 'restored' === $result ) {
						$restored[] = 'identity';
					} elseif ( 'conflict' === $result ) {
						$conflicts[] = 'identity';
						$remaining  += $unit;
					}
				} elseif ( $unit ) {
					$remaining += $unit;
				}
				// Owned field entries first; the derived module switch is decided last, against the restored state.
				$ordered = array_diff_key( array_reverse( $valid, true ), $unit );
				if ( isset( $ordered[ self::MODULE ] ) ) {
					$module_entry = $ordered[ self::MODULE ];
					unset( $ordered[ self::MODULE ] );
					$ordered[ self::MODULE ] = $module_entry;
				}
				foreach ( $ordered as $option => $entry ) {
					if ( $only && ! in_array( $option, $only, true ) ) {
						$remaining[ $option ] = $entry;
						continue;
					}
					$current = self::read_state( $option );
					if ( $current === $entry['before'] ) {
						continue;
					}
					if ( self::MODULE === $option ) {
						// Keep the module on if a creation rule still depends on it: another document's rule
						// (none existed at apply time), or an invoice rule other than the original pre-apply state.
						$own       = 'wcj_invoicing_' . self::TYPE . '_create_on';
						$own_state = self::read_state( $own );
						$original  = isset( $valid[ $own ] ) && $own_state === $valid[ $own ]['before'];
						$depends   = ! empty( self::state_value( $own_state ) ) && 'disabled' !== self::state_value( $own_state ) && ! $original;
						if ( self::other_documents() || $depends ) {
							$conflicts[]          = $entry['field'];
							$remaining[ $option ] = $entry;
							continue;
						}
					}
					if ( $current === $entry['after'] && self::compare_exchange( $option, $entry['after'], $entry['before'] ) ) {
						$restored[] = $entry['field'];
					} else {
						$conflicts[]          = $entry['field'];
						$remaining[ $option ] = $entry;
					}
				}
				// Unreadable entries are never written and are dropped from the local receipt; the result says so.
				$receipt['entries'] = array_intersect_key( $valid, $remaining );
				$receipt['status']  = $remaining ? 'partially_restored' : 'restored';
				update_option( self::RECEIPT, $receipt, false );
				return array(
					'status'    => $conflicts ? 'partial' : 'restored',
					'restored'  => $restored,
					'conflicts' => $conflicts,
					'ignored'   => $ignored,
					'message'   => $conflicts ? __( 'Restored what still matched. Values changed after your apply were kept; they are listed. Issued invoices, numbers and orders were not touched.', 'woocommerce-jetpack' ) : __( 'Previous values restored and read from the database. Issued invoices, numbers and orders were not touched.', 'woocommerce-jetpack' ),
					'fresh'     => self::load(),
				);
			} finally {
				self::unlock( $lock );
			}
		}

		/**
		 * AJAX adapter: POST, capability and per-action nonce on every action.
		 */
		public function handle() {
		if ( ! WCJ_Task_Finder::allowed( 'pdf_invoicing' ) ) { wp_send_json_error( array( 'error' => esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ) ), 403 ); }
			try {
				self::authorize();
				$action = isset( $_POST['action'] ) && is_string( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$op     = str_replace( 'wcj_invoice_job_', '', $action );
				if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! in_array( $op, array( 'load', 'preview', 'apply', 'recover' ), true ) ) {
					throw new RuntimeException( esc_html__( 'Invalid request.', 'woocommerce-jetpack' ) );
				}
				check_ajax_referer( 'wcj_invoice_job_' . $op, 'nonce' );
				$result = call_user_func( array( __CLASS__, $op ) );
				nocache_headers();
				if ( isset( $result['error'] ) ) {
					wp_send_json_error( $result, 409 );
				}
				wp_send_json_success( $result );
			} catch ( Throwable $error ) {
				nocache_headers();
				$message = $error instanceof RuntimeException ? $error->getMessage() : __( 'The request could not complete. No success is claimed; reload to see the saved values.', 'woocommerce-jetpack' );
				wp_send_json_error( array( 'error' => $message ), 400 );
			}
		}

		/**
		 * Render the canvas.
		 */
		public function render() {
			if ( ! WCJ_Task_Finder::allowed( 'pdf_invoicing' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
			}
			wp_enqueue_media();
			wp_enqueue_style( 'wcj-invoice-job', wcj_plugin_url() . '/assets/css/admin/wcj-invoice-job.css', array(), '2.5' );
			wp_enqueue_script( 'wcj-invoice-job', wcj_plugin_url() . '/assets/js/admin/wcj-invoice-job.js', array(), '2.9', true );
			$nonces = array();
			foreach ( array( 'load', 'preview', 'apply', 'recover' ) as $op ) {
				$nonces[ $op ] = wp_create_nonce( 'wcj_invoice_job_' . $op );
			}
			$fields = array();
			foreach ( self::fields() as $key => $def ) {
				$fields[ $key ] = array(
					'label'  => $def['label'],
					'type'   => $def['type'],
					'group'  => $def['group'],
					'tier'   => $def['tier'],
					'option' => $def['option'],
				);
			}
			$labels = array();
			foreach ( self::create_choices() as $k => $c ) {
				$labels['create_on'][ $k ] = $c['label'];
			}
			foreach ( self::attach_choices() as $k => $c ) {
				$labels['attach_to_emails'][ $k ] = $c['label'];
			}
			$labels['create_on']['custom']        = __( 'custom combination (advanced settings)', 'woocommerce-jetpack' );
			$labels['attach_to_emails']['custom'] = __( 'custom combination (advanced settings)', 'woocommerce-jetpack' );
			wp_localize_script(
				'wcj-invoice-job',
				'wcjInvoiceJob',
				array(
					'url'     => admin_url( 'admin-ajax.php' ),
					'nonces'  => $nonces,
					'fields'  => $fields,
					'choices' => $labels,
					'user'    => get_current_user_id(),
					'native'  => array(
						'templates' => self::native_url( 'pdf_invoicing_templates' ),
						'header'    => self::native_url( 'pdf_invoicing_header' ),
					),
				)
			);
			include __DIR__ . '/views/invoice-job.php';
		}
	}

endif;

new WCJ_Invoice_Job();

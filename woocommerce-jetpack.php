<?php
/*
Plugin Name: Booster for WooCommerce
Plugin URI: http://booster.io
Description: Supercharge your WooCommerce site with these awesome powerful features.
Version: 2.3.11
Author: Algoritmika Ltd
Author URI: http://www.algoritmika.com
Copyright: © 2015 Algoritmika Ltd.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return; // Check if WooCommerce is active

if ( ! class_exists( 'WC_Jetpack' ) ) :

/**
 * Main WC_Jetpack Class
 *
 * @class   WC_Jetpack
 * @version 2.3.9
 */

final class WC_Jetpack {

	/**
	 * @var WC_Jetpack The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Jetpack Instance
	 *
	 * Ensures only one instance of WC_Jetpack is loaded or can be loaded.
	 *
	 * @static
	 * @see WCJ()
	 * @return WC_Jetpack - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '3.9.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '3.9.1' );
	}

	/**
	 * WC_Jetpack Constructor.
	 *
	 * @version 2.2.5
	 * @access public
	 */
	public function __construct() {

		/* echo 'Constructor Start: memory_get_usage( false )' . number_format( memory_get_usage( false ), 0, '.', ',' );
		echo 'Constructor Start: memory_get_usage( true )' . number_format( memory_get_usage( true ), 0, '.', ',' ); */

		//require_once( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );

		// Include required files
		$this->includes();

		//register_activation_hook( __FILE__, array( $this, 'install' ) );
		//add_action( 'admin_init', array( $this, 'install' ) );
		add_action( 'init', array( $this, 'init' ), 0 );

		// Settings
		if ( is_admin() ) {
			add_filter( 'woocommerce_get_settings_pages',                     array( $this, 'add_wcj_settings_tab' ), PHP_INT_MAX );
			add_filter( 'get_wc_jetpack_plus_message',                        array( $this, 'get_wcj_plus_message' ), 100, 2 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
			add_action( 'admin_menu',                                         array( $this, 'jetpack_menu' ), 100 );
//			add_filter( 'admin_footer_text',                                  array( $this, 'admin_footer_text' ), 2 );
			add_action( 'admin_notices',                                      array( $this, 'name_changed_notice' ) );
		}

		// Scripts
		if ( is_admin() ) {
			if (
				'yes' === get_option( 'wcj_purchase_data_enabled' ) ||
				'yes' === get_option( 'wcj_pdf_invoicing_enabled' ) ||
				'yes' === get_option( 'wcj_crowdfunding_enabled' )
			) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				//add_action( 'admin_head', array( $this, 'add_datepicker_script' ) );
			}
		}

		if (
			'yes' === get_option( 'wcj_product_input_fields_enabled' ) ||
			'yes' === get_option( 'wcj_checkout_custom_fields_enabled' )
		){
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
			add_action( 'init',               array( $this, 'register_frontend_scripts' ) );
		}

		// Loaded action
		do_action( 'wcj_loaded' );

		/* echo 'Constructor End: memory_get_usage( false )' . number_format( memory_get_usage( false ), 0, '.', ',' );
		echo 'Constructor End: memory_get_usage( true )' . number_format( memory_get_usage( true ), 0, '.', ',' ); */
	}

	/**
	 * enqueue_frontend_scripts.
	 *
	 * @version 2.3.3
	 * @since   2.3.0
	 */
	function enqueue_frontend_scripts() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-timepicker' );
		wp_enqueue_script( 'wcj-datepicker', wcj_plugin_url() . '/includes/js/wcj-datepicker.js',
			array( 'jquery' ),
			false,
			true );
		wp_enqueue_script( 'wcj-timepicker', wcj_plugin_url() . '/includes/js/wcj-timepicker.js',
			array( 'jquery' ),
			false,
			true );

		wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'wcj-timepicker-css', wcj_plugin_url() . '/includes/css/jquery.timepicker.min.css' );
	}

	/**
	 * register_frontend_scripts.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	public function register_frontend_scripts() {
		wp_register_script(
			'jquery-ui-timepicker',
			wcj_plugin_url() . '/includes/js/jquery.timepicker.min.js',
			array( 'jquery' ),
			false,
			true
		);
	}

	/**
	 * name_changed_notice.
	 *
	 * @version 2.2.4
	 * @since   2.2.4
	 */
	public function name_changed_notice() {

		if ( ! is_admin() ) return;

		//require_once( ABSPATH . 'wp-includes/pluggable.php' );
		if ( ! is_super_admin() ) return;

		$user_id = get_current_user_id();

		// Reset
//		update_option( 'wcj_rename_message_hidden', 'no' );
//		update_user_meta( $user_id, 'wcj_rename_message_hidden', 'no' );

		if ( isset( $_GET['wcj_rename_message_hide'] ) ) {
			// Hide message for current user
			update_user_meta( $user_id, 'wcj_rename_message_hidden', 'yes' );
//			update_option( 'wcj_rename_message_hidden', 'yes' );
		} else {
			$is_message_hidden = get_user_meta( $user_id, 'wcj_rename_message_hidden', true );
//			$is_message_hidden = get_option( 'wcj_rename_message_hidden', 'no' );
			if ( 'yes' != $is_message_hidden ) {
				// Show message
				$class = 'update-nag';
				$message = __( '<strong>WooCommerce Jetpack</strong> plugin changed its name to <strong>Booster for WooCommerce</strong>.', 'woocommerce-jetpack' );
				$button = '<a href="' . add_query_arg( 'wcj_rename_message_hide', '1' ) . '">'
							. '<button>' . __( 'Got it! Hide this message', 'woocommerce-jetpack' ) . '</button>'
							. '</a>';
				echo '<div class="' . $class . '"><p>' . $message . '</p><p>' . $button . '</p></div>';
			}
		}
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 2.2.5
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'wcj-datepicker', $this->plugin_url() . '/includes/js/wcj-datepicker.js' );
		wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
	}

	/**
	 * admin_footer_text
	 *
	public function admin_footer_text( $footer_text ) {

		if ( isset( $_GET['page'] ) ) {
			if ( 'wcj-tools' === $_GET['page'] || ( 'wc-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'jetpack' === $_GET['tab'] ) ) {
				return sprintf( __( 'If you like <strong>WooCommerce Jetpack</strong> please leave us a <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating on <a href="%1$s" target="_blank">WordPress.org</a>. We will be grateful for any help!', 'woocommerce-jetpack' ), 'https://wordpress.org/support/view/plugin-reviews/woocommerce-jetpack?filter=5#postform' );
			}
		}

		return $footer_text;
	}

	/**
	 * Add menu item
	 *
	 * @version 2.2.4
	 */
	public function jetpack_menu() {
		add_submenu_page( 'woocommerce', __( 'Booster for WooCommerce', 'woocommerce' ),  __( 'Booster Settings', 'woocommerce' ) , 'manage_woocommerce', 'admin.php?page=wc-settings&tab=jetpack' );
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @version 2.3.8
	 * @param   mixed $links
	 * @return  array
	 */
	public function action_links( $links ) {
		return array_merge( array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
			'<a href="' . esc_url( 'http://booster.io/' )                       . '">' . __( 'Docs', 'woocommerce' ) . '</a>',
			'<a href="' . esc_url( 'http://booster.io/plus/' )                  . '">' . __( 'Unlock all', 'woocommerce' ) . '</a>',
		), $links );
	}

	/**
	 * get_wcj_plus_message.
	 *
	 * @version 2.3.8
	 */
	public function get_wcj_plus_message( $value, $message_type ) {

		switch ( $message_type ) {

			case 'global':
				return '<div class="updated">
							<p class="main"><strong>' . __( 'Install Booster Plus to unlock all features', 'woocommerce-jetpack' ) . '</strong></p>
							<span>' . sprintf( __('Some settings fields are locked and you will need %s to modify all locked fields.', 'woocommerce-jetpack'), '<a href="http://booster.io/plus/">Booster for WooCommerce Plus</a>' ) . '</span>
							<p><a href="http://booster.io/plus/" target="_blank" class="button button-primary">' . __( 'Buy now', 'woocommerce-jetpack' ) . '</a> <a href="http://booster.io" target="_blank" class="button">'. sprintf( __( 'Visit Booster Site', 'woocommerce-jetpack' ), 'http://booster.io' ) . '</a></p>
						</div>';

			case 'desc':
				return __( 'Get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to change value.', 'woocommerce-jetpack' );

			case 'desc_below':
				return __( 'Get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to change values below.', 'woocommerce-jetpack' );

			case 'desc_above':
				return __( 'Get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to change values above.', 'woocommerce-jetpack' );

			case 'desc_no_link':
				return __( 'Get Booster Plus to change value.', 'woocommerce-jetpack' );

			case 'readonly':
				return array( 'readonly' => 'readonly' );

			case 'disabled':
				return array( 'disabled' => 'disabled' );

			case 'readonly_string':
				return 'readonly';

			case 'disabled_string':
				return 'disabled';
		}

		return $value;
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {

		// Functions
		$this->include_functions();

		// Classes
		include_once( 'includes/classes/class-wcj-module.php' );
		include_once( 'includes/classes/class-wcj-product.php' );
		include_once( 'includes/classes/class-wcj-invoice.php' );
		include_once( 'includes/classes/class-wcj-pdf-invoice.php' );

		// Tools
		include_once( 'includes/admin/class-wcj-tools.php' );

		// Shortcodes
		$this->include_shortcodes();

		// Abstracts
		//include_once( 'includes/abstracts/class-wcj-product-input-fields.php' );

		// Modules and Submodules
		$this->include_modules();
	}

	/**
	 * include_functions.
	 */
	private function include_functions() {
		// Functions
		include_once( 'includes/functions/wcj-debug-functions.php' );
		include_once( 'includes/functions/wcj-functions.php' );
		include_once( 'includes/functions/wcj-html-functions.php' );
		include_once( 'includes/functions/wcj-country-functions.php' );
		include_once( 'includes/functions/wcj-invoicing-functions.php' );
	}

	/**
	 * include_shortcodes.
	 */
	private function include_shortcodes() {
		// Shortcodes
		include_once( 'includes/shortcodes/class-wcj-shortcodes.php' );
		include_once( 'includes/shortcodes/class-wcj-general-shortcodes.php' );
		include_once( 'includes/shortcodes/class-wcj-invoices-shortcodes.php' );
		include_once( 'includes/shortcodes/class-wcj-orders-shortcodes.php' );
		include_once( 'includes/shortcodes/class-wcj-order-items-shortcodes.php' );
		include_once( 'includes/shortcodes/class-wcj-products-shortcodes.php' );
	}

	/**
	 * Include modules and submodules
	 *
	 * @version 2.3.9
	 */
	private function include_modules() {
		$settings = array();

		$settings[] = include_once( 'includes/class-wcj-price-labels.php' );
		$settings[] = include_once( 'includes/class-wcj-call-for-price.php' );

		$settings[] = include_once( 'includes/class-wcj-product-listings.php' );
		$settings[] = include_once( 'includes/class-wcj-sorting.php' );
		$settings[] = include_once( 'includes/class-wcj-product-info.php' );
		$settings[] = include_once( 'includes/class-wcj-product-add-to-cart.php' );
		$settings[] = include_once( 'includes/class-wcj-related-products.php' );
		$settings[] = include_once( 'includes/class-wcj-sku.php' );
		$settings[] = include_once( 'includes/class-wcj-product-tabs.php' );
		$settings[] = include_once( 'includes/class-wcj-product-input-fields.php' );
		$settings[] = include_once( 'includes/class-wcj-product-bulk-price-converter.php' );
		$settings[] = include_once( 'includes/class-wcj-purchase-data.php' );
		$settings[] = include_once( 'includes/class-wcj-crowdfunding.php' );
		$settings[] = include_once( 'includes/class-wcj-wholesale-price.php' );
		$settings[] = include_once( 'includes/class-wcj-product-images.php' );

		$settings[] = include_once( 'includes/class-wcj-add-to-cart.php' );
		$settings[] = include_once( 'includes/class-wcj-more-button-labels.php' );

		$settings[] = include_once( 'includes/class-wcj-cart.php' );
		$settings[] = include_once( 'includes/class-wcj-empty-cart-button.php' );
		$settings[] = include_once( 'includes/class-wcj-mini-cart.php' );
		$settings[] = include_once( 'includes/class-wcj-checkout-core-fields.php' );
		$settings[] = include_once( 'includes/class-wcj-checkout-custom-fields.php' );
		$settings[] = include_once( 'includes/class-wcj-checkout-custom-info.php' );
		$settings[] = include_once( 'includes/class-wcj-payment-gateways.php' );
		$settings[] = include_once( 'includes/class-wcj-payment-gateways-icons.php' );
		$settings[] = include_once( 'includes/class-wcj-payment-gateways-fees.php' );
		$settings[] = include_once( 'includes/class-wcj-payment-gateways-per-category.php' );
		$settings[] = include_once( 'includes/class-wcj-payment-gateways-currency.php' );

		$settings[] = include_once( 'includes/class-wcj-shipping.php' );
		$settings[] = include_once( 'includes/class-wcj-shipping-calculator.php' );

		$settings[] = include_once( 'includes/class-wcj-address-formats.php' );

		$settings[] = include_once( 'includes/class-wcj-orders.php' );
		$settings[] = include_once( 'includes/class-wcj-order-numbers.php' );
		$settings[] = include_once( 'includes/class-wcj-order-custom-statuses.php' );

		$settings[] = include_once( 'includes/class-wcj-pdf-invoices.php' );

		$settings[] = include_once( 'includes/class-wcj-pdf-invoicing.php' );
		$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-numbering.php' );
		$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-templates.php' );
		$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-styling.php' );
		$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-header.php' );
		$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-footer.php' );
		$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-page.php' );
		$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-emails.php' );
		$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-display.php' );
		//$settings[] = include_once( 'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-general.php' );

		$settings[] = include_once( 'includes/class-wcj-emails.php' );

		$settings[] = include_once( 'includes/class-wcj-currencies.php' );
		$settings[] = include_once( 'includes/class-wcj-currency-external-products.php' );
		$settings[] = include_once( 'includes/class-wcj-price-by-country.php' );
		$settings[] = include_once( 'includes/class-wcj-currency-exchange-rates.php' );

		$settings[] = include_once( 'includes/class-wcj-general.php' );
		$settings[] = include_once( 'includes/class-wcj-eu-vat-number.php' );
		$settings[] = include_once( 'includes/class-wcj-old-slugs.php' );
		$settings[] = include_once( 'includes/class-wcj-reports.php' );
		$settings[] = include_once( 'includes/class-wcj-admin-tools.php' );
		$settings[] = include_once( 'includes/class-wcj-wpml.php' );

//		do_action( 'woojetpack_modules', $settings );

		// Add options
		if ( is_admin() ) {
			foreach ( $settings as $section ) {

				$values = $section->get_settings();

				// Modules statuses
				$submodules_classes = array(
					'WCJ_PDF_Invoicing_Display',
					'WCJ_PDF_Invoicing_Emails',
					'WCJ_PDF_Invoicing_Footer',
					'WCJ_PDF_Invoicing_Header',
					'WCJ_PDF_Invoicing_Numbering',
					'WCJ_PDF_Invoicing_Page',
					'WCJ_PDF_Invoicing_Styling',
					'WCJ_PDF_Invoicing_Templates',
				);
				if ( ! in_array( get_class( $section ), $submodules_classes ) ) {
					$this->module_statuses[] = $values[1];
				}

				// Adding options
				foreach ( $values as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {

						// Admin reset
						if ( isset ( $_GET['woojetpack_admin_options_reset'] ) ) {
							require_once( ABSPATH . 'wp-includes/pluggable.php' );
							if ( is_super_admin() ) {
								delete_option( $value['id'] );
							}
						}

						// Finally adding options
						$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );

						/* if ( $this->is_wpml_value( $section, $value ) ) {
							$wpml_keys[] = $value['id'];
						} */
					}
				}
			}
		}
	}

	/**
	 * Add Jetpack settings tab to WooCommerce settings.
	 */
	public function add_wcj_settings_tab( $settings ) {
		$the_settings = include( 'includes/admin/class-wc-settings-jetpack.php' );
		$the_settings->add_module_statuses( $this->module_statuses );
		$settings[] = $the_settings;
		return $settings;
	}

	/**
	 * Init WC_Jetpack when WordPress initialises.
	 */
	public function init() {
		// Before init action
		do_action( 'before_wcj_init' );
		// Set up localisation
		load_plugin_textdomain( 'woocommerce-jetpack',  false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
		// Init action
		do_action( 'wcj_init' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

endif;

/**
 * Returns the main instance of WC_Jetpack to prevent the need to use globals.
 *
 * @return WC_Jetpack
 */
function WCJ() {
	return WC_Jetpack::instance();
}

WCJ();

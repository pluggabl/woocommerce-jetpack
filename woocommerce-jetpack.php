<?php
/*
Plugin Name: Booster for WooCommerce
Plugin URI: http://booster.io
Description: Supercharge your WooCommerce site with these awesome powerful features.
Version: 2.5.4
Author: Algoritmika Ltd
Author URI: http://www.algoritmika.com
Text Domain: woocommerce-jetpack
Domain Path: /langs
Copyright: © 2016 Algoritmika Ltd.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Check if WooCommerce is active
$plugin = 'woocommerce/woocommerce.php';
if (
	! in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) &&
	! ( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
) return;

if ( ! class_exists( 'WC_Jetpack' ) ) :

/**
 * Main WC_Jetpack Class
 *
 * @class   WC_Jetpack
 * @version 2.5.4
 */

final class WC_Jetpack {

	/**
	 * WooCommerce Jetpack version.
	 *
	 * @var   string
	 * @since 2.4.7
	 */
	public $version = '2.5.4';

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
	 */
	/* public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '3.9.1' );
	} */

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	/* public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '3.9.1' );
	} */

	/**
	 * WC_Jetpack Constructor.
	 *
	 * @version 2.5.3
	 * @access public
	 */
	public function __construct() {

//		require_once( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );

		// Include required files
		$this->includes();

		//register_activation_hook( __FILE__, array( $this, 'install' ) );
//		add_action( 'admin_init', array( $this, 'install' ) );
		add_action( 'init', array( $this, 'init' ), 0 );

		// Settings
		if ( is_admin() ) {
			add_filter( 'woocommerce_get_settings_pages',                     array( $this, 'add_wcj_settings_tab' ), PHP_INT_MAX );
			add_filter( 'get_wc_jetpack_plus_message',                        array( $this, 'get_wcj_plus_message' ), 100, 2 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
			add_action( 'admin_menu',                                         array( $this, 'jetpack_menu' ), 100 );
			add_filter( 'admin_footer_text',                                  array( $this, 'admin_footer_text' ), 2 );
//			add_action( 'admin_notices',                                      array( $this, 'name_changed_notice' ) );
		}

		// Scripts
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_wcj_backend_scripts' ) );
			if (
				'yes' === get_option( 'wcj_purchase_data_enabled' ) ||
				'yes' === get_option( 'wcj_pdf_invoicing_enabled' ) ||
				'yes' === get_option( 'wcj_crowdfunding_enabled' )
			) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts' ) );
			}
		}

		if (
			'yes' === get_option( 'wcj_product_input_fields_enabled' ) ||
			'yes' === get_option( 'wcj_checkout_custom_fields_enabled' ) ||
			'yes' === get_option( 'wcj_product_bookings_enabled' )
		){
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		}

		if ( wcj_is_module_enabled( 'general' ) && 'yes' === get_option( 'wcj_general_advanced_recalculate_cart_totals', 'no' ) ) {
			add_action( 'wp_loaded', array( $this, 'fix_mini_cart' ), PHP_INT_MAX );
		}

		add_action( 'wp_loaded', array( $this, 'manage_options' ), PHP_INT_MAX );

		// Loaded action
		do_action( 'wcj_loaded' );
	}

	/**
	 * enqueue_wcj_backend_scripts.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function enqueue_wcj_backend_scripts() {
		wp_enqueue_style( 'wcj-admin', wcj_plugin_url() . '/includes/css/wcj-admin.css' );
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
	 * fix_mini_cart.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 * @todo    this is only temporary solution!
	 */
	function fix_mini_cart() {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			if ( null !== ( $wc = WC() ) ) {
				if ( isset( $wc->cart ) ) {
					$wc->cart->calculate_totals();
				}
			}
		}
	}

	/**
	 * enqueue_frontend_scripts.
	 *
	 * @version 2.4.0
	 * @since   2.3.0
	 */
	function enqueue_frontend_scripts() {
		$this->maybe_enqueue_datepicker_scripts();
		$this->maybe_enqueue_timepicker_scripts();
		$this->maybe_enqueue_datepicker_style();
		$this->maybe_enqueue_timepicker_style();
	}

	/**
	 * enqueue_backend_scripts.
	 *
	 * @version 2.4.0
	 */
	public function enqueue_backend_scripts() {
		$this->maybe_enqueue_datepicker_scripts();
		$this->maybe_enqueue_datepicker_style();
	}

	/**
	 * maybe_enqueue_datepicker_scripts.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function maybe_enqueue_datepicker_scripts() {
		if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === get_option( 'wcj_general_advanced_disable_datepicker_js', 'no' ) ) ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'wcj-datepicker', wcj_plugin_url() . '/includes/js/wcj-datepicker.js',
				array( 'jquery' ),
				false,
				true );
			wp_enqueue_script( 'wcj-weekpicker', wcj_plugin_url() . '/includes/js/wcj-weekpicker.js',
				array( 'jquery' ),
				false,
				true );
		}
	}

	/**
	 * maybe_enqueue_timepicker_scripts.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function maybe_enqueue_timepicker_scripts() {
		if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === get_option( 'wcj_general_advanced_disable_timepicker_js', 'no' ) ) ) {
			wp_enqueue_script( 'jquery-ui-timepicker',
				wcj_plugin_url() . '/includes/js/jquery.timepicker.min.js',
				array( 'jquery' ),
				false,
				true );
			wp_enqueue_script( 'wcj-timepicker', wcj_plugin_url() . '/includes/js/wcj-timepicker.js',
				array( 'jquery' ),
				false,
				true );
		}
	}

	/**
	 * maybe_enqueue_datepicker_style.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function maybe_enqueue_datepicker_style() {
		if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === get_option( 'wcj_general_advanced_disable_datepicker_css', 'no' ) ) ) {
			$datepicker_css_path = '//ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css';
			if ( wcj_is_module_enabled( 'general' ) ) {
				$datepicker_css_path = get_option( 'wcj_general_advanced_datepicker_css', $datepicker_css_path );
			}
			wp_enqueue_style( 'jquery-ui-css', $datepicker_css_path );
		}
	}

	/**
	 * maybe_enqueue_timepicker_style.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function maybe_enqueue_timepicker_style() {
		if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === get_option( 'wcj_general_advanced_disable_timepicker_css', 'no' ) ) ) {
			wp_enqueue_style( 'wcj-timepicker-css', wcj_plugin_url() . '/includes/css/jquery.timepicker.min.css' );
		}
	}

	/**
	 * name_changed_notice.
	 *
	 * @version 2.2.4
	 * @since   2.2.4
	 */
	/* public function name_changed_notice() {

		if ( ! is_admin() ) return;

		//require_once( ABSPATH . 'wp-includes/pluggable.php' );
		if ( ! wcj_is_user_role( 'administrator' ) ) return;

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
	} */

	/**
	 * admin_footer_text
	 *
	 * @version 2.5.3
	 */
	function admin_footer_text( $footer_text ) {
		if ( isset( $_GET['page'] ) ) {
			if ( 'wcj-tools' === $_GET['page'] || ( 'wc-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'jetpack' === $_GET['tab'] ) ) {
				/* $rocket_icons = '';
				for ( $i = 0; $i < 5; $i++ ) {
					$rocket_icons .= wcj_get_rocket_icon();
				} */
				$rocket_icons = wcj_get_5_rocket_image();
				$rating_link = '<a href="https://wordpress.org/support/view/plugin-reviews/woocommerce-jetpack?filter=5#postform" target="_blank">' . $rocket_icons . '</a>';
				return sprintf(
					__( 'If you like <strong>Booster for WooCommerce</strong> please leave us a %s rating. Thank you, we couldn\'t have done it without you!', 'woocommerce-jetpack' ),
					$rating_link
				);
			}
		}
		return $footer_text;
	}

	/**
	 * Add menu item
	 *
	 * @version 2.5.0
	 */
	public function jetpack_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Booster for WooCommerce', 'woocommerce-jetpack' ),
			__( 'Booster Settings', 'woocommerce-jetpack' ) ,
			'manage_woocommerce',
			'admin.php?page=wc-settings&tab=jetpack'
		);
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @version 2.5.2
	 * @param   mixed $links
	 * @return  array
	 */
	public function action_links( $links ) {
		$custom_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
			'<a href="' . esc_url( 'http://booster.io/' )                       . '">' . __( 'Docs', 'woocommerce' ) . '</a>',
		);
		if ( 1 === apply_filters( 'wcj_get_option_filter', 1, '' ) ) {
			$custom_links[] = '<a href="' . esc_url( 'http://booster.io/plus/' ) . '">' . __( 'Unlock all', 'woocommerce' ) . '</a>';
		}
		return array_merge( $custom_links, $links );
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
	 *
	 * @version 2.4.8
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

		// Widgets
		include_once( 'includes/widgets/class-wcj-widget-multicurrency.php' );
		include_once( 'includes/widgets/class-wcj-widget-country-switcher.php' );
		include_once( 'includes/widgets/class-wcj-widget-left-to-free-shipping.php' );

		// Abstracts
//		include_once( 'includes/abstracts/class-wcj-product-input-fields.php' );

		// Modules and Submodules
		$this->include_modules();
	}

	/**
	 * include_functions.
	 *
	 * @version 2.5.0
	 */
	private function include_functions() {
		include_once( 'includes/functions/wcj-debug-functions.php' );
		include_once( 'includes/functions/wcj-functions.php' );
		include_once( 'includes/functions/wcj-functions-number-to-words.php' );
		include_once( 'includes/functions/wcj-functions-number-to-words-bg.php' );
		include_once( 'includes/functions/wcj-html-functions.php' );
		include_once( 'includes/functions/wcj-country-functions.php' );
		include_once( 'includes/functions/wcj-invoicing-functions.php' );

		include_once( 'includes/currencies/wcj-currencies.php' );
	}

	/**
	 * include_shortcodes.
	 *
	 * @version 2.5.4
	 */
	private function include_shortcodes() {
		if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === get_option( 'wcj_general_shortcodes_disable_booster_shortcodes', 'no' ) ) ) {
			include_once( 'includes/shortcodes/class-wcj-shortcodes.php' );
			include_once( 'includes/shortcodes/class-wcj-general-shortcodes.php' );
			include_once( 'includes/shortcodes/class-wcj-invoices-shortcodes.php' );
			include_once( 'includes/shortcodes/class-wcj-orders-shortcodes.php' );
			include_once( 'includes/shortcodes/class-wcj-order-items-shortcodes.php' );
			include_once( 'includes/shortcodes/class-wcj-products-shortcodes.php' );
			include_once( 'includes/shortcodes/class-wcj-products-crowdfunding-shortcodes.php' );
			include_once( 'includes/shortcodes/class-wcj-products-add-form-shortcodes.php' );
			include_once( 'includes/shortcodes/class-wcj-input-field-shortcodes.php' );
		}
	}

	/**
	 * Include modules and submodules
	 *
	 * @version 2.5.4
	 */
	function include_modules() {
		$modules_files = array(
			'includes/class-wcj-price-labels.php',
			'includes/class-wcj-call-for-price.php',
			'includes/class-wcj-product-listings.php',
			'includes/class-wcj-sorting.php',
			'includes/class-wcj-product-custom-info.php',
			'includes/class-wcj-product-info.php',
			'includes/class-wcj-product-add-to-cart.php',
			'includes/class-wcj-related-products.php',
			'includes/class-wcj-sku.php',
			'includes/class-wcj-product-tabs.php',
			'includes/class-wcj-product-input-fields.php',
			'includes/class-wcj-product-bulk-price-converter.php',
			'includes/class-wcj-purchase-data.php',
			'includes/class-wcj-product-bookings.php',
			'includes/class-wcj-crowdfunding.php',
			'includes/class-wcj-product-addons.php',
			'includes/class-wcj-wholesale-price.php',
			'includes/class-wcj-product-open-pricing.php',
			'includes/class-wcj-price-by-user-role.php',
			'includes/class-wcj-product-price-by-formula.php',
			'includes/class-wcj-product-images.php',
			'includes/class-wcj-product-by-country.php',
			'includes/class-wcj-product-by-user.php',
			'includes/class-wcj-add-to-cart.php',
			'includes/class-wcj-more-button-labels.php',
			'includes/class-wcj-cart.php',
			'includes/class-wcj-empty-cart-button.php',
			'includes/class-wcj-mini-cart.php',
			'includes/class-wcj-checkout-core-fields.php',
			'includes/class-wcj-checkout-custom-fields.php',
			'includes/class-wcj-checkout-files-upload.php',
			'includes/class-wcj-checkout-custom-info.php',
			'includes/class-wcj-payment-gateways.php',
			'includes/class-wcj-payment-gateways-icons.php',
			'includes/class-wcj-payment-gateways-fees.php',
			'includes/class-wcj-payment-gateways-per-category.php',
			'includes/class-wcj-payment-gateways-currency.php',
			'includes/class-wcj-payment-gateways-min-max.php',
			'includes/class-wcj-payment-gateways-by-country.php',
			'includes/class-wcj-payment-gateways-by-user-role.php',
			'includes/class-wcj-shipping.php',
			'includes/class-wcj-shipping-calculator.php',
			'includes/class-wcj-address-formats.php',
			'includes/class-wcj-orders.php',
			'includes/class-wcj-order-numbers.php',
			'includes/class-wcj-order-custom-statuses.php',
//			'includes/class-wcj-pdf-invoices.php',
			'includes/class-wcj-pdf-invoicing.php',
			'includes/class-wcj-emails.php',
			'includes/class-wcj-currencies.php',
			'includes/class-wcj-multicurrency.php',
			'includes/class-wcj-multicurrency-product-base-price.php',
			'includes/class-wcj-currency-per-product.php',
			'includes/class-wcj-currency-external-products.php',
			'includes/class-wcj-price-by-country.php',
			'includes/class-wcj-currency-exchange-rates.php',
			'includes/class-wcj-price-formats.php',
			'includes/class-wcj-general.php',
			'includes/class-wcj-export-import.php',
//			'includes/class-wcj-shortcodes-module.php',
			'includes/class-wcj-eu-vat-number.php',
			'includes/class-wcj-old-slugs.php',
			'includes/class-wcj-reports.php',
			'includes/class-wcj-admin-tools.php',
			'includes/class-wcj-wpml.php',
			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-numbering.php',
			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-templates.php',
			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-styling.php',
			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-header.php',
			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-footer.php',
			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-page.php',
			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-emails.php',
			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-display.php',
//			'includes/pdf-invoices/settings/class-wcj-pdf-invoicing-general.php',
		);

		$this->modules = array();
		foreach ( $modules_files as $module_file ) {
			$module = include_once( $module_file );
			$this->modules[ $module->id ] = $module;
		}

//		do_action( 'woojetpack_modules', $settings );

		// Add and Manage options
		if ( is_admin() ) {
			$this->add_options();
		}
	}

	/**
	 * add_options.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_options() {

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

		foreach ( $this->modules as $module ) {

			if ( ! in_array( get_class( $module ), $submodules_classes ) ) {
				$status_settings = $module->add_enable_module_setting( array() );
				$this->module_statuses[] = $status_settings[1];
			}

			if ( get_option( 'booster_for_woocommerce_version' ) === $this->version ) {
				continue;
			}

			$values = $module->get_settings();

			// Adding options
			foreach ( $values as $value ) {
				if ( isset( $value['default'] ) && isset( $value['id'] ) ) {

					$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
					add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );

					/* if ( $this->is_wpml_value( $module, $value ) ) {
						$wpml_keys[] = $value['id'];
					} */
				}
			}
		}

		if ( get_option( 'booster_for_woocommerce_version' ) !== $this->version ) {
			update_option( 'booster_for_woocommerce_version', $this->version );
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
	 * @version 2.5.3
	 * @since   2.5.2
	 */
	function manage_options_export() {
		$export_settings = array();
		$export_counter = array();
		foreach ( $this->modules as $module ) {
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
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function manage_options_reset() {
		global $wcj_notice;
		$delete_counter = 0;
		foreach ( $this->modules as $module ) {
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

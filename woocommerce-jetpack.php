<?php
/*
Plugin Name: Booster for WooCommerce
Plugin URI: https://booster.io
Description: Supercharge your WooCommerce site with these awesome powerful features.
Version: 3.1.3-dev
Author: Algoritmika Ltd
Author URI: https://booster.io
Text Domain: woocommerce-jetpack
Domain Path: /langs
Copyright: © 2017 Algoritmika Ltd.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'wcj_is_plugin_active' ) ) {
	/**
	 * wcj_is_plugin_active.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @return  bool
	 */
	function wcj_is_plugin_active( $plugin ) {
		return (
			in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
			( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
		);
	}
}

// Check if WooCommerce is active
if ( ! wcj_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}

// Check if Plus is active
if ( 'woocommerce-jetpack.php' === basename( __FILE__ ) ) {
	if ( wcj_is_plugin_active( 'booster-plus-for-woocommerce/booster-plus-for-woocommerce.php' ) ) {
		return;
	}
}

if ( ! class_exists( 'WC_Jetpack' ) ) :

if ( ! function_exists( 'wcj_plugin_file' ) ) {
	/**
	 * wcj_plugin_file.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function wcj_plugin_file() {
		return __FILE__;
	}
}

// Constants
require_once( 'includes/constants/wcj-constants.php' );

/**
 * Main WC_Jetpack Class
 *
 * @class   WC_Jetpack
 * @version 3.1.3
 */
final class WC_Jetpack {

	/**
	 * Booster for WooCommerce version.
	 *
	 * @var   string
	 * @since 2.4.7
	 */
	public $version = '3.1.3-dev-201709241626';

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
	 * @see    WCJ()
	 * @return WC_Jetpack - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * WC_Jetpack Constructor.
	 *
	 * @version 3.0.0
	 * @access  public
	 */
	function __construct() {

		// Set up localisation
		load_plugin_textdomain( 'woocommerce-jetpack', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );

		// Include required files
		$this->includes();

		// Settings
		$this->init_settings();

		// Scripts
		require_once( 'includes/classes/class-wcj-scripts.php' );

		// Settings manager
		require_once( 'includes/admin/class-wcj-settings-manager.php' );

		// Loaded action
		do_action( 'wcj_loaded' );
	}

	/**
	 * init_settings.
	 *
	 * @version 3.0.1
	 * @since   2.9.0
	 */
	function init_settings() {
		if ( is_admin() ) {
			add_filter( 'woocommerce_get_settings_pages',                     array( $this, 'add_wcj_settings_tab' ), 1 );
			add_filter( 'booster_get_message',                                'wcj_get_plus_message', 100, 3 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
			add_action( 'admin_menu',                                         array( $this, 'booster_menu' ), 100 );
			add_filter( 'admin_footer_text',                                  array( $this, 'admin_footer_text' ), 2 );
			if ( 'woocommerce-jetpack.php' === basename( __FILE__ ) ) {
				add_action( 'admin_notices',                                  array( $this, 'check_plus_version' ) );
			}
		}
	}

	/**
	 * check_plus_version.
	 *
	 * @version 3.1.1
	 * @since   2.5.9
	 */
	function check_plus_version() {
		if ( ! is_admin() ) {
			return;
		}
		// Check if Plus is installed and activated
		$is_plus_active    = false;
		$is_plus_v3_active = false;
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}
		foreach ( $active_plugins as $active_plugin ) {
			$active_plugin = explode( '/', $active_plugin );
			if ( isset( $active_plugin[1] ) ) {
				if ( 'booster-plus-for-woocommerce.php' === $active_plugin[1] ) {
					$is_plus_v3_active = true;
					break;
				} elseif ( 'woocommerce-jetpack-plus.php' === $active_plugin[1] || 'woocommerce-booster-plus.php' === $active_plugin[1] ) {
					$is_plus_active = true;
				}
			}
		}
		// Check Plus version
		if ( ! $is_plus_v3_active && $is_plus_active ) {
			$plus_version          = get_option( 'booster_plus_version', false );
			$required_plus_version = '1.1.0';
			$notice_type           = ( version_compare( $plus_version, $required_plus_version, '<' ) ? 'error' : 'warning' );
			global $pagenow;
			if ( 'error' === $notice_type || 'plugins.php' === $pagenow ) {
				$class   = 'notice notice-' . $notice_type;
				$message = ( 'error' === $notice_type ?
					sprintf(
						__( 'Please upgrade <strong>Booster Plus for WooCommerce</strong> plugin. Visit <a target="_blank" href="%s">your account page</a> on booster.io to download the latest Booster Plus version.', 'woocommerce-jetpack' ),
						'https://booster.io/my-account/?utm_source=plus_update'
					) :
					sprintf(
						__( 'There is new version of <strong>Booster Plus for WooCommerce</strong> plugin available. We recommend upgrading. Please visit <a target="_blank" href="%s">your account page</a> on booster.io to download the latest Booster Plus version.', 'woocommerce-jetpack' ),
						'https://booster.io/my-account/?utm_source=plus_update'
					)
				);
				echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
			}
		}
	}

	/**
	 * admin_footer_text
	 *
	 * @version 2.9.0
	 */
	function admin_footer_text( $footer_text ) {
		if ( isset( $_GET['page'] ) ) {
			if ( 'wcj-tools' === $_GET['page'] || ( 'wc-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'jetpack' === $_GET['tab'] ) ) {
				$rocket_icons = wcj_get_5_rocket_image();
				$rating_link = '<a href="https://wordpress.org/support/plugin/woocommerce-jetpack/reviews/?rate=5#new-post" target="_blank">' . $rocket_icons . '</a>';
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
	 * @version 2.9.0
	 */
	function booster_menu() {
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
	function action_links( $links ) {
		$custom_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
			'<a href="' . esc_url( 'https://booster.io/' )                      . '">' . __( 'Docs', 'woocommerce' ) . '</a>',
		);
		if ( 1 === apply_filters( 'booster_get_option', 1, '' ) ) {
			$custom_links[] = '<a href="' . esc_url( 'https://booster.io/plus/' ) . '">' . __( 'Unlock all', 'woocommerce' ) . '</a>';
		}
		return array_merge( $custom_links, $links );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @version 3.1.0
	 */
	function includes() {

		// Functions
		$this->include_functions();

		// Classes
		include_once( 'includes/classes/class-wcj-module.php' );
		include_once( 'includes/classes/class-wcj-product.php' );
		include_once( 'includes/classes/class-wcj-invoice.php' );
		include_once( 'includes/classes/class-wcj-pdf-invoice.php' );

		// Plus
		if ( 'booster-plus-for-woocommerce.php' === basename( __FILE__ ) ) {
			require_once( 'includes/plus/class-wcj-plus.php' );
		}

		// Tools
		include_once( 'includes/admin/class-wcj-tools.php' );

		// Shortcodes
		$this->include_shortcodes();

		// Widgets
		include_once( 'includes/widgets/class-wcj-widget.php' );
		include_once( 'includes/widgets/class-wcj-widget-multicurrency.php' );
		include_once( 'includes/widgets/class-wcj-widget-country-switcher.php' );
		include_once( 'includes/widgets/class-wcj-widget-left-to-free-shipping.php' );
		include_once( 'includes/widgets/class-wcj-widget-selector.php' );

		// Modules and Submodules
		$this->include_modules();
	}

	/**
	 * include_functions.
	 *
	 * @version 2.9.0
	 */
	function include_functions() {
		include_once( 'includes/functions/wcj-core-functions.php' );
		include_once( 'includes/functions/wcj-debug-functions.php' );
		include_once( 'includes/functions/wcj-admin-functions.php' );
		include_once( 'includes/functions/wcj-general-functions.php' );
		include_once( 'includes/functions/wcj-date-time-functions.php' );
		include_once( 'includes/functions/wcj-product-functions.php' );
		include_once( 'includes/functions/wcj-order-functions.php' );
		include_once( 'includes/functions/wcj-eu-vat-functions.php' );
		include_once( 'includes/functions/wcj-price-currency-functions.php' );
		include_once( 'includes/functions/wcj-user-roles-functions.php' );
		include_once( 'includes/functions/wcj-exchange-rates-functions.php' );
		include_once( 'includes/functions/wcj-functions-number-to-words.php' );
		include_once( 'includes/functions/wcj-functions-number-to-words-bg.php' );
		include_once( 'includes/functions/wcj-functions-number-to-words-lt.php' );
		include_once( 'includes/functions/wcj-html-functions.php' );
		include_once( 'includes/functions/wcj-country-functions.php' );
		include_once( 'includes/functions/wcj-invoicing-functions.php' );
		include_once( 'includes/functions/wcj-reports-functions.php' );
		include_once( 'includes/functions/wcj-currencies.php' );
	}

	/**
	 * include_shortcodes.
	 *
	 * @version 2.5.4
	 */
	function include_shortcodes() {
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
	 * @version 3.1.3
	 */
	function include_modules() {
		$modules_files = array(
			'includes/class-wcj-admin-tools.php',
			'includes/class-wcj-price-labels.php',
			'includes/class-wcj-call-for-price.php',
			'includes/class-wcj-free-price.php',
			'includes/class-wcj-product-listings.php',
			'includes/class-wcj-products-per-page.php',
			'includes/class-wcj-sorting.php',
			'includes/class-wcj-product-custom-info.php',
			'includes/class-wcj-product-info.php',
			'includes/class-wcj-product-add-to-cart.php',
			'includes/class-wcj-related-products.php',
			'includes/class-wcj-sku.php',
			'includes/class-wcj-stock.php',
			'includes/class-wcj-product-tabs.php',
			'includes/class-wcj-product-input-fields.php',
			'includes/class-wcj-product-bulk-price-converter.php',
			'includes/class-wcj-product-bulk-meta-editor.php',
			'includes/class-wcj-purchase-data.php',
			'includes/class-wcj-product-bookings.php',
			'includes/class-wcj-crowdfunding.php',
			'includes/class-wcj-product-addons.php',
			'includes/class-wcj-wholesale-price.php',
			'includes/class-wcj-product-open-pricing.php',
			'includes/class-wcj-offer-price.php',
			'includes/class-wcj-price-by-user-role.php',
			'includes/class-wcj-global-discount.php',
			'includes/class-wcj-product-price-by-formula.php',
			'includes/class-wcj-product-images.php',
			'includes/class-wcj-product-by-country.php',
			'includes/class-wcj-product-by-time.php',
			'includes/class-wcj-product-by-date.php',
			'includes/class-wcj-product-by-user-role.php',
			'includes/class-wcj-product-by-user.php',
			'includes/class-wcj-add-to-cart.php',
			'includes/class-wcj-more-button-labels.php',
			'includes/class-wcj-cart.php',
			'includes/class-wcj-cart-customization.php',
			'includes/class-wcj-empty-cart-button.php',
			'includes/class-wcj-mini-cart.php',
			'includes/class-wcj-checkout-core-fields.php',
			'includes/class-wcj-checkout-custom-fields.php',
			'includes/class-wcj-checkout-files-upload.php',
			'includes/class-wcj-checkout-custom-info.php',
			'includes/class-wcj-checkout-customization.php',
			'includes/class-wcj-payment-gateways.php',
			'includes/class-wcj-payment-gateways-icons.php',
			'includes/class-wcj-payment-gateways-fees.php',
			'includes/class-wcj-payment-gateways-per-category.php',
			'includes/class-wcj-payment-gateways-currency.php',
			'includes/class-wcj-payment-gateways-by-currency.php',
			'includes/class-wcj-payment-gateways-min-max.php',
			'includes/class-wcj-payment-gateways-by-country.php',
			'includes/class-wcj-payment-gateways-by-user-role.php',
			'includes/class-wcj-payment-gateways-by-shipping.php',
			'includes/class-wcj-shipping.php',
			'includes/class-wcj-shipping-options.php',
			'includes/class-wcj-left-to-free-shipping.php',
			'includes/class-wcj-shipping-calculator.php',
			'includes/class-wcj-shipping-by-user-role.php',
			'includes/class-wcj-address-formats.php',
			'includes/class-wcj-orders.php',
			'includes/class-wcj-order-min-amount.php',
			'includes/class-wcj-order-numbers.php',
			'includes/class-wcj-order-custom-statuses.php',
			'includes/class-wcj-order-quantities.php',
			'includes/class-wcj-pdf-invoicing.php',
			'includes/class-wcj-emails.php',
			'includes/class-wcj-email-options.php',
			'includes/class-wcj-emails-verification.php',
			'includes/class-wcj-currencies.php',
			'includes/class-wcj-multicurrency.php',
			'includes/class-wcj-multicurrency-product-base-price.php',
			'includes/class-wcj-currency-per-product.php',
			'includes/class-wcj-currency-external-products.php',
			'includes/class-wcj-price-by-country.php',
			'includes/class-wcj-currency-exchange-rates.php',
			'includes/class-wcj-price-formats.php',
			'includes/class-wcj-general.php',
			'includes/class-wcj-track-users.php',
			'includes/class-wcj-breadcrumbs.php',
			'includes/class-wcj-url-coupons.php',
			'includes/class-wcj-admin-bar.php',
			'includes/class-wcj-my-account.php',
			'includes/class-wcj-custom-css.php',
			'includes/class-wcj-custom-js.php',
			'includes/class-wcj-products-xml.php',
			'includes/class-wcj-export-import.php',
			'includes/class-wcj-eu-vat-number.php',
			'includes/class-wcj-old-slugs.php',
			'includes/class-wcj-reports.php',
			'includes/class-wcj-wpml.php',
			'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-numbering.php',
			'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-templates.php',
			'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-styling.php',
			'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-header.php',
			'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-footer.php',
			'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-page.php',
			'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-emails.php',
			'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-display.php',
		);
		$this->modules = array();
		foreach ( $modules_files as $module_file ) {
			$module = include_once( $module_file );
			$this->modules[ $module->id ] = $module;
		}

		// Add and Manage options
		if ( is_admin() ) {
			$this->add_options();
		}
	}

	/**
	 * add_options.
	 *
	 * @version 2.8.0
	 * @since   2.5.2
	 * @todo    (maybe) this only loads Enable, Tools and Reset settings for each module
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
					if ( 'yes' === get_option( 'wcj_autoload_options', 'yes' ) ) {
						$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
					} else {
						$autoload = false;
					}
					add_option( $value['id'], $value['default'], '', $autoload );
				}
			}
		}
		if ( get_option( 'booster_for_woocommerce_version' ) !== $this->version ) {
			update_option( 'booster_for_woocommerce_version', $this->version );
			add_action( 'admin_notices', array( $this, 'admin_notices_version_updated' ) );
		}
	}

	/**
	 * admin_notices_version_updated.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function admin_notices_version_updated() {
		if ( get_option( 'booster_for_woocommerce_version' ) === $this->version ) {
			$class   = 'notice notice-success is-dismissible';
			$message = sprintf( __( '<strong>Booster for WooCommerce</strong> plugin was successfully updated to version <strong>%s</strong>.', 'woocommerce-jetpack' ), $this->version );
			echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}
	}

	/**
	 * Add Jetpack settings tab to WooCommerce settings.
	 */
	function add_wcj_settings_tab( $settings ) {
		$_settings = include( 'includes/admin/class-wc-settings-jetpack.php' );
		$_settings->add_module_statuses( $this->module_statuses );
		$settings[] = $_settings;
		return $settings;
	}

}

endif;

if ( ! function_exists( 'WCJ' ) ) {
	/**
	 * Returns the main instance of WC_Jetpack to prevent the need to use globals.
	 *
	 * @version 2.5.7
	 * @return  WC_Jetpack
	 */
	function WCJ() {
		return WC_Jetpack::instance();
	}
}

WCJ();

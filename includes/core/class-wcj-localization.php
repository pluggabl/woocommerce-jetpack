<?php
/**
 * Booster - WordPress-managed Dutch catalog fallback.
 *
 * @version 8.5.0
 * @since   8.5.0
 * @package Booster_For_WooCommerce/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Localization' ) ) :
	/**
	 * Appends bundled Dutch translations without replacing the primary catalog.
	 */
	class WCJ_Localization {
		/**
		 * Main plugin file, used for WordPress path registration.
		 *
		 * @var string
		 */
		private $plugin_file;

		/**
		 * Prevents re-entry from third-party translation loading filters.
		 *
		 * @var bool
		 */
		private $loading = false;

		/**
		 * Respect an explicit, non-reloadable unload for the rest of this request.
		 *
		 * @var bool
		 */
		private $unloaded = false;

		/**
		 * Registers the existing domain and locale lifecycle callbacks.
		 *
		 * @param string $plugin_file Main plugin file.
		 */
		public function __construct( $plugin_file ) {
			$this->plugin_file = $plugin_file;
			add_action( 'init', array( $this, 'load' ), 9, 0 );
			add_action( 'change_locale', array( $this, 'load' ), 10 );
			add_action( 'unload_textdomain', array( $this, 'unload' ), 10, 2 );
			$this->load();
		}

		/**
		 * Does not undo a merchant/plugin request to stop loading this domain.
		 *
		 * @param string $domain Text domain being unloaded.
		 * @param bool   $reloadable Whether WordPress may reload it for a locale switch.
		 * @return void
		 */
		public function unload( $domain, $reloadable ) {
			if ( 'woocommerce-jetpack' === $domain && ! $reloadable ) {
				$this->unloaded = true;
			}
		}

		/**
		 * Lets WordPress select the primary catalog, then fills its missing keys.
		 *
		 * WordPress load_textdomain() keeps already-loaded translations first.
		 * Its standard file-format, redirect and short-circuit filters stay active.
		 * A merchant's explicit full-domain override can therefore suppress this
		 * fallback. Never remove their filters or unload their catalog to bypass it.
		 *
		 * @param string|null $locale Locale supplied by change_locale, or null.
		 * @return void
		 */
		public function load( $locale = null ) {
			if ( $this->loading || $this->unloaded ) {
				return;
			}

			$this->loading = true;
			try {
				load_plugin_textdomain( 'woocommerce-jetpack', false, dirname( plugin_basename( $this->plugin_file ) ) . '/langs/' );
				$locale = null === $locale ? determine_locale() : $locale;

				// Only the owner-approved standard Dutch locale gets a new fallback.
				if ( 'nl_NL' !== $locale ) {
					return;
				}

				$catalog = plugin_dir_path( $this->plugin_file ) . 'langs/woocommerce-jetpack-nl_NL.mo';
				if ( ! is_readable( $catalog ) ) {
					return;
				}

				// Respect WordPress/community/custom selection before the bundled file.
				get_translations_for_domain( 'woocommerce-jetpack' );
				load_textdomain( 'woocommerce-jetpack', $catalog, $locale );
			} finally {
				$this->loading = false;
			}
		}
	}
endif;

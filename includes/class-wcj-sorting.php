<?php
/**
 * Booster for WooCommerce - Module - Sorting
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Sorting' ) ) :
	/**
	 * WCJ_Sorting.
	 */
	class WCJ_Sorting extends WCJ_Module {

		/**
		 * WCJ_Sorting Constructor.
		 *
		 * @access  public
		 * @version 5.2.0
		 */
		public function __construct() {

			$this->id         = 'sorting';
			$this->short_desc = __( 'Sorting', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add more sorting options; rename or remove default sorting options (Plus); rearrange sorting options on frontend. Sort SKUs as numbers (Plus)', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add more sorting options; rename or remove default sorting options; rearrange sorting options on frontend.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-sorting';
			parent::__construct();

			if ( $this->is_enabled() ) {

				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sorting_remove_all_enabled', 'no' ) ) ) {
					// Remove All Sorting.
					add_action( 'wp_loaded', array( $this, 'remove_sorting' ), PHP_INT_MAX );
					add_filter( 'wc_get_template', array( $this, 'remove_sorting_template' ), PHP_INT_MAX, 5 );

				} else {

					// Add Custom Sorting.
					if ( 'yes' === wcj_get_option( 'wcj_more_sorting_enabled', 'yes' ) ) {
						add_filter( 'woocommerce_get_catalog_ordering_args', array( $this, 'custom_woocommerce_get_catalog_ordering_args' ), PHP_INT_MAX ); // Sorting.
						add_filter( 'woocommerce_catalog_orderby', array( $this, 'custom_woocommerce_catalog_orderby' ), PHP_INT_MAX ); // Front end.
						add_filter( 'woocommerce_default_catalog_orderby_options', array( $this, 'custom_woocommerce_catalog_orderby' ), PHP_INT_MAX ); // Back end (default .sorting).
					}

					// Remove or Rename Default Sorting.
					if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sorting_default_sorting_enabled', 'no' ) ) ) {
						add_filter( 'woocommerce_catalog_orderby', array( $this, 'remove_default_sortings' ), PHP_INT_MAX );
						add_filter( 'woocommerce_catalog_orderby', array( $this, 'rename_default_sortings' ), PHP_INT_MAX );
						add_filter( 'woocommerce_default_catalog_orderby_options', array( $this, 'remove_default_sortings' ), PHP_INT_MAX );
					}

					// Rearrange All Sorting.
					if ( 'yes' === wcj_get_option( 'wcj_sorting_rearrange_enabled', 'no' ) ) {
						add_filter( 'woocommerce_catalog_orderby', array( $this, 'rearrange_sorting' ), PHP_INT_MAX );
						add_filter( 'woocommerce_default_catalog_orderby_options', array( $this, 'rearrange_sorting' ), PHP_INT_MAX );
					}

					// Restore Default WooCommerce Sorting.
					if ( 'yes' === wcj_get_option( 'wcj_sorting_restore_default_sorting', 'no' ) ) {
						$this->restore_default_sorting();
					}
				}
			}
		}

		/**
		 * Restores default WooCommerce sorting on Avada Theme.
		 *
		 * @version 4.6.0
		 * @since   4.3.0
		 */
		public function restore_default_sorting_on_avada() {
			add_action(
				'wp_head',
				function () {
					?>
			<style>
				form.woocommerce-ordering {
					display: inline-block;
				}
			</style>
					<?php
				}
			);
			add_action(
				'woocommerce_before_shop_loop',
				function () {
					echo '<div class="catalog-ordering fusion-clearfix">';
				},
				9
			);
			add_action(
				'woocommerce_before_shop_loop',
				function () {
					echo '</div>';
				},
				31
			);
			add_action(
				'after_setup_theme',
				function () {
					global $avada_woocommerce;
					remove_action( 'pre_get_posts', array( $avada_woocommerce, 'product_ordering' ), 5 );
				}
			);
		}

		/**
		 * Restores default WooCommerce sorting
		 *
		 * @version 4.3.0
		 * @since   4.3.0
		 */
		public function restore_default_sorting() {
			$theme = wcj_get_option( 'wcj_sorting_restore_default_sorting_theme', 'avada' );
			switch ( $theme ) {
				case 'avada':
					$this->restore_default_sorting_on_avada();
					break;
			}
		}

		/**
		 * Remove_sorting_template.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 * @param string $located Get located.
		 * @param string $template_name Get template_name.
		 * @param array  $args Get args.
		 * @param string $template_path Get template_path.
		 * @param string $default_path Get default_path.
		 */
		public function remove_sorting_template( $located, $template_name, $args, $template_path, $default_path ) {
			if ( 'loop/orderby.php' === $template_name ) {
				$located = untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/..' ) ) . '/includes/templates/wcj-empty.php';
			}
			return $located;
		}

		/**
		 * Rearrange_sorting.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 * @param array $sortby Get sortby.
		 */
		public function rearrange_sorting( $sortby ) {
			$rearranged_sorting = wcj_get_option( 'wcj_sorting_rearrange', false );
			if ( false === $rearranged_sorting ) {
				$rearranged_sorting = $this->get_woocommerce_sortings_order();
			} else {
				$rearranged_sorting = explode( PHP_EOL, $rearranged_sorting );
			}
			$rearranged_sortby = array();
			foreach ( $rearranged_sorting as $sorting ) {
				$sorting = str_replace( "\n", '', $sorting );
				$sorting = str_replace( "\r", '', $sorting );
				if ( isset( $sortby[ $sorting ] ) ) {
					$rearranged_sortby[ $sorting ] = $sortby[ $sorting ];
					unset( $sortby[ $sorting ] );
				}
			}
			return array_merge( $rearranged_sortby, $sortby );
		}

		/**
		 * Remove_default_sortings.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 * @param array $sortby Get sortby.
		 */
		public function remove_default_sortings( $sortby ) {
			$default_sortings = $this->get_woocommerce_default_sortings();
			foreach ( $default_sortings as $sorting_key => $sorting_desc ) {
				$option_key = str_replace( '-', '_', $sorting_key );
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sorting_default_sorting_' . $option_key . '_disable', 'no' ) ) ) {
					unset( $sortby[ $sorting_key ] );
				}
			}
			return $sortby;
		}

		/**
		 * Rename_default_sortings.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 * @param array $sortby Get sortby.
		 */
		public function rename_default_sortings( $sortby ) {
			$default_sortings = $this->get_woocommerce_default_sortings();
			foreach ( $default_sortings as $sorting_key => $sorting_desc ) {
				$option_key = str_replace( '-', '_', $sorting_key );
				if ( isset( $sortby[ $sorting_key ] ) ) {
					$sortby[ $sorting_key ] = apply_filters(
						'booster_option',
						$sorting_desc,
						get_option( 'wcj_sorting_default_sorting_' . $option_key, $sorting_desc )
					);
				}
			}
			return $sortby;
		}

		/**
		 * Get_woocommerce_sortings_order.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 */
		public function get_woocommerce_sortings_order() {
			return array(
				'menu_order',
				'popularity',
				'rating',
				'date',
				'price',
				'price-desc',
				'title_asc',
				'title_desc',
				'sku_asc',
				'sku_desc',
				'stock_quantity_asc',
				'stock_quantity_desc',
			);
		}

		/**
		 * Get_woocommerce_default_sortings.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 */
		public function get_woocommerce_default_sortings() {
			return array(
				'menu_order' => __( 'Default sorting', 'woocommerce' ),
				'popularity' => __( 'Sort by popularity', 'woocommerce' ),
				'rating'     => __( 'Sort by average rating', 'woocommerce' ),
				'date'       => __( 'Sort by newness', 'woocommerce' ),
				'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
				'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
			);
		}

		/**
		 * Remove_sorting.
		 *
		 * @version 2.6.0
		 */
		public function remove_sorting() {
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
			remove_action( 'mpcth_before_shop_loop', 'woocommerce_catalog_ordering', 40 ); // Blaszok theme.
			remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 ); // Storefront.
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 ); // Storefront.
		}

		/**
		 * Maybe_add_sorting.
		 *
		 * @version 2.2.4
		 * @since   2.2.4
		 * @param array  $sortby Get sortby.
		 * @param string $option_name Get option_name.
		 * @param string $key Get key.
		 */
		public function maybe_add_sorting( $sortby, $option_name, $key ) {
			if ( '' !== wcj_get_option( $option_name ) ) {
				$sortby[ $key ] = wcj_get_option( $option_name );
			}
			return $sortby;
		}

		/**
		 * Add new sorting options to Front End and to Back End (in WooCommerce > Settings > Products > Default Product Sorting).
		 *
		 * @version 2.2.4
		 * @param array $sortby Get sortby.
		 */
		public function custom_woocommerce_catalog_orderby( $sortby ) {
			$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_name_asc_text', 'title_asc' );
			$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_name_desc_text', 'title_desc' );
			$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_sku_asc_text', 'sku_asc' );
			$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_sku_desc_text', 'sku_desc' );
			$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_stock_quantity_asc_text', 'stock_quantity_asc' );
			$sortby = $this->maybe_add_sorting( $sortby, 'wcj_sorting_by_stock_quantity_desc_text', 'stock_quantity_desc' );
			return $sortby;
		}

		/**
		 * Add new sorting options to WooCommerce sorting.
		 *
		 * @param array $args Get args.
		 * @version 5.6.8
		 */
		public function custom_woocommerce_get_catalog_ordering_args( $args ) {
			// Get ordering from query string.
			$_get = array();
			if ( ! isset( $_SERVER['QUERY_STRING'] ) ) {
				return $args;
			}
			parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $_get );
			$orderby_value = ( WCJ_IS_WC_VERSION_BELOW_3 ) ?
			( ( isset( $_get['orderby'] ) && $wpnonce ) ? woocommerce_clean( sanitize_text_field( wp_unslash( $_get['orderby'] ) ) ) : apply_filters(
				'woocommerce_default_catalog_orderby',
				get_option( 'woocommerce_default_catalog_orderby' )
			) ) :
			( isset( $_get['orderby'] ) ? wc_clean( sanitize_text_field( wp_unslash( $_get['orderby'] ) ) ) : apply_filters(
				'woocommerce_default_catalog_orderby',
				get_option( 'woocommerce_default_catalog_orderby' )
			)
			);
			// Get orderby arg from string.
			$orderby_value = explode( '-', $orderby_value );
			$orderby       = esc_attr( $orderby_value[0] );

			switch ( $orderby ) :
				case 'title_asc':
					$args['orderby']  = 'title';
					$args['order']    = 'asc';
					$args['meta_key'] = ''; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					break;
				case 'title_desc':
					$args['orderby']  = 'title';
					$args['order']    = 'desc';
					$args['meta_key'] = ''; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					break;
				case 'sku_asc':
					$args['orderby']  = ( 'no' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sorting_by_sku_num_enabled', 'no' ) ) ) ?
					'meta_value' : 'meta_value_num';
					$args['order']    = 'asc';
					$args['meta_key'] = '_sku'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					break;
				case 'sku_desc':
					$args['orderby']  = ( 'no' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sorting_by_sku_num_enabled', 'no' ) ) ) ?
					'meta_value' : 'meta_value_num';
					$args['order']    = 'desc';
					$args['meta_key'] = '_sku'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					break;
				case 'stock_quantity_asc':
					$args['orderby']  = 'meta_value_num';
					$args['order']    = 'asc';
					$args['meta_key'] = '_stock'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					break;
				case 'stock_quantity_desc':
					$args['orderby']  = 'meta_value_num';
					$args['order']    = 'desc';
					$args['meta_key'] = '_stock'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					break;
			endswitch;

			return $args;
		}

	}

endif;

return new WCJ_Sorting();

<?php
/**
 * Booster True V10 - Dynamic Module Catalog (loaded only on REST/V10 requests).
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Catalog' ) ) :

	/**
	 * Builds the searchable module inventory from Booster's native runtime registry.
	 */
	class WCJ_V10_Catalog {

		/**
		 * Map module IDs to Phase 1 flagship editor slugs.
		 *
		 * @return array<string, string>
		 */
		public static function flagship_map() {
			if ( class_exists( 'WCJ_V10_Registry' ) ) {
				return WCJ_V10_Registry::section_to_editor_map();
			}
			return array(
				'pdf_invoicing'         => 'pdf_invoicing',
				'payment_gateways_fees' => 'payment_gateways_fees',
				'add_to_cart'           => 'button_price_labels',
				'more_button_labels'    => 'button_price_labels',
				'price_labels'          => 'button_price_labels',
				'sale_flash'            => 'button_price_labels',
			);
		}

		/**
		 * Extra intent search keywords for high-frequency merchant jobs.
		 *
		 * @return array<string, string>
		 */
		private static function search_intents() {
			return array(
				'pdf_invoicing'          => 'invoice pdf packing slip logo branding header tax vat receipt email attachment proforma credit note',
				'payment_gateways_fees'  => 'fee surcharge discount paypal stripe credit card cod cash on delivery bacs bank transfer gateway payment method checkout min max',
				'add_to_cart'            => 'button label add to cart buy now product text wording rename add to cart',
				'more_button_labels'     => 'place order checkout button wording text override rename button',
				'price_labels'           => 'price prefix suffix label text vat gst tax before after excl incl',
				'sale_flash'             => 'sale badge flash discount tag wording save off hide sale',
				'price_by_user_role'     => 'role wholesale vip customer tier price b2b bulk',
				'checkout_custom_fields' => 'checkout field gift note delivery message custom additional information',
				'product_addons'         => 'addon extra gift wrap paid option checkbox radio upsell',
				'shipping_options'       => 'hide shipping flat rate free shipping delivery local pickup most expensive',
				'left_to_free_shipping'  => 'free shipping bar threshold progress left amount cart notice',
				'eu_vat_number'          => 'eu vat number vies tax exempt b2b reverse charge europe',
				'multicurrency'          => 'currency exchange rate switcher usd eur gbp eu multicurrency',
				'returns'                => 'returns rma refund request window',
				'cart_abandonment'       => 'abandoned cart recovery reminder email coupon lost sales',
				'order_numbers'          => 'order number sequential invoice prefix receipt custom counter',
				'wholesale_price'        => 'wholesale bulk b2b quantity discount tier customer pricing',
			);
		}

		/**
		 * Build full dynamic catalog array for the Booster Command Center.
		 *
		 * @return array{categories: array, modules: array}
		 */
		public static function get_catalog() {
			$core = WCJ_V10_Store::core();
			if ( ! $core || empty( $core->all_modules ) ) {
				return array(
					'categories' => array(),
					'modules'    => array(),
				);
			}

			$cats_file = dirname( __DIR__ ) . '/wcj-modules-cats.php';
			$cats_raw  = file_exists( $cats_file ) ? include $cats_file : array();
			$flagships = self::flagship_map();
			$intents   = self::search_intents();

			// Map module_id => category info.
			$mod_to_cat = array();
			$categories = array();
			if ( is_array( $cats_raw ) ) {
				foreach ( $cats_raw as $cat_id => $cat ) {
					if ( 'dashboard' === $cat_id || empty( $cat['all_cat_ids'] ) ) {
						continue;
					}
					$label        = isset( $cat['label'] ) ? wp_strip_all_tags( (string) $cat['label'] ) : $cat_id;
					$categories[] = array(
						'id'    => $cat_id,
						'label' => $label,
					);
					foreach ( (array) $cat['all_cat_ids'] as $mod_id ) {
						if ( ! isset( $mod_to_cat[ $mod_id ] ) ) {
							$mod_to_cat[ $mod_id ] = array(
								'id'    => $cat_id,
								'label' => $label,
							);
						}
					}
				}
			}

			// Avoid sub-modules that do not have standalone module enablement in PDF invoicing.
			$hidden_sub_sections = array(
				'pdf_invoicing_numbering',
				'pdf_invoicing_templates',
				'pdf_invoicing_header',
				'pdf_invoicing_footer',
				'pdf_invoicing_styling',
				'pdf_invoicing_page',
				'pdf_invoicing_emails',
				'pdf_invoicing_display',
				'pdf_invoicing_advanced',
				'product_info',
			);

			$blocks_limited = array(
				'checkout_custom_fields',
				'checkout_files_upload',
				'checkout_core_fields',
				'eu_vat_number',
				'cart_abandonment',
				'left_to_free_shipping',
			);

			$modules = array();
			foreach ( $core->all_modules as $mod_id => $mod_obj ) {
				if ( in_array( $mod_id, $hidden_sub_sections, true ) ) {
					continue;
				}
				if ( function_exists( 'wcj_is_module_allowed_by_allowlist' ) && ! wcj_is_module_allowed_by_allowlist( $mod_id ) ) {
					continue;
				}
				if ( wcj_is_module_enabled( 'modules_by_user_roles' ) && function_exists( 'wcj_check_modules_by_user_roles' ) && ! wcj_check_modules_by_user_roles( $mod_id ) ) {
					continue;
				}

				$cat_info  = isset( $mod_to_cat[ $mod_id ] ) ? $mod_to_cat[ $mod_id ] : array( 'id' => 'emails_and_misc', 'label' => __( 'Emails & Misc.', 'woocommerce-jetpack' ) );
				$title     = isset( $mod_obj->short_desc ) ? wp_strip_all_tags( (string) $mod_obj->short_desc ) : $mod_id;
				$is_paid   = 'free' !== WCJ_V10_Store::edition();
				$raw_desc  = ( $is_paid && ! empty( $mod_obj->desc_pro ) ) ? $mod_obj->desc_pro : ( isset( $mod_obj->desc ) ? $mod_obj->desc : '' );
				$desc      = wp_strip_all_tags( (string) $raw_desc );
				$enabled   = function_exists( 'wcj_is_module_enabled' ) ? wcj_is_module_enabled( $mod_id ) : ( 'yes' === wcj_get_option( 'wcj_' . $mod_id . '_enabled', 'no' ) );
				$nonce     = wp_create_nonce( 'wcj-cat-nonce' );
				$classic   = admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . rawurlencode( $cat_info['id'] ) . '&section=' . rawurlencode( $mod_id ) . '&wcj_classic=1&wcj-cat-nonce=' . $nonce );

				$modules[] = array(
					'id'              => $mod_id,
					'name'            => $title,
					'desc'            => $desc,
					'category_id'     => $cat_info['id'],
					'category_label'  => $cat_info['label'],
					'enabled'         => (bool) $enabled,
					'flagship_editor' => isset( $flagships[ $mod_id ] ) ? $flagships[ $mod_id ] : '',
					'blocks_status'   => in_array( $mod_id, $blocks_limited, true ) ? 'classic' : 'blocks',
					'classic_url'     => $classic,
					'keywords'        => isset( $intents[ $mod_id ] ) ? $intents[ $mod_id ] : '',
				);
			}

			usort(
				$modules,
				static function ( $a, $b ) {
					if ( $a['enabled'] !== $b['enabled'] ) {
						return $a['enabled'] ? -1 : 1;
					}
					$a_flag = '' !== $a['flagship_editor'] ? 0 : 1;
					$b_flag = '' !== $b['flagship_editor'] ? 0 : 1;
					if ( $a_flag !== $b_flag ) {
						return $a_flag - $b_flag;
					}
					return strcmp( $a['name'], $b['name'] );
				}
			);

			return array(
				'categories' => $categories,
				'modules'    => $modules,
			);
		}
	}

endif;

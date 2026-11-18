<?php
/** Purpose-group help only. Native controls, rights and writers remain unchanged.
 *
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
/** Native-backed optional admin workflow and its narrowly scoped helpers. */
final class WCJ_Product_Availability_Groups {
	/**
	 * Declare substantive native groups and their guide destinations.
	 *
	 * @return mixed
	 */
	public static function groups() {
		return array(
			array( 'add_to_cart_button_visibility', 'button-global', 'wcj_cart_button_visibility_all_product_tab', __( 'Buttons on product pages and archives', 'woocommerce-jetpack' ), __( 'Global enable, separate single/archive hiding methods and replacement HTML or shortcodes. Hiding a button does not prohibit purchase or protect product data.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-add-to-cart-button-visibility/#button-global' ),
			array( 'add_to_cart_button_visibility', 'button-categories', 'wcj_cart_button_visibility_per_category_tab', __( 'Buttons for categories', 'woocommerce-jetpack' ), __( 'Category enable, selected categories and separate archive/single replacement content. Native reset is module-wide, not guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-add-to-cart-button-visibility/#button-categories' ),
			array( 'add_to_cart_button_visibility', 'button-product', 'wcj_cart_button_visibility_per_product_tab', __( 'Buttons on one product', 'woocommerce-jetpack' ), __( 'Global per-product gate and the native product panel: single/archive hiding and replacement content. These settings are display rules, not an availability guarantee.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-add-to-cart-button-visibility/#button-product' ),
			array( 'login_to_see_pricing', 'login-single', 'wcj_login_single_product_tab', __( 'Guest prices on product pages', 'woocommerce-jetpack' ), __( 'Elite: enable guest price replacement and its text on single products. Signed-in shoppers bypass this display rule.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-login-to-see-pricing/#login-single' ),
			array( 'login_to_see_pricing', 'login-archives', 'wcj_login_archives_product_tab', __( 'Guest prices in archives', 'woocommerce-jetpack' ), __( 'Elite: separate archive guest price replacement and text. Check the actual theme and Blocks output.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-login-to-see-pricing/#login-archives' ),
			array( 'login_to_see_pricing', 'login-other-areas', 'wcj_login_general_tab', __( 'Other guest price areas', 'woocommerce-jetpack' ), __( 'Elite: additional native price/button suppression, plus module reset. This is not price secrecy, API authorization or purchase prevention.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-login-to-see-pricing/#login-other-areas' ),
			array( 'product_by_country', 'country-effects', 'wcj_product_custom_visibility_options_tab', __( 'Country effects', 'woocommerce-jetpack' ), __( 'Visibility, purchasability and query/widget filtering are distinct switches. Empty lists are unrestricted; when both lists apply, exclusion wins. Module activation is separate.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-visibility-by-country/#country-effects' ),
			array( 'product_by_country', 'country-selection', 'wcj_product_custom_visibility_options_list_tab', __( 'Country source and selectors', 'woocommerce-jetpack' ), __( 'Country source, manual selector list and billing overwrite; geolocation or a shopper selection is not verified nationality. Paid/legacy native method and list limits remain unchanged.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-visibility-by-country/#country-selection' ),
			array( 'product_by_country', 'country-product-lists', 'wcj_product_custom_visibility_admin_options_tab', __( 'Product inclusion and exclusion lists', 'woocommerce-jetpack' ), __( 'Native product Visible/Invisible lists follow the configured visibility method. Empty, removed and custom values are retained. Use the product editor; quick/bulk controls appear only when their own native gates permit them.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-visibility-by-country/#country-product-lists' ),
			array( 'product_by_country', 'country-admin', 'wcj_product_custom_visibility_admin_options_tab', __( 'Product list editors and reset', 'woocommerce-jetpack' ), __( 'Visibility method, select style, quick edit, paid bulk edit and product-list column. Native saves and module reset have their own recovery limits; guided Date Undo does not cover them.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-visibility-by-country/#country-admin' ),
			array( 'product_by_user_role', 'role-effects', 'wcj_product_custom_visibility_options_tab', __( 'Role effects', 'woocommerce-jetpack' ), __( 'Visibility, purchasability and query/widget filtering are distinct switches. Empty lists are unrestricted; when both lists apply, exclusion wins. Module activation is separate.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-visibility-by-user-role/#role-effects' ),
			array( 'product_by_user_role', 'role-selection', 'wcj_product_custom_visibility_options_list_tab', __( 'Role choices', 'woocommerce-jetpack' ), __( 'Native editable roles and paid Skip Editable Roles Filter. Multiple roles and guest handling matter; preserve removed or custom role keys. Native REST paths may bypass this display rule.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-visibility-by-user-role/#role-selection' ),
			array( 'product_by_user_role', 'role-product-lists', 'wcj_product_custom_visibility_admin_options_tab', __( 'Product inclusion and exclusion lists', 'woocommerce-jetpack' ), __( 'Native product Visible/Invisible lists follow the configured visibility method. Empty, removed and custom values are retained. Use the product editor; quick/bulk controls appear only when their own native gates permit them.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-visibility-by-user-role/#role-product-lists' ),
			array( 'product_by_user_role', 'role-admin', 'wcj_product_custom_visibility_admin_options_tab', __( 'Product list editors and reset', 'woocommerce-jetpack' ), __( 'Visibility method, select style, quick edit, paid bulk edit and product-list column. Native saves and module reset have their own recovery limits; guided Date Undo does not cover them.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-visibility-by-user-role/#role-admin' ),
			array( 'product_custom_visibility', 'custom-effects', 'wcj_product_custom_visibility_options_tab', __( 'Custom selection effects', 'woocommerce-jetpack' ), __( 'Visibility, purchasability and query/widget filtering are distinct switches. Empty lists are unrestricted; when both lists apply, exclusion wins. Module activation is separate.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-custom-visibility/#custom-effects' ),
			array( 'product_custom_visibility', 'custom-options', 'wcj_product_custom_visibility_options_list_tab', __( 'Custom option names and selectors', 'woocommerce-jetpack' ), __( 'Options create sanitized internal keys used by the shopper selector/session. Renaming or colliding labels can orphan existing product lists. Preserve unknown keys; a selector is not an identity/security boundary.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-custom-visibility/#custom-options' ),
			array( 'product_custom_visibility', 'custom-product-lists', 'wcj_product_custom_visibility_admin_options_tab', __( 'Product inclusion and exclusion lists', 'woocommerce-jetpack' ), __( 'Native product Visible/Invisible lists follow the configured visibility method. Empty, removed and custom values are retained. Use the product editor; quick/bulk controls appear only when their own native gates permit them.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-custom-visibility/#custom-product-lists' ),
			array( 'product_custom_visibility', 'custom-admin', 'wcj_product_custom_visibility_admin_options_tab', __( 'Product list editors and reset', 'woocommerce-jetpack' ), __( 'Visibility method, select style, quick edit, paid bulk edit and product-list column. Native saves and module reset have their own recovery limits; guided Date Undo does not cover them.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-custom-visibility/#custom-admin' ),
			array( 'product_by_date', 'date-global', 'wcj_product_by_date_all_product_tab', __( 'Monthly dates for all products', 'woocommerce-jetpack' ), __( 'Global monthly gate and twelve day-range schedules, including month-off values. Native defaults are not an all-year unrestricted schedule. This task never changes them.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-availability-by-date/#date-global' ),
			array( 'product_by_date', 'date-product', 'wcj_product_by_date_per_product_tab', __( 'Direct date and monthly override on one product', 'woocommerce-jetpack' ), __( 'Global per-product gate, product enable, direct date and twelve retained monthly values. A nonempty direct date takes precedence only while the product override is enabled. Free has one enabled Date product, separate from Time; paid editions retain native unlimited rules.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-availability-by-date/#date-product' ),
			array( 'product_by_date', 'date-messages', 'wcj_product_by_date_frontend_messages_tab', __( 'Date messages and action', 'woocommerce-jetpack' ), __( 'Monthly, month-off and direct-date messages, shortcodes, shop-page messages and the Action in Advanced Options. Message-only does not prohibit purchase. Native message editing restrictions remain unchanged.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-availability-by-date/#date-messages' ),
			array( 'product_by_date', 'date-format', 'wcj_product_by_date_per_product_tab', __( 'Date format and native evaluation', 'woocommerce-jetpack' ), __( 'Numeric direct-date input format and store timezone. Calendar-only dates start at midnight; native formats with explicit times retain them. Unsupported/custom formats remain in full settings. Advanced Options also holds the module-wide reset.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-availability-by-date/#date-format' ),
			array( 'product_by_time', 'time-global', 'wcj_product_by_time_all_product_tab', __( 'Weekly times for all products', 'woocommerce-jetpack' ), __( 'Global gate and seven weekday windows in store time. Endpoints are inclusive; do not assume an overnight range is normalized. Other purchase restrictions still apply.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-availability-by-time/#time-global' ),
			array( 'product_by_time', 'time-product', 'wcj_product_by_time_per_product_tab', __( 'Weekly times on one product', 'woocommerce-jetpack' ), __( 'Global per-product gate, product enable and seven native daily overrides. Free has one enabled Time product independently of the Date slot. No schedule conversion or guided Date Undo here.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-availability-by-time/#time-product' ),
			array( 'product_by_time', 'time-messages', 'wcj_product_by_time_frontend_messages_tab', __( 'Time messages and reset', 'woocommerce-jetpack' ), __( 'Unavailable/day-off message templates and native editing rights. Module reset is separate from the one-product Date task and its Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-availability-by-time/#time-messages' ),
			array( 'stock', 'stock-in', 'wcj_product_stock_in_stock_tab', __( 'In-stock wording and classes', 'woocommerce-jetpack' ), __( 'Section gate, custom text, quantity/low-stock/backorder variants and CSS classes. These change display, not inventory or purchasing rights.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-stock/#stock-in' ),
			array( 'stock', 'stock-out', 'wcj_product_stock_out_of_stock_tab', __( 'Out-of-stock wording and classes', 'woocommerce-jetpack' ), __( 'Section gate, text and CSS class. A visible label is not proof of actual inventory.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-stock/#stock-out' ),
			array( 'stock', 'stock-backorders', 'wcj_product_stock_available_on_bacorder_tab', __( 'Backorder wording and classes', 'woocommerce-jetpack' ), __( 'Section gate, text and CSS class. This does not enable backorders or change fulfilment promises.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-stock/#stock-backorders' ),
			array( 'stock', 'stock-html', 'wcj_product_stock_custom_html_tab', __( 'Stock HTML and hiding', 'woocommerce-jetpack' ), __( 'Native paid stock HTML and More Options removal of frontend display; Free locked branches stay locked. Native reset changes the module, not stock counts.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-stock/#stock-html' ),
		); }
	/**
	 * Return the native settings route.
	 *
	 * @param mixed $module Module.
	 * @param mixed $tab Tab.
	 * @return mixed
	 */
	public static function native_url( $module, $tab = '' ) {
		return add_query_arg( 'active_tab', $tab, admin_url( wcj_admin_tab_url() . '&wcj-cat=products&section=' . $module ) ); }
	/**
	 * Require module policy and actual installed registration.
	 *
	 * @param mixed $module Module.
	 * @return mixed
	 */
	private static function allowed( $module ) {
		$core = WCJ_Job_Store::core();
		return WCJ_Task_Finder::allowed( $module ) && $core && isset( $core->all_modules[ $module ] );
	}
	/**
	 * Render the optional editor with product controls initially blocked.
	 *
	 * @param mixed $module Module.
	 * @param mixed $anchor Anchor.
	 * @return void
	 */
	public static function render( $module = '', $anchor = '' ) {
		$groups = array_filter(
			self::groups(),
			static function ( $g ) use ( $module, $anchor ) {
				return ( ! $module || $g[0] === $module ) && ( ! $anchor || $g[1] === $anchor ) && self::allowed( $g[0] );
			}
		);
		if ( ! $groups ) {
			return; }
		echo '<details class="wcj-availability-groups"><summary>' . esc_html__( 'Product visibility, availability and stock: full settings and help', 'woocommerce-jetpack' ) . '</summary><p>' . esc_html__( 'These are different tools, not interchangeable security controls. Full settings open in a new tab and keep their own edition limits. Save there first, then load current values here. Native edits and module resets are not covered by guided Date Undo.', 'woocommerce-jetpack' ) . '</p><ul>';
		foreach ( $groups as $g ) {
			$extra = array( 'date-messages' => array( 'wcj_product_by_date_advanced_options_tab', __( 'Action and shop-page message settings (new tab)', 'woocommerce-jetpack' ) ), 'date-format' => array( 'wcj_product_by_date_advanced_options_tab', __( 'Advanced settings and reset (new tab)', 'woocommerce-jetpack' ) ), 'stock-html' => array( 'wcj_product_stock_more_options_tab', __( 'Remove stock display and reset (new tab)', 'woocommerce-jetpack' ) ) );
			echo '<li data-availability-group="' . esc_attr( $g[1] ) . '"><strong>' . esc_html( $g[3] ) . '</strong> &middot; ' . esc_html( wcj_is_module_enabled( $g[0] ) ? __( 'Module on', 'woocommerce-jetpack' ) : __( 'Module off', 'woocommerce-jetpack' ) ) . '<p>' . esc_html( $g[4] ) . '</p><a target="_blank" rel="noopener noreferrer" href="' . esc_url( self::native_url( $g[0], $g[2] ) ) . '">' . esc_html__( 'Full settings (new tab)', 'woocommerce-jetpack' ) . '</a> | <a target="_blank" rel="noopener noreferrer" href="' . esc_url( $g[5] ) . '">' . esc_html__( 'Help for this group (new tab)', 'woocommerce-jetpack' ) . '</a>';
			if ( isset( $extra[ $g[1] ] ) ) { echo ' | <a target="_blank" rel="noopener noreferrer" href="' . esc_url( self::native_url( $g[0], $extra[ $g[1] ][0] ) ) . '">' . esc_html( $extra[ $g[1] ][1] ) . '</a>'; }
			echo '</li>';
		}
		echo '</ul></details>';
	}
	/**
	 * Add contextual links without replacing native panel behavior.
	 *
	 * @param mixed $post Post.
	 * @return void
	 */
	public static function panel_help( $post ) {
		global $wp_meta_boxes;
		if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) || empty( $wp_meta_boxes['product'] ) ) {
			return; }
		$map = array(
			'add_to_cart_button_visibility' => 'button-product',
			'product_by_date'               => 'date-product',
			'product_by_time'               => 'time-product',
			'product_by_country'            => 'country-product-lists',
			'product_by_user_role'          => 'role-product-lists',
			'product_custom_visibility'     => 'custom-product-lists',
		);
		foreach ( $wp_meta_boxes['product'] as &$contexts ) {
			foreach ( $contexts as &$boxes ) {
				foreach ( $map as $module => $anchor ) {
					$box_id = 'wc-jetpack-' . $module;
					if ( empty( $boxes[ $box_id ] ) || ! self::allowed( $module ) || ! is_callable( $boxes[ $box_id ]['callback'] ) ) {
						continue; }
					$original                     = $boxes[ $box_id ]['callback'];
					$boxes[ $box_id ]['callback'] = static function ( $product_post, $box ) use ( $original, $module, $anchor ) {
						if ( current_user_can( 'edit_post', $product_post->ID ) && self::allowed( $module ) ) {
							self::render( $module, $anchor ); }
						call_user_func( $original, $product_post, $box );
					};
				}
			}
		}
	}
	/**
	 * Show editor help only when native list-editor gates allow it.
	 *
	 * @param mixed $kind Kind.
	 * @return void
	 */
	private static function list_help( $kind ) {
		if ( ! current_user_can( 'edit_products' ) ) {
			return; }
		foreach ( array(
			'product_by_country'        => 'country-admin',
			'product_by_user_role'      => 'role-admin',
			'product_custom_visibility' => 'custom-admin',
		) as $module => $anchor ) {
			$enabled = wcj_get_option( 'wcj_' . $module . '_admin_' . $kind . '_edit', 'no' );
			if ( 'bulk' === $kind ) {
				$enabled = apply_filters( 'booster_option', 'no', $enabled ); }
			if ( self::allowed( $module ) && wcj_is_module_enabled( $module ) && 'yes' === $enabled ) {
				self::render( $module, $anchor ); }
		}
	}
	/**
	 * Show help for admitted native quick-edit controls.
	 *
	 * @return void
	 */
	public static function quick_help() {
		self::list_help( 'quick' ); }
	/**
	 * Show help for admitted native bulk-edit controls.
	 *
	 * @return void
	 */
	public static function bulk_help() {
		self::list_help( 'bulk' ); }
}

<?php
/**
 * Product-family expert destinations with unchanged native recovery limits.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
/** Purpose-group navigation only; no native persistence changes. */
final class WCJ_Product_Question_Groups {

	/**
	 * Prepend permitted contextual help to existing native panel callbacks.
	 *
	 * @param mixed $post Post.
	 * @return void
	 */
	public static function panel_help( $post ) {
		global $wp_meta_boxes;
		if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) || empty( $wp_meta_boxes['product'] ) ) {
			return; }
		$map = array(
			'wc-jetpack-product-input-fields' => 'product_input_fields',
			'wc-jetpack-product_addons'       => 'product_addons',
			'wc-jetpack-custom-tabs'          => 'product_tabs',
		);
		foreach ( $wp_meta_boxes['product'] as &$contexts ) {
			foreach ( $contexts as &$boxes ) {
				foreach ( $map as $box_id => $module ) {
					if ( empty( $boxes[ $box_id ] ) || ! WCJ_Task_Finder::allowed( $module ) || ! is_callable( $boxes[ $box_id ]['callback'] ) ) {
						continue; }
					$original                     = $boxes[ $box_id ]['callback'];
					$boxes[ $box_id ]['callback'] = static function ( $product_post, $box ) use ( $original, $module ) {
						if ( current_user_can( 'edit_post', $product_post->ID ) && WCJ_Task_Finder::allowed( $module ) ) {
							self::render( $module ); }
						call_user_func( $original, $product_post, $box );
					};
				}
			}
		}
	}
	/**
	 * Return the existing native settings route.
	 *
	 * @param mixed $module Module.
	 * @return mixed
	 */
	public static function native_url( $module ) {
		return admin_url( wcj_admin_tab_url() . '&wcj-cat=products&section=' . $module ); }
	public static function groups() {
		return array(
			array( 'product_input_fields', 'input-local', __( 'Questions on one product', 'woocommerce-jetpack' ), __( 'Per-product enable, default count and individual fields. Native product pages and the full product panel can migrate legacy stored fields.', 'woocommerce-jetpack' ) ),
			array( 'product_input_fields', 'input-global', __( 'Questions on many products', 'woocommerce-jetpack' ), __( 'Global enable and repeated fields can affect every matching product, not just the selected product.', 'woocommerce-jetpack' ) ),
			array( 'product_input_fields', 'input-types', __( 'Other answer types and field rules', 'woocommerce-jetpack' ), __( 'Enabled/order, required message, length, checkbox values, number, date/week/time, choice, country, email, phone and password presentation. Native validation and edition limits apply; password presentation does not promise secret storage.', 'woocommerce-jetpack' ) ),
			array( 'product_input_fields', 'input-files', __( 'File uploads and attachments', 'woocommerce-jetpack' ), __( 'Files are stored on your server. Set allowed types and size limits; review download access and customer/admin email attachments. Uploads are not guided here.', 'woocommerce-jetpack' ) ),
			array( 'product_input_fields', 'input-visibility', __( 'Question visibility', 'woocommerce-jetpack' ), __( 'Global product/category/tag inclusion and exclusion rules determine where questions appear.', 'woocommerce-jetpack' ) ),
			array( 'product_input_fields', 'input-display', __( 'Cart and order display', 'woocommerce-jetpack' ), __( 'Cart item data versus item name, cart/order templates, line breaks, admin labels and checkout output affect all product questions.', 'woocommerce-jetpack' ) ),
			array( 'product_input_fields', 'input-advanced', __( 'Question layout and advanced settings', 'woocommerce-jetpack' ), __( 'Frontend hook/priority, wrappers, required suffix, CSS classes and slash handling are module-wide; theme and Blocks output can differ.', 'woocommerce-jetpack' ) ),
			array( 'product_input_fields', 'input-legacy-storage', __( 'Older question storage', 'woocommerce-jetpack' ), __( 'Legacy or mixed metadata stays unchanged in this task. Native product pages or the full panel may run their existing conversion; guided Undo does not cover native edits.', 'woocommerce-jetpack' ) ),
			array( 'product_addons', 'addons-local-global', __( 'Extras on one or many products', 'woocommerce-jetpack' ), __( 'Per-product and global enable/counts, exclusions and required choices. These settings can change what customers pay.', 'woocommerce-jetpack' ) ),
			array( 'product_addons', 'addons-choices-prices', __( 'Extra choices and prices', 'woocommerce-jetpack' ), __( 'Types, labels, titles, prices, tooltips, defaults, placeholders, AJAX price updates, percentage display and price filtering change paid options.', 'woocommerce-jetpack' ) ),
			array( 'product_addons', 'addons-quantity', __( 'Extra quantities', 'woocommerce-jetpack' ), __( 'Quantity fields and quantity reduction interact with cart quantity and the amount charged.', 'woocommerce-jetpack' ) ),
			array( 'product_addons', 'addons-variations', __( 'Variation extras, import and export', 'woocommerce-jetpack' ), __( 'Variation selection and import/export mappings need their own product checks. Existing native choices are not replaced by this task.', 'woocommerce-jetpack' ) ),
			array( 'product_addons', 'addons-templates', __( 'Extra layout, cart and order display', 'woocommerce-jetpack' ), __( 'Per-type templates, CSS class, frontend/price priorities, strike-through, checkout output and admin-order hiding affect display and price presentation.', 'woocommerce-jetpack' ) ),
			array( 'product_custom_info', 'info-current-single', __( 'Product Info: product-page blocks', 'woocommerce-jetpack' ), __( 'Repeated content blocks, positions and priorities show information; they do not collect shopper answers.', 'woocommerce-jetpack' ) ),
			array( 'product_custom_info', 'info-current-archive', __( 'Product Info: shop/archive blocks', 'woocommerce-jetpack' ), __( 'Separate archive count, content, positions and priorities. Configured blocks are not proof of visible output.', 'woocommerce-jetpack' ) ),
			array( 'product_custom_info', 'info-visibility-filters', __( 'Product Info: visibility and filters', 'woocommerce-jetpack' ), __( 'Include/exclude products, categories and tags; list/ID selectors and extra filter definitions. HTML and shortcodes remain expert content.', 'woocommerce-jetpack' ) ),
			array( 'product_tabs', 'tabs-shared-local', __( 'Shared and per-product tabs', 'woocommerce-jetpack' ), __( 'Global/local enables, counts, titles, keys, priorities, content and local defaults. The existing shared-tab task remains available.', 'woocommerce-jetpack' ) ),
			array( 'product_tabs', 'tabs-standard', __( 'Standard product tabs', 'woocommerce-jetpack' ), __( 'Rename, prioritize or remove Description, Additional Information and Reviews. Native edition limits still apply.', 'woocommerce-jetpack' ) ),
			array( 'product_tabs', 'tabs-visibility', __( 'Tab visibility', 'woocommerce-jetpack' ), __( 'Product/category/tag include/exclude selectors and older comma-separated IDs determine matching products.', 'woocommerce-jetpack' ) ),
			array( 'product_tabs', 'tabs-links-editor', __( 'Tab links and editors', 'woocommerce-jetpack' ), __( 'Links/new-window behavior, content processing, visual editor and Yoast integration remain in the full editor.', 'woocommerce-jetpack' ) ),
			array( 'product_info', 'info-v1-positions', __( 'Older settings: Product Info V1 positions', 'woocommerce-jetpack' ), __( 'Existing single/archive hook-position fields remain available even if no data is detected. No automatic conversion or active-block claim.', 'woocommerce-jetpack' ) ),
			array( 'product_info', 'info-v1-extra', __( 'Older settings: Product Info V1 extra blocks', 'woocommerce-jetpack' ), __( 'Single/archive enable, HTML/shortcodes, hook, priority and excluded product IDs remain separate from current Product Info.', 'woocommerce-jetpack' ) ),
		);
	}
	/**
	 * Render the task or contextual native group disclosure.
	 *
	 * @param mixed $module Module.
	 * @return void
	 */
	public static function render( $module = '' ) {
		$guides = array(
			'product_input_fields' => 'woocommerce-product-input-fields',
			'product_addons'       => 'woocommerce-product-addons',
			'product_custom_info'  => 'woocommerce-product-info',
			'product_info'         => 'woocommerce-product-info',
			'product_tabs'         => 'woocommerce-custom-product-tabs',
		);
		if ( $module && ! isset( $guides[ $module ] ) ) {
			return; }
		echo '<details class="wcj-question-groups"><summary>' . esc_html__( 'Everything else in this family: full settings and help', 'woocommerce-jetpack' ) . '</summary><p>' . esc_html__( 'Full settings open in a new tab; your draft stays here. Native edits have their own save and recovery limits, not this task\'s Undo. Return and reload current to see saved changes.', 'woocommerce-jetpack' ) . '</p><ul>';
		foreach ( self::groups() as $g ) {
			if ( ( $module && $g[0] !== $module ) || ! WCJ_Task_Finder::allowed( $g[0] ) ) {
				continue; }
			echo '<li data-question-group="' . esc_attr( $g[1] ) . '"><strong>' . esc_html( $g[2] ) . '</strong> <span>' . esc_html( wcj_is_module_enabled( $g[0] ) ? __( 'Module on', 'woocommerce-jetpack' ) : __( 'Module off', 'woocommerce-jetpack' ) ) . '</span><p>' . esc_html( $g[3] ) . '</p><a target="_blank" rel="noopener noreferrer" href="' . esc_url( self::native_url( $g[0] ) ) . '">' . esc_html__( 'Full settings (new tab)', 'woocommerce-jetpack' ) . '</a> | <a target="_blank" rel="noopener noreferrer" href="' . esc_url( 'https://booster.io/docs/' . $guides[ $g[0] ] . '/#' . $g[1] ) . '">' . esc_html__( 'Help for this group (new tab)', 'woocommerce-jetpack' ) . '</a></li>';
		}
		echo '</ul></details>';
	}
}

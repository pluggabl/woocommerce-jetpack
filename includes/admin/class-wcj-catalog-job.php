<?php
/**
 * Optional, bounded native catalog settings tasks. No storefront replacement or schema renderer.
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;

abstract class WCJ_Catalog_Job {
	public static function catalogue() {
		$rows = array(
			'breadcrumbs' => array( 'Choose the breadcrumb home link', 'Breadcrumbs', 'woocommerce-breadcrumbs', 'navigation home trail' ),
			'cross_sells' => array( 'Choose cross-sells in the cart', 'Cross-sells in the cart', 'woocommerce-cross-sells', 'recommend cart replace products' ),
			'frequently_bought_together' => array( 'Suggest products bought together', 'Frequently bought together', 'woocommerce-frequently-bought-together', 'bundle suggestions product recommendations' ),
			'product_images' => array( 'Choose which product images appear', 'Product images', 'woocommerce-product-images', 'hide image gallery thumbnail placeholder' ),
			'product_listings' => array( 'Choose shop and category listings', 'Shop and category listings', 'woocommerce-product-listings', 'categories counts empty price range' ),
			'product_variation_swatches' => array( 'Show variation choices as swatches', 'Variation swatches', 'woocommerce-product-variation-swatches', 'attribute term color image button' ),
			'products_per_page' => array( 'Let shoppers choose products per page', 'Products per page', 'woocommerce-products-per-page', 'count pagination selector' ),
			'related_products' => array( 'Choose related product suggestions', 'Related products', 'woocommerce-related-products', 'matching category tag attribute recommendations' ),
			'sorting' => array( 'Arrange product sorting choices', 'Product sorting', 'woocommerce-sorting', 'sort name sku stock default order' ),
			'upsells' => array( 'Choose upsells on product pages', 'Upsells', 'woocommerce-upsells', 'recommend alternative product' ),
		);
		$out = array();
		foreach ( $rows as $id => $row ) {
			$out[ $id ] = array( $row[0], $row[1], 'catalog-' . $id, 'WCJ_Catalog_' . $id . '_Job', $row[2], $row[3] );
		}
		return $out;
	}
	public static function edition() {
		$file = defined( 'WCJ_PLUGIN_FILE' ) ? basename( WCJ_PLUGIN_FILE ) : '';
		return 'booster-elite-for-woocommerce.php' === $file ? 'Elite' : ( 'booster-plus-for-woocommerce.php' === $file ? 'Plus' : 'Free' );
	}
	public static function available() { return isset( WCJ_Job_Store::core()->all_modules[ static::ID ] ); }
	public static function allowed() { return WCJ_Task_Finder::allowed( static::ID ); }
	public static function register() {
		foreach ( self::catalogue() as $id => $task ) {
			if ( ! WCJ_Task_Finder::allowed( $id ) ) { continue; }
			add_submenu_page( 'woocommerce', $task[1], $task[1], 'manage_woocommerce', 'wcj-' . $task[2] . '-job', array( $task[3], 'render' ) );
		}
	}
	public static function url( $field = '' ) {
		$url = admin_url( 'admin.php?page=wcj-catalog-' . static::ID . '-job' );
		return $field ? add_query_arg( 'field', $field, $url ) : $url;
	}
	public static function native_url() {
		$category = 'breadcrumbs' === static::ID ? 'emails_and_misc' : ( 'frequently_bought_together' === static::ID ? 'cart_and_checkout' : 'products' );
		return admin_url( wcj_admin_tab_url() . '&wcj-cat=' . $category . '&section=' . static::ID );
	}
	public static function summary( $id ) {
		if ( 'frequently_bought_together' === $id && 'Free' === self::edition() ) { return 'Not included in this installed Free package. Legacy Plus and Elite include the native module.'; }
		return 'Save one named native setting, inspect the result, or use the unchanged full editor. Activation is separate.';
	}
	public static function groups( $id ) {
		$all = array(
			'breadcrumbs' => array( 'breadcrumbs-home' => 'Home link and visibility', 'booster-v10-limits' => 'Theme and Blocks breadcrumb placement' ),
			'cross_sells' => array( 'cross-sells-layout' => 'Cross-sell layout and ordering', 'cross-sells-products' => 'Global and native linked products', 'cross-sells-cart-replacement' => 'Cart replacement and AJAX' ),
			'frequently_bought_together' => array( 'fbt-display' => 'Display and global suggestions', 'fbt-product' => 'Individual product overrides' ),
			'product_images' => array( 'product-images-visibility' => 'Image visibility', 'product-images-replacement' => 'HTML replacement and placeholder', 'product-images-template-priorities' => 'Template callback priorities', 'product-images-product' => 'Five individual product image settings' ),
			'product_listings' => array( 'listings-shop' => 'Shop listings', 'listings-category' => 'Category display rules', 'listings-price-visibility' => 'Price-range visibility' ),
			'product_variation_swatches' => array( 'swatches-display' => 'Swatch display settings', 'swatches-attributes' => 'Attribute types and variation choices', 'swatches-terms' => 'Term colors and images' ),
			'products_per_page' => array( 'per-page-options' => 'Default and selector choices', 'per-page-template-state' => 'Selector templates, request method and saved shopper preference' ),
			'related_products' => array( 'related-layout' => 'Related product count and columns', 'related-matching-rules' => 'Ordering, matching and conditional hiding', 'related-product' => 'Manual product choices: enabled and empty intentionally hides' ),
			'sorting' => array( 'sorting-labels' => 'Sorting labels and ordering', 'sorting-numeric-sku' => 'Numeric SKU', 'sorting-theme-restore' => 'Remove sorting or restore the Avada placement' ),
			'upsells' => array( 'upsells-display' => 'Upsell display', 'upsells-products' => 'Global upsells and native Linked products' ),
		);
		return $all[ $id ] ?? array();
	}
	public static function guide( $id, $anchor, $label ) {
		$t = self::catalogue()[ $id ];
		return '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( 'https://booster.io/docs/' . $t[4] . '/#' . $anchor ) . '">' . esc_html( $label ) . ' <span class="screen-reader-text">(opens in a new tab)</span></a>';
	}
	public static function native_help( $id ) {
		if ( ! isset( self::catalogue()[ $id ] ) || ! WCJ_Task_Finder::allowed( $id ) ) { return; }
		echo '<div class="wcj-catalog-native-help"><p><strong>Specialist instructions:</strong> ';
		foreach ( self::groups( $id ) as $anchor => $label ) { echo self::guide( $id, $anchor, $label ) . ' &nbsp; '; }
		echo '</p><p>Full settings retain every native option. Opening a guide does not save this form. Native settings may migrate older stored formats when opened.</p></div>';
	}
	public static function object_allowed( $id, $product = true ) {
		return isset( self::catalogue()[ $id ] ) && ( $product ? current_user_can( 'edit_post', get_the_ID() ) : current_user_can( 'manage_product_terms' ) ) && ( ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( $id ) ) && ( ! wcj_is_module_enabled( 'modules_by_user_roles' ) || wcj_check_modules_by_user_roles( $id ) );
	}
	public static function object_help( $id, $anchor, $label, $product = true ) {
		if ( ! self::object_allowed( $id, $product ) ) { return; }
		echo '<p class="wcj-catalog-object-help">' . self::guide( $id, $anchor, $label ) . '</p>';
	}
	public static function fields() {
		$all = require __DIR__ . '/catalog-job-fields.php';
		$fields = $all[ static::ID ];
		if ( 'Elite' !== self::edition() ) {
			unset( $fields['wcj_product_button_variation_item_width'], $fields['wcj_product_button_variation_item_height'] );
		}
		if ( isset( $fields['wcj_breadcrumbs_home_url'] ) ) { $fields['wcj_breadcrumbs_home_url']['default'] = home_url(); }
		if ( 'sorting' === static::ID ) {
			foreach ( WCJ_Job_Store::core()->all_modules['sorting']->get_woocommerce_default_sortings() as $sorting_key => $label ) {
				$fields[ 'wcj_sorting_default_sorting_' . str_replace( '-', '_', $sorting_key ) ]['default'] = $label;
			}
			$translated = array(
				'wcj_sorting_by_name_asc_text' => __( 'Sort by title: A to Z', 'woocommerce-jetpack' ),
				'wcj_sorting_by_name_desc_text' => __( 'Sort by title: Z to A', 'woocommerce-jetpack' ),
				'wcj_sorting_by_sku_asc_text' => __( 'Sort by SKU: low to high', 'woocommerce-jetpack' ),
				'wcj_sorting_by_sku_desc_text' => __( 'Sort by SKU: high to low', 'woocommerce-jetpack' ),
				'wcj_sorting_by_stock_quantity_asc_text' => __( 'Sort by stock quantity: low to high', 'woocommerce-jetpack' ),
				'wcj_sorting_by_stock_quantity_desc_text' => __( 'Sort by stock quantity: high to low', 'woocommerce-jetpack' ),
			);
			foreach ( $translated as $key => $label ) { $fields[ $key ]['default'] = $label; }
		}
		if ( 'products_per_page' === static::ID ) {
			$fields['wcj_products_per_page_text']['default'] = sprintf( __( 'Products <strong>%1$s - %2$s</strong> from <strong>%3$s</strong>. Products on page %4$s', 'woocommerce-jetpack' ), '%from%', '%to%', '%total%', '%select_form%' ) . '<br>';
		}
		return $fields;
	}
	public static function editable( $f ) {
		if ( 'direct' !== $f['mode'] ) { return false; }
		return 'all' === $f['gate'] || ( 'elite' === $f['gate'] && 'Elite' === self::edition() && 'yes' === apply_filters( 'booster_option', 'no', 'yes' ) ) || ( 'paid' === $f['gate'] && 'Free' !== self::edition() && 'yes' === apply_filters( 'booster_option', 'no', 'yes' ) );
	}
	public static function choices( $f ) {
		if ( 'products' === $f['provider'] ) {
			$out = array();
			foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => 256, 'orderby' => 'title', 'order' => 'ASC' ) ) as $product ) { if ( current_user_can( 'read_post', $product->get_id() ) ) { $out[ $product->get_id() ] = $product->get_name() . ' (#' . $product->get_id() . ')'; } }
			return $out;
		}
		if ( in_array( $f['provider'], array( 'product_cat', 'product_tag' ), true ) ) {
			$out = array(); $terms = get_terms( array( 'taxonomy' => $f['provider'], 'hide_empty' => false, 'number' => 256 ) );
			if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { $out[ $term->term_id ] = $term->name; } }
			return $out;
		}
		return (array) $f['options'];
	}
	public static function value( $state, $f ) { return $state['exists'] ? WCJ_Job_Store::state_value( $state ) : $f['default']; }
	public static function display( $v ) { return is_scalar( $v ) ? (string) $v : wp_json_encode( $v ); }
	private static function journal() { return 'booster_catalog_v10_' . static::ID; }
	private static function read( $key ) { return WCJ_Job_Store::read_state( $key ); }
	private static function states() {
		$keys = array_merge( array_keys( static::fields() ), array( 'wcj_' . static::ID . '_enabled', self::journal() ) );
		if ( 'cross_sells' === static::ID ) { $keys[] = 'woocommerce_enable_ajax_add_to_cart'; }
		$out = array(); foreach ( $keys as $key ) { $out[ $key ] = self::read( $key ); }
		return $out;
	}
	private static function guard( $states ) { return hash_hmac( 'sha256', serialize( array( get_current_user_id(), static::ID, self::edition(), $states ) ), wp_salt( 'nonce' ) ); }
	private static function post( $key ) { return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; }
	private static function receipt( $states ) {
		$r = WCJ_Job_Store::state_value( $states[ self::journal() ] );
		return is_array( $r ) && 1 === ( $r['v'] ?? 0 ) && get_current_user_id() === ( $r['user'] ?? 0 ) && isset( $r['field'], $r['before'], $r['after'] ) ? $r : null;
	}
	public static function resume() {
		if ( ! static::allowed() || ! static::available() ) { return ''; }
		$states = self::states(); $r = self::receipt( $states ); $fields = static::fields();
		return $r && isset( $fields[ $r['field'] ] ) && self::editable( $fields[ $r['field'] ] ) && $states[ $r['field'] ] === $r['after'] ? static::url( $r['field'] ) : '';
	}
	private static function validate( $key, $f, $v ) {
		if ( 'multiselect' === $f['type'] ) {
			if ( '' === $v ) { $v = array(); }
			if ( ! is_array( $v ) || count( $v ) > 256 ) { throw new RuntimeException( 'Use the native editor for this nonstandard selection.' ); }
			$choices = self::choices( $f ); $out = array();
			foreach ( $v as $item ) {
				if ( ! is_scalar( $item ) || ! array_key_exists( (string) $item, $choices ) ) { throw new RuntimeException( 'A selected item is missing or outside this bounded list. Its native value was kept; use full settings.' ); }
				$out[] = (string) $item;
			}
			return array_values( array_unique( $out ) );
		}
		if ( ! is_scalar( $v ) || is_bool( $v ) ) { throw new RuntimeException( 'Custom value kept. Use full settings.' ); }
		$v = (string) $v;
		if ( strlen( $v ) > 5000 || false === preg_match( '//u', $v ) || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $v ) ) { throw new RuntimeException( 'Invalid or oversized value. The native setting was kept.' ); }
		if ( 'checkbox' === $f['type'] && ! in_array( $v, array( 'yes', 'no' ), true ) ) { throw new RuntimeException( 'Choose Yes or No.' ); }
		if ( 'select' === $f['type'] && ! array_key_exists( $v, self::choices( $f ) ) ) { throw new RuntimeException( 'Custom selection kept. Use full settings.' ); }
		if ( 'number' === $f['type'] ) {
			if ( ! preg_match( '/^-?\d+(?:\.\d+)?$/', $v ) || ! is_finite( (float) $v ) || ( null !== $f['min'] && (float) $v < (float) $f['min'] ) || ( null !== $f['max'] && (float) $v > (float) $f['max'] ) ) { throw new RuntimeException( 'Use a number within the native setting range.' ); }
			if ( null === $f['step'] && false !== strpos( $v, '.' ) ) { throw new RuntimeException( 'Use a whole number for this setting.' ); }
		}
		if ( in_array( $f['type'], array( 'text', 'textarea' ), true ) && preg_match( '/[<>\[\]]/', $v ) ) { throw new RuntimeException( 'Custom markup or shortcode kept. Use full settings.' ); }
		if ( in_array( $key, array( 'wcj_breadcrumbs_home_url', 'wcj_product_images_placeholder_src' ), true ) && '' !== $v && ( ! wp_http_validate_url( $v ) && ! preg_match( '~^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?(?:/|$)~i', $v ) ) ) { throw new RuntimeException( 'Use an HTTP or HTTPS URL. Custom native values stay in full settings.' ); }
		if ( 'wcj_products_per_page_select_options' === $key ) {
			foreach ( preg_split( '/\r?\n/', $v ) as $line ) {
				if ( ! preg_match( '/^[^|<>\r\n]+\|(?:[1-9]\d*|-1)$/', trim( $line ) ) ) { throw new RuntimeException( 'Use one Label|count per line; a positive count or -1 for all.' ); }
			}
		}
		if ( 'wcj_sorting_rearrange' === $key && '' !== trim( $v ) ) {
			$known = array( 'menu_order', 'popularity', 'rating', 'date', 'price', 'price-desc', 'title_asc', 'title_desc', 'sku_asc', 'sku_desc', 'stock_quantity_asc', 'stock_quantity_desc' );
			foreach ( preg_split( '/\r?\n/', trim( $v ) ) as $item ) { if ( ! in_array( trim( $item ), $known, true ) ) { throw new RuntimeException( 'A custom sorting key was kept. Use full settings for plugin-added choices.' ); } }
		}
		return $v;
	}
	public static function lock_reason( $key, $f, $state ) {
		if ( ! self::editable( $f ) ) { return 'expert' === $f['mode'] ? 'This specialist setting stays in full settings.' : 'Not editable in this installed edition. Its native value is kept.'; }
		if ( has_filter( 'pre_option_' . $key ) || has_filter( 'option_' . $key ) || has_filter( $key ) ) { return 'A site filter owns this value. Use full settings; no guided write is allowed.'; }
		if ( ! $state['exists'] ) { return ''; } // An absent native option is not a custom stored value.
		try { self::validate( $key, $f, self::value( $state, $f ) ); } catch ( RuntimeException $e ) { return 'Custom value kept: ' . $e->getMessage(); }
		return '';
	}
	private static function consequential( $key ) { return in_array( $key, array( 'wcj_cross_sells_replace_with_cross_sells', 'wcj_sorting_remove_all_enabled', 'wcj_sorting_restore_default_sorting' ), true ); }
	private static function preview_token( $states, $key, $value, $sample, $preview ) { return hash_hmac( 'sha256', serialize( array( self::guard( $states ), $key, $value, $sample, $preview ) ), wp_salt( 'nonce' ) ); }
	public static function form_state( $key, $states ) {
		wp_nonce_field( 'wcj_catalog_' . static::ID . '_' . $key, '_catalog_nonce' );
		echo '<input type="hidden" name="catalog_guard" value="' . esc_attr( self::guard( $states ) ) . '">';
	}
	/** Exactly one whitelisted native key and one owned receipt. No grouped schema save. */
	private static function change( $key ) {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! static::allowed() || ! static::available() ) { throw new RuntimeException( 'Ask an administrator for access.' ); }
		$fields = static::fields();
		if ( ! isset( $fields[ $key ] ) || ! self::editable( $fields[ $key ] ) || ! wp_verify_nonce( self::post( '_catalog_nonce' ), 'wcj_catalog_' . static::ID . '_' . $key ) ) { throw new RuntimeException( 'This choice is unavailable or the form expired. Reload.' ); }
		$op = self::post( 'catalog_op' );
		if ( ! in_array( $op, array( 'save', 'undo', 'preview', 'preview_undo' ), true ) ) { throw new RuntimeException( 'Unknown action.' ); }
		$lockkey = self::journal() . '_lock'; $lock = null;
		if ( ! in_array( $op, array( 'preview', 'preview_undo' ), true ) ) { $lock = WCJ_Job_Store::lock( $lockkey, 'Another catalog change is running. Reload.' ); }
		try {
			$states = self::states();
			if ( ! hash_equals( self::guard( $states ), self::post( 'catalog_guard' ) ) ) { throw new RuntimeException( 'Native settings or your receipt changed elsewhere. Reload; newer values were kept.' ); }
			$reason = self::lock_reason( $key, $fields[ $key ], $states[ $key ] );
			if ( $reason ) { throw new RuntimeException( $reason ); }
			$prior = $states[ self::journal() ]; $old = $states[ $key ]; $r = self::receipt( $states );
			if ( in_array( $op, array( 'undo', 'preview_undo' ), true ) ) {
				if ( ! $r || $r['field'] !== $key || $old !== $r['after'] ) { throw new RuntimeException( 'Undo is unavailable because this native value changed. Nothing was overwritten.' ); }
				$new = $r['before'];
			} else {
				$v = isset( $_POST['catalog_value'] ) ? wp_unslash( $_POST['catalog_value'] ) : ( 'multiselect' === $fields[ $key ]['type'] ? array() : null );
				$v = self::validate( $key, $fields[ $key ], $v );
				$new = WCJ_Job_Store::value_state( $v );
			}
			$sample = absint( self::post( 'sample_product' ) );
			$preview = array();
			if ( in_array( $op, array( 'preview', 'preview_undo' ), true ) || self::consequential( $key ) ) {
				try { $preview = static::preview( $sample, $key, self::value( $new, $fields[ $key ] ) ); }
				catch ( RuntimeException $e ) { throw $e; }
				catch ( Throwable $e ) { throw new RuntimeException( 'The native preview could not resolve this product or relationship. This guided preview saved no setting. Check the current products and full settings.' ); }
			}
			if ( in_array( $op, array( 'preview', 'preview_undo' ), true ) ) { $v = self::value( $new, $fields[ $key ] ); return array( 'message' => 'Read-only preview. No setting or activation was saved.', 'preview' => $preview, 'value' => $v, 'sample' => $sample, 'undo_preview' => 'preview_undo' === $op, 'token' => self::preview_token( $states, $key, $v, $sample, $preview ) ); }
			if ( self::consequential( $key ) && ( 'yes' !== self::post( 'confirm_consequence' ) || ! hash_equals( self::preview_token( $states, $key, self::value( $new, $fields[ $key ] ), $sample, $preview ), self::post( 'preview_token' ) ) ) ) { throw new RuntimeException( 'Preview this exact choice and confirm its consequence before saving. No setting changed.' ); }
			if ( $old === $new ) { return array( 'message' => 'No change needed.' ); }
			if ( 'undo' === $op ) {
				if ( ! WCJ_Job_Store::compare_exchange( $key, $old, $new ) || self::read( $key ) !== $new ) { throw new RuntimeException( 'The original value could not be verified. Check full settings.' ); }
				if ( ! WCJ_Job_Store::compare_exchange( self::journal(), $prior, array( 'exists' => false, 'raw' => '' ) ) ) { throw new RuntimeException( 'Native value restored; the receipt changed elsewhere. Check current settings.' ); }
				return array( 'message' => 'Previous native value restored and read back. Other settings were not changed.' );
			}
			$receipt = WCJ_Job_Store::value_state( array( 'v' => 1, 'user' => get_current_user_id(), 'field' => $key, 'before' => $old, 'after' => $new ) );
			if ( ! WCJ_Job_Store::compare_exchange( self::journal(), $prior, $receipt, array( self::journal() ) ) ) { throw new RuntimeException( 'Another change owns the receipt. Nothing was applied.' ); }
			if ( ! WCJ_Job_Store::compare_exchange( $key, $old, $new ) ) {
				WCJ_Job_Store::compare_exchange( self::journal(), $receipt, $prior, array( self::journal() ) );
				throw new RuntimeException( 'A native writer changed the setting before saving. Reload; its value was kept.' );
			}
			if ( self::read( $key ) !== $new || self::read( self::journal() ) !== $receipt ) { throw new RuntimeException( 'Readback changed. Inspect full settings before making another edit.' ); }
			return array( 'message' => 'Saved and read back from the native setting. Module activation was not changed.' );
		} finally { if ( null !== $lock ) { WCJ_Job_Store::unlock( $lockkey, $lock ); } }
	}
	/** Saved rule resolution without Woo's related-products transient writer or a cart/session mutation. */
	private static function related_ids( $product ) {
		$id = $product->get_id();
		$cats = apply_filters( 'woocommerce_product_related_posts_relate_by_category', true, $id ) ? apply_filters( 'woocommerce_get_related_product_cat_terms', wc_get_product_term_ids( $id, 'product_cat' ), $id ) : array();
		$tags = apply_filters( 'woocommerce_product_related_posts_relate_by_tag', true, $id ) ? apply_filters( 'woocommerce_get_related_product_tag_terms', wc_get_product_term_ids( $id, 'product_tag' ), $id ) : array();
		$args = apply_filters( 'woocommerce_output_related_products_args', array( 'posts_per_page' => 4, 'columns' => 4, 'orderby' => 'rand', 'order' => 'desc' ) );
		$configured = (int) ( $args['posts_per_page'] ?? 4 );
		$limit = $configured < 0 ? 100 : max( 0, min( 100, $configured ) );
		$exclude = array_merge( array( 0, $id ), $product->get_upsell_ids() );
		$ids = ( $cats || $tags || apply_filters( 'woocommerce_product_related_posts_force_display', false, $id ) ) ? WC_Data_Store::load( 'product' )->get_related_products( $cats, $tags, $exclude, $limit + 10, $id ) : array();
		$ids = apply_filters( 'woocommerce_related_products', $ids, $id, array( 'limit' => $limit, 'excluded_ids' => $exclude ) );
		$products = array_filter( array_map( 'wc_get_product', is_array( $ids ) ? $ids : array() ), 'wc_products_array_filter_visible' );
		$products = wc_products_array_orderby( $products, $args['orderby'] ?? 'rand', $args['order'] ?? 'desc' );
		return array_map( static function ( $p ) { return $p->get_id(); }, array_slice( $products, 0, $limit ) );
	}
	public static function preview( $sample, $key = '', $value = null ) {
		if ( ! static::allowed() || ! static::available() ) { throw new RuntimeException( 'Ask an administrator for access.' ); }
		$out = array( 'module' => static::ID, 'module_on' => wcj_is_module_enabled( static::ID ), 'note' => 'Read-only current native rules, not a theme screenshot or a guarantee about Blocks. No cart, order, cache purge or activation is created.', 'items' => array() );
		if ( 'sorting' === static::ID ) {
			$defaults = array(
				'menu_order' => __( 'Default sorting', 'woocommerce' ),
				'popularity' => __( 'Sort by popularity', 'woocommerce' ),
				'rating' => __( 'Sort by average rating', 'woocommerce' ),
				'date' => __( 'Sort by latest', 'woocommerce' ),
				'price' => __( 'Sort by price: low to high', 'woocommerce' ),
				'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
			);
			$out['current_choices'] = apply_filters( 'woocommerce_catalog_orderby', $defaults );
			if ( 'menu_order' !== apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) ) ) { unset( $out['current_choices']['menu_order'] ); }
			if ( ! wc_review_ratings_enabled() ) { unset( $out['current_choices']['rating'] ); }
			$out['placement_hidden'] = wcj_is_module_enabled( static::ID ) && 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_sorting_remove_all_enabled', 'no' ) );
			$out['placement_status'] = $out['placement_hidden'] ? 'The active native Remove All Sorting rule suppresses the sorting control. These eligible labels are not a promise of a visible dropdown.' : 'No active native Remove All Sorting rule suppresses the control. The theme can still omit or replace it.';
			$out['note'] .= ' Classic shop-archive dropdown labels through current native filters, not search results or the shorter useLabel/Blocks variant. This is not a theme screenshot.';
			$out['proposed'] = 'wcj_sorting_remove_all_enabled' === $key ? ( 'yes' === $value ? 'Remove the Classic sorting control when this module is on.' : 'Stop removing the sorting control; other sorting rules still apply.' ) : ( 'wcj_sorting_restore_default_sorting' === $key ? 'Change the native Avada sorting placement option only. This is not a reset of sorting labels or all themes.' : 'Inspect these current choices before editing.' );
			return $out;
		}
		if ( 'products_per_page' === static::ID ) {
			$out['selector'] = 'wcj_products_per_page_select_options' === $key ? $value : apply_filters( 'booster_option', "10|10\n25|25\n50|50\n100|100\nAll|-1", wcj_get_option( 'wcj_products_per_page_select_options', "10|10\n25|25\n50|50\n100|100\nAll|-1" ) );
			$out['default'] = 'wcj_products_per_page_default' === $key ? $value : wcj_get_option( 'wcj_products_per_page_default', 10 );
			$out['persistence'] = wcj_get_option( 'wcj_products_per_page_saving_method', 'cookie' );
			return $out;
		}
		$product = wc_get_product( $sample );
		if ( ! $product || 'publish' !== $product->get_status() || ! current_user_can( 'read_post', $sample ) ) { throw new RuntimeException( 'Choose an existing published sample product. Nothing was saved.' ); }
		$out['sample'] = array( 'id' => $sample, 'name' => $product->get_name(), 'type' => $product->get_type() );
		$out['product_url'] = get_permalink( $sample );
		$old_post = $GLOBALS['post'] ?? null; $old_product = $GLOBALS['product'] ?? null;
		$GLOBALS['post'] = get_post( $sample ); $GLOBALS['product'] = $product;
		try {
			$ids = array();
			if ( 'cross_sells' === static::ID ) {
				$ids = $product->get_cross_sell_ids();
				$out['sample_cart'] = 'One unit of the selected product. This is not your real cart.';
				$out['ajax_dependency'] = 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' ) ? 'AJAX add to cart on archives is enabled. Native cart replacement requires it to be disabled in WooCommerce settings.' : 'Native AJAX dependency is satisfied; still test the actual Classic cart link.';
				if ( 'wcj_cross_sells_replace_with_cross_sells' === $key ) {
					$out['proposed'] = 'yes' === $value ? 'Requested native policy: replace matching original cart lines only after a valid simple cross-sell is successfully added. Selecting options, a rejected add or an invalid link must keep originals. Verify the actual Classic cart; this changes contents, not only appearance.' : 'Requested native policy: keep original cart products when adding cross-sells.';
				} else { $out['saved_replacement_policy'] = wcj_is_module_enabled( static::ID ) && 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_cross_sells_replace_with_cross_sells', 'no' ) ) ? 'Replacement policy is enabled in the active module; test the actual Classic cart.' : 'No active native replacement policy in this module.'; }
				$out['replacement_eligible_ids'] = array();
				foreach ( $ids as $cross_id ) {
					$cross = wc_get_product( $cross_id );
					if ( $cross && $cross->get_id() !== $product->get_id() && 'publish' === $cross->get_status() && $cross->is_type( 'simple' ) && $cross->is_purchasable() && $cross->is_in_stock() ) { $out['replacement_eligible_ids'][] = $cross->get_id(); }
				}
				$out['note'] .= ' Replacement links apply to eligible simple cross-sells. Variable/external Select options links carry no removal intent. A failed add keeps originals; actual cart validation still applies.';
				if ( 'wcj_cross_sells_replace_with_cross_sells' === $key && 'yes' === $value && ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' ) || ! $out['replacement_eligible_ids'] || ! $product->is_type( 'simple' ) ) ) { throw new RuntimeException( 'To confirm replacement, choose a simple sample with an in-stock, purchasable simple cross-sell and disable the native AJAX dependency separately. No setting was saved.' ); }
			} elseif ( 'upsells' === static::ID ) {
				$ids = $product->get_upsell_ids();
			} elseif ( 'related_products' === static::ID ) {
				$module = WCJ_Job_Store::core()->all_modules[ static::ID ];
				$out['placement_hidden'] = wcj_is_module_enabled( static::ID ) && 'yes' === wcj_get_option( 'wcj_product_info_related_products_hide', 'no' ) && ( $module->do_hide_for_all_products() || $module->do_hide_for_product( $sample ) );
				$out['placement_status'] = $out['placement_hidden'] ? 'The native Related Products placement is hidden for this product by the active hide rule. Candidate IDs are not a promise of visible suggestions.' : 'No active native hide rule removes this product placement. The actual theme can still replace or omit it.';
				$out['configured_count'] = wcj_is_module_enabled( static::ID ) ? wcj_get_option( 'wcj_product_info_related_products_num', 3 ) : 4;
				$out['preview_bound'] = 'At most 100 candidates; -1 or any larger native count is bounded here without changing the merchant setting.';
				$ids = self::related_ids( $product );
				$out['per_product_enabled'] = get_post_meta( $sample, '_wcj_product_info_related_products_enabled', true );
				$out['per_product_ids'] = get_post_meta( $sample, '_wcj_product_info_related_products_ids', true );
				$out['note'] .= ' This resolves the active native query without writing its transient. Random order may differ; output is capped at 100. Enabled with an empty manual selection intentionally hides suggestions.';
			} elseif ( 'frequently_bought_together' === static::ID ) {
				if ( $product->is_type( array( 'external', 'grouped' ) ) ) { $out['note'] .= ' This native product type does not show the bundle.'; }
				else {
					$module = WCJ_Job_Store::core()->all_modules[ static::ID ]; $items = array();
					if ( 'yes' === get_post_meta( $sample, '_wcj_product_fbt_enabled', true ) ) { $items = $module->wcj_fbt_get_single_items( $sample ); }
					if ( ! $items && 'yes' === get_option( 'wcj_fbt_global_enabled', 'no' ) ) { $items = $module->wcj_fbt_get_global_items(); }
					$ids = array_merge( array( $sample ), wp_list_pluck( (array) $items, 'id' ) );
					$out['note'] .= ' Actual native configured bundle candidates. Variable choices, stock and purchase eligibility must be checked on the product page; this preview never adds them.';
				}
			} elseif ( 'product_images' === static::ID ) {
				$out['image_id'] = $product->get_image_id(); $out['gallery_ids'] = $product->get_gallery_image_ids();
				foreach ( array( 'custom_on_single', 'custom_on_archives' ) as $part ) { $out[ $part ] = get_post_meta( $sample, '_wcj_product_images_meta_' . $part, true ); }
				foreach ( array( 'hide_image_on_single', 'hide_thumb_on_single', 'hide_image_on_archives' ) as $part ) { $out[ $part ] = get_post_meta( $sample, '_wcj_product_images_' . $part, true ); }
				$out['placeholder_url'] = 'wcj_product_images_placeholder_src' === $key ? $value : wcj_get_option( 'wcj_product_images_placeholder_src', '' );
				$out['note'] .= ' HTML is shown as text, never executed in this admin preview. Open the real product to verify theme output.';
			} elseif ( 'product_variation_swatches' === static::ID ) {
				if ( ! $product->is_type( 'variable' ) ) { throw new RuntimeException( 'Choose a variable product to inspect real variation choices.' ); }
				foreach ( $product->get_variation_attributes() as $taxonomy => $options ) {
					$attribute_id = wc_attribute_taxonomy_id_by_name( $taxonomy );
					$type = $attribute_id ? get_option( 'wcj_attribute_type_' . $attribute_id, '' ) : '';
					$terms = array();
					foreach ( $options as $slug ) {
						$term = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $slug, $taxonomy ) : false;
						$terms[] = array( 'value' => $slug, 'color' => $term ? get_term_meta( $term->term_id, 'wcj_product_attribute_color', true ) : '', 'image' => $term ? get_term_meta( $term->term_id, 'wcj_product_attribute_image', true ) : '' );
					}
					$out['attributes'][ $taxonomy ] = array( 'type' => $type ?: 'Unconfigured / native dropdown', 'terms' => $terms );
				}
			}
			foreach ( array_unique( array_map( 'absint', $ids ) ) as $id ) {
				$p = wc_get_product( $id );
				if ( $p && 'publish' === $p->get_status() && current_user_can( 'read_post', $id ) ) { $out['items'][] = array( 'id' => $id, 'name' => $p->get_name(), 'type' => $p->get_type(), 'in_stock' => $p->is_in_stock() ); }
			}
		} finally { $GLOBALS['post'] = $old_post; $GLOBALS['product'] = $old_product; }
		return $out;
	}
	private static function product_state_note( $id, $product_id ) {
		if ( 'product_images' === $id ) {
			$keys = array( '_wcj_product_images_meta_custom_on_single', '_wcj_product_images_meta_custom_on_archives', '_wcj_product_images_hide_image_on_single', '_wcj_product_images_hide_thumb_on_single', '_wcj_product_images_hide_image_on_archives' ); $stored = 0;
			foreach ( $keys as $key ) { if ( metadata_exists( 'post', $product_id, $key ) ) { ++$stored; } }
			return $stored . ' of five image fields explicitly stored; panel requires module on.';
		}
		$prefix = 'related_products' === $id ? '_wcj_product_info_related_products_' : ( 'frequently_bought_together' === $id ? '_wcj_product_fbt_' : '' );
		if ( $prefix ) {
			$enabled = get_post_meta( $product_id, $prefix . 'enabled', true );
			$ids = get_post_meta( $product_id, $prefix . ( 'related_products' === $id ? 'ids' : 'products_ids' ), true );
			$count = is_array( $ids ) ? count( $ids ) : ( '' === $ids ? 0 : null );
			return 'Stored individual enable: ' . ( '' === $enabled ? 'native default' : $enabled ) . '; ' . ( null === $count ? 'custom selection kept' : $count . ' selected IDs' ) . ( 'related_products' === $id && 'yes' === $enabled && 0 === $count ? '; intentionally hidden when entitled manual mode is active.' : '.' );
		}
		$key = 'cross_sells' === $id ? '_crosssell_ids' : '_upsell_ids'; $ids = get_post_meta( $product_id, $key, true );
		return ( is_array( $ids ) ? count( $ids ) : 0 ) . ' stored native linked-product IDs; global rules may alter the active result.';
	}
	public static function elsewhere() {
		$id = static::ID;
		if ( in_array( $id, array( 'product_images', 'related_products', 'frequently_bought_together', 'cross_sells', 'upsells' ), true ) ) {
			if ( ! current_user_can( 'edit_products' ) ) { echo '<p>Product editors require product-edit permission. Ask an administrator for access.</p>'; return; }
			$shown = 0;
			echo '<p>Individual product settings live in the product editor. Global choices do not erase individual overrides. A panel may require its module and native feature switch to be on.</p><ul>';
			foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => 20, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $p ) {
				if ( current_user_can( 'edit_post', $p->get_id() ) ) { ++$shown; echo '<li><a target="_blank" rel="noopener noreferrer" href="' . esc_url( get_edit_post_link( $p->get_id(), 'raw' ) ) . '">' . esc_html( $p->get_name() ) . ' (#' . esc_html( $p->get_id() ) . ') - edit product (new tab)</a> ' . esc_html( self::product_state_note( $id, $p->get_id() ) ) . '</li>'; }
			}
			echo '</ul><p>' . esc_html( $shown ) . ' permitted published products shown (at most 20). <a target="_blank" rel="noopener noreferrer" href="' . esc_url( admin_url( 'edit.php?post_type=product' ) ) . '">Browse all products (new tab)</a>.</p>';
			if ( 'related_products' === $id ) { echo '<p><strong>Enabled + empty manual IDs = intentionally hidden.</strong> Clearing IDs is not a generic recovery action. Restore the prior enable state and IDs deliberately.</p>'; }
		}
		if ( 'product_variation_swatches' === $id ) {
			if ( ! current_user_can( 'manage_product_terms' ) ) { echo '<p>Attribute and term editing needs product-term permission. Ask an administrator for access.</p>'; return; }
			global $wpdb;
			$attributes = $wpdb->get_results( "SELECT attribute_id, attribute_name, attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies ORDER BY attribute_id LIMIT 101" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Bounded read-only native attribute index.
			$truncated = count( $attributes ) > 100; $attributes = array_slice( $attributes, 0, 100 ); $empty = 0;
			echo '<ul>';
			foreach ( $attributes as $attribute ) {
				$type = get_option( 'wcj_attribute_type_' . $attribute->attribute_id, '' ); if ( ! $type ) { ++$empty; }
				$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
				echo '<li>' . esc_html( $attribute->attribute_label . ': ' . ( $type ?: 'Unconfigured / native dropdown' ) ) . ' <a target="_blank" rel="noopener noreferrer" href="' . esc_url( admin_url( 'edit.php?post_type=product&page=product_attributes&edit=' . $attribute->attribute_id ) ) . '">Edit attribute (new tab)</a> <a target="_blank" rel="noopener noreferrer" href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=product' ) ) . '">Configure terms (new tab)</a></li>';
			}
			echo '</ul><p>' . esc_html( count( $attributes ) . ' global attributes shown' . ( $truncated ? ' (first 100 only)' : '' ) . '; ' . $empty . ' unconfigured swatch types in this list.' ) . ' Local product attributes may continue to use dropdowns.</p>';
		}
	}
	public static function render() {
		if ( ! static::allowed() ) { wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) ); }
		$t = self::catalogue()[ static::ID ]; $fields = static::available() ? static::fields() : array();
		$key = isset( $_GET['field'] ) && is_string( $_GET['field'] ) ? sanitize_key( wp_unslash( $_GET['field'] ) ) : array_key_first( $fields );
		if ( $fields && ! isset( $fields[ $key ] ) ) { wp_die( 'Unknown catalog setting.', '', array( 'response' => 400 ) ); }
		$result = array(); $error = '';
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { try { $result = self::change( $key ); } catch ( RuntimeException $e ) { $error = $e->getMessage(); } catch ( Throwable $e ) { $error = 'This operation could not be verified. Inspect current native settings before trying again; no automatic rollback of other changes is promised.'; } }
		$states = $fields ? self::states() : array(); $receipt = $fields ? self::receipt( $states ) : null;
		$reason = $fields ? self::lock_reason( $key, $fields[ $key ], $states[ $key ] ) : '';
		wp_enqueue_style( 'wcj-catalog-job', wcj_plugin_url() . '/assets/css/admin/wcj-catalog-job.css', array(), '1.1' );
		wp_enqueue_script( 'wcj-catalog-job', wcj_plugin_url() . '/assets/js/admin/wcj-catalog-job.js', array(), '1.0.3', true );
		include __DIR__ . '/views/catalog-job.php';
	}
}
final class WCJ_Catalog_breadcrumbs_Job extends WCJ_Catalog_Job { const ID = 'breadcrumbs'; }
final class WCJ_Catalog_cross_sells_Job extends WCJ_Catalog_Job { const ID = 'cross_sells'; }
final class WCJ_Catalog_frequently_bought_together_Job extends WCJ_Catalog_Job { const ID = 'frequently_bought_together'; }
final class WCJ_Catalog_product_images_Job extends WCJ_Catalog_Job { const ID = 'product_images'; }
final class WCJ_Catalog_product_listings_Job extends WCJ_Catalog_Job { const ID = 'product_listings'; }
final class WCJ_Catalog_product_variation_swatches_Job extends WCJ_Catalog_Job { const ID = 'product_variation_swatches'; }
final class WCJ_Catalog_products_per_page_Job extends WCJ_Catalog_Job { const ID = 'products_per_page'; }
final class WCJ_Catalog_related_products_Job extends WCJ_Catalog_Job { const ID = 'related_products'; }
final class WCJ_Catalog_sorting_Job extends WCJ_Catalog_Job { const ID = 'sorting'; }
final class WCJ_Catalog_upsells_Job extends WCJ_Catalog_Job { const ID = 'upsells'; }
add_action( 'admin_menu', array( 'WCJ_Catalog_Job', 'register' ), 99 );

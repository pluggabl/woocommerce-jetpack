<?php
/** @package Booster_For_WooCommerce/admin */
defined( 'ABSPATH' ) || exit;
$id = static::ID;
$f = $fields ? $fields[ $key ] : null;
$native = $f ? self::value( $states[ $key ], $f ) : '';
$editing_native = $native;
$default_choice_label = '';
if ( $f && ! $reason && ! $states[ $key ]['exists'] && 'select' === $f['type'] ) {
	$choices = self::choices( $f );
	if ( $choices && ! array_key_exists( (string) $native, $choices ) ) {
		$editing_native = (string) array_key_first( $choices );
		$default_choice_label = $choices[ $editing_native ];
	}
}
$display_choices = $f && in_array( $f['type'], array( 'select', 'multiselect' ), true ) ? self::choices( $f ) : array();
$human_value = static function ( $raw ) use ( $f, $display_choices ) {
	if ( 'checkbox' === $f['type'] && in_array( $raw, array( 'yes', 'no' ), true ) ) { return 'yes' === $raw ? 'On' : 'Off'; }
	if ( is_scalar( $raw ) && array_key_exists( (string) $raw, $display_choices ) ) { return $display_choices[ (string) $raw ]; }
	if ( is_array( $raw ) ) { return $raw ? count( $raw ) . ' selected (see technical details)' : 'No selection'; }
	$text = self::display( $raw );
	if ( '' === $text ) { return 'Empty'; }
	return strlen( $text ) > 160 ? 'Text saved (see technical details)' : $text;
};
$has_receipt = $receipt && $receipt['field'] === $key;
$current_heading = 'Current setting';
if ( ! empty( $result['message'] ) && ! isset( $result['preview'] ) ) {
	$headings = array(
		'Previous native value restored and read back. Other settings were not changed.' => 'Restored',
		'Saved and read back from the native setting. Module activation was not changed.' => 'Saved',
		'No change needed.' => 'Unchanged',
	);
	$current_heading = $headings[ $result['message'] ] ?? $result['message'];
}
$value = array_key_exists( 'value', $result ) ? $result['value'] : $editing_native;
if ( $error && isset( $_POST['catalog_value'] ) ) { $value = wp_unslash( $_POST['catalog_value'] ); }
$sample = absint( self::post( 'sample_product' ) );
$samples = $fields ? wc_get_products( array( 'status' => 'publish', 'limit' => 100, 'orderby' => 'ID', 'order' => 'ASC' ) ) : array();
$can_preview = in_array( $id, array( 'cross_sells', 'frequently_bought_together', 'product_images', 'product_variation_swatches', 'products_per_page', 'related_products', 'sorting', 'upsells' ), true );
$next_checks = array(
	'breadcrumbs' => 'Open a product page and check its breadcrumb trail and Home link.',
	'cross_sells' => 'Open a Classic cart with a matching original product and check its cross-sell suggestions. Adding a suggestion can change the cart when replacement is on. Do not place an order.',
	'frequently_bought_together' => 'Open a matching product and check the grouped suggestions and choices before adding anything to the cart.',
	'product_images' => 'Open a matching product and the shop page. Check the main image, thumbnails and any replacement content.',
	'product_listings' => 'Open the affected shop and category pages. Check which products and categories appear and how prices are shown.',
	'product_variation_swatches' => 'Open a matching variable product and select its attributes. Check the swatches and the selected variation.',
	'products_per_page' => 'Open the shop or category page and check the count selector. Choosing a count may update that shopper\'s remembered preference.',
	'related_products' => 'Open a matching product and check its related-product suggestions, count and layout.',
	'sorting' => 'Open the shop or category page. Check the sorting choices and resulting product order.',
	'upsells' => 'Open a matching product and check its upsell suggestions and layout.',
);

?>
<div class="wrap wcj-catalog-job" id="wcj-catalog-job" data-module="<?php echo esc_attr( $id ); ?>" data-user="<?php echo esc_attr( get_current_user_id() ); ?>" data-edition="<?php echo esc_attr( self::edition() ); ?>" data-response="<?php echo 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ? 'yes' : 'no'; ?>" data-field="<?php echo esc_attr( (string) $key ); ?>" data-guard="<?php echo esc_attr( $fields ? self::guard( $states ) : '' ); ?>" data-native="<?php echo esc_attr( wp_json_encode( $editing_native ) ); ?>" data-error="<?php echo $error ? 'yes' : 'no'; ?>" data-saved="<?php echo ! empty( $result['message'] ) && ! isset( $result['preview'] ) ? 'yes' : 'no'; ?>">
<h1><?php echo esc_html( $t[1] ); ?></h1>
<p><?php echo esc_html( $t[0] ); ?>. <strong><?php echo esc_html( self::edition() ); ?></strong> &middot; <?php echo esc_html( current_user_can( 'manage_options' ) ? 'Store administrator' : 'Permitted store manager' ); ?></p>
<?php if ( ! $fields ) : ?>
<section class="wcj-catalog-card"><h2>Not included in this installed package</h2><p>Frequently Bought Together is not available in Free. The existing native module is included in legacy Plus and Elite. No settings or product data are exposed here.</p><?php echo self::guide( $id, 'booster-v10-edition', 'Edition details' ); ?></section>
<?php else : ?>
<p><strong>Module <?php echo wcj_is_module_enabled( $id ) ? 'on' : 'off'; ?>.</strong> Saving here never turns the module on. <a href="<?php echo esc_url( static::native_url() ); ?>" target="_blank" rel="noopener noreferrer">Open full settings to change activation (new tab)</a>.</p>
<p>Full settings and help open in new tabs; unsaved edits stay here. After saving elsewhere, reopen guided setup from the full editor for fresh saved values.</p>
<?php if ( $error ) : ?><div role="alert" class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p><p>The attempted edit is kept below, but cannot be submitted again from this response. <a id="wcj-catalog-reload" href="<?php echo esc_url( static::url( $key ) ); ?>">Reload current native values</a> before deciding what to do. A conflicting stored draft must be deliberately discarded before a fresh edit.</p></div><?php endif; ?>
<div id="wcj-catalog-draft" role="status" class="wcj-catalog-note" hidden></div>
<div class="wcj-catalog-layout">
<section class="wcj-catalog-card">
<h2>Essentials: one native setting at a time</h2>
<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" id="wcj-catalog-scope">
<input type="hidden" name="page" value="<?php echo esc_attr( 'wcj-catalog-' . $id . '-job' ); ?>">
<label for="wcj-catalog-field">Choose a setting</label>
<select id="wcj-catalog-field" name="field">
<?php foreach ( $fields as $k => $row ) : ?>
<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $key ); ?>><?php echo esc_html( $row['label'] . ( 'expert' === $row['mode'] ? ' (full settings)' : ( self::editable( $row ) ? '' : ' (locked in this edition)' ) ) ); ?></option>
<?php endforeach; ?>
</select><button class="button" type="submit">Open setting</button>
</form>
<h3><?php echo esc_html( $f['label'] ); ?></h3>
<div class="wcj-catalog-current"<?php echo ! empty( $result['message'] ) && ! isset( $result['preview'] ) ? ' role="status"' : ''; ?>>
<p id="wcj-catalog-saved"><strong><?php echo esc_html( $current_heading ); ?>:</strong> <?php echo esc_html( $human_value( $native ) ); ?><?php echo $states[ $key ]['exists'] ? '' : ' (default, not explicitly saved)'; ?>.</p>
<?php if ( $has_receipt ) : ?><p>Previously: <?php echo esc_html( $human_value( self::value( $receipt['before'], $f ) ) ); ?><?php echo $receipt['before']['exists'] ? '' : ' (default, not explicitly saved)'; ?>.</p><?php endif; ?>
</div>
<details><summary>Technical details</summary><p>Native key: <code><?php echo esc_html( $key ); ?></code>. Stored explicitly: <?php echo $states[ $key ]['exists'] ? 'Yes' : 'No'; ?>.</p><p>Saved native value<?php echo $states[ $key ]['exists'] ? '' : ' (default, not explicitly stored)'; ?>:</p><pre class="wcj-catalog-value"><?php echo esc_html( self::display( $native ) ); ?></pre>
<?php if ( $has_receipt ) : ?><p>Previous native value<?php echo $receipt['before']['exists'] ? '' : ' (default, not explicitly stored)'; ?>:</p><pre class="wcj-catalog-value"><?php echo esc_html( self::display( self::value( $receipt['before'], $f ) ) ); ?></pre><?php endif; ?>
</details>
<?php if ( $has_receipt ) : ?>
<section class="wcj-catalog-history" aria-label="Undo last saved change">
<?php if ( ! $error && ! $reason && $states[ $key ] === $receipt['after'] ) : ?>
<form method="post" action="<?php echo esc_url( static::url( $key ) ); ?>" class="wcj-catalog-undo"><?php self::form_state( $key, $states ); ?>
<?php if ( self::consequential( $key ) ) : ?>
<input type="hidden" name="sample_product" value="<?php echo esc_attr( $sample ); ?>"><button class="button" name="catalog_op" value="preview_undo">Preview restoring previous value</button>
<?php else : ?><button class="button" name="catalog_op" value="undo">Undo this saved change</button><?php endif; ?>
</form>
<?php else : ?><p>The value or permission changed elsewhere. Undo is unavailable; current values are kept.</p><?php endif; ?>
</section>
<?php endif; ?>
<?php if ( $f['help'] ) : ?><p><?php echo esc_html( $f['help'] ); ?></p><?php endif; ?>
<?php if ( 'sorting' === $id && 0 === strpos( $key, 'wcj_sorting_default_sorting_' ) && 'wcj_sorting_default_sorting_enabled' !== $key ) : ?><p><strong>Stored value and active behavior are different.</strong> These native child values can be saved, but default sorting renaming/removal only runs when its paid-entitled section and module are enabled. Saving a child here does not enable that section or grant Free the paid behavior.</p><?php endif; ?>
<p><?php echo self::guide( $id, $f['anchor'] ?? 'booster-v10-setup', 'Instructions for ' . $f['label'] ); ?></p>
<?php if ( $reason ) : ?>
<p role="note"><strong><?php echo esc_html( $reason ); ?></strong></p>
<a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( add_query_arg( 'active_tab', $f['tab'] ?? '', static::native_url() ) ); ?>">Open this native settings group (new tab)</a>
<?php else : ?>
<form method="post" action="<?php echo esc_url( static::url( $key ) ); ?>" id="wcj-catalog-edit" data-preview-value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>" data-preview-sample="<?php echo esc_attr( (string) $sample ); ?>">
<fieldset <?php echo $error ? 'disabled' : ''; ?>>
<?php self::form_state( $key, $states ); ?>
<?php if ( $default_choice_label ) : ?><p><?php echo esc_html( 'No value is stored yet. The chooser starts with ' . $default_choice_label . '; nothing changes until you save.' ); ?></p><?php endif; ?>
<label for="wcj-catalog-value"><?php echo esc_html( $f['label'] ); ?></label>
<?php if ( 'checkbox' === $f['type'] ) : ?>
<select id="wcj-catalog-value" name="catalog_value"><option value="no" <?php selected( $value, 'no' ); ?>>No</option><option value="yes" <?php selected( $value, 'yes' ); ?>>Yes</option></select>
<?php elseif ( in_array( $f['type'], array( 'select', 'multiselect' ), true ) ) : ?>
<select id="wcj-catalog-value" name="catalog_value<?php echo 'multiselect' === $f['type'] ? '[]' : ''; ?>" <?php echo 'multiselect' === $f['type'] ? 'multiple size="6"' : ''; ?>>
<?php foreach ( self::choices( $f ) as $choice => $label ) : ?>
<option value="<?php echo esc_attr( $choice ); ?>" <?php selected( 'multiselect' === $f['type'] ? in_array( (string) $choice, array_map( 'strval', (array) $value ), true ) : (string) $value === (string) $choice ); ?>><?php echo esc_html( $label ); ?></option>
<?php endforeach; ?>
</select>
<?php if ( 'multiselect' === $f['type'] ) : ?><p>This chooser is limited to 256 items. An existing choice outside it is kept and remains in full settings. Use Ctrl (Windows) or Command (Mac) to choose several items. Unselected means an empty native selection; it does not delete products.</p><?php endif; ?>
<?php elseif ( 'textarea' === $f['type'] ) : ?>
<textarea id="wcj-catalog-value" name="catalog_value" rows="6" maxlength="5000"><?php echo esc_textarea( is_scalar( $value ) ? $value : self::display( $value ) ); ?></textarea>
<?php else : ?>
<input id="wcj-catalog-value" name="catalog_value" type="<?php echo 'number' === $f['type'] ? 'number' : 'text'; ?>" value="<?php echo esc_attr( is_scalar( $value ) ? $value : self::display( $value ) ); ?>" maxlength="5000" <?php foreach ( array( 'min', 'max', 'step' ) as $attr ) { if ( null !== $f[ $attr ] ) { echo esc_attr( $attr ) . '="' . esc_attr( $f[ $attr ] ) . '" '; } } ?>>
<?php endif; ?>
<?php if ( 'wcj_sorting_rearrange' === $key ) : ?><p>Keyboard-accessible ordering: one native sorting key per line, in the desired order. Edit the lines with normal text-selection keys; no dragging required. Unknown plugin-added keys stay in full settings.</p><?php endif; ?>
<?php if ( 'wcj_products_per_page_select_options' === $key ) : ?><p>This changes selector choices, not each shopper's already saved preference. All products can be slow on a large catalogue.</p><?php endif; ?>
<?php if ( $can_preview ) : ?>
<?php if ( ! in_array( $id, array( 'products_per_page', 'sorting' ), true ) ) : ?>
<label for="wcj-catalog-sample">Choose a real sample <?php echo 'cross_sells' === $id ? 'cart product (one unit)' : 'product'; ?></label>
<select id="wcj-catalog-sample" name="sample_product"><option value="">Choose a product</option>
<?php foreach ( $samples as $p ) : if ( ! current_user_can( 'read_post', $p->get_id() ) ) { continue; } ?>
<option value="<?php echo esc_attr( $p->get_id() ); ?>" <?php selected( $sample, $p->get_id() ); ?>><?php echo esc_html( $p->get_name() . ' (#' . $p->get_id() . ', ' . $p->get_type() . ')' ); ?></option>
<?php endforeach; ?></select><p>Up to 100 published products. No product is added to a cart by previewing.</p>
<?php endif; ?>
<p><button type="submit" class="button" name="catalog_op" value="preview" formnovalidate>Preview without saving</button></p>
<?php endif; ?>
<?php if ( self::consequential( $key ) ) : ?>
<p><strong>This changes shopper behavior.</strong> Preview the exact choice before saving. Native full settings remain available without this optional guided flow.</p>
<?php if ( isset( $result['token'] ) ) : ?>
<input type="hidden" name="preview_token" value="<?php echo esc_attr( $result['token'] ); ?>">
<p><label><input type="checkbox" id="wcj-catalog-confirm" name="confirm_consequence" value="yes" required aria-describedby="wcj-catalog-confirm-hint"> I have read this real sample and want this separate behavior change.</label></p>
<p id="wcj-catalog-confirm-hint" class="wcj-catalog-confirm-hint" role="status">Read the sample and check the box to confirm.</p>
<button type="submit" id="wcj-catalog-confirm-save" class="button button-primary" aria-describedby="wcj-catalog-confirm-hint" name="catalog_op" value="<?php echo ! empty( $result['undo_preview'] ) ? 'undo' : 'save'; ?>"><?php echo ! empty( $result['undo_preview'] ) ? 'Confirm restoring previous value' : 'Confirm and save this choice'; ?></button>
<?php endif; ?>
<?php else : ?>
<p><button type="submit" class="button button-primary" name="catalog_op" value="save">Save this setting</button></p>
<?php endif; ?>
<button type="button" class="button" id="wcj-catalog-discard" hidden>Discard this unsaved edit</button>
</fieldset></form>
<?php endif; ?>

</section>
<aside class="wcj-catalog-card">
<h2>Next check</h2><p><?php echo esc_html( $next_checks[ $id ] ?? 'Open the matching shopper page in a fresh session and check the changed setting. Do not place an order.' ); ?></p>
<details><summary>How this works</summary><p>Classic hooks, theme replacements and Blocks can show different output. This task does not replace templates or support every placement. Check the actual shopper page in a fresh session; saving a setting does not verify that output.</p></details>
<?php if ( 'related_products' === $id ) : ?><p>Preview resolves the <strong>currently saved, active native rules</strong>. Unsaved count or column changes are not applied to that list. When the module is off, native WooCommerce rules remain active. Random ordering and caches can differ on the storefront.</p><?php endif; ?>
<?php if ( 'product_listings' === $id ) : ?><p>Category visibility and price-range rules affect listing membership, not the product's stored price. The unchanged full editor may migrate old scalar category lists on opening; this guided page does not run that migration.</p><?php endif; ?>
<?php if ( 'sorting' === $id ) : ?><p>Restoring sorting is the native <strong>Avada-only placement</strong> control. It does not restore all settings, change every theme, or reset products.</p><?php endif; ?>
<h3>Native specialist groups</h3><ul><?php foreach ( self::groups( $id ) as $anchor => $label ) : ?><li><?php echo self::guide( $id, $anchor, $label ); ?></li><?php endforeach; ?></ul>
<p><a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( static::native_url() ); ?>">Full settings for this module (new tab)</a></p>
<?php WCJ_Task_Finder::help( $id, true ); ?>
</aside></div>
<?php if ( ! empty( $result['preview'] ) ) : $preview = $result['preview']; ?>
<section class="wcj-catalog-card" id="wcj-catalog-preview" aria-labelledby="wcj-catalog-preview-heading">
<h2 id="wcj-catalog-preview-heading" tabindex="-1">Read-only sample, not a saved change</h2>
<p><?php echo esc_html( $preview['note'] ); ?></p>
<?php if ( ! empty( $preview['sample'] ) ) : ?><h3><?php echo esc_html( $preview['sample']['name'] . ' (#' . $preview['sample']['id'] . ')' ); ?></h3><a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $preview['product_url'] ); ?>">View this real product (new tab)</a><?php endif; ?>
<?php foreach ( array( 'sample_cart', 'ajax_dependency', 'proposed', 'saved_replacement_policy', 'placement_status', 'preview_bound' ) as $part ) : if ( ! empty( $preview[ $part ] ) ) : ?><p><strong><?php echo esc_html( $preview[ $part ] ); ?></strong></p><?php endif; endforeach; ?>
<?php if ( isset( $preview['selector'] ) ) : ?><p>Selector choices (illustration only; choosing here does not persist a shopper preference):</p><select aria-label="Preview products per page"><?php foreach ( preg_split( '/\r?\n/', $preview['selector'] ) as $line ) { $parts = explode( '|', trim( $line ), 2 ); if ( 2 === count( $parts ) ) { echo '<option>' . esc_html( $parts[0] . ' (' . $parts[1] . ')' ) . '</option>'; } } ?></select><p>Saved shopper storage method: <?php echo esc_html( $preview['persistence'] ); ?>.</p><?php endif; ?>
<?php if ( isset( $preview['current_choices'] ) ) : ?><h3>Current Classic sorting choices</h3><ol><?php foreach ( $preview['current_choices'] as $choice => $label ) { echo '<li>' . esc_html( $label . ' [' . $choice . ']' ) . '</li>'; } ?></ol><?php endif; ?>
<?php if ( isset( $preview['image_id'] ) ) : ?><h3>Current product image</h3><?php echo wp_kses_post( wp_get_attachment_image( $preview['image_id'], 'thumbnail' ) ); ?><p>Replacement HTML remains escaped in the readback below. Verify its actual theme output on the product page.</p><?php endif; ?>
<?php if ( ! empty( $preview['items'] ) ) : ?><h3><?php echo ! empty( $preview['placement_hidden'] ) ? 'Native candidates (placement hidden)' : 'Resolved native product candidates'; ?></h3><ul><?php foreach ( $preview['items'] as $item ) { echo '<li>' . esc_html( $item['name'] . ' (#' . $item['id'] . ', ' . $item['type'] . ', ' . ( $item['in_stock'] ? 'in stock' : 'out of stock' ) . ')' ) . '</li>'; } ?></ul><?php elseif ( in_array( $id, array( 'cross_sells', 'upsells', 'related_products', 'frequently_bought_together' ), true ) ) : ?><p>No product candidates were resolved for this sample. This is not permission to delete an override or invent suggestions.</p><?php endif; ?>
<?php if ( isset( $preview['attributes'] ) ) : ?><h3>Real variable product attributes</h3><ul><?php foreach ( $preview['attributes'] as $taxonomy => $attribute ) { echo '<li>' . esc_html( $taxonomy . ': ' . $attribute['type'] ) . '<ul>'; foreach ( $attribute['terms'] as $term ) { echo '<li>' . esc_html( $term['value'] . ' | color: ' . ( $term['color'] ?: 'not configured' ) . ' | image ID: ' . ( $term['image'] ?: 'not configured' ) ) . '</li>'; } echo '</ul></li>'; } ?></ul><?php endif; ?>
<p><a href="#wcj-catalog-edit">Return to this setting and its confirmation</a></p><details><summary>Exact sample readback and limitations</summary><pre class="wcj-catalog-value"><?php echo esc_html( wp_json_encode( $preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre></details>
</section>
<?php endif; ?>
<section class="wcj-catalog-card"><h2>Settings elsewhere</h2><?php self::elsewhere(); ?>
<details><summary>All native members and specialist routes</summary><ul><?php foreach ( $fields as $member => $row ) : ?><li><strong><?php echo esc_html( $row['label'] ); ?></strong> &middot; <?php echo esc_html( 'expert' === $row['mode'] ? 'Full settings' : ( self::editable( $row ) ? 'Guided and native editor' : 'Locked in this edition' ) ); ?> &middot; <a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( add_query_arg( 'active_tab', $row['tab'] ?? '', static::native_url() ) ); ?>">Native group</a> &middot; <?php echo self::guide( $id, $row['anchor'] ?? 'booster-v10-setup', 'Specific instructions' ); ?><br><code><?php echo esc_html( $member ); ?></code></li><?php endforeach; ?></ul></details>
</section>
<?php endif; ?>
</div>

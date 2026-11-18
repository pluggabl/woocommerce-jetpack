<?php
/** @package Booster_For_WooCommerce/admin */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wcj-product-date" id="wcj-product-date">
<h1><?php esc_html_e( 'Make one product available from a date', 'woocommerce-jetpack' ); ?></h1>
<p><?php esc_html_e( 'Set one product override and its direct date. This never turns on the module or its global per-product gate, changes the action, or erases monthly schedules.', 'woocommerce-jetpack' ); ?></p>
<p><a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( self::native_url() ); ?>"><?php esc_html_e( 'Full Date settings (new tab)', 'woocommerce-jetpack' ); ?></a> <a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-product-availability-by-date/#date-start"><?php esc_html_e( 'Available-from guide (new tab)', 'woocommerce-jetpack' ); ?></a></p>
<details><summary><?php esc_html_e( 'Using full editors without losing a draft', 'woocommerce-jetpack' ); ?></summary><p><?php esc_html_e( 'Full editors stay separate so unsaved edits remain in their original tab. Save there first, then load current values here. Native edits are not covered by this task\'s Undo.', 'woocommerce-jetpack' ); ?></p></details>
<noscript><p><?php esc_html_e( 'This task requires JavaScript to load authoritative product state safely. Use full settings instead; nothing was changed.', 'woocommerce-jetpack' ); ?></p></noscript>
<p id="wcj-pd-status" role="status" aria-live="polite"><?php esc_html_e( 'Choose a product to load its current values. No edit controls are active yet.', 'woocommerce-jetpack' ); ?></p>
<form id="wcj-pd-search-form">
	<label for="wcj-pd-search"><?php esc_html_e( 'Find a product you can edit', 'woocommerce-jetpack' ); ?></label>
	<input id="wcj-pd-search" type="search" maxlength="100" disabled>
	<button id="wcj-pd-search-button" type="submit" class="button" disabled><?php esc_html_e( 'Search products', 'woocommerce-jetpack' ); ?></button>
</form>
<p id="wcj-pd-search-note"><?php esc_html_e( 'Up to 25 matching products are shown. Refine your search if needed; the loaded product is retained.', 'woocommerce-jetpack' ); ?></p>
<label for="wcj-pd-products"><?php esc_html_e( 'Product', 'woocommerce-jetpack' ); ?></label>
<select id="wcj-pd-products" disabled><option value=""><?php esc_html_e( 'Search, then choose a product', 'woocommerce-jetpack' ); ?></option></select>
<button id="wcj-pd-load" type="button" class="button" disabled><?php esc_html_e( 'Load selected product', 'woocommerce-jetpack' ); ?></button>
<section id="wcj-pd-editor" hidden aria-labelledby="wcj-pd-title">
	<h2 id="wcj-pd-title" tabindex="-1"></h2>
	<h3><?php esc_html_e( 'Current values', 'woocommerce-jetpack' ); ?></h3>
	<p id="wcj-pd-current"></p><p id="wcj-pd-inactive" hidden></p><p id="wcj-pd-gates"></p>
	<details><summary><?php esc_html_e( 'Rule behavior and limits', 'woocommerce-jetpack' ); ?></summary><p id="wcj-pd-boundary"></p>
	<p id="wcj-pd-scope"><?php esc_html_e( 'Available from the configured date, not only that date. A calendar-only date starts at midnight in the store timezone. Native custom formats with explicit time remain in full settings. Other purchase restrictions still apply.', 'woocommerce-jetpack' ); ?></p></details>
	<p><a id="wcj-pd-native" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Full product editor (new tab)', 'woocommerce-jetpack' ); ?></a> | <a id="wcj-pd-shopper" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View product (new tab)', 'woocommerce-jetpack' ); ?></a></p>
	<p id="wcj-pd-lock" role="status"></p>
	<h3><?php esc_html_e( 'Your draft', 'woocommerce-jetpack' ); ?></h3>
	<fieldset id="wcj-pd-fields" disabled inert>
	<p><label for="wcj-pd-enabled"><?php esc_html_e( 'Use this product\'s Date override', 'woocommerce-jetpack' ); ?></label> <select id="wcj-pd-enabled"><option value="no"><?php esc_html_e( 'Disabled', 'woocommerce-jetpack' ); ?></option><option value="yes"><?php esc_html_e( 'Enabled', 'woocommerce-jetpack' ); ?></option></select></p>
	<p><label for="wcj-pd-date"><?php esc_html_e( 'Available from date', 'woocommerce-jetpack' ); ?></label> <input id="wcj-pd-date" type="text" maxlength="32" autocomplete="off" aria-describedby="wcj-pd-format wcj-pd-empty"></p>
	<p id="wcj-pd-format"></p><p id="wcj-pd-empty"><?php esc_html_e( 'Empty date uses retained product monthly rules while this override is enabled. Disabling the override returns to global monthly rules if enabled. Neither choice means unrestricted purchasing.', 'woocommerce-jetpack' ); ?></p>
	<button id="wcj-pd-save" type="button" class="button button-primary" disabled><?php esc_html_e( 'Save', 'woocommerce-jetpack' ); ?></button>
	</fieldset>
	<p id="wcj-pd-draft-note" role="status"></p>
	<div id="wcj-pd-copy-draft" hidden><p><label for="wcj-pd-retained-draft"><?php esc_html_e( 'Retained draft to copy before discarding', 'woocommerce-jetpack' ); ?></label></p><textarea id="wcj-pd-retained-draft" class="large-text" rows="2" readonly></textarea></div>
	<section id="wcj-pd-review" hidden aria-labelledby="wcj-pd-review-title">
	<h3 id="wcj-pd-review-title" tabindex="-1"><?php esc_html_e( 'Review purchase consequence', 'woocommerce-jetpack' ); ?></h3>
	<p id="wcj-pd-review-text"></p><ul id="wcj-pd-review-changes"></ul>
	<p><?php esc_html_e( 'Only this product\'s enable and direct date will change. The global gates, action, monthly schedules, other products, stock, orders and payments stay unchanged. The review expires after five minutes or any draft change.', 'woocommerce-jetpack' ); ?></p>
	<label><input id="wcj-pd-confirm" type="checkbox" required disabled> <?php esc_html_e( 'I understand the purchase consequence for this product.', 'woocommerce-jetpack' ); ?></label>
	<p><button id="wcj-pd-confirm-save" type="button" class="button button-primary" disabled><?php esc_html_e( 'Confirm and save', 'woocommerce-jetpack' ); ?></button> <button id="wcj-pd-cancel-review" type="button" class="button"><?php esc_html_e( 'Cancel review', 'woocommerce-jetpack' ); ?></button></p>
	</section>
	<p><button id="wcj-pd-reload" type="button" class="button" aria-describedby="wcj-pd-reload-help"><?php esc_html_e( 'Load current values', 'woocommerce-jetpack' ); ?></button> <button id="wcj-pd-discard" type="button" class="button" disabled><?php esc_html_e( 'Discard this draft', 'woocommerce-jetpack' ); ?></button></p>
	<p id="wcj-pd-reload-help"><?php esc_html_e( 'Load current values refreshes saved product and Date settings, including changes made in another tab. It does not save your draft.', 'woocommerce-jetpack' ); ?></p>
	<h3><?php esc_html_e( 'Saved record', 'woocommerce-jetpack' ); ?></h3><p id="wcj-pd-record"></p><p id="wcj-pd-record-inactive" hidden></p><ul id="wcj-pd-record-changes" aria-label="<?php esc_attr_e( 'What changed', 'woocommerce-jetpack' ); ?>"></ul>
	<button id="wcj-pd-undo" type="button" class="button" disabled><?php esc_html_e( 'Undo this saved change', 'woocommerce-jetpack' ); ?></button>
	<p><?php esc_html_e( 'Undo is available only for your matching last saved change. It restores the exact previous values, including missing metadata, not unrelated settings or third-party state. Discard a draft before Undo.', 'woocommerce-jetpack' ); ?></p>
	<h3><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h3><p><?php esc_html_e( 'View the selected product as the intended shopper with the actual gates and action. Check its unavailable message and purchase control where applicable, without placing an order. A saved configuration is not proof of shopper eligibility.', 'woocommerce-jetpack' ); ?></p>
	<details><summary><?php esc_html_e( 'Technical details and retained monthly values', 'woocommerce-jetpack' ); ?></summary><pre id="wcj-pd-raw"></pre><p><?php esc_html_e( 'null means no stored row. Monthly values are read here, never written by this task.', 'woocommerce-jetpack' ); ?></p></details>
</section>
<?php
WCJ_Task_Finder::help( 'product_by_date', true );
WCJ_Product_Availability_Groups::render();
?>
</div>

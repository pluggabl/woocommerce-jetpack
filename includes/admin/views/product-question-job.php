<?php
/**
 * Product question canvas.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wcj-product-question" id="wcj-product-question">
<h1><?php esc_html_e( 'Product question', 'woocommerce-jetpack' ); ?></h1>
<p><?php esc_html_e( 'Ask for a message on one product, with no extra charge. Shoppers answer on the product page; the native field carries the answer with that cart item and order line.', 'woocommerce-jetpack' ); ?></p>
<nav aria-label="<?php esc_attr_e( 'Choose the right task', 'woocommerce-jetpack' ); ?>">
<?php
foreach ( array(
	'checkout_custom_fields' => array( 'wcj-checkout-job', __( 'Ask once at checkout: one answer per order, no charge', 'woocommerce-jetpack' ) ),
	'product_addons'         => array( 'wcj-product-extra-job', __( 'Charge for an extra: changes what customers pay', 'woocommerce-jetpack' ) ),
	'product_tabs'           => array( 'wcj-product-tab-job', __( 'Show information on product pages: does not collect answers', 'woocommerce-jetpack' ) ),
) as $module => $route ) :
	if ( ! WCJ_Task_Finder::allowed( $module ) ) {
		continue; }
	?>
<p><a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $route[0] ) ); ?>"><?php echo esc_html( $route[1] ); ?></a> | <?php echo esc_html( wcj_is_module_enabled( $module ) ? __( 'Module on', 'woocommerce-jetpack' ) : __( 'Module off', 'woocommerce-jetpack' ) ); ?></p>
<?php endforeach; ?>
</nav>
<form id="question-search"><label for="question-query"><?php esc_html_e( 'Find a product', 'woocommerce-jetpack' ); ?></label> <input id="question-query" type="search" maxlength="100"> <button class="button" type="submit"><?php esc_html_e( 'Search', 'woocommerce-jetpack' ); ?></button><label for="question-product"><?php esc_html_e( 'Choose a product', 'woocommerce-jetpack' ); ?></label><select id="question-product"><option value=""><?php esc_html_e( 'Choose a product', 'woocommerce-jetpack' ); ?></option></select></form>
<p id="question-message" role="status" aria-live="polite"></p>
<section id="question-card" hidden>
<h2 id="question-product-title"></h2><p id="question-state"></p>
<p id="question-reason" role="status"></p>
<details id="question-existing"><summary><?php esc_html_e( 'Existing fields and module-wide settings', 'woocommerce-jetpack' ); ?></summary><ul id="question-fields"></ul><p id="question-display"></p><p><?php esc_html_e( 'Existing fields are not adopted or reordered. Display hooks, templates, cart/order placement and email settings apply to all product questions and stay in full settings.', 'woocommerce-jetpack' ); ?></p></details>
<form id="question-form">
<div class="question-columns"><div>
<p><label for="question-title"><?php esc_html_e( 'Question', 'woocommerce-jetpack' ); ?></label><input id="question-title" type="text" maxlength="200" required></p>
<p><label for="question-type"><?php esc_html_e( 'Answer', 'woocommerce-jetpack' ); ?></label><select id="question-type"><option value="text"><?php esc_html_e( 'Short text', 'woocommerce-jetpack' ); ?></option><option value="textarea"><?php esc_html_e( 'Long text', 'woocommerce-jetpack' ); ?></option></select></p>
<p><label><input id="question-required" type="checkbox"> <?php esc_html_e( 'Customers must answer', 'woocommerce-jetpack' ); ?></label></p>
<p><label for="question-hint"><?php esc_html_e( 'Hint (optional)', 'woocommerce-jetpack' ); ?></label><input id="question-hint" type="text" maxlength="200"></p>
<p><label for="question-maxlength"><?php esc_html_e( 'Browser character limit (0 means no limit)', 'woocommerce-jetpack' ); ?></label><input id="question-maxlength" type="number" min="0" max="10000" value="0"></p>
</div><aside><h3><?php esc_html_e( 'Illustrative sample', 'woocommerce-jetpack' ); ?></h3><p><?php esc_html_e( 'Not your theme or shopper-output proof. Typing here does not save a customer answer.', 'woocommerce-jetpack' ); ?></p><label id="question-sample-label" for="question-sample"></label><div id="question-sample-host"><input id="question-sample" type="text"></div></aside></div>
<fieldset id="question-activation"><legend><?php esc_html_e( 'Activation affects more than this product', 'woocommerce-jetpack' ); ?></legend>
<label id="question-module-row"><input id="question-module" type="checkbox"> <?php esc_html_e( 'Turn on Product Input Fields', 'woocommerce-jetpack' ); ?></label>
<p id="question-module-reach"><?php esc_html_e( 'Turning on the module can also show existing local questions on other products and enabled global questions on every matching product. This task does not count them as active.', 'woocommerce-jetpack' ); ?></p>
<label id="question-local-row"><input id="question-local" type="checkbox"> <?php esc_html_e( 'Turn on per-product questions', 'woocommerce-jetpack' ); ?></label>
<p id="question-local-reach"><?php esc_html_e( 'Turning on per-product questions can also show existing questions on other products when the module is on.', 'woocommerce-jetpack' ); ?></p>
<label id="question-ack-row" hidden><input id="question-ack" type="checkbox"> <?php esc_html_e( 'I checked the wider reach of these activation changes.', 'woocommerce-jetpack' ); ?></label>
</fieldset>
<p id="question-draft" hidden><?php esc_html_e( 'Unsaved changes', 'woocommerce-jetpack' ); ?></p><p id="question-save-reason" role="status" aria-live="polite" hidden></p><p class="question-actions"><button class="button button-primary" id="question-save" aria-describedby="question-save-reason" type="submit" disabled><?php esc_html_e( 'Save question', 'woocommerce-jetpack' ); ?></button> <button class="button" id="question-discard" type="button" hidden><?php esc_html_e( 'Discard draft', 'woocommerce-jetpack' ); ?></button></p>
</form>
<div id="question-receipt" hidden><p><?php esc_html_e( 'Saved settings, not proof of shopper output.', 'woocommerce-jetpack' ); ?> <button class="button" id="question-undo" type="button"><?php esc_html_e( 'Undo this change', 'woocommerce-jetpack' ); ?></button></p><details><summary><?php esc_html_e( 'What changed', 'woocommerce-jetpack' ); ?></summary><ul id="question-changes"></ul></details></div>
<p><button class="button" id="question-reload" type="button"><?php esc_html_e( 'Reload current (keep draft)', 'woocommerce-jetpack' ); ?></button></p>
<h3><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h3><p id="question-next"></p>
<p><a id="question-shopper" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open product (new tab)', 'woocommerce-jetpack' ); ?></a> | <a id="question-native" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Full product editor (new tab)', 'woocommerce-jetpack' ); ?></a></p><p><?php esc_html_e( 'Native product pages and the Booster product panel may convert legacy field storage. Your draft stays here; native edits are not covered by this task\'s Undo.', 'woocommerce-jetpack' ); ?></p>
</section>
<p><a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-product-input-fields/#product-question"><?php esc_html_e( 'Product question setup guide (new tab)', 'woocommerce-jetpack' ); ?></a></p>
<?php WCJ_Product_Question_Groups::render(); ?>
</div>

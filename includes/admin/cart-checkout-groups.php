<?php
/** Complete cart/checkout purpose groups. Native fields and persistence are unchanged.
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
final class WCJ_Cart_Checkout_Groups {
	/** Stable native group to substantive guide mapping. */
	public static function groups() {
		return array(
			array( 'address_formats', 'country-format', 'address_address_formats_address_formats_by_country_tab', __( 'Country/default postal formats', 'woocommerce-jetpack' ), __( 'Country-specific placeholders and line breaks change formatted address output, not checkout collection.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-address-formats/#booster-v10-s14-country-format' ),
			array( 'address_formats', 'country-display', 'address_address_formats_force_base_country_display_tab', __( 'Base country display', 'woocommerce-jetpack' ), __( 'Force country display independently of address fields or buyer eligibility.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-address-formats/#booster-v10-s14-country-display' ),
			array( 'cart', 'item-info', 'wcj_cart_info_cart_items_table_tab', __( 'Information beside cart item names', 'woocommerce-jetpack' ), __( 'HTML/shortcode output per cart line; not a persisted shopper answer.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-custom-info/#booster-v10-s14-item-info' ),
			array( 'cart', 'cart-blocks', 'wcj_cart_info_info_blocks_tab', __( 'Cart information blocks', 'woocommerce-jetpack' ), __( 'Count and repeated content/hook/priority; retain flexible native placement and explain Classic/theme hook reach.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-custom-info/#booster-v10-s14-cart-blocks' ),
			array( 'cart_customization', 'cart-coupon', 'wcj_cart_customization_general_options_tab', __( 'Coupon field visibility', 'woocommerce-jetpack' ), __( 'Cart coupon display only; not coupon-policy or checkout-validation enforcement.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-customization/#booster-v10-s14-cart-coupon' ),
			array( 'cart_customization', 'cart-remove-link', 'wcj_cart_customization_general_options_tab', __( 'Cart item remove link', 'woocommerce-jetpack' ), __( 'Presentation of line-removal link, not access control or protection of answers.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-customization/#booster-v10-s14-cart-remove-link' ),
			array( 'cart_customization', 'empty-return', 'wcj_cart_customization_general_options_tab', __( 'Empty-cart return action', 'woocommerce-jetpack' ), __( 'Separate text method/content from enabled redirect URL; show native route and current destination.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-customization/#booster-v10-s14-empty-return' ),
			array( 'checkout_core_fields', 'core-global', 'wcj_cart_core_fields_general_options_tab', __( 'Address/locale overrides and rule relation', 'woocommerce-jetpack' ), __( 'Global precedence and sorting can affect multiple fields; preserve defaults and country behavior.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-core-global' ),
			array( 'checkout_core_fields', 'core-presence', 'wcj_cart_core_fields_fields_options_tab', __( 'Core field presence', 'woocommerce-jetpack' ), __( 'Removing a collection field is consequential, not a label change; native Woo/country requirements remain.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-core-presence' ),
			array( 'checkout_core_fields', 'core-required', 'wcj_cart_core_fields_fields_options_tab', __( 'Core field requiredness', 'woocommerce-jetpack' ), __( 'Validation requirement distinct from label/placeholder; retain country and conditional behavior.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-core-required' ),
			array( 'checkout_core_fields', 'core-conditions', array( 'wcj_cart_core_fields_fields_options_tab' => __( 'Full settings: field conditions (new tab)', 'woocommerce-jetpack' ), 'wcj_cart_core_fields_general_options_tab' => __( 'Full settings: condition relation (new tab)', 'woocommerce-jetpack' ) ), __( 'Core field cart/category conditions', 'woocommerce-jetpack' ), __( 'Include/exclude sets and global AND/OR relation control whether a field is collected for the current cart.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-core-conditions' ),
			array( 'checkout_core_fields', 'core-layout', array( 'wcj_cart_core_fields_fields_options_tab' => __( 'Full settings: field layout (new tab)', 'woocommerce-jetpack' ), 'wcj_cart_core_fields_general_options_tab' => __( 'Full settings: priority sorting (new tab)', 'woocommerce-jetpack' ) ), __( 'Core field layout and order', 'woocommerce-jetpack' ), __( 'Width/class and priority affect layout/order, not answer identity; native sort caveat.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-core-layout' ),
			array( 'checkout_core_fields', 'core-wording', 'wcj_cart_core_fields_fields_options_tab', __( 'Core field question and hints', 'woocommerce-jetpack' ), __( 'Label/placeholder/description for existing native IDs; useful bounded optional wording job, no requiredness/activation change.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-core-wording' ),
			array( 'checkout_custom_fields', 'answer-display', 'wcj_checkout_custom_fields_general_options_tab', __( 'Where existing answers are displayed', 'woocommerce-jetpack' ), __( 'Shared switches/templates affect all questions; email and order/thank-you output are separate from collection.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-answer-display' ),
			array( 'checkout_custom_fields', 'answer-cleaning', 'wcj_checkout_custom_fields_general_options_tab', __( 'Multiline answer handling', 'woocommerce-jetpack' ), __( 'Cleaning and line-break conversion are not purely label settings; preserve existing answers.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-answer-cleaning' ),
			array( 'checkout_custom_fields', 'question-datetime', 'wcj_checkout_custom_fields_custom_fields_tab', __( 'Date/week/time input options', 'woocommerce-jetpack' ), __( 'Native specialized types, format/range/block rules, browser behavior and server guarantees separately qualified.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-question-datetime' ),
			array( 'checkout_custom_fields', 'question-choices', 'wcj_checkout_custom_fields_custom_fields_tab', __( 'Choice values and checkbox semantics', 'woocommerce-jetpack' ), __( 'Keep saved option keys and checkbox display values stable; defaults are not prior answers.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-question-choices' ),
			array( 'checkout_custom_fields', 'question-conditions', 'wcj_checkout_custom_fields_custom_fields_tab', __( 'Conditional question collection', 'woocommerce-jetpack' ), __( 'Product/category/cart-amount eligibility and supported Blocks version; hidden required fields need focused checks.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-question-conditions' ),
			array( 'checkout_custom_fields', 'question-prefill', 'wcj_checkout_custom_fields_custom_fields_tab', __( 'Prefill and deliberate clear-data behavior', 'woocommerce-jetpack' ), __( 'Customer-meta/default retrieval versus intentionally clearing input on checkout retrieval; do not promise answer retention when enabled.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-question-prefill' ),
			array( 'checkout_custom_fields', 'question-layout', 'wcj_checkout_custom_fields_custom_fields_tab', __( 'Question layout', 'woocommerce-jetpack' ), __( 'Width, priority and float-clear are distinct from Clear Data and field identity.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-question-layout' ),
			array( 'checkout_custom_fields', 'question-copy', 'wcj_checkout_custom_fields_custom_fields_tab', __( 'Question wording and hints', 'woocommerce-jetpack' ), __( 'Checkout question supports a safe subset. Native HTML/custom values retain exact expert route; do not rewrite arbitrary content.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-question-copy' ),
			array( 'checkout_custom_fields', 'question-identity', 'wcj_checkout_custom_fields_custom_fields_tab', __( 'Question slots, type, section and requiredness', 'woocommerce-jetpack' ), __( 'Use Checkout question for supported plain questions. Slot index plus section is storage identity; total count, enabled/required/type and section changes require explicit preservation reasoning.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-question-identity' ),
			array( 'checkout_custom_info', 'checkout-blocks', 'checkout_custom_info_general_options_tab', __( 'Checkout information blocks', 'woocommerce-jetpack' ), __( 'Count/content/hook/priority are Classic/theme content injection, not stored answer collection.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-info/#booster-v10-s14-checkout-blocks' ),
			array( 'checkout_customization', 'checkout-country', 'wcj_checkout_customization_restrict_countries_tab', __( 'Country restrictions and exceptions', 'woocommerce-jetpack' ), __( 'IP versus saved billing country, admin/YITH exceptions and conditions; no identity/security guarantee.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-customization/#booster-v10-s14-checkout-country' ),
			array( 'checkout_customization', 'account-default', 'wcj_checkout_customization_checkbox_options_tab', __( 'Create-account default', 'woocommerce-jetpack' ), __( 'Default checkbox choice is not forced registration or consent.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-customization/#booster-v10-s14-account-default' ),
			array( 'checkout_customization', 'order-again', 'wcj_checkout_customization_button_options_tab', __( 'Order Again visibility', 'woocommerce-jetpack' ), __( 'Hide existing order action without claiming removal of order data.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-customization/#booster-v10-s14-order-again' ),
			array( 'checkout_customization', 'logged-fields', 'wcj_checkout_customization_disable_fields_tab', __( 'Logged-in readonly/disabled fields', 'woocommerce-jetpack' ), __( 'Readonly and disabled controls differ in submission. Exact customer state and arbitrary field IDs need safe testing.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-customization/#booster-v10-s14-logged-fields' ),
			array( 'checkout_customization', 'checkout-refresh', array( 'wcj_checkout_customization_recalculate_checkout_tab' => __( 'Full settings: recalculate checkout (new tab)', 'woocommerce-jetpack' ), 'wcj_checkout_customization_force_checkout_tab' => __( 'Full settings: force checkout update (new tab)', 'woocommerce-jetpack' ) ), __( 'Recalculation/update triggers', 'woocommerce-jetpack' ), __( 'Selector-driven requests may replace fragments; verify retained typed answers and no duplicate side effects.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-customization/#booster-v10-s14-checkout-refresh' ),
			array( 'checkout_customization', 'checkout-messages', array( 'wcj_checkout_customization_order_message_options_tab' => __( 'Full settings: order received message (new tab)', 'woocommerce-jetpack' ), 'wcj_checkout_customization_returning_message_options_tab' => __( 'Full settings: returning customer message (new tab)', 'woocommerce-jetpack' ) ), __( 'Checkout/login/order-received messages', 'woocommerce-jetpack' ), __( 'Existing display wording and enable switches, not checkout question storage.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-customization/#booster-v10-s14-checkout-messages' ),
			array( 'checkout_files_upload', 'upload-templates', array( 'wcj_checkout_files_upload_form_template_options_tab' => __( 'Full settings: upload form template (new tab)', 'woocommerce-jetpack' ), 'wcj_checkout_files_upload_order_template_options_tab' => __( 'Full settings: order template (new tab)', 'woocommerce-jetpack' ), 'wcj_checkout_files_upload_Email_template_options_tab' => __( 'Full settings: email template (new tab)', 'woocommerce-jetpack' ) ), __( 'Upload form/order/email templates', 'woocommerce-jetpack' ), __( 'Placeholders, images and styles; not proof of permissioned download or attachment delivery.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-templates' ),
			array( 'checkout_files_upload', 'upload-email', 'wcj_checkout_files_upload_emails_options_tab', __( 'File attachments and notifications', 'woocommerce-jetpack' ), __( 'Admin/customer attachments and upload/remove action emails; isolated sink only during QA.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-email' ),
			array( 'checkout_files_upload', 'upload-cleanup', 'wcj_checkout_files_upload_general_options_tab', __( 'Empty-cart file cleanup', 'woocommerce-jetpack' ), __( 'Explain destructive staged-file deletion, notices and separate retained order-file lifecycle.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-cleanup' ),
			array( 'checkout_files_upload', 'upload-safety', 'wcj_checkout_files_upload_advanced_options_tab', __( 'File safety and notice behavior', 'woocommerce-jetpack' ), __( 'Extension blocklist is not antivirus; Classic and private Blocks admission differ.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-safety' ),
			array( 'checkout_files_upload', 'upload-eligibility', 'wcj_checkout_files_upload_options_tab', __( 'File-request visibility conditions', 'woocommerce-jetpack' ), __( 'Products/categories/tags/roles include/exclude sets and checkout/order contexts.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-eligibility' ),
			array( 'checkout_files_upload', 'upload-types', 'wcj_checkout_files_upload_options_tab', __( 'Accepted file types', 'woocommerce-jetpack' ), __( 'Extension/MIME and server-size admission; browser accept hint is not the whole policy.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-types' ),
			array( 'checkout_files_upload', 'upload-wording', 'wcj_checkout_files_upload_options_tab', __( 'File labels and recovery notices', 'woocommerce-jetpack' ), __( 'Upload/remove/required/wrong-type/no-file messages; truthful outcome, preserve errors.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-wording' ),
			array( 'checkout_files_upload', 'upload-placement', 'wcj_checkout_files_upload_options_tab', __( 'Checkout and later order upload placement', 'woocommerce-jetpack' ), __( 'Classic hooks, priority, thank-you/account choices and authorized order ownership.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-placement' ),
			array( 'checkout_files_upload', 'upload-slots', 'wcj_checkout_files_upload_options_tab', __( 'File slots and requiredness', 'woocommerce-jetpack' ), __( 'Count/enabled/required and field identity. Free effective one slot; preserve staged files and pending-order uncertainty.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-upload-slots' ),
			array( 'empty_cart', 'empty-confirm', 'wcj_empty_cart_general_options_tab', __( 'Confirmation before emptying cart', 'woocommerce-jetpack' ), __( 'Destructive cart action, not guided settings Undo; staged upload cleanup may follow.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-empty-cart-button/#booster-v10-s14-empty-confirm' ),
			array( 'empty_cart', 'empty-placement', 'wcj_empty_cart_general_options_tab', __( 'Empty-cart button placement', 'woocommerce-jetpack' ), __( 'Cart and checkout hooks differ; no native Blocks parity implied.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-empty-cart-button/#booster-v10-s14-empty-placement' ),
			array( 'empty_cart', 'empty-presentation', 'wcj_empty_cart_general_options_tab', __( 'Empty-cart wording and styling', 'woocommerce-jetpack' ), __( 'Text/class/style/enable maintain action meaning, not a safer removal mechanism.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-empty-cart-button/#booster-v10-s14-empty-presentation' ),
			array( 'mini_cart', 'mini-blocks', 'wcj_mini_cart_blocks_tab', __( 'Mini-cart information blocks', 'woocommerce-jetpack' ), __( 'Count/content/hook/priority; distinguish Classic widget/theme hooks from native Mini-Cart Block.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-mini-cart-custom-info/#booster-v10-s14-mini-blocks' ),
			array( 'one_page_checkout', 'one-page', 'wcj_one_page_checkout_general_options_tab', __( 'Product selection and one-page checkout', 'woocommerce-jetpack' ), __( 'Plus/Elite only. Global IDs/priority, product checkbox, shortcode/AJAX/session flow; no Free implementation or Blocks parity.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-one-page-checkout/#booster-v10-s14-one-page' ),
			array( 'product_add_to_cart', 'add-redirect', 'wcj_add_to_cart_local_redirect', __( 'After-add redirect', 'woocommerce-jetpack' ), __( 'Global and per-product gates/URLs; preserve local redirect validation and shopper context.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-add-redirect' ),
			array( 'product_add_to_cart', 'add-on-visit', 'wcj_add_to_cart_on_visit', __( 'Automatic add on visit', 'woocommerce-jetpack' ), __( 'Can mutate cart on navigation, not display wording; preserve enabled scope and action visibility.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-add-on-visit' ),
			array( 'product_add_to_cart', 'variation-radio', 'wcj_add_to_cart_variable_product', __( 'Variation radio selection', 'woocommerce-jetpack' ), __( 'Radio display and label/description/style templates; actual variation identity and eligibility remain native.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-variation-radio' ),
			array( 'product_add_to_cart', 'archive-button', 'wcj_add_to_cart_replace_button', __( 'Archive add-to-cart replacement', 'woocommerce-jetpack' ), __( 'Single/variable replacement changes interaction and quantity/selection context.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-archive-button' ),
			array( 'product_add_to_cart', 'quantity', 'wcj_add_to_cart_quantity', __( 'Quantity and sold-individually', 'woocommerce-jetpack' ), __( 'Hiding quantity controls differs from native sold-individually restriction.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-quantity' ),
			array( 'product_add_to_cart', 'product-url', 'wcj_add_to_cart_custom_url', __( 'Per-product archive URL', 'woocommerce-jetpack' ), __( 'Separate archive destination from checkout answers; native per-product gate and postmeta.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-product-url' ),
			array( 'product_add_to_cart', 'product-ajax', 'wcj_add_to_cart_ajax', __( 'Per-product AJAX selection', 'woocommerce-jetpack' ), __( 'Native enable/disable/default choices and module gate, not global compatibility.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-product-ajax' ),
			array( 'product_add_to_cart', 'external-link', 'wcj_add_to_cart_external_products', __( 'External-product windows', 'woocommerce-jetpack' ), __( 'Single/archive target behavior, not product fulfillment/checkout.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-external-link' ),
			array( 'product_add_to_cart', 'add-messages', 'wcj_add_to_cart_message_options', __( 'Add-to-cart notices', 'woocommerce-jetpack' ), __( 'Continue-shopping/View-cart wording and switches; no new action or side effect.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-add-messages' ),
			array( 'product_add_to_cart', 'button-position', 'wcj_add_to_cart_position_options', __( 'Button hook and priority', 'woocommerce-jetpack' ), __( 'Single/archive enabled hook/priority overrides; preserve theme-specific expert mechanism.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-button-position' ),
			array( 'address_formats', 'address_formats-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-address-formats/#booster-v10-s14-address-formats-activation' ),
			array( 'address_formats', 'address_formats-reset', 'address_address_formats_address_formats_by_country_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-address-formats/#booster-v10-s14-address-formats-reset' ),
			array( 'cart', 'cart-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-custom-info/#booster-v10-s14-cart-activation' ),
			array( 'cart', 'cart-reset', 'wcj_cart_info_cart_items_table_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-custom-info/#booster-v10-s14-cart-reset' ),
			array( 'cart_customization', 'cart_customization-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-customization/#booster-v10-s14-cart-customization-activation' ),
			array( 'cart_customization', 'cart_customization-reset', 'wcj_cart_customization_general_options_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-cart-customization/#booster-v10-s14-cart-customization-reset' ),
			array( 'checkout_core_fields', 'checkout_core_fields-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-checkout-core-fields-activation' ),
			array( 'checkout_core_fields', 'checkout_core_fields-reset', 'wcj_cart_core_fields_fields_options_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-checkout-core-fields-reset' ),
			array( 'checkout_custom_fields', 'checkout_custom_fields-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-checkout-custom-fields-activation' ),
			array( 'checkout_custom_fields', 'checkout_custom_fields-reset', 'wcj_checkout_custom_fields_custom_fields_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-fields/#booster-v10-s14-checkout-custom-fields-reset' ),
			array( 'checkout_custom_info', 'checkout_custom_info-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-info/#booster-v10-s14-checkout-custom-info-activation' ),
			array( 'checkout_custom_info', 'checkout_custom_info-reset', 'checkout_custom_info_general_options_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-custom-info/#booster-v10-s14-checkout-custom-info-reset' ),
			array( 'checkout_customization', 'checkout_customization-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-customization/#booster-v10-s14-checkout-customization-activation' ),
			array( 'checkout_customization', 'checkout_customization-reset', 'wcj_checkout_customization_force_checkout_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-customization/#booster-v10-s14-checkout-customization-reset' ),
			array( 'checkout_files_upload', 'checkout_files_upload-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-checkout-files-upload-activation' ),
			array( 'checkout_files_upload', 'checkout_files_upload-reset', 'wcj_checkout_files_upload_advanced_options_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-checkout-files-upload/#booster-v10-s14-checkout-files-upload-reset' ),
			array( 'empty_cart', 'empty_cart-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-empty-cart-button/#booster-v10-s14-empty-cart-activation' ),
			array( 'empty_cart', 'empty_cart-reset', 'wcj_empty_cart_general_options_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-empty-cart-button/#booster-v10-s14-empty-cart-reset' ),
			array( 'mini_cart', 'mini_cart-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-mini-cart-custom-info/#booster-v10-s14-mini-cart-activation' ),
			array( 'mini_cart', 'mini_cart-reset', 'wcj_mini_cart_blocks_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-mini-cart-custom-info/#booster-v10-s14-mini-cart-reset' ),
			array( 'one_page_checkout', 'one_page_checkout-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-one-page-checkout/#booster-v10-s14-one-page-checkout-activation' ),
			array( 'one_page_checkout', 'one_page_checkout-reset', 'wcj_one_page_checkout_general_options_tab', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-one-page-checkout/#booster-v10-s14-one-page-checkout-reset' ),
			array( 'product_add_to_cart', 'product_add_to_cart-activation', '', __( 'Module availability and activation', 'woocommerce-jetpack' ), __( 'Check the module switch and your access. Opening this page or saving wording does not activate the module.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-product-add-to-cart-activation' ),
			array( 'product_add_to_cart', 'product_add_to_cart-reset', 'wcj_add_to_cart_position_options', __( 'Native module reset', 'woocommerce-jetpack' ), __( 'Destructive native module defaults, not guided Undo; preserve native confirmation and permissions. Reset is included in Elite only; it is separate from guided Undo.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-product-add-to-cart/#booster-v10-s14-product-add-to-cart-reset' ),
		);
	}
	/** Actual installed registry, not a licence guess. */
	public static function available( $module ) {
		$core = WCJ_Job_Store::core();
		return $core && isset( $core->all_modules[ $module ] );
	}
	/** Same native module policy as accepted guided tasks. */
	public static function allowed( $module ) {
		return self::available( $module ) && WCJ_Task_Finder::allowed( $module );
	}
	/** Use a real category and native active tab. */
	public static function native_url( $module, $tab = '' ) {
		$category = 'address_formats' === $module ? 'shipping_and_orders' : ( 'product_add_to_cart' === $module ? 'products' : 'cart_and_checkout' );
		$url = admin_url( wcj_admin_tab_url() . '&wcj-cat=' . $category . '&section=' . $module );
		return $tab ? add_query_arg( 'active_tab', $tab, $url ) : $url;
	}
	/** Explicit edition boundaries for the affected native groups. */
	private static function limit( $group ) {
		$free = defined( 'WCJ_FREE_PLUGIN_PATH' );
		$elite = defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE );
		if ( false !== strpos( $group, '-reset' ) && ! $elite ) {
			return __( 'Module reset is not included in this edition.', 'woocommerce-jetpack' );
		}
		if ( $free && in_array( $group, array( 'question-identity', 'upload-slots' ), true ) ) {
			return __( 'Free: one effective native slot. Saved extra slots are not an entitlement to use them.', 'woocommerce-jetpack' );
		}
		if ( $free && in_array( $group, array( 'core-conditions', 'core-layout', 'core-global' ), true ) ) {
			return __( 'Free keeps the native paid controls disabled: category conditions, relation and priority have separate paid limits. Wording is available in Free.', 'woocommerce-jetpack' );
		}
		return __( 'Native field-specific edition and permission limits still apply.', 'woocommerce-jetpack' );
	}
	/** Render only relevant groups and real native destinations. */
	public static function render( $module = '', $only = array() ) {
		$groups = array_filter( self::groups(), static function ( $g ) use ( $module, $only ) { return ( ! $module || $module === $g[0] ) && ( ! $only || in_array( $g[1], $only, true ) ) && WCJ_Task_Finder::allowed( $g[0] ); } );
		if ( ! $groups ) {
			return;
		}
		echo '<details class="wcj-cart-checkout-groups"><summary>' . esc_html__( 'Cart and checkout: full settings and group help', 'woocommerce-jetpack' ) . '</summary><p>' . esc_html__( 'Choose the actual mechanism you need. Full settings and guides open separately; unsaved edits stay here. Native saves and resets are not covered by the Order notes wording Undo.', 'woocommerce-jetpack' ) . '</p><ul>';
		foreach ( $groups as $g ) {
			$available = self::available( $g[0] );
			$reset_missing = false !== strpos( $g[1], '-reset' ) && ( ! defined( 'WCJ_PLUGIN_FILE' ) || 'booster-elite-for-woocommerce.php' !== basename( WCJ_PLUGIN_FILE ) );
			$status = ! $available ? __( 'Not included in this edition', 'woocommerce-jetpack' ) : ( wcj_is_module_enabled( $g[0] ) ? __( 'Module on', 'woocommerce-jetpack' ) : __( 'Module off', 'woocommerce-jetpack' ) );
			echo '<li data-cart-checkout-group="' . esc_attr( $g[1] ) . '"><strong>' . esc_html( $g[3] ) . '</strong> &middot; ' . esc_html( $status ) . '<p>' . esc_html( $g[4] ) . '</p><p>' . esc_html( self::limit( $g[1] ) ) . '</p>';
			if ( $available && ! $reset_missing ) {
				$tabs = is_array( $g[2] ) ? $g[2] : array( $g[2] => __( 'Full settings (new tab)', 'woocommerce-jetpack' ) );
				foreach ( $tabs as $tab => $label ) {
					echo '<a href="' . esc_url( self::native_url( $g[0], $tab ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a> | ';
				}
			}
			echo '<a href="' . esc_url( $g[5] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Help for this group (new tab)', 'woocommerce-jetpack' ) . '</a></li>';
		}
		echo '</ul></details>';
	}
	/** Compact family entry, not a replacement for the accepted question task. */
	public static function entry() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wcj-cart-checkout-tasks' ) ) . '">' . esc_html__( 'Cart and checkout tasks: answers, wording, information and behaviour', 'woocommerce-jetpack' ) . '</a></p>';
		}
	}
	/** Four owner purposes preserve all twelve module destinations. */
	public static function hub() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
		}
		wp_enqueue_style( 'wcj-order-notes-job', wcj_plugin_url() . '/assets/css/admin/wcj-order-notes-job.css', array(), '1.0' );
		echo '<div class="wrap wcj-cart-checkout-hub"><h1>' . esc_html__( 'Cart and checkout tasks', 'woocommerce-jetpack' ) . '</h1><p>' . esc_html__( 'Keep existing answers and settings. Looking here changes nothing; each native tool keeps its own rights and checkout limits.', 'woocommerce-jetpack' ) . '</p>';
		$purposes = array(
			array( __( 'Collect an answer', 'woocommerce-jetpack' ), __( 'One question per order is the existing Checkout question task. File collection has separate ownership, storage and recovery.', 'woocommerce-jetpack' ), array( 'checkout_custom_fields', 'checkout_files_upload' ) ),
			array( __( 'Change an existing field', 'woocommerce-jetpack' ), __( 'Order notes wording is optional. Requiredness, removal, conditions and layout remain separate native controls.', 'woocommerce-jetpack' ), array( 'checkout_core_fields' ) ),
			array( __( 'Show information', 'woocommerce-jetpack' ), __( 'Display text or format addresses without collecting a new answer. Classic/theme hooks are not a promise of Woo Blocks support.', 'woocommerce-jetpack' ), array( 'address_formats', 'cart', 'checkout_custom_info', 'mini_cart' ) ),
			array( __( 'Change cart and checkout behaviour', 'woocommerce-jetpack' ), __( 'These tools can change cart contents, submission or navigation. Read their exact consequence before saving in the native editor.', 'woocommerce-jetpack' ), array( 'cart_customization', 'checkout_customization', 'empty_cart', 'one_page_checkout', 'product_add_to_cart' ) ),
		);
		foreach ( $purposes as $purpose ) {
			echo '<section class="wcj-purpose"><h2>' . esc_html( $purpose[0] ) . '</h2><p>' . esc_html( $purpose[1] ) . '</p>';
			foreach ( $purpose[2] as $module ) {
				if ( ! WCJ_Task_Finder::allowed( $module ) ) {
					echo '<p>' . esc_html__( 'A module in this purpose is restricted. Ask an administrator for access.', 'woocommerce-jetpack' ) . '</p>';
					continue;
				}
				if ( 'checkout_custom_fields' === $module && self::available( $module ) ) {
					echo '<p><a class="button" href="' . esc_url( WCJ_Checkout_Job::url() ) . '">' . esc_html__( 'Open Checkout question', 'woocommerce-jetpack' ) . '</a></p>';
				}
				if ( 'checkout_core_fields' === $module && self::available( $module ) ) {
					echo '<p><a class="button" href="' . esc_url( WCJ_Order_Notes_Job::url() ) . '">' . esc_html__( 'Change Order notes wording', 'woocommerce-jetpack' ) . '</a></p>';
				}
				self::render( $module );
			}
			echo '</section>';
		}
		echo '</div>';
	}

	/** Existing admin order fields have their own value and permission path. */
	public static function order_answers_help( $order ) {
		if ( ! is_object( $order ) || ! is_callable( array( $order, 'get_id' ) ) || ! current_user_can( 'edit_shop_order', $order->get_id() ) || ! self::allowed( 'checkout_custom_fields' ) || ! wcj_is_module_enabled( 'checkout_custom_fields' ) ) {
			return;
		}
		self::render( 'checkout_custom_fields', array( 'answer-display', 'question-identity' ) );
	}
	/** Add upload lifecycle guidance only beside a native order file panel that actually exists. */
	public static function order_panel_help() {
		global $wp_meta_boxes;
		if ( ! self::allowed( 'checkout_files_upload' ) || ! wcj_is_module_enabled( 'checkout_files_upload' ) ) {
			return;
		}
		foreach ( array( 'shop_order', 'woocommerce_page_wc-orders' ) as $screen ) {
			if ( empty( $wp_meta_boxes[ $screen ] ) ) {
				continue;
			}
			foreach ( $wp_meta_boxes[ $screen ] as &$contexts ) {
				foreach ( $contexts as &$boxes ) {
					$id = 'wc-jetpack-checkout_files_upload';
					if ( empty( $boxes[ $id ] ) || ! is_callable( $boxes[ $id ]['callback'] ) || ! empty( $boxes[ $id ]['wcj_s14_help'] ) ) {
						continue;
					}
					$original = $boxes[ $id ]['callback'];
					$boxes[ $id ]['wcj_s14_help'] = true;
					$boxes[ $id ]['callback'] = static function ( $object, $box ) use ( $original ) {
						$order_id = is_object( $object ) && is_callable( array( $object, 'get_id' ) ) ? $object->get_id() : ( $object->ID ?? 0 );
						if ( $order_id && current_user_can( 'edit_shop_order', $order_id ) && self::allowed( 'checkout_files_upload' ) ) {
							self::render( 'checkout_files_upload', array( 'upload-placement', 'upload-cleanup', 'upload-email', 'upload-safety' ) );
						}
						call_user_func( $original, $object, $box );
					};
				}
			}
		}
	}
	/** Customer profile prefill is separate from a current checkout or a stored order answer. */
	public static function profile_help( $user ) {
		if ( $user && current_user_can( 'edit_user', $user->ID ) && self::allowed( 'checkout_custom_fields' ) && wcj_is_module_enabled( 'checkout_custom_fields' ) ) {
			self::render( 'checkout_custom_fields', array( 'question-prefill', 'question-identity' ) );
		}
	}
	/** Add nearby help to native product panels without replacing their callbacks or fields. */
	public static function panel_help( $post ) {
		global $wp_meta_boxes;
		if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) || empty( $wp_meta_boxes['product'] ) ) {
			return;
		}
		$map = array( 'product_add_to_cart' => array( 'add-redirect', 'product-url', 'product-ajax' ), 'one_page_checkout' => array( 'one-page' ) );
		foreach ( $wp_meta_boxes['product'] as &$contexts ) {
			foreach ( $contexts as &$boxes ) {
				foreach ( $map as $module => $groups ) {
					$id = 'wc-jetpack-' . $module;
					if ( empty( $boxes[ $id ] ) || ! self::allowed( $module ) || ! is_callable( $boxes[ $id ]['callback'] ) ) {
						continue;
					}
					$original = $boxes[ $id ]['callback'];
					$boxes[ $id ]['callback'] = static function ( $product, $box ) use ( $original, $module, $groups ) {
						if ( current_user_can( 'edit_post', $product->ID ) && self::allowed( $module ) ) {
							self::render( $module, $groups );
						}
						call_user_func( $original, $product, $box );
					};
				}
			}
		}
	}
}

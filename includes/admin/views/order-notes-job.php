<?php
/** Order notes wording canvas. @package Booster_For_WooCommerce/admin */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wcj-order-notes" id="wcj-order-notes">
	<h1><?php esc_html_e( 'Change Order notes wording', 'woocommerce-jetpack' ); ?></h1>
	<p><?php esc_html_e( 'Change the wording of the existing Order notes field, not what checkout collects or requires. No module is turned on.', 'woocommerce-jetpack' ); ?></p>
	<p><a href="<?php echo esc_url( self::native_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Full Core Fields settings (new tab)', 'woocommerce-jetpack' ); ?></a> | <a href="https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-order-notes" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Order notes guide (new tab)', 'woocommerce-jetpack' ); ?></a> | <a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-cart-checkout-tasks' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Other cart and checkout tasks (new tab)', 'woocommerce-jetpack' ); ?></a></p>
	<section class="wcj-on-current" aria-labelledby="wcj-on-current-heading">
		<h2 id="wcj-on-current-heading"><?php esc_html_e( 'Current configuration', 'woocommerce-jetpack' ); ?></h2>
		<p id="wcj-on-context"><?php esc_html_e( 'Loading saved settings...', 'woocommerce-jetpack' ); ?></p>
		<p id="wcj-on-scope"></p>
	</section>
	<p class="wcj-on-message" id="wcj-on-message" role="status" aria-live="polite"></p>
	<form id="wcj-on-form" novalidate>
		<fieldset id="wcj-on-fields" disabled>
			<legend class="screen-reader-text"><?php esc_html_e( 'Order notes wording', 'woocommerce-jetpack' ); ?></legend>
			<?php foreach ( array( 'label' => __( 'Label', 'woocommerce-jetpack' ), 'placeholder' => __( 'Placeholder', 'woocommerce-jetpack' ), 'description' => __( 'Description', 'woocommerce-jetpack' ) ) as $key => $label ) : ?>
				<label for="wcj-on-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
				<input type="text" id="wcj-on-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" maxlength="500" aria-describedby="wcj-on-blank">
			<?php endforeach; ?>
		</fieldset>
		<p id="wcj-on-blank"><?php esc_html_e( 'Blank removes this override and keeps the incoming WooCommerce, theme or plugin wording. It does not make the shopper field blank. Plain single-line text only, up to 500 bytes per value.', 'woocommerce-jetpack' ); ?></p>
		<details class="wcj-on-sample"><summary><?php esc_html_e( 'Sample, not your checkout', 'woocommerce-jetpack' ); ?></summary><p><strong id="wcj-on-sample-label"></strong></p><textarea id="wcj-on-sample-input" disabled aria-label="<?php esc_attr_e( 'Order notes sample, not submitted', 'woocommerce-jetpack' ); ?>"></textarea><p id="wcj-on-sample-description"></p><p><?php esc_html_e( 'This plain illustration does not run checkout, load shopper answers, or prove field visibility.', 'woocommerce-jetpack' ); ?></p></details>
		<div class="wcj-on-actions"><button type="submit" class="button button-primary" id="wcj-on-save" disabled><?php esc_html_e( 'Save wording', 'woocommerce-jetpack' ); ?></button><button type="button" class="button" id="wcj-on-reload"><?php esc_html_e( 'Check saved settings', 'woocommerce-jetpack' ); ?></button><button type="button" class="button" id="wcj-on-discard" hidden><?php esc_html_e( 'Discard draft and load current', 'woocommerce-jetpack' ); ?></button></div>
		<p id="wcj-on-action-reason"></p>
	</form>
	<section id="wcj-on-receipt" hidden aria-labelledby="wcj-on-receipt-heading"><h2 id="wcj-on-receipt-heading"><?php esc_html_e( 'Saved configuration', 'woocommerce-jetpack' ); ?></h2><p id="wcj-on-record-status"></p><details id="wcj-on-changes"><summary><?php esc_html_e( 'What changed', 'woocommerce-jetpack' ); ?></summary><ul id="wcj-on-change-list"></ul></details><button type="button" class="button" id="wcj-on-undo" disabled><?php esc_html_e( 'Undo this wording change', 'woocommerce-jetpack' ); ?></button><p id="wcj-on-undo-reason"></p></section>
	<p><strong><?php esc_html_e( 'Next check:', 'woocommerce-jetpack' ); ?></strong> <span id="wcj-on-next-check"><?php esc_html_e( 'Load current configuration first.', 'woocommerce-jetpack' ); ?></span> <a href="<?php echo esc_url( self::native_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Full Core Fields settings (new tab)', 'woocommerce-jetpack' ); ?></a></p>
	<details><summary><?php esc_html_e( 'How this works and recovery', 'woocommerce-jetpack' ); ?></summary><p><?php esc_html_e( 'Only three wording options change. Requiredness, visibility, category conditions, module activation and existing order/customer answers stay untouched. Full settings open separately and keep your draft here. After a native edit, check saved settings; discard your draft deliberately to load newer values. Undo is offered only while your saved record and relevant native context still match. It restores absent options as absent, not as empty new rows.', 'woocommerce-jetpack' ); ?></p><p><?php esc_html_e( 'Classic is the initial supported output scope. Blocks, shortcodes inside templates, theme overrides and conditional shopper carts need their own check. This admin view does not execute checkout to decide shopper eligibility.', 'woocommerce-jetpack' ); ?></p></details>
	<details><summary><?php esc_html_e( 'Native value details', 'woocommerce-jetpack' ); ?></summary><ul id="wcj-on-raw-list"></ul></details>
	<?php WCJ_Cart_Checkout_Groups::render( 'checkout_core_fields' ); ?>
</div>

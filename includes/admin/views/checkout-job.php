<?php
/**
 * Checkout question page markup. State, preview and apply are driven by wcj-checkout-job.js.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wcj-ij wcj-cq" id="wcj-cq">
	<div class="wcj-ij-bar">
		<h1 class="wcj-ij-job"><?php esc_html_e( 'Checkout question', 'woocommerce-jetpack' ); ?></h1>
		<label class="wcj-cq-slot" id="wcj-cq-slot-wrap" hidden><span class="screen-reader-text"><?php esc_html_e( 'Question', 'woocommerce-jetpack' ); ?></span><select id="wcj-cq-slot"></select></label>
		<a class="wcj-ij-adv" id="wcj-cq-native" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Checkout_Job::native_url() ); ?>"><?php esc_html_e( 'All checkout field settings', 'woocommerce-jetpack' ); ?></a>
	</div>
	<?php // WordPress places global admin notices after this marker instead of inside the flex title bar. ?>
	<hr class="wp-header-end">
	<?php WCJ_Task_Finder::help( 'checkout_custom_fields' ); ?>
	<div class="wcj-ij-env" id="wcj-cq-env"></div>

	<div class="wcj-ij-cx">
		<form class="wcj-ij-ed" id="wcj-cq-form" novalidate onsubmit="return false;">
			<div class="wcj-cq-parked" id="wcj-cq-parked" hidden></div>
			<label class="wcj-ij-ck wcj-cq-on"><input type="checkbox" id="wcj-cq-enabled" data-k="enabled"> <?php esc_html_e( 'Ask this question at checkout', 'woocommerce-jetpack' ); ?></label>
			<p class="wcj-ij-err" id="wcj-cq-err-enabled"></p>

			<label class="wcj-ij-f" for="wcj-cq-label"><?php esc_html_e( 'Question', 'woocommerce-jetpack' ); ?>
				<input type="text" maxlength="200" id="wcj-cq-label" data-k="label" placeholder="<?php esc_attr_e( 'For example: Delivery instructions', 'woocommerce-jetpack' ); ?>">
			</label>
			<p class="wcj-ij-err" id="wcj-cq-err-label"></p>

			<fieldset class="wcj-cq-fs">
				<legend><?php esc_html_e( 'Answer type', 'woocommerce-jetpack' ); ?></legend>
				<div class="wcj-cq-seg" role="group" data-group="type">
					<button type="button" class="button" data-set="type:text" aria-pressed="false"><?php esc_html_e( 'Short answer', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button" data-set="type:textarea" aria-pressed="false"><?php esc_html_e( 'Long answer', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button" data-set="type:select" aria-pressed="false"><?php esc_html_e( 'Choice', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button" data-set="type:checkbox" aria-pressed="false"><?php esc_html_e( 'Checkbox', 'woocommerce-jetpack' ); ?></button>
				</div>
				<p class="wcj-ij-err" id="wcj-cq-err-type"></p>
			</fieldset>

			<div id="wcj-cq-options-box" hidden>
				<label class="wcj-ij-f" for="wcj-cq-options"><?php esc_html_e( 'Choices', 'woocommerce-jetpack' ); ?> <span class="wcj-ij-opt"><?php esc_html_e( 'one per line', 'woocommerce-jetpack' ); ?></span>
					<textarea rows="4" id="wcj-cq-options" data-k="options"></textarea>
				</label>
				<p class="wcj-ij-err" id="wcj-cq-err-options"></p>
			</div>

			<label class="wcj-ij-ck"><input type="checkbox" id="wcj-cq-required" data-k="required"> <?php esc_html_e( 'Customers must answer', 'woocommerce-jetpack' ); ?></label>
			<p class="wcj-ij-err" id="wcj-cq-err-required"></p>

			<fieldset class="wcj-cq-fs">
				<legend><?php esc_html_e( 'Where it appears', 'woocommerce-jetpack' ); ?></legend>
				<div class="wcj-cq-seg" role="group" data-group="section">
					<button type="button" class="button" data-set="section:billing" aria-pressed="false"><?php esc_html_e( 'Billing', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button" data-set="section:shipping" aria-pressed="false"><?php esc_html_e( 'Shipping', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button" data-set="section:order" aria-pressed="false"><?php esc_html_e( 'Order notes', 'woocommerce-jetpack' ); ?></button>
				</div>
				<p class="wcj-ij-err" id="wcj-cq-err-section"></p>
			</fieldset>

			<label class="wcj-ij-f" for="wcj-cq-placeholder"><?php esc_html_e( 'Hint', 'woocommerce-jetpack' ); ?> <span class="wcj-ij-opt"><?php esc_html_e( 'optional, inside the box', 'woocommerce-jetpack' ); ?></span>
				<input type="text" maxlength="200" id="wcj-cq-placeholder" data-k="placeholder" placeholder="<?php esc_attr_e( 'For example: Leave at reception', 'woocommerce-jetpack' ); ?>">
			</label>
			<p class="wcj-ij-err" id="wcj-cq-err-placeholder"></p>

			<fieldset class="wcj-cq-fs">
				<legend><?php esc_html_e( 'Who sees the answers', 'woocommerce-jetpack' ); ?> <span class="wcj-ij-opt" id="wcj-cq-shared-note"></span></legend>
				<label class="wcj-ij-ck"><input type="checkbox" id="wcj-cq-email_admin" data-k="email_admin"> <?php esc_html_e( 'Emails to you', 'woocommerce-jetpack' ); ?></label>
				<label class="wcj-ij-ck"><input type="checkbox" id="wcj-cq-email_customer" data-k="email_customer"> <?php esc_html_e( 'Customer emails', 'woocommerce-jetpack' ); ?></label>
				<label class="wcj-ij-ck"><input type="checkbox" id="wcj-cq-thanks" data-k="thanks"> <?php esc_html_e( 'Thank-you and order pages', 'woocommerce-jetpack' ); ?></label>
				<p class="wcj-ij-err is-note" id="wcj-cq-native-note" hidden></p>
				<p class="wcj-ij-err" id="wcj-cq-err-email_admin"></p>
				<p class="wcj-ij-err" id="wcj-cq-err-email_customer"></p>
				<p class="wcj-ij-err" id="wcj-cq-err-thanks"></p>
			</fieldset>

			<details class="wcj-ij-more" id="wcj-cq-kept-box" hidden>
				<summary><?php esc_html_e( 'Also saved for this question', 'woocommerce-jetpack' ); ?></summary>
				<ul class="wcj-cq-kept" id="wcj-cq-kept"></ul>
				<p class="wcj-ij-links"><?php esc_html_e( 'These stay as they are. Change them in', 'woocommerce-jetpack' ); ?> <a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Checkout_Job::native_url() ); ?>"><?php esc_html_e( 'all checkout field settings', 'woocommerce-jetpack' ); ?></a>.</p>
			</details>
		</form>

		<section class="wcj-ij-pv" aria-label="<?php esc_attr_e( 'Checkout preview', 'woocommerce-jetpack' ); ?>">
			<div class="wcj-ij-act">
				<span class="wcj-ij-seg" role="group" aria-label="<?php esc_attr_e( 'Compare', 'woocommerce-jetpack' ); ?>">
					<button type="button" class="button" data-side="now" aria-pressed="true"><?php esc_html_e( 'Now', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button" data-side="after" aria-pressed="false"><?php esc_html_e( 'After', 'woocommerce-jetpack' ); ?></button>
				</span>
				<span class="wcj-ij-fresh" id="wcj-cq-fresh"></span>
				<span class="wcj-ij-spacer"></span>
				<span id="wcj-ij-draftbar" class="wcj-cq-bar">
					<button type="button" class="button-link wcj-ij-count" id="wcj-cq-count" aria-expanded="false" aria-controls="wcj-cq-rows-box">0</button>
					<button type="button" class="button button-primary" id="wcj-cq-primary" disabled><?php esc_html_e( 'Apply', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button-link" id="wcj-cq-discard" hidden><?php esc_html_e( 'Discard', 'woocommerce-jetpack' ); ?></button>
				</span>
				<span id="wcj-ij-savedbar" class="wcj-cq-bar" hidden>
					<span class="wcj-ij-saved" id="wcj-cq-saved"></span>
					<button type="button" class="button" id="wcj-cq-undo"><?php esc_html_e( 'Undo', 'woocommerce-jetpack' ); ?></button>
				</span>
			</div>
			<div class="wcj-ij-status" id="wcj-cq-status" role="status" aria-live="polite"></div>
			<div class="wcj-ij-rows-box" id="wcj-cq-rows-box" hidden><ul class="wcj-ij-rows" id="wcj-cq-rows"></ul></div>
			<div class="wcj-ij-review" id="wcj-cq-review" hidden tabindex="-1"></div>
			<div class="wcj-cq-stage" id="wcj-cq-stage"></div>
			<div class="wcj-ij-under">
				<details class="wcj-ij-about"><summary><?php esc_html_e( 'About this preview', 'woocommerce-jetpack' ); ?></summary><ul id="wcj-cq-limits"></ul></details>
			</div>
			<details class="wcj-ij-receipt" id="wcj-cq-receipt" hidden><summary><?php esc_html_e( 'What changed', 'woocommerce-jetpack' ); ?></summary><div id="wcj-cq-receipt-body"></div></details>
		</section>
	</div>
<section class="wcj-job-next-check"><h2><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h2><p><?php esc_html_e( 'Open checkout and check this question. Verify selected answer destinations only with an authorized test order, not this preview.', 'woocommerce-jetpack' ); ?></p></section>
</div>

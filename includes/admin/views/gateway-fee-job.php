<?php
/**
 * Payment method fee page markup. State, preview and apply are driven by wcj-gateway-fee-job.js.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wcj-ij wcj-gf" id="wcj-gf">
	<div class="wcj-ij-bar">
		<h1 class="wcj-ij-job"><?php esc_html_e( 'Payment method fee', 'woocommerce-jetpack' ); ?></h1>
		<label class="wcj-gf-method"><span class="screen-reader-text"><?php esc_html_e( 'Payment method', 'woocommerce-jetpack' ); ?></span><select id="wcj-gf-method"></select></label>
		<a class="wcj-ij-adv" id="wcj-gf-native" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Gateway_Fee_Job::native_url() ); ?>"><?php esc_html_e( 'All fee settings', 'woocommerce-jetpack' ); ?></a>
	</div>
	<?php // WordPress places global admin notices after this marker instead of inside the flex title bar. ?>
	<hr class="wp-header-end">
	<?php WCJ_Task_Finder::help( 'payment_gateways_fees' ); ?>
	<div class="wcj-ij-env" id="wcj-gf-env"></div>

	<div class="wcj-ij-cx">
		<form class="wcj-ij-ed" id="wcj-gf-form" novalidate onsubmit="return false;">
			<div class="wcj-gf-locked" id="wcj-gf-locked" hidden></div>
			<div id="wcj-gf-fields">
				<label class="wcj-ij-f" for="wcj-gf-text"><?php esc_html_e( 'Name shoppers see', 'woocommerce-jetpack' ); ?> <span class="wcj-ij-opt"><?php esc_html_e( 'leave blank to turn this fee off', 'woocommerce-jetpack' ); ?></span>
					<input type="text" maxlength="200" id="wcj-gf-text" data-k="text" placeholder="<?php esc_attr_e( 'For example: Cash handling', 'woocommerce-jetpack' ); ?>">
				</label>
				<p class="wcj-ij-err" id="wcj-gf-err-text"></p>

				<fieldset class="wcj-gf-fs">
					<legend class="screen-reader-text"><?php esc_html_e( 'Fee or discount', 'woocommerce-jetpack' ); ?></legend>
					<div class="wcj-gf-seg" role="group" data-group="kind">
						<button type="button" class="button" data-set="kind:fee" aria-pressed="false"><?php esc_html_e( 'Charge a fee', 'woocommerce-jetpack' ); ?></button>
						<button type="button" class="button" data-set="kind:discount" aria-pressed="false"><?php esc_html_e( 'Give a discount', 'woocommerce-jetpack' ); ?></button>
					</div>
					<p class="wcj-ij-err" id="wcj-gf-err-kind"></p>
				</fieldset>

				<div class="wcj-gf-amount">
					<label class="wcj-ij-f" for="wcj-gf-amount"><?php esc_html_e( 'Amount', 'woocommerce-jetpack' ); ?>
						<input type="text" inputmode="decimal" maxlength="17" id="wcj-gf-amount" data-k="amount" placeholder="5.00">
					</label>
					<div class="wcj-gf-seg" role="group" data-group="type" aria-label="<?php esc_attr_e( 'Amount type', 'woocommerce-jetpack' ); ?>">
						<button type="button" class="button" data-set="type:fixed" aria-pressed="false" id="wcj-gf-fixed"><?php esc_html_e( 'Fixed', 'woocommerce-jetpack' ); ?></button>
						<button type="button" class="button" data-set="type:percent" aria-pressed="false"><?php esc_html_e( '% of cart', 'woocommerce-jetpack' ); ?></button>
					</div>
				</div>
				<p class="wcj-ij-err" id="wcj-gf-err-amount"></p>
				<p class="wcj-ij-err" id="wcj-gf-err-type"></p>

				<fieldset class="wcj-gf-fs">
					<legend><?php esc_html_e( 'Only for some cart amounts', 'woocommerce-jetpack' ); ?> <span class="wcj-ij-opt"><?php esc_html_e( '0 = no limit', 'woocommerce-jetpack' ); ?></span></legend>
					<div class="wcj-gf-limits">
						<label class="wcj-ij-f" for="wcj-gf-min"><?php esc_html_e( 'At least', 'woocommerce-jetpack' ); ?>
							<input type="text" inputmode="decimal" maxlength="17" id="wcj-gf-min" data-k="min">
						</label>
						<label class="wcj-ij-f" for="wcj-gf-max"><?php esc_html_e( 'At most', 'woocommerce-jetpack' ); ?>
							<input type="text" inputmode="decimal" maxlength="17" id="wcj-gf-max" data-k="max">
						</label>
					</div>
					<p class="wcj-ij-err" id="wcj-gf-err-min"></p>
					<p class="wcj-ij-err" id="wcj-gf-err-max"></p>
				</fieldset>

				<div class="wcj-gf-module" id="wcj-gf-module-box" hidden>
					<label class="wcj-ij-ck"><input type="checkbox" id="wcj-gf-module" data-k="module"> <?php esc_html_e( 'Turn on Fees & Discounts', 'woocommerce-jetpack' ); ?></label>
					<p class="wcj-gf-hint" id="wcj-gf-module-hint"></p>
					<p class="wcj-ij-err" id="wcj-gf-err-module"></p>
				</div>

				<details class="wcj-ij-more" id="wcj-gf-kept-box" hidden>
					<summary><?php esc_html_e( 'Also saved for this method', 'woocommerce-jetpack' ); ?></summary>
					<ul class="wcj-gf-kept" id="wcj-gf-kept"></ul>
					<p class="wcj-ij-links"><?php esc_html_e( 'These stay as they are. Change them in', 'woocommerce-jetpack' ); ?> <a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Gateway_Fee_Job::native_url() ); ?>"><?php esc_html_e( 'all fee settings', 'woocommerce-jetpack' ); ?></a>.</p>
				</details>
			</div>
		</form>

		<section class="wcj-ij-pv" aria-label="<?php esc_attr_e( 'Fee preview', 'woocommerce-jetpack' ); ?>">
			<div class="wcj-ij-act">
				<span class="wcj-ij-fresh" id="wcj-gf-fresh"></span>
				<span class="wcj-ij-spacer"></span>
				<span id="wcj-ij-draftbar" class="wcj-gf-bar">
					<button type="button" class="button-link wcj-ij-count" id="wcj-gf-count" aria-expanded="false" aria-controls="wcj-gf-rows-box">0</button>
					<button type="button" class="button button-primary" id="wcj-gf-primary" disabled><?php esc_html_e( 'Apply', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button-link" id="wcj-gf-discard" hidden><?php esc_html_e( 'Discard', 'woocommerce-jetpack' ); ?></button>
				</span>
				<span id="wcj-ij-savedbar" class="wcj-gf-bar" hidden>
					<span class="wcj-ij-saved" id="wcj-gf-saved"></span>
					<button type="button" class="button" id="wcj-gf-undo"><?php esc_html_e( 'Undo', 'woocommerce-jetpack' ); ?></button>
				</span>
			</div>
			<div class="wcj-ij-status" id="wcj-gf-status" role="status" aria-live="polite"></div>
			<div class="wcj-ij-rows-box" id="wcj-gf-rows-box" hidden><ul class="wcj-ij-rows" id="wcj-gf-rows"></ul></div>
			<div class="wcj-ij-review" id="wcj-gf-review" hidden tabindex="-1"></div>
			<div class="wcj-gf-stage" id="wcj-gf-stage">
				<div class="wcj-gf-ctx">
					<span class="wcj-gf-ctx-h"><?php esc_html_e( 'Example cart', 'woocommerce-jetpack' ); ?></span>
					<label for="wcj-gf-subtotal"><?php esc_html_e( 'Items', 'woocommerce-jetpack' ); ?> <input type="text" inputmode="decimal" maxlength="17" id="wcj-gf-subtotal" value="25.00"></label>
					<label for="wcj-gf-shipping"><?php esc_html_e( 'Shipping', 'woocommerce-jetpack' ); ?> <input type="text" inputmode="decimal" maxlength="17" id="wcj-gf-shipping" value="0.00"></label>
					<span class="wcj-gf-ctx-note"><?php esc_html_e( 'Same cart for Now and After. No tax.', 'woocommerce-jetpack' ); ?></span>
				</div>
				<div class="wcj-gf-cards">
					<div class="wcj-gf-card" id="wcj-gf-now"><div class="wcj-gf-card-h"><?php esc_html_e( 'Now', 'woocommerce-jetpack' ); ?></div><div class="wcj-gf-card-b"></div></div>
					<div class="wcj-gf-card is-after" id="wcj-gf-after"><div class="wcj-gf-card-h"><?php esc_html_e( 'After', 'woocommerce-jetpack' ); ?></div><div class="wcj-gf-card-b"></div></div>
				</div>
				<p class="wcj-gf-est"><?php esc_html_e( 'Fee estimate before tax and checkout adjustments. Not a payable total.', 'woocommerce-jetpack' ); ?></p>
			</div>
			<div class="wcj-ij-under">
				<details class="wcj-ij-about"><summary><?php esc_html_e( 'About this preview', 'woocommerce-jetpack' ); ?></summary><ul>
					<li><?php esc_html_e( 'Worked out by Booster\'s own fee calculation on the example cart, using your saved settings (Now) and your draft (After).', 'woocommerce-jetpack' ); ?></li>
					<li><?php esc_html_e( 'Tax, coupons, rounding by WooCommerce, your theme and the payment itself are not shown.', 'woocommerce-jetpack' ); ?></li>
					<li><?php esc_html_e( 'Nothing is saved, no cart or order is used and no payment method is switched on.', 'woocommerce-jetpack' ); ?></li>
					<?php if ( WCJ_Gateway_Fee_Job::explain_url() ) : ?>
						<li><a href="<?php echo esc_url( WCJ_Gateway_Fee_Job::explain_url() ); ?>"><?php esc_html_e( 'Compare two carts, roles or products in Rule explanations', 'woocommerce-jetpack' ); ?></a></li>
					<?php endif; ?>
				</ul></details>
			</div>
			<details class="wcj-ij-receipt" id="wcj-gf-receipt" hidden><summary><?php esc_html_e( 'What changed', 'woocommerce-jetpack' ); ?></summary><div id="wcj-gf-receipt-body"></div></details>
		</section>
	</div>
<section class="wcj-job-next-check"><h2><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h2><p><?php esc_html_e( 'Choose this payment method in a test cart and check the fee before placing any order.', 'woocommerce-jetpack' ); ?></p></section>
</div>

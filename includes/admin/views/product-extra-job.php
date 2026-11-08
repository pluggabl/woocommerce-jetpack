<?php
/**
 * Optional extra page markup. State, preview and apply are driven by wcj-product-extra-job.js.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wcj-ij wcj-pe" id="wcj-pe">
	<div class="wcj-ij-bar">
		<h1 class="wcj-ij-job"><?php esc_html_e( 'Optional extra', 'woocommerce-jetpack' ); ?></h1>
		<label class="wcj-pe-slot" id="wcj-pe-slot-wrap" hidden><span class="screen-reader-text"><?php esc_html_e( 'Extra', 'woocommerce-jetpack' ); ?></span><select id="wcj-pe-slot"></select></label>
		<a class="wcj-ij-adv" id="wcj-pe-native" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Product_Extra_Job::native_url() ); ?>"><?php esc_html_e( 'All Product Addons settings', 'woocommerce-jetpack' ); ?></a>
	</div>
	<?php // WordPress places global admin notices after this marker instead of inside the flex title bar. ?>
	<hr class="wp-header-end">
	<?php WCJ_Task_Finder::help( 'product_addons' ); ?>
	<details class="wcj-ij-more wcj-pe-info" id="wcj-pe-info" hidden><summary></summary></details>
	<div class="wcj-ij-env" id="wcj-pe-env"></div>

	<div class="wcj-ij-cx">
		<form class="wcj-ij-ed" id="wcj-pe-form" novalidate onsubmit="return false;">
			<div class="wcj-pe-parked" id="wcj-pe-parked" hidden></div>
			<div class="wcj-pe-parked wcj-pe-stale" id="wcj-pe-stale" role="alert" hidden></div>
			<div class="wcj-pe-locked" id="wcj-pe-locked" hidden></div>

			<label class="wcj-ij-ck wcj-pe-on"><input type="checkbox" id="wcj-pe-live" data-k="live"> <?php esc_html_e( 'Customers see this extra', 'woocommerce-jetpack' ); ?></label>
			<p class="wcj-ij-err" id="wcj-pe-err-live"></p>

			<label class="wcj-ij-f" for="wcj-pe-label"><?php esc_html_e( 'Name', 'woocommerce-jetpack' ); ?>
				<input type="text" maxlength="200" id="wcj-pe-label" data-k="label" placeholder="<?php esc_attr_e( 'For example: Gift wrap', 'woocommerce-jetpack' ); ?>">
			</label>
			<p class="wcj-ij-err" id="wcj-pe-err-label"></p>

			<fieldset class="wcj-pe-fs">
				<legend><?php esc_html_e( 'Price', 'woocommerce-jetpack' ); ?></legend>
				<div class="wcj-pe-price">
					<span class="wcj-pe-seg" role="group" aria-label="<?php esc_attr_e( 'Price type', 'woocommerce-jetpack' ); ?>">
						<button type="button" class="button" data-unit="fixed" aria-pressed="true"><?php esc_html_e( 'Amount per item', 'woocommerce-jetpack' ); ?></button>
						<button type="button" class="button" data-unit="percent" aria-pressed="false"><?php esc_html_e( '% of price', 'woocommerce-jetpack' ); ?></button>
					</span>
					<input type="text" inputmode="decimal" maxlength="12" id="wcj-pe-price" aria-label="<?php esc_attr_e( 'Price', 'woocommerce-jetpack' ); ?>" placeholder="5.00">
					<span class="wcj-pe-unit" id="wcj-pe-unit" aria-hidden="true"></span>
				</div>
				<p class="wcj-ij-err" id="wcj-pe-err-price"></p>
			</fieldset>

			<label class="wcj-ij-ck"><input type="checkbox" id="wcj-pe-required" data-k="required"> <?php esc_html_e( 'Customers must choose it', 'woocommerce-jetpack' ); ?></label>
			<p class="wcj-ij-err" id="wcj-pe-err-required"></p>

			<label class="wcj-ij-f" for="wcj-pe-tooltip"><?php esc_html_e( 'Hint', 'woocommerce-jetpack' ); ?> <span class="wcj-ij-opt"><?php esc_html_e( 'optional, on hover', 'woocommerce-jetpack' ); ?></span>
				<input type="text" maxlength="300" id="wcj-pe-tooltip" data-k="tooltip" placeholder="<?php esc_attr_e( 'For example: Wrapped by hand with a card', 'woocommerce-jetpack' ); ?>">
			</label>
			<p class="wcj-ij-err" id="wcj-pe-err-tooltip"></p>

			<label class="wcj-ij-f" for="wcj-pe-title"><?php esc_html_e( 'Heading', 'woocommerce-jetpack' ); ?> <span class="wcj-ij-opt"><?php esc_html_e( 'optional, above it', 'woocommerce-jetpack' ); ?></span>
				<input type="text" maxlength="200" id="wcj-pe-title" data-k="title">
			</label>
			<p class="wcj-ij-err" id="wcj-pe-err-title"></p>

			<fieldset class="wcj-pe-fs">
				<legend><?php esc_html_e( 'Which products', 'woocommerce-jetpack' ); ?></legend>
				<p class="wcj-pe-reach" id="wcj-pe-reach"></p>
				<ul class="wcj-pe-chips" id="wcj-pe-chips" aria-label="<?php esc_attr_e( 'Not on these products', 'woocommerce-jetpack' ); ?>"></ul>
				<label class="wcj-ij-f" for="wcj-pe-search"><?php esc_html_e( 'Leave it off a product', 'woocommerce-jetpack' ); ?>
					<input type="search" id="wcj-pe-search" autocomplete="off" aria-controls="wcj-pe-results" placeholder="<?php esc_attr_e( 'Search products', 'woocommerce-jetpack' ); ?>">
				</label>
				<ul class="wcj-pe-results" id="wcj-pe-results" role="listbox" hidden></ul>
				<p class="wcj-ij-err" id="wcj-pe-err-exclude"></p>
				<p class="wcj-ij-links"><?php esc_html_e( 'Only on one product? Use that product’s own Booster: Product Addons box.', 'woocommerce-jetpack' ); ?></p>
			</fieldset>

			<fieldset class="wcj-pe-fs wcj-pe-others" id="wcj-pe-others" hidden>
				<legend><?php esc_html_e( 'Other saved extras', 'woocommerce-jetpack' ); ?></legend>
				<p class="wcj-ij-opt"><?php esc_html_e( 'Switching extras on would also show these. They are hidden now.', 'woocommerce-jetpack' ); ?></p>
				<div id="wcj-pe-others-list"></div>
				<p class="wcj-ij-err" id="wcj-pe-err-others"></p>
			</fieldset>

			<details class="wcj-ij-more" id="wcj-pe-kept-box" hidden>
				<summary><?php esc_html_e( 'Also saved for this extra', 'woocommerce-jetpack' ); ?></summary>
				<ul class="wcj-pe-kept" id="wcj-pe-kept"></ul>
				<p class="wcj-ij-links"><?php esc_html_e( 'These stay as they are. Change them in', 'woocommerce-jetpack' ); ?> <a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Product_Extra_Job::native_url() ); ?>"><?php esc_html_e( 'all Product Addons settings', 'woocommerce-jetpack' ); ?></a>.</p>
			</details>
			<p class="wcj-ij-err" id="wcj-pe-err-general"></p>
		</form>

		<section class="wcj-ij-pv" aria-label="<?php esc_attr_e( 'Product page preview', 'woocommerce-jetpack' ); ?>">
			<div class="wcj-ij-act">
				<span class="wcj-ij-seg" role="group" aria-label="<?php esc_attr_e( 'Compare', 'woocommerce-jetpack' ); ?>">
					<button type="button" class="button" data-side="now" aria-pressed="false"><?php esc_html_e( 'Now', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button" data-side="after" aria-pressed="true"><?php esc_html_e( 'After', 'woocommerce-jetpack' ); ?></button>
				</span>
				<label class="wcj-pe-sample"><span class="screen-reader-text"><?php esc_html_e( 'Sample product', 'woocommerce-jetpack' ); ?></span><select id="wcj-pe-sample"></select></label>
				<span class="wcj-ij-spacer"></span>
				<span id="wcj-pe-draftbar" class="wcj-pe-bar">
					<button type="button" class="button-link wcj-ij-count" id="wcj-pe-count" aria-expanded="false" aria-controls="wcj-pe-rows-box">0</button>
					<button type="button" class="button button-primary" id="wcj-pe-primary" disabled><?php esc_html_e( 'Apply', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button-link" id="wcj-pe-discard" hidden><?php esc_html_e( 'Discard', 'woocommerce-jetpack' ); ?></button>
				</span>
				<span id="wcj-pe-savedbar" class="wcj-pe-bar" hidden>
					<span class="wcj-ij-saved" id="wcj-pe-saved"></span>
					<button type="button" class="button" id="wcj-pe-undo"><?php esc_html_e( 'Undo', 'woocommerce-jetpack' ); ?></button>
				</span>
			</div>
			<div class="wcj-ij-status" id="wcj-pe-status" role="status" aria-live="polite"></div>
			<div class="wcj-ij-rows-box" id="wcj-pe-rows-box" hidden><ul class="wcj-ij-rows" id="wcj-pe-rows"></ul></div>
			<div class="wcj-ij-review" id="wcj-pe-review" hidden tabindex="-1"></div>
			<div class="wcj-pe-stage" id="wcj-pe-stage">
				<div class="wcj-pe-product" id="wcj-pe-product">
					<p class="wcj-pe-pname" id="wcj-pe-pname"></p>
					<p class="wcj-pe-pprice" id="wcj-pe-pprice"></p>
					<iframe class="wcj-pe-frame" id="wcj-pe-frame" title="<?php esc_attr_e( 'Booster product extras markup', 'woocommerce-jetpack' ); ?>" sandbox=""></iframe>
					<p class="wcj-pe-cartbtn" aria-hidden="true"><?php esc_html_e( 'Add to cart', 'woocommerce-jetpack' ); ?></p>
				</div>
				<p class="wcj-pe-sum" id="wcj-pe-sum"></p>
				<ul class="wcj-pe-notes" id="wcj-pe-notes"></ul>
			</div>
			<div class="wcj-ij-under">
				<details class="wcj-ij-about"><summary><?php esc_html_e( 'About this preview', 'woocommerce-jetpack' ); ?></summary><ul id="wcj-pe-limits"></ul></details>
			</div>
			<details class="wcj-ij-receipt" id="wcj-pe-receipt" hidden><summary><?php esc_html_e( 'What changed', 'woocommerce-jetpack' ); ?></summary><div id="wcj-pe-receipt-body"></div></details>
		</section>
	</div>
<section class="wcj-job-next-check"><h2><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h2><p><?php esc_html_e( 'Open a matching product, select the extra and check its price in the cart. Do not place an order.', 'woocommerce-jetpack' ); ?></p></section>
</div>

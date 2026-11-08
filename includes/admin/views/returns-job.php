<?php
/**
 * Returns job page. Values, example and receipt are filled from the load call.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wcj-ij wcj-rj">
	<h1><?php esc_html_e( 'Return requests', 'woocommerce-jetpack' ); ?>
		<a class="wcj-ij-adv" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Returns_Job::native_url() ); ?>"><?php esc_html_e( 'All Returns settings', 'woocommerce-jetpack' ); ?></a>
	</h1>
	<?php WCJ_Task_Finder::help( 'returns' ); ?>
	<p class="wcj-rj-lede"><?php esc_html_e( 'Logged-in owners of completed orders can ask to return items. Approving a request never refunds or restocks.', 'woocommerce-jetpack' ); ?></p>
	<div id="wcj-rj-msg" class="wcj-rj-msg" role="status" aria-live="polite"></div>

	<form id="wcj-rj-form" class="wcj-rj-card" hidden>
		<p id="wcj-rj-module-row">
			<label><input type="checkbox" id="wcj-rj-module" value="yes"> <?php esc_html_e( 'Turn on return requests', 'woocommerce-jetpack' ); ?></label>
			<span class="wcj-rj-hint"><?php esc_html_e( 'Saving other settings does not turn requests on. Check this box to include activation; turn off in All Returns settings.', 'woocommerce-jetpack' ); ?></span>
		</p>
		<p id="wcj-rj-module-lock" class="wcj-rj-note" hidden>
			<?php esc_html_e( 'Return requests are off. Item rules are saved, so turn Returns on in the full settings, where those rules are checked.', 'woocommerce-jetpack' ); ?>
			<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Returns_Job::native_url() ); ?>"><?php esc_html_e( 'All Returns settings', 'woocommerce-jetpack' ); ?></a>
		</p>
		<p id="wcj-rj-module-on" class="wcj-rj-hint" role="status"><?php esc_html_e( 'Return requests are on.', 'woocommerce-jetpack' ); ?></p>
		<fieldset>
			<legend><?php esc_html_e( 'Return window', 'woocommerce-jetpack' ); ?></legend>
			<span class="wcj-rj-presets">
				<button type="button" class="button" data-days="14">14</button>
				<button type="button" class="button" data-days="30">30</button>
				<button type="button" class="button" data-days="60">60</button>
			</span>
			<label><input type="number" id="wcj-rj-window" min="1" max="365" step="1" inputmode="numeric"> <?php esc_html_e( 'days after the order is completed', 'woocommerce-jetpack' ); ?></label>
			<span class="wcj-rj-issue" data-for="window"></span>
		</fieldset>
		<p>
			<label><input type="checkbox" id="wcj-rj-notify" value="yes"> <?php esc_html_e( 'Queue status notices', 'woocommerce-jetpack' ); ?></label>
			<span class="wcj-rj-hint"><?php esc_html_e( 'Notices wait in the Returns queue; staff send each one by hand.', 'woocommerce-jetpack' ); ?></span>
		</p>
		<div id="wcj-rj-rules" class="wcj-rj-note" hidden></div>
		<details id="wcj-rj-rule" hidden>
			<summary><?php esc_html_e( 'Set one item rule', 'woocommerce-jetpack' ); ?></summary>
			<p class="wcj-rj-rule-row">
				<select id="wcj-rj-rule-kind" aria-label="<?php esc_attr_e( 'Item type', 'woocommerce-jetpack' ); ?>">
					<option value="product"><?php esc_html_e( 'Product ID', 'woocommerce-jetpack' ); ?></option>
					<option value="variation"><?php esc_html_e( 'Variation ID', 'woocommerce-jetpack' ); ?></option>
					<option value="category"><?php esc_html_e( 'Category ID', 'woocommerce-jetpack' ); ?></option>
				</select>
				<input type="number" id="wcj-rj-rule-id" min="1" step="1" inputmode="numeric" aria-label="<?php esc_attr_e( 'ID', 'woocommerce-jetpack' ); ?>">
				<select id="wcj-rj-rule-mode" aria-label="<?php esc_attr_e( 'Rule', 'woocommerce-jetpack' ); ?>">
					<option value="days"><?php esc_html_e( 'Window of', 'woocommerce-jetpack' ); ?></option>
					<option value="exclude"><?php esc_html_e( 'No returns', 'woocommerce-jetpack' ); ?></option>
				</select>
				<label id="wcj-rj-rule-days-wrap"><input type="number" id="wcj-rj-rule-days" min="1" max="365" step="1" inputmode="numeric"> <?php esc_html_e( 'days', 'woocommerce-jetpack' ); ?></label>
			</p>
			<span class="wcj-rj-hint"><?php esc_html_e( 'IDs are listed in Products and Categories. The review names the item before anything is saved; other saved rules stay as they are.', 'woocommerce-jetpack' ); ?></span>
			<span class="wcj-rj-issue" data-for="rule"></span>
		</details>
		<span class="wcj-rj-issue" data-for="module"></span>
		<p><button type="button" class="button button-primary" id="wcj-rj-review"><?php esc_html_e( 'Review changes', 'woocommerce-jetpack' ); ?></button></p>
	</form>

	<section id="wcj-rj-preview" class="wcj-rj-card" hidden>
		<h2><?php esc_html_e( 'Review', 'woocommerce-jetpack' ); ?></h2>
		<table class="widefat striped" id="wcj-rj-changes"><thead><tr><th></th><th><?php esc_html_e( 'Now', 'woocommerce-jetpack' ); ?></th><th><?php esc_html_e( 'After', 'woocommerce-jetpack' ); ?></th></tr></thead><tbody></tbody></table>
		<h3><?php esc_html_e( 'Example: elapsed days only', 'woocommerce-jetpack' ); ?></h3>
		<p class="wcj-rj-hint"><?php esc_html_e( 'Made-up orders completed this many days ago. This is arithmetic on the window, not a check of real customers or orders.', 'woocommerce-jetpack' ); ?></p>
		<table class="widefat" id="wcj-rj-example"><thead><tr><th></th></tr></thead><tbody></tbody></table>
		<ul class="wcj-rj-caveats">
			<li><?php esc_html_e( 'The window applies to new requests. Existing requests and history stay as they are.', 'woocommerce-jetpack' ); ?></li>
			<li><?php esc_html_e( 'Actual intake also depends on order status, quantities already requested, item rules, role settings and Returns storage being set up.', 'woocommerce-jetpack' ); ?></li>
			<li id="wcj-rj-cav-roles" hidden><?php esc_html_e( 'Modules by user roles is on, so who sees Returns depends on the customer\'s role.', 'woocommerce-jetpack' ); ?></li>
			<li id="wcj-rj-cav-rule" hidden><?php esc_html_e( 'Item rows assume an order line matched by this rule only. A variation rule beats a product rule, otherwise the lowest matching category applies, and any "no returns" match wins.', 'woocommerce-jetpack' ); ?></li>
			<li id="wcj-rj-cav-module" hidden><?php esc_html_e( 'Turning Returns on does not set up its private storage, guest access or email delivery. Check those in the full settings.', 'woocommerce-jetpack' ); ?></li>
		</ul>
		<p>
			<button type="button" class="button button-primary" id="wcj-rj-apply"><?php esc_html_e( 'Apply', 'woocommerce-jetpack' ); ?></button>
			<button type="button" class="button" id="wcj-rj-back"><?php esc_html_e( 'Keep editing', 'woocommerce-jetpack' ); ?></button>
		</p>
	</section>

	<section id="wcj-rj-receipt" class="wcj-rj-card" hidden>
		<h2 id="wcj-rj-receipt-title"><?php esc_html_e( 'Last change', 'woocommerce-jetpack' ); ?></h2>
		<p id="wcj-rj-diag" class="wcj-rj-note" hidden></p>
		<details id="wcj-rj-receipt-details"><summary><?php esc_html_e( 'What changed', 'woocommerce-jetpack' ); ?></summary><ul id="wcj-rj-receipt-rows"></ul><details><summary><?php esc_html_e( 'Technical values', 'woocommerce-jetpack' ); ?></summary><ul id="wcj-rj-receipt-raw"></ul></details></details>
		<p id="wcj-rj-receipt-note" class="wcj-rj-hint"></p>
		<div id="wcj-rj-pending" class="wcj-rj-note is-error" hidden>
			<?php esc_html_e( 'This change did not finish, so the values above may not be what you intended. Check them in', 'woocommerce-jetpack' ); ?>
			<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Returns_Job::native_url() ); ?>"><?php esc_html_e( 'All Returns settings', 'woocommerce-jetpack' ); ?></a>.
			<?php esc_html_e( 'New changes here wait until you confirm.', 'woocommerce-jetpack' ); ?>
			<p><button type="button" class="button" id="wcj-rj-resolve"><?php esc_html_e( 'I checked these settings', 'woocommerce-jetpack' ); ?></button></p>
		</div>
		<p><button type="button" class="button" id="wcj-rj-undo" hidden><?php esc_html_e( 'Undo this change', 'woocommerce-jetpack' ); ?></button></p>
	</section>
<section class="wcj-job-next-check"><h2><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h2><p id="wcj-rj-next-check"><?php esc_html_e( 'Check Returns setup, then use an authorized eligible test account and order to check the request form. Do not refund, restock or send a notice as a visual check.', 'woocommerce-jetpack' ); ?></p></section>
</div>

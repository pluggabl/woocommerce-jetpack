<?php /** @package Booster_For_WooCommerce/admin */ if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<section class="wcj-shj" aria-labelledby="wcj-shj-heading">
 <h2 id="wcj-shj-heading">Hide other shipping rates when Free shipping is offered</h2>
	<?php WCJ_Task_Finder::help( 'shipping_options' ); ?>
 <p id="wcj-shj-current"></p><div id="wcj-shj-message" role="status" aria-live="polite"></div><p id="wcj-shj-lock" hidden></p>
 <div id="wcj-shj-fields" hidden>
  <label><input id="wcj-shj-enabled" type="checkbox"> Hide other rates when a Free shipping method appears</label>
  <p><label for="wcj-shj-type">When Free shipping is offered, keep:</label> <select id="wcj-shj-type"></select></p><p id="wcj-shj-tier"></p>
  <p>In full settings, “Hide all” means hide all other rates, not Free shipping itself. Only native Free shipping rate keys count. A zero-cost Flat rate does not, and Local pickup may still charge.</p>
  <p id="wcj-shj-enable-row" hidden><label><input id="wcj-shj-activate" type="checkbox"> Turn on Shipping Options as a separate listed change</label></p>
  <details><summary>Sample rates, not your store</summary><p id="wcj-shj-preview-mode"></p><table><thead><tr><th>Example method and key</th><th>Example cost (<?php echo esc_html( get_woocommerce_currency() ); ?>)</th><th>Result</th></tr></thead><tbody id="wcj-shj-rates"></tbody></table><p>If no Free shipping rate is offered, this rule hides nothing. Other shipping rules and eligibility still apply.</p></details>
  <div id="wcj-shj-review" hidden><table id="wcj-shj-review-table"><thead><tr><th>Setting</th><th>Saved</th><th>After</th></tr></thead><tbody id="wcj-shj-changes"></tbody></table><p id="wcj-shj-review-consequence">Changes live checkout for all buyers on their next shipping recalculation.</p><button type="button" id="wcj-shj-apply" class="button button-primary">Apply change</button> <button type="button" id="wcj-shj-cancel" class="button">Cancel</button></div>
 </div>
 <section id="wcj-shj-receipt" hidden aria-label="Last shipping change"><h3>Last change</h3><p id="wcj-shj-receipt-status"></p><details id="wcj-shj-receipt-details"><summary>What changed</summary><ul id="wcj-shj-receipt-rows"></ul><p id="wcj-shj-receipt-technical"></p></details><button type="button" id="wcj-shj-recover" class="button">Undo</button><p id="wcj-shj-close" hidden><label><input type="checkbox" id="wcj-shj-close-confirm"> I checked the current settings and want to leave them as they are.</label> <button type="button" id="wcj-shj-resolve" class="button">Keep current settings and close the record</button></p></section>
 <p><a id="wcj-shj-native" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( self::native_url() ); ?>">Full Shipping Options settings</a>. The same switch also appears in <a id="wcj-shj-woo" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=options' ) ); ?>">WooCommerce Settings: Shipping options</a> when this module is on. Free-by-product, most-expensive shipping and shipping zones stay unchanged.</p>
<section class="wcj-job-next-check"><h2><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h2><p><?php esc_html_e( 'Check a cart where Free shipping is offered, then one where it is not. Do not place an order.', 'woocommerce-jetpack' ); ?></p></section>
</section>

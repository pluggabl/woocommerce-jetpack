<?php /** @package Booster_For_WooCommerce/admin */ if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<section class="wcj-ptj" aria-labelledby="wcj-ptj-heading">
 <h2 id="wcj-ptj-heading">Add a tab to your product pages</h2>
	<?php WCJ_Task_Finder::help( 'product_tabs' ); ?>
 <p>One plain-text shared tab. <a id="wcj-ptj-native" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( self::native_url() ); ?>">Open full tab editor</a>. Other tabs, per-product tabs and advanced options stay unchanged.</p>
 <div id="wcj-ptj-message" role="status" aria-live="polite"></div>
 <label for="wcj-ptj-slot">Shared tab</label> <select id="wcj-ptj-slot"><option value="auto">Choose a supported tab</option></select>
 <p id="wcj-ptj-tier"></p><p id="wcj-ptj-lock" class="wcj-ptj-warning" hidden></p>
 <div id="wcj-ptj-fields" hidden>
  <label for="wcj-ptj-title">Tab title</label><input id="wcj-ptj-title" type="text" maxlength="50" autocomplete="off">
  <label for="wcj-ptj-text">Tab text</label><textarea id="wcj-ptj-text" rows="5" maxlength="6000" placeholder="Shipping and returns information for shoppers"></textarea>
  <p class="description">Plain text only. Shortcodes and HTML need the full editor. This is public product information, not a change to return eligibility.</p>
  <fieldset><legend>Shows on</legend>
   <label><input type="radio" name="wcj-ptj-mode" value="all" checked> All products</label>
   <label><input type="radio" name="wcj-ptj-mode" value="categories"> Only products in these categories</label>
   <label id="wcj-ptj-except-row" hidden><input type="radio" name="wcj-ptj-mode" value="except"> All except these categories</label>
  </fieldset>
  <div id="wcj-ptj-categories" role="group" aria-label="Product categories" hidden></div>
  <p class="description">Category matching uses directly assigned categories. Children are not automatically included. Counts beside categories are native catalog counts; preview below counts published products.</p>
  <details><summary>More options</summary><label for="wcj-ptj-priority">Position (after Reviews: 40)</label><input id="wcj-ptj-priority" type="number" min="0" max="999" value="40"></details>
  <p id="wcj-ptj-enable-row" hidden><label><input id="wcj-ptj-activate" type="checkbox"> Turn on the shared tab controls listed in the confirmation</label></p>
  <div class="wcj-ptj-preview"><h3>Plain preview, not your theme</h3><strong id="wcj-ptj-preview-title"></strong><iframe id="wcj-ptj-preview-text" sandbox="" title="Plain tab text preview, not your theme"></iframe><details><summary>How this works</summary><p>Only escaped plain text is previewed. The real page still uses your site's content processing and theme; other tabs may affect its position.</p></details></div>
  <p id="wcj-ptj-reach" aria-live="polite"></p><p id="wcj-ptj-examples"></p><p id="wcj-ptj-summary"></p>
  <button id="wcj-ptj-apply" type="button" class="button button-primary" hidden disabled>Apply</button>
 </div>
 <section id="wcj-ptj-receipt" hidden aria-label="Last tab change"><h3>Last change</h3><p id="wcj-ptj-receipt-status"></p><details id="wcj-ptj-receipt-details"><summary>What changed</summary><ul id="wcj-ptj-receipt-rows"></ul><p id="wcj-ptj-receipt-technical"></p></details><a id="wcj-ptj-view-product" hidden target="_blank" rel="noopener">View on a matching product</a> <button id="wcj-ptj-recover" type="button" class="button">Undo</button><p id="wcj-ptj-close" hidden><label><input id="wcj-ptj-close-confirm" type="checkbox"> I checked the full editor and want to keep current settings.</label> <button id="wcj-ptj-resolve" type="button" class="button">Keep current settings and close this change</button></p></section>
 <dialog id="wcj-ptj-dialog" aria-labelledby="wcj-ptj-dialog-title"><h2 id="wcj-ptj-dialog-title">Review changes to this tab</h2><table class="widefat"><thead><tr><th>Setting</th><th>Saved now</th><th>After Apply</th></tr></thead><tbody id="wcj-ptj-changes"></tbody></table><p id="wcj-ptj-dialog-reach"></p><button id="wcj-ptj-cancel" type="button" class="button">Back to edit</button> <button id="wcj-ptj-confirm" type="button" class="button button-primary">Apply listed changes</button></dialog>
<section class="wcj-job-next-check"><h2><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h2><p><?php esc_html_e( 'Open a matching product and check the tab title, text and position.', 'woocommerce-jetpack' ); ?></p></section>
</section>

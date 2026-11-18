<?php
/** Existing percentage-discount task view. @package Booster_For_WooCommerce/admin */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap" id="wcj-discount-job">
 <h1>Adjust an existing percentage discount</h1>
 <p>Change one enabled percentage group without changing its products, order or activation. <strong>This setting can change charged prices.</strong> Other pricing rules still apply.</p>
 <p><a id="discount-native" href="<?php echo esc_url( WCJ_Global_Discount_Job::native_url() ); ?>" target="_blank" rel="noopener">Open full Global Discount settings (new tab)</a> | <a href="https://booster.io/docs/woocommerce-shop-global-discount/#booster-v10-s17-global-discount" target="_blank" rel="noopener">How this task works (new tab)</a> | <a href="<?php echo esc_url( WCJ_Pricing_Discount_Groups::url() ); ?>">All pricing choices</a></p>
 <p>Free and Plus: this new shortcut edits group 1. Plus retains all its existing groups in full settings. Elite can choose an eligible existing group. Other configurations stay native-only.</p>
 <div id="discount-message" role="status" aria-live="polite" tabindex="-1">Loading current configuration...</div>
 <div id="discount-refusal" class="discount-notice" hidden></div>
 <section aria-labelledby="discount-current-heading">
  <h2 id="discount-current-heading">Current saved configuration</h2>
  <p id="discount-status"></p><p id="discount-current"></p><p id="discount-position" class="discount-notice"></p>
  <ul id="discount-scope"></ul>
  <details><summary>Existing group order</summary><p>Enabled does not mean this group wins. Product and sale restrictions still decide which group applies first. Stored zero values can differ from absent values; this task does not normalize them.</p><ol id="discount-order"></ol></details>
  <details id="discount-other-wrap" hidden><summary>Other groups and configurations</summary><ul id="discount-other"></ul></details>
 </section>
 <form id="discount-form">
  <h2>Proposed change</h2>
  <label for="discount-group">Existing eligible group</label><select id="discount-group" aria-describedby="discount-position"></select>
  <button type="button" class="button" id="discount-load">Discard draft and load current values</button>
  <label for="discount-percent">Discount percent</label><input type="text" inputmode="decimal" id="discount-percent" maxlength="7" autocomplete="off" aria-describedby="discount-rate-help discount-stored">
  <p id="discount-rate-help">Use a positive percentage below 100, with at most four decimals. <strong>Blank or 0 does not turn this off.</strong> Use the group's Enable switch in full settings instead. For example, 10.25% is stored as -10.25.</p>
  <p id="discount-stored"></p>
  <label for="discount-example">Your example incoming amount (not a product price)</label><input type="text" inputmode="decimal" id="discount-example" value="80" maxlength="9" autocomplete="off" aria-describedby="discount-example-help discount-effect">
  <p id="discount-example-help">A positive example up to 9999.9999, with at most four decimals. The initial 80 is only an illustration. No product is looked up. No tax or currency rounding is applied.</p>
  <p id="discount-effect" class="discount-notice"></p>
  <p><strong>This group applies only if it is the first applicable group.</strong> Earlier groups may prevent it from running. Role, Wholesale, Formula and other price rules, tax, coupons, fees and currency conversion can change final charges.</p>
  <p id="discount-draft-status" aria-live="polite"></p>
  <button type="submit" class="button button-primary" id="discount-save" disabled>Review discount change</button>
 </form>
 <section id="discount-review" class="discount-notice" hidden aria-labelledby="discount-review-heading">
  <h2 id="discount-review-heading" tabindex="-1">Review the exact change</h2><p id="discount-review-change"></p><ul id="discount-review-scope"></ul><p id="discount-review-summary"></p>
  <p>Confirmation expires after five minutes. Any draft or context change needs a new review.</p>
  <button type="button" class="button button-primary" id="discount-confirm">Confirm and save reviewed discount</button> <button type="button" class="button" id="discount-cancel">Cancel review, keep draft</button>
 </section>
 <section id="discount-receipt" hidden aria-labelledby="discount-receipt-heading">
  <h2 id="discount-receipt-heading">Your last matching change</h2><p id="discount-receipt-state"></p><p id="discount-receipt-change"></p><p id="discount-receipt-summary"></p><p id="discount-receipt-current"></p>
  <button type="button" class="button" id="discount-undo" disabled>Review restoring previous discount</button>
  <p>Restore is available only while this exact saved change, its group context and your access still match. It does not overwrite newer edits. Only the latest task change has a restore receipt; a new save replaces it, including a save for another group or by another administrator.</p>
 </section>
</div>

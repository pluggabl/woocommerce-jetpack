<?php
/** Shipping description task. @package Booster_For_WooCommerce/admin */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wcj-description-job" id="wcj-description-job">
 <h1>Edit a shipping description</h1>
 <p>Change one plain-text description, not a price, delivery promise or shipping rule. Opening or saving this task does not enable anything.</p>
 <p><a href="<?php echo esc_url( WCJ_Shipping_Description_Job::native_url() ); ?>" target="_blank" rel="noopener noreferrer">Full description settings (new tab)</a> | <a href="https://booster.io/docs/woocommerce-shipping-descriptions/#booster-v10-s16-shipping-description" target="_blank" rel="noopener noreferrer">Description task help (new tab)</a> | <a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-shipping-delivery-tasks' ) ); ?>" target="_blank" rel="noopener noreferrer">All shipping tasks (new tab)</a></p>
 <div id="description-message" role="status" aria-live="polite">Loading current configuration...</div>
 <form id="description-form" novalidate>
  <p><label for="description-target"><strong>Existing method or instance</strong></label><br><select id="description-target" disabled><option value="">Loading...</option></select></p>
  <section aria-labelledby="description-current-title"><h2 id="description-current-title">Current saved configuration</h2>
   <p id="description-scope"></p><p id="description-status"></p><p id="description-context"></p><ul id="description-matches"></ul>
   <p><a id="description-woo" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>">WooCommerce shipping settings (new tab)</a></p>
   <pre id="description-saved" class="description-literal"></pre>
  </section>
  <p><label for="description-text"><strong>Your draft description</strong></label><br>
   <textarea id="description-text" rows="3" placeholder=" (Dispatches in 2 business days)" aria-describedby="description-hint description-validation" disabled></textarea></p>
  <p id="description-hint">Text is joined directly to the shipping label. Include your own leading or trailing space or punctuation. Up to 500 UTF-8 bytes, on one line. No HTML, entities or shortcodes. Empty clears existing wording; ordinary leading/trailing spaces are kept exactly.</p>
  <p id="description-validation" aria-live="polite"></p>
  <section aria-labelledby="description-sample-title"><h2 id="description-sample-title">Sample, not your checkout</h2><pre id="description-sample" class="description-literal"></pre><p>No price, offered rate, carrier promise or Blocks compatibility is inferred.</p></section>
  <p><button type="submit" class="button button-primary" id="description-save" disabled>Save description</button> <button type="button" class="button" id="description-discard">Discard draft and load current</button></p>
 </form>
 <section id="description-receipt" hidden aria-labelledby="description-receipt-title"><h2 id="description-receipt-title">Saved change</h2><p id="description-receipt-summary"></p><pre id="description-changes" class="description-literal"></pre><p id="description-receipt-current"></p><button type="button" class="button" id="description-undo" disabled>Undo this change</button></section>
 <p id="description-next"></p>
 <p>After editing in another tab, return here and deliberately discard and load current values. Native saves and resets are not this task's Undo. Your parked draft is never silently rebased.</p>
 <?php WCJ_Shipping_Delivery_Groups::render( 'shipping_description' ); ?>
</div>

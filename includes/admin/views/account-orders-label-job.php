<?php
/** Rename Orders task. @package Booster_For_WooCommerce/admin */
defined('ABSPATH') || exit;
?>
<div class="wrap wcj-account-label-job" id="wcj-account-label-job">
 <h1>Rename Orders in My Account</h1>
 <p>Change the Orders navigation label and Orders page heading together. This does not change the page address, order details, customer access, menu order or any order status.</p>
 <p><a href="<?php echo esc_url(WCJ_Account_Orders_Label_Job::native_url()); ?>" target="_blank" rel="noopener noreferrer">Full My Account settings (new tab)</a> | <a href="https://booster.io/docs/woocommerce-my-account/#booster-v10-s20-account-orders-label-task" target="_blank" rel="noopener noreferrer">Label setup, limits and recovery (new tab)</a> | <a href="<?php echo esc_url(WCJ_Order_Account_Groups::url()); ?>" target="_blank" rel="noopener noreferrer">Orders and account choices (new tab)</a></p>
 <p>Full settings keep your draft here. Save there first, then deliberately discard and load current values here.</p>
 <p id="wcj-account-label-notice" role="status" aria-live="polite" tabindex="-1">Reading saved account labels...</p>
 <section aria-labelledby="wcj-account-label-now"><h2 id="wcj-account-label-now">Last loaded settings</h2>
  <h3>Saved Orders wording</h3><p id="wcj-account-label-saved"></p>
  <h3>When the wording can be used</h3><ul id="wcj-account-label-conditions"></ul>
  <p>The Orders item must already be available in WooCommerce. Custom links, custom pages and other integrations can change what customers see. This task does not add a missing item or change the separate View order heading.</p>
  <p id="wcj-account-label-blocker" class="wcj-account-label-warning" hidden></p>
 </section>
 <section id="wcj-account-label-history" hidden><h2>Your last saved change</h2><p id="wcj-account-label-receipt"></p><p id="wcj-account-label-history-status"></p><button type="button" class="button" id="wcj-account-label-undo" hidden>Undo last Orders label change</button></section>
 <form id="wcj-account-label-form" novalidate>
  <h2>Orders wording</h2><label for="wcj-account-label-value">New Orders label and heading</label>
  <input id="wcj-account-label-value" name="label" type="text" maxlength="64" autocomplete="off" aria-describedby="wcj-account-label-input-help wcj-account-label-validation" disabled>
  <p id="wcj-account-label-input-help">Use a short, plain-text label such as Purchases. Accented letters and symbols may need a shorter label. The form tells you if the wording is too long or cannot be used. Leave blank to use the wording supplied by WooCommerce and your site. Other account labels stay unchanged.</p>
  <details><summary>Text limits and unsupported wording</summary><p>Use visible text on one line. Do not use HTML, shortcodes, encoded entities such as &amp;amp;, hidden or control characters, extra spaces at either end, repeated spaces, or just 0. The exact storage limit is 64 UTF-8 bytes, not 64 characters; some characters use more than one byte.</p></details>
  <p id="wcj-account-label-validation" aria-live="polite"></p>
  <div id="wcj-account-label-draft" hidden><h3>Wording illustration, not your account page</h3>
   <dl><dt>Navigation label</dt><dd id="wcj-account-label-sample"></dd><dt>Orders page heading</dt><dd id="wcj-account-label-heading"></dd></dl>
   <p>Only the wording is illustrated. Blank uses the supplied default, which can vary by language and site. Saving here does not enable My Account or Customize Menu &amp; Endpoints.</p>
   <button class="button button-primary" type="submit" id="wcj-account-label-save" disabled>Save Orders wording</button>
  </div>
 </form>
 <p><button type="button" class="button" id="wcj-account-label-reload">Discard draft and load current values</button></p>
 <section><h2>Next check</h2><p id="wcj-account-label-next">Check both saved switches before expecting a customer-facing change.</p></section>
 <details><summary>Stored label and Undo limits</summary><p>Field: <code id="wcj-account-label-key"></code>. <span id="wcj-account-label-existence"></span></p><pre id="wcj-account-label-raw"></pre><p>Other saved account labels are not shown here. Undo restores the exact previous saved-label state, including true absence, only while your latest change and relevant settings still match. It does not undo order actions, roles, addresses or changes made in full settings.</p></details>
 <?php WCJ_Order_Account_Groups::render('my_account'); ?>
</div>

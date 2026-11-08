<?php
/** Default sale badge text task. @package Booster_For_WooCommerce/admin */
defined('ABSPATH') || exit;
?>
<div class="wrap wcj-badge-job" id="wcj-badge-job">
 <h1>Change the default sale badge text</h1>
 <p>Change one plain label without changing a product's sale status, price or discount. This task does not turn on Sale Flash or change where badges are shown.</p>
 <p><a href="<?php echo esc_url(WCJ_Sale_Badge_Job::native_url()); ?>" target="_blank" rel="noopener noreferrer">Full Sale Flash settings (new tab)</a> | <a href="https://booster.io/docs/woocommerce-sale-flash/#booster-v10-s19-sale-badge-task" target="_blank" rel="noopener noreferrer">Sale badge setup, limits and recovery (new tab)</a> | <a href="<?php echo esc_url(WCJ_Promotions_Groups::url()); ?>" target="_blank" rel="noopener noreferrer">Promotions and merchandising choices (new tab)</a></p>
 <p>Full settings keep your draft here. Save there first, then deliberately discard and load current values here.</p>
 <p id="wcj-badge-notice" role="status" aria-live="polite" tabindex="-1">Reading the saved configuration...</p>
 <section aria-labelledby="wcj-badge-now"><h2 id="wcj-badge-now">Last loaded settings</h2>
  <h3>Saved default badge text</h3><p id="wcj-badge-saved"></p>
  <h3>When the default can be used</h3><ul id="wcj-badge-conditions"></ul>
  <p>Saving here does not make the badge appear. WooCommerce first checks whether a product is on sale. Product, category or tag choices can bypass this default; themes and other integrations can change the final display. A bypassed default does not mean every badge is hidden.</p>
  <p id="wcj-badge-blocker" class="wcj-badge-warning" hidden></p>
 </section>
 <section id="wcj-badge-history" hidden><h2>Your last saved change</h2><p id="wcj-badge-receipt"></p><p id="wcj-badge-history-status"></p><button type="button" class="button" id="wcj-badge-undo" hidden>Undo last badge text change</button></section>
 <form id="wcj-badge-form" novalidate>
  <h2>Badge text</h2><label for="wcj-badge-value">New default sale badge text</label>
  <input id="wcj-badge-value" name="badge" type="text" maxlength="64" autocomplete="off" aria-describedby="wcj-badge-input-help wcj-badge-validation" disabled>
  <p id="wcj-badge-input-help">Visible plain text only, up to 64 UTF-8 bytes, for example Sale. Some characters use more than one byte. Ampersands and quotes are allowed; spaces are kept as typed. No markup, shortcodes or encoded entities. Blank does not restore a default or hide the badge.</p>
  <p id="wcj-badge-validation" aria-live="polite"></p>
  <div id="wcj-badge-draft" hidden><h3>Text illustration, not a store preview</h3><p id="wcj-badge-sample" class="wcj-badge-sample"></p><p>Only the words are illustrated. Your theme controls appearance; this is not a discount calculation or a claim that the badge is visible.</p>
   <p>Save changes only the default label. Activation, hide switches, scoped badges, product prices and sale dates stay unchanged.</p>
   <button class="button button-primary" type="submit" id="wcj-badge-save" disabled>Save badge text</button>
  </div>
 </form>
 <p><button type="button" class="button" id="wcj-badge-reload">Discard draft and load current values</button></p>
 <section><h2>Next check</h2><p id="wcj-badge-next">Read the saved module, global and scope choices first.</p></section>
 <details><summary>Stored value and Undo limits</summary><p>Option: <code id="wcj-badge-key"></code>. <span id="wcj-badge-existence"></span></p><pre id="wcj-badge-raw"></pre><p>Advanced HTML stays unchanged in full settings. Undo is limited to your last verified change while its saved value and relevant settings still match. It restores the exact previous value, or removes the option if it was absent. Changes made in full settings do not acquire this task's Undo.</p></details>
 <?php WCJ_Promotions_Groups::render('sale_flash'); ?>
</div>

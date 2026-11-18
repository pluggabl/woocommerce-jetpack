<?php
/** Currency symbol task canvas. @package Booster_For_WooCommerce/admin */
defined('ABSPATH') || exit;
?>
<div class="wrap wcj-symbol-job" id="wcj-symbol-job">
 <h1 id="wcj-symbol-title">Change the symbol for your store currency</h1>
 <p>Change the text used for this currency without changing numeric amounts, the saved currency code or tax settings. This task does not convert prices or activate Currencies.</p>
 <p><a href="<?php echo esc_url(WCJ_Currency_Symbol_Job::native_url()); ?>" target="_blank" rel="noopener noreferrer">Full Currencies settings (new tab)</a> | <a href="https://booster.io/docs/woocommerce-all-currencies/#booster-v10-s18-symbol-task" target="_blank" rel="noopener noreferrer">Symbol setup, limits and recovery (new tab)</a> | <a href="<?php echo esc_url(WCJ_Currency_Tax_Groups::url()); ?>" target="_blank" rel="noopener noreferrer">Currency and tax choices (new tab)</a></p>
 <p>Full settings keep your draft here. Save there first, then deliberately discard and load current values here.</p>
 <p id="wcj-symbol-notice" role="status" aria-live="polite" tabindex="-1">Reading the saved configuration...</p>
 <section aria-labelledby="wcj-symbol-now"><h2 id="wcj-symbol-now">Last loaded settings</h2>
  <dl><dt>Store currency</dt><dd id="wcj-symbol-code"></dd><dt>Native symbol field</dt><dd id="wcj-symbol-branch"></dd><dt>Saved symbol</dt><dd id="wcj-symbol-saved"></dd><dt>Currencies module</dt><dd id="wcj-symbol-module"></dd></dl>
  <p id="wcj-symbol-blocker" class="wcj-symbol-warning" hidden></p>
 </section>
 <section id="wcj-symbol-history" hidden><h2>Your last saved change</h2><p id="wcj-symbol-receipt"></p><p id="wcj-symbol-history-status"></p><button type="button" class="button" id="wcj-symbol-undo" hidden>Undo last symbol change</button></section>
 <form id="wcj-symbol-form" novalidate>
  <h2>Symbol text</h2><label for="wcj-symbol-value">New symbol for <span id="wcj-symbol-target">the saved store currency</span></label>
  <input id="wcj-symbol-value" name="symbol" type="text" maxlength="64" autocomplete="off" aria-describedby="wcj-symbol-input-help wcj-symbol-validation" disabled>
  <p id="wcj-symbol-input-help">Visible plain text only, up to 64 UTF-8 bytes, for example $ or US$. No code, shortcodes or encoded symbols. Blank does not restore a default or hide the symbol.</p>
  <p id="wcj-symbol-validation" aria-live="polite"></p>
  <div id="wcj-symbol-draft" hidden><h3>Formatting sample, not a price or checkout quote</h3><p id="wcj-symbol-sample" class="wcj-symbol-sample"></p><p>Example arrangement only. This does not show your store's position, separators, precision, conversion or tax.</p>
   <p>This edits the selected native symbol only. Themes, Blocks, other currency settings and integrations may affect where it appears. Check actual display separately; saving is not proof of what shoppers see or pay.</p>
   <button class="button button-primary" type="submit" id="wcj-symbol-save" disabled>Save symbol</button>
  </div>
 </form>
 <p><button type="button" class="button" id="wcj-symbol-reload">Discard draft and load current values</button></p>
 <section><h2>Next check</h2><p id="wcj-symbol-next">Read the saved currency and module state first.</p></section>
 <details><summary>Stored value and scope details</summary><p>Option: <code id="wcj-symbol-key"></code>. <span id="wcj-symbol-existence"></span> Undo is limited to your last verified change while the exact value and relevant context still match. It restores prior presence, bytes and autoload, not an inferred default. Native advanced editors do not acquire this task's Undo.</p></details>
 <?php WCJ_Currency_Tax_Groups::inventory(); ?>
</div>

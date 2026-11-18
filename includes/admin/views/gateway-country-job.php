<?php
/** @package Booster_For_WooCommerce/admin */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap wcj-gcj">
 <h1>Payment method by country</h1>
	<?php WCJ_Task_Finder::help( 'payment_gateways_by_country' ); ?>
 <p>Set one method's country rule. Other checkout restrictions still apply. <a id="wcj-gcj-native" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( self::native_url() ); ?>">All country settings</a></p>
 <div id="wcj-gcj-message" role="status" aria-live="polite"></div>
 <div class="wcj-gcj-card">
  <label for="wcj-gcj-gateway"><strong>Payment method</strong></label>
  <select id="wcj-gcj-gateway"><option value="bacs">Bank transfer</option></select>
  <p id="wcj-gcj-context"></p><p id="wcj-gcj-lite" hidden>Guided Free/Plus: bank transfer. Existing Plus native controls for other methods remain in All country settings.</p>
  <p id="wcj-gcj-disabled" class="wcj-gcj-warning" hidden>This method is disabled in WooCommerce. A country-rule pass will not make it appear at checkout.</p>
  <p id="wcj-gcj-lock" class="wcj-gcj-warning" hidden></p>
  <form id="wcj-gcj-form" hidden>
   <fieldset><legend>Country rule</legend>
    <label><input type="radio" name="wcj-gcj-mode" value="include" checked> Only in these countries</label>
    <label><input type="radio" name="wcj-gcj-mode" value="exclude"> Except these countries</label>
   </fieldset>
   <label for="wcj-gcj-search">Find a country</label><input id="wcj-gcj-search" type="search" autocomplete="off" placeholder="Name or code">
   <div id="wcj-gcj-countries" class="wcj-gcj-country-list" role="group" aria-label="Countries"></div>
   <p id="wcj-gcj-selected"></p><p class="description">No countries selected means no country restriction. European Union expands to the native member list in Only mode.</p>
   <p id="wcj-gcj-activation-row"><label><input id="wcj-gcj-activate" type="checkbox"> Include turning on country rules in this change</label><br><span id="wcj-gcj-activation-note" class="description"></span></p>
   <button id="wcj-gcj-review" class="button button-primary" type="submit">Review changes</button>
  </form>
  <section id="wcj-gcj-preview" hidden aria-labelledby="wcj-gcj-preview-title">
   <h2 id="wcj-gcj-preview-title" tabindex="-1">Review this country change</h2>
   <table class="widefat" id="wcj-gcj-changes"><thead><tr><th>Setting</th><th>Saved</th><th>After</th></tr></thead><tbody></tbody></table>
   <p id="wcj-gcj-enforcement"></p>
   <div class="wcj-gcj-sample-wrap"><table class="widefat" id="wcj-gcj-samples"><thead><tr><th>Sample country</th><th>Saved rule</th><th>After rule</th></tr></thead><tbody></tbody></table></div>
   <p class="description">These are the native country-rule results, not whole-checkout availability. State and postcode are empty in these examples. Other plugins, method settings and checkout facts may still hide the method. A country supplied empty is not a checked country.</p>
   <button id="wcj-gcj-back" type="button" class="button">Back to edit</button>
   <button id="wcj-gcj-apply" type="button" class="button button-primary">Apply this change</button>
  </section>
 </div>
 <section id="wcj-gcj-receipt" class="wcj-gcj-card" hidden><h2>Last country change</h2><p id="wcj-gcj-receipt-status"></p><details id="wcj-gcj-receipt-details"><summary>What changed</summary><ul id="wcj-gcj-receipt-rows"></ul><p id="wcj-gcj-receipt-technical"></p></details><button id="wcj-gcj-recover" type="button" class="button">Undo this change</button><div id="wcj-gcj-close" hidden><p>After checking All country settings, you can keep the current values instead of restoring this change.</p><label><input id="wcj-gcj-close-confirm" type="checkbox"> I checked the current country settings</label><p><button id="wcj-gcj-resolve" type="button" class="button">Keep current settings and close this change</button></p></div></section>
<section class="wcj-job-next-check"><h2><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h2><p id="wcj-gcj-next-check"><?php esc_html_e( 'Use a known country under the selected detection mode, then check the method at checkout. Other restrictions still apply. Do not place an order.', 'woocommerce-jetpack' ); ?></p></section>
</div>

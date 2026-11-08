<?php /** @package Booster_For_WooCommerce/admin */ defined( 'ABSPATH' ) || exit;
$id=static::ID; $price_example=''; $product_name='';
if ( 'price_labels' === $id ) {
	$examples=wc_get_products(array('status'=>'publish','type'=>'simple','limit'=>1,'orderby'=>'ID','order'=>'ASC','return'=>'objects','visibility'=>'visible'));
	if ( $examples && '' !== $examples[0]->get_price() ) {
		$price_example=html_entity_decode(wp_strip_all_tags(wc_price(wc_get_price_to_display($examples[0]))),ENT_QUOTES,get_bloginfo('charset'));
		$product_name=$examples[0]->get_name();
	}
}
$default_wording=''; if('more_button_labels'===$id){$default_wording=__('Place order','woocommerce');}elseif('free_price'===$id){$default_wording=__('Free!','woocommerce');}
$intro = array(
 'more_button_labels' => 'Change the wording, not the order total or payment.',
 'add_to_cart' => 'Change the button wording, not what the button does.',
 'price_labels' => 'Add text beside the price without changing the stored amount.',
 'free_price' => 'Change zero-price text, not the price. This is not a discount.',
);
$next_check = array(
 'more_button_labels' => 'Open checkout in a private window. Select each payment method you offer and read the button. Do not place an order.',
 'add_to_cart' => 'Open a matching product and the shop page. Check the button wording for the product types you use.',
 'price_labels' => 'Open a matching product and the shop page. Check the text beside its price.',
 'free_price' => 'Open a zero-priced product and the shop page. Check the text shown for its price.',
);
$input_label = 'price_labels' === $id ? 'Price text' : ( 'free_price' === $id ? 'Zero-price text' : 'Button text' );
$op = self::post('wcj_label_op');
$known_result = in_array($message, array('Saved and read back from the native setting.', 'Previous native value restored and read back.', 'No change needed.'), true);
// Presentation only: the controller still authorizes and verifies every native write and Undo.
$readable = static function ($state, $key) use ($id) {
 if (self::OVERRIDE === $key && (!$state['exists'] || in_array($state['raw'], array('yes', 'no', ''), true))) { return self::display($state, $key); }
 if (!$state['exists'] || self::shipped_free_default($state, $key)) { return 'Default wording'; }
 if ('' === $state['raw']) { return 'free_price' === $id ? 'Empty native label' : ('price_labels' === $id ? 'No added text' : 'Default wording'); }
 return is_string($state['raw']) ? $state['raw'] : wp_json_encode($state['raw']);
};
$record = static function ($key, $states, $lock) use ($field, $message, $error, $known_result, $op, $readable) {
 $gateway = self::OVERRIDE === $key;
 $channel = $gateway ? 'gateway' : 'wording';
 $state = $states[$key];
 $receipt = self::receipt($key, $states[self::journal_key($key)]);
 $matches = $receipt && $state === $receipt['after'];
 $changed = $receipt && $receipt['before'] !== $receipt['after'];
 $own_result = $known_result && !$error && in_array($op, array('save_'.$channel, 'undo_'.$channel), true);
 $undone = $own_result && 'Previous native value restored and read back.' === $message;
 $noop = $own_result && 'No change needed.' === $message;
 $failed_save_record = $own_result && 'Saved and read back from the native setting.' === $message && (!$matches || !$changed);
 $saved = $matches && $changed && !$lock && !$error && !$undone && !$noop && !$failed_save_record;
 ?>
 <div class="wcj-label-record"<?php if ($own_result) { echo ' role="status" aria-atomic="true"'; } ?>>
 <p><?php if ($undone): ?><strong><?php echo $gateway ? 'Restored gateway policy:' : 'Restored:'; ?></strong>
 <?php elseif ($noop): ?><strong>No change needed.</strong> 0 changes. <?php echo $gateway ? 'Current gateway policy:' : 'Current wording:'; ?>
 <?php elseif ($saved): ?><strong><?php echo $gateway ? 'Saved gateway policy:' : 'Saved:'; ?></strong>
 <?php else: ?><strong><?php echo $gateway ? 'Current gateway policy:' : 'Current wording:'; ?></strong><?php endif; ?>
 <span class="wcj-label-value"><?php echo esc_html($readable($state, $key)); ?></span><?php if ($saved): ?> (was <span class="wcj-label-value"><?php echo esc_html($readable($receipt['before'], $key)); ?></span>). <span>1 change.</span><?php endif; ?></p>
 <?php if ($failed_save_record): ?><p>The saved record no longer matches this setting. Check full settings before making another change.</p><?php endif; ?>
 <?php if ($receipt && (!$matches || $error)): ?><details open><summary><?php echo !$matches ? 'The native value changed elsewhere. Undo is unavailable.' : 'Previous change details'; ?></summary>
 <p>Previous change: <span class="wcj-label-value"><?php echo esc_html($readable($receipt['before'], $key)); ?></span> to <span class="wcj-label-value"><?php echo esc_html($readable($receipt['after'], $key)); ?></span>. The current value above was kept.</p></details><?php endif; ?>
 <?php if ($matches && $changed && !$lock && !$undone && !$failed_save_record): ?>
 <form method="post" action="<?php echo esc_url(static::url($field)); ?>">
 <?php self::form_state($field, 'undo_'.$channel, $key, $states); ?>
 <button class="button" type="submit"><?php echo $gateway ? 'Undo gateway change' : 'Undo'; ?></button></form>
 <?php endif; ?></div>
 <?php
};
?>
<div class="wrap wcj-label-job" id="wcj-label-job" data-task="<?php echo esc_attr($id); ?>" data-default="<?php echo esc_attr($default_wording); ?>" data-price="<?php echo esc_attr($price_example); ?>" data-before="<?php echo esc_attr('wcj_global_price_labels_add_before_text'===$field?'yes':'no'); ?>">
<h1><?php echo esc_html($t[1]); ?></h1>
<p><?php echo esc_html($intro[$id]); ?></p>
<?php if ($message && ($error || !$known_result)) : ?><div class="notice <?php echo $error?'notice-error':'notice-success'; ?> inline" role="<?php echo $error?'alert':'status'; ?>"><p><?php echo esc_html($message); ?></p></div><?php endif; ?>
<div class="wcj-label-layout"><section class="wcj-label-card">
<p class="wcj-label-module"><strong>Module <?php echo $on?'on':'off'; ?>.</strong> <?php if(!$on): ?>Wording will not affect shoppers until you <a href="<?php echo esc_url(static::native_url()); ?>">turn this module on in full settings</a>. Saving here does not turn it on.<?php endif; ?></p>
<?php if ('add_to_cart'===$id): ?><p>Per Product Type Labels: <strong><?php echo 'yes'===wcj_get_option('wcj_add_to_cart_text_enabled','no')?'on':'off'; ?></strong>. Both this section and the module must be on. <?php if ('yes'!==wcj_get_option('wcj_add_to_cart_text_enabled','no')): ?><a href="<?php echo esc_url(static::native_url()); ?>">Turn on this section in full settings</a>. Saving here does not turn it on.<?php endif; ?></p><?php endif; ?>
<?php if(count($fields)>1): ?>
<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="wcj-label-scope">
<input name="page" type="hidden" value="<?php echo esc_attr('wcj-'.$t[2].'-job'); ?>"><label for="wcj-label-scope"><strong>Choose one setting</strong></label>
<select id="wcj-label-scope" name="field"><?php foreach($fields as $key=>$row): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($key,$field); ?>><?php echo esc_html($row[0].($row[1]?'':' (not editable in this edition)')); ?></option><?php endforeach; ?></select>
<button class="button" type="submit">Open setting</button></form>
<?php endif; ?>
<p><strong>Applies to:</strong> <?php echo esc_html($fields[$field][0]); ?></p>
<?php if ('more_button_labels' === $id && !interface_exists('\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface')): ?><p>The Blocks integration API is not available on this request.</p><?php endif; ?>
<?php if($locked): ?><p role="note"><?php echo esc_html($locked); ?></p>
<?php else: ?>
<form id="wcj-label-wording" method="post" action="<?php echo esc_url(static::url($field)); ?>">
<?php self::form_state($field,'save_wording',$field,$wording_state); ?>
<label for="wcj-label-value"><strong><?php echo esc_html($input_label); ?></strong></label>
<input id="wcj-label-value" name="label_value" type="text" maxlength="500" value="<?php echo esc_attr($current['exists'] && !self::shipped_free_default($current,$field)?$current['raw']:''); ?>" aria-describedby="wcj-label-default-note">
<p id="wcj-label-default-note"><?php if('price_labels'===$id): ?>Leave blank to remove this added text. Leading and trailing spaces are kept.<?php else: ?>Leave blank to use the default wording.<?php endif; ?><?php if('free_price'===$id): ?> Saving blank restores the module's built-in “Free!” label, not an empty price.<?php elseif('add_to_cart'===$id): ?> Another matching rule or the product's normal wording can still apply.<?php endif; ?></p>
<?php if('more_button_labels'===$id): ?><p>Some payment methods use their own wording. <a href="#wcj-label-gateway">Change that separately</a>. Saving wording never changes that policy.</p><?php elseif('add_to_cart'===$id): ?><p>In-cart, stock, sale and price conditions can take precedence. Variable, external and grouped products have different normal actions.</p><?php elseif('free_price'===$id): ?><p>Use a real zero price, not a missing price. Grouped and variable products depend on their child prices. Related products and the selected variation have separate wording.</p><?php else: ?><p>Global include/exclude lists, replacement text, theme output and per-product settings can change what appears.</p><?php endif; ?>
<div class="wcj-label-preview"><p><strong>Preview, not live checkout</strong></p>
<?php if('price_labels'===$id): ?><p><?php echo $price_example?esc_html('Sample catalog product: '.$product_name.'.'):'No priced simple product available for a numeric preview.'; ?></p><?php endif; ?>
<output id="wcj-label-preview" aria-live="polite"><?php echo esc_html('price_labels'===$id?$price_example:self::display($current,$field)); ?></output></div>
<p><button type="submit" class="button button-primary">Save wording</button> <button type="button" id="wcj-label-clear" class="button" hidden>Restore default</button></p>
</form>
<?php endif; $record($field,$wording_state,$locked); ?>
</section>
<section class="wcj-label-card wcj-label-next" aria-labelledby="wcj-label-next-heading"><h2 id="wcj-label-next-heading">Next check</h2>
<?php if (!$on): ?><p>This module is off, so its saved wording is not active for shoppers. <a href="<?php echo esc_url(static::native_url()); ?>">Review activation in full settings</a> before checking the shopper page. Saving wording here does not turn it on.</p>
<?php elseif ('add_to_cart' === $id && 'yes' !== wcj_get_option('wcj_add_to_cart_text_enabled', 'no')): ?><p>Per Product Type Labels is off, so these saved labels are not active. <a href="<?php echo esc_url(static::native_url()); ?>">Review this section in full settings</a> before checking the product or shop page.</p>
<?php else: ?><p><?php echo esc_html($next_check[$id]); ?></p><?php endif; ?>
<details class="wcj-label-mechanisms"><summary>How this works</summary>
<?php if('more_button_labels'===$id): ?>
<p>Classic uses the standard Place order button. <?php echo interface_exists('\\Automattic\\WooCommerce\\Blocks\\Integrations\\IntegrationInterface')?'WooCommerce’s Blocks integration API is present.':'The Blocks integration API is not available on this request.'; ?> Supported standard Checkout Blocks can use the native label filter when the payment method is known. Blank wording keeps the incoming Blocks label; gateway-owned and express buttons can differ.</p>
<?php elseif('add_to_cart'===$id): ?>
<p><a href="<?php echo esc_url(static::native_url('add_to_cart_per_category_tab')); ?>">Category groups and their labels</a> · <a href="<?php echo esc_url(static::native_url('add_to_cart_per_product_tab')); ?>">Per-product settings</a>. Individual values live on the product edit screen when enabled.</p>
<?php elseif('price_labels'===$id): ?>
<p><a href="<?php echo esc_url(static::native_url('price_labels_globally_tab')); ?>">Global replacement, exclusions and price placement</a> · <a href="<?php echo esc_url(static::native_url('price_labels_per_product_tab')); ?>">Per-product price labels</a>. Use the product editor for individual overrides. The numeric sample uses a current display amount, not theme markup, sale decorations or eligibility rules.</p>
<?php endif; ?>
<p>If you see old wording, check page or object caches before saving again. This editor does not purge caches or translate wording.<?php if ('more_button_labels'===$id): ?> The Place order string is listed in the shipped WPML configuration. No automatic translation is created here.<?php endif; ?> Verify each shop language with your translation integration.</p>
</details>
<p><a href="<?php echo esc_url(static::native_url()); ?>">Full settings for this module</a></p>
<?php WCJ_Task_Finder::help($id,true); ?>
</section></div>
<?php if('more_button_labels'===$id):
$gateway_state=self::guard_states(self::OVERRIDE);$g=$gateway_state[self::OVERRIDE];$reason=self::lock_reason(self::OVERRIDE,$g); ?>
<details class="wcj-label-card" id="wcj-label-gateway" <?php if ( in_array( self::post( 'wcj_label_op' ), array( 'save_gateway','undo_gateway' ),true ) ) { echo 'open'; } ?>><summary><strong>Gateway-specific button text</strong></summary>

<p>Replacing gateway-specific wording can change what customers see when they select a method. Classic can suppress a gateway’s own label even when your wording is blank. Blank wording does not replace a native Blocks label; express or custom payment buttons can remain gateway-owned.</p>
<?php if(!$reason): ?><form method="post" action="<?php echo esc_url(static::url($field)); ?>" id="wcj-label-gateway-form">
<?php self::form_state($field,'save_gateway',self::OVERRIDE,$gateway_state); ?>
<label for="wcj-label-gateway-choice">When a payment method provides its own text</label>
<select id="wcj-label-gateway-choice" name="label_value"><option value="no" <?php selected('yes'!==$g['raw']); ?>>Keep gateway-specific text</option><option value="yes" <?php selected('yes'===$g['raw']); ?>>Replace gateway-specific text</option></select>
<p><label><input type="checkbox" name="confirm_gateway" value="yes" required> I want to change this gateway policy separately from the wording.</label></p>
<button type="submit" class="button">Save gateway choice</button></form>
<?php else: ?><p><?php echo esc_html($reason); ?></p><?php endif; $record(self::OVERRIDE,$gateway_state,$reason); ?></details>
<?php endif; ?>
</div>


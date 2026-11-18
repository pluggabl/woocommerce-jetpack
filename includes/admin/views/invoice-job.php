<?php
/**
 * Branded invoice canvas markup. State, preview and apply are driven by wcj-invoice-job.js.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$swatches = array(
	'#cccccc' => __( 'Light grey', 'woocommerce-jetpack' ),
	'#0073aa' => __( 'Blue', 'woocommerce-jetpack' ),
	'#2f5d50' => __( 'Forest green', 'woocommerce-jetpack' ),
	'#8a3a12' => __( 'Rust', 'woocommerce-jetpack' ),
	'#1a1a2e' => __( 'Ink', 'woocommerce-jetpack' ),
);
?>
<div class="wrap wcj-ij" id="wcj-ij">
	<div class="wcj-ij-bar">
		<h1 class="wcj-ij-job"><?php esc_html_e( 'Branded invoice', 'woocommerce-jetpack' ); ?></h1>
		<a class="wcj-ij-adv" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Invoice_Job::native_url( 'pdf_invoicing' ) ); ?>"><?php esc_html_e( 'All PDF settings', 'woocommerce-jetpack' ); ?></a>
	</div>
	<?php // WordPress places global admin notices after this marker instead of inside the flex title bar. ?>
	<hr class="wp-header-end">
	<?php WCJ_Task_Finder::help( 'pdf_invoicing' ); ?>
	<details class="wcj-ij-info" id="wcj-ij-info" hidden><summary></summary></details>
	<div class="wcj-ij-env" id="wcj-ij-env"></div>

	<p class="wcj-ij-status" id="wcj-ij-startup" role="status"><span id="wcj-ij-startup-message"><?php esc_html_e( 'Loading saved settings. Editing is unavailable until loading finishes.', 'woocommerce-jetpack' ); ?></span> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-invoice-job' ) ); ?>"><?php esc_html_e( 'Reload this page', 'woocommerce-jetpack' ); ?></a> <?php esc_html_e( 'or use All PDF settings if loading does not finish.', 'woocommerce-jetpack' ); ?></p>
	<div class="wcj-ij-cx" id="wcj-ij-workspace" inert aria-busy="true">
		<form class="wcj-ij-ed" id="wcj-ij-form" novalidate onsubmit="return false;">
			<h2 class="wcj-ij-h"><?php esc_html_e( 'Brand', 'woocommerce-jetpack' ); ?></h2>
			<div class="wcj-ij-logo">
				<span class="wcj-ij-thumb" id="wcj-ij-logo-box"><img id="wcj-ij-logo-img" alt="<?php esc_attr_e( 'Current logo', 'woocommerce-jetpack' ); ?>" hidden><span id="wcj-ij-logo-none"><?php esc_html_e( 'No logo', 'woocommerce-jetpack' ); ?></span></span>
				<span>
					<button type="button" class="button" id="wcj-ij-logo-pick"><?php esc_html_e( 'Choose logo', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button-link" id="wcj-ij-logo-clear"><?php esc_html_e( 'Remove', 'woocommerce-jetpack' ); ?></button>
				</span>
				<input type="hidden" data-k="header_image" id="wcj-ij-header_image">
			</div>
			<p class="wcj-ij-err" id="wcj-ij-err-header_image"></p>

			<fieldset class="wcj-ij-colors">
				<legend><?php esc_html_e( 'Colour', 'woocommerce-jetpack' ); ?></legend>
				<div class="wcj-ij-seg">
					<?php foreach ( $swatches as $hex => $name ) : ?>
						<button type="button" class="wcj-ij-sw" style="background:<?php echo esc_attr( $hex ); ?>" data-swatch="<?php echo esc_attr( $hex ); ?>" title="<?php echo esc_attr( $name ); ?>" aria-label="<?php echo esc_attr( $name ); ?>" aria-pressed="false"></button>
					<?php endforeach; ?>
					<label class="wcj-ij-custom" title="<?php esc_attr_e( 'Custom colour', 'woocommerce-jetpack' ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Custom colour', 'woocommerce-jetpack' ); ?></span><input type="color" id="wcj-ij-header_text_color" data-k="header_text_color"></label>
					<span class="wcj-ij-colorname" id="wcj-ij-colorname" aria-live="polite"></span>
				</div>
				<p class="wcj-ij-err" id="wcj-ij-err-header_text_color"></p>
			</fieldset>

			<h2 class="wcj-ij-h"><?php esc_html_e( 'Business', 'woocommerce-jetpack' ); ?></h2>
			<div id="wcj-ij-seller">
				<label class="wcj-ij-f" for="wcj-ij-seller_name"><?php esc_html_e( 'Business name', 'woocommerce-jetpack' ); ?>
					<input type="text" maxlength="120" id="wcj-ij-seller_name" data-k="seller_name" aria-describedby="wcj-ij-unset-seller_name" placeholder="COMPANY NAME">
				</label>
				<p class="wcj-ij-err" id="wcj-ij-err-seller_name"></p>
				<p class="description" id="wcj-ij-unset-seller_name" hidden><?php esc_html_e( 'Not set: the stock seller block shows COMPANY NAME.', 'woocommerce-jetpack' ); ?></p>
				<label class="wcj-ij-f" for="wcj-ij-seller_address"><?php esc_html_e( 'Address', 'woocommerce-jetpack' ); ?>
					<textarea rows="3" maxlength="300" id="wcj-ij-seller_address" data-k="seller_address" aria-describedby="wcj-ij-unset-seller_address"></textarea>
				</label>
				<p class="wcj-ij-err" id="wcj-ij-err-seller_address"></p>
				<p class="description" id="wcj-ij-unset-seller_address" hidden><?php esc_html_e( 'Not set: the stock seller block shows COMPANY ADDRESS 1.', 'woocommerce-jetpack' ); ?></p>
				<label class="wcj-ij-f" for="wcj-ij-seller_reference"><?php esc_html_e( 'Tax or company number', 'woocommerce-jetpack' ); ?> <span class="wcj-ij-opt"><?php esc_html_e( 'optional', 'woocommerce-jetpack' ); ?></span>
					<input type="text" maxlength="120" id="wcj-ij-seller_reference" data-k="seller_reference">
				</label>
				<p class="wcj-ij-err" id="wcj-ij-err-seller_reference"></p>
			</div>
			<p class="wcj-ij-lock" id="wcj-ij-seller-lock" hidden><?php esc_html_e( 'Your custom template sets the seller details and is kept as saved.', 'woocommerce-jetpack' ); ?> <a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Invoice_Job::native_url( 'pdf_invoicing_templates' ) ); ?>"><?php esc_html_e( 'Edit template', 'woocommerce-jetpack' ); ?></a></p>
			<label class="wcj-ij-f" for="wcj-ij-header_title_text"><?php esc_html_e( 'Title', 'woocommerce-jetpack' ); ?>
				<input type="text" maxlength="120" id="wcj-ij-header_title_text" data-k="header_title_text">
			</label>
			<p class="wcj-ij-err" id="wcj-ij-err-header_title_text"></p>

			<h2 class="wcj-ij-h"><?php esc_html_e( 'Sending', 'woocommerce-jetpack' ); ?></h2>
			<label class="wcj-ij-f" for="wcj-ij-create_on"><?php esc_html_e( 'Create invoices', 'woocommerce-jetpack' ); ?>
				<select id="wcj-ij-create_on" data-k="create_on"></select>
			</label>
			<p class="wcj-ij-err" id="wcj-ij-err-create_on"></p>
			<label class="wcj-ij-f" for="wcj-ij-attach_to_emails"><?php esc_html_e( 'Attach to', 'woocommerce-jetpack' ); ?>
				<select id="wcj-ij-attach_to_emails" data-k="attach_to_emails"></select>
			</label>
			<p class="wcj-ij-err" id="wcj-ij-err-attach_to_emails"></p>

			<details class="wcj-ij-more">
				<summary><?php esc_html_e( 'More options', 'woocommerce-jetpack' ); ?></summary>
				<label class="wcj-ij-ck"><input type="checkbox" id="wcj-ij-header-link" checked> <?php esc_html_e( 'Header line shows the business name', 'woocommerce-jetpack' ); ?></label>
				<label class="wcj-ij-f" for="wcj-ij-header_text"><?php esc_html_e( 'Header line', 'woocommerce-jetpack' ); ?>
					<input type="text" maxlength="120" id="wcj-ij-header_text" data-k="header_text">
				</label>
				<p class="wcj-ij-err" id="wcj-ij-err-header_text"></p>
				<label class="wcj-ij-ck"><input type="checkbox" id="wcj-ij-line-match" checked> <?php esc_html_e( 'Header line colour matches', 'woocommerce-jetpack' ); ?></label>
				<label class="wcj-ij-inline" for="wcj-ij-header_line_color"><?php esc_html_e( 'Header line colour', 'woocommerce-jetpack' ); ?> <input type="color" id="wcj-ij-header_line_color" data-k="header_line_color"></label>
				<p class="wcj-ij-err" id="wcj-ij-err-header_line_color"></p>
				<label class="wcj-ij-f" for="wcj-ij-header_image_width_mm"><?php esc_html_e( 'Logo width (mm)', 'woocommerce-jetpack' ); ?>
					<input type="number" min="5" max="150" step="1" id="wcj-ij-header_image_width_mm" data-k="header_image_width_mm">
				</label>
				<p class="wcj-ij-err" id="wcj-ij-err-header_image_width_mm"></p>
				<label class="wcj-ij-f" for="wcj-ij-footer_text"><?php esc_html_e( 'Footer', 'woocommerce-jetpack' ); ?>
					<input type="text" maxlength="240" id="wcj-ij-footer_text" data-k="footer_text">
				</label>
				<p class="wcj-ij-err" id="wcj-ij-err-footer_text"></p>
				<label class="wcj-ij-inline" for="wcj-ij-footer_text_color"><?php esc_html_e( 'Footer text', 'woocommerce-jetpack' ); ?> <input type="color" id="wcj-ij-footer_text_color" data-k="footer_text_color"></label>
				<label class="wcj-ij-inline" for="wcj-ij-footer_line_color"><?php esc_html_e( 'Footer line', 'woocommerce-jetpack' ); ?> <input type="color" id="wcj-ij-footer_line_color" data-k="footer_line_color"></label>
				<p class="wcj-ij-err" id="wcj-ij-err-footer_text_color"></p>
				<p class="wcj-ij-err" id="wcj-ij-err-footer_line_color"></p>
				<label class="wcj-ij-ck"><input type="checkbox" id="wcj-ij-header_enabled" data-k="header_enabled"> <?php esc_html_e( 'Show header', 'woocommerce-jetpack' ); ?></label>
				<label class="wcj-ij-ck"><input type="checkbox" id="wcj-ij-footer_enabled" data-k="footer_enabled"> <?php esc_html_e( 'Show footer', 'woocommerce-jetpack' ); ?></label>
				<p class="wcj-ij-links">
					<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Invoice_Job::native_url( 'pdf_invoicing_templates' ) ); ?>"><?php esc_html_e( 'HTML template', 'woocommerce-jetpack' ); ?></a> ·
					<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Invoice_Job::native_url( 'pdf_invoicing_styling' ) ); ?>"><?php esc_html_e( 'Styling', 'woocommerce-jetpack' ); ?></a> ·
					<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Invoice_Job::native_url( 'pdf_invoicing_numbering' ) ); ?>"><?php esc_html_e( 'Numbering', 'woocommerce-jetpack' ); ?></a> ·
					<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( WCJ_Invoice_Job::native_url( 'pdf_invoicing_emails' ) ); ?>"><?php esc_html_e( 'Email options', 'woocommerce-jetpack' ); ?></a>
				</p>
			</details>
		</form>

		<section class="wcj-ij-pv" aria-label="<?php esc_attr_e( 'Invoice preview', 'woocommerce-jetpack' ); ?>">
			<div class="wcj-ij-act" id="wcj-ij-act">
				<span class="wcj-ij-seg" role="group" aria-label="<?php esc_attr_e( 'Compare', 'woocommerce-jetpack' ); ?>">
					<button type="button" class="button" data-side="now" aria-pressed="true"><?php esc_html_e( 'Now', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button" data-side="after" aria-pressed="false"><?php esc_html_e( 'After', 'woocommerce-jetpack' ); ?></button>
				</span>
				<span class="wcj-ij-fresh" id="wcj-ij-fresh"></span>
				<span class="wcj-ij-spacer"></span>
				<span id="wcj-ij-draftbar">
					<button type="button" class="button-link wcj-ij-count" id="wcj-ij-count" aria-expanded="false" aria-controls="wcj-ij-rows-box">0</button>
					<button type="button" class="button button-primary" id="wcj-ij-primary" disabled><?php esc_html_e( 'Apply', 'woocommerce-jetpack' ); ?></button>
					<button type="button" class="button-link" id="wcj-ij-discard" hidden><?php esc_html_e( 'Discard', 'woocommerce-jetpack' ); ?></button>
				</span>
				<span id="wcj-ij-savedbar" hidden>
					<span class="wcj-ij-saved" id="wcj-ij-saved"></span>
					<button type="button" class="button" id="wcj-ij-undo"><?php esc_html_e( 'Undo', 'woocommerce-jetpack' ); ?></button>
				</span>
			</div>
			<div class="wcj-ij-status" id="wcj-ij-status" role="status" aria-live="polite"></div>
			<div class="wcj-ij-rows-box" id="wcj-ij-rows-box" hidden><ul class="wcj-ij-rows" id="wcj-ij-rows"></ul></div>
			<div id="wcj-ij-review" class="wcj-ij-review" hidden tabindex="-1"></div>
			<div class="wcj-ij-frame">
				<iframe id="wcj-ij-iframe" title="<?php esc_attr_e( 'Invoice PDF preview', 'woocommerce-jetpack' ); ?>" hidden></iframe>
				<div class="wcj-ij-veil" id="wcj-ij-veil" hidden>
					<p id="wcj-ij-veil-msg"></p>
					<button type="button" class="button button-primary" id="wcj-ij-render"><?php esc_html_e( 'Render preview', 'woocommerce-jetpack' ); ?></button>
				</div>
			</div>
			<div class="wcj-ij-under">
				<label class="wcj-ij-ck"><input type="checkbox" id="wcj-ij-auto" checked> <?php esc_html_e( 'Update preview as I edit', 'woocommerce-jetpack' ); ?></label>
				<details class="wcj-ij-about"><summary><?php esc_html_e( 'About this preview', 'woocommerce-jetpack' ); ?></summary><ul id="wcj-ij-limits"></ul></details>
			</div>
			<details class="wcj-ij-receipt" id="wcj-ij-receipt" hidden><summary><?php esc_html_e( 'What changed', 'woocommerce-jetpack' ); ?></summary><div id="wcj-ij-receipt-body"></div></details>
		</section>
	</div>
<section class="wcj-job-next-check"><h2><?php esc_html_e( 'Next check', 'woocommerce-jetpack' ); ?></h2><p><?php esc_html_e( 'Inspect the intended invoice PDF. Check email attachments separately with an authorized test order; this preview does not prove delivery.', 'woocommerce-jetpack' ); ?></p></section>
</div>

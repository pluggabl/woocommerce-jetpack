<?php
/**
 * Starter setup view.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
WCJ_Invoice_Setup::authorize();
?>
<style>
	#wcj-invoice-setup{max-width:960px}
	#wcj-invoice-setup [hidden]{display:none!important}
	#wcj-invoice-setup .wcj-setup-section{background:#fff;border:1px solid #c3c4c7;border-radius:4px;margin:20px 0;padding:8px 24px 20px}
	#wcj-invoice-setup .wcj-setup-section h2{font-size:18px}
	#wcj-invoice-setup .wcj-setup-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
	#wcj-invoice-setup .wcj-setup-safety{border-left:4px solid #0073aa;padding:4px 16px;background:#f0f6fc}
	#wcj-invoice-setup details{margin:12px 0}
	#wcj-invoice-setup summary{cursor:pointer}
	#wcj-invoice-setup input:not([type=checkbox]),#wcj-invoice-setup textarea,#wcj-invoice-setup select{max-width:100%;box-sizing:border-box}
	#wcj-setup-status{margin:20px 0;padding:12px 16px;border:1px solid #c3c4c7;border-left:4px solid #0073aa;white-space:pre-line;background:#fff}
	#wcj-setup-status.is-error{border-left-color:#b32d2e}
	#wcj-invoice-setup td{overflow-wrap:anywhere}
	#wcj-setup-summary{table-layout:fixed}
	#wcj-invoice-setup a{color:#0073aa}
	#wcj-invoice-setup .button{border-color:#0073aa;color:#0073aa;max-width:100%;white-space:normal;overflow-wrap:anywhere;height:auto}
	#wcj-invoice-setup .button:hover{border-color:#005f8d;color:#005f8d}
	#wcj-invoice-setup .button-primary:not(:disabled){background:#0073aa;border-color:#0073aa;color:#fff}
	#wcj-invoice-setup .button-primary:not(:disabled):hover{background:#005f8d;border-color:#005f8d;color:#fff}
	#wcj-invoice-setup .button:focus,#wcj-invoice-setup a:focus,#wcj-invoice-setup summary:focus,#wcj-invoice-setup [tabindex="-1"]:focus{box-shadow:0 0 0 1px #fff,0 0 0 3px #0073aa;outline:2px solid transparent}
	@media(max-width:782px){#wcj-invoice-setup .wcj-setup-section{padding:8px 16px 20px}#wcj-invoice-setup .form-table td{padding:8px 0 18px}#wcj-invoice-setup .form-table th{padding-top:12px}#wcj-setup-summary th,#wcj-setup-summary td{padding:8px;font-size:12px}}
</style>
<div class="wrap" id="wcj-invoice-setup">
	<h1><?php esc_html_e( 'Branded invoice setup', 'woocommerce-jetpack' ); ?></h1>
	<p><?php esc_html_e( 'Add your business details, check a sample, then review the settings before you save and activate.', 'woocommerce-jetpack' ); ?></p>
	<p class="wcj-setup-actions"><a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-getting-started' ) ); ?>"><?php esc_html_e( 'Back to Getting Started', 'woocommerce-jetpack' ); ?></a><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=pdf_invoicing&section=pdf_invoicing' ) ); ?>"><?php esc_html_e( 'Open full invoice settings', 'woocommerce-jetpack' ); ?></a></p>
	<noscript>
		<style>
			#wcj-invoice-setup-form,
			#wcj-setup-status,
			#wcj-setup-undo { display: none !important; }
		</style>
		<p class="notice notice-warning"><?php esc_html_e( 'JavaScript is required to choose a logo, generate samples, test attachments, review settings changes, save and activate, or undo changes in this guide. Enable JavaScript to use the guide, or open full invoice settings to edit existing settings. Existing settings have not been changed.', 'woocommerce-jetpack' ); ?></p>
	</noscript>
	<div class="wcj-setup-safety">
		<p><?php esc_html_e( 'Samples use a stock design and synthetic data. Sample and attachment tests do not create orders, use invoice numbers, enable settings or send email. Existing custom templates cannot be replaced here.', 'woocommerce-jetpack' ); ?></p>
		<p><?php esc_html_e( 'A sample is not a production preview or proof of customer email delivery. All existing edition rights remain available in full invoice settings.', 'woocommerce-jetpack' ); ?></p>
		<details><summary><?php esc_html_e( 'Sample layout and language limits', 'woocommerce-jetpack' ); ?></summary><p><?php esc_html_e( 'The sample uses a fixed A4 layout and bundled font. Production headers, footers, fonts, margins and advanced styling can look different: this is not a pixel-identical production preview. Stock document labels remain in English; saved merchant templates are not translated.', 'woocommerce-jetpack' ); ?></p></details>
	</div>
	<div id="wcj-setup-status" role="status" aria-live="polite" aria-atomic="true" tabindex="-1"><?php esc_html_e( 'This draft has not been saved. Start with your business details below.', 'woocommerce-jetpack' ); ?></div>
	<form id="wcj-invoice-setup-form">
		<section class="wcj-setup-section" aria-labelledby="wcj-setup-business-heading">
		<h2 id="wcj-setup-business-heading"><?php esc_html_e( '1. Add your business details', 'woocommerce-jetpack' ); ?></h2>
		<table class="form-table" role="presentation"><tbody>
			<tr><th><label for="wcj-setup-business-name"><?php esc_html_e( 'Business name', 'woocommerce-jetpack' ); ?></label></th><td><input id="wcj-setup-business-name" name="draft[business_name]" class="regular-text" maxlength="120" required><p class="description"><?php esc_html_e( 'Plain text, at most 120 bytes.', 'woocommerce-jetpack' ); ?></p></td></tr>
			<tr><th><label for="wcj-setup-address"><?php esc_html_e( 'Business address', 'woocommerce-jetpack' ); ?></label></th><td><textarea id="wcj-setup-address" name="draft[business_address]" rows="4" class="large-text" maxlength="500" required></textarea><p class="description"><?php esc_html_e( 'Plain text, at most 500 bytes and seven lines.', 'woocommerce-jetpack' ); ?></p></td></tr>
			<tr><th><label for="wcj-setup-reference"><?php esc_html_e( 'Business reference (optional)', 'woocommerce-jetpack' ); ?></label></th><td><input id="wcj-setup-reference" name="draft[business_reference]" class="regular-text" maxlength="120"><p class="description"><?php esc_html_e( 'Your registration or tax reference, if appropriate. This guide does not assess legal or tax requirements.', 'woocommerce-jetpack' ); ?></p></td></tr>
			<tr><th><?php esc_html_e( 'Logo (optional)', 'woocommerce-jetpack' ); ?></th><td>
				<button type="button" class="button" id="wcj-setup-choose-logo"><?php esc_html_e( 'Choose logo', 'woocommerce-jetpack' ); ?></button>
				<button type="button" class="button" id="wcj-setup-remove-logo" hidden><?php esc_html_e( 'Remove logo', 'woocommerce-jetpack' ); ?></button>
				<span id="wcj-setup-logo-name"><?php esc_html_e( 'No logo selected.', 'woocommerce-jetpack' ); ?></span>
				<details><summary><?php esc_html_e( 'Advanced: Media Library attachment ID', 'woocommerce-jetpack' ); ?></summary><label for="wcj-setup-logo"><?php esc_html_e( 'Attachment ID (0 for no logo)', 'woocommerce-jetpack' ); ?></label> <input id="wcj-setup-logo" type="number" min="0" max="9999999999" name="draft[logo_attachment_id]" value="0"></details>
				<p class="description"><?php esc_html_e( 'Local PNG/JPEG only: up to 2 MB, four million pixels and 4096 pixels per side; aspect ratio 1:8 to 8:1. The logo fits within 35 × 15 mm without stretching. Remote/offloaded images are not fetched. Custom production margins can still affect placement.', 'woocommerce-jetpack' ); ?></p>
			</td></tr>
			<tr><th><label for="wcj-setup-accent"><?php esc_html_e( 'Brand color', 'woocommerce-jetpack' ); ?></label></th><td><input id="wcj-setup-accent" type="color" name="draft[accent_color]" value="#0073aa"></td></tr>
			<tr><th><label for="wcj-setup-footer"><?php esc_html_e( 'Footer note (optional)', 'woocommerce-jetpack' ); ?></label></th><td><textarea id="wcj-setup-footer" name="draft[footer_note]" rows="2" class="large-text" maxlength="240"></textarea><p class="description"><?php esc_html_e( 'Plain text, at most 240 bytes. HTML and shortcodes are not accepted.', 'woocommerce-jetpack' ); ?></p></td></tr>
		</tbody></table>
		</section>
		<section class="wcj-setup-section" aria-labelledby="wcj-setup-delivery-heading">
		<h2 id="wcj-setup-delivery-heading"><?php esc_html_e( '2. Choose when the document is used', 'woocommerce-jetpack' ); ?></h2>
		<table class="form-table" role="presentation"><tbody>
			<?php
			foreach ( array(
				'document_type' => array( __( 'Document', 'woocommerce-jetpack' ), WCJ_Invoice_Setup::documents() ),
				'operation'     => array( __( 'Generate', 'woocommerce-jetpack' ), WCJ_Invoice_Setup::operations() ),
				'email'         => array( __( 'Email attachment', 'woocommerce-jetpack' ), WCJ_Invoice_Setup::emails() ),
			) as $wcj_setup_key => $wcj_setup_field ) :
				?>
			<tr><th><label for="wcj-setup-<?php echo esc_attr( $wcj_setup_key ); ?>"><?php echo esc_html( $wcj_setup_field[0] ); ?></label></th><td><select id="wcj-setup-<?php echo esc_attr( $wcj_setup_key ); ?>" name="draft[<?php echo esc_attr( $wcj_setup_key ); ?>]">
														<?php
														foreach ( $wcj_setup_field[1] as $wcj_setup_value => $wcj_setup_label ) :
															?>
															<option value="<?php echo esc_attr( $wcj_setup_value ); ?>"><?php echo esc_html( $wcj_setup_label ); ?></option><?php endforeach; ?>
			</select></td></tr>
			<?php endforeach; ?>
		</tbody></table>
		<p><?php esc_html_e( 'Email must match the selected generation event. Existing gateway exclusions remain in force. No email is sent by any test in this guide.', 'woocommerce-jetpack' ); ?></p>
		</section>
		<section class="wcj-setup-section" aria-labelledby="wcj-setup-check-heading">
		<h2 id="wcj-setup-check-heading"><?php esc_html_e( '3. Check a sample and review', 'woocommerce-jetpack' ); ?></h2>
		<p><?php esc_html_e( 'Generate and open a sample, test attachment creation, then review the exact settings changes. These checks do not save or activate settings.', 'woocommerce-jetpack' ); ?></p>
		<p class="wcj-setup-actions"><button type="button" class="button" data-setup-action="sample"><?php esc_html_e( 'Generate sample PDF', 'woocommerce-jetpack' ); ?></button> <a id="wcj-setup-open" class="button" hidden target="_blank" rel="noopener"><?php esc_html_e( 'Open sample PDF', 'woocommerce-jetpack' ); ?></a> <a id="wcj-setup-download" class="button" hidden download="booster-starter-sample.pdf"><?php esc_html_e( 'Download sample PDF', 'woocommerce-jetpack' ); ?></a></p>
		<p><button type="button" class="button" data-setup-action="rehearse"><?php esc_html_e( 'Test attachment (no email sent)', 'woocommerce-jetpack' ); ?></button></p>
		<p><button type="button" class="button" data-setup-action="review"><?php esc_html_e( 'Review settings changes', 'woocommerce-jetpack' ); ?></button></p>
		</section>
		<section class="wcj-setup-section" id="wcj-setup-review" aria-labelledby="wcj-setup-review-heading" hidden>
			<h2 id="wcj-setup-review-heading" tabindex="-1"><?php esc_html_e( '4. Save and activate reviewed settings', 'woocommerce-jetpack' ); ?></h2>
			<p><?php esc_html_e( 'Activation enables PDF Invoicing for the chosen document and applies only the changes below. The starter design uses your plain business details. Numbering, taxes, gateway exclusions, advanced styling and unrelated settings remain unchanged. Attachment rehearsal proves local construction and cleanup only; production email delivery is unverified.', 'woocommerce-jetpack' ); ?></p>
			<table class="widefat striped" id="wcj-setup-summary"><thead><tr><th scope="col"><?php esc_html_e( 'Setting', 'woocommerce-jetpack' ); ?></th><th scope="col"><?php esc_html_e( 'Current', 'woocommerce-jetpack' ); ?></th><th scope="col"><?php esc_html_e( 'After activation', 'woocommerce-jetpack' ); ?></th></tr></thead><tbody></tbody></table>
			<details><summary><?php esc_html_e( 'Technical details: exact option changes and existing gateway exclusions', 'woocommerce-jetpack' ); ?></summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:400px;overflow:auto" id="wcj-setup-changes"></pre></details>
			<p id="wcj-setup-activation-help"><?php esc_html_e( 'Saving is available after a sample, an attachment test and your confirmation. If you edit this draft, repeat the checks and review.', 'woocommerce-jetpack' ); ?></p>
			<p><label><input type="checkbox" id="wcj-setup-confirm"> <?php esc_html_e( 'I reviewed the changes and want to activate these settings.', 'woocommerce-jetpack' ); ?></label></p><button type="button" class="button button-primary" data-setup-action="activate" aria-describedby="wcj-setup-activation-help" disabled><?php esc_html_e( 'Save and activate reviewed settings', 'woocommerce-jetpack' ); ?></button>
		</section>
	</form>
	<section class="wcj-setup-section" id="wcj-setup-undo" aria-labelledby="wcj-setup-undo-heading">
	<h2 id="wcj-setup-undo-heading"><?php esc_html_e( 'Undo this guide\'s settings changes', 'woocommerce-jetpack' ); ?></h2><p><?php esc_html_e( 'Only values still matching this guide\'s changes can be restored. Later edits are preserved and reported as conflicts. Issued invoices and orders are never removed.', 'woocommerce-jetpack' ); ?></p><button type="button" class="button" data-setup-action="undo"><?php esc_html_e( 'Undo this guide’s settings changes', 'woocommerce-jetpack' ); ?></button>
	</section>
</div>

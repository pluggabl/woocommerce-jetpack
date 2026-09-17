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
	#wcj-invoice-setup [hidden]{display:none!important}
	#wcj-invoice-setup td{overflow-wrap:anywhere}
	#wcj-setup-summary{table-layout:fixed}
	#wcj-invoice-setup a{color:#0073aa}
	#wcj-invoice-setup .button{border-color:#0073aa;color:#0073aa}
	#wcj-invoice-setup .button:hover{border-color:#005f8d;color:#005f8d}
	#wcj-invoice-setup .button-primary:not(:disabled){background:#0073aa;border-color:#0073aa;color:#fff}
	#wcj-invoice-setup .button-primary:not(:disabled):hover{background:#005f8d;border-color:#005f8d;color:#fff}
	#wcj-invoice-setup .button:focus,#wcj-invoice-setup a:focus{box-shadow:0 0 0 1px #fff,0 0 0 3px #0073aa;outline:2px solid transparent}
</style>
<div class="wrap" id="wcj-invoice-setup" style="max-width:900px">
	<h1><?php esc_html_e( 'Create your first branded document', 'woocommerce-jetpack' ); ?></h1>
	<p><?php esc_html_e( 'Business details → Sample PDF → Attachment rehearsal → Review and activate', 'woocommerce-jetpack' ); ?></p>
	<p><?php esc_html_e( 'This starter guide uses a stock design and synthetic data. Preview and rehearsal do not create orders, consume invoice numbers, enable settings or send email. Existing custom templates cannot be replaced here.', 'woocommerce-jetpack' ); ?></p>
	<p><?php esc_html_e( 'The sample uses a fixed A4 layout and bundled font. Production headers, footers, fonts, margins and advanced styling can look different: this is not a pixel-identical production preview. Stock document labels remain in English; saved merchant templates are not translated.', 'woocommerce-jetpack' ); ?></p>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=pdf_invoicing&section=pdf_invoicing' ) ); ?>"><?php esc_html_e( 'Advanced invoice settings — all existing edition rights remain available', 'woocommerce-jetpack' ); ?></a></p>
	<form id="wcj-invoice-setup-form">
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
		<p><button type="button" class="button" data-setup-action="sample"><?php esc_html_e( '1. Generate sample PDF', 'woocommerce-jetpack' ); ?></button> <a id="wcj-setup-open" class="button" hidden target="_blank" rel="noopener"><?php esc_html_e( 'Open sample PDF', 'woocommerce-jetpack' ); ?></a> <a id="wcj-setup-download" class="button" hidden download="booster-starter-sample.pdf"><?php esc_html_e( 'Download sample PDF', 'woocommerce-jetpack' ); ?></a></p>
		<p><button type="button" class="button" data-setup-action="rehearse"><?php esc_html_e( '2. Rehearse attachment construction', 'woocommerce-jetpack' ); ?></button></p>
		<p><button type="button" class="button" data-setup-action="review"><?php esc_html_e( '3. Review exact settings changes', 'woocommerce-jetpack' ); ?></button></p>
		<div id="wcj-setup-review" hidden>
			<h2><?php esc_html_e( 'Changes requiring your confirmation', 'woocommerce-jetpack' ); ?></h2>
			<p><?php esc_html_e( 'Activation enables PDF Invoicing for the chosen document and applies only the changes below. The starter design uses your plain business details. Numbering, taxes, gateway exclusions, advanced styling and unrelated settings remain unchanged. Attachment rehearsal proves local construction and cleanup only; production email delivery is unverified.', 'woocommerce-jetpack' ); ?></p>
			<table class="widefat striped" id="wcj-setup-summary"><thead><tr><th scope="col"><?php esc_html_e( 'Setting', 'woocommerce-jetpack' ); ?></th><th scope="col"><?php esc_html_e( 'Current', 'woocommerce-jetpack' ); ?></th><th scope="col"><?php esc_html_e( 'After activation', 'woocommerce-jetpack' ); ?></th></tr></thead><tbody></tbody></table>
			<details><summary><?php esc_html_e( 'Technical details: exact option changes and existing gateway exclusions', 'woocommerce-jetpack' ); ?></summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:400px;overflow:auto" id="wcj-setup-changes"></pre></details>
			<p><label><input type="checkbox" id="wcj-setup-confirm"> <?php esc_html_e( 'I reviewed the changes and want to activate these settings.', 'woocommerce-jetpack' ); ?></label></p><button type="button" class="button button-primary" data-setup-action="activate" disabled><?php esc_html_e( 'Activate reviewed settings', 'woocommerce-jetpack' ); ?></button>
		</div>
	</form>
	<div id="wcj-setup-status" role="status" aria-live="polite" style="margin:20px 0;padding:12px;border:1px solid #c3c4c7"></div>
	<h2><?php esc_html_e( 'Undo starter settings', 'woocommerce-jetpack' ); ?></h2><p><?php esc_html_e( 'Only values still matching this guide\'s changes can be restored. Later edits are preserved and reported as conflicts. Issued invoices and orders are never removed.', 'woocommerce-jetpack' ); ?></p><button type="button" class="button" data-setup-action="undo"><?php esc_html_e( 'Undo this guide’s settings changes', 'woocommerce-jetpack' ); ?></button>
</div>

<?php
/**
 * Read-only navigation between existing Booster task and settings screens.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;

final class WCJ_Task_Finder {
	/** The accepted job routes. No preset, nonce action or activation URL belongs here. */
	public static function tasks() {
		return array_merge( array(
			'pdf_invoicing' => array( __( 'Make a branded invoice', 'woocommerce-jetpack' ), __( 'Branded invoice', 'woocommerce-jetpack' ), 'invoice', 'WCJ_Invoice_Job', 'woocommerce-pdf-invoicing-and-packing-slips', __( 'PDF document logo seller branding packing slip billing', 'woocommerce-jetpack' ) ),
			'product_by_date' => array( __( 'Make one product available from a date', 'woocommerce-jetpack' ), __( 'Product available from date', 'woocommerce-jetpack' ), 'product-date', 'WCJ_Product_Date_Job', 'woocommerce-product-availability-by-date', __( 'product visibility available date schedule start monthly time stock country role login buttons', 'woocommerce-jetpack' ) ),
			'product_input_fields' => array( __( 'Ask for a message on one product (no charge)', 'woocommerce-jetpack' ), __( 'Product question', 'woocommerce-jetpack' ), 'product-question', 'WCJ_Product_Question_Job', 'woocommerce-product-input-fields', __( 'gift message ask product question note per item text', 'woocommerce-jetpack' ) ),
			'checkout_core_fields' => array( __( 'Change Order notes wording', 'woocommerce-jetpack' ), __( 'Order notes wording', 'woocommerce-jetpack' ), 'order-notes', 'WCJ_Order_Notes_Job', 'woocommerce-checkout-core-fields', __( 'rename checkout order notes label placeholder description existing field', 'woocommerce-jetpack' ) ),
			'checkout_custom_fields' => array( __( 'Ask once at checkout (no charge)', 'woocommerce-jetpack' ), __( 'Checkout question', 'woocommerce-jetpack' ), 'checkout', 'WCJ_Checkout_Job', 'woocommerce-checkout-custom-fields', __( 'checkout field gift message personal note ask buyer question', 'woocommerce-jetpack' ) ),
			'product_addons' => array( __( 'Charge for gift wrap (adds a price)', 'woocommerce-jetpack' ), __( 'Optional extra', 'woocommerce-jetpack' ), 'product-extra', 'WCJ_Product_Extra_Job', 'woocommerce-product-addons', __( 'gift message paid wrapping extra add-on option product price', 'woocommerce-jetpack' ) ),
			'payment_gateways_min_max' => array( __( 'Limit one payment method by cart amount', 'woocommerce-jetpack' ), __( 'Payment method amount limits', 'woocommerce-jetpack' ), 'gateway-amount', 'WCJ_Gateway_Amount_Job', 'woocommerce-payment-gateways-min-max-amounts', __( 'payment method gateway minimum maximum cart amount limit bank transfer', 'woocommerce-jetpack' ) ),
			'payment_gateways_fees' => array( __( 'Set a fee or discount for a payment method', 'woocommerce-jetpack' ), __( 'Payment method fee', 'woocommerce-jetpack' ), 'gateway-fee', 'WCJ_Gateway_Fee_Job', 'woocommerce-payment-gateways-fees-and-discounts', __( 'gateway surcharge charge checkout total diagnose why rule explanations', 'woocommerce-jetpack' ) ),
			'returns' => array( __( 'Set a return window', 'woocommerce-jetpack' ), __( 'Return requests', 'woocommerce-jetpack' ), 'returns', 'WCJ_Returns_Job', 'woocommerce-return-requests', __( 'returns policy days customer request notifications eligibility diagnose why', 'woocommerce-jetpack' ) ),
			'payment_gateways_by_country' => array( __( 'Limit a payment method by country', 'woocommerce-jetpack' ), __( 'Payment method by country', 'woocommerce-jetpack' ), 'gateway-country', 'WCJ_Gateway_Country_Job', 'woocommerce-payment-gateways-by-country-or-state', __( 'hide allow gateway bank transfer billing location countries address diagnose why', 'woocommerce-jetpack' ) ),
			'product_tabs' => array( __( 'Add a shared product tab', 'woocommerce-jetpack' ), __( 'Add a tab to your product pages', 'woocommerce-jetpack' ), 'product-tab', 'WCJ_Product_Tab_Job', 'woocommerce-custom-product-tabs', __( 'tabs information description care instructions catalog categories', 'woocommerce-jetpack' ) ),
			'currency' => array( __( 'Change the symbol for your store currency', 'woocommerce-jetpack' ), __( 'Store currency symbol', 'woocommerce-jetpack' ), 'currency-symbol', 'WCJ_Currency_Symbol_Job', 'woocommerce-all-currencies', __( 'currency symbol display wording custom USD EUR tax conversion', 'woocommerce-jetpack' ) ),
			'sale_flash' => array( __( 'Change the default sale badge text', 'woocommerce-jetpack' ), __( 'Default sale badge text', 'woocommerce-jetpack' ), 'sale-badge', 'WCJ_Sale_Badge_Job', 'woocommerce-sale-flash', __( 'sale badge label flash wording promotion default text', 'woocommerce-jetpack' ) ),
			'my_account' => array( __( 'Rename Orders in My Account', 'woocommerce-jetpack' ), __( 'Orders menu label and heading', 'woocommerce-jetpack' ), 'account-orders-label', 'WCJ_Account_Orders_Label_Job', 'woocommerce-my-account', __( 'account orders menu label title heading purchases wording', 'woocommerce-jetpack' ) ),
			'global_discount' => array( __( 'Adjust an existing percentage discount', 'woocommerce-jetpack' ), __( 'Existing percentage discount', 'woocommerce-jetpack' ), 'global-discount', 'WCJ_Global_Discount_Job', 'woocommerce-shop-global-discount', __( 'pricing percentage discount existing group charged price first applicable', 'woocommerce-jetpack' ) ),
			'shipping_description' => array( __( 'Edit a shipping description', 'woocommerce-jetpack' ), __( 'Shipping description', 'woocommerce-jetpack' ), 'shipping-description', 'WCJ_Shipping_Description_Job', 'woocommerce-shipping-descriptions', __( 'shipping delivery wording plain text method instance description', 'woocommerce-jetpack' ) ),
			'shipping_options' => array( __( 'Hide other rates when Free shipping is offered', 'woocommerce-jetpack' ), __( 'Hide other shipping rates when Free shipping is offered', 'woocommerce-jetpack' ), 'shipping-hide', 'WCJ_Shipping_Hide_Job', 'woocommerce-shipping-options', __( 'shipping delivery flat rate local pickup diagnose why', 'woocommerce-jetpack' ) ),
		), WCJ_Label_Job::catalogue(), WCJ_Catalog_Job::catalogue() );
	}

	/** Use the package identity, never a request parameter or a licence inference. */
	private static function edition() {
		$file = defined( 'WCJ_PLUGIN_FILE' ) ? basename( WCJ_PLUGIN_FILE ) : '';
		return 'booster-elite-for-woocommerce.php' === $file ? 'Elite' : ( 'booster-plus-for-woocommerce.php' === $file ? 'Plus' : 'Free' );
	}

	/** Eligibility for an admitted task. Dashboard menu policy is separately enforced in render(). */
	public static function allowed( $id ) {
		if ( ! self::module_allowed( $id ) ) { return false; }
		if ( 'pdf_invoicing' === $id ) {
			// One combined editor renders these native layers and must not expose a restricted layer.
			foreach ( array( 'pdf_invoicing_templates', 'pdf_invoicing_header', 'pdf_invoicing_footer', 'pdf_invoicing_emails', 'pdf_invoicing_styling', 'pdf_invoicing_numbering' ) as $part ) {
				if ( ! self::module_allowed( $part ) ) { return false; }
			}
		}
		return true;
	}

	private static function module_allowed( $id ) {
		return current_user_can( 'manage_woocommerce' )
			&& ( ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( $id ) )
			&& ( ! wcj_is_module_enabled( 'modules_by_user_roles' ) || wcj_check_modules_by_user_roles( $id ) );
	}

	/** The external guide is never a form submit or an in-place navigation. */
	private static function link( $task, $fragment, $label ) {
		if ( 'currency-symbol' === $task[2] ) { return '<a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-all-currencies/#booster-v10-s18-symbol-task">' . esc_html( $label . ' (new tab)' ) . '</a>'; }
		if ( 'sale-badge' === $task[2] ) { return '<a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-sale-flash/#booster-v10-s19-sale-badge-task">' . esc_html( $label . ' (new tab)' ) . '</a>'; }
		if ( 'account-orders-label' === $task[2] ) { return '<a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-my-account/#booster-v10-s20-account-orders-label-task">' . esc_html( $label . ' (new tab)' ) . '</a>'; }
		if ( 'global-discount' === $task[2] ) { return '<a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-shop-global-discount/#booster-v10-s17-global-discount">' . esc_html( $label . ' (new tab)' ) . '</a>'; }
		if ( 'shipping-description' === $task[2] ) { return '<a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-shipping-descriptions/#booster-v10-s16-shipping-description">' . esc_html( $label . ' (new tab)' ) . '</a>'; }
		if ( 'gateway-amount' === $task[2] ) { return '<a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-payment-gateways-min-max-amounts/#booster-v10-s15-gateway-amount">' . esc_html( $label . ' (new tab)' ) . '</a>'; }
		if ( 'order-notes' === $task[2] ) { return '<a target="_blank" rel="noopener noreferrer" href="https://booster.io/docs/woocommerce-checkout-core-fields/#booster-v10-s14-order-notes">' . esc_html( $label ) . ' <span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'woocommerce-jetpack' ) . '</span></a>'; }
		return '<a href="' . esc_url( 'https://booster.io/docs/' . $task[4] . '/#booster-v10-' . $fragment ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'woocommerce-jetpack' ) . '</span></a>';
	}

	/** Compact point-of-use support for the accepted workflow, not an assertion that every expert option is documented. */
	public static function help( $id, $inside_guided_task = false ) {
		$tasks = self::tasks();
		if ( ! isset( $tasks[ $id ] ) || ! self::allowed( $id ) ) { return; }
		$t = $tasks[ $id ];
		if ( 'checkout_core_fields' === $id ) { echo '<p>' . self::link( $t, '', __( 'Order notes wording: setup, limits and recovery', 'woocommerce-jetpack' ) ) . '</p>'; return; }
		if ( ! $inside_guided_task && ! is_subclass_of( $t[3], 'WCJ_Label_Job' ) ) {
			echo '<p class="description">' . esc_html__( 'Full settings open separately; your draft stays here. Return through guided setup to load their saved changes.', 'woocommerce-jetpack' ) . '</p>';
		}
		?>
		<div class="wcj-task-help" data-wcj-guide="<?php echo esc_attr( $id ); ?>">
			<p><?php echo self::link( $t, 'setup', sprintf( __( 'Setup guide: %s', 'woocommerce-jetpack' ), $t[1] ) ); ?> <small><?php esc_html_e( '(opens in a new tab)', 'woocommerce-jetpack' ); ?></small></p>
			<details><summary><?php esc_html_e( 'Testing, limits and recovery', 'woocommerce-jetpack' ); ?></summary>
				<ul><?php foreach ( array( 'edition' => __( 'What this edition includes', 'woocommerce-jetpack' ), 'limits' => __( 'Exceptions and full settings', 'woocommerce-jetpack' ), 'test' => __( 'Check the result', 'woocommerce-jetpack' ), 'recover' => __( 'Recover a previous change', 'woocommerce-jetpack' ) ) as $key => $label ) { echo '<li>' . self::link( $t, $key, $label ) . '</li>'; } ?></ul>
			</details>
		</div>
		<?php
	}

	/** Read-only return path. Opening a new tab preserves even nonstandard unsaved native controls. */
	private static function guided_link( $t ) {
		return '<a class="button wcj-open-guided" target="_blank" rel="noopener noreferrer" href="' . esc_url( admin_url( 'admin.php?page=wcj-' . $t[2] . '-job' ) ) . '">' . esc_html( sprintf( __( 'Guided setup: %s (opens in a new tab)', 'woocommerce-jetpack' ), $t[0] ) ) . '</a>';
	}
	/** Read-only contextual help for the two cross-module settings screens. */
	private static function general_role_help( $section ) {
		if ( ! in_array( $section, array( 'general', 'modules_by_user_roles' ), true ) || ! self::allowed( $section ) ) { return; }
		$groups = array(
			array( 'general', 'booster-v10-s28-general-shortcodes', __( 'Shortcodes and order privacy', 'woocommerce-jetpack' ), __( 'Text Widgets can run all registered shortcodes, not just Booster shortcodes, when enabled here. Disabling Booster shortcodes stops their normal registration while General is enabled; turning General off restores that registration. Force order shortcodes skips the usual customer/order checks and can expose order information. Do not use it as a general missing-data fix on public pages. Its saved setting is read separately from the General switch; turn the option off itself to restore those checks.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-shortcodes' ),
			array( 'general', 'booster-v10-s28-general-ip', __( 'IP detection', 'woocommerce-jetpack' ), __( 'Choose the order of request fields used by Booster IP detection. The first nonempty field wins; the order displayed in settings and the fallback for a never-saved list differ. Overwrite WooCommerce IP Detection can replace the real-IP request field with this result when General is enabled. Check your proxy setup with your host before changing it. An IP value is not proof of customer identity.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-ip' ),
			array( 'general', 'booster-v10-s28-general-advanced', __( 'Cart, sessions and date controls', 'woocommerce-jetpack' ), __( 'Recalculate Cart Totals adds a frontend cart recalculation when General is enabled; it is not a guaranteed currency fix. Session Type chooses standard PHP or WooCommerce sessions for Booster. Read and Close applies to standard PHP sessions on supported PHP versions. Datepicker and timepicker switches control their styles and scripts, not booking rules. Keep required assets available and check the affected form after a change.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-advanced' ),
			array( 'general', 'booster-v10-s28-general-paypal', __( 'Per-product PayPal email', 'woocommerce-jetpack' ), __( 'With General and PayPal Email per Product enabled, a PayPal Email field is added to product editing. The existing gateway handling examines the cart and stops at its first nonempty product email. This is not split payment support and does not establish that a payment reached that address. Check the installed PayPal gateway in a test environment before using this setting.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-paypal' ),
			array( 'general', 'booster-v10-s28-general-sessions', __( 'WooCommerce session durations', 'woocommerce-jetpack' ), __( 'Enable this section and General before these durations are used by the WooCommerce session filters. Enter seconds: the declared defaults are 169200 for Session Expiring and 172800 for Session Expiration. These are session durations, not a promise that every cart is retained or that customers are logged out at that time. Record the previous values and test the affected shopping session.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-sessions' ),
			array( 'general', 'booster-v10-s28-general-role-changer', __( 'Booster role testing', 'woocommerce-jetpack' ), __( 'The role changer is a Plus and Elite feature; its enable control is locked in Free. General, the changer switch and an allowed current role are required. The default allowed roles are Administrator and Shop manager. The toolbar selection changes the role used by participating Booster features for the current user, not their WordPress role or capabilities. Modules by User Roles uses the actual first WordPress role instead. Return to the original selection after testing.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-role-changer' ),
			array( 'general', 'booster-v10-s28-general-php', __( 'PHP resource limits', 'woocommerce-jetpack' ), __( 'Memory is entered in megabytes and time in seconds. A saved 0 leaves the corresponding server setting alone; it does not set a fixed 256 MB or 30-second default. With General enabled, nonzero values request a PHP limit change, which hosting restrictions may prevent. Record the current values and involve your host before changing them.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-php' ),
			array( 'general', 'booster-v10-s28-general-custom-roles', __( 'Create or remove WordPress roles', 'woocommerce-jetpack' ), __( 'The existing Add/Manage Custom Roles tool requires General to be enabled. Creating a role uses an ID, a name and capabilities copied from a selected role. Deleting a role removes that role; it is not a preview or a temporary Booster role selection. Check affected accounts and keep a recovery administrator and backup before changing roles. This help does not run either action.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-custom-roles' ),
			array( 'general', 'booster-v10-s28-general-hide-products', __( 'Hide products from selected listings', 'woocommerce-jetpack' ), __( 'This setting is available in Elite only. With General enabled, selected products are excluded from the main shop, product category and product tag queries and from the supported products-shortcode query. An empty selection adds no exclusions. This does not protect direct product URLs or prevent purchasing through every other path. Use product access controls for access restrictions, and check the actual listings after saving.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-hide-products' ),
			array( 'general', 'booster-v10-s28-general-recovery', __( 'Save, check and recover', 'woocommerce-jetpack' ), __( 'These are existing full settings, not a guided Save/Restore task. Record the previous values before saving and check the specific affected feature. General off is not a universal undo for saved options, product emails, roles or prior effects. Reset Settings writes the declared defaults for this module; it is not a backup restore and does not remove all related product or user data. Guide links open separately without submitting this form.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-general-tools/#booster-v10-s28-general-recovery' ),
			array( 'modules_by_user_roles', 'booster-v10-s28-roles-rules', __( 'Choose modules for each actual role', 'woocommerce-jetpack' ), __( 'Choose a role tab. A nonempty Enable Modules list permits only those modules for that role; an empty list adds no inclusion restriction. Disable Modules excludes its selected modules and wins if a module appears in both lists. The first actual WordPress role is used, or Guest when there is none. These restrictions do not activate a module, unlock a paid feature or grant WordPress capabilities. Module choices reflect the installed edition.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-modules-by-user-roles/#booster-v10-s28-roles-rules' ),
			array( 'modules_by_user_roles', 'booster-v10-s28-roles-recovery', __( 'Check access and recover carefully', 'woocommerce-jetpack' ), __( 'Record both lists and keep a separate administrator recovery route before enabling or changing these restrictions. Check a fresh request as the intended user, including a guest where relevant. This is not a universal security boundary: the native module-enabled check skips these role restrictions for REST requests, and other permissions still apply. Clearing both lists removes this role restriction, not other access rules. Reset Settings replaces the declared role lists with their defaults; there is no guided Undo here.', 'woocommerce-jetpack' ), 'https://booster.io/docs/woocommerce-booster-modules-by-user-roles/#booster-v10-s28-roles-recovery' ),
		);
		echo '<details class="wcj-task-help" data-wcj-native-help="' . esc_attr( $section ) . '"><summary>' . esc_html__( 'Settings guidance, checks and recovery', 'woocommerce-jetpack' ) . '</summary>';
		foreach ( $groups as $group ) {
			if ( $section !== $group[0] || ( 'booster-v10-s28-general-hide-products' === $group[1] && 'Elite' !== self::edition() ) ) { continue; }
			echo '<section data-wcj-help-group="' . esc_attr( $group[1] ) . '"><h3>' . esc_html( $group[2] ) . '</h3><p>' . esc_html( $group[3] ) . '</p><p><a target="_blank" rel="noopener noreferrer" href="' . esc_url( $group[4] ) . '">' . esc_html__( 'Detailed guide (opens in a new tab)', 'woocommerce-jetpack' ) . '</a></p></section>';
		}
		echo '</details>';
	}
	public static function native_help( $section ) {
		self::general_role_help( $section );
		if ( class_exists( 'WCJ_Pricing_Discount_Groups' ) ) { WCJ_Pricing_Discount_Groups::render( $section ); }
		if ( class_exists( 'WCJ_Currency_Tax_Groups' ) ) { WCJ_Currency_Tax_Groups::render( $section ); }
		if ( class_exists( 'WCJ_Promotions_Groups' ) ) { WCJ_Promotions_Groups::render( $section ); }
		if ( class_exists( 'WCJ_Order_Account_Groups' ) ) { WCJ_Order_Account_Groups::render( $section ); }
		if ( class_exists( 'WCJ_Shipping_Delivery_Groups' ) ) { WCJ_Shipping_Delivery_Groups::render( $section ); }
		if ( class_exists( 'WCJ_Payment_Choice_Groups' ) ) { WCJ_Payment_Choice_Groups::render( $section ); }
		if ( class_exists( 'WCJ_Cart_Checkout_Groups' ) ) { WCJ_Cart_Checkout_Groups::render( $section ); }
		if ( class_exists( 'WCJ_Product_Question_Groups' ) ) { WCJ_Product_Question_Groups::render( $section ); }
		if ( class_exists( 'WCJ_Product_Availability_Groups' ) ) { WCJ_Product_Availability_Groups::render( $section ); }
		$aliases = array( 'pdf_invoicing_templates', 'pdf_invoicing_header', 'pdf_invoicing_footer', 'pdf_invoicing_styling', 'pdf_invoicing_numbering', 'pdf_invoicing_emails' );
		$id = in_array( $section, $aliases, true ) ? 'pdf_invoicing' : $section;
		$tasks = self::tasks();
		if ( ! isset( $tasks[ $id ] ) || ! self::allowed( $id ) || ( $section !== $id && ! self::allowed( $section ) ) ) { return; }
		echo '<p>' . self::guided_link( $tasks[ $id ] ) . ' ' . esc_html__( 'Unsaved edits stay in this full editor. Save here first to see your changes in guided setup.', 'woocommerce-jetpack' ) . '</p>';
		self::help( $id, true );
		WCJ_Catalog_Job::native_help( $id );
	}
	public static function shipping_link() {
		$t = self::tasks()['shipping_options'];
		return self::allowed( 'shipping_options' ) ? ' ' . self::guided_link( $t ) . ' ' . self::link( $t, 'setup', __( 'Setup guide: Hide other shipping rates (opens in a new tab)', 'woocommerce-jetpack' ) ) : '';
	}

	private static function boundary( $id ) {
		if ( isset( WCJ_Label_Job::catalogue()[ $id ] ) ) { return WCJ_Label_Job::summary( $id ); }
		if ( isset( WCJ_Catalog_Job::catalogue()[ $id ] ) ) { return WCJ_Catalog_Job::summary( $id ); }
		$is_elite = 'Elite' === self::edition();
		switch ( $id ) {
			case 'checkout_core_fields': return __( 'Only the existing Order notes label, placeholder and description. Blank uses incoming wording; no activation, requiredness or answer changes.', 'woocommerce-jetpack' );
			case 'pdf_invoicing': return __( 'Start with a sample. Existing custom templates stay in full settings.', 'woocommerce-jetpack' );
			case 'product_by_date': return __( 'One product override and direct date, never global activation. Monthly schedules remain stored. Free allows one enabled Date product; paid editions retain their native limits.', 'woocommerce-jetpack' );
			case 'product_input_fields': return __( 'One answer per product cart item and order line, not once per order. No extra price.' , 'woocommerce-jetpack' );
			case 'checkout_custom_fields': return __( 'One answer per order at checkout, not per product. No extra price. The task shows available slots and checkout compatibility.', 'woocommerce-jetpack' );
			case 'product_addons': return __( 'Configure a priced option on products, not a checkout question. Check the available slot and its scope before saving.', 'woocommerce-jetpack' );
			case 'payment_gateways_min_max': return __( 'Only one method minimum and maximum, never activation or global basis. Free and Plus shortcut BACS only; Plus native rights remain. Saved configuration is not availability proof.', 'woocommerce-jetpack' );
			case 'payment_gateways_fees': return __( 'Check the method and resulting total. A fee is not an availability rule.', 'woocommerce-jetpack' );
			case 'returns': return $is_elite ? __( 'Set the window or inspect a supported item rule. Approval does not issue a refund.', 'woocommerce-jetpack' ) : __( 'Set the storewide window. Item-specific policy rules are not included in this edition.', 'woocommerce-jetpack' );
			case 'payment_gateways_by_country': return $is_elite ? __( 'Check a supported payment method against a billing country. Other eligibility rules still apply.', 'woocommerce-jetpack' ) : __( 'Guided country changes are for bank transfer. Existing full settings keep their own edition limits.', 'woocommerce-jetpack' );
			case 'product_tabs': return $is_elite ? __( 'Use a supported shared tab. Advanced and per-product overrides stay in full settings.', 'woocommerce-jetpack' ) : __( 'One guided shared tab, for all products or chosen categories. Other native settings are unchanged.', 'woocommerce-jetpack' );
			case 'shipping_description': return __( 'Edit the exact plain wording for one supported shipping method or instance. Method-wide wording applies to all matching rates. No price, availability or activation changes.', 'woocommerce-jetpack' );
			case 'currency': return __( 'One plain symbol for the saved store currency. Free ordinary symbols stay locked; an existing matching custom currency can be eligible. Plus keeps native symbol rights. No conversion, tax or activation changes.', 'woocommerce-jetpack' );
			case 'sale_flash': return __( 'One plain default badge label, up to 64 UTF-8 bytes. No activation, hide, scope or price changes. Advanced HTML stays in full settings; saved text does not prove that a badge appears.', 'woocommerce-jetpack' );
			case 'my_account': return __( 'Rename only the Orders menu label and Orders heading. Blank uses supplied wording. Other labels, addresses, access, order actions and activation stay unchanged.', 'woocommerce-jetpack' );
			case 'global_discount': return $is_elite ? __( 'Change one eligible existing percentage group, with a review before Save and Restore. First applicable group wins; the example is not a final price quote.', 'woocommerce-jetpack' ) : __( 'Group 1 percentage shortcut only, with a review before Save and Restore. Plus keeps all its native groups. First applicable group wins; the example is not a final price quote.', 'woocommerce-jetpack' );
			default: return $is_elite ? __( 'Choose what to keep when a native Free shipping rate is offered. A zero-cost flat rate does not count.', 'woocommerce-jetpack' ) : __( 'Use the native keep mode shown by the task. Choosing a different guided keep mode is not included in this edition.', 'woocommerce-jetpack' );
		}
	}

	/** Positive user-bound saved evidence only; a store receipt is not another person's draft. */
	private static function saved_route( $t ) {
		$class = $t[3];
		if ( 'WCJ_Currency_Symbol_Job' === $class ) { return $class::resume(); }
		if ( 'WCJ_Sale_Badge_Job' === $class ) { return $class::resume(); }
		if ( 'WCJ_Account_Orders_Label_Job' === $class ) { return $class::resume(); }
		if ( in_array( $class, array( 'WCJ_Product_Question_Job', 'WCJ_Product_Date_Job' ), true ) ) { return ''; }
		if ( is_subclass_of( $class, 'WCJ_Label_Job' ) || is_subclass_of( $class, 'WCJ_Catalog_Job' ) ) { return $class::resume(); }
		$r = get_option( $class::RECEIPT, null );
		if ( ! is_array( $r ) || empty( $r['id'] ) || empty( $r['entries'] ) || empty( $r['user'] ) || get_current_user_id() !== (int) $r['user'] || ! isset( $r['status'] ) || 'applied' !== $r['status'] ) { return ''; }
		if ( in_array( $t[2], array( 'checkout', 'product-extra' ), true ) ) {
			return isset( $r['slot'] ) && absint( $r['slot'] ) ? $class::url( absint( $r['slot'] ) ) : '';
		}
		if ( 'gateway-fee' === $t[2] ) {
			return isset( $r['gateway'] ) && is_string( $r['gateway'] ) && sanitize_key( $r['gateway'] ) === $r['gateway'] ? $class::url( $r['gateway'] ) : '';
		}
		return in_array( $t[2], array( 'invoice', 'returns' ), true ) ? $class::url() : '';
	}

	/** Browse existing workspaces only. This is not another task search or a permission system. */
	private static function browse_families() {
		$families = array(
			'WCJ_Document_Workspace' => array( 'wcj-family-documents', __( 'Order documents', 'woocommerce-jetpack' ) ),
			'WCJ_Communications_Workspace' => array( 'wcj-family-communications', __( 'Customer communications', 'woocommerce-jetpack' ) ),
			'WCJ_Catalog_Admin_Workspace' => array( 'wcj-family-catalog-admin', __( 'Catalog and admin tools', 'woocommerce-jetpack' ) ),
			'WCJ_Data_Exchange_Workspace' => array( 'wcj-family-data-exchange', __( 'Data exchange', 'woocommerce-jetpack' ) ),
			'WCJ_Diagnose_Customize_Workspace' => array( 'wcj-family-diagnose-customize', __( 'Diagnose and customize', 'woocommerce-jetpack' ) ),
			'WCJ_Specialized_Selling_Workspace' => array( 'wcj-family-specialized-selling', __( 'Specialized selling', 'woocommerce-jetpack' ) ),
		);
		$available = array();
		foreach ( $families as $class => $family ) {
			if ( ! class_exists( $class ) ) { continue; }
			if ( 'WCJ_Data_Exchange_Workspace' === $class ) {
				$modules = array( 'export', 'products_xml', 'reports', 'track_users' );
			} elseif ( 'WCJ_Diagnose_Customize_Workspace' === $class ) {
				$modules = $class::MODULES;
			} else {
				$modules = array_column( $class::groups(), 'module' );
			}
			foreach ( $modules as $module ) {
				if ( $class::allowed( $module ) ) { $available[ $class ] = $family; break; }
			}
		}
		return $available;
	}

	/** Find is a GET, limited to local navigation. No recents, telemetry, writes or actions on load. */
	public static function render() {
		if ( function_exists( 'wcj_is_guided_admin_experience_enabled' ) && ! wcj_is_guided_admin_experience_enabled() ) { return; }
		if ( ! current_user_can( 'manage_woocommerce' ) || ( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) && ! current_user_can( 'manage_options' ) ) ) { echo '<p>' . esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ) . '</p>'; return; }
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only local search.
		$q = isset( $_GET['wcj_find_task'] ) && is_string( $_GET['wcj_find_task'] ) ? sanitize_text_field( wp_unslash( $_GET['wcj_find_task'] ) ) : '';
		// A byte slice can bisect a multibyte character when mbstring is missing. PCRE Unicode is already required here.
		$valid_query = 1 === preg_match( '/^.{0,100}/us', $q, $bounded );
		$q = $valid_query ? $bounded[0] : '';
		$words = preg_split( '/[^\p{L}\p{N}]+/u', strtolower( $q ), -1, PREG_SPLIT_NO_EMPTY );
		$found = array(); $saved = array(); $recent = array();
		$families = self::browse_families();
		$visited = get_user_meta( get_current_user_id(), 'wcj_recent_modules', true );
		$visited = is_array( $visited ) ? $visited : array();
		foreach ( self::tasks() as $id => $t ) {
			if ( ! class_exists( $t[3] ) ) { continue; }
			$match = $valid_query && is_array( $words ); $haystack = implode( ' ', array( $t[0], $t[1], $id, $t[5] ) );
			foreach ( (array) $words as $word ) {
				if ( ! in_array( $word, array( 'a', 'an', 'the', 'at', 'to', 'for', 'my', 'i', 'want', 'how', 'do', 'is', 'can' ), true ) && 1 !== preg_match( '/' . preg_quote( $word, '/' ) . '/iu', $haystack ) ) { $match = false; break; }
			}
			if ( $match ) { $found[ $id ] = $t; }
			if ( self::allowed( $id ) ) {
				$url = self::saved_route( $t );
				if ( $url ) { $saved[ $t[1] ] = $url; }
				if ( in_array( $id, $visited, true ) ) {
					$class = $t[3];
					$recent[ $t[1] ] = 'pdf_invoicing' === $id ? $class::native_url( $id ) : $class::native_url();
				}
			}
		}
		?>
		<section class="wcj-task-finder" id="wcj-find-task" aria-labelledby="wcj-find-heading">
			<h2 id="wcj-find-heading"><?php esc_html_e( 'Find a task', 'woocommerce-jetpack' ); ?></h2>
			<p><?php esc_html_e( 'Find a guided task, check what it changes, or go straight to full settings. Looking here does not turn anything on.', 'woocommerce-jetpack' ); ?></p>
			<p id="wcj-find-scope"><?php esc_html_e( 'Searches guided task shortcuts. Access depends on your permissions.', 'woocommerce-jetpack' ); ?><?php if ( $families ) { echo ' ' . esc_html__( 'Other workspaces you can access are listed below.', 'woocommerce-jetpack' ); } ?></p>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php#wcj-find-task' ) ); ?>" role="search"><input type="hidden" name="page" value="wcj-dashboard">
				<label for="wcj-find-query"><?php esc_html_e( 'What do you want to do?', 'woocommerce-jetpack' ); ?></label>
				<div><input type="search" id="wcj-find-query" aria-describedby="wcj-find-scope" name="wcj_find_task" maxlength="100" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'e.g. add a gift message', 'woocommerce-jetpack' ); ?>"> <button class="button button-primary" type="submit"><?php esc_html_e( 'Find', 'woocommerce-jetpack' ); ?></button></div>
			</form>
			<?php if ( $saved && '' === $q ) : ?><h3><?php esc_html_e( 'Continue from a saved change', 'woocommerce-jetpack' ); ?></h3><p><?php esc_html_e( 'Your last saved change for each task, not an unsaved draft.', 'woocommerce-jetpack' ); ?></p><ul><?php foreach ( $saved as $name => $url ) { echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a></li>'; } ?></ul><?php endif; ?>
			<?php if ( $recent ) : ?><details><summary><?php esc_html_e( 'Recently opened module settings', 'woocommerce-jetpack' ); ?></summary><p><?php esc_html_e( 'These modules are in your existing recent list. A visit does not mean a change was saved.', 'woocommerce-jetpack' ); ?></p><ul><?php foreach ( $recent as $name => $url ) { echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a></li>'; } ?></ul></details><?php endif; ?>
			<h3 aria-live="polite"><?php echo $q ? esc_html( sprintf( _n( '%d matching task', '%d matching tasks', count( $found ), 'woocommerce-jetpack' ), count( $found ) ) ) : esc_html__( 'Guided tasks', 'woocommerce-jetpack' ); ?></h3>
			<?php if ( ! $found ) : ?>
				<p><?php echo esc_html( $families ? __( 'No guided task matches. Browse the sections below.', 'woocommerce-jetpack' ) : __( 'No guided task matches.', 'woocommerce-jetpack' ) ); ?></p>
				<?php if ( $families ) : ?><ul class="wcj-family-jumps" aria-label="<?php esc_attr_e( 'Available workspaces', 'woocommerce-jetpack' ); ?>">
					<?php foreach ( $families as $family ) { echo '<li><a href="#' . esc_attr( $family[0] ) . '">' . esc_html( $family[1] ) . '</a></li>'; } ?>
				</ul><?php endif; ?>
			<?php endif; ?>
			<ul class="wcj-task-list">
			<?php foreach ( $found as $id => $t ) :
				$class = $t[3]; ?>
				<li><h4><?php echo esc_html( $t[0] ); ?></h4>
				<?php if ( ! self::allowed( $id ) ) : ?><p><?php esc_html_e( 'Ask an administrator for access.', 'woocommerce-jetpack' ); ?></p>
				<?php else : ?>
					<p><strong><?php echo esc_html( $t[1] ); ?></strong> &middot; <?php echo wcj_is_module_enabled( $id ) ? esc_html__( 'Module on', 'woocommerce-jetpack' ) : esc_html__( 'Module off', 'woocommerce-jetpack' ); ?></p>
					<p><?php echo esc_html( self::edition() . ': ' . self::boundary( $id ) ); ?></p>
					<div class="wcj-task-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-' . $t[2] . '-job' ) ); ?>"><?php esc_html_e( 'Open task', 'woocommerce-jetpack' ); ?><span class="screen-reader-text">: <?php echo esc_html( $t[1] ); ?></span></a>
					<?php if ( ! is_subclass_of( $class, 'WCJ_Catalog_Job' ) || $class::available() ) : ?><a href="<?php echo esc_url( 'pdf_invoicing' === $id ? $class::native_url( $id ) : $class::native_url() ); ?>"><?php esc_html_e( 'Open full settings', 'woocommerce-jetpack' ); ?><span class="screen-reader-text">: <?php echo esc_html( $t[1] ); ?></span></a><?php endif; ?>
					<?php echo self::link( $t, 'setup', sprintf( __( 'Setup guide: %s', 'woocommerce-jetpack' ), $t[1] ) ); ?>
					</div>
					<?php if ( 'payment_gateways_fees' === $id ) : ?>
						<p><?php if ( 'Elite' === self::edition() && class_exists( 'WCJ_Rule_Explanations_Admin' ) ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-rule-explanations' ) ); ?>"><?php esc_html_e( 'Diagnose a rule with Rule explanations', 'woocommerce-jetpack' ); ?></a>
						<?php else : esc_html_e( 'Rule explanations is not included in this edition. Use the guide to check a test total.', 'woocommerce-jetpack' ); endif; ?></p>
					<?php endif; ?>
				<?php endif; ?></li>
			<?php endforeach; ?></ul>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins' ) ); ?>"><?php esc_html_e( 'Browse all modules', 'woocommerce-jetpack' ); ?></a> &middot; <a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins#wcj_search_modules' ) ); ?>"><?php esc_html_e( 'Search settings', 'woocommerce-jetpack' ); ?></a></p>
			<?php if ( class_exists( 'WCJ_Product_Availability_Groups' ) ) { WCJ_Product_Availability_Groups::render(); } ?>
			<?php if ( class_exists( 'WCJ_Cart_Checkout_Groups' ) ) { WCJ_Cart_Checkout_Groups::entry(); } ?>
			<?php if ( class_exists( 'WCJ_Payment_Choice_Groups' ) ) { WCJ_Payment_Choice_Groups::entry(); } ?>
			<?php if ( class_exists( 'WCJ_Shipping_Delivery_Groups' ) ) { WCJ_Shipping_Delivery_Groups::entry(); } ?>
			<?php if ( class_exists( 'WCJ_Pricing_Discount_Groups' ) ) { WCJ_Pricing_Discount_Groups::entry(); } ?>
			<?php if ( class_exists( 'WCJ_Currency_Tax_Groups' ) ) { WCJ_Currency_Tax_Groups::entry(); } ?>
			<?php if ( class_exists( 'WCJ_Promotions_Groups' ) ) { WCJ_Promotions_Groups::entry(); } ?>
			<?php if ( class_exists( 'WCJ_Order_Account_Groups' ) ) { WCJ_Order_Account_Groups::entry(); } ?>
			<?php foreach ( $families as $class => $family ) : ?>
				<div id="<?php echo esc_attr( $family[0] ); ?>" tabindex="-1" aria-label="<?php echo esc_attr( $family[1] ); ?>">
					<?php $class::entry(); ?>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
	}
}


add_action( 'admin_enqueue_scripts', static function () {
	$screen = get_current_screen();
	if ( $screen && false !== strpos( $screen->id, 'wcj-' ) ) {
		wp_enqueue_style( 'wcj-task-finder', wcj_plugin_url() . '/assets/css/admin/wcj-task-finder.css', array(), '1.3' );
	}
} );

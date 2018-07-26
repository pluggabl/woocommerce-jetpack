/**
 * wcj-checkout-fees.
 *
 * @version 3.8.0
 * @since   3.8.0
 */

jQuery('body').on('change',wcj_checkout_fees.checkout_fields,function(){
	jQuery('body').trigger('update_checkout');
});

/**
 * wcj-checkout-fees.
 *
 * @version 3.7.1
 * @since   3.7.1
 */

jQuery('body').on('change',wcj_checkout_fees.checkout_fields,function(){
	jQuery('body').trigger('update_checkout');
});

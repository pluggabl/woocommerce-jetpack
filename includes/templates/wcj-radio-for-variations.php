<?php
/**
 * Variable product add to cart - radio inputs
 *
 * @version 6.0.0
 * @since   2.4.8
 * @author  Pluggabl LLC.
 * @package WooCommerce/Templates
 */

global $product;
$attribute_keys = array_keys( $attributes );

?>

<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
	<p class="stock out-of-stock"><?php esc_html_e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?></p>
<?php else : ?>
	<table class="" cellspacing="0">
		<tbody>
		<tr>
			<th colspan="2">
				<?php $attribute_labels = array_map( 'wc_attribute_label', array_keys( $attributes ) ); ?>
				<?php echo esc_html( implode( ' X ', $attribute_labels ) ); ?>
				<?php echo '<a style="float:right" class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>'; ?>
			</th>
		</tr>
		<?php foreach ( $available_variations as $variation ) : ?>
			<tr>
				<?php wcj_variation_radio_button( $product, $variation ); ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
	foreach ( $product->get_attributes() as $attribute_name => $options ) {
		echo wp_kses_post( '<input type="hidden" name="attribute_' . $attribute_name . '" value="" />' );
	}
	?>
<?php endif; ?>

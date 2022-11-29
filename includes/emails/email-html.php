<?php
/**
 * Booster for WooCommerce - Custom Email HTML Template
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes/email
 */

?>
<h1>Email</h1>
<p>
<table>
<tbody>
	<tr><th>Order Date</th><td>[wcj_order_date]</td></tr>
	<tr><th>Order Nr.</th><td>[wcj_order_number]</td></tr>
</tbody>
</table>
</p>
<p>
<table>
<tbody>
	<tr><th>Buyer</th></tr>
	<tr><td>[wcj_order_billing_address]</td></tr>
</tbody>
</table>
</p>
<p>
[wcj_order_items_table
	columns="item_number|item_name|item_quantity|line_total_tax_excl"
	columns_titles="|Product|Qty|Total"
	columns_styles="width:5%;|width:75%;|width:5%;|width:15%;text-align:right;"]
<table>
<tbody>
	<tr><th>Total (excl. TAX)</th><td>[wcj_order_total_excl_tax]</td></tr>
	<tr><th>Taxes</th><td>[wcj_order_total_tax hide_if_zero="no"]</td></tr>
	<tr><th>Order Total</th><td>[wcj_order_total]</td></tr>
</tbody>
</table>
</p>
<p>Payment method: [wcj_order_payment_method]</p>

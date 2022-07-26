/**
 * Admin-script.
 *
 * @version 5.6.2
 *
 * @package Booster_For_WooCommerce/includes/JS
 */

var acc = document.getElementsByClassName( "wcj-accordion" );
var i;
var acc_length = acc.length;
for (i = 0; i < acc_length; i++) {
	acc[i].addEventListener(
		"click",
		function() {

			this.classList.toggle( "active" );
			var panel = this.nextElementSibling;
			if (panel.style.display === "block") {
				panel.style.display = "none";
			} else {
				panel.style.display = "block";
			}
		}
	);
}

/**
 * Wcj-New Design.
 *
 * @version 5.6.2
 * @since   5.4.2
 * @package Booster_For_WooCommerce/includes/JS
 **/

jQuery( document ).ready(
	function () {
		let circleBadge = document.querySelector( '.circle-badge' ),
		subCircles      = document.querySelectorAll( '.subCircles > div' );
		if (null !== circleBadge) {
			circleBadge.addEventListener( 'click', showCircles );

			function showCircles() {
				subCircles.forEach(
					circle => {circle.classList.toggle( "show" );}
				)
			};
		}
	}
);

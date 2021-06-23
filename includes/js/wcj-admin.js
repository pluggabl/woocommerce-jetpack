/**
 * wcj-admin.
 *
 * @version 5.4.2-dev
 * @since   5.4.2-dev
 */

jQuery(document).ready(function () {
    let circleBadge = document.querySelector('.circle-badge'),
    subCircles = document.querySelectorAll('.subCircles > div');

    circleBadge.addEventListener('click', showCircles);

    function showCircles() {
        subCircles.forEach(circle => {
            circle.classList.toggle("show");
        })
    };
});
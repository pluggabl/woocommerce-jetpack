/**
 * wcj-admin.
 *
 * @version 5.4.9
 * @since   5.4.2
 */

jQuery(document).ready(function () {
    let circleBadge = document.querySelector('.circle-badge'),
    subCircles = document.querySelectorAll('.subCircles > div');
    if (null !== circleBadge) {
    circleBadge.addEventListener('click', showCircles);

    function showCircles() {
        subCircles.forEach(circle => {
            circle.classList.toggle("show");
        })
    };
  }   
});
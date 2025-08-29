jQuery(document).ready(function($) {
    $('.wcj-ara-faq-details').each(function() {
        $(this).on('toggle', function() {
            if (this.open) {
                $(this).find('.wcj-ara-faq-answer').slideDown(200);
            } else {
                $(this).find('.wcj-ara-faq-answer').slideUp(200);
            }
        });
    });

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        $('.wcj-ara-faq-answer').css('transition', 'none');
    }
});

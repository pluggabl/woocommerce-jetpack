/**
 * Booster for WooCommerce - Module Filters & Getting Started Hub JavaScript
 *
 * Part of Session C: Navigation (P6, P7)
 *
 * @version 7.9.0
 */

(function($) {
    'use strict';

    /**
     * Getting Started Hub functionality
     */
    var WCJHub = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '#wcj-hub-dismiss', this.dismissHub);
        },

        dismissHub: function(e) {
            e.preventDefault();

            var $hub = $('#wcj-getting-started-hub');

            // Send AJAX request to dismiss
            $.ajax({
                url: wcj_hub_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcj_dismiss_getting_started',
                    nonce: wcj_hub_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove the hub
                        $hub.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                },
                error: function() {
                    // Even on error, hide the hub for this session
                    $hub.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        }
    };

    /**
     * Module Filters functionality
     */
    var WCJModuleFilters = {
        init: function() {
            var container = document.getElementById('wcj-module-filters');
            var buttons = container.querySelectorAll('.wcj-filter-btn');
            var cards = document.querySelectorAll('.wcj-catalog-item[data-module]');
            var status = document.getElementById('wcj-catalog-status');
            var labels = typeof wcj_hub_params === 'undefined' ? {} : wcj_hub_params;
            function filterModules(filter) {
                var visible = 0;
                Array.prototype.forEach.call(cards, function(card) {
                    // The complete catalog card is the visibility unit. Keep its form values intact.
                    var show = filter !== 'recommended' || card.getAttribute('data-recommended') === 'yes';
                    card.hidden = !show;
                    if (show) {
                        visible++;
                    }
                });
                Array.prototype.forEach.call(buttons, function(button) {
                    var selected = button.getAttribute('data-filter') === filter;
                    button.classList.toggle('active', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
                if (status) {
                    status.textContent = visible === 1 ? (labels.one || '') : (visible ? (labels.count || '').replace('%d', visible) : (labels.empty || ''));
                }
            }
            Array.prototype.forEach.call(buttons, function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    filterModules(button.getAttribute('data-filter'));
                });
            });
            filterModules('all');
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize Hub functionality
        if ($('#wcj-getting-started-hub').length) {
            WCJHub.init();
        }

        // Initialize Module Filters functionality
        if ($('#wcj-module-filters').length) {
            WCJModuleFilters.init();
        }
    });

})(jQuery);

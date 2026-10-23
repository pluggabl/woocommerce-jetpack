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
            this.bindEvents();
            this.updateFilterCounts();
        },

        bindEvents: function() {
            $(document).on('click', '.wcj-filter-btn', this.handleFilterClick.bind(this));
        },

        handleFilterClick: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var filter = $button.data('filter');

            // Update active state
            $('.wcj-filter-btn').removeClass('active');
            $button.addClass('active');

            // Apply filter
            this.filterModules(filter);
        },

        filterModules: function(filter) {
            var $moduleCards = $('.wcj-plugins-sing-acc-box-head[data-module]');
            var visibleCount = 0;

            $moduleCards.each(function() {
                var $card = $(this);
                var shouldShow = false;

                switch (filter) {
                    case 'all':
                        shouldShow = true;
                        break;
                    case 'recommended':
                        shouldShow = $card.attr('data-recommended') === 'yes';
                        break;
                    case 'active':
                        shouldShow = $card.attr('data-active') === 'yes';
                        break;
                    case 'recent':
                        shouldShow = $card.attr('data-recent') === 'yes';
                        break;
                    default:
                        shouldShow = true;
                }

                if (shouldShow) {
                    $card.removeClass('wcj-filtered-out').show();
                    visibleCount++;
                } else {
                    $card.addClass('wcj-filtered-out').hide();
                }
            });

            // Handle empty state
            this.handleEmptyState(filter, visibleCount);
        },

        handleEmptyState: function(filter, visibleCount) {
            var $container = $('.wcj-plugins-right-listing');
            var $emptyState = $container.find('.wcj-module-empty-state');

            if (visibleCount === 0 && filter !== 'all') {
                var message = this.getEmptyStateMessage(filter);
                
                if ($emptyState.length === 0) {
                    // Agar message box nahi hai, toh naya add karein
                    $container.append('<div class="wcj-module-empty-state"><p>' + message + '</p></div>');
                } else {
                    // Agar message box pehle se hai (par galat message ke saath), toh sirf text update karein
                    $emptyState.find('p').text(message);
                }
            } else {
                // Agar modules mil gaye hain, toh empty state hata dein
                $emptyState.remove();
            }
        },

        getEmptyStateMessage: function(filter) {
            var messages = {
                'recommended': 'No recommended modules to display.',
                'active': 'No active modules found. Enable some modules to see them here.',
                'recent': 'No recently used modules. Visit a module\'s settings to add it here.'
            };
            return messages[filter] || 'No modules to display.';
        },

        updateFilterCounts: function() {
            var $moduleCards = $('.wcj-plugins-sing-acc-box-head[data-module]');

            var counts = {
                'all': $moduleCards.length,
                'recommended': $moduleCards.filter('[data-recommended="yes"]').length,
                'active': $moduleCards.filter('[data-active="yes"]').length,
                'recent': $moduleCards.filter('[data-recent="yes"]').length
            };

            // Update button counts if you want to display them
            $('.wcj-filter-btn').each(function() {
                var $btn = $(this);
                var filter = $btn.data('filter');
                var count = counts[filter];

                // Optionally add count badges
                var $count = $btn.find('.count');
                if ($count.length === 0 && count !== undefined) {
                    // Uncomment the following line to show counts
                    // $btn.append('<span class="count">' + count + '</span>');
                } else {
                    $count.text(count);
                }
            });
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

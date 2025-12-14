/**
 * New admin js
 *
 * @version 7.3.1
 */

jQuery( document ).ready(
  function ($) {
    // D4: Success Toast after settings save
    (function() {
      var urlParams = new URLSearchParams( window.location.search );
      if ( urlParams.get( 'success' ) === '1' ) {
        // Create toast element
        var toast = $( '<div class="wcj-success-toast">' +
          '<span class="dashicons dashicons-yes-alt"></span>' +
          '<span>Settings saved successfully</span>' +
          '<button type="button" class="wcj-success-toast-close" aria-label="Dismiss">' +
            '<span class="dashicons dashicons-no-alt"></span>' +
          '</button>' +
        '</div>' );

        $( 'body' ).append( toast );

        // Auto-dismiss after 5 seconds
        var dismissTimeout = setTimeout( function() {
          dismissToast( toast );
        }, 5000 );

        // Manual dismiss on click
        toast.find( '.wcj-success-toast-close' ).on( 'click', function() {
          clearTimeout( dismissTimeout );
          dismissToast( toast );
        } );

        function dismissToast( toastEl ) {
          toastEl.addClass( 'wcj-toast-hiding' );
          setTimeout( function() {
            toastEl.remove();
          }, 300 );
        }

        // Remove success param from URL without reload (clean URL)
        if ( window.history.replaceState ) {
          urlParams.delete( 'success' );
          var newUrl = window.location.pathname + '?' + urlParams.toString() + window.location.hash;
          window.history.replaceState( {}, '', newUrl );
        }
      }
    })();

    // GTM/GA4 click tracking using stable data attributes
    $(document).on('click keydown', '.wcj-btn-chip', function(e){
      var isKeyboard = (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '));
      if (e.type === 'click' || isKeyboard) {
        var $chips = $(this).closest('#wcj-promo-chips');
        var eventName = $(this).attr('data-gtm') || $(this).attr('data-ga') || 'promo_click';
        var payload = {
          event: eventName,
          placement: $(this).attr('data-placement') || '',
          page: $chips.attr('data-page') || '',
          section: $chips.attr('data-section') || ''
        };
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(payload);
        if (isKeyboard) {
          // Space key should activate link navigation
          if (e.key === ' ') {
            e.preventDefault();
            var href = $(this).attr('href');
            if (href) { window.location.href = href; }
          }
        }
      }
    });

    // Educator link tracking
    $(document).on('click', '.wcj-educator-link', function(){
      var context = $(this).data('context') || '';
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ event: 'educator_link', context: context });
    });

    // Sidebar click tracking
    $(document).on('click', '.wcj-menubar a, #wcj-sidebar a', function(){
      var $wrap = $(this).closest('.wcj-menubar');
      if (!$wrap.length) { $wrap = $(this).closest('#wcj-sidebar'); }
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        event: $wrap.data('gtm') || 'booster_click_sidebar',
        page: $wrap.data('page') || '',
        section: $wrap.data('section') || ''
      });
    });

    $( '.wcj-setting-color-picker' ).wpColorPicker();
    $( 'select.wcj_setting_multiselect' ).select2();
    
    $( 'body' ).find("select.wc-product-search, select.wc-customer-search").select2({
        minimumInputLength: 3,
        tags: [],
        ajax: {
            url: wcj_admin_ajax_obj.ajax_url,
            dataType: 'json',
            delay: 250,
            data: function( params ) {
                var nonce = wcj_admin_ajax_obj.search_products_nonce;
                if( $( this ).data( 'action' ) == "woocommerce_json_search_customers" ){
                    nonce = wcj_admin_ajax_obj.search_customers_nonce;
                }
                return {
                    term         : params.term,
                    action       : $( this ).data( 'action' ) || 'woocommerce_json_search_products_and_variations',
                    security     : nonce,
                };
            },
            processResults: function( data ) {
                var terms = [];
                if ( data ) {
                    $.each( data, function( id, text ) {
                        terms.push( { id: id, text: text } );
                    });
                }
                return {
                    results: terms
                };
            },
        }
    });

    $( '.wcj-plugins' ).find(".wcj_select_search_input").select2({
    });

    $( '.wcj_close_deshboard_modal' ).on(
      'click',
      function(){
        var targetclass = $( this ).attr( 'data-targetclass' );
        $( '.' + targetclass ).hide();
      }
    );

    // TABS
    $( '.wcj-tab-menu li a' ).on(
      'click',
      function(e){
         e.preventDefault();
         var target = $( this ).attr( 'data-rel' );
         $( '.wcj-tab-menu li a' ).removeClass( 'active' );
         $( this ).addClass( 'active' );
         $( "#" + target ).fadeIn( 'slow' ).siblings( ".wcj-tab-box" ).hide();
         $( 'select.wcj_setting_multiselect' ).select2();
         $( '.wcj_setting_active_tab' ).val( target );
         $( 'body' ).find("select.wc-product-search, select.wc-customer-search").select2({
            minimumInputLength: 3,
            tags: [],
            ajax: {
                url: wcj_admin_ajax_obj.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function( params ) {
                    var nonce = wcj_admin_ajax_obj.search_products_nonce;
                    if( $( this ).data( 'action' ) == "woocommerce_json_search_customers" ){
                        nonce = wcj_admin_ajax_obj.search_customers_nonce;
                    }
                    return {
                        term         : params.term,
                        action       : $( this ).data( 'action' ) || 'woocommerce_json_search_products_and_variations',
                        security     : nonce,
                    };
                },
                processResults: function( data ) {
                    var terms = [];
                    if ( data ) {
                        $.each( data, function( id, text ) {
                            terms.push( { id: id, text: text } );
                        });
                    }
                    return {
                        results: terms
                    };
                },
            }
        });
      }
    );
    
    $( '.wcj_enable_plugin' ).on(
      'click',
      function(){
         var id = $( this ).attr( 'data-id' );
        if ( $( this ).attr( 'data-type' ) == "enable" ) {
          $( "#enable_" + id ).removeClass( 'wcj-disable' );
          $( "#disable_" + id ).addClass( 'wcj-disable' );
          $( "#" + id ).val( 'yes' );
        } else {
          $( "#disable_" + id ).removeClass( 'wcj-disable' );
          $( "#enable_" + id ).addClass( 'wcj-disable' );
          $( "#" + id ).val( 'no' );
        }
      }
    );

    $( '#wcj_search_modules_btn' ).on(
      'click',
      function(){
        if ( $( '#wcj_search_modules' ).val() != "" ) {
          var search_url       = $( this ).attr( 'return_url' ) + $( '#wcj_search_modules' ).val();
          window.location.href = search_url;
        } else {
          $( '#wcj_search_modules' ).focus();
        }
      }
    );

    $( 'body' ).on(
      'change',
      '.wcj_setting_checkbox_key',
      function(e){
        var target_key = $( this ).attr( 'data-rel_id' );
        if ($( this ).prop( 'checked' ) == true) {
          $( "#" + target_key ).val( 'yes' );
          $("input[type='hidden'][name='"+target_key+"']").val('yes');
        } else {
          console.log('else : '+target_key);
          $( "#" + target_key ).val( 'no' );
          $("input[type='hidden'][name='"+target_key+"']").val('no');
        }
      }
    );

    // ===== fixed-header ====
    $( window ).scroll(
      function(){
        if ($( window ).scrollTop() >= 100) {
          $( '.wcj-new-header' ).addClass( 'fixed-header' );
        } else {
          $( '.wcj-new-header' ).removeClass( 'fixed-header' );
        }
      }
    );

    // closing accordion form
    $( 'body' ).on(
      'click',
      '.wcj_closing_accordion_form',
      function(e){
        var new_icon = $( this ).attr( 'data-img_path' )+"down-arw2.png";
        $(this).attr('src', new_icon);
        $(this).removeClass('wcj_closing_accordion_form');
        $(this).addClass('wcj_opneing_accordion_form');
        $('.wcj-plugins-sing-acc-sub-cnt').hide();
      }
    );

    // open accordion form
    $( 'body' ).on(
      'click',
      '.wcj_opneing_accordion_form',
      function(e){
        var new_icon = $( this ).attr( 'data-img_path' )+"up-arw-new.png";
        $(this).attr('src', new_icon);
        $(this).removeClass('wcj_opneing_accordion_form');
        $(this).addClass('wcj_closing_accordion_form');
        $('.wcj-plugins-sing-acc-sub-cnt').show();
      }
    );
  }
);

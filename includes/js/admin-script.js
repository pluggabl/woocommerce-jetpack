/**
 * New admin js
 *
 * @version 7.0.0
 */

jQuery( document ).ready(
  function ($) {

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

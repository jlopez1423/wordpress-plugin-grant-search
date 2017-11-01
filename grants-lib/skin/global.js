jQuery( document ).ready( function() {

    jQuery( '#searchButton' ).on( "click", function( e )
    {
        e.preventDefault();
        //Need to obtain data from input fields, will send these via AJAX
        //var keyword     = jQuery( "#keyword" ).val(),
        var keyword = jQuery("#org").select2("val"),
        term        = jQuery( "#term_options" ).val(),
        app_date    = jQuery( "#app-date" ).val(),
        org         = jQuery( "#org" ).val(),
        grant       = jQuery( "#grnt-amt" ).val(),
        search_word = jQuery( "#search-text" ).val();

        console.log(grant);

        jQuery( '.result2' ).html( '<span class="loading-grants">Searching... Please wait...</span>' );
        jQuery.ajax( {
            type : "get",
            url: '/wp-admin/admin-ajax.php',
            dataType: 'html',
            data :  { action: 'search_grants', keyword: keyword, term: term, app_date: app_date, org: org, grant: grant, search_word: search_word},
            success: function(response) {
                console.log( 'Success' );
                jQuery( '.result2' ).html( response );
                console.log("success");
            },
            error: function() {
                alert( 'There was a problem. Please refresh the page and try again.' );
            },
            complete: function()
            {
                console.log( 'Rquest is complete' );
            }
        } );


    } );



    jQuery( '#resetBtn' ).on( "click", function(e)
    {
        jQuery( "#keyword" ).val( '' );
        jQuery( '#term_options' ).find('option:first').attr('selected', 'selected');
        jQuery( '#app-date' ).find('option:first').attr('selected', 'selected');
        jQuery( '#org' ).find('option:first').attr('selected', 'selected');
        jQuery( '#grnt-amt' ).find('option:first').attr('selected', 'selected');
        jQuery( '.result-wrapper' ).html( '' );
        jQuery( '#grant-next-page' ).html( '' );
        jQuery( '.result2' ).html( '' );
    });

    jQuery( 'body' ).on( 'click', 'a.grant_next_page', function() {
        var keyword       = jQuery( "#keyword" ).val(),
        term        = jQuery( "#term_options" ).val(),
        app_date    = jQuery( "#app-date" ).val(),
        org         = jQuery( "#org" ).val(),
        grant       = jQuery( "#grnt-amt" ).val();

        jQuery( '#grant-next-page' ).html( '<span class="loading-grants">Searching... Please wait...</span>' );
        // console.log(page.info);
        jQuery.ajax( {
            type : "get",
            url: '/wp-admin/admin-ajax.php',
            dataType: 'html',
            data :  { action: 'search_grants', keyword: keyword, term: term, app_date: app_date, org: org, grant: grant, pagenum: jQuery(this).data('pagenum')},
            success: function(response) {
                jQuery( '#grant-next-page' ).replaceWith( response );
            },
            error: function() {
                alert( 'There was a problem. Please refresh the page and try again.' );
            }
        } );
        return false;
    } );

    //Place for approval date
    if( phpParams && phpParams.grantsApproval ) {

        var approval = JSON.parse( phpParams.grantsApproval );

        jQuery( '.search-form select[name="app-date"]' ).html( '<option value="0">Year Approved</option>' );

        //Remove repeats and only include the year
        for(var j = 0; j < approval.length; j++)
        {
            approval[j] = approval[j].substring(0,4);
        }
        var uniqueVals = [];
        jQuery.each(approval, function(i, el){
            if(jQuery.inArray(el, uniqueVals) === -1) uniqueVals.push(el);
        });

        for( var i = 0; i < uniqueVals.length; i++ ) {
            jQuery( '.search-form select[name="app-date"]' ).append( '<option value="' + uniqueVals[i] + '">' + uniqueVals[i] + '</option>' );
        }
    }


    // dynamically populate the areas dropdown filter
    if( phpParams && phpParams.grantsOrganizaiton ) {

        var orgs = JSON.parse( phpParams.grantsOrganizaiton );
        // console.log(orgs);
        jQuery( '.search-form select[name="org"]' ).html( '<option value="0">All Organizations</option>' );

        for( var i = 0; i < orgs.length; i++ ) {
            jQuery( '.search-form select[name="org"]' ).append( '<option value="' + orgs[i] + '">' + orgs[i] + '</option>' );
        }

    }

    // dynamically populate the years dropdown filter
    if( phpParams && phpParams.grantsPeriod ) {

        var term = JSON.parse( phpParams.grantsPeriod );
        jQuery( '.search-form select[name="term_options"]' ).html( '<option value="0">Term</option>' );

        //Eliminate all the duplicates
        var uniqueVals2 = [];
        jQuery.each(term, function(i, el){
            if(jQuery.inArray(el, uniqueVals2) === -1) uniqueVals2.push(el);
        });

        uniqueVals2.sort(function(a, b){ return a-b});

        // console.log(uniqueVals2);

        for( var i = 0; i < term.length; i++ ) {
            jQuery( '.search-form select[name="term_options"]' ).append( '<option value="' + uniqueVals2[i] + '">' + uniqueVals2[i] + ' Months </option>' );
        }
    }


} );

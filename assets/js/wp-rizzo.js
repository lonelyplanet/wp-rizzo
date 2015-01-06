(function($) {
    "use strict";

    function wp_rizzo_fetch_content()
    {
        var data = {
                action: "wp-rizzo-fetch-endpoints",
            }, ajax = $.post(
                window.ajaxurl,
                data,
                function( data, textstatus, jqxhr ) {
                    console.log( data, textstatus, jqxhr );
                },
                "json"
            );
        return ajax;
    }

    var last_fetch = $("#wp-rizzo-last-fetch"),
        fetch_time = parseInt( last_fetch.data("fetch-time"), 10 );

    if ( fetch_time > 0 ) {
        last_fetch.text( new Date( fetch_time * 1000 ).toLocaleString() );
    } else {
        last_fetch.text( "Never" );
    }

    $("#wp-rizzo-fetch-button").on("click.wp-rizzo", function( e ) {
        var button = $( e.currentTarget );
        button.addClass("wait");
        wp_rizzo_fetch_content().then( function( data ) {
            console.log( data );
            button.removeClass("wait");
            last_fetch.text( new Date( data.data.time * 1000 ).toLocaleString() );
        });
    });

})(jQuery);

(function( $ ) {
	'use strict';
        $( ".ep-dismissible" ).click(function(){
            var notice_name = $( this ).attr( 'id' );
            var data        = {'action': 'ep_dismissible_notice','notice_name': notice_name,'nonce':ep_ajax_object.nonce};
            $.post(
                ep_ajax_object.ajax_url,
                data,
                function(response) {

                });
        });

})( jQuery );
( function ( $ ) {
    $( document ).ready( function () {
        const adminSearchBar = $( '#bwfan-admin-bar-search' );
        adminSearchBar.on( 'keyup', function () {
            let value = $( this ).val();
            if ( value ) {
                fetch(
                    bwfanProObj.siteUrl+'/wp-json/' + bwfanProObj.apiNamespace + '/contacts?search=' +
                    value +
                    '&limit=5&offset=0',
                    {
                        credentials: 'include',
                        headers: {
                            'content-type': 'application/json',
                            'X-WP-Nonce': wpApiSettings.nonce,
                        },
                    }
                )
                    .then( ( response ) => response.json() )
                    .then( function ( result ) {
                        if ( result.code == 200 ) {
                            let res = result.result;
                            let template = '';
                            if ( res.length > 0 ) {
                                res.map( function ( contact ) {
                                    if (
                                        contact.hasOwnProperty( 'email' ) &&
                                        contact.hasOwnProperty( 'id' )
                                    ) {
                                        template +=
                                            '<a class="bwf-contact-suggestion" target="_blank" href="'+bwfanProObj.siteUrl+'/wp-admin/admin.php?page=autonami&path=/contact/' +
                                            contact.id +
                                            '">' +
                                            contact.email +
                                            '</a>';
                                    }
                                } );
                            } else {
                                template = '<span class="label">No contacts found</span>';
                            }
                            $( '#bwf-contact-list-suggestion-list' ).html(
                                template
                            );
                        }
                    } );
            }
        } );
    } );
} )( jQuery );

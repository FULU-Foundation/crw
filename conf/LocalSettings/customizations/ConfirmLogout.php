<?php

# Ask for confirmation before logging out.
$wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
    $out->addInlineScript( <<<JS
document.addEventListener( 'click', function ( e ) {
    var link = e.target.closest( '#pt-logout a[data-mw="interface"]' );
    if ( !link ) {
        return;
    }
    e.preventDefault();
    e.stopPropagation();
    mw.loader.using( 'oojs-ui-windows' ).done( function () {
        OO.ui.confirm( "Are you sure you'd like to log out?" ).done( function ( confirmed ) {
            if ( confirmed ) {
                mw.hook( 'skin.logout' ).fire( link.href );
            }
        } );
    } );
}, true );
JS
    );
};
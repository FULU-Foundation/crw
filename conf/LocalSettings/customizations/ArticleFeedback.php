<?php

use MediaWiki\Html\Html;

# Adds a "Give feedback" button on article pages
$wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
    $title = $out->getTitle();
    $context = $skin->getContext();

    if (
        !$title ||
        !$title->inNamespace( NS_MAIN ) ||
        !$title->exists() ||
        $context->getActionName() !== 'view' ||
        $context->getRequest()->getCheck( 'diff' )
    ) {
        return;
    }

    $out->addModules( [ 'mediawiki.api' ] );
    $pageName = Html::encodeJsVar( $title->getPrefixedText() );

    $discordGuildId = Html::encodeJsVar( (string)getenv( 'ARTICLE_FEEDBACK_DISCORD_GUILD_ID' ) ?: null );
    $discordChannelId = Html::encodeJsVar( (string)getenv( 'ARTICLE_FEEDBACK_DISCORD_CHANNEL_ID' ) ?: null );

    $out->addInlineScript( <<<JS
(window.RLQ = window.RLQ || []).push( function () {
    var discordGuildId = {$discordGuildId};
    var discordChannelId = {$discordChannelId};
    var cratePromise = null;

    function openFeedbackChannel() {
        if ( !discordGuildId || !discordChannelId ) {
            return;
        }
        if ( !cratePromise ) {
            cratePromise = new Promise( function ( resolve, reject ) {
                var script = document.createElement( 'script' );
                script.src = 'https://cdn.jsdelivr.net/npm/@widgetbot/crate@3';
                script.onload = function () {
                    resolve( new window.Crate( {
                        server: discordGuildId,
                        channel: discordChannelId
                    } ) );
                };
                script.onerror = reject;
                document.body.appendChild( script );
            } );
        }
        cratePromise.then( function ( crate ) {
            crate.toggle( true );
        } ).catch( function () {
        } );
    }

    var button = document.createElement( 'button' );
    button.id = 'crw-article-feedback-button';
    button.textContent = '💬 Give feedback';
    button.style.cssText = 'position:fixed;right:1em;bottom:1em;z-index:100;' +
        'padding:0.5em 1em;background:#36c;color:#fff;border:0;border-radius:2px;' +
        'font-size:1em;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.3);';
    button.addEventListener( 'click', function () {
        mw.loader.using( [ 'oojs-ui-windows', 'mediawiki.api' ] ).done( function () {
            var message = new OO.ui.HtmlSnippet(
                '<p>What feedback would you like to share about this article?</p>' +
                '<p style="font-size:0.85em;color:#54595d;margin-top:0.5em;">' +
                'Feedback is posted to our Discord community channel (also crossposted to our Zulip community), where our editors can see it and reply.' +
                '</p>'
            );
            OO.ui.prompt( message, {
                textInput: { multiline: true, rows: 4 }
            } ).done( function ( text ) {
                if ( !text ) {
                    return;
                }
                new mw.Api().postWithToken( 'csrf', {
                    action: 'articlefeedback',
                    title: {$pageName},
                    text: text
                } ).done( function () {
                    mw.notify( 'Thanks - your feedback has been sent!' );
                    openFeedbackChannel();
                } ).fail( function ( code, data ) {
                    mw.notify( 'Sorry, that didn\\'t go through. Please try again later.', { type: 'error' } );
                } );
            } );
        } );
    } );
    document.body.appendChild( button );
} );
JS
    );
};

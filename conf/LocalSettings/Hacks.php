<?php

// Dirty hack required for leftover SMW schema
$wgContentHandlers['smw/schema'] = JsonContentHandler::class;

# OpenGraph Tags Injection (Make this dynamic at some point - Jake)
$wgHooks['BeforePageDisplay'][] = function ( OutputPage &$out, Skin &$skin ) {
    $title = $out->getTitle();

    $ogTitle = htmlspecialchars( $title->getText() );
    $ogDescription = "Welcome to the Consumer Rights Wiki – exposing the overlooked exploitation of modern consumers. Get involved today and help protect your rights!";
    $ogUrl = htmlspecialchars( $title->getFullURL() );
    $ogImage = "https://consumerrights.wiki/images/logo/new_fixed_logo.png";

    $out->addHeadItem( 'meta-og-title',       "<meta property=\"og:title\" content=\"$ogTitle\" />" );
    $out->addHeadItem( 'meta-og-description', "<meta property=\"og:description\" content=\"$ogDescription\" />" );
    $out->addHeadItem( 'meta-og-url',         "<meta property=\"og:url\" content=\"$ogUrl\" />" );
    $out->addHeadItem( 'meta-og-image',       "<meta property=\"og:image\" content=\"$ogImage\" />" );
    $out->addHeadItem( 'meta-twitter-card',   '<meta name="twitter:card" content="summary_large_image" />' );

    return true;
};
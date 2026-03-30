<?php

# SEO-related hooks for indexation control and OpenGraph completeness.
# This file contains BeforePageDisplay hooks that improve search engine
# and social media handling of wiki pages.

use MediaWiki\Output\OutputPage;

# Add og:type meta tag to satisfy OpenGraph protocol requirements.
# WikiSEO handles og:title, og:description, og:image but omits og:type.
# Main page and non-article pages get "website"; articles get "article".
$wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
    $title = $out->getTitle();
    if ( $title->isSpecialPage() ) {
        return;
    }
    $ogType = ( $title->getNamespace() === NS_MAIN && !$title->isMainPage() ) ? 'article' : 'website';
    $out->addHeadItem( 'og:type', '<meta property="og:type" content="' . htmlspecialchars( $ogType ) . '" />' );
};

# Auto-populate meta description from Cargo IncidentCargo.Description field
# when no manual {{#seo:description=...}} exists on the page.
# Priority: manual {{#seo:}} > Cargo Description > WikiSEO auto-description.
# Only queries Cargo for main-namespace content pages to minimize DB load.
$wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
    $title = $out->getTitle();

    // Only main namespace, non-main-page, non-special
    if ( $title->getNamespace() !== NS_MAIN || $title->isMainPage() || $title->isSpecialPage() ) {
        return;
    }

    // Check if a description meta tag was already set (by WikiSEO's {{#seo:}} tag)
    $existingMeta = $out->getMetaTags();
    foreach ( $existingMeta as $tag ) {
        if ( isset( $tag[0] ) && $tag[0] === 'description' && !empty( $tag[1] ) && $tag[1] !== '.' ) {
            return; // Manual description exists, don't override
        }
    }

    // Query Cargo for the Description field
    if ( !class_exists( 'CargoUtils' ) ) {
        return;
    }

    try {
        $dbr = CargoUtils::getDB();
        $res = $dbr->select(
            'IncidentCargo',
            [ 'Description' ],
            [ '_pageName' => $title->getText() ],
            __METHOD__
        );
        $row = $res->fetchObject();
        if ( $row && !empty( $row->Description ) ) {
            $description = trim( $row->Description );
            // Truncate to 160 characters at last complete word
            if ( strlen( $description ) > 160 ) {
                $description = substr( $description, 0, 157 );
                $description = substr( $description, 0, strrpos( $description, ' ' ) ) . '...';
            }
            $out->addMeta( 'description', $description );
            $out->addHeadItem(
                'og:description:cargo',
                '<meta property="og:description" content="' . htmlspecialchars( $description ) . '" />'
            );
        }
    } catch ( Exception $e ) {
        // Silently fail — Cargo table may not exist yet or may have been rebuilt
        wfDebugLog( 'SEO', 'Cargo description lookup failed: ' . $e->getMessage() );
    }
};

# Conditionally noindex thin categories (fewer than 5 members).
# Prevents empty/near-empty category listing pages from diluting site quality signals.
# Categories automatically become indexable once they reach 5 members.
$wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
    $title = $out->getTitle();
    if ( $title->getNamespace() !== NS_CATEGORY ) {
        return;
    }
    $category = Category::newFromTitle( $title );
    if ( $category && $category->getMemberCount() < 5 ) {
        $out->setRobotPolicy( 'noindex,follow' );
    }
};

# Conditionally noindex stub articles (fewer than 2,000 bytes of wikitext).
# Thin stubs trigger Google's Helpful Content classifier as low-quality signals.
# Uses $title->getLength() which reads cached page_len — no content loading required.
# Articles automatically become indexable as the community expands them past 2,000 bytes.
$wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
    $title = $out->getTitle();
    if ( $title->getNamespace() !== NS_MAIN || $title->isMainPage() ) {
        return;
    }
    if ( $title->getLength() < 2000 ) {
        $out->setRobotPolicy( 'noindex,follow' );
    }
};

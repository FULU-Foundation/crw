<?php

# Add custom text to the footer for privacy policy and license information
# This moves the content from MediaWiki:Sitenotice to the footer
$wgHooks['SkinAddFooterLinks'][] = function ( Skin $skin, string $key, array &$footerlinks ) {
    if ( $key === 'info' ) {
        # Add privacy policy notice
        $footerlinks['privacy-notice'] = $skin->msg( 'footer-privacy-notice' )->parse();

        # Add license notice
        $footerlinks['license-notice'] = $skin->msg( 'footer-license-notice' )->parse();
    }

    if ( $key === 'places' ) {
        # Add changelog and staff links
        $linkRenderer = \MediaWiki\MediaWikiServices::getInstance()->getLinkRenderer();
        $footerlinks['changelog'] = $linkRenderer->makeKnownLink( \MediaWiki\Title\Title::newFromText( 'Project:Changelog' ), 'Changelog' );
        $footerlinks['staff'] = $linkRenderer->makeKnownLink( \MediaWiki\Title\Title::newFromText( 'Project:Staff' ), 'Staff' );
    }
};

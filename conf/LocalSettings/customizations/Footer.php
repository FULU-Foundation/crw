<?php

# Add custom text to the footer for privacy policy and license information
# This moves the content from MediaWiki:Sitenotice to the footer
$wgHooks['SkinAddFooterLinks'][] = function ( Skin $skin, string $key, array &$footerlinks ) {
    if ( $key === 'info' ) {
        # Add privacy policy notice
        $footerLinks['privacy-notice'] = $skin->msg( 'footer-privacy-notice' )->parse();
        
        # Add license notice
        $footerlinks['license-notice'] = $skin->msg( 'footer-license-notice' )->parse();
    }
};

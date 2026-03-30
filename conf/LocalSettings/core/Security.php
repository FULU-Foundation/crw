<?php

# These settings hide certain user preferences from the interface for privacy
$wgHiddenPrefs[] = 'realname';
$wgHiddenPrefs[] = 'language';

# Hides software version information from public view
$wgHideSoftwareVersion = true;

# Noindex non-content namespaces to prevent Google's Helpful Content
# classifier from suppressing site-wide rankings due to high junk-to-content ratio.
# Uses noindex,follow so link equity still flows through these pages.
# NS_CATEGORY deliberately excluded — thin categories handled conditionally in SEO.php.
$wgNamespaceRobotPolicies = [
    NS_TALK            => 'noindex,follow',
    NS_USER            => 'noindex,follow',
    NS_USER_TALK       => 'noindex,follow',
    NS_PROJECT         => 'noindex,follow',
    NS_PROJECT_TALK    => 'noindex,follow',
    NS_FILE_TALK       => 'noindex,follow',
    NS_HELP            => 'noindex,follow',
    NS_HELP_TALK       => 'noindex,follow',
    NS_TEMPLATE_TALK   => 'noindex,follow',
    NS_CATEGORY_TALK   => 'noindex,follow',
    NS_MEDIAWIKI_TALK  => 'noindex,follow',
    3004               => 'noindex,follow',  // Projects
    3005               => 'noindex,follow',  // Projects_talk
];

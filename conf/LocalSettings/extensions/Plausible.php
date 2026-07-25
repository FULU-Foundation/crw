<?php
# Load the Plausible MediaWiki extension.
# This adds a lightweight, privacy-respecting analytics system to track traffic,
# useful for monitoring spikes and understanding user engagement.
if(getenv('WIKI_ENV') != "Dev") {
    wfLoadExtension( 'Plausible' );
}
# Core configuration for your self-hosted Plausible instance.
# This ensures the extension knows where to send analytics events.
$wgPlausibleDomain = "https://analytics.consumerrights.wiki";   // Your Plausible base URL
$wgPlausibleDomainKey = "consumerrights.wiki";                  // Domain being tracked

$wgPlausibleApiKey = getenv( 'PLAUSIBLE_API_KEY' ); // UserImpact extension's view-stats panel.

# Respect privacy by not tracking logged-in users.
$wgPlausibleTrackLoggedIn = false;  // Do not track editors/admins

# Enable event tracking enhancements
$wgPlausibleTrackOutboundLinks = true;    // Track external link clicks
$wgPlausibleTrackFileDownloads = true;    // Track downloads (PDFs, images, etc.)
$wgPlausibleEnableTaggedEvents = true;    // Track clicks via HTML class names
$wgPlausibleHonorDNT = true;              // Respect browser's "Do Not Track" setting

# Allow users to opt-out using <plausible-opt-out /> tag
$wgPlausibleEnableOptOutTag = true;

# Track key MediaWiki UI actions
$wgPlausibleTrack404 = true;              // Log missing pages
$wgPlausibleTrackSearchInput = true;      // Log search bar input
$wgPlausibleTrackEditButtonClicks = true; // Log edit tab clicks
$wgPlausibleTrackNavplateClicks = true;   // Track navplate usage
$wgPlausibleTrackInfoboxClicks = true;    // Track infobox link clicks

# Ignore admin/special pages from analytics to reduce noise
$wgPlausibleIgnoredTitles = [
    '/Special:*',   // Exclude all Special pages
    '/User:*',      // Exclude user pages
    '/User_talk:*'  // Exclude user talk pages
];

# Server-side tracking for important backend events
# These events do not rely on JavaScript and work even with tracking blockers
$wgPlausibleServerSideTracking = [
    'pageedit' => true,
    'pagedelete' => true,
    'pageundelete' => true,
    'pagemove' => true,
    'fileupload' => true,
    'filedelete' => true,
    'fileundelete' => true,
    'searchnotfound' => true,
    'searchfound' => true
];

<?php

# Basic session settings
$wgObjectCacheSessionExpiry = 86400;      // Time-to-live for session data in object cache (24 hours).
$wgCookieExpiration = 86400;              // Expiration time for session cookies (24 hours).
$wgExtendedLoginCookieExpiration = 2592000; // Expiration time for "remember me" cookies (30 days).

# Session security settings
$wgSessionCacheType = CACHE_DB;           // Use the database for session storage to ensure data is not lost during cache evictions.

# Cookie configuration for cross-domain authentication
$wgCookiePrefix = "wiki_";                // Unique prefix for cookies to avoid conflicts with other applications.

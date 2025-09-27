<?php

# Basic session settings
$wgSessionLength = 86400;                 // Session length in seconds (24 hours).
$wgObjectCacheSessionExpiry = 86400;      // Time-to-live for session data in object cache (24 hours).
$wgCookieExpiration = 86400;              // Expiration time for session cookies (24 hours).
$wgExtendedLoginCookieExpiration = 2592000; // Expiration time for "remember me" cookies (30 days).

# Session security settings
$wgSessionUpdateAge = 300;                // Update session every 5 minutes of user activity to keep sessions fresh.
$wgSessionsInObjectCache = true;          // Store session data in the object cache for better scalability and persistence.
$wgSessionCacheType = CACHE_DB;           // Use the database for session storage to ensure data is not lost during cache evictions.

# Cookie configuration for cross-domain authentication
$wgCookiePath = "/";                      // Sets cookies to be valid across the entire site.
$wgCookiePrefix = "wiki_";                // Unique prefix for cookies to avoid conflicts with other applications.

<?php

# Enable support for Content Delivery Networks (CDNs).
$wgUseCdn = true; // Allows MediaWiki to work with CDNs for caching and serving content.

# Allow detection of private IPs.
# Important for setups using Docker or internal proxies to ensure real client IPs are preserved.
$wgUsePrivateIPs = true;

# Trusted single proxy servers (empty here as we specify ranges below).
$wgCdnServers = [];

# Trusted proxy servers and IP ranges that should not be purged.
# These ranges include local servers, Docker bridge networks, and Cloudflare's IP ranges.
$wgCdnServersNoPurge = [
// Local server and Docker bridge network.
    '127.0.0.1',        // Nginx running on localhost.
    '172.18.0.1/16',    // Docker bridge network (required to avoid misidentifying all users as the Docker IP).

    // Cloudflare IPv4 ranges (required if using Cloudflare as a CDN or proxy).
    '103.21.244.0/22',
    '103.22.200.0/22',
    '103.31.4.0/22',
    '104.16.0.0/13',
    '104.24.0.0/14',
    '108.162.192.0/18',
    '131.0.72.0/22',
    '141.101.64.0/18',
    '162.158.0.0/15',
    '172.64.0.0/13',
    '173.245.48.0/20',
    '188.114.96.0/20',
    '190.93.240.0/20',
    '197.234.240.0/22',
    '198.41.128.0/17',

    // Cloudflare IPv6 ranges.
    '2400:cb00::/32',
    '2606:4700::/32',
    '2803:f800::/32',
    '2405:b500::/32',
    '2405:8100::/32',
    '2a06:98c0::/29',
    '2c0f:f248::/32',
];
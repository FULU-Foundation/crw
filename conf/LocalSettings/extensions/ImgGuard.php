<?php

# ImgGuard Configuration
#
# Screens uploaded files for explicit content before they are saved, and hands
# the verdict to AbuseFilter so consequences can be configured on-wiki.
#
# LOAD ORDER MATTERS. This file must be required AFTER AbuseFilter.php in
# LocalSettings.php. MediaWiki runs same-hook handlers in registration order,
# so if ImgGuard registers first it will block rejected uploads before
# AbuseFilter ever sees them, and no filter consequence (block, warn,
# blockautopromote, degroup, tag, throttle) will fire. ImgGuard logs a warning
# if it detects this, but the log is the only symptom.

wfLoadExtension( 'ImgGuard' );

# --- Service ---------------------------------------------------------------

# Where the classifier lives. 'crw-imgguard' is the compose service name; on a
# bare-metal install pointing at a local process, use http://127.0.0.1:8181.
$wgImgGuardScannerUrl = getenv( 'IMGGUARD_URL' ) ?: 'http://crw-imgguard:8181';

# Shared secret. Must match AUTH_TOKEN in the scanner's config.py. Not needed
# when the service is bound to localhost; strongly recommended when it is a
# separate container reachable on the compose network.
$wgImgGuardAuthToken = getenv( 'IMGGUARD_AUTH_TOKEN' ) ?: '';

# --- Policy ----------------------------------------------------------------

$wgImgGuardEnabled = true;

# START IN MONITOR MODE.
#
# With this false, every upload is scanned, logged and exposed to AbuseFilter,
# but ImgGuard never blocks anything. Run it this way for a week, read
# Special:Log/imgguard, confirm the false-positive rate on live traffic matches
# the backtest, and only then flip it to true. Turning it on cold means the
# first false positive is discovered by a contributor rather than by you.
$wgImgGuardEnforce = false;

# If the scanner is unreachable, reject rather than let files through
# unscanned. This is the right call for a zero-tolerance policy, and it does
# mean uploads stop working when the service is down - which is why the
# maintenance script exists and why the failure is logged at error level.
$wgImgGuardFailClosed = true;

# Formats the wiki accepts that can carry a picture. The list deliberately
# omits video: video uploads are already sysop-only here, and sysops hold
# imgguard-bypass, so scanning them would be dead weight. If video is ever
# opened up to non-sysops, add the extensions here AND set SCAN_VIDEO = True
# in the scanner's config.py - either alone does nothing.
$wgImgGuardScanExtensions = [
	'png', 'gif', 'jpg', 'jpeg', 'webp', 'svg',
	'pdf',
	'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp',
	'tif', 'tiff', 'bmp', 'avif',
];

# Must not exceed MAX_UPLOAD_MB in the scanner's config.py (32 MB by default).
# $wgMaxUploadSize here is 200 MB, so files between 32 MB and 200 MB are never
# classified; ImgGuardOversizeAction decides what happens to them.
$wgImgGuardMaxFileSize = 32 * 1024 * 1024;

# 'allow' or 'reject'. Both are recorded and visible to AbuseFilter either way,
# so a filter can take a stricter line without changing this.
$wgImgGuardOversizeAction = 'allow';
$wgImgGuardUnsupportedAction = 'allow';

# --- Logging ---------------------------------------------------------------

# Give ImgGuard its own log destination rather than relying on the ambient
# config, which differs by environment in a way that bites exactly when you
# need the log most.
#
# conf/LocalSettings/core/Logs.php only sets up the JSON logger when
# WIKI_ENV != "Dev". In a dev container there is no mediawiki-json.log at all,
# and an unrouted channel falls back to $wgDebugLogFile - so the first thing you
# try when uploads break is reading a file that does not exist. Naming the
# channel explicitly means it lands in the same place either way.
#
# The directory has to exist and be writable by www-data. The image only
# creates /var/log/php-fpm, so on a fresh container:
#   mkdir -p /var/log/mediawiki && chown -R www-data:www-data /var/log/mediawiki
$wgDebugLogGroups['ImgGuard'] = '/var/log/mediawiki/imgguard.log';

# Rejections, errors and bypasses are always logged to Special:Log/imgguard.
# This adds successful uploads too - useful while tuning, noisy afterwards.
$wgImgGuardLogAccepts = false;

# --- User-facing -----------------------------------------------------------

# Substituted as $1 into the block message. The wording itself is editable
# on-wiki at MediaWiki:Imgguard-rejected without touching this file.
$wgImgGuardContactAddress = 'help@consumerrights.wiki';

$wgImgGuardShowSpinner = true;

# --- Rights ----------------------------------------------------------------

# sysop gets both by default from the extension. Granting bypass more widely
# also means those uploads are never checked, so grant it deliberately.
# $wgGroupPermissions['superconfirmed']['imgguard-bypass'] = true;

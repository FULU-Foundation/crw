<?php


# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

# Periodically send a pingback to https://www.mediawiki.org/ with basic data
# about this MediaWiki instance. The Wikimedia Foundation shares this data
# with MediaWiki developers to help guide future development efforts.
$wgPingback = false;

# Site language code, should be one of the list in ./languages/data/Names.php
$wgLanguageCode = "en";

# Time zone
$wgLocaltimezone = "UTC";

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publicly accessible from the web.
#$wgCacheDirectory = "$IP/cache";

$wgSecretKey = getenv("MEDIAWIKI_SECRET");

# Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = "1";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

# Core
require_once './LocalSettings/core/Cache.php';
require_once './LocalSettings/core/Logs.php';
require_once './LocalSettings/core/Brand.php';
require_once './LocalSettings/core/Paths.php';
require_once './LocalSettings/core/Database.php';
require_once './LocalSettings/core/Skins.php';
require_once './LocalSettings/core/Namespaces.php';
require_once './LocalSettings/core/Uploads.php';
require_once './LocalSettings/core/Email.php';
require_once './LocalSettings/core/Copyright.php';
require_once './LocalSettings/core/Security.php';
require_once './LocalSettings/core/CDN.php';
require_once './LocalSettings/core/Debug.php';
require_once './LocalSettings/core/Sessions.php';

# Extensions
require_once './LocalSettings/extensions/Scribunto.php';
require_once './LocalSettings/extensions/TimezoneConverter.php';
require_once './LocalSettings/extensions/ContributionScores.php';
require_once './LocalSettings/extensions/Cargo.php';
require_once './LocalSettings/extensions/MobileFrontend.php';
require_once './LocalSettings/extensions/DarkMode.php';
require_once './LocalSettings/extensions/VisualEditor.php';
#require_once './LocalSettings/extensions/JsonConfig.php';
require_once './LocalSettings/extensions/TemplateStyles.php';
require_once './LocalSettings/extensions/Captcha.php';
require_once './LocalSettings/extensions/CategoryTree.php';
require_once './LocalSettings/extensions/DiscussionTools.php';
require_once './LocalSettings/extensions/Echo.php';
require_once './LocalSettings/extensions/ParserFunctions.php';
require_once './LocalSettings/extensions/InputBox.php';
require_once './LocalSettings/extensions/Linter.php';
require_once './LocalSettings/extensions/PageForms.php';

require_once './LocalSettings/extensions/BulkBlock.php';
require_once './LocalSettings/extensions/Nuke.php';

require_once './LocalSettings/extensions/StopForumSpam.php';
require_once './LocalSettings/extensions/AbuseFilter.php';
require_once './LocalSettings/extensions/SmiteSpam.php';

require_once './LocalSettings/extensions/EmbedVideo.php';
require_once './LocalSettings/extensions/UserMerge.php';
require_once './LocalSettings/extensions/PageImages.php';
require_once './LocalSettings/extensions/Cite.php';
require_once './LocalSettings/extensions/Plausible.php';

require_once './LocalSettings/extensions/Discord.php';
require_once './LocalSettings/extensions/CRWHooks.php';

# Customizations
require_once './LocalSettings/customizations/Footer.php';

# Groups
require_once './LocalSettings/core/Groups.php';
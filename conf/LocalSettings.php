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

$wgSecretKey = getenv("MEDIAWIKI_SECRET");

# Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = "1";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

# Core
require_once __DIR__ . '/LocalSettings/core/Cache.php';
require_once __DIR__ . '/LocalSettings/core/Logs.php';
require_once __DIR__ . '/LocalSettings/core/Brand.php';
require_once __DIR__ . '/LocalSettings/core/Paths.php';
require_once __DIR__ . '/LocalSettings/core/Database.php';
require_once __DIR__ . '/LocalSettings/core/Skins.php';
require_once __DIR__ . '/LocalSettings/core/Namespaces.php';
require_once __DIR__ . '/LocalSettings/core/Uploads.php';
require_once __DIR__ . '/LocalSettings/core/Email.php';
require_once __DIR__ . '/LocalSettings/core/Copyright.php';
require_once __DIR__ . '/LocalSettings/core/Security.php';
require_once __DIR__ . '/LocalSettings/core/CDN.php';
require_once __DIR__ . '/LocalSettings/core/Debug.php';
require_once __DIR__ . '/LocalSettings/core/Sessions.php';
require_once __DIR__ . '/LocalSettings/core/TempUser.php';

# Extensions
require_once __DIR__ . '/LocalSettings/extensions/Scribunto.php';
require_once __DIR__ . '/LocalSettings/extensions/TimezoneConverter.php';
require_once __DIR__ . '/LocalSettings/extensions/ContributionScores.php';
require_once __DIR__ . '/LocalSettings/extensions/Cargo.php';
require_once __DIR__ . '/LocalSettings/extensions/MobileFrontend.php';
require_once __DIR__ . '/LocalSettings/extensions/DarkMode.php';
require_once __DIR__ . '/LocalSettings/extensions/VisualEditor.php';
#require_once __DIR__ . '/LocalSettings/extensions/JsonConfig.php';
require_once __DIR__ . '/LocalSettings/extensions/TemplateStyles.php';
require_once __DIR__ . '/LocalSettings/extensions/Captcha.php';
require_once __DIR__ . '/LocalSettings/extensions/CategoryTree.php';
require_once __DIR__ . '/LocalSettings/extensions/DiscussionTools.php';
require_once __DIR__ . '/LocalSettings/extensions/Echo.php';
require_once __DIR__ . '/LocalSettings/extensions/ParserFunctions.php';
require_once __DIR__ . '/LocalSettings/extensions/InputBox.php';
require_once __DIR__ . '/LocalSettings/extensions/Linter.php';
require_once __DIR__ . '/LocalSettings/extensions/PageForms.php';

require_once __DIR__ . '/LocalSettings/extensions/BulkBlock.php';
require_once __DIR__ . '/LocalSettings/extensions/Nuke.php';

require_once __DIR__ . '/LocalSettings/extensions/StopForumSpam.php';
require_once __DIR__ . '/LocalSettings/extensions/AbuseFilter.php';
require_once __DIR__ . '/LocalSettings/extensions/ImgGuard.php';
require_once __DIR__ . '/LocalSettings/extensions/SmiteSpam.php';
require_once __DIR__ . '/LocalSettings/extensions/SpamBlacklist.php';
require_once __DIR__ . '/LocalSettings/extensions/TitleBlacklist.php';
require_once __DIR__ . '/LocalSettings/extensions/ProtectSite.php';

require_once __DIR__ . '/LocalSettings/extensions/EmbedVideo.php';
require_once __DIR__ . '/LocalSettings/extensions/UserMerge.php';
require_once __DIR__ . '/LocalSettings/extensions/PageImages.php';
require_once __DIR__ . '/LocalSettings/extensions/Cite.php';
require_once __DIR__ . '/LocalSettings/extensions/PageViewInfo.php';
require_once __DIR__ . '/LocalSettings/extensions/Plausible.php';
require_once __DIR__ . '/LocalSettings/extensions/CloudflarePurge.php';

require_once __DIR__ . '/LocalSettings/extensions/Discord.php';
require_once __DIR__ . '/LocalSettings/extensions/ArticleFeedback.php';
require_once __DIR__ . '/LocalSettings/extensions/ConfirmLogout.php';
require_once __DIR__ . '/LocalSettings/extensions/MassRollback.php';

require_once __DIR__ . '/LocalSettings/extensions/WikiEditor.php';
require_once __DIR__ . '/LocalSettings/extensions/Thanks.php';
require_once __DIR__ . '/LocalSettings/extensions/CodeEditor.php';
require_once __DIR__ . '/LocalSettings/extensions/NewUserMessage.php';
require_once __DIR__ . '/LocalSettings/extensions/TwoColConflict.php';
require_once __DIR__ . '/LocalSettings/extensions/CodeMirror.php';

require_once __DIR__ . '/LocalSettings/extensions/WikiSEO.php';
require_once __DIR__ . '/LocalSettings/extensions/TextExtracts.php';
require_once __DIR__ . '/LocalSettings/extensions/Popups.php';

require_once __DIR__ . '/LocalSettings/extensions/CirrusSearch.php';
require_once __DIR__ . '/LocalSettings/extensions/Elastica.php';

require_once __DIR__ . '/LocalSettings/extensions/SearchDigest.php';

require_once __DIR__ . '/LocalSettings/extensions/OATHAuth.php';
require_once __DIR__ . '/LocalSettings/extensions/CheckUser.php';
require_once __DIR__ . '/LocalSettings/extensions/AntiSpoof.php';
require_once __DIR__ . '/LocalSettings/extensions/TorBlock.php';

# Customizations
require_once __DIR__ . '/LocalSettings/customizations/Footer.php';
require_once __DIR__ . '/LocalSettings/customizations/CustomStyles.php';

# Groups
require_once __DIR__ . '/LocalSettings/core/Groups.php';

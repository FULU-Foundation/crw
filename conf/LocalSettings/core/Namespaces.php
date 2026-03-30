<?php

$wgMetaNamespace = "Consumer_Rights_Wiki";

# Define custom namespaces
define("NS_PRIMARY", 4);
define("NS_PRIMARY_TALK", 5);

$wgNamespaceProtection[NS_PRIMARY] = ['edit-cat'];

$wgExtraNamespaces[NS_PRIMARY] = 'Consumer_Rights_Wiki';
$wgExtraNamespaces[NS_PRIMARY_TALK] = 'Consumer_Rights_Wiki_talk';

define("NS_PROJECTS", 3004);
define("NS_PROJECTS_TALK", 3005);

$wgExtraNamespaces[NS_PROJECTS] = "Projects";
$wgExtraNamespaces[NS_PROJECTS_TALK] = "Projects_talk";

# Restrict sitemap generation to main namespace (articles) only.
# Non-content namespaces are noindexed via $wgNamespaceRobotPolicies in Security.php,
# so including them in the sitemap would send contradictory signals to search engines.
$wgSitemapNamespaces = [ 0 ];

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

# Block crawling of certain namespaces
$wgNamespaceRobotPolicies = [
    NS_TALK => 'noindex,nofollow',          
    NS_USER => 'noindex,nofollow',           
    NS_USER_TALK => 'noindex,nofollow',      
    NS_PROJECT_TALK => 'noindex,nofollow',   
    NS_FILE_TALK => 'noindex,nofollow',      
    NS_MEDIAWIKI => 'noindex,nofollow',      
    NS_MEDIAWIKI_TALK => 'noindex,nofollow', 
    NS_TEMPLATE => 'noindex,nofollow',       
    NS_TEMPLATE_TALK => 'noindex,nofollow',  
    NS_HELP_TALK => 'noindex,nofollow',      
    NS_CATEGORY_TALK => 'noindex,nofollow',
    NS_PRIMARY_TALK => 'noindex,nofollow',
    NS_PROJECTS_TALK => 'noindex,nofollow',
];

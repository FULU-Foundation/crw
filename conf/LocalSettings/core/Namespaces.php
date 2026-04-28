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

# Block robots
$wgNamespaceRobotPolicies = [
    NS_TALK => 'noindex,follow',          
    NS_USER => 'noindex,follow',           
    NS_USER_TALK => 'noindex,follow',      
    NS_PROJECT_TALK => 'noindex,follow',   
    NS_FILE_TALK => 'noindex,follow',      
    NS_MEDIAWIKI => 'noindex,follow',      
    NS_MEDIAWIKI_TALK => 'noindex,follow', 
    NS_TEMPLATE => 'noindex,follow',       
    NS_TEMPLATE_TALK => 'noindex,follow',  
    NS_HELP_TALK => 'noindex,follow',      
    NS_CATEGORY_TALK => 'noindex,follow',
    NS_PRIMARY_TALK => 'noindex,follow',
    NS_PROJECTS_TALK => 'noindex,follow',
];

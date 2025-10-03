<?php

$wgMetaNamespace = "Consumer_Rights_Wiki";

# Define custom namespaces
define("NS_PRIMARY", 4);
define("NS_PRIMARY_TALK", 5);

$wgNamespaceProtection[NS_PRIMARY] = ['edit-cat'];

$wgExtraNamespaces[NS_PRIMARY] = 'Consumer_Rights_Wiki';
$wgExtraNamespaces[NS_PRIMARY_TALK] = 'Consumer_Rights_Wiki_talk';
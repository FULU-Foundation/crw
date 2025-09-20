<?php

# Enable CategoryTree
wfLoadExtension( 'CategoryTree' );
$wgCategoryTreeUseCache = true;
$wgCategoryTreeAllowTag = true;
$wgCategoryTreeDynamicTag = true;

$wgCategoryTreeDefaultOptions = [
    'mode' => 'pages',          // Show both pages and subcategories
    'showcount' => true,        // Show number of items
    'namespaces' => [ 0, NS_CATEGORY ], // Only show pages in main & category namespaces
    'depth' => 2,               // Start with 2 levels expanded
    'hideprefix' => 'categories' // Clean output for categories
];
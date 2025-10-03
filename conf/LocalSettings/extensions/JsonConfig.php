<?php

# Load JsonConfig extension - Required for proper citation template functionality
wfLoadExtension( 'JsonConfig' );

# Enable Lua support for JsonConfig (needed for citation processing)
$wgJsonConfigEnableLuaSupport = true;

# Configure the tabular data namespace
$wgJsonConfigModels['Tabular.JsonConfig'] = 'JsonConfig\JCTabularContent';
$wgJsonConfigs['Tabular.JsonConfig'] = [
    'namespace' => 486,    // Data namespace ID 
    'nsName' => 'Data',    // Namespace name
    'pattern' => '/.\.tab$/',
    'license' => 'CC0-1.0',
    'isLocal' => false,    // Allow remote data access
];

# Set up Commons connection for citation data
$wgJsonConfigInterwikiPrefix = "commons";
$wgJsonConfigs['Tabular.JsonConfig']['remote'] = [
    'url' => 'https://commons.wikimedia.org/w/api.php'
];

<?php

# Load the Scribunto extension (only need to load once)
wfLoadExtension( 'Scribunto' );

# Set the default engine to 'luastandalone'
# This ensures Lua scripts are executed using the standalone interpreter
$wgScribuntoDefaultEngine = 'luastandalone';

# Specify the correct path to the Lua interpreter
# Using the verified 64-bit Linux binary path
$wgScribuntoEngineConf['luastandalone']['luaPath'] = '/var/www/html/extensions/Scribunto/includes/Engines/LuaStandalone/binaries/lua5_1_5_linux_64_generic/lua';

# Resource Limits
# Adjust these values if needed for complex templates
$wgScribuntoEngineConf['luastandalone']['memoryLimit'] = 209715200; // 200MB
$wgScribuntoEngineConf['luastandalone']['cpuLimit'] = 10; // Maximum of 10 seconds CPU time

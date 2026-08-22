<?php

wfLoadExtension( 'TorBlock' );

# Exit node list is refreshed via cron/load_tor_nodes.sh
$wgTorLoadNodes = false;

<?php
# Load the Discord extension for sending notifications to a Discord channel.
wfLoadExtension( 'mw-discord' );

# Discord Webhook Configuration
$wgDiscordWebhookURL = getenv('DISCORD_HOOK');

# Excluded Namespaces
$wgDiscordDisabledNS = [];

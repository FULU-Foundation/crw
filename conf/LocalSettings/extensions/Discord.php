<?php
# Load the Discord extension for sending notifications to a Discord channel.
wfLoadExtension( 'mw-discord' );

# Discord Webhook Configuration
$wgDiscordWebhookURL = [''];
// Webhook URL for sending messages to Discord.

# Bot Appearance
$wgDiscordUsername = 'Wiki_Updates';   // Display name of the Discord bot.
$wgDiscordAvatarURL = 'https://consumerrights.wiki/images/logo/135x135png.png'; // URL for the bot's avatar.

# Notification Content Settings
$wgDiscordIncludePageContent = true; // Include full content of changes in notifications.
$wgDiscordMaxContentLength = 2000;  // Limit content previews to 2,000 characters (Discord's maximum).

# Enable Notifications for Key Actions
$wgDiscordIncludeLogTypes = [
    'create',     // Notify on new page creation.
    'edit',       // Notify on page edits.
    'delete',     // Notify on page deletions.
    'protect',    // Notify on page protection changes.
    'upload',     // Notify on file uploads.
    'move'        // Notify on page moves or renames.
];

# Message Formatting
$wgDiscordUseEmbeds = true;           // Use rich embeds for visually appealing messages.
$wgDiscordShowTags = true;            // Display tags associated with edits.
$wgDiscordShowExcerpts = true;        // Include excerpts of changes in notifications.

# Notification Colors
$wgDiscordColors = [
    'create' => '45DE51',  // Green for new pages.
    'edit'   => '3498DB',  // Blue for edits.
    'delete' => 'E74C3C',  // Red for deletions.
    'protect' => 'F1C40F', // Yellow for protection changes.
    'upload' => '9B59B6',  // Purple for uploads.
    'move'   => 'E67E22'   // Orange for moves.
];

# Notification Behavior
$wgDiscordLinkBehavior = 'embed';        // Use embeds for links in messages.
$wgDiscordIncludeDiffSize = true;       // Show size of changes in notifications.
$wgDiscordShowTimestamp = true;         // Include timestamps in Discord messages.

# Excluded Namespaces
$wgDiscordExcludedNamespaces = [
    NS_USER,        // Exclude notifications for user pages.
    NS_USER_TALK    // Exclude notifications for user talk pages.
];

# Privacy and Additional Settings
$wgDiscordIncludeUserEmail = false;  // Do not include user email addresses in notifications.
$wgDiscordIncludeIP = false;         // Do not include user IP addresses in notifications.

$wgDiscordDisabledHooks[] = 'LocalUserCreated';
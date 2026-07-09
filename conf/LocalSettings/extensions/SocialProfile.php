<?php

require_once "$IP/extensions/SocialProfile/SocialProfile.php";

$wgUserProfileDisplay['avatar'] = true; // Profile pictures.
$wgUserProfileDisplay['awards'] = true; // Achievement badges.
$wgUserProfileDisplay['gifts'] = false; // User-to-user virtual gifts disabled.
$wgUserProfileDisplay['custom'] = false; // Hides the "Custom information" section on profile pages.

$wgUserBoard = false; // Message board disabled on profile pages.
$wgFriendingEnabled = false; // Relationships disabled.

# Track edit counts live so awards unlock as soon as a threshold is crossed.
require_once "$IP/extensions/SocialProfile/UserStats/EditCount.php";
$wgNamespacesForEditPoints = [ 0 ]; // Count edits to content pages only.
$wgUserStatsPointValues['edit'] = 0; // Points awarded per qualifying edit.

# T328235 and T287962
$wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
    $out->addInlineStyle( '.visualClear { clear: both; }' );
};

# Permissions for sysop users to manage SocialProfile extension
$wgGroupPermissions['sysop']['editothersprofiles'] = true;
$wgGroupPermissions['sysop']['updatepoints'] = true;
$wgGroupPermissions['sysop']['generatetopusersreport'] = true;

# Permissions for profile-managers to manage SocialProfile extension
$wgExtensionFunctions[] = static function () {
    global $wgGroupPermissions, $wgSpecialPages;
    unset( $wgGroupPermissions['staff'] );
    $wgGroupPermissions['profile-manager']['awardsmanage'] = true;
    $wgGroupPermissions['profile-manager']['giftadmin'] = true;
    $wgGroupPermissions['profile-manager']['populate-user-profiles'] = true;
    $wgGroupPermissions['profile-manager']['editothersprofiles'] = true;
    $wgGroupPermissions['profile-manager']['editothersprofiles-private'] = true;
    # Remove special pages related to relationships
    unset( $wgSpecialPages['AddRelationship'] );
    unset( $wgSpecialPages['RemoveRelationship'] );
    unset( $wgSpecialPages['ViewRelationshipRequests'] );
    unset( $wgSpecialPages['ViewRelationships'] );
};
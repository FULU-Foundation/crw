<?php

require_once "$IP/extensions/SocialProfile/SocialProfile.php";

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

# Limit access to Special:GiveGift to users with the giftadmin permission.
$wgHooks['SpecialPageBeforeExecute'][] = function ( SpecialPage $special, $subPage ) {
    if ( $special->getName() === 'GiveGift' && !$special->getUser()->isAllowed( 'giftadmin' ) ) {
        throw new PermissionsError( 'giftadmin' );
    }
};

# Permissions for sysop users to manage SocialProfile extension
$wgGroupPermissions['sysop']['editothersprofiles'] = true;
$wgGroupPermissions['sysop']['updatepoints'] = true;

# Permissions for profile-managers to manage SocialProfile extension
$wgExtensionFunctions[] = static function () {
    global $wgGroupPermissions, $wgSpecialPages;
    unset( $wgGroupPermissions['staff'] );
    $wgGroupPermissions['profile-manager']['awardsmanage'] = true;
    $wgGroupPermissions['profile-manager']['giftadmin'] = true;
    $wgGroupPermissions['profile-manager']['populate-user-profiles'] = true;
    $wgGroupPermissions['profile-manager']['editothersprofiles'] = true;
    $wgGroupPermissions['profile-manager']['editothersprofiles-private'] = true;
    # Remove special pages
    unset( $wgSpecialPages['AddRelationship'] );
    unset( $wgSpecialPages['RemoveRelationship'] );
    unset( $wgSpecialPages['ViewRelationshipRequests'] );
    unset( $wgSpecialPages['ViewRelationships'] );
    unset( $wgSpecialPages['UserActivity'] );
    unset( $wgSpecialPages['GenerateTopUsersReport'] );
    unset( $wgSpecialPages['TopUsers'] );
    unset( $wgSpecialPages['TopFansByStatistic'] );
    unset( $wgSpecialPages['TopUsersRecent'] );
    unset( $wgSpecialPages['TopAwards'] );
    unset( $wgSpecialPages['UserBoard'] );
    unset( $wgSpecialPages['SendBoardBlast'] );
};
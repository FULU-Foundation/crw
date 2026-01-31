<?php
/**
 * Hooks for ArchiveLinkHelper extension
 *
 * @file
 * @ingroup Extensions
 * @license GPL-3.0-or-later
 */

class ArchiveLinkHelperHooks {

	/**
	 * Add modules to page output
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( $out, $skin ) {
		$action = $out->getRequest()->getVal( 'action', 'view' );
		
		// Load editor module when editing
		if ( in_array( $action, [ 'edit', 'submit' ] ) ) {
			$out->addModules( 'ext.archiveLinkHelper' );
		}
		
		// Load view module when viewing pages (adds archive indicators to links)
		if ( $action === 'view' ) {
			$out->addModules( 'ext.archiveLinkHelper.view' );
		}
	}
}
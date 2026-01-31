/**
 * ArchiveLinkHelper - Editor Integration
 *
 * @license GPL-3.0-or-later
 */

( function () {
	'use strict';

	var ARCHIVE_ORG_AVAILABLE_API = 'https://archive.org/wayback/available';
	var ARCHIVE_ORG_SAVE_URL = 'https://web.archive.org/save/';
	var ARCHIVE_ORG_WEB_URL = 'https://web.archive.org/web/';

	var POLL_INTERVAL_MS = 5000;
	var MAX_POLL_ATTEMPTS = 24;

	function checkArchiveAvailability( url ) {
		var deferred = $.Deferred();

		$.ajax( {
			url: ARCHIVE_ORG_AVAILABLE_API,
			data: { url: url },
			dataType: 'jsonp',
			timeout: 10000
		} ).done( function ( data ) {
			if ( data && data.archived_snapshots && data.archived_snapshots.closest ) {
				deferred.resolve( data.archived_snapshots.closest );
			} else {
				deferred.resolve( null );
			}
		} ).fail( function () {
			deferred.resolve( null );
		} );

		return deferred.promise();
	}

	function triggerArchiveSaveFetch( url ) {
		if ( window.fetch ) {
			return fetch( ARCHIVE_ORG_SAVE_URL, {
				method: 'POST',
				mode: 'no-cors',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: 'url=' + encodeURIComponent( url )
			} ).catch( function () {} );
		}
		return $.Deferred().resolve().promise();
	}

	function formatWikiLink( originalUrl, archiveUrl, linkText ) {
		if ( linkText ) {
			return '[' + originalUrl + ' ' + linkText + '] ([' + archiveUrl + ' archived])';
		}
		return originalUrl + ' ([' + archiveUrl + ' archived])';
	}

	function extractUrls( text ) {
		var urlRegex = /https?:\/\/[^\s\]\[<>]+/g;
		return text.match( urlRegex ) || [];
	}

	function insertArchiveLink( $textarea, originalUrl, archiveUrl, selection ) {
		var linkText = '';
		var replacement;

		var wikiLinkMatch = selection.match( /\[https?:\/\/[^\s\]]+ ([^\]]+)\]/ );
		if ( wikiLinkMatch ) {
			linkText = wikiLinkMatch[ 1 ];
		}

		replacement = formatWikiLink( originalUrl, archiveUrl, linkText );

		if ( selection ) {
			$textarea.textSelection( 'replaceSelection', replacement );
		} else {
			$textarea.textSelection( 'encapsulateSelection', {
				pre: replacement
			} );
		}
	}

	function showArchiveDialog( url, $textarea, selection ) {
		var $dialog = $( '<div>' )
			.attr( 'id', 'archive-link-dialog' )
			.append( $( '<p>' ).text( mw.msg( 'archivelinkhelper-dialog-no-archive' ) ) )
			.append( $( '<p>' ).text( mw.msg( 'archivelinkhelper-dialog-create' ) ) );

		var $status = $( '<p>' )
			.attr( 'id', 'archive-status' )
			.css( 'font-style', 'italic' );

		var $progressBar = $( '<div>' ).css( {
			width: '100%',
			height: '8px',
			background: '#eee',
			borderRadius: '4px',
			marginTop: '10px',
			display: 'none'
		} );

		var $progressFill = $( '<div>' ).css( {
			width: '0%',
			height: '100%',
			background: '#4CAF50',
			borderRadius: '4px',
			transition: 'width 0.3s'
		} );

		$progressBar.append( $progressFill );
		$dialog.append( $status ).append( $progressBar );

		var isArchiving = false;
		var pollCount = 0;

		function startArchiving() {
			if ( isArchiving ) {
				return;
			}
			isArchiving = true;
			pollCount = 0;

			$status.text( mw.msg( 'archivelinkhelper-sending' ) );
			$progressBar.show();
			$progressFill.css( 'width', '5%' );

			triggerArchiveSaveFetch( url ).then( function () {
				$status.text( mw.msg( 'archivelinkhelper-waiting' ) );
				$progressFill.css( 'width', '15%' );
				pollForNewArchive();
			} );
		}

		function pollForNewArchive() {
			pollCount++;
			var progress = Math.min( 15 + ( pollCount * 3.5 ), 95 );
			$progressFill.css( 'width', progress + '%' );

			checkArchiveAvailability( url ).then( function ( snapshot ) {
				if ( snapshot && snapshot.available ) {
					$progressFill.css( 'width', '100%' );
					$status.text( mw.msg( 'archivelinkhelper-success' ) );

					setTimeout( function () {
						insertArchiveLink( $textarea, url, snapshot.url, selection );
						mw.notify( mw.msg( 'archivelinkhelper-success' ), { type: 'success' } );
						$dialog.dialog( 'close' );
					}, 500 );
				} else if ( pollCount < MAX_POLL_ATTEMPTS ) {
					$status.text( mw.msg( 'archivelinkhelper-waiting' ) +
						' (' + pollCount + '/' + MAX_POLL_ATTEMPTS + ')' );
					setTimeout( pollForNewArchive, POLL_INTERVAL_MS );
				} else {
					$status.text( mw.msg( 'archivelinkhelper-timeout' ) );
					$progressBar.hide();
					isArchiving = false;
				}
			} );
		}

		$dialog.dialog( {
			title: mw.msg( 'archivelinkhelper-dialog-title' ),
			width: 450,
			modal: true,
			buttons: [
				{
					text: mw.msg( 'archivelinkhelper-archive-now' ),
					click: function () {
						startArchiving();
					}
				},
				{
					text: mw.msg( 'archivelinkhelper-insert-placeholder' ),
					click: function () {
						var placeholder = ARCHIVE_ORG_WEB_URL + '*/' + url;
						insertArchiveLink( $textarea, url, placeholder, selection );
						mw.notify( mw.msg( 'archivelinkhelper-found' ), { type: 'info' } );
						$dialog.dialog( 'close' );
					}
				},
				{
					text: mw.msg( 'archivelinkhelper-cancel' ),
					click: function () {
						$dialog.dialog( 'close' );
					}
				}
			],
			close: function () {
				$( this ).dialog( 'destroy' ).remove();
			}
		} );
	}

	function handleArchiveLinkAction( context ) {
		var $textarea = context.$textarea;
		var selection = $textarea.textSelection( 'getSelection' );
		var urls = extractUrls( selection );

		if ( urls.length === 0 ) {
			var inputUrl = prompt( mw.msg( 'archivelinkhelper-prompt' ) );
			if ( inputUrl && inputUrl.match( /^https?:\/\// ) ) {
				urls = [ inputUrl ];
			} else if ( inputUrl ) {
				mw.notify( mw.msg( 'archivelinkhelper-invalid-url' ), { type: 'error' } );
				return;
			} else {
				return;
			}
		}

		var url = urls[ 0 ];
		mw.notify( mw.msg( 'archivelinkhelper-checking' ) + ' ' + url, { type: 'info' } );

		checkArchiveAvailability( url ).then( function ( snapshot ) {
			if ( snapshot && snapshot.available ) {
				insertArchiveLink( $textarea, url, snapshot.url, selection );
				mw.notify( mw.msg( 'archivelinkhelper-found' ), { type: 'success' } );
			} else {
				showArchiveDialog( url, $textarea, selection );
			}
		} );
	}

	function addToolbarButton() {
		$( '#wpTextbox1' ).wikiEditor( 'addToToolbar', {
			section: 'main',
			group: 'insert',
			tools: {
				archiveLink: {
					label: mw.msg( 'archivelinkhelper-button-label' ),
					type: 'button',
					oouiIcon: 'clock',
					action: {
						type: 'callback',
						execute: function ( context ) {
							handleArchiveLinkAction( context );
						}
					}
				}
			}
		} );
	}

	mw.hook( 'wikiEditor.toolbarReady' ).add( function () {
		addToolbarButton();
	} );

}() );
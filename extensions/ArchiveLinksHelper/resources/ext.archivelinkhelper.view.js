/**
 * ArchiveLinkHelper - View Mode Integration
 *
 * @license GPL-3.0-or-later
 */

( function () {
	'use strict';

	var ARCHIVE_ORG_AVAILABLE_API = 'https://archive.org/wayback/available';
	var ARCHIVE_ORG_SAVE_URL = 'https://web.archive.org/save/';

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

	function triggerArchiveSave( url ) {
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

	function addArchiveIndicators( $content ) {
		var $externalLinks = $content.find( 'a.external' );

		$externalLinks.each( function () {
			var $link = $( this );
			var url = $link.attr( 'href' );

			if ( $link.data( 'archive-checked' ) ||
				url.indexOf( 'archive.org' ) !== -1 ||
				url.indexOf( 'web.archive.org' ) !== -1 ) {
				return;
			}

			$link.data( 'archive-checked', true );

			var $indicator = $( '<span>' )
				.addClass( 'archive-indicator' )
				.text( ' ⏳' );

			$link.after( $indicator );

			checkArchiveAvailability( url ).then( function ( snapshot ) {
				if ( snapshot && snapshot.available ) {
					$indicator
						.empty()
						.append(
							$( '<a>' )
								.attr( 'href', snapshot.url )
								.attr( 'title', 'Archived: ' + snapshot.timestamp )
								.addClass( 'archive-link archive-link-exists' )
								.text( ' [archived]' )
						);
				} else {
					var $archiveLink = $( '<a>' )
						.attr( 'href', '#' )
						.attr( 'title', 'No archive found - click to create one' )
						.addClass( 'archive-link archive-link-missing' )
						.text( ' [archive this]' )
						.on( 'click', function ( e ) {
							e.preventDefault();
							var $this = $( this );

							if ( $this.data( 'archiving' ) ) {
								return;
							}

							$this.data( 'archiving', true );
							$this.text( ' [archiving...]' )
								.removeClass( 'archive-link-missing' )
								.addClass( 'archive-link-pending' );

							triggerArchiveSave( url ).then( function () {
								var attempts = 0;
								var checkInterval = setInterval( function () {
									attempts++;
									checkArchiveAvailability( url ).then( function ( newSnapshot ) {
										if ( newSnapshot && newSnapshot.available ) {
											clearInterval( checkInterval );
											$this
												.attr( 'href', newSnapshot.url )
												.off( 'click' )
												.removeClass( 'archive-link-pending' )
												.addClass( 'archive-link-exists' )
												.text( ' [archived]' )
												.data( 'archiving', false );
										} else if ( attempts >= MAX_POLL_ATTEMPTS ) {
											clearInterval( checkInterval );
											$this
												.removeClass( 'archive-link-pending' )
												.addClass( 'archive-link-timeout' )
												.text( ' [archive pending]' )
												.data( 'archiving', false );
										}
									} );
								}, POLL_INTERVAL_MS );
							} );
						} );

					$indicator.empty().append( $archiveLink );
				}
			} );
		} );
	}

	mw.hook( 'wikipage.content' ).add( function ( $content ) {
		if ( mw.config.get( 'wgAction' ) === 'view' ) {
			addArchiveIndicators( $content );
		}
	} );

}() );
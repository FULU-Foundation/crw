/* Any JavaScript here will be loaded for all users on every page load. */
// stolen from Lajos Mészáros on this post https://stackoverflow.com/questions/13358292/capture-tap-event-with-pure-javascript
const onClickOrTap = (element, handler) => {
  let touchMoveHappened = false;
  element.addEventListener('touchstart', () => {
    // on mobile this is the 1st event that happens
    touchMoveHappened = false;
  });
  element.addEventListener('touchmove', () => {
    // on mobile this might get triggered in which case the
    // click or tap will get cancelled
    // we'll keep a track of it
    touchMoveHappened = true;
  });
  element.addEventListener('touchend', (e) => {
    // happens after touchstart, but before click
    // if touch happened then we'll exit
    if (touchMoveHappened) {
      return;
    }
    // calling preventDefault() will make sure the
    // subsequent click will not get triggered
    e.preventDefault();
    // at this point we are ready to call our original handler
    handler(e);
  });
  element.addEventListener('click', (e) => {
    // this will only get triggered on desktop
    // because we call preventDefault for the "touchend" event
    handler(e);
  });
}

/* Create Page */
const createPageTargetIdDataSetString = "[data-create-page-target-id]";

if (document.querySelector(createPageTargetIdDataSetString)) {
  /**
   * @type {HTMLElement[]}
   */
  let itemsCreateInputPageGrid = [];
  let itemsCreatePageGrid = [];
  const createPageTargetId = "createPageTargetId";
  const createPageInputHiddenClass = "hidden";
  const createPageInputActiveClass = "pageInputActive";

  /**
   * @param {HTMLElement} element
   */
  function getGridTarget(element) {
    const targetId = element.dataset[createPageTargetId];

    return document.getElementById(targetId);
  }

  /**
   * @this {HTMLDivElement}
   */
  function toggleGridTarget(element, targetElement) {
    for (let index = 0; index < itemsCreateInputPageGrid.length; index += 1) {
      const target = itemsCreateInputPageGrid[index];

      target.classList.add(createPageInputHiddenClass);

      if (target === targetElement) {
        target.classList.remove(createPageInputHiddenClass);
      }

      const clickedElement = itemsCreatePageGrid[index];

      clickedElement.classList.remove(createPageInputActiveClass);

      if (clickedElement === element) {
        clickedElement.classList.add(createPageInputActiveClass);
      }
    }
  }

  /**
   * @param {HTMLElement} element
   * @param {HTMLElement} targetElement
   */
  function addToggleGridTargetEvent(element, targetElement) {
    itemsCreatePageGrid.push(element);
    itemsCreateInputPageGrid.push(targetElement);

    onClickOrTap(element, (e) => {
     toggleGridTarget(element, targetElement)
    });
  }

  const createPageTargetElements = document.querySelectorAll(
    createPageTargetIdDataSetString
  );

  for (let index = 0; index < createPageTargetElements.length; index += 1) {
    const element = createPageTargetElements[index];

    const targetElement = getGridTarget(element);

    if (targetElement) {
      addToggleGridTargetEvent(element, targetElement);
    }
  }
}

/* Recent Changes Portlet */
(function () {
  function crwTimeAgo(date) {
    var s = Math.floor((Date.now() - date.getTime()) / 1000);
    if (s < 60) return s + "s ago";
    var m = Math.floor(s / 60);
    if (m < 60) return m + "m ago";
    var h = Math.floor(m / 60);
    if (h < 24) return h + "h ago";
    var d = Math.floor(h / 24);
    return d + "d ago";
  }

  function crwInjectStyles() {
    if (document.getElementById("crw-rc-style")) return;

    var style = document.createElement("style");
    style.id = "crw-rc-style";
    style.textContent =
      "#p-crw-recentchanges .vector-menu-heading{display:flex;align-items:center;justify-content:space-between;}" +
      "#p-crw-recentchanges .crw-rc-dot{width:8px;height:8px;border-radius:50%;background:#2da44e;box-shadow:0 0 0 2px rgba(45,164,78,.18);flex:0 0 auto;}" +
      "#p-crw-recentchanges .vector-menu-heading{font-size:.95em;}" +
      "#p-crw-recentchanges .vector-menu-content{font-size:.92em;}" +
      "#p-crw-recentchanges .crw-rc-meta{font-size:.85em;opacity:.85;}";
    document.head.appendChild(style);
  }

  function crwPlacePortlet(portlet, boxId, afterId) {
    var column = document.querySelector(".vector-column-end");

    if (column) {
      var box = document.createElement("div");
      box.id = boxId;
      box.className = "vector-pinned-container";
      box.appendChild(portlet);

      var stack = column.querySelector(".vector-sticky-pinned-container") || column;
      stack.appendChild(box);
      return;
    }

    var panel = document.getElementById("mw-panel");
    if (!panel) return;

    portlet.className += " vector-menu-portal portal";

    var after = afterId ? document.getElementById(afterId) : null;
    if (after && after.parentNode) {
      after.parentNode.insertBefore(portlet, after.nextSibling);
    } else {
      panel.appendChild(portlet);
    }
  }

  function crwBuildPortlet() {
    if (!document.querySelector(".vector-column-end, #mw-panel")) return null;
    if (document.getElementById("p-crw-recentchanges")) return null;

    crwInjectStyles();

    var portlet = document.createElement("div");
    portlet.id = "p-crw-recentchanges";
    portlet.className = "vector-menu mw-portlet";

    var heading = document.createElement("div");
    heading.className = "vector-menu-heading";

    var headingText = document.createElement("span");
    headingText.textContent = "Recent changes";

    var dot = document.createElement("span");
    dot.className = "crw-rc-dot";
    dot.setAttribute("aria-hidden", "true");
    dot.title = "Live";

    heading.appendChild(headingText);
    heading.appendChild(dot);

    var content = document.createElement("div");
    content.className = "vector-menu-content";

    var ul = document.createElement("ul");
    ul.className = "vector-menu-content-list";
    ul.id = "crw-rc-list";

    var moreWrap = document.createElement("div");
    moreWrap.className = "crw-rc-more";

    var moreLink = document.createElement("a");
    moreLink.href = mw.util.getUrl("Special:RecentChanges");
    moreLink.textContent = "Show more…";
    moreWrap.appendChild(moreLink);

    content.appendChild(ul);
    content.appendChild(moreWrap);

    portlet.appendChild(heading);
    portlet.appendChild(content);

    crwPlacePortlet(portlet, "crw-rc-box", "p-navigation");

    return ul;
  }

  function crwLoadRecentChanges(ul) {
    if (!ul) return;

    var api = new mw.Api();
    api
      .get({
        action: "query",
        list: "recentchanges",
        rcnamespace: "0",
        rctype: "edit|new",
        rclimit: 30,
        rcprop: "title|timestamp|user|loginfo",
        rcshow: "!bot",
        formatversion: 2
      })
      .then(function (data) {
        var items =
          data && data.query && data.query.recentchanges
            ? data.query.recentchanges
            : [];

        ul.textContent = "";

        var groups = [];

        for (var i = 0; i < items.length; i++) {
          var rc = items[i];

          if (!rc.title) continue;
          if (rc.actionhidden || rc.userhidden || rc.commenthidden) continue;
          if (rc.suppressed) continue;
          if (rc.type !== "edit" && rc.type !== "new") continue;

          var last = groups[groups.length - 1];

          if (last && last.title === rc.title && last.user === (rc.user || "")) {
            last.count++;
            continue;
          }

          if (groups.length >= 5) break;

          groups.push({
            title: rc.title,
            user: rc.user || "",
            timestamp: rc.timestamp,
            count: 1
          });
        }

        for (var g = 0; g < groups.length; g++) {
          var grp = groups[g];

          var li = document.createElement("li");
          li.className = "mw-list-item crw-rc-item";

          var a = document.createElement("a");
          a.href = mw.util.getUrl(grp.title);
          a.textContent = grp.title;

          var meta = document.createElement("div");
          meta.className = "crw-rc-meta";
          meta.textContent =
            crwTimeAgo(new Date(grp.timestamp)) +
            " · " +
            grp.user +
            (grp.count > 1 ? " (" + grp.count + "x)" : "");

          li.appendChild(a);
          li.appendChild(meta);
          ul.appendChild(li);
        }
      })
      .catch(function () {
        ul.textContent = "";

        var li = document.createElement("li");
        li.className = "mw-list-item crw-rc-item";

        var a = document.createElement("a");
        a.href = mw.util.getUrl("Special:RecentChanges");
        a.textContent = "View recent changes";

        li.appendChild(a);
        ul.appendChild(li);
      });
  }

  function crwBuildExtensionBox() {
    if (!document.querySelector(".vector-column-end, #mw-panel")) return;
    if (document.getElementById("crw-ext-box")) return;

    var portlet = document.createElement("div");
    portlet.id = "p-crw-extension";
    portlet.className = "vector-menu mw-portlet";

    var heading = document.createElement("div");
    heading.className = "vector-menu-heading";
    heading.textContent = "Browser extension";

    var content = document.createElement("div");
    content.className = "vector-menu-content";

    var desc = document.createElement("div");
    desc.className = "crw-ext-desc";
    desc.textContent = "Get notified when the site, product, or service you're viewing has an article on the wiki.";

    var actions = document.createElement("div");
    actions.className = "crw-ext-actions";

    function addButton(href, label, browser) {
      var a = document.createElement("a");
      a.href = href;
      a.className = "crw-ext-btn crw-ext-btn-" + browser;
      a.textContent = label;
      actions.appendChild(a);
    }

    addButton("https://chromewebstore.google.com/detail/consumer-rights-wiki/bppajinomefndbbmopljhbdfefnefdha", "Chrome", "chrome");
    addButton("https://addons.mozilla.org/firefox/addon/consumer-rights-wiki/", "Firefox", "firefox");

    content.appendChild(desc);
    content.appendChild(actions);

    portlet.appendChild(heading);
    portlet.appendChild(content);

    crwPlacePortlet(portlet, "crw-ext-box", null);
  }

  function crwInitRecentChanges() {
    if (!window.mw || !mw.util || !mw.Api) return;

    var ul = crwBuildPortlet();
    if (!ul) ul = document.getElementById("crw-rc-list");
    if (!ul) return;

    crwLoadRecentChanges(ul);
    setInterval(function () {
      crwLoadRecentChanges(ul);
    }, 60000);
  }

  function crwWaitForMenu() {
    var tries = 0;

    var timer = setInterval(function () {
      var column = document.querySelector(".vector-column-end, #mw-panel");

      if (column) {
        clearInterval(timer);
        crwInitRecentChanges();
        crwBuildExtensionBox();
        return;
      }

      tries++;
      if (tries > 50) clearInterval(timer);
    }, 200);
  }

  crwWaitForMenu();
})();

/* */
(function () {
  function crwAddMobileDonate() {
    var menu = document.getElementById("mw-mf-page-left");
    if (!menu || document.getElementById("p-crw-donate")) return;

    var list = document.createElement("ul");
    list.id = "p-crw-donate";
    list.className = "toggle-list__list";

    var item = document.createElement("li");
    item.className = "mw-list-item";
    item.id = "n-Donate";

    var link = document.createElement("a");
    link.href = "https://buy.stripe.com/cNi9ASahn2ozbiJblj1Fe00";
    link.textContent = "Donate";
    link.title = "Make a tax-deductible donation to FULU Education, a 501(c)(3) nonprofit";

    item.appendChild(link);
    list.appendChild(item);

    var footerLinks = menu.querySelector(".hlist");
    menu.insertBefore(list, footerLinks || null);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", crwAddMobileDonate);
  } else {
    crwAddMobileDonate();
  }
})();

/* Create page chooser — builds title inputs (Project:Create page) */
if (document.querySelector('.cp-input')) {
	mw.loader.using('mediawiki.util').then(function () {
	  document.querySelectorAll('.cp-input').forEach(function (box) {
	    var input = document.createElement('input');
	    input.type = 'text';
	    input.placeholder = box.dataset.placeholder || '';
	    input.className = 'cp-title-input';
	    var btn = document.createElement('button');
	    btn.type = 'button';
	    btn.textContent = 'Create page';
	    btn.className = 'crw-cp-btn';
	    function go() {
	      var title = input.value.trim();
	      if (!title) { input.focus(); return; }
	      var url;
	      if (box.dataset.form) {
	        url = mw.util.getUrl(
	          'Special:FormEdit/' + box.dataset.form + '/' + title,
	          box.dataset.preload ? { preload: 'Template:' + box.dataset.preload } : {}
	        );
	      } else {
	        var params = { veaction: 'edit' };
	        if (box.dataset.preload) { params.preload = 'Template:' + box.dataset.preload; }
	        if (box.dataset.editintro) { params.editintro = 'Template:' + box.dataset.editintro; }
	        url = mw.util.getUrl(title, params);
	      }
	      window.location.href = url;
	    }
	    btn.addEventListener('click', go);
	    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { go(); } });
	    box.append(input, btn);
	  });
	});
}

/* Inline player for locally uploaded video files.
   This wiki has no TimedMediaHandler, so [[File:clip.mp4]] cannot render a
   player, and EmbedVideo's "videolink" service drops the file URL and is
   skipped by its own consent script. Mark a video up as:
     <div class="crw-video">[https://consumerrights.wiki/images/x/xx/clip.mp4 Fallback text]</div>
   and this upgrades the link into a native <video controls> element.
   With JavaScript off, the fallback link still opens the file directly. */
( function () {
	function buildVideoPlayers() {
		var holders = document.querySelectorAll( '.crw-video' );
		if ( !holders.length ) {
			return;
		}
		Array.prototype.forEach.call( holders, function ( holder ) {
			if ( holder.getAttribute( 'data-crw-video-done' ) ) {
				return;
			}
			var link = holder.querySelector( 'a[href]' );
			if ( !link ) {
				return;
			}
			var src = link.href;
			if ( !/\.(mp4|m4v|webm|mov)(\?|$)/i.test( src ) ) {
				return;
			}
			var video = document.createElement( 'video' );
			video.src = src;
			video.controls = true;
			video.preload = 'metadata';
			video.setAttribute( 'playsinline', '' );
			video.style.display = 'block';
			video.style.maxWidth = '100%';
			video.style.height = 'auto';
			video.style.margin = '0 auto';
			var poster = holder.getAttribute( 'data-poster' );
			if ( poster ) {
				video.poster = poster;
			}
			while ( holder.firstChild ) {
				holder.removeChild( holder.firstChild );
			}
			holder.appendChild( video );
			holder.setAttribute( 'data-crw-video-done', '1' );
		} );
	}
	if ( window.mw && mw.hook ) {
		mw.hook( 'wikipage.content' ).add( buildVideoPlayers );
	} else {
		document.addEventListener( 'DOMContentLoaded', buildVideoPlayers );
	}
}() );

/* BEGIN crw-gallery */
/* ---------------------------------------------------------------------------
 * consumerrights.wiki click-through screenshot viewer
 *
 * DEPLOYED TO: MediaWiki:Common.js (between the BEGIN/END crw-gallery markers).
 * Master copy: wiki-config/mediawiki-common-crw-gallery.js, with its styles in
 * wiki-config/mediawiki-common-crw-gallery.css (MediaWiki:Common.css). Keep the
 * live pages and these files identical, then run scripts/verify-crw-gallery.py.
 *
 * WHY THIS EXISTS
 * <gallery mode="slideshow"> paints every image as a thumbnail grid until its
 * script runs, then shows three small grey icons with no counter, and it does
 * not build at all on the mobile skin. Readers could not tell how to step
 * through a long run of screenshots. This viewer shows one large screenshot at
 * a time with labelled Previous/Next buttons, a "3 of 43" counter, swipe, arrow
 * keys and a numbered filmstrip, and opens a full-screen viewer (zoom, swipe,
 * Escape or Back to close) for reading the text in a screenshot.
 *
 * Article markup (opt-in; nothing happens on pages without the wrapper):
 *   <div class="crw-gallery" data-shot-anchor="ownership-screenshot-">
 *   <gallery class="noviewer" caption="Heading shown above the viewer">
 *   File:Example.png|alt=What the image shows|Caption, may contain [[#row-3|links]]
 *   </gallery>
 *   </div>
 * data-shot-anchor (optional) makes #ownership-screenshot-7 open screenshot 7.
 * With JavaScript off, Common.css turns the gallery into a one-row swipeable
 * strip instead of a grid.
 * ------------------------------------------------------------------------- */
( function () {
	'use strict';

	const WIDTH_BUCKETS = [ 480, 640, 800, 1024, 1280, 1600, 2048, 2560, 3200 ];
	const reduceMotion = () => window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const urlCache = {};
	let lightbox = null;

	function el( tag, cls, attrs ) {
		const node = document.createElement( tag );
		if ( cls ) {
			node.className = cls;
		}
		Object.keys( attrs || {} ).forEach( ( k ) => {
			if ( k === 'text' ) {
				node.textContent = attrs[ k ];
			} else {
				node.setAttribute( k, attrs[ k ] );
			}
		} );
		return node;
	}

	function bucketFor( px ) {
		for ( let i = 0; i < WIDTH_BUCKETS.length; i++ ) {
			if ( WIDTH_BUCKETS[ i ] >= px ) {
				return WIDTH_BUCKETS[ i ];
			}
		}
		return WIDTH_BUCKETS[ WIDTH_BUCKETS.length - 1 ];
	}

	/* One imageinfo request per width bucket for every file in the gallery. */
	function fetchUrls( titles, width ) {
		const key = width + '|' + titles.join( '|' );
		if ( urlCache[ key ] ) {
			return urlCache[ key ];
		}
		const api = new mw.Api();
		const chunks = [];
		for ( let i = 0; i < titles.length; i += 50 ) {
			chunks.push( titles.slice( i, i + 50 ) );
		}
		urlCache[ key ] = Promise.all( chunks.map( ( chunk ) => api.get( {
			action: 'query',
			prop: 'imageinfo',
			iiprop: 'url|size',
			iiurlwidth: width,
			titles: chunk,
			formatversion: 2
		} ) ) ).then( ( responses ) => {
			const out = {};
			responses.forEach( ( res ) => {
				const norm = {};
				( ( res.query && res.query.normalized ) || [] ).forEach( ( n ) => {
					norm[ n.to ] = n.from;
				} );
				( ( res.query && res.query.pages ) || [] ).forEach( ( page ) => {
					const info = page.imageinfo && page.imageinfo[ 0 ];
					if ( !info ) {
						return;
					}
					const rec = {
						original: info.url,
						width: info.width,
						height: info.height,
						sized: info.width <= width ? info.url : ( info.thumburl || info.url ),
						descriptionurl: info.descriptionurl
					};
					out[ page.title ] = rec;
					if ( norm[ page.title ] ) {
						out[ norm[ page.title ] ] = rec;
					}
				} );
			} );
			return out;
		} );
		return urlCache[ key ];
	}

	/* Read file, alt text, caption and thumbnail from the rendered gallery,
	   including MobileFrontend's lazy-image placeholders. */
	function readItems( gallery ) {
		const items = [];
		gallery.querySelectorAll( 'li.gallerybox' ).forEach( ( box ) => {
			const link = box.querySelector( 'a.mw-file-description, a[href*="File:"]' );
			const img = box.querySelector( 'img' );
			const lazy = box.querySelector( '.lazy-image-placeholder' );
			let title = null;
			if ( link ) {
				const href = decodeURIComponent( link.getAttribute( 'href' ) || '' );
				const m = href.match( /(?:\/wiki\/|\/w\/|title=)(File:[^?&#]+)/ );
				title = m ? m[ 1 ].replace( /_/g, ' ' ) : null;
			}
			if ( !title && img ) {
				const f = ( img.getAttribute( 'src' ) || '' ).match( /[?&]f=([^&]+)/ );
				title = f ? 'File:' + decodeURIComponent( f[ 1 ] ).replace( /_/g, ' ' ) : null;
			}
			if ( !title ) {
				return;
			}
			const captionNode = box.querySelector( '.gallerytext' );
			items.push( {
				title: title,
				alt: ( img && img.getAttribute( 'alt' ) ) || ( lazy && lazy.getAttribute( 'data-alt' ) ) || '',
				thumb: ( img && ( img.getAttribute( 'src' ) ) ) || ( lazy && lazy.getAttribute( 'data-mw-src' ) ) || '',
				thumbSrcset: ( img && img.getAttribute( 'srcset' ) ) || ( lazy && lazy.getAttribute( 'data-mw-srcset' ) ) || '',
				caption: captionNode ? captionNode.cloneNode( true ) : null,
				fileHref: link ? link.href : null
			} );
		} );
		return items;
	}

	function shortLabel( item, index, items ) {
		/* "16", "16.2" for the second screenshot of quote 16; else the position. */
		const a = item.caption && item.caption.querySelector( 'a[href^="#"]' );
		const n = a && ( a.textContent.match( /(\d+)\s*$/ ) || [] )[ 1 ];
		if ( !n ) {
			return String( index + 1 );
		}
		let seen = 0;
		for ( let i = 0; i <= index; i++ ) {
			const b = items[ i ].caption && items[ i ].caption.querySelector( 'a[href^="#"]' );
			if ( b && ( b.textContent.match( /(\d+)\s*$/ ) || [] )[ 1 ] === n ) {
				seen++;
			}
		}
		return seen > 1 ? n + '.' + seen : n;
	}

	function scrollTrackTo( track, index, smooth ) {
		const slide = track.children[ index ];
		if ( !slide ) {
			return;
		}
		track.scrollTo( { left: slide.offsetLeft - track.offsetLeft, behavior: smooth && !reduceMotion() ? 'smooth' : 'auto' } );
	}

	function currentIndex( track ) {
		const w = track.clientWidth;
		return w ? Math.round( track.scrollLeft / w ) : 0;
	}

	/* Follows a snap track's scroll position and reports the slide the reader moved to.
	   Positions are ignored while a button or key press is still scrolling to its target
	   (a smooth scroll passes through the slides in between) and right after the track's
	   width changes (the pixel offset stays put while the slides resize). */
	function followTrack( track, onIndex ) {
		const f = { target: null, width: 0, timer: null, settle: null };
		f.travel = ( index ) => {
			f.target = index;
			clearTimeout( f.timer );
			f.timer = setTimeout( () => {
				f.target = null;
			}, 1200 );
		};
		const read = () => {
			if ( !track.clientWidth ) {
				return;
			}
			if ( track.clientWidth !== f.width ) {
				f.width = track.clientWidth;
				return;
			}
			const i = currentIndex( track );
			if ( f.target !== null ) {
				if ( i === f.target && Math.abs( track.scrollLeft - track.children[ i ].offsetLeft + track.offsetLeft ) < 3 ) {
					f.target = null;
				}
				return;
			}
			onIndex( i );
		};
		track.addEventListener( 'scroll', () => {
			read();
			clearTimeout( f.settle );
			f.settle = setTimeout( read, 140 );
		}, { passive: true } );
		track.addEventListener( 'scrollend', () => {
			if ( f.target !== null && currentIndex( track ) === f.target ) {
				f.target = null;
			}
		} );
		return f;
	}

	function centerInStrip( strip, button ) {
		if ( !strip.clientWidth ) {
			return;
		}
		const left = button.offsetLeft - strip.offsetLeft - ( strip.clientWidth - button.offsetWidth ) / 2;
		strip.scrollTo( { left: Math.max( 0, left ), behavior: reduceMotion() ? 'auto' : 'smooth' } );
	}

	/* ---------------------------------------------------------------- viewer */
	function buildViewer( wrapper ) {
		const gallery = wrapper.querySelector( 'ul.gallery' );
		if ( !gallery ) {
			return;
		}
		const items = readItems( gallery );
		if ( !items.length ) {
			return;
		}
		const total = items.length;
		const titles = items.map( ( it ) => it.title );
		const headingNode = gallery.querySelector( '.gallerycaption' );
		const heading = headingNode ? headingNode.textContent.trim() : 'Screenshots';
		const anchorPrefix = wrapper.getAttribute( 'data-shot-anchor' ) || '';
		const state = { index: 0, urls: null };
		const touchOnly = window.matchMedia && window.matchMedia( '(hover: none)' ).matches;

		const root = el( 'div', 'crw-gv', {
			role: 'region',
			'aria-roledescription': 'carousel',
			'aria-label': heading
		} );
		const head = el( 'div', 'crw-gv-head' );
		head.appendChild( el( 'div', 'crw-gv-title', { text: heading } ) );
		root.appendChild( head );

		const stage = el( 'div', 'crw-gv-stage' );
		const track = el( 'div', 'crw-gv-track', { tabindex: '0', 'aria-label': heading + ': use the arrow keys to move between screenshots' } );
		const slides = items.map( ( item, i ) => {
			const fig = el( 'figure', 'crw-gv-slide', { role: 'group', 'aria-roledescription': 'slide', 'aria-label': ( i + 1 ) + ' of ' + total } );
			const open = el( 'button', 'crw-gv-open', { type: 'button', 'aria-label': 'Open screenshot ' + ( i + 1 ) + ' of ' + total + ' full screen' } );
			const img = el( 'img', 'crw-gv-img', { alt: item.alt, decoding: 'async', draggable: 'false' } );
			open.appendChild( img );
			open.appendChild( el( 'span', 'crw-gv-open-hint', { 'aria-hidden': 'true', text: touchOnly ? 'Tap to enlarge' : 'Click to enlarge' } ) );
			fig.appendChild( open );
			track.appendChild( fig );
			open.addEventListener( 'click', () => openLightbox( i, open ) );
			return { fig: fig, img: img };
		} );
		const arrowPrev = el( 'button', 'crw-gv-arrow crw-gv-arrow-prev', { type: 'button', 'aria-label': 'Previous screenshot', text: '‹' } );
		const arrowNext = el( 'button', 'crw-gv-arrow crw-gv-arrow-next', { type: 'button', 'aria-label': 'Next screenshot', text: '›' } );
		stage.appendChild( track );
		stage.appendChild( arrowPrev );
		stage.appendChild( arrowNext );
		root.appendChild( stage );

		const bar = el( 'div', 'crw-gv-bar' );
		const btnPrev = el( 'button', 'crw-gv-btn crw-gv-btn-prev', { type: 'button' } );
		btnPrev.innerHTML = '<span aria-hidden="true">‹</span> Previous';
		const counter = el( 'div', 'crw-gv-count', { 'aria-live': 'polite', 'aria-atomic': 'true' } );
		const btnNext = el( 'button', 'crw-gv-btn crw-gv-btn-next', { type: 'button' } );
		btnNext.innerHTML = 'Next <span aria-hidden="true">›</span>';
		const btnFull = el( 'button', 'crw-gv-btn crw-gv-btn-full', { type: 'button', 'aria-label': 'View full screen', title: 'View full screen' } );
		btnFull.innerHTML = '<span aria-hidden="true" class="crw-gv-full-icon"></span><span class="crw-gv-full-text">Full screen</span>';
		bar.appendChild( btnPrev );
		bar.appendChild( counter );
		bar.appendChild( btnNext );
		bar.appendChild( btnFull );
		root.appendChild( bar );

		const caption = el( 'div', 'crw-gv-caption' );
		root.appendChild( caption );

		const strip = el( 'div', 'crw-gv-strip', { role: 'group', 'aria-label': 'All ' + total + ' screenshots' } );
		const thumbs = items.map( ( item, i ) => {
			const label = shortLabel( item, i, items );
			const b = el( 'button', 'crw-gv-thumb', { type: 'button', 'aria-label': 'Screenshot ' + ( i + 1 ) + ' of ' + total + ( label !== String( i + 1 ) ? ', quote ' + label : '' ) } );
			if ( item.thumb ) {
				const t = el( 'img', '', { src: item.thumb, alt: '', loading: 'lazy', decoding: 'async', draggable: 'false' } );
				if ( item.thumbSrcset ) {
					t.setAttribute( 'srcset', item.thumbSrcset );
				}
				b.appendChild( t );
			}
			b.appendChild( el( 'span', 'crw-gv-thumb-num', { 'aria-hidden': 'true', text: label } ) );
			b.addEventListener( 'click', () => go( i, true ) );
			strip.appendChild( b );
			return b;
		} );
		root.appendChild( strip );

		function setImages() {
			if ( !state.urls ) {
				return;
			}
			slides.forEach( ( s, i ) => {
				if ( Math.abs( i - state.index ) <= 2 && !s.img.getAttribute( 'src' ) ) {
					const rec = state.urls[ items[ i ].title ];
					if ( rec ) {
						s.img.setAttribute( 'width', rec.width );
						s.img.setAttribute( 'height', rec.height );
						s.img.src = rec.sized;
					}
				}
			} );
		}

		function render() {
			const i = state.index;
			counter.innerHTML = '';
			counter.appendChild( el( 'span', 'crw-gv-count-word', { text: 'Screenshot ' } ) );
			counter.appendChild( el( 'strong', '', { text: String( i + 1 ) } ) );
			counter.appendChild( document.createTextNode( ' of ' + total ) );
			caption.innerHTML = '';
			if ( items[ i ].caption ) {
				caption.appendChild( items[ i ].caption.cloneNode( true ) );
			}
			btnPrev.disabled = i === 0;
			arrowPrev.disabled = i === 0;
			btnNext.disabled = i === total - 1;
			arrowNext.disabled = i === total - 1;
			thumbs.forEach( ( t, k ) => {
				if ( k === i ) {
					t.setAttribute( 'aria-current', 'true' );
				} else {
					t.removeAttribute( 'aria-current' );
				}
			} );
			slides.forEach( ( s, k ) => {
				s.fig.setAttribute( 'aria-hidden', k === i ? 'false' : 'true' );
				s.fig.querySelector( 'button' ).tabIndex = k === i ? 0 : -1;
			} );
			centerInStrip( strip, thumbs[ i ] );
			setImages();
		}

		const follower = followTrack( track, ( i ) => {
			if ( i !== state.index && i >= 0 && i < total ) {
				state.index = i;
				render();
			}
		} );

		function go( i, smooth ) {
			const next = Math.max( 0, Math.min( total - 1, i ) );
			state.index = next;
			follower.travel( next );
			scrollTrackTo( track, next, smooth );
			render();
		}

		[ btnPrev, arrowPrev ].forEach( ( b ) => b.addEventListener( 'click', () => go( state.index - 1, true ) ) );
		[ btnNext, arrowNext ].forEach( ( b ) => b.addEventListener( 'click', () => go( state.index + 1, true ) ) );
		btnFull.addEventListener( 'click', () => openLightbox( state.index, btnFull ) );

		root.addEventListener( 'keydown', ( e ) => {
			if ( e.target.closest && e.target.closest( '.crw-gv-caption' ) ) {
				return;
			}
			if ( e.key === 'ArrowRight' ) {
				go( state.index + 1, true );
				e.preventDefault();
			} else if ( e.key === 'ArrowLeft' ) {
				go( state.index - 1, true );
				e.preventDefault();
			} else if ( e.key === 'Home' && e.target === track ) {
				go( 0, true );
				e.preventDefault();
			} else if ( e.key === 'End' && e.target === track ) {
				go( total - 1, true );
				e.preventDefault();
			}
		} );

		/* Keep the current slide in place when the width changes (rotation, resize,
		   or a collapsed mobile section being opened). */
		let lastWidth = 0;
		const keepPosition = () => {
			if ( track.clientWidth && track.clientWidth !== lastWidth ) {
				lastWidth = track.clientWidth;
				follower.width = track.clientWidth;
				follower.travel( state.index );
				scrollTrackTo( track, state.index, false );
				centerInStrip( strip, thumbs[ state.index ] );
				maybeLoadUrls();
			}
		};
		if ( window.ResizeObserver ) {
			new ResizeObserver( keepPosition ).observe( track );
		} else {
			window.addEventListener( 'resize', keepPosition );
		}

		let urlWidth = 0;
		function maybeLoadUrls() {
			const cssWidth = track.clientWidth || Math.min( window.innerWidth, 1200 );
			const want = bucketFor( cssWidth * Math.min( window.devicePixelRatio || 1, 2 ) );
			if ( want <= urlWidth ) {
				return;
			}
			urlWidth = want;
			fetchUrls( titles, want ).then( ( urls ) => {
				if ( want !== urlWidth ) {
					return;
				}
				const upgrading = !!state.urls;
				state.urls = urls;
				if ( upgrading ) {
					slides.forEach( ( s, i ) => {
						const rec = urls[ items[ i ].title ];
						if ( rec && s.img.getAttribute( 'src' ) ) {
							s.img.src = rec.sized;
						}
					} );
				}
				setImages();
			} ).catch( () => {
				/* Fall back to the gallery's own thumbnails. */
				if ( !state.urls ) {
					state.urls = {};
					items.forEach( ( it ) => {
						state.urls[ it.title ] = { sized: it.thumb, original: it.fileHref, width: 0, height: 0 };
					} );
					setImages();
				}
			} );
		}

		/* ---------------------------------------------------------- lightbox */
		function openLightbox( index, returnFocusTo ) {
			const lb = getLightbox();
			lb.open( {
				items: items,
				titles: titles,
				index: index,
				anchorPrefix: anchorPrefix,
				onClose: ( lastIndex ) => {
					go( lastIndex, false );
					/* Chromium drops focus to <body> when Back returns to a URL with a
					   fragment, after this runs; restore it again once that settles. */
					const refocus = () => {
						if ( returnFocusTo && returnFocusTo.isConnected &&
							( document.activeElement === returnFocusTo || document.activeElement === document.body || !document.activeElement ) ) {
							returnFocusTo.focus( { preventScroll: true } );
						}
					};
					refocus();
					setTimeout( refocus, 80 );
					setTimeout( refocus, 400 );
					window.addEventListener( 'hashchange', refocus, { once: true } );
					setTimeout( () => window.removeEventListener( 'hashchange', refocus ), 1500 );
				}
			} );
		}

		gallery.parentNode.insertBefore( root, gallery );
		wrapper.classList.add( 'crw-gallery-ready' );
		render();
		maybeLoadUrls();

		/* #ownership-screenshot-7 selects screenshot 7 and scrolls the viewer into view. */
		const fromHash = () => {
			if ( !anchorPrefix ) {
				return;
			}
			const m = decodeURIComponent( location.hash.slice( 1 ) ).match( new RegExp( '^' + anchorPrefix.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '(\\d+)$' ) );
			if ( m ) {
				const i = parseInt( m[ 1 ], 10 ) - 1;
				if ( i >= 0 && i < total ) {
					go( i, false );
					root.scrollIntoView( { block: 'start' } );
				}
			}
		};
		fromHash();
		window.addEventListener( 'hashchange', fromHash );
	}

	/* A single shared full-screen viewer. It is a <dialog> opened with show(), not
	   showModal(): browsers disagree on whether the top layer used by showModal() is
	   painted inside a filter on <html>, and this wiki's DarkMode extension inverts
	   the whole page with exactly such a filter (Firefox inverts a modal dialog,
	   Chromium does not). A fixed, non-modal dialog is inside the page filter in every
	   browser, so it counter-inverts itself when the page is inverted, and makes the
	   rest of the page inert while open. */
	function getLightbox() {
		if ( lightbox ) {
			return lightbox;
		}
		const dialog = el( 'dialog', 'crw-lb', { 'aria-label': 'Screenshot viewer', 'aria-modal': 'true' } );
		const top = el( 'div', 'crw-lb-top' );
		const count = el( 'div', 'crw-lb-count', { 'aria-live': 'polite', 'aria-atomic': 'true' } );
		const zoomBtn = el( 'button', 'crw-lb-btn crw-lb-zoom', { type: 'button', 'aria-pressed': 'false', text: 'Zoom' } );
		const orig = el( 'a', 'crw-lb-btn crw-lb-orig', { target: '_blank', rel: 'noopener', text: 'Original' } );
		const close = el( 'button', 'crw-lb-btn crw-lb-close', { type: 'button', 'aria-label': 'Close viewer', text: '✕' } );
		top.appendChild( count );
		top.appendChild( zoomBtn );
		top.appendChild( orig );
		top.appendChild( close );
		const track = el( 'div', 'crw-lb-track' );
		const prev = el( 'button', 'crw-lb-arrow crw-lb-prev', { type: 'button', 'aria-label': 'Previous screenshot', text: '‹' } );
		const next = el( 'button', 'crw-lb-arrow crw-lb-next', { type: 'button', 'aria-label': 'Next screenshot', text: '›' } );
		const caption = el( 'div', 'crw-lb-caption' );
		dialog.appendChild( top );
		dialog.appendChild( track );
		dialog.appendChild( prev );
		dialog.appendChild( next );
		dialog.appendChild( caption );
		document.body.appendChild( dialog );

		const st = { items: [], index: 0, urls: null, onClose: null, pushed: false, anchorPrefix: '', slides: [], inerted: [], scrollY: 0 };

		function setZoom( on, point ) {
			const s = st.slides[ st.index ];
			if ( !s ) {
				return;
			}
			const img = s.img;
			if ( on && !img.naturalWidth ) {
				return;
			}
			if ( on ) {
				const fitW = img.clientWidth;
				const rect = img.getBoundingClientRect();
				const fx = point ? ( point.x - rect.left ) / rect.width : 0.5;
				const fy = point ? ( point.y - rect.top ) / rect.height : 0;
				s.box.classList.add( 'is-zoomed' );
				const zoomW = Math.max( img.naturalWidth, fitW * 2 );
				img.style.width = zoomW + 'px';
				s.box.scrollLeft = fx * img.clientWidth - s.box.clientWidth / 2;
				s.box.scrollTop = fy * img.clientHeight - s.box.clientHeight / 2;
			} else {
				s.box.classList.remove( 'is-zoomed' );
				img.style.width = '';
			}
			zoomBtn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			zoomBtn.textContent = on ? 'Fit' : 'Zoom';
			track.classList.toggle( 'is-locked', on );
		}

		function loadAround() {
			if ( !st.urls ) {
				return;
			}
			st.slides.forEach( ( s, i ) => {
				if ( Math.abs( i - st.index ) <= 1 && !s.img.getAttribute( 'src' ) ) {
					const rec = st.urls[ st.items[ i ].title ];
					if ( rec ) {
						s.img.src = rec.sized;
					}
				}
			} );
		}

		function render() {
			const i = st.index;
			const total = st.items.length;
			count.textContent = ( i + 1 ) + ' / ' + total;
			caption.innerHTML = '';
			if ( st.items[ i ].caption ) {
				caption.appendChild( st.items[ i ].caption.cloneNode( true ) );
			}
			const rec = st.urls && st.urls[ st.items[ i ].title ];
			orig.href = rec ? rec.original : ( st.items[ i ].fileHref || '#' );
			prev.disabled = i === 0;
			next.disabled = i === total - 1;
			st.slides.forEach( ( s, k ) => s.box.setAttribute( 'aria-hidden', k === i ? 'false' : 'true' ) );
			loadAround();
			if ( st.pushed && st.anchorPrefix ) {
				history.replaceState( history.state, '', '#' + st.anchorPrefix + ( i + 1 ) );
			}
		}

		const follower = followTrack( track, ( i ) => {
			if ( dialog.open && i !== st.index && i >= 0 && i < st.items.length ) {
				setZoom( false );
				st.index = i;
				render();
			}
		} );

		function go( i, smooth ) {
			const total = st.items.length;
			const n = Math.max( 0, Math.min( total - 1, i ) );
			if ( n !== st.index ) {
				setZoom( false );
			}
			st.index = n;
			follower.travel( n );
			scrollTrackTo( track, n, smooth );
			render();
		}

		prev.addEventListener( 'click', () => go( st.index - 1, true ) );
		next.addEventListener( 'click', () => go( st.index + 1, true ) );
		zoomBtn.addEventListener( 'click', () => setZoom( !st.slides[ st.index ].box.classList.contains( 'is-zoomed' ) ) );
		close.addEventListener( 'click', () => requestClose() );
		caption.addEventListener( 'click', ( e ) => {
			const a = e.target.closest && e.target.closest( 'a[href^="#"]' );
			if ( a ) {
				/* Jumping to a table row: close first so the page can scroll. */
				e.preventDefault();
				const target = a.getAttribute( 'href' );
				requestClose( () => {
					location.hash = target;
				} );
			}
		} );
		dialog.addEventListener( 'click', ( e ) => {
			if ( e.target === dialog || e.target === track || e.target.classList.contains( 'crw-lb-slide' ) ) {
				requestClose();
			}
		} );
		document.addEventListener( 'keydown', ( e ) => {
			if ( !dialog.open ) {
				return;
			}
			if ( e.key === 'Escape' ) {
				e.preventDefault();
				requestClose();
			} else if ( e.key === 'Tab' ) {
				/* Keep keyboard focus inside the viewer. */
				const focusable = Array.prototype.filter.call(
					dialog.querySelectorAll( 'button, a[href]' ),
					( n ) => !n.disabled && n.offsetParent !== null
				);
				if ( !focusable.length ) {
					return;
				}
				const first = focusable[ 0 ];
				const last = focusable[ focusable.length - 1 ];
				if ( !dialog.contains( document.activeElement ) ) {
					first.focus();
					e.preventDefault();
				} else if ( e.shiftKey && document.activeElement === first ) {
					last.focus();
					e.preventDefault();
				} else if ( !e.shiftKey && document.activeElement === last ) {
					first.focus();
					e.preventDefault();
				}
			} else if ( e.key === 'ArrowRight' ) {
				go( st.index + 1, true );
				e.preventDefault();
			} else if ( e.key === 'ArrowLeft' ) {
				go( st.index - 1, true );
				e.preventDefault();
			} else if ( e.key === 'Home' ) {
				go( 0, true );
				e.preventDefault();
			} else if ( e.key === 'End' ) {
				go( st.items.length - 1, true );
				e.preventDefault();
			} else if ( e.key === 'z' || e.key === 'Z' ) {
				setZoom( !st.slides[ st.index ].box.classList.contains( 'is-zoomed' ) );
			}
		} );

		/* Pull down (when not zoomed) to close, like the phone photo viewers. */
		let touchStart = null;
		track.addEventListener( 'touchstart', ( e ) => {
			if ( e.touches.length === 1 && !st.slides[ st.index ].box.classList.contains( 'is-zoomed' ) ) {
				touchStart = { x: e.touches[ 0 ].clientX, y: e.touches[ 0 ].clientY };
			} else {
				touchStart = null;
			}
		}, { passive: true } );
		track.addEventListener( 'touchend', ( e ) => {
			if ( !touchStart || !e.changedTouches.length ) {
				return;
			}
			const dx = e.changedTouches[ 0 ].clientX - touchStart.x;
			const dy = e.changedTouches[ 0 ].clientY - touchStart.y;
			touchStart = null;
			if ( dy > 110 && Math.abs( dy ) > Math.abs( dx ) * 2 ) {
				requestClose();
			}
		}, { passive: true } );

		function finishClose( after ) {
			if ( !dialog.open ) {
				return;
			}
			setZoom( false );
			const lastIndex = st.index;
			dialog.close();
			document.documentElement.classList.remove( 'crw-lb-open' );
			st.inerted.forEach( ( n ) => n.removeAttribute( 'inert' ) );
			st.inerted = [];
			const cb = st.onClose;
			st.onClose = null;
			if ( cb ) {
				cb( lastIndex );
			}
			if ( after ) {
				after();
			} else {
				/* Put the page back where the reader was. Chromium scrolls to the URL's
				   fragment when Back returns to it; undo that once it has happened. */
				const y = st.scrollY;
				const restore = () => {
					if ( Math.abs( window.scrollY - y ) > 2 ) {
						window.scrollTo( 0, y );
					}
				};
				restore();
				setTimeout( restore, 80 );
				setTimeout( restore, 400 );
				window.addEventListener( 'hashchange', restore, { once: true } );
				setTimeout( () => window.removeEventListener( 'hashchange', restore ), 1500 );
			}
		}

		function requestClose( after ) {
			if ( st.pushed && history.state && history.state.crwLightbox ) {
				st.pushed = false;
				st.afterPop = after || null;
				history.back();
			} else {
				st.pushed = false;
				finishClose( after );
			}
		}

		window.addEventListener( 'popstate', () => {
			if ( dialog.open && !( history.state && history.state.crwLightbox ) ) {
				st.pushed = false;
				const after = st.afterPop;
				st.afterPop = null;
				finishClose( after );
			}
		} );

		function buildSlides() {
			track.innerHTML = '';
			st.slides = st.items.map( ( item ) => {
				const slide = el( 'div', 'crw-lb-slide' );
				const box = el( 'div', 'crw-lb-box' );
				const img = el( 'img', 'crw-lb-img', { alt: item.alt, decoding: 'async', draggable: 'false' } );
				img.addEventListener( 'click', ( e ) => {
					e.stopPropagation();
					setZoom( !box.classList.contains( 'is-zoomed' ), { x: e.clientX, y: e.clientY } );
				} );
				box.appendChild( img );
				slide.appendChild( box );
				track.appendChild( slide );
				return { slide: slide, box: box, img: img };
			} );
		}

		lightbox = {
			open: ( opts ) => {
				if ( dialog.open ) {
					return;
				}
				st.items = opts.items;
				st.index = opts.index;
				st.onClose = opts.onClose;
				st.anchorPrefix = opts.anchorPrefix;
				st.urls = null;
				st.scrollY = window.scrollY;
				buildSlides();
				document.documentElement.classList.add( 'crw-lb-open' );
				dialog.classList.toggle( 'crw-lb-counter-invert', /invert/.test( getComputedStyle( document.documentElement ).filter || '' ) );
				st.inerted = Array.prototype.filter.call( document.body.children, ( n ) => n !== dialog && !n.hasAttribute( 'inert' ) && n.tagName !== 'SCRIPT' );
				st.inerted.forEach( ( n ) => n.setAttribute( 'inert', '' ) );
				dialog.show();
				follower.width = track.clientWidth;
				follower.travel( st.index );
				scrollTrackTo( track, st.index, false );
				render();
				close.focus( { preventScroll: true } );
				const vw = Math.max( window.innerWidth, window.innerHeight * 1.5 );
				const want = bucketFor( vw * Math.min( window.devicePixelRatio || 1, 2 ) );
				fetchUrls( opts.titles, want ).then( ( urls ) => {
					st.urls = urls;
					render();
				} );
				try {
					const url = st.anchorPrefix ? '#' + st.anchorPrefix + ( st.index + 1 ) : location.href;
					history.pushState( { crwLightbox: true }, '', url );
					st.pushed = true;
				} catch ( err ) {
					st.pushed = false;
				}
			}
		};
		window.addEventListener( 'resize', () => {
			if ( dialog.open ) {
				follower.width = track.clientWidth;
				follower.travel( st.index );
				scrollTrackTo( track, st.index, false );
			}
		} );
		return lightbox;
	}

	function init( $content ) {
		const scope = ( $content && $content[ 0 ] ) || document;
		scope.querySelectorAll( '.crw-gallery:not(.crw-gallery-ready)' ).forEach( ( wrapper ) => {
			try {
				buildViewer( wrapper );
			} catch ( err ) {
				wrapper.classList.add( 'crw-gallery-failed' );
				mw.log.error( err );
			}
		} );
	}

	if ( window.mw && mw.hook ) {
		mw.loader.using( [ 'mediawiki.api' ] ).then( () => {
			mw.hook( 'wikipage.content' ).add( init );
		} );
	}
}() );
/* END crw-gallery */

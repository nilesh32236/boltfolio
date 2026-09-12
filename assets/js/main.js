/**
 * Boltfolio front-end behaviour.
 *
 * No dependencies. Everything here is progressive enhancement: the
 * pages are complete and usable before a single line runs.
 *
 *  1. Live waterfall   — draws this page's real navigation timing
 *  2. Metric strip     — the same measurement, as four numbers
 *  3. Code blocks      — language label + copy button
 *  4. Docs TOC         — built from headings, with scroll-spy
 *  5. Docs search      — local index, ⌘K
 *  6. Reveal           — one orchestrated entrance, motion permitting
 *
 * The mobile menu and the docs sidebar toggle deliberately live inline
 * in the document head instead of here: optimisation plugins rewrite
 * deferred scripts into inert ones, and a menu that needs a script to
 * have been *allowed to run* is a menu that eventually breaks.
 */
( function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* =========================================================
	   Small helpers
	   ========================================================= */
	function $( selector, scope ) {
		return ( scope || document ).querySelector( selector );
	}

	function $$( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function onReady( fn ) {
		// Deferred and delayed scripts can both land after the event has
		// already fired, so check the current state rather than assume.
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', fn );
		} else {
			fn();
		}
	}

	function ms( value ) {
		if ( ! value || value < 0 ) {
			return null;
		}

		return value < 10 ? Math.round( value * 10 ) / 10 : Math.round( value );
	}

	function formatMs( value ) {
		if ( null === value ) {
			return '—';
		}

		return value >= 1000 ? ( value / 1000 ).toFixed( 2 ) + ' s' : value + ' ms';
	}

	function formatBytes( bytes ) {
		if ( ! bytes ) {
			return '—';
		}

		if ( bytes < 1024 ) {
			return bytes + ' B';
		}

		if ( bytes < 1024 * 1024 ) {
			return ( bytes / 1024 ).toFixed( 0 ) + ' KB';
		}

		return ( bytes / 1024 / 1024 ).toFixed( 2 ) + ' MB';
	}

	/* =========================================================
	   1 + 2. Live waterfall and metric strip

	   The hero shows the actual load of the page you are reading,
	   read from the Navigation Timing API. Nothing is invented: if a
	   phase did not happen, it is reported as absent rather than
	   filled with a plausible number.
	   ========================================================= */
	function readNavigation() {
		var entries = performance.getEntriesByType ? performance.getEntriesByType( 'navigation' ) : [];
		var nav = entries && entries[ 0 ];

		if ( ! nav || 'number' !== typeof nav.responseStart || 0 === nav.responseStart ) {
			return null;
		}

		var phases = [
			{ key: 'redirect', label: 'Redirect', start: nav.redirectStart, end: nav.redirectEnd },
			{ key: 'dns', label: 'DNS', start: nav.domainLookupStart, end: nav.domainLookupEnd },
			{ key: 'tcp', label: 'Connect', start: nav.connectStart, end: nav.secureConnectionStart > 0 ? nav.secureConnectionStart : nav.connectEnd },
			{ key: 'tls', label: 'TLS', start: nav.secureConnectionStart, end: nav.secureConnectionStart > 0 ? nav.connectEnd : 0 },
			{ key: 'ttfb', label: 'Waiting', start: nav.requestStart, end: nav.responseStart },
			{ key: 'download', label: 'Download', start: nav.responseStart, end: nav.responseEnd },
			{ key: 'dom', label: 'Parse', start: nav.responseEnd, end: nav.domContentLoadedEventEnd }
		];

		var total = nav.duration || nav.loadEventEnd || nav.responseEnd;

		return {
			nav: nav,
			phases: phases,
			total: total,
			ttfb: nav.responseStart,
			weight: nav.transferSize || nav.encodedBodySize || 0,
			requests: performance.getEntriesByType( 'resource' ).length + 1
		};
	}

	function paintMarks() {
		var marks = [];
		var paints = performance.getEntriesByType ? performance.getEntriesByType( 'paint' ) : [];

		paints.forEach( function ( entry ) {
			if ( 'first-contentful-paint' === entry.name ) {
				marks.push( { key: 'paint', label: 'First paint', at: entry.startTime } );
			}
		} );

		return marks;
	}

	function renderWaterfall( root, data ) {
		var rows = $( '[data-wf-rows]', root );
		var axis = $( '[data-wf-axis]', root );
		var status = $( '[data-wf-status]', root );

		if ( ! rows ) {
			return;
		}

		var marks = paintMarks();
		var total = Math.max( data ? data.total : 0, marks.length ? marks[ marks.length - 1 ].at : 0 );

		if ( ! total ) {
			root.setAttribute( 'data-state', 'unsupported' );
			return;
		}

		// Build the ordered row list: real phases first, then paint marks.
		var list = [];

		if ( data ) {
			data.phases.forEach( function ( phase ) {
				var duration = ms( phase.end - phase.start );

				if ( null === duration || duration <= 0 ) {
					return;
				}

				list.push( {
					key: phase.key,
					label: phase.label,
					start: phase.start,
					duration: duration
				} );
			} );
		}

		marks.forEach( function ( mark ) {
			list.push( {
				key: mark.key,
				label: mark.label,
				start: 0,
				duration: ms( mark.at ),
				isMark: true
			} );
		} );

		// Paint marks are cumulative points, not spans; show them at their
		// position so the eye reads "the page was visible here".
		list = list.map( function ( row ) {
			if ( row.isMark ) {
				return { key: row.key, label: row.label, left: row.duration, width: 0, value: row.duration };
			}

			return {
				key: row.key,
				label: row.label,
				left: row.start,
				width: row.duration,
				value: row.duration
			};
		} );

		var max = Math.max.apply( null, list.map( function ( row ) {
			return row.left + row.width;
		} ).concat( [ total ] ) );

		rows.innerHTML = '';

		list.forEach( function ( row, index ) {
			var li = document.createElement( 'li' );
			li.className = 'wf__row';
			li.setAttribute( 'data-phase', row.key );
			li.setAttribute( 'data-key', row.key );

			var label = document.createElement( 'span' );
			label.className = 'wf__label';
			label.textContent = row.label;

			var track = document.createElement( 'span' );
			track.className = 'wf__track';

			var bar = document.createElement( 'span' );
			bar.className = 'wf__bar';

			var leftPct = ( row.left / max ) * 100;
			var widthPct = row.isMark ? 0 : Math.max( ( row.width / max ) * 100, 0.4 );

			if ( row.isMark ) {
				// A paint mark is a moment: render it as a 2px tick.
				bar.style.left = 'calc(' + leftPct + '% - 1px)';
				bar.style.width = '2px';
				bar.style.opacity = '1';
			} else {
				bar.style.left = leftPct + '%';
				bar.style.width = '0';
			}

			track.appendChild( bar );

			var value = document.createElement( 'span' );
			value.className = 'wf__ms';
			value.textContent = formatMs( row.value );

			li.appendChild( label );
			li.appendChild( track );
			li.appendChild( value );
			rows.appendChild( li );

			// Draw in sequence, but never at the cost of waiting.
			if ( row.isMark ) {
				return;
			}

			var delay = reduceMotion ? 0 : 60 * index;

			window.setTimeout( function () {
				bar.style.width = widthPct + '%';
			}, delay );
		} );

		if ( axis ) {
			axis.innerHTML = '';

			[ 0, 0.25, 0.5, 0.75, 1 ].forEach( function ( fraction ) {
				var tick = document.createElement( 'span' );
				tick.textContent = formatMs( ms( max * fraction ) );
				axis.appendChild( tick );
			} );
		}

		root.setAttribute( 'data-state', 'done' );

		if ( status ) {
			status.innerHTML = '<span class="wf__dot" aria-hidden="true"></span>' + formatMs( ms( total ) ) + ' total';
		}
	}

	function renderMetrics( data ) {
		var strip = $( '[data-metrics]' );

		if ( ! strip ) {
			return;
		}

		var values = {
			ttfb: data ? ms( data.ttfb ) : null,
			weight: data ? data.weight : 0,
			requests: data ? data.requests : null,
			lcp: null
		};

		function apply( key, text ) {
			var node = $( '[data-metric="' + key + '"]', strip );

			if ( ! node ) {
				return;
			}

			node.textContent = text;
			node.removeAttribute( 'data-pending' );
		}

		apply( 'ttfb', formatMs( values.ttfb ) );
		apply( 'weight', formatBytes( values.weight ) );
		apply( 'requests', values.requests ? String( values.requests ) : '—' );

		// LCP arrives later than everything else, so it fills in on its own.
		if ( 'PerformanceObserver' in window ) {
			try {
				var observer = new PerformanceObserver( function ( entryList ) {
					var entries = entryList.getEntries();
					var last = entries[ entries.length - 1 ];

					if ( last ) {
						apply( 'lcp', formatMs( ms( last.startTime ) ) );
						observer.disconnect();
					}
				} );

				observer.observe( { type: 'largest-contentful-paint', buffered: true } );
			} catch ( error ) {
				apply( 'lcp', '—' );
			}
		} else {
			apply( 'lcp', '—' );
		}
	}

	function initWaterfall() {
		var root = $( '[data-waterfall]' );

		if ( ! root ) {
			return;
		}

		if ( ! ( 'performance' in window ) || ! performance.getEntriesByType ) {
			root.setAttribute( 'data-state', 'unsupported' );
			return;
		}

		var data = readNavigation();

		root.setAttribute( 'data-state', 'live' );

		var run = function () {
			renderWaterfall( root, data );
			renderMetrics( data );
		};

		// Let the first paint land before drawing, so the chart is not
		// competing with the page's own critical path.
		if ( 'requestAnimationFrame' in window ) {
			window.requestAnimationFrame( function () {
				window.setTimeout( run, reduceMotion ? 0 : 240 );
			} );
		} else {
			run();
		}
	}

	/* =========================================================
	   3. Code blocks
	   ========================================================= */
	function detectLanguage( pre ) {
		var code = $( 'code[class*="language-"]', pre );

		if ( code ) {
			var match = code.className.match( /language-([\w+-]+)/ );

			if ( match ) {
				return match[ 1 ];
			}
		}

		if ( pre.hasAttribute( 'data-lang' ) ) {
			return pre.getAttribute( 'data-lang' );
		}

		return 'code';
	}

	var COPY_ICON = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
	var DONE_ICON = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';

	function copyText( text, button ) {
		var done = function () {
			button.innerHTML = DONE_ICON + ' Copied';
			button.classList.add( 'is-copied' );

			window.setTimeout( function () {
				button.innerHTML = COPY_ICON + ' Copy';
				button.classList.remove( 'is-copied' );
			}, 1800 );
		};

		var fallback = function () {
			var area = document.createElement( 'textarea' );
			area.value = text;
			area.style.position = 'fixed';
			area.style.opacity = '0';
			document.body.appendChild( area );
			area.select();

			try {
				document.execCommand( 'copy' );
				done();
			} catch ( error ) {
				// Clipboard is unavailable; leave the button as it was.
			}

			document.body.removeChild( area );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done ).catch( fallback );
		} else {
			fallback();
		}
	}

	function enhanceCodeBlocks( scope ) {
		$$( 'pre:not(.no-enhance)', scope || document ).forEach( function ( pre ) {
			if ( pre.parentElement && pre.parentElement.classList.contains( 'code-block' ) ) {
				return;
			}

			var wrapper = document.createElement( 'div' );
			wrapper.className = 'code-block';

			var bar = document.createElement( 'div' );
			bar.className = 'code-block-bar';

			var lang = document.createElement( 'span' );
			lang.className = 'code-block-lang';
			lang.textContent = detectLanguage( pre );

			var button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'code-block-copy';
			button.setAttribute( 'aria-label', 'Copy code to clipboard' );
			button.innerHTML = COPY_ICON + ' Copy';

			button.addEventListener( 'click', function () {
				copyText( pre.innerText, button );
			} );

			bar.appendChild( lang );
			bar.appendChild( button );

			pre.parentNode.insertBefore( wrapper, pre );
			wrapper.appendChild( bar );
			wrapper.appendChild( pre );
		} );
	}

	/* =========================================================
	   4. "On this page"
	   ========================================================= */
	function buildDocsToc() {
		var container = $( '.docs-toc' );

		if ( ! container ) {
			return;
		}

		var list = $( '.docs-toc-list', container );
		var contentRoot = $( '.docs-content .entry-content' );

		if ( ! list || ! contentRoot ) {
			container.hidden = true;
			return;
		}

		var headings = $$( 'h2[id], h3[id]', contentRoot ).filter( function ( node, index, all ) {
			// Skip an h3 that appears before any h2 — it belongs to the
			// page header rather than to a section.
			if ( 'H3' !== node.tagName ) {
				return true;
			}

			return all.slice( 0, index ).some( function ( earlier ) {
				return 'H2' === earlier.tagName;
			} );
		} );

		if ( headings.length < 2 ) {
			container.hidden = true;
			return;
		}

		var layout = $( '.docs-layout' );

		if ( layout ) {
			layout.classList.add( 'has-toc' );
		}

		var links = [];

		headings.forEach( function ( heading, index ) {
			var item = document.createElement( 'li' );
			item.setAttribute( 'data-level', 'H3' === heading.tagName ? '3' : '2' );

			var link = document.createElement( 'a' );
			link.href = '#' + heading.id;

			var num = document.createElement( 'span' );
			num.className = 'toc-num';
			num.textContent = String( index + 1 ).padStart( 2, '0' );

			link.appendChild( num );
			link.appendChild( document.createTextNode( heading.textContent.trim() ) );

			item.appendChild( link );
			list.appendChild( item );
			links.push( link );
		} );

		list.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a' );

			if ( ! link ) {
				return;
			}

			links.forEach( function ( other ) {
				other.classList.remove( 'is-active' );
			} );

			link.classList.add( 'is-active' );
		} );

		if ( ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var visible = new Map();
		var byId = {};

		headings.forEach( function ( heading, index ) {
			byId[ heading.id ] = links[ index ];
		} );

		var setActive = function ( id ) {
			links.forEach( function ( link ) {
				link.classList.remove( 'is-active' );
			} );

			if ( id && byId[ id ] ) {
				byId[ id ].classList.add( 'is-active' );
			}
		};

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					visible.set( entry.target.id, entry.isIntersecting );
				} );

				var bestId = '';
				var bestTop = Infinity;

				headings.forEach( function ( heading ) {
					if ( ! visible.get( heading.id ) ) {
						return;
					}

					var top = heading.getBoundingClientRect().top;

					if ( top < bestTop ) {
						bestTop = top;
						bestId = heading.id;
					}
				} );

				if ( ! bestId && window.scrollY < 200 && headings[ 0 ] ) {
					bestId = headings[ 0 ].id;
				}

				setActive( bestId );
			},
			{ rootMargin: '-14% 0px -58% 0px' }
		);

		headings.forEach( function ( heading ) {
			observer.observe( heading );
		} );
	}

	/* =========================================================
	   5. Documentation search
	   ========================================================= */
	function initSearch() {
		var modal = $( '[data-search-modal]' );

		if ( ! modal ) {
			return;
		}

		var configNode = document.getElementById( 'boltfolio-search-config' );
		var config = {};

		if ( configNode ) {
			try {
				config = JSON.parse( configNode.textContent );
			} catch ( error ) {
				config = {};
			}
		}

		var input = $( '[data-search-input]', modal );
		var results = $( '[data-search-results]', modal );
		var scopeLabel = $( '[data-search-scope]', modal );
		var index = null;
		var loading = false;
		var active = -1;
		var current = [];

		// Match the platform's own modifier hint rather than always
		// showing the macOS glyph.
		var isMac = /Mac|iPhone|iPad/.test( navigator.platform || navigator.userAgent );
		$$( '.nav-search kbd' ).forEach( function ( node ) {
			node.textContent = isMac ? '⌘K' : 'Ctrl K';
		} );

		function open() {
			modal.setAttribute( 'data-open', 'true' );
			document.documentElement.style.overflow = 'hidden';

			if ( input ) {
				input.focus();
				input.select();
			}

			load();
		}

		function close() {
			modal.setAttribute( 'data-open', 'false' );
			document.documentElement.style.overflow = '';

			if ( input ) {
				input.setAttribute( 'aria-expanded', 'false' );
			}
		}

		function load() {
			if ( index || loading || ! config.endpoint ) {
				return;
			}

			loading = true;

			fetch( config.endpoint, { credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( data ) {
					index = Array.isArray( data ) ? data : [];
					loading = false;

					if ( scopeLabel ) {
						scopeLabel.textContent = index.length + ( index.length === 1 ? ' page' : ' pages' );
					}

					if ( input && input.value.trim() ) {
						run( input.value );
					}
				} )
				.catch( function () {
					loading = false;
					index = [];
				} );
		}

		function score( record, terms ) {
			var title = record.title.toLowerCase();
			var haystack = record.key;
			var total = 0;

			for ( var i = 0; i < terms.length; i++ ) {
				var term = terms[ i ];
				var at = haystack.indexOf( term );

				if ( -1 === at ) {
					// Allow an abbreviation like "clmain" to match "class-main".
					return -1;
				}

				total += title.indexOf( term ) === 0 ? 60 : ( title.indexOf( term ) > -1 ? 30 : 8 );
				total += Math.max( 0, 12 - at / 4 );
			}

			return total;
		}

		function escapeHtml( value ) {
			return value.replace( /[&<>"']/g, function ( character ) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ character ];
			} );
		}

		function highlight( title, terms ) {
			var safe = escapeHtml( title );
			var lowered = safe.toLowerCase();

			// Highlight the longest matching term only, so overlapping
			// terms cannot double-wrap the same characters.
			var best = terms.slice().sort( function ( a, b ) {
				return b.length - a.length;
			} )[ 0 ];

			if ( ! best ) {
				return safe;
			}

			var at = lowered.indexOf( escapeHtml( best ).toLowerCase() );

			if ( -1 === at ) {
				return safe;
			}

			var end = at + best.length;

			return safe.slice( 0, at ) + '<mark>' + safe.slice( at, end ) + '</mark>' + safe.slice( end );
		}

		function render( matches, terms ) {
			results.innerHTML = '';
			active = -1;
			current = matches;

			if ( ! matches.length ) {
				var empty = document.createElement( 'li' );
				empty.className = 'search-results__empty';
				empty.textContent = config.empty || 'No pages match that search.';
				results.appendChild( empty );
				input.setAttribute( 'aria-expanded', 'false' );
				return;
			}

			matches.slice( 0, 24 ).forEach( function ( record ) {
				var item = document.createElement( 'li' );
				var link = document.createElement( 'a' );
				link.href = record.url;
				link.setAttribute( 'role', 'option' );

				var title = document.createElement( 'span' );
				title.className = 'search-results__title';
				title.innerHTML = highlight( record.title, terms );

				link.appendChild( title );

				var trail = record.trail || '';

				if ( record.heads && record.heads.length ) {
					trail = trail ? trail + ' › ' + record.heads[ 0 ].text : record.heads[ 0 ].text;
				}

				if ( trail ) {
					var path = document.createElement( 'span' );
					path.className = 'search-results__path';
					path.textContent = trail;
					link.appendChild( path );
				}

				item.appendChild( link );
				results.appendChild( item );
			} );

			input.setAttribute( 'aria-expanded', 'true' );
		}

		function run( value ) {
			var query = value.trim().toLowerCase();

			if ( ! query ) {
				results.innerHTML = '';
				current = [];
				input.setAttribute( 'aria-expanded', 'false' );
				return;
			}

			if ( ! index ) {
				load();
				return;
			}

			var terms = query.split( /\s+/ ).filter( Boolean );

			var matches = index
				.map( function ( record ) {
					return { record: record, points: score( record, terms ) };
				} )
				.filter( function ( entry ) {
					return entry.points >= 0;
				} )
				.sort( function ( a, b ) {
					return b.points - a.points;
				} )
				.map( function ( entry ) {
					return entry.record;
				} );

			render( matches, terms );
		}

		function move( delta ) {
			var links = $$( 'a', results );

			if ( ! links.length ) {
				return;
			}

			active = ( active + delta + links.length ) % links.length;

			links.forEach( function ( link, index ) {
				link.setAttribute( 'data-active', index === active ? 'true' : 'false' );
			} );

			links[ active ].scrollIntoView( { block: 'nearest' } );
		}

		$$( '[data-search-open]' ).forEach( function ( trigger ) {
			trigger.addEventListener( 'click', open );
		} );

		$$( '[data-search-close]', modal ).forEach( function ( trigger ) {
			trigger.addEventListener( 'click', close );
		} );

		if ( input ) {
			input.addEventListener( 'input', function () {
				run( input.value );
			} );

			input.addEventListener( 'keydown', function ( event ) {
				if ( 'ArrowDown' === event.key ) {
					event.preventDefault();
					move( 1 );
				} else if ( 'ArrowUp' === event.key ) {
					event.preventDefault();
					move( -1 );
				} else if ( 'Enter' === event.key ) {
					var links = $$( 'a', results );
					var target = links[ active > -1 ? active : 0 ];

					if ( target ) {
						event.preventDefault();
						window.location.href = target.href;
					}
				}
			} );
		}

		document.addEventListener( 'keydown', function ( event ) {
			var open_ = 'true' === modal.getAttribute( 'data-open' );
			var typing = /^(INPUT|TEXTAREA|SELECT)$/.test( document.activeElement.tagName ) || document.activeElement.isContentEditable;

			if ( ( event.metaKey || event.ctrlKey ) && 'k' === event.key.toLowerCase() ) {
				event.preventDefault();
				open_ ? close() : open();
				return;
			}

			if ( 'Escape' === event.key && open_ ) {
				close();
				return;
			}

			if ( '/' === event.key && ! open_ && ! typing ) {
				event.preventDefault();
				open();
			}
		} );
	}

	/* =========================================================
	   6. Reveal
	   ========================================================= */
	function initReveal() {
		var targets = $$( '.reveal' );

		if ( ! targets.length ) {
			return;
		}

		if ( reduceMotion || ! ( 'IntersectionObserver' in window ) ) {
			targets.forEach( function ( node ) {
				node.classList.add( 'is-in' );
			} );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					entry.target.classList.add( 'is-in' );
					observer.unobserve( entry.target );
				} );
			},
			{ rootMargin: '0px 0px -12% 0px', threshold: 0.05 }
		);

		targets.forEach( function ( node ) {
			observer.observe( node );
		} );
	}

	/* =========================================================
	   Boot
	   ========================================================= */
	onReady( function () {
		initWaterfall();
		enhanceCodeBlocks();
		buildDocsToc();
		initSearch();
		initReveal();
	} );
} )();

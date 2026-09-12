/**
 * Shared headless WordPress-blocks environment for the docs tools.
 *
 * Sets up jsdom globals BEFORE requiring @wordpress packages (they touch
 * `window`/`document` at import time), then exposes the block API with
 * core blocks + boltfolio/callout registered for parse/serialize/rawHandler.
 *
 * Standalone tooling only — never loaded by WordPress.
 */
import { JSDOM, VirtualConsole } from 'jsdom';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname( fileURLToPath( import.meta.url ) );

const virtualConsole = new VirtualConsole();
virtualConsole.on( 'jsdomError', () => {} ); // silence jsdom CSS parser noise from bundled styles

const dom = new JSDOM( '', { url: 'http://localhost/', virtualConsole } );
for ( const key of [
	'window', 'document', 'DOMParser', 'XMLSerializer', 'Node', 'NodeFilter',
	'getComputedStyle', 'MutationObserver', 'IntersectionObserver',
	'ResizeObserver', 'Element', 'HTMLElement', 'SVGElement', 'customElements',
	'location', 'matchMedia', 'getSelection',
] ) {
	if ( dom.window[ key ] !== undefined ) {
		globalThis[ key ] = dom.window[ key ];
	}
}
try {
	// Node >=21 ships a getter-only `navigator`; replace it when possible.
	Object.defineProperty( globalThis, 'navigator', {
		value: dom.window.navigator,
		configurable: true,
	} );
} catch {
	/* keep the Node built-in */
}

const require = createRequire( path.join( here, 'noop.js' ) );

export const blocks = require( '@wordpress/blocks' );
const blockLibrary = require( '@wordpress/block-library' );

let ready = false;

/** Register core blocks + boltfolio/callout once. */
export function registerAll() {
	if ( ready ) {
		return;
	}
	blockLibrary.registerCoreBlocks();
	blocks.registerBlockType( {
		name: 'boltfolio/callout',
		apiVersion: 3,
		title: 'Callout',
		category: 'design',
		attributes: {
			type: { type: 'string', default: 'info' },
			title: { type: 'string', default: '' },
		},
		supports: { html: false },
		save: () => null, // dynamic block — markup comes from render.php
	} );
	ready = true;
}

/**
 * Serialize one block. `blocks.serialize()` returns an empty string for
 * dynamic blocks that have no save function (boltfolio/callout renders via
 * render.php) — WordPress stores those as the block comment plus the inner
 * block markup, so we emit exactly that contract by hand. Everything else
 * delegates to blocks.serialize().
 *
 * @param {object} block Block instance.
 * @return {string} Serialized block markup.
 */
export function serializeBlock( block ) {
	if ( block?.name === 'boltfolio/callout' ) {
		const attrs = { type: block.attributes?.type ?? 'info' };
		if ( block.attributes?.title ) {
			attrs.title = block.attributes.title;
		}
		const inner = ( block.innerBlocks ?? [] ).map( serializeBlock ).join( '\n\n' );
		return `<!-- wp:boltfolio/callout ${ JSON.stringify( attrs ) } -->\n${ inner }\n<!-- /wp:boltfolio/callout -->`;
	}
	return blocks.serialize( block );
}

/** Serialize a list of top-level blocks (joined the way WordPress does). */
export function serializeBlocks( list ) {
	return list.map( serializeBlock ).join( '\n\n' );
}

/**
 * Convert a classic-HTML chunk into blocks with rawHandler, then repair the
 * one thing rawHandler loses: heading ids (verified empirically —
 * `<h2 id="x">` yields a plain wp:heading). The table className IS preserved
 * by rawHandler's table transform, so tables only get a count sanity check
 * plus className assignment as a belt-and-braces step.
 *
 * @param {string} html    Classic HTML fragment.
 * @param {object} stats   Aggregator: { headingsAnchored, tablesClassed, zipSkipped }.
 * @return {Array} Blocks.
 */
export function htmlToBlocks( html, stats = { headingsAnchored: 0, tablesClassed: 0, zipSkipped: 0 } ) {
	registerAll();

	// Source facts, in document order.
	const sourceTables = [ ...html.matchAll( /<table\b([^>]*)>/gi ) ].map( ( m ) => {
		const cls = m[ 1 ].match( /class\s*=\s*["']([^"']*)["']/i );
		return cls ? cls[ 1 ].trim() : null;
	} );
	// ALL heading opening tags in order, with or without an id.
	const sourceHeadings = [ ...html.matchAll( /<(h[1-6])\b([^>]*)>/gi ) ].map( ( m ) => ( {
		tag: m[ 1 ].toLowerCase(),
		id: ( m[ 2 ].match( /id\s*=\s*["']([^"']*)["']/i ) || [ null, null ] )[ 1 ],
		level: parseInt( m[ 1 ].slice( 1 ), 10 ),
	} ) );

	const produced = blocks.rawHandler( { HTML: html, mode: 'BLOCKS' } ).filter(
		( b ) => b && typeof b === 'object' && typeof b.name === 'string'
	);

	// --- tables: zip in document order ---------------------------------------
	const tableBlocks = produced.filter( ( b ) => b.name === 'core/table' );
	if ( tableBlocks.length === sourceTables.length ) {
		let ti = 0;
		for ( const b of produced ) {
			if ( b.name !== 'core/table' ) {
				continue;
			}
			const cls = sourceTables[ ti++ ];
			if ( cls ) {
				b.attributes = { ...( b.attributes || {} ) };
				const existing = b.attributes.className ?? '';
				if ( existing && existing !== cls ) {
					b.attributes.className = `${ cls } ${ existing }`;
				} else if ( ! existing ) {
					b.attributes.className = cls;
				}
				stats.tablesClassed++;
			}
		}
	} else {
		stats.zipSkipped++;
		console.error( `[tables] count mismatch: source ${ sourceTables.length } vs blocks ${ tableBlocks.length } — skipped class zip` );
	}

	// --- headings: order-aware zip, level asserted ----------------------------
	const headingBlocks = produced.filter( ( b ) => b.name === 'core/heading' );
	if ( sourceHeadings.length > 0 && headingBlocks.length === sourceHeadings.length ) {
		let hi = 0;
		for ( const b of produced ) {
			if ( b.name !== 'core/heading' ) {
				continue;
			}
			const src = sourceHeadings[ hi++ ];
			const level = b.attributes?.level ?? 2;
			if ( level !== src.level ) {
				stats.zipSkipped++;
				console.error( `[headings] level mismatch at position ${ hi }: h${ src.level } vs block level ${ level } — skipping zip for this file` );
				continue;
			}
			if ( src.id ) {
				b.attributes = { ...( b.attributes || {} ), anchor: src.id };
				stats.headingsAnchored++;
			}
		}
	} else if ( sourceHeadings.length > 0 ) {
		stats.zipSkipped++;
		console.error( `[headings] count mismatch: source ${ sourceHeadings.length } vs blocks ${ headingBlocks.length } — skipped anchor zip` );
	}

	return produced;
}

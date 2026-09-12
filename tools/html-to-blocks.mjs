#!/usr/bin/env node
/**
 * html-to-blocks.mjs — convert classic-HTML documentation fragments into
 * native Gutenberg block markup.
 *
 * Usage (from the theme root):
 *   node tools/html-to-blocks.mjs --in /tmp/opencode/docs-staging --out /tmp/opencode/docs-staging/blocks
 *
 * Runs WITHOUT WordPress: rawHandler/parse/serialize from @wordpress/blocks
 * (core blocks registered from @wordpress/block-library) under jsdom globals.
 *
 * Per fragment:
 *   1. `[callout type="..."]...[/callout]` shortcodes are extracted first and
 *      rebuilt as real boltfolio/callout blocks (dynamic block: comment +
 *      inner core blocks, server-rendered by render.php; note->info).
 *   2. Everything else goes through rawHandler({HTML, mode:'BLOCKS'}).
 *   3. Heading ids -> core/heading anchor attributes + id= re-injected into
 *      the saved markup (rawHandler drops ids; see lib/wp-env.mjs).
 *   4. Table classes (wppo-api-params, ...) preserved as className attributes
 *      (rawHandler already carries them; double-set is harmless + verified).
 *   5. manifest.json files are copied through unchanged.
 *
 * Writes scope: --out directory only. No WordPress/DB interaction.
 */
import fs from 'node:fs';
import path from 'node:path';
import { htmlToBlocks, registerAll, serializeBlocks, blocks } from './lib/wp-env.mjs';

const argv = process.argv.slice( 2 );
const getArg = ( name ) => {
	const i = argv.indexOf( `--${ name }` );
	if ( i !== -1 && argv[ i + 1 ] ) {
		return argv[ i + 1 ];
	}
	const p = argv.find( ( a ) => a.startsWith( `--${ name }=` ) );
	return p ? p.slice( name.length + 3 ) : null;
};

const inDir = getArg( 'in' ) ?? '/tmp/opencode/docs-staging';
const outDir = getArg( 'out' ) ?? '/tmp/opencode/docs-staging/blocks';

if ( ! fs.existsSync( inDir ) ) {
	console.error( `Input directory not found: ${ inDir }` );
	process.exit( 1 );
}

const CALLOUT_RE = /\[callout\b([^\]]*)\]([\s\S]*?)\[\/callout\]/g;
const TYPE_MAP = { note: 'info', info: 'info', tip: 'tip', warning: 'warning' };

registerAll();

const stats = {
	files: 0,
	callouts: 0,
	headingsAnchored: 0,
	tablesClassed: 0,
	zipSkipped: 0,
	fallback: [],
};

/** Parse `key="value" ...` shortcode attribute-ish text. */
function parseShortcodeAttrs( text ) {
	const attrs = {};
	for ( const m of text.matchAll( /(\w+)\s*=\s*"([^"]*)"/g ) ) {
		attrs[ m[ 1 ].toLowerCase() ] = m[ 2 ];
	}
	return attrs;
}

/**
 * Build a boltfolio/callout block. The block is dynamic (render.php, no
 * save.js): the saved markup is the block comment + the serialized inner
 * blocks only, which is exactly what serialize() produces for it.
 */
function buildCallout( attrs, innerHtml ) {
	const type = TYPE_MAP[ ( attrs.type || 'note' ).toLowerCase() ] ?? 'info';
	const title = typeof attrs.title === 'string' && attrs.title.trim() !== '' ? attrs.title.trim() : null;

	const inner = htmlToBlocks( innerHtml.replace( /^\s*\n+/, '' ).replace( /\n+\s*$/, '' ), stats );
	const attributes = { type, ...( title ? { title } : {} ) };

	// Dynamic block: serialize() emits the comment + inner blocks; render.php
	// is responsible for the markup server-side.
	return blocks.createBlock( 'boltfolio/callout', attributes, inner );
}

/** Convert one fragment file to block markup. */
function convertFile( src ) {
	const top = [];
	let last = 0;

	// Unwrap a <p> that contains nothing but the callout shortcode.
	const html = src.replace(
		/<p>\s*(\[callout\b[^\]]*\][\s\S]*?\[\/callout\])\s*<\/p>/g,
		'$1'
	);

	for ( const m of html.matchAll( CALLOUT_RE ) ) {
		const before = html.slice( last, m.index );
		if ( before.trim() !== '' ) {
			top.push( ...htmlToBlocks( before, stats ) );
		}
		const attrs = parseShortcodeAttrs( m[ 1 ] );
		top.push( buildCallout( attrs, m[ 2 ] ) );
		stats.callouts++;
		last = m.index + m[ 0 ].length;
	}
	const tail = html.slice( last );
	if ( tail.trim() !== '' ) {
		top.push( ...htmlToBlocks( tail, stats ) );
	}

	return serializeBlocks( top );
}

// ---------------------------------------------------------------------------
// Run
// ---------------------------------------------------------------------------

const dirs = fs
	.readdirSync( inDir, { withFileTypes: true } )
	.filter( ( d ) => d.isDirectory() )
	.map( ( d ) => d.name )
	.filter( ( name ) => path.resolve( inDir, name ) !== path.resolve( outDir ) ) // never consume our own output
	.sort();

fs.mkdirSync( outDir, { recursive: true } );

for ( const dir of dirs ) {
	const srcDir = path.join( inDir, dir );
	const dstDir = path.join( outDir, dir );
	fs.mkdirSync( dstDir, { recursive: true } );

	const files = fs.readdirSync( srcDir ).filter( ( f ) => f.endsWith( '.html' ) ).sort();
	let dirFallback = 0;

	for ( const file of files ) {
		const src = fs.readFileSync( path.join( srcDir, file ), 'utf8' );
		let out;
		try {
			out = convertFile( src );
			const probe = blocks.parse( out ); // must at least parse cleanly
			const freeform = probe.filter( ( b ) => b.name === 'core/freeform' || b.name === 'core/html' ).length;
			if ( freeform > 0 ) {
				stats.fallback.push( `${ dir }/${ file } (${ freeform } freeform/html block(s))` );
				dirFallback++;
			}
		} catch ( err ) {
			stats.fallback.push( `${ dir }/${ file } (CONVERSION ERROR: ${ err.message })` );
			console.error( `ERROR converting ${ dir }/${ file }: ${ err.message }` );
			continue;
		}
		fs.writeFileSync( path.join( dstDir, file ), out );
		stats.files++;
	}

	const manifest = path.join( srcDir, 'manifest.json' );
	if ( fs.existsSync( manifest ) ) {
		fs.copyFileSync( manifest, path.join( dstDir, 'manifest.json' ) );
	}

	console.log(
		`[${ dir }] converted ${ files.length } files` + ( dirFallback ? ` — ${ dirFallback } need review` : '' )
	);
}

console.log( '---' );
console.log( `Total fragments converted : ${ stats.files }` );
console.log( `Callout blocks built      : ${ stats.callouts }` );
console.log( `Heading anchors (re)set   : ${ stats.headingsAnchored }` );
console.log( `Table classNames ensured  : ${ stats.tablesClassed }` );
console.log( `Zip assertions skipped    : ${ stats.zipSkipped }` );
console.log( `Manual fallback needed    : ${ stats.fallback.length === 0 ? 'none' : '' }` );
for ( const f of stats.fallback ) {
	console.log( `  - ${ f }` );
}

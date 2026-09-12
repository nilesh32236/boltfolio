#!/usr/bin/env node
/**
 * qa-blocks.mjs — QA for the converted Gutenberg block fragments.
 *
 * Usage: node tools/qa-blocks.mjs --in /tmp/opencode/docs-staging --out /tmp/opencode/docs-staging/blocks
 *
 * Checks per output file:
 *   (a) wp.blocks.parse() round-trip: zero core/freeform + core/html blocks
 *       (unparsed classic content), zero core/shortcode, zero blocks with
 *       isValid === false.
 *   (b) zero `[callout` shortcodes remain (converted to boltfolio/callout).
 *   (c) every h2/h3 id in the SOURCE fragment exists in the output as a
 *       heading anchor (comment attribute AND id= in the saved markup).
 *   (d) source <table> count == output core/table count; per-order className
 *       match (wppo-api-params etc.).
 *   (e) no <script>/<style> introduced.
 *   (f) serialize->parse idempotence on one sample file per directory.
 */
import fs from 'node:fs';
import path from 'node:path';
import { registerAll, blocks, serializeBlocks } from './lib/wp-env.mjs';

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

registerAll();

const totals = {
	files: 0,
	freeform: 0,
	shortcode: 0,
	invalid: 0,
	calloutsLeft: 0,
	idsMissing: 0,
	idsChecked: 0,
	tablesSrc: 0,
	tablesOut: 0,
	tablesClassMismatch: 0,
	scriptStyle: 0,
	idempotenceFail: 0,
};
const failures = [];

const dirs = fs
	.readdirSync( outDir, { withFileTypes: true } )
	.filter( ( d ) => d.isDirectory() )
	.map( ( d ) => d.name )
	.sort();

for ( const dir of dirs ) {
	const srcDir = path.join( inDir, dir );
	const outFiles = fs.readdirSync( path.join( outDir, dir ) ).filter( ( f ) => f.endsWith( '.html' ) ).sort();
	const dirStats = { files: outFiles.length, freeform: 0, shortcode: 0, invalid: 0, tables: 0, callouts: 0 };

	for ( const file of outFiles ) {
		const src = fs.readFileSync( path.join( srcDir, file ), 'utf8' );
		const outPath = path.join( outDir, dir, file );
		const out = fs.readFileSync( outPath, 'utf8' );
		totals.files++;

		// (a) parse round-trip / block hygiene
		const parsed = blocks.parse( out );
		const freeform = parsed.filter( ( b ) => b.name === 'core/freeform' || b.name === 'core/html' );
		const shortcode = parsed.filter( ( b ) => b.name === 'core/shortcode' );
		const invalid = parsed.filter( ( b ) => b.isValid === false );
		const freeIn = ( list ) => list.flatMap( ( b ) => b.innerBlocks ?? [] );
		const allBlocks = [ ...parsed, ...freeIn( parsed ), ...freeIn( freeIn( parsed ) ) ];
		const allShortcode = allBlocks.filter( ( b ) => b.name === 'core/shortcode' );
		totals.freeform += freeform.length;
		dirStats.freeform += freeform.length;
		totals.shortcode += allShortcode.length;
		dirStats.shortcode += allShortcode.length;
		totals.invalid += invalid.length;
		dirStats.invalid += invalid.length;
		if ( freeform.length ) {
			failures.push( `${ dir }/${ file }: ${ freeform.length } freeform/html block(s)` );
		}
		if ( allShortcode.length ) {
			failures.push( `${ dir }/${ file }: ${ allShortcode.length } core/shortcode block(s) remain` );
		}
		if ( invalid.length ) {
			failures.push( `${ dir }/${ file }: ${ invalid.length } invalid block(s) (isValid=false)` );
		}

		// (b) no callout shortcodes left in the markup
		const left = ( out.match( /\[callout\b/g ) || [] ).length;
		totals.calloutsLeft += left;
		dirStats.callouts += left;
		if ( left ) {
			failures.push( `${ dir }/${ file }: ${ left } [callout] shortcode(s) left` );
		}

		// (c) heading ids -> anchors
		const srcIds = [ ...src.matchAll( /<h[23]\b[^>]*?\bid="([^"]+)"/gi ) ].map( ( m ) => m[ 1 ] );
		for ( const id of srcIds ) {
			totals.idsChecked++;
			const anchorOk = out.includes( `"anchor":"${ id }"` );
			const markupOk = out.includes( `id="${ id }"` );
			if ( ! anchorOk || ! markupOk ) {
				totals.idsMissing++;
				failures.push( `${ dir }/${ file }: heading id ${ id } missing (anchor:${ anchorOk } markup:${ markupOk })` );
			}
		}

		// (d) tables: count + per-order className
		const srcTables = [ ...src.matchAll( /<table\b([^>]*)>/gi ) ].map( ( m ) => {
			const cls = m[ 1 ].match( /class\s*=\s*["']([^"']*)["']/i );
			return cls ? cls[ 1 ].trim() : null;
		} );
		const outTables = allBlocks.filter( ( b ) => b.name === 'core/table' );
		totals.tablesSrc += srcTables.length;
		totals.tablesOut += outTables.length;
		dirStats.tables += outTables.length;
		if ( srcTables.length !== outTables.length ) {
			totals.tablesClassMismatch++;
			failures.push( `${ dir }/${ file }: table count ${ srcTables.length } != ${ outTables.length }` );
		} else {
			outTables.forEach( ( b, i ) => {
				const want = srcTables[ i ];
				const have = b.attributes?.className ?? null;
				if ( want && have !== want ) {
					totals.tablesClassMismatch++;
					failures.push( `${ dir }/${ file }: table ${ i } className "${ have }" != "${ want }"` );
				}
			} );
		}

		// (e) no script/style introduced
		if ( /<script\b/i.test( out ) || /<style\b/i.test( out ) ) {
			totals.scriptStyle++;
			failures.push( `${ dir }/${ file }: <script>/<style> present` );
		}

		// (f) idempotence — one sample per directory (the first file)
		if ( file === outFiles[ 0 ] ) {
			const reparsed = blocks.parse( out );
			const re = serializeBlocks( reparsed );
			if ( re !== out ) {
				totals.idempotenceFail++;
				let at = 0;
				while ( re[ at ] === out[ at ] ) {
					at++;
				}
				failures.push( `${ dir}/${ file }: NOT idempotent (first diff at char ${ at }: ${ JSON.stringify( out.slice( at, at + 60 ) ) } vs ${ JSON.stringify( re.slice( at, at + 60 ) ) })` );
			}
		}
	}

	console.log(
		`[${ dir }] ${ dirStats.files } files | freeform ${ dirStats.freeform } | shortcode ${ dirStats.shortcode } | invalid ${ dirStats.invalid } | tables ${ dirStats.tables } | leftover callouts ${ dirStats.callouts }`
	);
}

console.log( '---' );
console.log( `Files                        : ${ totals.files }` );
console.log( `freeform/html blocks (a)     : ${ totals.freeform }` );
console.log( `core/shortcode blocks (a,b)  : ${ totals.shortcode }` );
console.log( `invalid blocks (a)           : ${ totals.invalid }` );
console.log( `leftover [callout] (b)       : ${ totals.calloutsLeft }` );
console.log( `heading ids checked (c)      : ${ totals.idsChecked }, missing: ${ totals.idsMissing }` );
console.log( `tables src/out (d)           : ${ totals.tablesSrc } / ${ totals.tablesOut }, class mismatches: ${ totals.tablesClassMismatch }` );
console.log( `script/style leaks (e)       : ${ totals.scriptStyle }` );
console.log( `idempotence failures (f)     : ${ totals.idempotenceFail } (1 sample per dir)` );
console.log( `manifests copied             : ${ dirs.filter( ( d ) => fs.existsSync( path.join( outDir, d, 'manifest.json' ) ) ).length }/${ dirs.length }` );

if ( failures.length ) {
	console.log( `\nFAILURES (${ failures.length }):` );
	for ( const f of failures.slice( 0, 30 ) ) {
		console.log( '  -', f );
	}
	process.exit( 1 );
}
console.log( '\nALL BLOCK QA CHECKS PASSED' );

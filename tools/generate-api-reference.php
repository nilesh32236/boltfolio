<?php
/**
 * Static API-reference generator for the Performance Optimisation plugin.
 *
 * Parses the plugin source with token_get_all() (no WordPress, no plugin
 * includes) and writes WordPress-Codex-style HTML fragments plus a
 * manifest.json for publishing as hierarchical documentation pages.
 *
 * Usage (from the theme root):
 *   php tools/generate-api-reference.php [--out=DIR]
 *
 * Default output directory: /tmp/opencode/docs-staging/g
 *
 * @package Boltfolio\Tools
 */

declare( strict_types = 1 );

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

error_reporting( E_ALL );

// ---------------------------------------------------------------------------
// Configuration / file discovery
// ---------------------------------------------------------------------------

function wppo_docs_norm( string $p ): string {
	return rtrim( str_replace( '\\', '/', $p ), '/' );
}

function wppo_docs_rel( string $plugin_dir, string $abs ): string {
	$l = wppo_docs_norm( $plugin_dir );
	$a = wppo_docs_norm( $abs );
	if ( str_starts_with( $a, $l . '/' ) ) {
		return substr( $a, strlen( $l ) + 1 );
	}
	return basename( $a );
}

/**
 * Explicit scan set: root entry points, includes/*.php, includes/minify/*.php,
 * templates/*.php. vendor/, docs/, tests/, node_modules/, build/ and
 * non-PHP files are never touched.
 *
 * @return array<string,string> rel path => section
 */
function wppo_docs_target_files( string $plugin_dir ): array {
	$files = array();

	foreach ( array( 'performance-optimisation.php', 'uninstall.php' ) as $f ) {
		if ( is_file( $plugin_dir . '/' . $f ) ) {
			$files[ $f ] = 'root';
		}
	}
	// Redis connection helper lives in includes/ but is documented with the root entry points.
	if ( is_file( $plugin_dir . '/includes/redis-connect-helper.php' ) ) {
		$files[ 'includes/redis-connect-helper.php' ] = 'root';
	}

	$inc = glob( $plugin_dir . '/includes/*.php' ) ?: array();
	sort( $inc, SORT_NATURAL );
	foreach ( $inc as $p ) {
		if ( 'redis-connect-helper.php' === basename( $p ) ) {
			continue; // documented with the root entry points per spec
		}
		$files[ wppo_docs_rel( $plugin_dir, $p ) ] = 'includes';
	}

	foreach ( array( 'includes/minify/*.php' => 'minify', 'templates/*.php' => 'templates' ) as $pat => $sec ) {
		$set = glob( $plugin_dir . '/' . $pat ) ?: array();
		sort( $set, SORT_NATURAL );
		foreach ( $set as $p ) {
			$files[ wppo_docs_rel( $plugin_dir, $p ) ] = $sec;
		}
	}

	ksort( $files, SORT_NATURAL );
	return $files;
}

// ---------------------------------------------------------------------------
// Docblock parsing
// ---------------------------------------------------------------------------

/**
 * Parse a raw docblock into structured data.
 *
 * @return array{summary:?string,description:?string,params:array<int,array{type:string,name:string,desc:string}>,return:?array{type:string,desc:string},tags:array<int,string>}
 */
function wppo_docs_parse_docblock( ?string $raw ): array {
	$out = array(
		'summary'     => null,
		'description' => null,
		'params'      => array(),
		'return'      => null,
		'tags'        => array(),
	);
	if ( null === $raw || '' === $raw ) {
		return $out;
	}
	$text  = preg_replace( '/^\/\*\*|\*\/$/', '', trim( $raw ) ) ?? '';
	$lines = array();
	foreach ( explode( "\n", $text ) as $ln ) {
		$lines[] = rtrim( preg_replace( '/^\s*\*\s?/', '', $ln ) ?? '' );
	}

	$desc_lines = array();
	$tags       = array();
	$current    = null;
	foreach ( $lines as $ln ) {
		if ( preg_match( '/^\s*@([A-Za-z_-]+)\s*(.*)$/', $ln, $m ) ) {
			if ( null !== $current ) {
				$tags[] = $current;
			}
			$current = array( $m[1], trim( $m[2] ) );
			continue;
		}
		if ( null !== $current ) {
			if ( '' !== trim( $ln ) ) {
				$current[1] = trim( $current[1] . ' ' . trim( $ln ) );
			} else {
				$tags[]  = $current;
				$current = null;
			}
			continue;
		}
		$desc_lines[] = $ln;
	}
	if ( null !== $current ) {
		$tags[] = $current;
	}

	$paras = array();
	$buf   = array();
	foreach ( $desc_lines as $ln ) {
		if ( '' === trim( $ln ) ) {
			if ( $buf ) {
				$paras[] = trim( implode( ' ', $buf ) );
				$buf     = array();
			}
			continue;
		}
		$buf[] = trim( $ln );
	}
	if ( $buf ) {
		$paras[] = trim( implode( ' ', $buf ) );
	}
	$paras = array_values( array_filter( $paras, static fn( $p ) => '' !== $p ) );
	if ( $paras ) {
		$out['description'] = $paras[0];
		$out['summary']     = wppo_docs_first_sentence( $paras[0] );
	}

	foreach ( $tags as $tag ) {
		[ $name, $rest ] = $tag;
		$lower = strtolower( $name );
		if ( 'param' === $lower ) {
			if ( preg_match( '/^(\S+)\s+(\$\w+)\s*(.*)$/', $rest, $m ) ) {
				// WP hash notation: "@param array $config { ... @type ... }" — drop the opening brace.
				$out['params'][] = array(
					'type' => $m[1],
					'name' => $m[2],
					'desc' => preg_replace( '/^\{\s*/', '', trim( $m[3] ) ) ?? '',
				);
			} elseif ( preg_match( '/^(\S+)\s*(.*)$/', $rest, $m ) ) {
				$out['params'][] = array( 'type' => $m[1], 'name' => '', 'desc' => trim( $m[2] ) );
			}
			continue;
		}
		if ( 'type' === $lower ) {
			// @type sub-parameter: fold into the matching (or last) @param description.
			if ( ! empty( $out['params'] ) ) {
				$target = count( $out['params'] ) - 1;
				if ( preg_match( '/\$(\w+)/', $rest, $tm ) ) {
					foreach ( $out['params'] as $pi => $pp ) {
						if ( ltrim( $pp['name'], '$' ) === $tm[1] ) {
							$target = $pi;
							break;
						}
					}
				}
				$out['params'][ $target ]['desc'] = trim(
					$out['params'][ $target ]['desc'] . ' ' . trim( rtrim( $rest, '}' ) )
				);
			}
			continue;
		}
		if ( 'return' === $lower || 'returns' === $lower ) {
			if ( preg_match( '/^(\S+)\s*(.*)$/', $rest, $m ) ) {
				$out['return'] = array( 'type' => $m[1], 'desc' => trim( $m[2] ) );
			}
			continue;
		}
		$out['tags'][] = '@' . $name . ( '' !== $rest ? ' ' . $rest : '' );
	}
	$out['tags'] = array_values( array_unique( $out['tags'] ) );
	return $out;
}

function wppo_docs_first_sentence( string $p ): string {
	$parts = preg_split( '/(?<=[.!?])\s+(?=[A-Z`\'\[])/u', $p, 2 ) ?: array( $p );
	$s     = trim( $parts[0] );
	return '' !== $s ? $s : $p;
}

/**
 * File-level intro: for plugin-header docblocks (Plugin Name:/Description:)
 * use the Description value; otherwise the first paragraph.
 */
function wppo_docs_file_summary( ?string $raw ): ?string {
	if ( null === $raw ) {
		return null;
	}
	if ( str_contains( $raw, 'Plugin Name:' ) && preg_match( '/^\s*\*\s*Description:\s*(.+)$/m', $raw, $m ) ) {
		return wppo_docs_first_sentence( trim( $m[1] ) );
	}
	return wppo_docs_parse_docblock( $raw )['summary'] ?? null;
}

// ---------------------------------------------------------------------------
// Token parsing
// ---------------------------------------------------------------------------

/**
 * Parse one PHP file into a structure tree using token_get_all().
 */
function wppo_docs_parse_file( string $src, string $rel, string $section ): array {
	$raw = token_get_all( $src, TOKEN_PARSE );

	// Normalise tokens to [id, text, line] with reliable start lines.
	$tokens = array();
	$run    = 1;
	foreach ( $raw as $t ) {
		if ( is_array( $t ) ) {
			$line = $t[2];
			$run  = max( $run, $line ) + substr_count( $t[1], "\n" );
			$tokens[] = array( 'id' => $t[0], 'text' => $t[1], 'line' => $line );
		} else {
			$tokens[] = array( 'id' => null, 'text' => $t, 'line' => $run );
			$run     += substr_count( $t, "\n" );
		}
	}

	$n = count( $tokens );

	// Pass 1: index docblocks with end lines.
	$docblocks = array();
	foreach ( $tokens as $idx => $t ) {
		if ( T_DOC_COMMENT === $t['id'] ) {
			$docblocks[ $idx ] = array(
				'end' => $t['line'] + substr_count( $t['text'], "\n" ),
				'raw' => $t['text'],
			);
		}
	}

	// --- scanner state -------------------------------------------------------
	$stack         = array(); // ['kind'=>'class'|'fn'|'anon'|'block'|'interp', 'idx'=>int]
	$await_fn      = 0;       // function-like declaration awaiting its body brace
	$pending_class = null;    // index into $classes awaiting its body brace
	$pending_anon  = false;
	$ns            = null;

	$classes   = array();
	$functions = array();
	$defines   = array();

	$in_fn = static function () use ( &$stack ): bool {
		foreach ( $stack as $ctx ) {
			if ( 'fn' === $ctx['kind'] || 'anon' === $ctx['kind'] ) {
				return true;
			}
		}
		return false;
	};
	$innermost = static function () use ( &$stack ) {
		return $stack ? $stack[ count( $stack ) - 1 ] : null;
	};

	$next_sig = static function ( int $from ) use ( $tokens, $n ): int {
		for ( $j = $from; $j < $n; $j++ ) {
			$id = $tokens[ $j ]['id'];
			if ( null !== $id && in_array( $id, array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			return $j;
		}
		return $n;
	};

	$doc_for = static function ( int $idx ) use ( $tokens, $docblocks ): ?string {
		for ( $j = $idx - 1; $j >= 0; $j-- ) {
			$id = $tokens[ $j ]['id'];
			if ( T_WHITESPACE === $id || T_COMMENT === $id ) {
				continue;
			}
			if ( T_DOC_COMMENT === $id ) {
				return $docblocks[ $j ]['raw'];
			}
			break;
		}
		return null;
	};

	$collect_name = static function ( int $from ) use ( $tokens, $n ): array {
		$name_ids = array( T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE, T_NS_SEPARATOR );
		$txt      = '';
		$j        = $from;
		while ( $j < $n ) {
			$id = $tokens[ $j ]['id'];
			if ( null !== $id && in_array( $id, $name_ids, true ) ) {
				$txt .= $tokens[ $j ]['text'];
				$j++;
				continue;
			}
			break;
		}
		return array( trim( $txt ), $j );
	};

	/**
	 * Walk backwards over whitespace/comments collecting declaration modifiers.
	 * @return array{0:array<int,string>,1:int} [modifier list, index of first token found]
	 */
	$scan_back_modifiers = static function ( int $idx ) use ( $tokens ): array {
		$mods = array();
		$first = $idx;
		$j     = $idx - 1;
		while ( $j >= 0 ) {
			$id = $tokens[ $j ]['id'];
			if ( T_WHITESPACE === $id || T_COMMENT === $id || T_DOC_COMMENT === $id ) {
				$j--;
				continue;
			}
			if ( in_array( $id, array( T_ABSTRACT, T_FINAL, T_READONLY, T_STATIC, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR ), true ) ) {
				$mods[] = strtolower( $tokens[ $j ]['text'] );
				$first  = $j;
				$j--;
				continue;
			}
			break;
		}
		return array( $mods, $first );
	};

	$norm_ws = static fn( string $s ): string => trim( preg_replace( '/\s+/', ' ', $s ) ?? '' );

	$parse_params = static function ( int $open_idx ) use ( $tokens, $n, $norm_ws ): array {
		$params = array();
		$cur    = array();
		$depth  = 1;
		$j      = $open_idx + 1;
		for ( ; $j < $n; $j++ ) {
			$txt = $tokens[ $j ]['text'];
			$id  = $tokens[ $j ]['id'];
			$openish = ( null === $id && in_array( $txt, array( '(', '[', '{' ), true ) )
				|| in_array( $id, array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true );
			$closish = ( null === $id && in_array( $txt, array( ')', ']', '}' ), true ) );

			if ( $closish ) {
				$depth--;
				if ( 0 === $depth ) {
					break;
				}
				$cur[] = $tokens[ $j ];
				continue;
			}
			if ( $openish ) {
				$depth++;
				$cur[] = $tokens[ $j ];
				continue;
			}
			if ( 1 === $depth && null === $id && ',' === $txt ) {
				$params[] = $cur;
				$cur      = array();
				continue;
			}
			$cur[] = $tokens[ $j ];
		}
		if ( $cur ) {
			$params[] = $cur;
		}

		$out = array();
		foreach ( $params as $ptoks ) {
			$p      = array( 'type' => '', 'name' => '', 'default' => null, 'byref' => false, 'variadic' => false, 'promoted' => null );
			$type_t = array();
			$def_t  = array();
			$state  = 'pre';
			foreach ( $ptoks as $t ) {
				$id  = $t['id'];
				$txt = $t['text'];
				if ( T_WHITESPACE === $id || T_COMMENT === $id ) {
					if ( 'def' === $state ) {
						$def_t[] = ' ';
					}
					continue;
				}
				if ( T_PUBLIC === $id || T_PROTECTED === $id || T_PRIVATE === $id ) {
					$p['promoted'] = strtolower( $txt );
					continue;
				}
				if ( T_READONLY === $id ) {
					continue;
				}
				if ( null === $id && '&' === $txt ) {
					$p['byref'] = true;
					continue;
				}
				if ( null === $id && '...' === $txt ) {
					$p['variadic'] = true;
					continue;
				}
				if ( null === $id && '=' === $txt && 'pre' === $state ) {
					$state = 'def';
					continue;
				}
				if ( T_VARIABLE === $id ) {
					$p['name'] = ltrim( $txt, '$' );
					$state     = 'def' === $state ? 'def' : 'after_var';
					continue;
				}
				if ( 'def' === $state ) {
					$def_t[] = $txt;
					continue;
				}
				$type_t[] = $txt;
			}
			if ( '' === $p['name'] ) {
				continue;
			}
			$p['type']    = $norm_ws( implode( '', $type_t ) );
			$p['default'] = 'def' === $state ? $norm_ws( implode( '', $def_t ) ) : null;
			$out[]        = $p;
		}
		return array( $out, $j + 1 );
	};

	// From index after ')': collect return type; returns [type|null, terminatorIdx, terminatorIsBrace]
	$parse_return_type = static function ( int $from ) use ( $tokens, $n, $norm_ws ): array {
		$txt = '';
		$j   = $from;
		for ( ; $j < $n; $j++ ) {
			$id  = $tokens[ $j ]['id'];
			$txr = $tokens[ $j ]['text'];
			if ( T_WHITESPACE === $id || T_COMMENT === $id ) {
				continue;
			}
			if ( null === $id && ( '{' === $txr || ';' === $txr ) ) {
				break;
			}
			if ( null === $id && ':' === $txr && '' === $txt ) {
				continue; // the return-type colon itself
			}
			$txt .= $txr;
		}
		$rt = '' !== trim( $txt ) ? $norm_ws( $txt ) : null;
		return array( $rt, $j, '{' === ( $tokens[ $j ]['text'] ?? '' ) );
	};

	$is_prop_start_id = static function ( $id ): bool {
		return in_array(
			$id,
			array( T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR, T_STATIC, T_READONLY, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE, T_ARRAY, T_CALLABLE, T_STRING ),
			true
		);
	};

	$try_property = static function ( int $from ) use ( $tokens, $n, $doc_for, $norm_ws ): ?array {
		$rel      = 0;
		$seen_var = -1;
		$eq       = -1;
		$end      = -1;
		for ( $j = $from; $j < $n; $j++ ) {
			$id  = $tokens[ $j ]['id'];
			$txt = $tokens[ $j ]['text'];
			if ( T_WHITESPACE === $id || T_COMMENT === $id || T_DOC_COMMENT === $id ) {
				continue;
			}
			if ( T_FUNCTION === $id || T_CONST === $id || T_CASE === $id || T_USE === $id ) {
				return null;
			}
			$openish = ( null === $id && in_array( $txt, array( '(', '[', '{' ), true ) )
				|| in_array( $id, array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true );
			$closish = ( null === $id && in_array( $txt, array( ')', ']', '}' ), true ) );
			if ( $openish ) {
				$rel++;
				if ( -1 === $seen_var && '(' === $txt ) {
					return null;
				}
				continue;
			}
			if ( $closish ) {
				$rel--;
				continue;
			}
			if ( 0 === $rel && null === $id && ';' === $txt ) {
				$end = $j;
				break;
			}
			if ( T_VARIABLE === $id && -1 === $seen_var ) {
				$seen_var = $j;
				continue;
			}
			if ( 0 === $rel && null === $id && '=' === $txt && -1 === $eq ) {
				$eq = $j;
			}
		}
		if ( -1 === $seen_var || -1 === $end ) {
			return null;
		}
		$mods   = array();
		$type_t = array();
		for ( $j = $from; $j < $seen_var; $j++ ) {
			$id  = $tokens[ $j ]['id'];
			$txt = $tokens[ $j ]['text'];
			if ( T_WHITESPACE === $id || T_COMMENT === $id ) {
				continue;
			}
			if ( in_array( $id, array( T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR, T_STATIC, T_READONLY ), true ) ) {
				$mods[] = strtolower( $txt );
				continue;
			}
			$type_t[] = $txt;
		}
		$def = null;
		if ( -1 !== $eq ) {
			$dt = array();
			for ( $j = $eq + 1; $j < $end; $j++ ) {
				$dt[] = $tokens[ $j ]['text'];
			}
			$def = $norm_ws( implode( '', $dt ) );
		}
		$visibility = 'public';
		foreach ( $mods as $m ) {
			if ( in_array( $m, array( 'public', 'protected', 'private' ), true ) ) {
				$visibility = $m;
			}
		}
		return array(
			'name'       => ltrim( $tokens[ $seen_var ]['text'], '$' ),
			'visibility' => $visibility,
			'static'     => in_array( 'static', $mods, true ),
			'readonly'   => in_array( 'readonly', $mods, true ),
			'type'       => $norm_ws( implode( '', $type_t ) ),
			'default'    => $def,
			'line'       => $tokens[ $from ]['line'],
			'docblock'   => $doc_for( $from ),
			'end_idx'    => $end,
		);
	};

	$skip_to_semicolon = static function ( int $from ) use ( $tokens, $n ): int {
		$rel = 0;
		for ( $j = $from; $j < $n; $j++ ) {
			$id  = $tokens[ $j ]['id'];
			$txt = $tokens[ $j ]['text'];
			if ( null === $id && ( '{' === $txt || '(' === $txt || '[' === $txt ) ) {
				$rel++;
				continue;
			}
			if ( null === $id && ( '}' === $txt || ')' === $txt || ']' === $txt ) ) {
				$rel--;
				continue;
			}
			if ( 0 === $rel && null === $id && ';' === $txt ) {
				return $j;
			}
		}
		return $n - 1;
	};

	$prev_sig_val = -1;
	$i            = 0;
	while ( $i < $n ) {
		$id  = $tokens[ $i ]['id'];
		$txt = $tokens[ $i ]['text'];

		if ( null !== $id && in_array( $id, array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG ), true ) ) {
			$i++;
			continue;
		}

		// Attribute blocks: skip entirely.
		if ( T_ATTRIBUTE === $id ) {
			$depth = 0;
			for ( ; $i < $n; $i++ ) {
				$depth += substr_count( $tokens[ $i ]['text'], '[' ) - substr_count( $tokens[ $i ]['text'], ']' );
				if ( $depth <= 0 && str_contains( $tokens[ $i ]['text'], ']' ) ) {
					$i++;
					break;
				}
			}
			continue;
		}

		$is_stmt_start = ( -1 === $prev_sig_val )
			|| ( T_OPEN_TAG === $tokens[ $prev_sig_val ]['id'] )
			|| ( null === $tokens[ $prev_sig_val ]['id'] && in_array( $tokens[ $prev_sig_val ]['text'], array( '{', '}', ';' ), true ) );

		$inner = $innermost();

		// ---- braces ---------------------------------------------------------
		if ( null === $id && '{' === $txt ) {
			if ( null !== $pending_class ) {
				$stack[]       = array( 'kind' => 'class', 'idx' => $pending_class );
				$pending_class = null;
			} elseif ( $pending_anon ) {
				$stack[]      = array( 'kind' => 'anon' );
				$pending_anon = false;
			} elseif ( $await_fn > 0 ) {
				$stack[] = array( 'kind' => 'fn' );
				$await_fn--;
			} else {
				$stack[] = array( 'kind' => 'block' );
			}
			$prev_sig_val = $i;
			$i++;
			continue;
		}
		if ( null === $id && '}' === $txt ) {
			array_pop( $stack );
			$prev_sig_val = $i;
			$i++;
			continue;
		}
		if ( T_CURLY_OPEN === $id || T_DOLLAR_OPEN_CURLY_BRACES === $id ) {
			$stack[]      = array( 'kind' => 'interp' );
			$prev_sig_val = $i;
			$i++;
			continue;
		}

		// ---- namespace ------------------------------------------------------
		if ( T_NAMESPACE === $id && null === $ns ) {
			[ $name, $j ] = $collect_name( $next_sig( $i + 1 ) );
			$ns = '' !== $name ? $name : null;
			while ( $j < $n && ! ( null === $tokens[ $j ]['id'] && in_array( $tokens[ $j ]['text'], array( ';', '{' ), true ) ) ) {
				$j++;
			}
			$prev_sig_val = $j - 1;
			$i            = $j;
			continue;
		}

		// ---- use statements -------------------------------------------------
		if ( T_USE === $id ) {
			$nx = $next_sig( $i + 1 );
			if ( $nx < $n && null === $tokens[ $nx ]['id'] && '(' === $tokens[ $nx ]['text'] ) {
				$depth = 0;
				for ( ; $nx < $n; $nx++ ) {
					$cx = $tokens[ $nx ]['text'];
					if ( '(' === $cx ) {
						$depth++;
					} elseif ( ')' === $cx ) {
						$depth--;
						if ( 0 === $depth ) {
							break;
						}
					}
				}
				$prev_sig_val = $nx;
				$i            = $nx + 1;
				continue;
			}
			$end          = $skip_to_semicolon( $i );
			$prev_sig_val = $end;
			$i            = $end + 1;
			continue;
		}

		// ---- class-like declarations ----------------------------------------
		if ( T_CLASS === $id || T_TRAIT === $id || T_INTERFACE === $id || T_ENUM === $id ) {
			$ps       = $prev_sig_val;
			$prev_id  = $ps >= 0 ? $tokens[ $ps ]['id'] : null;
			$prev_txt = $ps >= 0 ? $tokens[ $ps ]['text'] : '';

			// Walk back over declaration modifiers to spot "new [readonly] class".
			$check = $ps;
			while ( $check >= 0 && in_array( $tokens[ $check ]['id'], array( T_READONLY, T_FINAL, T_ABSTRACT ), true ) ) {
				$check = $prev_sig_val - 1; // only one modifier level possible; keep simple
				break;
			}
			$prev_id_check  = $ps >= 0 ? $tokens[ $ps ]['id'] : null;
			$is_anon_header = in_array( T_NEW, array( $prev_id, $prev_id_check ), true )
				|| ( $ps >= 0 && T_READONLY === $tokens[ $ps ]['id'] && $ps - 2 >= 0 && T_NEW === $tokens[ $ps - 2 ]['id'] );
			$is_ref = $is_anon_header
				|| in_array( $prev_id, array( T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_INSTANCEOF ), true )
				|| ( null === $prev_id && '?' === $prev_txt );

			if ( $is_anon_header ) {
				$pending_anon = true;
				$drel          = 0;
				$j            = $i + 1;
				for ( ; $j < $n; $j++ ) {
					$cx   = $tokens[ $j ]['text'];
					$cxid = $tokens[ $j ]['id'];
					if ( null === $cxid && ( '(' === $cx || '[' === $cx ) ) {
						$drel++;
					} elseif ( null === $cxid && ( ')' === $cx || ']' === $cx ) ) {
						$drel--;
					} elseif ( null === $cxid && '{' === $cx && 0 === $drel ) {
						break;
					}
				}
				$prev_sig_val = $j - 1;
				$i            = $j;
				continue;
			}
			if ( $is_ref ) {
				$prev_sig_val = $i;
				$i++;
				continue;
			}

			$kind          = match ( $id ) {
				T_TRAIT     => 'trait',
				T_INTERFACE => 'interface',
				T_ENUM      => 'enum',
				default     => 'class',
			};
			[ $mods, $first_idx ] = $scan_back_modifiers( $i );
			[ $name, $j ] = $collect_name( $next_sig( $i + 1 ) );

			$classes[] = array(
				'kind'       => $kind,
				'name'       => $name,
				'abstract'   => in_array( 'abstract', $mods, true ),
				'final'      => in_array( 'final', $mods, true ),
				'readonly'   => in_array( 'readonly', $mods, true ),
				'extends'    => null,
				'implements' => array(),
				'enum_type'  => null,
				'line'       => $tokens[ $i ]['line'],
				'docblock'   => $doc_for( $first_idx ),
				'constants'  => array(),
				'properties' => array(),
				'methods'    => array(),
			);
			$cls_idx = count( $classes ) - 1;

			$impl  = array();
			$mode  = null;
			$found = false;
			$k     = $j;
			while ( $k < $n ) {
				$tid = $tokens[ $k ]['id'];
				$tt  = $tokens[ $k ]['text'];
				if ( null === $tid && '{' === $tt ) {
					$found = true;
					break;
				}
				if ( T_EXTENDS === $tid ) {
					$mode = 'extends';
					$k++;
					continue;
				}
				if ( T_IMPLEMENTS === $tid ) {
					$mode = 'implements';
					$k++;
					continue;
				}
				if ( null === $tid && ':' === $tt && 'enum' === $kind && null === $mode ) {
					$mode = 'enumtype';
					$k++;
					continue;
				}
				if ( T_WHITESPACE === $tid || T_COMMENT === $tid || ( null === $tid && ',' === $tt ) ) {
					$k++;
					continue;
				}
				[ $iname, $k ] = $collect_name( $k );
				if ( '' === $iname ) {
					$k++;
					continue;
				}
				if ( 'extends' === $mode ) {
					$classes[ $cls_idx ]['extends'] = ( $classes[ $cls_idx ]['extends'] ?? '' ) . ( null !== $classes[ $cls_idx ]['extends'] ? ' , ' : '' ) . $iname;
				} elseif ( 'implements' === $mode ) {
					$impl[] = $iname;
				} elseif ( 'enumtype' === $mode ) {
					$classes[ $cls_idx ]['enum_type'] = $iname;
				}
			}
			$classes[ $cls_idx ]['implements'] = $impl;

			if ( $found ) {
				$pending_class = $cls_idx;
				$prev_sig_val  = $k - 1;
				$i             = $k; // brace branch pushes the class ctx
			} else {
				$prev_sig_val = $i;
				$i++;
			}
			continue;
		}

		// ---- function declarations -------------------------------------------
		if ( T_FUNCTION === $id ) {
			$in_class_body = ( null !== $inner && 'class' === $inner['kind'] );
			$in_function   = $in_fn();
			$prev_t        = -1 !== $prev_sig_val ? $tokens[ $prev_sig_val ] : null;
			$prev_ptxt     = null !== $prev_t ? $prev_t['text'] : '';
			$prev_pid      = null !== $prev_t ? $prev_t['id'] : null;
			$is_closure    = ( null === $prev_pid && in_array( $prev_ptxt, array( '=', '(', ',', '?', ':' ), true ) )
				|| T_RETURN === $prev_pid || T_NEW === $prev_pid;

			if ( $in_class_body && ! $is_closure ) {
				[ $mods, $first_idx ] = $scan_back_modifiers( $i );
				[ $name, $j ] = $collect_name( $next_sig( $i + 1 ) );
				$open = $next_sig( $j );
				[ $params, $after ] = $parse_params( $open );
				[ $rt, $term, $is_brace ] = $parse_return_type( $after );
				$classes[ $inner['idx'] ]['methods'][] = array(
					'name'       => $name,
					'visibility' => in_array( 'private', $mods, true ) ? 'private' : ( in_array( 'protected', $mods, true ) ? 'protected' : 'public' ),
					'static'     => in_array( 'static', $mods, true ),
					'abstract'   => in_array( 'abstract', $mods, true ) || ! $is_brace,
					'final'      => in_array( 'final', $mods, true ),
					'params'     => $params,
					'return'     => $rt,
					'line'       => $tokens[ $i ]['line'],
					'docblock'   => $doc_for( $first_idx ),
				);
				if ( $is_brace ) {
					$await_fn++;
					$prev_sig_val = $term;
					$i            = $term;
				} else {
					$prev_sig_val = $term;
					$i            = $term + 1;
				}
				continue;
			}

			if ( ! $in_class_body && ! $in_function && ! $is_closure && '' !== ( $collect_name( $next_sig( $i + 1 ) )[0] ?? '' ) ) {
				[ $name, $j ] = $collect_name( $next_sig( $i + 1 ) );
				$open = $next_sig( $j );
				[ $params, $after ] = $parse_params( $open );
				[ $rt, $term, $is_brace ] = $parse_return_type( $after );
				$functions[] = array(
					'name'     => $name,
					'params'   => $params,
					'return'   => $rt,
					'line'     => $tokens[ $i ]['line'],
					'docblock' => $doc_for( $i ),
				);
				if ( $is_brace ) {
					$await_fn++;
					$prev_sig_val = $term;
					$i            = $term;
				} else {
					$prev_sig_val = $term;
					$i            = $term + 1;
				}
				continue;
			}

			// Closure or fn-body context: register so defines inside are not treated as top-level.
			$await_fn++;
			$prev_sig_val = $i;
			$i++;
			continue;
		}

		if ( T_FN === $id ) {
			$prev_sig_val = $i;
			$i++;
			continue;
		}

		// ---- constants (class const / top-level const) ------------------------
		if ( T_CONST === $id && ( -1 === $prev_sig_val || T_DOUBLE_COLON !== $tokens[ $prev_sig_val ]['id'] ) ) {
			$in_class             = ( null !== $inner && 'class' === $inner['kind'] );
			[ $mods, $first_idx ] = $scan_back_modifiers( $i );
			$drel   = 0;
			$parts = array();
			$value = '';
			$state = 'head';
			$end   = null;
			for ( $j = $next_sig( $i + 1 ); $j < $n; $j++ ) {
				$tid = $tokens[ $j ]['id'];
				$tt  = $tokens[ $j ]['text'];
				if ( T_WHITESPACE === $tid || T_COMMENT === $tid ) {
					if ( 'value' === $state ) {
						$value .= ' ';
					}
					continue;
				}
				if ( null === $tid && '=' === $tt && 0 === $drel && 'head' === $state ) {
					$state = 'value';
					continue;
				}
				if ( null === $tid && ';' === $tt && 0 === $drel ) {
					$end = $j;
					break;
				}
				if ( null === $tid && ( '(' === $tt || '[' === $tt || '{' === $tt || T_CURLY_OPEN === $tid || T_DOLLAR_OPEN_CURLY_BRACES === $tid ) ) {
					$drel++;
					if ( 'value' === $state ) {
						$value .= $tt;
					}
					continue;
				}
				if ( null === $tid && ( ')' === $tt || ']' === $tt || '}' === $tt ) ) {
					$drel--;
					if ( 'value' === $state ) {
						$value .= $tt;
					}
					continue;
				}
				if ( 'head' === $state ) {
					$parts[] = $tokens[ $j ];
				} else {
					$value .= $tt;
				}
			}
			$name  = '';
			$ctype = '';
			foreach ( $parts as $pt ) {
				if ( T_STRING === $pt['id'] ) {
					$name = $pt['text'];
				} else {
					$ctype .= $pt['text'];
				}
			}
			if ( '' !== $name && null !== $end ) {
				$entry = array(
					'name'       => $name,
					'value'      => $norm_ws( $value ),
					'type'       => $norm_ws( $ctype ),
					'visibility' => in_array( 'private', $mods, true ) ? 'private' : ( in_array( 'protected', $mods, true ) ? 'protected' : 'public' ),
					'line'       => $tokens[ $i ]['line'],
				);
				if ( $in_class ) {
					$classes[ $inner['idx'] ]['constants'][] = $entry;
				} else {
					$defines[] = $entry;
				}
			}
			$prev_sig_val = null !== $end ? $end : $i;
			$i            = ( null !== $end ? $end : $i ) + 1;
			continue;
		}

		// ---- enum cases -------------------------------------------------------
		if ( T_CASE === $id && null !== $inner && 'class' === $inner['kind'] ) {
			[ $name, $j ] = $collect_name( $next_sig( $i + 1 ) );
			$value = '';
			$state = 'wait';
			$end   = $i;
			for ( $k = $j; $k < $n; $k++ ) {
				$tid = $tokens[ $k ]['id'];
				$tt  = $tokens[ $k ]['text'];
				if ( T_WHITESPACE === $tid || T_COMMENT === $tid ) {
					continue;
				}
				if ( null === $tid && '=' === $tt ) {
					$state = 'value';
					continue;
				}
				if ( null === $tid && ';' === $tt ) {
					$end = $k;
					break;
				}
				if ( 'value' === $state ) {
					$value .= $tt;
				}
			}
			$classes[ $inner['idx'] ]['constants'][] = array(
				'name'       => $name,
				'value'      => $norm_ws( $value ),
				'type'       => 'case',
				'visibility' => 'public',
				'line'       => $tokens[ $i ]['line'],
			);
			$prev_sig_val = $end;
			$i            = $end + 1;
			continue;
		}

		// ---- top-level define() ----------------------------------------------
		if ( T_STRING === $id && 'define' === strtolower( $txt ) && ! $in_fn()
			&& ( null === $inner || 'class' !== $inner['kind'] ) ) {
			$nx = $next_sig( $i + 1 );
			if ( $nx < $n && null === $tokens[ $nx ]['id'] && '(' === $tokens[ $nx ]['text'] ) {
				$drel = 0;
				$arg = '';
				$rest = '';
				$state = 'name';
				$end  = null;
				$commas = 0;
				for ( $j = $nx + 1; $j < $n; $j++ ) {
					$tid = $tokens[ $j ]['id'];
					$tt  = $tokens[ $j ]['text'];
					if ( T_WHITESPACE === $tid ) {
						if ( 'value' === $state && '' !== trim( $rest ) ) {
							$rest .= ' ';
						}
						continue;
					}
					if ( null === $tid && '(' === $tt ) {
						$drel++;
						if ( $commas >= 1 ) {
							$rest .= $tt;
						}
						continue;
					}
					if ( null === $tid && ')' === $tt ) {
						$drel--;
						if ( $drel <= 0 ) {
							$end = $j;
							break;
						}
						$rest .= $tt;
						continue;
					}
					if ( null === $tid && ',' === $tt && 1 === $drel ) {
						$commas++;
						$state = 'value';
						continue;
					}
					if ( 'name' === $state ) {
						$arg .= $tt;
					} else {
						$rest .= $tt;
					}
				}
				$const_name = trim( $arg, "\"' \t" );
				if ( '' !== $const_name && preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $const_name ) && null !== $end ) {
					$defines[] = array(
						'name'       => $const_name,
						'value'      => $norm_ws( $rest ),
						'type'       => 'define',
						'visibility' => 'public',
						'line'       => $tokens[ $i ]['line'],
					);
				}
				$prev_sig_val = null !== $end ? $end : $i;
				$i            = ( null !== $end ? $end : $i ) + 1;
				continue;
			}
		}

		// ---- property statements inside class bodies --------------------------
		if ( null !== $inner && 'class' === $inner['kind'] && $is_stmt_start && $is_prop_start_id( $id ) ) {
			$prop = $try_property( $i );
			if ( null !== $prop ) {
				$end = $prop['end_idx'];
				unset( $prop['end_idx'] );
				$classes[ $inner['idx'] ]['properties'][] = $prop;
				$prev_sig_val                             = $end;
				$i                                        = $end + 1;
				continue;
			}
		}

		$prev_sig_val = $i;
		$i++;
	}

	$line_count = substr_count( $src, "\n" );
	if ( '' !== $src && ! str_ends_with( $src, "\n" ) ) {
		$line_count++;
	}

	$first_doc_idx = array_key_first( $docblocks );
	$first_doc_raw = null !== $first_doc_idx ? $docblocks[ $first_doc_idx ]['raw'] : null;

	return array(
		'path'        => $rel,
		'section'     => $section,
		'namespace'   => $ns,
		'lines'       => $line_count,
		'filedoc_raw' => $first_doc_raw,
		'filedoc'     => wppo_docs_file_summary( $first_doc_raw ),
		'classes'     => $classes,
		'functions'   => $functions,
		'defines'     => $defines,
	);
}

// ---------------------------------------------------------------------------
// Hook extraction (regex over source is fine for hooks)
// ---------------------------------------------------------------------------

/**
 * Extract wppo_* hook references (fired + registered) from a source file.
 *
 * @return array<int,array{name:string,type:string,kind:string,deprecated:bool,line:int,callback:?string,doc_params:?int}>
 */
function wppo_docs_extract_hooks( string $src ): array {
	$re = '/(?<![\$\w>:])(do_action_deprecated|apply_filters_deprecated|do_action|apply_filters|add_action|add_filter)\s*\(\s*([\'"])(wppo_[A-Za-z0-9_]+)\2/';
	if ( ! preg_match_all( $re, $src, $m, PREG_OFFSET_CAPTURE ) ) {
		return array();
	}

	// Docblock end lines for @param counting.
	$doc_list = array();
	$run      = 1;
	foreach ( token_get_all( $src, TOKEN_PARSE ) as $t ) {
		if ( is_array( $t ) ) {
			$line = $t[2];
			$run  = max( $run, $line ) + substr_count( $t[1], "\n" );
			if ( T_DOC_COMMENT === $t[0] ) {
				$doc_list[] = array(
					'end'    => $line + substr_count( $t[1], "\n" ),
					'params' => preg_match_all( '/@param\b/', $t[1] ) ?: 0,
				);
			}
		} else {
			$run += substr_count( $t, "\n" );
		}
	}

	$hooks = array();
	foreach ( $m[1] as $k => $fn ) {
		$call = $fn[0];
		$off  = (int) $fn[1];
		$name = $m[3][ $k ][0];
		$line = substr_count( substr( $src, 0, $off ), "\n" ) + 1;

		$fired = str_starts_with( $call, 'do_' ) || str_starts_with( $call, 'apply_' );
		$type  = ( str_starts_with( $call, 'do_' ) || 'add_action' === $call ) ? 'action' : 'filter';
		$kind  = $fired ? 'fired' : 'registered';

		$callback = ! $fired
			? wppo_docs_second_arg( $src, $m[2][ $k ][1] + strlen( $m[2][ $k ][0] ) )
			: null;

		$doc_params = null;
		foreach ( $doc_list as $d ) {
			if ( $d['end'] < $line && $d['end'] >= $line - 4 && $d['params'] > 0 ) {
				$doc_params = $d['params'];
				break;
			}
		}

		$hooks[] = array(
			'name'       => $name,
			'type'       => $type,
			'kind'       => $kind,
			'deprecated' => str_ends_with( $call, '_deprecated' ),
			'line'       => $line,
			'callback'   => $callback,
			'doc_params' => $doc_params,
		);
	}
	return $hooks;
}

/** Capture the 2nd argument (the callback) of an add_action/add_filter call. */
function wppo_docs_second_arg( string $src, int $from ): ?string {
	$len = strlen( $src );
	$j   = $from;
	while ( $j < $len && preg_match( '/\s/', $src[ $j ] ) ) {
		$j++;
	}
	if ( $j >= $len || ',' !== $src[ $j ] ) {
		return null;
	}
	$j++;
	$depth   = 0;
	$q       = null;
	$esc     = false;
	$buf     = '';
	$started = false;
	for ( ; $j < $len; $j++ ) {
		$c = $src[ $j ];
		if ( null !== $q ) {
			$buf .= $c;
			if ( $esc ) {
				$esc = false;
				continue;
			}
			if ( '\\' === $c ) {
				$esc = true;
				continue;
			}
			if ( $q === $c ) {
				$q = null;
			}
			continue;
		}
		if ( '"' === $c || "'" === $c ) {
			$q       = $c;
			$buf     .= $c;
			$started = true;
			continue;
		}
		if ( '(' === $c || '[' === $c || '{' === $c ) {
			$depth++;
		} elseif ( ')' === $c || ']' === $c || '}' === $c ) {
			if ( 0 === $depth ) {
				break;
			}
			$depth--;
		} elseif ( ',' === $c && 0 === $depth ) {
			break;
		}
		$buf .= $c;
		if ( ! preg_match( '/\s/', $c ) ) {
			$started = true;
		}
	}
	$buf = trim( $buf );
	return ( '' !== $buf && $started ) ? $buf : null;
}

// ---------------------------------------------------------------------------
// Rendering helpers
// ---------------------------------------------------------------------------

function wppo_docs_h( string $s ): string {
	return htmlspecialchars( $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function wppo_docs_code( string $s ): string {
	return '<code>' . wppo_docs_h( $s ) . '</code>';
}

function wppo_docs_slug_for( string $rel, string $section ): string {
	$base = preg_replace( '/\.php$/', '', $rel ) ?? $rel;
	switch ( $section ) {
		case 'root':
			// Entry points are slugified from the bare file name (e.g. redis-connect-helper-php),
			// even when the file physically lives in includes/.
			return str_replace( '/', '-', basename( $base ) ) . '-php';
		case 'includes':
			return 'includes-' . str_replace( '/', '-', basename( $base ) );
		case 'minify':
			return 'minify-' . basename( $base );
		case 'templates':
			return 'templates-' . basename( $base );
	}
	return str_replace( '/', '-', $base );
}

function wppo_docs_badges( array $parts ): string {
	$out = '';
	foreach ( $parts as $p ) {
		if ( '' === $p ) {
			continue;
		}
		$out .= '<span class="wppo-api-tag">' . wppo_docs_h( $p ) . '</span> ';
	}
	return $out;
}

function wppo_docs_signature_method( array $m ): string {
	$sig = '';
	if ( isset( $m['visibility'] ) ) {
		$sig .= $m['visibility'] . ' ';
	}
	foreach ( array( 'abstract', 'final', 'static' ) as $flag ) {
		if ( ! empty( $m[ $flag ] ) ) {
			$sig .= $flag . ' ';
		}
	}
	$sig .= 'function ' . $m['name'] . '(';
	$ps  = array();
	foreach ( $m['params'] as $p ) {
		$t = '';
		if ( null !== ( $p['promoted'] ?? null ) ) {
			$t .= $p['promoted'] . ' ';
		}
		if ( '' !== $p['type'] ) {
			$t .= $p['type'] . ' ';
		}
		if ( ! empty( $p['variadic'] ) ) {
			$t .= '...';
		} elseif ( ! empty( $p['byref'] ) ) {
			$t .= '&';
		}
		$t .= '$' . $p['name'];
		if ( null !== $p['default'] ) {
			$t .= ' = ' . $p['default'];
		}
		$ps[] = $t;
	}
	$sig .= implode( ', ', $ps ) . ')';
	if ( ! empty( $m['return'] ) ) {
		$sig .= ': ' . $m['return'];
	}
	return $sig;
}

function wppo_docs_signature_class( array $c ): string {
	$sig = '';
	if ( ! empty( $c['abstract'] ) ) {
		$sig .= 'abstract ';
	}
	if ( ! empty( $c['final'] ) ) {
		$sig .= 'final ';
	}
	if ( ! empty( $c['readonly'] ) && 'class' === $c['kind'] ) {
		$sig .= 'readonly ';
	}
	$sig .= $c['kind'] . ' ' . $c['name'];
	if ( 'enum' === $c['kind'] && ! empty( $c['enum_type'] ) ) {
		$sig .= ': ' . $c['enum_type'];
	}
	if ( ! empty( $c['extends'] ) ) {
		$sig .= ' extends ' . str_replace( ' , ', ', ', $c['extends'] );
	}
	if ( ! empty( $c['implements'] ) ) {
		$sig .= ' implements ' . implode( ', ', $c['implements'] );
	}
	return $sig;
}

function wppo_docs_class_id( string $name ): string {
	return 'class-' . preg_replace( '/[^A-Za-z0-9_\-]/', '', $name );
}

/** Params table (Parameter | Type | Default | Description) merged from signature + docblock. */
function wppo_docs_params_table( array $params, array $doc_params ): string {
	if ( empty( $params ) ) {
		return '';
	}
	$desc_by_name = array();
	foreach ( $doc_params as $dp ) {
		if ( '' !== $dp['name'] ) {
			$desc_by_name[ ltrim( $dp['name'], '$' ) ] = $dp;
		}
	}
	$rows = '';
	foreach ( $params as $p ) {
		$ty = '' !== $p['type'] ? $p['type'] : ( $desc_by_name[ $p['name'] ]['type'] ?? '' );
		$de = null !== $p['default'] ? $p['default'] : '';
		$ds = $desc_by_name[ $p['name'] ]['desc'] ?? '';
		$nm = ( ! empty( $p['variadic'] ) ? '...' : '' ) . '$' . $p['name'];
		$rows .= '<tr>'
			. '<td>' . wppo_docs_code( $nm ) . '</td>'
			. '<td>' . ( '' !== $ty ? wppo_docs_code( $ty ) : '—' ) . '</td>'
			. '<td>' . ( '' !== $de ? wppo_docs_code( $de ) : '—' ) . '</td>'
			. '<td>' . ( '' !== $ds ? wppo_docs_h( $ds ) : '—' ) . '</td>'
			. '</tr>';
	}
	foreach ( $doc_params as $dp ) {
		$nm = ltrim( $dp['name'], '$' );
		if ( '' === $nm ) {
			continue;
		}
		$known = false;
		foreach ( $params as $p ) {
			if ( $p['name'] === $nm ) {
				$known = true;
				break;
			}
		}
		if ( $known ) {
			continue;
		}
		$rows .= '<tr><td>' . wppo_docs_code( '$' . $nm ) . '</td>'
			. '<td>' . ( '' !== $dp['type'] ? wppo_docs_code( $dp['type'] ) : '—' ) . '</td><td>—</td>'
			. '<td>' . ( '' !== $dp['desc'] ? wppo_docs_h( $dp['desc'] ) : '—' ) . '</td></tr>';
	}
	return '<table class="wppo-api-params">'
		. '<thead><tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>'
		. '<tbody>' . $rows . '</tbody></table>';
}

function wppo_docs_return_line( array $doc, array $m ): string {
	if ( null !== $doc['return'] ) {
		$desc = rtrim( $doc['return']['desc'], '.' );
		$line = 'Return: ' . wppo_docs_code( $doc['return']['type'] );
		if ( '' !== $desc ) {
			$line .= ' — ' . wppo_docs_h( $desc );
		}
		return '<p>' . $line . '.</p>';
	}
	$rt = $m['return'] ?? null;
	if ( null !== $rt && '' !== $rt ) {
		return '<p>Return: ' . wppo_docs_code( $rt ) . '.</p>';
	}
	return '';
}

function wppo_docs_tags_line( array $doc ): string {
	if ( empty( $doc['tags'] ) ) {
		return '';
	}
	return '<p class="wppo-api-meta">Tags: ' . wppo_docs_h( implode( ' · ', array_slice( $doc['tags'], 0, 6 ) ) ) . '</p>';
}

// ---------------------------------------------------------------------------
// File fragment rendering
// ---------------------------------------------------------------------------

function wppo_docs_render_hooks_section( array $hooks, string $path ): string {
	if ( empty( $hooks ) ) {
		return '';
	}
	$rows = '';
	foreach ( $hooks as $h ) {
		$type = $h['type'] . ( $h['deprecated'] ? ' (deprecated)' : '' ) . ( 'registered' === $h['kind'] ? ' registration' : '' );
		$note = '';
		if ( 'registered' === $h['kind'] && null !== $h['callback'] ) {
			$note = 'Callback: ' . $h['callback'];
		}
		if ( null !== $h['doc_params'] && $h['doc_params'] > 0 ) {
			$note = ( '' !== $note ? $note . ' · ' : '' ) . '@param ×' . $h['doc_params'];
		}
		$rows .= '<tr><td>' . wppo_docs_code( $h['name'] ) . '</td>'
			. '<td>' . wppo_docs_h( $type ) . '</td>'
			. '<td>' . (int) $h['line'] . '</td>'
			. '<td>' . ( '' !== $note ? wppo_docs_h( $note ) : '—' ) . '</td></tr>';
	}
	return '<h3 id="hooks">Hooks</h3>'
		. '<p>Hooks referenced in ' . wppo_docs_h( $path ) . ':</p>'
		. '<table class="wppo-api-params"><thead><tr><th>Hook</th><th>Type</th><th>Line</th><th>Notes</th></tr></thead><tbody>' . $rows . '</tbody></table>';
}

function wppo_docs_render_file( array $f, string $hooks_html ): string {
	$path  = $f['path'];
	$out   = array();
	$out[] = '<h2>' . wppo_docs_h( $path ) . '</h2>';

	$intro = $f['filedoc'];
	if ( null === $intro ) {
		$nc = count( $f['classes'] );
		$nf = count( $f['functions'] ) + count( $f['defines'] );
		$intro = ( $nc || $nf )
			? "Source file {$path} contains {$nc} class(es) and {$nf} function(s) or constant definition(s)."
			: "Source file {$path} ({$f['lines']} lines).";
	}
	$out[] = '<p>' . wppo_docs_h( $intro ) . '</p>';

	$meta = array();
	if ( ! empty( $f['namespace'] ) ) {
		$meta[] = 'Namespace: ' . $f['namespace'];
	}
	$meta[] = "Lines: {$f['lines']}";
	$out[] = '<p class="wppo-api-meta">' . wppo_docs_h( implode( ' · ', $meta ) ) . '</p>';

	if ( ! empty( $f['defines'] ) ) {
		$out[] = '<h3 id="constants">Constants</h3>';
		$rows  = '';
		foreach ( $f['defines'] as $c ) {
			$rows .= '<tr><td>' . wppo_docs_code( $c['name'] ) . '</td>'
				. '<td>' . ( '' !== $c['value'] ? wppo_docs_code( $c['value'] ) : '—' ) . '</td>'
				. '<td>' . (int) $c['line'] . '</td></tr>';
		}
		$out[] = '<table class="wppo-api-params"><thead><tr><th>Constant</th><th>Value</th><th>Line</th></tr></thead><tbody>' . $rows . '</tbody></table>';
	}

	foreach ( $f['classes'] as $cls ) {
		$cid   = wppo_docs_class_id( $cls['name'] );
		$label = ucfirst( $cls['kind'] ) . ' ' . $cls['name'];
		$out[] = '<h3 id="' . wppo_docs_h( $cid ) . '">' . wppo_docs_h( $label ) . '</h3>';
		$cdoc  = wppo_docs_parse_docblock( $cls['docblock'] );
		$out[] = '<p>' . wppo_docs_h( $cdoc['summary'] ?? '(No description.)' ) . '</p>';
		$out[] = '<p class="wppo-api-meta">Source: ' . wppo_docs_h( $path ) . ', line ' . (int) $cls['line'] . '</p>';
		$out[] = '<pre><code>' . wppo_docs_h( wppo_docs_signature_class( $cls ) ) . '</code></pre>';
		$out[] = wppo_docs_tags_line( $cdoc );

		if ( ! empty( $cls['constants'] ) ) {
			$out[] = '<h4>Constants</h4>';
			$rows  = '';
			foreach ( $cls['constants'] as $c ) {
				$rows .= '<tr><td>' . wppo_docs_code( $c['name'] ) . '</td>'
					. '<td>' . wppo_docs_h( $c['visibility'] ) . '</td>'
					. '<td>' . ( '' !== $c['value'] ? wppo_docs_code( $c['value'] ) : '—' ) . '</td>'
					. '<td>' . (int) $c['line'] . '</td></tr>';
			}
			$out[] = '<table class="wppo-api-params"><thead><tr><th>Constant</th><th>Visibility</th><th>Value</th><th>Line</th></tr></thead><tbody>' . $rows . '</tbody></table>';
		}

		if ( ! empty( $cls['properties'] ) ) {
			$out[] = '<h4>Properties</h4>';
			$pdoc_types = array();
			$rows  = '';
			foreach ( $cls['properties'] as $p ) {
				$flags = $p['visibility'] . ( $p['static'] ? ' static' : '' ) . ( $p['readonly'] ? ' readonly' : '' );
				$ptype = $p['type'];
				if ( '' === $ptype ) {
					// Fall back to the docblock @var type.
					if ( ! isset( $pdoc_types[ $p['name'] ] ) ) {
						$vd = wppo_docs_parse_docblock( $p['docblock'] ?? null );
						$pdoc_types[ $p['name'] ] = '';
						foreach ( $vd['tags'] as $tg ) {
							if ( preg_match( '/^@var\s+(\S+)/', $tg, $vm ) ) {
								$pdoc_types[ $p['name'] ] = $vm[1];
								break;
							}
						}
					}
					$ptype = $pdoc_types[ $p['name'] ];
				}
				$rows .= '<tr><td>' . wppo_docs_code( '$' . $p['name'] ) . '</td>'
					. '<td>' . wppo_docs_h( $flags ) . '</td>'
					. '<td>' . ( '' !== $ptype ? wppo_docs_code( $ptype ) : '—' ) . '</td>'
					. '<td>' . ( null !== $p['default'] ? wppo_docs_code( $p['default'] ) : '—' ) . '</td>'
					. '<td>' . (int) $p['line'] . '</td></tr>';
			}
			$out[] = '<table class="wppo-api-params"><thead><tr><th>Property</th><th>Visibility</th><th>Type</th><th>Default</th><th>Line</th></tr></thead><tbody>' . $rows . '</tbody></table>';
		}

		foreach ( $cls['methods'] as $mth ) {
			$mid  = $cid . '-method-' . $mth['name'];
			$mdoc = wppo_docs_parse_docblock( $mth['docblock'] );
			$parts = array( $mth['visibility'] );
			foreach ( array( 'abstract', 'final', 'static' ) as $flag ) {
				if ( ! empty( $mth[ $flag ] ) ) {
					$parts[] = $flag;
				}
			}
			$out[] = '<h3 id="' . wppo_docs_h( $mid ) . '">' . wppo_docs_badges( $parts ) . wppo_docs_h( $mth['name'] ) . '()</h3>';
			$out[] = '<pre><code class="wppo-api-sign">' . wppo_docs_h( wppo_docs_signature_method( $mth ) ) . '</code></pre>';
			$out[] = '<p>' . wppo_docs_h( $mdoc['summary'] ?? '(No description.)' ) . '</p>';
			$out[] = wppo_docs_params_table( $mth['params'], $mdoc['params'] );
			$out[] = wppo_docs_return_line( $mdoc, $mth );
			$out[] = wppo_docs_tags_line( $mdoc );
			$out[] = '<p class="wppo-api-meta">Source: ' . wppo_docs_h( $path ) . ', line ' . (int) $mth['line'] . '</p>';
		}
	}

	foreach ( $f['functions'] as $fn ) {
		$fid  = 'function-' . $fn['name'];
		$fdoc = wppo_docs_parse_docblock( $fn['docblock'] );
		$out[] = '<h3 id="' . wppo_docs_h( $fid ) . '">' . wppo_docs_badges( array( 'function' ) ) . wppo_docs_h( $fn['name'] ) . '()</h3>';
		$out[] = '<pre><code class="wppo-api-sign">' . wppo_docs_h( wppo_docs_signature_method( $fn ) ) . '</code></pre>';
		$out[] = '<p>' . wppo_docs_h( $fdoc['summary'] ?? '(No description.)' ) . '</p>';
		$out[] = wppo_docs_params_table( $fn['params'], $fdoc['params'] );
		$out[] = wppo_docs_return_line( $fdoc, $fn );
		$out[] = wppo_docs_tags_line( $fdoc );
		$out[] = '<p class="wppo-api-meta">Source: ' . wppo_docs_h( $path ) . ', line ' . (int) $fn['line'] . '</p>';
	}

	if ( '' !== $hooks_html ) {
		$out[] = $hooks_html;
	}

	return implode( "\n", array_filter( $out, static fn( $s ) => '' !== $s ) ) . "\n";
}

// ---------------------------------------------------------------------------
// Hub pages + manifest
// ---------------------------------------------------------------------------

const WPPO_DOCS_SECTIONS = array(
	'includes'  => array( 'file' => 'reference-includes.html', 'slug' => 'reference-includes', 'order' => 10, 'title' => 'Includes — core classes', 'intro' => 'Core classes loaded by the Performance Optimisation plugin. Each row links to the per-file reference page.' ),
	'minify'    => array( 'file' => 'reference-minify.html', 'slug' => 'reference-minify', 'order' => 11, 'title' => 'Minify engine', 'intro' => 'CSS, JavaScript and HTML minification classes under includes/minify/.' ),
	'templates' => array( 'file' => 'reference-templates.html', 'slug' => 'reference-templates', 'order' => 12, 'title' => 'Templates and drop-ins', 'intro' => 'Drop-in templates shipped with the plugin: the Redis object cache drop-in and the compiled translations template.' ),
	'root'      => array( 'file' => 'reference-root.html', 'slug' => 'reference-root', 'order' => 13, 'title' => 'Entry points', 'intro' => 'Entry point files: the main plugin bootstrap, the uninstall routine and the Redis connection helper.' ),
);

function wppo_docs_render_index( array $files, string $generator_rel ): string {
	$n_files   = count( $files );
	$n_classes = 0;
	$n_methods = 0;
	foreach ( $files as $f ) {
		$n_classes += count( $f['classes'] );
		$n_methods += count( $f['functions'] );
		foreach ( $f['classes'] as $c ) {
			$n_methods += count( $c['methods'] );
		}
	}

	$all = array();
	foreach ( $files as $path => $f ) {
		foreach ( $f['hooks'] as $h ) {
			if ( ! isset( $all[ $h['name'] ] ) ) {
				$all[ $h['name'] ] = array( 'types' => array(), 'fired' => array(), 'registered' => array() );
			}
			$all[ $h['name'] ]['types'][ $h['type'] ] = true;
			$loc                                       = $path . ':' . $h['line'];
			if ( 'fired' === $h['kind'] ) {
				$all[ $h['name'] ]['fired'][] = $loc;
			} else {
				$all[ $h['name'] ]['registered'][] = $loc;
			}
		}
	}
	ksort( $all, SORT_NATURAL );

	$out   = array();
	$out[] = '<p>Code reference for the Performance Optimisation plugin, generated by static analysis of the plugin source: '
		. "{$n_files} PHP files, {$n_classes} classes, {$n_methods} methods and functions, and " . count( $all ) . ' distinct <code>wppo_*</code> hooks. '
		. 'Each page documents the classes, methods, properties and hooks of one source file with exact line numbers into that source. '
		. 'Regenerate after code changes with <code>php ' . wppo_docs_h( $generator_rel ) . '</code> run from the theme root.</p>';

	$out[] = '<h2>Reference sections</h2>';
	$out[] = '<ul>';
	$counts = array( 'includes' => 0, 'minify' => 0, 'templates' => 0, 'root' => 0 );
	foreach ( $files as $f ) {
		$counts[ $f['section'] ]++;
	}
	foreach ( WPPO_DOCS_SECTIONS as $key => $sec ) {
		$out[] = '<li><a href="' . wppo_docs_h( $sec['slug'] ) . '/">' . wppo_docs_h( $sec['title'] ) . '</a> — ' . $counts[ $key ] . ' files.</li>';
	}
	$out[] = '</ul>';

	$out[] = '<h2>Hooks summary</h2>';
	$out[] = '<p>All <code>wppo_*</code> hooks found in hook position (<code>do_action</code>/<code>apply_filters</code> and their deprecated variants, plus <code>add_action</code>/<code>add_filter</code> registrations), sorted by name.</p>';
	$rows  = '';
	foreach ( $all as $name => $info ) {
		$type = implode( ', ', array_keys( $info['types'] ) );

		$fired      = array_values( array_unique( $info['fired'] ) );
		$registered = array_values( array_unique( $info['registered'] ) );

		$def_cell = empty( $fired )
			? '—'
			: wppo_docs_code( $fired[0] ) . ( count( $fired ) > 1 ? ' (+' . ( count( $fired ) - 1 ) . ' more)' : '' );
		$reg_show = array_slice( $registered, 0, 2 );
		$reg_cell = empty( $reg_show )
			? '—'
			: implode( ', ', array_map( 'wppo_docs_code', $reg_show ) ) . ( count( $registered ) > 2 ? ' (+' . ( count( $registered ) - 2 ) . ' more)' : '' );

		$rows .= '<tr><td>' . wppo_docs_code( $name ) . '</td><td>' . wppo_docs_h( $type ) . '</td><td>' . $def_cell . '</td><td>' . $reg_cell . '</td></tr>';
	}
	$out[] = '<table class="wppo-api-params"><thead><tr><th>Hook</th><th>Type</th><th>Defined in</th><th>Registered by</th></tr></thead><tbody>' . $rows . '</tbody></table>';

	return implode( "\n", $out ) . "\n";
}

function wppo_docs_render_section_hub( string $section, array $files ): string {
	$meta  = WPPO_DOCS_SECTIONS[ $section ];
	$out   = array();
	$out[] = '<p>' . wppo_docs_h( $meta['intro'] ) . '</p>';

	$list = array_filter( $files, static fn( $f ) => $f['section'] === $section );
	uasort( $list, static fn( $a, $b ) => strcmp( $a['path'], $b['path'] ) );

	$rows = '';
	foreach ( $list as $f ) {
		$n_cls  = count( $f['classes'] );
		$n_meth = count( $f['functions'] );
		foreach ( $f['classes'] as $c ) {
			$n_meth += count( $c['methods'] );
		}
		$rows .= '<tr><td><a href="' . wppo_docs_h( $f['slug'] ) . '/">' . wppo_docs_h( $f['path'] ) . '</a></td>'
			. '<td>' . $n_cls . '</td><td>' . $n_meth . '</td><td>' . (int) $f['lines'] . '</td></tr>';
	}
	$out[] = '<table class="wppo-api-params"><thead><tr><th>File</th><th>Classes</th><th>Methods</th><th>Lines</th></tr></thead><tbody>' . $rows . '</tbody></table>';
	return implode( "\n", $out ) . "\n";
}

// ---------------------------------------------------------------------------
// CLI entry point
// ---------------------------------------------------------------------------

function wppo_docs_arg( string $name, array $argv ): ?string {
	foreach ( array_slice( $argv, 1 ) as $a ) {
		if ( str_starts_with( $a, '--' . $name . '=' ) ) {
			return substr( $a, strlen( '--' . $name . '=' ) );
		}
	}
	return null;
}

$plugin_dir = dirname( __DIR__, 3 ) . '/plugins/performance-optimisation'; // tools/ -> boltfolio -> themes -> wp-content

if ( ! is_dir( $plugin_dir ) ) {
	fwrite( STDERR, "Plugin directory not found: {$plugin_dir}\n" );
	exit( 1 );
}

$out_dir = wppo_docs_arg( 'out', $argv ) ?? '/tmp/opencode/docs-staging/g';
if ( '' === $out_dir ) {
	fwrite( STDERR, "Invalid --out value.\n" );
	exit( 1 );
}
if ( ! is_dir( $out_dir ) && ! mkdir( $out_dir, 0775, true ) && ! is_dir( $out_dir ) ) {
	fwrite( STDERR, "Cannot create output dir: {$out_dir}\n" );
	exit( 1 );
}

$generator_rel = 'wp-content/themes/boltfolio/tools/generate-api-reference.php';

// Re-runs must be deterministic: drop previously generated fragments first.
foreach ( ( glob( $out_dir . '/*.html' ) ?: array() ) as $stale ) {
	@unlink( $stale );
}
if ( is_file( $out_dir . '/manifest.json' ) ) {
	@unlink( $out_dir . '/manifest.json' );
}

$files  = array();
$errors = array();

foreach ( wppo_docs_target_files( $plugin_dir ) as $rel => $section ) {
	$abs = $plugin_dir . '/' . $rel;
	try {
		$src = file_get_contents( $abs );
		if ( false === $src ) {
			throw new RuntimeException( 'cannot read file' );
		}
		$f             = wppo_docs_parse_file( $src, $rel, $section );
		$f['slug']     = wppo_docs_slug_for( $rel, $section );
		$f['hooks']    = wppo_docs_extract_hooks( $src );
		$files[ $rel ] = $f;

		file_put_contents(
			$out_dir . '/' . $f['slug'] . '.html',
			wppo_docs_render_file( $f, wppo_docs_render_hooks_section( $f['hooks'], $rel ) )
		);
	} catch ( Throwable $e ) {
		$errors[] = "{$rel}: " . $e->getMessage();
	}
}

file_put_contents( $out_dir . '/reference-index.html', wppo_docs_render_index( $files, $generator_rel ) );
foreach ( WPPO_DOCS_SECTIONS as $section => $meta ) {
	file_put_contents( $out_dir . '/' . $meta['file'], wppo_docs_render_section_hub( $section, $files ) );
}

// ---- manifest --------------------------------------------------------------
$manifest = array();

$manifest[] = array(
	'file'        => 'reference-index.html',
	'slug'        => 'reference',
	'title'       => 'Code reference',
	'menu_order'  => 0,
	'parent_slug' => null,
	'excerpt'     => 'Generated code reference for the Performance Optimisation plugin, built by static analysis of its source files.',
);

foreach ( WPPO_DOCS_SECTIONS as $meta ) {
	$manifest[] = array(
		'file'        => $meta['file'],
		'slug'        => $meta['slug'],
		'title'       => $meta['title'],
		'menu_order'  => $meta['order'],
		'parent_slug' => 'reference',
		'excerpt'     => $meta['intro'],
	);
}

$per_section = array( 'includes' => array(), 'minify' => array(), 'templates' => array(), 'root' => array() );
foreach ( $files as $f ) {
	$per_section[ $f['section'] ][] = $f;
}
foreach ( WPPO_DOCS_SECTIONS as $section => $meta ) {
	$list = $per_section[ $section ];
	usort( $list, static fn( $a, $b ) => strcmp( $a['path'], $b['path'] ) );
	$order = 10;
	foreach ( $list as $f ) {
		$excerpt = $f['filedoc']
			?? ( 'Source file ' . $f['path'] . ' with ' . count( $f['classes'] ) . ' class(es) and '
				. ( count( $f['functions'] ) + count( $f['defines'] ) ) . ' function(s) or constant definition(s).' );
		$manifest[] = array(
			'file'        => $f['slug'] . '.html',
			'slug'        => $f['slug'],
			'title'       => $f['path'],
			'menu_order'  => $order++,
			'parent_slug' => $meta['slug'],
			'excerpt'     => wppo_docs_first_sentence( $excerpt ),
		);
	}
}

file_put_contents( $out_dir . '/manifest.json', (string) json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );

// ---- console summary --------------------------------------------------------
$n_classes = 0;
$n_methods = 0;
$n_hooks   = array();
foreach ( $files as $f ) {
	$n_classes += count( $f['classes'] );
	foreach ( $f['classes'] as $c ) {
		$n_methods += count( $c['methods'] );
	}
	$n_methods += count( $f['functions'] );
	foreach ( $f['hooks'] as $h ) {
		$n_hooks[ $h['name'] ] = true;
	}
}

echo 'Wrote ' . count( $manifest ) . " files (fragments + hubs + manifest) into {$out_dir}\n";
echo 'Parsed files: ' . count( $files ) . "\n";
echo "Classes: {$n_classes}\n";
echo "Methods + top-level functions: {$n_methods}\n";
echo 'Distinct wppo_* hooks: ' . count( $n_hooks ) . "\n";
if ( $errors ) {
	echo "PARSE WARNINGS:\n" . implode( "\n", $errors ) . "\n";
	exit( 2 );
}

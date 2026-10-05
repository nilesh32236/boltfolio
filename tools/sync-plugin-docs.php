<?php
/**
 * Upsert a documentation bundle into the Boltfolio docs post type.
 *
 * Usage:
 *   php tools/sync-plugin-docs.php --bundle=/tmp/docs-bundle --product=performance-optimisation
 *   php tools/sync-plugin-docs.php --bundle=/tmp/docs-bundle --product=duoport-opencode --dry-run
 *
 * A bundle contains manifest.json plus one HTML/Gutenberg-block fragment per
 * entry. The importer is deliberately idempotent: it matches by product,
 * slug, and parent, updates existing revisions in place, and never touches
 * unrelated post types or connector settings.
 *
 * @package Boltfolio\Tools
 */

declare( strict_types = 1 );

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

$wp_root = dirname( __DIR__, 4 );
if ( ! is_file( $wp_root . '/wp-load.php' ) ) {
	fwrite( STDERR, "WordPress root not found: {$wp_root}\n" );
	exit( 1 );
}
require_once $wp_root . '/wp-load.php';

$options = getopt( '', array( 'bundle:', 'product:', 'dry-run', 'help' ) );
if ( isset( $options['help'] ) ) {
	echo "Usage: php tools/sync-plugin-docs.php --bundle=DIR --product=SLUG [--dry-run]\n";
	exit( 0 );
}

$bundle = isset( $options['bundle'] ) ? rtrim( (string) $options['bundle'], '/' ) : '';
$product = isset( $options['product'] ) ? sanitize_key( (string) $options['product'] ) : '';
if ( '' === $bundle || '' === $product ) {
	fwrite( STDERR, "--bundle and --product are required.\n" );
	exit( 2 );
}

$manifest_path = $bundle . '/manifest.json';
if ( ! is_file( $manifest_path ) ) {
	fwrite( STDERR, "Manifest not found: {$manifest_path}\n" );
	exit( 1 );
}

try {
	$manifest = json_decode( (string) file_get_contents( $manifest_path ), true, 512, JSON_THROW_ON_ERROR );
} catch ( JsonException $exception ) {
	fwrite( STDERR, "Invalid manifest JSON: {$exception->getMessage()}\n" );
	exit( 1 );
}
if ( ! is_array( $manifest ) ) {
	fwrite( STDERR, "Manifest must be a JSON array.\n" );
	exit( 1 );
}

if ( ! taxonomy_exists( Boltfolio_Docs::TAXONOMY ) ) {
	fwrite( STDERR, "Documentation taxonomy is not registered.\n" );
	exit( 1 );
}

$term = term_exists( $product, Boltfolio_Docs::TAXONOMY );
if ( ! $term ) {
	$term = wp_insert_term( $product, Boltfolio_Docs::TAXONOMY, array( 'slug' => $product ) );
}
if ( is_wp_error( $term ) ) {
	fwrite( STDERR, "Could not create product term: {$term->get_error_message()}\n" );
	exit( 1 );
}
$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );

$dry_run = isset( $options['dry-run'] );
$ids_by_slug = array();
$created = 0;
$updated = 0;
$unchanged = 0;
$errors = array();

/**
 * Find a documentation post by product, slug, and parent.
 *
 * @param string $slug     Post slug.
 * @param int    $parent   Parent post ID.
 * @param int    $term_id  Product term ID.
 * @return int Existing post ID, or 0.
 */
function boltfolio_sync_find_doc( string $slug, int $parent, int $term_id ): int {
	$query = array(
		'post_type'      => Boltfolio_Docs::POST_TYPE,
		'post_status'    => 'any',
		'name'           => $slug,
		'posts_per_page' => 50,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => Boltfolio_Docs::TAXONOMY,
				'field'    => 'term_id',
				'terms'    => array( $term_id ),
			),
		),
	);
	$candidates = get_posts( $query );
	foreach ( $candidates as $candidate ) {
		// WP_Query's name lookup can return a same-slug post from another
		// product even when a tax_query is present. Verify the relationship
		// explicitly before reusing an existing document.
		if ( ! has_term( $term_id, Boltfolio_Docs::TAXONOMY, (int) $candidate ) ) {
			continue;
		}
		if ( (int) get_post_field( 'post_parent', $candidate ) === $parent ) {
			return (int) $candidate;
		}
	}
	return 0;
}

/**
 * Resolve a parent from the current pass or an existing product post.
 *
 * @param string|null $parent_slug Parent slug.
 * @param array       $ids_by_slug Current pass map.
 * @param int         $term_id     Product term ID.
 * @return int Parent ID, or 0.
 */
function boltfolio_sync_find_parent( ?string $parent_slug, array $ids_by_slug, int $term_id ): int {
	if ( null === $parent_slug || '' === $parent_slug ) {
		return 0;
	}
	if ( isset( $ids_by_slug[ $parent_slug ] ) ) {
		return (int) $ids_by_slug[ $parent_slug ];
	}
	return boltfolio_sync_find_doc( $parent_slug, 0, $term_id );
}

foreach ( $manifest as $index => $entry ) {
	if ( ! is_array( $entry ) ) {
		$errors[] = "entry {$index}: expected an object";
		continue;
	}
	$file = isset( $entry['file'] ) ? (string) $entry['file'] : '';
	$slug = isset( $entry['slug'] ) ? sanitize_title( (string) $entry['slug'] ) : '';
	$title = isset( $entry['title'] ) ? sanitize_text_field( (string) $entry['title'] ) : '';
	$parent_slug = isset( $entry['parent_slug'] ) && null !== $entry['parent_slug']
		? sanitize_title( (string) $entry['parent_slug'] )
		: null;
	$content_path = $bundle . '/' . ltrim( $file, '/' );
	if ( '' === $file || '' === $slug || '' === $title || ! is_file( $content_path ) ) {
		$errors[] = "entry {$index}: file, slug, title, and an existing fragment are required";
		continue;
	}

	$parent_id = boltfolio_sync_find_parent( $parent_slug, $ids_by_slug, $term_id );
	$post_id = boltfolio_sync_find_doc( $slug, $parent_id, $term_id );
	$is_new  = 0 === $post_id;
	$content = (string) file_get_contents( $content_path );
	$excerpt = isset( $entry['excerpt'] ) ? sanitize_textarea_field( (string) $entry['excerpt'] ) : '';
	if ( '' === $excerpt && $post_id ) {
		$excerpt = sanitize_textarea_field( (string) get_post_field( 'post_excerpt', $post_id ) );
	}
	if ( '' === $excerpt ) {
		$excerpt = wp_trim_words( $title, 22, '…' );
	}
	$order = isset( $entry['menu_order'] ) ? (int) $entry['menu_order'] : 0;
	$postarr = array(
		'ID'           => $post_id,
		'post_type'    => Boltfolio_Docs::POST_TYPE,
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_parent'  => $parent_id,
		'post_content' => wp_slash( $content ),
		'post_excerpt' => $excerpt,
		'menu_order'   => $order,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);

	if ( $dry_run ) {
		// Use a synthetic negative ID for a not-yet-created parent so the
		// dry-run still exercises the manifest hierarchy without writing.
		$ids_by_slug[ $slug ] = $post_id ?: -( $index + 1 );
		if ( $post_id ) {
			++$updated;
		} else {
			++$created;
		}
		printf( "[dry-run] %s %s (parent=%d)\n", $post_id ? 'update' : 'create', $slug, $parent_id );
		continue;
	}

	$result = wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $result ) ) {
		$errors[] = "{$slug}: {$result->get_error_message()}";
		continue;
	}
	$post_id = (int) $result;
	wp_set_object_terms( $post_id, array( $term_id ), Boltfolio_Docs::TAXONOMY, false );
	update_post_meta( $post_id, '_boltfolio_generated', '1', true );
	update_post_meta( $post_id, '_boltfolio_product', $product, true );
	update_post_meta( $post_id, '_boltfolio_source', basename( $bundle ), true );
	$ids_by_slug[ $slug ] = $post_id;
	if ( $is_new ) {
		++$created;
	} else {
		++$updated;
	}
	clean_post_cache( $post_id );
}

if ( $errors ) {
	fwrite( STDERR, "Errors:\n- " . implode( "\n- ", $errors ) . "\n" );
	exit( 1 );
}

printf(
	"Product: %s\nCreated: %d\nUpdated: %d\nUnchanged: %d\n",
	$product,
	$created,
	$updated,
	$unchanged
);
exit( 0 );

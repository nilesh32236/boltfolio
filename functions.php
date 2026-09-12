<?php
/**
 * Boltfolio theme bootstrap.
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BOLTFOLIO_VERSION', '2.0.0' );

require get_template_directory() . '/inc/class-boltfolio-projects.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/class-boltfolio-content.php';
require get_template_directory() . '/inc/class-boltfolio-docs.php';
require get_template_directory() . '/inc/class-boltfolio-docs-search.php';
require get_template_directory() . '/inc/class-boltfolio-docs-redirects.php';

/**
 * Auto-register every compiled block in the master block suite.
 *
 * Scans each compiled manifest under `blocks/build/{slug}/block.json`
 * so new blocks added via create-block need no extra PHP — just
 * `npm run build`.
 *
 * @return void
 */
function boltfolio_register_blocks(): void {
	$build_dir = get_template_directory() . '/blocks/build';

	if ( ! is_dir( $build_dir ) ) {
		return;
	}

	foreach ( glob( $build_dir . '/*/block.json' ) as $manifest ) {
		register_block_type( dirname( $manifest ) );
	}
}
add_action( 'init', 'boltfolio_register_blocks' );

/**
 * Theme setup.
 *
 * @return void
 */
function boltfolio_setup(): void {
	load_theme_textdomain( 'boltfolio', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 48,
			'width'       => 160,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'boltfolio' ),
			'footer'  => __( 'Footer Menu', 'boltfolio' ),
		)
	);
}
add_action( 'after_setup_theme', 'boltfolio_setup' );

/**
 * Content width for oEmbed and large media.
 *
 * @return void
 */
function boltfolio_content_width(): void {
	$GLOBALS['content_width'] = 1240;
}
add_action( 'after_setup_theme', 'boltfolio_content_width', 0 );

/**
 * Whether the current request is anywhere in the documentation.
 *
 * @return bool
 */
function boltfolio_is_docs_context(): bool {
	if ( ! class_exists( 'Boltfolio_Docs' ) ) {
		return false;
	}

	return is_singular( Boltfolio_Docs::POST_TYPE )
		|| is_post_type_archive( Boltfolio_Docs::POST_TYPE )
		|| is_tax( Boltfolio_Docs::TAXONOMY );
}

/**
 * Enqueue front-end assets with filemtime cache busting.
 *
 * @return void
 */
function boltfolio_assets(): void {
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	$ver = static function ( string $relative ) use ( $dir ): string {
		$path = $dir . $relative;

		return file_exists( $path ) ? (string) filemtime( $path ) : BOLTFOLIO_VERSION;
	};

	// Self-hosted variable fonts. Latin + latin-ext subsets only; the
	// browser fetches a subset solely when those glyphs actually appear.
	wp_enqueue_style( 'boltfolio-fonts', $uri . '/assets/fonts/fonts.css', array(), $ver( '/assets/fonts/fonts.css' ) );

	wp_enqueue_style( 'boltfolio-style', get_stylesheet_uri(), array( 'boltfolio-fonts' ), $ver( '/style.css' ) );

	$script = '/assets/js/main.js';

	if ( file_exists( $dir . $script ) ) {
		wp_enqueue_script(
			'boltfolio-script',
			$uri . $script,
			array(),
			$ver( $script ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( boltfolio_is_docs_context() ) {
		$docs_css = '/assets/css/docs.css';

		wp_enqueue_style( 'boltfolio-docs', $uri . $docs_css, array( 'boltfolio-style' ), $ver( $docs_css ) );
	}
}
add_action( 'wp_enqueue_scripts', 'boltfolio_assets' );

/**
 * Declare the theme's script as non-delayable.
 *
 * Optimisation plugins that "delay JS until interaction" rewrite script
 * tags into an inert `type` so the browser never executes them. That is
 * fatal for the mobile menu, the docs table of contents and the code
 * copy buttons, because the first tap is consumed by the delayed script
 * instead of opening the menu.
 *
 * @param array<int, string> $exclusions Handle or URL fragments to keep eager.
 * @return array<int, string>
 */
function boltfolio_exclude_script_from_delay( array $exclusions ): array {
	$exclusions[] = 'boltfolio-script';
	$exclusions[] = 'main.js';

	return $exclusions;
}
add_filter( 'wppo_exclude_delay_js', 'boltfolio_exclude_script_from_delay' );
add_filter( 'wppo_delay_js_exclusions', 'boltfolio_exclude_script_from_delay' );

/**
 * Trim archive excerpts.
 *
 * @param int $length Excerpt length.
 * @return int
 */
function boltfolio_excerpt_length( int $length ): int {
	return is_admin() ? $length : 26;
}
add_filter( 'excerpt_length', 'boltfolio_excerpt_length' );

/**
 * Replace default excerpt ellipsis.
 *
 * @param string $more The string shown within the more link.
 * @return string
 */
function boltfolio_excerpt_more( string $more ): string {
	return is_admin() ? $more : '&hellip;';
}
add_filter( 'excerpt_more', 'boltfolio_excerpt_more' );

/**
 * Add helpful body classes.
 *
 * @param array<string> $classes Body classes.
 * @return array<string>
 */
function boltfolio_body_classes( array $classes ): array {
	if ( is_front_page() ) {
		$classes[] = 'is-home';
	}

	if ( is_singular( 'project' ) || is_post_type_archive( 'project' ) || is_tax( 'project_type' ) ) {
		$classes[] = 'is-projects';
	}

	if ( boltfolio_is_docs_context() ) {
		$classes[] = 'is-docs';
	}

	return $classes;
}
add_filter( 'body_class', 'boltfolio_body_classes' );

/**
 * Mark the document as JS-capable before first paint so reveal
 * animations never hide content when scripting is unavailable.
 *
 * @return void
 */
function boltfolio_js_flag(): void {
	echo "<script>document.documentElement.classList.add('js');</script>\n";
}
add_action( 'wp_head', 'boltfolio_js_flag', 0 );

/**
 * Critical inline behaviour that must survive aggressive optimisation.
 *
 * The mobile menu is the one interaction a visitor needs before any
 * deferred script has run: if it is delayed, the first tap is swallowed
 * and the menu never opens. It is a few hundred bytes, so it ships
 * inline rather than risk that.
 *
 * @return void
 */
function boltfolio_critical_js(): void {
	?>
	<script>
	( function () {
		var toggle = document.querySelector( '.nav-toggle' );
		var nav = document.getElementById( 'site-nav' );
		var side = document.querySelector( '.docs-sidebar' );
		var sideToggle = document.querySelector( '.docs-sidebar__toggle' );

		if ( toggle && nav ) {
			toggle.addEventListener( 'click', function () {
				var open = nav.classList.toggle( 'is-open' );
				toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );
			document.addEventListener( 'keydown', function ( e ) {
				if ( 'Escape' === e.key && nav.classList.contains( 'is-open' ) ) {
					nav.classList.remove( 'is-open' );
					toggle.setAttribute( 'aria-expanded', 'false' );
					toggle.focus();
				}
			} );
		}

		if ( side && sideToggle ) {
			sideToggle.addEventListener( 'click', function () {
				var open = 'true' !== side.getAttribute( 'data-open' );
				side.setAttribute( 'data-open', open ? 'true' : 'false' );
				sideToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );
		}
	} )();
	</script>
	<?php
}
add_action( 'wp_footer', 'boltfolio_critical_js', 5 );

/**
 * Reading time in minutes for a piece of content.
 *
 * @param int|null $post_id Post ID, defaults to current.
 * @return int
 */
function boltfolio_reading_time( ?int $post_id = null ): int {
	$content = (string) get_post_field( 'post_content', $post_id ?? get_the_ID() );
	$words   = str_word_count( wp_strip_all_tags( $content ) );

	return max( 1, (int) ceil( $words / 220 ) );
}

/**
 * Site-wide counts used in the hero and the docs hub.
 *
 * @return array{docs:int, projects:int, classes:int}
 */
function boltfolio_stats(): array {
	$cached = get_transient( 'boltfolio_stats' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$stats = array(
		'docs'     => (int) wp_count_posts( Boltfolio_Docs::POST_TYPE )->publish,
		'projects' => (int) wp_count_posts( 'project' )->publish,
		'classes'  => 0,
	);

	// "Classes" counts the source-reference groups in the docs tree, which
	// is the honest denominator for how much of the codebase is documented.
	$reference = get_page_by_path( 'reference', OBJECT, Boltfolio_Docs::POST_TYPE );

	if ( $reference instanceof WP_Post ) {
		$stats['classes'] = count(
			get_posts(
				array(
					'post_type'      => Boltfolio_Docs::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'post_parent'    => $reference->ID,
				)
			)
		);
	}

	set_transient( 'boltfolio_stats', $stats, HOUR_IN_SECONDS );

	return $stats;
}

/**
 * Invalidate cached counts and the docs search index when content changes.
 *
 * @return void
 */
function boltfolio_flush_caches(): void {
	delete_transient( 'boltfolio_stats' );
	delete_transient( 'boltfolio_docs_index' );
}
add_action( 'save_post', 'boltfolio_flush_caches' );
add_action( 'deleted_post', 'boltfolio_flush_caches' );

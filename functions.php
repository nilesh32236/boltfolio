<?php
/**
 * Boltfolio theme bootstrap.
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BOLTFOLIO_VERSION', '1.0.0' );

require get_template_directory() . '/inc/class-boltfolio-projects.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/class-boltfolio-content.php';
require get_template_directory() . '/inc/class-boltfolio-docs.php';
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

	add_image_size( 'boltfolio-card', 800, 500, true );
}
add_action( 'after_setup_theme', 'boltfolio_setup' );

/**
 * Content width for oEmbed and large media.
 *
 * @return void
 */
function boltfolio_content_width(): void {
	$GLOBALS['content_width'] = 1100;
}
add_action( 'after_setup_theme', 'boltfolio_content_width', 0 );

/**
 * Enqueue front-end assets with filemtime cache busting.
 *
 * @return void
 */
function boltfolio_assets(): void {
	$style_file  = get_template_directory() . '/style.css';
	$script_file = get_template_directory() . '/assets/js/main.js';

	wp_enqueue_style(
		'boltfolio-style',
		get_stylesheet_uri(),
		array(),
		file_exists( $style_file ) ? (string) filemtime( $style_file ) : BOLTFOLIO_VERSION
	);

	if ( file_exists( $script_file ) ) {
		wp_enqueue_script(
			'boltfolio-script',
			get_template_directory_uri() . '/assets/js/main.js',
			array(),
			(string) filemtime( $script_file ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_page_template( 'page-docs.php' ) || is_singular( Boltfolio_Docs::POST_TYPE ) || is_post_type_archive( Boltfolio_Docs::POST_TYPE ) || is_tax( Boltfolio_Docs::TAXONOMY ) ) {
		$docs_css = get_template_directory() . '/assets/css/docs.css';

		if ( file_exists( $docs_css ) ) {
			wp_enqueue_style(
				'boltfolio-docs',
				get_template_directory_uri() . '/assets/css/docs.css',
				array( 'boltfolio-style' ),
				(string) filemtime( $docs_css )
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'boltfolio_assets' );

// Keep nav-critical theme JS out of delay loading: the first tap would be consumed by the delayed script instead of opening the mobile menu.
add_filter(
	'wppo_exclude_delay_js',
	static function ( array $exclusions ): array {
		$exclusions[] = 'boltfolio-script';

		return $exclusions;
	}
);

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
		$classes[] = 'bolt-home';
	}
	if ( is_singular( 'project' ) || is_post_type_archive( 'project' ) ) {
		$classes[] = 'bolt-projects';
	}
	return $classes;
}
add_filter( 'body_class', 'boltfolio_body_classes' );

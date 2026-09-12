<?php
/**
 * 301 redirects from legacy docs page URLs to the docs CPT URLs.
 *
 * Runs at template_redirect (before output) and only acts on 404 responses,
 * so legacy /docs/... page URLs keep working while the old pages are being
 * retired. `/docs/` itself is never redirected — the CPT archive serves it.
 *
 * Extend or override the map with the `boltfolio_docs_redirect_map` filter
 * (old path => new path, both relative, no leading/trailing slashes).
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Boltfolio_Docs_Redirects {

	/**
	 * Wire hooks.
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 1 );
	}

	/**
	 * Redirect a legacy docs URL to its new CPT URL (301).
	 */
	public static function maybe_redirect(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! is_404() ) {
			return;
		}

		$path = self::request_path();

		// Never touch /docs/ itself — the CPT archive owns it.
		if ( '' === $path || 'docs' === $path ) {
			return;
		}

		$target = self::resolve( $path );

		if ( null === $target || $target === $path ) {
			return;
		}

		wp_safe_redirect( home_url( '/' . $target . '/' ), 301 );
		exit;
	}

	/**
	 * Normalized request path (no leading/trailing slashes, urldecoded).
	 *
	 * @return string
	 */
	private static function request_path(): string {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- path is parsed, not stored.
		$path = (string) parse_url( $uri, PHP_URL_PATH );

		return trim( (string) rawurldecode( $path ), '/' );
	}

	/**
	 * Resolve a legacy path to its new path, or null when unmapped.
	 *
	 * @param string $path Normalized request path.
	 * @return string|null
	 */
	public static function resolve( string $path ): ?string {
		$map = apply_filters( 'boltfolio_docs_redirect_map', self::map() );

		if ( isset( $map[ $path ] ) ) {
			return $map[ $path ];
		}

		// Per-file API reference pages: /docs/reference/<section>/<file>/ keeps
		// its tail under the new product prefix. Bounded to the four known
		// reference sections and exactly one trailing segment.
		$parts = explode( '/', $path );

		if (
			4 === count( $parts )
			&& 'docs' === $parts[0]
			&& 'reference' === $parts[1]
			&& in_array( $parts[2], self::reference_sections(), true )
			&& '' !== $parts[3]
		) {
			return 'docs/performance-optimisation/reference/' . $parts[2] . '/' . $parts[3];
		}

		return null;
	}

	/**
	 * Reference section slugs.
	 *
	 * @return array<string>
	 */
	private static function reference_sections(): array {
		return array( 'reference-includes', 'reference-minify', 'reference-templates', 'reference-root' );
	}

	/**
	 * Static legacy-to-new path map.
	 *
	 * @return array<string, string>
	 */
	private static function map(): array {
		$product = 'docs/performance-optimisation';

		$map = array(
			'docs/installation'   => $product . '/installation',
			'docs/features'       => $product . '/features',
			'docs/litespeed'      => $product . '/litespeed',
			'docs/configuration'  => $product . '/configuration',
			'docs/troubleshooting' => $product . '/troubleshooting',
			'docs/faq'            => $product . '/faq',
			'docs/reference'      => $product . '/reference',
		);

		foreach ( self::feature_slugs() as $slug ) {
			$map[ 'docs/features/' . $slug ] = $product . '/features/' . $slug;
		}

		foreach ( self::config_slugs() as $slug ) {
			$map[ 'docs/configuration/' . $slug ] = $product . '/configuration/' . $slug;
		}

		foreach ( self::reference_sections() as $section ) {
			$map[ 'docs/reference/' . $section ] = $product . '/reference/' . $section;
		}

		return $map;
	}

	/**
	 * Legacy feature page slugs.
	 *
	 * @return array<string>
	 */
	private static function feature_slugs(): array {
		return array(
			'page-cache',
			'redis-object-cache',
			'minify-combine',
			'defer-delay-js',
			'used-critical-css',
			'images-webp-avif',
			'database-cleanup',
			'preload-speculation',
			'monitoring-rum',
		);
	}

	/**
	 * Legacy configuration page slugs.
	 *
	 * @return array<string>
	 */
	private static function config_slugs(): array {
		return array( 'settings-reference', 'hooks', 'wp-cli', 'rest-api' );
	}
}

Boltfolio_Docs_Redirects::init();

<?php
/**
 * 301 redirects for documentation URLs that have moved.
 *
 * Two generations of URL exist in the wild:
 *
 *  1. Legacy pages:       /docs/installation/           (pre-CPT)
 *  2. Pre-cleanup CPT:    /docs/<product>/reference/reference-includes/class-main/
 *
 * The second group is recorded in the `boltfolio_docs_url_map` option by
 * the slug migration, because a rename of a branch changes the path of
 * every page beneath it and that mapping cannot be derived at runtime.
 *
 * Both are resolved only when WordPress has already decided the request
 * is a 404, so a live page is never second-guessed.
 *
 * Extend or override with the `boltfolio_docs_redirect_map` filter
 * (old path => new path, both relative, no leading or trailing slashes).
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Boltfolio_Docs_Redirects {

	public const OPTION = 'boltfolio_docs_url_map';

	/**
	 * Wire hooks.
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 1 );
	}

	/**
	 * Redirect a moved docs URL to its current location.
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
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed, not stored.
		$path = (string) parse_url( $uri, PHP_URL_PATH );

		return trim( (string) rawurldecode( $path ), '/' );
	}

	/**
	 * Resolve a moved path to its current path, or null when unmapped.
	 *
	 * @param string $path Normalized request path.
	 * @return string|null
	 */
	public static function resolve( string $path ): ?string {
		$map = apply_filters( 'boltfolio_docs_redirect_map', self::map() );

		if ( isset( $map[ $path ] ) ) {
			return $map[ $path ];
		}

		// Per-file API reference pages: /docs/reference/<section>/<file>/
		// keeps its tail under the product prefix. Bounded to the known
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
	 * Reference branch slugs, in both their legacy and current spelling.
	 *
	 * @return array<string>
	 */
	private static function reference_sections(): array {
		return array( 'includes', 'minify', 'templates', 'entry-points', 'reference-includes', 'reference-minify', 'reference-templates', 'reference-root' );
	}

	/**
	 * Every known moved path.
	 *
	 * @return array<string, string>
	 */
	private static function map(): array {
		$product = 'docs/performance-optimisation';

		$map = array(
			'docs/installation'    => $product . '/installation',
			'docs/features'        => $product . '/features',
			'docs/litespeed'       => $product . '/litespeed',
			'docs/configuration'   => $product . '/configuration',
			'docs/troubleshooting' => $product . '/troubleshooting',
			'docs/faq'             => $product . '/faq',
			'docs/reference'       => $product . '/reference',
		);

		foreach ( self::feature_slugs() as $slug ) {
			$map[ 'docs/features/' . $slug ] = $product . '/features/' . $slug;
		}

		foreach ( self::config_slugs() as $slug ) {
			$map[ 'docs/configuration/' . $slug ] = $product . '/configuration/' . $slug;
		}

		foreach ( self::reference_sections() as $slug ) {
			$map[ 'docs/reference/' . $slug ] = $product . '/reference/' . $slug;
		}

		// Paths recorded by the slug migration: branch renames cascade to
		// every descendant, so the list has to be stored rather than
		// recomputed.
		$recorded = get_option( self::OPTION );

		if ( is_array( $recorded ) ) {
			foreach ( $recorded as $old => $new ) {
				if ( is_string( $old ) && is_string( $new ) && '' !== $old && '' !== $new ) {
					$map[ $old ] = $new;
				}
			}
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

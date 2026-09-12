<?php
/**
 * Documentation search.
 *
 * A 75-page reference is unusable without search, and a round trip per
 * keystroke is the wrong shape for one. The whole documentation set is
 * small enough to ship as a single pre-built index, so the browser
 * filters it locally: results appear as you type, offline, with no
 * request after the first.
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Boltfolio_Docs_Search {

	public const TRANSIENT = 'boltfolio_docs_index';
	public const REST_NS   = 'boltfolio/v1';

	/**
	 * Wire hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_footer', array( __CLASS__, 'print_config' ), 6 );
	}

	/**
	 * Expose the index at /wp-json/boltfolio/v1/docs-index.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/docs-index',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'serve' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Serve the cached index, rebuilding it when cold.
	 *
	 * @return WP_REST_Response
	 */
	public static function serve(): WP_REST_Response {
		$response = new WP_REST_Response( self::index() );
		$response->set_headers( array( 'Cache-Control' => 'public, max-age=3600' ) );

		return $response;
	}

	/**
	 * The documentation index: one record per published doc, carrying the
	 * title, its position in the tree, and its section headings.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function index(): array {
		$cached = get_transient( self::TRANSIENT );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$posts = get_posts(
			array(
				'post_type'      => Boltfolio_Docs::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);

		$index = array();

		foreach ( $posts as $post ) {
			$url      = get_permalink( $post );
			$trail    = self::trail( $post );
			$headings = self::headings( (string) $post->post_content );

			$index[] = array(
				'id'    => (int) $post->ID,
				'title' => self::clean_title( $post->post_title ),
				'url'   => $url,
				'trail' => $trail,
				// Lower-cased haystacks keep the client-side filter trivial.
				'key'   => strtolower( self::clean_title( $post->post_title ) . ' ' . $trail ),
				'heads' => $headings,
			);
		}

		set_transient( self::TRANSIENT, $index, WEEK_IN_SECONDS );

		return $index;
	}

	/**
	 * Human-readable breadcrumb trail, excluding the current doc.
	 *
	 * @param WP_Post $post Doc.
	 * @return string
	 */
	private static function trail( WP_Post $post ): string {
		$parts = array();

		foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor_id ) {
			$title = get_the_title( $ancestor_id );

			if ( '' !== $title ) {
				$parts[] = self::clean_title( $title );
			}
		}

		return implode( ' › ', $parts );
	}

	/**
	 * Doc titles are often source paths. Strip the noise so search and
	 * display show the part a reader would actually type.
	 *
	 * @param string $title Raw title.
	 * @return string
	 */
	public static function clean_title( string $title ): string {
		return trim( str_replace( array( '&#8212;', '&mdash;' ), '-', $title ) );
	}

	/**
	 * Section headings inside a doc, used to land a search result deeper
	 * than the page top and to power the "on this page" rail.
	 *
	 * @param string $content Raw post content.
	 * @return array<int, array{text:string, id:string, level:int}>
	 */
	private static function headings( string $content ): array {
		if ( ! preg_match_all( '/<h([23])\b([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
			return array();
		}

		$headings = array();

		foreach ( $matches as $match ) {
			$attrs = $match[2];
			$id    = '';

			if ( preg_match( '/\bid\s*=\s*"([^"]+)"/i', $attrs, $id_match ) ) {
				$id = $id_match[1];
			}

			$text = trim( wp_strip_all_tags( $match[3] ) );

			if ( '' === $text ) {
				continue;
			}

			$headings[] = array(
				'text'  => $text,
				'id'    => $id,
				'level' => (int) $match[1],
			);
		}

		return $headings;
	}

	/**
	 * Inline configuration for the client: the endpoint and a small
	 * amount of copy the modal needs.
	 *
	 * @return void
	 */
	public static function print_config(): void {
		if ( ! boltfolio_is_docs_context() ) {
			return;
		}

		$config = array(
			'endpoint' => esc_url_raw( rest_url( self::REST_NS . '/docs-index' ) ),
			'label'    => __( 'Search documentation', 'boltfolio' ),
			'empty'    => __( 'No pages match that search.', 'boltfolio' ),
			'hint'     => __( 'Search every guide, hook and class in the reference.', 'boltfolio' ),
		);

		printf(
			"<script id=\"boltfolio-search-config\" type=\"application/json\">%s</script>\n",
			wp_json_encode( $config )
		);
	}
}

Boltfolio_Docs_Search::init();

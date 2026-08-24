<?php
/**
 * Content enhancements: heading anchors, callout shortcode and custom block.
 *
 * Registers the `boltfolio/callout` dynamic block (PHP-rendered) plus an
 * equivalent `[callout]` shortcode, and injects anchor IDs into h2/h3
 * elements so the documentation table of contents can link to sections.
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Boltfolio_Content {

	/**
	 * Wire hooks.
	 */
	public static function init(): void {
		add_filter( 'the_content', array( __CLASS__, 'anchor_headings' ), 20 );
		add_shortcode( 'callout', array( __CLASS__, 'render_callout_shortcode' ) );
		add_filter( 'nav_menu_css_class', array( __CLASS__, 'archive_menu_current' ), 10, 3 );
	}

	/**
	 * Mark the Projects archive item as current on project singles/terms.
	 *
	 * @param array<string> $classes Nav item classes.
	 * @param WP_Post       $item    Menu item.
	 * @param stdClass      $args    Menu args.
	 * @return array<string>
	 */
	public static function archive_menu_current( array $classes, $item, $args ): array {
		if ( ( $args->theme_location ?? '' ) !== 'primary' ) {
			return $classes;
		}

		$is_project_archive_item = ( $item->type ?? '' ) === 'post_type_archive'
			&& ( $item->object ?? '' ) === 'project';

		if ( ! $is_project_archive_item || in_array( 'current-menu-item', $classes, true ) ) {
			return $classes;
		}

		if (
			is_singular( 'project' )
			|| is_post_type_archive( 'project' )
			|| is_tax( Boltfolio_Projects::TAXONOMY )
		) {
			$classes[] = 'current-menu-item';
		}

		return $classes;
	}

	/**
	 * Add IDs to h2/h3 headings (skipping ones that already have them).
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function anchor_headings( string $content ): string {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() || '' === $content ) {
			return $content;
		}

		$seen = array();

		$content = preg_replace_callback(
			'/<h([23])((?:\s[^>]*)?)>(.*?)<\/h\1>/is',
			static function ( array $matches ) use ( &$seen ): string {
				$text = trim( wp_strip_all_tags( $matches[3] ) );

				if ( '' === $text || false !== stripos( $matches[2], ' id=' ) ) {
					return $matches[0];
				}

				$id = sanitize_title( $text );
				$n  = '';

				while ( isset( $seen[ $id . $n ] ) ) {
					$n = (string) ( (int) $n + 1 );
				}

				$seen[ $id . $n ] = true;

				return sprintf(
					'<h%1$s%2$s id="%3$s">%4$s</h%1$s>',
					$matches[1],
					$matches[2],
					esc_attr( $id . $n ),
					$matches[3]
				);
			},
			$content
		);

		return is_string( $content ) ? $content : '';
	}

	/**
	 * Render a callout wrapper around inner block content.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $inner      Inner blocks HTML.
	 * @return string
	 */
	public static function render_callout( array $attributes, string $inner = '' ): string {
		$type  = ( isset( $attributes['type'] ) && in_array( $attributes['type'], array( 'info', 'tip', 'warning' ), true ) )
			? $attributes['type']
			: 'info';
		$title = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';

		ob_start();
		?>
		<div class="callout callout-<?php echo esc_attr( $type ); ?>">
			<span class="callout-icon" aria-hidden="true">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo self::icon_path( $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted path data. ?></svg>
			</span>
			<div class="callout-body">
				<?php if ( '' !== $title ) : ?>
					<p class="callout-title"><?php echo esc_html( $title ); ?></p>
				<?php endif; ?>
				<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner block output. ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * `[callout type="info|tip|warning" title="…"]Body[/callout]` shortcode.
	 *
	 * @param array<string, mixed>|string $atts    Shortcode attributes.
	 * @param string                      $content Enclosed content.
	 * @return string
	 */
	public static function render_callout_shortcode( array|string $atts, string $content = '' ): string {
		$atts = shortcode_atts(
			array(
				'type'  => 'info',
				'title' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'callout'
		);

		return self::render_callout( $atts, wpautop( trim( $content ) ) );
	}

	/**
	 * Icon path data per callout type.
	 *
	 * @param string $type Callout type.
	 * @return string
	 */
	private static function icon_path( string $type ): string {
		switch ( $type ) {
			case 'tip':
				return '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>';
			case 'warning':
				return '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>';
			default:
				return '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>';
		}
	}

}

Boltfolio_Content::init();

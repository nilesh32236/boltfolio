<?php
/**
 * Template tags and reusable markup helpers.
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social profile links used across the site.
 *
 * @return array<int, array{label:string, url:string, icon:string}>
 */
function boltfolio_social_links(): array {
	return array(
		array(
			'label' => __( 'GitHub', 'boltfolio' ),
			'url'   => 'https://github.com/nilesh32236',
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>',
		),
		array(
			'label' => __( 'LinkedIn', 'boltfolio' ),
			'url'   => 'https://www.linkedin.com/in/nilesh-kanzariya-a8019b254/',
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13Zm1.78 13.02H3.56V9h3.56v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/></svg>',
		),
		array(
			'label' => __( 'Email', 'boltfolio' ),
			'url'   => 'mailto:nilesh.kanzariya912@gmail.com',
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>',
		),
	);
}

/**
 * Render social icon anchors.
 *
 * @return void
 */
function boltfolio_social_icons(): void {
	foreach ( boltfolio_social_links() as $link ) {
		printf(
			'<a class="icon-link" href="%1$s" aria-label="%2$s"%3$s>%4$s</a>',
			esc_url( $link['url'] ),
			esc_attr( $link['label'] ),
			str_starts_with( $link['url'], 'mailto:' ) ? '' : ' target="_blank" rel="noopener noreferrer"',
			$link['icon'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG.
		);
	}
}

/**
 * Wordmark: a monospace monogram plus the name.
 *
 * The site belongs to one engineer, so the mark is his initials set in
 * the same mono face used for every measurement on the page.
 *
 * @return void
 */
function boltfolio_branding(): void {
	?>
	<a class="branding" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<span class="branding__mark" aria-hidden="true">NK</span>
		<span class="branding__name"><?php bloginfo( 'name' ); ?></span>
	</a>
	<?php
}

/**
 * Render one row of the project index.
 *
 * Six projects do not need a card grid; they need an index. Each row
 * carries its position, title, one-line description, categories and the
 * source link, separated by hairlines rather than boxed.
 *
 * @param int $post_id Project post ID.
 * @param int $index   Zero-based position in the list.
 * @return void
 */
function boltfolio_project_row( int $post_id, int $index = 0 ): void {
	$permalink = get_permalink( $post_id );
	$github    = (string) get_post_meta( $post_id, 'github_url', true );
	$terms     = get_the_terms( $post_id, Boltfolio_Projects::TAXONOMY );
	$excerpt   = get_the_excerpt( $post_id );
	?>
	<li class="index__item">
		<a class="index__link" href="<?php echo esc_url( $permalink ); ?>">
			<span class="index__num"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
			<span class="index__body">
				<span class="index__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
				<?php if ( $excerpt ) : ?>
					<span class="index__desc"><?php echo esc_html( wp_trim_words( $excerpt, 30 ) ); ?></span>
				<?php endif; ?>
				<?php if ( is_array( $terms ) && $terms ) : ?>
					<span class="index__tags">
						<?php foreach ( $terms as $term ) : ?>
							<span class="tag"><?php echo esc_html( $term->name ); ?></span>
						<?php endforeach; ?>
					</span>
				<?php endif; ?>
			</span>
			<span class="index__aside">
				<span class="index__meta"><?php echo esc_html( get_the_date( 'Y', $post_id ) ); ?></span>
				<?php if ( $github ) : ?>
					<span class="index__year"><?php esc_html_e( 'Source ↗', 'boltfolio' ); ?></span>
				<?php endif; ?>
			</span>
		</a>
	</li>
	<?php
}

/**
 * Breadcrumb trail.
 *
 * @param array<int, array{label:string, url?:string}> $items Trail, last item is current.
 * @return void
 */
function boltfolio_crumbs( array $items ): void {
	if ( ! $items ) {
		return;
	}

	echo '<nav class="crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'boltfolio' ) . '">';
	$last = count( $items ) - 1;

	foreach ( $items as $index => $item ) {
		if ( $index > 0 ) {
			echo '<span class="crumbs__sep" aria-hidden="true">/</span>';
		}

		if ( $index === $last || empty( $item['url'] ) ) {
			printf( '<span aria-current="page">%s</span>', esc_html( $item['label'] ) );
		} else {
			printf( '<a href="%1$s">%2$s</a>', esc_url( $item['url'] ), esc_html( $item['label'] ) );
		}
	}

	echo '</nav>';
}

/**
 * Breadcrumb trail for a single doc, walking its real ancestor chain.
 *
 * @param int $doc_id Doc post ID.
 * @return void
 */
function boltfolio_docs_crumbs( int $doc_id ): void {
	$items = array(
		array(
			'label' => __( 'Home', 'boltfolio' ),
			'url'   => home_url( '/' ),
		),
		array(
			'label' => __( 'Docs', 'boltfolio' ),
			'url'   => (string) get_post_type_archive_link( Boltfolio_Docs::POST_TYPE ),
		),
	);

	foreach ( Boltfolio_Docs::ancestors( $doc_id ) as $ancestor ) {
		$items[] = array(
			'label' => Boltfolio_Docs::display_title( $ancestor->post_title ),
			'url'   => (string) get_permalink( $ancestor ),
		);
	}

	$items[] = array(
		'label' => Boltfolio_Docs::display_title( get_the_title( $doc_id ) ),
	);

	boltfolio_crumbs( $items );
}

/**
 * Term badges for a project.
 *
 * @param int|null $post_id Post ID, defaults to current.
 * @return void
 */
function boltfolio_term_badges( ?int $post_id = null ): void {
	$terms = get_the_terms( $post_id ?? get_the_ID(), Boltfolio_Projects::TAXONOMY );

	if ( ! is_array( $terms ) || ! $terms ) {
		return;
	}

	echo '<div class="index__tags">';

	foreach ( $terms as $term ) {
		printf( '<span class="tag">%s</span>', esc_html( $term->name ) );
	}

	echo '</div>';
}

/**
 * The version string of the documented plugin, read from its header.
 *
 * Shown in the docs sidebar so readers always know which release the
 * reference describes.
 *
 * @return string
 */
function boltfolio_documented_version(): string {
	$cached = get_transient( 'boltfolio_plugin_version' );

	if ( is_string( $cached ) && ! empty( $cached ) ) {
		return $cached;
	}

	$version = '';
	$header  = WP_PLUGIN_DIR . '/performance-optimisation/performance-optimisation.php';

	if ( file_exists( $header ) ) {
		$data = get_file_data( $header, array( 'Version' => 'Version' ) );

		if ( ! empty( $data['Version'] ) ) {
			$version = (string) $data['Version'];
		}
	}

	set_transient( 'boltfolio_plugin_version', $version, DAY_IN_SECONDS );

	return $version;
}

/**
 * Keyboard shortcut label for the docs search.
 *
 * macOS gets the command glyph; the browser rewrites it for other
 * platforms, because guessing the OS server-side is unreliable.
 *
 * @return string
 */
function boltfolio_search_shortcut_label(): string {
	return '⌘K';
}

/**
 * Fallback primary navigation when no menu has been assigned yet.
 *
 * Keeps the site navigable straight after activation instead of
 * rendering an empty header.
 *
 * @return void
 */
function boltfolio_primary_menu_fallback(): void {
	$items = array(
		array(
			'label' => __( 'Work', 'boltfolio' ),
			'url'   => (string) get_post_type_archive_link( 'project' ),
		),
		array(
			'label' => __( 'Docs', 'boltfolio' ),
			'url'   => (string) get_post_type_archive_link( Boltfolio_Docs::POST_TYPE ),
		),
	);

	foreach ( array( 'about', 'contact' ) as $slug ) {
		$page = get_page_by_path( $slug );

		if ( $page instanceof WP_Post ) {
			$items[] = array(
				'label' => get_the_title( $page ),
				'url'   => (string) get_permalink( $page ),
			);
		}
	}

	echo '<ul class="primary-menu">';

	foreach ( $items as $item ) {
		if ( empty( $item['url'] ) ) {
			continue;
		}

		printf(
			'<li class="menu-item"><a href="%1$s">%2$s</a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}

	echo '</ul>';
}

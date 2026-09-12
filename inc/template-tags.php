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
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>',
		),
		array(
			'label' => __( 'LinkedIn', 'boltfolio' ),
			'url'   => 'https://www.linkedin.com/in/nilesh-kanzariya-a8019b254/',
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13Zm1.78 13.02H3.56V9h3.56v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/></svg>',
		),
		array(
			'label' => __( 'Email', 'boltfolio' ),
			'url'   => 'mailto:nilesh.kanzariya912@gmail.com',
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>',
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
 * Lightning bolt brand mark.
 *
 * @return void
 */
function boltfolio_bolt_mark(): void {
	echo '<svg class="brand-bolt" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 3.6 13.2c-.3.4 0 .8.4.8H11l-1.9 7.6c-.1.5.5.8.8.4L19.4 10.8c.3-.4 0-.8-.4-.8H12l1.8-7.6c.1-.5-.5-.8-.8-.4Z"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG.
}

/**
 * Project-type badges for a project post.
 *
 * @return void
 */
function boltfolio_term_badges(): void {
	$terms = get_the_terms( get_the_ID(), Boltfolio_Projects::TAXONOMY );
	if ( ! is_array( $terms ) ) {
		return;
	}
	echo '<div class="badges">';
	foreach ( $terms as $term ) {
		printf(
			'<a class="badge" href="%1$s">%2$s</a>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name )
		);
	}
	echo '</div>';
}

/**
 * Render one project card (used on front page and archive).
 *
 * @param int|null $post_id Post ID. Defaults to current loop post.
 * @return void
 */
function boltfolio_project_card( ?int $post_id = null ): void {
	$post_id = $post_id ?? get_the_ID();
	if ( ! $post_id ) {
		return;
	}

	$permalink = get_permalink( $post_id );
	$github    = get_post_meta( $post_id, 'github_url', true );
	$live      = get_post_meta( $post_id, 'live_url', true );
	?>
	<article class="project-card">
		<div class="project-card-top">
			<?php
			// Reuse badge markup against an explicit post id.
			$terms = get_the_terms( $post_id, Boltfolio_Projects::TAXONOMY );
			if ( is_array( $terms ) ) {
				echo '<div class="badges">';
				foreach ( $terms as $term ) {
					printf( '<span class="badge">%s</span>', esc_html( $term->name ) );
				}
				echo '</div>';
			} else {
				echo '<span></span>';
			}
			?>
			<span class="card-links">
				<?php if ( $live ) : ?>
					<a class="card-icon" href="<?php echo esc_url( $live ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Visit live site', 'boltfolio' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"/><path d="M17 7H8"/><path d="M17 7v9"/></svg>
					</a>
				<?php endif; ?>
				<?php if ( $github ) : ?>
					<a class="card-icon" href="<?php echo esc_url( $github ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'View source on GitHub', 'boltfolio' ); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
					</a>
				<?php endif; ?>
			</span>
		</div>

		<h3><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>

		<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 26 ) ); ?></p>

		<?php if ( $github ) : ?>
			<div class="card-meta">
				<a href="<?php echo esc_url( $github ); ?>" target="_blank" rel="noopener noreferrer">
					<svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
					<?php esc_html_e( 'Source available on GitHub', 'boltfolio' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</article>
	<?php
}

/**
 * Simple breadcrumb trail: Home / Parent / Current.
 *
 * @return void
 */
function boltfolio_breadcrumbs(): void {
	$items = array(
		array(
			'label' => __( 'Home', 'boltfolio' ),
			'url'   => home_url( '/' ),
		),
	);

	$post = get_post();
	if ( $post instanceof WP_Post && $post->post_parent ) {
		$parent = get_post( $post->post_parent );
		if ( $parent ) {
			$items[] = array(
				'label' => get_the_title( $parent ),
				'url'   => get_permalink( $parent ),
			);
		}
	}

	$items[] = array(
		'label' => wp_trim_words( get_the_title(), 8 ),
		'url'   => '',
	);

	echo '<nav class="entry-meta" aria-label="' . esc_attr__( 'Breadcrumb', 'boltfolio' ) . '">';
	$last = count( $items ) - 1;
	foreach ( $items as $index => $item ) {
		if ( $item['url'] && $index !== $last ) {
			printf( '<a href="%1$s">%2$s</a>', esc_url( $item['url'] ), esc_html( $item['label'] ) );
		} elseif ( $index === $last ) {
			echo '<span aria-current="page">' . esc_html( $item['label'] ) . '</span>';
		} else {
			echo esc_html( $item['label'] );
		}
		if ( $index !== $last ) {
			echo ' <span aria-hidden="true">/</span> ';
		}
	}
	echo '</nav>';
}

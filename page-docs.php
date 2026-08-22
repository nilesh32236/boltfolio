<?php
/**
 * Template Name: Documentation Layout
 *
 * Docs-style layout: sticky sidebar navigation built from the page
 * hierarchy, a JS-generated "On this page" TOC rail on wide screens,
 * and a prev/next pager across sibling doc pages.
 *
 * @package boltfolio
 */

get_header();

while ( have_posts() ) :
	the_post();

	$parent_id = $post->post_parent ? (int) $post->post_parent : (int) get_the_ID();

	/**
	 * Pager chain: the docs overview page acts as the first entry,
	 * followed by its published children ordered by menu order.
	 */
	$siblings = array( $parent_id );

	$children = get_posts(
		array(
			'post_type'      => 'page',
			'post_parent'    => $parent_id,
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $children ) ) {
		$siblings = array_merge( $siblings, $children );
	}

	$current = (int) get_the_ID();
	$pos     = array_search( $current, $siblings, true );
	$prev_id = ( false !== $pos && $pos > 0 ) ? $siblings[ $pos - 1 ] : null;
	$next_id = ( false !== $pos && $pos < count( $siblings ) - 1 ) ? $siblings[ $pos + 1 ] : null;
	?>
	<article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="bolt-container">
			<div class="docs-layout">

				<aside class="docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'boltfolio' ); ?>">
					<p class="docs-sidebar-title"><?php echo esc_html( get_the_title( $parent_id ) ); ?></p>
					<nav class="docs-nav">
						<ul>
							<li class="docs-overview<?php echo ( get_the_ID() === $parent_id ? ' current_page_item' : '' ); ?>">
								<a href="<?php echo esc_url( get_permalink( $parent_id ) ); ?>"><?php esc_html_e( 'Overview', 'boltfolio' ); ?></a>
							</li>
							<?php
							wp_list_pages(
								array(
									'child_of'    => $parent_id,
									'title_li'    => '',
									'depth'       => 1,
									'sort_column' => 'menu_order, post_title',
								)
							);
							?>
						</ul>
					</nav>
				</aside>

				<div class="docs-content">
					<header class="entry-header">
						<?php boltfolio_breadcrumbs(); ?>
						<h1 class="entry-title"><?php the_title(); ?></h1>
						<?php if ( get_the_excerpt() ) : ?>
							<p class="entry-sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>
					</header>

					<div class="entry-content">
						<?php the_content(); ?>
					</div>

					<?php if ( $prev_id || $next_id ) : ?>
						<nav class="docs-pager" aria-label="<?php esc_attr_e( 'Documentation pages', 'boltfolio' ); ?>">
							<?php if ( $prev_id ) : ?>
								<a href="<?php echo esc_url( get_permalink( $prev_id ) ); ?>">
									<span class="pager-label">&larr; <?php esc_html_e( 'Previous', 'boltfolio' ); ?></span>
									<strong><?php echo esc_html( get_the_title( $prev_id ) ); ?></strong>
								</a>
							<?php else : ?>
								<span></span>
							<?php endif; ?>

							<?php if ( $next_id ) : ?>
								<a class="pager-next" href="<?php echo esc_url( get_permalink( $next_id ) ); ?>">
									<span class="pager-label"><?php esc_html_e( 'Next', 'boltfolio' ); ?> &rarr;</span>
									<strong><?php echo esc_html( get_the_title( $next_id ) ); ?></strong>
								</a>
							<?php else : ?>
								<span></span>
							<?php endif; ?>
						</nav>
					<?php endif; ?>
				</div>

				<aside class="docs-toc" aria-label="<?php esc_attr_e( 'On this page', 'boltfolio' ); ?>">
					<p class="docs-toc-title"><?php esc_html_e( 'On this page', 'boltfolio' ); ?></p>
					<ul class="docs-toc-list"></ul>
				</aside>

			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();

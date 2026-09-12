<?php
/**
 * Single doc template (docs CPT).
 *
 * Docs-style layout mirroring page-docs.php: sticky sidebar navigation for
 * the current doc's product, a JS-generated "On this page" TOC rail, and a
 * prev/next pager across sibling doc pages.
 *
 * @package boltfolio
 */

get_header();

while ( have_posts() ) :
	the_post();

	$doc_id  = (int) get_the_ID();
	$terms   = get_the_terms( $doc_id, Boltfolio_Docs::TAXONOMY );
	$product = ( is_array( $terms ) && $terms ) ? $terms[0] : null;

	/**
	 * Product tree: top-level docs assigned to the current doc's product.
	 * Untagged docs fall back to their own ancestor tree so the sidebar
	 * and pager keep working before terms are assigned.
	 */
	$roots_args = array(
		'post_type'      => Boltfolio_Docs::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post_parent'    => 0,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	);

	if ( $product ) {
		$roots_args['tax_query'] = array(
			array(
				'taxonomy' => Boltfolio_Docs::TAXONOMY,
				'field'    => 'term_id',
				'terms'    => array( $product->term_id ),
			),
		);
	}

	$roots = get_posts( $roots_args );

	if ( empty( $roots ) ) {
		$ancestor_ids = array_map( 'intval', get_post_ancestors( $doc_id ) );
		$roots        = array( get_post( $ancestor_ids ? (int) end( $ancestor_ids ) : $doc_id ) );
	}

	$root       = $roots[0];
	$root_id    = (int) $root->ID;
	$root_title = $product ? $product->name : get_the_title( $root_id );
	$ancestors  = array_map( 'intval', get_post_ancestors( $doc_id ) );

	/**
	 * Pager chain (mirrors page-docs.php): the parent doc acts as the first
	 * entry, followed by its published children ordered by menu order.
	 */
	$parent_id = $post->post_parent ? (int) $post->post_parent : $doc_id;

	$siblings = array_merge(
		array( $parent_id ),
		get_posts(
			array(
				'post_type'      => Boltfolio_Docs::POST_TYPE,
				'post_parent'    => $parent_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'fields'         => 'ids',
			)
		)
	);

	$pos     = array_search( $doc_id, $siblings, true );
	$prev_id = ( false !== $pos && $pos > 0 ) ? $siblings[ $pos - 1 ] : null;
	$next_id = ( false !== $pos && $pos < count( $siblings ) - 1 ) ? $siblings[ $pos + 1 ] : null;
	?>
	<article id="doc-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="bolt-container">
			<div class="docs-layout">

				<aside class="docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'boltfolio' ); ?>">
					<p class="docs-sidebar-title"><?php echo esc_html( $root_title ); ?></p>
					<nav class="docs-nav">
						<ul>
							<?php if ( 1 === count( $roots ) ) : ?>
								<li class="docs-overview<?php echo $doc_id === $root_id ? ' current_page_item' : ''; ?>">
									<a href="<?php echo esc_url( get_permalink( $root_id ) ); ?>"><?php esc_html_e( 'Overview', 'boltfolio' ); ?></a>
								</li>
								<?php Boltfolio_Docs::render_nav_tree( $root_id, $doc_id, $ancestors ); ?>
							<?php else : ?>
								<?php foreach ( $roots as $root_post ) : ?>
									<?php Boltfolio_Docs::render_nav_tree( (int) $root_post->ID, $doc_id, $ancestors, 0, true ); ?>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</nav>
				</aside>

				<div class="docs-content">
					<header class="entry-header">
						<nav class="entry-meta" aria-label="<?php esc_attr_e( 'Breadcrumb', 'boltfolio' ); ?>">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'boltfolio' ); ?></a>
							<span aria-hidden="true">/</span>
							<a href="<?php echo esc_url( get_post_type_archive_link( Boltfolio_Docs::POST_TYPE ) ); ?>"><?php esc_html_e( 'Docs', 'boltfolio' ); ?></a>
							<?php foreach ( array_reverse( $ancestors ) as $ancestor_id ) : ?>
								<span aria-hidden="true">/</span>
								<a href="<?php echo esc_url( get_permalink( $ancestor_id ) ); ?>"><?php echo esc_html( get_the_title( $ancestor_id ) ); ?></a>
							<?php endforeach; ?>
							<span aria-hidden="true">/</span>
							<span aria-current="page"><?php the_title(); ?></span>
						</nav>
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

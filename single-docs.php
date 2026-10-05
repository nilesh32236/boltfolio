<?php
/**
 * Single doc template.
 *
 * Three columns: the product tree, the reference itself, and an
 * "on this page" rail. Reference pages are titled by their source path,
 * so their heading is set in the same monospace face as the code.
 *
 * @package boltfolio
 */

get_header();

while ( have_posts() ) :
	the_post();

	$boltfolio_doc_id   = (int) get_the_ID();
	$boltfolio_root     = Boltfolio_Docs::root_of( $boltfolio_doc_id );
	$boltfolio_root_id  = $boltfolio_root ? (int) $boltfolio_root->ID : $boltfolio_doc_id;
	$boltfolio_terms    = get_the_terms( $boltfolio_doc_id, Boltfolio_Docs::TAXONOMY );
	$boltfolio_product  = ( is_array( $boltfolio_terms ) && $boltfolio_terms ) ? $boltfolio_terms[0] : null;
	$boltfolio_title    = Boltfolio_Docs::display_title( get_the_title() );
	$boltfolio_is_file  = Boltfolio_Docs::looks_like_path( get_the_title() );
	$boltfolio_ancestry = array_map( 'intval', get_post_ancestors( $boltfolio_doc_id ) );

	$boltfolio_product_name = $boltfolio_product ? $boltfolio_product->name : ( $boltfolio_root ? $boltfolio_root->post_title : '' );

	// Pager: the parent page leads, then its published children in order.
	$boltfolio_parent = $post->post_parent ? (int) $post->post_parent : $boltfolio_doc_id;

	$boltfolio_chain = array_merge(
		array( $boltfolio_parent ),
		get_posts(
			array(
				'post_type'      => Boltfolio_Docs::POST_TYPE,
				'post_parent'    => $boltfolio_parent,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'fields'         => 'ids',
			)
		)
	);

	$boltfolio_pos  = array_search( $boltfolio_doc_id, $boltfolio_chain, true );
	$boltfolio_prev = ( false !== $boltfolio_pos && $boltfolio_pos > 0 ) ? $boltfolio_chain[ $boltfolio_pos - 1 ] : null;
	$boltfolio_next = ( false !== $boltfolio_pos && $boltfolio_pos < count( $boltfolio_chain ) - 1 ) ? $boltfolio_chain[ $boltfolio_pos + 1 ] : null;
	?>

	<div class="shell">
		<div class="docs-layout">

			<aside class="docs-sidebar" data-docs-sidebar data-open="false" aria-label="<?php esc_attr_e( 'Documentation navigation', 'boltfolio' ); ?>">
				<div class="docs-sidebar__head">
					<div class="docs-product">
						<?php if ( $boltfolio_root ) : ?>
							<p class="docs-product__name">
								<a href="<?php echo esc_url( (string) get_permalink( $boltfolio_root_id ) ); ?>"><?php echo esc_html( $boltfolio_product_name ); ?></a>
							</p>
						<?php endif; ?>

						<?php if ( boltfolio_documented_version( $boltfolio_doc_id ) ) : ?>
							<span class="docs-product__ver">v<?php echo esc_html( boltfolio_documented_version( $boltfolio_doc_id ) ); ?></span>
						<?php endif; ?>
					</div>

					<button class="docs-search-trigger" type="button" data-search-open>
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
						<span><?php esc_html_e( 'Search documentation', 'boltfolio' ); ?></span>
						<kbd><?php echo esc_html( boltfolio_search_shortcut_label() ); ?></kbd>
					</button>

					<div class="docs-sidebar__row">
						<button class="docs-sidebar__toggle" type="button" aria-expanded="false" aria-controls="docs-nav">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
							<?php esc_html_e( 'Browse', 'boltfolio' ); ?>
						</button>
					</div>
				</div>

				<nav class="docs-nav" id="docs-nav" aria-label="<?php esc_attr_e( 'Documentation pages', 'boltfolio' ); ?>">
					<ul>
						<?php if ( $boltfolio_root ) : ?>
							<li class="docs-overview<?php echo $boltfolio_doc_id === $boltfolio_root_id ? ' current_page_item' : ''; ?>">
								<a href="<?php echo esc_url( (string) get_permalink( $boltfolio_root_id ) ); ?>"><?php esc_html_e( 'Overview', 'boltfolio' ); ?></a>
							</li>

							<?php Boltfolio_Docs::render_grouped_nav( $boltfolio_root_id, $boltfolio_doc_id, $boltfolio_ancestry ); ?>
						<?php endif; ?>
					</ul>
				</nav>
			</aside>

			<div class="docs-content">
				<header class="dochead">
					<?php boltfolio_docs_crumbs( $boltfolio_doc_id ); ?>

					<h1 class="dochead__title<?php echo $boltfolio_is_file ? '' : ' dochead__title--prose'; ?>"><?php echo esc_html( $boltfolio_title ); ?></h1>

					<?php if ( get_the_excerpt() ) : ?>
						<p class="dochead__sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>

					<div class="dochead__facts">
						<?php if ( $boltfolio_is_file ) : ?>
							<span><b><?php esc_html_e( 'Source', 'boltfolio' ); ?></b> <?php echo esc_html( get_the_title() ); ?></span>
						<?php endif; ?>

						<span><b><?php echo esc_html( (string) boltfolio_reading_time( $boltfolio_doc_id ) ); ?></b> <?php esc_html_e( 'min read', 'boltfolio' ); ?></span>

						<?php if ( $boltfolio_product_name ) : ?>
							<span><b><?php esc_html_e( 'Part of', 'boltfolio' ); ?></b> <?php echo esc_html( $boltfolio_product_name ); ?></span>
						<?php endif; ?>
					</div>
				</header>

				<div class="entry-content prose">
					<?php the_content(); ?>
				</div>

				<nav class="docs-pager" aria-label="<?php esc_attr_e( 'Documentation pages', 'boltfolio' ); ?>">
					<?php if ( $boltfolio_prev ) : ?>
						<a href="<?php echo esc_url( (string) get_permalink( $boltfolio_prev ) ); ?>">
							<span class="pager-label">&larr; <?php esc_html_e( 'Previous', 'boltfolio' ); ?></span>
							<strong><?php echo esc_html( Boltfolio_Docs::display_title( get_the_title( $boltfolio_prev ) ) ); ?></strong>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>

					<?php if ( $boltfolio_next ) : ?>
						<a class="pager-next" href="<?php echo esc_url( (string) get_permalink( $boltfolio_next ) ); ?>">
							<span class="pager-label"><?php esc_html_e( 'Next', 'boltfolio' ); ?> &rarr;</span>
							<strong><?php echo esc_html( Boltfolio_Docs::display_title( get_the_title( $boltfolio_next ) ) ); ?></strong>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>
				</nav>
			</div>

			<aside class="docs-toc" aria-label="<?php esc_attr_e( 'On this page', 'boltfolio' ); ?>">
				<p class="docs-toc__title"><?php esc_html_e( 'On this page', 'boltfolio' ); ?></p>
				<ul class="docs-toc-list"></ul>
			</aside>

		</div>
	</div>

	<?php
endwhile;

get_footer();

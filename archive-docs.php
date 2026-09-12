<?php
/**
 * Documentation hub — the archive for the docs post type.
 *
 * A reader arriving here needs a route, not a list. Each product gets a
 * "start here" track in document order, plus a count of what sits
 * underneath it, so the shape of the reference is visible before you
 * commit to a page.
 *
 * @package boltfolio
 */

get_header();

$boltfolio_terms = get_terms(
	array(
		'taxonomy'   => Boltfolio_Docs::TAXONOMY,
		'hide_empty' => false,
	)
);

if ( is_wp_error( $boltfolio_terms ) ) {
	$boltfolio_terms = array();
}
?>

<div class="shell docs-hub">

	<header class="page-head">
		<p class="eyebrow"><?php esc_html_e( 'Documentation', 'boltfolio' ); ?></p>

		<h1 class="page-head__title"><?php esc_html_e( 'Plugin documentation', 'boltfolio' ); ?></h1>

		<p class="page-head__sub">
			<?php
			printf(
				/* translators: %d: number of documentation pages. */
				esc_html__( 'Every guide, hook, WP-CLI command, REST route and class in the reference — %d pages, written against the source rather than around it.', 'boltfolio' ),
				(int) wp_count_posts( Boltfolio_Docs::POST_TYPE )->publish
			);
			?>
		</p>

		<div class="btn-row" style="margin-top:1.5rem">
			<button class="docs-search-trigger" type="button" data-search-open style="max-width:22rem">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				<span><?php esc_html_e( 'Search the documentation', 'boltfolio' ); ?></span>
				<kbd><?php echo esc_html( boltfolio_search_shortcut_label() ); ?></kbd>
			</button>
		</div>
	</header>

	<?php if ( ! $boltfolio_terms ) : ?>

		<div class="empty mt-3">
			<p class="empty__title"><?php esc_html_e( 'No documentation yet', 'boltfolio' ); ?></p>
			<p class="empty__text"><?php esc_html_e( 'Documentation products will appear here once a doc is assigned to one.', 'boltfolio' ); ?></p>
		</div>

	<?php else : ?>

		<?php
		foreach ( $boltfolio_terms as $boltfolio_term ) :
			$boltfolio_roots = get_posts(
				array(
					'post_type'      => Boltfolio_Docs::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post_parent'    => 0,
					'orderby'        => 'menu_order title',
					'order'          => 'ASC',
					'tax_query'      => array(
						array(
							'taxonomy' => Boltfolio_Docs::TAXONOMY,
							'field'    => 'term_id',
							'terms'    => array( $boltfolio_term->term_id ),
						),
					),
				)
			);

			if ( ! $boltfolio_roots ) {
				continue;
			}

			$boltfolio_root = $boltfolio_roots[0];
			?>
			<section class="section section--tight" id="product-<?php echo esc_attr( $boltfolio_term->slug ); ?>">
				<header class="shead">
					<div class="shead__top">
						<div>
							<p class="eyebrow"><?php esc_html_e( 'Product', 'boltfolio' ); ?></p>
							<h2 class="shead__title"><?php echo esc_html( $boltfolio_term->name ); ?></h2>
						</div>
						<p class="shead__aside">
							<?php
							printf(
								/* translators: %d: number of documentation pages. */
								esc_html__( '%d pages covering setup, configuration and the full source reference.', 'boltfolio' ),
								(int) $boltfolio_term->count
							);

							if ( boltfolio_documented_version() ) {
								echo ' <span class="meta">v' . esc_html( boltfolio_documented_version() ) . '</span>';
							}
							?>
						</p>
					</div>
				</header>

				<div class="track">
					<a class="track__item" href="<?php echo esc_url( (string) get_permalink( $boltfolio_root->ID ) ); ?>">
						<span class="track__num">00</span>
						<span class="track__title">
							<?php echo esc_html( $boltfolio_root->post_title ); ?>
							<span class="track__desc"><?php echo esc_html( wp_trim_words( get_the_excerpt( $boltfolio_root ), 22 ) ); ?></span>
						</span>
						<span class="track__meta"><?php esc_html_e( 'Overview', 'boltfolio' ); ?></span>
					</a>

					<?php
					$boltfolio_children = get_posts(
						array(
							'post_type'      => Boltfolio_Docs::POST_TYPE,
							'post_status'    => 'publish',
							'posts_per_page' => -1,
							'post_parent'    => (int) $boltfolio_root->ID,
							'orderby'        => 'menu_order title',
							'order'          => 'ASC',
						)
					);

					foreach ( $boltfolio_children as $boltfolio_i => $boltfolio_child ) :
						$boltfolio_total = Boltfolio_Docs::count_descendants( (int) $boltfolio_child->ID );
						?>
						<a class="track__item" href="<?php echo esc_url( (string) get_permalink( $boltfolio_child->ID ) ); ?>">
							<span class="track__num"><?php echo esc_html( str_pad( (string) ( $boltfolio_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="track__title">
								<?php echo esc_html( $boltfolio_child->post_title ); ?>
								<span class="track__desc"><?php echo esc_html( wp_trim_words( get_the_excerpt( $boltfolio_child ), 22 ) ); ?></span>
							</span>
							<span class="track__meta">
								<?php
								if ( $boltfolio_total ) {
									printf(
										/* translators: %d: number of nested pages. */
										esc_html__( '%d pages', 'boltfolio' ),
										(int) $boltfolio_total
									);
								} else {
									esc_html_e( 'Read', 'boltfolio' );
								}
								?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

	<?php endif; ?>

</div>

<?php get_footer(); ?>

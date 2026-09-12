<?php
/**
 * Docs archive: product hubs.
 *
 * Reuses the page-docs.php visual shell minus the sidebar/TOC: an intro
 * header plus a card list of top-level docs (one per product), styled with
 * the existing project-card classes.
 *
 * @package boltfolio
 */

get_header();

$hubs = get_posts(
	array(
		'post_type'      => Boltfolio_Docs::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post_parent'    => 0,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	)
);
?>
<div class="bolt-container">
	<header class="archive-header">
		<p class="section-kicker"><?php esc_html_e( 'Resources', 'boltfolio' ); ?></p>
		<h1 class="page-title"><?php esc_html_e( 'Documentation', 'boltfolio' ); ?></h1>
		<p class="section-desc"><?php esc_html_e( 'Install guides, feature references, configuration and troubleshooting docs — organized per product.', 'boltfolio' ); ?></p>
	</header>

	<?php if ( $hubs ) : ?>
		<div class="project-grid">
			<?php foreach ( $hubs as $hub ) : ?>
				<?php
				$hub_permalink = get_permalink( $hub );
				$hub_terms     = get_the_terms( $hub->ID, Boltfolio_Docs::TAXONOMY );
				?>
				<article class="project-card">
					<?php if ( is_array( $hub_terms ) && $hub_terms ) : ?>
						<span class="badge badge-muted"><?php echo esc_html( $hub_terms[0]->name ); ?></span>
					<?php endif; ?>
					<h2><a href="<?php echo esc_url( $hub_permalink ); ?>"><?php echo esc_html( get_the_title( $hub ) ); ?></a></h2>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $hub ), 26 ) ); ?></p>
					<div class="card-meta">
						<a href="<?php echo esc_url( $hub_permalink ); ?>"><?php esc_html_e( 'Read docs', 'boltfolio' ); ?> <span aria-hidden="true">&rarr;</span></a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'Documentation is being prepared. Check back soon.', 'boltfolio' ); ?></p>
	<?php endif; ?>
</div><!-- .bolt-container -->

<?php get_footer();
